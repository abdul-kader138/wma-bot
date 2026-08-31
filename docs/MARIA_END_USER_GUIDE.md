# Maria Assistant — Step‑by‑Step User Guide (with sample data)

Version: 1.4
Last updated: 31 August 2026
Audience: the person who will use Maria every day (not the administrator)

---

## How to read this guide

This guide walks you through Maria from your first login to a full working day,
using **one continuous set of example data**. Every screen, button, and message
you need is written out. Where you see a box like this:

> **Do this:** open **Maria Assistant → Command Center**.

follow it exactly. Where sample records are given, type those values in yourself
so your screens match what this guide describes. You can safely delete the sample
records afterwards.

Menu names in this guide match the app's built‑in labels — for example the
Projects screen is listed as **Maria Projects** and the Tasks screen as **Maria
Tasks** under the **Maria Assistant** group.

You do **not** need to talk to anyone to complete this guide. If something on your
screen does not match, jump to **Section 14, Troubleshooting**.

If you would rather see one complete working day before the reference sections,
read **Section 17, "Your first Monday with Maria"** first — it walks the whole
day, click by click, on the sample data — then come back here.

Two assumptions are already handled by your administrator before you start:

1. Maria is switched on and your account can see the **Maria Assistant** menu.
2. If you will use Gmail / Google Calendar / Google Drive, your Google account is
   already connected in read‑only mode.

If either is not true, send this guide's **Section 2 checklist** to your
administrator and stop until they confirm.

---

## 1. What Maria is, in one minute

Maria is your private executive assistant inside this application. She works in
two places:

| Where | What you do there | Example |
|---|---|---|
| **Command Center** (web app) | Read briefs, review drafts, approve actions, keep your projects and tasks current | Approving an email before it is sent |
| **Chat** (WhatsApp, from your verified number) | Ask quick questions, capture tasks on the move | "Maria, what's due today?" |

**Maria always prepares, never fires.** She will draft an email, build a meeting
brief, sort your inbox, and flag a deadline. She will **not** send an email,
create a calendar event, publish a post, spend money, or sign anything unless
**you approve that exact action** first. A draft you see on screen has not been
sent.

Maria also will not: automate anything on LinkedIn, invent facts or quotes, or
make legal / medical / financial / doctrinal decisions for you.

---

## 2. Before you start — checklist for your administrator

Ask your administrator to confirm every line. Copy this list to them as‑is.

- [ ] **Enable Maria Assistant** is ON in System Settings → General.
- [ ] My user role has the **Access Maria Assistant** permission.
- [ ] I can see the **Maria Assistant** group in the left menu after signing in.
- [ ] My **Maria Settings** profile exists and **Active** is ON.
- [ ] Profile timezone, working hours, morning‑brief time and evening‑review time are set to my real values.
- [ ] The queue worker / Horizon is running and the scheduler runs every minute.
- [ ] (If using Google) My Google account shows as **active** under **Connections**, with a permission count and no error.
- [ ] (If using Google) External writes are **OFF** for now — we will turn them on later, deliberately.

When all boxes are ticked, continue.

---

## 3. Meet your example user

Throughout this guide you are **Fr. Morson Livingston**. Your work is split into
"domains" that Maria keeps separate — she files every project, task, and email
under exactly one of them:

| Code | Domain | What lives here |
|---|---|---|
| PER | Personal Executive | Your own priorities, travel, admin |
| ACM | All Catholic Media | Podcast, newsletter, donors, partners |
| AGV | Agverse AI UAE | Compliance product, pilots, investors |
| BKS | Books and Publishing | Manuscripts, editors, launches |
| REL | LinkedIn and Relationships | Research and follow‑ups (no automation) |
| COM | Communications | Email triage, replies, commitments |
| MTG | Meetings and Calendar | Briefs, agendas, follow‑ups |
| LEG | Legal Administration | Deadlines and records only |
| XPF | Cross‑Portfolio | Anything that touches two or more of the above |

Your example settings (already in your Maria Settings profile):

| Field | Value |
|---|---|
| Timezone | `Europe/Rome` |
| Language | English *(set this to Italian or Bengali and Maria replies — in chat, and in your briefs — in that language)* |
| Working hours | `08:00`–`18:00` |
| Morning brief at | `07:30` |
| Evening review at | `18:30` |
| Enabled workflows | Morning Brief, Evening Review, Email Triage, Meeting Preparation, Deadline Monitor, Daily Five |
| Allow approved external actions | OFF |

> **Do this:** open **Maria Assistant → Maria Settings**, open your profile, and
> check these values. Fix any that are wrong, then **Save**.

**Enabled workflows** is your on/off switch for each piece of automation. If a
box is unticked, that workflow does not run — no brief, no triage, no deadline
alerts for it. Tick only what you want while you are learning, and add more
later.

---

## 4. Your first 15 minutes in the Command Center

> **Do this:** open **Maria Assistant → Command Center**.

You will see five count tiles across the top:

| Tile | Meaning | Right now it says |
|---|---|---|
| Active projects | Projects in stage Defined / Active / Waiting / Review | `0` |
| Open tasks | Tasks not yet Completed | `0` |
| Pending approvals | Approvals awaiting your decision | `0` |
| Workflow failures | Background jobs that failed in the last 7 days | `0` |
| Daily Five pending | Relationship suggestions you have not reviewed | `0` |

Below the tiles:

- **Due tasks** — your next 7 tasks that have a due date.
- **Pending approvals** — up to 5, soonest to expire first.
- **Recent workflow runs** — the last 5 background jobs and whether they
  completed, failed, or are still running.
