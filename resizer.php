<?
/**
 * php dynamic resizer with cache
 * Copyright 2009 Fabrizio Zellini
 *
 *   This file is part of PicGrab
 *
 *   PicGrab is free software: you can redistribute it and/or modify
 *   it under the terms of the GNU General Public License as published by
 *   the Free Software Foundation, either version 3 of the License, or
 *   (at your option) any later version.
 *
 *   Foobar is distributed in the hope that it will be useful,
 *   but WITHOUT ANY WARRANTY; without even the implied warranty of
 *   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *   GNU General Public License for more details.
 *
 *   You should have received a copy of the GNU General Public License
 *   along with Foobar.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

// limits for the resizer
define ("MAX_WIDTH",1280);
define ("MAX_HEIGHT",1024);

function genRandString(){
    $chset="abcdefghijklmnopqrstuvwxyz0123456789";
    $tok="";
    srand();
    for ($i=0;$i<16;$i++) {
      $tok.=$chset[rand(0,strlen($chset)-1)];
    }
    return $tok;
}

function grab ($url, &$contentType) {
    $curl = curl_init();
    // set URL and other appropriate options
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_FAILONERROR, true);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    
    $ua =  getParameterValue ($_SERVER,"HTTP_USER_AGENT");
    if ($ua) {
      curl_setopt($curl, CURLOPT_USERAGENT, $ua);
    }
    
    $referer = getParameterValue ($_SERVER,"HTTP_REFERER");
    if ($referer) {
      curl_setopt($curl, CURLOPT_REFERER, $referer);
    }
    
    // curl_setopt($curl, CURLOPT_HEADER, true);
    curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 15);
    curl_setopt($curl, CURLOPT_TIMEOUT, 15);
    curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($curl, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
    // get response
    $response = curl_exec($curl);
    $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    $contentType = curl_getinfo($curl, CURLINFO_CONTENT_TYPE );
    $redirectURL = curl_getinfo($curl,CURLINFO_EFFECTIVE_URL );
    curl_close ($curl);

//    error_log("httpCode is [$httpCode] for [$url]");
    if ($httpCode == 301){
      return grab($redirectURL, $contentType);
    } else {
      if ($httpCode!=200 && $httpCode!=201){
        error_log("httpCode is [$httpCode] for [$url]");
        return null;
      }
      // parse response
      return $response;
    } 
}



/**
 * Parse resize expression 
 * [(x0,y0,xs,ys)]<xd>x|x<yd>|<xd>x<yd>[c[<crop>]][;<cachetime>]
 * e.g. 100x, x200, 100x50, 100x200c50, 100x200c
 * @param string $expr
 * @param number $returnWidth
 * @param number $returnHeight
 * @param number $returnCrop
 * @param number $x0
 * @param number $y0
 * @param number $xs
 * @param number $ys
 */
function parseResizeExpr($expr, &$returnWidth, &$returnHeight,&$returnCrop,&$x0,&$y0,&$xs,&$ys){
  
  
  $x0=0;$y0=0;$xs=0;$ys=0;

  // cache time specified ?
  $cachetime = CACHETIME;
  if (preg_match("/.*?;(.*)/",$expr,$matches)){
    $cachetime = $matches[1];
    $idx = strpos($expr, ';');
    $expr = substr($expr,0,$idx);
  }

  // start specified ?
  if (preg_match ("/^\((\d+),(\d+),(\d+),(\d+)\)/",$expr,$matches)){
    // extract x0,y0,xs,ys
    $x0 = $matches[1];
    $y0 = $matches[2];
    $xs = $matches[3];
    $ys = $matches[4];
    
    $idx = strpos($expr, ')');
    $expr = substr($expr,$idx+1);
  }
  
  // crop specified ? (at end of resizeExpr )
  $idx = strpos($expr, 'c');
  if ($idx!==false) {
    $returnCrop = substr($expr,$idx+1);
    
    if ($returnCrop=="") $returnCrop=50;
    
    $expr = substr($expr,0,$idx);
  }
  
  $idx = strpos($expr, 'x');
  // starts with x
  if ($idx === 0) {
    $returnWidth = 0;
    $returnHeight = substr($expr, 1);
  }
  // ends with x
  elseif ($idx === strlen($expr) - 1) {
    $returnWidth = substr($expr, 0, strlen($expr)-1);
    $returnHeight = 0;      
  }
  // contains x
  elseif ($idx !== false) {
    $returnWidth = substr($expr, 0, $idx);
    $returnHeight = substr($expr, $idx +1);
  } else {
    trigger_error('Invalid image dimensions', E_USER_ERROR);
  }

}

