<?php
if ( ! ( defined( '_VALID_CB' ) || defined( '_JEXEC' ) || defined( '_VALID_MOS' ) ) ) { die( 'Direct Access to this location is not allowed.' ); }

class HTML_groupjiveInvitesList {

	/**
	 * render frontend invites list
	 *
	 * @param array $rows
	 * @param cbgjCategory $category
	 * @param cbgjGroup $group
	 * @param moscomprofilerUser $user
	 * @param object $plugin
	 * @return mixed
	 */
	static function showInvitesList( $rows, $category, $group, $user, $plugin ) {
		$group->setPathway( CBTxt::T( 'Invites List' ), true );

		$inviteBy			=	explode( '|*|', $plugin->params->get( 'group_invites_by', '1|*|2|*|3|*|4' ) );

		$return				=	'<div class="gjInvitesList">'
							.		'<form action="' . cbgjClass::getPluginURL( array( 'invites', 'send', (int) $category->get( 'id' ), (int) $group->get( 'id' ) ) ) . '" method="post" enctype="multipart/form-data" name="gjForm" id="gjForm" class="gjForm">'
							.			'<legend class="gjEditTitle">' . cbgjClass::getOverride( 'user', true ) . '</legend>';

		if ( $rows ) {
			$return			.=			'<div class="gjContent">';

			foreach ( $rows as $row ) {
				$cbUser		=&	CBuser::getInstance( (int) $row->get( 'id' ) );

				if ( ! $cbUser ) {
					$cbUser	=&	CBuser::getInstance( null );
				}

				$recipient	=&	$cbUser->getUserData();

				if ( in_array( 1, $inviteBy ) ) {
					$invite	=	(int) $recipient->id;
				} elseif ( in_array( 4, $inviteBy ) ) {
					$invite	=	$recipient->email;
				} elseif ( in_array( 2, $inviteBy ) ) {
					$invite	=	$recipient->username;
				} elseif ( in_array( 3, $inviteBy ) ) {
					$invite	=	$recipient->name;
				}

				if ( ! $invite ) {
					$invite	=	(int) $row->get( 'id' );
				}

				$inviteUrl	=	"document.gjForm.invites_invite.value = '" . addslashes( $invite ) . "';"
							.	"document.gjForm.submit();";

				$return		.=				'<div class="gjContentBox mini-layout">'
							.					'<div class="gjContentBoxRow">' . $cbUser->getField( 'formatname', null, 'html', 'none', 'list', 0, true ) . '</div>'
							.					'<div class="gjContentBoxRow">' . $cbUser->getField( 'avatar', null, 'html', 'none', 'list', 0, true ) . '</div>'
							.					'<div class="gjContentBoxRow">' . $cbUser->getField( 'onlinestatus', null, 'html', 'none', 'profile', 0, true  ) . '</div>'
							.					'<div class="gjContentBoxRow">'
							.						'<input type="button" value="' . htmlspecialchars( CBTxt::Th( 'Invite' ) ) . '" class="gjButton btn btn-success" onclick="' . $inviteUrl . '" />'
							.					'</div>'
							.				'</div>';
			}

			$return			.=			'</div>';
		} else {
			$return			.=			'<div class="gjContent">' . CBTxt::Ph( 'There are no [users] available to invite.', array( '[users]' => cbgjClass::getOverride( 'user', true ) ) ) . '</div>';
		}

		$return				.=			'<div class="gjButtonWrapper form-actions">'
							.				'<input type="button" value="' . htmlspecialchars( CBTxt::T( 'Cancel' ) ) . '" class="gjButton gjButtonCancel btn" onclick="' . cbgjClass::getPluginURL( array( 'groups', 'show', (int) $category->get( 'id' ), (int) $group->get( 'id' ) ), CBTxt::T( 'Are you sure you want to cancel? All unsaved data will be lost!' ) ) . '" />'
							.			'</div>'
							.			'<input type="hidden" name="invites_invite" id="invites_invite" value="" />'
							.			'<input type="hidden" name="invites_list" id="invites_list" value="1" />'
							.			cbGetSpoofInputTag( 'plugin' )
							.		'</form>'
							.	'</div>';

		echo $return;
	}
}
?>