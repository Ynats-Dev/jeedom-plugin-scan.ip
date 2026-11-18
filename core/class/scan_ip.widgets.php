<?php

/**
 * Description of scan_ip
 *
 * @author Ynats
 */

require_once __DIR__ . "/../../../../plugins/scan_ip/core/class/scan_ip.require_once.php";

class scan_ip_widgets extends eqLogic {
    
    public static function getWidgetType($_eq) {
        return $_eq->getConfiguration('type_widget', 'normal');
    }
    
    // Style des widgets Réseau et Alertes : le dashboard ne charge pas desktop/css/scan_ip.css,
    // les règles sont donc embarquées et limitées aux classes .scanip-w-*
    public static function getWidgetStyle() {
        return '<style>
            .scanip-w-table { width: 100%; border-collapse: collapse; margin: 0 0 6px 0; font-size: 0.95em; }
            .scanip-w-table thead th { position: sticky; top: 0; z-index: 1; padding: 5px 8px; text-align: left; white-space: nowrap;
                font-size: 0.82em; font-weight: 600; text-transform: uppercase; letter-spacing: 0.03em;
                background: var(--bg-color, #f4f5f7); border-bottom: 1px solid rgba(127,127,127,0.25); }
            .scanip-w-table thead th[data-sort] { cursor: pointer; }
            .scanip-w-table thead th.sorting-asc:after { content: " \2191"; }
            .scanip-w-table thead th.sorting-desc:after { content: " \2193"; }
            .scanip-w-table tbody td { padding: 4px 8px; border-bottom: 1px solid rgba(127,127,127,0.15); vertical-align: middle; }
            .scanip-w-table tbody tr:last-child td { border-bottom: none; }
            .scanip-w-table tbody tr:hover td { background: rgba(127,127,127,0.07); }
            .scanip-w-table .scanip-w-center { text-align: center; width: 28px; }
            .scanip-w-table .scanip-w-mono { font-family: Menlo, Consolas, "Liberation Mono", monospace; font-size: 0.92em; white-space: nowrap; }
            .scanip-w-table .scanip-w-date { white-space: nowrap; opacity: 0.8; }
            .scanip-w-table .scanip-w-sort { display: none; }
            .scanip-w-table tr.scanip-w-muted td:not(.scanip-w-keep) { opacity: 0.6; }
            .scanip-w-dot { display: inline-block; width: 9px; height: 9px; border-radius: 50%; vertical-align: middle; background: #8a949e; }
            .scanip-w-dot.is-on { background: #2fa84f; box-shadow: 0 0 0 3px rgba(47,168,79,0.22); }
            .scanip-w-dot.is-off { background: #e5484d; box-shadow: 0 0 0 3px rgba(229,72,77,0.18); }
            .scanip-w-on { color: #2fa84f; }
            .scanip-w-warn { color: #f0a020; }
            .scanip-w-none { color: #8a949e; }
            .scanip-w-empty { padding: 14px; text-align: center; opacity: 0.6; }
        </style>';
    }
    
    public static function getWidgetDot($_online, $_title) {
        return '<span class="scanip-w-dot ' . ($_online ? 'is-on' : 'is-off') . '" title="' . scan_ip_tools::escape($_title) . '"></span>';
    }
    

}
