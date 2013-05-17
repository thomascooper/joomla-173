<?php
if ( ! ( defined( '_VALID_CB' ) || defined( '_JEXEC' ) || defined( '_VALID_MOS' ) ) ) { die( 'Direct Access to this location is not allowed.' ); }

global $_PLUGINS;

static $CB_loaded	=	0;

if ( ! $CB_loaded++ ) {
	$_PLUGINS->loadPluginGroup( 'user' );
}

class cbgjClass {

	/**
	 * prepares plugin for API usage
	 *
	 * @return object
	 */
    static public function getPlugin() {
		global $_CB_framework, $_CB_database;

		static $plugin								=	null;

		if ( ! isset( $plugin ) ) {
			$query									=	'SELECT *'
													.	"\n FROM " . $_CB_database->NameQuote( '#__comprofiler_plugin' )
													.	"\n WHERE " . $_CB_database->NameQuote( 'element' ) . " = " . $_CB_database->Quote( 'cbgroupjive' );
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
				$plugin->imgsLive					=	$site . '/images/' . str_replace( 'com_', '', $plugin->option ) . '/' . $plugin->folder;
				$plugin->imgsAbs					=	$path . '/images/' . str_replace( 'com_', '', $plugin->option ) . '/' . $plugin->folder;
				$plugin->xml						=	$plugin->absPath . '/' . $plugin->element . '.xml';
				$plugin->scheme						=	( ( isset( $_SERVER['HTTPS'] ) && ( ! empty( $_SERVER['HTTPS'] ) ) && ( $_SERVER['HTTPS'] != 'off' ) ) ? 'https' : 'http' );

				cbimport( 'language.cbteamplugins' );
			}
		}

