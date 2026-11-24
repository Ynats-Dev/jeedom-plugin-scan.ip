<?php

/**
 * Bridges de Scan.Ip : moteur de règles
 *
 * Un bridge est une règle qui désigne un champ contenant une ip dans un autre plugin
 * (configuration d'un équipement, logicalId, configuration d'une commande ou configuration du plugin).
 * Lorsqu'une adresse MAC associée change d'ip, la règle met ce champ à jour.
 *
 * Les règles fournies avec le plugin sont dans data/json/bridges.json (catalogue).
 * Les règles créées ou modifiées par l'utilisateur sont stockées dans la configuration "bridges_rules".
 * Quelques cas très spécifiques restent en PHP (source "php") dans core/class/bridges/.
 *
 * Format d'une association (configuration plug_element_plugin_N d'un équipement scan_ip) :
 *  - eqLogic      : plugin|idEquipement|cléConfiguration
 *  - logicalId    : plugin|idEquipement|@logicalId
 *  - cmd          : plugin|idEquipement|cmd<idCommande>:cléConfiguration
 *  - pluginConfig : plugin|config|cléConfiguration
 *  - php          : format défini par la classe du bridge (plugin|id[|champ])
 *
 * @author Ynats
 */

require_once __DIR__ . "/../../../../plugins/scan_ip/core/class/scan_ip.require_once.php";

class scan_ip_bridges extends eqLogic {

    public static $_jsonBridges = __DIR__ . "/../../../../plugins/scan_ip/data/json/bridges.json";
    public static $_folderBridges = __DIR__ . "/../../../../plugins/scan_ip/core/class/bridges/";
    public static $_defaut_bridges_by_equipement = 10;
    public static $_configRules = 'bridges_rules';
    public static $_configMigration = 'bridges_migration';
    public static $_versionMigration = 2;

    public static $_sources = array('eqLogic', 'logicalId', 'cmd', 'pluginConfig', 'php');
    // auto : détecté à chaque mise à jour selon la valeur du champ ; full : tout le champ ; ip : seulement l'ip dans la valeur
    public static $_replaces = array('auto', 'full', 'ip');
    public static $_operators = array('==', '!=', 'in', 'notin', 'contains', 'notcontains', 'empty', 'notempty', 'regex');
    public static $_specialKeys = array('@value', '@name', '@eqName', '@isEnable', '@logicalId');

    private static $_cachedRules = NULL;
    private static $_cachedElements = NULL;
    private static $_cachedIndex = NULL;
    private static $_cachedInstalled = NULL;
    private static $_cachedBridges = NULL;

    public static function clearCache(){
        self::$_cachedRules = NULL;
        self::$_cachedElements = NULL;
        self::$_cachedIndex = NULL;
        self::$_cachedInstalled = NULL;
        self::$_cachedBridges = NULL;
    }

    /* ------------------------------------------------------------------------------------------
     * Outils ip
     * ---------------------------------------------------------------------------------------- */

    public static function ipRegex(){
        return '/(?<![\d.])(?:(?:25[0-5]|2[0-4]\d|1?\d?\d)\.){3}(?:25[0-5]|2[0-4]\d|1?\d?\d)(?![\d])/';
    }

    public static function extractIp($_value){
        if (!is_scalar($_value) || $_value === '') {
            return NULL;
        }
        if (preg_match(self::ipRegex(), (string) $_value, $match)) {
            return $match[0];
        }
        return NULL;
    }

    public static function isValidIp($_ip){
        return filter_var($_ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) !== FALSE;
    }

    // Mode effectif pour une valeur : en automatique, un champ vide ou réduit à une ip est remplacé entièrement,
    // un champ qui contient une ip au milieu d'autre chose (URL, hôte:port...) ne voit que son ip remplacée
    public static function effectiveReplace($_raw, $_replace){
        if ($_replace != 'auto') {
            return $_replace;
        }
        $raw = is_scalar($_raw) ? trim((string) $_raw) : '';
        if ($raw === '' || self::isValidIp($raw)) {
            return 'full';
        }
        return 'ip';
    }

    // Ip actuelle d'un élément ; FALSE si l'élément doit être ignoré (pas d'ip à remplacer dans la valeur)
    public static function elementIp($_raw, $_replace){
        if (self::effectiveReplace($_raw, $_replace) == 'full') {
            return ($_replace == 'auto') ? trim((string) $_raw) : $_raw;
        }
        $ip = self::extractIp($_raw);
        return ($ip === NULL) ? FALSE : $ip;
    }

    // Nouvelle valeur du champ, NULL si l'ip ne peut pas y être placée
    public static function computeNewValue($_raw, $_ip, $_replace){
        if (self::effectiveReplace($_raw, $_replace) == 'full') {
            return $_ip;
        }
        $old = self::extractIp($_raw);
        if ($old === NULL) {
            return NULL;
        }
        // Seules les occurrences de l'ancienne ip sont remplacées (une autre ip dans la même valeur reste intacte)
        $pattern = '/(?<![\d.])' . preg_quote($old, '/') . '(?![\d])/';
        return preg_replace($pattern, $_ip, (string) $_raw);
    }

    /* ------------------------------------------------------------------------------------------
     * Plugins
     * ---------------------------------------------------------------------------------------- */

    public static function installedPlugins(){
        if (self::$_cachedInstalled !== NULL) {
            return self::$_cachedInstalled;
        }
        $installed = array('core' => 'Jeedom (core)');
        foreach (plugin::listPlugin() as $plugin) {
            if ($plugin->getId() == 'scan_ip') {
                continue;
            }
            $installed[$plugin->getId()] = $plugin->getName();
        }
        self::$_cachedInstalled = $installed;
        return $installed;
    }

    public static function pluginHasDaemon($_name){
        if ($_name == 'core') {
            return FALSE;
        }
        try {
            $plugin = plugin::byId($_name);
            return is_object($plugin) && $plugin->getHasOwnDeamon() == 1;
        } catch (Throwable $e) {
            return FALSE;
        }
    }

    // Plugins installés qui ont leur propre démon
    public static function pluginsWithDaemon(){
        $return = array();
        foreach (plugin::listPlugin() as $plugin) {
            try {
                if ($plugin->getId() != 'scan_ip' && $plugin->getHasOwnDeamon() == 1) {
                    $return[] = $plugin->getId();
                }
            } catch (Throwable $e) {
            }
        }
        return $return;
    }

    public static function bridges_pluginExists($_name){
        $installed = self::installedPlugins();
        return isset($installed[$_name]);
    }

    /* ------------------------------------------------------------------------------------------
     * Règles
     * ---------------------------------------------------------------------------------------- */

    public static function getCatalog(){
        $rules = array();
        $json = @json_decode(@file_get_contents(self::$_jsonBridges), true);
        if (is_array($json) && isset($json['rules']) && is_array($json['rules'])) {
            foreach ($json['rules'] as $rule) {
                if (!empty($rule['id'])) {
                    $rules[$rule['id']] = $rule;
                }
            }
        }
        return $rules;
    }

    // Plugins (ou champs) exclus de la recherche des ip : ils gèrent eux-mêmes les ip des appareils
    // (ex. plugin Freebox qui découvre les appareils connectés). Comparaison insensible à la casse.
    public static function getDiscoverIgnore(){
        $return = array();
        $json = @json_decode(@file_get_contents(self::$_jsonBridges), true);
        if (is_array($json) && isset($json['discoverIgnore']) && is_array($json['discoverIgnore'])) {
            foreach ($json['discoverIgnore'] as $ignore) {
                if (is_array($ignore) && !empty($ignore['plugin'])) {
                    $return[] = array(
                        'plugin' => strtolower((string) $ignore['plugin']),
                        'key' => isset($ignore['key']) ? (string) $ignore['key'] : '',
                    );
                }
            }
        }
        return $return;
    }

    private static function isDiscoverIgnored($_ignore, $_plugin, $_key){
        foreach ($_ignore as $ignore) {
            if ($ignore['plugin'] == strtolower($_plugin) && ($ignore['key'] === '' || $ignore['key'] === $_key)) {
                return TRUE;
            }
        }
        return FALSE;
    }

    public static function getUserStore(){
        $store = config::byKey(self::$_configRules, 'scan_ip', array());
        if (is_string($store)) {
            $store = json_decode($store, true);
        }
        return is_array($store) ? $store : array();
    }

    private static function saveUserStore($_store){
        config::save(self::$_configRules, $_store, 'scan_ip');
        self::clearCache();
    }

    public static function normalizeRule($_rule){
        $rule = is_array($_rule) ? $_rule : array();
        $rule['id'] = isset($rule['id']) ? trim((string) $rule['id']) : '';
        $rule['plugin'] = isset($rule['plugin']) ? trim((string) $rule['plugin']) : '';
        $rule['source'] = isset($rule['source']) ? (string) $rule['source'] : 'eqLogic';
        $rule['key'] = isset($rule['key']) ? trim((string) $rule['key']) : '';
        if ($rule['source'] == 'logicalId') {
            $rule['key'] = 'logicalId';
        }
        $rule['replace'] = (isset($rule['replace']) && in_array($rule['replace'], self::$_replaces)) ? $rule['replace'] : 'full';
        $rule['enabled'] = !isset($rule['enabled']) || ($rule['enabled'] !== FALSE && $rule['enabled'] !== 0 && $rule['enabled'] !== '0' && $rule['enabled'] !== 'false');
        $rule['daemon'] = !empty($rule['daemon']) && $rule['daemon'] !== '0' && $rule['daemon'] !== 'false';
        foreach (array('detail', 'detailKey', 'name', 'nameKey', 'legacy', 'class', 'description') as $field) {
            $rule[$field] = isset($rule[$field]) ? trim((string) $rule[$field]) : '';
        }
        $conditions = array();
        if (isset($rule['conditions']) && is_array($rule['conditions'])) {
            foreach ($rule['conditions'] as $condition) {
                if (!is_array($condition) || empty($condition['key'])) {
                    continue;
                }
                $conditions[] = array(
                    'key' => trim((string) $condition['key']),
                    'op' => isset($condition['op']) ? (string) $condition['op'] : '==',
                    'value' => isset($condition['value']) ? (string) $condition['value'] : '',
                );
            }
        }
        $rule['conditions'] = $conditions;
        $repeat = NULL;
        if (isset($rule['repeat']) && is_array($rule['repeat'])) {
            $repeat = array(
                'from' => isset($rule['repeat']['from']) ? (int) $rule['repeat']['from'] : 1,
                'to' => isset($rule['repeat']['to']) ? (int) $rule['repeat']['to'] : 0,
                'toKey' => isset($rule['repeat']['toKey']) ? (string) $rule['repeat']['toKey'] : '',
            );
        }
        $rule['repeat'] = $repeat;
        return $rule;
    }

