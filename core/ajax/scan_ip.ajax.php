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

try {
    require_once dirname(__FILE__) . '/../../../../core/php/core.inc.php';
    include_file('core', 'authentification', 'php');

    if (!isConnect('admin')) {
        throw new Exception(__('401 - Accès non autorisé', __FILE__));
    }
    
    require_once dirname(__FILE__) . '/../../../../plugins/scan_ip/core/class/scan_ip.require_once.php';
    
    ajax::init();
    
    if (init('action') == 'getScanProgress') {

        ajax::success(scan_ip_tools::getScanProgress());

    }

    elseif (init('action') == 'syncEqLogicWithOpenScanId') {
        
        // Libère la session pour que l'interface puisse suivre l'avancement pendant le scan
        @session_write_close();
        
        if(scan_ip_tools::lockProcess() == TRUE){
            scan_ip_scan::syncScanIp();
            scan_ip_tools::unlockProcess();  
            ajax::success(array('alreadyRunning' => false));
        } else {
            ajax::success(array('alreadyRunning' => true));
        }
    }
    
    elseif (init('action') == 'recordCommentaires') {

        scan_ip_json::majNetworkCommentaires(init('data'));
        ajax::success();
        
    }
    
    elseif (init('action') == 'updateVendorFiles') {

        // Téléchargement long : on libère la session pour ne pas bloquer l'interface
        @session_write_close();
        @set_time_limit(400);
        ajax::success(scan_ip_tools::updateVendorFiles());

    }

    elseif (init('action') == 'addEquipementFromNetwork') {

        $mac = trim(init('mac'));
        if (!preg_match('/^([0-9A-Fa-f]{2}:){5}[0-9A-Fa-f]{2}$/', $mac)) {
            throw new Exception(__('Adresse MAC invalide', __FILE__) . ' : ' . $mac);
        }
        ajax::success(array('id' => scan_ip_eqLogic::createElement($mac)));

    }

    elseif (init('action') == 'addEquipement') {
        
        scan_ip_eqLogic::addEquipementsTab(init('data'));
        ajax::success();
        
    }
    
    elseif (init('action') == 'removeEquipement') {
        
        scan_ip_json::removeEquipementsTab(init('data'));
        ajax::success();
        
    }
    
    /* ---------- Bridges ---------- */

    elseif (init('action') == 'bridgesList') {

        ajax::success(array(
            'rules' => scan_ip_bridges::listRules(),
            'plugins' => scan_ip_bridges::installedPlugins(),
            'daemons' => scan_ip_bridges::pluginsWithDaemon(),
        ));

    }

    elseif (init('action') == 'bridgesAnalyze') {

        @session_write_close();
        ajax::success(scan_ip_bridges::analyze(init('plugin'), init('source'), init('key')));

    }

    elseif (init('action') == 'bridgesWizardCreate') {

        $rule = json_decode(init('rule'), true);
        if (!is_array($rule)) {
            throw new Exception(__('Bridge invalide', __FILE__));
        }
        $associations = json_decode(init('associations'), true);
        ajax::success(scan_ip_bridges::wizardCreate($rule, is_array($associations) ? $associations : array()));

    }

    elseif (init('action') == 'bridgesProposal') {

        ajax::success(scan_ip_bridges::proposal(init('id')));

    }

    elseif (init('action') == 'bridgesCatalog') {

        ajax::success(scan_ip_bridges::listCatalog());

    }

    elseif (init('action') == 'bridgesDiscover') {

        @session_write_close();
        @set_time_limit(120);
        ajax::success(scan_ip_bridges::discover());

    }

    elseif (init('action') == 'bridgesFields') {

        ajax::success(scan_ip_bridges::fieldSuggestions(init('plugin'), init('source')));

    }

    elseif (init('action') == 'bridgesSimulate') {

        $rule = json_decode(init('rule'), true);
        if (!is_array($rule)) {
            throw new Exception(__('Bridge invalide', __FILE__));
        }
        ajax::success(scan_ip_bridges::simulate($rule, init('testIp'), init('testValue')));

    }

    elseif (init('action') == 'bridgesSave') {

        $rule = json_decode(init('rule'), true);
        if (!is_array($rule)) {
            throw new Exception(__('Bridge invalide', __FILE__));
        }
        ajax::success(array('id' => scan_ip_bridges::saveRule($rule)));

    }

    elseif (init('action') == 'bridgesToggle') {

        scan_ip_bridges::setRuleEnabled(init('id'), init('enabled') == 1);
        ajax::success();

    }

    elseif (init('action') == 'bridgesDelete') {

        scan_ip_bridges::deleteRule(init('id'));
        ajax::success();

    }

    throw new Exception(__('Aucune méthode correspondante à : ', __FILE__) . init('action'));
    /*     * *********Catch exeption*************** */
} catch (Exception $e) {
    ajax::error(displayException($e), $e->getCode());
}
