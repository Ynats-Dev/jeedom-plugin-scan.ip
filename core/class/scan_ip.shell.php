<?php

/**
 * Description of scan_ip
 *
 * @author Ynats
 */

require_once __DIR__ . "/../../../../plugins/scan_ip/core/class/scan_ip.require_once.php";

class scan_ip_shell extends eqLogic {
    
    public static function printShell($_shell){
        $output = shell_exec($_shell);
        echo "<pre style='background-color: #1b2426 !important; color: white !important;'>".$output."</pre>";
    }
    
    public static function getIpRoute(){
        log::add('scan_ip', 'debug', 'getIpRoute :. ' . __('Lancement', __FILE__));
        return exec("ip route show default | awk '/default/ {print $3}'");
    }
    
    public static function arpScanShell($_subReseau = NULL){
        log::add('scan_ip', 'debug', 'scan_ip_shell::arpScanShell :. ' . __('Lancement', __FILE__));
        
        $time = time();
        $return = array();
        
        $retry = config::byKey('add_retry_scan', 'scan_ip', 3);
        
        exec('sudo arp-scan --numeric --retry=' . $retry .  ' --interval=2 --timeout=300 --interface=' . $_subReseau . ' --localnet --ouifile=' . scan_ip::$_file_oui, $output);        
        
        foreach ($output as $scanLine) {
            // Seules les lignes de résultat commencent par une IP suivie d'une MAC ; l'en-tête
            // ("Interface: eth0, type: EN10MB, MAC: ..., IPv4: ...") contient aussi une MAC et une IP
            if (preg_match('/^\s*\d{1,3}(\.\d{1,3}){3}\s+([0-9a-fA-F]{2}:){5}[0-9a-fA-F]{2}/', $scanLine)) {     
                    preg_match(scan_ip_tools::getRegex("ip_v4"), $scanLine, $sortIp); 
                    preg_match(scan_ip_tools::getRegex("mac"), $scanLine, $sortMac);
                    
                    $mac = strtoupper($sortMac[0]);
                    
                    $id_mac = scan_ip_tools::getLastMac($mac);
                    
                    $return[$id_mac]["mac"] = $mac;
                    $return[$id_mac]["equipement"] = self::getEquipement($scanLine);
                    $return[$id_mac]["ip_v4"] = $sortIp[0];
                    $return[$id_mac]["time"] = $time;
            }
        }
        return $return;
    }
    
    // Durée estimée d'un arp-scan --localnet sur une interface (secondes) :
    // nombre d'adresses du sous-réseau x intervalle (2 ms) x tentatives, plus le délai de réponse
    public static function estimateArpScanDuration($_interface){
        $prefix = 24;
        $output = array();
        exec('ip -o -f inet addr show ' . escapeshellarg($_interface) . ' 2>/dev/null', $output);
        foreach ($output as $line) {
            if (preg_match('/inet [0-9.]+\/([0-9]{1,2})/', $line, $match)) {
                $prefix = intval($match[1]);
                break;
            }
        }
        $prefix = max(8, min(32, $prefix));
        $hosts = max(1, pow(2, 32 - $prefix) - 2);
        $retry = max(1, intval(config::byKey('add_retry_scan', 'scan_ip', 3)));
        return intval(ceil($hosts * 0.002 * $retry + 1));
    }
    
    public static function arpVersion(){
        log::add('scan_ip', 'debug', 'arpVersion :. ' . __('Lancement', __FILE__));
        $exec = exec('sudo arp-scan -V 2>&1',$output, $return_var);
        if($return_var == 0) { 
            foreach ($output as $searchVersion) {
                if(preg_match("(arp-scan )", $searchVersion)) { return substr($searchVersion,9); }
            }    
        } else {
          log::add('scan_ip', 'error', 'sudo: arp-scan: command not found');
          return("arp-scan not found");
        }
    }
    
    public static function getEquipement($_scanLine){
        $equipement = preg_replace(scan_ip_tools::getRegex("ip_v4"), "", $_scanLine);
        $equipement = preg_replace(scan_ip_tools::getRegex("mac"), "", $equipement);
        $equipement = preg_replace(scan_ip_tools::getRegex("()"), "", $equipement);
        $equipement = trim($equipement);
        if($equipement == ""){ return "..."; }
        else { return $equipement; }
    }
    
