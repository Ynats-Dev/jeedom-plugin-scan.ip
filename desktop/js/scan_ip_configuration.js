
getDataConfig();

function getDataConfig() {
    $.getJSON("/plugins/scan_ip/core/ajax/scan_ip.ajax.conf.php", function (result) {
        $("#dataConfig").html("");
        $.each(result, function (key, value) {
            $("#dataConfig").append("<div id='"+key+"' data-config='"+value+"'></div>");
        });
    });
}

function showVersion(){
    var giveVersionByJeedom = $("#span_plugin_install_date").html();
    var versionPlugin = $("#version_plugin").attr("data-config");
    $("#span_plugin_install_date").html("");
    $("#span_plugin_install_date").append( "v" + versionPlugin + " ("+giveVersionByJeedom+")");
}

// Mise à jour des fichiers constructeurs (oui.txt / iab.txt) depuis le site de l'IEEE
function scanIpUpdateVendorFiles() {
    var $bt = $('#bt_scanIpUpdateVendorFiles');
    if ($bt.hasClass('disabled')) {
        return;
    }
    $bt.addClass('disabled').find('i, svg').first().attr('class', 'fas fa-spinner fa-spin');
    $('#scan_ip_vendor_files_status').text("{{Téléchargement en cours, cela peut prendre une minute...}}");

    $.ajax({
        type: "POST",
        url: "plugins/scan_ip/core/ajax/scan_ip.ajax.php",
        data: {action: "updateVendorFiles"},
        dataType: 'json',
        global: false,
        timeout: 420000,
        error: function (request, status, error) {
            $bt.removeClass('disabled').find('i, svg').first().attr('class', 'fas fa-download');
            $('#scan_ip_vendor_files_status').text('');
            handleAjaxError(request, status, error);
        },
        success: function (data) {
            $bt.removeClass('disabled').find('i, svg').first().attr('class', 'fas fa-download');
            if (data.state != 'ok') {
                $('#scan_ip_vendor_files_status').text('');
                $('#div_alert').showAlert({message: data.result, level: 'danger'});
                return;
            }
            var messages = [];
            var allOk = true;
            $.each(data.result, function (name, res) {
                var $status = $('.scanip-vendor-file[data-file="' + name + '"]');
                if (res.ok) {
                    $status.css('color', 'green').text(name + ' ({{Installé}} · ' + res.date + ')');
                    messages.push(name + ' : ' + res.count + ' {{entrées}}');
                } else {
                    allOk = false;
                    messages.push(name + ' : ' + res.message);
                }
            });
            if (allOk) {
                var now = new Date();
                var pad = function (n) { return (n < 10 ? '0' : '') + n; };
                $('#scan_ip_vendor_last').text(pad(now.getDate()) + '/' + pad(now.getMonth() + 1) + '/' + now.getFullYear() + ' ' + pad(now.getHours()) + ':' + pad(now.getMinutes()));
            }
            $('#scan_ip_vendor_files_status').text(messages.join(' · '));
            $('#div_alert').showAlert({message: messages.join(' · '), level: allOk ? 'success' : 'warning'});
        }
    });
}

$('#bt_scanIpUpdateVendorFiles').off('click').on('click', scanIpUpdateVendorFiles);

// Libellé de la fréquence affiché en mode normal
function scan_ip_vendor_label() {
    $('#scan_ip_vendor_auto_label').text($('#scan_ip_vendor_auto option:selected').text());
}
$('#scan_ip_vendor_auto').off('change').on('change', scan_ip_vendor_label);

// Sélecteur de mode : pilote la liste enregistrée par Jeedom (bouton Sauvegarder de la configuration)
var scanIpModeDescriptions = {
    normal: "{{L'essentiel : widgets, rafraîchissement, fichiers constructeurs et bridges.}}",
    advanced: "{{En plus : votre réseau (routeur, Jeedom), nombre d'essais, plages à scanner et fréquence des fichiers constructeurs.}}",
    debug: "{{En plus du mode avancé : vérification des données, dépendances et mise à jour manuelle des fichiers constructeurs.}}"
};

