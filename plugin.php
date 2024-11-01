<?php
/*
Plugin Name: WPO Enhancements
Description: Some tricks and tips to rock our website. Depends on WPRocket plugin. Adjust some options and improve Core Web Vitals score on Page Speed Insights.
Version: 2.0.11
Requires at lest: 4.9
Requires PHP: 7.0
Plugin URI: https://seocom.agency
Author: David Garcia
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
*/

require_once __DIR__ . '/src/backend.php';
require_once __DIR__ . '/src/frontend.php';

$wpo_enhancements_backend = new wpo_enhancements_backend();
$wpo_enhancements_frontend = new wpo_enhancements_frontend();
