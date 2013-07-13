<?php
/**
* Facebook CB plugin for Community Builder
* @version $Id: cb.facebookconnect.php 1102 2010-05-06 12:56:04Z kyle $
* @package Community Builder
* @subpackage Facebook CB plugin
* @author Kyle and Beat
* @copyright (C) http://www.joomlapolis.com and various
* @license http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU/GPL version 2
*/

if ( ! ( defined( '_VALID_CB' ) || defined( '_JEXEC' ) || defined( '_VALID_MOS' ) ) ) { die( 'Direct Access to this location is not allowed.' ); }

global $_PLUGINS;
$_PLUGINS->registerFunction( 'onAfterLoginForm', 'getDisplay', 'cbfacebookconnectPlugin' );
$_PLUGINS->registerFunction( 'onAfterLogoutForm', 'getDisplay', 'cbfacebookconnectPlugin' );
$_PLUGINS->registerFunction( 'onPrepareMenus', 'getMenu','cbfacebookconnectPlugin' );

$_PLUGINS->registerUserFieldParams();
$_PLUGINS->registerUserFieldTypes( array( 'facebook_userid' => 'cbfacebookconnectField' ) );

class cbfacebookconnectField extends cbFieldHandler {

	public function getField( &$field, &$user, $output, $reason, $list_compare_types ) {
		global $_CB_framework;

		$value						=	$user->get( $field->name );
		$return						=	null;

		switch( $output ) {
			case 'htmledit':
				if ( $reason == 'search' ) {
					$fieldFormat	=	$this->_fieldEditToHtml( $field, $user, $reason, 'input', 'text', $value, null );
					$return			=	$this->_fieldSearchModeHtml( $field, $user, $fieldFormat, 'text', $list_compare_types );
				} else {
					if ( $_CB_framework->getUi() == 2 ) {
						$return		=	$this->_fieldEditToHtml( $field, $user, $reason, 'input', 'text', $value, null );
					}
				}
				break;
			case 'html':
			case 'rss':
				if ( $value ) {
					$value			=	'<a href="http://www.facebook.com/profile.php?id=' . htmlspecialchars( urlencode( $value ) ) . '" target="_blank">' . htmlspecialchars( CBTxt::T( 'View Facebook Profile' ) ) . '</a>';
				}

				$return				=	$this->_formatFieldOutput( $field->name, $value, $output, false );
				break;
			default:
				$return				=	$this->_formatFieldOutput( $field->name, $value, $output );
				break;
		}

		return $return;
	}
}

class cbfacebookconnectSynchronize {

	public function syncUser( $userId = null, $userVars = array() ) {
		global $_CB_framework;

		if ( ! $this->getConnectID() ) {
			cbfacebookconnectClass::setRedirect( null, CBTxt::T( 'ID not found!' ), 'error' );

			return false;
		}

		$myId									=	$_CB_framework->myId();

		if ( ! $userId ) {
			$userId								=	$this->getUserID();
		}

		if ( $myId ) {
			if ( $userId ) {
				if ( $userId != $myId ) {
					cbfacebookconnectClass::setRedirect( null, CBTxt::T( 'ID already in use or account mismatch!' ), 'error' );

					return false;
				}
			} else {
				$userId							=	$myId;
			}
		}

		$user									=&	CBuser::getUserDataInstance( (int) $userId );

		if ( $userVars ) foreach ( $userVars as $k => $v ) {
			$user->set( $k, $v );
		}

		if ( $user->id ) {
			if ( $this->sync->link ) {
				if ( ! $this->updateUser( $user ) ) {
					cbfacebookconnectClass::setRedirect( null, $user->getError(), 'error' );

					return false;
				} elseif ( $myId ) {
					cbfacebookconnectClass::setRedirect( null, CBTxt::T( 'Account linked successfully.' ), 'message' );
				}
			} elseif ( $myId ) {
				cbfacebookconnectClass::setRedirect( null, CBTxt::T( 'Account linking not permitted!' ), 'error' );

				return false;
			}

			if ( ! $myId  ) {
				$redirect						=	null;

				if ( ( ! $user->get( 'lastvisitDate' ) ) || ( $user->get( 'lastvisitDate' ) == '0000-00-00 00:00:00' ) ) {
					$redirect					=	$this->sync->firstlogin;
				}

				$login							=	$this->loginUser( $user );

				if ( $login ) {
					if ( ! $redirect ) {
						$redirect				=	$this->sync->login;
					}

					cbfacebookconnectClass::setRedirect( $redirect, ( $login !== true ? $login : null ) );
				} else {
					cbfacebookconnectClass::setRedirect( null, $user->getError(), 'error' );

					return false;
				}
			}
		} else {
			if ( $this->sync->register ) {
				$registration					=	$this->registerUser( $user );

				if ( $registration ) {
					if ( $user->block == 0 ) {
						$login					=	$this->loginUser( $user );

						if ( $login ) {
							$redirect			=	$this->sync->firstlogin;

							if ( ! $redirect ) {
								$redirect		=	$this->sync->login;
							}

							cbfacebookconnectClass::setRedirect( $redirect, ( $login !== true ? $login : null ) );
						} else {
							cbfacebookconnectClass::setRedirect( null, $user->getError(), 'error' );

							return false;
						}
					} else {
						cbfacebookconnectClass::setRedirect( null, $registration, 'message' );

						return false;
					}
				} else {
					cbfacebookconnectClass::setRedirect( null, $user->getError(), 'error' );

					return false;
				}
			} else {
				cbfacebookconnectClass::setRedirect( null, CBTxt::T( 'Account registration not permitted!' ), 'error' );

				return false;
			}
		}

		return true;
	}

	private function registerUser( &$user ) {
		global $_CB_framework, $_CB_database, $_PLUGINS, $ueConfig;

		$connectUser			=	$this->getConnectUser( null, $user );

		if ( ! $connectUser ) {
			$errors				=	$user->getError();

			cbfacebookconnectClass::setRedirect( null, ( $errors ? $errors : CBTxt::T( 'User failed to initiate!' ) ), 'error' );

			return false;
		}

		$secret					=	$this->gen->secret;
		$approve				=	$this->sync->approve;
		$confirm				=	$this->sync->confirm;
		$usergroup				=	$this->sync->usergroup;
		$approval				=	( $approve == 2 ? $ueConfig['reg_admin_approval'] : $approve );
		$confirmation			=	( $confirm == 2 ? $ueConfig['reg_confirmation'] : $confirm );
		$username				=	$connectUser->username;
		$fieldname				=	$this->gen->fieldname;

		$dummyUser				=	new moscomprofilerUser( $_CB_database );

		if ( $dummyUser->loadByUsername( $username ) ) {
			$username			=	$username . '_' . $connectUser->id;

			if ( $dummyUser->loadByUsername( $username ) ) {
				cbfacebookconnectClass::setRedirect( null, CBTxt::T( 'This username is already in use!' ), 'error' );

				return false;
			}
		}

		$connectEmail			=	$user->get( 'email' );

		if ( ! $connectEmail ) {
			$connectEmail		=	$connectUser->email;
		}

		$emailExists			=	$dummyUser->loadByEmail( $connectEmail );
		$emailInvalid			=	preg_match( '/@invalid(?:\.com)?|cb\.invalid$/', $connectEmail );
		$termsAndConditions		=	( $ueConfig['reg_enable_toc'] && ( $user->get( 'acceptedterms' ) != 1 ) );

		if ( $emailExists || $emailInvalid || $termsAndConditions ) {
			$error				=	null;

			if ( $emailExists ) {
				$error			=	CBTxt::T( 'This email address is already in use!' );

				if ( $this->sync->link ) {
					$error		.=	' ' . CBTxt::T( 'Please login to link an existing account or supply a valid email address to complete registration.' );
				} else {
					$error		.=	' ' . CBTxt::T( 'Please supply a valid email address to complete registration.' );
				}
			} else {
				$error			=	CBTxt::T( 'Your registration is not yet complete.' );

				if ( $emailInvalid && $termsAndConditions ) {
					if ( $this->sync->link ) {
						$error	.=	' ' . CBTxt::T( 'Please login to link to an existing account or supply a valid email address and review the terms and conditions to complete registration.' );
					} else {
						$error	.=	' ' . CBTxt::T( 'Please supply a valid email address and review the terms and conditions to complete registration.' );
					}
				} elseif ( $emailInvalid ) {
					if ( $this->sync->link ) {
						$error	.=	' ' . CBTxt::T( 'Please login to link to an existing account or supply a valid email address to complete registration.' );
					} else {
						$error	.=	' ' . CBTxt::T( 'Please supply a valid email address.' );
					}
				} elseif ( $termsAndConditions ) {
					if ( $this->sync->link ) {
						$error	.=	' ' . CBTxt::T( 'Please login to link to an existing account or review the terms and conditions to complete registration.' );
					} else {
						$error	.=	' ' . CBTxt::T( 'Please review the terms and conditions to complete registration.' );
					}
				}
			}

			cbfacebookconnectClass::getPluginURL( array( $this->type, 'registration' ), $error, false, true, 'message', 'current' );

			return false;
		}

		$middlenamePosition		=	strpos( $connectUser->name, ' ' );
		$lastnamePosition		=	strrpos( $connectUser->name, ' ' );

		if ( $lastnamePosition !== false ) {
			$firstname			=	substr( $connectUser->name, 0, $middlenamePosition );
			$lastname			=	substr( $connectUser->name, $lastnamePosition + 1 );

			if ( $middlenamePosition !== $lastnamePosition ) {
				$middlename		=	substr( $connectUser->name, $middlenamePosition + 1, $lastnamePosition - $middlenamePosition - 1 );
			} else {
				$middlename		=	null;
			}
		} else {
			$firstname			=	null;
			$middlename			=	null;
			$lastname			=	$connectUser->name;
		}

		if ( ! $usergroup ) {
			$usertype			=	$_CB_framework->getCfg( 'new_usertype' );
			$gid				=	$_CB_framework->acl->get_group_id( $usertype, 'ARO' );
			$gids				=	array( $gid );
		} else {
			if ( checkJversion() >= 2 ) {
				$gids			=	explode( '|*|', $usergroup );
				$gid			=	$_CB_framework->acl->getBackwardsCompatibleGid( $gids );
				$usertype		=	$_CB_framework->acl->get_group_name( $gid );
			} else {
				$usertype		=	$_CB_framework->acl->get_group_name( $usergroup );
				$gid			=	$usergroup;
				$gids			=	array( $gid );
			}
		}

		if ( ! $usertype ) {
			$usertype			=	'Registered';
			$gid				=	$_CB_framework->acl->get_group_id( $usertype, 'ARO' );
			$gids				=	array( $gid );
		}

		$user->set( 'usertype', $usertype );
		$user->set( 'gid', $gid );
		$user->set( 'gids', $gids );
		$user->set( 'sendEmail', 0 );
		$user->set( 'registerDate', date( 'Y-m-d H:i:s' ) );
		$user->set( 'username', $username );
		$user->set( 'firstname', $firstname );
		$user->set( 'middlename', $middlename );
		$user->set( 'lastname', $lastname );
		$user->set( 'name', $connectUser->name );
		$user->set( 'email', $connectEmail );
		$user->set( 'password', $user->hashAndSaltPassword( md5( $connectUser->id . $secret ) ) );
		$user->set( 'avatar', $this->setAvatar( $connectUser ) );
		$user->set( 'registeripaddr', cbGetIPlist() );
		$user->set( $fieldname, $connectUser->id );

		if ( $approval == 0 ) {
			$user->set( 'approved', 1 );
		} else {
			$user->set( 'approved', 0 );
		}

		if ( $confirmation == 0 ) {
			$user->set( 'confirmed', 1 );
		} else {
			$user->set( 'confirmed', 0 );
		}

		if ( ( $user->get( 'confirmed' ) == 1 ) && ( $user->get( 'approved' ) == 1 ) ) {
			$user->set( 'block', 0 );
		} else {
			$user->set( 'block', 1 );
		}

		$_PLUGINS->trigger( 'onBeforeUserRegistration', array( &$user, &$user ) );

		if ( $user->store() ) {
			if ( ( $user->confirmed == 0 ) && ( $confirmation != 0 ) ) {
				$user->_setActivationCode();

				if ( ! $user->store() ) {
					return false;
				}
			}

			$messagesToUser		=	activateUser( $user, 1, 'UserRegistration' );

			$_PLUGINS->trigger( 'onAfterUserRegistration', array( &$user, &$user, true ) );

			return $messagesToUser;
		}

		return false;
	}

