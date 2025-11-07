document.addEventListener('DOMContentLoaded', function () {
    loadCompletedSessions();
});

function loadCompletedSessions() {
    fetch('./api/get_completed_sessions.php')
    .then(response => response.json())
    .then(data => {
        const tbody = document.getElementById('completedSessionsList');
        tbody.innerHTML = '';

        if (data.length === 0) {
        tbody.innerHTML = `
            <tr>
            <td colspan="6" class="text-center text-muted">No completed sessions found.</td>
            </tr>`;
        return;
        }

        data.forEach(session => {
        const row = document.createElement('tr');
        row.innerHTML = `
            <td>${session.student_name}</td>
            <td>${session.appointment_date}</td>
            <td>${session.completed_date}</td>
            <td>${session.studio_staff}</td>
            <td>${session.photo_count}</td>
            <td>
                <button class="btn btn-sm btn-info me-1" onclick="viewPhotos(${session.id})">Photos</button>
                <button class="btn btn-sm btn-primary" data-id="${session.id}" onclick="viewDetails(${session.id})">Details</button>
            </td>
        `;
        tbody.appendChild(row);
        });
    })
    .catch(error => {
        console.error('Failed to load sessions:', error);
        document.getElementById('sessionAlert').classList.remove('d-none');
        document.getElementById('sessionAlert').textContent = 'Failed to load sessions.';
    });
}

function viewPhotos(sessionId) {
    fetch(`./api/get_session_photos.php?id=${sessionId}`)
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            if (data.error) {
                throw new Error(data.error);
            }

            const gallery = document.getElementById('photoGallery');
            gallery.innerHTML = '';

            if (data.photos.length === 0) {
                gallery.innerHTML = '<p class="text-muted">No photos uploaded for this session.</p>';
                return;
            }

            data.photos.forEach(photo => {
                const col = document.createElement('div');
                col.className = 'col-md-4 mb-3';
                col.innerHTML = `<img src="../uploads/${photo.photo_path}" class="img-fluid rounded shadow" alt="Session Photo">`;
                gallery.appendChild(col);
            });

            const modal = new bootstrap.Modal(document.getElementById('photoGalleryModal'));
            modal.show();
        })
        .catch(err => {
            console.error('Error loading photos:', err);
            alert('Failed to load session photos.');
        });
}


function viewDetails(appointmentId) {
    fetch(`./api/get_session_details.php?id=${appointmentId}`)
        .then(response => response.json())
        .then(data => {
            if (!data || !data.student_name) {
                document.getElementById('sessionDetails').innerHTML = '<p>Error loading details.</p>';
                return;
            }

            const detailsDiv = document.getElementById('sessionDetails');
            detailsDiv.innerHTML = `
                <div class="mb-3">
                    <strong>Student:</strong> ${data.student_name || 'N/A'}
                </div>
                <div class="mb-3">
                    <strong>Appointment Date:</strong> ${formatDateTime(data.preferred_date, data.preferred_time)}
                </div>
                <div class="mb-3">
                    <strong>Completed Date:</strong> ${formatDateTime(data.actual_date, data.actual_time)}
                </div>
                <div class="mb-3">
                    <strong>Studio Staff:</strong> ${data.staff_name || 'Unassigned'}
                </div>
                <div class="mb-3">
                    <strong>Notes:</strong> ${data.notes || 'No notes available'}
                </div>
            `;

            new bootstrap.Modal(document.getElementById('sessionDetailsModal')).show();
        })
        .catch(error => {
            console.error('Error loading session details:', error);
        });
}

function formatDateTime(date, time = '') {
    if (!date) return 'N/A';
    const dateObj = new Date(`${date}T${time || '00:00:00'}`);
    return dateObj.toLocaleString(undefined, {
        year: 'numeric', month: 'short', day: 'numeric',
        hour: '2-digit', minute: '2-digit'
    });
}
