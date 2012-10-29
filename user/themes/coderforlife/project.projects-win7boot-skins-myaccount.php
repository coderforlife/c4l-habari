<?php

require_once 'skins/functions.inc';
require_once 'skins/multipart.inc';

if (!$logged_in)
{
  include 'skins/login.inc';
  exit();
}

$userid = $_SESSION['skins']['userid'];

$db = dbconnect();
$stmt = $db->prepare('SELECT name, slug, email, public_email, url, `desc` FROM users WHERE id=? LIMIT 1');
$stmt->bind_param('i', $userid);
$stmt->execute();
$stmt->bind_result($name, $slug, $email, $public_email, $url, $desc);
$stmt->fetch();
$stmt->close();

$flag_count = get_flag_count($db, 'user', $userid);
$skins_flag_count = get_total_skin_flag_count($db, $userid);

$email = htmlentities(trim($email));
$url = htmlentities(trim($url));
$desc = format_long_text($desc);

?>
<?php $theme->display('header'); ?>
<div id="content" class="skins">
	<div id="post-<?php echo $post->id; ?>" class="<?php echo $post->statusname; ?>">
    <div class="entry-meta"><a href="/projects/win7boot/skins/logout/">Logout</a></div>
		<div class="entry-head"><h1 class="entry-title"><?php echo $post->title_out; ?></h1></div>
  	<div class="entry-content"><?php echo $post->content_out; ?></div>
	</div>

<b><a href="/projects/win7boot/skins/user/<?php echo $slug; ?>/"><?php echo $name; ?></a></b><br>
<?php
if ($flag_count > 0)       echo '<p class="error">Your account profile has been flagged by at least one visitor and will have to be moderated. In the mean time your account may become unavailable to visitors.</p>';
if ($skins_flag_count > 0) echo '<p class="error">At least one of your skins has been flagged by at least one visitor and will have to be moderated. In the mean time they may become unavailable to visitors.</p>';
?>
<a href="javascript:void(0);">Change Password</a><br>
<!--Email: <?php echo "<a href='mailto:$email'>$email</a>"; ?> <?php if ($public_email*1 == 1) echo '(Shown publicly)'; ?> <a href="javascript:void(0);">Update</a><br>-->
<div class="editable" onclick="edit(this, 'email', true);"><span class="editable-name">Email:</span> <span class="editable-content"><?php echo $email ?></span></div>
<div class="editable" onclick="edit(this, 'url', true);"><span class="editable-name">URL:</span> <span class="editable-content"><?php echo $url ?></span></div>
<div class="editable" onclick="edit(this, 'desc', false);"><span class="editable-name">Description:</span> <div class="editable-content"><?php echo $desc ?></div></div>

<?php
$stmt = $db->prepare('SELECT id, name, slug, url, `desc`, license FROM skins WHERE user_id=?');
$stmt->bind_param('i', $userid);
$stmt->execute();
$stmt->store_result();
if ($stmt->num_rows > 0)
{
  echo '<h2>Your Skins</h2>';

  $stmt->bind_result($id, $name, $slug, $url, $desc, $license);
  while ($stmt->fetch())
  {
    $flag_count = get_flag_count($db, 'skin', $id);
    $url = htmlentities(trim($url));
    $desc = format_long_text($desc);
    $license = format_long_text($license);
?>
<div class="skin">
<div class="skin-title">+ <a href="/projects/win7boot/skins/skin/<?php echo $slug; ?>/"><?php echo htmlentities($name); ?></a><?php if ($flag_count > 0) echo ' <span class="error">Flagged</span>'; ?></div>
<div class="skin-details">
URL: <?php if ($url) echo "<a href='$url'>$url</a>"; ?> <a href="javascript:void(0);"><?php echo $url ? 'Update' : 'Add'; ?></a><br>
Description: <a href="javascript:void(0);"><?php echo $desc ? 'Update</a>'.$desc : 'Add</a><br>'; ?>
License: <a href="javascript:void(0);"><?php echo $license ? 'Update</a>'.$license : 'Add</a><br>'; ?>
</div>
</div>
<?php
  }
}
$stmt->close();

echo '<h2>Add a Skin</h2>';

$errors = array();

