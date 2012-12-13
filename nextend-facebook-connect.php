<?php
/*
Plugin Name: Nextend Facebook Connect
Plugin URI: http://nextendweb.com/
Description: This plugins helps you create Facebook login and register buttons. The login and register process only takes one click.
Version: 1.4.31
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
  if(!session_id())
    session_start();
}

function new_fb_end_session() {
  if(session_id())
    session_destroy();
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
  $wp->add_query_var('editProfileRedirect');
  $wp->add_query_var('loginFacebook');
  $wp->add_query_var('loginFacebookdoauth');
}
add_filter('init', 'new_fb_add_query_var');

/* -----------------------------------------------------------------------------
  Main function to handle the Sign in/Register/Linking process
----------------------------------------------------------------------------- */

/*
  Compatibility for older versions
*/
add_action('parse_request', new_fb_login_compat);
function new_fb_login_compat(){
  global $wp;
  if($wp->request == 'loginFacebook' || isset($wp->query_vars['loginFacebook']) ){
    new_fb_login_action();
  }
}

/*
  For login page
*/
add_action('login_init', new_fb_login);
function new_fb_login(){
  if($_REQUEST['loginFacebook'] == '1'){
    new_fb_login_action();
  }
}

function new_fb_login_action(){
  global $wp, $wpdb, $new_fb_settings;
  require(dirname(__FILE__).'/sdk/init.php');
  
  $user = $facebook->getUser();
  
  if ($user && is_user_logged_in() && new_fb_is_user_connected()) {
    header( 'Location: '.$_GET['redirect'] ) ;
    exit;
  }elseif($user){
    // Register or Login
    try {
      // Proceed knowing you have a logged in user who's authenticated.
      $user_profile = $facebook->api('/me');
      $ID = $wpdb->get_var($wpdb->prepare('
        SELECT ID FROM '.$wpdb->prefix.'social_users WHERE type = "fb" AND identifier = "%d"
      ',$user_profile['id']));
      if(!get_user_by('id',$ID)){
        $wpdb->query($wpdb->prepare('
          DELETE FROM '.$wpdb->prefix.'social_users WHERE ID = "%d"
        ', $ID));
        $ID = null;
      }
      if(!is_user_logged_in()){
        if($ID == NULL){ // Register
          if(!isset($user_profile['email'])) $user_profile['email'] = $user_profile['username'].'@facebook.com';
          $ID = email_exists($user_profile['email']);
          if($ID == false){ // Real register
            require_once( ABSPATH . WPINC . '/registration.php');
            $random_password = wp_generate_password( $length=12, $include_standard_special_chars=false );
            
            if(!isset($new_fb_settings['fb_user_prefix'])) $new_fb_settings['fb_user_prefix'] = 'facebook-';
            $sanitized_user_login = sanitize_user($new_fb_settings['fb_user_prefix'].$user_profile['username']);
            if(!validate_username($sanitized_user_login)){
              $sanitized_user_login = sanitize_user('facebook'.$user_profile['id']);
            }
            $defaul_user_name = $sanitized_user_login;
            $i = 1;
            while(username_exists($sanitized_user_login)){
              $sanitized_user_login = $defaul_user_name.$i;
              $i++;
            }
            
            $ID = wp_create_user($sanitized_user_login, $random_password, $user_profile['email'] );
            if(!is_wp_error($ID)){
            	wp_new_user_notification($ID, $random_password);
              wp_update_user(array(
                'ID' => $ID, 
                'display_name' => $user_profile['name'], 
                'first_name' => $user_profile['first_name'], 
                'last_name' => $user_profile['last_name']
              ));
              update_user_meta( $ID, 'fb_profile_picture', 'https://graph.facebook.com/'.$user_profile['id'].'/picture?type=large');
            }else{
              return;
            }
          }
          if($ID){
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
          if(isset($new_fb_settings['fb_redirect_reg']) && $new_fb_settings['fb_redirect_reg'] != '' && $new_fb_settings['fb_redirect_reg'] != 'auto'){
            $_SESSION['redirect'] = $new_fb_settings['fb_redirect_reg'];
          }
        }
        
        if($ID){ // Login
          $secure_cookie = is_ssl();
          $secure_cookie = apply_filters('secure_signon_cookie', $secure_cookie, array());
          global $auth_secure_cookie; // XXX ugly hack to pass this to wp_authenticate_cookie
          $auth_secure_cookie = $secure_cookie;
          
          wp_set_auth_cookie($ID, true, $secure_cookie);
          $user_info = get_userdata($ID);
          do_action('wp_login', $user_info->user_login, $user_info);
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
    $loginUrl = $facebook->getLoginUrl(array('scope' => 'email') );
    if(isset($new_fb_settings['fb_redirect']) && $new_fb_settings['fb_redirect'] != '' && $new_fb_settings['fb_redirect'] != 'auto'){
      $_GET['redirect'] = $new_fb_settings['fb_redirect'];
    }
    $_SESSION['redirect'] = isset($_GET['redirect']) ? $_GET['redirect'] : site_url();
    header( 'Location: '.$loginUrl ) ;
    exit;
  }
}

/*
  Is the current user connected the Facebook profile? 
*/
function new_fb_is_user_connected(){
  global $wpdb;
  $current_user = wp_get_current_user();
  $ID = $wpdb->get_var($wpdb->prepare('
    SELECT identifier FROM '.$wpdb->prefix.'social_users WHERE type = "fb" AND ID = "%d"
  ', $current_user->ID));
  if($ID === NULL) return false;
  return $ID;
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
        var loginForm = $.merge($('#loginform'),$('#registerform'));
        socialLogins = $('<div class="newsociallogins" style="text-align: center;"><div style="clear:both;"></div></div>');
        loginForm.prepend("<h3 style='text-align:center;'>OR</h3>");
        loginForm.prepend(socialLogins);
      }
      socialLogins.prepend('<?php echo addslashes(preg_replace('/^\s+|\n|\r|\s+$/m', '',new_fb_sign_button())); ?>');
    }(jQuery));
  });
  </script>
  <?php
}

add_action('login_form', 'new_add_fb_login_form');
add_action('register_form', 'new_add_fb_login_form');

add_filter( 'get_avatar', 'new_fb_insert_avatar', 1, 5 );
function new_fb_insert_avatar( $avatar = '', $id_or_email, $size = 96, $default = '', $alt = false ) {
  $id = 0;	
  if(is_numeric($id_or_email)){
    $id = $id_or_email;
  }else if(is_string($id_or_email)){
    $u = get_user_by('email',$id_or_email);
    $id = $u->id;
  }else if(is_object($id_or_email)){
    $id = $id_or_email->user_id;
  }
  if($id == 0) return $avatar;

  $pic = get_user_meta($id, 'fb_profile_picture', true);
  if(!$pic || $pic == '') return $avatar;
  $avatar = preg_replace('/src=("|\').*?("|\')/i', 'src="'.$pic.'"', $avatar);
  return $avatar;
}

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
  return '<a href="'.new_fb_login_url().(isset($_GET['redirect_to']) ? '&redirect='.$_GET['redirect_to'] : '').'" rel="nofollow">'.$new_fb_settings['fb_login_button'].'</a><br />';
}

function new_fb_link_button(){
  global $new_fb_settings;
  return '<a href="'.new_fb_login_url().'&redirect='.site_url().$_SERVER["REQUEST_URI"].'">'.$new_fb_settings['fb_link_button'].'</a><br />';
}

function new_fb_login_url(){
  return site_url('wp_login.php').'?loginFacebook=1';
}

function new_fb_edit_profile_redirect(){
  global $wp;
  if(isset($wp->query_vars['editProfileRedirect']) ){
    if(function_exists('bp_loggedin_user_domain')){
      header('LOCATION: '.bp_loggedin_user_domain().'profile/edit/group/1/');
    }else{
      header('LOCATION: '.self_admin_url( 'profile.php' ));
    }
    exit;
  }
}
add_action('parse_request', new_fb_edit_profile_redirect);

function new_fb_jquery(){
  wp_enqueue_script( 'jquery' );
}

add_action('login_form_login', 'new_fb_jquery');
add_action('login_form_register', 'new_fb_jquery');

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