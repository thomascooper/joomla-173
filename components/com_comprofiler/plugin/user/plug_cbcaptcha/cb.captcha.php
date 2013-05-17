<?php
/**
* Captcha Tab Class for handling CB Registration, CB lost password,  CB member email and CB Contact form submissions
* @version $Id: cb.captcha.php 2561 2012-08-27 12:46:52Z kyle $
* @package plug_cbcaptcha
* @subpackage cb.captcha.php
* @author Nant and Beat
* @copyright (C) 2007-2009 Nant, JoomlaJoe and Beat, www.joomlapolis.com
* @license Limited  http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU/GPL v2
* @final 2.2.1
*/

if ( ! ( defined( '_VALID_CB' ) || defined( '_JEXEC' ) || defined( '_VALID_MOS' ) ) ) { die( 'Direct Access to this location is not allowed.' ); }

global $_PLUGINS;
if ( isset( $_PLUGINS ) ) {
	$_PLUGINS->registerFunction( 'onBeforeUserRegistration',	'onBeforeUserRegistration',		'getcaptchaTab' );

	$_PLUGINS->registerFunction( 'onLostPassForm', 				'onLostPassForm',				'getcaptchaTab' );
	$_PLUGINS->registerFunction( 'onLostPassForm', 				'onLostPassFormB',				'getcaptchaTab' );
	$_PLUGINS->registerFunction( 'onStartNewPassword',			'onStartNewPassword',			'getcaptchaTab' );

	$_PLUGINS->registerFunction( 'onAfterEmailUserForm', 		'onAfterEmailUserForm',			'getcaptchaTab' );
	$_PLUGINS->registerFunction( 'onBeforeEmailUser',			'onBeforeEmailUser',			'getcaptchaTab' );

	$_PLUGINS->registerFunction( 'onAfterEmailToContactForm', 	'onAfterEmailToContactForm',	'getcaptchaTab' );
	$_PLUGINS->registerFunction( 'onBeforeSendEmailToContact',	'onBeforeSendEmailToContact',	'getcaptchaTab' );

	$_PLUGINS->registerFunction( 'onGetCaptchaHtmlElements', 	'onGetCaptchaHtmlElements',		'getcaptchaTab' );
	$_PLUGINS->registerFunction( 'onCheckCaptchaHtmlElements',	'onCheckCaptchaHtmlElements',	'getcaptchaTab' );

	$_PLUGINS->registerFunction( 'onAfterLoginForm',			'onAfterLoginForm',				'getcaptchaTab' );
	$_PLUGINS->registerFunction( 'onBeforeLogin',				'onBeforeLogin',				'getcaptchaTab' );
}

