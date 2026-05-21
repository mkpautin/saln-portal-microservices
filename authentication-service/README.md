# Authentication Service

Authentication and account management API for SALN Portal. Built with Laravel, PostgreSQL, and deployed on AWS using Bref Serverless.

## Overview

- Serves authentication endpoints under `/api/auth` in the serverless configuration.
- Uses JWT authentication and email delivery integrations.
- Includes Laravel queues and cache backed by PostgreSQL.

## Local Development

Create your environment file:

```bash
cp .env.example .env
```

Optional: start the local Postgres container:

```bash
docker compose up -d
```

Install dependencies and initialize:

```bash
composer run setup
```

Start the development stack:

```bash
composer run dev
```

Run tests:

```bash
composer run test
```

## Artisan Commands

Database migrations (ensure the database is running and your `DB_*` values are correct):

```bash
php artisan migrate
php artisan migrate:status
```

Configuration and optimization helpers:

```bash
php artisan config:clear
php artisan config:cache
php artisan route:cache
php artisan event:cache
php artisan optimize
php artisan optimize:clear
```

Use `optimize` for production or when you want cached config/routes/events for performance. Use `optimize:clear` during local development or after changing `.env` or routes to avoid stale caches.

## Configuration

- Local environment template: [authentication-service/.env.example](authentication-service/.env.example)
- Deployment config: [authentication-service/serverless.yml](authentication-service/serverless.yml)
- Local Postgres compose: [authentication-service/docker-compose.yml](authentication-service/docker-compose.yml)

Key environment variables:

- `DB_CONNECTION`, `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`
- `JWT_SECRET`
- `RESEND_API_KEY`
- `MAIL_MAILER`, `MAIL_HOST`, `MAIL_PORT`, `MAIL_USERNAME`, `MAIL_PASSWORD`, `MAIL_FROM_ADDRESS`, `MAIL_FROM_NAME`
