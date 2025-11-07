document.addEventListener('DOMContentLoaded', function() {
    const urlParams = new URLSearchParams(window.location.search);
    const appointmentId = urlParams.get('id');
    
    if (appointmentId) {
        document.getElementById('appointment_id').value = appointmentId;
        loadSessionDetails(appointmentId);
    }
    
    initializePhotoUpload();
});

function loadSessionDetails(appointmentId) {
    fetch(`../studio/api/get_session_details.php?id=${appointmentId}`)
        .then(response => response.json())
        .then(data => {
            const detailsDiv = document.getElementById('sessionDetails');

            if (!data.success || !data.data) {
                detailsDiv.innerHTML = `<p>Error loading session details.</p>`;
                return;
            }
            const appt = data.data;

            detailsDiv.innerHTML = `
                <p><strong>Student:</strong> ${appt.student_name || 'N/A'}</p>
                <p><strong>Appointment Time:</strong> ${appt.appointment_time || 'N/A'}</p>
                <p><strong>Notes:</strong> ${appt.notes || 'N/A'}</p>
            `;
        })
        .catch(error => {
            console.error('Error loading session details:', error);
        });
}


function initializePhotoUpload() {
    const form = document.getElementById('photoUploadForm');
    
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(form);
        
        fetch('../studio/api/upload_photos.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Photos uploaded successfully!');
                
                // Update appointment status
                return fetch('../studio/api/update_status.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        appointment_id: formData.get('appointment_id'),
                        status: formData.get('status')
                    })
                });
            } else {
                throw new Error(data.error || 'Upload failed');
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                window.location.href = '../studio/dashboard.php';
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error: ' + error.message);
        });
    });
}
