# DonutSMP bridge

The bot that connects the site's wallet to the game server. It does three things:

1. **Sign up / log in**: a player enters their IGN on the site, gets an 8-character
   code, and whispers it to the bot (`/msg <bot> CODE`). The bot forwards the code
   to the site, which signs the player in — or links the IGN to the account they
   are already signed into.
2. **Deposits**: when the bot sees `<player> paid you $1.5m` in a system message,
   it reports the receipt. The site credits the matching pending deposit at the
   rate in `config/minecraft.php`. No pending request means no credit.
3. **Payouts**: the bot polls the site for the next queued withdrawal, runs
   `/pay <player> <amount>`, and reports whether the server confirmed it.

Only system/whisper output is trusted. A receipt typed in normal player chat is
never credited (`MC_ALLOW_CHAT_POSITION=1` disables that guard — do not use it).

## Setup

```bash
cd minecraft-bridge
npm install
cp .env.example .env      # then fill MC_BRIDGE_SECRET, MC_BOT_USERNAME
npm start
```

Set the same secret on the site (`MC_BRIDGE_SECRET` in the project `.env`) and
the bot's display name (`MC_BOT_IGN`, optional — the bridge reports it on the
first heartbeat, so the site learns it automatically). No account name is
hardcoded anywhere.

Then apply the wallet tables once:

```bash
php artisan migrate
```

## What the site exposes to it

| Endpoint | Purpose |
| --- | --- |
| `POST /api/mc/heartbeat` | bot online/offline state shown on the wallet pages |
| `POST /api/bot/link` | `{player, code}` whispered by a player |
| `POST /api/bot/deposit` | `{player, amount, raw}` receipt for a `/pay` |
| `POST /api/bot/withdrawals/claim` | bot asks for the next payout |
| `POST /api/bot/withdrawals/result` | `{id, status: paid\|failed\|unconfirmed, receipt, reason}` |
| `POST /api/mc/chat` | public chat, accepted and dropped on this site |

All of them are HMAC-SHA256 signed (`X-Timestamp` + `X-Signature` over
`"<ms>.<body>"`) and reject anything older than 30 seconds.

## Payout safety

* One payout is in flight globally; it is marked `sending` before the bot may
  type `/pay`, so a crash cannot pay twice.
* Coins are taken from the balance when the payout is requested and refunded
  only when the bot reports a failure or the request expires.
* No confirmation within 10 seconds → `unconfirmed`: held for review, never
  auto-refunded and never paid a second time.
* Payouts are capped by the float the bot reports, so the bot cannot be drained.
