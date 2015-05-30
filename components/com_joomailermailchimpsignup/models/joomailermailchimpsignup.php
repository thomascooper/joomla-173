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

require_once('jmsModel.php');
if (!class_exists('joomlamailerMCAPI')) {
    require_once(JPATH_ADMINISTRATOR . '/components/com_joomailermailchimpintegration/libraries/MCAPI.class.php');
}

class JoomailerMailchimpSignupModelJoomailerMailchimpSignup extends jmsModel {

    public function register_save($userId = '') {
        $option = JRequest::getCmd('option');
        $extension = JRequest::getVar('component');

        $db = JFactory::getDBO();
        $user = JFactory::getUser($userId);
        $lang = JFactory::getLanguage();
        $task = JRequest::getVar('oldtask');

        // com_users
        if ($extension == 'com_users') {
            $jform = JRequest::getVar('jform');
            $name = $jform['name'];
            $email_address = $jform['email1'];

            $name = explode(' ', $name);
            $controller = '/controller.php';
            $cname = 'UsersController';
            $lang->load($extension, JPATH_BASE);
        // Jomsocial
        } else if ($extension == 'com_community') {
            if ($user->email) {
                $email_address = $user->email;
                $name = explode(' ', $user->name);
                $_POST['view'] = 'profile';
            } else {
                $query = $db->getQuery(true)
                    ->select($db->qn('token'))
                    ->from($db->qn('#__community_register_auth_token'))
                    ->where($db->qn('auth_key') . ' = ' . $db->q(JRequest::getVar('authkey')));
                $db->setQuery($query);
                $token = $db->loadResult();
                if (!$token) {
                    return;
                }
                $query = $db->getQuery(true)
                    ->select($db->qn(array('name', 'email')))
                    ->from($db->qn('#__community_register'))
                    ->where($db->qn('token') . ' = ' . $db->q($token));
                $db->setQuery($query);
                $details = $db->loadAssocList();
                $name = explode(' ', $details[0]['name']);
                $email_address = $details[0]['email'];
            }

            $cntrllr = JRequest::getVar('cntrllr');
            $controller = '/controllers/' . $cntrllr . '.php';
            $cname = 'Community' . strtoupper($cntrllr[0]) . substr($cntrllr, 1) . 'Controller';
            $this->_name = str_replace('com_', '', $extension);

            require_once(JPATH_SITE . '/components/com_community/defines.community.php');
            require_once(JPATH_SITE . '/components/com_community/libraries/core.php');
            require_once(JPATH_SITE . '/components/com_community/libraries/template.php');
            require_once(JPATH_SITE . '/components/com_community/controllers/controller.php');
            require_once(JPATH_SITE . '/components/com_community/views/views.php');
            require_once(JPATH_SITE . '/components/com_community/views/register/view.html.php');
            require_once(JPATH_SITE . '/components/com_community/views/profile/view.html.php');
            $lang->load($extension, JPATH_BASE);
            $_POST['option'] = $extension;
            $_POST['task'] = $task;
            $view = JRequest::getCmd('view');

        // Community Builder
        } else if ($extension == 'com_comprofiler') {
            $name[0] = JRequest::getVar('firstname');
            $name[1] = JRequest::getVar('lastname');
            $name = explode(' ', JRequest::getVar('name'));
            $email_address = JRequest::getVar('email');
            $controller = '/comprofiler.php';
            $cname = 'Comprofiler';
            $_POST['option'] = $extension;
            $GLOBALS['_JREQUEST']['option'] = array('DEFAULTCMD0' => 'com_comprofiler');
            $cbtask = JRequest::getVar('oldtask');
        } else if ($extension == 'com_virtuemart') {
            $name[0] = JRequest::getVar('first_name');
            $name[1] = JRequest::getVar('middle_name');
            $name[2] = JRequest::getVar('last_name');
            $email_address = JRequest::getVar('email');
        }
        $email_address_old = JRequest::getVar('oldEmail', $email_address);

        $fname = $name[0];
        $lname = '';
        if (count($name) > 1) {
            unset($name[0]);
            $lname = implode(' ', $name);
        }

        // Create the api object
        $params = JComponentHelper::getParams('com_joomailermailchimpintegration');
        $MCapi = $params->get('params.MCapi');
        $api = new joomlamailerMCAPI($MCapi);

        // Get the ID of the mailing list
        $plugin = JPluginHelper::getPlugin('system', 'joomailermailchimpsignup');
        $pluginParams = new JRegistry($plugin->params);
        $listId = $pluginParams->get('listid');

        // Check if the user is already activated and subscribed
        $sub = false;
        if (!$user->activation && $user->email) {
            $userlists = $api->listsForEmail($email_address_old);
            if ($userlists && in_array($listId, $userlists)) {
                $sub = true;
            }
        }

        // User wishes to subscribe/update interests
        if (JRequest::getVar('newsletter', 0)) {
            $double_optin = false;
            $update_existing = false;
            $replace_interests = false;
            $send_welcome = false;

            // Get merge vars from API
            $fields = $api->listMergeVars($listId);
            $fieldids = $pluginParams->get('fields');
            $key = 'tag';
            $val = 'name';

            // Get interests from API
            $interests = $api->listInterestGroupings($listId);
            $interestids = $pluginParams->get('interests');
            $groupings = $merges = array();

            if ($extension == 'com_users') {
                // Default registration
                if ($fields) {
                    foreach ($fields as $f) {
                        if (JRequest::getVar('mf_' . $f['tag'], 0)) {
                            $val = JRequest::getVar('mf_' . $f['tag']);
                            $merges[$f['tag']] = $val;
                        }
                    }
                }

                if ($interests) {
                    foreach ($interests as $i) {
                        $postData = JRequest::getVar('interest_' . $i['id']);
                        if ($postData) {
                            $groups = '';
                            if (is_array($postData)) {
                                foreach ($postData as $g) {
                                    //var_dump($i['groups']);die;
                                    foreach ($i['groups'] as $gg) {
                                        if ($g == $gg['bit']) {
                                            $groups .= $gg['name'].',';
                                        }
                                    }
                                }
                                $groups = substr($groups, 0, -1);
                                $groupings[$i['name']] = array('name' => $i['name'], 'id' => $i['id'], 'groups' => $groups);

                            } else {
                                foreach ($i['groups'] as $gg) {
                                    if (JRequest::getVar('interest_' . $i['id']) == $gg['bit']) {
                                        $groups .= $gg['name'];
                                    }
                                }
                                $groupings[$i['name']] = array(
                                    'name' => $i['name'],
                                    'id' => $i['id'],
                                    'groups' => $groups
                                );
                            }
                        }
                    }
                }

            } else if ($extension == 'com_comprofiler' || $extension == 'com_community' || $extension == 'com_virtuemart') {

                // Get custom fields
                $query = $db->getQuery(true)
                    ->select($db->qn(array('dbfield', 'grouping_id', 'type', 'framework'), array('dbfield', 'gid', 'type', 'framework')))
                    ->from($db->qn('#__joomailermailchimpintegration_custom_fields'))
                    ->where($db->qn('listID') . ' = ' . $db->q($listId));
                $db->setQuery($query);
                $customfields = $db->loadAssocList();

                if ($customfields) {
                    // loop over groupings
                    if ($interests) {
                        foreach ($interests as $i) {
                            foreach ($customfields as $cf) {
                                if ($cf['type'] == 'group') {
                                    if ($i['id'] == $cf['gid']) {
                                        $groups = '';
                                        if (($extension == 'com_comprofiler' && $cf['framework'] == 'CB')
                                            || ($extension == 'com_virtuemart' && $cf['framework'] == 'VM')){
                                            $field = JRequest::getVar($cf['dbfield']);
                                        } else {
                                            if (JRequest::getVar('field' . $cf['dbfield'], 0)) {
                                                $field = JRequest::getVar('field' . $cf['dbfield']);
                                            }
                                        }
                                        if (isset($field) && is_array($field)) {
                                            foreach ($field as $g) {
                                                foreach ($i['groups'] as $gg) {
                                                    if ($g == $gg['name']) {
                                                        $groups .= $gg['name'].',';
                                                    }
                                                }
                                            }
                                            $groups = substr($groups, 0, -1);
                                        } else {
                                            foreach ($i['groups'] as $gg) {
                                                if (isset($field) && $field == $gg['name']) {
                                                    $groups .= $gg['name'];
                                                }
                                            }
                                        }
                                        $groupings[$i['name']] = array('name' => $i['name'], 'id' => $i['id'], 'groups' => $groups);
                                    }
                                }
                            }
                        }
                    }
                }

                // loop over merge vars
                if ($fields) {
                    foreach ($fields as $f) {
                        foreach ($customfields as $cf) {
                            if ($cf['type'] == 'field') {
                                if($f['tag'] == strtoupper($cf['gid'])) {
                                    if (($extension == 'com_comprofiler' && $cf['framework'] == 'CB')
                                        || ($extension == 'com_virtuemart' && $cf['framework'] == 'VM')) {
                                        if ($f['field_type'] == 'date') {
                                            if ($extension == 'com_virtuemart') {
                                                $valDay = JRequest::getVar('birthday_selector_day');
                                                $valMonth = JRequest::getVar('birthday_selector_month');
                                                $valYear = JRequest::getVar('birthday_selector_year');
                                                $val = $valMonth . '/' . $valDay . '/' . $valYear;
                                            } else {
                                                $val = JRequest::getVar($cf['dbfield']);
                                            }
                                            $merges[$f['tag']] = substr($val, 3, 2) . '-' . substr($val, 0, 2) .
                                                '-' . substr($val, 6, 4);
                                        } else {
                                            $val = JRequest::getVar($cf['dbfield']);
                                            $merges[$f['tag']] = $val;
                                        }
                                    } else {
                                        if (JRequest::getVar('field' . $cf['dbfield'], 0)) {
                                            $val = JRequest::getVar('field' . $cf['dbfield']);
                                            if ($f['field_type'] == 'date') {
                                                $merges[$f['tag']] = $val[2] . '-' . $val[1] . '-' . $val[0];
                                            } else {
                                                $merges[$f['tag']] = $val;
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }

            // If this is a new user then just store details now and subscribe the user later at activation
            if ($user->activation) {
                $merges_string = '';
                if ($merges) {
                    foreach ($merges as $k => $v) {
                        $merges_string .= "name=" . $k . "\n";
                        if (is_array($v)) {
                            $merges_string .= "value=";
                            foreach ($v as $vv) {
                                $merges_string .= $vv . "||";
                            }
                        } else {
                            $merges_string .= "value=" . $v;
                        }
                        $merges_string .= "\n\n";
                    }
                }

                $groupings_string = '';
                foreach ($groupings as $g) {
                    $groupings_string .= 'name=' . $g['name'] . "\n";
                    $groupings_string .= 'id=' . $g['id'] . "\n";
                    $groupings_string .= 'groups=' . $g['groups'] . "\n" . '||' . "\n";
                }
                $groupings_string = substr($groupings_string, 0, -3);
                $merges_string = substr($merges_string, 0, -2);

                $query = $db->getQuery(true)
                    ->insert($db->qn('#__joomailermailchimpsignup'))
                    ->set(array(
                        $db->qn('fname') . ' = ' . $db->q($fname),
                        $db->qn('lname') . ' = ' . $db->q($lname),
                        $db->qn('email') . ' = ' . $db->q($email_address),
                        $db->qn('groupings') . ' = ' . $db->q($groupings_string),
                        $db->qn('merges') . ' = ' . $db->q($merges_string)
                    ));
                $db->setQuery($query)->execute();

            //Otherwise workout whether to update or subscribe the user
            } else {
                $merge_vars = array(
                    'FNAME' => $fname,
                    'LNAME' => $lname,
                    'INTERESTS' => '',
                    'GROUPINGS' => $groupings
                );

                //Get the users ip address unless the admin is saving his profile in backend
                $app = JFactory::getApplication();
                if ($app->isSite()) {
                    $merge_vars['OPTINIP'] = $this->get_ip_address();
                }

                $merge_vars = array_merge($merge_vars, $merges);
                $email_type = '';

                if (!$sub) {
                    //Subscribe the user
                    $api->listSubscribe($listId, $email_address, $merge_vars, $email_type, $double_optin, $update_existing, $replace_interests, $send_welcome);
                    $query = $db->getQuery(true)
                        ->insert($db->qn('#__joomailermailchimpintegration'))
                        ->set(array(
                            $db->qn('userid') . ' = ' . $db->q($user->id),
                            $db->qn('email') . ' = ' . $db->q($email_address),
                            $db->qn('listid') . ' = ' . $db->q($listId)
                        ));
                    $db->setQuery($query)->execute();
                } else {
                    //Update the users subscription
                    // email address changed in CB?
                    if (in_array($extension, array('com_user', 'com_users', 'com_community', 'com_comprofiler', 'com_virtuemart'))
                        && $email_address != $email_address_old) {
                        // update local database entry
                        $query = $db->getQuery(true)
                            ->update($db->qn('#__joomailermailchimpintegration'))
                            ->set($db->qn('email') . ' = ' . $db->q($email_address))
                            ->where($db->qn('email') . ' = ' . $db->q($email_address_old))
                            ->where($db->qn('listid') . ' = ' . $db->q($listId));
                        $db->setQuery($query)->execute();
                        // add new email address to merge vars array
                        $merge_vars['EMAIL'] = $email_address;
                    }
                    $api->listUpdateMember($listId, $email_address_old, $merge_vars, $email_type, true);
                }
            }

        //User wishes to unsubscribe
        } else if (!JRequest::getVar('newsletter', 0) && $sub) {
            $api->listUnsubscribe($listId, $email_address, false, false, false);
            // remove local database entry
            $query = $db->getQuery(true)
                ->delete($db->qn('#__joomailermailchimpintegration'))
                ->where($db->qn('email') . ' = ' . $db->q($email_address))
                ->where($db->qn('listid') . ' = ' . $db->q($listId));
            $db->setQuery($query)->execute();
        }

        if ($api->errorCode && in_array($api->errorCode, array(211, 215)) === false) {
            echo "Unable to load listSubscribe()!\n";
            echo "\tCode=" . $api->errorCode . "\n";
            echo "\tMsg=" . $api->errorMessage . "\n";
        } else {
            if ($option == 'com_users' || in_array($extension, array('com_virtuemart', 'com_comprofiler')) ||
                ($extension == 'com_community' && $task != 'edit' )) {
                // we're done at this point
                return;

            // route back to original controller task
            } else if ($extension != 'com_comprofiler') {
                if ($extension == 'com_community') {
                    $controllerpath = JPATH_SITE . '/components/' . $extension . $controller;
                    require_once($controllerpath);
                    $userController = new $cname(array('base_path' => JPATH_ROOT . '/components/' . $extension, 'name' => $view));
                    $this->_name = str_replace('com_', '', $extension);
                } else {
                    JRequest::setVar('task', $task);
                    JRequest::setVar('option', 'com_users');
                    $task = explode('.', $task);
                    $controllerpath = JPATH_SITE . '/components/com_users/controller.php';
                    require_once($controllerpath);
                    $controllerpath = JPATH_SITE . '/components/com_users/controllers/' . $task[0] . '.php';
                    require_once($controllerpath);
                    $userController = JController::getInstance('Users', array(
                        'base_path' => JPATH_ROOT . '/components/' . $extension,
                        'name' => str_replace('com_', '', $extension)
                    ));
                    $task = $task[1];
                }

                $userController->execute($task);
                $userController->redirect();
            } else {
                $controllerpath = JPATH_SITE . '/components/' . $extension . $controller;
                require_once($controllerpath);
                if ($cbtask == 'saveUserEdit') {
                    userSave(JRequest::getVar('option'), JRequest::getVar('id'));
                } else {
                    saveRegistration(JRequest::getVar('option'));
                }
            }
        }
    }

    function get_ip_address() {
        foreach (array('HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_FORWARDED', 'HTTP_X_CLUSTER_CLIENT_IP', 'HTTP_FORWARDED_FOR', 'HTTP_FORWARDED', 'REMOTE_ADDR') as $key) {
            if (array_key_exists($key, $_SERVER) === true) {
                foreach (explode(',', $_SERVER[$key]) as $ip) {
                    if (filter_var($ip, FILTER_VALIDATE_IP) !== false) {
                        return $ip;
                    }
                }
            }
        }
    }
}
