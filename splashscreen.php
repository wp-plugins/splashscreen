<?php
/*
Plugin Name: SplashScreen
Plugin URI: 
Description: Show splash screen before allowing visitor to get to blog.
Author: djpushplay
Author URI: http://cochinoman.com
Version: 0.20
*/

/*
	Copyright (c) 2009 cochinoman.com.  All rights reserved. (email : info@cochinoman.com)

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
	var $version = '0.20';
	var $err = false;
	var $msg = '';
	var $allSettings = array();

	//----------------------------------------------------------------
	// Constructor
	//----------------------------------------------------------------
	function splashscreen() {
		// Display the splash if enabled
		add_action('send_headers', array('splashscreen', 'display_splash'));
		
		// Admin part below
		$data = array(
			'splashscreen_type' => '',
			'splashscreen_enable' => '',
			'splashscreen_excludedpaths' => ''
		);
						
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
			if ($splash->allSettings['splashscreen_enable']) {
				$form['splashscreen_enable'] = ' checked="checked"';
			}
			
			if (isset($splash->allSettings['splashscreen_excludedpaths'])) {
				$form['splashscreen_excludedpaths'] = $splash->WhitespaceToLinebreak($splash->allSettings['splashscreen_excludedpaths']);
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
				<th scope="row">Enable SplashScreen</th>
				<td>
					<input type="checkbox" id="splashscreen_enable" name="splashscreen_enable" value="1"<?php echo $form['splashscreen_enable']; ?> />
					<label for="splashscreen_enable">Check this box to enable your splash screen.</label>
				</td>
			</tr>
			<tr valign="top">
				<th scope="row">Select Template</th>
				<td>
					<select name="splashscreen_type">
						<?php echo $selection; ?>
					</select>
				</td>
			</tr>
			<tr valign="top">
				<th scope="row">Paths to Exclude</th>
				<td>
					Enter paths to be excluded from Splashscreen and separate multiple paths with line breaks.<br />
					If you want to exclude <em>http://mysite.com/feed/</em>, enter <em>/feed/</em>.<br />
<textarea style='width:50%;' name='splashscreen_excludedpaths' id='splashscreen_excludedpaths' rows='5' ><?php echo $form['splashscreen_excludedpaths']; ?></textarea>
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
			
			$data = array(
				'splashscreen_type' => basename(base64_decode($_POST['splashscreen_type'])),
				'splashscreen_enable' => ((int) $_POST['splashscreen_enable']),
				'splashscreen_excludedpaths' => $splash->LinebreakToWhitespace($_POST['splashscreen_excludedpaths'])
				);
			
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
	// convert white space to line breaks for display
	//----------------------------------------------------------------
	function WhitespaceToLinebreak($input) {
		$output = str_replace(' ', "\n", $input);
		return $output;
	}
	
	//----------------------------------------------------------------
	// convert line breaks to white space
	//----------------------------------------------------------------
	function LinebreakToWhitespace($input) {
		// Remove white spaces
		$input = str_replace(' ', '', $input);
	
		// Replace linebreaks with white space, considering both \n and \r
		$input = preg_replace("/\r|\n/s", ' ', $input);
	
		// Create result. We create an array and loop thru it but do not consider empty values. 
		$sourceArray = explode(' ', $input);
		$loopcount = 0;
		$result = '';
		foreach ($sourceArray as $loopval) {
			if ($loopval <> '') {
				// Create separator
				$sep = '';
				if ($loopcount >= 1) $sep = ' ';
				// result
				$result .= $sep . $loopval;
				$loopcount++;				
			}
		}
		return $result;
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
	// Excluded path
	//----------------------------------------------------------------
	function is_excluded_url() {
		global $splash;
	
		//$splash->getSettings();
		$urlarray = $splash->allSettings['splashscreen_excludedpaths'];
		$urlarray = preg_replace("/\r|\n/s", ' ', $urlarray);	// needed, otherwise explode doesn't work here
		$urlarray = explode(' ', $urlarray);		
		$oururl = 'http://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
		foreach ($urlarray as $expath) {
			if ((!empty($expath)) && (strpos($oururl, $expath) !== false)) {
				return true;
			}
		}
		return false;
	}

	//----------------------------------------------------------------
	// Display the splash screen if its enabled
	//----------------------------------------------------------------
	function display_splash() {
		global $splash;
		
		$splash->getSettings();

		if (($splash->allSettings['splashscreen_enable']) && (!is_admin()) && (!$splash->is_excluded_url()) && (!isset($_COOKIE["splash"]))) {
			// display the splash screen
			$dir = dirname(__FILE__) . '/';
			@include_once($dir . $splash->allSettings['splashscreen_type']);
			exit();
		}
	}
}

// Run the plugin

//----------------------------------------------------------------
// clear cookie
//----------------------------------------------------------------
function splashscreen_clearcookie() {
	if (isset($_POST['splashscreen_submit']) ) {
		setcookie("splash", "", time() - 3600, "/");	// delete cookie
	}
}
$splash = new splashscreen;

add_action('init', 'splashscreen_clearcookie');
?>