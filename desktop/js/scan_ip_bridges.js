/* Scan.Ip — gestion des bridges (modale) */

var scanIpBridges = {
    rules: [],
    plugins: {},
    sources: {
        eqLogic: "{{Équipement}}",
        logicalId: "{{logicalId}}",
        cmd: "{{Commande}}",
        pluginConfig: "{{Config. du plugin}}",
        php: "{{Code PHP}}"
    },
    operators: {
        '==': "{{est égal à}}",
        '!=': "{{est différent de}}",
        'in': "{{est l'une des valeurs}}",
        'notin': "{{n'est aucune des valeurs}}",
        'contains': "{{contient}}",
        'notcontains': "{{ne contient pas}}",
        'empty': "{{est vide}}",
        'notempty': "{{n'est pas vide}}",
        'regex': "{{correspond à l'expression}}"
    },
    specialKeys: ['@value', '@name', '@eqName', '@isEnable', '@logicalId'],
    fields: [],
    view: 'normal',
    isNewRule: false
};

function scanIpBridgesEscape(value) {
    if (value === null || value === undefined) {
        return '';
    }
    return String(value).replace(/[&<>"']/g, function (c) {
        return {'&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'}[c];
    });
}

function scanIpBridgesAlert(message, level) {
    $('#div_alert_bridges').showAlert({message: message, level: level || 'danger'});
}

function scanIpBridgesAjax(action, data, success, $button) {
    data = data || {};
    data.action = action;
    if ($button) {
        $button.addClass('disabled');
    }
    $.ajax({
        type: 'POST',
        url: 'plugins/scan_ip/core/ajax/scan_ip.ajax.php',
        data: data,
        dataType: 'json',
        global: false,
        timeout: 180000,
        error: function (request, status, error) {
            if ($button) { $button.removeClass('disabled'); }
            handleAjaxError(request, status, error);
        },
        success: function (result) {
            if ($button) { $button.removeClass('disabled'); }
            if (result.state != 'ok') {
                scanIpBridgesAlert(result.result);
                return;
            }
            success(result.result);
        }
    });
}

/* ---------- Présentation ---------- */

// Icône du plugin : celle du plugin installé, sinon celle du Market Jeedom (plugins non installés du catalogue),
// sinon une icône générique
function scanIpBridgesPluginIcon(plugin) {
    if (plugin == 'core') {
        return '<span class="scanip-plugin-icon is-missing"><i class="fas fa-cogs"></i></span>';
    }
    var id = encodeURIComponent(plugin);
    var installed = !!(scanIpBridges.plugins && scanIpBridges.plugins[plugin]);
    var market = ($('#scanip_bridges').data('market') || 'https://market.jeedom.com') + '/filestore/market/plugin/images/' + id + '_icon.png';
    var local = 'plugins/' + id + '/plugin_info/' + id + '_icon.png';
    return '<span class="scanip-plugin-icon"><img src="' + scanIpBridgesEscape(installed ? local : market) + '" data-fallback="' + scanIpBridgesEscape(installed ? market : '') + '"'
        + ' alt="" width="32" height="32" style="width:32px;height:32px;" loading="lazy" onerror="scanIpBridgesIconError(this)"><i class="fas fa-puzzle-piece"></i></span>';
}

function scanIpBridgesIconError(img) {
    var fallback = img.getAttribute('data-fallback');
    if (fallback) {
        img.setAttribute('data-fallback', '');
        img.src = fallback;
        return;
    }
    img.parentNode.className += ' is-missing';
    img.remove();
}

// Nom du plugin avec son icône, et une ligne de détail
function scanIpBridgesPluginCell(plugin, name, detail) {
    return '<div class="scanip-plugin">' + scanIpBridgesPluginIcon(plugin)
        + '<div class="scanip-plugin-text"><div class="scanip-plugin-name">' + scanIpBridgesEscape(name) + '</div>'
        + (detail ? '<div class="scanip-plugin-detail">' + detail + '</div>' : '') + '</div></div>';
}

function scanIpBridgesEmpty(icon, text) {
    return '<div class="scanip-empty scanip-empty-sm"><i class="fas ' + icon + '"></i>' + text + '</div>';
}

/* ---------- Vue normale / avancée ---------- */

function scanIpBridgesIsAdvanced() {
    return scanIpBridges.view == 'advanced';
}

function scanIpBridgesSetView(view) {
    scanIpBridges.view = (view == 'advanced') ? 'advanced' : 'normal';
    try {
        localStorage.setItem('scanip_bridges_view', scanIpBridges.view);
    } catch (e) {}
    $('#scanip_bridges').toggleClass('scanip-view-normal', !scanIpBridgesIsAdvanced()).toggleClass('scanip-view-advanced', scanIpBridgesIsAdvanced());
    $('.scanip-bridges-view a[data-view]').each(function () {
        var active = ($(this).data('view') == scanIpBridges.view);
        $(this).toggleClass('is-active', active);
    });
    // L'emplacement ne se modifie qu'en vue avancée ; le plugin uniquement pour un nouveau bridge
    scanIpBridgesLockFields();
    if (scanIpBridges.rules.length > 0) {
        scanIpBridgesRenderList();
    }
    if (scanIpBridges.fields.length > 0 && $('#scanip_bridges_discover').is(':visible')) {
        scanIpBridgesRenderDiscover();
    }
}

$('#scanip_bridges').off('click', '.scanip-bridges-view a[data-view]').on('click', '.scanip-bridges-view a[data-view]', function () {
    scanIpBridgesSetView($(this).data('view'));
});

(function () {
    var view = null;
    try {
        view = localStorage.getItem('scanip_bridges_view');
    } catch (e) {}
    scanIpBridgesSetView(view || $('#scanip_bridges').data('default-view'));
})();

/* ---------- Liste ---------- */

function scanIpBridgesLoad() {
    scanIpBridgesAjax('bridgesList', {}, function (result) {
        scanIpBridges.rules = result.rules;
        scanIpBridges.plugins = result.plugins;
        scanIpBridges.daemons = result.daemons || [];
        scanIpBridgesRenderList();
    });
}

// Option du démon : seulement pour un plugin qui en a un (ou si elle est déjà cochée, pour pouvoir la retirer)
function scanIpBridgesDaemonVisibility() {
    var plugin = $('#scanip_bridge_plugin').val();
    var hasDaemon = $.inArray(plugin, scanIpBridges.daemons || []) != -1;
    $('#scanip_bridge_daemon_group').toggle(hasDaemon || $('#scanip_bridge_daemon').is(':checked'));
}

function scanIpBridgesConditionsText(rule) {
    if (!rule.conditions || rule.conditions.length == 0) {
        return '<span class="scanip-meta">—</span>';
    }
    return $.map(rule.conditions, function (c) {
        var text = '<code>' + scanIpBridgesEscape(c.key) + '</code> ' + scanIpBridgesEscape(scanIpBridges.operators[c.op] || c.op);
        if (c.op != 'empty' && c.op != 'notempty') {
            text += ' <code>' + scanIpBridgesEscape(c.value) + '</code>';
        }
        return text;
    }).join('<br>');
}

