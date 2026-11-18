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
$allNoEquipement = scan_ip_json::showNoEquipements();
$list = 1;
$listComment = 1;
$nbOnline = 0;
$rows = '';

if (!empty($allNoEquipement)) {
    foreach ($allNoEquipement as $equipement) {
        $online = ($equipement["online"] == 0) ? 0 : 1;
        if ($online == 1) { $nbOnline++; }
        $title = ($online == 1) ? __('En ligne', __FILE__) : __('Hors ligne', __FILE__);

        $rows .= '<tr>'
            . '<td class="scanip-center"><input type="checkbox" onclick="is_checked_scan_ip()" id="checked_input_' . $list++ . '" data-mac="' . $equipement["mac"] . '" class="add_element_scan_ip"></td>'
            . '<td class="scanip-center"><span class="scanip-sort-key">' . $online . '</span>' . scan_ip_tools::getStatusDot($online, $title) . '</td>'
            . '<td>' . $equipement["name"] . '</td>'
            . '<td><span class="scanip-sort-key">' . scan_ip_tools::getCleanForSortTable($equipement["comment"]) . '</span><input type="text" id="input_' . $listComment++ . '" data-id="' . $equipement["mac_id"] . '" value="' . htmlspecialchars((string) $equipement["comment"], ENT_QUOTES) . '" class="form-control input-sm" placeholder="{{Ajouter un commentaire}}"></td>'
            . '<td class="scanip-mono">' . $equipement["mac"] . '</td>'
            . '<td class="scanip-mono"><span class="scanip-sort-key">' . scan_ip_tools::getCleanForSortTable($equipement["ip_v4"]) . '</span>' . $equipement["ip_v4"] . '</td>'
            . '<td class="scanip-date"><span class="scanip-sort-key">' . scan_ip_tools::getCleanForSortTable($equipement["record"]) . '</span>' . scan_ip_tools::printDate($equipement["record"]) . '</td>'
            . '<td class="scanip-date"><span class="scanip-sort-key">' . scan_ip_tools::getCleanForSortTable($equipement["time"]) . '</span>' . scan_ip_tools::printDate($equipement["time"]) . '</td>'
            . '</tr>';
    }
}
?>

<div class="scanip-modal">
<?php if (!empty($allNoEquipement)) { ?>
    <div class="scanip-toolbar">
        <div class="scanip-filter">
            <i class="fas fa-search"></i>
            <input type="text" class="form-control input-sm" placeholder="{{Filtrer (MAC, ip, nom, commentaire...)}}" onkeyup="scanIpFilterTable(this, 'scan_ip_no_equipement')">
        </div>
        <div class="scanip-stats">
<?php
            echo scan_ip_tools::getStat(count($allNoEquipement), '<i class="fas fa-question-circle"></i>', '{{non enregistrés}}');
            echo scan_ip_tools::getStat($nbOnline, '<span class="scanip-dot is-on"></span>', '{{en ligne}}');
            echo scan_ip_tools::getStat(count($allNoEquipement) - $nbOnline, '<span class="scanip-dot is-off"></span>', '{{hors ligne}}');
?>
        </div>
        <span class="scanip-spacer"></span>
        <a id="btSaveCommentaires" class="btn btn-success btn-sm"><i class="far fa-check-circle"></i> {{Sauvegarder les commentaires}}</a>
        <span id="show_scan_ip_add" style="display:none;">
            <a class="btn btn-success btn-sm" id="add_scan_ip_equipement"><i class="fas fa-check-circle"></i> {{Ajouter les équipements sélectionnés}}</a>
            <a class="btn btn-danger btn-sm" id="remove_scan_ip_equipement"><i class="fas fa-trash"></i> {{Supprimer les équipements sélectionnés}}</a>
        </span>
    </div>

    <div class="scanip-table-wrap" id="div_scan_ip_equipement_no">
        <table class="scanip-table" id="scan_ip_no_equipement">
            <thead>
                <tr>
                    <th class="scanip-center"><input type="checkbox" id="scan_ip_check_all" title="{{Tout sélectionner}}"></th>
                    <th data-sort="int" class="scanip-center" title="{{En ligne}}"><i class="fas fa-signal"></i></th>
                    <th data-sort="string">{{Nom}}</th>
                    <th data-sort="string">{{Commentaire}}</th>
                    <th data-sort="string">{{Adresse MAC}}</th>
                    <th data-sort="int">{{ip}}</th>
                    <th data-sort="string">{{Enregistrement}}</th>
                    <th data-sort="string">{{Date de mise à jour}}</th>
                </tr>
            </thead>
            <tbody>
                <?php echo $rows ?>
            </tbody>
        </table>
    </div>
<?php } else { ?>
    <div class="scanip-empty" id="div_alert_scan_ip">
        <i class="fas fa-check-circle"></i>
        {{Vous n'avez aucun équipement non enregistré sur le réseau.}}
    </div>
<?php } ?>
</div>

<script>
    $("#add_scan_ip_equipement").click(function() {
        addEquipement(<?php echo $list ?>);
    });

    $("#remove_scan_ip_equipement").click(function() {
        removeEquipement(<?php echo $list ?>);
    });

    $("#btSaveCommentaires").click(function() {
        btSaveCommentaires(<?php echo $listComment ?>);
    });

    $("#scan_ip_check_all").click(function() {
        $(".add_element_scan_ip:visible").prop('checked', $(this).is(':checked'));
        is_checked_scan_ip();
    });
</script>

<?php include_file('desktop', 'scan_ip', 'css', 'scan_ip'); ?>
<?php include_file('3rdparty', 'stupidtable.min', 'js', 'scan_ip'); ?>
<?php include_file('desktop', 'scan_ip_no_equipements', 'js', 'scan_ip'); ?>
<?php include_file('desktop', 'scan_ip_network', 'js', 'scan_ip'); ?>
