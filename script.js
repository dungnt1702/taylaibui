// TAY LÁI BỤI - Trình quản lý xe bằng MySQL
// Tệp này thay thế phiên bản trước đây sử dụng Firebase. Tất cả trạng thái
// được lưu trữ trong cơ sở dữ liệu MySQL thông qua các API PHP. Ngoài ra, chúng
// ta bổ sung tính năng chọn nhiều xe để khởi động đồng hồ hoặc đưa vào cung đường.

// Kích hoạt speech synthesis sau lần bấm đầu tiên
let speechEnabled = false;
document.addEventListener('click', () => { speechEnabled = true; }, { once: true });

// Phát âm một thông báo nếu người dùng đã kích hoạt âm thanh
function speak(text) {
  if (!speechEnabled) return;
  const utter = new SpeechSynthesisUtterance(text);
  utter.lang = 'vi-VN';
  speechSynthesis.speak(utter);
}

// Định dạng số giây thành chuỗi mm:ss
function formatTime(seconds) {
  // Ensure seconds is a finite non-negative number. If it is not, default to 0.
  if (!Number.isFinite(seconds) || seconds < 0) seconds = 0;
  const m = Math.floor(seconds / 60);
  const s = Math.floor(seconds % 60);
  return m.toString().padStart(2, '0') + ':' + s.toString().padStart(2, '0');
}

// Định dạng timestamp thành chuỗi HH:MM:SS DD/MM/YYYY
function formatDateTime(timestamp) {
  if (!timestamp) return '';
  const date = new Date(timestamp);
  const day = String(date.getDate()).padStart(2, '0');
  const month = String(date.getMonth() + 1).padStart(2, '0');
  const year = date.getFullYear();
  const hours = String(date.getHours()).padStart(2, '0');
  const minutes = String(date.getMinutes()).padStart(2, '0');
  const seconds = String(date.getSeconds()).padStart(2, '0');
  return `${hours}:${minutes}:${seconds} ${day}/${month}/${year}`;
}

// Biến lưu trạng thái lọc hiện tại
let currentFilter = 'all';
// Dữ liệu xe được tải từ máy chủ
let vehicleData = {};
// Tổng số xe (cố định 22)
const vehicles = 22;
// Cờ cảnh báo để tránh lặp lại thông báo âm thanh
const warningFlags = {};
// Danh sách xe được chọn trong chế độ Khách đoàn
let groupSelection = [];

// Trạng thái thu gọn (ẩn) thông tin của từng xe cho chế độ mobile. Khi true, chi tiết sẽ ẩn.
const collapsedVehicles = {};

// ID của xe đang được chỉnh sửa tình trạng trong modal
let editingNotesId = null;
// Flag to indicate if the notes modal is being used to send a car into workshop
let sendingToWorkshop = false;

// Cập nhật trạng thái xe vào cơ sở dữ liệu MySQL thông qua save_vehicle_status.php.
// Hàm này gộp dữ liệu hiện tại với các trường được cập nhật để đảm bảo REPLACE INTO
// không ghi đè các trường khác ngoài những trường cần thay đổi.
function updateVehicleStatus(id, updates) {
  const current = vehicleData[id] || {};
  const payload = {
    id: id,
    active: updates.active !== undefined ? (updates.active ? 1 : 0) : (current.active ? 1 : 0),
    endAt: updates.endAt !== undefined ? (updates.endAt || null) : (current.endAt || null),
    paused: updates.paused !== undefined ? (updates.paused ? 1 : 0) : (current.paused ? 1 : 0),
    remaining: updates.remaining !== undefined ? (updates.remaining || null) : (current.remaining || null),
    minutes: updates.minutes !== undefined ? (updates.minutes || null) : (current.minutes || null),
    notifiedEnd: updates.notifiedEnd !== undefined ? (updates.notifiedEnd ? 1 : 0) : (current.notifiedEnd ? 1 : 0),
    // routeNumber và routeStartAt: sử dụng 0 để đại diện cho null trong DB
    routeNumber: updates.routeNumber !== undefined ? updates.routeNumber : (current.routeNumber || 0),
    routeStartAt: updates.routeStartAt !== undefined ? (updates.routeStartAt || 0) : (current.routeStartAt || 0)
    ,
    // ghi chú tình trạng xe
    repairNotes: updates.repairNotes !== undefined ? updates.repairNotes : (current.repairNotes || null)
  };
  fetch('save_vehicle_status.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(payload)
  }).catch((err) => {
    console.error('Error updating vehicle status', err);
  });
}

// Tải dữ liệu xe từ máy chủ và cập nhật state cục bộ
function loadVehicleData() {
  fetch('get_vehicles.php')
    .then(res => res.json())
    .then(data => {
      for (let i = 1; i <= vehicles; i++) {
        let v = data[i];
        if (v) {
          // Chuyển đổi các trường về kiểu dữ liệu thích hợp
          if (v.endAt !== null && v.endAt !== undefined) v.endAt = Number(v.endAt);
          if (v.remaining !== null && v.remaining !== undefined) v.remaining = Number(v.remaining);
          if (v.routeStartAt !== null && v.routeStartAt !== undefined) v.routeStartAt = Number(v.routeStartAt);
          if (v.routeNumber !== null && v.routeNumber !== undefined) v.routeNumber = Number(v.routeNumber);
          if (v.minutes !== null && v.minutes !== undefined) v.minutes = Number(v.minutes);
          if (v.active !== null && v.active !== undefined) v.active = v.active ? true : false;
          if (v.paused !== null && v.paused !== undefined) v.paused = v.paused ? true : false;
          if (v.notifiedEnd !== null && v.notifiedEnd !== undefined) v.notifiedEnd = v.notifiedEnd ? true : false;
        }
        vehicleData[i] = v || {};
        if (!warningFlags[i]) {
          warningFlags[i] = { warned5: false, warned1: false, notified: false };
        }
      }
      renderVehicles();
    })
    .catch(err => {
      console.error('Error loading vehicle data', err);
    });
}

// Định kỳ tải lại dữ liệu (trừ khi ở tab chọn nhiều để tránh mất lựa chọn)
function periodicRefresh() {
  setInterval(() => {
    if (currentFilter !== 'group' && currentFilter !== 'maintenance' && currentFilter !== 'repair' && currentFilter !== 'user') {
      loadVehicleData();
    }
  }, 5000);
}

// Thiết lập tab lọc
function setFilter(filter) {
  // Cập nhật bộ lọc và cập nhật URL mà không tải lại trang
  currentFilter = filter;
  const url = new URL(window.location);
  url.searchParams.set('filter', filter);
  history.pushState({}, '', url);
  
  // Hiển thị hoặc ẩn điều khiển Khách đoàn
  const groupControls = document.getElementById('group-controls');
  if (groupControls) {
    groupControls.style.display = (filter === 'group') ? 'flex' : 'none';
  }
  
  // Xử lý filter user
  if (filter === 'user') {
    // loadUserContent() sẽ được gọi từ setFilter
    updatePageTitle(); // Cập nhật tiêu đề
    return;
  }
  
  // Xử lý filter maintenance
  if (filter === 'maintenance') {
    const maintenanceContent = document.getElementById('maintenance-content');
    const vehicleList = document.getElementById('vehicle-list');
    if (maintenanceContent) maintenanceContent.style.display = 'block';
    if (vehicleList) vehicleList.style.display = 'none';
    updatePageTitle(); // Cập nhật tiêu đề
    return;
  }
  
  // Xử lý filter repair
  if (filter === 'repair') {
    const repairContent = document.getElementById('repair-content');
    const vehicleList = document.getElementById('vehicle-list');
    if (repairContent) repairContent.style.display = 'block';
    if (vehicleList) vehicleList.style.display = 'none';
    updatePageTitle(); // Cập nhật tiêu đề
    return;
  }
  
  // Khi rời tab Khách đoàn, xóa lựa chọn
  if (filter !== 'group') {
    groupSelection = [];
  }
  
  // Đóng menu trên thiết bị di động nếu đang mở
  const nav = document.getElementById('nav-menu');
  if (nav && nav.classList.contains('open')) {
    nav.classList.remove('open');
  }
  
  // Ẩn user content, maintenance content, repair content và hiển thị vehicle list
  const userContent = document.getElementById('user-content');
  const maintenanceContent = document.getElementById('maintenance-content');
  const repairContent = document.getElementById('repair-content');
  const vehicleList = document.getElementById('vehicle-list');
  if (userContent) userContent.style.display = 'none';
  if (maintenanceContent) maintenanceContent.style.display = 'none';
  if (repairContent) repairContent.style.display = 'none';
  if (vehicleList) vehicleList.style.display = 'block';
  
  // Chỉ render vehicles cho các filter thông thường
  if (filter !== 'maintenance' && filter !== 'repair' && filter !== 'user') {
    renderVehicles();
    updatePageTitle(); // Chỉ cập nhật tiêu đề cho filter thông thường
  }
  // Không cần gọi updatePageTitle() ở đây vì đã gọi trong các filter đặc biệt
}

