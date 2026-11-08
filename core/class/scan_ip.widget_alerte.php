<?php

/**
 * Description of scan_ip
 *
 * @author Ynats
 */

require_once __DIR__ . "/../../../../plugins/scan_ip/core/class/scan_ip.require_once.php";

class scan_ip_widget_alerte extends eqLogic {

    public static function getWidgetAlerteNewEquipement(){
        $eqLogics = eqLogic::byType('scan_ip');
        $exist = 0;
        foreach ($eqLogics as $scan_ip) {
            if(scan_ip_widgets::getWidgetType($scan_ip) == 'new_equipement') {
                $exist = 1;
                break;
            }
        }
        
        if($exist == 0){
            $scan_ip = new scan_ip();
            $scan_ip->setEqType_name("scan_ip");
            $scan_ip->setIsEnable(1);
            if(config::byKey('widget_new_equipement', 'scan_ip', '1') == 1) { 
                $scan_ip->setIsVisible(1); 
                $scan_ip->setIsEnable(1);
            } else { 
                $scan_ip->setIsVisible(0); 
                $scan_ip->setIsEnable(0);
            }
            $scan_ip->setDisplay('height',"230px");
            $scan_ip->setDisplay('width',"1130px");
            $scan_ip->setName("Scan.Ip Widget Alertes");
            $scan_ip->setConfiguration('type_widget', 'new_equipement');
            $scan_ip->save();
        }
        
        return $scan_ip;
    }
    
    public static function setCmdAlerteNewEquipement($_scanIp){
        
        for ($i = 0; $i <= (scan_ip::$_defaut_alerte_new_equipement -1); $i++) {
            $info = $_scanIp->getCmd(null, 'last_'.$i.'_mac');
            if (!is_object($info)) {
                $info = new scan_ipCmd();
                $info->setName(__('Connexion ', __FILE__).$i.__(' MAC', __FILE__));
            }
            $info->setEqLogic_id($_scanIp->getId());
            $info->setLogicalId('last_'.$i.'_mac');
            $info->setType('info');
            $info->setSubType('string');
            $info->save();
            
            $info = $_scanIp->getCmd(null, 'last_'.$i.'_time');
            if (!is_object($info)) {
                $info = new scan_ipCmd();
                $info->setName(__('Connexion ', __FILE__).$i.__(' Time', __FILE__));
            }
            $info->setEqLogic_id($_scanIp->getId());
            $info->setLogicalId('last_'.$i.'_time');
            $info->setType('info');
            $info->setSubType('numeric');
            $info->save();
            
            $info = $_scanIp->getCmd(null, 'last_'.$i.'_date');
            if (!is_object($info)) {
                $info = new scan_ipCmd();
                $info->setName(__('Connexion ', __FILE__).$i.__(' Date', __FILE__));
            }
            $info->setEqLogic_id($_scanIp->getId());
            $info->setLogicalId('last_'.$i.'_date');
            $info->setType('info');
            $info->setSubType('string');
            $info->save();
            
            $info = $_scanIp->getCmd(null, 'last_'.$i.'_ip_v4');
            if (!is_object($info)) {
                $info = new scan_ipCmd();
                $info->setName(__('Connexion ', __FILE__).$i.__(' IpV4', __FILE__));
            }
            $info->setEqLogic_id($_scanIp->getId());
            $info->setLogicalId('last_'.$i.'_ip_v4');
            $info->setType('info');
            $info->setSubType('string');
            $info->save();
            
            $info = $_scanIp->getCmd(null, 'last_'.$i.'_equipement');
            if (!is_object($info)) {
                $info = new scan_ipCmd();
                $info->setName(__('Connexion ', __FILE__).$i.__(' Equipement', __FILE__));
            }
            $info->setEqLogic_id($_scanIp->getId());
            $info->setLogicalId('last_'.$i.'_equipement');
            $info->setType('info');
            $info->setSubType('string');
            $info->save();
            
            $info = $_scanIp->getCmd(null, 'last_'.$i.'_record');
            if (!is_object($info)) {
                $info = new scan_ipCmd();
                $info->setName(__('Connexion ', __FILE__).$i.__(' Création', __FILE__));
            }
            $info->setEqLogic_id($_scanIp->getId());
            $info->setLogicalId('last_'.$i.'_record');
            $info->setType('info');
            $info->setSubType('string');
            $info->save();
        }
        
        $refresh = $_scanIp->getCmd(null, 'refresh');
        if (!is_object($refresh)) {
            $refresh = new scan_ipCmd();
            $refresh->setName(__('Rafraichir', __FILE__));
        }
        $refresh->setEqLogic_id($_scanIp->getId());
        $refresh->setLogicalId('refresh');
        $refresh->setType('action');
        $refresh->setSubType('other');
        $refresh->save();
    }
    
