---
layout: default
lang: es_ES
---

Descripción
===

Este plugin se utiliza para escanear su red y recuperar la lista de dispositivos conectados junto con sus direcciones MAC.

Con esta información, el plugin permite:
- recuperar la nueva IP de un dispositivo
- mostrar un widget que referencia el estado de su red
- mostrar un widget que lista los dispositivos no registrados que se acaban de conectar a la red
- mostrar un widget para cada dispositivo registrado con acceso a los comandos asociados a ese dispositivo
- recibir alertas cuando un dispositivo desaparece, aparece o cambia de IP en la red
- automatizar cambios de IP en cascada en otros plugins a través de bridges
- proporcionar la capacidad de encender un dispositivo de forma remota (Wake-on-LAN)

Instalación y Dependencias
===

- Este plugin requiere "arp-scan", "wakeonlan" y "etherwake".
- El uso de los archivos "oui.txt" e "iab.txt" proporcionados por "standards-oui.ieee.org"
- Las API proporcionadas por "macvendors.com" y "macvendors.co"

# Configuración

Para facilitar el uso de este plugin, permite adaptar las opciones ofrecidas en tres modos: "normal", "avanzado" y "debug".

## Modo normal

El modo normal muestra los parámetros principales, que generalmente se adaptan a configuraciones estándar.

![scan_ip1](../images/Config.Normal.png)

### Widget de Red

Esta opción permite mostrar u ocultar el widget de visualización de red.

![scan_ip2](../images/Widget.network.png)

> Tiene la opción de ordenar las columnas con un simple clic.

> También tiene la opción de configurar un orden de clasificación predeterminado yendo al dispositivo vinculado a este widget.

### Widget de Alertas

Esta opción permite mostrar u ocultar el widget de visualización de alertas.

