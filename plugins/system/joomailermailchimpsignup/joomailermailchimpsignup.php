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

jimport('joomla.plugin.plugin');
jimport('joomla.filesystem.file');

if (!class_exists('joomlamailerMCAPI')) {
    require_once(JPATH_ADMINISTRATOR . '/components/com_joomailermailchimpintegration/libraries/MCAPI.class.php');
}

class plgSystemJoomailerMailchimpSignup extends JPlugin {

    private static $MC = null;

    public function onAfterRender() {
        if (!JFile::exists(JPATH_ADMINISTRATOR . '/components/com_joomailermailchimpintegration/libraries/MCAPI.class.php')) {
            return;
        }

        $listId = $this->params->get('listid');
        if (!$listId) {
            return;
        }

        $option	= JRequest::getCmd('option');
        $view	= JRequest::getVar('view');
        $layout	= JRequest::getVar('layout', '');
        $task	= JRequest::getVar('task', 0, 'get', 'string');
        $user	= JFactory::getUser();

        if (JFactory::getApplication()->isSite()) {

            if (JFile::exists(JPATH_ADMINISTRATOR . '/components/com_k2/admin.k2.php')) {
                $k2params = JComponentHelper::getParams('com_k2');
                $k2plugin = $k2params->get('K2UserProfile');
            } else {
                $k2plugin = false;
            }

            if (($option == 'com_users' && ($view == 'registration' || ($view == 'profile' && $layout == 'edit' ))) ||
                ($option == 'com_community'   && ($task == 'registerProfile' || $view == 'profile' || $view == 'register')) ||
                ($option == 'com_comprofiler' && ($task == 'registers' || strtolower($task) == 'userdetails' )) ||
                ($option == 'com_virtuemart'  && JRequest::getVar('page') == 'checkout.index')) {

                $body = JResponse::getBody();
                $lang = JFactory::getLanguage();
                $lang->load('plg_system_joomailermailchimpsignup', JPATH_ADMINISTRATOR);

                $api = $this->getApiInstance();

                $mergefields = $api->listMergeVars($listId);
                $mergeids = $this->params->get('fields');
                $key = 'tag';
                $val = 'name';
                $ihtml = '';

                $interests = $api->listInterestGroupings($listId);
                $interestids = $this->params->get('interests');
                $key = 'bit';
                $val = 'name';

                $checked = '';
                $groupings = '';
                // Check if the user is subscribed
                if ($user->email) {
                    $userlists = $api->listsForEmail($user->email);

                    if ($userlists && in_array($listId, $userlists)) {
                        $checked = 'checked="checked"';
                    }
                    $userinfo = $api->listMemberInfo($listId, $user->email);
                    $usermerges = $userinfo['merges'];

                    $groupings = (isset($userinfo['merges']['GROUPINGS'])) ? $userinfo['merges']['GROUPINGS'] : '';
                } else {
                    $usermerges = array();
                }

                // For the standard registration
                if ($option == 'com_users' && ($view == 'registration' || ($view == 'profile' && $layout == 'edit' ))) {

                    if ($mergefields) {
                        $ihtml = $this->buildMergeVarsHTML($mergefields, $usermerges, $mergeids);
                    }

                    if ($interests) {
                        // Build the HTML for the interests
                        $ihtml .= $this->buildInterestsHTML($interests, $groupings, $interestids);
                    }

                    JHTML::_('behavior.calendar');

                    if ($view == 'registration') {
                        $pattern = '#(</fieldset>[^>]+>[^<]+<button type="submit")#';
                        $replacement = '<div class="control-group"><div class="control-label">';
                        $replacement.= '<label for="newsletter">' . JText::_('JM_NEWSLETTERSIGNUP') . '</label></div>';
                        $replacement.= '<div class="controls">';
                        $replacement.= '<input type="checkbox" name="newsletter" id="newsletter" value="1" />';
                        $replacement.= '</div></div>';
                        $replacement.= $ihtml;
                        $replacement.= '$1';

                        $body = preg_replace($pattern, $replacement, $body);
                        //$body = str_replace('<input type="hidden" name="task" value="register_save" />','<input type="hidden" name="option" value="com_joomailermailchimpsignup" /><input type="hidden" name="component" value="com_user" /><input type="hidden" name="task" value="register_save" /><input type="hidden" name="oldtask" value="register_save" />',$body);
                    } else {
                        $pattern = '#(<input type="email" name="jform\[email2\]"[^>]+>[^>]+>[^>]+>)#i';
                        $replacement = "\t$1\n";
                        $replacement.= "\t\t<div class='control-group'>\n";
                        $replacement.= "\t\t\t<div class='control-label'><label for='newsletter'>".JText::_('JM_NEWSLETTERSIGNUP')."</label></div>\n";
                        $replacement.= "\t\t\t<div class='controls'>\n";
                        $replacement.= "\t\t\t\t<input type='checkbox' name='newsletter' id='newsletter' value='1' ".$checked."/><input type='hidden' name='oldEmail' value='".$user->email."' />\n";
                        $replacement.= "\t\t\t</div>\n";
                        $replacement.= "\t\t</div>\n";
                        $replacement.= $ihtml;

                        $body = preg_replace($pattern, $replacement, $body);
                    }
                } else if ($option == 'com_community') {
                    if ($task == 'registerProfile' || $view == 'register') {
                        $pattern = '#(<li>[^>]+>[^<]+<div id="cwin-wait" style="display:none;"></div>' .
                            '[^<]+<input class="[^"]+" type="submit" id="btnSubmit" value="' .
                            preg_quote(JText::_('COM_COMMUNITY_REGISTER')) . '" name="submit">[^>]+>[^>]+>)#';

                        $replacement = '<li><div class="ctitle"><h2>' . JText::_('JM_NEWSLETTER') . '</h2></div>';
                        $replacement.= '<label for="newsletter" class="form-label">' . JText::_('JM_NEWSLETTERSIGNUP') . '</label>';
                        $replacement.= '<div class="form-field"><input type="checkbox" name="newsletter" id="newsletter" value="1" /></div>';
                        $replacement.= '</li>$1';

                        $body = preg_replace($pattern, $replacement, $body);
                    } else if ($view == 'profile' && $task == 'edit') {
                        $pattern = '/<table class="formtable" cellspacing="1" cellpadding="0" style="width: 98%;">(\s|\n)*<tbody>(\s|\n)*<tr>(\s|\n)*<td class="key"><\/td>(\s|\n)*<td class="value">(\s|\n)*<input type="hidden" name="action" value="save" \/>/';

                        $pattern = '#(<li>[^>]+>[^<]+<input type="submit" name="frmSubmit" onclick="submitbutton\(\); return false;" class="cButton cButton-Blue" value="' .
                            preg_quote(JText::_('COM_COMMUNITY_SAVE_BUTTON')) . '" />[^>]+>[^>]+>)#';

                        $replacement = '<li><div class="ctitle"><h2>' . JText::_('JM_NEWSLETTER') . '</h2></div>';
                        $replacement.= '<label for="newsletter" class="form-label">' . JText::_('JM_NEWSLETTERSIGNUP') . '</label>';
                        $replacement.= '<div class="form-field"><input type="checkbox" name="newsletter" id="newsletter" value="1"' . $checked . ' /></div>';
                        $replacement.= '<input type="hidden" name="option" value="com_joomailermailchimpsignup" />';
                        $replacement.= '<input type="hidden" name="component" value="com_community" />';
                        $replacement.= '<input type="hidden" name="task" value="register_save" />';
                        $replacement.= '<input type="hidden" name="cntrllr" value="profile" />';
                        $replacement.= '<input type="hidden" name="oldtask" value="edit" />';
                        $replacement.= '<input type="hidden" name="action" value="save" />';
                        $replacement.= '</li>$1';

                        $body = preg_replace($pattern, $replacement, $body);
                        $body = str_replace('action="/joomla/index.php?option=com_community&amp;view=profile&amp;task=edit&amp;Itemid=53"', 'action=""', $body);
                    }
                } elseif ($option =='com_comprofiler') {
                    if ($task == 'registers') {
                        $pattern = '#<tr class="cbRegistrationButtonRow">.*?</tr>#is';
                        $replacement = '<tr class="sectiontableentry1 cb_table_line">';
                        $replacement.= '<td class="titleCell"><label for="newsletter">';
                        $replacement.= JText::_('JM_NEWSLETTERSIGNUP');
                        $replacement.= '</label></td>';
                        $replacement.= '<td>';
                        $replacement.= '<input type=\'checkbox\' name=\'newsletter\' id=\'newsletter\' value=\'1\' />';
                        $replacement.= '</td>';
                        $replacement.= '</tr>';
                        $replacement.= '$0';

                        $body = preg_replace($pattern, $replacement, $body);
                    } else if (strtolower($task) == 'userdetails') {
                        $pattern = '#</table></div></div></div></div><div class="form-group cb_form_line#';
                        $replacement = '<tr class="sectiontableentry1 cb_table_line">';
                        $replacement.= '<td class="titleCell"><label for="newsletter">';
                        $replacement.= JText::_('JM_NEWSLETTERSIGNUP');
                        $replacement.= '</label></td>';
                        $replacement.= '<td>';
                        $replacement.= '<input type="checkbox" name="newsletter" id="newsletter" value="1" ' . $checked . ' />';
                        $replacement.= '</td>';
                        $replacement.= '</tr>';
                        $replacement.= '$0';
                        $body = preg_replace($pattern, $replacement, $body);

                        $pattern = '#<input type="hidden" name="id" value="\d+" />#';
                        $replacement = '$0<input type="hidden" name="component" value="com_comprofiler" />'
                            . '<input type="hidden" name="oldEmail" value="' . $user->email . '" />';
                        $body = preg_replace($pattern, $replacement, $body);
                    }
                } else if ($option == 'com_virtuemart') {
                    if ($mergefields) {
                        $ihtml = $this->buildMergeVarsHTML($mergefields, $usermerges, $mergeids);
                    }
                    if ($interests) {
                        //Build the HTML for the interests
                        $ihtml .= $this->buildInterestsHTML($interests, $groupings, $interestids);
                    }

                    $pattern = '/<\/div>(\s|\n)*<div align="center">(\s|\n)*<input type="hidden" name="remember" value="yes" \/>/is';
                    $replacement = "\t<fieldset>\n";
                    $replacement.= "\t\t<legend class='sectiontableheader'>".JText::_('JM_NEWSLETTER')."</legend>\n";
                    $replacement.= "\t\t<div class='formLabel'>\n";
                    $replacement.= "\t\t\t<label for='newsletter'>".JText::_('JM_NEWSLETTERSIGNUP')."</label>\n";
                    $replacement.= "\t\t</div>\n";
                    $replacement.= "\t\t<div class='formField'>\n";
                    $replacement.= "\t\t\t<input type='checkbox' name='newsletter' id='newsletter' value='1' ".$checked."/>\n";
                    $replacement.= "\t\t</div>\n";
                    $replacement.= "\t</fieldset>\n";
                    $replacement.= "</div>\n<div align='center'>\n<input type='hidden' value='yes' name='remember'>";
                    $body = preg_replace($pattern, $replacement, $body);
                    //$body = str_replace('<input type="hidden" name="option" value="com_virtuemart" />','<input type="hidden" name="option" value="com_joomailermailchimpsignup" /><input type="hidden" name="component" value="com_virtuemart" /><input type="hidden" name="task" value="register_save" /><input type="hidden" name="oldtask" value="shopperadd" />',$body);
                }

                JResponse::setBody($body);
                return true;
            }
        // backend
        } else {
            $lang = JFactory::getLanguage();
            $lang->load('plg_system_joomailermailchimpsignup', JPATH_ADMINISTRATOR);
            $lang->load('com_joomailermailchimpintegration', JPATH_ADMINISTRATOR);

            $layout = JRequest::getVar('layout');
            $body = JResponse::getBody();

            if (($option == 'com_community' && $view == 'users' && $layout == 'edit')
                || ($option == 'com_comprofiler' && stristr($body, '"newCBuser"'))) {
                $api = $this->getApiInstance();
                if ($option == 'com_comprofiler') {
                    $usersbrowser = JRequest::getVar('usersbrowser');
                    $id = (isset($usersbrowser['idcid'][0])) ? $usersbrowser['idcid'][0] : JRequest::getVar('cid');
                } else {
                    $id = JRequest::getVar('id');
                }
                $user = JFactory::getUser($id);

                //Check if the user is already registered and subscribed
                if ($user->email) {
                    $userlists = $api->listsForEmail($user->email);
                    if ($userlists && in_array($listId, $userlists)) {
                        $replacement = "$1\n<input type=\"hidden\" name=\"newsletter\" value=\"1\" />\n"
                            . "<input type=\"hidden\" name=\"oldEmail\" value=\"{$user->email}\" />";
                        $body = preg_replace('/(<form.*?name="adminForm".*?>)/i', $replacement, $body);

                        JResponse::setBody($body);
                    }
                }
            } else if ($option == 'com_virtuemart' && $view == 'user' && $task == 'edit') {
                $id = JRequest::getVar('virtuemart_user_id');
                $id = $id[0];
                $user = JFactory::getUser($id);

                $listDetails = $this->getListDetails();

                $api = $this->getApiInstance();
                $userlists = $api->listsForEmail($user->email);
                if ($userlists && in_array($listId, $userlists)) {
                    $checked = ' checked="checked"';
                } else {
                    $checked = '';
                }

                $newsletterLegend = JText::_('JM_NEWSLETTER');
                $newsletterLabel = JText::_('JM_LIST') . ': ' . $listDetails['name'];
                $replacement = <<<EOF
<fieldset>
    <legend>{$newsletterLegend}</legend>
    <label for="newsletter" style="padding-right: 5px;">{$newsletterLabel}</label>
    <input type="checkbox" name="newsletter" id="newsletter" value="1"{$checked}/>
    <input type="hidden" name="oldEmail" value="{$user->email}" />
</fieldset>

$0
EOF;
                $pattern = '#<input type="hidden" name="virtuemart_user_id" value="[^"]+" />#';
                $body = preg_replace($pattern, $replacement, $body);
                JResponse::setBody($body);
            }
        }
    }

