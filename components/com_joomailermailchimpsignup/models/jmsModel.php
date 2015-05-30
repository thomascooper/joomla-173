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
defined('_JEXEC') or die('Restricted access');

jimport('joomla.application.component.model');

if (version_compare(JVERSION, '3.0', 'ge')) {
    class jmsModel extends JModelLegacy {
        public static function addIncludePath($path = '', $prefix = '') {
            return parent::addIncludePath($path, $prefix);
        }
    }
} else {
    class jmsModel extends JModel {
        public static function addIncludePath($path = '', $prefix = '') {
            return parent::addIncludePath($path, $prefix);
        }
    }
}