<?php
if ( ! ( defined( '_VALID_CB' ) || defined( '_JEXEC' ) || defined( '_VALID_MOS' ) ) ) { die( 'Direct Access to this location is not allowed.' ); }

global $_CB_framework, $_PLUGINS;

require_once( $_CB_framework->getCfg( 'absolute_path' ) . '/components/com_comprofiler/plugin/user/plug_cbgroupjive/cbgroupjive.class.php' );

$_PLUGINS->registerFunction( 'gj_onBeforeGroupTab', 'getEvents', 'cbgjEventsPlugin' );
$_PLUGINS->registerFunction( 'gj_onAfterDeleteGroup', 'deleteEvents', 'cbgjEventsPlugin' );
$_PLUGINS->registerFunction( 'gj_onGroupEdit', 'getParam', 'cbgjEventsPlugin' );
$_PLUGINS->registerFunction( 'gj_onConfigIntegrations', 'getConfig', 'cbgjEventsPlugin' );
$_PLUGINS->registerFunction( 'gj_onPluginFE', 'getPluginFE', 'cbgjEventsPlugin' );
$_PLUGINS->registerFunction( 'gj_onAfterCreateGroup', 'setParam', 'cbgjEventsPlugin' );
$_PLUGINS->registerFunction( 'gj_onAfterUpdateGroup', 'setParam', 'cbgjEventsPlugin' );
$_PLUGINS->registerFunction( 'gj_onAfterDeleteUser', 'leaveGroup', 'cbgjEventsPlugin' );
$_PLUGINS->registerFunction( 'onAfterDeleteUser', 'deleteUser', 'cbgjEventsPlugin' );
$_PLUGINS->registerFunction( 'gj_onAuthorization', 'getAuthorization', 'cbgjEventsPlugin' );
$_PLUGINS->registerFunction( 'gj_onGroupNotifications', 'getNotifications', 'cbgjEventsPlugin' );
$_PLUGINS->registerFunction( 'gj_onBeforeUpdateNotificationParams', 'setNotifications', 'cbgjEventsPlugin' );
$_PLUGINS->registerFunction( 'gj_onMenusIntegrationsGeneral', 'getMenu', 'cbgjEventsPlugin' );
$_PLUGINS->registerFunction( 'gj_onMenusIntegrationsSave', 'saveMenu', 'cbgjEventsPlugin' );

class cbgjEventsPlugin extends cbPluginHandler {

	public function getPluginFE( $params, $user, $plugin ) {
		if ( $params[1] && $params[2] ) {
			switch ( $params[0] ) {
				case 'events_publish':
					$this->stateEvent( $params[1], $params[2], $params[3], 1, $user, $plugin );
					break;
				case 'events_unpublish':
					$this->stateEvent( $params[1], $params[2], $params[3], 0, $user, $plugin );
					break;
				case 'events_yes':
					$this->attendEvent( $params[1], $params[2], $params[3], 1, $user, $plugin );
					break;
				case 'events_maybe':
					$this->attendEvent( $params[1], $params[2], $params[3], 2, $user, $plugin );
					break;
				case 'events_no':
					$this->attendEvent( $params[1], $params[2], $params[3], 0, $user, $plugin );
					break;
				case 'events_attending':
					$this->showAttending( $params[1], $params[2], $params[3], $user, $plugin );
					break;
				case 'events_edit':
					$this->editEvent( $params[1], $params[2], $params[3], $user, $plugin );
					break;
				case 'events_save':
					cbSpoofCheck( 'plugin' );
					$this->saveEvent( $params[1], $params[2], $params[3], $user, $plugin );
					break;
				case 'events_message':
					$this->showEventMessage( $params[1], $params[2], $params[3], $user, $plugin );
					break;
				case 'events_send':
					cbSpoofCheck( 'plugin' );
					$this->sendEventMessage( $params[1], $params[2], $params[3], $user, $plugin );
					break;
				case 'events_delete':
					$this->deleteEvent( $params[1], $params[2], $params[3], $user, $plugin );
					break;
			}
		} else {
			switch ( $params[0] ) {
				case 'events_approval':
					$this->getEventApproval( $user, $plugin );
					break;
			}
		}
	}

	private function getEventApproval( $user, $plugin ) {
		cbgjClass::getTemplate( 'cbgroupjiveevents_approval' );

		$paging				=	new cbgjPaging( 'events_approval' );

		$limit				=	$paging->getlimit( (int) $plugin->params->get( 'events_approval_limit', 15 ) );
		$limitstart			=	$paging->getLimistart();
		$search				=	$paging->getFilter( 'search' );
		$where				=	array();

		if ( isset( $search ) && ( $search != '' ) ) {
			$where[]		=	array( 'location', 'CONTAINS', $search, array( 'title', 'CONTAINS', $search ), array( 'event', 'CONTAINS', $search ) );
		}

		$searching			=	( count( $where ) ? true : false );

		$where[]			=	array( 'published', '=', -1, 'c.params', 'CONTAINS', 'events_approve=1' );

		$total				=	count( cbgjEventsData::getEvents( array( array( 'mod_lvl4', 'grp_approved' ), $user, null, true ), $where ) );

		if ( $total <= $limitstart ) {
			$limitstart		=	0;
		}

		$pageNav			=	$paging->getPageNav( $total, $limitstart, $limit );

		$rows				=	cbgjEventsData::getEvents( array( array( 'mod_lvl4', 'grp_approved' ), $user, null, true ), $where, null, ( $plugin->params->get( 'events_approval_paging', 1 ) ? array( $pageNav->limitstart, $pageNav->limit ) : null ) );

		$pageNav->search	=	$paging->getInputSearch( 'gjForm', 'search', CBTxt::T( 'Search Events...' ), $search );
		$pageNav->searching	=	$searching;
		$pageNav->limitbox	=	$paging->getLimitbox( $pageNav );
		$pageNav->pagelinks	=	$paging->getPagesLinks( $pageNav );

		if ( class_exists( 'HTML_cbgroupjiveeventsapproval' ) ) {
			HTML_cbgroupjiveeventsapproval::showEventsApproval( $rows, $pageNav, $user, $plugin );
		} else {
			$this->showEventsApproval( $rows, $pageNav, $user, $plugin );
		}
	}

	private function showEventsApproval( $rows, $pageNav, $user, $plugin ) {
		global $_CB_framework;

		$generalTitle					=	$plugin->params->get( 'general_title', $plugin->name );

		$_CB_framework->setPageTitle( CBTxt::T( 'Event Approval' ) );

		if ( $generalTitle != '' ) {
			$_CB_framework->appendPathWay( htmlspecialchars( CBTxt::T( $generalTitle ) ), cbgjClass::getPluginURL() );
		}

		$_CB_framework->appendPathWay( CBTxt::T( 'Event Approval' ), cbgjClass::getPluginURL( array( 'plugin', 'events_approval' ) ) );

		$eventsApprovalSearch			=	$plugin->params->get( 'events_approval_search', 1 );
		$eventsApprovalPaging			=	$plugin->params->get( 'events_approval_paging', 1 );
		$eventsApprovalLimitbox		    =	$plugin->params->get( 'events_approval_limitbox', 1 );
		$eventPlotting					=	$plugin->params->get( 'events_plotting', 1 );

		if ( $eventPlotting ) {
			$isHttps					=	( isset( $_SERVER['HTTPS'] ) && ( ! empty( $_SERVER['HTTPS'] ) ) && ( $_SERVER['HTTPS'] != 'off' ) );

			$_CB_framework->document->addHeadScriptUrl( ( $isHttps ? 'https' : 'http' ) . '://maps.google.com/maps/api/js?sensor=false' );

			$mapDisplayJs				=	"$( '.gjEventLocation a' ).click( function() {"
										.		"var location = $( this ).attr( 'rel' ).split( '|*|' );"
										.		"var title = $( this ).attr( 'title' ).split( '|*|' );"
										.		"$( '#gjEventsMap' ).dialog({"
										.			"title: title[1],"
										.			"height: 500,"
										.			"width: 500,"
										.			"resizable: false,"
										.			"open: function( event, ui ) {"
										.				"var address = new google.maps.LatLng( location[0], location[1] );"
										.				"var options = { zoom: 16, center: address, mapTypeId: google.maps.MapTypeId.ROADMAP };"
										.				"var map = new google.maps.Map( document.getElementById( 'gjEventsMap' ), options );"
										.				"var marker = new google.maps.Marker( { position: address, map: map, title: title[0] } );"
										.			"}"
										.		"});"
										.	"});";

			$_CB_framework->outputCbJQuery( $mapDisplayJs, 'ui-all' );
		}

		$return							=	'<div class="gjEventsApproval">'
										.		'<form action="' . cbgjClass::getPluginURL( array( 'plugin', 'events_approval' ) ) . '" method="post" name="gjForm" id="gjForm" class="gjForm form-horizontal">'
										.			( $eventsApprovalSearch && ( $pageNav->searching || $pageNav->total ) ? '<div class="gjTop gjTopRight">' . $pageNav->search . '</div>' : null );

		if ( $rows ) foreach ( $rows as $row ) {
			$category					=	$row->getCategory();
			$group						=	$row->getGroup();
			$authorized					=	cbgjClass::getAuthorization( $category, $group, $user, $row->getOwner() );
			$attendingUrl				=	cbgjClass::getPluginURL( array( 'plugin', 'events_attending', (int) $category->get( 'id' ), (int) $group->get( 'id' ), (int) $row->get( 'id' ) ), null, true, false, null, true );
			$attendees					=	$row->getAttendees();
			$userStatus				    =	null;

			if ( $attendees ) foreach ( $attendees as $attendance => $attendances ) {
				if ( $attendances ) foreach ( $attendances as $attendee ) {
					if ( $attendee == $user->id ) {
						$userStatus	    =	$attendance;
					}
				}
			}

			$canMessage					=	( ( ! $row->isPast() ) && ( ( $plugin->params->get( 'events_message', 1 ) && cbgjClass::hasAccess( 'usr_me', $authorized ) ) || cbgjClass::hasAccess( 'mod_lvl2', $authorized ) ) && ( $row->published || cbgjClass::hasAccess( 'mod_lvl4', $authorized ) ) );
			$canAttend					=	( ( ! $row->isPast() ) && ( cbgjClass::hasAccess( 'mod_lvl5', $authorized ) && ( ! cbgjClass::hasAccess( 'usr_me', $authorized ) ) && ( $userStatus != 'yes' ) ) && ( $row->published || cbgjClass::hasAccess( 'mod_lvl4', $authorized ) ) );
			$canMaybeAttend				=	( ( ! $row->isPast() ) && ( cbgjClass::hasAccess( 'mod_lvl5', $authorized ) && ( ! cbgjClass::hasAccess( 'usr_me', $authorized ) ) && ( $userStatus != 'maybe' ) ) && ( $row->published || cbgjClass::hasAccess( 'mod_lvl4', $authorized ) ) );
			$canNotAttend				=	( ( ! $row->isPast() ) && ( cbgjClass::hasAccess( 'mod_lvl5', $authorized ) && ( ! cbgjClass::hasAccess( 'usr_me', $authorized ) ) && ( $userStatus != 'no' ) ) && ( $row->published || cbgjClass::hasAccess( 'mod_lvl4', $authorized ) ) );

			if ( $row->get( 'location' ) ) {
				$location				=	htmlspecialchars( $row->get( 'location' ) );

				if ( $eventPlotting && $row->get( 'address' ) && $row->get( 'latitude' ) && $row->get( 'longitude' ) ) {
					$location			=	'<a href="javascript: void(0);" rel="' . htmlspecialchars( $row->get( 'latitude' ) ) . '|*|' . htmlspecialchars( $row->get( 'longitude' ) ) . '" title="' . htmlspecialchars( $row->get( 'location' ) ) . '|*|' . htmlspecialchars( $row->get( 'address' ) ) . '">' . $location . '</a>';
				}

				$location				=	'<div class="gjEventLocation"><i class="icon-map-marker"></i> ' . $location . '</div>';
			} else {
				$location				=	null;
			}

			$return						.=			'<div class="gjContent row-fluid">'
										.				'<div class="gjContentLogo span2">'
										.					'<div class="gjContentLogoRow">' . $row->getOwnerName( true ) . '</div>'
										.					'<div class="gjContentLogoRow">' . $row->getOwnerAvatar( true ) . '</div>'
										.					'<div class="gjContentLogoRow">' . $row->getOwnerOnline() . '</div>'
										.				'</div>'
										.				'<div class="gjContentBody mini-layout span10">'
										.					'<div class="gjContentBodyHeader row-fluid">'
										.						'<div class="gjContentBodyTitle span9"><h5>' . $row->getTitle() . '<small> ' . $row->getDate() . ' - ' . $category->getName( 0, true ) . ' - ' . $group->getName( 0, true ) . '</small></h5></div>'
										.						'<div class="gjContentBodyMenu span3">';

			if ( cbgjClass::hasAccess( 'mod_lvl4', $authorized ) ) {
				$return					.=							'<input type="button" value="' . htmlspecialchars( CBTxt::T( 'Approve' ) ) . '" class="gjButton btn btn-mini btn-success" onclick="' . cbgjClass::getPluginURL( array( 'plugin', 'events_publish', (int) $category->get( 'id' ), (int) $group->get( 'id' ), (int) $row->get( 'id' ) ), true, true, false, null, true ) . '" />';
			}

			if ( cbgjClass::hasAccess( array( 'mod_lvl4', 'mod_lvl3', 'usr_me' ), $authorized ) || $canMessage || $canAttend || $canMaybeAttend || $canNotAttend ) {
				$menuItems				=	( cbgjClass::hasAccess( array( 'mod_lvl3', 'usr_me' ), $authorized ) ? '<div><a href="' . cbgjClass::getPluginURL( array( 'plugin', 'events_edit', $category->id, $group->id, $row->id ), null, true, false, null, true ) . '"><i class="icon-pencil"></i> ' . CBTxt::Th( 'Edit' ) . '</a></div>' : null )
										.	( $canMessage ? '<div><a href="' . cbgjClass::getPluginURL( array( 'plugin', 'events_message', (int) $category->get( 'id' ), (int) $group->get( 'id' ), (int) $row->get( 'id' ) ), null, true, false, null, true ) . '"><i class="icon-envelope"></i> ' . CBTxt::Th( 'Message' ) . '</a></div>' : null )
										.	( $canAttend ? '<div><a href="javascript: void(0);" onclick="' . cbgjClass::getPluginURL( array( 'plugin', 'events_yes', (int) $category->get( 'id' ), (int) $group->get( 'id' ), (int) $row->get( 'id' ) ), CBTxt::T( 'Are you sure you want to attend this event?' ), true, false, null, true ) . '"><i class="icon-thumbs-up"></i> ' . CBTxt::Th( 'Attend' ) . '</a></div>' : null )
										.	( $canMaybeAttend ? '<div><a href="javascript: void(0);" onclick="' . cbgjClass::getPluginURL( array( 'plugin', 'events_maybe', (int) $category->get( 'id' ), (int) $group->get( 'id' ), (int) $row->get( 'id' ) ), CBTxt::T( 'Are you sure you maybe want to attend this event?' ), true, false, null, true ) . '"><i class="icon-hand-right"></i> ' . CBTxt::Th( 'Maybe Attend' ) . '</a></div>' : null )
										.	( $canNotAttend ? '<div><a href="javascript: void(0);" onclick="' . cbgjClass::getPluginURL( array( 'plugin', 'events_no', (int) $category->get( 'id' ), (int) $group->get( 'id' ), (int) $row->get( 'id' ) ), CBTxt::T( 'Are you sure you do not want to attend this event?' ), true, false, null, true ) . '"><i class="icon-thumbs-down"></i> ' . CBTxt::Th( 'Not Attending' ) . '</a></div>' : null )
										.	( cbgjClass::hasAccess( array( 'mod_lvl4', 'usr_me' ), $authorized ) ? '<div><a href="javascript: void(0);" onclick="' . cbgjClass::getPluginURL( array( 'plugin', 'events_delete', (int) $category->get( 'id' ), (int) $group->get( 'id' ), (int) $row->get( 'id' ) ), CBTxt::T( 'Are you sure you want to delete this event?' ), true, false, null, true ) . '"><i class="icon-remove"></i> ' . CBTxt::Th( 'Delete' ) . '</a></div>' : null );

				$return					.=							cbgjClass::getDropdown( $menuItems, CBTxt::Th( 'Menu' ) );
			}

			$return						.=						'</div>'
										.					'</div>'
										.					'<div class="gjContentBodyInfo">' . ( $row->getEvent() || $location ? '<div class="well well-small">' . ( $row->getEvent() ? '<div class="gjContentBodyInfoRow">' . $row->getEvent() . '</div>' : null ) . $location . '</div>' : null ) . '</div>'
										.					'<div class="gjContentDivider"></div>'
										.					'<div class="gjContentBodyFooter">'
										.						( count( $attendees['yes'] ) ? '<a href="' . $attendingUrl . '">' . CBTxt::Ph( '[event_attend_yes] Attending', array( '[event_attend_yes]' => count( $attendees['yes'] ) ) ) . '</a>' . ( count( $attendees['maybe'] ) || count( $attendees['no'] ) ? ' | ' : null ) : null )
										.						( count( $attendees['maybe'] ) ? '<a href="' . $attendingUrl . '">' . CBTxt::Ph( '[event_attend_maybe] Maybe Attending', array( '[event_attend_maybe]' => count( $attendees['maybe'] ) ) ) . '</a>' . ( count( $attendees['no'] ) ? ' | ' : null ) : null )
										.						( count( $attendees['no'] ) ? '<a href="' . $attendingUrl . '">' . CBTxt::Ph( '[event_attend_no] Not Attending', array( '[event_attend_no]' => count( $attendees['no'] ) ) ) . '</a>' : null )
										.					'</div>'
										.				'</div>'
										.			'</div>';
		} else {
			$return						.=			'<div class="gjContent">';

			if ( $eventsApprovalSearch && $pageNav->searching ) {
				$return					.=				CBTxt::Th( 'No event search results found.' );
			} else {
				$return					.=				CBTxt::Th( 'There are no events pending approval.' );
			}

			$return						.=			'</div>';
		}

		if ( $eventsApprovalPaging ) {
			$return						.=			'<div class="gjPaging pagination pagination-centered">'
										.				( $pageNav->total > $pageNav->limit ? $pageNav->pagelinks : null )
										.				( ! $eventsApprovalLimitbox ? $pageNav->getLimitBox( false ) : ( $pageNav->total ? '<div class="gjPagingLimitbox">' . $pageNav->limitbox . '</div>' : null ) )
										.			'</div>';
		}

		$return							.=			cbGetSpoofInputTag( 'plugin' )
										.		'</form>'
										.		( $eventPlotting ? '<div id="gjEventsMap"></div>' : null )
										.	'</div>';

		echo $return;
	}

