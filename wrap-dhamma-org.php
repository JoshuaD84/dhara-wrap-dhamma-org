<?php
/*
Plugin Name: wrap-dhamma-org
Plugin URI: 
Description:  retrieves, re-formats, and emits HTML for selected pages from www.dhamma.org
Version: 1.2 rev 30-Jun-2013 
Author: Jeremy Dunn <jeremy.j.dunn@gmail.com>, 
Contributor: Joshua Hartwell <JHartwell@gmail.com>
Author URI: 
*/

function wrap_dhamma($page) {
	// validate page
	switch ($page) {
		case 'vipassana' :
		case 'code' :
		case 'goenka' :
		case 'art' :
		case 'qanda' :
		case 'schedules/schdhara' :
		case 'dscode' :
		case 'video' :
			break;
		default:
			die ("invalid page '".$page."'");
	}

	$author = 'Jeremy Dunn (jeremy.j.dunn@gmail.com)';

	if($page == 'video') {
        $url = 'http://video.server.dhamma.org/video/';
    } else {
	
	    // form the URL
	    $base = 'http://www.dhamma.org/en/';
	    $suffix = '.shtml';
	    $url = $base . $page . $suffix;
    }

	// get the page, which is a full HTML document
	// JJD 4/21/13 php.ini on dreamhost has changed.  no longer allowed to include remote URL
	//$handle = fopen($url,"r");
	//$raw = stream_get_contents($handle);
    $raw = file_get_contents($url); // JJD 4/27/13 simpler
 	//fclose($handle);	
	//ob_start();
	//include $url;
	//$raw = ob_get_contents();
	//ob_end_clean();


	// take HTML between <body> and </body>
	$bodypos = strpos($raw, '<body>');
	$nohead = substr($raw, $bodypos + 6); // strip <body> tag as well
	$bodyendpos = strpos($nohead, '</body>');
	$notail = substr($nohead, 1, ($bodyendpos -1));

	// strip .shtml suffixes for anchors within file
	$noshtml = str_replace($suffix, '', $notail);

	
	// remove language suffix from the path of any anchors in file
	// this leaves anchors we can emulate in WP
	$language = 'en/';
	$final = str_replace($language, '', $noshtml);
	
	//Fix all URLS - JDH 6/29/2013
	//Try to move towards using preg_replace and more dynamic matching algorithms, for some durability.  But this works today. 
	$final = str_replace("<a href=\"art\">", "<a href=\"" . get_option('home') . "/about-vipassana/art/\">", $final);
	$final = str_replace("<a href=\"goenka\">", "<a href=\"" . get_option('home') . "/about-vipassana/goenka/\">", $final);
	$final = preg_replace("#<a href=[\"']/?code/?[\"']>#", "<a href=\"" . get_option('home') . "/about-vipassana/code/\">", $final);
	$final = str_replace("<a href=\"vipassana\">", "<a href=\"" . get_option('home') . "/about-vipassana/vipassana/\">", $final);
	$final = str_replace("<a href='vipassana'>", "<a href=\"" . get_option('home') . "/about-vipassana/vipassana/\">", $final);
	$final = str_replace("<a href='/bycountry/'>", "<a target=\"_blank\" href=\"http://courses.dhamma.org/en-US/schedules/schdhara\">", $final);
	$final = str_replace("<a href='/'>", "<a href=\"" . get_option('home') . "\">", $final);
	$final = str_replace("<a href='/docs/core/code-en.pdf'>here</a>", "<a href='http://www.dhamma.org/en/docs/core/code-en.pdf'>here</a>", $final);
	
	//Strip out some images, maybe keep these images in the page? - JDH 6/30/2013
	$final = str_replace("<img src=\"/images/icons/aniglobe.gif\" alt=' '>", "", $final);
	$final = str_replace("<img src=\"/images/icons/bodhi.gif\" alt=' '>", "", $final);

	
	//Strip horizontal rules - JDH 6/29/2013
	$final = str_replace("<hr class='www-thinrule'>", "", $final);
	$final = str_replace("<hr class='thinrule'>", "", $final);
	$final = str_replace("<hr class='ninty'>", "", $final);
	
	
	//Strip <h1> tags - JDH 6/29/2013
	$final = preg_replace('@<h1[^>]*?>.*?<\/h1>@si', '', $final); //This isn't a great solution, not very dynamic, but it gets the job done. 


	//Strip home link - JDH 6/29/2013
	$final = preg_replace('#<a href=\".*\"><img src=\'/images/icons/home.png\' alt=\'Return to the home page\'>.*Home</a>#i', '', $final);
	$final = str_replace("<hr class='www-tarule'>", "", $final);
	
	
	//get rid of newlines which WP changes to <br>'s and creates a narrow page. JDH 6/26/2013 
	$final = preg_replace('/\s+/', ' ', trim($final));
	
	//Make the goenka images inline
	$final = str_replace("<img class='www-float-right-bottom'", "<image align=\"right\"", $final);
	$final = str_replace("<img alt='S. N. Goenka at U.N.'", "<img alt='S. N. Goenka at U.N.' style=\"display: block; margin-left: auto; margin-right: auto;\"",$final);
	
	//Centers the credit text on the second goenka image JDH 6/29/2013
	$final = str_replace("<p> <img alt='S. N. Goenka at U.N.'","<p style=\"text-align: center\"> <img alt='S. N. Goenka at U.N.'", $final);
	
	// emit the required comment
	echo '<!-- ' . $url . ' has been dynamically reformatted on ' . date("D M  j G:i s Y T") . '.';
	echo 'Program maintainer: ' . $author . '-->';
	// emit the reformatted page between center tags
	echo '<center>' . $final . '</center>';
	echo '<!-- end dynamically generated content.-->';
	// we're done
}

?>