	private function updateUser( &$user ) {
		global $_PLUGINS, $ueConfig;

		$fieldname			=	$this->gen->fieldname;
		$connectUserid		=	$this->getConnectID();
		$oldUserComplete	=	$user;

		$_PLUGINS->trigger( 'onBeforeUserUpdate', array( &$user, &$user, &$oldUserComplete, &$oldUserComplete ) );

		if ( $user->get( $fieldname ) != $connectUserid ) {
			$user->set( $fieldname, $connectUserid );

			if ( $ueConfig['reg_enable_toc'] && ( $user->get( 'acceptedterms' ) != 1 ) ) {
				$user->set( 'acceptedterms', 1 );
			}

			if ( ! $user->store() ) {
				return false;
			}
		}

		$_PLUGINS->trigger( 'onAfterUserUpdate', array( &$user, &$user, $oldUserComplete ) );

		return true;
	}

	private function loginUser( &$user  ) {
		$fieldname				=	$this->gen->fieldname;

		if ( $user->get( $fieldname ) == $this->getConnectID() ) {
			cbimport( 'cb.authentication' );

			$cbAuthenticate		=	new CBAuthentication();

			$messagesToUser		=	array();
			$alertMessages		=	array();
			$redirectUrl		=	cbfacebookconnectClass::setReturnURL( true );
			$resultError		=	$cbAuthenticate->login( $user->get( 'username' ), false, 0, 1, $redirectUrl, $messagesToUser, $alertMessages, 1 );

			if ( $resultError || ( count( $messagesToUser ) > 0 ) ) {
				$error			=	null;

				if ( $resultError ) {
					$error		.=	$resultError;
				}

				if ( count( $messagesToUser ) > 0 ) {
					if ( $resultError ) {
						$error	.=	'<br />';
					}

					$error		.=	stripslashes( implode( '<br />', $messagesToUser ) );
				}

				$user->set( '_error', $error );
			} else {
				return ( count( $alertMessages ) > 0 ? stripslashes( implode( '<br />', $alertMessages ) ) : true );
			}
		}

		return false;
	}

	public function setAvatar( $connectUser ) {
		global $_CB_framework, $ueConfig;

		$avatarImg									=	$connectUser->avatar;

		if ( $avatarImg ) {
			$avatar_name							=	$connectUser->id;

			cbimport( 'cb.snoopy' );

			$snoopy									=	new CBSnoopy;
			$snoopy->read_timeout					=	30;

			$snoopy->fetch( $avatarImg );

			if ( ( ! $snoopy->results ) && stristr( $avatarImg, 'https://' ) ) {
				$snoopy->fetch( str_replace( 'https://', 'http://', $avatarImg ) );
			}

			if ( ! $snoopy->error ) {
				$headers							=	$snoopy->headers;

				if ( $headers ) foreach( $headers as $header ) {
					if ( preg_match( '/^Content-Type:/', $header ) ) {
						if ( preg_match( '/image\/(\w+)/', $header, $matches ) ) {
							if ( isset( $matches[1] ) ) {
								$ext				=	$matches[1];
							}
						}
					}
				}

				if ( isset( $ext ) ) {
					$ext							=	strtolower( $ext );

					if ( ! in_array( $ext, array( 'jpeg', 'jpg', 'png', 'gif' ) ) ) {
						return null;
					}

					cbimport( 'cb.imgtoolbox' );

					$path							=	$_CB_framework->getCfg( 'absolute_path' ) . '/images/comprofiler/';
					$allwaysResize					=	( isset( $ueConfig['avatarResizeAlways'] ) ? $ueConfig['avatarResizeAlways'] : 1 );

					$imgToolBox						=	new imgToolBox();
					$imgToolBox->_conversiontype	=	$ueConfig['conversiontype'];
					$imgToolBox->_IM_path			=	$ueConfig['im_path'];
					$imgToolBox->_NETPBM_path		=	$ueConfig['netpbm_path'];
					$imgToolBox->_maxsize			=	$ueConfig['avatarSize'];
					$imgToolBox->_maxwidth			=	$ueConfig['avatarWidth'];
					$imgToolBox->_maxheight			=	$ueConfig['avatarHeight'];
					$imgToolBox->_thumbwidth		=	$ueConfig['thumbWidth'];
					$imgToolBox->_thumbheight		=	$ueConfig['thumbHeight'];
					$imgToolBox->_debug				=	0;

					$image							=	array( 'name' => $avatar_name . '.' . $ext, 'tmp_name' => $snoopy->results );
					$newFileName					=	$imgToolBox->processImage( $image, $avatar_name, $path, 0, 0, 4, $allwaysResize );

					if ( $newFileName ) {
						return $newFileName;
					}
				}
			}
		}

		return null;
	}
}

class cbfacebookconnectGeneral extends cbfacebookconnectSynchronize {

	public function getUserID( $id = null ) {
		global $_CB_database;

		static $cache		=	array();

		if ( $id === null ) {
			$id				=	$this->getConnectID();
		}

		if ( ! isset( $cache[$id] ) ) {
			$userId			=	null;

			if ( $id ) {
				$query		=	'SELECT ' . $_CB_database->NameQuote( 'id' )
							.	"\n FROM " . $_CB_database->NameQuote( '#__comprofiler' )
							.	"\n WHERE " . $_CB_database->NameQuote( $this->gen->fieldname ) . " = " . $_CB_database->Quote( $id );
				$_CB_database->setQuery( $query );
				$userId		=	$_CB_database->loadResult();
			}

			$cache[$id]		=	$userId;
		}

		return $cache[$id];
	}

	public function getUser( $id = null ) {
		static $cache		=	array();

		if ( $id === null ) {
			$id				=	$this->getUserID();
		}

		if ( ! isset( $cache[$id] ) ) {
			$cache[$id]		=&	CBuser::getUserDataInstance( (int) $id );
		}

		return $cache[$id];
	}

	public function getToken() {
		static $cache	=	null;

		if ( ! isset( $cache ) ) {
			$session	=	$this->getSession();

			if ( $session ) {
				$cache	=	stripslashes( cbGetParam( $session, 'access_token', null ) );
			}
		}

		return $cache;
	}

	public function getConnectID() {
		static $cache	=	null;

		if ( ! isset( $cache ) ) {
			$session	=	$this->getSession();

			if ( $session ) {
				$cache	=	stripslashes( cbGetParam( $session, 'user_id', null ) );
			}
		}

		return $cache;
	}

	public function getRawSession() {
		static $cache		=	null;

		if ( ! isset( $cache ) ) {
			$session		=	$this->getSession();

			if ( $session ) {
				if ( isset( $session['raw'] ) ) {
					$cache	=	(array) json_decode( $session['raw'] );
				} else {
					$cache	=	array();
				}
			}
		}

		return $cache;
	}

