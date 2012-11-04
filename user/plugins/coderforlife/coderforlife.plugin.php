<?php

include_once dirname(__FILE__).'/geshi/geshi.php';

function full_path($f, $b=null) { return (strlen($f) > 0 && $f[0] !== '/') ? ($b?$b:$_SERVER['REQUEST_URI']).$f : $f; }
function physical_path($f, $b=null) { return $_SERVER['DOCUMENT_ROOT'].(($_SERVER['DOCUMENT_ROOT']=='C:/xampp/htdocs') ? '/coderforlife' : '').full_path($f, $b); }
function base64url_encode($data) { return strtr(rtrim(base64_encode($data), '='), '+/', '-_'); }
function base64url_decode($data) { return base64_decode(str_pad(strtr($data, '-_', '+/'), strlen($data) % 4, '=', STR_PAD_RIGHT)); }

//function array_keys_exist($arr, $keys) { foreach ($keys as $k) { if (!array_key_exists($k, $arr)) { return false; } } return true; }
//function remove_keys($arr, $keys) { foreach ($keys as $k) unset($arr[$k]); }

class GeshiFormater extends Format {
  public static function do_code($matches) {
    $whole = $matches[0];

    $doc = new DOMDocument();
    $doc->loadXML("<?xml version=\"1.0\"?>\n$whole");
    $code = $doc->documentElement;
    $class = $code->hasAttribute('class')?$code->getAttribute('class'):null;
    $id = $code->hasAttribute('id')?$code->getAttribute('id'):null;
    $lang = $code->getAttribute('language');
    $code = $code->textContent;

    $geshi = new GeSHi(trim($code), $lang);
    $geshi->set_header_type(GESHI_HEADER_PRE_VALID);
    $geshi->enable_line_numbers(GESHI_NORMAL_LINE_NUMBERS);
    $geshi->set_overall_class("code $class");
    $geshi->set_overall_id($id);
    $geshi->enable_classes();
    //echo '<!--'.$geshi->get_stylesheet().'-->'; //TODO: resuse other stylesheets
    return $geshi->parse_code();
  }
  static function geshi($content) {
    $content = str_replace("\t", '    ', $content);
    //Language is in backreference 3 and code is in backreference 5
    //Extra parts of the <code> tag are in 1 and 4
    //2 is used by the search and 0 is the whole match
    return preg_replace_callback('~<code\s+([^>]+\s+)?language=([\'"]?)([a-z0-9_]+)\2([^>]*)>(.*?)</code>~si', 'GeshiFormater::do_code', $content);
  }
}

class LinkFormater extends Format {
  public static function linkify($content, $post, $create_links = null, $in_comment = false) {
    if (is_null($create_links)) { $create_links = $post->style != 'raw'; }
    
    static $start = '@(^|[\s:=~;,\[\]<]|<[^a][^<>]*>)('; // @ is used as the regex delimiter
    static $end   = ')($|[\s:=~;,\[\]>.]|</[^a]|<[^/])@i';
    static $ip    = '(?:(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.){3}(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)';
    static $port  = '(?::[0-9]{1,4})?';
    static $chars = '[0-9a-z_!~*\'?&=+$%#().,:\@-]+';
    static $ending_char = '[0-9a-z_~\'&=+$%#()/:\@-]';
    static $email_chars = '[0-9a-z_!~*\'?&=+$%#{}/^`|-]+';

    // Top-level domain names. The most accurate way would be to use "[a-z]{2,6}" however that causes many file names to be made into links
    // Below is the list of all available top-level domains, excluding:
    //   "cat" because it is a common file extension and an uncommon top-level domain)
    //   "eu"  because it is accepted by the general country specific regex used
    static $top_level = '(?:aero|arpa|asia|biz|com|coop|edu|gov|info|int|jobs|mil|mobi|museum|name|net|org|pro|tel|travel|xxx|a[c-gil-oq-uwxz]|b[abd-jmnorstvwyz]|c[acdf-ik-orsuvxyz]|d[ejkmoz]|e[cegr-u]|f[ijkmor]|g[abd-ilmnp-uwy]|h[kmnrtu]|i[del-oq-t]|j[emop]|k[eghimnprwyz]|l[abcikr-vy]|m[acdeghk-z]|n[acefgilopruz]|om|p[aefghk-nrstwy]|qa|r[eosuw]|s[a-eg-ortuvyz]|t[cdfghj-prtvwz]|u[agksyz]|v[aceginu]|w[fs]|y[et]|z[amw])';

    $host  = '(?:[0-9a-z_!~*\'()-]+\.)*(?:[0-9a-z][0-9a-z-]{0,61})?[0-9a-z]\.'.$top_level;
    $email = $email_chars.'(?:\.'.$email_chars.')*\@(?:'.$host.'|\['.$ip.'\])';
    $full = '(?:'.$host.'|'.$ip.')'.$port.'(?:(?:/'.$chars.')+'.$ending_char.'|/|)?';
    
    static $href_start = '@(<a\s[^>]*href=)(["\'])';
    static $href_end = '\2([^>]*>)@iUe';
    
    $rel = $in_comment ? ' rel="nofollow"' : '';
    $patterns = array(
      $start.$email.$end.'e',                      // email
      $start.'https?://'.$full.$end,               // link with http
      $start.            $full.$end,               // link
      $href_start.'([^/"\'][^:"\']*|)' .$href_end, // relative hrefs
      $href_start.'mailto:('.$email.')'.$href_end, // email hrefs
      '@(?)('.$email.')(?)@ie');                   // any remaining emails need to be obfuscated
    $replacements = array(
      "'$1<a href=\"/contact/'.LinkFormater::encrypt_email('$2').'/\" rel=\"nofollow\">'.LinkFormater::obfuscate_email('$2').'</a>$3'",
      '$1<a href="$2"'       .$rel.'>$2</a>$3',
      '$1<a href="http://$2"'.$rel.'>$2</a>$3',
      "'$1\"'.full_path('$3','{$post->permalink}').'\"$4'",
      "'$1\"/contact/'.LinkFormater::encrypt_email('$3').'/\" rel=\"nofollow\"$4'",
      "'$1'.LinkFormater::obfuscate_email('$2').'$3'");
    if (!$create_links) { $patterns = array_slice($patterns, 3); $replacements = array_slice($replacements, 3); }
    return preg_replace($patterns, $replacements, $content);
  }
  private static function obfuscate_email($email) {
    $inj = array('NULL', 'REMOVE', 'XXX', 'JUNK', 'SPAM');
    $parts = str_split($email, 3);
    $count = count($parts);
    $email = '';
    for ($i = 0; $i < $count; $i++) {
        $email .= '<span>'.array_rand($inj).'</span>'.$parts[$i];
    }
    return $email.'<span>'.array_rand($inj).'</span>';
  }
  private static $crypt_email_key = 'fairly plain for email', $crypt_md5, $crypt_md5_2;
  public static function init() { LinkFormater::$crypt_md5 = md5(LinkFormater::$crypt_email_key); LinkFormater::$crypt_md5_2 = md5(LinkFormater::$crypt_md5); }
  public static function encrypt_email($data) { return base64url_encode(mcrypt_encrypt(MCRYPT_RIJNDAEL_256, LinkFormater::$crypt_md5, $data, MCRYPT_MODE_CBC, LinkFormater::$crypt_md5_2)); }
  public static function decrypt_email($data) { return rtrim(mcrypt_decrypt(MCRYPT_RIJNDAEL_256, LinkFormater::$crypt_md5, base64url_decode($data), MCRYPT_MODE_CBC, LinkFormater::$crypt_md5_2), "\0"); }
}
LinkFormater::init();

