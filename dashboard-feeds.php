<?php

/*
Plugin Name: Dashboard Feeds
Plugin URI: http://premium.wpmudev.org/project/dashboard-feeds
Description: Customize the dashboard for every user in a flash with this straightforward dashboard feed replacement widget... no more WP development news or Matt's latest photo set :)
Author: Ivan Shaovchev, Andrew Billits, Andrey Shipilov (Incsub)
Author URI: http://premium.wpmudev.org
Version: 1.5.2
Network: true
WDP ID: 15
License: GNU General Public License (Version 2 - GPLv2)
*/

/*
Copyright 2007-2011 Incsub (http://incsub.com)

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

/**
 * Set the widget settings after "=".
 * The settings will apply to all blogs on your network and will overwrite
 * the default and custom ones.
 *
 * @return void
 **/
function df_set_widget_options() {

    $widget_options['dashboard_primary']['link']         = 'http://wordpress.org/news/';
    $widget_options['dashboard_primary']['url']          = 'http://wordpress.org/news/feed/';
    $widget_options['dashboard_primary']['title']        = 'WordPress Blog';
    $widget_options['dashboard_primary']['items']        = 2;
    $widget_options['dashboard_primary']['show_summary'] = true;
    $widget_options['dashboard_primary']['show_author']  = false;
    $widget_options['dashboard_primary']['show_date']    = true;

    $widget_options['dashboard_secondary']['link']         = 'http://planet.wordpress.org/';
    $widget_options['dashboard_secondary']['url']          = 'http://planet.wordpress.org/feed/';
    $widget_options['dashboard_secondary']['title']        = 'Other WordPress News';
    $widget_options['dashboard_secondary']['items']        = 5;
    $widget_options['dashboard_secondary']['show_summary'] = false;
    $widget_options['dashboard_secondary']['show_author']  = false;
    $widget_options['dashboard_secondary']['show_date']    = false;

    $current_widget_options = get_option('dashboard_widget_options');

    if ( $current_widget_options ) {
        //delete cache for update the widget when we change options values
        if ( $current_widget_options['dashboard_primary']['link']           != $widget_options['dashboard_primary']['link'] ||
             $current_widget_options['dashboard_primary']['url']            != $widget_options['dashboard_primary']['url'] ||
             $current_widget_options['dashboard_primary']['items']          != $widget_options['dashboard_primary']['items'] ||
             $current_widget_options['dashboard_primary']['show_summary']   != $widget_options['dashboard_primary']['show_summary'] ||
             $current_widget_options['dashboard_primary']['show_author']    != $widget_options['dashboard_primary']['show_author'] ||
             $current_widget_options['dashboard_primary']['show_date']      != $widget_options['dashboard_primary']['show_date'] )
        {
            $cache_key = 'dash_' . md5( 'dashboard_primary' );
            delete_transient( $cache_key );
        }
        //delete cache for update the widget when we change values of options
        if ( $current_widget_options['dashboard_secondary']['link']         != $widget_options['dashboard_secondary']['link'] ||
             $current_widget_options['dashboard_secondary']['url']          != $widget_options['dashboard_secondary']['url'] ||
             $current_widget_options['dashboard_secondary']['items']        != $widget_options['dashboard_secondary']['items'] ||
             $current_widget_options['dashboard_secondary']['show_summary'] != $widget_options['dashboard_secondary']['show_summary'] ||
             $current_widget_options['dashboard_secondary']['show_author']  != $widget_options['dashboard_secondary']['show_author'] ||
             $current_widget_options['dashboard_secondary']['show_date']    != $widget_options['dashboard_secondary']['show_date'] )
        {
            $cache_key = 'dash_' . md5( 'dashboard_secondary' );
            delete_transient( $cache_key );
        }

        $widget_options = array_merge( $current_widget_options, $widget_options );
    }
    update_option( 'dashboard_widget_options', $widget_options );

}

/* Call the function */
df_set_widget_options();

/* Update Notifications Notice */
if ( !function_exists( 'wdp_un_check' ) ) {
    function wdp_un_check() {
        if ( !class_exists('WPMUDEV_Update_Notifications') && current_user_can('edit_users') )
            echo '<div class="error fade"><p>' . __('Please install the latest version of <a href="http://premium.wpmudev.org/project/update-notifications/" title="Download Now &raquo;">our free Update Notifications plugin</a> which helps you stay up-to-date with the most stable, secure versions of WPMU DEV themes and plugins. <a href="http://premium.wpmudev.org/wpmu-dev/update-notifications-plugin-information/">More information &raquo;</a>', 'wpmudev') . '</a></p></div>';
    }
    add_action( 'admin_notices', 'wdp_un_check', 5 );
    add_action( 'network_admin_notices', 'wdp_un_check', 5 );
}

?>