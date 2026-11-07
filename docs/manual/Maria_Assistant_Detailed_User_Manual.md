# Maria Assistant

## Detailed user manual and operating guide

**Edition 3.0 · 18 October 2026 · English**

For everyday users, reviewers, administrators, and support staff.

This guide explains how to configure Maria, maintain its working records, use each Maria screen, review generated work, and operate controlled Google actions. It combines the existing operating guide with a source-code review of the current application.

**Verification boundary:** Screen definitions, action handlers, tools, scheduler logic, and existing operating documents were inspected in this repository. No production login, Google authorization, live send, deployment, or end-to-end acceptance test was performed for this document. Menu visibility depends on role, global settings, and the deployed revision. Examples are training data, not real claims or commitments.

**Read first:** Use chapter 15 for a first-session walkthrough, chapter 16 for the screen map, and chapters 17–25 for detailed procedures. Administrators should complete Part I before handing the system to users. The printed contents and PDF bookmarks provide navigation.

**How Maria works**

Source records and Google reads → scheduled or requested processing → internal drafts, briefs, tasks and recommendations → human review → separate approval and execution for supported Google writes → provider confirmation or reconciliation.

An empty dashboard usually means source records or completed workflows are missing. A successful workflow does not prove that its facts are correct, and an approval does not mean an action was executed.


# Part I — Setup and daily operation

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

Maria's reply text is a draft. Check recipients, facts, attachments, tone, confidentiality, and brand before requesting or granting approval. A draft displayed in Maria has not been sent. Saving the draft does not create an approval or execute an external action.

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

**Interface limitation:** Email Triage only edits the draft and follow-up date. It has no request-approval or send button. Steps below require an approval prepared through the application’s Google action service by an authorized technical operator. Approval alone does not send: use **Execute approved action** on the approved row. The current sender supports plain text, not file attachments.

1. Email triage creates an unsent draft.
2. User opens the communication and checks it against the source thread in Gmail.
3. User corrects the draft if necessary.
4. An authorized technical operator prepares an approval for the final exact recipient, CC/BCC, subject, plain-text body, and optional thread. File attachments are not supported by the current sender.
5. Approver reviews the exact preview and approves it before expiry.
6. Maria's execution layer checks global control, profile control, scopes, approval hash, and idempotency.
7. User selects **Execute approved action** and confirms; Maria calls Gmail. If the outcome is uncertain, follow reconciliation instead of assuming a retry is safe.
8. Provider confirmation is recorded.
9. User verifies the completed state. If the provider result is ambiguous, the action moves to reconciliation and must not be retried blindly.

### 8.7 Example: creating a Calendar event

**Interface limitation:** Meetings has no Create event button. An authorized technical operator must prepare the exact Calendar approval through the application service. Users can then review, approve, and explicitly execute it from Approvals.

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


# Part II — Detailed screen-by-screen procedures

## 15. Your first session: sign in and create useful work

### 15.1 Sign in

1. Obtain the deployed application address and your assigned account from the administrator. This repository configures the administration panel; the operating documents identify `/admin/login` as the sign-in path.
2. Open the supplied HTTPS address in your browser and enter your email and password.
3. If prompted for two-factor authentication, enter the code from your authenticator or use an available recovery code. Do not share either with support.
4. Expand **Maria Assistant** in the navigation and open **Command Center**.
5. If the group is missing, follow chapter 12 before requesting a new account. Global enablement and role access are separate requirements.
6. Confirm that the displayed records belong to your work. Administrators may see records across owners.

**Expected result:** The Command Center loads. Empty cards are normal before you create records and workflows finish.

### 15.2 Set your working preferences

1. Open **Maria Settings**, then edit your profile. If no profile exists and Create is unavailable, ask the administrator to create it for your account.
2. Set timezone, language, working hours, morning and evening times.
3. Select the workflows you need and enable **Active**.
4. Enter short voice preferences, such as “Use English; show three priorities; flag missing evidence.”
5. Leave approved external actions off during initial testing and save.
6. Reopen the profile to confirm your saved values.

### 15.3 Complete one practice project

