# Maria Operations Runbook

## Production deployment

1. Put the application in a controlled deployment window.
2. Run `git pull origin main`, `composer install --no-dev --optimize-autoloader`, and `php artisan migrate --force` (recent migrations add `maria_eval_runs`, `assistant_memories`, and `assistant_conversations.summary`).
3. Run `php artisan optimize:clear`, `php artisan optimize`, and `php artisan queue:restart`.
4. If new Maria pages/resources shipped, regenerate permissions (`php artisan shield:generate`) and grant the new **Cost & Quality** and **Remembered Facts** entries to the roles that need them.
5. Confirm `php artisan schedule:list`, the queue supervisor, `/admin/login`, Maria Command Center, the **Cost & Quality** page, and connector health.
6. Run `php artisan maria:eval` (lists cases) and, after a prompt/model/tool change, `php artisan maria:eval --live` — confirm the pass rate is acceptable before relying on the deploy.
7. Confirm the global external-action control and each authorized profile switch before enabling writes. The global switch **fails safe**: until it is explicitly on, every external action is treated as stopped.

## AI configuration to review after deploy

Under **System Settings → AI** and **→ General**:

- **Fallback model (Maria)** — optional secondary model used once if the primary is unreachable / 5xx / unknown-model 404.
- **Requests per minute**, **Daily Claude Call Cap**, **Maria Daily Claude Call Cap** — the shared per-minute burst limit plus two daily ceilings (both 0 = off). When a cap is hit, Maria chat asks the user to retry and scheduled workflows defer; customer WhatsApp traffic is unaffected.
- **Input/Output price per million tokens** — feeds the cost figures on the Cost & Quality page only.
- **Email owners a digest when a brief is generated** — sends each owner a short brief summary email (off by default).
- **Email triage — excluded senders / labels** — mail Maria must never read; also a best-effort secret scrub runs on any email/notes text before it reaches the model.
- **Require a passing eval before activating a Maria system prompt** + **Minimum eval pass rate** — the prompt-activation gate.
- Workflow jobs that exhaust their retries email the **staff notification email**; a single transient rate-limit failure is logged, not emailed.

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

Maria has two access gates: **System Settings → General → Enable Maria Assistant** must be enabled, and non-super-admin users need **Access Maria Assistant** in the role permission screen. Disabling the global switch hides and denies Maria pages, connector routes, private identity routing, and scheduled dispatches.

The role editor follows the global switch. When Maria is disabled, Maria resource/page permissions and the custom access permission are hidden. Existing role assignments are retained and become visible again when Maria is re-enabled.

## Queue and scheduler recovery

1. Confirm the scheduler invokes `php artisan schedule:run` every minute.
2. Confirm queue workers are running and inspect failed jobs with `php artisan queue:failed`. Maria's Claude-using workflow jobs retry with a 60s / 5min / 15min back-off; an occasional single failed Workflow Run that later completes is expected under shared-budget load.
3. Fix the underlying connector, schema, or provider issue before retrying jobs. A Workflow Run failing with `failed schema validation after a repair attempt` means the model could not produce valid output twice — treat it as a prompt/schema problem.
4. Run `php artisan queue:restart` after deployment. Never clear action or approval ledgers to force a retry.

## Cost and quality monitoring

Use **Maria Assistant → Cost & Quality** (7 / 30 / 90-day windows) for estimated spend, per-workflow run counts and success rate, estimated time saved, chat token spend, and recent workflow failures. Investigate any workflow whose success rate drops below ~90%. The Acceptance Dashboard remains the source for owner-verified time savings.

## Conversation memory

`assistant_memories` holds durable per-owner facts a user asked Maria to remember; review or clear them under **Maria Assistant → Remembered Facts**. Long chats fold older turns into a rolling summary on `assistant_conversations.summary`. Neither is used by scheduled workflows and both are owner-scoped.

## Backup and restore

1. Back up MySQL, application storage, and production environment secrets using the hosting platform's encrypted backup process.
2. Test restoration in an isolated environment with external actions globally stopped and outbound network writes disabled.
3. Validate migrations, record counts, encrypted connector readability, and audit continuity.
4. Revoke and reconnect external tokens after a production restore if token exposure cannot be ruled out.

## Incident evidence

Preserve workflow run IDs, approval IDs, assistant action IDs, provider confirmations, reconciliation evidence, audit entries, and relevant timestamps. Never copy OAuth tokens, full confidential email bodies, or private contact data into incident tickets.
