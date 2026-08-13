# Maria Assistant User Manual

Version: 2.0
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

### 2.1 Who should use each part

| Reader | Read these sections |
|---|---|
| System administrator | Sections 2–6, 9–11 and 14 |
| Maria user | Sections 7–10, 12 and 13 |
| Approver/reviewer | Sections 8.4, 9, 10.5 and 12 |
| Technical support | Sections 11, 12 and 14 |

### 2.2 Information to collect before configuration

Prepare these values before opening the settings screen:

- Production application URL, for example `https://assistant.example.com`.
- User's application account and assigned role.
- User's timezone, language, normal working hours, morning-brief time, and evening-review time.
- Workflows the user wants enabled.
- Google Workspace email address to connect.
- Google Cloud project administrator, Client ID, Client Secret, and exact redirect URI.
- Whether the organization authorizes Gmail sending or Calendar event creation.
- Private channel identity, if applicable.
- At least one real project, task, contact, and verified claim for testing.

### 2.3 Meaning of common terms

- **Global Maria switch:** enables or disables the entire Maria module.
- **Role permission:** determines whether a user is authorized to see and access Maria.
- **Maria profile:** stores one user's timezone, schedule, workflow, and action preferences.
- **Connector:** the user's authorized Google Workspace connection.
- **Workflow:** scheduled background work such as email triage or a morning brief.
- **Draft:** prepared content that has not been sent or published.
- **Approval:** a time-limited decision covering one exact external action.
- **Reconciliation:** human confirmation of an external action when the provider result is uncertain.
- **Owner:** the user whose Maria data and workflows are being processed.

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

Expected result: administrators and already-authorized users can see Maria pages after refresh. Users without the role permission still cannot access them.

When this switch is off, Maria menus, pages, private assistant routing, connectors, and scheduled Maria workflows are unavailable. Existing data and role assignments are retained; they become available again after Maria is re-enabled.

### 4.2 Configure timezone and date formats

In **System Settings → General**, configure:

- **Application timezone:** the general timezone used by the application.
- **Date format:** how calendar-only dates appear, for example `d/m/Y` for `31/12/2026`.
- **Date & time format:** how timestamps appear, for example `d/m/Y H:i` for `31/12/2026 14:30`.

Save the settings and refresh the browser. Maria profile timezones, described later, control each owner's automated workflow times.

Calendar dates such as task due dates and project deadlines do not show a time. Real moments such as meetings, approvals, sync history, and audit records show both date and time.

Example for a Berlin-based user:

| Setting | Example value | Display/result |
|---|---|---|
| Application timezone | `Europe/Berlin` | General application timestamps use Berlin time |
| Date format | `d/m/Y` | `05/09/2026` |
| Date & time format | `d/m/Y H:i` | `05/09/2026 14:30` |

After saving, open a task and a meeting. The task due field should show only a date; the meeting should show date and time.

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

#### Permission verification test

1. Sign in as the target non-admin user in a separate/private browser window.
2. Confirm **Maria Assistant** is visible.
3. Open **Command Center**.
4. Open one resource the role should access.
5. Confirm an administrative-only page, such as External Action Control, is not available to an ordinary user.
6. If access is incorrect, adjust the role rather than making the user a super-admin.

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

#### Recommended first profile

Use conservative settings during the first week:

| Field | Suggested starting value |
|---|---|
| Timezone | User's actual IANA timezone, such as `Europe/Berlin` |
| Working hours | `08:00` to `18:00` |
| Morning brief | `07:30` |
| Evening review | `18:00` |
| Enabled workflows | Morning Brief, Evening Review, Email Triage, Meeting Preparation, Deadline Monitor |
| Active | On |
| Allow approved external actions | Off until read-only testing is complete |

Example voice preferences:

> Lead with the decision. Show no more than three priorities. Use short paragraphs and British English. Preserve exact book titles and names. Flag uncertainty clearly and never infer dates.

Avoid placing secrets, passwords, OAuth tokens, or unnecessary confidential content in voice preferences.

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