    public static function cmdRefreshWidgetAlerte($_eqlogic, $_mapping = NULL){
        
        $mac_id = scan_ip_eqLogic::getEquipementsbyId();
        
        if($mac_id == NULL OR empty($_mapping["byTime"])){
            return;
        }
        
        // Recherche par clé (O(1)) au lieu de in_array
        $knownMac = array_flip($mac_id);
        $nbAlertes = scan_ip::$_defaut_alerte_new_equipement;
        
        // byTime est déjà trié du plus récent au plus ancien : on s'arrête dès qu'on a assez d'éléments
        $temp = array();
        foreach ($_mapping["byTime"] as $allElement) { 
            foreach ($allElement as $element) { 
                if(!isset($knownMac[$element["mac_id"]])){
                    $temp[] = $element;
                    if(count($temp) >= $nbAlertes){
                        break 2;
                    }
                }  
            }
        }
        
        // Récupération groupée des commandes (une seule requête au lieu d'une par checkAndUpdateCmd)
        $cmdCache = array();
        foreach ($_eqlogic->getCmd('info') as $cmd) {
            $cmdCache[$cmd->getLogicalId()] = $cmd;
        }

        for ($i = 0; $i < $nbAlertes; $i++) {
            $time = !empty($temp[$i]["time"]) ? $temp[$i]["time"] : NULL;
            $values = array(
                'record' => !empty($temp[$i]["record"]) ? $temp[$i]["record"] : NULL,
                'mac' => !empty($temp[$i]["mac"]) ? $temp[$i]["mac"] : NULL,
                'time' => $time,
                'date' => ($time != NULL) ? scan_ip_tools::printDate($time) : NULL,
                'equipement' => !empty($temp[$i]["equipement"]) ? $temp[$i]["equipement"] : NULL,
                'ip_v4' => !empty($temp[$i]["ip_v4"]) ? $temp[$i]["ip_v4"] : NULL,
            );
            
            foreach ($values as $suffix => $value) {
                $logicalId = 'last_'.$i.'_'.$suffix;
                if(isset($cmdCache[$logicalId])){
                    $_eqlogic->checkAndUpdateCmd($cmdCache[$logicalId], $value);
                }
            }
        }
        
    }
    
    public static function createAlerteWidget($scan_ip, $_replace) {

        log::add('scan_ip', 'debug', 'createAlerteWidget :.  ' . __('Lancement', __FILE__));

        $replace = $_replace;
        
        $temp = array();
        
        $replace["#last_unknown_equipement#"] = '<table style="width: 100%; margin: -5px -5px 22px 0;">
        <thead>
            <tr style="background-color: grey !important; color: white !important;">
                <th style="padding : 3px 0 3px 15px !important; text-align: center;"></th>
                <th style="padding : 3px 0 3px 15px !important;">' . __('Date', __FILE__) . '</th>
                <th style="padding : 3px 0 3px 15px !important;">' . __('Adresse MAC', __FILE__) . '</th>
                <th style="padding : 3px 0 3px 15px !important;">' . __('Ip', __FILE__) . '</th>
                <th style="padding : 3px 0 3px 15px !important;">' . __('Information', __FILE__) . '</th>
                <th style="padding : 3px 0 3px 15px !important;">' . __('Vu la première fois', __FILE__) . '</th>
            </tr>
        </thead>
        <tbody>';
        
        for ($i = 0; $i <= (scan_ip::$_defaut_alerte_new_equipement -1); $i++) {
            if(!empty(scan_ip_cmd::getCommande("last_".$i."_date", $scan_ip))){ 
                
                $record = scan_ip_cmd::getCommande("last_".$i."_record", $scan_ip, NULL);
                $time = scan_ip_cmd::getCommande("last_".$i."_time", $scan_ip, NULL);
                $offline_time = $scan_ip->getConfiguration("offline_time", scan_ip::$_defaut_offline_time);
                
                if($record != NULL){
                    $record = date("d/m/Y H:i:s", $record);
                } else {
                    $record = NULL;
                }
                
                if (scan_ip_tools::isOffline($offline_time, $time) == 0) {
                    $element["colorOnLine"] = "#50aa50";
                    $element["titleOnLine"] = __('En ligne', __FILE__);
                } else {
                    $element["colorOnLine"] = "red";
                    $element["titleOnLine"] = __("Hors ligne", __FILE__);
                }
                
                $replace["#last_unknown_equipement#"] .= '<tr>'
                . '<td style="padding : 3px 0 3px 15px !important;" title="' . $element["titleOnLine"] .'">' . scan_ip_tools::getCycle("15px", $element["colorOnLine"]) . '</td>'
                . '<td style="padding : 3px 0 3px 15px !important;">' . scan_ip_cmd::getCommande("last_".$i."_date", $scan_ip) . '</td>'
                . '<td style="padding : 3px 0 3px 15px !important;">' . scan_ip_cmd::getCommande("last_".$i."_mac", $scan_ip) . '</td>'
                . '<td style="padding : 3px 0 3px 15px !important;">' . scan_ip_cmd::getCommande("last_".$i."_ip_v4", $scan_ip) . '</td>'
                . '<td style="padding : 3px 0 3px 15px !important;">' . scan_ip_cmd::getCommande("last_".$i."_equipement", $scan_ip) . '</td>'
                . '<td style="padding : 3px 0 3px 15px !important;">' . $record . '</td>'
                . '</tr>';
            }
        }
        
        $replace["#last_unknown_equipement#"] .= '</tbody></table>';
        
        return $replace;
    }
    
}
