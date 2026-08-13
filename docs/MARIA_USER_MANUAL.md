# Maria Assistant User Manual

Version: 1.0  
Last updated: 13 August 2026

## 1. Purpose of this manual

This manual explains how an administrator can enable and configure Maria Assistant, how an authorized user can connect Google Workspace, and how users should operate Maria safely each day.

Maria is a private executive-assistant workspace inside the application. It can organize projects and tasks, prepare briefs, triage email, prepare meetings, monitor deadlines, maintain contacts and verified claims, and prepare draft content. Maria does not treat a draft as sent or published. External actions require the correct Google permission, enabled safety switches, and a separate exact-content approval.

## 2. Before you begin

Ask the system administrator to confirm the following infrastructure is running:

- The application is deployed and migrations are current.
- MySQL is available.
- Redis and the Laravel queue worker/Horizon are running.
- The Laravel scheduler runs every minute.
- HTTPS works on the production domain.
- The AI provider configuration is valid.
- A Google Cloud OAuth client is available if Gmail, Calendar, or Drive will be used.

Normal users do not need server or database access.

## 3. Configuration overview

Complete the setup in this order:

1. Enable Maria globally.
2. Set the application timezone and date formats.
3. Grant the user's role access to Maria.
4. Create or edit the user's Maria Settings profile.
5. Configure Google OAuth credentials, if Google Workspace is required.
6. Connect the user's Google account.
7. Optionally enable approved Google writes.
8. Add and verify private channel identities, if private messaging access is required.
9. Enter projects, tasks, contacts, claims, books, or other source records.
10. Confirm the queue, scheduler, Command Center, and workflow results.

Do not skip the role-permission or Maria-profile steps. Enabling Maria globally does not automatically authorize every user.

## 4. Administrator setup

### 4.1 Enable Maria Assistant globally

1. Sign in using a super-admin or administrator account.
2. Open **System Settings**.
3. Select the **General** tab.
4. Turn on **Enable Maria Assistant**.
5. Save the settings.
6. Refresh the page.
7. Confirm that the **Maria Assistant** navigation group is visible.

When this switch is off, Maria menus, pages, private assistant routing, connectors, and scheduled Maria workflows are unavailable. Existing data and role assignments are retained; they become available again after Maria is re-enabled.

### 4.2 Configure timezone and date formats

In **System Settings → General**, configure:

- **Application timezone:** the general timezone used by the application.
- **Date format:** how calendar-only dates appear, for example `d/m/Y` for `31/12/2026`.
- **Date & time format:** how timestamps appear, for example `d/m/Y H:i` for `31/12/2026 14:30`.

Save the settings and refresh the browser. Maria profile timezones, described later, control each owner's automated workflow times.

Calendar dates such as task due dates and project deadlines do not show a time. Real moments such as meetings, approvals, sync history, and audit records show both date and time.

### 4.3 Grant role permission

Maria uses two access gates: the global switch and the user's role permission.

1. Ensure **Enable Maria Assistant** is on.
2. Open the role/permission management screen.
3. Edit the role assigned to the intended user.
4. Enable **Access Maria Assistant**.
5. Grant only the Maria resource and page permissions needed by that role.
6. Save the role.
7. Confirm the user has that role under **Users**.
8. Ask the user to sign out and sign in again if the menu does not update immediately.

Super-admins retain administrative access. When Maria is globally disabled, Maria permissions are hidden from the role editor but are not deleted.

Recommended permission approach:

- Administrators: Maria access plus administrative resources, safety controls, identities, prompts, and workflow history.
- Executive user: Maria access plus their own operational resources and connections.
- Support/reviewer: only the specific read or review permissions required.
- Public or customer-service users: no Maria permission.

### 4.4 Create the user's Maria Settings profile

1. Open **Maria Assistant → Maria Settings**.
2. Select an existing profile or choose **Create**.
3. For an administrator-created profile, select the correct user.
4. Complete the fields below.

#### Profile fields

- **Timezone:** the user's local timezone. This controls the local execution time of briefs and scheduled workflows.
- **Language:** the user's preferred working language.
- **Working hours start/end:** email triage runs only within this window.
- **Morning brief at:** local time for the Morning Command Brief.
- **Evening review at:** local time for the Evening Review.
- **Weekly production day/time:** when eligible All Catholic Media weekly plans are processed.
- **Enabled workflows:** select only the workflows the user wants to run.
- **Voice preferences:** describe preferred tone, brevity, priorities, naming conventions, or formatting.
- **Active:** must be on for scheduled work.
- **Allow approved external actions:** per-user emergency switch for approved Google writes.

