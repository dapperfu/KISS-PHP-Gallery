<?php
# Image Gallery
function gallery() {
	global $baseURL, $dir, $enable_caption_editing;
	// Determine what type of data there is to show (if any)
	$folder_scan_results = folder_scan();
	$images = $folder_scan_results['images'];
	$movies = $folder_scan_results['movies'];
	$directories = $folder_scan_results['directories'];
	# Load the Captions
	$captions = get_captions();
	# If the title is set on the captions page, else set it to the current image.
	# Print the header
	$title=empty($captions['title'])?$dir:$captions['title'];
	if (function_exists("printheader")) {
		printheader($title);
	} else {
		html_header($title);
	}
	/*
	echo "<h1><a href=\"/uploadForm.php\" onClick=\"window.open ('/uploadForm.php',
		'UploadForm','menubar=0,resizable=0,width=725,height=525');return false;\">Upload Pictures</a></h1>";
	*/
	if ($enable_caption_editing && count($images)) {
		echo "<a href=\"" . rrawurlencode($baseURL . "captions/" . $dir) . "\">Edit Captions</a>\n";
	}
	printPath();
	print_gallery_images($images);
	print_gallery_movies($movies);
	print_gallery_directories($directories);
	if (function_exists("printfooter")) {
		printfooter($title);
	} else {
		html_footer($title);
	}
}
# Preview Image
function preview($currImage) {
	global $baseURL, $scriptPath, $dir, $gallery_images_in_preview, $galleryWidth, $enable_caption_editing, $loadingGif, $enableHTMLlinks, $enableBBCodelinks;
	# Scan the folder for pictures & thumbnails
	$folder_scan_results = folder_scan();
	# Find Images with Thumbnails.
	$images_with_thumbs = images_with_thumbnails($folder_scan_results['images']);
	$noThumbs = count($folder_scan_results['images']) - count($images_with_thumbs);
	# Find where the current image is in the list of images.
	$imageIndex = array_search($currImage, $images_with_thumbs);
	if (empty($imageIndex)) $imageIndex = 0;
	# If somehow the user tries to preview an image  that doesn't exist, just go back to gallery view.
	if ($imageIndex === NULL) {
		header("Location:" . rrawurlencode($baseURL . $dir));
	}
	# Load the Captions
	$captions = get_captions();
	foreach($images_with_thumbs as $idx => $image) {
		$captions[$idx] = str_replace(array("\r", "\n"), array("", "<br>"), htmlentities($captions[$image]));
	}
	/*
	In folder with 300 images in it, each of these functions was run 100 times. (30,000 times times per function). This is how long those took:
	getimagesize: 8.4805779743195
	captions: 0.11382412910461
	imgName: 0.13228607177734
	getFileSize: 1.2750179767609
	getFileDate: 13.157135009766
	fullLink : 0.33402609825134
	preview: 0.41940498352051
	gallery: 0.45263981819153
	So everything gets cached to disk if it can be.
	*/
	# Attempt to load the cache file.
	if ($cache = @unserialize(file_get_contents($scriptPath . "cache/" . $dir . "cache"))) {
		#Extract all the variables
		//$num_cached_images = $cache["images_with_thumbs"];
		$fileSize = $cache["fileSize"];
		$exifDate = $cache["exifDate"];
		$imgName = $cache["imgName"];
		$fullLinkURL = $cache["fullLinkURL"];
		$preview = $cache["preview"];
		$gallery = $cache["gallery"];
		$bbCode = $cache["bbcode"];
		$html   = $cache["html"];
	}
	# Determine the uncached images.
	$uncached_images = count($images_with_thumbs)- $num_cached_images;
	# Rebuild cache if there were any changes.
	if ($uncached_images) {
	//if (1) {
		# Clean out any old data and create arrays
		$bbCode=$html=$fileSize=$exifDate=$imgName=$fullLinkURL=$preview=$gallery=array();
		foreach($images_with_thumbs as $idx => $image) {
			$hardlinks = hardlinks($image);
			$bbCode[$idx]=$hardlinks["bbCode"];
			$html[$idx]= $hardlinks["html"];
			$fileSize[$idx] = getFileSize($scriptPath . $dir . $image);
			$exifDate[$idx] = getFileDate($scriptPath . $dir . $image);
			$imgName[$idx] = addslashes(pathinfo($image, PATHINFO_FILENAME));
			$fullLinkURL[$idx] = rrawurlencode($baseURL . $dir . $image);
			$preview[$idx] = rrawurlencode($baseURL . "cache/" . $dir . "preview/" . $image);
			$gallery[$idx] = rrawurlencode($baseURL . "cache/" . $dir . "gallery/" . $image);
		}
		# If there was a cache change.
		$cache["images_with_thumbs"] = count($images_with_thumbs);
		$cache["fileSize"] = $fileSize;
		$cache["exifDate"] = $exifDate;
		$cache["imgName"] = $imgName;
		$cache["fullLinkURL"] = $fullLinkURL;
		$cache["preview"] = $preview;
		$cache["gallery"] = $gallery;
		$cache["bbcode"] = $bbCode;
		$cache["html"] = $html;
		file_put_contents($scriptPath . "cache/" . $dir . "cache",serialize($cache));
	}
	# Count the number of images, used for wrap around math.
	$imageCount = count($images_with_thumbs);
	for ($i = - $gallery_images_in_preview;$i <= $gallery_images_in_preview;$i++) {
		# Build the array +- image_previews.
		$slide_previews[$i] = $gallery[($imageIndex + $i + $imageCount) % $imageCount];
	}
	$title=$imgName[$imageIndex];
	if (function_exists("printheader")) {
		printheader($title);
	} else {
		html_header($title);
	}
	if ($enable_caption_editing) {
		echo "<a href=\"" . rrawurlencode($baseURL . "captions/" . $dir) . "\">Edit Captions</a>\n";
	}
	# Print the directory path.
	printPath();
?>
<!-- Begin Previews -->
<div id="preview">
<span id="navDirections"></span>
<div id="leftpreview">
<?
	for ($i = - $gallery_images_in_preview;$i <= - 1;$i++) {
		echo "\t<img src=\"" . $slide_previews[$i] . "\" id=\"gallery$i\" width=\"$galleryWidth\" alt=\"gallery$i\" onclick=\"changePicture($i);return false;\">\n";
	}
?>
</div>
<div id="rightpreview">
<?
	for ($i = 1;$i <= $gallery_images_in_preview;$i++) {
		echo "\t<img src=\"" . $slide_previews[$i] . "\" id=\"gallery$i\" width=\"$galleryWidth\" alt=\"gallery$i\" onclick=\"changePicture($i);return false;\">\n";
	}
?>
</div>
<div id="centerpreview">
	<center>
		<a href="<?=$preview[($imageIndex + 1 + $imageCount) % $imageCount] ?>"  id="nextLink" onclick="changePicture(1);return false;">
			<img src="<?=$preview[$imageIndex] ?>" alt="Current Preview" id="mainPreview">
		</a>
		<br>
		<a href="<?=$fullLinkURL[$imageIndex] ?>" target="_new" id="fullLink">Link to Full Picture (<?=$fileSize[$imageIndex] ?>)</a><br>
		<div id="caption" style="display:<?=empty($captions[$imageIndex]) ? "none" : "block" ?>"><?=str_replace("\n", "<br>", htmlentities($captions[$imageIndex])) ?></div>
		<div id="timeStamp" style="display:<?=empty($exifDate[$imageIndex]) ? "none" : "block" ?>"><?=$exifDate[$imageIndex] ?>
	</div>
		<?
		if ($enableBBCodelinks) {
			echo "<div><input type=\"text\" size=\"100\" value=\"".$bbCode[$imageIndex]."\" id=\"bbCodeLink\"></div>\n";
		}
		if ($enableHTMLlinks) {
			echo "<div><input type=\"text\" size=\"100\" value=\"".$html[$imageIndex]."\" id=\"htmlLink\"></div>\n";
		}
		?>
	</center>
	<img src="<?=$slide_previews[0] ?>" id="gallery0" class="cache" alt='cache'>
	<?=display_generating_thumbnails($noThumbs); ?>
	<? /* The current index of the image */ ?>
	<div style="display:none" id="imageIndex"><?=$imageIndex ?></div>
	<div style="clear:both;"></div>
</div>
</div>
<div id="end_javascript">
<script type="text/javascript">
<!--
var previewSrc = Array();
var gallerySrc = Array();
var fullLinkURL = Array();
var imgSize = Array();
var timeStamp = Array();
var imgName = Array();
var caption = Array();
var bbCode= Array();
var html=Array();
<?
	for ($i = 0;$i < $imageCount;$i++) {
		echo "previewSrc[$i]=\"" . $preview[$i] . "\";\n";
		echo "gallerySrc[$i]=\"" . $gallery[$i] . "\";\n";
		echo "fullLinkURL[$i]=\"" . $fullLinkURL[$i] . "\";\n";
		echo "imgName[$i]=\"" . $imgName[$i] . "\";\n";
		echo "imgSize[$i]=\"Link To Full Picture (" . $fileSize[$i] . ")\";\n";
		echo "timeStamp[$i]=\"" . $exifDate[$i] . "\"\n";
		echo "bbCode[$i]=\"" . $bbCode[$i] . "\"\n";
		echo "html[$i]=\"" . str_replace("/","\/",$html[$i]) . "\"\n";
		echo "caption[$i]=\"" . $captions[$i] . "\";\n\n";
	}
?>
function $(e) {return document.getElementById(e);}
function keyBoardNav(e) {
	var KeyID = (window.event) ? event.keyCode : e.keyCode;
	switch(KeyID) {
		// Left Arrow
		case 37:
			changePicture(-1);
			break;
		// Space Bar
		case 32:
		// Right Arrow
		case 39:
			changePicture(1);
			break;
		default:
			return true;
	}	
}
function changePicture(imgChg) {
	var imgIdx=parseInt($('imageIndex').innerHTML)+imgChg;
	// Calculate the wrap around math. If the user scrolls past first or last image
	if (imgIdx<0) {
		imgIdx=previewSrc.length-1;
	} else if (imgIdx>=previewSrc.length) {
		imgIdx=0;
	}
	$('mainPreview').src=previewSrc[imgIdx];
	$('mainPreview').title=caption[imgIdx];
	try {
	<?
		if ($enableBBCodelinks) {
			echo "$('bbCodeLink').value=bbCode[imgIdx];\n";
		}
		if ($enableHTMLlinks) {
			echo "$('htmlLink').value=html[imgIdx];\n";
		}
	?>
	} catch (e) {
		alert(e);
	}
	var l=previewSrc.length;
	for (i=-<?=$gallery_images_in_preview?>;i<=<?=$gallery_images_in_preview?>;i++) {
		try {
			$('gallery'+i).src=gallerySrc[(l+imgIdx+i)%l];
		} catch(err) {
		}
	}
	// Change the document title and hash.
	document.title=(imgName[imgIdx]);
	document.location.hash=(imgName[imgIdx]);
	// Set the new current image index.
	$('imageIndex').innerHTML=imgIdx;
	// Change the full image url
	$('fullLink').href=fullLinkURL[imgIdx];
	// Change the inner html
	$('fullLink').innerHTML=imgSize[imgIdx];
	if (timeStamp[imgIdx]=="") {
		$('timeStamp').style.display="none";
	} else {
		$('timeStamp').style.display="block";
		$('timeStamp').innerHTML=timeStamp[imgIdx];
	}
	if (caption[imgIdx]=="") {
		$('caption').style.display="none";
	} else {
		$('caption').style.display="block";
		$('caption').innerHTML=caption[imgIdx];
	}
	$('navDirections').innerHTML="";
}
<?/*
If the url has a hash, attempt to locate the picture in the picture array. If it does, swap the image to that one. 
This helps if people scroll left or right from a picture then send a link of the image to someone.
*/?>
if (window.location.hash.substring(1)!="") {
	var newImg=window.location.hash.substring(1);
	for (i in imgName) {
		if (imgName[i]==newImg) {
			changePicture(i-parseInt($('imageIndex').innerHTML));
			break;
		}
	}
}
<?/*
Display a 'loading previews' image. 

Once the page is loaded (all images too) then display the 'use arrow keys' message.
*/?>
$('navDirections').innerHTML="<img alt='Loading Previews' src='<?=$loadingGif?>' title='Loading Previews'><br>Loading Preview Images.<br>Use arrow keys to navigate pictures.";
window.onload=function(){document.onkeydown = keyBoardNav;$('navDirections').innerHTML=["Use arrow keys to navigate pictures"];};
// -->
</script>
</div>
<div id="cache">
<?
	/* Javascript or CSS Image Caching... */
	if (false) {
		echo "<script language=\"JavaScript\">";
		for ($i = $gallery_images_in_preview;$i < count($images_with_thumbs) - $gallery_images_in_preview;$i++) {
			$q = ($i + $imageIndex) % count($images_with_thumbs);
			$x++;
			echo "pic$x= new Image();";
			echo "pic$x.src=\"" . $preview[$q] . "\";\n";
			$x++;
			echo "pic$x= new Image();";
			echo "pic$x.src=\"" . $gallery[$q] . "\";\n";
		}
		echo "</script>";
	} else {
		for ($i = $gallery_images_in_preview;$i < count($images_with_thumbs) - $gallery_images_in_preview;$i++) {
			$q = ($i + $imageIndex) % count($images_with_thumbs);
			echo "<img src=\"" . $preview[$q] . "\" class='cache' alt='cache'>\n";
			echo "<img src=\"" . $gallery[$q] . "\" class='cache' alt='cache'>\n";
		}
	}
?>
</div>
<?
	if (function_exists("printfooter")) {
		printfooter($title);
	} else {
		html_footer($title);
	}
}

