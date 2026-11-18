
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

function recordBtMac() {
    $.ajax({
        type: "POST",
        url: "plugins/scan_ip/core/ajax/scan_ip.ajax.php",
        data: {
            action: "recordMacBouton",
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
}

function scan_ip_mode_plugin() { 
    var mode = $( "#scan_ip_mode" ).val();
    // Aucune valeur enregistrée : on affiche le mode normal par défaut
    if (!mode) {
        mode = "normal";
        $( "#scan_ip_mode" ).val(mode);
    }
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
