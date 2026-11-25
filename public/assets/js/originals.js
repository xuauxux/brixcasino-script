/*
 * DonutTrix originals — Towers, Coin Flip, HiLo, Limbo, Plinko and Keno.
 * Every outcome comes from the server; this file only draws the round.
 */
(function () {
    var CSRF = $('meta[name="csrf-token"]').attr('content');
    var MIN_BET = Number(window.DONUT_MIN_BET) || 10000;

    function money(value) {
        return '$' + Number(value || 0).toLocaleString('en-US', { maximumFractionDigits: 2 });
    }

    function post(url, data, done) {
        $.post(url, $.extend({ _token: CSRF }, data))
            .then(done)
            .fail(function () {
                message('Request failed, try again.');
            });
    }

    function balance(value) {
        if (typeof updateBalance === 'function') updateBalance(value);
    }

    function betAmount() {
        var bet = parseFloat($('.input__bet').val());
        return isNaN(bet) ? 0 : bet;
    }

    function message(text, target) {
        var $el = target ? $(target) : $('.games__result');
        if ($el.length) $el.text(text);
    }

    /* ------------------------------------------------------------ quick bets */

    $(document).on('click', '[data-bet]', function () {
        var $sidebar = $(this).closest('.games__sidebar');
        var $input = $sidebar.find('.input__bet');
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

    /* ---------------------------------------------------------------- towers */

    function initTowers() {
        var $board = $('#towers_board');
        if (!$board.length) return;

        var optionsByDifficulty = { easy: 4, medium: 3, hard: 2 };
        var game = { active: false, step: 0, options: 4, floors: 8, difficulty: 'easy', mult: 1, bet: 0 };

        function render(reveal) {
            var html = '';

            for (var floor = game.floors - 1; floor >= 0; floor--) {
                var passed = game.step > floor;
                html += '<div class="towers__row' + (passed ? ' towers__row_passed' : '') + (game.step === floor && game.active ? ' towers__row_current' : '') + '" data-floor="' + floor + '">';

                for (var tile = 0; tile < game.options; tile++) {
                    html += '<button type="button" class="towers__tile' + (passed ? ' towers__tile_safe' : '') + '" data-tile="' + tile + '"' + (passed || !game.active ? ' disabled' : '') + '></button>';
                }

                html += '</div>';
            }

            $board.html(html);

            if (reveal) {
                var $row = $board.find('[data-floor="' + reveal.floor + '"]');
                $row.addClass('towers__row_boom');
                $row.find('[data-tile="' + reveal.pick + '"]').addClass('towers__tile_boom');
                $row.find('[data-tile="' + reveal.safe + '"]').addClass('towers__tile_safe');
            }
        }

        $('.tower__diff').on('click', function () {
            if (game.active) return;
            $('.tower__diff').removeClass('active');
            $(this).addClass('active');
            game.difficulty = $(this).data('tower-difficulty');
            game.options = optionsByDifficulty[game.difficulty] || 4;
            render();
        });

        $('#towers_play').on('click', function () {
            var bet = betAmount();
            if (bet < MIN_BET) return message('The minimum bet is ' + money(MIN_BET), '#towers_hint');

            post('/originals/towers/start', { bet: bet, difficulty: game.difficulty }, function (e) {
                if (e.type !== 'success') return message(e.msg, '#towers_hint');

                game.active = true;
                game.step = 0;
                game.options = e.options;
                game.floors = e.floors;
                game.bet = bet;
                game.mult = 1;

                $('#towers_mult').text('1.00x');
                $('#towers_cash').text('0.00');
                $('#towers_play').hide();
                $('#towers_take').show();
                balance(e.balance);
                render();
            });
        });

        $board.on('click', '.towers__tile', function () {
            if (!game.active) return;

            var floor = parseInt($(this).closest('.towers__row').data('floor'), 10);
            if (floor !== game.step) return;

            var pick = parseInt($(this).data('tile'), 10);

            post('/originals/towers/open', { pick: pick }, function (e) {
                if (e.type !== 'success') return message(e.msg);

                if (e.out === 'safe') {
                    game.step = e.step;
                    game.mult = e.mult;
                    $('#towers_mult').text(Number(e.mult).toFixed(2) + 'x');
                    $('#towers_cash').text((game.bet * e.mult).toFixed(2));
                    render();

                    if (e.complete) $('#towers_take').trigger('click');
                    return;
                }

                game.active = false;
                render({ floor: game.step, pick: pick, safe: e.safe });
                $('#towers_mult').text('0.00x');
                $('#towers_cash').text('0.00');
                $('#towers_take').hide();
                balance(e.balance);

                setTimeout(function () {
                    $('#towers_play').show();
                    game.step = 0;
                    render();
                }, 1400);
            });
        });

        $('#towers_take').on('click', function () {
            post('/originals/towers/take', {}, function (e) {
                if (e.type !== 'success') return message(e.msg);

                game.active = false;
                game.step = 0;
                $('#towers_mult').text('0.00x');
                $('#towers_cash').text('0.00');
                $('#towers_take').hide();
                $('#towers_play').show();
                balance(e.balance);
                render();

                $.notify({ message: 'Cashed out ' + money(e.cash) }, { type: 'success' });
            });
        });

        render();
    }

    /* -------------------------------------------------------------- coin flip */

    function initFlip() {
        var $coin = $('#flip_coin');
        if (!$coin.length) return;

        var side = 'heads';

        $('.flip__side').on('click', function () {
            $('.flip__side').removeClass('active');
            $(this).addClass('active');
            side = $(this).data('flip-side');
        });

        $('#flip_play').on('click', function () {
            var bet = betAmount();
            if (bet < MIN_BET) return $('#flip_result').text('The minimum bet is ' + money(MIN_BET));

            var $button = $(this).prop('disabled', true);
            $coin.removeClass('flip__coin_heads flip__coin_tails').addClass('flip__coin_spin');
            $('#flip_result').text('Flipping…');

            post('/originals/flip', { bet: bet, side: side }, function (e) {
                if (e.type !== 'success') {
                    $button.prop('disabled', false);
                    $coin.removeClass('flip__coin_spin');
                    return $('#flip_result').text(e.msg);
                }

                setTimeout(function () {
                    $coin.removeClass('flip__coin_spin');
                    $coin.addClass('flip__coin_' + e.result);
                    var won = e.out === 'win';
                    $('#flip_result')
                        .removeClass('flip__result_win flip__result_lose')
                        .addClass(won ? 'flip__result_win' : 'flip__result_lose')
                        .text(won
                            ? 'Landed on ' + e.result + ' — you won ' + money(e.cash)
                            : 'Landed on ' + e.result + ' — better luck next flip');
                    balance(e.balance);
                    $button.prop('disabled', false);
                }, 800);
            });
        });
    }

    /* ------------------------------------------------------------------- hilo */

    function initHiLo() {
        var $card = $('#hilo_card');
        if (!$card.length) return;

        var suits = { spades: '♠', hearts: '♥', diamonds: '♦', clubs: '♣' };
        var red = { hearts: true, diamonds: true };
        var active = false;
        var bet = 0;

        function drawCard(card) {
            $('#hilo_rank').text(card.rank);
            $('#hilo_suit').text(suits[card.suit] || '♦');
            $card.toggleClass('hilo__card_red', !!red[card.suit]);
        }

        function showOdds(odds) {
            $('#hilo_higher_mult').text(Number(odds.higher).toFixed(2) + 'x');
            $('#hilo_lower_mult').text(Number(odds.lower).toFixed(2) + 'x');
            $('#hilo_higher').prop('disabled', odds.higher <= 0);
            $('#hilo_lower').prop('disabled', odds.lower <= 0);
        }

        function showState(e) {
            active = true;
            drawCard(e.card);
            showOdds(e.odds);
            $('#hilo_mult').text(Number(e.mult).toFixed(2) + 'x');
            $('#hilo_cash').text(Number(e.cash).toFixed(2));
            $('#hilo_start').hide();
            $('#hilo_actions').show();
            $('#hilo_take').show();
        }

        function reset(text) {
            active = false;
            $('#hilo_start').show();
            $('#hilo_actions').hide();
            $('#hilo_take').hide();
            $('#hilo_mult').text('0.00x');
            $('#hilo_cash').text('0.00');
            $('#hilo_result').text(text);
        }

        $('#hilo_start').on('click', function () {
            bet = betAmount();
            if (bet < MIN_BET) return $('#hilo_result').text('The minimum bet is ' + money(MIN_BET));

            post('/originals/hilo/start', { bet: bet }, function (e) {
                if (e.type !== 'success') return $('#hilo_result').text(e.msg);
                showState(e);
                balance(e.balance);
                $('#hilo_result').text('Guess if the next card is higher or lower');
            });
        });

        $('.hilo__guess').on('click', function () {
            if (!active) return;

            post('/originals/hilo/guess', { guess: $(this).hasClass('hilo__higher') ? 'higher' : 'lower' }, function (e) {
                if (e.type !== 'success') return $('#hilo_result').text(e.msg);

                if (e.out === 'lose') {
                    drawCard(e.card);
                    balance(e.balance);
                    return reset('Wrong guess — round over');
                }

                showState(e);
                $('#hilo_result').text(e.out === 'push' ? 'Same rank — no change, guess again' : 'Correct!');
            });
        });

        $('#hilo_take').on('click', function () {
            post('/originals/hilo/take', {}, function (e) {
                if (e.type !== 'success') return $('#hilo_result').text(e.msg);
                balance(e.balance);
                reset('Cashed out ' + money(e.cash) + ' at ' + Number(e.mult).toFixed(2) + 'x');
            });
        });
    }

    /* ------------------------------------------------------------------ limbo */

    function initLimbo() {
        var $value = $('#limbo_value');
        if (!$value.length) return;

        function target() {
            var t = parseFloat($('.input__target').val());
            return isNaN(t) ? 2 : t;
        }

        function preview() {
            var t = Math.min(1000, Math.max(1.01, target()));
            $('#limbo_chance').text((99 / t).toFixed(2) + '%');
            $('#limbo_payout').text((betAmount() * t).toFixed(2));
        }

        $('.input__target').on('input', preview);

        $('[data-target]').on('click', function () {
            $('.input__target').val($(this).data('target'));
            preview();
        });

        $(document).on('click', '[data-bet]', preview);

        $('#limbo_play').on('click', function () {
            var bet = betAmount();
            if (bet < MIN_BET) return $('#limbo_result').text('The minimum bet is ' + money(MIN_BET));

            var t = Math.min(1000, Math.max(1.01, target()));
            var $button = $(this).prop('disabled', true);

            $value.removeClass('limbo__value_win limbo__value_lose').text('1.00x');
            $('#limbo_result').text('Rolling…');

            post('/originals/limbo', { bet: bet, target: t }, function (e) {
                if (e.type !== 'success') {
                    $button.prop('disabled', false);
                    return $('#limbo_result').text(e.msg);
                }

                var started = performance.now();
                var duration = 650;

                function frame(now) {
                    var progress = Math.min(1, (now - started) / duration);
                    var eased = 1 - Math.pow(1 - progress, 3);
                    $value.text((1 + (e.crash - 1) * eased).toFixed(2) + 'x');

                    if (progress < 1) return requestAnimationFrame(frame);

                    var won = e.out === 'win';
                    $value.addClass(won ? 'limbo__value_win' : 'limbo__value_lose');
                    $('#limbo_result').text(won
                        ? 'Crashed at ' + Number(e.crash).toFixed(2) + 'x — you won ' + money(e.cash)
                        : 'Crashed at ' + Number(e.crash).toFixed(2) + 'x — under your ' + Number(e.target).toFixed(2) + 'x target');
                    balance(e.balance);
                    $button.prop('disabled', false);
                }

                requestAnimationFrame(frame);
            });
        });

        preview();
    }

    /* ----------------------------------------------------------------- plinko */

    function initPlinko() {
        var canvas = document.getElementById('plinko_canvas');
        if (!canvas) return;

        var ctx = canvas.getContext('2d');
        var rows = 12;
        var risk = 'medium';
        var animating = false;

        var buckets = {
            8: {
                low: [8.08, 2.94, 1.47, 0.73, 0.37, 0.73, 1.47, 2.94, 8.08],
                medium: [16.19, 4.42, 1.47, 0.52, 0.15, 0.52, 1.47, 4.42, 16.19],
                high: [28.50, 5.94, 1.19, 0.24, 0.12, 0.24, 1.19, 5.94, 28.50]
            },
            12: {
                low: [10.45, 6.27, 4.18, 2.09, 1.04, 0.63, 0.42, 0.63, 1.04, 2.09, 4.18, 6.27, 10.45],
                medium: [14.77, 7.95, 4.54, 2.27, 1.14, 0.57, 0.23, 0.57, 1.14, 2.27, 4.54, 7.95, 14.77],
                high: [31.44, 15.72, 6.29, 2.62, 1.05, 0.31, 0.10, 0.31, 1.05, 2.62, 6.29, 15.72, 31.44]
            },
            16: {
                low: [20.74, 11.67, 7.78, 5.18, 2.59, 1.30, 0.91, 0.65, 0.52, 0.65, 0.91, 1.30, 2.59, 5.18, 7.78, 11.67, 20.74],
                medium: [34.67, 19.07, 10.40, 6.07, 3.47, 1.73, 0.87, 0.43, 0.26, 0.43, 0.87, 1.73, 3.47, 6.07, 10.40, 19.07, 34.67],
                high: [75.03, 37.51, 16.67, 8.34, 4.17, 1.67, 0.67, 0.33, 0.17, 0.33, 0.67, 1.67, 4.17, 8.34, 16.67, 37.51, 75.03]
            }
        };

        var W = canvas.width;
        var H = canvas.height;
        var topY = 40;
        var bottomY = H - 90;
        var activeSlot = -1;

        function geometry() {
            var gap = Math.min(58, (W - 90) / rows);
            var rowGap = (bottomY - topY) / rows;
            return { gap: gap, rowGap: rowGap };
        }

        function pegPosition(row, index, g) {
            var count = row + 1;
            var startX = W / 2 - (count - 1) * g.gap / 2;
            return { x: startX + index * g.gap, y: topY + row * g.rowGap };
        }

        function draw() {
            var g = geometry();
            var table = buckets[rows][risk];
            var bucketWidth = Math.min(52, (W - 40) / table.length);

            ctx.clearRect(0, 0, W, H);

            for (var row = 0; row <= rows; row++) {
                for (var i = 0; i < row + 1; i++) {
                    var p = pegPosition(row, i, g);
                    ctx.beginPath();
                    ctx.arc(p.x, p.y, 3.2, 0, Math.PI * 2);
                    ctx.fillStyle = 'rgba(255,255,255,.28)';
                    ctx.fill();
                }
            }

            for (var b = 0; b < table.length; b++) {
                var x = W / 2 - (table.length * bucketWidth) / 2 + b * bucketWidth;

                ctx.beginPath();
                ctx.roundRect(x + 2, bottomY + 10, bucketWidth - 4, 34, 8);
                ctx.fillStyle = b === activeSlot ? '#c9f73a' : 'rgba(0,0,0,.45)';
                ctx.fill();
                ctx.strokeStyle = b === activeSlot ? '#c9f73a' : 'rgba(255,255,255,.12)';
                ctx.lineWidth = 1;
                ctx.stroke();

                ctx.fillStyle = b === activeSlot ? '#11160a' : 'rgba(255,255,255,.72)';
                ctx.font = '700 11px Outfit, sans-serif';
                ctx.textAlign = 'center';
                ctx.textBaseline = 'middle';
                ctx.fillText(table[b].toFixed(2) + 'x', x + bucketWidth / 2, bottomY + 27);
            }
        }

        function animate(path) {
            var g = geometry();
            var index = 0;
            var step = 0;
            var current = { x: W / 2, y: 6 };

            animating = true;

            function frame() {
                if (step >= path.length) {
                    animating = false;
                    draw();
                    return;
                }

                var target = pegPosition(step + 1, index + path[step], g);
                index += path[step];
                step++;

                var fromX = current.x;
                var fromY = current.y;
                var started = performance.now();

                function move(now) {
                    var progress = Math.min(1, (now - started) / 150);
                    var eased = Math.sin((progress * Math.PI) / 2);

                    current.x = fromX + (target.x - fromX) * eased;
                    current.y = fromY + (target.y - fromY) * eased;

                    draw();
                    ctx.beginPath();
                    ctx.arc(current.x, current.y, 6, 0, Math.PI * 2);
                    ctx.fillStyle = '#c9f73a';
                    ctx.fill();

                    if (progress < 1) return requestAnimationFrame(move);
                    requestAnimationFrame(frame);
                }

                requestAnimationFrame(move);
            }

            requestAnimationFrame(frame);
        }

        $('.plinko__risk').on('click', function () {
            if (animating) return;
            $('.plinko__risk').removeClass('active');
            $(this).addClass('active');
            risk = $(this).data('plinko-risk');
            activeSlot = -1;
            draw();
        });

        $('.plinko__rows').on('click', function () {
            if (animating) return;
            $('.plinko__rows').removeClass('active');
            $(this).addClass('active');
            rows = parseInt($(this).data('plinko-rows'), 10);
            activeSlot = -1;
            draw();
        });

        $('#plinko_play').on('click', function () {
            if (animating) return;

            var bet = betAmount();
            if (bet < 1) return;

            var $button = $(this).prop('disabled', true);

            post('/originals/plinko', { bet: bet, rows: rows, risk: risk }, function (e) {
                if (e.type !== 'success') return $button.prop('disabled', false);

                balance(e.balance);
                animate(e.path);

                setTimeout(function () {
                    activeSlot = e.slot;
                    draw();
                    $button.prop('disabled', false);
                }, e.path.length * 160 + 120);
            });
        });

        draw();
    }

    /* ------------------------------------------------------------------- keno */

    function initKeno() {
        var $grid = $('#keno_grid');
        if (!$grid.length) return;

        var picks = [];

        for (var n = 1; n <= 40; n++) {
            $grid.append('<button type="button" class="keno__cell" data-num="' + n + '">' + n + '</button>');
        }

        function refresh() {
            $('#keno_picked').text(picks.length + ' / 10');
            $('.keno__cell').removeClass('keno__cell_picked');
            picks.forEach(function (n) {
                $grid.find('[data-num="' + n + '"]').addClass('keno__cell_picked');
            });
        }

        $grid.on('click', '.keno__cell', function () {
            var num = parseInt($(this).data('num'), 10);
            var at = picks.indexOf(num);

            if (at >= 0) {
                picks.splice(at, 1);
            } else if (picks.length < 10) {
                picks.push(num);
            }

            refresh();
        });

        $('#keno_clear').on('click', function () {
            picks = [];
            $('.keno__cell').removeClass('keno__cell_drawn keno__cell_hit');
            $('#keno_hits').text('0');
            refresh();
        });

        $('#keno_play').on('click', function () {
            if (!picks.length) return;

            var bet = betAmount();
            if (bet < 1) return;

            var $button = $(this).prop('disabled', true);

            post('/originals/keno', { bet: bet, picks: picks }, function (e) {
                if (e.type !== 'success') return $button.prop('disabled', false);

                $('.keno__cell').removeClass('keno__cell_drawn keno__cell_hit');

                e.draw.forEach(function (num, index) {
                    setTimeout(function () {
                        var $cell = $grid.find('[data-num="' + num + '"]');
                        $cell.addClass('keno__cell_drawn');

                        if (picks.indexOf(num) >= 0) {
                            $cell.addClass('keno__cell_hit');
                            $('#keno_hits').text(function (_, hits) {
                                return parseInt(hits, 10) + 1;
                            });
                        }

                        if (index === e.draw.length - 1) $button.prop('disabled', false);
                    }, index * 45);
                });

                $('#keno_hits').text('0');
                balance(e.balance);
            });
        });

        refresh();
    }

    $(function () {
        initTowers();
        initFlip();
        initHiLo();
        initLimbo();
        initPlinko();
        initKeno();
    });
})();
