<?php
/*
Plugin Name: Advanced Multi-Room Chat
Description: A custom chat room plugin for WordPress with advanced text editor, private messaging, and sticker management.
Version: 1.0
Author: Your Name
*/

// جلوگیری از دسترسی مستقیم
if (!defined('ABSPATH')) {
    exit;
}

// فراخوانی فایل‌های دیگر
require_once plugin_dir_path(__FILE__) . 'includes/database.php';
require_once plugin_dir_path(__FILE__) . 'includes/admin-pages.php';
require_once plugin_dir_path(__FILE__) . 'includes/shortcodes.php';
require_once plugin_dir_path(__FILE__) . 'includes/ajax-handlers.php';
require_once plugin_dir_path(__FILE__) . 'includes/functions.php';

// افزودن استایل‌ها و اسکریپت‌ها
function amrc_enqueue_scripts() {
    // استایل‌ها
    wp_enqueue_style('amrc-style', plugin_dir_url(__FILE__) . 'assets/css/style.css');

    // اسکریپت‌ها
    wp_enqueue_script('amrc-script', plugin_dir_url(__FILE__) . 'assets/js/script.js', array('jquery'), null, true);
    wp_localize_script('amrc-script', 'amrc_ajax', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce'    => wp_create_nonce('amrc_nonce')
    ));
}
add_action('wp_enqueue_scripts', 'amrc_enqueue_scripts');