<?php
// RSS Functions
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