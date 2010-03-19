<?
/* Config */
$thumbBinary = "/home/commonlibraries/bin/gm"; # Define the binary/command used to create thumbnails. Image Magick and Graphics Magick both work ('convert' or 'gm', respectively.)
$thumbBinary = "/usr/bin/convert";
# Optional. Leave blank if undefined.
$ffmpegBinary = "/usr/bin/ffmpeg"; # Location of the ffmpeg binary, for displaying video play length.
$jheadBinary = "/usr/bin/jhead"; # Location of the jhead binary, for embedding Captions.
$enable_caption_editing = TRUE; # Allow caption editing
$gallery_images_in_preview = 2; # Number of gallery to show in preview mode.
$galleryHeight = 150; # Height of gallery images on the gallery page.
$galleryWidth = 200; # Width of the gallery images on preview page.
$previewWidth = 600; # Width of the preview images on preview page.
$previewWidth = 450; # Height of the preview images on preview page.
$displayPreviewLoading = TRUE; # Display the "Loading Previews" to allow all images time to cache when viewing a preview.
$previewLoadingThreshold = 2; # Threshold at which to display "Loading Previews". In MB.
$htmlLinks = FALSE;
$bbcodeLinks = FALSE;
/* End Config */
/* Baseline Variables. Stuff used everywhere */
$scriptPath = fixDir(getcwd());
$baseURL = dirname("http://" . $_SERVER['HTTP_HOST'] . $_SERVER['SCRIPT_NAME']) . "/";
# Fails if there are 'query string' characters in the query string. For example a directory named "VW
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
	case "kml": # Generate KML File
		printKML($file);
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
		define("_BBC_PAGE_NAME", "Thumbs: " . $dir);@include_once (COUNTER);
		# Lock file to prevent duplicate instances from being created.
		$lock_file = $scriptPath . "/.lock";
		# If a lock file exists and the process has been running for less than 5 minutes: Die.
		# Assume something went wrong if it has been running for more than 5
		if (is_file($lock_file) && (filemtime($lock_file) + 300 > time())) {
			//exit("Already running...\n");
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
# Header and footer functions. Comment out to include your own custom functions, or add includes to your header/footer.
function printheader($title = "") {
	html_header($title);
}
function printfooter() {
	html_footer();
}
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
			background: #FFF;
			/* border: 1px dashed #000 */
		}
		#movies {
			width: 50%;
			margin: 0 0;
			text-align: left;
			padding: 0;
			/* border: 1px dashed #000; */
		}
		#path {
			margin: 0 auto;
			text-align: center;
			padding: 0;
			/* border: 1px dashed #000; */
		}
		#image_gallery {
			margin: 10px auto;
			text-align: center;
			padding: 0;
			/* border: 1px dashed #000; */
		}
		#preview {
			position: relative;
			margin: 0 auto;
			text-align: center;
			padding: 0;
			/* border: 1px dashed #000; */
		}
		#leftpreview {
			float: left;
			position: relative;
			vertical-align: middle;
			width: <?=$galleryWidth ?>px;
			z-index:1;
			/* border: 1px dashed #000; */
		}
		#centerpreview {
			margin-left: <?=$galleryWidth + 5 ?>px;
			margin-right: <?=$galleryWidth + 5 ?>px;
			position: relative;
			z-index:10;
			/* border: 1px dashed #000; */
		}
		#rightpreview {
			float: right;
			position: relative;
			width: <?=$galleryWidth ?>px;
			z-index:2;
			/* border: 1px dashed #000; */
		}
		#loadingPreviews {
			display:none;
			position:absolute;
			top:0;
			left:0;
			z-index:1000;
			text-align:center;
			width:300px;
			padding:10px;
			background: #FFF;
		}
		#darkenBackground {
			background-color: rgb(0, 0, 0);
			opacity: 0.75; /* Safari, Opera */
			-moz-opacity:0.75; /* FireFox */
			filter: alpha(opacity=75); /* IE */
			z-index: 100;
			height: 100%;
			width: 100%;
			background-repeat:repeat;
			position:fixed;
			top: 0px;
			left: 0px;
			display:none;
		}
		#generatingThumb {
			margin: 10px 0;
			/* border: 1px dashed #000; */
		}
		.cache {
			display: none;
			/* border: 1px dashed #000; */
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
	if (empty($captions['title'])) {
		printheader(htmlentities($dir));
	} else {
		printheader($captions['title']);
	}
	if ($enable_caption_editing && count($images)) {
		echo "<a href=\"" . rrawurlencode($baseURL . "captions/" . $dir) . "\">Edit Captions</a>\n";
	}
	printPath();
	print_gallery_images($images);
	print_gallery_movies($movies);
	print_gallery_directories($directories);
	printfooter();
}
function loadCache($file) {


}
# Preview Image
function preview($currImage) {
	global $baseURL, $scriptPath, $dir, $gallery_images_in_preview, $galleryWidth, $previewWidth, $dynamicPreviewSizing, $displayPreviewLoading,$previewLoadingThreshold;
	# Scan the folder for pictures & thumbnails
	$folder_scan_results = folder_scan();
	# Find Images with Thumbnails.
	$images_with_thumbs = images_with_thumbnails($folder_scan_results['images']);
	$noThumbs = count($folder_scan_results['images']) - count($images_with_thumbs);
	# Find where the current image is in the list of images.
	$imageIndex = array_search($currImage, $images_with_thumbs);
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
		$cached_images = $cache["images_with_thumbs"];
		$ratio = $cache["ratio"];
		$fileSize = $cache["fileSize"];
		$exifDate = $cache["exifDate"];
		$imgName = $cache["imgName"];
		$fullLinkURL = $cache["fullLinkURL"];
		$preview = $cache["preview"];
		$gallery = $cache["gallery"];
		$previewPageSize = $cache['previewPageSize'];
	}
	# Determine the uncached images.
	$uncached_images = count($cache["images_with_thumbs"])-$cached_images;
	# Rebuild cache if there were any changes.
	if (count($uncached_images)) {
		foreach($images_with_thumbs as $idx => $image) {
			$size = getimagesize($scriptPath . $dir . $image);
			$ratio[$idx] = $size[0] / $size[1];
			$fileSize[$idx] = getFileSize($scriptPath . $dir . $image);
			$exifDate[$idx] = getFileDate($scriptPath . $dir . $image);
            $exifLocation[$idx]=getFileLocation($scriptPath . $dir . $image);
			$imgName[$idx] = htmlentities(pathinfo($image, PATHINFO_FILENAME));
			$fullLinkURL[$idx] = rrawurlencode($baseURL . $dir . $image);
			$preview[$idx] = rrawurlencode($baseURL . "cache/" . $dir . "preview/" . $image);
			$gallery[$idx] = rrawurlencode($baseURL . "cache/" . $dir . "gallery/" . $image);
			$previewSize[$idx] = filesize($scriptPath . "cache/" . $dir . "preview/" . $image);
			$gallerySize[$idx] = filesize($scriptPath . "cache/" . $dir . "gallery/" . $image);
		}
		$previewPageSize=(array_sum($previewSize)+array_sum($gallerySize)/(1024*1024));
		# If there was a cache change.
		$cache["images_with_thumbs"] = count($images_with_thumbs);
		$cache["ratio"] = $ratio;
		$cache["fileSize"] = $fileSize;
		$cache["exifDate"] = $exifDate;
		$cache["imgName"] = $imgName;
		$cache["fullLinkURL"] = $fullLinkURL;
		$cache["preview"] = $preview;
		$cache["gallery"] = $gallery;
		$cache['previewPageSize'] = $previewPageSize;
	}
	$cacheSize = (array_sum($previewSize) + array_sum($gallerySize)) / (1024 * 1024);
	# Count the number of images, used for wrap around math.
	$imageCount = count($images_with_thumbs);
	for ($i = - $gallery_images_in_preview;$i <= $gallery_images_in_preview;$i++) {
		# Build the array +- image_previews.
		$slide_previews[$i] = $gallery[($imageIndex + $i + $imageCount) % $imageCount];
	}
	printheader($imgName[$imageIndex]);
	# Print the directory path.
	printPath();
