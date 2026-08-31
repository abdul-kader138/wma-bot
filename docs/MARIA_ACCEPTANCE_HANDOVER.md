# Maria Acceptance and Handover

## Evidence standard

The Acceptance Dashboard uses the latest 30 days of owner-scoped records. A metric is shown as **Not measured** when the platform lacks a defensible denominator. Estimated time savings and owner-verified savings are never combined.

Use **Save 30-day snapshot** to preserve a dated, auditable report in `assistant_briefs` with its corresponding workflow run.

**Cost & Quality** (Maria Assistant menu) is the day-to-day observability surface: estimated spend, per-workflow success rate, chat token spend, and recent failures over 7 / 30 / 90 days. It complements — does not replace — the Acceptance Dashboard's verified figures.

**Behavioural evals**: `resources/evals/*.php` codifies the blueprint scenario tests (chat and structured-workflow). `php artisan maria:eval --live` records each run to `maria_eval_runs`; when the eval gate is on, a new system prompt cannot be activated without a recent passing run.

## Before client acceptance

1. Complete at least 30 operating days with production scheduler and queue monitoring.
2. Record human corrections and safety incidents consistently.
3. Review and verify actual human minutes on representative workflow runs.
4. Accept or reject Daily Five recommendations rather than leaving them pending.
5. Maintain owners, next actions, and dates on active projects.
6. Resolve every ambiguous external action through the reconciliation queue.
7. Review every failed threshold with the client and document corrective action.
8. Do not claim acceptance for metrics marked Not measured; add the required capture mechanism or verify them manually.

## Handover package

- Deployment checklist and operations runbook.
- End-user guide (`MARIA_END_USER_GUIDE.md`/`.pdf`) and administrator manual (`MARIA_USER_MANUAL.md`/`.pdf`).
- Current `plan.md` and client blueprint.
- Database migration history and schema backup.
- Google OAuth permission inventory and revocation owner.
- Prompt versions, latest `maria:eval` result, and Claims Registry review owner.
- Queue/scheduler monitoring owner.
- Latest Quality Report, Cost & Quality readout, and saved 30-day Acceptance snapshot.
- AI settings inventory (primary/fallback model, caps, prices, digest, triage exclusions, eval gate).
- Open corrections, safety incidents, reconciliations, and failed workflows.
