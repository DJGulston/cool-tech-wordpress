<?php
/*
Plugin Name: Cool Tech Views
Plugin URI: https://localhost/cooltech
Description: A plugin that keeps track of post views and top ten posts with most views in an hour.
Version: 1.0
Author: Dean Gulston
Author URI: https://localhost/cooltech
Licence: UNLICENCED
*/

// Adds one view to a post whenever it is queried.
function cool_tech_add_view() {
	
	// Returns null if the query is not for a single existing post.
	if(!is_single()) return null;
	
	// The post that has been queried.
	global $post;
	
	// Get current number of views.
	$views = get_post_meta($post->ID, 'cool_tech_views', true);
	
	// If views has not been set in the post meta, we set views to 0.
	if(!$views) {
		$views = 0;
	}
	
	// Increment the views by one since the post is being viewed currently.
	$views++;
	
	// Update the views of the post in the post meta.
	update_post_meta($post->ID, 'cool_tech_views', $views);
	
	return $views;
	
}

add_action('wp_head', 'cool_tech_add_view');

// Formats the number of views for a post appropriately with a K, M or B appended to it.
function format_views($views) {
	// Casts the views from int to string.
	$str_views = strval($views);
	
	// Formats the strings based on whether it is over a billion, million or thousand.
	// Views over a billion get abbreviated with a B (such as 1B for 1,250,000,000), views
	// over a million get abbreviated with an M (such as 160M for 160,500,000; 30M for
	// 30,125,000; 7M for 7,350,000; etc), and views over a thousand get abbreviated with
	// a K (such as 320K for 320,200; 25K for 25,500; 6K for 6,100; etc).
	if((int) $views >= 1000000000) {
		$str_views = substr($str_views, 0, 1) . "B";
	}
	else if((int) $views >= 100000000) {
		$str_views = substr($str_views, 0, 3) . "M";
	}
	else if((int) $views >= 10000000) {
		$str_views = substr($str_views, 0, 2) . "M";
	}
	else if((int) $views >= 1000000) {
		$str_views = substr($str_views, 0, 1) . "M";
	}
	else if((int) $views >= 100000) {
		$str_views = substr($str_views, 0, 3) . "K";
	}
	else if((int) $views >= 10000) {
		$str_views = substr($str_views, 0, 2) . "K";
	}
	else if((int) $views >= 1000) {
		$str_views = substr($str_views, 0, 1) . "K";
	}
	
	return $str_views;
}

// Returns the number of views for a post.
function cool_tech_views() {
	
	// The post currently being queried.
	global $post;
	
	// Gets the current number of views.
	$views = get_post_meta($post->ID, 'cool_tech_views', true);
	
	// If views has not been set in the post meta, we set views to 0.
	if(!$views) {
		$views = 0;
	}
	
	// Formats the views.
	$str_views = format_views($views);
	
	// Displays number of views with grammatically appropriate sentence.
	if((int) $views === 1) {
		return $str_views . ' view';
	}
	else {
		return $str_views . ' views';
	}
}

// Adds a hot right now view to a post.
// If a post has a hot right now view, it means that post has been viewed
// within the last hour.
// We record the timestamp of when the post has been viewed/queried.
function add_hot_view() {
	
	// Returns null if the query is not for a single existing post.
	if(!is_single()) return null;
	
	// The post that has been queried.
	global $post;
	
	// Array of hot view timestamps obtained from post meta.
	$hot_view_times = get_post_meta($post->ID, 'hot_view_times', true);
	
	// Set the hot view timestamps array if it has not been already set.
	if(!$hot_view_times) {
		$hot_view_times = array();
	}
	
	// Gets the current time as a unix timestamp
	$current_unix_time = strtotime(date('Y-m-d H:i:s'));
	
	// Adds the current unix timestamp to the array of hot view times.
	array_push($hot_view_times, $current_unix_time);
	
	// Determines the number of hot views based on the number of hot view timestamps.
	$hot_views = count($hot_view_times);
	
	// Update hot view timestamps array in post meta.
	update_post_meta($post->ID, 'hot_view_times', $hot_view_times);
	
	// Update number of hot views in post meta.
	update_post_meta($post->ID, 'hot_views', $hot_views);
	
	return $hot_view_times;
	
}