- **Latest Morning Brief / Evening Review** — the most recent of each.
- **Latest Book / Agverse / Quality reports** — the most recent weekly outputs.

Everything is empty because you have no data yet. Section 5 fixes that.

---

## 5. Load your starting data (the sample dataset)

Enter these records exactly. They are referenced by every later section, so your
briefs and chat replies will match this guide.

Dates use the format `DD/MM/YYYY`. "Today" in this guide is **Monday 7 September
2026**. Use real dates near your own today if you prefer — just keep them
consistent.

### 5.1 Projects — create 3

> **Do this:** open **Maria Assistant → Maria Projects → New project**. Repeat for each
> row block below.

**Project A**

| Field | Value |
|---|---|
| Domain | `BKS` — Books and Publishing |
| Name | Autumn book launch: *The Quiet Field* |
| Desired outcome | Final manuscript approved and a 4‑week launch plan agreed |
| Stage | Active |
| Priority | High |
| Owner | Fr. Morson Livingston |
| Next action | Review the editor's marked‑up manuscript |
| Next‑action date | `10/09/2026` |
| Deadline | `30/09/2026` |
| Status | Scheduled |
| Blocker | *(leave empty)* |
| Confidentiality | Confidential |

**Project B**

| Field | Value |
|---|---|
| Domain | `AGV` — Agverse AI UAE |
| Name | Al Ain exporter pilot |
| Desired outcome | One signed pilot agreement with a UAE date‑exporter |
| Stage | Waiting |
| Priority | High |
| Owner | Fr. Morson Livingston |
| Next action | Chase signed NDA from the exporter's legal team |
| Next‑action date | `09/09/2026` |
| Deadline | `20/09/2026` |
| Status | Waiting on Another Person |
| Blocker | Exporter legal review of the NDA is not finished |
| Confidentiality | Confidential |

**Project C**

| Field | Value |
|---|---|
| Domain | `ACM` — All Catholic Media |
| Name | September newsletter |
| Desired outcome | Newsletter written, approved, and scheduled to send on 26 September |
| Stage | Defined |
| Priority | Normal |
| Owner | Fr. Morson Livingston |
| Next action | Choose the lead reflection topic |
| Next‑action date | `12/09/2026` |
| Deadline | `25/09/2026` |
| Status | Scheduled |
| Confidentiality | Internal |

You should now see **Active projects: 3** on the Command Center (Active + Waiting +
Defined all count).

### 5.2 Tasks — create 6

> **Do this:** open **Maria Assistant → Maria Tasks → New task**. For every task
> the **Owner** is `Fr. Morson Livingston` and the **Source** is `User`.
> Fill in the four columns below, then in the task form pick the **Project** from
> the dropdown and set the **Status**.

| # | Task (one observable action) | Project | Due date | Status |
|---|---|---|---|---|
| 1 | Read editor's cover note and list required changes | Autumn book launch | `08/09/2026` | Open |
| 2 | Approve final cover proof | Autumn book launch | `15/09/2026` | Open |
| 3 | Send pilot scope summary to Agverse legal | Al Ain exporter pilot | `09/09/2026` | Waiting |
| 4 | Draft September newsletter reflection (600 words) | September newsletter | `18/09/2026` | Open |
| 5 | Confirm travel dates for the Rome editors' meeting | Autumn book launch | `05/09/2026` | Open |
| 6 | Reply to Sr. Angela about the parish retreat date | *(none)* | `07/09/2026` | Open |

For **task 3** only, also set **Follow‑up date** to `12/09/2026`.

What each of these is teaching you:

- **Task 5** is **already past** its due date on our "today" (7 September). After
  you save it, it appears in the Command Center due list in red — that is how
  Maria shows **overdue** work. It is deliberate here so you can see the flag.
- **Task 6** is **due today**. A task due today is *current* for the whole day
  and only turns overdue tomorrow — so it is **not** red yet.
- **Task 3** is **Waiting** (someone else must act first) with a follow‑up date.
  Nothing is "due", but Maria will still surface it to you on 12 September so it
  does not silently rot.
- **Task 1** is written as one checkable action ("read… and list changes"), not
  "sort out the cover". Keep tasks that small — it makes the briefs useful.

After saving all six, **Command Center → Open tasks** should read **6**
(a Waiting task still counts as open).

### 5.3 Contacts — create 4

> **Do this:** open **Maria Assistant → Contacts → New contact**. Create each of
> the four records below. The **Verification source** field matters: only
> contacts that have one are ever offered to you in the Daily Five workflow, so
> every record here has a real one.

**Contact 1**

| Field | Value |
|---|---|
| Full name | Elena Prati |
| Role | Commissioning Editor |
| Organization | Aldergate Press |
| Email | elena.prati@example.com |
| Tier | A |
| Stage | conversation |
| Verification source | The LinkedIn profile URL you personally opened and checked |
| Warm path | Introduced by your literary agent in June |
| Why this person matters | Owns the launch‑date and print‑run decision for *The Quiet Field* |
| Follow‑up date | `10/09/2026` |

**Contact 2**

| Field | Value |
|---|---|
| Full name | Khalid Al‑Mansoori |
| Role | Head of Compliance |
| Organization | Gulf Date Exporters LLC |
| Email | k.almansoori@example.com |
| Tier | A |
| Stage | meeting |
| Verification source | The company website leadership page |
| Warm path | Met at the Abu Dhabi agri‑tech expo |
| Why this person matters | Sponsors the Agverse pilot on the exporter side |
| Follow‑up date | `09/09/2026` |

**Contact 3**

