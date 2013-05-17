<?php
if ( ! ( defined( '_VALID_CB' ) || defined( '_JEXEC' ) || defined( '_VALID_MOS' ) ) ) { die( 'Direct Access to this location is not allowed.' ); }

global $_PLUGINS;
$_PLUGINS->registerFunction( 'onAfterUserRegistration', 'acceptInvites', 'cbinvitesPlugin' );
$_PLUGINS->registerFunction( 'onAfterNewUser', 'acceptInvites', 'cbinvitesPlugin' );
$_PLUGINS->registerFunction( 'onAfterDeleteUser', 'deleteInvites', 'cbinvitesPlugin' );

class cbinvitesPlugin extends cbPluginHandler {

	public function acceptInvites( $user ) {
		$plugin							=	cbinvitesClass::getPlugin();
		$mode							=	$plugin->params->get( 'invite_connection', 2 );
		$code							=	$user->get( 'invite_code' );

		if ( $code ) {
			$where						=	array( 'to', '=', $user->email, array( 'code', '=', $code ) );
		} else {
			$where						=	array( 'to', '=', $user->email );
		}

		$invites						=	cbinvitesData::getInvites( null, $where );

		if ( $invites ) foreach ( $invites as $invite ) {
			if ( ! $invite->isAccepted() ) {
				$invite->user			=	(int) $user->id;
				$invite->accepted		=	date( 'Y-m-d H:i:s' );

				if ( $invite->store() ) {
					if ( $mode ) {
						$connections	=	new cbConnection( $invite->user_id );

						$connections->_insertConnection( $invite->user_id, $user->id, null );

						if ( $mode == 2 ) {
							$connections->_activateConnection( $invite->user_id, $user->id );
						}
					}
				}
			}
		}
	}

	public function deleteInvites( $user ) {
		$invites	=	cbinvitesData::getInvites( null, array( 'user_id', '=', $user->id, array( 'user', '=', $user->id ) ) );

		if ( $invites ) foreach ( $invites as $invite ) {
			$invite->delete();
		}
	}
}
?>