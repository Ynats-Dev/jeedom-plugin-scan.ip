<?php

/**
 * Description of scan_ip
 *
 * @author Ynats
 */

require_once __DIR__ . "/../../../../plugins/scan_ip/core/class/scan_ip.require_once.php";

class scan_ip_tools extends eqLogic {

    public static function arrayCompose($_arrayOld, $_arrayNew){
        if(is_array($_arrayNew)){
            if($_arrayOld == NULL){ 
                return $_arrayNew; 
            } 
            else { 
                return array_merge($_arrayOld, $_arrayNew); 
            }
        } else {
            if(($_arrayOld == NULL OR empty($_arrayOld[0])) AND $_arrayNew != NULL){ 
                $array = array();
                array_push($array, $_arrayNew); 
                return $array;
            } 
            elseif($_arrayNew != NULL){  
                array_push($_arrayOld, $_arrayNew);  
                return array_unique($_arrayOld);
            }
            else {   
                return $_arrayOld;
            }
        } 
    }
    
    public static function isLockProcess(){
       if(file_exists(scan_ip::$_file_lock)) {
           return TRUE;
       } else {
           return FALSE;
       }
    }
    
    public static function lockProcess(){
        if(file_exists(scan_ip::$_file_lock)) {
            // Le scan rafraîchit le verrou à chaque étape : il n'est périmé que si plus rien ne bouge depuis 10 minutes
            if((time() - filemtime (scan_ip::$_file_lock)) > 600){
                // Verrou périmé : on le remplace par un nouveau
                self::unlockProcess();
            } else {
               return FALSE; 
            }
        }
        fopen(scan_ip::$_file_lock, "w");
        chmod(scan_ip::$_file_lock, 0777);
        return TRUE;
    }
    
    // Avancement du scan, lu par l'interface pendant la synchronisation
    // $_stepEnd / $_eta : pourcentage visé en fin d'étape et durée estimée de l'étape (en secondes),
    // pour que l'interface fasse avancer la barre pendant une étape longue (arp-scan)
    public static function setScanProgress($_percent, $_label, $_state = "running", $_stepEnd = NULL, $_eta = NULL){
        if (file_exists(scan_ip::$_file_lock)) {
            @touch(scan_ip::$_file_lock);
        }
        $previous = self::getScanProgress();
        $start = ($_percent == 0 OR empty($previous["start"])) ? time() : $previous["start"];
        $progress = array(
            "percent" => max(0, min(100, intval($_percent))),
            "label" => $_label,
            "state" => $_state,
            "start" => $start,
            "time" => time(),
            "stepStart" => time(),
            "stepEnd" => ($_stepEnd !== NULL) ? max(0, min(100, intval($_stepEnd))) : NULL,
            "eta" => ($_eta !== NULL) ? max(1, intval($_eta)) : NULL,
        );
        cache::set('scan_ip::scanProgress', json_encode($progress), 900);
    }
    
    public static function getScanProgress(){
        $progress = json_decode(cache::byKey('scan_ip::scanProgress')->getValue(''), true);
        if (!is_array($progress)) {
            $progress = array("percent" => 0, "label" => "", "state" => "idle", "start" => 0, "time" => 0);
        }
        $progress["locked"] = self::isLockProcess();
        $progress["now"] = time();
        return $progress;
    }
    
    public static function unlockProcess(){
        if(file_exists(scan_ip::$_file_lock)) {
            unlink(scan_ip::$_file_lock);
            return TRUE;
        } else {
            return FALSE;
        }
    }
    
    public static function getDateFile($_filename){
        if (@file_exists($_filename)) {
            return date("d/m/Y", filemtime($_filename));
        } else {
            return NULL;
        }
        
    }
    
    public static function getRegex($_type){
        if($_type == "ip_v4") { return "/\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}/"; }
        elseif($_type == "mac") { return "/([a-fA-F0-9]{2}:){5}[a-fA-F0-9]{2}/"; }
        elseif($_type == "iproute2") { return "/\d{1,3}\:\s(.+)\:\s/"; }
        elseif($_type == "net-tools") { return "/(.+)\:\s(flags)/"; }
        elseif($_type == "()") { return "/(\((.*?)\))/"; }
        else { return NULL; }
    }
    
    public static function getPlageIp($_ip = NULL){
        if($_ip != NULL){
            @list($a, $b, $c) = explode('.', $_ip);
            return $a . "." . $b . "." . $c;
        } else {
            return NULL;
        }
    }
    