function resizeAndOuput($resizeExpr, $imageurl,$expire=CACHETIME) {

  //echo $fullname;die();

  // remove cachetime from cache directory

  $cachetime = CACHETIME;

  if (preg_match("/.*?;(.*)/",$resizeExpr,$matches)){
  
    $cachetimes = $matches[1];
    if (preg_match ("/(.*?)([mMhdwy])/",$cachetimes,$mmm)) {
      $units = strtolower($mmm[2]);
      $cachetime = $mmm[1];
      switch ($units) {
        case 'm':
          $cachetime*=60;break;
        case 'h':
          $cachetime*=3600;break;
        case 'd':
          $cachetime*=86400;break;
        case 'w':
          $cachetime*=604800;break;
        case 'M':
          $cachetime*=2592000;break;
        case 'y':
          $cachetime*=31536000;break;
      }
      $cachetime = (int)$cachetime;
    } else {
      if ($cachetimes=='inf') $cachetimes="-1";
      $cachetime = (int)$cachetimes;
    }
    //error_log ("cachetime: $cachetime");
    
    $idx = strpos($resizeExpr, ';');
    $resizeExpr = substr($resizeExpr,0,$idx);
  }
  //error_log ("resizeExpr $resizeExpr");


  $cacheKey = md5($imageurl . '|' . $resizeExpr );
  $cacheDir = realpath(dirname(__FILE__)) . '/picscache/' . $resizeExpr . '/';

  $cacheBaseDir = realpath(dirname(__FILE__)) . '/picscache/';
  $cacheDir = $cacheBaseDir . $resizeExpr . '/';

  // create cache dir if no exists
  if (!file_exists($cacheBaseDir)){
    @mkdir($cacheBaseDir, 0777);
    chmod ($cacheBaseDir,0777);
  }
  
  if (!file_exists($cacheDir)){
    @mkdir($cacheDir, 0777);
    chmod ($cacheDir,0777);
  }
  
  $cacheFile = $cacheDir . $cacheKey;
 
  
  if ( file_exists($cacheFile) && (((time()-filemtime($cacheFile))<$cachetime) || ($cachetime==-1)) ) {
    // Output the cached one
    $expire = $expire - (time()-filemtime($cacheFile));
    //error_log ("Using cached one $cacheFile");
    outputPic($cacheFile,$expire);
    exit();
  }
  // grab the image
  //error_log ("Fetching $imageurl");
  $contents = grab ($imageurl,$contentType);
  if (!$contents) {
    // empty content: return 404
    header('HTTP/1.0 404 Page not found');
    exit();
  }
  
  $tmpfilename =  $cacheDir.genRandString();
  $tmpfilenamehandle = fopen ($tmpfilename,'w+');
  fwrite ($tmpfilenamehandle,$contents);
  fclose ($tmpfilenamehandle);
  //
  chmod ($tmpfilename,0777);
  
  $fullname = $tmpfilename ;

  switch ($contentType) {
    case 'image/gif':
      $image = imagecreatefromgif($fullname);
      break;
    case 'image/jpeg':
      $image = imagecreatefromjpeg($fullname);
      break;
    case 'image/png':
      $image = imagecreatefrompng($fullname);
      
      break;
    case 'image/bmp':
    case 'image/x-bmp':
    // untested yet
      $image = imagecreatefromwbmp($fullname);
      break;
    default:
      // not an image: return 404
      header('HTTP/1.0 404 Page not found');
      exit();
  }
    // Get image dimensions
  $width = imagesx ($image);
  $height = imagesy ($image);

  $crop=-1;

  // Get new dimensions & crop
  parseResizeExpr($resizeExpr, $new_width, $new_height,$crop,$x0,$y0,$xs,$ys);  
  
  if ($xs && $ys) {
    if (($xs+$x0)<=$width && ($ys+$y0) <=$height) {
      $width = $xs;
      $height = $ys;
      // crop image
      $image_tmp = imagecreatetruecolor($width, $height);
      imagecopy($image_tmp, $image, 0, 0, $x0, $y0, $width, $height);
      $image = $image_tmp;
    }
  }

  // limits check
  if ($new_height>MAX_HEIGHT) $new_height=MAX_HEIGHT;
  if ($new_width>MAX_WIDTH) $new_width = MAX_WIDTH;
  // non ingrandiamo l'immagine
  if ($new_height>$height) $new_height = $height;
  if ($new_width>$width) $new_width = $width;
  $ratio = $width/$height;
  // compute the unspecified dimension
  if ($new_width < 1) {
    $new_width = $new_height * $ratio;
  } elseif ($new_height < 1) {
    $new_height = $new_width/$ratio;
  }
  $new_ratio = $new_width/$new_height;
  $widthRatio = $new_width/$width;
  $heightRatio = $new_height/$height;
  if ($crop==-1) {
    // keep original ratio
    if ($new_ratio != $ratio) {
      if ($widthRatio > $heightRatio) {
        $new_width = $new_height*$ratio;
      } else {
        $new_height = $new_width/$ratio; 
      }
    }
    $src_x=0;
    $src_y=0;
  } else {
    // cropping needs 2 dimensions
    $rratio = $ratio/$new_ratio;
    if ($rratio>1) {
    // horizontal crop
      $centerx   = ($width/2);
      $destwidth = $new_width/$heightRatio;
      $rangex = $centerx-$destwidth/2;
      // crop shift
      // normalize crop between -1 and +1
      // 0-> - 1 SX
      // 50->  0 Center
      // 100-> 1 DX
      $crop=($crop-50)/50;
      $src_x=$centerx-($destwidth/2)+($rangex*$crop);
      $src_y = 0;
      $width = $width/$rratio;
    } else {
    // vertical crop
      $centery   = ($height/2);
      $destheight = $new_height/$widthRatio;
      $rangey = $centery-$destheight/2;
      // crop shift
      $crop=($crop-50)/50;
      $src_y=$centery-($destheight/2)+($rangey*$crop);
      $src_x = 0;
      $height = $height*$rratio;
    }
  }
  // Resample
  $image_p = imagecreatetruecolor($new_width, $new_height);

imagealphablending($image_p, false);
imagesavealpha($image_p, true);

  imagecopyresampled($image_p, $image, 0, 0, $src_x, $src_y, $new_width, $new_height, $width, $height);
  // Cache thumb
  imagejpeg($image_p, $cacheFile, 85);
  chmod ($cacheFile,0777);
  // Output
  outputPic($cacheFile,$expire);
  unlink ($tmpfilename);
}

