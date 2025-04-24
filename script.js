let speechEnabled = false;
document.addEventListener('click', () => { speechEnabled = true; }, { once: true });

function speak(text) {
  if (!speechEnabled) return;
  const utter = new SpeechSynthesisUtterance(text);
  utter.lang = 'vi-VN';
  speechSynthesis.speak(utter);
}

function formatTime(seconds) {
  const m = Math.floor(seconds / 60);
  const s = seconds % 60;
  return m.toString().padStart(2, '0') + ':' + s.toString().padStart(2, '0');
}

let currentFilter = 'all';
let timers = {};
let vehicleData = {};
const vehicles = 22;
const db = firebase.database();

function setFilter(filter) {
  currentFilter = filter;
  localStorage.setItem('activeTab', filter);
  document.querySelectorAll('.tabs button').forEach(btn => btn.classList.remove('active'));
  document.getElementById('tab-' + filter).classList.add('active');
  renderVehicles();
}

function renderVehicles() {
  const container = document.getElementById('vehicle-list');
  container.innerHTML = '';
  for (let i = 1; i <= vehicles; i++) {
    const data = vehicleData[i] || {};
    if (currentFilter === 'active' && !data.active) continue;
    if (currentFilter === 'inactive' && data.active) continue;

    const secondsLeft = data.endAt ? Math.floor((data.endAt - Date.now()) / 1000) : 0;
    const div = document.createElement('div');
    div.className = 'vehicle';
    if (data.active === false) div.classList.add('inactive');
    if (secondsLeft <= 60 && secondsLeft > 0) div.classList.add('warning');
    if (secondsLeft <= 0 && data.endAt) div.classList.add('expired');
    if (data.paused) div.classList.add('paused');
    if (data.endAt && !data.paused) div.classList.add('running');

    div.innerHTML = `
      <h3>Xe ${i}</h3>
      <div class="timer" id="timer-${i}">${data.paused ? 'Tạm hoãn' : (data.endAt ? formatTime(secondsLeft) : '00:00')}</div>
      <div>
        <button onclick="startTimer(${i}, 15)">Bắt đầu 15p</button>
        <button onclick="startTimer(${i}, 30)">Bắt đầu 30p</button>
        <button onclick="pauseTimer(${i})">Tạm hoãn</button>
        <button onclick="resumeTimer(${i})">Tiếp tục</button>
        <button onclick="resetTimer(${i})">Reset</button>
      </div>
    `;
    container.appendChild(div);
  }
}

function startTimer(id, minutes) {
  const endAt = Date.now() + minutes * 60000;
  db.ref('timers/' + id).set({ endAt, active: true, minutes, paused: false });
}

function pauseTimer(id) {
  db.ref('timers/' + id).once('value').then(snap => {
    const data = snap.val();
    if (data) {
      const remaining = Math.floor((data.endAt - Date.now()) / 1000);
      db.ref('timers/' + id).update({ paused: true, remaining });
    }
  });
}

function resumeTimer(id) {
  db.ref('timers/' + id).once('value').then(snap => {
    const data = snap.val();
    if (data && data.remaining) {
      const endAt = Date.now() + data.remaining * 1000;
      db.ref('timers/' + id).update({ paused: false, endAt, remaining: null });
    }
  });
}

function resetTimer(id) {
  db.ref('timers/' + id).set(null);
}

function syncData() {
  for (let i = 1; i <= vehicles; i++) {
    db.ref('timers/' + i).on('value', snap => {
      vehicleData[i] = snap.val() || {};
      renderVehicles();
      clearInterval(timers[i]);

      if (vehicleData[i].endAt && !vehicleData[i].paused) {
        let secondsLeft = Math.floor((vehicleData[i].endAt - Date.now()) / 1000);
        const display = document.getElementById('timer-' + i);

        timers[i] = setInterval(() => {
          if (!display) return;
          secondsLeft--;
          display.textContent = secondsLeft > 0 ? formatTime(secondsLeft) : 'Hết giờ';

          if (secondsLeft === 60) speak('Xe số ' + i + ' còn 1 phút');
          if (secondsLeft === 300) speak('Xe số ' + i + ' còn 5 phút');
          if (secondsLeft === 0) {
            speak('Xe số ' + i + ' đã hết thời gian');
            clearInterval(timers[i]);
          }
        }, 1000);
      }
    });
  }
}

window.onload = () => {
  const savedFilter = localStorage.getItem('activeTab') || 'all';
  setFilter(savedFilter);
  syncData();
};
