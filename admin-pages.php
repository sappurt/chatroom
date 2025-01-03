<?php
// جلوگیری از دسترسی مستقیم
if (!defined('ABSPATH')) {
    exit;
}

// افزودن منوی مدیریت
function add_chat_room_menu() {
    add_menu_page(
        'چت روم', // عنوان صفحه
        'چت روم', // عنوان منو
        'manage_options', // سطح دسترسی
        'chat_rooms', // اسلاگ منو
        'chat_rooms_page', // تابع نمایش صفحه
        'dashicons-format-chat', // آیکون
        6 // موقعیت منو
    );

    add_submenu_page(
        'chat_rooms', // اسلاگ منوی والد
        'اضافه کردن اتاق', // عنوان صفحه
        'اضافه کردن اتاق', // عنوان منو
        'manage_options', // سطح دسترسی
        'add_chat_room', // اسلاگ منو
        'add_chat_room_page' // تابع نمایش صفحه
    );

    add_submenu_page(
        'chat_rooms', // اسلاگ منوی والد
        'تاریخچه پیام‌ها', // عنوان صفحه
        'تاریخچه پیام‌ها', // عنوان منو
        'manage_options', // سطح دسترسی
        'chat_message_history', // اسلاگ منو
        'chat_message_history_page' // تابع نمایش صفحه
    );

    add_submenu_page(
        'chat_rooms', // اسلاگ منوی والد
        'تنظیمات ویرایشگر', // عنوان صفحه
        'تنظیمات ویرایشگر', // عنوان منو
        'manage_options', // سطح دسترسی
        'chat_editor_settings', // اسلاگ منو
        'chat_editor_settings_page' // تابع نمایش صفحه
    );

    add_submenu_page(
        'chat_rooms', // اسلاگ منوی والد
        'مدیریت استیکرها', // عنوان صفحه
        'مدیریت استیکرها', // عنوان منو
        'manage_options', // سطح دسترسی
        'chat_stickers', // اسلاگ منو
        'chat_stickers_page' // تابع نمایش صفحه
    );
}
add_action('admin_menu', 'add_chat_room_menu');

// صفحه مدیریت اتاق‌ها
function chat_rooms_page() {
    global $wpdb;
    $table_rooms = $wpdb->prefix . 'chat_rooms';

    // حذف اتاق
    if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['room_id'])) {
        $room_id = intval($_GET['room_id']);
        $wpdb->delete($table_rooms, array('id' => $room_id));
        echo '<div class="updated"><p>اتاق با موفقیت حذف شد.</p></div>';
    }

    // دریافت لیست اتاق‌ها
    $rooms = $wpdb->get_results("SELECT * FROM $table_rooms ORDER BY created_at DESC");

    echo '<div class="wrap">';
    echo '<h1>مدیریت اتاق‌های چت</h1>';
    echo '<table class="wp-list-table widefat fixed striped">';
    echo '<thead><tr><th>ID</th><th>نام اتاق</th><th>خصوصی</th><th>پسورد</th><th>تاریخ ایجاد</th><th>عملیات</th></tr></thead>';
    echo '<tbody>';
    foreach ($rooms as $room) {
        echo '<tr>';
        echo '<td>' . esc_html($room->id) . '</td>';
        echo '<td>' . esc_html($room->name) . '</td>';
        echo '<td>' . ($room->is_private ? 'بله' : 'خیر') . '</td>';
        echo '<td>' . esc_html($room->password) . '</td>';
        echo '<td>' . esc_html($room->created_at) . '</td>';
        echo '<td><a href="?page=chat_rooms&action=delete&room_id=' . esc_attr($room->id) . '" class="button button-danger">حذف</a></td>';
        echo '</tr>';
    }
    echo '</tbody>';
    echo '</table>';
    echo '</div>';
}

