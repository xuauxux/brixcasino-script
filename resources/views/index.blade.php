<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="yandex-verification" content="5116afe53bf0aaa1" />
    <meta name="csrf-token" content="{{ csrf_token() }}" />
    <meta content="donuttrix, online casino, lottery, games, free bonus, nvuti, dragon, cabura" name="keywords">
    <meta name="description" content="DonutTrix is a DonutSMP arcade with instant games, money rains and daily activity leaderboards. Sign in with your Minecraft name and play.">

    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="stylesheet" href="/assets/css/home.css?v=3">
    <link rel="icon" href="/assets/images/favicon.ico" type="image/x-icon">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="/assets/css/notifyme.css">

    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.3/jquery.min.js" integrity="sha512-STof4xm1wgkfm7heWqFJVn58Hm3EtS31XFaagaa8VMReCXAkQnJZ+jEy8PCC/iT18dFy95WcExNHFTqLyp72eQ==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
    <script src='https://cdnjs.cloudflare.com/ajax/libs/socket.io/2.0.3/socket.io.js'></script>
    <script src="/assets/js/notifyme.min.js"></script>
    <script src="/assets/js/app.js"></script>
    <script src="/assets/js/socket.js"></script>

    <script type="text/javascript" >
        (function(m,e,t,r,i,k,a){m[i]=m[i]||function(){(m[i].a=m[i].a||[]).push(arguments)};
        m[i].l=1*new Date();
        for (var j = 0; j < document.scripts.length; j++) {if (document.scripts[j].src === r) { return; }}
        k=e.createElement(t),a=e.getElementsByTagName(t)[0],k.async=1,k.src=r,a.parentNode.insertBefore(k,a)})
        (window, document, "script", "https://mc.yandex.ru/metrika/tag.js", "ym");

        ym(92949077, "init", {
                clickmap:true,
                trackLinks:true,
                accurateTrackBounce:true
        });
    </script>
    <noscript><div><img src="https://mc.yandex.ru/watch/92949077" style="position:absolute; left:-9999px;" alt="" /></div></noscript>

    <title>DONUTTRIX - instant games</title>
