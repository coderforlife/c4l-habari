<?php

$errors = array();
if (array_key_exists('pword1', $_POST) && array_key_exists('pword2', $_POST))
{
  $pword = $_POST['pword1'];
  if (strlen($pword) < 6)
  {
    $errors[] = 'The password must be at least 6 characters long';
  }
  if ($pword != $_POST['pword2'])
  {
    $errors[] = 'Passwords much match';
  }
  if (count($errors) == 0)
  {
    $db = dbconnect();
    $stmt = $db->prepare('UPDATE users SET password=PASSWORD(?), reset_password=0 WHERE id=? LIMIT 1');
    $userid = $_SESSION['skins']['userid'];
    $stmt->bind_param('si', $pword, $userid);
    $stmt->execute();
    $stmt->close();
    $_SESSION['skins']['needs_pw_change'] = false;
    return;
  }
}

?>
<?php $theme->display('header'); ?>
<div id="content" class="skins">
	<div id="post-<?php echo $post->id; ?>" class="<?php echo $post->statusname; ?>">
    <div class="entry-meta"><a href="/projects/win7boot/skins/logout/">Logout</a></div>
		<div class="entry-head"><h1 class="entry-title"><?php echo $post->title_out; ?></h1></div>
  	<div class="entry-content"><?php echo $post->content_out; ?></div>
	</div>

<br>
<center>

<span style="display:inline-block;vertical-align:top;">
<form name="resetpw" method="post">
<fieldset id="resetpw"><legend>Reset Password</legend>
<?php
foreach ($errors as $error)
  echo '<p class="error">'.htmlentities($error).'</p>';
?>
<label for="pword1">New Password: </label><input type="password" name="pword1" id="pword1"><br>
<label for="pword2">Retype Password: </label><input type="password" name="pword2" id="pword2"><br>
<input type="submit" value="Set Password">
</fieldset>
</form>
</span>

</center>
<br>

</div>
<?php $theme->display('footer'); ?>