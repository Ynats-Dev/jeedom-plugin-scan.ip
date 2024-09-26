---
layout: default
lang: de_DE
---

Beschreibung
===

Dieses Plugin wird verwendet, um Ihr Netzwerk zu scannen und die Liste der verbundenen Geräte sowie deren MAC-Adressen abzurufen.

Mit diesen Informationen ermöglicht das Plugin:
- die neue IP-Adresse eines Geräts abzurufen
- ein Widget anzuzeigen, das den Status Ihres Netzwerks anzeigt
- ein Widget anzuzeigen, das nicht registrierte Geräte anzeigt, die sich gerade mit dem Netzwerk verbunden haben
- ein Widget für jedes registrierte Gerät anzuzeigen und die zugehörigen Befehle bereitzustellen
- Benachrichtigungen zu erhalten, wenn ein Gerät im Netzwerk verschwindet, erscheint oder seine IP-Adresse ändert
- IP-Änderungen in anderen Plugins über Bridges zu automatisieren
- die Möglichkeit zu bieten, ein Gerät aus der Ferne einzuschalten (Wake-on-LAN)

Installation und Abhängigkeiten
===

- Dieses Plugin erfordert zwingend "arp-scan", "wakeonlan" und "etherwake".
- Die Verwendung der Dateien "oui.txt" und "iab.txt", die von "standards-oui.ieee.org" bereitgestellt werden
- APIs, die von "macvendors.com" und "macvendors.co" bereitgestellt werden

# Konfiguration

Um die Verwendung dieses Plugins zu erleichtern, können die angebotenen Optionen in drei Modi angepasst werden: "normal", "fortgeschritten" und "debug".

## Normaler Modus

Der normale Modus zeigt die Hauptparameter an, die sich in der Regel an Standardkonfigurationen anpassen.

![scan_ip1](../images/Config.Normal.png)

### Netzwerk-Widget

Diese Option ermöglicht es, das Netzwerk-Widget anzuzeigen oder auszublenden.

![scan_ip2](../images/Widget.network.png)

> Sie haben die Möglichkeit, die Spalten mit einem einfachen Klick zu sortieren.

> Sie haben auch die Möglichkeit, eine Standardsortierung zu konfigurieren, indem Sie das Gerät aufrufen, das mit diesem Widget verknüpft ist.

### Benachrichtigungs-Widget

Diese Option ermöglicht es, das Benachrichtigungs-Widget anzuzeigen oder auszublenden.