    public function onAfterRoute() {
        if (!JFile::exists(JPATH_ADMINISTRATOR . '/components/com_joomailermailchimpintegration/libraries/MCAPI.class.php')) {
            return;
        }

        $listId = $this->params->get('listid');
        if (!JFactory::getApplication()->isSite() || !$listId) {
            return;
        }

        $option = JRequest::getCmd('option');
        $task   = JRequest::getVar('task');
        $view   = JRequest::getVar('view');

        if (($option == 'com_users' && $task == 'registration.activate') ||
            ($option == 'com_comprofiler' && $view == 'confirm')) {

            $api = $this->getApiInstance();
            $db = JFactory::getDBO();
            if ($option == 'com_users') {
                $activation = JRequest::getVar('token');
                $query = $db->getQuery(true)
                    ->select($db->qn(array('id', 'email')))
                    ->from('#__users')
                    ->where($db->qn('activation') . ' = ' . $db->q($activation));
                $db->setQuery($query);
                $result = $db->loadObject();
                if (!$result) {
                    return;
                }
                $email = $result->email;
                $uid = $result->id;
            } else if ($option == 'com_comprofiler') {
                $activation = JRequest::getVar('confirmcode');
                $query = $db->getQuery(true)
                    ->select($db->qn('user_id'))
                    ->from('#__comprofiler')
                    ->where($db->qn('cbactivation') . ' = ' . $db->q($activation));
                $db->setQuery($query);
                $uid = $db->loadResult();
                if (!$uid) {
                    return;
                }
                $query = $db->getQuery(true)
                    ->select($db->qn('email'))
                    ->from('#__users')
                    ->where($db->qn('id') . ' = ' . $db->q($uid));
                $db->setQuery($query);
                $email = $db->loadResult();
            }

            $query = $db->getQuery(true)
                ->select($db->qn(array('fname', 'lname', 'email', 'groupings', 'merges')))
                ->from('#__joomailermailchimpsignup')
                ->where($db->qn('email') . ' = ' . $db->q($email));
            $db->setQuery($query);
            $result = $db->loadObject();
            if (!$result) {
                return;
            }

            $garray = '';
            if ($result->groupings) {
                $groups = explode('||', $result->groupings);
                foreach ($groups as $g) {
                    if ($g[0] == "\n") {
                        $g = substr($g,1);
                    }
                    $groupings = explode("\n", $g);
                    $name = substr(stristr($groupings[0], '='),1);
                    $id = substr(stristr($groupings[1], '='),1);
                    $vars = substr(stristr($groupings[2], '='),1);
                    $garray[$name] = array('name' => $name, 'id' => $id, 'groups' => $vars);
                }
            }
            $merges = array();
            $merges_string = $result->merges;
            if ($merges_string) {
                $first = explode("\n\n", $merges_string);
                foreach ($first as $f) {
                    $second = explode("\n", $f);

                    $name = str_replace('name=', '', $second[0]);
                    $second[1] = str_replace('value=', '', $second[1]);
                    if (stristr($second[1], '||')) {
                        $value = explode("||",substr($second[1], 0, -2));
                        if (count($value) == 3) {
                            $value['area'] = $value[0];
                            $value['detail1'] = $value[1];
                            $value['detail2'] = $value[2];
                        } else {
                            $value['addr1'] = $value[0];
                            $value['addr2'] = $value[1];
                            $value['city'] = $value[2];
                            $value['state'] = $value[3];
                            $value['zip'] = $value[4];
                            $value['country'] = $value[5];
                            unset($value[3]);
                            unset($value[4]);
                        }
                        unset($value[0]);
                        unset($value[1]);
                        unset($value[2]);
                    } else {
                        $value = $second[1];
                    }
                    $merges[$name] = $value;
                }
            }

            //Get the users ip address
            $ip = (ini_get('register_globals')) ? @$REMOTE_ADDR : $_SERVER['REMOTE_ADDR'];

            $merge_vars = array(
                'FNAME' => $result->fname,
                'LNAME' => $result->lname,
                'INTERESTS' => '',
                'GROUPINGS' => $garray,
                'OPTINIP' => $ip
            );
            $merge_vars = array_merge($merge_vars, $merges);
            $email_type = '';
            $double_optin = false;
            $update_existing = true;
            $replace_interests = true;
            $send_welcome = false;

            // create hidden signup date merge var if it doesn't exist
            $createSignupdateMerge = true;
            $mergeVars = $api->listMergeVars($listId);
            foreach ($mergeVars as $mv) {
                if ($mv['tag'] == 'SIGNUPAPI') {
                    $createSignupdateMerge = false;
                    break;
                }
            }
            if ($createSignupdateMerge){
                $api->listMergeVarAdd($listId, 'SIGNUPAPI', 'date added (API)', array('field_type' => 'date', 'public' => false));
            }
            $merge_vars['SIGNUPAPI'] = date('Y-m-d');

            //Subscribe the user
            $retval = $api->listSubscribe($listId, $email, $merge_vars, $email_type, $double_optin, $update_existing, $replace_interests, $send_welcome);

            $query = $db->getQuery(true)
                ->delete('#__joomailermailchimpsignup')
                ->where($db->qn('email') . ' = ' . $db->q($email));
            $db->setQuery($query)->execute();

            if ($api->errorCode && !in_array($api->errorCode, array(211, 215))) {
                echo "Unable to load listSubscribe()!\n";
                echo "\tCode={$api->errorCode}\n";
                echo "\tMsg={$api->errorMessage}\n";
            } else {
                $query = $db->getQuery(true)
                    ->replace($db->qn('#__joomailermailchimpintegration'))
                    ->set($db->qn('userid') . ' = ' . $db->q($uid))
                    ->set($db->qn('email') . ' = ' . $db->q($email))
                    ->set($db->qn('listid') . ' = ' . $db->q($listId));
                $db->setQuery($query)->execute();
            }

            // add user to CRM
            if ($this->params->get('sugar')) {
                $this->addToSugar($uid);
            }
            if ($this->params->get('highrise')) {
                $this->addToHighrise($uid);
            }
        }
    }

