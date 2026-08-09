<?php

return [

    'auth' => [
        'two_factor' => [
            'heading'       => 'Autenticazione a due fattori',
            'code_label'    => 'Codice di autenticazione',
            'code_helper'   => 'Inserisci il codice a 6 cifre dalla tua app di autenticazione, oppure un codice di recupero.',
            'back_to_login' => 'Torna al login',
        ],
    ],

    'nav' => [
        'dashboard'        => 'Pannello',
        'services'         => 'Servizi',
        'service_requests' => 'Richieste di Servizio',
        'conversations'    => 'Conversazioni',
        'faqs'             => 'Domande Frequenti',
        'users'            => 'Utenti',
        'roles'            => 'Ruoli',
        'system_settings'  => 'Impostazioni di Sistema',
        'whatsapp_accounts' => 'Account WhatsApp',
        'groups' => [
            'administration' => 'Amministrazione',
        ],
    ],

    'stats' => [
        'new_requests'        => 'Nuove Richieste',
        'awaiting_staff'      => 'In attesa del personale',
        'in_progress'         => 'In Corso',
        'being_handled'       => 'In gestione',
        'completed'           => 'Completate',
        'all_time'            => 'Totale',
        'active_conversations'=> 'Conversazioni Attive',
        'open_chats'          => 'Chat attualmente aperte',
    ],

    'service' => [
        'label'        => 'Servizio',
        'label_plural' => 'Servizi',
        'tabs' => [
            'basic'  => 'Informazioni Base',
            'bot'    => 'Impostazioni Bot',
            'fields' => 'Campi Dati',
        ],
        'sections' => [
            'identity'   => 'Identità',
            'labels'     => 'Etichette Multilingua',
            'bot_config' => 'Configurazione Bot',
            'fields'     => 'Campi Dati',
        ],
        'fields' => [
            'label_en'         => 'Nome Servizio',
            'color'            => 'Colore Badge',
            'is_active'        => 'Attivo',
            'sort_order'       => 'Ordine',
            'prompt_label'     => 'Etichetta Prompt',
            'tool_name'        => 'Nome Funzione Tool',
            'tool_description' => 'Descrizione Tool',
        ],
    ],

    'service_request' => [
        'label'        => 'Richiesta di Servizio',
        'label_plural' => 'Richieste di Servizio',
        'sections' => [
            'details' => 'Dettagli Richiesta',
        ],
        'fields' => [
            'phone'        => 'Telefono WhatsApp',
            'service'      => 'Servizio',
            'status'       => 'Stato',
            'payload'      => 'Dettagli Raccolti',
            'staff_notes'  => 'Note del Personale',
            'phone_short'  => 'Telefono',
            'received'     => 'Ricevuto',
            'last_updated' => 'Ultimo Aggiornamento',
        ],
        'status' => [
            'new'         => 'Nuova',
            'in_progress' => 'In Corso',
            'done'        => 'Completata',
        ],
        'actions' => [
            'in_progress'  => 'In Corso',
            'done'         => 'Completata',
            'mark_as_done' => 'Segna come Completata',
        ],
    ],

    'conversation' => [
        'label'        => 'Conversazione',
        'label_plural' => 'Conversazioni',
        'sections' => [
            'details'    => 'Dettagli',
            'transcript' => 'Trascrizione della conversazione',
        ],
        'fields' => [
            'phone'         => 'Telefono',
            'last_activity' => 'Ultima Attività',
            'started_at'    => 'Iniziata',
            'language'      => 'Lingua',
            'role'          => 'Da',
            'message'       => 'Messaggio',
        ],
        'roles' => [
            'user'      => 'Cliente',
            'assistant' => 'Bot',
        ],
        'steps' => [
            'NEW'           => 'Nuova',
            'AWAIT_LANG'    => 'In attesa di lingua',
            'AWAIT_SERVICE' => 'In attesa di servizio',
            'IN_SERVICE'    => 'In servizio',
            'DONE'          => 'Completata',
        ],
        'actions' => [
            'reset' => 'Reimposta',
        ],
    ],

    'faq' => [
        'label'        => 'FAQ',
        'label_plural' => 'Domande Frequenti',
        'sections' => [
            'faq'    => 'FAQ',
            'answer' => 'Risposta',
        ],
        'fields' => [
            'applies_to'   => 'Si applica a',
            'all_services' => 'Tutti i servizi',
            'active'       => 'Attivo',
            'question'     => 'Domanda di riferimento (per il personale)',
            'keywords'     => 'Frasi trigger',
            'keywords_help'=> 'Parole o frasi brevi che devono attivare questa risposta, es. "prezzo", "quanto costa", "orari di apertura".',
            'triggers'     => 'Trigger',
        ],
    ],

    'user' => [
        'label'        => 'Utente',
        'label_plural' => 'Utenti',
        'sections' => [
            'account' => 'Dettagli Account',
            'roles'   => 'Ruoli e Permessi',
        ],
        'fields' => [
            'name'             => 'Nome',
            'email'            => 'Email',
            'password'         => 'Password',
            'confirm_password' => 'Conferma Password',
            'password_help'    => 'Lascia vuoto per mantenere la password attuale (durante la modifica).',
            'roles'            => 'Ruoli',
            'roles_help'       => 'Assegna uno o più ruoli. I ruoli controllano cosa può vedere e fare l\'utente nel pannello admin.',
            'verified'         => 'Verificato',
            'not_verified'     => 'Non verificato',
            'role'             => 'Ruolo',
        ],
    ],

    'whatsapp_account' => [
        'label'        => 'Account WhatsApp',
        'label_plural' => 'Account WhatsApp',
        'sections' => [
            'identity'    => 'Identità',
            'credentials' => 'Credenziali API Meta',
        ],
        'fields' => [
            'name'             => 'Nome',
            'name_help'        => 'Un\'etichetta amichevole per identificare questo numero, es. "Vendite" o "Assistenza".',
            'phone_number_id'  => 'ID Numero di Telefono',
            'phone_number_id_help' => 'Si trova in Meta Business Suite → WhatsApp → Configurazione API.',
            'waba_id'          => 'ID Account WhatsApp Business',
            'access_token'     => 'Token di Accesso',
            'access_token_help'=> 'Token di accesso permanente o temporaneo di Meta per questo numero.',
            'api_version'      => 'Versione API',
            'is_active'        => 'Attivo',
            'is_active_help'   => 'Gli account inattivi non invieranno né riceveranno messaggi WhatsApp.',
            'is_default'       => 'Predefinito',
            'is_default_help'  => 'Usato come ripiego quando un numero non può essere altrimenti identificato.',
        ],
    ],

    'settings' => [
        'title'   => 'Impostazioni di Sistema',
        'save'    => 'Salva Impostazioni',
        'saved'   => 'Impostazioni salvate. Aggiorna la pagina per applicare le modifiche al tema.',
        'tabs' => [
            'general'    => 'Generale',
            'appearance' => 'Aspetto',
            'security'   => 'Sicurezza',
            'whatsapp'   => 'WhatsApp',
            'claude'     => 'Claude AI',
            'bot'        => 'Comportamento Bot',
            'email'      => 'Email',
        ],
        'sections' => [
            'application'  => 'Applicazione',
            'color_theme'  => 'Tema Colori',
            'panel_mode'   => 'Modalità Pannello',
            'auth_bg'      => 'Sfondo Pagina di Accesso',
            'branding'     => 'Asset Branding',
            'two_factor'   => 'Autenticazione a Due Fattori',
            'wa_api'       => 'WhatsApp Business API',
            'claude_api'   => 'Anthropic Claude API',
            'response'     => 'Impostazioni Risposta',
            'mail_sender'  => 'Mittente Email',
        ],
        'fields' => [
            'app_name'         => 'Nome Applicazione',
            'app_tagline'      => 'Slogan',
            'support_email'    => 'Email Supporto',
            'maintenance_mode'        => 'Modalità Manutenzione',
            'maintenance_help'        => 'Se abilitata, il pannello admin mostrerà un avviso di manutenzione.',
            'default_language'        => 'Lingua Predefinita',
            'default_language_help'   => 'La lingua mostrata nella pagina di accesso e usata come predefinita. I singoli admin possono comunque cambiare lingua dal selettore in alto.',
            'two_factor_enabled'      => 'Abilita Autenticazione a Due Fattori',
            'two_factor_enabled_help' => 'Attiva o disattiva la funzione di autenticazione a due fattori per l\'intero pannello admin. Se disattivata, nessuno dovrà inserire un codice al login e gli admin non potranno attivarla dal proprio profilo — le configurazioni esistenti restano salvate ma inattive finché non viene riattivata.',
        ],
    ],

    'dashboard' => [
        'greeting' => [
            'morning'   => 'Buongiorno',
            'afternoon' => 'Buon Pomeriggio',
            'evening'   => 'Buona Sera',
        ],
        'quick' => [
            'pending' => 'In Attesa',
            'active'  => 'Chat Attive',
            'today'   => 'Oggi',
        ],
        'chart' => [
            'heading'       => 'Richieste di Servizio',
            'last_7_days'   => 'Ultimi 7 giorni',
            'last_14_days'  => 'Ultimi 14 giorni',
            'last_30_days'  => 'Ultimi 30 giorni',
            'dataset_label' => 'Richieste',
        ],
        'conversations_chart' => [
            'heading'       => 'Conversazioni',
            'last_7_days'   => 'Ultimi 7 giorni',
            'last_14_days'  => 'Ultimi 14 giorni',
            'last_30_days'  => 'Ultimi 30 giorni',
            'dataset_label' => 'Conversazioni',
        ],
        'recent' => [
            'heading' => 'Richieste Recenti',
        ],
    ],

    'profile' => [
        'sections' => [
            'picture'    => 'Foto Profilo',
            'details'    => 'Informazioni Personali',
            'security'   => 'Cambia Password',
            'two_factor' => 'Autenticazione a Due Fattori',
        ],
        'descriptions' => [
            'picture'    => 'Carica un\'immagine quadrata. Verrà visualizzata come cerchio.',
            'details'    => 'Aggiorna il tuo nome e indirizzo email.',
            'security'   => 'Lascia vuoto per mantenere la password attuale.',
            'two_factor' => 'Aggiungi un ulteriore livello di sicurezza al tuo account usando un\'app di autenticazione.',
        ],
        'fields' => [
            'avatar' => 'Avatar',
        ],
        'two_factor' => [
            'enabled'                  => 'L\'autenticazione a due fattori è attiva.',
            'disabled'                 => 'L\'autenticazione a due fattori non è attiva.',
            'disabled_globally'        => 'Un amministratore ha disattivato l\'autenticazione a due fattori per questa applicazione.',
            'enable_action'            => 'Attiva',
            'disable_action'           => 'Disattiva',
            'disable_confirm_heading'  => 'Disattivare l\'autenticazione a due fattori?',
            'disable_confirm_body'     => 'Non ti verrà più richiesto un codice per accedere.',
            'show_recovery_codes'      => 'Mostra codici di recupero',
            'regenerate_recovery_codes' => 'Rigenera codici di recupero',
            'setup_heading'            => 'Scansiona il codice QR',
            'setup_description'        => 'Scansiona questo codice QR con la tua app di autenticazione (Google Authenticator, Authy, 1Password, ecc.), quindi inserisci il codice a 6 cifre generato per confermare.',
            'secret_label'             => 'Oppure inserisci questo codice manualmente',
            'code_label'               => 'Codice di conferma',
            'code_helper'              => 'Inserisci il codice a 6 cifre dalla tua app di autenticazione.',
            'invalid_code'             => 'Il codice fornito non è valido.',
            'confirm_action'           => 'Conferma',
            'recovery_codes_heading'   => 'Codici di recupero',
            'recovery_codes_description' => 'Conserva questi codici in un luogo sicuro. Ognuno può essere usato una sola volta per accedere se perdi l\'accesso alla tua app di autenticazione.',
            'enabled_notification'    => 'Autenticazione a due fattori attivata.',
            'disabled_notification'   => 'Autenticazione a due fattori disattivata.',
            'regenerated_notification' => 'Codici di recupero rigenerati.',
        ],
    ],

    'brand' => [
        'headline_dark'  => 'Il tuo Centro di Controllo :name.',
        'headline_light' => 'Gestisci il tuo Bot AI con sicurezza.',
        'admin_badge'    => 'Admin',
        'footer'         => 'Powered by Claude AI & WhatsApp Business API',
        'features' => [
            'Conversazioni WhatsApp potenziate dall\'AI',
            'Abbinamento e instradamento FAQ intelligente',
            'Gestione richieste di servizio in tempo reale',
            'Cronologia completa delle conversazioni e analisi',
        ],
    ],

];