class StripHTML extends Format {
  public static function strip_all_html($content) { return strip_tags($content); }
  public static function strip_bad_tags($content) {
    //<span>
    return strip_tags($content, '<br><hr><code><p><pre><a><img><dd><dl><dt><li><ol><ul><address><blockquote><del><ins><abbr><acronym><cite><dfn><kbd><var><b><big><em><i><s><small><strike><strong><sub><sup><u><tt>');
  }
  public static function strip_excerpt_tags($content) {
    return strip_tags($content, '<a><br><hr><address><blockquote><center><div><h1><h2><h3><h4><h5><h6><p><pre><dd><dir><dl><dt><li><menu><ol><ul><span><abbr><acronym><cite><code><del><dfn><ins><kbd><samp><var><em><strong><b><big><i><small><s><strike><sub><sup><tt><u><form><fieldset><label><select><option><optgroup><input><textarea><button><legend><caption><table><thead><tfoot><tbody><tr><td><th><col><colgroup>');
  }
}

class PhpFormater extends Format {
  public static function exec_php($matches) {
    $code = $matches[0];
    $code = (($code[2] == '=') ? ('echo '.trim(substr($code, 3, -2))) : trim(substr($code, 5, -2))).';';
    ob_start();
    eval($code);
    return ob_get_clean();
  }
  public static function run_php($content) {
    return preg_replace_callback('~<\?(php\s|=).*\?>~siU', 'PhpFormater::exec_php', $content);
  }
}