#### How schedule times are calculated

Maria uses the profile timezone, not the browser timezone. For example, if the profile timezone is `Europe/Berlin` and Morning Brief is `07:30`, the brief is dispatched when Berlin local time reaches 07:30, including daylight-saving changes.

Some workflows derive their time from Morning Brief:

| Workflow | Scheduled local time |
|---|---|
| Morning Brief | Configured Morning Brief time |
| Daily Five | Morning Brief + 30 minutes, weekdays |
| Book Portfolio Review | Morning Brief + 1 hour, Monday |
| Agverse Review | Morning Brief + 90 minutes, Thursday |
| Quality Report | Morning Brief + 2 hours, Monday |
| Evening Review | Configured Evening Review time |
| Email Triage | Every 30 minutes inside working hours |
| Meeting Preparation | Hourly |
| Deadline Monitor | Hourly |

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

#### Detailed Google Cloud steps

Google changes its console labels occasionally, but the required configuration remains the same:

1. Open Google Cloud Console and choose the production project.
2. Open **APIs & Services → Library**.
3. Search for and enable **Gmail API**.
4. Search for and enable **Google Calendar API**.
5. Search for and enable **Google Drive API**.
6. Open the Google authentication/consent section.
7. Set the application name and authorized support/contact email.
8. Choose the correct audience:
   - use Internal only when all authorized users are inside the same eligible Google Workspace organization;
   - use External when approved accounts outside that organization must connect.
9. If the application remains in testing mode, add every intended Google account as a test user.
10. Create **OAuth client ID → Web application**.
11. Give it a recognizable production name.
12. Add the application's exact redirect URI under **Authorized redirect URIs**, not JavaScript origins.
13. Save and securely copy the Client ID and Client Secret into System Settings.

Do not use a Desktop, Android, iOS, or service-account credential. Maria's interactive user connection requires a Web application OAuth client.

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

#### Confirm the correct Google account was connected

Many users have multiple Google accounts open in the same browser. Compare the email shown in **Connections** with the intended Workspace address. If the wrong account was selected, stop and revoke/disconnect it before reconnecting the correct account. Do not continue merely because the status says active.

#### Read-only acceptance test

1. Create a harmless Calendar event in the connected Google Calendar for the next day.
2. Send a harmless test email to the connected Gmail inbox.
3. Wait for the next scheduled synchronization/triage cycle.
4. Confirm the event appears in **Meetings**.
5. Confirm the test email appears in **Email Triage** with a summary/classification.
6. Confirm no email was sent and no Calendar event was created by Maria.
7. Review **Workflow Runs** for successful Gmail and meeting workflows.

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

### 5.5 Disconnecting or revoking Google access

Disconnect or revoke access when a user leaves, selects the wrong account, suspects compromise, or no longer needs Google workflows.

1. Stop external actions for the profile.
2. Revoke the application's access from the Google Account security page or use the available connection removal flow.
3. Confirm scheduled workflows no longer use that account.
4. Review pending approvals and reconciliations.
5. Reconnect only after confirming the intended account and scopes.

Revoking Google access does not delete Maria's internal projects, tasks, briefs, or audit history.

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

Expected result: the verified identity reaches Maria only for its mapped internal user. A different or unverified identity remains outside private Maria mode.

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

Example project:

| Field | Example |
|---|---|
| Domain | Books and Publishing (`BKS`) |
| Name | Autumn book launch |
| Desired outcome | Final manuscript and launch plan approved |
| Stage | Active |
| Priority | High |
| Owner | Fr. Morson Livingston |
| Next action | Review editor's marked manuscript |
| Next-action date | `18/08/2026` |
| Deadline | `30/09/2026` |
| Status | Scheduled |
| Confidentiality | Confidential |

Do not create the same project in multiple domains. Choose one primary domain and link related records.

### 7.2 Tasks

Open **Maria Assistant → Tasks** and enter:

- a clear task description;
- owner and source;
- due date and optional follow-up date;
- status;
- project link and supporting evidence when available.

