<?php header('HTTP/1.0 403 Forbidden'); ?>
<?php $theme->display('header'); ?>
<div id="content" class="skins flagged">
	<div id="post-<?php echo $post->id; ?> skin-<?php echo $id; ?>" class="<?php echo $post->statusname; ?>">
    <div class="entry-meta"><a href="/projects/win7boot/skins/myaccount/">My Account</a></div>
		<div class="entry-head"><h1 class="entry-title"><?php echo $post->title_out; ?>: Flagged</h1></div>
  	<div class="entry-content">This skin has been flagged as inappropriate, illegal, or spam. It will be reviewed and hopefully be available again soon.</div>
	</div>
</div>
<?php $theme->display('footer'); ?>