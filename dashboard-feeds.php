<?php
/*
Plugin Name: Dashboard Feeds
Plugin URI: http://premium.wpmudev.org/project/dashboard-feeds
Description: Customize the dashboard for every user in a flash with this straightforward dashboard feed replacement widget... no more WP development news or Matt's latest photo set :)
Author: Paul Menard (Incsub)
Author URI: http://premium.wpmudev.org
Version: 2.0.4.1
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

if (!class_exists('WPMUDEV_Dashboard_Feeds')) {

	class WPMUDEV_Dashboard_Feeds {
		private $_settings;
		private $_pagehooks;
	    
		function __construct() {
			$this->_settings['VERSION'] = "2.0.4.1";
			
			// Support for WPMU DEV Dashboard plugin
			global $wpmudev_notices;
			$wpmudev_notices[] = array( 'id'=> 15,'name'=> 'Dashboard Feeds', 'screens' => array('settings_page_dashboard-feeds-network') );
			require_once( dirname(__FILE__) . '/lib/dash-notices/wpmudev-dash-notification.php');			
			
			//add_action( 'init', 							array(&$this, 'init_proc') );			
			//add_action( 'admin_init', 					array(&$this, 'admin_init_proc') );
			add_filter( 'option_dashboard_widget_options', 	array(&$this, 'option_dashboard_widget_options_filter') );		

			add_action( 'admin_footer', 					array(&$this, 'admin_footer_proc'), 1 );
			add_action( 'admin_menu', 						array(&$this, 'admin_menu_proc'), 1 );
			add_action( 'network_admin_menu', 				array(&$this, 'admin_menu_proc'), 1 );

			add_action( 'wp_dashboard_setup', 				array(&$this, 'add_dashboard_widgets'), 99 );
			add_action( 'wp_network_dashboard_setup', 		array(&$this, 'add_dashboard_widgets'), 99 );
			add_action( 'wp_user_dashboard_setup', 			array(&$this, 'add_dashboard_widgets'), 99 );
			
	        load_plugin_textdomain( 'dashboard-feeds', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
		}

		function WPMUDEV_Dashboard_Feeds() {
	        $this->__construct();
	    }

		function admin_footer_proc() {
			$js_commands = '';
			
			$df_settings = $this->get_df_widget_settings(); 
			//echo "df_settings<pre>"; print_r($df_settings); echo "</pre>";
			
			if (isset($df_settings['hide-default-wordpress-feed-widget'])) {
				$js_commands .= "jQuery('div#dashboard_primary').hide(); ";
				$js_commands .= "var input_hide = jQuery('div.metabox-prefs input#dashboard_primary-hide'); jQuery(input_hide).hide(); ";
				$js_commands .= "var label_hide = jQuery(input_hide).parent('label'); jQuery(label_hide).hide();";
			}
			
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
		
		function admin_init_proc() {
			
		}
		
		function option_dashboard_widget_options_filter($widget_options) {
		
			//echo "widget_options<pre>"; print_r($widget_options); echo "</pre>";
			//echo "widget_options<pre>"; print_r($widget_options); echo "</pre>";
			//echo "trace<pre>"; print_r(debug_print_backtrace()); echo "</pre>";
			
						
			if (!is_admin()) return $widget_options;
			if (is_network_admin()) return $widget_options;
			
			$df_settings = $this->get_df_widget_settings();
			//echo "df_settings<pre>"; print_r($df_settings); echo "</pre>";
			
			$df_widgets = $this->get_df_feed_widgets_options();
			//echo "df_widgets<pre>"; print_r($df_widgets); echo "</pre>";
			
			// Enforce Primary Widget rules. 
			if (isset($widget_options['dashboard_primary'])) {
				if ((isset($df_settings['force-dashboard_primary'])) && ($df_settings['force-dashboard_primary'] == "on")) {
					if (isset($df_widgets['df-dashboard_primary'])) {
						$widget_options['dashboard_primary'] = $df_widgets['df-dashboard_primary'];
					}
				}
			} else {				
				if (isset($df_widget['df-dashboard_primary'])) {
					$widget_options['dashboard_primary'] = $df_widget['df-dashboard_primary'];
				}
			}

			// Enforce Seconday Widget rules. 
			if (isset($widget_options['dashboard_secondary'])) {
				if ((isset($df_settings['force-dashboard_secondary'])) && ($df_settings['force-dashboard_secondary'] == "on")) {
					if (isset($df_widgets['df-dashboard_secondary'])) {
						$widget_options['dashboard_secondary'] = $df_widgets['df-dashboard_secondary'];
					}
				}
			} else {				
				if (isset($df_widget['df-dashboard_secondary'])) {
					$widget_options['dashboard_secondary'] = $df_widget['df-dashboard_secondary'];
				}
			}
			//echo "widget_options<pre>"; print_r($widget_options); echo "</pre>";
			//die();
			return $widget_options;
		}
		
		function admin_menu_proc() {
			if (is_multisite()) {
				if (is_network_admin()) {
					$this->_pagehooks['dashboard-feeds'] = add_submenu_page('settings.php', 
						_x("Dashboard Feeds", 'page label', 'dashboard-feeds'), 
						_x("Dashboard Feeds", 'page label', 'dashboard-feeds'), 
						'manage_network', 
						'dashboard-feeds', 
						array($this, 'network_settings_show_panel')
					);
				}				
			} else if (!is_multisite()) {
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
				//$screen = get_current_screen();
				//echo "screen<pre>"; print_r($screen); echo "</pre>";
				
					//echo "_POST<pre>"; print_r($_POST); echo "</pre>";
					if (isset($_POST['df-form-submit'])) {
						//echo "_POST<pre>"; print_r($_POST); echo "</pre>";
						//die();
						if (isset($_POST['df_settings'])) {
							$this->set_df_widget_settings($_POST['df_settings']);
						} else {
							$this->set_df_widget_settings(false);
						}
					}
					
					if ((isset($_POST['widget-rss'])) && (isset($_GET['page'])) && ($_GET['page'] == "dashboard-feeds")) {

						$df_widgets = array();
						
						foreach($_POST['widget-rss'] as $widget_id => $widget_options) {
							if (substr($widget_id, 0, 3) == "df-") {
								
								if (!isset($widget_options['items']))
									$widget_options['items'] = '5';
								
								if (!isset($widget_options['show_summary']))
									$widget_options['show_summary'] = '';
								if (!isset($widget_options['show_author']))
									$widget_options['show_author'] = '';
								if (!isset($widget_options['show_date']))
									$widget_options['show_date'] = '';

								$df_widgets[$widget_id] = $widget_options;
							}
						}
						//echo "df_widgets<pre>"; print_r($df_widgets); echo "</pre>";
						
						if (count($df_widgets)) {
							$df_settings 		= $this->get_df_widget_settings();
							$wp_widgets_current = get_option('dashboard_widget_options');
							
							$df_widgets_current = $this->get_df_feed_widgets_options();
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
								//echo "cache_key=[". $cache_key ."]<br />";
					            delete_transient( $cache_key );					            
						    }

							update_option('dashboard_widget_options', $wp_widgets_current);						
							//update_option('wpmudev_df_widget_options', $df_widgets_current);
							
							$this->set_df_feed_widgets_options($df_widgets_current);
							
						} else {
							//delete_option('wpmudev_df_widget_options');
							$this->set_df_feed_widgets_options('');
						}
					}
				?>
				
				<form id="dashboard-feeds-form" method="post" action="">
					<input name="df-form-submit" value="1" type="hidden" />
					<?php 
						$df_settings = $this->get_df_widget_settings(); //get_option('dashboard_widget_settings');					
						$df_widgets = $this->get_df_feed_widgets_options(); //get_option('wpmudev_df_widget_options');
						if ((!$df_widgets) || (!is_array($df_widgets)))
							$df_widgets = array();
 					?>
					<?php 
					global $wp_version;
					$version_compare = version_compare($wp_version, '3.7.1');
					if (0 >= $version_compare) {
						if (!isset($df_widgets['df-dashboard_primary'])) { 
							$wp_widgets = get_option('dashboard_widget_options');

							if (isset($wp_widgets['dashboard_primary']))
								$df_widgets['df-dashboard_primary'] = $wp_widgets['dashboard_primary'];
						}
						
						if (isset($df_widgets['df-dashboard_primary'])) { ?>

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
							<?php 
						} 

						if (!isset($df_widgets['df-dashboard_secondary'])) { 
							$wp_widgets = get_option('dashboard_widget_options');
							
							if (isset($wp_widgets['dashboard_secondary']))
								$df_widgets['df-dashboard_secondary'] = $wp_widgets['dashboard_secondary'];
						}
						
						if (isset($df_widgets['df-dashboard_secondary'])) { ?>					
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
							<?php 
						} 
					} else {
						$df_widgets = $this->convert_legacy_wordpress_widgets($df_widgets);
						?>
						<h2><?php _e('WordPress Primary & Secondary Feed Widgets', 'dashboard-feeds'); ?></h2>
						
						<p><?php _e('Due to changes in WordPress 3.8, the Dashboard Feeds plugin can no longer override the options defined for the WordPress Primary & Secondary RSS Widgets. If you have previously defined Primary and Secondary feeds they will be automatically converted to Extra feed configurations below. You can however chose to hide the default feed widget from WordPress by setting the checkbox below.', 'dashboard-feeds'); ?></p>
						<p><input type="checkbox" name="df_settings[hide-default-wordpress-feed-widget]" id="df-settings-hide-default-wordpress-feed-widget" <?php if (isset($df_settings['hide-default-wordpress-feed-widget'])) { echo ' checked="checked" '; } ?> /> <label for="df-settings-hide-default-wordpress-feed-widget"><?php _e('Set to hide default WordPress Feed box.')?></label></p>
						<?php
						
						
					}
					//echo "df_widgets<pre>"; print_r($df_widgets); echo "</pre>";
					?>
					
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

										if (!isset($widget_options['show-on'])) {
											$widget_options['show-on'] = array();
											$widget_options['show-on']['network'] = 'on';
											$widget_options['show-on']['site'] = 'on';
										}
										
										?>
										<div class="df-form_section">
											<p><label for="rss-link-<?php echo $widget_options['number']; ?>"><?php _e('Enter Site URL here:'); ?></label>
											<input class="widefat" id="rss-link-<?php echo $widget_options['number']; ?>" 
												name="widget-rss[<?php echo $widget_options['number']; ?>][link]" type="text" 
												value="<?php echo $widget_options['link']; ?>" /></p>
											<?php wp_widget_rss_form($widget_options); ?>
											
											<p><input type="checkbox" name="widget-rss[<?php echo $widget_options['number'] ?>][show-on][site]" id="df-settings-show-on-site-<?php echo $widget_options['number']; ?>" <?php if (isset($widget_options['show-on']['site'])) { echo ' checked="checked" '; } ?> /> <label for="df-settings-show-on-site-<?php echo $widget_options['number']; ?>"><?php _e('Checked - Show this feed on Site Dashboard', 'dashboard-feeds'); ?></label></p>
											<?php if (is_multisite()) { ?>
												<p><input type="checkbox" name="widget-rss[<?php echo $widget_options['number']; ?>][show-on][network]" id="df-settings-show-on-network-<?php echo $widget_options['number']; ?>" <?php if (isset($widget_options['show-on']['network'])) { echo ' checked="checked" '; } ?> /> <label for="df-settings-show-on-network-<?php echo $widget_options['number']; ?>"><?php _e('Checked - Show this feed on Network Dashboard',
														 'dashboard-feeds'); ?></label></p>
											<?php } ?>
											
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
									$widget_options['number'] 				= sprintf("df-%d", count($df_widgets)+1);
									$widget_options['title']				= '';
									$widget_options['link']					= '';
									$widget_options['url']					= '';
									$widget_options['items']				= 5;
									$widget_options['show-on'] 				= array();
									$widget_options['show-on']['network'] 	= 'on';
									$widget_options['show-on']['site'] 		= 'on';
									
									?>
									<div class="df-form_section">
										<p><label for="rss-link-<?php echo $widget_options['number']; ?>"><?php _e('Enter Site URL here:'); ?></label>
										<input class="widefat" id="rss-link-<?php echo $widget_options['number']; ?>" 
											name="widget-rss[<?php echo $widget_options['number']; ?>][link]" type="text" 
											value="<?php echo $widget_options['link']; ?>" /></p>
										<?php wp_widget_rss_form($widget_options); ?>
										<p><input type="checkbox" name="widget-rss[<?php echo $widget_options['number'] ?>][show-on][site]" id="df-settings-show-on-site-<?php echo $widget_options['number']; ?>" <?php if (isset($widget_options['show-on']['site'])) { echo ' checked="checked" '; } ?> /> <label for="df-settings-show-on-site-<?php echo $widget_options['number']; ?>"><?php _e('Checked - Show this feed on Site Dashboard', 'dashboard-feeds'); ?></label></p>
										<?php if (is_multisite()) { ?>
											<p><input type="checkbox" name="widget-rss[<?php echo $widget_options['number'] ?>][show-on][network]" id="df-settings-show-on-network-<?php echo $widget_options['number']; ?>" <?php if (isset($widget_options['show-on']['network'])) { echo ' checked="checked" '; } ?> /> <label for="df-settings-show-on-network-<?php echo $widget_options['number']; ?>"><?php _e('Checked - Show this feed on Network Dashboard',
													 'dashboard-feeds'); ?></label></p>
										<?php } ?>
										
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

			$df_widgets_current = $this->get_df_feed_widgets_options();
				
			if ((!$df_widgets_current) || (!is_array($df_widgets_current)))
				$df_widgets_current = array();
			
			//echo "df_widgets_current<pre>"; print_r($df_widgets_current); echo "</pre>";
			global $wp_version;
			$version_compare = version_compare($wp_version, '3.7.1');
			if (0 >= $version_compare) {
			} else {
				$df_widgets_current = $this->convert_legacy_wordpress_widgets($df_widgets_current);
			}

			foreach($df_widgets_current as $widget_id => $widget_options) {
				// IF we still have them, ignore.
				if (($widget_id == 'df-dashboard_primary') || ($widget_id == 'df-dashboard_secondary')) {
					continue;
				} else {
					if ((is_multisite()) && (is_network_admin())) {
						if ((!isset($widget_options['show-on'])) || (isset($widget_options['show-on']['network']))) {
							$widget_items[$widget_id] = new WPMUDEV_Dashboard_Feed_Widget();
							$widget_items[$widget_id]->init($widget_id, $widget_options);							
						}
					} else {
						if ((!isset($widget_options['show-on'])) || (isset($widget_options['show-on']['site']))) {
							$widget_items[$widget_id] = new WPMUDEV_Dashboard_Feed_Widget();
							$widget_items[$widget_id]->init($widget_id, $widget_options);							
						}
					}
				}
			}
		}
		
		function convert_legacy_wordpress_widgets($df_widgets = array()) {
			if (isset($df_widgets['df-dashboard_primary'])) { 
				$count = 0;
				foreach($df_widgets as $widget_id => $widget_options) {
					if (($widget_id != 'df-dashboard_primary') && ($widget_id != 'df-dashboard_secondary')) {
						$count += 1;
					}
				}
				$new_widget_id = 'df-'. sprintf("df-%d", intval($count)+1);
				$df_widgets[$new_widget_id] = $df_widgets['df-dashboard_primary'];
				unset($df_widgets['df-dashboard_primary']);
			}
			
			if (isset($df_widgets['df-dashboard_secondary'])) {
				$count = 0;
				foreach($df_widgets as $widget_id => $widget_options) {
					if (($widget_id != 'df-dashboard_primary') && ($widget_id != 'df-dashboard_secondary')) {
						$count += 1;
					}
				}
				$new_widget_id = 'df-'. sprintf("df-%d", intval($count)+1);
				$df_widgets[$new_widget_id] = $df_widgets['df-dashboard_secondary'];
				unset($df_widgets['df-dashboard_secondary']);
			}
			return $df_widgets;
		}
		
		function get_df_feed_widgets_options() {
			if (is_multisite()) {
				global $current_blog;
				//echo "current_blog<pre>"; print_r($current_blog); echo "</pre>";
				
				if ($current_blog->site_id == $current_blog->blog_id) {
					$df_widgets = get_blog_option($current_blog->site_id, 'wpmudev_df_widget_options');
					if (!is_array($df_widgets)) {
						$df_widgets = get_option('wpmudev_df_widget_options');
					}
				
				} else {
					$df_widgets = get_blog_option($current_blog->site_id, 'wpmudev_df_widget_options');				
				}
			} else {
				$df_widgets = get_option('wpmudev_df_widget_options');
			}
			
			return $df_widgets;
		}

		function set_df_feed_widgets_options($df_widgets) {
			if (is_multisite()) {
				global $current_blog;
			
				if (is_array($df_widgets))
					update_blog_option($current_blog->site_id, 'wpmudev_df_widget_options', $df_widgets);
				else
					delete_blog_option($current_blog->site_id, 'wpmudev_df_widget_options');
			} else {
				if (is_array($df_widgets))
					update_option('wpmudev_df_widget_options', $df_widgets);
				else
					delete_option('wpmudev_df_widget_options');				
			}
		}
		
		function get_df_widget_settings() {
			if (is_multisite()) {
				global $current_blog;
			
				if ($current_blog->site_id == $current_blog->blog_id) {
					$df_settings = get_blog_option($current_blog->site_id, 'dashboard_widget_settings');
					if (!is_array($df_settings)) {
						$df_settings = get_option('dashboard_widget_settings');
					}
				
				} else {
					$df_settings = get_blog_option($current_blog->site_id, 'dashboard_widget_settings');				
				}
			} else {
				$df_settings = get_option('dashboard_widget_settings');
			}
			return $df_settings;
		}
		
		function set_df_widget_settings($df_settings) {
			if (is_multisite()) {
				global $current_blog;
			
				if (is_array($df_settings))
					update_blog_option($current_blog->site_id, 'dashboard_widget_settings', $df_settings);
				else
					delete_blog_option($current_blog->site_id, 'dashboard_widget_settings');
			} else {
				if (is_array($df_settings))
					update_option('dashboard_widget_settings', $df_settings);
				else
					delete_option('dashboard_widget_settings');				
			}
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
				/* array(&$this, 'wp_dashboard_widget_controls')  */
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
