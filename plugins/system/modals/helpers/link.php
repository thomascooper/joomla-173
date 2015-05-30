<?php
/**
 * Plugin Helper File: Link
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

class plgSystemModalsHelperLink
{
	var $helpers = array();

	public function __construct()
	{
		require_once __DIR__ . '/helpers.php';
		$this->helpers = plgSystemModalsHelpers::getInstance();
		$this->params = $this->helpers->getParams();
	}

	public function buildLink($attributes, $data, $content = '')
	{

		$isexternal = $this->helpers->get('file')->isExternal($attributes->href);
		$ismedia = $this->helpers->get('file')->isMedia($attributes->href);
		$isiframe = $this->helpers->get('file')->isIframe($attributes->href, $data);

		if ($ismedia)
		{
			$auto_titles = isset($data['title']) ? 0 : (isset($data['auto_titles']) ? $data['auto_titles'] : $this->params->auto_titles);
			$title_case = isset($data['title_case']) ? $data['title_case'] : $this->params->title_case;
			if ($auto_titles)
			{
				$data['title'] = $this->helpers->get('file')->getTitle($attributes->href, $title_case);
			}
		}
		unset($data['auto_titles']);

		// Force/overrule certain data values
		if ($isiframe || ($isexternal && !$ismedia))
		{
			// use iframe mode for external urls
			$data['iframe'] = 'true';
			$this->helpers->get('data')->setDataWidthHeight($data, $isexternal);
		}

		if ($attributes->href && $attributes->href['0'] != '#' && !$isexternal && !$ismedia)
		{
			$this->helpers->get('scripts')->addTmpl($attributes->href, $isiframe);
		}


		if (empty($data['title']) && empty($attributes->{'data-modal-title'}))
		{
			$data['classname'] = (isset($data['classname']) ? $data['classname'] . ' ' : '') . 'no_title';
			$data['title'] = '';
		}

		if (!empty($data['autoclose']))
		{
			$data['title'] .= '<div class="countdown"></div>';
		}

		return
			'<a'
			. $this->helpers->get('data')->flattenAttributeList($attributes)
			. $this->helpers->get('data')->flattenDataAttributeList($data)
			. '>'
			. $content;
	}

	public function getLink($string, $link = '', $content = '')
	{
		$attributes = $this->prepareLinkAttributeList($link);

		// map href to url
		$string = preg_replace('#^href=#', 'url=', $string);

		$tag = nnTags::getTagValues(
			$string,
			($attributes->href ? array() : array('url'))
		);

		$data = array();
		if (!empty($tag->url))
		{
			$attributes->href = $tag->url;
		}
		unset($tag->url);

		$extra = '';


		$attributes->id = !empty($tag->id) ? ' id="' . $tag->id . '"' : '';
		unset($tag->id);

		$attributes->class .= !empty($tag->class) ? ' ' . $tag->class : '';
		unset($tag->class);

		// move onSomething params to attributes, except the modal callbacks
		$callbacks = array('onopen', 'onload', 'oncomplete', 'oncleanup', 'onclosed');
		foreach ($tag as $key => $val)
		{
			if (
				substr($key, 0, 2) == 'on'
				&& !in_array(strtolower($key), $callbacks)
				&& is_string($val)
			)
			{
				$attributes->$key = $val;
				unset($tag->$key);
			}
		}

		// set data by keys set in tag without values (and see them as true)
		foreach ($tag->params as $key)
		{
			$data[strtolower($key)] = 'true';
		}
		unset($tag->params);


		// set data by values set in tag
		foreach ($tag as $key => $val)
		{
			$data[strtolower($key)] = $val;
		}

		return array($this->buildLink($attributes, $data, $content), $extra);
	}


	private function prepareLinkAttributeList($link)
	{
		$attributes = new stdClass;
		$attributes->href = '';
		$attributes->class = $this->params->class;
		$attributes->id = '';

		if (!$link)
		{
			return $attributes;
		}

		$link_attributes = $this->getLinkAttributeList(trim($link));

		foreach ($link_attributes as $key => $val)
		{
			$key = trim($key);
			$val = trim($val);

			if ($key == '' || $val == '')
			{
				continue;
			}

			if ($key == 'class')
			{
				$attributes->{$key} = trim($attributes->{$key} . ' ' . $val);
				continue;
			}

			$attributes->{$key} = $val;
		}

		return $attributes;
	}

	public function getLinkAttributeList($string)
	{
		$attributes = new stdClass;

		if (!$string)
		{
			return $attributes;
		}

		if (preg_match_all('#([a-z0-9_-]+)="([^\"]*)"#si', $string, $params, PREG_SET_ORDER) < 1)
		{
			return $attributes;
		}

		foreach ($params as $param)
		{
			$attributes->$param['1'] = $param['2'];
		}

		return $attributes;
	}

}
