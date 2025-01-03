<?php
// جلوگیری از دسترسی مستقیم
if (!defined('ABSPATH')) {
    exit;
}

// شورت‌کد برای نمایش لیست اتاق‌ها
function list_chat_rooms_shortcode() {
    global $wpdb;
    $table_rooms = $wpdb->prefix . 'chat_rooms';
    $rooms = $wpdb->get_results("SELECT * FROM $table_rooms ORDER BY created_at DESC");

    ob_start();
    if ($rooms) {
        echo '<div class="chat-rooms-list">';
        echo '<h2>لیست اتاق‌های چت</h2>';
        echo '<ul>';
        foreach ($rooms as $room) {
            $room_url = add_query_arg('room_id', $room->id, get_permalink());
            echo '<li><a href="' . esc_url($room_url) . '">' . esc_html($room->name) . '</a>';
            if ($room->is_private) {
                echo ' <em>(خصوصی)</em>';
            }
            echo '</li>';
        }
        echo '</ul>';
        echo '</div>';
    } else {
        echo '<p>هیچ اتاق چتی وجود ندارد.</p>';
    }
    return ob_get_clean();
}
add_shortcode('list_chat_rooms', 'list_chat_rooms_shortcode');

// شورت‌کد برای نمایش چت روم
function multi_room_chat_shortcode() {
    global $wpdb;
    $table_messages = $wpdb->prefix . 'chat_messages';
    $table_rooms = $wpdb->prefix . 'chat_rooms';
    $table_stickers = $wpdb->prefix . 'chat_stickers';

    // دریافت شناسه اتاق از پارامتر URL
    $room_id = isset($_GET['room_id']) ? intval($_GET['room_id']) : 0;

    ob_start();
    if (is_user_logged_in() && $room_id > 0) {
        // بررسی اتاق خصوصی
        $room = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_rooms WHERE id = %d", $room_id));
        if ($room && $room->is_private) {
            if (!isset($_POST['room_password']) || $_POST['room_password'] !== $room->password) {
                echo '<form method="post" action="">';
                echo '<p>این اتاق خصوصی است. لطفاً پسورد را وارد کنید:</p>';
                echo '<input type="password" name="room_password" required>';
                echo '<input type="submit" value="ورود">';
                echo '</form>';
                return ob_get_clean();
            }
        }

        wp_enqueue_script('quill', 'https://cdn.quilljs.com/1.3.6/quill.min.js', array(), '1.3.6', true);
        wp_enqueue_style('quill', 'https://cdn.quilljs.com/1.3.6/quill.snow.css', array(), '1.3.6');

        // دریافت پیام‌های قبلی از دیتابیس
        $messages = $wpdb->get_results(
            $wpdb->prepare("SELECT * FROM $table_messages WHERE room_id = %d ORDER BY timestamp ASC", $room_id)
        );

        // دریافت لیست استیکرها
        $stickers = $wpdb->get_results("SELECT * FROM $table_stickers ORDER BY created_at DESC");

        ?>
        <style>
        .multi-room-chat {
            border: 1px solid #ddd;
            padding: 15px;
            border-radius: 5px;
            background-color: #f9f9f9;
            max-width: 600px;
            margin: 0 auto;
        }

        .chat-messages {
            height: 300px;
            overflow-y: auto;
            border: 1px solid #ccc;
            padding: 10px;
            margin-bottom: 10px;
            background-color: #fff;
            border-radius: 5px;
        }

        .chat-editor {
            height: 100px;
            margin-bottom: 10px;
            background-color: #fff;
            border: 1px solid #ccc;
            border-radius: 5px;
        }

        .send-message {
            background-color: #0073aa;
            color: #fff;
            border: none;
            padding: 10px 15px;
            border-radius: 5px;
            cursor: pointer;
        }

        .send-message:hover {
            background-color: #005177;
        }

        .user-link {
            color: #0073aa;
            text-decoration: none;
        }

        .user-link:hover {
            text-decoration: underline;
        }
        </style>

        <div id="multi-room-chat-<?php echo esc_attr($room_id); ?>" class="multi-room-chat">
            <div id="chat-messages-<?php echo esc_attr($room_id); ?>" class="chat-messages">
                <?php
                foreach ($messages as $msg) {
                    $user = get_userdata($msg->user_id);
                    echo '<div><a href="#" class="user-link" data-user-id="' . esc_attr($msg->user_id) . '"><strong>' . esc_html($user->display_name) . ':</strong></a> <div class="message-content">' . wp_kses_post($msg->message) . '</div></div>';
                }
                ?>
            </div>
            <div id="editor-<?php echo esc_attr($room_id); ?>" class="chat-editor"></div>
            <button id="send-message-<?php echo esc_attr($room_id); ?>" class="send-message">ارسال</button>
        </div>

        <!-- پاپ‌آپ چت خصوصی -->
        <div id="private-chat-popup" style="display: none; position: fixed; bottom: 20px; right: 20px; width: 300px; background: #fff; border: 1px solid #ccc; padding: 10px; box-shadow: 0 0 10px rgba(0,0,0,0.1); z-index: 1000;">
            <div id="private-chat-header" style="font-weight: bold; margin-bottom: 10px;">چت خصوصی</div>
            <div id="private-chat-messages" style="height: 200px; overflow-y: auto; margin-bottom: 10px;"></div>
            <textarea id="private-chat-input" style="width: 100%; margin-bottom: 10px;"></textarea>
            <button id="send-private-message" style="width: 100%;">ارسال</button>
            <button id="close-private-chat" style="width: 100%; margin-top: 5px;">بستن</button>
        </div>

        <script>
        jQuery(document).ready(function($) {
            var currentReceiverId = null;

            // تنظیمات ادیتور
            var toolbarOptions = [
                <?php if (get_option('chat_editor_bold', 1)) echo "['bold'],"; ?>
                <?php if (get_option('chat_editor_italic', 1)) echo "['italic'],"; ?>
                <?php if (get_option('chat_editor_underline', 1)) echo "['underline'],"; ?>
                <?php if (get_option('chat_editor_strike', 1)) echo "['strike'],"; ?>
                <?php if (get_option('chat_editor_color', 1)) echo "[{ 'color': [] }],"; ?>
                <?php if (get_option('chat_editor_size', 1)) echo "[{ 'size': ['small', false, 'large', 'huge'] }],"; ?>
                <?php if (get_option('chat_editor_link', 1)) echo "['link'],"; ?>
                <?php if (get_option('chat_editor_image', 1)) echo "['image'],"; ?>
                <?php if (get_option('chat_editor_sticker', 1)) echo "[{ 'sticker': " . json_encode($stickers) . " }],"; ?>
                ["clean"]
            ];

            var quill = new Quill("#editor-<?php echo esc_attr($room_id); ?>", {
                theme: "snow",
                modules: {
                    toolbar: toolbarOptions
                }
            });

            // ارسال پیام
            $("#send-message-<?php echo esc_attr($room_id); ?>").click(function() {
                var message = quill.root.innerHTML;
                var room_id = "<?php echo esc_attr($room_id); ?>";
                if (message) {
                    $.ajax({
                        url: "<?php echo admin_url('admin-ajax.php'); ?>",
                        type: "POST",
                        data: {
                            action: "send_chat_message",
                            message: message,
                            room_id: room_id
                        },
                        success: function(response) {
                            $("#chat-messages-<?php echo esc_attr($room_id); ?>").append("<div><strong><?php echo esc_html(wp_get_current_user()->display_name); ?>:</strong> <div class=\"message-content\">" + response + "</div></div>");
                            quill.root.innerHTML = "";
                        }
                    });
                }
            });

            // باز کردن پاپ‌آپ چت خصوصی
            $('.user-link').click(function(e) {
                e.preventDefault();
                currentReceiverId = $(this).data('user-id');
                $('#private-chat-popup').show();
                loadPrivateMessages(currentReceiverId);
            });

            // بستن پاپ‌آپ چت خصوصی
            $('#close-private-chat').click(function() {
                $('#private-chat-popup').hide();
            });

            // بارگیری پیام‌های خصوصی
            function loadPrivateMessages(receiverId) {
                $.ajax({
                    url: "<?php echo admin_url('admin-ajax.php'); ?>",
                    type: "POST",
                    data: {
                        action: "load_private_messages",
                        receiver_id: receiverId
                    },
                    success: function(response) {
                        $('#private-chat-messages').html(response);
                    }
                });
            }

            // ارسال پیام خصوصی
            $('#send-private-message').click(function() {
                var message = $('#private-chat-input').val();
                if (message && currentReceiverId) {
                    $.ajax({
                        url: "<?php echo admin_url('admin-ajax.php'); ?>",
                        type: "POST",
                        data: {
                            action: "send_private_message",
                            receiver_id: currentReceiverId,
                            message: message
                        },
                        success: function(response) {
                            $('#private-chat-input').val('');
                            loadPrivateMessages(currentReceiverId);
                        }
                    });
                }
            });
        });
        </script>
        <?php
    } else {
        echo '<p>برای ارسال پیام باید وارد سیستم شوید.</p>';
    }
    return ob_get_clean();
}
add_shortcode('multi_room_chat', 'multi_room_chat_shortcode');