    public function onAfterDispatch() {
        if (!JFile::exists(JPATH_ADMINISTRATOR . '/components/com_joomailermailchimpintegration/libraries/MCAPI.class.php')) {
            return;
        }

        $app = JFactory::getApplication();
        if ($app->isAdmin()) {
            return;
        }

        $option = JRequest::getCmd('option');
        $view = JRequest::getCmd('view');
        $task = JRequest::getCmd('task');
        $layout = JRequest::getCmd('layout');
        $user = JFactory::getUser();
        $api = $this->getApiInstance();

        $k2plugin = false;
        if (JFile::exists(JPATH_ADMINISTRATOR . '/components/com_k2/admin.k2.php')) {
            $k2params = JComponentHelper::getParams('com_k2');
            $k2plugin = $k2params->get('K2UserProfile');
        }

        if (!$k2plugin && ($option == 'com_user' && ($view == 'register' || $view == 'user'))) {
            $template = $app->getTemplate();
            if ($view == 'register') {
                $v = 'register';
                $l = ($template == 'morph') ? 'register_morph' : 'register';
            } else {
                $v = 'user';
                $l = 'profile';
            }
            require_once(JPATH_SITE . '/components/com_user/controller.php');
            $controller = new UserController;
            $view = $controller->getView($v, 'html');
            $view->_addPath('template', JPATH_SITE . '/components/com_joomailermailchimpsignup/templates');
            $view->_addPath('template', JPATH_SITE . '/templates/' . $app->getTemplate() . '/html/com_joomailermailchimpsignup/templates');
            $view->setLayout($l);

            $listId = $this->params->get('listid');

            $mergefields = $api->listMergeVars($listId);
            $mergeids = $this->params->get('fields');

            $interests = $api->listInterestGroupings($listId);
            $interestids = $this->params->get('interests');

            $ihtml = '';
            $checked = '';
            $groupings = '';
            $usermerges = array();

            //Check if the user is subscribed
            if ($user->email) {
                $userlists = $api->listsForEmail($user->email);
                if ($userlists && in_array($listId, $userlists)) {
                    $checked = 'checked="checked"';
                }
                $userinfo = $api->listMemberInfo($listId, $user->email);
                $usermerges = $userinfo['merges'];
                $groupings = $userinfo['merges']['GROUPINGS'];
            }

            $lang = JFactory::getLanguage();
            $lang->load('plg_system_joomailermailchimpsignup', JPATH_ADMINISTRATOR);

            if ($template == 'morph') {
                $ihtml = '<li class="label"><label for="newsletter">';
                $ihtml.= JText::_('JM_NEWSLETTERSIGNUP');
                $ihtml.= '</label></li>';
                $ihtml.= '<li>';
                $ihtml.= '<input type="checkbox" name="newsletter" id="newsletter" value="1" ' . $checked . ' />';
                $ihtml.= '&nbsp;&nbsp;<label for="newsletter">';
                $ihtml.= JText::_('JM_NEWSLETTERSIGNUP');
                $ihtml.= '</label>';
                $ihtml.= "</li>";
            } else {
                $ihtml = '<tr><td height="40"><label for="newsletter">';
                $ihtml.= JText::_('JM_NEWSLETTERSIGNUP');
                $ihtml.= '</label></td>';
                $ihtml.= '<td height="40">';
                $ihtml.= '<input type="checkbox" name="newsletter" id="newsletter" value="1" ' . $checked . ' /><input type="hidden" name="oldEmail" value="' . $user->email . '" />';
                $ihtml.= '</td></tr>';
            }

            //Merge Fields
            if ($mergefields) {
                $ihtml .= $this->buildMergeVarsHTML($mergefields, $usermerges, $mergeids);
            }

            //Groups
            if ($interests) {
                //Build the HTML for the interests
                $ihtml .= $this->buildInterestsHTML($interests, $groupings, $interestids);
            }
            $view->assignRef('ihtml', $ihtml);

            $pathway = $app->getPathway();
            $pathway->setPathway(NULL);

            ob_start();
            $view->display();
            $contents = ob_get_clean();
            $document = JFactory::getDocument();
            $document->setBuffer($contents, 'component');
        }
    }

