# SALN Form Service

SALN form API and PDF generation service for SALN Portal. Built with Laravel, PostgreSQL, and deployed on AWS using Bref Serverless.

## Overview

- Serves SALN form API routes under `/api/saln` in the serverless configuration.
- Generates PDFs in AWS Lambda and returns pre-signed S3 URLs.
- Uses a fillable PDF template stored in the service repository.

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

- Local environment template: [salnForm-service/.env.example](salnForm-service/.env.example)
- Deployment config: [salnForm-service/serverless.yml](salnForm-service/serverless.yml)
- Local Postgres compose: [salnForm-service/docker-compose.yml](salnForm-service/docker-compose.yml)

PDF generation settings:

- `SALN_PDF_DISK`
- `SALN_PDF_S3_BUCKET`
- `SALN_PDF_S3_PREFIX`
- `SALN_PDF_URL_TTL_MINUTES`
- `PDFTK_BINARY`

PDF template file:

- [salnForm-service/storage/app/pdf-templates/saln_fillable_form.pdf](salnForm-service/storage/app/pdf-templates/saln_fillable_form.pdf)

AWS permissions: the Lambda role needs `s3:PutObject` and `s3:GetObject` for the PDF bucket.