| Field | Value |
|---|---|
| Full name | Sr. Angela Moretti |
| Role | Retreat Coordinator |
| Organization | Diocese of Rieti |
| Email | *(leave blank — you only have a phone number)* |
| Tier | B |
| Stage | connect |
| Verification source | Parish bulletin, confirmed by phone |
| Warm path | Long‑standing parish contact |
| Why this person matters | Organizing the October clergy retreat you were asked to lead |
| Follow‑up date | `07/09/2026` |

**Contact 4**

| Field | Value |
|---|---|
| Full name | David Okafor |
| Role | Podcast Producer |
| Organization | Freelance |
| Email | david.okafor@example.com |
| Tier | B |
| Stage | opportunity |
| Verification source | Referred by your ACM production lead |
| Warm path | Worked on two prior episodes |
| Why this person matters | Could produce the Advent podcast series for ACM |
| Follow‑up date | `16/09/2026` |

**Tier** is your judgement of importance (A = closest / highest‑value, C =
peripheral). **Stage** is how far the relationship has progressed
(research → engage → connect → conversation → meeting → opportunity → partner).
Maria uses both to rank the Daily Five list; she never changes a stage on her
own.

### 5.4 Claims Registry — create 2

The Claims Registry stops Maria from putting an unproven statement into public
content. Enter the exact wording you are willing to publish.

> **Do this:** open **Maria Assistant → Claims Registry → New claim**.

**Claim 1**

| Field | Value |
|---|---|
| Claim text | *The Quiet Field* is Fr. Morson Livingston's fourth book. |
| Subject | The Quiet Field |
| Category | Publication |
| Evidence source | Link to your publisher's catalogue page |
| Verified date | `05/09/2026` |
| Recheck date | `05/03/2027` |
| Permitted brand | Fr. Morson (personal) |
| Status | Verified |

**Claim 2**

| Field | Value |
|---|---|
| Claim text | Agverse AI UAE is running a compliance pilot with a UAE date exporter. |
| Subject | Agverse |
| Category | Partnership |
| Evidence source | Link to the signed letter of intent (internal drive) |
| Verified date | `06/09/2026` |
| Recheck date | `06/12/2026` |
| Permitted brand | Agverse AI UAE |
| Status | Verified |

A claim is usable until its **recheck date**. If a content workflow needs a
stronger statement than the verified wording, Maria will **block it** rather than
guess.

### 5.5 One book record (optional, for the weekly Book review)

> **Do this:** open **Maria Assistant → Book Portfolio → New book**.

| Field | Value |
|---|---|
| Exact title | The Quiet Field |
| Author / credit | Fr. Morson Livingston |
| Current stage | Editing |
| Next milestone | Editor's marked manuscript returned |
| Milestone date | `12/09/2026` |
| Publication target | `28/10/2026` |

### 5.6 Check your Command Center

Refresh **Command Center**. You should now see:

- Active projects: **3**
- Open tasks: **6**
- Pending approvals: **0**
- Workflow failures: **0**
- Daily Five pending: **0** (it fills after the first weekday run)
- Due tasks list ordered by due date: task 5 (05/09), 6 (07/09), 1 (08/09),
  3 (09/09), 2 (15/09), 4 (18/09).

Your data is loaded. The rest of the guide shows Maria using it.

---

## 6. Talking to Maria in chat

From your **verified WhatsApp number**, send a message to the assistant number.
Maria replies in plain text. She can read your data and capture tasks; she cannot
send anything externally from chat.

Below are real example exchanges. **You** lines are what you type; **Maria** lines
are a realistic reply based on the sample data.

### 6.1 "What's on today?"

> **You:** What's due today and what's overdue?
>
> **Maria:** Two items need you today.
> 1. **Overdue (since 5 Sep):** Confirm travel dates for the Rome editors'
>    meeting — Autumn book launch.
> 2. **Due today:** Reply to Sr. Angela about the parish retreat date.
>
> Waiting in the background: "Send pilot scope summary to Agverse legal" — I'll
> resurface it on 12 Sep. Status: Completed.

*(Behind the scenes Maria used the `list_tasks` tool, scoped to you only.)*

### 6.2 Capture a task on the move

> **You:** Remind me to call the printer about paper stock before Thursday.
>
> **Maria:** Added an internal task: "Call the printer about paper stock",
> owner Fr. Morson Livingston, due Wed 10 Sep. It is not linked to a project yet.
> Status: Completed.

*(Tool used: `create_task`. Nothing was sent to anyone — it is an internal note.)*

### 6.3 Mark something done

> **You:** I've replied to Sr. Angela. Close that task.
>
> **Maria:** Done — task #6 "Reply to Sr. Angela about the parish retreat date"
> is now Completed. Status: Completed.

*(Tool used: `complete_task`. If Maria cannot find the task she will ask you to
confirm the number from the task list rather than guessing.)*

### 6.3a Reschedule or re‑file a task

> **You:** Push the newsletter reflection task to next Friday and put it under the
> September newsletter project.
>
> **Maria:** Updated task #4 "Draft September newsletter reflection (600 words)":
> due date now Fri 25 Sep, linked to project "September newsletter", status left
> Open. Status: Completed.

*(Tool used: `update_task`. It can change a task's due date, follow‑up date,
status (open / scheduled / waiting / blocked), or which project it belongs to —
never the task wording, and it will not touch a task that is not yours.)*

### 6.4 See your projects

> **You:** List my active projects with their next actions.
>
> **Maria:**
> - **Autumn book launch: *The Quiet Field*** (BKS, Active) — next: review the
>   editor's marked‑up manuscript by 10 Sep. Deadline 30 Sep.
> - **Al Ain exporter pilot** (AGV, Waiting) — next: chase signed NDA by 9 Sep.
>   Blocker: exporter legal review not finished. Deadline 20 Sep.
> - **September newsletter** (ACM, Defined) — next: choose lead reflection topic
>   by 12 Sep. Deadline 25 Sep.
> Status: Completed.