    private function buildMergeVarsHTML($mergefields, $usermerges, $mergeids) {
        if(!JFile::exists(JPATH_ADMINISTRATOR . '/components/com_joomailermailchimpintegration/libraries/MCAPI.class.php')) {
            return;
        }

        $key = 'tag';
        $val = 'name';
        $ihtml = array();
        $values = '';
        $k = 0;
        foreach ($mergefields as $mf) {
            $tag = $mf['tag'];

            $attribs = '';
            $control_name = '';
            $options = '';
            $selected = '';
            $req = ($mf['req'])?'*':'';
            $usertag = (isset($usermerges[$tag])) ? $usermerges[$tag] : '';

            if ((is_array($mergeids) && in_array($mf['tag'],$mergeids) || $mergeids == $mf['tag'])) {
                if ($mf['field_type'] == 'dropdown') {
                    $selected = 0;
                    foreach ($mf['choices'] as $m){
                        if ($usertag == $m) {
                            $selected = $m;
                        }

                        $options[] = array($key => $m , $val => $m);
                    }

                    $ihtml[] = array('<label for="mf_' . $tag . '">' . $mf['name'] . '</label>',
                        JHTML::_('select.genericlist', $options, 'mf_' . $tag, $attribs, $key, $val, $selected, $control_name . 'mf_' . $tag) . $req);
                } else if ($mf['field_type'] == 'radio') {
                    $selected = 0;
                    for ($n = 0; $n < count($mf['choices']); $n++) {
                        //foreach ($mf['choices'] as $m){
                        $m = $mf['choices'][$n];
                        if ($usertag == $m) {
                            $selected = $m;
                        }

                        $options[] = JHTML::_('select.option', $m, $m);
                    }

                    $ihtml[] = array( '<label for="mf_' . $tag . '">' . $mf['name'] . '</label>',
                        JHTML::_('select.radiolist', $options, 'mf_' . $tag, 'class="inputbox"', 'value', 'text', $selected) . $req);

                } elseif ($mf['field_type'] == 'number' || $mf['field_type'] == 'text' || $mf['field_type'] == 'url') {
                    $ihtml[] = array( '<label for="mf_' . $tag . '">' . $mf['name'] . '</label>',
                        '<input type="text" name="mf_' . $tag . '" id="mf_' . $mf['name'] . '" class="inputbox ' . $mf['field_type'] . '" size="25" value="' . $usertag . '"/>' . $req);
                } elseif ($mf['field_type'] == 'date') {
                    $ihtml[] = array( '<label for="mf_' . $tag . '">' . $mf['name'] . '</label>',
                        JHTML::_('calendar', $usertag, 'mf_' . $tag, 'mf_' . $tag, '%Y-%m-%d', '') . $req);
                    JHTML::_('behavior.calendar');
                } elseif ($mf['field_type'] == 'address') {
                    $ihtml[] = array( '<label for="mf_' . $tag . '">' . $mf['name'] . $req . '</label>');
                    $addr1 = (isset($usertag['addr1'])) ? $usertag['addr1'] : '';
                    $ihtml[(count($ihtml)-1)][] = '<input type="text" placeholder="' . JText::_('JM_STREET_ADDRESS') . '" name="mf_' . $tag . '[addr1]" class="inputbox ' . $mf['field_type'] . '" value="' . $addr1 . '"/>';

                    $addr2  = (isset($usertag['addr2'])) ? $usertag['addr2'] : '';
                    $ihtml[] = array('',
                        '<input type="text" name="mf_' . $tag . '[addr2]" placeholder="' . JText::_('JM_ADDRESS_LINE_2') . '" class="inputbox ' . $mf['field_type'] . '" value="' . $addr2 . '"/>');

                    $city   = (isset($usertag['city'])) ? $usertag['city'] : '';
                    $ihtml[] = array('',
                        '<input type="text" placeholder="' . JText::_('JM_CITY') . '" name="mf_' . $tag . '[city]" class="inputbox ' . $mf['field_type'] . '" value="' . $city . '"/>');

                    $state  = (isset($usertag['state'])) ? $usertag['state'] : '';
                    $ihtml[] = array('',
                        '<input type="text" placeholder="' . JText::_('JM_STATE_PROVINCE_REGION') . '" name="mf_' . $tag . '[state]" class="inputbox ' . $mf['field_type'] . '" value="' . $state . '"/>');

                    $zip    = (isset($usertag['zip'])) ? $usertag['zip'] : '';
                    $ihtml[] = array('',
                        '<input type="text" placeholder="' . JText::_('JM_ZIP_POSTAL') . '" name="mf_' . $tag . '[zip]" class="inputbox ' . $mf['field_type'] . '" value="' . $zip . '"/>');

                    $options = array(
                        array($key=>'',$val=>JText::_('JM_COUNTRY') . $req),
                        array($key=>'AF',$val=>'AFGHANISTAN'),
                        array($key=>'AX',$val=>'ÅLAND ISLANDS'),
                        array($key=>'AL',$val=>'ALBANIA'),
                        array($key=>'DZ',$val=>'ALGERIA'),
                        array($key=>'AS',$val=>'AMERICAN SAMOA'),
                        array($key=>'AD',$val=>'ANDORRA'),
                        array($key=>'AO',$val=>'ANGOLA'),
                        array($key=>'AI',$val=>'ANGUILLA'),
                        array($key=>'AQ',$val=>'ANTARCTICA'),
                        array($key=>'AG',$val=>'ANTIGUA AND BARBUDA'),
                        array($key=>'AR',$val=>'ARGENTINA'),
                        array($key=>'AM',$val=>'ARMENIA'),
                        array($key=>'AW',$val=>'ARUBA'),
                        array($key=>'AU',$val=>'AUSTRALIA'),
                        array($key=>'AT',$val=>'AUSTRIA'),
                        array($key=>'AZ',$val=>'AZERBAIJAN'),
                        array($key=>'BS',$val=>'BAHAMAS'),
                        array($key=>'BH',$val=>'BAHRAIN'),
                        array($key=>'BD',$val=>'BANGLADESH'),
                        array($key=>'BB',$val=>'BARBADOS'),
                        array($key=>'BY',$val=>'BELARUS'),
                        array($key=>'BE',$val=>'BELGIUM'),
                        array($key=>'BZ',$val=>'BELIZE'),
                        array($key=>'BJ',$val=>'BENIN'),
                        array($key=>'BM',$val=>'BERMUDA'),
                        array($key=>'BT',$val=>'BHUTAN'),
                        array($key=>'BO',$val=>'BOLIVIA, PLURINATIONAL STATE OF'),
                        array($key=>'BA',$val=>'BOSNIA AND HERZEGOVINA'),
                        array($key=>'BW',$val=>'BOTSWANA'),
                        array($key=>'BV',$val=>'BOUVET ISLAND'),
                        array($key=>'BR',$val=>'BRAZIL'),
                        array($key=>'IO',$val=>'BRITISH INDIAN OCEAN TERRITORY'),
                        array($key=>'BN',$val=>'BRUNEI DARUSSALAM'),
                        array($key=>'BG',$val=>'BULGARIA'),
                        array($key=>'BF',$val=>'BURKINA FASO'),
                        array($key=>'BI',$val=>'BURUNDI'),
                        array($key=>'KH',$val=>'CAMBODIA'),
                        array($key=>'CM',$val=>'CAMEROON'),
                        array($key=>'CA',$val=>'CANADA'),
                        array($key=>'CV',$val=>'CAPE VERDE'),
                        array($key=>'KY',$val=>'CAYMAN ISLANDS'),
                        array($key=>'CF',$val=>'CENTRAL AFRICAN REPUBLIC'),
                        array($key=>'TD',$val=>'CHAD'),
                        array($key=>'CL',$val=>'CHILE'),
                        array($key=>'CN',$val=>'CHINA'),
                        array($key=>'CX',$val=>'CHRISTMAS ISLAND'),
                        array($key=>'CC',$val=>'COCOS (KEELING) ISLANDS'),
                        array($key=>'CO',$val=>'COLOMBIA'),
                        array($key=>'KM',$val=>'COMOROS'),
                        array($key=>'CG',$val=>'CONGO'),
                        array($key=>'CD',$val=>'CONGO, THE DEMOCRATIC REPUBLIC OF THE'),
                        array($key=>'CK',$val=>'COOK ISLANDS'),
                        array($key=>'CR',$val=>'COSTA RICA'),
                        array($key=>'CI',$val=>'CÔTE D\'IVOIRE'),
                        array($key=>'HR',$val=>'CROATIA'),
                        array($key=>'CU',$val=>'CUBA'),
                        array($key=>'CY',$val=>'CYPRUS'),
                        array($key=>'CZ',$val=>'CZECH REPUBLIC'),
                        array($key=>'DK',$val=>'DENMARK'),
                        array($key=>'DJ',$val=>'DJIBOUTI'),
                        array($key=>'DM',$val=>'DOMINICA'),
                        array($key=>'DO',$val=>'DOMINICAN REPUBLIC'),
                        array($key=>'EC',$val=>'ECUADOR'),
                        array($key=>'EG',$val=>'EGYPT'),
                        array($key=>'SV',$val=>'EL SALVADOR'),
                        array($key=>'GQ',$val=>'EQUATORIAL GUINEA'),
                        array($key=>'ER',$val=>'ERITREA'),
                        array($key=>'EE',$val=>'ESTONIA'),
                        array($key=>'ET',$val=>'ETHIOPIA'),
                        array($key=>'FK',$val=>'FALKLAND ISLANDS (MALVINAS)'),
                        array($key=>'FO',$val=>'FAROE ISLANDS'),
                        array($key=>'FJ',$val=>'FIJI'),
                        array($key=>'FI',$val=>'FINLAND'),
                        array($key=>'FR',$val=>'FRANCE'),
                        array($key=>'GF',$val=>'FRENCH GUIANA'),
                        array($key=>'PF',$val=>'FRENCH POLYNESIA'),
                        array($key=>'TF',$val=>'FRENCH SOUTHERN TERRITORIES'),
                        array($key=>'GA',$val=>'GABON'),
                        array($key=>'GM',$val=>'GAMBIA'),
                        array($key=>'GE',$val=>'GEORGIA'),
                        array($key=>'DE',$val=>'GERMANY'),
                        array($key=>'GH',$val=>'GHANA'),
                        array($key=>'GI',$val=>'GIBRALTAR'),
                        array($key=>'GR',$val=>'GREECE'),
                        array($key=>'GL',$val=>'GREENLAND'),
                        array($key=>'GD',$val=>'GRENADA'),
                        array($key=>'GP',$val=>'GUADELOUPE'),
                        array($key=>'GU',$val=>'GUAM'),
                        array($key=>'GT',$val=>'GUATEMALA'),
                        array($key=>'GG',$val=>'GUERNSEY'),
                        array($key=>'GN',$val=>'GUINEA'),
                        array($key=>'GW',$val=>'GUINEA-BISSAU'),
                        array($key=>'GY',$val=>'GUYANA'),
                        array($key=>'HT',$val=>'HAITI'),
                        array($key=>'HM',$val=>'HEARD ISLAND AND MCDONALD ISLANDS'),
                        array($key=>'VA',$val=>'HOLY SEE (VATICAN CITY STATE)'),
                        array($key=>'HN',$val=>'HONDURAS'),
                        array($key=>'HK',$val=>'HONG KONG'),
                        array($key=>'HU',$val=>'HUNGARY'),
                        array($key=>'IS',$val=>'ICELAND'),
                        array($key=>'IN',$val=>'INDIA'),
                        array($key=>'ID',$val=>'INDONESIA'),
                        array($key=>'IR',$val=>'IRAN, ISLAMIC REPUBLIC OF'),
                        array($key=>'IQ',$val=>'IRAQ'),
                        array($key=>'IE',$val=>'IRELAND'),
                        array($key=>'IM',$val=>'ISLE OF MAN'),
                        array($key=>'IL',$val=>'ISRAEL'),
                        array($key=>'IT',$val=>'ITALY'),
                        array($key=>'JM',$val=>'JAMAICA'),
                        array($key=>'JP',$val=>'JAPAN'),
                        array($key=>'JE',$val=>'JERSEY'),
                        array($key=>'JO',$val=>'JORDAN'),
                        array($key=>'KZ',$val=>'KAZAKHSTAN'),
                        array($key=>'KE',$val=>'KENYA'),
                        array($key=>'KI',$val=>'KIRIBATI'),
                        array($key=>'KP',$val=>'KOREA, DEMOCRATIC PEOPLE\'S REPUBLIC OF'),
                        array($key=>'KR',$val=>'KOREA, REPUBLIC OF'),
                        array($key=>'KW',$val=>'KUWAIT'),
                        array($key=>'KG',$val=>'KYRGYZSTAN'),
                        array($key=>'LA',$val=>'LAO PEOPLE\'S DEMOCRATIC REPUBLIC'),
                        array($key=>'LV',$val=>'LATVIA'),
                        array($key=>'LB',$val=>'LEBANON'),
                        array($key=>'LS',$val=>'LESOTHO'),
                        array($key=>'LR',$val=>'LIBERIA'),
                        array($key=>'LY',$val=>'LIBYAN ARAB JAMAHIRIYA'),
                        array($key=>'LI',$val=>'LIECHTENSTEIN'),
                        array($key=>'LT',$val=>'LITHUANIA'),
                        array($key=>'LU',$val=>'LUXEMBOURG'),
                        array($key=>'MO',$val=>'MACAO'),
                        array($key=>'MK',$val=>'MACEDONIA, THE FORMER YUGOSLAV REPUBLIC OF'),
                        array($key=>'MG',$val=>'MADAGASCAR'),
                        array($key=>'MW',$val=>'MALAWI'),
                        array($key=>'MY',$val=>'MALAYSIA'),
                        array($key=>'MV',$val=>'MALDIVES'),
                        array($key=>'ML',$val=>'MALI'),
                        array($key=>'MT',$val=>'MALTA'),
                        array($key=>'MH',$val=>'MARSHALL ISLANDS'),
                        array($key=>'MQ',$val=>'MARTINIQUE'),
                        array($key=>'MR',$val=>'MAURITANIA'),
                        array($key=>'MU',$val=>'MAURITIUS'),
                        array($key=>'YT',$val=>'MAYOTTE'),
                        array($key=>'MX',$val=>'MEXICO'),
                        array($key=>'FM',$val=>'MICRONESIA, FEDERATED STATES OF'),
                        array($key=>'MD',$val=>'MOLDOVA, REPUBLIC OF'),
                        array($key=>'MC',$val=>'MONACO'),
                        array($key=>'MN',$val=>'MONGOLIA'),
                        array($key=>'ME',$val=>'MONTENEGRO'),
                        array($key=>'MS',$val=>'MONTSERRAT'),
                        array($key=>'MA',$val=>'MOROCCO'),
                        array($key=>'MZ',$val=>'MOZAMBIQUE'),
                        array($key=>'MM',$val=>'MYANMAR'),
                        array($key=>'NA',$val=>'NAMIBIA'),
                        array($key=>'NR',$val=>'NAURU'),
                        array($key=>'NP',$val=>'NEPAL'),
                        array($key=>'NL',$val=>'NETHERLANDS'),
                        array($key=>'AN',$val=>'NETHERLANDS ANTILLES'),
                        array($key=>'NC',$val=>'NEW CALEDONIA'),
                        array($key=>'NZ',$val=>'NEW ZEALAND'),
                        array($key=>'NI',$val=>'NICARAGUA'),
                        array($key=>'NE',$val=>'NIGER'),
                        array($key=>'NG',$val=>'NIGERIA'),
                        array($key=>'NU',$val=>'NIUE'),
                        array($key=>'NF',$val=>'NORFOLK ISLAND'),
                        array($key=>'MP',$val=>'NORTHERN MARIANA ISLANDS'),
                        array($key=>'NO',$val=>'NORWAY'),
                        array($key=>'OM',$val=>'OMAN'),
                        array($key=>'PK',$val=>'PAKISTAN'),
                        array($key=>'PW',$val=>'PALAU'),
                        array($key=>'PS',$val=>'PALESTINIAN TERRITORY, OCCUPIED'),
                        array($key=>'PA',$val=>'PANAMA'),
                        array($key=>'PG',$val=>'PAPUA NEW GUINEA'),
                        array($key=>'PY',$val=>'PARAGUAY'),
                        array($key=>'PE',$val=>'PERU'),
                        array($key=>'PH',$val=>'PHILIPPINES'),
                        array($key=>'PN',$val=>'PITCAIRN'),
                        array($key=>'PL',$val=>'POLAND'),
                        array($key=>'PT',$val=>'PORTUGAL'),
                        array($key=>'PR',$val=>'PUERTO RICO'),
                        array($key=>'QA',$val=>'QATAR'),
                        array($key=>'RE',$val=>'RÉUNION'),
                        array($key=>'RO',$val=>'ROMANIA'),
                        array($key=>'RU',$val=>'RUSSIAN FEDERATION'),
                        array($key=>'RW',$val=>'RWANDA'),
                        array($key=>'BL',$val=>'SAINT BARTHÉLEMY'),
                        array($key=>'SH',$val=>'SAINT HELENA, ASCENSION AND TRISTAN DA CUNHA'),
                        array($key=>'KN',$val=>'SAINT KITTS AND NEVIS'),
                        array($key=>'LC',$val=>'SAINT LUCIA'),
                        array($key=>'MF',$val=>'SAINT MARTIN'),
                        array($key=>'PM',$val=>'SAINT PIERRE AND MIQUELON'),
                        array($key=>'VC',$val=>'SAINT VINCENT AND THE GRENADINES'),
                        array($key=>'WS',$val=>'SAMOA'),
                        array($key=>'SM',$val=>'SAN MARINO'),
                        array($key=>'ST',$val=>'SAO TOME AND PRINCIPE'),
                        array($key=>'SA',$val=>'SAUDI ARABIA'),
                        array($key=>'SN',$val=>'SENEGAL'),
                        array($key=>'RS',$val=>'SERBIA'),
                        array($key=>'SC',$val=>'SEYCHELLES'),
                        array($key=>'SL',$val=>'SIERRA LEONE'),
                        array($key=>'SG',$val=>'SINGAPORE'),
                        array($key=>'SK',$val=>'SLOVAKIA'),
                        array($key=>'SI',$val=>'SLOVENIA'),
                        array($key=>'SB',$val=>'SOLOMON ISLANDS'),
                        array($key=>'SO',$val=>'SOMALIA'),
                        array($key=>'ZA',$val=>'SOUTH AFRICA'),
                        array($key=>'GS',$val=>'SOUTH GEORGIA AND THE SOUTH SANDWICH ISLANDS'),
                        array($key=>'ES',$val=>'SPAIN'),
                        array($key=>'LK',$val=>'SRI LANKA'),
                        array($key=>'SD',$val=>'SUDAN'),
                        array($key=>'SR',$val=>'SURINAME'),
                        array($key=>'SJ',$val=>'SVALBARD AND JAN MAYEN'),
                        array($key=>'SZ',$val=>'SWAZILAND'),
                        array($key=>'SE',$val=>'SWEDEN'),
                        array($key=>'CH',$val=>'SWITZERLAND'),
                        array($key=>'SY',$val=>'SYRIAN ARAB REPUBLIC'),
                        array($key=>'TW',$val=>'TAIWAN, PROVINCE OF CHINA'),
                        array($key=>'TJ',$val=>'TAJIKISTAN'),
                        array($key=>'TZ',$val=>'TANZANIA, UNITED REPUBLIC OF'),
                        array($key=>'TH',$val=>'THAILAND'),
                        array($key=>'TL',$val=>'TIMOR-LESTE'),
                        array($key=>'TG',$val=>'TOGO'),
                        array($key=>'TK',$val=>'TOKELAU'),
                        array($key=>'TO',$val=>'TONGA'),
                        array($key=>'TT',$val=>'TRINIDAD AND TOBAGO'),
                        array($key=>'TN',$val=>'TUNISIA'),
                        array($key=>'TR',$val=>'TURKEY'),
                        array($key=>'TM',$val=>'TURKMENISTAN'),
                        array($key=>'TC',$val=>'TURKS AND CAICOS ISLANDS'),
                        array($key=>'TV',$val=>'TUVALU'),
                        array($key=>'UG',$val=>'UGANDA'),
                        array($key=>'UA',$val=>'UKRAINE'),
                        array($key=>'AE',$val=>'UNITED ARAB EMIRATES'),
                        array($key=>'GB',$val=>'UNITED KINGDOM'),
                        array($key=>'US',$val=>'UNITED STATES'),
                        array($key=>'UM',$val=>'UNITED STATES MINOR OUTLYING ISLANDS'),
                        array($key=>'UY',$val=>'URUGUAY'),
                        array($key=>'UZ',$val=>'UZBEKISTAN'),
                        array($key=>'VU',$val=>'VANUATU'),
                        array($key=>'VA',$val=>'VATICAN CITY STATE'),
                        array($key=>'VE',$val=>'VENEZUELA, BOLIVARIAN REPUBLIC OF'),
                        array($key=>'VN',$val=>'VIET NAM'),
                        array($key=>'VG',$val=>'VIRGIN ISLANDS, BRITISH'),
                        array($key=>'VI',$val=>'VIRGIN ISLANDS, U.S.'),
                        array($key=>'WF',$val=>'WALLIS AND FUTUNA'),
                        array($key=>'EH',$val=>'WESTERN SAHARA'),
                        array($key=>'YE',$val=>'YEMEN'),
                        array($key=>'ZM',$val=>'ZAMBIA'),
                        array($key=>'ZW',$val=>'ZIMBABWE')

                    ); //@todo: cross-reference this list with Mailchimp country list

                    $selected = (isset($usertag['country'])) ? $usertag['country'] : '' ;
                    $ihtml[] = array(
                        '',
                        JHTML::_('select.genericlist', $options, 'mf_' . $tag . '[country]', $attribs, $key, $val, $selected, $control_name . 'mf_' . $tag . '[country]'));

                } else if ($mf['field_type'] == 'phone') {
                    $ihtml[] = array(
                        '<label for="mf_' . $tag . '">' . JText::_('JM_PHONE') . '</label>',
                        '(<input type="text" size="3" maxlength="3" name="mf_' . $tag . '[area]" id="mf_' . $mf['name'] . '-area" value="' . substr($usertag, 0, 3) . '"/>)' .
                        ' <input type="text" size="3" maxlength="3" name="mf_' . $tag . '[detail1]" id="mf_' . $mf['name'] . '-detail1" value="' . substr($usertag, 4, 3) . '"/>' .
                        ' - <input type="text" size="4" maxlength="4" name="mf_' . $tag . '[detail2]" id="mf_' . $mf['name'] . '-detail2" value="' . substr($usertag, 8, 4) . '"/>' .
                        '<label for="mf_' . $tag . '[detail2]">(####) ### - ####</label>' . $req);
                } elseif ($mf['field_type'] == 'imageurl') {
                    //@todo: create HTML for image URL
                }
            }
        }

        $returnValue = '';
        if (count($ihtml)) {
            foreach ($ihtml as $field) {
                $returnValue .= "\n" . '<div class="control-group"><div class="control-label">' . $field[0]
                    . '</div><div class="controls">' . $field[1] . '</div></div>' . "\n";
            }
        }

        return $returnValue;
    }

