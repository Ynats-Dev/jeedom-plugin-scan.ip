---
layout: default
lang: it_IT
---

Descrizione
===

Questo plugin viene utilizzato per scansionare la tua rete e recuperare l'elenco dei dispositivi collegati insieme ai loro indirizzi MAC.

Con queste informazioni, il plugin consente:
- recuperare il nuovo IP di un dispositivo
- visualizzare un widget che mostra lo stato della rete
- visualizzare un widget che elenca i dispositivi non registrati che si sono appena connessi alla rete
- visualizzare un widget per ciascun dispositivo registrato con accesso ai comandi associati a quel dispositivo
- ricevere avvisi quando un dispositivo scompare, appare o cambia IP nella rete
- automatizzare i cambiamenti di IP in cascata su altri plugin attraverso bridge
- accendere un dispositivo da remoto (Wake-on-LAN)

Installazione e Dipendenze
===

- Questo plugin richiede "arp-scan", "wakeonlan" ed "etherwake".
- L'utilizzo dei file "oui.txt" e "iab.txt" forniti da "standards-oui.ieee.org"
- Le API fornite da "macvendors.com" e "macvendors.co"

# Configurazione

Per facilitare l'utilizzo di questo plugin, consente di adattare le opzioni offerte in tre modalità: "normale", "avanzato" e "debug".

## Modalità normale

La modalità normale mostra i parametri principali, che generalmente si adattano alle configurazioni standard.

![scan_ip1](../images/Config.Normal.png)

### Widget Rete

Questa opzione consente di visualizzare o nascondere il widget di visualizzazione della rete.

![scan_ip2](../images/Widget.network.png)

> Hai la possibilità di ordinare le colonne con un semplice clic.

> Hai anche la possibilità di configurare un ordinamento predefinito andando nel dispositivo collegato a questo widget.

### Widget Avvisi

Questa opzione consente di visualizzare o nascondere il widget di visualizzazione degli avvisi.

