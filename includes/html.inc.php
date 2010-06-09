<?php
/* HTML Header & Footer Functions */
# Print HTML Header. If you use your own header and footer, the CSS needs to be added to your CSS
# file (unless you want to use your own CSS);
function html_header($title = "") {
	global $dir, $scriptPath, $baseURL, $galleryWidth, $previewWidth;
	$GLOBALS['time_start'] = microtime(true);
?>
<!DOCTYPE html>
<html>
<head>
	<meta http-equiv="content-type" content="text/html; charset=utf-8">
	<title><?=$title?></title>
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