	public function setSession( $userId = null, $accessToken = null, $raw = null ) {
		$cookie				=	array(	'user_id' => $userId,
										'access_token' => $accessToken,
										'raw' => ( $raw ? json_encode( $raw ) : null ),
										'signature' => md5( $userId . $this->gen->secret )
									);

		if ( class_exists( 'JFactory' ) ) {
			$session		=	JFactory::getSession();

			$session->set( $this->gen->session_id, $cookie );
		} else {
			cbimport( 'cb.session' );

			CBCookie::setcookie( $this->gen->session_id, http_build_query( $cookie, null, '&' ) );
		}
	}

	public function resetSession() {
		$this->setSession();
	}

	public function getSession() {
		static $cache			=	null;

		if ( ! isset( $cache ) ) {
			if ( class_exists( 'JFactory' ) ) {
				$sessions		=	JFactory::getSession();
				$session		=	$sessions->get( $this->gen->session_id );
			} else {
				$cookie			=	stripslashes( cbGetParam( $_COOKIE, $this->gen->session_id, null, _CB_ALLOWRAW ) );

				if ( $cookie ) {
					parse_str( $cookie, $session );
				} else {
					$session	=	null;
				}
			}

			if ( $session ) {
				$signature		=	md5( stripslashes( cbGetParam( $session, 'user_id', null ) ) . $this->gen->secret );

				if ( $signature === stripslashes( cbGetParam( $session, 'signature', null ) ) ) {
					$cache		=	$session;
				}
			}
		}

		return $cache;
	}

	public function showRegistration() {
		global $_CB_framework, $_CB_database, $ueConfig;

		$return								=	null;

		if ( ! $_CB_framework->myId() ) {
			$user							=	$this->getUser();
			$connectUser					=	$this->getConnectUser( null, $user );

			if ( ( ! $user->id ) && $connectUser ) {
				$plugin						=	cbfacebookconnectClass::getPlugin();

				$_CB_framework->document->addHeadStyleSheet( $plugin->livePath . '/css/registration.css' );

				$dummyUser					=	new moscomprofilerUser( $_CB_database );
				$emailExists				=	$dummyUser->loadByEmail( $connectUser->email );
				$emailInvalid				=	preg_match( '/@invalid(?:\.com)?|cb\.invalid$/', $connectUser->email );

				$js							=	"$( '#connectForm' ).validate( {"
											.		"submitHandler: function( form ) {"
											.			"$( form ).find( 'input[type=\"submit\"]' ).attr( 'disabled', 'disabled' ).addClass( 'disabled' ).val( '" . addslashes( CBTxt::T( 'Loading...' ) ) . "' );"
											.			"form.submit();"
											.		"},"
											.		"rules: {"
											.			"cbconnect_email: { required: true, email: true },"
											.			"cbconnect_email_confirm: { equalTo: '#cbconnect_email' },"
											.			"cbconnect_tos: { required: true }"
											.		"},"
											.		"ignoreTitle: true,"
											.		"errorClass: 'connectValidationError',"
											.		"highlight: function( element, errorClass ) {"
											.			"$( element ).parent().parent().addClass( 'error');"
											.		"},"
											.		"unhighlight: function( element, errorClass ) {"
											.			"$( element ).parent().parent().removeClass( 'error' );"
											.		"},"
											.		"errorElement: 'div',"
											.		"errorPlacement: function( error, element ) {"
											.			"$( element ).parent().children().last().after( error );"
											.		"}"
											.	"});"
											.	"$( '#connectFormLink' ).validate( {"
											.		"submitHandler: function( form ) {"
											.			"$( form ).find( 'input[type=\"submit\"]' ).attr( 'disabled', 'disabled' ).addClass( 'disabled' ).val( '" . addslashes( CBTxt::T( 'Loading...' ) ) . "' );"
											.			"form.submit();"
											.		"},"
											.		"rules: {"
											.			"cbconnect_username: { required: true },"
											.			"cbconnect_password: { required: true }"
											.		"},"
											.		"ignoreTitle: true,"
											.		"errorClass: 'connectValidationError',"
											.		"highlight: function( element, errorClass ) {"
											.			"$( element ).parent().parent().addClass( 'error');"
											.		"},"
											.		"unhighlight: function( element, errorClass ) {"
											.			"$( element ).parent().parent().removeClass( 'error' );"
											.		"},"
											.		"errorElement: 'div',"
											.		"errorPlacement: function( error, element ) {"
											.			"$( element ).parent().children().last().after( error );"
											.		"}"
											.	"});"
											.	"$.extend( jQuery.validator.messages, {"
											.		"required: '" . addslashes( CBTxt::T( 'This field is required.' ) ) . "',"
											.		"remote: '" . addslashes( CBTxt::T( 'Please fix this field.' ) ) . "',"
											.		"email: '" . addslashes( CBTxt::T( 'Please enter a valid email address.' ) ) . "',"
											.		"url: '" . addslashes( CBTxt::T( 'Please enter a valid URL.' ) ) . "',"
											.		"date: '" . addslashes( CBTxt::T( 'Please enter a valid date.' ) ) . "',"
											.		"dateISO: '" . addslashes( CBTxt::T( 'Please enter a valid date (ISO).' ) ) . "',"
											.		"number: '" . addslashes( CBTxt::T( 'Please enter a valid number.' ) ) . "',"
											.		"digits: '" . addslashes( CBTxt::T( 'Please enter only digits.' ) ) . "',"
											.		"creditcard: '" . addslashes( CBTxt::T( 'Please enter a valid credit card number.' ) ) . "',"
											.		"equalTo: '" . addslashes( CBTxt::T( 'Please enter the same value again.' ) ) . "',"
											.		"accept: '" . addslashes( CBTxt::T( 'Please enter a value with a valid extension.' ) ) . "',"
											.		"maxlength: $.validator.format('" . addslashes( CBTxt::T( 'Please enter no more than {0} characters.' ) ) . "'),"
											.		"minlength: $.validator.format('" . addslashes( CBTxt::T( 'Please enter at least {0} characters.' ) ) . "'),"
											.		"rangelength: $.validator.format('" . addslashes( CBTxt::T( 'Please enter a value between {0} and {1} characters long.' ) ) . "'),"
											.		"range: $.validator.format('" . addslashes( CBTxt::T( 'Please enter a value between {0} and {1}.' ) ) . "'),"
											.		"max: $.validator.format('" . addslashes( CBTxt::T( 'Please enter a value less than or equal to {0}.' ) ) . "'),"
											.		"min: $.validator.format('" . addslashes( CBTxt::T( 'Please enter a value greater than or equal to {0}.' ) ) . "')"
											.	"});";

				$_CB_framework->outputCbJQuery( $js, 'validate' );

				$return						.=	'<div class="cbConnect cb_template_' . selectTemplate( 'dir' ) . '">'
											.		'<div class="cbConnectInner">';

				if ( $emailExists || $emailInvalid || $ueConfig['reg_enable_toc'] ) {
					$return					.=			'<form action="' . cbfacebookconnectClass::getPluginURL( array( $this->type, 'storeregistration' ), null, false, false, null, 'current' ) . '" method="post" enctype="multipart/form-data" name="connectForm" id="connectForm" class="connectForm form-horizontal">'
											.				'<div class="connectHeader page-header"><h4>' . CBTxt::T( 'Registration' ) . '</h4></div>';

					if ( $emailExists || $emailInvalid ) {
						$return				.=				'<div class="connectEditContentInput control-group">'
											.					'<label class="connectEditContentInputTitle control-label">' . CBTxt::T( 'E-mail Address' ) . '</label>'
											.					'<div class="connectEditContentInputField controls">'
											.						'<input type="text" id="cbconnect_email" name="cbconnect_email" value="" class="inputbox" size="30" />'
											.					'</div>'
											.				'</div>'
											.				'<div class="connectEditContentInput control-group">'
											.					'<label class="connectEditContentInputTitle control-label">' . CBTxt::T( 'Confirm E-mail Address' ) . '</label>'
											.					'<div class="connectEditContentInputField controls">'
											.						'<input type="text" id="cbconnect_email_confirm" name="cbconnect_email_confirm" value="" class="inputbox" size="30" />'
											.					'</div>'
											.				'</div>';
					}

					if ( $ueConfig['reg_enable_toc'] ) {
						$return				.=				'<div class="connectEditContentInput control-group">'
											.					'<label class="connectEditContentInputTitle control-label"><input type="checkbox" id="cbconnect_tos" name="cbconnect_tos" value="1" class="inputbox" /></label>'
											.					'<div class="connectEditContentInputField controls">'
											.						sprintf( _UE_TOC_LINK, '<a href="' . cbSef( htmlspecialchars( $ueConfig['reg_toc_url'] ) ) . '" target="_BLANK"> ', '</a>' )
											.					'</div>'
											.				'</div>';
					}

					$return					.=				'<div class="connectButtonWrapper form-actions">'
											.					'<input type="submit" value="' . htmlspecialchars( CBTxt::T( 'Register' ) ) . '" class="connectButton connectButtonSubmit btn btn-primary" />'
											.				'</div>'
											.				cbGetSpoofInputTag( 'plugin' )
											.			'</form>';
				}

				if ( $this->sync->link ) {
					switch ( isset( $ueConfig['login_type'] ) ? $ueConfig['login_type'] : 0 ) {
						case 2:
							$userNameText	=	_UE_EMAIL;
							break;
						case 1:
							if ( ! defined( '_UE_USERNAME_OR_EMAIL' ) ) {
								DEFINE('_UE_USERNAME_OR_EMAIL','Username or email');
							}

							$userNameText	=	_UE_USERNAME_OR_EMAIL;
							break;
						case 0:
						default:
							$userNameText	=	_UE_USERNAME;
							break;
					}

					$return					.=			'<form action="' . cbfacebookconnectClass::getPluginURL( array( $this->type, 'storeregistration' ), null, false, false, null, 'current' ) . '" method="post" enctype="multipart/form-data" name="connectFormLink" id="connectFormLink" class="connectFormLink form-horizontal">'
											.				'<div class="connectHeader page-header"><h4>' . CBTxt::T( 'Login' ) . '</h4></div>'
											.				'<div class="connectEditContentInput control-group">'
											.					'<label class="connectEditContentInputTitle control-label">' . $userNameText . '</label>'
											.					'<div class="connectEditContentInputField controls">'
											.						'<input type="text" id="cbconnect_username" name="cbconnect_username" value="" class="inputbox" size="30" />'
											.					'</div>'
											.				'</div>'
											.				'<div class="connectEditContentInput control-group">'
											.					'<label class="connectEditContentInputTitle control-label">' . _UE_PASS . '</label>'
											.					'<div class="connectEditContentInputField controls">'
											.						'<input type="password" id="cbconnect_password" name="cbconnect_password" value="" class="inputbox" size="30" />'
											.					'</div>'
											.				'</div>'
											.				'<div class="connectButtonWrapper form-actions">'
											.					'<input type="submit" value="' . htmlspecialchars( CBTxt::T( 'Link' ) ) . '" class="connectButton connectButtonSubmit btn btn-primary" />'
											.				'</div>'
											.				cbGetSpoofInputTag( 'plugin' )
											.			'</form>';
				}

				$return						.=		'</div>'
											.	'</div>';
			}
		}

		if ( ! $return ) {
			cbfacebookconnectClass::setRedirect( null, CBTxt::T( 'Not authorized.' ), 'error' );
		} else {
			echo $return;
		}
	}