    private function buildInterestsHTML($interests, $groupings, $interestids) {
        if (!JFile::exists(JPATH_ADMINISTRATOR . '/components/com_joomailermailchimpintegration/libraries/MCAPI.class.php')) {
            return;
        }

        $key = 'bit';
        $val = 'name';
        $ihtml = array();

        foreach ($interests as $int) {
            //Check user values for the interests
            if (isset($groupings) && $groupings !== '') {
                foreach ($groupings as $group) {
                    if ($group['id'] == $int['id']) {
                        if (stristr($group['groups'], ',')) {
                            $values[$int['id']] = explode(', ', $group['groups']);
                        } else {
                            $values[$int['id']] = array($group['groups']);
                        }
                    }
                }
            }
            $attribs = '';
            $control_name = '';
            $options = '';
            $selected = '';

            if ((is_array($interestids) && in_array($int['id'], $interestids) || $interestids == $int['id'])) {
                if ($int['form_field'] == 'checkboxes') {
                    $ihtml[] = array('<label for="interest_' . $int['id'] . '">' . $int['name'] . '</label>');
                    foreach ($int['groups'] as $g){
                        $selected = '';

                        if (isset($values[$int['id']])) {
                            if (in_array($g[$val], $values[$int['id']])) {
                                $selected = 'checked';
                            }
                        }
                        if (!isset($ihtml[(count($ihtml) - 1)][1])) {
                            $ihtml[(count($ihtml) - 1)][1] = '';
                        }
                        $ihtml[(count($ihtml) - 1)][1] .= '<input type="checkbox" name="interest_' . $int['id'] . '[]" ' .
                            'id="' . $g[$key] . '" value="' . $g[$key] . '" ' . $selected . ' />' .
                            '<label for="' . $g[$key] . '">' . $g[$val] . '</label>&nbsp;';
                    }

                } else if ($int['form_field'] == 'dropdown') {

                    foreach ($int['groups'] as $g) {
                        if (isset($values[$int['id']])) {
                            if (in_array($g[$val], $values[$int['id']])) {
                                $selected = $g[$key];
                            }
                        }
                        $options[] = array($key => $g[$key], $val => $g[$val]);
                    }

                    $ihtml[] = array('<label for="interest_' . $int['id'] . '">' . $int['name'] . '</label>',
                        JHTML::_('select.genericlist', $options, 'interest_' . $int['id'], $attribs, $key, $val, $selected, $control_name . 'interest_' . $int['id']));

                } else if ($int['form_field'] == 'radio') {
                    foreach ($int['groups'] as $g){
                        if (isset($values[$int['id']])) {
                            if (in_array($g[$val], $values[$int['id']])) {
                                $selected = $g[$key];
                            }
                        }
                        $options[] = JHTML::_('select.option', $g[$key], $g[$val]);
                    }
                    $ihtml[] = array('<label for="interest_' . $int['id'] . '">' . $int['name'] . '</label>',
                        JHTML::_('select.radiolist', $options, 'interest_' . $int['id'], 'class="inputbox"', 'value', 'text', $selected));
                }
            }
        }

        $returnValue = '';
        if (count($ihtml)) {
            foreach ($ihtml as $field) {
                $returnValue .= '<div class="control-group"><div class="control-label">' . $field[0]
                    . '</div><div class="controls">' . $field[1] . '</div></div>';
            }
        }

        return $returnValue;
    }

