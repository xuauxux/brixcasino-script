@php
    // Every game in the lobby, so a player can jump straight into another one.
    $moreGames = [
        'dice' => ['Dice', 'Instant'],
        'mines' => ['Mines', 'Instant'],
        'wheel' => ['Wheel', 'Instant'],
        'jackpot' => ['Jackpot', 'Jackpot'],
        'crash' => ['Crash', 'Instant'],
        'towers' => ['Towers', 'Instant'],
        'flip' => ['Coin Flip', 'Instant'],
        'hilo' => ['HiLo', 'Cards'],
        'limbo' => ['Limbo', 'Instant'],
        'plinko' => ['Plinko', 'Instant'],
        'keno' => ['Keno', 'Instant'],
        'blackjack' => ['Blackjack', 'Table'],
    ];

    $current = $current ?? '';
@endphp

<section class="moregames">
    <div class="moregames__head">
        <h2 class="moregames__title">
            <svg class="moregames__spark" viewBox="0 0 24 24" aria-hidden="true"><path d="M12 2.5l1.9 5.2 5.2 1.9-5.2 1.9L12 16.7l-1.9-5.2L4.9 9.6l5.2-1.9L12 2.5zM18.7 15l.9 2.4 2.4.9-2.4.9-.9 2.4-.9-2.4-2.4-.9 2.4-.9.9-2.4z"/></svg>
            More games
        </h2>
        <div class="moregames__nav">
            <button type="button" class="moregames__arrow" data-dir="-1" aria-label="Previous games">
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M14.7 5.3L8 12l6.7 6.7 1.4-1.4L10.8 12l5.3-5.3-1.4-1.4z"/></svg>
            </button>
            <button type="button" class="moregames__arrow" data-dir="1" aria-label="More games">
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M9.3 5.3L8 6.7l5.3 5.3L8 17.3l1.3 1.4L16 12 9.3 5.3z"/></svg>
            </button>
        </div>
    </div>

    <div class="moregames__rail">
        @foreach($moreGames as $slug => $game)
            @continue($slug === $current)
            <a class="moregames__item" href="/{{ $slug }}" title="{{ $game[0] }}">
                <span class="moregames__art" style="background-image:url('/assets/images/games/{{ $slug }}.png')"></span>
                <span class="moregames__info">
                    <span class="moregames__name">{{ $game[0] }}</span>
                    <span class="moregames__meta">
                        <span class="moregames__cat">Originals · {{ $game[1] }}</span>
                        <span class="moregames__go">Play</span>
                    </span>
                </span>
            </a>
        @endforeach
    </div>
</section>

<script>
    (() => {
        const rail = document.querySelector('.moregames__rail');
        if (!rail) return;

        document.querySelectorAll('.moregames__arrow').forEach((button) => {
            button.addEventListener('click', () => {
                rail.scrollBy({ left: Number(button.dataset.dir) * 236, behavior: 'smooth' });
            });
        });
    })();
</script>