// صفحه اضافه کردن اتاق
function add_chat_room_page() {
    global $wpdb;
    $table_rooms = $wpdb->prefix . 'chat_rooms';

    // افزودن اتاق جدید
    if (isset($_POST['submit'])) {
        $room_name = sanitize_text_field($_POST['room_name']);
        $is_private = isset($_POST['is_private']) ? 1 : 0;
        $password = $is_private ? sanitize_text_field($_POST['password']) : null;

        if (!empty($room_name)) {
            $result = $wpdb->insert(
                $table_rooms,
                array(
                    'name' => $room_name,
                    'is_private' => $is_private,
                    'password' => $password
                )
            );
            if ($result) {
                echo '<div class="updated"><p>اتاق با موفقیت اضافه شد.</p></div>';
            } else {
                echo '<div class="error"><p>خطا در اضافه کردن اتاق. لطفاً دوباره امتحان کنید.</p></div>';
            }
        } else {
            echo '<div class="error"><p>نام اتاق نمی‌تواند خالی باشد.</p></div>';
        }
    }

    echo '<div class="wrap">';
    echo '<h1>اضافه کردن اتاق چت</h1>';
    echo '<form method="post" action="">';
    echo '<table class="form-table">';
    echo '<tr><th scope="row"><label for="room_name">نام اتاق</label></th>';
    echo '<td><input name="room_name" type="text" id="room_name" class="regular-text" required></td></tr>';
    echo '<tr><th scope="row"><label for="is_private">اتاق خصوصی</label></th>';
    echo '<td><input name="is_private" type="checkbox" id="is_private"></td></tr>';
    echo '<tr><th scope="row"><label for="password">پسورد</label></th>';
    echo '<td><input name="password" type="password" id="password" class="regular-text"></td></tr>';
    echo '</table>';
    echo '<p class="submit"><input type="submit" name="submit" class="button button-primary" value="ذخیره"></p>';
    echo '</form>';
    echo '</div>';
}

