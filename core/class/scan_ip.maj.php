<?php

require_once __DIR__ . "/../../../../plugins/scan_ip/core/class/scan_ip.require_once.php";

class scan_ip_maj extends eqLogic {
    
    public static $_versionPlugin = 1.1;
    
    public static function checkPluginVersionAJour(){
        if(self::getVersionPlugin() == self::$_versionPlugin){
            return TRUE;
        } else {
            return FALSE;
        }
    }
    
    public static function pluginAJour(){
        
        $checkData = 0;
        if(self::checkJsonCommentaires_v1_1() == TRUE){
            $checkData++;
        }
        
        if(self::checkJsonEquipements_v1_1() == TRUE){
            $checkData++;
        }
        
        if($checkData == 2){
            self::setVersionPlugin();
            return TRUE;
        } else {
            return FALSE;
        }
    }
    
    public static function etatCron(){
        return config::byKey('functionality::cron::enable', 'scan_ip');
    }
    
    public static function activationCron($_active = 0){
        if($_active == 0){ 
            log::add('scan_ip', 'info', '>  Désactivation du CRON');
        } else { 
            log::add('scan_ip', 'info', '>  Réactivation du CRON'); 
        }
        
        config::save('functionality::cron::enable', $_active, 'scan_ip');
    }
    
    public static function getVersionPlugin(){
        if (config::byKey('versionPlugin', 'scan_ip') == '') {
            config::save('versionPlugin', 1, 'scan_ip');
        }
        return config::byKey('versionPlugin', 'scan_ip');
    }
    
    public static function setVersionPlugin(){
        config::save('versionPlugin', self::$_versionPlugin, 'scan_ip');
    }
    
    public static function printVersionPlugin(){
        if(self::pluginAJour() == FALSE){
            return '<a class="btn btn-danger btn-sm" id="reloadMajPlugin" style="position:relative;top:-5px;"><i class="fas fa-exclamation-triangle"></i> Données non compatibles avec la version '.self::$_versionPlugin.' (Cliquez-ici pour corriger le problème)</a>';
        } else {
            return "<span style='color:green;'>Compatibles v".self::getVersionPlugin()."</span>";
        }
    }
    
    public static function printCheckPlugin($_statut = FALSE){
        if($_statut == FALSE){
            return "<span style='color:red;'>Pas à jour</span>";
        } else {
            return "<span style='color:green;'>A jour</span>";
        }
    }
    
//    public static function saveAllEquipement(){
//        log::add('scan_ip', 'info', '>  Mise à jour des équipements');
//        foreach (eqLogic::byType('scan_ip') as $scan_ip) {
//            try {
//                $scan_ip->save();
//            } catch (Exception $e) { }
//        } 
//    }
    
    public static function setConfigBase(){
        log::add('scan_ip', 'info', '>  Initialisation des configs');
        if (config::byKey('cron_pass', 'scan_ip') == '') {
            config::save('cron_pass', 1, 'scan_ip');
        }
        if (config::byKey('offline_time', 'scan_ip') == '') {
            config::save('offline_time', 4, 'scan_ip');
        }
    }
    
///////////////////////////////////////////////////////////////////////////////
// Release 1.1
    
    public static function majJsonCommentaires_v1_1(){
        
        log::add('scan_ip', 'info', '>  Mise à jour du JSON des commentaires');
        
        $commentMac = NULL;
        $OldCommentaires = scan_ip_json::getJson(scan_ip::$_jsonCommentairesEquipement);
        
        if(self::checkJsonCommentaires_v1_1() == FALSE){ 
            foreach ($OldCommentaires as $tempCommentMac) { 
                $commentMac[] = array(
                    array("id" => scan_ip_tools::getLastMac($tempCommentMac[0]["mac"])), 
                    array("val" => $tempCommentMac[1]["val"]));
            }
            scan_ip_json::majNetworkCommentaires($commentMac);
        }
    }
    
    public static function checkJsonCommentaires_v1_1(){
        $array = scan_ip_json::getJson(scan_ip::$_jsonCommentairesEquipement);
        if(empty($array[0][0]["mac"])){ return TRUE; }
        elseif(!empty($array[0][0]["if"]) OR empty($array)){ return TRUE; }
        else{ return FALSE; }
    }
    
