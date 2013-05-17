<?php
if ( ! ( defined( '_VALID_CB' ) || defined( '_JEXEC' ) || defined( '_VALID_MOS' ) ) ) { die( 'Direct Access to this location is not allowed.' ); }

global $_PLUGINS;
$_PLUGINS->loadPluginGroup( 'user', array( (int) 1 ) );
$_PLUGINS->registerUserFieldTypes( array( 'invite_code' => 'cbinvitesField' ) );
$_PLUGINS->registerUserFieldParams();

class cbinvitesField extends CBfield_text {

	public function getField( &$field, &$user, $output, $reason, $list_compare_types ) {
		if ( ( $reason == 'register' ) && ( $output == 'htmledit' ) ) {
			$this->ajaxCheckField( $field, $user, $reason );

			$code	=	cbinvitesClass::getCleanParam( true, 'invite_code', null, 'GET' );

			if ( $code ) {
				$user->set( 'invite_code', $code );
			}
		}

		return parent::getField( $field, $user, $output, $reason, $list_compare_types );
	}

	public function fieldClass( &$field, &$user, &$postdata, $reason ) {
		parent::fieldClass( $field, $user, $postdata, $reason );

		$function				=	cbGetParam( $_GET, 'function', null );
		$return					=	null;

		if ( $function == 'checkvalue' ) {
			$value				=	stripslashes( cbGetParam( $postdata, 'value', null ) );

			if ( $value ) {
				$invite			=	cbinvitesData::getInvites( null, array( 'code', '=', $value ), null, null, false );

				if ( ! $invite->get( 'id' ) ) {
					$return		=	'<span class="cb_result_error">' . CBTxt::T( 'Invite code not valid.' ) . '</span>';
				} else {
					if ( $invite->isAccepted() ) {
						$return	=	'<span class="cb_result_error">' . CBTxt::T( 'Invite code already used.' ) . '</span>';
					} else {
						$return	=	'<span class="cb_result_ok">' . CBTxt::T( 'Invite code is valid.' ) . '</span>';
					}
				}
			}
		}

		return $return;
	}

	public function validate( &$field, &$user, $columnName, &$value, &$postdata, $reason ) {
		if ( parent::validate( $field, $user, $columnName, $value, $postdata, $reason ) ) {
			if ( $value ) {
				$invite		=	cbinvitesData::getInvites( null, array( 'code', '=', $value ), null, null, false );

				if ( ! $invite->get( 'id' ) ) {
					$this->_setValidationError( $field, $user, $reason, CBTxt::T( 'Invite code not valid.' ) );
					return false;
				} elseif ( $invite->isAccepted() ) {
					$this->_setValidationError( $field, $user, $reason, CBTxt::T( 'Invite code already used.' ) );
					return false;
				}
			}
		}
	}
}
?>