=== Plugin Name ===
Contributors: zlabs
Tags: pictures, resize, crop, cache
Requires at least: 6.1.1
Tested up to: 
Stable tag: 0.32

Picgrab is a plugin that download, resize and crop an image on the fly.
It also stores resized and cropped images in local cache.

== Description ==

Picgrab is a plugin that download, resize and crop an image on the fly.  
Simply add the “resize” attribute in the img tag you want to grab & resize on your post.  

E.g.
&lt;img src="http://farm4.static.flickr.com/3532/3243887563_700849f242.jpg" resize="402×200c;66y" /&gt;
Once activated, the plugin replaces the attribute “src” on img tags containing the "resize" attribute with something like:

http://yoursite/wp-content/plugins/picgrab/resize.php?url=&lt;coded characters&gt;&size=&lt;coded characters&gt;
and removes the "resize" attribute.

The resize.php script included with plugin does the work to grab, crop and resize the image.
It also manages the cache.
The resize attribute format is:

[(x0,y0,xs,ys)]&lt;xd&gt;x|x&lt;yd&gt;|&lt;xd&gt;x&lt;yd&gt;[c[&lt;crop&gt;]][;&lt;cachetime&gt;]

e.g. 100x, x200, 100×50, 100×200c50, 100×200c

Refer to [This post](http://fabrizio.zellini.org/resize-e-crop-immagini-in-php "Crop / Resize script") for more informations on resizer syntax.

PicGrab require Php version &gt;=8, gd and curl libraries.

== Installation ==

1. Unzip into your /wp-content/plugins/ directory. If you're uploading it make sure to upload the top-level folder. Don't just upload all the php files and put them in /wp-content/plugins/
1. Activate the plugin through the 'Plugins' menu in WordPress
1. make sure picgrab/picscache folder is writable by all ( 777 permission )
1. That's it!

