<?php
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

global $wpdb;

// حذف جدول‌های دیتابیس
$wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}chat_rooms");
$wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}chat_messages");
$wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}private_messages");
$wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}chat_stickers");

// حذف گزینه‌های ذخیره شده
delete_option('chat_editor_bold');
delete_option('chat_editor_italic');
delete_option('chat_editor_underline');
delete_option('chat_editor_strike');
delete_option('chat_editor_color');
delete_option('chat_editor_size');
delete_option('chat_editor_link');
delete_option('chat_editor_image');
delete_option('chat_editor_sticker');