class SpecialTagsFormater extends Format {
  private static function _get_attributes($s, $re, &$attr) {
    $ms = array();
    if (preg_match_all($re, $s, $ms, PREG_SET_ORDER))
      foreach ($ms as $m) $attr[$m[1]] = $m[2];
  }
  static function get_attributes($s, $bools = array()) {
    $attr = array();
    SpecialTagsFormater::_get_attributes($s, '~\b(\w+)=\'([^\']*)\'~', $attr);
    SpecialTagsFormater::_get_attributes($s, '~\b(\w+)="([^"]*)"~', $attr);
    SpecialTagsFormater::_get_attributes($s, '~\b(\w+)=([\w_-]+)~', $attr);
    if (count($bools) > 0) {
      $x = '~(?:^|\s)(';
      foreach ($bools as $b) $x .= $b.'|';
      $x = trim($x, '|').')(?:>|\s|$)~i';
      $ms = array();
      if (preg_match_all($x, $s, $ms))
        foreach ($ms[1] as $m) $attr[$m] = true;
    }
    return $attr;
  }
  static function make_attrs($as) { $s='';if(is_string($as)){$s=$as;}else if(isset($as)){$s=''; foreach ($as as $n=>$v) { $s .= is_bool($v)?($v?" $n":''):" $n=\"$v\""; } } return $s; }
  /*private*/ static function _do_tag($s, $f, $req, $opt, $bool) {
    $a = SpecialTagsFormater::get_attributes($s, $bool);
    $x = array();
    foreach ($req as $n)  if (array_key_exists($n, $a)) { $x[] = $a[$n]; unset($a[$n]); } else { return $s; }
    foreach ($opt as $n)  if (array_key_exists($n, $a)) { $x[] = $a[$n]; unset($a[$n]); } else { $x[] = ''; }
    foreach ($bool as $n) if (array_key_exists($n, $a)) { $x[] = true;   unset($a[$n]); } else { $x[] = false; }
    $x[] = $a;
    return call_user_func_array($f, $x);
  }
  static function do_tag($tag, $s, $f, $req, $opt = array(), $bool = array()) {
    $f = var_export($f, true); $req = var_export($req, true); $opt = var_export($opt, true); $bool = var_export($bool, true);
    $l = create_function('$ms', "return SpecialTagsFormater::_do_tag(\$ms[0],$f,$req,$opt,$bool);");
    return preg_replace_callback("~<$tag(?:\\s+(?:[^>]*)|)>~i", $l, $s);
  }