	public function getEvents( $tabs, $group, $category, $user, $plugin ) {
		$authorized			=	cbgjClass::getAuthorization( $category, $group, $user );

		if ( ! cbgjClass::hasAccess( 'events_show', $authorized ) ) {
			return;
		}

		cbgjClass::getTemplate( 'cbgroupjiveevents' );

		if ( $plugin->params->get( 'general_validate', 1 ) ) {
			cbgjClass::getFormValidation( '#gjForm_event', "groups: { events_time: 'events_date_hr events_date_min events_date_ampm' }" );
		}

		$paging				=	new cbgjPaging( 'events' );

		$limit				=	$paging->getlimit( (int) $plugin->params->get( 'events_limit', 15 ) );
		$limitstart			=	$paging->getLimistart();
		$search				=	$paging->getFilter( 'search' );
		$where				=	array();

		if ( isset( $search ) && ( $search != '' ) ) {
			$where[]		=	array( 'location', 'CONTAINS', $search, array( 'title', 'CONTAINS', $search ), array( 'event', 'CONTAINS', $search ) );
		}

		$searching			=	( count( $where ) ? true : false );

		if ( ! cbgjClass::hasAccess( 'mod_lvl4', $authorized ) ) {
			$where[]		=	array( 'published', '=', 1, array( 'user_id', '=', (int) $user->id ) );
		}

		$where[]			=	array( 'group', '=', (int) $group->get( 'id' ) );

		$total				=	count( cbgjEventsData::getEvents( null, $where ) );

		if ( $total <= $limitstart ) {
			$limitstart		=	0;
		}

		$pageNav			=	$paging->getPageNav( $total, $limitstart, $limit );

		$rows				=	cbgjEventsData::getEvents( null, $where, null, ( $plugin->params->get( 'events_paging', 1 ) ? array( $pageNav->limitstart, $pageNav->limit ) : null ) );

		$pageNav->search	=	$paging->getInputSearch( 'gjForm_events', 'search', CBTxt::T( 'Search Events...' ), $search );
		$pageNav->searching	=	$searching;
		$pageNav->limitbox	=	$paging->getLimitbox( $pageNav );
		$pageNav->pagelinks	=	$paging->getPagesLinks( $pageNav );

		if ( class_exists( 'HTML_cbgroupjiveevents' ) ) {
			return HTML_cbgroupjiveevents::showEvents( $rows, $pageNav, $tabs, $group, $category, $user, $plugin );
		} else {
			return $this->showEvents( $rows, $pageNav, $tabs, $group, $category, $user, $plugin );
		}
	}

	private function showEvents( $rows, $pageNav, $tabs, $group, $category, $user, $plugin ) {
		global $_CB_framework;

		$eventsSearch					=	( $plugin->params->get( 'events_search', 1 ) && ( $pageNav->searching || $pageNav->total ) );
		$eventsPaging					=	$plugin->params->get( 'events_paging', 1 );
		$eventsLimitbox					=	$plugin->params->get( 'events_limitbox', 1 );
		$eventsEditor					=	$plugin->params->get( 'events_editor', 1 );
		$eventPlotting					=	$plugin->params->get( 'events_plotting', 1 );
		$authorized						=	cbgjClass::getAuthorization( $category, $group, $user );
		$eventsToggle					=	( ( $plugin->params->get( 'group_toggle', 3 ) > 1 ) && cbgjClass::hasAccess( 'events_schedule', $authorized ) );

		$params							=	$group->getParams();
		$eventsApprove					=	$params->get( 'events_approve', $plugin->params->get( 'events_approve', 0 ) );

		if ( $eventPlotting ) {
			$isHttps					=	( isset( $_SERVER['HTTPS'] ) && ( ! empty( $_SERVER['HTTPS'] ) ) && ( $_SERVER['HTTPS'] != 'off' ) );

			$_CB_framework->document->addHeadScriptUrl( ( $isHttps ? 'https' : 'http' ) . '://maps.google.com/maps/api/js?sensor=false' );

			$mapDisplayJs				=	"$( '.gjEventLocation a' ).click( function() {"
										.		"var location = $( this ).attr( 'rel' ).split( '|*|' );"
										.		"var title = $( this ).attr( 'title' ).split( '|*|' );"
										.		"$( '#gjEventsMap' ).dialog({"
										.			"title: title[1],"
										.			"height: 500,"
										.			"width: 500,"
										.			"resizable: false,"
										.			"open: function( event, ui ) {"
										.				"var address = new google.maps.LatLng( location[0], location[1] );"
										.				"var options = { zoom: 16, center: address, mapTypeId: google.maps.MapTypeId.ROADMAP };"
										.				"var map = new google.maps.Map( document.getElementById( 'gjEventsMap' ), options );"
										.				"var marker = new google.maps.Marker( { position: address, map: map, title: title[0] } );"
										.			"}"
										.		"});"
										.	"});";

			$_CB_framework->outputCbJQuery( $mapDisplayJs, 'ui-all' );
		}

		$return							=	$tabs->startTab( null, htmlspecialchars( CBTxt::T( 'Events' ) ), 'gjIntegrationsEvents' )
										.	'<div class="gjEvents">';

		if ( cbgjClass::hasAccess( 'events_schedule', $authorized ) ) {
			if ( $plugin->params->get( 'events_captcha', 0 ) && ( ! cbgjClass::hasAccess( 'usr_mod', $authorized ) ) ) {
				$captcha				=	cbgjCaptcha::render();
			} else {
				$captcha				=	false;
			}

			$input						=	array();

			$input['publish']			=	moscomprofilerHTML::yesnoSelectList( 'published', ( ! cbgjClass::hasAccess( 'mod_lvl4', $authorized ) ? 'disabled="disabled"' : null ) . ' class="input-small"', (int) cbgjClass::getCleanParam( cbgjClass::hasAccess( 'mod_lvl4', $authorized ), 'events_published', ( $params->get( 'events_approve', $plugin->params->get( 'events_approve', 0 ) ) ? 0 : 1 ) ) );
			$input['title']				=	'<input type="text" size="35" class="input-large required" value="' . htmlspecialchars( cbgjClass::getCleanParam( true, 'events_title' ) ) . '" name="events_title" id="events_title" />';

			if ( $eventsEditor >= 2 ) {
				$event					=	cbgjClass::getHTMLCleanParam( true, 'events_event' );
			} else {
				$event					=	cbgjClass::getCleanParam( true, 'events_event' );
			}

			if ( $eventsEditor == 3 ) {
				$input['event']			=	$_CB_framework->displayCmsEditor( 'events_event', $event, 400, 200, 40, 6 );
			} else {
				$input['event']			=	'<textarea id="events_event" name="events_event" class="input-xlarge required" cols="40" rows="6">' . htmlspecialchars( $event ) . '</textarea>';
			}

			$input['location']			=	'<input type="text" size="35" class="input-large required" value="' . htmlspecialchars( cbgjClass::getCleanParam( true, 'events_location' ) ) . '" name="events_location" id="events_location" />';
			$input['address']			=	'<input type="text" size="35" class="input-large" value="' . htmlspecialchars( cbgjClass::getCleanParam( $eventPlotting, 'events_address' ) ) . '" name="events_address" id="events_address" />';

			$calendar					=	$this->getCalendar( null, $plugin );

			$return						.=		'<form action="' . cbgjClass::getPluginURL( array( 'plugin', 'events_save', (int) $category->get( 'id' ), (int) $group->get( 'id' ) ) ) . '" method="post" name="gjForm_event" id="gjForm_event" class="gjForm gjToggle form-horizontal">';

			if ( cbgjClass::hasAccess( 'mod_lvl4', $authorized ) ) {
				$return					.=			'<div class="gjEditContentInput control-group">'
										.				'<label class="gjEditContentInputTitle control-label">' . CBTxt::Th( 'Published' ) . '</label>'
										.				'<div class="gjEditContentInputField controls">'
										.					$input['publish']
										.					'<span class="gjEditContentInputIcon help-inline">'
										.						cbgjClass::getIcon( CBTxt::T( 'Select publish status of event. Unpublished events will not be visible to the public.' ) )
										.					'</span>'
										.				'</div>'
										.			'</div>';
			}

			$return						.=			'<div class="gjEditContentInput control-group">'
										.				'<label class="gjEditContentInputTitle control-label">' . CBTxt::Th( 'Title' ) . '</label>'
										.				'<div class="gjEditContentInputField controls">'
										.					$input['title']
										.					'<span class="gjEditContentInputIcon help-inline">'
										.						cbgjClass::getIcon( null, CBTxt::T( 'Required' ), 'icon-star' )
										.						cbgjClass::getIcon( CBTxt::T( 'Input event title.' ) )
										.					'</span>'
										.				'</div>'
										.			'</div>'
										.			'<div class="gjEditContentInput control-group">'
										.				'<label class="gjEditContentInputTitle control-label">' . CBTxt::Th( 'Event' ) . '</label>'
										.				'<div class="gjEditContentInputField controls">'
										.					$input['event']
										.					'<span class="gjEditContentInputIcon help-inline">'
										.						cbgjClass::getIcon( null, CBTxt::T( 'Required' ), 'icon-star' )
										.						cbgjClass::getIcon( CBTxt::T( 'Input event information.' ) )
										.					'</span>'
										.				'</div>'
										.			'</div>'
										.			'<div class="gjEditContentInput control-group">'
										.				'<label class="gjEditContentInputTitle control-label">' . CBTxt::Th( 'Location' ) . '</label>'
										.				'<div class="gjEditContentInputField controls">'
										.					$input['location']
										.					'<span class="gjEditContentInputIcon help-inline">'
										.						cbgjClass::getIcon( null, CBTxt::T( 'Required' ), 'icon-star' )
										.						cbgjClass::getIcon( CBTxt::T( 'Input event location (e.g. City Hall).' ) )
										.					'</span>'
										.				'</div>'
										.			'</div>';

			if ( $eventPlotting ) {
				$return					.=			'<div class="gjEditContentInput control-group">'
										.				'<label class="gjEditContentInputTitle control-label">' . CBTxt::Th( 'Address' ) . '</label>'
										.				'<div class="gjEditContentInputField controls">'
										.					$input['address']
										.					'<span class="gjEditContentInputIcon help-inline">'
										.						cbgjClass::getIcon( CBTxt::T( 'Optionally input event address (e.g. 140 West Street) or coordinates (e.g. 180,-50). Address determines Google mapping location of event.' ) )
										.					'</span>'
										.				'</div>'
										.			'</div>';
			}

			$return						.=			'<div class="gjEditContentInput control-group">'
										.				'<label class="gjEditContentInputTitle control-label">' . CBTxt::Th( 'Date' ) . '</label>'
										.				'<div class="gjEditContentInputField controls">'
										.					$calendar['date']
										.					'<span class="gjEditContentInputIcon help-inline">'
										.						cbgjClass::getIcon( null, CBTxt::T( 'Required' ), 'icon-star' )
										.						cbgjClass::getIcon( CBTxt::T( 'Select event date.' ) )
										.					'</span>'
										.				'</div>'
										.			'</div>'
										.			'<div class="gjEditContentInput control-group">'
										.				'<label class="gjEditContentInputTitle control-label">' . CBTxt::Th( 'Time' ) . '</label>'
										.				'<div class="gjEditContentInputField controls">'
										.					$calendar['time']
										.					'<span class="gjEditContentInputIcon help-inline">'
										.						cbgjClass::getIcon( null, CBTxt::T( 'Required' ), 'icon-star' )
										.						cbgjClass::getIcon( CBTxt::T( 'Select event time.' ) )
										.					'</span>'
										.				'</div>'
										.			'</div>';

			if ( $captcha !== false ) {
				$return					.=			'<div class="gjEditContentInput control-group">'
										.				'<label class="gjEditContentInputTitle control-label">' . CBTxt::Th( 'Captcha' ) . '</label>'
										.				'<div class="gjEditContentInputField controls">'
										.					'<div style="margin-bottom: 5px;">' . $captcha['code'] . '</div>'
										.					'<div>' . $captcha['input'] . '</div>'
										.					'<span class="gjEditContentInputIcon help-inline">'
										.						cbgjClass::getIcon( null, CBTxt::T( 'Required' ), 'icon-star' )
										.					'</span>'
										.				'</div>'
										.			'</div>';
			}

			$return						.=			'<div class="gjButtonWrapper form-actions">'
										.				'<input type="submit" value="' . htmlspecialchars( CBTxt::T( 'Schedule Event' ) ) . '" class="gjButton gjButtonSubmit btn btn-primary" />&nbsp;'
										.				( $eventsToggle ? '<a href="#gjEventToggle" role="button" class="gjButton gjButtonCancel btn btn-mini gjToggleCollapse">' . CBTxt::Th( 'Cancel' ) . '</a>' : null )
										.			'</div>'
										.			cbGetSpoofInputTag( 'plugin' )
										.		'</form>';
		}

		$return							.=		'<form action="' . $group->getUrl() . '" method="post" name="gjForm_events" id="gjForm_events" class="gjForm">';

		if ( $eventsToggle || $eventsSearch ) {
			$return						.=			'<div class="gjTop row-fluid">'
										.				'<div class="gjTop gjTopLeft span6">'
										.					( $eventsToggle ? '<a href="#gjForm_event" id="gjEventToggle" role="button" class="gjButton btn gjToggleExpand">' . CBTxt::Th( 'New Event' ) . '</a>' : null )
										.				'</div>'
										.				'<div class="gjTop gjTopRight span6">'
										.					( $eventsSearch ? $pageNav->search : null )
										.				'</div>'
										.			'</div>';
		}

		if ( $rows ) foreach ( $rows as $row ) {
			$authorized					=	cbgjClass::getAuthorization( $category, $group, $user, $row->getOwner() );
			$attendingUrl				=	cbgjClass::getPluginURL( array( 'plugin', 'events_attending', (int) $category->get( 'id' ), (int) $group->get( 'id' ), (int) $row->get( 'id' ) ) );
			$attendees					=	$row->getAttendees();
			$userStatus					=	null;

			if ( $attendees ) foreach ( $attendees as $attendance => $attendances ) {
				if ( $attendances ) foreach ( $attendances as $attendee ) {
					if ( $attendee == $user->id ) {
						$userStatus		=	$attendance;
					}
				}
			}

			$canMessage					=	( ( ! $row->isPast() ) && ( ( $plugin->params->get( 'events_message', 1 ) && cbgjClass::hasAccess( 'usr_me', $authorized ) ) || cbgjClass::hasAccess( 'mod_lvl2', $authorized ) ) && ( $row->get( 'published' ) || cbgjClass::hasAccess( 'mod_lvl4', $authorized ) ) );
			$canAttend					=	( ( ! $row->isPast() ) && ( cbgjClass::hasAccess( 'mod_lvl5', $authorized ) && ( ! cbgjClass::hasAccess( 'usr_me', $authorized ) ) && ( $userStatus != 'yes' ) ) && ( $row->get( 'published' ) || cbgjClass::hasAccess( 'mod_lvl4', $authorized ) ) );
			$canMaybeAttend				=	( ( ! $row->isPast() ) && ( cbgjClass::hasAccess( 'mod_lvl5', $authorized ) && ( ! cbgjClass::hasAccess( 'usr_me', $authorized ) ) && ( $userStatus != 'maybe' ) ) && ( $row->get( 'published' ) || cbgjClass::hasAccess( 'mod_lvl4', $authorized ) ) );
			$canNotAttend				=	( ( ! $row->isPast() ) && ( cbgjClass::hasAccess( 'mod_lvl5', $authorized ) && ( ! cbgjClass::hasAccess( 'usr_me', $authorized ) ) && ( $userStatus != 'no' ) ) && ( $row->get( 'published' ) || cbgjClass::hasAccess( 'mod_lvl4', $authorized ) ) );

			if ( $row->get( 'location' ) ) {
				$location				=	htmlspecialchars( $row->get( 'location' ) );

				if ( $eventPlotting && $row->get( 'address' ) && $row->get( 'latitude' ) && $row->get( 'longitude' ) ) {
					$location			=	'<a href="javascript: void(0);" rel="' . htmlspecialchars( $row->get( 'latitude' ) ) . '|*|' . htmlspecialchars( $row->get( 'longitude' ) ) . '" title="' . htmlspecialchars( $row->get( 'location' ) ) . '|*|' . htmlspecialchars( $row->get( 'address' ) ) . '">' . $location . '</a>';
				}

				$location				=	'<div class="gjEventLocation"><i class="icon-map-marker"></i> ' . $location . '</div>';
			} else {
				$location				=	null;
			}

			if ( $row->get( 'published' ) == 1 ) {
				$state					=	'<div><a href="javascript: void(0);" onclick="' . cbgjClass::getPluginURL( array( 'plugin', 'events_unpublish', (int) $category->get( 'id' ), (int) $group->get( 'id' ), (int) $row->get( 'id' ) ), CBTxt::T( 'Are you sure you want to unpublish this event?' ) ) . '"><i class="icon-ban-circle"></i> ' . CBTxt::Th( 'Unpublish' ) . '</a></div>';
			} else {
				$state					=	'<div><a href="' . cbgjClass::getPluginURL( array( 'plugin', 'events_publish', (int) $category->get( 'id' ), (int) $group->get( 'id' ), (int) $row->get( 'id' ) ) ) . '"><i class="icon-ok"></i> ' . CBTxt::Th( 'Publish' ) . '</a></div>';
			}

			$canApprove					=	( $eventsApprove && ( $row->get( 'published' ) == -1 ) && cbgjClass::hasAccess( 'mod_lvl4', $authorized ) );

			$return						.=			'<div class="gjContent row-fluid">'
										.				'<div class="gjContentLogo span2">'
										.					'<div class="gjContentLogoRow">' . $row->getOwnerName( true ) . '</div>'
										.					'<div class="gjContentLogoRow">' . $row->getOwnerAvatar( true ) . '</div>'
										.					'<div class="gjContentLogoRow">' . $row->getOwnerOnline() . '</div>'
										.				'</div>'
										.				'<div class="gjContentBody mini-layout span10">'
										.					'<div class="gjContentBodyHeader row-fluid">'
										.						'<div class="gjContentBodyTitle span9"><h5>' . $row->getTitle() . '<small> ' . $row->getDate() . '</small></h5></div>'
										.						'<div class="gjContentBodyMenu span3">';

			if ( $canApprove ) {
				$return					.=							'<input type="button" value="' . htmlspecialchars( CBTxt::T( 'Approve' ) ) . '" class="gjButton btn btn-mini btn-success" onclick="' . cbgjClass::getPluginURL( array( 'plugin', 'events_publish', (int) $category->get( 'id' ), (int) $group->get( 'id' ), (int) $row->get( 'id' ) ), true ) . '" />';
			} else {
				if ( ! $row->get( 'published' ) ) {
					$return			.=								cbgjClass::getIcon( null, CBTxt::T( 'This event is currently unpublished.' ), 'icon-eye-close' );
				}
			}

			if ( ( ! $canApprove ) && $canAttend && $canMaybeAttend && $canNotAttend ) {
				$unsureAttend			=	true;

				$return					.=							' <input type="button" value="' . htmlspecialchars( CBTxt::T( 'Attend' ) ) . '" class="gjButton btn btn-mini btn-success" onclick="' . cbgjClass::getPluginURL( array( 'plugin', 'events_yes', (int) $category->get( 'id' ), (int) $group->get( 'id' ), (int) $row->get( 'id' ) ), CBTxt::T( 'Are you sure you want to attend this event?' ) ) . '" />';
			} else {
				$unsureAttend			=	false;
			}

			if ( cbgjClass::hasAccess( array( 'mod_lvl4', 'mod_lvl3', 'usr_me' ), $authorized ) || $canMessage || $canAttend || $canMaybeAttend || $canNotAttend ) {
				$menuItems				=	( cbgjClass::hasAccess( array( 'mod_lvl3', 'usr_me' ), $authorized ) ? '<div><a href="' . cbgjClass::getPluginURL( array( 'plugin', 'events_edit', (int) $category->get( 'id' ), (int) $group->get( 'id' ), (int) $row->get( 'id' ) ) ) . '"><i class="icon-pencil"></i> ' . CBTxt::Th( 'Edit' ) . '</a></div>' : null )
										.	( ( ! $canApprove ) && cbgjClass::hasAccess( 'mod_lvl4', $authorized ) ? $state : null )
										.	( $canMessage ? '<div><a href="' . cbgjClass::getPluginURL( array( 'plugin', 'events_message', (int) $category->get( 'id' ), (int) $group->get( 'id' ), (int) $row->get( 'id' ) ) ) . '"><i class="icon-envelope"></i> ' . CBTxt::Th( 'Message' ) . '</a></div>' : null )
										.	( $canAttend && ( ! $unsureAttend ) ? '<div><a href="javascript: void(0);" onclick="' . cbgjClass::getPluginURL( array( 'plugin', 'events_yes', (int) $category->get( 'id' ), (int) $group->get( 'id' ), (int) $row->get( 'id' ) ), CBTxt::T( 'Are you sure you want to attend this event?' ) ) . '"><i class="icon-thumbs-up"></i> ' . CBTxt::Th( 'Attend' ) . '</a></div>' : null )
										.	( $canMaybeAttend ? '<div><a href="javascript: void(0);" onclick="' . cbgjClass::getPluginURL( array( 'plugin', 'events_maybe', (int) $category->get( 'id' ), (int) $group->get( 'id' ), (int) $row->get( 'id' ) ), CBTxt::T( 'Are you sure you maybe want to attend this event?' ) ) . '"><i class="icon-hand-right"></i> ' . CBTxt::Th( 'Maybe Attend' ) . '</a></div>' : null )
										.	( $canNotAttend ? '<div><a href="javascript: void(0);" onclick="' . cbgjClass::getPluginURL( array( 'plugin', 'events_no', (int) $category->get( 'id' ), (int) $group->get( 'id' ), (int) $row->get( 'id' ) ), CBTxt::T( 'Are you sure you do not want to attend this event?' ) ) . '"><i class="icon-thumbs-down"></i> ' . CBTxt::Th( 'Not Attending' ) . '</a></div>' : null )
										.	( cbgjClass::hasAccess( array( 'mod_lvl4', 'usr_me' ), $authorized ) ? '<div><a href="javascript: void(0);" onclick="' . cbgjClass::getPluginURL( array( 'plugin', 'events_delete', (int) $category->get( 'id' ), (int) $group->get( 'id' ), (int) $row->get( 'id' ) ), CBTxt::T( 'Are you sure you want to delete this event?' ) ) . '"><i class="icon-remove"></i> ' . CBTxt::Th( 'Delete' ) . '</a></div>' : null );

				$return					.=							cbgjClass::getDropdown( $menuItems, CBTxt::Th( 'Menu' ) );
			}

			$return						.=						'</div>'
										.					'</div>'
										.					'<div class="gjContentBodyInfo">' . ( $row->getEvent() || $location ? '<div class="well well-small">' . ( $row->getEvent() ? '<div class="gjContentBodyInfoRow">' . $row->getEvent() . '</div>' : null ) . $location . '</div>' : null ) . '</div>'
										.					'<div class="gjContentDivider"></div>'
										.					'<div class="gjContentBodyFooter">'
										.						( count( $attendees['yes'] ) ? '<a href="' . $attendingUrl . '">' . CBTxt::Ph( '[event_attend_yes] Attending', array( '[event_attend_yes]' => count( $attendees['yes'] ) ) ) . '</a>' . ( count( $attendees['maybe'] ) || count( $attendees['no'] ) ? ' | ' : null ) : null )
										.						( count( $attendees['maybe'] ) ? '<a href="' . $attendingUrl . '">' . CBTxt::Ph( '[event_attend_maybe] Maybe Attending', array( '[event_attend_maybe]' => count( $attendees['maybe'] ) ) ) . '</a>' . ( count( $attendees['no'] ) ? ' | ' : null ) : null )
										.						( count( $attendees['no'] ) ? '<a href="' . $attendingUrl . '">' . CBTxt::Ph( '[event_attend_no] Not Attending', array( '[event_attend_no]' => count( $attendees['no'] ) ) ) . '</a>' : null )
										.					'</div>'
										.				'</div>'
										.			'</div>';
		} else {
			$return						.=			'<div class="gjContent">';

			if ( $eventsSearch && $pageNav->searching ) {
				$return					.=				CBTxt::Th( 'No event search results found.' );
			} else {
				$return					.=				CBTxt::Ph( 'This [group] has no events scheduled.', array( '[group]' => cbgjClass::getOverride( 'group' ) ) );
			}

			$return						.=			'</div>';
		}

		if ( $eventsPaging ) {
			$return						.=			'<div class="gjPaging pagination pagination-centered">'
										.				( $pageNav->total > $pageNav->limit ? $pageNav->pagelinks : null )
										.				( ! $eventsLimitbox ? $pageNav->getLimitBox( false ) : ( $pageNav->total ? '<div class="gjPagingLimitbox">' . $pageNav->limitbox . '</div>' : null ) )
										.			'</div>';
		}

		$return							.=			cbGetSpoofInputTag( 'plugin' )
										.		'</form>'
										.		( $eventPlotting ? '<div id="gjEventsMap"></div>' : null )
										.	'</div>'
										.	$tabs->endTab();

		return $return;
	}

