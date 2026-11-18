
//$(document).ready(function ($) {
//    $("#scan_ip_network").stupidtable();
//});

function btSaveCommentaires(nb){

    if(nb > 0){
        var commentaires = [];
        for (var i = 1; i <= nb; i++) {
                var val = $("#input_" + i).val();
                if (val) {
                    var id = $("#input_" + i).attr('data-id');
                    commentaires.push([{id: id}, {val: val}]);
            }
        }

        $.ajax({
            type: "POST",
            url: "plugins/scan_ip/core/ajax/scan_ip.ajax.php",
            data: {
                action: "recordCommentaires",
                data: commentaires,
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
                $('#div_alert').showAlert({message: "{{Commentaires sauvegardés}}", level: 'success'});
            }
        });
    }
    
}
// Enregistrer un appareil directement depuis la vue Réseau, puis ouvrir sa fiche
$('#scan_ip_network').off('click', '.scanip-add-network').on('click', '.scanip-add-network', function () {
    var $bt = $(this);
    if ($bt.hasClass('disabled')) {
        return;
    }
    $bt.addClass('disabled').find('i').attr('class', 'fas fa-spinner fa-spin');

    $.ajax({
        type: "POST",
        url: "plugins/scan_ip/core/ajax/scan_ip.ajax.php",
        data: {
            action: "addEquipementFromNetwork",
            mac: $bt.attr('data-mac'),
        },
        dataType: 'json',
        error: function (request, status, error) {
            $bt.removeClass('disabled').find('i').attr('class', 'fas fa-plus-circle');
            handleAjaxError(request, status, error);
        },
        success: function (data) {
            if (data.state != 'ok') {
                $bt.removeClass('disabled').find('i').attr('class', 'fas fa-plus-circle');
                $('#div_alert').showAlert({message: data.result, level: 'danger'});
                return;
            }
            window.location.href = 'index.php?v=d&m=scan_ip&p=scan_ip&id=' + data.result.id;
        }
    });
});
