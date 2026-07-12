# 🛒 Laravel E-Commerce Platform

A full-stack e-commerce platform built with **Laravel 12** and **Inertia.js (Vue.js)**, designed around one core principle: **zero hardcoded business logic**.

Every business operation — from pricing rules to shipping thresholds — is managed entirely through a dynamic admin dashboard. Updates to the business do not require redeployments or code changes. The application is a control layer over a clean backend/frontend architecture.

---

## 📋 Table of Contents

- [Overview](#overview)
- [Tech Stack](#tech-stack)
- [Architecture](#architecture)
- [Admin Dashboard](#admin-dashboard)
- [Frontend & CRO](#frontend--conversion-optimization)
- [Backend Design](#backend-design)
- [Data Flow: From Cart to Order](#data-flow-from-cart-to-order)
- [Authentication & Authorization](#authentication--authorization)
- [Testing](#testing)
- [Installation](#installation)

---

## Overview

This platform is not just an e-commerce store — it is a system designed as a real product, where business logic is decoupled from code and fully controlled through an administrative layer.

- Guest and authenticated shopping with seamless cart merging
- Dynamic discount and discount system
- Atomic checkout flow with full data integrity
- VIP customer segmentation based on lifetime value (LTV)
- Real-time inventory and stock management
- Analytics with behavioral insights and sales impact tracking

---

## Tech Stack

| Layer | Technology | Purpose |
|---|---|---|
| Backend | Laravel 12 | Core framework, routing, ORM |
| Frontend | Inertia.js + Vue.js | SPA-like experience without API overhead |
| Routing (Frontend) | Ziggy | Shares Laravel named routes with Vue |
| Auth | Laravel Breeze | Authentication scaffolding |
| Social Login | Laravel Socialite | OAuth login (Google, etc.) |
| Database | MySQL | Production database |
| Testing DB | SQLite (in-memory) | Fast, isolated test runs |
| CI/CD | GitHub Actions | Automated testing on every push |

UI/UX is fully custom-designed from scratch — no templates used.

---

## Architecture

```
app/
├── Http/
│   ├── Controllers/
│   │   ├── Admin/
│   │   │   ├── DashboardController.php
│   │   │   ├── ProductController.php
│   │   │   ├── OrderController.php
│   │   │   ├── CustomerController.php
│   │   │   ├── AnalyticsController.php
│   │   │   └── InventoryController.php
│   │   ├── CartController.php
│   │   ├── CheckoutController.php
│   │   ├── ShopController.php
│   │   └── HomeController.php
│   └── Middleware/
│       └── AdminMiddleware.php
├── Models/
│   ├── User.php
│   ├── Product.php
│   ├── Order.php / OrderItem.php
│   ├── Cart.php / CartItem.php
│   ├── Discount.php
│   ├── Category.php / Brand.php
│   └── HomeSetting.php
└── Services/
    ├── CartService.php         # All cart calculation logic
    ├── CustomerService.php     # VIP tracking & customer metrics
    └── AnalyticsService.php    # Behavioral insights & sales analytics
```

Controllers are kept thin — they receive the request, delegate to Services, and return the response. All business logic lives in `Services/`, making it fully testable in isolation without HTTP overhead.

---

## Admin Dashboard

Protected by `['auth', 'verified', 'admin']` middleware. Every business rule is configurable here — no code changes needed.

| Module | Description |
|---|---|
| 📊 **Overview** | Real-time metrics: sales, orders, growth rate, returning customer rate |
| 🏠 **Home Editor** | Dynamic control of homepage content — hero section, banners, stats |
| 🛍️ **Products & Drops** | Full product CRUD with pricing, discounts, and stock logic |
| 📦 **Inventory** | Real-time stock management with total warehouse value calculation |
| 📈 **Analytics** | Behavioral insights and impact analysis of changes on sales performance |
| 🛒 **Orders** | Full lifecycle order management and status tracking |
| 👥 **Customers & VIP** | Basic CRM with automatic segmentation based on customer LTV |
| ⚙️ **Settings** | Global configuration: taxes, currency, shipping thresholds, time zone |

---

## Frontend & Conversion Optimization

The frontend is designed with a strong focus on **Conversion Rate Optimization (CRO)**:

- **Smart filtering and sorting** in the shop for faster product discovery
- **Atomic checkout flow** using database transactions — order creation, stock updates, cart cleanup, and admin notifications all happen atomically

### Cart System
- Works for both **guests and logged-in users**
- Guests use a `session_id` to persist the cart
- On login, the guest cart automatically **merges** with the user's saved cart — no lost carts, better conversion

### Discount System
- Two types: **percentage** (e.g. 20% off) and **fixed value** (e.g. 10R off)
- Validated against: expiry date, usage limit, minimum order value
- Applied at cart level and recalculated on every change

---

## Backend Design

The backend is built around consistency, scalability, and data integrity.

### 🔒 Concurrency Control
`lockForUpdate` is used during checkout to prevent race conditions and overselling — two users cannot purchase the last unit simultaneously.

### 🗄️ Database Schema & Indexing
The schema is optimized for read-heavy shop operations. Key relationships are indexed to keep query performance predictable at scale.

### 🧠 Separated Services
Business logic is separated from HTTP concerns:
- `CartService` — single source of truth for all cart math (subtotal, discounts, shipping)
- `CustomerService` — VIP recalculation based on cumulative spend
- `AnalyticsService` — revenue trends, growth rates, behavioral insights

### 🚫 No Hardcoded Business Rules
Thresholds like free shipping limits, VIP qualification amounts, tax rates, and currency are stored in the database and managed through the Settings panel. Changing them requires no code modification.

---

## Data Flow: From Cart to Order

### Step 1 — Adding to Cart
```
User clicks "Add to Cart"
    → CartController@store
    → CartService::addItem()
    → Creates/updates CartItem linked to Cart (by user_id or session_id)
```

### Step 2 — Cart Calculation
Every time the cart is viewed or modified, `CartService` recalculates:

```
subtotal = sum of (price × quantity) for all items

discount = discount applied?
    → percentage: subtotal × (discount% / 100)
    → fixed:      flat amount off

total = subtotal - discount
```

### Step 3 — Checkout
The entire checkout runs inside a **database transaction**:

```php
DB::transaction(function () {
    1. lockForUpdate() — prevent concurrent stock conflicts
    2. Validate stock availability for all items
    3. Create Order record (address, totals, payment status)
    4. Create OrderItem per CartItem (snapshot of product + price at purchase time)
    5. Decrement stock per product
    6. Clear the cart
    7. Dispatch admin notification
});
```

If any step fails, the entire operation rolls back. No partial orders. No phantom stock deductions.

### Step 4 — Post-Order
```
Order confirmed
    → Admin receives notification
    → CustomerService updates total spent
    → VIP status recalculated based on LTV threshold (configurable in Settings)
```

---

## Authentication & Authorization

### Role System
Roles are stored as a column on the `users` table (`role: 'admin' | 'user'`). Simple and dependency-free for a two-role system.

### Middleware Stack for Admin Routes
```php
Route::middleware(['auth', 'verified', 'admin'])->prefix('admin')
```

1. `auth` — must be logged in
2. `verified` — email must be verified
3. `admin` — `AdminMiddleware` checks `user->role === 'admin'`

### Guest Access
`/cart`, `/shop`, and `/checkout` are intentionally public. Authentication is only required at order placement to attach the order to an account.

---

## Testing

### Unit Tests (`tests/Unit/`)
| Test | What it verifies |
|---|---|
| `CartServiceTest` | Subtotal calculation, discount application, shipping threshold logic |

Tested directly against the service — no HTTP, no database, no framework overhead.

### Feature Tests (`tests/Feature/`)
| Test | What it verifies |
|---|---|
| `AdminAccessTest` | Guests and regular users are blocked; admins can access the dashboard |
| `CartTest` | Adding and removing products via HTTP |
| `CheckoutTest` | Full checkout flow, stock decrement, order creation |
| `Auth/*` | Login, registration, password reset, email verification |
| `ProfileTest` | Profile update and account deletion |

### CI/CD
GitHub Actions runs the full test suite on every push to `main` and `develop`:

```yaml
- PHP 8.2 with SQLite in-memory database
- Composer dependencies installed
- App key generated from .env.example
- php artisan test
```

`withoutVite()` is called in `TestCase::setUp()` to skip Vite manifest resolution — backend tests run without requiring a frontend build.

---

## Installation

```bash
# 1. Clone the repository
git clone https://github.com/andmurturi0/laravel-ecommerce.git
cd laravel-ecommerce

# 2. Install PHP dependencies
composer install

# 3. Install Node dependencies and build assets
npm install && npm run build

# 4. Configure environment
cp .env.example .env
php artisan key:generate

# 5. Set up the database
php artisan migrate --seed

# 6. Start the development server
php artisan serve
```

### Environment Variables
```env
DB_CONNECTION=mysql
DB_DATABASE=laravel_ecommerce
DB_USERNAME=root
DB_PASSWORD=

MAIL_MAILER=smtp
# Required for email verification and password reset
```

---

## Design Decisions

| Decision | Reason |
|---|---|
| Zero hardcoded business logic | Business rules change; code should not need to change with them |
| `lockForUpdate` in checkout | Prevents overselling under concurrent load |
| `CartService` as a class | Single source of truth for cart math — reused across controllers and tests |
| `DB::transaction` in checkout | Atomicity — no partial state if any step fails |
| Dynamic settings via DB | Shipping thresholds, VIP levels, taxes — all configurable without redeployment |
| Role column vs. Spatie | Simpler for two roles; easy to extend later if needed |
| Inertia.js over REST API | Server-side routing and auth; no separate API authentication layer |
| SQLite for tests | No MySQL required in CI; in-memory teardown keeps tests fast |
| `withoutVite()` in tests | Decouples backend tests from frontend build pipeline |