Save the profile. Use a valid timezone such as `Europe/Berlin`, not a manually typed UTC offset.

#### Workflow choices

- **Morning Brief:** daily command summary at the configured morning time.
- **Evening Review:** daily closeout at the configured evening time.
- **Email Triage:** Gmail review every 30 minutes during working hours.
- **Meeting Preparation:** hourly check for upcoming meetings.
- **Deadline Monitor:** hourly monitoring of due dates and waiting items.
- **Daily Five Relationships:** weekday relationship recommendations, 30 minutes after the morning brief time.
- **Weekly Book Portfolio Review:** Monday, one hour after the morning brief time.
- **Thursday Agverse Opportunity Review:** Thursday, 90 minutes after the morning brief time.
- **All Catholic Media Weekly Production:** configured weekday and time.
- **Weekly Maria Quality Report:** Monday, two hours after the morning brief time.

The profile must be active and Maria must be globally enabled for schedules to dispatch.

## 5. Google Workspace configuration

This section has two parts: an administrator registers the application in Google Cloud, then each user connects their own Google account.

### 5.1 Create or verify the Google Cloud OAuth client

In Google Cloud Console:

1. Select or create the correct Google Cloud project.
2. Enable the Gmail API, Google Calendar API, and Google Drive API.
3. Configure the OAuth consent screen.
4. Add the users or organization allowed to authorize the application.
5. Create an OAuth client of type **Web application**.
6. Keep the Google Cloud page open while completing the application settings below.

Google may require verification before external production users can grant sensitive scopes. This is controlled by Google, not by Maria.

### 5.2 Enter Google credentials in the application

1. Open **System Settings → Google Workspace**.
2. Enter the Google OAuth **Client ID**.
3. Enter the **Client Secret**.
4. Copy the application's **Redirect URI** exactly as displayed.
5. Add that exact URI to **Authorized redirect URIs** in the Google Cloud OAuth client.
6. Save Google Cloud configuration.
7. Save the application settings.

Important rules:

- The redirect URI must match exactly, including `https`, domain, path, and trailing slash behavior.
- The saved client secret is encrypted and is not displayed again.
- Leaving the client-secret field empty later preserves the stored secret.
- Environment variables remain fallback values for recovery.
- Never place the client secret in a manual, screenshot, ticket, or source-control commit.

### 5.3 Connect a user's Google account with read access

The user must perform this step while signed in to their own application account.

1. Open **Maria Assistant → Connections**.
2. Select **Connect Google Workspace**.
3. Sign in to the correct Google account.
4. Review Google's consent screen carefully.
5. Approve the requested read permissions.
6. Return to the application.
7. Confirm the connection row shows:
   - provider `google`;
   - the correct email address;
   - status `active`;
   - a permission count;
   - no current error.

Read access supports Gmail triage, Calendar synchronization and meeting preparation, and Drive lookup. It does not authorize Maria to send email or create calendar events.

### 5.4 Optionally enable approved Google writes

Only do this when the organization has approved Gmail sending or Calendar creation.

1. Open **Maria Assistant → Connections**.
2. Select **Enable approved writes**.
3. Read the warning and confirm.
4. Complete Google authorization for the additional scopes.
5. Return to Connections and confirm the account remains active.
6. In **Maria Assistant → Maria Settings**, turn on **Allow approved external actions** for the user.
7. As an administrator, open **Maria Assistant → External Action Control** and confirm global external actions are enabled.

These permissions do not permit autonomous writes. Each external action still needs a separate approval for the exact recipients, content, attachments, and event details. If those details change or the approval expires, a new approval is required.

## 6. Private messaging identity setup

Use this only when Maria should be accessible from a supported private messaging channel.

1. Sign in as an administrator.
2. Open **Maria Assistant → Channel Identities**.
3. Create an identity for the intended internal user.
4. Select the channel/provider and enter the provider's exact identity value.
5. Mark the identity active.
6. Set **Verified at** only after independently confirming ownership of the channel account.
7. Save the record.
8. Send a harmless test request from that exact account.
9. Confirm it enters the private Maria flow, not the public customer-service flow.

