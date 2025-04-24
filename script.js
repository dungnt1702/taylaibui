
function setFilter(filter) {
  document.querySelectorAll('.tabs button').forEach(btn => btn.classList.remove('active'));
  document.getElementById(`tab-${filter}`).classList.add('active');

  document.querySelectorAll('.vehicle').forEach(vehicle => {
    const isRunning = vehicle.classList.contains('running');
    const isPaused = vehicle.classList.contains('paused');
    const isExpired = vehicle.classList.contains('expired');

    if (
      filter === 'all' ||
      (filter === 'active' && (isRunning || isPaused)) ||
      (filter === 'inactive' && !isRunning && !isPaused)
    ) {
      vehicle.style.display = 'inline-block';
    } else {
      vehicle.style.display = 'none';
    }
  });
}