	private function attendEvent( $catid, $grpid, $id, $status, $user, $plugin ) {
		$category							=	cbgjData::getCategories( array( array( 'cat_access', 'mod_lvl1' ), $user ), array( 'id', '=', (int) $catid ), null, null, false );
		$group								=	cbgjData::getGroups( array( array( 'grp_access', 'mod_lvl2' ), $user ), array( 'id', '=', (int) $grpid ), null, null, false );
		$row								=	cbgjEventsData::getEvents( array( 'mod_lvl5', $user ), array( 'id', '=', (int) $id ), null, null, false );

		if ( ( ! $group->get( 'id' ) ) && $row->get( 'id' ) ) {
			$group							=	$row->getGroup();
		}

		if ( ( ! $category->get( 'id' ) ) && $group->get( 'id' ) ) {
			$category						=	$group->getCategory();
		}

		$authorized							=	cbgjClass::getAuthorization( $category, $group, $user, $row->getOwner() );

		if ( $row->get( 'id' ) && ( ! cbgjClass::hasAccess( 'usr_me', $authorized ) ) ) {
			if ( $row->isPast() ) {
				cbgjClass::getPluginURL( array( 'groups', 'show', (int) $category->get( 'id' ), (int) $group->get( 'id' ) ), CBTxt::T( 'Event expired!' ), false, true, 'error' );
			}

			if ( ! $row->storeAttendance( $status ) ) {
				cbgjClass::getPluginURL( array( 'groups', 'show', (int) $category->get( 'id' ), (int) $group->get( 'id' ) ), CBTxt::P( 'Event attendance failed to save! Error: [error]', array( '[error]' => $row->getError() ) ), false, true, 'error' );
			}

			if ( $row->get( 'published' ) ) {
				$notification				=	cbgjData::getNotifications( array( array( 'events_notifications' ), 'owner' ), array( array( 'type', '=', 'group' ), array( 'item', '=', (int) $group->get( 'id' ) ), array( 'user_id', '=', (int) $row->get( 'user_id' ) ), array( 'params', 'CONTAINS', 'group_eventyes=1', array( 'params', 'CONTAINS', 'group_eventmaybe=1' ), array( 'params', 'CONTAINS', 'group_eventno=1' ) ) ), null, null, false );

				if ( $notification->get( 'id' ) ) {
					$notificationParams		=	$notification->getParams();
					$subject				=	CBTxt::P( '[group_name] - Event Attendance!', $row->getSubstitutionExtras( true ) );
					$message				=	null;

					if ( $status == 0 ) {
						if ( $notificationParams->get( 'group_eventno' ) ) {
							$message		=	CBTxt::P( '[user] is not attending [event_url] in [group]!', $row->getSubstitutionExtras( true ) );
						}
					} elseif ( $status == 1 ) {
						if ( $notificationParams->get( 'group_eventyes' ) ) {
							$message		=	CBTxt::P( '[user] is attending [event_url] in [group]!', $row->getSubstitutionExtras( true ) );
						}
					} elseif ( $status == 2 ) {
						if ( $notificationParams->get( 'group_eventmaybe' ) ) {
							$message		=	CBTxt::P( '[user] is maybe attending [event_url] in [group]!', $row->getSubstitutionExtras( true ) );
						}
					}

					if ( $message ) {
						cbgjClass::getNotification( $notification->get( 'user_id' ), $user->id, $subject, $message, 1, $category, $group );
					}
				}
			}

			cbgjClass::getPluginURL( array( 'groups', 'show', (int) $category->get( 'id' ), (int) $group->get( 'id' ) ), CBTxt::T( 'Event attendance saved successfully!' ), false, true, null, false, false, true );
		} else {
			if ( $group->get( 'id' ) ) {
				$url						=	array( 'groups', 'show', (int) $category->get( 'id' ), (int) $group->get( 'id' ) );
			} elseif ( $category->get( 'id' ) ) {
				$url						=	array( 'categories', 'show', (int) $category->get( 'id' ) );
			} else {
				$url						=	array( 'overview' );
			}

			cbgjClass::getPluginURL( $url, CBTxt::T( 'Not authorized.' ), false, true, 'error' );
		}
	}

	private function editEvent( $catid, $grpid, $id, $user, $plugin, $message = null ) {
		global $_CB_framework;

		$category				=	cbgjData::getCategories( array( array( 'cat_access', 'mod_lvl1' ), $user ), array( 'id', '=', (int) $catid ), null, null, false );
		$group					=	cbgjData::getGroups( array( array( 'grp_access', 'mod_lvl2' ), $user ), array( 'id', '=', (int) $grpid ), null, null, false );
		$row					=	cbgjEventsData::getEvents( array( array( 'mod_lvl3', 'usr_me' ), $user ), array( 'id', '=', (int) $id ), null, null, false );

		if ( ( ! $group->get( 'id' ) ) && $row->get( 'id' ) ) {
			$group				=	$row->getGroup();
		}

		if ( ( ! $category->get( 'id' ) ) && $group->get( 'id' ) ) {
			$category			=	$group->getCategory();
		}

		$authorized				=	cbgjClass::getAuthorization( $category, $group, $user, $row->getOwner() );

		if ( $row->get( 'id' ) ) {
			$params				=	$group->getParams();
			$eventsEditor		=	$plugin->params->get( 'events_editor', 1 );

			$row->setPathway( CBTxt::T( 'Edit Event' ) );

			cbgjClass::getTemplate( 'cbgroupjiveevents_edit' );

			if ( $plugin->params->get( 'general_validate', 1 ) ) {
				cbgjClass::getFormValidation( null, "groups: { events_time: 'events_date_hr events_date_min events_date_ampm' }" );
			}

			$input				=	array();

			$input['publish']	=	moscomprofilerHTML::yesnoSelectList( 'published', ( ! cbgjClass::hasAccess( 'mod_lvl4', $authorized ) ? 'disabled="disabled"' : null ) . ' class="input-small"', (int) cbgjClass::getCleanParam( cbgjClass::hasAccess( 'mod_lvl4', $authorized ), 'events_published', $row->get( 'published', ( $params->get( 'events_approve', $plugin->params->get( 'events_approve', 0 ) ) ? 0 : 1 ) ) ) );
			$input['title']		=	'<input type="text" size="35" class="input-large required" value="' . htmlspecialchars( cbgjClass::getCleanParam( true, 'events_title', $row->get( 'title' ) ) ) . '" name="events_title" id="events_title" />';

			if ( $eventsEditor >= 2 ) {
				$event			=	cbgjClass::getHTMLCleanParam( true, 'events_event', $row->get( 'event' ) );
			} else {
				$event			=	cbgjClass::getCleanParam( true, 'events_event', $row->get( 'event' ) );
			}

			if ( $eventsEditor == 3 ) {
				$input['event']	=	$_CB_framework->displayCmsEditor( 'events_event', $event, 400, 200, 40, 6 );
			} else {
				$input['event']	=	'<textarea id="events_event" name="events_event" class="input-xlarge required" cols="40" rows="6">' . htmlspecialchars( $event ) . '</textarea>';
			}

			$input['location']	=	'<input type="text" size="35" class="input-large required" value="' . htmlspecialchars( cbgjClass::getCleanParam( true, 'events_location', $row->get( 'location' ) ) ) . '" name="events_location" id="events_location" />';
			$input['address']	=	'<input type="text" size="35" class="input-large" value="' . htmlspecialchars( cbgjClass::getCleanParam( $plugin->params->get( 'events_plotting', 1 ), 'events_address', $row->get( 'address' ) ) ) . '" name="events_address" id="events_address" />';

			$calendar			=	$this->getCalendar( $row, $plugin );

			if ( class_exists( 'HTML_cbgroupjiveeventsEdit' ) ) {
				$return			=	HTML_cbgroupjiveeventsEdit::showEventEdit( $row, $input, $group, $category, $user, $plugin );
			} else {
				$return			=	'<div class="gjEventsEdit">'
								.		'<form action="' . cbgjClass::getPluginURL( array( 'plugin', 'events_save', (int) $category->get( 'id' ), (int) $group->get( 'id' ), (int) $row->get( 'id' ) ), null, true, false, null, 'current' ) . '" method="post" enctype="multipart/form-data" name="gjForm" id="gjForm" class="gjForm form-horizontal">'
								.			'<legend class="gjEditTitle">' . CBTxt::Th( 'Edit Event' ) . '</legend>';

				if ( cbgjClass::hasAccess( 'mod_lvl4', $authorized ) ) {
					$return		.=			'<div class="gjEditContentInput control-group">'
								.				'<label class="gjEditContentInputTitle control-label">' . CBTxt::Th( 'Published' ) . '</label>'
								.				'<div class="gjEditContentInputField controls">'
								.					$input['publish']
								.					'<span class="gjEditContentInputIcon help-inline">'
								.						cbgjClass::getIcon( CBTxt::T( 'Select publish status of event. Unpublished events will not be visible to the public.' ) )
								.					'</span>'
								.				'</div>'
								.			'</div>';
				}

				$return			.=			'<div class="gjEditContentInput control-group">'
								.				'<label class="gjEditContentInputTitle control-label">' . CBTxt::Th( 'Title' ) . '</label>'
								.				'<div class="gjEditContentInputField controls">'
								.					$input['title']
								.					'<span class="gjEditContentInputIcon help-inline">'
								.						cbgjClass::getIcon( null, CBTxt::T( 'Required' ), 'icon-star' )
								.						cbgjClass::getIcon( CBTxt::T( 'Input event title.' ) )
								.					'</span>'
								.				'</div>'
								.			'</div>'
								.			'<div class="gjEditContentInput control-group">'
								.				'<label class="gjEditContentInputTitle control-label">' . CBTxt::Th( 'Event' ) . '</label>'
								.				'<div class="gjEditContentInputField controls">'
								.					$input['event']
								.					'<span class="gjEditContentInputIcon help-inline">'
								.						cbgjClass::getIcon( null, CBTxt::T( 'Required' ), 'icon-star' )
								.						cbgjClass::getIcon( CBTxt::T( 'Input event information.' ) )
								.					'</span>'
								.				'</div>'
								.			'</div>'
								.			'<div class="gjEditContentInput control-group">'
								.				'<label class="gjEditContentInputTitle control-label">' . CBTxt::Th( 'Location' ) . '</label>'
								.				'<div class="gjEditContentInputField controls">'
								.					$input['location']
								.					'<span class="gjEditContentInputIcon help-inline">'
								.						cbgjClass::getIcon( null, CBTxt::T( 'Required' ), 'icon-star' )
								.						cbgjClass::getIcon( CBTxt::T( 'Input event location (e.g. City Hall).' ) )
								.					'</span>'
								.				'</div>'
								.			'</div>';

			if ( $plugin->params->get( 'events_plotting', 1 ) ) {
				$return			.=			'<div class="gjEditContentInput control-group">'
								.				'<label class="gjEditContentInputTitle control-label">' . CBTxt::Th( 'Address' ) . '</label>'
								.				'<div class="gjEditContentInputField controls">'
								.					$input['address']
								.					'<span class="gjEditContentInputIcon help-inline">'
								.						cbgjClass::getIcon( CBTxt::T( 'Optionally input event address (e.g. 140 West Street) or coordinates (e.g. 180,-50). Address determines Google mapping location of event.' ) )
								.					'</span>'
								.				'</div>'
								.			'</div>';
			}

			$return				.=			'<div class="gjEditContentInput control-group">'
								.				'<label class="gjEditContentInputTitle control-label">' . CBTxt::Th( 'Date' ) . '</label>'
								.				'<div class="gjEditContentInputField controls">'
								.					$calendar['date']
								.					'<span class="gjEditContentInputIcon help-inline">'
								.						cbgjClass::getIcon( null, CBTxt::T( 'Required' ), 'icon-star' )
								.						cbgjClass::getIcon( CBTxt::T( 'Select event date.' ) )
								.					'</span>'
								.				'</div>'
								.			'</div>'
								.			'<div class="gjEditContentInput control-group">'
								.				'<label class="gjEditContentInputTitle control-label">' . CBTxt::Th( 'Time' ) . '</label>'
								.				'<div class="gjEditContentInputField controls">'
								.					$calendar['time']
								.					'<span class="gjEditContentInputIcon help-inline">'
								.						cbgjClass::getIcon( null, CBTxt::T( 'Required' ), 'icon-star' )
								.						cbgjClass::getIcon( CBTxt::T( 'Select event time.' ) )
								.					'</span>'
								.				'</div>'
								.			'</div>'
								.			'<div class="gjButtonWrapper form-actions">'
								.				'<input type="submit" value="' . htmlspecialchars( CBTxt::T( 'Update Event' ) ) . '" class="gjButton gjButtonSubmit btn btn-primary" />&nbsp;'
								.				'<input type="button" value="' . htmlspecialchars( CBTxt::T( 'Cancel' ) ) . '" class="gjButton gjButtonCancel btn btn-mini" onclick="' . cbgjClass::getPluginURL( array( 'groups', 'show', (int) $category->get( 'id' ), (int) $group->get( 'id' ) ), CBTxt::T( 'Are you sure you want to cancel? All unsaved data will be lost!' ), true, false, null, false, false, true ) . '" />'
								.			'</div>'
								.			cbGetSpoofInputTag( 'plugin' )
								.		'</form>'
								.	'</div>';
			}

			cbgjClass::displayMessage( $message );

			echo $return;
		} else {
			if ( $group->get( 'id' ) ) {
				$url			=	array( 'groups', 'show', (int) $category->get( 'id' ), (int) $group->get( 'id' ) );
			} elseif ( $category->get( 'id' ) ) {
				$url			=	array( 'categories', 'show', (int) $category->get( 'id' ) );
			} else {
				$url			=	array( 'overview' );
			}

			cbgjClass::getPluginURL( $url, CBTxt::T( 'Not authorized.' ), false, true, 'error' );
		}
	}

