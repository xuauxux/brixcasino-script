<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use App\Models\User;
use App\Http\Controllers\PagesController;
use App\Http\Controllers\SlotsController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\MainController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\MinesController;
use App\Http\Controllers\WheelController;
use App\Http\Controllers\DiceController;
use App\Http\Controllers\JackpotController;
use App\Http\Controllers\CrashController;
use App\Http\Controllers\OriginalsController;
use App\Http\Controllers\BlackjackController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\Minecraft\AdminPanelController;
use App\Http\Controllers\Minecraft\BridgeController;
use App\Http\Controllers\Minecraft\IgnAuthController;
use App\Http\Controllers\Minecraft\WalletController;
use App\Http\Controllers\Platform\OwnerPanelController;
use App\Http\Controllers\Platform\PlayerController;
use App\Http\Middleware\Access;
use App\Http\Middleware\UserBanned;

Route::group(['prefix' => 'slots'], function () {
    Route::any('/getGames', [SlotsController::class, 'getGames']);
    Route::any('/getUrl', [SlotsController::class, 'getGameURI']);
    Route::any('/getUrl', [SlotsController::class, 'getGameURI']);
    Route::any('/callback231', [SlotsController::class, 'callback']);
    Route::get('/parse', [SlotsController::class, 'parseSlots']);
});

Route::group(['middleware' => 'guest'], function() {
    Route::get('/auth/vk', [AuthController::class, 'auth'])->name('vk.auth');
    Route::get('/auth/vk/callback', [AuthController::class, 'callback']);
    Route::post('/auth/register', [AuthController::class, 'register']);
    Route::post('/auth/login', [AuthController::class, 'login']);
    Route::get('/slots', [PagesController::class, 'slots'])->name('slots');
});

// IGN verification: guests use it to sign up / log in, a signed-in account uses
// it to link a Minecraft name to its existing wallet.
// Development-only shortcut so an admin can reach the dashboard without waiting
// for an in-game whisper. This route does not exist outside APP_ENV=local.
if (app()->environment('local')) {
    Route::get('/dev/login/{ign}', function (string $ign) {
        $user = User::where('minecraft_ign', strtolower($ign))->where('admin', 1)->first();

        if ($user === null) {
            abort(404, 'No verified admin with that IGN.');
        }

        Auth::login($user, true);
        request()->session()->regenerate();

        return redirect('/admin/minecraft');
    })->middleware('throttle:10,1')->name('dev.login');
}

Route::post('/auth/ign', [IgnAuthController::class, 'start'])->name('ign.start')->middleware('throttle:20,1');
Route::get('/auth/ign/status', [IgnAuthController::class, 'status'])->name('ign.status')->middleware('throttle:120,1');
Route::post('/auth/ign/cancel', [IgnAuthController::class, 'cancel'])->name('ign.cancel');

// Live chat is public to read; only verified players may post (checked in the
// controller and by the /chat/send route being inside the auth group).
Route::get('/chat', [ChatController::class, 'index'])->name('chat');
Route::get('/chat/messages', [ChatController::class, 'messages'])->middleware('throttle:120,1');

