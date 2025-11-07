<?php
require_once '../includes/security.php';
require_once '../config/database.php';

class BackupSystem {
    private $conn;
    private $backupDir;
    
    public function __construct($conn) {
        $this->conn = $conn;
        $this->backupDir = dirname(__DIR__) . '/backups';
        
        if (!file_exists($this->backupDir)) {
            mkdir($this->backupDir, 0755, true);
        }
    }
    
    // Create database backup
    public function backupDatabase() {
        $timestamp = date('Y-m-d_H-i-s');
        $filename = $this->backupDir . "/db_backup_$timestamp.sql";
        
        // Get all tables
        $tables = [];
        $result = $this->conn->query('SHOW TABLES');
        while ($row = $result->fetch_row()) {
            $tables[] = $row[0];
        }
        
        $return = '';
        
        // Iterate tables
        foreach ($tables as $table) {
            $result = $this->conn->query("SELECT * FROM $table");
            $numFields = $result->field_count;
            
            $return .= "DROP TABLE IF EXISTS $table;";
            
            // Get create table syntax
            $row2 = $this->conn->query("SHOW CREATE TABLE $table")->fetch_row();
            $return .= "\n\n" . $row2[1] . ";\n\n";
            
            // Get table data
            while ($row = $result->fetch_row()) {
                $return .= "INSERT INTO $table VALUES(";
                for ($j = 0; $j < $numFields; $j++) {
                    $row[$j] = addslashes($row[$j]);
                    $row[$j] = str_replace("\n", "\\n", $row[$j]);
                    if (isset($row[$j])) {
                        $return .= '"' . $row[$j] . '"';
                    } else {
                        $return .= '""';
                    }
                    if ($j < ($numFields - 1)) {
                        $return .= ',';
                    }
                }
                $return .= ");\n";
            }
            $return .= "\n\n";
        }
        
        // Save file
        file_put_contents($filename, $return);
        return $filename;
    }
    
    // Backup uploaded files
    public function backupUploads() {
        $timestamp = date('Y-m-d_H-i-s');
        $filename = $this->backupDir . "/uploads_backup_$timestamp.zip";
        
        $zip = new ZipArchive();
        if ($zip->open($filename, ZipArchive::CREATE) !== true) {
            return false;
        }
        
        $uploadsDir = dirname(__DIR__) . '/uploads';
        $this->addFolderToZip($uploadsDir, $zip);
        
        $zip->close();
        return $filename;
    }
    
    // Helper function to add folder to zip
    private function addFolderToZip($folder, $zipFile, $subfolder = '') {
        if ($handle = opendir($folder)) {
            while (false !== ($entry = readdir($handle))) {
                if ($entry != "." && $entry != "..") {
                    $filePath = "$folder/$entry";
                    
                    // If it's a file, add it to the zip
                    if (is_file($filePath)) {
                        $zipFile->addFile($filePath, $subfolder . $entry);
                    } elseif (is_dir($filePath)) {
                        // If it's a folder, recurse
                        $this->addFolderToZip($filePath, $zipFile, $subfolder . $entry . '/');
                    }
                }
            }
            closedir($handle);
        }
    }
    
    // Clean old backups
    public function cleanOldBackups($days = 7) {
        $files = glob($this->backupDir . '/*');
        $now = time();
        
        foreach ($files as $file) {
            if (is_file($file)) {
                if ($now - filemtime($file) >= (60 * 60 * 24 * $days)) {
                    unlink($file);
                }
            }
        }
    }
    
    // Restore database from backup
    public function restoreDatabase($backupFile) {
        if (!file_exists($backupFile)) {
            throw new Exception('Backup file not found');
        }
        
        $sql = file_get_contents($backupFile);
        $queries = explode(';', $sql);
        
        $this->conn->begin_transaction();
        
        try {
            foreach ($queries as $query) {
                if (trim($query) != '') {
                    $this->conn->query($query);
                }
            }
            
            $this->conn->commit();
            return true;
        } catch (Exception $e) {
            $this->conn->rollback();
            throw $e;
        }
    }
    
