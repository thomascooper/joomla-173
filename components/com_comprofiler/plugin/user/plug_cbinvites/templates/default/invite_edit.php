<?php
if ( ! ( defined( '_VALID_CB' ) || defined( '_JEXEC' ) || defined( '_VALID_MOS' ) ) ) { die( 'Direct Access to this location is not allowed.' ); }

class HTML_cbinvitesInviteEdit {

	static function showInviteEdit( $row, $input, $user, $plugin ) {
		global $_CB_framework;

		$authorized		=	cbinvitesClass::getAuthorization( $row, $user );
		$pageTitle		=	( $row->get( 'to' ) ? CBTxt::T( 'Edit Invite' ) : CBTxt::T( 'Create Invite' ) );

		$_CB_framework->setPageTitle( $pageTitle );
		$_CB_framework->appendPathWay( htmlspecialchars( $pageTitle ), cbinvitesClass::getPluginURL( ( $row->get( 'id' ) ? array( 'invites', 'edit', $row->get( 'id' ) ) : array( 'blogs', 'new' ) ) ) );

		$return			=	'<div class="invitesEdit">'
						.		'<form action="' . cbinvitesClass::getPluginURL( array( 'invites', 'save', $row->get( 'id' ) ), null, true, false, null, 'current' ) . '" method="post" enctype="multipart/form-data" name="invitesForm" id="invitesForm" class="invitesForm form-horizontal">'
						.			( $pageTitle ? '<div class="invitesTitle page-header"><h3>' . $pageTitle . '</h3></div>' : null )
						.			'<div class="invitesEditGroup control-group">'
						.				'<label class="invitesEditTitle control-label">' . CBTxt::Th( 'To' ) . '</label>'
						.				'<div class="invitesEditInput controls">'
						.					$input['to']
						.					'<span class="invitesEditIcon help-inline">'
						.						cbinvitesClass::getIcon( null, CBTxt::T( 'Required' ), 'icon-star' )
						.						cbinvitesClass::getIcon( CBTxt::T( 'Input invite email to address.' ) )
						.					'</span>'
						.				'</div>'
						.			'</div>'
						.			'<div class="invitesEditGroup control-group">'
						.				'<label class="invitesEditTitle control-label">' . CBTxt::Th( 'Subject' ) . '</label>'
						.				'<div class="invitesEditInput controls">'
						.					$input['subject']
						.					'<span class="invitesEditIcon help-inline">'
						.						cbinvitesClass::getIcon( CBTxt::T( 'Input invite email subject; if left blank a subject will be applied.' ) )
						.					'</span>'
						.				'</div>'
						.			'</div>'
						.			'<div class="invitesEditGroup control-group">'
						.				'<label class="invitesEditTitle control-label">' . CBTxt::Th( 'Body' ) . '</label>'
						.				'<div class="invitesEditInput controls">'
						.					$input['body']
						.					'<span class="invitesEditIcon help-inline">'
						.						cbinvitesClass::getIcon( CBTxt::T( 'Input private message to include with invite email.' ) )
						.					'</span>'
						.				'</div>'
						.			'</div>';

		if ( cbinvitesClass::hasAccess( 'usr_mod', $authorized ) ) {
			$return		.=			'<div class="invitesEditGroup control-group">'
						.				'<label class="invitesEditTitle control-label">' . CBTxt::Th( 'Owner' ) . '</label>'
						.				'<div class="invitesEditInput controls">'
						.					$input['user_id']
						.					'<span class="invitesEditIcon help-inline">'
						.						cbinvitesClass::getIcon( null, CBTxt::T( 'Required' ), 'icon-star' )
						.						cbinvitesClass::getIcon( CBTxt::T( 'Input owner of invite as single integer user_id.' ) )
						.					'</span>'
						.				'</div>'
						.			'</div>';
		}

		if ( $plugin->params->get( 'invite_captcha', 0 ) && ( ! cbinvitesClass::hasAccess( 'usr_mod', $authorized ) ) ) {
			$captcha	=	cbinvitesCaptcha::render();

			if ( $captcha !== false ) {
				$return	.=			'<div class="invitesEditGroup control-group">'
						.				'<label class="invitesEditTitle control-label">' . CBTxt::Th( 'Captcha' ) . '</label>'
						.				'<div class="invitesEditInput controls">'
						.					'<div style="margin-bottom: 5px;">' . $captcha['code'] . '</div>'
						.					'<div>' . $captcha['input'] . '</div>'
						.					'<span class="invitesEditIcon help-inline">'
						.						cbinvitesClass::getIcon( null, CBTxt::T( 'Required' ), 'icon-star' )
						.					'</span>'
						.				'</div>'
						.			'</div>';
			}
		}

		$return			.=			'<div class="invitesButtonWrapper form-actions">'
						.				'<input type="submit" value="' . htmlspecialchars( ( $row->get( 'id' ) ? CBTxt::T( 'Update Invite' ) : CBTxt::T( 'Send Invite' ) ) ) . '" class="invitesButton invitesButtonSubmit btn btn-primary" />&nbsp;'
						.				'<input type="button" value="' . htmlspecialchars( CBTxt::T( 'Cancel' ) ) . '" class="invitesButton invitesButtonCancel btn btn-mini" onclick="' . cbinvitesClass::getCBURL( $row->user_id, CBTxt::T( 'Are you sure you want to cancel? All unsaved data will be lost!' ), true, false, null, false, false, true ) . '" />'
						.			'</div>'
						.			cbGetSpoofInputTag( 'plugin' )
						.		'</form>'
						.	'</div>';

		echo $return;
	}
}
?>