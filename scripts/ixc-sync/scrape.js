#!/usr/bin/env node
/**
 * Logs into the IXC Provedor admin panel (no API on this account — see
 * scripts/ixc-sync/README.md for why this exists) and pulls service orders
 * for one branch + a short list of technicians.
 *
 * Strategy: rather than parsing the rendered page's HTML/CSS (which
 * breaks on every visual tweak IXC ships), this listens to every
 * XHR/fetch response the page makes while it loads and while the filters
 * are applied, and keeps any JSON response that appears to contain
 * appointment/OS data. That's the same data the page itself renders from,
 * just captured before it's turned into HTML — closer to using an
 * internal API than scraping the DOM, without needing IXC to publish one.
 *
 * Contract with the Laravel side (App\Services\Ixc\IxcSyncService):
 *   - stdout: exactly one JSON object on success, nothing else.
 *   - stderr: human-readable progress/debug logging.
 *   - exit code 0 = success, non-zero = failure (stderr has the reason).
 */

import { chromium } from 'playwright';
import { mkdirSync, writeFileSync } from 'node:fs';
import { config } from './config.js';

const DEBUG_DIR = new URL('./debug/', import.meta.url).pathname;

function log(...args) {
  console.error('[ixc-sync]', ...args);
}

async function main() {
  if (config.debug) {
    mkdirSync(DEBUG_DIR, { recursive: true });
  }

  const browser = await chromium.launch({ headless: process.env.HEADLESS !== '0' });
  const context = await browser.newContext({ ignoreHTTPSErrors: false });
  const page = await context.newPage();

  /** @type {Array<{url: string, status: number, body: unknown}>} */
  const capturedJson = [];

  page.on('response', async (response) => {
    const contentType = response.headers()['content-type'] || '';

    if (!contentType.includes('json')) {
      return;
    }

    try {
      const body = await response.json();
      capturedJson.push({ url: response.url(), status: response.status(), body });
    } catch {
      // Not actually JSON despite the header, or body already consumed — skip.
    }
  });

  try {
    log('Opening', config.baseUrl + config.selectors.loginPage);
    await page.goto(config.baseUrl + config.selectors.loginPage, { timeout: config.timeouts.navigation });

    await login(page);
    await goToSchedulingScreen(page);
    await applyFilters(page);

    // Let any XHR triggered by the filter apply/calendar render settle.
    await page.waitForLoadState('networkidle', { timeout: config.timeouts.navigation }).catch(() => {
      log('networkidle timed out — continuing with whatever was captured so far');
    });

    const unscheduled = extractUnscheduledFromCapture(capturedJson)
      ?? await extractUnscheduledFromDom(page);

    const scheduled = extractScheduledFromCapture(capturedJson, config.technicians);

    if (config.debug) {
      await page.screenshot({ path: DEBUG_DIR + 'page.png', fullPage: true });
      writeFileSync(DEBUG_DIR + 'captured-responses.json', JSON.stringify(capturedJson, null, 2));
      writeFileSync(DEBUG_DIR + 'page.html', await page.content());
      log(`Debug artifacts written to ${DEBUG_DIR}`);
      log(`Captured ${capturedJson.length} JSON response(s) — inspect captured-responses.json to find the real appointment payload and tighten extractScheduledFromCapture()/extractUnscheduledFromCapture() in this file.`);
    }

    const result = {
      synced_at: new Date().toISOString(),
      branch: config.branchName,
      technicians: config.technicians,
      unscheduled,
      scheduled,
      // Kept so the Laravel side can log/inspect what came back even when
      // the heuristics above didn't confidently parse it — remove once
      // the extraction is calibrated and stable.
      _raw_capture_count: capturedJson.length,
    };

    process.stdout.write(JSON.stringify(result));
  } catch (error) {
    if (config.debug) {
      await page.screenshot({ path: DEBUG_DIR + 'error.png', fullPage: true }).catch(() => {});
    }

    log('FAILED:', error.message);
    process.exitCode = 1;
  } finally {
    await browser.close();
  }
}

async function login(page) {
  log('Logging in as', config.username);

  await page.locator(config.selectors.usernameField).first().fill(config.username);
  await page.locator(config.selectors.passwordField).first().fill(config.password);
  await Promise.all([
    page.waitForLoadState('networkidle', { timeout: config.timeouts.navigation }).catch(() => {}),
    page.locator(config.selectors.loginSubmit).first().click(),
  ]);

  log('Login submitted, current URL:', page.url());
}