function scanIpBridgesRenderList() {
    var advanced = scanIpBridgesIsAdvanced();
    var filter = $('#scanip_bridges_filter').val().toLowerCase();
    var rows = '';
    var nbShown = 0, nbShownEnabled = 0;

    $.each(scanIpBridges.rules, function (i, rule) {
        // Les deux vues listent les mêmes bridges : ceux qui trouvent des éléments dans vos plugins,
        // et ceux que vous avez créés ou modifiés. Les autres (champ vide, plugin non installé) se consultent
        // dans le catalogue et apparaîtront d'eux-mêmes dès que le champ sera renseigné.
        if (rule.origin != 'user' && !rule.overridden && !(rule.installed && rule.nbElements > 0)) {
            return;
        }
        var haystack = (rule.plugin + ' ' + rule.pluginName + ' ' + rule.key + ' ' + rule.id).toLowerCase();
        if (filter != '' && haystack.indexOf(filter) == -1) {
            return;
        }

        nbShown++;
        if (rule.enabled) { nbShownEnabled++; }

        // Ligne de détail : emplacement (vue avancée) › champ, puis options
        var field = (rule.source == 'php')
            ? '<span class="scanip-adv">' + scanIpBridges.sources.php + ' · </span>' + scanIpBridgesEscape(rule.description)
            : '<span class="scanip-adv">' + scanIpBridgesEscape(scanIpBridges.sources[rule.source] || rule.source) + ' <i class="fas fa-chevron-right scanip-sep"></i> </span>'
              + '<span class="scanip-chip">' + scanIpBridgesEscape(rule.source == 'logicalId' ? 'logicalId' : rule.key) + '</span>'
              + (rule.replace == 'ip' ? ' <span class="scanip-adv" title="{{Seule l\'ip contenue dans la valeur est remplacée}}">· {{ip dans la valeur}}</span>' : '')
              + (rule.daemon ? ' <i class="fas fa-redo scanip-adv" title="{{Redémarre le démon du plugin}}"></i>' : '');

        var origin;
        if (rule.origin == 'user') {
            origin = '<span class="scanip-badge is-on">{{Personnel}}</span>';
        } else if (rule.overridden) {
            origin = '<span class="scanip-badge is-warn">{{Catalogue modifié}}</span>';
        } else {
            origin = '<span class="scanip-badge is-muted">{{Catalogue}}</span>';
        }

        var id = scanIpBridgesEscape(rule.id);
        var actions = '';
        if (rule.installed) {
            actions += '<a class="btn btn-default btn-xs scanip-bridge-test" data-id="' + id + '" title="{{Voir ce que ce bridge modifierait, sans rien enregistrer}}"><i class="fas fa-play"></i> {{Tester}}</a>';
        }
        if (rule.source != 'php') {
            actions += '<a class="btn btn-default btn-xs scanip-bridge-edit" data-id="' + id + '" title="{{Modifier}}"><i class="fas fa-pen"></i></a>';
        }
        if ((rule.origin == 'user' || rule.overridden) && rule.source != 'php') {
            actions += '<a class="btn btn-default btn-xs scanip-bridge-propose" data-id="' + id + '" title="{{Proposer ce bridge pour qu\'il soit ajouté au catalogue}}"><i class="fas fa-share-square"></i></a>';
        }
        if (rule.origin == 'user') {
            actions += '<a class="btn btn-default btn-xs scanip-bridge-delete scanip-btn-danger" data-id="' + id + '" title="{{Supprimer}}"><i class="fas fa-trash"></i></a>';
        } else if (rule.overridden) {
            actions += '<a class="btn btn-default btn-xs scanip-bridge-delete scanip-btn-warn" data-id="' + id + '" title="{{Revenir à la version du catalogue}}"><i class="fas fa-undo"></i></a>';
        }

        var elements = rule.installed
            ? '<span class="scanip-badge is-muted" title="{{Éléments trouvés dans le plugin}}">' + rule.nbElements + ' {{trouvé(s)}}</span> '
              + '<span class="scanip-badge ' + (rule.nbAssociations > 0 ? 'is-on' : 'is-muted') + '" title="{{Éléments associés à une adresse MAC}}"><i class="fas fa-link"></i> ' + rule.nbAssociations + '</span>'
            : '<span class="scanip-badge is-muted">{{non installé}}</span>';

        rows += '<tr class="' + (rule.enabled ? '' : 'scanip-bridge-off') + '">'
            + '<td class="scanip-center scanip-keep"><label class="scanip-switch" title="' + (rule.enabled ? '{{Actif}}' : '{{Inactif}}') + '">'
            + '<input type="checkbox" class="scanip-bridge-toggle" data-id="' + id + '"' + (rule.enabled ? ' checked' : '') + (rule.installed ? '' : ' disabled') + '><span></span></label></td>'
            + '<td>' + scanIpBridgesPluginCell(rule.plugin, rule.pluginName, field) + '</td>'
            + '<td class="scanip-adv">' + (rule.source == 'php' ? '<span class="scanip-meta">—</span>' : scanIpBridgesConditionsText(rule)) + '</td>'
            + '<td class="scanip-nowrap">' + elements + '</td>'
            + '<td>' + origin + '</td>'
            + '<td class="scanip-actions scanip-keep"><div class="scanip-actions-group">' + actions + '</div></td>'
            + '</tr>';
    });

    if (rows == '') {
        rows = '<tr><td colspan="7">'
            + scanIpBridgesEmpty('fa-project-diagram', (filter != '') ? '{{Aucun bridge ne correspond au filtre}}'
                : '{{Aucun bridge ne trouve d\'élément dans vos plugins. Utilisez « Créer un bridge » pour en ajouter un.}}')
            + '</td></tr>';
    }
    $('#scanip_bridges_table tbody').html(rows);
    $('#scanip_bridges_stats').html('<span class="scanip-stat"><span class="scanip-dot is-on"></span><b>' + nbShownEnabled + '</b> / ' + nbShown + ' {{actif(s)}}</span>');
}

function scanIpBridgesFindRule(id) {
    var found = null;
    $.each(scanIpBridges.rules, function (i, rule) {
        if (rule.id == id) { found = rule; }
    });
    return found;
}

$('#scanip_bridges_filter').off('keyup').on('keyup', scanIpBridgesRenderList);

$('#scanip_bridges_table').off('change', '.scanip-bridge-toggle').on('change', '.scanip-bridge-toggle', function () {
    var $input = $(this);
    var enabled = $input.is(':checked') ? 1 : 0;
    scanIpBridgesAjax('bridgesToggle', {id: $input.data('id'), enabled: enabled}, function () {
        var rule = scanIpBridgesFindRule($input.data('id'));
        if (rule) { rule.enabled = (enabled == 1); }
        scanIpBridgesRenderList();
    });
});

$('#scanip_bridges_table').off('click', '.scanip-bridge-edit').on('click', '.scanip-bridge-edit', function () {
    scanIpBridgesOpenForm(scanIpBridgesFindRule($(this).data('id')), false);
});

$('#scanip_bridges_table').off('click', '.scanip-bridge-test').on('click', '.scanip-bridge-test', function () {
    // Ouvre le bridge (en lecture seule pour un bridge PHP) et lance directement la simulation
    scanIpBridgesOpenForm(scanIpBridgesFindRule($(this).data('id')), false);
    scanIpBridgesSimulate();
});

$('#scanip_bridges_table').off('click', '.scanip-bridge-delete').on('click', '.scanip-bridge-delete', function () {
    var rule = scanIpBridgesFindRule($(this).data('id'));
    var message = (rule.origin == 'user')
        ? "{{Supprimer ce bridge ? Les associations qui l'utilisent ne seront plus mises à jour.}}"
        : "{{Revenir à la version du catalogue ? Vos modifications de ce bridge seront perdues.}}";
    bootbox.confirm(message, function (result) {
        if (result) {
            scanIpBridgesAjax('bridgesDelete', {id: rule.id}, scanIpBridgesLoad);
        }
    });
});

/* ---------- Découverte ---------- */

// Recherche des champs disponibles : lancée à l'ouverture de la fenêtre (étape 1 de la création d'un bridge)
function scanIpBridgesDiscover() {
    var $bt = $('#bt_scanipBridgesDiscover');
    $bt.find('i, svg').first().attr('class', 'fas fa-sync-alt fa-spin');
    if (scanIpBridges.fields.length == 0) {
        $('#scanip_bridges_discover_table').closest('.scanip-table-wrap').hide();
        $('#scanip_bridges_discover_help').hide();
        $('#scanip_bridges_discover_empty').show().html(scanIpBridgesEmpty('fa-spinner fa-spin', '{{Recherche des ip dans vos plugins…}}'));
    }
    scanIpBridgesAjax('bridgesDiscover', {}, function (fields) {
        $bt.find('i, svg').first().attr('class', 'fas fa-sync-alt');
        scanIpBridges.fields = fields;
        scanIpBridgesRenderDiscover();
    }, $bt);
}

