# 🛒 Laravel E-Commerce for 8OHM Technologies

Built with **Laravel 12** and **Inertia.js (Vue3)**, features a custom admin dashboard with various modules tailored towards a SaaS business.

---

## 📋 Table of Contents

- [Overview](#overview)
- [Tech Stack](#tech-stack)
- [Architecture & Core Concepts](#architecture--core-concepts)
- [Admin Dashboard](#admin-dashboard)
- [SaaS Features & User Portals](#saas-features--user-portals)
- [Authentication & Middleware](#authentication--middleware)
- [Testing](#testing)
- [Installation](#installation)

---

## Overview

This platform is a data-centric SaaS and digital licensing platform tailored for legal and analytical datasets. Decoupled from hardcoded business logic, the entire product ecosystem, subscription tiers, API access rights, and telemetry are managed dynamically.

### Core Offerings
- **Once-off Datasets**: POPIA-compliant legal datasets (e.g. CCMA arbitration awards) available for download in CSV/JSON formats.
- **Developer API**: Subscription-based developer portal offering REST API access with managed rate limits.
- **Pro Analytics**: No-code interactive analytics dashboard showing trends and insights.
- **Managed Data Pipelines**: Custom web scraping, ETL, and private LLM deployments.

---

## Tech Stack

- **Backend**: Laravel 12 (Core, Routing, Eloquent ORM)
- **Frontend**: Inertia.js (Vue 3 client-side SPA state management)
- **Routing**: Ziggy (Laravel routes in Vue)
- **Auth**: Laravel Breeze (Breeze scaffolding) + Laravel Socialite (OAuth)
- **Database**: MySQL (Production) & SQLite (In-Memory for Tests)
- **CI/CD**: GitHub Actions

---

## Architecture & Core Concepts

Business logic is completely isolated within **Services** to keep Controllers thin and easily testable:

- `CartService`: Manages cart arithmetic, pricing adjustments, and discounts.
- `CustomerService`: Tracks lifetime value (LTV) and updates VIP customer tiers.
- `AnalyticsService`: Aggregates usage patterns, revenue trends, and growth metrics.

### Atomic Checkout & Provisioning
When a purchase or subscription is made, a database transaction handles the provisioning atomically:
1. Validates and locks checkout status (`lockForUpdate`).
2. Creates the Order and OrderItem records.
3. Provisions access rights (API subscription flags, download tokens, or subscriber status).
4. Clears active cart.

---

## Admin Dashboard

Protected by `admin` middleware, the dashboard acts as the business control center:

- **📊 Overview**: Real-time sales, subscriptions, MRR, and active user metrics.
- **🛍️ Services & Products**: CRUD operations for data packages, pricing, and license terms.
- **🔑 Licenses**: Manage API keys, active client tokens, and custom API limit overrides.
- **👥 Customers & VIP**: CRM to manage client accounts, toggle VIP access, and view LTV.
- **📈 Analytics**: Visual insights on system usage and download frequencies.
- **⚙️ Settings**: Define tax rates, threshold values for VIP triggers, and payment gateways.

---

## SaaS Features & User Portals

### 1. Developer Portal (`has.api.access`)
- **API Key Management**: Create, view, and revoke personal API keys.
- **Developer Documentation**: Interactive API documentation.
- **Rate Limiting**: Limits enforced dynamically based on active tier (1000/mo for API, 3000/mo for Pro).

### 2. Pro Analytics Dashboard (`subscribed`)
- **CCMA Awards Analytics**: Labor arbitration trends, procedural velocity (hearing duration, award delay, ingestion latency), regional exposure, and employer risk profiling.
- **SAFLII Courts Jurisprudence Intelligence**: Superior court analytics (Constitutional Court & Competition Appeal Court), precedent citation networks, citation treatment breakdown (Applied, Referred, Distinguished), judicial bench analysis, and comprehensive case dossier viewer with *Ratio Decidendi* and *Obiter Dicta* extraction.
- Instant exports of sanitized datasets.

---

## 📡 Monitoring & Telegram Notifications

The application integrates with Telegram via `defstudio/telegraph` to alert admins about critical platform events and system anomalies:

- **🚨 Backend Exceptions & Server Errors**: Automatically captures unhandled exceptions with HTTP/CLI context, user ID, IP, and location. Throttled at **1 alert per 15 minutes per unique exception** to prevent channel flood.
- **⏱️ Scheduled Task Tracking**: Instant notifications when scheduled tasks fail (`ScheduledTaskFailed`), with optional completion summaries (`ScheduledTaskFinished`).
- **💥 Queue Job Failures**: Real-time alerts when background queue jobs fail (`JobFailed`) with connection, queue, and error details.
- **🛡️ Security & Auth Lockouts**: Alerts triggered on brute-force authentication lockouts (`Lockout`) with IP and target account details.
- **💼 Business Events**: Instant alerts for `OrderPlaced`, `PaymentCompleted`, `PaymentFailedOrError`, `NewUserRegistered`, and `WebsiteEnquiryReceived`.
- **🛠️ Manual Dispatch & CLI**: Send manual or broadcast messages via `vendor/bin/sail artisan telegram:send "Your message"`.

---

## Authentication & Middleware

Access levels are enforced via specialized middlewares:
- `AdminMiddleware` (`admin`): Restricts access to administrative endpoints.
- `SubscribedMiddleware` (`subscribed`): Restricts access to the Pro Analytics dashboard.
- `DatasetAccessMiddleware` (`has.dataset.access`): Enforces download limits on once-off datasets.
- `ApiAccessMiddleware` (`has.api.access`): Validates developer portal access.
- `VerifyDeveloperApiKey`: Intercepts API requests and validates credentials against rate limits.

---

## Testing

Comprehensive backend and integration tests are run via PHPUnit:

```bash
# Run all tests
vendor/bin/sail artisan test --compact

# Filter specific tests
vendor/bin/sail artisan test --compact --filter=CheckoutTest
```

*Note: Frontend asset compilation is skipped in PHPUnit tests via `withoutVite()` in setup.*

---

## Installation

```bash
# 1. Clone & enter project
git clone https://github.com/8OHM-Technologies/ohmsite.git
cd ohmsite

# 2. Run Sail containers
./vendor/bin/sail up -d

# 3. Install packages & build frontend
./vendor/bin/sail composer install
./vendor/bin/sail npm install
./vendor/bin/sail npm run build

# 4. Environment & Database Setup
cp .env.example .env
./vendor/bin/sail artisan key:generate
./vendor/bin/sail artisan migrate --seed
```

