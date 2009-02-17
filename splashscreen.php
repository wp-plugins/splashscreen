<?php
/*
Plugin Name: SplashScreen
Plugin URI: http://cochinoman.com/2009/02/16/wordpress-plugin-for-splash-screen/
Description: Show splash screen before allowing visitor to get to blog.
Author: djpushplay
Author URI: http://cochinoman.com
Version: 0.01
*/

/*  Copyright (c) 2009 Djpushplay.com.  All rights reserved. (email : info@cochinoman.com)

	This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

class splashscreen {
	//----------------------------------------------------------------
	// Variables
	//----------------------------------------------------------------
	var $version = '0.01';
	var $err = false;
	var $msg = '';
	var $allSettings = array();
	
	//----------------------------------------------------------------
	// delete cookie
	//----------------------------------------------------------------


	//----------------------------------------------------------------
	// Constructor
	//----------------------------------------------------------------
	function splashscreen() {
		// Display the splash if enabled
		add_action('send_headers', array('splashscreen', 'display_splash'));
		
		// Admin part below
		$data = array(	'splashscreen_type' => '',
						'splashscreen_enable' => '');
						
		add_option('splashscreenSettings',$data,'SplashScreen Settings');
		add_action('admin_menu', array('splashscreen', 'addSettingsPage'));
	}

	//----------------------------------------------------------------
	// Get all the settings data
	//----------------------------------------------------------------
	function getSettings() {
		global $splash;
		
		$splash->allSettings = get_option('splashscreenSettings');
	}

	//----------------------------------------------------------------
	// Show the settings link for this plugin
	//----------------------------------------------------------------
	function addSettingsPage() {
		//function clearcookie() {
		//	if (isset($_POST['splashscreen_submit']) ) {
		//		setcookie("splash", "", time() - 3600, "/");
		//	}
		//}

		if (function_exists('add_options_page')) {
			$plugin_page = add_options_page('SplashScreen', 'SplashScreen', 8, basename(__FILE__), array('splashscreen', 'optionPage'));
			//add_action('admin_head-'. $plugin_page, 'clearcookie');
		}
	}

	//----------------------------------------------------------------
	// Show the options of the settings
	//----------------------------------------------------------------
	function optionPage() {
		global $splash;
		
		// User is authorized
		if ($splash->isAuthorized() ) {
			
			// Update the settings when the submit button is pressed
			$splash->updateSettings();
			$splash->getSettings();
			$form = array();

			// Fill the settings to the form
			if( $splash->allSettings['splashscreen_enable'] ) {
				$form['splashscreen_enable'] = ' checked="checked"';
			}

			$selection = $splash->getAvailableSplash($splash->allSettings['splashscreen_type']);

			// Got msg to display?
			$splash->msgBox();

			// ---- Start HTML for settings page ----
		?>
<div class="wrap">
	<h2>SplashScreen Settings</h2>
	<p>This plugin will display a splash screen before the visitor is allowed to get to the blog.</p>
	<form action="" method="post">
				
		<table class="form-table">
			<tr valign="top">
				<th scope="row">Enable Splash Screen</th>
				<td>
					<input type="checkbox" id="splashscreen_enable" name="splashscreen_enable" value="1"<?php echo $form['splashscreen_enable']; ?> />
					<label for="splashscreen_enable">Check this box to enable your splash screen.</label>
				</td>
			</tr>
			<tr valign="top">
				<th scope="row">Select Splash Screen</th>
				<td>
					<select name="splashscreen_type">
						<?php echo $selection; ?>
					</select>
				</td>
			</tr>
		</table>
		<p class="submit">
			<input type="submit" name="splashscreen_submit" value="Update" class="button" />
		</p>
	</form>
</div>
		<?php
		}
	}

	//----------------------------------------------------------------
	// Submit button pressed, update settings
	//----------------------------------------------------------------
	function updateSettings() {
		global $splash;
		
		// We update when the submit button is pressed
		if (isset($_POST['splashscreen_submit']) ) {
			//setcookie ("splash", "", time() - 3600, "/");
			
			$data = array(	'splashscreen_type' => basename(base64_decode($_POST['splashscreen_type'])),
							'splashscreen_enable' => ((int) $_POST['splashscreen_enable']));
			
			update_option('splashscreenSettings', $data);
			$splash->msg = 'Your settings have been saved.';
		}
	}

	//----------------------------------------------------------------
	// Is user authorized to perform this action?
	//----------------------------------------------------------------
	function isAuthorized() {
		global $user_level;

		if (function_exists("current_user_can")) {
			return current_user_can('activate_plugins');
		} else {
			return $user_level > 5;
		}
	}

	//----------------------------------------------------------------
	// Get all the available splash screens user have installed
	//----------------------------------------------------------------
	function getAvailableSplash($type) {
		global $splash;
		
		// init
		$selection = '';
		
		$dir = dirname(__FILE__) . '/';	// same directory as PHP file
		
		// Error reading the folder
		if( ($handle = @opendir($dir)) == false ) {
			$splash->err = true;
			$splash->msg = 'Cannot find splash screen files in directory <code>' . $dir . '</code>.';
			return '<option value="0">---</option>';
		}
		
		// We start reading the dir
		while( false !== ($file = readdir($handle)) ) {
			$ext = substr($file, strrpos($file, '.') + 1, strlen($file));
			
			if( !is_dir($dir . $file) && strtolower($ext) == 'htm' ) {
				if( $type == $file ) 
					$selection .= '<option value="' . base64_encode($file) . '" selected="selected">' . substr($file, 0, strrpos($file, '.')) . '</option>' . "\r\n";
				else 
					$selection .= '<option value="' . base64_encode($file) . '">' . substr($file, 0, strrpos($file, '.')) . '</option>' . "\r\n";
			}
		}
		closedir($handle);
		return $selection;
	}

	//----------------------------------------------------------------
	// Display the msg box if we got msg to display
	//----------------------------------------------------------------
	function msgBox() {
		global $splash;
		
		if( $splash->msg != NULL ) {
			// ---- Start of HTML for msg box ----
		?>
			<div id="message" class="updated fade"><p><?php echo $splash->msg; ?></p></div>
		<?php
		}
	}

	//----------------------------------------------------------------
	// Display the splash screen if its enabled
	//----------------------------------------------------------------
	function display_splash() {
		global $splash;
		
		$splash->getSettings();
		
		// Run the splash screen
		if( $splash->allSettings['splashscreen_enable'] ) {
			if (!isset($_COOKIE["splash"])) {
				$dir = dirname(__FILE__) . '/';
				@include_once($dir . $splash->allSettings['splashscreen_type']);
				// That's all folks
				exit();
			}
		}
	}
}

// Run the plugin

function clearcookie() {
	if (isset($_POST['splashscreen_submit']) ) {
		setcookie("splash", "", time() - 3600, "/");	// delete cookie
	}
}
$splash = new splashscreen;

add_action('init', 'clearcookie');
?>