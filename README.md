# Operix

**Enterprise Field Service Management Platform**

> Em desenvolvimento ativo. Este README será expandido com a documentação completa (arquitetura, stack, screenshots, API, instalação) na fase de Documentation do roadmap do projeto.

## Stack

- PHP 8.2 / Laravel 12
- Livewire 3 + Alpine.js + Tailwind CSS 4 + Vite
- MySQL/MariaDB
- Pest (testes) · Pint (code style) · Larastan (análise estática)

## Ambiente de desenvolvimento

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
php artisan migrate
npm run dev
php artisan serve
```

## Documentação

A documentação técnica completa está sendo construída em [`docs/`](docs/).
