# 08/11/2026 | v1.6.0
* Added "tvremote" and "tvByOrange" bridges
* Updated ieee-oui (40,458 entries, +5,578) and ieee-iab vendor files
* Optimized arp-scan (no DNS resolution, shorter timeouts) and added a setting to choose the number of retries (1 or 2)
* Scan performance improvements: commands loaded at once, updated only when the value changes, saves and tile refreshes limited to what is necessary
* More reliable online vendor lookup: lookup based on the MAC address OUI, maximum delay per request and at most 3 lookups per scan so the cron is no longer blocked
* Fixed the name of the device linked by a bridge not being displayed in the device list
* Fixed the plugin update stopping with an error, and the version is now saved automatically so scans are no longer suspended after an update
* PHP 8 compatibility fixes and code cleanup

# 03/06/2025 | v1.5.2
* Bridge "tydom" added by Flobul

# 02/01/2025 | v1.5.1
* Added 2 bridges: "mirobot" and "sshmanager" by Flobul

# 26/09/2024 | v1.5
* Implementation of translations for EN, DE, ES, and IT

# 07/09/2024 | v1.24
* Bug fixes for Debian 12 version by vegeta0911

# 04/01/2024 | v1.23
* Updated ieee-oui and ieee-iab

# 20/11/2023 | v1.22
* Added column for command values
* Added default configuration values during the first installation of the plugin

# 14/11/2023 | v1.21
* Fixed configuration of a new device that disappears.
* Fixed the popup indicating that the device may appear offline.

# 20/02/2022 | v1.2
* Integration of CRAN into the Jeedom system task engine
* Fixed Scan OnLine errors
* Fixed multi Core Error alerts when loading the page
* Fixed Bug with losing comments (#73803)
* Fixed Bug displaying the version in the configuration
* Fixed Last IP (registered devices table)
* Fixed Added column for On Line status for offline equipment
* Improved equipment table height

# 04/02/2022 | v1.1
* Fixed connection status on the Network pop-in

# 01/02/2022
* Fixed bug - JavaScript error when displaying the stupidtable in the network widget (#77688)

# 07/10/2021
* Fixed bug: disabling the Alerts widget (#66586)

# 03/08/2021
* Added a green/red indicator for online/offline status by Flobul
* Prevented spaces between buttons (rounded mode) by Flobul

# 23/07/2021 | Beta & Stable
* Fixed bug with sorting IPv4

# 01/07/2021 | Beta & Stable
* Display of MAC Id in Debug mode
* Added MAC Id errors in the Debug modal
* Updated subnet documentation

# 18/06/2021 | Stable
* Merged Beta v1.1.1
* Fixed bug: sort by date (#61919)

# 07/12/2021 | Beta v1.1.1
* Fixed the "script" bridge (registration and update error)
* Added a Warning when associating the "core" bridge to a device

# 05/01/2021 | Stable v1.1
* Major changes to manage IP and MAC address changes when using repeaters
* Extensive work on the update section (backup, data verification, checks and automated debugs, waiting for ongoing scans to finish, and disabling crons during the update to avoid bugs)
* Fixed bug displaying IP range in subnets
* Fixed bug removing orphans when deleting a bridged device
* Development mode (to make development easier)
* More information in the device section
* Returned the Alerts widget to normal (only unregistered elements are displayed)
* Fixed error v1.1 for bridging connections (config)

# 17/11/2020 | Stable v1

# 07/10/2020 | Beta v1