    // Vérifie une règle saisie dans le formulaire. Lève une exception si elle n'est pas valide.
    public static function validateRule($_rule, $_ignoreId = NULL){
        $rule = self::normalizeRule($_rule);
        if (!preg_match('/^[A-Za-z0-9_\-]+$/', $rule['plugin'])) {
            throw new Exception(__('Plugin invalide', __FILE__));
        }
        if (!in_array($rule['source'], self::$_sources)) {
            throw new Exception(__('Source invalide', __FILE__));
        }
        if ($rule['source'] == 'php') {
            if (!preg_match('/^[A-Za-z0-9_]+$/', $rule['class']) || !file_exists(self::$_folderBridges . $rule['class'] . '.php')) {
                throw new Exception(__('Bridge PHP introuvable', __FILE__));
            }
        } elseif ($rule['key'] === '' || !preg_match('/^[A-Za-z0-9_:.\-{}]+$/', $rule['key'])) {
            throw new Exception(__('Nom du champ invalide', __FILE__));
        }
        if ($rule['source'] != 'pluginConfig' && strpos($rule['key'], '{n}') !== FALSE) {
            throw new Exception(__('{n} n\'est utilisable que pour la configuration d\'un plugin', __FILE__));
        }
        foreach ($rule['conditions'] as $condition) {
            if (!in_array($condition['op'], self::$_operators)) {
                throw new Exception(__('Opérateur de condition invalide', __FILE__) . ' : ' . $condition['op']);
            }
            if (substr($condition['key'], 0, 1) == '@' && !in_array($condition['key'], self::$_specialKeys)) {
                throw new Exception(__('Champ de condition inconnu', __FILE__) . ' : ' . $condition['key']);
            }
            if ($condition['op'] == 'regex' && @preg_match(self::conditionRegex($condition['value']), '') === FALSE) {
                throw new Exception(__('Expression régulière invalide', __FILE__) . ' : ' . $condition['value']);
            }
        }
        // Deux règles sur le même champ produiraient les mêmes éléments
        if ($rule['source'] != 'php') {
            foreach (self::getRules() as $other) {
                if ($other['id'] == $rule['id'] || $other['id'] == $_ignoreId) {
                    continue;
                }
                if ($other['plugin'] == $rule['plugin'] && $other['source'] == $rule['source'] && $other['key'] == $rule['key']) {
                    throw new Exception(sprintf(__('Ce champ est déjà géré par le bridge "%s"', __FILE__), $other['id']));
                }
            }
        }
        return $rule;
    }

    // Règles effectives : catalogue + modifications de l'utilisateur + règles de l'utilisateur
    /**
     * Test (mode développeur + mode debug uniquement) : le catalogue est ignoré, comme si aucun bridge n'était fourni.
     * Les plugins qu'il couvre redeviennent proposés par la recherche et l'assistant.
     * Rien n'est supprimé : associations et modifications du catalogue sont retrouvées en désactivant l'option
     * ou en quittant le mode debug.
     */
    public static function catalogIgnored(){
        return config::byKey('bridges_ignore_catalog', 'scan_ip', 0) == 1
            && config::byKey('mode_plugin', 'scan_ip', 'normal') == 'debug'
            && scan_ip_dev::modeDeveloppeur();
    }

    public static function getRules(){
        if (self::$_cachedRules !== NULL) {
            return self::$_cachedRules;
        }
        $catalog = self::getCatalog();
        $store = self::getUserStore();
        $rules = array();
        if (self::catalogIgnored()) {
            // Les modifications de bridges du catalogue sont mises de côté (elles ne sont pas des bridges complets)
            foreach (array_keys($catalog) as $id) {
                unset($store[$id]);
            }
            $catalog = array();
        }
        foreach ($catalog as $id => $rule) {
            $rule['id'] = $id;
            $base = self::normalizeRule($rule);
            if (isset($store[$id]) && is_array($store[$id])) {
                $rule = array_merge($rule, $store[$id]);
            }
            $rule = self::normalizeRule($rule);
            $rule['origin'] = 'catalog';
            // Modifié = la règle elle-même diffère du catalogue (activer / désactiver ne compte pas)
            $rule['overridden'] = self::ruleDiffers($base, $rule);
            $rules[$id] = $rule;
        }
        foreach ($store as $id => $rule) {
            if (isset($catalog[$id]) || !is_array($rule)) {
                continue;
            }
            $rule['id'] = $id;
            $rule = self::normalizeRule($rule);
            $rule['origin'] = 'user';
            $rule['overridden'] = FALSE;
            $rules[$id] = $rule;
        }
        uasort($rules, function ($a, $b) {
            $cmp = strcasecmp($a['plugin'], $b['plugin']);
            return ($cmp != 0) ? $cmp : strcasecmp($a['id'], $b['id']);
        });
        self::$_cachedRules = $rules;
        return $rules;
    }

    // Champs qui définissent la règle (l'état actif / inactif n'en fait pas partie)
    private static $_ruleFields = array('plugin', 'source', 'key', 'replace', 'daemon', 'conditions', 'detail', 'detailKey', 'name', 'nameKey', 'repeat', 'class', 'description', 'legacy');

    private static function ruleDiffers($_a, $_b){
        foreach (self::$_ruleFields as $field) {
            $a = isset($_a[$field]) ? $_a[$field] : NULL;
            $b = isset($_b[$field]) ? $_b[$field] : NULL;
            if (json_encode($a) !== json_encode($b)) {
                return TRUE;
            }
        }
        return FALSE;
    }

    public static function getRule($_id){
        $rules = self::getRules();
        return isset($rules[$_id]) ? $rules[$_id] : NULL;
    }

    private static function generateRuleId($_rule){
        $base = $_rule['plugin'] . '.' . preg_replace('/[^A-Za-z0-9_\-]+/', '_', $_rule['source'] . '_' . $_rule['key']);
        $rules = self::getRules();
        $id = $base;
        $i = 2;
        while (isset($rules[$id])) {
            $id = $base . '_' . $i++;
        }
        return $id;
    }

    public static function saveRule($_rule){
        $rule = self::validateRule($_rule);
        $catalog = self::getCatalog();
        if ($rule['id'] === '' || (!isset($catalog[$rule['id']]) && self::getRule($rule['id']) === NULL)) {
            $rule['id'] = self::generateRuleId($rule);
        }
        $store = self::getUserStore();
        $data = array();
        foreach (array('plugin', 'source', 'key', 'replace', 'enabled', 'daemon', 'conditions', 'detail', 'detailKey', 'name', 'nameKey', 'repeat', 'class', 'description', 'legacy') as $field) {
            if ($rule[$field] !== '' && $rule[$field] !== NULL && $rule[$field] !== array()) {
                $data[$field] = $rule[$field];
            }
        }
        $data['enabled'] = $rule['enabled'];
        $data['daemon'] = $rule['daemon'];
        $data['conditions'] = $rule['conditions'];
        $store[$rule['id']] = $data;
        self::saveUserStore($store);
        log::add('scan_ip', 'info', 'Bridges :. ' . sprintf(__('Bridge "%s" enregistré', __FILE__), $rule['id']));
        return $rule['id'];
    }

    public static function setRuleEnabled($_id, $_enabled){
        $rule = self::getRule($_id);
        if ($rule === NULL) {
            throw new Exception(__('Bridge introuvable', __FILE__) . ' : ' . $_id);
        }
        $store = self::getUserStore();
        if (!isset($store[$_id]) || !is_array($store[$_id])) {
            $store[$_id] = array();
        }
        $store[$_id]['enabled'] = (bool) $_enabled;
        // Bridge du catalogue remis dans son état par défaut, sans autre modification : rien à conserver
        $catalog = self::getCatalog();
        if (isset($catalog[$_id]) && array_keys($store[$_id]) == array('enabled')) {
            $default = self::normalizeRule(array_merge($catalog[$_id], array('id' => $_id)));
            if ($store[$_id]['enabled'] === $default['enabled']) {
                unset($store[$_id]);
            }
        }
        self::saveUserStore($store);
        log::add('scan_ip', 'info', 'Bridges :. ' . sprintf(($_enabled ? __('Bridge "%s" activé', __FILE__) : __('Bridge "%s" désactivé', __FILE__)), $_id));
    }

    // Supprime une règle de l'utilisateur, ou rétablit la version du catalogue
    public static function deleteRule($_id){
        $store = self::getUserStore();
        if (isset($store[$_id])) {
            // Bridge du catalogue : ses modifications sont abandonnées mais il reste désactivé s'il l'était
            $catalog = self::getCatalog();
            $keepDisabled = isset($catalog[$_id]) && is_array($store[$_id]) && isset($store[$_id]['enabled']) && $store[$_id]['enabled'] === FALSE;
            if ($keepDisabled) {
                $store[$_id] = array('enabled' => FALSE);
            } else {
                unset($store[$_id]);
            }
            self::saveUserStore($store);
            log::add('scan_ip', 'info', 'Bridges :. ' . sprintf(__('Bridge "%s" supprimé ou réinitialisé', __FILE__), $_id));
        }
    }

    /* ------------------------------------------------------------------------------------------
     * Conditions
     * ---------------------------------------------------------------------------------------- */

    private static function conditionRegex($_pattern){
        return '~' . str_replace('~', '\~', (string) $_pattern) . '~i';
    }

    public static function checkConditions($_conditions, $_getter, $_n = NULL){
        foreach ($_conditions as $condition) {
            $key = ($_n !== NULL) ? str_replace('{n}', $_n, $condition['key']) : $condition['key'];
            $actual = call_user_func($_getter, $key);
            $actual = is_scalar($actual) ? trim((string) $actual) : (empty($actual) ? '' : json_encode($actual));
            $expected = trim($condition['value']);
            $list = array_map('trim', explode(',', $expected));
            switch ($condition['op']) {
                case '==': $ok = ($actual == $expected); break;
                case '!=': $ok = ($actual != $expected); break;
                case 'in': $ok = in_array($actual, $list); break;
                case 'notin': $ok = !in_array($actual, $list); break;
                case 'contains': $ok = ($expected === '' || stripos($actual, $expected) !== FALSE); break;
                case 'notcontains': $ok = ($expected === '' || stripos($actual, $expected) === FALSE); break;
                case 'empty': $ok = ($actual === ''); break;
                case 'notempty': $ok = ($actual !== ''); break;
                case 'regex': $ok = (@preg_match(self::conditionRegex($expected), $actual) === 1); break;
                default: $ok = FALSE;
            }
            if (!$ok) {
                return FALSE;
            }
        }
        return TRUE;
    }

