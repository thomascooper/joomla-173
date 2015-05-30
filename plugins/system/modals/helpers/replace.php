<?php
/**
 * Plugin Helper File: Replace
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

class plgSystemModalsHelperReplace
{
	var $helpers = array();

	public function __construct()
	{
		require_once __DIR__ . '/helpers.php';
		$this->helpers = plgSystemModalsHelpers::getInstance();
		$this->params = $this->helpers->getParams();

		$this->params->tag = trim($this->params->tag);

		$bts = '((?:<(?:p|span|div)(?:(?:\s|&nbsp;)[^>]*)?>\s*){0,3})'; // break tags start
		$bte = '((?:\s*</(?:p|span|div)>){0,3})'; // break tags end

		$regex_a = nnText::getTagRegex('a', false, false);
		$regex_span_img = nnText::getTagRegex(array('span', 'i', 'img'));
		$regex_text_whitespace = '[^<\{]*';

		$this->params->regex = '#'
			. $bts
			. '\{' . $this->params->tag . '(?:\s|&nbsp;)+'
			. '((?:[^\}]*?\{[^\}]*?\})*[^\}]*?)'
			. '\}'
			. $bte
			. '\s*(.*?)\s*'
			. $bts
			. '\{\/' . $this->params->tag . '\}'
			. $bte
			. '#s';
		$this->params->regex_end = '#'
			. $bts
			. '\{\/' . $this->params->tag . '\}'
			. $bte
			. '#s';
		$this->params->regex_inlink = '#'
			. '(' . $regex_a . $regex_text_whitespace . ')((?:' . $regex_span_img . $regex_text_whitespace . ')*)'
			. '\{' . $this->params->tag
			. '((?:(?:\s|&nbsp;)+(?:[^\}]*?\{[^\}]*?\})*[^\}]*?)?)'
			. '\}'
			. '(.*?)'
			. '\{\/' . $this->params->tag . '\}'
			. '((?:' . $regex_text_whitespace . $regex_span_img . ')*)' . $regex_text_whitespace . '<\/a>'
			. '#s';
		$this->params->regex_link = '#'
			. '<a(?:\s|&nbsp;|&\#160;)[^>"]*(?:"[^"]*"[^>"]*)*>'
			. '#s';
	}

	public function replace(&$string, $area = 'article')
	{
		if (!is_string($string) || $string == '')
		{
			return;
		}

		nnProtect::removeFromHtmlTagAttributes(
			$string, array(
				$this->params->tag,
			)
		);

		// allow in component?
		if ($area == 'component' && in_array(JFactory::getApplication()->input->get('option'), $this->params->disabled_components))
		{
			$this->helpers->get('protect')->protectTags($string);

			return;
		}

		$this->helpers->get('protect')->protect($string);

		// Handle content inside the iframed modal
		if (JFactory::getApplication()->input->getInt('ml', 0) && JFactory::getApplication()->input->getInt('iframe', 0))
		{
			$this->replaceInsideModal($string);

			nnProtect::unprotect($string);

			return;
		}

		$this->replaceLinks($string);

		// tag syntax inside links
		$this->replaceTagSyntaxInsideLinks($string);

		// tag syntax
		$this->replaceTagSyntax($string);

		// closing tag
		$this->replaceClosingTag($string);


		nnProtect::unprotect($string);
	}

	// add ml to internal links
	private function replaceInsideModal(&$string)
	{
		if (preg_match_all($this->params->regex_link, $string, $matches, PREG_SET_ORDER) < 1)
		{
			return;
		}

		foreach ($matches as $match)
		{
			// get the link attributes
			$attributes = $this->helpers->get('link')->getLinkAttributeList($match['0']);

			// ignore if the link has no href or is an anchor or has a target
			if (empty($attributes->href) || $attributes->href['0'] != '#' || isset($attributes->target))
			{
				continue;
			}

			// ignore if link is external or an image
			if ($this->helpers->get('file')->isExternal($attributes->href) || $this->helpers->get('file')->isMedia($attributes->href))
			{
				continue;
			}

			$href = $attributes->href;
			$this->helpers->get('scripts')->addTmpl($attributes->href, 1);
			$this->replaceOnce('href="' . $href . '"', 'href="' . $attributes->href . '"', $string);
		}
	}

	private function replaceTagSyntaxInsideLinks(&$string)
	{
		if (preg_match_all($this->params->regex_inlink, $string, $matches, PREG_SET_ORDER) < 1)
		{
			return;
		}

		foreach ($matches as $match)
		{
			$data = preg_replace('#^(\s|&nbsp;|&\#160;)*#', '', $match['3']);
			$content = trim($match['2'] . $match['4'] . $match['5']);

			list($link, $extra) = $this->helpers->get('link')->getLink($data, $match['1'], $content);
			$link .= '</a>';

			$this->replaceOnce($match['0'], $link, $string, $extra);
		}
	}

	private function replaceTagSyntax(&$string)
	{
		if (preg_match_all($this->params->regex, $string, $matches, PREG_SET_ORDER) < 1)
		{
			return;
		}

		foreach ($matches as $match)
		{
			list($pre, $post) = nnTags::setSurroundingTags($match['1'], $match['3']);
			list($link, $extra) = $this->helpers->get('link')->getLink($match['2'], '', trim($match['4']));

			$html = $post . $pre . $link;

			list($pre, $post) = nnTags::setSurroundingTags($match['5'], $match['6']);
			$html .= $pre . '</a>' . $post;

			$this->replaceOnce($match['0'], $html, $string, $extra);
		}
	}

	private function replaceClosingTag(&$string)
	{
		if (preg_match_all($this->params->regex_end, $string, $matches, PREG_SET_ORDER) < 1)
		{
			return;
		}

		foreach ($matches as $match)
		{
			list($pre, $post) = nnTags::setSurroundingTags($match['1'], $match['2']);
			$html = $pre . '</a>' . $post;
			$this->replaceOnce($match['0'], $html, $string);
		}
	}

	private function replaceLinks(&$string)
	{
		if (
			(
				empty($this->params->classnames)
				&& !preg_match('#class="[^"]*(' . implode('|', $this->params->classnames) . ')#s', $string)
			)
		)
		{
			return;
		}

		if (preg_match_all($this->params->regex_link, $string, $matches, PREG_SET_ORDER) < 1)
		{
			return;
		}

		foreach ($matches as $match)
		{
			$this->replaceLink($string, $match);
		}
	}

	private function replaceLink(&$string, $match)
	{
		// get the link attributes
		$attributes = $this->helpers->get('link')->getLinkAttributeList($match['0']);

		if (!$this->helpers->get('pass')->passLinkChecks($attributes))
		{
			return;
		}

		$data = array();
		$isexternal = $this->helpers->get('file')->isExternal($attributes->href);
		$ismedia = $this->helpers->get('file')->isMedia($attributes->href);
		$iframe = $this->helpers->get('file')->isIframe($attributes->href, $data);

		// Force/overrule certain data values
		if ($iframe || ($isexternal && !$ismedia))
		{
			// use iframe mode for external urls
			$data['iframe'] = 'true';
			$this->helpers->get('data')->setDataWidthHeight($data, $isexternal);
		}

		$attributes->class = !empty($attributes->class) ? $attributes->class . ' ' . $this->params->class : $this->params->class;
		$link = $this->helpers->get('link')->buildLink($attributes, $data);

		$this->replaceOnce($match['0'], $link, $string);
	}

	private function replaceOnce($search, $replace, &$string, $extra = '')
	{
		if (!$extra
			|| !preg_match('#' . preg_quote($search, '#') . '(.*?</(?:div|p)>)#', $string, $match)
		)
		{
			$string = nnText::strReplaceOnce($search, $replace . $extra, $string);

			return;
		}

		// Place the extra div stuff behind the first ending div/p tag
		$string = nnText::strReplaceOnce(
			$match['0'],
			$replace . $match['1'] . $extra,
			$string
		);
	}

}
