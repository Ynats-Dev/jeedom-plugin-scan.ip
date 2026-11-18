
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
            $('#scan_ip_vendor_files_status').text(messages.join(' · '));
            $('#div_alert').showAlert({message: messages.join(' · '), level: allOk ? 'success' : 'warning'});
        }
    });
}

$('#bt_scanIpUpdateVendorFiles').off('click').on('click', scanIpUpdateVendorFiles);

function scan_ip_mode_plugin() { 
    var mode = $( "#scan_ip_mode" ).val();
    // Aucune valeur enregistrée : on affiche le mode normal par défaut
    if (!mode) {
        mode = "normal";
        $( "#scan_ip_mode" ).val(mode);
    }
    // Mise à jour automatique des fichiers constructeurs : désactivée tant qu'aucune valeur n'est enregistrée
    if (!$('#scan_ip_vendor_auto').val()) {
        $('#scan_ip_vendor_auto').val('0');
    }

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

// Bridges : n'afficher que les plugins installés, sauf si la case est cochée
function scan_ip_filter_bridges() {
    var showAll = $('#scan_ip_show_all_bridges').is(':checked');
    var total = $('#scan_ip_bridges .scanip-bridge').length;
    var installed = $('#scan_ip_bridges .scanip-bridge.is-installed').length;

    $('#scan_ip_bridges').toggleClass('scanip-only-installed', !showAll);
    $('#scan_ip_bridges_none').toggle(!showAll && installed == 0);
    $('#scan_ip_bridges_count').text(installed + ' / ' + total + ' {{installés}}');
}

$('#scan_ip_show_all_bridges').off('change').on('change', scan_ip_filter_bridges);

setTimeout(function(){
    showVersion();
    scan_ip_mode_plugin();
    scan_ip_filter_bridges();
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