$('#scan_ip_mode_switch').off('click', 'a').on('click', 'a', function () {
    $('#scan_ip_mode').val($(this).data('mode')).trigger('change');
});

function scan_ip_mode_plugin() { 
    var mode = $( "#scan_ip_mode" ).val();
    // Aucune valeur enregistrée : on affiche le mode normal par défaut
    if (!mode) {
        mode = "normal";
        $( "#scan_ip_mode" ).val(mode);
    }
    $('#scan_ip_mode_switch a').each(function () {
        $(this).toggleClass('is-active', $(this).data('mode') == mode);
    });
    $('#scan_ip_mode_description').text(scanIpModeDescriptions[mode] || '');
    // Fichiers constructeurs : mise à jour mensuelle par défaut, fréquence modifiable en mode avancé,
    // mise à jour manuelle en mode debug
    if (!$('#scan_ip_vendor_auto').val()) {
        $('#scan_ip_vendor_auto').val('monthly');
    }
    scan_ip_vendor_label();
    $('.scanip-vendor-normal').toggle(mode == "normal");
    $('.scanip-vendor-advanced').toggle(mode != "normal");
    $('.scanip-vendor-debug').toggle(mode == "debug");

    // Éléments de la page du plugin dépendant du mode : mis à jour sans recharger la page
    $('#bt_scanIpDebug').toggle(mode == "debug");
    $('#scan_ip_debug_macid').toggle(mode == "debug");
    $('#show_off_line').toggle(mode != "normal");

    if(mode == "normal"){
        $('#show_debug').hide();
        $('#show_sous_reseau').hide();
        $('#show_avance').hide();
    } 
    else if(mode == "advanced"){
        $('#show_debug').hide();
        $('#show_sous_reseau').show();
        $('#show_avance').show();
    }
    else if(mode == "debug"){
        $('#show_debug').show();
        $('#show_sous_reseau').show();
        $('#show_avance').show();
    }
}

// Bridges : gestion dans une modale
// La configuration est souvent affichée dans une fenêtre : on la ferme avant d'ouvrir celle des bridges,
// sinon cette dernière s'ouvre derrière
$('#bt_scanIpConfigBridges').off('click').on('click', function () {
    var $button = $(this);
    var closed = false;

    // Fenêtre jQuery UI (Jeedom 4.0 à 4.3)
    var $uiDialog = $button.closest('.ui-dialog-content');
    if ($uiDialog.length) {
        try { $uiDialog.dialog('close'); closed = true; } catch (e) {}
    }
    // Fenêtre jeeDialog (Jeedom 4.4 et plus)
    var jeeDialog = $button.closest('.jeeDialog')[0];
    if (!closed && jeeDialog) {
        try {
            if (jeeDialog._jeeDialog && typeof jeeDialog._jeeDialog.close === 'function') {
                jeeDialog._jeeDialog.close();
            } else {
                $(jeeDialog).find('.btClose').first().trigger('click');
            }
            closed = true;
        } catch (e) {}
    }

    setTimeout(function () {
        $('#md_modal').dialog({title: "{{Bridges}}"});
        $('#md_modal').load('index.php?v=d&plugin=scan_ip&modal=bridges').dialog('open');
    }, closed ? 200 : 0);
});

setTimeout(function(){
    showVersion();
    scan_ip_mode_plugin();
}, 150);

$("#reloadMajPlugin").click(function() {
    $.ajax({
        type: "POST",
        url: "plugins/scan_ip/core/ajax/scan_ip.ajax.maj.php",
        data: {
            action: "reloadMaj",
        },
        dataType: 'json',
        error: function (request, status, error) {
            handleAjaxError(request, status, error);
        },
        success: function (data) {
            if (data.state != 'ok') {
                $('#div_alert').showAlert({message: data.result, level: 'danger'});
                return;
            }
            window.location.reload();
        }
    });
});
