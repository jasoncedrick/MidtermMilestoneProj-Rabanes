-- CLEAN INSTALL: create schema plus six category seeds; no users or recipes.
-- Import explicitly in phpMyAdmin; PHP does not run this file on each request.
-- Import this file using phpMyAdmin. It creates six tables and starting categories.
-- No sample accounts or recipes are included. Existing tables are kept.
CREATE DATABASE IF NOT EXISTS kusina_barangay
    CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE kusina_barangay;
-- Accounts: unique email; password_hash contains a hash, never a plaintext password.
CREATE TABLE IF NOT EXISTS users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(60) NOT NULL,
    email VARCHAR(190) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
-- Shared lookup values used by recipes.category_id.
CREATE TABLE IF NOT EXISTS categories (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(60) NOT NULL UNIQUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
-- Each recipe belongs to a user and category; updated_at starts NULL until an edit.
CREATE TABLE IF NOT EXISTS recipes (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    category_id INT UNSIGNED NOT NULL,
    title VARCHAR(100) NOT NULL,
    description VARCHAR(300) NOT NULL,
    steps TEXT NOT NULL,
    cook_minutes SMALLINT UNSIGNED NOT NULL CHECK(cook_minutes BETWEEN 1 AND 1440),
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT NULL,
    FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY(category_id) REFERENCES categories(id) ON DELETE RESTRICT,
    INDEX recipes_latest(created_at, id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
-- One row per ingredient; recipe_id links its recipe and position preserves order.
CREATE TABLE IF NOT EXISTS ingredients (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    recipe_id INT UNSIGNED NOT NULL,
    item VARCHAR(160) NOT NULL,
    position SMALLINT UNSIGNED NOT NULL CHECK(position BETWEEN 1 AND 40),
    UNIQUE(recipe_id, position),
    FOREIGN KEY(recipe_id) REFERENCES recipes(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
-- Each comment links an author to a recipe; updated_at controls its Edited label.
CREATE TABLE IF NOT EXISTS comments (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    recipe_id INT UNSIGNED NOT NULL,
    body VARCHAR(1000) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT NULL,
    FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY(recipe_id) REFERENCES recipes(id) ON DELETE CASCADE,
    INDEX comments_recipe(recipe_id, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
-- Many-to-many link: composite primary key prevents a user saving a recipe twice.
CREATE TABLE IF NOT EXISTS favorites (
    user_id INT UNSIGNED NOT NULL,
    recipe_id INT UNSIGNED NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY(user_id, recipe_id),
    FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY(recipe_id) REFERENCES recipes(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
-- Seed categories. Existing primary/unique-key conflicts are skipped on repeat import.
INSERT IGNORE INTO categories (id, name) VALUES
    (1, 'Ulam'), (2, 'Sabaw'), (3, 'Gulay'),
    (4, 'Merienda'), (5, 'Panghimagas'), (6, 'Almusal');
