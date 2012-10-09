<?php
/*
Plugin Name: Nextend Facebook Connect
Plugin URI: http://nextendweb.com/
Description: This plugins helps you create Facebook login and register buttons. The login and register process only takes one click.
Version: 1.3.1
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

define( 'NEW_FB_LOGIN', 1 );
if ( ! defined( 'NEW_FB_LOGIN_PLUGIN_BASENAME' ) )
	define( 'NEW_FB_LOGIN_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );
  
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
  global $wp, $wpdb;
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
        if(!is_user_logged_in()){
          if($ID == NULL){ // Register
            $ID = email_exists($user_profile['email']);
            if($ID == false){ // Real register
              $random_password = wp_generate_password( $length=12, $include_standard_special_chars=false );
              $ID = wp_create_user( 'Facebook - '.$user_profile['name'], $random_password, $user_profile['email'] );
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
  if($new_social_header === NULL){
    ?>
    <h3>Social connect</h3>
    <?php
    $new_social_header = true;
  }
  ?>
  <table class="form-table">
    <tbody>
      <tr>	
        <th>
          <label>Link Facebook with this profile</label>
        </th>	
        <td>
          <?php if(!new_fb_is_user_connected()): ?>
            <a href="<?php echo new_fb_login_url().'&redirect='.site_url().$_SERVER["REQUEST_URI"]; ?>">Link Facebook with this profile</a>
          <?php else: ?>
          Already connected
          <?php endif; ?>
        </td>
      </tr>
    </tbody>
  </table>
  <?php
}
add_action('profile_personal_options', 'new_add_fb_connect_field');


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