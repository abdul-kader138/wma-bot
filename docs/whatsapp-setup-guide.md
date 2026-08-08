---
title: "WhatsApp Number Setup Guide"
subtitle: "How to get your WABA ID, Phone Number ID, and Access Token from Meta, and connect a number to the bot"
author: "WMA Bot"
date: "August 2026"
lang: en
mainfont: "Noto Sans"
fontsize: 12pt
geometry: margin=24mm
---

# Overview

To connect a WhatsApp number to the bot, you need three things from Meta, plus one secret you create yourself:

1. **WABA ID** (WhatsApp Business Account ID)
2. **Phone Number ID**
3. **Access Token**
4. **Webhook Verify Token** — a password you invent yourself (not from Meta)

This guide shows exactly where to find each one.

# Step 1 — Create a Meta App

1. Go to **developers.facebook.com** and log in with a Facebook account.
2. Click **My Apps → Create App**.
3. Choose the **Business** app type.
4. Give the app a name (e.g. "WMA Bot") and create it.
5. On the app dashboard, find **WhatsApp** in the product list and click **Set up**.

# Step 2 — Get the WABA ID and Phone Number ID

After adding the WhatsApp product, Meta opens the **API Setup** page. This page shows:

- A **From** dropdown with a phone number (Meta gives you a free test number automatically).
- Below it, a box labeled **Phone number ID** with a long number and a **Copy** button.
- The **WhatsApp Business Account ID** is shown either on this same page or under **WhatsApp → Configuration → the account name link**.

Copy both values into a safe place (e.g. a password manager or a notes file):

```
WABA ID:          123456789012345
Phone Number ID:  109876543210987
```

**Note:** the Phone Number ID is not the same as the phone number itself. It is an internal ID, always a long number.

If you want to use your **own business phone number** instead of the free test number:

1. On the API Setup page, click **Add phone number**.
2. Enter your business details and the phone number.
3. Verify it by SMS or a phone call code.
4. Once verified, it becomes selectable in the **From** dropdown, with its own Phone Number ID.

# Step 3 — Get an Access Token

## Quick option (for testing only)

On the same **API Setup** page, there is a **Temporary access token** box with a **Copy** button. This works immediately but **expires after 24 hours**, so it is only good for a quick test.

## Permanent option (for production use)

1. Go to **business.facebook.com** → **Settings → Business Settings**.
2. In the left menu, open **Users → System Users**.
3. Click **Add** to create a new system user (e.g. name it "wma-bot"), role **Admin**.
4. Click **Add Assets**, select your WhatsApp app/account, and grant **Full control**.
5. With the system user selected, click **Generate new token**.
6. Choose your app, and select these permissions:
   - `whatsapp_business_messaging`
   - `whatsapp_business_management`
7. Click **Generate token** and copy it immediately — Meta only shows it once.

This token does not expire, so it is what should be used in the live bot.

# Step 4 — Get the App Secret

1. On the Meta App dashboard, go to **Settings → Basic**.
2. Find **App Secret**, click **Show**, and copy it.

This value is not entered in the admin panel. It goes into the server's `.env` file as:

```
WHATSAPP_APP_SECRET=paste_it_here
```

Then the server needs `php artisan config:cache` run once to pick it up. (Ask your developer/hosting person to do this if you don't manage the server yourself.)

# Step 5 — Create the Webhook Verify Token

This one is not from Meta — you make it up yourself. Any random string works, for example:

```
wma-bot-8f2a91c4
```

Use the **same string** in two places:

1. **Admin panel → System Settings → WhatsApp tab → Webhook Verify Token** — paste it and Save.
2. **Meta App dashboard → WhatsApp → Configuration → Webhook → Edit** — paste the same string into the **Verify token** field.

The **Callback URL** in that same Meta screen should be:

```
https://tmmtravels.it/api/webhook/whatsapp
```

Click **Verify and Save**, then click **Manage** next to Webhook fields and subscribe to **messages**.

# Step 6 — Add the Number in the Admin Panel

Once you have the WABA ID, Phone Number ID, and Access Token:

1. Log in to the admin panel.
2. Left menu → **Administration → WhatsApp Accounts → Create**.
3. Fill in the form:

| Field | What to enter |
|---|---|
| Name | Any label, e.g. "Main Support Line" |
| WABA ID | From Step 2 |
| Phone Number ID | From Step 2 |
| API Version | Leave as `v22.0` |
| Access Token | From Step 3 |
| Active | Turn on |
| Default | Turn on if this is the main/primary number |

4. Click **Save**.

# Step 7 — Test It

1. From your own phone, send a WhatsApp message to the number.
   - If using Meta's free test number, that phone must first be added under **API Setup → Manage phone number list** (Meta only allows up to 5 test recipients).
2. Check the admin panel → **Conversations** — a new conversation should appear within a few seconds.
3. If nothing shows up:
   - Confirm the webhook shows a green "Verified" status in Meta.
   - Confirm the Webhook Verify Token matches exactly in both places (Step 5).
   - Ask your developer to check `storage/logs/laravel.log` on the server and confirm the background queue worker is running.

# Summary Checklist

- [ ] WABA ID copied
- [ ] Phone Number ID copied
- [ ] Access Token generated (permanent, for production)
- [ ] App Secret added to server `.env`
- [ ] Webhook Verify Token created and matches in both Meta and admin panel
- [ ] Callback URL set in Meta and shows "Verified"
- [ ] Subscribed to the `messages` webhook field
- [ ] WhatsApp Account created in admin panel with all credentials
- [ ] Test message sent and conversation appears in admin panel
