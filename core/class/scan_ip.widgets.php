<?php

/**
 * Description of scan_ip
 *
 * @author Ynats
 */

require_once __DIR__ . "/../../../../plugins/scan_ip/core/class/scan_ip.require_once.php";

class scan_ip_widgets extends eqLogic {
    
    public static function getWidgetType($_eq) {
        return $_eq->getConfiguration('type_widget', 'normal');
    }
    

}
