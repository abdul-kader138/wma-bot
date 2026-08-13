# Maria Deployment Checklist

- MySQL backup completed and restore point recorded.
- Production secrets configured outside Git.
- Migrations applied successfully.
- Application/config/routes/views optimized.
- Queue workers restarted and healthy.
- Scheduler entries visible.
- Maria dashboard and owner scoping smoke-tested.
- Google read connector smoke-tested.
- Global external actions stopped during verification.
- Per-profile external actions disabled unless explicitly authorized.
- One test approval checked without executing a real external action.
- Reconciliation queue empty or reviewed.
- Rollback owner and incident contact identified.
