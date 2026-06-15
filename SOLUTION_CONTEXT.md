# WhatsApp AI Service Router — Complete Solution Context

A self-contained reference for an AI-powered WhatsApp intake bot. A customer messages a
single WhatsApp Business number; the bot collects what they need across multiple services
and languages, then saves a validated record for staff to action. Stack: Laravel +
WhatsApp Cloud API + Claude Haiku, hosted on Hetzner (managed via Ploi).

---

## 1. What this is

When someone messages the business on WhatsApp, the bot:

1. asks them to pick a language (tap a menu),
2. asks what service they need — ticket booking, driving license, immigration (tap a menu),
3. hands the conversation to Claude, which collects the required details for that service
   one question at a time, in the chosen language,
4. once every required detail is collected and the customer confirms, saves a structured
   record to a database table,
5. confirms to the customer that the request is recorded.

Staff then pull those records from the table for further processing.

---

## 2. Decisions locked in

| Decision | Choice | Why |
|---|---|---|
| Scope | Single business, single number | No multi-tenant / agency layer needed — simplest codebase. |
| Automation | Fully automated **intake** | Bot collects + validates; it does not file/book anything itself. |
| Endpoint | Collect → save → human pulls | License/immigration filing happens on government portals with no API, so the realistic endpoint is a complete, validated record + confirmation. |
| Channel | WhatsApp **Cloud API** (Meta) | Free to host, no per-message BSP markup. Twilio only for quick prototyping. |
| Brain | **Claude Haiku** via API | Cheap, fast, multilingual, good enough for intake. |
| Routing | Native WhatsApp menus, **not** AI | Tap-only menus are 100% reliable and cost nothing; AI only handles the open-ended intake. |

---

## 3. Architecture & stack

- **WhatsApp Cloud API** — receives messages via webhook, sends replies. Meta-hosted, free.
- **Laravel app** (your Hetzner + Ploi box) — webhook endpoint, conversation state, queue workers.
- **Claude Haiku** — called only during the intake step; uses tool calling to capture structured data.
- **Database** — two tables: `conversations` (state) and `service_requests` (the saved records).
- **Queue + Supervisor** — the webhook returns `200` instantly and pushes work to a queue so Meta never retries.

Flow at a glance:

```
Customer ──WhatsApp──> Cloud API ──webhook──> Laravel (200 fast)
                                                  │
                                          dispatch to queue
                                                  │
                                   HandleIncomingMessage (state machine)
                                          │            │
                                  native menus     ClaudeAgent ──> Anthropic API
                                          │            │
                                          └──> WhatsAppClient ──> reply to customer
                                                  │
                                       on confirm: save ServiceRequest (status=new)
```

---

## 4. Conversation flow (state machine)

State is stored per phone number in `conversations.step`:

```
NEW ──> AWAIT_LANG ──> AWAIT_SERVICE ──> IN_SERVICE ──> DONE
```

- **NEW** — send language list, move to `AWAIT_LANG`.
- **AWAIT_LANG** — store language tap, send service buttons, move to `AWAIT_SERVICE`.
- **AWAIT_SERVICE** — store service tap, move to `IN_SERVICE`, Claude greets + asks first question.
- **IN_SERVICE** — every customer message goes to Claude until all details are confirmed.
- **DONE** — request saved; next message starts a fresh flow.

Typing `menu`, `start`, `hi`, `ciao` (etc.) resets to `NEW` at any point.

---

## 5. The core pattern: native menus + AI intake

The single most important design choice. Do **not** let the AI run the menu — it
hallucinates options and misreads input. Instead:

- **Language + service selection** use WhatsApp's native interactive messages (a list for
  language, reply buttons for services). Tap-only, unambiguous, zero AI cost.
- **Only after** language + service are captured does Claude take over, scoped by a system
  prompt to that one service and locked to that one language.

This keeps the routing deterministic and reserves the AI for the part it's actually good at —
open-ended, multilingual data collection.

---

## 6. The intake mechanism (Claude tool calling)

Each service defines an **intake schema** in `config/services_bot.php`. That schema is handed
to Claude as a *tool*. Claude fills the parameters conversationally; the schema's `required`
list is what forces completeness — Claude literally cannot submit with missing fields.

On each turn, Claude returns one of two things:

- **text** → still collecting / answering a question → relay it to the customer.
- **tool_use** → all required fields collected and the customer confirmed → execute the save.

