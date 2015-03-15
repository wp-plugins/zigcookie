<?php
/*
Plugin Name: ZigCookie
Plugin URI: http://www.zigpress.com/plugins/zigcookie/
Description: ZigCookie allows your site to meet its legal obligations in the European Union by alerting visitors that cookies are used.
Version: 0.2.2
Author: ZigPress
Requires at least: 4.0
Tested up to: 4.1.1
Author URI: http://www.zigpress.com/
License: GPLv2
*/


/*
Copyright (c) 2014-2015 ZigPress

This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation Inc, 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
*/


require_once dirname(__FILE__) . '/admincallbacks.php';


if (!class_exists('zigcookie')) {


	class zigcookie {
	

		public $plugin_folder;
		public $plugin_directory;
		public $options;
		public $params;
		public $result;
		public $result_type;
		public $result_message;
		public $callback_url;
		public $themes;
	
	
		public function __construct() {
			$this->plugin_folder = get_bloginfo('wpurl') . '/' . PLUGINDIR . '/' . dirname(plugin_basename(__FILE__)) . '/';
			$this->plugin_directory = WP_PLUGIN_DIR . '/' . dirname(plugin_basename(__FILE__)) . '/';
			global $wp_version;
			if (version_compare(phpversion(), '5.2.4', '<')) wp_die('ZigCookie requires PHP 5.2.4 or newer. Please update your server.'); 
			if (version_compare($wp_version, '4.0', '<')) wp_die('ZigCookie requires WordPress 4.0 or newer. Please update your installation.'); 
			$this->themes = array(
				'black' => 'Black',
				'darkgrey' => 'Dark Grey',
/* 				'midgrey' => 'Mid Grey', */
				'lightgrey' => 'Light Grey',
				'white' => 'White',
				'red' => 'Red',
/*				'orange' => 'Orange', */
/*				'yellow' => 'Yellow', */
/*				'aqua' => 'Aqua', */
				'green' => 'Green',
				'blue' => 'Blue',
/* 				'purple' => 'Purple', */
			);
			$this->get_params();
			$this->callback_url = preg_replace('/\?.*/', '', $_SERVER['REQUEST_URI']);
			add_action('wp_enqueue_scripts', array($this, 'action_wp_enqueue_scripts'));
			add_action('wp_head', array($this, 'action_wp_head'));
			add_action('admin_init', array($this, 'action_admin_init'));
			add_action('admin_enqueue_scripts', array($this, 'action_admin_enqueue_scripts'));
			add_action('admin_menu', array($this, 'action_admin_menu'));
			add_filter('plugin_row_meta', array($this, 'filter_plugin_row_meta'), 10, 2 );
			remove_filter('the_title', 'capital_P_dangit', 11);
			remove_filter('the_content', 'capital_P_dangit', 11);
			remove_filter('comment_text', 'capital_P_dangit', 31);
			$this->options = get_option('zigcookie');
		}
	
	
		public function activate() {
			if (!$this->options = get_option('zigcookie')) { 
				$this->options = array(); 
				add_option('zigcookie', $this->options);
				$this->options['theme'] = 'black';
				$this->options['position'] = 'bottom';
				$this->options['notice'] = "Like virtually all websites we use cookies. These are required for the site to work properly.  If you continue we'll assume you are happy to use cookies on our site.";
				$this->options['privacy'] = "Click here to read our privacy statement.";
				$this->options['privacyurl'] = '/privacy/';
				$this->options['accept'] = "Click here to remove this notice.";
			}
			$this->options['delete_options_next_deactivate'] = '0'; # always reset this
			update_option("zigcookie", $this->options);
		}
	
	
		public function deactivate() {
			if ($this->options['delete_options_next_deactivate'] == '1') delete_option("zigcookie");
		}
	
	
		# ACTIONS
	
	
		public function action_wp_enqueue_scripts() {
			wp_enqueue_style('zigcookie', $this->plugin_folder . 'css/zigcookie.css', false, rand());
			wp_enqueue_script('zigcookie',	$this->plugin_folder . 'js/zigcookie.js', array('jquery'), rand(), false);
		}
		
		
		public function action_wp_head() {
			?>
			<!-- ZIGCOOKIE SCRIPT STARTS -->
			<script type="text/javascript">
			jQuery(document).ready(function(){
				jQuery('body').cookiechecker({
					"notice" : "<?php echo $this->options['notice'] ?>",
					"policy" : "<?php echo $this->options['privacy'] ?>",
					"policyurl" : "<?php echo $this->options['privacyurl'] ?>",
					"accept" : "<?php echo $this->options['accept'] ?>",
					"theme" : "<?php echo $this->options['theme'] ?>",
					"position" : "<?php echo $this->options['position'] ?>"
				});
			});
			</script>
			<!-- ZIGCOOKIE SCRIPT ENDS -->
			<?php
		}
	
	
		public function action_admin_init() {
			new zigcookie_admincallbacks(@$this->params['zigaction']);
		}
	
	
		public function action_admin_enqueue_scripts() {
			wp_enqueue_style('zigcookieadmin', $this->plugin_folder . 'css/admin.css', false, date('Ymd'));
		}
	
	
		public function action_admin_menu() {
			add_options_page('ZigCookie Options', 'ZigCookie', 'manage_options', 'zigcookie-options', array($this, 'admin_page_options'));
		}
	
	
		# FILTERS
	
	
		public function filter_plugin_row_meta($links, $file) {
			$plugin = plugin_basename(__FILE__);
			$newlinks = array(
				'<a target="_blank" href="http://www.zigpress.com/donations/">Donate</a>',
				'<a href="' . get_admin_url() . 'options-general.php?page=zigcookie-options">Settings</a>',
			);
			if ($file == $plugin) return array_merge($links, $newlinks);
			return $links;
		}
	
	
		# ADMIN CONTENT
	
	
		public function admin_page_options() {
			if (!current_user_can('manage_options')) { wp_die('You are not allowed to do this.'); }
			if ($this->result_type != '') echo $this->show_result($this->result_type, $this->result_message);
			?>
			<div class="wrap zigcookie-admin">
			<h2>ZigCookie - Settings</h2>
			<div class="wrap-left">
			<div class="col-pad">
			<p>ZigCookie allows your site to meet its legal obligations in the European Union by alerting visitors that cookies are used and informing them that by continuing to use the site, they accept that cookies will be used.</p>
			<form action="<?php echo $_SERVER['PHP_SELF']?>?page=zigcookie-options" method="post">
			<input type="hidden" name="zigaction" value="zigcookie-admin-options-update" />
			<?php wp_nonce_field('zigpress_nonce'); ?>
			<table class="form-table">
			<tr valign="top">
			<th>Colour scheme:</th>
			<td><select name="theme">
			<?php
			foreach ($this->themes as $theme => $themename) {
				?>
				<option value="<?php echo $theme ?>" <?php echo ($this->options['theme'] == $theme) ? 'selected="selected"' : '' ?> ><?php echo $themename ?></option>
				<?php
			}
			?>
			</select></td>
			</tr>
			<tr valign="top">
			<th>Position:</th>
			<td><select name="position">
			<option value="top" <?php echo ($this->options['position'] == 'top') ? 'selected="selected"' : '' ?> >Top</option>
			<option value="bottom" <?php echo ($this->options['position'] == 'bottom') ? 'selected="selected"' : '' ?> >Bottom</option>
			</select></td>
			</tr>
			<tr valign="top">
			<th>Main text:</th>
			<td><textarea class="large-text" name="notice" rows="4"><?php echo @$this->options['notice'] ?></textarea></td>
			</tr>
			<tr valign="top">
			<th>Privacy link text:</th>
			<td><input class="large-text" type="text" name="privacy" value="<?php echo @$this->options['privacy'] ?>" /></td>
			</tr>
			<tr valign="top">
			<th>Privacy policy URL:</th>
			<td><input class="large-text" type="text" name="privacyurl" value="<?php echo @$this->options['privacyurl'] ?>" /></td>
			</tr>
			<tr valign="top">
			<th>Accept link text:</th>
			<td><input class="large-text" type="text" name="accept" value="<?php echo @$this->options['accept'] ?>" /></td>
			</tr>
<!--			<tr valign="top">
			<th>Deactivation kills options:</th>
			<td><input class="checkbox" type="checkbox" name="delete_options_next_deactivate" value="1" <?php if (@$this->options['delete_options_next_deactivate'] == 1) { echo('checked="checked"'); } ?> /> <span class="description">Remove stored options on next deactivation of this plugin</span></td>
			</tr>
-->
			</table>
			<p class="submit"><input type="submit" name="Submit" class="button-primary" value="Save Changes" /></p> 
			</form>
			</div><!--col-pad-->
			</div><!--wrap-left-->
			<div class="wrap-right">
			<table class="widefat donate" cellspacing="0">
			<thead>
			<tr><th>Support this plugin!</th></tr>
			</thead>
			<tr><td>
			<form action="https://www.paypal.com/cgi-bin/webscr" method="post" target="_blank">
			<input type="hidden" name="cmd" value="_s-xclick">
			<input type="hidden" name="hosted_button_id" value="GT252NPAFY8NN">
			<input type="image" src="https://www.paypal.com/en_US/i/btn/btn_donateCC_LG.gif" border="0" name="submit" alt="PayPal - The safer, easier way to pay online!">
			<img alt="" border="0" src="https://www.paypal.com/en_US/i/scr/pixel.gif" width="1" height="1">
			</form>
			<p>If you find ZigCookie useful, please keep it free and actively developed by making a donation.</p>
			<p>Suggested donation: &euro;10 or an amount of your choice. Thanks!</p>
			</td></tr>
			</table>
			<table class="widefat donate" cellspacing="0">
			<thead>
			<tr><th><img class="icon floatRight" src="<?php echo $this->plugin_folder?>img/zp.png" />Brought to you by ZigPress</th></tr>
			</thead>
			<tr><td>
			<p><a href="http://www.zigpress.com/">ZigPress</a> is engaged in WordPress consultancy, solutions and research. We have also released a number of free and commercial plugins to support the WordPress community.</p>
			<p><a target="_blank" href="http://www.zigpress.com/plugins/zigcookie/"><img class="icon" src="<?php echo $this->plugin_folder?>img/zigcookie.png" alt="ZigCookie WordPress plugin by ZigPress" title="ZigCookie WordPress plugin by ZigPress" />&nbsp; ZigCookie page</a></p>
			<p><a target="_blank" href="http://www.zigpress.com/plugins/"><img class="icon" src="<?php echo $this->plugin_folder?>img/plugin.png" alt="WordPress plugins by ZigPress" title="WordPress plugins by ZigPress" />&nbsp; Other ZigPress plugins</a></p>
			<p><a target="_blank" href="http://www.facebook.com/zigpress"><img class="icon" src="<?php echo $this->plugin_folder?>img/facebook.png" alt="ZigPress on Facebook" title="ZigPress on Facebook" />&nbsp; ZigPress on Facebook</a></p>
			<p><a target="_blank" href="http://twitter.com/ZigPress"><img class="icon" src="<?php echo $this->plugin_folder?>img/twitter.png" alt="ZigPress on Twitter" title="ZigPress on Twitter" />&nbsp; ZigPress on Twitter</a></p>
			</td></tr>
			</table>
			</div><!--wrap-right-->
			<div class="clearer">&nbsp;</div>
			<?php
			if (@$this->options['debug'] == '1') {
				?>
				<h3>Debug Information</h3>
				<pre><?php print_r($this->options)?></pre>
				<?php
			}
			?>
			</div><!--/wrap-->
			<?php
		}
		
		
		# FUNCTIONS
		
		
		# UTILITIES
	
	
		public function get_params() {
			$this->params = array();
			foreach ($_REQUEST as $key=>$value) {
				$this->params[$key] = $value;
				if (!is_array($this->params[$key])) { $this->params[$key] = strip_tags(stripslashes(trim($this->params[$key]))); }
				# need to sanitise arrays as well really
			}
			if (!is_numeric(@$this->params['zigpage'])) { $this->params['zigpage'] = 1; }
			if ((@$this->params['zigaction'] == '') && (@$this->params['zigaction2'] != '')) { $this->params['zigaction'] = $this->params['zigaction2']; }
			$this->result = '';
			$this->result_type = '';
			$this->result_message = '';
			if ($this->result = base64_decode(@$this->params['r'])) list($this->result_type, $this->result_message) = explode('|', $this->result); # base64 for ease of encoding
		}
	
	
		public function show_result($strType, $strMessage) {
			$strOutput = '';
			if ($strMessage != '') {
				$strClass = '';
				switch (strtoupper($strType)) {
					case 'OK' :
						$strClass = 'updated';
					break;
					case 'INFO' :
						$strClass = 'updated highlight';
					break;
					case 'ERR' :
						$strClass = 'error';
					break;
					case 'WARN' :
						$strClass = 'error';
					break;
				}
				if ($strClass != '') {
					$strOutput .= '<div class="msg ' . $strClass . '" title="Click to hide"><p>' . $strMessage . '</p></div>';
				}
			}
			return $strOutput;
		}
	
	
		public function validate_as_integer($param, $default = 0, $min = -1, $max = -1) {
			if (!is_numeric($param)) $param = $default;
			$param = (int) $param;
			if ($min != -1) { if ($param < $min) $param = $min; }
			if ($max != -1) { if ($param > $max) $param = $max; }
			return $param;
		}
	
	
		function get_all_post_meta($id = 0) {
			if ($id == 0) {
				global $wp_query;
				$content_array = $wp_query->get_queried_object();
				$id = $content_array->ID;
			}
			$data = array();
			global $wpdb;
			$wpdb->query("SELECT meta_key, meta_value FROM {$wpdb->postmeta} WHERE post_id = {$id} ");
			foreach($wpdb->last_result as $k => $v) {
				$data[$v->meta_key] = $v->meta_value;
			}
			return $data;
		}
	
	
	} # END OF CLASS


} else {
	wp_die('Namespace clash! Class zigcookie already exists.');
}


# INSTANTIATE PLUGIN


$zigcookie = new zigcookie();
register_activation_hook(__FILE__, array(&$zigcookie, 'activate'));
register_deactivation_hook(__FILE__, array(&$zigcookie, 'deactivate'));


# EOF
