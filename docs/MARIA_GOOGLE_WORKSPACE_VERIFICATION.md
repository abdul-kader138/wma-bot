# Maria Assistant — Google Workspace Verification Checklist

**Use after the Google Client ID and Client Secret have been saved**

Last updated: 16 August 2026

Complete the checks in order. Use only harmless test data. Mark every result **Pass** or **Fail**. Keep sending and calendar creation disabled until all read-only checks pass.

> Never put the Client Secret, access token, or refresh token in this checklist, a screenshot, chat, or support ticket.

## 1. Check that Maria is enabled

1. Sign in as an administrator.
2. Open **System Settings → General**.
3. Confirm **Enable Maria Assistant** is on.
4. Save if changed, then refresh.
5. Confirm the **Maria Assistant** navigation group is visible.

**Pass:** The Maria Assistant menu is visible.  
Result: [ ] Pass  [ ] Fail

## 2. Check the user's permission

1. Open the role/permission editor.
2. Select the role assigned to the intended Maria user.
3. Confirm **Access Maria Assistant** is enabled.
4. Confirm access to **Connections**, **Email Triage**, **Meetings**, and **Workflow Runs**.
5. Sign in as that user and confirm the Maria menu opens without an access error.

**Pass:** The intended user can open the required Maria pages.  
Result: [ ] Pass  [ ] Fail

## 3. Check the Maria profile

1. Open **Maria Assistant → Maria Settings**.
2. Open or create the intended user's profile.
3. Confirm the profile is **Active**.
4. Confirm timezone and working hours are correct.
5. Enable **Email Triage** and **Meeting Preparation**.
6. Keep **Allow approved external actions** off during read-only testing.
7. Save.

**Pass:** The correct user has an active profile and both workflows are selected.  
Result: [ ] Pass  [ ] Fail

## 4. Check OAuth configuration

1. Open **System Settings → Google Workspace**.
2. Confirm a Client ID is present and Maria says a Client Secret is stored.
3. Copy the displayed **Redirect URI**.
4. In Google Cloud, open **Google Auth Platform → Clients** and select Maria's client.
5. Confirm its type is **Web application**.
6. Confirm Maria's URI appears under **Authorized redirect URIs** exactly—including `https`, domain, path, port, capitalization, and trailing slash.
7. In the same Cloud project, confirm Gmail API, Google Calendar API, and Google Drive API are enabled.

Expected callback pattern:

```text
https://YOUR-DOMAIN/panel-api/connectors/google/callback
```

Leave Maria's Client Secret field empty when editing later to keep the saved secret.

**Pass:** The redirect URI matches exactly and all three APIs are enabled.  
Result: [ ] Pass  [ ] Fail

## 5. Connect the user's Google account

Do this while signed in as the user who owns the Gmail and Calendar data.

1. Open **Maria Assistant → Connections**.
2. Click **Connect Google Workspace**.
3. Choose the correct Workspace account.
4. Review and grant the requested read permissions.
5. Allow Google to redirect back to Maria.
6. Confirm the Connections row shows:
   - provider `google`;
   - the correct email address;
   - status `active`;
   - permissions/scopes;
   - no current error.

**Pass:** The correct Google account is active.  
Result: [ ] Pass  [ ] Fail

## 6. Test Gmail read access

1. From another account, send a harmless email to the connected inbox.
2. Use subject **Maria connection test — do not reply**.
3. Make sure the current time is inside the user's configured working hours.
4. Wait for Email Triage (normally every 30 minutes during working hours).
5. Open **Maria Assistant → Email Triage**.
6. Find the test message and inspect its classification, summary, commitments, follow-up information, and draft.
7. Check Gmail **Sent** and confirm Maria sent nothing.

**Pass:** Maria processed the email and made no external change.  
Result: [ ] Pass  [ ] Fail

## 7. Test Calendar read access

1. In the connected Google Calendar, create an event for tomorrow.
2. Use title **Maria Calendar Connection Test** and a harmless description.
3. Wait for the hourly Meeting Preparation workflow.
4. Open **Maria Assistant → Meetings**.
5. Confirm the title, date, time, timezone, and details are correct.
6. Confirm Maria did not create or modify a Google Calendar event.

