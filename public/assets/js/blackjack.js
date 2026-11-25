/*
 * DonutTrix blackjack. The server owns the shoe, the hands and the payout;
 * this file only sends actions and draws what it is told.
 */
(function () {
    var CSRF = $('meta[name="csrf-token"]').attr('content');
    var MIN_BET = Number(window.DONUT_MIN_BET) || 10000;

    function money(value) {
        return '$' + Number(value || 0).toLocaleString('en-US', { maximumFractionDigits: 2 });
    }
    var SUITS = { spades: '♠', hearts: '♥', diamonds: '♦', clubs: '♣' };
    var RESULTS = {
        win: 'You win',
        blackjack: 'Blackjack!',
        push: 'Push',
        lose: 'Dealer wins',
        bust: 'Bust'
    };
    var running = false;

    function hint(text) {
        $('#bj_hint').text(text);
    }

    function post(url, data, done) {
        $.post(url, $.extend({ _token: CSRF }, data || {}))
            .then(done)
            .fail(function () {
                hint('Request failed, try again.');
            });
    }

    function balance(value) {
        if (typeof updateBalance === 'function') {
            updateBalance(value);
        }
    }

    function cardHtml(card) {
        if (!card || card.suit === 'back') {
            return '<div class="bj__card bj__card_back"><span>?</span></div>';
        }

        var red = card.suit === 'hearts' || card.suit === 'diamonds';

        return '<div class="bj__card' + (red ? ' bj__card_red' : '') + '">' +
            '<span class="bj__rank">' + card.rank + '</span>' +
            '<span class="bj__suit">' + (SUITS[card.suit] || '') + '</span>' +
        '</div>';
    }

    function hand(cards, target) {
        $(target).html((cards || []).map(cardHtml).join(''));
    }

    function begin() {
        running = true;
        $('#bj_deal').hide();
        $('#bj_actions').css('display', 'flex');
    }

    function end() {
        running = false;
        $('#bj_actions').hide();
        $('#bj_deal').show();
    }

    function paint(round) {
        hand(round.dealer, '#bj_dealer');
        hand(round.player, '#bj_player');

        $('#bj_player_value').text(round.player_value);
        $('#bj_player_total').text(round.player_value);
        $('#bj_dealer_value').text(round.dealer_hidden ? round.dealer_value + ' + ?' : round.dealer_value);
        $('#bj_dealer_total').text(round.dealer_hidden ? round.dealer_value : round.dealer_value);
        $('#bj_bet').text(Number(round.bet).toFixed(2));

        if (round.status === 'player') {
            begin();
            $('#bj_hit').prop('disabled', !round.can_hit);
            $('#bj_double').prop('disabled', !round.can_double);
        } else {
            end();
        }
    }

    function settle(data) {
        paint(data.round);
        balance(data.balance);

        $('#bj_banner')
            .attr('class', 'bj__banner bj__banner_' + data.result)
            .text(RESULTS[data.result] || '')
            .prop('hidden', false);

        if (data.payout > 0) {
            hint(RESULTS[data.result] + ' — returned ' + Number(data.payout).toFixed(2) + ' $');
        } else {
            hint(RESULTS[data.result]);
        }

        end();
    }

    function betAmount() {
        var bet = parseFloat($('.input__bet').val());
        return isNaN(bet) ? 0 : bet;
    }

    /* Quick bet buttons, same behaviour as the other games' sidebar. */
    $(document).on('click', '[data-bet]', function () {
        var $input = $('.input__bet');
        var value = parseFloat($input.val()) || 0;
        var action = String($(this).data('bet'));

        if (action === 'min') {
            value = MIN_BET;
        } else if (action === 'max') {
            var shown = parseFloat(String($('#balance').text()).replace(/[^0-9.]/g, ''));
            value = isNaN(shown) ? value : shown;
        } else {
            value = Math.max(MIN_BET, value + parseFloat(action));
        }

        $input.val(Math.round(value * 100) / 100);
    });

    $(document).on('click', '#bj_deal', function () {
        if (running) {
            return hint('Finish the hand in play first');
        }

        var bet = betAmount();

        if (bet < 1) {
            return hint('The minimum bet is ' + money(MIN_BET));
        }

        $('#bj_banner').prop('hidden', true).text('');

        post('/blackjack/start', { bet: bet }, function (res) {
            if (res.type === 'error') {
                return hint(res.msg);
            }

            if (res.round.status === 'done') {
                return settle(res);
            }

            balance(res.balance);
            paint(res.round);
            hint(res.round.player_value === 21 ? 'Blackjack!' : 'Hit, stand or double.');
        });
    });

    $(document).on('click', '#bj_hit', function () {
        post('/blackjack/hit', {}, function (res) {
            if (res.type === 'error') {
                return hint(res.msg);
            }

            if (res.round.status === 'done') {
                return settle(res);
            }

            paint(res.round);
            hint('Your hand: ' + res.round.player_value);
        });
    });

    $(document).on('click', '#bj_stand', function () {
        post('/blackjack/stand', {}, function (res) {
            if (res.type === 'error') {
                return hint(res.msg);
            }

            settle(res);
        });
    });

    $(document).on('click', '#bj_double', function () {
        post('/blackjack/double', {}, function (res) {
            if (res.type === 'error') {
                return hint(res.msg);
            }

            settle(res);
        });
    });

    $(function () {
        $.get('/blackjack/state').then(function (res) {
            if (res.active && res.round) {
                paint(res.round);
                hint('Hand in play — hit, stand or double.');
            }
        });
    });
})();
