<?php

var_dump($theme);

?>
<?php $theme->display('header'); ?>
<div id="content" class="skins">
	<div id="post-<?php echo $post->id; ?>" class="<?php echo $post->statusname; ?>">
		<div class="entry-head"><h1 class="entry-title"><?php echo $post->title_out; ?> Login</h1></div>
  	<div class="entry-content"><?php echo $post->content_out; ?></div>
	</div>
</div>
<?php $theme->display('footer'); ?>