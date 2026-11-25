<?php

namespace App\Services\Minecraft;

use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * DonutSMP economy: IGN verification, in-game deposits and coin payouts.
 *
 * Ported from the reference arcade. Two rules matter:
 *  - an IGN only counts as yours once the bridge saw your whispered code, so a
 *    typed name alone can never be paid;
 *  - a deposit only credits when a signed receipt matches the player and the
 *    exact amount of a pending request.
 */
class Economy
{
    /** No 0/O, 1/I/L: the code is typed into Minecraft chat by hand. */
    private const ALPHABET = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';

    /* ---------------------------------------------------------------- helpers */

    /** The account the player pays: configured, admin-added, or learned from the bot login. */
    public function botIgn(): ?string
    {
        $configured = trim((string) config('minecraft.bot_ign'));

        if ($configured !== '') {
            return $configured;
        }

        $pool = $this->botIgns('deposit');

        if ($pool !== []) {
            return $pool[0];
        }

        $learned = Cache::get('mc:deposit:bot');

        return is_string($learned) && $learned !== '' ? $learned : null;
    }

    /* ---------------------------------------------------------- bot accounts */

    /**
     * The Minecraft accounts admins added for the economy.
     *
     * @return array<int, array{id: int, ign: string, purpose: string, enabled: bool, note: string|null}>
     */
    public function botAccounts(?string $purpose = null): array
    {
        if (! Schema::hasTable('mc_bot_accounts')) {
            return [];
        }

        $out = [];

        foreach (DB::table('mc_bot_accounts')->orderBy('id')->get() as $row) {
            if ($purpose !== null && $row->purpose !== 'all' && $row->purpose !== $purpose) {
                continue;
            }

            $out[] = [
                'id' => (int) $row->id,
                'ign' => (string) $row->ign,
                'purpose' => (string) $row->purpose,
                'auth' => (string) ($row->auth ?? 'microsoft'),
                'login' => $row->login !== null && $row->login !== '' ? (string) $row->login : null,
                'has_password' => ! empty($row->password_enc),
                'enabled' => (bool) $row->enabled,
                'note' => $row->note,
            ];
        }

        return $out;
    }

    /** @return array<int, string> enabled names for a purpose */
    public function botIgns(?string $purpose = null): array
    {
        $names = [];

        foreach ($this->botAccounts($purpose) as $account) {
            if ($account['enabled']) {
                $names[] = $account['ign'];
            }
        }

        return array_values(array_unique($names));
    }

    /**
     * Adds (or updates) an account the bridge can run. A Minecraft name alone
     * cannot join a premium server, so Microsoft accounts also need the email
     * used to log in plus their password, which is stored encrypted.
     *
     * @return array{ok: bool, error?: string, created?: bool, updated?: bool}
     */
    public function addBotAccount(
        string $ign,
        string $purpose = 'all',
        ?string $note = null,
        string $auth = 'microsoft',
        ?string $login = null,
        ?string $password = null,
    ): array {
        $ign = strtolower(trim($ign));

        if (! preg_match('/^\.?[a-z0-9_]{3,16}$/', $ign)) {
            return ['ok' => false, 'error' => 'Minecraft names are 3-16 letters, numbers, _ or a leading dot.' ];
        }

        $purpose = in_array($purpose, ['all', 'verify', 'deposit', 'withdraw'], true) ? $purpose : 'all';
        $note = $note !== null ? mb_substr(trim($note), 0, 120) : null;
        $auth = in_array($auth, ['microsoft', 'offline'], true) ? $auth : 'microsoft';

        $login = $login !== null ? trim($login) : '';
        $login = $login !== '' ? mb_substr($login, 0, 190) : null;
        $password = $password !== null ? trim($password) : '';
        $password = $password !== '' ? $password : null;

        $existing = DB::table('mc_bot_accounts')->where('ign', $ign)->first();
        $storedPassword = $existing->password_enc ?? null;

        if ($auth === 'microsoft') {
            if ($login === null) {
                return ['ok' => false, 'error' => 'Microsoft accounts need the email used to log in.'];
            }

            if (! filter_var($login, FILTER_VALIDATE_EMAIL)) {
                return ['ok' => false, 'error' => 'That does not look like an email address.'];
            }

            if ($password === null && empty($storedPassword)) {
                return ['ok' => false, 'error' => 'Microsoft accounts need their password so the bot can log in.'];
            }
        }

        $fields = [
            'purpose' => $purpose,
            'auth' => $auth,
            'login' => $auth === 'microsoft' ? $login : null,
            'enabled' => true,
            'note' => $note,
            'updated_at' => now(),
        ];

        // Leaving the field empty on an existing account keeps the stored
        // password, so the purpose or note can change without retyping it.
        if ($password !== null) {
            $fields['password_enc'] = Crypt::encryptString($password);
        }

        if ($existing !== null) {
            DB::table('mc_bot_accounts')->where('ign', $ign)->update($fields);

            return ['ok' => true, 'updated' => true];
        }

        DB::table('mc_bot_accounts')->insert($fields + [
            'ign' => $ign,
            'created_at' => now(),
        ]);

        return ['ok' => true, 'created' => true];
    }

