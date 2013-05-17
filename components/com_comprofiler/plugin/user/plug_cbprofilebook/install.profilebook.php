<?php
/**
* Joomla Community Builder User Plugin: plug_cbprofilebook
* @version $Id: install.profilebook.php 2650 2012-10-25 14:06:13Z kyle $
* @package plug_cbprofilebook
* @subpackage install.profilebook.php
* @author JoomlaJoe and Beat
* @copyright (C) JoomlaJoe and Beat, www.joomlapolis.com
* @license Limited  http://www.gnu.org/copyleft/gpl.html GNU/GPL
* @final 1.2
*/

/** ensure this file is being included by a parent file */
if ( ! ( defined( '_VALID_CB' ) || defined( '_JEXEC' ) || defined( '_VALID_MOS' ) ) ) { die( 'Direct Access to this location is not allowed.' ); }

/**
 * Show installation result to user
 *
 * @return string
 */
function plug_cb_profilebook_install(){
  	?>
	<br />Copyright 2006-2011 Beat, MamboJoe/JoomlaJoe and CB team on joomlapolis.com . This component is released under the GNU/GPL License and parts under Community Builder Free License. All copyright statements must be kept. Derivate work must prominently duly acknowledge original work and include visible online links. Official site: <a href="http://www.joomlapolis.com">www.joomlapolis.com</a>
	<br />
	<br />
	<font color="green"><b>Installation finished.</b></font>
	<?php
	return "";
}
?>