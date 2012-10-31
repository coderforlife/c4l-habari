<!-- commentsform -->
<?php // Do not delete these lines
if ( ! defined('HABARI_PATH' ) ) { die( _t('Please do not load this page directly. Thanks!') ); }
$required = (Options::get('comments_require_id') == 1) ? '*' : '';
?>
<div id="commentformcontainer" class="comments">
	<div id="respond" class="reply" onclick="toggleHidden(this.parentNode)" style="cursor:pointer;"><?php _e('Leave a Reply >'); ?></div>
<div><b style="color:red">Note: If you are having a problem with Windows 7 Boot Updater read <a href="/projects/win7boot/#Troubleshooting">the troubleshooting list</a> first!.</b></div>
<div><b>If your question has already been answered there or in the comments it will be deleted.</b></div>
<?php
if ( Session::has_messages() ) {
	Session::messages_out();
}
?>
<div style="float:right;width:350px;text-align:right;">
Automatically creates links and paragraphs.<br>
Many HTML tags are supported.<br>
Use &lt;code language=&quot;&quot;&gt;&lt;/code&gt; for code.<br>
Language can be dos, java, php, c, ...
</div>
  <?php $post->comment_form()->out(); ?>
	<hr>
</div>
<!-- /commentsform -->