    /**
     * Login details the bridge needs for an account it was started with. Only
     * reachable through a signed bridge request; the password is decrypted just
     * for that reply and never reaches a browser.
     *
     * @return array{ign: string, auth: string, login: string|null, password: string|null}|null
     */
    public function botCredentials(string $ign): ?array
    {
        if (! Schema::hasTable('mc_bot_accounts')) {
            return null;
        }

        $row = DB::table('mc_bot_accounts')->where('ign', strtolower(trim($ign)))->first();

        if ($row === null) {
            return null;
        }

        $password = null;

        if (! empty($row->password_enc)) {
            try {
                $password = Crypt::decryptString($row->password_enc);
            } catch (\Throwable) {
                $password = null;
            }
        }

        return [
            'ign' => (string) $row->ign,
            'auth' => (string) ($row->auth ?? 'microsoft'),
            'login' => $row->login !== null && $row->login !== '' ? (string) $row->login : null,
            'password' => $password,
        ];
    }

    public function toggleBotAccount(int $id): bool
    {
        $row = DB::table('mc_bot_accounts')->where('id', $id)->first();

        if ($row === null) {
            return false;
        }

        DB::table('mc_bot_accounts')->where('id', $id)->update([
            'enabled' => ! $row->enabled,
            'updated_at' => now(),
        ]);

        return true;
    }

    public function deleteBotAccount(int $id): bool
    {
        return DB::table('mc_bot_accounts')->where('id', $id)->delete() > 0;
    }

