<?php
session_start();
require_once '../config/database.php';
require_once '../includes/auth_helper.php';

checkStudioAccess();

include '../includes/header.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Studio Dashboard</title>
    <style>
        .stat-card {
            transition: transform 0.2s;
            border-radius: 15px !important;
            min-height: 140px;
            background-size: cover;
            background-position: center;
            position: relative;
            overflow: hidden;
        }
        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: inherit;
            filter: brightness(0.6);
            z-index: 1;
        }
        .stat-card:hover {
            transform: translateY(-5px);
        }
        .stat-card .card-body {
            position: relative;
            z-index: 2;
            padding: 1.5rem;
            height: 100%;
        }
        .stat-card h5 {
            font-size: 1.1rem;
            font-weight: 500;
            margin-bottom: 1rem;
            text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.5);
        }
        .stat-card h2 {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.3);
        }
        .stat-card p {
            font-size: 1rem;
            opacity: 0.9;
            margin-bottom: 0;
            text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.5);
        }
        .stat-card i {
            font-size: 2rem;
            opacity: 0.9;
            margin-bottom: 1rem;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.3);
        }
        .stat-card.pending {
            background-image: url('../includes/card-warning.png');
        }
        .stat-card.completed {
            background-image: url('../includes/card-success.png');
        }
    </style>
</head>
<body>

<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col">
            <h2 class="mb-0"><i class="fas fa-photo-video me-2"></i> Studio Dashboard</h2>
            <p class="text-muted">Monitor and manage studio sessions</p>
        </div>
    </div>
            
    <!-- Stats Cards -->
    <div class="row g-4 mb-4">
        <div class="col-md-6">
            <div class="card stat-card pending text-white shadow-sm">
                <div class="card-body text-center d-flex flex-column justify-content-center">
                    <i class="fas fa-clock mb-2"></i>
                    <h5 class="card-title">Pending Sessions</h5>
                    <h2 class="card-text" id="pendingSessionCount">0</h2>
                    <p class="card-text">Waiting to be processed</p>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card stat-card completed text-white shadow-sm">
                <div class="card-body text-center d-flex flex-column justify-content-center">
                    <i class="fas fa-check-circle mb-2"></i>
                    <h5 class="card-title">Completed Sessions</h5>
                    <h2 class="card-text" id="completedSessionCount">0</h2>
                    <p class="card-text">Successfully processed</p>
                </div>
            </div>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-md-6">
            <div class="input-group">
                <input type="text" class="form-control" id="searchPendingSession" placeholder="Search by name or student number">
                <button class="btn btn-outline-primary" type="button" onclick="applyFilters()">
                    <i class="fas fa-search"></i>
                </button>
            </div>
        </div>
    </div>

    <!-- Queue Section -->
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white py-3">
            <h5 class="mb-0">Current Queue</h5>
        </div>
        <div class="card-body">
            <div id="queueList" class="list-group">
                <!-- Queue items will be loaded here dynamically -->
            </div>
        </div>
    </div>
</div>

<script src="../includes/studio.js"></script>
</body>
</html>
