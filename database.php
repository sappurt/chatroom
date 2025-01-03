<?php
// جلوگیری از دسترسی مستقیم
if (!defined('ABSPATH')) {
    exit;
}

// ایجاد جدول‌های دیتابیس
function create_chat_tables() {
    global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();

    // جدول اتاق‌ها
    $table_rooms = $wpdb->prefix . 'chat_rooms';
    $sql_rooms = "CREATE TABLE $table_rooms (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        name varchar(100) NOT NULL,
        password varchar(255) DEFAULT NULL,
        is_private tinyint(1) DEFAULT 0,
        created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
        PRIMARY KEY (id)
    ) $charset_collate;";

    // جدول پیام‌ها
    $table_messages = $wpdb->prefix . 'chat_messages';
    $sql_messages = "CREATE TABLE $table_messages (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        room_id mediumint(9) NOT NULL,
        user_id bigint(20) NOT NULL,
        message text NOT NULL,
        timestamp datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
        PRIMARY KEY (id)
    ) $charset_collate;";

    // جدول پیام‌های خصوصی
    $table_private_messages = $wpdb->prefix . 'private_messages';
    $sql_private_messages = "CREATE TABLE $table_private_messages (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        sender_id bigint(20) NOT NULL,
        receiver_id bigint(20) NOT NULL,
        message text NOT NULL,
        timestamp datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
        PRIMARY KEY (id)
    ) $charset_collate;";

    // جدول استیکرها
    $table_stickers = $wpdb->prefix . 'chat_stickers';
    $sql_stickers = "CREATE TABLE $table_stickers (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        name varchar(100) NOT NULL,
        url varchar(255) NOT NULL,
        created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
        PRIMARY KEY (id)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql_rooms);
    dbDelta($sql_messages);
    dbDelta($sql_private_messages);
    dbDelta($sql_stickers);
}
register_activation_hook(__FILE__, 'create_chat_tables');