*(Tool used: `list_projects`.)*

### 6.5 What am I being asked to approve?

> **You:** Anything waiting for my approval?
>
> **Maria:** Nothing is pending approval right now. When there is, I'll show the
> exact action, recipient, and expiry here so you can decide. Status: Completed.

*(Tool used: `list_pending_approvals`. This tool only reads — Maria can never
approve on your behalf from chat.)*

### 6.6 Re‑read this morning's brief

> **You:** Remind me what my three outcomes were this morning.
>
> **Maria:** From today's Morning Command Brief:
> 1. Lock the Rome editors' meeting travel dates so the manuscript review stays
>    on schedule.
> 2. Unblock the Agverse pilot by getting the signed NDA moving.
> 3. Reply to Sr. Angela so the October retreat date is fixed.
> Status: Completed.

*(Tool used: `get_latest_brief`. It reads the brief Maria already generated; it
does not build a new one.)*

### 6.7 Who should I reconnect with?

> **You:** Who's on my relationship follow‑up list?
>
> **Maria:** Today's suggestions (review only — I don't contact anyone):
> 1. **Elena Prati**, Commissioning Editor at Aldergate Press (Tier A). Warm path:
>    introduced by your literary agent in June. Suggested next step: a short,
>    specific note about the launch window. Draft note ready for your edit.
> 2. **Khalid Al‑Mansoori**, Head of Compliance at Gulf Date Exporters (Tier A).
>    Warm path: met at the Abu Dhabi expo. Suggested: acknowledge the NDA delay
>    and offer a call.
> Status: Completed.

*(Tool used: `list_relationship_followups`. Available because "Daily Five" is in
your enabled workflows.)*

### 6.8 Ask about your meetings

> **You:** What meetings do I have this week and are they prepared?
>
> **Maria:** Two upcoming, from your synced calendar:
> - Wed 10 Sep 09:30 — "Manuscript review with Elena Prati" — preparation:
>   **prepared**.
> - Thu 11 Sep 14:00 — "Agverse pilot check‑in" — preparation: **pending**.
> Status: Completed.

*(Tool used: `list_meetings`. It reads the meetings Maria has already synced plus
their preparation status, and works even if Google needs reconnecting. Ask
"what's on my calendar right now" to force a fresh read with
`list_calendar_events` instead — that one needs Google connected and will say the
calendar was not checked rather than invent events.)*

### 6.8a Ask what Maria can do

> **You:** what can you do?
>
> **Maria:** In chat I can: list and add tasks, complete or reschedule a task,
> list your projects and upcoming meetings, show what's awaiting your approval,
> re‑read your latest brief, and list relationship follow‑ups. With Google
> connected I can also read recent email, calendar and Drive files. I can't send
> email, change your calendar, decide an approval, or do anything on LinkedIn
> from chat. Status: Completed.

### 6.8b Ask Maria to remember something

> **You:** From now on, assume I want meetings before 11am unless I say otherwise.
>
> **Maria:** Noted — I've stored "meeting_time_preference: prefers meetings before
> 11am" and I'll take it into account. Status: Completed.

*(Tool used: `remember_fact`. These durable facts persist across conversations,
not just this chat. An administrator can see and delete them under
**Maria Assistant → Remembered Facts**.)*

Maria also keeps its own rolling summary of a long chat, so you can pick a
conversation back up later without repeating yourself.

### 6.9 What Maria will refuse in chat

> **You:** Email Elena and tell her we'll confirm the launch date Friday.
>
> **Maria:** I can write the wording, but I can't send it — sending email is an
> external action that needs your approval on the exact text and recipient.
> Draft: "Dear Elena, I'll confirm the launch date by Friday. With prayers,
> Fr. Morson Livingston." To send it, open the thread in **Email Triage**,
> finalise it, and request approval. Status: Blocked (needs your approval to
> send).

---

## 7. The Morning Command Brief

At your **morning brief time** (07:30 Rome, weekdays) Maria assembles one
screen‑sized brief from your tasks, projects, pending approvals, and — if Google
is connected — your next 48 hours of calendar and your unread priority email.

If your administrator has turned on brief emails, you'll also get a short email
with the three outcomes and the risk to watch, so you see the day's priorities
before you open the app. The email is a summary only — act on things in the
Command Center.

### 7.1 What Maria reads (sample)

- Open tasks 1–6 from Section 5.2.
- Projects A, B, C from Section 5.1.
- Pending approvals: none.
- Calendar (if connected): the two events in 6.8.
- Email (if connected): 3 unread threads, e.g. one from Elena Prati about the
  print run.

If a source cannot be read, the brief says so — it never pretends a source was
checked.

### 7.2 What the brief looks like

Open **Command Center** and read **Latest Morning Brief**. With the sample data it
reads roughly:

