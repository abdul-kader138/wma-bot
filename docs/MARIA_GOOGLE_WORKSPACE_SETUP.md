# Maria Assistant — Google Workspace Setup Guide

Version 1.0

Last updated: 13 August 2026

## 1. Purpose

This guide explains how to connect Google Workspace to Maria Assistant. Complete the steps in order: prepare Maria, configure Google Cloud, connect a user with read-only permissions, test Gmail and Calendar, and only then optionally enable approved writes.

Start with read-only access. Do not enable Gmail sending or Calendar event creation until the read-only acceptance tests pass.

## 2. Prepare Maria

1. Sign in to the application as an administrator.
2. Open **System Settings → General**.
3. Turn on **Enable Maria Assistant**.
4. Save the settings.
5. Confirm the intended user's role has **Access Maria Assistant** and the required Maria resource permissions.
6. Open **Maria Assistant → Maria Settings**.
7. Open or create the intended user's profile.
8. Confirm:
   - the profile is active;
   - timezone is correct;
   - working hours are configured;
   - **Email Triage** is selected;
   - **Meeting Preparation** is selected.
9. Save the profile.

Expected result: the user can sign in and see **Maria Assistant → Connections**.

## 3. Create the Google Cloud project

1. Open [Google Cloud Console](https://console.cloud.google.com/).
2. Sign in using an authorized Google Workspace administrator account.
3. Click the project selector at the top.
4. Click **New Project**.
5. Enter a clear name, for example `Maria Assistant Production`.
6. Select the correct Google Workspace organization, if shown.
7. Click **Create**.
8. Wait for project creation to finish.
9. Select the new project before continuing.

Important: always confirm the correct project name in the Google Cloud header before changing an API, consent screen, or OAuth client.

## 4. Enable the required APIs

1. In Google Cloud, open **APIs & Services → Library**.
2. Search for **Gmail API**, open it, and click **Enable**.
3. Return to the API Library.
4. Search for **Google Calendar API**, open it, and click **Enable**.
5. Return to the API Library.
6. Search for **Google Drive API**, open it, and click **Enable**.

| API | Maria feature |
|---|---|
| Gmail API | Email triage and approved email sending |
| Google Calendar API | Meeting preparation and approved event creation |
| Google Drive API | Read-only Drive metadata lookup |

Official reference: [Enable Google Workspace APIs](https://developers.google.com/workspace/guides/enable-apis).

Expected result: all three APIs appear under **APIs & Services → Enabled APIs & services**.

## 5. Configure Google Auth Platform

### 5.1 Branding

1. Open **Google Auth Platform → Branding**.
2. If Google shows **Get Started**, click it.
3. Enter:
   - **App name:** `Maria Assistant`;
   - **User support email:** the approved support or Workspace administrator email;
   - **Developer contact email:** the technical contact email.
4. Add an approved logo only if required.
5. Save.

### 5.2 Audience

1. Open **Google Auth Platform → Audience**.
2. Choose the appropriate audience:
   - **Internal:** all Maria users belong to the same eligible Google Workspace organization;
   - **External:** approved users outside that organization must connect.
3. When using External in testing mode, add every intended Google account under **Test users**.
4. Save.

Use Internal when Maria is private to one eligible Workspace organization. If the account is not listed as an allowed internal or test user, Google may refuse authorization.

### 5.3 Data access and scopes

Open **Google Auth Platform → Data Access**. Maria uses these read scopes:

```text
openid
email
https://www.googleapis.com/auth/gmail.readonly
https://www.googleapis.com/auth/calendar.events.readonly
https://www.googleapis.com/auth/drive.metadata.readonly
```

Optional write scopes are:

```text
https://www.googleapis.com/auth/gmail.send
https://www.googleapis.com/auth/calendar.events
```

If Google asks you to declare or justify scopes, explain:

- Gmail read-only is used for email triage and draft preparation.
- Calendar event read-only is used for event synchronization and meeting preparation.
- Drive metadata read-only is used to locate referenced Workspace files.
- Gmail send is used only for a separately approved, exact email.
- Calendar events is used only for a separately approved, exact event.

Request only the minimum access required. See [Google OAuth policy guidance](https://developers.google.com/identity/protocols/oauth2/production-readiness/policy-compliance).

## 6. Obtain Maria's redirect URI

1. Return to Maria Assistant.
2. Open **System Settings → Google Workspace**.
3. Locate **Redirect URI**.
4. Copy it exactly.

The URI follows this pattern:

```text
https://YOUR-DOMAIN/panel-api/connectors/google/callback
```

For an application at `https://assistant.example.com`, the URI would be:

```text
https://assistant.example.com/panel-api/connectors/google/callback
```

Use the value displayed in Maria. Do not guess it. These parts must match Google exactly:

- `https` scheme;
- domain and subdomain;
- optional port;
- complete callback path;
- capitalization;
- trailing-slash behavior.

An exact mismatch causes Google's `redirect_uri_mismatch` error. See [Google OAuth for web-server applications](https://developers.google.com/identity/protocols/oauth2/web-server).

## 7. Create the OAuth client

1. In Google Cloud, open **Google Auth Platform → Clients**.
2. Click **Create Client**.
3. Choose **Web application** as the application type.
4. Enter a name such as `Maria Assistant Production Web Client`.
5. Find **Authorized redirect URIs**.
6. Click **Add URI**.
7. Paste the exact Redirect URI copied from Maria.
8. Do not put the callback only under Authorized JavaScript origins.
9. Click **Create**.
10. Securely copy the generated:
    - Client ID;
    - Client Secret.

Maria uses a confidential, server-side OAuth web flow. Do not create a Desktop, Android, iOS, API-key, or service-account credential for this connection.

## 8. Save Google credentials in Maria

1. In Maria, open **System Settings → Google Workspace**.
2. Paste the Google **Client ID**.
3. Paste the Google **Client Secret**.
4. Confirm the **Redirect URI** is correct.
5. Click **Save**.

Security rules:

- The saved Client Secret is encrypted.
- Maria does not display the stored secret again.
- When editing later, leave Client Secret empty to preserve the stored value.
- Never put the secret in Git, screenshots, chat messages, tickets, or manuals.

## 9. Connect the user's Google account

The user who owns the Gmail and Calendar data must complete this section while signed in to their own Maria account.

1. Open **Maria Assistant → Connections**.
2. Click **Connect Google Workspace**.
3. On Google's account-selection page, choose the correct Workspace account.
4. Review the requested permissions.
5. Grant the read permissions.
6. Wait for Google to redirect back to Maria.
7. Confirm the Connections row shows:
   - provider `google`;
   - the correct Workspace email address;
   - status `active`;
   - a permission count;
   - no current error.

If multiple Google accounts are open, compare the connected email carefully. If it is wrong, revoke/disconnect it before proceeding.

## 10. Read-only acceptance test

### 10.1 Gmail test

1. Send a harmless email to the connected Gmail account.
2. Use a clear subject such as `Maria connection test – do not reply`.
3. Confirm current local time is inside the user's configured working hours.
4. Wait for the next email-triage cycle; it runs every 30 minutes.
5. Open **Maria Assistant → Email Triage**.
6. Confirm the message was processed.
7. Review classification, summary, commitments, follow-up date, and draft reply.
8. Confirm Maria did not send an email.

### 10.2 Calendar test

1. Create an event in the connected Google Calendar for the next day.
2. Use a title such as `Maria Calendar Connection Test`.
3. Add a harmless test description.
4. Wait for the hourly meeting-preparation workflow.
5. Open **Maria Assistant → Meetings**.
6. Confirm the event appears with the correct date, time, timezone, and details.
7. Confirm Maria did not create or modify any Calendar event.

### 10.3 Workflow test

1. Open **Maria Assistant → Workflow Runs**.
2. Find the email-triage and meeting-preparation runs.
3. Confirm they completed successfully.
4. If a workflow failed, open its details and record the error before retrying.

Read-only acceptance passes when Maria can read and prepare internal output but makes no external changes.

## 11. Optionally enable approved writes

Only continue after the read-only acceptance test passes and the organization authorizes external writes.

1. Open **Maria Assistant → Connections**.
2. Click **Enable approved writes**.
3. Read and confirm the warning.
4. Select the same Google account.
5. Grant Gmail send and Calendar event permissions.
6. Return to Maria and confirm the connection remains active.
7. Open **Maria Assistant → Maria Settings**.
8. Turn on **Allow approved external actions** for the user.
9. As an administrator, open **Maria Assistant → External Action Control**.
10. Confirm global external actions are enabled.

Write access still requires all of these controls:

- Maria is globally enabled.
- User has Maria permission.
- User profile is active.
- Connector has the correct write scope.
- Per-profile approved external actions are enabled.
- Global external actions are enabled.
- A current exact-content approval exists.
- No unresolved reconciliation blocks execution.

Changing recipients, content, attachments, channel, event details, or dates invalidates an approval.

## 12. Troubleshooting

### `redirect_uri_mismatch`

Compare:

- Maria: **System Settings → Google Workspace → Redirect URI**;
- Google: **Google Auth Platform → Clients → Authorized redirect URIs**.

They must be character-for-character identical.

### This app is blocked or access denied

Check:

- user is an allowed Internal user or listed Test user;
- audience is configured correctly;
- Gmail, Calendar, and Drive APIs are enabled;
- requested scopes are declared;
- Workspace administrator policies allow the application.

### Connection does not become active

Check:

- Client ID and Client Secret belong to the same OAuth client;
- redirect URI belongs to that client;
- correct Google Cloud project is selected;
- production application uses HTTPS;
- Connections row and server logs show no unresolved error.

### Email triage does not run

Confirm:

- Maria is enabled globally;
- user has Maria permission;
- profile is active;
- Email Triage workflow is enabled;
- Google connector is active;
- local time is inside working hours;
- Laravel scheduler and Horizon are running.

### Calendar event does not appear

Confirm Meeting Preparation is enabled, Calendar read scope was granted, the event belongs to the connected account/calendar, the profile is active, and the hourly workflow has run.

### Google token was revoked or expired

Correct the consent/client problem and reconnect the user. Do not copy tokens manually into the database. Tokens can be revoked by the user or Google at any time.

## 13. Final checklist

- [ ] Maria is globally enabled.
- [ ] User role has Access Maria Assistant.
- [ ] User profile is active and timezone is correct.
- [ ] Email Triage and Meeting Preparation are selected.
- [ ] Gmail API is enabled.
- [ ] Google Calendar API is enabled.
- [ ] Google Drive API is enabled.
- [ ] Branding, Audience, and Data Access are configured.
- [ ] Correct users are allowed by Internal/Test-user settings.
- [ ] OAuth client type is Web application.
- [ ] Redirect URI exactly matches Maria.
- [ ] Client ID and encrypted Client Secret are saved in Maria.
- [ ] Correct Workspace account appears active in Connections.
- [ ] Gmail read-only test passes.
- [ ] Calendar read-only test passes.
- [ ] Workflow Runs show successful processing.
- [ ] Writes remain disabled unless explicitly approved.
- [ ] If writes are enabled, both safety switches and exact approvals are understood.
