<?php
/*

Plugin Name: Picture grabber, resizer and cropper
Plugin URI: http://fabrizio.zellini.org/picgrab-a-wordpress-plugin-to-grab-resize-crop-and-cache-images
Description: Plugin 
Author: Fabrizio Zellini
Version: 0.32
Author URI: http://fabrizio.zellini.org

 Copyright 2009-2011 Fabrizio Zellini
   PicGrab is free software: you can redistribute it and/or modify
   it under the terms of the GNU General Public License as published by
   the Free Software Foundation, either version 3 of the License, or
   (at your option) any later version.

   Foobar is distributed in the hope that it will be useful,
   but WITHOUT ANY WARRANTY; without even the implied warranty of
   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
   GNU General Public License for more details.

   You should have received a copy of the GNU General Public License
   along with Foobar.  If not, see <http://www.gnu.org/licenses/>.

*/

require_once 'std.encryption.class.inc';

if (!function_exists ('imageGrabberFilter')){
function imageGrabberFilter ($content){

    $resizerpath= realpath(dirname(__FILE__).'/resize.php');

    $crypt = new encryption_class();

    $blogurl = get_bloginfo('home');
    
  // 1. extract images
  // 2. subst with resizer calls
  // 3. return content
    
    $resizerbase = $blogurl."/wp-content/plugins/picgrab/resize.php";
    $imgtags=Array();
    $imgurls=Array();
    $resizeparms=Array();

    if (preg_match_all ('/<img.*?src=["\'](.*?)["\'].*?resize=["\'](.*?)["\'].*?>/i', $content, $matches)){
      if (!strpos ($matches[1][0],$resizerbase)){
        $imgtags = array_merge ($imgtags,$matches[0]);
        $imgurls = array_merge ($imgurls,$matches[1]);
        $resizeparms = array_merge ($resizeparms,$matches[2]);
      }
    }
    if (preg_match_all ('/<img.*?resize=["\'](.*?)["\'].*?src=["\'](.*?)["\'].*?>/i', $content, $matches)){
      if (!strpos ($matches[2][0],$resizerbase)){
        $imgtags = array_merge ($imgtags,$matches[0]);
        $imgurls = array_merge ($imgurls,$matches[2]);
        $resizeparms = array_merge ($resizeparms,$matches[1]);
      }
    }
    

    for ( $i=0;$i<count($imgtags);$i++) {
      // 1. remove "resize=..."
      $imgtag = $imgtags[$i];
      $imgtag2 = preg_replace ('/resize=["\'].*?["\']/','',$imgtag);
      //error_log ($imgtag2);
      // 2. replace "src=.." with resizer expr
      $imgurl=$imgurls[$i];
      $resizeparm = $resizeparms[$i];
      $imgurl2 = $resizerbase."?url=".urlencode($crypt->encrypt($imgurl))."&amp;size=".urlencode($crypt->encrypt($resizeparm));
      //error_log ($imgurl2);
      $imgtag2 = str_replace ($imgurl,$imgurl2,$imgtag2);
      //error_log ($imgtag2);
      $content = str_replace ($imgtag,$imgtag2,$content);     
    }
    return $content;
}
add_filter('the_content', 'imageGrabberFilter');
}

?>