  public static function zoom($src, $alt, $width = 0, $height = 0, $class = '', $thumb = '', $extra = array()) {
    $extra = SpecialTagsFormater::make_attrs($extra);
    /*$s = '<a href="/zoom.php?img='.urlencode(full_path($src)).'&amp;title='.urlencode($title).'">';
    $s.= '<img src="'.htmlspecialchars($src, ENT_QUOTES).'" alt="'.htmlspecialchars($alt, ENT_QUOTES)."\" class=link title='Click to enlarge' width=$width height=$height $extra>";
    $s.= '</a>';*/
    $width = $width == 0 ? '' : " width=$width";
    $height = $height == 0 ? '' : " height=$height";
    $full = '';
    if ($thumb != '')
    {
      $full = ' full="'.htmlspecialchars($src, ENT_QUOTES).'"';
      $src = $thumb;
    }
    $s = '<img src="'.htmlspecialchars($src, ENT_QUOTES).'" alt="'.htmlspecialchars($alt, ENT_QUOTES)."\" class='zoom $class' title='Click to enlarge'$width$height$full $extra>";
    return $s;
  }
  /*public static function thumb($src, $title, $alt, $height = 0, $extra = array()) {
    $extra = SpecialTagsFormater::make_attrs($extra);
    $height = $height == 0 ? '' : " style='height:{$height}px;'";
    $src = htmlspecialchars($src, ENT_QUOTES);
    $s = "<span class=thumb$height $extra><img src='$src' alt=''><img src='$src' alt='".htmlspecialchars($alt, ENT_QUOTES).'\'></span>';
    return $s;
  }*/
  public static function size($file_or_size) {
    if (is_int($file_or_size) || is_float($file_or_size))
    {
    $size = $file_or_size;
    }
    else
    {
      $file = $file_or_size;
      if (!file_exists($file))    return 'unknown size';
      $size = filesize($file);
    }
    if ($size < 1024)           return $size . ' bytes';
    else if ($size < 1024*1024) return round($size/1024, 2) . ' KB';
    else                        return round($size/(1024*1024), 2) . ' MB';
  }
  public static function download($file, $name, $class = '', $extra = array()) {
    $extra = SpecialTagsFormater::make_attrs($extra);
    $s = "<a class='download $class' href='$file' onclick='trkr._trackPageview(this.pathname)'$extra>$name (";
    $file = physical_path($file);
    if (file_exists($file))
      $s = $s.SpecialTagsFormater::size($file).', updated '.date('Y-m-d', filemtime($file));
    else
      $s = $s.'<span class=error>File Not Found</span>';
    return $s.')</a>';
  }
  public static function applet($src, $main, $width, $height, $scriptable = false, $mayscript = false, $extra = array()) {
    $extra = SpecialTagsFormater::make_attrs($extra);
    $s = $scriptable?'true':'false';
    $m = $mayscript?'true':'false';
  return str_replace("\n", '', <<<APPLET
<!--[if !IE]>--><object classid="java:$main.class" type="application/x-java-applet;version=1.5" archive="$src" style="padding:0;margin:0" width="$width" height="$height"$extra><!--<![endif]-->
<!--[if IE]><object classid="clsid:8AD9C840-044E-11D1-B3E9-00805F499D93" width="$width" height="$height"$extra><param name="code" value="$main.class"><param name="type" value="application/x-java-applet;version=1.5"><![endif]-->
<param name="archive" value="$src"><param name="scriptable" value="$s"><param name="mayscript" value="$m"><b>You do not have Java installed or your version of Java is too old</b><br><a href="http://www.java.com/">Click here to get the latest version of Java</a></object>
APPLET
  );
  }
  public static function flash($movie, $w, $h, $id = '', $expandable = '', $swLiveConnect = false, $extra = array()) {
    $x = SpecialTagsFormater::make_attrs($extra);
    if ($expandable) {
      $extra['class'] = (array_key_exists('class', $extra)?($extra['class'].' '):'').'expandable'.($expandable=='both'?' exp-both':'');
      return "<div style=\"height:{$h}px;width:{$w}px\"$x>".SpecialTagsFormater::flash($movie,'100%','100%',$id,'',$swLiveConnect,$extra).'</div>';
    }
    if ($swLiveConnect) {
      if (!$id) $id = substr($movie, 0, strrpos($movie, '.'));
      $name = " name=\"$id\"";
      $id = " id=\"$id\"";
      $flash =
"<object classid=\"clsid:D27CDB6E-AE6D-11cf-96B8-444553540000\"$id width=\"$w\" height=\"$h\" codebase=\"http://download.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=10,0,0,0\"$x>".
"<param name=movie value=\"$movie\">".
"<embed src=\"$movie\" width=\"$w\" height=\"$h\" swLiveConnect=true$id$name type=\"application/x-shockwave-flash\" pluginspage=\"http://www.macromedia.com/go/getflashplayer\"$x></embed>";
    } else {
      $id = $id ? " id=\"$id\"" : '';
      $flash =
"<object$id type=\"application/x-shockwave-flash\" width=\"$w\" height=\"$h\" data=\"$movie\"$x>".
"<param name=\"allowFullScreen\" value=\"true\"><param name=\"movie\" value=\"$movie\">".
"<b>You do not have Flash installed</b><br><a href=\"http://www.adobe.com/shockwave/download/download.cgi?P1_Prod_Version=ShockwaveFlash\">Click here to get Flash</a>";
    }
    foreach ($extra as $n=>$v)
      $flash .= "<param name=\"$n\" value=\"$v\">";
    return $flash.'</object>';
  }
  public static function video($src, $width = -1, $height = -1, $extra = array()) {
    $src = full_path($src);
    $extra = SpecialTagsFormater::make_attrs($extra);
    $preview = $src.'.png';

    $ua = $_SERVER['HTTP_USER_AGENT'];
    if (stripos($ua, 'iphone')>-1 || stripos($ua, 'ipod')>-1 || stripos($ua, 'ipad')>-1 || stripos($ua, 'andriod')>-1) {
      $prev = file_exists(physical_path($preview)) ? " poster=\"$preview\"" : '';
      $vid = "<center><video controls$prev $extra>";
      if (file_exists(physical_path($src.'.mp4')))  $vid .= "<source src=\"$src.mp4\" type='video/mp4; codecs=\"avc1.42E01E, mp4a.40.2\"'>";
      if (file_exists(physical_path($src.'.webm'))) $vid .= "<source src=\"$src.webm\" type='video/webm; codecs=\"vp8, vorbis\"'>";
      if (file_exists(physical_path($src.'.ogv')))  $vid .= "<source src=\"$src.ogv\" type='video/ogg, codecs=\"theora, vorbis\"'>";
      $vid .= '</video>';
    } else {
      if ($height == -1 || $width == -1) { $height = 200; $width = 200; }
      $height += 29;
      $prev = file_exists(physical_path($preview)) ? "&amp;image=$preview" : '';
      $vid = str_replace("\n", '', <<<VID
<center class="movie"$extra><object type="application/x-shockwave-flash" width="$width" height="$height" data="/movieplayer/player.swf">
<param name="allowFullScreen" value="true"><param name="movie" value="/movieplayer/player.swf"><param name="FlashVars" value="file=$src.flv&amp;skin=/movieplayer/glow.zip$prev">
<b>You do not have Flash installed</b><br><a href="http://www.adobe.com/shockwave/download/download.cgi?P1_Prod_Version=ShockwaveFlash">Click here to get Flash</a></object>
VID
      );
    }
    $original = $src.'.mpg';
    if (file_exists(physical_path($original))) {
      $vid .= '<br>'.SpecialTagsFormater::download($original, 'Download the movie');
    }
    $vid .= '</center>';
    return trim($vid);
  }
  public static function special_tags($content) {
    // Zoom
    // required: src, alt
    // optional: width, height (should use at least one), class, thumb
    $content = SpecialTagsFormater::do_tag('zoom', $content, 'SpecialTagsFormater::zoom', array('src', 'alt'), array('width', 'height', 'class', 'thumb'));

    // Thumbnails
    // required: src, alt , ? ?
    //$content = SpecialTagsFormater::do_tag('thumb', $content, 'SpecialTagsFormater::zoom', array('src'));

    // Download
    // required: file, name
    // optional: class
    $content = SpecialTagsFormater::do_tag('download', $content, 'SpecialTagsFormater::download', array('file', 'name'), array('class'));

    // Java
    // required: src, main, width, height
    // boolean:  scriptable, mayscript
    $content = SpecialTagsFormater::do_tag('java', $content, 'SpecialTagsFormater::applet', array('src', 'main', 'width', 'height'), array(), array('scriptable', 'mayscript'));

    // Flash
    // required: src, width, height
    // optional: id, expandable=[width|both]
    // boolean:  swLiveConnect
    $content = SpecialTagsFormater::do_tag('flash', $content, 'SpecialTagsFormater::flash', array('src', 'width', 'height'), array('id', 'expandable'), array('swLiveConnect'));

    // Video
    // required: src
    // optional: original, preview
    $content = SpecialTagsFormater::do_tag('video', $content, 'SpecialTagsFormater::video', array('src', 'width', 'height'));

    return $content;
  }
}

