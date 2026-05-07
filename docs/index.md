---
layout: home

hero:
  name: Svea PHP SDK
  text: Modern PHP SDK for Svea Checkout
  tagline: Checkout, Payment Admin, Subscriptions & Webhooks — fluent, fully tested, Laravel-ready.
  image:
    src: /logo.svg
    alt: nordkit/svea
  actions:
    - theme: brand
      text: Get Started
      link: /guide/getting-started
    - theme: alt
      text: API Reference
      link: /api/checkout
    - theme: alt
      text: View on GitHub
      link: https://github.com/nordkit/svea

features:
  - icon: 🛒
    title: Full API coverage
    details: Checkout, Payment Admin (deliver, cancel, credit), Webhook Subscriptions, and inbound webhook verification — all four Svea API surfaces in one package.
  - icon: 🎯
    title: Fluent & strict
    details: Final readonly value objects, fluent builders with when() / unless(), typed exceptions, PHPStan level 6, full strict_types — built for PHP 8.2, 8.3, 8.4.
  - icon: 🪄
    title: Two ways to build any request
    details: Pass a fully constructed value object — or a closure that mutates a pre-built builder. Loops, conditionals and composition come for free with the fluent callback style.
  - icon: 🧪
    title: First-class testing
    details: Svea::fake() mirrors Laravel's Http::fake() — assert calls, seed responses, prevent stray requests. Plus full Guzzle MockHandler examples.
  - icon: ⚡
    title: Production-ready transport
    details: HMAC-SHA512 auth, opt-in exponential backoff retries on 429/5xx, idempotency keys for safe queue retries, async task polling.
  - icon: 🪶
    title: Framework-agnostic core
    details: The core has zero Laravel dependencies. Use it in Symfony, plain PHP, or anywhere. Laravel integration (service provider, facade, Artisan commands) ships separately.
  - icon: 📖
    title: Documented end-to-end
    details: Comprehensive PHPDoc on every public class. README + API reference + this docs site. Direct links to the official Svea API documentation.
---

<style>
.VPHero .name,
.VPHero .text {
  background: -webkit-linear-gradient(120deg, #0066cc 30%, #41d1ff);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
}
</style>