$('#bt_scanipBridgesDiscover').off('click').on('click', scanIpBridgesDiscover);

function scanIpBridgesRenderDiscover() {
    var rows = '';
    var nbCovered = 0;
    $.each(scanIpBridges.fields, function (i, field) {
        // Les champs déjà gérés sont dans la liste des bridges, inutile de les répéter (quelle que soit la vue)
        if (field.rules.length > 0) {
            nbCovered++;
            return;
        }
        // Grille nom | valeur | appareil Scan.Ip : les colonnes restent alignées d'une ligne à l'autre
        var samples = '<div class="scanip-bridge-samples">' + $.map(field.samples.slice(0, 3), function (s) {
            return '<div class="scanip-bridge-sample">'
                + '<span class="scanip-bridge-sample-name" title="' + scanIpBridgesEscape(s.name) + '">' + scanIpBridgesEscape(s.name) + '</span>'
                + '<span class="scanip-bridge-value" title="' + scanIpBridgesEscape(s.raw) + '">' + scanIpBridgesHighlight(s.raw, s.ip, 'scanip-bridge-ip') + '</span>'
                + '<span class="scanip-bridge-sample-known">' + (s.known ? '<span class="scanip-badge is-on" title="{{Ip actuelle de cet équipement Scan.Ip}}"><i class="fas fa-link"></i> ' + scanIpBridgesEscape(s.known) + '</span>' : '') + '</span>'
                + '</div>';
        }).join('') + (field.count > 3 ? '<div class="scanip-bridge-samples-more">+ ' + (field.count - 3) + ' {{autre(s)}}</div>' : '') + '</div>';
        rows += '<tr>'
            + '<td>' + scanIpBridgesPluginCell(field.plugin, field.pluginName, '<span class="scanip-adv">' + scanIpBridgesEscape(scanIpBridges.sources[field.source]) + '</span>') + '</td>'
            + '<td><span class="scanip-chip">' + scanIpBridgesEscape(field.key) + '</span></td>'
            + '<td class="scanip-center"><span class="scanip-count">' + field.count + '</span></td>'
            + '<td>' + samples + '</td>'
            + '<td class="scanip-actions"><div class="scanip-actions-group"><a class="btn btn-success btn-xs scanip-bridge-create" data-index="' + i + '" title="{{Vérifier, tester et associer avant de créer le bridge}}"><i class="fas fa-magic"></i> {{Créer le bridge}}</a>'
            + '<a class="btn btn-default btn-xs scanip-bridge-customize scanip-adv" data-index="' + i + '" title="{{Ouvrir le formulaire pré-rempli avant de créer le bridge}}"><i class="fas fa-pen"></i></a></div></td>'
            + '</tr>';
    });
    var empty = (rows == '');
    $('#scanip_bridges_discover_table').closest('.scanip-table-wrap').toggle(!empty);
    $('#scanip_bridges_discover_help').toggle(!empty);
    $('#scanip_bridges_discover_empty').toggle(empty).html(
        (scanIpBridges.fields.length == 0)
            ? scanIpBridgesEmpty('fa-plug', "{{Aucun plugin trouvé ne permet de créer un bridge : aucune ip n'a été trouvée dans la configuration de vos plugins.}}")
            : scanIpBridgesEmpty('fa-check-circle', "{{Aucun plugin trouvé ne permet de créer un nouveau bridge : toutes les ip trouvées dans vos plugins sont déjà gérées par un bridge.}}")
    );
    $('#scanip_bridges_discover_covered').html(nbCovered > 0 && !empty
        ? '<i class="fas fa-info-circle"></i> ' + nbCovered + ' {{champ(s) déjà géré(s) par un bridge ne sont pas répétés ici.}}'
        : '');
    $('#scanip_bridges_discover_table tbody').html(rows);
    $('#scanip_bridges_discover').show();
}

function scanIpBridgesRuleFromField(field) {
    return {
        id: '',
        plugin: field.plugin,
        source: field.source,
        key: field.key,
        replace: field.replace,
        conditions: [],
        enabled: true,
        daemon: !!field.daemon
    };
}

// Créer le bridge : toujours par l'assistant (vérifications, test, association)
$('#scanip_bridges_discover_table').off('click', '.scanip-bridge-create').on('click', '.scanip-bridge-create', function () {
    scanIpWizardOpen(scanIpBridges.fields[$(this).data('index')]);
});

$('#scanip_bridges_discover_table').off('click', '.scanip-bridge-customize').on('click', '.scanip-bridge-customize', function () {
    scanIpBridgesOpenForm(scanIpBridgesRuleFromField(scanIpBridges.fields[$(this).data('index')]), true);
});


/* ---------- Formulaire ---------- */

$('#bt_scanipBridgesNew').off('click').on('click', function () {
    scanIpBridgesOpenForm(null, true, true);
});

$('#bt_scanipBridgesBack').off('click').on('click', function () {
    $('#scanip_bridges_form_view').hide();
    $('#scanip_bridges_list_view').show();
});

/* ---------- Proposition au catalogue ---------- */

function scanIpBridgesCopy(text, done) {
    var fallback = function () {
        var $area = $('<textarea style="position:fixed;top:-1000px;opacity:0;"></textarea>').val(text).appendTo('body');
        $area[0].select();
        var ok = false;
        try { ok = document.execCommand('copy'); } catch (e) {}
        $area.remove();
        done(ok);
    };
    // L'API du presse-papiers n'est disponible qu'en https : copie classique sinon
    if (navigator.clipboard && window.isSecureContext) {
        navigator.clipboard.writeText(text).then(function () { done(true); }, fallback);
    } else {
        fallback();
    }
}

$('#scanip_bridges_table').off('click', '.scanip-bridge-propose').on('click', '.scanip-bridge-propose', function () {
    scanIpBridgesAjax('bridgesProposal', {id: $(this).data('id')}, function (proposal) {
        var message = '<p>' + (proposal.isUpdate
                ? "{{Vous proposez une modification d'un bridge du catalogue.}}"
                : "{{Vous proposez un nouveau bridge pour le catalogue.}}")
            + ' {{Seule la règle ci-dessous est transmise : aucune ip, valeur ni nom d\'équipement de votre installation. Vérifiez que les valeurs des conditions ne contiennent rien de personnel.}}</p>'
            + '<pre class="scanip-bridge-proposal">' + scanIpBridgesEscape(proposal.json) + '</pre>'
            + '<p class="scanip-meta">{{GitHub : une issue pré-remplie s\'ouvre (compte GitHub nécessaire). Forum : la proposition est copiée dans le presse-papiers et le sujet du plugin s\'ouvre sur community.jeedom.com ; cliquez sur « Répondre » puis collez-la.}}</p>';
        bootbox.dialog({
            title: "{{Proposer au catalogue}}",
            message: message,
            buttons: {
                close: {label: "{{Fermer}}", className: 'btn-default'},
                forum: {
                    label: '<i class="fas fa-comments"></i> {{Forum Jeedom}}',
                    className: 'btn-default',
                    callback: function () {
                        // Réponse au sujet du plugin : titre en gras puis la proposition
                        scanIpBridgesCopy('**' + proposal.title + "**\n\n" + proposal.body, function (ok) {
                            if (ok) {
                                scanIpBridgesAlert("{{Proposition copiée : dans le sujet du forum, cliquez sur « Répondre » puis collez-la.}}", 'success');
                            } else {
                                scanIpBridgesAlert("{{La copie automatique a échoué : copiez la règle affichée dans la fenêtre « Proposer au catalogue » et collez-la dans une réponse au sujet du forum.}}", 'warning');
                            }
                        });
                        window.open(proposal.forum, '_blank', 'noopener');
                    }
                },
                github: {
                    label: '<i class="fab fa-github"></i> GitHub',
                    className: 'btn-primary',
                    callback: function () {
                        window.open(proposal.github, '_blank', 'noopener');
                    }
                }
            }
        });
    }, $(this));
});