    // List available backups
    public function listBackups() {
        $backups = [];
        $files = glob($this->backupDir . '/*');
        
        foreach ($files as $file) {
            if (is_file($file)) {
                $backups[] = [
                    'filename' => basename($file),
                    'size' => filesize($file),
                    'created' => date('Y-m-d H:i:s', filemtime($file))
                ];
            }
        }
        
        return $backups;
    }
}

// Only allow admin access
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: /login.php');
    exit();
}

// Handle backup actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $backup = new BackupSystem($conn);
    
    try {
        switch ($_POST['action']) {
            case 'backup_db':
                $file = $backup->backupDatabase();
                $response = ['success' => true, 'message' => 'Database backup created: ' . basename($file)];
                break;
                
            case 'backup_uploads':
                $file = $backup->backupUploads();
                $response = ['success' => true, 'message' => 'Uploads backup created: ' . basename($file)];
                break;
                
            case 'restore_db':
                if (!isset($_POST['backup_file'])) {
                    throw new Exception('No backup file selected');
                }
                $file = $backup->backupDir . '/' . basename($_POST['backup_file']);
                $backup->restoreDatabase($file);
                $response = ['success' => true, 'message' => 'Database restored successfully'];
                break;
                
            case 'clean_old':
                $backup->cleanOldBackups();
                $response = ['success' => true, 'message' => 'Old backups cleaned successfully'];
                break;
                
            default:
                throw new Exception('Invalid action');
        }
    } catch (Exception $e) {
        $response = ['success' => false, 'message' => $e->getMessage()];
    }
    
    header('Content-Type: application/json');
    echo json_encode($response);
    exit();
}

// Display backup interface
$backup = new BackupSystem($conn);
$backups = $backup->listBackups();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Backup</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container py-4">
        <h2>System Backup</h2>
        
        <div class="row mb-4">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Create Backup</h5>
                    </div>
                    <div class="card-body">
                        <button class="btn btn-primary me-2" onclick="createBackup('backup_db')">
                            Backup Database
                        </button>
                        <button class="btn btn-success" onclick="createBackup('backup_uploads')">
                            Backup Uploads
                        </button>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Maintenance</h5>
                    </div>
                    <div class="card-body">
                        <button class="btn btn-warning me-2" onclick="cleanOldBackups()">
                            Clean Old Backups
                        </button>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Available Backups</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Filename</th>
                                <th>Size</th>
                                <th>Created</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($backups as $backup): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($backup['filename']); ?></td>
                                <td><?php echo number_format($backup['size'] / 1024, 2); ?> KB</td>
                                <td><?php echo $backup['created']; ?></td>
                                <td>
                                    <?php if (strpos($backup['filename'], 'db_backup') === 0): ?>
                                    <button class="btn btn-sm btn-info" 
                                            onclick="restoreBackup('<?php echo htmlspecialchars($backup['filename']); ?>')">
                                        Restore
                                    </button>
                                    <?php endif; ?>
                                    <a href="/backups/<?php echo urlencode($backup['filename']); ?>" 
                                       class="btn btn-sm btn-secondary">
                                        Download
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function createBackup(action) {
            if (!confirm('Are you sure you want to create a backup?')) return;
            
            fetch('backup.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=' + action
            })
            .then(response => response.json())
            .then(data => {
                alert(data.message);
                if (data.success) location.reload();
            })
            .catch(error => alert('Error: ' + error));
        }
        
        function restoreBackup(filename) {
            if (!confirm('Are you sure you want to restore this backup? This will overwrite current data!')) return;
            
            fetch('backup.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=restore_db&backup_file=' + encodeURIComponent(filename)
            })
            .then(response => response.json())
            .then(data => {
                alert(data.message);
                if (data.success) location.reload();
            })
            .catch(error => alert('Error: ' + error));
        }
        
        function cleanOldBackups() {
            if (!confirm('Are you sure you want to clean old backups?')) return;
            
            fetch('backup.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=clean_old'
            })
            .then(response => response.json())
            .then(data => {
                alert(data.message);
                if (data.success) location.reload();
            })
            .catch(error => alert('Error: ' + error));
        }
    </script>
</body>
</html>
