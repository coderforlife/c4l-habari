<?php
$theme->display('header');
?>
<!-- page.projects -->
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
  	<div class="entry-content"><?php echo $post->content_out; ?></div>
	</div>
<div id="projsel" style="display:none;">
<b>Categories:</b>
<?php
foreach (CoderForLife::type_tags() as $type)
  echo "<div class=\"cat\" id=\"cat-{$type->term}\" onclick=\"toggleType(this);\">".CoderForLife::type_name($type->term_display).'</div>';
?><br>
<div class="cat inactive" id="show-best" onclick="toggleBest(this);">Show decent projects as well</div>
</div>
<br style="clear:both">
<br><br>
<div id="browseboxes"><?php
foreach ($projects as $project) {
  if ($project->is_sub_page) continue;
  $link = $project->permalink;
  $pic = $project->picture ? $link.$project->picture : '/projects/temp.png';
  $alt = htmlspecialchars($project->picture_alt);
  $name = htmlspecialchars($project->name ? $project->name : $project->title);
  $best = $project->is_best;
  $types = $project->types;

  echo "<a href=\"$link\" class=browsebox name=\"".strtolower(implode(' ',array_map('CoderForLife::type_short_name',CoderForLife::tag_slugs($types)))).'" '.($best?'rev=best':'style="display:none"').'>';
  echo "<img src=\"$pic\" alt=\"$alt\" title=\"$alt\" border=0><br>\n";
  echo "<b>$name</b> - ".implode('/',array_map('CoderForLife::type_short_name', CoderForLife::tag_names($types)))."<br>\n";
  echo stripslashes($project->desc);

  if ($project->start) {
    $start = $project->start;
    $end = $project->end;
    echo " ($start";
    $current = !$end;
    if ($current && $start != idate('Y'))
      echo '-Present';
    else if (!$current && $end != $start)
      echo "-$end";
    echo ')';
  }
  echo "\n";
  echo '</a>';
}
?><br style='clear:both'></div>
<?php $theme->display('comments'); ?>
</div>
<!-- /page.projects -->
<?php $theme->display('footer'); ?>