1. Open the projects list and select its Create action.
2. Choose **Books and Publishing**, name the project “Training: cover review,” and enter the desired outcome “Approve a final cover proof.”
3. Set stage **active**, priority **Normal**, confidentiality **Internal**, and your name as owner.
4. Enter next action “Compare the two cover proofs,” a future next-action date, and a future deadline.
5. Choose status **Scheduled** and save.
6. Open Tasks, create “Review the training cover proof,” link the project, enter your owner name, choose source **User**, and set a due date and **Open** status.
7. Save, return to Command Center, and check the counts and task list.
8. After completing the practice, edit the task to **Completed**. Set the project's stage to **complete** and status to **Completed** when its outcome is achieved.

**Expected result:** Both records save, the task links to the project, and completed work no longer counts as open. Task completion does not automatically close the project.

## 16. Navigation and screen reference

The labels below are the Maria navigation labels or underlying resource names. Generated labels for projects and tasks may include “Maria.” Use your assigned sidebar permissions; unavailable buttons generally require administrator review.

| Screen | Main purpose | Normal operation |
|---|---|---|
| Command Center | Latest briefs, priorities and counts | Read and follow linked records |
| Projects / Maria Projects | Outcomes and next actions | Create, edit, delete |
| Tasks / Maria Tasks | Individual commitments | Create, edit, complete |
| Maria Settings | Personal schedule and controls | Create/edit profile |
| Approvals | Exact-action decisions | View, Approve, Reject; execute supported approved Google actions |
| Connections | Google account state | Connect Google Workspace; Enable approved writes |
| Email Triage | Email summaries and unsent replies | Edit draft and follow-up date |
| Meetings | Synced meetings and preparation | Edit objective/tier/domain; Process notes |
| Contacts | Relationship source records | Create and edit |
| Daily Five | Relationship suggestions | View, Accept, Reject |
| Claims Registry | Evidence-backed factual statements | Create, verify, recheck |
| Alerts | Deadline and follow-up warnings | Review and acknowledge where available |
| Verified Identities | Private messaging identity mapping | Authorized administrator maintenance |
| Workflow Runs | Processing history | View; Verify time saved |
| Prompt Versions | Workflow instructions and schemas | Authorized prompt maintenance |
| Content Packages | Brand-specific content drafts | Create idea; Generate package; review |
| Book Portfolio | Books and milestones | Create and update |
| Agverse Opportunities | Evidence and opportunity ranking | Create, score, update |
| ACM Production | Weekly editorial planning | Create; Generate plan; review |
| Quality & Corrections | Feedback and incident records | Create, investigate, resolve |
| Acceptance Dashboard | Measured adoption and quality | Review; Save 30-day snapshot |
| Action Reconciliation | Uncertain external outcomes | Confirm completed / not executed with evidence |
| External Action Control | Global external-write switch | Administrator emergency stop or enable |

### 16.1 Using lists and forms

1. Open the required list from the sidebar.
2. Use search on searchable fields such as project name, task text, contact name, or workflow ID. Sorting is available on columns configured as sortable.
3. Select **Edit** to change an editable record, or **View** for read-only detail.
4. Complete required fields, save, and confirm the value in the list or reopened form.
5. If validation appears, correct the indicated field and save again. A disabled field is generated or protected.
6. Use Delete only when removing a record is intended. Completing or archiving work usually preserves better history. No universal restore procedure is established by this review.

Search, filters and bulk operations vary by screen. Do not assume every list includes export, import, merge, or a manual Run workflow button.

## 17. Projects and tasks in detail

### 17.1 Understand stage versus status

Project **stage** describes the lifecycle: idea, defined, active, waiting, review, complete, paused or archived. Project **status** describes its operational condition: Completed, Awaiting Approval, Waiting on Another Person, Scheduled or Blocked. Update both consistently.

**Owner name** records responsibility; it does not transfer the account ownership used for access control. Typing a colleague's name does not grant that colleague access.

### 17.2 Project field guide

| Field | What to enter |
|---|---|
| Domain | Primary work area: Personal Executive, ACM, Agverse, Books, Relationships, Communications, Meetings, Legal Administration or Cross-Portfolio |
| Name / desired outcome | A recognizable title and observable finished result |
| Stage / priority | Lifecycle and real urgency |
| Confidentiality | Public, Internal, Confidential or Restricted as appropriate |
| Owner name | Person responsible for delivery |
| Next action / next-action date | Specific next move and its calendar date |
| Deadline | Final delivery date if known |
| Status / blocker | Current condition and what prevents progress |

