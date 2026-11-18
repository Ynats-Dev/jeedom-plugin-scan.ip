
//$(document).ready(function ($) {
//    $("#scan_ip_network").stupidtable();
//});

function btSaveCommentaires(nb){

    if(nb > 0){
        var commentaires = [];
        for (var i = 1; i <= nb; i++) {
            var $input = $("#input_" + i);
            if ($input.length == 0) {
                continue;
            }
            // Valeur vide envoyée aussi : elle supprime le commentaire
            commentaires.push([{id: $input.attr('data-id')}, {val: $.trim($input.val())}]);
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
