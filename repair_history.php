<?php
// File này sẽ được include vào index.php khi filter=repair
// Không cần HTML head/body tags

// Kiểm tra xem có kết nối database không
if (!isset($mysqli)) {
    echo '<p class="error">Lỗi: Không có kết nối database</p>';
    return;
}

// Lấy toàn bộ dữ liệu sửa chữa từ bảng repair_history
$query = "
    SELECT 
        rh.id,
        rh.vehicle_id,
        rh.repair_type,
        rh.description,
        rh.cost,
        rh.repair_date,
        rh.completed_date,
        rh.status,
        rh.technician,
        rh.created_at,
        u.name as created_by
    FROM repair_history rh
    LEFT JOIN users u ON rh.user_id = u.id
    ORDER BY rh.repair_date DESC, rh.created_at DESC
";

$result = $mysqli->query($query);
if (!$result) {
    echo '<p class="error">Lỗi SQL: ' . $mysqli->error . '</p>';
    return;
}

$repairs = [];
while ($row = $result->fetch_assoc()) {
    $repairs[] = $row;
}

// Debug: hiển thị số lượng bản ghi
// echo '<p>Debug: Tìm thấy ' . count($repairs) . ' bản ghi sửa chữa</p>';
?>

<div class="repair-container">
    <!-- Tiêu đề sẽ được hiển thị bởi JavaScript updatePageTitle() -->
    
    <div class="repair-actions">
        <button class="btn-primary" onclick="showAddRepairModal()">➕ Thêm sửa chữa mới</button>
        <button class="btn-secondary" onclick="exportRepairHistory()">📊 Xuất báo cáo</button>
    </div>
    
    <!-- Phân trang controls -->
    <div class="pagination-controls">
        <div class="pagination-info">
            <span>Hiển thị </span>
            <select id="page-size" onchange="changePageSize()">
                <option value="10">10</option>
                <option value="20">20</option>
                <option value="30">30</option>
            </select>
            <span> dòng mỗi trang</span>
        </div>
        <div class="pagination-nav">
            <button id="prev-page" onclick="previousPage()" disabled>← Trước</button>
            <span id="page-info">Trang 1</span>
            <button id="next-page" onclick="nextPage()">Tiếp →</button>
        </div>
    </div>
    
    <!-- Container riêng cho bảng có thể scroll ngang -->
    <div class="table-container">
        <table class="repair-table">
        <thead>
            <tr>
                <th>Thứ tự</th>
                <th>Xe</th>
                <th>Loại sửa chữa</th>
                <th>Mô tả</th>
                <th>Chi phí</th>
                <th>Ngày sửa</th>
                <th>Ngày hoàn thành</th>
                <th>Trạng thái</th>
                <th>Thợ sửa</th>
                <th>Người tạo</th>
                <th>Ngày tạo</th>
                <th>Thao tác</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($repairs)): ?>
                <tr>
                    <td colspan="12" class="no-data-row">
                        <p class="no-data">Chưa có dữ liệu sửa chữa nào.</p>
                    </td>
                </tr>
            <?php else: ?>
                <?php 
                $counter = 1;
                foreach ($repairs as $repair): 
                ?>
                    <tr>
                        <td data-label="Thứ tự:"><?= $counter ?></td>
                        <td data-label="Xe:"><strong>Xe <?= htmlspecialchars($repair['vehicle_id']) ?></strong></td>
                        <td data-label="Loại sửa chữa:">
                            <span class="repair-type"><?= htmlspecialchars($repair['repair_type']) ?></span>
                        </td>
                        <td data-label="Mô tả:">
                            <div class="repair-description" title="<?= htmlspecialchars($repair['description']) ?>">
                                <?= htmlspecialchars(substr($repair['description'], 0, 50)) ?>
                                <?= strlen($repair['description']) > 50 ? '...' : '' ?>
                            </div>
                        </td>
                        <td data-label="Chi phí:">
                            <?php if ($repair['cost'] > 0): ?>
                                <span class="repair-cost"><?= number_format($repair['cost'], 0, ',', '.') ?> VNĐ</span>
                            <?php else: ?>
                                <span class="no-data">-</span>
                            <?php endif; ?>
                        </td>
                        <td data-label="Ngày sửa:"><?= date('d/m/Y', strtotime($repair['repair_date'])) ?></td>
                        <td data-label="Ngày hoàn thành:">
                            <?php if ($repair['completed_date']): ?>
                                <?= date('d/m/Y', strtotime($repair['completed_date'])) ?>
                            <?php else: ?>
                                <span class="no-data">-</span>
                            <?php endif; ?>
                        </td>
                        <td data-label="Trạng thái:">
                            <?php
                            $statusClass = 'status-' . $repair['status'];
                            $statusText = [
                                'pending' => 'Chờ xử lý',
                                'in_progress' => 'Đang sửa',
                                'completed' => 'Hoàn thành',
                                'cancelled' => 'Đã hủy'
                            ][$repair['status']] ?? $repair['status'];
                            ?>
                            <span class="status-badge <?= $statusClass ?>"><?= $statusText ?></span>
                        </td>
                        <td data-label="Thợ sửa:">
                            <?php if ($repair['technician']): ?>
                                <span class="technician"><?= htmlspecialchars($repair['technician']) ?></span>
                            <?php else: ?>
                                <span class="no-data">-</span>
                            <?php endif; ?>
                        </td>
                        <td data-label="Người tạo:"><?= htmlspecialchars($repair['created_by'] ?? 'N/A') ?></td>
                        <td data-label="Ngày tạo:"><?= date('d/m/Y H:i', strtotime($repair['created_at'])) ?></td>
                        <td data-label="Thao tác:">
                            <button class="edit-btn" onclick="editRepair(<?= $repair['id'] ?>)">
                                ✏️ Sửa
                            </button>
                            <button class="delete-btn" onclick="deleteRepair(<?= $repair['id'] ?>)">
                                🗑️ Xóa
                            </button>
                        </td>
                    </tr>
                <?php 
                $counter++;
                endforeach; 
                ?>
            <?php endif; ?>
        </tbody>
        </table>
    </div>
