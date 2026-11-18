# 18/11/2026 | v1.7.1
* Fichiers constructeurs oui.txt et iab.txt mis à jour automatiquement tous les mois depuis le site de l'IEEE, avec la date de la dernière mise à jour ; fréquence modifiable ou désactivable en mode avancé, mise à jour immédiate en mode debug ; les fichiers existants sont conservés en cas d'échec
* Éléments de plugins associés présentés sous forme d'étiquettes (plugin + équipement) dans la modale des équipements enregistrés, sans doublons

# 18/11/2026 | v1.7.0
* Ajout du bridge "vigipool"
* Modernisation de l'interface : nouvelle feuille de style compatible avec les thèmes clair et sombre de Jeedom, titres de section avec icônes, une icône distincte par action et compteurs d'équipements
* Modales Réseau et Équipements revues : cartes Jeedom / routeur, filtre instantané, compteurs (masqués à zéro, dont « hors ligne » et « non enregistrés »), en-tête de tableau fixe, badges d'état et case « Tout sélectionner »
* Barre de progression pendant le scan (étape en cours, pourcentage, temps écoulé) depuis le bouton Synchroniser et la modale Réseau, avec un bouton « Scanner » pour relancer un scan
* Enregistrement d'un appareil directement depuis la modale Réseau, et accès direct à la fiche des appareils déjà enregistrés
* Compteurs cliquables pour filtrer les tableaux des modales (en ligne, hors ligne, enregistrés, non enregistrés, changements d'IP)
* Bouton « Enregistrer » sur chaque ligne de la modale des équipements non enregistrés
* Widgets Réseau et Alertes du dashboard modernisés (pastilles d'état, icônes, compatibles thèmes clair et sombre)
* Commentaires enregistrés automatiquement pendant la saisie (plus de bouton « Sauvegarder les commentaires »), modifiables aussi depuis la modale des équipements non enregistrés, et suppression d'un commentaire en vidant son champ
* Configuration : bridges affichés sous forme de pastilles, avec uniquement les plugins installés par défaut et une case pour afficher tous les plugins pris en compte
* Ouverture plus rapide de la modale Réseau (plus de résolution DNS à l'affichage)
* Sécurité : échappement des noms, fabricants et commentaires affichés dans l'interface et les widgets
* Correction du menu de sélection du mode qui restait vide dans la configuration
* Correction des menus Wake-on-LAN, « Présumé hors-ligne » et ordre d'affichage du widget qui restaient vides sur un équipement
* Correction d'un faux « Changement d'Ip » affiché pour les équipements sans dernière IP connue
* Correction du fichier des commentaires qui grossissait à chaque sauvegarde
* Correction des commandes « mac » et « Last IpV4 » qui n'étaient plus mises à jour depuis la v1.6.0 (le widget affichait « ... »)
* Correction du verrou de scan périmé qui permettait de lancer deux scans en parallèle
* Correction de l'alignement des cases des sous-réseaux à scanner
* Correction de l'adresse MAC de Jeedom mal lue quand une ligne « altname » apparaît dans la configuration réseau
* Correction de l'en-tête d'arp-scan enregistré à tort comme un appareil (« Interface: eth0, type: EN10MB… »)
* Barre de progression qui avance pendant le scan d'un sous-réseau, avec une estimation du temps restant
* Documentation : captures d'écran mises à jour avec la nouvelle interface
* Mode debug : les vérifications réussies affichent « À jour » au lieu de « Pas à jour »
* Le bouton Debug et les options des modes avancé et debug apparaissent dès le changement de mode, sans recharger la page
* Configuration : les interfaces réseau sans adresse IPv4 (interfaces virtuelles Docker, cartes non connectées) ne sont plus proposées au scan
* Traductions complétées en anglais, allemand, espagnol et italien, et correction du fichier allemand qui contenait des textes en italien
* Compatibilité PHP 7 et PHP 8 : suppression des avertissements « deprecated » sous PHP 8
* Jeedom 4.0 minimum requis

# 08/11/2026 | v1.6.0
* Ajout des bridges "tvremote" et "tvByOrange"
* Mise à jour des fichiers fabricants ieee-oui (40 458 entrées, +5 578) et ieee-iab
* Optimisation d'arp-scan (pas de résolution DNS, délais d'attente réduits) et choix du nombre de tentatives (1 ou 2) dans la configuration
* Optimisation des performances du scan : commandes chargées en une seule fois, mises à jour uniquement quand la valeur change, sauvegardes et rafraîchissements de tuile limités au strict nécessaire
* Recherche du fabricant en ligne fiabilisée : recherche sur l'OUI de l'adresse MAC, délai maximum par requête et 3 recherches au maximum par scan pour ne plus bloquer le cron
* Correction du nom de l'équipement associé par un bridge qui ne s'affichait pas dans la liste des équipements
* Correction de la mise à jour du plugin qui s'interrompait en erreur, et enregistrement automatique de la version pour que les scans ne restent plus suspendus après une mise à jour
* Corrections de compatibilité PHP 8 et nettoyage du code

# 03/06/2025 | v1.5.2
* Ajout du bridge "tydom" par Flobul

# 02/01/2025 | v1.5.1
* Ajout de 2 bridges "mirobot" et "sshmanager" par Flobul

# 26/09/2024 | v1.5
* Mise en place des traduction EN, DE, ES et IT

# 07/09/2024 | v1.24
* Correction de bugs pour la version debian 12 par vegeta0911

# 04/01/2024 | v1.23
* Mise à jour du ieee-oui et du ieee-iab

# 20/11/2023 | v1.22
* Ajout de la colonne de valeurs des commandes
* Ajout de valeurs de config par défaut à la première installation du plugin

# 14/11/2023 | v1.21
* Corrige la configuration d'un nouveau équipement qui disparait.
* Corrige la popup qui indique que l'appareil risque d'apparaire hors ligne.

# 20/02/2022 | v1.2
* Intégration des CRAN dans le moteur de tache du système Jeedom
* Fixe erreurs Scan OnLine
* Fixe erreurs des alertes multi Core Error au chargement de la page
* Fix Bug de la perte de commentaires (#73803)
* Fixe Bug affichage de la versino dans la configuration
* Fixe Last IP (tableau des équipements enregistrés)
* Fixe Ajout de la colonne On Line equipement offline
* Amélioration hauteur tableaux équipement

# 04/02/2022 | v1.1
* Correction état de connexion sur la popin Network

# 01/02/2022
* Fixe bug - Erreur javascript lors de l’affichage de la stupidtable dans le widget network (#77688)

# 07/10/2021
* Fix bug : désactivation du widget d'Alerte (#66586)

# 03/08/2021
* Ajouter un point vert/rouge indiquant online/offline par Flobul
* Pour éviter les espaces entre les boutons (mode rounded) par Flobul

# 23/07/2021 | Beta & Stable
* Fix bug au niveau des tries d'IP v4

# 01/07/2021 | Beta & Stable

* Affichage du MAC Id en mode Debug
* Ajout des erreurs MAC Id dans la modale Debug
* Maj. Documentation sous réseau

# 18/06/2021 | Stable

* Merge Beta v1.1.1
* Fix bug : trie par date (#61919)

# 07/12/2021 | Beta v1.1.1

* Correction du bridge "script" (erreur d'enregistrement et de mise à jour)
* Ajout d'un Warning quand on associe le bridge "core" à un équipement

# 05/01/2021 | Stable v1.1

* Modifications majeures pour gérer les changements d’Ip et d’adresse MAC quand on a des répéteurs
* Gros travail sur la partie mise à jour (backup, vérification des données, check et debugs automatisés, attente de la fin des scans en cours et désactivation des cron le temps de la mise à jour car sources de bugs)
* Correction bug affichage de la plage des ip dans les sous réseaux
* Correction bug suppression des orphelins quand on supprime un équipement bridgé
* Mode developpement (pour me faciliter la vie dans les developpement)
* Un peu plus d’infos dans la partie équipement
* Retour à la normal du widget Alertes (seuls les éléments non enregistrés s’affichent)
* Fix erreur v1.1 pour raccorder les bridges (config)

# 17/11/2020 | Stable v1

# 07/10/2020 | Beta v1
