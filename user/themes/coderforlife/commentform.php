<!-- commentsform -->
<?php // Do not delete these lines
if ( ! defined('HABARI_PATH' ) ) { die( _t('Please do not load this page directly. Thanks!') ); }
$required = (Options::get('comments_require_id') == 1) ? '*' : '';
?>
<div id="commentformcontainer" class="comments">
	<div id="respond" class="reply" onclick="toggleHidden(this.parentNode)" style="cursor:pointer;"><?php _e('Leave a Reply >'); ?></div>
<div><b style="color:red">Note:</b> If you are having a problem with Windows 7 Boot Updater please <a href="mailto:jeff@coderforlife.com&subject=Windows%207%20Boot%20Updater%20Problem">email me</a>.
Include the description of the problem, the output of <a href="/projects/win7boot/extras/#bootinfo">boot-info</a>, and any other relevant information (e.g. if you are dual-booting).</div>

<div><b>If your question has already been answered it will simply be deleted.</b></div>

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