/* ---------- Assistant de création ---------- */

function scanIpWizardStep(step) {
    $('#scanip_bridges_wizard_view .scanip-wizard-step').hide().filter('[data-step="' + step + '"]').show();
    $('#scanip_bridges_wizard_view .scanip-stepper span').each(function () {
        var n = parseInt($(this).data('step'), 10);
        $(this).toggleClass('is-active', n == step).toggleClass('is-done', n < step);
    });
}

function scanIpWizardOpen(field) {
    scanIpBridges.wizard = {};
    $('#scanip_bridges_list_view, #scanip_bridges_catalog_view, #scanip_bridges_form_view').hide();
    $('#scanip_bridges_wizard_view').show();
    scanIpWizardAnalyze(field);
}

// Retour à la liste (étape 1 : champs disponibles), recherche actualisée
function scanIpWizardClose() {
    $('#scanip_bridges_wizard_view').hide();
    $('#scanip_bridges_list_view').show();
    scanIpBridgesDiscover();
}

$('#bt_scanipWizardPrev, #bt_scanipWizardAnother').off('click').on('click', scanIpWizardClose);
$('#scanip_bridges_wizard_view .scanip-bridges-back').off('click').on('click', scanIpWizardClose);

// Étape 2 : vérifications
function scanIpWizardAnalyze(field) {
    scanIpWizardStep(2);
    $('#scanip_wizard_path').html('');
    $('#scanip_wizard_checks').html(scanIpBridgesEmpty('fa-spinner fa-spin', '{{Vérifications en cours…}}'));
    $('#scanip_wizard_elements tbody').empty();
    scanIpBridgesAjax('bridgesAnalyze', {plugin: field.plugin, source: field.source, key: field.key}, function (analysis) {
        scanIpBridges.wizard = analysis;
        var rule = analysis.rule;
        $('#scanip_wizard_path').html('<div class="scanip-bridge-path">' + scanIpBridgesPluginIcon(rule.plugin)
            + '<span class="scanip-bridge-path-plugin">' + scanIpBridgesEscape(analysis.pluginName) + '</span>'
            + '<i class="fas fa-chevron-right"></i><span>' + scanIpBridgesEscape($('#scanip_bridge_source option[value="' + rule.source + '"]').text()) + '</span>'
            + '<i class="fas fa-chevron-right"></i><span class="scanip-bridge-path-key">' + scanIpBridgesEscape(rule.source == 'logicalId' ? 'logicalId' : rule.key) + '</span></div>');

        var icons = {ok: 'fa-check-circle', info: 'fa-info-circle', warn: 'fa-exclamation-triangle', error: 'fa-times-circle'};
        $('#scanip_wizard_checks').html($.map(analysis.checks, function (c) {
            return '<div class="scanip-wizard-check is-' + c.level + '"><i class="fas ' + icons[c.level] + '"></i><div><b>' + scanIpBridgesEscape(c.title) + '</b>'
                + '<div class="scanip-meta">' + scanIpBridgesEscape(c.text) + '</div></div></div>';
        }).join(''));

        var tests = {updated: "{{Écriture à blanc : modification inattendue}}", noip: "{{Écriture à blanc : ip introuvable}}", notfound: "{{Écriture à blanc : élément introuvable}}", error: "{{Écriture à blanc : erreur}}"};
        var rows = '';
        // Liste des équipements Scan.Ip (appareils) proposés pour l'association
        var deviceOptions = function (selected) {
            var html = '<option value="">{{Ne pas associer maintenant}}</option>';
            $.each(analysis.scanIps || [], function (j, d) {
                html += '<option value="' + d.id + '"' + (d.id == selected ? ' selected' : '') + '>'
                    + scanIpBridgesEscape(d.name) + (d.ip ? ' · ' + scanIpBridgesEscape(d.ip) : '') + (d.online == 1 ? '' : ' ({{hors ligne}})') + '</option>';
            });
            return html;
        };
        $.each(analysis.elements, function (i, el) {
            var device;
            if (el.associated.length > 0) {
                device = '<span class="scanip-badge is-on"><i class="fas fa-link"></i> {{Déjà associé à}} ' + scanIpBridgesEscape(el.associated.join(', ')) + '</span>';
            } else if (!analysis.scanIps || analysis.scanIps.length == 0) {
                // Aucun appareil lié à ce plugin : rien à proposer ici
                device = '<span class="scanip-meta">{{Aucun appareil Scan.Ip lié à ce plugin pour l\'instant : associez plus tard depuis la fiche de l\'équipement Scan.Ip de l\'appareil.}}</span>';
                if (el.network) {
                    device += '<div class="scanip-meta"><i class="fas fa-info-circle"></i> {{Vu sur le réseau sans équipement Scan.Ip}} · <span class="scanip-mono">' + scanIpBridgesEscape(el.network.mac) + '</span>'
                        + (el.network.vendor ? ' · ' + scanIpBridgesEscape(el.network.vendor) : '') + '</div>';
                }
            } else {
                device = '<select class="form-control input-sm scanip-wizard-assoc" data-index="' + i + '">' + deviceOptions(el.match ? el.match.id : '') + '</select>';
                if (el.match) {
                    device += '<div class="scanip-meta"><i class="fas fa-check scanip-icon-on"></i> {{Même ip : appareil reconnu}}</div>';
                } else if (el.network) {
                    device += '<div class="scanip-meta"><i class="fas fa-info-circle"></i> {{Vu sur le réseau sans équipement Scan.Ip}} · <span class="scanip-mono">' + scanIpBridgesEscape(el.network.mac) + '</span>'
                        + (el.network.vendor ? ' · ' + scanIpBridgesEscape(el.network.vendor) : '') + '</div>';
                } else {
                    device += '<div class="scanip-meta"><i class="fas fa-info-circle"></i> {{Aucun appareil avec cette ip au dernier scan (éteint, ou ip déjà changée)}}</div>';
                }
            }
            if (el.test && tests[el.test]) {
                device += '<div><span class="scanip-badge is-off">' + tests[el.test] + '</span></div>';
            }
            rows += '<tr>'
                + '<td><div class="scanip-plugin-name">' + scanIpBridgesEscape(el.name) + '</div><div class="scanip-meta">' + scanIpBridgesEscape(el.detail) + '</div></td>'
                + '<td><span class="scanip-bridge-value">' + scanIpBridgesHighlight(el.raw, el.ip, 'scanip-bridge-ip') + '</span></td>'
                + '<td>' + device + '</td>'
                + '<td class="scanip-wizard-test-cell">'
                + '<div class="scanip-wizard-test"><input type="text" class="form-control input-sm scanip-wizard-test-ip" data-index="' + i + '" value="' + scanIpBridgesEscape(scanIpWizardTestIp(el, el.match ? el.match.ip : '')) + '">'
                + '<a class="btn btn-default btn-sm scanip-wizard-test-run" data-index="' + i + '" title="{{Tester}}"><i class="fas fa-play"></i></a></div>'
                + '</td>'
                + '</tr>'
                // Résultat du test : ligne dédiée sous l'élément, sur toute la largeur
                + '<tr class="scanip-wizard-test-row" data-index="' + i + '" style="display:none;"><td colspan="4"><div class="scanip-test-slot"></div></td></tr>';
        });
        if (rows == '') {
            rows = '<tr><td colspan="4">' + scanIpBridgesEmpty('fa-inbox', '{{Aucun élément disponible pour ce plugin : impossible de tester, le bridge ne peut pas être créé.}}') + '</td></tr>';
        }
        $('#scanip_wizard_elements tbody').html(rows);

        $('#bt_scanipWizardCreate').toggleClass('disabled', !!analysis.blocking)
            .attr('title', analysis.blocking ? "{{Création impossible : voir les vérifications}}" : '');
    });
}