// Render danh sách xe dựa trên dữ liệu và bộ lọc hiện tại
function renderVehicles() {
  const container = document.getElementById('vehicle-list');
  if (!container) return;
  container.innerHTML = '';
  // Nếu là tab 'route', hiển thị danh sách theo từng cung đường
  if (currentFilter === 'route') {
    // Nhóm xe theo routeNumber
    const groups = {};
    for (let i = 1; i <= vehicles; i++) {
      const data = vehicleData[i] || {};
      const routeNum = data.routeNumber;
      if (routeNum && routeNum !== 0) {
        if (!groups[routeNum]) groups[routeNum] = [];
        groups[routeNum].push({ id: i, data });
      }
    }
    // Sắp xếp số cung đường tăng dần
    const routeNumbers = Object.keys(groups).sort((a, b) => parseInt(a) - parseInt(b));
    if (routeNumbers.length === 0) {
      const p = document.createElement('p');
      p.className = 'no-data';
      p.textContent = '--Không có xe nào--';
      container.appendChild(p);
    } else {
      routeNumbers.forEach(route => {
        const groupDiv = document.createElement('div');
        groupDiv.className = 'route-group';
        // Header với nút reset
        const headerDiv = document.createElement('div');
        headerDiv.className = 'route-group-header';
        headerDiv.innerHTML = `<h2>Cung đường ${route}</h2><button onclick="resetRouteGroup(${route})">Xe về bãi</button>`;
        groupDiv.appendChild(headerDiv);
        // Bảng danh sách xe trong nhóm
        const table = document.createElement('table');
        table.className = 'route-table';
        const thead = document.createElement('thead');
        thead.innerHTML = '<tr><th>Số xe</th><th>Thời gian bắt đầu</th></tr>';
        table.appendChild(thead);
        const tbody = document.createElement('tbody');
        groups[route].forEach(item => {
          const tr = document.createElement('tr');
          const tdId = document.createElement('td');
          tdId.textContent = `Xe ${item.id}`;
          const tdStart = document.createElement('td');
          tdStart.textContent = formatDateTime(item.data.routeStartAt);
          tr.appendChild(tdId);
          tr.appendChild(tdStart);
          tbody.appendChild(tr);
        });
        table.appendChild(tbody);
        groupDiv.appendChild(table);
        container.appendChild(groupDiv);
      });
    }
    return;
  }
  // Nếu là tab 'inactive', hiển thị bảng danh sách xe trong xưởng với tình trạng
  if (currentFilter === 'inactive') {
    // Xây dựng bảng
    const table = document.createElement('table');
    table.className = 'repair-table';
    const thead = document.createElement('thead');
    thead.innerHTML = '<tr><th>Số xe</th><th>Ghi chú hỏng hóc</th><th>Lần sửa chữa cuối</th><th>Hành động</th></tr>';
    table.appendChild(thead);
    const tbody = document.createElement('tbody');
    let hasRow = false;
    for (let i = 1; i <= vehicles; i++) {
      const data = vehicleData[i] || {};
      // Chỉ hiển thị xe không hoạt động (đang trong xưởng)
      if (data.active !== false) continue;
      hasRow = true;
      const tr = document.createElement('tr');
      const tdId = document.createElement('td');
      tdId.textContent = `Xe ${i}`;
      tdId.setAttribute('data-label', 'Số xe:');
      const tdStatus = document.createElement('td');
      tdStatus.textContent = data.repairNotes || '';
      tdStatus.setAttribute('data-label', 'Ghi chú hỏng hóc:');
      const tdLastRepair = document.createElement('td');
      // Chuyển đổi trạng thái sửa chữa thành text tiếng Việt
      const repairStatusText = {
        'pending': 'Chờ xử lý',
        'in_progress': 'Đang sửa',
        'completed': 'Hoàn thành',
        'cancelled': 'Đã hủy'
      }[data.last_repair_status] || data.last_repair_status || 'Chưa có';
      tdLastRepair.textContent = repairStatusText;
      tdLastRepair.setAttribute('data-label', 'Lần sửa chữa cuối:');
      const tdAction = document.createElement('td');
      tdAction.innerHTML = `
        <button class="edit-notes-btn" onclick="showNotesEditor(${i})">✏️</button>
        <button class="repair-history-btn" onclick="showRepairHistory(${i})" title="Xem lịch sử sửa chữa">🔧</button>
      `;
      tdAction.setAttribute('data-label', 'Hành động:');
      tr.appendChild(tdId);
      tr.appendChild(tdStatus);
      tr.appendChild(tdLastRepair);
      tr.appendChild(tdAction);
      tbody.appendChild(tr);
    }
    table.appendChild(tbody);
    if (hasRow) {
      // Bọc bảng trong container để có thể scroll ngang trên mobile
      const tableContainer = document.createElement('div');
      tableContainer.className = 'repair-table-container';
      tableContainer.appendChild(table);
      container.appendChild(tableContainer);
    } else {
      const p = document.createElement('p');
      p.className = 'no-data';
      p.textContent = '--Không có xe nào--';
      container.appendChild(p);
    }
    return;
  }
  // Trường hợp khác: hiển thị từng xe dựa trên bộ lọc, có sắp xếp theo thứ tự ưu tiên
  const list = [];
  for (let i = 1; i <= vehicles; i++) {
    const data = vehicleData[i] || {};
    const onRoute = data.routeNumber !== undefined && data.routeNumber !== null && data.routeNumber !== 0;
    const secondsLeft = (!onRoute && data.endAt) ? Math.floor((Number(data.endAt) - Date.now()) / 1000) : 0;
    // Skip vehicles in route for these tabs
    if (currentFilter !== 'route' && onRoute) continue;
    // Filtering conditions
    if (currentFilter === 'group') {
      if (!(data.active === true && !onRoute && !data.endAt && !data.paused)) continue;
    } else if (currentFilter === 'inactive') {
      if (data.active !== false) continue;
    } else if (currentFilter === 'active') {
      if (data.active !== true || onRoute) continue;
    } else if (currentFilter === 'running') {
      if (!(data.endAt && !data.paused && secondsLeft > 0)) continue;
    } else if (currentFilter === 'waiting') {
      if (!(data.endAt == null && data.active === true)) continue;
    } else if (currentFilter === 'expired') {
      if (!(data.endAt && secondsLeft <= 0)) continue;
    } else if (currentFilter === 'paused') {
      if (!data.paused) continue;
    }
    list.push({ id: i, data: data, secondsLeft: secondsLeft, onRoute: onRoute });
  }
  // Sort items based on filter
  if (currentFilter === 'running') {
    // For running tab, sort by remaining time (ascending)
    list.sort((a, b) => a.secondsLeft - b.secondsLeft);
  } else if (currentFilter === 'all') {
    // Custom order: expired -> running (ascending secondsLeft) -> paused -> waiting -> inactive
    const groupOrder = (item) => {
      const d = item.data;
      if (d.active === false) return 4; // inactive (xe trong xưởng)
      if (d.paused) return 2; // paused
      if (d.endAt) {
        return item.secondsLeft <= 0 ? 0 : 1; // expired:0, running:1
      }
      return 3; // waiting
    };
    list.sort((a, b) => {
      const orderA = groupOrder(a);
      const orderB = groupOrder(b);
      if (orderA !== orderB) return orderA - orderB;
      // If both are running, sort by secondsLeft ascending
      if (orderA === 1) return a.secondsLeft - b.secondsLeft;
      return 0;
    });
  }
  // Render sorted items
  list.forEach(item => {
    const i = item.id;
    const data = item.data;
    const secondsLeft = item.secondsLeft;
    const onRoute = item.onRoute;
    // For group mode, render table format
    if (currentFilter === 'group') {
      // Create table header if not exists
      if (!document.querySelector('.group-table')) {
        const tableHeader = document.createElement('div');
        tableHeader.className = 'group-table-header';
        tableHeader.innerHTML = `
          <div class="group-table">
            <div class="group-table-row header">
              <div class="group-table-cell checkbox-header">
                <input type="checkbox" id="select-all" onchange="toggleSelectAll()">
              </div>
              <div class="group-table-cell">Tên xe</div>
              <div class="group-table-cell">Trạng thái</div>
            </div>
          </div>
        `;
        container.appendChild(tableHeader);
      }
      
      const tableRow = document.createElement('div');
      tableRow.className = 'group-table-row';
      const selected = groupSelection.includes(i);
      if (selected) tableRow.classList.add('selected');
      
      // Determine vehicle status
      let statusText = '';
      let statusClass = '';
      
      if (data.active === false) {
        statusText = 'Trong xưởng';
        statusClass = 'status-workshop';
      } else if (data.endAt) {
        const secondsLeft = Math.floor((data.endAt - Date.now()) / 1000);
        if (data.paused) {
          statusText = 'Tạm hoãn';
          statusClass = 'status-paused';
        } else if (secondsLeft <= 0) {
          statusText = 'Hết giờ';
          statusClass = 'status-expired';
        } else if (secondsLeft <= 60) {
          statusText = 'Sắp hết giờ';
          statusClass = 'status-warning';
        } else {
          statusText = 'Đang chạy';
          statusClass = 'status-running';
        }
      } else if (data.maintenance_status && data.maintenance_status.trim()) {
        statusText = data.maintenance_status;
        statusClass = 'status-notes';
      } else {
        statusText = 'Sẵn sàng';
        statusClass = 'status-ready';
      }
      
      tableRow.innerHTML = `
        <div class="group-table-cell checkbox-cell">
          <input type="checkbox" class="select-checkbox" ${selected ? 'checked' : ''} onchange="toggleSelection(${i})">
        </div>
        <div class="group-table-cell vehicle-name">Xe ${i}</div>
        <div class="group-table-cell vehicle-status ${statusClass}">${statusText}</div>
      `;
      
      // Find the table and append row
      const table = document.querySelector('.group-table');
      if (table) {
        table.appendChild(tableRow);
      }
      return;
    }
    // Create card for normal mode
    const div = document.createElement('div');
    div.className = 'vehicle';
    div.setAttribute('data-vehicle-id', i); // Thêm data attribute để dễ tìm
    if (data.active === false) div.classList.add('inactive');
    if (!onRoute) {
      if (secondsLeft <= 60 && secondsLeft > 0) div.classList.add('warning');
      if (secondsLeft <= 0 && data.endAt) div.classList.add('expired');
      if (data.paused) div.classList.add('paused');
      if (data.endAt && !data.paused && secondsLeft > 0) div.classList.add('running');
    } else {
      div.classList.add('route');
    }
    // Determine timer display
    let timerContent;
    if (onRoute) {
      timerContent = `Cung đường ${data.routeNumber}<br><span class="start-time">Bắt đầu: ${formatDateTime(data.routeStartAt)}</span>`;
    } else {
      if (data.paused) {
        timerContent = 'Tạm hoãn';
      } else if (data.endAt) {
        timerContent = formatTime(secondsLeft);
      } else {
        timerContent = '00:00';
      }
    }
    // Controls HTML
    let controlsHtml = '';
    if (!onRoute) {
      if (!data.endAt) {
        // Xe chưa chạy: hiển thị 3 nút trên cùng 1 hàng
        controlsHtml += '<div class="controls-row">' +
          `<button onclick="startTimer(${i}, 45)" class="btn-45" id="btn45-${i}">Chạy 45p</button>` +
          `<button onclick="startTimer(${i}, 30)" class="btn-30" id="btn30-${i}">Chạy 30p</button>` +
          // Nếu là xe trong xưởng (inactive) -> Xuất xưởng; ngược lại -> Về xưởng
          (data.active === false ?
            `<button onclick="toggleVehicle(${i})" class="toggle-btn">Xuất xưởng</button>` :
            `<button onclick="showWorkshopModal(${i})" class="toggle-btn">Về xưởng</button>`
          ) +
          '</div>';
      } else {
        // Xe có đồng hồ: phân loại theo trạng thái hiện tại
        const secs = Math.floor((Number(data.endAt) - Date.now()) / 1000);
        const isExpired = (data.endAt && secs <= 0);
        const isPaused = data.paused;
        const isRunning = (data.endAt && secs > 0 && !isPaused);
        if (isExpired) {
          // Hết giờ: chỉ hiển thị Thêm 10p và Về bãi
          controlsHtml += '<div class="controls-row">' +
            `<button onclick="addTime(${i}, 10)" class="btn-add">Thêm 10p</button>` +
            `<button onclick="resetTimer(${i})" class="btn-reset">Về bãi</button>` +
            '</div>';
        } else if (isRunning) {
          // Đang chạy: Thêm 10p, Tạm hoãn và Về bãi trên cùng một hàng
          controlsHtml += '<div class="controls-row">' +
            `<button onclick="addTime(${i}, 10)" class="btn-add">Thêm 10p</button>` +
            `<button onclick="pauseTimer(${i})" id="pause-${i}">Tạm hoãn</button>` +
            `<button onclick="resetTimer(${i})" class="btn-reset">Về bãi</button>` +
            '</div>';
        } else if (isPaused) {
          // Tạm hoãn: chỉ hiển thị Tiếp tục và Về bãi trên cùng một hàng
          controlsHtml += '<div class="controls-row">' +
            `<button onclick="resumeTimer(${i})" id="resume-${i}" class="btn-resume">Tiếp tục</button>` +
            `<button onclick="resetTimer(${i})" class="btn-reset">Về bãi</button>` +
            '</div>';
        }
      }
    } else {
      controlsHtml += '<div class="controls-row">' +
        `<button onclick="changeRoute(${i}, 0)" class="btn-reset">Về bãi</button>` +
        '</div>';
    }
    // Determine arrow and collapsed state
    let showArrow = (currentFilter === 'all' || currentFilter === 'running' || currentFilter === 'waiting');
    // Include arrow for active vehicles tab as well
    if (currentFilter === 'active') {
      showArrow = true;
    }
    let collapsed = false;
    if (collapsedVehicles[i] !== undefined) {
      collapsed = collapsedVehicles[i];
    } else if (data.active === true && !data.endAt && !data.paused && !onRoute) {
      collapsed = true;
    }
    const arrowClass = collapsed ? 'collapsed' : 'expanded';
    const detailsDisplay = collapsed ? 'none' : 'block';
    // Build arrow span separately; click handling will be attached to the entire header
    const arrowSpan = showArrow ? `<span class="arrow ${arrowClass}"></span>` : '';
    const h3Attr = showArrow ? ` onclick="toggleDetails(${i})"` : '';
    div.innerHTML = `
      <h3${h3Attr}>${arrowSpan} Xe ${i}</h3>
      <div class="details" style="display:${detailsDisplay};">
        <div class="timer" id="timer-${i}">${timerContent}</div>
        <div class="controls">
          ${controlsHtml}
        </div>
      </div>
    `;
    // Add collapsed class to adjust padding when details are hidden
    if (collapsed) {
      div.classList.add('collapsed');
    }
    container.appendChild(div);
  });
  // If no items in list, show message
  if (list.length === 0) {
    const p = document.createElement('p');
    p.className = 'no-data';
    p.textContent = '--Không có xe nào--';
    container.appendChild(p);
  }
}

