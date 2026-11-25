/*
 * DonutSMP wallet: IGN verification, in-game deposits and payouts.
 * Every amount is decided by the server; this file only asks and renders.
 */
(function () {
    var CSRF = $('meta[name="csrf-token"]').attr('content');
    var bot = $.trim($('.mc__mono').first().text() || '') || 'the bot';

    function post(url, data) {
        return $.ajax({
            url: url,
            method: 'POST',
            dataType: 'json',
            data: $.extend({ _token: CSRF }, data || {})
        });
    }

    function get(url, data) {
        return $.ajax({ url: url, method: 'GET', dataType: 'json', data: data || {} });
    }

    function nf(value) {
        return Number(value || 0).toLocaleString('en-US');
    }

    function hint(text, ok) {
        $('#mc_hint')
            .text(text || '')
            .toggleClass('mc__hint_ok', ok === true)
            .toggleClass('mc__hint_error', ok === false);
    }

    function statusLabel(status) {
        var labels = {
            pending: 'waiting',
            sending: 'paying',
            paid: 'paid',
            completed: 'credited',
            expired: 'expired',
            failed: 'failed',
            cancelled: 'cancelled',
            unconfirmed: 'unconfirmed'
        };

        return labels[status] || status;
    }

    /* ------------------------------------------------------- IGN verification */

    function initLink() {
        if (!$('#mc_ign_start').length) return;

        var timer = null;

        $('#mc_ign_start').on('click', function () {
            var ign = $.trim($('#mc_ign').val() || '');

            if (ign.length < 3) {
                return hint('Enter your Minecraft name first.', false);
            }

            var $button = $(this).prop('disabled', true);

            post('/auth/ign', { ign: ign }).done(function (result) {
                if (!result.ok) {
                    $button.prop('disabled', false);
                    return hint(result.error || 'Could not start verification.', false);
                }

                var accounts = result.accounts || [];
                var names = accounts.length ? accounts.join(' or ') : (result.bot || '');
                var primary = accounts[0] || result.bot || null;

                $('#mc_code').text(accounts.length ? accounts.join(' · ') : '—');
                $('#mc_code_box').prop('hidden', false);
                $('#mc_link_state').text('Waiting for your whisper in DonutSMP…');

                if (!primary) {
                    hint('No Minecraft account is set up yet. Ask an admin to add one.', false);
                } else if (result.bridge_online === false) {
                    hint('Whisper anything to ' + names + ' once the bot is online.', false);
                } else {
                    hint('In game: /msg ' + primary + ' hi — that links your account.', true);
                }

                clearInterval(timer);
                timer = setInterval(function () {
                    get('/auth/ign/status', { id: result.id }).done(function (state) {
                        if (state.state === 'verified') {
                            clearInterval(timer);
                            $('#mc_link_state').text('Verified! Reloading…');
                            setTimeout(function () { location.reload(); }, 600);
                        } else if (state.state === 'expired') {
                            clearInterval(timer);
                            $button.prop('disabled', false);
                            $('#mc_link_state').text('That code expired — request a new one.');
                        }
                    });
                }, 3000);
            }).fail(function (xhr) {
                $button.prop('disabled', false);

                var data = xhr.responseJSON || {};
                var message = data.error || data.message;

                if (!message && xhr.status === 404) {
                    message = 'Verification needs the Laravel server, and this page is the static preview.';
                }

                hint(message || ('Could not start verification (HTTP ' + (xhr.status || 'network') + ').'), false);
            });
        });
    }

    /* -------------------------------------------------------------- deposits */

    function depositRow(row) {
        return '<div class="mc__item">' +
            '<div class="mc__item_main"><strong>' + nf(row.game_amount) + '</strong> in game</div>' +
            '<div class="mc__item_coins">+' + Number(row.coins).toFixed(2) + ' $</div>' +
            '<div class="mc__badge mc__badge_' + row.status + '">' + statusLabel(row.status) + '</div>' +
            '</div>';
    }

    function renderDeposits(data) {
        var pending = data.pending || [];
        var recent = data.recent || [];

        $('#mc_pending').html(pending.length
            ? pending.map(depositRow).join('')
            : '<div class="mc__empty">No pending deposits.</div>');

        $('#mc_recent').html(recent.length
            ? recent.map(depositRow).join('')
            : '<div class="mc__empty">Nothing here yet.</div>');
    }

    function initDeposit() {
        if (!$('#mc_deposit').length) return;

        $('.mc__tier').on('click', function () {
            $('.mc__tier').removeClass('mc__tier_active');
            $(this).addClass('mc__tier_active');
            $('#mc_amount').val($(this).data('amount'));
        });

        $('#mc_deposit').on('click', function () {
            var amount = parseInt($('#mc_amount').val(), 10);

            if (!amount || amount < 1) {
                return hint('Enter the amount you want to send in game.', false);
            }

            var $button = $(this).prop('disabled', true);

            post('/wallet/minecraft/deposit', { amount: amount }).done(function (result) {
                $button.prop('disabled', false);

                if (!result.ok) {
                    return hint(result.error || 'Could not create the deposit.', false);
                }

                hint('Now run /pay ' + bot + ' ' + nf(amount) + ' in DonutSMP.', true);
                pollDeposits();
            }).fail(function (xhr) {
                $button.prop('disabled', false);
                hint((xhr.responseJSON && xhr.responseJSON.error) || 'Could not create the deposit.', false);
            });
        });

        pollDeposits();
        setInterval(pollDeposits, 6000);
    }

    function pollDeposits() {
        get('/wallet/minecraft/deposit/status').done(renderDeposits);
    }

    /* ----------------------------------------------------------- withdrawals */

    function withdrawalRow(row) {
        var cancel = row.status === 'pending'
            ? '<button type="button" class="mc__cancel" data-cancel="' + row.id + '">Cancel</button>'
            : '';

        return '<div class="mc__item">' +
            '<div class="mc__item_main"><strong>' + nf(row.game_amount) + '</strong> to ' + row.player + '</div>' +
            '<div class="mc__item_coins">-' + Number(row.coins).toFixed(2) + ' $</div>' +
            '<div class="mc__badge mc__badge_' + row.status + '">' + statusLabel(row.status) + '</div>' +
            cancel +
            '</div>';
    }

    function renderWithdrawals(data) {
        var pending = data.pending || [];
        var recent = data.recent || [];

        $('#mc_pending').html(pending.length
            ? pending.map(withdrawalRow).join('')
            : '<div class="mc__empty">No payouts in the queue.</div>');

        $('#mc_recent').html(recent.length
            ? recent.map(withdrawalRow).join('')
            : '<div class="mc__empty">Nothing here yet.</div>');

        if (typeof data.available !== 'undefined') {
            $('.mc__row').filter(function () {
                return $(this).text().indexOf('Bot can pay') === 0;
            }).find('strong').text(nf(data.available));
        }
    }

    function initWithdraw() {
        if (!$('#mc_withdraw').length) return;

        $('#mc_withdraw').on('click', function () {
            var amount = parseInt($('#mc_amount').val(), 10);

            if (!amount || amount < 1) {
                return hint('Enter the payout amount in game.', false);
            }

            var $button = $(this).prop('disabled', true);

            post('/wallet/minecraft/withdraw', { amount: amount }).done(function (result) {
                $button.prop('disabled', false);

                if (!result.ok) {
                    return hint(result.error || 'Could not create the payout.', false);
                }

                hint('Payout queued. The bot will /pay you shortly.', true);
                pollWithdrawals();
            }).fail(function (xhr) {
                $button.prop('disabled', false);
                hint((xhr.responseJSON && xhr.responseJSON.error) || 'Could not create the payout.', false);
            });
        });

        $(document).on('click', '[data-cancel]', function () {
            var id = $(this).data('cancel');

            post('/wallet/minecraft/withdraw/cancel', { id: id }).done(function (result) {
                if (!result.ok) {
                    return hint(result.error || 'Could not cancel that payout.', false);
                }

                hint('Payout cancelled and coins returned.', true);
                pollWithdrawals();
            });
        });

        pollWithdrawals();
        setInterval(pollWithdrawals, 6000);
    }

    function pollWithdrawals() {
        get('/wallet/minecraft/withdraw/status').done(renderWithdrawals);
    }

    $(function () {
        initLink();
        initDeposit();
        initWithdraw();
    });
})();
