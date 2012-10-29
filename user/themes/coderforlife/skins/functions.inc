<?php

if (!array_key_exists('skins', $_SESSION)) $_SESSION['skins'] = array();

$logged_in = array_key_exists('userid', $_SESSION['skins']);

if (!$logged_in)
{
  $messages = array();
  if (array_key_exists('login_username', $_POST))
  {
    $user = trim($_POST['login_username']); // or email
    $slug = slugify($user);
    $pass = trim($_POST['login_password']);
    $db = dbconnect();
    $stmt = $db->prepare('SELECT id, verified, reset_password FROM users WHERE (slug=? OR email=?) AND password=PASSWORD(?) LIMIT 1');
    $stmt->bind_param('sss', $slug, $user, $pass);
    $stmt->execute();
    $stmt->bind_result($id, $verified, $reset_password);
    $fetch = !!$stmt->fetch();
    $stmt->close();
    if (!$fetch)
      $messages[] = '<p class="error">Username/email and password failed</p>';
    else
    {
      $logged_in = true;
      if ($verified == 0)
        $db->query("UPDATE users SET verified=1 WHERE id=$id LIMIT 1");
      $_SESSION['skins']['userid'] = $id*1;
      $_SESSION['skins']['needs_pw_change'] = $reset_password == 1;
    }
  }
}

if ($logged_in)
{
  if (isset($logout) && $logout)
  {
    $_SESSION['skins'] = array();
    unset($_SESSION['skins']);
    $page = '/projects/win7boot/skins/';
    if (isset($_SERVER['HTTP_REFERER']) && $_SERVER['HTTP_REFERER'])
    {
      $referer = parse_url($_SERVER['HTTP_REFERER']);
      if (($referer['host'] == 'coderforlife' || str_ends_with($referer['host'], 'coderforlife.com')) && ($referer['path'] != '/projects/win7boot/skins/myaccount/'))
        $page = $referer['path']; // include 'query' and 'fragment'?
    }
    header('Location: '.$page);
    exit();
  }
  
  if ($_SESSION['skins']['needs_pw_change'])
  {
    include 'project.projects-win7boot-skins-resetpw.inc';
    if ($_SESSION['skins']['needs_pw_change'])
      exit();
  }
  
  if (array_key_exists('originating_page', $_SESSION['skins']))
  {
    $page = $_SESSION['skins']['originating_page'];
    unset($_SESSION['skins']['originating_page']);
    if ($_SERVER['REQUEST_URI'] != $page)
    {
      header('Location: '.$page);
      exit();
    }
  }
}

function str_ends_with($string, $test)
{
    $testlen = strlen($test);
    return ($testlen <= strlen($string)) && substr_compare($string, $test, -$testlen) === 0;
}

function get_int($data, $pos) { return ord($data[$pos]) | (ord($data[$pos+1]) << 8) | (ord($data[$pos+2]) << 16) | (ord($data[$pos+3]) << 24); }
function set_int(&$data,$pos, $x) { $data[$pos] = chr($x&0xFF); $data[$pos+1] = chr(($x>>8)&0xFF); $data[$pos+2] = chr(($x>>16)&0xFF); $data[$pos+3] = chr(($x>>24)&0xFF); }

function round_up_to($x, $n) { $r = $x % $n; return $r ? $x + $n - $r : $x; }
function round_to_int($x) { return (int)($x + 0.5); }

function format_long_text($text)
{
  $text = trim($text);
  return $text ? Format::autop(LinkFormater::linkify(GeshiFormater::geshi(StripHTML::strip_bad_tags($text)))) : '';
}

function s($x) { return ($x == 1) ? '' : 's'; }

function POST($key, $def = '') { return array_key_exists($key, $_POST) ? $_POST[$key] : $def; }
function echoValFromPOST($key) { if (array_key_exists($key, $_POST)) echo ' value="'.htmlentities($_POST[$key]).'"'; }

function slugify($name) { return preg_replace(array('/[^a-z0-9]+/','/^-|-$/'), array('-',''), strtolower($name)); }

function skins_file_path($file) { return dirname(realpath(__FILE__)).'/'.$file; }

function dbconnect() { return new mysqli('localhost', 'jeff', 'ja87bush', 'jeff_bootskins'); }

function name_already_exists($db, $tbl, $name, $slug)
{
  $stmt = $db->prepare("SELECT id FROM $tbl WHERE name=? OR slug=? LIMIT 1");
  $stmt->bind_param('ss', $name, $slug);
  $stmt->execute();
  $stmt->bind_result($id);
  $result = !!$stmt->fetch();
  $stmt->close();
  return $result;
}