### 17.3 Track a waiting task

1. Create or edit the task and choose its project if relevant.
2. Describe the action, for example “Receive the corrected cover proof from the designer.”
3. Enter the responsible person and source.
4. Set status **Waiting** and a follow-up date. Add a due date only when there is a real delivery commitment.
5. Explain the reason or dependency in **Priority reason**. The numeric priority score is disabled in the form.
6. Save. Review deadline-monitor alerts after its next hourly cycle.
7. When the response arrives, update the next task or mark this one Completed. Review existing follow-up warnings separately.

A due date is a calendar date. Use the configured display format and date picker; avoid ambiguous dates in free text. Possible Duplicate requires human comparison of task, source, project, owner and date before removal.

## 18. Contacts and Daily Five

### 18.1 Create a reliable contact

1. Open **Contacts → Create** and enter the verified full name.
2. Enter the known email, role and organization. Leave unknown values empty.
3. Choose tier A, B or C according to your organization's relationship policy and select the actual relationship stage.
4. Explain **Why matters** and record **Verification source**.
5. Add a real warm path, next action and follow-up date when known.
6. Record consent preferences. Put private details only in the intended private fields and only when needed.
7. Save and search for the contact to ensure you did not create an obvious duplicate.

There is no contact merge action in the inspected Contacts table. Ask support to handle a verified merge if required.

### 18.2 Review the Daily Five

1. Enable the workflow in your active profile and maintain useful contact records.
2. After the weekday cycle, open **Daily Five**. Use the status filter to select **Pending review**.
3. Select **View** and read relevance, warm path, suggested comment, connection note and follow-up.
4. Compare the recommendation with the contact's facts and consent preferences.
5. Choose **Accept** or **Reject** on the pending row and confirm.
6. Perform any intended outreach yourself through an authorized channel, then update the contact's stage and next action.

**Expected result:** The recommendation records accepted or rejected status. Accepting does not contact the person or trigger LinkedIn activity. The detail form is read-only.

## 19. Claims and content packages

### 19.1 Verify a claim before using it

1. Open **Claims Registry → Create**.
2. Enter the exact statement, subject and category.
3. Add the authoritative evidence URL and inspect that evidence yourself.
4. Enter verification and recheck dates; add each permitted brand using its exact label.
5. Set **Verified** only after verification and save.
6. Reopen the record to compare the exact wording and brand with the planned content.

Training example: use a harmless fictional statement only in an isolated training environment. Do not mark an invented business statement Verified in production.

### 19.2 Generate a content package

1. Open **Content Packages → Create**.
2. Select one brand: Fr. Morson, All Catholic Media, Agverse AI UAE or Books.
3. Enter a content pillar, audience, required **Source idea**, and source URL when available.
4. Add factual statements under **Core claims**, matching current permitted Claims Registry entries exactly.
5. Save and return to the list.
6. Select **Generate package** and confirm. This action is offered for idea, blocked_claims or failed records.
7. Reopen the record. Read **Master draft**, **Derivatives**, status and review notes. Generated output fields are disabled; derivatives may appear as structured JSON.
8. Check facts, brand voice, sources, names, links and suitability. Record corrections in Review notes and Quality & Corrections when appropriate.
9. Use your authorized editorial process to finalize and publish outside this screen.

**Expected result:** An internal draft package is produced, or the status reports a block/failure. Generation does not publish to social media. Review notes are feedback, not an automatic rewrite command. A generated record may no longer offer Generate package; ask support for a deliberate revision path rather than deleting history.

### 19.3 Fix a claims block

1. Open each claim used in the package.
2. Compare exact wording, verification status, source, recheck date and permitted brand.
3. Correct the source record after checking evidence; correct the package's Core claims if necessary.
4. Save both records.
5. Retry **Generate package** when the blocked record offers the action.
6. If it remains blocked, report the package ID and relevant claim IDs without confidential source content.

## 20. Book Portfolio and Agverse Opportunities

### 20.1 Maintain a book

