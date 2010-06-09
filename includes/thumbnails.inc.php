<?php
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