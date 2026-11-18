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

$ipsReseau = scan_ip_json::getJson(scan_ip::$_jsonMapping);

if (empty($ipsReseau)) {
    if(scan_ip_maj::checkPluginVersionAJour() == TRUE){ scan_ip_scan::syncScanIp(); }
    $ipsReseau = scan_ip_json::getJson(scan_ip::$_jsonMapping);
}

$savingMac = scan_ip_eqLogic::getAlleqLogics();
$commentMac = scan_ip_json::getCommentaires();

$eqLogic = scan_ip_eqLogic::searcheqLogicByType("network");
$orderBy = scan_ip_widget_network::getConfigOrder($eqLogic);

?>

<?php
$nbTotal = 0;
$nbOnline = 0;
$nbSaved = 0;
$rows = '';
$list = 1;
$iconsEquipement = array(
    2 => '<i class="fas fa-clipboard-check scanip-icon-on"></i>',
    1 => '<i class="fas fa-clipboard-check scanip-icon-off"></i>',
    0 => '<i class="fas fa-question-circle scanip-icon-none"></i>',
);

foreach ($ipsReseau["sort"] as $device) {

    $element = scan_ip_widget_network::getElementVueNetwork($device, $savingMac, $commentMac);

    $nbTotal++;
    if ($element["lineSortOnline"] == 1) { $nbOnline++; }
    if ($element["lineSortEquipement"] > 0) { $nbSaved++; }

    $rowClass = ($element["lineSortEquipement"] == 0) ? ' class="scanip-row-muted"' : '';

    if ($element["lineSortEquipement"] == 0) {
        $action = '<a class="btn btn-success btn-xs scanip-add-network" data-mac="' . $device["mac"] . '" title="{{Créer un équipement MAC pour cet appareil}}"><i class="fas fa-plus-circle"></i> {{Enregistrer}}</a>';
    } else {
        $action = '<a class="btn btn-default btn-xs" href="index.php?v=d&m=scan_ip&p=scan_ip&id=' . $savingMac[$device["mac_id"]]["id"] . '" title="{{Ouvrir la fiche de l\'équipement}}"><i class="fas fa-external-link-alt"></i> {{Ouvrir}}</a>';
    }

    $rows .= '<tr' . $rowClass . '>'
        . '<td class="scanip-center scanip-keep"><span class="scanip-sort-key">' . $element["lineSortOnline"] . '</span>' . scan_ip_tools::getStatusDot($element["lineSortOnline"], $element["titleOnLine"]) . '</td>'
        . '<td class="scanip-center scanip-keep" title="' . $element["titleEquipement"] . '"><span class="scanip-sort-key">' . $element["lineSortEquipement"] . '</span>' . $iconsEquipement[$element["lineSortEquipement"]] . '</td>'
        . '<td class="scanip-mono">' . $device["mac"] . '</td>'
        . '<td class="scanip-mono"><span class="scanip-sort-key">' . scan_ip_tools::getCleanForSortTable($device["ip_v4"], "int") . '</span>' . $device["ip_v4"] . '</td>'
        . '<td><span class="scanip-sort-key">' . scan_ip_tools::getCleanForSortTable($element["name"], "string") . '</span>' . $element["name"] . '</td>'
        . '<td class="scanip-keep"><span class="scanip-sort-key">' . scan_ip_tools::getCleanForSortTable($element["printComment"], "string") . '</span><input type="text" id="input_' . $list++ . '" data-id="' . $device["mac_id"] . '" value="' . $element["printComment"] . '" class="form-control input-sm" placeholder="{{Ajouter un commentaire}}"></td>'
        . '<td class="scanip-date"><span class="scanip-sort-key">' . scan_ip_tools::getCleanForSortTable($device["time"], "date") . '</span>' . scan_ip_tools::printDate($device["time"]) . '</td>'
        . '<td class="scanip-keep scanip-actions">' . $action . '</td>'
        . '</tr>';
}

$hostName = gethostbyaddr($ipsReseau["jeedom"]["ip_v4"]);
?>