Route::group(['middleware' => ['banned', 'maintenance']], function() {
    Route::get('/', [PagesController::class,'index'])->name('index');
    Route::get('/dice', [PagesController::class, 'dice'])->name('dice');
    Route::get('/mines', [PagesController::class, 'mines'])->name('mines');
    Route::get('/wheel', [PagesController::class, 'wheel'])->name('wheel');
    Route::get('/jackpot', [JackpotController::class, 'index'])->name('jackpot');
    Route::get('/crash', [CrashController::class, 'index'])->name('crash');
    Route::get('/towers', [PagesController::class, 'towers'])->name('towers');
    Route::get('/flip', [PagesController::class, 'flip'])->name('flip');
    Route::get('/hilo', [PagesController::class, 'hilo'])->name('hilo');
    Route::get('/limbo', [PagesController::class, 'limbo'])->name('limbo');
    Route::get('/plinko', [PagesController::class, 'plinko'])->name('plinko');
    Route::get('/keno', [PagesController::class, 'keno'])->name('keno');
    Route::get('/blackjack', [PagesController::class, 'blackjack'])->name('blackjack');
    Route::get('/parther/{id}', [MainController::class, 'referral']);
    Route::get('/rules', [PagesController::class,'rules'])->name('rules');
    Route::get('/privacy', [PagesController::class,'privacy'])->name('privacy');
    Route::get('/slots', [PagesController::class, 'slots'])->name('slots');

    // Leaderboard and event pages are readable by everyone; only collecting
    // letters and entering the competitions needs an account.
    Route::get('/leaderboards', [PlayerController::class, 'leaderboards'])->name('leaderboards');
    Route::get('/event', [PlayerController::class, 'event'])->name('event');
    Route::get('/rain/status', [PlayerController::class, 'rainStatus'])->middleware('throttle:120,1');

    Route::group(['middleware' => 'auth', 'middleware' => 'banned'], function() {
        Route::get('/user/balance', [MainController::class, 'balance']);
        Route::post('/user/daily', [MainController::class, 'daily']);
        Route::post('/user/promo', [MainController::class, 'promo']);
        Route::post('/user/withdraw', [MainController::class, 'withdraw']);
        Route::post('/user/card', [MainController::class, 'userCard']);
        Route::post('/user/wheel/status', [WheelController::class, 'infoWheel']);

        Route::get('/bonus', [PagesController::class,'bonus'])->name('bonus');
        Route::get('/referrals', [PagesController::class, 'referrals'])->name('referrals');
        Route::get('/profile', [MainController::class, 'profile'])->name('profile');

        Route::get('/wallet/pay', [WalletController::class, 'depositPage'])->name('pay');
        Route::get('/wallet/withdraw', [WalletController::class, 'withdrawPage'])->name('withdraw');
        Route::get('/wallet/history', [PagesController::class, 'history'])->name('history');
        Route::post('/chat/send', [ChatController::class, 'send'])->middleware('throttle:30,1');
        Route::post('/wallet/minecraft/deposit', [WalletController::class, 'depositCreate']);
        Route::get('/wallet/minecraft/deposit/status', [WalletController::class, 'depositStatus']);
        Route::post('/wallet/minecraft/withdraw', [WalletController::class, 'withdrawCreate']);
        Route::post('/wallet/minecraft/withdraw/cancel', [WalletController::class, 'withdrawCancel']);
        Route::get('/wallet/minecraft/withdraw/status', [WalletController::class, 'withdrawStatus']);
        Route::get('/wallet/cancel/withdraw/{id}', [MainController::class, 'user_cancel']);
        Route::post('/load/table', [MainController::class, 'loadTable']);

        Route::group(['prefix' => '/dice'], function () {
            Route::post('/bet', [DiceController::class, 'dice']);
        });
        
        Route::group(['prefix' => '/mines'], function () {
            Route::post('/open', [MinesController::class, 'open']);
            Route::post('/create', [MinesController::class, 'create']);
            Route::post('/get', [MinesController::class, 'get']);
            Route::post('/take', [MinesController::class, 'take']);
        });

        Route::group(['prefix' => '/blackjack'], function () {
            Route::post('/start', [BlackjackController::class, 'start']);
            Route::post('/hit', [BlackjackController::class, 'hit']);
            Route::post('/stand', [BlackjackController::class, 'stand']);
            Route::post('/double', [BlackjackController::class, 'double']);
            Route::get('/state', [BlackjackController::class, 'state']);
        });

        Route::group(['prefix' => '/originals'], function () {
            Route::post('/flip', [OriginalsController::class, 'flip']);
            Route::post('/limbo', [OriginalsController::class, 'limbo']);
            Route::post('/plinko', [OriginalsController::class, 'plinko']);
            Route::post('/keno', [OriginalsController::class, 'keno']);
            Route::post('/towers/start', [OriginalsController::class, 'towersStart']);
            Route::post('/towers/open', [OriginalsController::class, 'towersOpen']);
            Route::post('/towers/take', [OriginalsController::class, 'towersTake']);
            Route::post('/towers/state', [OriginalsController::class, 'towersState']);
            Route::post('/hilo/start', [OriginalsController::class, 'hiloStart']);
            Route::post('/hilo/guess', [OriginalsController::class, 'hiloGuess']);
            Route::post('/hilo/take', [OriginalsController::class, 'hiloTake']);
        });

        Route::group(['prefix' => 'crash'], function() {
            Route::post('/addBet', [CrashController::class, 'createBet']);
            Route::post('/last', [CrashController::class, 'lastBet']);
            Route::post('/cashout', [CrashController::class, 'Cashout']);
            Route::post('/get', [CrashController::class, 'crashGet']);
        });

        Route::post('/wallet/pay/{system}', [MainController::class, 'pay']);

        Route::get('/logout', [AuthController::class, 'logout']);
    });

    Route::post('/load/dice', [AdminController::class, 'loadDice']); 
});

// DonutSMP bridge (minecraft-bridge/bridge.js): HMAC signed, never a browser
// session, so CSRF does not apply.
Route::post('api/mc/heartbeat', [BridgeController::class, 'heartbeat'])
    ->name('bridge.heartbeat')
    ->middleware('throttle:120,1')
    ->withoutMiddleware([\App\Http\Middleware\VerifyCsrfToken::class]);
