-- Tạo bảng lịch sử bảo dưỡng xe
CREATE TABLE IF NOT EXISTS `maintenance_history` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `vehicle_id` int(11) NOT NULL,
  `status` varchar(255) NOT NULL COMMENT 'Tình trạng xe',
  `notes` text COMMENT 'Ghi chú chi tiết',
  `user_id` int(11) NOT NULL COMMENT 'ID người cập nhật',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `vehicle_id` (`vehicle_id`),
  KEY `user_id` (`user_id`),
  KEY `created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Thêm dữ liệu mẫu từ repairNotes cũ (nếu có)
INSERT INTO maintenance_history (vehicle_id, status, notes, user_id, created_at)
SELECT 
  id as vehicle_id,
  CASE 
    WHEN active = 0 THEN 'Trong xưởng'
    WHEN repairNotes IS NOT NULL AND repairNotes != '' THEN repairNotes
    ELSE 'Sẵn sàng'
  END as status,
  COALESCE(repairNotes, '') as notes,
  1 as user_id, -- Giả sử user_id = 1 là admin
  NOW() as created_at
FROM vehicles 
WHERE (active = 0 OR (repairNotes IS NOT NULL AND repairNotes != ''));

-- Thêm cột để liên kết với bảng maintenance_history
ALTER TABLE vehicles ADD COLUMN last_maintenance_id int(11) NULL;
ALTER TABLE vehicles ADD INDEX (last_maintenance_id);

-- Cập nhật last_maintenance_id cho các xe có lịch sử
UPDATE vehicles v 
SET last_maintenance_id = (
  SELECT id FROM maintenance_history mh 
  WHERE mh.vehicle_id = v.id 
  ORDER BY mh.created_at DESC 
  LIMIT 1
);
