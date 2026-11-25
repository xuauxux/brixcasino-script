<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}" />
    <meta content="donuttrix, online casino, lottery, games, free bonus, nvuti, dragon, cabura" name="keywords">
    <meta name="description" content="DonutTrix is a DonutSMP arcade with instant games, money rains and activity leaderboards, powered by your Minecraft name.">

    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="stylesheet" href="/assets/css/platform.css">
    <link rel="stylesheet" href="/assets/css/leaderboards.css">
    <link rel="icon" href="/assets/images/favicon.ico" type="image/x-icon">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="/assets/css/notifyme.css">

    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.3/jquery.min.js" integrity="sha512-STof4xm1wgkfm7heWqFJVn58Hm3EtS31XFaagaa8VMReCXAkQnJZ+jEy8PCC/iT18dFy95WcExNHFTqLyp72eQ==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
    <script>
        window.DONUT_MIN_BET = {{ (int) config('donut.min_bet') }};
        window.DONUT_CURRENCY = @json(config('donut.currency'));
    </script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-cookie/1.4.1/jquery.cookie.min.js" integrity="sha512-3j3VU6WC5rPQB4Ld1jnLV7Kd5xr+cq9avvhwqzbH/taCRNURoeEpoPBK9pDyeukwSxwRPJ8fDgvYXd6SkaZ2TA==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
    <script src='https://cdnjs.cloudflare.com/ajax/libs/socket.io/2.0.3/socket.io.js'></script>
    <script src="/assets/js/jquery.kinetic.min.js"></script>
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
    @if(Auth::check())
        <script>
            const client_user = {{Auth::User()->id}};
        </script>
    @endif
    <div class="navbar">
        <div class="logotype">
            <a href="/" class="logo">
                <span>DONUT<span class="green-x">TRIX</span></span>
            </a>
        </div> 
        <div class="navmenu">
            <ul class="navmenu__list">
                <li class="navmenu__item">
                    <a href="/" class="navmenu__item_link {{ request()->routeIs('index') ? 'active' : '' }}">Home</a>
                </li>
                <li class="navmenu__item">
                    <a href="/leaderboards" class="navmenu__item_link {{ request()->routeIs('leaderboards') ? 'active' : '' }}">Leaderboard</a>
                </li>
                <li class="navmenu__item">
                    <a href="/event" class="navmenu__item_link {{ request()->routeIs('event') ? 'active' : '' }}">Rain</a>
                </li>
                @auth
                <li class="navmenu__item">
                    <a href="/dashboard" class="navmenu__item_link {{ request()->routeIs('dashboard') ? 'active' : '' }}">Dashboard</a>
                </li>
                <li class="navmenu__item">
                    <a href="/games" class="navmenu__item_link {{ request()->routeIs('games') ? 'active' : '' }}">Games</a>
                </li>
                <li class="navmenu__item">
                    <a href="/bonus" class="navmenu__item_link {{ request()->routeIs('bonus') ? 'active' : '' }}">Bonuses</a>
                </li>
                <li class="navmenu__item">
                    <a href="/profile" class="navmenu__item_link {{ request()->routeIs('profile') ? 'active' : '' }}">Profile</a>
                </li>
                <li class="navmenu__item">
                    <a href="/referrals" class="navmenu__item_link {{ request()->routeIs('referrals') ? 'active' : '' }}">Affiliates</a>
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
            <a href="/dashboard" class="mobile_menu__link">
                <i class='bx bxs-dashboard' ></i>
                Dashboard
            </a>
            <a href="/games" class="mobile_menu__link">
                <i class='bx bxs-game' ></i>
                Games
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
            @if(Auth::check() && ((Auth::user()->role ?? 'user') === 'owner' || (Auth::user()->role ?? 'user') === 'admin'))
            <div class="leftside__game">
                <a href="/panel" title="Owner panel">
                    <i class='bx bx-shield-quarter'></i>
                    <span class="leftside__label">Panel</span>
                </a>
            </div>
            @endif
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
        @yield('content')
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

    <script async src="https://www.googletagmanager.com/gtag/js?id=G-2GBFZ1SSVQ"></script>
    <script>
        window.dataLayer = window.dataLayer || [];
        function gtag(){dataLayer.push(arguments);}
        gtag('js', new Date());

        gtag('config', 'G-2GBFZ1SSVQ');
    </script>
</body>
</html>