![scan_ip5](../images//Widget.Alerte.png)

> Dieses Widget zeigt nur nicht registrierte Geräte an, und die Anzeige erfolgt sortiert nach ((dateConnexion).(datePremierReferencement)). Ein neues Gerät wird also an erster Stelle angezeigt.

### Aktualisierungsrate

Dient zur Einstellung der Aktualisierungsrate der geplanten Aufgabe.  
Standardmäßig ist diese auf 1 Minute eingestellt.

> Es sei darauf hingewiesen, dass bei jedem Netzwerkscan nicht unbedingt alle Geräte antworten, da sie zu lange zum Antworten benötigen (z. B. WLAN-Geräte). Es wird daher empfohlen, mindestens drei Scans durchzuführen, bevor festgestellt wird, dass ein Gerät tatsächlich offline ist. Sie werden sehen, dass Sie diesen Parameter auf Geräteebene feiner einstellen können.

### Liste der unterstützten Plugins

Scan.ip verwendet Bridges, um Daten in bestimmten Plugins zu aktualisieren. Ein Indikator zeigt Ihnen an, ob das Plugin in Ihrem Jeedom vorhanden ist oder nicht.

> In der Praxis ermöglicht dies, durch Bridges die IP-Adresse eines Geräts in anderen Plugins automatisch zu ändern, wenn es seine IP ändert (zum Beispiel, wenn es nicht möglich ist, eine feste IP im DHCP festzulegen).

Sie können ein Gerät einem oder mehreren Plugins zuordnen.

## Fortgeschrittener Modus

![scan_ip7](../images/Config.Avance.png)

### Router

Ermöglicht das Hinzufügen des Routers zur Netzwerk-Liste. Standardmäßig wird dieser entfernt.

### Jeedom

Ermöglicht das Hinzufügen des Jeedom-Servers zur Netzwerk-Liste. Standardmäßig wird dieser entfernt.

### Retry-Option

Damit können Sie die Anzahl der Versuche beim Netzwerkscan festlegen. Tatsächlich antworten nicht alle Geräte beim ersten Scanversuch. Beachten Sie, dass je mehr Versuche Sie einstellen, desto länger der Scan dauert.

### Safari-Kompatibilität

Im Safari-Webbrowser ist der versteckte Modus im Auswahlmenü, der nur unbenutzte Bridges anzeigt, nicht kompatibel. Wenn Sie diese Option aktivieren, werden bereits verwendete Menüs deaktiviert. Diese Option sollte nur aktiviert werden, wenn Sie das Problem in Safari haben.

### Netzbereiche zum Scannen festlegen

Wenn Sie mehrere Netzwerkkarten oder insbesondere Subnetze haben, sollten Sie das/die zu scannende(n) Subnetz(e) festlegen.

> Zur Information: Ein Subnetz erlaubt möglicherweise nicht, MAC-Adressen abzurufen (gesperrt auf dem Router oder anderen Stellen). Wenn Sie dieses Problem haben, müssen Sie ein weiteres Jeedom in diesem Subnetz installieren, das mit einem dedizierten Scan.ip das gleiche Subnetz verwaltet.

## Debug-Modus

![scan_ip7](../images/Config.Debug.png)

### Vorhandene Dateien

Dient zur Überprüfung, ob die Dateien oui.txt und iab.txt vorhanden sind und wann sie zuletzt aktualisiert wurden.

> Diese beiden Dateien werden verwendet, um eine MAC-Adresse einem Hersteller zuzuordnen.

### Abhängigkeiten

Ermöglicht die einfache Visualisierung von fehlgeschlagenen Abhängigkeiten.

# Geräteverwaltung

![scan_ip9](../images/AllEquipements.png)

> Die Geräte sind in zwei Teile unterteilt: "MAC-Geräte" und "Widgets, die von Scan.Ip verwaltet werden". Diese sollten nur im Falle eines Fehlers gelöscht werden. Um sie zu löschen, müssen Sie den Debug-Modus aktivieren, damit der "Löschen"-Button erscheint.

## Zugriffssymbole

> Wenn Sie den Modus ändern, müssen Sie die Seite aktualisieren, um die Änderungen anzuzeigen.

Im Debug-Modus erscheint ein Debug-Symbol.

![scan_ip10](../images/Menu.Debug.png)

### MAC-Gerät hinzufügen

Dies ermöglicht es, ein Gerät hinzuzufügen, wenn Sie die MAC-Adresse des hinzuzufügenden Geräts kennen.

> Beachten Sie, dass es einfacher ist, über "Nicht registrierte Einträge" zu gehen, mit dem Sie ein oder mehrere im Netzwerk vorhandene Geräte schnell hinzufügen können.

### Synchronisieren

Erzwingt einen neuen Scan und aktualisiert die Geräte.

> Wenn bereits ein Scan läuft, ist die Aktion nicht möglich.

### Netzwerk

![scan_ip11](../images/Modal.Network.png)

Die Tabelle zeigt das gesamte Netzwerk an:

- Die erste Spalte zeigt an, ob das Gerät "Online" oder "Offline" ist.
- Die zweite Spalte zeigt an, ob das Gerät "registriert", "registriert, aber deaktiviert" oder "nicht registriert" ist.
- Die dritte Spalte zeigt die MAC-Adresse des Geräts an.
- Die vierte Spalte zeigt die IP-Adresse des Geräts an.
- Die fünfte Spalte zeigt den Gerätenamen an, wenn es registriert ist, oder den Herstellernamen, der von einem "|" eingeleitet wird.
- Die sechste Spalte ermöglicht das Hinzufügen von Kommentaren zu einem Gerät (Sie müssen die Kommentare speichern, damit sie berücksichtigt werden).
- Die siebte Spalte zeigt das letzte Erscheinen des Geräts an.

> Wenn Sie "|" im Gerätenamen bemerken, bedeutet dies, dass es nicht erkannt wurde.

### Registrierte Geräte

![scan_ip12](../images/Modal.Equipemetn.Ok.png)

- Die erste Spalte zeigt an, ob das Gerät "Online" oder "Offline" ist.
- Die zweite Spalte zeigt den Gerätenamen an.
- Die dritte Spalte zeigt die MAC-Adresse des Geräts an.
- Die vierte Spalte zeigt die IP-Adresse des Geräts an.
- Die fünfte Spalte zeigt die IP-Adresse, die beim letzten Verbinden verwendet wurde.
- Die sechste Spalte zeigt das letzte Erscheinen des Geräts an.
- Die siebte Spalte zeigt alle Bridges an, die mit dem Gerät verbunden sind. Durch Klicken auf diese werden Sie zum entsprechenden Plugin-Gerät weitergeleitet.

### Nicht registrierte Geräte

![scan_ip13](../images/Modal.Equipemetn.No.png)

- Die erste Spalte ermöglicht die Auswahl des Geräts* (um es "on the fly" zu Ihren Geräten hinzuzufügen oder es aus den Netzwerkarchiven zu löschen).
- Die zweite Spalte zeigt den Namen des Geräts an.
- Die dritte Spalte zeigt die mit dem Gerät verbundenen Kommentare an.
- Die vierte Spalte zeigt die MAC-Adresse des Geräts an.
- Die fünfte Spalte zeigt die vom Gerät verwendete IP-Adresse an.
- Die sechste Spalte zeigt das Datum des ersten Eintrags in Ihr Netzwerk an.
- Die siebte Spalte zeigt das letzte Erscheinen des Geräts an.

> * Wenn Sie ein oder mehrere Elemente auswählen, erscheinen Aktionsschaltflächen.

![scan_ip14](../images/Modal.Equipemetn.No.save.png)

### Debug (nur im Debug-Modus)

![scan_ip15](../images/Modal.Debug.png)

Dieses Modal dient dazu, potenzielle Fehler zu verstehen, die auftreten könnten.

#### # ip a

Ergebnis des Befehls "ip a", der die Liste aller Netzwerke anzeigt, die von Ihrem Jeedom aus zugänglich sind.

#### # ip route show default

Zeigt die IP-Adresse des Routers an, der mit Ihrem Jeedom verbunden ist.

#### # sudo arp-scan -r 3 --interface=eth0 --localnet --ouifile=ieee-oui.txt

> Hier ist das Netzwerk "eth0" ausgewählt, aber dies ist spezifisch für Ihr Netzwerk.

Ergebnis des Befehls "sudo arp-scan -r 3 --interface=eth0 --localnet --ouifile=ieee-oui.txt", das die Liste der Geräte anzeigt, die auf den Scan antworten.

> Wie bereits erwähnt, werden möglicherweise nicht alle angeschlossenen Geräte in einem Scan angezeigt. Aus diesem Grund ist es wichtig, mehrere Scans durchzuführen, um eine genauere Rückmeldung darüber zu erhalten, welche Geräte verbunden sind.

#### # Geräte

Zeigt die im Geräte-json enthaltenen Daten an. Dies ist der Verlauf aller Geräte, die sich mit Ihrem Netzwerk verbunden haben.

#### # Mappings

Zeigt die im Mapping-json enthaltenen Daten an. Dies ist der letzte Status Ihres Netzwerks mit der verwendeten Version von "arp-scan".

### Konfiguration

Dieser Punkt wurde bereits am Anfang behandelt.

## Ihre Geräte

### Im normalen Modus

![scan_ip16](../images/Equipement.Normal.png)

#### MAC-Adresse suchen

Dieses Auswahlmenü ermöglicht es Ihnen, direkt nach einem Element in Ihrem Netzwerk zu suchen und dessen MAC-Adresse abzurufen.

> Nur nicht zugeordnete MAC-Adressen werden angezeigt, um Duplikate zu vermeiden.

#### Zugeordnete MAC-Adresse

Dieses Feld wird:
- entweder automatisch mit dem Auswahlmenü "MAC-Adresse suchen" ausgefüllt
- oder Sie können es manuell ausfüllen, wenn Sie das Hinzufügen von Geräten in Ihr Netzwerk vorwegnehmen möchten.

#### Hersteller

Wenn der Hersteller identifiziert wurde, wird er hier eingetragen.

#### Wake-on-LAN

Dies ermöglicht das Hinzufügen eines WoL-Aktionsbefehls, mit dem Sie ein Gerät aus der Ferne einschalten können.

> Stellen Sie sicher, dass Ihr Gerät kompatibel ist und/oder die Option aktiviert wurde, damit es funktioniert.

#### Zuordnung

![scan_ip6](../images/Equipement.Bridge.png)

Wie im Konfigurationsabschnitt beschrieben, werden hier die Plugins über Bridges mit Scan.Ip verbunden.

> Im Auswahlmenü wird die Information wie folgt angezeigt: [Pluginname][im Plugin registrierte IP::Subconfig-Name (falls vorhanden)] Name des Zielgeräts im Plugin.

> Achtung: Diese Option nimmt Änderungen an anderen Plugins vor. Verwenden Sie sie mit Vorsicht. Der Autor des Ziel-Plugins ist nicht verantwortlich für Fehlfunktionen, die dadurch in seinem Plugin entstehen könnten.

> Das versteht sich von selbst! Solange Sie keine Zuordnung zu einem Plugin vornehmen, nimmt Scan.ip keine Änderungen daran vor.

> Wenn Sie Entwickler sind und eine zusätzliche Bridge vorschlagen möchten, ist dies möglich. Entweder durch einen direkten Vorschlag auf GitHub (https://ynats.github.io/jeedom-plugin-scan.ip/) oder über das Forum.

### Im fortgeschrittenen Modus

![scan_ip17](../images/Equipement.Avanced.png)

#### Erinnerung "Konfiguration"

Damit haben Sie die Konfiguration, die Sie gespeichert haben, im Blick.

#### Als offline vermutet nach

Standardmäßig auf 4 Minuten eingestellt, was bedeutet, dass bei einer Einstellung auf 1 Minute ein Scan alle 4 Durchläufe benötigt, um festzustellen, ob das Gerät wirklich offline ist.  
Erinnern Sie sich, nicht alle Geräte werden unbedingt bei jedem Scan erfasst.

> Wenn Sie die Aktualisierungsrate auf 3 Minuten eingestellt haben, müssen Sie diesen Parameter auf mindestens 15 Minuten einstellen.

> Dieser Parameter kann für LAN-Geräte, die sehr reaktionsschnell sind, angepasst werden. Es wäre angebracht, ihn auf 2 Minuten zu senken, zum Beispiel bei einer Aktualisierungsrate von 1 Minute. Dies würde 2 Durchläufe benötigen, um den Status zu bestimmen.

### Zugeordnete Befehle

![scan_ip18](../images/Commande.Wol.png)

- IpV4 (IPv4-Adresse des Geräts)
- Wol (der Aktionsbefehl, um das Gerät aus der Ferne einzuschalten)
- Last Date (Datum im Format TT/MM/JJJJ HH:MM:SS des letzten Erscheinens im Netzwerk)
- Last IpV4 (Letzte vom Gerät verwendete IP-Adresse)
- Last Time (Datum im Zeitstempel-Format des letzten Erscheinens im Netzwerk)
- Online (Status des Geräts)

### Zugeordnete Widgets

![scan_ip9](../images/Widget.Normal.png)

# Von Scan.Ip verwaltete Widgets

![scan_ip19](../images/AllEquipements.png)

Standardmäßig umfasst dieser Abschnitt 2 Widgets (sie können umbenannt werden):
- Scan.Ip Widget Alerts
- Scan.Ip Widget Network

Diese Geräte werden direkt vom Plugin verwaltet und entsprechen den von Ihnen im Plugin konfigurierten Einstellungen.

![scan_ip1](../images/Config.Normal.png)

> Zur Erinnerung: Um deren Anzeige zu verwalten, erfolgt dies im Konfigurationsbereich.

## Das Netzwerk-Widget

![scan_ip20](../images/config.widget.network.png)

## Das Benachrichtigungs-Widget

![scan_ip21](../images/config.widget.alerte.png)

#### Zugeordnete Befehle

Es werden 10 Befehlsgruppen erstellt, die den letzten 10 nicht registrierten Geräten entsprechen, die sich verbunden haben. Die chronologische Reihenfolge beginnt bei null (das neueste) und endet bei neun (das älteste).

Jedes Element hat 6 Unterbefehle:

- Verbindung 0 Erstellung (Zeitstempel des ersten Verweises)
- Verbindung 0 Datum (Datum im Format TT/MM/JJJJ HH:MM:SS des letzten Erscheinens)
- Verbindung 0 Zeit (Zeitstempel des letzten Erscheinens)
- Verbindung 0 Gerät (Herstellername, falls angegeben)
- Verbindung 0 IpV4 (IPv4-Adresse des Geräts)
- Verbindung 0 MAC (MAC-Adresse des Geräts)

## Subnetze und Scan.Ip

#### Wenn Sie mehrere Netzwerke verwalten möchten (z. B. Sie besitzen einen Hub, der mit Ihrem Router verbunden ist)

Scan.Ip ist in der Lage, mehrere Netzwerke gleichzeitig zu verwalten.  
Dazu müssen Sie Ihr Jeedom mit den verschiedenen Netzwerken verbinden.  
Sie haben beispielsweise die Möglichkeit, das WLAN Ihres Jeedom-PCs mit einem Netzwerk und das LAN mit einem anderen zu verbinden. Oder einfach "usb/LAN"- oder "usb/WLAN"-Adapter an Ihr Jeedom anzuschließen.  
Scan.Ip zeigt Ihnen die Subnetze an, die Sie dann im Konfigurationsbereich des Plugins (im erweiterten Modus) aktivieren können.

![scan_ip22](../images/config.widget.reseaux.png)
