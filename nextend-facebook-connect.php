<?php
/*
Plugin Name: Nextend Facebook Connect
Plugin URI: http://nextendweb.com/
Description: This plugins helps you create Facebook login and register buttons. The login and register process only takes one click.
Version: 1.4.3
Author: Roland Soos
License: GPL2
*/

/*  Copyright 2012  Roland Soos - Nextend  (email : roland@nextendweb.com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as 
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/
global $new_fb_settings;

define( 'NEW_FB_LOGIN', 1 );
if ( ! defined( 'NEW_FB_LOGIN_PLUGIN_BASENAME' ) )
	define( 'NEW_FB_LOGIN_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

$new_fb_settings = maybe_unserialize(get_option('nextend_fb_connect'));
              
/*
  Sessions required for the profile notices 
*/
function new_fb_start_session() {
  if(!session_id()) {
      session_start();
  }
}

function new_fb_end_session() {
  session_destroy ();
}

add_action('init', 'new_fb_start_session', 1);
add_action('wp_logout', 'new_fb_end_session');
add_action('wp_login', 'new_fb_end_session');

/*
  Loading style for buttons
*/
function nextend_fb_connect_stylesheet(){
  wp_register_style( 'nextend_fb_connect_stylesheet', plugins_url('buttons/facebook-btn.css', __FILE__) );
  wp_enqueue_style( 'nextend_fb_connect_stylesheet' );
}

if(!isset($new_fb_settings['fb_load_style'])) $new_fb_settings['fb_load_style'] = 1;
if($new_fb_settings['fb_load_style']){
  add_action( 'wp_enqueue_scripts', 'nextend_fb_connect_stylesheet' );
  add_action( 'login_enqueue_scripts', 'nextend_fb_connect_stylesheet' );
  add_action( 'admin_enqueue_scripts', 'nextend_fb_connect_stylesheet' );
}  

/*
  Creating the required table on installation
*/
function nextend_fb_connect_install(){
  global $wpdb;
  
  $table_name = $wpdb->prefix . "social_users";
    
  $sql = "CREATE TABLE $table_name (
    `ID` int(11) NOT NULL,
    `type` varchar(20) NOT NULL,
    `identifier` varchar(100) NOT NULL,
    KEY `ID` (`ID`,`type`)
  );";

   require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
   dbDelta($sql);
}
register_activation_hook(__FILE__, 'nextend_fb_connect_install');

/*
  Adding query vars for the WP parser
*/
function new_fb_add_query_var(){
  global $wp;
  $wp->add_query_var('loginFacebook');
  $wp->add_query_var('loginFacebookdoauth');
}
add_filter('init', 'new_fb_add_query_var');