// Toggle select all vehicles in group mode
function toggleSelectAll() {
  const selectAllCheckbox = document.getElementById('select-all');
  const checkboxes = document.querySelectorAll('.select-checkbox');
  
  if (selectAllCheckbox.checked) {
    // Select all vehicles
    checkboxes.forEach(checkbox => {
      const vehicleId = parseInt(checkbox.getAttribute('onchange').match(/\d+/)[0]);
      if (!groupSelection.includes(vehicleId)) {
        groupSelection.push(vehicleId);
      }
      checkbox.checked = true;
    });
  } else {
    // Deselect all vehicles
    checkboxes.forEach(checkbox => {
      const vehicleId = parseInt(checkbox.getAttribute('onchange').match(/\d+/)[0]);
      const idx = groupSelection.indexOf(vehicleId);
      if (idx > -1) {
        groupSelection.splice(idx, 1);
      }
      checkbox.checked = false;
    });
  }
  
  // Update visual selection
  document.querySelectorAll('.group-table-row').forEach(row => {
    if (row.classList.contains('header')) return;
    const checkbox = row.querySelector('.select-checkbox');
    if (checkbox.checked) {
      row.classList.add('selected');
    } else {
      row.classList.remove('selected');
    }
  });
}

// Bật/tắt xe
function toggleVehicle(id) {
  const current = vehicleData[id] || {};
  const newActive = !(current.active === false);
  vehicleData[id] = Object.assign({}, current, { active: !newActive });
  updateVehicleStatus(id, { active: !newActive });
  renderVehicles();
}

// Bắt đầu bộ đếm cho một xe
function startTimer(id, minutes) {
  const endAt = Date.now() + minutes * 60000;
  const current = vehicleData[id] || {};
  vehicleData[id] = Object.assign({}, current, {
    endAt: endAt,
    active: true,
    minutes: minutes,
    paused: false,
    remaining: null,
    notifiedEnd: false,
    routeNumber: 0,
    routeStartAt: null
  });
  warningFlags[id] = { warned5: false, warned1: false, notified: false };
  updateVehicleStatus(id, {
    active: true,
    endAt: endAt,
    paused: false,
    remaining: null,
    minutes: minutes,
    notifiedEnd: false,
    routeNumber: 0,
    routeStartAt: 0
  });
  speak(`Xe số ${id} bắt đầu lượt ${minutes} phút`);
  renderVehicles();
}

