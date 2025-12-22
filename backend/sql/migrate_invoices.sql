-- Migration Script: Invoices Table Redesign
-- Date: 2025-12-19
-- Purpose: Convert from user-based to machine code-based invoice system

-- STEP 1: Backup existing data (if any)
DO $$
BEGIN
    IF EXISTS (SELECT FROM information_schema.tables WHERE table_name = 'invoices') THEN
        CREATE TABLE invoices_backup AS SELECT * FROM invoices;
        RAISE NOTICE 'Backed up existing invoices table to invoices_backup';
    END IF;
END $$;

-- STEP 2: Drop old table and indexes
DROP TABLE IF EXISTS invoices CASCADE;

-- STEP 3: Create new invoices table with machine code schema
CREATE TABLE invoices (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    code VARCHAR(50) NOT NULL,           -- Machine code (e.g., AA001001)
    month VARCHAR(20) NOT NULL,          -- Month name (January, February, etc.)
    year INTEGER NOT NULL,               -- Year (2025, 2024, etc.)
    s3_key TEXT NOT NULL,                -- S3 path: invoices/AA001001-Jan-2025.pdf
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    -- Validation constraints
    CONSTRAINT valid_month CHECK (month IN (
        'January', 'February', 'March', 'April', 'May', 'June',
        'July', 'August', 'September', 'October', 'November', 'December'
    )),
    CONSTRAINT valid_year CHECK (year >= 2020 AND year <= 2100),
    CONSTRAINT valid_code CHECK (code ~ '^[A-Z]{2}[0-9]{6}$'),  -- AA001001 format
    
    -- Prevent duplicate invoices for same code/month/year
    CONSTRAINT unique_code_month_year UNIQUE(code, month, year)
);

-- STEP 4: Create performance indexes
CREATE INDEX idx_invoices_code ON invoices(code);
CREATE INDEX idx_invoices_year_month ON invoices(year DESC, month);
CREATE INDEX idx_invoices_created_at ON invoices(created_at DESC);

-- STEP 5: Grant permissions (if needed)
-- GRANT SELECT, INSERT ON invoices TO your_app_user;

-- Migration complete
SELECT 'Migration completed successfully. New invoices table ready.' AS status;

-- Verify table structure
SELECT 
    column_name, 
    data_type, 
    character_maximum_length,
    is_nullable
FROM information_schema.columns 
WHERE table_name = 'invoices'
ORDER BY ordinal_position;