	private function saveEvent( $catid, $grpid, $id, $user, $plugin ) {
		global $ueConfig;

		$category							=	cbgjData::getCategories( array( array( 'cat_access', 'mod_lvl1' ), $user ), array( 'id', '=', (int) $catid ), null, null, false );
		$group								=	cbgjData::getGroups( array( array( 'grp_access', 'mod_lvl2' ), $user ), array( 'id', '=', (int) $grpid ), null, null, false );
		$row								=	cbgjEventsData::getEvents( array( array( 'mod_lvl3', 'usr_me' ), $user ), array( 'id', '=', (int) $id ), null, null, false );

		if ( ( ! $group->get( 'id' ) ) && $row->get( 'id' ) ) {
			$group							=	$row->getGroup();
		}

		if ( ( ! $category->get( 'id' ) ) && $group->get( 'id' ) ) {
			$category						=	$group->getCategory();
		}

		$authorized							=	cbgjClass::getAuthorization( $category, $group, $user, $row->getOwner() );

		if ( $row->get( 'id' ) || cbgjClass::hasAccess( 'events_schedule', $authorized ) ) {
			$params							=	$group->getParams();
			$eventsApprove					=	$params->get( 'events_approve', $plugin->params->get( 'events_approve', 0 ) );

			$row->set( 'published', (int) cbgjClass::getCleanParam( cbgjClass::hasAccess( 'mod_lvl4', $authorized ), 'published', $row->get( 'published', ( $eventsApprove ? -1 : 1 ) ) ) );
			$row->set( 'user_id', (int) $row->get( 'user_id', $user->id ) );
			$row->set( 'group', (int) $row->get( 'group', $group->get( 'id' ) ) );
			$row->set( 'title', cbgjClass::getWordFiltering( cbgjClass::getCleanParam( true, 'events_title', $row->get( 'title' ) ) ) );

			if ( $plugin->params->get( 'events_editor', 1 ) >= 2 ) {
				$row->set( 'event', cbgjClass::getWordFiltering( cbgjClass::getHTMLCleanParam( true, 'events_event', $row->get( 'event' ) ) ) );
			} else {
				$row->set( 'event', cbgjClass::getWordFiltering( cbgjClass::getCleanParam( true, 'events_event', $row->get( 'event' ) ) ) );
			}

			$row->set( 'location', cbgjClass::getWordFiltering( cbgjClass::getCleanParam( true, 'events_location', $row->get( 'location' ) ) ) );
			$row->set( 'address', cbgjClass::getWordFiltering( cbgjClass::getCleanParam( $plugin->params->get( 'events_plotting', 1 ), 'events_address', $row->get( 'address' ) ) ) );

			if ( ! $row->get( 'attending' ) ) {
				$row->set( 'attending', (int) $row->get( 'user_id' ) . ':1' );
			}

			$date							=	cbgjClass::getCleanParam( true, 'events_date', cbgjClass::getUTCDate( null, $row->get( 'date' ) ) );

			if ( $date ) {
				$date						=	dateConverter( $date, str_replace( 'y', 'Y', $ueConfig['date_format'] ), 'Y-m-d' );
			}

			$time							=	cbgjClass::getCleanParam( true, 'events_time', cbgjClass::getUTCDate( null, $row->get( 'date' ) ) );

			if ( $time ) {
				$time						=	cbgjClass::getUTCDate( 'H:i:s', $time );
			}

			if ( $date && $time ) {
				$row->set( 'date', $date . ' ' . $time );
			} else {
				$row->set( 'date', '0000-00-00 00:00:00' );
			}

			if ( $row->get( 'address' ) ) {
				$isHttps					=	( isset( $_SERVER['HTTPS'] ) && ( ! empty( $_SERVER['HTTPS'] ) ) && ( $_SERVER['HTTPS'] != 'off' ) );

				cbimport( 'cb.snoopy' );

				$snoopy						=	new CBSnoopy();
				$snoopy->read_timeout		=	30;

				$snoopy->fetch( ( $isHttps ? 'https' : 'http' ) . '://maps.googleapis.com/maps/api/geocode/json?address=' . urlencode( $row->get( 'address' ) ) . '&sensor=false' );

				if ( ! $snoopy->error ) {
					if ( function_exists( 'json_decode' ) ) {
						$geocode			=	json_decode( $snoopy->results );

						if ( isset( $geocode->results[0]->geometry->location ) ) {
							$location		=	(array) $geocode->results[0]->geometry->location;

							$row->set( 'latitude', stripslashes( cbGetParam( $location, 'lat', null ) ) );
							$row->set( 'longitude', stripslashes( cbGetParam( $location, 'lng', null ) ) );
						}
					}
				}
			}

			if ( $row->get( 'title' ) == '' ) {
				$row->set( '_error', CBTxt::T( 'Title not specified!' ) );
			} elseif ( $row->get( 'event' ) == '' ) {
				$row->set( '_error', CBTxt::T( 'Event not specified!' ) );
			} elseif ( $row->get( 'location' ) == '' ) {
				$row->set( '_error', CBTxt::T( 'Location not specified!' ) );
			} elseif ( ( $row->get( 'date' ) == '' ) || ( $row->get( 'date' ) == '0000-00-00 00:00:00' ) ) {
				$row->set( '_error', CBTxt::T( 'Date or Time not specified!' ) );
			} elseif ( ! $row->get( 'user_id' ) ) {
				$row->set( '_error', CBTxt::P( '[user] not specified!', array( '[user]' => cbgjClass::getOverride( 'user' ) ) ) );
			} elseif ( ! $row->get( 'group' ) ) {
				$row->set( '_error', CBTxt::P( '[group] not specified!', array( '[group]' => cbgjClass::getOverride( 'group' ) ) ) );
			} elseif ( $plugin->params->get( 'events_captcha', 0 ) && ( ! $row->get( 'id' ) ) && ( ! cbgjClass::hasAccess( 'usr_mod', $authorized ) ) ) {
				$captcha					=	cbgjCaptcha::validate();

				if ( $captcha !== true ) {
					$row->set( '_error', CBTxt::T( $captcha ) );
				}
			}

			$new							=	( $row->get( 'id' ) ? false : true );

			if ( $row->getError() || ( ! $row->store() ) ) {
				if ( ! $new ) {
					$this->editEvent( $category->get( 'id' ), $group->get( 'id' ), $row->get( 'id' ), $user, $plugin, CBTxt::P( 'Event failed to save! Error: [error]', array( '[error]' => $row->getError() ) ) );
				} else {
					CBplug_cbgroupjive::showGroup( $category->get( 'id' ), $group->get( 'id' ), $user, $plugin, CBTxt::P( 'Event failed to save! Error: [error]', array( '[error]' => $row->getError() ) ), '#gjForm_event' );
				}
				return;
			}

			if ( $new ) {
				if ( $row->get( 'published' ) ) {
					$notifications			=	cbgjData::getNotifications( array( array( 'events_notifications' ), 'owner' ), array( array( 'type', '=', 'group' ), array( 'item', '=', $group->id ), array( 'user_id', '!IN', array( $user->id, $row->user_id ) ), array( 'params', 'CONTAINS', 'group_eventnew=1' ) ) );

					if ( $notifications ) {
						$subject			=	CBTxt::P( '[group_name] - Event Scheduled!', $row->getSubstitutionExtras( true ) );
						$message			=	CBTxt::P( '[user] scheduled [event_title_linked] in [group]!', $row->getSubstitutionExtras( true ) );

						foreach ( $notifications as $notification ) {
							cbgjClass::getNotification( $notification->get( 'user_id' ), $row->get( 'user_id' ), $subject, $message, 1, $category, $group );
						}
					}
				} elseif ( $eventsApprove && ( $row->get( 'published' ) == -1 ) && ( ! cbgjClass::hasAccess( 'mod_lvl4', $authorized ) ) ) {
					$notifications			=	cbgjData::getNotifications( array( array( 'events_notifications' ), 'owner' ), array( array( 'type', '=', 'group' ), array( 'item', '=', $group->id ), array( 'user_id', '!IN', array( $user->id, $row->user_id ) ), array( 'params', 'CONTAINS', 'group_eventapprove=1' ) ) );

					if ( $notifications ) {
						$subject			=	CBTxt::P( '[group_name] - Event Scheduled Requires Approval!', $row->getSubstitutionExtras( true ) );
						$message			=	CBTxt::P( '[user] scheduled [event_title_linked] in [group] and requires approval!', $row->getSubstitutionExtras( true ) );

						foreach ( $notifications as $notification ) {
							cbgjClass::getNotification( $notification->get( 'user_id' ), $row->get( 'user_id' ), $subject, $message, 1, $category, $group );
						}
					}
				}
			} elseif ( $row->get( 'published' ) ) {
				$notifications				=	cbgjData::getNotifications( array( array( 'events_notifications' ), 'owner' ), array( array( 'type', '=', 'group' ), array( 'item', '=', $group->id ), array( 'user_id', '!IN', array( $user->id, $row->user_id ) ), array( 'params', 'CONTAINS', 'group_eventupdate=1' ) ) );

				if ( $notifications ) {
					$subject				=	CBTxt::P( '[group_name] - Event Edited!', $row->getSubstitutionExtras( true ) );
					$message				=	CBTxt::P( '[user] edited [event_title_linked] in [group]!', $row->getSubstitutionExtras( true ) );

					foreach ( $notifications as $notification ) {
						cbgjClass::getNotification( $notification->get( 'user_id' ), $row->get( 'user_id' ), $subject, $message, 1, $category, $group );
					}
				}
			}

			if ( $eventsApprove && ( $row->get( 'published' ) == -1 ) && ( ! cbgjClass::hasAccess( 'mod_lvl4', $authorized ) ) ) {
				$successMsg					=	CBTxt::T( 'Event saved successfully and awaiting approval!' );
			} else {
				$successMsg					=	CBTxt::T( 'Event saved successfully!' );
			}

			cbgjClass::getPluginURL( array( 'groups', 'show', (int) $category->get( 'id' ), (int) $group->get( 'id' ) ), $successMsg, false, true, null, false, false, true );
		} else {
			if ( $group->get( 'id' ) ) {
				$url						=	array( 'groups', 'show', (int) $category->get( 'id' ), (int) $group->get( 'id' ) );
			} elseif ( $category->get( 'id' ) ) {
				$url						=	array( 'categories', 'show', (int) $category->get( 'id' ) );
			} else {
				$url						=	array( 'overview' );
			}

			cbgjClass::getPluginURL( $url, CBTxt::T( 'Not authorized.' ), false, true, 'error' );
		}
	}

	public function showEventMessage( $catid, $grpid, $id, $user, $plugin, $message = null ) {
		global $_CB_framework;

		$category					=	cbgjData::getCategories( array( array( 'cat_access', 'mod_lvl1' ), $user ), array( 'id', '=', (int) $catid ), null, null, false );
		$group						=	cbgjData::getGroups( array( array( 'grp_access', 'mod_lvl2' ), $user ), array( 'id', '=', (int) $grpid ), null, null, false );
		$row						=	cbgjEventsData::getEvents( array( array( 'mod_lvl2', 'usr_me' ), $user ), array( 'id', '=', (int) $id ), null, null, false );

		if ( ( ! $group->get( 'id' ) ) && $row->get( 'id' ) ) {
			$group					=	$row->getGroup();
		}

		if ( ( ! $category->get( 'id' ) ) && $group->get( 'id' ) ) {
			$category				=	$group->getCategory();
		}

		$authorized					=	cbgjClass::getAuthorization( $category, $group, $user, $row->getOwner() );

		if ( $row->get( 'id' ) && ( cbgjClass::hasAccess( 'mod_lvl2', $authorized ) || $plugin->params->get( 'events_message', 1 ) ) ) {
			$eventsEditor			=	$plugin->params->get( 'events_editor', 1 );

			if ( $row->isPast() ) {
				cbgjClass::getPluginURL( array( 'groups', 'show', $category->id, $group->id ), CBTxt::T( 'Event expired!' ), false, true, 'error' );
			}

			$row->setPathway( CBTxt::T( 'Message Attending' ) );

			cbgjClass::getTemplate( 'cbgroupjiveevents_message' );

			$input					=	array();

			$input['subject']		=	'<input type="text" id="subject" name="subject" value="' . htmlspecialchars( cbgjClass::getCleanParam( true, 'subject' ) ) . '" class="input-large required" size="40" />';

			if ( $eventsEditor >= 2 ) {
				$body				=	cbgjClass::getHTMLCleanParam( true, 'body' );
			} else {
				$body				=	cbgjClass::getCleanParam( true, 'body' );
			}

			if ( $eventsEditor == 3 ) {
				$input['body']		=	$_CB_framework->displayCmsEditor( 'body', $body, 400, 200, 40, 6 );
			} else {
				$input['body']		=	'<textarea id="body" name="body" class="input-xlarge required" cols="40" rows="6">' . htmlspecialchars( $body ) . '</textarea>';
			}

			if ( $plugin->params->get( 'events_captcha', 0 ) && ( ! cbgjClass::hasAccess( 'usr_mod', $authorized ) ) ) {
				$input['captcha']	=	cbgjCaptcha::render();
			} else {
				$input['captcha']	=	false;
			}

			if ( class_exists( 'HTML_cbgroupjiveeventsMessage' ) ) {
				$return				=	HTML_cbgroupjiveeventsMessage::showEventMessage( $row, $input, $group, $category, $user, $plugin );
			} else {
				$return				=	'<div class="gjEventsMessage">'
									.		'<form action="' . cbgjClass::getPluginURL( array( 'plugin', 'events_send', (int) $category->get( 'id' ), (int) $group->get( 'id' ), (int) $row->get( 'id' ) ), null, true, false, null, 'current' ) . '" method="post" enctype="multipart/form-data" name="gjForm" id="gjForm" class="gjForm form-horizontal">'
									.			'<legend class="gjEditTitle">' . CBTxt::Th( 'Message Attending' ) . '</legend>'
									.			'<div class="gjEditContentInput control-group">'
									.				'<label class="gjEditContentInputTitle control-label">' . CBTxt::Th( 'Subject' ) . '</label>'
									.				'<div class="gjEditContentInputField controls">'
									.					$input['subject']
									.					'<span class="gjEditContentInputIcon help-inline">'
									.						cbgjClass::getIcon( null, CBTxt::T( 'Required' ), 'icon-star' )
									.						cbgjClass::getIcon( CBTxt::T( 'Input event message subject.' ) )
									.					'</span>'
									.				'</div>'
									.			'</div>'
									.			'<div class="gjEditContentInput control-group">'
									.				'<label class="gjEditContentInputTitle control-label">' . CBTxt::Th( 'Body' ) . '</label>'
									.				'<div class="gjEditContentInputField controls">'
									.					$input['body']
									.					'<span class="gjEditContentInputIcon help-inline">'
									.						cbgjClass::getIcon( null, CBTxt::T( 'Required' ), 'icon-star' )
									.						cbgjClass::getIcon( CBTxt::T( 'Input event message body.' ) )
									.					'</span>'
									.				'</div>'
									.			'</div>';

				if ( $input['captcha'] !== false ) {
					$return			.=			'<div class="gjEditContentInput control-group">'
									.				'<label class="gjEditContentInputTitle control-label">' . CBTxt::Th( 'Captcha' ) . '</label>'
									.				'<div class="gjEditContentInputField controls">'
									.					'<div style="margin-bottom: 5px;">' . $input['captcha']['code'] . '</div>'
									.					'<div>' . $input['captcha']['input'] . '</div>'
									.					'<span class="gjEditContentInputIcon help-inline">'
									.						cbgjClass::getIcon( null, CBTxt::T( 'Required' ), 'icon-star' )
									.					'</span>'
									.				'</div>'
									.			'</div>';
				}

				$return				.=			'<div class="gjButtonWrapper form-actions">'
									.				'<input type="submit" value="' . htmlspecialchars( CBTxt::T( 'Send Message' ) ) . '" class="gjButton gjButtonSubmit btn btn-primary" />&nbsp;'
									.				'<input type="button" value="' . htmlspecialchars( CBTxt::T( 'Cancel' ) ) . '" class="gjButton gjButtonCancel btn btn-mini" onclick="' . cbgjClass::getPluginURL( array( 'groups', 'show', (int) $category->get( 'id' ), (int) $group->get( 'id' ) ), CBTxt::T( 'Are you sure you want to cancel? All unsaved data will be lost!' ), true, false, null, false, false, true ) . '" />'
									.			'</div>'
									.			cbGetSpoofInputTag( 'plugin' )
									.		'</form>'
									.	'</div>';
			}

			cbgjClass::displayMessage( $message );

			echo $return;
		} else {
			if ( $group->get( 'id' ) ) {
				$url				=	array( 'groups', 'show', (int) $category->get( 'id' ), (int) $group->get( 'id' ) );
			} elseif ( $category->get( 'id' ) ) {
				$url				=	array( 'categories', 'show', (int) $category->get( 'id' ) );
			} else {
				$url				=	array( 'overview' );
			}

			cbgjClass::getPluginURL( $url, CBTxt::T( 'Not authorized.' ), false, true, 'error' );
		}
	}

	private function sendEventMessage( $catid, $grpid, $id, $user, $plugin ) {
		$category				=	cbgjData::getCategories( array( array( 'cat_access', 'mod_lvl1' ), $user ), array( 'id', '=', (int) $catid ), null, null, false );
		$group					=	cbgjData::getGroups( array( array( 'grp_access', 'mod_lvl2' ), $user ), array( 'id', '=', (int) $grpid ), null, null, false );
		$row					=	cbgjEventsData::getEvents( array( array( 'mod_lvl2', 'usr_me' ), $user ), array( 'id', '=', (int) $id ), null, null, false );

		if ( ( ! $group->get( 'id' ) ) && $row->get( 'id' ) ) {
			$group				=	$row->getGroup();
		}

		if ( ( ! $category->get( 'id' ) ) && $group->get( 'id' ) ) {
			$category			=	$group->getCategory();
		}

		$authorized				=	cbgjClass::getAuthorization( $category, $group, $user, $row->getOwner() );

		if ( $row->get( 'id' ) && ( cbgjClass::hasAccess( 'mod_lvl2', $authorized ) || $plugin->params->get( 'events_message', 1 ) ) ) {
			if ( $row->isPast() ) {
				cbgjClass::getPluginURL( array( 'groups', 'show', (int) $category->get( 'id' ), (int) $group->get( 'id' ) ), CBTxt::T( 'Event expired!' ), false, true, 'error' );
			}

			$subject			=	cbgjClass::getWordFiltering( cbgjClass::getCleanParam( true, 'subject' ) );

			if ( $plugin->params->get( 'events_editor', 1 ) >= 2 ) {
				$body			=	cbgjClass::getWordFiltering( cbgjClass::getHTMLCleanParam( true, 'body' ) );
			} else {
				$body			=	cbgjClass::getWordFiltering( cbgjClass::getCleanParam( true, 'body' ) );
			}

			if ( $subject == '' ) {
				$error			=	CBTxt::T( 'Subject not specified!' );
			} elseif ( $body == '' ) {
				$error			=	CBTxt::T( 'Body not specified!' );
			} elseif ( $plugin->params->get( 'events_captcha', 0 ) && ( ! cbgjClass::hasAccess( 'usr_mod', $authorized ) ) ) {
				$captcha		=	cbgjCaptcha::validate();

				if ( $captcha !== true ) {
					$error		=	CBTxt::T( $captcha );
				}
			}

			$attendees			=	$row->getAttendees();
			$users				=	array();

			if ( $attendees ) foreach ( $attendees as $attendance => $attendances ) {
				if ( $attendances && ( $attendance == 'yes' ) ) foreach ( $attendances as $attendee ) {
					$users[]	=	$attendee;
				}
			}

			if ( isset( $error ) ) {
				$this->showEventMessage( $catid, $grpid, $id, $user, $plugin, CBTxt::P( 'Event message failed to save! Error: [error]', array( '[error]' => $error ) ) );
				return;
			}

			if ( $users ) foreach ( $users as $userId ) {
				$msgSubject		=	CBTxt::P( '[site_name] - [msg_subject]', array_merge( array( '[msg_subject]' => $subject ), $row->getSubstitutionExtras( true ) ) );
				$msgBody		=	CBTxt::P( 'Hello [username], the following is a message from [event_title_linked].<br /><br />[msg_body]', array_merge( array( '[msg_body]' => $body ), $row->getSubstitutionExtras( true ) ) );

				cbgjClass::getNotification( $userId, $user->id, $msgSubject, $msgBody, 2, $category, $group );
			} else {
				cbgjClass::getPluginURL( array( 'groups', 'show', (int) $category->get( 'id' ), (int) $group->get( 'id' ) ), CBTxt::P( 'Event message failed to send! Error: no [users] to message', array( '[users]' => cbgjClass::getOverride( 'user', true ) ) ), false, true, 'error' );
			}

			cbgjClass::getPluginURL( array( 'groups', 'show', (int) $category->get( 'id' ), (int) $group->get( 'id' ) ), CBTxt::T( 'Event message sent successfully!' ), false, true, null, false, false, true );
		} else {
			if ( $group->get( 'id' ) ) {
				$url			=	array( 'groups', 'show', (int) $category->get( 'id' ), (int) $group->get( 'id' ) );
			} elseif ( $category->get( 'id' ) ) {
				$url			=	array( 'categories', 'show', (int) $category->get( 'id' ) );
			} else {
				$url			=	array( 'overview' );
			}

			cbgjClass::getPluginURL( $url, CBTxt::T( 'Not authorized.' ), false, true, 'error' );
		}
	}