// Tạm hoãn bộ đếm
function pauseTimer(id) {
  const data = vehicleData[id];
  if (data && data.endAt) {
    const remaining = Math.floor((data.endAt - Date.now()) / 1000);
    vehicleData[id] = Object.assign({}, data, { paused: true, remaining: remaining });
    updateVehicleStatus(id, { paused: true, remaining: remaining });
    renderVehicles();
  }
}

// Tiếp tục bộ đếm sau khi tạm hoãn
function resumeTimer(id) {
  const data = vehicleData[id];
  if (data && data.remaining) {
    const endAt = Date.now() + data.remaining * 1000;
    vehicleData[id] = Object.assign({}, data, { paused: false, endAt: endAt, remaining: null });
    updateVehicleStatus(id, { paused: false, endAt: endAt, remaining: null });
    warningFlags[id] = { warned5: false, warned1: false, notified: false };
    renderVehicles();
  }
}

// Đặt lại bộ đếm
function resetTimer(id) {
  const current = vehicleData[id] || {};
  vehicleData[id] = Object.assign({}, current, {
    endAt: null,
    paused: false,
    remaining: null,
    minutes: null,
    notifiedEnd: false
  });
  warningFlags[id] = { warned5: false, warned1: false, notified: false };
  updateVehicleStatus(id, { endAt: null, paused: false, remaining: null, minutes: null, notifiedEnd: false });
  renderVehicles();
}

// Thay đổi cung đường cho xe
function changeRoute(id, route) {
  const routeNum = parseInt(route, 10);
  if (!route || routeNum < 1 || routeNum > 10 || isNaN(routeNum)) {
    // Xóa thông tin cung đường
    const current = vehicleData[id] || {};
    vehicleData[id] = Object.assign({}, current, { routeNumber: null, routeStartAt: null });
    updateVehicleStatus(id, { routeNumber: 0, routeStartAt: 0 });
  } else {
    // Cập nhật cung đường và reset timer
    const startAt = Date.now();
    const current = vehicleData[id] || {};
    vehicleData[id] = Object.assign({}, current, {
      routeNumber: routeNum,
      routeStartAt: startAt,
      endAt: null,
      paused: false,
      remaining: null,
      minutes: null,
      notifiedEnd: false
    });
    warningFlags[id] = { warned5: false, warned1: false, notified: false };
    updateVehicleStatus(id, {
      routeNumber: routeNum,
      routeStartAt: startAt,
      endAt: null,
      paused: false,
      remaining: null,
      minutes: null,
      notifiedEnd: false
    });
  }
  renderVehicles();
}

// Hàm được gọi từ phía PHP để tương thích
function setRoute(id, value) {
  changeRoute(id, value);
}

// Bật xe (sử dụng trong PHP khi xe không active)
function startVehicle(id) {
  if (vehicleData[id] && vehicleData[id].active === false) {
    toggleVehicle(id);
  }
}

// Tắt xe
function stopVehicle(id) {
  const current = vehicleData[id] || {};
  vehicleData[id] = Object.assign({}, current, { active: false });
  updateVehicleStatus(id, { active: false });
  renderVehicles();
}

// Cộng thêm thời gian cho xe đang chạy
function addTime(id, minutes) {
  const data = vehicleData[id];
  // Nếu không có dữ liệu hoặc không có endAt hợp lệ, khởi động hẹn giờ mới
  if (!data || !data.endAt) {
    startTimer(id, minutes);
    return;
  }
  // Lấy giá trị endAt hiện tại (ensure number)
  const currentEnd = Number(data.endAt);
  const now = Date.now();
  // Nếu thời gian kết thúc còn ở tương lai thì cộng thêm; nếu đã hết giờ thì bắt đầu từ bây giờ
  const baseTime = (currentEnd > now) ? currentEnd : now;
  const newEndAt = baseTime + minutes * 60000;
  const newMinutes = (Number(data.minutes) || 0) + minutes;
  vehicleData[id] = Object.assign({}, data, {
    endAt: newEndAt,
    minutes: newMinutes,
    paused: false,
    remaining: null
  });
  updateVehicleStatus(id, {
    endAt: newEndAt,
    minutes: newMinutes,
    paused: false,
    remaining: null
  });
  // Đặt lại cờ cảnh báo để tiếp tục phát thông báo đúng lúc
  warningFlags[id] = { warned5: false, warned1: false, notified: false };
  renderVehicles();
}

// Cập nhật giao diện đồng hồ mỗi giây và phát cảnh báo
function updateTimers() {
  setInterval(() => {
    for (let i = 1; i <= vehicles; i++) {
      const data = vehicleData[i];
      if (!data) continue;
      // Không cập nhật khi tạm hoãn hoặc không hẹn giờ hoặc đang ở cung đường
      if (data.paused || !data.endAt) continue;
      if (data.routeNumber !== undefined && data.routeNumber !== null && data.routeNumber !== 0) continue;
      const secondsLeft = Math.floor((Number(data.endAt) - Date.now()) / 1000);
      const display = document.getElementById(`timer-${i}`);
      if (!display) continue;
      if (secondsLeft <= 0) {
        display.textContent = 'Hết giờ';
        if (!warningFlags[i].notified && data.minutes) {
          speak(`Xe số ${i} đã hết lượt ${data.minutes} phút`);
          warningFlags[i].notified = true;
          updateVehicleStatus(i, { notifiedEnd: true });
        }
      } else {
        display.textContent = formatTime(secondsLeft);
        if (secondsLeft === 300 && !warningFlags[i].warned5) {
          speak(`Xe số ${i} còn 5 phút`);
          warningFlags[i].warned5 = true;
        }
        if (secondsLeft === 60 && !warningFlags[i].warned1) {
          speak(`Xe số ${i} còn 1 phút`);
          warningFlags[i].warned1 = true;
        }
      }
    }
  }, 1000);
}

// Cập nhật tiêu đề trang dựa trên bộ lọc
function updatePageTitle() {
  const titleEl = document.getElementById('page-title');
  if (!titleEl) return;
            const titles = {
        all: 'Tất cả xe',
        inactive: 'Xe trong xưởng',
        active: 'Xe ngoài bãi',
        running: 'Xe đang chạy',
        waiting: 'Xe đang chờ',
        expired: 'Xe hết giờ',
        paused: 'Xe tạm dừng',
        route: 'Xe cung đường',
        group: 'Khách đoàn',
        user: 'Quản lý người dùng',
        maintenance: 'Lịch sử bảo dưỡng xe',
        repair: 'Lịch sử sửa chữa xe'
      };
  titleEl.textContent = titles[currentFilter] || '';
}

// Chọn/bỏ chọn một xe trong chế độ chọn nhiều
function toggleSelection(id) {
  const idx = groupSelection.indexOf(id);
  if (idx >= 0) {
    groupSelection.splice(idx, 1);
  } else {
    groupSelection.push(id);
  }
  renderVehicles();
}

// Bắt đầu đếm giờ hàng loạt cho các xe đã chọn trong tab Khách đoàn
function startTimerGroup(minutes) {
  if (!minutes || isNaN(minutes)) return;
  groupSelection.forEach(id => {
    const data = vehicleData[id] || {};
    // Chỉ bắt đầu cho xe không ở cung đường
    if (!data.routeNumber || data.routeNumber === 0) {
      startTimer(id, minutes);
    }
  });
  groupSelection = [];
  renderVehicles();
}

// Gán cung đường hàng loạt cho các xe đã chọn trong tab Khách đoàn
function setRouteGroup(route) {
  const routeNum = parseInt(route, 10);
  if (!routeNum || routeNum < 1 || routeNum > 10) return;
  groupSelection.forEach(id => {
    changeRoute(id, routeNum);
  });
  groupSelection = [];
  renderVehicles();
}

// Toggle collapse state for a vehicle (used on mobile). When collapsed, hide timer and controls.
function toggleDetails(id) {
  collapsedVehicles[id] = !collapsedVehicles[id];
  
  // Thay vì render lại toàn bộ, chỉ toggle trạng thái của xe cụ thể
  // Tìm xe theo data-vehicle-id attribute
  const vehicleElement = document.querySelector(`[data-vehicle-id="${id}"]`);
  
  if (vehicleElement) {
    const detailsElement = vehicleElement.querySelector('.details');
    const arrowElement = vehicleElement.querySelector('.arrow');
    
    if (detailsElement && arrowElement) {
      if (collapsedVehicles[id]) {
        detailsElement.style.display = 'none';
        arrowElement.classList.remove('expanded');
        arrowElement.classList.add('collapsed');
        vehicleElement.classList.add('collapsed');
      } else {
        detailsElement.style.display = 'block';
        arrowElement.classList.remove('collapsed');
        arrowElement.classList.add('expanded');
        vehicleElement.classList.remove('collapsed');
      }
    }
  }
}