<div class="scanip-modal">

    <div class="scanip-cards">
        <div class="scanip-card">
            <div class="scanip-card-title"><i class="fas fa-server"></i> {{Votre Jeedom}}</div>
            <dl class="scanip-kv">
            <?php if($ipsReseau["jeedom"]["name"] != "") { ?>
                <dt>{{Nom}}</dt><dd><?php echo $ipsReseau["jeedom"]["name"] ?></dd>
            <?php } ?>
                <dt>{{ip}}</dt><dd><?php echo $ipsReseau["jeedom"]["ip_v4"] ?></dd>
                <dt>{{Adresse MAC}}</dt><dd><?php echo $ipsReseau["jeedom"]["mac"] ?></dd>
                <dt>{{Installation}}</dt><dd><?php echo scan_ip_tools::printDate($ipsReseau["jeedom"]["record"]) ?></dd>
            <?php if($hostName != $ipsReseau["jeedom"]["ip_v4"]) { ?>
                <dt>{{Host Name}}</dt><dd><?php echo $hostName ?></dd>
            <?php } ?>
            </dl>
        </div>
        <div class="scanip-card">
            <div class="scanip-card-title"><i class="fas fa-route"></i> {{Votre routeur}}</div>
            <dl class="scanip-kv">
            <?php if(!empty($ipsReseau["route"]["equipement"])) { ?>
                <dt>{{Nom}}</dt><dd><?php echo $ipsReseau["route"]["equipement"] ?></dd>
            <?php } ?>
                <dt>{{ip}}</dt><dd><?php echo $ipsReseau["route"]["ip_v4"] ?></dd>
                <dt>{{Adresse MAC}}</dt><dd><?php echo $ipsReseau["route"]["mac"] ?></dd>
            </dl>
        </div>
    </div>

    <div class="scanip-toolbar">
        <div class="scanip-filter">
            <i class="fas fa-search"></i>
            <input type="text" class="form-control input-sm" placeholder="{{Filtrer (MAC, ip, nom, commentaire...)}}" onkeyup="scanIpFilterTable(this, 'scan_ip_network')">
        </div>
        <div class="scanip-stats">
            <span class="scanip-stat"><i class="fas fa-network-wired"></i> <b><?php echo $nbTotal ?></b> {{appareils}}</span>
            <span class="scanip-stat"><span class="scanip-dot is-on"></span> <b><?php echo $nbOnline ?></b> {{en ligne}}</span>
            <span class="scanip-stat"><i class="fas fa-clipboard-check scanip-icon-on"></i> <b><?php echo $nbSaved ?></b> {{enregistrés}}</span>
        </div>
        <span class="scanip-spacer"></span>
        <span class="scanip-meta"><i class="far fa-clock"></i> <?php echo $ipsReseau["infos"]["date"] ?></span>
        <a id="btSaveCommentaires" class="btn btn-success btn-sm"><i class="far fa-check-circle"></i> {{Sauvegarder les commentaires}}</a>
    </div>

    <div class="scanip-table-wrap">
        <table class="scanip-table" id="scan_ip_network">
            <thead>
                <tr>
                    <th data-sort="string" class="scanip-center" title="{{En ligne}}"><i class="fas fa-signal"></i></th>
                    <th data-sort="int" class="scanip-center" title="{{Elément enregistré}}"><i class="fas fa-clipboard-check"></i></th>
                    <th data-sort="string">{{Adresse MAC}}</th>
                    <th data-sort="int">{{ip}}</th>
                    <th data-sort="string">{{Nom}}</th>
                    <th data-sort="string">{{Commentaire}}</th>
                    <th data-sort="int">{{Date de mise à jour}}</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php echo $rows ?>
            </tbody>
        </table>
    </div>
</div>

<script>
    $("#btSaveCommentaires").click(function() {
        btSaveCommentaires(<?php echo $list ?>);
    });

    $(document).ready(function ($) {
        var $table = $("#scan_ip_network").stupidtable();
        var $th_to_sort = $table.find("thead th").eq("<?php echo scan_ip_widget_network::getOrderBy($orderBy) ?>");
        $th_to_sort.stupidsort();
    });
</script>

<?php include_file('desktop', 'scan_ip', 'css', 'scan_ip'); ?>
<?php include_file('desktop', 'scan_ip_network', 'js', 'scan_ip'); ?>
<?php include_file('3rdparty', 'stupidtable.min', 'js', 'scan_ip'); ?>
