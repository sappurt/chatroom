<?php
// جلوگیری از دسترسی مستقیم
if (!defined('ABSPATH')) {
    exit;
}

// پردازش ارسال پیام
function send_chat_message() {
    if (is_user_logged_in()) {
        global $wpdb;
        $table_messages = $wpdb->prefix . 'chat_messages';
        $message = wp_kses_post($_POST['message']);
        $room_id = intval($_POST['room_id']);
        $user_id = get_current_user_id();

        // ذخیره پیام در دیتابیس
        $wpdb->insert(
            $table_messages,
            array(
                'room_id' => $room_id,
                'user_id' => $user_id,
                'message' => $message
            )
        );

        // نمایش پیام
        echo $message;
        wp_die();
    }
}
add_action('wp_ajax_send_chat_message', 'send_chat_message');

// پردازش ارسال پیام خصوصی
function send_private_message() {
    if (is_user_logged_in()) {
        global $wpdb;
        $table_private_messages = $wpdb->prefix . 'private_messages';
        $message = sanitize_text_field($_POST['message']);
        $receiver_id = intval($_POST['receiver_id']);
        $sender_id = get_current_user_id();

        // ذخیره پیام خصوصی در دیتابیس
        $wpdb->insert(
            $table_private_messages,
            array(
                'sender_id' => $sender_id,
                'receiver_id' => $receiver_id,
                'message' => $message
            )
        );

        // نمایش پیام
        echo $message;
        wp_die();
    }
}
add_action('wp_ajax_send_private_message', 'send_private_message');

// بارگیری پیام‌های خصوصی
function load_private_messages() {
    if (is_user_logged_in()) {
        global $wpdb;
        $table_private_messages = $wpdb->prefix . 'private_messages';
        $receiver_id = intval($_POST['receiver_id']);
        $sender_id = get_current_user_id();

        // دریافت پیام‌های خصوصی
        $messages = $wpdb->get_results($wpdb->prepare("
            SELECT pm.*, u1.display_name as sender_name
            FROM $table_private_messages pm
            LEFT JOIN {$wpdb->users} u1 ON pm.sender_id = u1.ID
            WHERE (pm.sender_id = %d AND pm.receiver_id = %d) OR (pm.sender_id = %d AND pm.receiver_id = %d)
            ORDER BY pm.timestamp ASC
        ", $sender_id, $receiver_id, $receiver_id, $sender_id));

        foreach ($messages as $msg) {
            echo '<div><strong>' . esc_html($msg->sender_name) . ':</strong> ' . esc_html($msg->message) . '</div>';
        }
        wp_die();
    }
}
add_action('wp_ajax_load_private_messages', 'load_private_messages');