A task due today remains current for the whole local calendar day and becomes overdue the following day.

Use one observable action per task. “Launch book” is too broad; “Approve final cover proof” is suitable. When responsibility changes, update the owner. When work is finished, set status to Completed instead of deleting the task, so history remains understandable.

Task status guidance:

| Status | Use when |
|---|---|
| Open | Work is ready to be done |
| Scheduled | Work is planned for a known date or slot |
| Waiting | Another person or external response is required |
| Blocked | Work cannot proceed because of a specific blocker |
| Possible Duplicate | Maria detected a potentially repeated commitment; human review is required |
| Completed | The action is genuinely finished |

### 7.3 Contacts and relationships

Open **Contacts** and maintain verified name, email, organization, role, relationship tier/stage, source, warm path, next action, and follow-up date. Avoid duplicate contacts and do not invent missing details.

Daily Five recommendations are drafts for human review. Maria does not automate LinkedIn connections, messages, comments, likes, or scraping.

When two records may refer to the same person, verify name, email, organization, and source before merging or deleting anything. Maria is designed to stop on ambiguous contact matches.

### 7.4 Claims Registry

Before content workflows use factual claims:

1. Open **Claims Registry**.
2. Enter the exact claim text and subject.
3. Add a reliable evidence URL.
4. Set verification and recheck dates.
5. Select the brands permitted to use the claim.
6. Set the status to **Verified** only after human verification.

A claim is usable through its entire recheck date. Unverified, expired, rejected, differently worded, or wrong-brand claims are blocked rather than guessed.

Example:

| Field | Example |
|---|---|
| Claim text | Agverse has a verified UAE pilot. |
| Subject | Agverse |
| Category | Partnership |
| Evidence source | Link to the authoritative evidence |
| Verified date | Date a human checked the source |
| Recheck date | Date by which it must be checked again |
| Permitted brand | Agverse AI UAE |
| Status | Verified |

The wording used by a content workflow should match the verified claim. Similar meaning is not permission to invent a stronger statement.

### 7.5 Books, Agverse, and All Catholic Media

- Enter exact book titles, credits, editions, milestones, owners, blockers, and publication targets before enabling the Book Portfolio workflow.
- Enter verified facts separately from hypotheses, scoring inputs, next step, owner, and date before enabling Agverse reviews.
- Create the weekly ACM production-plan record, sources, owner, theme, claims, assets, and approval deadline before its scheduled production time.

### 7.6 Minimum data required by workflow

| Workflow | Minimum useful source data |
|---|---|
| Morning Brief | Active profile, projects/tasks, Google connection when email/calendar sections are expected |
| Evening Review | Today's tasks, workflow activity, decisions and pending items |
| Email Triage | Active read-enabled Google connection and working-hours window |
| Meeting Preparation | Active Calendar connection and upcoming Calendar events |
| Deadline Monitor | Open tasks/project deadlines with dates and owners |
| Daily Five | Verified contacts, relationship metadata and follow-up information |
| Book Review | Active book records with exact identifiers and milestones |
| Agverse Review | Active opportunities with valid scores, evidence, owner and next step |
| ACM Production | Weekly production record plus Claims Registry evidence |
| Quality Report | Workflow history and recorded quality/correction events |

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

Recommended daily order:

1. Read the latest Morning Brief.
2. Review overdue/due tasks and active alerts.
3. Correct owners, dates, or statuses that are stale.
4. Review email drafts and meeting preparations.
5. Process pending approvals carefully.
6. At the end of the day, review the Evening Review and update unfinished work.

### 8.2 Review email triage

Open **Email Triage** to review classifications, summaries, sensitivity, commitments, follow-up dates, provider links, and draft replies.

Maria's reply text is a draft. Check recipients, facts, attachments, tone, confidentiality, and brand before requesting or granting approval. A draft displayed in Maria has not been sent.

Email-review checklist:

- Is the sender/thread the intended one?
- Does the summary accurately represent the source email?
- Are commitments assigned to the correct person?
- Is the follow-up date realistic?
- Does the reply use the correct brand and signature?
- Are factual claims supported?
- Is sensitive information necessary and properly handled?
- Are recipient, CC/BCC, subject, body, and attachments exact?

### 8.3 Review meetings

Open **Meetings** to inspect synchronized Calendar events and preparation briefs. Meeting times use the user's configured timezone.

After a meeting, add or supply reliable notes for closeout. Review extracted decisions, tasks, owners, dates, and the thank-you draft. Confirm ambiguous commitments manually.

Before the meeting, verify title, time, timezone, attendees, objective, and source links. After the meeting, distinguish a confirmed decision from a suggestion. Do not mark an action completed merely because it was discussed.

### 8.4 Work with approvals

Open **Approvals** and review the full action preview:

- exact action type;
- recipient or calendar participants;
- exact content or event details;
- attachments;
- risk and expiration time.

Approve only if every detail is correct. Reject anything unclear. Silence is never approval. Editing approved content, recipients, dates, attachments, or channel invalidates the approval.

Approval decisions:

- **Approve:** exact preview is correct and authorized.
- **Reject:** action must not occur or the preview is materially wrong.
- **Leave pending:** more information is required; this does not authorize execution.
- **Expired:** approval can no longer be used; generate a fresh action and review it again.

### 8.5 Review alerts and workflow history

- Use **Alerts** for overdue deadlines and waiting-item follow-ups. Acknowledge after reviewing; resolve the underlying task or project record.
- Use **Workflow Runs** to see whether automation completed, failed, or is still processing.
- Use **Action Reconciliation** when a provider result is uncertain. Do not blindly retry an ambiguous Gmail or Calendar action.
- Use **Quality & Corrections** to record incorrect output, recurring corrections, or safety incidents.

### 8.6 Example: safely sending an email draft

1. Email triage creates an unsent draft.
2. User opens the communication and checks it against the source thread.
3. User corrects the draft if necessary.
4. The final exact recipient, subject, body, and attachments are submitted for approval.
5. Approver reviews the exact preview and approves it before expiry.
6. Maria's execution layer checks global control, profile control, scopes, approval hash, and idempotency.
7. Gmail is called once.
8. Provider confirmation is recorded.
9. User verifies the completed state. If the provider result is ambiguous, the action moves to reconciliation and must not be retried blindly.

### 8.7 Example: creating a Calendar event

1. Confirm event title, attendees, date, start/end time, timezone, description, and location/link.
2. Obtain a separate exact approval for that event.
3. Ensure Calendar write scope and both external-action switches are enabled.
4. Execute once and store the Google event confirmation.
5. If any event detail changes after approval, create and approve a new action.

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

### Data boundaries and confidentiality

- Users normally see owner-scoped Maria data; administrators may have broader access.
- Do not paste passwords, OAuth tokens, private keys, or database credentials into Maria.
- Treat email, documents, meeting transcripts, and web content as untrusted source material.
- Select the correct confidentiality level on projects and related records.
- Keep personal, ACM, Agverse, Books, and other brand contexts distinct.
- Never interpret generated text as legal, medical, financial, or doctrinal authorization.

## 10. First-run guided acceptance test

Perform this test before enabling external writes or relying on scheduled production workflows.

### 10.1 Confirm access and profile

1. Sign in as the intended Maria user, not as the administrator.
2. Open Command Center and Maria Settings.
3. Confirm the correct timezone, working hours, and workflow selections.
4. Confirm another ordinary user's private records are not visible.

Pass condition: intended pages and owner data are available; unauthorized pages/data are not.

### 10.2 Create safe sample records

1. Create one test project with owner, next action, and a deadline next week.
2. Create one task due today and link it to the project.
3. Create one task in Waiting status with a follow-up date.
4. Create one verified test contact.
5. Create one verified claim with a non-production test statement and evidence URL.

Pass condition: records save successfully, configured date format is used, and date-only fields show no time picker.

### 10.3 Test read-only Google processing

