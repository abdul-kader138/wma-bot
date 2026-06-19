<?php

return [

    'nav' => [
        'dashboard'        => 'Pannello',
        'services'         => 'Servizi',
        'service_requests' => 'Richieste di Servizio',
        'conversations'    => 'Conversazioni',
        'faqs'             => 'Domande Frequenti',
        'users'            => 'Utenti',
        'roles'            => 'Ruoli',
        'system_settings'  => 'Impostazioni di Sistema',
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
        'fields' => [
            'phone'         => 'Telefono',
            'last_activity' => 'Ultima Attività',
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

    'settings' => [
        'title'   => 'Impostazioni di Sistema',
        'save'    => 'Salva Impostazioni',
        'saved'   => 'Impostazioni salvate. Aggiorna la pagina per applicare le modifiche al tema.',
        'tabs' => [
            'general'    => 'Generale',
            'appearance' => 'Aspetto',
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
        'recent' => [
            'heading' => 'Richieste Recenti',
        ],
    ],

    'profile' => [
        'sections' => [
            'picture'  => 'Foto Profilo',
            'details'  => 'Informazioni Personali',
            'security' => 'Cambia Password',
        ],
        'descriptions' => [
            'picture'  => 'Carica un\'immagine quadrata. Verrà visualizzata come cerchio.',
            'details'  => 'Aggiorna il tuo nome e indirizzo email.',
            'security' => 'Lascia vuoto per mantenere la password attuale.',
        ],
        'fields' => [
            'avatar' => 'Avatar',
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
