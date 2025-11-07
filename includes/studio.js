document.addEventListener('DOMContentLoaded', function() {
    loadDashboardStats();
    loadQueue();
    // Refresh data every 30 seconds
    setInterval(() => {
        loadDashboardStats();
        loadQueue();
    }, 30000);
});

function loadDashboardStats() {
    fetch('../studio/api/get_dashboard_stats.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('pendingSessionCount').textContent = data.stats.pending_sessions;
                document.getElementById('completedSessionCount').textContent = data.stats.completed_sessions;
            }
        })
        .catch(error => console.error('Error loading dashboard stats:', error));
}

function loadQueue() {
    fetch('../studio/api/get_queue.php')
        .then(response => response.json())
        .then(data => {
            const queueList = document.getElementById('queueList');
            queueList.innerHTML = '';
            
            if (data.length === 0) {
                queueList.innerHTML = '<div class="text-center p-3">No pending sessions in queue</div>';
                return;
            }
            
            data.forEach(appointment => {
                const item = document.createElement('div');
                item.className = 'list-group-item';
                item.innerHTML = `
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="mb-1">${appointment.student_name}</h6>
                            <small>Appointment Time: ${appointment.appointment_time}</small>
                        </div>
                        <button class="btn btn-primary btn-sm" onclick="startSession(${appointment.id})">
                            Start Session
                        </button>
                    </div>
                `;
                queueList.appendChild(item);
            });
        })
        .catch(error => console.error('Error loading queue:', error));
}

function startSession(appointmentId) {
    if (!confirm('Are you sure you want to start this session?')) {
        return;
    }

    fetch('../studio/api/start_session.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            appointment_id: appointmentId
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Redirect to process session page
            window.location.href = `process_session.php?id=${appointmentId}`;
        } else {
            alert('Error starting session: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error starting session. Please try again.');
    });
}
