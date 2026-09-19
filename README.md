# Chickventory

Chickventory is an inventory management system for Chicky Fryday. It tracks products, stock levels, suppliers, inventory movements, users, reports, and system settings. The application is built with Laravel and uses Supabase PostgreSQL as its database.

## Product Purpose

The app gives administrators and inventory staff one place to:

- Monitor current inventory and low-stock products.
- Record incoming stock from suppliers.
- Review stock-in and stock-out transactions.
- See which products belong to each supplier.
- Review inventory activity and monthly transaction counts.
- Manage system users and roles.
- Store inventory system settings.
- Represent automatic stock deductions from an external ordering system.

## Current Features

### Dashboard

- Shows active product count, total stock, low-stock count, and active supplier count.
- Lists recent inventory transactions.
- Lists products below their minimum stock level.
- Shows the number of ordering-system transactions processed today.
- Explains the automatic stock-out flow from the ordering system to inventory.

### Products / Inventory

- Lists active products with product code, category, supplier, stock, minimum stock, unit, and status.
- Supports search by product name or product code.
- Supports filtering by category.
- Calculates low-stock status from `current_stock < minimum_stock`.

### Stock In

- Loads active products and suppliers from the database.
- Accepts product, supplier, quantity, date received, and remarks fields.
- Validates the submitted stock-in data.
- Increases the selected product's current stock.
- Creates a corresponding inventory transaction.
- Uses a database transaction so the stock update and transaction record succeed or fail together.
- Displays recent stock-in records.

### Inventory Transactions

- Lists the latest inventory movements.
- Shows transaction code, date, reference, product, type, quantity, source, and status.
- Supports search by transaction code or reference.
- Supports filtering by `stock_in` or `stock_out`.
- Represents stock-outs received from the external Ordering System.

### Suppliers

- Lists active suppliers and their contact information.
- Shows the number of products associated with each supplier.
- Supports supplier-name search.

### Reports

- Shows active product count, current-month stock-in count, current-month stock-out count, and active supplier count.
- Shows recent inventory activity.
- Links to the complete inventory transaction view.

### User Management

- Lists users by name and email.
- Supports user search.
- Stores user role and active/inactive status.

### Settings

- Reads system settings from the database.
- Supports the system name, currency, description, low-stock threshold, and default unit settings as stored configuration values.

### Purchase Orders

- Creates supplier purchase orders with an automatically generated unique PO number.
- Restricts PO creation to suppliers marked `requires_po = true`.
- Stores PO line items with raw material, ordered quantity, and unit price.
- Filters POs by status: draft, approved, partially fulfilled, fulfilled, or cancelled.
- Supports approving draft POs and opening approved POs for receiving.

### Purchases / Receiving

- Records direct purchases for suppliers that do not require a PO.
- Receives approved purchase orders through a PO-linked purchase record.
- Supports multiple material line items per purchase.
- Updates raw-material stock and creates `purchase_in` stock movements.
- Updates supplier-material last unit cost from each received purchase.
- Marks a PO as `fulfilled` or `partially_fulfilled` based on received quantities.

### Raw Materials and Recipes

- Stores raw materials separately from finished products.
- Tracks raw-material stock, minimum stock, unit, and active status.
- Lets users attach raw materials to products with a required quantity per product unit.
- Prevents sales from being recorded for products without a recipe.

### Sales / Order Intake

- Provides a manual sale-entry form for testing external sales integrations.
- Accepts an external order ID, product, quantity, order date, and source system.
- Rejects duplicate external order IDs.
- Deducts recipe materials from raw-material stock inside a database transaction.
- Creates usage stock movements with an `order_item` reference.
- Rejects sales when any required raw material would fall below zero stock.
- Supports JSON responses for external webhook-style requests.

### Expenses

- Records expense description, amount, date, and optional purchase association.
- Lists expenses with their transfer-to-sales status.
- Toggles `transferred_to_sales` for COGS/sales reconciliation.

### Supplier-Material Pricing

- Maintains the supplier-to-raw-material relationship in `supplier_material`.
- Stores the last known unit cost for each supplier/material pair.
- Automatically updates the last unit cost when a purchase item is received.

## Routes

| URL | Name | Controller | Purpose |
| --- | --- | --- | --- |
| `/` | - | Redirect | Redirects to the dashboard. |
| `/dashboard` | `dashboard` | `DashboardController@index` | Inventory overview. |
| `/products` | `products` | `ProductController@index` | Product and stock listing. |
| `/products` | `products.store` | `ProductController@store` | Creates a finished product. |
| `/ingredients` | `ingredients.store` | `IngredientController@store` | Creates an ingredient / raw material. |
| `/stock-in` | `stock-in` | `StockInController@index` | Stock-in form and recent entries. |
| `/stock-in` | `stock-in.store` | `StockInController@store` | Saves a stock-in transaction. |
| `/inventory-transactions` | `inventory-transactions` | `InventoryTransactionController@index` | Transaction history. |
| `/suppliers` | `suppliers` | `SupplierController@index` | Supplier listing. |
| `/reports` | `reports` | `ReportController@index` | Inventory summaries and activity. |
| `/users` | `users` | `UserController@index` | User listing. |
| `/settings` | `settings` | `SettingController@index` | System settings. |
| `/purchase-orders` | `purchase-orders` | `PurchaseOrderController@index` | PO listing, filtering, and creation. |
| `/purchase-orders` | `purchase-orders.store` | `PurchaseOrderController@store` | Creates a purchase order. |
| `/purchase-orders/{purchaseOrder}/approve` | `purchase-orders.approve` | `PurchaseOrderController@approve` | Approves a draft PO. |
| `/purchases` | `purchases` | `PurchaseController@index` | Purchase receiving and purchase history. |
| `/purchases` | `purchases.store` | `PurchaseController@store` | Receives direct or PO-based materials. |
| `/sales` | `sales` | `OrderItemController@index` | Manual sales/order intake and history. |
| `/sales` | `sales.store` | `OrderItemController@store` | Records an order and deducts recipe stock. |
| `/expenses` | `expenses` | `ExpenseController@index` | Expense register and transfer status. |
| `/expenses` | `expenses.store` | `ExpenseController@store` | Records an expense. |
| `/expenses/{expense}/transfer` | `expenses.transfer` | `ExpenseController@transfer` | Toggles expense transfer-to-sales status. |
| `/products/{product}/recipe` | `recipes.store` | `RecipeController@store` | Adds or updates a product recipe material. |
| `/products/{product}/recipe/{material}` | `recipes.destroy` | `RecipeController@destroy` | Removes a recipe material. |

