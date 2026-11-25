@extends('layout')

@section('content')
<div class="rules__container">
    <header class="rules__hero">
        <span class="rules__badge">DonutTrix · community guidelines</span>
        <h1 class="rules__title">Server rules</h1>
        <p class="rules__lead">
            These rules apply everywhere on DonutTrix — the website, the live chat, every game and our Discord.
            Breaking them can get you muted, timed out or banned, and any balance gained through abuse can be reset.
        </p>
    </header>

    <div class="rules__grid">
        <section class="rules__card">
            <div class="rules__card_head">
                <span class="rules__icon">🤝</span>
                <h2 class="rules__card_title">1. Respect &amp; Conduct</h2>
            </div>
            <ul class="rules__list">
                <li>Treat all members and staff with respect.</li>
                <li>No hate speech, racism, harassment, discrimination or excessive toxicity.</li>
                <li>Do not impersonate staff or other members.</li>
                <li>Keep all content appropriate and SFW.</li>
                <li>Publishing or sharing NSFW content, gore or terrorist material is strictly prohibited and will result in a ban.</li>
            </ul>
        </section>

        <section class="rules__card">
            <div class="rules__card_head">
                <span class="rules__icon">💬</span>
                <h2 class="rules__card_title">2. Chat &amp; Channels</h2>
            </div>
            <ul class="rules__list">
                <li>Keep conversations relevant to their channels and always use the correct channel.</li>
                <li>No spam, flooding, excessive caps or repeated messages.</li>
                <li>No unnecessary pings.</li>
                <li>English is the only language allowed, so our moderation team can understand you.</li>
            </ul>
        </section>

        <section class="rules__card">
            <div class="rules__card_head">
                <span class="rules__icon">📈</span>
                <h2 class="rules__card_title">3. Advertising &amp; Self-Promotion</h2>
            </div>
            <ul class="rules__list">
                <li>Advertising other servers, websites or competing casino sites is strictly prohibited.</li>
                <li>Do not promote any social media or services that are not official DonutTrix ones.</li>
            </ul>
        </section>

        <section class="rules__card">
            <div class="rules__card_head">
                <span class="rules__icon">❗</span>
                <h2 class="rules__card_title">4. Account &amp; Server Integrity</h2>
            </div>
            <ul class="rules__list">
                <li>Ban evasion and mute evasion are strictly prohibited.</li>
                <li>Using alternative accounts to bypass punishments is not allowed.</li>
                <li>Do not ping staff unless it is important.</li>
                <li>Spam pinging staff can result in a 1-day timeout.</li>
            </ul>
        </section>

        <section class="rules__card">
            <div class="rules__card_head">
                <span class="rules__icon">💰</span>
                <h2 class="rules__card_title">5. Trading &amp; Payments</h2>
            </div>
            <ul class="rules__list">
                <li>Any trades or transactions with other members are done at your own risk.</li>
                <li>We are not responsible for any scams, chargebacks or losses resulting from member-to-member trades.</li>
            </ul>
        </section>

        <section class="rules__card">
            <div class="rules__card_head">
                <span class="rules__icon">📩</span>
                <h2 class="rules__card_title">6. Support &amp; Tickets</h2>
            </div>
            <ul class="rules__list">
                <li>Follow all ticket guidelines.</li>
                <li>Be patient while waiting for support.</li>
                <li>Do not create false, unnecessary or troll tickets.</li>
            </ul>
        </section>
    </div>

    <div class="rules__note">
        <i class='bx bx-info-circle'></i>
        <p><strong>These rules can change.</strong> We may update them at any time — keep using DonutTrix and you accept the latest version.</p>
    </div>

    <div class="rules__cta">
        <div class="rules__cta_text">
            <h2>Need help or want to appeal a punishment?</h2>
            <p>Open a ticket in our Discord or reach the staff in the live chat.</p>
        </div>
        <div class="rules__cta_btns">
            <a class="rules__btn" href="https://discord.gg/qKMrjbMWVH" target="_blank" rel="noopener"><i class='bx bxl-discord-alt'></i> Join Discord</a>
            <a class="rules__btn rules__btn--ghost" href="/chat"><i class='bx bx-chat'></i> Live chat</a>
        </div>
    </div>
</div>
@endsection