	private function showAttending( $catid, $grpid, $id, $user, $plugin ) {
		$category								=	cbgjData::getCategories( array( array( 'cat_access', 'mod_lvl1' ), $user ), array( 'id', '=', (int) $catid ), null, null, false );
		$group									=	cbgjData::getGroups( array( array( 'grp_access', 'mod_lvl2' ), $user ), array( 'id', '=', (int) $grpid ), null, null, false );
		$row									=	cbgjEventsData::getEvents( array( array( 'mod_lvl5', 'events_show' ), $user ), array( 'id', '=', (int) $id ), null, null, false );

		if ( ( ! $group->get( 'id' ) ) && $row->get( 'id' ) ) {
			$group								=	$row->getGroup();
		}

		if ( ( ! $category->get( 'id' ) ) && $group->get( 'id' ) ) {
			$category							=	$group->getCategory();
		}

		if ( $row->get( 'id' ) ) {
			$row->setPathway();

			cbgjClass::getTemplate( 'cbgroupjiveevents_attending' );

			if ( class_exists( 'HTML_cbgroupjiveeventsAttending' ) ) {
				$return							=	HTML_cbgroupjiveeventsAttending::showEventsAttending( $row, $group, $category, $user, $plugin );
			} else {
				$attendees						=	$row->getAttendees();
				$attending						=	array( 'yes' => null, 'maybe' => null, 'no' => null );

				if ( $attendees ) foreach ( $attendees as $attendance => $attendances ) {
					if ( $attendances ) foreach ( $attendances as $attendee ) {
						$cbUser					=&	CBuser::getInstance( (int) $attendee );

						if ( ! $cbUser ) {
							$cbUser				=&	CBuser::getInstance( null );
						}

						$attending[$attendance]	.=	'<div class="gjContentBox mini-layout">'
												.		'<div class="gjContentBoxRow">' . $cbUser->getField( 'formatname', null, 'html', 'none', 'list', 0, true ) . '</div>'
												.		'<div class="gjContentBoxRow">' . $cbUser->getField( 'avatar', null, 'html', 'none', 'list', 0, true ) . '</div>'
												.		'<div class="gjContentBoxRow">' . $cbUser->getField( 'onlinestatus', null, 'html', 'none', 'profile', 0, true  ) . '</div>'
												.	'</div>';
					}
				}

				$return							=	'<div class="gjEventsAttending">'
												.		'<legend class="gjEditTitle">' . CBTxt::Th( 'Attending' ) . '</legend>'
												.		'<div class="gjContent">' . ( $attending['yes'] ? $attending['yes'] : CBTxt::Ph( 'No [users] are attending [event_title].', array( '[users]' => cbgjClass::getOverride( 'user', true ), '[event_title]' => $row->getTitle() ) ) ) . '</div>'
												.		'<legend class="gjEditTitle">' . CBTxt::Th( 'Maybe Attending' ) . '</legend>'
												.		'<div class="gjContent">' . ( $attending['maybe'] ? $attending['maybe'] : CBTxt::Ph( 'No [users] are maybe attending [event_title].', array( '[users]' => cbgjClass::getOverride( 'user', true ), '[event_title]' => $row->getTitle() ) ) ) . '</div>'
												.		'<legend class="gjEditTitle">' . CBTxt::Th( 'Not Attending' ) . '</legend>'
												.		'<div class="gjContent">' . ( $attending['no'] ? $attending['no'] : CBTxt::Ph( 'No [users] are not attending [event_title].', array( '[users]' => cbgjClass::getOverride( 'user', true ), '[event_title]' => $row->getTitle() ) ) ) . '</div>'
												.		'<div class="gjButtonWrapper form-actions">'
												.			'<input type="button" value="' . htmlspecialchars( CBTxt::T( 'Back' ) ) . '" class="gjButton gjButtonCancel btn" onclick="' . cbgjClass::getPluginURL( array( 'groups', 'show', $category->id, $group->id ), true ) . '" />'
												.		'</div>'
												.	'</div>';
			}

			echo $return;
		} else {
			if ( $group->get( 'id' ) ) {
				$url							=	array( 'groups', 'show', (int) $category->get( 'id' ), (int) $group->get( 'id' ) );
			} elseif ( $category->get( 'id' ) ) {
				$url							=	array( 'categories', 'show', (int) $category->get( 'id' ) );
			} else {
				$url							=	array( 'overview' );
			}

			cbgjClass::getPluginURL( $url, CBTxt::T( 'Not authorized.' ), false, true, 'error' );
		}
	}

	private function stateEvent( $catid, $grpid, $id, $state, $user, $plugin ) {
		$category					=	cbgjData::getCategories( array( array( 'cat_access', 'mod_lvl1' ), $user ), array( 'id', '=', (int) $catid ), null, null, false );
		$group						=	cbgjData::getGroups( array( array( 'grp_access', 'mod_lvl2' ), $user ), array( 'id', '=', (int) $grpid ), null, null, false );
		$row						=	cbgjEventsData::getEvents( array( array( 'mod_lvl4', 'grp_approved' ), $user, null, true ), array( 'id', '=', (int) $id ), null, null, false );

		if ( ( ! $group->get( 'id' ) ) && $row->get( 'id' ) ) {
			$group					=	$row->getGroup();
		}

		if ( ( ! $category->get( 'id' ) ) && $group->get( 'id' ) ) {
			$category				=	$group->getCategory();
		}

		if ( $row->get( 'id' ) ) {
			$currentState			=	$row->get( 'published' );

			if ( ! $row->storeState( $state ) ) {
				cbgjClass::getPluginURL( array( 'groups', 'show', (int) $category->get( 'id' ), (int) $group->get( 'id' ) ), CBTxt::P( 'Event state failed to save! Error: [error]', array( '[error]' => $row->getError() ) ), false, true, 'error' );
			}

			if ( $state && ( $currentState == -1 ) ) {
				$notifications		=	cbgjData::getNotifications( array( array( 'events_notifications' ), 'owner' ), array( array( 'type', '=', 'group' ), array( 'item', '=', $group->id ), array( 'user_id', '!IN', array( $user->id, $row->user_id ) ), array( 'params', 'CONTAINS', 'group_eventnew=1' ) ) );

				if ( $notifications ) {
					$subject		=	CBTxt::P( '[group_name] - Event Scheduled!', $row->getSubstitutionExtras( true ) );
					$message		=	CBTxt::P( '[user] scheduled [event_title_linked] in [group]!', $row->getSubstitutionExtras( true ) );

					foreach ( $notifications as $notification ) {
						cbgjClass::getNotification( $notification->get( 'user_id' ), $row->get( 'user_id' ), $subject, $message, 1, $category, $group );
					}
				}

				if ( $user->id != $row->get( 'user_id' ) ) {
					$subject		=	CBTxt::T( '[group_name] - Event Schedule Request Accepted!' );
					$message		=	CBTxt::T( 'Your request to schedule [event_title_linked] in [group] has been accepted!' );

					cbgjClass::getNotification( $row->get( 'user_id' ), $user->id, $subject, $message, 1, $category, $group );
				}
			}

			cbgjClass::getPluginURL( array( 'groups', 'show', (int) $category->get( 'id' ), (int) $group->get( 'id' ) ), CBTxt::T( 'Event state saved successfully!' ), false, true, null, false, false, true );
		} else {
			if ( $group->get( 'id' ) ) {
				$url				=	array( 'groups', 'show', (int) $category->get( 'id' ), (int) $group->get( 'id' ) );
			} elseif ( $category->get( 'id' ) ) {
				$url				=	array( 'categories', 'show', (int) $category->get( 'id' ) );
			} else {
				$url				=	array( 'overview' );
			}

			cbgjClass::getPluginURL( $url, CBTxt::T( 'Not authorized.' ), false, true, 'error' );
		}
	}

	private function deleteEvent( $catid, $grpid, $id, $user, $plugin ) {
		$category				=	cbgjData::getCategories( array( array( 'cat_access', 'mod_lvl1' ), $user ), array( 'id', '=', (int) $catid ), null, null, false );
		$group					=	cbgjData::getGroups( array( array( 'grp_access', 'mod_lvl2' ), $user ), array( 'id', '=', (int) $grpid ), null, null, false );
		$row					=	cbgjEventsData::getEvents( array( array( 'mod_lvl4', 'usr_me' ), $user ), array( 'id', '=', (int) $id ), null, null, false );

		if ( ( ! $group->get( 'id' ) ) && $row->get( 'id' ) ) {
			$group				=	$row->getGroup();
		}

		if ( ( ! $category->get( 'id' ) ) && $group->get( 'id' ) ) {
			$category			=	$group->getCategory();
		}

		if ( $row->get( 'id' ) ) {
			$published			=	$row->get( 'published' );

			if ( $published ) {
				$notifications	=	cbgjData::getNotifications( array( array( 'events_notifications' ), 'owner' ), array( array( 'type', '=', 'group' ), array( 'item', '=', (int) $group->get( 'id' ) ), array( 'user_id', '!IN', array( (int) $user->id, (int) $row->get( 'user_id' ) ) ), array( 'params', 'CONTAINS', 'group_eventdelete=1' ) ) );
			} else {
				$notifications	=	null;
			}

			if ( ! $row->delete() ) {
				cbgjClass::getPluginURL( array( 'groups', 'show', (int) $category->get( 'id' ), (int) $group->get( 'id' ) ), CBTxt::P( 'Event failed to delete! Error: [error]', array( '[error]' => $row->getError() ) ), false, true, 'error' );
			}

			if ( $notifications ) {
				$subject		=	CBTxt::P( '[group_name] - Event Deleted!', $row->getSubstitutionExtras( true ) );
				$message		=	CBTxt::P( '[user] deleted [event_title] in [group]!', $row->getSubstitutionExtras( true ) );

				foreach ( $notifications as $notification ) {
					cbgjClass::getNotification( $notification->get( 'user_id' ), $row->get( 'user_id' ), $subject, $message, 1, $category, $group );
				}
			}

			cbgjClass::getPluginURL( array( 'groups', 'show', (int) $category->get( 'id' ), (int) $group->get( 'id' ) ), CBTxt::T( 'Event deleted successfully!' ), false, true, null, false, false, true );
		} else {
			if ( $group->get( 'id' ) ) {
				$url			=	array( 'groups', 'show', (int) $category->get( 'id' ), (int) $group->get( 'id' ) );
			} elseif ( $category->get( 'id' ) ) {
				$url			=	array( 'categories', 'show', (int) $category->get( 'id' ) );
			} else {
				$url			=	array( 'overview' );
			}

			cbgjClass::getPluginURL( $url, CBTxt::T( 'Not authorized.' ), false, true, 'error' );
		}
	}

	public function deleteEvents( $group, $category, $user, $plugin ) {
		$rows	=	cbgjEventsData::getEvents( null, array( 'group', '=', (int) $group->get( 'id' ) ) );

		if ( $rows ) foreach ( $rows as $row ) {
			$row->delete();
		}
	}

	public function getParam( $tabs, $group, $category, $user, $plugin ) {
		global $_CB_framework;

		$authorized					=	cbgjClass::getAuthorization( $category, $group, $user );
		$params						=	$group->getParams();
		$eventsShow					=	$plugin->params->get( 'events_show_config', 1 );
		$eventsPublic				=	$plugin->params->get( 'events_public_config', 1 );
		$eventsSchedule				=	$plugin->params->get( 'events_schedule_config', 1 );
		$eventsApprove				=	$plugin->params->get( 'events_approve_config', 1 );

		$input						=	array();

		$input['events_show']		=	moscomprofilerHTML::yesnoSelectList( 'events_show', 'class="input-small"', (int) cbgjClass::getCleanParam( ( $eventsShow || cbgjClass::hasAccess( 'usr_mod', $authorized ) ), 'events_show', $params->get( 'events_show', $plugin->params->get( 'events_show', 1 ) ) ), CBTxt::T( 'Enable' ), CBTxt::T( 'Disable' ) );
		$input['events_public']		=	moscomprofilerHTML::yesnoSelectList( 'events_public', 'class="input-small"', (int) cbgjClass::getCleanParam( ( $eventsPublic || cbgjClass::hasAccess( 'usr_mod', $authorized ) ), 'events_public', $params->get( 'events_public', $plugin->params->get( 'events_public', 1 ) ) ) );

		$listSchedule				=	array();
		$listSchedule[]				=	moscomprofilerHTML::makeOption( '0', cbgjClass::getOverride( 'group' ) . '&nbsp;' . cbgjClass::getOverride( 'user', true ) );
		$listSchedule[]				=	moscomprofilerHTML::makeOption( '1', '.&nbsp;-&nbsp;' . cbgjClass::getOverride( 'group' ) . '&nbsp;' . cbgjClass::getOverride( 'moderator', true ) );
		$listSchedule[]				=	moscomprofilerHTML::makeOption( '2', '.&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;-&nbsp;' . cbgjClass::getOverride( 'group' ) . '&nbsp;' . cbgjClass::getOverride( 'admin', true ) );
		$listSchedule[]				=	moscomprofilerHTML::makeOption( '3', '.&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;-&nbsp;' . cbgjClass::getOverride( 'group' ) . '&nbsp;' . cbgjClass::getOverride( 'owner' ) );
		$input['events_schedule']	=	moscomprofilerHTML::selectList( $listSchedule, 'events_schedule', 'class="input-large"', 'value', 'text', (int) cbgjClass::getCleanParam( ( $eventsSchedule || cbgjClass::hasAccess( 'usr_mod', $authorized ) ), 'events_schedule', $params->get( 'events_schedule', $plugin->params->get( 'events_schedule', 0 ) ) ), 1, false, false );

		$input['events_approve']	=	moscomprofilerHTML::yesnoSelectList( 'events_approve', 'class="input-small"', (int) cbgjClass::getCleanParam( ( $eventsApprove || cbgjClass::hasAccess( 'usr_mod', $authorized ) ), 'events_approve', $params->get( 'events_approve', $plugin->params->get( 'events_approve', 0 ) ) ), CBTxt::T( 'Enable' ), CBTxt::T( 'Disable' ) );

		if ( $_CB_framework->getUi() == 2 ) {
			$return					=	$tabs->startTab( null, htmlspecialchars( CBTxt::T( 'Events' ) ), 'gjIntegrationsEvents' )
									.		'<table class="adminlist" width="100%" cellspacing="0" cellpadding="4" border="0">'
									.			'<tbody>'
									.				'<tr>'
									.					'<th width="15%">' . CBTxt::Th( 'Display' ) . '</div>'
									.					'<td width="40%">' . $input['events_show'] . '</div>'
									.					'<td>' . CBTxt::Th( 'Select usage of group events.' ) . '</div>'
									.				'</tr>'
									.				'<tr>'
									.					'<th width="15%">' . CBTxt::Th( 'Public' ) . '</div>'
									.					'<td width="40%">' . $input['events_public'] . '</div>'
									.					'<td>' . CBTxt::Th( 'Select if group events tab is publicly visible.' ) . '</div>'
									.				'</tr>'
									.				'<tr>'
									.					'<th width="15%">' . CBTxt::Th( 'Schedule' ) . '</div>'
									.					'<td width="40%">' . $input['events_schedule'] . '</div>'
									.					'<td>' . CBTxt::Th( 'Select group schedule access. Schedule access determines what type of users can schedule group events (e.g. Users signify only those a member of your group can schedule). The users above the selected will also have access.' ) . '</div>'
									.				'</tr>'
									.				'<tr>'
									.					'<th width="15%">' . CBTxt::Th( 'Approve' ) . '</div>'
									.					'<td width="40%">' . $input['events_approve'] . '</div>'
									.					'<td>' . CBTxt::Th( 'Enable or disable approval of newly scheduled group events. Events will require approval by a group moderator, admin, or owner to be published. Group moderators, admins, and owner are exempt from this configuration.' ) . '</div>'
									.				'</tr>'
									.			'</tbody>'
									.		'</table>'
									.	$tabs->endTab();
		} else {
			if ( ( ! $eventsShow ) && ( ! $eventsPublic ) && ( ! $eventsSchedule ) && ( ! $eventsApprove ) && ( ! cbgjClass::hasAccess( 'usr_mod', $authorized ) ) ) {
				return;
			}

			cbgjClass::getTemplate( 'cbgroupjiveevents_params' );

			if ( class_exists( 'HTML_cbgroupjiveeventsParams' ) ) {
				$return				=	HTML_cbgroupjiveeventsParams::showEventsParams( $input, $group, $category, $user, $plugin );
			} else {
				$return				=	$tabs->startTab( null, htmlspecialchars( CBTxt::T( 'Events' ) ), 'gjIntegrationsEvents' );

				if ( $eventsShow || cbgjClass::hasAccess( 'usr_mod', $authorized ) ) {
					$return			.=		'<div class="gjEditContentInput control-group">'
									.			'<label class="gjEditContentInputTitle control-label">' . CBTxt::Th( 'Display' ) . '</label>'
									.			'<div class="gjEditContentInputField controls">'
									.				$input['events_show']
									.				'<span class="gjEditContentInputIcon help-inline">'
									.					cbgjClass::getIcon( CBTxt::P( 'Select usage of [group] events.', array( '[group]' => cbgjClass::getOverride( 'group' ) ) ) )
									.				'</span>'
									.			'</div>'
									.		'</div>';
				}

				if ( $eventsPublic || cbgjClass::hasAccess( 'usr_mod', $authorized ) ) {
					$return			.=		'<div class="gjEditContentInput control-group">'
									.			'<label class="gjEditContentInputTitle control-label">' . CBTxt::Th( 'Public' ) . '</label>'
									.			'<div class="gjEditContentInputField controls">'
									.				$input['events_public']
									.				'<span class="gjEditContentInputIcon help-inline">'
									.					cbgjClass::getIcon( CBTxt::P( 'Select if [group] events tab is publicly visible.', array( '[group]' => cbgjClass::getOverride( 'group' ) ) ) )
									.				'</span>'
									.			'</div>'
									.		'</div>';
				}

				if ( $eventsSchedule || cbgjClass::hasAccess( 'usr_mod', $authorized ) ) {
					$return			.=		'<div class="gjEditContentInput control-group">'
									.			'<label class="gjEditContentInputTitle control-label">' . CBTxt::Th( 'Schedule' ) . '</label>'
									.			'<div class="gjEditContentInputField controls">'
									.				$input['events_schedule']
									.				'<span class="gjEditContentInputIcon help-inline">'
									.					cbgjClass::getIcon( CBTxt::P( 'Select [group] schedule access. Schedule access determines what type of [users] can schedule [group] events (e.g. [users] signify only those a member of your [group] can schedule). The [users] above the selected will also have access.', array( '[group]' => cbgjClass::getOverride( 'group' ), '[users]' => cbgjClass::getOverride( 'user', true ) ) ) )
									.				'</span>'
									.			'</div>'
									.		'</div>';
				}

				if ( $eventsApprove || cbgjClass::hasAccess( 'usr_mod', $authorized ) ) {
					$return			.=		'<div class="gjEditContentInput control-group">'
									.			'<label class="gjEditContentInputTitle control-label">' . CBTxt::Th( 'Approve' ) . '</label>'
									.			'<div class="gjEditContentInputField controls">'
									.				$input['events_approve']
									.				'<span class="gjEditContentInputIcon help-inline">'
									.					cbgjClass::getIcon( CBTxt::P( 'Enable or disable approval of newly scheduled [group] events. Events will require approval by a [group] [mod], [admin], or [owner] to be published. [group] [mods], [admins], and [owner] are exempt from this configuration.', array( '[group]' => cbgjClass::getOverride( 'group' ), '[mods]' => cbgjClass::getOverride( 'mod', true ), '[admins]' => cbgjClass::getOverride( 'admin', true ), '[mod]' => cbgjClass::getOverride( 'mod' ), '[admin]' => cbgjClass::getOverride( 'admin' ), '[owner]' => cbgjClass::getOverride( 'owner' ) ) ) )
									.				'</span>'
									.			'</div>'
									.		'</div>';
				}

				$return				.=	$tabs->endTab();
			}
		}

		return $return;
	}