/* -----------------------------------------------------------------------------
  Main function to handle the Sign in/Register/Linking process
----------------------------------------------------------------------------- */
add_action('parse_request', new_fb_login);
function new_fb_login(){
  global $wp, $wpdb, $new_fb_settings;
  if($wp->request == 'loginFacebook' || isset($wp->query_vars['loginFacebook']) ){
    require(dirname(__FILE__).'/sdk/init.php');
    
    $user = $facebook->getUser();
    
    if ($user && is_user_logged_in() && new_fb_is_user_connected()) {
      header( 'Location: '.$_GET['redirect'] ) ;
      exit;
    }else{
      $loginUrl = $facebook->getLoginUrl(array('redirect_uri' => site_url('index.php').'?loginFacebookdoauth=1') );
      $_SESSION['redirect'] = isset($_GET['redirect']) ? $_GET['redirect'] : site_url();
      header( 'Location: '.$loginUrl ) ;
      exit;
    }
  }elseif($wp->request == 'loginFacebook/doauth' || isset($wp->query_vars['loginFacebookdoauth'])){
    require(dirname(__FILE__).'/sdk/init.php');
    $user = $facebook->getUser();
    if($user){
      // Register or Login
      try {
        // Proceed knowing you have a logged in user who's authenticated.
        $user_profile = $facebook->api('/me');
        $ID = $wpdb->get_var($wpdb->prepare('
          SELECT ID FROM '.$wpdb->prefix.'social_users WHERE type = "fb" AND identifier = "'.$user_profile['id'].'"
        '));
        if(!get_user_by('id',$ID)){
          $wpdb->query($wpdb->prepare('
            DELETE FROM '.$wpdb->prefix.'social_users WHERE ID = "'.$ID.'"
          '));
          $ID = null;
        }
        if(!is_user_logged_in()){
          if($ID == NULL){ // Register
            $ID = email_exists($user_profile['email']);
            if($ID == false){ // Real register
              $random_password = wp_generate_password( $length=12, $include_standard_special_chars=false );
              $settings = maybe_unserialize(get_option('nextend_fb_connect'));
              
              if(!isset($settings['fb_user_prefix'])) $settings['fb_user_prefix'] = 'facebook-';
              if(!isset($user_profile['email'])) $user_profile['email'] = $user_profile['id'].'@facebook.com';
              $ID = wp_create_user( $settings['fb_user_prefix'].$user_profile['name'], $random_password, $user_profile['email'] );
            }
            $wpdb->insert( 
            	$wpdb->prefix.'social_users', 
            	array( 
            		'ID' => $ID, 
            		'type' => 'fb',
                'identifier' => $user_profile['id']
            	), 
            	array( 
            		'%d', 
            		'%s',
                '%s'
            	) 
            );
          }
          
          if($ID){ // Login
            wp_set_auth_cookie($ID, true, false);
            do_action('wp_login', $settings['fb_user_prefix'].$user_profile['name']);
            header( 'Location: '.$_SESSION['redirect'] );
            unset($_SESSION['redirect']);
            exit;
          }
        }else{
          $current_user = wp_get_current_user();
          if($current_user->ID == $ID){ // It was a simple login
            header( 'Location: '.$_SESSION['redirect'] );
            unset($_SESSION['redirect']);
            exit;
          }elseif($ID === NULL){  // Let's connect the accout to the current user!
            $wpdb->insert( 
            	$wpdb->prefix.'social_users', 
            	array( 
            		'ID' => $current_user->ID, 
            		'type' => 'fb',
                'identifier' => $user_profile['id']
            	), 
            	array( 
            		'%d', 
            		'%s',
                '%s'
            	) 
            );
            $_SESSION['new_fb_admin_notice'] = __('Your Facebook profile is successfully linked with your account. Now you can sign in with Facebook easily.', 'nextend-facebook-connect');
            header( 'Location: '.$_SESSION['redirect'] );
            unset($_SESSION['redirect']);
            exit;
          }else{
            $_SESSION['new_fb_admin_notice'] = __('This Facebook profile is already linked with other account. Linking process failed!', 'nextend-facebook-connect');
            header( 'Location: '.$_SESSION['redirect'] );
            unset($_SESSION['redirect']);
            exit;
          }
        }
        exit;
      } catch (FacebookApiException $e) {
        echo '<pre>'.htmlspecialchars(print_r($e, true)).'</pre>';
        $user = null;
      }
      exit;
    }else{
      echo "There was an error with the FB auth!\n";
      exit;
    }
  }
}

/*
  Is the current user connected the Facebook profile? 
*/
function new_fb_is_user_connected(){
  global $wpdb;
  $current_user = wp_get_current_user();
  $ID = $wpdb->get_var($wpdb->prepare('
    SELECT ID FROM '.$wpdb->prefix.'social_users WHERE type = "fb" AND ID = "'.$current_user->ID.'"
  '));
  if($ID === NULL) return false;
  return true;
}

/*
  Connect Field in the Profile page
*/
function new_add_fb_connect_field() {
  global $new_is_social_header;
  if($new_is_social_header === NULL){
    ?>
    <h3>Social connect</h3>
    <?php
    $new_is_social_header = true;
  }
  ?>
  <table class="form-table">
    <tbody>
      <tr>	
        <th>
        </th>	
        <td>
          <?php if(!new_fb_is_user_connected()): ?>
            <?php echo new_fb_link_button() ?>
          <?php endif; ?>
        </td>
      </tr>
    </tbody>
  </table>
  <?php
}
add_action('profile_personal_options', 'new_add_fb_connect_field');

function new_add_fb_login_form(){
  ?>
  <script>
  var has_social_form = false;
  var socialLogins = null;
  jQuery(document).ready(function(){
    (function($) {
      if(!has_social_form){
        has_social_form = true;
        var loginForm = $('#loginform');
        socialLogins = $('<div class="newsociallogins" style="text-align: center;"><div style="clear:both;"></div></div>');
        loginForm.prepend("<h3 style='text-align:center;'>OR</h3>");
        loginForm.prepend(socialLogins);
      }
      socialLogins.prepend('<?php echo addslashes(new_fb_sign_button()); ?>');
    }(jQuery));
  });
  </script>
  <?php
}

add_action('login_form', 'new_add_fb_login_form');

/*if(isset($new_fb_settings['fb_import_avatar']) && $new_fb_settings['fb_import_avatar']){
	add_filter( 'get_avatar', 'new_fb_insert_avatar', 100, 5 );
  
  function new_fb_insert_avatar( $avatar, $id_or_email, $size, $default, $alt ) {	
		
    if ( strpos( $default, $this->url ) !== false ) {
			$email = empty( $email ) ? 'nobody' : md5( $email );
			
			// 'www' version for WP2.9 and older
			if ( strpos( $default, 'http://0.gravatar.com/avatar/') === 0 || strpos( $default, 'http://www.gravatar.com/avatar/') === 0 )
				$avatar = str_replace( $default, 'asd'."&size={$size}x{$size}", $avatar );

			//otherwise, just swap the placeholder with the hash
			$avatar = str_replace( 'emailhash', $email, $avatar );
			
			//this is ugly, but has to be done
			//make sure we pass the correct size params to the generated avatar
			$avatar = str_replace( '%3F', "%3Fsize={$size}x{$size}%26", $avatar );
			
		}

  file_put_contents(dirname(__FILE__).'/asd.txt', $avatar."\n",  FILE_APPEND);
		return $avatar;
	}
}*/

/* 
  Options Page 
*/
require_once(trailingslashit(dirname(__FILE__)) . "nextend-facebook-settings.php");

if(class_exists('NextendFBSettings')) {
	$nextendfbsettings = new NextendFBSettings();
	
	if(isset($nextendfbsettings)) {
		add_action('admin_menu', array(&$nextendfbsettings, 'NextendFB_Menu'), 1);
	}
}

add_filter( 'plugin_action_links', 'new_fb_plugin_action_links', 10, 2 );

function new_fb_plugin_action_links( $links, $file ) {
  if ( $file != NEW_FB_LOGIN_PLUGIN_BASENAME )
  	return $links;
	$settings_link = '<a href="' . menu_page_url( 'nextend-facebook-connect', false ) . '">'
		. esc_html( __( 'Settings', 'nextend-facebook-connect' ) ) . '</a>';

	array_unshift( $links, $settings_link );

	return $links;
}

/* -----------------------------------------------------------------------------
  Miscellaneous functions
----------------------------------------------------------------------------- */
function new_fb_sign_button(){
  global $new_fb_settings;
  return '<a href="'.new_fb_login_url().'">'.$new_fb_settings['fb_login_button'].'</a><br />';
}

function new_fb_link_button(){
  global $new_fb_settings;
  return '<a href="'.new_fb_login_url().'&redirect='.site_url().$_SERVER["REQUEST_URI"].'">'.$new_fb_settings['fb_link_button'].'</a><br />';
}


function new_fb_login_url(){
  return site_url('index.php').'?loginFacebook=1';
}


/*
  Session notices used in the profile settings
*/
function new_fb_admin_notice(){
  if(isset($_SESSION['new_fb_admin_notice'])){
    echo '<div class="updated">
       <p>'.$_SESSION['new_fb_admin_notice'].'</p>
    </div>';
    unset($_SESSION['new_fb_admin_notice']);
  }
}
add_action('admin_notices', 'new_fb_admin_notice');