    /* ------------------------------------------------------------------------------------------
     * Éléments produits par une règle
     * ---------------------------------------------------------------------------------------- */

    private static function legacyValue($_rule, $_vars){
        if ($_rule['legacy'] === '') {
            return NULL;
        }
        $value = $_rule['legacy'];
        foreach ($_vars as $name => $var) {
            $value = str_replace('{' . $name . '}', (string) $var, $value);
        }
        return $value;
    }

    private static function printPlugin($_rule, $_detail){
        return ($_detail !== '' && $_detail !== NULL) ? $_rule['plugin'] . " :: " . $_detail : $_rule['plugin'];
    }

    private static function makeElement($_rule, $_data){
        $element = array_merge(array(
            "plugin" => $_rule['plugin'],
            "plugin_print" => $_rule['plugin'],
            "name" => "",
            "id" => "",
            "champ" => NULL,
            "ip_v4" => NULL,
            "raw" => NULL,
            "value" => "",
            "aliases" => array(),
            "url" => "",
            "rule" => $_rule['id'],
            "enabled" => $_rule['enabled'],
            "ctx" => array(),
        ), $_data);
        $element["aliases"] = array_values(array_filter(array_unique($element["aliases"]), function ($alias) use ($element) {
            return $alias !== NULL && $alias !== '' && $alias !== $element["value"];
        }));
        return $element;
    }

    public static function getRuleElements($_rule){
        $rule = self::normalizeRule($_rule);
        if ($rule['id'] === '') {
            $rule['id'] = '_test';
        }
        if (!self::bridges_pluginExists($rule['plugin'])) {
            return array();
        }
        try {
            switch ($rule['source']) {
                case 'eqLogic':
                case 'logicalId':
                    return self::elementsEqLogic($rule);
                case 'cmd':
                    return self::elementsCmd($rule);
                case 'pluginConfig':
                    return self::elementsPluginConfig($rule);
                case 'php':
                    return self::elementsPhp($rule);
            }
        } catch (Throwable $e) {
            log::add('scan_ip', 'error', 'Bridges :. ' . sprintf(__('Erreur du bridge "%s"', __FILE__), $rule['id']) . ' : ' . $e->getMessage());
        }
        return array();
    }

    private static function eqLogicGetter($_eqLogic, $_raw){
        return function ($_key) use ($_eqLogic, $_raw) {
            switch ($_key) {
                case '@value': return $_raw;
                case '@name':
                case '@eqName': return $_eqLogic->getName();
                case '@isEnable': return $_eqLogic->getIsEnable();
                case '@logicalId': return $_eqLogic->getLogicalId();
            }
            return $_eqLogic->getConfiguration($_key);
        };
    }

    private static function elementsEqLogic($_rule){
        $return = array();
        $isLogicalId = ($_rule['source'] == 'logicalId');
        foreach (eqLogic::byType($_rule['plugin']) as $eqLogic) {
            $raw = $isLogicalId ? $eqLogic->getLogicalId() : $eqLogic->getConfiguration($_rule['key']);
            if (is_array($raw) || is_object($raw)) {
                continue;
            }
            $getter = self::eqLogicGetter($eqLogic, $raw);
            if (!self::checkConditions($_rule['conditions'], $getter)) {
                continue;
            }
            $ip = self::elementIp($raw, $_rule['replace']);
            if ($ip === FALSE) {
                continue;
            }
            $detail = ($_rule['detail'] !== '') ? $_rule['detail'] : (($_rule['detailKey'] !== '') ? call_user_func($getter, $_rule['detailKey']) : '');
            $id = $eqLogic->getId();
            $champ = $isLogicalId ? '@logicalId' : $_rule['key'];
            $return[] = self::makeElement($_rule, array(
                "plugin_print" => self::printPlugin($_rule, is_scalar($detail) ? (string) $detail : ''),
                "name" => $eqLogic->getName(),
                "id" => $id,
                "champ" => $champ,
                "ip_v4" => $ip,
                "raw" => $raw,
                "value" => $_rule['plugin'] . "|" . $id . "|" . $champ,
                "aliases" => array(self::legacyValue($_rule, array('plugin' => $_rule['plugin'], 'eqId' => $id, 'key' => $_rule['key']))),
                "url" => "index.php?v=d&m=" . $_rule['plugin'] . "&p=" . $_rule['plugin'] . "&id=" . $id,
                "ctx" => array("eqId" => $id),
            ));
        }
        return $return;
    }

    private static function elementsCmd($_rule){
        $return = array();
        foreach (eqLogic::byType($_rule['plugin']) as $eqLogic) {
            $eqName = trim($eqLogic->getName());
            foreach ($eqLogic->getCmd() as $cmd) {
                $raw = $cmd->getConfiguration($_rule['key']);
                if (is_array($raw) || is_object($raw) || $raw === '' || $raw === NULL) {
                    continue;
                }
                $getter = function ($_key) use ($cmd, $eqLogic, $raw) {
                    switch ($_key) {
                        case '@value': return $raw;
                        case '@name': return $cmd->getName();
                        case '@eqName': return $eqLogic->getName();
                        case '@isEnable': return $eqLogic->getIsEnable();
                        case '@logicalId': return $cmd->getLogicalId();
                    }
                    return $cmd->getConfiguration($_key);
                };
                if (!self::checkConditions($_rule['conditions'], $getter)) {
                    continue;
                }
                $ip = self::elementIp($raw, $_rule['replace']);
                if ($ip === FALSE) {
                    continue;
                }
                $detail = ($_rule['detail'] !== '') ? $_rule['detail'] : (($_rule['detailKey'] !== '') ? call_user_func($getter, $_rule['detailKey']) : $eqName);
                $eqId = $eqLogic->getId();
                $cmdId = $cmd->getId();
                $champ = "cmd" . $cmdId . ":" . $_rule['key'];
                $return[] = self::makeElement($_rule, array(
                    "plugin_print" => self::printPlugin($_rule, is_scalar($detail) ? (string) $detail : ''),
                    "name" => $cmd->getName(),
                    "id" => $eqId,
                    "champ" => $champ,
                    "ip_v4" => $ip,
                    "raw" => $raw,
                    "value" => $_rule['plugin'] . "|" . $eqId . "|" . $champ,
                    "aliases" => array(self::legacyValue($_rule, array('plugin' => $_rule['plugin'], 'eqId' => $eqId, 'cmdId' => $cmdId, 'key' => $_rule['key']))),
                    "url" => "index.php?v=d&m=" . $_rule['plugin'] . "&p=" . $_rule['plugin'] . "&id=" . $eqId,
                    "ctx" => array("eqId" => $eqId, "cmdId" => $cmdId),
                ));
            }
        }
        return $return;
    }

    private static function elementsPluginConfig($_rule){
        $return = array();
        $plugin = $_rule['plugin'];
        $indexes = array(NULL);
        if (strpos($_rule['key'], '{n}') !== FALSE) {
            $from = ($_rule['repeat'] !== NULL) ? max(0, $_rule['repeat']['from']) : 1;
            $to = ($_rule['repeat'] !== NULL) ? $_rule['repeat']['to'] : 0;
            if ($_rule['repeat'] !== NULL && $_rule['repeat']['toKey'] !== '') {
                $to = (int) config::byKey($_rule['repeat']['toKey'], $plugin);
            }
            if ($to < $from) {
                $to = $from + 9;
            }
            $indexes = range($from, min($to, $from + 49));
        }
        foreach ($indexes as $n) {
            $key = ($n !== NULL) ? str_replace('{n}', $n, $_rule['key']) : $_rule['key'];
            $raw = config::byKey($key, $plugin);
            if (is_array($raw) || is_object($raw) || $raw === '' || $raw === NULL) {
                continue;
            }
            $getter = function ($_key) use ($raw, $plugin, $key) {
                switch ($_key) {
                    case '@value': return $raw;
                    case '@name': return $key;
                    case '@eqName':
                    case '@logicalId': return '';
                    case '@isEnable': return 1;
                }
                return config::byKey($_key, $plugin);
            };
            if (!self::checkConditions($_rule['conditions'], $getter, $n)) {
                continue;
            }
            $ip = self::elementIp($raw, $_rule['replace']);
            if ($ip === FALSE) {
                continue;
            }
            $name = '';
            if ($_rule['nameKey'] !== '') {
                $name = config::byKey(str_replace('{n}', (string) $n, $_rule['nameKey']), $plugin);
            }
            if (!is_scalar($name) || $name === '') {
                $name = ($_rule['name'] !== '') ? str_replace('{n}', (string) $n, $_rule['name']) : $key;
            }
            $detail = ($_rule['detail'] !== '') ? str_replace('{n}', (string) $n, $_rule['detail']) : (($_rule['detailKey'] !== '') ? config::byKey(str_replace('{n}', (string) $n, $_rule['detailKey']), $plugin) : '');
            $return[] = self::makeElement($_rule, array(
                "plugin_print" => self::printPlugin($_rule, is_scalar($detail) ? (string) $detail : ''),
                "name" => $name,
                "id" => "config",
                "champ" => $key,
                "ip_v4" => $ip,
                "raw" => $raw,
                "value" => $plugin . "|config|" . $key,
                "aliases" => array(self::legacyValue($_rule, array('plugin' => $plugin, 'key' => $key))),
                "url" => ($plugin == 'core') ? "index.php?v=d&p=administration" : "index.php?v=d&p=plugin&id=" . $plugin,
                "ctx" => array("key" => $key),
            ));
        }
        return $return;
    }

    private static function phpBridge($_rule){
        $file = self::$_folderBridges . $_rule['class'] . '.php';
        if ($_rule['class'] === '' || !preg_match('/^[A-Za-z0-9_]+$/', $_rule['class']) || !file_exists($file)) {
            return NULL;
        }
        require_once($file);
        $class = "scan_ip_" . $_rule['class'];
        return class_exists($class) ? new $class : NULL;
    }