// Reset tất cả xe trong một cung đường: đưa về bãi chờ
function resetRouteGroup(route) {
  const routeNum = parseInt(route, 10);
  if (!routeNum || isNaN(routeNum)) return;
  for (let i = 1; i <= vehicles; i++) {
    const data = vehicleData[i] || {};
    if (data.routeNumber === routeNum) {
      changeRoute(i, 0);
    }
  }
  // Sau khi reset, làm mới hiển thị
  renderVehicles();
}

// Hiển thị popup chỉnh sửa tình trạng xe
function showNotesEditor(id) {
  editingNotesId = id;
  const modal = document.getElementById('notes-modal');
  const title = document.getElementById('notes-title');
  const textarea = document.getElementById('notes-textarea');
  if (title) title.textContent = `TÌNH TRẠNG XE SỐ ${id}`;
  if (textarea) textarea.value = (vehicleData[id] && vehicleData[id].repairNotes) ? vehicleData[id].repairNotes : '';
  if (modal) modal.style.display = 'flex';
}

// Hiển thị popup nhập lý do đưa xe vào xưởng
function showWorkshopModal(id) {
  editingNotesId = id;
  sendingToWorkshop = true;
  const modal = document.getElementById('notes-modal');
  const title = document.getElementById('notes-title');
  const textarea = document.getElementById('notes-textarea');
  if (title) title.textContent = `LÝ DO ĐƯA XE VÀO XƯỞNG - XE ${id}`;
  if (textarea) textarea.value = '';
  if (modal) modal.style.display = 'flex';
}

// Đóng popup
function closeNotesModal() {
  const modal = document.getElementById('notes-modal');
  if (modal) modal.style.display = 'none';
  editingNotesId = null;
  // Reset sending to workshop flag when closing
  sendingToWorkshop = false;
}

// Hiển thị lịch sử sửa chữa của xe
    function showRepairHistory(vehicleId) {
        fetch('get_repair_history.php?vehicle_id=' + vehicleId)
            .then(response => response.json())
            .then(data => {
                if (data.success && data.repairs && Array.isArray(data.repairs)) {
                    // Lấy 3 lần sửa chữa gần nhất
                    const recentRepairs = data.repairs.slice(0, 3);
                    let historyHtml = '<div class="repair-history-table-container">';
                    
                    if (recentRepairs.length === 0) {
                        historyHtml += '<div class="no-repair-history">';
                        historyHtml += '<h3>Xe ' + vehicleId + ' chưa có lịch sử sửa chữa</h3>';
                        historyHtml += '<p class="no-data-message">Chưa có bản ghi sửa chữa nào cho xe này</p>';
                        historyHtml += '<div class="repair-history-actions">';
                        historyHtml += '<button class="btn-primary" onclick="showAddRepairModal(' + vehicleId + ')">➕ Thêm lịch sử sửa chữa</button>';
                        
                        // Xe không có lịch sử sửa chữa thì không thể xuất xưởng
                        historyHtml += '<button class="btn-warning" disabled>🚗 Xuất xưởng</button>';
                        historyHtml += '</div>';
                        historyHtml += '</div>';
                    } else {
                        historyHtml += '<h3>3 lần sửa chữa gần nhất của Xe ' + vehicleId + '</h3>';
                        historyHtml += '<div class="repair-history-actions">';
                        historyHtml += '<button class="btn-primary" onclick="showAddRepairModal(' + vehicleId + ')">➕ Thêm sửa chữa mới</button>';
                        
                        // Kiểm tra tất cả lịch sử sửa chữa để quyết định có thể xuất xưởng không
                        const canExport = recentRepairs.every(repair => 
                            repair.status === 'completed' || repair.status === 'cancelled'
                        );
                        
                        if (canExport) {
                            historyHtml += '<button class="btn-success" onclick="exportFromWorkshop(' + vehicleId + ')">🚗 Xuất xưởng</button>';
                        } else {
                            historyHtml += '<button class="btn-warning" disabled>🚗 Xuất xưởng</button>';
                        }
                        historyHtml += '</div>';
                        
                        historyHtml += '<table class="repair-history-table">';
                        historyHtml += '<thead><tr><th>Ngày sửa</th><th>Loại sửa chữa</th><th>Mô tả</th><th>Chi phí</th><th>Trạng thái</th><th>Thợ sửa</th><th>Thao tác</th></tr></thead>';
                        historyHtml += '<tbody>';
                        
                        recentRepairs.forEach(repair => {
                            const statusText = {
                                'pending': 'Chờ xử lý',
                                'in_progress': 'Đang sửa',
                                'completed': 'Hoàn thành',
                                'cancelled': 'Đã hủy'
                            }[repair.status] || repair.status;
                            
                            historyHtml += `
                                <tr>
                                    <td data-label="Ngày sửa:">${repair.repair_date || '-'}</td>
                                    <td data-label="Loại sửa chữa:"><strong>${repair.repair_type || 'Không có'}</strong></td>
                                    <td data-label="Mô tả:">${repair.description || 'Không có mô tả'}</td>
                                    <td data-label="Chi phí:">${repair.cost > 0 ? repair.cost.toLocaleString('vi-VN') + ' VNĐ' : '-'}</td>
                                    <td data-label="Trạng thái:"><span class="status-badge status-${repair.status || 'unknown'}">${statusText}</span></td>
                                    <td data-label="Thợ sửa:">${repair.technician || '-'}</td>
                                    <td data-label="Thao tác:">
                                        <button class="edit-repair-btn" onclick="editRepairFromHistory(${repair.id})" title="Sửa sửa chữa">✏️</button>
                                    </td>
                                </tr>
                            `;
                        });
                        
                        historyHtml += '</tbody></table>';
                    }
                    
                    historyHtml += '</div>';
                    
                    // Hiển thị modal
                    const modal = document.getElementById('repair-history-modal');
                    const content = document.getElementById('repair-history-content');
                    if (modal && content) {
                        content.innerHTML = historyHtml;
                        modal.style.display = 'block';
                    } else {
                        console.error('Modal elements not found');
                        alert('Không thể hiển thị modal lịch sử sửa chữa');
                    }
                } else {
                    console.error('Invalid data structure:', data);
                    alert('Dữ liệu không hợp lệ hoặc không có lịch sử sửa chữa');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Có lỗi xảy ra khi tải lịch sử sửa chữa');
            });
    }
    
    function closeRepairHistoryModal() {
        document.getElementById('repair-history-modal').style.display = 'none';
    }
    
    // Sửa sửa chữa từ lịch sử
    function editRepairFromHistory(repairId) {
        // Đóng modal lịch sử trước
        closeRepairHistoryModal();
        
        // Mở modal sửa chữa trước
        document.getElementById('repair-modal').style.display = 'block';
        
        // Load danh sách xe trước
        loadVehicleOptions().then(() => {
            // Sau khi load xe xong, mới load thông tin sửa chữa
            fetch('get_repair_by_id.php?id=' + repairId)
                .then(response => response.json())
                .then(data => {
                    console.log('Repair data:', data); // Debug log
                    if (data.success) {
                        const repair = data.repair;
                        console.log('Repair object:', repair); // Debug log
                        console.log('Vehicle ID:', repair.vehicle_id); // Debug log
                        
                        // Fill form với dữ liệu hiện tại
                        document.getElementById('repair-id').value = repair.id;
                        document.getElementById('vehicle-select').value = repair.vehicle_id;
                        document.getElementById('repair-type').value = repair.repair_type;
                        document.getElementById('repair-description').value = repair.description;
                        document.getElementById('repair-cost').value = repair.cost;
                        document.getElementById('repair-date').value = repair.repair_date;
                        document.getElementById('technician').value = repair.technician || '';
                        document.getElementById('repair-status').value = repair.status;
                        
                        // Disable select xe và set text cố định
                        const vehicleSelect = document.getElementById('vehicle-select');
                        vehicleSelect.disabled = true;
                        vehicleSelect.style.backgroundColor = '#f5f5f5';
                        vehicleSelect.style.cursor = 'not-allowed';
                        
                        // Set mode sửa
                        document.getElementById('repair-modal-title').textContent = '✏️ Sửa sửa chữa';
                        document.getElementById('repair-submit-btn').textContent = 'Cập nhật sửa chữa';
                    } else {
                        alert('Lỗi: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Có lỗi xảy ra khi tải thông tin sửa chữa');
                });
        });
    }
    
    // Thêm sửa chữa mới từ lịch sử
    function showAddRepairModal(vehicleId) {
        // Đóng modal lịch sử trước
        closeRepairHistoryModal();
        
        // Mở modal sửa chữa trước
        document.getElementById('repair-modal').style.display = 'block';
        
        // Reset form và set mode thêm mới
        document.getElementById('repair-form').reset();
        document.getElementById('repair-modal-title').textContent = '➕ Thêm sửa chữa mới';
        document.getElementById('repair-submit-btn').textContent = 'Lưu sửa chữa';
        document.getElementById('repair-id').value = '';
        
        // Load danh sách xe trước
        loadVehicleOptions().then(() => {
            // Sau khi load xe xong, mới set value và disable
            document.getElementById('vehicle-select').value = vehicleId;
            
            // Disable select xe và set text cố định
            const vehicleSelect = document.getElementById('vehicle-select');
            vehicleSelect.disabled = true;
            vehicleSelect.style.backgroundColor = '#f5f5f5';
            vehicleSelect.style.cursor = 'not-allowed';
        });
    }
    
    // Xuất xe khỏi xưởng
    function exportFromWorkshop(vehicleId) {
        if (confirm('Bạn có chắc chắn muốn xuất xe ' + vehicleId + ' khỏi xưởng?')) {
            // Gọi API để xuất xe
            fetch('save_vehicle_status.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    id: vehicleId,
                    active: 1
                })
            })
            .then(response => response.text())
            .then(data => {
                if (data === 'OK') {
                    alert('Đã xuất xe ' + vehicleId + ' khỏi xưởng thành công!');
                    closeRepairHistoryModal();
                    location.reload(); // Reload trang để cập nhật
                } else {
                    alert('Lỗi: ' + data);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Có lỗi xảy ra khi xuất xe khỏi xưởng');
            });
        }
    }
    
    // Đóng modal sửa chữa
    function closeRepairModal() {
        document.getElementById('repair-modal').style.display = 'none';
        document.getElementById('repair-form').reset();
        
        // Reset trạng thái select xe
        const vehicleSelect = document.getElementById('vehicle-select');
        if (vehicleSelect) {
            vehicleSelect.disabled = false;
            vehicleSelect.style.backgroundColor = '';
            vehicleSelect.style.cursor = '';
        }
    }
    
    // Load danh sách xe vào select
    function loadVehicleOptions() {
        return fetch('get_vehicles.php')
            .then(response => response.json())
            .then(data => {
                console.log('Vehicles data:', data); // Debug log
                const vehicleSelect = document.getElementById('vehicle-select');
                if (vehicleSelect) {
                    // Clear existing options except the first one
                    vehicleSelect.innerHTML = '<option value="">Chọn xe</option>';
                    
                    // Xử lý data dạng object {1: {...}, 2: {...}}
                    Object.values(data).forEach(vehicle => {
                        console.log('Adding vehicle:', vehicle); // Debug log
                        const option = document.createElement('option');
                        option.value = vehicle.id;
                        option.textContent = `Xe ${vehicle.id}`;
                        vehicleSelect.appendChild(option);
                    });
                    
                    console.log('Final vehicle select options:', vehicleSelect.innerHTML); // Debug log
                }
                return data;
            })
            .catch(error => {
                console.error('Error loading vehicles:', error);
                throw error;
            });
    }
    
    // Xử lý form thêm/sửa sửa chữa
    document.addEventListener('DOMContentLoaded', function() {
        const repairForm = document.getElementById('repair-form');
        if (repairForm) {
            repairForm.addEventListener('submit', function(e) {
                e.preventDefault();
                
                const formData = new FormData(this);
                const repairId = document.getElementById('repair-id').value;
                const vehicleId = document.getElementById('vehicle-select').value;
                
                // Debug logging
                console.log('Form data before submit:');
                for (let [key, value] of formData.entries()) {
                    console.log(key + ': ' + value);
                }
                console.log('Repair ID:', repairId);
                console.log('Vehicle ID:', vehicleId);
                
                // Đảm bảo vehicle_id được gửi (vì select có thể bị disable)
                if (vehicleId) {
                    formData.set('vehicle_id', vehicleId);
                }
                
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
                        alert(message);
                        closeRepairModal();
                        location.reload(); // Reload trang để cập nhật
                    } else {
                        alert('Lỗi: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Có lỗi xảy ra khi xử lý sửa chữa');
                });
            });
        }
    });

