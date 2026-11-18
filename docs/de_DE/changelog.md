# 18/11/2026 | v1.7.0
* Hinzufügen der Bridge "vigipool"
* Modernisierung der Oberfläche: neues Stylesheet, kompatibel mit dem hellen und dunklen Jeedom-Theme, Abschnittstitel mit Symbolen, ein eigenes Symbol pro Aktion und Gerätezähler
* Überarbeitete Fenster Netzwerk und Geräte: Karten für Jeedom / Router, Sofortfilter, Zähler (bei null ausgeblendet, darunter „offline“ und „nicht registriert“), fixierter Tabellenkopf, Status-Badges und Kontrollkästchen „Alle auswählen“
* Fortschrittsbalken während des Scans (aktueller Schritt, Prozentsatz, verstrichene Zeit) über die Schaltfläche Synchronisieren und das Fenster Netzwerk, mit einer Schaltfläche „Scannen“ für einen neuen Scan
* Registrierung eines Geräts direkt aus dem Fenster Netzwerk und direkter Zugriff auf bereits registrierte Geräte
* Anklickbare Zähler zum Filtern der Tabellen (online, offline, registriert, nicht registriert, IP-Änderungen)
* Schaltfläche „Registrieren“ in jeder Zeile des Fensters der nicht registrierten Geräte
* Modernisierte Dashboard-Widgets Netzwerk und Warnungen (Statuspunkte, Symbole, helles und dunkles Theme)
* Kommentare im Fenster der nicht registrierten Geräte bearbeitbar, Bestätigung nach dem Speichern und Löschen eines Kommentars durch Leeren des Feldes
* Konfiguration: Bridges als Badges angezeigt, standardmäßig nur installierte Plugins, mit einem Kontrollkästchen zur Anzeige aller unterstützten Plugins
* Schnelleres Öffnen des Fensters Netzwerk (keine DNS-Auflösung mehr beim Anzeigen)
* Sicherheit: Namen, Hersteller und Kommentare werden in der Oberfläche und den Widgets maskiert
* Korrektur der Modusauswahl, die in der Konfiguration leer blieb
* Korrektur einer fälschlich angezeigten „IP-Änderung“ bei Geräten ohne bekannte letzte IP
* Korrektur der Kommentardatei, die bei jedem Speichern größer wurde
* Korrektur einer veralteten Scan-Sperre, die zwei gleichzeitige Scans ermöglichte
* Korrektur der Ausrichtung der Kontrollkästchen für die Subnetze
* Vervollständigte Übersetzungen ins Englische, Deutsche, Spanische und Italienische sowie Korrektur der deutschen Datei, die italienische Texte enthielt
* Kompatibilität mit PHP 7 und PHP 8: „deprecated“-Warnungen unter PHP 8 entfernt
* Mindestens Jeedom 4.0 erforderlich

# 08/11/2026 | v1.6.0
* Hinzufügen der Bridges "tvremote" und "tvByOrange"
* Aktualisierung der Herstellerdateien ieee-oui (40.458 Einträge, +5.578) und ieee-iab
* Optimierung von arp-scan (keine DNS-Auflösung, kürzere Wartezeiten) und Auswahl der Anzahl der Wiederholungen (1 oder 2) in der Konfiguration
* Leistungsverbesserungen beim Scan: Befehle werden auf einmal geladen, nur bei Wertänderung aktualisiert, Speichervorgänge und Kachelaktualisierungen auf das Nötigste beschränkt
* Zuverlässigere Online-Herstellersuche: Suche anhand des OUI der MAC-Adresse, maximale Wartezeit pro Anfrage und höchstens 3 Suchen pro Scan, damit der Cron nicht mehr blockiert wird
* Korrektur des Namens des über eine Bridge verknüpften Geräts, der in der Geräteliste nicht angezeigt wurde
* Korrektur der Plugin-Aktualisierung, die mit einem Fehler abbrach; die Version wird nun automatisch gespeichert, damit die Scans nach einer Aktualisierung nicht mehr ausgesetzt bleiben
* Korrekturen für die PHP-8-Kompatibilität und Code-Bereinigung

