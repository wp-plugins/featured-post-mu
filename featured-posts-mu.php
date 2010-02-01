<?php
/* 
Plugin Name: Featured Posts WPMU 
Plugin URI: none
Description: Display specific/multiple posts on any place of your site. It creates a tab "Featured Posts wpmu" in "Settings" or "Options" tab
Version: 1.0
Author: mamoun.othman
Author URI: none
*/

/*  Copyright 2008  SAN - w3cgallery.com & Windowshostingpoint.com

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; 
*/
function get_user_details_by_id( $id ) {
	global $wpdb;
	return $wpdb->get_row( $wpdb->prepare("SELECT user_login FROM $wpdb->users WHERE ID = %d", $id) );
}

function ftable_exists($tablename, $database = false) {

    if(!$database) {
        $res = mysql_query("SELECT DATABASE()");
        $database = mysql_result($res, 0);
    }

    $res = mysql_query("
        SELECT COUNT(*) AS count 
        FROM information_schema.tables 
        WHERE table_schema = '$database' 
        AND table_name = '$tablename'
    ");

    return mysql_result($res, 0) == 1;

}

/*function featured_posts_list_sort_by_managers($a, $b)
{
	$managers = array(28, 27, 38, 45, 74, 79);
	
	$blog_id_a = explode(',', $a);
	$blog_id_a = $blog_id_a[1];
	
	
	$blog_id_b = explode(',', $b);
	$blog_id_b = $blog_id_b[1];
	
	if (in_array($blog_id_a, $managers) && in_array($blog_id_b, $managers)) return 0;
	if (in_array($blog_id_a, $managers)) return -1; 
	if (in_array($blog_id_b, $managers)) return 1; 
	return 0;
}*/

function featuredpostsList($before = '<li>', $after = '</li>') {
	global $post, $wpdb, $posts_settings;
	// posts_id from database
	$posts_id = $posts_settings;
	//usort($posts_id, 'featured_posts_list_sort_by_managers');

	$counter=1;
	if(!empty($posts_id)) {
		foreach ($posts_id as $featured_post) {
			$ids = split(",",$featured_post);
			if (!ftable_exists("wp_{$ids[1]}_posts")) continue;
			$featured_blog_post =  get_blog_post($ids[1],$ids[0]);
			$author = get_user_details_by_id($featured_blog_post->post_author);
			print '<div class="featured_post_item">';
			print '<a class="linkwediget" href="'. $featured_blog_post->guid.'" rel="bookmark" title="Permanent Link to '. $featured_blog_post->guid.'">';
			print $featured_blog_post->post_title."</a><br />";
			print '<div class="gray_text_small">'.date('d.m.y',strtotime($featured_blog_post->post_date)) .'| by '.$author->user_login.' | '.$featured_blog_post->comment_count.' Comment</div>';
			print '<div class="entry gray_text_mid">'.truncate(strip_tags($featured_blog_post->post_content),200,"...",true,true).'</div>';
			print "<div style='text-align:right'><a href='$featured_blog_post->guid'>read more</a></div>";
			print '</div>';
			if($counter%2==0)
			print '<div style="clear:both;"></div>';
			
			
			$counter++;
		}
	} else {
		print "Sorry!, You do not  have Featured Post";
	}
}
$data = array ('posts_id' => '' );
$ol_flash = '';
$posts_settings = get_option ( 'posts_settings' );

// ADMIN PANLE SEETTING
function posts_add_pages() {
	// Add new menu in Setting or Options tab:
	add_options_page ( 'Featured Posts List', 'Featured Posts wpmu', 8, 'postsoptions', 'posts_options_page' );
}

/* Define Constants and variables*/
//define ( 'PLUGIN_URI', get_option ( 'siteurl' ) . '/wp-content/plugins/' );

/* Functions */

function posts_options_page() {
	global $ol_flash, $posts_settings, $_POST, $wp_rewrite,$wpdb;
	if (isset ($_POST['posts_id'])) {
		//$posts_settings = array();
		$posts_settings= $_POST['posts_id'];
		
		update_option( 'posts_settings', $posts_settings);
		$ol_flash = "Your Featured List has been saved.";
	}
	
	if ($ol_flash != '')
		echo '<div id="message"class="updated fade"><p>' . $ol_flash . '</p></div>';

	$blogs = $wpdb->get_results( $wpdb->prepare ( "SELECT blog_id FROM $wpdb->blogs" ) );
	$query="";
	foreach ( $blogs as $blog ) {
		$query.="(SELECT $blog->blog_id AS 'blog_id',wp_".$blog->blog_id."_posts.post_date, wp_".$blog->blog_id."_posts.post_title,wp_".$blog->blog_id."_posts.post_author,wp_".$blog->blog_id."_posts.ID,wp_".$blog->blog_id."_posts.guid,$wpdb->users.display_name FROM wp_".$blog->blog_id."_posts JOIN $wpdb->users ON wp_".$blog->blog_id."_posts.post_author = $wpdb->users.ID WHERE wp_".$blog->blog_id."_posts.post_status = 'publish' AND wp_".$blog->blog_id."_posts.post_type = 'post' ) UNION ";
	}
	
	$query =substr($query,0,-6);
	
	$result =$wpdb->get_results($wpdb->prepare($query)) or die(mysql_error());
	
	$post_per_page = 10;
	$total =count($result);
	
	$total_page = ceil($total/$post_per_page);
	
	echo '<div class="wrap">';
	echo '<h2>Click on checkbox to create Featured Post</h2>';
	echo '<h2><span style="background-color:#FFFBCC">Note</span>: you can use this plugin by put this code where erver you want. </h2><br /> <strong>featuredPostsList()</strong>';
	echo '<strong>This plugin gives full freedom to display multiple blogs as Featured Blogs List to your site.</strong><br />';
	
	echo '<form name="form'.$i.'" action="" method="post">';
	for($i=0;$i<$total_page;$i++) {
		
	

	print "<div class='virtualpage'>";
	
	$start = $post_per_page * $i;
	$query .=" ORDER BY post_date DESC";
	print "<table id='active-plugins-table' class='widefat' cellspacing='0'>";
	print "<thead><tr><th class='col'></th><th>post title</th><th>post date</th><th>blog URI</th><th>owner</th></tr></thead>";
	print "<tbody class='plugins'>";
	if(strpos($query," LIMIT")){
		$query = substr($query,0,strpos($query," LIMIT"));
	}
	
	$query .= " LIMIT $start,$post_per_page";

	$result =$wpdb->get_results($wpdb->prepare($query)) or die(mysql_error());

	foreach ($result as $row) {
		
		$position = strpos($row->guid,"/?p=");
		$guid = substr_replace($row->guid,"",strpos($row->guid,"/?p="));
		$guid = substr($guid,7,strlen($guid));
		print '<tr>';
			print '<td>';
				if($posts_settings) {
					if(in_array($row->ID.','.$row->blog_id,$posts_settings)){
					
						print '<input type="checkbox" name="posts_id[]" value="'.$row->ID.','.$row->blog_id.'" checked />';
					} else {
						print '<input type="checkbox" name="posts_id[]" value="'.$row->ID.','.$row->blog_id.'" />';
					}
				} else {
					print '<input type="checkbox" name="posts_id[]" value="'.$row->ID.','.$row->blog_id.'" />';
				}
				
			print '</td>';
			print '<td>';
				print $row->post_title;
			print '</td>';
			print '<td>';
				print $row->post_date;
			print '</td>';
			print '<td>';
				print $guid;
			print '</td>';			
			print '<td>';
				print $row->display_name;
			print '</td>';
		print '</tr>';
		
	}
	print "</tbody>";
	print "</table><Div class='submit'><input type='submit' value='Save your list' /></div></div>";
	
	}
	
	print '</form>';
	
	$home_url = get_option("home");
	print "</div>";
	print '<div id="gallerypaginate" class="paginationstyle"><a href="#" rel="previous">Prev</a> <span class="flatview"></span> <a href="#" rel="next">Next</a></div>';
	print "</div>";
	print '<script type="text/javascript" src="'. $home_url .'/wp-content/plugins/featured-posts-list/virtualpaginate.js"></script>';
	print '<script type="text/javascript">';
	print 'var gallery=new virtualpaginate({piececlass:"virtualpage",piececontainer: "div",pieces_per_page: 1,defaultpage: 0,persist: false});';
	print 'gallery.buildpagination(["gallerypaginate"]);';
	print '</script>';
	print '<style type="text/css">

/*Sample CSS used for the Virtual Pagination Demos. Modify/ remove as desired*/

.paginationstyle{ /*Style for demo pagination divs*/
width: 950px;
text-align: center;
padding: 2px 0;
margin: 10px 0;
}

.paginationstyle select{
border: 1px solid navy;
margin: 0 15px;
}

.paginationstyle a{ /*Pagination links style*/
padding: 0 5px;
text-decoration: none;
border: 1px solid black;
color: navy;
background-color: white;
}

.paginationstyle a:hover, .paginationstyle a.selected{
color: #000;
background-color: #ccc;
}

.paginationstyle a.imglinks{ /*Pagination Image links style (class="imglinks") */
border: 0;
padding: 0;
}

.paginationstyle a.imglinks img{
vertical-align: bottom;
border: 0;
}

.paginationstyle a.imglinks a:hover{
background: none;
}

.paginationstyle .flatview a:hover, .paginationstyle .flatview a.selected{ /*Pagination div "flatview" links style*/
color: #000;
background-color: #ccc;
}

</style>';
}

function fpl_delete_featured($post_id)
{
	global $post, $wpdb, $posts_settings;

	$new_post_settings = array();

	foreach ($posts_settings AS $post_couple)
	{
		$post_couple_array = explode(',', $post_couple);
		//if (!ftable_exists("wp_{$post_couple[1]}_posts")) continue;
		if ($post_id == trim($post_couple_array[0])) continue;
		$new_post_settings[] = $post_couple;
	}

	update_option('posts_settings', $new_post_settings);
	//$posts_settings = get_option ( 'posts_settings' );
}

add_action ( 'admin_menu', 'posts_add_pages' );
add_action ('deleted_post', 'fpl_delete_featured');


function truncate($text, $length = 100, $ending = '...', $exact = true, $considerHtml = false) {
	if ($considerHtml) {
		// if the plain text is shorter than the maximum length, return the whole text
		if (strlen(preg_replace('/<.*?>/', '', $text)) <= $length) {
			return $text;
		}
		// splits all html-tags to scanable lines
		preg_match_all('/(<.+?>)?([^<>]*)/s', $text, $lines, PREG_SET_ORDER);
		$total_length = strlen($ending);
		$open_tags = array();
		$truncate = '';
		foreach ($lines as $line_matchings) {
			// if there is any html-tag in this line, handle it and add it (uncounted) to the output
			if (!empty($line_matchings[1])) {
				// if it's an "empty element" with or without xhtml-conform closing slash (f.e. <br/>)
				if (preg_match('/^<(\s*.+?\/\s*|\s*(img|br|input|hr|area|base|basefont|col|frame|isindex|link|meta|param)(\s.+?)?)>$/is', $line_matchings[1])) {
					// do nothing
				// if tag is a closing tag (f.e. </b>)
				} else if (preg_match('/^<\s*\/([^\s]+?)\s*>$/s', $line_matchings[1], $tag_matchings)) {
					// delete tag from $open_tags list
					$pos = array_search($tag_matchings[1], $open_tags);
					if ($pos !== false) {
						unset($open_tags[$pos]);
					}
				// if tag is an opening tag (f.e. <b>)
				} else if (preg_match('/^<\s*([^\s>!]+).*?>$/s', $line_matchings[1], $tag_matchings)) {
					// add tag to the beginning of $open_tags list
					array_unshift($open_tags, strtolower($tag_matchings[1]));
				}
				// add html-tag to $truncate'd text
				$truncate .= $line_matchings[1];
			}
			// calculate the length of the plain text part of the line; handle entities as one character
			$content_length = strlen(preg_replace('/&[0-9a-z]{2,8};|&#[0-9]{1,7};|[0-9a-f]{1,6};/i', ' ', $line_matchings[2]));
			if ($total_length+$content_length> $length) {
				// the number of characters which are left
				$left = $length - $total_length;
				$entities_length = 0;
				// search for html entities
				if (preg_match_all('/&[0-9a-z]{2,8};|&#[0-9]{1,7};|[0-9a-f]{1,6};/i', $line_matchings[2], $entities, PREG_OFFSET_CAPTURE)) {
					// calculate the real length of all entities in the legal range
					foreach ($entities[0] as $entity) {
						if ($entity[1]+1-$entities_length <= $left) {
							$left--;
							$entities_length += strlen($entity[0]);
						} else {
							// no more characters left
							break;
						}
					}
				}
				$truncate .= substr($line_matchings[2], 0, $left+$entities_length);
				// maximum lenght is reached, so get off the loop
				break;
			} else {
				$truncate .= $line_matchings[2];
				$total_length += $content_length;
			}
			// if the maximum length is reached, get off the loop
			if($total_length>= $length) {
				break;
			}
		}
	} else {
		if (strlen($text) <= $length) {
			return $text;
		} else {
			$truncate = substr($text, 0, $length - strlen($ending));
		}
	}
	// if the words shouldn't be cut in the middle...
	if (!$exact) {
		// ...search the last occurance of a space...
		$spacepos = strrpos($truncate, ' ');
		if (isset($spacepos)) {
			// ...and cut the text in this position
			$truncate = substr($truncate, 0, $spacepos);
		}
	}
	// add the defined ending to the text
	$truncate .= $ending;
	if($considerHtml) {
		// close all unclosed html-tags
		foreach ($open_tags as $tag) {
			$truncate .= '</' . $tag . '>';
		}
	}
	return $truncate;
}
?>