# 18/11/2026 | v1.7.1
* Archivos de fabricantes oui.txt e iab.txt actualizados automáticamente cada mes desde el sitio del IEEE, con la fecha de la última actualización; frecuencia modificable o desactivable en modo avanzado, actualización inmediata en modo debug; se conservan los archivos existentes en caso de fallo
* Elementos de plugins asociados mostrados como etiquetas (plugin + dispositivo) en la ventana de dispositivos registrados, sin duplicados

# 18/11/2026 | v1.7.0
* Añadido el puente "vigipool"
* Modernización de la interfaz: nueva hoja de estilos compatible con los temas claro y oscuro de Jeedom, títulos de sección con iconos, un icono distinto para cada acción y contadores de dispositivos
* Ventanas Red y Dispositivos rediseñadas: tarjetas Jeedom / router, filtro instantáneo, contadores (ocultos cuando valen cero, incluidos «desconectados» y «no registrados»), cabecera de tabla fija, insignias de estado y casilla «Seleccionar todo»
* Barra de progreso durante el escaneo (paso actual, porcentaje, tiempo transcurrido) desde el botón Sincronizar y la ventana Red, con un botón «Escanear» para lanzar un nuevo escaneo
* Registro de un dispositivo directamente desde la ventana Red y acceso directo a la ficha de los dispositivos ya registrados
* Contadores en los que se puede hacer clic para filtrar las tablas (en línea, desconectados, registrados, no registrados, cambios de IP)
* Botón «Registrar» en cada línea de la ventana de dispositivos no registrados
* Widgets Red y Alertas del dashboard modernizados (indicadores de estado, iconos, temas claro y oscuro)
* Comentarios guardados automáticamente al escribir (ya no hay botón «Guardar los comentarios»), editables también desde la ventana de dispositivos no registrados, y eliminación de un comentario vaciando su campo
* Configuración: puentes mostrados como insignias, solo los plugins instalados por defecto y una casilla para mostrar todos los plugins compatibles
* Apertura más rápida de la ventana Red (ya no hay resolución DNS al mostrarla)
* Seguridad: los nombres, fabricantes y comentarios se escapan en la interfaz y los widgets
* Se corrige el selector de modo que quedaba vacío en la configuración
* Se corrigen los menús Wake-on-LAN, «Considerado desconectado» y orden de visualización del widget que quedaban vacíos en un dispositivo
* Se corrige un falso «Cambio de IP» mostrado para dispositivos sin última IP conocida
* Se corrige el archivo de comentarios que crecía en cada guardado
* Se corrigen los comandos «mac» y «Last IpV4» que ya no se actualizaban desde la v1.6.0 (el widget mostraba «...»)
* Se corrige un bloqueo de escaneo caducado que permitía lanzar dos escaneos a la vez
* Se corrige la alineación de las casillas de las subredes
* Se corrige la dirección MAC de Jeedom mal leída cuando aparece una línea «altname» en la configuración de red
* Se corrige la cabecera de arp-scan registrada por error como dispositivo («Interface: eth0, type: EN10MB…»)
* La barra de progreso avanza durante el escaneo de una subred, con una estimación del tiempo restante
* Documentación: capturas de pantalla actualizadas con la nueva interfaz
* Modo debug: las verificaciones correctas muestran «Actualizado» en lugar de «No actualizado»
* El botón Debug y las opciones de los modos avanzado y debug aparecen en cuanto se cambia de modo, sin recargar la página
* Configuración: las interfaces de red sin dirección IPv4 (interfaces virtuales de Docker, tarjetas no conectadas) ya no se proponen para el escaneo
* Traducciones completadas en inglés, alemán, español e italiano, y corrección del archivo alemán que contenía textos en italiano
* Compatibilidad con PHP 7 y PHP 8: eliminados los avisos «deprecated» en PHP 8
* Se requiere como mínimo Jeedom 4.0

