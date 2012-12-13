<?php
/*
Plugin Name: Dashboard Feeds
Plugin URI: http://premium.wpmudev.org/project/dashboard-feeds
Description: Customize the dashboard for every user in a flash with this straightforward dashboard feed replacement widget... no more WP development news or Matt's latest photo set :)
Author: Paul Menard (Incsub)
Author URI: http://premium.wpmudev.org
Version: 2.0.1
WDP ID: 15
License: GNU General Public License (Version 2 - GPLv2)

Copyright 2012 Incsub (http://incsub.com)

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License (Version 2 - GPLv2) as published by
the Free Software Foundation.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
*/

// Support for WPMU DEV Dashboard plugin
include_once( dirname(__FILE__) . '/lib/dash-notices/wpmudev-dash-notification.php');

if (!class_exists('WPMUDEV_Dashboard_Feeds')) {

	class WPMUDEV_Dashboard_Feeds {
		private $_settings;
		private $_pagehooks;

		function WPMUDEV_Dashboard_Feeds() {
	        $this->__construct();
	    }
	    
		function __construct() {
			$this->_settings['VERSION'] = "2.0.1";
			
			add_action( 'init', 						array(&$this, 'init_proc') );			
			add_action( 'admin_head', 					array(&$this, 'admin_head_proc') );
			
			add_action( 'admin_menu', 					array(&$this, 'admin_menu_proc') );			
			add_action( 'network_admin_menu', 			array(&$this, 'admin_menu_proc') );			
			
			add_action( 'wp_dashboard_setup', 			array($this, 'add_dashboard_widgets'), 99 );
			
			add_filter( 'dashboard_primary_link', 		array($this, 'dashboard_primary_link_filter'), 99 );
			add_filter( 'dashboard_primary_feed', 		array($this, 'dashboard_primary_feed_filter'), 99 );
			add_filter( 'dashboard_primary_title', 		array($this, 'dashboard_primary_title_filter'), 99 );

			add_filter( 'dashboard_primary_link', 		array($this, 'dashboard_secondary_link_filter'), 99 );
			add_filter( 'dashboard_secondary_feed', 	array($this, 'dashboard_secondary_feed_filter'), 99 );
			add_filter( 'dashboard_secondary_title',	array($this, 'dashboard_secondary_title_filter'), 99 );
			
	        load_plugin_textdomain( 'dashboard-feeds', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
		}

		function admin_head_proc() {
			$js_commands = '';
			
			$df_settings = get_option('dashboard_widget_settings');						
			if (isset($df_settings['force-dashboard_primary'])) {
				$js_commands .= "jQuery('div#dashboard_primary span.postbox-title-action').hide(); ";
			}
			if (isset($df_settings['force-dashboard_secondary'])) {
				$js_commands .= "jQuery('div#dashboard_secondary span.postbox-title-action').hide(); ";
			}
			if (strlen($js_commands)) {
				?>
				<script type="text/javascript">
				jQuery(document).ready( function($) {
					<?php echo $js_commands; ?>
				});	
				</script>
				<?php
			}
		}
		
		function init_proc() {
		}
		
		function admin_menu_proc() {
			if (is_network_admin()) {
				$this->_pagehooks['dashboard-feeds'] = add_submenu_page('settings.php', 
					_x("Dashboard Feeds", 'page label', 'dashboard-feeds'), 
					_x("Dashboard Feeds", 'page label', 'dashboard-feeds'), 
					'manage_network', 
					'dashboard-feeds', 
					array($this, 'network_settings_show_panel')
				);
			} else {
				$this->_pagehooks['dashboard-feeds'] = add_options_page( 
					_x("Dashboard Feeds", 'page label', 'dashboard-feeds'),
					_x("Dashboard Feeds", 'menu label', 'dashboard-feeds'),
					'manage_options', 
					'dashboard-feeds', 
					array($this, 'network_settings_show_panel')
				);
			}
			add_action('load-'. $this->_pagehooks['dashboard-feeds'], 		array(&$this, 'network_settings_panel_on_load'));
		}

		function network_settings_panel_on_load() {
			wp_enqueue_style( 'dashboard-feeds-admin-stylesheet', plugins_url( '/css/dashboard-feeds-admin.css', __FILE__ ), 
				false, $this->_settings['VERSION']);			
		}
		
		function network_settings_show_panel() {
			?>
			<div id="user-reports-panel" class="wrap user-reports-wrap">
				<?php screen_icon(); ?>
				<h2><?php _ex("Dashboard Feeds", "New Page Title", 'dashboard-feeds'); ?></h2>

				<?php
					if (isset($_POST['df_settings'])) {
						update_option('dashboard_widget_settings', $_POST['df_settings']);						
					}
					
					if ((isset($_POST['widget-rss'])) && (isset($_GET['page'])) && ($_GET['page'] == "dashboard-feeds")) {

						$df_widgets = array();
						
						foreach($_POST['widget-rss'] as $widget_id => $widget_options) {
							if (substr($widget_id, 0, 3) == "df-") {
								$df_widgets[$widget_id] = $widget_options;
							}
						}

						if (count($df_widgets)) {
							$df_settings 		= get_option('dashboard_widget_settings');						
							$wp_widgets_current = get_option('dashboard_widget_options');
							$df_widgets_current = get_option('wpmudev_df_widget_options');
							if ((!$df_widgets_current) || (!is_array($df_widgets_current)))
								$df_widgets_current = array();
							
							foreach($df_widgets as $widget_id => $widget_options) {
								if (empty($widget_options['url'])) {
									unset($df_widgets_current[$widget_id]);
								} else {
									// We update the WP dashboard widgets at the same time IF we are in force mode. May the force by with you. 
									if (($widget_id == "df-dashboard_primary") && (isset($df_settings['force-dashboard_primary']))) {
										$wp_widgets_current['dashboard_primary'] = $widget_options;
									} else if (($widget_id == "df-dashboard_secondary") && (isset($df_settings['force-dashboard_primary']))) {
										$wp_widgets_current['dashboard_secondary'] = $widget_options;
									}
								
									$df_widgets_current[$widget_id] = $widget_options;
								} 
								$cache_key = 'dash_' . md5( $widget_id );
					            delete_transient( $cache_key );					            
						    }

							update_option('dashboard_widget_options', $wp_widgets_current);						
							update_option('wpmudev_df_widget_options', $df_widgets_current);

						} else {
							delete_option('wpmudev_df_widget_options');
						}
					}
				?>
				
				<form id="dashboard-feeds-form" method="post" action="">
					<?php 
						$df_settings = get_option('dashboard_widget_settings');					
						$df_widgets = get_option('wpmudev_df_widget_options');
						if ((!$df_widgets) || (!is_array($df_widgets)))
							$df_widgets = array();
 					?>
					<?php 
						if (!isset($df_widgets['df-dashboard_primary'])) { 
							$wp_widgets = get_option('dashboard_widget_options');

							if (isset($wp_widgets['dashboard_primary']))
								$df_widgets['df-dashboard_primary'] = $wp_widgets['dashboard_primary'];
						}
					?>
					<?php if (isset($df_widgets['df-dashboard_primary'])) { ?>

						<h2><?php _e('Primary Dashboard Widget', 'dashboard-feeds'); ?></h2>
						<table class="df_dashboard_widgets">
						<tr>
							<td>
								<input type="checkbox" name="df_settings[force-dashboard_primary]" id="df-settings-force-dashboard-primary" <?php
								 if (isset($df_settings['force-dashboard_primary'])) { echo ' checked="checked" '; } ?> /> <label 
								for="df-settings-force-dashboard-primary"><?php _e('Checked - This will remove the "configure" link on the widget header.<br /> Unchecked - allow individual users to control this widget on their own Dashboards.', 'dashboard-feeds'); ?></label>
								<?php 
									$df_widgets['df-dashboard_primary']['number'] = 'df-dashboard_primary';

									if (!isset($df_widgets['df-dashboard_primary']['link']))
										$df_widgets['df-dashboard_primary']['link'] = '';
									?>
									<div class="df-form_section">
										<p><label for="rss-link-<?php echo $df_widgets['df-dashboard_primary']['number']; ?>"><?php _e('Enter Site URL here:'); ?></label>
										<input class="widefat" id="rss-link-<?php echo $df_widgets['df-dashboard_primary']['number']; ?>" 
											name="widget-rss[<?php echo $df_widgets['df-dashboard_primary']['number']; ?>][link]" type="text" 
											value="<?php echo $df_widgets['df-dashboard_primary']['link']; ?>" />
										<?php wp_widget_rss_form($df_widgets['df-dashboard_primary']); ?></p>
									</div>
									<?php
									unset($df_widgets['df-dashboard_primary']);
								?>
							</td>
						</tr>
						</table>
					<?php } ?>
					
					<?php 
						if (!isset($df_widgets['df-dashboard_secondary'])) { 
							$wp_widgets = get_option('dashboard_widget_options');
							
							if (isset($wp_widgets['dashboard_secondary']))
								$df_widgets['df-dashboard_secondary'] = $wp_widgets['dashboard_secondary'];
						}
					?>					
					<?php if (isset($df_widgets['df-dashboard_secondary'])) { ?>					
						<h2><?php _e('Secondary Dashboard Widget', 'dashboard-feeds'); ?></h2>
						<table class="df_dashboard_widgets">
						<tr>
							<td>
								<input type="checkbox" name="df_settings[force-dashboard_secondary]" id="df-settings-force-dashboard-secondary" <?php
								 if (isset($df_settings['force-dashboard_secondary'])) { echo ' checked="checked" '; } ?> /> <label 
								for="df-settings-force-dashboard-secondary"><?php _e('Checked - This will remove the "configure" link on the widget header.<br />Unchecked - allow individual users to control this widget on their own Dashboards.', 'dashboard-feeds'); ?></label>
								<?php 
									$df_widgets['df-dashboard_secondary']['number'] = 'df-dashboard_secondary';

									if (!isset($df_widgets['df-dashboard_secondary']['link']))
										$df_widgets['df-dashboard_secondary']['link'] = '';
									?>
									<div class="df-form_section">
										<p><label for="rss-link-<?php echo $df_widgets['df-dashboard_secondary']['number']; ?>"><?php _e('Enter Site URL here:'); ?></label>
										<input class="widefat" id="rss-link-<?php echo $df_widgets['df-dashboard_secondary']['number']; ?>" 
										name="widget-rss[<?php echo $df_widgets['df-dashboard_secondary']['number']; ?>][link]" type="text" 
										value="<?php echo $df_widgets['df-dashboard_secondary']['link']; ?>" /></p>
										<?php wp_widget_rss_form($df_widgets['df-dashboard_secondary']); ?>
									</div>
									<?php
									unset($df_widgets['df-dashboard_secondary']);
								?>
							</td>
						</tr>
						</table>
					<?php } ?>
					
					<h2><?php _e('Extra Dashboard Widgets', 'dashboard-feeds'); ?></h2>
					<?php _e('<p>To remove a widget blank the RSS feed url and submit</p>', 'dashboard-feeds'); ?>
					<table class="df_dashboard_widgets">
					<tr>
						<td>
							<ul class="df_extra_widgets_list">
						    <?php 
								if (count($df_widgets)) {
									foreach($df_widgets as $widget_id => $widget_options) {
										?><li><?php 
										$widget_options['number'] 	= $widget_id;
										if (!isset($widget_options['link']))
											$widget_options['link'] = '';
										?>
										<div class="df-form_section">
											<p><label for="rss-link-<?php echo $widget_options['number']; ?>"><?php _e('Enter Site URL here:'); ?></label>
											<input class="widefat" id="rss-link-<?php echo $widget_options['number']; ?>" 
												name="widget-rss[<?php echo $widget_options['number']; ?>][link]" type="text" 
												value="<?php echo $widget_options['link']; ?>" /></p>
											<?php wp_widget_rss_form($widget_options); ?>
										</div>
										</li><?php				
									}
								}
								?>
							</ul>
						</td>
					</tr>
					</table>

					<h2><?php _e('New Dashboard Widget', 'dashboard-feeds'); ?></h2>
					<?php _e('<p>Enter the new widget information below.</p>', 'dashboard-feeds'); ?>
					<table class="df_dashboard_widgets">
					<tr>
						<td>
							<ul class="df_extra_widgets_list">
								<li><?php 
									$widget_options['number'] 	= sprintf("df-%d", count($df_widgets)+1);
									$widget_options['title']	= '';
									$widget_options['link']		= '';
									$widget_options['url']		= '';
									$widget_options['items']	= 5;
									?>
									<div class="df-form_section">
										<p><label for="rss-link-<?php echo $widget_options['number']; ?>"><?php _e('Enter Site URL here:'); ?></label>
										<input class="widefat" id="rss-link-<?php echo $widget_options['number']; ?>" 
											name="widget-rss[<?php echo $widget_options['number']; ?>][link]" type="text" 
											value="<?php echo $widget_options['link']; ?>" /></p>
										<?php wp_widget_rss_form($widget_options); ?>
									</div>
								</li>
							</ul>
						</td>
					</tr>
					</table>
					<input type="submit" value="<?php _e('Submit', 'dashboard-feeds'); ?>" class="primary-button"/>
				</form>
			</div>
			<?php
		}
		
		function add_dashboard_widgets() {
			$widget_items = array();
			$df_widgets_current = get_option('wpmudev_df_widget_options');
			if ((!$df_widgets_current) || (!is_array($df_widgets_current)))
				$df_widgets_current = array();
				
			foreach($df_widgets_current as $widget_id => $widget_options) {
				if (($widget_id == 'df-dashboard_primary') || ($widget_id == 'df-dashboard_secondary'))
					continue;
				else {
					$widget_items[$widget_id] = new WPMUDEV_Dashboard_Feed_Widget();
					$widget_items[$widget_id]->init($widget_id, $widget_options);
				}
			}
		}
		
		function dashboard_primary_link_filter($link) {
			$df_settings = get_option('dashboard_widget_settings');
			if (isset($df_settings['force-dashboard_primary'])) {
				$df_widgets = get_option('wpmudev_df_widget_options');
				if ((isset($df_widgets['df-dashboard_primary']['link'])) && (!empty($df_widgets['df-dashboard_primary']['link'])))
					return $df_widgets['df-dashboard_primary']['link'];
			}
			return $link;
		}
		function dashboard_primary_feed_filter($feed) {
			$df_settings = get_option('dashboard_widget_settings');						
			if (isset($df_settings['force-dashboard_primary'])) {
				$df_widgets = get_option('wpmudev_df_widget_options');
				if ((isset($df_widgets['df-dashboard_primary']['url'])) && (!empty($df_widgets['df-dashboard_primary']['url'])))
					return $df_widgets['df-dashboard_primary']['url'];
			}
			return $feed;
		}
		function dashboard_primary_title_filter($title) {
			$df_settings = get_option('dashboard_widget_settings');						
			if (isset($df_settings['force-dashboard_primary'])) {
				$df_widgets = get_option('wpmudev_df_widget_options');
				if ((isset($df_widgets['df-dashboard_primary']['title'])) && (!empty($df_widgets['df-dashboard_primary']['title'])))
					return $df_widgets['df-dashboard_primary']['title'];
			}
			return $title;
		}

		function dashboard_secondary_link_filter($link) {
			$df_settings = get_option('dashboard_widget_settings');						
			if (isset($df_settings['force-dashboard_secondary'])) {
				$df_widgets = get_option('wpmudev_df_widget_options');
				if ((isset($df_widgets['df-dashboard_secondary']['link'])) && (!empty($df_widgets['df-dashboard_secondary']['link'])))
					return $df_widgets['df-dashboard_secondary']['link'];
			} 
			return $link;
		}
		function dashboard_secondary_feed_filter($feed) {
			$df_settings = get_option('dashboard_widget_settings');						
			if (isset($df_settings['force-dashboard_secondary'])) {
				$df_widgets = get_option('wpmudev_df_widget_options');
				if ((isset($df_widgets['df-dashboard_secondary']['url'])) && (!empty($df_widgets['df-dashboard_secondary']['url'])))
					return $df_widgets['df-dashboard_secondary']['url'];
			}
			return $feed;			
		}
		function dashboard_secondary_title_filter($title) {
			$df_settings = get_option('dashboard_widget_settings');						
			if (isset($df_settings['force-dashboard_secondary'])) {
				$df_widgets = get_option('wpmudev_df_widget_options');
				if ((isset($df_widgets['df-dashboard_secondary']['title'])) && (!empty($df_widgets['df-dashboard_secondary']['title'])))
					return $df_widgets['df-dashboard_secondary']['title'];
			}
			return $title;
		}
	}
}

$wpmudev_dashboard_feeds = new WPMUDEV_Dashboard_Feeds();

if (!class_exists('WPMUDEV_Dashboard_Feed_Widget')) {

	class WPMUDEV_Dashboard_Feed_Widget {
		var $widget_id;
		var $widget_options;
		
		function WPMUDEV_Dashboard_Feed_Widget() {
	        $this->__construct();
	    }
	    
		function __construct() {
		}
		
		function init($options_set='', $options=array()) {
			if (empty($options_set)) return;
			if (empty($options)) return;

			if (strlen($options_set)) {
				$this->widget_id = "wpmudev_dashboard_item_". $options_set;
				$options['number'] = $options_set;
			}
			
			$this->widget_options = $options;
			wp_add_dashboard_widget( $this->widget_id, 
				$this->widget_options['title'], 
				array(&$this, 'wp_dashboard_widget_display') 
			);
		}

		function wp_dashboard_widget_display() {

			$rss = @fetch_feed( $this->widget_options['url'] );

			if ( is_wp_error($rss) ) {
				if ( is_admin() || current_user_can('manage_options') ) {
					echo '<div class="rss-widget"><p>';
					printf(__('<strong>RSS Error</strong>: %s'), $rss->get_error_message());
					echo '</p></div>';
				}
				
			} elseif ( !$rss->get_item_quantity() ) {
				$rss->__destruct();
				unset($rss);
				return false;
				
			} else {
				echo '<div class="rss-widget">';
				wp_widget_rss_output( $rss, $this->widget_options );
				echo '</div>';
				$rss->__destruct();
				unset($rss);
			}
		}

		function wp_dashboard_widget_controls() {
			wp_widget_rss_form( $this->widget_options );			
		}		
	}
}
