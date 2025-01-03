jQuery(document).ready(function($) {
    // ارسال پیام
    $('.send-message').click(function() {
        var message = $('.chat-editor').html();
        var room_id = $('.multi-room-chat').data('room-id');
        if (message) {
            $.ajax({
                url: amrc_ajax.ajax_url,
                type: "POST",
                data: {
                    action: "send_chat_message",
                    message: message,
                    room_id: room_id,
                    _ajax_nonce: amrc_ajax.nonce
                },
                success: function(response) {
                    $('.chat-messages').append("<div><strong>" + amrc_ajax.current_user + ":</strong> <div class=\"message-content\">" + response + "</div></div>");
                    $('.chat-editor').html("");
                }
            });
        }
    });

    // باز کردن پاپ‌آپ چت خصوصی
    $('.user-link').click(function(e) {
        e.preventDefault();
        var receiverId = $(this).data('user-id');
        $('#private-chat-popup').show();
        loadPrivateMessages(receiverId);
    });

    // بستن پاپ‌آپ چت خصوصی
    $('#close-private-chat').click(function() {
        $('#private-chat-popup').hide();
    });

    // بارگیری پیام‌های خصوصی
    function loadPrivateMessages(receiverId) {
        $.ajax({
            url: amrc_ajax.ajax_url,
            type: "POST",
            data: {
                action: "load_private_messages",
                receiver_id: receiverId,
                _ajax_nonce: amrc_ajax.nonce
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
                url: amrc_ajax.ajax_url,
                type: "POST",
                data: {
                    action: "send_private_message",
                    receiver_id: currentReceiverId,
                    message: message,
                    _ajax_nonce: amrc_ajax.nonce
                },
                success: function(response) {
                    $('#private-chat-input').val('');
                    loadPrivateMessages(currentReceiverId);
                }
            });
        }
    });
});