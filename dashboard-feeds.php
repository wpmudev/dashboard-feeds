<?php
/*
Plugin Name: Dashboard Feeds
Plugin URI: 
Description:
Author: Andrew Billits
Version: 1.5.0
Author URI:
*/

/* 
Copyright 2007-2009 Incsub (http://incsub.com)

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License (Version 2 - GPLv2) as published by
the Free Software Foundation.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
*/

//------------------------------------------------------------------------//
//---Config---------------------------------------------------------------//
//------------------------------------------------------------------------//
$primary_feed_url = '';
$primary_feed_link = '';
$primary_feed_title = '';
$primary_feed_description = '';
$secondary_feed_url = '';
$secondary_feed_link = '';
$secondary_feed_title = '';
$secondary_feed_description = '';
//------------------------------------------------------------------------//
//---Hook-----------------------------------------------------------------//
//------------------------------------------------------------------------//
add_filter('dashboard_primary_feed', 'primary_dashboard_feed_url');
add_filter('dashboard_primary_link', 'primary_dashboard_feed_link');
add_filter('dashboard_primary_title', 'primary_dashboard_feed_title');
add_filter('dashboard_secondary_feed', 'secondary_dashboard_feed_url');
add_filter('dashboard_secondary_link', 'secondary_dashboard_feed_link');
add_filter('dashboard_secondary_title', 'secondary_dashboard_feed_title');
add_action('wp_dashboard_setup', 'dashboard_feed_descriptions', 100 );
//------------------------------------------------------------------------//
//---Functions------------------------------------------------------------//
//------------------------------------------------------------------------//

function dashboard_feed_descriptions() {
	global $wp_registered_widgets, $primary_feed_description, $secondary_feed_description;
	if ($primary_feed_description != ''){
		if ( isset($wp_registered_widgets['dashboard_primary']) ) {
			$wp_registered_widgets['dashboard_primary']['description']         = __( $primary_feed_description );
		}
	}
	if ($secondary_feed_description != ''){
		if ( isset($wp_registered_widgets['dashboard_secondary']) ) {
			$wp_registered_widgets['dashboard_secondary']['description']       = __( $secondary_feed_description );
		}
	}
}

function primary_dashboard_feed_url($url){
	global $primary_feed_url;
	if ($primary_feed_url != ''){
		$url = $primary_feed_url;
	}
	return $url;
}

function primary_dashboard_feed_link($link){
	global $primary_feed_link;
	if ($primary_feed_link != ''){
		$link = $primary_feed_link;
	}
	return $link;
}

function primary_dashboard_feed_title($title){
	global $primary_feed_title;
	if ($primary_feed_title != ''){
		$title = $primary_feed_title;
	}
	return $title;
}

function secondary_dashboard_feed_url($url){
	global $secondary_feed_url;
	if ($secondary_feed_url != ''){
		$url = $secondary_feed_url;
	}
	return $url;
}

function secondary_dashboard_feed_link($link){
	global $secondary_feed_link;
	if ($secondary_feed_link != ''){
		$link = $secondary_feed_link;
	}
	return $link;
}

function secondary_dashboard_feed_title($title){
	global $secondary_feed_title;
	if ($secondary_feed_title != ''){
		$title = $secondary_feed_title;
	}
	return $title;
}

//------------------------------------------------------------------------//
//---Output Functions-----------------------------------------------------//
//------------------------------------------------------------------------//

//------------------------------------------------------------------------//
//---Page Output Functions------------------------------------------------//
//------------------------------------------------------------------------//

//------------------------------------------------------------------------//
//---Support Functions----------------------------------------------------//
//------------------------------------------------------------------------//

?>