<!-- footer -->
<div class="adsense"><script type="text/javascript"><!--
google_ad_client = "ca-pub-5735177462697983"; google_ad_slot = "9479533038"; google_ad_width = 728; google_ad_height = 90;
//--></script><script type="text/javascript" src="http://pagead2.googlesyndication.com/pagead/show_ads.js"></script></div>
<div class="spacer"></div>
<div id="footer">
<div id="search">
<form method="get" id="searchform" action="<?php URL::out('display_search'); ?>">
<input type="text" id="seachtext" name="criteria" value="<?php if (isset($criteria)) { echo htmlentities($criteria, ENT_COMPAT, 'UTF-8'); } ?>"><input type="submit" id="searchsubmit" value="<?php _e('Search'); ?>">
</form>
</div>
<small><?php _e('Powered by'); ?> <a href="http://www.habariproject.org/">Habari</a></small><br>
<small><img src="/images/blog/feed.png" alt="<?php _e('Feed'); ?>" style="vertical-align:middle;">
<a href="<?php URL::out('atom_feed', array('index'=>'1')); ?>"><?php _e('Entries'); ?></a>,
<a href="<?php URL::out('atom_feed_projects'); ?>"><?php _e('Projects'); ?></a>,
<a href="<?php URL::out('atom_feed_comments'); ?>"><?php _e('Comments'); ?></a></small>
<?php echo $theme->footer(); ?>
</div>
<div class="spacer"></div>
<?php
/* In order to see DB profiling information:
1. Insert this line in your config file: define( 'DEBUG', TRUE );
2. Uncomment the followng line
*/
// include 'db_profiling.php';
?>
</div>
<div id="fb-root"></div>
<script type="text/javascript" src="https://apis.google.com/js/plusone.js"></script>
<script type="text/javascript">
window.fbAsyncInit = function() { FB.init({appId: '168233093245142', status: true, cookie: true, xfbml: true, oauth: true}); };
(function() {
  var e = document.createElement('script');
  e.type = 'text/javascript'; e.async = true;
  e.src = document.location.protocol + '//connect.facebook.net/en_US/all.js';
  document.getElementById('fb-root').appendChild(e);
}());
trkr._trackPageview();
</script>
<!--[if lte IE 8]></div><![endif]-->
</body></html>
<!-- /footer -->