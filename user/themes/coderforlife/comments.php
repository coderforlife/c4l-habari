<!-- comments -->
<div class="comments-divider"></div>
<div class="comments">
<h1>Comments</h1>
<span id="comments"><?php echo $post->comments->moderated->count; ?> <?php $post->comments->moderated->count == 1 ? _e('Response to') : _e('Responses to'); ?> '<?php echo $post->title; ?>'</span>
<span class="commentsrsslink"><a href="<?php echo $post->comment_feed_link; ?>"><img src="/images/blog/feed.png" alt="<?php _e('Feed for this Entry'); ?>" title="<?php _e('Feed for this Entry'); ?>"></a></span>
<?php if ( $post->comments->moderated->count ) { ?>
<?php if ($post->comments->moderated->count > 2) { ?>
<div class="adsense"><script type="text/javascript"><!--
google_ad_client = "ca-pub-5735177462697983"; google_ad_slot = "8360279414"; google_ad_width = 728; google_ad_height = 90;
//--></script><script type="text/javascript" src="http://pagead2.googlesyndication.com/pagead/show_ads.js"></script></div>
<?php } ?>
<ol id="commentlist">
<?php
	foreach ( $post->comments->moderated as $comment ) {
		if ( $comment->url_out == '' ) {
			$comment_url = $comment->name_out;
		} else {
			$comment_url = '<a href="' . $comment->url_out . '" rel="external">' . $comment->name_out . '</a>';
		}
?>
<li id="comment-<?php echo $comment->id; ?>" <?php echo $theme->my_comment_class( $comment, $post ); ?>>
<a href="#comment-<?php echo $comment->id; ?>" class="counter" title="<?php _e('Permanent Link to this Comment'); ?>"><img src="/images/blog/comment.png" alt="#<?php echo $comment->id; ?>"></a>
<span class="commentauthor"><?php echo $comment_url; ?></span>
<small class="comment-meta"><?php $comment->date->out(); ?><?php if ( $comment->status == Comment::STATUS_UNAPPROVED ) : ?> <br><em><?php _e('In moderation'); ?></em><?php endif; ?></small>
<div class="comment-content"><?php echo $comment->content_out; ?></div>
</li>
<?php } ?>
</ol>
<?php } else { ?>
<br><p><?php _e('There are currently no comments.'); ?></p><br>
<?php } ?>
<?php if (!$post->info->comments_disabled ) { include_once( 'commentform.php' ); } else { echo '<hr>'; } ?>
</div>
<!-- /comments -->