class CoderForLife extends Plugin {
  public function action_plugin_activation($plugin_file) {
    // add the 'project 'content type and allow it to be read
    Post::add_new_type('project');
    $group = UserGroup::get_by_name('anonymous');
    $group->grant('post_project', 'read');
  }
  public function action_plugin_deactivation($plugin_file) {
    Post::deactivate_post_type('project');
  }

  public function action_init() {
    // add templates for projects
    $this->add_template('project.single', dirname(__FILE__).'/project.single.php');
    $this->add_template('project.multiple', dirname(__FILE__).'/project.multiple.php');

    // Add the language attribute to the <code> element
    InputFilter::$whitelist_attributes['code'] = array('language'=>'text');
  }

  private static function create_rule($name, $priority, $regex, $build_str)
  {
    return new RewriteRule(array('name'=>$name, 'handler'=>'PluginHandler', 'action'=>$name, 'priority'=>$priority, 'parse_regex'=>$regex, 'build_str'=>$build_str));
  }

  public function filter_rewrite_rules($rules)
  {
    $rules[] = CoderForLife::create_rule('display_project',            8, '%^projects/(?P<name>.+)/?$%iU',                                       'projects/{$name}/');
    $rules[] = CoderForLife::create_rule('atom_feed_projects',         6, '%^projects/atom(?:/page/(?P<page>\d+))?/?$%i',                        'projects/atom(/page/{$page})');
    $rules[] = CoderForLife::create_rule('atom_feed_project_comments', 7, '%^projects/(?P<name>.+)/atom/comments(?:/page/(?P<page>\d+))?/?$%iU', 'projects/{$name}/atom/comments(/page/{$page})');

    $rules[] = CoderForLife::create_rule('display_w7bu_user',          6, '%^projects/win7boot/skins/user/(?P<user_slug>.+)/?$%iU',              'projects/win7boot/skins/user/{$user_slug}/');
    $rules[] = CoderForLife::create_rule('display_w7bu_skin',          6, '%^projects/win7boot/skins/skin/(?P<skin_slug>.+)/?$%iU',              'projects/win7boot/skins/skin/{$skin_slug}/');

    $rules[] = CoderForLife::create_rule('contact_redir',              6, '%^contact/(?P<email_hash>[A-Za-z0-9_-]+)/?$%i',                       'contact/{$email_hash}/');
    
    return $rules;
  }
  
  // Special contact (email) redirection
  public function action_plugin_act_contact_redir($handler) {
    // TODO: display the email on the resulting page in some fashion
    // TODO: filter based on 
    static $ip127007 = 0x7F000001; // ip2long('127.0.0.7');
    $ip = $_SERVER['REMOTE_ADDR'];
    $rev_ip = implode('.', array_reverse(explode('.', $ip)));
    $hostname = $rev_ip.'.zen.spamhaus.org';
    $records = dns_get_record($hostname, DNS_A);
    if ($records !== FALSE) {
      foreach ($records as $record) {
        if ($record['type'] == 'A' && ip2long($record['ip']) <= $ip127007) { // $record['host'] == $hostname && $record['class'] == 'IN' &&
          header('HTTP/1.0 403 Forbidden');
          echo '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">'."\n";
          echo '<html><head><title>Coder for Life - Contact</title></head><body><p>Your IP ($ip) is blacklisted by <a href="http://spamhaus.org">spamhaus.org</a>.</p></body></html>';
          return;
        }
      }
    }
    $email_hash = $handler->handler_vars['email_hash'];
    header('Location: mailto:'.LinkFormater::decrypt_email($email_hash));
    $prev = $_SERVER['HTTP_REFERER'];
    $host = $_SERVER['HTTP_HOST'];
    $pos = stripos($prev, $host);
    if (!$prev || $pos < 7 || $pos > 12) { $prev = false; }
    echo '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">'."\n";
    echo '<html><head><title>Coder for Life - Contact</title>';
    if ($prev) { echo "<meta HTTP-EQUIV=\"REFRESH\" content=\"1; url=$prev\">"; }
    echo '<script type="text/javascript">function goback(){window.history.back();}</script></head><body onLoad="setTimeout(goback, 500)">';
    echo '<p>Your mailto program should have opened. This will go back to the page you came from in a moment. If not please <a href="'.($prev?$prev:'javascript:goback()').'" rel="nofollow">click here</a>.</p>';
    echo '</body></html>';
  }

  public function filter_post_type_display($type, $foruse) {
    $names = array('project' => array('singular' => _t('Project'), 'plural' => _t('Projects')));
    return isset($names[$type][$foruse]) ? $names[$type][$foruse] : $type;
  }

  public static function get_proj_arr($s, $extra = null) {
    $arr = array('content_type'=>Post::type('project'),'status'=>Post::status('published'),'slug'=>array($s));
    return $extra ? array_merge($arr, $extra) : $arr;
  }

