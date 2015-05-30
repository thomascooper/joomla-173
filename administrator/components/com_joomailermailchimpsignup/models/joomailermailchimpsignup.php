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

if (!class_exists('MCAPI')) {
	require_once(JPATH_ADMINISTRATOR . '/components/com_joomailermailchimpintegration/libraries/MCAPI.class.php');
}

class JoomailerMailchimpSignupModelJoomailerMailchimpSignup extends jmsModel {

	public function mcsave($ext) {
		$api = $this->getApiInstance();

		//Create the api object
		$params = JComponentHelper::getParams('com_joomailermailchimpintegration');
		$MCapi  = $params->get('params.MCapi');
		$api = new joomlamailerMCAPI($MCapi);

		$db = JFactory::getDBO();
		$email = JRequest::getVar('email');
		$name = JRequest::getVar('name');
		$name = explode(' ', $name);
		$fname = $name[0];
		$lname = (isset($name[1])) ? $name[1] : '';

		//Get the ID of the mailing list
        $plugin = JPluginHelper::getPlugin('system', 'joomailermailchimpsignup');
        $pluginParams = new JRegistry($plugin->params);
        $listId = $pluginParams->get('listid');
        $dbext = ($ext == 'com_community') ? 'JS' : 'CB';

		//Get the fields associated with the extension
        $query = $db->getQuery(true)
            ->select($db->qn('*'))
            ->from($db->qn('#__joomailermailchimpintegration_custom_fields'))
            ->where($db->qn('framework') . ' = ' . $db->q($dbext))
            ->where($db->qn('listID') . ' = ' . $db->q($listId));
		$db->setQuery($query);
		$fields = $db->loadObjectList();
		$merge_vars = $this->getData($fields, $dbext);
		$others = array('FNAME' => $fname, 'LNAME' => $lname, 'INTERESTS' => '');
		$merge_vars = array_merge($others, $merge_vars);
		$email_type = '';
		$replace_interests = true;

		$retval = $api->listUpdateMember($listId, $email, $merge_vars, $email_type, $replace_interests);

		if ($api->errorCode) {
			echo "Unable to update member info!\n";
			echo "\tCode=" . $api->errorCode . "\n";
			echo "\tMsg=" . $api->errorMessage . "\n";
		} else {
		    if ($ext == 'com_community') {
    			//echo "Returned: ".$retval."\n";
    			require_once(JPATH_ADMINISTRATOR . '/components/com_community/controllers/controller.php');
    			require_once(JPATH_ADMINISTRATOR . '/components/com_community/controllers/users.php');
    			require_once(JPATH_ADMINISTRATOR . '/components/com_community/models/users.php');
    			$lang = JFactory::getLanguage();
    			$lang->load('com_community', JPATH_ADMINISTRATOR);
    			$userController = new CommunityControllerUsers();
    			$userController->execute('save');
    			$userController->redirect();
            } else if ($ext == 'com_comprofiler') {
                $GLOBALS['_JREQUEST']['option'] = array('DEFAULTCMD0' => 'com_comprofiler');
                require_once(JPATH_ADMINISTRATOR . '/components/com_comprofiler/admin.comprofiler.controller.php');
                saveUser($ext);
            }
		}
	}

	/**
	 * Method to create Mailchimp api instance
	 */
	private function getApiInstance() {
		$params = JComponentHelper::getParams('com_joomailermailchimpintegration');
		$MCapi  = $params->get('params.MCapi');

		return new joomlamailerMCAPI($MCapi);
	}

	/**
	 * Method to get merge fields and interests from form
	 */
	function getData($fields, $ext) {
		$merges = array();
		$groupings = array();
        $db = JFactory::getDBO();

        if ($ext == 'JS') {
          $table = 'community_fields';
          $field = 'id';
          $suffix = 'field';
        } else {
          $table = 'comprofiler_fields';
          $field = 'name';
          $suffix = '';
        }

		if ($fields) {
			foreach ($fields as $f) {
				$groups = '';
				$val = JRequest::getVar($suffix . $f->dbfield);
				if ($f->type == 'field') {
                    $query = $db->getQuery(true)
                        ->select($db->qn('type'))
                        ->from($db->qn('#__' . $table))
                        ->where($db->qn($field) . ' = ' . $db->q($f->dbfield));
					$db->setQuery($query);
					$type = $db->loadResult();
					if ($type == 'date' && $ext == 'JS') {
						$merges[$f->grouping_id] = $val[2] . '-' . $val[1] . '-' . $val[0];
					} else if ($type == 'date' && $ext == 'CB') {
						$merges[$f->grouping_id] = substr($val, 3, 2) . '-' . substr($val, 0, 2) . '-' . substr($val, 6, 4);
					} else {
						$merges[$f->grouping_id] = $val;
					}
				} else if ($f->type='group') {
					if (is_array($val)) {
						foreach ($val as $v) {
							$groups .= $v . ',';
						}
						$groups = substr($groups, 0, -1);
					} else {
						$groups .= $val;
					}
					$groupings[$f->name] =  array('name' => $f->name, 'id' => $f->grouping_id, 'groups' => $groups);
				}
			}
		}

		return array_merge($merges, array('GROUPINGS' => $groupings));
	}
}