?>
<!-- Begin Previews -->
<div id="loadingPreviews" onclick="javascript:hideLoadingPreview();">
	<img alt="LoadingPreviews" src="<?=printAjaxGIF() ?>" title="Loading Previews">
	<br>
	Loading previews.<br>
	Use Arrow Keys To Navigate Pictures
</div>
<div id="darkenBackground"></div>
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
			<img src="<?=$preview[$imageIndex] ?>" width="<?=$previewWidth ?>" alt="Current Preview" id="mainPreview">
		</a>
		<br>
		<a href="<?=$fullLinkURL[$imageIndex] ?>" target="_new" id="fullLink">Link to Full Picture (<?=$fileSize[$imageIndex] ?>)</a><br>
		<div id="caption" style="display:<?=empty($captions[$imageIndex]) ? "none" : "block" ?>"><?=str_replace("\n", "<br>", htmlentities($captions[$imageIndex])) ?></div>
		<div id="timeStamp" style="display:<?=empty($exifDate[$imageIndex]) ? "none" : "block" ?>"><?=$exifDate[$imageIndex] ?></div>
	</center>
	<img src="<?=$slide_previews[0] ?>" id="gallery0" class="cache" alt='cache'>
	<?=display_generating_thumbnails($noThumbs); ?>
	<? /* The current index of the image */ ?>
	<div style="display:none" id="imageIndex"><?=$imageIndex?></div>
	<div style="clear:both;"></div>
</div>
</div>
<div id="end_javascript">
<script type="text/javascript">
<?
	if ($displayPreviewLoading && $previewPageSize > 1) {
?>
// Display 'loading previews' block'
// Set it to hide after the page is fully loaded.
function showLoadingPreview() {
	if (document.body.clientHeight==0) return;
	$('loadingPreviews').style.top=(document.body.clientHeight-75)/2;
	$('loadingPreviews').style.left=(document.body.clientWidth-300)/2;
	$('loadingPreviews').style.display='block';
	$('darkenBackground').style.display='block';
	document.onkeydown = keyBoardNav;
}
function hideLoadingPreview() {
	$("navDirections").innerHTML="(Use Arrow Keys to Navigate)";
	$('loadingPreviews').style.display='none';
	$('loadingPreviews').style.top=
	$('loadingPreviews').style.left=-200;
	$('darkenBackground').style.display='none';
}
showLoadingPreview();
window.onload=hideLoadingPreview;
<?
	}
