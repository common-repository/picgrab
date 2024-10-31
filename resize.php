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

// how much time the images must be kept in local cache
define ('CACHETIME', 3600);

// parameters are crypted ?
define ('CRYPT','1');

if (defined('CRYPT') && CRYPT){
  require_once 'std.encryption.class.inc';
}
require_once ('resizer.php');

$dimensions = getParameterValue ($_GET,'size');
$imgpath = getParameterValue ($_GET,'url');


if (defined ('CRYPT') && CRYPT) {
  $resizepath= realpath(dirname(__FILE__).'/resize.php');

  $crypt = new encryption_class();
  $dimensions = $crypt->decrypt($dimensions);
  $imgpath = $crypt->decrypt($imgpath);
}
if (preg_match ("/resize\.php/i",$imgpath)) {
    header('HTTP/1.0 404 Page not found');
    echo "PRRR";
    exit();
}

if ($dimensions && $imgpath) 
  resizeAndOuput($dimensions, $imgpath);
//echo $dimensions."-".$imgpath;

?>
