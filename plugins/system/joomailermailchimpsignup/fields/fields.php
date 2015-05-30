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

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die('Restricted access');

class JFormFieldFields extends JFormField {

    public function getInput() {
        jimport( 'joomla.filesystem.file' );
        $mainframe = JFactory::getApplication();
        if (!JFile::exists(JPATH_ADMINISTRATOR . '/components/com_joomailermailchimpintegration/libraries/MCAPI.class.php')) {
            $mainframe->redirect('index.php', JText::_('JM_INSTALLJOOMAILER'), 'error');
        } else {
            $listid = $this->form->getValue('listid', 'params');
            require_once(JPATH_ADMINISTRATOR . '/components/com_joomailermailchimpintegration/libraries/MCAPI.class.php');
            $params = JComponentHelper::getParams( 'com_joomailermailchimpintegration' );
            $MCapi = $params->get('params.MCapi');
            $api = new joomlamailerMCAPI($MCapi);
            $fields = $api->listMergeVars($listid);

            $key = 'tag';
            $val = 'name';
            $options = false;

            if ($fields) {
                foreach ($fields as $field) {
                    if ($field['field_type'] != 'email') {
                        $options[]= array($key => $field[$key], $val => $field[$val]);
                    }
                }
            }

            $attribs = 'multiple="multiple"';
            if ($options){
                $content = JHtml::_('select.genericlist', $options, 'jform[params][fields][]', $attribs, $key, $val, $this->value, $this->id);
            } else {
                $content = '<div style="float:left;">' . JText::_('JM_NO_MERGE_VARS') . '</div>';
            }

            return $content;
        }
    }
}