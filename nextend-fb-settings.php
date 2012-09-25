<?php
/*
Nextend FB Connect Settings Page
*/

$newfb_status = "normal";

if(isset($_POST['newfb_update_options'])) {
	if($_POST['newfb_update_options'] == 'Y') {
		update_option("nextend_fb_connect", maybe_serialize($_POST));
		$newfb_status = 'update_success';
	}
}

if(!class_exists('NextendFBSettings')) {
class NextendFBSettings {
function NextendFB_Options_Page() {
  $domain = get_option('siteurl');
  $domain = str_replace(array('http://', 'https://'), array('',''), $domain);
  $domain = str_replace('www.', '', $domain);
  $a = explode("/",$domain);
  $domain = $a[0]; 
	?>

	<div class="wrap">
	<div id="newfb-options">
	<div id="newfb-title"><h2>Nextend Facebook Connect Settings</h2></div>
	<?php
	global $newfb_status;
	if($newfb_status == 'update_success')
		$message =__('Configuration updated', 'nextend-fb-connect') . "<br />";
	else if($newfb_status == 'update_failed')
		$message =__('Error while saving options', 'nextend-fb-connect') . "<br />";
	else
		$message = '';

	if($message != "") {
	?>
		<div class="updated"><strong><p><?php
		echo $message;
		?></p></strong></div><?php
	} ?>
	<div id="newfb-desc">
	<p><?php _e('This plugins helps you create Facebook login and register buttons. The login and register process only takes one click and you can fully customize the buttons with images and other assets.', 'nextend-fb-connect'); ?></p>
	<h3><?php _e('Setup', 'nextend-fb-connect'); ?></h3>
  <p>
  <?php _e('<ol><li><a href="https://developers.facebook.com/apps/?action=create" target="_blank">Create a facebook app!</a></li>', 'nextend-fb-connect'); ?>
  <?php _e('<li>Choose an App Name, it can be anything you like</li>', 'nextend-fb-connect'); ?>
  <?php _e('<li>Click on <b>Continue</b></li>', 'nextend-fb-connect'); ?>
  <?php _e('<li>Go to the newly created <b>App settings page</b> and click <b>Edit Settings</b></li>', 'nextend-fb-connect'); ?>
  <?php _e('<li>Fill out <b>App Domains</b> field with: <b>'.$domain.'</b></li>', 'nextend-fb-connect'); ?>
  <?php _e('<li>Click on <b>Website with Facebook Login</b> tab abd fill out <b>Site URL</b> field with: <b>'.get_option('siteurl').'</b></li>', 'nextend-fb-connect'); ?>
  <?php _e('<li>Click on <b>Save changes</b> and the top of the page contains the <b>App Id</b> and <b>App secret</b> which you have to copy and past below.</li>', 'nextend-fb-connect'); ?>
  <?php _e('<li><b>Save changes!</b></li></ol>', 'nextend-fb-connect'); ?>
  
  
  </p>
  <h3><?php _e('Usage', 'nextend-fb-connect'); ?></h3>
  <h4><?php _e('Simple link', 'nextend-fb-connect'); ?></h4>
	<p><?php _e('&lt;a href="'.get_option('siteurl').'/loginFacebook?redirect='.get_option('siteurl').'" onclick="window.location = \''.get_option('siteurl').'/loginFacebook?redirect=\'+window.location.href; return false;"&gt;Click here to login or register with Facebook&lt;/a&gt;', 'nextend-fb-connect'); ?></p>
	
  <h4><?php _e('Image button', 'nextend-fb-connect'); ?></h4>
	<p><?php _e('&lt;a href="'.get_option('siteurl').'/loginFacebook?redirect='.get_option('siteurl').'" onclick="window.location = \''.get_option('siteurl').'/loginFacebook?redirect=\'+window.location.href; return false;"&gt; &lt;img src="HereComeTheImage" /&gt; &lt;/a&gt;', 'nextend-fb-connect'); ?></p>
  
  <h3><?php _e('Note', 'nextend-fb-connect'); ?></h3>
  <p><?php _e('If the Facebook user\'s email address already used by another member of your site, the facebook profile will be automatically linked to the existing profile!', 'nextend-fb-connect'); ?></p>
  
  </div>

	<!--right-->
	<div class="postbox-container" style="float:right;width:30%;">
	<div class="metabox-holder">
	<div class="meta-box-sortables">

	<!--about-->
	<div id="newfb-about" class="postbox">
	<h3 class="hndle"><?php _e('About this plugin', 'nextend-fb-connect'); ?></h3>
	<div class="inside"><ul>
	<li><a href="http://wordpress.org/extend/plugins/nextend-facebook-connect/"><?php _e('Plugin URI', 'nextend-fb-connect'); ?></a></li>
	<li><a href="http://profiles.wordpress.org/nextendweb" target="_blank"><?php _e('Author URI', 'nextend-fb-connect'); ?></a></li>
	</ul></div>
	</div>
	<!--about end-->

	<!--others-->
	<!--others end-->

	</div></div></div>
	<!--right end-->

	<!--left-->
	<div class="postbox-container" style="float:left;width: 69%;">
	<div class="metabox-holder">
	<div class="meta-box-sortabless">

	<!--setting-->
	<div id="newfb-setting" class="postbox">
	<h3 class="hndle"><?php _e('Settings', 'nextend-fb-connect'); ?></h3>
	<?php $nextend_fb_connect = maybe_unserialize(get_option('nextend_fb_connect')); ?>

	<form method="post" action="<?php echo get_bloginfo("wpurl"); ?>/wp-admin/options-general.php?page=nextend-fb-connect">
	<input type="hidden" name="newfb_update_options" value="Y">

	<table class="form-table">
		<tr>
		<th scope="row"><?php _e('Facebook App ID:', 'nextend-fb-connect'); ?></th>
		<td>
		<input type="text" name="fb_appid" value="<?php echo $nextend_fb_connect['fb_appid']; ?>" />
		</td>
		</tr>

		<tr>
		<th scope="row"><?php _e('Facebook App Secret:', 'nextend-fb-connect'); ?></th>
		<td>
		<input type="text" name="fb_secret" value="<?php echo $nextend_fb_connect['fb_secret']; ?>" />
		</td>
		</tr>
	</table>

	<p class="submit">
	<input style="margin-left: 10%;" type="submit" name="Submit" value="<?php _e('Save Changes', 'nextend-fb-connect'); ?>" />
	</p>
	</form>
	</div>
	<!--setting end-->

	<!--others-->
	<!--others end-->

	</div></div></div>
	<!--left end-->

	</div>
	</div>
	<?php
}

function NextendFB_Menu() {
	add_options_page(__('Nextend FB Connect'), __('Nextend FB Connect'), 'manage_options', 'nextend-fb-connect', array(__CLASS__,'NextendFB_Options_Page'));
}

}
}
?>