    private static function elementsPhp($_rule){
        $return = array();
        $bridge = self::phpBridge($_rule);
        if ($bridge === NULL) {
            return $return;
        }
        $elements = $bridge->getAllElements();
        if (!is_array($elements)) {
            return $return;
        }
        foreach ($elements as $element) {
            if (empty($element["id"])) {
                continue;
            }
            $champ = !empty($element["champ"]) ? $element["champ"] : NULL;
            $value = $_rule['plugin'] . "|" . $element["id"] . ($champ !== NULL ? "|" . $champ : "");
            $return[] = self::makeElement($_rule, array(
                "plugin_print" => isset($element["plugin_print"]) ? $element["plugin_print"] : $_rule['plugin'],
                "name" => isset($element["name"]) ? $element["name"] : "",
                "id" => $element["id"],
                "champ" => $champ,
                "ip_v4" => isset($element["ip_v4"]) ? $element["ip_v4"] : NULL,
                "raw" => isset($element["ip_v4"]) ? $element["ip_v4"] : NULL,
                "value" => $value,
                "url" => is_numeric($element["id"]) ? "index.php?v=d&m=" . $_rule['plugin'] . "&p=" . $_rule['plugin'] . "&id=" . $element["id"] : "",
                "ctx" => array("id" => $element["id"], "champ" => $champ),
            ));
        }
        return $return;
    }

    /* ------------------------------------------------------------------------------------------
     * Tous les éléments (règles actives et inactives des plugins installés)
     * ---------------------------------------------------------------------------------------- */

    public static function getAllElements(){
        if (self::$_cachedElements !== NULL) {
            return self::$_cachedElements;
        }
        $elements = array();
        foreach (self::getRules() as $rule) {
            foreach (self::getRuleElements($rule) as $element) {
                $elements[] = $element;
            }
        }
        usort($elements, function ($a, $b) {
            $cmp = strcasecmp($a['plugin_print'], $b['plugin_print']);
            return ($cmp != 0) ? $cmp : strcasecmp($a['name'], $b['name']);
        });
        self::$_cachedElements = $elements;
        return $elements;
    }

    // Index valeur d'association (et anciennes valeurs) -> élément
    private static function getIndex(){
        if (self::$_cachedIndex !== NULL) {
            return self::$_cachedIndex;
        }
        $index = array();
        foreach (self::getAllElements() as $element) {
            $index[$element["value"]] = $element;
        }
        foreach (self::getAllElements() as $element) {
            foreach ($element["aliases"] as $alias) {
                if (!isset($index[$alias])) {
                    $index[$alias] = $element;
                }
            }
        }
        self::$_cachedIndex = $index;
        return $index;
    }

    public static function resolveValue($_value){
        $index = self::getIndex();
        return isset($index[$_value]) ? $index[$_value] : NULL;
    }

    // Compatibilité : éléments des règles actives
    public static function bridges_getElements(){
        if (self::$_cachedBridges !== NULL) {
            return self::$_cachedBridges;
        }
        $elements = array();
        $rules = array();
        foreach (self::getAllElements() as $element) {
            if ($element["enabled"]) {
                $elements[] = $element;
                $rules[$element["rule"]] = TRUE;
            }
        }
        self::$_cachedBridges = array("array" => (count($elements) > 0) ? $elements : NULL, "nb" => count($rules));
        return self::$_cachedBridges;
    }

    /* ------------------------------------------------------------------------------------------
     * Application d'une règle
     * ---------------------------------------------------------------------------------------- */

    /**
     * Met à jour (ou simule la mise à jour de) l'ip d'un élément
     * @return array status : updated | uptodate | noip | notfound | error, old, new, daemon
     */
    public static function applyElement($_rule, $_element, $_ip, $_dryRun = FALSE){
        $rule = self::normalizeRule($_rule);
        $result = array("status" => "notfound", "old" => isset($_element["raw"]) ? $_element["raw"] : NULL, "new" => NULL, "daemon" => NULL);
        if (!self::isValidIp($_ip)) {
            $result["status"] = "error";
            $result["message"] = __('Ip invalide', __FILE__);
            return $result;
        }
        try {
            switch ($rule['source']) {
                case 'eqLogic':
                case 'logicalId':
                    $eqLogic = eqLogic::byId($_element["ctx"]["eqId"]);
                    if (!is_object($eqLogic) || $eqLogic->getEqType_name() != $rule['plugin']) {
                        return $result;
                    }
                    $raw = ($rule['source'] == 'logicalId') ? $eqLogic->getLogicalId() : $eqLogic->getConfiguration($rule['key']);
                    $new = self::computeNewValue($raw, $_ip, $rule['replace']);
                    $result = self::compare($result, $raw, $new);
                    if ($result["status"] == "updated" && !$_dryRun) {
                        if ($rule['source'] == 'logicalId') {
                            $eqLogic->setLogicalId($new);
                        } else {
                            $eqLogic->setConfiguration($rule['key'], $new);
                        }
                        $eqLogic->save();
                    }
                    break;

                case 'cmd':
                    $cmd = cmd::byId($_element["ctx"]["cmdId"]);
                    if (!is_object($cmd)) {
                        return $result;
                    }
                    $raw = $cmd->getConfiguration($rule['key']);
                    $new = self::computeNewValue($raw, $_ip, $rule['replace']);
                    $result = self::compare($result, $raw, $new);
                    if ($result["status"] == "updated" && !$_dryRun) {
                        $cmd->setConfiguration($rule['key'], $new);
                        $cmd->save();
                    }
                    break;

                case 'pluginConfig':
                    $key = $_element["ctx"]["key"];
                    $raw = config::byKey($key, $rule['plugin']);
                    $new = self::computeNewValue($raw, $_ip, $rule['replace']);
                    $result = self::compare($result, $raw, $new);
                    if ($result["status"] == "updated" && !$_dryRun) {
                        config::save($key, $new, $rule['plugin']);
                    }
                    break;

                case 'php':
                    // Le bridge PHP ne sait pas simuler : on compare l'ip actuelle
                    $result["old"] = $_element["ip_v4"];
                    if ($_element["ip_v4"] == $_ip) {
                        $result["status"] = "uptodate";
                        $result["new"] = $_element["ip_v4"];
                    } else {
                        $result["status"] = "updated";
                        $result["new"] = $_ip;
                        if (!$_dryRun) {
                            $bridge = self::phpBridge($rule);
                            if ($bridge !== NULL) {
                                $daemon = $bridge->majIpElement(array("ip" => $_ip, "id" => $_element["ctx"]["id"], "champ" => $_element["ctx"]["champ"]));
                                if (!empty($daemon)) {
                                    $result["daemon"] = $daemon;
                                }
                            }
                        }
                    }
                    return $result;
            }
        } catch (Throwable $e) {
            $result["status"] = "error";
            $result["message"] = $e->getMessage();
            return $result;
        }
        if ($result["status"] == "updated" && $rule['daemon']) {
            $result["daemon"] = $rule['plugin'];
        }
        return $result;
    }

    private static function compare($_result, $_raw, $_new){
        $_result["old"] = $_raw;
        $_result["new"] = $_new;
        if ($_new === NULL) {
            $_result["status"] = "noip";
        } elseif ((string) $_new === (string) $_raw) {
            $_result["status"] = "uptodate";
        } else {
            $_result["status"] = "updated";
        }
        return $_result;
    }

    /* ------------------------------------------------------------------------------------------
     * Associations des équipements scan_ip
     * ---------------------------------------------------------------------------------------- */

    // valeur d'association -> équipements scan_ip associés
    public static function getAssociations(){
        $return = array();
        foreach (eqLogic::byType('scan_ip') as $scan_ip) {
            for ($i = 1; $i <= self::$_defaut_bridges_by_equipement; $i++) {
                $value = $scan_ip->getConfiguration("plug_element_plugin_" . $i);
                if (empty($value)) {
                    continue;
                }
                $element = self::resolveValue($value);
                $key = ($element !== NULL) ? $element["value"] : $value;
                $return[$key][$scan_ip->getId()] = $scan_ip;
            }
        }
        return $return;
    }

    // Réécrit les associations enregistrées avec l'ancien format des bridges
    public static function bridges_migrateAssociations($_force = FALSE){
        if (!$_force && (int) config::byKey(self::$_configMigration, 'scan_ip', 0) >= self::$_versionMigration) {
            return 0;
        }
        $nb = 0;
        foreach (eqLogic::byType('scan_ip') as $scan_ip) {
            $changed = FALSE;
            for ($i = 1; $i <= self::$_defaut_bridges_by_equipement; $i++) {
                $value = $scan_ip->getConfiguration("plug_element_plugin_" . $i);
                if (empty($value)) {
                    continue;
                }
                $element = self::resolveValue($value);
                if ($element !== NULL && $element["value"] !== $value) {
                    $scan_ip->setConfiguration("plug_element_plugin_" . $i, $element["value"]);
                    $changed = TRUE;
                    $nb++;
                }
            }
            if ($changed) {
                $scan_ip->save(true);
            }
        }
        config::save(self::$_configMigration, self::$_versionMigration, 'scan_ip');
        if ($nb > 0) {
            log::add('scan_ip', 'info', 'Bridges :. ' . sprintf(__('%s association(s) convertie(s) au nouveau format', __FILE__), $nb));
        }
        return $nb;
    }

