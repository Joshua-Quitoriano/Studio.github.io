<?php
session_start();
require_once '../config/database.php';
require_once '../includes/header.php';
require_once '../includes/auth_helper.php';

checkStudentAccess();

// Get student's photos from their studio sessions
$query = "SELECT sp.*, a.preferred_date, a.actual_date 
          FROM studio_photos sp
          JOIN appointments a ON sp.appointment_id = a.id
          WHERE a.student_id = ? 
          AND a.status = 'completed'
          ORDER BY sp.uploaded_at DESC";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$photos = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>

<div class="container py-4">
    <h2 class="mb-4">My Photo Gallery</h2>
    
    <?php if (empty($photos)): ?>
    <div class="alert alert-info">
        No photos available yet. Complete a studio session to see your photos here.
    </div>
    <?php else: ?>
    <div class="row">
        <?php foreach ($photos as $photo): ?>
        <div class="col-md-4 col-sm-6 mb-4">
            <div class="card h-100">
                <img src="../<?php echo htmlspecialchars($photo['file_path']); ?>" 
                     class="card-img-top" 
                     alt="Studio Photo"
                     style="object-fit: cover; height: 200px;"
                     onclick="viewPhoto('<?php echo htmlspecialchars($photo['file_path']); ?>')">
                <div class="card-body">
                    <p class="card-text">
                        <small class="text-muted">
                            Session Date: <?php echo date('M d, Y', strtotime($photo['actual_date'])); ?>
                        </small>
                    </p>
                    <div class="btn-group w-100">
                        <button class="btn btn-info btn-sm" 
                                onclick="viewPhoto('<?php echo htmlspecialchars($photo['file_path']); ?>')">
                            <i class="fas fa-eye"></i> View
                        </button>
                        <a href="../student/api/download_photo.php?id=<?php echo $photo['id']; ?>" 
                           class="btn btn-primary btn-sm">
                            <i class="fas fa-download"></i> Download
                        </a>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>

<!-- Photo View Modal -->
<div class="modal fade" id="photoViewModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Photo View</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center p-0">
                <img id="modalImage" src="" alt="Full size photo" class="img-fluid">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
function viewPhoto(imagePath) {
    const modal = new bootstrap.Modal(document.getElementById('photoViewModal'));
    document.getElementById('modalImage').src = '../' + imagePath;
    modal.show();
}
</script>