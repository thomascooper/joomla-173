<?php
// no direct access
defined( '_JEXEC' ) or die( 'Restricted access' );

jimport( 'joomla.plugin.plugin' );

class  plgSystemFullPageMemcache extends JPlugin
{
	function plgSystemFullPageMemcache(& $subject, $config) {
		parent::__construct($subject, $config);
	}

	function onAfterRender() {
		if(class_exists('Memcached')) {
			$mainframe = JFactory::getApplication();
			$req_token = str_replace(' ','%20',$_SERVER['REQUEST_URI'].$_SERVER['HTTP_USER_AGENT']);
			$utf_token = mb_convert_encoding($req_token, "UTF-8");
			$m = new Memcached();
			$m->setOption(Memcached::OPT_BINARY_PROTOCOL, true);
			$m->setOption(Memcached::OPT_COMPRESSION, true);
			$m->addServers(array(
				array('10.1.1.36', 11211, 20)
			));

			$namespace = '__ns__'.$_SERVER['HTTP_HOST'];
			$key = $namespace.md5($_SERVER['REQUEST_METHOD'] . ' '. $_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'].$_SERVER['HTTP_USER_AGENT']) . 0;

			$buffer = JResponse::toString($mainframe->getCfg('gzip'));
			$data = "EXTRACT_HEADERS\r\nContent-Type: gzip; charset=UTF-8\r\n";
			$data .= "\r\n";
			$data .= $buffer."\r\n";
			$data .= '<!-- Memcached using key:'.$key.' @ '.date('Y-m-d H:i:s').' uri: '.$_SERVER['REQUEST_URI'].' //-->';

			// Set duration in minutes
			$minutes = 15;

			$duration = $minutes * 60;
			$ret = $m->add($key,$data,$duration);
		}
	}
}