	public function storeRegistration() {
		global $_CB_framework, $_CB_database, $ueConfig;

		$post										=	array();
		$userId										=	null;
		$error										=	null;

		if ( ! $_CB_framework->myId() ) {
			$user									=	$this->getUser();
			$connectUser							=	$this->getConnectUser( null, $user );

			if ( ( ! $user->id ) && $connectUser ) {
				$username							=	stripslashes( cbGetParam( $_POST, 'cbconnect_username', null ) );
				$password							=	stripslashes( cbGetParam( $_POST, 'cbconnect_password', null ) );

				if ( $username || $password ) {
					$valid							=	false;
					$dummyUser						=	new moscomprofilerUser( $_CB_database );
					$foundUser						=	$dummyUser->loadByUsername( $username );

					if ( ! $foundUser ) {
						$foundUser					=	$dummyUser->loadByEmail( $username );
					}

					if ( $foundUser && $dummyUser->verifyPassword( $password )  ) {
						$valid						=	true;
					}

					if ( ! $valid ) {
						$error						=	CBTxt::T( 'Invalid login credentials! Please login to link an existing account or supply a valid email address to complete registration.' );
					} else {
						$userId						=	$dummyUser->get( 'id' );
					}
				} else {
					$dummyUserA						=	new moscomprofilerUser( $_CB_database );
					$acceptTerms					=	true;

					if ( $ueConfig['reg_enable_toc'] ) {
						$termsAndConditions			=	(int) stripslashes( cbGetParam( $_POST, 'cbconnect_tos', null ) );

						if ( ! $termsAndConditions ) {
							$acceptTerms			=	false;
						} else {
							$post['acceptedterms']	=	$termsAndConditions;
						}
					}

					$emailExists					=	false;
					$emailInvalid					=	false;

					if ( $dummyUserA->loadByEmail( $connectUser->email ) || preg_match( '/@invalid(?:\.com)?|cb\.invalid$/', $connectUser->email ) ) {
						$email						=	stripslashes( cbGetParam( $_POST, 'cbconnect_email', null ) );
						$confirmEmail				=	stripslashes( cbGetParam( $_POST, 'cbconnect_email_confirm', null ) );

						if ( ( $email && cbIsValidEmail( $email ) ) && ( $confirmEmail && cbIsValidEmail( $confirmEmail ) ) && ( $email == $confirmEmail ) ) {
							$dummyUserB				=	new moscomprofilerUser( $_CB_database );

							if ( $dummyUserB->loadByEmail( $email ) ) {
								$emailExists		=	true;
							} else {
								$post['email']		=	$email;
							}
						} else {
							$emailInvalid			=	true;
						}
					}

					if ( $emailExists ) {
						$error						.=	CBTxt::T( 'This email address is already in use!' );

						if ( $this->sync->link ) {
							$error					.=	' ' . CBTxt::T( 'Please login to link an existing account or supply a valid email address to complete registration.' );
						} else {
							$error					.=	' ' . CBTxt::T( 'Please supply a valid email address to complete registration.' );
						}
					} elseif ( $emailInvalid || ( ! $acceptTerms ) ) {
						$error						.=	CBTxt::T( 'Your registration is not yet complete.' );

						if ( $emailInvalid && ( ! $acceptTerms ) ) {
							if ( $this->sync->link ) {
								$error				.=	' ' . CBTxt::T( 'Please login to link to an existing account or supply a valid email address and review the terms and conditions to complete registration.' );
							} else {
								$error				.=	' ' . CBTxt::T( 'Please supply a valid email address and review the terms and conditions to complete registration.' );
							}
						} elseif ( $emailInvalid ) {
							if ( $this->sync->link ) {
								$error				.=	' ' . CBTxt::T( 'Please login to link to an existing account or supply a valid email address to complete registration.' );
							} else {
								$error				.=	' ' . CBTxt::T( 'Please supply a valid email address.' );
							}
						} elseif ( ! $acceptTerms ) {
							if ( $this->sync->link ) {
								$error				.=	' ' . CBTxt::T( 'Please login to link to an existing account or review the terms and conditions to complete registration.' );
							} else {
								$error				.=	' ' . CBTxt::T( 'Please review the terms and conditions to complete registration.' );
							}
						}
					}
				}
			}
		}

		if ( $error ) {
			cbfacebookconnectClass::getPluginURL( array( $this->type, 'registration' ), $error, false, true, 'error', 'current' );
		} else {
			$this->syncUser( $userId, $post );
		}
	}

	public function showButton( $horizontal = 0, $compact = 0 ) {
		global $_CB_framework;

		$api					=	$this->getInstance();
		$return					=	null;

		if ( $api->loadAPI() ) {
			$user				=&	CBuser::getUserDataInstance( $_CB_framework->myId() );
			$fieldname			=	$this->gen->fieldname;
			$button				=	$this->sync->button;

			if ( $user->id ) {
				if ( $this->sync->link && ( ! $user->$fieldname ) && ( ! $api->getUserID() ) ) {
					if ( ( ! $button ) || ( ( $button == 2 ) && $horizontal ) || ( ( $button == 3 ) && $compact ) ) {
						$return	=	'<a href="javascript: void(0);" class="' . $this->type . '_button_small" title="' . htmlspecialchars( CBTxt::P( 'Link your [sitename] account.', array( '[sitename]' => $this->name ) ) ) . '"></a>';
					} else {
						$return	=	'<div class="' . $this->type . '_button_bg"><a href="javascript: void(0);" class="' . $this->type . '_button" title="' . htmlspecialchars( CBTxt::P( 'Link your [sitename] account.', array( '[sitename]' => $this->name ) ) ) . '">' . CBTxt::T( ( $this->sync->button_link ? $this->sync->button_link : 'Link' ) ) . '</a></div>';
					}
				}
			} else {
				if ( ( ! $button ) || ( ( $button == 2 ) && $horizontal ) || ( ( $button == 3 ) && $compact ) ) {
					$return		=	'<a class="' . $this->type . '_button_small" title="' . htmlspecialchars( CBTxt::P( 'Login with your [sitename] account.', array( '[sitename]' => $this->name ) ) ) . '"></a>';
				} else {
					$return		=	'<div class="' . $this->type . '_button_bg"><a href="javascript: void(0);" class="' . $this->type . '_button" title="' . htmlspecialchars( CBTxt::P( 'Login with your [sitename] account.', array( '[sitename]' => $this->name ) ) ) . '">' . CBTxt::T( ( $this->sync->button_signin ? $this->sync->button_signin : 'Sign in' ) ) . '</a></div>';
				}
			}
		}

		if ( $horizontal ) {
			$return				=	'<span>' . $return . '</span>';
		} else {
			$return				=	'<div>' . $return . '</div>';
		}

		return $return;
	}
}

class cbfacebookconnect extends cbfacebookconnectGeneral {
	var $name	=	null;
	var $type	=	null;
	var $api	=	null;
	var $gen	=	null;
	var $sync	=	null;

	public function __construct() {
		$plugin							=	cbfacebookconnectClass::getPlugin();

		$this->name						=	'Facebook';
		$this->type						=	'facebook';

		$this->api						=	new stdClass();
		$this->api->application_id		=	$plugin->params->get( 'fb_app_id', null );
		$this->api->application_secret	=	$plugin->params->get( 'fb_app_secretkey', null );
		$this->api->enabled				=	$plugin->params->get( 'fb_app_enabled', 1 );

		$this->gen						=	new stdClass();
		$this->gen->fieldname			=	'fb_userid';
		$this->gen->session_id			=	'cbfacebookconnect_facebook';
		$this->gen->secret				=	md5( $this->api->application_secret );

		$this->sync						=	new stdClass();
		$this->sync->register			=	$plugin->params->get( 'fb_register', 1 );
		$this->sync->usergroup			=	$plugin->params->get( 'fb_reg_usergroup', null );
		$this->sync->approve			=	$plugin->params->get( 'fb_reg_approve', 0 );
		$this->sync->confirm			=	$plugin->params->get( 'fb_reg_confirm', 0 );
		$this->sync->link				=	$plugin->params->get( 'fb_link', 1 );
		$this->sync->firstlogin			=	$plugin->params->get( 'fb_redirect_firstlog', null );
		$this->sync->login				=	$plugin->params->get( 'fb_redirect_log', null );
		$this->sync->button				=	$plugin->params->get( 'fb_button', 2 );
		$this->sync->button_signin		=	$plugin->params->get( 'fb_button_signin', null );
		$this->sync->button_link		=	$plugin->params->get( 'fb_button_link', null );
	}