> **Morning Command Brief — Monday 7 September 2026**
>
> **Today's three outcomes**
> 1. **Lock Rome editors' meeting travel dates.** Owner: you. Next action:
>    confirm dates today. Why: task is overdue since 5 Sep and the manuscript
>    review on 10 Sep depends on it.
> 2. **Get the Agverse NDA moving.** Owner: you. Next action: send the pilot
>    scope summary to Agverse legal and chase the exporter's signature. Why:
>    pilot deadline is 20 Sep and it is currently blocked.
> 3. **Fix the October retreat date with Sr. Angela.** Owner: you. Next action:
>    reply today. Why: due today; small task, unblocks her planning.
>
> **Meetings and preparation**
> - Wed 10 Sep 09:30 — Manuscript review with Elena Prati. Brief prepared
>   separately; bring your list of required cover changes (task 1).
>
> **Decisions / approvals needed:** none today.
>
> **Relationship opportunities (up to 5):** Elena Prati, Khalid Al‑Mansoori.
>
> **One risk to watch:** the Autumn book launch has two dependencies (travel
> dates, cover proof) both feeding the 30 Sep deadline.
>
> **Work Maria can do without you:** none queued that is safe to run
> unsupervised.
>
> **Source gaps:** *(none)* — or *"Gmail not checked"* if Google is not
> connected.
>
> Status: Completed.

### 7.3 How to act on it, step by step

1. **Read the three outcomes only.** That is the whole point of the brief — it
   has already decided what matters today. Do not scroll to your full task list
   yet.
2. **If an outcome is wrong**, the brief is not the thing to fix — the data
   behind it is. Example: if outcome 1 should not be top priority because the
   Rome trip was cancelled, open **Maria Tasks**, open task 5, and either set
   **Status: Completed** or change the due date. Tomorrow's brief will re‑rank.
3. **Do outcome 1 now.** Confirm the travel dates by whatever means you normally
   use. Then open task 5 and set **Status → Completed**. Save.
4. **Start outcome 2.** Open task 3, check the follow‑up date is `12/09`, leave
   **Status: Waiting**. Waiting is a valid, finished state for today — you have
   done your part until the other side signs.
5. **Do outcome 3.** Send your reply to Sr. Angela. Then close task 6: either
   open it in **Maria Tasks** and set **Status → Completed**, or from chat send
   *"close task 6"*.
6. **Glance at "Meetings and preparation"** in the brief. If Wednesday's meeting
   is listed, open **Meetings** and skim the prepared brief so nothing surprises
   you later in the week.
7. **Ignore everything else in the brief for now.** Approvals, relationship
   opportunities, and "work Maria can do without you" are there for context, not
   for action first thing.

By 08:00 you have touched three things, updated three statuses, and the rest of
the day's noise is off your desk.

---

## 8. The Evening Review

At your **evening review time** (18:30 Rome) Maria produces a short closeout.

With the sample data, after a normal day it reads:

> **Evening Review — Monday 7 September 2026**
>
> **Completed today:** Confirm Rome travel dates; Reply to Sr. Angela.
>
> **Awaiting your approval:** none.
>
> **Waiting on other people:** Agverse NDA signature (exporter legal); pilot
> scope summary acknowledgement (follow up 12 Sep).
>
> **Unfinished, with reason:** "Read editor's cover note" — not started, no
> blocker, carried to tomorrow.
>
> **Tomorrow's likely top three:** read the editor's cover note; prepare cover
> change list for Wednesday's meeting; check the NDA status.
>
> Status: Completed.

How to use it, in three minutes:

1. **"Completed today"** — sanity‑check it. If something is listed that you did
   not actually finish, open that task and set the real status. If something you
   finished is missing, you probably forgot to set it Completed — do that now.
2. **"Awaiting your approval"** — if this is not empty, open **Approvals** and
   clear it before you log off (approvals expire overnight).
3. **"Waiting on other people"** — no action tonight; just confirm the follow‑up
   dates look right so Maria chases them for you.
4. **"Unfinished, with reason"** — for each item decide: carry it to tomorrow (do
   nothing), reschedule it (change the due date), or drop it (set Completed or
   Blocked with a note).
5. **"Tomorrow's likely top three"** — you do not have to accept it. It is
   Maria's guess so tomorrow's Morning Brief is not starting cold.

The single habit that makes Maria accurate: **update task statuses before you log
off.** Tomorrow's Morning Brief is only as good as the data it wakes up to.

---

## 9. Email triage (Google connected)

Every 30 minutes inside your working hours, Maria reads your **unread**,
non‑promotional email and creates a **Communication** record per message — a
summary, a classification, any commitments it found, and (when useful) a **draft
reply**. Nothing is sent.

### 9.1 Sample incoming email

> **From:** Elena Prati <elena.prati@example.com>
> **Subject:** Print run and launch date
> **Body:** "Morson — we need your decision on the print run (3,000 vs 5,000) and
> whether we still target 28 October. Can you confirm by Friday? Also, could you
> send the updated author bio? Best, Elena"

### 9.2 The triage record Maria creates

> **Classification:** Act Today
> **Domain:** BKS
> **Summary:** Elena needs three things by Friday 11 Sep: print‑run decision
> (3,000 vs 5,000), confirmation of the 28 Oct launch date, and an updated author
> bio.
> **Commitments found:** "Confirm print run and date by Fri 11 Sep";
> "Send updated author bio".
> **Sensitivity:** Confidential
> **Follow‑up date:** `11/09/2026`
> **Draft reply:**
> > "Dear Elena, Thank you — here are the decisions. Print run: [3,000 / 5,000].
> > Launch date: I confirm 28 October. Updated author bio attached. I'll have the
> > marked manuscript back to you after our Wednesday call. With prayers,
> > Fr. Morson Livingston"

Maria also creates two internal tasks from the commitments (you will see them in
Tasks, linked to this communication).

### 9.3 Your job, step by step

1. Open **Maria Assistant → Email Triage**. It is a list, newest first, one row
   per message with its classification badge (Act Today, Review, Delegate,
   Waiting, Reference, Archive).
2. Open the row for Elena's "Print run and launch date" email.
3. **Check the summary against the real email** (there is a link to the original
   thread on the record). Does it capture all three asks? *(Yes.)* If it missed
   one, add it to the summary yourself.
