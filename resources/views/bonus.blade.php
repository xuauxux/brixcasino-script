@extends('layout')

@section('content')
<div class="bonus__container">
    <div class="bonus__grid">
        <div class="bonus__daily">
            <div class="bonus__text">
                Get a <span class="bonus_green">bonus every 24 hours</span>
            </div>
            <div class="bonus__button">
                <button class="daily_btn">Claim</button>
            </div>
        </div>
        @php
            $promo_x = [0, 1, 1, 2, 3, 4, 4, 5, 5, 6, 7];
        @endphp
        <div class="bonus__promo">
            <div class="bonus__inner">
                <div class="bonus__promo_group">
                    <div class="input__wrapper">
                        <input type="text" class="promo__input" placeholder="Enter promo code" id="promocode">
                    </div>
                    <div class="bonus__promo_x" style="@if(Auth::user()->rank <= 3) background: linear-gradient(90deg,#535353 0%,#6e6e6e 100%); @elseif(Auth::user()->rank >= 4) background:linear-gradient(90deg,#007330 30%,#0077ff 100%); @elseif(Auth::user()->rank >= 7) background:linear-gradient(90deg,#0077ff 0%,#2ac800 100%); @endif">
                        <p>Your multiplier: x{{$promo_x[Auth::user()->rank]}}</p>
                    </div>
                </div>
                <div class="bonus__button">
                    <button class="promo__activate_btn">Activate</button>
                </div>
            </div>
        </div>
        <div class="bonus__discord">
            <div class="bonus__text" style="max-width: 300px;">
                Join our Discord community
            </div>
            <div class="bonus__button">
                <a href="https://discord.gg/qKMrjbMWVH" target="_blank" rel="noopener" class="discord_bonus">Join</a>
            </div>
        </div>
    </div>
    <div class="bonus__rules">
        <a href="https://discord.gg/qKMrjbMWVH" target="_blank" rel="noopener" class="rules__sub">
            <i class="bx bxl-discord-alt"></i>
        </a>
    </div>
</div>
@endsection