	static public function getInstance() {
		static $cache	=	null;

		if ( ! isset( $cache ) ) {
			$cache		=	new cbfacebookconnect();
		}

		return $cache;
	}

	public function loadAPI() {
		global $_CB_framework;

		static $cache							=	null;

		if ( ! isset( $cache ) ) {
			$plugin								=	cbfacebookconnectClass::getPlugin();

			if ( $this->api->enabled && $this->api->application_id && $this->api->application_secret ) {
				static $JS_loaded				=	0;

				if ( ! $JS_loaded++ ) {
					$_CB_framework->addJQueryPlugin( 'oauthpopup', $plugin->livePath . '/js/jquery.oauthpopup.js' );

					$urlParams					=	array(	'response_type=code',
															'client_id=' . urlencode( $this->api->application_id ),
															'redirect_uri=' . urlencode( cbfacebookconnectClass::getEndpointURL( 'facebook', 'accesstoken' ) ),
															'scope=email',
															'state=' . urlencode( md5( uniqid( $this->api->application_secret ) ) ),
															'display=popup'
														);

					$API_js						=	"$( '.facebook_button,.facebook_button_small' ).oauthpopup({"
												.		"url: 'https://www.facebook.com/dialog/oauth?" . addslashes( implode( '&', $urlParams ) ) . "',"
												.		"name: 'facebook_oAuthLogin',"
												.		"callback: function( success, error, oAuthWindow ) {"
												.			"if ( success == true ) {"
												.				"window.location = '" . addslashes( cbfacebookconnectClass::getPluginURL( array( 'facebook' ), null, false, false, null, true ) ) . "';"
												.			"} else {"
												.				( $_CB_framework->getCfg( 'debug' ) ? "console.log( error );" : null )
												.				"window.location.reload();"
												.			"}"
												.		"}"
												.	"});"
												.	"$( '.facebook_unlink' ).click( function() {"
												.		"if ( confirm( '" . addslashes( CBTxt::P( 'Are you sure you want to unjoin [live_site]?', array( '[live_site]' => $_CB_framework->getCfg( 'live_site' ) ) ) ) . "' ) ) {"
												.			"$.ajax({"
												.				"url: 'https://graph.facebook.com/me/permissions?method=delete&access_token=" . addslashes( urlencode( $this->getToken() ) ) . "'"
												.			"}).complete( function( jqXHR, textStatus ) {"
												.				"$.ajax({"
												.					"url: '" . addslashes( cbfacebookconnectClass::getEndpointURL( 'facebook', 'reset' ) ) . "'"
												.				"}).complete( function( jqXHR, textStatus ) {"
												.					"window.location.reload();"
												.				"});"
												.			"});"
												.		"}"
												.	"});";

					$_CB_framework->outputCbJQuery( $API_js, 'oauthpopup' );
				}

				$cache							=	true;
			} else {
				$cache							=	false;
			}
		}

		return $cache;
	}

	public function getConnectUser( $id = null, $user = null ) {
		static $cache							=	array();

		if ( $id === null ) {
			$id									=	'me';
		}

		if ( ! isset( $cache[$id] ) ) {
			$cache[$id]							=	false;

			if ( $this->getConnectID() == $id ) {
				$id								=	'me';
			} else {
				if ( $id != 'me' ) {
					$id							=	urlencode( $id );
				}
			}

			$token								=	$this->getToken();

			if ( $token ) {
				$token							=	'&access_token=' . urlencode( $token );
			}

			$fields								=	array( 'id', 'username', 'name', 'email', 'picture.type(large)' );
			$request							=	cbfacebookconnectClass::httpRequest( 'https://graph.facebook.com/' . $id . '?fields=' . implode( ',', $fields ) . $token );
			$resultsArray						=	(array) json_decode( $request['results'] );

			if ( ( $request['http_code'] == 200 ) && ( ! $request['error'] ) ) {
				$sessionArray					=	$this->getRawSession();

				$cache[$id]						=	new stdClass();
				$cache[$id]->id					=	stripslashes( cbGetParam( $resultsArray, 'id', null ) );

				if ( ( ! $cache[$id]->id ) && ( $id == 'me' ) ) {
					$cache[$id]->id				=	stripslashes( cbGetParam( $sessionArray, 'id', null ) );
				}

				$cache[$id]->username			=	preg_replace( '/\W/', '', str_replace( ' ', '_', stripslashes( cbGetParam( $resultsArray, 'username', null ) ) ) );

				if ( ( ! $cache[$id]->username ) && ( $id == 'me' ) ) {
					$cache[$id]->username		=	preg_replace( '/\W/', '', str_replace( ' ', '_', stripslashes( cbGetParam( $sessionArray, 'username', null ) ) ) );
				}

				if ( ! $cache[$id]->username ) {
					$cache[$id]->username		=	preg_replace( '/\W/', '', str_replace( ' ', '_', stripslashes( cbGetParam( $resultsArray, 'name', null ) ) ) );
				}

				if ( ( ! $cache[$id]->username ) && ( $id == 'me' ) ) {
					$cache[$id]->username		=	preg_replace( '/\W/', '', str_replace( ' ', '_', stripslashes( cbGetParam( $sessionArray, 'name', null ) ) ) );
				}

				$cache[$id]->name				=	stripslashes( cbGetParam( $resultsArray, 'name', null ) );

				if ( ( ! $cache[$id]->name ) && ( $id == 'me' ) ) {
					$cache[$id]->name			=	stripslashes( cbGetParam( $sessionArray, 'name', null ) );
				}

				$picture						=	cbGetParam( $resultsArray, 'picture', null );

				if ( isset( $picture->data ) ) {
					$cache[$id]->avatar			=	stripslashes( cbGetParam( get_object_vars( $picture->data ), 'url', null ) );
				} elseif ( is_string( $picture ) ) {
					$cache[$id]->avatar			=	stripslashes( $picture );
				} else {
					$cache[$id]->avatar			=	null;
				}

				$cache[$id]->email				=	stripslashes( cbGetParam( $resultsArray, 'email', null ) );

				if ( ( ! $cache[$id]->email ) && ( $id == 'me' ) ) {
					$cache[$id]->email			=	stripslashes( cbGetParam( $sessionArray, 'email', null ) );
				}

				if ( ! $cache[$id]->email ) {
					$cache[$id]->email			=	$cache[$id]->id . '@cb.invalid';
				}

				$cache[$id]->user				=	$resultsArray;
			} elseif ( $user ) {
				if ( $request['results'] ) {
					$resultError				=	cbGetParam( $resultsArray, 'error', null );

					if ( $resultError ) {
						if ( isset( $resultError->message ) ) {
							$user->_error		=	stripslashes( $resultError->message );
						}
					}
				} elseif ( $request['error'] ) {
					$user->_error				=	$request['error'];
				}
			}
		}

		return $cache[$id];
	}

	public function accessToken() {
		$success							=	'false';
		$error								=	null;

		if ( $this->api->enabled && $this->api->application_id && $this->api->application_secret ) {
			$errorResponse					=	stripslashes( cbGetParam( $_GET, 'error', null ) );

			if ( ! $errorResponse ) {
				$request					=	array(	'code=' . urlencode( stripslashes( cbGetParam( $_GET, 'code', null, _CB_ALLOWRAW ) ) ),
														'client_id=' . urlencode( $this->api->application_id ),
														'client_secret=' . urlencode( $this->api->application_secret ),
														'redirect_uri=' . urlencode( cbfacebookconnectClass::getEndpointURL( 'facebook', 'accesstoken' ) ),
														'grant_type=authorization_code'
													);

				$request					=	cbfacebookconnectClass::httpRequest( 'https://graph.facebook.com/oauth/access_token', 'POST', implode( '&', $request ) );

				if ( ( $request['http_code'] == 200 ) && ( ! $request['error'] ) ) {
					$resultsArray			=	array();

					parse_str( $request['results'], $resultsArray );

					$accessToken			=	stripslashes( cbGetParam( $resultsArray, 'access_token', null ) );
					$request				=	cbfacebookconnectClass::httpRequest( 'https://graph.facebook.com/me?access_token=' . $accessToken );
					$resultsArray			=	(array) json_decode( $request['results'] );

					if ( ( $request['http_code'] == 200 ) && ( ! $request['error'] ) ) {
						$userId				=	stripslashes( cbGetParam( $resultsArray, 'id', null ) );

						$this->setSession( $userId, $accessToken, $resultsArray );

						$success			=	'true';
					} else {
						if ( $request['results'] ) {
							$resultError	=	cbGetParam( $resultsArray, 'error', null );

							if ( $resultError ) {
								if ( isset( $resultError->message ) ) {
									$error	=	stripslashes( $resultError->message );
								}
							}
						}

						if ( ! $error ) {
							$error			=	$request['error'];
						}
					}
				} else {
					if ( $request['results'] ) {
						$resultsArray		=	(array) json_decode( $request['results'] );
						$resultError		=	cbGetParam( $resultsArray, 'error', null );

						if ( $resultError ) {
							if ( isset( $resultError->message ) ) {
								$error		=	stripslashes( $resultError->message );
							}
						}
					}

					if ( ! $error ) {
						$error				=	$request['error'];
					}
				}
			} else {
				$error						=	$errorResponse . ': ' . stripslashes( cbGetParam( $_GET, 'error_description', null ) );
			}
		}

		$js									=	"window.opener.oAuthSuccess = $success;"
											.	( $error ? "window.opener.oAuthError = '" . addslashes( $error ). "';" : null )
											.	"window.close();";

		echo '<script type="text/javascript">' . $js . '</script>';
	}
}

