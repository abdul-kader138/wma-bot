# Maria Deployment Checklist

- MySQL backup completed and restore point recorded.
- Production secrets configured outside Git.
- Migrations applied successfully (including `maria_eval_runs`, `assistant_memories`, `assistant_conversations.summary`).
- `php artisan shield:generate` run; new pages/resources (Cost & Quality, Remembered Facts) granted to the intended roles.
- Application/config/routes/views optimized.
- Queue workers restarted and healthy.
- Scheduler entries visible.
- Maria dashboard, Cost & Quality page, and owner scoping smoke-tested.
- Google read connector smoke-tested.
- Global external actions stopped during verification (switch fails safe: off = stopped).
- Per-profile external actions disabled unless explicitly authorized.
- One test approval checked without executing a real external action.
- Reconciliation queue empty or reviewed.
- `php artisan maria:eval` lists cases; `--live` run recorded at an acceptable pass rate.
- AI settings reviewed: primary + fallback model, requests/min, daily caps, token prices, digest email toggle, triage exclusions, eval gate.
- Staff notification email set (workflow-failure alerts).
- Rollback owner and incident contact identified.
