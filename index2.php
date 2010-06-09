<?php
print_r($_SERVER);
die;
# Includes File
foreach (glob("includes/*.php") as $filename) {
    include $filename;
}

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