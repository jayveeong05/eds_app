-- Users Table
-- Re-creating with firebase_uid
DROP TABLE IF EXISTS invoices;
DROP TABLE IF EXISTS promotions;
DROP TABLE IF EXISTS users;

CREATE TABLE IF NOT EXISTS users (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    firebase_uid VARCHAR(128) UNIQUE NOT NULL, -- New field for Firebase Link
    email VARCHAR(255) UNIQUE NOT NULL,
    name VARCHAR(255), -- User's display name
    role VARCHAR(50) DEFAULT 'user', -- 'user' or 'admin'
    status VARCHAR(50) DEFAULT 'inactive', -- 'active' or 'inactive'
    profile_image_url TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Promotions Table
CREATE TABLE IF NOT EXISTS promotions (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    user_id UUID REFERENCES users(id) ON DELETE CASCADE,
    image_url TEXT NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Invoices Table (Machine Code-Based System with Replacement Strategy)
-- Supports bulk uploads with filename parsing (e.g., AA001001-Jan.pdf)
-- Each machine code has max 12 records (one per month)
-- Validation handled in application code (InvoiceParser.php)
CREATE TABLE IF NOT EXISTS invoices (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    code VARCHAR(50) NOT NULL,
    month VARCHAR(20) NOT NULL,
    file_url TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Performance indexes
CREATE INDEX IF NOT EXISTS idx_invoices_code ON invoices(code);
CREATE INDEX IF NOT EXISTS idx_invoices_code_month ON invoices(code, month);
CREATE INDEX IF NOT EXISTS idx_invoices_created_at ON invoices(created_at DESC);