class cbfacebookconnectPlugin extends cbPluginHandler {

	public function getDisplay( $name_lenght, $pass_lenght, $horizontal, $class_sfx, $params ) {
		global $_CB_framework;

		$plugin				=	cbfacebookconnectClass::getPlugin();

		static $CSS_loaded	=	0;

		if ( ! $CSS_loaded++ ) {
			$_CB_framework->document->addHeadStyleSheet( $plugin->livePath . '/css/buttons.css' );
		}

		$return				=	cbfacebookconnect::getInstance()->showButton( $horizontal, $params->get( 'compact', 0 ) );

		return ( ! $_CB_framework->myId() ? array( 'afterButton' => $return ) : $return );
	}

	public function getMenu( $user ) {
		$plugin							=	cbfacebookconnectClass::getPlugin();
		$fbId							=	$user->get( 'fb_userid' );

		if ( $fbId ) {
			$fbUserid					=	cbfacebookconnect::getInstance()->getConnectID();

			if ( $fbUserid && ( $fbId == $fbUserid ) ) {
				if ( $plugin->params->get( 'facebook_unlink', 1 ) ) {
					$unjoin				=	array();
					$unjoin['arrayPos']	=	array( '_UE_MENU_EDIT' => array( '_UE_MENU_FBC_UNJOIN facebook_unlink' => null ) );
					$unjoin['position']	=	'menuBar';
					$unjoin['caption']	=	htmlspecialchars( CBTxt::T( 'Unjoin this site' ) );
					$unjoin['url']		=	"javascript: void(0);";
					$unjoin['target']	=	'';
					$unjoin['img']		=	'<img src="' . $plugin->livePath . '/images/icon.png" width="16" height="16" />';
					$unjoin['tooltip']	=	htmlspecialchars( CBTxt::T( 'Unauthorize this site from your Facebook account.' ) );

					$this->addMenu( $unjoin );
				}
			}
		}
	}

	public function loadUsergroupsList( $name, $value, $control_name ) {
		global $_CB_framework;

		$listUsergroups		=	array();
		$listUsergroups[]	=	moscomprofilerHTML::makeOption( '', CBTxt::T( 'Default CMS' ) );
		$listUsergroups		=	array_merge( $listUsergroups, $_CB_framework->acl->get_group_children_tree( null, 'USERS', false ) );

		if ( isset( $value ) ) {
			$valAsObj		=	array_map( create_function( '$v', '$o=new stdClass(); $o->value=$v; return $o;' ), explode( '|*|', $value ) );
		} else {
			$valAsObj		=	null;
		}

		return moscomprofilerHTML::selectList( $listUsergroups, ( $control_name ? $control_name .'['. $name .'][]' : $name ), null, 'value', 'text', $valAsObj, 0, false, false );
	}

	public function loadInstructions() {
		global $_CB_framework;

		$return	=	'<div>To begin developing your facebook application to connect with your ' . htmlspecialchars( $_CB_framework->getCfg( 'live_site' ) ) . ' CB website, you must do the following steps.</div>'
				.	'<ol>'
				.		'<li>To begin you will need to login to <a href="http://developers.facebook.com/" target="_blank">Facebook Developer</a> in order to create your Application. Once there click <a href="https://developers.facebook.com/apps" target="_blank">Apps</a> to begin creating your Application.</li>'
				.		'<li>Once at <a href="https://developers.facebook.com/apps" target="_blank">Apps</a> page you will need to click <strong>+ Create New App</strong> to create your new Application.</li>'
				.		'<li>Once presented with the <strong>New App</strong> pop-up you will need to enter your Applications name (recommended: ' . htmlspecialchars( $_CB_framework->getCfg( 'sitename' ) ) . '), select your Applications default language, and agree to <a href="http://www.facebook.com/terms.php" target="_blank">Facebook Terms</a> (please read carefully) then click <strong>Continue</strong>.</li>'
				.		'<li>Once <strong>Continue</strong> is clicked you will need to input and complete the <strong>Security Check</strong> (captcha) to prevent spam then click <strong>Submit</strong>.</li>'
				.		'<li>Once <strong>Submit</strong> is clicked and you have not yet verified your account you will need to follow the instructions for developer account verification.</li>'
				.		'<li>On the Application <strong>Settings</strong> page under <strong>Basic Info</strong> you will need to provide your <strong>App Domains</strong> (required: ' . htmlspecialchars( cbfacebookconnectClass::getURLDomain() ) . ') then clicking <strong>Website</strong> under <strong>Select how your app integrates with Facebook</strong> will present an input to provide <strong>Site URL</strong> (required: ' . htmlspecialchars( $_CB_framework->getCfg( 'live_site' ) ) . '/) then click <strong>Save Changes</strong>.</li>'
				.		'<li>You will now notice your application has its own personalized IDs (<strong>App ID/App Key</strong> and <strong>App Secret</strong>) at the top of the page in order to perform API calls on your websites behalf. These IDs need to be copied to their locations below.</li>'
				.		'<li>Click <strong>Save</strong></li>'
				.		'<li>Locate <strong>CB Login</strong> (mod_cblogin) within <strong>Module Manager</strong>.</li>'
				.		'<li>Set the parameter <strong>CB Plugins integration</strong> to <strong>Yes</strong>.</li>'
				.		'<li>Click <strong>Save</strong></li>'
				.	'</ol>';

		return $return;
	}

	public function checkCURL() {
		if ( ! function_exists( 'curl_init' ) ) {
			return '<div style="color: red;">' . CBTxt::T( 'Not Installed' ) . '</div>';
		} else {
			return '<div style="color: green;">' . CBTxt::T( 'Installed' ) . '</div>';
		}
	}

	public function checkJSON() {
		if ( ! function_exists( 'json_decode' ) ) {
			return '<div style="color: red;">' . CBTxt::T( 'Not Installed' ) . '</div>';
		} else {
			return '<div style="color: green;">' . CBTxt::T( 'Installed' ) . '</div>';
		}
	}

	public function checkAPI() {
		$plugin	=	cbfacebookconnectClass::getPlugin();

		if ( ( ! $plugin->params->get( 'fb_app_id', null ) ) || ( ! $plugin->params->get( 'fb_app_secretkey', null ) ) ) {
			return '<div>' . CBTxt::T( 'Not Configured' ) . '</div>';
		} else {
			if ( $plugin->params->get( 'fb_app_enabled', 1 ) && $plugin->params->get( 'fb_app_id', null ) && $plugin->params->get( 'fb_app_secretkey', null ) ) {
				return '<div style="color: green;">' . CBTxt::T( 'Initiated' ) . '</div>';
			} else {
				return '<div style="color: red;">' . CBTxt::T( 'Not Initiated' ) . '</div>';
			}
		}
	}
}

class cbfacebookconnectClass {

	static public function getPlugin() {
		global $_CB_framework, $_CB_database;

		static $plugin				=	null;

		if ( ! isset( $plugin ) ) {
			$query					=	'SELECT *'
									.	"\n FROM " . $_CB_database->NameQuote( '#__comprofiler_plugin' )
									.	"\n WHERE " . $_CB_database->NameQuote( 'element' ) . " = " . $_CB_database->Quote( 'cb.facebookconnect' );
			$_CB_database->setQuery( $query );
			$plugin					=	null;
			$_CB_database->loadObject( $plugin );

			if ( $plugin ) {
				if ( ! is_object( $plugin->params ) ) {
					$plugin->params	=	new cbParamsBase( $plugin->params );
				}

				if ( $_CB_framework->getUi() == 2 ) {
					$site			=	'..';
				} else {
					$site			=	str_replace( '/administrator', '', $_CB_framework->getCfg( 'live_site' ) );
				}

				$path				=	str_replace( '/administrator', '', $_CB_framework->getCfg( 'absolute_path' ) );

				$plugin->option		=	'com_comprofiler';
				$plugin->relPath	=	'components/' . $plugin->option . '/plugin/' . $plugin->type . '/' . $plugin->folder;
				$plugin->livePath	=	 '/' . $plugin->relPath;
				$plugin->absPath	=	$path . '/' . $plugin->relPath;
				$plugin->xml		=	$plugin->absPath . '/' . $plugin->element . '.xml';
				$plugin->scheme		=	( ( isset( $_SERVER['HTTPS'] ) && ( ! empty( $_SERVER['HTTPS'] ) ) && ( $_SERVER['HTTPS'] != 'off' ) ) ? 'https' : 'http' );

				cbimport( 'cb.html' );
				cbimport( 'language.cbteamplugins' );
			}
		}

		return $plugin;
	}

	static public function getStaticID( $variable ) {
		if ( is_array( $variable ) || is_object( $variable ) ) {
			$variable	=	serialize( $variable );
		}

		return md5( $variable );
	}

