<?php

define('UTF8_BOM'               , "\xEF\xBB\xBF");
define('UTF16_BIG_ENDIAN_BOM'   , "\xFE\xFF");
define('UTF16_LITTLE_ENDIAN_BOM', "\xFF\xFE");
define('UTF32_BIG_ENDIAN_BOM'   , "\x00\x00\xFE\xFF");
define('UTF32_LITTLE_ENDIAN_BOM', "\xFF\xFE\x00\x00");
define('UTF1_BOM'               , "\xF7\x64\x4C");
define('UTF_EBCDIC_BOM'         , "\xDD\x73\x66\x73");
define('SCSU_BOM'               , "\x0E\xFE\xFF");
define('BOCU1_BOM'              , "\xFB\xEE\x28");
define('GB18030_BOM'            , "\x84\x31\x95\x33");

function remove_bom($str)
{
  if (strncmp($str, UTF8_BOM, 3) === 0 || strncmp($str, UTF1_BOM, 3) === 0 || strncmp($str, SCSU_BOM, 3) === 0 || strncmp($str, BOCU1_BOM, 3) === 0)
    return substr($str, 3);
  else if (strncmp($str, UTF32_BIG_ENDIAN_BOM, 4) === 0 || strncmp($str, UTF32_LITTLE_ENDIAN_BOM, 4) === 0 || strncmp($str, UTF_EBCDIC_BOM, 4) === 0 || strncmp($str, GB18030_BOM, 4) === 0)
    return substr($str, 4);
  else if (strncmp($str, UTF16_BIG_ENDIAN_BOM, 2) === 0 || strncmp($str, UTF16_LITTLE_ENDIAN_BOM, 2) === 0)
    return substr($str, 2);
  return $str;
}

class MultipartFile
{
  private $boundary = FALSE;
  private $xml_only = FALSE;
  private $xml = FALSE;
  private $parts = array();
  private $part_types = array();
  
  public function __construct($file_name)
  {
    $data = remove_bom(file_get_contents($file_name));
    if (substr_compare($data, '<?xml ', 0, 6) === 0)
    {
      $this->xml_only = TRUE;
      $this->xml = $data;
      return;
    }
    
    //$index = strpos($data, "\n");
    //$line_style = ($index !== FALSE && $index > 0 && $data[$index-1] == "\r") ? "\r\n" : "\n";
    
    $index = 0;
    $headers = MultipartFile::read_header($data, $index);
    
    if (!array_key_exists('content-type', $headers))
      return; // no boundary
    $ct = $headers['content-type'];
    $bpos = strpos($ct, 'boundary=');
    if ($bpos === FALSE)
      return; // no boundary
    $boundary = substr($ct, $bpos + 9);
    $quote = $boundary[0];
    $semicolon = strpos($boundary, ';');
    if ($quote == '"' || $quote == "'")
      $boundary = substr($boundary, 1, strpos($boundary, $quote, 1) - 1);
    else if ($semicolon !== FALSE)
      $boundary = substr($boundary, 0, $semicolon);
    $this->boundary = '--'.$boundary;

    $parts = explode($this->boundary, substr($data, $index));
    $count = count($parts);
    for ($i = 1; $i < $count; ++$i) // skip the first one (before the first boundary)
    {
      if ($parts[$i][0] == '-' && $parts[$i][1] == '-')
        break; // terminal
      
      $part = ltrim($parts[$i]);
      
      $index = 0;
      $headers = MultipartFile::read_header($part, $index);
      $id = $headers['content-id'];
      $type = strtolower($headers['content-type']);
      $data = substr($part, $index);
      
      if ($type == 'application/xml')
        $this->xml = remove_bom($data);
      $this->parts[$id] = $data;
      $this->part_types[$id] = $type;
    }
  }
  
  private static function read_header($data, &$index)
  {
    $headers = array();
    while (($end = strpos($data, "\n", $index)) !== FALSE)
    {
      $line = trim(substr($data, $index, $end - $index));
      $index = $end + 1;
      if ($line == '')
        return $headers; // end of header
      $header = explode(':', $line, 2);
      $headers[strtolower($header[0])] = trim($header[1]);
    }
    // should take into account the possibly trailing line
    return $headers; // end of file
  }
  
  public function is_ready()           { return $this->boundary !== FALSE || $this->xml !== FALSE; }
  public function is_xml_only()        { return $this->xml_only; }
  
  public function get_xml()            { return $this->xml; }
  public function get_part_names()     { return array_keys($this->parts); }
  public function get_part_types()     { return $this->part_types; }
  public function has_part($name)      { return array_key_exists($name, $this->parts); }
  public function get_part($name)      { return $this->parts[$name]; }
  public function get_part_type($name) { return $this->part_types[$name]; }
}

?>