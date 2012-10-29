<?php

//TODO: make full / half selectable
$half = false;

if (!isset($skin_slug))
{
  header('Location: /projects/win7boot/skins/');
  exit();
}

require_once 'skins/functions.inc';

$qs = trim($_SERVER['QUERY_STRING']);
$special = $qs == 'bs7' || $qs == 'exe' || $qs == 'img' || $qs == 'xml' || strncmp($qs, '~', 1) == 0;

$db = dbconnect();
$stmt = $db->prepare('SELECT id, name, user_id, viewed_count, download_count, url, `desc`, license, original_size, LENGTH(original) FROM skins WHERE slug=? LIMIT 1');
$stmt->bind_param('s', $skin_slug); $stmt->execute();
$stmt->bind_result($id, $name, $userid, $viewed_count, $download_count, $url, $desc, $license, $bs7_size, $bs7_compressed_size);
if (!$stmt->fetch())
{
  $theme->display('404');
  exit();
}
$stmt->close();

$flag_count = get_flag_count($db, 'skin', $id);
if ($flag_count > 10) // 10 flags constitutes disabling
{
  include 'skins/flagged-skin.inc';
  exit();
}

$stmt = $db->prepare('SELECT name, slug FROM users WHERE id=? LIMIT 1');
$stmt->bind_param('i', $userid); $stmt->execute();
$stmt->bind_result($author, $user_slug); $stmt->fetch();
$stmt->close();

$name = htmlentities(trim($name));
$url = htmlentities(trim($url));
$author = htmlentities(trim($author));
$desc_raw = htmlentities(strip_tags($desc));
$license_raw = htmlentities(strip_tags($license));
$desc = format_long_text($desc);
$license = format_long_text($license);
$installer_path = skins_file_path('installer.exe');

if ($special)
{
  include 'skins/get.inc';
  exit();
}

$stmt = $db->prepare('SELECT LENGTH(full) FROM skin_pngs WHERE skin_id=? AND name=\'PREVIEW-SMALL\' LIMIT 1');
$stmt->bind_param('i', $id); $stmt->execute();
$stmt->bind_result($img_len); $stmt->fetch();
$stmt->close();

$exe_size = filesize($installer_path)+$bs7_compressed_size+strlen($name)+strlen($author)+strlen($url)+strlen($desc_raw)+strlen($license_raw)+170+$img_len;

?>
<?php $theme->display('header'); ?>
<div id="content" class="skins">
	<div id="post-<?php echo $post->id; ?> skin-<?php echo $id; ?>" class="<?php echo $post->statusname; ?>">
    <div class="entry-meta"><a href="/projects/win7boot/skins/myaccount/">My Account</a></div>
		<div class="entry-head"><h1 class="entry-title"><?php echo $post->title_out; ?></h1></div>
    <h2 class="skin-name"><?php echo $name; ?> by <a class="skin-author" href="/projects/win7boot/skins/user/<?php echo $user_slug; ?>/"><?php echo $author; ?></a></h2>
	</div>
  <div style="width:740px;height:0;overflow:visible;"><img alt="" src="?img" style="width:100%"></div>
	<!--[if !IE]>--><object type="application/x-shockwave-flash" data="/projects/win7boot/skins/display.swf" width="740" height="555" id="skin-display"><!--<![endif]-->
	<!--[if IE]><object classid="clsid:d27cdb6e-ae6d-11cf-96b8-444553540000" width="740" height="555" id="skin-display" align="middle"><![endif]-->
		<param name="movie" value="/projects/win7boot/skins/display.swf">
		<param name="bgcolor" value="#000000">
		<param name="scale" value="showall">
    <param name="FlashVars" value="bs7=<?php echo $skin_slug; if ($half) echo '&amp;half=true'; ?>">
		<a href="http://www.adobe.com/go/getflash"><img src="http://www.adobe.com/images/shared/download_buttons/get_flash_player.gif" alt="Get Adobe Flash player"></a>
	</object>
  <p>This skin has been viewed <?php echo $viewed_count.' time'.s($viewed_count); ?> and downloaded <?php echo $download_count.' time'.s($download_count); ?></p>
  <a href="?bs7" class="bs7-download">Download Boot Skin (<?php echo SpecialTagsFormater::size($bs7_size); ?>)</a>
  <a href="?exe" class="exe-download">Download Installer (<?php echo SpecialTagsFormater::size($exe_size); ?>)</a>
<?php
  if ($url)     echo "<p>See <a href='$url'>$url</a> for more information</p>\n";
  if ($desc)    echo "<div class='skin-desc'>\n$desc\n</div>\n";
  if ($license) echo "<p>This boot skin is available under the following license:</p><div class='skin-license'>\n$license\n</div>\n";
?>
</div>
<?php $theme->display('footer'); ?>