    public static function getLastMac($_mac = NULL){
        if($_mac != NULL){
            @list($a, $b, $c, $d, $e, $f) = explode(':', $_mac);
            return $d . ":" . $e . ":" . $f;
        } else {
            return NULL;
        }
    }
    
    public static function isOffline($_expire, $_time){
        if($_expire == NULL){ $_expire = 4; }
        $expire = time() - (60 * $_expire);
        if($expire <= $_time){ return 0; } 
        else { return 1; }
    }
    
    public static function excludeSubReseau($_string){
        if($_string == ""){ return FALSE; }
        elseif($_string == "lo"){ return FALSE; }
        elseif(preg_match('/(tun)[0-9]*/', $_string)){ return FALSE; }
        else { return TRUE; }
    }
    
    public static function vueSubTitle($_titre, $_from = "devices", $_icon = NULL){
        
        if ($_from == "config") { $cols = "col-lg-offset-1 col-lg-9"; $extra = " scanip-section--config"; }
        else { $cols = "col-sm-offset-1 col-sm-8"; $extra = ""; }
        
        $icon = ($_icon != NULL) ? '<i class="fas '.$_icon.'"></i> ' : '';
        
        echo '  <div class="form-group">
                    <div class="'.$cols.'">
                        <div class="scanip-section'.$extra.'">'. $icon . '<span>'. $_titre .'</span></div>
                    </div>
                </div>';
    }
    
    // Échappe un texte avant de l'insérer dans du HTML (noms, fabricants, commentaires...)
    public static function escape($_string){
        return htmlspecialchars((string) $_string, ENT_QUOTES, 'UTF-8');
    }
    
    // Compteur de la barre d'outils des modales : rien n'est affiché quand il vaut 0
    // $_filter : critère appliqué au tableau au clic ("" = tout afficher, NULL = compteur non cliquable)
    public static function getStat($_nb, $_icon, $_label, $_class = "", $_filter = NULL){
        if (intval($_nb) <= 0) {
            return "";
        }
        $class = ($_class != "") ? " " . $_class : "";
        $attr = "";
        if ($_filter !== NULL) {
            $class .= " scanip-stat-filter";
            $attr = ' data-filter="' . self::escape($_filter) . '" title="' . __('Cliquer pour filtrer le tableau', __FILE__) . '"';
        }
        return '<span class="scanip-stat' . $class . '"' . $attr . '>' . $_icon . ' <b>' . intval($_nb) . '</b> ' . $_label . '</span>';
    }
    
    public static function getStatusDot($_online, $_title = ""){
        $class = ($_online == 1) ? "is-on" : "is-off";
        return '<span class="scanip-dot '.$class.'" title="'.$_title.'"></span>';
    }
    
    public static function getCycle($_width, $_color){
        $return = '<div style="margin: 0;">';
        $return .= '<div style="width: '.$_width.'; height: '.$_width.'; border-radius: '.$_width.'; background: '.$_color.';"></div>';
        $return .= '</div>';
        return $return;
    }
    
    public static function printArray($_array){
        echo "<pre style='background-color: #1b2426 !important; color: white !important;'>".print_r($_array, true)."</pre>";
    }
    
    public static function printFileOuiExist(){
        return self::printVendorFileStatus(scan_ip::$_file_oui, "oui.txt");
    }
    
    public static function printFileIatExist(){
        return self::printVendorFileStatus(scan_ip::$_file_iab, "iab.txt");
    }
    
    // Date de la dernière mise à jour des fichiers constructeurs (téléchargement réussi, sinon date du fichier oui.txt)
    public static function getVendorFilesLastUpdate(){
        $last = intval(config::byKey('vendor_files_last_update', 'scan_ip', 0));
        if ($last > 0) {
            return date("d/m/Y H:i", $last);
        }
        if (@file_exists(scan_ip::$_file_oui)) {
            return date("d/m/Y H:i", filemtime(scan_ip::$_file_oui));
        }
        return "…";
    }
    
    public static function printVendorFileStatus($_file, $_name){
        if(@file_exists($_file) == TRUE){
            return '<span class="scanip-vendor-file" data-file="' . $_name . '" style="color:green">' . $_name . ' (' . __('Installé', __FILE__) . ' · ' . self::getDateFile($_file) . ')</span>';
        } else {
            return '<span class="scanip-vendor-file" data-file="' . $_name . '" style="color:var(--al-danger-color, red)">' . $_name . ' (' . __('Manquant', __FILE__) . ')</span>';
        }
    }
    
