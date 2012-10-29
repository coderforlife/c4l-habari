<?php $theme->display ('header'); ?>
<!-- page.single -->
<div id="content">
	<div id="post-<?php echo $post->id; ?>" class="<?php echo $post->statusname; ?>">
    <div class="entry-meta">
      <span class="chronodata"><abbr class="published"><?php $post->pubdate->out(); ?></abbr></span> <?php if ( $show_author ) { _e('by %s', array( $post->author->displayname ) ); } ?>
      <!--<?php $theme->comments_link($post); ?>-->
      <?php if (count($post->tags) && !!$post->tags_out) { ?> <span class="entry-tags"><?php echo $post->tags_out; ?></span> <?php } ?>
    </div>
    <div id="post-badges">
      <fb:like href="<?=$post->permalink?>" send="false" layout="button_count" width="90" show_faces="false" colorscheme="dark" font="verdana"></fb:like>
      <div class="plusone"><g:plusone size="medium" href="<?=$post->permalink?>"></g:plusone></div>
    </div>
		<div class="entry-head">
      <h1 class="entry-title">
        <!--<a href="<?php echo $post->permalink; ?>"><?php echo $post->title_out; ?></a>-->
        <?php echo $post->title_out; ?>
        <?php if ($loggedin) { ?><a href="<?php echo $post->editlink; ?>" class="entry-edit"><img src="/images/blog/pencil.png" alt="Edit"></a><?php } ?>
      </h1>
    </div>
  	<div class="entry-content">
  		<?php echo $post->content_out; ?>
  	</div>
	</div>
<?php $theme->display ('comments'); ?>
</div>
<!-- /page.single -->
<?php $theme->display ('footer'); ?>