    public static function bridges_printSelectOptionEquiements(){
        $elements = self::getAllElements();
        // Associations enregistrées qu'aucun bridge ne reconnaît (catalogue ignoré pendant un test, élément introuvable...) :
        // elles restent dans la liste pour ne pas être effacées à l'enregistrement de l'équipement
        $orphans = array();
        foreach (eqLogic::byType('scan_ip') as $scan_ip) {
            for ($i = 1; $i <= self::$_defaut_bridges_by_equipement; $i++) {
                $value = $scan_ip->getConfiguration("plug_element_plugin_" . $i);
                if (!empty($value) && self::resolveValue($value) === NULL) {
                    $orphans[$value] = TRUE;
                }
            }
        }
        if (count($elements) == 0 && count($orphans) == 0) {
            return FALSE;
        }
        // Options regroupées par plugin (les éléments sont déjà triés par plugin puis par nom)
        $groups = array();
        foreach ($elements as $element) {
            $label = "[ " . $element["ip_v4"] . " ] " . $element["name"];
            if (!$element["enabled"]) {
                $label .= " (" . __('bridge désactivé', __FILE__) . ")";
            }
            $groups[$element["plugin_print"]][] = "<option value=\"" . scan_ip_tools::escape($element["value"]) . "\" data-label=\"" . scan_ip_tools::escape($label) . "\">" . scan_ip_tools::escape($label) . "</option>";
        }
        foreach (array_keys($orphans) as $value) {
            $split = explode("|", $value);
            $label = $value . " (" . __('conservée : aucun bridge actif ne la gère', __FILE__) . ")";
            $groups[$split[0]][] = "<option value=\"" . scan_ip_tools::escape($value) . "\" data-label=\"" . scan_ip_tools::escape($label) . "\">" . scan_ip_tools::escape($label) . "</option>";
        }
        $print = "";
        foreach ($groups as $plugin => $options) {
            $print .= "<optgroup label=\"" . scan_ip_tools::escape($plugin) . "\">" . implode("", $options) . "</optgroup>";
        }
        return $print;
    }

    public static function bridges_printOptionEquiements(){
        self::bridges_migrateAssociations();
        $selection = self::bridges_printSelectOptionEquiements();

        if ($selection != FALSE) {
            echo '<div id="scanip_associations">';
            for ($index = 1; $index <= self::$_defaut_bridges_by_equipement; $index++) {
                echo '<div class="form-group scanip-assoc-row" data-index="' . $index . '">';
                echo '<label class="col-sm-3 control-label scanip-assoc-label">' . __('Association', __FILE__) . ' ' . $index . '</label>';
                echo '<div class="col-sm-5">';
                echo '<div class="input-group">';
                echo '<select class="form-control eqLogicAttr plug_element_plugin plug_element_plugin_' . $index . '" data-index="' . $index . '" data-l1key="configuration" data-l2key="plug_element_plugin_' . $index . '">';
                echo '<option value="">' . __('Sélectionnez un élément', __FILE__) . '</option>';
                echo $selection;
                echo '</select>';
                echo '<span class="input-group-btn">';
                echo '<a class="btn btn-default scanip-assoc-clear" title="' . __('Retirer cette association', __FILE__) . '"><i class="fas fa-times"></i></a>';
                echo '</span>';
                echo '</div>';
                echo '</div>';
                echo '</div>';
            }
            echo '<div class="form-group">';
            echo '<div class="col-sm-offset-3 col-sm-5">';
            echo '<a class="btn btn-default btn-sm" id="bt_scanipAssocAdd"><i class="fas fa-plus-circle"></i> ' . __('Ajouter une association', __FILE__) . '</a>';
            echo '</div>';
            echo '</div>';
            echo '</div>';
        } else {
            echo '<div class="form-group">';
            echo '<label class="col-sm-3 control-label">' . __('Association 1', __FILE__) . '</label>';
            echo '<div class="col-sm-5">';
            echo '<input class="form-control" style="color:var(--al-warning-color) !important;" type="text" value="' . __('Aucun élément compatible n\'est associé aux bridges.', __FILE__) . '" readonly="">';
            echo '</div>';
            echo '</div>';
        }
    }

    // valeur d'association (et anciennes valeurs) -> nom, ip, plugin, lien
    public static function bridges_getEquiementsById(){
        log::add('scan_ip', 'debug', 'bridges_getEquiementsById :. ' . __('Lancement', __FILE__));
        $return = array();
        foreach (self::getIndex() as $value => $element) {
            $return[$value] = array(
                "name" => $element["name"],
                "ip_v4" => $element["ip_v4"],
                "plugin" => $element["plugin"],
                "url" => $element["url"],
                "id" => $element["id"],
            );
        }
        return $return;
    }

    public static function bridges_existId($_id){
        return !is_numeric($_id) || is_object(eqLogic::byId($_id));
    }

    public static function bridges_getAllAssignEquipement($_ouput = NULL){
        log::add('scan_ip', 'debug', 'bridges_getAllAssignEquipement :. ' . __('Lancement', __FILE__));
        $return = NULL;

        foreach (eqLogic::byType('scan_ip') as $scan_ip) {
            $macId = $scan_ip->getConfiguration("mac_id");
            for ($i = 1; $i <= self::$_defaut_bridges_by_equipement; $i++) {
                $plug = $scan_ip->getConfiguration("plug_element_plugin_" . $i);
                if (!empty($plug)) {
                    $plugTest = explode("|", $plug);
                    if (isset($plugTest[1]) && !self::bridges_existId($plugTest[1])) {
                        $scan_ip->setConfiguration("plug_element_plugin_" . $i, NULL);
                        $scan_ip->save();
                    } else {
                        $return[$macId][] = $plug;
                    }
                }
            }
        }

        if ($_ouput == "json" and !empty($return)) {
            return json_encode($return);
        } else {
            return $return;
        }
    }

    public static function bridges_startDeamons($_deamons = NULL){
        if ($_deamons != NULL) {
            foreach (array_unique($_deamons) as $deamon) {
                if ($deamon == 'core' || !class_exists($deamon) || !method_exists($deamon, 'deamon_start')) {
                    continue;
                }
                log::add('scan_ip', 'debug', 'bridges_startDeamons :. ' . __('Lancement du deamon', __FILE__) . ' "' . $deamon . '"');
                try {
                    $deamon::deamon_start();
                } catch (Throwable $e) {
                    log::add('scan_ip', 'error', 'bridges_startDeamons :. ' . $deamon . ' : ' . $e->getMessage());
                }
            }
        }
    }

    public static function majElementsAssocies($_eqlogic, $_device){
        log::add('scan_ip', 'debug', 'majElementsAssocies :. ' . __('Lancement', __FILE__));
        if (empty($_device["ip_v4"])) {
            return;
        }
        $deamons = array();
        $rules = self::getRules();
        $changed = FALSE;

        for ($index = 1; $index <= self::$_defaut_bridges_by_equipement; $index++) {
            $value = $_eqlogic->getConfiguration("plug_element_plugin_" . $index);
            if ($value == "") {
                continue;
            }
            $split = explode("|", $value);

            if (!self::bridges_pluginExists($split[0])) {
                log::add('scan_ip', 'debug', 'majElementsAssocies :. ' . sprintf(__('Suppression du bridge car le plugin %s n\'est pas installé', __FILE__), $split[0]));
                $_eqlogic->setConfiguration("plug_element_plugin_" . $index, "");
                $changed = TRUE;
                continue;
            }

            $element = self::resolveValue($value);
            if ($element === NULL) {
                if (isset($split[1]) && !self::bridges_existId($split[1])) {
                    log::add('scan_ip', 'debug', 'majElementsAssocies :. ' . sprintf(__('Suppression de l\'association "%s" : équipement supprimé', __FILE__), $value));
                    $_eqlogic->setConfiguration("plug_element_plugin_" . $index, "");
                    $changed = TRUE;
                } else {
                    log::add('scan_ip', 'debug', 'majElementsAssocies :. ' . sprintf(__('Association "%s" ignorée : aucun bridge ne la prend en charge', __FILE__), $value));
                }
                continue;
            }
            if ($element["value"] !== $value) {
                $_eqlogic->setConfiguration("plug_element_plugin_" . $index, $element["value"]);
                $changed = TRUE;
            }
            if (!$element["enabled"] || !isset($rules[$element["rule"]])) {
                log::add('scan_ip', 'debug', 'majElementsAssocies :. ' . sprintf(__('Bridge "%s" désactivé : "%s" non mis à jour', __FILE__), $element["rule"], $element["name"]));
                continue;
            }

            $result = self::applyElement($rules[$element["rule"]], $element, $_device["ip_v4"]);
            if ($result["status"] == "updated") {
                log::add('scan_ip', 'info', 'Bridges :. ' . sprintf(__('%s "%s" : %s -> %s', __FILE__), $element["plugin_print"], $element["name"], $result["old"], $result["new"]));
                if (!empty($result["daemon"])) {
                    $deamons[] = $result["daemon"];
                }
            } elseif ($result["status"] == "error") {
                log::add('scan_ip', 'error', 'Bridges :. ' . $element["plugin_print"] . ' "' . $element["name"] . '" : ' . (isset($result["message"]) ? $result["message"] : ''));
            }
        }

        if ($changed) {
            $_eqlogic->save(true);
        }
        self::bridges_startDeamons($deamons);
    }

    /* ------------------------------------------------------------------------------------------
     * Simulation
     * ---------------------------------------------------------------------------------------- */

    private static function scanIpInfo($_scan_ip){
        return array(
            "id" => $_scan_ip->getId(),
            "name" => $_scan_ip->getName(),
            "mac" => $_scan_ip->getConfiguration('adress_mac'),
            "ip" => scan_ip_cmd::getCommande('ip_v4', $_scan_ip),
            "online" => (int) scan_ip_cmd::getCommande('on_line', $_scan_ip),
        );
    }

