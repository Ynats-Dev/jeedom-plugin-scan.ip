
$("#hiden_type_widget").change(function () {
    if($("#hiden_type_widget").val() == "normal" || $("#hiden_type_widget").val() == null){
        $("#hiden_type_normal").show();
        $("#scan_ip_info_widget").hide();
        $("[data-action='copy']").show();
        $("[data-action='configure']").show();
        $("[data-action='remove']").show();
        $("#scan_ip_widget_network_parametre").hide();
        $("#scan_ip_commandes").show();
    } else { 
        $("#hiden_type_normal").hide();
        $("#scan_ip_info_widget").show();
        $("[data-action='copy']").hide();
        $("[data-action='configure']").hide();  
        $("[data-action='remove']").hide();
        if($("#hiden_type_widget").val() == "network"){
            $("#scan_ip_widget_network_parametre").show();
            $("#scan_ip_commandes").hide();
        } else {
            $("#scan_ip_widget_network_parametre").hide();
            $("#scan_ip_commandes").show();
        }
    }
});

// Appelé par Jeedom après le chargement d'un équipement : valeurs par défaut des menus
// jamais enregistrés (sinon Jeedom y met une valeur vide et le menu reste blanc)
function printEqLogic(_eqLogic) {
    var defaults = {
        'offline_time': '4',
        'enable_wol': '0',
        'saveOrderColonWidegetNetwork': 'ip'
    };
    $.each(defaults, function (key, value) {
        var $select = $('.eqLogicAttr[data-l1key="configuration"][data-l2key="' + key + '"]');
        if ($select.length && !$select.val()) {
            $select.val(value);
        }
    });
    scanIpRefreshAssociations(_eqLogic);
}

/* ------------------------------------------------------------------------------------------
 * Associations aux bridges
 * ---------------------------------------------------------------------------------------- */

var scanIpAssocRequest = 0;

// Réapplique les valeurs enregistrées puis grise les éléments déjà associés à une autre adresse MAC.
// Jeedom ne recharge pas la page entre deux équipements : tout est réinitialisé à chaque appel.
var scanIpAssocOptions = null;

function scanIpRefreshAssociations(_eqLogic) {
    var $selects = $('#scanip_associations select.plug_element_plugin');
    if (!$selects.length) {
        return;
    }
    // Liste complète mémorisée au premier passage : chaque rafraîchissement repart de celle-ci
    if (scanIpAssocOptions === null) {
        scanIpAssocOptions = $selects.first().html();
    }
    var configuration = (_eqLogic && _eqLogic.configuration) ? _eqLogic.configuration : null;

    $selects.each(function () {
        var $select = $(this);
        var value = (configuration !== null)
            ? (configuration['plug_element_plugin_' + $select.attr('data-index')] || '')
            : ($select.val() || '');
        $select.html(scanIpAssocOptions);
        scanIpSetAssociationValue($select, value);
    });
    if (configuration !== null) {
        $('#scanip_associations .scanip-assoc-row').removeClass('scanip-assoc-open');
    }
    scanIpUpdateAssociationRows();
    verifEquipement();

    var request = ++scanIpAssocRequest;
    $.getJSON("/plugins/scan_ip/core/ajax/scan_ip.ajax.associations.php", function (result) {
        if (request !== scanIpAssocRequest || !result) {
            return;
        }
        // Éléments déjà associés à un autre équipement : retirés des listes (sauf s'ils sont sélectionnés ici)
        var current = $('#mac_id').val();
        var taken = {};
        $.each(result, function (mac, values) {
            if (mac != current) {
                $.each(values, function (key, value) {
                    taken[value] = true;
                });
            }
        });
        $selects.find('option').filter(function () {
            return this.value !== '' && taken[this.value] === true && !this.selected;
        }).remove();
        $selects.find('optgroup').filter(function () {
            return $(this).children('option').length === 0;
        }).remove();
    });
}

// Sélectionne une valeur ; une valeur absente de la liste est ajoutée pour ne pas être perdue à l'enregistrement
function scanIpSetAssociationValue($select, value) {
    if (value !== '' && !$select.find('option').filter(function () { return this.value === value; }).length) {
        var $option = $('<option>').val(value).attr('data-label', value).text(value);
        $select.append($option);
    }
    $select.val(value);
}

