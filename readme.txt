=== InstaTeam Instagram Importer ===
Contributors: SPARK6, abrudtkuhl
Donate link: Free
Tags: instagram, import, teams, photo, gallery
Requires at least: 3.0.1
Tested up to: 4.2.1
Stable tag: 1.1
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Import a whitelist of Instagram users and hashtagged photos into Wordpress!

== Description ==

We built this plugin out of necessity. Our agency, SPARK6, has a team distributed all over the word. Instagram is a perfect way for us to convey company culture by allowing the team to post photos with the #spark6 hashtag for anything they would like surfaced on the company site.

If you are looking for a plugin that will allow you to consolidate posts from one or more Instagram users on your Wordpress blog then this is the plugin for you!

Features:

* Ability to import one or more Instagram user accounts (whitelist) into Wordpress as custom Post Types.
* Ability to choose which photos are imported based on one or more #hashtags. You can also import tags without providing a user user whitelist.
* Ability to map imported photos to a new or pre-existing Post Type of your choosing.
* Workflow - choose the post status of all imported photos. Helpful if you want to review any photos that are imported before they post live to site. There are 3 settings: Auto-post, moderate, or private (must be logged in to view).
* Option to set all imported images to featured images.
* Ability to specify how many photos are imported per Instagram API call (20, 40, 60, 80, 100 or 200).
* Ability to set import frequency (every minute, every hour, twice daily, daily)
* Shortcode that supports custom CSS class name.
* Shortcode supports an option “photos” parameter that lets you specify the number of images to return in the grid.
* Support for responsive web breakpoints.
* When clicked, individual photos launch into a responsive lightbox (courtesy of Magnific Popup.
* Ability to unlink account at any time.

== Installation ==

**Automatic Install**

By far, the simplest way to install the plugin is to click on “Plugins” then “Add” and type “InstaTeam Instagram Importer” in the search field.
From here you can click “Install” and have Wordpress automatically install and configure the plugin for you.

**Manual Install**

1. Login to your Wordpress site and go to the Plugins section of your admin panel.
2. Click the Add New button.
3. Under Install Plugins, click the upload link.
4. Select the plugin zip file (instateam-intagram-importer.x.x.zip) from your computer then click the Install Now button.
5. You should see a message stating that the plugin was installed successfully.

**Configuration**

Once the plugin has been installed navigate to the “Plugins” page and click the “Activate Plugin” link.
You will now see a new item at the bottom of the left navigation called “InstaTeam”. Click this item to launch the plugin configuration.
Be sure that you are logged into your Instagram account, then click the “Secure Authentication” button located on this page. This will authorize your website to use the Instagram API.  Click the “Authorize” button when prompted by Instagram. Installation is complete once you are redirected back to the main plugin page.


== Screenshots ==

1. This screen shot description corresponds to screenshot-1.(png|jpg|jpeg|gif). Note that the screenshot is taken from
the /assets directory or the directory that contains the stable readme.txt (tags or trunk). Screenshots in the /assets
directory take precedence. For example, `/assets/screenshot-1.png` would win over `/tags/4.3/screenshot-1.png`
(or jpg, jpeg, gif).
2. This is the second screen shot

== Changelog ==
= 1.1 =
* Fixes bug caused by Instagram API Update http://developers.instagram.com/post/116410697261/publishing-guidelines-and-signed-requests
= 1.0 =
* Initial release.

== Upgrade Notice ==

None at this time.

== Frequently Asked Questions ==

**Can I use an existing post type with this plugin?**

Yes, the plugin supports all custom post types installed on your theme. By default all images are imported into the post type “wpteam_instagram”. You can change this setting on the Import Options tab.

**How can I import all Instagram photos for a particular user?**

If you add a username with no tags, the plugin will attempt to import all public Instagram photos for that user.

**Can I import photos from a private user or private photos?**

Unfortunately the Instagram API does not grant us access to private accounts or photos. If you attempt to add a private user to the whitelist, we will show you a warning that the user is private. All private photos will be skipped when an import occurs.
