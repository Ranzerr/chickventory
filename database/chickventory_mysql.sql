-- Chickventory MySQL schema
-- Import this file into the database selected in phpMyAdmin.
-- Structure only, plus the migrations ledger so `php artisan migrate`
-- sees these migrations as already run.

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

CREATE TABLE IF NOT EXISTS `migrations` (
    `id` int unsigned NOT NULL AUTO_INCREMENT,
    `migration` varchar(255) NOT NULL,
    `batch` int NOT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `migrations_migration_unique` (`migration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `migrations` (`migration`, `batch`) VALUES
    ('0001_01_01_000000_create_users_table', 1),
    ('0001_01_01_000001_create_cache_table', 1),
    ('0001_01_01_000002_create_jobs_table', 1),
    ('2026_09_14_000003_create_inventory_tables', 1),
    ('2026_09_14_000004_add_role_to_users_table', 1),
    ('2026_09_14_000005_create_procurement_and_recipe_tables', 1)
ON DUPLICATE KEY UPDATE `batch` = VALUES(`batch`);

CREATE TABLE IF NOT EXISTS `users` (
    `id` bigint unsigned NOT NULL AUTO_INCREMENT,
    `name` varchar(255) NOT NULL,
    `email` varchar(255) NOT NULL,
    `role` varchar(255) NOT NULL DEFAULT 'Staff',
    `status` varchar(255) NOT NULL DEFAULT 'active',
    `email_verified_at` timestamp NULL DEFAULT NULL,
    `password` varchar(255) NOT NULL,
    `remember_token` varchar(100) DEFAULT NULL,
    `created_at` timestamp NULL DEFAULT NULL,
    `updated_at` timestamp NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `users_email_unique` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `password_reset_tokens` (
    `email` varchar(255) NOT NULL,
    `token` varchar(255) NOT NULL,
    `created_at` timestamp NULL DEFAULT NULL,
    PRIMARY KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `sessions` (
    `id` varchar(255) NOT NULL,
    `user_id` bigint unsigned DEFAULT NULL,
    `ip_address` varchar(45) DEFAULT NULL,
    `user_agent` text,
    `payload` longtext NOT NULL,
    `last_activity` int NOT NULL,
    PRIMARY KEY (`id`),
    KEY `sessions_user_id_index` (`user_id`),
    KEY `sessions_last_activity_index` (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `cache` (
    `key` varchar(255) NOT NULL,
    `value` mediumtext NOT NULL,
    `expiration` bigint NOT NULL,
    PRIMARY KEY (`key`),
    KEY `cache_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `cache_locks` (
    `key` varchar(255) NOT NULL,
    `owner` varchar(255) NOT NULL,
    `expiration` bigint NOT NULL,
    PRIMARY KEY (`key`),
    KEY `cache_locks_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `jobs` (
    `id` bigint unsigned NOT NULL AUTO_INCREMENT,
    `queue` varchar(255) NOT NULL,
    `payload` longtext NOT NULL,
    `attempts` tinyint unsigned NOT NULL,
    `reserved_at` int unsigned DEFAULT NULL,
    `available_at` int unsigned NOT NULL,
    `created_at` int unsigned NOT NULL,
    PRIMARY KEY (`id`),
    KEY `jobs_queue_index` (`queue`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `job_batches` (
    `id` varchar(255) NOT NULL,
    `name` varchar(255) NOT NULL,
    `total_jobs` int NOT NULL,
    `pending_jobs` int NOT NULL,
    `failed_jobs` int NOT NULL,
    `failed_job_ids` longtext NOT NULL,
    `options` mediumtext,
    `cancelled_at` int DEFAULT NULL,
    `created_at` int NOT NULL,
    `finished_at` int DEFAULT NULL,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `failed_jobs` (
    `id` bigint unsigned NOT NULL AUTO_INCREMENT,
    `uuid` varchar(255) NOT NULL,
    `connection` varchar(255) NOT NULL,
    `queue` varchar(255) NOT NULL,
    `payload` longtext NOT NULL,
    `exception` longtext NOT NULL,
    `failed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`),
    KEY `failed_jobs_connection_queue_failed_at_index` (`connection`, `queue`, `failed_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `suppliers` (
    `id` bigint unsigned NOT NULL AUTO_INCREMENT,
    `name` varchar(255) NOT NULL,
    `contact_person` varchar(255) DEFAULT NULL,
    `phone` varchar(255) DEFAULT NULL,
    `email` varchar(255) DEFAULT NULL,
    `status` varchar(255) NOT NULL DEFAULT 'active',
    `requires_po` tinyint(1) NOT NULL DEFAULT 0,
    `created_at` timestamp NULL DEFAULT NULL,
    `updated_at` timestamp NULL DEFAULT NULL,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `products` (
    `id` bigint unsigned NOT NULL AUTO_INCREMENT,
    `product_code` varchar(255) NOT NULL,
    `name` varchar(255) NOT NULL,
    `category` varchar(255) NOT NULL DEFAULT 'Ingredients',
    `supplier_id` bigint unsigned DEFAULT NULL,
    `current_stock` decimal(12,2) NOT NULL DEFAULT 0.00,
    `minimum_stock` decimal(12,2) NOT NULL DEFAULT 0.00,
    `unit` varchar(30) NOT NULL DEFAULT 'pcs',
    `status` varchar(255) NOT NULL DEFAULT 'active',
    `created_at` timestamp NULL DEFAULT NULL,
    `updated_at` timestamp NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `products_product_code_unique` (`product_code`),
    KEY `products_supplier_id_foreign` (`supplier_id`),
    CONSTRAINT `products_supplier_id_foreign` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `inventory_transactions` (
    `id` bigint unsigned NOT NULL AUTO_INCREMENT,
    `transaction_code` varchar(255) NOT NULL,
    `product_id` bigint unsigned NOT NULL,
    `reference` varchar(255) DEFAULT NULL,
    `type` varchar(30) NOT NULL,
    `quantity` decimal(12,2) NOT NULL,
    `source` varchar(255) NOT NULL DEFAULT 'Manual Entry',
    `status` varchar(255) NOT NULL DEFAULT 'completed',
    `occurred_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `created_at` timestamp NULL DEFAULT NULL,
    `updated_at` timestamp NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `inventory_transactions_transaction_code_unique` (`transaction_code`),
    KEY `inventory_transactions_product_id_foreign` (`product_id`),
    KEY `inventory_transactions_type_occurred_at_index` (`type`, `occurred_at`),
    CONSTRAINT `inventory_transactions_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `system_settings` (
    `id` bigint unsigned NOT NULL AUTO_INCREMENT,
    `key` varchar(255) NOT NULL,
    `value` text,
    `created_at` timestamp NULL DEFAULT NULL,
    `updated_at` timestamp NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `system_settings_key_unique` (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `raw_materials` (
    `id` bigint unsigned NOT NULL AUTO_INCREMENT,
    `material_code` varchar(255) NOT NULL,
    `name` varchar(255) NOT NULL,
    `unit` varchar(30) NOT NULL DEFAULT 'pcs',
    `current_stock` decimal(12,2) NOT NULL DEFAULT 0.00,
    `minimum_stock` decimal(12,2) NOT NULL DEFAULT 0.00,
    `status` varchar(255) NOT NULL DEFAULT 'active',
    `created_at` timestamp NULL DEFAULT NULL,
    `updated_at` timestamp NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `raw_materials_material_code_unique` (`material_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `purchase_orders` (
    `id` bigint unsigned NOT NULL AUTO_INCREMENT,
    `po_number` varchar(255) NOT NULL,
    `supplier_id` bigint unsigned NOT NULL,
    `created_by` bigint unsigned DEFAULT NULL,
    `order_date` date NOT NULL,
    `status` varchar(255) NOT NULL DEFAULT 'draft',
    `created_at` timestamp NULL DEFAULT NULL,
    `updated_at` timestamp NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `purchase_orders_po_number_unique` (`po_number`),
    KEY `purchase_orders_supplier_id_foreign` (`supplier_id`),
    KEY `purchase_orders_created_by_foreign` (`created_by`),
    KEY `purchase_orders_status_order_date_index` (`status`, `order_date`),
    CONSTRAINT `purchase_orders_supplier_id_foreign` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`) ON DELETE RESTRICT,
    CONSTRAINT `purchase_orders_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `purchase_order_items` (
    `id` bigint unsigned NOT NULL AUTO_INCREMENT,
    `purchase_order_id` bigint unsigned NOT NULL,
    `material_id` bigint unsigned NOT NULL,
    `quantity_ordered` decimal(12,2) NOT NULL,
    `unit_price` decimal(12,2) NOT NULL,
    `created_at` timestamp NULL DEFAULT NULL,
    `updated_at` timestamp NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `purchase_order_items_purchase_order_id_foreign` (`purchase_order_id`),
    KEY `purchase_order_items_material_id_foreign` (`material_id`),
    CONSTRAINT `purchase_order_items_purchase_order_id_foreign` FOREIGN KEY (`purchase_order_id`) REFERENCES `purchase_orders` (`id`) ON DELETE CASCADE,
    CONSTRAINT `purchase_order_items_material_id_foreign` FOREIGN KEY (`material_id`) REFERENCES `raw_materials` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `purchases` (
    `id` bigint unsigned NOT NULL AUTO_INCREMENT,
    `po_id` bigint unsigned DEFAULT NULL,
    `purchase_type` varchar(255) NOT NULL DEFAULT 'direct',
    `supplier_id` bigint unsigned NOT NULL,
    `received_by` bigint unsigned DEFAULT NULL,
    `purchase_date` date NOT NULL,
    `created_at` timestamp NULL DEFAULT NULL,
    `updated_at` timestamp NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `purchases_po_id_foreign` (`po_id`),
    KEY `purchases_supplier_id_foreign` (`supplier_id`),
    KEY `purchases_received_by_foreign` (`received_by`),
    CONSTRAINT `purchases_po_id_foreign` FOREIGN KEY (`po_id`) REFERENCES `purchase_orders` (`id`) ON DELETE SET NULL,
    CONSTRAINT `purchases_supplier_id_foreign` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`) ON DELETE RESTRICT,
    CONSTRAINT `purchases_received_by_foreign` FOREIGN KEY (`received_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `purchase_items` (
    `id` bigint unsigned NOT NULL AUTO_INCREMENT,
    `purchase_id` bigint unsigned NOT NULL,
    `material_id` bigint unsigned NOT NULL,
    `quantity_received` decimal(12,2) NOT NULL,
    `unit_cost` decimal(12,2) NOT NULL,
    `subtotal` decimal(14,2) NOT NULL,
    `created_at` timestamp NULL DEFAULT NULL,
    `updated_at` timestamp NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `purchase_items_purchase_id_foreign` (`purchase_id`),
    KEY `purchase_items_material_id_foreign` (`material_id`),
    CONSTRAINT `purchase_items_purchase_id_foreign` FOREIGN KEY (`purchase_id`) REFERENCES `purchases` (`id`) ON DELETE CASCADE,
    CONSTRAINT `purchase_items_material_id_foreign` FOREIGN KEY (`material_id`) REFERENCES `raw_materials` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `supplier_material` (
    `supplier_id` bigint unsigned NOT NULL,
    `material_id` bigint unsigned NOT NULL,
    `last_unit_cost` decimal(12,2) NOT NULL DEFAULT 0.00,
    `created_at` timestamp NULL DEFAULT NULL,
    `updated_at` timestamp NULL DEFAULT NULL,
    PRIMARY KEY (`supplier_id`, `material_id`),
    KEY `supplier_material_material_id_foreign` (`material_id`),
    CONSTRAINT `supplier_material_supplier_id_foreign` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`) ON DELETE CASCADE,
    CONSTRAINT `supplier_material_material_id_foreign` FOREIGN KEY (`material_id`) REFERENCES `raw_materials` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `product_recipes` (
    `id` bigint unsigned NOT NULL AUTO_INCREMENT,
    `product_id` bigint unsigned NOT NULL,
    `material_id` bigint unsigned NOT NULL,
    `quantity_required` decimal(12,4) NOT NULL,
    `created_at` timestamp NULL DEFAULT NULL,
    `updated_at` timestamp NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `product_recipes_product_id_material_id_unique` (`product_id`, `material_id`),
    KEY `product_recipes_material_id_foreign` (`material_id`),
    CONSTRAINT `product_recipes_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
    CONSTRAINT `product_recipes_material_id_foreign` FOREIGN KEY (`material_id`) REFERENCES `raw_materials` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `order_items` (
    `id` bigint unsigned NOT NULL AUTO_INCREMENT,
    `external_order_id` varchar(255) NOT NULL,
    `product_id` bigint unsigned NOT NULL,
    `quantity` int unsigned NOT NULL,
    `order_date` datetime NOT NULL,
    `source_system` varchar(255) NOT NULL DEFAULT 'manual',
    `created_at` timestamp NULL DEFAULT NULL,
    `updated_at` timestamp NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `order_items_external_order_id_unique` (`external_order_id`),
    KEY `order_items_product_id_foreign` (`product_id`),
    CONSTRAINT `order_items_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `stock_movements` (
    `id` bigint unsigned NOT NULL AUTO_INCREMENT,
    `material_id` bigint unsigned NOT NULL,
    `movement_type` varchar(30) NOT NULL,
    `quantity` decimal(12,4) NOT NULL,
    `reference_type` varchar(255) DEFAULT NULL,
    `reference_id` bigint unsigned DEFAULT NULL,
    `performed_by` bigint unsigned DEFAULT NULL,
    `remarks` text,
    `created_at` timestamp NULL DEFAULT NULL,
    `updated_at` timestamp NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `stock_movements_material_id_foreign` (`material_id`),
    KEY `stock_movements_performed_by_foreign` (`performed_by`),
    KEY `stock_movements_reference_type_reference_id_index` (`reference_type`, `reference_id`),
    CONSTRAINT `stock_movements_material_id_foreign` FOREIGN KEY (`material_id`) REFERENCES `raw_materials` (`id`) ON DELETE CASCADE,
    CONSTRAINT `stock_movements_performed_by_foreign` FOREIGN KEY (`performed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `expenses` (
    `id` bigint unsigned NOT NULL AUTO_INCREMENT,
    `purchase_id` bigint unsigned DEFAULT NULL,
    `description` varchar(255) NOT NULL,
    `amount` decimal(14,2) NOT NULL,
    `expense_date` date NOT NULL,
    `transferred_to_sales` tinyint(1) NOT NULL DEFAULT 0,
    `created_at` timestamp NULL DEFAULT NULL,
    `updated_at` timestamp NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `expenses_purchase_id_foreign` (`purchase_id`),
    CONSTRAINT `expenses_purchase_id_foreign` FOREIGN KEY (`purchase_id`) REFERENCES `purchases` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;