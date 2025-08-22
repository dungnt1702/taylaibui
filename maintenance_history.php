<?php
// File này sẽ được include vào index.php khi filter=maintenance
// Không cần HTML head/body tags

// Lấy danh sách xe với tình trạng bảo dưỡng cuối cùng
$query = "
    SELECT 
        v.id,
        v.active,
        v.endAt,
        v.paused,
        COALESCE(mh.status, 'Sẵn sàng') as current_status,
        COALESCE(mh.notes, '') as current_notes,
        COALESCE(mh.created_at, NOW()) as last_updated
    FROM vehicles v
    LEFT JOIN maintenance_history mh ON v.last_maintenance_id = mh.id
    ORDER BY v.id ASC
";

$result = $mysqli->query($query);
$vehicles = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $vehicles[] = $row;
    }
}
?>

<div class="maintenance-container">
    <h1>📋 Lịch sử bảo dưỡng xe</h1>
    
    <table class="maintenance-table">
        <thead>
            <tr>
                <th>ID Xe</th>
                <th>Tên xe</th>
                <th>Tình trạng hiện tại</th>
                <th>Ghi chú</th>
                <th>Cập nhật lần cuối</th>
                <th>Thao tác</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($vehicles as $vehicle): ?>
                <tr>
                    <td><?= htmlspecialchars($vehicle['id']) ?></td>
                    <td><strong>Xe <?= htmlspecialchars($vehicle['id']) ?></strong></td>
                    <td>
                        <?php
                        $statusClass = '';
                        if ($vehicle['active'] == 0) {
                            $statusClass = 'status-workshop';
                            $statusText = 'Trong xưởng';
                        } elseif ($vehicle['current_status'] == 'Sẵn sàng') {
                            $statusClass = 'status-ready';
                            $statusText = 'Sẵn sàng';
                        } else {
                            $statusClass = 'status-repair';
                            $statusText = htmlspecialchars($vehicle['current_status']);
                        }
                        ?>
                        <span class="status-badge <?= $statusClass ?>"><?= $statusText ?></span>
                    </td>
                    <td><?= htmlspecialchars($vehicle['current_notes']) ?></td>
                    <td><?= date('d/m/Y H:i', strtotime($vehicle['last_updated'])) ?></td>
                    <td>
                        <button class="history-btn" onclick="showMaintenanceHistory(<?= $vehicle['id'] ?>)">
                            📖 Xem lịch sử
                        </button>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<!-- Modal lịch sử bảo dưỡng -->
<div id="history-modal" class="modal" style="display: none;">
    <div class="modal-content" style="max-width: 800px;">
        <span class="close" onclick="closeHistoryModal()">&times;</span>
        <h2>📖 Lịch sử bảo dưỡng xe <span id="vehicle-title"></span></h2>
        
        <div id="history-content">
            <!-- Nội dung lịch sử sẽ được load ở đây -->
        </div>
        
        <div class="add-maintenance-form">
            <h3>➕ Thêm tình trạng mới</h3>
            <form id="add-maintenance-form">
                <input type="hidden" id="vehicle-id" name="vehicle_id">
                <div class="form-group">
                    <label for="status">Tình trạng:</label>
                    <input type="text" id="status" name="status" required placeholder="Ví dụ: Đang sửa máy, Thay dầu...">
                </div>
                <div class="form-group">
                    <label for="notes">Ghi chú chi tiết:</label>
                    <textarea id="notes" name="notes" rows="3" placeholder="Mô tả chi tiết tình trạng..."></textarea>
                </div>
                <button type="submit" class="btn-primary">Lưu tình trạng mới</button>
            </form>
        </div>
    </div>
</div>

<script>
    function showMaintenanceHistory(vehicleId) {
        document.getElementById('vehicle-id').value = vehicleId;
        document.getElementById('vehicle-title').textContent = 'Xe ' + vehicleId;
        
        // Load lịch sử bảo dưỡng
        fetch('get_maintenance_history.php?vehicle_id=' + vehicleId)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    document.getElementById('history-content').innerHTML = data.html;
                    document.getElementById('history-modal').style.display = 'block';
                } else {
                    alert('Lỗi: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Có lỗi xảy ra khi tải lịch sử');
            });
    }
    
    function closeHistoryModal() {
        document.getElementById('history-modal').style.display = 'none';
    }
    
    // Xử lý form thêm tình trạng mới
    document.getElementById('add-maintenance-form').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        
        fetch('add_maintenance_status.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Đã thêm tình trạng mới thành công!');
                location.reload(); // Reload trang để cập nhật
            } else {
                alert('Lỗi: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Có lỗi xảy ra khi thêm tình trạng');
        });
    });
    
    // Đóng modal khi click ra ngoài
    window.onclick = function(event) {
        const modal = document.getElementById('history-modal');
        if (event.target == modal) {
            closeHistoryModal();
        }
    }
</script>