  // handle project URLs
  public function action_plugin_act_display_project($handler) {
    $name = str_replace('/', '-', $handler->handler_vars['name']);
    $handler->theme->act_display_post(CoderForLife::get_proj_arr("projects-$name"));
  }
  public function action_plugin_act_atom_feed_project_comments($handler) {
    $ah = new AtomHandler();
    $name = str_replace('/', '-', $handler->handler_vars['name']);
    $ah->get_comments(array('slug'=>"projects-$name"));
  }
  public function action_plugin_act_atom_feed_projects($handler) {
    $ah = new AtomHandler();
    $ah->get_collection(array('content_type'=>Post::type('project'),'status'=>Post::status('published')));
  }
  public function filter_rewrite_args($args, $name) {
    if ($name == 'atom_feed_project_comments') {
      if (array_key_exists('slug', $args))
        $args['name'] = str_replace('-', '/', substr($args['slug'], 9));
      else if (!array_key_exists('name', $args))
        $args['name'] = '';
    }
    return $args;
  }
  public function filter_atom_get_collection_alternate_rules($alt_rules)
  {
		$alternate_rules['atom_feed_projects'] = 'display_project';
		$alternate_rules['atom_feed_project_comments'] = 'display_home';
  }

  // handle W7BU bootskin URLs
  public function action_plugin_act_display_w7bu_user($handler)
  {
    $handler->theme->user_slug = $handler->handler_vars['user_slug'];
    $handler->theme->act_display_post(CoderForLife::get_proj_arr('projects-win7boot-skins-user'));
  }
  public function action_plugin_act_display_w7bu_skin($handler)
  {
    $handler->theme->skin_slug = $handler->handler_vars['skin_slug'];
    $handler->theme->act_display_post(CoderForLife::get_proj_arr('projects-win7boot-skins-skin'));
  }


  private static function get_requested_date($hv)
  {
    $year = array_key_exists('year', $hv) ? $hv['year'] : false;
    $month = array_key_exists('month', $hv) ? $hv['month'] : false;
    $day = array_key_exists('day', $hv) ? $hv['day'] : false;

    // will assume that if there is a day, there is also a month, and if there is a month then there is a year

    return ($year === false) ? 'unknown date'
      : (($month === false) ? date('Y', mktime(12, 0, 0, $month, $day, $year))
      : (($day === false) ? date('M Y', mktime(12, 0, 0, $month, $day, $year))
      : date('M j, Y', mktime(12, 0, 0, $month, $day, $year))));
  }

  private static function find_img($c)
  {
    static $any = '(?:[^>]*)';
    static $img_start = '~<(img|zoom|thumb)\s+(?:[^>]+[\s\'"])?';
    static $img_src = 'src=(\'|"|)([^\s>\'"]+)\1';
    static $img_main = '\bmain\b';
    $img_end = "$any>~i";
    if (preg_match($img_start.$img_src.$any.$img_main.$img_end, $c, $matches)) return $matches[2]; // Look for images with the 'main' flag
    else if (preg_match($img_start.$img_src.$img_end, $c, $matches)) return $matches[2]; // Look for any images
    return NULL;
  }

  private static function rel2abs($rel, $base)
  {
    if (parse_url($rel, PHP_URL_SCHEME) != '') return $rel; /* already absolute */
    if ($rel[0]=='#' || $rel[0]=='?') return $base.$rel; /* queries and anchors */
    extract(parse_url($base)); /* parse base URL and convert to local variables: $scheme, $host, $path */
    $path = preg_replace('#/[^/]*$#', '', $path); /* remove non-directory element from path */
    if ($rel[0] == '/') $path = ''; /* destroy path if relative url points to root */
    $abs = "$host$path/$rel"; /* dirty absolute URL */
    $re = array('#(/\.?/)#', '#/(?!\.\.)[^/]+/\.\./#'); /* replace '//' or '/./' or '/foo/../' with '/' */
    for($n=1; $n>0; $abs=preg_replace($re, '/', $abs, -1, $n)) {}
    return $scheme.'://'.$abs; /* absolute URL is ready! */
  }