async function goToSchedulingScreen(page) {
  const clicks = config.navigationClicks.length > 0
    ? config.navigationClicks
    : [config.selectors.schedulingMenuItem];

  for (const selector of clicks) {
    log('Clicking', selector);
    await page.locator(selector).first().click({ timeout: config.timeouts.action });
    await page.waitForTimeout(500);
  }
}

async function applyFilters(page) {
  try {
    log('Setting branch filter to', config.branchName);
    await page.locator(config.selectors.branchFilterInput).first().fill(config.branchName, { timeout: config.timeouts.action });
  } catch {
    log('Could not set branch filter with the configured selector — skipping (adjust selectors.branchFilterInput)');
  }

  for (const technician of config.technicians) {
    try {
      log('Adding technician filter:', technician);
      await page.locator(config.selectors.technicianFilterInput).first().fill(technician, { timeout: config.timeouts.action });
      await page.keyboard.press('Enter').catch(() => {});
    } catch {
      log('Could not set technician filter for', technician, '— skipping (adjust selectors.technicianFilterInput)');
    }
  }

  try {
    await page.locator(config.selectors.applyFiltersButton).first().click({ timeout: config.timeouts.action });
  } catch {
    log('Could not click "Aplicar filtros" — filters may not have been applied (adjust selectors.applyFiltersButton)');
  }
}

/**
 * Heuristic: any captured JSON response whose body (stringified) mentions
 * one of the configured technician names is very likely the appointment
 * payload — service order lists are pretty much the only thing on this
 * screen that would contain a technician's name in a JSON response.
 *
 * @param {Array<{url: string, body: unknown}>} captured
 * @param {string[]} technicianNames
 */
function extractScheduledFromCapture(captured, technicianNames) {
  const shortNames = technicianNames.map((name) => name.replace(/\s*\(.*\)$/, '').trim());

  const match = captured.find(({ body }) => {
    const text = JSON.stringify(body);

    return shortNames.some((name) => text.includes(name));
  });

  if (!match) {
    return [];
  }

  return normalizeToArray(match.body);
}

/**
 * Same idea for the "OS não agendadas" list — looks for a captured
 * response shaped like a list of records with an "id"-ish key, since we
 * don't know the exact field names IXC uses without seeing the real
 * payload (see captured-responses.json in debug mode).
 */
function extractUnscheduledFromCapture(captured) {
  const candidate = captured.find(({ body }) => {
    const list = normalizeToArray(body);

    return list.length > 0 && list.every((row) => typeof row === 'object' && row !== null
      && Object.keys(row).some((key) => /^id/i.test(key)));
  });

  return candidate ? normalizeToArray(candidate.body) : null;
}

function normalizeToArray(body) {
  if (Array.isArray(body)) {
    return body;
  }

  if (body && typeof body === 'object') {
    const firstArrayValue = Object.values(body).find((value) => Array.isArray(value));

    if (firstArrayValue) {
      return firstArrayValue;
    }
  }

  return [];
}

/**
 * DOM fallback for the unscheduled list, based on the "ID: ... Nome: ...
 * Assunto: ... Endereço: ..." card layout visible in the screenshot.
 * Only used if nothing in the captured JSON looked like the list — meaning
 * that panel is likely server-rendered HTML, not a separate XHR call.
 */
async function extractUnscheduledFromDom(page) {
  const cards = await page.locator(config.selectors.unscheduledCard).all();

  if (cards.length === 0) {
    log('No unscheduled cards found via DOM fallback either — adjust selectors.unscheduledCard');

    return [];
  }

  const results = [];

  for (const card of cards) {
    const text = (await card.innerText()).replace(/\s+/g, ' ').trim();

    results.push({
      raw_text: text,
      external_id: text.match(/ID:\s*(\d+)/)?.[1] ?? null,
      customer_name: text.match(/Nome:\s*([^A-Z]+?)(?=\s+Assunto:|$)/)?.[1]?.trim() ?? null,
      subject: text.match(/Assunto:\s*([^A-Z]+?)(?=\s+Endereço:|$)/)?.[1]?.trim() ?? null,
      address: text.match(/Endereço:\s*(.+?)(?=\s+Data Reserva:|$)/)?.[1]?.trim() ?? null,
    });
  }

  return results;
}

main();