## Data Model

The inventory schema is defined in `database/migrations/2026_09_14_000003_create_inventory_tables.php`.

- `users`: Laravel users plus `role` and `status`.
- `suppliers`: supplier contact details and status.
- `products`: product code, category, supplier, stock quantities, unit, and status.
- `inventory_transactions`: stock movement history, source, reference, quantity, and timestamp.
- `system_settings`: key/value configuration records.
- `raw_materials`: ingredient stock and minimum stock levels.
- `purchase_orders`: intended supplier purchases and approval/fulfillment status.
- `purchase_order_items`: materials, quantities, and prices planned on each PO.
- `purchases`: actual receiving events, optionally linked to a PO.
- `purchase_items`: materials received, unit costs, and computed subtotals.
- `supplier_material`: supplier/material pricing pivot with `last_unit_cost`.
- `product_recipes`: finished-product recipes and required material quantities.
- `order_items`: external or manual sales orders with duplicate-safe external IDs.
- `stock_movements`: signed raw-material movements with source references.
- `expenses`: costs optionally linked to purchases and marked as transferred to sales.

Relationships:

- A supplier has many products.
- A product belongs to an optional supplier.
- A product has many inventory transactions.
- An inventory transaction belongs to a product.
- A supplier can require a purchase order before receiving materials.
- A purchase order belongs to a supplier and has many purchase-order items.
- A purchase may optionally belong to a purchase order and has many purchase items.
- Suppliers and raw materials are many-to-many through `supplier_material`.
- Products and raw materials are many-to-many through `product_recipes`.
- An order item belongs to a finished product and triggers recipe deduction.
- A stock movement references its source through `reference_type` and `reference_id`.
- An expense may optionally belong to a purchase.

## Technology Stack

- PHP 8.2+
```dotenv
APP_NAME=Chickventory
APP_URL=http://127.0.0.1:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=your_database_name
DB_USERNAME=root
DB_PASSWORD=
```

Run migrations:

```bash
php artisan migrate --force
```

Load the development administrator and sample inventory records:

```bash
php artisan db:seed
```

Build frontend assets:

```bash
npm run build
```
Start the application:

```bash
php artisan serve
```

Open `http://127.0.0.1:8000`.

The development seeder creates an administrator with:

- Email: `admin@example.com`
- Password: `password`

Change or remove this development credential before deploying the app.

## Frontend Structure

- `resources/views/layouts/app.blade.php`: shared application layout.
- `resources/views/partials/sidebar.blade.php`: shared navigation and logo.
- `resources/views/partials/header.blade.php`: shared top header.
- `resources/views/partials/footer.blade.php`: shared footer.
- `resources/views/*.blade.php`: page-specific content.
- `resources/css/app.css`: shared responsive inventory UI styles.
- `resources/js/app.js`: Vite JavaScript entry point.
- `public/images/ChickyLogo.jpg`: application logo.

## Backend Structure

- `routes/web.php`: web routes.
- `app/Http/Controllers`: page controllers and procurement, recipe, sales, and expense workflows.
- `app/Models`: Eloquent models and relationships for inventory and procurement.
- `app/Services/RecipeStockService.php`: transactional recipe-based raw-material deduction.
- `database/migrations`: database schema.
- `database/seeders`: development administrator, products, suppliers, raw materials, transactions, and settings.

## Guidance for Future Changes

- Use Eloquent models and relationships for database access.
- Keep database queries in controllers or dedicated services, not in Blade templates.
- Preserve the shared layout and partials for all inventory pages.
- Use database-agnostic Eloquent queries so the application remains compatible with MySQL.
- Wrap related inventory mutations in `DB::transaction()`.
- Keep stock quantities and transaction history consistent: every stock adjustment should create a matching transaction record.
- Add migrations for schema changes; do not edit an existing migration after it has been applied to a database.
- Add validation for every write endpoint.
- Keep `.env` credentials private and use `.env.example` for non-secret configuration documentation.

## Current Limitations

Product and supplier creation, stock-in, purchase-order approval, purchase receiving, recipe assignment, manual sales intake, expense recording, and expense transfer toggling are implemented.

Product editing/deletion, supplier editing/deletion, raw-material management UI, supplier-material comparison UI, user administration actions, settings updates, report downloads, authentication/authorization, and production webhook authentication still need dedicated production-ready workflows.

The app uses a shared Eloquent database connection configured through `.env`. MySQL via phpMyAdmin is the supported local database setup.

## Validation Commands

```bash
php artisan route:list
php artisan view:cache
php artisan test
npm run build
```