if (array_key_exists('skin-name', $_POST) && array_key_exists('bs7', $_FILES))
{
  $name = trim($_POST['skin-name']);
  $slug = slugify($name);
  $url = trim($_POST['skin-url']);
  $desc = trim($_POST['skin-desc']);
  $license = trim($_POST['skin-license']);
  $bs7_file_upload = $_FILES['bs7'];

  $db = dbconnect();

  if ($name == '' || $slug == '')
    $errors[] = 'The name must contain at least one alpha-numeric character';
  else if (!$db->connect_error && name_already_exists($db, 'skins', $name, $slug))
    $errors[] = 'The name (or something very similar) already exists';

  if ($bs7_file_upload['error'] != UPLOAD_ERR_OK)
  {
    switch ($bs7_file_upload['error'])
    {
    //case UPLOAD_ERR_OK: break;
    case UPLOAD_ERR_NO_FILE: $errors[] = 'Need to upload a bootskin file'; break;
    case UPLOAD_ERR_PARTIAL: $errors[] = 'The bootskin file did not completely upload, please try again'; break;
    case UPLOAD_ERR_INI_SIZE: case UPLOAD_ERR_FORM_SIZE: $errors[] = 'The bootskin file is too large, maximum size is XXXX'; break;
    default: $errors[] = 'Server failed to properly upload the bootskin file'; break;
    }
  }
  else if ($bs7_file_upload['size'] < 64)
    $errors[] = 'The bootskin file is not large enough';
  else
  {
    $bs7_orig_size = filesize($bs7_file_upload['tmp_name']);
    $bs7_orig = file_get_contents($bs7_file_upload['tmp_name']);
    $bs7_file = new MultipartFile($bs7_file_upload['tmp_name']);

    $preview_cids = array();

    $xml = $bs7_file->get_xml();
    $xmldoc = new DOMDocument();
    if (!$xmldoc->loadXML($xml) || !$xmldoc->schemaValidate(skins_file_path('bs7.xsd')) || trim($xmldoc->documentElement->getAttribute('version')) != '1')
      $errors[] = 'Invalid BS7 file uploaded';
    else
    {
      $winload = get_xml_child($xmldoc->documentElement, 'Winload');
      $winresume = get_xml_child($xmldoc->documentElement, 'Winresume');

      $winload_cids = get_xml_cids($winload);
      $cids = ($winresume === null) ? $winload_cids : array_merge($winload_cids, get_xml_cids($winresume));
      foreach ($cids as $cid)
        if (!$bs7_file->has_part($cid) || strncmp($bs7_file->get_part_type($cid), 'image/', 6) !== 0)
        {
          $errors[] = 'BS7 file is missing images'; break;
        }

      if (count($errors) == 0)
      {
        $null = NULL;

        $stmt = $db->prepare('INSERT INTO skins (name, slug, user_id, url, `desc`, license, original_size, original, xml, created) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())');
        $stmt->bind_param('ssisssibb', $name, $slug, $userid, $url, $null, $null, $bs7_orig_size, $null, $null);
        send_large_data($stmt, 4, $desc, $db);
        send_large_data($stmt, 5, $license, $db);
        send_large_data($stmt, 7, gzcompress($bs7_orig, 9), $db);
        if (!$bs7_file->is_xml_only())
          send_large_data($stmt, 8, gzcompress($xml, 9), $db);
        $stmt->execute();
        $skin_id = $db->insert_id*1;
        $stmt->close();

        $stmt = $db->prepare("INSERT INTO skin_pngs (skin_id, name, full, half) VALUES ($skin_id, ?, ?, ?)");
        foreach ($cids as $cid)
        {
          $name = '~'.$cid;
          $full = ensure_is_png($bs7_file->get_part($cid));
          $half = shrink_image($full);
          $stmt->bind_param('sbb', $name, $null, $null);
          send_large_data($stmt, 1, $full, $db);
          send_large_data($stmt, 2, $half, $db);
          $stmt->execute();
        }

        // add preview to database
        $im = generate_preview($winload, $bs7_file, 61, NULL);
        $full  = imagepng_string($im);
        $half  = shrink_image($im);
        $small = shrink_image($im, 0.4225);
        imagedestroy($im);

        $name = 'PREVIEW';
        $stmt->bind_param('sbb', $name, $null, $null);
        send_large_data($stmt, 1, $full, $db);
        send_large_data($stmt, 2, $half, $db);
        $stmt->execute();

        $name = 'PREVIEW-SMALL';
        $stmt->bind_param('sbb', $name, $null, $null);
        file_put_contents(skins_file_path('small.png'), $small);
        send_large_data($stmt, 1, $small, $db);
        $stmt->execute();

        $stmt->close();

        $_POST = array();
      }
    }
  }
}
?>
<form name="add-skin" method="post" enctype="multipart/form-data">
<?php
foreach ($errors as $error)
  echo '<p class="error">'.htmlentities($error).'</p>';
?>
<label for="skin-name">Name: </label><input type="text" name="skin-name" id="skin-name"<?php echoValFromPOST('skin-name'); ?>> (will be used to create a URL for the skin)<br>
<label for="bs7">Skin: </label><input type="file" name="bs7" id="bs7"> (only BS7 files are supported)<br>
<label for="skin-url">URL: </label><input type="text" name="skin-url" id="skin-url"<?php echoValFromPOST('skin-url'); ?>><br>
<label for="skin-desc">Description: </label><textarea name="skin-desc" id="skin-desc"><?php htmlentities(POST('skin-desc')); ?></textarea><br>
<label for="skin-license">License: </label><textarea name="skin-license" id="skin-license"><?php htmlentities(POST('skin-license')); ?></textarea><br>
<input type="Submit" value="Upload">
</form>

</div>
<?php $theme->display('footer'); ?>