$('#bt_scanipWizardCreate').off('click').on('click', function () {
    var analysis = scanIpBridges.wizard;
    if (!analysis || !analysis.rule || analysis.blocking) {
        return;
    }
    var rule = $.extend({}, analysis.rule);
    var associations = [];
    $('#scanip_wizard_elements .scanip-wizard-assoc').each(function () {
        var scanIp = $(this).val();
        if (scanIp) {
            var el = analysis.elements[$(this).data('index')];
            associations.push({value: el.value, scanIp: scanIp, name: el.name});
        }
    });
    scanIpBridgesAjax('bridgesWizardCreate', {rule: JSON.stringify(rule), associations: JSON.stringify(associations)}, function (result) {
        scanIpBridges.wizard.createdId = result.id;
        var text = '<b>{{Bridge créé}}</b><br>'
            + (result.added > 0 ? result.added + ' {{association(s) ajoutée(s) : l\'ip de ces éléments suivra désormais celle de leur appareil.}}'
                : '{{Aucune association pour l\'instant : c\'est normal. Quand vous le souhaitez, ouvrez la fiche de l\'équipement Scan.Ip de l\'appareil et choisissez l\'élément dans une des listes « Association » : il recevra ensuite l\'ip de cet appareil à chaque scan.}}');
        if (result.failed.length > 0) {
            text += '<div class="scanip-text-warn" style="margin-top:6px;">{{Non associé (plus d\'emplacement libre sur l\'équipement Scan.Ip)}} : ' + scanIpBridgesEscape(result.failed.join(', ')) + '</div>';
        }
        $('#scanip_wizard_done').html('<div class="scanip-empty scanip-wizard-success"><i class="fas fa-check-circle"></i>' + text + '</div>');
        scanIpWizardStep(3);
        scanIpBridgesLoad();
    }, $(this));
});

$('#bt_scanipWizardTest').off('click').on('click', function () {
    var id = scanIpBridges.wizard ? scanIpBridges.wizard.createdId : null;
    scanIpBridgesAjax('bridgesList', {}, function (result) {
        scanIpBridges.rules = result.rules;
        scanIpBridges.plugins = result.plugins;
        scanIpBridges.daemons = result.daemons || [];
        scanIpBridgesRenderList();
        var rule = scanIpBridgesFindRule(id);
        if (rule) {
            $('#scanip_bridges_wizard_view').hide();
            scanIpBridgesOpenForm(rule, false);
            scanIpBridgesSimulate();
        }
    });
});

// Ip proposée pour le test : celle de l'appareil choisi, sinon une ip voisine de l'actuelle (dernier octet + 1)
function scanIpWizardTestIp(el, deviceIp) {
    if (deviceIp) {
        return deviceIp;
    }
    if (!el.ip) {
        return '';
    }
    var parts = el.ip.split('.');
    var last = parseInt(parts[3], 10);
    parts[3] = (last >= 254) ? last - 1 : last + 1;
    return parts.join('.');
}

// Test d'un élément : valeur que le bridge écrirait (simulation, rien n'est modifié)
// Résultat d'un test, identique dans l'assistant et le formulaire :
//   ✓ Test avec <ip> · <statut>
//     Champ   <champ> (commande #n)
//     Valeur  <nouvelle valeur>
function scanIpBridgesTestResultHtml(row, ip) {
    var field = '';
    if (row && row.field) {
        var name = String(row.field), where = '';
        var cmd = name.match(/^cmd(\d+):(.*)$/);
        if (cmd) {
            name = cmd[2];
            where = ' <span class="scanip-meta">({{commande}} #' + cmd[1] + ')</span>';
        } else if (name == '@logicalId') {
            name = 'logicalId';
        }
        field = '<span class="scanip-chip">' + scanIpBridgesEscape(name) + '</span>' + where;
    }
    var block = function (cls, icon, text, value, extra) {
        var lines = '';
        if (field) {
            lines += '<dt>{{Champ}}</dt><dd>' + field + '</dd>';
        }
        if (value) {
            lines += '<dt>{{Valeur}}</dt><dd><span class="scanip-bridge-value">' + value + '</span></dd>';
        }
        if (extra) {
            lines += '<dt>{{Démon}}</dt><dd>' + extra + '</dd>';
        }
        return '<div class="scanip-test-result">'
            + '<div class="scanip-test-result-head"><i class="fas ' + icon + ' ' + cls + '"></i> {{Test avec}} <span class="scanip-mono">' + scanIpBridgesEscape(ip || '…') + '</span> · ' + text + '</div>'
            + (lines ? '<dl class="scanip-test-result-lines">' + lines + '</dl>' : '')
            + '</div>';
    };
    if (!row) {
        return block('scanip-text-off', 'fa-times-circle', '{{élément introuvable}}', '', '');
    }
    switch (row.status) {
        case 'updated':
            return block('scanip-icon-on', 'fa-check-circle', '{{la valeur serait mise à jour}}', scanIpBridgesHighlight(row.new, ip, 'scanip-bridge-ip'),
                row.daemon ? '<i class="fas fa-redo"></i> {{redémarrage de}} ' + scanIpBridgesEscape(row.daemon) : '');
        case 'uptodate':
            return block('scanip-meta', 'fa-equals', '{{déjà cette ip : aucune modification}}', '', '');
        case 'unassociated':
            return block('scanip-meta', 'fa-unlink', '{{non associé à une adresse MAC : non mis à jour}}', '', '');
        case 'noip':
            return block('scanip-text-off', 'fa-times-circle', '{{ip introuvable}}', '', '');
        default:
            return block('scanip-text-off', 'fa-times-circle', scanIpBridgesEscape(row.message || "{{échec}}"), '', '');
    }
}

function scanIpWizardRunTest(index) {
    var analysis = scanIpBridges.wizard;
    var el = analysis.elements[index];
    var ip = $.trim($('#scanip_wizard_elements .scanip-wizard-test-ip[data-index="' + index + '"]').val());
    var $row = $('#scanip_wizard_elements .scanip-wizard-test-row[data-index="' + index + '"]');
    var $result = $row.find('.scanip-test-slot');
    $row.show();
    if (ip == '') {
        $result.html('<div class="scanip-test-result"><div class="scanip-test-result-head scanip-meta">{{Indiquez une ip à tester}}</div></div>');
        return;
    }
    var rule = $.extend({}, analysis.rule);
    $result.html('<div class="scanip-test-result"><div class="scanip-test-result-head"><i class="fas fa-spinner fa-spin scanip-meta"></i> {{Test en cours…}}</div></div>');
    scanIpBridgesAjax('bridgesSimulate', {rule: JSON.stringify(rule), testIp: ip, testValue: el.value}, function (result) {
        $result.html(scanIpBridgesTestResultHtml(result.rows[0], ip));
    });
}

$('#scanip_wizard_elements').off('click', '.scanip-wizard-test-run').on('click', '.scanip-wizard-test-run', function () {
    scanIpWizardRunTest($(this).data('index'));
});
$('#scanip_wizard_elements').off('keyup', '.scanip-wizard-test-ip').on('keyup', '.scanip-wizard-test-ip', function (e) {
    if (e.which == 13) {
        scanIpWizardRunTest($(this).data('index'));
    }
});
// Appareil choisi : son ip devient l'ip de test
$('#scanip_wizard_elements').off('change', '.scanip-wizard-assoc').on('change', '.scanip-wizard-assoc', function () {
    var index = $(this).data('index');
    var device = null, id = $(this).val();
    $.each(scanIpBridges.wizard.scanIps || [], function (j, d) {
        if (d.id == id) { device = d; }
    });
    var el = scanIpBridges.wizard.elements[index];
    $('#scanip_wizard_elements .scanip-wizard-test-ip[data-index="' + index + '"]').val(scanIpWizardTestIp(el, device ? device.ip : ''));
    $('#scanip_wizard_elements .scanip-wizard-test-row[data-index="' + index + '"]').hide().find('.scanip-test-slot').empty();
});


/* ---------- Catalogue (lecture seule) ---------- */

scanIpBridges.replaces = {
    auto: "{{Automatique}}",
    full: "{{Tout le champ}}",
    ip: "{{Seulement l'ip}}"
};

