<?php
/**
 * Copyright (C) 2015  freakedout (www.freakedout.de)
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
**/

// no direct access
defined('_JEXEC') or die('Restricted Access');

JLoader::register('jmsModel', JPATH_COMPONENT . '/models/jmsModel.php');
JLoader::register('jmsController', JPATH_COMPONENT . '/controllers/jmsController.php');

require_once(JPATH_COMPONENT_ADMINISTRATOR . '/controller.php' );

if($controller = JRequest::getWord('controller')) {
   $path = JPATH_COMPONENT_ADMINISTRATOR . '/controllers/' . $controller . '.php';
   if (file_exists($path)) {
       require_once($path);
   } else {
       $controller = '';
   }
}

$classname = 'JoomailerMailchimpSignupController' . $controller;
$controller = new $classname();

$controller->execute(JRequest::getVar('task'));
$controller->redirect();