</div>

<!-- Modal thêm/sửa sửa chữa -->
<div id="repair-modal" class="modal" style="display: none;">
    <div class="modal-content" style="max-width: 700px;">
        <span class="close" onclick="closeRepairModal()">&times;</span>
        <h2 id="repair-modal-title">➕ Thêm sửa chữa mới</h2>
        
        <form id="repair-form">
            <input type="hidden" id="repair-id" name="repair_id">
            <div class="form-row">
                <div class="form-group">
                    <label for="vehicle-select">Chọn xe:</label>
                    <select id="vehicle-select" name="vehicle_id" required>
                        <option value="">-- Chọn xe --</option>
                        <?php for ($i = 1; $i <= 20; $i++): ?>
                            <option value="<?= $i ?>">Xe <?= $i ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="repair-type">Loại sửa chữa:</label>
                    <input type="text" id="repair-type" name="repair_type" required placeholder="Ví dụ: Thay dầu, Sửa máy...">
                </div>
            </div>
            
            <div class="form-group">
                <label for="repair-description">Mô tả chi tiết:</label>
                <textarea id="repair-description" name="description" rows="4" placeholder="Mô tả chi tiết công việc sửa chữa..."></textarea>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="repair-date">Ngày sửa chữa:</label>
                    <input type="date" id="repair-date" name="repair_date" required value="<?= date('Y-m-d') ?>">
                </div>
                <div class="form-group">
                    <label for="repair-cost">Chi phí (VNĐ):</label>
                    <input type="number" id="repair-cost" name="cost" placeholder="0" min="0" step="1000">
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="technician">Thợ sửa chữa:</label>
                    <input type="text" id="technician" name="technician" placeholder="Tên thợ sửa chữa">
                </div>
                <div class="form-group">
                    <label for="repair-status">Trạng thái:</label>
                    <select id="repair-status" name="status" required>
                        <option value="pending">Chờ xử lý</option>
                        <option value="in_progress">Đang sửa</option>
                        <option value="completed">Hoàn thành</option>
                        <option value="cancelled">Đã hủy</option>
                    </select>
                </div>
            </div>
            
            <div class="form-actions">
                <button type="submit" class="btn-primary" id="repair-submit-btn">Lưu sửa chữa</button>
                <button type="button" class="btn-secondary" onclick="closeRepairModal()">Hủy</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal lịch sử sửa chữa -->