  function action_add_template_vars($theme, $handlervars)
  {
    $is_https = (bool)$_SERVER['HTTPS'];
    $port = $_SERVER['SERVER_PORT'];
    $theme->base_url =
    ($is_https ? 'https://' : 'http://') .
    ($_SERVER['HTTP_HOST'] ? $_SERVER['HTTP_HOST'] : ($_SERVER['REMOTE_HOST'] ? $_SERVER['REMOTE_HOST'] : $_SERVER['REMOTE_ADDR']) ) .
    ($port != ($is_https ? 443 : 80) ? ":$port" : '');
    $theme->requested_url = $theme->base_url . $_SERVER['REQUEST_URI'];

    if ($theme->request) {
      $r = $theme->request;
      $p = $theme->post;
      $theme->request->is_article = $r->display_entry || $r->display_page || $r->display_project;
      $theme->request->is_project = $r->display_project;
      $theme->page_title =
      $r->is_project ? "Project - $p->title" // display_project
        : ($r->is_article ? $p->title // display_entry or display_page
        : ($r->display_home ? 'Home'
        : ($r->display_entries_by_date ? 'Entries for '.CoderForLife::get_requested_date($handlervars)
        : ($r->display_entries_by_tag ? "Entries tagged with {$handlervars['tag']}"
        : ($r->display_search ? "Search for '{$handlervars['criteria']}'"
        : ($r->display_404 ? 'Page not found'
        : '')))))); // admin, user, user_profile, rsd, display_entries

      $theme->page_desc =
      $r->is_article ? ($p->desc ? $p->desc : $p->tiny_content_excerpt) // display_project, display_entry or display_page
        : ($r->display_home ? 'Homepage of the Coder for Life blog and projects'
        : ($r->display_entries_by_date ? 'Coder for Life blog entries and projects for '.CoderForLife::get_requested_date($handlervars)
        : ($r->display_entries_by_tag ? "Coder for Life blog entries and projects tagged with {$handlervars['tag']}"
        : ($r->display_search ? "Coder for Life blog entries and projects when searching for '{$handlervars['criteria']}'"
        : ($r->display_404 ? '404 Error: This page does not exist'
        : ''))))); // admin, user, user_profile, rsd, display_entries

      $found_pic = false;
      if ($theme->request->is_article) {
        $q = $p;
        do { // Look for a picture attached to the post (or a parent of the post)
          $pic = $q->picture;
          if ($pic) { $theme->page_image = $q->permalink.$pic; $found_pic = true; break; }
        } while ($q = $q->parent);

        if (!$found_pic) {
          $q = $p;
          do { // Look for a picture anywhere in the post (or a parent of the post)
            $pic = CoderForLife::find_img($q->content);
            if ($pic) { $theme->page_image = CoderForLife::rel2abs($pic, $q->permalink); $found_pic = true; break; }
          } while ($q = $q->parent);
        }
      }
      if (!$found_pic)
        $theme->page_image = $theme->base_url.'/images/c4l.png';
    }
  }

  //function filter_spam_filter($rating, $comment, $handler_vars) { return $rating; }

  // short hand functions
  public function filter_post_style($style, $post) { return $post->info->style; }
  public function filter_post_name($name, $post) { return ($post->content_type == Post::type('project')) ? ($post->info->name ? $post->info->name : $post->title) : $post->title; }
  public function filter_post_desc($desc, $post) { return ($post->content_type == Post::type('project')) ? $post->info->desc : $desc; }
  public function filter_post_start($start, $post) { return ($post->content_type == Post::type('project')) ? $post->info->start : $start; }
  public function filter_post_end($end, $post) { return ($post->content_type == Post::type('project')) ? $post->info->end : $end; }
  public function filter_post_picture($picture, $post) { return ($post->content_type == Post::type('project')) ? $post->info->picture : $picture; }
  public function filter_post_show_picture($show_picture, $post) { return ($post->content_type == Post::type('project')) ? $post->info->show_picture : $show_picture; }
  public function filter_post_picture_alt($picture_alt, $post) { return ($post->content_type == Post::type('project')) ? $post->info->picture_alt : $picture_alt; }
  public function filter_post_donation($donation, $post) { return ($post->content_type == Post::type('project')) ? $post->info->donation : $donation; }

  // project utility parameters
  public function filter_post_is_project($x, $post) { return $post->content_type==Post::type('project'); }
  public function filter_post_project_slug($x, $post) { return ($post->content_type==Post::type('project')) ? substr($post->slug, 9) : $post->slug; }
  public function filter_post_is_sub_page($x, $post) { return ($post->content_type==Post::type('project')) && (strpos($post->slug, '-', 10) !== FALSE); }
  public function filter_post_parent($x, $post) { return $post->is_sub_page ? Post::get(CoderForLife::get_proj_arr(substr($post->slug, 0, strrpos($post->slug, '-')))) : NULL; }
  public function filter_post_types($x, $post) { return CoderForLife::type_tags_only($post->tags); }
  public function filter_post_is_best($x, $post) { return $post->tags->has('@best'); }
  public function filter_post_is_current($x, $post) { return ($post->content_type == Post::type('project')) ? !$post->end : FALSE; }

  // Process the content of a comment
  public function filter_comment_content_out($content, $comment) {
    $post = Post::get(array('id'=>$comment->post_id));
    $c = StripHTML::strip_bad_tags($content);
    $c = GeshiFormater::geshi($c);
    return LinkFormater::linkify(Format::autop($c), $post, true, $comment->email != $post->author->email);
  }

  // Process the style of a post
  public function filter_post_content_out($content, $post) {
    $c = SpecialTagsFormater::special_tags($content);
    $c = GeshiFormater::geshi($c);
    return LinkFormater::linkify($post->style == 'raw' ? PhpFormater::run_php($c) : /*simple*/ Format::autop($c), $post);
  }

  // Process post excerpts
  public function filter_post_content_excerpt($ce, $post) {
    $ce = StripHTML::strip_excerpt_tags($ce);
    $ce = LinkFormater::linkify($post->style == 'raw' ? $ce : Format::autop($ce), $post);
    return Format::more($ce, $post, _t('Read More &raquo;'), 100, 2); // Limit post length on listings to 2 paragraphs or 100 words
  }