    public static function getInfoJeedom($_ipRoute){ 
        log::add('scan_ip', 'debug', 'getInfoJeedom :. ' . __('Lancement', __FILE__));
        
        $plageRouteur = scan_ip_tools::getPlageIp($_ipRoute);
        exec('sudo ip a', $list);

        foreach ($list as $i => $value) {
            // $return["plage_route"] = NULL; 
            if(preg_match(scan_ip_tools::getRegex("ip_v4"), $value) AND preg_match("(".$plageRouteur.")", $value)) {
                $return["ip_v4"] = trim(str_replace("inet", "", explode("/",$value)[0]));
                // Adresse MAC : ligne "link/ether" de la même interface, qui n'est pas forcément
                // juste au-dessus (des lignes "altname ..." peuvent s'intercaler)
                $return["mac"] = "";
                for ($j = $i - 1; $j >= 0; $j--) {
                    if (strpos($list[$j], "link/ether") !== FALSE AND preg_match(scan_ip_tools::getRegex("mac"), $list[$j], $macJeedom)) {
                        $return["mac"] = strtoupper($macJeedom[0]);
                        break;
                    }
                    if (preg_match('/^\d+:\s/', $list[$j])) { break; } // début du bloc de l'interface atteint
                }
                $return["name"] = config::byKey('name');
                $return["hostname"] = (string) gethostname();
                $return["time"] = time();
                $return["record"] = scan_ip_tools::getInstallJeedom();
                break;
            }
        }
        return $return;
    }
    
    public static function scan_ip_iproute2($_ipRoute){
        exec('sudo ip a', $list);
        $i = 0;
        $return["name_plage_route"] = NULL;
        
        foreach ($list as $value) {
            if(preg_match(scan_ip_tools::getRegex("iproute2"), $value)){ 
                $name = trim(explode(":", $value)[1]);
                $i++; 
                $return[$i]["name"] = $name; 
            }
            
            if(preg_match(scan_ip_tools::getRegex("ip_v4"), $value) AND empty($return[$i]["ip_v4"])) {
                $return[$i]["ip_v4"] = scan_ip_tools::getPlageIp(trim(str_replace("inet", "", explode("/",$value)[0]))).".*"; 
                if(!empty($_ipRoute) AND !empty($return[$i]["ip_v4"]) AND scan_ip_tools::getPlageIp($_ipRoute) == scan_ip_tools::getPlageIp($return[$i]["ip_v4"])) {
                    $return["name_plage_route"] = $name;
                }   
            }
        
        }
        return $return;
    }
    
    public static function scan_ip_net_tools($_ipRoute){
        exec('sudo ifconfig', $list);
        $i = 0;
        $return["name_plage_route"] = NULL;
        
        foreach ($list as $value) {
            if(preg_match(scan_ip_tools::getRegex("net-tools"), $value)){ 
                $name = trim(explode(":", $value)[0]);
                $i++; 
                $return[$i]["name"] = $name; 
            }
            if(preg_match(scan_ip_tools::getRegex("ip_v4"), $value) AND empty($return[$i]["ip_v4"])) { 
                $return[$i]["ip_v4"] = scan_ip_tools::getPlageIp(trim(str_replace("inet", "", explode("netmask",$value)[0]))).".*"; 
                if(!empty($_ipRoute) AND !empty($return[$i]["ip_v4"]) AND scan_ip_tools::getPlageIp($_ipRoute) == scan_ip_tools::getPlageIp($return[$i]["ip_v4"])) {
                    $return["name_plage_route"] = $name;
                }   
            }
        }
        return $return;
    }
    
    public static function scanSubReseau($_ipRoute = NULL){ 
        log::add('scan_ip', 'debug', 'scanSubReseau :. ' . __('Lancement', __FILE__));
        
        if($_ipRoute != NULL){
            $ipRoute = $_ipRoute;
        } else {
            $ipRoute = self::getIpRoute();
        }
        
        $return = self::scan_ip_iproute2($ipRoute);
        
        if(empty($return[0])){
            $return = self::scan_ip_net_tools($ipRoute); 
        }
        
        return $return;
    }
    