class getcaptchaTab extends cbTabHandler {
	var $_captchaSession		=	null;
	var $_captchaInput			=	0;
	var $_captchaCodePrev		=	null;
	var $_captchaInputNamePrev	=	null;
	/**
	* Constructor
	*/	
	function getCaptchaTab() {
		$this->cbTabHandler();
	}
	/**
	 * Gets CB session
	 * @param  string     $captchasid
	 * @return CBSession
	 */
	function & _sessionGet( $captchasid = null ) {
		if ( $this->_captchaSession === null ) {
			cbimport( 'cb.session' );
	
			$cbSession =& CBSession::getInstance( 'sessionid' );
			if ( ! $cbSession->session_id() ) {
				$captchasid = strip_tags( $this->_getReqParam( 'captchasid' ) );
				$cbSession->session_id( $captchasid );
				if ( ! $cbSession->session_start() ) {
					die( 'session start issue' );
				}
			}
			$this->_captchaSession =& $cbSession->getReference( 'cb.plug.captcha' );
		}

		return $this->_captchaSession;
	}
	function _sessionSave() {
		$cbSession =& CBSession::getInstance( 'sessionid' );
		if ( ! $cbSession->session_write_close() ) {
			die( 'session write issue' );
		}
	}
	function _sessionId() {
		$cbSession =& CBSession::getInstance( 'sessionid' );
		return $cbSession->session_id();
	}
	/**
	 * Generates a random code based on function params
	 *
	 * @param  int     $numofcharacters  number of characters in code
	 * @param  string  $setofcharacters  characters to use for code
	 * @return string  random code
	 */
	function _generateCode($numofcharacters=6,$setofcharacters='abcdefhijklmnopqrstuvwxyz') {
		$possible = $setofcharacters;
		$code = '';
		$i = 0;
		while ($i < $numofcharacters) { 
			$code .= substr($possible, mt_rand(0, strlen($possible)-1), 1);
			$i++;
		}
		return $code;
	}
	/**
	* Retrieves relevant CB Captcha parameters and puts in session
	*/
	function _putCaptchaParameters() {
		
		static $cbcaptchaparms							=	array();

		if ( count( $cbcaptchaparms ) == 0 ) {
			$params										=	$this->params;

			// Plugin Parameters with default settings
			$cbcaptchaparms['captchaWidth']				=	$params->get('captchaWidth', '95');
			$cbcaptchaparms['captchaHeight']			=	$params->get('captchaHeight', '30');
			$cbcaptchaparms['captchaNumChars']			=	$params->get('captchaNumChars', '5');
			$cbcaptchaparms['captchaCharSet']			=	$params->get('captchaCharSet', 'abcdefhijklmnopqrstuvwxyz');
			$cbcaptchaparms['captchaFont']				=	$params->get('captchaFont', '0');
	        $cbcaptchaparms['captchaBackgroundRGB']		=	$params->get('captchaBackgroundRGB', '255,255,255');

	        if ( substr_count($cbcaptchaparms['captchaBackgroundRGB'],',') != 2 ) {
	        	$cbcaptchaparms['captchaBackgroundRGB']	=	'255,255,255';
			}

	        $cbcaptchaparms['captchaTextRGB']			=	$params->get('captchaTextRGB', '20,40,100');

	        if ( substr_count($cbcaptchaparms['captchaTextRGB'],',') != 2 ) {
	        	$cbcaptchaparms['captchaTextRGB']		=	'20,40,100';
			}

	        $cbcaptchaparms['captchaNoiseRGB']			=	$params->get('captchaNoiseRGB', '100,120,180');

	        if ( substr_count($cbcaptchaparms['captchaNoiseRGB'],',') != 2 ) {
	        	$cbcaptchaparms['captchaNoiseRGB']		=	'100,120,180';
			}

			$cbcaptchaparms['captchaSecurityMode']		=	$params->get('captchaSecurityMode', 0);

			$captchaSession								=&	$this->_sessionGet();

			if ( ( ! $this->_captchaCodePrev ) && isset( $captchaSession['cbcaptchaparams']['captchaCode'] ) ) {
				$this->_captchaCodePrev					=	$captchaSession['cbcaptchaparams']['captchaCode'];
			}

			if ( ( ! $this->_captchaInputNamePrev ) && isset( $captchaSession['cbcaptchaparams']['captchaInputName'] ) ) {
				$this->_captchaInputNamePrev			=	$captchaSession['cbcaptchaparams']['captchaInputName'];
			}
	
			$cbcaptchaparms['captchaCode']				=	$this->_generateCode( $cbcaptchaparms['captchaNumChars'], $cbcaptchaparms['captchaCharSet'] );
			$cbcaptchaparms['captchaInputName']			=	$this->_generateCode( mt_rand( 30, 40 ), 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ' );
	        $captchaSession['cbcaptchaparams']			=	$cbcaptchaparms; 	// this is needed to send data to stand-alone php file

	        $this->_sessionSave();
		}
	}
	
	/**
	* Gets relevant plugin parameters and generates HTML code
	* to link to captcha dynamic image file
	*/
	function _getHTMLcaptcha() {
		global $_CB_framework;

		$this->_putCaptchaParameters();					 

		if ( $this->params->get( 'captchaUrlMode', 1 ) == 1 ) {
			$imageUrl	=	$_CB_framework->getCfg( 'live_site' ) . '/components/com_comprofiler/plugin/user/plug_cbcaptcha/captchaindex.php?captchaurlmode=image&amp;captchasid=' . $this->_sessionId();
		} else {
			$imageUrl	=	str_replace( 'cbcaptcha', 'cb.captcha', $this->_getAbsURLwithParam( array( 'urlmode' => 'image', 'sid' => $this->_sessionId(), 'Itemid' => 0 ), 'pluginclass', true, null, 'raw' ) );
		}
		$width		=	$this->params->get('captchaWidth', '95');
		$height		=	$this->params->get('captchaHeight', '30');

		$CaptchaImage = '<img src="' . $imageUrl . '" alt="'. htmlspecialchars(_UE_CAPTCHA_ALT_IMAGE) . '" width="' . $width . '" height="' . $height . '" />';
		
		return $CaptchaImage;                                           
	}
	
	/**
	* Generates HTML code
	* to link to audio playback dynamic mp3 file 
	*/
	function _getHTMLaudio() {
        global $_CB_framework;

        static $scriptNotOut	=	true;

        $speaker_png = $_CB_framework->getCfg( 'live_site' ) . "/components/com_comprofiler/plugin/user/plug_cbcaptcha/images/sound.png";					 

		if ( $this->params->get( 'captchaUrlMode', 1 ) == 1 ) {
        	$audioURL	=	$_CB_framework->getCfg( 'live_site' ) . '/components/com_comprofiler/plugin/user/plug_cbcaptcha/captchaindex.php?captchaurlmode=audio&amp;captchasid=' . $this->_sessionId();
		} else {
			$audioURL	=	str_replace( 'cbcaptcha', 'cb.captcha', $this->_getAbsURLwithParam( array( 'urlmode' => 'audio', 'sid' => $this->_sessionId(), 'Itemid' => 0 ), 'pluginclass', true, null, 'raw' ) );
		}

		$captchavieweros = $this->_CheckUserOS();
		$captchabrowser = $this->_CheckUserBrowser();

		if ($captchavieweros == 'Win') {
			switch($captchabrowser) {
				case 'IE':
					$soundobjtype = 'application/x-mplayer2';
					$soundembtype = 'audio/mpeg';
					break;
				case 'FIREFOX':
					$soundobjtype = 'application/x-mplayer2';
					$soundembtype = 'audio/mpeg';
					break;					
				case 'OPERA':
					$soundobjtype = 'application/x-mplayer2';
					$soundembtype = 'audio/mpeg';
					break;
				case 'SAFARI':
					$soundobjtype = 'application/x-mplayer2';
					$soundembtype = 'audio/mpeg';
					break;										
				case 'OTHER':
					$soundobjtype = 'application/x-mplayer2';
					$soundembtype = 'audio/mpeg';
					break;					
			}
		} else {
			switch($captchabrowser) {
				case 'IE':
					$soundobjtype = 'audio/mpeg';
					$soundembtype = 'audio/mpeg';
					break;
				case 'FIREFOX':
					$soundobjtype = 'audio/mpeg';
					$soundembtype = 'audio/mpeg';
					break;					
				case 'OPERA':
					$soundobjtype = 'audio/mpeg';
					$soundembtype = 'audio/mpeg';
					break;					
				case 'SAFARI':
					$soundobjtype = 'audio/mpeg';
					$soundembtype = 'audio/mpeg';
					break;										
				case 'OTHER':
					$soundobjtype = 'audio/mpeg';
					$soundembtype = 'audio/mpeg';
					break;					
			}	
		}

		if ( $scriptNotOut ) {
			$_CB_framework->document->addHeadScriptUrl( '/components/com_comprofiler/plugin/user/plug_cbcaptcha/js/captchapopup.js' );
			$scriptNotOut = false;
		}
		
		$htmlpopupcode = '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">\n'
		.'<html xmlns="http://www.w3.org/1999/xhtml">\n'
		.'  <head><meta http-equiv="Content-Type" content="text/html; charset=UTF-8" /><title>[captchatitle]</title></head>\n'
		.'  <body bgcolor="#9999ff">\n'
		.'    <div align="center">\n'
		.'      <b style ="font-size:12px;font-family:Lucida,sans-serif;line-height:1.6">[captchatitle]</b><br />\n'
		.'      <object width="[captchaobjectwidth]" height="[captchaobjectheight]" type="[captchaobjtypetag]" data="[captchafilepath]">\n'
		.'        <param name="src" value="[captchafilepath]" />\n'
		.'        <param name="type" value="[captchaobjtypetag]" />\n'
		.'        <param name="autostart" value="true" />\n'
		.'        <param name="autoplay" value="true" />\n'
		.'        <param name="showcontrols" value="1" />\n'
		.'        <param name="showstatusbar" value="1" />\n'
		.'        <embed src ="[captchafilepath]"\n'
		.'               type="[captchaembtypetag]"\n'
		.'               autoplay="true"\n'
		.'               width="[captchaobjectwidth]"\n'
		.'               height="[captchaobjectheight]"\n'
		.'               bgcolor="#9999ff">\n'
		.'        </embed>\n'
		.'      </object><br />\n'
		.'    </div>\n'
		.'    <b style ="font-size:12px;font-family:Lucida,sans-serif;font-style:italic;line-height:1.6">[captchadescription]</b><br />\n'
		.'    <p style="font-size:12px;font-family:Lucida,sans-serif;text-align:center">\n'
		.'      <a href="[captchafilepath]">' . _UE_CAPTCHA_AUDIO_DOWNLOAD . '</a>\n'
		.'      <br /><span style="font-size:10px">' . _UE_CAPTCHA_AUDIO_CLICK2DOWNLOAD . '</span>\n'
		.'    </p>\n'
		.'    <form action="..." >\n'
		.'      <div align="center">\n'
		.'        <input type="button" value="' . _UE_CAPTCHA_AUDIO_POPUP_CLOSEWINDOW . '" onclick="javascript:window.close();" />\n'
		.'      </div>\n'
		.'    </form>\n'
		.'  </body>\n'
		.'</html>';
		$htmlpopupcode = str_replace("[captchatitle]", _UE_CAPTCHA_AUDIO_POPUP_TITLE, $htmlpopupcode);
		$htmlpopupcode = str_replace("[captchadescription]", _UE_CAPTCHA_AUDIO_POPUP_DESCRIPTION, $htmlpopupcode);
		$htmlpopupcode = str_replace("[captchafilepath]",$audioURL, $htmlpopupcode);
		$htmlpopupcode = str_replace("[captchaobjectwidth]", '280', $htmlpopupcode);
		$htmlpopupcode = str_replace("[captchaobjectheight]", '69', $htmlpopupcode);
		$htmlpopupcode = str_replace("[captchaobjtypetag]", $soundobjtype, $htmlpopupcode);
		$htmlpopupcode = str_replace("[captchaembtypetag]", $soundembtype, $htmlpopupcode);
		$htmlpopupcode = htmlspecialchars($htmlpopupcode);
		$popupwinwidth = '320';
		$charsperwidth = floor($popupwinwidth / 8);	// 7 pixels per character
		$extrawinheight_title = floor(strlen(_UE_CAPTCHA_AUDIO_POPUP_TITLE) / $charsperwidth) * 10 ;
		$extrawinheight_desc = floor(strlen(_UE_CAPTCHA_AUDIO_POPUP_DESCRIPTION) / $charsperwidth) * 10 ;
		$popupwinheight = 220 + $extrawinheight_title + $extrawinheight_desc;
		$popupwinid = 111;
		$CaptchaAudioPopup = '<a href="' . $audioURL . '" target="_blank" onclick="javascript:CAPTCHAHTMLPOPUP(' . "'$htmlpopupcode','$popupwinwidth','$popupwinheight','$popupwinid'" . '); return false">' 
			. '<img style="cursor:pointer;border:0px;" src="' . $speaker_png . '" alt="' . htmlspecialchars(_UE_CAPTCHA_AUDIO) .'" title="' . htmlspecialchars(_UE_CAPTCHA_AUDIO) . '" width="32" height="32" /></a>';

		return $CaptchaAudioPopup;
		
	}

	/**
	* Test captcha
	*/
    function testCaptcha() {
		$params = $this->params;

		$CaptchaImage = $this->_getHTMLcaptcha();

		$return = $CaptchaImage;
		if (!$params->get('captchaSecurityMode',0)) {
			$return .= $this->_getHTMLaudio();	
		}

		return $return;
	}

	
	/**
	* Generates the HTML to display the registration tab/area
	* @param object tab reflecting the tab database entry
	* @param object mosUser reflecting the user being displayed (here null)
	* @param int 1 for front-end, 2 for back-end
	* @return mixed : either string HTML for tab content, or false if ErrorMSG generated
	*/
	function getDisplayRegistration($tab, $user, $ui) {
		
		$params = $this->params;
        if (!$params->get('captchaRegistration',1)) {
        	return null;
		}
		
		$CaptchaImage = $this->_getHTMLcaptcha();
/*
 *		OLD WAY before CB 1.2.3:
		global $_CB_OneTwoRowsStyleToggle;
		$class 						=	'sectiontableentry' . $_CB_OneTwoRowsStyleToggle;
		$_CB_OneTwoRowsStyleToggle	=	( $_CB_OneTwoRowsStyleToggle == 1 ? 2 : 1 );
		$trTag	= "\t<tr class=\"" . $class. "\">\n";

		$return	= $trTag;
		$return .= "	<td class=\"titleCell\">" . htmlspecialchars(_UE_CAPTCHA_Label) . ":</td>\n";
		$return .= "	<td class=\"fieldCell\">" . $CaptchaImage;
		if (!$params->get('captchaSecurityMode',0)) {
			$return .= $this->_getHTMLaudio();
		}
		$return .= "</td></tr>\n";

		// First generate the input
		$captchaInput	=	$this->_generateCaptchaInput();

		$return	.= $trTag;
		$return .= "	<td class=\"titleCell\">" . $this->_generateCaptchaInputLabel( _UE_CAPTCHA_Enter_Label ) . ":</td>\n";
		$return .= "	<td class=\"fieldCell\">";
		$return .= $captchaInput;
		$return .= getFieldIcons($ui, true, false, _UE_CAPTCHA_Desc, _UE_CAPTCHA_Label . ":");
		$return .= "</td></tr>\n";
*/
		// New way after CB 1.2.3:

		if (!$params->get('captchaSecurityMode',0)) {
			$CaptchaImage	.=	$this->_getHTMLaudio();
		}

		// First generate the input
		$captchaInput		=	$this->_generateCaptchaInput();

		// Now return the 2 lines to display on the registartion form:
		return array( cbTabs::_createPseudoField( $tab, _UE_CAPTCHA_Label, $CaptchaImage, '', 'cbcaptchaImage', false, null, false ),
					  cbTabs::_createPseudoField( $tab, _UE_CAPTCHA_Enter_Label, $captchaInput . getFieldIcons($ui, true, false, _UE_CAPTCHA_Desc, _UE_CAPTCHA_Label . ":"), _UE_CAPTCHA_Desc, 'cbcaptchaInput', false, $this->_nameOfCaptchaInput(), true ) );
	}
	
	/**
	* This function is called before user registration and checks if the correct security
	* code was entered during registration application.
	* If not - then the application fails, a popup message is displayed and the applicant must try again.
	*/
	function onBeforeUserRegistration( &$row, &$rowExtras ) {
		global $_PLUGINS;

		$params = $this->params;
        if (!$params->get('captchaRegistration',1)) {
        	return;
		}
		
		$this->_verifyCaptchaRaiseError();
	}
	
	/**    
	* This function is needed only to fix a bug in CB 1.0.2 (hopefully with next version this could be
	* removed). 
	*/
	function onBeforeRegisterForm( $option, $emailpass, &$regErrorMSG, &$fieldsQuery ) {
		global $_PLUGINS;

		$params = $this->params;
        if (!$params->get('captchaRegistration',1)) {
        	return;
		}
		
		$_PLUGINS->_iserror = false;			// ugly bug fix of CB 1.0.2
	}

	/**
	* Displays captcha on lost password form 
	*/
	function onLostPassForm( $ui ) {

		$params = $this->params;
        if (!$params->get('captchaNewPassword',1)) {
        	return null;
		}

		if (!$params->get('captchaSecurityMode',0)) {
			$CaptchaImage = $this->_getHTMLcaptcha() . $this->_getHTMLaudio();
		} else {
			$CaptchaImage = $this->_getHTMLcaptcha();		
		}		
		$return = array( 0 => "", 1 => $CaptchaImage );
		return $return;		
	}
	
	/**
	* Displays captcha check on lost password form 
	*/
	function onLostPassFormB( $ui ) {
		$params = $this->params;
        if (!$params->get('captchaNewPassword',1)) {
        	return null;
		}
		
		$captchaInput	=	$this->_generateCaptchaInput()
						//.	"<img src='" . $_CB_framework->getCfg( 'live_site' ) . "/components/com_comprofiler/images/required.gif' width='16' height='16' alt='*' title='" . htmlspecialchars(_UE_FIELDREQUIRED) . "' /> ";
		                . 	getFieldIcons( 1, 1, null );
		$return = array( 0 => $this->_generateCaptchaInputLabel( _UE_CAPTCHA_Enter_Label ) . ':', 1 => $captchaInput );
		return $return;
	}
	
	/**
	* Checks code entered during forgotten password form validation 
	*/
	function onStartNewPassword( &$checkusername, &$confirmEmail ) {
		global $_PLUGINS;

		$params = $this->params;
        if (!$params->get('captchaNewPassword',1)) {
        	return null;
		}

		$this->_verifyCaptchaRaiseError();

		return true;	
	}

	/**
	* Generates the HTML to display security image on forgotten email form
	*/
	function onAfterEmailUserForm( ) {
		return $this->_generateHTML( 'captchaEmailUser' );
	}
	/**
	* Generates the HTML to display security image on CB email to contact form
	*/
	function onAfterEmailToContactForm( ) {
		return $this->_generateHTML( 'captchaContactForm' );
	}
	/**
	* Generates the HTML to display security image on CB email to contact form
	*/
	function onAfterLoginForm( $name_lenght, $pass_lenght, $horizontal, $class_sfx, &$formparams ) {
		$params = $this->params;
		if (!$params->get('captchaLoginForm',1)) {
        	return null;
		}

		$captchalogintitle = getLangDefinition( trim( $params->get( 'captchaLoginFormSecurityFieldTitle', _UE_CAPTCHA_Enter_Label ) ) );
 		
		$elems = $this->_generateHTML( 'captchaLoginForm', false, $pass_lenght, 0, $class_sfx );
		$html =	'<div class="mod_login_captcha_image">' . $elems[0] . '</div>';
		if ( $params->get('captchaLoginFormIncludeSecurityFieldTitle',1 ) && $captchalogintitle ) {
			$html .= '<div class="mod_login_captcha_title">' . $this->_generateCaptchaInputLabel( $captchalogintitle ) . '</div>';
		}
		$html .= '<div class="mod_login_captcha_field">' . $elems[1] . '</div>';
		return $html;
	}
	/**
	* Generates the HTML to display security image on forgotten email form
	*/
	function onGetCaptchaHtmlElements( $generateFullHtml = true ) {
		return $this->_generateHTML( 'captchaOtherUses', $generateFullHtml );
	}
	/**
	 * Generates HTML depending on parameter
	 *
	 * @param  string  $setting the name of the param setting
	 * @param  boolean $generateFullHtml if TRUE: return string of a div, if FALSE: returns array( images, input field )
	 * @return string|array
	 */
	function _generateHTML( $setting, $generateFullHtml = true, $inputSize = 20, $defaultSetting = 1 ) {
    	global $_CB_framework;
    	
		$params = $this->params;
		$settingValue = $params->get( $setting, $defaultSetting );
        if ( ( $settingValue == 0 ) || ( ( $settingValue == 2 ) && $_CB_framework->myId() ) ) {
        	return null;
		}

		if (!$params->get('captchaSecurityMode',0)) {
			$CaptchaImage = $this->_getHTMLcaptcha() . $this->_getHTMLaudio();
		} else {
			$CaptchaImage = $this->_getHTMLcaptcha();		
		}		

		$CaptchaInputField	=	$this->_generateCaptchaInput( $inputSize );

		if ( $generateFullHtml ) {
			$return = "<div style=\"margin-left:160px;\">" . $CaptchaImage . "</div>";
			$return .= "<div style=\"float:left; position:relative; left:0px; width:160px;\">" . $this->_generateCaptchaInputLabel( _UE_CAPTCHA_Enter_Label ) . ":</div>\n";
			$return .= "<div style=\"float:left; position:relative; left:0px;\">"
					.  $CaptchaInputField
					//.  "<img src='" . $_CB_framework->getCfg( 'live_site' ) . "/components/com_comprofiler/images/required.gif' width='16' height='16' alt='*' title='" . htmlspecialchars(_UE_FIELDREQUIRED) . "' /> "
					.	getFieldIcons( 1, 1, null )
					.  "</div>"
					.  "<div style=\"clear:both;\">&nbsp;</div>";
		} else {
			$return	= array( $CaptchaImage, $CaptchaInputField );
		}
		return $return;
	}

	/**
	* Checks code entered during email user form validation 
	*/
	function onBeforeEmailUser( ) {
		$this->_checkCaptcha( 'captchaEmailUser' );
		return null;
	}
	/**
	* Checks code entered during email to CB contact form validation 
	*/
	function onBeforeSendEmailToContact( ) {
		return $this->_checkCaptcha( 'captchaContactForm' );
	}
	/**
	* Checks code entered during CB login form validation 
	*/
	function onBeforeLogin( ) {
		if ( ( ( cbGetParam( $_GET, 'option' ) == 'com_comprofiler' ) || ( cbGetParam( $_POST, 'option' ) == 'com_comprofiler' ) )
		&& ( ( cbGetParam( $_GET, 'task' ) == 'login' ) || ( cbGetParam( $_POST, 'task' ) == 'login' ) ) ) {
			return $this->_checkCaptcha( 'captchaLoginForm' );
		}
		return null;
	}
	/**
	* Checks code entered during any other form for validation 
	*/
	function onCheckCaptchaHtmlElements( ) {
		return $this->_checkCaptcha( 'captchaOtherUses' );
	}
	/**
	* Checks code entered during email user form validation 
	*/
	function _checkCaptcha( $setting ) {
		global $_CB_framework;

		if ($setting == 'captchaLoginForm') {
			$params_default = 0;	
		} else {
			$params_default = 1;
		}
		
		$params = $this->params;
		$settingValue = $params->get( $setting, $params_default );
        if ( ( $settingValue == 0 ) || ( ( $settingValue == 2 ) && $_CB_framework->myId() ) ) {
        	return null;
		}

		$this->_verifyCaptchaRaiseError();
		return true;	
	}
	/**
	 * Low-level html generation of captcha input box
	 * @access private
	 *
	 * @param  int  $inputSize  Size of the text input element
	 * @return string           HTML <input> fields
	 */
	function _generateCaptchaInput( $inputSize = 20 ) {
		$inputId			=	++$this->_captchaInput;
		$captchaSession		=	$this->_sessionGet();
		$inputName			=	$captchaSession['cbcaptchaparams']['captchaInputName'];
		return	'<input class="required inputbox" type="text" name="' . htmlspecialchars( $inputName ) . '" id="' . htmlspecialchars( $inputName ) . '_' . $inputId . '" mosReq="1" mosLabel="' . htmlspecialchars( _UE_CAPTCHA_Enter_Label ) . '" value="" size="' . (int) $inputSize . '" />'
			.	'<input type="hidden" name="' . $this->_getPagingParamName( 'captchasid' ) . '" value="' . $this->_sessionId() . '" />'
			.	'<input type="text" name="full_address" value="" style="display:none;" />';
	}
	/**
	 * Low-level label generation for captcha input box
	 * @access private
	 *
	 * @param  string  $labelText  String for label to htmlspecialchar
	 * @return string              HTML <label> element
	 */
	function _nameOfCaptchaInput( ) {
		$inputId			=	$this->_captchaInput;
		$captchaSession		=	$this->_sessionGet();
		$inputName			=	$captchaSession['cbcaptchaparams']['captchaInputName'];
		return $inputName . '_' . $inputId;
	}
	/**
	 * Low-level label generation for captcha input box
	 * @access private
	 *
	 * @param  string  $labelText  String for label to htmlspecialchar
	 * @return string              HTML <label> element
	 */
	function _generateCaptchaInputLabel( $labelText ) {
		$inputName			=	$this->_nameOfCaptchaInput();
		return '<label for="' . $inputName . '">' . htmlspecialchars( $labelText ) . '</label>';
	}
	/**
	 * Low-level check of captcha code:
	 * - if ok, clears the value to avoid any possible automatic re-use for a limited time after a human used it
	 * - if not ok, raises an error message.
	 * @access private
	 *
	 */
	function _verifyCaptchaRaiseError() {
		global $_PLUGINS, $_POST;

		$honeypot			=	stripslashes( cbGetParam( $_POST, 'full_address', '', _CB_NOTRIM ) );
		$captchaSession		=&	$this->_sessionGet();

		// use previous inputname and code only if they exist:
		if ( $this->_captchaInputNamePrev && $this->_captchaCodePrev  ) {
			$inputName		=	$this->_captchaInputNamePrev;
			$captchaCode	=	$this->_captchaCodePrev;
			$previous		=	true;
		} else {
			$inputName		=	$captchaSession['cbcaptchaparams']['captchaInputName'];
			$captchaCode	=	$captchaSession['cbcaptchaparams']['captchaCode'];
			$previous		=	false;
		}

		$typedValue			=	cbGetParam( $_POST, $inputName );

		if ( ( $captchaCode == $typedValue ) && ( ! empty( $captchaCode ) ) && ( $honeypot === '' ) ) {
			// don't delete the session data if we used previous inputname and code so new captcha rendered functions:
			if ( ! $previous ) {
				unset( $captchaSession['cbcaptchaparams'] );
			}

			$this->_sessionSave();
		} else {
			$_PLUGINS->raiseError( 0 );
			$_PLUGINS->_setErrorMSG( _UE_CAPTCHA_NOT_VALID );
		}
	}
	/**
	* Joins array of mp3 audio files to dynamically
	* create a combined audio playback sequence of the captcha code
	*/
	function joinmp3s($mp3files) {
		$out = '';
		$numfiles = count($mp3files);
		$i = 0;
		foreach ($mp3files as $mp3file) {
			$i++;
			if ($i == $numfiles) {
				$offsetlength = 0;
			} else {
				$offsetlength = 128;
			}
			$fh = fopen($mp3file, 'rb');
			$size = filesize($mp3file);
			$out .= fread($fh, $size-$offsetlength);
			fclose($fh);
		}
		return $out;
	}

	/**
	* Returns viewers browser type
	*/
	function _CheckUserBrowser() {
		$useragent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : "unknown";
		if (strstr($useragent,'IE')) {
			$browser = 'IE';
		} else {
			if (strstr($useragent,'Firefox')) {
				$browser = 'FIREFOX';
			} else {
				if (strstr($useragent,'Opera')) {
					$browser = 'OPERA';
				} else {
					if (strstr($useragent,'Safari')) {
						$browser = 'SAFARI';
					} else {
						$browser = 'OTHER';
					}
				}
			}
		}	
		return $browser;
	}

	/**
	* Returns viewers operating system
	*/
	function _CheckUserOS() {	
		$useragent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : "unknown";
		if (strstr($useragent,'Win')) {
			$os = 'Win';
		} else {
			if (strstr($useragent,'Mac')) {
				$os = 'Mac';
			} else {
				if (strstr($useragent,'Linux')) {
					$os = 'Linux';
				} else {
					if (strstr($useragent,'Unix')) {
						$os = 'Unix';
					} else {
						$os = 'Other';
					}
				}
			}
		}
		return $os;
	}
	
	/**
	* Parse calls to get GD Version and Freetype Support status of server installation 
	*/
	function showReqAndTest($name,$value,$control_name) {
		if ($this->getModuleSetting('gd','GD Version')) {
			$gdstatus = "<font color=green>" . $this->getModuleSetting('gd','GD Version') . "</font>"; 	
		} else {
			$gdstatus = "<font color=red>" . "GD NOT INSTALLED!!!" . "</font>";	
		}
		if ($this->getModuleSetting('gd','FreeType Support')) {
			$freetypestatus = "<font color=green>" . $this->getModuleSetting('gd','FreeType Support') . "</font>"; 	
		} else {
			$freetypestatus = "<font color=red>" . "FREETYPE NOT INSTALLED!!!" . "</font>";	
		}
		
        $htmlcaptcha = "GD: <b>" . $gdstatus . "</b><br />";
        $htmlcaptcha .= "Freetype: <b>" . $freetypestatus . "</b><br /><br />";
        $htmlcaptcha .= "<b>Sample Rendering (based on settings)</b><br /><br />";
		return $htmlcaptcha . $this->testCaptcha();
	}

	/**
	* Parse phpinfo 
	*/
	function parsePHPModules() {
		ob_start();
		phpinfo(INFO_MODULES);
		$s = ob_get_contents();
		ob_end_clean();
		$s = strip_tags($s,'<h2><th><td>');
		$s = preg_replace('/<th[^>]*>([^<]+)<\/th>/',"<info>\\1</info>",$s);
		$s = preg_replace('/<td[^>]*>([^<]+)<\/td>/',"<info>\\1</info>",$s);
		$vTmp = preg_split('/(<h2>[^<]+<\/h2>)/',$s,-1,PREG_SPLIT_DELIM_CAPTURE);
		$vModules = array();
		for ($i=1;$i<count($vTmp);$i++) {
			$vMat	=	null;
			if (preg_match('/<h2>([^<]+)<\/h2>/',$vTmp[$i],$vMat)) {
				$vName = trim($vMat[1]);
				$vTmp2 = explode("\n",$vTmp[$i+1]);
   				foreach ($vTmp2 AS $vOne) {
    				$vPat = '<info>([^<]+)<\/info>';
    				$vPat3 = "/$vPat\\s*$vPat\\s*$vPat/";
    				$vPat2 = "/$vPat\\s*$vPat/";
    				if (preg_match($vPat3,$vOne,$vMat)) { // 3cols
     					$vModules[$vName][trim($vMat[1])] = array(trim($vMat[2]),trim($vMat[3])); 
    				} elseif (preg_match($vPat2,$vOne,$vMat)) { // 2cols
     					$vModules[$vName][trim($vMat[1])] = trim($vMat[2]); 
    				} 
				} 
			} 
		} 
		return $vModules;
	}

	/**
	* Get phpinfo module settings 
	*/
	function getModuleSetting($pModuleName,$pSetting) {
		$vModules = $this->parsePHPModules();
		return isset( $vModules[$pModuleName][$pSetting] ) ? $vModules[$pModuleName][$pSetting] : null;
	}
}
/**
 * Class to handle the joomla index.php image and sound:
 */
class CBplug_cbcaptcha extends getcaptchaTab {
	/**
	* WARNING: UNCHECKED ACCESS! On purpose unchecked access for M2M operations
	* Generates the HTML to display for a specific component-like page for the tab. WARNING: unchecked access !
	* @param  null                $tab
	* @param  moscomprofilerUser  $user      the user being displayed
	* @param  int                 $ui        1 for front-end, 2 for back-end
	* @param  array               $postdata  _POST data for saving edited tab content as generated with getEditTab
	* @return mixed                          either string HTML for tab content, or false if ErrorMSG generated
	*/
	function getCBpluginComponent( $tab, &$user, $ui, &$postdata ) {
		if ( $this->params->get( 'captchaUrlMode', 1 ) == 0 ) {
			global $_CB_framework;
			require_once $_CB_framework->getCfg( 'absolute_path' ) . "/components/com_comprofiler/plugin/user/plug_cbcaptcha/captchaindex.php";					 
		}
	}
} // end class getCaptchaTab.
?>