  // Process post excerpts
  public function filter_post_tiny_content_excerpt($x, $post) {
    return StripHTML::strip_all_html(Format::more(StripHTML::strip_all_html($post->content), $post, '', 50, 1)); // Limit to 1 paragraphs or 50 words
  }

  public function filter_post_permalink($content, $post) {
    if ($post->is_project)
      $content = str_replace(array('{$name}', '-'), array($post->project_slug, '/'), $content);
    if ($content[strlen($content) - 1] != '/')
      $content .= '/';
    return $content;
  }

  //Hidden (special) tags
  // @xxxx  (general: best, ...)
  // #xxxx  (types: C, C++, electronics, ...)
  public static function filter_tags($tags, $func)
  {
    $out = new Terms();
    foreach ($tags as $tag)
      if (call_user_func($func, $tag->term_display))
        $out[] = $tag;
    return $out;
  }
  public static function type_tags_only($tags) { return CoderForLife::filter_tags($tags, 'CoderForLife::is_type_tag'); }
  public static function type_tags() { return CoderForLife::type_tags_only(Tags::get_by_frequency(null, 'project')); }
  public static function is_type_tag($a) { return $a[0] == '#'; }
  public static function is_special_tag($a) { return $a[0] == '@' || $a[0] == '#'; }
  public static function is_not_special_tag($a) { return !CoderForLife::is_special_tag($a); }
  public function filter_post_tags_out($tags) { return Format::tag_and_list(CoderForLife::filter_tags($tags, 'CoderForLife::is_not_special_tag')); 	}
  public static function tag_slugs($tags) { $out = array(); foreach ($tags as $tag) $out[] = $tag->term; return $out; }
  public static function tag_names($tags) { $out = array(); foreach ($tags as $tag) $out[] = $tag->term_display; return $out; }
  public static function type_name($n) {
    if ($n[0] == '#') $n = substr($n, 1);
    switch ($n) {
    case 'Web': return 'Web (HTML/JS/CSS)';
    case 'CPP': return 'C++';
    case 'CS':  return 'C#';
    //case 'VB': return 'Visual Basic';
    default: return $n;
    }
  }
  public static function type_short_name($n) {
    if ($n[0] == '#') $n = substr($n, 1);
    switch ($n) {
    case 'Web (HTML/JS/CSS)': return 'Web';
    case 'CPP':               return 'C++';
    case 'CS':                return 'C#';
    //case 'Visual Basic': return 'VB';
    default: return $n;
    }
  }

  public function action_form_publish($form, $post) {
    // Post Style (raw or simple)
    $form->settings->append('select', 'post_style', 'null:null', _t('Style'), array('raw'=>'raw', 'simple'=>'simple'), 'tabcontrol_select');
    $form->post_style->value = $post->style ? $post->style : 'simple';

    if ($post->content_type == Post::type('project')) {
      $settings = $form->publish_controls->append('fieldset', 'projectSettings', _t('Project Settings'));

      // Project Information
      $settings->append('text', 'pname', 'null:null', _t('Name (on Projects page)'), 'tabcontrol_text');
      $settings->pname->value = $post->name ? $post->name : '';
      $settings->append('text', 'desc', 'null:null', _t('Description (on Projects)'), 'tabcontrol_text');
      $settings->desc->value = $post->desc ? $post->desc : '';

      // Project Years
      $settings->append('text', 'start', 'null:null', _t('Started Year (on Projects)'), 'tabcontrol_text');
      $settings->start->value = $post->start ? $post->start : '';
      $settings->append('text', 'end', 'null:null', _t('Ended Year (on Projects)'), 'tabcontrol_text');
      $settings->end->value = $post->end ? $post->end : '';

      // Project Picture
      $settings->append('text', 'pic', 'null:null', _t('Picture'), 'tabcontrol_text');
      $settings->pic->value = $post->picture ? $post->picture : '';
      $settings->append('checkbox', 'show_pic', 'null:null', _t('Show Picture On Post'), 'tabcontrol_checkbox');
      $settings->show_pic->value = $post->show_picture;
      $settings->append('text', 'pic_alt', 'null:null', _t('Picture Alt/Title Text (on Projects)'), 'tabcontrol_text');
      $settings->pic_alt->value = $post->picture_alt ? $post->picture_alt : '';

      // Dontation Button
      $settings->append('text', 'donation', 'null:null', _t('Donation Value Encrypted'), 'tabcontrol_text');
      $settings->donation->value = $post->donation ? $post->donation : '';
    }
  }

  public function action_save_post($post, $form) {
    action_publish_post($post, $form);
  }

  public function action_publish_post($post, $form) {
    // Post Style (raw or simple)
    $post->info->style = $form->post_style->value;

    if ($post->content_type == Post::type('project')) {
      // Project Information
      $post->info->name = $form->pname->value;
      $post->info->desc = $form->desc->value;

      // Project Years
      $post->info->start = $form->start->value;
      $post->info->end = $form->end->value;

      // Project Picture
      $post->info->picture = $form->pic->value;
      $post->info->show_picture = $form->show_pic->value;
      $post->info->picture_alt = $form->pic_alt->value;

      // Dontation Button
      $post->info->donation = $form->donation->value;
    }
  }
}

?>