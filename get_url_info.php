<?php
/**
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @author Jun Ichikawa <jun1ka0@gmail.com>
 * @version 0.1
*/

include_once('simple_html_dom.php');

// initialize variable
$ogp_title = "";
$ogp_site_name = "";
$ogp_description = "";
$ogp_url = "";
$description = "";
$base_url = "";
$host_url = "";
$charset_name = "";
$image_array = array();


function flush_result($msg){
	header("Content-Type: application/json; charset=utf-8");
	echo json_encode($msg);
	exit(0);
}

function add_image($img){
	global $image_array;

	if( ! preg_match('/^(https?)(:\/\/[-_.!~*\'()a-zA-Z0-9;\/?:\@&=+\$,%#]+(jpg|jpeg|gif|png|bmp))$/', $img) ){
		// if it is not image file
		return;
	}
	if( in_array($img, $image_array) ){
		return;
	}

	$image_array[] = $img;
}

function is_valid_text($text){
	if( isset($text) && $text !== "" && (mb_detect_encoding($text, 'UTF-8', true) !== FALSE)){
		return TRUE;
	}
	return FALSE;
}

$target_url = "";
if( strtoupper($_SERVER['REQUEST_METHOD']) === "POST" ){
	$target_url = $_POST["target"]; 
}
else{
	$target_url = $_GET["target"];
}

$ogp_url = $target_url;

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
					$ogp_description = trim($content);
					break;
				case "site_name":
					$ogp_site_name = trim($content);
					break;
				case "title":
					$ogp_title = trim($content);
					break;
				case "url":
					$ogp_url = trim($content);
					break;
				case "image":
					add_image($content);
					break;
			}
		}
	}
	else if( isset($element->name) && isset($element->content) ){
		switch(strtolower($element->name)){
			case "description":
				$description = trim($element->content);
				break;
		}
	}
}

if( $ogp_title === "" ){
	// if ogp title is null find from title tag
	$elements = $html->find( 'title' );
	foreach( $html->find( 'title' ) as $element )
	{
		$ogp_title = trim($element->plaintext);
		break;
		
	}
}

// get images
foreach( $html->find( 'img' ) as $img ){
	if( ! isset( $img->src ) )
		continue;

	if( preg_match('/^(https?)(:\/\/[-_.!~*\'()a-zA-Z0-9;\/?:\@&=+\$,%#]+)$/', $img->src) ){
		$url_parts = parse_url($img->src);
		if( $url_info['host'] == $url_parts['host'] ){
			// if the host matches
			add_image( $img->src );
		}
	}
	else{
		if( substr($img->src, 0,1) == "/" ){
			// if not relative path
			add_image( $host_url.substr($img->src, 1) );
		}
		else{
			add_image( $base_url.$img->src );
		}
	}
}

$result = array();
if( isset($ogp_url) && $ogp_url != "" ){
	$result["url"] = $ogp_url;
	$result["title"] = $ogp_url;
}
else{
	$result["url"] = $target_url;
	$result["title"] = $target_url;
}

$result["title"] = "";
if( is_valid_text($ogp_title) ){
	$result["title"] = $ogp_title;
}
elseif( is_valid_text($ogp_site_name) ){
	$result["title"] = $ogp_site_name;
}

$result["description"] = "";
if( is_valid_text($ogp_description) ){
	$result["description"] = $ogp_description;
}
elseif( is_valid_text($description) ){
	$result["description"] = $description;
}

$result["imgs"] = $image_array;

$html->clear();

flush_result( $result );
