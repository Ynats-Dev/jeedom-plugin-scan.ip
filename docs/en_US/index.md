---
layout: default
lang: en_US
---

Description
===

This plugin is used to scan your network and retrieve the list of connected devices along with their MAC addresses.

With this information, the plugin allows:
- retrieve a device's new IP address
- display a widget that references the status of your network
- display a widget that shows unregistered devices that have just connected to the network
- display a widget for each registered device with access to the commands associated with that device
- receive alerts when a device disappears, appears, or changes its IP address on the network
- automate cascading IP changes in other plugins through bridges
- provide the ability to turn on a device remotely (Wake-on-LAN)

Installation and Dependencies
===

- This plugin strictly requires "arp-scan," "wakeonlan," and "etherwake."
- The use of "oui.txt" and "iab.txt" files provided by "standards-oui.ieee.org"
- APIs provided by "macvendors.com" and "macvendors.co"

# Configuration

To facilitate the use of this plugin, it allows adapting the options offered in three modes: "normal", "advanced", and "debug."

## Normal Mode

The normal mode displays the main parameters, which generally adapt to standard configurations.

![scan_ip1](../images/Config.Normal.png)

### Network Widget

This option allows you to display or hide the network display widget.

![scan_ip2](../images/Widget.network.png)

> You have the option to sort the columns with a simple click.

> You also have the option to configure a default sort by going to the device linked to this widget.

### Alerts Widget

This option allows you to display or hide the alerts display widget.

