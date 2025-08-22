-- Tạo bảng lịch sử sửa chữa xe
CREATE TABLE IF NOT EXISTS `repair_history` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `vehicle_id` int(11) NOT NULL,
  `repair_type` varchar(255) NOT NULL COMMENT 'Loại sửa chữa',
  `description` text COMMENT 'Mô tả chi tiết sửa chữa',
  `cost` decimal(10,2) DEFAULT NULL COMMENT 'Chi phí sửa chữa',
  `repair_date` date NOT NULL COMMENT 'Ngày sửa chữa',
  `completed_date` date DEFAULT NULL COMMENT 'Ngày hoàn thành',
  `status` enum('pending','in_progress','completed','cancelled') DEFAULT 'pending' COMMENT 'Trạng thái sửa chữa',
  `technician` varchar(255) DEFAULT NULL COMMENT 'Thợ sửa chữa',
  `user_id` int(11) NOT NULL COMMENT 'ID người tạo',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `vehicle_id` (`vehicle_id`),
  KEY `user_id` (`user_id`),
  KEY `repair_date` (`repair_date`),
  KEY `status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Thêm dữ liệu mẫu từ repairNotes cũ (nếu có)
INSERT INTO repair_history (vehicle_id, repair_type, description, repair_date, status, user_id, created_at)
SELECT 
  id as vehicle_id,
  'Sửa chữa chung' as repair_type,
  COALESCE(repairNotes, 'Không có ghi chú') as description,
  CURDATE() as repair_date,
  CASE 
    WHEN active = 0 THEN 'in_progress'
    ELSE 'completed'
  END as status,
  1 as user_id, -- Giả sử user_id = 1 là admin
  NOW() as created_at
FROM vehicles 
WHERE (active = 0 OR (repairNotes IS NOT NULL AND repairNotes != ''));

-- Thêm cột để liên kết với bảng repair_history
ALTER TABLE vehicles ADD COLUMN last_repair_id int(11) NULL;
ALTER TABLE vehicles ADD INDEX (last_repair_id);

-- Cập nhật last_repair_id cho các xe có lịch sử sửa chữa
UPDATE vehicles v 
SET last_repair_id = (
  SELECT id FROM repair_history rh 
  WHERE rh.vehicle_id = v.id 
  ORDER BY rh.repair_date DESC, rh.created_at DESC
  LIMIT 1
);
