# Maria Operations Runbook

## Production deployment

1. Put the application in a controlled deployment window.
2. Run `git pull origin main`, `composer install --no-dev --optimize-autoloader`, and `php artisan migrate --force`.
3. Run `php artisan optimize:clear`, `php artisan optimize`, and `php artisan queue:restart`.
4. Confirm `php artisan schedule:list`, the queue supervisor, `/admin/login`, Maria Command Center, and connector health.
5. Confirm the global external-action control and each authorized profile switch before enabling writes.

## Emergency external-action stop

1. Open **Maria Assistant → External Action Control** as a super-admin.
2. Select **Emergency stop**. This blocks new Google write approvals and execution; read-only workflows continue.
3. Pause queue workers at the infrastructure layer if an active incident requires full processing isolation.
4. Inspect executing assistant actions and the Action Reconciliation queue.
5. Record a safety incident under **Quality & Corrections**.
6. Release the stop only after provider state, recipients, content hashes, and outstanding approvals are reviewed.

## Ambiguous provider result

1. Do not retry while a reconciliation is pending.
2. Check Gmail Sent or the Google Calendar event ID using the connected owner account.
3. Compare exact recipient, subject/title, timestamps, and the assistant action's content hash.
4. In **Action Reconciliation**, choose **Confirm completed** with the provider confirmation ID, or **Confirm not executed** with evidence.
5. A new attempt requires the reconciled state and, when content changes or approval expires, a new exact-content approval.

## OAuth revocation

1. Use the Connections screen to disconnect the Google account; this calls Google's revocation endpoint and removes encrypted tokens.
2. If the UI is unavailable, revoke access from the Google Account security console, then remove the connector record through an approved database operation.
3. Rotate the Google OAuth client secret if client credentials may be compromised and update production secrets without committing them.
4. Reconnect first with read scopes; grant approved-write scopes only when operationally required.

Google Client ID, encrypted Client Secret, and Redirect URI can be managed under **System Settings → Google Workspace**. Environment variables remain fallback values for recovery. Saving an empty client-secret field preserves the stored secret.

Global timezone and date/date-time display formats are managed under **System Settings → General**. Database timestamps remain normal Laravel timestamps; Maria profile timezones continue to control that owner's scheduled workflow times.

## Queue and scheduler recovery

1. Confirm the scheduler invokes `php artisan schedule:run` every minute.
2. Confirm queue workers are running and inspect failed jobs with `php artisan queue:failed`.
3. Fix the underlying connector, schema, or provider issue before retrying jobs.
4. Run `php artisan queue:restart` after deployment. Never clear action or approval ledgers to force a retry.

## Backup and restore

1. Back up MySQL, application storage, and production environment secrets using the hosting platform's encrypted backup process.
2. Test restoration in an isolated environment with external actions globally stopped and outbound network writes disabled.
3. Validate migrations, record counts, encrypted connector readability, and audit continuity.
4. Revoke and reconnect external tokens after a production restore if token exposure cannot be ruled out.

## Incident evidence

Preserve workflow run IDs, approval IDs, assistant action IDs, provider confirmations, reconciliation evidence, audit entries, and relevant timestamps. Never copy OAuth tokens, full confidential email bodies, or private contact data into incident tickets.
