-- 1. Users table
-- Note: PostgreSQL uses SERIAL for auto-increment, MySQL uses AUTO_INCREMENT
-- This script is compatible with PostgreSQL (Neon)

CREATE TABLE IF NOT EXISTS users (
  id SERIAL PRIMARY KEY,
  firebase_uid VARCHAR(255) UNIQUE NOT NULL,
  email VARCHAR(255) NOT NULL,
  name VARCHAR(255),
  profile_image_url TEXT,
  login_method VARCHAR(20) DEFAULT 'email',
  status VARCHAR(20) DEFAULT 'inactive', -- 'active', 'inactive'
  role VARCHAR(20) DEFAULT 'user', -- 'user', 'admin'
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 2. Promotions table
CREATE TABLE IF NOT EXISTS promotions (
  id SERIAL PRIMARY KEY,
  user_id INT,
  image_url TEXT NOT NULL,
  description TEXT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id)
);

-- 3. Invoices table
CREATE TABLE IF NOT EXISTS invoices (
  id SERIAL PRIMARY KEY,
  user_id INT,
  invoice_number VARCHAR(50) NOT NULL,
  amount DECIMAL(10,2),
  pdf_url TEXT,
  status VARCHAR(20) DEFAULT 'pending', -- 'paid', 'pending', 'overdue'
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id)
);
