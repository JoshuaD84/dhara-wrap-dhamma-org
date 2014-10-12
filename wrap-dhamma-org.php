<?php
/*
Plugin Name: wrap-dhamma-org
Description: retrieves, re-formats, and emits HTML for selected pages from www.dhamma.org
Version: 2.0
Revised: 2014/10/12 - JDH
Author: Jeremy Dunn <jeremy.j.dunn@gmail.com>, 
Contributor: Joshua Hartwell <JHartwell@gmail.com>
*/

function wrap_dhamma( $page ) {
	// validate page
	switch ( $page ) {
		case 'vipassana' :
		case 'code' :
		case 'goenka' :
		case 'art' :
		case 'qanda' :
		case 'schedules/schdhara' :
		case 'dscode' :
			$url = 'http://www.dhamma.org/en/' . $page . "?raw";
			$text_to_output = pull_page( $url );
			break;
			
		case 'video' :
			$url = 'http://video.server.dhamma.org/video/';
			$text_to_output = pull_video_page( $url );
			break;
			
		default:
			die ( "invalid page '".$page."'" );
			
	}
	
	// emit the required comment
	echo '<!-- ' . $url . ' has been dynamically reformatted on ' . date("D M  j G:i s Y T") . '. -->';
	// emit the reformatted page between center tags
	echo $text_to_output;
	echo '<!-- end dynamically generated content.-->';
	// we're done
	
}

function pull_page ( $url ) {

	$raw = file_get_contents ( $url );
	
	$raw = fixURLs ( $raw );
	$raw = stripH1 ( $raw );
	$raw = stripHR ( $raw );
	$raw = changeTag ( $raw, "h3", "h2" );
	$raw = changeTag ( $raw, "h4", "h3" );
	$raw = fixGoenkaImages ( $raw );
	
	return $raw;
}



function pull_video_page ( $url ) {
	
	$raw = file_get_contents ( $url );
	
	$raw = getBodyContent ( $raw );
		
	$raw = stripH1 ( $raw );
	$raw = stripHR ( $raw );
	$raw = stripTableTags ( $raw );
	$raw = stripExessVideoLineBreaks ( $raw );
	$raw = fixVideoURLS ( $raw );
	$raw = fixBlueBallImages ( $raw );
	$raw = stripHomeLink ( $raw );
	
	return $raw;

}



function fixURLs ( $raw ) {
	$raw = str_replace('<a href="art">', '<a href="' . get_option('home') . '/about-vipassana/art/">', $raw);
	$raw = str_replace("<a href=\"goenka\">", "<a href=\"" . get_option('home') . "/about-vipassana/goenka/\">", $raw);
	$raw = preg_replace("#<a href=[\"']/?code/?[\"']>#", "<a href=\"" . get_option('home') . "/about-vipassana/code/\">", $raw);
	$raw = str_replace("<a href=\"vipassana\">", "<a href=\"" . get_option('home') . "/about-vipassana/vipassana/\">", $raw);
	$raw = str_replace("<a href='vipassana'>", "<a href=\"" . get_option('home') . "/about-vipassana/vipassana/\">", $raw);
	$raw = str_replace("<a href='/bycountry/'>", "<a target=\"_blank\" href=\"http://courses.dhamma.org/en-US/schedules/schdhara\">", $raw);
	$raw = str_replace("<a href='/'>", "<a href=\"" . get_option('home') . "\">", $raw);
	$raw = str_replace("<a href='/docs/core/code-en.pdf'>here</a>", "<a href='http://www.dhamma.org/en/docs/core/code-en.pdf'>here</a>", $raw);
	return $raw;
}

function fixVideoURLS ( $raw ) {
	$raw = preg_replace( "#<a href='./intro/#si", "<a href='http://video.server.dhamma.org/video/intro/", $raw );
	return $raw;
}

function stripH1( $raw ) {
	return preg_replace('@<h1[^>]*?>.*?<\/h1>@si', '', $raw); //This isn't a great solution, not very dynamic, but it gets the job done. 
}

function stripHR ( $raw ) {
	return preg_replace("@<hr.*?>@si", '', $raw); 
}

function changeTag ( $source, $oldTag, $newTag ) {
	$source = preg_replace( "@<{$oldTag}>@si", "<{$newTag}>", $source ); 
	$source = preg_replace( "@</{$oldTag}>@si", "</{$newTag}>", $source ); 
	return $source;
}

function fixGoenkaImages ( $raw ) {

	//Make the Goenkaji images work - JDH 10/12/2014
	$raw = preg_replace( '#/images/sng/#si', 'https://www.dhamma.org/images/sng/', $raw );
		
	//Make the goenka images inline - JDH 10/12/2014
	$raw = str_replace('class="www-float-right-bottom"', "align='right'", $raw);
	$raw = str_replace('<img alt="S. N. Goenka at U.N."', '<img alt="S. N. Goenka at U.N." style="display: block; margin-left: auto; margin-right: auto;"', $raw);
	$raw = str_replace('Photo courtesy Beliefnet, Inc.', '<p style="text-align:center">Photo courtesy Beliefnet, Inc.</p>', $raw);
	
	return $raw;
}

function stripTableTags ( $raw ) {
	$raw = preg_replace("@</*?table.*?>@si", '', $raw); 
	$raw = preg_replace("@</*?tr.*?>@si", '', $raw); 
	$raw = preg_replace("@</*?td.*?>@si", '', $raw);
	return $raw;
	
}

function stripExessVideoLineBreaks ( $raw ) {
	$raw = preg_replace( "@\n@si", '', $raw ); 
	$raw = preg_replace( "@[ ]+@", ' ', $raw ); 
	
	return $raw;
}

function getBodyContent ( $raw ) {
	// take HTML between <body> and </body>
	$bodypos = strpos($raw, '<body>');
	$nohead = substr($raw, $bodypos + 6); // strip <body> tag as well
	$bodyendpos = strpos($nohead, '</body>');
	$raw = substr($nohead, 1, ($bodyendpos -1));
	
	return $raw;
}

function fixBlueBallImages ( $raw ) {
	$raw = preg_replace ( '#<IMG SRC="/images/icons/blueball.gif">#si', '', $raw );
	
	return $raw;
}

function stripHomeLink ( $raw ) {
	$raw = preg_replace ( "#Download a free copy of <a href='http://www.real.com'>RealPlayer</a>.#si", "", $raw );
	$raw = preg_replace ( "#<br/> <a href='http://www.dhamma.org/'><img style='border:0' src='/images/icons/home.gif' alt=' '></A>#si", "", $raw );

	return $raw;

}
	
?>