<div id="repair-history-modal" class="modal" style="display: none;">
    <div class="modal-content" style="max-width: 900px;">
        <span class="close" onclick="closeRepairHistoryModal()">&times;</span>
        <h2>📖 Lịch sử sửa chữa xe <span id="repair-vehicle-title"></span></h2>
        
        <div id="repair-history-content">
            <!-- Nội dung lịch sử sẽ được load ở đây -->
        </div>
    </div>
</div>

<!-- Modal xác nhận xóa -->
<div id="delete-confirm-modal" class="modal" style="display: none;">
    <div class="modal-content" style="max-width: 400px;">
        <span class="close" onclick="closeDeleteConfirmModal()">&times;</span>
        <h2>🗑️ Xác nhận xóa</h2>
        <p>Bạn có chắc chắn muốn xóa sửa chữa này?</p>
        <p><strong>Hành động này không thể hoàn tác!</strong></p>
        
        <div class="form-actions">
            <button type="button" class="btn-danger" id="delete-confirm-btn">Xóa</button>
            <button type="button" class="btn-secondary" onclick="closeDeleteConfirmModal()">Hủy</button>
        </div>
    </div>
</div>

<!-- Modal thông báo -->
<div id="message-modal" class="modal" style="display: none;">
    <div class="modal-content" style="max-width: 400px;">
        <span class="close" onclick="closeMessageModal()">&times;</span>
        <div class="message-content">
            <div id="message-icon" class="message-icon"></div>
            <p id="message-text"></p>
        </div>
        
        <div class="form-actions">
            <button type="button" class="btn-primary" onclick="closeMessageModal()">Đóng</button>
        </div>
    </div>
</div>

