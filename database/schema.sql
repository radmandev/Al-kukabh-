-- Al Kawkaba — MySQL 5.7+ / MariaDB 10.3+ schema.
-- Import once (phpMyAdmin → Import, or: mysql -u USER -p DBNAME < database/schema.sql).
-- Columns keep the camelCase names used throughout the PHP code.

SET NAMES utf8mb4;

-- role: ADMIN (everything), EDITOR (catalog + content), CUSTOMER
CREATE TABLE IF NOT EXISTS users (
  id           VARCHAR(32)  NOT NULL PRIMARY KEY,
  email        VARCHAR(191) NOT NULL UNIQUE,
  name         VARCHAR(191) NOT NULL,
  phone        VARCHAR(40)  NULL,
  passwordHash VARCHAR(255) NOT NULL,
  role         VARCHAR(16)  NOT NULL DEFAULT 'CUSTOMER',
  createdAt    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS media (
  id        VARCHAR(32)  NOT NULL PRIMARY KEY,
  url       VARCHAR(255) NOT NULL,
  kind      VARCHAR(10)  NOT NULL,            -- image | video
  mime      VARCHAR(64)  NOT NULL,
  width     INT NULL,
  height    INT NULL,
  size      INT NOT NULL DEFAULT 0,
  altAr     VARCHAR(255) NOT NULL DEFAULT '',
  altEn     VARCHAR(255) NOT NULL DEFAULT '',
  posterId  VARCHAR(32)  NULL,                -- video thumbnail (another media row)
  createdAt DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY media_kind (kind, createdAt)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS categories (
  id     VARCHAR(32)  NOT NULL PRIMARY KEY,
  slug   VARCHAR(191) NOT NULL UNIQUE,
  nameAr VARCHAR(191) NOT NULL,
  nameEn VARCHAR(191) NOT NULL,
  sort   INT NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS collections (
  id            VARCHAR(32)  NOT NULL PRIMARY KEY,
  slug          VARCHAR(191) NOT NULL UNIQUE,
  nameAr        VARCHAR(191) NOT NULL,
  nameEn        VARCHAR(191) NOT NULL,
  descAr        TEXT NOT NULL,
  descEn        TEXT NOT NULL,
  coverId       VARCHAR(32) NULL,
  coverMobileId VARCHAR(32) NULL,
  sort          INT NOT NULL DEFAULT 0,
  published     TINYINT(1) NOT NULL DEFAULT 1,
  createdAt     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS products (
  id           VARCHAR(32)  NOT NULL PRIMARY KEY,
  slug         VARCHAR(191) NOT NULL UNIQUE,
  sku          VARCHAR(100) NOT NULL UNIQUE,
  nameAr       VARCHAR(191) NOT NULL,
  nameEn       VARCHAR(191) NOT NULL,
  descAr       TEXT NOT NULL,
  descEn       TEXT NOT NULL,
  fabricAr     TEXT NOT NULL,
  fabricEn     TEXT NOT NULL,
  detailsAr    TEXT NOT NULL,
  detailsEn    TEXT NOT NULL,
  careAr       TEXT NOT NULL,
  careEn       TEXT NOT NULL,
  price        DECIMAL(12,2) NOT NULL DEFAULT 0,
  salePrice    DECIMAL(12,2) NULL,
  sizes        TEXT NOT NULL,                 -- JSON string[]
  colors       TEXT NOT NULL,                 -- JSON {nameAr,nameEn,hex}[]
  stock        INT NOT NULL DEFAULT 0,
  availability VARCHAR(20) NOT NULL DEFAULT 'in_stock', -- in_stock | made_to_order | out_of_stock
  deliveryAr   VARCHAR(255) NOT NULL DEFAULT '',
  deliveryEn   VARCHAR(255) NOT NULL DEFAULT '',
  isNew        TINYINT(1) NOT NULL DEFAULT 0,
  isFeatured   TINYINT(1) NOT NULL DEFAULT 0,
  isLimited    TINYINT(1) NOT NULL DEFAULT 0,
  isReady      TINYINT(1) NOT NULL DEFAULT 0,
  published    TINYINT(1) NOT NULL DEFAULT 1,
  seoTitle     VARCHAR(255) NOT NULL DEFAULT '',
  seoDesc      TEXT NOT NULL,
  categoryId   VARCHAR(32) NULL,
  collectionId VARCHAR(32) NULL,
  createdAt    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updatedAt    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY products_pub (published, createdAt),
  CONSTRAINT products_category FOREIGN KEY (categoryId) REFERENCES categories(id) ON DELETE SET NULL,
  CONSTRAINT products_collection FOREIGN KEY (collectionId) REFERENCES collections(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- order of media per product; sort 0 = primary image
CREATE TABLE IF NOT EXISTS product_media (
  productId VARCHAR(32) NOT NULL,
  mediaId   VARCHAR(32) NOT NULL,
  sort      INT NOT NULL DEFAULT 0,
  PRIMARY KEY (productId, mediaId),
  KEY pm_media (mediaId),
  CONSTRAINT pm_product FOREIGN KEY (productId) REFERENCES products(id) ON DELETE CASCADE,
  CONSTRAINT pm_media FOREIGN KEY (mediaId) REFERENCES media(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Homepage block. type: hero | collections | products | occasions | ready | story | campaign | values | text
-- data: JSON, shape depends on type (see app/lib/sections.php)
CREATE TABLE IF NOT EXISTS sections (
  id        VARCHAR(32) NOT NULL PRIMARY KEY,
  type      VARCHAR(32) NOT NULL,
  sort      INT NOT NULL DEFAULT 0,
  published TINYINT(1) NOT NULL DEFAULT 1,
  data      MEDIUMTEXT NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS discounts (
  id        VARCHAR(32) NOT NULL PRIMARY KEY,
  code      VARCHAR(64) NOT NULL UNIQUE,
  type      VARCHAR(10) NOT NULL DEFAULT 'percent', -- percent | fixed
  value     DECIMAL(12,2) NOT NULL,
  minTotal  DECIMAL(12,2) NOT NULL DEFAULT 0,
  maxUses   INT NULL,
  used      INT NOT NULL DEFAULT 0,
  expiresAt DATETIME NULL,
  active    TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS orders (
  id            VARCHAR(32) NOT NULL PRIMARY KEY,
  number        VARCHAR(32) NOT NULL UNIQUE,
  userId        VARCHAR(32) NULL,
  email         VARCHAR(191) NOT NULL,
  name          VARCHAR(191) NOT NULL,
  phone         VARCHAR(40) NOT NULL,
  country       VARCHAR(80) NOT NULL,
  city          VARCHAR(100) NOT NULL,
  address       VARCHAR(400) NOT NULL,
  notes         TEXT NOT NULL,
  items         MEDIUMTEXT NOT NULL,          -- JSON snapshot of lines
  subtotal      DECIMAL(12,2) NOT NULL,
  discount      DECIMAL(12,2) NOT NULL DEFAULT 0,
  discountCode  VARCHAR(64) NULL,
  shipping      DECIMAL(12,2) NOT NULL DEFAULT 0,
  total         DECIMAL(12,2) NOT NULL,
  currency      VARCHAR(8) NOT NULL,
  paymentMethod VARCHAR(20) NOT NULL,
  paymentStatus VARCHAR(20) NOT NULL DEFAULT 'pending',   -- pending | paid | failed | refunded
  status        VARCHAR(20) NOT NULL DEFAULT 'pending',   -- pending | confirmed | processing | cancelled | completed
  shipStatus    VARCHAR(20) NOT NULL DEFAULT 'unfulfilled', -- unfulfilled | shipped | delivered | returned
  tracking      VARCHAR(255) NOT NULL DEFAULT '',
  history       MEDIUMTEXT NOT NULL,          -- JSON {at, note, by?}[]
  createdAt     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY orders_user (userId),
  KEY orders_email (email),
  KEY orders_status (status, createdAt),
  CONSTRAINT orders_user FOREIGN KEY (userId) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS subscribers (
  id        VARCHAR(32) NOT NULL PRIMARY KEY,
  email     VARCHAR(191) NOT NULL UNIQUE,
  createdAt DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- single key/value store for store settings (JSON values)
CREATE TABLE IF NOT EXISTS settings (
  `key`   VARCHAR(64) NOT NULL PRIMARY KEY,
  `value` MEDIUMTEXT NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