    public static function getSubReseauEnable($_ipRoute = NULL) {
        log::add('scan_ip', 'debug', 'getSubReseauEnable :. ' . __('Lancement', __FILE__));
        
        $a = $return["subReseauEnable"] = 0;
        
        $allReseau = self::scanSubReseau($_ipRoute);
        
        $return["name_plage_route"] = $allReseau["name_plage_route"];
        unset($allReseau["name_plage_route"]);
        
        foreach ($allReseau as $key => $sub) { 
            $return["subReseau"][$a]["enable"] = config::byKey('sub_enable_'.md5($sub["name"]), 'scan_ip', 0);
            $return["subReseau"][$a]["name"] = $sub["name"];
            $return["subReseauEnable"] =  $return["subReseau"][$a]["enable"] + $return["subReseauEnable"];
            $a++; 
        }
        
        return $return; 
    }
    
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
# GESTION DES SOUS-RESEAUX
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////      
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
# DEPENDANCES
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////      
  
    public static function dependancy_arp_scan(){
        if (exec('which arp-scan | wc -l') == 0 OR exec("dpkg --get-selections | grep -v deinstall | grep -E 'arp-scan' | wc -l") == 0) {
            $return[0] = FALSE;
            $return[1] = "<span style='color:red'>" . __('En erreur', __FILE__) . "</span>";
        } else {
            $return[0] = TRUE;
            $return[1] = "<span style='color:green'>" . __('Validé', __FILE__) . "</span>";
        }
        return $return;
    }
    
    public static function dependancy_etherwake(){
        if (exec('which etherwake | wc -l') == 0 OR exec("dpkg --get-selections | grep -v deinstall | grep -E 'etherwake' | wc -l") == 0) {
            $return[0] = FALSE;
            $return[1] = "<span style='color:red'>" . __('En erreur', __FILE__) . "</span>";
        } else {
            $return[0] = TRUE;
            $return[1] = "<span style='color:green'>" . __('Validé', __FILE__) . "</span>";
        }
        return $return;
    }
    
    public static function dependancy_wakeonlan(){
        if (exec('which wakeonlan | wc -l') == 0 OR exec("dpkg --get-selections | grep -v deinstall | grep -E 'wakeonlan' | wc -l") == 0) {
            $return[0] = FALSE;
            $return[1] = "<span style='color:red'>" . __('En erreur', __FILE__) . "</span>";
        } else {
            $return[0] = TRUE;
            $return[1] = "<span style='color:green'>" . __('Validé', __FILE__) . "</span>";
        }
        return $return;
    }
    
    public static function dependancy_net_tools(){
        if (exec('which ifconfig | wc -l') == 0 OR exec("dpkg --get-selections | grep -v deinstall | grep -E 'net-tools' | wc -l") == 0) {
            $return[0] = FALSE;
            $return[1] = "<span style='color:red'>" . __('En erreur', __FILE__) . "</span>";
        } else {
            $return[0] = TRUE;
            $return[1] = "<span style='color:green'>" . __('Validé', __FILE__) . "</span>";
        }
        return $return;
    }
    
    public static function dependancy_iproute2(){
        if (exec('which ip | wc -l') == 0 OR exec("dpkg --get-selections | grep -v deinstall | grep -E 'iproute2' | wc -l") == 0) {
            $return[0] = FALSE;
            $return[1] = "<span style='color:red'>" . __('En erreur', __FILE__) . "</span>";
        } else {
            $return[0] = TRUE;
            $return[1] = "<span style='color:green'>" . __('Validé', __FILE__) . "</span>";
        }
        return $return;
    }
    
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
# DEPENDANCES
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////  
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
# WAKE ON LAN
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////    
  
    public static function wakeOnLan($_mac){
        log::add('scan_ip', 'debug', 'wakeOnLan :. wakeonlan '.$_mac);
        shell_exec("sudo wakeonlan ".$_mac);
        log::add('scan_ip', 'debug', 'etherwake :. etherwake '.$_mac);
        shell_exec("sudo /usr/sbin/etherwake ".$_mac);
    }
    
    public static function wakeOnLanByCmd($_mac){ 
        log::add('scan_ip', 'debug', 'wakeOnLanByCmd :. ' . __('Lancement', __FILE__));
        if(preg_match(scan_ip_tools::getRegex("mac"), $_mac)){
            self::wakeOnLan($_mac);
        }
    }
      
}
