<?php
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
