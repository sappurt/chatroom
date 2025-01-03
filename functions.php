<?php
// جلوگیری از دسترسی مستقیم
if (!defined('ABSPATH')) {
    exit;
}

// تابع کمکی برای بررسی دسترسی کاربر
function amrc_check_user_access($room_id) {
    global $wpdb;
    $table_rooms = $wpdb->prefix . 'chat_rooms';
    $room = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_rooms WHERE id = %d", $room_id));
    if ($room && $room->is_private) {
        if (!isset($_POST['room_password']) || $_POST['room_password'] !== $room->password) {
            return false;
        }
    }
    return true;
}