    /**
     * Simule une règle (enregistrée ou non) sans rien modifier
     * - sans $_testValue : uniquement les éléments associés à une adresse MAC, chacun avec l'ip actuelle de son appareil
     *   (exactement ce que fera le prochain scan) ;
     * - avec $_testValue : uniquement cet élément, avec $_testIp (ou l'ip de son appareil s'il est associé).
     */
    public static function simulate($_rule, $_testIp = '', $_testValue = ''){
        $rule = self::validateRule($_rule, isset($_rule['id']) ? $_rule['id'] : NULL);
        if ($rule['id'] === '') {
            $rule['id'] = '_test';
        }
        $_testIp = trim((string) $_testIp);
        $_testValue = trim((string) $_testValue);
        if ($_testIp !== '' && !self::isValidIp($_testIp)) {
            throw new Exception(__('Ip de test invalide', __FILE__));
        }
        $associations = self::getAssociations();
        $rows = array();
        $list = array();
        $nbUnassociated = 0;
        foreach (self::getRuleElements($rule) as $element) {
            $list[] = array("value" => $element["value"], "label" => $element["name"] . ' · ' . (is_scalar($element["raw"]) ? (string) $element["raw"] : ''));
            $assocs = array();
            foreach (array_merge(array($element["value"]), $element["aliases"]) as $value) {
                if (isset($associations[$value])) {
                    foreach ($associations[$value] as $scan_ip) {
                        $assocs[$scan_ip->getId()] = self::scanIpInfo($scan_ip);
                    }
                }
            }
            if ($_testValue !== '') {
                if ($element["value"] !== $_testValue) {
                    continue;
                }
                if ($_testIp === '' && count($assocs) == 0) {
                    throw new Exception(__('Cet élément n\'est associé à aucune adresse MAC : indiquez l\'ip à appliquer', __FILE__));
                }
            } elseif (count($assocs) == 0) {
                $nbUnassociated++;
                continue;
            }
            if (count($assocs) == 0) {
                $assocs = array(NULL);
            }
            foreach ($assocs as $assoc) {
                $row = array(
                    "plugin_print" => $element["plugin_print"],
                    "name" => $element["name"],
                    "field" => $element["champ"],
                    "raw" => is_scalar($element["raw"]) ? (string) $element["raw"] : '',
                    "ip" => $element["ip_v4"],
                    "url" => $element["url"],
                    "assoc" => $assoc,
                    "status" => "unassociated",
                    "new" => NULL,
                );
                $ip = ($_testValue !== '' && $_testIp !== '') ? $_testIp : (($assoc !== NULL) ? $assoc["ip"] : '');
                if ($ip !== '' && $ip !== NULL) {
                    if (!self::isValidIp($ip)) {
                        $row["status"] = "noip";
                    } else {
                        $result = self::applyElement($rule, $element, $ip, TRUE);
                        $row["status"] = $result["status"];
                        $row["new"] = is_scalar($result["new"]) ? (string) $result["new"] : NULL;
                        $row["applied"] = $ip;
                        $row["daemon"] = ($result["status"] == "updated" && $rule['daemon']) ? $rule['plugin'] : NULL;
                        if (isset($result["message"])) {
                            $row["message"] = $result["message"];
                        }
                    }
                }
                $rows[] = $row;
            }
        }
        return array("rule" => $rule, "testIp" => $_testIp, "testValue" => $_testValue, "rows" => $rows, "elements" => $list, "unassociated" => $nbUnassociated);
    }

    /* ------------------------------------------------------------------------------------------
     * Liste pour l'interface
     * ---------------------------------------------------------------------------------------- */

    public static function listRules(){
        $installed = self::installedPlugins();
        $associations = self::getAssociations();
        $counts = array();
        foreach (self::getAllElements() as $element) {
            if (!isset($counts[$element["rule"]])) {
                $counts[$element["rule"]] = array("elements" => 0, "associations" => 0);
            }
            $counts[$element["rule"]]["elements"]++;
            if (isset($associations[$element["value"]])) {
                $counts[$element["rule"]]["associations"] += count($associations[$element["value"]]);
            }
        }
        $return = array();
        foreach (self::getRules() as $rule) {
            $rule["installed"] = isset($installed[$rule['plugin']]);
            $rule["pluginName"] = isset($installed[$rule['plugin']]) ? $installed[$rule['plugin']] : $rule['plugin'];
            $rule["nbElements"] = isset($counts[$rule['id']]) ? $counts[$rule['id']]["elements"] : 0;
            $rule["nbAssociations"] = isset($counts[$rule['id']]) ? $counts[$rule['id']]["associations"] : 0;
            $return[] = $rule;
        }
        return $return;
    }

    /* ------------------------------------------------------------------------------------------
     * Assistant de création : analyse d'un champ avant de créer le bridge
     * ---------------------------------------------------------------------------------------- */

    // La méthode est-elle écrite par le plugin lui-même (et non héritée du core) ?
    private static function pluginDeclares($_class, $_method){
        try {
            if (!class_exists($_class) || !method_exists($_class, $_method)) {
                return FALSE;
            }
            $declaring = (new ReflectionMethod($_class, $_method))->getDeclaringClass()->getName();
            return !in_array($declaring, array('eqLogic', 'cmd', 'config'));
        } catch (Throwable $e) {
            return FALSE;
        }
    }

    /**
     * Faut-il redémarrer le démon du plugin après une mise à jour du champ ?
     * Jeedom appelle postConfig_<clé>() après l'enregistrement d'un paramètre du plugin et postSave() / postUpdate()
     * après celui d'un équipement ou d'une commande : un plugin qui les implémente réagit lui-même au changement.
     */
    public static function daemonAdvice($_plugin, $_source, $_key){
        $return = array("recommended" => FALSE, "hasDaemon" => FALSE, "running" => NULL, "reason" => "");
        if ($_plugin == 'core') {
            $return["reason"] = __('Jeedom applique directement sa configuration : aucun redémarrage n\'est nécessaire.', __FILE__);
            return $return;
        }
        $return["hasDaemon"] = self::pluginHasDaemon($_plugin);
        if (!$return["hasDaemon"]) {
            $return["reason"] = __('Le plugin n\'a pas de démon : la nouvelle ip est lue à chaque utilisation, aucun redémarrage n\'est nécessaire.', __FILE__);
            return $return;
        }
        try {
            $info = plugin::byId($_plugin)->deamon_info();
            $return["running"] = (isset($info['state']) && $info['state'] == 'ok');
        } catch (Throwable $e) {
        }
        if ($_source == 'pluginConfig') {
            $keys = array($_key);
            if (strpos($_key, '{n}') !== FALSE) {
                $keys = array(str_replace('{n}', '1', $_key));
            }
            $hook = 'postConfig_' . str_replace(array('::', ':'), '_', $keys[0]);
            if (self::pluginDeclares($_plugin, $hook)) {
                $return["reason"] = __('Le plugin traite lui-même la modification de ce paramètre : aucun redémarrage n\'est nécessaire.', __FILE__);
                return $return;
            }
            $return["recommended"] = TRUE;
            $return["reason"] = __('Le plugin a un démon et ne réagit pas à la modification de ce paramètre : il ne verrait la nouvelle ip qu\'au prochain redémarrage. Le démon sera redémarré après chaque mise à jour.', __FILE__);
            return $return;
        }
        $class = ($_source == 'cmd') ? $_plugin . 'Cmd' : $_plugin;
        foreach (array('postSave', 'postUpdate') as $hook) {
            if (self::pluginDeclares($class, $hook)) {
                $return["reason"] = __('Le plugin réagit à l\'enregistrement de ses équipements (et transmet en général la modification à son démon) : aucun redémarrage n\'est nécessaire.', __FILE__);
                return $return;
            }
        }
        $return["recommended"] = TRUE;
        $return["reason"] = __('Le plugin a un démon mais ne réagit pas à l\'enregistrement de ses équipements : le démon sera redémarré après chaque mise à jour pour qu\'il prenne en compte la nouvelle ip.', __FILE__);
        return $return;
    }

    // Équipement d'un appareil (adresse MAC), et non un widget du plugin (Network, Alertes)
    public static function isDevice($_scan_ip){
        return is_object($_scan_ip) && $_scan_ip->getEqType_name() == 'scan_ip' && $_scan_ip->getConfiguration('type_widget', '') == '';
    }

    // ip actuelle -> équipement Scan.Ip
    public static function knownScanIps(){
        $return = array();
        foreach (eqLogic::byType('scan_ip') as $scan_ip) {
            if (!self::isDevice($scan_ip)) {
                continue;
            }
            $ip = scan_ip_cmd::getCommande('ip_v4', $scan_ip);
            if (!empty($ip) && self::isValidIp($ip)) {
                $return[$ip] = self::scanIpInfo($scan_ip);
            }
        }
        return $return;
    }

    private static function check(&$_checks, $_level, $_title, $_text){
        $_checks[] = array("level" => $_level, "title" => $_title, "text" => $_text);
    }

