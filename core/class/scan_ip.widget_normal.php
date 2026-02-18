<?php

/**
 * Description of scan_ip
 *
 * @author Ynats
 */

require_once __DIR__ . "/../../../../plugins/scan_ip/core/class/scan_ip.require_once.php";

class scan_ip_widget_normal extends eqLogic {
    
    public static function cmdRefreshWidgetNormal($_eqlogic, $_mapping = NULL){
        $device = scan_ip_json::searchByMac($_eqlogic->getConfiguration("mac_id"), $_mapping);
        $offline_time = $_eqlogic->getConfiguration("offline_time", scan_ip::$_defaut_offline_time);
        $isOnline = (!empty($device["time"]) && scan_ip_tools::isOffline($offline_time, $device["time"]) == 0);

        // 2. Récupération groupée
        $cmds = $_eqlogic->getCmd();
        $cmdCache = [];
        foreach($cmds as $c) {
            $cmdCache[$c->getLogicalId()] = $c;
        }

        // 3. Mise à jour des commandes
        $hasChangedStatus = false;
        if ($isOnline){
	        $_eqlogic->setStatus(array('lastCommunication' => date('Y-m-d H:i:s'), 'timeout' => 0));
            if (isset($cmdCache['on_line']) && $cmdCache['on_line']->execCmd() != 1) {
                $cmdCache['on_line']->event(1);
                $hasChangedStatus = true; // L'appareil vient d'apparaître
            }
            if (isset($cmdCache['ip_v4']) && $cmdCache['ip_v4']->execCmd() != $device["ip_v4"]) {
                $cmdCache['ip_v4']->event($device["ip_v4"]);
            }
        } else {
            if (isset($cmdCache['on_line']) && $cmdCache['on_line']->execCmd() != 0) {
                $cmdCache['on_line']->event(0);
            }
            if (isset($cmdCache['ip_v4']) && $cmdCache['ip_v4']->execCmd() != "") {
                $cmdCache['ip_v4']->event(NULL);
            }
        }

        // 4. Gestion des dates (CIBLE DE L'OPTIMISATION)
        if ($isOnline && !empty($device["time"])) {
            $oldIp = $_eqlogic->getConfiguration('last_ip_v4');

            // CONDITION : On ne met à jour la date que si :
            // - L'appareil vient de passer Online ($hasChangedStatus)
            // - OU l'IP a changé ($oldIp != $device["ip_v4"])
            // - OU on est à une minute ronde (ex: :00) pour garder une trace en base
            if ($hasChangedStatus || $oldIp != $device["ip_v4"]) {
                if (isset($cmdCache['update_time'])) {
                    $cmdCache['update_time']->event($device["time"]);
                }
                $newDate = date("d/m/Y H:i:s", $device["time"]);
                if (isset($cmdCache['update_date'])) {
                    $cmdCache['update_date']->event($newDate);
                }
            }
        }

        // 5. Gestion des Bridges et de la Config uniquement si l'ip a changé pour éviter les traitements inutiles
        $oldIp = $_eqlogic->getConfiguration('last_ip_v4');
        if ($device["ip_v4"] != "" && $oldIp != $device["ip_v4"]) {
            $_eqlogic->setConfiguration('last_ip_v4', $device["ip_v4"]);
            $_eqlogic->save();
            scan_ip_bridges::majElementsAssocies($_eqlogic, $device);
        }
    }

    public static function getIdWidgetSpeciaux(){
        $return = NULL;
        $widgetDetect = 0;
        $widgetMax = 2;
        foreach ($eqLogics as $eqLogic) {
            if($eqLogic->getConfiguration('type_widget', 'normal') == "network"){
                $return["network"] = $eqLogic->getId();
                $widgetDetect++;
            } 
            elseif($eqLogic->getConfiguration('type_widget', 'normal') == "new_equipement"){
                $return["new_equipement"] = $eqLogic->getId();
                $widgetDetect++;
            }
            if($widgetDetect >= $widgetMax){
                break;
            }
        }
        return $return;
    }
    
