@extends('layout')

@section('content')
<div class="chat__container">
    <div class="chat__panel">
        <div class="chat__head">
            <div>
                <h1 class="chat__title">Live chat</h1>
                <p class="chat__sub">
                    Read by everyone · verified players can post
                    @auth
                    · <span class="chat__ign">{{ $ign }}</span>
                    @endauth
                </p>
            </div>
            <span class="chat__live"><span class="chat__dot"></span> live</span>
        </div>

        <div class="chat__list" id="chat_list">
            <div class="chat__empty" id="chat_empty">No messages yet — say hello.</div>
        </div>

        @if($can_write)
        <form class="chat__form" id="chat_form" autocomplete="off">
            <input type="text" class="chat__input" id="chat_input" maxlength="200" placeholder="Message the chat…">
            <button type="submit" class="chat__send" id="chat_send">Send</button>
        </form>

        <p class="chat__hint" id="chat_hint">Be kind. Messages are tied to your verified IGN.</p>
        @else
        <div class="chat__locked">
            <i class='bx bx-lock-alt'></i>
            <div>
                <strong>Read only</strong>
                <p>
                    @auth
                    Verify your Minecraft name to post here — open your wallet, get a code and whisper it to the bot in DonutSMP.
                    @else
                    Sign in with your Minecraft name to post here.
                    @endauth
                </p>
                @guest
                <button type="button" class="chat__signin auth__btn">Sign in</button>
                @endguest
            </div>
        </div>
        @endif
    </div>
</div>
<script src="/assets/js/chat.js"></script>
@endsection
