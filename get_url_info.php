<?php
/**
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @author Jun Ichikawa <jun1ka0@gmail.com>
 * @version 0.1
*/

include_once('simple_html_dom.php');

function flush_result($msg){
	header("Content-Type: application/json; charset=utf-8");
	echo json_encode($msg);
	exit(0);
}


$target_url = "";
if( strtoupper($_SERVER['REQUEST_METHOD']) === "POST" ){
	$target_url = $_POST["target"]; 
}
else{
	$target_url = $_GET["target"];
}

// check parameter
if( is_null($target_url) ){
	flush_result( "" );
}

if ( ! preg_match('/^(https?)(:\/\/[-_.!~*\'()a-zA-Z0-9;\/?:\@&=+\$,%#]+)$/', $target_url) ){
	flush_result( "" );
}

// get html
$html = file_get_html( $target_url );
if( is_null($html) ){
	flush_result( "" );
}

$url_info = parse_url($target_url);

// initialize variable
$ogp_title = "";
$ogp_site_name = "";
$ogp_description = "";
$ogp_url = $target_url;
$description = "";
$base_url = "";
$host_url = "";
$image_array = array();

foreach($html->find('base') as $element){
	$base_url = $element->href;
	$host_url = $element->href;
}
if( $base_url === "" ){
	$host_url = $url_info['scheme']."://".$url_info['host']."/";
	$base_url = $host_url;
	if( !empty($url_info['path']) && $url_info['path'] != "/" ){
		$base_url = $base_url.$url_info['path']."/";
	}
}

// find by meta tag
foreach($html->find('meta') as $element){
	if( isset($element->property) && preg_match("/^og:/", $element->property) ){
		// get info from "Open Graph Protocol" meta tags
		$values = explode(":", $element->property);
		$content = isset($element->content) ? $element->content : NULL;
		if( count($values) === 2 && !is_null($content) ){
			switch(strtolower($values[1])){
				case "description":
					$ogp_description = $content;
					break;
				case "site_name":
					$ogp_site_name = $content;
					break;
				case "title":
					$ogp_title = $content;
					break;
				case "url":
					$ogp_url = $content;
					break;
				case "image":
					$image_array[] = $content;
					break;
			}
		}
	}
	else if( isset($element->name) && isset($element->content) ){
		switch(strtolower($element->name)){
			case "description":
				$description = $element->content;
				break;
		}
	}
}

if( $ogp_title === "" ){
	// if ogp title is null find from title tag
	$elements = $html->find( 'title' );
	foreach( $html->find( 'title' ) as $element )
	{
		$ogp_title = $element->plaintext;
		break;
		
	}
}

// get images
foreach( $html->find( 'img' ) as $img ){
	if( ! isset( $img->src ) )
		continue;

	if( preg_match('/^(https?)(:\/\/[-_.!~*\'()a-zA-Z0-9;\/?:\@&=+\$,%#]+)$/', $img->src) ){
		if( ! preg_match('/^(https?)(:\/\/[-_.!~*\'()a-zA-Z0-9;\/?:\@&=+\$,%#]+(jpg|jpeg|gif|png|bmp))$/', $img->src) ){
			// if it is not image file
			continue;
		}
		$url_parts = parse_url($img->src);
		if( $url_info['host'] == $url_parts['host'] ){
			// if the host matches
			$image_array[] = $img->src;
		}
	}
	else{
		if( substr($img->src, 0,1) == "/" ){
			// if not relative path
			$image_array[] = $host_url.substr($img->src, 1);
		}
		else{
			$image_array[] = $base_url.$img->src;
		}
	}
}

$html->clear();

$result = array();
$result["url"] = $ogp_url;
$result["title"] = empty($ogp_title) ? (empty($ogp_site_name) ? $ogp_url : $ogp_site_name) : $ogp_title;
$result["description"] = empty($ogp_description) ? $description : $ogp_description;
$result["imgs"] = $image_array;

flush_result( $result );