    public static function analyze($_plugin, $_source, $_key){
        $rule = array('id' => '', 'plugin' => $_plugin, 'source' => $_source, 'key' => $_key, 'replace' => 'auto', 'conditions' => array(), 'enabled' => TRUE, 'daemon' => FALSE);
        $rule = self::validateRule($rule);
        if (!self::bridges_pluginExists($rule['plugin'])) {
            throw new Exception(__('Plugin non installé', __FILE__) . ' : ' . $rule['plugin']);
        }
        $rule['id'] = '_wizard';
        $checks = array();
        $known = self::knownScanIps();
        $associations = self::getAssociations();
        // Appareils vus lors du dernier scan du réseau (enregistrés ou non dans Scan.Ip)
        $network = array();
        try {
            $mapping = scan_ip_json::getJson(scan_ip::$_jsonMapping);
            if (is_array($mapping) && isset($mapping["byIpv4"]) && is_array($mapping["byIpv4"])) {
                $network = $mapping["byIpv4"];
            }
        } catch (Throwable $e) {
        }
        $unregistered = array(); $absent = array();

        // Éléments que le bridge mettrait à jour
        $elements = array();
        $nbFull = 0; $nbIp = 0; $nbMulti = 0; $nbMatched = 0; $nbErrors = 0;
        foreach (self::getRuleElements($rule) as $element) {
            $raw = is_scalar($element["raw"]) ? (string) $element["raw"] : '';
            $mode = self::effectiveReplace($raw, 'auto');
            ($mode == 'full') ? $nbFull++ : $nbIp++;
            if (preg_match_all(self::ipRegex(), $raw) > 1) {
                $nbMulti++;
            }
            $ip = self::extractIp($raw);
            $match = ($ip !== NULL && isset($known[$ip])) ? $known[$ip] : NULL;
            $associated = array();
            foreach (array_merge(array($element["value"]), $element["aliases"]) as $value) {
                if (isset($associations[$value])) {
                    foreach ($associations[$value] as $scan_ip) {
                        $associated[] = $scan_ip->getName();
                    }
                }
            }
            // Test à blanc : l'élément reçoit sa propre ip, la valeur doit rester identique
            $test = NULL;
            if ($ip !== NULL) {
                $result = self::applyElement($rule, $element, $ip, TRUE);
                $test = $result["status"];
                if (in_array($result["status"], array('error', 'notfound', 'noip'))) {
                    $nbErrors++;
                }
            }
            $seen = NULL;
            if ($match !== NULL) {
                $nbMatched++;
            } elseif ($ip !== NULL) {
                if (isset($network[$ip])) {
                    $seen = array(
                        "mac" => isset($network[$ip]["mac"]) ? $network[$ip]["mac"] : '',
                        "vendor" => isset($network[$ip]["equipement"]) ? $network[$ip]["equipement"] : '',
                    );
                    $unregistered[$ip] = TRUE;
                } else {
                    $absent[$ip] = TRUE;
                }
            }
            $elements[] = array(
                "value" => $element["value"],
                "name" => $element["name"],
                "detail" => $element["plugin_print"],
                "raw" => $raw,
                "ip" => $ip,
                "mode" => $mode,
                "match" => $match,
                "network" => $seen,
                "associated" => array_values(array_unique($associated)),
                "test" => $test,
                "url" => $element["url"],
            );
        }
        $nb = count($elements);

        if ($nb == 0) {
            self::check($checks, 'error', __('Aucun élément disponible', __FILE__), __('Aucun élément de ce plugin ne contient d\'ip dans ce champ : il n\'y a rien à tester et le bridge n\'aurait rien à mettre à jour. Il ne peut donc pas être créé.', __FILE__));
        } else {
            self::check($checks, 'ok', sprintf(__('%s élément(s) trouvé(s)', __FILE__), $nb), __('Le bridge pourra mettre à jour ces éléments dès qu\'ils seront associés à une adresse MAC.', __FILE__));

            $text = array();
            if ($nbFull > 0) { $text[] = sprintf(__('%s champ(s) ne contiennent que l\'ip : ils seront remplacés entièrement', __FILE__), $nbFull); }
            if ($nbIp > 0) { $text[] = sprintf(__('%s champ(s) contiennent l\'ip dans une valeur (URL, hôte:port…) : seule l\'ip sera remplacée', __FILE__), $nbIp); }
            self::check($checks, 'ok', __('Type de mise à jour détecté', __FILE__), implode(' ; ', $text) . '.');

            if ($nbMulti > 0) {
                self::check($checks, 'info', __('Plusieurs ip dans une valeur', __FILE__), sprintf(__('%s valeur(s) contiennent plusieurs ip : seule l\'ip de l\'appareil associé sera remplacée, les autres restent intactes.', __FILE__), $nbMulti));
            }

            if ($nbErrors > 0) {
                self::check($checks, 'error', __('Test d\'écriture', __FILE__), sprintf(__('Le test à blanc a échoué pour %s élément(s) : voir le détail ci-dessous.', __FILE__), $nbErrors));
            } else {
                self::check($checks, 'ok', __('Test d\'écriture', __FILE__), __('Test à blanc réussi : chaque élément peut être relu et réécrit (rien n\'a été modifié).', __FILE__));
            }

            $list = function ($_ips) {
                $ips = array_keys($_ips);
                return implode(', ', array_slice($ips, 0, 5)) . ((count($ips) > 5) ? '…' : '');
            };
            // Pas d'association possible automatiquement : normal à ce stade, elle peut être choisie ci-dessous ou faite plus tard
            if ($nbMatched == $nb) {
                self::check($checks, 'ok', __('Appareils reconnus', __FILE__), __('Chaque ip correspond à un équipement Scan.Ip : les éléments sont pré-associés à leur appareil ci-dessous.', __FILE__));
            } else {
                $text = ($nbMatched > 0)
                    ? sprintf(__('%s élément(s) sur %s ont l\'ip d\'un équipement Scan.Ip et sont pré-associés ci-dessous.', __FILE__), $nbMatched, $nb) . ' '
                    : '';
                $text .= __('Les autres pourront être associés plus tard depuis la fiche de leur équipement Scan.Ip : l\'élément recevra alors l\'ip actuelle de cet appareil à chaque scan.', __FILE__);
                if (count($unregistered) > 0) {
                    $text .= ' ' . sprintf(__('%s : appareil vu sur le réseau mais sans équipement Scan.Ip, à enregistrer depuis la fenêtre Réseau.', __FILE__), $list($unregistered));
                }
                if (count($absent) > 0) {
                    $text .= ' ' . sprintf(__('%s : aucun appareil avec cette ip au dernier scan (éteint, ou ip déjà changée).', __FILE__), $list($absent));
                }
                self::check($checks, 'info', __('Association aux appareils', __FILE__), $text);
            }
        }

        $advice = self::daemonAdvice($rule['plugin'], $rule['source'], $rule['key']);
        $rule['daemon'] = $advice["recommended"];
        $daemonText = $advice["reason"];
        if ($advice["recommended"] && $advice["running"] === FALSE) {
            $daemonText .= ' ' . __('Attention : le démon est actuellement arrêté, il sera démarré lors d\'une mise à jour.', __FILE__);
        }
        // L'assistant décide et informe seulement : le réglage se modifie ensuite en vue avancée
        if ($advice["recommended"]) {
            self::check($checks, 'info', __('Redémarrage du démon après chaque mise à jour', __FILE__), $daemonText . ' ' . __('Réglage modifiable ensuite dans le formulaire du bridge, en vue avancée.', __FILE__));
        } else {
            self::check($checks, 'ok', $advice["hasDaemon"] ? __('Pas de redémarrage du démon nécessaire', __FILE__) : __('Pas de démon à redémarrer', __FILE__), $daemonText);
        }

        $rule['id'] = '';
        $installed = self::installedPlugins();
        // Appareils proposés pour l'association : uniquement ceux liés au plugin
        //  - même ip qu'un élément de ce champ ;
        //  - ou déjà associés à un autre élément de ce plugin.
        $related = array();
        foreach ($elements as $element) {
            if ($element["match"] !== NULL) {
                $related[$element["match"]["id"]] = TRUE;
            }
        }
        foreach ($associations as $value => $scan_ips) {
            if (strpos($value, $rule['plugin'] . '|') === 0) {
                foreach ($scan_ips as $scan_ip) {
                    $related[$scan_ip->getId()] = TRUE;
                }
            }
        }
        $scanIps = array();
        foreach (array_keys($related) as $id) {
            $scan_ip = eqLogic::byId($id);
            if (self::isDevice($scan_ip)) {
                $scanIps[] = self::scanIpInfo($scan_ip);
            }
        }
        usort($scanIps, function ($a, $b) { return strcasecmp($a["name"], $b["name"]); });

        return array(
            "rule" => $rule,
            "pluginName" => $installed[$rule['plugin']],
            "scanIps" => $scanIps,
            "checks" => $checks,
            "elements" => $elements,
            "daemon" => $advice,
            "blocking" => ($nb == 0 || $nbErrors == $nb),
        );
    }

    // Ajoute une association (élément -> équipement Scan.Ip) dans le premier emplacement libre
    public static function addAssociation($_scanIpId, $_value){
        $scan_ip = eqLogic::byId($_scanIpId);
        if (!self::isDevice($scan_ip)) {
            return FALSE;
        }
        $free = NULL;
        for ($i = 1; $i <= self::$_defaut_bridges_by_equipement; $i++) {
            $current = $scan_ip->getConfiguration("plug_element_plugin_" . $i);
            if ($current == $_value) {
                return TRUE;
            }
            if (empty($current) && $free === NULL) {
                $free = $i;
            }
        }
        if ($free === NULL) {
            return FALSE;
        }
        $scan_ip->setConfiguration("plug_element_plugin_" . $free, $_value);
        $scan_ip->save(true);
        return TRUE;
    }

    public static function wizardCreate($_rule, $_associations){
        $id = self::saveRule($_rule);
        $added = 0; $failed = array();
        if (is_array($_associations)) {
            foreach ($_associations as $association) {
                if (empty($association['value']) || empty($association['scanIp'])) {
                    continue;
                }
                if (self::addAssociation((int) $association['scanIp'], (string) $association['value'])) {
                    $added++;
                } else {
                    $failed[] = isset($association['name']) ? (string) $association['name'] : (string) $association['value'];
                }
            }
        }
        self::clearCache();
        if ($added > 0) {
            log::add('scan_ip', 'info', 'Bridges :. ' . sprintf(__('%s association(s) ajoutée(s) par l\'assistant', __FILE__), $added));
        }
        return array("id" => $id, "added" => $added, "failed" => $failed);
    }

    /* ------------------------------------------------------------------------------------------
     * Proposition d'un bridge au catalogue
     * ---------------------------------------------------------------------------------------- */

    // Règle au format du catalogue (data/json/bridges.json) : uniquement la règle, aucune valeur trouvée chez l'utilisateur
    public static function proposal($_id){
        $rule = self::getRule($_id);
        if ($rule === NULL) {
            throw new Exception(__('Bridge introuvable', __FILE__) . ' : ' . $_id);
        }
        if ($rule['source'] == 'php') {
            throw new Exception(__('Un bridge PHP ne peut pas être proposé depuis cette fenêtre', __FILE__));
        }
        $catalog = self::getCatalog();
        $isUpdate = isset($catalog[$rule['id']]);
        $id = $isUpdate ? $rule['id'] : (isset($catalog[$rule['plugin']]) ? $rule['plugin'] . '_' . preg_replace('/[^A-Za-z0-9_]+/', '_', $rule['key']) : $rule['plugin']);

        $data = array('id' => $id, 'plugin' => $rule['plugin'], 'source' => $rule['source']);
        if ($rule['source'] != 'logicalId') {
            $data['key'] = $rule['key'];
        }
        if ($rule['replace'] != 'full') {
            $data['replace'] = $rule['replace'];
        }
        if ($rule['repeat'] !== NULL && strpos($rule['key'], '{n}') !== FALSE) {
            $repeat = array('from' => $rule['repeat']['from']);
            if ($rule['repeat']['toKey'] !== '') {
                $repeat['toKey'] = $rule['repeat']['toKey'];
            } elseif ($rule['repeat']['to'] > 0) {
                $repeat['to'] = $rule['repeat']['to'];
            }
            $data['repeat'] = $repeat;
        }
        foreach (array('name', 'nameKey', 'detail', 'detailKey') as $field) {
            if ($rule[$field] !== '') {
                $data[$field] = $rule[$field];
            }
        }
        if (count($rule['conditions']) > 0) {
            $data['conditions'] = $rule['conditions'];
        }
        if ($rule['daemon']) {
            $data['daemon'] = TRUE;
        }
        $json = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        $installed = self::installedPlugins();
        $pluginName = isset($installed[$rule['plugin']]) ? $installed[$rule['plugin']] : $rule['plugin'];
        $pluginVersion = '';
        try {
            $update = update::byLogicalId($rule['plugin']);
            if (is_object($update)) {
                $pluginVersion = trim($update->getConfiguration('version', '') . ' ' . $update->getLocalVersion());
            }
        } catch (Throwable $e) {
        }
        $jeedomVersion = '';
        try {
            $jeedomVersion = jeedom::version();
        } catch (Throwable $e) {
        }

        $title = '[Bridge] ' . $pluginName . ' : ' . ($rule['source'] == 'logicalId' ? 'logicalId' : $rule['key']);
        $body = "### Plugin
" . $pluginName . ' (`' . $rule['plugin'] . '`)' . ($pluginVersion !== '' ? ' · ' . $pluginVersion : '') . "

"
            . "### Type de proposition
" . ($isUpdate ? 'Modification du bridge `' . $rule['id'] . '` du catalogue' : 'Nouveau bridge') . "

"
            . "### Règle (format data/json/bridges.json)
```json
" . $json . "
```

"
            . "### Version
Scan.Ip " . scan_ip_maj::$_versionPlugin . ($jeedomVersion !== '' ? ' · Jeedom ' . $jeedomVersion : '') . "

"
            . "### Remarques
(Testé sur quels équipements ? Le plugin relit-il sa configuration sans redémarrer son démon ?)
";

        return array(
            'json' => $json,
            'title' => $title,
            'body' => $body,
            'isUpdate' => $isUpdate,
            'github' => 'https://github.com/Ynats-Dev/jeedom-plugin-scan.ip/issues/new?template=bridge.md&title=' . rawurlencode($title) . '&body=' . rawurlencode($body),
            // Réponse au sujet du plugin (Discourse ne permet pas de pré-remplir une réponse : le texte est copié)
            'forum' => 'https://community.jeedom.com/t/plugin-scan-ip-maj/48722/last',
        );
    }

