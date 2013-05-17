<?php
if ( ! ( defined( '_VALID_CB' ) || defined( '_JEXEC' ) || defined( '_VALID_MOS' ) ) ) { die( 'Direct Access to this location is not allowed.' ); }

class HTML_cbinvitesTab {

	static function showTab( $rows, $pageNav, $viewer, $user, $plugin ) {
		global $_CB_framework;

		$authorized			=	cbinvitesClass::getAuthorization( null, $viewer );
		$tabPaging			=	$plugin->params->get( 'tab_paging', 1 );
		$canInvite			=	( $viewer->id == $user->id ) && cbinvitesClass::hasAccess( 'inv_create_limited', $authorized );
		$canSearch			=	( $plugin->params->get( 'tab_search', 1 ) && ( $pageNav->searching || $pageNav->total ) );

		$return				=	'<div class="invitesTab">'
							.		'<form action="' . $_CB_framework->userProfileUrl( $user->id, true, $plugin->tab->tabid ) . '" method="post" name="inviteForm" id="inviteForm" class="inviteForm">';

		if ( $canInvite || $canSearch ) {
			$return			.=			'<div class="invitesHeader row-fluid">'
							.				'<div class="invitesHeaderLeft span6">' . ( $canInvite ? '<a href="' . cbinvitesClass::getPluginURL( array( 'invites', 'new' ) ) . '" class="invitesButton invitesButtonNew btn">' . CBTxt::T( 'New Invite' ) . '</a>' : null ) . '</div>'
							.				'<div class="invitesHeaderRight span6">' . ( $canSearch ? $pageNav->search : null ) . '</div>'
							.			'</div>';
		}

		$return				.=			'<div class="invitesContainer mini-layout">'
							.				'<div class="invitesColumns row-fluid">'
							.					'<div class="invitesColumn1 span4">' . CBTxt::T( 'To' ) . '</div>'
							.					'<div class="invitesColumn2 span3">' . CBTxt::T( 'Sent' ) . '</div>'
							.					'<div class="invitesColumn3 span3">' . CBTxt::T( 'Accepted' ) . '</div>'
							.					'<div class="invitesColumn4 span1"></div>'
							.					'<div class="invitesColumn5 span1"></div>'
							.				'</div>';

		if ( $rows ) foreach ( $rows as $row ) {
			$authorized		=	cbinvitesClass::getAuthorization( $row, $viewer );

			$return			.=				'<div class="invitesDivider"></div>'
							.				'<div class="invitesContent row-fluid">'
							.					'<div class="invitesColumn1 span4">' . $row->getTo() . '</div>'
							.					'<div class="invitesColumn2 span3">' . ( $row->isSent() ? cbFormatDate( $row->sent ) : null ) . '</div>'
							.					'<div class="invitesColumn3 span3">' . ( $row->isAccepted() ? cbFormatDate( $row->accepted ) : null ) . '</div>'
							.					'<div class="invitesColumn4 span1">' . $row->getStatus() . '</div>'
							.					'<div class="invitesColumn5 span1">';

			if ( cbinvitesClass::hasAccess( 'mod_lvl1', $authorized ) && ( $row->canResend() || ( ! $row->isAccepted() ) ) ) {
				$return		.=						'<div class="invitesDropdown">'
							.							'<i class="icon-chevron-down"></i>'
							.							'<div class="invitesDropdownItems">'
							.								( cbinvitesClass::hasAccess( 'mod_lvl1', $authorized ) && $row->canResend() ? '<div><a href="' . cbinvitesClass::getPluginURL( array( 'invites', 'send', $row->id ) ) . '"><i class="icon-share-alt"></i> ' . CBTxt::T( 'Resend' ) . '</a></div>' : null )
							.								( cbinvitesClass::hasAccess( 'mod_lvl1', $authorized ) && ( ! $row->isAccepted() ) ? '<div><a href="' . cbinvitesClass::getPluginURL( array( 'invites', 'edit', $row->id ) ) . '"><i class="icon-pencil"></i> ' . CBTxt::T( 'Edit' ) . '</a></div>' : null )
							.								( cbinvitesClass::hasAccess( 'mod_lvl1', $authorized ) && ( ! $row->isAccepted() ) ? '<div><a href="javascript: void(0);" onclick="' . cbinvitesClass::getPluginURL( array( 'invites', 'delete', $row->id ), CBTxt::T( 'Are you sure you want to delete this invite?' ) ) . '"><i class="icon-remove"></i> ' . CBTxt::T( 'Delete' ) . '</a></div>' : null )
							.							'</div>'
							.						'</div>';
			}

			$return			.=					'</div>'
							.				'</div>';
		} else {
			$return			.=				'<div class="invitesDivider"></div>'
							.				'<div class="invitesContent">'
							.					'<div>';

			if ( $pageNav->searching ) {
				$return		.=						CBTxt::T( 'No invite search results found.' );
			} else {
				if ( $viewer->id == $user->id ) {
					$return	.=						CBTxt::T( 'You have no invites.' );
				} else {
					$return	.=						CBTxt::T( 'This user has no invites.' );
				}
			}

			$return			.=					'</div>'
							.				'</div>';
		}

		$return				.=			'</div>';

		if ( $tabPaging ) {
			$return			.=			'<div class="invitesPaging pagination pagination-centered">'
							.				( $pageNav->total > $pageNav->limit ? $pageNav->pagelinks : null )
							.				$pageNav->getLimitBox( false )
							.			'</div>';
		}

		$return				.=		'</form>'
							.	'</div>';

		return $return;
	}
}
?>