# 08/11/2026 | v1.6.0
* Añadidos los puentes "tvremote" y "tvByOrange"
* Actualización de los archivos de fabricantes ieee-oui (40.458 entradas, +5.578) e ieee-iab
* Optimización de arp-scan (sin resolución DNS, tiempos de espera reducidos) y elección del número de reintentos (1 o 2) en la configuración
* Mejoras de rendimiento del escaneo: comandos cargados de una sola vez, actualizados solo cuando cambia el valor, guardados y refrescos del widget limitados a lo estrictamente necesario
* Búsqueda en línea del fabricante más fiable: búsqueda por el OUI de la dirección MAC, tiempo máximo por petición y como máximo 3 búsquedas por escaneo para no bloquear el cron
* Se corrige el nombre del dispositivo asociado mediante un puente que no se mostraba en la lista de dispositivos
* Se corrige la actualización del plugin que se interrumpía con un error; la versión se guarda ahora automáticamente para que los escaneos ya no queden suspendidos tras una actualización
* Correcciones de compatibilidad con PHP 8 y limpieza del código

# 03/06/2025 | v1.5.2
* Puente "tydom" añadido por Flobul

# 02/01/2025 | v1.5.1
* Añadidos 2 puentes: "mirobot" y "sshmanager" por Flobul

# 26/09/2024 | v1.5
* Implementación de las traducciones para EN, DE, ES y IT

# 07/09/2024 | v1.24
* Corrección de errores para la versión de Debian 12 por vegeta0911

# 04/01/2024 | v1.23
* Actualización de ieee-oui y ieee-iab

# 20/11/2023 | v1.22
* Añadida columna de valores de comandos
* Añadidos valores de configuración predeterminados durante la primera instalación del plugin

# 14/11/2023 | v1.21
* Se corrige la configuración de un nuevo dispositivo que desaparece.
* Se corrige el popup que indica que el dispositivo podría aparecer como fuera de línea.

# 20/02/2022 | v1.2
* Integración de CRAN en el motor de tareas del sistema Jeedom
* Corrección de errores en Scan OnLine
* Corrección de errores en las alertas de error múltiple en la carga de la página
* Corrección del error de pérdida de comentarios (#73803)
* Corrección del error de visualización de la versión en la configuración
* Corrección de Last IP (tabla de dispositivos registrados)
* Añadida columna para el estado "On Line" para dispositivos fuera de línea
* Mejora en la altura de las tablas de dispositivos

# 04/02/2022 | v1.1
* Corrección del estado de la conexión en la pop-up de Red

# 01/02/2022
* Corrección de error - Error de JavaScript al mostrar la tabla en el widget de red (#77688)

# 07/10/2021
* Corrección de error: desactivación del widget de Alertas (#66586)

# 03/08/2021
* Añadido un indicador verde/rojo que muestra en línea/fuera de línea por Flobul
* Se evita el espacio entre botones (modo redondeado) por Flobul

# 23/07/2021 | Beta & Estable
* Corrección de error en la ordenación de IPv4

# 01/07/2021 | Beta & Estable
* Visualización del Id MAC en modo Debug
* Añadidos errores de Id MAC en la ventana modal de Debug
* Actualización de la documentación sobre subredes

# 18/06/2021 | Estable
* Fusión de Beta v1.1.1
* Corrección de error: ordenación por fecha (#61919)

# 07/12/2021 | Beta v1.1.1
* Corrección del bridge "script" (error de registro y actualización)
* Añadida una advertencia al asociar el bridge "core" a un dispositivo

# 05/01/2021 | Estable v1.1
* Cambios importantes para gestionar los cambios de IP y de dirección MAC cuando se usan repetidores
* Gran trabajo en la parte de actualización (backup, verificación de datos, comprobaciones y depuración automática, espera del final de los escaneos en curso y desactivación de crons durante la actualización para evitar errores)
* Corrección de error en la visualización del rango de IP en las subredes
* Corrección de error al eliminar huérfanos cuando se elimina un dispositivo conectado por bridge
* Modo de desarrollo (para facilitar el desarrollo)
* Más información en la sección de dispositivos
* Retorno al estado normal del widget de Alertas (solo se muestran los elementos no registrados)
* Corrección de error v1.1 para conectar los bridges (configuración)

# 17/11/2020 | Estable v1

# 07/10/2020 | Beta v1