add_action('wp_head', 'add_hot_view');

// Prints out a top 10 list of posts with the highest views
// in descending order.
function cool_tech_views_list() {
	
	// Parameters that search for 10 published posts ordered by
	// the number of views in descending order.
	$search_params = [
		'posts_per_page' => 10,
		'post_type' => 'post',
		'post_status' => 'publish',
		'meta_key' => 'cool_tech_views',
		'orderby' => 'meta_value_num',
		'order' => 'DESC'
	];
	
	// List of 10 most viewed posts.
	$list = new WP_Query($search_params);
	
	if($list->have_posts()) {
		
		global $post;
		
		echo '<ol>';
		
		while($list->have_posts()) {
			
			// Iterate to the next post.
			$list->the_post();
			
			// Gets the number of views for the post.
			$views = get_post_meta($post->ID, 'cool_tech_views', true);
			
			// If the views has not been set in the post meta, we set the views to 0.
			if(!$views) {
				$views = 0;
			}
			
			// Formats the views.
			$str_views = format_views($views);
			
			echo '<li><a href="' . get_permalink($post->ID) . '">';
			
			// Displays the title of the post.
			the_title();
			
			// Displays the number of views for that post with grammatically
			// appropriate sentence.
			if((int) $views === 1) {
				echo '</a> (' . $str_views . ' view)</li>';
			}
			else {
				echo '</a> (' . $str_views . ' views)</li>';
			}
			
		}
		
		echo '</ol>';
	}
	
}

// Returns the number of hot right now views (i.e. the number of views for a
// post within the last hour).
function hot_views() {
	
	// Removes hot views with timestamps older than 1 hour for all posts.
	refresh_all_hot_views();
	
	wp_reset_query();
	
	// The post currently being queried.
	global $post;
	
	// Obtains number of hot views after removing old hot view timestamps.
	$hot_views = get_post_meta($post->ID, 'hot_views', true);
	
	// Formats the hot views.
	$str_views = format_views($hot_views);
	
	// Displays number of hot views with grammatically appropriate sentence.
	if((int) $hot_views === 1) {
		return $str_views . ' view in the last hour';
	}
	else {
		return $str_views . ' views in the last hour';
	}
}

// Prints out a top 10 list of posts with the highest hot right now views
// in descending order.
function hot_views_list() {
	
	// Removes hot views with timestamps older than 1 hour for all posts.
	refresh_all_hot_views();
	
	wp_reset_query();
	
	// Parameters that search for 10 published posts ordered by
	// the number of hot views in descending order.
	$search_params = [
		'posts_per_page' => 10,
		'post_type' => 'post',
		'post_status' => 'publish',
		'meta_key' => 'hot_views',
		'orderby' => 'meta_value_num',
		'order' => 'DESC'
	];
	
	// List of highest numbered hot view posts.
	$list = new WP_Query($search_params);
	
	if($list->have_posts()) {
		
		global $post;
		
		echo '<ol>';
		
		while($list->have_posts()) {
			
			// Iterate to the next post.
			$list->the_post();
			
			// Gets the number of hot views for the post.
			$hot_views = get_post_meta($post->ID, 'hot_views', true);
			
			// If hot views has not been set in the post meta, we set hot views to 0.
			if(!$hot_views) {
				$hot_views = 0;
			}
			
			// Formats the hot views.
			$str_views = format_views($hot_views);
			
			echo '<li><a href="' . get_permalink($post->ID) . '">';
			
			// Displays the title of the post.
			the_title();
			
			// Displays the number of hot views for that post with grammatically
			// appropriate sentence.
			if((int) $hot_views === 1) {
				echo '</a> (' . $str_views . ' view in the last hour)</li>';
			}
			else {
				echo '</a> (' . $str_views . ' views in the last hour)</li>';
			}
			
		}
		
		echo '</ol>';
	}
	
}

