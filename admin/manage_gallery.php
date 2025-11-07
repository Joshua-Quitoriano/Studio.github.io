<?php
require_once '../includes/header.php';
require_once '../config/database.php';

// Get all appointments with completed pictorials
$stmt = $conn->prepare("
    SELECT a.id, a.student_id, u.username, a.actual_date
    FROM appointments a
    JOIN users u ON a.student_id = u.id
    WHERE a.status = 'completed'
    ORDER BY a.actual_date DESC
");
$stmt->execute();
$appointments = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>

<div class="container py-4">
    <h2 class="mb-4">Gallery Management</h2>

    <!-- Upload Photos Form -->
    <div class="card mb-4">
        <div class="card-body">
            <h5 class="card-title">Upload Photos</h5>
            <form action="process_gallery.php" method="POST" enctype="multipart/form-data">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="student_id" class="form-label">Select Student</label>
                        <select class="form-select" id="student_id" name="student_id" required>
                            <option value="">Choose...</option>
                            <?php foreach ($appointments as $apt): ?>
                                <option value="<?php echo $apt['student_id']; ?>">
                                    <?php echo htmlspecialchars($apt['username']); ?> 
                                    (<?php echo date('M j, Y', strtotime($apt['actual_date'])); ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="photos" class="form-label">Select Photos</label>
                        <input type="file" class="form-control" id="photos" name="photos[]" multiple accept="image/*" required>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary">Upload Photos</button>
            </form>
        </div>
    </div>

    <!-- Photos Management -->
    <div class="card">
        <div class="card-body">
            <h5 class="card-title">Manage Uploaded Photos</h5>
            
            <!-- Student Filter -->
            <div class="mb-3">
                <label for="filterStudent" class="form-label">Filter by Student</label>
                <select class="form-select" id="filterStudent" onchange="filterPhotos()">
                    <option value="">All Students</option>
                    <?php foreach ($appointments as $apt): ?>
                        <option value="<?php echo $apt['student_id']; ?>">
                            <?php echo htmlspecialchars($apt['username']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Photos Grid -->
            <div id="photosGrid" class="row g-4">
                <!-- Photos will be loaded here via AJAX -->
            </div>
        </div>
    </div>
</div>

<!-- Photo Actions Modal -->
<div class="modal fade" id="photoActionsModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Photo Actions</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <img id="modalPhoto" src="" alt="Selected photo" class="img-fluid mb-3">
                <div class="d-grid gap-2">
                    <button type="button" class="btn btn-success" onclick="approvePhoto()">
                        <i class="fas fa-check"></i> Approve Photo
                    </button>
                    <button type="button" class="btn btn-danger" onclick="deletePhoto()">
                        <i class="fas fa-trash"></i> Delete Photo
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
let currentPhotoId = null;

function filterPhotos() {
    const studentId = document.getElementById('filterStudent').value;
    loadPhotos(studentId);
}

function loadPhotos(studentId = '') {
    fetch(`get_photos.php?student_id=${studentId}`)
        .then(response => response.json())
        .then(data => {
            const grid = document.getElementById('photosGrid');
            grid.innerHTML = data.photos.map(photo => `
                <div class="col-md-4 col-lg-3">
                    <div class="card h-100">
                        <img src="${photo.image_path}" 
                             class="card-img-top" 
                             alt="Student Photo"
                             style="height: 200px; object-fit: cover;"
                             onclick="showPhotoActions(${photo.id}, '${photo.image_path}')">
                        <div class="card-body">
                            <p class="card-text">
                                Status: <span class="badge bg-${photo.is_approved ? 'success' : 'warning'}">
                                    ${photo.is_approved ? 'Approved' : 'Pending'}
                                </span>
                            </p>
                        </div>
                    </div>
                </div>
            `).join('');
        });
}

function showPhotoActions(photoId, imagePath) {
    currentPhotoId = photoId;
    const modal = new bootstrap.Modal(document.getElementById('photoActionsModal'));
    document.getElementById('modalPhoto').src = imagePath;
    modal.show();
}

function approvePhoto() {
    if (!currentPhotoId) return;
    
    fetch('process_gallery.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `action=approve&photo_id=${currentPhotoId}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            bootstrap.Modal.getInstance(document.getElementById('photoActionsModal')).hide();
            filterPhotos();
        } else {
            alert('Failed to approve photo: ' + data.message);
        }
    });
}

function deletePhoto() {
    if (!currentPhotoId || !confirm('Are you sure you want to delete this photo?')) return;
    
    fetch('process_gallery.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `action=delete&photo_id=${currentPhotoId}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            bootstrap.Modal.getInstance(document.getElementById('photoActionsModal')).hide();
            filterPhotos();
        } else {
            alert('Failed to delete photo: ' + data.message);
        }
    });
}

// Load all photos on page load
document.addEventListener('DOMContentLoaded', () => loadPhotos());
</script>