    // Télécharge les registres de l'IEEE et régénère oui.txt et iab.txt au format arp-scan
    // (<préfixe><TAB><constructeur>). Un fichier n'est remplacé que si le résultat est complet.
    public static function updateVendorFiles(){
        $sources = array(
            "oui.txt" => array("url" => "https://standards-oui.ieee.org/oui/oui.txt", "file" => scan_ip::$_file_oui, "min" => 10000, "type" => "oui"),
            "iab.txt" => array("url" => "https://standards-oui.ieee.org/iab/iab.txt", "file" => scan_ip::$_file_iab, "min" => 1000, "type" => "iab"),
        );
        $result = array();
        foreach ($sources as $name => $source) {
            $result[$name] = array("ok" => FALSE, "count" => 0, "message" => "", "date" => "");
            $content = self::downloadUrl($source["url"]);
            if ($content === FALSE) {
                $result[$name]["message"] = __('Téléchargement impossible', __FILE__);
                log::add('scan_ip', 'error', 'updateVendorFiles :. ' . $name . ' : ' . __('Téléchargement impossible', __FILE__) . ' (' . $source["url"] . ')');
                continue;
            }
            $lines = ($source["type"] == "oui") ? self::parseOuiRegistry($content) : self::parseIabRegistry($content);
            unset($content);
            if (count($lines) < $source["min"]) {
                $result[$name]["message"] = __('Fichier reçu incomplet, ancien fichier conservé', __FILE__);
                log::add('scan_ip', 'error', 'updateVendorFiles :. ' . $name . ' : ' . count($lines) . ' ' . __('entrées seulement, ancien fichier conservé', __FILE__));
                continue;
            }
            $header = "# " . $name . " -- Ethernet vendor " . strtoupper($source["type"]) . " file for arp-scan\n"
                . "#\n"
                . "# <" . strtoupper($source["type"]) . "><TAB><Vendor>\n"
                . "#\n"
                . "# This file was automatically generated by Scan.Ip at " . date("Y-m-d H:i:s") . "\n"
                . "# using data from " . $source["url"] . "\n"
                . "#\n";
            $tmp = $source["file"] . ".tmp";
            if (@file_put_contents($tmp, $header . implode("\n", $lines) . "\n") === FALSE OR !@rename($tmp, $source["file"])) {
                @unlink($tmp);
                $result[$name]["message"] = __('Écriture du fichier impossible', __FILE__);
                log::add('scan_ip', 'error', 'updateVendorFiles :. ' . $name . ' : ' . __('Écriture du fichier impossible', __FILE__));
                continue;
            }
            @chmod($source["file"], 0664);
            $result[$name] = array("ok" => TRUE, "count" => count($lines), "message" => "", "date" => self::getDateFile($source["file"]));
            log::add('scan_ip', 'info', 'updateVendorFiles :. ' . $name . ' : ' . count($lines) . ' ' . __('entrées', __FILE__));
        }
        clearstatcache();
        // Date de la dernière mise à jour complète : sert à la mise à jour automatique (cronDaily).
        // En cas d'échec partiel elle n'est pas enregistrée, pour réessayer le lendemain.
        if ($result["oui.txt"]["ok"] AND $result["iab.txt"]["ok"]) {
            config::save('vendor_files_last_update', time(), 'scan_ip');
        }
        return $result;
    }
    
    public static function downloadUrl($_url){
        $userAgent = "Mozilla/5.0 (compatible; Scan.Ip plugin for Jeedom)";
        if (function_exists('curl_init')) {
            $ch = curl_init($_url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 20);
            curl_setopt($ch, CURLOPT_TIMEOUT, 180);
            curl_setopt($ch, CURLOPT_USERAGENT, $userAgent);
            $content = curl_exec($ch);
            $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            if (PHP_VERSION_ID < 80000) { curl_close($ch); } // inutile (et dépréciée) depuis PHP 8
            return ($content !== FALSE AND $code == 200) ? $content : FALSE;
        }
        $context = stream_context_create(array("http" => array("timeout" => 180, "user_agent" => $userAgent)));
        return @file_get_contents($_url, FALSE, $context);
    }
    
    // "286FB9     (base 16)		Nokia Shanghai Bell Co., Ltd."  =>  "286FB9<TAB>Nokia Shanghai Bell Co., Ltd."
    public static function parseOuiRegistry($_content){
        $lines = array();
        foreach (preg_split('/\r\n|\r|\n/', $_content) as $line) {
            if (preg_match('/^\s*([0-9A-Fa-f]{6})\s+\(base 16\)\s+(.*\S)\s*$/', $line, $m)) {
                $lines[] = strtoupper($m[1]) . "\t" . $m[2];
            }
        }
        return $lines;
    }
    