1. Open **Book Portfolio → Create**.
2. Enter **Exact title**, subtitle, credits and edition precisely.
3. Choose the actual stage: idea, outline, drafting, editing, design, proofing, publishing or published.
4. Add the manuscript URL, current milestone, milestone owner, milestone due date and publication target.
5. Record blocker, contributor/status pairs and marketing status where known.
6. Set record status Active, On hold, Completed or Archived; enter next action and date; save.
7. Enable Weekly Book Portfolio Review in the profile.
8. After Monday's scheduled review, open Command Center's **Weekly Book Portfolio Review**. Review high-value actions and missing-data warnings.
9. Update the actual book and task records to reflect decisions. A review does not publish a book.

### 20.2 Maintain and rank an opportunity

1. Open **Agverse Opportunities → Create** and enter name, organization and summary.
2. Enter expected value if known and choose AED, USD, EUR or GBP.
3. Assign each required score from 1 to 5: expected value, strategic fit, urgency, evidence strength, effort and risk.
4. Separate **Verified facts** from **Hypotheses** and add evidence/URL pairs.
5. Enter next step, owner and date, and select Approval required when appropriate.
6. Choose stage research, qualifying, proposal, negotiation, won or lost; choose record status; save.
7. Review the list's computed priority and the Thursday review in Command Center.

The implemented score is **value + strategic fit + urgency + evidence − effort − risk**. Larger effort and risk scores reduce priority. The weekly review flags approval when the record requires it or risk is 4 or 5. This flag does not create a contract, send a proposal or authorize spending.

## 21. ACM weekly production

1. Open **ACM Production → Create** before the planned weekly run.
2. Select the week-start date, enter the theme and supporting source notes.
3. Add exact core claims currently verified for **All Catholic Media**.
4. Enter required owner name and approval deadline with the correct time.
5. Save. Select **Generate plan** and confirm when offered; alternatively enable ACM weekly production and configure its weekday/time in the active profile.
6. For scheduled processing, ensure the plan belongs to the current week and has planned or blocked_claims status.
7. Reopen the record and review **Production package**, generated status and timestamp. The package may be displayed as JSON.
8. Enter review notes and assign the necessary editorial work through your normal project/task process.
9. Resolve claims blocks using chapter 19 before generating again.

**Expected result:** A draft weekly production plan is saved. Neither the approval deadline nor a generated plan automatically publishes content or approves it.

## 22. Email and meeting walkthroughs

### 22.1 Triage an email

1. Connect Google and enable Email Triage in the profile.
2. Wait for the next half-hour cycle inside working hours. Allow additional time for the queue.
3. Open **Email Triage** and find the Subject/From row.
4. Select **Edit**. Review classification, sensitivity, summary and commitments; these are disabled source/generated fields.
5. Open the real Gmail thread separately to verify source context.
6. Edit **Draft reply (not sent)** and Follow up at, then save.
7. If the summary or classification is wrong, record the correction under Quality & Corrections; those fields cannot be edited in this form.
8. If a send is required, follow the controlled approval procedure in chapter 23 or use the organization's normal Gmail process.

This is an email-review screen, not a full Gmail client. It does not expose a compose, attachment upload, request-approval or send action.

### 22.2 Prepare for a meeting

1. Enable Meeting Preparation and connect the correct Google Calendar account.
2. After the hourly cycle, open **Meetings** and locate the event.
3. Select **Edit** and check title, start/end times, preparation status and brief.
4. Enter or correct objective, tier and domain, then save.
5. Correct the event's schedule or attendee information in the source Calendar when necessary. The synced title and times are disabled in Maria's form.
6. Review missing source information before relying on a generated brief.

### 22.3 Process meeting notes

1. After the meeting, prepare accurate notes with decisions, actions, owners, dates and unresolved questions.
2. On the meeting row select **Process notes**.
3. Paste the notes into the required Notes field and submit.
4. Wait for **Meeting closeout prepared** or inspect the reported error.
5. Review resulting tasks and any approval or draft records produced. Check whether an action duplicates an existing commitment.
6. Confirm ambiguous owners/dates with the people involved and correct the task records.

A thank-you draft is unsent. Processing notes does not replace a human check of what was agreed, and repeated processing should be reviewed for duplicate records.

## 23. Approvals, execution and reconciliation

### 23.1 Important interface boundaries

The inspected code implements service methods to request Gmail-send and Calendar-create approvals. Email Triage and Meetings do not expose request buttons, and the conversational tool registry does not include these write tools. If no approval exists, an authorized technical operator must prepare it through the application's supported service integration. Do not look for a documented self-service composer that is absent from this revision.

