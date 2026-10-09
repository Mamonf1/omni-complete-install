-- ═══════════════════════════════════════════════════════════════════════
--   أومني — قاعدة البيانات الكاملة (MySQL / MariaDB) v6
--   الاستخدام: phpMyAdmin → SQL → الصق الكل → GO
--   ملاحظة: أخطاء "Duplicate column name" آمنة — تجاهلها
-- ═══════════════════════════════════════════════════════════════════════

SET NAMES utf8mb4;
SET time_zone = '+03:00';
SET FOREIGN_KEY_CHECKS = 0;

-- ─── 1) المستخدمون ───
CREATE TABLE IF NOT EXISTS profiles (
  id VARCHAR(36) PRIMARY KEY,
  name VARCHAR(200) NOT NULL,
  phone VARCHAR(20) UNIQUE,
  phone_country VARCHAR(10) DEFAULT '963',
  email VARCHAR(200) UNIQUE,
  password_hash VARCHAR(255) NULL,
  role ENUM('customer','merchant','admin') DEFAULT 'customer',
  account_number VARCHAR(20) UNIQUE,
  balance_omni DECIMAL(15,2) DEFAULT 0,
  balance_syp DECIMAL(15,2) DEFAULT 0,
  user_type VARCHAR(20) DEFAULT 'regular',
  merchant_status ENUM('pending','approved','rejected') DEFAULT 'approved',
  store_name VARCHAR(200) NULL,
  business_name VARCHAR(200) NULL,
  store_id VARCHAR(36) NULL,
  api_key VARCHAR(64) NULL,
  telegram_linked TINYINT(1) DEFAULT 0,
  spin_balance INT DEFAULT 0,
  last_free_spin TIMESTAMP NULL,
  last_login TIMESTAMP NULL,
  login_count INT DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX(phone), INDEX(email), INDEX(role), INDEX(merchant_status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE profiles
  ADD COLUMN IF NOT EXISTS spin_balance INT DEFAULT 0,
  ADD COLUMN IF NOT EXISTS last_free_spin TIMESTAMP NULL,
  ADD COLUMN IF NOT EXISTS balance_omni DECIMAL(15,2) DEFAULT 0,
  ADD COLUMN IF NOT EXISTS balance_syp DECIMAL(15,2) DEFAULT 0,
  ADD COLUMN IF NOT EXISTS merchant_status VARCHAR(20) DEFAULT 'approved',
  ADD COLUMN IF NOT EXISTS user_type VARCHAR(20) DEFAULT 'regular',
  ADD COLUMN IF NOT EXISTS store_name VARCHAR(200) NULL,
  ADD COLUMN IF NOT EXISTS business_name VARCHAR(200) NULL,
  ADD COLUMN IF NOT EXISTS store_id VARCHAR(36) NULL,
  ADD COLUMN IF NOT EXISTS api_key VARCHAR(64) NULL,
  ADD COLUMN IF NOT EXISTS last_login TIMESTAMP NULL,
  ADD COLUMN IF NOT EXISTS login_count INT DEFAULT 0,
  ADD COLUMN IF NOT EXISTS telegram_linked TINYINT(1) DEFAULT 0;

-- ─── 2) الصندوق والإيرادات ───
CREATE TABLE IF NOT EXISTS omni_fund (
  id INT PRIMARY KEY DEFAULT 1,
  balance DECIMAL(15,2) DEFAULT 0,
  total_income DECIMAL(15,2) DEFAULT 0,
  total_expenses DECIMAL(15,2) DEFAULT 0,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
INSERT IGNORE INTO omni_fund (id, balance) VALUES (1, 0);

CREATE TABLE IF NOT EXISTS revenue_log (
  id VARCHAR(36) PRIMARY KEY,
  source VARCHAR(50) NOT NULL,
  amount DECIMAL(15,2) NOT NULL,
  user_id VARCHAR(36) NULL,
  description VARCHAR(255) NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX(source), INDEX(created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── 3) إعدادات الرسوم ───
CREATE TABLE IF NOT EXISTS fee_settings (
  `key` VARCHAR(64) PRIMARY KEY,
  value DECIMAL(10,4) NOT NULL DEFAULT 0,
  description VARCHAR(255) NULL,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO fee_settings (`key`, value, description) VALUES
('transfer_fee_percent',       0.5, 'أجرة التحويل %'),
('exchange_fee_percent',       0.5, 'أجرة الصرف %'),
('product_commission_percent', 5.0, 'عمولة المنتجات %'),
('stock_trade_fee_percent',    1.0, 'أجرة تداول الأسهم %'),
('game_package_share_percent', 25.0,'حصة الألعاب %');

-- ─── 4) أسعار الصرف ───
CREATE TABLE IF NOT EXISTS exchange_rates (
  id INT AUTO_INCREMENT PRIMARY KEY,
  usd_to_syp DECIMAL(15,2) NOT NULL,
  omni_to_usd DECIMAL(15,8) NOT NULL DEFAULT 0.005,
  updated_by VARCHAR(36) NULL,
  notes VARCHAR(255) NULL,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX(updated_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
INSERT IGNORE INTO exchange_rates (id, usd_to_syp, omni_to_usd, notes)
VALUES (1, 15000, 0.005, 'الأسعار الأولية');

-- ─── 5) المعاملات ───
CREATE TABLE IF NOT EXISTS transactions (
  id VARCHAR(36) PRIMARY KEY,
  user_id VARCHAR(36) NOT NULL,
  type ENUM('in','out') NOT NULL,
  title VARCHAR(255) NOT NULL,
  amount DECIMAL(15,2) NOT NULL,
  currency VARCHAR(10) DEFAULT 'OMNI',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX(user_id, created_at), INDEX(type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── 6) المتاجر ───
CREATE TABLE IF NOT EXISTS stores (
  id VARCHAR(36) PRIMARY KEY,
  owner_id VARCHAR(36) NULL,
  name VARCHAR(200) NOT NULL,
  category VARCHAR(50) NULL,
  phone VARCHAR(20) NULL,
  address TEXT NULL,
  icon VARCHAR(20) DEFAULT '🏪',
  active TINYINT(1) DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX(owner_id), INDEX(category), INDEX(active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── 7) المنتجات ───
CREATE TABLE IF NOT EXISTS products (
  id VARCHAR(36) PRIMARY KEY,
  store_id VARCHAR(36) NOT NULL,
  name VARCHAR(200) NOT NULL,
  description TEXT NULL,
  price DECIMAL(15,2) NOT NULL,
  old_price DECIMAL(15,2) NULL,
  unit VARCHAR(50) DEFAULT 'قطعة',
  icon VARCHAR(20) DEFAULT '📦',
  category VARCHAR(50) NULL,
  stock INT DEFAULT 100,
  featured TINYINT(1) DEFAULT 0,
  active TINYINT(1) DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX(store_id), INDEX(category), INDEX(active, featured)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── 8) الطلبات ───
CREATE TABLE IF NOT EXISTS orders (
  id VARCHAR(36) PRIMARY KEY,
  order_number VARCHAR(50) UNIQUE NOT NULL,
  user_id VARCHAR(36) NOT NULL,
  status ENUM('pending','confirmed','preparing','delivering','delivered','cancelled') DEFAULT 'pending',
  subtotal DECIMAL(15,2) NOT NULL DEFAULT 0,
  delivery_fee DECIMAL(15,2) NOT NULL DEFAULT 0,
  total DECIMAL(15,2) NOT NULL DEFAULT 0,
  payment_method VARCHAR(20) DEFAULT 'OMNI',
  customer_name VARCHAR(200) NULL,
  customer_phone VARCHAR(20) NULL,
  address TEXT NULL,
  city VARCHAR(50) NULL,
  notes TEXT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX(user_id, created_at), INDEX(status), INDEX(created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── 9) عناصر الطلب ───
CREATE TABLE IF NOT EXISTS order_items (
  id VARCHAR(36) PRIMARY KEY,
  order_id VARCHAR(36) NOT NULL,
  product_id VARCHAR(36) NULL,
  product_name VARCHAR(200) NOT NULL,
  product_icon VARCHAR(20) NULL,
  price DECIMAL(15,2) NOT NULL,
  quantity INT NOT NULL,
  store_id VARCHAR(36) NULL,
  store_name VARCHAR(200) NULL,
  merchant_status ENUM('pending','confirmed','preparing','delivering','delivered','cancelled') DEFAULT 'pending',
  INDEX(order_id), INDEX(store_id), INDEX(merchant_status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── 10) العجلة ───
CREATE TABLE IF NOT EXISTS wheel_pool (
  id INT PRIMARY KEY DEFAULT 1,
  balance DECIMAL(15,2) DEFAULT 0,
  total_collected DECIMAL(15,2) DEFAULT 0,
  total_paid_out DECIMAL(15,2) DEFAULT 0,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
INSERT IGNORE INTO wheel_pool (id, balance) VALUES (1, 0);

CREATE TABLE IF NOT EXISTS wheel_pool_log (
  id VARCHAR(36) PRIMARY KEY,
  type ENUM('in','out') NOT NULL,
  source VARCHAR(50) NOT NULL,
  amount DECIMAL(15,2) NOT NULL,
  user_id VARCHAR(36) NULL,
  description VARCHAR(255) NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX(type, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS wheel_spins (
  id VARCHAR(36) PRIMARY KEY,
  user_id VARCHAR(36) NOT NULL,
  prize_value DECIMAL(15,2) DEFAULT 0,
  spin_type ENUM('free','paid') DEFAULT 'free',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX(user_id, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── 11) الدعم الفوري ───
CREATE TABLE IF NOT EXISTS chat_conversations (
  id VARCHAR(36) PRIMARY KEY,
  user_id VARCHAR(36) NOT NULL,
  subject VARCHAR(200) DEFAULT 'استفسار',
  status ENUM('open','closed') DEFAULT 'open',
  last_message_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  last_message_preview VARCHAR(200) NULL,
  unread_admin INT DEFAULT 0,
  unread_user INT DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX(user_id), INDEX(status, last_message_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS chat_messages (
  id VARCHAR(36) PRIMARY KEY,
  conversation_id VARCHAR(36) NOT NULL,
  sender_id VARCHAR(36) NOT NULL,
  sender_role ENUM('user','admin') NOT NULL,
  message TEXT NOT NULL,
  is_read TINYINT(1) DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX(conversation_id, created_at),
  INDEX(conversation_id, is_read)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── 12) التقييمات ───
CREATE TABLE IF NOT EXISTS product_reviews (
  id VARCHAR(36) PRIMARY KEY,
  product_id VARCHAR(36) NOT NULL,
  user_id VARCHAR(36) NOT NULL,
  order_id VARCHAR(36) NOT NULL,
  rating TINYINT NOT NULL,
  comment TEXT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY unique_user_product_order (user_id, product_id, order_id),
  INDEX(product_id, created_at), INDEX(user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── 13) بيانات تجريبية ───
INSERT IGNORE INTO stores (id, name, category, icon, active) VALUES
('s1', 'بقالة الأمان',     'groceries',   '🏪', 1),
('s2', 'مطعم الشام',       'restaurants', '🍽️', 1),
('s3', 'حلويات الأصيل',    'sweets',      '🍰', 1),
('s4', 'مقهى الياسمين',    'drinks',      '☕', 1),
('s5', 'صيدلية النور',     'pharmacy',    '💊', 1);

INSERT IGNORE INTO products (id, store_id, name, description, price, old_price, unit, icon, category, featured) VALUES
('p1',  's1', 'أرز بسمتي فاخر',   'أرز بسمتي هندي',   5000,  6500,  'كغ',      '🍚', 'groceries',   1),
('p2',  's1', 'زيت زيتون',        'زيت زيتون سوري',   12000, 15000, 'ليتر',    '🫒', 'groceries',   1),
('p3',  's2', 'برغر لحم',         'لحم بقري طازج',    15000, NULL,  'وجبة',    '🍔', 'restaurants', 1),
('p4',  's2', 'شاورما دجاج',      'مع ثومية',         12000, NULL,  'ساندويش', '🌯', 'restaurants', 1),
('p5',  's3', 'كنافة بالجبن',     'كنافة نابلسية',    18000, 22000, 'كغ',      '🍰', 'sweets',      1),
('p6',  's1', 'شاي أحمد',         'شاي إنجليزي',       4500, NULL,  'علبة',    '🍵', 'groceries',   0),
('p7',  's4', 'قهوة عربية',       'مع هيل',            8000, NULL,  'علبة',    '☕', 'drinks',      0),
('p8',  's5', 'باراسيتامول',      'مسكن',              2000, NULL,  'علبة',    '💊', 'pharmacy',    0),
('p9',  's4', 'عصير برتقال',      'طازج 100%',         6000,  7000,  'ليتر',    '🧃', 'drinks',      1),
('p10', 's1', 'خبز عربي',         'طازج',              1500, NULL,  'ربطة',    '🥖', 'groceries',   0),
('p11', 's1', 'بندورة',           'طازجة',             3500, NULL,  'كغ',      '🍅', 'groceries',   0),
('p12', 's3', 'شوكولا جالاكسي',   'بالحليب',           3000,  4000,  'لوح',     '🍫', 'sweets',      1);

SET FOREIGN_KEY_CHECKS = 1;
-- ✅ انتهى
