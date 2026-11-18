
//$(document).ready(function ($) {
//    $("#scan_ip_network").stupidtable();
//});

// Enregistrement automatique des commentaires (modales Réseau et non enregistrés) :
// 1 seconde après la dernière frappe, ou dès que le champ perd le focus / Entrée
var scanIpCommentTimers = {};

function scanIpSaveComment($input) {
    var id = $input.attr('data-id');
    var val = $.trim($input.val());
    if (!id || $input.data('saved') === val) {
        return;
    }
    clearTimeout(scanIpCommentTimers[id]);
    $input.removeClass('scanip-comment-saved scanip-comment-error').addClass('scanip-comment-saving');

    $.ajax({
        type: "POST",
        url: "plugins/scan_ip/core/ajax/scan_ip.ajax.php",
        data: {
            action: "recordCommentaires",
            data: [[{id: id}, {val: val}]],
        },
        dataType: 'json',
        global: false,
        error: function (request, status, error) {
            $input.removeClass('scanip-comment-saving').addClass('scanip-comment-error');
            handleAjaxError(request, status, error);
        },
        success: function (data) {
            $input.removeClass('scanip-comment-saving');
            if (data.state != 'ok') {
                $input.addClass('scanip-comment-error');
                $('#div_alert').showAlert({message: data.result, level: 'danger'});
                return;
            }
            $input.data('saved', val).addClass('scanip-comment-saved');
            setTimeout(function () { $input.removeClass('scanip-comment-saved'); }, 1500);
        }
    });
}

$(document).off('.scanipComment')
    .on('focus.scanipComment', '.scanip-modal input[data-id][id^="input_"]', function () {
        var $input = $(this);
        if ($input.data('saved') === undefined) {
            $input.data('saved', $.trim($input.val()));
        }
    })
    .on('input.scanipComment', '.scanip-modal input[data-id][id^="input_"]', function () {
        var $input = $(this);
        var id = $input.attr('data-id');
        clearTimeout(scanIpCommentTimers[id]);
        scanIpCommentTimers[id] = setTimeout(function () { scanIpSaveComment($input); }, 1000);
    })
    .on('change.scanipComment', '.scanip-modal input[data-id][id^="input_"]', function () {
        scanIpSaveComment($(this));
    })
    .on('keydown.scanipComment', '.scanip-modal input[data-id][id^="input_"]', function (e) {
        if (e.which == 13) {
            e.preventDefault();
            scanIpSaveComment($(this));
        }
    });