// Refreshes hot right now views for all posts. Any hot right now view that has a
// timestamp older than one hour is removed from the hot right now view timestamps
// array.
function refresh_all_hot_views() {
	
	// Parameters that search for all posts.
	$search_params = [
		'posts_per_page' => -1,
		'post_type' => 'post',
		'post_status' => 'publish'
	];
	
	// List of all posts.
	$list = new WP_Query($search_params);
	
	if($list->have_posts()) {
		
		while($list->have_posts()) {
			
			// Iterate to the next post.
			$list->the_post();
			
			$post_id = get_the_ID();
			
			// Gets array of hot views timestamps for the post.
			$hot_view_times = get_post_meta($post_id, 'hot_view_times', true);
			
			// Sets the hot view timestamp array if it has not been set already.
			if(!$hot_view_times) {
				$hot_view_times = array();
			}
			
			// Get the current time as a unix timestamp.
			$current_unix_time = strtotime(date('Y-m-d H:i:s'));
			
			$i = 0; // Index used to iterate through the array.
			
			// Loops through the array of hot view times and removes all timestamps that differ more than 1 hour
			// from the current time.
			while($i < count($hot_view_times)) {
				
				// If the difference between the current unix timestamp and the unix timestamp at index i
				// is greater than 3600 seconds (i.e. greater than 1 hour), we remove the unix timestamp at
				// index i from the array.
				if((int) $current_unix_time - (int) $hot_view_times[$i] > 3600) {
					
					array_splice($hot_view_times, $i, 1); // Removes timestamp at index i.
					
					// Note that we do not increment index i since we removed the unix timestamp at index i.
					// If we increment i here, we will mistakenly skip the next timestamp.
					
				}
				else {
					// Increment index i to move onto the next timestamp since we did not remove a
					// timestamp at index i.
					$i++;
				}
			}
			
			// Update the hot view timestamps array after removing old timestamps.
			update_post_meta($post_id, 'hot_view_times', $hot_view_times);
			
			// Determine the number of hot views by counting the number of timestamps still currently
			// in the hot view timestamps array.
			$hot_views = count($hot_view_times);
			
			// Update the number of hot views after removing old timestamps.
			update_post_meta($post_id, 'hot_views', $hot_views);
		}
		
	}
}

/*

References:

How to initialize a blank array in PHP:
- https://www.geeksforgeeks.org/best-way-to-initialize-empty-array-in-php/

How to use a substring function in PHP:
- https://www.phptutorial.net/php-tutorial/php-substr/

Subtract hours from datetime in PHP:
- https://www.itsolutionstuff.com/post/how-to-subtract-hours-from-datetime-in-phpexample.html

Show number of views in last 48 hours:
- https://wordpress.stackexchange.com/questions/129804/show-number-of-views-in-the-last-48-hours

Update post meta value as array:
- https://wordpress.stackexchange.com/questions/243238/how-to-update-post-meta-value-as-array

Checking if date is within last hour:
- https://stackoverflow.com/questions/29012927/php-comparing-date-checking-if-within-last-hour

How to use date function in PHP:
- https://www.w3schools.com/php/func_date_date.asp

How to use strtotime function in PHP:
- https://www.w3schools.com/php/func_date_strtotime.asp

Deleting element from array in PHP:
- https://stackoverflow.com/questions/369602/deleting-an-element-from-an-array-in-php

How to get the post ID from WP_Query loop:
- https://wordpress.stackexchange.com/questions/214699/how-can-i-get-the-post-id-from-a-wp-query-loop

How to query all posts using WP_Query():
- https://stackoverflow.com/questions/30531600/wordpress-how-do-i-get-all-posts-from-wp-query-in-the-search-results

When to use wp_reset_query():
- https://wordpress.stackexchange.com/questions/144343/wp-reset-postdata-or-wp-reset-query-after-a-custom-loop

*/