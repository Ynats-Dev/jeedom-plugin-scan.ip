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

require_once dirname(__FILE__) . '/../../../core/php/core.inc.php';

include_file('core', 'authentification', 'php');
if (!isConnect()) {
    include_file('desktop', '404', 'php');
    die();
}

require_once dirname(__FILE__) . "/../../../plugins/scan_ip/core/class/scan_ip.bridges.php";
require_once dirname(__FILE__) . "/../../../plugins/scan_ip/core/class/scan_ip.tools.php";

?>
<form class="form-horizontal">
    
    <!-- Mode d'affichage : la liste (enregistrée par Jeedom) est pilotée par le sélecteur -->
    <div class="form-group">
        <div class="col-lg-offset-1 col-lg-9">
            <div class="scanip-config-mode">
                <div class="scanip-config-mode-text">
                    <div class="scanip-config-mode-title"><i class="fas fa-eye"></i> {{Mode d'affichage}}</div>
                    <div class="scanip-meta" id="scan_ip_mode_description"></div>
                </div>
                <div class="scanip-segmented" id="scan_ip_mode_switch">
                    <a data-mode="normal"><i class="fas fa-leaf"></i> {{Normal}}</a>
                    <a data-mode="advanced"><i class="fas fa-sliders-h"></i> {{Avancé}}</a>
                    <a data-mode="debug"><i class="fas fa-bug"></i> {{Debug}}</a>
                </div>
                <select onchange="scan_ip_mode_plugin()" class="configKey form-control" data-l1key="mode_plugin" id="scan_ip_mode" style="display:none;">
                    <option value="normal">{{Mode normal}}</option>
                    <option value="advanced">{{Mode avancé}}</option>
                    <option value="debug">{{Mode debug}}</option>
                </select>
            </div>
        </div>
    </div>
    
    <div id="dataConfig"></div>
    
    <fieldset>
<?php
        scan_ip_tools::vueSubTitle("{{Widgets dédiés à Scan.Ip}}", "config", "fa-th-large");
?>
        <div class="form-group">
            <label class="col-lg-4 control-label">{{Widget Network}}
                <sup><i class="fa fa-question-circle tooltips" title="{{Permet d'afficher un widget avec l'ensemble du réseau}}"></i></sup>
            </label>
            <div class="col-lg-5">
                <label class="scanip-switch-row">
                    <span class="scanip-switch"><input type="checkbox" class="configKey" data-l1key="widget_network"><span></span></span>
                    <span>{{Afficher le widget de votre réseau}}</span>
                </label>
            </div>
        </div>
        <div class="form-group">
            <label class="col-lg-4 control-label">{{Widget Alertes}}
                <sup><i class="fa fa-question-circle tooltips" title="<?php echo sprintf(__('Permet d\'afficher le widget des %s derniers équipements entrant dans votre réseau', __FILE__), scan_ip::$_defaut_alerte_new_equipement) ?>"></i></sup>
            </label>
            <div class="col-lg-5">
                <label class="scanip-switch-row">
                    <span class="scanip-switch"><input type="checkbox" class="configKey" data-l1key="widget_new_equipement"><span></span></span>
                    <span><?php echo sprintf(__('Afficher le widget des %s derniers équipements non enregistrés dans le réseau', __FILE__), scan_ip::$_defaut_alerte_new_equipement) ?></span>
                </label>
            </div>
        </div>
        
        <div id="show_avance" style="display:none;">   
<?php
        scan_ip_tools::vueSubTitle("{{Votre réseau}}", "config", "fa-network-wired", "advanced");
?>     
        <div class="form-group">
            <label class="col-lg-4 control-label">{{Routeur}}</label>
            <div class="col-lg-5">
                <label class="scanip-switch-row">
                    <span class="scanip-switch"><input type="checkbox" class="configKey" data-l1key="add_network_routeur"><span></span></span>
                    <span>{{Afficher le routeur dans la liste du réseau}}</span>
                </label>
            </div>
        </div>
        <div class="form-group">
            <label class="col-lg-4 control-label">{{Jeedom}}</label>
            <div class="col-lg-5">
                <label class="scanip-switch-row">
                    <span class="scanip-switch"><input type="checkbox" class="configKey" data-l1key="add_network_jeedom"><span></span></span>
                    <span>{{Afficher Jeedom dans la liste du réseau}}</span>
                </label>
            </div>
        </div>
<?php
        scan_ip_tools::vueSubTitle("{{Nombre d'essais lors des scans}}", "config", "fa-redo", "advanced");
?>             
        <div class="form-group">
            <label class="col-lg-4 control-label">{{Nombre d'essais}}
            <sup><i class="fa fa-question-circle tooltips" title="{{Cela conditionne la durée du scan mais permet d'avoir de meilleurs retours sur l'exploration de votre réseau}}"></i></sup>
            </label>
            <div class="col-lg-5">
                <select class="configKey form-control" data-l1key="add_retry_scan">
                    <option value="1">{{1 tentative}}</option>
                    <option value="2">{{2 tentatives}}</option>
                    <option value="3">{{3 tentatives (par défaut)}}</option>
                    <option value="4">{{4 tentatives}}</option>
                    <option value="5">{{5 tentatives}}</option>
                    <option value="6">{{6 tentatives}}</option>
                </select> 
            </div>
        </div>
                      
            
    </div> 
           
    <div id="show_debug" style="display:none;">        
<?php
    // Test des bridges : réservé au développement (fichier data/dev/modeDev présent)
    if (scan_ip_dev::modeDeveloppeur()) {
        scan_ip_tools::vueSubTitle("{{Test des bridges}}", "config", "fa-vial", "debug");
?>
        <div class="form-group">
            <label class="col-lg-4 control-label">{{Ignorer le catalogue}}
                <sup><i class="fa fa-question-circle tooltips" title="{{Pour tester la recherche des ip et l'assistant de création : les plugins couverts par un bridge fourni redeviennent proposés. N'agit qu'en mode développeur, avec le mode debug.}}"></i></sup>
            </label>
            <div class="col-lg-5">
                <label class="scanip-switch-row">
                    <span class="scanip-switch"><input type="checkbox" class="configKey" data-l1key="bridges_ignore_catalog"><span></span></span>
                    <span>{{Faire comme si aucun bridge n'était fourni}}</span>
                </label>
                <div class="scanip-meta" style="margin-top:4px;">{{Les bridges du catalogue ne sont plus appliqués pendant le test. Rien n'est supprimé : vos associations et vos modifications sont retrouvées en désactivant l'option ou en quittant le mode debug. Les bridges créés pendant le test restent, supprimez-les depuis la fenêtre Bridges si besoin.}}</div>
            </div>
        </div>
<?php
    }
        scan_ip_tools::vueSubTitle("{{Vérification des données}}", "config", "fa-clipboard-check", "debug");
?>
        <div class="form-group">
            <label class="col-lg-4 control-label">{{Vérification des données}}</label>
            <div class="col-lg-5"><?php echo scan_ip_maj::printVersionPlugin(); ?>
            </div>
        </div>
<?php
        scan_ip_tools::vueSubTitle("{{Dépendances}}", "config", "fa-cubes", "debug");
?>
        <div class="form-group">
            <label class="col-lg-4 control-label">iproute2</label>
            <div class="col-lg-5"><?php echo scan_ip_shell::dependancy_iproute2()[1] ?>
            </div>
        </div>
        <div class="form-group">
            <label class="col-lg-4 control-label">net-tools</label>
            <div class="col-lg-5"><?php echo scan_ip_shell::dependancy_net_tools()[1] ?>
            </div>
        </div>
        <div class="form-group">
            <label class="col-lg-4 control-label">arp-scan</label>
            <div class="col-lg-5"><?php echo scan_ip_shell::dependancy_arp_scan()[1] ?>
            </div>
        </div>
        <div class="form-group">
            <label class="col-lg-4 control-label">etherwake</label>
            <div class="col-lg-5"><?php echo scan_ip_shell::dependancy_etherwake()[1] ?>
            </div>
        </div>
        <div class="form-group">
            <label class="col-lg-4 control-label">wakeonlan</label>
            <div class="col-lg-5"><?php echo scan_ip_shell::dependancy_wakeonlan()[1] ?>
            </div>
        </div>
    </div>
<?php
        scan_ip_tools::vueSubTitle("{{Cadence de rafraîchissement}}", "config", "fa-clock");
?>
        <div class="form-group">
            <label class="col-lg-4 control-label">{{Cadence de rafraîchissement}}
                <sup><i class="fa fa-question-circle tooltips" title="<?php echo sprintf(__('Il est recommandé de laisser ce paramètre à %s minute(s)', __FILE__), scan_ip::$_defaut_cron_pass) ?>"></i></sup>
            </label>
            <div class="col-lg-5">
                <select class="configKey form-control" id="cron_pass" data-l1key="cron_pass">
                    <option value="1">{{1 minute (recommandé)}}</option>
                    <option value="2">{{2 minutes}}</option>
                    <option value="3">{{3 minutes}}</option>
                </select> 
            </div>
        </div>
<?php
        scan_ip_tools::vueSubTitle("{{Fichiers constructeurs (oui.txt et iab.txt)}}", "config", "fa-database");
?>
        <div class="form-group">
            <label class="col-lg-4 control-label">{{Mise à jour automatique}}
                <sup><i class="fa fa-question-circle tooltips" title="{{Ces fichiers permettent d'identifier le constructeur d'un appareil à partir de son adresse MAC. Ils sont téléchargés depuis le site de l'IEEE ; en cas d'échec, les fichiers existants sont conservés et une nouvelle tentative a lieu le lendemain.}}"></i></sup>
            </label>
            <div class="col-lg-5">
                <!-- Mode normal : fréquence affichée en lecture seule -->
                <div class="scanip-vendor-normal" style="padding-top:5px;"><span class="scanip-stat"><i class="fas fa-sync-alt"></i> <span id="scan_ip_vendor_auto_label">{{Tous les mois}}</span></span></div>
                <!-- Modes avancé et debug : fréquence modifiable -->
                <div class="scanip-vendor-advanced" style="display:none;">
                    <select class="configKey form-control" data-l1key="vendor_files_auto_update" id="scan_ip_vendor_auto">
                        <option value="monthly">{{Tous les mois}}</option>
                        <option value="weekly">{{Toutes les semaines}}</option>
                        <option value="0">{{Désactivée}}</option>
                    </select>
                </div>
            </div>
        </div>
        <div class="form-group">
            <label class="col-lg-4 control-label">{{Dernière mise à jour}}</label>
            <div class="col-lg-5" style="padding-top:7px;" id="scan_ip_vendor_last"><?php echo scan_ip_tools::getVendorFilesLastUpdate(); ?></div>
        </div>
        <!-- Mode debug : état des fichiers et mise à jour manuelle -->
        <div class="scanip-vendor-debug" style="display:none;">
            <div class="form-group">
                <label class="col-lg-4 control-label">{{Fichiers présents}}</label>
                <div class="col-lg-5" style="padding-top:7px;">
                    <div><?php echo scan_ip_tools::printFileOuiExist() ?></div>
                    <div><?php echo scan_ip_tools::printFileIatExist() ?></div>
                </div>
            </div>
            <div class="form-group">
                <label class="col-lg-4 control-label"></label>
                <div class="col-lg-5">
                    <a class="btn btn-default btn-sm" id="bt_scanIpUpdateVendorFiles"><i class="fas fa-download"></i> {{Mettre à jour maintenant}}</a>
                    <span class="scanip-meta" id="scan_ip_vendor_files_status" style="margin-left:8px;"></span>
                </div>
            </div>
        </div>
    
    <div id="show_sous_reseau" style="display:none;">     
    <?php
        scan_ip_tools::vueSubTitle("{{Plages à scanner}}", "config", "fa-sitemap", "advanced");
        echo scan_ip_tools::printInputSubConfig(); 
    ?> 
    </div>
    <?php
        scan_ip_tools::vueSubTitle("{{Bridges}}", "config", "fa-project-diagram");
        $bridgesSummary = scan_ip_bridges::countSummary();
    ?>
        <div class="form-group">
            <label class="col-lg-4 control-label">{{Bridges}}
                <sup><i class="fa fa-question-circle tooltips" title="{{Un bridge met à jour l'ip enregistrée dans un autre plugin quand l'adresse MAC associée change d'ip}}"></i></sup>
            </label>
            <div class="col-lg-6">
                <div class="scanip-config-inline">
                    <span class="scanip-stat"><span class="scanip-dot is-on"></span><b><?php echo $bridgesSummary['enabled']; ?></b> / <?php echo $bridgesSummary['shown']; ?> {{actif(s)}}</span>
                    <?php if ($bridgesSummary['user'] > 0) { echo '<span class="scanip-stat"><b>' . $bridgesSummary['user'] . '</b> ' . __('personnel(s)', __FILE__) . '</span>'; } ?>
                    <a class="btn btn-default btn-sm" id="bt_scanIpConfigBridges"><i class="fas fa-project-diagram"></i> {{Gérer les bridges}}</a>
                </div>
            </div>
        </div>
    </fieldset>
    <br />
</form>

<?php include_file('desktop', 'scan_ip', 'css', 'scan_ip'); ?>
<?php include_file('desktop', 'scan_ip_configuration', 'js', 'scan_ip'); ?>
