<?php
if ( ! ( defined( '_VALID_CB' ) || defined( '_JEXEC' ) || defined( '_VALID_MOS' ) ) ) { die( 'Direct Access to this location is not allowed.' ); }

class cbinvitesClass {

	static public function getPlugin() {
		global $_CB_framework, $_CB_database;

		static $plugin								=	null;

		if ( ! isset( $plugin ) ) {
			$query									=	'SELECT *'
													.	"\n FROM " . $_CB_database->NameQuote( '#__comprofiler_plugin' )
													.	"\n WHERE " . $_CB_database->NameQuote( 'element' ) . " = " . $_CB_database->Quote( 'cbinvites' );
			$_CB_database->setQuery( $query );
			$plugin									=	null;
			$_CB_database->loadObject( $plugin );

			if ( $plugin ) {
				if ( ! is_object( $plugin->params ) ) {
					$plugin->params					=	new cbParamsBase( $plugin->params );
				}

				if ( ! isset( $plugin->tab ) ) {
					$query							=	'SELECT *'
													.	"\n FROM " . $_CB_database->NameQuote( '#__comprofiler_tabs' )
													.	"\n WHERE " . $_CB_database->NameQuote( 'pluginid' ) . " = " . (int) $plugin->id;
					$_CB_database->setQuery( $query );
					$plugin->tab					=	null;
					$_CB_database->loadObject( $plugin->tab );

					if ( $plugin->tab ) {
						if ( ! is_object( $plugin->tab->params ) ) {
							$plugin->tab->params	=	new cbParamsBase( $plugin->tab->params );
						}
					}
				}

				if ( $_CB_framework->getUi() == 2 ) {
					$site							=	'..';
				} else {
					$site							=	str_replace( '/administrator', '', $_CB_framework->getCfg( 'live_site' ) );
				}

				$path								=	str_replace( '/administrator', '', $_CB_framework->getCfg( 'absolute_path' ) );

				$plugin->option						=	'com_comprofiler';
				$plugin->relPath					=	'components/' . $plugin->option . '/plugin/' . $plugin->type . '/' . $plugin->folder;
				$plugin->livePath					=	$site . '/' . $plugin->relPath;
				$plugin->absPath					=	$path . '/' . $plugin->relPath;
				$plugin->xml						=	$plugin->absPath . '/' . $plugin->element . '.xml';

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
						.	"\n WHERE " . $_CB_database->NameQuote( 'link' ) . " LIKE " . $_CB_database->Quote( 'index.php?option=com_comprofiler&task=pluginclass&plugin=cbinvites%' )
						.	"\n AND " . $_CB_database->NameQuote( 'published' ) . " = 1"
						.	"\n AND " . $_CB_database->NameQuote( 'access' ) . " IN ( " . implode( ',', cbArrayToInts( CBuser::getMyInstance()->getAuthorisedViewLevelsIds() ) ) . " )"
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

	static public function getPluginURL( $variables = array(), $msg = null, $htmlspecialchars = true, $redirect = false, $type = null, $return = false, $ajax = false, $back = false ) {
		global $_CB_framework;

		$get_return				=	cbinvitesClass::getReturnURL();

		if ( $back && $get_return ) {
			$url				=	$get_return;
		} else {
			$plugin				=	cbinvitesClass::getPlugin();
			$action				=	( isset( $variables[0] ) ? '&action=' . urlencode( $variables[0] ) : null );
			$id					=	( isset( $variables[2] ) ? '&id=' . urlencode( $variables[2] ) : null );

			if ( $return === 'current' ) {
				$set_return		=	( $get_return ? '&return=' . base64_encode( $get_return ) : null );
			} else {
				$set_return		=	( $return ? cbinvitesClass::setReturnURL() : null );
			}

			if ( $_CB_framework->getUi() == 2 ) {
				$function		=	( isset( $variables[1] ) ? '.' . urlencode( $variables[1] ) : null );
				$vars			=	$action . $function . $id . $set_return;
				$format			=	( $ajax ? ( is_bool( $ajax ) || is_int( $ajax ) ? 'raw' : $ajax ) : 'html' );
				$url			=	'index.php?option=' . $plugin->option . '&task=editPlugin&cid=' . $plugin->id . $vars;

				if ( $htmlspecialchars ) {
					$url		=	htmlspecialchars( $url );
				}

				$url			=	$_CB_framework->backendUrl( $url, $htmlspecialchars, $format );
			} else {
				$function		=	( isset( $variables[1] ) ? '&func=' . urlencode( $variables[1] ) : null );
				$vars			=	$action . $function . $id . $set_return . cbinvitesClass::getItemid();
				$format			=	( $ajax ? ( is_bool( $ajax ) || is_int( $ajax ) ? 'component' : $ajax ) : 'html' );
				$url			=	cbSef( 'index.php?option=' . $plugin->option . '&task=pluginclass&plugin=' . $plugin->element . $vars, $htmlspecialchars, $format );
			}
		}

		if ( $msg ) {
			if ( $redirect ) {
				cbinvitesClass::setRedirect( $url, ( $msg === true ? null : $msg ), $type );
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

	static public function getCBURL( $task = null, $msg = null, $htmlspecialchars = true, $redirect = false, $type = null, $return = false, $ajax = false, $back = false ) {
		global $_CB_framework;

		$get_return				=	cbinvitesClass::getReturnURL();

		if ( $back && $get_return ) {
			$url				=	$get_return;
		} else {
			$plugin				=	cbinvitesClass::getPlugin();

			if ( (int) $task > 0 ) {
				$itemidtask		=	'userprofile';

				if ( $task != $_CB_framework->myId() ) {
					$task		=	( $task ? '&task=userprofile&user=' . (int) $task : null );
				} else {
					$task		=	null;
				}
			} else {
				$itemidtask		=	$task;
				$task			=	( $task && ( $task != 'userprofile' ) ? '&task=' . urlencode( $task ) : null );
			}

			if ( ( ! $itemidtask ) || ( $itemidtask == 'userprofile' ) ) {
				$tab			=	'&tab=' . (int) $plugin->tab->tabid;
			} else {
				$tab			=	null;
			}

			if ( $return === 'current' ) {
				$set_return		=	( $get_return ? '&return=' . base64_encode( $get_return ) : null );
			} else {
				$set_return		=	( $return ? cbinvitesClass::setReturnURL() : null );
			}

			$itemid				=	( $itemidtask ? getCBprofileItemid( false, $itemidtask ) : getCBprofileItemid() );
			$vars				=	$task . $tab . $itemid . $set_return;
			$format				=	( $ajax ? ( is_bool( $ajax ) || is_int( $ajax ) ? 'component' : $ajax ) : 'html' );
			$url				=	cbSef( 'index.php?option=' . $plugin->option . $vars, $htmlspecialchars, $format );
		}

		if ( $msg ) {
			if ( $redirect ) {
				cbinvitesClass::setRedirect( $url, ( $msg === true ? null : $msg ), $type );
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

		if ( isset( $_SERVER['HTTPS'] ) && ( ! empty( $_SERVER['HTTPS'] ) ) && ( strtolower( $_SERVER['HTTPS'] ) != 'off' ) ) {
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
		} elseif ( preg_match( '!index\.php\?option=com_comprofiler&task=confirm&confirmCode=|index\.php\?option=com_comprofiler&task=login!', cbUnHtmlspecialchars( $return ) ) ) {
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
			$return					=	'&return=' . base64_encode( $return );
		}

		return $return;
	}

	static public function getReturnURL() {
		global $_CB_framework;

		$return			=	trim( stripslashes( cbGetParam( $_GET, 'return', null ) ) );

		if ( $return ) {
			$return		=	base64_decode( (string) preg_replace( '/[^A-Z0-9\/+=]/i', '', $return ) );
		} else {
			return null;
		}

		if ( ! preg_match( '!^(?:(?:' . preg_quote( $_CB_framework->getCfg( 'live_site' ), '!' ) . ')|(?:index.php))!', $return ) ) {
			return null;
		}

		if ( preg_match( '!index\.php\?option=com_comprofiler&task=confirm&confirmCode=|index\.php\?option=com_comprofiler&task=login!', cbUnHtmlspecialchars( $return ) ) ) {
			$return		=	'index.php';
		}

		return $return;
	}

	static public function sendInvite( &$row ) {
		global $_CB_framework;

		$plugin						=	cbinvitesClass::getPlugin();
		$invite_prefix				=	CBTxt::T( $plugin->params->get( 'invite_prefix', '[sitename] - ' ) );
		$invite_header				=	CBTxt::T( $plugin->params->get( 'invite_header', '<p>You have been invited by [username] to join [sitename]!</p><br />' ) );
		$invite_footer				=	CBTxt::T( $plugin->params->get( 'invite_footer', '<br /><p>Invite Code - [code]<br />[sitename] - [site]<br />Registration - [register]<br />[username] - [profile]</p>' ) );

		$cbUser						=&	CBuser::getInstance( (int) $row->user_id );

		if ( ! $cbUser ) {
			$cbUser					=&	CBuser::getInstance( null );
		}

		$user						=&	$cbUser->getUserData();

		$extra						=	array(	'sitename' => $_CB_framework->getCfg( 'sitename' ),
												'site' => $_CB_framework->getCfg( 'live_site' ),
												'path' => $_CB_framework->getCfg( 'absolute_path' ),
												'itemid' => getCBprofileItemid(),
												'register' => cbSef( 'index.php?option=' . $plugin->option . '&task=registers' . ( $row->code ? '&invite_code=' . urlencode( $row->code ) : null ) . getCBprofileItemid( false, 'registers' ), false ),
												'profile' => $_CB_framework->userProfileUrl( (int) $row->user_id, false ),
												'code' => $row->code,
												'to' => $row->to
											);

		$mailTo						=	trim( strip_tags( $cbUser->replaceUserVars( $row->to, true, true, $extra ) ) );
		$mailCC						=	trim( strip_tags( $cbUser->replaceUserVars( $plugin->params->get( 'invite_cc', null ), true, true, $extra ) ) );
		$mailBCC					=	trim( strip_tags( $cbUser->replaceUserVars( $plugin->params->get( 'invite_bcc', null ), true, true, $extra ) ) );
		$mailSubject				=	trim( strip_tags( $cbUser->replaceUserVars( ( $invite_prefix . ( $row->subject ? $row->subject : CBTxt::T( 'Join Me!' ) ) ), true, true, $extra ) ) );
		$mailBody					=	cbinvitesClass::getFilteredText( $cbUser->replaceUserVars( ( $invite_header . $row->body . $invite_footer ), false, true, $extra ) );
		$mailAttachments			=	trim( strip_tags( $cbUser->replaceUserVars( $plugin->params->get( 'invite_attachments', null ), true, true, $extra ) ) );

		if ( $mailTo ) {
			$mailTo					=	preg_split( ' *, *', $mailTo );
		}

		if ( $mailCC ) {
			$mailCC					=	preg_split( ' *, *', $mailCC );
		}

		if ( $mailBCC ) {
			$mailBCC				=	preg_split( ' *, *', $mailBCC );
		}

		if ( $mailAttachments ) {
			$mailAttachments		=	preg_split( ' *, *', $mailAttachments );
		}

		if ( $mailTo && $mailSubject && $mailBody ) {
			$sent					=	comprofilerMail( $user->email, $user->name, $mailTo, $mailSubject, $mailBody, (int) $plugin->params->get( 'invite_mode', 1 ), $mailCC, $mailBCC, $mailAttachments );

			if ( $sent ) {
				return true;
			} else {
				if ( function_exists( 'error_get_last' ) ) {
					$error			=	error_get_last();
					$row->_error	=	( isset( $error['message'] ) ? $error['message'] : CBTxt::T( 'Mailer failed to send.' ) );
				} else {
					$row->_error	=	CBTxt::T( 'Mailer failed to send.' );
				}
			}
		} else {
			if ( ! $mailTo ) {
				$row->_error		=	CBTxt::T( 'To address missing.' );
			} elseif ( ! $mailSubject ) {
				$row->_error		=	CBTxt::T( 'Subject missing.' );
			} elseif ( ! $mailBody ) {
				$row->_error		=	CBTxt::T( 'Body missing.' );
			}
		}

		return false;
	}

	static public function getTemplate( $files = null ) {
		global $_CB_framework;

		static $tmpl						=	array();

		$id									=	cbinvitesClass::getStaticID( $files );

		if ( ! isset( $tmpl[$id] ) ) {
			$plugin							=	cbinvitesClass::getPlugin();
			$template						=	$plugin->params->get( 'general_template', 'default' );
			$files							=	( ! is_array( $files ) ? array( $files ) : $files );
			$paths							=	array();

			foreach ( $files as $file ) {
				$file						=	preg_replace( '/[^-a-zA-Z0-9_]/', '', $file );
				$global_css					=	'/templates/' . $template . '/template.css';
				$override_css				=	'/templates/' . $template . '/override.css';

				if ( $file ) {
					$php					=	$plugin->absPath . '/templates/' . $template . '/' . $file . '.php';
					$css					=	'/templates/' . $template . '/' . $file . '.css';
					$js						=	'/templates/' . $template . '/' . $file . '.js';
				} else {
					$php					=	null;
					$css					=	null;
					$js						=	null;
				}

				if ( ! file_exists( $plugin->absPath . $global_css ) ) {
					$global_css				=	'/templates/default/template.css';
				}

				if ( file_exists( $plugin->absPath . $global_css ) ) {
					$_CB_framework->document->addHeadStyleSheet( $plugin->livePath . $global_css );

					$paths['global_css']	=	$plugin->livePath . $global_css;
				}

				if ( $file ) {
					if ( ! file_exists( $php ) ) {
						$php				=	$plugin->absPath . '/templates/default/' . $file . '.php';
					}

					if ( file_exists( $php ) ) {
						require_once( $php );

						$paths['php']		=	$php;
					}

					if ( ! file_exists( $plugin->absPath . $css ) ) {
						$css				=	'/templates/default/' . $file . '.css';
					}

					if ( file_exists( $plugin->absPath . $css ) ) {
						$_CB_framework->document->addHeadStyleSheet( $plugin->livePath . $css );

						$paths['css']		=	$plugin->livePath . $css;
					}

					if ( ! file_exists( $plugin->absPath . $js ) ) {
						$js					=	'/templates/default/' . $file . '.js';
					}

					if ( file_exists( $plugin->absPath . $js ) ) {
						$_CB_framework->document->addHeadScriptUrl( $plugin->livePath . $js );

						$paths['js']		=	$plugin->livePath . $js;
					}
				}

				if ( file_exists( $plugin->absPath . $override_css ) ) {
					$_CB_framework->document->addHeadStyleSheet( $plugin->livePath . $override_css );

					$paths['override_css']	=	$plugin->livePath . $override_css;
				}
			}

			$tmpl[$id]						=	$paths;
		}

		return $tmpl[$id];
	}

	static public function getAuthorization( $invite = null, $user = null, $owner = null ) {
		global $_CB_framework;

		$plugin								=	cbinvitesClass::getPlugin();
		$invite_limit						=	$plugin->params->get( 'invite_limit', null );

		if ( ! $user ) {
			$user							=	$_CB_framework->myId();
		}

		if ( ! is_object( $user ) ) {
			$user							=&	CBuser::getUserDataInstance( (int) $user );
		}

		if ( ! $user ) {
			$user							=&	CBuser::getUserDataInstance( null );
		}

		static $invs						=	array();

		if ( $invite && ( ( ! is_object( $invite ) ) || ( is_object( $invite ) && ( ! $invite instanceof cbinvitesInvite ) ) ) ) {
			if ( is_object( $invite ) ) {
				if ( isset( $invite->id ) ) {
					$invid					=	$invite->id;
				} else {
					$invid					=	0;
				}
			} else {
				$invid						=	$invite;
			}

			if ( ! isset( $invs[$invid] ) ) {
				$invs[$invid]				=	cbinvitesData::getInvites( null, array( 'id', '=', $invid ), null, null, false );
			}

			$invite							=	$invs[$invid];
		}

		static $owners						=	array();

		if ( $owner ) {
			if ( ! is_object( $owner ) ) {
				if ( ! isset( $owners[$owner] ) ) {
					$owners[$owner]			=&	CBuser::getUserDataInstance( (int) $owner );
				}

				$owner						=	$owners[$owner];
			}
		} elseif ( isset( $invite->id ) ) {
			if ( ! isset( $owners[$invite->user_id] ) ) {
				$owners[$invite->user_id]	=	$invite->getOwner();
			}

			$owner							=	$owners[$invite->user_id];
		}

		static $cache						=	array();

		$id									=	$user->id . ( isset( $invite->id ) ? $invite->id : 0 ) . ( isset( $owner->id ) ? $owner->id : 0 );

		if ( ( ! isset( $cache[$id] ) ) || cbinvitesClass::resetCache() ) {
			$access							=	array();

			if ( $user->id ) {
				$access[]					=	'usr_reg'; // Registered
			} else {
				$access[]					=	'usr_guest'; // Guest
			}

			if ( cbinvitesClass::hasAccess( 'usr_reg', $access ) ) {
				if ( $_CB_framework->acl->get_user_moderator( $user->id ) ) {
					$access[]				=	'usr_mod'; // Moderator
				}

				if ( isset( $owner->id ) ) {
					if ( $owner->id == $user->id ) {
						$access[]			=	'usr_me'; // Me
					}
				}
			}

			if ( isset( $invite->id ) ) {
				$access[]					=	'inv'; // Invite Exists

				if ( cbinvitesClass::hasAccess( 'inv', $access ) ) {
					if ( cbinvitesClass::hasAccess( 'usr_reg', $access ) ) {
						if ( $invite->user_id == $user->id ) {
							$access[]		=	'inv_owner'; // Invite Owner
						}
					}
				}
			}

			if ( cbinvitesClass::hasAccess( 'usr_reg', $access ) ) {
				if ( cbinvitesClass::hasAccess( array( 'usr_mod', 'inv_owner' ), $access ) ) {
					$access[]				=	'mod_lvl1'; // Moderation Level
				}

				$access[]					=	'inv_create'; // Invite Create New

				if ( $invite_limit && ( ! cbinvitesClass::hasAccess( 'usr_mod', $access ) ) ) {
					if ( ! ( count( cbinvitesData::getInvites( null, array( array( 'user_id', '=', $user->id ), array( 'user', 'EMPTY' ) ) ) ) >= $invite_limit ) ) {
						$access[]			=	'inv_create_limited'; // Invite Create New With Limits
					} else {
						$access[]			=	'inv_create_maxed'; // Invite Maximum Create Limit Reached
					}
				} else {
					$access[]				=	'inv_create_limited'; // Invite Create New With Limits
				}
			}

			$cache[$id]						=	$access;
		}

		return $cache[$id];
	}

	static public function setRedirect( $url = null, $msg = null, $type = 'message' ) {
		static $REDIRECT	=	0;

		if ( ! $REDIRECT++ ) {
			if ( ! $url ) {
				$return		=	cbinvitesClass::getReturnURL();

				if ( $return ) {
					$url	=	$return;
				}

				if ( ! $url ) {
					$url	=	cbinvitesClass::setReturnURL( true );
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

    static public function getIcon( $msg, $title = null, $class = 'icon-question-sign', $forceTooltip = false ) {
		$plugin				=	cbinvitesClass::getPlugin();
		$generalTooltips	=	$plugin->params->get( 'general_tooltips', 1 );

		if ( $msg ) {
			if ( ( $generalTooltips == 1 ) || $forceTooltip ) {
				$popover	=	'<div class="popover top">'
							.		'<div class="popover-inner">'
							.			'<div class="arrow"></div>'
							.			( $title ? '<h3 class="popover-title">' . $title . '</h3>' : null )
							.			'<div class="popover-content">'
							.				'<p>' . $msg . '</p>'
							.			'</div>'
							.		'</div>'
							.	'</div>';

				return '<span class="invitesPopOver"><i class="' . htmlspecialchars( $class ) . '"></i>' . $popover . '</span>&nbsp;';
			} elseif ( $generalTooltips == 2 ) {
				return $msg;
			}
		} elseif ( $title ) {
			$tooltip		=	'<span class="tooltip fade top in">'
							.		'<div class="tooltip-arrow"></div>'
							.		'<div class="tooltip-inner">' . $title . '</div>'
							.	'</span>';

			return '<span class="invitesTooltip"><i class="' . htmlspecialchars( $class ) . '"></i>' . $tooltip . '</span>&nbsp;';
		}

		return null;
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

		$id									=	cbinvitesClass::getStaticID( array( $row, $html, $base ) );

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
								$p			=	cbinvitesClass::parseParams( $v, $html );
								$v			=	trim( $p->toIniString() );
							} else {
								$v			=	implode( '|*|', $v );
							}
						}

						if ( ( ! is_array( $v ) && ( ! is_object( $v ) ) ) ) {
							if ( $v !== null ) {
								$v			=	stripslashes( $v );

								if ( $html && ( $html !== 'raw' ) ) {
									$v		=	cbinvitesClass::getFilteredText( $v );
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

	static public function getCleanParam( $access, $param, $default = null, $items = 'POST' ) {
		if ( $items == 'POST' ) {
			$items	=	$_POST;
		} elseif ( $items == 'GET' ) {
			$items	=	$_GET;
		} elseif ( $items == 'REQUEST' ) {
			$items	=	$_REQUEST;
		}

		$data		=	cbinvitesClass::parseParams( $items );

		return ( (bool) $access ? $data->get( $param, $default ) : $default );
	}

	static public function getHTMLCleanParam( $access, $param, $default = null, $items = 'POST' ) {
		if ( $items == 'POST' ) {
			$items	=	$_POST;
		} elseif ( $items == 'GET' ) {
			$items	=	$_GET;
		} elseif ( $items == 'REQUEST' ) {
			$items	=	$_REQUEST;
		}

		$data		=	cbinvitesClass::parseParams( $items, true );

		return ( (bool) $access ? $data->get( $param, $default ) : $default );
	}

	static public function getRAWParam( $access, $param, $default = null, $items = 'POST' ) {
		if ( $items == 'POST' ) {
			$items	=	$_POST;
		} elseif ( $items == 'GET' ) {
			$items	=	$_GET;
		} elseif ( $items == 'REQUEST' ) {
			$items	=	$_REQUEST;
		}

		$data		=	cbinvitesClass::parseParams( $items, 'raw' );

		return ( (bool) $access ? $data->get( $param, $default ) : $default );
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

	static public function resetCache( $reset = false ) {
		static $cache	=	false;

		if ( $reset ) {
			$cache		=	true;
		} else {
			return $cache;
		}
	}

	static public function hasAccess( $access, $authorized, $and = false ) {
		if ( $authorized ) {
			if ( is_array( $access ) ) {
				foreach ( $access as $v ) {
					if ( $and ) {
						if ( ! in_array( $v, $authorized ) ) {
							return false;
						}
					} else {
						if ( in_array( $v, $authorized ) ) {
							return true;
						}
					}
				}

				if ( $and ) {
					return true;
				}
			} else {
				if ( in_array( $access, $authorized ) ) {
					return true;
				}
			}
		}

		return false;
	}
}

class cbinvitesInvites extends comprofilerDBTable {
	var $id			=	null;
	var $user_id	=	null;
	var $to			=	null;
	var $subject	=	null;
	var $body		=	null;
	var $code		=	null;
	var $sent		=	null;
	var $accepted	=	null;
	var $user		=	null;

	public function __construct( $db ) {
		$this->comprofilerDBTable( '#__comprofiler_plugin_invites', 'id', $db );
	}

	public function get( $var, $def = null ) {
		if ( isset( $this->$var ) ) {
			return $this->$var;
		} else {
			return $def;
		}
	}

	public function store() {
		$new	=	( $this->get( 'id' ) ? false : true );

		if ( ! parent::store() ) {
			return false;
		}

		if ( $new ) {
			cbinvitesClass::resetCache( true );
		}

		return true;
	}

	public function send() {
		$this->sent	=	date( 'Y-m-d H:i:s' );

		if ( ! cbinvitesClass::sendInvite( $this ) ) {
			return false;
		}

		if ( $this->getError() || ( ! $this->store() ) ) {
			return false;
		}

		return true;
	}

	public function getOwner() {
		static $cache				=	array();

		if ( ! isset( $cache[$this->user_id] ) ) {
			$cache[$this->user_id]	=&	CBuser::getUserDataInstance( (int) $this->user_id );
		}

		return $cache[$this->user_id];
	}

	public function getOwnerName( $linked = false ) {
		static $cache				=	array();

		if ( ! isset( $cache[$this->user_id] ) ) {
			$cbUser					=&	CBuser::getInstance( (int) $this->user_id );

			if ( ! $cbUser ) {
				$cbUser				=&	CBuser::getInstance( null );
			}

			$cache[$this->user_id]	=	$cbUser;
		}

		$name						=	null;

		if ( $cache[$this->user_id] ) {
			$user					=	$cache[$this->user_id];

			if ( $linked ) {
				$name				=	$user->getField( 'formatname', null, 'html', 'none', 'list', 0, true );
			} else {
				$name				=	$user->getField( 'formatname', null, 'html', 'none', 'profile', 0, true );
			}
		}

		return $name;
	}

	public function getOwnerAvatar( $linked = false ) {
		static $cache				=	array();

		if ( ! isset( $cache[$this->user_id] ) ) {
			$cbUser					=&	CBuser::getInstance( (int) $this->user_id );

			if ( ! $cbUser ) {
				$cbUser				=&	CBuser::getInstance( null );
			}

			$cache[$this->user_id]	=	$cbUser;
		}

		$name						=	null;

		if ( $cache[$this->user_id] ) {
			$user					=	$cache[$this->user_id];

			if ( $linked ) {
				$name				=	$user->getField( 'avatar', null, 'html', 'none', 'list', 0, true );
			} else {
				$name				=	$user->getField( 'avatar', null, 'html', 'none', 'profile', 0, true );
			}
		}

		return $name;
	}

	public function getUser() {
		global $_CB_database;

		static $cache	=	array();

		$id				=	( $this->user ? $this->user : $this->to );

		if ( ! isset( $cache[$id] ) ) {
			if ( ! $this->user ) {
				$user	=	new moscomprofilerUser( $_CB_database );

				$user->loadByEmail( $this->to );
			} else {
				$user	=&	CBuser::getUserDataInstance( (int) $this->user );
			}


			$cache[$id]	=&	$user;
		}

		return $cache[$id];
	}

	public function getUserName( $linked = false ) {
		static $cache		=	array();

		$id					=	( $this->user ? $this->user : $this->to );

		if ( ! isset( $cache[$id] ) ) {
			if ( ! $this->user ) {
				$user_id	=	$this->getUser()->id;
			} else {
				$user_id	=	$this->user;
			}

			$cbUser			=&	CBuser::getInstance( (int) $user_id );

			if ( ! $cbUser ) {
				$cbUser		=&	CBuser::getInstance( null );
			}

			$cache[$id]		=	$cbUser;
		}

		$name				=	null;

		if ( $cache[$id] ) {
			$user			=	$cache[$id];

			if ( $linked ) {
				$name		=	$user->getField( 'formatname', null, 'html', 'none', 'list', 0, true );
			} else {
				$name		=	$user->getField( 'formatname', null, 'html', 'none', 'profile', 0, true );
			}
		}

		return $name;
	}

	public function getUserAvatar( $linked = false ) {
		static $cache		=	array();

		$id					=	( $this->user ? $this->user : $this->to );

		if ( ! isset( $cache[$id] ) ) {
			if ( ! $this->user ) {
				$user_id	=	$this->getUser()->id;
			} else {
				$user_id	=	$this->user;
			}

			$cbUser			=&	CBuser::getInstance( (int) $user_id );

			if ( ! $cbUser ) {
				$cbUser		=&	CBuser::getInstance( null );
			}

			$cache[$id]		=	$cbUser;
		}

		$name				=	null;

		if ( $cache[$id] ) {
			$user			=	$cache[$id];

			if ( $linked ) {
				$name		=	$user->getField( 'avatar', null, 'html', 'none', 'list', 0, true );
			} else {
				$name		=	$user->getField( 'avatar', null, 'html', 'none', 'profile', 0, true );
			}
		}

		return $name;
	}

	public function getToUser() {
		global $_CB_database;

		static $cache			=	array();

		if ( ! isset( $cache[$this->to] ) ) {
			$user				=	new moscomprofilerUser( $_CB_database );

			$user->loadByEmail( $this->to );

			$cache[$this->to]	=	$user;
		}

		return $cache[$this->to];
	}

	public function getTo( $linked = true ) {
		static $cache				=	array();

		if ( ! isset( $cache[$this->id.$linked] ) ) {
			if ( $this->user ) {
				$cache[$this->id]	=	$this->getUserName( $linked );
			} else {
				$email				=	htmlspecialchars( $this->to );
				$cache[$this->id]	=	( $linked ? '<a href="mailto:' . $email . '">' . $email . '</a>' : $email );
			}
		}

		return $cache[$this->id];
	}

	public function getStatus() {
		static $cache				=	array();

		if ( ! isset( $cache[$this->id] ) ) {
			if ( $this->isAccepted() ) {
				$cache[$this->id]	=	'<i class="icon-ok" title="' . htmlspecialchars( CBTxt::T( 'Accepted' ) ) . '"></i>';
			} else {
				$cache[$this->id]	=	'<i class="icon-ban-circle" title="' . htmlspecialchars( CBTxt::T( 'Pending' ) ) . '"></i>';
			}
		}

		return $cache[$this->id];
	}

	public function isSent() {
		static $cache			=	array();

		if ( ! isset( $cache[$this->id] ) ) {
			if ( $this->sent && ( $this->sent != '0000-00-00 00:00:00' ) && ( $this->sent != '0000-00-00' ) ) {
				$sent			=	true;
			} else {
				$sent			=	false;
			}

			$cache[$this->id]	=	$sent;
		}

		return $cache[$this->id];
	}

	public function isAccepted() {
		static $cache			=	array();

		if ( ! isset( $cache[$this->id] ) ) {
			if ( $this->accepted && ( $this->accepted != '0000-00-00 00:00:00' ) && ( $this->accepted != '0000-00-00' ) ) {
				$accepted		=	true;
			} else {
				$accepted		=	false;
			}

			$cache[$this->id]	=	$accepted;
		}

		return $cache[$this->id];
	}

	public function dateDifference() {
		global $_CB_framework;

		static $cache			=	array();

		if ( ! isset( $cache[$this->id] ) ) {
			$cache[$this->id]	=	( ( $_CB_framework->now() - strtotime( $this->sent ) ) / 86400 );
		}

		return $cache[$this->id];
	}

	public function canResend() {
		static $cache			=	array();

		if ( ! isset( $cache[$this->id] ) ) {
			$plugin				=	cbinvitesClass::getPlugin();

			if ( ( ! $this->isAccepted() ) && ( ( ! $this->isSent() ) || ( $this->dateDifference() >= (int) $plugin->params->get( 'invite_resend', 7 ) ) ) ) {
				$resend			=	true;
			} else {
				$resend			=	false;
			}

			$cache[$this->id]	=	$resend;
		}

		return $cache[$this->id];
	}

	public function isDuplicate() {
		static $cache			=	array();

		if ( ! isset( $cache[$this->to] ) ) {
			if ( $this->id ) {
				$where			=	array( array( 'id', '!=', $this->id ), array( 'to', '=', $this->to ) );
			} else {
				$where			=	array( 'to', '=', $this->to );
			}

			if ( count( cbinvitesData::getInvites( null, $where ) ) ) {
				$duplicate		=	true;
			} else {
				$duplicate		=	false;
			}

			$cache[$this->to]	=	$duplicate;
		}

		return $cache[$this->to];
	}
}

class cbinvitesData {

	static public function getInvites( $access = array(), $filtering = array(), $ordering = array(), $limits = 0, $list = true ) {
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

		$id					=	cbinvitesClass::getStaticID( array( $filtering, $ordering ) );

		if ( ( ! isset( $cache[$id] ) ) || cbinvitesClass::resetCache() ) {
			$where			=	array();
			$join			=	array();

			if ( $filtering ) {
				cbinvitesData::where( $where, $join, $filtering, 'a' );
			}

			$orderby		=	array();

			if ( $ordering ) {
				cbinvitesData::order( $orderby, $join, $ordering, 'a' );
			}

			$query			=	'SELECT a.*'
							.	"\n FROM " . $_CB_database->NameQuote( '#__comprofiler_plugin_invites' ) . " AS a";

			if ( count( $join ) ) {
				if ( in_array( 'b', $join ) ) {
					$query	.=	"\n LEFT JOIN " . $_CB_database->NameQuote( '#__users' ) . " AS b"
							.	' ON b.' . $_CB_database->NameQuote( 'id' ) . ' = a.' . $_CB_database->NameQuote( 'user' );
				}

				if ( in_array( 'c', $join ) ) {
					$query	.=	"\n LEFT JOIN " . $_CB_database->NameQuote( '#__users' ) . " AS c"
							.	' ON c.' . $_CB_database->NameQuote( 'id' ) . ' = a.' . $_CB_database->NameQuote( 'user_id' );
				}
			}

			$query			.=	( count( $where ) ? "\n WHERE " . implode( "\n AND ", $where ) : null )
							.	"\n ORDER BY " . ( count( $orderby ) ? implode( ', ', $orderby ) : "a." . $_CB_database->NameQuote( 'sent' ) . " DESC" );
			$_CB_database->setQuery( $query );
			$cache[$id]		=	$_CB_database->loadObjectList( 'id', 'cbinvitesInvites', array( &$_CB_database ) );
		}

		$rows				=	$cache[$id];

		if ( $rows ) {
			if ( $access ) {
				cbinvitesData::access( $rows, $access );
			}

			if ( $limits ) {
				cbinvitesData::limit( $rows, $limits );
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
				$rows		=	new cbinvitesInvites( $_CB_database );
			}

			return $rows;
		}
	}

	static public function where( &$where, &$join, $filtering = array(), $defaultkey = null ) {
		global $_CB_database;

		if ( $filtering ) {
			if ( is_array( $filtering[0] ) ) {
				foreach ( $filtering as $filter ) {
					cbinvitesData::where( $where, $join, $filter, $defaultkey );
				}
			} else {
				$var									=	( isset( $filtering[0] ) ? $filtering[0] : null );

				if ( $var ) {
					$operator							=	( isset( $filtering[1] ) ? strtoupper( $filtering[1] ) : '=' );
					$input								=	( isset( $filtering[2] ) ? $filtering[2] : '' );
					$or_case							=	( isset( $filtering[3] ) ? $filtering[3] : null );

					if ( stristr( $var, '.' ) ) {
						$key_var						=	explode( '.', $var );
						$key							=	( isset( $key_var[0] ) ? $key_var[0] : null );
						$var							=	( isset( $key_var[1] ) ? $key_var[1] : null );
					} else {
						$key							=	$defaultkey;
					}

					if ( $key ) {
						$key							=	preg_replace( '/[^-a-zA-Z0-9]/', '', $key );

						if ( $key != $defaultkey ) {
							$join[]						=	$key;
						}

						$key							=	$key . '.';
					}

					if ( is_int( $input )  ) {
						$input							=	(int) $input;
					} elseif ( is_float( $input ) ) {
						$input							=	(float) $input;
					} elseif ( is_array( $input ) ) {
						foreach ( $input as $k => $v ) {
							if ( ( ! is_int( $v ) ) || ( ! is_float( $v ) ) ) {
								if ( $v === null ) {
									$input[$k]			=	'NULL';
								} else {
									$input[$k]			=	$_CB_database->Quote( $v );
								}
							}
						}

						switch ( $operator ) {
							case '!=':
							case '!IN':
								$operator				=	'NOT IN';
								break;
							case '=':
							default:
								$operator				=	'IN';
								break;
						}

						$input							=	'( ' . implode( ', ', $input ) . ' )';
					} elseif ( is_string( $input ) ) {
						switch ( $operator ) {
							case 'IN':
							case '!IN':
							case 'NOT IN':
								if ( $operator == '!IN' ) {
									$operator			=	'NOT IN';
								}

								$input					=	explode( ',', $input );

								foreach ( $input as $k => $v ) {
									if ( ( ! is_int( $v ) ) || ( ! is_float( $v ) ) ) {
										if ( $v === null ) {
											$input[$k]	=	'NULL';
										} else {
											$input[$k]	=	$_CB_database->Quote( $v );
										}
									}
								}

								$input					=	'( ' . implode( ', ', $input ) . ' )';
								break;
							case 'CONTAINS':
							case 'LIKE':
							case '!CONTAINS':
							case 'NOT LIKE':
								$input					=	$_CB_database->Quote( '%' . $_CB_database->getEscaped( $input, true ) . '%', false );
								break;
							default:
								$input					=	$_CB_database->Quote( $input );
								break;
						}
					} elseif ( $input === null ) {
						$input							=	'NULL';
					} else {
						$input							=	$_CB_database->Quote( '' );
					}

					switch ( $operator ) {
						case 'EMPTY':
						case 'IS EMPTY':
						case 'IS NULL':
							$operator					=	'IS NULL';
							$input						=	null;
							break;
						case '!EMPTY':
						case 'NOT EMPTY':
						case 'IS NOT NULL':
							$operator					=	'IS NOT NULL';
							$input						=	null;
							break;
						case 'CONTAINS':
						case 'LIKE':
							$operator					=	'LIKE';
							break;
						case '!CONTAINS':
						case 'NOT LIKE':
							$operator					=	'NOT LIKE';
							break;
						case 'REGEX':
						case 'REGEXP':
							$operator					=	'REGEXP';
							break;
						case '!REGEX':
						case '!REGEXP':
						case 'NOT REGEXP':
							$operator					=	'NOT REGEXP';
							break;
					}

					if ( $or_case ) {
						$or_cases						=	$filtering;

						unset( $or_cases[0] );
						unset( $or_cases[1] );
						unset( $or_cases[2] );

						$or_cases						=	array_values( $or_cases );

						$or								=	array();

						cbinvitesData::where( $or, $join, $or_cases, $defaultkey );

						if ( is_array( $or_case ) ) {
							if ( is_array( $or_case[0] ) ) {
								$add_or					=	( count( $or ) ? ' AND ' . implode( ' AND ', $or ) : null );
							} else {
								$add_or					=	( count( $or ) ? ' OR ' . implode( ' OR ', $or ) : null );
							}
						} else {
							$add_or						=	( count( $or ) ? ' AND ' . implode( ' AND ', $or ) : null );
						}
					} else {
						$add_or							=	null;
					}

					$where[]							=	( $add_or ? '( ' : null ) . '( ' . $key . $_CB_database->NameQuote( $var ) . ' ' . $operator . ( $input !== null ? ' ' . $input : null ) . ' )' . $add_or . ( $add_or ? ' )' : null );
				}
			}
		}
	}

	static public function order( &$orderby, &$join, $ordering = array(), $defaultkey = null ) {
		global $_CB_database;

		if ( $ordering ) {
			if ( is_array( $ordering[0] ) ) {
				foreach ( $ordering as $order ) {
					cbinvitesData::order( $orderby, $join, $order, $defaultkey );
				}
			} else {
				$var				=	( isset( $ordering[0] ) ? $ordering[0] : null );

				if ( $var ) {
					$dir			=	( isset( $ordering[1] ) ? strtoupper( $ordering[1] ) : 'ASC' );

					if ( stristr( $var, '.' ) ) {
						$key_var	=	explode( '.', $var );
						$key		=	( isset( $key_var[0] ) ? $key_var[0] : null );
						$var		=	( isset( $key_var[1] ) ? $key_var[1] : null );
					} else {
						$key		=	$defaultkey;
					}

					if ( $key ) {
						$key		=	preg_replace( '/[^-a-zA-Z0-9]/', '', $key );

						if ( $key != $defaultkey ) {
							$join[]	=	$key;
						}

						$key		=	$key . '.';
					}

					$orderby[]		=	$key . $_CB_database->NameQuote( $var ) . ( $dir == 'DESC' ? ' DESC' : null );
				}
			}
		}
	}

	static public function limit( &$rows, $limits = 0 ) {
		if ( ( ! empty( $rows ) ) && $limits )  {
			if ( is_array( $limits ) ) {
				$start	=	( isset( $limits[0] ) ? (int) $limits[0] : 0 );
				$end	=	( isset( $limits[1] ) ? (int) $limits[1] : 0 );
			} else {
				$start	=	0;
				$end	=	(int) $limits;
			}

			$rows		=	array_slice( $rows, $start, $end, true );
		}
	}

	static public function access( &$rows, $access = array() ) {
		global $_CB_framework;

		if ( ( ! empty( $rows ) ) && $access ) foreach ( $rows as $k => $row ) {
			$permissions			=	( isset( $access[0] ) ? $access[0] : null );

			if ( $permissions ) {
				if ( ! is_array( $permissions ) ) {
					$permissions	=	$access;
					$user			=	null;
					$owner			=	null;
					$and			=	false;
				} else {
					$user			=	( isset( $access[1] ) ? $access[1] : null );
					$owner			=	( isset( $access[2] ) ? $access[2] : null );
					$and			=	( isset( $access[3] ) ? $access[3] : false );
				}

				if ( ! $user ) {
					$user			=	$_CB_framework->myId();
				}

				if ( ( ! $owner ) && method_exists( $row, 'getOwner' ) ) {
					$owner			=	$row->getOwner();
				}

				$invite				=	null;

				if ( $row instanceof cbinvitesInvites ) {
					$invite			=	$row;
				}

				$authorized			=	cbinvitesClass::getAuthorization( $invite, $user, $owner );

				if ( ! cbinvitesClass::hasAccess( $permissions, $authorized, $and ) ) {
					unset( $rows[$k] );
				}
			}
		}
	}
}

class cbinvitesCaptcha {

	static public function render() {
		global $_PLUGINS;

		static $CB_loaded	=	0;

		if ( ! $CB_loaded++ ) {
			$_PLUGINS->loadPluginGroup( 'user' );
		}

		$captcha			=	$_PLUGINS->trigger( 'onGetCaptchaHtmlElements', array( false ) );

		if ( ! empty( $captcha ) ) {
			$captcha		=	$captcha[0];
			$code			=	( isset( $captcha[0] ) ? $captcha[0] : null );
			$input			=	( isset( $captcha[1] ) ? $captcha[1] : null );

			return array( 'code' => $code, 'input' => $input );
		}

		return false;
	}

	static public function validate() {
		global $_PLUGINS;

		static $CB_loaded	=	0;

		if ( ! $CB_loaded++ ) {
			$_PLUGINS->loadPluginGroup( 'user' );
		}

		$_PLUGINS->trigger( 'onCheckCaptchaHtmlElements', array() );

		if ( $_PLUGINS->is_errors() ) {
			return $_PLUGINS->getErrorMSG();
		}

		return true;
	}
}

class cbinvitesPaging {
	var $plugin		=	null;
	var $limit		=	null;
	var $limitstart	=	null;
	var $name		=	null;

	public function __construct( $name ) {
		$this->plugin	=	cbinvitesClass::getPlugin();
		$this->name		=	$name;

		cbimport( 'cb.pagination' );
	}

	public function inputName( $name ) {
		return $this->name . '_' . $name;
	}

	public function getPageNav( $total, $limitstart, $limit ) {
		return new cbPageNav( $total, $limitstart, $limit, array( $this, 'inputName' ) );
	}

	public function getlimit( $default = 0 ) {
		global $_CB_framework;

		$limit			=	( $default ? $default : $_CB_framework->getCfg( 'list_limit' ) );

		if ( ! $limit ) {
			$limit		=	10;
		}

		$this->limit	=	$_CB_framework->getUserStateFromRequest( $this->name . '_limit{' . $this->plugin->option . '}', $this->name . '_limit', (int) $limit );

		return $this->limit;
	}

	public function getLimistart( $default = 0 ) {
		global $_CB_framework;

		$this->limitstart	=	$_CB_framework->getUserStateFromRequest( $this->name . '_limitstart{' . $this->plugin->option . '}', $this->name . '_limitstart', (int) $default );

		return $this->limitstart;
	}

	public function getFilter( $filter, $default = '' ) {
		global $_CB_framework;

		$id			=	$this->name . '_' . $filter;
		$return		=	$_CB_framework->getUserStateFromRequest( $id . '{' . $this->plugin->option . '}', $id, $default );

		return $return;
	}

	public function getPagesLinks( $pageNav ) {
		$return		=	str_replace( 'class="pagenav"', '', $pageNav->getPagesLinks() );
		$return		=	preg_replace( '%(<span.+</span>)%', '<li>$1</li>', $return );
		$return		=	preg_replace( '%(<a.+</a>)%', '<li>$1</li>', $return );
		$return		=	'<ul>' . $return . '</ul>';

		return $return;
	}

	public function getLimitBox( $pageNav, $class = 'input-small' ) {
		return str_replace( 'class="inputbox"', 'class="' . $class . '"', $pageNav->getLimitBox() );
	}

	public function getInputSearch( $form, $filter, $title = '', $value = '', $size = '20', $class = 'input-large' ) {
		$id			=	htmlspecialchars( $this->name . '_' . $filter );
		$onchange	=	"document." . preg_replace( '/[^a-zA-Z0-9_]/', '', $form ) . ".submit();";
		$return		=	'<div class="input-prepend">'
					.		'<span class="add-on"><i class="icon-search"></i></span>'
					.		'<input type="text" id="' . $id . '" name="' . $id . '" onchange="' . $onchange . '" placeholder="' . htmlspecialchars( $title ) . '" value="' . htmlspecialchars( $value ) . '" class="' . htmlspecialchars( $class ) . '" size="' . (int) $size . '" />'
					.	'</div>';

		return $return;
	}

	public function getInputText( $form, $filter, $value = '', $size = '20', $class = 'inputbox' ) {
		$id			=	htmlspecialchars( $this->name . '_' . $filter );
		$onchange	=	"document." . preg_replace( '/[^a-zA-Z0-9_]/', '', $form ) . ".submit();";

		return '<input type="text" id="' . $id . '" name="' . $id . '" value="' . htmlspecialchars( $value ) . '" class="' . htmlspecialchars( $class ) . '" size="' . (int) $size . '" onchange="' . $onchange . '" />';
	}

	public function getInputSelect( $form, $filter, $values = array(), $value = '', $required = 0, $htmlspecialed = false, $class = 'inputbox' ) {
		$id			=	htmlspecialchars( $this->name . '_' . $filter );
		$onchange	=	"document." . preg_replace( '/[^a-zA-Z0-9_]/', '', $form ) . ".submit();";

		return moscomprofilerHTML::selectList( $values, $id, 'class="' . htmlspecialchars( $class ) . '" onchange="' . $onchange . '"', 'value', 'text', $value, $required, $htmlspecialed );
	}
}
?>