function random_password()
{
  $letters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';
  $numbers = '0123456789';
  $symbols = '!@#$%^&*()-_=+`~[]{}\|;:\'",./<>?';
  $x = str_repeat($letters, 5).str_repeat($numbers, 5).$symbols;
  return substr(str_shuffle($x), 0, 8);
}

function valid_email($email)
{
  $top_level = '(?:[A-Z]{2}|com|org|net|edu|gov|mil|biz|info|mobi|name|aero|asia|jobs|museum)';
  if (preg_match('/^([\w!#$%&\'*+\/=?^`{|}~-]+(?:\.[\w!#$%&\'*+\/=?^`{|}~-]+)*)@((?:[a-z0-9](?:[a-z0-9-]*[a-z0-9])?\.)+'.$top_level.')$/', $email, $matches) != 1)
    return false;
  $name = $matches[1];
  $domain = $matches[2];
  $has_mx = getmxrr($domain, $mxhosts);
  $count_mx = count($mxhosts);
  if (!$has_mx || $count_mx == 0 || ($count_mx == 1 && ($mxhosts[0] == NULL || $mxhosts[0] == '0.0.0.0')))
    return false;
  return checkdnsrr($domain, 'A');
}

function sendmail($to, $subject, $body)
{
  $headers =
    'From: no-reply@coderforlife.com'."\r\n".
    'Reply-To: admin@coderforlife.com'."\r\n".
    'Return-Path: admin@coderforlife.com'."\r\n".
    'X-Sender: admin@coderforlife.com'."\r\n".
    'MIME-Version: 1.0'."\r\n".
    'X-Mailer: PHP/'.phpversion()."\r\n".
    'Message-ID: <'.md5(time()).'.bootskins@coderforlife.com>'."\r\n";
  return mail($to, $subject, $body, $headers);
}

function get_value_0($db, $query)
{
  $result = $db->query($query);
  $row = $result->fetch_row();
  $result->close();
  return $row[0];
}

function get_value_1($db, $query, $types, $var1)
{
  $stmt = $db->prepare($query); 
  $stmt->bind_param($types, $var1);
  $stmt->execute();
  $stmt->bind_result($out);
  $stmt->fetch();
  $stmt->close();
  return $out;
}

function get_value_2($db, $query, $types, $var1, $var2)
{
  $stmt = $db->prepare($query); 
  $stmt->bind_param($types, $var1, $var2);
  $stmt->execute();
  $stmt->bind_result($out);
  $stmt->fetch();
  $stmt->close();
  return $out;
}

function get_flag_count($db, $type, $id)
{
  if (!array_key_exists('_flag_counter', $GLOBALS))
    $GLOBALS['_flag_counter'] = $db->prepare('SELECT COUNT(*) FROM flags WHERE type=? AND id=?');
  $stmt = $GLOBALS['_flag_counter']; 
  $stmt->bind_param('si', $type, $id);
  $stmt->execute();
  $stmt->store_result();
  $stmt->bind_result($count);
  $stmt->fetch();
  return $count*1;
}

function get_total_skin_flag_count($db, $userid)
{
  return get_value_1($db, 'SELECT COUNT(*) FROM skins, flags WHERE skins.user_id=? AND skins.id=flags.id AND flags.type=\'skin\'', 'i', $userid)*1;
}

function get_max_allowed_packet($db)
{
  if (!array_key_exists('_max_allowed_packet', $GLOBALS))
    $GLOBALS['_max_allowed_packet'] = get_value_0($db, 'SELECT @@global.max_allowed_packet LIMIT 1')*1;
  return $GLOBALS['_max_allowed_packet'];
}

function send_large_data($stmt, $index, $data, $db)
{
  $max_allowed_packet = get_max_allowed_packet($db);
  $parts = str_split($data, $max_allowed_packet - 32);
  foreach ($parts as $part)
    $stmt->send_long_data($index, $part);
}

function get_xml_child(DOMNode $node, $tag_name)
{
  for ($n = $node->firstChild; $n != NULL; $n = $n->nextSibling)
    if ($n->nodeType == XML_ELEMENT_NODE && $n->nodeName == $tag_name)
      return $n;
  return NULL;
}