// Lưu tình trạng xe
function saveNotes() {
  const id = editingNotesId;
  if (!id) {
    closeNotesModal();
    return;
  }
  const textarea = document.getElementById('notes-textarea');
  const notes = textarea ? textarea.value.trim() : '';
  // Cập nhật dữ liệu cục bộ và gửi lên máy chủ
  if (!vehicleData[id]) vehicleData[id] = {};
  // Nếu đang gửi xe vào xưởng: đặt active=false và lưu lý do
  if (sendingToWorkshop) {
    vehicleData[id].repairNotes = notes;
    vehicleData[id].active = false;
    updateVehicleStatus(id, { repairNotes: notes, active: false });
    
    // Tự động tạo bản ghi sửa chữa khi báo cáo hỏng hóc
    if (notes) {
      const formData = new FormData();
      formData.append('vehicle_id', id);
      formData.append('repair_type', 'Hỏng hóc');
      formData.append('description', notes);
      formData.append('repair_date', new Date().toISOString().split('T')[0]);
      formData.append('status', 'pending');
      formData.append('cost', '0');
      
      fetch('add_repair_record.php', {
        method: 'POST',
        body: formData
      })
      .then(response => response.json())
      .then(data => {
        if (data.success) {
          console.log('Đã tạo bản ghi sửa chữa tự động');
        } else {
          console.error('Lỗi tạo bản ghi sửa chữa:', data.message);
        }
      })
      .catch(error => {
        console.error('Error creating repair record:', error);
      });
    }
    
    sendingToWorkshop = false;
  } else {
    vehicleData[id].repairNotes = notes;
    updateVehicleStatus(id, { repairNotes: notes });
  }
  closeNotesModal();
  renderVehicles();
}

// Hiển thị/ẩn menu điều hướng trên thiết bị di động
function toggleNav() {
  const nav = document.getElementById('nav-menu');
  if (nav) {
    nav.classList.toggle('open');
  }
}

// Load user content based on user role
function loadUserContent() {
  const userContent = document.getElementById('user-content');
  const vehicleList = document.getElementById('vehicle-list');
  const groupControls = document.getElementById('group-controls');
  
  if (!userContent) return;
  
  // Ẩn vehicle list và group controls
  if (vehicleList) vehicleList.style.display = 'none';
  if (groupControls) groupControls.style.display = 'none';
  
  // Hiển thị user content
  userContent.style.display = 'block';
  
  // Fetch user content based on role
  fetch('get_user_content.php')
    .then(res => res.json())
    .then(data => {
      if (data.success) {
        userContent.innerHTML = data.content;
        // Cập nhật tiêu đề trang
        updatePageTitle();
      } else {
        userContent.innerHTML = '<p class="error-message">Không thể tải nội dung người dùng</p>';
      }
    })
    .catch(error => {
      console.error('Error loading user content:', error);
      userContent.innerHTML = '<p class="error-message">Có lỗi xảy ra khi tải nội dung</p>';
    });
}

// Khởi tạo khi tải trang
window.onload = () => {
  // Đọc tham số filter từ URL để đặt bộ lọc ban đầu
  const urlParams = new URLSearchParams(window.location.search);
  const filterFromUrl = urlParams.get('filter');
  currentFilter = filterFromUrl || 'all';
  
  // Luôn khởi tạo các event listeners và chức năng cơ bản
  initializeBasicFunctions();
  
  // Xử lý các filter đặc biệt ngay khi load trang
  if (filterFromUrl === 'user') {
    console.log('Filter is user, calling loadUserContent()...');
    loadUserContent();
    return;
  }
  
  if (filterFromUrl === 'maintenance') {
    const maintenanceContent = document.getElementById('maintenance-content');
    const vehicleList = document.getElementById('vehicle-list');
    if (maintenanceContent) maintenanceContent.style.display = 'block';
    if (vehicleList) vehicleList.style.display = 'none';
    updatePageTitle();
    return;
  }
  
  if (filterFromUrl === 'repair') {
    const repairContent = document.getElementById('repair-content');
    const vehicleList = document.getElementById('vehicle-list');
    if (repairContent) repairContent.style.display = 'block';
    if (vehicleList) vehicleList.style.display = 'none';
    updatePageTitle();
    return;
  }
  
  // Hiển thị điều khiển Khách đoàn nếu cần
  const groupControls = document.getElementById('group-controls');
  if (groupControls) {
    groupControls.style.display = (currentFilter === 'group') ? 'flex' : 'none';
  }
  
  // Chỉ load vehicle data cho các filter thông thường
  if (filterFromUrl !== 'maintenance' && filterFromUrl !== 'repair' && filterFromUrl !== 'user') {
    loadVehicleData();
    updateTimers();
    periodicRefresh();
    // Cập nhật tiêu đề trang lần đầu cho filter thông thường
    updatePageTitle();
  }
  // Không cần gọi updatePageTitle() ở đây vì đã gọi trong các filter đặc biệt
};

