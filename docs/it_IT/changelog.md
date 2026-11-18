# 18/11/2026 | v1.7.1
* Nuovo pulsante per aggiornare i file dei produttori oui.txt e iab.txt dal sito dell'IEEE (configurazione, modalità debug), conservando i file precedenti in caso di errore
* Aggiornamento automatico dei file oui.txt e iab.txt, ogni settimana o ogni mese (configurazione, modalità avanzata)
* Elementi dei plugin associati mostrati come etichette (plugin + dispositivo) nella finestra dei dispositivi registrati, senza duplicati
* Correzione del pulsante di download dei file oui.txt e iab.txt mancanti, che non funzionava

# 18/11/2026 | v1.7.0
* Aggiunto il bridge "vigipool"
* Interfaccia modernizzata: nuovo foglio di stile compatibile con i temi chiaro e scuro di Jeedom, titoli di sezione con icone, un'icona distinta per ogni azione e contatori dei dispositivi
* Finestre Rete e Dispositivi ridisegnate: schede Jeedom / router, filtro istantaneo, contatori (nascosti quando valgono zero, tra cui «offline» e «non registrati»), intestazione della tabella fissa, badge di stato e casella «Seleziona tutto»
* Barra di avanzamento durante la scansione (passo corrente, percentuale, tempo trascorso) dal pulsante Sincronizza e dalla finestra Rete, con un pulsante «Scansiona» per avviare una nuova scansione
* Registrazione di un dispositivo direttamente dalla finestra Rete e accesso diretto alla scheda dei dispositivi già registrati
* Contatori cliccabili per filtrare le tabelle (online, offline, registrati, non registrati, cambi di IP)
* Pulsante «Registra» su ogni riga della finestra dei dispositivi non registrati
* Widget Rete e Avvisi del dashboard modernizzati (indicatori di stato, icone, temi chiaro e scuro)
* Commenti salvati automaticamente durante la digitazione (niente più pulsante «Salva i commenti»), modificabili anche dalla finestra dei dispositivi non registrati, ed eliminazione di un commento svuotandone il campo
* Configurazione: bridge visualizzati come badge, solo i plugin installati per impostazione predefinita e una casella per mostrare tutti i plugin supportati
* Apertura più rapida della finestra Rete (nessuna risoluzione DNS durante la visualizzazione)
* Sicurezza: nomi, produttori e commenti vengono ora sottoposti a escape nell'interfaccia e nei widget
* Correzione del selettore della modalità che restava vuoto nella configurazione
* Correzione dei menu Wake-on-LAN, «Considerato offline» e ordine di visualizzazione del widget che restavano vuoti su un dispositivo
* Correzione di un falso «Cambio di IP» mostrato per i dispositivi senza ultimo IP noto
* Correzione del file dei commenti che cresceva a ogni salvataggio
* Correzione dei comandi «mac» e «Last IpV4» che non venivano più aggiornati dalla v1.6.0 (il widget mostrava «...»)
* Correzione di un blocco di scansione scaduto che permetteva due scansioni contemporanee
* Correzione dell'allineamento delle caselle delle sottoreti
* Correzione dell'indirizzo MAC di Jeedom letto male quando compare una riga «altname» nella configurazione di rete
* Correzione dell'intestazione di arp-scan registrata per errore come dispositivo («Interface: eth0, type: EN10MB…»)
* La barra di avanzamento si muove durante la scansione di una sottorete, con una stima del tempo rimanente
* Documentazione: screenshot aggiornati con la nuova interfaccia
* Modalità debug: i controlli riusciti mostrano «Aggiornato» invece di «Non aggiornato»
* Il pulsante Debug e le opzioni delle modalità avanzata e debug compaiono non appena si cambia modalità, senza ricaricare la pagina
* Configurazione: le interfacce di rete senza indirizzo IPv4 (interfacce virtuali Docker, schede non collegate) non sono più proposte per la scansione
* Traduzioni completate in inglese, tedesco, spagnolo e italiano, e correzione del file tedesco che conteneva testi in italiano
* Compatibilità PHP 7 e PHP 8: rimossi gli avvisi «deprecated» in PHP 8
* Richiesto almeno Jeedom 4.0