// Affiche les associations renseignées + au moins une ligne vide ; renumérote les lignes visibles
function scanIpUpdateAssociationRows() {
    var $rows = $('#scanip_associations .scanip-assoc-row');
    var hasEmptyVisible = false;
    $rows.each(function () {
        var $row = $(this);
        var filled = !!$row.find('select').val();
        var visible = filled || $row.hasClass('scanip-assoc-open');
        if (!filled && visible) {
            hasEmptyVisible = true;
        }
        $row.toggle(visible);
    });
    if (!hasEmptyVisible && !$rows.filter(':visible').length) {
        $rows.first().addClass('scanip-assoc-open').show();
        hasEmptyVisible = true;
    }
    var num = 0;
    $rows.filter(':visible').each(function () {
        num++;
        $(this).find('.scanip-assoc-label').text('{{Association}} ' + num);
        $(this).find('.scanip-assoc-clear').toggleClass('disabled', !$(this).find('select').val());
    });
    $('#bt_scanipAssocAdd').toggle(!hasEmptyVisible && $rows.filter(':hidden').length > 0);
}

$('#scanip_associations').off('change', 'select.plug_element_plugin').on('change', 'select.plug_element_plugin', function () {
    var $select = $(this);
    if (!$select.val()) {
        $select.closest('.scanip-assoc-row').addClass('scanip-assoc-open');
    }
    scanIpUpdateAssociationRows();
    verifEquipement();
});

$('#scanip_associations').off('click', '.scanip-assoc-clear').on('click', '.scanip-assoc-clear', function () {
    if ($(this).hasClass('disabled')) {
        return;
    }
    var $row = $(this).closest('.scanip-assoc-row');
    $row.find('select').val('').trigger('change');
    // La ligne vidée disparaît s'il reste une autre ligne visible
    if ($('#scanip_associations .scanip-assoc-row:visible').length > 1) {
        $row.removeClass('scanip-assoc-open');
        scanIpUpdateAssociationRows();
    }
});

$('#bt_scanipAssocAdd').off('click').on('click', function () {
    $('#scanip_associations .scanip-assoc-row:hidden').first().addClass('scanip-assoc-open');
    scanIpUpdateAssociationRows();
});

$('#offline_time').change(function () {
    verifCadence();
    getEquipementById();
    getEquipementCommentaire();
});

function getEquipementById() {
    $("#ConstrunctorMac").val("");
    $("#LastMAC").val("");
    $("#LastIp").val("");
    
    $.getJSON("/plugins/scan_ip/core/ajax/scan_ip.ajax.getEquipementById.php", function (result) {
        $.each(result, function (id, value) {
            var current = $('#mac_id').val();
            if (id == current) {
                if(value["equipement"]){
                    $("#ConstrunctorMac").val(value["equipement"]);
                } else {
                    $("#ConstrunctorMac").val("???");
                }
                $("#LastMAC").val(value["mac"]);
                $("#LastIp").val(value["ip_v4"]);
            }
        });
    });
}

function getEquipementCommentaire() {
    $("#Commentaire").val("");

    $.getJSON("/plugins/scan_ip/core/ajax/scan_ip.ajax.getCommentaireById.php", function (result) {
        $.each(result, function (id, value) {
            var current = $('#mac_id').val();
            if (id == current) {
                $("#Commentaire").val(value);
            }
        });
    });
}

function verifCadence() {
    var offline_time = $("#offline_time").val();
    var cron_pass = $("#cronPass").attr('data-cron');
    var delta = offline_time / cron_pass;

    if (offline_time && delta < 2) {
        $('#div_alert_scan_ip_cadence').showAlert({message: "{{Si vous validez cette configuration, il est possible que certains de vos équipements soient indiqués comme hors-ligne alors qu'ils ne le sont pas.}}", level: 'warning'});
    } else {
        $('#div_alert_scan_ip_cadence').hide();
    }
}

function timeCron() {
    $.getJSON("/plugins/scan_ip/core/ajax/scan_ip.ajax.conf.php", function (result) { 
        if(result["mode_plugin"] === "debug"){ 
            $("[data-action='remove']").show();
        } 
        $("#cronPass").attr('data-cron', result["cron_pass"]);
        $("#cronPass").val("{{La cadence de rafraichissement se fait toutes les " + result["cron_pass"] + " minute(s)}}");
        verifCadence();
    });
}

