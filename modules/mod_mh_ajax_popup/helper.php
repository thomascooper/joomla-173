<?php
/**
 * @author     mediahof, Kiel-Germany
 * @link       http://www.mediahof.de
 * @copyright  Copyright (C) 2011 - 2014 mediahof. All rights reserved.
 * @license    GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

defined('_JEXEC') or die;

final class mod_mh_ajax_popup
{

    private $params;
    public $css;
    public $content;

    public function __construct(JRegistry &$params, stdClass &$module)
    {
        $input = JFactory::getApplication()->input;
        $this->params = $params;
        $this->css = new stdClass;

        $cookieName = 'mhap_' . $module->id;

        $appear = $this->params->get('appear', 0);

        if ($appear == 0 || $input->cookie->get($cookieName, 0) <= $appear) {
            $input->cookie->set($cookieName, ($input->cookie->get($cookieName) + 1));
            $this->loadContent();
            $this->setCSS();
        }
    }

    private function loadContent()
    {
        $db = JFactory::getDbo();

        $query = $db->getQuery(true)
            ->select(array('c.introtext', 'c.fulltext'))
            ->from('#__content AS c')
            ->where('c.state = 1')
            ->where('c.id = ' . $this->params->get('contentid'));

        $db->setQuery($query);
        $row = $db->loadObject();

        if (empty($row)) {
            return;
        }

        $this->content = !empty($row->fulltext) ? $row->fulltext : $row->introtext;
        $this->content = JHtml::_('content.prepare', $this->content);
    }

    private function setCSS()
    {
        $this->css->popup = 'position:absolute;'
            . 'opacity:0;'
            . 'filter:alpha(opacity=0);'
            . 'display:none;'
            . 'z-index:999;'
            . 'width:' . $this->params->get('width') . 'px;'
            . 'height:' . $this->params->get('height') . 'px;'
            . 'top:' . $this->params->get('positionTop', 0) . 'px;'
            . 'left:' . $this->params->get('positionLeft', 0) . 'px;';

        if ($this->params->get('backgroundColor')) {
            $this->css->popup .= 'background-color:' . $this->params->get('backgroundColor') . ';';
        }

        if ($this->params->get('textColor')) {
            $this->css->popup .= 'color:' . $this->params->get('textColor') . ';';
        }

        $this->css->title = 'width:100%;'
            . 'height:' . $this->params->get('titleHeight') . 'px;'
            . 'background:' . $this->params->get('titleBG') . ';'
            . 'text-align: center;';

        $this->css->link = 'cursor:pointer;'
            . 'display:block;'
            . 'position:absolute;'
            . 'text-decoration:none;'
            . 'top:0;'
            . 'left:0;'
            . 'height:' . $this->params->get('titleHeight') . 'px;'
            . 'width:' . $this->params->get('width') . 'px;'
            . 'color:' . $this->params->get('titleColor') . ';'
            . 'line-height:' . $this->params->get('titleHeight') . 'px;';

        $this->css->div = 'overflow:auto;' . 'height:' . ($this->params->get('titleHide') != '1' ? $this->params->get('height') - $this->params->get('titleHeight') : $this->params->get('height')) . 'px;';
    }
}