# 08/11/2026 | v1.6.0
* Aggiunti i bridge "tvremote" e "tvByOrange"
* Aggiornamento dei file dei produttori ieee-oui (40.458 voci, +5.578) e ieee-iab
* Ottimizzazione di arp-scan (nessuna risoluzione DNS, tempi di attesa ridotti) e scelta del numero di tentativi (1 o 2) nella configurazione
* Miglioramento delle prestazioni della scansione: comandi caricati in una sola volta, aggiornati solo quando il valore cambia, salvataggi e aggiornamenti del widget limitati allo stretto necessario
* Ricerca online del produttore più affidabile: ricerca tramite l'OUI dell'indirizzo MAC, tempo massimo per richiesta e al massimo 3 ricerche per scansione per non bloccare più il cron
* Correzione del nome del dispositivo associato tramite un bridge che non veniva visualizzato nell'elenco dei dispositivi
* Correzione dell'aggiornamento del plugin che si interrompeva con un errore; la versione viene ora salvata automaticamente affinché le scansioni non restino più sospese dopo un aggiornamento
* Correzioni di compatibilità con PHP 8 e pulizia del codice

# 03/06/2025 | v1.5.2
* Ponte "tydom" aggiunto da Flobul

# 02/01/2025 | v1.5.1
* Aggiunti 2 bridge: "mirobot" e "sshmanager" da Flobul

# 26/09/2024 | v1.5
* Implementazione delle traduzioni per EN, DE, ES e IT

# 07/09/2024 | v1.24
* Correzione di bug per la versione Debian 12 da vegeta0911

# 04/01/2024 | v1.23
* Aggiornamento di ieee-oui e ieee-iab

# 20/11/2023 | v1.22
* Aggiunta della colonna per i valori dei comandi
* Aggiunta di valori di configurazione predefiniti alla prima installazione del plugin

# 14/11/2023 | v1.21
* Correzione della configurazione di un nuovo dispositivo che scompare.
* Correzione del popup che indica che il dispositivo potrebbe apparire offline.

# 20/02/2022 | v1.2
* Integrazione di CRAN nel motore di compiti del sistema Jeedom
* Correzione degli errori di Scan OnLine
* Correzione degli errori degli avvisi Multi Core durante il caricamento della pagina
* Correzione del bug relativo alla perdita di commenti (#73803)
* Correzione del bug della visualizzazione della versione nella configurazione
* Correzione di Last IP (tabella dei dispositivi registrati)
* Aggiunta della colonna On Line per i dispositivi offline
* Miglioramento dell'altezza delle tabelle dei dispositivi

# 04/02/2022 | v1.1
* Correzione dello stato della connessione nel popup della rete

# 01/02/2022
* Correzione del bug - Errore Javascript durante la visualizzazione della tabella nel widget di rete (#77688)

# 07/10/2021
* Correzione del bug: disattivazione del widget Avvisi (#66586)

# 03/08/2021
* Aggiunta di un indicatore verde/rosso che indica online/offline da Flobul
* Eliminati gli spazi tra i pulsanti (modalità arrotondata) da Flobul

# 23/07/2021 | Beta & Stable
* Correzione del bug per l'ordinamento di IPv4

# 01/07/2021 | Beta & Stable
* Visualizzazione dell'ID MAC in modalità Debug
* Aggiunta degli errori dell'ID MAC nella finestra di Debug
* Aggiornamento della documentazione sulla subnet

# 18/06/2021 | Stable
* Fusione della Beta v1.1.1
* Correzione del bug: ordinamento per data (#61919)

# 07/12/2021 | Beta v1.1.1
* Correzione del bridge "script" (errore di registrazione e aggiornamento)
* Aggiunto un avviso quando si associa il bridge "core" a un dispositivo

# 05/01/2021 | Stable v1.1
* Modifiche importanti per gestire i cambiamenti di IP e indirizzo MAC quando si utilizzano ripetitori
* Ampio lavoro sulla parte di aggiornamento (backup, verifica dei dati, controlli e debug automatici, attesa della fine delle scansioni in corso e disattivazione dei cron durante l'aggiornamento per evitare bug)
* Correzione del bug della visualizzazione della gamma IP nelle subnet
* Correzione del bug durante la rimozione di dispositivi orfani quando si elimina un dispositivo collegato a un bridge
* Modalità di sviluppo (per facilitare lo sviluppo)
* Più informazioni nella sezione dispositivi
* Ritorno alla normalità del widget Avvisi (vengono mostrati solo gli elementi non registrati)
* Correzione dell'errore v1.1 per collegare i bridge (configurazione)

# 17/11/2020 | Stable v1

# 07/10/2020 | Beta v1
