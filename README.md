# SALN Portal

[![License](https://img.shields.io/badge/license-TBD-lightgrey)](#license)
[![Version](https://img.shields.io/badge/version-0.0.0-blue)](#)
[![Build](https://img.shields.io/badge/build-unknown-lightgrey)](#)

SALN Portal is a microservices-based web application for SALN form filing. It supports JSON export/import, autosave, and dynamic PDF generation from form input.

## About

SALN Portal consists of two Laravel services and a Vue frontend, deployed on AWS using Bref Serverless.

High-level features:
- JSON export/import of SALN form data.
- Autosave for in-progress forms.
- PDF generation on AWS with S3 storage and pre-signed URL delivery.
- Separate authentication and SALN form APIs.

## Project Structure

| Path | Description |
| --- | --- |
| [authentication-service/](authentication-service/) | Laravel authentication API (JWT and email integrations are configured in dependencies). |
| [salnForm-service/](salnForm-service/) | Laravel SALN form API and PDF generation service. |
| [frontend/](frontend/) | Vue 3 application built with Vite. |

## Prerequisites

| Requirement | Notes |
| --- | --- |
| PHP 8.3+ | Required by both Laravel services (see composer.json). |
| Composer | For PHP dependencies and Laravel scripts. |
| Node.js | Required for Vite builds in the Laravel services. |
| Node.js 20.19+ or >=22.12 | Required by the frontend (see package.json engines). |
| npm | Used by Laravel service build scripts. |
| pnpm | Used by the frontend scripts. |
| PostgreSQL | Required by both services. |
| Docker (optional) | Provided docker-compose files for local Postgres. |

## Installation Guide

### Authentication Service

1. From the repo root:

```bash
cd authentication-service
```

2. Create and update your environment file:

```bash
cp .env.example .env
```

3. If you want a local Postgres instance, start it with the provided compose file:

```bash
docker compose up -d
```

4. Install dependencies and run initial setup:

```bash
composer run setup
```

5. Update database settings in your .env to match your Postgres instance. If you use the provided docker-compose file, note that it maps port 5434 to the container.

6. Start the development stack:

```bash
composer run dev
```

### SALN Form Service

1. From the repo root:

```bash
cd salnForm-service
```

2. Create and update your environment file:

```bash
cp .env.example .env
```

3. If you want a local Postgres instance, start it with the provided compose file:

```bash
docker compose up -d
```

4. Install dependencies and run initial setup:

```bash
composer run setup
```

5. Update database settings in your .env to match your Postgres instance. If you use the provided docker-compose file, note that it maps port 5433 to the container.

6. Start the development stack:

```bash
composer run dev
```

### Frontend

1. From the repo root:

```bash
cd frontend
```

2. Install dependencies:

```bash
pnpm install
```

3. Create a local environment file and set the API URLs:

```bash
cp .env.example .env
```

4. Start the dev server:

```bash
pnpm dev
```

## Usage Examples

Run each service in its own terminal:

```bash
cd authentication-service
composer run dev
```

```bash
cd salnForm-service
composer run dev
```

```bash
cd frontend
pnpm dev
```

Run tests for a Laravel service:

```bash
cd authentication-service
composer run test
```

## Configuration

### Environment Files

- Authentication service local env: [authentication-service/.env.example](authentication-service/.env.example)
- SALN form service local env: [salnForm-service/.env.example](salnForm-service/.env.example)
- Frontend env: [frontend/.env.example](frontend/.env.example)

Frontend environment variables:

```bash
VITE_AUTH_API_URL=
VITE_SALN_API_URL=
```

### Docker Compose (Local Postgres)

- Authentication service DB compose file: [authentication-service/docker-compose.yml](authentication-service/docker-compose.yml)
- SALN form service DB compose file: [salnForm-service/docker-compose.yml](salnForm-service/docker-compose.yml)


SALN form PDF generation settings (from the SALN form service deployment config):

| Variable | Purpose |
| --- | --- |
| SALN_PDF_S3_BUCKET | Private S3 bucket for generated PDFs. |
| SALN_PDF_S3_PREFIX | Object prefix for generated PDFs (default: saln-pdf). |
| SALN_PDF_URL_TTL_MINUTES | Pre-signed URL lifetime in minutes (default: 10). |
| PDFTK_BINARY | Path to the Lambda-compatible pdftk binary when pdftk is not available on PATH. |

PDF template file used by the SALN form service:
- [salnForm-service/storage/app/pdf-templates/saln_fillable_form.pdf](salnForm-service/storage/app/pdf-templates/saln_fillable_form.pdf)

## Troubleshooting & FAQs

**Q: Database connection fails for a service.**
A: Verify the DB_* variables in your .env match the running Postgres instance. If using the provided docker-compose files, the host ports are 5434 (authentication) and 5433 (SALN form).

**Q: JWT errors during authentication.**
A: Ensure JWT_SECRET is set in your local .env or via the SSM parameter used in the serverless config.

**Q: Email sending fails in the authentication service.**
A: Check RESEND_API_KEY and the MAIL_* variables in your local .env. The production settings are defined in the authentication service serverless config.

**Q: PDF generation fails in the SALN form service.**
A: Confirm SALN_PDF_* variables, PDFTK_BINARY, and S3 permissions (s3:PutObject and s3:GetObject) are configured as in the serverless config. Also verify the PDF template file exists.

**Q: Frontend cannot reach the APIs.**
A: Make sure VITE_AUTH_API_URL and VITE_SALN_API_URL are set in your frontend .env file.

## Contribution Guidelines

Placeholder: contribution process, coding standards, and review workflow.

## License

Placeholder: project license information.