The current Gmail sender builds plain-text messages with To, optional CC/BCC, Subject, Body and optional thread ID. It does not construct file attachments. The email approval preview includes To, Subject and Body but does not explicitly print CC/BCC. Calendar approval previews show title, times, timezone and attendees, while description/location are not explicitly included. Obtain verification of all exact proposed fields from an authorized operator before approving when the visible preview is incomplete.

### 23.2 Review and execute an existing action

1. Open **Approvals → View** on the intended row.
2. Check action type, proposed action, channel/recipient, risk, preview, attachments information if shown, decision and expiration.
3. For a Google action, verify the connected owner/account and all recipients or event fields. Resolve any incomplete preview before proceeding.
4. Return to the list. On a current pending row choose **Approve** or **Reject** and confirm.
5. An approved action is still not executed. For supported Google actions, use **Execute approved action** and confirm the external-action warning.
6. Wait for the completion notification. Verify the provider directly if necessary; do not infer completion from the approval decision.
7. If execution fails or is uncertain, stop retries and inspect Action Reconciliation.

The Execute button is offered for approved Google-send/Calendar-create types without a recorded completed action. A visible button does not bypass runtime expiry, permissions, safety controls or reconciliation checks.

### 23.3 Resolve an uncertain outcome

1. Open **Action Reconciliation** and identify the pending row by action, provider, reason and time.
2. Check the connected owner's Gmail Sent folder or Google Calendar directly.
3. Match recipient/attendees, exact content, date/time and provider identifiers. If evidence is inconclusive, keep the reconciliation pending and ask support.
4. If definitely completed, choose **Confirm completed**, enter the actual provider confirmation ID when available, evidence and required Resolution notes, then confirm.
5. If definitely not executed, choose **Confirm not executed**, enter evidence and required Resolution notes, then confirm.
6. Confirm the reconciliation status changed. The completed path marks the action completed; the not-executed path marks it failed.
7. Ask support to validate the retry path and approval validity before another attempt. Reconciliation itself does not resend anything.

### 23.4 Emergency stop

1. An administrator opens **External Action Control**.
2. Select **Emergency stop** and inspect executing-action and pending-reconciliation counts.
3. Verify any in-flight action at its provider; a stop cannot recall an already sent email or cancel an already completed event.
4. Record a safety incident and preserve IDs and timestamps.
5. Enable global external actions only after investigation and a deliberate review of outstanding approvals.

Read-only work can continue. A full processing pause requires infrastructure support.

## 24. Private WhatsApp conversation

### 24.1 Administrator identity setup

1. Confirm the messaging account and inbound webhook are configured using the repository's channel setup guide.
2. Open **Verified Identities → Create** using an authorized role.
3. Select the intended Maria profile and the channel account where required.
4. Select platform **WhatsApp** and enter the exact external identifier used by that channel.
5. Verify the person's identity outside the chat. Only then set Verified at and Active, add a descriptive label and save.
6. Test from the intended number and confirm it resolves to the right owner before using private information.

Only WhatsApp is offered in this Maria identity form. Generic Messenger or Instagram support elsewhere in the app does not establish private Maria support for those platforms.

### 24.2 Example requests

Send one clear request at a time to the configured WhatsApp business number from the verified identity:

- “Show my open tasks.”
- “Create a task: review the cover proof. Owner: me. Due date: 22 October 2026.”
- “List my upcoming Calendar meetings.”
- “Show my priority emails.”
- “Search Drive metadata for the autumn launch plan.”

These are natural-language examples, not guaranteed slash commands. Ask for clarification when a response uses an unexpected date or owner. Verify a created task in Tasks.

The registered chat tools can list tasks, create internal tasks, list priority emails, list Calendar events and search Drive files when available. They do not implement general project editing, Google sending, Calendar creation, file-content retrieval or social publishing. Drive access is metadata read-only; finding a filename does not mean Maria read the document body.

If private mode fails, stop sharing private context and ask the administrator to inspect the profile, global gate, user permission, active/verified identity and channel-account match.

## 25. Documents, account security and adjacent app functions

### 25.1 Manage local documents