// صفحه تاریخچه پیام‌ها
function chat_message_history_page() {
    global $wpdb;
    $table_messages = $wpdb->prefix . 'chat_messages';
    $table_private_messages = $wpdb->prefix . 'private_messages';
    $table_rooms = $wpdb->prefix . 'chat_rooms';

    // حذف دسته‌جمعی پیام‌ها
    if (isset($_POST['delete_messages'])) {
        $room_id = intval($_POST['room_id']);
        if ($room_id > 0) {
            $wpdb->delete($table_messages, array('room_id' => $room_id));
            echo '<div class="updated"><p>پیام‌ها با موفقیت حذف شدند.</p></div>';
        } else {
            $wpdb->query("TRUNCATE TABLE $table_messages");
            $wpdb->query("TRUNCATE TABLE $table_private_messages");
            echo '<div class="updated"><p>تمام پیام‌ها با موفقیت حذف شدند.</p></div>';
        }
    }

    // دریافت لیست پیام‌ها
    $messages = $wpdb->get_results("
        SELECT m.*, r.name as room_name
        FROM $table_messages m
        LEFT JOIN $table_rooms r ON m.room_id = r.id
        ORDER BY m.timestamp DESC
    ");

    // دریافت لیست پیام‌های خصوصی
    $private_messages = $wpdb->get_results("
        SELECT pm.*, u1.display_name as sender_name, u2.display_name as receiver_name
        FROM $table_private_messages pm
        LEFT JOIN {$wpdb->users} u1 ON pm.sender_id = u1.ID
        LEFT JOIN {$wpdb->users} u2 ON pm.receiver_id = u2.ID
        ORDER BY pm.timestamp DESC
    ");

    echo '<div class="wrap">';
    echo '<h1>تاریخچه پیام‌ها</h1>';
    echo '<form method="post" action="">';
    echo '<select name="room_id">';
    echo '<option value="0">همه اتاق‌ها</option>';
    $rooms = $wpdb->get_results("SELECT * FROM $table_rooms");
    foreach ($rooms as $room) {
        echo '<option value="' . esc_attr($room->id) . '">' . esc_html($room->name) . '</option>';
    }
    echo '</select>';
    echo '<input type="submit" name="delete_messages" class="button button-danger" value="حذف پیام‌ها">';
    echo '</form>';
    echo '<h2>پیام‌های عمومی</h2>';
    echo '<table class="wp-list-table widefat fixed striped">';
    echo '<thead><tr><th>ID</th><th>اتاق</th><th>کاربر</th><th>پیام</th><th>زمان</th></tr></thead>';
    echo '<tbody>';
    foreach ($messages as $msg) {
        $user = get_userdata($msg->user_id);
        echo '<tr>';
        echo '<td>' . esc_html($msg->id) . '</td>';
        echo '<td>' . esc_html($msg->room_name) . '</td>';
        echo '<td>' . esc_html($user->display_name) . '</td>';
        echo '<td>' . esc_html($msg->message) . '</td>';
        echo '<td>' . esc_html($msg->timestamp) . '</td>';
        echo '</tr>';
    }
    echo '</tbody>';
    echo '</table>';
    echo '<h2>پیام‌های خصوصی</h2>';
    echo '<table class="wp-list-table widefat fixed striped">';
    echo '<thead><tr><th>ID</th><th>فرستنده</th><th>گیرنده</th><th>پیام</th><th>زمان</th></tr></thead>';
    echo '<tbody>';
    foreach ($private_messages as $pm) {
        echo '<tr>';
        echo '<td>' . esc_html($pm->id) . '</td>';
        echo '<td>' . esc_html($pm->sender_name) . '</td>';
        echo '<td>' . esc_html($pm->receiver_name) . '</td>';
        echo '<td>' . esc_html($pm->message) . '</td>';
        echo '<td>' . esc_html($pm->timestamp) . '</td>';
        echo '</tr>';
    }
    echo '</tbody>';
    echo '</table>';
    echo '</div>';
}

// صفحه تنظیمات ویرایشگر
function chat_editor_settings_page() {
    if (isset($_POST['submit'])) {
        update_option('chat_editor_bold', isset($_POST['bold']) ? 1 : 0);
        update_option('chat_editor_italic', isset($_POST['italic']) ? 1 : 0);
        update_option('chat_editor_underline', isset($_POST['underline']) ? 1 : 0);
        update_option('chat_editor_strike', isset($_POST['strike']) ? 1 : 0);
        update_option('chat_editor_color', isset($_POST['color']) ? 1 : 0);
        update_option('chat_editor_size', isset($_POST['size']) ? 1 : 0);
        update_option('chat_editor_link', isset($_POST['link']) ? 1 : 0);
        update_option('chat_editor_image', isset($_POST['image']) ? 1 : 0);
        update_option('chat_editor_sticker', isset($_POST['sticker']) ? 1 : 0);
        echo '<div class="updated"><p>تنظیمات با موفقیت ذخیره شدند.</p></div>';
    }

    $bold = get_option('chat_editor_bold', 1);
    $italic = get_option('chat_editor_italic', 1);
    $underline = get_option('chat_editor_underline', 1);
    $strike = get_option('chat_editor_strike', 1);
    $color = get_option('chat_editor_color', 1);
    $size = get_option('chat_editor_size', 1);
    $link = get_option('chat_editor_link', 1);
    $image = get_option('chat_editor_image', 1);
    $sticker = get_option('chat_editor_sticker', 1);

    echo '<div class="wrap">';
    echo '<h1>تنظیمات ویرایشگر</h1>';
    echo '<form method="post" action="">';
    echo '<table class="form-table">';
    echo '<tr><th scope="row"><label for="bold">ضخیم کردن (Bold)</label></th>';
    echo '<td><input name="bold" type="checkbox" id="bold" ' . checked($bold, 1, false) . '></td></tr>';
    echo '<tr><th scope="row"><label for="italic">مورب (Italic)</label></th>';
    echo '<td><input name="italic" type="checkbox" id="italic" ' . checked($italic, 1, false) . '></td></tr>';
    echo '<tr><th scope="row"><label for="underline">زیرخط (Underline)</label></th>';
    echo '<td><input name="underline" type="checkbox" id="underline" ' . checked($underline, 1, false) . '></td></tr>';
    echo '<tr><th scope="row"><label for="strike">خط‌خورده (Strike)</label></th>';
    echo '<td><input name="strike" type="checkbox" id="strike" ' . checked($strike, 1, false) . '></td></tr>';
    echo '<tr><th scope="row"><label for="color">رنگ متن</label></th>';
    echo '<td><input name="color" type="checkbox" id="color" ' . checked($color, 1, false) . '></td></tr>';
    echo '<tr><th scope="row"><label for="size">اندازه متن</label></th>';
    echo '<td><input name="size" type="checkbox" id="size" ' . checked($size, 1, false) . '></td></tr>';
    echo '<tr><th scope="row"><label for="link">لینک</label></th>';
    echo '<td><input name="link" type="checkbox" id="link" ' . checked($link, 1, false) . '></td></tr>';
    echo '<tr><th scope="row"><label for="image">تصویر</label></th>';
    echo '<td><input name="image" type="checkbox" id="image" ' . checked($image, 1, false) . '></td></tr>';
    echo '<tr><th scope="row"><label for="sticker">استیکر</label></th>';
    echo '<td><input name="sticker" type="checkbox" id="sticker" ' . checked($sticker, 1, false) . '></td></tr>';
    echo '</table>';
    echo '<p class="submit"><input type="submit" name="submit" class="button button-primary" value="ذخیره تنظیمات"></p>';
    echo '</form>';
    echo '</div>';
}

// صفحه مدیریت استیکرها
function chat_stickers_page() {
    global $wpdb;
    $table_stickers = $wpdb->prefix . 'chat_stickers';

    // حذف استیکر
    if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['sticker_id'])) {
        $sticker_id = intval($_GET['sticker_id']);
        $sticker = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_stickers WHERE id = %d", $sticker_id));
        if ($sticker) {
            // حذف فایل استیکر از سرور
            $upload_dir = wp_upload_dir();
            $sticker_path = $upload_dir['basedir'] . '/stickers/' . basename($sticker->url);
            if (file_exists($sticker_path)) {
                unlink($sticker_path);
            }
            // حذف استیکر از دیتابیس
            $wpdb->delete($table_stickers, array('id' => $sticker_id));
            echo '<div class="updated"><p>استیکر با موفقیت حذف شد.</p></div>';
        }
    }

    // افزودن استیکر جدید
    if (isset($_POST['submit'])) {
        $name = sanitize_text_field($_POST['name']);
        $sticker_file = $_FILES['sticker_file'];

        if (!empty($name) && !empty($sticker_file['name'])) {
            // بررسی نوع فایل
            $allowed_types = array('image/jpeg', 'image/png', 'image/gif', 'image/webp');
            if (!in_array($sticker_file['type'], $allowed_types)) {
                echo '<div class="error"><p>فقط فایل‌های تصویری با پسوند JPEG, PNG, GIF یا WebP مجاز هستند.</p></div>';
                return;
            }

            // بررسی حجم فایل (حداکثر 5 مگابایت)
            $max_size = 5 * 1024 * 1024; // 5MB
            if ($sticker_file['size'] > $max_size) {
                echo '<div class="error"><p>حجم فایل باید کمتر از 5 مگابایت باشد.</p></div>';
                return;
            }

            // آپلود فایل
            $upload_dir = wp_upload_dir();
            $sticker_dir = $upload_dir['basedir'] . '/stickers/';
            if (!file_exists($sticker_dir)) {
                mkdir($sticker_dir, 0755, true);
            }

            $file_name = sanitize_file_name($sticker_file['name']);
            $file_path = $sticker_dir . $file_name;

            if (move_uploaded_file($sticker_file['tmp_name'], $file_path)) {
                // تغییر اندازه تصویر به 50x50 پیکسل
                $image = wp_get_image_editor($file_path);
                if (!is_wp_error($image)) {
                    $image->resize(50, 50, true);
                    $image->save($file_path);
                }

                // ذخیره اطلاعات استیکر در دیتابیس
                $sticker_url = $upload_dir['baseurl'] . '/stickers/' . $file_name;
                $result = $wpdb->insert(
                    $table_stickers,
                    array(
                        'name' => $name,
                        'url' => $sticker_url
                    )
                );

                if ($result) {
                    echo '<div class="updated"><p>استیکر با موفقیت اضافه شد.</p></div>';
                } else {
                    echo '<div class="error"><p>خطا در اضافه کردن استیکر. لطفاً دوباره امتحان کنید.</p></div>';
                }
            } else {
                echo '<div class="error"><p>خطا در آپلود فایل. لطفاً دوباره امتحان کنید.</p></div>';
            }
        } else {
            echo '<div class="error"><p>نام و فایل استیکر نمی‌تواند خالی باشد.</p></div>';
        }
    }

    // دریافت لیست استیکرها
    $stickers = $wpdb->get_results("SELECT * FROM $table_stickers ORDER BY created_at DESC");

    echo '<div class="wrap">';
    echo '<h1>مدیریت استیکرها</h1>';
    echo '<form method="post" action="" enctype="multipart/form-data">';
    echo '<table class="form-table">';
    echo '<tr><th scope="row"><label for="name">نام استیکر</label></th>';
    echo '<td><input name="name" type="text" id="name" class="regular-text" required></td></tr>';
    echo '<tr><th scope="row"><label for="sticker_file">فایل استیکر</label></th>';
    echo '<td><input name="sticker_file" type="file" id="sticker_file" accept="image/*" required></td></tr>';
    echo '</table>';
    echo '<p class="submit"><input type="submit" name="submit" class="button button-primary" value="ذخیره"></p>';
    echo '</form>';
    echo '<h2>لیست استیکرها</h2>';
    echo '<table class="wp-list-table widefat fixed striped">';
    echo '<thead><tr><th>ID</th><th>نام</th><th>تصویر</th><th>تاریخ ایجاد</th><th>عملیات</th></tr></thead>';
    echo '<tbody>';
    foreach ($stickers as $sticker) {
        echo '<tr>';
        echo '<td>' . esc_html($sticker->id) . '</td>';
        echo '<td>' . esc_html($sticker->name) . '</td>';
        echo '<td><img src="' . esc_url($sticker->url) . '" alt="' . esc_attr($sticker->name) . '" style="width: 50px; height: 50px;"></td>';
        echo '<td>' . esc_html($sticker->created_at) . '</td>';
        echo '<td><a href="?page=chat_stickers&action=delete&sticker_id=' . esc_attr($sticker->id) . '" class="button button-danger">حذف</a></td>';
        echo '</tr>';
    }
    echo '</tbody>';
    echo '</table>';
    echo '</div>';
}