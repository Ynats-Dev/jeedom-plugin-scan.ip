<?php

/**
 * Description of scan_ip
 *
 * @author Ynats
 */

require_once __DIR__ . "/../../../../plugins/scan_ip/core/class/scan_ip.require_once.php";

class scan_ip_api_mac_vendor extends eqLogic {

    public static $_jsonMajVendorApi = __DIR__ . "/../../../../plugins/scan_ip/data/json/majVendorApi";
    public static $_jsonRoulement = 3600 * 7; // Délai (7 heures) avant de retenter un OUI introuvable
    public static $_maxLookupsByScan = 3; // Nombre max de recherches en ligne par scan (évite de bloquer le cron)
    public static $_httpTimeout = 3; // Timeout (secondes) de chaque appel HTTP

    private static $_lookupsDone = 0;
    private static $_lastCallTime = 0;

    public static function get_MacVendor($_mac){
        log::add('scan_ip', 'debug', 'get_MacVendor :. '.__('Lancement', __FILE__) . ' ' . $_mac);

        // Le fabricant ne dépend que de l'OUI (3 premiers octets)
        $oui = self::getOui($_mac);
        if($oui == NULL){
            return "...";
        }

        // Limitation par scan : les adresses restantes seront traitées aux scans suivants
        if(self::$_lookupsDone >= self::$_maxLookupsByScan){
            log::add('scan_ip', 'debug', 'get_MacVendor :. '.__('Limite de recherches atteinte pour ce scan', __FILE__));
            return "...";
        }

        $now = time();
        $jsonMajVendorApi = scan_ip_json::getJson(self::$_jsonMajVendorApi);
        if(!is_array($jsonMajVendorApi)){
            $jsonMajVendorApi = array();
        }

        // OUI déjà recherché sans succès récemment
        if(!empty($jsonMajVendorApi[$oui]) AND ($now - $jsonMajVendorApi[$oui]) < self::$_jsonRoulement){
            return "...";
        }

        self::$_lookupsDone++;

        $return = self::get_MacvendorsCom($oui);
        if($return == NULL){
            $return = self::get_MacvendorsCo($oui);
        }

        if($return != NULL){
            unset($jsonMajVendorApi[$oui]);
            scan_ip_json::createJsonFile(self::$_jsonMajVendorApi, $jsonMajVendorApi);
            return $return;
        } else {
            $jsonMajVendorApi[$oui] = $now;
            scan_ip_json::createJsonFile(self::$_jsonMajVendorApi, $jsonMajVendorApi);
            return "...";
        }
    }

    public static function getOui($_mac){
        if(!preg_match(scan_ip_tools::getRegex("mac"), $_mac)){
            return NULL;
        }
        return strtoupper(substr($_mac, 0, 8));
    }

    private static function httpGet($_url){
        // Respect du rate-limit des API gratuites (1 requête / seconde)
        $wait = 1 - (microtime(true) - self::$_lastCallTime);
        if($wait > 0){
            usleep((int) ($wait * 1000000));
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, self::$_httpTimeout);
        curl_setopt($ch, CURLOPT_TIMEOUT, self::$_httpTimeout);
        $body = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        unset($ch);

        self::$_lastCallTime = microtime(true);

        return array("code" => $code, "body" => ($body === FALSE ? NULL : $body));
    }

////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
# TESTS SUR DIFFERENTES API
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    public static function get_MacvendorsCom($_mac){
        log::add('scan_ip', 'debug', 'get_MacvendorsCom :. '.__('Lancement de la recherche', __FILE__));

        // Réponse en texte brut si trouvé (200), JSON {"errors":...} sinon
        $response = self::httpGet("https://api.macvendors.com/" . urlencode($_mac));
        $vendor = trim((string) $response["body"]);

        if($response["code"] == 200 AND $vendor != "" AND $vendor[0] != "{"){
            log::add('scan_ip', 'debug', 'get_MacvendorsCom :. '.__('Trouvé', __FILE__).' '.$vendor);
            return $vendor;
        }

        log::add('scan_ip', 'debug', 'get_MacvendorsCom :. '.__('Pas Trouvé', __FILE__) . ' (HTTP ' . $response["code"] . ')');
        return NULL;
    }

    public static function get_MacvendorsCo($_mac) {
        log::add('scan_ip', 'debug', 'get_MacvendorsCo :. '.__('Lancement de la recherche', __FILE__));

        $response = self::httpGet("https://macvendors.co/api/" . urlencode($_mac));
        $json = ($response["code"] == 200) ? @json_decode($response["body"], TRUE) : NULL;

        if(!empty($json["result"]["company"])){
            log::add('scan_ip', 'debug', 'get_MacvendorsCo :. '.__('Trouvé', __FILE__).' '.$json["result"]["company"]);
            return $json["result"]["company"];
        }

        log::add('scan_ip', 'debug', 'get_MacvendorsCo :. '.__('Pas Trouvé', __FILE__) . ' (HTTP ' . $response["code"] . ')');
        return NULL;
    }

}
