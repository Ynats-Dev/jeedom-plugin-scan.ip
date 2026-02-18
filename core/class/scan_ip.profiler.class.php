<?php
  
class Profiler {
    private static $stack = [];
    private static $depth = 0;
  
    /**
     * à appeller en début de méthode pour laquelle on veut mesurer le temps d'exécution.
     * Il faut utiliser le même nom que celui passé à stop()
     * Exemple : Profiler::start(__METHOD__);
     * */
    public static function start($name) {
        $indent = str_repeat("  ", self::$depth);
        log::add('scan_ip', 'debug', "[Profilage] " . $indent . "START : " . $name);
        
        self::$stack[] = [
            'name' => $name,
            'start' => microtime(true),
            'children' => [] 
        ];

        self::$depth++;
    }

    /**
     * à appeller en fin de méthode pour laquelle on veut mesurer le temps d'exécution.
     * Optionnel : $showSummary = true pour afficher un résumé des sous-appels effectués par la méthode.
     * Il faut utiliser le même nom que celui passé à start()
     * Exemple : Profiler::stop(__METHOD__);
     * */
    public static function stop($name, $showSummary = false) {
        $endTime = microtime(true);
        $current = array_pop(self::$stack);
        
        if (!$current || $current['name'] !== $name) {
            return null;
        }

        self::$depth--;
        if (self::$depth < 0) self::$depth = 0;
        
        $duration = $endTime - $current['start'];
        $indent = str_repeat("  ", self::$depth);
        
        log::add('scan_ip', 'debug', "[Profilage] " . $indent . "STOP  : $name : " . round($duration, 4) . "s");

        // GESTION DU RÉSUMÉ GLOBAL
        if ($showSummary && !empty($current['children'])) {
            $summaryStr = "[Profilage Résumé Global $name] :";
            // Tri par temps décroissant pour voir le plus lourd en premier
            uasort($current['children'], function($a, $b) {
                return $b['time'] <=> $a['time'];
            });
            
            foreach ($current['children'] as $childName => $stats) {
                $summaryStr .= "\n[Profilage Résumé Global $name]   -> " . $childName . " | Appel(s): " . $stats['count'] . " | Total: " . round($stats['time'], 4) . "s";
            }
            log::add('scan_ip', 'debug', $summaryStr);
        }

        // TRANSMISSION RÉCURSIVE AU PARENT
        $stackSize = count(self::$stack);
        if ($stackSize > 0) {
            $parentIdx = $stackSize - 1;
            
            // 1. On transmet l'appel actuel au parent
            self::updateStats(self::$stack[$parentIdx]['children'], $name, $duration);
            
            // 2. NOUVEAUTÉ : On transmet aussi tous les sous-appels collectés par l'appel actuel au parent
            foreach ($current['children'] as $childName => $childStats) {
                self::updateStats(self::$stack[$parentIdx]['children'], $childName, $childStats['time'], $childStats['count']);
            }
        }

        return $duration;
    }

    // Petite fonction utilitaire pour centraliser la mise à jour des stats
    private static function updateStats(&$target, $name, $time, $count = 1) {
        if (!isset($target[$name])) {
            $target[$name] = ['count' => 0, 'time' => 0];
        }
        $target[$name]['count'] += $count;
        $target[$name]['time'] += $time;
    }
}