// Khởi tạo các chức năng cơ bản (luôn được gọi)
function initializeBasicFunctions() {
  console.log('Initializing basic functions...');
  
  // Đăng ký sự kiện cho nút menu khi ở chế độ mobile
  const menuToggle = document.getElementById('menu-toggle');
  if (menuToggle) {
    console.log('Menu toggle button found, adding event listener');
    menuToggle.addEventListener('click', toggleNav);
  } else {
    console.log('Menu toggle button not found');
  }

  // Đóng menu khi click ra ngoài menu trên mobile
  document.addEventListener('click', function(e) {
    const nav = document.getElementById('nav-menu');
    const toggle = document.getElementById('menu-toggle');
    if (!nav || !toggle) return;
    if (nav.classList.contains('open')) {
      if (!nav.contains(e.target) && e.target !== toggle) {
        nav.classList.remove('open');
      }
    }
  });
}

// ---------------------------------------------------------
// User Management Functions
// ---------------------------------------------------------

// Load danh sách người dùng
function loadUsersList() {
  console.log('loadUsersList() called');
  const userTableBody = document.getElementById('user-table-body');
  if (!userTableBody) {
    console.error('user-table-body not found');
    return;
  }
  
  console.log('Fetching users from get_users_list.php...');
  fetch('get_users_list.php')
    .then(response => {
      console.log('Response status:', response.status);
      return response.json();
    })
    .then(data => {
      console.log('Response data:', data);
      if (data.success) {
        renderUsersTable(data.users);
      } else {
        console.error('Error loading users:', data.message);
        userTableBody.innerHTML = '<tr><td colspan="6" class="no-data">Không thể tải danh sách người dùng: ' + data.message + '</td></tr>';
      }
    })
    .catch(error => {
      console.error('Error:', error);
      userTableBody.innerHTML = '<tr><td colspan="6" class="no-data">Có lỗi xảy ra khi tải dữ liệu: ' + error.message + '</td></tr>';
    });
}

// Render bảng người dùng
function renderUsersTable(users) {
  const userTableBody = document.getElementById('user-table-body');
  if (!userTableBody) return;
  
  if (users.length === 0) {
    userTableBody.innerHTML = '<tr><td colspan="6" class="no-data">Chưa có người dùng nào</td></tr>';
    return;
  }
  
  let html = '';
  users.forEach(user => {
    const roleText = user.is_admin ? 'Quản trị viên' : 'Người dùng';
    const roleClass = user.is_admin ? 'admin' : 'user';
    const statusText = user.is_active ? 'Hoạt động' : 'Bị tắt';
    const statusClass = user.is_active ? 'active' : 'inactive';
    const toggleText = user.is_active ? 'Tắt' : 'Bật';
    const toggleClass = user.is_active ? '' : 'deactivated';
    
    html += `
      <tr>
        <td>${user.id}</td>
        <td>${escapeHtml(user.name)}</td>
        <td>${escapeHtml(user.phone)}</td>
        <td><span class="status-badge ${roleClass}">${roleText}</span></td>
        <td><span class="status-badge ${statusClass}">${statusText}</span></td>
        <td class="user-actions-cell">
          <button class="btn-edit" onclick="showEditUserModal(${user.id}, '${escapeHtml(user.name)}', '${escapeHtml(user.phone)}', ${user.is_admin})" title="Sửa người dùng">
            ✏️
          </button>
          <button class="btn-toggle ${toggleClass}" onclick="toggleUserStatus(${user.id}, ${user.is_active ? 0 : 1})" title="${toggleText} người dùng" ${user.id == getCurrentUserId() ? 'disabled' : ''}>
            ${user.is_active ? '🔒' : '🔓'}
          </button>
        </td>
      </tr>
    `;
  });
  
  userTableBody.innerHTML = html;
}

// Escape HTML để tránh XSS
function escapeHtml(text) {
  const div = document.createElement('div');
  div.textContent = text;
  return div.innerHTML;
}

// Lấy ID của user hiện tại từ session
let currentUserId = null;

function getCurrentUserId() {
  return currentUserId;
}

// Hiển thị modal thêm người dùng
function showAddUserModal() {
  const modal = document.getElementById('add-user-modal');
  if (modal) {
    modal.style.display = 'block';
    document.getElementById('add-user-form').reset();
  }
}

// Đóng modal thêm người dùng
function closeAddUserModal() {
  const modal = document.getElementById('add-user-modal');
  if (modal) {
    modal.style.display = 'none';
  }
}

// Hiển thị modal chỉnh sửa người dùng
function showEditUserModal(userId, name, phone, isAdmin) {
  const modal = document.getElementById('edit-user-modal');
  if (modal) {
    document.getElementById('edit-user-id').value = userId;
    document.getElementById('edit-user-name').value = name;
    document.getElementById('edit-user-phone').value = phone;
    document.getElementById('edit-user-password').value = ''; // Reset password field
    document.getElementById('edit-user-role').value = isAdmin ? 1 : 0;
    modal.style.display = 'block';
  }
}

// Đóng modal chỉnh sửa người dùng
function closeEditUserModal() {
  const modal = document.getElementById('edit-user-modal');
  if (modal) {
    modal.style.display = 'none';
  }
}

// Hiển thị modal chỉnh sửa thông tin cá nhân
function showEditProfileModal() {
  const modal = document.getElementById('edit-profile-modal');
  if (modal) {
    modal.style.display = 'block';
  }
}

// Đóng modal chỉnh sửa thông tin cá nhân
function closeEditProfileModal() {
  const modal = document.getElementById('edit-profile-modal');
  if (modal) {
    modal.style.display = 'none';
  }
}

// Toggle trạng thái người dùng
function toggleUserStatus(userId, newStatus) {
  if (!confirm(`Bạn có chắc chắn muốn ${newStatus ? 'bật' : 'tắt'} người dùng này?`)) {
    return;
  }
  
  const formData = new FormData();
  formData.append('user_id', userId);
  formData.append('status', newStatus);
  
  fetch('toggle_user_status.php', {
    method: 'POST',
    body: formData
  })
  .then(response => response.json())
  .then(data => {
    if (data.success) {
      alert(data.message);
      loadUsersList(); // Reload danh sách
    } else {
      alert('Lỗi: ' + data.message);
    }
  })
  .catch(error => {
    console.error('Error:', error);
    alert('Có lỗi xảy ra khi cập nhật trạng thái người dùng');
  });
}

// Biến để theo dõi xem đã khởi tạo user management chưa
let userManagementInitialized = false;

// Khởi tạo event listeners cho quản lý người dùng
function initializeUserManagement() {
  console.log('initializeUserManagement() called');
  
  // Kiểm tra element cần thiết
  const userTableBody = document.getElementById('user-table-body');
  if (!userTableBody) {
    console.error('Cannot initialize user management: user-table-body not found');
    return false;
  }
  
  // Tránh khởi tạo nhiều lần
  if (userManagementInitialized) {
    console.log('User management already initialized, just reloading users list');
    loadUsersList();
    return true;
  }
  
  console.log('Initializing user management for the first time...');
  
  // Event listener cho form thêm người dùng
  const addUserForm = document.getElementById('add-user-form');
  if (addUserForm) {
    addUserForm.addEventListener('submit', function(e) {
      e.preventDefault();
      
      const formData = new FormData(this);
      
      fetch('add_new_user.php', {
        method: 'POST',
        body: formData
      })
      .then(response => response.json())
      .then(data => {
        if (data.success) {
          alert(data.message);
          closeAddUserModal();
          loadUsersList(); // Reload danh sách
        } else {
          alert('Lỗi: ' + data.message);
        }
      })
      .catch(error => {
        console.error('Error:', error);
        alert('Có lỗi xảy ra khi thêm người dùng');
      });
    });
  }
  
  // Event listener cho form chỉnh sửa người dùng
  const editUserForm = document.getElementById('edit-user-form');
  if (editUserForm) {
    editUserForm.addEventListener('submit', function(e) {
      e.preventDefault();
      
      const formData = new FormData(this);
      
      fetch('update_user.php', {
        method: 'POST',
        body: formData
      })
      .then(response => response.json())
      .then(data => {
        if (data.success) {
          alert(data.message);
          closeEditUserModal();
          loadUsersList(); // Reload danh sách
        } else {
          alert('Lỗi: ' + data.message);
        }
      })
      .catch(error => {
        console.error('Error:', error);
        alert('Có lỗi xảy ra khi cập nhật người dùng');
      });
    });
  }
  
  // Event listener cho form chỉnh sửa thông tin cá nhân
  const editProfileForm = document.getElementById('edit-profile-form');
  if (editProfileForm) {
    editProfileForm.addEventListener('submit', function(e) {
      e.preventDefault();
      
      const formData = new FormData(this);
      
      fetch('update_user.php', {
        method: 'POST',
        body: formData
      })
      .then(response => response.json())
      .then(data => {
        if (data.success) {
          showSuccessModal(data.message || 'Cập nhật thông tin thành công!');
          closeEditProfileModal();
          // Reload user content để cập nhật thông tin hiển thị
          setTimeout(() => {
            loadUserContent();
          }, 1500);
        } else {
          showErrorModal('Lỗi: ' + (data.message || 'Không thể cập nhật thông tin'));
        }
      })
      .catch(error => {
        console.error('Error:', error);
        showErrorModal('Có lỗi xảy ra khi cập nhật thông tin cá nhân');
      });
    });
  }
  
  // Đóng modal khi click ra ngoài
  window.onclick = function(event) {
    const addModal = document.getElementById('add-user-modal');
    const editModal = document.getElementById('edit-user-modal');
    const editProfileModal = document.getElementById('edit-profile-modal');
    
    if (event.target === addModal) {
      closeAddUserModal();
    }
    if (event.target === editModal) {
      closeEditUserModal();
    }
    if (event.target === editProfileModal) {
      closeEditProfileModal();
    }
  };
  
  // Đánh dấu đã khởi tạo
  userManagementInitialized = true;
  
  // Load danh sách người dùng khi khởi tạo
  console.log('Calling loadUsersList() from initializeUserManagement');
  loadUsersList();
  
  return true;
}