![scan_ip5](../images//Widget.Alerte.png)

> Questo widget mostra solo i dispositivi non registrati e l'ordine di visualizzazione è ordinato per ((dateConnexion).(datePremierReferencement)). Pertanto, un dispositivo nuovo apparirà in prima posizione.

### Frequenza di aggiornamento

Serve per regolare la frequenza di aggiornamento del task pianificato.  
Di default, è impostata a 1 minuto.

> Nota che ad ogni scansione della rete non tutti i dispositivi rispondono necessariamente poiché potrebbero impiegare troppo tempo a rispondere (ad es. dispositivi Wi-Fi). Si consiglia di effettuare almeno tre scansioni prima di determinare che un dispositivo è veramente offline. Potrai regolare questo parametro in modo più fine a livello dei dispositivi.

### Elenco dei Plugin Supportati

Scan.ip utilizza i bridge per aggiornare i dati su determinati plugin. Un indicatore ti permetterà di vedere se il plugin è presente nel tuo Jeedom o no.

> In pratica, ciò consente di automatizzare, tramite i bridge, il cambiamento dell'IP di un dispositivo negli altri plugin quando decide di cambiare IP (ad esempio, quando non è possibile impostare un IP fisso nel DHCP).

Potrai associare un dispositivo a uno o più plugin.

## Modalità avanzata

![scan_ip7](../images/Config.Avance.png)

### Router

Consente di aggiungere il router all'elenco della rete. Per impostazione predefinita, viene rimosso.

### Jeedom

Consente di aggiungere il server Jeedom all'elenco della rete. Per impostazione predefinita, viene rimosso.

### Opzione Retry

Ciò consente di impostare il numero di tentativi durante le scansioni di rete. Infatti, non tutti i dispositivi rispondono al primo tentativo di scansione. Nota che più tentativi imposti, più tempo impiegherà la scansione.

### Compatibilità con il Browser Safari

Nel browser web Safari la modalità nascosta nel menu di selezione, che consente di visualizzare solo i bridge non utilizzati, non è compatibile. Di conseguenza, attivando questa opzione, i menu già utilizzati saranno disabilitati. Questa opzione deve essere attivata solo se riscontri il problema su Safari.

### Specificare Intervalli di Rete da Scansionare

Quando hai più schede di rete o in particolare sottoreti, è opportuno specificare le sottoreti da scansionare.

> Per tua informazione, una sottorete potrebbe non consentire il recupero degli indirizzi MAC (blocco a livello del router o altro). Se riscontri questo problema, dovrai installare un altro Jeedom in quella sottorete con un Scan.ip dedicato per gestire la stessa sottorete.

## Modalità debug

![scan_ip7](../images/Config.Debug.png)

### File presenti

Serve per verificare la presenza dei file oui.txt e iab.txt e le loro date di aggiornamento.

> Questi due file servono a creare il collegamento tra un indirizzo MAC e un produttore.

### Dipendenze

Permette di visualizzare facilmente le dipendenze che non sono riuscite.

# Gestione dei dispositivi

![scan_ip9](../images/AllEquipements.png)

> I dispositivi sono separati in due parti: "Dispositivi MAC" e "Widget Gestiti da Scan.Ip". Questi ultimi non devono essere eliminati se non in caso di bug. Per eliminarli, dovrai attivare la modalità debug affinché appaia il pulsante "elimina".

## Icone di accesso

> Quando cambi modalità, dovrai aggiornare la pagina per vedere apparire le modifiche.

In modalità debug vedrai apparire un'icona di debug.

![scan_ip10](../images/Menu.Debug.png)

### Aggiungere un Dispositivo MAC

Questo consente di aggiungere un dispositivo quando conosci l'indirizzo MAC del dispositivo che desideri aggiungere.

> Nota che è più semplice passare per "Registri non registrati", che ti permetterà di aggiungere al volo uno o più dispositivi presenti nella tua rete.

### Sincronizzare

Forza l'avvio di una nuova scansione e l'aggiornamento dei dispositivi.

> Se è già in corso una scansione, l'azione non sarà possibile.

### Rete

![scan_ip11](../images/Modal.Network.png)

La tabella mostra l'intera rete:

- La prima colonna indica se il dispositivo è "Online" o "Offline".
- La seconda colonna indica se il dispositivo è "registrato", "registrato ma disabilitato" o "non registrato".
- La terza colonna indica l'indirizzo MAC del dispositivo.
- La quarta colonna indica l'indirizzo IP del dispositivo.
- La quinta colonna indica il nome del dispositivo se è registrato o il nome del produttore preceduto da un "|".
- La sesta colonna consente di associare commenti a un dispositivo (dovrai salvare i commenti affinché vengano presi in considerazione).
- La settima colonna indica la data dell'ultima apparizione del dispositivo.

> Se noti "|..." nei nomi dei dispositivi significa che non è stato riconosciuto.

### Dispositivi registrati

![scan_ip12](../images/Modal.Equipemetn.Ok.png)

- La prima colonna indica se il dispositivo è "Online" o "Offline".
- La seconda colonna indica il nome del dispositivo.
- La terza colonna indica l'indirizzo MAC del dispositivo.
- La quarta colonna indica l'indirizzo IP del dispositivo.
- La quinta colonna indica l'IP utilizzato durante l'ultima connessione.
- La sesta colonna indica la data dell'ultima apparizione del dispositivo.
- La settima colonna indica tutti i bridge associati al dispositivo. Cliccando su di essi verrai reindirizzato al dispositivo del plugin associato.

### Dispositivi non registrati

![scan_ip13](../images/Modal.Equipemetn.No.png)

- La prima colonna consente di selezionare il dispositivo* (per aggiungerlo al volo tra i tuoi dispositivi o per eliminarlo dagli archivi della rete).
- La seconda colonna indica il nome del dispositivo.
- La terza colonna mostra i commenti associati al dispositivo.
- La quarta colonna indica l'indirizzo MAC del dispositivo.
- La quinta colonna indica l'IP utilizzato dal dispositivo.
- La sesta colonna indica la data del primo registro nella tua rete.
- La settima colonna l'ultima data di apparizione del dispositivo.

> * Quando selezioni uno o più elementi appariranno i pulsanti di azione.

![scan_ip14](../images/Modal.Equipemetn.No.save.png)

### Debug (solo in modalità Debug)

![scan_ip15](../images/Modal.Debug.png)

Questa finestra ha lo scopo di aiutare a comprendere eventuali bug che potrebbero apparire.

#### # ip a

Risultato del comando "ip a" che mostra l'elenco di tutte le reti accessibili dal tuo Jeedom.

#### # ip route show default

Mostra l'IP del router associato al tuo Jeedom.

#### # sudo arp-scan -r 3 --interface=eth0 --localnet --ouifile=ieee-oui.txt

> Qui viene selezionata la rete "eth0", ma questo è specifico per la tua rete.

Risultato del comando "sudo arp-scan -r 3 --interface=eth0 --localnet --ouifile=ieee-oui.txt" che mostra l'elenco dei dispositivi che rispondono alla scansione.

> Come indicato sopra, è possibile che non tutti i dispositivi collegati vengano visualizzati in una sola scansione. Ecco perché è importante effettuare più scansioni per avere un resoconto più accurato di chi è collegato.

#### # Dispositivi

Mostra i dati presenti nel file json dei dispositivi. Questo è lo storico di tutti i dispositivi che si sono connessi alla tua rete.

#### # Mappings

Mostra i dati presenti nel file json di mapping. Questo è lo stato più recente della tua rete con la versione di "arp-scan" utilizzata.

### Configurazione

Questo punto è già stato trattato all'inizio.

## I tuoi dispositivi

### In modalità normale

![scan_ip16](../images/Equipement.Normal.png)

#### Cerca un indirizzo MAC

Questo menu di selezione ti consente di cercare direttamente un elemento nella tua rete e recuperare il suo indirizzo MAC.

> Solo gli indirizzi MAC non associati vengono visualizzati per evitare duplicati.

#### Indirizzo MAC associato

Questo campo è:
- compilato automaticamente con il menu di selezione "Cerca un indirizzo MAC"
- oppure puoi compilarlo manualmente se desideri anticipare l'ingresso di dispositivi nella tua rete.

#### Produttore

Se il produttore è stato identificato, verrà indicato qui.

#### Wake-on-LAN

Questo consente di aggiungere un comando di azione WoL che permetterà di accendere un dispositivo da remoto.

> Verifica che il tuo dispositivo sia compatibile e/o che l'opzione sia stata attivata affinché funzioni.

#### Associazione

![scan_ip6](../images/Equipement.Bridge.png)

Come visto nella sezione di configurazione, è qui che si associano i plugin a Scan.Ip tramite i bridge.

> Nel menu di selezione l'informazione è indicata come segue [nome del plugin][IP registrato nel plugin:: Sotto nome di configurazione (se presente)] Nome del dispositivo di destinazione del plugin.

> Attenzione: questa opzione apporta modifiche ad altri plugin. Usala con cautela. L'autore del plugin di destinazione non è responsabile per eventuali malfunzionamenti che ciò potrebbe causare nel suo plugin.

> Va da sé! Finché non effettui alcuna associazione a un plugin, Scan.ip non apporterà alcuna modifica.

> Se sei uno sviluppatore e desideri proporre un bridge aggiuntivo, questo è assolutamente possibile. O proponendo direttamente un'aggiunta su GitHub (https://ynats.github.io/jeedom-plugin-scan.ip/) oppure tramite il forum.

### In modalità avanzata

![scan_ip17](../images/Equipement.Avanced.png)

#### Promemoria "Configurazione"

Ciò ti consente di ricordare la configurazione che avevi salvato.

#### Presunto offline dopo

Di default, è impostato a 4 minuti, il che significa che se hai lasciato tutto su default, viene eseguita una scansione ogni minuto e 4 passaggi per determinare se il dispositivo è effettivamente offline.  
Ricorda, non tutti i dispositivi rispondono necessariamente a ogni scansione.

> Se hai impostato la frequenza di aggiornamento a 3 minuti, ciò significa che dovrai impostare questo parametro ad almeno 15 minuti.

> Questo parametro può essere modificato su dispositivi LAN molto reattivi. Sarebbe opportuno ridurlo a 2 minuti, ad esempio per una frequenza di 1 minuto. Questo significherebbe 2 passaggi per determinare lo stato.

### Comandi associati

![scan_ip18](../images/Commande.Wol.png)

- IpV4 (IP in formato v4 del dispositivo)
- Wol (il comando di azione per accendere il dispositivo da remoto)
- Last Date (Data in formato GG/MM/AAAA HH:MM:SS dell'ultima apparizione nella rete)
- Last IpV4 (Ultimo IP utilizzato dal dispositivo)
- Last Time (Data in formato timestamp dell'ultima apparizione nella rete)
- Online (Stato del dispositivo)

### Widget associati

![scan_ip9](../images/Widget.Normal.png)

# Widget gestiti da Scan.Ip

![scan_ip19](../images/AllEquipements.png)

Per impostazione predefinita, questa parte comprende 2 widget (possono essere rinominati):
- Scan.Ip Widget Alerts
- Scan.Ip Widget Network

Questi dispositivi sono gestiti direttamente dal plugin e corrispondono a ciò che hai configurato nel plugin.

![scan_ip1](../images/Config.Normal.png)

> Come promemoria, per gestire la loro visualizzazione, devi andare nello spazio di configurazione.

## Il widget Network

![scan_ip20](../images/config.widget.network.png)

## Il widget Alerts

![scan_ip21](../images/config.widget.alerte.png)

#### Comandi associati

Vengono creati 10 gruppi di comandi che corrispondono agli ultimi 10 dispositivi non registrati che si sono connessi. L'ordine cronologico inizia da zero (il più recente) e termina a nove (il più vecchio).

Ogni elemento ha 6 sottocomandi:

- Connessione 0 Creazione (Data in formato timestamp della prima referenza)
- Connessione 0 Data (Data in formato GG/MM/AAAA HH:MM:SS dell'ultima apparizione)
- Connessione 0 Ora (Data in formato timestamp dell'ultima apparizione)
- Connessione 0 Dispositivo (Nome del produttore se referenziato)
- Connessione 0 IpV4 (IP in formato v4 assegnato)
- Connessione 0 MAC (Indirizzo MAC del dispositivo)

## Sottoreti e Scan.Ip

#### Se desideri gestire più reti (ad es. possiedi un Hub collegato al tuo router)

Scan.Ip è in grado di gestire più reti contemporaneamente.  
Per fare ciò, è necessario collegare il tuo Jeedom alle diverse reti.  
Ad esempio, hai la possibilità di collegare il Wi-Fi del tuo PC Jeedom a una rete e il LAN a un'altra. Oppure semplicemente collegare adattatori "usb/LAN" o "usb/Wi-Fi" al tuo Jeedom.  
Scan.Ip ti mostrerà le sottoreti che potrai quindi attivare nella parte di configurazione del plugin (in modalità avanzata).

![scan_ip22](../images/config.widget.reseaux.png)