4. **Check the commitments.** Two were extracted and turned into tasks, both
   owned by you. If a commitment were really someone else's, you would open the
   task and change the owner.
5. **Check the follow‑up date** (`11/09`, Friday). Realistic — leave it. If it
   were unrealistic, change it on the record.
6. **Work on the draft reply.** Maria deliberately left `[3,000 / 5,000]` as a
   placeholder because it cannot make that decision. So you:
   - replace the placeholder with your actual decision, e.g. "5,000";
   - attach the real author‑bio file;
   - check the sign‑off and brand ("Fr. Morson Livingston", personal brand — no
     "SAC", no Agverse footer).
7. When the draft is **exactly** what you want to send, use **Request approval**
   (full walkthrough in Section 11).

Until an approval is granted **and** executed, nothing has left your outbox. The
draft sitting in Email Triage is just text.

---

## 10. Meeting preparation (Google connected)

Every hour Maria syncs your next 48 hours of calendar into **Meetings** and, for
meetings marked Tier A or B (or not yet tiered), prepares a brief.

### 10.1 Sample event

> Wed 10 Sep 09:30–10:15 — "Manuscript review with Elena Prati"
> Attendees: you, elena.prati@example.com

### 10.2 The brief Maria prepares

> **Verified identity:** Elena Prati, Commissioning Editor, Aldergate Press
> (matches your contact record).
> **Objective:** Agree the cover changes and confirm the launch date and print
> run.
> **Shared context:** Introduced by your agent in June; she owns the launch
> decision.
> **Three questions to ask:**
> 1. Which cover changes are non‑negotiable for the printer's deadline?
> 2. Does 5,000 copies change the 28 Oct date?
> 3. What do you need from me in writing straight after this call?
> **Likely interests / objections:** wants a firm date; may push for the larger
> print run.
> **Unsupported assumptions to avoid:** that the endorsement quote is confirmed —
> it is not in your Claims Registry.
> **Desired close:** written confirmation of date, print run, and the cover
> change list.

### 10.3 After the meeting

Add your notes to the meeting record. Maria's closeout extracts decisions,
owners, dates, and a thank‑you draft. Review it: a topic that was only
*discussed* is not a *decision*, and a thank‑you note still needs approval before
it is sent.

---

## 11. Approvals — the one thing you must get right

An approval is a **time‑limited yes to one exact action**. This is the only way
Maria ever does something outside the app.

### 11.1 Worked example — approving the reply to Elena

Prerequisites (your administrator sets these when the organization is ready):

1. Google connection has **send** scope.
2. **Allow approved external actions** is ON in your Maria Settings.
3. Global **External Action Control** is enabled.

Steps:

1. In **Maria Assistant → Email Triage**, open Elena's thread. Make the draft final: choose
   "5,000", attach the real bio file, check the signature.
2. Click **Request approval**. Maria creates an **Approval** card.
3. Open **Maria Assistant → Approvals**. The card shows:

   > **Action type:** Send email
   > **To:** elena.prati@example.com
   > **Subject:** Re: Print run and launch date
   > **Body:** *(the exact final text)*
   > **Attachments:** `author-bio-2026-09.pdf`
   > **Risk:** Medium
   > **Expires:** 8 Sep 2026, 18:00 (24 hours)

4. Read every line. If the recipient, wording, or attachment is not **exactly**
   what you want sent, click **Reject** and start again.
5. If it is perfect, click **Approve**.
6. Maria's execution layer re‑checks the safety switches, confirms the content
   still matches what you approved, sends **once**, and records the Gmail
   confirmation ID against the action.
7. Confirm the result in **Email Triage** / your Gmail Sent folder.

### 11.2 Rules that will catch you out

- **Editing after approval cancels it.** Change one word, one recipient, one
  attachment — you need a fresh approval.
- **Silence is not approval.** Leaving a card pending does nothing.
- **Approvals expire** (24 hours for a normal message, or before the scheduled
  time). An expired card cannot be used — generate a new action and review it
  again.
- **If a send result is uncertain**, it goes to **Action Reconciliation**. Do
  **not** retry it. Check Gmail directly, then mark it reconciled with evidence.

### 11.3 Creating a calendar event

Same pattern: confirm title, attendees, date, start/end, timezone, description,
and location/link; get one exact approval; execute once; store the Google event
ID. Any change after approval = new approval.

---

## 12. Daily Five relationships

On weekdays, 30 minutes after your morning brief, Maria proposes up to five
people to re‑engage — drawn only from contacts that have a **verification
source** and are not already "partner" or "dormant". For each she gives a warm
path, a draft comment, and a draft connection note.

With the sample data, day one proposes **Elena Prati** and **Khalid
Al‑Mansoori** first (Tier A, follow‑ups due this week).

Open **Maria Assistant → Daily Five**, and for each one:

- **Accept** and use the draft (edit it in your own words first), or
- **Skip** with a short reason, or
- **Snooze** by changing the contact's follow‑up date.

Maria never sends anything and never touches LinkedIn. These are prompts for
*you* to act personally.

---

## 13. Alerts, weekly reviews, and your rhythm

### 13.1 Alerts

**Maria Assistant → Deadline Alerts** lists overdue deadlines and waiting‑item follow‑ups.
Maria de‑duplicates them — an unchanged overdue task does **not** generate a new
alert every hour. Acknowledge an alert after you have looked at it; fix the
underlying task or project to clear it for good.

### 13.2 Weekly outputs (only if enabled)