$('#bt_scanipBridgesCatalog').off('click').on('click', function () {
    scanIpBridgesAjax('bridgesCatalog', {}, function (catalog) {
        scanIpBridges.catalog = catalog;
        scanIpBridgesRenderCatalog();
        $('#scanip_bridges_list_view').hide();
        $('#scanip_bridges_catalog_view').show();
    }, $(this));
});

$('#scanip_bridges_catalog_view .scanip-bridges-back').off('click').on('click', function () {
    $('#scanip_bridges_catalog_view').hide();
    $('#scanip_bridges_list_view').show();
});

$('#scanip_bridges_catalog_filter').off('keyup').on('keyup', scanIpBridgesRenderCatalog);
$('#scanip_bridges_catalog_installed').off('change').on('change', scanIpBridgesRenderCatalog);

// Configuration d'une règle du catalogue, sous forme de liste « libellé : valeur »
function scanIpBridgesCatalogRuleHtml(rule) {
    var lines = [];
    var line = function (label, value) {
        lines.push('<dt>' + label + '</dt><dd>' + value + '</dd>');
    };
    if (rule.source == 'php') {
        line("{{Type}}", "{{Bridge écrit en PHP (cas particulier)}}");
        if (rule.description) { line("{{Description}}", scanIpBridgesEscape(rule.description)); }
    } else {
        line("{{Emplacement}}", scanIpBridgesEscape($('#scanip_bridge_source option[value="' + rule.source + '"]').text() || rule.source));
        if (rule.source != 'logicalId') {
            line("{{Champ}}", '<code>' + scanIpBridgesEscape(rule.key) + '</code>');
        }
        if (rule.repeat && rule.key.indexOf('{n}') != -1) {
            line("{{Numéros}} {n}", "{{de}} " + rule.repeat.from + " {{à}} "
                + (rule.repeat.toKey ? "{{la valeur de}} <code>" + scanIpBridgesEscape(rule.repeat.toKey) + '</code>' : (rule.repeat.to || '…')));
        }
        line("{{Mise à jour}}", scanIpBridgesEscape(scanIpBridges.replaces[rule.replace] || rule.replace));
        if (rule.conditions && rule.conditions.length > 0) {
            line("{{Conditions}}", scanIpBridgesConditionsText(rule));
        }
    }
    if (rule.daemon) { line("{{Démon}}", "{{Redémarré après une mise à jour}}"); }
    if (rule.detailKey) { line("{{Précision affichée}}", '<code>' + scanIpBridgesEscape(rule.detailKey) + '</code>'); }
    if (rule.detail) { line("{{Précision affichée}}", scanIpBridgesEscape(rule.detail)); }
    if (rule.name) { line("{{Nom de l'élément}}", scanIpBridgesEscape(rule.name)); }
    if (rule.nameKey) { line("{{Nom de l'élément}}", '<code>' + scanIpBridgesEscape(rule.nameKey) + '</code>'); }
    return '<dl class="scanip-catalog-rule">' + lines.join('') + '</dl>';
}

function scanIpBridgesRenderCatalog() {
    var filter = $('#scanip_bridges_catalog_filter').val().toLowerCase();
    var onlyInstalled = $('#scanip_bridges_catalog_installed').is(':checked');
    var open = $('#scanip_bridges_catalog_list .scanip-catalog-item.is-open').data('plugin');

    // Regroupement par plugin
    var plugins = [], byPlugin = {}, nbInstalled = 0;
    $.each(scanIpBridges.catalog || [], function (i, rule) {
        if (!byPlugin[rule.plugin]) {
            byPlugin[rule.plugin] = {plugin: rule.plugin, name: rule.pluginName, installed: rule.installed, rules: []};
            plugins.push(byPlugin[rule.plugin]);
            if (rule.installed) { nbInstalled++; }
        }
        byPlugin[rule.plugin].rules.push(rule);
    });
    plugins.sort(function (a, b) { return a.name.localeCompare(b.name); });

    var html = '';
    $.each(plugins, function (i, item) {
        if (onlyInstalled && !item.installed) {
            return;
        }
        var haystack = (item.plugin + ' ' + item.name + ' ' + $.map(item.rules, function (r) { return r.key + ' ' + r.description; }).join(' ')).toLowerCase();
        if (filter != '' && haystack.indexOf(filter) == -1) {
            return;
        }
        var chips = $.map(item.rules, function (r) {
            return '<span class="scanip-chip">' + scanIpBridgesEscape(r.source == 'php' ? 'PHP' : (r.source == 'logicalId' ? 'logicalId' : r.key)) + '</span>';
        }).join('');
        html += '<div class="scanip-catalog-item' + (item.plugin == open ? ' is-open' : '') + '" data-plugin="' + scanIpBridgesEscape(item.plugin) + '">'
            + '<div class="scanip-catalog-head">'
            + scanIpBridgesPluginIcon(item.plugin)
            + '<span class="scanip-catalog-name">' + scanIpBridgesEscape(item.name) + '</span>'
            + (item.installed ? '<span class="scanip-badge is-on"><i class="fas fa-check"></i> {{installé}}</span>' : '')
            + '<span class="scanip-spacer"></span>'
            + '<span class="scanip-catalog-chips">' + chips + '</span>'
            + '<i class="fas fa-chevron-down scanip-catalog-chevron"></i>'
            + '</div>'
            + '<div class="scanip-catalog-body">' + $.map(item.rules, scanIpBridgesCatalogRuleHtml).join('') + '</div>'
            + '</div>';
    });
    if (html == '') {
        html = scanIpBridgesEmpty('fa-book', '{{Aucun bridge ne correspond au filtre}}');
    }
    $('#scanip_bridges_catalog_list').html(html);
    $('#scanip_bridges_catalog_stats').html(
        '<span class="scanip-stat"><b>' + plugins.length + '</b> {{plugins pris en charge}}</span>'
        + '<span class="scanip-stat"><b>' + nbInstalled + '</b> {{installés}}</span>'
    );
}

// Un seul plugin déplié à la fois
$('#scanip_bridges_catalog_list').off('click', '.scanip-catalog-head').on('click', '.scanip-catalog-head', function () {
    var $item = $(this).closest('.scanip-catalog-item');
    var wasOpen = $item.hasClass('is-open');
    $('#scanip_bridges_catalog_list .scanip-catalog-item').removeClass('is-open');
    $item.toggleClass('is-open', !wasOpen);
});

function scanIpBridgesConditionRow(condition) {
    condition = condition || {key: '', op: '==', value: ''};
    var options = '';
    $.each(scanIpBridges.operators, function (op, label) {
        options += '<option value="' + op + '"' + (op == condition.op ? ' selected' : '') + '>' + label + '</option>';
    });
    var $row = $('<div class="scanip-bridge-condition">'
        + '<input type="text" class="form-control input-sm scanip-cond-key" list="scanip_bridge_condition_keys" autocomplete="off" placeholder="{{champ}}">'
        + '<select class="form-control input-sm scanip-cond-op">' + options + '</select>'
        + '<input type="text" class="form-control input-sm scanip-cond-value" placeholder="{{valeur (séparées par des virgules pour une liste)}}">'
        + '<a class="btn btn-default btn-sm scanip-cond-remove" title="{{Supprimer}}"><i class="fas fa-minus-circle"></i></a>'
        + '</div>');
    $row.find('.scanip-cond-key').val(condition.key);
    $row.find('.scanip-cond-value').val(condition.value);
    scanIpBridgesConditionVisibility($row);
    return $row;
}

function scanIpBridgesConditionVisibility($row) {
    var op = $row.find('.scanip-cond-op').val();
    $row.find('.scanip-cond-value').toggle(op != 'empty' && op != 'notempty');
}

$('#scanip_bridge_conditions').off('change', '.scanip-cond-op').on('change', '.scanip-cond-op', function () {
    scanIpBridgesConditionVisibility($(this).closest('.scanip-bridge-condition'));
});
$('#scanip_bridge_conditions').off('click', '.scanip-cond-remove').on('click', '.scanip-cond-remove', function () {
    $(this).closest('.scanip-bridge-condition').remove();
});
$('#bt_scanipBridgeAddCondition').off('click').on('click', function () {
    $('#scanip_bridge_conditions').append(scanIpBridgesConditionRow());
});

