# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

**Subscriptions for WooCommerce** (v1.9.7) — a WordPress plugin by WP Swings that enables WooCommerce stores to sell subscription-based products with recurring billing. The plugin lives inside a Local by Flywheel WordPress install; the site runs at `http://localhost:10034` (Apache on port 10034, managed by Local).

The companion **Pro** plugin is at `../woocommerce-subscriptions-pro/`.

## Asset Build Commands

Two separate build systems exist for different asset types:

**Gulp** — compiles SCSS → CSS and concatenates/minifies JS for admin and public areas:
```bash
npm install
npx gulp admin-css     # compile admin/src/scss → admin/dist/css
npx gulp admin-js      # compile admin/src/js → admin/dist/js
npx gulp public-css    # compile public/src/scss → public/dist/css
npx gulp public-js     # compile public/src/js → public/dist/js
```

**Webpack / wp-scripts** — compiles the React onboarding wizard (`src/` → `build/`):
```bash
npm run build          # production build (src/ → build/)
npm run start          # watch mode
```

CSS/JS files committed to `admin/css/`, `admin/js/`, `public/css/`, `public/js/` are the compiled outputs that WordPress enqueues directly — these are the files to edit if `admin/src/` or `public/src/` don't exist.

**PHP linting** (requires `composer install` first):
```bash
composer install
vendor/bin/phpcs --standard=WordPress .
```

## Architecture

### Plugin Bootstrap Pattern

The plugin follows the [WordPress Plugin Boilerplate](https://github.com/DevinVinson/WordPress-Plugin-Boilerplate) pattern:

1. `subscriptions-for-woocommerce.php` — entry point; defines constants, calls activation/deactivation hooks, instantiates `Subscriptions_For_Woocommerce`
2. `includes/class-subscriptions-for-woocommerce.php` — orchestrator; loads all dependencies, registers hooks via the Loader, conditionally loads payment gateways
3. `includes/class-subscriptions-for-woocommerce-loader.php` — hook registry; collects `add_action`/`add_filter` calls and fires them all via `run()`

### Subscription Data Model

Subscriptions are stored as a custom WooCommerce order type (`wps_subscriptions`) via `WPS_Subscription extends WC_Order` (`includes/class-wps-subscription.php`). Key post meta fields:

| Meta key | Purpose |
|---|---|
| `wps_subscription_status` | `active`, `on-hold`, `cancelled`, `expired` |
| `wps_sfw_subscription_number` | Billing cycle length |
| `wps_sfw_subscription_interval` | Frequency: `day`, `week`, `month`, `year` |
| `wps_sfw_subscription_expiry_number` | Expiry cycle count |
| `wps_sfw_subscription_trial_end` | Unix timestamp of trial end |
| `wps_subscription_cancelled_by` | Who cancelled: `user` or `admin` |

Subscription objects are retrieved using WooCommerce's `wc_get_order()` — it automatically returns `WPS_Subscription` instances for orders of type `wps_subscriptions`.

### Settings System

Settings fields are rendered via `wps_sfw_plug_generate_html()` on the main `Subscriptions_For_Woocommerce` class. Each settings tab populates its field array by hooking into a filter:

- General tab: `wps_sfw_general_settings_array` → `wps_sfw_admin_general_settings_page()`
- Settings are saved as individual WordPress options, prefixed `wps_sfw_`
- The helper `wps_sfw_get_option_with_legacy_fallback()` handles migrations from old option names

### Payment Gateway Integration

Gateways live in `package/gateways/` (stripe, stripe-sepa, paypal, payfast, amazonpay, woocybs, wps-paypal). They are **lazy-loaded** — only included when needed (admin pages, AJAX, REST API, WP-Cron, cart/checkout/account pages) via `wps_sfw_maybe_init_payment_integration()`.

### Scheduled Renewals

`includes/class-subscriptions-for-woocommerce-scheduler.php` handles cron jobs for:
- Processing renewal orders on due subscriptions
- Marking subscriptions as expired when their end date passes

### React Onboarding Wizard

`src/App.js` is a multi-step React setup wizard (Material-UI components, built to `build/index.js`). Steps: General Settings → Create Subscription Product → Payment Gateway → Completion. State is managed via React Context (`src/store/store.js`).

### Admin UI Structure

The admin panel renders tab-based pages. Each tab is a partial in `admin/partials/`. The tab list and page structure are built inside `admin/class-subscriptions-for-woocommerce-admin.php`. Global admin object is accessed via `global $sfw_wps_sfw_obj`.

### WooCommerce Compatibility

- **HPOS (Custom Order Tables)**: declared compatible via `FeaturesUtil::declare_compatibility()`
- **WooCommerce Blocks** (cart/checkout): integration layer in `wc-block/`
- Custom emails extend `WC_Email` and are registered via the `woocommerce_email_classes` filter

## Key Files

| File | Role |
|---|---|
| `subscriptions-for-woocommerce.php` | Plugin entry point, constants |
| `includes/class-subscriptions-for-woocommerce.php` | Core orchestrator, all hook registration |
| `includes/class-wps-subscription.php` | Subscription data model (extends WC_Order) |
| `includes/class-subscriptions-for-woocommerce-scheduler.php` | Renewal/expiry cron logic |
| `includes/subscriptions-for-woocommerce-common-function.php` | Global utility functions |
| `admin/class-subscriptions-for-woocommerce-admin.php` | Admin asset enqueueing, settings tabs |
| `public/class-subscriptions-for-woocommerce-public.php` | Frontend asset enqueueing, customer views |
| `package/gateways/` | Per-gateway recurring payment integrations |
| `package/rest-api/` | REST API endpoint handler |
| `src/App.js` | React onboarding wizard root |

## Local Development Environment

The site runs via [Local by Flywheel](https://localwp.com/). Key environment details:
- **Site URL**: `http://localhost:10034`
- **MySQL socket**: `/home/shivam/.config/Local/run/wCdL6ejyZ/mysql/mysqld.sock`
- **MySQL binary**: `/home/shivam/.config/Local/lightning-services/mysql-8.0.16+6/bin/linux/bin/mysql`
- **PHP-FPM**: managed by Local, logs at `logs/php/php-fpm.log`
- **Apache logs**: `logs/apache/error.log`
- **DB credentials**: user `root`, password `root`, database `local`

Query the database:
```bash
/home/shivam/.config/Local/lightning-services/mysql-8.0.16+6/bin/linux/bin/mysql \
  -u root -proot \
  --socket=/home/shivam/.config/Local/run/wCdL6ejyZ/mysql/mysqld.sock \
  local
```