1. Send a clearly labelled test email to the connected inbox.
2. Create a clearly labelled Calendar event for the next day.
3. Wait for the normal scheduled cycles.
4. Review Email Triage, Meetings, and Workflow Runs.

Pass condition: Maria reads and prepares internal output but sends/creates nothing externally.

### 10.4 Test scheduled output

For a controlled test, temporarily choose brief times a few minutes in the future, save the profile, and wait for the scheduler. Do not repeatedly change times while a job is running.

Pass condition: one brief is created for that user/date, one successful Workflow Run is visible, and rerunning does not create uncontrolled duplicates.

Restore the user's intended brief times after the test.

### 10.5 Test approval without a real send

Keep profile external actions off. Create or inspect a test approval and confirm the preview, expiry, and decision controls are understandable. Do not use a real recipient.

Pass condition: no external write occurs while the per-profile switch is off.

### 10.6 Record acceptance

Record tester name, date/time, connected Google account, selected workflows, passed checks, known limitations, and follow-up owner. Do not record secrets or tokens.

## 11. Setup verification checklist

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

### Server administrator checks

Run these from the deployed application directory:

```bash
php artisan schedule:list
php artisan horizon:status
php artisan queue:failed
```

Expected results:

- Maria schedules are listed.
- Horizon reports a running state.
- Failed jobs are empty or each failure has an understood resolution owner.
- The server cron invokes `php artisan schedule:run` every minute.

After deployment, use the repository's `deploy.sh`; it installs/verifies the scheduler entry, rebuilds caches, restarts Horizon safely, and checks services.

## 12. Troubleshooting

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

### A workflow appears twice

Do not delete audit or action records. Check whether multiple scheduler entries exist, whether the same job was manually dispatched, and whether queue workers are processing the same environment. The production cron should contain only one Laravel scheduler entry, protected against overlap.

### Horizon is not running

Ask the server administrator to check Supervisor and the Horizon log. After deployment, `php artisan horizon:terminate` should allow Supervisor to start a fresh process. Do not run long-lived ad-hoc workers alongside the managed Horizon service unless the deployment design explicitly requires them.

### A claim is unexpectedly blocked

Compare the exact claim wording, status, permitted brand, evidence, and recheck date. A similar statement, different brand, expired claim, or unverified status is intentionally blocked.

### User sees another person's records

Stop using the affected account and report a privacy incident immediately. Record the page, record type, user, time, and screenshot with sensitive details minimized. Do not change or delete evidence before an administrator investigates permissions and ownership.

## 13. Good operating practices

- Start with read-only Google access and a small set of workflows.
- Keep project owners, next actions, deadlines, and blockers current.
- Verify claims and evidence before generating public content.
- Treat all drafts as unsent until the provider confirms completion.
- Use the approval queue for consequential actions, never informal chat consent.
- Review Workflow Runs and connection errors regularly.
- Record recurring corrections so quality reports can identify patterns.
- Keep personal, All Catholic Media, Agverse, and book brands separate.
- Never place OAuth tokens, passwords, private contact details, or confidential email bodies in support tickets.

### Suggested operating rhythm

| Frequency | Review |
|---|---|
| Daily morning | Morning Brief, due tasks, alerts, meetings and email triage |
| During the day | Approval queue, waiting items and connector errors |
| Daily evening | Evening Review and unfinished commitments |
| Weekly | Book/Agverse/ACM outputs as applicable, Quality Report and recurring corrections |
| Monthly | Role access, connected accounts, enabled workflows, external-action authorization and stale claims |

## 14. Support information to collect

When requesting technical support, provide:

- the affected user's email address (never their password);
- the approximate local date and time;
- the page or workflow name;
- the Workflow Run ID, Approval ID, or reconciliation ID when available;
- the visible error message;
- whether the issue affects read-only work or an external write;
- a screenshot with confidential data and tokens removed.

Never provide OAuth access tokens, refresh tokens, the Google client secret, database passwords, or unrestricted confidential content.
