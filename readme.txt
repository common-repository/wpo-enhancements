=== WPO Enhancements ===
Contributors: sultanicq
Tags: cdn, wpo, enhancements, speed, optimization, core web vitals, page speed insights
Requires at least: 4.9
Tested up to: 5.6
Stable tag: 2.0.10
Requires PHP: 7.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Some tricks and tips to rock our website. Depends on WP Rocket plugin. Adjust some options and improve Core Web Vitals score on Page Speed Insights.

== Description ==
This plugin customizes the effects of WPRocket plugin. Allow us to adjust the way js/css loads on the page.

Improves the scoring on PageSpeedInsights by improving the Core Web Vitals.

Visit the <a href="https://seocom.agency/">Seocom website</a> for more information about SEO or WPO optimization

== Installation ==

1. Install "WPO Enhancements" either via the WordPress.org plugin directory, or by uploading the files to your server inside the wp-content/plugins folder of your WordPress installation.
2. Activate "WPO Enhancements" plugin via WordPress Settings.
3. It's done. Easy, isn't it?

== Changelog ==
= 2.0.11 =
* Bugfix. Debug operators removed

= 2.0.10 =
* Bugfix. Unable to load resources followed by a hash symbol.

= 2.0.9 =
* Bugfix. Invalid return due to erroneous testing.

= 2.0.8 =
* Versioning fix.

= 2.0.7 =
* Bugfix. Inverted boolean condition. If site had excluded urls always will return as wprocket deactivated.

= 2.0.6 =
* Bugfix. In some cases the page could render empty.

= 2.0.5 =
* Added compatibility checkbox for plugin "Recaptcha in wp comments" on configuration page.

= 2.0.4 =
* Bugfixes
* Forces WP Rocket cache purge when updating options
* Adding User Agents clauses on WP Rocket Advanced Cache options forces full disable of WP Rocket plugin.

= 2.0.3 =
* Bugfixes
* Detection of WP Rocket plugin enabled.
* Guard clauses moved inside functions

= 2.0.2 =
* Bugfixes
* Improved methods while detecting WP Rocket settings.

= 2.0.1 =
* Bugfix. loadExt library now loads the right way.

= 2.0.0 =
* Initial Release.
