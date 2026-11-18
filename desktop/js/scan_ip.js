
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

// Recherche : bouton d'effacement
$('#bt_scanIpResetSearch').off('click').on('click', function () {
    $('#in_searchEqlogic').val('').keyup().focus();
});

// Filtre des tableaux des modales : texte saisi + critère du compteur cliqué (data-flags des lignes)
function scanIpFilterTable(input, tableId) {
    var $table = $('#' + tableId);
    var search = $.trim($('[data-filter-table="' + tableId + '"]').val() || '').toLowerCase();
    var flag = $table.attr('data-flag') || '';
    $table.find('tbody tr').each(function () {
        var $tr = $(this);
        var flags = ' ' + ($tr.attr('data-flags') || '') + ' ';
        var okFlag = (flag === '' || flags.indexOf(' ' + flag + ' ') !== -1);
        var okText = true;
        if (search !== '') {
            var text = $tr.find('td').map(function () {
                var $input = $(this).find('input[type="text"]');
                return $(this).text() + ' ' + ($input.length ? $input.val() : '');
            }).get().join(' ').toLowerCase();
            okText = text.indexOf(search) !== -1;
        }
        $tr.toggle(okFlag && okText);
    });
}

// Compteurs cliquables : un clic filtre, un second clic sur le même compteur retire le filtre
$(document).off('click.scanip', '.scanip-stat-filter').on('click.scanip', '.scanip-stat-filter', function () {
    var $stat = $(this);
    var $modal = $stat.closest('.scanip-modal');
    var $table = $modal.find('table.scanip-table').first();
    var filter = $stat.attr('data-filter') || '';
    if ($stat.hasClass('is-active') || filter === '') {
        filter = '';
    }
    $modal.find('.scanip-stat-filter').removeClass('is-active');
    if (filter !== '') {
        $stat.addClass('is-active');
    }
    $table.attr('data-flag', filter);
    scanIpFilterTable(null, $table.attr('id'));
});

// Enregistrer un appareil (modales Réseau et non enregistrés), puis ouvrir sa fiche
$(document).off('click.scanip', '.scanip-add-network').on('click.scanip', '.scanip-add-network', function () {
    var $bt = $(this);
    if ($bt.hasClass('disabled')) {
        return;
    }
    $bt.addClass('disabled').find('i, svg').first().attr('class', 'fas fa-spinner fa-spin');

    $.ajax({
        type: "POST",
        url: "plugins/scan_ip/core/ajax/scan_ip.ajax.php",
        data: {
            action: "addEquipementFromNetwork",
            mac: $bt.attr('data-mac'),
        },
        dataType: 'json',
        error: function (request, status, error) {
            $bt.removeClass('disabled').find('i, svg').first().attr('class', 'fas fa-plus-circle');
            handleAjaxError(request, status, error);
        },
        success: function (data) {
            if (data.state != 'ok') {
                $bt.removeClass('disabled').find('i, svg').first().attr('class', 'fas fa-plus-circle');
                $('#div_alert').showAlert({message: data.result, level: 'danger'});
                return;
            }
            window.location.href = 'index.php?v=d&m=scan_ip&p=scan_ip&id=' + data.result.id;
        }
    });
});