    public function rememberBot(string $ign): void
    {
        if (! preg_match('/^[A-Za-z0-9_]{3,16}$/', $ign)) {
            return;
        }

        // A bridge that comes online registers its own account, so a freshly
        // started bot shows up in the admin list without any setup.
        if (Schema::hasTable('mc_bot_accounts')
            && ! DB::table('mc_bot_accounts')->where('ign', strtolower($ign))->exists()) {
            DB::table('mc_bot_accounts')->insert([
                'ign' => strtolower($ign),
                'purpose' => 'all',
                'enabled' => true,
                'note' => 'Auto-added by the bridge',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        if (trim((string) config('minecraft.bot_ign')) !== '') {
            return;
        }

        Cache::put('mc:deposit:bot', $ign, now()->addDays(30));
    }

    public function verified(?User $user): bool
    {
        return $user !== null
            && ! empty($user->minecraft_ign)
            && ! empty($user->minecraft_verified_at);
    }

    public function bridgeOnline(): bool
    {
        $beat = Cache::get('mc:bridge');

        return is_array($beat)
            && ($beat['state'] ?? null) === 'online'
            && ! empty($beat['at'])
            && now()->diffInSeconds($beat['at'], true) < 90;
    }

    /** In-game dollars → site coins. */
    public function coinsFor(int $gameAmount): float
    {
        $rate = (int) config('minecraft.coins_per_million');

        return $rate > 0 ? round($gameAmount * $rate / 1_000_000, 2) : 0.0;
    }

    /** Site coins → in-game dollars. */
    public function gameForCoins(float $coins): int
    {
        $rate = (int) config('minecraft.coins_per_million');

        return $rate > 0 ? (int) floor($coins * 1_000_000 / $rate) : 0;
    }

    public function taxFor(int $gameAmount, ?int $known = null): int
    {
        if ($known !== null && $known > 0) {
            return $known;
        }

        $bps = max(0, (int) config('minecraft.withdraw.tax_bps'));

        return $bps > 0 ? intdiv($gameAmount * $bps + 9_999, 10_000) : 0;
    }

    /** Records a bot event id once; true the first time only. */
    private function once(string $eventId, string $kind): bool
    {
        if ($eventId === '' || strlen($eventId) > 64) {
            return false;
        }

        return DB::table('mc_events')->insertOrIgnore([
            'event_id' => $eventId,
            'kind' => $kind,
            'created_at' => now(),
            'updated_at' => now(),
        ]) === 1;
    }

    /* ------------------------------------------------------------ IGN linking */

    /**
     * Issues the 8-character code the player has to whisper to the bot.
     *
     * @return array{ok: bool, error?: string, id?: int, code?: string, ign?: string, bot?: string|null, expires_in?: int}
     */
    public function startLink(string $ign, ?int $userId = null): array
    {
        $ign = strtolower(trim($ign));

        if (! preg_match('/^\.?[A-Za-z0-9_]{3,16}$/', $ign)) {
            return ['ok' => false, 'error' => 'That is not a valid Minecraft username (3-16 letters, numbers, _ or a leading dot).'];
        }

        $owner = User::where('minecraft_ign', $ign)->whereNotNull('minecraft_verified_at')->first();

        if ($owner !== null && $userId !== null && (int) $owner->id !== $userId) {
            return ['ok' => false, 'error' => 'That Minecraft name is already linked to another account.'];
        }

        // Guests prove an IGN and get (or return to) its account; a logged-in
        // account links the IGN to itself instead of creating a second row.
        $linkUserId = $userId;

        if ($linkUserId === null && $owner !== null) {
            $linkUserId = (int) $owner->id;
        }

        // Only a signed-in account is refused here. A returning player whose IGN
        // is already verified must still be able to request a login code.
        if ($userId !== null) {
            $account = User::where('id', $userId)->first();

            if ($account !== null && $account->minecraft_verified_at !== null) {
                return ['ok' => false, 'error' => 'This account is already verified.'];
            }
        }

        $code = '';

        for ($i = 0; $i < 8; $i++) {
            $code .= self::ALPHABET[random_int(0, strlen(self::ALPHABET) - 1)];
        }

        DB::table('mc_link_codes')->where('ign', $ign)->whereNull('consumed_at')->delete();

        $id = DB::table('mc_link_codes')->insertGetId([
            'ign' => $ign,
            'code_hash' => hash('sha256', $code),
            'user_id' => $linkUserId,
            'expires_at' => now()->addMinutes((int) config('minecraft.link_ttl_minutes', 10)),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // No code is shown any more: a whisper to any listed account from this
        // IGN is enough, so the page only needs the account names.
        return [
            'ok' => true,
            'id' => (int) $id,
            'ign' => $ign,
            'accounts' => $this->botIgns('verify'),
            'bot' => $this->botIgn(),
            'expires_in' => (int) config('minecraft.link_ttl_minutes', 10) * 60,
        ];
    }

    /** @return array{state: string, user_id?: int, ign?: string} */
    public function linkStatus(int $id): array
    {
        $row = DB::table('mc_link_codes')->where('id', $id)->first();

        if ($row === null) {
            return ['state' => 'missing'];
        }

        if ($row->consumed_at !== null) {
            return ['state' => 'consumed'];
        }

        if ($row->verified_at !== null && $row->user_id !== null) {
            return ['state' => 'verified', 'user_id' => (int) $row->user_id, 'ign' => (string) $row->ign];
        }

        if (now()->greaterThan($row->expires_at)) {
            return ['state' => 'expired'];
        }

        return ['state' => 'pending'];
    }

    public function consumeLink(int $id): void
    {
        DB::table('mc_link_codes')->where('id', $id)->update([
            'consumed_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * The bot saw "<player> whispers: CODE". The code is bound to the IGN that
     * asked for it, so linking someone else's name is impossible.
     *
     * @return array{status: string, ign?: string, user_id?: int}
     */
    public function completeLink(string $eventId, string $player, ?string $code = null): array
    {
        if (! $this->once($eventId, 'link')) {
            return ['status' => 'duplicate'];
        }

        $player = strtolower(trim($player));

        if (! preg_match('/^\.?[a-z0-9_]{3,16}$/', $player)) {
            return ['status' => 'invalid_player'];
        }

        $code = strtoupper(trim((string) $code));

        // Without a code the whisper itself is the proof: match the pending
        // request opened for that IGN by the browser session.
        $row = DB::table('mc_link_codes')
            ->whereNull('consumed_at')
            ->when($code !== '', fn ($q) => $q->where('code_hash', hash('sha256', $code)))
            ->when($code === '', fn ($q) => $q->where('ign', $player))
            ->orderByDesc('id')
            ->first();

        if ($row === null) {
            return ['status' => 'invalid_code'];
        }

        if (now()->greaterThan($row->expires_at)) {
            return ['status' => 'expired_code'];
        }

        if ((string) $row->ign !== $player) {
            return ['status' => 'ign_mismatch'];
        }

        if ($row->verified_at !== null && $row->user_id !== null) {
            return ['status' => 'linked', 'ign' => $player, 'user_id' => (int) $row->user_id];
        }

        $taken = User::where('minecraft_ign', $player)
            ->when($row->user_id !== null, fn ($q) => $q->where('id', '!=', (int) $row->user_id))
            ->whereNotNull('minecraft_verified_at')
            ->exists();

        if ($taken) {
            return ['status' => 'username_taken'];
        }

        $user = $row->user_id !== null ? User::where('id', (int) $row->user_id)->first() : null;

        if ($user === null) {
            // A legacy username/password row with the same name keeps working,
            // it just gains a verified IGN instead of a second account.
            $user = User::where('username', $player)->first();
        }

        if ($user === null) {
            $user = User::create([
                'username' => $player,
                'password' => Hash::make(Str::random(40)),
                'avatar' => '/assets/images/profile.jpg',
                'ip' => request()->ip(),
                'minecraft_ign' => $player,
                'minecraft_verified_at' => now(),
                'unique_id' => Str::random(8),
                'ref_code' => bin2hex(random_bytes(5)),
                'referred_by' => null,
                'date' => date('d.m.Y'),
            ]);

            // Resolve the referral cookie against a real account id (the cookie
            // holds the referrer's public code, not their id).
            (new \App\Services\Platform\Referrals())->attach($user, (string) request()->cookie('ref'));
        } else {
            $user->update([
                'minecraft_ign' => $player,
                'minecraft_verified_at' => now(),
                'ip' => request()->ip(),
            ]);
        }

        // Verifying with a staff IGN is the only way in: the whisper proves the
        // player controls that Minecraft account, so it can now open the panel.
        $this->promoteTrustedAdmin($user->fresh() ?? $user);

        DB::table('mc_link_codes')->where('id', $row->id)->update([
            'user_id' => $user->id,
            'verified_at' => now(),
            'updated_at' => now(),
        ]);

        return ['status' => 'linked', 'ign' => $player, 'user_id' => (int) $user->id];
    }

    /**
     * Names on the instant-verify list skip the whisper: the account is marked
     * verified and signed in on submit. Only call this for a name the caller
     * has already matched against config('donut.instant_verify').
     */
    public function verifyInstantly(string $ign, ?int $userId = null): array
    {
        $player = strtolower(trim($ign));

        if ($player === '') {
            return ['ok' => false, 'error' => 'Enter your Minecraft name.'];
        }

        $taken = User::where('minecraft_ign', $player)
            ->when($userId !== null, fn ($q) => $q->where('id', '!=', $userId))
            ->whereNotNull('minecraft_verified_at')
            ->exists();

        if ($taken) {
            return ['ok' => false, 'error' => 'That name is already verified on another account.'];
        }

        $user = $userId !== null ? User::where('id', $userId)->first() : null;
        $user = $user ?? User::where('username', $player)->first();

        if ($user === null) {
            $user = User::create([
                'username' => $player,
                'password' => Hash::make(Str::random(40)),
                'avatar' => '/assets/images/profile.jpg',
                'ip' => request()->ip(),
                'minecraft_ign' => $player,
                'minecraft_verified_at' => now(),
                'unique_id' => Str::random(8),
                'ref_code' => bin2hex(random_bytes(5)),
                'referred_by' => null,
                'date' => date('d.m.Y'),
            ]);

            (new \App\Services\Platform\Referrals())->attach($user, (string) request()->cookie('ref'));
        } else {
            $user->update([
                'minecraft_ign' => $player,
                'minecraft_verified_at' => now(),
                'ip' => request()->ip(),
            ]);
        }

        $this->promoteTrustedAdmin($user->fresh() ?? $user);

        return ['ok' => true, 'user_id' => (int) $user->id];
    }

    /* --------------------------------------------------------------- deposits */

    /**
     * Turns the account into an owner when its verified IGN is one of the
     * trusted staff accounts. Never demotes and never runs before verification.
     */
    private function promoteTrustedAdmin(User $user): bool
    {
        try {
            $ign = strtolower((string) $user->minecraft_ign);

            if ($ign === '' || empty($user->minecraft_verified_at)) {
                return false;
            }

            if (! \App\Services\Platform\Settings::isAdminIgn($ign)) {
                return false;
            }

            if ((int) $user->admin === 1 && ($user->role ?? 'user') === 'owner') {
                return false;
            }

            $user->forceFill(['admin' => 1, 'role' => 'owner'])->save();

            \App\Services\Platform\Audit::log(
                (int) $user->id,
                'trusted_verify',
                (int) $user->id,
                'Owner access granted by verified staff IGN '.$ign,
                ['ign' => $ign],
            );

            return true;
        } catch (\Throwable $e) {
            logger()->warning('admin promotion failed: '.$e->getMessage());

            return false;
        }
    }

    /** Promotes accounts verified before a name was added to the trusted list. */
    public function syncTrustedAdmins(): int
    {
        $igns = \App\Services\Platform\Settings::adminIgns();

        if ($igns === []) {
            return 0;
        }

        $promoted = 0;

        foreach (User::whereIn('minecraft_ign', $igns)->whereNotNull('minecraft_verified_at')->get() as $user) {
            if ($this->promoteTrustedAdmin($user)) {
                $promoted++;
            }
        }

        return $promoted;
    }

    public function expireStale(): void
    {
        DB::table('mc_deposit_requests')
            ->where('status', 'pending')
            ->where('expires_at', '<=', now())
            ->update(['status' => 'expired', 'updated_at' => now()]);

        $stale = DB::table('mc_withdrawals')
            ->where('status', 'pending')
            ->where('expires_at', '<=', now())
            ->get(['id', 'user_id', 'coins']);

        foreach ($stale as $row) {
            $this->refundWithdrawal((int) $row->id, (float) $row->coins, 'expired');
        }

        DB::table('mc_withdrawals')
            ->where('status', 'sending')
            ->where('claimed_at', '<=', now()->subMinutes(10))
            ->update(['status' => 'unconfirmed', 'updated_at' => now()]);
    }

    /** @return array{ok: bool, error?: string, request?: array<string, mixed>, min?: int, max?: int, max_pending?: int} */
    public function createDepositRequest(User $user, int $gameAmount): array
    {
        if (! config('minecraft.deposit.enabled')) {
            return ['ok' => false, 'error' => 'Deposits are temporarily unavailable.'];
        }

        if (! $this->verified($user)) {
            return ['ok' => false, 'error' => 'Verify your Minecraft name first.'];
        }

        $min = (int) config('minecraft.deposit.min_game');
        $max = (int) config('minecraft.deposit.max_game');

        if ($gameAmount < $min || $gameAmount > $max) {
            return ['ok' => false, 'error' => 'Enter an amount between '.number_format($min).' and '.number_format($max).' in-game.', 'min' => $min, 'max' => $max];
        }

        $this->expireStale();

        $open = DB::table('mc_deposit_requests')->where('user_id', $user->id)->where('status', 'pending')->count();

        if ($open >= (int) config('minecraft.deposit.max_pending')) {
            return ['ok' => false, 'error' => 'You already have a deposit waiting for the bot.', 'max_pending' => (int) config('minecraft.deposit.max_pending')];
        }

        $coins = $this->coinsFor($gameAmount);

        if ($coins < 1) {
            return ['ok' => false, 'error' => 'That amount is too small.'];
        }

        $id = DB::table('mc_deposit_requests')->insertGetId([
            'user_id' => $user->id,
            'player' => strtolower((string) $user->minecraft_ign),
            'game_amount' => $gameAmount,
            'coins' => $coins,
            'status' => 'pending',
            'expires_at' => now()->addMinutes((int) config('minecraft.deposit.request_ttl_minutes', 15)),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return ['ok' => true, 'request' => $this->presentDeposit((int) $id), 'coins_for_amount' => $coins];
    }

    /** @return array<string, mixed>|null */
    public function presentDeposit(int $id): ?array
    {
        $row = DB::table('mc_deposit_requests')->where('id', $id)->first();

        if ($row === null) {
            return null;
        }

        return [
            'id' => (int) $row->id,
            'game_amount' => (int) $row->game_amount,
            'coins' => (float) $row->coins,
            'status' => (string) $row->status,
            'expires_at' => (string) $row->expires_at,
            'expires_in' => max(0, now()->diffInSeconds($row->expires_at, false)),
        ];
    }

    /**
     * A signed "/pay" receipt from the bot: match the oldest pending request of
     * that player for that exact amount, then credit the wallet.
     *
     * @return array{status: string, coins?: float}
     */
    public function handleDeposit(string $eventId, string $player, int $gameAmount, ?string $raw): array
    {
        return DB::transaction(function () use ($eventId, $player, $gameAmount, $raw) {
            if (! $this->once($eventId, 'deposit')) {
                return ['status' => 'duplicate'];
            }

            $player = strtolower(trim($player));

            $request = DB::table('mc_deposit_requests')
                ->where('player', $player)
                ->where('game_amount', $gameAmount)
                ->where('status', 'pending')
                ->where('expires_at', '>', now())
                ->orderBy('created_at')
                ->lockForUpdate()
                ->first();

            if ($request === null) {
                DB::table('mc_unmatched')->insert([
                    'player' => mb_substr($player, 0, 16),
                    'game_amount' => $gameAmount,
                    'raw' => $raw !== null ? mb_substr($raw, 0, 300) : null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                return ['status' => 'unmatched'];
            }

            $user = User::where('id', $request->user_id)->lockForUpdate()->first();

            if ($user === null) {
                return ['status' => 'unmatched'];
            }

            DB::table('mc_deposit_requests')->where('id', $request->id)->update([
                'status' => 'completed',
                'completed_at' => now(),
                'updated_at' => now(),
            ]);

            $coins = (float) $request->coins;

            $user->update(['balance' => round((float) $user->balance + $coins, 2)]);

            DB::table('payments')->insert([
                'user_id' => $user->id,
                'system' => 'minecraft',
                'amount' => $coins,
                'status' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return ['status' => 'credited', 'coins' => $coins];
        });
    }

    /* ------------------------------------------------------------ withdrawals */

    /** In-game money the bot should be holding right now. */
    public function availableFloat(): int
    {
        $deposited = (int) DB::table('mc_deposit_requests')->where('status', 'completed')->sum('game_amount');

        $committed = 0;

        $open = DB::table('mc_withdrawals')
            ->whereIn('status', ['pending', 'sending', 'paid', 'unconfirmed'])
            ->get(['game_amount', 'tax']);

        foreach ($open as $row) {
            $committed += (int) $row->game_amount + $this->taxFor((int) $row->game_amount, (int) $row->tax);
        }

        return max(0, $deposited - $committed);
    }

    /**
     * Everything the admin panel shows about the bot's money, in in-game money.
     *
     * @return array<string, int>
     */
    public function floatSummary(): array
    {
        $deposited = (int) DB::table('mc_deposit_requests')->where('status', 'completed')->sum('game_amount');

        $paid = 0;
        $queued = 0;
        $payouts = 0;

        $rows = DB::table('mc_withdrawals')
            ->whereIn('status', ['pending', 'sending', 'paid', 'unconfirmed'])
            ->get(['status', 'game_amount', 'tax']);

        foreach ($rows as $row) {
            $amount = (int) $row->game_amount + $this->taxFor((int) $row->game_amount, (int) $row->tax);

            if ($row->status === 'paid') {
                $paid += $amount;
                $payouts++;
            } else {
                $queued += $amount;
            }
        }

        return [
            'deposited' => $deposited,
            'paid' => $paid,
            'queued' => $queued,
            'available' => max(0, $deposited - $paid - $queued),
            'completed_deposits' => (int) DB::table('mc_deposit_requests')->where('status', 'completed')->count(),
            'payouts_paid' => $payouts,
        ];
    }

    /** What this user may request right now, in in-game money. */
    public function availableFor(User $user): int
    {
        return max(0, min(
            $this->availableFloat(),
            $this->gameForCoins((float) $user->balance),
            (int) config('minecraft.withdraw.max_game'),
        ));
    }

    /** @return array{ok: bool, error?: string, withdrawal?: array<string, mixed>, min?: int, max?: int, available?: int, game_amount?: int, tax?: int} */
    public function createWithdrawal(User $user, int $gameAmount): array
    {
        if (! config('minecraft.withdraw.enabled')) {
            return ['ok' => false, 'error' => 'Withdrawals are temporarily unavailable.'];
        }

        if (! $this->verified($user)) {
            return ['ok' => false, 'error' => 'Verify your Minecraft name first.'];
        }

        $min = (int) config('minecraft.withdraw.min_game');
        $max = (int) config('minecraft.withdraw.max_game');

        if ($gameAmount < $min || $gameAmount > $max) {
            return ['ok' => false, 'error' => 'Enter an amount between '.number_format($min).' and '.number_format($max).' in-game.', 'min' => $min, 'max' => $max];
        }

        $coins = $this->coinsFor($gameAmount);
        $tax = $this->taxFor($gameAmount);

        if ($coins < 1) {
            return ['ok' => false, 'error' => 'That amount is too small.'];
        }

        $this->expireStale();

        return DB::transaction(function () use ($user, $gameAmount, $coins, $tax) {
            $locked = User::where('id', $user->id)->lockForUpdate()->first();

            if ((float) $locked->balance < $coins) {
                return ['ok' => false, 'error' => 'Not enough balance for that payout.'];
            }

            $open = DB::table('mc_withdrawals')
                ->where('user_id', $locked->id)
                ->whereIn('status', ['pending', 'sending', 'unconfirmed'])
                ->count();

            if ($open >= (int) config('minecraft.withdraw.max_pending')) {
                return ['ok' => false, 'error' => 'You already have a payout in the queue.'];
            }

            $available = $this->availableFor($locked);

            if ($gameAmount > $available) {
                return ['ok' => false, 'error' => 'Payouts are limited to what the bot holds right now.', 'available' => $available];
            }

            $locked->update(['balance' => round((float) $locked->balance - $coins, 2)]);

            $id = DB::table('mc_withdrawals')->insertGetId([
                'user_id' => $locked->id,
                'player' => strtolower((string) $locked->minecraft_ign),
                'game_amount' => $gameAmount,
                'coins' => $coins,
                'tax' => $tax,
                'status' => 'pending',
                'expires_at' => now()->addMinutes((int) config('minecraft.withdraw.pending_ttl_minutes', 30)),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return ['ok' => true, 'withdrawal' => $this->presentWithdrawal((int) $id), 'coins' => $coins, 'tax' => $tax];
        });
    }

    /** @return array<string, mixed>|null */
    public function presentWithdrawal(int $id): ?array
    {
        $row = DB::table('mc_withdrawals')->where('id', $id)->first();

        if ($row === null) {
            return null;
        }

        return [
            'id' => (int) $row->id,
            'player' => (string) $row->player,
            'game_amount' => (int) $row->game_amount,
            'coins' => (float) $row->coins,
            'tax' => (int) $row->tax,
            'status' => (string) $row->status,
            'at' => (string) $row->created_at,
        ];
    }

    public function cancelWithdrawal(User $user, int $id): array
    {
        $row = DB::table('mc_withdrawals')->where('id', $id)->where('user_id', $user->id)->first();

        if ($row === null) {
            return ['ok' => false, 'error' => 'Payout not found.'];
        }

        if ($row->status !== 'pending') {
            return ['ok' => false, 'error' => 'That payout is already being sent.'];
        }

        $this->refundWithdrawal($id, (float) $row->coins, 'cancelled');

        return ['ok' => true];
    }

    private function refundWithdrawal(int $id, float $coins, string $status): void
    {
        DB::transaction(function () use ($id, $coins, $status) {
            $row = DB::table('mc_withdrawals')->where('id', $id)->lockForUpdate()->first();

            if ($row === null || ! in_array($row->status, ['pending', 'sending'], true)) {
                return;
            }

            DB::table('mc_withdrawals')->where('id', $id)->update([
                'status' => $status,
                'completed_at' => now(),
                'updated_at' => now(),
            ]);

            $user = User::where('id', $row->user_id)->lockForUpdate()->first();

            if ($user !== null) {
                $user->update(['balance' => round((float) $user->balance + $coins, 2)]);
            }
        });
    }

    /**
     * The bot asks for the next payout. Only one is in flight globally and a
     * claim is marked `sending` before the bot is allowed to type /pay, so a
     * crash can never cause a double payment.
     *
     * @return array<string, mixed>|null
     */
    public function claimWithdrawal(): ?array
    {
        $this->expireStale();

        return DB::transaction(function () {
            if (DB::table('mc_withdrawals')->whereIn('status', ['sending', 'unconfirmed'])->exists()) {
                return null;
            }

            $row = DB::table('mc_withdrawals')
                ->where('status', 'pending')
                ->where('expires_at', '>', now())
                ->orderBy('created_at')
                ->lockForUpdate()
                ->first();

            if ($row === null) {
                return null;
            }

            DB::table('mc_withdrawals')->where('id', $row->id)->update([
                'status' => 'sending',
                'claimed_at' => now(),
                'updated_at' => now(),
            ]);

            return [
                'id' => (int) $row->id,
                'player' => (string) $row->player,
                'game_amount' => (int) $row->game_amount,
                'tax' => (int) $row->tax,
            ];
        });
    }

    /** @return array{ok: bool, status?: string, error?: string} */
    public function completeWithdrawal(int $id, string $status, ?int $tax, ?string $note): array
    {
        $row = DB::table('mc_withdrawals')->where('id', $id)->first();

        if ($row === null) {
            return ['ok' => false, 'error' => 'unknown_withdrawal'];
        }

        if ($row->status === 'paid') {
            return ['ok' => true, 'status' => 'already_paid'];
        }

        if ($row->status === 'unconfirmed') {
            return ['ok' => true, 'status' => 'already_claimed'];
        }

        // The bot typed /pay but never saw a confirmation: hold it for review and
        // do not refund, because the money may already be gone.
        if ($status === 'unconfirmed') {
            DB::table('mc_withdrawals')->where('id', $id)->update([
                'status' => 'unconfirmed',
                'note' => $note !== null ? mb_substr($note, 0, 200) : null,
                'updated_at' => now(),
            ]);

            return ['ok' => true, 'status' => 'unconfirmed'];
        }

        if ($status === 'paid') {
            DB::table('mc_withdrawals')->where('id', $id)->update([
                'status' => 'paid',
                'tax' => $this->taxFor((int) $row->game_amount, $tax),
                'note' => $note !== null ? mb_substr($note, 0, 200) : null,
                'completed_at' => now(),
                'updated_at' => now(),
            ]);

            return ['ok' => true, 'status' => 'paid'];
        }

        DB::table('mc_withdrawals')->where('id', $id)->update([
            'status' => 'sending',
            'note' => $note !== null ? mb_substr($note, 0, 200) : null,
            'updated_at' => now(),
        ]);

        $this->refundWithdrawal($id, (float) $row->coins, 'failed');

        return ['ok' => true, 'status' => 'failed'];
    }
}