The system prompt instructs Claude to ask one field at a time, summarise, wait for explicit
confirmation, and only then call the tool. Adding or changing a service is a config-only edit.

---

## 7. Saving & staff handoff

When Claude calls the tool, the job writes a row to `service_requests`:

- `wa_phone`, `service`, `payload` (the collected fields as JSON), `status = 'new'`, `staff_notes`.

Staff pull from there — e.g. `ServiceRequest::where('status','new')->latest()->get()` — and
flip `status` to `in_progress` / `done`. A Filament/Nova resource or a notification on new
rows are the natural additions.

---

## 8. Cost model

- **Inbound conversations are essentially free.** Because the customer messages first, a
  24-hour service window opens in which your free-form replies cost nothing. You only pay
  for an approved template message if *you* re-open a conversation after 24h of silence.
- **Real running cost = Claude Haiku tokens**, which are low for short WhatsApp turns.
- WhatsApp Cloud API itself is free to host (Meta is the provider).

---

## 9. Project file tree

```
config/services_bot.php                          # languages, services, intake schemas
database/migrations/..._create_bot_tables.php    # conversations + service_requests
app/Models/Conversation.php                      # per-customer state
app/Models/ServiceRequest.php                    # the saved record staff retrieve
app/Http/Controllers/WhatsAppWebhookController.php
app/Services/WhatsAppClient.php                  # Cloud API send + parse
app/Services/ClaudeAgent.php                     # tool-calling intake
app/Jobs/HandleIncomingMessage.php               # the state machine
```

---

## 10. Setup

**`.env`**

```env
WHATSAPP_TOKEN=              # permanent token from your Meta app
WHATSAPP_PHONE_ID=           # the phone number ID (not the number)
WHATSAPP_VERIFY_TOKEN=       # any random string you choose
WHATSAPP_VERSION=v21.0
ANTHROPIC_API_KEY=
ANTHROPIC_MODEL=claude-haiku-4-5-20251001
QUEUE_CONNECTION=database
```

**Add to `config/services.php`**

```php
'whatsapp' => [
    'token'        => env('WHATSAPP_TOKEN'),
    'phone_id'     => env('WHATSAPP_PHONE_ID'),
    'verify_token' => env('WHATSAPP_VERIFY_TOKEN'),
    'version'      => env('WHATSAPP_VERSION', 'v21.0'),
],
'anthropic' => [
    'key'   => env('ANTHROPIC_API_KEY'),
    'model' => env('ANTHROPIC_MODEL', 'claude-haiku-4-5-20251001'),
],
```

**Add to `routes/api.php`**

```php
use App\Http\Controllers\WhatsAppWebhookController;

Route::get('/webhook/whatsapp',  [WhatsAppWebhookController::class, 'verify']);
Route::post('/webhook/whatsapp', [WhatsAppWebhookController::class, 'handle']);
```

**Run**

```bash
php artisan queue:table   # if the jobs table doesn't exist yet
php artisan migrate
php artisan queue:work     # run under Supervisor on Hetzner
```

**Point Meta at the webhook** (Meta app dashboard → WhatsApp → Configuration):

- Callback URL: `https://yourdomain/api/webhook/whatsapp`
- Verify token: the same `WHATSAPP_VERIFY_TOKEN`
- Subscribe to the **messages** field.

---

## 11. Extending it

Everything service-related lives in `config/services_bot.php`. To add a service: add a key,
its translated `label`, a `prompt_label` (English description for the model), and the
`tool.fields` schema. No code changes.

Note: WhatsApp reply buttons cap at **3** services. Beyond three, switch
`sendServiceButtons()` to an interactive list (same structure as `sendLanguageList()`).

To add a language: add it to the `languages` array and supply the matching strings under
`replies`. Claude handles the conversation in any language you name in the prompt.

---

## 12. Production checklist

- [ ] Verify Meta's `X-Hub-Signature-256` header against your app secret in the webhook controller.
- [ ] Dedupe by WhatsApp message ID — Meta can deliver the same message more than once.
- [ ] Run `queue:work` under Supervisor so it restarts on failure.
- [ ] Add basic logging/alerting on Anthropic API failures.
- [ ] Decide template messages for any case where you re-engage a customer after 24h.

---

## 13. Next steps / roadmap

1. **Staff admin** — a Filament resource over `service_requests` to work the queue.
2. **New-request notification** — email/Slack the moment a `ServiceRequest` lands.
3. **Per-service true automation** — where an API exists (e.g. ticket booking), execute the
   booking inside the tool handler instead of just saving.