	public function setParam( $group, $category, $user, $plugin ) {
		$authorized	=	cbgjClass::getAuthorization( $category, $group, $user );
		$params		=	$group->getParams();

		$params->set( 'events_show', (int) cbgjClass::getCleanParam( ( $plugin->params->get( 'events_show_config', 1 ) || cbgjClass::hasAccess( 'usr_mod', $authorized ) ), 'events_show', $params->get( 'events_show', $plugin->params->get( 'events_show', 1 ) ) ) );
		$params->set( 'events_public', (int) cbgjClass::getCleanParam( ( $plugin->params->get( 'events_public_config', 1 ) || cbgjClass::hasAccess( 'usr_mod', $authorized ) ), 'events_public', $params->get( 'events_public', $plugin->params->get( 'events_public', 1 ) ) ) );
		$params->set( 'events_schedule', (int) cbgjClass::getCleanParam( ( $plugin->params->get( 'events_schedule_config', 1 ) || cbgjClass::hasAccess( 'usr_mod', $authorized ) ), 'events_schedule', $params->get( 'events_schedule', $plugin->params->get( 'events_schedule', 0 ) ) ) );
		$params->set( 'events_approve', (int) cbgjClass::getCleanParam( ( $plugin->params->get( 'events_approve_config', 1 ) || cbgjClass::hasAccess( 'usr_mod', $authorized ) ), 'events_approve', $params->get( 'events_approve', $plugin->params->get( 'events_approve', 0 ) ) ) );

		$group->storeParams( $params );
	}

	public function getConfig( $tabs, $user, $plugin ) {
		$input												=	array();

		$input['events_message']							=	moscomprofilerHTML::yesnoSelectList( 'events_message', null, $plugin->params->get( 'events_message', 1 ), CBTxt::T( 'Enable' ), CBTxt::T( 'Disable' ) );
		$input['events_plotting']							=	moscomprofilerHTML::yesnoSelectList( 'events_plotting', null, $plugin->params->get( 'events_plotting', 1 ), CBTxt::T( 'Enable' ), CBTxt::T( 'Disable' ) );
		$input['events_delete']								=	moscomprofilerHTML::yesnoSelectList( 'events_delete', null, $plugin->params->get( 'events_delete', 0 ), CBTxt::T( 'Enable' ), CBTxt::T( 'Disable' ) );
		$input['events_notifications']						=	moscomprofilerHTML::yesnoSelectList( 'events_notifications', null, $plugin->params->get( 'events_notifications', 1 ), CBTxt::T( 'Enable' ), CBTxt::T( 'Disable' ) );
		$input['events_notifications_group_eventnew']		=	moscomprofilerHTML::yesnoSelectList( 'events_notifications_group_eventnew', null, $plugin->params->get( 'events_notifications_group_eventnew', 0 ), CBTxt::T( 'Notify' ), CBTxt::T( 'Don\'t Notify' ) );
		$input['events_notifications_group_eventapprove']	=	moscomprofilerHTML::yesnoSelectList( 'events_notifications_group_eventapprove', null, $plugin->params->get( 'events_notifications_group_eventapprove', 0 ), CBTxt::T( 'Notify' ), CBTxt::T( 'Don\'t Notify' ) );
		$input['events_notifications_group_eventyes']		=	moscomprofilerHTML::yesnoSelectList( 'events_notifications_group_eventyes', null, $plugin->params->get( 'events_notifications_group_eventyes', 0 ), CBTxt::T( 'Notify' ), CBTxt::T( 'Don\'t Notify' ) );
		$input['events_notifications_group_eventmaybe']		=	moscomprofilerHTML::yesnoSelectList( 'events_notifications_group_eventmaybe', null, $plugin->params->get( 'events_notifications_group_eventmaybe', 0 ), CBTxt::T( 'Notify' ), CBTxt::T( 'Don\'t Notify' ) );
		$input['events_notifications_group_eventno']		=	moscomprofilerHTML::yesnoSelectList( 'events_notifications_group_eventno', null, $plugin->params->get( 'events_notifications_group_eventno', 0 ), CBTxt::T( 'Notify' ), CBTxt::T( 'Don\'t Notify' ) );
		$input['events_notifications_group_eventupdate']	=	moscomprofilerHTML::yesnoSelectList( 'events_notifications_group_eventupdate', null, $plugin->params->get( 'events_notifications_group_eventupdate', 0 ), CBTxt::T( 'Notify' ), CBTxt::T( 'Don\'t Notify' ) );
		$input['events_notifications_group_eventdelete']	=	moscomprofilerHTML::yesnoSelectList( 'events_notifications_group_eventdelete', null, $plugin->params->get( 'events_notifications_group_eventdelete', 0 ), CBTxt::T( 'Notify' ), CBTxt::T( 'Don\'t Notify' ) );

		$listEditor											=	array();
		$listEditor[]										=	moscomprofilerHTML::makeOption( '1', CBTxt::T( 'Plain Text' ) );
		$listEditor[]										=	moscomprofilerHTML::makeOption( '2', CBTxt::T( 'HTML Text' ) );
		$listEditor[]										=	moscomprofilerHTML::makeOption( '3', CBTxt::T( 'WYSIWYG' ) );
		$input['events_editor']								=	moscomprofilerHTML::selectList( $listEditor, 'events_editor', null, 'value', 'text', $plugin->params->get( 'events_editor', 1 ), 1, false, false );

		$input['events_event_content']						=	moscomprofilerHTML::yesnoSelectList( 'events_event_content', null, $plugin->params->get( 'events_event_content', 0 ), CBTxt::T( 'Enable' ), CBTxt::T( 'Disable' ) );
		$input['events_24hr']								=	moscomprofilerHTML::yesnoSelectList( 'events_24hr', null, $plugin->params->get( 'events_24hr', 0 ), CBTxt::T( 'Enable' ), CBTxt::T( 'Disable' ) );
		$input['events_captcha']							=	moscomprofilerHTML::yesnoSelectList( 'events_captcha', null, $plugin->params->get( 'events_captcha', 0 ), CBTxt::T( 'Enable' ), CBTxt::T( 'Disable' ) );
		$input['events_paging']								=	moscomprofilerHTML::yesnoSelectList( 'events_paging', null, $plugin->params->get( 'events_paging', 1 ), CBTxt::T( 'Enable' ), CBTxt::T( 'Disable' ) );
		$input['events_limitbox']							=	moscomprofilerHTML::yesnoSelectList( 'events_limitbox', null, $plugin->params->get( 'events_limitbox', 1 ), CBTxt::T( 'Enable' ), CBTxt::T( 'Disable' ) );
		$input['events_limit']								=	'<input type="text" id="events_limit" name="events_limit" value="' . (int) $plugin->params->get( 'events_limit', 15 ) . '" class="inputbox" size="5" />';
		$input['events_search']								=	moscomprofilerHTML::yesnoSelectList( 'events_search', null, $plugin->params->get( 'events_search', 1 ), CBTxt::T( 'Enable' ), CBTxt::T( 'Disable' ) );
		$input['events_approval_paging']					=	moscomprofilerHTML::yesnoSelectList( 'events_approval_paging', null, $plugin->params->get( 'events_approval_paging', 1 ), CBTxt::T( 'Enable' ), CBTxt::T( 'Disable' ) );
		$input['events_approval_limitbox']					=	moscomprofilerHTML::yesnoSelectList( 'events_approval_limitbox', null, $plugin->params->get( 'events_approval_limitbox', 1 ), CBTxt::T( 'Enable' ), CBTxt::T( 'Disable' ) );
		$input['events_approval_limit']						=	'<input type="text" id="events_approval_limit" name="events_approval_limit" value="' . (int) $plugin->params->get( 'events_approval_limit', 15 ) . '" class="inputbox" size="5" />';
		$input['events_approval_search']					=	moscomprofilerHTML::yesnoSelectList( 'events_approval_search', null, $plugin->params->get( 'events_approval_search', 1 ), CBTxt::T( 'Enable' ), CBTxt::T( 'Disable' ) );
		$input['events_show']								=	moscomprofilerHTML::yesnoSelectList( 'events_show', null, $plugin->params->get( 'events_show', 1 ), CBTxt::T( 'Enable' ), CBTxt::T( 'Disable' ) );
		$input['events_show_config']						=	moscomprofilerHTML::yesnoSelectList( 'events_show_config', null, $plugin->params->get( 'events_show_config', 1 ), CBTxt::T( 'Show' ), CBTxt::T( 'Hide' ) );
		$input['events_public']								=	moscomprofilerHTML::yesnoSelectList( 'events_public', null, $plugin->params->get( 'events_public', 1 ) );
		$input['events_public_config']						=	moscomprofilerHTML::yesnoSelectList( 'events_public_config', null, $plugin->params->get( 'events_public_config', 1 ), CBTxt::T( 'Show' ), CBTxt::T( 'Hide' ) );

		$listUpload											=	array();
		$listUpload[]										=	moscomprofilerHTML::makeOption( '0', cbgjClass::getOverride( 'group' ) . '&nbsp;' . cbgjClass::getOverride( 'user', true ) );
		$listUpload[]										=	moscomprofilerHTML::makeOption( '1', '.&nbsp;-&nbsp;' . cbgjClass::getOverride( 'group' ) . '&nbsp;' . cbgjClass::getOverride( 'moderator', true ) );
		$listUpload[]										=	moscomprofilerHTML::makeOption( '2', '.&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;-&nbsp;' . cbgjClass::getOverride( 'group' ) . '&nbsp;' . cbgjClass::getOverride( 'admin', true ) );
		$listUpload[]										=	moscomprofilerHTML::makeOption( '3', '.&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;-&nbsp;' . cbgjClass::getOverride( 'group' ) . '&nbsp;' . cbgjClass::getOverride( 'owner' ) );
		$input['events_schedule']							=	moscomprofilerHTML::selectList( $listUpload, 'events_schedule', 'class="inputbox"', 'value', 'text', $plugin->params->get( 'events_schedule', 0 ), 1, false, false );

		$input['events_schedule_config']					=	moscomprofilerHTML::yesnoSelectList( 'events_schedule_config', null, $plugin->params->get( 'events_schedule_config', 1 ), CBTxt::T( 'Show' ), CBTxt::T( 'Hide' ) );
		$input['events_approve']							=	moscomprofilerHTML::yesnoSelectList( 'events_approve', null, $plugin->params->get( 'events_approve', 0 ), CBTxt::T( 'Enable' ), CBTxt::T( 'Disable' ) );
		$input['events_approve_config']						=	moscomprofilerHTML::yesnoSelectList( 'events_approve_config', null, $plugin->params->get( 'events_approve_config', 1 ), CBTxt::T( 'Show' ), CBTxt::T( 'Hide' ) );

		$return												=	$tabs->startTab( null, htmlspecialchars( CBTxt::T( 'Events' ) ), 'gjIntegrationsEvents' )
															.		$tabs->startPane( 'gjIntegrationsEventsTabs' )
															.			$tabs->startTab( null, htmlspecialchars( CBTxt::T( 'General' ) ), 'gjIntegrationsEventsGeneral' )
															.				'<table class="adminlist" width="100%" cellspacing="0" cellpadding="4" border="0">'
															.					'<tbody>'
															.						'<tr>'
															.							'<th width="15%">' . CBTxt::Th( 'Message' ) . '</th>'
															.							'<td width="40%">' . $input['events_message'] . '</td>'
															.							'<td>' . CBTxt::Th( 'Enable or disable messaging of event attendees. Moderators, category owners, and group owners are exempt from this configuration and can always message.' ) . '</td>'
															.						'</tr>'
															.						'<tr>'
															.							'<th width="15%">' . CBTxt::Th( 'Plotting' ) . '</th>'
															.							'<td width="40%">' . $input['events_plotting'] . '</td>'
															.							'<td>' . CBTxt::Th( 'Enable or disable plotting of event locations on google maps. Plotting is optional on an event by event basis. Plotting controls access to and display of the address field.' ) . '</td>'
															.						'</tr>'
															.						'<tr>'
															.							'<th width="15%">' . CBTxt::Th( 'Auto Delete' ) . '</th>'
															.							'<td width="40%">' . $input['events_delete'] . '</td>'
															.							'<td>' . CBTxt::Th( 'Enable or disable deletion of user events on group leave.' ) . '</td>'
															.						'</tr>'
															.					'</tbody>'
															.				'</table>'
															.			$tabs->endTab()
															.			$tabs->startTab( null, htmlspecialchars( CBTxt::T( 'Notifications' ) ), 'gjIntegrationsEventsNotifications' )
															.				$tabs->startPane( 'gjIntegrationsEventsNotificationsTabs' )
															.					$tabs->startTab( null, htmlspecialchars( CBTxt::T( 'General' ) ), 'gjIntegrationsEventsNotificationsGeneral' )
															.						'<table class="adminlist" width="100%" cellspacing="0" cellpadding="4" border="0">'
															.							'<tbody>'
															.								'<tr>'
															.									'<th width="15%">' . CBTxt::Th( 'Notifications' ) . '</th>'
															.									'<td width="40%">' . $input['events_notifications'] . '</td>'
															.									'<td>' . CBTxt::Th( 'Enable or disable sending and configuration of events notifications. Moderators are exempt from this configuration.' ) . '</td>'
															.								'</tr>'
															.							'</tbody>'
															.						'</table>'
															.					$tabs->endTab()
															.					$tabs->startTab( null, htmlspecialchars( CBTxt::T( 'Defaults' ) ), 'gjIntegrationsEventsNotificationsDefault' )
															.						'<table class="adminlist" width="100%" cellspacing="0" cellpadding="4" border="0">'
															.							'<tbody>'
															.								'<tr>'
															.									'<th width="15%">' . CBTxt::Th( 'Schedule Event' ) . '</th>'
															.									'<td width="40%">' . $input['events_notifications_group_eventnew'] . '</td>'
															.									'<td>' . CBTxt::Th( 'Select default value for events notification parameter "Schedule of new event".' ) . '</td>'
															.								'</tr>'
															.								'<tr>'
															.									'<th width="15%">' . CBTxt::Th( 'Event Approval' ) . '</th>'
															.									'<td width="40%">' . $input['events_notifications_group_eventapprove'] . '</td>'
															.									'<td>' . CBTxt::Th( 'Select default value for events notification parameter "New event requires approval".' ) . '</td>'
															.								'</tr>'
															.								'<tr>'
															.									'<th width="15%">' . CBTxt::Th( 'User Attend' ) . '</th>'
															.									'<td width="40%">' . $input['events_notifications_group_eventyes'] . '</td>'
															.									'<td>' . CBTxt::Th( 'Select default value for events notification parameter "User "Attend" to my existing events".' ) . '</td>'
															.								'</tr>'
															.								'<tr>'
															.									'<th width="15%">' . CBTxt::Th( 'User Maybe Attend' ) . '</th>'
															.									'<td width="40%">' . $input['events_notifications_group_eventmaybe'] . '</td>'
															.									'<td>' . CBTxt::Th( 'Select default value for events notification parameter "User "Maybe Attend" to my existing events".' ) . '</td>'
															.								'</tr>'
															.								'<tr>'
															.									'<th width="15%">' . CBTxt::Th( 'User Not Attending' ) . '</th>'
															.									'<td width="40%">' . $input['events_notifications_group_eventno'] . '</td>'
															.									'<td>' . CBTxt::Th( 'Select default value for events notification parameter "User "Not Attending" to my existing events".' ) . '</td>'
															.								'</tr>'
															.								'<tr>'
															.									'<th width="15%">' . CBTxt::Th( 'Update Event' ) . '</th>'
															.									'<td width="40%">' . $input['events_notifications_group_eventupdate'] . '</td>'
															.									'<td>' . CBTxt::Th( 'Select default value for events notification parameter "Update of existing event".' ) . '</td>'
															.								'</tr>'
															.								'<tr>'
															.									'<th width="15%">' . CBTxt::Th( 'Delete Event' ) . '</th>'
															.									'<td width="40%">' . $input['events_notifications_group_eventdelete'] . '</td>'
															.									'<td>' . CBTxt::Th( 'Select default value for events notification parameter "Delete of existing event".' ) . '</td>'
															.								'</tr>'
															.							'</tbody>'
															.						'</table>'
															.					$tabs->endTab()
															.				$tabs->endPane()
															.			$tabs->endTab()
															.			$tabs->startTab( null, htmlspecialchars( CBTxt::T( 'Schedule' ) ), 'gjIntegrationsEventsSchedule' )
															.				'<table class="adminlist" width="100%" cellspacing="0" cellpadding="4" border="0">'
															.					'<tbody>'
															.						'<tr>'
															.							'<th width="15%">' . CBTxt::Th( 'Editor' ) . '</th>'
															.							'<td width="40%">' . $input['events_editor'] . '</td>'
															.							'<td>' . CBTxt::Th( 'Select method for events editing.' ) . '</td>'
															.						'</tr>'
															.						'<tr>'
															.							'<th width="15%">' . CBTxt::Th( 'Content Plugins' ) . '</th>'
															.							'<td width="40%">' . $input['events_event_content'] . '</td>'
															.							'<td>' . CBTxt::Th( 'Enable or disable processing of content plugins on events.' ) . '</td>'
															.						'</tr>'
															.						'<tr>'
															.							'<th width="15%">' . CBTxt::Th( '24 Hour' ) . '</th>'
															.							'<td width="40%">' . $input['events_24hr'] . '</td>'
															.							'<td>' . CBTxt::Th( 'Enable or disable usage of 24 hour display instead of 12 Hour with AM/PM.' ) . '</td>'
															.						'</tr>'
															.						'<tr>'
															.							'<th width="15%">' . CBTxt::Th( 'Captcha' ) . '</th>'
															.							'<td width="40%">' . $input['events_captcha'] . '</td>'
															.							'<td>' . CBTxt::Th( 'Enable or disable usage of captcha on events. Requires latest CB Captcha or integrated captcha to be installed and published. Moderators are exempt from this configuration.' ) . '</td>'
															.						'</tr>'
															.					'</tbody>'
															.				'</table>'
															.			$tabs->endTab()
															.			$tabs->startTab( null, htmlspecialchars( CBTxt::T( 'Paging' ) ), 'gjIntegrationsEventsPaging' )
															.				'<table class="adminlist" width="100%" cellspacing="0" cellpadding="4" border="0">'
															.					'<tbody>'
															.						'<tr>'
															.							'<th width="15%">' . CBTxt::Th( 'Paging' ) . '</th>'
															.							'<td width="40%">' . $input['events_paging'] . '</td>'
															.							'<td>' . CBTxt::Th( 'Enable or disable usage of paging on events.' ) . '</td>'
															.						'</tr>'
															.						'<tr>'
															.							'<th width="15%">' . CBTxt::T( 'Page Limitbox' ) . '</th>'
															.							'<td width="50%">' . $input['events_limitbox'] . '</td>'
															.							'<td>' . CBTxt::T( 'Enable or disable usage of page limit on events. Requires Paging to be Enabled.' ) . '</td>'
															.						'</tr>'
															.						'<tr>'
															.							'<th width="15%">' . CBTxt::T( 'Page Limit' ) . '</th>'
															.							'<td width="50%">' . $input['events_limit'] . '</td>'
															.							'<td>' . CBTxt::T( 'Input default page limit on events. Page limit determines how many events are displayed per page.' ) . '</td>'
															.						'</tr>'
															.						'<tr>'
															.							'<th width="15%">' . CBTxt::Th( 'Search' ) . '</th>'
															.							'<td width="600px">' . $input['events_search'] . '</td>'
															.							'<td>' . CBTxt::Th( 'Enable or disable usage of searching on events.' ) . '</td>'
															.						'</tr>'
															.					'</tbody>'
															.				'</table>'
															.			$tabs->endTab()
															.			$tabs->startTab( null, htmlspecialchars( CBTxt::T( 'Approval' ) ), 'gjIntegrationsEventsApproval' )
															.				'<table class="adminlist" width="100%" cellspacing="0" cellpadding="4" border="0">'
															.					'<tbody>'
															.						'<tr>'
															.							'<th width="15%">' . CBTxt::Th( 'Paging' ) . '</th>'
															.							'<td width="40%">' . $input['events_approval_paging'] . '</td>'
															.							'<td>' . CBTxt::Th( 'Enable or disable usage of paging on events.' ) . '</td>'
															.						'</tr>'
															.						'<tr>'
															.							'<th width="15%">' . CBTxt::T( 'Page Limitbox' ) . '</th>'
															.							'<td width="50%">' . $input['events_approval_limitbox'] . '</td>'
															.							'<td>' . CBTxt::T( 'Enable or disable usage of page limit on events. Requires Paging to be Enabled.' ) . '</td>'
															.						'</tr>'
															.						'<tr>'
															.							'<th width="15%">' . CBTxt::T( 'Page Limit' ) . '</th>'
															.							'<td width="50%">' . $input['events_approval_limit'] . '</td>'
															.							'<td>' . CBTxt::T( 'Input default page limit on events. Page limit determines how many events are displayed per page.' ) . '</td>'
															.						'</tr>'
															.						'<tr>'
															.							'<th width="15%">' . CBTxt::Th( 'Search' ) . '</th>'
															.							'<td width="600px">' . $input['events_approval_search'] . '</td>'
															.							'<td>' . CBTxt::Th( 'Enable or disable usage of searching on events.' ) . '</td>'
															.						'</tr>'
															.					'</tbody>'
															.				'</table>'
															.			$tabs->endTab()
															.			$tabs->startTab( null, htmlspecialchars( CBTxt::T( 'Defaults' ) ), 'gjIntegrationsEventsDefaults' )
															.				'<table class="adminlist" width="100%" cellspacing="0" cellpadding="4" border="0">'
															.					'<tbody>'
															.						'<tr>'
															.							'<th width="15%">' . CBTxt::Th( 'Display' ) . '</th>'
															.							'<td width="40%">' . $input['events_show'] . ' ' . $input['events_show_config'] . '</td>'
															.							'<td>' . CBTxt::Th( 'Select default value for group parameter "Display". Additionally select the display of the "Display" group parameter. Moderators are exempt from this configuration.' ) . '</td>'
															.						'</tr>'
															.						'<tr>'
															.							'<th width="15%">' . CBTxt::Th( 'Public' ) . '</th>'
															.							'<td width="40%">' . $input['events_public'] . ' ' . $input['events_public_config'] . '</td>'
															.							'<td>' . CBTxt::Th( 'Select default value for group parameter "Public". Additionally select the display of the "Public" group parameter. Moderators are exempt from this configuration.' ) . '</td>'
															.						'</tr>'
															.						'<tr>'
															.							'<th width="15%">' . CBTxt::Th( 'Schedule' ) . '</th>'
															.							'<td width="600px">' . $input['events_schedule'] . ' ' . $input['events_schedule_config'] . '</td>'
															.							'<td>' . CBTxt::Th( 'Select default value for group parameter "Schedule". Additionally select the display of the "Schedule" group parameter. Moderators are exempt from this configuration.' ) . '</td>'
															.						'</tr>'
															.						'<tr>'
															.							'<th width="15%">' . CBTxt::Th( 'Approve' ) . '</th>'
															.							'<td width="600px">' . $input['events_approve'] . ' ' . $input['events_approve_config'] . '</td>'
															.							'<td>' . CBTxt::Th( 'Select default value for group parameter "Approve". Additionally select the display of the "Approve" group parameter. Moderators are exempt from this configuration.' ) . '</td>'
															.						'</tr>'
															.					'</tbody>'
															.				'</table>'
															.			$tabs->endTab()
															.		$tabs->endPane()
															.	$tabs->endTab();

		return $return;
	}

