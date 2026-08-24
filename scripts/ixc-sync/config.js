import 'dotenv/config';

/**
 * Every selector/URL below is a best guess from the screenshots you shared,
 * not something verified against the live system (I have no way to reach
 * sistema.fenixwireless.com.br or log in from here). Expect to adjust the
 * `selectors` block after the first calibration run — see README.md.
 */
export const config = {
  baseUrl: required('IXC_BASE_URL'),
  username: required('IXC_USERNAME'),
  password: required('IXC_PASSWORD'),
  branchName: process.env.IXC_BRANCH_NAME || 'FENIX LITORAL-',
  technicians: (process.env.IXC_TECHNICIANS || 'GUSTAVO BEZZA (LITORAL),CEZAR GUEDES (LITORAL)')
    .split(',')
    .map((name) => name.trim())
    .filter(Boolean),
  debug: process.env.IXC_DEBUG === '1',

  // Adjust these to match the real DOM once you've run in debug mode and
  // inspected ./debug/page.png + ./debug/*.html.
  selectors: {
    // Login screen.
    loginPage: '/adm.php',
    usernameField: 'input[name="usuario"], input[name="login"], #usuario',
    passwordField: 'input[name="senha"], input[type="password"]',
    loginSubmit: 'button[type="submit"], input[type="submit"]',

    // Left-hand menu entry that opens the scheduling calendar — the
    // screenshot shows a "Planejamento" top-level item; the calendar
    // itself may be a sub-item under it. Update this selector (or the
    // navigationClicks list below) to match the real click path.
    schedulingMenuItem: 'text=Planejamento',

    // Filter panel on the scheduling screen (see the 3rd screenshot).
    branchFilterInput: 'text=Filial >> xpath=following::input[1]',
    technicianFilterInput: 'text=Colaborador >> xpath=following::input[1]',
    applyFiltersButton: 'text=Aplicar filtros',

    // Cards in the "OS não agendadas" side list.
    unscheduledCard: '.os-nao-agendada, [class*="unscheduled"]',
  },

  /**
   * If a single menu click doesn't reach the calendar, list every click in
   * order here (each a Playwright selector string) and the script will
   * click through them one at a time before applying filters.
   */
  navigationClicks: [],

  timeouts: {
    navigation: 30_000,
    action: 10_000,
  },
};

function required(key) {
  const value = process.env[key];

  if (!value) {
    throw new Error(`Missing required env var ${key}. Copy .env.example to .env and fill it in, or pass it from the parent process.`);
  }

  return value;
}
