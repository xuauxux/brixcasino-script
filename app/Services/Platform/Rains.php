<?php

namespace App\Services\Platform;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Point rains. The owner sets the interval, the pot and the length; rains then
 * run themselves, players join while the countdown is live, and the pot is
 * split between everyone who joined. Finished rains are marked before paying,
 * and (rain_id, user_id) is unique, so nobody can be paid twice.
 */
class Rains
{
    /** The rain that is accepting players right now, finishing it if it timed out. */
    public function active(): ?object
    {
        if (! Schema::hasTable('rain_events')) {
            return null;
        }

        $rain = DB::table('rain_events')->where('status', 'running')->orderByDesc('id')->first();

        if ($rain === null) {
            return null;
        }

        if ($rain->ends_at !== null && Carbon::parse($rain->ends_at)->isPast()) {
            $this->finish((int) $rain->id);

            return null;
        }

        return $rain;
    }

    /** Auto-start when the configured interval has elapsed. */
    public function tick(): void
    {
        $settings = Settings::rain();

        if (! $settings['enabled'] || ! Schema::hasTable('rain_events')) {
            return;
        }

        if ($this->active() !== null) {
            return;
        }

        $last = DB::table('rain_events')->orderByDesc('id')->first();
        $dueAt = $last === null
            ? Carbon::now()->subMinutes($settings['interval'])
            : Carbon::parse($last->started_at ?? $last->created_at)->addMinutes($settings['interval']);

        if ($dueAt->isPast()) {
            $this->start(null, $settings['amount'], $settings['duration']);
        }
    }

    /** @return array{ok: bool, error?: string, rain?: object} */
    public function start(?int $adminId = null, ?float $amount = null, ?int $duration = null): array
    {
        $settings = Settings::rain();
        $amount = $amount ?? $settings['amount'];
        $duration = $duration ?? $settings['duration'];

        if ($amount <= 0) {
            return ['ok' => false, 'error' => 'The rain pot must be greater than zero.'];
        }

        if ($this->active() !== null) {
            return ['ok' => false, 'error' => 'A rain is already running.'];
        }

        $now = Carbon::now();

        $id = DB::table('rain_events')->insertGetId([
            'amount' => round($amount, 2),
            'status' => 'running',
            'started_by' => $adminId,
            'started_at' => $now,
            'ends_at' => $now->copy()->addSeconds($duration),
            'participants' => 0,
            'paid_out' => 0,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        Settings::set('rain_last_started', $now->toDateTimeString());

        return ['ok' => true, 'rain' => DB::table('rain_events')->where('id', $id)->first()];
    }

    /** @return array{ok: bool, error?: string, amount?: float, participants?: int} */
    public function stop(): array
    {
        $rain = $this->active();

        if ($rain === null) {
            return ['ok' => false, 'error' => 'No rain is running.'];
        }

        return $this->finish((int) $rain->id);
    }

    /** @return array{ok: bool, error?: string, rain_id?: int} */
    public function join(User $user): array
    {
        $rain = $this->active();

        if ($rain === null) {
            return ['ok' => false, 'error' => 'No rain is running right now.'];
        }

        if (DB::table('rain_participants')->where('rain_id', $rain->id)->where('user_id', $user->id)->exists()) {
            return ['ok' => false, 'error' => 'You are already in this rain.'];
        }

        DB::table('rain_participants')->insert([
            'rain_id' => $rain->id,
            'user_id' => $user->id,
            'amount' => 0,
            'joined_at' => now(),
        ]);

        DB::table('rain_events')->where('id', $rain->id)->update([
            'participants' => DB::table('rain_participants')->where('rain_id', $rain->id)->count(),
            'updated_at' => now(),
        ]);

        (new Activity())->points($user, 25, 'rain');

        return ['ok' => true, 'rain_id' => (int) $rain->id];
    }

    /** @return array{ok: bool, error?: string, amount?: float, participants?: int} */
    public function finish(int $rainId): array
    {
        $rain = DB::table('rain_events')->where('id', $rainId)->first();

        if ($rain === null || $rain->status !== 'running') {
            return ['ok' => false, 'error' => 'That rain is not running.'];
        }

        // Claim it first: a concurrent request cannot pay the same rain twice.
        $claimed = DB::table('rain_events')
            ->where('id', $rainId)
            ->where('status', 'running')
            ->update(['status' => 'finished', 'updated_at' => now()]);

        if ($claimed === 0) {
            return ['ok' => false, 'error' => 'That rain is not running.'];
        }

        $participants = DB::table('rain_participants')->where('rain_id', $rainId)->get();

        if ($participants->isEmpty()) {
            return ['ok' => true, 'amount' => 0, 'participants' => 0];
        }

        $share = floor(((float) $rain->amount) / $participants->count());
        $wallet = new Wallet();

        foreach ($participants as $participant) {
            if ($share <= 0) {
                break;
            }

            $wallet->credit((int) $participant->user_id, $share, 'rain', 'Rain #'.$rainId, ['rain_id' => $rainId]);

            DB::table('rain_participants')->where('id', $participant->id)->update(['amount' => $share]);
        }

        DB::table('rain_events')->where('id', $rainId)->update([
            'paid_out' => $share * $participants->count(),
            'participants' => $participants->count(),
            'updated_at' => now(),
        ]);

        return ['ok' => true, 'amount' => $share * $participants->count(), 'participants' => $participants->count()];
    }

    /** Recent rains for the panel. */
    public function history(int $limit = 10): array
    {
        if (! Schema::hasTable('rain_events')) {
            return [];
        }

        return DB::table('rain_events')->orderByDesc('id')->limit($limit)->get()->all();
    }
}