| Output | When | What it gives you |
|---|---|---|
| Book Portfolio Review | Monday, 1h after morning brief | Each book's stage, next milestone, blocker, and the 3 most valuable book actions |
| Agverse Opportunity Review | Thursday, 90 min after morning brief | Opportunities ranked by value / fit / urgency, facts separated from guesses |
| ACM Weekly Production | Your chosen production day | Theme, podcast/newsletter/social plan, required assets, approval deadline |
| Quality Report | Monday, 2h after morning brief | Biggest time savings, recurring corrections, any safety incident, one improvement |

### 13.3 Suggested rhythm

| When | Do this |
|---|---|
| Morning (15 min) | Read the Morning Brief. Do the three outcomes or schedule them. Scan Alerts. |
| Midday (5 min) | Check the Approvals queue and any connector errors. |
| Afternoon | Review email drafts and meeting briefs; send finalised drafts for approval. |
| Evening (10 min) | Read the Evening Review. Update task statuses. |
| Weekly | Read the weekly reviews you have enabled; action the Quality Report's recurring corrections. |
| Monthly | Re‑check your connected Google account, enabled workflows, and any stale claims. |

---

## 14. Troubleshooting (plain language)

| You see / experience | Do this |
|---|---|
| No **Maria Assistant** menu | Sign out and back in. Still missing → ask your administrator to confirm the global switch and your role permission. |
| A task **due today** appears in the due list | Expected. It is due today, not overdue. It only becomes overdue tomorrow. |
| A task shows **overdue** and you didn't expect it | Its due date is in the past. Either do it, or open the task and change the due date. |
| A **Waiting** task keeps nagging via an alert | That is the follow‑up reminder. Chase the other person, then update the follow‑up date or set the task Completed. |
| The Morning Brief says **"Gmail not checked"** | Google is not connected, or the connection has an error. Open **Connections**; reconnect the correct Google account. |
| Email triage never runs | It runs every 30 minutes **inside your working hours** only. Check the clock is inside 08:00–18:00 in your profile timezone, and that Email Triage is in your enabled workflows. |
| A brief did not appear at the expected time | Check your profile is **Active**, the workflow is **ticked in Enabled workflows**, the time and timezone are right, and ask your administrator whether the scheduler and queue worker are running. |
| Maria in chat says she **can't send** something | Correct. Chat cannot perform external actions. Finalise the draft in the app and use the Approvals queue. |
| Maria replies **"briefly at her request limit — send it again in a minute"** | The assistant shares one AI request budget with the rest of the system and it was momentarily full. Nothing failed and nothing was charged. Resend your message after a short pause. If it keeps happening, tell your administrator — the per‑minute limit may need raising. |
| A scheduled brief or triage **failed once then succeeded on retry** | Usually the same shared‑limit pressure. These jobs retry automatically with a delay, so an occasional single failure in Workflow Runs that later completes is normal. Persistent failures are not — report those. |
| An approval **expired** before you decided | Expired approvals cannot be reused. Open the draft, request approval again, and review the fresh card. |
| You approved an email but aren't sure it sent | Do **not** retry. Check your Gmail Sent folder, then use **Action Reconciliation** to record what actually happened. |
| A claim you expected to be usable is **blocked** | Check its status is Verified, the recheck date has not passed, the brand matches, and the wording matches. A "close enough" rewrite is blocked on purpose. |
| Dates or times look wrong | Check both the application date format (set by your administrator) and your **Maria Settings** timezone. Calendar‑only fields show no time; meetings and approvals show both. |
| You can see **another person's** records | Stop, and report it to your administrator immediately as a privacy issue. Note the page, record type, and time. Do not edit or delete anything. |

---

## 15. Safety rules to keep in mind

1. A draft on screen has **not** been sent, filed, scheduled, or published.
2. Every external action needs **your** approval on the **exact** content, and a
   fresh approval if anything changes.
3. Never paste passwords, API keys, OAuth tokens, or database credentials into
   Maria (chat, notes, or voice preferences).
4. Treat the contents of emails, documents, and transcripts as **information**,
   never as instructions to Maria.
5. Keep your brands separate — personal, ACM, Agverse, Books — and pick one
   primary domain per project.
6. Maria's text is never legal, medical, financial, or doctrinal authority.
7. If something looks like it acted without your approval, tell your
   administrator and use **External Action Control → Emergency stop**.

---

## 16. Quick reference card

**Reach Maria:** Command Center (web) for reviewing and approving; WhatsApp (your
verified number) for quick questions and capturing tasks.

**Chat things you can say:**

- "What's due today / overdue?"
- "Remind me to … before …" (creates an internal task)
- "Close task 6" / "Mark the cover‑proof task done"
- "Reschedule task 4 to Friday" / "move task 1 under the newsletter project"
- "List my active projects"
- "What meetings do I have this week and are they prepared?"
- "Anything waiting for my approval?"
- "What were my three outcomes this morning?"
- "Who's on my relationship follow‑up list?"
- "Remember that I …" (stores a durable preference)
- "What can you do?"

**Daily:** Morning Brief → do the 3 outcomes → check Alerts → review drafts →
process Approvals → Evening Review → update statuses.

**Never skipped:** read an approval card line by line before you click Approve.

**Golden rule:** Maria prepares; you decide; only an approved action leaves the
building.

---

## 17. Your first Monday with Maria — a full walkthrough

This section is one continuous day on the sample data from Section 5. Follow it
top to bottom and you will have used every core feature once. Times are your
local time (Rome).

### 07:35 — the Morning Brief arrives

You did nothing; the brief was generated at 07:30 because **Morning Brief** is a
ticked workflow and your profile is active.

