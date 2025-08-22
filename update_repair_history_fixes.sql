-- =====================================================
-- SQL UPDATE SCRIPT FOR REPAIR HISTORY FIXES
-- Date: 2025-01-20
-- Description: Fixes for repair history table rendering and mobile layout
-- =====================================================

-- Backup existing data (optional but recommended)
-- CREATE TABLE repair_history_backup AS SELECT * FROM repair_history;

-- =====================================================
-- 1. UPDATE REPAIR HISTORY TABLE STRUCTURE
-- =====================================================

-- Ensure the repair_history table has all required columns
ALTER TABLE repair_history 
ADD COLUMN IF NOT EXISTS created_by VARCHAR(100) DEFAULT 'N/A',
ADD COLUMN IF NOT EXISTS updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;

-- Update existing records to have created_by if NULL
UPDATE repair_history 
SET created_by = 'Admin' 
WHERE created_by IS NULL OR created_by = '';

-- =====================================================
-- 2. ADD INDEXES FOR BETTER PERFORMANCE
-- =====================================================

-- Add index for repair_date (used in ORDER BY)
CREATE INDEX IF NOT EXISTS idx_repair_history_repair_date ON repair_history(repair_date);

-- Add index for vehicle_id (used in filtering)
CREATE INDEX IF NOT EXISTS idx_repair_history_vehicle_id ON repair_history(vehicle_id);

-- Add index for status (used in filtering)
CREATE INDEX IF NOT EXISTS idx_repair_history_status ON repair_history(status);

-- Add index for created_at (used in ORDER BY)
CREATE INDEX IF NOT EXISTS idx_repair_history_created_at ON repair_history(created_at);

-- =====================================================
-- 3. UPDATE STATUS VALUES TO ENSURE CONSISTENCY
-- =====================================================

-- Update status values to match the expected format
UPDATE repair_history 
SET status = 'pending' 
WHERE status IN ('chờ xử lý', 'cho xu ly', 'cho_xu_ly');

UPDATE repair_history 
SET status = 'in_progress' 
WHERE status IN ('đang sửa', 'dang sua', 'dang_sua');

UPDATE repair_history 
SET status = 'completed' 
WHERE status IN ('hoàn thành', 'hoan thanh', 'hoan_thanh');

UPDATE repair_history 
SET status = 'cancelled' 
WHERE status IN ('đã hủy', 'da huy', 'da_huy');

-- =====================================================
-- 4. ENSURE DATA INTEGRITY
-- =====================================================

-- Set default values for NULL fields
UPDATE repair_history 
SET cost = 0 
WHERE cost IS NULL;

UPDATE repair_history 
SET technician = '' 
WHERE technician IS NULL;

UPDATE repair_history 
SET description = 'Không có mô tả' 
WHERE description IS NULL OR description = '';

-- =====================================================
-- 5. ADD CONSTRAINTS FOR DATA VALIDATION
-- =====================================================

-- Add check constraint for cost (must be non-negative)
ALTER TABLE repair_history 
ADD CONSTRAINT chk_repair_cost_non_negative 
CHECK (cost >= 0);

-- Add check constraint for status (must be valid values)
ALTER TABLE repair_history 
ADD CONSTRAINT chk_repair_status_valid 
CHECK (status IN ('pending', 'in_progress', 'completed', 'cancelled'));

-- =====================================================
-- 6. UPDATE SAMPLE DATA (if needed)
-- =====================================================

-- Insert sample repair records if table is empty
INSERT IGNORE INTO repair_history 
(vehicle_id, repair_type, description, cost, repair_date, completed_date, status, technician, created_by, created_at) 
VALUES 
(1, 'Thay dầu nhớt', 'Thay dầu nhớt động cơ và lọc dầu', 150000, '2025-01-15', '2025-01-15', 'completed', 'Anh Minh', 'Admin', '2025-01-15 08:00:00'),
(2, 'Sửa phanh', 'Kiểm tra và thay thế má phanh trước', 300000, '2025-01-16', '2025-01-17', 'completed', 'Anh Hùng', 'Admin', '2025-01-16 09:00:00'),
(3, 'Bảo dưỡng định kỳ', 'Kiểm tra tổng thể xe, thay dầu, lọc gió', 200000, '2025-01-18', NULL, 'in_progress', 'Anh Dũng', 'Admin', '2025-01-18 10:00:00'),
(4, 'Sửa điện', 'Thay bình ắc quy và kiểm tra hệ thống điện', 450000, '2025-01-19', NULL, 'pending', '', 'Admin', '2025-01-19 11:00:00'),
(5, 'Thay lốp', 'Thay 4 lốp xe mới', 800000, '2025-01-20', NULL, 'pending', '', 'Admin', '2025-01-20 12:00:00');

-- =====================================================
-- 7. VERIFICATION QUERIES
-- =====================================================

-- Verify table structure
DESCRIBE repair_history;

-- Verify data integrity
SELECT 
    COUNT(*) as total_records,
    COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending_count,
    COUNT(CASE WHEN status = 'in_progress' THEN 1 END) as in_progress_count,
    COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed_count,
    COUNT(CASE WHEN status = 'cancelled' THEN 1 END) as cancelled_count
FROM repair_history;

-- Verify indexes
SHOW INDEX FROM repair_history;

-- =====================================================
-- 8. ROLLBACK SCRIPT (if needed)
-- =====================================================

/*
-- To rollback changes, uncomment and run:

-- Remove constraints
ALTER TABLE repair_history DROP CONSTRAINT chk_repair_cost_non_negative;
ALTER TABLE repair_history DROP CONSTRAINT chk_repair_status_valid;

-- Remove indexes
DROP INDEX idx_repair_history_repair_date ON repair_history;
DROP INDEX idx_repair_history_vehicle_id ON repair_history;
DROP INDEX idx_repair_history_status ON repair_history;
DROP INDEX idx_repair_history_created_at ON repair_history;

-- Remove columns (if added)
ALTER TABLE repair_history DROP COLUMN created_by;
ALTER TABLE repair_history DROP COLUMN updated_at;

-- Restore from backup (if created)
-- DROP TABLE repair_history;
-- RENAME TABLE repair_history_backup TO repair_history;
*/

-- =====================================================
-- END OF UPDATE SCRIPT
-- =====================================================

-- Execute this script on your online server to apply all fixes
-- Make sure to backup your data before running this script