Never map an unverified or shared public identity to a private Maria user. Disabling Maria globally also disables private Maria routing.

## 7. Prepare Maria's source data

Maria's output quality depends on current, structured source records. Begin with a small, accurate dataset.

### 7.1 Projects

Open **Maria Assistant → Projects** and create each active project with:

- one primary domain;
- desired outcome;
- stage and priority;
- owner;
- next action and next-action date;
- deadline, when applicable;
- status, blocker, and confidentiality.

Every active project should have an owner, next action, and date.

### 7.2 Tasks

Open **Maria Assistant → Tasks** and enter:

- a clear task description;
- owner and source;
- due date and optional follow-up date;
- status;
- project link and supporting evidence when available.

A task due today remains current for the whole local calendar day and becomes overdue the following day.

### 7.3 Contacts and relationships

Open **Contacts** and maintain verified name, email, organization, role, relationship tier/stage, source, warm path, next action, and follow-up date. Avoid duplicate contacts and do not invent missing details.

Daily Five recommendations are drafts for human review. Maria does not automate LinkedIn connections, messages, comments, likes, or scraping.

### 7.4 Claims Registry

Before content workflows use factual claims:

1. Open **Claims Registry**.
2. Enter the exact claim text and subject.
3. Add a reliable evidence URL.
4. Set verification and recheck dates.
5. Select the brands permitted to use the claim.
6. Set the status to **Verified** only after human verification.

A claim is usable through its entire recheck date. Unverified, expired, rejected, differently worded, or wrong-brand claims are blocked rather than guessed.

### 7.5 Books, Agverse, and All Catholic Media

- Enter exact book titles, credits, editions, milestones, owners, blockers, and publication targets before enabling the Book Portfolio workflow.
- Enter verified facts separately from hypotheses, scoring inputs, next step, owner, and date before enabling Agverse reviews.
- Create the weekly ACM production-plan record, sources, owner, theme, claims, assets, and approval deadline before its scheduled production time.

## 8. Daily use

### 8.1 Start at the Command Center

Open **Maria Assistant → Command Center**. Review:

- active projects;
- open and due tasks;
- pending approvals;
- recent workflow failures;
- Daily Five pending recommendations;
- latest Morning Brief and Evening Review;
- latest Book, Agverse, and Quality reports.

Resolve stale source data before relying on a generated brief.

### 8.2 Review email triage

Open **Email Triage** to review classifications, summaries, sensitivity, commitments, follow-up dates, provider links, and draft replies.

Maria's reply text is a draft. Check recipients, facts, attachments, tone, confidentiality, and brand before requesting or granting approval. A draft displayed in Maria has not been sent.

### 8.3 Review meetings

Open **Meetings** to inspect synchronized Calendar events and preparation briefs. Meeting times use the user's configured timezone.

After a meeting, add or supply reliable notes for closeout. Review extracted decisions, tasks, owners, dates, and the thank-you draft. Confirm ambiguous commitments manually.

### 8.4 Work with approvals

Open **Approvals** and review the full action preview:

- exact action type;
- recipient or calendar participants;
- exact content or event details;
- attachments;
- risk and expiration time.

Approve only if every detail is correct. Reject anything unclear. Silence is never approval. Editing approved content, recipients, dates, attachments, or channel invalidates the approval.

### 8.5 Review alerts and workflow history

- Use **Alerts** for overdue deadlines and waiting-item follow-ups. Acknowledge after reviewing; resolve the underlying task or project record.
- Use **Workflow Runs** to see whether automation completed, failed, or is still processing.
- Use **Action Reconciliation** when a provider result is uncertain. Do not blindly retry an ambiguous Gmail or Calendar action.
- Use **Quality & Corrections** to record incorrect output, recurring corrections, or safety incidents.

## 9. Safety controls

External writes require all of the following:

1. Maria is globally enabled.
2. The user has Maria permission.
3. The user profile is active.
4. The Google connection has the required write scope.
5. **Allow approved external actions** is enabled for that profile.
6. Global External Action Control is enabled.
7. A current exact-content approval exists.
8. No unresolved reconciliation prevents retry.

### Emergency stop

If an external-action incident is suspected:

1. Open **Maria Assistant → External Action Control** as an administrator.
2. Select **Emergency stop**.
3. Review executing actions and pending reconciliations.
4. Check the provider directly, such as Gmail Sent or Google Calendar.
5. Record a quality/safety incident.
6. Re-enable actions only after the exact provider state and outstanding approvals are confirmed.

Read-only workflows can continue while external writes are stopped.

## 10. Setup verification checklist

After configuration, confirm each item:

- [ ] Maria is enabled in System Settings.
- [ ] Application timezone and date formats are correct.
- [ ] The user has **Access Maria Assistant** and required resource permissions.
- [ ] The user can see the Maria Assistant menu after signing in.
- [ ] The user's Maria Settings profile is active.
- [ ] Profile timezone and working hours are correct.
- [ ] Morning and evening times are correct.
- [ ] Only intended workflows are selected.
- [ ] Google OAuth settings are saved, if required.
- [ ] Google redirect URI exactly matches Google Cloud.
- [ ] The correct Google account appears as active in Connections.
- [ ] Read-only Gmail, Calendar, and Drive behavior has been tested.
- [ ] Write scopes are enabled only if approved.
- [ ] Global and per-profile write controls are set intentionally.
- [ ] Projects, tasks, contacts, and claims contain accurate sample records.
- [ ] `php artisan schedule:list` shows Maria schedules.
- [ ] Horizon/queue workers are running.
- [ ] A scheduled workflow produces a Workflow Run and expected output.
- [ ] No unexpected workflow failures or connection errors remain.

## 11. Troubleshooting

### Maria menu is missing

Check, in order:

1. **System Settings → General → Enable Maria Assistant** is on.
2. The user's role has **Access Maria Assistant**.
3. The role has permission for the required Maria page/resource.
4. The user has the expected role.
5. Sign out and sign back in.

### Google reports a redirect URI mismatch

Copy the Redirect URI again from **System Settings → Google Workspace**. Ensure the Google Cloud authorized redirect URI matches character for character and uses the production HTTPS domain.

### Google connection is inactive or shows an error

Confirm the correct APIs are enabled, the OAuth client and consent screen are valid, the user is allowed by the consent configuration, and the OAuth secret has not been rotated. Reconnect only after correcting the underlying cause.

### Briefs or scheduled workflows do not run

Check:

1. Maria is globally enabled.
2. The profile is active.
3. The workflow is selected in Enabled Workflows.
4. The profile timezone and scheduled time are correct.
5. The server scheduler runs every minute.
6. Horizon/queue workers are running.
7. Failed jobs and Workflow Runs show no unresolved error.

### Email triage does not run

Confirm Email Triage is selected, Google is active, the profile is active, and the current local time is inside the configured working-hours window. Triage is scheduled every 30 minutes, not continuously.

### A task due today appears in the due list

This is expected. It is due today but is not overdue until the next local calendar day.

### Maria cannot send email or create an event

Review all eight safety requirements in Section 9. The most common causes are read-only Google scopes, disabled per-profile external actions, the global emergency stop, or missing/expired exact-content approval.

### A write may have completed but Maria shows an uncertain result

Do not retry. Check the provider directly, then use **Action Reconciliation** to confirm completed or confirm not executed with evidence.

### Dates or times look wrong

Check both the global application timezone/date formats and the user's Maria Settings timezone. Calendar-only fields use the configured date format; meetings, approvals, and audit timestamps include time.

## 12. Good operating practices

- Start with read-only Google access and a small set of workflows.
- Keep project owners, next actions, deadlines, and blockers current.
- Verify claims and evidence before generating public content.
- Treat all drafts as unsent until the provider confirms completion.
- Use the approval queue for consequential actions, never informal chat consent.
- Review Workflow Runs and connection errors regularly.
- Record recurring corrections so quality reports can identify patterns.
- Keep personal, All Catholic Media, Agverse, and book brands separate.
- Never place OAuth tokens, passwords, private contact details, or confidential email bodies in support tickets.

## 13. Support information to collect

When requesting technical support, provide:

- the affected user's email address (never their password);
- the approximate local date and time;
- the page or workflow name;
- the Workflow Run ID, Approval ID, or reconciliation ID when available;
- the visible error message;
- whether the issue affects read-only work or an external write;
- a screenshot with confidential data and tokens removed.

Never provide OAuth access tokens, refresh tokens, the Google client secret, database passwords, or unrestricted confidential content.
