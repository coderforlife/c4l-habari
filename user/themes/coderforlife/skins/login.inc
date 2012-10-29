<?php

require_once 'recaptcha.inc';

$login_msgs = $messages;
$forgot_msgs = array();
$register_msgs = array();

if (array_key_exists('forgot_email', $_POST))
{
  $email = trim($_POST['forgot_email']);
  $db = dbconnect();
  $stmt = $db->prepare('SELECT id FROM users WHERE email=? LIMIT 1');
  $stmt->bind_param('s', $email);
  $stmt->execute();
  $stmt->bind_result($id);
  $fetch = !!$stmt->fetch();
  $stmt->close();
  if (!$fetch)
    $forgot_msgs[] = '<p class="error">No user with that email found</p>';
  else
  {
    $pass = random_password();
    if (sendmail($email, 'Coder for Life Bootskins Password Reset', <<<MESSAGE
Hi,

Upon your request your coderforlife.com/projects/win7boot/skins/ password has
been reset. Login using your username or email and the new password below. You
will be required to change your password when you login.

New Password: $pass

Jeff
MESSAGE
))
    {
      $stmt = $db->prepare('UPDATE users SET password=PASSWORD(?), reset_password=1 WHERE id=? LIMIT 1');
      $stmt->bind_param('si', $pass, $id*1);
      $stmt->execute();
      $stmt->close();
      $forgot_msgs[] = '<p class="good">A new password has been emailed</p>';
    }
    else 
      $forgot_msgs[] = '<p class="error">Failed to send email</p>';
  }
}
else if (array_key_exists('username', $_POST))
{
  $user = trim($_POST['username']);
  $slug = slugify($user);
  $email = trim($_POST['email']);
  $db = dbconnect();
  if ($name == '' || $slug == '')
    $register_msgs[] = '<p class="error">The username must have at least 1 alpha-numeric character</p>';
  else if (name_already_exists($db, 'users', $user, $slug))
    $register_msgs[] = '<p class="error">The username (or something very similar) already exists</p>';
  
  $stmt = $db->prepare("SELECT id FROM users WHERE email=? LIMIT 1");
  $stmt->bind_param('s', $email);
  $stmt->execute();
  $stmt->bind_result($id);
  $email_exists = !!$stmt->fetch();
  $stmt->close();
  if ($email_exists)
    $register_msgs[] = '<p class="error">The email is already registered</p>';
  else if (!valid_email($email))
    $register_msgs[] = '<p class="error">The email is not valid</p>';
  
  $recaptcha_resp = recaptcha_check_answer($_SERVER['REMOTE_ADDR'], $_POST['recaptcha_challenge_field'], $_POST['recaptcha_response_field']);
  if (!$recaptcha_resp->is_valid)
    $register_msgs[] = '<p class="error">The text wasn\'t typed correctly</p>';
  
  if (count($register_msgs) == 0)
  {
    $pass = random_password();
    if (sendmail($email, 'Coder for Life Bootskins New Account', <<<MESSAGE
Hi,

A new account at coderforlife.com/projects/win7boot/skins/ has been created.
Login using your username or email and the temporary password below. You will
be required to change your password when you login.

Username: $user
Password: $pass

User page: coderforlife.com/projects/win7boot/skins/user/$slug/

Jeff
MESSAGE
))
    {
      $stmt = $db->prepare('INSERT INTO users (name, slug, password, email, public_email, created) VALUES (?, ?, PASSWORD(?), ?, ?, NOW())');
      $public_email = POST('public_email', 0)*1;
      $stmt->bind_param('ssssi', $user, $slug, $pass, $email, $public_email);
      $stmt->execute();
      $stmt->close();
      $register_msgs[] = '<p class="good">Your temporary password has been emailed to</p>';
    }
    else
      $register_msgs[] = '<p class="error">Failed to send email</p>';
  }
}
?>
<?php $theme->display('header'); ?>
<div id="content" class="skins">
	<div id="post-<?php echo $post->id; ?>" class="<?php echo $post->statusname; ?>">
		<div class="entry-head"><h1 class="entry-title"><?php echo $post->title_out; ?> Login</h1></div>
  	<div class="entry-content"><?php echo $post->content_out; ?></div>
	</div>

<br>
<center>

<span style="display:inline-block;vertical-align:top;">
<form name="login" method="post">
<fieldset><legend>Login</legend>
<?php foreach ($login_msgs as $msg) echo $msg; ?>
<label for="login_username">Username: </label><input type="text" name="login_username" id="login_username" <? echoValFromPOST('login_username'); ?> maxlength=255><br>
<label for="login_password">Password: </label><input type="password" name="login_password" id="login_password"><br>
<input type="submit" value="Login">
</fieldset>
</form>
<br>
<form name="forgot" method="post">
<fieldset><legend>Forgot Password</legend>
<?php foreach ($forgot_msgs as $msg) echo $msg; ?>
<label for="forgot_email">Email: </label><input type="text" name="forgot_email" id="forgot_email" <? echoValFromPOST('forgot_email'); ?> maxlength=255><br>
<input type="submit" value="Reset Password">
</fieldset>
</form>
</span>

<span style="display:inline-block;vertical-align:top;">
<form name="register" method="post">
<fieldset><legend>Register</legend>
<?php foreach ($register_msgs as $msg) echo $msg; ?>
<label for="username">Username: </label><input type="text" name="username" id="username" <? echoValFromPOST('username'); ?> maxlength=128><br>
<label for="email">Email: </label><input type="text" name="email" id="email" <? echoValFromPOST('email'); ?> maxlength=255> 
<input type="checkbox" name="public_email" id="public_email" value="1" <? if (POST('public_email', 0) == 1) echo 'checked'; ?>><label for="public_email"> Public</label>
<?php echo recaptcha_get_html(isset($recaptcha_resp) && !$recaptcha_resp->is_valid ? $recaptcha_resp->error : null); ?>
<input type="submit" value="Register">
</fieldset>
</form>
</span>

</center>
<br>

</div>
<?php $theme->display('footer'); ?>