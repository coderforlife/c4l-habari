<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<!-- header -->
<html xmlns="http://www.w3.org/1999/xhtml" xmlns:og="http://ogp.me/ns#" xmlns:fb="http://www.facebook.com/2008/fbml">
<head>
<meta http-equiv="Content-Type" content="text/html">
<meta name="generator" content="Habari">
<meta property="og:site_name" content="<?php Options::out('title') ?>"/>
<meta property="fb:admins" content="coderforlife"/>
<meta property="fb:app_id" content="168233093245142"/>
<meta property="og:title" content="<?= htmlspecialchars($page_title, ENT_QUOTES) ?>"/>
<meta property="og:url" content="<?= $request->is_article ? $post->permalink : $requested_url ?>"/>
<meta property="og:type" content="<?= $request->is_article ? 'article' : 'blog' ?>"/>
<meta property="og:image" content="<?= htmlspecialchars($page_image, ENT_QUOTES) ?>"/>
<meta property="og:description" content="<?= htmlspecialchars($page_desc, ENT_QUOTES) ?>"/>
<meta property="og:region" content="CA"/>
<meta property="og:country-name" content="USA"/>
<meta property="og:email" content="jeff@coderforlife.com"/>
<title><?php Options::out('title') ?><?php if ($page_title) echo ' - ' . htmlspecialchars($page_title, ENT_QUOTES); ?></title>
<link rel="canonical" href="<?= $request->is_article ? $post->permalink : $requested_url ?>">
<link rel="icon" type="image/vnd.microsoft.icon" href="/favicon.ico">
<link rel="icon" type="image/png" href="/favicon.png">
<link rel="SHORTCUT ICON" href="/favicon.ico">
<link rel="stylesheet" type="text/css" href="/styles/styles.css" media="all">
<link rel="stylesheet" type="text/css" href="/styles/code.css" media="all">
<link rel="stylesheet" type="text/css" href="/styles/mobile.css" media="handheld">
<link rel="stylesheet" type="text/css" href="/styles/iphone.css" media="only screen and (max-device-width: 480px)">
<script type="text/javascript" src="/scripts.js"></script>
<?php if ($_SERVER['SERVER_NAME'] != 'coderforlife') { ?>
<script type="text/javascript" src="http<?=!empty($_SERVER['HTTPS'])?'s://ssl':'://www'?>.google-analytics.com/ga.js"></script>
<script type="text/javascript">trkr=_gat._getTracker("UA-4738680-1")</script>
<?php } else { ?>
<script type="text/javascript">trkr={_trackPageview:function(path){if(path)alert(path);}}</script>
<?php } ?>
<?php echo $theme->header();?>
</head>
<body>
<!--[if lte IE 8]><div id="isIE8orOlder"><![endif]-->
<div id="main">
<div id="header"><h1><a href="/"><img src="/images/header.png" alt="<?php Options::out('title'); ?>: <?php Options::out('tagline'); ?>"></a></h1></div>
<div id="nav"><div id="nav-left"></div><div id="nav-right"></div>
<?php
if (isset($post) && (!isset($posts) || get_class($posts) == 'Post') && $post->content_type != Post::type('page')) {
  $left_text = '';
  $right_text = '';
  $is_sub_page = $post->is_sub_page;
  if ($is_sub_page) {
    $left_text = "&uarr; <a href=\"{$post->parent->permalink}\">{$post->parent->name}</a>";
  } else {
    $x = $post;
    while ($x = $x->descend())
      if (!$x->is_sub_page) { $left_text = "&larr; <a href=\"{$x->permalink}\">{$x->name}</a>"; break; }
  }
  $nav_text = '';
  if (!$is_sub_page) {
    $x = $post;
    while ($x = $x->ascend())
      if (!$x->is_sub_page) { $right_text = "<a href=\"{$x->permalink}\">{$x->name}</a>"; break; } // &rarr;
  }
  $larrow = $left_text?($is_sub_page?' up_arrow':' arrow'):'';
  $rarrow = $right_text?' arrow':'';
  echo "<div class='navigate left$larrow'><div></div>$left_text</div><div class='navigate right$rarrow'><div></div>$right_text</div>";
}
echo '<img class="seperator" src="/images/seperator.png" alt="|"><a class="button';
if ($request->display_home)
    echo ' active';
echo '" href="';
Site::out_url('habari');
echo '">'.$home_tab.'</a>';

foreach ($pages as $tab) {
  echo '<img class="seperator" src="/images/seperator.png" alt="|"><a class="button';
  if (!empty($post) && ($post->slug == $tab->slug || strpos($post->slug, $tab->slug.'-') === 0)) {
    echo ' active';
  }
  echo '" href="'.$tab->permalink.'">'.$tab->title.'</a>';
}
echo '<img class="seperator" src="/images/seperator.png" alt="|">';
?>
</div>
<div id="toc"><a id="openToc" href="javascript:openToc()">Table of Contents</a><a id="closeToc" href="javascript:closeToc()">Close</a></div>
<div id="site-badges">
<a href="http://twitter.com/coder_for_life" class="twitter-follow-button" data-button="grey" data-text-color="#FFFFFF" data-link-color="#00AEFF" data-show-count="false">Follow @coder_for_life</a>
<fb:fan profile_id="105828076181783" width="160" height="20" connections=0 stream=0 css="http://www.coderforlife.com/styles/fb.css"></fb:fan>
<div class="plusone"><g:plusone size="small" href="http://www.coderforlife.com/"></g:plusone> <span>Coder for Life</span></div>
</div>
<script src="http://platform.twitter.com/widgets.js" type="text/javascript"></script>
<div class="spacer"></div>
<div class="adsense"><script type="text/javascript"><!--
google_ad_client = "ca-pub-5735177462697983"; google_ad_slot = "5665915731"; google_ad_width = 728; google_ad_height = 90;
//--></script><script type="text/javascript" src="http://pagead2.googlesyndication.com/pagead/show_ads.js"></script></div>
<!-- /header -->