function outputPic($file,$expire) {
  
  $if_modified_since = isset($_SERVER['HTTP_IF_MODIFIED_SINCE']) ?
      stripslashes($_SERVER['HTTP_IF_MODIFIED_SINCE']) :
      false;
  if ($if_modified_since) {
    $if_modified_since_time = strtotime($if_modified_since);
    // error_log('got if_modified_since ' . $if_modified_since_time . ' ' . filemtime($file));
    if (filemtime($file) <= $if_modified_since_time) {
      // error_log('exiting...');
      header('HTTP/1.0 304 Not Modified');
      exit();
    }
  }

  header('Last-Modified: ' . gmdate('D, d M Y H:i:s', filemtime($file)) . ' GMT');
  $expirationTime = time() + $expire;
  header('Expires: ' . gmdate('D, d M Y H:i:s', $expirationTime) . ' GMT');
  header('Cache-Control: max-age='.$expire);
  header('Content-Type: image/jpeg');
  header('Content-Length: '. filesize($file));
  readfile($file);
}

function getParameterValue ($params,$name,$default=null,$default1=null){
  if ($params && array_key_exists ($name,$params)){
    if ($default1==null) {
      $value = $params[$name];
    } else {
      $value = $default1;
    }
  } else $value = $default;
  return $value;
}

?>
