<?php
/**
 * Plugin Helper File
 *
 * @package         Modals
 * @version         5.3.0
 *
 * @author          Peter van Westen <peter@nonumber.nl>
 * @link            http://www.nonumber.nl
 * @copyright       Copyright © 2015 NoNumber All Rights Reserved
 * @license         http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */

defined('_JEXEC') or die;

require_once JPATH_PLUGINS . '/system/nnframework/helpers/functions.php';
require_once JPATH_PLUGINS . '/system/nnframework/helpers/text.php';
require_once JPATH_PLUGINS . '/system/nnframework/helpers/tags.php';
require_once JPATH_PLUGINS . '/system/nnframework/helpers/protect.php';

nnFrameworkFunctions::loadLanguage('plg_system_modals');

/**
 * Plugin that replaces stuff
 */
class plgSystemModalsHelper
{
	var $data_files = array();

	public function __construct(&$params)
	{
		$this->params = $params;

		$this->params->class = 'modal_link';
		// array_filter will remove any empty values
		$this->params->classnames = $this->params->autoconvert_classnames ? nnText::createArray(str_replace(' ', ',', trim($this->params->classnames))) : array();

		$this->params->tag = preg_replace('#[^a-z0-9-_]#si', '', $this->params->tag);

		$this->params->paramNamesCamelcase = array(
			'innerWidth', 'innerHeight', 'initialWidth', 'initialHeight', 'maxWidth', 'maxHeight', 'className',
		);
		$this->params->paramNamesLowercase = array_map('strtolower', $this->params->paramNamesCamelcase);
		$this->params->paramNamesBooleans = array(
			'scalephotos', 'scrolling', 'inline', 'iframe', 'fastiframe',
			'photo', 'preloading', 'retinaimage', 'open', 'returnfocus', 'trapfocus', 'reposition',
			'loop', 'slideshow', 'slideshowauto', 'overlayclose', 'closebutton', 'esckey', 'arrowkey', 'fixed'
		);

		if (JFactory::getApplication()->input->getInt('ml', 0))
		{
			JFactory::getApplication()->input->set('tmpl', $this->params->tmpl);
		}

		$this->params->disabled_components = array('com_acymailing');

		require_once __DIR__ . '/helpers/helpers.php';
		$this->helpers = plgSystemModalsHelpers::getInstance($params);
	}

	public function onContentPrepare(&$article, &$context)
	{
		nnFrameworkHelper::processArticle($article, $context, $this, 'replace');
	}

	public function onAfterDispatch()
	{
		// only in html
		if (JFactory::getDocument()->getType() !== 'html'
			&& JFactory::getDocument()->getType() !== 'feed'
		)
		{
			return;
		}

		$buffer = JFactory::getDocument()->getBuffer('component');

		if (empty($buffer) || is_array($buffer))
		{
			return;
		}

		// do not load scripts/styles on feed or print page
		if (JFactory::getDocument()->getType() !== 'feed'
			&& !JFactory::getApplication()->input->getInt('print', 0)
		)
		{
			$this->helpers->get('scripts')->loadScriptsStyles($buffer);
		}

		$this->replace($buffer, 'component');

		JFactory::getDocument()->setBuffer($buffer, 'component');
	}

	public function onAfterRender()
	{
		// only in html and feeds
		if (JFactory::getDocument()->getType() !== 'html' && JFactory::getDocument()->getType() !== 'feed')
		{
			return;
		}

		$html = JResponse::getBody();
		if ($html == '')
		{
			return;
		}

		// only do stuff in body
		list($pre, $body, $post) = nnText::getBody($html);
		$this->replace($body, 'body');

		if (strpos($body, $this->params->class) === false)
		{
			// remove style and script if no items are found
			$pre = preg_replace('#\s*<' . 'link [^>]*href="[^"]*/(modals/css|css/modals)/[^"]*\.css[^"]*"[^>]* />#s', '', $pre);
			$pre = preg_replace('#\s*<' . 'script [^>]*src="[^"]*/(modals/js|js/modals)/[^"]*\.js[^"]*"[^>]*></script>#s', '', $pre);
			$pre = preg_replace('#/\* START: Modals .*?/\* END: Modals [a-z]* \*/\s*#s', '', $pre);
		}

		$html = $pre . $body . $post;

		$this->cleanLeftoverJunk($html);

		JResponse::setBody($html);
	}

	public function replace(&$string, $area = 'article')
	{
		$this->helpers->get('replace')->replace($string, $area);
	}

	/**
	 * Just in case you can't figure the method name out: this cleans the left-over junk
	 */
	private function cleanLeftoverJunk(&$string)
	{
		$this->helpers->get('protect')->unprotectTags($string);

		nnProtect::removeFromHtmlTagContent($string, $this->params->protected_tags);
		nnProtect::removeInlineComments($string, 'Modals');
	}
}