function get_xml_content(DOMNode $node)
{
  switch ($node->nodeType)
  {
    case XML_ELEMENT_NODE: case XML_DOCUMENT_FRAG_NODE:
      $text = '';
      for ($n = $node->firstChild; $n != NULL; $n = $n->nextSibling)
        $text .= get_xml_content($n);
      return $text;
    case XML_DOCUMENT_NODE:
      return get_xml_content($node->documentElement);
    case XML_TEXT_NODE: case XML_CDATA_SECTION_NODE:
      return $node->nodeValue;
    default:
    //case XML_COMMENT_NODE:
    //case XML_ATTRIBUTE_NODE:
    //case XML_DOCUMENT_TYPE_NODE:
    //case XML_PI_NODE:
    //case XML_ENTITY_REF_NODE: case XML_ENTITY_NODE:
    //case XML_NOTATION_NODE:
    //case XML_HTML_DOCUMENT_NODE:
    //case XML_DTD_NODE: case XML_ELEMENT_DECL_NODE: case XML_ATTRIBUTE_DECL_NODE: case XML_ENTITY_DECL_NODE: case XML_NAMESPACE_DECL_NODE: 
      return '';
  }
}

function append_xml_cid(DOMNode $node, $tag_name, &$cids)
{
  $n = get_xml_child($node, $tag_name);
  if ($n !== NULL && $n->hasAttribute('cid'))
    $cids[] = trim($n->getAttribute('cid'));
}

function get_xml_cids(DOMNode $node)
{
  $cids = array();
  append_xml_cid($node, 'Animation', $cids);
  append_xml_cid($node, 'BackgroundImage', $cids);
  return $cids;
}

function get_xml_color(DOMNode $node, $tag_name, $default)
{
  $n = get_xml_child($node, $tag_name);
  return ($n === NULL) ? $default : hexdec(trim(get_xml_content($n)));
}

function imagepng_string($im)
{
  ob_start();
  if (!imagepng($im, null, 9))
  {
    ob_end_clean();
    return null;
  }
  return ob_get_clean();
}

// accepts either an image object or an image string
// returns png image string
function ensure_is_png($im)
{
  $from_string = is_string($im);
  if ($from_string)
  {
    if (strncmp($im, "\x89\x50\x4E\x47\x0D\x0A\x1A\x0A", 8) === 0)
      return $im; // already png image string
    $im = imagecreatefromstring($im);
  }
  $png = imagepng_string($im);
  if ($from_string)
    imagedestroy($im);
  return $png;
}

// accepts either an image object or an image string
// returns png image string
function shrink_image($full, $ratio = 0.5)
{
  $from_string = is_string($full);
  if ($from_string)
    $full = imagecreatefromstring($full);
  $w2 = round_to_int(($w = imagesx($full)) * $ratio);
  $h2 = round_to_int(($h = imagesy($full)) * $ratio);
  $half = imagecreatetruecolor($w2, $h2);
  imagecopyresampled($half, $full, 0, 0, 0, 0, $w2, $h2, $w, $h);
  $png = imagepng_string($half);
  imagedestroy($half);
  if ($from_string)
    imagedestroy($full);
  return $png;
}

function get_anim_img(DOMNode $xml, MultipartFile $bs7, DOMNode $winload = NULL)
{
  $anim = get_xml_child($xml, 'Animation');
  if ($anim !== NULL)
  {
    $s = $anim->hasAttribute('source') ? $anim->getAttribute('source') : NULL;
    if (!$s || $s == 'embedded')
      return $anim->hasAttribute('cid') ? $bs7->get_part($anim->getAttribute('cid')) : base64_decode(get_xml_content($anim));
    else
      return ($s == 'winload' && $winload) ? get_anim_img($winload, $bs7, NULL) : file_get_contents(skins_file_path(($s == 'default') ? 'activity.png' : 'invalid.png'));
  }
  else return $winload ? get_anim_img($winload, $bs7, NULL) : file_get_contents(skins_file_path('activity.png'));
}