<script>
    function showAddRepairModal() {
        // Reset form và set mode thêm mới
        document.getElementById('repair-form').reset();
        document.getElementById('repair-modal-title').textContent = '➕ Thêm sửa chữa mới';
        document.getElementById('repair-submit-btn').textContent = 'Lưu sửa chữa';
        document.getElementById('repair-id').value = '';
        document.getElementById('repair-modal').style.display = 'block';
    }
    
    function closeRepairModal() {
        document.getElementById('repair-modal').style.display = 'none';
        document.getElementById('repair-form').reset();
    }
    
    function showRepairHistory(vehicleId) {
        document.getElementById('repair-vehicle-title').textContent = 'Xe ' + vehicleId;
        
        // Load lịch sử sửa chữa
        fetch('get_repair_history.php?vehicle_id=' + vehicleId)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    document.getElementById('repair-history-content').innerHTML = data.html;
                    document.getElementById('repair-history-modal').style.display = 'block';
                } else {
                    showErrorModal('Lỗi: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showErrorModal('Có lỗi xảy ra khi tải lịch sử');
            });
    }
    
    function closeRepairHistoryModal() {
        document.getElementById('repair-history-modal').style.display = 'none';
    }
    
    function editRepair(repairId) {
        // Load thông tin sửa chữa để sửa
        fetch('get_repair_by_id.php?id=' + repairId)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const repair = data.repair;
                    
                    // Fill form với dữ liệu hiện tại
                    document.getElementById('repair-id').value = repair.id;
                    document.getElementById('vehicle-select').value = repair.vehicle_id;
                    document.getElementById('repair-type').value = repair.repair_type;
                    document.getElementById('repair-description').value = repair.description;
                    document.getElementById('repair-cost').value = repair.cost;
                    document.getElementById('repair-date').value = repair.repair_date;
                    document.getElementById('technician').value = repair.technician || '';
                    document.getElementById('repair-status').value = repair.status;
                    
                    // Set mode sửa
                    document.getElementById('repair-modal-title').textContent = '✏️ Sửa sửa chữa';
                    document.getElementById('repair-submit-btn').textContent = 'Cập nhật sửa chữa';
                    
                    // Hiển thị modal
                    document.getElementById('repair-modal').style.display = 'block';
                } else {
                    showErrorModal('Lỗi: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showErrorModal('Có lỗi xảy ra khi tải thông tin sửa chữa');
            });
    }
    
    function deleteRepair(repairId) {
        // Hiển thị modal xác nhận xóa
        showDeleteConfirmModal(repairId);
    }
    
    function showDeleteConfirmModal(repairId) {
        const modal = document.getElementById('delete-confirm-modal');
        const confirmBtn = document.getElementById('delete-confirm-btn');
        
        // Set repair ID cho button xác nhận
        confirmBtn.onclick = function() {
            performDeleteRepair(repairId);
        };
        
        modal.style.display = 'block';
    }
    
    function performDeleteRepair(repairId) {
        const formData = new FormData();
        formData.append('repair_id', repairId);
        
        fetch('delete_repair_record.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showSuccessModal('Đã xóa sửa chữa thành công!');
                closeDeleteConfirmModal();
                // Thay vì reload trang, chỉ cập nhật dữ liệu
                loadRepairData();
            } else {
                showErrorModal('Lỗi: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showErrorModal('Có lỗi xảy ra khi xóa sửa chữa');
        });
    }
    
    function closeDeleteConfirmModal() {
        document.getElementById('delete-confirm-modal').style.display = 'none';
    }
    
    function showSuccessModal(message) {
        const modal = document.getElementById('message-modal');
        const messageText = document.getElementById('message-text');
        const messageIcon = document.getElementById('message-icon');
        
        messageText.textContent = message;
        messageIcon.textContent = '✅';
        messageIcon.className = 'message-icon success';
        
        modal.style.display = 'block';
        
        // Tự động đóng sau 3 giây
        setTimeout(() => {
            modal.style.display = 'none';
        }, 3000);
    }
    
    function showErrorModal(message) {
        const modal = document.getElementById('message-modal');
        const messageText = document.getElementById('message-text');
        const messageIcon = document.getElementById('message-icon');
        
        messageText.textContent = message;
        messageIcon.textContent = '❌';
        messageIcon.className = 'message-icon error';
        
        modal.style.display = 'block';
        
        // Tự động đóng sau 5 giây
        setTimeout(() => {
            modal.style.display = 'none';
        }, 5000);
    }
    
    function closeMessageModal() {
        document.getElementById('message-modal').style.display = 'none';
    }
    
    // Phân trang
    let currentPage = 1;
    let pageSize = 10;
    let allRepairData = [];
    
    function changePageSize() {
        pageSize = parseInt(document.getElementById('page-size').value);
        currentPage = 1;
        renderPagination();
        renderRepairTable();
    }
    
    function previousPage() {
        if (currentPage > 1) {
            currentPage--;
            renderPagination();
            renderRepairTable();
        }
    }
    
    function nextPage() {
        const totalPages = Math.ceil(allRepairData.length / pageSize);
        if (currentPage < totalPages) {
            currentPage++;
            renderPagination();
            renderRepairTable();
        }
    }
    
    function renderPagination() {
        const totalPages = Math.ceil(allRepairData.length / pageSize);
        const pageInfo = document.getElementById('page-info');
        const prevBtn = document.getElementById('prev-page');
        const nextBtn = document.getElementById('next-page');
        
        pageInfo.textContent = `Trang ${currentPage} / ${totalPages}`;
        prevBtn.disabled = currentPage <= 1;
        nextBtn.disabled = currentPage >= totalPages;
    }
    
    function renderRepairTable() {
        const startIndex = (currentPage - 1) * pageSize;
        const endIndex = startIndex + pageSize;
        const pageData = allRepairData.slice(startIndex, endIndex);
        
        const tbody = document.querySelector('.repair-table tbody');
        if (!tbody) return;
        
        tbody.innerHTML = '';
        
        if (pageData.length === 0) {
            const row = tbody.insertRow();
            row.className = 'no-data-row';
            const cell = row.insertCell();
            cell.colSpan = 12; /* 12 cột: Thứ tự, Xe, Loại, Mô tả, Chi phí, Ngày sửa, Ngày hoàn thành, Trạng thái, Thợ sửa, Người tạo, Ngày tạo, Thao tác */
            cell.innerHTML = '<div class="no-data">Không có dữ liệu sửa chữa</div>';
            return;
        }
        
        pageData.forEach((repair, index) => {
            const row = tbody.insertRow();
            const counter = startIndex + index + 1;
            row.innerHTML = `
                <td data-label="Thứ tự:">${counter}</td>
                <td data-label="Xe:"><strong>Xe ${repair.vehicle_id}</strong></td>
                <td data-label="Loại sửa chữa:">
                    <span class="repair-type">${repair.repair_type}</span>
                </td>
                <td data-label="Mô tả:">
                    <div class="repair-description" title="${repair.description || ''}">
                        ${repair.description ? (repair.description.length > 50 ? repair.description.substring(0, 50) + '...' : repair.description) : '-'}
                    </div>
                </td>
                <td data-label="Chi phí:">
                    ${repair.cost > 0 ? repair.cost.toLocaleString('vi-VN') + ' VNĐ' : '-'}
                </td>
                <td data-label="Ngày sửa:">${repair.repair_date}</td>
                <td data-label="Ngày hoàn thành:">${repair.completed_date || '-'}</td>
                <td data-label="Trạng thái:">
                    <span class="status-badge status-${repair.status}">${getStatusText(repair.status)}</span>
                </td>
                <td data-label="Thợ sửa:">${repair.technician || '-'}</td>
                <td data-label="Người tạo:">${repair.created_by || 'N/A'}</td>
                <td data-label="Ngày tạo:">${repair.created_at}</td>
                <td data-label="Thao tác:">
                    <button class="edit-btn" onclick="editRepair(${repair.id})">
                        ✏️ Sửa
                    </button>
                    <button class="delete-btn" onclick="deleteRepair(${repair.id})">
                        🗑️ Xóa
                    </button>
                </td>
            `;
        });
    }
    
    function getStatusText(status) {
        const statusMap = {
            'pending': 'Chờ xử lý',
            'in_progress': 'Đang sửa',
            'completed': 'Hoàn thành',
            'cancelled': 'Đã hủy'
        };
        return statusMap[status] || status;
    }
    
    // Load dữ liệu ban đầu
    function loadRepairData() {
        // Giả lập dữ liệu - trong thực tế sẽ gọi API
        fetch('get_all_repairs.php')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    allRepairData = data.repairs;
                    renderPagination();
                    renderRepairTable();
                }
            })
            .catch(error => {
                console.error('Error loading repair data:', error);
            });
    }
    
    // Khởi tạo phân trang khi trang load
    document.addEventListener('DOMContentLoaded', function() {
        // Set page size mặc định
        document.getElementById('page-size').value = '10';
        
        // Chỉ load dữ liệu nếu không có dữ liệu PHP
        const tbody = document.querySelector('.repair-table tbody');
        if (tbody && tbody.children.length <= 1) {
            loadRepairData();
        }
    });
    
    function exportRepairHistory() {
        // TODO: Implement export functionality
        showErrorModal('Tính năng xuất báo cáo sẽ được phát triển sau');
    }
    
    // Xử lý form thêm/sửa sửa chữa
    document.getElementById('repair-form').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        const repairId = document.getElementById('repair-id').value;
        
        // Xác định endpoint dựa trên mode (thêm mới hoặc sửa)
        const endpoint = repairId ? 'update_repair_record.php' : 'add_repair_record.php';
        
        fetch(endpoint, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const message = repairId ? 'Đã cập nhật sửa chữa thành công!' : 'Đã thêm sửa chữa mới thành công!';
                showSuccessModal(message);
                closeRepairModal();
                // Thay vì reload trang, chỉ cập nhật dữ liệu
                loadRepairData();
            } else {
                showErrorModal('Lỗi: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showErrorModal('Có lỗi xảy ra khi xử lý sửa chữa');
        });
    });
    
    // Đóng modal khi click ra ngoài
    window.onclick = function(event) {
        const repairModal = document.getElementById('repair-modal');
        const historyModal = document.getElementById('repair-history-modal');
        const deleteModal = document.getElementById('delete-confirm-modal');
        const messageModal = document.getElementById('message-modal');
        
        if (event.target == repairModal) {
            closeRepairModal();
        }
        if (event.target == historyModal) {
            closeRepairHistoryModal();
        }
        if (event.target == deleteModal) {
            closeDeleteConfirmModal();
        }
        if (event.target == messageModal) {
            closeMessageModal();
        }
    }
</script>
