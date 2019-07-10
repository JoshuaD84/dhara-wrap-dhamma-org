<?php
/*
Plugin Name: wrap-dhamma-org
Description: retrieves, re-formats, and emits HTML for selected pages from www.dhamma.org 
Version: 3.0
Authors: Joshua Hartwell <JHartwell@gmail.com> & Jeremy Dunn <jeremy.j.dunn@gmail.com>

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, version 3.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program.  If not, see <https://www.gnu.org/licenses/>
*/

function wrap_dhamma( $page ) {
	// validate page
	switch ( $page ) {
		case 'vipassana':
		case 'code':
		case 'goenka':
		case 'art':
		case 'qanda':
		case 'dscode':
        case 'osguide':
        case 'privacy':
			$url = 'http://www.dhamma.org/en/' . $page . "?raw";
			$text_to_output = pull_page( $url );
			break;

		case 'video':
			$url = 'http://video.server.dhamma.org/video/';
			$text_to_output = pull_video_page( $url );
			break;

		default:
			die ( "invalid page '".$page."'" );
	}

	// emit the required comment
	echo '<!-- ' . $url . ' has been dynamically reformatted on ' . date("D M  j G:i s Y T") . '. -->';

	// emit the reformatted page
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
    $LOCAL_URLS = array(
	   'art' => '/about/art-of-living/',
	   'goenka' => '/about/goenka/',
	   'vipassana' => '/about/vipassana/',
	   '/' => '',
    );

    foreach ( $LOCAL_URLS as $from => $to ) {
	   $raw = str_replace('<a href="' . $from . '">', '<a href="' . get_option('home') . $to . '">', $raw);
	   $raw = str_replace("<a href='" . $from . "'>", '<a href="' . get_option('home') . $to . '">', $raw);
	}

	$raw = preg_replace("#<a href=[\"']/?code/?[\"']>#", '<a href="' . get_option('home') . '/courses/code/">', $raw);
	$raw = str_replace("<a href='/bycountry/'>", "<a target=\"_blank\" href=\"http://courses.dhamma.org/en-US/schedules/schdhara\">", $raw);
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

    $dir = plugin_dir_path( __FILE__ );  
    $raw = str_replace ( 'src="https://www.dhamma.org/assets/sng/sng-f01f4d6595afa4ab14edced074a7e45c.gif"', 'id="goenka-image" src="/wp-content/plugins/wrap-dhamma-org/goenka.png"', $raw );
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