# 03/06/2025 | v1.5.2
* Hinzufügen der Bridge "tydom" durch Flobul

# 02/01/2025 | v1.5.1
* Hinzufügung von 2 Bridges: "mirobot" und "sshmanager" von Flobul

# 26/09/2024 | v1.5
* Umsetzung der Übersetzungen für EN, DE, ES und IT

# 07/09/2024 | v1.24
* Fehlerbehebungen für die Debian 12-Version von vegeta0911

# 04/01/2024 | v1.23
* Aktualisierung von ieee-oui und ieee-iab

# 20/11/2023 | v1.22
* Hinzufügen der Spalte für Befehlswerte
* Hinzufügen von Standardkonfigurationswerten bei der ersten Installation des Plugins

# 14/11/2023 | v1.21
* Korrektur der Konfiguration eines neuen Geräts, das verschwindet.
* Korrektur des Popups, das anzeigt, dass das Gerät möglicherweise offline erscheint.

# 20/02/2022 | v1.2
* Integration von CRAN in die Aufgaben-Engine des Jeedom-Systems
* Fehlerbehebung bei Scan OnLine
* Fehlerbehebung bei Multi-Core-Fehlerwarnungen beim Laden der Seite
* Behebung des Fehlers beim Verlust von Kommentaren (#73803)
* Fehlerbehebung bei der Anzeige der Version in der Konfiguration
* Korrektur der letzten IP (Tabelle der registrierten Geräte)
* Hinzufügen der Spalte "On Line" für offline Geräte
* Verbesserung der Höhe der Gerätetabellen

# 04/02/2022 | v1.1
* Korrektur des Verbindungsstatus im Netzwerk-Popup

# 01/02/2022
* Fehlerbehebung - Javascript-Fehler bei der Anzeige der Tabelle im Netzwerk-Widget (#77688)

# 07/10/2021
* Fehlerbehebung: Deaktivierung des Alert-Widgets (#66586)

# 03/08/2021
* Hinzufügen eines grünen/roten Punktes, der den Status online/offline anzeigt, von Flobul
* Vermeidung von Lücken zwischen Schaltflächen (abgerundeter Modus) von Flobul

# 23/07/2021 | Beta & Stable
* Fehlerbehebung bei der Sortierung von IPv4-Adressen

# 01/07/2021 | Beta & Stable
* Anzeige der MAC-ID im Debug-Modus
* Hinzufügen von MAC-ID-Fehlern im Debug-Modal
* Aktualisierung der Dokumentation zu Subnetzen

# 18/06/2021 | Stable
* Zusammenführung von Beta v1.1.1
* Fehlerbehebung: Sortierung nach Datum (#61919)

# 07/12/2021 | Beta v1.1.1
* Korrektur der "script"-Bridge (Fehler bei der Registrierung und Aktualisierung)
* Hinzufügen einer Warnung beim Verknüpfen der "core"-Bridge mit einem Gerät

# 05/01/2021 | Stable v1.1
* Wichtige Änderungen zur Verwaltung von IP- und MAC-Adressänderungen bei Verwendung von Repeatern
* Umfangreiche Arbeiten im Bereich der Updates (Backup, Datenüberprüfung, automatisierte Checks und Debugs, Warten auf das Ende laufender Scans und Deaktivierung von Cron-Jobs während des Updates, um Fehler zu vermeiden)
* Behebung eines Fehlers bei der Anzeige des IP-Bereichs in den Subnetzen
* Behebung eines Fehlers beim Entfernen von Waisen, wenn ein gebridgtes Gerät gelöscht wird
* Entwicklungsmodus (um die Entwicklung zu erleichtern)
* Mehr Informationen im Gerätebereich
* Rückkehr zum normalen Zustand des Alert-Widgets (es werden nur nicht registrierte Elemente angezeigt)
* Fehlerbehebung v1.1 für die Verbindung von Bridges (Konfiguration)

# 17/11/2020 | Stable v1

# 07/10/2020 | Beta v1