// choosePlugin : le plugin n'est modifiable que pour un bridge créé de zéro (« Nouveau bridge »)
function scanIpBridgesOpenForm(rule, isNew, choosePlugin) {
    rule = rule || {id: '', plugin: '', source: 'eqLogic', key: '', replace: 'auto', conditions: [], enabled: true, daemon: false};
    var isPhp = (rule.source == 'php');
    scanIpBridges.isNewRule = !!choosePlugin;

    var options = '';
    var plugins = $.extend({}, scanIpBridges.plugins);
    if (rule.plugin && !plugins[rule.plugin]) {
        plugins[rule.plugin] = rule.plugin + ' ({{non installé}})';
    }
    var ids = Object.keys(plugins).sort(function (a, b) { return plugins[a].localeCompare(plugins[b]); });
    $.each(ids, function (i, id) {
        options += '<option value="' + scanIpBridgesEscape(id) + '">' + scanIpBridgesEscape(plugins[id]) + '</option>';
    });
    $('#scanip_bridge_plugin').html(options).val(rule.plugin || ids[0]);

    $('#scanip_bridge_id').val(isNew ? '' : rule.id);
    $('#scanip_bridge_source').val(isPhp ? 'eqLogic' : rule.source);
    $('#scanip_bridge_key').val(rule.key || '');
    $('#scanip_bridge_replace').val(rule.replace || 'auto');
    $('#scanip_bridge_detailKey').val(rule.detailKey || '');
    $('#scanip_bridge_name').val(rule.name || '');
    $('#scanip_bridge_daemon').prop('checked', !!rule.daemon);
    scanIpBridgesDaemonVisibility();
    $('#scanip_bridge_enabled').prop('checked', rule.enabled !== false);
    $('#scanip_bridge_repeat_from').val(rule.repeat ? rule.repeat.from : 1);
    $('#scanip_bridge_repeat_to').val(rule.repeat && rule.repeat.to ? rule.repeat.to : '');
    $('#scanip_bridge_repeat_toKey').val(rule.repeat ? rule.repeat.toKey : '');
    $('#scanip_bridge_test_ip').val('').hide();
    $('#scanip_bridge_test_element').html('<option value="">{{Les éléments associés, avec l\'ip de leur appareil}}</option>').val('');
    $('#scanip_bridge_conditions').empty();
    $.each(rule.conditions || [], function (i, condition) {
        $('#scanip_bridge_conditions').append(scanIpBridgesConditionRow(condition));
    });
    $('#scanip_bridge_simulation').hide();

    // Un bridge PHP ne se configure pas : seule la simulation est proposée
    scanIpBridges.current = isPhp ? rule : null;
    $('#scanip_bridges_form_view form .form-group').not(':has(#scanip_bridge_test_ip)').toggle(!isPhp);
    $('#scanip_bridges_form_view .scanip-section').not(':last').toggle(!isPhp);
    $('#bt_scanipBridgesSave').toggle(!isPhp);
    scanIpBridgesLockFields();

    // Vue normale : les conditions ne sont pas modifiables mais restent appliquées
    var nbConditions = (!isPhp && rule.conditions) ? rule.conditions.length : 0;
    $('#scanip_bridge_hidden_conditions').toggle(nbConditions > 0)
        .find('.scanip-meta').html(nbConditions > 0 ? scanIpBridgesConditionsText(rule) + '<br><i class="fas fa-info-circle"></i> {{Modifiables en vue avancée}}' : '');
    $('#scanip_bridge_form_title').text(isNew ? "{{Nouveau bridge}}" : rule.id + (rule.origin == 'catalog' ? ' ({{catalogue}})' : ''));

    if (!isPhp) {
        scanIpBridgesFormVisibility();
        scanIpBridgesLoadFields();
    }
    $('#scanip_bridges_list_view').hide();
    $('#scanip_bridges_form_view').show();
}

// Champ non modifiable : la liste est remplacée par son texte, pour ne pas laisser croire qu'on peut la changer
function scanIpBridgesLockField($select, $text, editable) {
    if ($select.is('select')) {
        var $option = $select.find('option:selected');
        $text.text($option.length ? $option.text() : '');
    } else {
        $text.text($select.val());
    }
    $select.toggle(editable);
    $text.toggle(!editable);
}

function scanIpBridgesLockFields() {
    // Vue avancée : le plugin n'est modifiable que pour un nouveau bridge
    // Vue normale : plugin, emplacement et champ sont résumés dans une carte non modifiable
    scanIpBridgesLockField($('#scanip_bridge_plugin'), $('#scanip_bridge_plugin_text'), scanIpBridges.isNewRule);
    scanIpBridgesRenderSummary();
}

// Valeurs actuelles du champ, ip mise en évidence
function scanIpBridgesValuesHtml(values) {
    if (!values || values.length == 0) {
        return '';
    }
    var shown = values.slice(0, 4);
    return '<div class="scanip-bridge-values">'
        + '<div class="scanip-meta">' + (values.length > 1 ? '{{Valeurs actuelles}}' : '{{Valeur actuelle}}') + '</div>'
        + $.map(shown, function (v) {
            var ip = String(v).match(/(?:\d{1,3}\.){3}\d{1,3}/);
            return '<div class="scanip-bridge-value">' + scanIpBridgesHighlight(v, ip ? ip[0] : '', 'scanip-bridge-ip') + '</div>';
        }).join('')
        + (values.length > shown.length ? '<div class="scanip-meta">{{et}} ' + (values.length - shown.length) + ' {{autre(s)}}</div>' : '')
        + '</div>';
}

function scanIpBridgesRenderSummary() {
    var source = $('#scanip_bridge_source').val();
    var key = (source == 'logicalId') ? 'logicalId' : $.trim($('#scanip_bridge_key').val());
    var values = (scanIpBridges.keyValues && scanIpBridges.keyValues[key]) ? scanIpBridges.keyValues[key] : [];
    $('#scanip_bridge_summary').html(
        '<div class="scanip-bridge-path">'
        + '<span class="scanip-bridge-path-plugin">' + scanIpBridgesEscape($('#scanip_bridge_plugin option:selected').text()) + '</span>'
        + '<i class="fas fa-chevron-right"></i>'
        + '<span>' + scanIpBridgesEscape($('#scanip_bridge_source option:selected').text()) + '</span>'
        + '<i class="fas fa-chevron-right"></i>'
        + '<span class="scanip-bridge-path-key">' + scanIpBridgesEscape(key) + '</span>'
        + '</div>'
        + scanIpBridgesValuesHtml(values)
    );
}

function scanIpBridgesFormVisibility() {
    var source = $('#scanip_bridge_source').val();
    var key = $('#scanip_bridge_key').val();
    $('#scanip_bridge_key_group').toggle(source != 'logicalId');
    $('#scanip_bridge_repeat_group').toggle(source == 'pluginConfig' && key.indexOf('{n}') != -1);
    $('#scanip_bridge_name_group').toggle(source == 'pluginConfig');
    scanIpBridgesKeyValues();
}

// Champs du plugin choisi pour l'autocomplétion
function scanIpBridgesLoadFields() {
    var plugin = $('#scanip_bridge_plugin').val();
    var source = $('#scanip_bridge_source').val();
    scanIpBridges.keyValues = {};
    $('#scanip_bridge_keys, #scanip_bridge_condition_keys').empty();
    if (source == 'logicalId') {
        source = 'eqLogic';
    }
    scanIpBridgesAjax('bridgesFields', {plugin: plugin, source: source}, function (fields) {
        var keys = '', all = '';
        $.each(scanIpBridges.specialKeys, function (i, key) {
            all += '<option value="' + key + '">';
        });
        $.each(fields, function (i, field) {
            scanIpBridges.keyValues[field.key] = field.values;
            var label = field.values.slice(0, 3).join(' | ');
            all += '<option value="' + scanIpBridgesEscape(field.key) + '">' + scanIpBridgesEscape(label) + '</option>';
            if (field.ip) {
                keys += '<option value="' + scanIpBridgesEscape(field.key) + '">' + scanIpBridgesEscape(label) + '</option>';
            }
        });
        $('#scanip_bridge_keys').html(keys);
        $('#scanip_bridge_condition_keys').html(all);
        scanIpBridgesKeyValues();
    });
}

