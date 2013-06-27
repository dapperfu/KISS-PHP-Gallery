<?
/* Config */
# Define the binary/command used to create thumbnails. Image Magick and Graphics Magick both work ('convert' or 'gm', respectively.)
$thumbBinary = "/usr/bin/gm";
# Optional. Leave blank if undefined.
$jheadBinary = "/usr/bin/jhead"; # Location of the jhead binary, for embedding Captions.
$ffmpegBinary = "/usr/bin/ffmpeg"; # Location of the ffmpeg binary, for displaying video play length.
$enable_caption_editing = FALSE; # Allow caption editing
$enableHTMLlinks=TRUE; # Print a form with HTML code for easy copy and pasting into a website.
$enableBBCodelinks=TRUE; # Print a form with BBCode code for easy copy and pasting into a forum.
$gallery_images_in_preview = 3; # Number of gallery to show in preview mode.
$galleryHeight = 200; # Height of gallery images on the gallery page. Not used for thumbnail generation, just display.
$galleryWidth = 267; # Width of the gallery images on preview page.
$previewWidth = 700; # Width of the preview images on preview page.
$previewHeight = 700; #Height of the preview images on preview page.
$includeFile= ""; # Include file that may have  printheader/printfooter functions in it. Leave blank otherwise.
# http://www.ajaxload.info/ for a good starting point.
$loadingGif = "http://github.com/jedediahfrey/KISS-PHP-Gallery/raw/master/loading.gif"; # Image to show when loading previews.
$generatingGif = "http://github.com/jedediahfrey/KISS-PHP-Gallery/raw/master/generating.gif"; # Image to show when generating thumbnails
/* End Config */
/* Baseline Variables. Stuff used everywhere */
$scriptPath = fixDir(getcwd());
$baseURL = dirname("http://" . $_SERVER['HTTP_HOST'] . $_SERVER['SCRIPT_NAME']) . "/";
# Fails if there are 'query string' characters in the query string.
# list($dir,$file) = getArguments($_GET['dir']); # Get the directory & image file
list($dir, $file) = getArguments(end(explode("=", $_SERVER['QUERY_STRING'])));
# Verify that the thumb binary is executable.
if (!is_executable($thumbBinary)) exit("Thumb executable '$thumbBinary' is not executable");
# Create htaccess file. Required for all functions. This can be commented out after first run. Adds ~1E-5s to execution time.
makeHtaccess();
# If the script is called from the command line. Set variables.
if ($argc) {
	$_GET['command'] = "thumbs";
	$dir = $argv[1];
	$_GET['recursive'] = TRUE;
}
# BBClone Setup the bbclone information. Track visits via bbclone.
define("_BBCLONE_DIR", $scriptPath . "bbclone/");
define("COUNTER", _BBCLONE_DIR . "mark_page.php");
if (!empty($includeFile)) include($includeFile);
# Control Central
switch ($_GET['command']) {
	case "gallery": # Image Gallery
		define("_BBC_PAGE_NAME", "Gallery: " . $dir);
		@include_once (COUNTER);
		# Gallery View
		gallery();
		exit;
	case "slide": # Slide Show / Preview Mode
		define("_BBC_PAGE_NAME", "Preview: " . $dir . $file);
		@include_once (COUNTER);
		# Preview View
		preview($file);
		exit;
	case "rss": # Generate RSS Feed
		printRSS();
		exit;
	case "captions": # Add Image Captions
		# Determine if caption editing is on or off.
		if ($enable_caption_editing) {
			define("_BBC_PAGE_NAME", "Caption:" . $dir . $file);
			@include_once (COUNTER);
			makeCaptions($_POST);
		} else {
			header("Location:" . rrawurlencode($baseURL . $dir));
		}
		exit;
	case "swf_player": # JW's SWF Player. http://www.longtailvideo.com/players/jw-flv-player/
		define("_BBC_PAGE_NAME", "Movie:" . $dir . $file);
		@include_once (COUNTER);
		swf_player($file);
		exit;
	case "qt_player": # Quicktime Player
		define("_BBC_PAGE_NAME", "Movie:" . $dir . $file);
		@include_once (COUNTER);
		qt_player($file);
		exit;
	case "thumbs": # Create thumbnails
		define("_BBC_PAGE_NAME", "Thumbs: " . $dir);
		@include_once (COUNTER);
		# Lock file to prevent duplicate instances from being created.
		$lock_file = $scriptPath . "/.lock";
		# If a lock file exists and the process has been running for less than 5 minutes: Die.
		# Assume something went wrong if it has been running for more than 5
		if (is_file($lock_file) && (filemtime($lock_file) + 300 > time())) {
			exit("Already running...\n");
		}
		# Lock file
		file_put_contents($lock_file, NULL);
		thumbnailParse($dir, $_GET['recursive']);
		unlink($lock_file);
		exit;
}
# If some how the script gets here, go to the URL base.
header("Locaton:" . $baseURL);
return;
/* HTML Header & Footer Functions */
# Print HTML Header. If you use your own header and footer, the CSS needs to be added to your CSS
# file (unless you want to use your own CSS);
function html_header($title = "") {
	global $dir, $scriptPath, $baseURL, $galleryWidth, $previewWidth;
	$GLOBALS['time_start'] = microtime(true);
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
	<meta http-equiv="content-type" content="text/html; charset=utf-8">
	<title><?=$title
?></title>
	<link rel="alternate" href="<?=rrawurlencode($baseURL . "rss/" . $dir) ?>" type="application/rss+xml" title="" id="gallery">
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
/* Movie Functions */
function swf_player($movie) {
	global $baseURL, $scriptPath, $dir;
	html_header();
	$data = movie_info($movie);
	$movie_file = rrawurlencode($baseURL . $dir . $movie);
?>
<div id="player">
	<h1>Flash Player Required</h1>
	<p><a href="http://www.adobe.com/go/getflashplayer"><img src="http://www.adobe.com/images/shared/download_buttons/get_flash_player.gif" alt="Get Adobe Flash player" /></a></p>
</div>
<script type="text/javascript">
var so = new SWFObject('http://www.exstatic.org/mediaplayer/player.swf','mpl','<?=$data['width'] ?>','<?=$data['height'] ?>','8','#000000');
so.addParam('allowscriptaccess','always');
so.addParam('allowfullscreen','true');
so.addParam('flashvars','file=<?=$movie_file ?>&controlbar=bottom&autostart=true&bufferlength=15&duration=<?=$data['duration'] ?>');
so.write('player');
</script>
<?
	html_footer();
}
function qt_player($movie) {
	global $baseURL, $scriptPath, $dir;
	html_header();
	$data = movie_info($movie);
	$movie_file = rrawurlencode($baseDir . $dir . $movie);
?>
<style type="text/css">
body {
background-color: #000;
}
</style>
	<embed src="<?=$movie_file?>" href="<?=$movie_file?>" width="<?=$data['width'] ?>" height="<?=$data['height'] + 25 ?>" type="video/quicktime" controller="true" autoplay="true" plugin = "quicktimeplugin" cache="true" controller="true" target="myself">  
<?
	html_footer();
}
function print_movie($file, $player) {
	global $baseURL, $scriptPath, $dir;
	$data = movie_info($file);
	$playPath = rrawurlencode($baseURL . $player . "/" . $dir . $file);
	$windowHeight = $data['height'] + 25;
	$windowWidth = $data['width'] + 25;
	$movieName = explode(".", end(explode("/", $file)));
	$ext = end(explode(".", $file));
	if ($ext == "mp3") {
		$windowHeight = 275;
		$windowWidth = 275;
	}
	echo "<a target='_new' href=\"" . $baseURL . $dir . $file . "\" onClick=\"movie_player('$playPath','$windowWidth', '$windowHeight');return false;\">";
	if ($ext == "flv" || $ext == "f4v") {
		echo "$movieName[0] (Flash Video)";
	}
	if ($ext == "mp4") {
		echo "$movieName[0] (iPod Video)";
	}
	if ($ext == "mp3") {
		echo "$movieName[0] (MP3)";
	}
	if (!empty($data['duration_text'])) echo " (" . $data['duration_text'] . ")";
	echo "</a>";
}
/* End Movie Functions */
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
function hasExt($file, $findExt) {
	if (!is_array($findExt)) {
		$findExt = array($findExt);
	}
	$ext = end(explode(".", $file));
	return in_array(strtolower($ext), $findExt);
}
function print_gallery_directories($directories) {
	global $baseURL, $scriptPath, $dir;
	if (count($directories) > 0) {
		echo "<!-- Begin Directories -->\n";
		echo "<div id='directories'>\n";
		echo "<h3>Sub-Albums</h3>";
		echo "\t<ul>\n";
		foreach($directories as $directory) {
			$link = rrawurlencode($baseURL . $dir . $directory);
			echo "\t\t<li>\n\t\t\t<a href=\"" . $link . "/\">" . htmlentities($directory) . "</a>\n";
			echo "\t\t</li>\n";
		}
		echo "\t</ul>\n</div>\n";
		echo "<!-- End Directories -->\n\n";
	}
}
function print_gallery_images($images) {
	global $baseURL, $scriptPath, $dir, $galleryHeight;
	if (count($images) > 0) {
		echo "<!-- Begin Pictures -->\n";
		echo "<div id='image_gallery'>\n";
		$noThumbs = 0;
		$pictures = "";
		$images_with_thumbs = images_with_thumbnails($images);
		$noThumbs = count($images) - count($images_with_thumbs);
		display_generating_thumbnails($noThumbs);
		if (count($images_with_thumbs)) {
			foreach($images_with_thumbs as $idx => $image) {
				$pictures.= "\t<a href=\"" . rrawurlencode($baseURL . "slide/" . $dir . $image) . "\">";
				$pictures.= "<img src=\"" . rrawurlencode($baseURL . "cache/" . $dir . "gallery/" . $image) . "\" height=\"$galleryHeight\" alt=\"" . htmlentities($image) . "\"></a>\n";
			}
		}
		echo $pictures;
		echo "</div>\n";
		echo "<!-- End Pictures -->\n\n";
	}
}
function folder_scan() {
	global $baseURL, $scriptPath, $dir;
	# Check for invalid folders.
	if (!($files = @scandir($scriptPath . $dir))) {
		# Move up one level.
		header("Location:" . rrawurlencode($baseURL . substr($dir, 0, strrpos("/", $dir, -1))));
		exit;
	}
	# Create arrays
	$result['directories'] = Array();
	$result['images'] = Array();
	$result['sounds'] = Array();
	$result['movies'] = Array();
	$result['archives'] = Array();
	# If there were files found.
	if (count($files) > 0) {
		foreach($files as $file) {
			# Skip all Unix hidden images.
			if (substr($file, 0, 1) == ".") {
				continue;
			}
			# Skip cache and bbclone directories.
			if (is_dir($scriptPath . $dir . $file) && (!in_array(strtolower($file), array("cache", "bbclone")))) {
				$result['directories'][] = $file;
			}
			if (is_file($scriptPath . $dir . $file) && hasExt($file, array('jpg', 'jpeg', 'png', 'gif'))) {
				$result['images'][] = $file;
			}
			if (is_file($scriptPath . $dir . "/" . $file) && hasExt($file, array('mp3', 'wav'))) {
				$result['sounds'][] = $file;
			}
			if (is_file($scriptPath . $dir . "/" . $file) && hasExt($file, array('zip', 'tar', 'gz'))) {
				$result['archives'][] = $file;
			}
			if (is_file($scriptPath . $dir . $file) && hasExt($file, array("f4v", "flv", "mpg", "mov", "avi", "mp4"))) {
				$result['movies'][] = $file;
			}
		}
	}
	sort($result['directories']);
	sort($result['images']);
	sort($result['sounds']);
	sort($result['archives']);
	sort($result['movies']);
	return $result;
}
function images_with_thumbnails($images) {
	global $scriptPath, $dir;
	$withThumbs = Array();
	if (!is_array($images)) {
		return $withThumbs;
	}
	foreach($images as $image) {
		if (is_file($scriptPath . "cache/" . $dir . "gallery/" . $image) && is_file($scriptPath . "cache/" . $dir . "preview/" . $image)) {
			$withThumbs[] = $image;
		}
	}
	return $withThumbs;
}
#
function print_gallery_sounds($sounds) {
	if (count($sounds) > 0) {
		echo "<!-- Begin Sounds-->\n";
		echo "<div id='sounds'>\n";
		echo "\t<h3>Audio Files</h3>\n";
		echo "\t<ul>\n";
		foreach($sounds as $sound) {
			if (is_movie($sound, array("mp3"))) {
				echo "\t<li>\n";
				echo "\t\t";
				print_movie($sound, "swf_player");
				echo "</a></li>\n";
				echo "\t</li>\n";
			}
			echo "\t</ul>\n";
			echo "</div>\n";
			echo "<!-- End Sounds-->\n\n";
		}
	}
}
#
function print_gallery_movies($movies) {
	global $baseURL, $dir;
	if (count($movies) > 0) {
		echo "<!-- Begin Movies-->\n";
?>
<script type="text/javascript">
function movie_player(movie,width,height) {
  var movie=window.open(movie,'','scrollbars=no,menubar=no,height='+height+',width='+width+',resizable=no,toolbar=no,location=no,status=no');
  return false;
}
</script>
<?
		echo "<div id='movies'>\n";
		echo "\t<h3>Movies</h3>\n";
		echo "\t<ul>\n";
		foreach($movies as $movie) {
			if (hasExt($movie, array("mpg", "avi"))) {
				echo "\t\t<li><a target='_new' href=\"" . rrawurlencode($baseURL . $dir . $movie) . "\">";
				echo "$movie</a> (" . getFileSize($scriptBase . $dir . $movie) . ")";
				echo "</li>\n";
			}
			if (hasExt($movie, "mov")) {
				echo "\t<li>\n";
				echo "\t\t";
				print_movie($movie, "qt_player");
				echo "</li>\n";
			}
			if (hasExt($movie, array("flv", "f4v", "mp4", "mp3"))) {
				echo "\t<li>\n";
				echo "\t\t";
				print_movie($movie, "swf_player");
				echo "</li>\n";
			}
		}
		echo "\t</ul>\n";
		echo "</div>\n";
		echo "<!-- End Movies-->\n\n";
	}
}
#
#
function hardLinks($file) {
	global $baseURL, $dir;
	$gallery = rrawurlencode($baseURL."cache/".$dir."preview/".($file));
	$fullsize = rrawurlencode($baseURL . $dir . $file);
	$html = "<a href='" . addslashes($fullsize) . "'><img src='$gallery' border='0' style='border:0'></a>";
	$bbCode = "[url=" . $fullsize . "][img]" . $gallery . "[/img][/url]";
	return Array('html' => $html, 'bbCode' => $bbCode);
}
/* Caption Functions */
# Get the captions from the captions file
function get_captions($caption_array=FALSE) {
	global $dir, $scriptPath;
	#  Get the captions for the image files.  All captions files are stored in the cache directory
	if ($captions_tmp = unserialize(@file_get_contents("cache/" . $dir . "captions.txt"))) {
		# Put in to convert all old captions to new array based ones.
		foreach($captions_tmp as $image => $caption) {
			if (!is_array($caption)) {
				$captions[$image][] = $caption;
			} else {
				$captions[$image]=$caption;
			}
		}
		# If the raw captions array is needed, just return it.
		if ($caption_array) {
			return $captions;
		} else {
			# Else return the last caption edited.
			foreach($captions as $image => $caption) {
				$caption_out[$image] = array_pop($caption);
			}
			return $caption_out;
		}
	} else {
		return array();
	}
}

function makeCaptions($new_captions) {
	global $baseURL, $scriptPath, $dir, $jheadBinary;
	# If there are new captions
	if (!empty($new_captions)) {
		# Get the old captions
		$captions = get_captions(true);
		# Append new captions to those already there
		foreach($new_captions as $i => $c) {
			# Base 64 was the best way (I thought of) of dealing with oddly named images in the input form.
			$captions[base64_decode($i)][] = trim($c);
			# If the jhead binary is executable.
			if (is_executable($jheadBinary)) setExifCaption(base64_decode($i), trim($c));
		}
		# Write the captions to disk.
		@file_put_contents($scriptPath . "cache/" . $dir . "captions.txt", serialize($captions));
		# If the function was called from AJAX... (Only one update at a time).
		if (count($new_captions) == 1) {
			exit;
		}
	}
	$captions = get_captions();
	$folder_scan_results = folder_scan();
	$images_with_thumbs = images_with_thumbnails($folder_scan_results['images']);
	foreach($images_with_thumbs as $idx => $image) {
		if (!array_key_exists($image, $captions)) {
			$captions[$image] = getExifCaption($image);
		}
	}
	/*
	Ajax Function. Compressed version of "The Ultimate Ajax Object"
	http://www.hunlock.com/blogs/The_Ultimate_Ajax_Object
	*/
	$title="Captions: ".$dir;
	if (function_exists("printheader")) {
		printheader($title);
	} else {
		html_header($title);
	}
?>
	<script type="text/javascript">
		function ajaxObject(b,a){var c=this;this.updating=false;this.abort=function(){if(c.updating){c.updating=false;c.AJAX.abort();c.AJAX=null}};this.update=function(g,e){if(c.updating){return false}c.AJAX=null;if(window.XMLHttpRequest){c.AJAX=new XMLHttpRequest()}else{c.AJAX=new ActiveXObject("Microsoft.XMLHTTP")}if(c.AJAX==null){return false}else{c.AJAX.onreadystatechange=function(){if(c.AJAX.readyState==4){c.updating=false;c.callback(c.AJAX.responseText,c.AJAX.status,c.AJAX.responseXML);c.AJAX=null}};c.updating=new Date();if(/post/i.test(e)){var f=d+"?"+c.updating.getTime();c.AJAX.open("POST",f,true);c.AJAX.setRequestHeader("Content-type","application/x-www-form-urlencoded");c.AJAX.setRequestHeader("Content-Length",g.length);c.AJAX.send(g)}else{var f=d+"?"+g+"&timestamp="+(c.updating.getTime());c.AJAX.open("GET",f,true);c.AJAX.send(null)}return true}};var d=b;this.callback=a||function(){}};
		// Update the caption. Setup to be called in an 'on change' so that all the comments are saved automatically
		// and Submit doesn't need to be pressed.
		function updateCaption(object) {
			var myRequest = new ajaxObject("<?=rrawurlencode($baseURL . "captions/" . $dir) ?>");
			myRequest.update(encodeURIComponent(object.name)+"="+encodeURIComponent(object.value),"POST");
			//location.reload();
		}
	</script>
<a href="<?=rrawurlencode($baseURL . $dir) ?>">Return To Gallery</a>
<form action="<?=rrawurlencode($baseURL . "captions/" . $dir) ?>" method="POST">
	<table>
		<tr>
			<td align="right">
				Album Title:
			</td>
			<td>
				<input type="text" name="<?=base64_encode('title'); ?>" id="title" size="20" value="<?=$captions['title'] ?>" tabindex="1" onchange="updateCaption(this)">
			</td>
		</tr>
<?
	if (count($images_with_thumbs)) {
		foreach($images_with_thumbs as $idx => $image) {
			$pictures.= "\t<tr><td align=\"right\">";
			$pictures.= "<a href=\"" . rrawurlencode($baseURL . $dir . $image) . "\"><img src=\"" . rrawurlencode($baseURL . "cache/" . $dir . "preview/" . $image) . "\" alt=\"" . htmlentities($image) . "\"></a>";
			$pictures.= "</td><td valign=\"bottom\"><textarea id=\"caption$idx\" name=\"" . base64_encode($image) . "\" rows=\"20\" cols=\"30\" tabindex=\"" . ($idx + 2) . "\" onchange=\"updateCaption(this)\">" . $captions[$image] . "</textarea></td></tr>\n";
		}
	}
	echo $pictures;
?>
	<tr>
		<td colspan="2" align="center">
			<input type="submit" value="Change Captions">
		</td>
	</tr>
	</table>
</form>
<?
	if (function_exists("printfooter")) {
		printfooter($title);
	} else {
		html_footer($title);
	}
}
# Get caption from exif data. The first time a comments section is created, comments shouldn't exist. The user may have put them in prior to uploading them, so try to get them first.
function getExifCaption($image) {
	global $baseURL, $scriptPath, $dir;
	$exif = @read_exif_data($scriptPath . $dir . $image);
	return $exif['COMMENT'][0];
}
# Set exif comments
function setExifCaption($image, $caption) {
	global $baseURL, $scriptPath, $dir, $jheadBinary;
	if (!is_executable($jheadBinary)) return;
	# Make sure the file exists before trying to write it.
	if (is_file($scriptPath . $dir . $image)) {
		$command = "$jheadBinary -cl " . escapeshellarg($caption) . " " . escapeshellarg($scriptPath . $dir . $image);
		shell_exec($command);
	}
}
/* End Comments Code */
/* RSS functions for CoolIris */
function printRSS() {
	# Define Globals
	global $doc, $channel, $dir;
	# Setup RSS Data
	$doc = new DomDocument('1.0');
	$doc->formatOutput = true;
	$root = $doc->createElement('rss');
	$root->setAttribute('version', '2.0');
	$root->setAttribute('xmlns:media', 'http://search.yahoo.com/mrss/');
	$root->setAttribute('xmlns:atom', 'http://www.w3.org/2005/Atom');
	$root = $doc->appendChild($root);
	# Create the channel
	$channel = $doc->createElement("channel");
	$channel = $root->appendChild($channel);
	$folder_scan_results = folder_scan();
	# Build up RSS function
	rss_builder($folder_scan_results, $dir);
	# Create RSS String
	$xml_string = $doc->saveXML();
	# Output RSS w/header
	header("Content-Type: application/rss+xml");
	echo $xml_string;
}
# Build XML from all the gathered images
function rss_builder($results, $path = "") {
	global $baseURL, $scriptPath;
	# For each image, add the image.
	foreach($results['images'] as $image) {
		add_image_xml($path, $image);
	}
}
# Add a single image to XML document.
function add_image_xml($path, $image) {
	global $baseURL, $channel, $doc;
	# Create the Item
	$item = $doc->createElement("item");
	# Create
	$item = $channel->appendChild($item);
	# Create Item Details
	# Title
	$title = $doc->createElement("title");
	$title->appendChild($doc->createTextNode($image));
	# Description
	$description = $doc->createElement("media:description");
	$description->appendChild($doc->createTextNode(htmlentities($image)));
	# Link to the full size image
	$link = $doc->createElement("link");
	$link->appendChild($doc->createTextNode(htmlentities($baseURL . $path . $image)));
	# Thumbnail
	$thumbnail = $doc->createElement("media:thumbnail");
	$thumbnail->setAttribute('url', htmlentities($baseURL . "cache/" . $path . "gallery/" . $image));
	$thumbnail->setAttribute('type', "image/jpeg");
	#Preview
	$content = $doc->createElement("media:content");
	$content->setAttribute('url', (htmlentities($baseURL . "cache/" . $path . "preview/" . $image)));
	$content->setAttribute('type', "image/jpeg");
	# Append everything to item
	$item->appendChild($title);
	$item->appendChild($description);
	$item->appendChild($link);
	$item->appendChild($thumbnail);
	$item->appendChild($content);
}
/* End RSS Functions */
# Get information about a movie file.
function movie_info($file) {
	global $baseURL, $scriptPath, $dir, $ffmpegBinary;
	$cacheFile = $scriptPath . "cache/" . $dir . $file . ".txt";
	if (is_file($cacheFile)) {
		$data = unserialize(file_get_contents($cacheFile));
	} elseif (!is_executable($ffmpegBinary)) {
		$data['width'] = 640;
		$data['height'] = 480;
		$data['duration'] = "";
		$data['duration_text'] = "";
	} else {
		# ffmpeg dumps file info to stderr so it has to be redirected to stdout so it can be read in.
		$cmd = "$ffmpegBinary -i " . escapeshellarg($scriptPath . $dir . $file) . " 2>&1";
		$str = shell_exec($cmd);
		# Get duration.
		preg_match('/Duration: ([\\d]+):([\\d]+):([\\d]+)\\./', $str, $matches);
		$hours = $matches[1];
		$minutes = $matches[2];
		$seconds = $matches[3];
		$data['duration'] = 3600 * $hours + 60 * $minutes + $seconds;
		$data['duration_text'].= $hours == 0 ? "" : $hours . "h";
		$data['duration_text'].= $minutes == 0 ? "" : $minutes . "m";
		$data['duration_text'].= $seconds == 0 ? "" : $seconds . "s";
		# Get video size.
		preg_match('/Video:[^,]+,[^,]+, ([\\d]+)x([\\d]+)/', $str, $matches);
		$data['width'] = $matches[1];
		$data['height'] = $matches[2];
		# If the cache folder doesn't exist.
		if (!is_dir($scriptPath . "cache/" . $dir)) mkdir($scriptPath . "cache/" . $dir, 0755, true);
		# Write the data.
		@file_put_contents($cacheFile, serialize($data));
	}
	return $data;
}
/* Thumbnail creation functions */
# Parse the folders looking for thumbnails that need to be created.
function thumbnailParse($parseDir = "./", $recurse = TRUE) {
	global $scriptPath, $dir;
	# Add trailing slash.
	$parseDir = fixDir($parseDir);
	# Remove the script path from the directory.
	# If the script is called as ./ and the $parseDir is ./, it leaves $scriptPath as ./ and $parseDir as ""
	# If the script is called as /home/<user>/host.com/ and $parseDir is /home/<user>/host.com/pictures/ $parseDir is pictures/ so that /home/<user>/host.com/cache/pictures can be created.
	$parseDir = str_replace($scriptPath, "", $parseDir);
	# Get list of folders & images
	$dir = $parseDir;
	$folder_scan_results = folder_scan();
	$images = $folder_scan_results['images'];
	$movies = $folder_scan_results['movies'];
	$directories = $folder_scan_results['directories'];
	#
	foreach($images as $image) {
		if (!is_file($scriptPath . 'cache/' . $dir . 'preview/' . $image)) {
			echo ("Creating: $dir$image preview\n");
			makeThumb($dir, $image, 1);
		}
		if (!is_file($scriptPath . 'cache/' . $dir . 'gallery/' . $image)) {
			echo ("Creating: $dir$image gallery\n");
			makeThumb($dir, $image, 2);
		}
	}
	foreach($movies as $movie) {
		movie_info($movie);
	}
	if (count($directories) > 0 && $recurse === TRUE) {
		foreach($directories as $file) {
			thumbnailParse($parseDir . $file);
		}
	}
}
# Make the thumbnail.
function makeThumb($dir, $file, $type) {
	global $thumbBinary, $scriptPath, $previewWidth, $previewHeight, $galleryHeight, $galleryWidth;
	# makethumbs.php creates thumbnails relative to itself. So replace the script path in $dir with nothing.
	$dir = str_replace($scriptPath, "", $dir);
	# Create the thumb directory relative to where the script is.
	$thumb_dir = $scriptPath . "cache/" . $dir;
	# Type 1, previews
	if ($type == 1) {
		# Add 'preview' to thumb directory base.
		$thumb_dir.= "preview/";
		# If the directory doesn't exist, make it. (This is the only part that really requires php5 for the recursive function)
		if (!is_dir($thumb_dir)) mkdir($thumb_dir, 0755, true);
		$dest_width = $previewWidth;
		$dest_height = $previewHeight;
	}
	# Type 2, gallery
	if ($type == 2) {
		# Add 'gallery' to thumb directory base.
		$thumb_dir.= "gallery/";
		# If the directory doesn't exist, make it. (This is the only part that really requires php5 for the recursive function)
		if (!is_dir($scriptPath . "cache/" . $dir . "gallery/")) mkdir($scriptPath . "cache/" . $dir . "gallery/", 0755, true);
		$dest_width = "";
		$dest_height = $galleryHeight;
	}
	# Img quality
	$quality = 60;
	# Resize
	$resize = $dest_width . "x" . $dest_height;
	# Input File
	$input = escapeshellarg($scriptPath . $dir . $file);
	# Output file.
	$output = escapeshellarg($thumb_dir . $file);
	global $thumbBinary;
	# Create the thumbnail.
	if (basename($thumbBinary) == "gm") {
		# If the Graphics Magick is used, add the convert command to the binary
		$command = $thumbBinary . " convert";
	} else {
		$command = $thumbBinary;
	}
	$command.= " -size $resize $input -quality $quality -resize $resize +profile '*' $output";
    	//echo $command."\n\n";
	exec($command);
}
/* Misc Functions used throughout the file */
# Assign arguments from the input.
function getArguments($input) {
	global $scriptPath, $baseURL;
	if (is_file($scriptPath . $input)) {
		$dir = fixDir(dirname($input));
		$file = basename($input);
		return array($dir, $file);
	} elseif (is_file(stripslashes($scriptPath . $input))) {
		$input = stripslashes($input);
		$dir = fixDir(dirname($input));
		$file = basename($input);
		return array($dir, $file);
	} elseif (is_dir($scriptPath . $input)) {
		$dir = fixDir($input);
		return array($dir, "");
	} elseif (is_dir(stripslashes($scriptPath . $input))) {
		$input = stripslashes($input);
		$dir = fixDir($input);
		return array($dir, "");
	}
	# Should never occur. But if it does, return to the base of the gallery
	header("Location: $baseURL");
}
# Short function to add a trailing slash to the directory value, Used multiple times, so a function was created.
function fixDir($dir) {
	return ($dir == "" || $dir == ".") ? "" : (substr($dir, -1) == "/" ? $dir : $dir . "/");
}
# Recursive Raw URL Encode. For those people that like non-standard charaters in their file/folder names.
function rrawurlencode($url) {
	# Determine if the URL is a relative or absolute URL
	$absolute = (substr($url, 0, 7) == 'http://') || (substr($url, 0, 8) == 'https://');
	$exploded_url = explode("/", $url);
	# Start URL String
	$url = "";
	# If the url is absolute, we don't want to encode http(s)://
	if ($absolute) {
		# Shift it off the beginning of the array.
		$url = array_shift($exploded_url);
	}
	#  Build up the URL raw encoding each directory/file. (rawurlencode will encode / if you pass the entire string)
	foreach($exploded_url as $part) {
		$url.= "/" . rawurlencode($part);
	}
	# Return the new url.
	return $url;
}
# Get current file size in human readable format.
function getFileSize($file) {
	global $scriptPath, $dir;
	$size = filesize(realpath($file));
	$units = array(' B', ' KB', ' MB', ' GB', ' TB');
	for ($i = 0;$size > 1024;$i++) {
		$size/= 1024;
	}
	return round($size, 2) . $units[$i];
}
# Get the exif information from when the photo was taken.
function getFileDate($file) {
	@$exif = exif_read_data($file);
	if (empty($exif['DateTimeOriginal'])) {
		$timestamp = "";
	} else {
		$a = explode(" ", $exif['DateTimeOriginal']);
		$timestamp = date("F j, Y, g:i a", strtotime(str_replace(":", "/", $a[0]) . " " . $a[1]));
		if ($timestamp == "December 31, 1969, 4:00 pm") {
			$timestamp = "";
		}
	}
	return $timestamp;
}
# Print the path to the gallery
function printPath() {
	global $baseURL, $dir;
	# Folder Separator to use when printing folders.
	# Example 1, " > ": Picture Gallery > Folder 1 > Subfolder 1
	# Example 2 "/": Picture Gallery/Folder 1/Subfolder 1
	$folderSeparator = "/";
	echo "<!-- Begin Folder Paths -->\n";
	echo "<div id='path'>\n";
	# Print the base folder.
	echo "<a href=\"" . rrawurlencode($baseURL) . "\">Picture Gallery</a>";
	# If directory isn't empty (We're in a sub folder)
	if (!empty($dir)) {
		# Separate the folders
		$directories = explode("/", trim($dir, '/'));
		$compound = "";
		# Add them on to each other until the current folder.
		foreach($directories as $directory) {
			if (empty($directory)) continue;
			$compound.= $directory . "/";
			echo htmlentities($folderSeparator) . "<a href=\"" . rrawurlencode($baseURL . $compound) . "\">$directory</a>";
		}
	}
	echo "</div>\n";
	echo "<!-- End Folder Paths -->\n\n";
}
# Create the htaccsess file if it doesn't exist.
function makeHtaccess() {
	global $scriptPath, $baseURL;
	if (is_file($scriptPath . ".htaccess")) return;
	$htaccess = <<<EOF
Options +FollowSymLinks
Options +Indexes
RewriteEngine On
RewriteRule ^bbclone/.*$ - [PT]
RewriteRule ^([^_]+)_player/(.*)$ index.php?command=$1_player&dir=$2 [NC,L]
RewriteRule ^rss/(.*)$ index.php?command=rss&dir=$1 [NC,L]
RewriteRule ^slide/(.*)$ index.php?command=slide&dir=$1 [NC,L]
RewriteRule ^thumbs/(.*)$ index.php?command=thumbs&dir=$1 [NC,L]
RewriteRule ^captions/(.*)$ index.php?command=captions&dir=$1 [NC,L]
RewriteRule ^(.*)/$ index.php?command=gallery&dir=$1
RewriteRule ^$ index.php?command=gallery&dir= 
EOF;
	if (!is_writable($scriptPath)) {
		$who = exec('whoami');
		html_header("Permissions Error");
		echo "$scriptPath is not writable by $who. Can not create required .htaccess file.";
		html_footer();
		exit();
	}
	@file_put_contents($scriptPath . ".htaccess", $htaccess);
	header("Location: $baseURL");
	exit("Unknown Error. Please press refresh.");
}
?>
