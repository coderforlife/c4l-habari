<?php $theme->display('header'); ?>
<!-- error -->
<div id="content">
  <div id="post" class="error">
		<div class="entry-head">
			<h1 class="entry-title"><?php _e('Error!'); ?></h1>
		</div>
		<div class="entry-content">
			<p><?php _e('The requested page was not found.'); ?></p>
		</div>
	</div>
</div>
<!-- /error -->
<?php $theme->display('footer'); ?>