4. **Analytics** — track requests per service/language and drop-off by step.

---

## Appendix — full source code

Every file below is the complete, current source. Paths are relative to the Laravel app root.


### `config/services_bot.php`

```php
<?php

return [

    // Tap-to-select languages shown in the first menu.
    'languages' => [
        'en' => 'English',
        'it' => 'Italiano',
        'bn' => 'বাংলা',
    ],

    // Static, translated strings (no AI needed for these).
    'replies' => [
        'choose_service' => [
            'en' => 'What can we help you with today?',
            'it' => 'Come possiamo aiutarti oggi?',
            'bn' => 'আজ আমরা কীভাবে সাহায্য করতে পারি?',
        ],
        'confirmation' => [
            'en' => 'Thank you! Your request has been recorded. Our team will get back to you shortly.',
            'it' => 'Grazie! La tua richiesta è stata registrata. Il nostro team ti contatterà a breve.',
            'bn' => 'ধন্যবাদ! আপনার অনুরোধ রেকর্ড করা হয়েছে। আমাদের টিম শীঘ্রই আপনার সাথে যোগাযোগ করবে।',
        ],
    ],

    /*
     | Each service defines its own intake schema. Claude is given the schema
     | as a tool; it collects every [required] field conversationally and only
     | calls the tool once the customer confirms. The tool call = a saved record.
     |
     | WhatsApp reply buttons allow max 3 services. For more, switch
     | sendServiceButtons() to an interactive list (see WhatsAppClient).
     */
    'services' => [

        'ticket' => [
            'label'        => ['en' => 'Ticket booking', 'it' => 'Biglietti', 'bn' => 'টিকিট বুকিং'],
            'prompt_label' => 'booking a travel ticket',
            'tool' => [
                'name'        => 'submit_ticket_request',
                'description' => 'Save a completed ticket booking request once all required details are collected and the customer has confirmed.',
                'fields' => [
                    'full_name'   => ['type' => 'string',  'required' => true,  'description' => "Customer's full name"],
                    'route'       => ['type' => 'string',  'required' => true,  'description' => 'From and to, e.g. Bologna to Rome'],
                    'travel_date' => ['type' => 'string',  'required' => true,  'description' => 'Date of travel, format YYYY-MM-DD'],
                    'passengers'  => ['type' => 'integer', 'required' => true,  'description' => 'Number of passengers'],
                    'notes'       => ['type' => 'string',  'required' => false, 'description' => 'Any extra preferences'],
                ],
            ],
        ],

        'license' => [
            'label'        => ['en' => 'Driving license', 'it' => 'Patente', 'bn' => 'ড্রাইভিং লাইসেন্স'],
            'prompt_label' => 'a driving license enquiry',
            'tool' => [
                'name'        => 'submit_license_request',
                'description' => 'Save a completed driving license enquiry once all required details are collected and confirmed.',
                'fields' => [
                    'full_name'    => ['type' => 'string', 'required' => true,  'description' => "Customer's full name"],
                    'request_type' => ['type' => 'string', 'required' => true,  'description' => 'New, renewal, or foreign conversion'],
                    'nationality'  => ['type' => 'string', 'required' => true,  'description' => 'Customer nationality'],
                    'phone'        => ['type' => 'string', 'required' => false, 'description' => 'Alternate contact number'],
                ],
            ],
        ],

        'immigration' => [
            'label'        => ['en' => 'Immigration', 'it' => 'Immigrazione', 'bn' => 'ইমিগ্রেশন'],
            'prompt_label' => 'an immigration enquiry',
            'tool' => [
                'name'        => 'submit_immigration_request',
                'description' => 'Save a completed immigration enquiry once all required details are collected and confirmed.',
                'fields' => [
                    'full_name'      => ['type' => 'string', 'required' => true,  'description' => "Customer's full name"],
                    'enquiry_type'   => ['type' => 'string', 'required' => true,  'description' => 'e.g. work permit, family reunification, citizenship'],
                    'nationality'    => ['type' => 'string', 'required' => true,  'description' => 'Customer nationality'],
                    'current_status' => ['type' => 'string', 'required' => false, 'description' => 'Current visa or residence status'],
                ],
            ],
        ],

    ],
];
```

