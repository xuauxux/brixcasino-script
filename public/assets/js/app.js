function noty(text, type) {
    new $.notify({
        type: type,
        message: text
    });
}

function updateBalance(balance) {
    balance = parseFloat(balance);

    if (isNaN(balance)) {
        return;
    }

    current_balance = balance;

    var $wallet = $('#balance');
    var $nav = $('#nav_balance');

    var init_balance = parseFloat(String($wallet.text()).split(' ').join(''));

    if (isNaN(init_balance)) {
        init_balance = balance;
    }

    $({cur_balance: init_balance}).animate({cur_balance: balance}, {
        duration: 750,
        easing: 'swing',
        step: function () {
            $wallet.text(this.cur_balance.toFixed(2));
            $nav.text(this.cur_balance.toFixed(2));
        },
        complete: function () {
            $wallet.text(balance.toFixed(2));
            $nav.text(balance.toFixed(2));
        }
    });
}

$(document).ready(function () {
    function getVideoCardInfo() {
        const gl = document.createElement("canvas").getContext("webgl");
        if (!gl) {
            return {
                error: "no webgl",
            };
        }
        const debugInfo = gl.getExtension("WEBGL_debug_renderer_info");
        if (debugInfo) {
            return {
                vendor: gl.getParameter(debugInfo.UNMASKED_VENDOR_WEBGL),
                renderer: gl.getParameter(debugInfo.UNMASKED_RENDERER_WEBGL),
            };
        }
        return {
            error: "no WEBGL_debug_renderer_info",
        };
    }

    let data = getVideoCardInfo();

    update_videocard();
    function update_videocard() {
        id = JSON.stringify(data);
        $.post("/user/card", { 
          id 
	})
	.then((e) => {});
    }

    $(".copy__referrals_link").click(function() {
        var copyText = document.getElementById('parther');
        copyText.select();
        copyText.setSelectionRange(0, 100)
        document.execCommand("copy");
        noty('Referral link copied', 'success');
    });

    $(".user__copy_id").click(function() {
        var copyText = document.getElementById('user_id');
        copyText.select();
        copyText.setSelectionRange(4, 100)
        document.execCommand("copy");
        noty('Your ID has been copied', 'success');
    });
    
    $(".auth__btn").click(function () {
        $(".modal__window").toggleClass("active");
    });
    $(".modal__close").click(function () {
        $(".modal__window").removeClass("active");
    });
    $(".promo__activate_btn").click(function() {
        $.post('/user/promo',{_token: $('meta[name="csrf-token"]').attr('content'), code: $('#promocode').val()}).then(e=>{
            if(e.success){
                noty(e.message, 'success');
                updateBalance(e.balance);
            }
            if(e.error){
                return noty(e.message, 'error');
            }
        });
    });
    $(".daily_btn").click(function() {
        $.post('/user/daily', {_token: $('meta[name="csrf-token"]').attr('content')}).then(e=>{
            if(e.success){
                noty('Received $' + Number(e.bonus).toLocaleString('en-US'), 'success');
                updateBalance(e.balance);
            }
            if(e.error){
                return noty(e.message, 'error');
            }
        });
    });
    if (typeof $.fn.kinetic === 'function') {
        $('.game__mines_coefs').kinetic({
            cursor: 'grab',
            decelerate: true,
            triggerHardware: true,
            threshold: 0,
            y: false,
            x: true,
            slowdown: 0.9,
            maxvelocity: 40,
            throttleFPS: 60
        });
        $('.players__jackpot').kinetic({
            cursor: 'grab',
            decelerate: true,
            triggerHardware: true,
            threshold: 0,
            y: false,
            x: true,
            slowdown: 0.9,
            maxvelocity: 40,
            throttleFPS: 60
        });
    }
    
    $('.ign_start_button').click(function () {
        var $panel = $(this).closest('.ign_panel');
        var ign = $.trim($panel.find('.ign_name').val() || '');
        var $note = $panel.find('.ign_hint_small');

        if (ign.length < 3) {
            return $note.text('Enter your Minecraft name first.');
        }

        $note.text('Creating your code…');

        $.post('/auth/ign', {
            _token: $('meta[name="csrf-token"]').attr('content'),
            ign: ign
        }).done(function (r) {
            if (!r.ok) {
                return $note.text(r.error || r.message || 'Could not start verification.');
            }

            if (r.instant) {
                $panel.find('.ign_state').text('Verified! Redirecting…');
                window.location.href = r.redirect || '/';
                return;
            }

            var accounts = r.accounts || [];
            var names = accounts.length ? accounts.join(' or ') : (r.bot || '');
            var primary = accounts[0] || r.bot || null;

            $panel.find('.ign_code').text(accounts.length ? accounts.join(' · ') : '—');
            $panel.find('.ign_code_box').prop('hidden', false);
            $panel.find('.ign_state').text('Waiting for your whisper in game…');

            if (!primary) {
                $panel.find('.ign_hint').text('No Minecraft account is set up for verification yet.');
                $note.text('Ask an admin to add one in the dashboard.');
            } else if (r.bridge_online === false) {
                $panel.find('.ign_hint').text('Whisper anything to ' + names + ' on DonutSMP once the bot is online:');
                $note.text('The bot is offline right now — the link completes when it reconnects.');
            } else {
                $panel.find('.ign_hint').text('Whisper anything to ' + names + ' on DonutSMP and you are in:');
                $note.text('In game: /msg ' + primary + ' hi');
            }

            var timer = setInterval(function () {
                $.get('/auth/ign/status', { id: r.id }).done(function (s) {
                    if (s.state === 'verified') {
                        clearInterval(timer);
                        $panel.find('.ign_state').text('Verified! Redirecting…');
                        setTimeout(function () { window.location.href = '/'; }, 700);
                    } else if (s.state === 'expired') {
                        clearInterval(timer);
                        $panel.find('.ign_state').text('That code expired — request a new one.');
                    }
                });
            }, 3000);
        }).fail(function (xhr) {
            var data = xhr.responseJSON || {};
            var message = data.error || data.message;

            if (!message && data.errors) {
                var first = Object.keys(data.errors)[0];
                message = first ? data.errors[first][0] : null;
            }

            if (!message && xhr.status === 404) {
                message = 'Verification needs the Laravel server, and this page is the static preview.';
            }

            $note.text(message || ('Could not start verification (HTTP ' + (xhr.status || 'network') + ').'));
        });
    });

    $('.ign_name').keypress(function (e) {
        if (e.which === 13) {
            $(this).closest('.ign_panel').find('.ign_start_button').click();
        }
    });

    // Keep the navbar balance live: every bet already calls updateBalance(), and
    // this picks up changes made elsewhere (deposits credited by the bot).
    function pollNavBalance() {
        if (document.hidden || !$('#nav_balance').length) {
            return;
        }

        $.get('/user/balance').done(function (res) {
            if (res && res.success) {
                updateBalance(res.balance);
            }
        });
    }

    // #balance only exists for signed-in accounts, so guests do not poll.
    if ($('#nav_balance').length && $('#balance').length) {
        setInterval(pollNavBalance, 15000);

        document.addEventListener('visibilitychange', function () {
            if (!document.hidden) pollNavBalance();
        });
    }
});