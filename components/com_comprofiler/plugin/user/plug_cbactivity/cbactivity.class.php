<?php
if ( ! ( defined( '_VALID_CB' ) || defined( '_JEXEC' ) || defined( '_VALID_MOS' ) ) ) { die( 'Direct Access to this location is not allowed.' ); }

global $_PLUGINS;

static $CB_loaded	=	0;

if ( ! $CB_loaded++ ) {
	$_PLUGINS->loadPluginGroup( 'user' );
}

class cbactivityClass {

	static public function getPlugin() {
		global $_CB_framework, $_CB_database;

		static $plugin								=	null;

		if ( ! isset( $plugin ) ) {
			$query									=	'SELECT *'
													.	"\n FROM " . $_CB_database->NameQuote( '#__comprofiler_plugin' )
													.	"\n WHERE " . $_CB_database->NameQuote( 'element' ) . " = " . $_CB_database->Quote( 'cbactivity' );
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
						.	"\n WHERE " . $_CB_database->NameQuote( 'link' ) . " LIKE " . $_CB_database->Quote( 'index.php?option=com_comprofiler&task=pluginclass&plugin=cbactivity%' )
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

	static public function getPluginURL( $variables = array(), $msg = null, $htmlspecialchars = true, $redirect = false, $type = null, $return = false, $ajax = false, $back = false ) {
		global $_CB_framework;

		$getReturn				=	cbactivityClass::getReturnURL();

		if ( $back && $getReturn ) {
			$url				=	$getReturn;
		} else {
			$plugin				=	cbactivityClass::getPlugin();
			$action				=	( isset( $variables[0] ) ? '&action=' . urlencode( $variables[0] ) : null );
			$id					=	( isset( $variables[2] ) ? '&id=' . urlencode( $variables[2] ) : null );

			if ( $return === 'current' ) {
				$setReturn		=	( $getReturn ? '&return=' . cbactivityClass::UTF8_base64_encode( $getReturn ) : null );
			} else {
				$setReturn		=	( $return ? cbactivityClass::setReturnURL() : null );
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
				$vars			=	$action . $function . $id . $setReturn . cbactivityClass::getItemid();
				$format			=	( $ajax ? ( is_bool( $ajax ) || is_int( $ajax ) ? 'component' : $ajax ) : 'html' );
				$url			=	cbSef( 'index.php?option=' . $plugin->option . '&task=pluginclass&plugin=' . $plugin->element . $vars, $htmlspecialchars, $format );
			}
		}

		if ( $msg ) {
			if ( $redirect ) {
				cbactivityClass::setRedirect( $url, ( $msg === true ? null : $msg ), $type );
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

	static public function getTabClassURL( $userId = null, $htmlspecialchars = true, $format = 'html' ) {
		global $_CB_framework;

		$plugin		=	cbactivityClass::getPlugin();

		if ( $userId && ( $userId == $_CB_framework->myId() ) ) {
			$userId	=	null;
		}

		return cbSef( 'index.php?option=com_comprofiler&task=tabclass' . ( $userId ? '&user=' . (int) $userId : null ) . '&tab=' . $plugin->element . 'Tab' . getCBprofileItemid( false ), $htmlspecialchars, $format );
	}

	static public function getCBURL( $task = null, $msg = null, $htmlspecialchars = true, $redirect = false, $type = null, $return = false, $ajax = false, $back = false ) {
		global $_CB_framework;

		$getReturn				=	cbactivityClass::getReturnURL();

		if ( $back && $getReturn ) {
			$url				=	$getReturn;
		} else {
			$plugin				=	cbactivityClass::getPlugin();

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
				$setReturn		=	( $getReturn ? '&return=' . cbactivityClass::UTF8_base64_encode( $getReturn ) : null );
			} else {
				$setReturn		=	( $return ? cbactivityClass::setReturnURL() : null );
			}

			$itemid				=	( $itemidtask ? getCBprofileItemid( false, $itemidtask ) : getCBprofileItemid() );
			$vars				=	$task . $tab . $itemid . $setReturn;
			$format				=	( $ajax ? ( is_bool( $ajax ) || is_int( $ajax ) ? 'component' : $ajax ) : 'html' );
			$url				=	cbSef( 'index.php?option=' . $plugin->option . $vars, $htmlspecialchars, $format );
		}

		if ( $msg ) {
			if ( $redirect ) {
				cbactivityClass::setRedirect( $url, ( $msg === true ? null : $msg ), $type );
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
			$return					=	'&return=' . cbactivityClass::UTF8_base64_encode( $return );
		}

		return $return;
	}

	static public function getReturnURL() {
		global $_CB_framework;

		$return			=	trim( stripslashes( cbGetParam( $_GET, 'return', null ) ) );

		if ( $return ) {
			$return		=	cbactivityClass::UTF8_base64_decode( $return );
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

    static public function getTemplate( $files = null, $loadGlobal = true, $loadHeader = true ) {
		global $_CB_framework;

		static $tmpl							=	array();

		$id										=	cbactivityClass::getStaticID( array( $files, $loadGlobal, $loadHeader ) );

		if ( ! isset( $tmpl[$id] ) ) {
			$plugin								=	cbactivityClass::getPlugin();
			$template							=	$plugin->params->get( 'general_template', 'default' );
			$files								=	( ! is_array( $files ) ? array( $files ) : $files );
			$paths								=	array( 'global_css' => null, 'php' => null, 'css' => null, 'js' => null, 'override_css' => null );

			foreach ( $files as $file ) {
				$file							=	preg_replace( '/[^-a-zA-Z0-9_]/', '', $file );
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

	static public function setRedirect( $url = null, $msg = null, $type = 'message' ) {
		static $REDIRECT	=	0;

		if ( ! $REDIRECT++ ) {
			if ( ! $url ) {
				$return		=	cbactivityClass::getReturnURL();

				if ( $return ) {
					$url	=	$return;
				}

				if ( ! $url ) {
					$url	=	cbactivityClass::setReturnURL( true );
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

			$menuType->load( array( 'menutype' => 'incubator' ) );

			if ( ! $menuType->id ) {
				$menuType->menutype	=	'incubator';
				$menuType->title		=	CBTxt::T( 'Incubator' );
				$menuType->description	=	CBTxt::T( 'Internal menu links generated by Incubator Projects.' );

				$menuType->check();

				if ( ! $menuType->store() ) {
					return false;
				}
			}

			$table						=	JTable::getInstance( 'Menu' );

			while ( $table->load( array( 'alias' => $alias, 'menutype' => 'incubator' ) ) ) {
				$matches				=	null;

				if ( preg_match( '#-(\d+)$#', $alias, $matches ) ) {
					$alias				=	preg_replace( '#-(\d+)$#', '-' . ( $matches[1] + 1 ) . '', $alias );
				} else {
					$alias				.=	'-2';
				}
			}

			$menu						=	JTable::getInstance( 'Menu' );
			$menu->menutype				=	'incubator';
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
										.	"\n WHERE " . $_CB_database->NameQuote( 'menutype' ) . " = " . $_CB_database->Quote( 'incubator' );
				$_CB_database->setQuery( $query );
				$menuType				=	$_CB_database->loadResult();

				if ( ! $menuType ) {
					$query				=	'INSERT INTO ' . $_CB_database->NameQuote( '#__menu_types' )
										.	"\n (" . $_CB_database->NameQuote( 'menutype' )
										.	', ' . $_CB_database->NameQuote( 'title' )
										.	', ' . $_CB_database->NameQuote( 'description' )
										.	')'
										.	"\n VALUES ("
										.	$_CB_database->Quote( 'incubator' )
										.	', ' . $_CB_database->Quote( 'Incubator' )
										.	', ' . $_CB_database->Quote( 'Internal menu links generated by Incubator Projects.' )
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
										.	$_CB_database->Quote( 'incubator' )
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
										.	$_CB_database->Quote( 'incubator' )
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

		$id									=	cbactivityClass::getStaticID( array( $row, $html, $base ) );

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
								$p			=	cbactivityClass::parseParams( $v, $html );
								$v			=	trim( $p->toIniString() );
							} else {
								$v			=	implode( '|*|', $v );
							}
						}

						if ( ( ! is_array( $v ) && ( ! is_object( $v ) ) ) ) {
							if ( $v !== null ) {
								$v			=	stripslashes( $v );

								if ( $html && ( $html !== 'raw' ) ) {
									$v		=	cbactivityClass::getFilteredText( $v );
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

	static public function getCleanParam( $param, $default = null, $items = 'POST' ) {
		if ( $items == 'POST' ) {
			$items	=	$_POST;
		} elseif ( $items == 'GET' ) {
			$items	=	$_GET;
		} elseif ( $items == 'REQUEST' ) {
			$items	=	$_REQUEST;
		}

		$data		=	cbactivityClass::parseParams( $items );

		return $data->get( $param, $default );
	}

	static public function getHTMLCleanParam( $param, $default = null, $items = 'POST' ) {
		if ( $items == 'POST' ) {
			$items	=	$_POST;
		} elseif ( $items == 'GET' ) {
			$items	=	$_GET;
		} elseif ( $items == 'REQUEST' ) {
			$items	=	$_REQUEST;
		}

		$data		=	cbactivityClass::parseParams( $items, true );

		return $data->get( $param, $default );
	}

	static public function getRAWParam( $param, $default = null, $items = 'POST' ) {
		if ( $items == 'POST' ) {
			$items	=	$_POST;
		} elseif ( $items == 'GET' ) {
			$items	=	$_GET;
		} elseif ( $items == 'REQUEST' ) {
			$items	=	$_REQUEST;
		}

		$data		=	cbactivityClass::parseParams( $items, 'raw' );

		return $data->get( $param, $default );
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

	static public function getUTCTimestamp( $time = 'now', $now = null ) {
		static $cache	=	array();

		if ( ! $time ) {
			$time		=	'now';
		}

		$id				=	cbactivityClass::getStaticID( array( $time, $now ) );

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

	static public function getUTCDate( $format = 'Y-m-d H:i:s', $timestamp = null ) {
		static $cache		=	array();

		if ( ! $format ) {
			$format			=	'Y-m-d H:i:s';
		}

		$id					=	cbactivityClass::getStaticID( array( $format, $timestamp ) );

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

	static public function cleanBBCode( $text, $length = 300 ) {
		$text		=	preg_replace( '!:(pinch|whistle|evil|side|kiss|blush|silly|cheer|woohoo|rolleyes|money|lol|dry|huh|blink|ohmy|unsure|mad|angry|laugh):!', '', $text ); // Smilies
		$text		=	preg_replace( '!((\[b\])*\w+ wrote:(\[\/b\])*\s+)*(?s)(\[quote\])(.*?)(\[/quote\])!', '...', $text ); // Quotes
		$text		=	preg_replace( '!(?s)(\[code(.*?)\])(.*?)(\[/code(.*?)\])!', '...', $text ); // Code
		$text		=	preg_replace( '!(?s)(\[article\])(.*?)(\[\/article\])!', '', $text ); // Article
		$text		=	preg_replace( '!(?s)(\[i\])(.*?)(\[\/i\])!', '\2', $text ); // Italic
		$text		=	preg_replace( '!(?s)(\[u\])(.*?)(\[\/u\])!', '\2', $text ); // Underline
		$text		=	preg_replace( '!(?s)(\[b\])(.*?)(\[\/b\])!', '\2', $text ); // Bold
		$text		=	preg_replace( '!(?s)(\[strike\])(.*?)(\[\/strike\])!', '\2', $text ); // Strike
		$text		=	preg_replace( '!(?s)(\[sub\])(.*?)(\[\/sub\])!', '\2', $text ); // Subscript
		$text		=	preg_replace( '!(?s)(\[sup\])(.*?)(\[\/sup\])!', '\2', $text ); // Superscript
		$text		=	preg_replace( '!(?s)(\[ul\])(.*?)(\[\/ul\])!', '\2', $text ); // Unodered List
		$text		=	preg_replace( '!(?s)(\[ol\])(.*?)(\[\/ol\])!', '\2', $text ); // Ordered List
		$text		=	preg_replace( '!(?s)(\[li\])(.*?)(\[\/li\])!', '\2', $text ); // List Item
		$text		=	preg_replace( '!(?s)(\[size(.*?)\])(.*?)(\[\/size\])!', '\3', $text ); // Font Size
		$text		=	preg_replace( '!(?s)(\[color(.*?)\])(.*?)(\[\/color\])!', '\3', $text ); // Font Color
		$text		=	preg_replace( '!(?s)(\[img(.*?)\])(.*?)(\[\/img\])!', '...', $text ); // Image
		$text		=	preg_replace( '!(?s)(\[video(.*?)\])(.*?)(\[\/video\])!', '...', $text ); // Video
		$text		=	preg_replace( '!(?s)(\[hide(.*?)\])(.*?)(\[\/hide\])!', '...', $text ); // Hidden
		$text		=	preg_replace( '!(?s)(\[ebay(.*?)\])(.*?)(\[\/ebay\])!', '...', $text ); // Ebay Item
		$text		=	preg_replace( '!(?s)(\[file(.*?)\])(.*?)(\[\/file\])!', '...', $text ); // File
		$text		=	preg_replace( '!(?s)(\[attachment(.*?)\])(.*?)(\[\/attachment\])!', '...', $text ); // Attachment
		$text		=	preg_replace( '!(?s)(\[spoiler(.*?)\])(.*?)(\[\/spoiler\])!', '...', $text ); // Spoiler
		$text		=	preg_replace( '!(?s)(\[url(.*?)\])(.*?)(\[\/url\])!', '...', $text ); // URL
		$text		=	preg_replace( '!(?s)(\[confidential(.*?)\])(.*?)(\[\/confidential\])!', '', $text ); // Confidential
		$text		=	preg_replace( '%[[/!]*?[^[\]]*?\]%', '', $text ); // Remaining Tags
		$text		=	preg_replace( '/(\.\.\.\s*){2,}/', '... ', $text ); // Remove Duplicate Replacements
		$text		=	strip_tags( $text );
		$text		=	stripslashes( $text );

		if ( $length && ( cbIsoUtf_strlen( $text ) > $length ) ) {
			$text	=	trim( cbIsoUtf_substr( $text, 0, $length ) ) . '...';
			$text	=	preg_replace( '/(\.\.\.\s*){2,}/', '... ', $text ); //Remove Duplicate Replacements
		}

		$text		=	trim( $text );

		return $text;
	}

	static public function getTabObject( $user, $class ) {
		global $_CB_framework;

		static $cache					=	array();

		$myId							=	$_CB_framework->myId();

		$id								=	$myId . $user->get( 'id' ) . $class;

		if ( ! isset( $cache[$id] ) ) {
			$cache[$id]					=	null;

			static $tabsCache			=	array();

			if ( ! isset( $tabsCache[$myId] ) ) {
				$tabsCache[$myId]		=	CBuser::getMyInstance()->_getCbTabs()->_getTabsDb( $user, 'profile' );
			}

			$tabs						=	$tabsCache[$myId];

			if ( $tabs ) foreach ( $tabs as $tab ) {
				if ( $tab->pluginclass == $class ) {
					if ( ! is_object( $tab->params ) ) {
						$tab->params	=	new cbParamsBase( $tab->params );
					}

					$cache[$id]			=	$tab;
				}
			}
		}

		return $cache[$id];
	}
}

class cbactivityActivity extends comprofilerDBTable {
	var $id			=	null;
	var $user_id	=	null;
	var $user		=	null;
	var $type		=	null;
	var $subtype	=	null;
	var $item		=	null;
	var $from		=	null;
	var $to			=	null;
	var $title		=	null;
	var $message	=	null;
	var $icon		=	null;
	var $class		=	null;
	var $date		=	null;

	public function __construct( $db ) {
		$this->comprofilerDBTable( '#__comprofiler_plugin_activity', 'id', $db );
	}

	public function get( $var, $def = null ) {
		if ( isset( $this->$var ) ) {
			return $this->$var;
		} else {
			return $def;
		}
	}

	public function store( $updateNulls = false ) {
		global $_CB_framework, $_PLUGINS;

		$plugin	=	cbactivityClass::getPlugin();
		$user	=&	CBuser::getUserDataInstance( $_CB_framework->myId() );
		$new	=	( $this->get( 'id' ) ? false : true );

		if ( ! $new ) {
			$_PLUGINS->trigger( 'activity_onBeforeUpdateActivity', array( &$this, $user, $plugin ) );
		} else {
			$_PLUGINS->trigger( 'activity_onBeforeCreateActivity', array( &$this, $user, $plugin ) );
		}

		if ( ! parent::store( $updateNulls ) ) {
			return false;
		}

		if ( ! $new ) {
			$_PLUGINS->trigger( 'activity_onAfterUpdateActivity', array( $this, $user, $plugin ) );
		} else {
			$_PLUGINS->trigger( 'activity_onAfterCreateActivity', array( $this, $user, $plugin ) );
		}

		return true;
	}

	public function delete( $id = null ) {
		global $_CB_framework, $_PLUGINS;

		$plugin	=	cbactivityClass::getPlugin();
		$user	=&	CBuser::getUserDataInstance( $_CB_framework->myId() );

		$_PLUGINS->trigger( 'activity_onBeforeDeleteActivity', array( &$this, $user, $plugin ) );

		if ( ! parent::delete( $id ) ) {
			return false;
		}

		$_PLUGINS->trigger( 'activity_onAfterDeleteActivity', array( $this, $user, $plugin ) );

		return true;
	}

	public function getOwner() {
		static $cache	=	array();

		$id				=	$this->get( 'user_id' );

		if ( ! isset( $cache[$id] ) ) {
			$cache[$id]	=&	CBuser::getUserDataInstance( (int) $id );
		}

		return $cache[$id];
	}

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

			if ( ! $name ) {
				$name	=	CBTxt::T( 'Unknown' );
			}
		}

		return $name;
	}

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

		$avatar			=	null;

		if ( $cache[$id] ) {
			$user		=	$cache[$id];

			if ( $linked ) {
				$avatar	=	$user->getField( 'avatar', null, 'html', 'none', 'list', 0, true );
			} else {
				$avatar	=	$user->getField( 'avatar', null, 'html', 'none', 'profile', 0, true );
			}
		}

		return $avatar;
	}

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

	public function getUser() {
		static $cache	=	array();

		$id				=	$this->get( 'user' );

		if ( ! isset( $cache[$id] ) ) {
			$cache[$id]	=&	CBuser::getUserDataInstance( (int) $id );
		}

		return $cache[$id];
	}

	public function getUserName( $linked = false ) {
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

			if ( ! $name ) {
				$name	=	CBTxt::T( 'Unknown' );
			}
		}

		return $name;
	}

	public function getUserAvatar( $linked = false ) {
		static $cache	=	array();

		$id				=	$this->get( 'user' );

		if ( ! isset( $cache[$id] ) ) {
			$cbUser		=&	CBuser::getInstance( (int) $id );

			if ( ! $cbUser ) {
				$cbUser	=&	CBuser::getInstance( null );
			}

			$cache[$id]	=	$cbUser;
		}

		$avatar			=	null;

		if ( $cache[$id] ) {
			$user		=	$cache[$id];

			if ( $linked ) {
				$avatar	=	$user->getField( 'avatar', null, 'html', 'none', 'list', 0, true );
			} else {
				$avatar	=	$user->getField( 'avatar', null, 'html', 'none', 'profile', 0, true );
			}
		}

		return $avatar;
	}

	public function getUserOnline( $html = true ) {
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

	public function getFrom() {
		static $cache	=	array();

		$id				=	$this->get( 'id' );

		if ( ! isset( $cache[$id] ) ) {
			$cache[$id]	=	( $this->get( 'from' ) ? CBTxt::T( $this->get( 'from' ) ) : null );
		}

		return $cache[$id];
	}

	public function getTo() {
		static $cache	=	array();

		$id				=	$this->get( 'id' );

		if ( ! isset( $cache[$id] ) ) {
			$cache[$id]	=	( $this->get( 'to' ) ? CBTxt::T( $this->get( 'to' ) ) : null );
		}

		return $cache[$id];
	}

	public function getTitle( $length = 0 ) {
		global $_CB_framework;

		static $cache				=	array();

		$id							=	$this->get( 'id' );

		if ( ! isset( $cache[$id] ) ) {
			if ( $this->title ) {
				$substitutions		=	array(	'[owner]' => $this->getOwnerName( false ),
												'[owner_linked]' => $this->getOwnerName( true ),
												'[user]' => $this->getUserName( false ),
												'[user_linked]' => $this->getUserName( true ),
												'[sitename_linked]' => '<a href="' . $_CB_framework->getCfg( 'live_site' ) . '">' . $_CB_framework->getCfg( 'sitename' ) . '</a>'
											);

				$cbUser				=&	CBuser::getInstance( $this->get( 'user_id' ) );

				if ( ! $cbUser ) {
					$cbUser			=&	CBuser::getInstance( null );
				}

				$cache[$id]			=	$cbUser->replaceUserVars( CBTxt::P( $this->get( 'title' ), $substitutions ) );
			} else {
				$cache[$id]			=	null;
			}
		}

		$title						=	$cache[$id];

		if ( $title ) {
			$length					=	(int) $length;

			if ( $length && ( cbIsoUtf_strlen( strip_tags( $title ) ) > $length ) ) {
				$title				=	rtrim( trim( cbIsoUtf_substr( strip_tags( $title ), 0, $length ) ), '.' ) . '...';
			}
		}

		return $title;
	}

	public function getMessage( $length = 0 ) {
		global $_CB_framework;

		static $cache				=	array();

		$id							=	$this->get( 'id' );

		if ( ! isset( $cache[$id] ) ) {
			if ( $this->message ) {
				$substitutions		=	array(	'[owner]' => $this->getOwnerName( false ),
												'[owner_linked]' => $this->getOwnerName( true ),
												'[user]' => $this->getUserName( false ),
												'[user_linked]' => $this->getUserName( true ),
												'[sitename_linked]' => '<a href="' . $_CB_framework->getCfg( 'live_site' ) . '">' . $_CB_framework->getCfg( 'sitename' ) . '</a>'
											);

				$cbUser				=&	CBuser::getInstance( $this->get( 'user_id' ) );

				if ( ! $cbUser ) {
					$cbUser			=&	CBuser::getInstance( null );
				}

				$cache[$id]			=	$cbUser->replaceUserVars( CBTxt::P( $this->get( 'message' ), $substitutions ) );
			} else {
				$cache[$id]			=	null;
			}
		}

		$message					=	$cache[$id];

		if ( $message ) {
			$length					=	(int) $length;

			if ( $length && ( cbIsoUtf_strlen( strip_tags( $message ) ) > $length ) ) {
				$message			=	rtrim( trim( cbIsoUtf_substr( strip_tags( $message ), 0, $length ) ), '.' ) . '...';
			}
		}

		return $message;
	}

	public function getTimeAgo( $jquery = false ) {
		static $cache				=	array();

		$id							=	$this->get( 'id' ) . $jquery;

		if ( ! isset( $cache[$id] ) ) {
			$plugin					=	cbactivityClass::getPlugin();

			if ( $jquery && $plugin->params->get( 'date_ago', 1 ) && $plugin->params->get( 'date_jquery', 1 ) ) {
				$date				=	'<span class="activityTimeago" title="' . htmlspecialchars( cbactivityClass::getUTCDate( 'c', $this->get( 'date' ) ) ) . '"></span>';
			} else {
				$dateFormat			=	$plugin->params->get( 'date_format' );

				if ( ! $dateFormat ) {
					$dateFormat		=	'l, F j, Y \a\t g:ia';
				}

				$nowTimestamp		=	cbactivityClass::getUTCTimestamp( cbFormatDate( cbactivityClass::getUTCDate() ) );
				$dateTimestamp		=	cbactivityClass::getUTCTimestamp( cbFormatDate( $this->get( 'date' ) ) );
				$dateFormatted		=	cbactivityClass::getUTCDate( $dateFormat, $dateTimestamp );

				if ( $plugin->params->get( 'date_ago', 1 ) ) {
					$difference		=	( $nowTimestamp - $dateTimestamp );
					$minutesAgo		=	intval( $difference / 60 );
					$hoursAgo		=	intval( $difference / 3600 );

					if ( $difference < 60 ) {
						$ago		=	CBTxt::T( 'now' );
					} elseif ( $minutesAgo < 2 ) {
						$ago		=	CBTxt::T( 'about a minute ago' );
					} elseif ( $minutesAgo < 60 ) {
						$ago		=	CBTxt::P( '[minutes] minutes ago', array( '[minutes]' => $minutesAgo ) );
					} elseif ( $hoursAgo < 2 ) {
						$ago		=	CBTxt::T( 'about an hour ago' );
					} elseif ( $hoursAgo < 24 ) {
						$ago		=	CBTxt::P( '[hours] hours ago', array( '[hours]' => $hoursAgo ) );
					} else {
						$ago		=	null;
					}

					$date			=	( $ago ? '<span class="activityTimeago" title="' . htmlspecialchars( $dateFormatted ) . '">' . $ago . '</span>' : $dateFormatted );
				} else {
					$date			=	$dateFormatted;
				}
			}

			$cache[$id]			=	$date;
		}

		return $cache[$id];
	}

	public function getIcon() {
		static $cache	=	array();

		$id				=	$this->get( 'icon' );

		if ( ! isset( $cache[$id] ) ) {
			if ( $id ) {
				$icon	=	'<i class="activityIcon icon-' . htmlspecialchars( str_replace( array( '_', ' ' ), '-', $id ) ) . '"></i>';
			} else {
				$icon	=	null;
			}

			$cache[$id]	=	$icon;
		}

		return $cache[$id];
	}
}

class cbactivityData {

	static public function getActivity( $filtering = array(), $ordering = array(), $limits = 0, $list = true, $ignoreAccess = false ) {
		global $_CB_framework, $_CB_database;

		static $cache		=	array();

		if ( ! $filtering ) {
			$filtering		=	array();
		}

		if ( ! $ordering ) {
			$ordering		=	array();
		}

		$id					=	cbactivityClass::getStaticID( array( $filtering, $ordering ) );

		if ( ! isset( $cache[$id] ) ) {
			$where			=	array();
			$join			=	array();

			if ( $filtering ) {
				cbactivityData::where( $where, $join, $filtering, 'a' );
			}

			$orderby		=	array();

			if ( $ordering ) {
				cbactivityData::order( $orderby, $join, $ordering, 'a' );
			}

			$query			=	'SELECT a.*'
							.	"\n FROM " . $_CB_database->NameQuote( '#__comprofiler_plugin_activity' ) . " AS a";

			if ( count( $join ) ) {
				if ( in_array( 'b', $join ) ) {
					$query	.=	"\n LEFT JOIN " . $_CB_database->NameQuote( '#__comprofiler_members' ) . " AS b"
							.	' ON b.' . $_CB_database->NameQuote( 'memberid' ) . ' = a.' . $_CB_database->NameQuote( 'user_id' );
				}

				if ( in_array( 'c', $join ) ) {
					$query	.=	"\n LEFT JOIN " . $_CB_database->NameQuote( '#__comprofiler_members' ) . " AS c"
							.	' ON c.' . $_CB_database->NameQuote( 'referenceid' ) . ' = a.' . $_CB_database->NameQuote( 'user_id' );
				}

				if ( in_array( 'd', $join ) ) {
					$query	.=	"\n LEFT JOIN " . $_CB_database->NameQuote( '#__comprofiler_members' ) . " AS d"
							.	' ON d.' . $_CB_database->NameQuote( 'memberid' ) . ' = c.' . $_CB_database->NameQuote( 'user' );
				}

				if ( in_array( 'e', $join ) ) {
					$query	.=	"\n LEFT JOIN " . $_CB_database->NameQuote( '#__comprofiler_members' ) . " AS e"
							.	' ON e.' . $_CB_database->NameQuote( 'referenceid' ) . ' = a.' . $_CB_database->NameQuote( 'user' );
				}

				if ( in_array( 'f', $join ) ) {
					$query	.=	"\n LEFT JOIN " . $_CB_database->NameQuote( '#__users' ) . " AS f"
							.	' ON f.' . $_CB_database->NameQuote( 'id' ) . ' = a.' . $_CB_database->NameQuote( 'user_id' );
				}

				if ( in_array( 'g', $join ) ) {
					$query	.=	"\n LEFT JOIN " . $_CB_database->NameQuote( '#__users' ) . " AS g"
							.	' ON g.' . $_CB_database->NameQuote( 'id' ) . ' = a.' . $_CB_database->NameQuote( 'user' );
				}

				if ( in_array( 'h', $join ) ) {
					$query	.=	"\n LEFT JOIN " . $_CB_database->NameQuote( '#__comprofiler' ) . " AS h"
							.	' ON h.' . $_CB_database->NameQuote( 'user_id' ) . ' = a.' . $_CB_database->NameQuote( 'user_id' );
				}

				if ( in_array( 'i', $join ) ) {
					$query	.=	"\n LEFT JOIN " . $_CB_database->NameQuote( '#__comprofiler' ) . " AS i"
							.	' ON i.' . $_CB_database->NameQuote( 'user_id' ) . ' = a.' . $_CB_database->NameQuote( 'user' );
				}
			}

			$query			.=	( count( $where ) ? "\n WHERE " . implode( "\n AND ", $where ) : null )
							.	"\n ORDER BY " . ( count( $orderby ) ? implode( ', ', $orderby ) : "a." . $_CB_database->NameQuote( 'date' ) . " DESC" );
			$_CB_database->setQuery( $query );
			$cache[$id]		=	$_CB_database->loadObjectList( 'id', 'cbactivityActivity', array( &$_CB_database ) );
		}

		$rows				=	$cache[$id];

		if ( $rows ) {
			if ( ( $_CB_framework->getUi() == 1 ) && ( ! $ignoreAccess ) ) {
				cbactivityData::access( $rows );
			}

			if ( $limits ) {
				cbactivityData::limit( $rows, $limits );
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
				$rows		=	new cbactivityActivity( $_CB_database );
			}

			return $rows;
		}
	}

	static public function where( &$where, &$join, $filtering = array(), $defaultkey = null ) {
		global $_CB_database;

		if ( $filtering ) {
			if ( is_array( $filtering[0] ) ) {
				foreach ( $filtering as $filter ) {
					cbactivityData::where( $where, $join, $filter, $defaultkey );
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

						cbactivityData::where( $or, $join, $or_cases, $defaultkey );

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
					cbactivityData::order( $orderby, $join, $order, $defaultkey );
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

	static public function access( &$rows ) {
		global $_CB_database, $_CB_framework, $_PLUGINS, $ueConfig;

		$plugin														=	cbactivityClass::getPlugin();
		$myId														=	$_CB_framework->myId();
		$isModerator												=	isModerator( $myId );

		static $users												=	array();

		static $pbTabs												=	array(); // CB ProfileBook Tabs
		static $pbPosts												=	array(); // CB ProfileBook Posts

		static $pgTabs												=	array(); // CB ProfileGallery Tabs
		static $pgPosts												=	array(); // CB ProfileGallery Posts
		static $pgAccessMode										=	array(); // CB ProfileGallery Access Modes
		static $pgTabAccess											=	array(); // CB ProfileGallery Tab Access

		static $gjCategories										=	array(); // CB GroupJive Category Access
		static $gjGroups											=	array(); // CB GroupJive Group Access
		static $gjEvents											=	array(); // CB GroupJive Group Event Access
		static $gjFiles												=	array(); // CB GroupJive Group File Access
		static $gjPhotos											=	array(); // CB GroupJive Group Photo Access
		static $gjVideos											=	array(); // CB GroupJive Group Video Access
		static $gjPosts												=	array(); // CB GroupJive Group Wall Access

		static $kunenaMessages										=	array(); // Kunena Message Access

		static $blogs												=	array(); // CB Blogs Access

		$gjInstalled												=	file_exists( $_CB_framework->getCfg( 'absolute_path' ) . '/components/com_comprofiler/plugin/user/plug_cbgroupjive/cbgroupjive.class.php' );
		$blogsInstalled												=	file_exists( $_CB_framework->getCfg( 'absolute_path' ) . '/components/com_comprofiler/plugin/user/plug_cbblogs/cbblogs.class.php' );

		$gjCatLogging												=	( class_exists( 'cbgjData' ) && $plugin->params->get( 'activity_gj_cat_create', 1 ) ); // CB GroupJive Category Logging
		$gjGrpLogging												=	( class_exists( 'cbgjData' ) && ( $plugin->params->get( 'activity_gj_grp_create', 1 ) || $plugin->params->get( 'activity_gj_grp_join', 1 ) ) ); // CB GroupJive Group Logging
		$gjEventLogging												=	( class_exists( 'cbgjEventsData' ) && ( $plugin->params->get( 'activity_gj_events_create', 1 ) || $plugin->params->get( 'activity_gj_events_attend', 1 ) ) ); // CB GroupJive Group Event Logging
		$gjFileLogging												=	( class_exists( 'cbgjFileData' ) && $plugin->params->get( 'activity_gj_files_create', 1 ) ); // CB GroupJive Group File Logging
		$gjPhotoLogging												=	( class_exists( 'cbgjPhotoData' ) && $plugin->params->get( 'activity_gj_photos_create', 1 ) ); // CB GroupJive Group Photo Logging
		$gjVideoLogging												=	( class_exists( 'cbgjVideoData' ) && $plugin->params->get( 'activity_gj_videos_create', 1 ) ); // CB GroupJive Group Video Logging
		$gjWallLogging												=	( class_exists( 'cbgjWallData' ) && ( $plugin->params->get( 'activity_gj_wall_create', 1 ) || $plugin->params->get( 'activity_gj_wall_reply', 1 ) ) ); // CB GroupJive Group Wall Logging

		$kunenaMessagesLogging										=	( class_exists( 'KunenaForumMessage' ) && ( $plugin->params->get( 'activity_kunena_create', 1 ) || $plugin->params->get( 'activity_kunena_reply', 1 ) ) ); // Kunena Messages Logging

		$_PLUGINS->trigger( 'activity_onBeforeAccess', array( &$rows, $plugin ) );

		if ( ! empty( $rows ) ) foreach ( $rows as $id => $row ) {
			switch ( $row->get( 'type' ) ) {
				case 'profile':
					$userId											=	(int) $row->get( 'user_id' );

					if ( ! isset( $users[$userId] ) ) {
						$users[$userId]								=&	CBuser::getUserDataInstance( $userId );
					}

					$user											=	$users[$userId];

					if ( ( ! $user ) || $user->get( 'block' ) || ( ! $user->get( 'approved' ) ) || ( ! $user->get( 'confirmed' ) ) ) {
						unset( $rows[$id] );
					} else {
						switch ( $row->get( 'subtype' ) ) {
							case 'registration':
								if ( ! $plugin->params->get( 'activity_registration', 1 ) ) {
									unset( $rows[$id] );
								}
								break;
							case 'login':
								if ( ! $plugin->params->get( 'activity_login', 0 ) ) {
									unset( $rows[$id] );
								}
								break;
							case 'logout':
								if ( ! $plugin->params->get( 'activity_logout', 0 ) ) {
									unset( $rows[$id] );
								}
								break;
							case 'update':
								if ( ! $plugin->params->get( 'activity_profile', 1 ) ) {
									unset( $rows[$id] );
								}
								break;
							case 'avatar':
								if ( ( ! $isModerator ) && ( ! $user->get( 'avatarapproved' ) ) ) {
									unset( $rows[$id] );
								}
								break;
							case 'connection':
								if ( ! $plugin->params->get( 'activity_connections', 1 ) ) {
									unset( $rows[$id] );
								}
								break;
						}
					}
					break;
				case 'groupjive':
					if ( $gjInstalled ) {
						static $GJ_loaded							=	0;

						if ( ! $GJ_loaded++ ) {
							require_once( $_CB_framework->getCfg( 'absolute_path' ) . '/components/com_comprofiler/plugin/user/plug_cbgroupjive/cbgroupjive.class.php' );
						}

						$itemId										=	(int) $row->get( 'item' );

						switch ( $row->get( 'subtype' ) ) {
							case 'category':
								if ( $gjCatLogging ) {
									if ( ! isset( $gjCategories[$itemId] ) ) {
										$gjCategory					=	cbgjData::getCategories( array( 'cat_access' ), array( 'id', '=', $itemId ), null, null, false );

										$gjCategories[$itemId]		=	( $gjCategory->get( 'id' ) ? true : false );
									}

									if ( ! $gjCategories[$itemId] ) {
										unset( $rows[$id] );
									}
								} else {
									unset( $rows[$id] );
								}
								break;
							case 'group':
								if ( $gjGrpLogging ) {
									if ( ! isset( $gjGroups[$itemId] ) ) {
										$gjGroup					=	cbgjData::getGroups( array( 'grp_access' ), array( 'id', '=', $itemId ), null, null, false );

										$gjGroups[$itemId]			=	( $gjGroup->get( 'id' ) ? true : false );
									}

									if ( ! $gjGroups[$itemId] ) {
										unset( $rows[$id] );
									}
								} else {
									unset( $rows[$id] );
								}
								break;
							case 'event':
								if ( $gjEventLogging ) {
									if ( ! isset( $gjEvents[$itemId] ) ) {
										$gjEvent					=	cbgjEventsData::getEvents( array( array( 'grp_access', 'events_show' ), null, null, true ), array( 'id', '=', $itemId, 'published', '=', 1 ), null, null, false );

										$gjEvents[$itemId]			=	( $gjEvent->get( 'id' ) ? true : false );
									}

									if ( ! $gjEvents[$itemId] ) {
										unset( $rows[$id] );
									}
								} else {
									unset( $rows[$id] );
								}
								break;
							case 'file':
								if ( $gjFileLogging ) {
									if ( ! isset( $gjFiles[$itemId] ) ) {
										$gjFile						=	cbgjFileData::getFiles( array( array( 'grp_access', 'file_show' ), null, null, true ), array( 'id', '=', $itemId, 'published', '=', 1 ), null, null, false );

										$gjFiles[$itemId]			=	( $gjFile->get( 'id' ) ? true : false );
									}

									if ( ! $gjFiles[$itemId] ) {
										unset( $rows[$id] );
									}
								} else {
									unset( $rows[$id] );
								}
								break;
							case 'photo':
								if ( $gjPhotoLogging ) {
									if ( ! isset( $gjPhotos[$itemId] ) ) {
										$gjPhoto					=	cbgjPhotoData::getPhotos( array( array( 'grp_access', 'photo_show' ), null, null, true ), array( 'id', '=', $itemId, 'published', '=', 1 ), null, null, false );

										$gjPhotos[$itemId]			=	( $gjPhoto->get( 'id' ) ? true : false );
									}

									if ( ! $gjPhotos[$itemId] ) {
										unset( $rows[$id] );
									}
								} else {
									unset( $rows[$id] );
								}
								break;
							case 'video':
								if ( $gjVideoLogging ) {
									if ( ! isset( $gjVideos[$itemId] ) ) {
										$gjVideo					=	cbgjVideoData::getVideos( array( array( 'grp_access', 'video_show' ), null, null, true ), array( 'id', '=', $itemId, 'published', '=', 1 ), null, null, false );

										$gjVideos[$itemId]			=	( $gjVideo->get( 'id' ) ? true : false );
									}

									if ( ! $gjVideos[$itemId] ) {
										unset( $rows[$id] );
									}
								} else {
									unset( $rows[$id] );
								}
								break;
							case 'wall':
								if ( $gjWallLogging ) {
									if ( ! isset( $gjPosts[$itemId] ) ) {
										$gjPost						=	cbgjWallData::getPosts( array( array( 'grp_access', 'wall_show' ), null, null, true ), array( 'id', '=', $itemId, 'published', '=', 1 ), null, null, false );

										$gjPosts[$itemId]			=	( $gjPost->get( 'id' ) ? true : false );
									}

									if ( ! $gjPosts[$itemId] ) {
										unset( $rows[$id] );
									}
								} else {
									unset( $rows[$id] );
								}
								break;
						}
					} else {
						unset( $rows[$id] );
					}
					break;
				case 'kunena':
					if ( ! class_exists( 'KunenaForum' ) ) {
						unset( $rows[$id] );
					} else {
						switch ( $row->get( 'subtype' ) ) {
							case 'message':
								if ( $kunenaMessagesLogging ) {
									$msgId							=	(int) $row->get( 'item' );

									if ( ! isset( $kunenaMessages[$msgId] ) ) {
										$msg						=	KunenaForumMessage::getInstance( $msgId );

										$kunenaMessages[$msgId]		=	( $msg && $msg->authorise( 'read', $myId ) ? true : false );
									}

									if ( ! $kunenaMessages[$msgId] ) {
										unset( $rows[$id] );
									}
								} else {
									unset( $rows[$id] );
								}
								break;
						}
					}
					break;
				case 'profilebook':
					if ( ! class_exists( 'pbProfileBookEntry' ) ) {
						unset( $rows[$id] );
					} elseif ( ( $row->get( 'subtype' ) == 'guest' ) && ( ! $plugin->params->get( 'activity_pb_guest_create', 1 ) ) ) {
						unset( $rows[$id] );
					} elseif ( ( $row->get( 'subtype' ) == 'wall' ) && ( ! $plugin->params->get( 'activity_pb_wall_create', 1 ) ) ) {
						unset( $rows[$id] );
					} elseif ( ( $row->get( 'subtype' ) == 'blog' ) && ( ! $plugin->params->get( 'activity_pb_blog_create', 1 ) ) ) {
						unset( $rows[$id] );
					} else {
						$itemId										=	(int) $row->get( 'item' );

						if ( ! isset( $pbPosts[$itemId] ) ) {
							$query									=	'SELECT a.' . $_CB_database->NameQuote( 'userid' )
																	.	"\n FROM " . $_CB_database->NameQuote( '#__comprofiler_plug_profilebook' ) . " AS a"
																	.	"\n WHERE a." . $_CB_database->NameQuote( 'id' ) . " = " . $itemId
																	.	"\n AND a." . $_CB_database->NameQuote( 'published' ) . " = 1";
							$_CB_database->setQuery( $query );
							$pbPosts[$itemId]						=	(int) $_CB_database->loadResult();
						}

						$userId										=	$pbPosts[$itemId];

						if ( ! $userId ) {
							unset( $rows[$id] );
						} else {
							switch ( $row->get( 'subtype' ) ) {
								case 'guest':
									$tabClass						=	'getprofilebookTab';
									break;
								case 'wall':
									$tabClass						=	'getprofilebookwallTab';
									break;
								case 'blog':
									$tabClass						=	'getprofilebookblogTab';
									break;
								default:
									$tabClass						=	null;
									break;
							}

							if ( ! isset( $users[$userId] ) ) {
								$users[$userId]						=&	CBuser::getUserDataInstance( $userId );
							}

							$user									=	$users[$userId];
							$pbTabId								=	$tabClass . (int) $userId;

							if ( ! isset( $pbTabs[$pbTabId] ) ) {
								$pbTabs[$pbTabId]					=	cbactivityClass::getTabObject( $user, $tabClass );
							}

							$pbTab									=	$pbTabs[$pbTabId];

							if ( ! $pbTab ) {
								unset( $rows[$id] );
							}
						}
					}
					break;
				case 'profilegallery':
					if ( class_exists( 'getProfileGalleryTab' ) && $plugin->params->get( 'activity_pg_create', 1 ) ) {
						$itemId										=	(int) $row->get( 'item' );

						if ( ! isset( $pgPosts[$itemId] ) ) {
							$query									=	'SELECT ' . $_CB_database->NameQuote( 'userid' )
																	.	"\n FROM " . $_CB_database->NameQuote( '#__comprofiler_plug_profilegallery' )
																	.	"\n WHERE " . $_CB_database->NameQuote( 'id' ) . " = " . $itemId
																	.	"\n AND " . $_CB_database->NameQuote( 'pgitempublished' ) . " = 1"
																	.	"\n AND " . $_CB_database->NameQuote( 'pgitemapproved' ) . " = 1";
							$_CB_database->setQuery( $query );
							$pgPosts[$itemId]						=	(int) $_CB_database->loadResult();
						}

						$userId										=	$pgPosts[$itemId];

						if ( ! $userId ) {
							unset( $rows[$id] );
						} else {
							if ( ! isset( $users[$userId] ) ) {
								$users[$userId]						=&	CBuser::getUserDataInstance( $userId );
							}

							$itemUser								=	$users[$userId];

							if ( ! isset( $pgTabs[$userId] ) ) {
								$pgTabs[$userId]					=	cbactivityClass::getTabObject( $itemUser, 'getProfileGalleryTab' );
							}

							$pgTab									=	$pgTabs[$userId];

							if ( ! $pgTab ) {
								unset( $rows[$id] );
							} elseif ( ( $userId != $myId ) && ( ! $isModerator ) ) {
								if ( ! isset( $pgAccessMode[$userId] ) ) {
									$pgAccessMode[$userId]			=	( isset( $itemUser->cb_pgaccessmode ) && ( $itemUser->cb_pgaccessmode != '' ) ? $itemUser->cb_pgaccessmode : $pgTab->params->get( 'pgAccessMode', 'REG' ) );
								}

								$staticId							=	$myId . $userId;

								if ( ! isset( $pgTabAccess[$staticId] ) ) {
									$pgAccess						=	false;

									switch ( $pgAccessMode[$userId] ) {
										case 'PUB':
											$pgAccess				=	true;
											break;
										case 'REG':
										case 'REG-S':
											if ( $myId ) {
												$pgAccess			=	true;
											}
											break;
										case 'CON':
										case 'CON-S':
											if ( $ueConfig['allowConnections'] ) {
												$query				=	'SELECT COUNT(*)'
																	.	"\n FROM " . $_CB_database->NameQuote( '#__comprofiler_members' )
																	.	"\n WHERE " . $_CB_database->NameQuote( 'memberid' ) . " = " . (int) $userId
																	.	"\n AND " . $_CB_database->NameQuote( 'referenceid' ) . " = " . (int) $myId
																	.	"\n AND " . $_CB_database->NameQuote( 'accepted' ) . " = 1"
																	.	"\n AND " . $_CB_database->NameQuote( 'pending' ) . " = 0";
												$_CB_database->setQuery( $query );
												$isConnected		=	$_CB_database->loadResult();

												if ( $isConnected ) {
													$pgAccess		=	true;
												}
											}
											break;
									}

									$pgTabAccess[$staticId]			=	$pgAccess;
								}

								if ( ! $pgTabAccess[$staticId] ) {
									unset( $rows[$id] );
								}
							}
						}
					} else {
						unset( $rows[$id] );
					}
					break;
				case 'cbblogs':
					if ( $blogsInstalled && $plugin->params->get( 'activity_cbblogs_create', 1 ) ) {
						static $CBBLOGS_loaded						=	0;

						if ( ! $CBBLOGS_loaded++ ) {
							require_once( $_CB_framework->getCfg( 'absolute_path' ) . '/components/com_comprofiler/plugin/user/plug_cbblogs/cbblogs.class.php' );
						}

						if ( class_exists( 'cbblogsData' ) ) {
							$itemId									=	(int) $row->get( 'item' );

							if ( ! isset( $blogs[$itemId] ) ) {
								$blog								=	cbblogsData::getBlogs( array( 'blg_access' ), array( 'id', '=', $itemId ), null, null, false );

								$blogs[$itemId]						=	( $blog->get( 'id' ) ? true : false );
							}

							if ( ! $blogs[$itemId] ) {
								unset( $rows[$id] );
							}
						} else {
							unset( $rows[$id] );
						}
					} else {
						unset( $rows[$id] );
					}
					break;
			}
		}

		$_PLUGINS->trigger( 'activity_onAfterAccess', array( &$rows, $plugin ) );
	}
}

class cbactivityPaging {
	var $plugin		=	null;
	var $limit		=	null;
	var $limitstart	=	null;
	var $name		=	null;

	public function __construct( $name ) {
		$this->plugin	=	cbactivityClass::getPlugin();
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

    public function getInputSearch( $form, $filter, $title = '', $value = '', $size = 20, $class = 'input-large' ) {
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