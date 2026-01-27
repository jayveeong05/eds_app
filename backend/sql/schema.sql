-- Users Table
-- Re-creating with firebase_uid
DROP TABLE IF EXISTS user_codes;
DROP TABLE IF EXISTS invoices;
DROP TABLE IF EXISTS promotions;
DROP TABLE IF EXISTS users;
DROP TABLE IF EXISTS news;
DROP TABLE IF EXISTS chat_sessions;
DROP TABLE IF EXISTS chat_messages;
DROP TABLE IF EXISTS admin_activity_log;
DROP TABLE IF EXISTS knowledge_base;
DROP TABLE IF EXISTS customer_requests;

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

-- News Table
CREATE TABLE IF NOT EXISTS news (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    user_id UUID REFERENCES users(id) ON DELETE CASCADE,
    title VARCHAR(255) NOT NULL,
    short_description VARCHAR(500) NOT NULL,
    details TEXT NOT NULL,
    link VARCHAR(500) NOT NULL,
    image_url TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Invoices Table (Machine Code-Based System)
-- Supports bulk uploads with filename parsing (e.g., AA001001-Jan-2025-001.pdf)
-- Format: CODE-MONTH-YEAR-INVOICENUMBER.pdf
-- Supports multiple invoices per code+month+year with different invoice numbers
-- Validation handled in application code (InvoiceParser.php)
CREATE TABLE IF NOT EXISTS invoices (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    code VARCHAR(50) NOT NULL,
    month VARCHAR(20) NOT NULL,
    invoice_year INTEGER NOT NULL, -- Actual invoice year from filename (not upload year)
    invoice_number VARCHAR(50) NOT NULL, -- Invoice number from filename (e.g., 001, IV001, M0176234)
    file_url TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Knowledge Base Table
-- Stores PDF documents with titles and subtitles for user reference
CREATE TABLE IF NOT EXISTS knowledge_base (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    title VARCHAR(255) NOT NULL,
    subtitle VARCHAR(500),
    file_url TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Chat Sessions Table
CREATE TABLE IF NOT EXISTS chat_sessions (
    id SERIAL PRIMARY KEY,
    user_id UUID NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    title VARCHAR(255) DEFAULT 'New Chat',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Chat Messages Table
CREATE TABLE IF NOT EXISTS chat_messages (
  id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  user_id UUID NOT NULL REFERENCES users(id),
  session_id INT REFERENCES chat_sessions(id) ON DELETE CASCADE,
  message_text TEXT NOT NULL,
  is_user_message BOOLEAN DEFAULT true,
  is_favorite BOOLEAN DEFAULT false,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Admin Activity Log Table
-- Tracks admin actions for audit and monitoring purposes
CREATE TABLE IF NOT EXISTS admin_activity_log (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    admin_user_id UUID NOT NULL REFERENCES users(id),
    action VARCHAR(100) NOT NULL,
    target_type VARCHAR(50),
    target_id UUID,
    details TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Printer Recommendation Requests Table
-- Tracks anonymous device requests for printer recommendations
CREATE TABLE IF NOT EXISTS customer_requests (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    device_id VARCHAR(255) NOT NULL,
    
    -- Extracted Requirements (from 'request' JSON)
    office_size VARCHAR(100),           -- e.g., "Small office, 5 people"
    monthly_volume INT,                 -- Numeric volume, e.g., 3000
    color_preference VARCHAR(20),       -- "Color" or "Mono"
    paper_size VARCHAR(10),             -- "A4" or "A3"
    scanning_frequency VARCHAR(50),     -- "None", "Occasional", "Heavy"
    budget_level VARCHAR(50),           -- "Low", "Medium", "High"
    
    -- Metadata
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- User Codes Table (User-Specific Invoice Filtering)
CREATE TABLE IF NOT EXISTS user_codes (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    user_id UUID NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    code VARCHAR(50) NOT NULL,
    assigned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE(user_id, code)
);

-- Performance indexes
CREATE INDEX IF NOT EXISTS idx_invoices_code ON invoices(code);
CREATE INDEX IF NOT EXISTS idx_invoices_code_month ON invoices(code, month);
CREATE INDEX IF NOT EXISTS idx_invoices_invoice_year ON invoices(invoice_year DESC);
CREATE INDEX IF NOT EXISTS idx_invoices_created_at ON invoices(created_at DESC);
CREATE UNIQUE INDEX IF NOT EXISTS idx_invoices_code_month_year_invoice ON invoices(code, month, invoice_year, invoice_number);
CREATE INDEX IF NOT EXISTS idx_knowledge_base_title ON knowledge_base(title);
CREATE INDEX IF NOT EXISTS idx_knowledge_base_created_at ON knowledge_base(created_at DESC);
CREATE INDEX IF NOT EXISTS idx_activity_log_admin ON admin_activity_log(admin_user_id);
CREATE INDEX IF NOT EXISTS idx_activity_log_created ON admin_activity_log(created_at DESC);
CREATE INDEX IF NOT EXISTS idx_chat_user_id ON chat_messages(user_id);
CREATE INDEX IF NOT EXISTS idx_chat_created_at ON chat_messages(created_at DESC);
CREATE INDEX IF NOT EXISTS idx_news_created_at ON news(created_at DESC);
CREATE INDEX IF NOT EXISTS idx_customer_requests_device ON customer_requests(device_id);
CREATE INDEX IF NOT EXISTS idx_customer_requests_created ON customer_requests(created_at DESC);
CREATE INDEX IF NOT EXISTS idx_user_codes_user ON user_codes(user_id);
CREATE INDEX IF NOT EXISTS idx_user_codes_code ON user_codes(code);
