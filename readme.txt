=== Multi Networks Setup ===
Contributors: madpixels
Donate link:
Tags: multisite, networks, network, blog, blogs, site, sites, domain, domains, mapping, domain mapping
Requires at least: 3.0
Tested up to: 3.9.1
Stable tag: 1.0.0
License: GPLv2 or later
License URI: http://www.opensource.org/licenses/gpl-license.php

The WordPress Multi Networks Setup plugin allows site owners to create multiple networks based on your WordPress 3.0 network installation.

== Description ==

The plugin allows you to create multiple networks based on your WordPress 3.0 network installation. It could be used when you want to create a couple of networks with similar functionality. For instance, you want to create a SaaS based platform which allows auto travellers to blog while travel. Additionally you want to launch the same network, but only for sailors. In this case you can easily create a new network by using this plugin and both networks will share the same functionality.

It requires manual installation as `sunrise.php` file must be copied to `wp-content/`. When upgrading the plugin, remember to update `sunrise.php` as well.

After installation and network activation, go to your network dashboard and visip "Networks" page. You will see all available networks there (from the beginning there will be only one network). To create new network click on "Add New" button, enter new domain name, title, admin email and submit the form. New network will be created. Pay attention that a domain of a new network should be properly setup and lead to your server.

== Installation ==

1. Install the plugin in the usual way into the regular WordPress plugins folder. Network activate the plugin.
1. Move `sunrise.php` into `wp-content/` folder. If there is a `sunrise.php` there already, you'll just have to merge them as best you can.
1. Edit `wp-config.php` and uncomment or add the SUNRISE definition line. If it does not exist please ensure it's on the line above the last `require_once` command: `define( 'SUNRISE', 'on' );`.
1. Comment out the `DOMAIN_CURRENT_SITE`, `PATH_CURRENT_SITE`, `SITE_ID_CURRENT_SITE`, `BLOG_ID_CURRENT_SITE` lines in your `wp-config.php` file.

== Changelog ==

= 1.0.0 =
* The initial plugin has been created.
