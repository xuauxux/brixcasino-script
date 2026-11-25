/*
 * Verified live chat: polls for new messages every 3 seconds and posts with the
 * session's verified account. Nothing typed here is trusted as a name.
 */
(function () {
    var CSRF = $('meta[name="csrf-token"]').attr('content');
    var $list = $('#chat_list');
    var last = 0;
    var busy = false;

    if (!$list.length) return;

    function esc(text) {
        return String(text).replace(/[&<>"']/g, function (ch) {
            return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[ch];
        });
    }

    function hint(text) {
        $('#chat_hint').text(text);
    }

    function row(message) {
        return '<div class="chat__msg">' +
            '<span class="chat__msg_ign">' + esc(message.ign) + '</span>' +
            '<span class="chat__msg_body">' + esc(message.body) + '</span>' +
            '<span class="chat__msg_time">' + esc(message.at) + '</span>' +
        '</div>';
    }

    function scrollDown() {
        if ($list.length) {
            $list.scrollTop($list[0].scrollHeight);
        }
    }

    function poll() {
        if (document.hidden) return;

        $.get('/chat/messages', { after: last }).done(function (res) {
            if (!res.ok) {
                return hint(res.error || 'Chat is unavailable.');
            }

            if (res.messages && res.messages.length) {
                $('#chat_empty').remove();
                $list.append(res.messages.map(row).join(''));
                scrollDown();
            }

            if (res.latest) {
                last = res.latest;
            }
        });
    }

    $('#chat_form').on('submit', function (event) {
        event.preventDefault();

        var body = $.trim($('#chat_input').val());

        if (!body || busy) {
            return;
        }

        busy = true;
        $('#chat_send').prop('disabled', true);

        $.post('/chat/send', { _token: CSRF, body: body })
            .done(function (res) {
                if (!res.ok) {
                    return hint(res.error || 'Could not send that.');
                }

                $('#chat_input').val('');
                hint('Be kind. Messages are tied to your verified IGN.');
                poll();
            })
            .fail(function () {
                hint('Could not send that.');
            })
            .always(function () {
                busy = false;
                $('#chat_send').prop('disabled', false);
            });
    });

    document.addEventListener('visibilitychange', function () {
        if (!document.hidden) poll();
    });

    poll();
    scrollDown();
    setInterval(poll, 3000);
})();
