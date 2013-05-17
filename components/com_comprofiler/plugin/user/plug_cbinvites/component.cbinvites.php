<?php
if ( ! ( defined( '_VALID_CB' ) || defined( '_JEXEC' ) || defined( '_VALID_MOS' ) ) ) { die( 'Direct Access to this location is not allowed.' ); }

class CBplug_cbinvites extends cbPluginHandler {

	public function getCBpluginComponent( $tab, $user, $ui, $postdata ) {
		global $_CB_framework;

		outputCbJs( 1 );
		outputCbTemplate( 1 );

		$plugin		=	cbinvitesClass::getPlugin();
		$action		=	cbGetParam( $_REQUEST, 'action', null );
		$function	=	cbGetParam( $_REQUEST, 'func', null );
		$id			=	cbGetParam( $_REQUEST, 'id', null );
		$user		=&	CBuser::getUserDataInstance( $_CB_framework->myId() );

		ob_start();
		switch ( $action ) {
			case 'invites':
				switch ( $function ) {
					case 'new':
						$this->showInviteEdit( null, $user, $plugin );
						break;
					case 'edit':
						$this->showInviteEdit( $id, $user, $plugin );
						break;
					case 'save':
						cbSpoofCheck( 'plugin' );
						$this->saveInviteEdit( $id, $user, $plugin );
						break;
					case 'send':
						$this->sendInvite( $id, $user, $plugin );
						break;
					case 'delete':
						$this->deleteInvite( $id, $user, $plugin );
						break;
					case 'show':
					default:
						cbinvitesClass::getCBURL( null, true, false, true );
						break;
				}
				break;
			default:
				cbinvitesClass::getCBURL( null, CBTxt::T( 'Not authorized.' ), false, true );
				break;
		}
		$html		=	ob_get_contents();
		ob_end_clean();

		$return		=	'<div id="cbInvites" class="cbInvites' . htmlspecialchars( $plugin->params->get( 'general_class', null ) ) . ' cb_template_' . selectTemplate( 'dir' ) . '">'
					.		'<div id="cbInvitesInner">'
					.			$html
					.		'</div>'
					.	'</div>';

		echo $return;
	}

	public function showInviteEdit( $id, $user, $plugin, $message = null ) {
		$row					=	cbinvitesData::getInvites( array( array( 'mod_lvl1' ), $user ), array( 'id', '=', $id ), null, null, false );
		$authorized				=	cbinvitesClass::getAuthorization( $row, $user );

		if ( ( $row->get( 'id' ) || cbinvitesClass::hasAccess( 'inv_create', $authorized ) ) && ( ! $row->isAccepted() ) ) {
			if ( cbinvitesClass::hasAccess( 'inv_create_maxed', $authorized ) ) {
				cbinvitesClass::getCBURL( $row->get( 'user_id' ), CBTxt::T( 'Invite limit reached!' ), false, true, 'error' );
			}

			cbinvitesClass::getTemplate( 'invite_edit' );

			$input				=	array();

			$input['to']		=	'<input type="text" id="to" name="to" value="' . htmlspecialchars( cbinvitesClass::getCleanParam( true, 'to', $row->get( 'to' ) ) ) . '" class="input-large" size="35" />';
			$input['subject']	=	'<input type="text" id="subject" name="subject" value="' . htmlspecialchars( cbinvitesClass::getCleanParam( true, 'subject', $row->get( 'subject' ) ) ) . '" class="input-large" size="25" />';
			$input['body']		=	'<textarea id="body" name="body" class="input-xlarge" cols="35" rows="4">' . htmlspecialchars( cbinvitesClass::getHTMLCleanParam( true, 'body', $row->get( 'body' ) ) ) . '</textarea>';
			$input['user_id']	=	'<input type="text" id="user_id" name="user_id" value="' . (int) cbinvitesClass::getCleanParam( true, 'user_id', $row->get( 'user_id', $user->id ) ) . '" class="input-small" size="6" />';

			cbinvitesClass::displayMessage( $message );

			HTML_cbinvitesInviteEdit::showInviteEdit( $row, $input, $user, $plugin );
		} else {
			cbinvitesClass::getCBURL( null, CBTxt::T( 'Not authorized.' ), false, true, 'error' );
		}
	}

