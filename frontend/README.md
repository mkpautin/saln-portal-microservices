# Frontend

Vue 3 application for SALN Portal, built with Vite. It connects to the authentication and SALN form APIs through environment-configured base URLs.

## Local Development

Install dependencies:

```bash
pnpm install
```

Create your environment file:

```bash
cp .env.example .env
```

Start the dev server:

```bash
pnpm dev
```

## Scripts

```bash
pnpm build
pnpm preview
pnpm lint
pnpm format
```

## Configuration

- Environment template: [frontend/.env.example](frontend/.env.example)

Required environment variables:

```bash
VITE_AUTH_API_URL=
VITE_SALN_API_URL=
```