function display_generating_thumbnails($noThumbs) {
	global $baseURL, $scriptPath, $dir, $generatingGif;
	if ($noThumbs) {
?>
<div id="generatingThumb">
<!-- Rather than use AJAX to 'call' the make thumbs script, just reference it using an image-->
<img src="<?=$baseURL?>thumbs/<?=$dir?>" alt="" width="0" height="0" style="display:none">
<img alt="Generating Thumbnails" src="<?=$generatingGif ?>" title="Generating Thumbnails"><br>
Generating thumbnails for <span id="generatingThumbCount"><?=$noThumbs ?></span> images.
<script type="text/javascript">
setTimeout('document.location.reload()',10000);
</script>
</div>
<?
	}
}


/* HTML Header & Footer Functions */
# Print HTML Header. If you use your own header and footer, the CSS needs to be added to your CSS
# file (unless you want to use your own CSS);
function html_header($title = "") {
	global $dir, $scriptPath, $baseURL, $galleryWidth, $previewWidth;
	$GLOBALS['time_start'] = microtime(true);
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="utf-8" />
	<title><?=$title?></title>
	<link rel="alternate" href="<?=rrawurlencode($baseURL . "rss/" . $dir) ?>" type="application/rss+xml" title="<?=$title?>" id="gallery">
	<script type="text/javascript" src="http://www.exstatic.org/mediaplayer/swfobject.js"></script>
	<style type="text/css">
		html {
			width: 100%;
			overflow-x: hidden;
		}
		body {
			margin: 10px 5px 0px;
			padding: 0;
			background: #FFFFFF;
			text-align: center;
		}
		img {
			border: none;
			display: inline;
			margin-left: auto;
			margin-right: auto;
		}
		#directories {
			width: 50%;
			margin: 0;
			text-align: left;
			padding: 0;
		}
		#movies {
			width: 50%;
			margin: 0 0;
			text-align: left;
			padding: 0;
		}
		#path {
			margin: 0 auto;
			text-align: center;
			padding: 0;
		}
		#image_gallery {
			margin: 10px auto;
			text-align: center;
			padding: 0;
		}
		#preview {
			position: relative;
			margin: 0 auto;
			text-align: center;
			padding: 0;
		}
		#leftpreview {
			float: left;
			position: relative;
			vertical-align: middle;
			width: <?=$galleryWidth ?>px;
			z-index:1;
		}
		#centerpreview {
			margin-left: <?=$galleryWidth + 5 ?>px;
			margin-right: <?=$galleryWidth + 5 ?>px;
			position: relative;
			z-index:10;
		}
		#rightpreview {
			float: right;
			position: relative;
			width: <?=$galleryWidth ?>px;
			z-index:2;
		}
		#end_javascript {
			display: none;
		}
		#generatingThumb {
			margin: 10px 0;
		}
		.cache {
			display: none;
		}
	</style>
	</head>
<body id="body">
<?
}
# html footer
function html_footer() {
	$time_end = microtime(true);
	$time = $time_end - $GLOBALS['time_start'];
?>
<div style="clear:both"></div>
</body>
</html>
<?
}
/* End HTML Functions */