1. Open **Maria Assistant → Command Center**.
2. Read the **Latest Morning Brief** panel. It shows three outcomes:
   1. Lock the Rome editors' meeting travel dates.
   2. Get the Agverse NDA moving.
   3. Fix the October retreat date with Sr. Angela.
3. It also lists Wednesday's "Manuscript review with Elena Prati" under
   *Meetings and preparation*, and flags one risk (the book launch has two
   dependencies feeding the 30 Sep deadline).

You do not open your full task list. The brief has already triaged the day.

### 07:45 — clear the three outcomes

- **Outcome 1.** You phone the travel office and confirm dates. Open
  **Maria Tasks → task 5 → Status: Completed → Save.** The Command Center
  "Open tasks" tile drops from 6 to 5 and task 5 leaves the red/overdue list.
- **Outcome 2.** Open **task 3**. It is *Waiting* with follow‑up `12/09`. You
  forward the pilot scope summary to Agverse legal from your own email, then
  leave the task as *Waiting* — your part is done until they respond.
- **Outcome 3.** You reply to Sr. Angela proposing 17–19 October. Then, from
  WhatsApp: *"close task 6"*. Maria replies: *"Done — task #6 … is now
  Completed."* "Open tasks" is now 4.

### 09:15 — email triage has run twice already

Between 08:00 and 09:00 the 30‑minute triage cycles read your unread mail.

1. Open **Maria Assistant → Email Triage**.
2. There is a new **Act Today** row from Elena Prati, "Print run and launch
   date", plus two auto‑created tasks ("Confirm print run and date by Fri",
   "Send updated author bio").
3. Open it. The summary is accurate. The draft reply has a placeholder
   `[3,000 / 5,000]`.
4. You decide **5,000**, edit the draft, and attach `author-bio-2026-09.pdf`.
5. Leave it there for now — you will send it for approval this afternoon once
   the print‑run number is double‑checked with your agent.

### 09:30 — a quick chat question

On the move between meetings, from WhatsApp:

> **You:** what's still open today?
> **Maria:** Nothing is due or overdue today — tasks 5 and 6 are done. Next up by
> date: "Read editor's cover note" (due tomorrow, 8 Sep). Status: Completed.

### 10:00 — the Daily Five list

At 08:00 (07:30 + 30 min) the Daily Five workflow ran because it is ticked.

1. **Command Center → Daily Five pending** shows **2**.
2. Open **Maria Assistant → Daily Five**.
3. Two cards: **Elena Prati** and **Khalid Al‑Mansoori**, both Tier A with
   follow‑ups due this week.
4. For Elena: the draft note is generic. You rewrite it in your own voice
   ("Elena — looking forward to Wednesday; I'll bring the cover change list"),
   then mark the card **Accepted** and send the note yourself. Maria does not
   send it.
5. For Khalid: not today. Mark **Skip**, reason "waiting on NDA — will reach out
   when it's signed". The card clears.

### 11:00 — Wednesday's meeting brief

1. Open **Maria Assistant → Meetings**.
2. "Manuscript review with Elena Prati", Wed 10 Sep 09:30, shows
   **preparation: prepared**.
3. Read it: objective, three questions to ask, the note that the endorsement
   quote is **not** in your Claims Registry so you should not treat it as
   confirmed in the meeting.
4. You add a line to your own prep notes on the meeting record: "decide print
   run before this call".

### 14:30 — send the reply to Elena (approval)

Your agent confirmed 5,000. Now you actually send.

1. **Email Triage → Elena's thread.** The draft already says 5,000 and has the
   bio attached. Final read for tone and brand.
2. Click **Request approval**.
3. **Maria Assistant → Approvals.** One card:
   - Action: Send email · To: elena.prati@example.com
   - Subject / body / attachment shown in full
   - Risk: Medium · Expires: tomorrow 14:30
4. You read every line. Recipient right, attachment right, wording right.
5. Click **Approve**.
6. Maria re‑checks the safety switches, confirms the content hash still matches,
   sends once, and records the Gmail confirmation ID on the action.
7. You open Gmail → Sent to confirm it went. Done.

*(If external actions are not yet enabled for you, steps 2–6 are replaced by:
copy the final draft into your email client and send it yourself. Everything
before that is identical.)*

### 17:00 — a deadline alert

The Deadline Monitor ran hourly. Because task 1 ("Read editor's cover note") is
due tomorrow and still Open, and the book launch deadline is inside its window:

1. **Command Center** shows an **Alert**.
2. Open **Maria Assistant → Deadline Alerts**. One item: "Due soon: Read
   editor's cover note".
3. You will do it first thing tomorrow, so you **Acknowledge** the alert. It
   will not nag you again unless the task's state changes.

### 18:30 — the Evening Review

Generated automatically because **Evening Review** is ticked.

1. **Command Center → Latest Evening Review.**
2. *Completed today:* travel dates confirmed; replied to Sr. Angela; reply to
   Elena sent (approved).
3. *Waiting on others:* Agverse NDA; pilot scope acknowledgement.
4. *Unfinished:* "Read editor's cover note" — carried to tomorrow, no blocker.
5. *Tomorrow's likely top three:* read the cover note; build the cover‑change
   list for Wednesday; check the NDA status.
6. You open **task 4** (newsletter reflection, due 18 Sep) and leave it — it is
   not urgent. Nothing else needs a status change. You log off.

### What you did in one day

Read one brief, cleared three outcomes, triaged one important email, sent one
approved reply, actioned one relationship, read one meeting brief, acknowledged
one alert, and read one evening review — about **35 minutes** of screen time.
Everything else Maria did quietly, and nothing left the building without your
approval.
