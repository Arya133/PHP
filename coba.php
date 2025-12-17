<?php
session_start();

/* ================= CONFIG ================= */
$password = "admin123"; // ganti password
$rootDir = __DIR__;     // folder root (jangan ganti kalau ga paham)
/* ========================================= */

if (!isset($_SESSION['login'])) {
    if (isset($_POST['password']) && $_POST['password'] === $password) {
        $_SESSION['login'] = true;
        header("Location: ?");
        exit;
    }
    ?>
    <form method="post" style="margin-top:100px;text-align:center">
        <h2>PHP File Manager</h2>
        <input type="password" name="password" placeholder="Password" required>
        <br><br>
        <button type="submit">Login</button>
    </form>
    <?php
    exit;
}

$dir = isset($_GET['dir']) ? realpath($_GET['dir']) : $rootDir;
if ($dir === false || strpos($dir, $rootDir) !== 0) {
    $dir = $rootDir;
}

/* ============ ACTIONS ============ */
if (isset($_POST['upload'])) {
    move_uploaded_file($_FILES['file']['tmp_name'], $dir . "/" . $_FILES['file']['name']);
}

if (isset($_GET['delete'])) {
    $target = realpath($_GET['delete']);
    if (strpos($target, $rootDir) === 0) {
        is_dir($target) ? rmdir($target) : unlink($target);
    }
}

if (isset($_POST['rename'])) {
    rename($_POST['old'], $dir . "/" . $_POST['new']);
}
/* ================================= */
?>

<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>PHP File Manager</title>
<style>
body{font-family:Arial;background:#111;color:#eee}
a{color:#4da6ff;text-decoration:none}
table{width:100%}
td{padding:5px}
input,button{background:#222;color:#fff;border:1px solid #444;padding:5px}
</style>
</head>
<body>

<h3>📂 Current Dir: <?php echo htmlspecialchars($dir); ?></h3>

<form method="post" enctype="multipart/form-data">
    <input type="file" name="file" required>
    <button name="upload">Upload</button>
</form>

<br>

<table>
<tr><th>Name</th><th>Size</th><th>Action</th></tr>

<?php
foreach (scandir($dir) as $file) {
    if ($file === ".") continue;
    $path = $dir . "/" . $file;
    echo "<tr>";
    if (is_dir($path)) {
        echo "<td>📁 <a href='?dir=$path'>$file</a></td>";
        echo "<td>DIR</td>";
    } else {
        echo "<td>📄 $file</td>";
        echo "<td>" . filesize($path) . " bytes</td>";
    }
    echo "<td>
        <a href='?delete=$path' onclick='return confirm(\"Delete?\")'>❌</a>
        <form method='post' style='display:inline'>
            <input type='hidden' name='old' value='$path'>
            <input type='text' name='new' placeholder='Rename'>
            <button name='rename'>✏️</button>
        </form>
    </td>";
    echo "</tr>";
}
?>
</table>

<br>
<a href="?logout=1">Logout</a>

<?php
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: ?");
}
?>

</body>
</html>