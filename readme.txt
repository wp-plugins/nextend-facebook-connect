=== Nextend Facebook Connect ===
Contributors: nextendweb 
Tags: facebook, register, login, social connect, social, facebook connect
Donate link: https://www.facebook.com/nextendweb
Requires at least: 3.0
Tested up to: 3.4
Stable tag: 1.4.4
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

One click registration & login plugin for Facebook? Easy installation? Is it totally free and comes with support? Yeah!

== Description ==

Check the [DEMO](http://www.nextendweb.com/wp-login.php) on our site.

Also we created a [Social Connect button generator](http://www.nextendweb.com/social-connect-button-generator) for this plugin. This allows you to create fancy login buttons. 

Personally, I hate to fill out registration forms, waiting for confirmation e-mails, so we designed this plugin for our website. Now, we want to share this very usable plugin with everyone, for free!
 
**Why should you choose Nextend Facebook Connect plugin from the many social plugins?**

* If your visitors have a Facebook profile, they can register your site with a single click, and later to log in too.
* The previously registered users can simply attach their existing Facebook profile to their account, so in the future, they can logging in with the one social button.
* The plugin has multiple desings, so it fits all kind of websites smoothly and elegantly. - Soon
* Very simple to use.
* Fast and helpful support.
* Totally free.

If you like our stuff donate a like to our [Facebook page](https://www.facebook.com/nextendweb) or follow us on [Twitter](https://www.facebook.com/nextendweb) 

#### Usage

After you activated the plugin, the plugin will autmatically 

* add the login buttons to the WordPress login page. See screenshot #1
* add the account linking buttons to the WordPress profile page. See screenshot #2


#### Advanced usage

**Simple link**

&lt;a href="*siteurl*?loginFacebook=1&redirect=*siteurl*" onclick="window.location = \'*siteurl*?loginFacebook=1&redirect=\'+window.location.href; return false;"&gt;Click here to login or register with Facebook&lt;/a&gt;

**Image button**

&lt;a href="*siteurl*?loginFacebook=1&redirect=*siteurl*" onclick="window.location = \'*siteurl*?loginFacebook=1&redirect=\'+window.location.href; return false;"&gt; &lt;img src="HereComeTheImage" /&gt; &lt;/a&gt;

== Installation ==

1.  Extract the zip file and just drop the contents in the wp-content/plugins/ directory of your WordPress installation and then activate the Plugin from Plugins page.
2.  Create a facebook app => https://developers.facebook.com/apps/?action=create
3.  Choose an App Name, it can be anything you like
4.  Click on Continue
5.  Go to the newly created App settings page and click Edit Settings
6.  Fill out App Domains field with: your domain name
7.  Click on Website with Facebook Login tab abd fill out Site URL field with: http://yoursiteurl.com
8.  Click on Save changes and the top of the page contains the App Id and App secret which you have to copy and past below.
9.  Save changes!


== Screenshots ==

1. Our Social Connect plugins on the main WP login page
2. Our Social Connect plugins in the profile page for account linking

== Changelog ==

= 1.4.4 =
* Modified login redirect issue for wp-login.php - author Michel Weimerskirch
* Added fix redirect url support. If you leave it empty or "auto" it will try to redirect back the user to the last visited page. 

= 1.4.3 =
* Facebook Certification bugfix
 
= 1.4 =
* Added Social button generator support

= 1.3 =
* Added linking option to the profile page, so an already registered user can easily link the profile with a Facebook profile.

= 1.2 =
* Fixed a bug when the htaccess short urls not enabled.