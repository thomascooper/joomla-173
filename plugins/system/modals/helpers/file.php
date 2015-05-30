<?php
/**
 * Plugin Helper File: File
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

class plgSystemModalsHelperFile
{
	var $helpers = array();

	public function __construct()
	{
		require_once __DIR__ . '/helpers.php';
		$this->helpers = plgSystemModalsHelpers::getInstance();
		$this->params = $this->helpers->getParams();

		$this->params->mediafiles = nnText::createArray(strtolower($this->params->mediafiles));
		$this->params->iframefiles = nnText::createArray(strtolower($this->params->iframefiles));
	}

	public function isExternal($url)
	{
		if (strpos($url, '://') === false)
		{
			return 0;
		}

		// hostname: give preference to SERVER_NAME, because this includes subdomains
		$hostname = ($_SERVER['SERVER_NAME']) ? $_SERVER['SERVER_NAME'] : $_SERVER['HTTP_HOST'];

		return !(strpos(preg_replace('#^.*?://#', '', $url), $hostname) === 0);
	}

	public function isMedia($url, $filetypes = array(), $ignore = 0)
	{
		$filetype = $this->getFiletype($url);
		if (!$filetype)
		{
			return 0;
		}
		if (empty($filetypes))
		{
			$filetypes = $this->params->mediafiles;
			$ignore = 0;
		}

		$pass = in_array($filetype, $filetypes);

		return $ignore ? !$pass : $pass;
	}

	public function isIframe($url, &$data)
	{
		if (!empty($data['inline']))
		{
			return false;
		}

		if ($this->isMedia($url, $this->params->iframefiles))
		{
			return true;
		}

		if ($this->isMedia($url))
		{
			unset($data['iframe']);

			return false;
		}

		if (empty($data['iframe']))
		{
			return $this->params->iframe;
		}

		return ($data['iframe'] !== 0 && $data['iframe'] != 'false');
	}

	public function getFiletype($url)
	{
		$info = pathinfo($url);
		if (!isset($info['extension']))
		{
			return '';
		}

		$ext = explode('?', $info['extension']);

		return strtolower($ext['0']);
	}

	public function getFileName($url)
	{
		return basename($url);
	}

	public function getFileTitle($url)
	{
		$info = pathinfo($url);

		return isset($info['filename']) ? $info['filename'] : '';
	}

	public function getFilePath($url)
	{
		return dirname($url) . '/';
	}

	public function getTitle($url, $case)
	{
		$title = basename($url);
		$title = explode('.', $title);
		$title = $title['0'];
		$title = preg_replace('#[_-]([0-9]+|[a-z])$#i', '', $title);
		$title = str_replace(array('-', '_'), ' ', $title);

		switch ($case)
		{
			case 'lowercase':
				$title = mb_strtolower($title);
				break;
			case 'uppercase':
				$title = mb_strtoupper($title);
				break;
			case 'uppercasefirst':
				$title = mb_strtoupper(mb_substr($title, 0, 1))
					. mb_strtolower(mb_substr($title, 1));
				break;
			case 'titlecase':
				$title = mb_convert_case(mb_strtolower($title), MB_CASE_TITLE);
				break;
			case 'titlecase_smart':
				$title = mb_convert_case(mb_strtolower($title), MB_CASE_TITLE);
				$lowercase_words = explode(',', ' ' . str_replace(',', ' , ', mb_strtolower($this->params->lowercase_words)) . ' ');
				$title = str_ireplace($lowercase_words, $lowercase_words, $title);
				break;
		}

		return $title;
	}

	public function trimFolder($folder)
	{
		return trim(str_replace(array('\\', '//'), '/', $folder), '/');
	}
}
