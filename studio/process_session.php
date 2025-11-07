<?php
session_start();
require_once '../config/database.php';
require_once '../includes/auth_helper.php';

checkStudioAccess();

include '../includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <h2 class="mt-4">Process Session</h2>
            <div class="row">
                <div class="col-md-8">
                    <div class="card">
                        <div class="card-header">
                            <h5>Upload Photos</h5>
                        </div>
                        <div class="card-body">
                            <form id="photoUploadForm" enctype="multipart/form-data">
                                <input type="hidden" id="appointment_id" name="appointment_id">
                                <div class="mb-3">
                                    <label for="photos" class="form-label">Select Photos</label>
                                    <input type="file" class="form-control" id="photos" name="photos[]" multiple accept="image/*" required>
                                </div>
                                <div class="mb-3">
                                    <label for="status" class="form-label">Session Status</label>
                                    <select class="form-control" id="status" name="status">
                                        <option value="processing">Processing</option>
                                        <option value="completed">Completed</option>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label for="notes" class="form-label">Notes</label>
                                    <textarea class="form-control" id="notes" name="notes" rows="3"></textarea>
                                </div>
                                <button type="submit" class="btn btn-primary">Save & Upload</button>
                            </form>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-header">
                            <h5>Session Details</h5>
                        </div>
                        <div class="card-body" id="sessionDetails">
                            <!-- Session details will be loaded here -->
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="../includes/process_session.js"></script>
