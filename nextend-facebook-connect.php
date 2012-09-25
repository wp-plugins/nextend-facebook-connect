<?php
/*
Plugin Name: Nextend Facebook Connect
Plugin URI: http://nextendweb.com/
Description: This plugins helps you create Facebook login and register buttons. The login and register process only takes one click.
Version: 1.0
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
  
register_activation_hook(__FILE__, 'nextend_fb_connect_install');

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
  
add_action('parse_request', new_fb_login);
function new_fb_login(){
  global $wp, $wpdb;
  if($wp->request == 'loginFacebook'){
    require(dirname(__FILE__).'/sdk/init.php');
    
    $user = $facebook->getUser();
    
    if ($user && is_user_logged_in()) {
      header( 'Location: '.$_GET['redirect'] ) ;
      exit;
    }else{
      $loginUrl = $facebook->getLoginUrl(array('redirect_uri' => site_url('loginFacebook/doauth')) );
      $_SESSION['redirect'] = isset($_GET['redirect']) ? $_GET['redirect'] : site_url();
      header( 'Location: '.$loginUrl ) ;
      exit;
    }
  }elseif($wp->request == 'loginFacebook/doauth'){
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

function new_fb_login_url(){
  return site_url('loginFacebook');
}


/* Options Page */
require_once(trailingslashit(dirname(__FILE__)) . "nextend-facebook-settings.php");

if(class_exists('NextendFBSettings')) {
	$nextendfbsettings = new NextendFBSettings();
	
	if(isset($nextendfbsettings)) {
		add_action('admin_menu', array(&$nextendfbsettings, 'NextendFB_Menu'), 1);
	}
}

add_filter( 'plugin_action_links', 'nextend_fb_plugin_action_links', 10, 2 );

function nextend_fb_plugin_action_links( $links, $file ) {
  if ( $file != NEW_FB_LOGIN_PLUGIN_BASENAME )
  	return $links;
	$settings_link = '<a href="' . menu_page_url( 'nextend-facebook-connect', false ) . '">'
		. esc_html( __( 'Settings', 'nextend-facebook-connect' ) ) . '</a>';

	array_unshift( $links, $settings_link );

	return $links;
}