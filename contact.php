<?php

$key = 'fairly plain for email';

function encrypt($string)
{
    global $key;
    return base64_encode(mcrypt_encrypt(MCRYPT_RIJNDAEL_256, md5($key), $string, MCRYPT_MODE_CBC, md5(md5($key))));
}

function decrypt($encrypted)
{
    global $key;
    return rtrim(mcrypt_decrypt(MCRYPT_RIJNDAEL_256, md5($key), base64_decode($encrypted), MCRYPT_MODE_CBC, md5(md5($key))), "\0");
}

header('Location: mailto:'.decrypt('7pIgEmKlf9MQzu7Gj1SC3z4Nf8nueelMEiIFatZQhx8='));

$prev = $_SERVER['HTTP_REFERER'];
$host = $_SERVER['HTTP_HOST'];
$pos = stripos($prev, $host);
if (!$prev || $pos < 7 || $pos > 12) { $prev = false; }
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html><head><title>Coder for Life - Contact</title><?php
if ($prev) { echo "<meta HTTP-EQUIV=\"REFRESH\" content=\"1; url=$prev\">"; }
?><script type="text/javascript">function goback(){window.history.back();}</script></head><body onLoad="setTimeout(goback, 500)">
<p>You mailto program should have opened. This will go back to the page you came from in a moment. If not please <a href="<?php echo $prev ? $prev : 'javascript:goback()'; ?>" rel="nofollow">click here</a>.</p>
</body></html>