	static public function getItemid( $htmlspecialchars = false ) {
		global $_CB_framework, $_CB_database;

		static $Itemid	=	null;

		if ( ! isset( $Itemid ) ) {
			$query		=	'SELECT ' . $_CB_database->NameQuote( 'id' )
						.	"\n FROM " . $_CB_database->NameQuote( '#__menu' )
						.	"\n WHERE " . $_CB_database->NameQuote( 'link' ) . " LIKE " . $_CB_database->Quote( 'index.php?option=com_comprofiler&task=pluginclass&plugin=cb.facebookconnect%' )
						.	"\n AND " . $_CB_database->NameQuote( 'published' ) . " = 1"
						.	"\n AND " . $_CB_database->NameQuote( 'access' ) . " IN ( " . implode( ',', cbArrayToInts( CBuser::getMyInstance()->getAuthorisedViewLevelsIds( ( checkJversion() >= 2 ? false : true ) ) ) ) . " )"
						.	( checkJversion() >= 2 ? "\n AND " . $_CB_database->NameQuote( 'language' ) . " IN ( " . $_CB_database->Quote( $_CB_framework->getCfg( 'lang_tag' ) ) . ", '*', '' )" : null );
			$_CB_database->setQuery( $query );
			$Itemid		=	$_CB_database->loadResult();

			if ( ! $Itemid ) {
				$Itemid	=	getCBprofileItemid( null );
			}
		}

		if ( is_bool( $htmlspecialchars ) ) {
			return ( $htmlspecialchars ? '&amp;' : '&' ) . 'Itemid=' . $Itemid;
		} else {
			return $Itemid;
		}
	}

	static public function getEndpointURL( $action, $function = null, $format = 'raw' ) {
		global $_CB_framework;

		$plugin				=	cbfacebookconnectClass::getPlugin();
		$url				=	'index.php?option=' . $plugin->option . '&task=pluginclass&plugin=' . $plugin->element . '&action=' . urlencode( $action ) . ( $function ? '&func=' . $function : null );

		if ( $format != 'html' ) {
			if ( $format == 'rawrel' ) {
				$format		=	'raw';
			} else {
				$url		=	$_CB_framework->getCfg( 'live_site' ) . '/' . $url;
			}

			if ( $format == 'component' ) {
				$url		=	$url . '&tmpl=' . $format;
			} else {
				$url		=	$url . '&format=' . $format;
			}
		}

		return $url;
	}

	static public function getPluginURL( $variables = array(), $msg = null, $htmlspecialchars = true, $redirect = false, $type = null, $return = false, $ajax = false, $back = false ) {
		global $_CB_framework;

		$getReturn				=	cbfacebookconnectClass::getReturnURL();

		if ( $back && $getReturn ) {
			$url				=	$getReturn;
		} else {
			$plugin				=	cbfacebookconnectClass::getPlugin();
			$action				=	( isset( $variables[0] ) ? '&action=' . urlencode( $variables[0] ) : null );
			$id					=	( isset( $variables[2] ) ? '&id=' . urlencode( $variables[2] ) : null );

			if ( $return === 'current' ) {
				$setReturn		=	( $getReturn ? '&return=' . cbfacebookconnectClass::UTF8_base64_encode( $getReturn ) : null );
			} else {
				$setReturn		=	( $return ? cbfacebookconnectClass::setReturnURL() : null );
			}

			if ( $_CB_framework->getUi() == 2 ) {
				$function		=	( isset( $variables[1] ) ? '.' . urlencode( $variables[1] ) : null );
				$vars			=	$action . $function . $id . $setReturn;
				$format			=	( $ajax ? ( is_bool( $ajax ) || is_int( $ajax ) ? 'raw' : $ajax ) : 'html' );
				$url			=	'index.php?option=' . $plugin->option . '&task=editPlugin&cid=' . $plugin->id . $vars;

				if ( $htmlspecialchars ) {
					$url		=	htmlspecialchars( $url );
				}

				$url			=	$_CB_framework->backendUrl( $url, $htmlspecialchars, $format );
			} else {
				$function		=	( isset( $variables[1] ) ? '&func=' . urlencode( $variables[1] ) : null );
				$vars			=	$action . $function . $id . $setReturn . cbfacebookconnectClass::getItemid();
				$format			=	( $ajax ? ( is_bool( $ajax ) || is_int( $ajax ) ? 'component' : $ajax ) : 'html' );
				$url			=	cbSef( 'index.php?option=' . $plugin->option . '&task=pluginclass&plugin=' . $plugin->element . $vars, $htmlspecialchars, $format );
			}
		}

		if ( $msg ) {
			if ( $redirect ) {
				cbfacebookconnectClass::setRedirect( $url, ( $msg === true ? null : $msg ), $type );
			} else {
				if ( $msg === true ) {
					$url		=	"javascript: location.href = '" . addslashes( $url ) . "';";
				} else {
					$url		=	"javascript: if ( confirm( '" . addslashes( $msg ) . "' ) ) { location.href = '" . addslashes( $url ) . "'; }";
				}
			}
		}

		return $url;
	}

	static public function setReturnURL( $raw = false ) {
		global $_CB_framework;

		if ( isset( $_SERVER['SERVER_PORT'] ) &&   $_SERVER['SERVER_PORT'] === '443'  ) {
			$return					=	'https://';
		} else {
			$return					=	'http://';
		}

		if ( ( ! empty( $_SERVER['PHP_SELF'] ) ) && ( ! empty( $_SERVER['REQUEST_URI'] ) ) ) {
			$return					.=	$_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
		} else {
			$return					.=	$_SERVER['HTTP_HOST'] . $_SERVER['SCRIPT_NAME'];

			if ( isset( $_SERVER['QUERY_STRING'] ) && ( ! empty( $_SERVER['QUERY_STRING'] ) ) ) {
				$return				.=	'?' . $_SERVER['QUERY_STRING'];
			}
		}

		$return						=	urldecode( $return );

		if ( ! preg_match( '!^(?:(?:' . preg_quote( $_CB_framework->getCfg( 'live_site' ), '!' ) . ')|(?:index.php))!', $return ) ) {
			return null;
		} elseif ( preg_match( '!index\.php\?option=com_comprofiler&task=confirm&confirmCode=|index\.php\?option=com_comprofiler&task=login|index\.php\?option=com_comprofiler&task=pluginclass&plugin=cb.facebookconnect!', cbUnHtmlspecialchars( $return ) ) ) {
			$return					=	'index.php';
		} else {
			$urlParts				=	parse_url( $return );

			if ( isset( $urlParts['query'] ) ) {
				$queryParts			=	array();

				parse_str( $urlParts['query'], $queryParts );

				$urlParts['query']	=	http_build_query( $queryParts );
			}

			$return					=	( isset( $urlParts['scheme'] ) ? $urlParts['scheme'] . '://' : null )
									.	( isset( $urlParts['user'] ) ? $urlParts['user'] : null )
									.	( isset( $urlParts['pass'] ) ? ':' . $urlParts['pass'] . ( isset( $urlParts['user'] ) ? '@' : null ) : null )
									.	( isset( $urlParts['host'] ) ? $urlParts['host'] : null )
									.	( isset( $urlParts['port'] ) ? ':' . $urlParts['port'] : null )
									.	( isset( $urlParts['path'] ) ? $urlParts['path'] : null )
									.	( isset( $urlParts['query'] ) ? '?' . $urlParts['query'] : null )
									.	( isset( $urlParts['fragment'] ) ? '#' . $urlParts['fragment'] : null );
		}

		if ( ! $raw ) {
			$return					=	'&return=' . cbfacebookconnectClass::UTF8_base64_encode( $return );
		}

		return $return;
	}

	static public function getReturnURL() {
		global $_CB_framework;

		$return			=	trim( stripslashes( cbGetParam( $_GET, 'return', null ) ) );

		if ( $return ) {
			$return		=	cbfacebookconnectClass::UTF8_base64_decode( $return );
		} else {
			return null;
		}

		if ( ! preg_match( '!^(?:(?:' . preg_quote( $_CB_framework->getCfg( 'live_site' ), '!' ) . ')|(?:index.php))!', $return ) ) {
			return null;
		}

		if ( preg_match( '!index\.php\?option=com_comprofiler&task=confirm&confirmCode=|index\.php\?option=com_comprofiler&task=login|index\.php\?option=com_comprofiler&task=pluginclass&plugin=cb.facebookconnect!', cbUnHtmlspecialchars( $return ) ) ) {
			$return		=	'index.php';
		}

		return $return;
	}

    static public function UTF8_base64_encode( $string ) {
		global $_CB_framework;

		if ( $_CB_framework->outputCharset() == 'UTF-8' ) {
			return strtr( base64_encode( $string ), '+/=', '-_.' );
		} else {
			return base64_encode( $string );
		}
	}

	static public function UTF8_base64_decode( $string ) {
		global $_CB_framework;

		if ( $_CB_framework->outputCharset() == 'UTF-8' ) {
			return base64_decode( strtr( $string, '-_.', '+/=' ) );
		} else {
			return base64_decode( $string );
		}
	}

	static public function setRedirect( $url = null, $msg = null, $type = 'message' ) {
		static $REDIRECT	=	0;

		if ( ! $REDIRECT++ ) {
			if ( ! $url ) {
				$return		=	cbfacebookconnectClass::getReturnURL();

				if ( $return ) {
					$url	=	$return;
				}

				if ( ! $url ) {
					$url	=	cbfacebookconnectClass::setReturnURL( true );
				}
			}

			if ( ! $url ) {
				$url		=	'index.php';
			}

			if ( is_array( $msg ) ) {
				$msg		=	implode( "\n", $msg );
			}

			cbRedirect( $url, $msg, $type );
		}
	}

	static public function getFilteredText( $text ) {
		global $ueConfig;

		cbimport( 'phpinputfilter.inputfilter' );

		$filter						=	new CBInputFilter( array(), array(), 1, 1, 1 );

		if ( isset( $ueConfig['html_filter_allowed_tags'] ) && $ueConfig['html_filter_allowed_tags'] ) {
			$filter->tagBlacklist	=	array_diff( $filter->tagBlacklist, explode( ' ', $ueConfig['html_filter_allowed_tags'] ) );
		}

		return trim( $filter->process( $text ) );
	}

