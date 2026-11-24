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

// Vue par défaut : celle de la configuration du plugin (normal, ou avancé pour les modes avancé et debug)
$bridgesDefaultView = (config::byKey('mode_plugin', 'scan_ip', 'normal') == 'normal') ? 'normal' : 'advanced';
// Market Jeedom : icônes des plugins non installés (catalogue)
$bridgesMarket = rtrim((string) config::byKey('market::address', 'core', 'https://market.jeedom.com'), '/');
if (!preg_match('#^https?://#', $bridgesMarket)) {
    $bridgesMarket = 'https://market.jeedom.com';
}

?>

<div class="scanip-modal scanip-view-normal" id="scanip_bridges" data-default-view="<?php echo $bridgesDefaultView; ?>" data-market="<?php echo scan_ip_tools::escape($bridgesMarket); ?>">
    <div id="div_alert_bridges"></div>
    <?php if (scan_ip_bridges::catalogIgnored()) { ?>
    <div class="scanip-callout scanip-callout-warn">
        <i class="fas fa-vial"></i>
        <div><b>{{Mode test : catalogue ignoré}}</b><br>{{Les bridges fournis ne sont pas appliqués et les plugins qu'ils couvrent sont proposés par la recherche et l'assistant. Désactivez l'option « Ignorer le catalogue » (configuration, mode debug, réservé au développement) pour revenir à la normale.}}</div>
    </div>
    <?php } ?>

    <!-- Liste des bridges -->
    <div id="scanip_bridges_list_view">
        <div class="scanip-toolbar">
            <a class="btn btn-default btn-sm" id="bt_scanipBridgesCatalog" title="{{Consulter la configuration de tous les bridges fournis}}"><i class="fas fa-book"></i> {{Catalogue}}</a>
            <a class="btn btn-default btn-sm scanip-adv" id="bt_scanipBridgesNew" title="{{Création manuelle, sans assistant}}"><i class="fas fa-plus-circle"></i> {{Création manuelle}}</a>
            <span class="scanip-spacer"></span>
            <div class="scanip-segmented scanip-bridges-view" role="group">
                <a data-view="normal"><i class="fas fa-leaf"></i> {{Normale}}</a>
                <a data-view="advanced"><i class="fas fa-sliders-h"></i> {{Avancée}}</a>
            </div>
        </div>

        <!-- Résultat de la découverte -->
        <div class="scanip-card scanip-bridges-discover" id="scanip_bridges_discover">
            <div class="scanip-card-title"><i class="fas fa-search-location"></i> <span class="scanip-norm">{{Créer un bridge : champs disponibles dans vos plugins}}</span><span class="scanip-adv">{{Champs contenant une ip dans vos plugins}}</span>
                <span class="scanip-spacer"></span>
                <a class="scanip-close" id="bt_scanipBridgesDiscover" title="{{Relancer la recherche}}"><i class="fas fa-sync-alt"></i></a>
            </div>
            <div class="scanip-bridges-discover-empty" id="scanip_bridges_discover_empty" style="display:none;"></div>
            <div class="scanip-meta" id="scanip_bridges_discover_help" style="margin-bottom:10px;">{{Chaque ligne est un champ où une ip a été trouvée et qu'aucun bridge ne gère encore. « Créer le bridge » lance l'assistant : vérifications, test et association aux appareils.}}</div>
            <div class="scanip-table-wrap">
                <table class="scanip-table" id="scanip_bridges_discover_table">
                    <thead>
                        <tr>
                            <th>{{Plugin}}</th>
                            <th>{{Champ}}</th>
                            <th class="scanip-center">{{Trouvé}}</th>
                            <th>{{Exemples de valeurs}}</th>
                            <th class="scanip-actions"></th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
            <div class="scanip-meta" id="scanip_bridges_discover_covered" style="margin-top:8px;"></div>
        </div>

        <div class="scanip-section"><i class="fas fa-project-diagram"></i> {{Vos bridges}}
            <span class="scanip-spacer"></span>
            <div class="scanip-stats" id="scanip_bridges_stats"></div>
            <div class="scanip-filter scanip-filter-inline">
                <i class="fas fa-search"></i>
                <input type="text" class="form-control input-sm" id="scanip_bridges_filter" placeholder="{{Filtrer}}">
            </div>
        </div>

        <div class="scanip-table-wrap">
            <table class="scanip-table" id="scanip_bridges_table">
                <thead>
                    <tr>
                        <th class="scanip-center" title="{{Actif}}">{{Actif}}</th>
                        <th>{{Bridge}}</th>
                        <th class="scanip-adv">{{Conditions}}</th>
                        <th title="{{Éléments trouvés dans le plugin / associés à une adresse MAC}}">{{Éléments}}</th>
                        <th title="{{Catalogue : fourni avec Scan.Ip ; Catalogue modifié : fourni puis modifié par vous ; Personnel : créé par vous}}">{{Origine}}</th>
                        <th class="scanip-actions"></th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>

    <!-- Assistant de création -->
    <div id="scanip_bridges_wizard_view" style="display:none;">
        <div class="scanip-toolbar">
            <a class="btn btn-default btn-sm scanip-bridges-back"><i class="fas fa-arrow-left"></i> {{Retour}}</a>
            <div class="scanip-stepper">
                <span data-step="1"><b>1</b> {{Champ}}</span>
                <span data-step="2"><b>2</b> {{Vérifications}}</span>
                <span data-step="3"><b>3</b> {{Terminé}}</span>
            </div>
        </div>

        <!-- Étape 2 : vérifications et réglages -->
        <div class="scanip-wizard-step" data-step="2">
            <div class="scanip-bridge-summary" id="scanip_wizard_path"></div>
            <div class="scanip-section"><i class="fas fa-clipboard-check"></i> {{Vérifications}}</div>
            <div class="scanip-wizard-checks" id="scanip_wizard_checks"></div>
            <div class="scanip-section"><i class="fas fa-link"></i> {{Éléments et associations}}</div>
            <div class="scanip-meta" style="margin-bottom:8px;">{{Seuls les appareils liés à ce plugin sont proposés (même ip, ou déjà associés à un de ses éléments). À chaque scan, l'élément recevra l'ip actuelle de l'appareil associé. Facultatif : l'association peut aussi se faire plus tard depuis la fiche de l'équipement Scan.Ip.}}</div>
            <div class="scanip-table-wrap">
                <table class="scanip-table" id="scanip_wizard_elements">
                    <thead>
                        <tr>
                            <th>{{Élément}}</th>
                            <th>{{Valeur actuelle}}</th>
                            <th>{{Associer à l'équipement Scan.Ip}}</th>
                            <th>{{Tester}} <sup><i class="fas fa-question-circle tooltips" title="{{Montre la valeur que le bridge écrirait avec cette ip. Rien n'est modifié.}}"></i></sup></th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
            <div class="scanip-wizard-actions">
                <a class="btn btn-default btn-sm" id="bt_scanipWizardPrev"><i class="fas fa-arrow-left"></i> {{Retour aux champs disponibles}}</a>
                <span class="scanip-spacer"></span>
                <a class="btn btn-success" id="bt_scanipWizardCreate"><i class="fas fa-check-circle"></i> {{Créer le bridge}}</a>
            </div>
        </div>

        <!-- Étape 3 : résultat -->
        <div class="scanip-wizard-step" data-step="3">
            <div id="scanip_wizard_done"></div>
            <div class="scanip-wizard-actions">
                <a class="btn btn-default btn-sm" id="bt_scanipWizardAnother"><i class="fas fa-plus-circle"></i> {{Créer un autre bridge}}</a>
                <span class="scanip-spacer"></span>
                <a class="btn btn-default" id="bt_scanipWizardTest"><i class="fas fa-play"></i> {{Tester le bridge}}</a>
                <a class="btn btn-primary scanip-bridges-back"><i class="fas fa-check"></i> {{Terminer}}</a>
            </div>
        </div>
    </div>

    <!-- Catalogue : configuration de tous les bridges fournis, en lecture seule -->
    <div id="scanip_bridges_catalog_view" style="display:none;">
        <div class="scanip-toolbar">
            <a class="btn btn-default btn-sm scanip-bridges-back"><i class="fas fa-arrow-left"></i> {{Retour}}</a>
            <div class="scanip-filter">
                <i class="fas fa-search"></i>
                <input type="text" class="form-control input-sm" id="scanip_bridges_catalog_filter" placeholder="{{Filtrer (plugin, champ...)}}">
            </div>
            <div class="scanip-stats" id="scanip_bridges_catalog_stats"></div>
            <span class="scanip-spacer"></span>
            <label class="checkbox-inline scanip-meta">
                <input type="checkbox" id="scanip_bridges_catalog_installed"> {{Uniquement mes plugins}}
            </label>
        </div>
        <div class="scanip-callout">
            <i class="fas fa-book"></i>
            <div>{{Configuration d'origine des bridges fournis avec Scan.Ip, à consulter pour vous en inspirer. Les bridges de vos plugins installés sont déjà dans « Vos bridges ».}}</div>
        </div>
        <div class="scanip-catalog" id="scanip_bridges_catalog_list"></div>
    </div>

    <!-- Formulaire d'un bridge -->
    <div id="scanip_bridges_form_view" style="display:none;">
        <div class="scanip-toolbar">
            <a class="btn btn-default btn-sm" id="bt_scanipBridgesBack"><i class="fas fa-arrow-left"></i> {{Retour à la liste}}</a>
            <span class="scanip-meta" id="scanip_bridge_form_title"></span>
            <span class="scanip-spacer"></span>
            <div class="scanip-segmented scanip-bridges-view" role="group">
                <a data-view="normal"><i class="fas fa-leaf"></i> {{Normale}}</a>
                <a data-view="advanced"><i class="fas fa-sliders-h"></i> {{Avancée}}</a>
            </div>
            <a class="btn btn-success btn-sm" id="bt_scanipBridgesSave"><i class="fas fa-check-circle"></i> {{Enregistrer}}</a>
        </div>

        <form class="form-horizontal" onsubmit="return false;">
            <input type="hidden" id="scanip_bridge_id">
            <!-- Activation : gérée par l'interrupteur de la liste, conservée à l'enregistrement -->
            <input type="checkbox" id="scanip_bridge_enabled" checked style="display:none;">
            <datalist id="scanip_bridge_keys"></datalist>
            <datalist id="scanip_bridge_condition_keys"></datalist>

            <div class="scanip-section"><i class="fas fa-crosshairs"></i> {{Champ contenant l'ip}}</div>
            <!-- Vue normale : récapitulatif non modifiable -->
            <div class="form-group scanip-norm">
                <label class="col-sm-3 control-label">{{Champ mis à jour}}</label>
                <div class="col-sm-8"><div class="scanip-bridge-summary" id="scanip_bridge_summary"></div></div>
            </div>
            <div class="form-group scanip-adv">
                <label class="col-sm-3 control-label">{{Plugin}}</label>
                <div class="col-sm-5">
                    <select class="form-control" id="scanip_bridge_plugin"></select>
                    <p class="form-control-static scanip-bridge-static" id="scanip_bridge_plugin_text"></p>
                </div>
            </div>
            <div class="form-group scanip-adv">
                <label class="col-sm-3 control-label">{{Emplacement}}</label>
                <div class="col-sm-5">
                    <select class="form-control" id="scanip_bridge_source">
                        <option value="eqLogic">{{Configuration des équipements}}</option>
                        <option value="logicalId">{{Identifiant logique des équipements (logicalId)}}</option>
                        <option value="cmd">{{Configuration des commandes}}</option>
                        <option value="pluginConfig">{{Configuration du plugin}}</option>
                    </select>
                </div>
            </div>
            <div class="form-group scanip-adv" id="scanip_bridge_key_group">
                <label class="col-sm-3 control-label">{{Champ}}
                    <sup><i class="fas fa-question-circle tooltips" title="{{Nom du paramètre qui contient l'ip. Pour la configuration d'un plugin, {n} remplace un numéro (ex. host_{n})}}"></i></sup>
                </label>
                <div class="col-sm-5">
                    <input type="text" class="form-control" id="scanip_bridge_key" list="scanip_bridge_keys" autocomplete="off" placeholder="{{ex. ip, addr, host...}}">
                </div>
                <div class="col-sm-offset-3 col-sm-8" id="scanip_bridge_key_values"></div>
            </div>
            <div class="form-group scanip-adv" id="scanip_bridge_repeat_group" style="display:none;">
                <label class="col-sm-3 control-label">{{Numéros}} {n}</label>
                <div class="col-sm-5">
                    <div class="input-group">
                        <span class="input-group-addon">{{de}}</span>
                        <input type="number" class="form-control" id="scanip_bridge_repeat_from" value="1" min="0">
                        <span class="input-group-addon">{{à}}</span>
                        <input type="number" class="form-control" id="scanip_bridge_repeat_to" value="" min="0" placeholder="10">
                        <span class="input-group-addon">{{ou champ}}</span>
                        <input type="text" class="form-control" id="scanip_bridge_repeat_toKey" placeholder="{{nombre défini par...}}">
                    </div>
                </div>
            </div>
            <div class="form-group scanip-adv">
                <label class="col-sm-3 control-label">{{Mise à jour}}</label>
                <div class="col-sm-5">
                    <select class="form-control" id="scanip_bridge_replace">
                        <option value="auto">{{Automatique : tout le champ s'il ne contient que l'ip, sinon seulement l'ip}}</option>
                        <option value="full">{{Le champ contient uniquement l'ip : il est remplacé}}</option>
                        <option value="ip">{{L'ip fait partie du champ (URL, hôte:port...) : seule l'ip est remplacée}}</option>
                    </select>
                </div>
            </div>

            <div class="form-group scanip-norm" id="scanip_bridge_hidden_conditions" style="display:none;">
                <label class="col-sm-3 control-label">{{Conditions}}</label>
                <div class="col-sm-8 scanip-meta" style="padding-top:7px;"></div>
            </div>

            <div class="scanip-section scanip-adv"><i class="fas fa-filter"></i> {{Conditions (optionnel)}}</div>
            <div class="form-group scanip-adv">
                <label class="col-sm-3 control-label">{{Ne prendre en compte que si}}
                    <sup><i class="fas fa-question-circle tooltips" title="{{Toutes les conditions doivent être vraies. @value = valeur du champ, @name = nom, @eqName = nom de l'équipement, @isEnable = équipement actif}}"></i></sup>
                </label>
                <div class="col-sm-8">
                    <div id="scanip_bridge_conditions"></div>
                    <a class="btn btn-default btn-xs" id="bt_scanipBridgeAddCondition"><i class="fas fa-plus"></i> {{Ajouter une condition}}</a>
                </div>
            </div>

            <div class="scanip-section scanip-adv"><i class="fas fa-cog"></i> {{Options}}</div>
            <div class="form-group scanip-adv">
                <label class="col-sm-3 control-label">{{Précision affichée}}
                    <sup><i class="fas fa-question-circle tooltips" title="{{Paramètre affiché à côté du nom du plugin dans la liste des associations (ex. type)}}"></i></sup>
                </label>
                <div class="col-sm-5"><input type="text" class="form-control" id="scanip_bridge_detailKey" list="scanip_bridge_condition_keys" autocomplete="off" placeholder="{{ex. type}}"></div>
            </div>
            <div class="form-group scanip-adv" id="scanip_bridge_name_group" style="display:none;">
                <label class="col-sm-3 control-label">{{Nom de l'élément}}</label>
                <div class="col-sm-5"><input type="text" class="form-control" id="scanip_bridge_name" placeholder="{{ex.}} Passerelle {n}"></div>
            </div>
            <div class="form-group scanip-adv" id="scanip_bridge_daemon_group">
                <label class="col-sm-3 control-label">{{Démon}}
                    <sup><i class="fas fa-question-circle tooltips" title="{{Certains plugins ne lisent leur configuration qu'au démarrage de leur démon : sans redémarrage, la nouvelle ip ne serait prise en compte qu'au prochain redémarrage. À activer uniquement pour ces plugins, car le redémarrage interrompt brièvement le plugin.}}"></i></sup>
                </label>
                <div class="col-sm-8">
                    <label class="checkbox-inline"><input type="checkbox" id="scanip_bridge_daemon"> {{Redémarrer le démon du plugin après une mise à jour de l'ip}}</label>
                    <div class="scanip-meta" style="margin-top:4px;">{{Utile quand le plugin ne relit pas sa configuration à chaud (souvent le cas de l'adresse d'une passerelle dans la configuration du plugin). Les bridges fournis sont déjà réglés.}}</div>
                </div>
            </div>

            <div class="scanip-section"><i class="fas fa-play"></i> {{Test}}</div>
            <div class="form-group">
                <label class="col-sm-3 control-label">{{Tester}}
                    <sup><i class="fas fa-question-circle tooltips" title="{{Par défaut, les éléments associés à une adresse MAC reçoivent l'ip actuelle de leur appareil : c'est ce que fera le prochain scan. Choisissez un élément pour lui appliquer l'ip de votre choix. Aucune modification n'est enregistrée.}}"></i></sup>
                </label>
                <div class="col-sm-8">
                    <div class="scanip-bridge-test">
                        <select class="form-control" id="scanip_bridge_test_element">
                            <option value="">{{Les éléments associés, avec l'ip de leur appareil}}</option>
                        </select>
                        <input type="text" class="form-control" id="scanip_bridge_test_ip" placeholder="{{ip à appliquer}}" style="display:none;">
                        <a class="btn btn-primary" id="bt_scanipBridgesSimulate"><i class="fas fa-play"></i> {{Lancer le test}}</a>
                    </div>
                </div>
            </div>
        </form>
        <div class="scanip-table-wrap" id="scanip_bridge_simulation" style="display:none;">
            <table class="scanip-table">
                <thead>
                    <tr>
                        <th>{{Élément}}</th>
                        <th>{{Valeur actuelle}}</th>
                        <th>{{Adresse MAC associée}}</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
</div>

<?php include_file('desktop', 'scan_ip', 'css', 'scan_ip'); ?>
<?php include_file('desktop', 'scan_ip_bridges', 'js', 'scan_ip'); ?>
