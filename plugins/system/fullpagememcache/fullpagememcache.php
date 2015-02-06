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
			if(!$mainframe->isAdmin() && JFactory::getUser()->guest) {
				//if($this->isEnabled() == false) return false;
				//$body = JResponse::getBody();

				// Get all local CSS files
				//$matches['css'] = $this->getCssMatches($body);

				// Remove all found files from body
				//$body = $this->cleanup($body, $matches);

				setcookie('jfpmc','',time()-3600,'/');

				$m = new Memcached();
				$m->setOption(Memcached::OPT_BINARY_PROTOCOL, true);
				$m->setOption(Memcached::OPT_COMPRESSION, $mainframe->getCfg('gzip'));
				$port = $mainframe->getCfg('memcache_server_port');
				$port = '11211';
				$m->addServers(array(
					array($mainframe->getCfg('memcache_server_host'), $port, 20)
				));

				$namespace = '__ns__'.$_SERVER['HTTP_HOST'];
				$key = $namespace.md5($_SERVER['REQUEST_METHOD'] . ' '. $_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'].$_SERVER['HTTP_USER_AGENT']) . 0;

				//$buffer = JResponse::toString($mainframe->getCfg('gzip'));
				if ($mainframe->getCfg('gzip')){
					$buffer = gzencode(JResponse::getBody());
				}else{
					$buffer = JResponse::getBody();
				}

				// Add nginx enhanced memcached headers and memcached tag
				$data = "EXTRACT_HEADERS\r\nContent-Type: text\html; charset=UTF-8\r\n";
				$data .= "\r\n";
				$data .= $buffer."\r\n";
				$data .= '<!-- Memcached using key:'.$key.' @ '.date('Y-m-d H:i:s').' uri: '.$_SERVER['REQUEST_URI'].' //-->';

				// Set duration in minutes
				$minutes = $mainframe->getCfg('cachetime');

				$duration = $minutes * 60;
				$ret = $m->add($key,$data,$duration);

				//JResponse::setBody($buffer);
			}
		}
	}
    /**
     * Load the parameters
     *
     * @param null
     * @return JParameter
     */
    private function getParams()
    {
        jimport('joomla.version');
        $version = new JVersion();
        if(version_compare($version->RELEASE, '1.5', 'eq')) {
            $plugin = JPluginHelper::getPlugin('system', 'scriptmerge');
            $params = new JParameter($plugin->params);
            return $params;
        } else {
            return $this->params;
        }
    }

    private function getCssMatches($body = null)
    {
        // Remove conditional comments from matching
        $buffer = preg_replace('/<!--(.*)-->/msU', '', $body);

        // Detect all CSS 
        preg_match_all('/<link(.*)href="([^\"]+)"(.*)>/msU', $buffer, $matches);

        // Parse all the matched entries
        $files = array();
        if(isset($matches[2])) {

            // Get the exclude-matches
            $exclude_css = explode(',', $this->getParams()->get('exclude_css'));
            if(!empty($exclude_css)) {
                foreach($exclude_css as $i => $e) {
                    $e = trim($e);
                    if(empty($e)) {
                        unset($exclude_css[$i]);
                    } else {
                        $exclude_css[$i] = $e;
                    }
                }
            }

            // Loop through the rules
            foreach($matches[2] as $index => $match) {

                // Skip certain entries
                if(strpos($matches[0][$index], 'stylesheet') == false && strpos($matches[0][$index], 'css') == false) continue;
                if(strpos($matches[0][$index], 'media="print"')) continue;

                // Only try to match local CSS
                $match = str_replace(JURI::base(), '', $match);
                $match = preg_replace('/^'.str_replace('/', '\/', JURI::base(true)).'/', '', $match);
                if(preg_match('/\.css$/', $match) && !preg_match('/^http:\/\//', $match)) {

                    // Only include files that can be read
                    $file = $match;

                    // Try to determine the path to this file
                    $filepath = JPATH_BASE.$file;

                    if(!empty($filepath)) {
                        $files[] = array(
                            'remote' => 0,
                            'file' => $filepath,
                            'html' => $matches[0][$index],
                        );
                    }
                }
            }
        }

        return $files;
    }

    /**
     * Method to remove obsolete tags in the HTML body
     *
     * @param string $body 
     * @param array $files
     * @return string
     */
    private function cleanup($body = null, $matches = array())
    {
        foreach($matches as $typename => $type) {
            if(!empty($type)) {

                $first = true;
                foreach($type as $file) {

                    if($first) {
                        $replacement = '<!-- plg_scriptmerge_'.md5($typename).' -->';
                        $first = false;
                    } else {
                        $replacement = '';
                    }

                    $body = str_replace($file['html'], $replacement, $body);
                }
            }
        }

        return $body;
    }
}
