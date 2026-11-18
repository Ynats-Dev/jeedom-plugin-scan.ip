<?php
/* This file is part of Jeedom.
 *
 * Jeedom is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Jeedom is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Jeedom. If not, see <http://www.gnu.org/licenses/>.
 */

if (!isConnect('admin')) {
    throw new Exception('{{401 - Accès non autorisé}}');
}

require_once dirname(__FILE__) . "/../../../../plugins/scan_ip/core/class/scan_ip.require_once.php";

?>

<?php
$allEquipements = scan_ip_eqLogic::showEquipements();
$nbOnline = 0;
$nbChanged = 0;
$rows = '';

if (!empty($allEquipements)) {
    foreach ($allEquipements as $equipement) {

        if ($equipement["on_line"] == 0) {
            $equipement["ip_v4"] = "…";
            $badge = '<span class="scanip-badge is-off"><span class="scanip-dot is-off"></span> ' . __('Hors ligne', __FILE__) . '</span>';
            $sortOnLine = 0;
            $sortip_v4 = 0;
        } else {
            $nbOnline++;
            $badge = '<span class="scanip-badge is-on"><span class="scanip-dot is-on"></span> ' . __('En ligne', __FILE__) . '</span>';
            $sortOnLine = 1;
            $sortip_v4 = scan_ip_tools::getCleanForSortTable($equipement["ip_v4"]);
        }

        if (empty($equipement["last_ip_v4"]) OR $equipement["last_ip_v4"] == "...") {
            $equipement["last_ip_v4"] = "…";
            $sortlast_ip_v4 = 0;
        } else {
            $sortlast_ip_v4 = scan_ip_tools::getCleanForSortTable($equipement["last_ip_v4"]);
        }
        if (empty($equipement["update_date"]) OR $equipement["update_date"] == "...") { $equipement["update_date"] = "…"; }
        if (empty($equipement["plug_element_plugin"])) { $equipement["plug_element_plugin"] = '<span class="scanip-meta">…</span>'; }

        $classLast = "";
        if ($equipement["on_line"] == 1 AND $equipement["last_ip_v4"] != "…" AND $equipement["ip_v4"] != $equipement["last_ip_v4"]) {
            $nbChanged++;
            $classLast = " scanip-text-warn";
            $badge = '<span class="scanip-badge is-warn"><i class="fas fa-exchange-alt"></i> ' . __("Changement d'Ip", __FILE__) . '</span>';
        }

        $rows .= '<tr>'
            . '<td><span class="scanip-sort-key">' . $sortOnLine . '</span>' . $badge . '</td>'
            . '<td>' . $equipement["link"] . '</td>'
            . '<td class="scanip-mono">' . $equipement["mac"] . '</td>'
            . '<td class="scanip-mono"><span class="scanip-sort-key">' . $sortip_v4 . '</span>' . $equipement["ip_v4"] . '</td>'
            . '<td class="scanip-mono' . $classLast . '"><span class="scanip-sort-key">' . $sortlast_ip_v4 . '</span>' . $equipement["last_ip_v4"] . '</td>'
            . '<td class="scanip-date"><span class="scanip-sort-key">' . scan_ip_tools::getCleanForSortTable($equipement["update_date"], "date") . '</span>' . $equipement["update_date"] . '</td>'
            . '<td><span class="scanip-sort-key">' . scan_ip_tools::getCleanForSortTable(strip_tags($equipement["plug_element_plugin"])) . '</span>' . $equipement["plug_element_plugin"] . '</td>'
            . '</tr>';
    }
}
?>

<div class="scanip-modal">
<?php if (!empty($allEquipements)) { ?>
    <div class="scanip-toolbar">
        <div class="scanip-filter">
            <i class="fas fa-search"></i>
            <input type="text" class="form-control input-sm" placeholder="{{Filtrer (MAC, ip, nom, plugin...)}}" onkeyup="scanIpFilterTable(this, 'scan_ip_equipement')">
        </div>
        <div class="scanip-stats">
<?php
            echo scan_ip_tools::getStat(count($allEquipements), '<i class="fas fa-clipboard-check"></i>', '{{enregistrés}}');
            echo scan_ip_tools::getStat($nbOnline, '<span class="scanip-dot is-on"></span>', '{{en ligne}}');
            echo scan_ip_tools::getStat(count($allEquipements) - $nbOnline, '<span class="scanip-dot is-off"></span>', '{{hors ligne}}');
            echo scan_ip_tools::getStat($nbChanged, '<i class="fas fa-exchange-alt"></i>', "{{changement(s) d'ip}}", 'scanip-text-warn');
?>
        </div>
    </div>

    <div class="scanip-table-wrap" id="div_scan_ip_equipement_yes">
        <table class="scanip-table" id="scan_ip_equipement">
            <thead>
                <tr>
                    <th data-sort="int">{{Statut}}</th>
                    <th data-sort="string">{{Nom}}</th>
                    <th data-sort="string">{{Adresse MAC}}</th>
                    <th data-sort="int">{{ip}}</th>
                    <th data-sort="int">{{Dernière ip}}</th>
                    <th data-sort="string">{{Mis à jour}}</th>
                    <th data-sort="string">{{Elément plugin associé}}</th>
                </tr>
            </thead>
            <tbody>
                <?php echo $rows ?>
            </tbody>
        </table>
    </div>
<?php } else { ?>
    <div class="scanip-empty" id="div_alert_scan_ip">
        <i class="fas fa-inbox"></i>
        {{Pour le moment, vous n'avez pas enregistré d'équipement MAC.}}
    </div>
<?php } ?>
</div>

<?php include_file('desktop', 'scan_ip', 'css', 'scan_ip'); ?>
<?php include_file('3rdparty', 'stupidtable.min', 'js', 'scan_ip'); ?>
<?php include_file('desktop', 'scan_ip_yes_equipements', 'js', 'scan_ip'); ?>