?>
var previewSrc = Array();
var gallerySrc = Array();
var fullLinkURL = Array();
var imgSize = Array();
var timeStamp = Array();
var imgName = Array();
var ratio = Array();
var caption = Array();
var html = Array();
var bbCode = Array();
<?
	for ($i = 0;$i < $imageCount;$i++) {
		//$hardLinks = hardlinks($images_with_thumbs[$i]);
		echo "previewSrc[$i]=\"" . $preview[$i] . "\";\n";
		echo "gallerySrc[$i]=\"" . $gallery[$i] . "\";\n";
		echo "fullLinkURL[$i]=\"" . $fullLinkURL[$i] . "\";\n";
		echo "imgName[$i]=\"" . $imgName[$i] . "\";\n";
		echo "ratio[$i]=\"" . $ratio[$i] . "\";\n";
		echo "imgSize[$i]=\"Link To Full Picture (" . $fileSize[$i] . ")\";\n";
		echo "timeStamp[$i]=\"" . $exifDate[$i] . "\"\n";
		echo "caption[$i]=\"" . $captions[$i] . "\";\n";
		echo "html[$i]=\"\";\n";
		echo "bbCode[$i]=\"\";\n\n";
	}
?>
function $(e) {return document.getElementById(e);}
function keyBoardNav(e) {
	if (!document.location.href.match("slide")) {
		return true;
	}
	var KeyID = (window.event) ? event.keyCode : e.keyCode;
	switch(KeyID) {
		case 32:
		case 37:
		case 38:
			changePicture(1);
		break;
		case 40:
		case 39:
			changePicture(-1);
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
	<?
	/*
	Change the side gallery previews first. This is so if the images are tall enough to warrant the
	vertical scroll bar to appear, all the remaining calculations are done using the new numbers.
	*/
?>
	var l=previewSrc.length;
	for (i=-<?=$gallery_images_in_preview?>;i<=<?=$gallery_images_in_preview?>;i++) {
		try {
			$('gallery'+i).src=gallerySrc[(l+imgIdx+i)%l];
		} catch(err) {
		}
	}
	//$('htmlHardLink').value=html[imgIdx];
	//$('bbCodeHardLink').value=bbCode[imgIdx];
	document.title=decodeURIComponent(imgName[imgIdx]);
	// Grab the current div width.
	var centerwidth=$('centerpreview').clientWidth;
	// Set the new current image index.
	$('imageIndex').innerHTML=imgIdx;
<?
	/*
	The reason the next two lines exist is because I found in testing with the 'dynamicResize'
	is if you changed images and the next image had a radically different ratio than the current
	image then you would see one of the two images distorted. (As in src.= or adjustWidth() would fire
	in random orders). By hiding the center block, then changing the image, user doesn't see an ugly
	distorted image.
	
	So hide the center preview. Then set a timeout to redisplay it.
	*/
?>
	$('centerpreview').style.visibility='hidden';
	window.setTimeout(function(){$('centerpreview').style.visibility='visible';},5);
	$('mainPreview').src=previewSrc[imgIdx];
	adjustWidth(imgIdx,centerwidth);
	$('fullLink').href=fullLinkURL[imgIdx];
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
function adjustWidth(imgIdx,centerwidth) {
	// IE6 Sucks.
	if (typeof window.XMLHttpRequest=="undefined") {
		return;
	}
	// If the function is called with no input
	if (imgIdx==undefined) {
		imgIdx=parseInt($('imageIndex').innerHTML);
	} 
	// Variables
	var height;var width;
	// Get width of center CSS block. If it is undefined or 0, try to get it.
	if (centerwidth==undefined||centerwidth==0) {
		width=$('centerpreview').clientWidth;
	} else {
		width=centerwidth;
	}
	// Some other error where we can't get the width (IE7)
	if (width==0) {
		return;
	}
	// Determine the height based on ratio
	height=width/ratio[imgIdx];
	// If the height is greater than the height of the document
	if (height>(document.body.clientHeight-105)) {
		// Set that as the constraight
		height=(document.body.clientHeight-105);
		// Back Calculate Width
		width=Math.min(height*ratio[imgIdx],width);
	}
	// Set their sizes.
	$('mainPreview').width=width;
	$('mainPreview').height=height;
}
/* Run Initial Stuff */
adjustWidth(<?=$imageIndex ?>);
/* Set Callbacks */
window.onresize=function() {adjustWidth(parseInt($('imageIndex').innerHTML));};
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
	printfooter();
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
	<embed src="<?=$movie_file
?>" href="<?=$movie_file
?>" width="<?=$data['width'] ?>" height="<?=$data['height'] + 25 ?>" type="video/quicktime" controller="true" autoplay="true" plugin = "quicktimeplugin" cache="true" controller="true" target="myself">  
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
	global $baseURL, $scriptPath, $dir;
	if ($noThumbs) {
?>
<div id="generatingThumb">
<!-- Rather than use AJAX to 'call' the make thumbs script, just reference it using an image-->
<img src="<?=$baseURL
?>thumbs/<?=$dir
?>" alt="" width="0" height="0" style="display:none">
<img alt="Generating Thumbnails" src="<?=printAjaxGIF() ?> title="Generating Thumbnails"><br>
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
		sort($images);
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
				$pictures.= "<img src=\"" . rrawurlencode($baseURL . "cache/" . $dir . "gallery/" . $image) . "\" height=\"$galleryHeight\" alt=\"" . htmlentities($image) . "\" title=\"Hello World\"></a>\n";
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
	$gallery = $baseURL . "cache/" . $dir . "preview/" . urlencode($file);
	$fullsize = $baseURL . $dir . $file;
	$html = "<a href='" . addslashes($fullsize) . "'><img src='$gallery' border='0' style='border:0'></a>";
	$bbCode = "[url=" . $fullsize . "][img]" . $gallery . "[/img][/url]";
	return Array('html' => $html, 'bbCode' => $bbCode);
}
function printHardLinks($file) {
	$hardLinks = hardLinks($file);
?>
<div id="hardLinks" style="display:none">
	<input type="text" value="<?=$hardLinks['bbCode'] ?>" id="bbCodeHardLink"><br>
	<input type="text" value="<?=$hardLinks['html'] ?>" id="htmlHardLink"><br>
</div>
<?
}
/* Caption Functions */
# Get the captions from the captions file
function get_captions() {
	global $dir, $scriptPath;
	#  Get the captions for the image files.  All captions files are stored in the cache directory
	if ($captions = unserialize(@file_get_contents("cache/" . $dir . "captions.txt"))) {
		return $captions;
	} else {
		return array();
	}
}
function makeCaptions($new_captions) {
	global $baseURL, $scriptPath, $dir, $jheadBinary;
	# If there are new captions
	if (!empty($new_captions)) {
		# Get the old captions
		$captions = get_captions();
		# Append new captions to those already there
		foreach($new_captions as $i => $c) {
			# Base 64 was the best way (I thought of) of dealing with oddly named images in the input form.
			$captions[base64_decode($i) ] = trim($c);
			# If the "embed caption in exif" is checked.
			if ($_POST['exifEmbed'] == "on") setExifCaption(base64_decode($i), trim($c));
		}
		# Write the captions to disk.
		@file_put_contents($scriptPath . "cache/" . $dir . "captions.txt", serialize($captions));
		# If the function was called from AJAX... (Only one update at a time).
		if (count($new_captions) == 1) {
			exit;
		}
	} else {
		# If the captions weren't already gotten, die.
		$captions = get_captions();
	}
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
	printheader("Captions: " . htmlentities($dir));
?>
	<script type="text/javascript">
function ajaxObject(b,a){var c=this;this.updating=false;this.abort=function(){if(c.updating){c.updating=false;c.AJAX.abort();c.AJAX=null}};this.update=function(g,e){if(c.updating){return false}c.AJAX=null;if(window.XMLHttpRequest){c.AJAX=new XMLHttpRequest()}else{c.AJAX=new ActiveXObject("Microsoft.XMLHTTP")}if(c.AJAX==null){return false}else{c.AJAX.onreadystatechange=function(){if(c.AJAX.readyState==4){c.updating=false;c.callback(c.AJAX.responseText,c.AJAX.status,c.AJAX.responseXML);c.AJAX=null}};c.updating=new Date();if(/post/i.test(e)){var f=d+"?"+c.updating.getTime();c.AJAX.open("POST",f,true);c.AJAX.setRequestHeader("Content-type","application/x-www-form-urlencoded");c.AJAX.setRequestHeader("Content-Length",g.length);c.AJAX.send(g)}else{var f=d+"?"+g+"&timestamp="+(c.updating.getTime());c.AJAX.open("GET",f,true);c.AJAX.send(null)}return true}};var d=b;this.callback=a||function(){}};

// Update the caption. Setup to be called in an 'on change' so that all the comments are saved automatically
// and Submit doesn't need to be pressed.
function updateCaption(object) {
	var myRequest = new ajaxObject("<?=rrawurlencode($baseURL . "captions/" . $dir) ?>");
	myRequest.update(encodeURIComponent(object.name)+"="+encodeURIComponent(object.value),"POST");
}
	</script>
<a href="<?=rrawurlencode($baseURL.$dir)?>">Return To Gallery</a>
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
			$pictures.= "</td><td valign=\"bottom\"><textarea id=\"caption$idx\" name=\"" . base64_encode($image) . "\" rows=\"20\" cols=\"30\" tabindex=\"" . ($idx + 2) . "\"onchange=\"updateCaption(this)\">" . $captions[$image] . "</textarea></td></tr>\n";
		}
	}
	echo $pictures;
	# If the jhead binary is executable, add the option to re-embed the captions into the image file.
	if (is_executable($jheadBinary)) {
?>
	<tr>
		<td align="right">
			Add comments to EXIF data of JPEG images (Must press "Change Captions"):
		</td>
		<td>
			<input type="checkbox" name="exifEmbed" checked="checked">
		</td>
	</tr>
<?
	}
?>
	<tr>
		<td colspan="2" align="center">
			<input type="submit" value="Change Captions">
		</td>
	</tr>
	</table>
</form>
<?
	printfooter();
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
function printKML($file) {
    /* KML Functions for Google maps */
    global $dom,$docNode,$baseURL, $scriptPath, $dir;
    $dom = new DOMDocument('1.0', 'UTF-8');
    $node = $dom->createElementNS('http://earth.google.com/kml/2.1', 'kml');
    $parNode = $dom->appendChild($node);
    $dnode = $dom->createElement('Document');
    $docNode = $parNode->appendChild($dnode);
    #
    $picStyleNode = $dom->createElement('Style');
    $picStyleNode->setAttribute('id', 'picStyle');
    $picIconstyleNode = $dom->createElement('IconStyle');
    $picIconstyleNode->setAttribute('id', 'picIcon');
    $picIconNode = $dom->createElement('Icon');
    $picHref = $dom->createElement('href', 'http://maps.google.com/mapfiles/kml/shapes/camera.png');
    $picIconNode->appendChild($picHref);
    $picIconstyleNode->appendChild($picIconNode);
    $picStyleNode->appendChild($picIconstyleNode);
    $docNode->appendChild($picStyleNode);
    if (!empty($file)) {
        $images_with_thumbs[]=$file;
    } else {
    	# Scan the folder for pictures & thumbnails
        $folder_scan_results = folder_scan();
        # Find Images with Thumbnails.
        $images_with_thumbs = images_with_thumbnails($folder_scan_results['images']);

    }
    foreach ($images_with_thumbs as $image) {
        addImageKML($image);
    }
    $kmlOutput = $dom->saveXML();
    header('Content-type: application/vnd.google-earth.kml+xml');
    echo $kmlOutput;
    die;
}

function addImageKML($file) {
    global $dom,$docNode,$baseURL, $scriptPath, $dir;
    $location=getFileLocation($scriptPath.$dir.$file);
    if (is_null($location)) {
        return;
    }
    $node = $dom->createElement('Placemark');
    $placeNode = $docNode->appendChild($node);
    // Creates an id attribute and assign it the value of id column.
    $placeNode->setAttribute('id', 'placemark' . 1);
    // Create name, and description elements and assigns them the values of the name and address columns from the results.
    $nameNode = $dom->createElement('name',htmlentities($file));
    $placeNode->appendChild($nameNode);
    $descNode = $dom->createElement('description');
    $cdata = $dom->createCDATASection("<a href='${baseURL}${dir}${file}' target='_blank'> <img src='${baseURL}cache/${dir}gallery/${file}'/></a>");
    $descNode->appendChild($cdata);
    $placeNode->appendChild($descNode);
    $styleUrl = $dom->createElement('styleUrl', '#picStyle');
    $placeNode->appendChild($styleUrl);
    // Creates a Point element.
    $pointNode = $dom->createElement('Point');
    $placeNode->appendChild($pointNode);
    // Creates a coordinates element and gives it the value of the lng and lat columns from the results.
    $coorNode = $dom->createElement('coordinates', $location);
    $pointNode->appendChild($coorNode);
    return;
}


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
		add_image_rss($path, $image);
	}
}
# Add a single image to XML document.
function add_image_rss($path, $image) {
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
	global $thumbBinary, $scriptPath, $previewWidth, $galleryHeight, $galleryWidth;
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
		$dest_width = $previewWidth;;
		$dest_height = "";
	}
	# Type 2, gallery
	if ($type == 2) {
		# Add 'gallery' to thumb directory base.
		$thumb_dir.= "gallery/";
		# If the directory doesn't exist, make it. (This is the only part that really requires php5 for the recursive function)
		if (!is_dir($scriptPath . "cache/" . $dir . "gallery/")) mkdir($scriptPath . "cache/" . $dir . "gallery/", 0755, true);
		$dest_width = $galleryWidth;
		$dest_height = "";
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
	$command.= " -quality $quality -size $resize -resize $resize +profile '*' $input $output";
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
function getFileLocation($file) {
    @$exif = exif_read_data($file);
    if (!empty($exif['GPSLatitude']) && !empty($exif['GPSLongitude'])) {
        if (is_array($exif['GPSLatitude'])) {
            $latitude=dms2decimal($exif['GPSLatitude'][0],$exif['GPSLatitude'][1],$exif['GPSLatitude'][2]);
        } else {
            $latitude=$exif['GPSLatitude'];
        }
        if (strtolower($exif['GPSLatitudeRef'][0])=="s") {
            $latitude=$latitude*-1;
        }
        if (is_array($exif['GPSLongitude'])) {
            $longitude=dms2decimal($exif['GPSLongitude'][0],$exif['GPSLongitude'][1],$exif['GPSLongitude'][2]);
        } else {
            $longitude=$exif['GPSLongitude'];
        }
        if (strtolower($exif['GPSLongitudeRef'][0])=="w") {
            $longitude=$longitude*-1;
        }
        $location=$longitude.",".$latitude;
    } else {
        $location=null;
    }
    return $location;
}
function dms2decimal($deg, $min, $sec) {
    $deg=explode("/",$deg);
    $min=explode("/",$min);
    $sec=explode("/",$sec);
    if (count($deg)==2) {
        $deg=$deg[0]/$deg[1];
    } else {
        $deg=$deg[0];
    }
    if (count($min)==2) {
        $min=$min[0]/$min[1];
    } else {
        $min=$min[0];
    }
    if (count($sec)==2) {
        $sec=$sec[0]/$sec[1];
    } else {
        $sec=$sec[0];
    }
    return $deg+$min/60+$sec/3600;;
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
# Ajax loading GIF.
function printAjaxGIF() {
	echo "data:image/gif,GIF89a%20%00%20%00%F3%00%00%FF%FF%FF%00%00%00%C6%C6%C6%84%84%84%B6%B6%B6%9A%9A%9A666VVV%D8%D8%D8%E4%E4%E4%BC%BC%BC%1E%1E%1E%04%04%04%00%00%00%00%00%00%00%00%00!%FF%0BNETSCAPE2.0%03%01%00%00%00!%FE%1ACreated%20with%20ajaxload.info%00!%F9%04%09%0A%00%00%00%2C%00%00%00%00%20%00%20%00%00%04%E7%10%C8Iia%A5%EA%CD%E7bK%85%24%9DF%0C%15%A3R%02A%94T%B2%2C%07%A52S%E2*05%2F%2F%C9m%A2p!z%93%C1%CC0%19%02%10%3B%24%C50C%01%9C.%02I*!%FCHC(A%40%11o%01%04%83!39T5%BA%5C%D18)%A8%0D%87%A0%60%C1%EE%B4%B2d%14%07wxG%3DY%04%0Ag%14%04%83wHb%86%1Dv%06A%3D%920%09V%5C%9C%5C%88%3B%09%02%05%03%A4%A5%9C%9F%3B%A5%AA%9BH%A8%8A%A2%AB%AC%9D%B3%1D%980%B6%B5t%25%91Hs%89%8BrY%3CH%7F.%81%13%C5%89%B7%96%09%BE%92b%BF%13Z%1Ab%C7OEg%3A%04%98%7FGY%5D.%C0%3D%DAA%DFOQ%9Cs%86%E6%00%EA%5Cb%C3h.9%EC%3Dsg%F5%7F%9Ec%8C%F3e%E2%1D%12%D8*%12%8F%D6%86f%007D%00%00!%F9%04%09%0A%00%00%00%2C%00%00%00%00%20%00%20%00%00%04%EA%10%C8IiY%A4%EA%CD%A7YF5%14%9DF%90%D4%A2R%06%C3%94Tb%18G%BAJ%85%BB%C0%EC%ACL%AA%9Dd%E1%1A%F0%26%85Ymx%1B%E8%8E%94%C3%0C%15%04%20%5C%0C%14%14%40%98%19%12%80%EA%C1%15%9A%20%08%04%B0%041%18%FC%26R%83%B1%92%13H%12%0A4%011Q%B4%DB%7CV%06%19%25%02z%04v%14%04%7F%23j0%87%0A%8E%14l%8CGg%7B0~%03%89%3C%81%3C%09%84%5B%A2%5B%87h%91x%A5%A1G%A9%04%0Ay%A9%A2%A9%A7%AF%87%A3%B6%96%5B%9E0%97%BA%1B%BCG%B4%91%1B%A8%A6P%86z%9C%12%C7h%C9%BE%A1%C4%98kz%C2%12i%1A%97%C9%08y%8E%A0%D1%CAh%7Cz%D5h%92G%DD%84%E2V%C5%A2%AF%81%E9%00%ED%B9%EB%5Ch%13%E7%5B%AF%8E%EF%00%C7%A4%88%8A%F5%26%95%2B%91%A0W%9E7%B78%E0%19%C8!%02%00!%F9%04%09%0A%00%00%00%2C%00%00%00%00%20%00%20%00%00%04%EE%10%C8I)1%A4%EA%CD%E71G5d%5D%85(%95%A1R%C7%B2%94T2%8C%94jL%84%7B%C3%D3%3C%20%13%5B%D05%E0M%14%BE%E0%0A0%D0%19)%85%19%0A%20L%B8%16%A4I%02%86%F0m%85%CDE%A8%C7%60%14%B4%04%05p%12%A5U%0A%0B%81%04%5Ef%14%25%08%82%5E%B1%05%C8%E4%1D%02%06u%0C%3B%13%02zz%02%7D0%84X%1B%09%0A%89S0%03ew%1Dy%04k%3C%07%9C%25%09%9FO%A3%A3%89%93%09%91%A6z%A4%AA%7B%92%AD%AC%AA%7C%A9%AA%A4%B6%A3%A2%25%B9%1C%9A%BB%14%BDF%AFi%8C1%C2%940%88%89%80%87%A6%CB%BCY%B4%9B%13%9A%C38%C4x%8A%BF%92%8C%09z%9F%C9%40%89%12%89%D7%3C%DD%AB%00%E2%00%9A%C7%C1%DE%E3%E8%00%AF%BE%1B%EC8%F1%E7Y%3C%AF%8C%EA%12%C9%A5%088%F3%87%A7%5C%87P%15%24%B5%BB%A5!%92%C1%0D%11%00%00!%F9%04%09%0A%00%00%00%2C%00%00%00%00%20%00%20%00%00%04%E7%10%C8I%A9%18%A2%EA%CDg%19EU%10%9D%96%20%D5%A0R%87a%94TB%10%D9%A4%0E%13%E1%BEp%3E'%B6%95%A4e%08%F5%24%88%99%22%88%03%14%5C%87%23E1C%01n%80%C4%8E%14%83%C9~%D7%D5%00J%19%2C%16%DC%2CAa%95%08%AA%1A%9DUw%5E4%01I%25P%DD%DE%08%0Eu%0BQ%1633%02%7B0%81%06i1T%85G%05gw%1Cy%7D%25%03%88%25'R%9C%9D%13%0C%A0%A1%0B%05%09%8E%85%8C%3D%A1%AA%0C%A6%A7%9D%AB%0C%A3%A5%A73%9E%B6G%96%25%B9%94p%BA%BD0%A6%0A%99%13%B3JRo%855%13%C8%860I%C4%A6myk%88%04%C3x%CD%13%08T%88_%7D%C8(%8F%00%85%D7%5E%E2%E2yK%9D%8Es%B5%12%EC%9C%E9%3Ei_%A8%25%8E%D5%EEn%FA%3D%D9%12%E2%DA%CAq%D84e%CD-M%C2%A4D%00%00!%F9%04%09%0A%00%00%00%2C%00%00%00%00%20%00%20%00%00%04%EE%10%C8I)*%A8%EA%CD')E%A5d%5D%95%90%13%18%A6%C3PR%09A%08%94%3A!%AD%FBzr%92%82%93%9Cbw%93%0D%256%80%22G%A4(d%24%5B%22%87%92%F8J%B1%1E%C0Fh%AD%90%06%03a%12%1BQ%04P%8D%60p%25%1C%C2%86%2FBFP%5CcU%0D%E2%0D%3FT%D0t%02W%2Fp%06%07G%26OtD%05a_%1Cs%04y%1DlD'M%98%99%13%0B%9C%9Dq%09%8Atc%98%9D%A5%0B%A1%A2%99%A6%0Bb%A0%A22%9A%B1D%03%93%90M%03%0C%0C%3A%1D%91%B5%1A%B9%B9%0B%8Fd%A1%88%25%02%06%C0%0C%06%7F%A24%25s)%0B%C0%BB%91u%83%04%83E3%14%B8%0C%A3%00YU%80%19%8B%00t%DA%96%E6%E6%91%C6D%8A%24%E6JiM%ED%3C%E0Y%E0%3B%8A%D8%B0%13%80%98d%3C%93%20O%02%82tX%F2%3Cq'%2B%11B%0E%11%00%00!%F9%04%09%0A%00%00%00%2C%00%00%00%00%20%00%20%00%00%04%F3%10%C8IiR%A9%EA%CD%A7%22J%25%20%9D%96%90%14%A1%A6EQZ%AA%90%AE%12%D2%12%EFLd%92%8A%F7-Y%AE%A6%0A%F5h%82%96k%E8Q%A1%7C%80%84%125%E1u%12%BE%0C4Y%F8I%14%83%01%15%AB%A0%02%04N%0DbW%0B%87%8D%80u%91%875%9B%0A%EE%C1r%82%F6%09%AC%25yb%1B%17%3E%5E%25%02o%2Frv%1Dl9'L%92%93%13%06%96%97%07%3B%1F%86%879%97%9F%06%9B%9C%93%A0%06%99%85%A3%94%AA9%03%80%25%8D%1C%05%0B%0B%03%8Bi9%B3%B3%06%9D%12%A8%20C%08%07%B9%0B%07%22%86B%1D%03%0CB%04%06%B9%B5Ds%13%8F%14%07%0C%0C%06%14%03%CE%5EX%04f%7D%24P%09%D7%0C%7BL%DE%3FP%00%CA%0C%0B%94%9BO4%00%0B%D7%D0%C0E%D3%F3%04%E5%92%9BV%EB%24%18%B8%E6%CA%11%01d%00%02J%D0%23)%12%85%0FpV%11%C2%C0%24%02%00!%F9%04%09%0A%00%00%00%2C%00%00%00%00%20%00%20%00%00%04%EB%10%C8IiR%A9%EA%CD%A7%22J%85d%5D%95%20%15%A1R%C2ZN%89*P*%01%AB%E1%3B%D5%24P%7B*%94N%82%C0%ED%5CE%D0%90%F2!%08%7F1UO2%DDD%09%99_r6I%F6b%0A%A1%A4%E5%D4%C4H%97%9A8%09B%97%3B%09%B2%AC%22'%08%AA%9CZ%DB%DAt%BD%92b%1C%80K%23C'K%88%89%13%03%8C%8D%05w%7D%3F%88%8D%94%03%91%92K%95%03%8Fiz6%8A%A0%3A%03x%82K%04%06%06%05%7FAC%A8%A8%07%9F%26%7D9%7F%07%AE%06%07tz%5C%1D%05%0B%5C%04%B6%A8%AAD5%18%3Bx%03%0B%0B%B9%13%05%A8%B1Q%81d(%0C%D6%00%09%CB%0B%B1KW%12%D6%0C%12%CA%0B%06%8AMB%E0%13%06%CB%03%88I%B4%E9%12%04%DA%88M%3D%F1%12%07%CB%A4%1Bs%13%F8%E2%B8%BD8Da%01%83%05%A1J%14%60%40LG%04%00!%F9%04%09%0A%00%00%00%2C%00%00%00%00%20%00%20%00%00%04%EF%10%C8IiR%A9%EA%CD%A7%22J%85d%5D%95%20%15%A1R%C2ZN%89*P*%01%AB%E1%3B%D5%24P%7B*%94N%82%C0%ED%5CE%D0%90%F2!%08%7F1UO2%DDD%09%99_r6I%F6b%0A%A1%A4%E5%D4%C4H%97%9A8%09B%97%3B%09%B2%AC%22'%08%AA%9CZ%DB%DAt%BD%92b%1C%80K%23C'K%88%89Gz%18iz6%88%8F8%7Dz%89%92%8D%94~%8A%9B%25X%84K%02%03%039%1D%83%3A%A2%A8%810%7D%A4%25%09%05%A8%03%05tz%5C%1D%04%06B%08%B1%A4l%18c%0C%03%1A%03%06%06%07L%A2bQ%81%06%0C%0C%C7%0B%D1%00%09%C5%06%90%88%05%CE%0C%19%D1%0B%12%05%C5%C7%89%0B%CE%C2%00%DD%13%07%C5%B3K%03%CE%DE%12%E8%12%B8%C5%88%E4%0C%EC%E7%D2%E9%C5x%1C%CE%06(%C8%9BP%E0%9A%0EX%15%0C%2C%08%C8%E9%D6%82%7C%2F%22%00%00!%F9%04%09%0A%00%00%00%2C%00%00%00%00%20%00%20%00%00%04%F0%10%C8IiR%A9%EA%CD%A7%22J%85d%5D%95%20%15%A1R%C2ZN%89*P*%01%AB%E1%3B%D5%24P%7B*%94N%82%C0%ED%5CE%D0%90%F2!%08%7F1UO2%DDD%09%99_r6I%F6b%0A%A1%A4%E5%D4%C4H%97%9A8%09B%97%3B%09%B2%AC%22'%08%AA%9CZ%DB%DAt%BD%92b%1C%80K%23C'K%88%89Gz%18iz6%88%8F8%7Dz%89%92%8D%94~%8A%9B%25%85%3A%08%07%84A%2F%03%0C%0C%03C%7D%18%1B%04%0B%A6%A6%86u%5C%13%06%AF%0C%06%B3%1B%02%03h%7Db%A5%A6%0B%05D%C2%1A%04%03%03%C3%5D%1F%3D%05%A6%A8%13%07%0B%0B%A8%06%D6%00%09%C8%03%81V)%D3%0B%19%D6%06%12%0A%DA%8A%06%D3%D0%E2%13%05%C89C%03%D3%E3%12%EBD%E6K%E8%0B%90%00%F5%12%ED%BCK%A6%85%A2u%8D%09%B7%12%C7*%1C00%90S%1E%03%CAtD%00%00!%F9%04%09%0A%00%00%00%2C%00%00%00%00%20%00%20%00%00%04%EB%10%C8IiR%A9%EA%CD%A7%22J%85d%5D%95%20%15%A1R%C2ZN%89*P*%01%AB%E1%3B%D5%24P%7B*%94N%82%C0%ED%5CE%D0%90%F2!%08%7F1UO2%DDD%09%99_r6I%F6b%0A%A1%A4%E5%D4%C4H%97%9A8%09B%97%3B%09%B2%AC%22'%08%AA%9CZ%DB%DAt%BD%92b%1C%80K%23C'K%88%89Gz%18%05%0B%0C%90%91%89z5%0A%91%97%0C%93%94%8D%8F%98%8A%9FC%85%3A%09%03%84A%2F%03%0B%0B%05C%7D%18%1B%04%06%AA%AA%86u%5C%13%07%B3%0B%07%B7%1BEh%7Db%A9%AA%066%00%08%C5%1A%5B%14%08%1F%3D%04%AA%A5%B8%06%06%A5%03%D7%00Wx%26)%D4%06%19%D7%D2I9%88%07%D4%AC%00%E1%40oC%05%D4%07%13%EAT%3FK%DE%C6%E9%D8%13d%DB%1B%EF%14%F2%5D%F8%C1B7%A1%C0%00%82%A06%08%18%D0%ABD%04%00!%F9%04%09%0A%00%00%00%2C%00%00%00%00%20%00%20%00%00%04%E8%10%C8IiR%A9%EA%CD%A7%22J%85d%5D%95%20%15%A1R%C2ZN%89*P*%01%AB%E1%3B%D5%24P%7B*%94N%82%C0%ED%5CE%D0%90%F2!%08%7F1UO2%DDD%09%99_r6I%F6%08%0C%C6%80%15%D4%C4H%97%9A%100%083%16%05%AA%B3%94h%D5%B8%13%83%9Ba%C0%97j%20U%12%05%0B%7BCIk%1CmbK%23%87cK%91%92%12%808%09%84%7Ba%92%958%99n%9B%95%18%98%99%93%A5%87%82V%90%3A%88%2F%05%06%06q%3AM%81%1B%0A%07%AF%AFCu%80~%00%B7%B8%07%89%1AEh%B3k%AE%AF%076%00%09%03%BD%00%5B%14%08%1F_%AF%B1%83%03%036P%3C%2FU%08%D9%03YHF%92%E19%3F%12M%C2%25%0A%E1G%CB%CC%E9C%E1k%F3%00v%A8%1B%D9%F1%3E.%5D%FA6%14%A9%F0!%87)%0E%17%02V%88%00%00!%F9%04%09%0A%00%00%00%2C%00%00%00%00%20%00%20%00%00%04%F0%10%C8IiR%A9%EA%CD%A7%22J%85d%5DU%0C%15%A1R%C2ZN%09%C3%18%94J%C0j%F8N2sK6%8F%0A%B1%9B%0Cd%8BI%10%80%C8%15)%0B%19%0A%10L%D8H%B0W%A1G%0C6%09%02%CA%17KX%18%A6%12%83%EC%A0%B1%92.6%A2d%B0%A8%1B~%02%06z%93h%D9%C2%14%07uu%07r%2F6%20X5%06%83I%3B_%86%1Ct%0B%05O%23E%09%7BO%9B%9B%889V%07%06%A2%A3%9C%9E9%A3%A84%9D%9E%18%04%A1%A9%9C%B1%9B%97%3BV%96C%2F%0A%03%03%80%B96%18%1B%08%BB%C3%98~*%BD%12'%C3%05%8A%1AMo%1F%B8%12%BA%BB%05%80n%CE%C7b%1FX%C2%03%3A%12~%5D%2BV*%CDm%15%E5%04%19K_%E0O%D1rK%00%F1%B3N%40.%00%EA%9B%D1d%F9%00~%CEq%D0%A6%E4%1F%13%81%1C%12D%A2%07B%D6%8B%0B%085D%00%00%3B%00%00%00%00%00%00%00%00%00";
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
		printheader("Permissions Error");
		echo "$scriptPath is not writable by $who. Can not create required .htaccess file.";
		printfooter();
		exit();
	}
	@file_put_contents($scriptPath . ".htaccess", $htaccess);
	header("Location: $baseURL");
	exit("Unknown Error. Please press refresh.");
}
?>