Route::post('api/mc/chat', [BridgeController::class, 'chat'])
    ->name('bridge.chat')
    ->middleware('throttle:120,1')
    ->withoutMiddleware([\App\Http\Middleware\VerifyCsrfToken::class]);
Route::post('api/bot/account', [BridgeController::class, 'account'])
    ->name('bot.account')
    ->middleware('throttle:30,1')
    ->withoutMiddleware([\App\Http\Middleware\VerifyCsrfToken::class]);
Route::post('api/bot/link', [BridgeController::class, 'link'])
    ->name('bot.link')
    ->middleware('throttle:120,1')
    ->withoutMiddleware([\App\Http\Middleware\VerifyCsrfToken::class]);
Route::post('api/bot/deposit', [BridgeController::class, 'deposit'])
    ->name('bot.deposit')
    ->middleware('throttle:120,1')
    ->withoutMiddleware([\App\Http\Middleware\VerifyCsrfToken::class]);
Route::post('api/bot/withdrawals/claim', [BridgeController::class, 'claimWithdrawal'])
    ->name('bot.withdrawals.claim')
    ->middleware('throttle:120,1')
    ->withoutMiddleware([\App\Http\Middleware\VerifyCsrfToken::class]);
Route::post('api/bot/withdrawals/result', [BridgeController::class, 'withdrawalResult'])
    ->name('bot.withdrawals.result')
    ->middleware('throttle:120,1')
    ->withoutMiddleware([\App\Http\Middleware\VerifyCsrfToken::class]);

Route::group(['prefix' => '/payment'], function () {
    Route::post('/fk/handle', [MainController::class, 'checkPaymentFk']);
    Route::post('/rp/handle', [MainController::class, 'checkPaymentRp']);
    Route::post('/cc/handle', [MainController::class, 'checkPaymentCc']);
    Route::get('/fail', [MainController::class, 'fail']);
    Route::get('/success', [MainController::class, 'success']);
});
Route::group(['prefix' => '/withdraw'], function () {
    Route::post('/rp/handle', [MainController::class, 'successWithdrawRp']);
});

Route::group(['prefix' => '/api'], function() {
    Route::group(['prefix' => 'wheel'], function() {
        Route::post('/start', [WheelController::class, 'start']);
        Route::post('/open', [WheelController::class, 'open']);
        Route::post('/close', [WheelController::class, 'close']);
        Route::post('/end', [WheelController::class, 'end']);
        Route::post('/get', [WheelController::class, 'getWheel']);
        Route::post('/bet/{color}', [WheelController::class, 'wheel']);
        Route::post('/admin/bet/{color}', [WheelController::class, 'adminwheel']);
    });
    Route::group(['prefix' => 'jackpot'], function() {
        Route::post('/newGame', [JackpotController::class, 'newGame']);
        Route::post('/getSlider', [JackpotController::class, 'getSlider']);
        Route::post('/getStatus', [JackpotController::class, 'getStatus']);
        Route::post('/setStatus', [JackpotController::class, 'setStatus']);
        Route::post('/newBet', [JackpotController::class, 'newBet']);
    });
    Route::group(['prefix' => 'crash'], function() {
        Route::post('/init', [CrashController::class, 'init']);
		Route::post('/slider', [CrashController::class, 'startSlider']);
		Route::post('/newGame', [CrashController::class, 'newGame']);
    });
});


/* ---------------------------------------------------------- platform pages */
Route::group(['middleware' => ['auth', 'banned', 'maintenance']], function () {
    Route::get('/dashboard', [PlayerController::class, 'dashboard'])->name('dashboard');
    Route::get('/games', [PlayerController::class, 'games'])->name('games');
    Route::post('/event/free-letter', [PlayerController::class, 'freeLetter'])->name('event.letter')->middleware('throttle:12,1');
    Route::post('/rain/join', [PlayerController::class, 'rainJoin'])->name('rain.join')->middleware('throttle:20,1');
});