### `database/migrations/2026_06_15_000000_create_bot_tables.php`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Per-customer conversation state machine.
        Schema::create('conversations', function (Blueprint $t) {
            $t->id();
            $t->string('wa_phone')->unique();
            $t->string('step')->default('NEW'); // NEW | AWAIT_LANG | AWAIT_SERVICE | IN_SERVICE | DONE
            $t->string('language')->nullable();
            $t->string('service')->nullable();
            $t->json('history')->nullable(); // recent turns sent to Claude
            $t->timestamps();
        });

        // Completed, validated requests — this is what your staff pull.
        Schema::create('service_requests', function (Blueprint $t) {
            $t->id();
            $t->string('wa_phone')->index();
            $t->string('service');
            $t->json('payload'); // the collected intake fields
            $t->string('status')->default('new'); // new | in_progress | done
            $t->text('staff_notes')->nullable();
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_requests');
        Schema::dropIfExists('conversations');
    }
};
```

### `app/Models/Conversation.php`

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Conversation extends Model
{
    protected $fillable = ['wa_phone', 'step', 'language', 'service', 'history'];

    protected $casts = ['history' => 'array'];
}
```

### `app/Models/ServiceRequest.php`

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ServiceRequest extends Model
{
    protected $fillable = ['wa_phone', 'service', 'payload', 'status', 'staff_notes'];

    protected $casts = ['payload' => 'array'];
}
```

### `app/Http/Controllers/WhatsAppWebhookController.php`

```php
<?php

namespace App\Http\Controllers;

use App\Jobs\HandleIncomingMessage;
use Illuminate\Http\Request;

class WhatsAppWebhookController extends Controller
{
    /**
     * GET /webhook/whatsapp
     * Meta's one-time verification handshake.
     * Note: PHP converts the dots in "hub.mode" etc. to underscores.
     */
    public function verify(Request $request)
    {
        $verifyToken = config('services.whatsapp.verify_token');

        if ($request->query('hub_mode') === 'subscribe'
            && $request->query('hub_verify_token') === $verifyToken) {
            return response($request->query('hub_challenge'), 200);
        }

        return response('Forbidden', 403);
    }

    /**
     * POST /webhook/whatsapp
     * Acknowledge immediately, then process on the queue so Meta never retries.
     */
    public function handle(Request $request)
    {
        // TODO (production): verify the X-Hub-Signature-256 header against your app secret.

        foreach ($request->input('entry', []) as $entry) {
            foreach ($entry['changes'] ?? [] as $change) {
                $value = $change['value'] ?? null;
                if ($value && ! empty($value['messages'])) {
                    HandleIncomingMessage::dispatch($value);
                }
            }
        }

        return response()->json(['status' => 'ok']);
    }
}
```

### `app/Services/WhatsAppClient.php`

```php
<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class WhatsAppClient
{
    private string $url;
    private string $token;

    public function __construct()
    {
        $phoneId = config('services.whatsapp.phone_id');
        $version = config('services.whatsapp.version', 'v21.0');
        $this->url = "https://graph.facebook.com/{$version}/{$phoneId}/messages";
        $this->token = config('services.whatsapp.token');
    }

    public function sendText(string $to, string $body): void
    {
        $this->post([
            'messaging_product' => 'whatsapp',
            'to'                => $to,
            'type'              => 'text',
            'text'              => ['body' => $body],
        ]);
    }

    /** First menu: pick a language (tap-only interactive list). */
    public function sendLanguageList(string $to): void
    {
        $rows = [];
        foreach (config('services_bot.languages') as $code => $name) {
            $rows[] = ['id' => $code, 'title' => $name]; // title max 24 chars
        }

        $this->post([
            'messaging_product' => 'whatsapp',
            'to'                => $to,
            'type'              => 'interactive',
            'interactive'       => [
                'type'   => 'list',
                'body'   => ['text' => 'Please choose your language / Scegli la lingua / ভাষা নির্বাচন করুন'],
                'action' => [
                    'button'   => 'Select',
                    'sections' => [['title' => 'Languages', 'rows' => $rows]],
                ],
            ],
        ]);
    }

    /** Second menu: pick a service (reply buttons, max 3). */
    public function sendServiceButtons(string $to, string $lang): void
    {
        $buttons = [];
        foreach (config('services_bot.services') as $key => $svc) {
            $title = $svc['label'][$lang] ?? $svc['label']['en']; // title max 20 chars
            $buttons[] = ['type' => 'reply', 'reply' => ['id' => $key, 'title' => $title]];
        }

        $prompt = config("services_bot.replies.choose_service.$lang")
            ?? config('services_bot.replies.choose_service.en');

        $this->post([
            'messaging_product' => 'whatsapp',
            'to'                => $to,
            'type'              => 'interactive',
            'interactive'       => [
                'type'   => 'button',
                'body'   => ['text' => $prompt],
                'action' => ['buttons' => array_slice($buttons, 0, 3)],
            ],
        ]);
    }

    /** Normalise an incoming webhook into ['phone', 'text', 'reply_id']. */
    public function parseIncoming(array $value): ?array
    {
        $message = $value['messages'][0] ?? null;
        if (! $message) {
            return null; // delivery / read status callbacks — ignore
        }

        $type = $message['type'] ?? '';
        $text = null;
        $replyId = null;

        if ($type === 'text') {
            $text = $message['text']['body'] ?? null;
        } elseif ($type === 'interactive') {
            $interactive = $message['interactive'] ?? [];
            $replyId = $interactive['list_reply']['id']
                ?? $interactive['button_reply']['id']
                ?? null;
        }

        return [
            'phone'    => $message['from'],
            'text'     => $text,
            'reply_id' => $replyId,
        ];
    }

    private function post(array $payload): void
    {
        Http::withToken($this->token)
            ->acceptJson()
            ->post($this->url, $payload)
            ->throw();
    }
}
```

### `app/Services/ClaudeAgent.php`

```php
<?php