    // "40-D8-55 (hex)" puis "0D7000-0D7FFF (base 16)  Avant Technologies"  =>  "40D8550D7<TAB>Avant Technologies"
    public static function parseIabRegistry($_content){
        $lines = array();
        $prefix = NULL;
        foreach (preg_split('/\r\n|\r|\n/', $_content) as $line) {
            if (preg_match('/^\s*([0-9A-Fa-f]{2})-([0-9A-Fa-f]{2})-([0-9A-Fa-f]{2})\s+\(hex\)/', $line, $m)) {
                $prefix = strtoupper($m[1] . $m[2] . $m[3]);
            } elseif ($prefix !== NULL AND preg_match('/^\s*([0-9A-Fa-f]{3})000-[0-9A-Fa-f]{3}FFF\s+\(base 16\)\s+(.*\S)\s*$/', $line, $m)) {
                $lines[] = $prefix . strtoupper($m[1]) . "\t" . $m[2];
                $prefix = NULL;
            }
        }
        return $lines;
    }
    
    public static function getIpv4ToSort($_string){
        $a = explode(".", $_string);
        $b = "";
        foreach ($a as $key => $value) {
            if($value < 10){
                $b .= "00".$value;
            }
            elseif($value < 100){
                $b .= "0".$value;
            }
            else {
                $b .= $value;
            }
        }
        return $b;
    }
    
    public static function getCleanForSortTable($_string, $_type = NULL){
        if (preg_match(self::getRegex("ip_v4"), $_string)) { 
            return self::getIpv4ToSort($_string);
        } 
        elseif($_type == "date"){
            $tmp = explode(" ", $_string);
            $tmp1 = explode("/", $tmp[0]);
            if(empty($tmp1)){
                $tmp2 = str_replace(":", "", $tmp[1]);
                return $tmp1[2].$tmp1[1].$tmp1[0].$tmp2;
            } else {
                return NULL;
            }
        }
        if($_string == "..."){
            if($_type == "int"){
                return 9999999999999;
            } elseif($_type == "string"){
                return "ZZZZZZZZZZZZZZZ";
            } elseif($_type == "date"){
                return 0;
            } else{
                return 9999999999999;
            }
        }
        else {
            return strtolower($_string);
        }
    }
    
    public static function cleanArrayEquipement($_array){
        $return = NULL;
        foreach ($_array as $mac => $scanLine) {
            if(!empty($scanLine["equipement"]) AND !empty($mac)){
                $return[$mac] = $scanLine;
            }
        }
        return $return;
    }
    
    public static function showEquCadence() {
        if (scan_ip::getConfigMode() == "normal") {
            echo " style='display:none;'";
        } else {
            echo "";
        }
    }
    
    public static function printInputSubConfig(){
        log::add('scan_ip', 'debug', 'printInputSubConfig :. ' . __('Lancement', __FILE__));
        $return = "";
        
        $allReseau = scan_ip_shell::scanSubReseau();

        unset($allReseau["name_plage_route"]);
        
        foreach ($allReseau as $sub) {
            // Interfaces sans adresse IPv4 (veth Docker, cartes non connectées...) : rien à scanner, on ne les affiche pas
            if(self::excludeSubReseau($sub["name"]) == TRUE AND !empty($sub["ip_v4"])) {
                $return .= '<div class="form-group">';
                $return .= '<label class="col-lg-4 control-label">'. __('Scanner le sous-réseau', __FILE__) . ' ['.$sub["name"].']</label>';
                $return .= '<div class="col-lg-5">';
                $return .= '<label class="scanip-subnet"><input type="checkbox" class="configKey" data-l1key="sub_enable_'.md5($sub["name"]).'"><span>'.$sub["ip_v4"].'</span></label>';
                $return .= '</div>';
                $return .= '</div>';
            }
        }
        return $return;
    }
    
    public static function printDate($_time = NULL){
        if($_time == NULL OR $_time == "..."){
            return "...";
        } else {
             return date("d/m/Y H:i:s", $_time);
        } 
    }
    
    public static function getInstallJeedom(){
        
        $test1 = __DIR__ . "/../../../../robots.txt";
        $test2 = __DIR__ . "/../../../../apple-touch-icon.png";
        $test3 = __DIR__ . "/../../../../COPYRING";
        
        if (file_exists($test1)) { $return[] = filemtime($test1); }
        if (file_exists($test2)) { $return[] = filemtime($test2); }
        if (file_exists($test3)) { $return[] = filemtime($test3); }
        
        asort($return);
        return $return[0];
    }

}
