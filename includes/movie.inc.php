<?php
// Movie Functions

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

// Get information about a movie file.
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