# Chickventory

Chickventory is an inventory management system for Chicky Fryday. It tracks products, stock levels, suppliers, inventory movements, users, reports, purchase orders, expenses, and system settings[cite: 3]. The application is built with Laravel and runs on a MySQL database (`chicky_db`)[cite: 3].

## Product Purpose

The app gives administrators and inventory staff one place to:

- Monitor current inventory and low-stock products[cite: 3].
- Record incoming stock from suppliers and manage purchase orders[cite: 3].
- Review stock-in and stock-out transactions[cite: 3].
- See which products belong to each supplier[cite: 3].
- Review inventory activity and transaction metrics[cite: 3].
- Manage system users and role permissions (Admin restricted)[cite: 3].
- Store inventory system settings[cite: 3].
- Process automatic stock deductions synced from an external Ordering System[cite: 3].
- Provide a secure, Admin-only Manual / Backup Sales tool for offline sales, catering, and inventory reconciliations.

## Current Features

### Dashboard

- Shows active product count, total raw material stock, low-stock count, and active supplier count[cite: 3].
- Lists recent inventory transactions with safe object and date parsing[cite: 3].
- Displays real-time low stock alerts for raw materials[cite: 3].
- Shows ordering system integration metrics and automated stock-out workflows[cite: 3].

### Products / Inventory

- Lists active products with product code, category, supplier, stock, minimum stock, unit, and status[cite: 3].
- Synchronized primary key mapping (`product_id`) for remote schema compatibility.
- Renders product images via dedicated route handling.
- Calculates low-stock status from `current_stock < minimum_stock`[cite: 3].

### Stock In

- Loads active products and suppliers from the database[cite: 3].
- Accepts product, supplier, quantity, date received, and remarks fields[cite: 3].
- Increases product stock and logs inventory transactions inside atomic `DB::transaction()` blocks[cite: 3].

### Inventory Transactions

- Tracks complete inventory movements (stock-ins, manual stock-outs, and order stock-outs)[cite: 3].
- Displays transaction codes, reference numbers, product names, quantities, sources, and statuses[cite: 3].
- Integrates automated `ORD-TXN-` stock-out events sent from the Ordering System[cite: 3].

### Manual / Backup Sales Entry (Admin Restricted)

- Restricted exclusively to system **Administrators** for security and auditing.
- Serves as a fallback tool for POS downtime, bulk catering events, or manual stock reconciliations.
- Auto-generates reference IDs, deducts recipe raw materials, and logs sales with designated sources (`Manual Override`, `Catering / Event`, `POS Offline Backup`).

### Expenses & Sales Integration

- Records expense descriptions, amounts, dates, and auto-generated `external_expense_id` codes (e.g., `EXP-20260930-XXXX`)[cite: 3].
- Toggles `transferred_to_sales` status for COGS and sales reconciliation[cite: 3].
- Computes real-time Net Sales (`Total Sales - Transferred Expenses`).

### User Management & System Settings (Admin Restricted)

- Role-based sidebar navigation ensuring **Manual / Backup Sale**, **Users**, and **Settings** links are visible and accessible only to Admin users[cite: 3].
- Centralized system configuration for currency, system name, and threshold defaults[cite: 3].

---

## Routes Summary

| URL | Name | Access Level | Purpose |
| --- | --- | --- | --- |
| `/` | - | Public | Redirects to dashboard[cite: 3] |
| `/dashboard` | `dashboard` | `auth` | Main system overview & metrics[cite: 3] |
| `/products` | `products` | `auth` | Product inventory listing[cite: 3] |
| `/stock-in` | `stock-in` | `auth` | Manual stock receiving form[cite: 3] |
| `/inventory-transactions` | `inventory-transactions` | `auth` | Inventory transaction logs[cite: 3] |
| `/suppliers` | `suppliers` | `auth` | Supplier management directory[cite: 3] |
| `/purchase-orders` | `purchase-orders` | `auth` | Purchase order management[cite: 3] |
| `/purchases` | `purchases` | `auth` | Receiving direct or PO items[cite: 3] |
| `/reports` | `reports` | `auth` | Activity summaries & reports[cite: 3] |
| `/expenses` | `expenses` | `auth` | Expense register & sales transfer toggle[cite: 3] |
| `/sales` | `sales` | `auth`, `admin` | Admin Manual / Backup Sales override tool[cite: 3] |
| `/users` | `users` | `auth`, `admin` | System user directory & creation[cite: 3] |
| `/settings` | `settings` | `auth`, `admin` | System configuration settings[cite: 3] |

---

## Database Configuration

Configure your local `.env` file to connect to your MySQL database[cite: 3]:

```dotenv
APP_NAME=Chickventory
APP_URL=[http://127.0.0.1:8000](http://127.0.0.1:8000)

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=chicky_db
DB_USERNAME=root
DB_PASSWORD=