	public function leaveGroup( $row, $group, $category, $user, $plugin ) {
		$this->deleteUserEvents( $user, $group );
	}

	public function deleteUser( $user, $deleted ) {
		$this->deleteUserEvents( $user );
	}

	private function deleteUserEvents( $user, $group = null ) {
		$plugin				=	cbgjClass::getPlugin();

		if ( $plugin->params->get( 'events_delete', 0 ) ) {
			$where			=	array();

			if ( $group ) {
				$where[]	=	array( 'group', '=', (int) $group->get( 'id' ) );
			}

			$where[]		=	array( 'user_id', '=', (int) $user->id );

			$rows			=	cbgjEventsData::getEvents( null, $where );

			if ( $rows ) foreach ( $rows as $row ) {
				$row->delete();
			}

			$where			=	array_pop( $where );

			$where[]		=	array( 'attending', 'CONTAINS', (int) $user->id );

			$rows			=	cbgjEventsData::getEvents( null, $where );

			if ( $rows ) foreach ( $rows as $row ) {
				$row->storeAttendance( -1, $user );
			}
		}
	}

	private function getCalendar( $row, $plugin ) {
		global $_CB_framework;

		static $cache			=	null;

		if ( ! isset( $cache ) ) {
			$events24hr			=	$plugin->params->get( 'events_24hr', 0 );

			if ( $row ) {
				$defaultDate	=	cbgjClass::getCleanParam( true, 'events_date', $row->get( 'date' ) );
				$defaultTime	=	cbgjClass::getCleanParam( true, 'events_time', $row->get( 'date' ) );
				$minimumYear	=	cbgjClass::getUTCDate( 'Y', $row->get( 'date' ) );
			} else {
				$defaultDate	=	cbgjClass::getCleanParam( true, 'events_date' );
				$defaultTime	=	cbgjClass::getCleanParam( true, 'events_time' );
				$minimumYear	=	cbgjClass::getUTCDate( 'Y' );
			}

			$currentDate		=	( $defaultDate ? cbgjClass::getUTCDate( 'Y-m-d', $defaultDate ) : null );
			$currentTime		=	( $defaultTime ? cbgjClass::getUTCDate( ( $events24hr ? 'H:i' : 'h:i A' ), $defaultTime ) : null );

			$calendars			=	new cbCalendars( 1 );

			$date				=	str_replace( 'class="inputbox', 'class="input-medium', $calendars->cbAddCalendar( 'events_date', null, 1, $currentDate, false, false, $minimumYear, ( cbgjClass::getUTCDate( 'Y' ) + 15 ) ) );
			$time				=	'<input type="text" size="15" class="input-small required" value="' . htmlspecialchars( $currentTime ) . '" name="events_time" id="events_time" />';

			$_CB_framework->document->addHeadStyleSheet( $plugin->livePath . '/plugins/cbgroupjiveevents/js/jquery-timepicker.css' );
			$_CB_framework->addJQueryPlugin( 'timepicker', $plugin->livePath . '/plugins/cbgroupjiveevents/js/jquery-timepicker.js', array( -1 => array( 'ui-all' ) ) );

			$timepickerJs		=	"$( '#events_time' ).timePicker({"
								.		( $events24hr ? "show24Hours: true" : "show24Hours: false" )
								.	"});"
								.	"$( '#events_date_Month_ID' ).removeClass( 'inputclass' ).addClass( 'input-medium' );"
								.	"$( '#events_date_Day_ID' ).removeClass( 'inputclass' ).addClass( 'input-small' );"
								.	"$( '#events_date_Year_ID' ).removeClass( 'inputclass' ).addClass( 'input-small' );";

			$_CB_framework->outputCbJQuery( $timepickerJs, 'timepicker' );

			$cache				=	array( 'date' => $date, 'time' => $time );
		}

		return $cache;
	}

	public function getAuthorization( &$access, $category, $group, $user, $owner, $row, $plugin ) {
		if ( isset( $group->id ) && cbgjClass::hasAccess( 'grp_approved', $access ) ) {
			$params					=	$group->getParams();
			$eventsShow				=	$params->get( 'events_show', $plugin->params->get( 'events_show', 1 ) );
			$eventsPublic			=	$params->get( 'events_public', $plugin->params->get( 'events_public', 1 ) );
			$eventsSchedule			=	$params->get( 'events_schedule', $plugin->params->get( 'events_schedule', 0 ) );

			if ( ( $eventsPublic || cbgjClass::hasAccess( 'mod_lvl5', $access ) ) && $eventsShow ) {
				$access[]			=	'events_show';

				if ( cbgjClass::hasAccess( 'usr_notifications', $access ) && ( $plugin->params->get( 'events_notifications', 1 ) || cbgjClass::hasAccess( 'usr_mod', $access ) ) && cbgjClass::hasAccess( 'mod_lvl5', $access ) ) {
					if ( ! cbgjClass::hasAccess( 'grp_usr_notifications', $access ) ) {
						$access[]	=	'grp_usr_notifications';
					}

					$access[]		=	'events_notifications';
				}
			}

			if ( $eventsShow && ( ( ( $eventsSchedule == 0 ) && cbgjClass::hasAccess( 'mod_lvl5', $access ) ) || ( ( $eventsSchedule == 1 ) && cbgjClass::hasAccess( 'mod_lvl4', $access ) ) || ( $eventsSchedule == 2 ) && cbgjClass::hasAccess( 'mod_lvl3', $access ) || ( $eventsSchedule == 3 ) && cbgjClass::hasAccess( 'mod_lvl2', $access ) ) ) {
				$access[]			=	'events_schedule';
			}
		}
	}

	public function getNotifications( $tabs, $row, $group, $category, $user, $plugin ) {
		$authorized							=	cbgjClass::getAuthorization( $category, $group, $user );

		if ( cbgjClass::hasAccess( 'events_notifications', $authorized ) ) {
			$params							=	$row->getParams();

			$input							=	array();

			$input['group_eventnew']		=	moscomprofilerHTML::yesnoSelectList( 'group_eventnew', 'class="input-medium"', ( (int) ( cbgjClass::hasAccess( array( 'mod_lvl5', 'events_show' ), $authorized, true ) ? cbgjClass::getCleanParam( true, 'group_eventnew', $params->get( 'group_eventnew', $plugin->params->get( 'events_notifications_group_eventnew', 0 ) ) ) : 0 ) ), CBTxt::T( 'Notify' ), CBTxt::T( 'Don\'t Notify' ) );
			$input['group_eventapprove']	=	moscomprofilerHTML::yesnoSelectList( 'group_eventapprove', 'class="input-medium"', ( (int) ( cbgjClass::hasAccess( array( 'mod_lvl4', 'events_show' ), $authorized, true ) ? cbgjClass::getCleanParam( true, 'group_eventapprove', $params->get( 'group_eventapprove', $plugin->params->get( 'events_notifications_group_eventapprove', 0 ) ) ) : 0 ) ), CBTxt::T( 'Notify' ), CBTxt::T( 'Don\'t Notify' ) );
			$input['group_eventyes']		=	moscomprofilerHTML::yesnoSelectList( 'group_eventyes', 'class="input-medium"', ( (int) ( cbgjClass::hasAccess( array( 'mod_lvl5', 'events_schedule' ), $authorized, true ) ? cbgjClass::getCleanParam( true, 'group_eventyes', $params->get( 'group_eventyes', $plugin->params->get( 'events_notifications_group_eventyes', 0 ) ) ) : 0 ) ), CBTxt::T( 'Notify' ), CBTxt::T( 'Don\'t Notify' ) );
			$input['group_eventmaybe']		=	moscomprofilerHTML::yesnoSelectList( 'group_eventmaybe', 'class="input-medium"', ( (int) ( cbgjClass::hasAccess( array( 'mod_lvl5', 'events_schedule' ), $authorized, true ) ? cbgjClass::getCleanParam( true, 'group_eventmaybe', $params->get( 'group_eventmaybe', $plugin->params->get( 'events_notifications_group_eventmaybe', 0 ) ) ) : 0 ) ), CBTxt::T( 'Notify' ), CBTxt::T( 'Don\'t Notify' ) );
			$input['group_eventno']			=	moscomprofilerHTML::yesnoSelectList( 'group_eventno', 'class="input-medium"', ( (int) ( cbgjClass::hasAccess( array( 'mod_lvl5', 'events_schedule' ), $authorized, true ) ? cbgjClass::getCleanParam( true, 'group_eventno', $params->get( 'group_eventno', $plugin->params->get( 'events_notifications_group_eventno', 0 ) ) ) : 0 ) ), CBTxt::T( 'Notify' ), CBTxt::T( 'Don\'t Notify' ) );
			$input['group_eventupdate']		=	moscomprofilerHTML::yesnoSelectList( 'group_eventupdate', 'class="input-medium"', ( (int) ( cbgjClass::hasAccess( array( 'mod_lvl5', 'events_show' ), $authorized, true ) ? cbgjClass::getCleanParam( true, 'group_eventupdate', $params->get( 'group_eventupdate', $plugin->params->get( 'events_notifications_group_eventupdate', 0 ) ) ) : 0 ) ), CBTxt::T( 'Notify' ), CBTxt::T( 'Don\'t Notify' ) );
			$input['group_eventdelete']		=	moscomprofilerHTML::yesnoSelectList( 'group_eventdelete', 'class="input-medium"', ( (int) ( cbgjClass::hasAccess( array( 'mod_lvl5', 'events_show' ), $authorized, true ) ? cbgjClass::getCleanParam( true, 'group_eventdelete', $params->get( 'group_eventdelete', $plugin->params->get( 'events_notifications_group_eventdelete', 0 ) ) ) : 0 ) ), CBTxt::T( 'Notify' ), CBTxt::T( 'Don\'t Notify' ) );

			$return							=	$tabs->startTab( null, htmlspecialchars( CBTxt::T( 'Events' ) ), 'gjNotificationsGroupEvents' )
											.		'<div class="gjEditContentInput control-group">'
											.			'<label class="gjEditContentInputTitle control-label">' . CBTxt::Th( 'Schedule of new event' ) . '</label>'
											.			'<div class="gjEditContentInputField controls">'
											.				$input['group_eventnew']
											.			'</div>'
											.		'</div>';

			if ( cbgjClass::hasAccess( array( 'mod_lvl4', 'events_show' ), $authorized, true ) ) {
				$return						.=		'<div class="gjEditContentInput control-group">'
											.			'<label class="gjEditContentInputTitle control-label">' . CBTxt::Th( 'New event requires approval' ) . '</label>'
											.			'<div class="gjEditContentInputField controls">'
											.				$input['group_eventapprove']
											.			'</div>'
											.		'</div>';
			}

			if ( cbgjClass::hasAccess( array( 'mod_lvl5', 'events_schedule' ), $authorized, true ) ) {
				$return						.=		'<div class="gjEditContentInput control-group">'
											.			'<label class="gjEditContentInputTitle control-label">' . CBTxt::Ph( '[user] "Attend" to my existing events', array( '[user]' => cbgjClass::getOverride( 'user' ) ) ) . '</label>'
											.			'<div class="gjEditContentInputField controls">'
											.				$input['group_eventyes']
											.			'</div>'
											.		'</div>'
											.		'<div class="gjEditContentInput control-group">'
											.			'<label class="gjEditContentInputTitle control-label">' . CBTxt::Ph( '[user] "Maybe Attend" to my existing events', array( '[user]' => cbgjClass::getOverride( 'user' ) ) ) . '</label>'
											.			'<div class="gjEditContentInputField controls">'
											.				$input['group_eventmaybe']
											.			'</div>'
											.		'</div>'
											.		'<div class="gjEditContentInput control-group">'
											.			'<label class="gjEditContentInputTitle control-label">' . CBTxt::Ph( '[user] "Not Attending" to my existing events', array( '[user]' => cbgjClass::getOverride( 'user' ) ) ) . '</label>'
											.			'<div class="gjEditContentInputField controls">'
											.				$input['group_eventno']
											.			'</div>'
											.		'</div>';
			}

			$return							.=		'<div class="gjEditContentInput control-group">'
											.			'<label class="gjEditContentInputTitle control-label">' . CBTxt::Th( 'Update of existing event' ) . '</label>'
											.			'<div class="gjEditContentInputField controls">'
											.				$input['group_eventupdate']
											.			'</div>'
											.		'</div>'
											.		'<div class="gjEditContentInput control-group">'
											.			'<label class="gjEditContentInputTitle control-label">' . CBTxt::Th( 'Delete of existing event' ) . '</label>'
											.			'<div class="gjEditContentInputField controls">'
											.				$input['group_eventdelete']
											.			'</div>'
											.		'</div>'
											.	$tabs->endTab();

			return $return;
		}
	}

	public function setNotifications( &$params, $row, $group, $category, $user, $plugin ) {
		if ( isset( $group->id ) ) {
			$authorized		=	cbgjClass::getAuthorization( $category, $group, $row->getOwner() );

			if ( cbgjClass::hasAccess( 'events_notifications', $authorized ) ) {
				$rowParams	=	$row->getParams();

				$params->set( 'group_eventnew', ( (int) ( cbgjClass::hasAccess( array( 'mod_lvl5', 'events_show' ), $authorized, true ) ? cbgjClass::getCleanParam( true, 'group_eventnew', $rowParams->get( 'group_eventnew', $plugin->params->get( 'events_notifications_group_eventnew', 0 ) ) ) : 0 ) ? 1 : 0 ) );
				$params->set( 'group_eventapprove', ( (int) ( cbgjClass::hasAccess( array( 'mod_lvl4', 'events_show' ), $authorized, true ) ? cbgjClass::getCleanParam( true, 'group_eventapprove', $rowParams->get( 'group_eventapprove', $plugin->params->get( 'events_notifications_group_eventapprove', 0 ) ) ) : 0 ) ? 1 : 0 ) );
				$params->set( 'group_eventyes', ( (int) ( cbgjClass::hasAccess( array( 'mod_lvl5', 'events_schedule' ), $authorized, true ) ? cbgjClass::getCleanParam( true, 'group_eventyes', $rowParams->get( 'group_eventyes', $plugin->params->get( 'events_notifications_group_eventyes', 0 ) ) ) : 0 ) ? 1 : 0 ) );
				$params->set( 'group_eventmaybe', ( (int) ( cbgjClass::hasAccess( array( 'mod_lvl5', 'events_schedule' ), $authorized, true ) ? cbgjClass::getCleanParam( true, 'group_eventmaybe', $rowParams->get( 'group_eventmaybe', $plugin->params->get( 'events_notifications_group_eventmaybe', 0 ) ) ) : 0 ) ? 1 : 0 ) );
				$params->set( 'group_eventno', ( (int) ( cbgjClass::hasAccess( array( 'mod_lvl5', 'events_schedule' ), $authorized, true ) ? cbgjClass::getCleanParam( true, 'group_eventno', $rowParams->get( 'group_eventno', $plugin->params->get( 'events_notifications_group_eventno', 0 ) ) ) : 0 ) ? 1 : 0 ) );
				$params->set( 'group_eventupdate', ( (int) ( cbgjClass::hasAccess( array( 'mod_lvl5', 'events_show' ), $authorized, true ) ? cbgjClass::getCleanParam( true, 'group_eventupdate', $rowParams->get( 'group_eventupdate', $plugin->params->get( 'events_notifications_group_eventupdate', 0 ) ) ) : 0 ) ? 1 : 0 ) );
				$params->set( 'group_eventdelete', ( (int) ( cbgjClass::hasAccess( array( 'mod_lvl5', 'events_show' ), $authorized, true ) ? cbgjClass::getCleanParam( true, 'group_eventdelete', $rowParams->get( 'group_eventdelete', $plugin->params->get( 'events_notifications_group_eventdelete', 0 ) ) ) : 0 ) ? 1 : 0 ) );
			}
		}
	}

