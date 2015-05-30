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

class joomailermailchimpintegrationViewInstaller extends jmView {

    public function display($tpl = null) {
        $db = JFactory::getDbo();
        $query = $db->getQuery(true)
            ->select($db->qn('manifest_cache'))
            ->from('#__extensions')
            ->where($db->qn('type') . ' = ' . $db->q('component'))
            ->where($db->qn('element') . ' = ' . $db->q('com_joomailermailchimpintegration'));
        $db->setQuery($query);
        $manifest = json_decode($db->loadResult());

        include_once(JPATH_ROOT . '/administrator/components/com_joomailermailchimpintegration/installer/installer.php');
        exit;
    }
}