1. Open **Documents** from the main application sidebar.
2. Administrators should confirm **Viewing documents for** before making changes. Ordinary users are restricted to their own documents.
3. Open the destination folder in the file manager.
4. Use the offered file-manager controls to create a folder or upload a file, then confirm it appears.
5. Use file actions to rename, move/copy, download or delete when available. Confirm the destination before moving or copying.
6. If loading fails, record “Could not load documents for this user” and contact support with the owner context.
7. If storage limits reject an upload, check the displayed drive information and ask the administrator about quota; do not repeatedly upload duplicates.

Local Documents and Google Drive are separate stores. Uploading a local document does not automatically put it in Google Drive or make its contents available to Maria's Drive-metadata tool. File-manager labels can vary with the installed component version and locale.

### 25.2 Protect your account

1. Open your account/profile menu and edit your profile.
2. Maintain your name, email, avatar and password through the available profile fields.
3. If two-factor setup is enabled globally, use the profile's enable action, add the supplied setup secret to your authenticator and confirm with its current code.
4. View and securely store recovery codes through the offered action.
5. Regenerating recovery codes replaces the previous set. Store the new set securely.
6. Sign out when finished on a shared device.

### 25.3 Other application areas

The wider application includes Conversations, Services, FAQs, Service Requests, messaging accounts, Users, role permissions, System Settings and audit logs. These support customer messaging and administration around Maria. They are separate workflows: an ordinary customer-service conversation is not automatically a verified private Maria session. Channel-provider setup details belong in the existing WhatsApp and Messenger/Instagram setup guides rather than being inferred from Maria's Google connection.

## 26. Quality, metrics and administrator maintenance

### 26.1 Report a correction

1. Open **Quality & Corrections → Create**.
2. Select Human correction, Safety incident or Feedback.
3. Choose the category and a realistic severity.
4. Link the relevant Workflow Run if available.
5. Enter a concise description, expected result, actual result and occurrence time.
6. Set status Open or Investigating and save.
7. After fixing the source or process, enter Resolution, Resolved at and status Resolved.

A quality event records feedback. It does not automatically edit the original email, task, prompt or generated draft.

### 26.2 Inspect a workflow and verify time saved

1. Open **Workflow Runs** and search for its run ID or workflow type.
2. Select **View**. Review status, prompt version, input references, source gaps and error.
3. Compare start/finish times with the expected cycle. A queued or running job may require infrastructure investigation if it remains stuck.
4. When you have measured actual human review time, select **Verify time saved** on the row.
5. Enter non-negative actual human minutes and submit.
6. The application records a verified saving of the estimated manual minutes minus human minutes, with a minimum of zero.

Do not count unreviewed output as verified time saved. The displayed cost is estimated USD cost, not an invoice.

### 26.3 Save an acceptance snapshot

1. Open **Acceptance Dashboard**.
2. Review its 30-day report and the quality/source records behind it.
3. Select **Save 30-day snapshot**.
4. Confirm the “Auditable 30-day snapshot saved” notification.
5. Use the snapshot in a handover review together with unresolved issues and observed user outcomes.

Saving a snapshot records evidence; it does not certify that every feature is production-ready.

### 26.4 Change a prompt version

1. An authorized administrator opens **Prompt Versions**.
2. Record the current active version for the target prompt type.
3. Create a new version with the exact prompt type, distinct version, content, expected output schema and change notes.
4. Validate it in a controlled environment using representative source records before activation.
5. Set Active and save when ready. Activating a version disables other versions of that same prompt type.
6. Review subsequent runs and quality events. If needed, reactivate the known-good version deliberately.

Prompts and identities are shared administrative resources rather than ordinary owner-scoped records. Grant their editing permissions carefully. A malformed output schema can break a workflow.

## 27. Practical daily and weekly checklists

### 27.1 Morning: approximately ten minutes

1. Open Command Center and confirm the latest brief's date.
2. Read its top outcomes and any **Sources not checked** warning.
3. Review due tasks; inspect the full Tasks list for work outside the small dashboard list.
4. Review the next meetings and email drafts.
5. Resolve or escalate pending approvals and alerts.
6. Update actual owners, dates and next actions before acting on recommendations.

