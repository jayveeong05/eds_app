-- Admin Web Panel Database Updates
-- PostgreSQL syntax

-- Add indexes for better performance on users table
CREATE INDEX IF NOT EXISTS idx_users_status ON users(status);
CREATE INDEX IF NOT EXISTS idx_users_role ON users(role);
CREATE INDEX IF NOT EXISTS idx_users_firebase_uid ON users(firebase_uid);

-- Add indexes for promotions
CREATE INDEX IF NOT EXISTS idx_promotions_created ON promotions(created_at DESC);

-- Add login_method column if it doesn't exist (for tracking email/google/apple)
DO $$ 
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.columns 
        WHERE table_name = 'users' AND column_name = 'login_method'
    ) THEN
        ALTER TABLE users ADD COLUMN login_method VARCHAR(20) DEFAULT 'email';
    END IF;
END $$;

-- Create admin activity log table
CREATE TABLE IF NOT EXISTS admin_activity_log (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    admin_user_id UUID NOT NULL REFERENCES users(id),
    action VARCHAR(100) NOT NULL,
    target_type VARCHAR(50),
    target_id UUID,
    details TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Add index for activity log queries
CREATE INDEX IF NOT EXISTS idx_activity_log_admin ON admin_activity_log(admin_user_id);
CREATE INDEX IF NOT EXISTS idx_activity_log_created ON admin_activity_log(created_at DESC);

-- Sample query to manually promote a user to admin
-- UPDATE users SET role = 'admin', status = 'active' WHERE email = 'your-admin-email@example.com';
