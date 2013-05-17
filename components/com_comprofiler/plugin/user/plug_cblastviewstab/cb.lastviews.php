<?php
/**
* LastView Tab Class for handling the CB tab api
* @version $Id: cb.lastviews.php 987 2010-04-07 23:39:29Z beat $
* @package Community Builder
* @subpackage lastviews.php
* @author Trail
* @copyright (C) 2005-2008 Trail, www.djtrail.nl
* @license http://www.gnu.org/copyleft/gpl.html GNU/GPL
* @final 1.2
*/

// Don't allow direct linking
if ( ! ( defined( '_VALID_CB' ) || defined( '_JEXEC' ) || defined( '_VALID_MOS' ) ) ) { die( 'Direct Access to this location is not allowed.' ); }

class getLastViewsTab extends cbTabHandler {
	/**
	* Constructor
	*/
	function getLastViewsTab() {
		$this->cbTabHandler();
	}

	function getDisplayTab( $tab, $user, $ui ) {
		global $_CB_framework, $_CB_database;
			
		$return							=	null;
		
		$params 						=	$this->params;
		$LVexclusionlist				=	$params->get( 'LVexclusionlistTXT', '0' );
		$LVdateformat					=	$params->get( 'LVdateformatOPTION', '0' );
		$LVadmingroup					=	$params->get( 'LVadmingroupTXT', '18' );
		$LVhideadmin					=	$params->get( 'LVhideadminOPTION', '1' );
		$LVhideguests					=	$params->get( 'LVhideguestsOPTION', '1' );
		$listlimit						=	$params->get( 'LVlistlimit', '10' );
		$LVthelast						=	$params->get( 'LVthelastTXT', 'The Last' );
		$LVadmin						=	$params->get( 'LVadminTXT', '(admin)' );
		$LVviews						=	$params->get( 'LVviewsTXT', 'views' );
		$LVavatar						=	$params->get( 'LVavatarTXT', 'Avatar' );
		$LVname							=	$params->get( 'LVnameTXT', 'Name' );
		$LVtime							=	$params->get( 'LVtimeTXT', 'Time' );
		$LVtotal						=	$params->get( 'LVtotalTXT', 'Total' );
		$LVguests						=	$params->get( 'LVguestsTXT', 'Guests have visited this profile.' );
		$LVshowtotal					=	$params->get( 'LVshowtotalOPTION', '0' );
		$LVhidetotal					=	$params->get( 'LVhidetotalOPTION', '0' );
		$LVusername						=	$params->get( 'LVusernameOPTION', '0' );
		$JPKarmaWare					=	$params->get( 'JPKarmaWareOPTION', '0' );
		$path							=	$_CB_framework->getCfg( 'absolute_path' );
		$live_site						=	$_CB_framework->getCfg( 'live_site' );
		$lang							=	$_CB_framework->getCfg( 'lang' );
		$myid							=	$_CB_framework->myId();
		$myCmsid						=	$_CB_framework->myCmsGid();

		if ( trim( $LVexclusionlist == '' ) ) {
			$LVexclusionlist			=	null;
		}
		
		$dateFormat						=	'';
		if ( $LVdateformat == '0' ) {
			$dateFormat					=	'%d/%m %H:%M';
		} elseif ( $LVdateformat == '1' ) {
			$dateFormat					=	'%d-%m %H:%M';
		} elseif ( $LVdateformat == '2' ) {
			$dateFormat					=	'%d/%m/%Y %H:%M';
		} elseif ( $LVdateformat == '3' ) {
			$dateFormat					=	'%d-%m-%Y %H:%M';
		} elseif ( $LVdateformat == '4' ) {
			$dateFormat					=	'%m/%d %H:%M';
		} elseif ( $LVdateformat == '5' ) {
			$dateFormat					=	'%m-%d %H:%M';
		} elseif ( $LVdateformat == '6' ) {
			$dateFormat					=	'%m/%d/%Y %H:%M';
		} elseif ( $LVdateformat == '7' ) {
			$dateFormat					=	'%m-%d-%Y %H:%M';
		} elseif ( $LVdateformat == '98' ) {
			$dateFormat					=	_DATE_FORMAT_LC;
		} elseif ( $LVdateformat == '99' ) {
			$dateFormat					=	_DATE_FORMAT_LC2;
		}

		if ( $LVexclusionlist !== null ) {
			$LVexclusionlist				=	explode( ',', $LVexclusionlist );
			cbArrayToInts( $LVexclusionlist );
			$LVexclusionlistSQLclean		=	implode( ',', $LVexclusionlist );
		}
		$sql							=	"SELECT v.viewer_id, lastview, v.viewscount, c.avatar, c.avatarapproved, c.banned, u.username, u.name "
										.	"\n FROM `#__comprofiler_views` v, `#__comprofiler` c, `#__users` u WHERE "
										.	"\n v.profile_id = " . (int) $user->id
										.	"\n AND c.id = v.viewer_id "
										.	"\n AND u.id = c.id "
										.	( $LVexclusionlist !== null ? "\n AND c.id NOT IN (" . $LVexclusionlistSQLclean . ")" : '' )
										.	"\n ORDER BY v.lastview DESC "
										.	"\n LIMIT " . (int) $listlimit;		
		$_CB_database->setQuery( $sql );
		$lastviews						=	$_CB_database->loadObjectList();
		$viewercount					=	count( $lastviews );

		if( $tab->description != null ) {
			$return						.=	'<div class="tab_Description">' . unHtmlspecialchars( getLangDefinition( $tab->description ) ) . '</div>';
		}

		$return							=	'<table class="cbLastViewsTable" cellpadding="3" cellspacing="0" border="0" width="95%">';
		
		$colspan						=	0;
		$text							=	null;
		$showLVtotal					=	true;
		
		if ( ( ( $myid == $user->id ) || ( $LVshowtotal == '1' ) ) && ( $LVhidetotal != '0' ) ) { 
			$colspan					=	4;
			$text						=	$LVthelast . ' ' . $viewercount . ' ' . $LVviews;
			$showLVtotal				=	true;
		} elseif ( $myid && ( $myCmsid == 2 || ( $myCmsid > $LVadmingroup ) ) && ( $LVhideadmin != '0' ) ) { 
			$colspan					=	4;
			$text						=	$LVthelast . ' ' . $viewercount . ' ' . $LVviews . ' ' . $LVadmin;
			$showLVtotal				=	true;
		} else {
			$colspan					=	3;
			$text						=	$LVthelast . ' ' . $viewercount . ' ' . $LVviews;
			$showLVtotal				=	false;
		}

		$return 						.=	'<tr>'
										.		'<th class="sectiontableheader" colspan="' . (int) $colspan . '">'
										.			htmlspecialchars( $text )
										.		'</th>'
										.	'</tr>'	
										.	'<tr>'
										.		'<th class="sectiontableheader">'
										.			htmlspecialchars( $LVavatar )
										.		'</th>'
										.		'<th class="sectiontableheader">'
										.			htmlspecialchars( $LVname )
										.		'</th>'
										.		'<th class="sectiontableheader">'
										.			htmlspecialchars( $LVtime )
										.		'</th>';

		if ( $showLVtotal ) {
			$return 					.=		'<th class="sectiontableheader">'
										.			htmlspecialchars( $LVtotal )
										.		'</th>';
		}

		$return							.=	'</tr>';
 
   if ( $LVhideguests != '0' ) {
		$sql = "SELECT count(viewer_id) FROM `#__comprofiler_views`"
			.		"\n WHERE profile_id = " . (int) $user->id . " AND viewer_id = 0";
		$_CB_database->setQuery( $sql );
		$guestscount					=	$_CB_database->loadResult();
	  }
		if ( $viewercount > 0 ) {
			$counter					=	0;
			$offset						=	$_CB_framework->getCfg( 'offset' );
			foreach ( $lastviews as $lastview ) {
				//this cool sectiontableentry switch code line was contributed by viames	:)
				$class					=	( is_int( $counter++ / 2 ) ? 'sectiontableentry1' : 'sectiontableentry2' );
				$cbUser					=	CBuser::getInstance( $lastview->viewer_id );
				$name					=	$cbUser->getField( 'name', null, 'html', 'none', 'list' );
				$username				=	$cbUser->getField( 'username', null, 'html', 'none', 'list' );
				$avatar					=	$cbUser->getField( 'avatar', null, 'html', 'none', 'list' );
				
				if ( $LVusername == '0' ) {
					$viewername 		=	$username; 
				} elseif ( $LVusername == '1' ) {
					$viewername 		=	$name; 
				} elseif ( $LVusername == '2' ) {
					$viewername 		=	$username . ' (' . $name . ')'; 
				} elseif ( $LVusername == '3' ) {
					$viewername 		=	$name . ' (' . $username . ')'; 
				}

				if ( $lastview->lastview && preg_match( '/([0-9]{4})-([0-9]{2})-([0-9]{2}) ([0-9]{2}):([0-9]{2}):([0-9]{2})/', $lastview->lastview, $regs ) ) {
					$date				=	mktime( $regs[4], $regs[5], $regs[6], $regs[2], $regs[3], $regs[1] );
					$date				=	$date > -1 ? strftime( $dateFormat, $date + ( $offset * 60 * 60 ) ) : '-';
				}

				$return					.=	'<tr>'
										.		'<td class="' . $class . '" width="1%">'
										.			$avatar
										.		'</td>'
										.		'<td class="' . $class . '">'
										.			$viewername
										.		'</td>'
										.		'<td class="' . $class . '">'
										.			$date
										.		'</td>';
		
				if ( $showLVtotal ) { 
					$return				.=		'<td class="' . $class . '" width="1%">'
										.			$lastview->viewscount
										.		'</td>';
				} 
				
				$return					.=	'</tr>';
			}

			if ( $LVhideguests != '0' ) {
				$return					.=	'<tr>'
										.		'<td colspan="' . $colspan . '" height="1">&nbsp;</td>'
										.	'</tr>'
										.	'<tr>'
										.		'<td colspan="' . $colspan . '" class="sectiontableheader">'
										.					$guestscount . ' ' . htmlspecialchars( $LVguests )
										.		'</td>'
										.	'</tr>';
			}

			if ( $JPKarmaWare != '1' ) {
				$return					.=	'<tr>'
										.		'<td align="center" class="sectiontableentry1" colspan="' . $colspan . '">'
										.			'<font color="darkred"><b>&copy;</b></font>'
										.			'by <a href="http://www.djtrail.nl" target="_blank">djTrail</a> Released as JPKarmaware :) '
										.			'<a href="http://www.joomlapolis.com/component/option,com_joomlaboard/Itemid,38/func,view/id,2308/catid,13/" target="_blank">'
										.				'<u>Click here to pay due karma</u>'
										.			'</a> :)'
										.		'</td>'
										.	'</tr>';
			}
			
			$return						.=	'</table>';
	
			return $return;
		}
	} //end getDisplayTab
}	//end class getLastViewsTab.
?>