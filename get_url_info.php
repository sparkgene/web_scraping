<?php
/**
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @author Jun Ichikawa <jun1ka0@gmail.com>
 * @version 0.2
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
$ccTLD = array('ac','ad','ae','af','ag','ai','al','am','an','ao','aq','ar','as','at','au','aw','az','ba','bb','bd','be','bf','bg','bh','bi','bj','bm','bn','bo','br','bs','bt','bv','bw','by','bz','ca','cc','cd','cf','cg','ch','ci','ck','cl','cm','cn','co','cr','cu','cv','cx','cy','cz','de','dj','dk','dm','do','dz','ec','ee','eg','eh','er','es','et','fi','fj','fk','fm','fo','fr','ga','gd','ge','gf','gg','gh','gi','gl','gm','gn','gp','gq','gr','gs','gt','gu','gw','gy','hk','hm','hn','hr','ht','hu','id','ie','il','im','in','io','iq','ir','is','it','je','jm','jo','jp','ke','kg','kh','ki','km','kn','kp','kr','kw','ky','kz','la','lb','lc','li','lk','lr','ls','lt','lu','lv','ly','ma','mc','md','mg','mh','mk','ml','mm','mn','mo','mp','mq','mr','ms','mt','mu','mv','mw','mx','my','mz','na','nc','ne','nf','ng','ni','nl','no','np','nr','nu','nz','om','pa','pe','pf','pg','ph','pk','pl','pm','pn','pr','ps','pt','pw','py','qa','re','ro','ru','rw','sa','sb','sc','sd','se','sg','sh','si','sj','sk','sl','sm','sn','so','sr','st','sv','sy','sz','tc','td','tf','tg','th','tj','tk','tm','tn','to','tp','tr','tt','tv','tw','tz','ua','ug','uk','um','us','uy','uz','va','vc','ve','vg','vi','vn','vu','wf','ws','ye','yt','yu','za','zm','zw');

function flush_result($msg){
	header("Cache-Control: no-cache, must-revalidate");
	header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");
	header("Content-Type: application/json; charset=utf-8");
	echo json_encode($msg);
	exit(0);
}

function add_image($img){
	global $image_array;

	if( ! preg_match('/^(https?)(:\/\/[-_.!~*\'()a-zA-Z0-9;\/?:\@&=+\$,%#]+(\.jpg|\.jpeg|\.png|\.bmp)(\?.*)?)$/', $img) ){
		// if it is not image file
		return;
	}
	if( in_array($img, $image_array) ){
		return;
	}

	$image_array[] = $img;
}

function is_subdomain($base_host_name, $img_host){
	global $ccTLD;
	
	$img_host_list = explode('.', $img_host);
	if( in_array($img_host_list[count($img_host_list)-1], $ccTLD) ){
		// "ccTLD" check the third word
		if( $img_host_list[count($img_host_list)-3] === $base_host_name){
			return TRUE;
		}
	}
	else{
		// This is not "ccTLD" check the second word
		if( $img_host_list[count($img_host_list)-2] === $base_host_name){
			return TRUE;
		}
	}
	return FALSE;
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
$base_host_list = explode('.', $url_info['host']);
$base_host_name = "";
if( in_array($base_host_list[count($base_host_list)-1], $ccTLD) ){
	$base_host_name = $base_host_list[count($base_host_list)-3];
}
else{
	$base_host_name = $base_host_list[count($base_host_list)-2];
}

foreach( $html->find( 'img' ) as $img ){
	if( ! isset( $img->src ) )
		continue;

	if( preg_match('/^(https?)(:\/\/[-_.!~*\'()a-zA-Z0-9;\/?:\@&=+\$,%#]+)$/', $img->src) ){
		$url_parts = parse_url($img->src);
		if( $url_info['host'] == $url_parts['host'] ){
			// if the host matches
			add_image( $img->src );
		}
		elseif( is_subdomain($base_host_name, $url_parts['host']) ){
			// if it is sub domain
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
$result["host"] = $url_info['host'];
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