/* ------------------------------------------------------------ owner panel */
Route::group(['prefix' => 'panel', 'middleware' => ['auth', 'role:admin']], function () {
    Route::get('/', [OwnerPanelController::class, 'overview'])->name('panel');
    Route::get('/status', [OwnerPanelController::class, 'status'])->middleware('throttle:120,1');
    Route::get('/users', [OwnerPanelController::class, 'users'])->name('panel.users');
    Route::get('/users/{id}', [OwnerPanelController::class, 'user'])->whereNumber('id')->name('panel.user');
    Route::post('/users/{id}/balance', [OwnerPanelController::class, 'balance'])->whereNumber('id');
    Route::post('/users/{id}/ban', [OwnerPanelController::class, 'ban'])->whereNumber('id');
    Route::post('/users/{id}/unban', [OwnerPanelController::class, 'unban'])->whereNumber('id');
    Route::get('/transactions', [OwnerPanelController::class, 'transactions'])->name('panel.transactions');
    Route::get('/games', [OwnerPanelController::class, 'games'])->name('panel.games');
    Route::post('/games/{game}/toggle', [OwnerPanelController::class, 'toggleGame'])->name('panel.games.toggle');

    // Bot and rain are regular staff tools.
    Route::get('/bot', [OwnerPanelController::class, 'botBalance'])->name('panel.bot');
    Route::post('/bot/toggle', [OwnerPanelController::class, 'botToggle'])->name('panel.bot.toggle');
    Route::get('/rain', [OwnerPanelController::class, 'rain'])->name('panel.rain');
    Route::post('/rain/settings', [OwnerPanelController::class, 'rainSettings'])->name('panel.rain.settings');
    Route::post('/rain/start', [OwnerPanelController::class, 'rainStart'])->name('panel.rain.start');
    Route::post('/rain/stop', [OwnerPanelController::class, 'rainStop'])->name('panel.rain.stop');

    // Owner only: roles, maintenance, site settings, the event window and payouts.
    Route::group(['middleware' => 'role:owner'], function () {
        Route::post('/users/{id}/role', [OwnerPanelController::class, 'setRole'])->whereNumber('id');
        Route::get('/maintenance', [OwnerPanelController::class, 'maintenance'])->name('panel.maintenance');
        Route::post('/maintenance', [OwnerPanelController::class, 'maintenanceSave'])->name('panel.maintenance.save');
        Route::get('/settings', [OwnerPanelController::class, 'settings'])->name('panel.settings');
        Route::post('/settings', [OwnerPanelController::class, 'settingsSave'])->name('panel.settings.save');
        Route::match(['get', 'post'], '/event', [OwnerPanelController::class, 'event'])->name('panel.event');
        });
});

Route::group(['prefix' => '/admin', 'middleware' => 'auth', 'middleware' => 'access:admin'], function () {
    Route::get('/', [AdminController::class,'index'])->name('admin.index');
    Route::get('/users', [AdminController::class,'users'])->name('admin.users');
    Route::get('/users/search', [AdminController::class,'searchUser']);
    Route::post('/users/sort', [AdminController::class,'sortUser']);
    Route::post('/user/ref/sort', [AdminController::class,'sortRefPay']);
    Route::get('/user/edit/{id}', [AdminController::class, 'manageUser'])->name('admin.user');
    Route::get('/user/ban/{id}', [AdminController::class, 'banUser']);
    Route::get('/user/unban/{id}', [AdminController::class, 'unbanUser']);
    Route::post('/user/{id}/multiaccounts', [AdminController::class, 'userMultiaccounts']);
    Route::post('/user/save', [AdminController::class, 'userSave']);

    Route::get('/withdraws', [AdminController::class,'withdraws'])->name('admin.withdraws');

    Route::get('/minecraft', [AdminPanelController::class, 'index'])->name('admin.minecraft');
    Route::get('/minecraft/live', [AdminPanelController::class, 'live'])->name('admin.minecraft.live');
    Route::post('/minecraft/accounts', [AdminPanelController::class, 'storeAccount'])->name('admin.minecraft.accounts.store');
    Route::post('/minecraft/accounts/{id}/toggle', [AdminPanelController::class, 'toggleAccount'])->name('admin.minecraft.accounts.toggle');
    Route::post('/minecraft/accounts/{id}/delete', [AdminPanelController::class, 'deleteAccount'])->name('admin.minecraft.accounts.delete');
    Route::get('/balance/rp', [AdminController::class,'rp_balance']);
    Route::get('/cancel/withdraw/{id}', [AdminController::class,'cancel_withdraw']);
    Route::get('/withdraw/fk/{id}', [AdminController::class, 'fk_withdraw']);
    Route::get('/withdraw/rp/{id}', [AdminController::class, 'rp_withdraw']);

    Route::get('/promocodes', [AdminController::class,'promocodes'])->name('admin.promocodes');
    Route::post('promocodes/new', [AdminController::class, 'promoNew']);
    Route::get('/promocodes/delete/{id}', [AdminController::class, 'promoDelete']);

    Route::get('/settings', [AdminController::class,'settings'])->name('admin.settings');
    Route::post('/setting/save', [AdminController::class, 'settingsSave']);
    Route::post('/enabled/{game}', [AdminController::class,'gameEnabled']);

    Route::post('/restartWheel', [AdminController::class, 'restartWheel']);
    Route::post('/stopWheel', [AdminController::class, 'stopWheel']);
});
