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

- **Tier 1: FREE Case Law**: Free access to South African case law records with standard public metadata (case number, judgment date, hearing date, adjudication duration, court location, and direct SAFLII link) across table and docket modal views.
- **Tier 2: Pro Case Law**: API access to all case law records, advanced dataset features (ratio decidendi, scrubbed records, acts cited), and live continuous data feed.
- **Tier 3: Pro Legal Analytics**: Complete no-code analytics platform, higher API limits (3,000 req/mo), interactive jurisprudence and citation network dashboards, and automated exports.
- **Tier 4: Once-off Datasets**: POPIA-compliant raw case law datasets available for bulk download in CSV/JSON formats for AI training and offline research.
- **Managed Data Infrastructure**: Custom web scraping, ETL data engineering, and private LLM deployments.

---

## Tech Stack

- **Backend**: Laravel 12 (Core, Routing, Eloquent ORM)
- **Frontend**: Inertia.js (Vue 3 client-side SPA state management)
- **Routing**: Ziggy (Laravel routes in Vue)
- **Auth**: Laravel Breeze (Breeze scaffolding) + Laravel Socialite (OAuth)
- **Database**: PostgreSQL (`laravel` & `pgsql_coeus` on Production with GIN Trigram (`pg_trgm`) & functional date indexing) & SQLite (In-Memory for Tests)
- **Caching**: Redis (Aggregated analytics & high-throughput dataset caching)
- **Web Server & Runtime**: Nginx with Gzip compression, FastCGI buffer tuning & optimized PHP-FPM process pool with production config/route preloading
- **CI/CD**: GitHub Actions

---

## Architecture & Core Concepts

Business logic is completely isolated within **Services** to keep Controllers thin and easily testable:

- `CartService`: Manages cart arithmetic, pricing adjustments, and discounts.
- `CustomerService`: Tracks lifetime value (LTV) and updates VIP customer tiers.
- `AnalyticsService`: Aggregates usage patterns, revenue trends, and growth metrics.
- `ComplianceAnalyticsClient`: Interfaces with the FastAPI + DuckDB analytical microservice and `pgsql_coeus` database for legal precedent research, statutory cross-referencing, and entity compliance profiles.

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
- **⚖️ Legal Records Human Review Queue**: Administrative queue and 3-state data refinement console (`extracted_record`, `parsed_record`, and `scrubbed_record`) to inspect and refine OCR/LLM extracted metadata, parties, judges, and rulings with single and batch review triggers.
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
- **SAFLII Courts Jurisprudence Intelligence**: Superior court analytics (Constitutional Court & Competition Appeal Court), precedent citation networks, citation treatment breakdown (Applied, Referred, Distinguished), judicial bench analysis, legal subject typology distribution, and comprehensive case dossier viewer with *Ratio Decidendi* and *Obiter Dicta* extraction.
- **Compliance & Enforcement Intelligence** (`/subscriber/analytics/compliance`): Macro-level monitoring of administrative sanctions, cumulative financial penalties in ZAR, POPIA infringement notices, Prudential Authority standards, and top fine leaderboards powered by the vectorized DuckDB + FastAPI microservice.
- **Ratio Decidendi Explorer**: Searchable and filterable binding legal principles, turnaround velocity badges, judicial panel sizes, and one-click case intelligence dossiers.
- **Metrics Transparency**: Integrated metrics logic modal explaining algorithmic formulations for litigation turnaround, citation classification, and LLM extraction pipelines.
- Instant exports of sanitized datasets.

### 3. Open Access Legal Records & Precedents
- **Case Law & Judgments** (`/legal-records/cases`): Superior court case law (Constitutional Court, Supreme Court of Appeal, High Courts) and CCMA arbitration awards with procedural summaries, holdings, dismissal reasons, full judgment viewer, high-performance multi-token keyword/phrase search with PostgreSQL GIN trigram acceleration and contextual keyword match highlighting, explicit AI-assisted processing disclaimers, and a one-click user error reporting mechanism that routes flagged records directly into the Admin Human Review queue (instantly quarantining and hiding flagged records from appearing in the frontend Case Law module until reviewed and resolved by an administrator).
- **Specialized Regulatory, Tribunal & Ombud Dossier Views**: Custom dossier layouts (`ComplianceRecordModal`, `RegulatoryRecordView`, `TribunalRecordView`, `OmbudRecordView`) visualizing administrative penalties in ZAR, formal debarment orders, statutory contraventions (§), insurer repudiation grounds, ombud compensation awards, tribunal remittal directives, and direct statutory cross-referencing in Compliance Engine and Compliance Analytics.
- **Law Journals & Reviews** (`/legal-records/journals`): Peer-reviewed South African academic law journals (PER, PELJ, De Rebus, AHRLJ, Stell LR, SACJ, etc.) featuring dedicated tabular grid and dossier views, author indexing, volume/issue citations, abstract summaries, analytical reading canvas, and direct original publication PDF downloads via [JournalRecordView](file:///home/tiaanf/Dev/ohmsite/resources/js/Pages/Subscriber/LegalRecords/Components/JournalRecordView.vue).
- **Court Rolls & Schedules** (`/legal-records/court-rolls`): Superior Court and High Court hearing schedules, daily motion court cause lists, hearing rosters, presiding judge and courtroom allocation, structured enrolled matters tables with in-roll keyword filtering, raw schedule view, and original registrar PDF downloads via [CourtRollRecordView](file:///home/tiaanf/Dev/ohmsite/resources/js/Pages/Subscriber/LegalRecords/Components/CourtRollRecordView.vue).
- **Government & Provincial Gazettes** (`/legal-records/gazettes`): Official National Government Gazettes and Provincial Gazettes (Gauteng, Western Cape, KwaZulu-Natal, Eastern Cape, etc.), statutory notices, proclamations, and regulations featuring dedicated jurisdiction filters, gazette number & volume tracking, notice readers, and authentic PDF downloads via [GazetteRecordView](file:///home/tiaanf/Dev/ohmsite/resources/js/Pages/Subscriber/LegalRecords/Components/GazetteRecordView.vue).

### 4. Interactive Demo Analytics (`/demo`)
- **Public Legal Analytics Showcase**: Interactive, full-featured demo highlighting SAFLII Superior Courts jurisprudence intelligence, precedent citation networks, judicial bench composition, and extracted *Ratio Decidendi* case dossiers without requiring an upfront subscription.

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

