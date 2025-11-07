<?php
session_start();
require_once '../includes/auth_helper.php';
require_once '../includes/header.php';

checkAdminAccess();
?>

<div class="container py-4">
    <div class="row mb-4">
        <div class="col">
            <h2><i class="fas fa-check-circle me-2"></i> Completed Sessions</h2>
            <p class="text-muted">Manage completed sessions</p>
        </div>
    </div>
    
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover" id="completedSessionsTable">
                    <thead>
                        <tr>
                            <th>Student Name</th>
                            <th>Appointment Date</th>
                            <th>Completed Date</th>
                            <th>Studio Staff</th>
                            <th>Photos</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="completedSessionsList">
                        <tr>
                            <td colspan="6" class="text-center text-muted">Loading sessions...</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Photo Gallery Modal -->
    <div class="modal fade" id="photoGalleryModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Session Photos</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row" id="photoGallery">
                        <!-- Photos will be loaded here -->
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Session Details Modal -->
    <div class="modal fade" id="sessionDetailsModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Session Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div id="sessionDetails">
                        <!-- Details will be loaded here -->
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="./completed_sessions.js"></script>