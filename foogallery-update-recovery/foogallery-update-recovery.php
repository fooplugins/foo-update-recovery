<?php
/**
 * Plugin Name: FooGallery Update Recovery
 * Description: Temporary safe Freemius package downloader. Remove after updating FooGallery to a verified fixed release.
 * Version: 1.0.0
 * Requires at least: 6.9
 * Requires PHP: 7.2
 * Author: FooPlugins
 * License: GPL-2.0-or-later
 * Text Domain: foogallery
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
require_once __DIR__ . '/package-downloader.php';