![scan_ip5](../images//Widget.Alerte.png)

> Este widget solo muestra dispositivos no registrados y el orden de visualización se ordena por ((dateConnexion).(datePremierReferencement)). Por lo tanto, un dispositivo nuevo aparecerá en la primera posición.

### Frecuencia de Actualización

Sirve para ajustar la frecuencia de actualización de la tarea programada.  
Por defecto, está configurado en 1 minuto.

> Cabe señalar que en cada escaneo de la red no necesariamente responden todos los dispositivos, ya que pueden tardar demasiado en responder (por ejemplo, dispositivos Wi-Fi). Se recomienda realizar al menos tres escaneos antes de determinar que un dispositivo está realmente fuera de línea. Verá que puede ajustar más finamente este parámetro a nivel de los dispositivos.

### Lista de Plugins Compatibles

Scan.ip utiliza bridges para actualizar los datos en ciertos plugins. Un indicador le permitirá ver si el plugin está presente en su Jeedom o no.

> En la práctica, esto permite automatizar, a través de bridges, el cambio de IP de un dispositivo en otros plugins cuando decide cambiar su IP (por ejemplo, cuando no es posible asignar una IP fija en el DHCP).

Podrá asociar un dispositivo con uno o más plugins.

## Modo Avanzado

![scan_ip7](../images/Config.Avance.png)

### Router

Permite agregar el router a la lista de red. Por defecto, se elimina.

### Jeedom

Permite agregar el servidor Jeedom a la lista de red. Por defecto, se elimina.

### Opción Retry

Esto permite ajustar el número de intentos durante los escaneos de red. De hecho, no todos los dispositivos responden al primer intento de escaneo. Tenga en cuenta que cuanto más intentos configure, más tiempo tardará el escaneo.

### Compatibilidad con el Navegador Safari

En el navegador Safari, el modo oculto en el menú de selección, que solo muestra los bridges no utilizados, no es compatible. Por lo tanto, al activar esta opción, los menús ya utilizados se desactivarán. Esta opción solo debe activarse si experimenta el problema en Safari.

### Especificar Rangos de Red para Escanear

Cuando tiene varias tarjetas de red o subredes en particular, es recomendable especificar la(s) subred(es) que se deben escanear.

> Para su información, una subred puede no permitir la recuperación de direcciones MAC (bloqueo a nivel del router u otros). Si tiene este problema, deberá instalar otro Jeedom en esa subred con un Scan.ip dedicado para gestionar la misma subred.

## Modo Debug

![scan_ip7](../images/Config.Debug.png)

### Archivos Presentes

Sirve para verificar la presencia de los archivos oui.txt e iab.txt y sus fechas de actualización.

> Estos dos archivos se utilizan para hacer la conexión entre una dirección MAC y un fabricante.

### Dependencias

Permite visualizar fácilmente las dependencias fallidas.

# Gestión de Dispositivos

![scan_ip9](../images/AllEquipements.png)

> Los dispositivos se dividen en dos partes: "Dispositivos MAC" y "Widgets Gestionados por Scan.Ip". No debe eliminar estos últimos a menos que ocurra un error. Para eliminarlos, deberá activar el modo debug para que aparezca el botón "eliminar".

## Iconos de Acceso

> Al cambiar de modo, deberá actualizar la página para ver los cambios.

En modo debug, verá aparecer un icono de debug.

![scan_ip10](../images/Menu.Debug.png)

### Agregar un Dispositivo MAC

Esto permite agregar un dispositivo cuando conoce la dirección MAC del dispositivo que desea agregar.

> Cabe señalar que es más fácil utilizar "Registros no registrados", que le permitirá agregar sobre la marcha uno o varios dispositivos presentes en su red.

### Sincronizar

Fuerza el inicio de un nuevo escaneo y la actualización de los dispositivos.

> Si ya hay un escaneo en curso, la acción no será posible.

### Red

![scan_ip11](../images/Modal.Network.png)

La tabla muestra toda la red:

- La primera columna indica si el dispositivo está "En línea" o "Fuera de línea".
- La segunda columna indica si el dispositivo está "registrado", "registrado pero desactivado" o "no registrado".
- La tercera columna indica la dirección MAC del dispositivo.
- La cuarta columna indica la dirección IP del dispositivo.
- La quinta columna indica el nombre del dispositivo si está registrado, o el nombre del fabricante precedido por un "|".
- La sexta columna permite asociar comentarios a un dispositivo (deberá guardar los comentarios para que se tengan en cuenta).
- La séptima columna indica la última fecha de aparición del dispositivo.

> Si observa "|..." en los nombres de los dispositivos, significa que no ha sido reconocido.

### Dispositivos Registrados

![scan_ip12](../images/Modal.Equipemetn.Ok.png)

- La primera columna indica si el dispositivo está "En línea" o "Fuera de línea".
- La segunda columna indica el nombre del dispositivo.
- La tercera columna indica la dirección MAC del dispositivo.
- La cuarta columna indica la dirección IP del dispositivo.
- La quinta columna indica la IP utilizada durante la última conexión.
- La sexta columna indica la última fecha de aparición del dispositivo.
- La séptima columna indica todos los bridges asociados al dispositivo. Al hacer clic en estos, será dirigido al dispositivo del plugin asociado.

### Dispositivos No Registrados

![scan_ip13](../images/Modal.Equipemetn.No.png)

- La primera columna permite seleccionar el dispositivo* (para agregarlo sobre la marcha entre sus dispositivos o para eliminarlo de los archivos de la red).
- La segunda columna indica el nombre del dispositivo.
- La tercera columna muestra los comentarios asociados al dispositivo.
- La cuarta columna indica la dirección MAC del dispositivo.
- La quinta columna indica la IP utilizada por el dispositivo.
- La sexta columna indica la fecha del primer registro en su red.
- La séptima columna la última fecha de aparición del dispositivo.

> * Cuando selecciona uno o más elementos, aparecerán botones de acción.

![scan_ip14](../images/Modal.Equipemetn.No.save.png)

### Debug (solo en modo Debug)

![scan_ip15](../images/Modal.Debug.png)

Este modal tiene como objetivo ayudar a comprender los posibles errores que podrían surgir.

#### # ip a

Resultado del comando "ip a" que muestra la lista de todas las redes accesibles desde su Jeedom.

#### # ip route show default

Muestra la IP del router asociado a su Jeedom.

#### # sudo arp-scan -r 3 --interface=eth0 --localnet --ouifile=ieee-oui.txt

> Aquí se selecciona la red "eth0", pero esto es específico para su red.

Resultado del comando "sudo arp-scan -r 3 --interface=eth0 --localnet --ouifile=ieee-oui.txt" que muestra la lista de dispositivos que responden al escaneo.

> Como se mencionó anteriormente, es posible que no todos los dispositivos conectados aparezcan en un solo escaneo. Es por eso que es importante realizar varios escaneos para obtener un informe más preciso de los dispositivos conectados.

#### # Dispositivos

Muestra los datos presentes en el archivo json de dispositivos. Este es el historial de todos los dispositivos que se han conectado a su red.

#### # Mappings

Muestra los datos presentes en el archivo json de mappings. Este es el último estado de su red con la versión de "arp-scan" utilizada.

### Configuración

Este punto ya se trató al principio.

## Sus Dispositivos

### En Modo Normal

![scan_ip16](../images/Equipement.Normal.png)

#### Buscar una Dirección MAC

Este menú de selección le permite buscar directamente un elemento en su red y recuperar su dirección MAC.

> Solo se muestran las direcciones MAC no asociadas para evitar duplicados.

#### Dirección MAC Asociada

Este campo:
- se completa automáticamente con el menú de selección "Buscar una Dirección MAC"
- o puede completarlo manualmente si desea anticipar la entrada de dispositivos en su red.

#### Fabricante

Si se ha identificado el fabricante, se mostrará aquí.

#### Wake-on-LAN

Esto permite agregar un comando de acción WoL que permitirá encender un dispositivo de forma remota.

> Verifique que su dispositivo sea compatible y/o que la opción esté activada para que funcione.

#### Asociación

![scan_ip6](../images/Equipement.Bridge.png)

Como se vio en la parte de configuración, aquí es donde se asocian los plugins con Scan.Ip a través de los bridges.

> En el menú de selección, la información se indica de la siguiente manera [nombre del plugin][IP registrada en el plugin:: Subnombre de configuración (si está presente)] Nombre del dispositivo objetivo del plugin.

> Atención, esta opción realiza modificaciones en otros plugins. Úsela con conocimiento de causa. El autor del plugin objetivo no es responsable de los malfuncionamientos que esto pueda causar en su plugin.

> ¡Obviamente! Mientras no asocie un plugin, Scan.ip no realizará ninguna modificación en él.

> Si es un desarrollador y desea proponer un bridge adicional, esto es totalmente posible. Ya sea proponiendo directamente una adición en GitHub (https://ynats.github.io/jeedom-plugin-scan.ip/) o a través del foro.

### En Modo Avanzado

![scan_ip17](../images/Equipement.Avanced.png)

#### Recordatorio "Configuración"

Esto le permite tener presente la configuración que había guardado.

#### Presunto fuera de línea después de

Por defecto, a 4 minutos, lo que significa que si ha dejado todo por defecto, se realiza un escaneo cada minuto y 4 pasadas para determinar si el dispositivo está realmente fuera de línea.  
Recuerde, no todos los dispositivos necesariamente responden a todos los escaneos.

> Si ha configurado la frecuencia de actualización a 3 minutos, eso significa que debe configurar este parámetro en al menos 15 minutos.

> Este parámetro se puede modificar en dispositivos LAN muy reactivos. Sería adecuado bajarlo a 2 minutos, por ejemplo, con una frecuencia de 1 minuto. Esto equivaldría a 2 pasadas para determinar el estado.

### Comandos Asociados

![scan_ip18](../images/Commande.Wol.png)

- IpV4 (IP en formato v4 del dispositivo)
- Wol (el comando de acción para encender el dispositivo de forma remota)
- Last Date (Fecha en formato DD/MM/AAAA HH:MM:SS de la última aparición en la red)
- Last IpV4 (Última IP utilizada por el dispositivo)
- Last Time (Fecha en formato de marca de tiempo de la última aparición en la red)
- Online (Estado del dispositivo)

### Widgets Asociados

![scan_ip9](../images/Widget.Normal.png)

# Widgets Gestionados por Scan.Ip

![scan_ip19](../images/AllEquipements.png)

Por defecto, esta parte incluye 2 widgets (pueden ser renombrados):
- Scan.Ip Widget Alerts
- Scan.Ip Widget Network

Estos dispositivos son gestionados directamente por el plugin y corresponden a lo que ha configurado en el plugin.

![scan_ip1](../images/Config.Normal.png)

> Como recordatorio, para gestionar su visualización se hace en el espacio de configuración.

## El Widget de Red

![scan_ip20](../images/config.widget.network.png)

## El Widget de Alertas

![scan_ip21](../images/config.widget.alerte.png)

#### Comandos Asociados

Se crean 10 grupos de comandos que corresponden a los últimos 10 dispositivos no registrados que se han conectado. El orden cronológico comienza en cero (el más reciente) y termina en nueve (el más antiguo).

Cada elemento tiene 6 subcomandos:

- Conexión 0 Creación (Fecha en formato de marca de tiempo de la primera referencia)
- Conexión 0 Fecha (Fecha en formato DD/MM/AAAA HH:MM:SS de su última aparición)
- Conexión 0 Hora (Fecha en formato de marca de tiempo de su última aparición)
- Conexión 0 Dispositivo (Nombre del fabricante si está referenciado)
- Conexión 0 IpV4 (IP en formato v4 asignada)
- Conexión 0 MAC (Dirección MAC del dispositivo)

## Subredes y Scan.Ip

#### Si desea gestionar varias redes (Ej. tiene un Hub conectado a su router)

Scan.Ip es capaz de gestionar varias redes al mismo tiempo.  
Para ello, debe conectar su Jeedom a las diferentes redes.  
Por ejemplo, tiene la opción de conectar el Wi-Fi de su PC Jeedom a una red y el LAN a otra. O simplemente conectar adaptadores "usb/LAN" o "usb/Wi-Fi" a su Jeedom.  
Scan.Ip le mostrará las subredes que luego podrá activar en la parte de configuración del plugin (en modo avanzado).

![scan_ip22](../images/config.widget.reseaux.png)
