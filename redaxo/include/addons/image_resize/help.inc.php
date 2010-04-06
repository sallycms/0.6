<?php
/**
 * Image-Resize Addon
 *
 * @author office[at]vscope[dot]at Wolfgang Hutteger
 * @author <a href="http://www.vscope.at">www.vscope.at</a>
 *
 * @author markus[dot]staab[at]redaxo[dot]de Markus Staab
 * @author zozi@webvariants.de
 * 
 *
 * @package redaxo4
 * @version $Id: 
 */
?>
<h3>Features:</h3>

<p>Makes resize of images on the fly, with extra cache of resized images so performance loss is extremly small.</p>

<h3>Usage:</h3>
<p>call an image that way <b>imageresize/100w__imagefile</b>
 to resize the imagefile to a width of 100px</p>

<p><b>Notice:</b> if mod-rewrite doesn't work on this server, you can use the old syntax instead:</p>
<p>index.php?rex_resize=100w__imagefile</p>

<h3>Methods:</h3>
<p>
w = width       (max width)<br />
h = height      (max height)<br />
c = crop        (resize image to hight and cut it to max width)<br />
l = crop 		(offset from left) <br />
r = crop		(offset from right) <br />
x = crop        (resize and cut image to fill out certain length and height rectangle)<br />
a = automatic   (longest side will be used)
</p>

<h3>Default-Filters:</h3>
<p>
blur<br />
brand<br />
sepia<br />
sharpen
</p>

<h3>Examples:</h3>
<p>
resize image to a length of 100px and calculate heigt to match ratio<br />
<b>imageresize/100w__imagefile</b><br />
or <b>index.php?rex_resize=100w__imagefile</b>

<br /><br />
resize image to a height of 150px and calculate width to match ratio<br />
<b>imageresize/150h__imagefile</b><br />
or <b>index.php?rex_resize=150h__imagefile</b>

<br /><br />
resize image on the longest side to 200px and calculate the other side to match ratio<br />
<b>imageresize/200a__imagefile</b>
or <b>index.php?rex_resize=200a__imagefile</b>

<br /><br />
resize image to a width of 100px and a heigt of 200px<br />
<b>imageresize/100w__200h__imagefile</b>
or <b>index.php?rex_resize=100w__200h__imagefile</b>

<br /><br />
resize inner image part to a width of 100px and a heigt of 200px<br />
<b>imageresize/100c__200h__imagefile</b>
or <b>index.php?rex_resize=100c__200h__imagefile</b>

<br /><br />
resize inner image part to a width of 100px and a heigt of 200px with an offset of 50px<br />
<b>imageresize/100c__200h__50o__imagefile</b>
or <b>index.php?rex_resize=100c__200h__50o__imagefile</b>

<br /><br />
resize inner image part to a width of 100px and a heigt of 200px with an offset of -150px<br />
<b>imageresize/100c__200h__-150o__imagefile</b>
or <b>index.php?rex_resize=100c__200h__-150o__imagefile</b>

<br /><br />
resize inner image part to a width of 100px and a heigt of 200px with an offset of -150px<br />
<b>imageresize/100c__200h__-150o__imagefile</b>
or <b>index.php?rex_resize=100c__200h__-150o__imagefile</b>

<br /><br />
resize inner image part to a width of 100px and a heigt of 200px with an offset of 150px from the right edge<br />
<b>imageresize/100c__200h__-150o__imagefile</b>
or <b>index.php?rex_resize=100c__200h__150r__imagefile</b>

<br /><br />
resize inner image part to a width of 100px and a heigt of 200px with an offset of 50px from the left edge<br />
<b>imageresize/100c__200h__-150o__imagefile</b>
or <b>index.php?rex_resize=100c__200h__50l__imagefile</b>

<br /><br />
resize and crop image to a width of 100px and a heigt of 200px<br />
<b>imageresize/100x__200h__imagefile</b>
or <b>index.php?rex_resize=100x__200h__imagefile</b>

<br /><br />
add filter/s: here blur and sepia<br />
<b>index.php?rex_resize=200a__imagefile&amp;rex_filter[]=blur&amp;rex_filter[]=sepia</b>

</p>