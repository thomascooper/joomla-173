<?php
/**
 * @package		Simple Mobile Detection
 * @subpackage	plg_cmobile
 * @copyright	Copyright (C) 2013 Conflate. All rights reserved.
 * @license		GNU General Public License <http://www.gnu.org/copyleft/gpl.html>
 * @link		http://www.joomla-specialist.net
 */

defined('_JEXEC') or die('Restricted access');

jimport('joomla.plugin.plugin');

class plgSystemCMobile extends JPlugin{

	function __construct( &$subject, $params ){
		parent::__construct( $subject, $params );
		$this->_plugin = JPluginHelper::getPlugin( 'system', 'cmobile' );
		$this->_params = new JRegistry( $this->_plugin->params );
	}
	
	function onAfterInitialise(){
		$app = JFactory::getApplication();
		if ( $app->isAdmin() ) {
			return;
		}
		
		jimport('joomla.environment.browser');
		$browser = JBrowser::getInstance();
		$agent = $browser->getAgentString();
		
		//Should an extra detection system be loaded
		$loadDS = $this->_params->get('detection_system', false);
		$isMobileDS = false; //extra check by the detection system if it's mobile
		
		//check if the plugin is disabled. I.e. for allowing desktop versions to display
		$enabled = $app->getUserStateFromRequest('cmobile.isenabled', 'cmobile', true, 'bool');
		
		//If the site in development
		$devMode = false;
		if($this->_params->get('devmode', false)){
			$devModeType = $this->_params->get('devmodetype', 'always');
			if($devModeType == 'ip'){
				$ip_addresses = array_filter(explode("\r\n", $this->_params->get('devmodeip', '')));
				if(!empty($ip_addresses)){
					foreach($ip_addresses as $ip){
						if($app->input->server->get('REMOTE_ADDR', false) === $ip){
							$devMode = true;
							break;
						}
					}
				}
			}elseif($devModeType == 'always'){
				$devMode = true;
			}
		}
		
		//if iPad is excluded, disable the plugin for iPads
		if(stristr($agent, 'ipad') && $this->_params->get('ipad_exclude', false)){
			$enabled = false;
		}
		
		if($loadDS){
			$DSFilename = dirname(__FILE__) . '/ds/';
			switch($loadDS){
				case 'md':
					$DSFilename .= 'Mobile_Detect.php'; 
					if(file_exists($DSFilename)){
						require_once $DSFilename;
						$loadDS = new Mobile_Detect();
						$isMobileDS = $loadDS->isMobile();
					}
					break;
				case 'esp':
					$DSFilename .= 'mdetect.php';
					if(file_exists($DSFilename)){
						require_once $DSFilename;
						$loadDS = new uagent_info();
						$isMobileDS = ($loadDS->DetectTierIphone() || $loadDS->DetectMobileLong() || $loadDS->DetectTierTablet());
					}
					break;
				default:
					$loadDS = false;
					$isMobileDS = false;
					break;
			}
		}

		if(	$devMode || //is the plugin in development mode
			
			( $loadDS && $isMobileDS ) || //is this a mobile site detected by the selected detection system
		
			( !$loadDS && $browser->isMobile() || stristr($agent, 'mobile') ) || //default plugin and Joomla detection system
		
			( $this->_params->get('allandroid') && stristr($agent, 'android') ) //no matter what, if the switch all android is on, it's always a mobile device
				
		){
			
			$device = new JRegistry();

			if(stristr($agent, 'ipad')){
				//iPad
				$device->set('name', 'ipad');
			}elseif(stristr($agent, 'ipod')){
				//iPod
				$device->set('name', 'ipod');
			}elseif(stristr($agent, 'iphone')){
				//iPhone
				$device->set('name', 'iphone');
			}elseif(stristr($agent, 'android')){
				//Android device
				$device->set('name', 'android');
				//try to find more info

				//Try to find the model and android build
				$regex = '/.+\;\ (.*?Build.*?)\)/is';
				$matches = array();
				preg_match_all($regex, $agent, $matches);
				if(isset($matches[1][0])){
					//It found something with Build in it
					//The part before Build should be the device model, the part after, the build
					$spl = preg_split('/Build\//i', $matches[1][0]);
					if(count($spl) > 1){
						//The first part is the model
						$device->set('model', $spl[0]);
						//The second part is the build
						$device->set('build', $spl[1]);
					}
				}
			}elseif($devMode){
				//Development mode
				$device->set('name', 'devmode');
			}else{
				$device->set('name', $browser->getBrowser());
			}
			
			$device->set('browser', $browser->getBrowser());
			$device->set('platform', $browser->getPlatform());
			//$device->set('os', php_uname('a'));
			
			if($enabled){
				//check if specific options should be used
				$deviceName = $device->get('name');
				$useSpecific = ($deviceName?$this->_params->get($deviceName . '_usespecific', false):false);

				//redirection options
				$redirect = $this->_params->get(($useSpecific?$deviceName . '_':'') . 'redirectmobile', false);
				$redirectPage = $this->_params->get(($useSpecific?$deviceName . '_':'') . 'redirectpage', false);
				$redirectOnce = $this->_params->get(($useSpecific?$deviceName . '_':'') . 'redirectonce', false);
				$mobiledomain = $this->_params->get(($useSpecific?$deviceName . '_':'') . 'mobiledomain', false);
			
				//template options
				$setTemplate = $this->_params->get(($useSpecific?$deviceName . '_':'') . 'settemplate', false);
				$template = $this->_params->get(($useSpecific?$deviceName . '_':'') . 'template', false);
				
				//viewport options
				$setViewport = $this->_params->get(($useSpecific?$deviceName . '_':'') . 'setviewport', false);
				$viewportContent = $this->_params->get(($useSpecific?$deviceName . '_':'') . 'viewport_content', false);
			
				//check if the site should be redirected
				if($redirect && (!$app->getUserState('cmobile.isredirected', false) || !$redirectOnce)){
					$uri = JFactory::getURI();
				
					//check if the mobile url is on the left part of the current site, that way we know no redirection needs to be done
					if(substr($uri->current(), 0, strlen($mobiledomain)) != $mobiledomain){
						$app->setUserState('cmobile.isredirected', true);
						$page = ltrim($uri->toString(array('path', 'query')), '/');
						
						if(preg_match('/^http(?:s)?\:\/\/.*/i', $mobiledomain)){
							$app->redirect($mobiledomain . ($redirectPage?'/'.$page:''));
						}else{
							//check if the domain is the same as the host
							if($uri->getHost() != $mobiledomain){
								$url = $uri->getScheme() . '://' . $mobiledomain;
								$app->redirect($url . ($redirectPage?'/'.$page:''));
							}
						}
					}
				}
			
				if($setTemplate){
					//set the template
					if($template && $template != -1) JRequest::setVar('templateStyle', $template); 
				}
				if($setViewport){
					$doc = JFactory::getDocument();
					$doc->setMetaData('viewport', $viewportContent);
				}
				$app->setUserState('cmobile.ismobile', true);
			}else{
				$app->setUserState('cmobile.ismobile', false);
			}
			$app->setUserState('cmobile.isdevice', true);
			$app->setUserState('cmobile.device', $device->toString());
			$app->setUserState('cmobile.detection.system', $loadDS);
			$app->input->def('cmobile', true);
		}else{
			$app->setUserState('cmobile.ismobile', false);
			$app->setUserState('cmobile.isdevice', false);
			$app->setUserState('cmobile.device', '{}');
			$app->setUserState('cmobile.detection.system', false);
			$app->input->def('cmobile', false);
		}
		
		//Handle the output when the system is being cached, make sure the cached output is different for mobile and desktop
		$cachingOn = true;
		
		if($cachingOn){
			$registeredurlparams = !empty($app->registeredurlparams) ? $app->registeredurlparams : new stdClass;
			$registeredurlparams->cmobile = 'BOOLEAN';
			$app->registeredurlparams = $registeredurlparams;
		}
	}
	
	
	/* 
	 * BETA Feature, please leave feedback in the forum if you find any bugs or improvements 
	 * http://www.joomla-specialist.net/forum/support/simple-mobile-detection.html
	*/
	public function onContentPrepare($context, &$article, &$params, $page = 0){
		$app = JFactory::getApplication();
		if ( $app->isAdmin() || !$this->_params->get('en_contentswitch', false)) {
			return;
		}
		
		//find the {IfMobile} and {EndIfMobile} pair and see if there is work to do
		$matches = array();
		preg_match_all('/.*?{IfMobile.*?}(?s).*?(?-s){EndIfMobile}.*?/im', $article->text, $matches, PREG_OFFSET_CAPTURE);
		if(!empty($matches) && !empty($matches[0])){
			//get the mobile parameters
			$isMobile = $app->getUserState('cmobile.ismobile', false);
			$isDevice = $app->getUserState('cmobile.isdevice', false);
			$mobileDevice = new JRegistry($app->getUserState('cmobile.device', '{}'));

			$replacePositionShift = 0;
			foreach($matches[0] as $i => $match){
				$originalText = $match[0];

				$replacePosition = $match[1];
				$replaceLength = strlen($originalText);
				
				$mobileParts = $nonMobileParts = $conditions = array();
				preg_match('/{IfMobile(.*?)}(.*?)(?:{Else.*?Mobile(?:.*?)}|{EndIfMobile})/is', $originalText, $mobileParts, PREG_OFFSET_CAPTURE);
				//TODO: ElseIf statemenets
				preg_match('/{ElseMobile}(.*?){EndIfMobile}/is', $originalText, $nonMobileParts, PREG_OFFSET_CAPTURE);
				
				$statementPos = $mobileParts[0][1];
				$statementLength = (strpos($originalText, '{EndIfMobile}') + strlen('{EndIfMobile}')) - $statementPos;
					
				$newText = '';
				$done = false;
				if($isMobile || $isDevice){
					//its a mobile device, get the mobile part of the content
					$content = $mobileParts[2][0];
					$contentPosition = $mobileParts[2][1];
					$replace = false;
					
					//check if there are conditions, and see if they are met before replacing
					if(isset($mobileParts[1][0]) && trim($mobileParts[1][0]) != ''){
						//mobile condition
						$conditions = explode(',', trim($mobileParts[1][0]));
						
						if(trim($conditions[0]) === 'isdevice' && $isDevice){
							$replace = true;
						}
						//first part is the device
						elseif($isMobile && $mobileDevice->get('name') === trim($conditions[0])){
							//conditions are met, replace the text
							$replace = true;
						}
					}elseif($isMobile){
						//no condition, just mobile enabled
						$replace = true;
					}
					if($replace){
						//replace the text
						$newText = $content;
						$done = true;
					}
					
				}
				if((!$isMobile && !$isDevice) || !$done){
					//not a mobile device, or conditions not met. Get the non mobile part of the content
					if(!empty($nonMobileParts)){
						$content = $nonMobileParts[1][0];
						$contentPosition = $nonMobileParts[1][1];
						
						$newText = $content;
					}
				}
				
				//replace the newText in the original content
				$originalText = substr_replace($originalText, $newText, $statementPos, $statementLength);
				
				$article->text = substr_replace($article->text, $originalText, ($replacePosition - $replacePositionShift), $replaceLength);
				
				//keep track of the changing positions after replacing content
				$replacePositionShift += ($replaceLength - strlen($originalText));
			}

		}
	}
}