// Avertissements : même plugin associé plusieurs fois, association au bridge core
function verifEquipement() {
    var plugins = {};
    var hasCore = false;
    var message = "";

    $('#scanip_associations select.plug_element_plugin').each(function () {
        var val = $(this).val();
        var plugin = val ? val.split("|")[0] : "";
        $(this).toggleClass('scanip-assoc-core', plugin == "core");
        if (plugin != "") {
            plugins[plugin] = (plugins[plugin] || 0) + 1;
            hasCore = hasCore || plugin == "core";
        }
    });

    $.each(plugins, function (plugin, nb) {
        if (nb > 1) {
            message += "{{Attention, cet équipement est déjà associé plusieurs fois au plugin}} <span style='font-weight: bold;'>" + plugin + "</span>.<br />";
        }
    });
    if (message != "") {
        $('#div_alert_scan_ip_equipement').showAlert({message: message + "{{Il est possible que cela génère des conflits s'il s'agit d'une erreur de votre part.}}", level: 'warning'});
    } else {
        $('#div_alert_scan_ip_equipement').hide();
    }

    if (hasCore) {
        $('#div_alert_scan_ip_equipement_core').showAlert({message: "{{Attention, cet équipement est associé au bridge <span style='font-weight: bold;'>core</span> de votre Jeedom !<br />Ce qui veut dire que Scan.Ip peut modifier votre configuration Jeedom.<br />Cette association s'adresse à des utilisateurs avancés car cela peut générer des erreurs dans votre config globale ou sur le bon fonctionnement d'autres plugins.}}", level: 'warning'});
    } else {
        $('#div_alert_scan_ip_equipement_core').hide();
    }
}

// Synchro
$('#bt_syncEqLogic').off('click').on('click', function () {
    syncEqLogicWithOpenScanId();
});

function syncEqLogicWithOpenScanId() {
    if ($('#scan_ip_sync_progress').length == 0) {
        window.location.reload();
        return;
    }
    $('#bt_syncEqLogic').addClass('disabled');
    scanIpStartScan($('#scan_ip_sync_progress'), function () {
        window.location.reload();
    });
}

// Au changement du menu de sélection on reproduit la valeur dans le champ
$('#scan_ip_mac_select').change(function () {
    var scan_ip_CopyMacId = $('#scan_ip_mac_select').find(":selected").val();
    $("#mac_id").val(scan_ip_CopyMacId);
    getEquipementById();
    getEquipementCommentaire();
});

function addCmdToTable(_cmd) {
    if (!isset(_cmd)) {
        var _cmd = {configuration: {}};
    }
    if (!isset(_cmd.configuration)) {
        _cmd.configuration = {};
    }

    var tr = '<tr class="cmd" data-cmd_id="' + init(_cmd.id) + '">';
    tr += '<td>';
    tr += '<span class="cmdAttr" data-l1key="id" style="display:none;"></span>';
    tr += '<div class="row">';
    tr += '<div class="col-sm-6">';
    tr += '<input class="cmdAttr form-control input-sm" data-l1key="name">';
    tr += '</div>';
    tr += '</div>';
    tr += '</td>';
    tr += '<td>';
    if (init(_cmd.type) == 'info') {
      tr += '<span class="cmdAttr" data-l1key="htmlstate"></span>';
    }
    tr += '</td>';
    tr += '<td>';
    if (is_numeric(_cmd.id)) {
        tr += '<a class="btn btn-default btn-xs cmdAction" data-action="configure"><i class="fa fa-cogs"></i></a> ';
        tr += '<a class="btn btn-default btn-xs cmdAction" data-action="test"><i class="fa fa-rss"></i> {{Tester}}</a>';
    }
    tr += '</td>';
    tr += '</tr>';
    $('#table_cmd tbody').append(tr);
    $('#table_cmd tbody tr:last').setValues(_cmd, '.cmdAttr');
//    if (isset(_cmd.type)) {
//        $('#table_cmd tbody tr:last .cmdAttr[data-l1key=type]').value(init(_cmd.type));
//    }
    jeedom.cmd.changeType($('#table_cmd tbody tr:last'), init(_cmd.subType));
}