	public function getMenu( $user, $plugin ) {
		$input						=	array();

		$input['approve_events']	=	'<input type="checkbox" id="type_approve_events" name="type[]" class="inputbox" value="approve-events" />';

		$return						=	'<tr>'
									.		'<td width="10%" style="text-align:center;">' . $input['approve_events'] . '</td>'
									.		'<th width="20%">' . CBTxt::Th( 'Event Approval' ) . '</td>'
									.		'<td>' . CBTxt::Th( 'Create menu link to a event approval page.' ) . '</td>'
									.	'</tr>';

		return $return;
	}

	public function saveMenu( $type, $categories, $groups, $user, $plugin ) {
		if ( $type == 'approve-events' ) {
			if ( ! cbgjClass::setMenu( CBTxt::T( 'Event Approval' ), 'index.php?option=' . $plugin->option . '&task=pluginclass&plugin=' . $plugin->element . '&action=plugin&func=events_approval', $plugin ) ) {
				cbgjClass::getPluginURL( array( 'menus' ), CBTxt::T( 'Event approval menu failed to create!' ), false, true, 'error' );
			}
		}
	}
}

class cbgjEvent extends comprofilerDBTable {
	var $id			=	null;
	var $published	=	null;
	var $user_id	=	null;
	var $group		=	null;
	var $title		=	null;
	var $event		=	null;
	var $location	=	null;
	var $address	=	null;
	var $latitude	=	null;
	var $longitude	=	null;
	var $attending	=	null;
	var $date		=	null;

	public function __construct( $db ) {
		$this->comprofilerDBTable( '#__groupjive_plugin_events', 'id', $db );
	}

	public function get( $var, $def = null ) {
		if ( isset( $this->$var ) ) {
			return $this->$var;
		} else {
			return $def;
		}
	}

	public function store( $updateNulls = false ) {
		global $_CB_framework, $_PLUGINS;

		$plugin	=	cbgjClass::getPlugin();
		$user	=&	CBuser::getUserDataInstance( $_CB_framework->myId() );
		$new	=	( $this->get( 'id' ) ? false : true );

		if ( ! $new ) {
			$_PLUGINS->trigger( 'gjint_onBeforeUpdateEvent', array( &$this, $this->getGroup(), $this->getCategory(), $user, $plugin ) );
		} else {
			$_PLUGINS->trigger( 'gjint_onBeforeCreateEvent', array( &$this, $this->getGroup(), $this->getCategory(), $user, $plugin ) );
		}

		if ( ! parent::store( $updateNulls ) ) {
			return false;
		}

		if ( ! $new ) {
			$_PLUGINS->trigger( 'gjint_onAfterUpdateEvent', array( $this, $this->getGroup(), $this->getCategory(), $user, $plugin ) );
		} else {
			cbgjClass::resetCache( true );

			$_PLUGINS->trigger( 'gjint_onAfterCreateEvent', array( $this, $this->getGroup(), $this->getCategory(), $user, $plugin ) );
		}

		return true;
	}

	public function delete( $id = null ) {
		global $_CB_framework, $_PLUGINS;

		$plugin	=	cbgjClass::getPlugin();
		$user	=&	CBuser::getUserDataInstance( $_CB_framework->myId() );

		$_PLUGINS->trigger( 'gjint_onBeforeDeleteEvent', array( &$this, $this->getGroup(), $this->getCategory(), $user, $plugin ) );

		if ( ! parent::delete( $id ) ) {
			return false;
		}

		$_PLUGINS->trigger( 'gjint_onAfterDeleteEvent', array( $this, $this->getGroup(), $this->getCategory(), $user, $plugin ) );

		return true;
	}

	public function storeState( $state ) {
		global $_CB_framework, $_PLUGINS;

		$plugin	=	cbgjClass::getPlugin();
		$user	=&	CBuser::getUserDataInstance( $_CB_framework->myId() );

		$_PLUGINS->trigger( 'gjint_onBeforeUpdateEventState', array( &$state, &$this, $this->getGroup(), $this->getCategory(), $user, $plugin ) );

		$this->set( 'published', (int) $state );

		if ( ! parent::store() ) {
			return false;
		}

		$_PLUGINS->trigger( 'gjint_onAfterUpdateEventState', array( $this->get( 'published' ), $this, $this->getGroup(), $this->getCategory(), $user, $plugin ) );

		return true;
	}

	public function storeAttendance( $status, $user = null ) {
		global $_CB_framework, $_PLUGINS;

		$plugin							=	cbgjClass::getPlugin();
		$attendees						=	explode( '|*|', $this->get( 'attending' ) );

		if ( $user === null ) {
			$user						=&	CBuser::getUserDataInstance( $_CB_framework->myId() );
		}

		if ( $attendees ) {
			if ( $status == -1 ) {
				foreach ( $attendees as $k => $v ) {
					$attend				=	explode( ':', $v );
					$user_id			=	( isset( $attend[0] ) ? (int) $attend[0] : null );

					if ( $user_id === $user->id ) {
						unset( $attendees[$k] );
					}
				}
			} else {
				$exists					=	false;

				foreach ( $attendees as $k => $v ) {
					$attend				=	explode( ':', $v );
					$user_id			=	( isset( $attend[0] ) ? (int) $attend[0] : null );
					$attending			=	( isset( $attend[1] ) ? (int) $attend[1] : null );

					if ( $user_id === $user->id ) {
						if ( $attending !== $status ) {
							$attend[1]	=	$status;
						}

						$exists			=	true;
					}

					$attendees[$k]		=	implode( ':', $attend );
				}

				if ( ! $exists ) {
					$attendees[]		=	$user->id . ':' . $status;
				}
			}
		}

		$_PLUGINS->trigger( 'gjint_onBeforeUpdateEventAttendance', array( &$attendees, &$this, $this->getGroup(), $this->getCategory(), $user, $plugin ) );

		$this->set( 'attending', implode( '|*|', $attendees ) );

		if ( ! parent::store() ) {
			return false;
		}

		$_PLUGINS->trigger( 'gjint_onAfterUpdateEventAttendance', array( $this, $this->getGroup(), $this->getCategory(), $user, $plugin ) );

		return true;
	}

	public function setPathway( $title = null, $url = null ) {
		global $_CB_framework;

		$this->getGroup()->setPathway( false );

		if ( $title !== false ) {
			if ( ! $title ) {
				$title	=	$this->getTitle();
			}

			if ( $title ) {
				$_CB_framework->setPageTitle( htmlspecialchars( $title ) );
			}
		} else {
			$title		=	$this->getTitle();
		}

		if ( ! $url ) {
			$url		=	$this->getUrl();
		}

		if ( $title ) {
			$_CB_framework->appendPathWay( htmlspecialchars( $title ), $url );
		}
	}

	public function isPast() {
		static $cache	=	array();

		$id				=	$this->get( 'id' );

		if ( ! isset( $cache[$id] ) ) {
			$cache[$id]	=	(bool) ( cbgjClass::getUTCTimestamp( $this->get( 'date' ) ) < cbgjClass::getUTCNow() );
		}

		return $cache[$id];
	}

	public function getAttendees() {
		static $cache				=	array();

		$id							=	$this->get( 'id' );

		if ( ! isset( $cache[$id] ) ) {
			$yes					=	array();
			$maybe					=	array();
			$no						=	array();

			foreach ( explode( '|*|', $this->get( 'attending' ) ) as $attendee ) {
				$attendee			=	explode( ':', $attendee );
				$user_id			=	( isset( $attendee[0] ) ? (int) $attendee[0] : null );

				if ( $user_id ) switch( ( isset( $attendee[1] ) ? (int) $attendee[1] : null ) ) {
					case 0:
						$no[]		=	$user_id;
						break;
					case 1:
						$yes[]		=	$user_id;
						break;
					case 2:
						$maybe[]	=	$user_id;
						break;
				}
			}

			if ( ! in_array( $this->get( 'user_id' ), $yes ) ) {
				$yes[]				=	$this->get( 'user_id' );
			}

			$cache[$id]				=	array( 'yes' => $yes, 'maybe' => $maybe, 'no' => $no );
		}

		return $cache[$id];
	}

	public function getUrl() {
		static $cache	=	array();

		$id				=	$this->get( 'id' );

		if ( ! isset( $cache[$id] ) ) {
			$cache[$id]	=	cbgjClass::getPluginURL( array( 'groups', 'show', (int) $this->getCategory()->get( 'id' ), (int) $this->get( 'group' ), null, array( 'tab' => htmlspecialchars( CBTxt::T( 'Events' ) ) ) ) );
		}

		return $cache[$id];
	}

	public function getTitle( $length = 0, $linked = false ) {
		static $cache	=	array();

		$id				=	$this->get( 'id' );

		if ( ! isset( $cache[$id] ) ) {
			$cache[$id]	=	htmlspecialchars( $this->get( 'title' ) );
		}

		$title			=	$cache[$id];

		if ( $title ) {
			$length		=	(int) $length;

			if ( $length && ( cbIsoUtf_strlen( $title ) > $length ) ) {
				$title	=	rtrim( trim( cbIsoUtf_substr( $title, 0, $length ) ), '.' ) . '...';
				$short	=	true;
			} else {
				$short	=	false;
			}

			if ( $linked ) {
				$title	=	'<a href="' . $this->getUrl() . '"' . ( $short ? ' title="' . $cache[$id] . '"' : null ) . '>' . $title . '</a>';
			}
		}

		return $title;
	}

	public function getEvent( $length = 0 ) {
		static $cache	=	array();

		$id				=	$this->get( 'id' );

		if ( ! isset( $cache[$id] ) ) {
			$plugin		=	cbgjClass::getPlugin();
			$event		=	$this->get( 'event' );

			if ( $plugin->params->get( 'events_event_content', 0 ) ) {
				$event	=	cbgjClass::prepareContentPlugins( $event );
			}

			$cache[$id]	=	( $plugin->params->get( 'events_editor', 1 ) >= 2 ? $event : htmlspecialchars( $event ) );
		}

		$event			=	$cache[$id];

		if ( $event ) {
			$length		=	(int) $length;

			if ( $length && ( cbIsoUtf_strlen( strip_tags( $event ) ) > $length ) ) {
				$event	=	rtrim( trim( cbIsoUtf_substr( strip_tags( $event ), 0, $length ) ), '.' ) . '...';
			}
		}

		return $event;
	}

	public function getDate() {
		global $ueConfig;

		static $cache	=	array();

		$id				=	$this->get( 'id' );

		if ( ! isset( $cache[$id] ) ) {
			$plugin		=	cbgjClass::getPlugin();

			$cache[$id]	=	cbgjClass::getUTCDate( $ueConfig['date_format'], $this->get( 'date' ) ) . ' ' .cbgjClass::getUTCDate( ( ! $plugin->params->get( 'events_24hr', 0 ) ? 'h:i A' : 'H:i' ), $this->get( 'date' ) );
		}

		return $cache[$id];
	}

	public function getOwner() {
		static $cache	=	array();

		$id				=	$this->get( 'user_id' );

		if ( ! isset( $cache[$id] ) ) {
			$cache[$id]	=&	CBuser::getUserDataInstance( (int) $id );
		}

		return $cache[$id];
	}

	public function getOwnerName( $linked = false ) {
		static $cache	=	array();

		$id				=	$this->get( 'user_id' );

		if ( ! isset( $cache[$id] ) ) {
			$cbUser		=&	CBuser::getInstance( (int) $id );

			if ( ! $cbUser ) {
				$cbUser	=&	CBuser::getInstance( null );
			}

			$cache[$id]	=	$cbUser;
		}

		$name			=	null;

		if ( $cache[$id] ) {
			$user		=	$cache[$id];

			if ( $linked ) {
				$name	=	$user->getField( 'formatname', null, 'html', 'none', 'list', 0, true );
			} else {
				$name	=	$user->getField( 'formatname', null, 'html', 'none', 'profile', 0, true );
			}
		}

		return $name;
	}

	public function getOwnerAvatar( $linked = false ) {
		static $cache	=	array();

		$id				=	$this->get( 'user_id' );

		if ( ! isset( $cache[$id] ) ) {
			$cbUser		=&	CBuser::getInstance( (int) $id );

			if ( ! $cbUser ) {
				$cbUser	=&	CBuser::getInstance( null );
			}

			$cache[$id]	=	$cbUser;
		}

		$name			=	null;

		if ( $cache[$id] ) {
			$user		=	$cache[$id];

			if ( $linked ) {
				$name	=	$user->getField( 'avatar', null, 'html', 'none', 'list', 0, true );
			} else {
				$name	=	$user->getField( 'avatar', null, 'html', 'none', 'profile', 0, true );
			}
		}

		return $name;
	}

	public function getOwnerOnline( $html = true ) {
		static $cache	=	array();

		$id				=	$this->get( 'user_id' );

		if ( ! isset( $cache[$id] ) ) {
			$cbUser		=&	CBuser::getInstance( (int) $id );

			if ( ! $cbUser ) {
				$cbUser	=&	CBuser::getInstance( null );
			}

			$cache[$id]	=	$cbUser;
		}

		$status			=	null;

		if ( $cache[$id] ) {
			$user		=	$cache[$id];

			if ( ! $html ) {
				$status	=	$user->getField( 'onlinestatus', null, 'csv', 'none', 'profile', 0, true );
			} else {
				$status	=	$user->getField( 'onlinestatus', null, 'html', 'none', 'profile', 0, true );
			}
		}

		return $status;
	}

	public function getCategory() {
		static $cache	=	array();

		$id				=	$this->get( 'group' );

		if ( ! isset( $cache[$id] ) ) {
			$cache[$id]	=	$this->getGroup()->getCategory();
		}

		return $cache[$id];
	}

	public function getGroup() {
		static $cache	=	array();

		$id				=	$this->get( 'group' );

		if ( ! isset( $cache[$id] ) ) {
			$cache[$id]	=	cbgjData::getGroups( null, array( 'id', '=', (int) $id ), null, null, false );
		}

		return $cache[$id];
	}

	public function getSubstitutionExtras( $cbtxt = false ) {
		$extras				=	array(	'event_id' => $this->get( 'id' ),
										'event_event' => $this->getEvent(),
										'event_title' => $this->getTitle(),
										'event_title_linked' => $this->getTitle( 0, true ),
										'event_url' => $this->getUrl(),
										'event_date' => $this->getDate(),
										'event_owner' => $this->getOwnerName(),
										'event_owner_linked' => $this->getOwnerName( true ),
										'event_location' => $this->get( 'location' ),
										'event_address' => $this->get( 'address' ),
										'event_latitude' => $this->get( 'latitude' ),
										'event_longitude' => $this->get( 'longitude' ),
										'event_published' => $this->get( 'published' ),
										'event_user_id' => $this->get( 'user_id' ),
										'event_group' => $this->get( 'group' )
									);

		if ( $cbtxt ) foreach ( $extras as $k => $v ) {
			$extras["[$k]"]	=	$v;

			unset( $extras[$k] );
		}

		return $extras;
	}
}

class cbgjEventsData {

    /**
     * prepare array of user objects
     *
     * @param array $access
     * @param array $filtering
     * @param array $ordering
     * @param int $limits
     * @param bool $list
     * @return array|cbgjEvent
     */
    static public function getEvents( $access = array(), $filtering = array(), $ordering = array(), $limits = 0, $list = true ) {
		global $_CB_database;

		static $cache		=	array();

		if ( ! $access ) {
			$access			=	array();
		}

		if ( ! $filtering ) {
			$filtering		=	array();
		}

		if ( ! $ordering ) {
			$ordering		=	array();
		}

		$id					=	cbgjClass::getStaticID( array( $filtering, $ordering ) );

		if ( ( ! isset( $cache[$id] ) ) || cbgjClass::resetCache() ) {
			$where			=	array();
			$join			=	array();

			if ( $filtering ) {
				cbgjData::where( $where, $join, $filtering, 'a' );
			}

			$orderBy		=	array();

			if ( $ordering ) {
				cbgjData::order( $orderBy, $join, $ordering, 'a' );
			}

			$query			=	'SELECT a.*'
							.	"\n FROM " . $_CB_database->NameQuote( '#__groupjive_plugin_events' ) . " AS a";

			if ( count( $join ) ) {
				if ( in_array( 'b', $join ) ) {
					$query	.=	"\n LEFT JOIN " . $_CB_database->NameQuote( '#__groupjive_users' ) . " AS b"
							.	' ON b.' . $_CB_database->NameQuote( 'group' ) . ' = a.' . $_CB_database->NameQuote( 'group' )
							.	' AND b.' . $_CB_database->NameQuote( 'user_id' ) . ' = a.' . $_CB_database->NameQuote( 'user_id' );
				}

				if ( array_intersect( array( 'c', 'd' ), $join ) ) {
					$query	.=	"\n LEFT JOIN " . $_CB_database->NameQuote( '#__groupjive_groups' ) . " AS c"
							.	' ON c.' . $_CB_database->NameQuote( 'id' ) . ' = a.' . $_CB_database->NameQuote( 'group' );
				}

				if ( in_array( 'd', $join ) ) {
					$query	.=	"\n LEFT JOIN " . $_CB_database->NameQuote( '#__groupjive_categories' ) . " AS d"
							.	' ON d.' . $_CB_database->NameQuote( 'id' ) . ' = c.' . $_CB_database->NameQuote( 'category' );
				}

				if ( in_array( 'e', $join ) ) {
					$query	.=	"\n LEFT JOIN " . $_CB_database->NameQuote( '#__users' ) . " AS e"
							.	' ON e.' . $_CB_database->NameQuote( 'id' ) . ' = a.' . $_CB_database->NameQuote( 'user_id' );
				}
			}

			$query			.=	( count( $where ) ? "\n WHERE " . implode( "\n AND ", $where ) : null )
							.	"\n ORDER BY " . ( count( $orderBy ) ? implode( ', ', $orderBy ) : "( CASE WHEN ( a." . $_CB_database->NameQuote( 'date' ) . " > NOW() ) THEN a." . $_CB_database->NameQuote( 'date' ) . " ELSE 99999 END ) ASC, a." . $_CB_database->NameQuote( 'date' ) . " DESC" );
			$_CB_database->setQuery( $query );
			$cache[$id]		=	$_CB_database->loadObjectList( 'id', 'cbgjEvent', array( & $_CB_database ) );
		}

		$rows				=	$cache[$id];

		if ( $rows ) {
			if ( $access ) {
				cbgjData::access( $rows, $access );
			}

			if ( $limits ) {
				cbgjData::limit( $rows, $limits );
			}
		}

		if ( ! $rows ) {
			$rows			=	array();
		}

		if ( $list ) {
			return $rows;
		} else {
			$rows			=	array_shift( $rows );

			if ( ! $rows ) {
				$rows		=	new cbgjEvent( $_CB_database );
			}

			return $rows;
		}
	}
}
?>