	private function saveInviteEdit( $id, $user, $plugin ) {
		$row						=	cbinvitesData::getInvites( array( array( 'mod_lvl1' ), $user ), array( 'id', '=', $id ), null, null, false );
		$authorized					=	cbinvitesClass::getAuthorization( $row, $user );
		$toArray					=	explode( ',', cbinvitesClass::getCleanParam( true, 'to' ) );

		if ( ( ! $plugin->params->get( 'invite_multiple', 1 ) ) && ( ! in_array( 'usr_mod', $authorized ) ) && ( count( $toArray ) > 1 ) ) {
			$this->showInviteEdit( $row->get( 'id' ), $user, $plugin, CBTxt::T( 'Comma seperated lists are not supported! Please use a single To address.' ) ); return;
		}

		if ( ( $row->get( 'id' ) || cbinvitesClass::hasAccess( 'inv_create', $authorized ) ) && ( ! $row->isAccepted() ) ) {
			$userId				=	null;
			$sent					=	false;

			if ( ! empty( $toArray ) ) {
				foreach ( $toArray as $k => $to ) {
					if ( $k != 0 ) {
						$row->set( 'id', null );
						$row->set( 'code', null );
					}

					$orgTo			=	$row->get( 'to' );

					$row->set( 'to', $to );
					$row->set( 'subject', cbinvitesClass::getCleanParam( true, 'subject', $row->get( 'subject' ) ) );
					$row->set( 'body', cbinvitesClass::getHTMLCleanParam( true, 'body', $row->get( 'body' ) ) );
					$row->set( 'user_id', (int) cbinvitesClass::getCleanParam( true, 'user_id', $row->get( 'user_id', $user->id ) ) );

					if ( ! $row->get( 'code' ) ) {
						$row->set( 'code', md5( uniqid() ) );
					}

					if ( ! $userId ) {
						$userId		=	$row->get( 'user_id' );
					}

					$authorized		=	cbinvitesClass::getAuthorization( $row, $user );

					if ( cbinvitesClass::hasAccess( 'inv_create_maxed', $authorized ) ) {
						cbinvitesClass::getCBURL( $row->user_id, CBTxt::T( 'Invite limit reached!' ), false, true, 'error' );
					}

					if ( ! $row->get( 'to' ) ) {
						$row->set( '_error', CBTxt::T( 'To address not specified.' ) );
					} elseif ( ! cbIsValidEmail( $row->get( 'to' ) ) ) {
						$row->set( '_error', CBTxt::P( 'To address not valid: [to_address]', array( '[to_address]' => $row->get( 'to' ) ) ) );
					} elseif ( $row->getUser()->id == $row->get( 'user_id' ) ) {
						$row->set( '_error', CBTxt::T( 'You can not invite your self.' ) );
					} elseif ( $row->getUser()->id && ( $row->get( 'to' ) != $orgTo ) ) {
						$row->set( '_error', CBTxt::T( 'To address is already a user.' ) );
					} elseif ( ( ! $plugin->params->get( 'invite_duplicate', 0 ) ) && ( ! in_array( 'usr_mod', $authorized ) ) && $row->isDuplicate() ) {
						$row->set( '_error', CBTxt::T( 'To address is already invited.' ) );
					} elseif ( $plugin->params->get( 'invite_captcha', 0 ) && ( ! $row->get( 'id' ) ) && ( $k == 0 ) && ( ! cbinvitesClass::hasAccess( 'usr_mod', $authorized ) ) ) {
						$captcha	=	cbinvitesCaptcha::validate();

						if ( $captcha !== true ) {
							$row->set( '_error', CBTxt::T( $captcha ) );
						}
					}

					$new			=	( $row->get( 'id' ) ? false : true );

					if ( $row->getError() || ( ! $row->store() ) ) {
						$this->showInviteEdit( $row->get( 'id' ), $user, $plugin, CBTxt::P( 'Invite failed to save! Error: [error]', array( '[error]' => $row->getError() ) ) ); return;
					}

					if ( ( $new || ( ! $row->isSent() ) ) && ( ! $row->getUser()->id ) ) {
						if ( ! $row->send() ) {
							$this->showInviteEdit( $row->get( 'id' ), $user, $plugin, CBTxt::P( 'Invite failed to send! Error: [error]', array( '[error]' => $row->getError() ) ) ); return;
						} else {
							$sent	=	true;
						}
					}
				}

				cbinvitesClass::getCBURL( $userId, ( $sent ? CBTxt::T( 'Invite sent successfully!' ) : CBTxt::T( 'Invite saved successfully!' ) ), false, true );
			} else {
				$this->showInviteEdit( $row->get( 'id' ), $user, $plugin, CBTxt::T( 'To address not specified.' ) ); return;
			}
		} else {
			cbinvitesClass::getCBURL( null, CBTxt::T( 'Not authorized.' ), false, true, 'error' );
		}
	}

	private function sendInvite( $id, $user, $plugin ) {
		$row	=	cbinvitesData::getInvites( array( array( 'mod_lvl1' ), $user ), array( 'id', '=', $id ), null, null, false );

		if ( $row->get( 'id' ) ) {
			if ( $row->isAccepted() ) {
				cbinvitesClass::getCBURL( $row->get( 'user_id' ), CBTxt::T( 'Invite already accepted and can not be resent.' ), false, true, 'error' );
			}

			if ( ! $row->canResend() ) {
				cbinvitesClass::getCBURL( $row->get( 'user_id' ), CBTxt::T( 'Invite resend not applicable at this time.' ), false, true, 'error' );
			}

			if ( ! $row->send() ) {
				cbinvitesClass::getCBURL( $row->get( 'user_id' ), CBTxt::P( 'Invite failed to send! Error: [error]', array( '[error]' => $row->getError() ) ), false, true, 'error' );
			}

			cbinvitesClass::getCBURL( $row->get( 'user_id' ), CBTxt::T( 'Invite sent successfully!' ), false, true, null, false, false, true );
		} else {
			cbinvitesClass::getCBURL( null, CBTxt::T( 'Not authorized.' ), false, true, 'error' );
		}
	}

	private function deleteInvite( $id, $user, $plugin ) {
		$row	=	cbinvitesData::getInvites( array( array( 'mod_lvl1' ), $user ), array( 'id', '=', $id ), null, null, false );

		if ( $row->get( 'id' ) ) {
			if ( $row->isAccepted() ) {
				cbinvitesClass::getCBURL( $row->get( 'user_id' ), CBTxt::T( 'Invite already accepted and can not be deleted.' ), false, true, 'error' );
			}

			if ( ! $row->delete() ) {
				cbinvitesClass::getCBURL( $row->get( 'user_id' ), CBTxt::P( 'Invite failed to delete! Error: [error]', array( '[error]' => $row->getError() ) ), false, true, 'error' );
			}

			cbinvitesClass::getCBURL( $row->get( 'user_id' ), CBTxt::T( 'Invite deleted successfully!' ), false, true, null, false, false, true );
		} else {
			cbinvitesClass::getCBURL( null, CBTxt::T( 'Not authorized.' ), false, true, 'error' );
		}
	}
}
?>