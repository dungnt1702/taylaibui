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
    if (currentFilter !== 'group') {
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
    loadUserContent();
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
  
  // Ẩn user content và hiển thị vehicle list
  const userContent = document.getElementById('user-content');
  const vehicleList = document.getElementById('vehicle-list');
  if (userContent) userContent.style.display = 'none';
  if (vehicleList) vehicleList.style.display = 'block';
  
  renderVehicles();
  updatePageTitle();
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
    thead.innerHTML = '<tr><th>Số xe</th><th>Tình trạng</th><th>Hành động</th></tr>';
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
      const tdStatus = document.createElement('td');
      tdStatus.textContent = data.repairNotes || '';
      const tdAction = document.createElement('td');
      tdAction.innerHTML = `
        <button class="edit-notes-btn" onclick="showNotesEditor(${i})">✏️</button>
        <button class="workshop-out-btn" onclick="toggleVehicle(${i})">Xuất xưởng</button>
      `;
      tr.appendChild(tdId);
      tr.appendChild(tdStatus);
      tr.appendChild(tdAction);
      tbody.appendChild(tr);
    }
    table.appendChild(tbody);
    if (hasRow) {
      container.appendChild(table);
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
    // For group mode, render simple list
    if (currentFilter === 'group') {
      const div = document.createElement('div');
      div.className = 'vehicle group-mode';
      const selected = groupSelection.includes(i);
      if (selected) div.classList.add('selected');
      const statusText = (data.repairNotes && data.repairNotes.trim()) ? data.repairNotes : '';
      div.innerHTML = `
        <input type="checkbox" class="select-checkbox" ${selected ? 'checked' : ''} onchange="toggleSelection(${i})">
        <h3>Xe ${i}</h3>
        <div class="status" id="status-${i}">${statusText}</div>
      `;
      container.appendChild(div);
      return;
    }
    // Create card for normal mode
    const div = document.createElement('div');
    div.className = 'vehicle';
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
        // Xe chưa chạy: hiển thị hai nút bắt đầu và nút về xưởng/xuất xưởng
        controlsHtml += '<div class="controls-row">' +
          `<button onclick="startTimer(${i}, 45)" class="btn-45" id="btn45-${i}">Chạy 45p</button>` +
          `<button onclick="startTimer(${i}, 30)" class="btn-30" id="btn30-${i}">Chạy 30p</button>` +
          '</div>';
        controlsHtml += '<div class="controls-row">' +
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
    user: 'Quản lý người dùng'
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
  renderVehicles();
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
  
  // Xử lý filter user ngay khi load trang
  if (filterFromUrl === 'user') {
    loadUserContent();
    return;
  }
  
  // Hiển thị điều khiển Khách đoàn nếu cần
  const groupControls = document.getElementById('group-controls');
  if (groupControls) {
    groupControls.style.display = (currentFilter === 'group') ? 'flex' : 'none';
  }
  
  loadVehicleData();
  updateTimers();
  periodicRefresh();
  
  // Cập nhật tiêu đề trang lần đầu
  updatePageTitle();
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