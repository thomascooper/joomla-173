<?php
if ( ! ( defined( '_VALID_CB' ) || defined( '_JEXEC' ) || defined( '_VALID_MOS' ) ) ) { die( 'Direct Access to this location is not allowed.' ); }

class HTML_cbactivityTab {

	static public function showActivityTab( $rows, $ajax, $pageNav, $viewer, $user, $plugin ) {
		global $_CB_framework;

		$ajaxPaging				=	$plugin->params->get( 'tab_paging_jquery', 1 );
		$return					=	null;

		static $cache			=	array();

		$cache['pb']			=	array(); // CB ProfileBook Posts
		$cache['pg']			=	array(); // CB ProfileGallery Uploads
		$cache['gjLoaded']		=	0; // GJ Loaded
		$cache['gjCategories']	=	array(); // CB GroupJive Categories
		$cache['gjGroups']		=	array(); // CB GroupJive Groups
		$cache['gjEvents']		=	array(); // CB GroupJive Group Events
		$cache['gjFiles']		=	array(); // CB GroupJive Group Files
		$cache['gjPhotos']		=	array(); // CB GroupJive Group Photos
		$cache['gjVideos']		=	array(); // CB GroupJive Group Videos
		$cache['gjPosts']		=	array(); // CB GroupJive Group Wall Posts
		$cache['blogsLoaded']	=	0; // CB Blogs Loaded
		$cache['blogs']			=	array(); // CB Blogs Posts
		$cache['messages']		=	array(); // Kunena Posts

		if ( ! $ajax ) {
			$return				.=	'<div class="activityTab">'
								.		'<form action="' . ( $ajaxPaging ? cbactivityClass::getTabClassURL( $user->get( 'id' ), true, 'raw' ) : $_CB_framework->userProfileUrl( $user->get( 'id' ), true, $plugin->tab->tabid ) ) . '" method="post" name="activityForm" id="activityForm" class="activityForm">';
		}

		if ( $rows ) foreach ( $rows as $row ) {
			$message			=	HTML_cbactivityActivity::getMessageDisplay( $row, $cache, $user, $plugin );

			$return				.=			'<div id="activity' . (int) $row->get( 'id' ) . '" class="activityContent' . htmlspecialchars( $row->get( 'class' ) ) . ' row-fluid">'
								.				'<div class="activityContentLogo span2">' . $row->getOwnerAvatar( true ) . '</div>'
								.				'<div class="activityContentBody mini-layout span10">'
								.					'<div class="activityContentBodyHeader row-fluid">'
								.						'<div class="activityContentBodyTitle span9"><h5>' . ( $row->get( 'icon' ) ? $row->getIcon() . ' ' : null ) . $row->getOwnerName( true ) . ( $row->get( 'title' ) ? ' <small>' . $row->getTitle() . '</small>' : null ) . '</h5></div>'
								.						'<div class="activityContentBodyTime muted span3"><small>' . $row->getTimeAgo( true ) . '</small></div>'
								.					'</div>'
								.					'<div class="activityContentBodyInfo">';

			if ( $message || $row->get( 'from' ) ) {
				$return			.=						'<div class="well well-small">'
								.							$message
								.							( $row->get( 'from' ) ? '<div class="activityContentBodyInfoRow">' . ( $row->get( 'to' ) ? CBTxt::P( '[from] to [to]', array( '[from]' => $row->getFrom(), '[to]' => $row->getTo() ) ) : CBTxt::P( 'removed [from]', array( '[from]' => $row->getFrom() ) ) ) . '</div>' : null )
								.						'</div>';
			}

			$return				.=					'</div>'
								.				'</div>'
								.			'</div>';
		} else {
			if ( ! $ajax ) {
				$return			.=			'<div class="activityContent">';

				if ( $viewer->get( 'id' ) == $user->get( 'id' ) ) {
					$return		.=				CBTxt::T( 'You have no activity.' );
				} else {
					$return		.=				CBTxt::T( 'This user has no activity.' );
				}

				$return			.=			'</div>';
			} else {
				return null;
			}
		}

		if ( $plugin->params->get( 'tab_paging', 1 ) && ( $pageNav->total > $pageNav->limit ) && ( ( ! $ajaxPaging ) || ( $ajaxPaging && ( $pageNav->limitstart < $pageNav->total ) ) ) ) {
			$return				.=			'<div class="activityPaging pagination pagination-centered">'
								.				( $ajaxPaging ? '<a class="activityButton activityButtonMore btn btn-large btn-block" href="javascript: void(0);">' . CBTxt::T( 'More' ) . '</a><input type="hidden" value="1" name="tab_activity_ajax" />' : $pageNav->pagelinks )
								.				$pageNav->getLimitBox( false )
								.			'</div>';
		}

		if ( ! $ajax ) {
			$return				.=		'</form>'
								.	'</div>';
		}

		return $return;
	}
}
?>