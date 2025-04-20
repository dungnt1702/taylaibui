
const firebaseConfig = {
  apiKey: "AIzaSyDsf_S3xkV-N0A08D425YQOqMPJuT15FKU",
  authDomain: "taylaibui-tracker.firebaseapp.com",
  databaseURL: "https://taylaibui-tracker-default-rtdb.asia-southeast1.firebasedatabase.app",
  projectId: "taylaibui-tracker",
  storageBucket: "taylaibui-tracker.appspot.com",
  messagingSenderId: "1004195839154",
  appId: "1:1004195839154:web:bf2b1056c3506343549dab"
};
firebase.initializeApp(firebaseConfig);
const db = firebase.database();

const vehicles = 22;
let vehicleData = {};
let currentFilter = 'all';
let timers = {};
let chart;

function speak(text) {
  const utter = new SpeechSynthesisUtterance(text);
  utter.lang = 'vi-VN';
  speechSynthesis.speak(utter);
}

function formatTime(seconds) {
  const m = Math.floor(seconds / 60);
  const s = seconds % 60;
  return \`\${m.toString().padStart(2, '0')}:\${s.toString().padStart(2, '0')}\`;
}

function setFilter(filter) {
  currentFilter = filter;
  document.querySelectorAll('.tabs button').forEach(btn => btn.classList.remove('active'));
  document.getElementById(\`tab-\${filter}\`).classList.add('active');
  renderVehicles();
}

function startTimer(id, minutes) {
  const endAt = Date.now() + minutes * 60000;
  db.ref('timers/' + id).set({
    endAt,
    minutes,
    paused: false,
    remaining: null,
    warned5: false,
    warned1: false,
    active: true
  });
}

function pauseTimer(id) {
  db.ref('timers/' + id).once('value', snap => {
    const data = snap.val();
    if (data && data.endAt) {
      const remaining = Math.floor((data.endAt - Date.now()) / 1000);
      db.ref('timers/' + id).update({ paused: true, remaining });
    }
  });
}

function resumeTimer(id) {
  db.ref('timers/' + id).once('value', snap => {
    const data = snap.val();
    if (data && data.remaining) {
      const endAt = Date.now() + data.remaining * 1000;
      db.ref('timers/' + id).update({ paused: false, endAt, remaining: null });
    }
  });
}

function resetTimer(id) {
  db.ref('timers/' + id).remove();
}

function toggleVehicle(id, active) {
  db.ref('timers/' + id + '/active').set(active);
}

function renderVehicles() {
  const container = document.getElementById('vehicle-list');
  container.innerHTML = '';

  const entries = Object.entries(vehicleData)
    .filter(([_, data]) => {
      if (!data) return false;
      if (currentFilter === 'active') return data.active;
      if (currentFilter === 'inactive') return !data.active;
      return true;
    })
    .sort(([_, a], [__, b]) => {
      const timeA = a.endAt ? Math.max((a.endAt - Date.now()) / 1000, 0) : 99999;
      const timeB = b.endAt ? Math.max((b.endAt - Date.now()) / 1000, 0) : 99999;
      return timeA - timeB;
    });

  entries.forEach(([id, data]) => {
    const vid = Number(id);
    const div = document.createElement('div');
    div.className = 'vehicle';
    if (!data.active) div.classList.add('inactive');

    const displayTime = data.paused ? 'Tạm hoãn' :
      (data.endAt ? (Math.floor((data.endAt - Date.now()) / 1000) > 0 ? formatTime(Math.floor((data.endAt - Date.now()) / 1000)) : 'Hết giờ') : '00:00');

    div.innerHTML = \`
      <h3>Xe \${vid}</h3>
      <div class="timer" id="timer-\${vid}">\${displayTime}</div>
      <button onclick="startTimer(\${vid}, 10)">Bắt đầu 10p</button>
      <button onclick="startTimer(\${vid}, 20)">Bắt đầu 20p</button>
      <button onclick="pauseTimer(\${vid})">Tạm hoãn</button>
      <button onclick="resumeTimer(\${vid})">Tiếp tục</button>
      <button onclick="resetTimer(\${vid})">Reset</button>
      <button onclick="toggleVehicle(\${vid}, \${!data.active})">\${data.active ? 'TẮT XE' : 'BẬT XE'}</button>
    \`;

    container.appendChild(div);
  });
}

function syncData() {
  for (let i = 1; i <= vehicles; i++) {
    db.ref('timers/' + i).on('value', snap => {
      const data = snap.val() || { active: false };
      if (!('active' in data)) data.active = false;
      vehicleData[i] = data;
      renderVehicles();

      clearInterval(timers[i]);
      const display = document.getElementById('timer-' + i);
      const parent = display?.parentElement;
      if (!display || data.paused || !data.endAt) return;

      let secondsLeft = Math.floor((data.endAt - Date.now()) / 1000);
      let warned5 = data.warned5 || false;
      let warned1 = data.warned1 || false;

      timers[i] = setInterval(() => {
        secondsLeft--;
        if (display) display.textContent = formatTime(secondsLeft);

        if (secondsLeft === 300 && !warned5) {
          speak(\`Xe số \${i} còn 5 phút\`);
          db.ref('timers/' + i + '/warned5').set(true);
          warned5 = true;
        }
        if (secondsLeft === 60 && !warned1) {
          speak(\`Xe số \${i} còn 1 phút\`);
          db.ref('timers/' + i + '/warned1').set(true);
          warned1 = true;
        }
        if (secondsLeft <= 0) {
          clearInterval(timers[i]);
          if (display) display.textContent = 'Hết giờ';
          speak(\`Xe số \${i} đã hết thời gian\`);
          if (data.minutes) {
            db.ref('usageLogs/' + new Date().toISOString().split('T')[0] + '/' + i)
              .transaction(val => (val || 0) + data.minutes);
          }
        }
      }, 1000);
    });
  }
}

syncData();
