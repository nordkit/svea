import { defineConfig } from 'vitepress'

const SITE_URL = 'https://nordkit.github.io/svea/'
const REPO_URL = 'https://github.com/nordkit/svea'

export default defineConfig({
  title: 'Svea PHP SDK',
  description:
    'Modern PHP SDK for Svea Checkout — Checkout, Payment Admin, Subscriptions and Webhooks. Framework-agnostic core with first-class Laravel support.',
  lang: 'en-US',

  // Deploy under nordkit.github.io/svea/. Change to '/' for a custom domain.
  base: '/svea/',
  cleanUrls: true,
  lastUpdated: true,

  sitemap: {
    hostname: SITE_URL,
  },

  head: [
    ['meta', { name: 'theme-color', content: '#0066cc' }],
    ['meta', { property: 'og:type', content: 'website' }],
    ['meta', { property: 'og:title', content: 'Svea PHP SDK — nordkit/svea' }],
    [
      'meta',
      {
        property: 'og:description',
        content:
          'Modern PHP SDK for Svea Checkout — Checkout, Admin, Subscriptions, Webhooks. Laravel-ready.',
      },
    ],
    ['meta', { property: 'og:url', content: SITE_URL }],
    ['meta', { name: 'twitter:card', content: 'summary_large_image' }],
  ],

  themeConfig: {
    nav: [
      { text: 'Guide', link: '/guide/getting-started', activeMatch: '/guide/' },
      { text: 'API Reference', link: '/api/checkout', activeMatch: '/api/' },
      {
        text: 'Resources',
        items: [
          { text: 'Packagist', link: 'https://packagist.org/packages/nordkit/svea' },
          { text: 'Changelog', link: `${REPO_URL}/blob/main/CHANGELOG.md` },
          { text: 'Contributing', link: `${REPO_URL}/blob/main/CONTRIBUTING.md` },
          { text: 'Official Svea API docs', link: 'https://paymentsdocs.svea.com/' },
        ],
      },
    ],

    sidebar: {
      '/guide/': [
        {
          text: 'Getting Started',
          items: [
            { text: 'Introduction', link: '/guide/getting-started' },
            { text: 'Installation', link: '/guide/installation' },
            { text: 'Quick Start', link: '/guide/quick-start' },
            { text: 'Configuration', link: '/guide/configuration' },
            { text: 'Authentication', link: '/guide/authentication' },
          ],
        },
        {
          text: 'Core Concepts',
          items: [
            { text: 'Fluent Builders & Conditionable', link: '/guide/fluent-builders' },
          ],
        },
        {
          text: 'Integrations',
          items: [
            { text: 'Laravel', link: '/guide/laravel' },
            { text: 'Standalone (no Laravel)', link: '/guide/standalone' },
          ],
        },
        {
          text: 'Migration',
          items: [
            { text: 'From sveaekonomi/checkout', link: '/guide/migration-from-official-sdk' },
          ],
        },
        {
          text: 'Advanced',
          items: [
            { text: 'Testing & Fakes', link: '/guide/testing' },
            { text: 'Error Handling', link: '/guide/error-handling' },
            { text: 'Retries & Idempotency', link: '/guide/retries-idempotency' },
            { text: 'Custom Middleware', link: '/guide/middleware' },
          ],
        },
      ],
      '/api/': [
        {
          text: 'API Reference',
          items: [
            { text: 'Checkout', link: '/api/checkout' },
            { text: 'Payment Admin', link: '/api/admin' },
            { text: 'Subscriptions', link: '/api/subscriptions' },
            { text: 'Webhooks', link: '/api/webhooks' },
            { text: 'Response Objects', link: '/api/response-objects' },
          ],
        },
      ],
    },

    socialLinks: [{ icon: 'github', link: REPO_URL }],

    editLink: {
      pattern: 'https://github.com/nordkit/svea/edit/main/docs/:path',
      text: 'Edit this page on GitHub',
    },

    search: {
      provider: 'local',
    },

    footer: {
      message: 'Released under the MIT License.',
      copyright: 'Copyright © 2024–2026 Nordkit',
    },

    outline: {
      level: [2, 3],
    },
  },
})