function draw_message($im, DOMNode $xml, $backColor)
{
  $font_file = skins_file_path('segoeui.ttf');

  $text = get_xml_content(get_xml_child($xml, 'Text'));
  $pos = get_xml_content(get_xml_child($xml, 'Position'))*1;
  $color = get_xml_color($xml, 'TextColor', 0xFFFFFF);
  $size = get_xml_content(get_xml_child($xml, 'TextSize'))*1;
  
  // Create image with just the text that is black (opaque) to white (transparent)
  $box = imagettfbbox($size, 0, $font_file, $text);
  $min_x = min($box[0], $box[2], $box[4], $box[6]);
  $max_x = max($box[0], $box[2], $box[4], $box[6]);
  $min_y = min($box[1], $box[3], $box[5], $box[7]);
  $max_y = max($box[1], $box[3], $box[5], $box[7]);
  $w = $max_x - $min_x; $w2 = $w*2;
  $h = $max_y - $min_y; $h2 = $h*2;
  $tmp = @imagecreatetruecolor($w2, $h2);
  imagesavealpha($tmp, false);
  imagealphablending($tmp, true);
  imagefill($tmp, 0, 0, 0xFFFFFF);
  $box = imagettftext($tmp, $size, 0, $w/2, $h*3/2, 0x000000, $font_file, $text); // this box is still wrong (off by 3 pixels in the x direction)

  /*$_min_x = min($box[0], $box[2], $box[4], $box[6]);
  $_max_x = max($box[0], $box[2], $box[4], $box[6]);
  $_min_y = min($box[1], $box[3], $box[5], $box[7]);
  $_max_y = max($box[1], $box[3], $box[5], $box[7]);
  imagerectangle($tmp, $_min_x, $_min_y, $_max_x, $_max_y, 0xFF0000);
  imagepng($tmp, skins_file_path('text'.$xml->getAttribute('id').'.png'));*/

  // Find real bounds
  $min_x = $w2; $max_x = 0;
  $min_y = $h2; $max_y = 0;
  for ($x = 0; $x < $w2; ++$x)
    for ($y = 0; $y < $h2; ++$y)
      if (imagecolorat($tmp, $x, $y) != 0xFFFFFF)
      {
        if ($x < $min_x) $min_x = $x;
        if ($x > $max_x) $max_x = $x;
        if ($y < $min_y) $min_y = $y;
        if ($y > $max_y) $max_y = $y;
      }
  $w = $max_x - $min_x + 1;
  $h = $max_y - $min_y + 1;
  $block_height = (int)($h*1.2);
  $startx = (1024 - $w) / 2;
  $starty = $pos + ($block_height - $h) / 2;
  
  if ($startx < 0) { $min_x -= $startx; $max_x = $min_x + 1023; $startx = 0; }
  else if ($w > 1024) { $max_x = $min_x + 1023; }

  if ($starty + $h > 768) { $max_y = $min_y + 767 - $starty; }

  // Copy text pixel-wise, converting black level to levels of transparency of the desired color
  imagefilledrectangle($im, 0, $pos, 1024, $pos + $block_height, $backColor);
  for ($x = $min_x, $x_ = $startx; $x < $max_x+1; ++$x, ++$x_)
    for ($y = $min_y, $y_ = $starty; $y < $max_y+1; ++$y, ++$y_)
      if (($c = imagecolorat($tmp, $x, $y)) != 0xFFFFFF)
        imagesetpixel($im, $x_, $y_, ((($c & 0xFF) >> 1) << 24) | $color);
  
  // Cleanup
  imagedestroy($tmp);
}

function generate_preview(DOMNode $xml, MultipartFile $bs7, $frame = 61, DOMNode $winload = NULL)
{
  $im = @imagecreatetruecolor(1024, 768);
  imagesavealpha($im, true);
  imagealphablending($im, true);
  
  // BackgroundColor
  imagefilledrectangle($im, 0, 0, 1024, 768, get_xml_color($xml, 'BackgroundColor', 0x000000));
  
  // BackgroundImage
  $bgimg = get_xml_child($xml, 'BackgroundImage');
  if ($bgimg !== NULL)
  {
    $bgimg = imagecreatefromstring($bgimg->hasAttribute('cid') ? $bs7->get_part($bgimg->getAttribute('cid')) : base64_decode(get_xml_content($bgimg)));
    imagecopy($im, $bgimg, 0, 0, 0, 0, imagesx($bgimg), imagesy($bgimg));
  }
  
  // Animation
  $anim = imagecreatefromstring(get_anim_img($xml, $bs7, $winload));
  imagecopy($im, $anim, 412, 284, 0, (imagesy($anim) == 200) ? 0 : $frame * 200, 200, 200);
  
  // Messages
	$msgs = get_xml_child($xml, 'Messages');
	if ($msgs !== NULL)
	{
		$msgBackColor = get_xml_color($msgs, 'BackgroundColor', 0x000000);
		$msgs = $msgs->getElementsByTagName('Message');
		$out_msgs = array();
		for ($i = 0; $i < $msgs->length; ++$i)
		{
		  $m = $msgs->item($i);
			$out_msgs[$m->getAttribute('id')] = $m;
		}
    ksort($out_msgs, SORT_NUMERIC);
    foreach ($out_msgs as $m)
      draw_message($im, $m, $msgBackColor);
	}
  
  return $im;
}
        
?>