The dashboard's due-task list is limited to seven non-completed dated tasks ordered by due date; it is not a complete “today” filter. Its Open tasks count includes non-completed waiting, blocked and duplicate-review items. The failure count covers the recent week. Administrators may see mixed-owner data.

### 27.2 Evening: approximately five minutes

1. Mark genuinely completed tasks Completed.
2. Update waiting items and follow-up dates.
3. Read the latest Evening Review and compare it with the day's actual work.
4. Review tomorrow's top actions and unresolved approvals.
5. Record material corrections and assign a follow-up owner.

### 27.3 Weekly review

1. Check Book Portfolio and Agverse review actions.
2. Prepare the next ACM weekly record before its scheduled run.
3. Recheck expiring claims and weak evidence.
4. Review recurring quality corrections, failed workflows and pending reconciliations.
5. Measure actual human time on a sample of completed workflows.
6. Review Acceptance Dashboard and save a snapshot when required by your handover process.

## 28. Troubleshooting decision table

| Symptom | First user check | Administrator/support check |
|---|---|---|
| Cannot sign in | Correct address, email, password, authenticator clock/code | Account access, two-factor recovery and rate limits |
| Maria missing | Refresh or sign in again | Global switch, access permission, role/resource permissions |
| No brief | Active profile, selected workflow, local scheduled time | Scheduler, queue, failed jobs, source data |
| No emails | Correct connected account and working hours | Read scope, half-hour cycle, triage error |
| No meeting | Correct Calendar/account, upcoming event, hourly cycle | Calendar scope, preparation workflow and source window |
| Draft saved but no email sent | Expected: draft editing only | Prepare an exact-action approval through service integration |
| Approved but not sent | Check Execute approved action and expiry | Safety gates, scopes, action/reconciliation state |
| Content Generate button missing | Check status; generated records may be ineligible | Intended revision/retry path |
| Claims blocked | Exact claim and permitted brand | Verification/evidence/recheck logic |
| Wrong owner in a task | Edit responsible owner name | Account ownership is separate from the name field |
| Wrong times | Profile timezone versus global display timezone | Stored values and server configuration |
| No disconnect button | Expected in this revision | Use supported disconnect endpoint or provider-side revocation and cleanup |
| Unknown send outcome | Inspect provider; do not retry | Action Reconciliation with evidence |
| Contact duplicate | Compare exact identity and sources | No built-in merge action; controlled cleanup |
| Documents fail to load | Refresh and confirm intended owner | Storage, access, quota and API error |

**Disconnect limitation:** The backend has a protected Google disconnect route, but Connections currently offers only the two connect/upgrade header actions and no row disconnect action. Ask an authorized operator to use the supported disconnect route or revoke access from the Google account and reconcile the connector state. Do not expect a visible Disconnect button.

**Schedule note:** Profile timezone controls the local time of daily/weekly workflows. Half-hour and hourly jobs follow the application's scheduler cadence; the queue adds processing delay. Keep ordinary daytime working hours unless overnight-window behavior has been explicitly verified. Schedules derived from the morning time should remain within the intended day.

## 29. First-use acceptance worksheet

Complete this in a controlled test with harmless data and writes initially disabled.

| Check | Expected evidence | Result / owner |
|---|---|---|
| Sign-in and Maria access | Intended account can open Command Center | __________________ |
| Ownership | Ordinary user cannot access another owner's records | __________________ |
| Profile | Saved timezone, times, workflow selections and Active | __________________ |
| Practice project/task | Record IDs and correct link/status | __________________ |
| Google reads | Correct active account; test email and Calendar item processed | __________________ |
| Scheduled brief | Correct local date and successful run ID | __________________ |
| Meeting notes | Closeout notification and reviewed tasks | __________________ |
| Claims/content | Verified evidence, draft output or explained block | __________________ |
| Approval review | Preview inspected; no accidental execution | __________________ |
| Emergency control | Administrator can identify stop and reconciliation page | __________________ |
| Quality/metrics | Correction saved and time verification understood | __________________ |
| Handover | Known issues, support contact and responsible owner recorded | __________________ |

Tester: __________________  Date/time/timezone: __________________

Application address/revision: __________________  Administrator: __________________

Known limitations and next actions: __________________________________________________

Only perform a real external-write acceptance test with an explicitly authorized recipient/event, a complete exact preview, both safety controls intentionally enabled, and an operator prepared to reconcile uncertain outcomes.