    private function getApiInstance() {
        if (!plgSystemJoomailerMailchimpSignup::$MC) {
            $params = JComponentHelper::getParams('com_joomailermailchimpintegration');
            $MCapi = $params->get('params.MCapi');
            plgSystemJoomailerMailchimpSignup::$MC = new joomlamailerMCAPI($MCapi);
        }

        return plgSystemJoomailerMailchimpSignup::$MC;
    }

    function addToSugar($uid) {
        $user = JFactory::getUser($uid);
        $db	= JFactory::getDBO();
        $params = JComponentHelper::getParams('com_joomailermailchimpintegration');
        $sugar_name = $params->get('params.sugar_name');
        $sugar_pwd  = $params->get('params.sugar_pwd');
        $sugar_url  = $params->get('params.sugar_url');

        $config = $this->getCrmConfig('sugar');
        if ($config == NULL){
            if (JFile::exists( JPATH_ADMINISTRATOR . '/components/com_comprofiler/admin.comprofiler.php')) {
                jimport('joomla.application.component.helper');
                $cHelper = JComponentHelper::getComponent('com_comprofiler', true);
            } else {
                $cHelper->enabled = false;
            }

            $config = new stdClass();
            $config->first_name = ($cHelper->enabled) ? 'CB' : 'core';
        }

        require_once(JPATH_ADMINISTRATOR . '/components/com_joomailermailchimpintegration/libraries/sugar.php');
        $sugar = new SugarCRMWebServices();
        $sugar->SugarCRM($sugar_name, $sugar_pwd, $sugar_url);
        $sugar->login();

        $queryJS = false;
        $queryCB = false;
        $JSand = array();
        foreach ($config as $k => $v) {
            if ($k != 'firstname' && $k != 'lastname') {
                $vEx = explode(';', $v);
                if ($vEx[0] == 'js') {
                    $queryJS = true;
                    $JSand[] = $vEx[1];
                } else {
                    $queryCB = true;
                }
            }
        }
        $JSand = implode("','", array_unique($JSand));

        $userCB = false;

        if ($config->first_name == 'core') {
            $names = explode(' ', $user->name);
            $first_name = $names[0];
            $last_name = '';
            if (isset($names[1])) {
                for ($i = 1; $i < count($names); $i++) {
                    $last_name .= $names[$i] . ' ';
                }
            }
            $last_name = trim($last_name);
        } else {
            $query = $db->getQuery(true);
            $query->select($db->qn(array('firstname', 'lastname', 'middlename')))
                ->from('#__comprofiler')
                ->where($db->qn('user_id') . ' = ' . $db->q($user->id));
            $db->setQuery($query);
            $userCB = $db->loadObjectList();

            $first_name = $userCB[0]->firstname;
            $last_name  = $userCB[0]->lastname;
            if ($userCB[0]->middlename != '') {
                $last_name = $userCB[0]->middlename . ' ' . $last_name;
            }
        }
        if ($queryJS) {
            $query = $db->getQuery(true);
            $query->select($db->qn(array('field_id', 'name')))
                ->from('#__community_fields_values')
                ->where($db->qn('user_id') . ' = ' . $db->q($user->id))
                ->where($db->qn('field_id') . ' IN (\'' . $JSand . '\')');
            $db->setQuery($query);
            $JSfields = $db->loadObjectList();

            $JSfieldsArray = array();
            foreach ($JSfields as $jsf) {
                $JSfieldsArray[$jsf->field_id] = $jsf->value;
            }
        }

        if ($queryCB) {
            if (!$userCB) {
                $query = $db->getQuery(true);
                $query->select('*')
                    ->from('#__comprofiler')
                    ->where($db->qn('user_id') . ' = ' . $db->q($user->id));
                $db->setQuery($query);
                $userCB = $db->loadObjectList();
            }
        }
        $data = array(
            'first_name' => $first_name,
            'last_name'  => $last_name,
            'email1'	 => $user->email
        );
        foreach ($config as $k => $v) {
            if ($k != 'first_name' && $k != 'last_name') {
                if ($v) {
                    $vEx = explode(';', $v);
                    if ($vEx[0] == 'js') {
                        $data[$k] = (isset($JSfieldsArray[$vEx[1]])) ? $JSfieldsArray[$vEx[1]] : '';
                    } else {
                        $data[$k] = (isset($userCB[0]->{$vEx[1]})) ? str_replace('|*|',', ',$userCB[0]->{$vEx[1]}) : '';
                    }
                }
            }
        }

        $existing_user = $sugar->findUserByEmail($user->email);

        if (isset($existing_users[$data['email1']])) {
            $data['id'] = $existing_user[$d['email1']];
        }

        $sugar->setContactMulti(array($data));

        return;
    }

