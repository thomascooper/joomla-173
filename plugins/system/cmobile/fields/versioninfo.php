<?php
/**
 * @package		Simple Mobile Detection
 * @subpackage	plg_cmobile
 * @copyright	Copyright (C) 2013 Conflate. All rights reserved.
 * @license		GNU General Public License <http://www.gnu.org/copyleft/gpl.html>
 * @link		http://www.joomla-specialist.net
 */

defined('_JEXEC') or die('Restricted access');

class JFormFieldVersionInfo extends JFormField{

	protected $type = 'VersionInfo';
	private $plgManifest = null;
	
	public function __construct($form = null){
		parent::__construct($form);
		$this->plgManifest = JFactory::getXML(dirname(dirname(__FILE__)) . '/cmobile.xml');
	}

	protected function getLabel(){
		$html = array();
		
		$class = !empty($this->class) ? ' class="' . $this->class . '"' : '';
		if($this->element['hr']){
			$html[] = '<hr' . $class . ' />';
		}
		$html[] = '<span id="plg-info" ' . $class . '>';
		$html[] = '<img src="' . $this->plgManifest->image . '" alt="">';
		$html[] = '</span>';

		return implode('', $html);
	}

	protected function getInput(){
		//display version info
		$html = array();
		$class = !empty($this->class) ? ' class="' . $this->class . '"' : '';
		
		if($this->element['hr']){
			$html[] = '<hr' . $class . ' />';
		}
		$html[] = '<span' . $class . '>';
		$html[] = '<p class="plg-name"><h3>' . JText::_($this->plgManifest->name) . '</h3></p>';
		$html[] = '<p class="plg-version"><h4>'.JText::_('PLG_CONFLATE_INFO_VERSION').': ' . $this->plgManifest->version . '</h4>';
		if($this->plgManifest->extensionUrl){
			$html[] = '<span class="plg-url"><a href="'.$this->plgManifest->extensionUrl.'" target="_blank">'.JText::_('PLG_CONFLATE_INFO_EXTENSION_PAGE').'</a></span>, ';
		}
		if($this->plgManifest->supportUrl){
			$html[] = '<span class="plg-url"><a href="'.$this->plgManifest->supportUrl.'" target="_blank">'.JText::_('PLG_CONFLATE_INFO_SUPPORT_FORUM').'</a></span></p>';
		}
		if(!$this->plgManifest->extensionUrl && !$this->plgManifest->supportUrl){
			$html[] = '<span class="plg-url"><a href="'.$this->plgManifest->authorUrl.'" target="_blank">'.$this->plgManifest->authorUrl.'</a></span></p>';
		}
		$html[] = '<p><small class="plg-author">--<br />' . $this->plgManifest->author . '</small></p>';
		$html[] = '</span>';
		
		//Where to display the version info
		if(version_compare(JVERSION,'3.2.0','ge')){
			// Joomla 3.2
		}elseif(version_compare(JVERSION,'3.0.0','ge')){
			// Joomla 3.0/1
			$html[] = "<script>$('plg-info').getParent('div.control-group').replaces($('details').getChildren('div.control-group')[0]);</script>";
		}elseif(version_compare(JVERSION,'2.5.0','ge')){
			// Joomla 2.5
			$html[] = "<script>$$('fieldset.adminform')[0].grab($$('fieldset.panelform li')[0]);</script>";
		}
		

		return implode('', $html);
	}

	/**
	 * Method to get the field title.
	 *
	 * @return  string  The field title.
	 *
	 * @since   11.1
	 */
	protected function getTitle()
	{
		return $this->getLabel();
	}
}
