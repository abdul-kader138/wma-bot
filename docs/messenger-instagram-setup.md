**Messenger & Instagram Setup**

Purpose
- This document explains how to configure Facebook Messenger and Instagram (Business) to work with this Laravel app. It lists required Meta App settings, environment variables, admin panel entries (WhatsAppAccount rows reused for Messenger/Instagram), webhook URLs, and test commands.

Prerequisites
- A public HTTPS URL for your app (ngrok for local development).
- A Meta developer account and a Meta App (https://developers.facebook.com).
- A Page (for Messenger) and/or an Instagram Business Account connected to a Facebook Page.

Key app pieces (how this project expects configuration)
- Webhook endpoints: `/api/webhook/messenger` and `/api/webhook/instagram` (see `routes/api.php`).
- Shared config keys in [config/services.php](config/services.php): `messenger.verify_token`, `messenger.app_secret`, `instagram.verify_token`, `instagram.app_secret` (populated from env vars).
- Per-account tokens and metadata are stored in the `whatsapp_accounts` table and managed in the admin panel (model: `App\\Models\\WhatsAppAccount`). Key fields used by the app:
  - `platform` (set to `messenger` or `instagram`)
  - `external_id` (Page ID or Instagram Business Account ID)
  - `access_token` (Page Access Token / IG access token)
  - `api_version` (e.g., `v17.0`)
  - `app_secret` (optional; override platform-wide app secret if this account uses a different Meta App)

Environment variables
- Add the following to your `.env` (or set the same values in the admin panel Settings where appropriate):

- `MESSENGER_VERIFY_TOKEN` — a random string you choose for webhook verification.
- `MESSENGER_APP_SECRET` — your Meta App secret for Messenger.
- `INSTAGRAM_VERIFY_TOKEN` — a random string you choose for Instagram webhook verification.
- `INSTAGRAM_APP_SECRET` — your Meta App secret for Instagram (usually same as messenger if under same App).

High-level setup (Meta developers)
1. Create a Meta App at https://developers.facebook.com/apps and note the App ID and App Secret.
2. In the App Dashboard, add the **Messenger** product (for Messenger) and **Instagram Graph API** (for Instagram). Follow Meta's guides to connect your Facebook Page and Instagram Business Account to the app.
3. Generate a Page Access Token for your Page. This is the token the server will use to call the Graph Send API. Save it — it goes into the `access_token` field of the corresponding account row in the admin panel.
4. In the App -> Webhooks section, add a webhook subscription and set the callback URL to:
   - `https://<your-domain>/api/webhook/messenger` for Messenger
   - `https://<your-domain>/api/webhook/instagram` for Instagram
   Use the same verify token value you set in `MESSENGER_VERIFY_TOKEN` / `INSTAGRAM_VERIFY_TOKEN`.
5. When choosing fields to subscribe to, make sure to include the messaging-related fields so the app will receive `entry[].messaging[]` payloads (the code expects that array). Typical fields include `messages`, `messaging_postbacks` and related message events for Pages/Instagram messaging.
6. If you use the same Meta App for both platforms you can reuse the App Secret for both `MESSENGER_APP_SECRET` and `INSTAGRAM_APP_SECRET`. If an individual Page/IG account belongs to a different Meta App, set the `app_secret` column for that `whatsapp_accounts` row (see migration `2026_08_10_130000_add_app_secret_to_whatsapp_accounts.php`).

Admin panel / database steps (how to tell the app about the Page / IG account)
1. In the app admin UI (Settings -> WhatsApp Accounts / Pages) create a new account row with:
   - `platform`: `messenger` or `instagram`
   - `name`: friendly name
   - `external_id`: the Page ID (Messenger) or Instagram Business Account ID (Instagram)
   - `access_token`: the Page Access Token or Instagram token
   - `api_version`: Graph API version (e.g., `v17.0`)
   - `app_secret`: optional override if this account uses a different Meta App
   - `is_active`: true
2. Mark one account `is_default` per platform if you want it to be the default send-receiver for that channel.

Webhook security and signature verification
- This app validates incoming POSTs using Meta's HMAC signature placed in header `X-Hub-Signature-256`. See `App\\Http\\Controllers\\Concerns\\VerifiesMetaWebhookSignature` — the code checks the request body against `sha256` HMAC using the platform app secret(s).
- Make sure your `MESSENGER_APP_SECRET` / `INSTAGRAM_APP_SECRET` or the account-specific `app_secret` value(s) are correct or webhooks will be rejected with `403 Invalid signature`.

Testing the webhook verification (handshake)
- Open a browser or use curl to test the GET verification handshake. Replace `VERIFY_TOKEN` with the value you set in the app/email settings or `.env`.

```
curl "https://<your-domain>/api/webhook/messenger?hub.mode=subscribe&hub.verify_token=VERIFY_TOKEN&hub.challenge=CHALLENGE"
```

If configured correctly the endpoint should return the `CHALLENGE` value (HTTP 200). If not, check the verify token value and that your app/Webhook subscription is set to the exact same token.

Sending a test message (using Graph Send API)
- Minimal example: send a text reply using the Page Access Token. Replace `PAGE_ACCESS_TOKEN` and recipient ID `PSID` with the real values:

```
curl -X POST "https://graph.facebook.com/v17.0/me/messages" \
  -H "Authorization: Bearer PAGE_ACCESS_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"recipient":{"id":"PSID"},"message":{"text":"Hello from the app"}}'
```

Notes:
- For Messenger, `PSID` is the Page-scoped ID for a user (you'll receive it in incoming webhook events under `entry[].messaging[].sender.id`).
- For Instagram, recipient IDs follow Instagram's ID scheme; incoming webhooks supply the sender ID the same way.

Testing signature verification locally
- When testing with a request signer (or replaying requests) the server expects header `X-Hub-Signature-256` set to `sha256=` + HMAC-SHA256(body, app_secret). Example to compute this in bash (replace `APP_SECRET`):

```
body='{"dummy":"payload"}'
sig=$(printf "%s" "$body" | openssl dgst -sha256 -hmac "$APP_SECRET" -binary | xxd -p -c 256)
echo "sha256=$sig"
```

Troubleshooting
- 403 Invalid signature: double-check `*_APP_SECRET` env vars and any `app_secret` value on the account row.
- Webhook returns 403 on handshake: confirm `*_VERIFY_TOKEN` matches what you entered into the App Dashboard webhook verify token.
- Messages not arriving: confirm the app is subscribed to the Page/IG account (App -> Webhooks -> Subscriptions -> Subscribe app to page) and the Page Access Token is valid and not expired.
- Development on localhost: use `ngrok` or similar to expose HTTPS and set that URL in the Webhooks configuration.

Converting to PDF (optional)
- If you want a PDF copy, use `pandoc` or VS Code's Export to PDF. Example using `pandoc`:

```
pandoc docs/messenger-instagram-setup.md -o docs/messenger-instagram-setup.pdf
```

Further reading
- Meta Webhooks: https://developers.facebook.com/docs/graph-api/webhooks
- Meta Messenger Platform: https://developers.facebook.com/docs/messenger-platform
- Instagram Messaging: https://developers.facebook.com/docs/messenger-platform/instagram

If you'd like, I can also generate a PDF copy now and add it to the `docs/` folder.