function scanIpBridgesKeyValues() {
    var key = $('#scanip_bridge_key').val();
    var values = (scanIpBridges.keyValues && scanIpBridges.keyValues[key]) ? scanIpBridges.keyValues[key] : [];
    $('#scanip_bridge_key_values').html(scanIpBridgesValuesHtml(values));
    scanIpBridgesRenderSummary();
}

$('#scanip_bridge_plugin, #scanip_bridge_source').off('change').on('change', function () {
    scanIpBridgesDaemonVisibility();
    scanIpBridgesFormVisibility();
    scanIpBridgesLoadFields();
});
$('#scanip_bridge_key').off('input change').on('input change', scanIpBridgesFormVisibility);

function scanIpBridgesFormRule() {
    if (scanIpBridges.current) {
        return scanIpBridges.current;
    }
    var conditions = [];
    $('#scanip_bridge_conditions .scanip-bridge-condition').each(function () {
        var key = $.trim($(this).find('.scanip-cond-key').val());
        if (key != '') {
            conditions.push({key: key, op: $(this).find('.scanip-cond-op').val(), value: $(this).find('.scanip-cond-value').val()});
        }
    });
    var rule = {
        id: $('#scanip_bridge_id').val(),
        plugin: $('#scanip_bridge_plugin').val(),
        source: $('#scanip_bridge_source').val(),
        key: $.trim($('#scanip_bridge_key').val()),
        // Vue normale : type de mise à jour toujours détecté automatiquement ; choix manuel en vue avancée
        replace: scanIpBridgesIsAdvanced() ? $('#scanip_bridge_replace').val() : 'auto',
        conditions: conditions,
        detailKey: $.trim($('#scanip_bridge_detailKey').val()),
        daemon: $('#scanip_bridge_daemon').is(':checked'),
        enabled: $('#scanip_bridge_enabled').is(':checked')
    };
    if (rule.source == 'pluginConfig') {
        rule.name = $.trim($('#scanip_bridge_name').val());
        if (rule.key.indexOf('{n}') != -1) {
            rule.repeat = {
                from: parseInt($('#scanip_bridge_repeat_from').val() || '1', 10),
                to: parseInt($('#scanip_bridge_repeat_to').val() || '0', 10),
                toKey: $.trim($('#scanip_bridge_repeat_toKey').val())
            };
        }
    }
    // Conserve les réglages du catalogue que le formulaire n'affiche pas
    var original = rule.id ? scanIpBridgesFindRule(rule.id) : null;
    if (original) {
        rule.detail = original.detail;
        rule.nameKey = original.nameKey;
        rule.legacy = original.legacy;
        if (rule.source != 'pluginConfig') {
            rule.name = original.name;
        }
    }
    return rule;
}

$('#bt_scanipBridgesSave').off('click').on('click', function () {
    scanIpBridgesAjax('bridgesSave', {rule: JSON.stringify(scanIpBridgesFormRule())}, function (result) {
        scanIpBridgesAlert("{{Bridge enregistré}} : " + scanIpBridgesEscape(result.id), 'success');
        $('#scanip_bridges_form_view').hide();
        $('#scanip_bridges_list_view').show();
        scanIpBridgesLoad();
        scanIpBridgesDiscover();
    }, $(this));
});

/* ---------- Simulation ---------- */

$('#bt_scanipBridgesSimulate').off('click').on('click', scanIpBridgesSimulate);

// Un élément choisi : l'ip à appliquer devient saisissable
$('#scanip_bridge_test_element').off('change').on('change', function () {
    var chosen = $(this).val() != '';
    $('#scanip_bridge_test_ip').toggle(chosen);
    if (chosen) {
        $('#scanip_bridge_test_ip').trigger('focus');
    } else {
        $('#scanip_bridge_test_ip').val('');
    }
});

// Échappe la valeur et met en évidence l'ip qu'elle contient
function scanIpBridgesHighlight(value, ip, cssClass) {
    var html = scanIpBridgesEscape(value);
    if (!ip) {
        return html;
    }
    var pattern = new RegExp('(^|[^\\d.])(' + String(ip).replace(/\./g, '\\.') + ')(?!\\d)', 'g');
    return html.replace(pattern, '$1<b class="' + (cssClass || 'scanip-bridge-newip') + '">$2</b>');
}

function scanIpBridgesSimulate() {
    var testValue = $('#scanip_bridge_test_element').val() || '';
    var testIp = testValue ? $.trim($('#scanip_bridge_test_ip').val()) : '';
    scanIpBridgesAjax('bridgesSimulate', {rule: JSON.stringify(scanIpBridgesFormRule()), testIp: testIp, testValue: testValue}, function (result) {
        // Liste des éléments pour tester un élément précis (sélection conservée)
        var options = '<option value="">{{Les éléments associés, avec l\'ip de leur appareil}}</option>';
        $.each(result.elements, function (i, el) {
            options += '<option value="' + scanIpBridgesEscape(el.value) + '">' + scanIpBridgesEscape(el.label) + '</option>';
        });
        $('#scanip_bridge_test_element').html(options).val(result.testValue || '');

        var rows = '';
        $.each(result.rows, function (i, row) {
            var assoc = row.assoc
                ? scanIpBridgesEscape(row.assoc.name) + ' <span class="scanip-meta scanip-mono">' + scanIpBridgesEscape(row.assoc.mac) + '</span><br>'
                  + '<span class="scanip-dot ' + (row.assoc.online == 1 ? 'is-on' : 'is-off') + '"></span> <span class="scanip-mono">' + scanIpBridgesEscape(row.assoc.ip || '...') + '</span>'
                : '<span class="scanip-meta">—</span>';
            var ip = row.applied || (result.testValue ? result.testIp : '') || (row.assoc ? row.assoc.ip : '');
            rows += '<tr class="scanip-test-element">'
                + '<td>' + (row.url ? '<a href="' + scanIpBridgesEscape(row.url) + '" target="_blank">' + scanIpBridgesEscape(row.name) + '</a>' : scanIpBridgesEscape(row.name))
                + '<br><span class="scanip-meta">' + scanIpBridgesEscape(row.plugin_print) + '</span></td>'
                + '<td><span class="scanip-bridge-value">' + scanIpBridgesHighlight(row.raw, row.ip, 'scanip-bridge-ip') + '</span></td>'
                + '<td>' + assoc + '</td>'
                + '</tr>'
                + '<tr class="scanip-test-row"><td colspan="3">' + scanIpBridgesTestResultHtml(row, ip) + '</td></tr>';
        });
        if (rows == '') {
            var empty = (result.elements.length == 0)
                ? '{{Aucun élément ne correspond à ce bridge (vérifiez le champ et les conditions).}}'
                : result.elements.length + ' {{élément(s) trouvé(s), mais aucun n\'est associé à une adresse MAC : le prochain scan ne modifiera rien. Choisissez un élément dans la liste pour le tester avec une ip.}}';
            rows = '<tr><td colspan="3">' + scanIpBridgesEmpty('fa-unlink', empty) + '</td></tr>';
        } else if (!result.testValue && result.unassociated > 0) {
            rows += '<tr><td colspan="3" class="scanip-meta"><i class="fas fa-info-circle"></i> ' + result.unassociated + ' {{autre(s) élément(s) non associé(s) à une adresse MAC : non mis à jour.}}</td></tr>';
        }
        $('#scanip_bridge_simulation tbody').html(rows);
        $('#scanip_bridge_simulation').show();
    }, $('#bt_scanipBridgesSimulate'));
}

scanIpBridgesLoad();
scanIpBridgesDiscover();