// Lance un scan et affiche son avancement dans $target, puis appelle onDone()
function scanIpStartScan($target, onDone) {
    var finished = false;
    var followOnly = false; // true quand un autre scan (cron) tournait déjà
    var poll = null;

    $target.html(
        '<div class="scanip-progress">'
        + '<div class="scanip-progress-head">'
        + '<i class="fas fa-circle-notch fa-spin"></i>'
        + '<span class="scanip-progress-label">{{Démarrage du scan}}</span>'
        + '<span class="scanip-progress-pct">0 %</span>'
        + '</div>'
        + '<div class="scanip-progress-bar"><div></div></div>'
        + '<div class="scanip-meta scanip-progress-elapsed"></div>'
        + '</div>'
    ).show();

    function render(p) {
        var pct = parseInt(p.percent || 0, 10);
        var remaining = null;
        // Étape longue (arp-scan) : avancement estimé d'après sa durée prévue
        if (p.eta && p.stepEnd && p.stepStart && p.now && p.state == 'running') {
            var spent = Math.max(0, p.now - p.stepStart);
            var ratio = Math.min(0.95, spent / p.eta);
            pct = Math.round(pct + (p.stepEnd - pct) * ratio);
            remaining = Math.max(0, p.eta - spent);
        }
        $target.find('.scanip-progress-bar > div').css('width', pct + '%');
        $target.find('.scanip-progress-pct').text(pct + ' %');
        if (p.label) {
            $target.find('.scanip-progress-label').text(p.label);
        }
        if (p.start && p.now) {
            var info = '{{Temps écoulé}} : ' + Math.max(0, p.now - p.start) + ' s';
            if (remaining !== null) {
                info += ' · {{environ}} ' + remaining + ' s {{restantes}}';
            }
            if (followOnly) {
                info += ' · {{scan automatique déjà en cours, suivi de son avancement}}';
            }
            $target.find('.scanip-progress-elapsed').text(info);
        }
        if (p.state == 'error') {
            $target.find('.scanip-progress').addClass('is-error');
            $target.find('.scanip-progress-head i').attr('class', 'fas fa-exclamation-triangle');
        }
    }

    function finish() {
        if (finished) {
            return;
        }
        finished = true;
        clearInterval(poll);
        render({percent: 100, label: "{{Scan terminé}}"});
        $target.find('.scanip-progress-head i').attr('class', 'fas fa-check-circle');
        setTimeout(onDone, 400);
    }

    function check() {
        $.ajax({
            type: "POST",
            url: "plugins/scan_ip/core/ajax/scan_ip.ajax.php",
            data: {action: "getScanProgress"},
            dataType: 'json',
            global: false,
            success: function (data) {
                if (finished || data.state != 'ok') {
                    return;
                }
                render(data.result);
                // Scan lancé ailleurs (cron) : on s'arrête quand le verrou est levé
                if (data.result.state == 'error') {
                    finished = true;
                    clearInterval(poll);
                } else if (followOnly && !data.result.locked) {
                    finish();
                }
            }
        });
    }

    poll = setInterval(check, 1000);

    $.ajax({
        type: "POST",
        url: "plugins/scan_ip/core/ajax/scan_ip.ajax.php",
        data: {action: "syncEqLogicWithOpenScanId"},
        dataType: 'json',
        global: false,
        error: function (request, status, error) {
            clearInterval(poll);
            finished = true;
            handleAjaxError(request, status, error);
        },
        success: function (data) {
            if (data.state != 'ok') {
                clearInterval(poll);
                finished = true;
                render({percent: 100, label: data.result, state: 'error'});
                return;
            }
            // Si un scan tournait déjà, le suivi continue jusqu'à sa fin
            if (data.result && data.result.alreadyRunning) {
                followOnly = true;
            } else {
                finish();
            }
        }
    });
}

// Sur la partie NetWork
$('#bt_scanIpNetwork').off('click').on('click', function () {
    $('#md_modal').dialog({title: "{{Afficher le Réseau}}"});
    $('#md_modal').load('index.php?v=d&plugin=scan_ip&modal=network').dialog('open');
});

// Sur la partie Equipement
$('#bt_scanIpEquipementYes').off('click').on('click', function () {
    $('#md_modal').dialog({title: "{{Vos équipements enregistrés}}"});
    $('#md_modal').load('index.php?v=d&plugin=scan_ip&modal=yes_equipement').dialog('open');
});

$('#bt_scanIpEquipementNo').off('click').on('click', function () {
    $('#md_modal').dialog({title: "{{Vos équipements non enregistrés}}"});
    $('#md_modal').load('index.php?v=d&plugin=scan_ip&modal=no_equipement').dialog('open');
});

// Sur la partie Debug
$('#bt_scanIpDebug').off('click').on('click', function () {
    $('#md_modal').dialog({title: "{{Debug}}"});
    $('#md_modal').load('index.php?v=d&plugin=scan_ip&modal=debug').dialog('open');
});

// Sur la partie Développeur
$('#bt_scan_ip_dev').off('click').on('click', function () {
    $.ajax({
        type: "POST",
        url: "plugins/scan_ip/core/ajax/scan_ip.ajax.dev.php",
        data: {
            action: "reset",
        },
        dataType: 'json',
        error: function (request, status, error) {
            handleAjaxError(request, status, error);
        },
        success: function (data) {
            $('#div_alert').showAlert({message: "{{Mode développeur : Reset Ok (Penser à réactiver le CRON)}}", level: 'warning'});
        }
    });
});

function reloadModal(idModal) {
    $('#md_modal').load('index.php?v=d&plugin=scan_ip&modal=' + idModal).dialog('close');
    setTimeout(function () {
        $('#md_modal').load('index.php?v=d&plugin=scan_ip&modal=' + idModal).dialog('open');
    }, 200);
}
 