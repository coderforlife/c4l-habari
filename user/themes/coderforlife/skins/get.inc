<?php

//TODO: exe download fails with compression...

$type = trim($_SERVER['QUERY_STRING']); // bs7, exe, xml, or ~cid

function inc_value($db, $id, $name)
{
  $x = "skins_{$name}ed_$id";
  if (!array_key_exists($x, $_SESSION) || !$_SESSION[$x])
  {
    $_SESSION[$x] = true; // only count views / downloads once per visit
    $stmt = $db->prepare("UPDATE skins SET `$name`=`$name`+1 WHERE id=? LIMIT 1");
    $stmt->bind_param('i', $id); $stmt->execute();
  }
}

function output_compressed($data, $mimetype, $filename = null, $already_compressed = true)
{
  $supports_deflate = false;
  if (array_key_exists('HTTP_ACCEPT_ENCODING', $_SERVER))
  {
    $accepted = explode(',', strtolower($_SERVER['HTTP_ACCEPT_ENCODING']));
    foreach ($accepted as $x)
      if (trim($x) == 'deflate') { $supports_deflate = true; break; }
  }

  if ($filename)
  {
    header('Content-Description: File Transfer');
    header("Content-Disposition: attachment; filename=$filename");
  }
  header("Content-Type: $mimetype");
  header('Content-Transfer-Encoding: binary');
  
  $supports_deflate = false;
  
  if ($supports_deflate)
  {
    // Make sure that output compression is OFF - data is already compressed
    ini_set('output_handler', NULL);
    ini_set('zlib.output_compression', 'Off');
    
    //$status = ob_get_status(true);
    //ob_start();
    //var_dump($status);
    //file_put_contents(skins_file_path('ob_status.txt'), ob_get_clean());
    
    header('Content-Encoding: deflate');
    header('Vary: Accept-Encoding');
    if (!$already_compressed)
      $data = gzcompress($data, 6);
  }
  else if ($already_compressed)
  {
    $data = gzuncompress($data);
  }
  header('Content-Length: '.strlen($data));
  echo $data;
}

function output_png($data)
{
  $data = ensure_is_png($data);
  header('Content-Type: image/png');
  header('Content-Transfer-Encoding: binary');
  header('Content-Length: '.strlen($data));
  echo $data;
}

function get_skin_data($db, $id, $name)
{
  $stmt = $db->prepare('SELECT `'.$name.'` FROM skins WHERE id=? LIMIT 1');
  $stmt->bind_param('i', $id); $stmt->execute();
  $stmt->bind_result($data);
  $success = $stmt->fetch();
  $stmt->close();
  return $success ? $data : null;
}

function get_png($db, $id, $name, $half = false)
{
  $stmt = $db->prepare('SELECT `'.($half?'half':'full').'` FROM skin_pngs WHERE skin_id=? AND name=? LIMIT 1');
  $stmt->bind_param('is', $id, $name); $stmt->execute();
  $stmt->bind_result($data);
  $success = $stmt->fetch();
  $stmt->close();
  return $success ? $data : ($half ? get_png($db, $id, $name, false) : null);
}

if ($type == 'bs7')
{
  // output entire file
  inc_value($db, $id, 'download_count');
  output_compressed(get_skin_data($db, $id, 'original'), 'application/bs7', "$skin_slug.bs7");
}
else if ($type == 'xml')
{
  // output just xml file
  //inc_value($db, $id, 'viewed_count');
  $data = get_skin_data($db, $id, 'xml');
  output_compressed($data ? $data : get_skin_data($db, $id, 'original'), 'application/xml');
}
else if ($type == 'img')
{
  // output preview image
  inc_value($db, $id, 'viewed_count');
  output_png(get_png($db, $id, 'PREVIEW', $half));
}
else if ($type == 'exe')
{
  // output installer
  inc_value($db, $id, 'download_count');

  $file_alignment = 0x000200;
  $rsrc_rva       = 0x053000;
  $rsrc_data_pos  = 0x04F200;

  $init_data_size_offset = 0x000120;
  $image_size_offset     = $init_data_size_offset + 0x30;
  $res_table_size_offset = $init_data_size_offset + 0x6C;
  
  $rsrc_virt_size_offset = 0x0002A0;
  $rsrc_raw_size_offset  = $rsrc_virt_size_offset + 0x08;
  
  $bs_rva_offset   = $rsrc_data_pos + 0x01D8; $bs_size_offset   = $bs_rva_offset   + 0x04;
  $desc_rva_offset = $bs_rva_offset   + 0x10; $desc_size_offset = $desc_rva_offset + 0x04;
  $img_rva_offset  = $desc_rva_offset + 0x10; $img_size_offset  = $img_rva_offset  + 0x04;
  
  $data = file_get_contents($installer_path);
  $bs = get_skin_data($db, $id, 'original');
  $desc = gzcompress("<?xml version=\"1.0\" encoding=\"utf-8\"?>\n<BootSkinDescription><Name>$name</Name><Author>$author</Author><URL>$url</URL><Description>$desc_raw</Description><License>$license_raw</License></BootSkinDescription>", 6);
  $img = get_png($db, $id, 'PREVIEW-SMALL');

  $rsrc_virt_size = get_int($data, $rsrc_virt_size_offset);

  $bs_size   = strlen($bs);
  $desc_size = strlen($desc);
  $img_size  = strlen($img);

  $bs_rva    = $rsrc_rva + $rsrc_virt_size;
  $desc_rva  = $bs_rva + $bs_size;
  $img_rva   = $desc_rva + $desc_size;
  
  $increase_raw = $bs_size + $desc_size + $img_size;
  $increase = round_up_to($bs_size + $desc_size + $img_size, 0x10);
  $rsrc_new_size = $rsrc_virt_size + $increase;
  $rsrc_raw_size = round_up_to($rsrc_new_size, $file_alignment);
  
  set_int($data, $init_data_size_offset, $increase + get_int($data, $init_data_size_offset));
  set_int($data, $image_size_offset,     $increase + get_int($data, $image_size_offset));
  set_int($data, $res_table_size_offset, $rsrc_new_size);
  set_int($data, $rsrc_virt_size_offset, $rsrc_new_size);
  set_int($data, $rsrc_raw_size_offset,  $rsrc_raw_size);
  
  set_int($data, $bs_rva_offset,    $bs_rva);
  set_int($data, $bs_size_offset,   $bs_size);
  set_int($data, $desc_rva_offset,  $desc_rva);
  set_int($data, $desc_size_offset, $desc_size);
  set_int($data, $img_rva_offset,   $img_rva);
  set_int($data, $img_size_offset,  $img_size);
  
  $data = substr($data, 0, $bs_rva - $rsrc_rva + $rsrc_data_pos).$bs.$desc.$img.str_repeat("\0", $rsrc_raw_size - $rsrc_virt_size - $increase_raw);
  
  output_compressed($data, 'application/octet-stream', "$skin_slug.exe", false);
}
else
{
  // output image part
  $data = get_png($db, $id, $type, $half);
  if ($data !== NULL)
  {
    output_png($data);
  }
  else
  {
    header('HTTP/1.0 404 Not Found');
  }
}

?>