</head>
<body>
    <div class="navbar">
        <div class="logotype">
            <a href="/" class="logo">
                <span>DONUT<span class="green-x">TRIX</span></span>
            </a>
        </div> 
        <div class="navmenu">
            <ul class="navmenu__list">
                <li class="navmenu__item">
                    <a href="/" class="navmenu__item_link active">Home</a>
                </li>
                <li class="navmenu__item">
                    <a href="/leaderboards" class="navmenu__item_link">Leaderboard</a>
                </li>
                <li class="navmenu__item">
                    <a href="/event" class="navmenu__item_link">Rain</a>
                </li>
                @auth
                <li class="navmenu__item">
                    <a href="/bonus" class="navmenu__item_link">Bonuses</a>
                </li>
                <li class="navmenu__item">
                    <a href="/profile" class="navmenu__item_link">Profile</a>
                </li>
                <li class="navmenu__item">
                    <a href="/referrals" class="navmenu__item_link">Affiliates</a>
                </li>
                @endauth
            </ul>
        </div>
        <div class="nav__balance">
            <span class="nav__balance_label">Balance</span>
            <span class="nav__balance_value"><span id="nav_balance">{{ Auth::check() ? Auth::user()->balance : '0.00' }}</span> <span class="ruble">$</span></span>
        </div>
        @guest
        <div class="auth__btn">
            Sign in
        </div>
        @endguest
        @auth
        <div class="user__wallet">
            <div class="user__balance">
                <!-- <i class='bx bxs-coin-stack'></i> -->
                <span id="balance">{{Auth::user()->balance}}</span> <span class="ruble">$</span>
            </div>
            <a href="/wallet/pay" class="wallet_up_btn">
                Wallet
            </a>
        </div>
        @endauth
    </div>
    <div class="mobile_menu">
        <div class="mobile_menu__content">
            <a href="/" class="mobile_menu__link">
                <i class='bx bxs-home' ></i>
                Home
            </a>
            <a href="/leaderboards" class="mobile_menu__link">
                <i class='bx bxs-trophy' ></i>
                Leaderboard
            </a>
            <a href="/event" class="mobile_menu__link">
                <i class='bx bxs-star' ></i>
                Rain
            </a>
            <a href="/bonus" class="mobile_menu__link">
                <i class='bx bxs-gift' ></i>
                Bonuses
            </a>
            <a href="/referrals" class="mobile_menu__link">
                <i class='bx bxs-user-plus' ></i>
                Affiliates
            </a>
            <a href="/profile" class="mobile_menu__link">
                <i class='bx bxs-user-circle' ></i>
                Profile
            </a>
            @if(Auth::check() && Auth::user()->admin == 1)
            <a href="/admin" class="mobile_menu__link">
                <i class='bx bxs-window-alt'></i>
                Admin
            </a>
            @endif
        </div>
    </div>
    <div class="fix__left_nav">
        <div class="leftside__games">
            <div class="leftside__game">
                <a href="/dice">
                    <i class='bx bx-dice-5'></i>
                    <span class="leftside__label">Dice</span>
                </a>
            </div>
            <div class="leftside__game">
                <a href="/mines">
                    <i class='bx bx-bomb' ></i>
                    <span class="leftside__label">Mines</span>
                </a>
            </div>
            <div class="leftside__game">
                <a href="/wheel">
                    <i class='bx bx-color'></i>
                    <span class="leftside__label">Wheel</span>
                </a>
            </div>
            <div class="leftside__game">
                <a href="/jackpot">
                    <i class='bx bx-crown'></i>
                    <span class="leftside__label">Jackpot</span>
                </a>
            </div>
            <div class="leftside__game">
                <a href="/crash">
                    <i class='bx bx-line-chart'></i>
                    <span class="leftside__label">Crash</span>
                </a>
            </div>
            <div class="leftside__game">
                <a href="/towers" title="Towers">
                    <i class='bx bx-buildings'></i>
                    <span class="leftside__label">Towers</span>
                </a>
            </div>
            <div class="leftside__game">
                <a href="/flip" title="Coin Flip">
                    <i class='bx bx-coin'></i>
                    <span class="leftside__label">Coin Flip</span>
                </a>
            </div>
            <div class="leftside__game">
                <a href="/hilo" title="HiLo">
                    <i class='bx bx-sort-alt-2'></i>
                    <span class="leftside__label">HiLo</span>
                </a>
            </div>
            <div class="leftside__game">
                <a href="/limbo" title="Limbo">
                    <i class='bx bx-rocket'></i>
                    <span class="leftside__label">Limbo</span>
                </a>
            </div>
            <div class="leftside__game">
                <a href="/plinko" title="Plinko">
                    <i class='bx bx-down-arrow-circle'></i>
                    <span class="leftside__label">Plinko</span>
                </a>
            </div>
            <div class="leftside__game">
                <a href="/keno" title="Keno">
                    <i class='bx bx-grid-alt'></i>
                    <span class="leftside__label">Keno</span>
                </a>
            </div>
            <div class="leftside__game">
                <a href="/blackjack" title="Blackjack">
                    <i class='bx bxs-diamond'></i>
                    <span class="leftside__label">Blackjack</span>
                </a>
            </div>
        </div>
        <div class="leftside__divider"></div>
        @auth
        <div class="leftside__tools">
            <div class="leftside__game">
                <a href="/wallet/pay" title="Deposit">
                    <i class='bx bx-down-arrow-circle'></i>
                    <span class="leftside__label">Deposit</span>
                </a>
            </div>
            <div class="leftside__game">
                <a href="/wallet/withdraw" title="Withdraw">
                    <i class='bx bx-up-arrow-circle'></i>
                    <span class="leftside__label">Withdraw</span>
                </a>
            </div>
        </div>
        @endauth
        <div class="leftside__tools">
            <div class="leftside__game">
                <a href="/chat" title="Live chat">
                    <i class='bx bx-chat'></i>
                    <span class="leftside__label">Live chat</span>
                </a>
            </div>
        </div>
        <div class="leftside__social">
            <div class="social social_discord">
                <a href="https://discord.gg/qKMrjbMWVH" target="_blank" rel="noopener">
                    <i class="bx bxl-discord-alt"></i>
                    <span class="leftside__label">Discord</span>
                </a>
            </div>
            @if(Auth::check() && Auth::user()->admin == 1)
            <div class="leftside__game">
                <a href="/admin">
                    <i class='bx bxs-window-alt'></i>
                    <span class="leftside__label">Admin</span>
                </a>
            </div>
            @endif
            @if(Auth::check() && Auth::user()->admin == 1)
            <div class="leftside__online">
                <span class="site__online">
                    <span class="online__dot"></span>
                    <span class="online"></span>
                </span>
            </div>
            @endif
        </div>
    </div>
    <div class="main__content">
        <section class="home__hero">
            <div class="home__hero_pattern"></div>
            <div class="home__hero_body">
                <span class="home__hero_badge">
                    <i class='bx bx-dice-5'></i>
                    Welcome to DonutTrix
                </span>
                <h1 class="home__hero_title">Got questions? Join our Discord</h1>
                <p class="home__hero_text">Giveaways, updates and support, all in our Discord.</p>
                <div class="home__hero_actions">
                    <a href="https://discord.gg/qKMrjbMWVH" target="_blank" rel="noopener noreferrer" class="home__hero_discord">
                        <i class="bx bxl-discord-alt"></i>
                        Join Discord
                    </a>
                    @guest
                    <div class="home__hero_btn auth__btn">Registration</div>
                    <div class="home__hero_btn home__hero_btn_ghost auth__btn">Log In</div>
                    @endguest
                </div>
            </div>
            <div class="home__hero_art" aria-hidden="true">
                <span class="home__hero_glow"></span>

                <div class="home__hero_card home__hero_card_red home__hero_card_back_l">
                    <span class="home__hero_corner">K<i>♦</i></span>
                    <span class="home__hero_pip">♦</span>
                    <span class="home__hero_corner home__hero_corner_b">K<i>♦</i></span>
                </div>

                <div class="home__hero_card home__hero_card_back_r">
                    <span class="home__hero_corner">A<i>♠</i></span>
                    <span class="home__hero_pip">♠</span>
                    <span class="home__hero_corner home__hero_corner_b">A<i>♠</i></span>
                </div>

                <div class="home__hero_card home__hero_card_front">
                    <span class="home__hero_corner">A<i>♠</i></span>
                    <span class="home__hero_pip">♠</span>
                    <span class="home__hero_corner home__hero_corner_b">A<i>♠</i></span>
                    <span class="home__hero_gloss"></span>
                </div>
            </div>
        </section>

        <div class="hx__strip">
            <div class="hx__stats">
                <a href="/leaderboards" class="hx__stat">
                    <span class="hx__stat_icon"><i class='bx bxs-trophy'></i></span>
                    <span class="hx__stat_body">
                        <span class="hx__stat_label">Leaderboard</span>
                        <strong>{{ $position ? '#'.$position['rank'] : 'Unranked' }}</strong>
                    </span>
                </a>
                <a href="/games" class="hx__stat">
                    <span class="hx__stat_icon"><i class='bx bxs-game'></i></span>
                    <span class="hx__stat_body">
                        <span class="hx__stat_label">Games</span>
                        <strong>{{ count($enabledGames) }}+</strong>
                    </span>
                </a>
                <a href="#rain-card" class="hx__stat">
                    <span class="hx__stat_icon"><i class='bx bx-money'></i></span>
                    <span class="hx__stat_body">
                        <span class="hx__stat_label">Money rain</span>
                        <strong>{{ $rain['active'] ? 'Live' : 'Auto' }}</strong>
                    </span>
                </a>
            </div>

            <section class="hx__rain {{ $rain['active'] ? 'is-live' : '' }}" id="rain-card" data-rain>
                <div class="hx__rain_main">
                    <div class="hx__rain_head">
                        <span class="hx__chip hx__chip_rain"><i class='bx bx-money'></i> Money rain</span>
                        <span class="hx__time">
                            <i class='bx bx-time-five'></i>
                            <span data-rain-timer>{{ $rain['active'] ? 'Ends in '.max(0, (int) now()->diffInSeconds(\Carbon\Carbon::parse($rain['active']->ends_at), false)).'s' : 'Every '.$rain['settings']['interval'].' minutes' }}</span>
                        </span>
                    </div>

                    <h2 class="hx__rain_title" data-rain-amount>${{ number_format($rain['active']->amount ?? $rain['settings']['amount']) }}</h2>
                    <p class="hx__rain_text" data-rain-state>
                        {{ $rain['active'] ? 'Live now. The pot is split between everyone who joins.' : 'The next drop starts automatically. Be online when it rains.' }}
                    </p>

                    <div class="hx__rain_foot">
                        @auth
                        <form method="POST" action="{{ route('rain.join') }}" class="hx__rain_form">
                            @csrf
                            <button class="hx__event_cta hx__rain_cta" type="submit" data-rain-join {{ $rain['active'] ? '' : 'disabled' }}>
                                {{ $rain['active'] ? 'Join the rain' : 'Waiting for the drop' }} <i class='bx bx-right-arrow-alt'></i>
                            </button>
                        </form>
                        @else
                        <button type="button" class="hx__event_cta hx__rain_cta js-auth">
                            Sign in to join <i class='bx bx-right-arrow-alt'></i>
                        </button>
                        @endauth
                        <span class="hx__rain_people" data-rain-people>{{ number_format($rain['active']->participants ?? 0) }} joined</span>
                    </div>
                </div>

                <div class="hx__rain_art" aria-hidden="true">
                    <span class="hx__rain_glow"></span>
                    <span class="hx__rain_bill hx__rain_bill_1">$</span>
                    <span class="hx__rain_bill hx__rain_bill_2">$</span>
                    <span class="hx__rain_bill hx__rain_bill_3">$</span>
                </div>
            </section>
        </div>

        <section class="hx__tiles">
            @auth
            <a href="/games" class="hx__tile hx__tile_wide">
            @else
            <a href="#" class="hx__tile hx__tile_wide js-auth">
            @endauth
                <span class="hx__tile_body">
                    <span class="hx__tile_title">Casino</span>
                    <span class="hx__tile_text">Play instant games &amp; earn big</span>
                    <span class="hx__tile_btn">{{ Auth::check() ? 'Play now' : 'Registration' }}</span>
                </span>
                <span class="hx__tile_badge"><i class='bx bxs-dice-5'></i></span>
            </a>

            <a href="#rain-card" class="hx__tile hx__tile_rain">
                <span class="hx__tile_title">Money rain</span>
                <span class="hx__tile_arrow"><i class='bx bx-down-arrow-circle'></i></span>
                <span class="hx__tile_badge"><i class='bx bx-money'></i></span>
            </a>

            @auth
            <a href="/bonus" class="hx__tile hx__tile_bonus">
            @else
            <a href="#" class="hx__tile hx__tile_bonus js-auth">
            @endauth
                <span class="hx__tile_title">Bonuses</span>
                <span class="hx__tile_arrow"><i class='bx bx-right-arrow-alt'></i></span>
                <span class="hx__tile_badge"><i class='bx bxs-gift'></i></span>
            </a>

            <a href="/leaderboards" class="hx__tile hx__tile_boards">
                <span class="hx__tile_title">Leaderboard</span>
                <span class="hx__tile_arrow"><i class='bx bx-right-arrow-alt'></i></span>
                <span class="hx__tile_badge"><i class='bx bxs-trophy'></i></span>
            </a>
        </section>

        <div class="games__container">
            <div class="games__grid">
                <a href="/dice" class="games__item">
                    <div class="games__card card_dice">
                        <div class="card_btn">
                            <span class="card__play">Play</span>
                        </div>
                    </div>
                </a>
                <a href="/mines" class="games__item">
                    <div class="games__card card_mines">
                        <div class="card_btn">
                            <span class="card__play">Play</span>
                        </div>
                    </div>
                </a>
                <a href="/wheel" class="games__item">
                    <div class="games__card card_wheel">
                        <div class="card_btn">
                            <span class="card__play">Play</span>
                        </div>
                    </div>
                </a>
                <a href="/jackpot" class="games__item">
                    <div class="games__card card_jackpot">
                        <div class="card_btn">
                            <span class="card__play">Play</span>
                        </div>
                    </div>
                </a>
                <a href="/crash" class="games__item">
                    <div class="games__card card_crash">
                        <div class="card_btn">
                            <span class="card__play">Play</span>
                        </div>
                    </div>
                </a>
                <a href="/towers" class="games__item">
                    <div class="games__card card_towers">
                        <div class="card_btn">
                            <span class="card__play">Play</span>
                        </div>
                    </div>
                </a>
                <a href="/flip" class="games__item">
                    <div class="games__card card_flip">
                        <div class="card_btn">
                            <span class="card__play">Play</span>
                        </div>
                    </div>
                </a>
                <a href="/hilo" class="games__item">
                    <div class="games__card card_hilo">
                        <div class="card_btn">
                            <span class="card__play">Play</span>
                        </div>
                    </div>
                </a>
                <a href="/limbo" class="games__item">
                    <div class="games__card card_limbo">
                        <div class="card_btn">
                            <span class="card__play">Play</span>
                        </div>
                    </div>
                </a>
                <a href="/plinko" class="games__item">
                    <div class="games__card card_plinko">
                        <div class="card_btn">
                            <span class="card__play">Play</span>
                        </div>
                    </div>
                </a>
                <a href="/keno" class="games__item">
                    <div class="games__card card_keno">
                        <div class="card_btn">
                            <span class="card__play">Play</span>
                        </div>
                    </div>
                </a>
                <a href="/blackjack" class="games__item">
                    <div class="games__card card_blackjack">
                        <div class="card_btn">
                            <span class="card__play">Play</span>
                        </div>
                    </div>
                </a>
            </div>
        </div>

        <section class="hx__promos">
            <div class="hx__promos_head">
                <i class='bx bxs-gift'></i>
                <h2>Promotions</h2>
                @auth
                <a href="/bonus">All bonuses <i class='bx bx-right-arrow-alt'></i></a>
                @else
                <a href="#" class="js-auth">All bonuses <i class='bx bx-right-arrow-alt'></i></a>
                @endauth
            </div>

            <div class="hx__promo_grid">
                <article class="hx__promo hx__promo_discord">
                    <div class="hx__promo_top">
                        <span class="hx__promo_tag">Community</span>
                        <span class="hx__promo_meta"><i class='bx bx-time-five'></i> 24/7</span>
                    </div>
                    <h3>Discord</h3>
                    <p>Giveaways, updates and support, say hi to the community.</p>
                    <span class="hx__promo_icon"><i class='bx bxl-discord-alt'></i></span>
                    <div class="hx__promo_foot">
                        <a href="https://discord.gg/qKMrjbMWVH" target="_blank" rel="noopener noreferrer" class="hx__promo_btn">
                            <i class='bx bxl-discord-alt'></i> Join Discord
                        </a>
                    </div>
                </article>

                <article class="hx__promo hx__promo_bonus">
                    <div class="hx__promo_top">
                        <span class="hx__promo_tag">Active</span>
                        <span class="hx__promo_meta"><i class='bx bx-time-five'></i> Every 24h</span>
                    </div>
                    <h3>Daily bonus</h3>
                    <p>Claim a free bonus every 24 hours before you hit the games.</p>
                    <span class="hx__promo_icon"><i class='bx bxs-gift'></i></span>
                    <div class="hx__promo_foot">
                        @auth
                        <a href="/bonus" class="hx__promo_btn">Open bonuses</a>
                        @else
                        <a href="#" class="hx__promo_btn js-auth">Registration</a>
                        @endauth
                    </div>
                </article>

                <article class="hx__promo hx__promo_race">
                    <div class="hx__promo_top">
                        <span class="hx__promo_tag">Top players</span>
                        <span class="hx__promo_meta"><i class='bx bx-time-five'></i> Daily, weekly, monthly</span>
                    </div>
                    <h3>Leaderboards</h3>
                    <p>See who has earned the most activity this day, week and month.</p>
                    <span class="hx__promo_icon"><i class='bx bxs-trophy'></i></span>
                    <div class="hx__promo_foot">
                        <a href="/leaderboards" class="hx__promo_btn">View boards</a>
                    </div>
                </article>

                <article class="hx__promo hx__promo_aff">
                    <div class="hx__promo_top">
                        <span class="hx__promo_tag">20M reward</span>
                        <span class="hx__promo_meta"><i class='bx bx-time-five'></i> Lifetime</span>
                    </div>
                    <h3>Affiliates</h3>
                    <p>Earn $20,000,000 for every verified player who joins on your link.</p>
                    <span class="hx__promo_icon"><i class='bx bxs-user-plus'></i></span>
                    <div class="hx__promo_foot">
                        @auth
                        <a href="/referrals" class="hx__promo_btn">Open affiliates</a>
                        @else
                        <a href="#" class="hx__promo_btn js-auth">Registration</a>
                        @endauth
                    </div>
                </article>
            </div>
        </section>

        <footer class="footer">
            <div class="footer__header">
                <div class="footer__logo">
                    <a href="/" class="logo">
                        <span>DONUT<span class="green-x">TRIX</span></span>
                    </a>
                    <strong class="footer_security">© 2023 DONUTTRIX. All rights reserved.</strong>
                </div>
                <div class="footer__warn footer__disclaimer">
                    <div class="warn_text">
                        <p>DonutTrix is an independent DonutSMP arcade with Mines, Towers, Coin Flip, Blackjack, Dice, HiLo, Limbo and more — provably fair rounds, instant deposits and 24/7 withdrawals.</p>
                        <p>We are an arcade and social platform, not a casino or a gambling company — no real-world money can be deposited, wagered or withdrawn. Not affiliated with Mojang AB, Microsoft or the DonutSMP server.</p>
                    </div>
                </div>
            </div>
            <div class="footer__bottom">
                <a href="/rules" class="footer__rules">Terms of use</a>
                <a href="/privacy" class="footer__privacy">Privacy policy</a>
            </div>
        </footer>
    </div>
    @guest
    <div class="modal__window modal_auth">
        <div class="modal__dialog modal__auth">
            <div class="modal">
                <div class="modal__close"><i class='bx bx-plus'></i></div>
                <div class="auth__modal">
                    <div class="auth__modal_content">
                        <div class="auth__modal_content_inner">
                            <div class="auth__modal_head">Sign in</div>
                            <div class="start_auth_modal">
                                <div class="ign_panel">
                                    <label class="ign_label">Your Minecraft name</label>
                                    <div class="games__sidebar_wrapper_input">
                                        <input type="text" class="games__sidebar_input ign_name" placeholder="IGN" maxlength="16" autocomplete="off">
                                    </div>
                                    <button type="button" class="sidebar__play ign_start_button" style="padding: 13px 15px">Continue</button>
                                    <div class="ign_code_box" hidden>
                                        <p class="ign_hint">Send this code to the bot on DonutSMP:</p>
                                        <div class="ign_code"></div>
                                        <p class="ign_state">Waiting for your message…</p>
                                    </div>
                                    <p class="ign_hint_small">New player? Confirming your IGN creates the account.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endguest

    @if(session()->has('error'))
    <script>
        $.notify({
            type: "error",
            message: "{{ session()->get('error') }}"
        });  
    </script>  
    @endif            
    @if(session()->has('success'))
    <script>
        $.notify({
            type: "success",
            message: "{{ session()->get('success') }}"
        });  
    </script>  
    @endif

    <script>
        $(document).on('click', '.js-auth', function (e) {
            e.preventDefault();
            $('.auth__btn').first().trigger('click');
        });

        // Money rain: keep the pot, countdown and join button in step with the
        // server without reloading the page.
        (() => {
            const root = document.querySelector('[data-rain]');
            if (!root) return;

            const stateEl = root.querySelector('[data-rain-state]');
            const timerEl = root.querySelector('[data-rain-timer]');
            const peopleEl = root.querySelector('[data-rain-people]');
            const amountEl = root.querySelector('[data-rain-amount]');
            const joinBtn = root.querySelector('[data-rain-join]');
            const idleTimer = root.classList.contains('is-live')
                ? 'Waiting for the next drop'
                : (timerEl ? timerEl.textContent.trim() : 'Automatic drops');

            let deadline = null;

            const renderTimer = () => {
                if (!timerEl || deadline === null) return;
                const left = Math.max(0, Math.round((deadline - Date.now()) / 1000));
                timerEl.textContent = 'Ends in ' + left + 's';
            };

            const tick = async () => {
                try {
                    const res = await fetch('/rain/status', { headers: { accept: 'application/json' } });
                    const data = await res.json();
                    root.classList.toggle('is-live', !!data.active);

                    if (data.active) {
                        if (amountEl && data.amount != null) {
                            amountEl.textContent = '$' + Number(data.amount).toLocaleString('en-US');
                        }
                        if (stateEl) stateEl.textContent = 'Live now. The pot is split between everyone who joins.';
                        if (peopleEl) peopleEl.textContent = Number(data.participants || 0).toLocaleString('en-US') + ' joined';
                        if (joinBtn) {
                            joinBtn.disabled = false;
                            joinBtn.innerHTML = 'Join the rain <i class="bx bx-right-arrow-alt"></i>';
                        }
                        deadline = Date.now() + Math.max(0, Number(data.seconds_left || 0)) * 1000;
                        renderTimer();
                    } else {
                        deadline = null;
                        if (timerEl) timerEl.textContent = idleTimer;
                        if (stateEl) stateEl.textContent = 'The next drop starts automatically. Be online when it rains.';
                        if (peopleEl) peopleEl.textContent = '0 joined';
                        if (joinBtn) {
                            joinBtn.disabled = true;
                            joinBtn.innerHTML = 'Waiting for the drop <i class="bx bx-right-arrow-alt"></i>';
                        }
                    }
                } catch (e) {
                    // Keep the server-rendered state when the poll fails.
                }
            };

            setInterval(renderTimer, 1000);
            tick();
            setInterval(tick, 12000);
        })();
    </script>

    <script async src="https://www.googletagmanager.com/gtag/js?id=G-2GBFZ1SSVQ"></script>
    <script>
        window.dataLayer = window.dataLayer || [];
        function gtag(){dataLayer.push(arguments);}
        gtag('js', new Date());

        gtag('config', 'G-2GBFZ1SSVQ');
    </script>
</body>
</html>