		return $plugin;
	}

	/**
	 * generates an md5 string for static caching as unique identifier based off content
	 *
	 * @param mixed $variable
	 * @return string
	 */
    static public function getStaticID( $variable ) {
		if ( is_array( $variable ) || is_object( $variable ) ) {
			$variable	=	serialize( $variable );
		}

		return md5( $variable );
	}

	/**
	 * prepare GroupJive Itemid if not found return CB Itemid
	 *
	 * @param boolean $htmlspecialchars
	 * @param string $task
	 * @return string
	 */
    static public function getItemid( $htmlspecialchars = false, $task = null ) {
		global $_CB_framework, $_CB_database;

		static $Itemid				=	array();

		if ( ! isset( $Itemid[$task] ) ) {
			$plugin					=	cbgjClass::getPlugin();
			$generalItemid			=	$plugin->params->get( 'general_itemid', null );
			$url					=	'index.php?option=com_comprofiler&task=pluginclass&plugin=cbgroupjive';

			if ( $task ) {
				$url				.=	$task;
			}

			$url					.=	'%';

			if ( ( ! $generalItemid ) || $task ) {
				$query				=	'SELECT ' . $_CB_database->NameQuote( 'id' )
									.	"\n FROM " . $_CB_database->NameQuote( '#__menu' )
									.	"\n WHERE " . $_CB_database->NameQuote( 'link' ) . " LIKE " . $_CB_database->Quote( $url )
									.	"\n AND " . $_CB_database->NameQuote( 'published' ) . " = 1"
									.	"\n AND " . $_CB_database->NameQuote( 'access' ) . " IN ( " . implode( ',', cbToArrayOfInt( CBuser::getMyInstance()->getAuthorisedViewLevelsIds( ( checkJversion() >= 2 ? false : true ) ) ) ) . " )"
									.	( checkJversion() >= 2 ? "\n AND " . $_CB_database->NameQuote( 'language' ) . " IN ( " . $_CB_database->Quote( $_CB_framework->getCfg( 'lang_tag' ) ) . ", '*', '' )" : null );
				$_CB_database->setQuery( $query );
				$Itemid[$task]		=	$_CB_database->loadResult();

				if ( ( ! $Itemid[$task] ) && $task ) {
					$Itemid[$task]	=	cbgjClass::getItemid( 0 );
				} elseif ( ! $Itemid[$task] ) {
					$Itemid[$task]	=	getCBprofileItemid( null );
				}
			} else {
				$Itemid[$task]		=	$generalItemid;
			}
		}

		if ( is_bool( $htmlspecialchars ) ) {
			return ( $htmlspecialchars ? '&amp;' : '&' ) . 'Itemid=' . $Itemid[$task];
		} else {
			return $Itemid[$task];
		}
	}

	/**
	 * prepares a URL for component plugin usage
	 *
	 * @param  array   $variables
	 * @param  string  $msg
	 * @param  boolean $htmlspecialchars
	 * @param  boolean $redirect
	 * @param  string  $type
	 * @param  boolean $return
	 * @param  boolean $ajax
	 * @param boolean $back
	 * @return string
	 */
    static public function getPluginURL( $variables = array(), $msg = null, $htmlspecialchars = true, $redirect = false, $type = null, $return = false, $ajax = false, $back = false ) {
		global $_CB_framework;

		$getReturn				=	cbgjClass::getReturnURL();

		if ( $back && $getReturn ) {
			$url				=	$getReturn;
		} else {
			$plugin				=	cbgjClass::getPlugin();
			$generalDynamicid	=	$plugin->params->get( 'general_dynamicid', 0 );
			$action				=	( isset( $variables[0] ) ? '&action=' . urlencode( $variables[0] ) : null );

			if ( $return === 'current' ) {
				$setReturn		=	( $getReturn ? '&return=' . base64_encode( $getReturn ) : null );
			} else {
				$setReturn		=	( $return ? cbgjClass::setReturnURL() : null );
			}

			if ( $_CB_framework->getUi() == 2 ) {
				$function		=	( isset( $variables[1] ) ? '.' . urlencode( $variables[1] ) : null );
				$id				=	( isset( $variables[2] ) ? '&id=' . urlencode( $variables[2] ) : null );
				$vars			=	$action . $function . $id . $setReturn;
				$format			=	( $ajax ? 'raw' : 'html' );
				$url			=	'index.php?option=' . $plugin->option . '&task=editPlugin&cid=' . $plugin->id . $vars;

				if ( $htmlspecialchars ) {
					$url		=	htmlspecialchars( $url );
				}

				$url			=	$_CB_framework->backendUrl( $url, $htmlspecialchars, $format );
			} else {
				$function		=	( isset( $variables[1] ) ? '&func=' . urlencode( $variables[1] ) : null );
				$category		=	( isset( $variables[2] ) ? '&cat=' . urlencode( $variables[2] ) : null );
				$group			=	( isset( $variables[3] ) ? '&grp=' . urlencode( $variables[3] ) : null );
				$id				=	( isset( $variables[4] ) ? '&id=' . urlencode( $variables[4] ) : null );
				$vars			=	$action . $function . $category . $group . $id;

				if ( isset( $variables[5] ) && is_array( $variables[5] ) ) foreach( $variables[5] as $k => $v ) {
					$vars		.=	'&' . urlencode( $k ) . '=' . urlencode( $v );
				}

				if ( $generalDynamicid ) {
					$vars		.=	cbgjClass::getItemid( false, $vars );
				} else {
					$vars		.=	cbgjClass::getItemid();
				}

				$vars			.=	$setReturn;
				$format			=	( $ajax ? 'component' : 'html' );
				$url			=	cbSef( 'index.php?option=' . $plugin->option . '&task=pluginclass&plugin=' . $plugin->element . $vars, $htmlspecialchars, $format );
			}
		}

		if ( $msg ) {
			if ( $redirect ) {
				cbgjClass::setRedirect( $url, ( $msg === true ? null : $msg ), $type );
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
	/**
	 * prepares a URL for CB usage
	 *
	 * @param  string  $task
	 * @param  string  $msg
	 * @param  boolean $htmlspecialchars
	 * @param  boolean $redirect
	 * @param  string  $type
	 * @param  boolean $return
	 * @param  boolean $ajax
	 * @return string
	 */
    static public function getCBURL( $task = null, $msg = null, $htmlspecialchars = true, $redirect = false, $type = null, $return = false, $ajax = false ) {
		$plugin				=	cbgjClass::getPlugin();

		if ( is_integer( $task ) ) {
			$itemidtask		=	'userprofile';
			$task			=	( $task ? '&task=userprofile&user=' . urlencode( $task ) : null );
		} else {
			$itemidtask		=	$task;
			$task			=	( $task ? '&task=' . urlencode( $task ) : null );
		}

		$itemid				=	( $itemidtask ? getCBprofileItemid( false, $itemidtask ) : getCBprofileItemid() );
		$setReturn			=	( $return ? cbgjClass::setReturnURL() : null );
		$vars				=	$task . $itemid . $setReturn;
		$format				=	( $ajax ? 'component' : 'html' );
		$url				=	cbSef( 'index.php?option=' . $plugin->option . $vars, $htmlspecialchars, $format );

		if ( $msg ) {
			if ( $redirect ) {
				cbgjClass::setRedirect( $url, ( $msg === true ? null : $msg ), $type );
			} else {
				if ( $msg === true ) {
					$url	=	"javascript: location.href = '" . addslashes( $url ) . "';";
				} else {
					$url	=	"javascript: if ( confirm( '" . addslashes( $msg ) . "' ) ) { location.href = '" . addslashes( $url ) . "'; }";
				}
			}
		}

		return $url;
	}

	/**
	 * build URL and encode for returning to previous location
	 *
	 * @param  boolean $raw
	 * @return string
	 */
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

	/**
	 * get URL and decode for returning to previous location
	 *
	 * @return string
	 */
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

	/**
	 * prepares and sends notification (email or PM)
	 *
	 * @param mixed  $to
	 * @param int    $from
	 * @param mixed  $subject
	 * @param mixed  $message
	 * @param int    $replace
	 * @param object $category
	 * @param object $group
     * @param boolean $type
	 */
    static public function getNotification( $to, $from, $subject, $message, $replace = null, $category = null, $group = null, $type = false ) {
		global $_CB_framework, $_CB_database, $ueConfig, $_CB_PMS, $_PLUGINS;

		$plugin					=	cbgjClass::getPlugin();
		$user					=&	CBuser::getUserDataInstance( $_CB_framework->myId() );
		$notifyBy				=	$plugin->params->get( 'general_notifyby', 1 );
		$generalTitle			=	CBTxt::T( $plugin->params->get( 'general_title', null ) );
		$msgSiteName			=	$_CB_framework->getCfg( 'sitename' );
		$msgOverviewName		=	( $generalTitle ? $generalTitle : $plugin->name );

		if ( isset( $from->id ) || preg_match( '!^\d+$!', $from ) ) {
			if ( is_object( $from ) ) {
				$fromId			=	$from->id;
			} else {
				$fromId			=	$from;
			}

			$cbUserFrom			=&	CBuser::getInstance( $fromId );

			if ( ! $cbUserFrom ) {
				$cbUserFrom		=&	CBuser::getInstance( null );
			}

			$userFrom			=&	$cbUserFrom->getUserData();
		}

		if ( isset( $userFrom->id ) ) {
			$fromName			=	$cbUserFrom->getField( 'formatname', null, 'html', 'none', 'profile', 0, true );
			$fromEmail			=	$userFrom->email;

			if ( $replace == 1 ) {
				$msgUserUrl		=	cbSef( 'index.php?option=com_comprofiler&task=userprofile&user=' . (int) $userFrom->id . getCBprofileItemid( false ), false );
				$msgUserName	=	$fromName;
				$msgUser		=	'<a href="' . $msgUserUrl . '">' . $msgUserName . '</a>';
				$subject		=	$cbUserFrom->replaceUserVars( $subject, false );
				$message		=	$cbUserFrom->replaceUserVars( $message, false );
			}
		} else {
			$fromId				=	0;
			$fromName			=	$_CB_framework->getCfg( 'fromname' );
			$fromEmail			=	$_CB_framework->getCfg( 'mailfrom' );
		}

		if ( isset( $to->id ) || preg_match( '!^\d+$!', $to ) ) {
			if ( is_object( $to ) ) {
				$toId			=	$to->id;
			} else {
				$toId			=	$to;
			}

			$cbUserTo			=&	CBuser::getInstance( $toId );

			if ( ! $cbUserTo ) {
				$cbUserTo		=&	CBuser::getInstance( null );
			}

			$userTo				=&	$cbUserTo->getUserData();
		}

		if ( isset( $userTo->id ) ) {
			$toEmail			=	$userTo->email;

			if ( $replace == 2 ) {
				$msgUserUrl	=	cbSef( 'index.php?option=com_comprofiler&task=userprofile&user=' . (int) $userTo->id . getCBprofileItemid( false ), false );
				$msgUserName	=	$cbUserTo->getField( 'formatname', null, 'html', 'none', 'profile', 0, true );
				$msgUser		=	'<a href="' . $msgUserUrl . '">' . $msgUserName . '</a>';
				$subject		=	$cbUserTo->replaceUserVars( $subject, false );
				$message		=	$cbUserTo->replaceUserVars( $message, false );
			}
		} else {
			$toId				=	0;

			if ( cbIsValidEmail( $to ) ) {
				$toEmail		=	$to;
			} else {
				$toEmail		=	null;
			}
		}

		if ( $plugin->params->get( 'notifications_from_name' ) ) {
			$fromName			=	$plugin->params->get( 'notifications_from_name' );
		}

		if ( $plugin->params->get( 'notifications_from_address' ) ) {
			$fromEmail			=	$plugin->params->get( 'notifications_from_address' );
		}

		$msgStrings				=	array(	'[site_name]',
											'[site]',
											'[admin_override]',
											'[admins_override]',
											'[moderator_override]',
											'[moderators_override]',
											'[owner_override]',
											'[panel_override]',
											'[overview_override]',
											'[overview_name]',
											'[overview]',
											'[categories_override]',
											'[category_override]',
											'[category_id]',
											'[category_name]',
											'[category]',
											'[groups_override]',
											'[group_override]',
											'[group_id]',
											'[group_name]',
											'[group]',
											'[users_override]',
											'[user_override]',
											'[user_name]',
											'[user]'
										);

		$msgValues				=	array(	$msgSiteName,
											'<a href="' . $_CB_framework->getCfg( 'live_site' ) . '">' . $msgSiteName . '</a>',
											cbgjClass::getOverride( 'admin' ),
											cbgjClass::getOverride( 'admin', true ),
											cbgjClass::getOverride( 'moderator' ),
											cbgjClass::getOverride( 'moderator', true ),
											cbgjClass::getOverride( 'owner' ),
											cbgjClass::getOverride( 'panel' ),
											cbgjClass::getOverride( 'overview' ),
											$msgOverviewName,
											'<a href="' . cbgjClass::getPluginURL( array( 'overview' ) ) . '">' . $msgOverviewName . '</a>',
											cbgjClass::getOverride( 'category', true ),
											cbgjClass::getOverride( 'category' ),
											( isset( $category->id ) ? $category->get( 'id' ) : null ),
											( isset( $category->id ) ? $category->getName() : null ),
											( isset( $category->id ) ? $category->getName( 0, true ) : null ),
											cbgjClass::getOverride( 'group', true ),
											cbgjClass::getOverride( 'group' ),
											( isset( $group->id ) ? $group->get( 'id' ) : null ),
											( isset( $group->id ) ? $group->getName() : null ),
											( isset( $group->id ) ? $group->getName( 0, true ) : null ),
											cbgjClass::getOverride( 'user', true ),
											cbgjClass::getOverride( 'user' ),
											( isset( $msgUserName ) ? $msgUserName : null ),
											( isset( $msgUser ) ? $msgUser : null )
										);

		$_PLUGINS->trigger( 'gj_onBeforeNotification', array( array( $fromId, $fromName, $fromEmail, $toId, $toEmail, $subject, $message ), $group, $category, $user, $plugin ) );

		$subject				=	trim( strip_tags( str_replace( $msgStrings, $msgValues, $subject ) ) );
		$message				=	cbgjClass::getFilteredText( str_replace( $msgStrings, $msgValues, $message ) );

		if ( $toId ) {
			if ( ( $notifyBy == 4 ) || ( $type == 4 ) ) {
				comprofilerMail( $fromEmail, $fromName, $toEmail, $subject, $message, 1 );
			} elseif ( ( $notifyBy == 3 ) || ( $type == 3 ) ) {
				$_CB_PMS->sendPMSMSG( $toId, $fromId, $subject, $message, true );
			} elseif ( ( $notifyBy == 2 ) || ( $type == 2 ) ) {
				$_CB_PMS->sendPMSMSG( $toId, $fromId, $subject, $message, true );

				comprofilerMail( $fromEmail, $fromName, $toEmail, $subject, $message, 1 );
			} elseif ( ( $notifyBy == 1 ) || ( $type == 1 ) ) {
				if ( ! $_CB_PMS->sendPMSMSG( $toId, $fromId, $subject, $message, true ) ) {
					comprofilerMail( $fromEmail, $fromName, $toEmail, $subject, $message, 1 );
				}
			}
		} elseif ( $toEmail ) {
			comprofilerMail( $fromEmail, $fromName, $toEmail, $subject, $message, 1 );
		} else {
			$moderators			=	implode( ',', $_CB_framework->acl->get_group_parent_ids( $ueConfig['imageApproverGid'] ) );

			if ( $moderators ) {
				$query			=	'SELECT ' . $_CB_database->NameQuote( 'email' )
								.	"\n FROM " . $_CB_database->NameQuote( '#__users' ) . " AS a"
								.	"\n INNER JOIN " . $_CB_database->NameQuote( '#__comprofiler' ) . " AS b"
								.	' ON b.' . $_CB_database->NameQuote( 'user_id' ) . ' = a.' . $_CB_database->NameQuote( 'id' );

				if ( checkJversion() == 2 ) {
					$query		.=	"\n INNER JOIN " . $_CB_database->NameQuote( '#__user_usergroup_map' ) . " AS c"
								.	' ON b.' . $_CB_database->NameQuote( 'id' ) . ' = c.' . $_CB_database->NameQuote( 'user_id' )
								.	"\n WHERE c." . $_CB_database->NameQuote( 'group_id' ) . " IN ( " . $moderators . " )";
				} else {
					$query		.=	"\n WHERE a." . $_CB_database->NameQuote( 'gid' ) . " IN ( " . $moderators . " )";
				}

				$query			.=	"\n AND a." . $_CB_database->NameQuote( 'block' ) . " = 0"
								.	"\n AND a." . $_CB_database->NameQuote( 'sendEmail' ) . " = 1"
								.	"\n AND b." . $_CB_database->NameQuote( 'confirmed' ) . " = 1"
								.	"\n AND b." . $_CB_database->NameQuote( 'approved' ) . " = 1"
								.	"\n AND b." . $_CB_database->NameQuote( 'banned' ) . " = 0";
				$_CB_database->setQuery( $query );
				$mods			=	$_CB_database->loadResultArray();

				foreach ( $mods AS $mod ) {
					comprofilerMail( $fromEmail, $fromName, $mod, $subject, $message, 1 );
				}
			}
		}

		$_PLUGINS->trigger( 'gj_onAfterNotification', array( array( $fromId, $fromName, $fromEmail, $toId, $toEmail, $subject, $message ), $group, $category, $user, $plugin ) );
	}

	/**
	 * prepares and returns integrations based off CB trigger
	 *
	 * @param  string $trigger
	 * @param  array  $variables
	 * @param  string $error
	 * @param  string $display
	 * @param  string $attr
	 * @return mixed
	 */
    static public function getIntegrations( $trigger, $variables = array(), $error = null, $display = 'div', $attr = null ) {
		global $_PLUGINS;

		static $cache			=	array();

		if ( ! isset( $cache[$trigger] ) ) {
			$integrations		=	array_filter( $_PLUGINS->trigger( $trigger, $variables ) );
			$return				=	null;

			if ( is_array( $attr ) ) {
				$attrContainer	=	$attr[0];
				$attrElement	=	$attr[1];
			} else {
				$attrContainer	=	$attr;
				$attrElement	=	$attr;
			}

			switch ( $display ) {
				case 'div':
					$open		=	'<div' . $attrElement . '>';
					$close		=	'</div>';
					break;
				case 'span':
					$open		=	'<span' . $attrElement . '>';
					$close		=	'</span>';
					break;
				case 'br':
					$close		=	'<br' . $attrElement . ' />';
					break;
				case 'p':
					$open		=	'<p' . $attrElement . '>';
					$close		=	'</p>';
					break;
				case 'hr':
					$close		=	'<hr' . $attrElement . ' />';
					break;
				case 'li':
					$open		=	'<li' . $attrElement . '>';
					$close		=	'</li>';
					break;
				case 'ulli':
					$open		=	'<ul' . $attrContainer . '><li' . $attrElement . '>';
					$close		=	'</li></ul>';
					break;
				case 'olli':
					$open		=	'<ol' . $attrContainer . '><li' . $attrElement . '>';
					$close		=	'</li></ol>';
					break;
				case 'td':
					$open		=	'<td' . $attrElement . '>';
					$close		=	'</td>';
					break;
				case 'th':
					$open		=	'<th' . $attrElement . '>';
					$close		=	'</th>';
					break;
				case 'trtd':
					$open		=	'<tr' . $attrContainer . '><td' . $attrElement . '>';
					$close		=	'</td></tr>';
					break;
				case 'trth':
					$open		=	'<tr' . $attrContainer . '><th' . $attrElement . '>';
					$close		=	'</th></tr>';
					break;
				default:
					$open		=	'';
					$close		=	'';
					break;
			}

			if ( $integrations ) {
				if ( $display == 'raw' ) {
					$return		=	$integrations;
				} else {
					$return		=	$open . implode( $close . $open, $integrations ) . $close;
				}
			} elseif ( $error ) {
				$return			=	$open . $error . $close;
			}

			$cache[$trigger]	=	$return;
		}

		return $cache[$trigger];
	}

	/**
	 * returns template based on file and template selection
	 *
	 * @param mixed $files
	 * @param bool $loadGlobal
	 * @param bool $loadHeader
	 */
    static public function getTemplate( $files = null, $loadGlobal = true, $loadHeader = true ) {
		global $_CB_framework, $_PLUGINS;

		static $tmpl							=	array();

		$id										=	cbgjClass::getStaticID( array( $files, $loadGlobal, $loadHeader ) );

		if ( ! isset( $tmpl[$id] ) ) {
			$plugin								=	cbgjClass::getPlugin();
			$template							=	$plugin->params->get( 'general_template', 'default' );
			$files								=	( ! is_array( $files ) ? array( $files ) : $files );
			$paths								=	array( 'global_css' => null, 'php' => null, 'css' => null, 'js' => null, 'override_css' => null );

			foreach ( $files as $file ) {
				$file							=	preg_replace( '/[^-a-zA-Z0-9_]/', '', $file );

				$_PLUGINS->trigger( 'gj_onBeforeTemplate', array( $file, $template, $loadGlobal, $loadHeader, $plugin ) );

				$globalCss						=	'/templates/' . $template . '/template.css';
				$overrideCss					=	'/templates/' . $template . '/override.css';

				if ( $file ) {
					$php						=	$plugin->absPath . '/templates/' . $template . '/' . $file . '.php';
					$css						=	'/templates/' . $template . '/' . $file . '.css';
					$js							=	'/templates/' . $template . '/' . $file . '.js';
				} else {
					$php						=	null;
					$css						=	null;
					$js							=	null;
				}

				$_PLUGINS->trigger( 'gj_onAfterTemplate', array( $file, $template, array( $globalCss, $php, $css, $js, $overrideCss ), $loadGlobal, $loadHeader, $plugin ) );

				if ( $loadGlobal && $loadHeader ) {
					if ( ! file_exists( $plugin->absPath . $globalCss ) ) {
						$globalCss				=	'/templates/default/template.css';
					}

					if ( file_exists( $plugin->absPath . $globalCss ) ) {
						$_CB_framework->document->addHeadStyleSheet( $plugin->livePath . $globalCss );

						$paths['global_css']	=	$plugin->livePath . $globalCss;
					}
				}

				if ( $file ) {
					if ( ! file_exists( $php ) ) {
						$php					=	$plugin->absPath . '/templates/default/' . $file . '.php';
					}

					if ( file_exists( $php ) ) {
						require_once( $php );

						$paths['php']			=	$php;
					}

					if ( $loadHeader ) {
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
				}

				if ( $loadGlobal && $loadHeader ) {
					if ( file_exists( $plugin->absPath . $overrideCss ) ) {
						$_CB_framework->document->addHeadStyleSheet( $plugin->livePath . $overrideCss );

						$paths['override_css']	=	$plugin->livePath . $overrideCss;
					}
				}
			}

			$tmpl[$id]							=	$paths;
		}
	}

    /**
     * prepares form validation jQuery
     *
     * @param string $selector
     * @param string $params
     */
    static public function getFormValidation( $selector = '#gjForm', $params = null ) {
		static $vld			=	array();

		if ( ! isset( $vld[$selector] ) ) {
			cbgjClass::getTemplate( 'jquery_validation', false );

			HTML_groupjiveFormValidation::loadJquery( $selector, $params );

			$vld[$selector]	=	true;
		}
	}

    /**
     *  parepares user access permission array
     *
     * @param cbgjCategory $category
     * @param cbgjGroup $group
     * @param moscomprofilerUser $user
     * @param moscomprofilerUser $owner
     * @param mixed $row
     * @return mixed
     */
    static public function getAuthorization( $category = null, $group = null, $user = null, $owner = null, $row = null ) {
		global $_CB_framework;

		$plugin											=	cbgjClass::getPlugin();
		$groupMessagePerm								=	$plugin->params->get( 'group_message_perm', 3 );
		$groupInvitesDisplay							=	$plugin->params->get( 'group_invites_display', 1 );

		if ( ! $user ) {
			$user										=	$_CB_framework->myId();
		}

		if ( ! is_object( $user ) ) {
			$user										=&	CBuser::getUserDataInstance( (int) $user );
		}

		if ( ! $user ) {
			$user										=&	CBuser::getUserDataInstance( null );
		}

		static $myCmsGids								=	array();

		if ( ! isset( $myCmsGids[$user->id] ) ) {
			$myCmsGids[$user->id]						=	$_CB_framework->acl->get_groups_below_me( (int) $user->id, true );
		}

		$myGids											=	$myCmsGids[$user->id];

		static $cats									=	array();

		if ( $category && ( ( ! is_object( $category ) ) || ( is_object( $category ) && ( ! $category instanceof cbgjCategory ) ) ) ) {
			if ( is_object( $category ) ) {
				if ( isset( $category->id ) ) {
					$catid								=	$category->id;
				} else {
					$catid								=	0;
				}
			} else {
				$catid									=	$category;
			}

			if ( ! isset( $cats[$catid] ) ) {
				$cats[$catid]							=	cbgjData::getCategories( null, array( 'id', '=', (int) $catid ), null, null, false );
			}

			$category									=	$cats[$catid];
		} elseif ( $category && ( $category instanceof cbgjCategory ) ) {
			if ( ! isset( $cats[$category->id] ) ) {
				$cats[$category->id]					=	$category;
			}

			$category									=	$cats[$category->id];
		}

		static $grps									=	array();

		if ( $group && ( ( ! is_object( $group ) ) || ( is_object( $group ) && ( ! $group instanceof cbgjGroup ) ) ) ) {
			if ( is_object( $group ) ) {
				if ( isset( $group->id ) ) {
					$grpid								=	$group->id;
				} else {
					$grpid								=	0;
				}
			} else {
				$grpid									=	$group;
			}

			if ( ! isset( $grps[$grpid] ) ) {
				$grps[$grpid]							=	cbgjData::getGroups( null, array( 'id', '=', (int) $grpid ), null, null, false );
			}

			$group										=	$grps[$grpid];
		} elseif ( $group && ( $group instanceof cbgjGroup ) ) {
			if ( ! isset( $grps[$group->id] ) ) {
				$grps[$group->id]						=	$group;
			}

			$group										=	$grps[$group->id];
		}

		if ( ( ! isset( $category->id ) ) && isset( $group->id ) ) {
			if ( ! isset( $cats[$group->category] ) ) {
				$cats[$group->category]					=	$group->getCategory();
			}

			$category									=	$cats[$group->category];
		}

		static $owners									=	array();

		if ( $owner ) {
			if ( ! is_object( $owner ) ) {
				if ( ! isset( $owners[$owner] ) ) {
					$owners[$owner]						=&	CBuser::getUserDataInstance( (int) $owner );
				}

				$owner									=	$owners[$owner];
			}
		} elseif ( isset( $category->id ) && ( ! isset( $group->id ) ) ) {
			if ( ! isset( $owners[$category->user_id] ) ) {
				$owners[$category->user_id]				=	$category->getOwner();
			}

			$owner										=	$owners[$category->user_id];
		} elseif ( isset( $group->id ) ) {
			if ( ! isset( $owners[$group->user_id] ) ) {
				$owners[$group->user_id]				=	$group->getOwner();
			}

			$owner										=	$owners[$group->user_id];
		}

		static $cache									=	array();

		$id												=	$user->id . ( isset( $category->id ) ? $category->id : 0 ) . ( isset( $group->id ) ? $group->id : 0 ) . ( isset( $owner->id ) ? $owner->id : 0 ) . ( isset( $row->id ) ? $row->id : 0 );

		if ( ( ! isset( $cache[$id] ) ) || cbgjClass::resetCache() ) {
			$access										=	array();

			if ( $user->id ) {
				$access[]								=	'usr_reg'; // Registered
			} else {
				$access[]								=	'usr_guest'; // Guest
			}

			if ( cbgjClass::hasAccess( 'usr_reg', $access ) ) {
				if ( $_CB_framework->acl->get_user_moderator( $user->id ) ) {
					$access[]							=	'usr_mod'; // Moderator
				}

				if ( isset( $owner->id ) ) {
					if ( $owner->id == $user->id ) {
						$access[]						=	'usr_me'; // Me
					}
				}

				if ( $plugin->params->get( 'overview_panel', 1 ) || cbgjClass::hasAccess( 'usr_mod', $access ) ) {
					$access[]							=	'usr_panel'; // User Panel
				}

				if ( $plugin->params->get( 'general_notifications', 1 ) || cbgjClass::hasAccess( 'usr_mod', $access ) ) {
					$access[]							=	'usr_notifications'; // User Notifications

					if ( cbgjClass::hasAccess( 'usr_mod', $access ) ) {
						$access[]						=	'gen_usr_notifications'; // General User Notifications
					}
				}
			}

			if ( isset( $category->id ) ) {
				$access[]								=	'cat'; // Category Exists

				if ( cbgjClass::hasAccess( 'cat', $access ) ) {
					if ( $category->getParentAccess( array( array( 'cat_access', 'mod_lvl1' ), $user ), true ) ) {
						if ( $category->get( 'published' ) == 1 ) {
							$access[]					=	'cat_pub'; // Category Published
						}

						if ( cbgjClass::hasAccess( 'usr_mod', $access ) ) {
							$access[]					=	'cat_access'; // Category Access
						} else {
							if ( cbgjClass::hasAccess( 'cat_pub', $access ) ) {
								if ( in_array( $category->get( 'access' ), $myGids ) ) {
									$access[]			=	'cat_access'; // Category Access
								}
							}
						}

						if ( cbgjClass::hasAccess( 'usr_reg', $access ) ) {
							if ( ( $category->get( 'user_id' ) == $user->id ) || $category->getParentAccess( array( 'cat_owner', $user ) ) ) {
								$access[]				=	'cat_owner'; // Category Owner
							}

							if ( $category->get( 'user_id' ) == $user->id ) {
								$access[]				=	'cat_creator'; // Category Creator
							}

							if ( count( cbgjData::getGroups( null, array( array( 'user_id', '=', $user->id ), array( 'category', '=', $category->get( 'id' ), array( 'b.parent', '=', $category->get( 'id' ) ) ) ) ) ) ) {
								$access[]				=	'cat_has_grp'; // User Has Group In Category
							}

							if ( count( cbgjData::getUsers( null, array( array( 'user_id', '=', $user->id ), array( 'status', '>=', 1 ), array( 'c.id', '=', $category->get( 'id' ), array( 'c.parent', '=', $category->get( 'id' ) ) ) ) ) ) ) {
								$access[]				=	'cat_is_usr'; // User Belongs To A Group In Category
							}
						}
					}
				}
			}

			if ( isset( $group->id ) && cbgjClass::hasAccess( 'cat_access', $access ) ) {
				$access[]								=	'grp'; // Group Exists

				if ( cbgjClass::hasAccess( 'grp', $access ) ) {
					if ( $group->getParentAccess( array( array( 'grp_access', 'mod_lvl2' ), $user ), true ) ) {
						if ( $group->get( 'published' ) == 1 ) {
							$access[]					=	'grp_pub'; // Group Published
						}

						if ( cbgjClass::hasAccess( 'usr_reg', $access ) ) {
							if ( ( $group->get( 'user_id' ) == $user->id ) || $group->getParentAccess( array( 'grp_owner', $user ) ) ) {
								$access[]				=	'grp_owner'; // Group Owner
							}

							if ( $group->get( 'user_id' ) == $user->id ) {
								$access[]				=	'grp_creator'; // Group Creator
							}

							if ( ! cbgjClass::hasAccess( 'grp_creator', $access ) ) {
								$gjUsers				=	cbgjData::getUsers( null, array( 'user_id', '=', $user->id ) );

								if ( $gjUsers ) foreach ( $gjUsers as $gjUser ) {
									if ( $group->get( 'id' ) == $gjUser->get( 'group' ) ) {
										$access[]		=	'grp_usr'; // Group User

										if ( $gjUser->get( 'status' ) == -1 ) {
											$access[]	=	'grp_usr_banned'; // Group User Banned
										}

										if ( $gjUser->get( 'status' ) == 2 ) {
											$access[]	=	'grp_usr_mod'; // Group User Moderator
										}

										if ( $gjUser->get( 'status' ) == 3 ) {
											$access[]	=	'grp_usr_admin'; // Group User Admin
										}

										if ( ! in_array( $gjUser->get( 'status' ), array( 0, -1 ) ) ) {
											$access[]	=	'grp_usr_active'; // Group User Active
										} elseif ( $gjUser->status == 0 ) {
											$access[]	=	'grp_usr_inactive'; // Group User In-active
										}
									}
								}

								if ( $groupInvitesDisplay && ( ( ( $group->get( 'invite' ) == 0 ) && cbgjClass::hasAccess( array( 'grp_usr_active', 'grp_owner' ), $access ) ) ||
								   ( ( $group->get( 'invite' ) == 1 ) && cbgjClass::hasAccess( array( 'grp_usr_mod', 'grp_usr_admin', 'grp_owner' ), $access ) ) ||
								   ( ( $group->get( 'invite' ) == 2 ) && cbgjClass::hasAccess( array( 'grp_usr_admin', 'grp_owner' ), $access ) ) ||
								   ( ( $group->get( 'invite' ) == 3 ) && cbgjClass::hasAccess( 'grp_owner', $access ) ) ) ) {
									$access[]			=	'grp_invite'; // Group User Can Invite
								}

								if ( ! cbgjClass::hasAccess( array( 'usr_guest', 'grp_usr', 'grp_creator' ), $access ) ) {
									$invited			=	cbgjData::getInvites( null, array( array( 'email', '=', $user->email, array( 'user', '=', $user->id ) ), array( 'accepted', 'IN', array( '0000-00-00', '0000-00-00 00:00:00', '', null ) ) ) );

									if ( $invited ) foreach ( $invited as $invite ) {
										if ( $group->id == $invite->group ) {
											$access[]	=	'grp_invited'; // User is invited to group
										}
									}
								}

								if ( ( ! cbgjClass::hasAccess( array( 'grp_creator', 'usr_guest', 'grp_usr' ), $access ) ) && ( $group->get( 'user_id' ) != $user->id ) && ( ( ( $group->get( 'type' ) == 1 ) || ( $group->get( 'type' ) == 2 ) ) || cbgjClass::hasAccess( array( 'usr_mod', 'cat_owner', 'grp_invited' ), $access ) ) ) {
									$access[]			=	'grp_join'; // Group Join
								}

								if ( ( $plugin->params->get( 'group_leave', 1 ) || cbgjClass::hasAccess( array( 'usr_mod', 'cat_owner' ), $access ) ) && cbgjClass::hasAccess( 'grp_usr', $access ) && ( ! cbgjClass::hasAccess( array( 'grp_creator', 'grp_usr_banned' ), $access ) ) ) {
									$access[]			=	'grp_leave'; // Group Leave
								}
							} else {
								$access[]				=	'grp_usr_active'; // Group Creator Always Active

								if ( $groupInvitesDisplay ) {
									$access[]			=	'grp_invite'; // Group Creator Always Invite
								}
							}
						}

						if ( ( $group->get( 'type' ) == 3 ) && ( ! cbgjClass::hasAccess( array( 'grp_usr_active', 'grp_owner', 'cat_owner', 'usr_mod', 'grp_invited' ), $access ) ) ) {
							$access[]					=	'grp_private'; // Group Flagged as Private for User
						} else {
							$access[]					=	'grp_public'; // Group Flagged as Public for User
						}

						if ( cbgjClass::hasAccess( 'usr_mod', $access ) ) {
							$access[]					=	'grp_access'; // Group Access
						} else {
							if ( cbgjClass::hasAccess( 'grp_pub', $access ) && ( ! cbgjClass::hasAccess( 'grp_usr_banned', $access ) ) ) {
								if ( in_array( $group->get( 'access' ), $myGids ) && cbgjClass::hasAccess( 'grp_public', $access ) ) {
									$access[]			=	'grp_access'; // Group Access
								}
							}
						}
					}
				}
			}

			if ( cbgjClass::hasAccess( 'usr_reg', $access ) ) {
				if ( cbgjClass::hasAccess( array( 'usr_mod', 'cat_owner' ), $access ) ) {
					$access[]							=	'mod_lvl1'; // Moderation Level
				}

				if ( cbgjClass::hasAccess( array( 'mod_lvl1', 'grp_owner' ), $access ) ) {
					$access[]							=	'mod_lvl2'; // Moderation Level
				}

				if ( cbgjClass::hasAccess( array( 'mod_lvl2', 'grp_usr_admin' ), $access ) ) {
					$access[]							=	'mod_lvl3'; // Moderation Level
				}

				if ( cbgjClass::hasAccess( array( 'mod_lvl3', 'grp_usr_mod' ), $access ) ) {
					$access[]							=	'mod_lvl4'; // Moderation Level
				}

				if ( cbgjClass::hasAccess( array( 'mod_lvl4', 'grp_usr_active' ), $access ) ) {
					$access[]							=	'mod_lvl5'; // Moderation Level
				}

				if ( ( $plugin->params->get( 'category_create', 1 ) && in_array( $plugin->params->get( 'category_create_access', -1 ), $myGids ) ) || cbgjClass::hasAccess( 'usr_mod', $access ) ) {
					$access[]							=	'cat_create'; // Category Create New
				}

				if ( ( $plugin->params->get( 'category_nested', 0 ) && in_array( $plugin->params->get( 'category_nested_access', -1 ), $myGids ) ) || cbgjClass::hasAccess( 'usr_mod', $access ) ) {
					$access[]							=	'cat_nested'; // Nested Category Access
				}

				if ( ( $plugin->params->get( 'group_create', 1 ) && in_array( $plugin->params->get( 'group_create_access', -1 ), $myGids ) ) || cbgjClass::hasAccess( 'usr_mod', $access ) ) {
					$access[]							=	'grp_create'; // Group Create New
				}

				if ( ( $plugin->params->get( 'group_nested', 0 ) && in_array( $plugin->params->get( 'group_nested_access', -1 ), $myGids ) ) || cbgjClass::hasAccess( 'usr_mod', $access ) ) {
					$access[]							=	'grp_nested'; // Nested Group Access
				}
			}

			if ( cbgjClass::hasAccess( 'cat', $access ) ) {
				if ( cbgjClass::hasAccess( 'usr_reg', $access ) ) {
					if ( ( ( ! $category->get( 'parent' ) ) && cbgjClass::hasAccess( 'usr_mod', $access ) ) || ( $category->get( 'parent' ) && $category->getParentAccess( array( 'mod_lvl1', $user ) ) ) ) {
						$access[]						=	'cat_can_publish'; // Category can be Published
					}
				}

				if ( $category->get( 'published' ) || cbgjClass::hasAccess( 'cat_can_publish', $access ) ) {
					$access[]							=	'cat_approved'; // Category Approved
				}

				if ( cbgjClass::hasAccess( 'usr_reg', $access ) ) {
					if ( ( $plugin->params->get( 'category_message', 1 ) && cbgjClass::hasAccess( array( 'mod_lvl1', 'cat_approved' ), $access, true ) ) || cbgjClass::hasAccess( 'usr_mod', $access ) ) {
						$access[]						=	'cat_message'; // Category Message
					}

					if ( cbgjClass::hasAccess( array( 'cat_nested', 'cat_approved' ), $access, true ) && ( ( $category->get( 'nested' ) && in_array( $category->nested_access, $myGids ) ) || cbgjClass::hasAccess( 'mod_lvl1', $access ) ) ) {
						$access[]						=	'cat_nested_create'; // Nested Category Create New
					}

					if ( cbgjClass::hasAccess( array( 'grp_create', 'cat_approved' ), $access, true ) && ( ( $category->get( 'create' ) && in_array( $category->create_access, $myGids ) ) || cbgjClass::hasAccess( 'mod_lvl1', $access ) ) ) {
						$access[]						=	'cat_grp_create'; // Category Group Create New
					}

					if ( cbgjClass::hasAccess( array( 'usr_notifications', 'cat_approved' ), $access, true ) && ( cbgjClass::hasAccess( 'mod_lvl1', $access ) && cbgjClass::hasAccess( array( 'cat_nested', 'grp_create' ), $access ) ) ) {
						$access[]						=	'cat_usr_notifications'; // Category User Notifications
					}
				}

				if ( $plugin->params->get( 'category_hide_empty', 0 ) && ( ! cbgjClass::hasAccess( 'grp', $access ) ) && cbgjClass::hasAccess( 'cat_access', $access ) && ( ! cbgjClass::hasAccess( array( 'cat_nested_create', 'cat_grp_create' ), $access, true ) ) ) {
					static $groupCount					=	array();

					$categoryId							=	$category->get( 'id' );

					if ( ! isset( $groupCount[$categoryId] ) ) {
						$groupCount[$categoryId]		=	$category->groupCount();
					}

					$count								=	$groupCount[$categoryId];

					if ( ! $count ) {
						if ( ( $catAccessKey = array_search( 'cat_access', $access ) ) !== false ) {
							unset( $access[$catAccessKey] );
						}
					}
				}
			}

			if ( cbgjClass::hasAccess( 'grp', $access ) ) {
				if ( cbgjClass::hasAccess( 'usr_reg', $access ) ) {
					if ( $groupInvitesDisplay && ( ! cbgjClass::hasAccess( 'grp_invite', $access ) ) && ( ( ( ! $group->get( 'parent' ) ) && cbgjClass::hasAccess( 'mod_lvl1', $access ) ) || ( $group->get( 'parent' ) && $group->getParentAccess( array( 'mod_lvl2', $user ) ) ) ) ) {
						$access[]						=	'grp_invite'; // Group User Can Invite
					}

					if ( ( ( ! $group->get( 'parent' ) ) && cbgjClass::hasAccess( 'mod_lvl1', $access ) ) || ( $group->get( 'parent' ) && $group->getParentAccess( array( 'mod_lvl2', $user ) ) ) ) {
						$access[]						=	'grp_can_publish'; // Group can be Published
					}
				}

				if ( $group->get( 'published' ) || cbgjClass::hasAccess( 'grp_can_publish', $access ) ) {
					$access[]							=	'grp_approved'; // Group Approved
				}

				if ( cbgjClass::hasAccess( 'usr_reg', $access ) ) {
					if ( ( $plugin->params->get( 'group_message', 1 ) && cbgjClass::hasAccess( 'grp_approved', $access ) ) || cbgjClass::hasAccess( 'usr_mod', $access ) ) {
						if ( ( $groupMessagePerm == 0 ) && cbgjClass::hasAccess( 'mod_lvl5', $access ) ) {
							$grpCanMsg					=	true; // Group User, Moderator, Administrator, or Owner
						} elseif ( in_array( $groupMessagePerm, array( 0, 1 ) ) && cbgjClass::hasAccess( 'mod_lvl4', $access ) ) {
							$grpCanMsg					=	true; // Group Moderator, Administrator, or Owner
						} elseif ( in_array( $groupMessagePerm, array( 0, 1, 2 ) ) && cbgjClass::hasAccess( 'mod_lvl3', $access ) ) {
							$grpCanMsg					=	true; // Group Administrator or Owner
						} elseif ( in_array( $groupMessagePerm, array( 0, 1, 2, 3 ) ) && cbgjClass::hasAccess( 'mod_lvl2', $access ) ) {
							$grpCanMsg					=	true; // Group Owner
						} elseif ( cbgjClass::hasAccess( 'mod_lvl1', $access ) ) {
							$grpCanMsg					=	true; // Moderator or Category Owner
						} else {
							$grpCanMsg					=	false; // Everyone else
						}

						if ( $grpCanMsg ) {
							$access[]					=	'grp_message'; // Group Message
						}
					}

					if ( cbgjClass::hasAccess( array( 'usr_notifications', 'grp_approved' ), $access, true ) && cbgjClass::hasAccess( array( 'mod_lvl4', 'grp_invite' ), $access ) ) {
						$access[]						=	'grp_usr_notifications'; // Group User Notifications
					}

					if ( cbgjClass::hasAccess( array( 'grp_nested', 'grp_approved' ), $access, true ) && ( $group->get( 'nested' ) || cbgjClass::hasAccess( 'mod_lvl2', $access ) ) ) {
						if ( ( $group->get( 'nested_access' ) == 0 ) && cbgjClass::hasAccess( 'mod_lvl5', $access ) ) {
							$grpCanNested				=	true; // Group User, Moderator, Administrator, or Owner
						} elseif ( in_array( $group->get( 'nested_access' ), array( 0, 1 ) ) && cbgjClass::hasAccess( 'mod_lvl4', $access ) ) {
							$grpCanNested				=	true; // Group Moderator, Administrator, or Owner
						} elseif ( in_array( $group->get( 'nested_access' ), array( 0, 1, 2 ) ) && cbgjClass::hasAccess( 'mod_lvl3', $access ) ) {
							$grpCanNested				=	true; // Group Administrator or Owner
						} elseif ( in_array( $group->get( 'nested_access' ), array( 0, 1, 2, 3 ) ) && cbgjClass::hasAccess( 'mod_lvl2', $access ) ) {
							$grpCanNested				=	true; // Group Owner
						} elseif ( cbgjClass::hasAccess( 'mod_lvl1', $access ) ) {
							$grpCanNested				=	true; // Moderator or Category Owner
						} else {
							$grpCanNested				=	false; // Everyone else
						}

						if ( $grpCanNested ) {
							$access[]					=	'grp_nested_create'; // Nested Group Create New
						}
					}
				}
			}

			cbgjClass::getIntegrations( 'gj_onAuthorization', array( &$access, $category, $group, $user, $owner, $row, $plugin ), null, 'raw' );

			$cache[$id]									=	$access;
		}

		return $cache[$id];
	}

	/**
	 * loads GJ plugin group
	 */
    static public function getPlugins() {
		global $_PLUGINS;

		static $loaded	=	0;

		if ( ! $loaded++ ) {
			$_PLUGINS->loadPluginGroup( 'user/plug_cbgroupjive/plugins' );
		}
	}

	/**
	 * includes frontend or backend language files based on location and language set
	 */
    static public function getLanguage() {
		global $_CB_framework;

		static $loaded		=	0;

		if ( ! $loaded++ ) {
			$plugin			=	cbgjClass::getPlugin();
			$langPath		=	$_CB_framework->getCfg( 'absolute_path' ) . '/components/com_comprofiler/plugin/language';
			$lang			=	$_CB_framework->getCfg( 'lang' );
			$path			=	$langPath . '/' . $plugin->element . $lang . '/';

			if ( ! file_exists( $path ) ) {
				$path		=	$langPath . '/' . $plugin->element . strtolower( $_CB_framework->getCfg( 'lang_tag' ) ) . '/';
				$lang		=	'language';
			}

			if ( $_CB_framework->getUi() == 2 ) {
				$filename	=	'admin_' . $lang . '.php';
			} else {
				$filename	=	$lang . '.php';
			}

			if ( file_exists( $path . $filename ) ) {
				$CBstrings	=	array();

				include_once( $path . $filename );

				CBTxt::addStrings( $CBstrings );
			}

			$plugins		=	'integrations_' . $lang . '.php';

			if ( file_exists( $path . $plugins ) ) {
				$CBstrings	=	array();

				include_once( $path . $plugins );

				CBTxt::addStrings( $CBstrings );
			}
		}
	}

	/**
	 * prepares a redirect
	 *
	 * @param string $url
	 * @param mixed $msg
	 * @param string $type
	 */
    static public function setRedirect( $url, $msg = null, $type = 'message' ) {
		static $REDIRECT	=	0;

		if ( ! $REDIRECT++ ) {
			if ( ! $url ) {
				$return		=	cbgjClass::getReturnURL();

				if ( $return ) {
					$url	=	$return;
				}

				if ( ! $url ) {
					$url	=	cbgjClass::setReturnURL( true );
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

	/**
	 * prepares a tooltip or title icon
	 *
	 * @param string $msg
	 * @param string $title
	 * @param string $class
	 * @param bool $forceTooltip
	 * @return string
	 */
    static public function getIcon( $msg, $title = null, $class = 'icon-question-sign', $forceTooltip = false ) {
		global $_CB_framework;

		$plugin						=	cbgjClass::getPlugin();
		$generalTooltips			=	$plugin->params->get( 'general_tooltips', 1 );

		if ( $msg ) {
			if ( $class && ( ( $generalTooltips == 1 ) || $forceTooltip ) ) {
				static $JS_LOADED	=	0;

				if ( ! $JS_LOADED++ ) {
					$js				=	"$( '.gjPopOver' ).live( 'click', function() {"
									.		"var visible = $( this ).children( '.popover.gjPopOverShow' );"
									.		"$( '#cbGj' ).find( '.gjPopOver .popover.gjPopOverShow' ).each( function() {"
									.			"$( this ).removeClass( 'gjPopOverShow' ).hide();"
									.		"});"
									.		"if ( ! visible.length ) {"
									.			"$( this ).children( '.popover' ).addClass( 'gjPopOverShow' ).show();"
									.		"}"
									.	"});";

					$_CB_framework->outputCbJQuery( $js );
				}

				$popover			=	' <span class="gjPopOver">'
									.		'<i class="' . htmlspecialchars( $class ) . '"></i>'
									.		'<div class="popover top">'
									.			'<div class="popover-inner">'
									.				'<div class="arrow"></div>'
									.				( $title ? '<h3 class="popover-title">' . $title . '</h3>' : null )
									.				'<div class="popover-content">'
									.					'<p>' . $msg . '</p>'
									.				'</div>'
									.			'</div>'
									.		'</div>'
									.	'</span> ';

				return $popover;
			} elseif ( $generalTooltips == 2 ) {
				return $msg;
			}
		} elseif ( $title && $class ) {
			$tooltip				=	' <span class="gjTooltip">'
									.		'<i class="' . htmlspecialchars( $class ) . '"></i>'
									.		'<span class="tooltip fade top in">'
									.			'<div class="tooltip-arrow"></div>'
									.			'<div class="tooltip-inner">' . $title . '</div>'
									.		'</span>'
									.	'</span> ';

			return $tooltip;
		} elseif ( $class ) {
			return ' <span class="gjIcon"><i class="' . htmlspecialchars( $class ) . '"></i></span> ';
		}

		return null;
	}

	/**
	 * @param string $items
	 * @param string|null $title
	 * @param string $icon
	 * @param string $class
	 * @return null|string
	 */
	static public function getDropdown( $items, $title = null, $icon = 'icon-chevron-down', $class = 'btn btn-mini' ) {
		global $_CB_framework;

		if ( $items ) {
			static $JS_LOADED	=	0;

			if ( ! $JS_LOADED++ ) {
				$js				=	"$( '.gjDropdown' ).live( 'click', function() {"
								.		"var visible = $( this ).children( '.gjDropdownItems.gjDropdownShow' );"
								.		"$( '#cbGj' ).find( '.gjDropdown .gjDropdownItems.gjDropdownShow' ).each( function() {"
								.			"$( this ).removeClass( 'gjDropdownShow' ).hide();"
								.		"});"
								.		"if ( ! visible.length ) {"
								.			"$( this ).children( '.gjDropdownItems' ).addClass( 'gjDropdownShow' ).show();"
								.		"}"
								.	"});";

				$_CB_framework->outputCbJQuery( $js );
			}

			if ( $title ) {
				$toggle			=	$title . ( $icon ? ' <i class="' . htmlspecialchars( $icon ) . '"></i>' : null );

				if ( $class != 'raw' ) {
					$toggle		=	'<button class="' . htmlspecialchars( $class ) . '" type="button">' . $toggle . '</button>';
				}
			} else {
				$toggle			=	'<i class="' . htmlspecialchars( $icon ) . '"></i>';
			}

			$dropdown			=	' <span class="gjDropdown' . ( $title && ( $class != 'raw' ) ? ' gjDropdownButton' : null ) . '">'
								.		'<span class="gjDropdownToggle">'
								.			$toggle
								.		'</span>'
								.		'<div class="gjDropdownItems">'
								.			$items
								.		'</div>'
								.	'</span> ';

			return $dropdown;
		}

		return null;
	}

    /**
     * prepare list of connections
     *
     * @param moscomprofilerUser $user
     * @return mixed
     */
    static public function getConnectionsList( $user ) {
		static $cache				=	array();

		if ( ! isset( $cache[$user->id] ) ) {
			$plugin					=	cbgjClass::getPlugin();
			$inviteBy				=	explode( '|*|', $plugin->params->get( 'group_invites_by', '1|*|2|*|3|*|4' ) );
			$listUsers				=	array();

			if ( $inviteBy ) {
                $cbConnection       =   new cbConnection( $user->id );
				$connections		=	$cbConnection->getActiveConnections( $user->id );

				if ( $connections ) foreach ( $connections as $connection ) {
					$cbConn			=&	CBuser::getInstance( (int) $connection->id );

					if ( ! $cbConn ) {
						$cbConn		=&	CBuser::getInstance( null );
					}

					if ( in_array( 2, $inviteBy ) ) {
						$value		=	$connection->username;
					} elseif ( in_array( 3, $inviteBy ) ) {
						$value		=	$connection->name;
					} elseif ( in_array( 4, $inviteBy ) ) {
						$value		=	$connection->email;
					} elseif ( in_array( 1, $inviteBy ) ) {
						$value		=	$connection->id;
					}

					$listUsers[]	=	moscomprofilerHTML::makeOption( $value, $cbConn->getField( 'formatname', null, 'html', 'none', 'profile', 0, true ) );
				}
			}

			$cache[$user->id]		=	$listUsers;
		}

		return $cache[$user->id];
	}

    /**
     * prepares internal CMS menu link for URL
     *
     * @param $title
     * @param $url
     * @param $plugin
     * @return bool
     */
    static public function setMenu( $title, $url, $plugin ) {
		global $_CB_database;

		$alias							=	trim( preg_replace( '/_+/', '-', preg_replace( '/\W+/', '', str_replace( ' ', '_', str_replace( '_', '', trim( strtolower( $title ) ) ) ) ) ) );

		if ( checkJversion() >= 2 ) {
			$extension					=	JTable::getInstance( 'Extension' );

			$extension->load( array( 'element' => 'com_comprofiler' ) );

			if ( ! $extension->extension_id ) {
				return false;
			}

			$menuType					=	JTable::getInstance( 'MenuType' );

			$menuType->load( array( 'menutype' => 'groupjive' ) );

			if ( ! $menuType->id ) {
				$menuType->menutype		=	'groupjive';
				$menuType->title		=	CBTxt::T( 'GroupJive' );
				$menuType->description	=	CBTxt::T( 'Internal menu links generated by GroupJive.' );

				$menuType->check();

				if ( ! $menuType->store() ) {
					return false;
				}
			}

			$table						=	JTable::getInstance( 'Menu' );

			while ( $table->load( array( 'alias' => $alias, 'menutype' => 'groupjive' ) ) ) {
				$matches				=	null;

				if ( preg_match( '#-(\d+)$#', $alias, $matches ) ) {
					$alias				=	preg_replace( '#-(\d+)$#', '-' . ( $matches[1] + 1 ) . '', $alias );
				} else {
					$alias				.=	'-2';
				}
			}

			$menu						=	JTable::getInstance( 'Menu' );
			$menu->menutype				=	'groupjive';
			$menu->title				=	$title;
			$menu->alias				=	$alias;
			$menu->link					=	$url;
			$menu->type					=	'component';
			$menu->component_id			=	(int) $extension->extension_id;
			$menu->language				=	'*';

			$menu->check();

			if ( ! $menu->store() ) {
				return false;
			} else {
				$menu->parent_id		=	1;
				$menu->level			=	1;

				if ( ! $menu->store() ) {
					return false;
				}
			}
		} else {
            $query						=	'SELECT ' . $_CB_database->NameQuote( 'id' )
										.	"\n FROM " . $_CB_database->NameQuote( '#__components' )
										.	"\n WHERE " . $_CB_database->NameQuote( 'link' ) . " = " . $_CB_database->Quote( 'option=com_comprofiler' );
            $_CB_database->setQuery( $query );
            $cb_id						=	$_CB_database->loadResult();

            if ( ! $cb_id ) {
                return false;
            }

            if ( checkJversion() == 1 ) {
                $query					=	'SELECT ' . $_CB_database->NameQuote( 'id' )
										.	"\n FROM " . $_CB_database->NameQuote( '#__menu_types' )
										.	"\n WHERE " . $_CB_database->NameQuote( 'menutype' ) . " = " . $_CB_database->Quote( 'groupjive' );
                $_CB_database->setQuery( $query );
                $menuType				=	$_CB_database->loadResult();

                if ( ! $menuType ) {
                    $query				=	'INSERT INTO ' . $_CB_database->NameQuote( '#__menu_types' )
										.	"\n (" . $_CB_database->NameQuote( 'menutype' )
										.	', ' . $_CB_database->NameQuote( 'title' )
										.	', ' . $_CB_database->NameQuote( 'description' )
										.	')'
										.	"\n VALUES ("
										.	$_CB_database->Quote( 'groupjive' )
										.	', ' . $_CB_database->Quote( 'GroupJive' )
										.	', ' . $_CB_database->Quote( 'Internal menu links generated by GroupJive.' )
										.	')';
                    $_CB_database->setQuery( $query );
                    if ( ! $_CB_database->query() ) {
                        return false;
                    }
                }

                $query					=	'INSERT INTO ' . $_CB_database->NameQuote( '#__menu' )
										.	"\n (" . $_CB_database->NameQuote( 'menutype' )
										.	', ' . $_CB_database->NameQuote( 'name' )
										.	', ' . $_CB_database->NameQuote( 'alias' )
										.	', ' . $_CB_database->NameQuote( 'link' )
										.	', ' . $_CB_database->NameQuote( 'type' )
										.	', ' . $_CB_database->NameQuote( 'componentid' )
										.	')'
										.	"\n VALUES ("
										.	$_CB_database->Quote( 'groupjive' )
										.	', ' . $_CB_database->Quote( $title )
										.	', ' . $_CB_database->Quote( $alias )
										.	', ' . $_CB_database->Quote( $url )
										.	', ' . $_CB_database->Quote( 'component' )
										.	', ' . (int) $cb_id
										.	')';
                $_CB_database->setQuery( $query );
                if ( ! $_CB_database->query() ) {
                    return false;
                }
            } else {
                $query					=	'INSERT INTO ' . $_CB_database->NameQuote( '#__menu' )
										.	"\n (" . $_CB_database->NameQuote( 'menutype' )
										.	', ' . $_CB_database->NameQuote( 'name' )
										.	', ' . $_CB_database->NameQuote( 'link' )
										.	', ' . $_CB_database->NameQuote( 'type' )
										.	', ' . $_CB_database->NameQuote( 'componentid' )
										.	')'
										.	"\n VALUES ("
										.	$_CB_database->Quote( 'groupjive' )
										.	', ' . $_CB_database->Quote( $title )
										.	', ' . $_CB_database->Quote( $url )
										.	', ' . $_CB_database->Quote( 'components' )
										.	', ' . (int) $cb_id
										.	')';
                $_CB_database->setQuery( $query );
                if ( ! $_CB_database->query() ) {
                    return false;
                }
            }
		}

		return true;
	}

	/**
	 * cleans text string for safe storage/display of HTML
	 *
	 * @param string $text
	 * @return string
	 */
    static public function getFilteredText( $text ) {
		global $ueConfig;

		cbimport( 'phpinputfilter.inputfilter' );

		$filter						=	new CBInputFilter( array(), array(), 1, 1, 1 );

		if ( isset( $ueConfig['html_filter_allowed_tags'] ) && $ueConfig['html_filter_allowed_tags'] ) {
			$filter->tagBlacklist	=	array_diff( $filter->tagBlacklist, explode( ' ', $ueConfig['html_filter_allowed_tags'] ) );
		}

		return trim( $filter->process( $text ) );
	}

    /**
     * parses an object, array, or string into cb params base for clean data retrieval
     *
     * @param $row
     * @param bool $html
     * @param mixed $base
     * @return cbParamsBase
     */
    static public function parseParams( $row, $html = false, $base = null ) {
		if ( ! $row ) {
			return new cbParamsBase( null );
		}

		static $params						=	array();

		$id									=	cbgjClass::getStaticID( array( $row, $html, $base ) );

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
								$p			=	cbgjClass::parseParams( $v, $html );
								$v			=	trim( $p->toIniString() );
							} else {
								$v			=	implode( '|*|', $v );
							}
						}

						if ( ( ! is_array( $v ) && ( ! is_object( $v ) ) ) ) {
							if ( $v !== null ) {
								$v			=	stripslashes( $v );

								if ( $html && ( $html !== 'raw' ) ) {
									$v		=	cbgjClass::getFilteredText( $v );
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

    /**
     * gets post value with defaults and permissions applied
     *
     * @param bool $access
     * @param mixed $param
     * @param mixed $value
     * @param mixed $default
     * @param string $items
     * @return mixed
     */
    static public function getCleanParam( $access, $param, $value = null, $default = null, $items = 'POST' ) {
		if ( $items == 'POST' ) {
			$items	=	$_POST;
		} elseif ( $items == 'GET' ) {
			$items	=	$_GET;
		} elseif ( $items == 'REQUEST' ) {
			$items	=	$_REQUEST;
		}

		$data		=	cbgjClass::parseParams( $items );

		return ( (bool) $access ? $data->get( $param, ( $value != null ? $value : $default ) ) : ( $value != null ? $value : $default ) );
	}

	/**
	 * gets post value with defaults and permissions applied with HTML allowed
	 *
     * @param bool $access
     * @param mixed $param
     * @param mixed $value
     * @param mixed $default
     * @param string $items
     * @return mixed
     */
    static public function getHTMLCleanParam( $access, $param, $value = null, $default = null, $items = 'POST' ) {
		if ( $items == 'POST' ) {
			$items	=	$_POST;
		} elseif ( $items == 'GET' ) {
			$items	=	$_GET;
		} elseif ( $items == 'REQUEST' ) {
			$items	=	$_REQUEST;
		}

		$data		=	cbgjClass::parseParams( $items, true );

		return ( (bool) $access ? $data->get( $param, ( $value != null ? $value : $default ) ) : ( $value != null ? $value : $default ) );
	}

	/**
	 * gets post value with defaults and permissions applied with no cleaning
	 *
     * @param bool $access
     * @param mixed $param
     * @param mixed $value
     * @param mixed $default
     * @param string $items
     * @return mixed
     */
    static public function getRAWParam( $access, $param, $value = null, $default = null, $items = 'POST' ) {
		if ( $items == 'POST' ) {
			$items	=	$_POST;
		} elseif ( $items == 'GET' ) {
			$items	=	$_GET;
		} elseif ( $items == 'REQUEST' ) {
			$items	=	$_REQUEST;
		}

		$data		=	cbgjClass::parseParams( $items, 'raw' );

		return ( (bool) $access ? $data->get( $param, ( $value != null ? $value : $default ) ) : ( $value != null ? $value : $default ) );
	}

	/**
	 * formats and outputs a message; default as error
	 *
	 * @param mixed $msg
     * @param string $type
	 */
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

	/**
	 * recursively delete a folder and its contents
	 *
	 * @param string $source
	 * @return boolean
	 */
	static public function deleteDirectory( $source ) {
		if ( is_dir( $source ) ) {
			$source			=	str_replace( '\\', '/', realpath( $source ) );

			if ( is_dir( $source ) ) {
				$files		=	new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $source ), RecursiveIteratorIterator::CHILD_FIRST );

				if ( $files ) foreach ( $files as $file ) {
					$file	=	str_replace( '\\', '/', realpath( $file ) );

					if ( is_dir( $file ) ) {
						@rmdir( $file );
					} elseif ( is_file( $file ) ) {
						@unlink( $file );
					}
				}

				@rmdir( $source );
			}
		}
	}

	/**
	 * recursively copy a folder and its contents
	 *
	 * @param string $source
	 * @param string $destination
	 */
	static public function copyDirectory( $source, $destination ) {
		if ( is_dir( $source ) ) {
			$folderMode				=	cbgjClass::getFolderPerms();
			$fileMode				=	cbgjClass::getFilePerms();
			$source					=	str_replace( '\\', '/', realpath( $source ) );
			$oldmask				=	@umask( 0 );

			if ( ! file_exists( $destination ) ) {
				@mkdir( $destination, $folderMode );
			}

			$destination			=	str_replace( '\\', '/', realpath( $destination ) );

			if ( is_dir( $destination ) ) {
				$files				=	new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $source ), RecursiveIteratorIterator::SELF_FIRST );

				if ( $files ) {
					foreach ( $files as $file ) {
						$file		=	str_replace( '\\', '/', realpath( $file ) );

						if ( is_dir( $file ) ) {
							@mkdir( str_replace( $source . '/', $destination . '/', $file . '/' ), $folderMode );
						} elseif ( is_file( $file ) ) {
							$copy	=	str_replace( $source . '/', $destination . '/', $file );

							@copy( $file, $copy );
							@chmod( $copy, $fileMode );
						}
					}
				}
			}

			@umask( $oldmask );
		}
	}

	/**
	 * get folder permissions used for folder creation; this won't override umask
	 *
	 * @return int
	 */
    static public function getFolderPerms() {
		$plugin			=	cbgjClass::getPlugin();
		$mode			=	(int) $plugin->params->get( 'general_dirperms', '755' );

		if ( ( ! $mode ) || ( strlen( $mode ) < 3 ) ) {
			$mode		=	0755;
		} else {
			if ( strlen( $mode ) < 4 ) {
				$mode	=	"0$mode";
			}
		}

		$mode			=	octdec( (string) $mode );

		return $mode;
	}

	/**
	 * get file permissions used for file creation; this won't override umask
	 *
	 * @return int
	 */
    static public function getFilePerms() {
		$plugin			=	cbgjClass::getPlugin();
		$mode			=	(int) $plugin->params->get( 'general_fileperms', '644' );

		if ( ( ! $mode ) || ( strlen( $mode ) < 3 ) ) {
			$mode		=	0644;
		} else {
			if ( strlen( $mode ) < 4 ) {
				$mode	=	"0$mode";
			}
		}

		$mode			=	octdec( (string) $mode );

		return $mode;
	}

	/**
	 * prepares an override string
	 *
	 * @param string $string
	 * @param boolean $plural
	 * @param boolean $htmlspecialchars
	 * @param boolean $translate
	 * @return string
	 */
    static public function getOverride( $string, $plural = false, $htmlspecialchars = true, $translate = true ) {
		static $overrides			=	array();

		$id							=	cbgjClass::getStaticID( array( $string, $plural ) );

		if ( ! isset( $overrides[$id] ) ) {
			if ( is_integer( $plural ) ) {
				$count				=	$plural;

				if ( ( $count > 1 ) || ( $count == 0 ) ) {
					$plural			=	true;
				} else {
					$plural			=	false;
				}
			} else {
				$count				=	null;
			}

			$plugin					=	cbgjClass::getPlugin();
			$override				=	null;

			switch( strtolower( $string ) ) {
				case 'overview':
					$override		=	$plugin->params->get( 'override_overview_s', 'Overview' );
					break;
				case 'panel':
					$override		=	$plugin->params->get( 'override_panel_s', 'Panel' );
					break;
				case 'cat':
				case 'category':
					if ( $plural ) {
						$override	=	$plugin->params->get( 'override_category_p', 'Categories' );
					} else {
						$override	=	$plugin->params->get( 'override_category_s', 'Category' );
					}
					break;
				case 'grp':
				case 'group':
					if ( $plural ) {
						$override	=	$plugin->params->get( 'override_group_p', 'Groups' );
					} else {
						$override	=	$plugin->params->get( 'override_group_s', 'Group' );
					}
					break;
				case 'user':
					if ( $plural ) {
						$override	=	$plugin->params->get( 'override_user_p', 'Users' );
					} else {
						$override	=	$plugin->params->get( 'override_user_s', 'User' );
					}
					break;
				case 'owner':
					$override		=	$plugin->params->get( 'override_owner_s', 'Owner' );
					break;
				case 'mod':
				case 'moderator':
					if ( $plural ) {
						$override	=	$plugin->params->get( 'override_mod_p', 'Moderators' );
					} else {
						$override	=	$plugin->params->get( 'override_mod_s', 'Moderator' );
					}
					break;
				case 'admin':
					if ( $plural ) {
						$override	=	$plugin->params->get( 'override_admin_p', 'Admins' );
					} else {
						$override	=	$plugin->params->get( 'override_admin_s', 'Admin' );
					}
					break;
			}

			$overrides[$id]			=	( $count !== null ? $count . ' ' : null ) . $override;
		}

		$return						=	$overrides[$id];

		if ( $return ) {
			if ( $translate ) {
				$return				=	CBTxt::T( $return );
			}

			if ( $htmlspecialchars ) {
				$return				=	htmlspecialchars( $return );
			}
		}

		return $return;
	}

	/**
	 * sets or checks a cache reset
	 *
	 * @param boolean $reset
	 * @return boolean
	 */
    static public function resetCache( $reset = false ) {
		static $cache	=	false;

		if ( $reset ) {
			$cache		=	true;
		} else {
			return $cache;
		}
	}

	/**
	 * saves users notifications
	 *
	 * @param int $catid
	 * @param int $grpid
	 * @param moscomprofilerUser $user
	 * @param object $plugin
	 * @param boolean $silent
	 * @return boolean
	 */
	static public function saveNotifications( $catid, $grpid, $user, $plugin, $silent = true ) {
		$category						=	cbgjData::getCategories( null, array( 'id', '=', (int) $catid ), null, null, false );
		$group							=	cbgjData::getGroups( null, array( 'id', '=', (int) $grpid ), null, null, false );

		if ( ( ! $category->get( 'id' ) ) && $group->get( 'id' ) ) {
			$category					=	$group->getCategory();
		}

		$authorized						=	cbgjClass::getAuthorization( $category, $group, $user );

		if ( cbgjClass::hasAccess( 'usr_notifications', $authorized ) ) {
			$categoryApprove			=	$plugin->params->get( 'category_approve', 0 );
			$groupApprove				=	$plugin->params->get( 'group_approve', 0 );

			if ( cbgjClass::hasAccess( 'gen_usr_notifications', $authorized ) ) {
				$generalNotifications	=	cbgjData::getNotifications( null, array( array( 'type', '=', 'general' ), array( 'user_id', '=', (int) $user->id ) ), null, null, false );

				$generalNotifications->set( 'user_id', (int) $user->id );
				$generalNotifications->set( 'type', 'general' );
				$generalNotifications->set( 'item', 0 );

				if ( $generalNotifications->getError() || ( ! $generalNotifications->store() ) ) {
					if ( ! $silent ) {
						cbgjClass::getPluginURL( array( 'notifications', 'show', (int) $category->get( 'id' ), (int) $group->get( 'id' ) ), CBTxt::P( 'General notifications failed to save! Error: [error]', array( '[error]' => $generalNotifications->getError() ) ), false, true, null, false, false, true );
					}

					return false;
				}

				$generalParams			=	$generalNotifications->getParams();
				$generalCleanParams		=	new cbParamsBase( null );

				$generalCleanParams->set( 'general_categorynew', ( (int) ( cbgjClass::hasAccess( 'usr_mod', $authorized ) ? cbgjClass::getCleanParam( true, 'general_categorynew', $generalParams->get( 'general_categorynew', $plugin->params->get( 'notifications_general_categorynew', 0 ) ) ) : 0 ) ? 1 : 0 ) );
				$generalCleanParams->set( 'general_categoryapprove', ( (int) ( cbgjClass::hasAccess( 'usr_mod', $authorized ) && $categoryApprove ? cbgjClass::getCleanParam( true, 'general_categoryapprove', $generalParams->get( 'general_categoryapprove', $plugin->params->get( 'notifications_general_categoryapprove', 0 ) ) ) : 0 ) ? 1 : 0 ) );
				$generalCleanParams->set( 'general_categoryupdate', ( (int) ( cbgjClass::hasAccess( 'usr_mod', $authorized ) ? cbgjClass::getCleanParam( true, 'general_categoryupdate', $generalParams->get( 'general_categoryupdate', $plugin->params->get( 'notifications_general_categoryupdate', 0 ) ) ) : 0 ) ? 1 : 0 ) );
				$generalCleanParams->set( 'general_categorydelete', ( (int) ( cbgjClass::hasAccess( 'usr_mod', $authorized ) ? cbgjClass::getCleanParam( true, 'general_categorydelete', $generalParams->get( 'general_categorydelete', $plugin->params->get( 'notifications_general_categorydelete', 0 ) ) ) : 0 ) ? 1 : 0 ) );

				if ( $generalNotifications->getError() || ( ! $generalNotifications->storeParams( $generalCleanParams ) ) ) {
					if ( ! $silent ) {
						cbgjClass::getPluginURL( array( 'notifications', 'show', (int) $category->get( 'id' ), (int) $group->get( 'id' ) ), CBTxt::P( 'General notifications failed to save! Error: [error]', array( '[error]' => $generalNotifications->getError() ) ), false, true, null, false, false, true );
					}

					return false;
				}
			}

			if ( cbgjClass::hasAccess( 'cat_usr_notifications', $authorized ) ) {
				$categoryNotifications	=	cbgjData::getNotifications( null, array( array( 'type', '=', 'category' ), array( 'item', '=', (int) $catid ), array( 'user_id', '=', (int) $user->id ) ), null, null, false );

				$categoryNotifications->set( 'user_id', (int) $user->id );
				$categoryNotifications->set( 'type', 'category' );
				$categoryNotifications->set( 'item', (int) $category->get( 'id' ) );

				if ( $categoryNotifications->getError() || ( ! $categoryNotifications->store() ) ) {
					if ( ! $silent ) {
						cbgjClass::getPluginURL( array( 'notifications', 'show', (int) $category->get( 'id' ), (int) $group->get( 'id' ) ), CBTxt::P( '[category] notifications failed to save! Error: [error]', array( '[category]' => cbgjClass::getOverride( 'category' ), '[error]' => $categoryNotifications->getError() ) ), false, true, null, false, false, true );
					}

					return false;
				}

				$categoryParams			=	$categoryNotifications->getParams();
				$categoryCleanParams	=	new cbParamsBase( null );

				$categoryCleanParams->set( 'category_nestednew', ( (int) ( cbgjClass::hasAccess( array( 'mod_lvl1', 'cat_nested' ), $authorized, true ) ? cbgjClass::getCleanParam( true, 'category_nestednew', $categoryParams->get( 'category_nestednew', $plugin->params->get( 'notifications_category_nestednew', 0 ) ) ) : 0 ) ? 1 : 0 ) );
				$categoryCleanParams->set( 'category_nestedapprove', ( (int) ( cbgjClass::hasAccess( array( 'mod_lvl1', 'cat_nested' ), $authorized, true ) && $categoryApprove ? cbgjClass::getCleanParam( true, 'category_nestedapprove', $categoryParams->get( 'category_nestedapprove', $plugin->params->get( 'notifications_category_nestedapprove', 0 ) ) ) : 0 ) ? 1 : 0 ) );
				$categoryCleanParams->set( 'category_nestedupdate', ( (int) ( cbgjClass::hasAccess( array( 'mod_lvl1', 'cat_nested' ), $authorized, true ) ? cbgjClass::getCleanParam( true, 'category_nestedupdate', $categoryParams->get( 'category_nestedupdate', $plugin->params->get( 'notifications_category_nestedupdate', 0 ) ) ) : 0 ) ? 1 : 0 ) );
				$categoryCleanParams->set( 'category_nesteddelete', ( (int) ( cbgjClass::hasAccess( array( 'mod_lvl1', 'cat_nested' ), $authorized, true ) ? cbgjClass::getCleanParam( true, 'category_nesteddelete', $categoryParams->get( 'category_nesteddelete', $plugin->params->get( 'notifications_category_nesteddelete', 0 ) ) ) : 0 ) ? 1 : 0 ) );
				$categoryCleanParams->set( 'category_groupnew', ( (int) ( cbgjClass::hasAccess( array( 'mod_lvl1', 'grp_create' ), $authorized, true ) ? cbgjClass::getCleanParam( true, 'category_groupnew', $categoryParams->get( 'category_groupnew', $plugin->params->get( 'notifications_category_groupnew', 0 ) ) ) : 0 ) ? 1 : 0 ) );
				$categoryCleanParams->set( 'category_groupapprove', ( (int) ( cbgjClass::hasAccess( array( 'mod_lvl1', 'grp_create' ), $authorized, true ) && $groupApprove ? cbgjClass::getCleanParam( true, 'category_groupapprove', $categoryParams->get( 'category_groupapprove', $plugin->params->get( 'notifications_category_groupapprove', 0 ) ) ) : 0 ) ? 1 : 0 ) );
				$categoryCleanParams->set( 'category_groupupdate', ( (int) ( cbgjClass::hasAccess( array( 'mod_lvl1', 'grp_create' ), $authorized, true ) ? cbgjClass::getCleanParam( true, 'category_groupupdate', $categoryParams->get( 'category_groupupdate', $plugin->params->get( 'notifications_category_groupupdate', 0 ) ) ) : 0 ) ? 1 : 0 ) );
				$categoryCleanParams->set( 'category_groupdelete', ( (int) ( cbgjClass::hasAccess( array( 'mod_lvl1', 'grp_create' ), $authorized, true ) ? cbgjClass::getCleanParam( true, 'category_groupdelete', $categoryParams->get( 'category_groupdelete', $plugin->params->get( 'notifications_category_groupdelete', 0 ) ) ) : 0 ) ? 1 : 0 ) );

				if ( $categoryNotifications->getError() || ( ! $categoryNotifications->storeParams( $categoryCleanParams ) ) ) {
					if ( ! $silent ) {
						cbgjClass::getPluginURL( array( 'notifications', 'show', (int) $category->get( 'id' ), (int) $group->get( 'id' ) ), CBTxt::P( '[category] notifications failed to save! Error: [error]', array( '[category]' => cbgjClass::getOverride( 'category' ), '[error]' => $categoryNotifications->getError() ) ), false, true, null, false, false, true );
					}

					return false;
				}
			}

			if ( cbgjClass::hasAccess( 'grp_usr_notifications', $authorized ) ) {
				$groupNotifications		=	cbgjData::getNotifications( null, array( array( 'type', '=', 'group' ), array( 'item', '=', (int) $grpid ), array( 'user_id', '=', (int) $user->id ) ), null, null, false );

				$groupNotifications->set( 'user_id', (int) $user->id );
				$groupNotifications->set( 'type', 'group' );
				$groupNotifications->set( 'item', (int) $group->get( 'id' ) );

				if ( $groupNotifications->getError() || ( ! $groupNotifications->store() ) ) {
					if ( ! $silent ) {
						cbgjClass::getPluginURL( array( 'notifications', 'show', (int) $category->get( 'id' ), (int) $group->get( 'id' ) ), CBTxt::P( '[group] notifications failed to save! Error: [error]', array( '[group]' => cbgjClass::getOverride( 'category' ), '[error]' => $groupNotifications->getError() ) ), false, true, null, false, false, true );
					}

					return false;
				}

				$groupParams			=	$groupNotifications->getParams();
				$groupCleanParams		=	new cbParamsBase( null );

				$groupCleanParams->set( 'group_nestednew', ( (int) ( cbgjClass::hasAccess( array( 'mod_lvl2', 'grp_nested' ), $authorized, true ) ? cbgjClass::getCleanParam( true, 'group_nestednew', $groupParams->get( 'group_nestednew', $plugin->params->get( 'notifications_group_nestednew', 0 ) ) ) : 0 ) ? 1 : 0 ) );
				$groupCleanParams->set( 'group_nestedapprove', ( (int) ( cbgjClass::hasAccess( array( 'mod_lvl2', 'grp_nested' ), $authorized, true ) && $groupApprove ? cbgjClass::getCleanParam( true, 'group_nestedapprove', $groupParams->get( 'group_nestedapprove', $plugin->params->get( 'notifications_group_nestedapprove', 0 ) ) ) : 0 ) ? 1 : 0 ) );
				$groupCleanParams->set( 'group_nestedupdate', ( (int) ( cbgjClass::hasAccess( array( 'mod_lvl2', 'grp_nested' ), $authorized, true ) ? cbgjClass::getCleanParam( true, 'group_nestedupdate', $groupParams->get( 'group_nestedupdate', $plugin->params->get( 'notifications_group_nestedupdate', 0 ) ) ) : 0 ) ? 1 : 0 ) );
				$groupCleanParams->set( 'group_nesteddelete', ( (int) ( cbgjClass::hasAccess( array( 'mod_lvl2', 'grp_nested' ), $authorized, true ) ? cbgjClass::getCleanParam( true, 'group_nesteddelete', $groupParams->get( 'group_nesteddelete', $plugin->params->get( 'notifications_group_nesteddelete', 0 ) ) ) : 0 ) ? 1 : 0 ) );
				$groupCleanParams->set( 'group_userjoin', ( (int) ( cbgjClass::hasAccess( 'mod_lvl4', $authorized ) ? cbgjClass::getCleanParam( true, 'group_userjoin', $groupParams->get( 'group_userjoin', $plugin->params->get( 'notifications_group_userjoin', 0 ) ) ) : 0 ) ? 1 : 0 ) );
				$groupCleanParams->set( 'group_userleave', ( (int) ( cbgjClass::hasAccess( 'mod_lvl4', $authorized ) ? cbgjClass::getCleanParam( true, 'group_userleave', $groupParams->get( 'group_userleave', $plugin->params->get( 'notifications_group_userleave', 0 ) ) ) : 0 ) ? 1 : 0 ) );
				$groupCleanParams->set( 'group_userinvite', ( (int) ( cbgjClass::hasAccess( 'mod_lvl2', $authorized ) ? cbgjClass::getCleanParam( true, 'group_userinvite', $groupParams->get( 'group_userinvite', $plugin->params->get( 'notifications_group_userinvite', 0 ) ) ) : 0 ) ? 1 : 0 ) );
				$groupCleanParams->set( 'group_userapprove', ( (int) ( cbgjClass::hasAccess( 'mod_lvl4', $authorized ) ? cbgjClass::getCleanParam( true, 'group_userapprove', $groupParams->get( 'group_userapprove', $plugin->params->get( 'notifications_group_userapprove', 0 ) ) ) : 0 ) ? 1 : 0 ) );
				$groupCleanParams->set( 'group_inviteaccept', ( (int) ( cbgjClass::hasAccess( 'grp_invite', $authorized ) ? cbgjClass::getCleanParam( true, 'group_inviteaccept', $groupParams->get( 'group_inviteaccept', $plugin->params->get( 'notifications_group_inviteaccept', 0 ) ) ) : 0 ) ? 1 : 0 ) );

				if ( $groupNotifications->getError() || ( ! $groupNotifications->storeParams( $groupCleanParams ) ) ) {
					if ( ! $silent ) {
						cbgjClass::getPluginURL( array( 'notifications', 'show', (int) $category->get( 'id' ), (int) $group->get( 'id' ) ), CBTxt::P( '[group] notifications failed to save! Error: [error]', array( '[group]' => cbgjClass::getOverride( 'category' ), '[error]' => $groupNotifications->getError() ) ), false, true, null, false, false, true );
						return false;
					}

					return false;
				}
			}

			if ( ! $silent ) {
				cbgjClass::getPluginURL( array( 'notifications', 'show', (int) $category->get( 'id' ), (int) $group->get( 'id' ) ), CBTxt::T( 'Notifications saved successfully!' ), false, true, null, false, false, true );
			}

			return true;
		} else {
			if ( ! $silent ) {
				cbgjClass::getPluginURL( array( 'overview' ), CBTxt::T( 'Not authorized.' ), false, true, 'error' );
			}

			return false;
		}
	}

	/**
	 * checks array of accesses againts authorized array
	 *
	 * @param mixed $access
	 * @param array $authorized
	 * @param boolean $and
	 * @return boolean
	 */
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

	/**
	 * creates folders progessively and corrects their permissions
	 *
	 * @param string $root
	 * @param string $category
	 * @param string $group
	 */
    static public function createFolderPath( $root = null, $category = null, $group = null ) {
		$plugin			=	cbgjClass::getPlugin();
		$folderMode		=	cbgjClass::getFolderPerms();
		$fileMode		=	cbgjClass::getFilePerms();

		if ( $root && ( ! is_dir( $root ) ) ) {
			$oldmask	=	@umask( 0 );

			if ( @mkdir( $root, $folderMode, true ) ) {
				@umask( $oldmask );

				@chmod( $root, $folderMode );

				if ( ! file_exists( $root . '/index.html' ) ) {
					@copy( $plugin->absPath . '/images/index.html', $root . '/index.html' );
					@chmod( $root . '/index.html', $fileMode );
				}
			} else {
				@umask( $oldmask );
			}
		}

		if ( $category && ( ! is_dir( $category ) ) ) {
			$oldmask	=	@umask( 0 );

			if ( @mkdir( $category, $folderMode, true ) ) {
				@umask( $oldmask );

				@chmod( $category, $folderMode );

				if ( ! file_exists( $category . '/index.html' ) ) {
					@copy( $plugin->absPath . '/images/index.html', $category . '/index.html' );
					@chmod( $category . '/index.html', $fileMode );
				}
			} else {
				@umask( $oldmask );
			}
		}

		if ( $group && ( ! is_dir( $group ) ) ) {
			$oldmask	=	@umask( 0 );

			if ( @mkdir( $group, $folderMode, true ) ) {
				@umask( $oldmask );

				@chmod( $group, $folderMode );

				if ( ! file_exists( $group . '/index.html' ) ) {
					@copy( $plugin->absPath . '/images/index.html', $group . '/index.html' );
					@chmod( $group . '/index.html', $fileMode );
				}
			} else {
				@umask( $oldmask );
			}
		}
	}

	/**
	 * returns a UTC formated now
	 *
	 * @return int
	 */
    static public function getUTCNow() {
		static $cache	=	null;

		if ( ! isset( $cache ) ) {
			$timezone	=	date_default_timezone_get();

			date_default_timezone_set( 'UTC' );

			$cache		=	time();

			date_default_timezone_set( $timezone );
		}

		return $cache;
	}

	/**
	 * returns a UTC formated timestamp
	 *
	 * @param string $time
	 * @param string $now
	 * @return int
	 */
    static public function getUTCTimestamp( $time = 'now', $now = null ) {
		static $cache	=	array();

		if ( ! $time ) {
			$time		=	'now';
		}

		$id				=	cbgjClass::getStaticID( array( $time, $now ) );

		if ( ! isset( $cache[$id] ) ) {
			$timezone	=	date_default_timezone_get();

			date_default_timezone_set( 'UTC' );

			if ( $now && ( ! ( ( (int) $now === $now )&& ( $now <= PHP_INT_MAX )&& ( $now >= ~PHP_INT_MAX ) ) ) ) {
				$now	=	strtotime( $now );
			}

			$cache[$id]	=	( $now ? strtotime( $time, $now ) : strtotime( $time ) );

			date_default_timezone_set( $timezone );
		}

		return $cache[$id];
	}

	/**
	 * returns a UTC formated date
	 *
	 * @param string $format
     * @param string $timestamp
	 * @return string
	 */
    static public function getUTCDate( $format = 'Y-m-d H:i:s', $timestamp = null ) {
		static $cache		=	array();

		if ( ! $format ) {
			$format			=	'Y-m-d H:i:s';
		}

		$id					=	cbgjClass::getStaticID( array( $format, $timestamp ) );

		if ( ! isset( $cache[$id] ) ) {
			$timezone		=	date_default_timezone_get();

			date_default_timezone_set( 'UTC' );

			if ( $timestamp && ( ! ( ( (int) $timestamp === $timestamp )&& ( $timestamp <= PHP_INT_MAX )&& ( $timestamp >= ~PHP_INT_MAX ) ) ) ) {
				$timestamp	=	strtotime( $timestamp );
			}

			$cache[$id]		=	( $timestamp ? date( $format, $timestamp ) : date( $format ) );

			date_default_timezone_set( $timezone );
		}

		return $cache[$id];
	}

	/**
	 * passes text through content plugins
	 *
	 * @param string $text
	 * @return string
	 */
	static public function prepareContentPlugins( $text ) {
		if ( is_callable( array( 'JHtml', '_' ) ) ) {
			$previousDocType	=	JFactory::getDocument()->getType();

			JFactory::getDocument()->setType( 'html' );

			jimport( 'joomla.application.module.helper' );

			try {
				$text			=	 JHtml::_( 'content.prepare', $text );
			} catch ( Exception $e ) {}

			JFactory::getDocument()->setType( $previousDocType );
		}

		return $text;
	}

	/**
	 * loops categories and groups for display in dropdown lists
	 *
	 * @param null|int $categoryId
	 * @param int $parent
	 * @param array $exclude
	 * @param array $options
	 * @param int $categoryRoot
	 * @return array
	 */
	static public function getCategoryGroupOptions( $categoryId = null, $parent = 0, $exclude = array(), &$options = array(), &$categoryRoot = null ) {
		static $categoryCache			=	null;

		if ( ! isset( $categoryCache ) ) {
			$categoryCache				=	cbgjData::getCategories( array( 'cat_access', 'mod_lvl1' ) );
		}

		static $groupCache				=	null;

		if ( ! isset( $groupCache ) ) {
			$groupCache					=	cbgjData::getGroups( array( 'grp_access', 'mod_lvl2' ) );
		}

		if ( $categoryId ) {
			$categoryId					=	(int) $categoryId;

			if ( isset( $categoryCache[$categoryId] ) ) {
				$categories				=	array( $categoryCache[$categoryId] );
			} else {
				$categories				=	null;
			}

			if ( ( ! $options ) && ( ! $categoryRoot ) ) {
				$categoryRoot			=	$categoryId;
			}
		} else {
			$categories					=	$categoryCache;
		}

		if ( $categories ) foreach ( $categories as $category ) {
			$groups						=	array();

			if ( $groupCache ) foreach ( $groupCache as $grp ) {
				if ( ( $category->get( 'id' ) == $grp->get( 'category' ) ) && ( $parent == $grp->get( 'parent' ) ) && ( ( ! $exclude ) || ( $exclude && ( ! in_array( $grp->get( 'id' ), $exclude ) ) ) ) ) {
					$groups[]			=	$grp;
				}
			}

			$categoryIndent				=	null;

			if ( ! $categoryId ) {
				$depth					=	$category->getDepth();

				if ( $depth ) {
					for ( $i = 0, $n = $depth; $i < $n; $i++ ) {
						$categoryIndent	.=	'- - ';
					}
				}

				$options[]				=	moscomprofilerHTML::makeOption( 'c' . $category->get( 'id' ), $categoryIndent . $category->getName() );
			}

			if ( $groups ) foreach ( $groups as $group ) {
				if ( ! $categoryRoot ) {
					$indent				=	'- - ';
				} else {
					$indent				=	null;
				}

				if ( $parent ) {
					$depth				=	$group->getDepth( $parent );

					if ( $depth ) {
						for ( $i = 0, $n = $depth; $i < $n; $i++ ) {
							$indent		.=	'- - ';
						}
					}
				}

				$options[]				=	moscomprofilerHTML::makeOption( 'g' . $group->get( 'id' ), $categoryIndent . $indent . $group->getName() );

				cbgjClass::getGroupOptions( $category->get( 'id' ), $group->get( 'id' ), $exclude, $options, $categoryRoot );
			}
		}

		return $options;
	}

	/**
	 * loops categories for display in dropdown lists
	 *
	 * @param string $access
	 * @param int $parent
	 * @param array $exclude
	 * @param array $options
	 * @param array $children
	 * @return array
	 */
	static public function getCategoryOptions( $access = 'cat_grp_create', $parent = 0, $exclude = array(), &$options = array(), &$children = array() ) {
		static $categoryCache		=	null;

		if ( ! isset( $categoryCache ) ) {
			$categoryCache			=	cbgjData::getCategories( array( 'cat_access', 'mod_lvl1' ) );
		}

		$categories					=	array();

		if ( $categoryCache ) foreach ( $categoryCache as $cat ) {
			if ( $parent == $cat->get( 'parent' ) && ( ( ! $exclude ) || ( $exclude && ( ! in_array( $cat->get( 'id' ), $exclude ) ) ) ) ) {
				$categories[]		=	$cat;
			}
		}

		if ( $categories ) foreach ( $categories as $category ) {
			if ( $access ) {
				$authorized			=	cbgjClass::getAuthorization( $category );
			} else {
				$authorized			=	array();
			}

			$indent					=	null;

			if ( $parent ) {
				$depth				=	$category->getDepth( $parent );

				if ( $depth ) {
					for ( $i = 0, $n = $depth; $i < $n; $i++ ) {
						$indent		.=	'- - ';
					}
				}

				$children[]			=	$category->get( 'id' );
			}

			$optGroup				=	false;

			if ( ( ! $authorized ) || cbgjClass::hasAccess( $access, $authorized ) ) {
				$options[]			=	moscomprofilerHTML::makeOption( $category->get( 'id' ), $indent . $category->getName() );
			} else {
				$optGroup			=	true;

				$options[]			=	moscomprofilerHTML::makeOptGroup( $indent . $category->getName() );
				$options[]			=	moscomprofilerHTML::makeOptGroup( null );

				$children			=	array();
			}

			cbgjClass::getCategoryOptions( $access, $category->get( 'id' ), $exclude, $options, $children );

			if ( $optGroup && ( ! $children ) ) {
				$options			=	array_slice( $options, 0, -2 );
			}
		}

		return $options;
	}


	/**
	 * loops groups for display in dropdown lists
	 *
	 * @param string $access
	 * @param null|int $categoryId
	 * @param int $parent
	 * @param array $exclude
	 * @param array $options
	 * @param array $children
	 * @param array $categoryGroups
	 * @param int $categoryRoot
	 * @return array
	 */
	static public function getGroupOptions( $access = 'grp_nested_create', $categoryId = null, $parent = 0, $exclude = array(), &$options = array(), &$children = array(), &$categoryGroups = array(), &$categoryRoot = null ) {
		static $categoryCache			=	null;

		if ( ! isset( $categoryCache ) ) {
			$categoryCache				=	cbgjData::getCategories( array( 'cat_access', 'mod_lvl1' ) );
		}

		static $groupCache				=	null;

		if ( ! isset( $groupCache ) ) {
			$groupCache					=	cbgjData::getGroups( array( 'grp_access', 'mod_lvl2' ) );
		}

		if ( $categoryId ) {
			$categoryId					=	(int) $categoryId;

			if ( isset( $categoryCache[$categoryId] ) ) {
				$categories				=	array( $categoryCache[$categoryId] );
			} else {
				$categories				=	null;
			}

			if ( ( ! $options ) && ( ! $categoryRoot ) ) {
				$categoryRoot			=	$categoryId;
			}
		} else {
			$categories					=	$categoryCache;
		}

		if ( $categories ) foreach ( $categories as $category ) {
			$groups						=	array();

			if ( $groupCache ) foreach ( $groupCache as $grp ) {
				if ( ( $category->get( 'id' ) == $grp->get( 'category' ) ) && ( $parent == $grp->get( 'parent' ) ) && ( ( ! $exclude ) || ( $exclude && ( ! in_array( $grp->get( 'id' ), $exclude ) ) ) ) ) {
					$groups[]			=	$grp;
				}
			}

			$categoryIndent				=	null;

			if ( ! $categoryId ) {
				$categoryGroups			=	array();

				$depth					=	$category->getDepth();

				if ( $depth ) {
					for ( $i = 0, $n = $depth; $i < $n; $i++ ) {
						$categoryIndent	.=	'- - ';
					}
				}

				$options[]				=	moscomprofilerHTML::makeOptGroup( $categoryIndent . $category->getName() );
				$options[]				=	moscomprofilerHTML::makeOptGroup( null );
			}

			if ( $groups ) foreach ( $groups as $group ) {
				if ( $access ) {
					$authorized			=	cbgjClass::getAuthorization( $category, $group );
				} else {
					$authorized			=	array();
				}

				if ( ! $categoryRoot ) {
					$indent				=	'- - ';
				} else {
					$indent				=	null;
				}

				if ( $parent ) {
					$depth				=	$group->getDepth( $parent );

					if ( $depth ) {
						for ( $i = 0, $n = $depth; $i < $n; $i++ ) {
							$indent		.=	'- - ';
						}
					}

					$children[]			=	$group->get( 'id' );
				}

				$optGroup				=	false;

				if ( ( ! $authorized ) || cbgjClass::hasAccess( $access, $authorized ) ) {
					$categoryGroups[]	=	$group->get( 'id' );

					$options[]			=	moscomprofilerHTML::makeOption( $group->get( 'id' ), $categoryIndent . $indent . $group->getName() );
				} else {
					$optGroup			=	true;

					$options[]			=	moscomprofilerHTML::makeOptGroup( $indent . $group->getName() );
					$options[]			=	moscomprofilerHTML::makeOptGroup( null );

					$children			=	array();
				}

				cbgjClass::getGroupOptions( $access, $category->get( 'id' ), $group->get( 'id' ), $exclude, $options, $children, $categoryGroups, $categoryRoot );

				if ( $optGroup && ( ! $children ) ) {
					$options			=	array_slice( $options, 0, -2 );
				}
			}

			if ( ( ! $categoryId ) && ( ! $categoryGroups ) ) {
				$options				=	array_slice( $options, 0, -2 );
			}
		}

		return $options;
	}

	/**
	 * Removes or replaces bad words in a string. Only whole words are removed or replaced.
	 *
	 * @param string $string
	 * @return string
	 */
	static public function getWordFiltering( $string ) {
		$plugin						=	cbgjClass::getPlugin();
		$wordFilter					=	$plugin->params->get( 'general_wordfilter', null );

		if ( $wordFilter ) {
			$filters				=	preg_split( '/(\s*[\r\n])/', $wordFilter );

			if ( $filters ) foreach ( $filters as $filter ) {
				$filterSplit		=	explode( '=', $filter );
				$word				=	( isset( $filterSplit[0] ) ? CBTxt::T( trim( $filterSplit[0] ) ) : null );

				if ( $word ) {
					$replacement	=	( isset( $filterSplit[1] ) ? CBTxt::T( trim( $filterSplit[1] ) ) : null );

					if ( $replacement ) {
						$string		=	preg_replace( '/(\b' . preg_quote( $word, '/' ) . '\b)+/i', $replacement, $string );
					} else {
						$string		=	preg_replace( '/( ?\b' . preg_quote( $word, '/' ) . '\b ?)+/i', '', preg_replace( '/( \b' . preg_quote( $word, '/' ) . '\b )+/i', ' ', $string ) );
					}
				}
			}
		}

		return $string;
	}
}

class cbgjCategory extends comprofilerDBTable {
	var $id				=	null;
	var $published		=	null;
	var $parent			=	null;
	var $user_id		=	null;
	var $name			=	null;
	var $description	=	null;
	var $logo			=	null;
	var $access			=	null;
	var $types			=	null;
	var $create			=	null;
	var $create_access	=	null;
	var $nested			=	null;
	var $nested_access	=	null;
	var $date			=	null;
	var $ordering		=	null;
	var $params			=	null;

	/**
	 * constructor for groupjive categories database
	 *
	 * @param object $db
	 */
	public function __construct( $db ) {
		$this->comprofilerDBTable( '#__groupjive_categories', 'id', $db );
	}

    /**
     * returns object variable or default if missing
     *
     * @param string $var
     * @param mixed $def
     * @return mixed
     */
    public function get( $var, $def = null ) {
		if ( isset( $this->$var ) ) {
			return $this->$var;
		} else {
			return $def;
		}
	}

	/**
	 * stores category to database
	 *
	 * @param bool $updateNulls
	 * @return bool
	 */
	public function store( $updateNulls = false ) {
		global $_CB_framework, $_CB_database, $_PLUGINS;

		$plugin	=	cbgjClass::getPlugin();
		$user	=&	CBuser::getUserDataInstance( $_CB_framework->myId() );
		$new	=	( $this->get( 'id' ) ? false : true );

		if ( ! $new ) {
			$_PLUGINS->trigger( 'gj_onBeforeUpdateCategory', array( &$this, $user, $plugin ) );
		} else {
			$_PLUGINS->trigger( 'gj_onBeforeCreateCategory', array( &$this, $user, $plugin ) );
		}

		if ( ! parent::store( $updateNulls ) ) {
			return false;
		}

		if ( ! $new ) {
			$_PLUGINS->trigger( 'gj_onAfterUpdateCategory', array( $this, $user, $plugin ) );
		} else {
			cbgjClass::resetCache( true );
			cbgjClass::saveNotifications( $this->get( 'id' ), null, $this->getOwner(), $plugin );

			$_PLUGINS->trigger( 'gj_onAfterCreateCategory', array( $this, $user, $plugin ) );
		}

		$this->updateOrder( $_CB_database->NameQuote( 'parent' ) . ' = ' . (int) $this->get( 'parent' ) );

		return true;
	}

	/**
	 * deletes category only
	 *
	 * @param null|int $id
	 * @return bool
	 */
	public function delete( $id = null ) {
		global $_CB_framework, $_CB_database, $_PLUGINS;

		$plugin	=	cbgjClass::getPlugin();
		$user	=&	CBuser::getUserDataInstance( $_CB_framework->myId() );

		$_PLUGINS->trigger( 'gj_onBeforeDeleteCategory', array( &$this, $user, $plugin ) );

		if ( ! parent::delete( $id ) ) {
			return false;
		}

		$_PLUGINS->trigger( 'gj_onAfterDeleteCategory', array( $this, $user, $plugin ) );

		$this->updateOrder( $_CB_database->NameQuote( 'parent' ) . ' = ' . (int) $this->get( 'parent' ) );

		return true;
	}

	/**
	 * moves category database position
	 *
	 * @param $order
	 * @param null|string $where
	 * @param string $ordering
	 * @return bool|void
	 */
	public function move( $order, $where = null, $ordering = 'ordering' ) {
		global $_CB_framework, $_CB_database, $_PLUGINS;

		$plugin	=	cbgjClass::getPlugin();
		$user	=&	CBuser::getUserDataInstance( $_CB_framework->myId() );

		$_PLUGINS->trigger( 'gj_onBeforeUpdateCategoryOrder', array( &$order, &$this, $user, $plugin ) );

		parent::move( (int) $order, $where, $ordering );

		$_PLUGINS->trigger( 'gj_onAfterUpdateCategoryOrder', array( $this->get( 'ordering' ), $this, $user, $plugin ) );

		$this->updateOrder( $_CB_database->NameQuote( 'parent' ) . ' = ' . (int) $this->get( 'parent' ) );

		return true;
	}

	/**
	 * deletes category and its groups
	 *
	 * @return boolean
	 */
	public function deleteAll() {
		$plugin			=	cbgjClass::getPlugin();
		$categoryPath	=	$plugin->imgsAbs . '/' . (int) $this->get( 'id' );

		if ( file_exists( $categoryPath ) ) {
			cbgjClass::deleteDirectory( $categoryPath );
		}

		if ( ( $plg = @scandir( $plugin->imgsAbs ) ) && count( $plg ) <= 3 ) {
			cbgjClass::deleteDirectory( $plugin->imgsAbs );
		}

		$groups			=	$this->getGroups();

		if ( $groups ) foreach ( $groups as $group ) {
			if ( ! $group->deleteAll() ) {
				return false;
			}
		}

		$categories		=	$this->getNested();

		if ( $categories ) foreach ( $categories as $category ) {
			if ( ! $category->deleteAll() ) {
				return false;
			}
		}

		$notifications	=	$this->getNotifications();

		if ( $notifications ) foreach ( $notifications as $notification ) {
			if ( ! $notification->delete() ) {
				return false;
			}
		}

		if ( ! $this->delete() ) {
			return false;
		}

		return true;
	}

	/**
	 * stores category state to database
	 *
	 * @param int $state
	 * @return boolean
	 */
	public function storeState( $state ) {
		global $_CB_framework, $_PLUGINS;

		$plugin	=	cbgjClass::getPlugin();
		$user	=&	CBuser::getUserDataInstance( $_CB_framework->myId() );

		$_PLUGINS->trigger( 'gj_onBeforeUpdateCategoryState', array( &$state, &$this, $user, $plugin ) );

		$this->set( 'published', (int) $state );

		if ( ! parent::store() ) {
			return false;
		}

		$_PLUGINS->trigger( 'gj_onAfterUpdateCategoryState', array( $this->get( 'published' ), $this, $user, $plugin ) );

		return true;
	}

	/**
	 * stores category order to database
	 *
	 * @param int $order
	 * @return boolean
	 */
	public function storeOrder( $order ) {
		global $_CB_framework, $_CB_database, $_PLUGINS;

		$plugin	=	cbgjClass::getPlugin();
		$user	=&	CBuser::getUserDataInstance( $_CB_framework->myId() );

		$_PLUGINS->trigger( 'gj_onBeforeUpdateCategoryOrder', array( &$order, &$this, $user, $plugin ) );

		$this->set( 'ordering', (int) $order );

		if ( ! parent::store() ) {
			return false;
		}

		$_PLUGINS->trigger( 'gj_onAfterUpdateCategoryOrder', array( $this->get( 'ordering' ), $this, $user, $plugin ) );

		$this->updateOrder( $_CB_database->NameQuote( 'parent' ) . ' = ' . (int) $this->get( 'parent' ) );

		return true;
	}

	/**
	 * stores a copy of this category in database
	 *
	 * @return boolean
	 */
	public function storeCopy() {
		global $_CB_framework, $_CB_database, $_PLUGINS;

		$plugin					=	cbgjClass::getPlugin();
		$user					=&	CBuser::getUserDataInstance( $_CB_framework->myId() );

		$id						=	(int) $this->get( 'id' );

		$this->set( 'id', null );
		$this->set( 'published', 0 );
		$this->set( 'params', null );

		$_PLUGINS->trigger( 'gj_onBeforeCreateCategory', array( &$this, $user, $plugin ) );

		if ( ! parent::store() ) {
			return false;
		}

		if ( $this->get( 'logo' ) ) {
			$categoryPath		=	$plugin->imgsAbs . '/' . (int) $id;
			$nextCategoryPath	=	$plugin->imgsAbs . '/' . (int) $this->get( 'id' );
			$mode				=	cbgjClass::getFilePerms();

			cbgjClass::createFolderPath( $plugin->imgsAbs, $nextCategoryPath );

			if ( file_exists( $categoryPath . '/' . $this->getCleanLogo() ) ) {
				@copy( $categoryPath . '/' . $this->getCleanLogo(), $nextCategoryPath . '/' . $this->getCleanLogo() );
				@chmod( $nextCategoryPath . '/' . $this->getCleanLogo(), $mode );
			}

			if ( file_exists( $categoryPath . '/tn' . $this->getCleanLogo() ) ) {
				@copy( $categoryPath . '/tn' . $this->getCleanLogo(), $nextCategoryPath . '/tn' . $this->getCleanLogo() );
				@chmod( $nextCategoryPath . '/tn' . $this->getCleanLogo(), $mode );
			}
		}

		$_PLUGINS->trigger( 'gj_onAfterCreateCategory', array( $this, $user, $plugin ) );

		$this->updateOrder( $_CB_database->NameQuote( 'parent' ) . ' = ' . (int) $this->get( 'parent' ) );

		return true;
	}

	/**
	 * stores category params to database
	 *
	 * @param mixed $params
	 * @param boolean $html
	 * @return boolean
	 */
	public function storeParams( $params, $html = true ) {
		global $_CB_framework, $_PLUGINS;

		$plugin	=	cbgjClass::getPlugin();
		$user	=&	CBuser::getUserDataInstance( $_CB_framework->myId() );
		$params	=	cbgjClass::parseParams( $params, $html, $this->getParams( $html ) );

		$_PLUGINS->trigger( 'gj_onBeforeUpdateCategoryParams', array( &$params, &$this, $user, $plugin ) );

		$this->set( 'params', trim( $params->toIniString() ) );

		if ( ! parent::store() ) {
			return false;
		}

		$_PLUGINS->trigger( 'gj_onAfterUpdateCategoryParams', array( $params, $this, $user, $plugin ) );

		return true;
	}

	/**
	 * prepares and stores category logo
	 *
	 * @param  string $file
	 * @return string
	 */
	public function storeLogo( $file ) {
		global $ueConfig;

		if ( isset( $_FILES[$file]['tmp_name'] ) && ! empty( $_FILES[$file]['tmp_name'] ) && ( $_FILES[$file]['error'] == 0 ) && ( is_uploaded_file( $_FILES[$file]['tmp_name'] ) ) ) {
			$plugin							=	cbgjClass::getPlugin();
			$logoSize						=	$plugin->params->get( 'logo_size', 2000 );
			$logoWidth						=	$plugin->params->get( 'logo_width', 200 );
			$logoHeight						=	$plugin->params->get( 'logo_height', 500 );
			$logoThumbwidth					=	$plugin->params->get( 'logo_thumbwidth', 60 );
			$logoThumbheight				=	$plugin->params->get( 'logo_thumbheight', 86 );
			$categoryPath					=	$plugin->imgsAbs . '/' . (int) $this->get( 'id' );
			$mode							=	cbgjClass::getFilePerms();

			cbgjClass::createFolderPath( $plugin->imgsAbs, $categoryPath );

			$allwaysResize					=	( isset( $ueConfig['avatarResizeAlways'] ) ? $ueConfig['avatarResizeAlways'] : 1 );
			$fileNameInDir					=	preg_replace( '/[^-a-zA-Z0-9_]/', '', uniqid( (int) $this->get( 'user_id' ) . '_' ) );

			$imgToolBox						=	new imgToolBox();
			$imgToolBox->_conversiontype	=	$ueConfig['conversiontype'];
			$imgToolBox->_IM_path			=	$ueConfig['im_path'];
			$imgToolBox->_NETPBM_path		=	$ueConfig['netpbm_path'];
			$imgToolBox->_maxsize			=	(int) ( $logoSize ? $logoSize : $ueConfig['avatarSize'] );
			$imgToolBox->_maxwidth			=	(int) ( $logoWidth ? $logoWidth : $ueConfig['avatarWidth'] );
			$imgToolBox->_maxheight			=	(int) ( $logoHeight ? $logoHeight : $ueConfig['avatarHeight'] );
			$imgToolBox->_thumbwidth		=	(int) ( $logoThumbwidth ? $logoThumbwidth : $ueConfig['thumbWidth'] );
			$imgToolBox->_thumbheight		=	(int) ( $logoThumbheight ? $logoThumbheight : $ueConfig['thumbHeight'] );
			$imgToolBox->_debug				=	0;

			$newFileName					=	$imgToolBox->processImage( $_FILES[$file], $fileNameInDir, $categoryPath . '/', 0, 0, 1, $allwaysResize );

			if ( $newFileName ) {
				if ( $this->get( 'logo' ) ) {
					if ( file_exists( $categoryPath . '/' . $this->getCleanLogo() ) ) {
						@unlink( $categoryPath . '/' . $this->getCleanLogo() );
					}

					if ( file_exists( $categoryPath . '/tn' . $this->getCleanLogo() ) ) {
						@unlink( $categoryPath . '/tn' . $this->getCleanLogo() );
					}
				}

				$this->set( 'logo', $newFileName );

				@chmod( $categoryPath . '/' . $this->getCleanLogo(), $mode );
				@chmod( $categoryPath . '/tn' . $this->getCleanLogo(), $mode );

				$this->store();
			} else {
				$this->set( '_error', CBTxt::T( str_replace( 'Error: ', '', $imgToolBox->_errMSG ) ) );
			}
		}
	}

	/**
	 * deletes category logo
	 */
	public function unsetLogo() {
		if ( $this->get( 'logo' ) ) {
			$plugin			=	cbgjClass::getPlugin();
			$categoryPath	=	$plugin->imgsAbs . '/' . (int) $this->get( 'id' );

			if ( file_exists( $categoryPath . '/' . $this->getCleanLogo() ) ) {
				@unlink( $categoryPath . '/' . $this->getCleanLogo() );
			}

			if ( file_exists( $categoryPath . '/tn' . $this->getCleanLogo() ) ) {
				@unlink( $categoryPath . '/tn' . $this->getCleanLogo() );
			}

			$this->set( 'logo', '' );
		}
	}

	/**
	 * sets category params to object
	 *
	 * @param mixed $params
	 * @param boolean $html
	 */
	public function setParams( $params, $html = true ) {
		global $_CB_framework, $_PLUGINS;

		$plugin	=	cbgjClass::getPlugin();
		$user	=&	CBuser::getUserDataInstance( $_CB_framework->myId() );
		$params	=	cbgjClass::parseParams( $params, $html, $this->getParams( $html ) );

		$_PLUGINS->trigger( 'gj_onBeforeSetCategoryParams', array( &$params, &$this, $user, $plugin ) );

		$this->set( 'params', trim( $params->toIniString() ) );

		$_PLUGINS->trigger( 'gj_onAfterSetCategoryParams', array( $params, $this, $user, $plugin ) );
	}

	/**
	 * prepares and appends group parent pathway
	 */
	public function setParentPathway() {
		global $_CB_framework;

		if ( $this->get( 'parent' ) ) {
			if ( $this->getParent()->get( 'parent' ) ) {
				$this->getParent()->setParentPathway();
			} else {
				$title	=	$this->getParent()->getName();

				if ( $title ) {
					$_CB_framework->appendPathWay( $title, $this->getParent()->getUrl() );
				}
			}
		}
	}

	/**
	 * prepares and appends category pathway and title
	 *
	 * @param string $title
	 * @param mixed $url
	 */
	public function setPathway( $title = null, $url = null ) {
		global $_CB_framework;

		$plugin				=	cbgjClass::getPlugin();
		$generalTitle		=	$plugin->params->get( 'general_title', $plugin->name );

		if ( $title !== false ) {
			if ( ! $title ) {
				$title		=	$this->getName();
			}

			if ( $title ) {
				$_CB_framework->setPageTitle( htmlspecialchars( $title ) );
			}
		} else {
			$title			=	$this->getName();
		}

		if ( $generalTitle != '' ) {
			$_CB_framework->appendPathWay( htmlspecialchars( CBTxt::T( $generalTitle ) ), cbgjClass::getPluginURL() );
		}

		$_CB_framework->appendPathWay( htmlspecialchars( cbgjClass::getOverride( 'category', true ) . ' ' . cbgjClass::getOverride( 'overview' ) ), cbgjClass::getPluginURL( array( 'overview' ) ) );
		$this->setParentPathway();

		if ( $url ) {
			if ( $this->get( 'id' ) ) {
				$_CB_framework->appendPathWay( $this->getName(), $this->getUrl() );
			}

			if ( $url === true ) {
				if ( $this->get( 'id' ) ) {
					$url	=	$this->getUrl();
				} else {
					$url	=	cbgjClass::getPluginURL( array( 'overview', 'show' ) );
				}
			}

			if ( $title ) {
				$_CB_framework->appendPathWay( htmlspecialchars( $title ), $url );
			}
		} else {
			if ( $title ) {
				$_CB_framework->appendPathWay( htmlspecialchars( $title ), $this->getUrl() );
			}
		}
	}

	/**
	 * prepares params base object for database row params column
	 *
	 * @param boolean $html
	 * @return cbParamsBase
	 */
	public function getParams( $html = true ) {
		static $cache	=	array();

		$id				=	cbgjClass::getStaticID( array( $this->get( 'id' ), $html ) );

		if ( ! isset( $cache[$id] ) ) {
			$cache[$id]	=	cbgjClass::parseParams( $this->get( 'params' ), $html );
		}

		return $cache[$id];
	}

	/**
	 * cleans logo filename of possible vulnerabilities
	 *
	 * @return string
	 */
	public function getCleanLogo() {
		return preg_replace( '/[^-a-zA-Z0-9_.]/', '', $this->get( 'logo' ) );
	}

	/**
	 * prepare category logo path
	 *
	 * @param boolean $html
	 * @param boolean $linked
	 * @param boolean $thumb
	 * @return string
	 */
	public function getLogo( $html = false, $linked = false, $thumb = false ) {
		static $cache	=	array();

		$id				=	$this->get( 'id' );

		if ( ! isset( $cache[$id] ) ) {
			$plugin		=	cbgjClass::getPlugin();
			$default	=	$plugin->params->get( 'category_logo', 'default_category.png' );

			$cache[$id]	=	( $this->get( 'logo' ) ? $plugin->imgsLive . '/' . (int) $id . '/' . $this->getCleanLogo() : ( $default ? $plugin->livePath . '/images/' . $default : null ) );
		}

		$logo			=	$cache[$id];

		if ( $logo ) {
			if ( $thumb ) {
				$logo	=	( $this->get( 'logo' ) ? str_replace( $this->getCleanLogo(), 'tn' . $this->getCleanLogo(), $logo ) : $logo );
			}

			if ( $html ) {
				$logo	=	'<img alt="' . htmlspecialchars( CBTxt::T( 'Logo' ) ) . '" title="' . $this->getName() . '" src="' . htmlspecialchars( $logo ) . '" class="' . ( $this->get( 'logo' ) ? 'gjLogoCustom' : 'gjLogoDefault' ) . ' img-polaroid" />';
			}

			if ( $linked ) {
				$logo	=	'<a href="' . $this->getUrl() . '" title="' . $this->getName() . '">' . $logo . '</a>';
			}
		}

		return $logo;
	}

	/**
	 * prepare category show url
	 *
	 * @return string
	 */
	public function getUrl() {
		static $cache	=	array();

		$id				=	$this->get( 'id' );

		if ( ! isset( $cache[$id] ) ) {
			$cache[$id]	=	cbgjClass::getPluginURL( array( 'categories', 'show', (int) $id ) );
		}

		return $cache[$id];
	}

	/**
	 * prepare category name
	 *
	 * @param int $length
	 * @param boolean $linked
	 * @return string
	 */
	public function getName( $length = 0, $linked = false ) {
		static $cache	=	array();

		$id				=	$this->get( 'id' );

		if ( ! isset( $cache[$id] ) ) {
			$cache[$id]	=	htmlspecialchars( $this->get( 'name' ) );
		}

		$name			=	$cache[$id];

		if ( $name ) {
			$length		=	(int) $length;

			if ( $length && ( cbIsoUtf_strlen( $name ) > $length ) ) {
				$name	=	rtrim( trim( cbIsoUtf_substr( $name, 0, $length ) ), '.' ) . '...';
				$short	=	true;
			} else {
				$short	=	false;
			}

			if ( $linked ) {
				$name	=	'<a href="' . $this->getUrl() . '"' . ( $short ? ' title="' . $cache[$id] . '"' : null ) . '>' . $name . '</a>';
			}
		}

		return $name;
	}

	/**
	 * prepare category description
	 *
	 * @param int $length
	 * @return string
	 */
	public function getDescription( $length = 0 ) {
		static $cache			=	array();

		$id						=	$this->get( 'id' );

		if ( ! isset( $cache[$id] ) ) {
			$plugin				=	cbgjClass::getPlugin();
			$description		=	$this->get( 'description' );

			if ( $plugin->params->get( 'category_desc_content', 0 ) ) {
				$description	=	cbgjClass::prepareContentPlugins( $description );
			}

			$cache[$id]			=	( $plugin->params->get( 'category_editor', 1 ) >= 2 ? $description : htmlspecialchars( $description ) );
		}

		$description			=	$cache[$id];

		if ( $description ) {
			$length				=	(int) $length;

			if ( $length && ( cbIsoUtf_strlen( strip_tags( $description ) ) > $length ) ) {
				$description	=	rtrim( trim( cbIsoUtf_substr( strip_tags( $description ), 0, $length ) ), '.' ) . '...';
			}
		}

		return $description;
	}

	/**
	 * check if parent category is accessible
	 *
	 * @param array $access
	 * @param boolean $default
	 * @return boolean
	 */
	public function getParentAccess( $access = null, $default = false ) {
		static $cache				=	array();

		$id							=	cbgjClass::getStaticID( array( $this->get( 'parent' ), $access, $default ) );

		if ( ! isset( $cache[$id] ) ) {
			if ( $this->get( 'parent' ) ) {
				$row				=	cbgjData::getCategories( $access, array( 'id', '=', (int) $this->get( 'parent' ) ), null, null, false );

				if ( ! $row->get( 'id' ) ) {
					$cache[$id]		=	false;
				} else {
					if ( $row->get( 'parent' ) ) {
						$cache[$id]	=	$row->getParentAccess( $access, $default );
					} else {
						$cache[$id]	=	true;
					}
				}
			} else {
				$cache[$id]			=	$default;
			}
		}

		return $cache[$id];
	}

	/**
	 * prepare depth count of a parent category
	 *
	 * @param int $parent
	 * @return int
	 */
	public function getDepth( $parent = null ) {
		if ( $parent === null ) {
			$parent					=	(int) $this->get( 'parent' );
		}

		static $cache				=	array();

		if ( ! isset( $cache[$parent] ) ) {
			$rows					=	cbgjData::getCategories( null, array( 'id', '=', (int) $parent ) );

			$cache[$parent]			=	0;

			if ( $rows ) foreach ( $rows as $row ) {
				$cache[$parent]		+=	1;

				if ( $row->get( 'parent' ) ) {
					$cache[$parent]	+=	$this->getDepth( (int) $row->get( 'parent' ) );
				}
			}
		}

		return $cache[$parent];
	}

	/**
	 * prepare parent category object
	 *
	 * @param array|null $access
	 * @return string
	 */
	public function getParent( $access = null ) {
		static $cache	=	array();

		$id				=	cbgjClass::getStaticID( array( $this->get( 'parent' ), $access ) );

		if ( ! isset( $cache[$id] ) ) {
			$cache[$id]	=	cbgjData::getCategories( $access, array( 'id', '=', (int) $this->get( 'parent' ) ), null, null, false );
		}

		return $cache[$id];
	}

	/**
	 * prepare category access
	 *
	 * @return string
	 */
	public function getAccess() {
		global $_CB_framework;

		static $cache		=	array();

		$id					=	$this->get( 'access' );

		if ( ! isset( $cache[$id] ) ) {
			if ( $id == -1 ) {
				$cache[$id]	=	CBTxt::T( 'All Registered Users' );
			} elseif ( $id == -2 ) {
				$cache[$id]	=	CBTxt::T( 'Everybody' );
			} else {
				$cache[$id]	=	$_CB_framework->acl->get_group_name( $id );
			}
		}

		return $cache[$id];
	}

	/**
	 * prepare category available types
	 *
	 * @return array
	 */
	public function getTypes() {
		static $cache			=	array();

		$id						=	$this->get( 'types' );

		if ( ! isset( $cache[$id] ) ) {
			$categoryTypes		=	explode( '|*|', $id );
			$cache[$id]			=	array();

			if ( in_array( 1, $categoryTypes ) ) {
				$cache[$id][]	=	CBTxt::T( 'Open' );
			}

			if ( in_array( 2, $categoryTypes ) ) {
				$cache[$id][]	=	CBTxt::T( 'Approval' );
			}

			if ( in_array( 3, $categoryTypes ) ) {
				$cache[$id][]	=	CBTxt::T( 'Invite' );
			}
		}

		return $cache[$id];
	}

	/**
	 * prepare category owner CB user
	 *
	 * @return moscomprofilerUser
	 */
	public function getOwner() {
		static $cache	=	array();

		$id				=	$this->get( 'user_id' );

		if ( ! isset( $cache[$id] ) ) {
			$cache[$id]	=&	CBuser::getUserDataInstance( (int) $id );
		}

		return $cache[$id];
	}

	/**
	 * prepare category owner CB user name
	 *
	 * @param boolean $linked
	 * @return string
	 */
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

	/**
	 * prepare category owner CB user avatar
	 *
	 * @param boolean $linked
	 * @return string
	 */
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

	/**
	 * prepare category owner CB user online status
	 *
	 * @param boolean $html
	 * @return string
	 */
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

	/**
	 * prepare in object a categories groups
	 *
	 * @param array|null $access
	 * @return array
	 */
	public function getGroups( $access = null ) {
		static $cache	=	array();

		$id				=	cbgjClass::getStaticID( array( $this->get( 'id' ), $access ) );

		if ( ! isset( $cache[$id] ) ) {
			$cache[$id]	=	cbgjData::getGroups( $access, array( 'category', '=', (int) $this->get( 'id' ) ) );
		}

		return $cache[$id];
	}

	/**
	 * prepare in object a categories groups (includes nested of nested)
	 *
	 * @param array|null $access
	 * @return array
	 */
	public function getGroupsRecursive( $access = null ) {
		static $cache						=	array();

		$id									=	cbgjClass::getStaticID( array( $this->get( 'id' ), $access ) );

		if ( ! isset( $cache[$id] ) ) {
			$groups							=	$this->getGroups( $access );
			$rows							=	array();

			if ( $groups ) foreach ( $groups as $groupId => $group ) {
				$rows[$groupId]				=	$group;

				$nestedGroups				=	$group->getNestedRecursive( $access );

				if ( $nestedGroups ) foreach ( $nestedGroups as $nestedGroupId => $nestedGroup ) {
					$rows[$nestedGroupId]	=	$nestedGroup;
				}
			}

			$cache[$id]						=	$rows;
		}

		return $cache[$id];
	}

	/**
	 * prepare in object a categories notifications
	 *
	 * @param array|null $access
	 * @return array
	 */
	public function getNotifications( $access = null ) {
		static $cache	=	array();

		$id				=	cbgjClass::getStaticID( array( $this->get( 'id' ), $access ) );

		if ( ! isset( $cache[$id] ) ) {
			$cache[$id]	=	cbgjData::getNotifications( $access, array( array( 'type', '=', 'category' ), array( 'item', '=', (int) $this->get( 'id' ) ) ) );
		}

		return $cache[$id];
	}

	/**
	 * prepare in object a categories nested categories
	 *
	 * @param array|null $access
	 * @return array
	 */
	public function getNested( $access = null ) {
		static $cache	=	array();

		$id				=	cbgjClass::getStaticID( array( $this->get( 'id' ), $access ) );

		if ( ! isset( $cache[$id] ) ) {
			$cache[$id]	=	cbgjData::getCategories( $access, array( 'parent', '=', (int) $this->get( 'id' ) ) );
		}

		return $cache[$id];
	}

	/**
	 * prepare in object a categories nested categories recursively (including nested of nested)
	 *
	 * @param array|null $access
	 * @return array
	 */
	public function getNestedRecursive( $access = null ) {
		static $cache	=	array();

		$id				=	cbgjClass::getStaticID( array( $this->get( 'id' ), $access ) );

		if ( ! isset( $cache[$id] ) ) {
			$cache[$id]	=	cbgjData::positionParents( cbgjData::getCategories( $access ), $this->get( 'id' ) );
		}

		return $cache[$id];
	}

	/**
	 * prepare in object a categories nested categories groups
	 *
	 * @param array|null $access
	 * @param array|null $groupAccess
	 * @return array
	 */
	public function getNestedGroups( $access = null, $groupAccess = null ) {
		static $cache				=	array();

		$id							=	cbgjClass::getStaticID( array( $this->get( 'id' ), $access, $groupAccess ) );

		if ( ! isset( $cache[$id] ) ) {
			$categories				=	$this->getNested( $access );
			$rows					=	array();

			if ( $categories ) foreach ( $categories as $category ) {
				$groups				=	$category->getGroups( $groupAccess );

				if ( $groups ) foreach ( $groups as $groupId => $group ) {
					$rows[$groupId]	=	$group;
				}
			}

			$cache[$id]				=	$rows;
		}

		return $cache[$id];
	}

	/**
	 * prepare in object a categories nested categories groups recursively (including nested of nested)
	 *
	 * @param array|null $access
	 * @param array|null $groupAccess
	 * @return array
	 */
	public function getNestedGroupsRecursive( $access = null, $groupAccess = null ) {
		static $cache					=	array();

		$id								=	cbgjClass::getStaticID( array( $this->get( 'id' ), $access, $groupAccess ) );

		if ( ! isset( $cache[$id] ) ) {
			$categories					=	$this->getNestedRecursive( $access );
			$rows						=	array();

			if ( $categories ) foreach ( $categories as $category ) {
				$groups					=	$category->getGroupsRecursive( $groupAccess );

				if ( $groups ) foreach ( $groups as $groupId => $group ) {
					$rows[$groupId]		=	$group;
				}
			}

			$cache[$id]					=	$rows;
		}

		return $cache[$id];
	}

	/**
	 * prepare category group count
	 *
	 * @param array|null $access
	 * @return int
	 */
	public function groupCount( $access = array( 'grp_access', 'mod_lvl2' ) ) {
		static $cache	=	array();

		$id				=	cbgjClass::getStaticID( array( $this->get( 'id' ), $access ) );

		if ( ! isset( $cache[$id] ) ) {
			$cache[$id]	=	count( $this->getGroups( $access ) );
		}

		return $cache[$id];
	}

	/**
	 * prepare category group recursive count (includes nested of nested)
	 *
	 * @param array|null $access
	 * @return int
	 */
	public function groupRecursiveCount( $access = array( 'grp_access', 'mod_lvl2' ) ) {
		static $cache	=	array();

		$id				=	cbgjClass::getStaticID( array( $this->get( 'id' ), $access ) );

		if ( ! isset( $cache[$id] ) ) {
			$cache[$id]	=	count( $this->getGroupsRecursive( $access ) );
		}

		return $cache[$id];
	}

	/**
	 * prepare nested category count
	 *
	 * @param array|null $access
	 * @return int
	 */
	public function nestedCount( $access = array( 'cat_access', 'mod_lvl1' ) ) {
		static $cache		=	array();

		$id					=	cbgjClass::getStaticID( array( $this->get( 'id' ), $access ) );

		if ( ! isset( $cache[$id] ) ) {
			$cache[$id]		=	count( $this->getNested( $access ) );
		}

		return $cache[$id];
	}

	/**
	 * prepare nested category recursive count (includes nested of nested)
	 *
	 * @param array|null $access
	 * @return int
	 */
	public function nestedRecursiveCount( $access = array( 'cat_access', 'mod_lvl1' ) ) {
		static $cache		=	array();

		$id					=	cbgjClass::getStaticID( array( $this->get( 'id' ), $access ) );

		if ( ! isset( $cache[$id] ) ) {
			$cache[$id]		=	count( $this->getNestedRecursive( $access ) );
		}

		return $cache[$id];
	}

	/**
	 * prepare in object a categories nested categories groups count
	 *
	 * @param array|null $access
	 * @param array|null $groupAccess
	 * @return array
	 */
	public function nestedGroupCount( $access = array( 'cat_access', 'mod_lvl1' ), $groupAccess = array( 'grp_access', 'mod_lvl2' ) ) {
		static $cache		=	array();

		$id					=	cbgjClass::getStaticID( array( $this->get( 'id' ), $access, $groupAccess ) );

		if ( ! isset( $cache[$id] ) ) {
			$cache[$id]		=	count( $this->getNestedGroups( $access, $groupAccess ) );
		}

		return $cache[$id];
	}

	/**
	 * prepare in object a categories nested categories groups recursive count (includes nested of nested)
	 *
	 * @param array|null $access
	 * @param array|null $groupAccess
	 * @return array
	 */
	public function nestedGroupRecursiveCount( $access = array( 'cat_access', 'mod_lvl1' ), $groupAccess = array( 'grp_access', 'mod_lvl2' ) ) {
		static $cache		=	array();

		$id					=	cbgjClass::getStaticID( array( $this->get( 'id' ), $access, $groupAccess ) );

		if ( ! isset( $cache[$id] ) ) {
			$cache[$id]		=	count( $this->getNestedGroupsRecursive( $access, $groupAccess ) );
		}

		return $cache[$id];
	}

	/**
	 * prepare in object a categories group and nested categories group count
	 *
	 * @param array|null $access
	 * @param array|null $groupAccess
	 * @return array
	 */
	public function groupCountTotal( $access = array( 'cat_access', 'mod_lvl1' ), $groupAccess = array( 'grp_access', 'mod_lvl2' ) ) {
		static $cache		=	array();

		$id					=	cbgjClass::getStaticID( array( $this->get( 'id' ), $access, $groupAccess ) );

		if ( ! isset( $cache[$id] ) ) {
			$cache[$id]		=	( $this->groupCount( $groupAccess ) + $this->nestedGroupCount( $access, $groupAccess ) );
		}

		return $cache[$id];
	}

	/**
	 * prepare in object a categories group and nested categories group recursive count (nested of nested)
	 *
	 * @param array|null $access
	 * @param array|null $groupAccess
	 * @return array
	 */
	public function groupCountRecursiveTotal( $access = array( 'cat_access', 'mod_lvl1' ), $groupAccess = array( 'grp_access', 'mod_lvl2' ) ) {
		static $cache		=	array();

		$id					=	cbgjClass::getStaticID( array( $this->get( 'id' ), $access, $groupAccess ) );

		if ( ! isset( $cache[$id] ) ) {
			$cache[$id]		=	( $this->groupRecursiveCount( $groupAccess ) + $this->nestedGroupRecursiveCount( $access, $groupAccess ) );
		}

		return $cache[$id];
	}
}

class cbgjGroup extends comprofilerDBTable {
	var $id				=	null;
	var $published		=	null;
	var $category		=	null;
	var $parent			=	null;
	var $user_id		=	null;
	var $name			=	null;
	var $description	=	null;
	var $logo			=	null;
	var $access			=	null;
	var $type			=	null;
	var $invite			=	null;
	var $users			=	null;
	var $nested			=	null;
	var $nested_access	=	null;
	var $date			=	null;
	var $ordering		=	null;
	var $params			=	null;

	/**
	 * constructor for groupjive groups database
	 *
	 * @param object $db
	 */
	public function __construct( $db ) {
		$this->comprofilerDBTable( '#__groupjive_groups', 'id', $db );
	}

    /**
     * returns object variable or default if missing
     *
     * @param string $var
     * @param mixed $def
     * @return mixed
     */
    public function get( $var, $def = null ) {
		if ( isset( $this->$var ) ) {
			return $this->$var;
		} else {
			return $def;
		}
	}

	/**
	 * stores group to database
	 *
	 * @param bool $updateNulls
	 * @return bool
	 */
	public function store( $updateNulls = false ) {
		global $_CB_framework, $_CB_database, $_PLUGINS;

		$plugin	=	cbgjClass::getPlugin();
		$user	=&	CBuser::getUserDataInstance( $_CB_framework->myId() );
		$new	=	( $this->get( 'id' ) ? false : true );

		if ( ! $new ) {
			$_PLUGINS->trigger( 'gj_onBeforeUpdateGroup', array( &$this, $this->getCategory(), $user, $plugin ) );
		} else {
			$_PLUGINS->trigger( 'gj_onBeforeCreateGroup', array( &$this, $this->getCategory(), $user, $plugin ) );
		}

		if ( ! parent::store( $updateNulls ) ) {
			return false;
		}

		if ( ! $new ) {
			$_PLUGINS->trigger( 'gj_onAfterUpdateGroup', array( $this, $this->getCategory(), $user, $plugin ) );
		} else {
			cbgjClass::resetCache( true );
			cbgjClass::saveNotifications( $this->get( 'category' ), $this->get( 'id' ), $this->getOwner(), $plugin );

			$_PLUGINS->trigger( 'gj_onAfterCreateGroup', array( $this, $this->getCategory(), $user, $plugin ) );
		}

		$this->updateOrder( $_CB_database->NameQuote( 'category' ) . ' = ' . (int) $this->get( 'category' ) . ' AND ' . $_CB_database->NameQuote( 'parent' ) . ' = ' . (int) $this->get( 'parent' ) );

		return true;
	}

	/**
	 * deletes group only
	 *
	 * @param null|int $id
	 * @return bool
	 */
	public function delete( $id = null ) {
		global $_CB_framework, $_CB_database, $_PLUGINS;

		$plugin	=	cbgjClass::getPlugin();
		$user	=&	CBuser::getUserDataInstance( $_CB_framework->myId() );

		$_PLUGINS->trigger( 'gj_onBeforeDeleteGroup', array( &$this, $this->getCategory(), $user, $plugin ) );

		if ( ! parent::delete( $id ) ) {
			return false;
		}

		$_PLUGINS->trigger( 'gj_onAfterDeleteGroup', array( $this, $this->getCategory(), $user, $plugin ) );

		$this->updateOrder( $_CB_database->NameQuote( 'category' ) . ' = ' . (int) $this->get( 'category' ) . ' AND ' . $_CB_database->NameQuote( 'parent' ) . ' = ' . (int) $this->get( 'parent' ) );

		return true;
	}

	/**
	 * moves group database position
	 *
	 * @param $order
	 * @param null|string $where
	 * @param string $ordering
	 * @return bool|void
	 */
	public function move( $order, $where = null, $ordering = 'ordering' ) {
		global $_CB_framework, $_CB_database, $_PLUGINS;

		$plugin	=	cbgjClass::getPlugin();
		$user	=&	CBuser::getUserDataInstance( $_CB_framework->myId() );

		$_PLUGINS->trigger( 'gj_onBeforeUpdateGroupOrder', array( &$order, &$this, $this->getCategory(), $user, $plugin ) );

		parent::move( (int) $order, $where, $ordering );

		$_PLUGINS->trigger( 'gj_onAfterUpdateGroupOrder', array( $this->get( 'ordering' ), $this, $this->getCategory(), $user, $plugin ) );

		$this->updateOrder( $_CB_database->NameQuote( 'category' ) . ' = ' . (int) $this->get( 'category' ) . ' AND ' . $_CB_database->NameQuote( 'parent' ) . ' = ' . (int) $this->get( 'parent' ) );

		return true;
	}

	/**
	 * deletes group and its users
	 *
	 * @return boolean
	 */
	public function deleteAll() {
		$plugin			=	cbgjClass::getPlugin();
		$categoryPath	=	$plugin->imgsAbs . '/' . (int) $this->get( 'category' );
		$groupPath		=	$categoryPath . '/' . (int) $this->get( 'id' );

		if ( file_exists( $groupPath ) ) {
			cbgjClass::deleteDirectory( $groupPath );
		}

		if ( ( $plg = @scandir( $categoryPath ) ) && count( $plg ) <= 3 ) {
			cbgjClass::deleteDirectory( $categoryPath );
		}

		if ( ( $plg = @scandir( $plugin->imgsAbs ) ) && count( $plg ) <= 3 ) {
			cbgjClass::deleteDirectory( $plugin->imgsAbs );
		}

		$users			=	$this->getUsers();

		if ( $users ) foreach ( $users as $user ) {
			if ( ! $user->deleteAll() ) {
				return false;
			}
		}

		$groups			=	$this->getNested();

		if ( $groups ) foreach ( $groups as $group ) {
			if ( ! $group->deleteAll() ) {
				return false;
			}
		}

		$notifications	=	$this->getNotifications();

		if ( $notifications ) foreach ( $notifications as $notification ) {
			if ( ! $notification->delete() ) {
				return false;
			}
		}

		if ( ! $this->delete() ) {
			return false;
		}

		return true;
	}

	/**
	 * stores group state to database
	 *
	 * @param int $state
	 * @return boolean
	 */
	public function storeState( $state ) {
		global $_CB_framework, $_PLUGINS;

		$plugin	=	cbgjClass::getPlugin();
		$user	=&	CBuser::getUserDataInstance( $_CB_framework->myId() );

		$_PLUGINS->trigger( 'gj_onBeforeUpdateGroupState', array( &$state, &$this, $this->getCategory(), $user, $plugin ) );

		$this->set( 'published', (int) $state );

		if ( ! parent::store() ) {
			return false;
		}

		$_PLUGINS->trigger( 'gj_onAfterUpdateGroupState', array( $this->get( 'published' ), $this, $this->getCategory(), $user, $plugin ) );

		return true;
	}

	/**
	 * stores group order to database
	 *
	 * @param int $order
	 * @return boolean
	 */
	public function storeOrder( $order ) {
		global $_CB_framework, $_CB_database, $_PLUGINS;

		$plugin	=	cbgjClass::getPlugin();
		$user	=&	CBuser::getUserDataInstance( $_CB_framework->myId() );

		$_PLUGINS->trigger( 'gj_onBeforeUpdateGroupOrder', array( &$order, &$this, $this->getCategory(), $user, $plugin ) );

		$this->set( 'ordering', (int) $order );

		if ( ! parent::store() ) {
			return false;
		}

		$_PLUGINS->trigger( 'gj_onAfterUpdateGroupOrder', array( $this->get( 'published' ), $this, $this->getCategory(), $user, $plugin ) );

		$this->updateOrder( $_CB_database->NameQuote( 'category' ) . ' = ' . (int) $this->get( 'category' ) . ' AND ' . $_CB_database->NameQuote( 'parent' ) . ' = ' . (int) $this->get( 'parent' ) );

		return true;
	}

	/**
	 * stores group params to database
	 *
	 * @param mixed $params
	 * @param boolean $html
	 * @return boolean
	 */
	public function storeParams( $params, $html = true ) {
		global $_CB_framework, $_PLUGINS;

		$plugin	=	cbgjClass::getPlugin();
		$user	=&	CBuser::getUserDataInstance( $_CB_framework->myId() );
		$params	=	cbgjClass::parseParams( $params, $html, $this->getParams( $html ) );

		$_PLUGINS->trigger( 'gj_onBeforeUpdateGroupParams', array( &$params, &$this, $this->getCategory(), $user, $plugin ) );

		$this->set( 'params', trim( $params->toIniString() ) );

		if ( ! parent::store() ) {
			return false;
		}

		$_PLUGINS->trigger( 'gj_onAfterUpdateGroupParams', array( $params, $this, $this->getCategory(), $user, $plugin ) );

		return true;
	}

	/**
	 * stores a copy of this group in database
	 *
	 * @return boolean
	 */
	public function storeCopy() {
		global $_CB_framework, $_CB_database, $_PLUGINS;

		$plugin				=	cbgjClass::getPlugin();
		$user				=&	CBuser::getUserDataInstance( $_CB_framework->myId() );

		$id					=	(int) $this->get( 'id' );

		$this->set( 'id', null );
		$this->set( 'published', 0 );
		$this->set( 'params', null );

		$_PLUGINS->trigger( 'gj_onBeforeCreateGroup', array( &$this, $this->getCategory(), $user, $plugin ) );

		if ( ! parent::store() ) {
			return false;
		}

		if ( $this->get( 'logo' ) ) {
			$categoryPath	=	$plugin->imgsAbs . '/' . (int) $this->get( 'category' );
			$groupPath		=	$categoryPath . '/' . (int) $id;
			$nextGroupPath	=	$categoryPath . '/' . (int) $this->get( 'id' );
			$mode			=	cbgjClass::getFilePerms();

			cbgjClass::createFolderPath( $plugin->imgsAbs, $categoryPath, $nextGroupPath );

			if ( file_exists( $groupPath . '/' . $this->getCleanLogo() ) ) {
				@copy( $groupPath . '/' . $this->getCleanLogo(), $nextGroupPath . '/' . $this->getCleanLogo() );
				@chmod( $nextGroupPath . '/' . $this->getCleanLogo(), $mode );
			}

			if ( file_exists( $groupPath . '/tn' . $this->getCleanLogo() ) ) {
				@copy( $groupPath . '/tn' . $this->getCleanLogo(), $nextGroupPath . '/tn' . $this->getCleanLogo() );
				@chmod( $nextGroupPath . '/tn' . $this->getCleanLogo(), $mode );
			}
		}

		$this->storeOwner( $this->get( 'user_id' ) );

		$_PLUGINS->trigger( 'gj_onAfterCreateGroup', array( $this, $this->getCategory(), $user, $plugin ) );

		$this->updateOrder( $_CB_database->NameQuote( 'category' ) . ' = ' . (int) $this->get( 'category' ) . ' AND ' . $_CB_database->NameQuote( 'parent' ) . ' = ' . (int) $this->get( 'parent' ) );

		return true;
	}

	/**
	 * sets group owner for existing group from one user to another or creates owner if one does not exist
	 *
	 * @param int $user_id
	 */
	public function storeOwner( $user_id ) {
		if ( $this->get( 'id' ) && $user_id ) {
			$prevOwner	=	cbgjData::getUsers( null, array( array( 'group', '=', (int) $this->get( 'id' ) ), array( 'status', '=', 4 ) ), null, null, false );

			if ( ( $prevOwner->get( 'user_id' ) != $user_id ) || ( ! $prevOwner->get( 'user_id' ) ) ) {
				if ( $prevOwner->get( 'id' ) ) {
					$prevOwner->deleteAll();
				}

				$owner	=	cbgjData::getUsers( null, array( array( 'group', '=', (int) $this->get( 'id' ) ), array( 'user_id', '=', (int) $user_id ) ), null, null, false );

				if ( ! $owner->get( 'id' ) ) {
					$owner->set( 'user_id', (int) $user_id );
					$owner->set( 'group', (int) $this->get( 'id' ) );
					$owner->set( 'date', cbgjClass::getUTCDate() );
				}

				$owner->set( 'status', 4 );

				if ( $owner->store() ) {
					if ( $this->get( 'user_id' ) != $owner->get( 'user_id' ) ) {
						$this->set( 'user_id', (int) $owner->get( 'user_id' ) );

						$this->store();
					}
				}
			}
		}
	}

	/**
	 * prepares and stores group logo
	 *
	 * @param  string $file
	 * @return string
	 */
	public function storeLogo( $file ) {
		global $ueConfig;

		if ( isset( $_FILES[$file]['tmp_name'] ) && ! empty( $_FILES[$file]['tmp_name'] ) && ( $_FILES[$file]['error'] == 0 ) && ( is_uploaded_file( $_FILES[$file]['tmp_name'] ) ) ) {
			$plugin							=	cbgjClass::getPlugin();
			$logoSize						=	$plugin->params->get( 'logo_size', 2000 );
			$logoWidth						=	$plugin->params->get( 'logo_width', 200 );
			$logoHeight						=	$plugin->params->get( 'logo_height', 500 );
			$logoThumbwidth					=	$plugin->params->get( 'logo_thumbwidth', 60 );
			$logoThumbheight				=	$plugin->params->get( 'logo_thumbheight', 86 );
			$categoryPath					=	$plugin->imgsAbs . '/' . (int) $this->get( 'category' );
			$groupPath						=	$categoryPath . '/' . (int) $this->get( 'id' );
			$mode							=	cbgjClass::getFilePerms();

			cbgjClass::createFolderPath( $plugin->imgsAbs, $categoryPath, $groupPath );

			$allwaysResize					=	( isset( $ueConfig['avatarResizeAlways'] ) ? $ueConfig['avatarResizeAlways'] : 1 );
			$fileNameInDir					=	preg_replace( '/[^-a-zA-Z0-9_]/', '', uniqid( (int) $this->get( 'user_id' ) . '_' ) );

			$imgToolBox						=	new imgToolBox();
			$imgToolBox->_conversiontype	=	$ueConfig['conversiontype'];
			$imgToolBox->_IM_path			=	$ueConfig['im_path'];
			$imgToolBox->_NETPBM_path		=	$ueConfig['netpbm_path'];
			$imgToolBox->_maxsize			=	(int) ( $logoSize ? $logoSize : $ueConfig['avatarSize'] );
			$imgToolBox->_maxwidth			=	(int) ( $logoWidth ? $logoWidth : $ueConfig['avatarWidth'] );
			$imgToolBox->_maxheight			=	(int) ( $logoHeight ? $logoHeight : $ueConfig['avatarHeight'] );
			$imgToolBox->_thumbwidth		=	(int) ( $logoThumbwidth ? $logoThumbwidth : $ueConfig['thumbWidth'] );
			$imgToolBox->_thumbheight		=	(int) ( $logoThumbheight ? $logoThumbheight : $ueConfig['thumbHeight'] );
			$imgToolBox->_debug				=	0;

			$newFileName					=	$imgToolBox->processImage( $_FILES[$file], $fileNameInDir, $groupPath . '/', 0, 0, 1, $allwaysResize );

			if ( $newFileName ) {
				if ( $this->get( 'logo' ) ) {
					if ( file_exists( $groupPath . '/' . $this->getCleanLogo() ) ) {
						@unlink( $groupPath . '/' . $this->getCleanLogo() );
					}

					if ( file_exists( $groupPath . '/tn' . $this->getCleanLogo() ) ) {
						@unlink( $groupPath . '/tn' . $this->getCleanLogo() );
					}
				}

				$this->set( 'logo', $newFileName );

				@chmod( $groupPath . '/', $this->getCleanLogo(), $mode );
				@chmod( $groupPath . '/tn', $this->getCleanLogo(), $mode );

				$this->store();
			} else {
				$this->set( '_error', CBTxt::T( str_replace( 'Error: ', '', $imgToolBox->_errMSG ) ) );
			}
		} elseif ( $this->get( 'logo' ) && isset( $this->_previousCategory ) && ( $this->_previousCategory != $this->get( 'category' ) ) ) {
			$plugin				=	cbgjClass::getPlugin();
			$categoryPath		=	$plugin->imgsAbs . '/' . (int) $this->get( 'category' );
			$groupPath			=	$categoryPath . '/' . (int) $this->get( 'id' );
			$oldCategoryPath	=	$plugin->imgsAbs . '/' . (int) $this->_previousCategory;
			$oldGroupPath		=	$oldCategoryPath . '/' . (int) $this->get( 'id' );

			if ( file_exists( $oldGroupPath ) ) {
				cbgjClass::createFolderPath( $plugin->imgsAbs, $categoryPath, $groupPath );
				cbgjClass::copyDirectory( $oldGroupPath, $groupPath );
				cbgjClass::deleteDirectory( $oldGroupPath );
			}
		}
	}

	/**
	 * deletes group logo
	 */
	public function unsetLogo() {
		if ( $this->get( 'logo' ) ) {
			$plugin			=	cbgjClass::getPlugin();
			$categoryPath	=	$plugin->imgsAbs . '/' . (int) $this->get( 'category' );
			$groupPath		=	$categoryPath . '/' . (int) $this->get( 'id' );

			if ( file_exists( $groupPath . '/' . $this->getCleanLogo() ) ) {
				@unlink( $groupPath . '/' . $this->getCleanLogo() );
			}

			if ( file_exists( $groupPath . '/tn' . $this->getCleanLogo() ) ) {
				@unlink( $groupPath . '/tn' . $this->getCleanLogo() );
			}

			$this->set( 'logo', '' );
		}
	}

	/**
	 * sets group params to object
	 *
	 * @param mixed $params
	 * @param boolean $html
	 */
	public function setParams( $params, $html = true ) {
		global $_CB_framework, $_PLUGINS;

		$plugin	=	cbgjClass::getPlugin();
		$user	=&	CBuser::getUserDataInstance( $_CB_framework->myId() );
		$params	=	cbgjClass::parseParams( $params, $html, $this->getParams( $html ) );

		$_PLUGINS->trigger( 'gj_onBeforeSetGroupParams', array( &$params, &$this, $this->getCategory(), $user, $plugin ) );

		$this->set( 'params', trim( $params->toIniString() ) );

		$_PLUGINS->trigger( 'gj_onAfterSetGroupParams', array( $params, $this, $this->getCategory(), $user, $plugin ) );
	}

	/**
	 * prepares and appends group parent pathway
	 */
	public function setParentPathway() {
		global $_CB_framework;

		if ( $this->get( 'parent' ) ) {
			if ( $this->getParent()->get( 'parent' ) ) {
				$this->getParent()->setParentPathway();
			} else {
				$title	=	$this->getParent()->getName();

				if ( $title ) {
					$_CB_framework->appendPathWay( $title, $this->getParent()->getUrl() );
				}
			}
		}
	}

	/**
	 * prepares and appends group pathway and title
	 *
	 * @param string $title
	 * @param mixed $url
	 */
	public function setPathway( $title = null, $url = null ) {
		global $_CB_framework;

		if ( $title !== false ) {
			if ( ! $title ) {
				$title		=	$this->getName();
			}

			if ( $title ) {
				$_CB_framework->setPageTitle( htmlspecialchars( $title ) );
			}
		} else {
			$title			=	$this->getName();
		}

		$this->getCategory()->setPathway( false );
		$this->setParentPathway();

		if ( $url ) {
			if ( $this->get( 'id' ) ) {
				$_CB_framework->appendPathWay( $this->getName(), cbgjClass::getPluginURL( array( 'groups', 'show', (int) $this->get( 'category' ), (int) $this->get( 'id' ) ) ) );
			}

			if ( $url === true ) {
				if ( $this->get( 'id' ) ) {
					$url	=	cbgjClass::getPluginURL( array( 'groups', 'show', (int) $this->get( 'category' ), (int) $this->get( 'id' ) ) );
				} else {
					$url	=	cbgjClass::getPluginURL( array( 'categories', 'show', (int) $this->get( 'category' ) ) );
				}
			}

			if ( $title ) {
				$_CB_framework->appendPathWay( htmlspecialchars( $title ), $url );
			}
		} else {
			if ( $title ) {
				$_CB_framework->appendPathWay( htmlspecialchars( $title ), cbgjClass::getPluginURL( array( 'groups', 'show', (int) $this->getCategory()->get( 'id' ), (int) $this->get( 'id' ) ) ) );
			}
		}
	}

	/**
	 * prepares params base object for database row params column
	 *
	 * @param boolean $html
	 * @return cbParamsBase
	 */
	public function getParams( $html = true ) {
		static $cache	=	array();

		$id				=	cbgjClass::getStaticID( array( $this->get( 'id' ), $html ) );

		if ( ! isset( $cache[$id] ) ) {
			$cache[$id]	=	cbgjClass::parseParams( $this->get( 'params' ), $html );
		}

		return $cache[$id];
	}

	/**
	 * cleans logo filename of possible vulnerabilities
	 *
	 * @return string
	 */
	public function getCleanLogo() {
		return preg_replace( '/[^-a-zA-Z0-9_.]/', '', $this->get( 'logo' ) );
	}

	/**
	 * prepare group logo path
	 *
	 * @param boolean $html
	 * @param boolean $linked
	 * @param boolean $thumb
	 * @return string
	 */
	public function getLogo( $html = false, $linked = false, $thumb = false ) {
		static $cache	=	array();

		$id				=	$this->get( 'id' );

		if ( ! isset( $cache[$id] ) ) {
			$plugin		=	cbgjClass::getPlugin();
			$default	=	$plugin->params->get( 'group_logo', 'default.png' );

			$cache[$id]	=	( $this->get( 'logo' ) ? $plugin->imgsLive . '/' . (int) $this->get( 'category' ) . '/' . (int) $id . '/' . $this->getCleanLogo() : ( $default ? $plugin->livePath . '/images/' . $default : null ) );
		}

		$logo			=	$cache[$id];

		if ( $cache[$id] ) {
			if ( $thumb ) {
				$logo	=	( $this->get( 'logo' ) ? str_replace( $this->getCleanLogo(), 'tn' . $this->getCleanLogo(), $logo ) : $logo );
			}

			if ( $html ) {
				$logo	=	'<img alt="' . htmlspecialchars( CBTxt::T( 'Logo' ) ) . '" title="' . $this->getName() . '" src="' . htmlspecialchars( $logo ) . '" class="' . ( $this->get( 'logo' ) ? 'gjLogoCustom' : 'gjLogoDefault' ) . ' img-polaroid" />';
			}

			if ( $linked ) {
				$logo	=	'<a href="' . $this->getUrl() . '" title="' . $this->getName() . '">' . $logo . '</a>';
			}
		}

		return $logo;
	}

	/**
	 * prepare group show url
	 *
	 * @return string
	 */
	public function getUrl() {
		static $cache	=	array();

		$id				=	$this->get( 'id' );

		if ( ! isset( $cache[$id] ) ) {
			$cache[$id]	=	cbgjClass::getPluginURL( array( 'groups', 'show', (int) $this->get( 'category' ), (int) $id ) );
		}

		return $cache[$id];
	}

	/**
	 * prepare group name
	 *
	 * @param int $length
	 * @param boolean $linked
	 * @return string
	 */
	public function getName( $length = 0, $linked = false ) {
		static $cache	=	array();

		$id				=	$this->get( 'id' );

		if ( ! isset( $cache[$id] ) ) {
			$cache[$id]	=	htmlspecialchars( $this->get( 'name' ) );
		}

		$name			=	$cache[$id];

		if ( $name ) {
			$length		=	(int) $length;

			if ( $length && ( cbIsoUtf_strlen( $name ) > $length ) ) {
				$name	=	rtrim( trim( cbIsoUtf_substr( $name, 0, $length ) ), '.' ) . '...';
				$short	=	true;
			} else {
				$short	=	false;
			}

			if ( $linked ) {
				$name	=	'<a href="' . $this->getUrl() . '"' . ( $short ? ' title="' . $cache[$id] . '"' : null ) . '>' . $name . '</a>';
			}
		}

		return $name;
	}

	/**
	 * prepare group description
	 *
	 * @param int $length
	 * @return string
	 */
	public function getDescription( $length = 0 ) {
		static $cache			=	array();

		$id						=	$this->get( 'id' );

		if ( ! isset( $cache[$id] ) ) {
			$plugin				=	cbgjClass::getPlugin();
			$description		=	$this->get( 'description' );

			if ( $plugin->params->get( 'group_desc_content', 0 ) ) {
				$description	=	cbgjClass::prepareContentPlugins( $description );
			}

			$cache[$id]			=	( $plugin->params->get( 'group_editor', 1 ) >= 2 ? $description : htmlspecialchars( $description ) );
		}

		$description			=	$cache[$id];

		if ( $description ) {
			$length				=	(int) $length;

			if ( $length && ( cbIsoUtf_strlen( strip_tags( $description ) ) > $length ) ) {
				$description	=	rtrim( trim( cbIsoUtf_substr( strip_tags( $description ), 0, $length ) ), '.' ) . '...';
			}
		}

		return $description;
	}

	/**
	 * check if parent group is accessible
	 *
	 * @param array $access
	 * @param boolean $default
	 * @return boolean
	 */
	public function getParentAccess( $access = null, $default = false ) {
		static $cache				=	array();

		$id							=	cbgjClass::getStaticID( array( $this->get( 'parent' ), $access, $default ) );

		if ( ! isset( $cache[$id] ) ) {
			if ( $this->get( 'parent' ) ) {
				$row				=	cbgjData::getGroups( $access, array( 'id', '=', (int) $this->get( 'parent' ) ), null, null, false );

				if ( ! $row->get( 'id' ) ) {
					$cache[$id]		=	false;
				} else {
					if ( $row->get( 'parent' ) ) {
						$cache[$id]	=	$row->getParentAccess( $access, $default );
					} else {
						$cache[$id]	=	true;
					}
				}
			} else {
				$cache[$id]			=	$default;
			}
		}

		return $cache[$id];
	}

	/**
	 * prepare depth count of a parent group
	 *
	 * @param int $parent
	 * @return int
	 */
	public function getDepth( $parent = null ) {
		if ( $parent === null ) {
			$parent					=	(int) $this->get( 'parent' );
		}

		static $cache				=	array();

		if ( ! isset( $cache[$parent] ) ) {
			$rows					=	cbgjData::getGroups( null, array( 'id', '=', (int) $parent ) );

			$cache[$parent]			=	0;

			if ( $rows ) foreach ( $rows as $row ) {
				$cache[$parent]		+=	1;

				if ( $row->get( 'parent' ) ) {
					$cache[$parent]	+=	$this->getDepth( $row->get( 'parent' ) );
				}
			}
		}

		return $cache[$parent];
	}

	/**
	 * prepare parent group object
	 *
	 * @param array|null $access
	 * @return string
	 */
	public function getParent( $access = null ) {
		static $cache	=	array();

		$id				=	cbgjClass::getStaticID( array( $this->get( 'parent' ), $access ) );

		if ( ! isset( $cache[$id] ) ) {
			$cache[$id]	=	cbgjData::getGroups( $access, array( 'id', '=', (int) $this->get( 'parent' ) ), null, null, false );
		}

		return $cache[$id];
	}

	/**
	 * prepare group access
	 *
	 * @return string
	 */
	public function getAccess() {
		global $_CB_framework;

		static $cache		=	array();

		$id					=	$this->get( 'access' );

		if ( ! isset( $cache[$id] ) ) {
			if ( $id == -1 ) {
				$cache[$id]	=	CBTxt::T( 'All Registered Users' );
			} elseif ( $id == -2 ) {
				$cache[$id]	=	CBTxt::T( 'Everybody' );
			} else {
				$cache[$id]	=	$_CB_framework->acl->get_group_name( $id );
			}
		}

		return $cache[$id];
	}

	/**
	 * prepare group type
	 *
	 * @return string
	 */
	public function getType() {
		static $cache		=	array();

		$id					=	$this->get( 'type' );

		if ( ! isset( $cache[$id] ) ) {
			if ( $id == 1 ) {
				$cache[$id]	=	CBTxt::T( 'Open' );
			} elseif ( $id == 2 ) {
				$cache[$id]	=	CBTxt::T( 'Approval' );
			} elseif ( $id == 3 ) {
				$cache[$id]	=	CBTxt::T( 'Invite' );
			} else {
				$cache[$id]	=	CBTxt::T( 'Unknown' );
			}
		}

		return $cache[$id];
	}

	/**
	 * prepare group admins list
	 *
	 * @return array
	 */
	public function getAdmins() {
		static $cache				=	array();

		$id							=	$this->get( 'id' );

		if ( ! isset( $cache[$id] ) ) {
			$cache[$id]				=	array();

			$rows					=	cbgjData::getUsers( null, array( 'group', '=', (int) $id ) );

			if ( $rows ) foreach( $rows as $row ) {
				if ( $row->get( 'status' ) == 3 ) {
					$cbUser			=&	CBuser::getInstance( (int) $row->get( 'user_id' ) );

					if ( ! $cbUser ) {
						$cbUser		=&	CBuser::getInstance( null );
					}

					$cache[$id][]	=	$cbUser->getField( 'formatname', null, 'html', 'none', 'list', 0, true );
				}
			}
		}

		return $cache[$id];
	}

	/**
	 * prepare group moderators list
	 *
	 * @return array
	 */
	public function getModerators() {
		static $cache				=	array();

		$id							=	$this->get( 'id' );

		if ( ! isset( $cache[$id] ) ) {
			$cache[$id]				=	array();

			$rows					=	cbgjData::getUsers( null, array( 'group', '=', (int) $id ) );

			if ( $rows ) foreach( $rows as $row ) {
				if ( $row->get( 'status' ) == 2 ) {
					$cbUser			=&	CBuser::getInstance( (int) $row->get( 'user_id' ) );

					if ( ! $cbUser ) {
						$cbUser		=&	CBuser::getInstance( null );
					}

					$cache[$id][]	=	$cbUser->getField( 'formatname', null, 'html', 'none', 'list', 0, true );
				}
			}
		}

		return $cache[$id];
	}

	/**
	 * prepare group actives list
	 *
	 * @return array
	 */
	public function getActives() {
		static $cache				=	array();

		$id							=	$this->get( 'id' );

		if ( ! isset( $cache[$id] ) ) {
			$cache[$id]				=	array();

			$rows					=	cbgjData::getUsers( null, array( 'group', '=', (int) $id ) );

			if ( $rows ) foreach( $rows as $row ) {
				if ( $row->get( 'status' ) == 1 ) {
					$cbUser			=&	CBuser::getInstance( (int) $row->get( 'user_id' ) );

					if ( ! $cbUser ) {
						$cbUser		=&	CBuser::getInstance( null );
					}

					$cache[$id][]	=	$cbUser->getField( 'formatname', null, 'html', 'none', 'list', 0, true );
				}
			}
		}

		return $cache[$id];
	}

	/**
	 * prepare group inactives list
	 *
	 * @return array
	 */
	public function getInactives() {
		static $cache				=	array();

		$id							=	$this->get( 'id' );

		if ( ! isset( $cache[$id] ) ) {
			$cache[$id]				=	array();

			$rows					=	cbgjData::getUsers( null, array( 'group', '=', (int) $id ) );

			if ( $rows ) foreach( $rows as $row ) {
				if ( $row->get( 'status' ) == 0 ) {
					$cbUser			=&	CBuser::getInstance( (int) $row->get( 'user_id' ) );

					if ( ! $cbUser ) {
						$cbUser		=&	CBuser::getInstance( null );
					}

					$cache[$id][]	=	$cbUser->getField( 'formatname', null, 'html', 'none', 'list', 0, true );
				}
			}
		}

		return $cache[$id];
	}

	/**
	 * prepare group banned list
	 *
	 * @return array
	 */
	public function getBanned() {
		static $cache				=	array();

		$id							=	$this->get( 'id' );

		if ( ! isset( $cache[$id] ) ) {
			$cache[$id]				=	array();

			$rows					=	cbgjData::getUsers( null, array( 'group', '=', (int) $id ) );

			if ( $rows ) foreach( $rows as $row ) {
				if ( $row->get( 'status' ) == -1 ) {
					$cbUser			=&	CBuser::getInstance( (int) $row->get( 'user_id' ) );

					if ( ! $cbUser ) {
						$cbUser		=&	CBuser::getInstance( null );
					}

					$cache[$id][]	=	$cbUser->getField( 'formatname', null, 'html', 'none', 'list', 0, true );
				}
			}
		}

		return $cache[$id];
	}

	/**
	 * prepare group owner CB user
	 *
	 * @return moscomprofilerUser
	 */
	public function getOwner() {
		static $cache	=	array();

		$id				=	$this->get( 'user_id' );

		if ( ! isset( $cache[$id] ) ) {
			$cache[$id]	=&	CBuser::getUserDataInstance( (int) $id );
		}

		return $cache[$id];
	}

	/**
	 * prepare group owner CB user name
	 *
	 * @param boolean $linked
	 * @return string
	 */
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

	/**
	 * prepare group owner CB user avatar
	 *
	 * @param boolean $linked
	 * @return string
	 */
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

	/**
	 * prepare group owner CB user online status
	 *
	 * @param boolean $html
	 * @return string
	 */
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

	/**
	 * prepare group owner
	 *
	 * @return cbgjUser
	 */
	public function getUser() {
		static $cache	=	array();

		$id				=	$this->get( 'id' );

		if ( ! isset( $cache[$id] ) ) {
			$cache[$id]	=	cbgjData::getUsers( null, array( array( 'group', '=', (int) $id ), array( 'user_id', '=', (int) $this->get( 'user_id' ) ) ), null, null, false );
		}

		return $cache[$id];
	}

    /**
     * prepare my group user
     *
     * @param int $id
     * @return cbgjUser
     */
    public function getMyUser( $id = null ) {
		global $_CB_framework;

		static $cache	=	array();

		if ( ! $id ) {
			$id			=	$_CB_framework->myId();
		}

		if ( ! isset( $cache[$id] ) ) {
			$cache[$id]	=	cbgjData::getUsers( null, array( array( 'group', '=', (int) $this->get( 'id' ) ), array( 'user_id', '=', (int) $id ) ), null, null, false );
		}

		return $cache[$id];
	}

	/**
	 * prepare group category
	 *
	 * @param array|null $access
	 * @return cbgjCategory
	 */
	public function getCategory( $access = null ) {
		static $cache	=	array();

		$id				=	cbgjClass::getStaticID( array( $this->get( 'category' ), $access ) );

		if ( ! isset( $cache[$id] ) ) {
			$cache[$id]	=	cbgjData::getCategories( $access, array( 'id', '=', (int) $this->get( 'category' ) ), null, null, false );
		}

		return $cache[$id];
	}

	/**
	 * prepare in object a groups users
	 *
	 * @param array|null $access
	 * @return array
	 */
	public function getUsers( $access = null ) {
		static $cache	=	array();

		$id				=	cbgjClass::getStaticID( array( $this->get( 'id' ), $access ) );

		if ( ! isset( $cache[$id] ) ) {
			$cache[$id]	=	cbgjData::getUsers( $access, array( 'group', '=', (int) $this->get( 'id' ) ) );
		}

		return $cache[$id];
	}

	/**
	 * prepare in object a groups invites
	 *
	 * @param array|null $access
	 * @return array
	 */
	public function getInvites( $access = null ) {
		static $cache	=	array();

		$id				=	cbgjClass::getStaticID( array( $this->get( 'id' ), $access ) );

		if ( ! isset( $cache[$id] ) ) {
			$cache[$id]	=	cbgjData::getInvites( $access, array( 'group', '=', (int) $this->get( 'id' ) ) );
		}

		return $cache[$id];
	}

	/**
	 * prepare in object a groups nested groups
	 *
	 * @param array|null $access
	 * @return array
	 */
	public function getNested( $access = null ) {
		static $cache	=	array();

		$id				=	cbgjClass::getStaticID( array( $this->get( 'id' ), $access ) );

		if ( ! isset( $cache[$id] ) ) {
			$cache[$id]	=	cbgjData::getGroups( $access, array( 'parent', '=', (int) $this->get( 'id' ) ) );
		}

		return $cache[$id];
	}

	/**
	 * prepare in object a groups nested groups recursively (including nested of nested)
	 *
	 * @param array|null $access
	 * @return array
	 */
	public function getNestedRecursive( $access = null ) {
		static $cache	=	array();

		$id				=	cbgjClass::getStaticID( array( $this->get( 'id' ), $access ) );

		if ( ! isset( $cache[$id] ) ) {
			$cache[$id]	=	cbgjData::positionParents( cbgjData::getGroups( $access ), $this->get( 'id' ) );
		}

		return $cache[$id];
	}

	/**
	 * prepare in object a groups notifications
	 *
	 * @param array|null $access
	 * @return array
	 */
	public function getNotifications( $access = null ) {
		static $cache	=	array();

		$id				=	cbgjClass::getStaticID( array( $this->get( 'id' ), $access ) );

		if ( ! isset( $cache[$id] ) ) {
			$cache[$id]	=	cbgjData::getNotifications( $access, array( array( 'type', '=', 'group' ), array( 'item', '=', (int) $this->get( 'id' ) ) ) );
		}

		return $cache[$id];
	}

	/**
	 * prepare group user count
	 *
	 * @return int
	 */
	public function userCount() {
		static $cache		=	array();

		$id					=	$this->get( 'id' );

		if ( ! isset( $cache[$id] ) ) {
			$authorized		=	cbgjClass::getAuthorization( $this->get( 'category' ), $this );
			$count			=	0;

			if ( ! cbgjClass::hasAccess( 'mod_lvl4', $authorized ) ) {
				$where		=	array( array( 'status', '>=', 1 ), array( 'c.id', '=', (int) $this->get( 'category' ) ) );
			} else {
				$where		=	array( 'c.id', '=', (int) $this->get( 'category' ) );
			}

			$users			=	cbgjData::getUsers( null, $where );

			if ( $users ) foreach( $users as $user ) {
				if ( $user->get( 'group' ) == $id ) {
					$count	+=	1;
				}
			}

			$cache[$id]		=	$count;
		}

		return $cache[$id];
	}

	/**
	 * prepare nested group count
	 *
	 * @param array|null $access
	 * @return int
	 */
	public function nestedCount( $access = array( 'grp_access', 'mod_lvl2' ) ) {
		static $cache		=	array();

		$id					=	cbgjClass::getStaticID( array( $this->get( 'id' ), $access ) );

		if ( ! isset( $cache[$id] ) ) {
			$cache[$id]		=	count( $this->getNested( $access ) );
		}

		return $cache[$id];
	}

	/**
	 * prepare nested group recursive count (includes nested of nested)
	 *
	 * @param array|null $access
	 * @return int
	 */
	public function nestedRecursiveCount( $access = array( 'grp_access', 'mod_lvl2' ) ) {
		static $cache		=	array();

		$id					=	cbgjClass::getStaticID( array( $this->get( 'id' ), $access ) );

		if ( ! isset( $cache[$id] ) ) {
			$cache[$id]		=	count( $this->getNestedRecursive( $access ) );
		}

		return $cache[$id];
	}

}

class cbgjUser extends comprofilerDBTable {
	var $id			=	null;
	var $user_id	=	null;
	var $group		=	null;
	var $date		=	null;
	var $status		=	null;
	var $params		=	null;

	/**
	 * constructor for groupjive users database
	 *
	 * @param object $db
	 */
	public function __construct( $db ) {
		$this->comprofilerDBTable( '#__groupjive_users', 'id', $db );
	}

    /**
     * returns object variable or default if missing
     *
     * @param string $var
     * @param mixed $def
     * @return mixed
     */
    public function get( $var, $def = null ) {
		if ( isset( $this->$var ) ) {
			return $this->$var;
		} else {
			return $def;
		}
	}

	/**
	 * stores user to database
	 *
	 * @param bool $updateNulls
	 * @return bool
	 */
	public function store( $updateNulls = false ) {
		global $_CB_framework, $_PLUGINS;

		$plugin	=	cbgjClass::getPlugin();
		$user	=&	CBuser::getUserDataInstance( $_CB_framework->myId() );
		$new	=	( $this->get( 'id' ) ? false : true );

		if ( ! $new ) {
			$_PLUGINS->trigger( 'gj_onBeforeUpdateUser', array( &$this, $this->getGroup(), $this->getCategory(), $user, $plugin ) );
		} else {
			$_PLUGINS->trigger( 'gj_onBeforeCreateUser', array( &$this, $this->getGroup(), $this->getCategory(), $user, $plugin ) );
		}

		if ( ! parent::store( $updateNulls ) ) {
			return false;
		}

		if ( ! $new ) {
			$_PLUGINS->trigger( 'gj_onAfterUpdateUser', array( $this, $this->getGroup(), $this->getCategory(), $user, $plugin ) );
		} else {
			cbgjClass::resetCache( true );
			cbgjClass::saveNotifications( $this->getCategory()->get( 'id' ), $this->get( 'group' ), $this->getOwner(), $plugin );

			$_PLUGINS->trigger( 'gj_onAfterCreateUser', array( $this, $this->getGroup(), $this->getCategory(), $user, $plugin ) );
		}

		return true;
	}

	/**
	 * deletes user only
	 *
	 * @param null|int $id
	 * @return bool
	 */
	public function delete( $id = null ) {
		global $_CB_framework, $_PLUGINS;

		$plugin	=	cbgjClass::getPlugin();
		$user	=&	CBuser::getUserDataInstance( $_CB_framework->myId() );

		$_PLUGINS->trigger( 'gj_onBeforeDeleteUser', array( &$this, $this->getGroup(), $this->getCategory(), $user, $plugin ) );

		if ( ! parent::delete( $id ) ) {
			return false;
		}

		$this->set( 'status', -2 );

		$_PLUGINS->trigger( 'gj_onAfterDeleteUser', array( $this, $this->getGroup(), $this->getCategory(), $user, $plugin ) );

		return true;
	}

	/**
	 * deletes user and all users invites
	 *
	 * @return boolean
	 */
	public function deleteAll() {
		$invites		=	$this->getAllInvites();

		if ( $invites ) foreach ( $invites as $invite ) {
			if ( ! $invite->delete() ) {
				return false;
			}
		}

		$notifications	=	$this->getNotifications();

		if ( $notifications ) foreach ( $notifications as $notification ) {
			if ( ! $notification->delete() ) {
				return false;
			}
		}

		if ( ! $this->delete() ) {
			return false;
		}

		return true;
	}

	/**
	 * stores user status to database
	 *
	 * @param int $status
	 * @return boolean
	 */
	public function setStatus( $status ) {
		global $_CB_framework, $_PLUGINS;

		$plugin				=	cbgjClass::getPlugin();
		$user				=&	CBuser::getUserDataInstance( $_CB_framework->myId() );

		$_PLUGINS->trigger( 'gj_onBeforeUpdateUserStatus', array( &$status, &$this, $this->getGroup(), $this->getCategory(), $user, $plugin ) );

		$this->set( 'status', (int) $status );

		if ( ! parent::store() ) {
			return false;
		}

		$_PLUGINS->trigger( 'gj_onAfterUpdateUserStatus', array( $this->get( 'status' ), $this, $this->getGroup(), $this->getCategory(), $user, $plugin ) );

		if ( in_array( $this->get( 'status' ), array( -1, 0 ) ) ) {
			$notifications	=	$this->getNotifications();

			if ( $notifications ) foreach ( $notifications as $notification ) {
				if ( ! $notification->delete() ) {
					return false;
				}
			}
		} else {
			cbgjClass::resetCache( true );
			cbgjClass::saveNotifications( $this->getCategory()->get( 'id' ), $this->get( 'group' ), $this->getOwner(), $plugin );
		}

		return true;
	}

	/**
	 * stores user params to database
	 *
	 * @param mixed $params
	 * @param boolean $html
	 * @return boolean
	 */
	public function storeParams( $params, $html = true ) {
		global $_CB_framework, $_PLUGINS;

		$plugin	=	cbgjClass::getPlugin();
		$user	=&	CBuser::getUserDataInstance( $_CB_framework->myId() );
		$params	=	cbgjClass::parseParams( $params, $html, $this->getParams( $html ) );

		$_PLUGINS->trigger( 'gj_onBeforeUpdateUserParams', array( &$params, &$this, $this->getGroup(), $this->getCategory(), $user, $plugin ) );

		$this->set( 'params', trim( $params->toIniString() ) );

		if ( ! parent::store() ) {
			return false;
		}

		$_PLUGINS->trigger( 'gj_onAfterUpdateUserParams', array( $params, $this, $this->getGroup(), $this->getCategory(), $user, $plugin ) );

		return true;
	}

	/**
	 * sets user params to object
	 *
	 * @param mixed $params
	 * @param boolean $html
	 */
	public function setParams( $params, $html = true ) {
		global $_CB_framework, $_PLUGINS;

		$plugin	=	cbgjClass::getPlugin();
		$user	=&	CBuser::getUserDataInstance( $_CB_framework->myId() );
		$params	=	cbgjClass::parseParams( $params, $html, $this->getParams( $html ) );

		$_PLUGINS->trigger( 'gj_onBeforeSetUserParams', array( &$params, &$this, $this->getGroup(), $this->getCategory(), $user, $plugin ) );

		$this->set( 'params', trim( $params->toIniString() ) );

		$_PLUGINS->trigger( 'gj_onAfterSetUserParams', array( $params, $this, $this->getGroup(), $this->getCategory(), $user, $plugin ) );
	}

	/**
	 * accepts all invites belonging to the user or invite code supplied
	 *
	 * @param string|null $code
	 * @return bool
	 */
	public function acceptInvites( $code = null ) {
		$invites	=	$this->getUnacceptedInvites( $code );

		if ( $invites ) foreach ( $invites as $invite ) {
			$invite->set( 'accepted', cbgjClass::getUTCDate() );
			$invite->set( 'user', (int) $this->get( 'user_id' ) );

			if ( ! $invite->store() ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * prepares params base object for database row params column
	 *
	 * @param boolean $html
	 * @return cbParamsBase
	 */
	public function getParams( $html = true ) {
		static $cache	=	array();

		$id				=	cbgjClass::getStaticID( array( $this->get( 'id' ), $html ) );

		if ( ! isset( $cache[$id] ) ) {
			$cache[$id]	=	cbgjClass::parseParams( $this->get( 'params' ), $html );
		}

		return $cache[$id];
	}

	/**
	 * prepare user status
	 *
	 * @return string
	 */
	public function getStatus() {
		static $cache	=	array();

		$id				=	$this->get( 'status' );

		if ( ! isset( $cache[$id] ) ) {
			if ( $id == -2 ) {
				$status	=	CBTxt::T( 'Deleted' );
			} elseif ( $id == -1 ) {
				$status	=	CBTxt::T( 'Banned' );
			} elseif ( $id == 0 ) {
				$status	=	CBTxt::T( 'Pending' );
			} elseif ( $id == 1 ) {
				$status	=	CBTxt::T( 'Active' );
			} elseif ( $id == 2 ) {
				$status	=	CBTxt::T( 'Moderator' );
			} elseif ( $id == 3 ) {
				$status	=	CBTxt::T( 'Admin' );
			} elseif ( $id == 4 ) {
				$status	=	CBTxt::T( 'Owner' );
			} else {
				$status	=	CBTxt::T( 'Unknown' );
			}

			$cache[$id]	=	$status;
		}

		return $cache[$id];
	}

	/**
	 * prepare user CB user
	 *
	 * @return moscomprofilerUser
	 */
	public function getOwner() {
		static $cache	=	array();

		$id				=	$this->get( 'user_id' );

		if ( ! isset( $cache[$id] ) ) {
			$cache[$id]	=&	CBuser::getUserDataInstance( (int) $id );
		}

		return $cache[$id];
	}

	/**
	 * prepare user CB user name
	 *
	 * @param boolean $linked
	 * @return string
	 */
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

	/**
	 * prepare user CB user avatar
	 *
	 * @param boolean $linked
	 * @return string
	 */
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

	/**
	 * prepare user CB user online status
	 *
	 * @param boolean $html
	 * @return string
	 */
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

	/**
	 * prepare user group category
	 *
	 * @param array|null $access
	 * @param array|null $groupAccess
	 * @return cbgjCategory
	 */
	public function getCategory( $access = null, $groupAccess = null ) {
		static $cache	=	array();

		$id				=	cbgjClass::getStaticID( array( $this->get( 'group' ), $access, $groupAccess ) );

		if ( ! isset( $cache[$id] ) ) {
			$cache[$id]	=	$this->getGroup( $groupAccess )->getCategory( $access );
		}

		return $cache[$id];
	}

	/**
	 * prepare user group
	 *
	 * @param array|null $access
	 * @return cbgjGroup
	 */
	public function getGroup( $access = null ) {
		static $cache	=	array();

		$id				=	cbgjClass::getStaticID( array( $this->get( 'group' ), $access ) );

		if ( ! isset( $cache[$id] ) ) {
			$cache[$id]	=	cbgjData::getGroups( $access, array( 'id', '=', (int) $this->get( 'group' ) ), null, null, false );
		}

		return $cache[$id];
	}

    /**
     * prepare in object a users invites
     *
	 * @param array|null $access
     * @return array
     */
    public function getInvites( $access = null ) {
		static $cache	=	array();

		$id				=	cbgjClass::getStaticID( array( $this->get( 'id' ), $access ) );

		if ( ! isset( $cache[$id] ) ) {
			$cache[$id]	=	cbgjData::getInvites( $access, array( array( 'id', '=', (int) $this->get( 'group' ) ), array( 'user_id', '=', (int) $this->get( 'user_id' ) ) ) );
		}

		return $cache[$id];
	}

	/**
	 * prepare in object invites to a user that are not accepted
	 *
	 * @param string|null $code
	 * @param array|null $access
	 * @return array
	 */
	public function getUnacceptedInvites( $code = null, $access = null ) {
		$id					=	cbgjClass::getStaticID( array( $this->get( 'user_id' ), $code, $access ) );

		if ( ! isset( $cache[$id] ) ) {
			$where			=	array();
			$where[]		=	array( 'group', '=', (int) $this->get( 'group' ) );

			if ( $code ) {
				$where[]	=	array( 'code', '=', $code );
			}

			$where[]		=	array( 'email', '=', $this->getOwner()->email, array( 'user', '=', (int) $this->get( 'user_id' ) ) );
			$where[]		=	array( 'accepted', 'IN', array( '0000-00-00', '0000-00-00 00:00:00', '', NULL ) );

			$cache[$id]		=	cbgjData::getInvites( $access, $where );
		}

		return $cache[$id];
	}

	/**
	 * prepare in object invites to a user
	 *
	 * @param array|null $access
	 * @return array
	 */
	public function getInvited( $access = null ) {
		static $cache	=	array();

		$id				=	cbgjClass::getStaticID( array( $this->get( 'id' ), $access ) );

		if ( ! isset( $cache[$id] ) ) {
			$cache[$id]	=	cbgjData::getInvites( $access, array( array( 'id', '=', (int) $this->get( 'group' ) ), array( 'user', '=', (int) $this->get( 'user_id' ) ) ) );
		}

		return $cache[$id];
	}

	/**
	 * prepare in object all of a users invites (to and from)
	 *
	 * @param array|null $access
	 * @return array
	 */
	public function getAllInvites( $access = null ) {
		static $cache	=	array();

		$id				=	cbgjClass::getStaticID( array( $this->get( 'id' ), $access ) );

		if ( ! isset( $cache[$id] ) ) {
			$cache[$id]	=	cbgjData::getInvites( $access, array( array( 'group', '=', (int) $this->get( 'group' ) ), array( 'user_id', '=', (int) $this->get( 'user_id' ), array( 'user', '=', (int) $this->get( 'user_id' ) ) ) ) );
		}

		return $cache[$id];
	}

	/**
	 * prepare in object a users notifications
	 *
	 * @param array|null $access
	 * @return array
	 */
	public function getNotifications( $access = null ) {
		static $cache	=	array();

		$id				=	cbgjClass::getStaticID( array( $this->get( 'id' ), $access ) );

		if ( ! isset( $cache[$id] ) ) {
			$cache[$id]	=	cbgjData::getNotifications( $access, array( array( 'type', '=', 'group' ), array( 'item', '=', (int) $this->get( 'group' ) ), array( 'user_id', '=', (int) $this->get( 'user_id' ) ) ) );
		}

		return $cache[$id];
	}
}

class cbgjInvite extends comprofilerDBTable {
	var $id			=	null;
	var $user_id	=	null;
	var $group		=	null;
	var $invited	=	null;
	var $accepted	=	null;
	var $code		=	null;
	var $email		=	null;
	var $user		=	null;

	/**
	 * constructor for groupjive invites database
	 *
	 * @param object $db
	 */
	public function __construct( $db ) {
		$this->comprofilerDBTable( '#__groupjive_invites', 'id', $db );
	}

    /**
     * returns object variable or default if missing
     *
     * @param string $var
     * @param mixed $def
     * @return mixed
     */
    public function get( $var, $def = null ) {
		if ( isset( $this->$var ) ) {
			return $this->$var;
		} else {
			return $def;
		}
	}

	/**
	 * stores invite to database
	 *
	 * @param bool $updateNulls
	 * @return bool
	 */
	public function store( $updateNulls = false ) {
		global $_CB_framework, $_PLUGINS;

		$plugin	=	cbgjClass::getPlugin();
		$user	=&	CBuser::getUserDataInstance( $_CB_framework->myId() );
		$new	=	( $this->get( 'id' ) ? false : true );

		if ( ! $new ) {
			$_PLUGINS->trigger( 'gj_onBeforeUpdateInvite', array( &$this, $this->getGroup(), $this->getCategory(), $user, $plugin ) );
		} else {
			$_PLUGINS->trigger( 'gj_onBeforeCreateInvite', array( &$this, $this->getGroup(), $this->getCategory(), $user, $plugin ) );
		}

		if ( ! parent::store( $updateNulls ) ) {
			return false;
		}

		if ( ! $new ) {
			$_PLUGINS->trigger( 'gj_onAfterUpdateInvite', array( $this, $this->getGroup(), $this->getCategory(), $user, $plugin ) );
		} else {
			cbgjClass::resetCache( true );

			$_PLUGINS->trigger( 'gj_onAfterCreateInvite', array( $this, $this->getGroup(), $this->getCategory(), $user, $plugin ) );
		}

		return true;
	}

	/**
	 * deletes invite only
	 *
	 * @param null|int $id
	 * @return bool
	 */
	public function delete( $id = null ) {
		global $_CB_framework, $_PLUGINS;

		$plugin	=	cbgjClass::getPlugin();
		$user	=&	CBuser::getUserDataInstance( $_CB_framework->myId() );

		$_PLUGINS->trigger( 'gj_onBeforeDeleteInvite', array( &$this, $this->getGroup(), $this->getCategory(), $user, $plugin ) );

		if ( ! parent::delete( $id ) ) {
			return false;
		}

		$_PLUGINS->trigger( 'gj_onAfterDeleteInvite', array( $this, $this->getGroup(), $this->getCategory(), $user, $plugin ) );

		return true;
	}

	/**
	 * prepare invited CB user
	 *
	 * @return moscomprofilerUser
	 */
	public function getInvited() {
		static $cache	=	array();

		$id				=	$this->get( 'user' );

		if ( ! isset( $cache[$id] ) ) {
			$cache[$id]	=&	CBuser::getUserDataInstance( (int) $id );
		}

		return $cache[$id];
	}

	/**
	 * prepare invited CB user name
	 *
	 * @param boolean $linked
	 * @return string
	 */
	public function getInvitedName( $linked = false ) {
		static $cache	=	array();

		$id				=	$this->get( 'user' );

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

	/**
	 * prepare invited CB user avatar
	 *
	 * @param boolean $linked
	 * @return string
	 */
	public function getInvitedAvatar( $linked = false ) {
		static $cache	=	array();

		$id				=	$this->get( 'user' );

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

	/**
	 * prepare invited CB user online status
	 *
	 * @param boolean $html
	 * @return string
	 */
	public function getInvitedOnline( $html = true ) {
		static $cache	=	array();

		$id				=	$this->get( 'user' );

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

	/**
	 * prepare invite owner CB user
	 *
	 * @return moscomprofilerUser
	 */
	public function getOwner() {
		static $cache	=	array();

		$id				=	$this->get( 'user_id' );

		if ( ! isset( $cache[$id] ) ) {
			$cache[$id]	=&	CBuser::getUserDataInstance( (int) $id );
		}

		return $cache[$id];
	}

	/**
	 * prepare invite owner CB user name
	 *
	 * @param boolean $linked
	 * @return string
	 */
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

	/**
	 * prepare invite owner CB user avatar
	 *
	 * @param boolean $linked
	 * @return string
	 */
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

	/**
	 * prepare invite owner CB user online status
	 *
	 * @param boolean $html
	 * @return string
	 */
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

	/**
	 * prepare invite group category
	 *
	 * @return cbgjCategory
	 */
	public function getCategory() {
		static $cache	=	array();

		$id				=	$this->get( 'group' );

		if ( ! isset( $cache[$id] ) ) {
			$cache[$id]	=	$this->getGroup()->getCategory();
		}

		return $cache[$id];
	}

	/**
	 * prepare invite group
	 *
	 * @return cbgjGroup
	 */
	public function getGroup() {
		static $cache	=	array();

		$id				=	$this->get( 'group' );

		if ( ! isset( $cache[$id] ) ) {
			$cache[$id]	=	cbgjData::getGroups( null, array( 'id', '=', (int) $id ), null, null, false );
		}

		return $cache[$id];
	}

	/**
	 * prepare invite group user
	 *
	 * @return cbgjUser
	 */
	public function getUser() {
		static $cache	=	array();

		$id				=	$this->get( 'id' );

		if ( ! isset( $cache[$id] ) ) {
			$cache[$id]	=	cbgjData::getUsers( null, array( array( 'group', '=', (int) $this->get( 'group' ) ), array( 'user_id', '=', (int) $this->get( 'user_id' ) ) ), null, null, false );
		}

		return $cache[$id];
	}

	/**
	 * prepare invite status
	 *
	 * @return string
	 */
	public function getStatus() {
		static $cache		=	array();

		$id					=	$this->get( 'id' );

		if ( ! isset( $cache[$id] ) ) {
			if ( $this->isAccepted() ) {
				$cache[$id]	=	'<font color="green">' . CBTxt::T( 'Accepted' ) . '</font>';
			} else {
				$cache[$id]	=	'<font color="red">' . CBTxt::T( 'Pending' ) . '</font>';
			}
		}

		return $cache[$id];
	}

	/**
	 * check if invite has a valid acceptance date
	 *
	 * @return boolean
	 */
	public function isAccepted() {
		static $cache		=	array();

		$id					=	$this->get( 'id' );

		if ( ! isset( $cache[$id] ) ) {
			if ( $this->get( 'accepted' ) && ( $this->get( 'accepted' ) != '0000-00-00 00:00:00' ) && ( $this->get( 'accepted' ) != '0000-00-00' ) ) {
				$accepted	=	true;
			} else {
				$accepted	=	false;
			}

			$cache[$id]		=	$accepted;
		}

		return $cache[$id];
	}

	/**
	 * prepare invite date difference in days
	 *
	 * @return string
	 */
	public function dateDifference() {
		global $_CB_framework;

		static $cache	=	array();

		$id				=	$this->get( 'id' );

		if ( ! isset( $cache[$id] ) ) {
			$cache[$id]	=	( ( strtotime( $_CB_framework->now() ) - strtotime( $this->get( 'invited' ) ) ) / 86400 );
		}

		return $cache[$id];
	}

	/**
	 * prepare available invite by
	 *
	 * @return array
	 */
	static public function inviteBy() {
		static $cache		=	array();

		if ( empty( $cache ) ) {
			$plugin			=	cbgjClass::getPlugin();
			$inviteBy		=	explode( '|*|', $plugin->params->get( 'group_invites_by', '1|*|2|*|3|*|4' ) );

			if ( in_array( 1, $inviteBy ) ) {
				$cache[]	=	CBTxt::T( 'User ID' );
			}

			if ( in_array( 2, $inviteBy ) ) {
				$cache[]	=	CBTxt::T( 'Username' );
			}

			if ( in_array( 3, $inviteBy ) ) {
				$cache[]	=	CBTxt::T( 'Name' );
			}

			if ( in_array( 4, $inviteBy ) ) {
				$cache[]	=	CBTxt::T( 'Email' );
			}
		}

		return $cache;
	}
}

class cbgjNotification extends comprofilerDBTable {
	var $id			=	null;
	var $user_id	=	null;
	var $type		=	null;
	var $item		=	null;
	var $params		=	null;

	/**
	 * constructor for groupjive notification database
	 *
	 * @param object $db
	 */
	public function __construct( $db ) {
		$this->comprofilerDBTable( '#__groupjive_notifications', 'id', $db );
	}

    /**
     * returns object variable or default if missing
     *
     * @param string $var
     * @param mixed $def
     * @return mixed
     */
    public function get( $var, $def = null ) {
		if ( isset( $this->$var ) ) {
			return $this->$var;
		} else {
			return $def;
		}
	}

	/**
	 * stores notification to database
	 *
	 * @param bool $updateNulls
	 * @return bool
	 */
	public function store( $updateNulls = false ) {
		global $_CB_framework, $_PLUGINS;

		$plugin	=	cbgjClass::getPlugin();
		$user	=&	CBuser::getUserDataInstance( $_CB_framework->myId() );
		$new	=	( $this->get( 'id' ) ? false : true );

		if ( ! $new ) {
			$_PLUGINS->trigger( 'gj_onBeforeUpdateNotification', array( &$this, $this->getGroup(), $this->getCategory(), $user, $plugin ) );
		} else {
			$_PLUGINS->trigger( 'gj_onBeforeCreateNotification', array( &$this, $this->getGroup(), $this->getCategory(), $user, $plugin ) );
		}

		if ( ! parent::store( $updateNulls ) ) {
			return false;
		}

		if ( ! $new ) {
			$_PLUGINS->trigger( 'gj_onAfterUpdateNotification', array( $this, $this->getGroup(), $this->getCategory(), $user, $plugin ) );
		} else {
			cbgjClass::resetCache( true );

			$_PLUGINS->trigger( 'gj_onAfterCreateNotification', array( $this, $this->getGroup(), $this->getCategory(), $user, $plugin ) );
		}

		return true;
	}

	/**
	 * deletes notification only
	 *
	 * @param null|int $id
	 * @return bool
	 */
	public function delete( $id = null ) {
		global $_CB_framework, $_PLUGINS;

		$plugin	=	cbgjClass::getPlugin();
		$user	=&	CBuser::getUserDataInstance( $_CB_framework->myId() );

		$_PLUGINS->trigger( 'gj_onBeforeDeleteNotification', array( &$this, $this->getGroup(), $this->getCategory(), $user, $plugin ) );

		if ( ! parent::delete( $id ) ) {
			return false;
		}

		$_PLUGINS->trigger( 'gj_onAfterDeleteNotification', array( $this, $this->getGroup(), $this->getCategory(), $user, $plugin ) );

		return true;
	}

	/**
	 * stores notification params to database
	 *
	 * @param mixed $params
	 * @param boolean $html
	 * @return boolean
	 */
	public function storeParams( $params, $html = true ) {
		global $_CB_framework, $_PLUGINS;

		$plugin	=	cbgjClass::getPlugin();
		$user	=&	CBuser::getUserDataInstance( $_CB_framework->myId() );
		$params	=	cbgjClass::parseParams( $params, $html, $this->getParams( $html ) );

		$_PLUGINS->trigger( 'gj_onBeforeUpdateNotificationParams', array( &$params, &$this, $this->getGroup(), $this->getCategory(), $user, $plugin ) );

		$this->set( 'params', trim( $params->toIniString() ) );

		if ( ! parent::store() ) {
			return false;
		}

		$_PLUGINS->trigger( 'gj_onAfterUpdateNotificationParams', array( $params, $this, $this->getGroup(), $this->getCategory(), $user, $plugin ) );

		return true;
	}

	/**
	 * sets notification params to object
	 *
	 * @param mixed $params
	 * @param boolean $html
	 */
	public function setParams( $params, $html = true ) {
		global $_CB_framework, $_PLUGINS;

		$plugin	=	cbgjClass::getPlugin();
		$user	=&	CBuser::getUserDataInstance( $_CB_framework->myId() );
		$params	=	cbgjClass::parseParams( $params, $html, $this->getParams( $html ) );

		$_PLUGINS->trigger( 'gj_onBeforeSetNotificationParams', array( &$params, &$this, $this->getGroup(), $this->getCategory(), $user, $plugin ) );

		$this->set( 'params', trim( $params->toIniString() ) );

		$_PLUGINS->trigger( 'gj_onAfterSetNotificationParams', array( $params, $this, $this->getGroup(), $this->getCategory(), $user, $plugin ) );
	}

	/**
	 * prepares params base object for database row params column
	 *
	 * @param boolean $html
	 * @return cbParamsBase
	 */
	public function getParams( $html = true ) {
		static $cache	=	array();

		$id				=	cbgjClass::getStaticID( array( $this->get( 'id' ), $html ) );

		if ( ! isset( $cache[$id] ) ) {
			$cache[$id]	=	cbgjClass::parseParams( $this->get( 'params' ), $html );
		}

		return $cache[$id];
	}

	/**
	 * prepare notification owner CB user
	 *
	 * @return moscomprofilerUser
	 */
	public function getOwner() {
		static $cache	=	array();

		$id				=	$this->get( 'user_id' );

		if ( ! isset( $cache[$id] ) ) {
			$cache[$id]	=&	CBuser::getUserDataInstance( (int) $id );
		}

		return $cache[$id];
	}

	/**
	 * prepare notification owner CB user name
	 *
	 * @param boolean $linked
	 * @return string
	 */
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

	/**
	 * prepare notification owner CB user avatar
	 *
	 * @param boolean $linked
	 * @return string
	 */
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

	/**
	 * prepare notification owner CB user online status
	 *
	 * @param boolean $html
	 * @return string
	 */
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

	/**
	 * prepare notification group category
	 *
	 * @return cbgjCategory
	 */
	public function getCategory() {
		static $cache	=	array();

		if ( $this->get( 'type' ) != 'category' ) {
			$id			=	0;
		} else {
			$id			=	$this->get( 'item' );
		}

		if ( ! isset( $cache[$id] ) ) {
			$cache[$id]	=	cbgjData::getCategories( null, array( 'id', '=', (int) $id ), null, null, false );
		}

		return $cache[$id];
	}

	/**
	 * prepare notification group
	 *
	 * @return cbgjGroup
	 */
	public function getGroup() {
		static $cache	=	array();

		if ( $this->get( 'type' ) != 'group' ) {
			$id			=	0;
		} else {
			$id			=	$this->get( 'item' );
		}

		if ( ! isset( $cache[$id] ) ) {
			$cache[$id]	=	cbgjData::getGroups( null, array( 'id', '=', (int) $id ), null, null, false );
		}

		return $cache[$id];
	}
}

class cbgjData {

    /**
     * prepare array of category objects
     *
     * @param array|null $access
     * @param array|null $filtering
     * @param array|null $ordering
     * @param int|array|null $limits
     * @param bool $list
     * @param int $parent
     * @return array|cbgjCategory
     */
    static public function getCategories( $access = array(), $filtering = array(), $ordering = array(), $limits = 0, $list = true, $parent = null ) {
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
			$order			=	"a." . $_CB_database->NameQuote( 'ordering' ) . " ASC";

			if ( $ordering ) {
				if ( in_array( $ordering, array( 'group_count', 'group_count_asc', 'group_count_desc' ) ) ) {
					$order	=	"( SELECT COUNT(*) FROM " . $_CB_database->NameQuote( '#__groupjive_groups' ) . " WHERE " . $_CB_database->NameQuote( 'category' ) . " = a." . $_CB_database->NameQuote( 'id' ) . " ) " . ( $ordering == 'group_count_asc' ? "ASC" : "DESC" );
				} elseif ( in_array( $ordering, array( 'nested_count', 'nested_count_asc', 'nested_count_desc' ) ) ) {
					$order	=	"( SELECT COUNT(*) FROM " . $_CB_database->NameQuote( '#__groupjive_categories' ) . " WHERE " . $_CB_database->NameQuote( 'parent' ) . " = a." . $_CB_database->NameQuote( 'id' ) . " ) " . ( $ordering == 'nested_count_asc' ? "ASC" : "DESC" );
				} else {
					cbgjData::order( $orderBy, $join, $ordering, 'a' );
				}
			}

			$query			=	'SELECT a.*'
							.	"\n FROM " . $_CB_database->NameQuote( '#__groupjive_categories' ) . " AS a";

			if ( count( $join ) ) {
				if ( in_array( 'b', $join ) ) {
					$query	.=	"\n LEFT JOIN " . $_CB_database->NameQuote( '#__users' ) . " AS b"
							.	' ON b.' . $_CB_database->NameQuote( 'id' ) . ' = a.' . $_CB_database->NameQuote( 'user_id' );
				}

				if ( array_intersect( array( 'c', 'd' ), $join ) ) {
					$query	.=	"\n LEFT JOIN " . $_CB_database->NameQuote( '#__groupjive_groups' ) . " AS c"
							.	' ON c.' . $_CB_database->NameQuote( 'category' ) . ' = a.' . $_CB_database->NameQuote( 'id' );
				}

				if ( in_array( 'd', $join ) ) {
					$query	.=	"\n LEFT JOIN " . $_CB_database->NameQuote( '#__groupjive_users' ) . " AS d"
							.	' ON d.' . $_CB_database->NameQuote( 'group' ) . ' = c.' . $_CB_database->NameQuote( 'id' );
				}

				if ( in_array( 'e', $join ) ) {
					$query	.=	"\n LEFT JOIN " . $_CB_database->NameQuote( '#__groupjive_categories' ) . " AS e"
							.	' ON e.' . $_CB_database->NameQuote( 'id' ) . ' = a.' . $_CB_database->NameQuote( 'parent' );
				}
			}

			$query			.=	( count( $where ) ? "\n WHERE " . implode( "\n AND ", $where ) : null )
							.	"\n ORDER BY " . ( count( $orderBy ) ? implode( ', ', $orderBy ) : $order );
			$_CB_database->setQuery( $query );
			$cache[$id]		=	$_CB_database->loadObjectList( 'id', 'cbgjCategory', array( &$_CB_database ) );
		}

		$rows				=	$cache[$id];

		if ( $rows ) {
			if ( $access ) {
				cbgjData::access( $rows, $access );
			}

			if ( $parent !== null ) {
				$rows		=	cbgjData::positionParents( $rows, $parent );
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
				$rows		=	new cbgjCategory( $_CB_database );
			}

			return $rows;
		}
	}

    /**
     * prepare array of group objects
     *
     * @param array|null $access
     * @param array|null $filtering
     * @param array|null $ordering
     * @param int|array|null $limits
     * @param bool $list
     * @param int $parent
     * @return array|cbgjGroup
     */
    static public function getGroups( $access = array(), $filtering = array(), $ordering = array(), $limits = 0, $list = true, $parent = null ) {
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
			$order			=	"a." . $_CB_database->NameQuote( 'ordering' ) . " ASC";

			if ( $ordering ) {
				if ( in_array( $ordering, array( 'user_count', 'user_count_asc', 'user_count_desc' ) ) ) {
					$order	=	"( SELECT COUNT(*) FROM " . $_CB_database->NameQuote( '#__groupjive_users' ) . " WHERE " . $_CB_database->NameQuote( 'group' ) . " = a." . $_CB_database->NameQuote( 'id' ) . " ) " . ( $ordering == 'user_count_asc' ? "ASC" : "DESC" );
				} elseif ( in_array( $ordering, array( 'nested_count', 'nested_count_asc', 'nested_count_desc' ) ) ) {
					$order	=	"( SELECT COUNT(*) FROM " . $_CB_database->NameQuote( '#__groupjive_groups' ) . " WHERE " . $_CB_database->NameQuote( 'parent' ) . " = a." . $_CB_database->NameQuote( 'id' ) . " ) " . ( $ordering == 'nested_count_asc' ? "ASC" : "DESC" );
				} else {
					cbgjData::order( $orderBy, $join, $ordering, 'a' );
				}
			}

			$query			=	'SELECT a.*'
							.	"\n FROM " . $_CB_database->NameQuote( '#__groupjive_groups' ) . " AS a";;

			if ( count( $join ) ) {
				if ( in_array( 'b', $join ) ) {
					$query	.=	"\n LEFT JOIN " . $_CB_database->NameQuote( '#__groupjive_categories' ) . " AS b"
							.	' ON b.' . $_CB_database->NameQuote( 'id' ) . ' = a.' . $_CB_database->NameQuote( 'category' );
				}

				if ( in_array( 'c', $join ) ) {
					$query	.=	"\n LEFT JOIN " . $_CB_database->NameQuote( '#__groupjive_users' ) . " AS c"
							.	' ON c.' . $_CB_database->NameQuote( 'group' ) . ' = a.' . $_CB_database->NameQuote( 'id' )
							.	' AND c.' . $_CB_database->NameQuote( 'user_id' ) . ' = a.' . $_CB_database->NameQuote( 'user_id' );
				}

				if ( in_array( 'd', $join ) ) {
					$query	.=	"\n LEFT JOIN " . $_CB_database->NameQuote( '#__users' ) . " AS d"
							.	' ON d.' . $_CB_database->NameQuote( 'id' ) . ' = a.' . $_CB_database->NameQuote( 'user_id' );
				}

				if ( in_array( 'e', $join ) ) {
					$query	.=	"\n LEFT JOIN " . $_CB_database->NameQuote( '#__groupjive_users' ) . " AS e"
							.	' ON e.' . $_CB_database->NameQuote( 'group' ) . ' = a.' . $_CB_database->NameQuote( 'id' );
				}

				if ( in_array( 'f', $join ) ) {
					$query	.=	"\n LEFT JOIN " . $_CB_database->NameQuote( '#__groupjive_invites' ) . " AS f"
							.	' ON f.' . $_CB_database->NameQuote( 'group' ) . ' = a.' . $_CB_database->NameQuote( 'id' );
				}

				if ( in_array( 'g', $join ) ) {
					$query	.=	"\n LEFT JOIN " . $_CB_database->NameQuote( '#__groupjive_groups' ) . " AS g"
							.	' ON g.' . $_CB_database->NameQuote( 'id' ) . ' = a.' . $_CB_database->NameQuote( 'parent' );
				}
			}

			$query			.=	( count( $where ) ? "\n WHERE " . implode( "\n AND ", $where ) : null )
							.	"\n ORDER BY " . ( count( $orderBy ) ? implode( ', ', $orderBy ) : $order );
			$_CB_database->setQuery( $query );
			$cache[$id]		=	$_CB_database->loadObjectList( 'id', 'cbgjGroup', array( &$_CB_database ) );
		}

		$rows				=	$cache[$id];

		if ( $rows ) {
			if ( $access ) {
				cbgjData::access( $rows, $access );
			}

			if ( $parent !== null ) {
				$rows		=	cbgjData::positionParents( $rows, $parent );
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
				$rows		=	new cbgjGroup( $_CB_database );
			}

			return $rows;
		}
	}

    /**
     * prepare array of user objects
     *
     * @param array|null $access
     * @param array|null $filtering
     * @param array|null $ordering
     * @param int|array|null $limits
     * @param bool $list
     * @return array|cbgjUser
     */
    static public function getUsers( $access = array(), $filtering = array(), $ordering = array(), $limits = 0, $list = true ) {
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
							.	"\n FROM " . $_CB_database->NameQuote( '#__groupjive_users' ) . " AS a";

			if ( count( $join ) ) {
				if ( array_intersect( array( 'b', 'c' ), $join ) ) {
					$query	.=	"\n LEFT JOIN " . $_CB_database->NameQuote( '#__groupjive_groups' ) . " AS b"
							.	' ON b.' . $_CB_database->NameQuote( 'id' ) . ' = a.' . $_CB_database->NameQuote( 'group' );
				}

				if ( in_array( 'c', $join ) ) {
					$query	.=	"\n LEFT JOIN " . $_CB_database->NameQuote( '#__groupjive_categories' ) . " AS c"
							.	' ON c.' . $_CB_database->NameQuote( 'id' ) . ' = b.' . $_CB_database->NameQuote( 'category' );
				}

				if ( in_array( 'd', $join ) ) {
					$query	.=	"\n LEFT JOIN " . $_CB_database->NameQuote( '#__users' ) . " AS d"
							.	' ON d.' . $_CB_database->NameQuote( 'id' ) . ' = a.' . $_CB_database->NameQuote( 'user_id' );
				}
			}

			$query			.=	( count( $where ) ? "\n WHERE " . implode( "\n AND ", $where ) : null )
							.	"\n ORDER BY " . ( count( $orderBy ) ? implode( ', ', $orderBy ) : "( CASE WHEN ( a." . $_CB_database->NameQuote( 'status' ) . " = 0 ) THEN 1 WHEN ( a." . $_CB_database->NameQuote( 'status' ) . " >= 1 ) THEN 2 ELSE 3 END ) ASC, a." . $_CB_database->NameQuote( 'status' ) . " DESC, a." . $_CB_database->NameQuote( 'date' ) . " DESC" );
			$_CB_database->setQuery( $query );
			$cache[$id]		=	$_CB_database->loadObjectList( 'id', 'cbgjUser', array( &$_CB_database ) );
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
				$rows		=	new cbgjUser( $_CB_database );
			}

			return $rows;
		}
	}

    /**
     * prepare array of invite objects
     *
     * @param array|null $access
     * @param array|null $filtering
     * @param array|null $ordering
     * @param int|array|null $limits
     * @param bool $list
     * @return array|cbgjInvite
     */
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
							.	"\n FROM " . $_CB_database->NameQuote( '#__groupjive_invites' ) . " AS a";

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

				if ( in_array( 'f', $join ) ) {
					$query	.=	"\n LEFT JOIN " . $_CB_database->NameQuote( '#__users' ) . " AS f"
							.	' ON f.' . $_CB_database->NameQuote( 'id' ) . ' = a.' . $_CB_database->NameQuote( 'user' );
				}
			}

			$query			.=	( count( $where ) ? "\n WHERE " . implode( "\n AND ", $where ) : null )
							.	"\n ORDER BY " . ( count( $orderBy ) ? implode( ', ', $orderBy ) : "a." . $_CB_database->NameQuote( 'accepted' ) . " ASC" );
			$_CB_database->setQuery( $query );
			$cache[$id]		=	$_CB_database->loadObjectList( 'id', 'cbgjInvite', array( &$_CB_database ) );
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
				$rows		=	new cbgjInvite( $_CB_database );
			}

			return $rows;
		}
	}

    /**
     * prepare array of notification objects
     *
     * @param array|null $access
     * @param array|null $filtering
     * @param array|null $ordering
     * @param int|array|null $limits
     * @param bool $list
     * @return array|cbgjNotification
     */
    static public function getNotifications( $access = array(), $filtering = array(), $ordering = array(), $limits = 0, $list = true ) {
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

        $id					=	cbgjClass::getStaticID( $filtering, $ordering );

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
							.	"\n FROM " . $_CB_database->NameQuote( '#__groupjive_notifications' ) . " AS a";

			if ( count( $join ) ) {
				if ( in_array( 'b', $join ) ) {
					$query	.=	"\n LEFT JOIN " . $_CB_database->NameQuote( '#__groupjive_categories' ) . " AS b"
							.	' ON b.' . $_CB_database->NameQuote( 'id' ) . ' = a.' . $_CB_database->NameQuote( 'item' )
							.	' AND a.' . $_CB_database->NameQuote( 'type' ) . ' = ' . $_CB_database->Quote( 'category' );
				}

				if ( array_intersect( array( 'c', 'd' ), $join ) ) {
					$query	.=	"\n LEFT JOIN " . $_CB_database->NameQuote( '#__groupjive_groups' ) . " AS c"
							.	' ON c.' . $_CB_database->NameQuote( 'id' ) . ' = a.' . $_CB_database->NameQuote( 'item' )
							.	' AND a.' . $_CB_database->NameQuote( 'type' ) . ' = ' . $_CB_database->Quote( 'group' );
				}

				if ( in_array( 'd', $join ) ) {
					$query	.=	"\n LEFT JOIN " . $_CB_database->NameQuote( '#__groupjive_users' ) . " AS d"
							.	' ON d.' . $_CB_database->NameQuote( 'group' ) . ' = c.' . $_CB_database->NameQuote( 'id' )
							.	' AND d.' . $_CB_database->NameQuote( 'user_id' ) . ' = a.' . $_CB_database->NameQuote( 'user_id' );
				}

				if ( in_array( 'e', $join ) ) {
					$query	.=	"\n LEFT JOIN " . $_CB_database->NameQuote( '#__users' ) . " AS e"
							.	' ON e.' . $_CB_database->NameQuote( 'id' ) . ' = a.' . $_CB_database->NameQuote( 'user_id' );
				}
			}

			$query			.=	( count( $where ) ? "\n WHERE " . implode( "\n AND ", $where ) : null )
                            .	( count( $orderBy ) ? "\n ORDER BY " . implode( ', ', $orderBy ) : null );
			$_CB_database->setQuery( $query );
			$cache[$id]		=	$_CB_database->loadObjectList( 'id', 'cbgjNotification', array( &$_CB_database ) );
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
				$rows		=	new cbgjNotification( $_CB_database );
			}

			return $rows;
		}
	}

    /**
     * prepare SQL safe where for query filtering
     *
     * @param array $where
     * @param array $join
     * @param array $filtering
     * @param string $defaultkey
     */
    static public function where( &$where, &$join, $filtering = array(), $defaultkey = null ) {
		global $_CB_database;

		if ( $filtering ) {
			if ( is_array( $filtering[0] ) ) {
				foreach ( $filtering as $filter ) {
					cbgjData::where( $where, $join, $filter, $defaultkey );
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

						cbgjData::where( $or, $join, $or_cases, $defaultkey );

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

    /**
     * prepare SQL safe order byfor query filtering
     *
     * @param array $orderby
     * @param array $join
     * @param array $ordering
     * @param string $defaultkey
     */
    static public function order( &$orderby, &$join, $ordering = array(), $defaultkey = null ) {
		global $_CB_database;

		if ( $ordering ) {
			if ( is_array( $ordering[0] ) ) {
				foreach ( $ordering as $order ) {
					cbgjData::order( $orderby, $join, $order, $defaultkey );
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

	/**
	 * limits rows between a set of values (e.g. 0 to 6)
	 *
	 * @param array $rows
	 * @param mixed $limits
	 */
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

	/**
	 * parses out rows that don't match GJ access levels specified
	 *
	 * @param array $rows
	 * @param array $access
	 */
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

				if ( ( $user == 'owner' ) && method_exists( $row, 'getOwner' ) ) {
					$user			=	$row->getOwner();
				}

				if ( ! $user ) {
					$user			=	$_CB_framework->myId();
				}

				if ( ( ! $owner ) && method_exists( $row, 'getOwner' ) ) {
					$owner			=	$row->getOwner();
				}

				$category			=	null;

				if ( $row instanceof cbgjCategory ) {
					$category		=	$row;
				} elseif ( method_exists( $row, 'getCategory' ) ) {
					$category		=	$row->getCategory();
				}

				$group				=	null;

				if ( $row instanceof cbgjGroup ) {
					$group			=	$row;
				} elseif ( method_exists( $row, 'getGroup' ) ) {
					$group			=	$row->getGroup();
				}

				if ( ( ! $category ) && $group ) {
					if ( method_exists( $group, 'getCategory' ) ) {
						$category	=	$group->getCategory();
					}
				}

				$authorized			=	cbgjClass::getAuthorization( $category, $group, $user, $owner, $row );

				if ( ! cbgjClass::hasAccess( $permissions, $authorized, $and ) ) {
					unset( $rows[$k] );
				}
			}
		}
	}

    /**
     * prepares array into drop-down list safe
     *
     * @param array $rows
     * @param string $value
     * @param string $text
     * @param bool $reposition
     * @return array
     */
    static public function listArray( $rows, $value = 'id', $text = 'name', $reposition = true ) {
		if ( $reposition ) {
			$rows					=	cbgjData::positionParents( $rows );
		}

		$list						=	array();

		if ( ! empty( $rows ) ) foreach ( $rows as $row ) {
			$item					=	new stdClass();
			$item->value			=	$row->$value;
			$item->text				=	$row->$text;

			if ( isset( $row->parent ) && $row->parent && method_exists( $row, 'getDepth' ) ) {
				$depth				=	$row->getDepth( $row->parent );

				if ( $depth ) {
					$indent			=	null;

					for ( $i = 0, $n = $depth; $i < $n; $i++ ) {
						$indent		.=	'- - ';
					}

					if ( $indent ) {
						$item->text	=	$indent . $item->text;
					}
				}
			}

			$list[]					=	$item;
		}

		return $list;
	}

	/**
	 * reorders list with parent and child properly positioned
	 *
	 * @param array $rows
	 * @param int $parentId
	 * @return array
	 */
    static public function positionParents( $rows, $parentId = 0 ) {
		$parents				=	$rows;

		if ( $parents ) foreach ( $parents as $k => $v ) {
			if ( $v->parent != $parentId ) {
				unset( $parents[$k] );
			}
		}

		$list					=	array();
		$order					=	array();

		if ( ! empty( $parents ) ) foreach ( $parents as $parent ) {
			$list[]				=	$parent;
			$order[]			=	$parent->id;

			$children			=	$rows;

			if ( $children ) foreach ( $children as $k => $v ) {
				if ( $v->parent != $parent->id ) {
					unset( $children[$k] );
				}
			}

			if ( ! empty( $children ) ) foreach ( $children as $child ) {
				$list[]			=	$child;
				$order[]		=	$child->id;

				$sub_children	=	cbgjData::positionParents( $rows, $child->id );

				if ( ! empty( $sub_children ) ) foreach ( $sub_children as $sub_child ) {
					$list[]		=	$sub_child;
					$order[]	=	$sub_child->id;
				}
			}
		}

		return $list;
	}
}

class cbgjCaptcha {

	/**
	 * if available render CB Captcha
	 *
	 * @return mixed
	 */
	static public function render() {
		$plugin			=	cbgjClass::getPlugin();
		$captcha		=	cbgjClass::getIntegrations( 'gj_onCaptchaRender', array( $plugin ), null, 'raw' );

		if ( empty( $captcha ) ) {
			$captcha	=	cbgjClass::getIntegrations( 'onGetCaptchaHtmlElements', array( false ), null, 'raw' );
		}

		if ( ! empty( $captcha ) ) {
			$captcha	=	$captcha[0];
			$code		=	( isset( $captcha[0] ) ? $captcha[0] : null );
			$input		=	( isset( $captcha[1] ) ? $captcha[1] : null );

			return array( 'code' => $code, 'input' => $input );
		}

		return false;
	}

	/**
	 * if available validates CB Captcha
	 *
	 * @return mixed
	 */
	static public function validate() {
		global $_PLUGINS;

		$plugin	=	cbgjClass::getPlugin();

		cbgjClass::getIntegrations( 'gj_onCaptchaValidate', array( $plugin ), null, 'raw' );

		if ( ! $_PLUGINS->is_errors() ) {
			cbgjClass::getIntegrations( 'onCheckCaptchaHtmlElements', array(), null, 'raw' );
		}

		if ( $_PLUGINS->is_errors() ) {
			return $_PLUGINS->getErrorMSG();
		}

		return true;
	}
}

class cbgjPaging {
	var $plugin		=	null;
	var $limit		=	null;
	var $limitstart	=	null;
	var $name		=	null;

    /**
     * constructor for groupjive paging
     *
     * @param string $name
     */
    public function __construct( $name ) {
		$this->plugin	=	cbgjClass::getPlugin();
		$this->name		=	$name;

		cbimport( 'cb.pagination' );
	}

	/**
	 * prepares name used in paging inputs
	 *
	 * @param string $name
	 * @return string
	 */
	public function inputName( $name ) {
		return $this->name . '_' . $name;
	}

	/**
	 * prepares paging navigation
	 *
	 * @param int $total
	 * @param int $limitstart
	 * @param int $limit
	 * @return cbPageNav
	 */
	public function getPageNav( $total, $limitstart, $limit ) {
		return new cbPageNav( $total, $limitstart, $limit, array( $this, 'inputName' ) );
	}

    /**
     * returns paging limit
     *
     * @param int $default
     * @return int
     */
    public function getlimit( $default = 0 ) {
		global $_CB_framework;

		$limit			=	( $default ? $default : $_CB_framework->getCfg( 'list_limit' ) );

		if ( ! $limit ) {
			$limit		=	10;
		}

		$this->limit	=	$_CB_framework->getUserStateFromRequest( $this->name . '_limit{' . $this->plugin->option . '}', $this->name . '_limit', (int) $limit );

		return $this->limit;
	}

	/**
	 * returns start of paging
	 *
	 * @param int $default
	 * @return int
	 */
	public function getLimistart( $default = 0 ) {
		global $_CB_framework;

		$this->limitstart	=	$_CB_framework->getUserStateFromRequest( $this->name . '_limitstart{' . $this->plugin->option . '}', $this->name . '_limitstart', (int) $default );

		return $this->limitstart;
	}

	/**
	 * returns paging input values
	 *
	 * @param string $filter
	 * @param string $default
	 * @return string
	 */
	public function getFilter( $filter, $default = '' ) {
		global $_CB_framework;

		$id			=	$this->name . '_' . $filter;
		$return		=	$_CB_framework->getUserStateFromRequest( $id . '{' . $this->plugin->option . '}', $id, $default );

		return $return;
	}

	/**
	 * render bootstrap formatted paging links
	 *
	 * @param cbPageNav $pageNav
	 * @return string
	 */
	public function getPagesLinks( $pageNav ) {
		$return		=	str_replace( 'class="pagenav"', '', $pageNav->getPagesLinks() );
		$return		=	preg_replace( '%(<span.+</span>)%', '<li>$1</li>', $return );
		$return		=	preg_replace( '%(<a.+</a>)%', '<li>$1</li>', $return );
		$return		=	'<ul>' . $return . '</ul>';

		return $return;
	}

	/**
	 * returns paging limit dropdown
	 *
     * @param cbPageNav $pageNav
     * @param string $class
	 * @return string
	 */
	public function getLimitBox( $pageNav, $class = 'input-small' ) {
		return str_replace( 'class="inputbox"', 'class="' . $class . '"', $pageNav->getLimitBox() );
	}

    /**
     * returns input search textbox for paging filtering
     *
     * @param string $form
     * @param string $filter
     * @param string $title
     * @param string $value
     * @param int $size
     * @param string $class
     * @return string
     */
    public function getInputSearch( $form, $filter, $title = '', $value = '', $size = 20, $class = 'input-large' ) {
		$id			=	htmlspecialchars( $this->name . '_' . $filter );
		$onchange	=	"document." . preg_replace( '/[^a-zA-Z0-9_]/', '', $form ) . ".submit();";
		$return		=	'<div class="input-prepend">'
					.		'<span class="add-on"><i class="icon-search"></i></span>'
					.		'<input type="text" id="' . $id . '" name="' . $id . '" onchange="' . $onchange . '" placeholder="' . htmlspecialchars( $title ) . '" value="' . htmlspecialchars( $value ) . '" class="' . htmlspecialchars( $class ) . '" size="' . (int) $size . '" />'
					.	'</div>';

		return $return;
	}

    /**
     * returns input textbox for paging filtering
     *
     * @param string $form
     * @param string $filter
     * @param string $value
     * @param int $size
     * @param string $class
     * @return string
     */
    public function getInputText( $form, $filter, $value = '', $size = 20, $class = 'inputbox' ) {
		$id			=	htmlspecialchars( $this->name . '_' . $filter );
		$onchange	=	"document." . preg_replace( '/[^a-zA-Z0-9_]/', '', $form ) . ".submit();";

		return '<input type="text" id="' . $id . '" name="' . $id . '" value="' . htmlspecialchars( $value ) . '" class="' . htmlspecialchars( $class ) . '" size="' . (int) $size . '" onchange="' . $onchange . '" />';
	}

	/**
	 * returns input selectbox for paging filtering
	 *
	 * @param string $form
	 * @param string $filter
	 * @param array $values
	 * @param mixed $value
	 * @param int $required
	 * @param boolean $htmlspecialed
	 * @param string $class
	 * @return string
	 */
	public function getInputSelect( $form, $filter, $values = array(), $value = '', $required = 0, $htmlspecialed = false, $class = 'inputbox' ) {
		$id			=	htmlspecialchars( $this->name . '_' . $filter );
		$onchange	=	"document." . preg_replace( '/[^a-zA-Z0-9_]/', '', $form ) . ".submit();";

		return moscomprofilerHTML::selectList( $values, $id, 'class="' . htmlspecialchars( $class ) . '" onchange="' . $onchange . '"', 'value', 'text', $value, $required, $htmlspecialed );
	}
}

cbgjClass::getPlugins();
cbgjClass::getLanguage();
?>