**Pass:** Maria synchronized the event and made no external change.  
Result: [ ] Pass  [ ] Fail

## 8. Check workflow health

1. Open **Maria Assistant → Workflow Runs**.
2. Find the recent **Email Triage** and **Meeting Preparation** runs.
3. Confirm both completed successfully.
4. If one failed, open it and record the error without copying tokens or confidential content.
5. If no run appears, ask the server administrator to check the scheduler and queue workers.

Useful server diagnostics:

```bash
php artisan schedule:list
php artisan horizon:status
```

**Pass:** Both workflows have a successful recent run.  
Result: [ ] Pass  [ ] Fail

## 9. Read-only acceptance

Read-only verification passes only when every box is checked:

- [ ] Correct Google account is active.
- [ ] Test email appears in Email Triage.
- [ ] Test event appears in Meetings.
- [ ] Both workflow runs succeeded.
- [ ] Maria sent no email.
- [ ] Maria created or modified no calendar event.

Decision: [ ] Accepted  [ ] Not accepted

Stop here unless read-only acceptance passes and your organization authorizes external writes.

## 10. Optional: enable approved writes

1. Open **Maria Assistant → Connections**.
2. Click **Enable approved writes**.
3. Continue only if the organization has authorized it.
4. Select the same Google account.
5. Grant Gmail send and Calendar event permissions.
6. Confirm the connection remains active.
7. Open **Maria Assistant → Maria Settings** and turn on **Allow approved external actions**.
8. As an administrator, open **Maria Assistant → External Action Control** and enable global external actions.

Write access still requires a separate, current, exact-content approval. Changing a recipient, message, attachment, event detail, or date invalidates approval.

**Pass:** Required write scopes and both safety switches are enabled.  
Result: [ ] Pass  [ ] Fail  [ ] Not applicable

## 11. Optional: test one approved email

1. Prepare a harmless email addressed only to an account you control.
2. Submit it to **Approvals**.
3. Verify the exact recipient, subject, body, and attachments.
4. Approve and execute it once.
5. Confirm exactly one message appears in Gmail **Sent** and reaches the test inbox.
6. Confirm the assistant action completed and no reconciliation is pending.

If the outcome is uncertain, use **Action Reconciliation** and check Gmail directly. Do not retry blindly.

**Pass:** Exactly one email was sent with exactly the approved content.  
Result: [ ] Pass  [ ] Fail  [ ] Not applicable

## 12. Optional: test one approved calendar event

1. Prepare a harmless event on a test calendar and submit it to **Approvals**.
2. Verify title, start/end, timezone, calendar, attendees, location, and description.
3. Approve and execute it once.
4. Confirm exactly one matching event appears in Google Calendar.
5. Confirm the assistant action completed and no reconciliation is pending.

If the outcome is uncertain, use **Action Reconciliation** and check Calendar directly. Do not retry blindly.

**Pass:** Exactly one event was created with the approved details.  
Result: [ ] Pass  [ ] Fail  [ ] Not applicable

## Troubleshooting

| Problem | What to check |
|---|---|
| `redirect_uri_mismatch` | Maria's displayed Redirect URI must exactly match the OAuth client's Authorized redirect URI. |
| Google blocks access | Check Internal audience eligibility or External test users, scopes, enabled APIs, and Workspace admin policy. |
| Connection is not active | Check Client ID/Secret belong to the same Web client, HTTPS, redirect URI, and selected Cloud project. |
| Email Triage does not run | Check active profile, selected workflow, working hours, connector, scheduler, and queue workers. |
| Meeting does not appear | Check active profile, selected workflow, Calendar read scope, event calendar, scheduler, and queue workers. |
| Cannot send or create | Check write scopes, both external-action switches, and an unchanged exact-content approval. |
| Provider result is uncertain | Check Gmail Sent or Calendar, then use Action Reconciliation; do not retry blindly. |

## Sign-off

Connected user: ______________________________  
Workspace email: _____________________________  
Verified by: _________________________________  
Date: ________________________________________  

Overall result: [ ] Pass  [ ] Fail
