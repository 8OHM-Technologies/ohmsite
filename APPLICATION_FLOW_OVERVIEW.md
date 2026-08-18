# Application Flow Overview — 8OHM Technologies

## 1. User Registration Flow

```mermaid
flowchart TD
    A["GET /register"] -->|RegisteredUserController::create| B["Auth/Register.vue"]
    B -->|useForm POST| C["POST /register"]
    C -->|RegisteredUserController::store| D["Validate name, email, password"]
    D --> E["Create User model"]
    E --> F["Fire Registered event"]
    F --> G["Auto-login via Auth::login"]
    G --> H{"User role?"}
    H -->|Admin| I["→ /dashboard"]
    H -->|Subscribed| J["→ /pro-dashboard"]
    H -->|Regular| K["→ /profile"]
```

### Step-by-Step

| # | Action | Route | Controller / Method | Vue Component | Key Details |
|---|--------|-------|---------------------|---------------|-------------|
| 1 | Visit register page | `GET /register` | [RegisteredUserController::create](file:///home/tiaanf/Dev/ohmsite/app/Http/Controllers/Auth/RegisteredUserController.php#L22-L25) | [Auth/Register.vue](file:///home/tiaanf/Dev/ohmsite/resources/js/Pages/Auth/Register.vue) | Guarded by `guest` middleware. Renders a split-screen form with `name`, `email`, `password`, `password_confirmation` fields. |
| 2 | Submit registration | `POST /register` | [RegisteredUserController::store](file:///home/tiaanf/Dev/ohmsite/app/Http/Controllers/Auth/RegisteredUserController.php#L32-L51) | — | Validates input, creates `User` record, fires `Registered` event, auto-logs in. |
| 3 | Post-login redirect | — | [User::getRedirectUrl](file:///home/tiaanf/Dev/ohmsite/app/Models/User.php#L136-L147) | — | Admins → `/dashboard`, Subscribers → `/pro-dashboard`, Others → `/profile`. |
| 4 | Email verification prompt | `GET /verify-email` | [EmailVerificationPromptController](file:///home/tiaanf/Dev/ohmsite/app/Http/Controllers/Auth/EmailVerificationPromptController.php) | [Auth/VerifyEmail.vue](file:///home/tiaanf/Dev/ohmsite/resources/js/Pages/Auth/VerifyEmail.vue) | Shown if user hasn't verified email yet. Uses `GuestLayout`. |
| 5 | Resend verification | `POST /email/verification-notification` | [EmailVerificationNotificationController::store](file:///home/tiaanf/Dev/ohmsite/app/Http/Controllers/Auth/EmailVerificationNotificationController.php) | — | Throttled at 6 requests/minute. |
| 6 | Verify email via link | `GET /verify-email/{id}/{hash}` | [VerifyEmailController](file:///home/tiaanf/Dev/ohmsite/app/Http/Controllers/Auth/VerifyEmailController.php) | — | Signed URL. Marks email as verified, fires `Verified` event, redirects via `getRedirectUrl()`. |

> [!NOTE]
> The `Registered` event triggers Laravel's built-in email verification notification (via the `MustVerifyEmail` contract). Routes requiring `verified` middleware (checkout gating, dashboard access) will redirect unverified users to the verification prompt.

---

## 2. Payment Flows

All three product types share the **same cart → checkout → order pipeline**. The difference is in the **product slug** and the **options** passed when adding to cart.

### 2.1 Shared Purchase Pipeline

```mermaid
flowchart TD
    A["Home.vue / Services/Show.vue"] -->|"addToCart(productId, options)"| B["POST /cart"]
    B -->|CartController::store| C["CartService::addItem"]
    C -->|"resolveUnitPrice(product, options)"| D["Price calculated per product slug"]
    D --> E["CartItem created in DB"]
    E --> F["Redirect to /cart"]
    F -->|CartController::index| G["Cart/Index.vue"]
    G -->|"Optional: apply discount"| H["POST /cart/discount"]
    G --> I["Navigate to /checkout"]
    I -->|CheckoutController::index| J["Checkout/Index.vue"]
    J -->|"Submit form"| K["POST /checkout"]
    K -->|CheckoutController::store| L["Create Order + OrderItems"]
    L --> M["Clear cart"]
    M --> N["Notify admins via OrderPlaced"]
    N --> O{"User logged in?"}
    O -->|Yes| P["→ /orders"]
    O -->|No| Q["→ /home"]
```

### Step-by-Step (shared across all product types)

| # | Action | Route | Controller / Method | Vue Component | Key Details |
|---|--------|-------|---------------------|---------------|-------------|
| 1 | Browse products | `GET /` or `GET /services` | [HomeController::index](file:///home/tiaanf/Dev/ohmsite/app/Http/Controllers/HomeController.php#L42-L108) / [ShopController::index](file:///home/tiaanf/Dev/ohmsite/app/Http/Controllers/ShopController.php#L12-L80) | [Home.vue](file:///home/tiaanf/Dev/ohmsite/resources/js/Pages/Home.vue) / [Services/Index.vue](file:///home/tiaanf/Dev/ohmsite/resources/js/Pages/Services/Index.vue) | Home page loads products by slug; Services page paginates all products with filtering. |
| 2 | View product detail | `GET /services/{product}` | [ShopController::show](file:///home/tiaanf/Dev/ohmsite/app/Http/Controllers/ShopController.php#L82-L101) | [Services/Show.vue](file:///home/tiaanf/Dev/ohmsite/resources/js/Pages/Services/Show.vue) | Increments `clicks`, loads related products, shows gallery. |
| 3 | Add to cart | `POST /cart` | [CartController::store](file:///home/tiaanf/Dev/ohmsite/app/Http/Controllers/CartController.php#L39-L54) → [CartService::addItem](file:///home/tiaanf/Dev/ohmsite/app/Services/CartService.php#L34-L55) | — | Creates/finds `Cart` (by `user_id` or `session_id`). Calls `resolveUnitPrice()` for dynamic pricing. Deduplicates by `product_id` + `options`. |
| 4 | View cart | `GET /cart` | [CartController::index](file:///home/tiaanf/Dev/ohmsite/app/Http/Controllers/CartController.php#L26-L34) | [Cart/Index.vue](file:///home/tiaanf/Dev/ohmsite/resources/js/Pages/Cart/Index.vue) | Uses [useCartStore](file:///home/tiaanf/Dev/ohmsite/resources/js/Stores/useCartStore.js) (Pinia) for optimistic UI. Shows items, quantities, summary sidebar with discount support. |
| 5 | Apply discount | `POST /cart/discount` | [DiscountController::apply](file:///home/tiaanf/Dev/ohmsite/app/Http/Controllers/DiscountController.php#L23-L36) → [CartService::applyDiscount](file:///home/tiaanf/Dev/ohmsite/app/Services/CartService.php#L92-L110) | — | Validates code, checks `min_order`, attaches `Discount` to `Cart`. Supports `percentage` and `fixed` types. |
| 6 | Checkout page | `GET /checkout` | [CheckoutController::index](file:///home/tiaanf/Dev/ohmsite/app/Http/Controllers/CheckoutController.php#L30-L45) | [Checkout/Index.vue](file:///home/tiaanf/Dev/ohmsite/resources/js/Pages/Checkout/Index.vue) | Throttled. Redirects to cart if empty. Collects: email, first/last name, country, phone. Payment method hardcoded to **Paystack**. |
| 7 | Place order | `POST /checkout` | [CheckoutController::store](file:///home/tiaanf/Dev/ohmsite/app/Http/Controllers/CheckoutController.php#L50-L113) | — | Creates `Order` (status: `pending`, payment_status: `pending`), creates `OrderItem` rows, clears cart, sends [OrderPlaced](file:///home/tiaanf/Dev/ohmsite/app/Notifications/OrderPlaced.php) notification to all admin users (database channel). |
| 8 | View orders | `GET /orders` | [UserSubscriptionController::index](file:///home/tiaanf/Dev/ohmsite/app/Http/Controllers/UserSubscriptionController.php) | [Profile/Subscriptions.vue](file:///home/tiaanf/Dev/ohmsite/resources/js/Pages/Profile) | Shows user's order history with items and products. |
| 9 | Admin confirms payment | `PATCH /admin/orders/{order}/update-status` | [Admin\OrderController::updateStatus](file:///home/tiaanf/Dev/ohmsite/app/Http/Controllers/Admin/OrderController.php#L46-L56) | Admin/Orders/Index.vue | Admin manually updates `status` and `payment_status` (e.g. to `paid`). |

---

### 2.2 Product-Specific Differences

#### Once-off Datasets (`slug: once-off-dataset`)

| Aspect | Detail |
|--------|--------|
| **Entry point** | [Home.vue → handlePurchaseOnceOff](file:///home/tiaanf/Dev/ohmsite/resources/js/Pages/Home.vue#L199-L203) |
| **Options sent** | `{ dataset: 'ccma' \| 'labour-court' \| 'all' }` |
| **Pricing logic** | [CartService::resolveUnitPrice](file:///home/tiaanf/Dev/ohmsite/app/Services/CartService.php#L163-L173) — Base price per dataset; `all` = `n × base - (n-1) × R500` discount |
| **Access gate** | [User::hasOnceOffDatasetAccess](file:///home/tiaanf/Dev/ohmsite/app/Models/User.php#L102-L114) — checks for a paid order containing this product slug |
| **Middleware** | `has.dataset.access` → [DatasetAccessMiddleware](file:///home/tiaanf/Dev/ohmsite/app/Http/Middleware/DatasetAccessMiddleware.php) |
| **Protected resource** | `GET /downloads/{dataset}` → [DownloadController::download](file:///home/tiaanf/Dev/ohmsite/app/Http/Controllers/DownloadController.php) — streams CSV file |

#### Developer API / 8OHM Case Law (`slug: developer-api`)

| Aspect | Detail |
|---|---|
| **Entry point** | [PricingSection.vue → handleSubscribeDeveloper](file:///home/tiaanf/Dev/ohmsite/resources/js/Pages/Home/Partials/PricingSection.vue) |
| **Options sent** | `{ frequency: 'monthly' \| 'annually' }` |
| **Pricing logic** | [CartService::resolveUnitPrice](file:///home/tiaanf/Dev/ohmsite/app/Services/CartService.php) — Monthly = base; Annual = base × 10. Includes full API access to all case law datasets. |
| **Access gate** | [User::hasApiSubscriptionAccess](file:///home/tiaanf/Dev/ohmsite/app/Models/User.php) — checks for a paid order with this slug |
| **Middleware** | `has.api.access` → [ApiAccessMiddleware](file:///home/tiaanf/Dev/ohmsite/app/Http/Middleware/ApiAccessMiddleware.php) |
| **Protected resource** | `GET /developer/docs` → [ApiDocsController::index](file:///home/tiaanf/Dev/ohmsite/app/Http/Controllers/ApiDocsController.php) → [Developer/Docs.vue](file:///home/tiaanf/Dev/ohmsite/resources/js/Pages/Developer/Docs.vue) |

#### Pro Analytics Subscription (`slug: pro-analytics`)

| Aspect | Detail |
|--------|--------|
| **Entry point** | [Home.vue → handleSubscribeAnalytics](file:///home/tiaanf/Dev/ohmsite/resources/js/Pages/Home.vue#L214-L220) |
| **Options sent** | `{ frequency: 'monthly' \| 'annually' }` |
| **Pricing logic** | [CartService::resolveUnitPrice](file:///home/tiaanf/Dev/ohmsite/app/Services/CartService.php#L192-L197) — Monthly = base; Annual = base × 10 |
| **Access gate** | [User::isSubscribed](file:///home/tiaanf/Dev/ohmsite/app/Models/User.php#L85-L97) — checks for a paid order with this slug |
| **Middleware** | `subscribed` → [SubscribedMiddleware](file:///home/tiaanf/Dev/ohmsite/app/Http/Middleware/SubscribedMiddleware.php) |
| **Protected resource** | `GET /subscriber` → [SubscriberController::index](file:///home/tiaanf/Dev/ohmsite/app/Http/Controllers/SubscriberController.php), `GET /subscriber/analytics/saflii` → [SafliiCourts.vue](file:///home/tiaanf/Dev/ohmsite/resources/js/Pages/Subscriber/Analytics/SafliiCourts.vue), `GET /subscriber/analytics/ccma` → [CcmaAwards.vue](file:///home/tiaanf/Dev/ohmsite/resources/js/Pages/Subscriber/Analytics/CcmaAwards.vue) |

#### Legal Records Module (`/legal-records`)

| Aspect | Detail |
|--------|--------|
| **Data Source** | Exclusively queries raw `scrubbed_records` joined with `extracted_records` on the `pgsql_coeus` database connection |
| **Access Tier** | **Standard Registered Users**: Full access to explore Case Law, Journals/Gazettes, and Court Rolls with Title and Summary clearly readable. Advanced judicial insights (*Ratio Decidendi*, *Obiter Dicta*, full Bench, orders, precedents cited, source URLs) are blurred with glassmorphism overlays and upgrade CTAs to `/#pricing`.<br>**Pro Case Law / Analytics Subscribers / Admins**: Unlocked, full unredacted legal dossiers |
| **Routes** | `GET /legal-records/cases` → [Cases.vue](file:///home/tiaanf/Dev/ohmsite/resources/js/Pages/Subscriber/LegalRecords/Cases.vue)<br>`GET /legal-records/journals` → [Journals.vue](file:///home/tiaanf/Dev/ohmsite/resources/js/Pages/Subscriber/LegalRecords/Journals.vue)<br>`GET /legal-records/court-rolls` → [CourtRolls.vue](file:///home/tiaanf/Dev/ohmsite/resources/js/Pages/Subscriber/LegalRecords/CourtRolls.vue)<br>`GET /legal-records/data` & `/legal-records/record/{id}` → [LegalRecordController.php](file:///home/tiaanf/Dev/ohmsite/app/Http/Controllers/LegalRecordController.php) |

---

## 3. Key Architecture Notes

### Cart System
- **Dual-identity carts**: Cart is resolved by `user_id` (authenticated) or `session_id` (guest) via [CartService::getCart](file:///home/tiaanf/Dev/ohmsite/app/Services/CartService.php#L20-L29).
- **Dynamic pricing**: [resolveUnitPrice](file:///home/tiaanf/Dev/ohmsite/app/Services/CartService.php#L161-L207) calculates the unit price at add-to-cart time based on `product.slug` + `options`. There's no Stripe/Paystack checkout session — orders are created with `payment_status: pending`.
- **State management**: Frontend uses [useCartStore](file:///home/tiaanf/Dev/ohmsite/resources/js/Stores/useCartStore.js) (Pinia) with optimistic UI updates, synced with Inertia props.

### Payment Processing

> [!IMPORTANT]
> The checkout currently creates orders with `payment_status: 'pending'` and `payment_method: 'paystack'`, but **there is no Paystack integration yet**. The UI shows "Paystack" as the payment method, but the `POST /checkout` endpoint creates the order directly without initiating a payment gateway redirect or webhook.
>
> Orders are manually marked as `paid` by an admin via `PATCH /admin/orders/{order}/update-status`.

### Access Control
- Product access is **order-based**, not subscription-based: each middleware/gate checks if the user has a `paid` order containing the specific `product.slug`.
- Admins bypass all access checks via `isAdmin()`.
- The `verified` middleware is applied to the pro-dashboard and download routes, requiring email verification.

### Middleware Registration ([bootstrap/app.php](file:///home/tiaanf/Dev/ohmsite/bootstrap/app.php))

| Alias | Middleware | Purpose |
|-------|-----------|---------|
| `admin` | [AdminMiddleware](file:///home/tiaanf/Dev/ohmsite/app/Http/Middleware/AdminMiddleware.php) | Admin panel access |
| `subscribed` | [SubscribedMiddleware](file:///home/tiaanf/Dev/ohmsite/app/Http/Middleware/SubscribedMiddleware.php) | Pro Analytics dashboard |
| `has.dataset.access` | [DatasetAccessMiddleware](file:///home/tiaanf/Dev/ohmsite/app/Http/Middleware/DatasetAccessMiddleware.php) | Dataset downloads |
| `has.api.access` | [ApiAccessMiddleware](file:///home/tiaanf/Dev/ohmsite/app/Http/Middleware/ApiAccessMiddleware.php) | Developer API docs |

---

## 4. System Monitoring & Telegram Notifications

All system anomalies and key events are dispatched to admin Telegram chats via [TelegramAlertService](file:///home/tiaanf/Dev/ohmsite/app/Services/TelegramAlertService.php) and [TelegramSystemEventsSubscriber](file:///home/tiaanf/Dev/ohmsite/app/Listeners/TelegramSystemEventsSubscriber.php):

```mermaid
flowchart TD
    A["System Event / Exception"] --> B{"Event Type"}
    B -->|Unhandled 500 Error| C["TelegramAlertService::reportException"]
    B -->|ScheduledTaskFailed / Finished| D["TelegramSystemEventsSubscriber"]
    B -->|JobFailed| D
    B -->|Lockout| D
    C --> E{"Check Throttle Cache (15m window)"}
    E -->|Already sent| F["Increment count & suppress"]
    E -->|First occurrence| G["Build SystemErrorNotification"]
    D --> H["Build Event Notification"]
    G --> I{"Admin User in DB?"}
    H --> I
    I -->|Yes| J["Notification::send(admins, ...)"]
    I -->|No / DB Down| K["Direct Dispatch: Telegraph::chat(chatId)"]
```

