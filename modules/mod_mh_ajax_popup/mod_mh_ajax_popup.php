<?php
/**
 * @author     mediahof, Kiel-Germany
 * @link       http://www.mediahof.de
 * @copyright  Copyright (C) 2011 - 2014 mediahof. All rights reserved.
 * @license    GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

defined('_JEXEC') or die;

require_once dirname(__FILE__) . '/helper.php';

$mhap = new mod_mh_ajax_popup($params, $module);

if (empty($mhap->content)) {
    return;
}

JFactory::getDocument()->addScript(JURI::base(true) . '/modules/' . $module->module . '/js/mhap.js');

require JModuleHelper::getLayoutPath($module->module, $params->get('layout', 'default'));