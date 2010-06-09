<?php
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