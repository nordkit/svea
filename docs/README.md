# Svea PHP SDK — Documentation

This directory contains the [VitePress](https://vitepress.dev/) source for the documentation site published at **https://nordkit.github.io/svea/**.

## Local development

Requires Node 20+.

```bash
cd docs
npm install
npm run dev      # starts the dev server on http://localhost:5173/svea/
npm run build    # builds to .vitepress/dist/
npm run preview  # serves the built site
```

## Structure

```
docs/
├── .vitepress/
│   └── config.ts          # site config — nav, sidebar, SEO, theme
├── public/
│   └── logo.svg           # static assets (served as-is)
├── guide/                 # narrative guides (left-nav under /guide/)
│   ├── getting-started.md
│   ├── installation.md
│   ├── quick-start.md
│   ├── configuration.md
│   ├── authentication.md
│   ├── laravel.md
│   ├── standalone.md
│   ├── testing.md
│   ├── error-handling.md
│   ├── retries-idempotency.md
│   └── middleware.md
├── api/                   # API reference (left-nav under /api/)
│   ├── checkout.md
│   ├── admin.md
│   ├── subscriptions.md
│   ├── webhooks.md
│   └── response-objects.md
├── index.md               # home page (hero + features)
└── package.json
```

## Deployment

`.github/workflows/docs.yml` builds and deploys to GitHub Pages on every push to `main` that touches `docs/**`.

To enable: in the repo settings → **Pages** → **Source** → **GitHub Actions**.

## Custom domain (optional)

To serve from a custom domain (e.g. `svea.nordkit.dev`):

1. Add the domain in repo Settings → Pages → Custom domain.
2. Add a `docs/public/CNAME` file containing the domain.
3. In `.vitepress/config.ts`, change `base: '/svea/'` to `base: '/'`.

## Editing

Every page has an "Edit this page on GitHub" link in the footer for one-click PRs. Conventional Commits are encouraged (`docs: …`).