# Part III — Analysis notes and evidence

## 30. Functional analysis and current limits

Maria combines a private messaging assistant, structured records, scheduled workflows, and a controlled external-action layer. Its usefulness depends on accurate source records and active background processing. It is not a single chat interface that automatically performs every sidebar operation.

| Layer | Implemented function | Operating implication |
|---|---|---|
| Access | Global Maria gate, user authorization, owner-scoped operational records | Global enablement does not grant every user access |
| Working memory | Projects, tasks, contacts, claims, books, opportunities and content records | Keep records current; account ownership differs from owner-name text |
| Conversation | Five registered task/Google-read tools | Natural language has a narrower capability set than the sidebar |
| Automation | Ten user workflows plus stuck-action reconciliation | Scheduling and queues must both work |
| Review | Draft forms, recommendations, approvals and quality events | Human review remains a distinct operating step |
| External actions | Gmail plain-text send and Calendar event creation services | Approval creation currently needs a service integration/operator |
| Execution safety | Exact-content approval checks, action ledger and reconciliation | Never retry an uncertain external result blindly |
| Measurement | Workflow time verification, quality reporting and snapshots | Measure human effort rather than treating estimates as proof |

### 30.1 Findings that affect user training

- **Separate approval from execution.** A user must deliberately use Execute approved action for supported Google approvals.
- **Incomplete self-service write initiation.** No request-send or create-event controls were found in Email Triage or Meetings; the registered chat tools also exclude these writes.
- **Preview completeness needs attention.** Some accepted fields are not explicitly printed by the request-service previews. Verify them through an authorized operator before approving.
- **No attachment sending in the current sender.** An attachments field in approval data does not establish MIME file-attachment support.
- **No visible Google disconnect action.** Backend revocation support exists, but the current Connections screen does not expose it.
- **No automatic social publishing.** Content generation and Daily Five acceptance produce internal work/review state.
- **Some outputs are technical to read.** Derivatives and ACM production packages are displayed as JSON in disabled text areas.
- **Dashboard is a summary.** It limits visible tasks/recommendations and latest outputs; full lists and run records are needed for investigation.

These are source observations and usability boundaries, not results of a production penetration test or live acceptance certification.

### 30.2 Recommended application improvements

The following are recommendations, not instructions for existing buttons: add self-service Google action request forms; show every approved field in the preview; expose a safe disconnect control; display structured content in readable sections; provide a deliberate draft revision path; and make dashboard owner/date context and list limits explicit. No application behavior was changed while preparing this manual.

## 31. Source traceability and document maintenance

This edition was prepared on 18 October 2026 from the checked-out repository. The earlier manual and operating documents were used as context, then compared with code for the detailed workflows. No secrets or real email content were needed.

| Topic | Primary repository evidence |
|---|---|
| Setup and operating baseline | docs/MARIA_USER_MANUAL.md; docs/MARIA_OPERATIONS_RUNBOOK.md |
| Google setup | docs/MARIA_GOOGLE_WORKSPACE_SETUP.md; Google connector controller; routes/web.php |
| Screen actions and fields | app/Filament/Resources/*Resource.php and their Pages classes |
| Dashboard behavior | app/Filament/Pages/MariaDashboard.php and its Blade view |
| Personal scheduling | routes/console.php; app/Jobs/Maria; AssistantProfileResource |
| Chat capabilities | app/Services/Maria/ToolRegistry.php; Tools directory |
| Private identity | AssistantChannelIdentityResource; AssistantIdentityResolver |
| Google execution | ApprovedGoogleActionService; ApprovalResource; Google/GmailWriteClient |
| Reconciliation and stop | ActionReconciliationResource; ExternalActionControl |
| Documents | DocumentController; resources/js document-manager component |
| Authentication | app/Filament/Auth/Login.php; EditProfile.php |
| Metrics and prompts | WorkflowRunResource; MariaAcceptanceDashboard; PromptVersionResource |

Existing automated test files provide additional implementation examples but were not executed as part of this documentation-only task. Production operators must run their own first-use acceptance checks against the deployed version.

Update this manual whenever navigation, available actions, workflow timing, approval payloads, connector scopes or permissions change. Preserve the distinction between available user controls, administrator procedures and future recommendations.