    public static function setCmdWidgetNormal($_scanIp){
        
        $info = $_scanIp->getCmd(null, 'ip_v4');
        if (!is_object($info)) {
            $info = new scan_ipCmd();
            $info->setName(__('IpV4', __FILE__));
        }
        $info->setLogicalId('ip_v4');
        $info->setEqLogic_id($_scanIp->getId());
        $info->setIsHistorized(0);
        $info->setIsVisible(0);
        $info->setType('info');
        $info->setSubType('string');
        $info->save();
        
        $info = $_scanIp->getCmd(null, 'mac');
        if (!is_object($info)) {
            $info = new scan_ipCmd();
            $info->setName(__('mac', __FILE__));
        }
        $info->setLogicalId('mac');
        $info->setEqLogic_id($_scanIp->getId());
        $info->setIsHistorized(0);
        $info->setIsVisible(0);
        $info->setType('info');
        $info->setSubType('string');
        $info->save();

        $info = $_scanIp->getCmd(null, 'last_ip_v4');
        if (!is_object($info)) {
            $info = new scan_ipCmd();
            $info->setName(__('Last IpV4', __FILE__));
        }
        $info->setLogicalId('last_ip_v4');
        $info->setEqLogic_id($_scanIp->getId());
        $info->setIsHistorized(0);
        $info->setIsVisible(0);
        $info->setType('info');
        $info->setSubType('string');
        $info->save();

        $info = $_scanIp->getCmd(null, 'update_time');
        if (!is_object($info)) {
            $info = new scan_ipCmd();
            $info->setName(__('Last Time', __FILE__));
        }
        $info->setLogicalId('update_time');
        $info->setEqLogic_id($_scanIp->getId());
        $info->setIsHistorized(0);
        $info->setIsVisible(0);
        $info->setType('info');
        $info->setSubType('numeric');
        $info->save();

        $info = $_scanIp->getCmd(null, 'update_date');
        if (!is_object($info)) {
            $info = new scan_ipCmd();
            $info->setName(__('Last Date', __FILE__));
        }
        $info->setLogicalId('update_date');
        $info->setEqLogic_id($_scanIp->getId());
        $info->setIsHistorized(0);
        $info->setIsVisible(0);
        $info->setType('info');
        $info->setSubType('string');
        $info->save();


        $info = $_scanIp->getCmd(null, 'on_line');
        if (!is_object($info)) {
            $info = new scan_ipCmd();
            $info->setName(__('Online', __FILE__));
        }
        $info->setEqLogic_id($_scanIp->getId());
        $info->setIsHistorized(1);
        $info->setLogicalId('on_line');
        $info->setType('info');
        $info->setSubType('binary');
        $info->save();

        $wol = $_scanIp->getCmd(null, 'wol');
        if($_scanIp->getConfiguration("enable_wol") == 1){ 
            if (!is_object($wol)) {
                $wol = new scan_ipCmd();
                $wol->setName(__('WoL', __FILE__));
            }
            $wol->setEqLogic_id($_scanIp->getId());
            $wol->setLogicalId('wol');
            $wol->setType('action');
            $wol->setSubType('other');
            $wol->save();
        } else {
            if (is_object($wol)) {
                $wol->remove();
                ajax::success(utils::o2a($_scanIp));
            } 
        }  
        
    }

    public static function createSimpleWidget($scan_ip, $_version = "dashboard", $_replace) {

        log::add('scan_ip', 'debug', 'createSimpleWidget :.  Lancement');

        $replace = $_replace;

        $replace["#ip_v4#"] = scan_ip_cmd::getCommande('ip_v4', $scan_ip);
        if($replace["#ip_v4#"] == "" OR $replace["#ip_v4#"] == 0){ $replace["#ip_v4#"] = "..."; $replace["#etat_cycle#"] = "red"; }
        else { $replace["#etat_cycle#"] = "#50aa50"; }

        if(!empty(scan_ip_cmd::getCommande('last_ip_v4', $scan_ip))){ $replace["#last_ip_v4#"] = scan_ip_cmd::getCommande('last_ip_v4', $scan_ip); } 
        else { $replace["#last_ip_v4#"] = "..."; }

        if(!empty(scan_ip_cmd::getCommande('update_date', $scan_ip))){ $replace["#update_date#"] = scan_ip_cmd::getCommande('update_date', $scan_ip); } 
        else { $replace["#update_date#"] = "..."; }

        if(!empty(scan_ip_cmd::getCommande('mac', $scan_ip))){ $replace["#mac#"] = scan_ip_cmd::getCommande('mac', $scan_ip); }
        else { $replace["#mac#"] = "..."; }

//        if($replace["#ip_v4#"] == "..."){ $replace["#etat_cycle#"] = "red"; } 
//        else{ $replace["#etat_cycle#"] = "#50aa50"; } 

        if($replace["#last_ip_v4#"] != $replace["#ip_v4#"] AND $replace["#ip_v4#"] != "..."){ $replace["#etat_last_ip#"] = ' color:orange;'; } 
        else { $replace["#etat_last_ip#"] = ''; }

        $wol = $scan_ip->getCmd(null,'wol');
        $replace['#cmdWol#'] = (is_object($wol)) ? $wol->getId() : '';

        if($scan_ip->getConfiguration("enable_wol") == 0){ $replace['#enableWol#'] = "display:none;"; }
        else { $replace['#enableWol#'] = ""; }

        return $replace;
    }

}
