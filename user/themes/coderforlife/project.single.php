<?php $theme->display('header'); ?>
<!-- entry.single -->
<div id="content"><div id="primarycontent" class="single">
	<div id="post-<?php echo $post->id; ?>" class="<?php echo $post->statusname; ?>">
		<small class="entry-meta">
			<span class="chronodata"><abbr class="published"><?php echo $post->pubdate->out(); ?></abbr></span> <?php if ( $show_author ) { _e( 'by %s', array( $post->author->displayname ) ); } ?>
      <!--<?php $theme->comments_link($post); ?>-->
      <?php if (count($post->tags) && !!$post->tags_out) { ?> <span class="entry-tags"><?php echo $post->tags_out; ?>
<?php
if ($post->is_best || count($post->types)) {
  echo '<span class="entry-tags-more">&raquo;</span><span><span>';
  $best = $post->is_best;
  $types = $post->types;
  if ($best) {
    echo '<a href="'.URL::get('display_entries_by_tag', array('tag'=>'best')).'" rel="tag">Best</a>';
    switch (count($types)) {
      case 0:  echo ''; break;
      case 1:  echo ' and '; break;
      default: echo ', '; break;
    }
  }
  if ($types)
    echo str_replace('#', '', Format::tag_and_list($types));
  echo '</span></span>';
}
?></span><?php } ?>
		</small>
    <div id="post-badges">
      <fb:like href="<?=$post->permalink?>" send="false" layout="button_count" width="90" show_faces="false" colorscheme="dark" font="verdana"></fb:like>
      <div class="plusone"><g:plusone size="medium" href="<?=$post->permalink?>"></g:plusone></div>
    </div>
		<div class="entry-head">
      <h1 class="entry-title">
        <!--<a href="<?php echo $post->permalink; ?>"><?php echo $post->title_out; ?></a>-->
        <?php echo $post->title_out; ?>
        <?php if ($post->donation) { ?>
<form class=donate action="https://www.paypal.com/cgi-bin/webscr" method="post">
<input type="hidden" name="cmd" value="_s-xclick">
<input type="hidden" name="encrypted" value="<?php echo $post->donation;?>">
<input type="image" src="/images/donate.png" alt="Donate" onMouseOver="this.src='/images/donate-over.png'" onMouseOut="this.src='/images/donate.png'">
</form>
        <?php } ?>
        <?php if ($loggedin) { ?><a href="<?php echo $post->editlink; ?>" class="entry-edit"><img src="/images/blog/pencil.png" alt="Edit"></a><?php } ?>
      </h1>
    </div>
    <?php if ($post->show_picture && $post->picture) { ?>
    <img class="post-pic" alt="" src="<?php echo $post->picture ?>">
    <?php } ?>
  	<div class="entry-content">
  		<?php echo $post->content_out; ?>
  	</div>
	</div>
<?php $theme->display('comments'); ?>
</div></div>
<!-- /entry.single -->
<?php $theme->display('footer'); ?>
