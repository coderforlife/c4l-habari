<?php

function create($w, $h) {
  global $black, $dark, $light;
  $img = imagecreate($w*5, $h*8);
  $black = imagecolorallocate($img, 0, 0, 0);
  $dark = imagecolorallocate($img, 0x19, 0x4C, 0x00);
  $light = imagecolorallocate($img, 0x33, 0x99, 0x00);
  imagefill($img, 0, 0, $black);
  return $img;
}
function finish($img) {
  header('Content-type: image/png');
  imagepng($img, null, 9);
}

function draw0($img, $x, $y, $c) {
  $x *= 5; $y *= 8;
  for ($i = $x+1; $i < $x+3; $i++) { imagesetpixel($img, $i, $y, $c); imagesetpixel($img, $i, $y+6, $c); }
  for ($i = $y+1; $i < $y+6; $i++) { imagesetpixel($img, $x, $i, $c); imagesetpixel($img, $x+3, $i, $c); }
}
function draw1($img, $x, $y, $c) {
  $x *= 5; $y *= 8;
  for ($i = $y; $i < $y+7; $i++) imagesetpixel($img, $x+2, $i, $c);
}
function drawRandom($img, $x, $y, $c) {
  $f = mt_rand(0,1)==0?'draw0':'draw1';
  $f($img, $x, $y, $c);
}

function load($name) {
  $file = file('bgimages/'.$name.'.txt');
  foreach ($file as $i=>$line)
    $file[$i] = str_replace(array("\0","\r","\n","\t"),'',trim($file[$i],"\0\r\n\t"));
  return array($file, strlen($file[0]), count($file));
}
function process($d, $img, $x, $y) {
  global $light, $dark;
  switch ($d) {
    case '0': draw0($img, $x, $y, $light); break;
    case '1': draw1($img, $x, $y, $light); break;
    case 'o': draw0($img, $x, $y, $dark); break;
    case 'i': draw1($img, $x, $y, $dark); break;
    case '*': break;
    case 'X': drawRandom($img, $x, $y, $light); break;
    default:  drawRandom($img, $x, $y, $dark);
  }
}

if ($_SERVER['QUERY_STRING'] == '') {
  $W = 50; $H = 35;
  $img = create($W, $H);

  /*$red = imagecolorallocate($img, 0xFF, 0, 0);

  $arr = array_fill(0, $W, array_fill(0, $H, true));
  $types = array('fish','small-tree');
  foreach ($types as $t) {
    list($data, $w, $h) = load($t);
    while (true) {
      $x_ = mt_rand(0, $W-1);
      $y_ = mt_rand(0, $H-1);
      for ($x = 0; $x < $w; $x++) {
        $X = ($x+$x_)%$W;
        for ($y = 0; $y < $h; $y++)
          if (!$arr[$X][($y+$y_)%$H]) continue 3;
      }
      break;
    }
    for ($x = 0; $x < $w; $x++) {
      $X = ($x+$x_)%$W;
      for ($y = 0; $y < $h; $y++) {
        $Y = ($y+$y_)%$H;
        $arr[$X][$Y] = false;
        process($data[$y][$x], $img, $X, $Y);
      }
    }
  }*/
  for ($x = 0; $x < $W; $x++)
    for ($y = 0; $y < $H; $y++)
//      if ($arr[$x][$y])
//        drawRandom($img, $x, $y, ($y==0||$x==0)?$red:$dark);
        drawRandom($img, $x, $y, $dark);
} else {
  list($data, $w, $h) = load('tree');
  $img = create($w, $h);
  for ($x = 0; $x < $w; $x++)
    for ($y = 0; $y < $h; $y++)
      process($data[$y][$x], $img, $x, $y);
}

finish($img);

?>