namespace App\Services;

use App\Models\Conversation;
use Illuminate\Support\Facades\Http;

class ClaudeAgent
{
    /**
     * Returns one of:
     *   ['type' => 'text', 'text' => '...']            -> relay to the customer
     *   ['type' => 'tool', 'name' => '...', 'input' => [...]] -> all fields collected & confirmed
     */
    public function handle(Conversation $convo): array
    {
        $service      = config("services_bot.services.{$convo->service}");
        $languageName = config("services_bot.languages.{$convo->language}", 'English');

        $tool     = $this->buildTool($service);
        $system   = $this->buildSystemPrompt($service, $languageName);
        $messages = $this->buildMessages($convo);

        $response = Http::withHeaders([
            'x-api-key'         => config('services.anthropic.key'),
            'anthropic-version' => '2023-06-01',
            'content-type'      => 'application/json',
        ])->timeout(40)->post('https://api.anthropic.com/v1/messages', [
            'model'      => config('services.anthropic.model'),
            'max_tokens' => 1024,
            'system'     => $system,
            'tools'      => [$tool],
            'messages'   => $messages,
        ])->throw()->json();

        foreach ($response['content'] ?? [] as $block) {
            if (($block['type'] ?? '') === 'tool_use') {
                return ['type' => 'tool', 'name' => $block['name'], 'input' => $block['input']];
            }
        }

        $text = collect($response['content'] ?? [])
            ->where('type', 'text')
            ->pluck('text')
            ->implode("\n");

        return ['type' => 'text', 'text' => $text ?: '…'];
    }

    private function buildTool(array $service): array
    {
        $properties = [];
        $required   = [];

        foreach ($service['tool']['fields'] as $name => $f) {
            $prop = ['type' => $f['type']];
            if (! empty($f['description'])) {
                $prop['description'] = $f['description'];
            }
            $properties[$name] = $prop;
            if (! empty($f['required'])) {
                $required[] = $name;
            }
        }

        return [
            'name'         => $service['tool']['name'],
            'description'  => $service['tool']['description'],
            'input_schema' => [
                'type'       => 'object',
                'properties' => $properties,
                'required'   => $required,
            ],
        ];
    }

    private function buildSystemPrompt(array $service, string $languageName): string
    {
        $lines = [];
        foreach ($service['tool']['fields'] as $name => $f) {
            $tag  = ! empty($f['required']) ? '[required]' : '[optional]';
            $desc = $f['description'] ?? '';
            $lines[] = "- {$name} {$tag} {$desc}";
        }
        $fields   = implode("\n", $lines);
        $toolName = $service['tool']['name'];
        $label    = $service['prompt_label'];

        return <<<PROMPT
        You are a friendly assistant handling WhatsApp enquiries.
        Reply ONLY in {$languageName}. Keep every message short, like a real WhatsApp chat.

        You are helping the customer with: {$label}.

        Collect the following details, asking for ONE thing at a time in a natural order:
        {$fields}

        Rules:
        - Never invent or assume values. If the customer is unsure, guide them.
        - When you have every [required] detail, summarise them back and ask the customer to confirm.
        - Only after the customer confirms, call the `{$toolName}` tool with the collected values.
        - Never call the tool before the customer has confirmed.
        PROMPT;
    }