	static public function parseParams( $row, $html = false, $base = null ) {
		if ( ! $row ) {
			return new cbParamsBase( null );
		}

		static $params						=	array();

		$id									=	cbfacebookconnectClass::getStaticID( array( $row, $html, $base ) );

		if ( ! isset( $params[$id] ) ) {
			if ( is_object( $row ) ) {
				if ( $row instanceof cbParamsBase ) {
					$row					=	$row->toIniString();
				} else {
					$row					=	get_object_vars( $row );
				}
			}

			if ( is_array( $row ) ) {
				if ( $base && ( ! ( $base instanceof cbParamsBase ) ) ) {
					$base					=	new cbParamsBase( $base );
				} elseif ( ! $base ) {
					$base					=	new cbParamsBase( null );
				}

				if ( $row ) foreach ( $row as $k => $v ) {
					if ( $k && ( ! in_array( $k, array( 'option', 'task', 'cid', 'action', 'cbsecuritym3' ) ) ) && ( ! ( isset( $k[0] ) && ( $k[0] == '_' ) ) ) ) {
						$v					=	cbGetParam( $row, $k, $v, ( $html ? _CB_ALLOWRAW : null ) );

						if ( is_array( $v ) ) {
							$sub			=	false;

							foreach ( $v as $key => $value ) {
								if ( is_string( $key ) || is_array( $value ) ) {
									$sub	=	true;
								}
							}

							if ( $sub ) {
								$p			=	cbfacebookconnectClass::parseParams( $v, $html );
								$v			=	trim( $p->toIniString() );
							} else {
								$v			=	implode( '|*|', $v );
							}
						}

						if ( ( ! is_array( $v ) && ( ! is_object( $v ) ) ) ) {
							if ( $v !== null ) {
								$v			=	stripslashes( $v );

								if ( $html && ( $html !== 'raw' ) ) {
									$v		=	cbfacebookconnectClass::getFilteredText( $v );
								}
							}

							$base->set( $k, $v );
						}
					}
				}

				if ( $base->_params ) {
					$base->_raw				=	trim( $base->toIniString() );
				}

				$params[$id]				=	$base;
			} elseif ( is_string( $row ) ) {
				$params[$id]				=	new cbParamsBase( $row );
			} else {
				$params[$id]				=	new cbParamsBase( null );
			}
		}

		return $params[$id];
	}

	static public function displayMessage( $msg, $type = 'error' ) {
		global $_CB_framework;

		if ( $msg ) {
			if ( is_array( $msg ) ) {
				$msg		=	( isset( $msg[0] ) ? $msg[0] : null );
				$type		=	( isset( $msg[1] ) ? $msg[1] : 'error' );

				if ( is_array( $msg ) ) {
					$msg	=	implode( "\n", $msg );
				}
			}

			if ( method_exists( $_CB_framework->_baseFramework, 'enqueueMessage' ) ) {
				$_CB_framework->_baseFramework->enqueueMessage( $msg, $type );
			}
		}
	}

	static public function getAvatarPath( $user ) {
		global $_CB_framework;

		static $path			=	array();

		if ( ! isset( $path[$user->id] ) ) {
			$avatar				=	null;

			if ( ( $user->avatar != '' ) && ( $user->avatarapproved > 0 ) ) {
				$avatar			=	'images/comprofiler/' . $user->avatar;

				if ( ! is_file( $_CB_framework->getCfg( 'absolute_path' ) . '/' . $avatar ) ) {
					$avatar		=	null;
				} else {
					$avatar		=	$_CB_framework->getCfg( 'live_site' ) . '/' . $avatar;
				}
			}

			if ( ! $avatar ) {
				if ( $user->avatarapproved == 0 ) {
					$icon		=	'pending_n.png';
				} else {
					$icon		=	'nophoto_n.png';
				}

				$avatar			=	selectTemplate() . 'images/avatar/' . $icon;
			}

			$path[$user->id]	=	$avatar;
		}

		return $path[$user->id];
	}

	static public function getURLDomain( $url = null ) {
		global $_CB_framework;

		$return			=	null;

		if ( ! $url ) {
			$url		=	$_CB_framework->getCfg( 'live_site' );
		}

		if ( $url ) {
			$pieces		=	parse_url( $url );
			$domain		=	( isset( $pieces['host'] ) ? $pieces['host'] : null );

			if ( $domain && preg_match( '/(?P<domain>[a-z0-9][a-z0-9\-]{1,63}\.[a-z\.]{2,6})$/i', $domain, $matches ) ) {
				$return	=	$matches['domain'];
			} else {
				$return	=	$domain;
			}
		}

		return $return;
	}

	static public function httpRequest( $url, $method = 'GET', $body = array(), $headers = array() ) {
		$response											=	null;

		if ( function_exists( 'curl_init' ) ) {
			$plugin											=	cbfacebookconnectClass::getPlugin();
			$ch												=	curl_init();

			curl_setopt( $ch, CURLOPT_URL, $url );

			if ( $method == 'POST' ) {
				curl_setopt( $ch, CURLOPT_POST, true );
			}

			if ( $body ) {
				if ( $method == 'POST' ) {
					curl_setopt( $ch, CURLOPT_POSTFIELDS, $body );
				} else {
					curl_setopt( $ch, CURLOPT_POSTFIELDS, http_build_query( $body, null, '&' ) );
				}
			}

			if ( $headers ) {
				curl_setopt( $ch, CURLOPT_HTTPHEADER, $headers );
			} else {
				curl_setopt( $ch, CURLOPT_HTTPHEADER, array( 'Expect:' ) );
			}

			curl_setopt( $ch, CURLOPT_TIMEOUT, 30 );
			curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );

			if ( ( ! ini_get( 'safe_mode' ) ) && ( ! ini_get( 'open_basedir' ) ) ) {
				curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, 1 );
			}

			curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, false );
			curl_setopt( $ch, CURLOPT_HEADER, true );
			curl_setopt( $ch, CURLINFO_HEADER_OUT, true );

			if ( $plugin->params->get( 'fb_curl_ipv4', 1 ) ) {
				if ( version_compare( phpversion(), '5.3.0', '>=' ) ) {
					$curlVersion							=	curl_version();

					if ( isset( $curlVersion['version'] ) && version_compare( $curlVersion['version'], '7.10.8', '>=' ) ) {
						curl_setopt( $ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4 );
					}
				}
			}

			$result											=	curl_exec( $ch );
			$httpCode										=	curl_getinfo( $ch, CURLINFO_HTTP_CODE );
			$headerOut										=	curl_getinfo( $ch, CURLINFO_HEADER_OUT );
			$error											=	curl_error( $ch );

			if ( $result ) {
				list( $rawResponseHeaders, $results )		=	explode( "\r\n\r\n", $result, 2 );
			} else {
				$rawResponseHeaders							=	null;
				$results									=	null;
			}

			$responseHeaders								=	array();

			if ( $rawResponseHeaders ) {
				$responseHeaderLines						=	explode( "\r\n", $rawResponseHeaders );

				foreach ( $responseHeaderLines as $headerLine ) {
					if ( $headerLine ) {
						$headerParts						=	explode( ': ', $headerLine, 2 );

						if ( count( $headerParts ) > 1 ) {
							list( $header, $value )			=	$headerParts;
						} else {
							$header							=	'Other';
							$value							=	implode( "\n", $headerParts );
						}

						if ( isset( $responseHeaders[$header] ) ) {
							$responseHeaders[$header]		.=	"\n" . $value;
						} else {
							$responseHeaders[$header]		=	$value;
						}
					}
				}
			}

			$sentHeaders									=	array();

			if ( $headerOut ) {
				$sentHeaderLines							=	explode( "\r\n", $headerOut );

				foreach ( $sentHeaderLines as $headerLine ) {
					if ( $headerLine ) {
						$headerParts						=	explode( ': ', $headerLine, 2 );

						if ( count( $headerParts ) > 1 ) {
							list( $header, $value )			=	$headerParts;
						} else {
							$header							=	'Other';
							$value							=	$headerParts[0];
						}

						if ( isset( $sentHeaders[$header] ) ) {
							$sentHeaders[$header]			.=	"\n" . $value;
						} else {
							$sentHeaders[$header]			=	$value;
						}
					}
				}
			}

			curl_close( $ch );

			$response										=	array(	'http_code' => $httpCode,
																		'results' => $results,
																		'error' => $error,
																		'headers' => $responseHeaders,
																		'headers_out' => $sentHeaders
																	);
		} else {
			trigger_error( 'cURL not installed', E_USER_ERROR );
		}

		return $response;
	}
}

class CBplug_cbfacebookconnect extends cbPluginHandler {

	public function getCBpluginComponent( $tab, $user, $ui, $postdata ) {
		switch ( cbGetParam( $_REQUEST, 'action', null ) ) {
			case 'facebook':
				switch ( cbGetParam( $_REQUEST, 'func', null ) ) {
					case 'accesstoken':
						cbfacebookconnect::getInstance()->accessToken();
						break;
					case 'registration':
						cbfacebookconnect::getInstance()->showRegistration();
						break;
					case 'storeregistration':
						cbSpoofCheck( 'plugin' );
						cbfacebookconnect::getInstance()->storeRegistration();
						break;
					case 'reset':
						cbfacebookconnect::getInstance()->resetSession();
						break;
					default:
						cbfacebookconnect::getInstance()->syncUser();
						break;
				}
				break;
		}
	}
}
?>