    private function addToHighrise($uid) {
        $request = array();
        $user = JFactory::getUser($uid);
        $db	= JFactory::getDBO();
        $params = JComponentHelper::getParams('com_joomailermailchimpintegration');
        $highrise_url = $params->get('params.highrise_url');
        $highrise_api_token = $params->get('params.highrise_api_token');

        $config = $this->getCrmConfig('highrise');
        if ($config == NULL) {
            if (JFile::exists(JPATH_ADMINISTRATOR . '/components/com_comprofiler/admin.comprofiler.php')) {
                jimport('joomla.application.component.helper');
                $cHelper = JComponentHelper::getComponent('com_comprofiler', true);
            } else {
                $cHelper->enabled = false;
            }

            $config = new stdClass();
            $config->{'first-name'} = ($cHelper->enabled) ? 'CB' : 'core';
            $config->email_work = 'default';
        }

        require_once(JPATH_ADMINISTRATOR . '/components/com_joomailermailchimpintegration/helpers/common.php');

        $queryJS = false;
        $queryCB = false;
        $JSand = array();
        foreach ($config as $k => $v) {
            if ($k != 'first-name' && $k != 'last-name') {
                $vEx = explode(';', $v);
                if ($vEx[0] == 'js') {
                    $queryJS = true;
                    $JSand[] = $vEx[1];
                } else {
                    $queryCB = true;
                }
            }
        }
        $JSand = implode("','", array_unique($JSand));

        require_once(JPATH_ADMINISTRATOR . '/components/com_joomailermailchimpintegration/libraries/push2Highrise.php');
        $highrise = new Push_Highrise($highrise_url, $highrise_api_token);

        $userCB = false;

        if ($config->{'first-name'} == 'core') {
            $names = explode(' ', $user->name);
            $firstname = $names[0];
            $lastname = '';
            if (isset($names[1])) {
                for ($i = 1; $i < count($names); $i++) {
                    $lastname .= $names[$i] . ' ';
                }
            }
            $lastname = trim($lastname);
        } else {
            $query = $db->getQuery(true);
            $query->select($db->qn(array('firstname', 'lastname', 'middlename')))
                ->from('#__comprofiler')
                ->where($db->qn('user_id') . ' = ' . $db->q($user->id));
            $db->setQuery($query);
            $userCB = $db->loadObjectList();

            $firstname = $userCB[0]->firstname;
            $lastname  = $userCB[0]->lastname;
            if( $userCB[0]->middlename != '' ){
                $lastname = $userCB[0]->middlename.' '.$lastname;
            }
        }

        $highriseUser = $highrise->person_in_highrise(array('first-name' => $firstname, 'last-name' => $lastname));
        $request['id'] = $highriseUser->id;

        if ($queryJS) {
            $query = $db->getQuery(true);
            $query->select($db->qn(array('field_id', 'value')))
                ->from('#__community_fields_values')
                ->where($db->qn('user_id') . ' = ' . $db->q($user->id))
                ->where($db->qn('field_id') . ' IN (\'' . $JSand . '\')');
            $db->setQuery($query);
            $JSfields = $db->loadObjectList();
            $JSfieldsArray = array();
            foreach ($JSfields as $jsf) {
                $JSfieldsArray[$jsf->field_id] = $jsf->value;
            }
        }

        if ($queryCB) {
            if (!$userCB) {
                $query = $db->getQuery(true);
                $query->select('*')
                    ->from('#__comprofiler')
                    ->where($db->qn('user_id') . ' = ' . $db->q($user->id));
                $db->setQuery($query);
                $userCB = $db->loadObjectList();
            }
        }

        $xml =  "<person>\n";

        if (intval($highriseUser->id) > 0) {
            $xml .= '<id>' . $highriseUser->id . "</id>\n";
        }

        $xml .=  "<first-name>" . htmlspecialchars($firstname) . "</first-name>\n" .
            "<last-name>" . htmlspecialchars($lastname) . "</last-name>";

        if (isset($config->title) && $config->title != '') {
            $conf = explode(';', $config->title);
            $value = ($conf[0] == 'js') ? ((isset($JSfieldsArray[$conf[1]])) ? $JSfieldsArray[$conf[1]] : '') :
                ((isset($userCB[0]->{$conf[1]})) ? $userCB[0]->{$conf[1]} : '');
            $xml .= "\n<title>" . htmlspecialchars($value) . "</title>";
        }
        if (isset($config->background) && $config->background != '') {
            $conf = explode(';', $config->background);
            $value = ($conf[0] == 'js') ? ((isset($JSfieldsArray[$conf[1]])) ? $JSfieldsArray[$conf[1]] : '') :
                ((isset($userCB[0]->{$conf[1]})) ? $userCB[0]->{$conf[1]} : '');
            $xml .= "\n<background>" . htmlspecialchars($value) . "</background>";
        }
        if (isset($config->company) && $config->company != '') {
            $conf = explode(';', $config->company);
            $value = ($conf[0] == 'js') ? ((isset($JSfieldsArray[$conf[1]])) ? $JSfieldsArray[$conf[1]] : '') :
                ((isset($userCB[0]->{$conf[1]})) ? $userCB[0]->{$conf[1]} : '');
            $xml .= "\n<company-name>" . htmlspecialchars($value) . '</company-name>';
        }

        $xml .= "\n<contact-data>";
        $xml .= "\n<email-addresses>";

        $emailTypes = array('work', 'home', 'other');
        foreach ($emailTypes as $et) {
            if (isset($config->{'email_' . $et}) && $config->{'email_'.$et} != '') {
                if ($config->{'email_' . $et} == 'default') {
                    $value = $user->email;
                } else {
                    $conf = explode(';', $config->{'email_' . $et});
                    $value = ( $conf[0] == 'js' ) ?  $JSfieldsArray[$conf[1]] : $userCB[0]->{$conf[1]};
                }

                $fieldId = '';
                if (isset($highriseUser->{'contact-data'}->{'email-addresses'}->{'email-address'})) {
                    foreach ($highriseUser->{'contact-data'}->{'email-addresses'} as $hu) {
                        foreach ($hu->{'email-address'} as $ea) {
                            if ($ea->location == ucfirst($et)) {
                                $fieldId = '<id type="integer">' . $ea->id[0] . "</id>\n";
                                break;
                            }
                        }
                    }
                }
                $xml .= "\n<email-address>\n" .
                    $fieldId .
                    "<address>".htmlspecialchars($value)."</address>\n" .
                    "<location>".ucfirst($et)."</location>\n" .
                    "</email-address>";
            }
        }

        $xml .= "\n</email-addresses>\n";

        $xml .= "\n<phone-numbers>\n";
        $phoneTypes = array('work', 'mobile', 'fax', 'pager', 'home', 'skype', 'other');
        foreach ($phoneTypes as $pt) {
            if (isset($config->{'phone_' . $pt}) && $config->{'phone_' . $pt} != '') {
                $conf = explode(';', $config->{'phone_' . $pt});
                $value = ($conf[0] == 'js') ? ((isset($JSfieldsArray[$conf[1]])) ? $JSfieldsArray[$conf[1]] : '') :
                    ((isset($userCB[0]->{$conf[1]})) ? $userCB[0]->{$conf[1]} : '');
            }

            $fieldId = '';
            if (isset($highriseUser->{'contact-data'}->{'phone-numbers'}->{'phone-number'})) {
                foreach ($highriseUser->{'contact-data'}->{'phone-numbers'} as $hu) {
                    foreach ($hu->{'phone-number'} as $pn) {
                        if ($pn->location == ucfirst($pt)) {
                            $fieldId = '<id type="integer">' . $pn->id[0] . "</id>\n";
                            break;
                        }
                    }
                }
            }
            $xml .= "<phone-number>\n" .
                $fieldId .
                "<number>".htmlspecialchars($value)."</number>\n" .
                "<location>".ucfirst($pt)."</location>\n" .
                "</phone-number>";
        }
        $xml .= "\n</phone-numbers>\n";

        $xml .= "\n<instant-messengers>\n";
        $imTypes = array('AIM', 'MSN', 'ICQ', 'Jabber', 'Yahoo', 'Skype', 'QQ', 'Sametime', 'Gadu-Gadu', 'Google Talk', 'Other');
        foreach ($imTypes as $im) {
            if (isset($config->{$im}) && $config->{$im} != '') {
                $value = false;
                if ($config->{$im} == 'default') {
                    $value = $user->email;
                } else if ($config->{$im} != '') {
                    $conf = explode(';', $config->{$im});
                    $value = ($conf[0] == 'js') ? ((isset($JSfieldsArray[$conf[1]])) ? $JSfieldsArray[$conf[1]] : '') :
                        ((isset($userCB[0]->{$conf[1]})) ? $userCB[0]->{$conf[1]} : '');
                }
                if ($value) {
                    $fieldId = '';
                    if (isset($highriseUser->{'contact-data'}->{'instant-messengers'}->{'instant-messenger'})) {
                        foreach ($highriseUser->{'contact-data'}->{'instant-messengers'} as $imx) {
                            foreach ($imx->{'instant-messenger'} as $ia) {
                                if ($ia->protocol == $im) {
                                    $fieldId = '<id type="integer">' . $ia->id[0] . "</id>\n";
                                    break;
                                }
                            }
                        }
                    }
                    $xml .= "<instant-messenger>\n" .
                        $fieldId .
                        "<address>".htmlspecialchars($value)."</address>\n" .
                        "<location>Work</location>\n" .
                        "<protocol>".$im."</protocol>\n" .
                        "</instant-messenger>";
                }
            }
        }
        $xml .= "\n</instant-messengers>\n";

        if (isset($config->website) && $config->website != '') {
            $xml .= "\n<web-addresses>\n";
            $conf = explode(';', $config->website);
            $value = ($conf[0] == 'js') ? ((isset($JSfieldsArray[$conf[1]])) ? $JSfieldsArray[$conf[1]] : '') :
                ((isset($userCB[0]->{$conf[1]})) ? $userCB[0]->{$conf[1]} : '');

            $fieldId = '';
            if (isset($highriseUser->{'contact-data'}->{'web-addresses'}->{'web-address'})) {
                foreach ($highriseUser->{'contact-data'}->{'web-addresses'} as $ws) {
                    foreach ($ws->{'web-address'} as $wa) {
                        if ($wa->location == 'Work') {
                            $fieldId = '<id type="integer">' . $wa->id[0] . "</id>\n";
                            break;
                        }
                    }
                }
            }
            $xml .= "<web-address>\n" .
                $fieldId .
                "<url>" . htmlspecialchars($value) . "</url>\n" .
                "<location>Work</location>\n" .
                "</web-address>\n" .
                "</web-addresses>\n";
        }

        if (isset($config->twitter) && $config->twitter != '') {
            $xml .= "\n<twitter-accounts>\n";
            $conf = explode(';', $config->twitter);
            $value = ($conf[0] == 'js') ? ((isset($JSfieldsArray[$conf[1]])) ? $JSfieldsArray[$conf[1]] : '') :
                ((isset($userCB[0]->{$conf[1]})) ? $userCB[0]->{$conf[1]} : '');
            $value = removeSpecialCharacters($value);
            $fieldId = '';
            if (isset($highriseUser->{'contact-data'}->{'twitter-accounts'}->{'twitter-account'})) {
                foreach ($highriseUser->{'contact-data'}->{'twitter-accounts'} as $tac) {
                    foreach ($tac->{'twitter-account'} as $ta) {
                        if ($ta->location == 'Personal') {
                            $fieldId = '<id type="integer">' . $ta->id[0] . "</id>\n";
                            break;
                        }
                    }
                }
            }
            $xml .= "<twitter-account>\n" .
                $fieldId .
                "<username>" . htmlspecialchars(str_replace(' ', '', $value)) . "</username>\n" .
                "<location>Personal</location>\n" .
                "</twitter-account>\n" .
                "</twitter-accounts>\n";
        }

        if(    ( isset($config->street) && $config->street != '' )
            || ( isset($config->city)   && $config->city != ''   )
            || ( isset($config->zip)    && $config->zip != ''    )
            || ( isset($config->state)  && $config->state != ''  )
            || ( isset($config->country)&& $config->country != '')){

            $xml .= "\n<addresses>\n";
            $xml .= "<address>\n";

            $fieldId = '';
            if (isset($highriseUser->{'contact-data'}->addresses->address)) {
                foreach ($highriseUser->{'contact-data'}->addresses as $ads) {
                    foreach ($ads->address as $ad) {
                        if ($ad->location == 'Work') {
                            $fieldId = '<id type="integer">' . $ad->id[0] . "</id>\n";
                            break;
                        }
                    }
                }
            }
            $xml .= $fieldId;

            if (isset($config->street) && $config->street != '') {
                $conf = explode(';', $config->street);
                $value = ($conf[0] == 'js') ? ((isset($JSfieldsArray[$conf[1]])) ? $JSfieldsArray[$conf[1]] : '') :
                    ((isset($userCB[0]->{$conf[1]})) ? $userCB[0]->{$conf[1]} : '');
                $xml .= "<street>" . htmlspecialchars($value) . "</street>\n";
            }
            if (isset($config->city) && $config->city != '') {
                $conf = explode(';', $config->city);
                $value = ($conf[0] == 'js') ? ((isset($JSfieldsArray[$conf[1]])) ? $JSfieldsArray[$conf[1]] : '') :
                    ((isset($userCB[0]->{$conf[1]})) ? $userCB[0]->{$conf[1]} : '');
                $xml .= "<city>" . htmlspecialchars($value) . "</city>\n";
            }
            if (isset($config->zip) && $config->zip != '') {
                $conf = explode(';', $config->zip);
                $value = ($conf[0] == 'js') ? ((isset($JSfieldsArray[$conf[1]])) ? $JSfieldsArray[$conf[1]] : '') :
                    ((isset($userCB[0]->{$conf[1]})) ? $userCB[0]->{$conf[1]} : '');
                $xml .= "<zip>" . htmlspecialchars($value) . "</zip>\n";
            }
            if (isset($config->state) && $config->state != '') {
                $conf = explode(';', $config->state);
                $value = ($conf[0] == 'js') ? ((isset($JSfieldsArray[$conf[1]])) ? $JSfieldsArray[$conf[1]] : '') :
                    ((isset($userCB[0]->{$conf[1]})) ? $userCB[0]->{$conf[1]} : '');
                $xml .= "<state>" . htmlspecialchars($value) . "</state>\n";
            }
            if (isset($config->country) && $config->country != '') {
                $conf = explode(';', $config->country);
                $value = ($conf[0] == 'js') ? ((isset($JSfieldsArray[$conf[1]])) ? $JSfieldsArray[$conf[1]] : '') :
                    ((isset($userCB[0]->{$conf[1]})) ? $userCB[0]->{$conf[1]} : '');
                $xml .= "<country>" . htmlspecialchars($value) . "</country>\n";
            }

            $xml .= "<location>Work</location>\n" .
                "</address>\n" .
                "</addresses>\n";
        }

        $xml .= "</contact-data>" .
            "\n</person>";

        $request['xml'] = $xml;

        $highrise->pushContact($request);
    }

