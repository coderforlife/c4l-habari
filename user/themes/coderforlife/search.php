<?php $theme->display('header'); ?>
<!-- search -->
<div id="content"><div id="primarycontent" class="hfeed">
	<div class="searchresults"><?php _e('Search results for \'%s\'', array( htmlspecialchars( $criteria ) ) ); ?></div>
<?php if (count($posts) == 0) { ?>
<center>Nothing found that contains '<?php echo htmlspecialchars( $criteria ) ?>'</center>
<?php } else {
  foreach ( $posts as $post ) {
?>
	<div id="post-<?php echo $post->id; ?>" class="<?php echo $post->statusname; ?>">
		<small class="entry-meta">
			<span class="chronodata"><abbr class="published"><?php echo $post->pubdate->text_format('<span>{M}</span> <span>{d}</span>, <span>{Y}</span>'); ?></abbr></span> <?php if ( $show_author ) { _e( 'by %s', array( $post->author->displayname ) ); } ?>
      <?php $theme->comments_link($post); ?>
      <?php if (count($post->tags) && !!$post->tags_out) { ?><span class="entry-tags"><?php echo $post->tags_out; ?>
<?php
if ($post->content_type==Post::type('project') && ($post->is_best || count($post->types))) {
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
		<div class="entry-head"><h1 class="entry-title">
      <a href="<?php echo $post->permalink; ?>"><?php echo $post->title_out; ?></a>
      <?php if ($loggedin) { ?><a href="<?php echo $post->editlink; ?>" class="entry-edit"><img src="/images/blog/pencil.png" alt="Edit"></a><?php } ?>
    </h1></div>
  	<div class="entry-content"><?php echo $post->content_excerpt; ?></div>
	</div>
<?php } ?>
</div><br><hr><div id="page-selector">Page:
	<?php echo $theme->prev_page_link(); ?> <?php echo $theme->page_selector( null, array( 'leftSide' => 2, 'rightSide' => 2 ) ); ?> <?php echo $theme->next_page_link(); ?>
<?php } ?>
</div></div>
<!-- /search -->
<?php $theme->display ('footer'); ?>
