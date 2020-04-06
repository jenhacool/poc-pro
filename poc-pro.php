<?php
/**
 * Plugin Name: POC Pro
 * Text Domain: poc-pro
 */

defined( 'ABSPATH' ) || exit;

if( ! defined( 'POC_PRO_PLUGIN_FILE' ) ) {
    define( 'POC_PRO_PLUGIN_FILE', __FILE__ );
}

if( ! class_exists( 'POC_PRO' ) ) {
    include_once dirname( POC_PRO_PLUGIN_FILE ) . '/includes/class-poc-pro.php';
}

function POC_PRO() {
    return POC_PRO::instance();
}

POC_PRO();