    private function getCrmConfig ($crm) {
        $db = JFactory::getDBO();
        $query = $db->getQuery(true)
            ->select($db->qn('params'))
            ->from($db->qn('#__joomailermailchimpintegration_crm'))
            ->where($db->qn('crm') . ' = ' . $db->q($crm));
        $db->setQuery($query);

        return json_decode($db->loadResult());
    }

    public function onUserBeforeSave($oldUser, $isNew, $newUser) {
        //var_dump($oldUser, $isNew, $newUser);die;
        $listId = $this->params->get('listid');
        if (!$listId) {
            return;
        }

        $option = JRequest::getCmd('option');

        if ($option == 'com_comprofiler' && !$isNew && $oldUser['activation'] && JFactory::getApplication()->isSite()) {
            JRequest::setVar('component', $option);
            require_once(JPATH_SITE . '/components/com_joomailermailchimpsignup/models/joomailermailchimpsignup.php');
            $model = new JoomailerMailchimpSignupModelJoomailerMailchimpSignup();
            $model->register_save($oldUser['id']);
        }
    }

    public function onUserAfterSave($user, $isNew, $success, $msg) {
        $listId = $this->params->get('listid');
        if (!$listId) {
            return;
        }

        $option = JRequest::getCmd('option');
        $task = JRequest::getCmd('task');

        if (in_array($option, array('com_users', 'com_virtuemart'))
            || ($option == 'com_community' && $isNew && $user['activation'] && JFactory::getApplication()->isSite())
            || ($option == 'com_comprofiler' && $task == 'saveuseredit')) {
            JRequest::setVar('component', $option);
            require_once(JPATH_SITE . '/components/com_joomailermailchimpsignup/models/joomailermailchimpsignup.php');
            $model = new JoomailerMailchimpSignupModelJoomailerMailchimpSignup();
            $model->register_save($user['id']);
        }
    }

    // unsubscribe the user when his account is deleted and if this option is set in the plugin config
    public function onUserAfterDelete($user, $success, $msg) {
        $listId = $this->params->get('listid');
        if (!$listId) {
            return;
        }

        $unsubscribe = $this->params->get('unsubscribe', 0);

        if ($unsubscribe && $success) {
            //Unsubscribe the user
            $listId = $this->params->get('listid');
            $api = $this->getApiInstance();
            $result = $api->listUnsubscribe($listId, $user['email'], false, false, false);

            $db = JFactory::getDBO();
            $query = $db->getQuery(true)
                ->delete('#__joomailermailchimpintegration')
                ->where($db->qn('email') . ' = ' . $db->q($user['email']))
                ->where($db->qn('listid') . ' = ' . $db->q($listId));
            $db->setQuery($query)->execute();
        }
    }

    private function getListDetails() {
        require_once(JPATH_ADMINISTRATOR . '/components/com_joomailermailchimpintegration/models/lists.php');
        $listsModel = new joomailermailchimpintegrationModelLists();

        return $listsModel->getListDetails($this->params->get('listid'));
    }
}