    private function buildMessages(Conversation $convo): array
    {
        $messages = [];
        foreach (($convo->history ?? []) as $turn) {
            $messages[] = ['role' => $turn['role'], 'content' => $turn['content']];
        }

        // Customer just picked the service and there is no turn yet — seed an opener.
        if (empty($messages)) {
            $messages[] = [
                'role'    => 'user',
                'content' => '(The customer just selected this service. Greet them and ask for the first detail.)',
            ];
        }

        return $messages;
    }
}
```

### `app/Jobs/HandleIncomingMessage.php`

```php
<?php

namespace App\Jobs;

use App\Models\Conversation;
use App\Models\ServiceRequest;
use App\Services\ClaudeAgent;
use App\Services\WhatsAppClient;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class HandleIncomingMessage implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public array $value) {}

    public function handle(WhatsAppClient $wa, ClaudeAgent $agent): void
    {
        $msg = $wa->parseIncoming($this->value);
        if (! $msg) {
            return;
        }

        $phone = $msg['phone'];
        $convo = Conversation::firstOrCreate(
            ['wa_phone' => $phone],
            ['step' => 'NEW', 'history' => []]
        );

        // Interactive replies give a reply_id; free text gives the body.
        $input = $msg['reply_id'] ?? trim((string) ($msg['text'] ?? ''));

        // Let the customer restart at any time.
        if (in_array(mb_strtolower($input), ['menu', 'start', 'restart', 'hi', 'hello', 'ciao'])) {
            $convo->update(['step' => 'NEW', 'service' => null, 'history' => []]);
        }

        switch ($convo->step) {
            case 'NEW':
                $wa->sendLanguageList($phone);
                $convo->update(['step' => 'AWAIT_LANG']);
                break;

            case 'AWAIT_LANG':
                if (! array_key_exists($input, config('services_bot.languages'))) {
                    $wa->sendLanguageList($phone); // re-prompt on anything unexpected
                    break;
                }
                $convo->update(['language' => $input, 'step' => 'AWAIT_SERVICE']);
                $wa->sendServiceButtons($phone, $input);
                break;

            case 'AWAIT_SERVICE':
                if (! array_key_exists($input, config('services_bot.services'))) {
                    $wa->sendServiceButtons($phone, $convo->language ?? 'en');
                    break;
                }
                $convo->update(['service' => $input, 'step' => 'IN_SERVICE', 'history' => []]);
                $this->runAgent($wa, $agent, $convo, $phone); // greet + first question
                break;

            case 'IN_SERVICE':
                $this->appendHistory($convo, 'user', $input);
                $this->runAgent($wa, $agent, $convo, $phone);
                break;

            case 'DONE':
            default:
                // Previous request finished — start a fresh one.
                $convo->update(['step' => 'AWAIT_LANG', 'history' => []]);
                $wa->sendLanguageList($phone);
                break;
        }
    }

    private function runAgent(WhatsAppClient $wa, ClaudeAgent $agent, Conversation $convo, string $phone): void
    {
        $reply = $agent->handle($convo);

        if ($reply['type'] === 'tool') {
            // Everything collected and confirmed -> persist for staff to pull.
            ServiceRequest::create([
                'wa_phone' => $phone,
                'service'  => $convo->service,
                'payload'  => $reply['input'],
                'status'   => 'new',
            ]);

            $lang = $convo->language ?? 'en';
            $confirmation = config("services_bot.replies.confirmation.$lang")
                ?? config('services_bot.replies.confirmation.en');

            $wa->sendText($phone, $confirmation);
            $convo->update(['step' => 'DONE', 'history' => []]);

            return;
        }

        $this->appendHistory($convo, 'assistant', $reply['text']);
        $wa->sendText($phone, $reply['text']);
    }

    private function appendHistory(Conversation $convo, string $role, string $content): void
    {
        $history = $convo->history ?? [];
        $history[] = ['role' => $role, 'content' => $content];
        $convo->history = array_slice($history, -20); // bound the context window
        $convo->save();
    }
}
```