// Event listener cũ đã được xóa để tránh xung đột
// initializeUserManagement() sẽ được gọi từ loadUserContent() sau khi DOM sẵn sàng

// Functions để hiển thị modal thông báo
function showSuccessModal(message) {
  const modal = document.getElementById('message-modal');
  if (modal) {
    const messageText = document.getElementById('message-text');
    if (messageText) {
      messageText.textContent = message;
    }
    modal.style.display = 'flex';
  } else {
    // Fallback nếu không có modal, sử dụng alert
    alert('Thành công: ' + message);
  }
}

function showErrorModal(message) {
  const modal = document.getElementById('message-modal');
  if (modal) {
    const messageText = document.getElementById('message-text');
    if (messageText) {
      messageText.textContent = message;
    }
    // Thay đổi class để hiển thị lỗi
    modal.classList.add('error');
    modal.style.display = 'flex';
  } else {
    // Fallback nếu không có modal, sử dụng alert
    alert('Lỗi: ' + message);
  }
}

function closeMessageModal() {
  const modal = document.getElementById('message-modal');
  if (modal) {
    modal.style.display = 'none';
    modal.classList.remove('error');
  }
}

// Function để khởi tạo event listener cho form edit profile (user thường)
function initializeProfileEventListeners() {
  console.log('initializeProfileEventListeners() called');
  
  // Event listener cho form chỉnh sửa thông tin cá nhân
  const editProfileForm = document.getElementById('edit-profile-form');
  if (editProfileForm) {
    console.log('edit-profile-form found, adding event listener');
    editProfileForm.addEventListener('submit', function(e) {
      e.preventDefault();
      console.log('edit-profile-form submitted');
      
      const formData = new FormData(this);
      console.log('Form data:', formData);
      
      fetch('update_user.php', {
        method: 'POST',
        body: formData
      })
      .then(response => response.json())
      .then(data => {
        console.log('Response from update_user.php:', data);
        if (data.success) {
          showSuccessModal(data.message || 'Cập nhật thông tin thành công!');
          closeEditProfileModal();
          // Reload user content để cập nhật thông tin hiển thị
          setTimeout(() => {
            loadUserContent();
          }, 1500);
        } else {
          showErrorModal('Lỗi: ' + (data.message || 'Không thể cập nhật thông tin'));
        }
      })
      .catch(error => {
        console.error('Error:', error);
        showErrorModal('Có lỗi xảy ra khi cập nhật thông tin cá nhân');
      });
    });
    console.log('Event listener added to edit-profile-form');
  } else {
    console.error('edit-profile-form not found in initializeProfileEventListeners');
  }
}

// Thêm event listener cho khi user content được load
function loadUserContent() {
  console.log('loadUserContent() called');
  const userContent = document.getElementById('user-content');
  const vehicleList = document.getElementById('vehicle-list');
  const groupControls = document.getElementById('group-controls');
  
  if (!userContent) {
    console.error('user-content element not found');
    return;
  }
  
  // Ẩn vehicle list và group controls
  if (vehicleList) vehicleList.style.display = 'none';
  if (groupControls) groupControls.style.display = 'none';
  
  // Hiển thị user content
  userContent.style.display = 'block';
  
  // Fetch user content based on role
  console.log('Fetching user content from get_user_content.php...');
  fetch('get_user_content.php')
    .then(res => res.json())
    .then(data => {
      console.log('User content response:', data);
      if (data.success) {
        console.log('Setting userContent.innerHTML...');
        console.log('Content to be inserted:', data.content);
        
        // Lưu current user ID từ server
        if (data.current_user_id) {
          currentUserId = data.current_user_id;
          console.log('Current user ID set to:', currentUserId);
        }
        
        userContent.innerHTML = data.content;
        console.log('userContent.innerHTML set, checking for user-table-body...');
        
        // Debug: kiểm tra ngay sau khi set innerHTML
        const immediateCheck = document.getElementById('user-table-body');
        console.log('Immediate check for user-table-body:', immediateCheck);
        
        // Debug: kiểm tra userContent có HTML không
        console.log('userContent.children.length:', userContent.children.length);
        console.log('userContent.innerHTML length:', userContent.innerHTML.length);
        // Cập nhật tiêu đề trang
        updatePageTitle();
        
        // Nếu là admin, khởi tạo quản lý người dùng
        if (data.is_admin) {
          console.log('User is admin, initializing user management...');
          
          // Phương án 1: Thử ngay lập tức
          const immediateTableBody = document.getElementById('user-table-body');
          if (immediateTableBody) {
            console.log('user-table-body found immediately, initializing...');
            initializeUserManagement();
          } else {
            console.log('user-table-body not found immediately, using MutationObserver...');
            
            // Phương án 2: Sử dụng MutationObserver
            const observer = new MutationObserver(function(mutations) {
              mutations.forEach(function(mutation) {
                if (mutation.type === 'childList') {
                  const userTableBody = document.getElementById('user-table-body');
                  if (userTableBody) {
                    console.log('user-table-body found via MutationObserver, initializing...');
                    observer.disconnect(); // Dừng observer
                    initializeUserManagement();
                  }
                }
              });
            });
            
            // Theo dõi thay đổi trong userContent
            observer.observe(userContent, {
              childList: true,
              subtree: true
            });
            
            // Fallback: Dừng observer sau 5 giây và thử timeout
            setTimeout(() => {
              observer.disconnect();
              console.log('MutationObserver timeout, trying setTimeout fallback...');
              
              setTimeout(() => {
                const userTableBody = document.getElementById('user-table-body');
                if (userTableBody) {
                  console.log('user-table-body found via setTimeout fallback, initializing...');
                  initializeUserManagement();
                } else {
                  console.error('user-table-body not found even with all methods');
                  // Debug: In ra toàn bộ userContent HTML
                  console.log('userContent.innerHTML:', userContent.innerHTML);
                }
              }, 500);
            }, 5000);
          }
        } else {
          console.log('User is not admin, initializing profile event listeners...');
          
          // Phương án 1: Thử ngay lập tức
          const immediateEditProfileForm = document.getElementById('edit-profile-form');
          if (immediateEditProfileForm) {
            console.log('edit-profile-form found immediately, initializing...');
            initializeProfileEventListeners();
          } else {
            console.log('edit-profile-form not found immediately, using MutationObserver...');
            
            // Phương án 2: Sử dụng MutationObserver
            const observer = new MutationObserver(function(mutations) {
              mutations.forEach(function(mutation) {
                if (mutation.type === 'childList') {
                  const editProfileForm = document.getElementById('edit-profile-form');
                  if (editProfileForm) {
                    console.log('edit-profile-form found via MutationObserver, initializing...');
                    observer.disconnect(); // Dừng observer
                    initializeProfileEventListeners();
                  }
                }
              });
            });
            
            // Theo dõi thay đổi trong userContent
            observer.observe(userContent, {
              childList: true,
              subtree: true
            });
            
            // Fallback: Dừng observer sau 3 giây
            setTimeout(() => {
              observer.disconnect();
              console.log('MutationObserver timeout, trying setTimeout fallback...');
              
              setTimeout(() => {
                const editProfileForm = document.getElementById('edit-profile-form');
                if (editProfileForm) {
                  console.log('edit-profile-form found via setTimeout fallback, initializing...');
                  initializeProfileEventListeners();
                } else {
                  console.error('Failed to find edit-profile-form after all attempts');
                  // Debug: In ra toàn bộ userContent HTML
                  console.log('userContent.innerHTML:', userContent.innerHTML);
                }
              }, 1000);
            }, 3000);
          }
        }
      } else {
        console.error('Failed to load user content:', data.message);
        userContent.innerHTML = '<p class="error-message">Không thể tải nội dung người dùng: ' + data.message + '</p>';
      }
    })
    .catch(error => {
      console.error('Error loading user content:', error);
      userContent.innerHTML = '<p class="error-message">Có lỗi xảy ra khi tải nội dung: ' + error.message + '</p>';
    });
}