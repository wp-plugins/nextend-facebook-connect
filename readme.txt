=== Nextend Facebook Connect ===
Contributors: nextendweb 
Tags: facebook, register, login, social connect, social, facebook connect
Requires at least: 3.0
Tested up to: 3.4
Stable tag: 1.2

This plugins helps you create Facebook login and register buttons. The login and register process only takes one click.

== Description ==

This plugins helps you create Facebook login and register buttons. The login and register process only takes one click and you can fully customize the buttons with images and other assets.

#### Usage


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

== Changelog ==

= 1.2 =
* Fixed a bug when the htaccess short urls not enabled.