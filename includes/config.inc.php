<?
/* Config */
# Define the binary/command used to create thumbnails. Image Magick and Graphics Magick both work ('convert' or 'gm', respectively.)
$thumbBinary = "/home/commonlibraries/bin/gm";
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