    // Catalogue d'origine (sans les modifications de l'utilisateur), pour consultation
    public static function listCatalog(){
        $installed = self::installedPlugins();
        $return = array();
        foreach (self::getCatalog() as $id => $rule) {
            $rule['id'] = $id;
            $rule = self::normalizeRule($rule);
            $rule['installed'] = isset($installed[$rule['plugin']]);
            $rule['pluginName'] = isset($installed[$rule['plugin']]) ? $installed[$rule['plugin']] : $rule['plugin'];
            $return[] = $rule;
        }
        usort($return, function ($a, $b) {
            $cmp = strcasecmp($a['plugin'], $b['plugin']);
            return ($cmp != 0) ? $cmp : strcasecmp($a['id'], $b['id']);
        });
        return $return;
    }

    // Mêmes bridges que la liste : ceux qui trouvent des éléments, et ceux créés ou modifiés par l'utilisateur
    public static function countSummary(){
        $installed = self::installedPlugins();
        $nbElements = array();
        foreach (self::getAllElements() as $element) {
            $nbElements[$element["rule"]] = TRUE;
        }
        $summary = array("shown" => 0, "enabled" => 0, "user" => 0);
        foreach (self::getRules() as $rule) {
            $used = isset($installed[$rule['plugin']]) && isset($nbElements[$rule['id']]);
            if (!$used && $rule['origin'] != 'user' && !$rule['overridden']) {
                continue;
            }
            $summary["shown"]++;
            if ($rule['enabled']) {
                $summary["enabled"]++;
            }
            if ($rule['origin'] == 'user') {
                $summary["user"]++;
            }
        }
        return $summary;
    }


    /* ------------------------------------------------------------------------------------------
     * Découverte des champs contenant une ip dans les plugins installés
     * ---------------------------------------------------------------------------------------- */

    private static function isInterestingIp($_ip){
        if ($_ip === NULL) {
            return FALSE;
        }
        // Masques, adresses non routables ou de bouclage : ce ne sont pas des équipements
        return !preg_match('/^(0\.|127\.|255\.|22[4-9]\.|23\d\.)/', $_ip);
    }

    private static function addDiscovered(&$_fields, $_plugin, $_source, $_key, $_name, $_raw, $_knownIps){
        // Clé vide (champ de formulaire sans nom côté plugin) : aucun bridge ne peut la cibler
        if (trim((string) $_key) === '') {
            return;
        }
        $ip = self::extractIp($_raw);
        if (!self::isInterestingIp($ip)) {
            return;
        }
        $id = $_plugin . '|' . $_source . '|' . $_key;
        if (!isset($_fields[$id])) {
            $_fields[$id] = array(
                "plugin" => $_plugin,
                "source" => $_source,
                "key" => $_key,
                "count" => 0,
                "exact" => 0,
                "known" => 0,
                "samples" => array(),
            );
        }
        $_fields[$id]["count"]++;
        if (trim((string) $_raw) === $ip) {
            $_fields[$id]["exact"]++;
        }
        $known = isset($_knownIps[$ip]) ? $_knownIps[$ip] : NULL;
        if ($known !== NULL) {
            $_fields[$id]["known"]++;
        }
        if (count($_fields[$id]["samples"]) < 5) {
            $_fields[$id]["samples"][] = array("name" => $_name, "raw" => (string) $_raw, "ip" => $ip, "known" => $known);
        }
    }

    public static function discover(){
        $installed = self::installedPlugins();
        $knownIps = array();
        foreach (eqLogic::byType('scan_ip') as $scan_ip) {
            $ip = scan_ip_cmd::getCommande('ip_v4', $scan_ip);
            if (!empty($ip)) {
                $knownIps[$ip] = $scan_ip->getName();
            }
        }

        $fields = array();
        foreach ($installed as $pluginId => $pluginName) {
            if ($pluginId != 'core') {
                try {
                    foreach (eqLogic::byType($pluginId) as $eqLogic) {
                        $configuration = $eqLogic->getConfiguration();
                        if (is_array($configuration)) {
                            foreach ($configuration as $key => $raw) {
                                if (is_scalar($raw)) {
                                    self::addDiscovered($fields, $pluginId, 'eqLogic', (string) $key, $eqLogic->getName(), $raw, $knownIps);
                                }
                            }
                        }
                        self::addDiscovered($fields, $pluginId, 'logicalId', 'logicalId', $eqLogic->getName(), $eqLogic->getLogicalId(), $knownIps);
                        foreach ($eqLogic->getCmd() as $cmd) {
                            $configuration = $cmd->getConfiguration();
                            if (is_array($configuration)) {
                                foreach ($configuration as $key => $raw) {
                                    if (is_scalar($raw)) {
                                        self::addDiscovered($fields, $pluginId, 'cmd', (string) $key, $eqLogic->getName() . ' / ' . $cmd->getName(), $raw, $knownIps);
                                    }
                                }
                            }
                        }
                    }
                } catch (Throwable $e) {
                    log::add('scan_ip', 'debug', 'Bridges discover :. ' . $pluginId . ' : ' . $e->getMessage());
                }
            }
            try {
                $configs = config::searchKey('', $pluginId);
                if (is_array($configs)) {
                    foreach ($configs as $config) {
                        if (isset($config['key']) && isset($config['value']) && is_scalar($config['value'])) {
                            self::addDiscovered($fields, $pluginId, 'pluginConfig', (string) $config['key'], $config['key'], $config['value'], $knownIps);
                        }
                    }
                }
            } catch (Throwable $e) {
                log::add('scan_ip', 'debug', 'Bridges discover :. config ' . $pluginId . ' : ' . $e->getMessage());
            }
        }

        // Règles existantes qui couvrent déjà ces champs
        $rules = self::getRules();
        $ignore = self::getDiscoverIgnore();
        $return = array();
        foreach ($fields as $field) {
            if (self::isDiscoverIgnored($ignore, $field["plugin"], $field["key"])) {
                continue;
            }
            $field["pluginName"] = $installed[$field["plugin"]];
            $field["rules"] = array();
            foreach ($rules as $rule) {
                if ($rule['plugin'] != $field["plugin"] || $rule['source'] != $field["source"]) {
                    continue;
                }
                $pattern = '/^' . str_replace('\{n\}', '\d+', preg_quote($rule['key'], '/')) . '$/';
                if ($rule['source'] == 'logicalId' || preg_match($pattern, $field["key"])) {
                    $field["rules"][] = array("id" => $rule['id'], "enabled" => $rule['enabled']);
                }
            }
            // Détection automatique, valeur par valeur, lors de chaque mise à jour
            $field["replace"] = 'auto';
            // Relance du démon proposée pour la configuration d'un plugin qui a son propre démon (lue en général au démarrage)
            $advice = self::daemonAdvice($field["plugin"], $field["source"], $field["key"]);
            $field["daemon"] = $advice["recommended"];
            $return[] = $field;
        }
        usort($return, function ($a, $b) {
            $covered = (count($a["rules"]) > 0) - (count($b["rules"]) > 0);
            if ($covered != 0) {
                return $covered;
            }
            $cmp = strcasecmp($a["plugin"], $b["plugin"]);
            return ($cmp != 0) ? $cmp : strcasecmp($a["key"], $b["key"]);
        });
        return $return;
    }

    // Champs d'un équipement, d'une commande ou du plugin pour aider à écrire les conditions
    public static function fieldSuggestions($_plugin, $_source){
        $keys = array();
        if (!self::bridges_pluginExists($_plugin)) {
            return array();
        }
        try {
            if ($_source == 'pluginConfig') {
                $configs = config::searchKey('', $_plugin);
                if (is_array($configs)) {
                    foreach ($configs as $config) {
                        if (isset($config['key']) && isset($config['value']) && is_scalar($config['value'])) {
                            $keys[$config['key']][] = (string) $config['value'];
                        }
                    }
                }
            } elseif ($_plugin != 'core') {
                foreach (eqLogic::byType($_plugin) as $eqLogic) {
                    $objects = ($_source == 'cmd') ? $eqLogic->getCmd() : array($eqLogic);
                    foreach ($objects as $object) {
                        $configuration = $object->getConfiguration();
                        if (!is_array($configuration)) {
                            continue;
                        }
                        foreach ($configuration as $key => $raw) {
                            if (is_scalar($raw)) {
                                $keys[$key][] = (string) $raw;
                            }
                        }
                    }
                }
            }
        } catch (Throwable $e) {
            log::add('scan_ip', 'debug', 'Bridges fieldSuggestions :. ' . $e->getMessage());
        }
        $return = array();
        foreach ($keys as $key => $values) {
            $values = array_values(array_unique(array_filter($values, function ($v) { return $v !== ''; })));
            $return[] = array("key" => (string) $key, "values" => array_slice($values, 0, 8), "ip" => (self::extractIp(implode(' ', $values)) !== NULL));
        }
        usort($return, function ($a, $b) { return strcasecmp($a["key"], $b["key"]); });
        return $return;
    }
}