![scan_ip5](../images//Widget.Alerte.png)

> This widget only displays unregistered devices, and the display order is sorted by ((dateConnexion).(datePremierReferencement)). Thus, a newly connected device will appear first.

### Refresh Rate

Used to adjust the refresh rate of the scheduled task. By default, it is set to 1 minute.

> Note that with each network scan, not all devices necessarily respond, as they take too long to respond (e.g., Wi-Fi devices). It is therefore recommended to perform at least three scans before determining that a device is truly offline. You will see that you have the option to fine-tune this parameter at the device level.

### List of Supported Plugins

Scan.ip uses bridges to update data on certain plugins. An indicator will let you see if the plugin is present in your Jeedom or not.

> In practice, this allows automating, through bridges, the IP address change of a device in other plugins when it decides to change its IP (for example, when you are unable to set a fixed IP in the DHCP).

You can associate a device with one or more plugins.

## Advanced Mode

![scan_ip7](../images/Config.Avance.png)

### Router

Allows adding the router to the network list. By default, it is removed.

### Jeedom

Allows adding the Jeedom server to the network list. By default, it is removed.

### Retry Option

This allows setting the number of attempts during network scans. Indeed, not all devices respond on the first scan attempt. Note that the more attempts you set, the longer the scan will take to respond.

### Compatibility with Safari Browser

In the Safari web browser, the hidden mode in the selection menu, which only displays unused bridges, is not compatible. Therefore, by activating this option, already used menus will be disabled. This option should only be activated if you encounter the problem on Safari.

### Specify Network Ranges to Scan

When you have multiple network cards or particularly subnets, it is advisable to specify the subnet(s) to scan.

> For information, a subnet may not allow retrieving MAC addresses (blocked at the router or elsewhere). If you encounter this issue, you will need to install another Jeedom on that subnet with a dedicated Scan.ip to manage the same subnet.

## Debug Mode

![scan_ip7](../images/Config.Debug.png)

### Files Present

Used to check the presence of the oui.txt and iab.txt files and their last update dates.

> These two files are used to link a MAC address to a manufacturer.

### Dependencies

Allows you to easily visualize any failed dependencies.

# Device Management

![scan_ip9](../images/AllEquipements.png)

> Devices are separated into two parts: "MAC Devices" and "Widgets Managed by Scan.Ip". These should not be deleted except in case of a bug. To delete them, you will need to activate debug mode to make the "delete" button appear.

## Access Icons

> When changing mode, you will need to refresh the page to see the changes appear.

In debug mode, you will see a debug icon appear.

![scan_ip10](../images/Menu.Debug.png)

### Add a MAC Device

This allows adding a device when you know the MAC address of the device you want to add.

> Note that it is easier to go through "Unregistered Records" which will allow you to add one or more devices on the fly that are present in your network.

### Synchronize

Forces a new scan and updates the devices.

> If a scan is already in progress, the action will not be possible.

### Network

![scan_ip11](../images/Modal.Network.png)

The table displays the entire network:

- The first column indicates whether the device is "Online" or "Offline."
- The second column indicates whether the device is "registered," "registered but disabled," or "unregistered."
- The third column indicates the MAC address of the device.
- The fourth column indicates the IP address of the device.
- The fifth column indicates the device name if registered, or the manufacturer's name preceded by a "|".
- The sixth column allows associating comments with a device (you will need to save the comments for them to be taken into account).
- The seventh column indicates the last appearance date of the device.

> If you notice "|..." in the device names, it means it has not been recognized.

### Registered Devices

![scan_ip12](../images/Modal.Equipemetn.Ok.png)

- The first column indicates whether the device is "Online" or "Offline."
- The second column indicates the device name.
- The third column indicates the MAC address of the device.
- The fourth column indicates the device's IP address.
- The fifth column indicates the IP used during the last connection.
- The sixth column indicates the last appearance date of the device.
- The seventh column indicates all bridges associated with the device. Clicking on these will direct you to the associated plugin's device.

### Unregistered Devices

![scan_ip13](../images/Modal.Equipemetn.No.png)

- The first column allows selecting the device* (to add it "on the fly" to your devices or to delete it from the network's archives).
- The second column indicates the device's name.
- The third column shows the comments associated with the device.
- The fourth column indicates the device's MAC address.
- The fifth column indicates the IP used by the device.
- The sixth column indicates the date of the first record in your network.
- The seventh column indicates the last appearance date of the device.

> * When you select one or more elements, action buttons will appear.

![scan_ip14](../images/Modal.Equipemetn.No.save.png)

### Debug (Only in Debug Mode)

![scan_ip15](../images/Modal.Debug.png)

This modal is designed to help understand potential bugs that may appear.

#### # ip a

Result of the "ip a" command, displaying the list of all networks accessible from your Jeedom.

#### # ip route show default

Displays the IP address of the router associated with your Jeedom.

#### # sudo arp-scan -r 3 --interface=eth0 --localnet --ouifile=ieee-oui.txt

> Here, the "eth0" network is selected, but this is specific to your network.

Result of the "sudo arp-scan -r 3 --interface=eth0 --localnet --ouifile=ieee-oui.txt" command, displaying the list of devices responding to the scan.

> As mentioned above, not all connected devices may appear in a single scan. That’s why it’s important to perform multiple scans for a more accurate report of connected devices.

#### # Devices

Displays the data present in the device json file. This is the history of all devices that have connected to your network.

#### # Mappings

Displays the data present in the mapping json file. This is the last state of your network with the version of "arp-scan" used.

### Configuration

This point was already covered at the beginning.

## Your Devices

### In Normal Mode

![scan_ip16](../images/Equipement.Normal.png)

#### Search for a MAC Address

This selection menu allows you to directly search for an element in your network and retrieve its MAC address.

> Only unassociated MAC addresses are present to avoid duplicates.

#### Associated MAC Address

This field is:
- either automatically filled with the selection menu "Search for a MAC Address"
- or you can manually fill it if you want to anticipate devices entering your network

#### Manufacturer

If the manufacturer has been identified, it will be displayed here.

#### Wake-on-LAN

This allows adding a WoL action command to remotely turn on a device.

> Ensure your device is compatible and/or the option is enabled for it to work.

#### Association

![scan_ip6](../images/Equipement.Bridge.png)

As seen in the configuration section, this is where plugins are associated with Scan.Ip using bridges.

> In the selection menu, the information is displayed as follows [plugin name][registered IP in the plugin::Subconfig name (if present)] Plugin's target device name.

> Warning: this option modifies other plugins. Use with caution. The author of the target plugin is not responsible for any malfunctions this might create in their plugin.

> It goes without saying! As long as you do not associate it with a plugin, Scan.ip will make no modifications to it.

> If you are a developer and want to propose an additional bridge, this is possible. Either by proposing a direct addition to GitHub (https://ynats.github.io/jeedom-plugin-scan.ip/) or through the forum.

### In Advanced Mode

![scan_ip17](../images/Equipement.Avanced.png)

#### Reminder "Configuration"

This allows you to keep in mind the configuration you had saved.

#### Presumed Offline After

By default, this is set to 4 minutes, meaning that if everything is left as default, it does one scan per minute, with 4 scans to determine if the device is truly offline.  
Remember, not all devices necessarily show up on every scan.

> If you have set the refresh rate to 3 minutes, you should set this parameter to at least 15 minutes.

> This parameter can be adjusted for LAN devices that are very responsive. It would be appropriate to lower it to 2 minutes, for example, with a 1-minute refresh rate. This would result in 2 scans to determine the device’s state.

### Associated Commands

![scan_ip18](../images/Commande.Wol.png)

- IpV4 (IPv4 format IP of the device)
- Wol (the action command to turn the device on remotely)
- Last Date (Date in DD/MM/YYYY HH:MM:SS format of the device's last appearance on the network)
- Last IpV4 (Last IP used by the device)
- Last Time (Timestamp format date of the last appearance on the network)
- Online (Device state)

### Associated Widgets

![scan_ip9](../images/Widget.Normal.png)

# Widgets Managed by Scan.Ip

![scan_ip19](../images/AllEquipements.png)

By default, this section includes 2 widgets (they can be renamed):
- Scan.Ip Widget Alerts
- Scan.Ip Widget Network

These devices are directly managed by the plugin and correspond to what you have configured in the plugin.

![scan_ip1](../images/Config.Normal.png)

> As a reminder, to manage their display, this is done in the configuration space.

## The Network Widget

![scan_ip20](../images/config.widget.network.png)

## The Alerts Widget

![scan_ip21](../images/config.widget.alerte.png)

#### Associated Commands

10 command groups are created and correspond to the last 10 unregistered devices that have connected. The chronological order starts at zero (the most recent) and ends at nine (the oldest).

Each element has 6 sub-commands:

- Connection 0 Creation (Timestamp format date of the first reference)
- Connection 0 Date (Date in DD/MM/YYYY HH:MM:SS format of the last appearance)
- Connection 0 Time (Timestamp format date of the last appearance)
- Connection 0 Device (Manufacturer name if referenced)
- Connection 0 IpV4 (IPv4 format IP assigned to the device)
- Connection 0 MAC (MAC address of the device)

## Subnets and Scan.Ip

#### If you want to manage multiple networks (e.g., you have a Hub connected to your router)

Scan.Ip is capable of managing multiple networks at the same time.  
To do this, you need to connect your Jeedom to the different networks.  
For example, you have the option to connect your Jeedom PC’s Wi-Fi to one network and the LAN to another. Or simply plug "usb/LAN" or "usb/Wi-Fi" adapters into your Jeedom.  
Scan.Ip will display the subnets, which you can then activate in the plugin’s configuration section (in advanced mode).

![scan_ip22](../images/config.widget.reseaux.png)