    public static function majJsonEquipements_v1_1(){
        
        log::add('scan_ip', 'info', '>  Mise à jour du JSON des équipements');
        
        $array = scan_ip_json::getJson(scan_ip::$_jsonEquipement);
        
        if(self::checkJsonEquipements_v1_1() == FALSE){
            $new = array();
            foreach ($array as $key => $value) {
                $new[scan_ip_tools::getLastMac($key)] = $value;
                $new[scan_ip_tools::getLastMac($key)]["mac"] = $key;
            }
            scan_ip_json::createJsonFile(scan_ip::$_jsonEquipement, $new);
            scan_ip_maj::setVersionPlugin();
            scan_ip_scan::syncScanIp();
        }
        
    }
    
    public static function majAllEquipements_v1_1(){
        log::add('scan_ip', 'info', '>  Mise à jour des équipements');

        foreach (eqLogic::byType('scan_ip') as $scan_ip) { 
            if(!empty($scan_ip->getConfiguration("adress_mac")) AND $eqLogic->getConfiguration('type_widget', 'normal') == "normal"){
                log::add('scan_ip', 'info', '>  Maj ' . $scan_ip->getConfiguration("adress_mac"));
                $scan_ip->setConfiguration('mac_id', scan_ip_tools::getLastMac($scan_ip->getConfiguration("adress_mac")));  
                $scan_ip->save();
            } else {
                $scan_ip->setConfiguration('mac_id', ""); 
                $scan_ip->save();
            }
        }  
                    
    }
    
    public static function checkJsonEquipements_v1_1(){
        $array = scan_ip_json::getJson(scan_ip::$_jsonEquipement);
        if(!empty($array)){
            foreach ($array as $key => $value) {
                if(strlen($key) == 8){
                    return TRUE;
                } else {
                    return FALSE;
                }
            }
        } else {
            return TRUE;
        }
    }
    
///////////////////////////////////////////////////////////////////////////////
// Release Beta
    
    public static function cleanAfterUpdate($_path = NULL){ 
        log::add('scan_ip', 'info', '>  Nettoyage des données Beta');
        
        if($_path == NULL){
             $resources = __DIR__ . "/../../../../plugins/scan_ip/resources/";
        } else {
            $resources = $_path."plugins/scan_ip/resources/";
        }
       
        if(@file_exists($resources . "ieee-oui.txt") == TRUE){
            unlink($resources . "ieee-oui.txt");
        }
        if(@file_exists($resources . "ieee-oui.txt") == TRUE){
            unlink($resources . "ieee-oui.txt.bak");
        }
        if(@file_exists($resources . "ieee-iab.txt") == TRUE){
            unlink($resources . "ieee-iab.txt");
        }
        if(@file_exists($resources . "ieee-iab.txt.bak") == TRUE){
            unlink($resources . "ieee-iab.txt.bak");
        }
        if(@file_exists($resources . "install_scan_ip.sh") == TRUE){
            unlink($resources . "install_scan_ip.sh");
        }
        if(@file_exists($resources . "upload.oui.sh") == TRUE){
            unlink($resources . "upload.oui.sh");
        }
        if(@file_exists($resources . "Autre") == TRUE){
            unlink($resources . "Autre");
        }
        
        if($_path == NULL){
             $bridge = __DIR__ . "/../../../../plugins/scan_ip/core/bridges/";
        } else {
            $bridge = $_path."plugins/scan_ip/core/bridges/";
        }
        if(@file_exists($bridge . "template.php") == TRUE){
            unlink($bridge . "template.php");
        }
        
        if($_path == NULL){
             $json = __DIR__ . "/../../../../plugins/scan_ip/core/json/";
        } else {
            $json = $_path."plugins/scan_ip/core/json/";
        }
        if(@file_exists($json . "macaddress.temp") == TRUE){
            unlink($json . "macaddress.temp");
        }
        if(@file_exists($json . "serialize.temp") == TRUE){
            unlink($json . "serialize.temp");
        }
        if(@file_exists($json . "bridges.json") == TRUE){
            unlink($json . "bridges.json");
        }
        
        if($_path == NULL){
             $subPlugs = __DIR__ . "/../../../../plugins/scan_ip/core/subPlugs";
        } else {
            $subPlugs = $_path."plugins/scan_ip/core/subPlugs/";
        }
        if(@is_dir($subPlugs)){
            if(@file_exists($subPlugs . "/broadlink.php") == TRUE){
                unlink($subPlugs . "/broadlink.php");
            }
            if(@file_exists($subPlugs . "/googlecast.php") == TRUE){
                unlink($subPlugs . "/googlecast.php");
            }
            if(@file_exists($subPlugs . "/template.php") == TRUE){
                unlink($subPlugs . "/template.php");
            }
            if(@file_exists($subPlugs . "/xiaomihome.php") == TRUE){
                unlink($subPlugs . "/xiaomihome.php");
            }
        
            rmdir($subPlugs);
        }
        
    }
    
}
