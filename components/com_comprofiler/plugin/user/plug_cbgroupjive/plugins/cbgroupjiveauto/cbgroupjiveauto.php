<?php
if ( ! ( defined( '_VALID_CB' ) || defined( '_JEXEC' ) || defined( '_VALID_MOS' ) ) ) { die( 'Direct Access to this location is not allowed.' ); }

global $_CB_framework, $_PLUGINS;

require_once( $_CB_framework->getCfg( 'absolute_path' ) . '/components/com_comprofiler/plugin/user/plug_cbgroupjive/cbgroupjive.class.php' );

$_PLUGINS->registerUserTabParams();
$_PLUGINS->registerUserFieldParams();
$_PLUGINS->registerUserFieldTypes( array( 'cbgjautojoin' => 'cbgjautoField' ) );

$_PLUGINS->registerFunction( 'gj_onMenuBE', 'getMenuBE', 'cbgjautoAdmin' );
$_PLUGINS->registerFunction( 'gj_onToolbarBE', 'getToolbarBE', 'cbgjautoAdmin' );
$_PLUGINS->registerFunction( 'gj_onPluginBEToolbar', 'getPluginBEToolbar', 'cbgjautoAdmin' );
$_PLUGINS->registerFunction( 'gj_onPluginBE', 'getPluginBE', 'cbgjautoAdmin' );

class cbgjautoField extends cbFieldHandler {

	public function getFieldRow( &$field, &$user, $output, $formatting, $reason, $list_compare_types ) {
		if ( ( ! in_array( $reason, array( 'register', 'edit' ) ) ) || $user->id ) {
			return null;
		}

		return parent::getFieldRow( $field, $user, $output, $formatting, $reason, $list_compare_types );
	}

	public function getField( &$field, &$user, $output, $reason, $list_compare_types ) {
		if ( ( ! in_array( $reason, array( 'register', 'edit' ) ) ) || $user->id ) {
			return null;
		}

		if ( ( $output == 'htmledit' ) && ( $reason != 'search' ) && ( ! $user->id ) ) {
			$catsInclude				=	$field->params->get( 'cbgjautojoin_cats_include', null );
			$grpsInclude				=	$field->params->get( 'cbgjautojoin_grps_include', null );
			$catsExclude				=	$field->params->get( 'cbgjautojoin_cats_exclude', null );
			$grpsExclude				=	$field->params->get( 'cbgjautojoin_grps_exclude', null );
			$authorized					=	$field->params->get( 'cbgjautojoin_authorized', 0 );
			$catsWhere					=	array();
			$grpsWhere					=	array();

			if ( $field->params->get( 'cbgjautojoin_mode', 1 ) ) {
				if ( $catsExclude ) {
					$catsExclude		=	explode( '|*|', $catsExclude );

					cbArrayToInts( $catsExclude );

					$catsWhere[]		=	array( 'id', '!IN', implode( ',', $catsExclude ) );
				}

				if ( $grpsExclude ) {
					$grpsExclude		=	explode( '|*|', $grpsExclude );

					cbArrayToInts( $grpsExclude );

					$grpsWhere[]		=	array( 'id', '!IN', implode( ',', $grpsExclude ) );
				}
			} else {
				if ( $catsInclude ) {
					$catsInclude		=	explode( '|*|', $catsInclude );

					cbArrayToInts( $catsInclude );

					$catsWhere[]		=	array( 'id', 'IN', implode( ',', $catsInclude ) );
				}

				if ( $grpsInclude ) {
					$grpsInclude		=	explode( '|*|', $grpsInclude );

					cbArrayToInts( $grpsInclude );

					$grpsWhere[]		=	array( 'id', 'IN', implode( ',', $grpsInclude ) );
				}
			}

			$listGroups					=	array();

			$categories					=	cbgjData::getCategories( ( $authorized ? array( array( 'cat_access', 'mod_lvl1' ), $user ) : null ), $catsWhere );

			if ( $categories ) foreach ( $categories as $category ) {
				$catGrpsWhere			=	$grpsWhere;
				$catGrpsWhere[]			=	array( 'category', '=', $category->get( 'id' ) );

				$groups					=	cbgjData::listArray( cbgjData::getGroups( ( $authorized ? array( 'grp_join', $user ) : null ), $catGrpsWhere ) );

				if ( $groups ) {
					if ( $field->params->get( 'cbgjautojoin_display_cats', 1 ) ) {
						$listGroups[]	=	moscomprofilerHTML::makeOptGroup( $category->get( 'name' ) );
					}

					foreach ( $groups as $group ) {
						$listGroups[]	=	moscomprofilerHTML::makeOption( $group->value, $group->text );
					}
				}
			}

			return htmlspecialchars_decode( $this->_fieldEditToHtml( $field, $user, $reason, 'input', ( $field->params->get( 'cbgjautojoin_display', 1 ) ? 'multiselect' : 'select' ), '', null, $listGroups ), ENT_NOQUOTES );
		}

		return null;
	}

	public function prepareFieldDataSave( &$field, &$user, &$postdata, $reason ) {
		if ( ( ! in_array( $reason, array( 'register', 'edit' ) ) ) || $user->id ) {
			return true;
		}

		$this->_prepareFieldMetaSave( $field, $user, $postdata, $reason );

		$value	=	$this->getGroups( $field, $postdata );

		$this->validate( $field, $user, $field->name, $value, $postdata, $reason );
	}

	public function commitFieldDataSave( &$field, &$user, &$postdata, $reason ) {
		$user_id			=	(int) cbGetParam( $postdata, 'id' );

		if ( ( ! in_array( $reason, array( 'register', 'edit' ) ) ) || $user_id ) {
			return;
		}

		$value				=	$this->getGroups( $field, $postdata );

		if ( $value && $this->validate( $field, $user, $field->name, $value, $postdata, $reason ) ) {
			$groups			=	explode( '|*|', $value );

			if ( $groups ) foreach ( $groups as $group_id ) {
				$group		=	cbgjData::getGroups( ( $field->params->get( 'cbgjautojoin_authorized', 0 ) ? array( array( 'grp_join' ), $user ) : null ), array( 'id', '=', (int) $group_id ), null, null, false );

				if ( $group->get( 'id' ) ) {
					$row	=	cbgjData::getUsers( null, array( array( 'group', '=', (int) $group->get( 'id' ) ), array( 'user_id', '=', (int) $user->id ) ), null, null, false );

					if ( ! $row->get( 'id' ) ) {
						$row->set( 'user_id', (int) $user->id );
						$row->set( 'group', (int) $group->get( 'id' ) );
						$row->set( 'date', cbgjClass::getUTCDate() );
						$row->set( 'status', (int) $field->params->get( 'cbgjautojoin_status', 1 ) );

						if ( $row->store() ) {
							if ( $row->get( 'status' ) == 4 ) {
								$group->storeOwner( $row->get( 'user_id' ) );
							}
						}
					}
				}
			}
		}
	}

	public function rollbackFieldDataSave( &$field, &$user, &$postdata, $reason ) {
		$user_id			=	(int) cbGetParam( $postdata, 'id' );

		if ( ( ! in_array( $reason, array( 'register', 'edit' ) ) ) || $user_id ) {
			return;
		}

		$value				=	$this->getGroups( $field, $postdata );

		if ( $value ) {
			$groups			=	explode( '|*|', $value );

			if ( $groups ) foreach ( $groups as $group_id ) {
				$group		=	cbgjData::getGroups( null, array( 'id', '=', (int) $group_id ), null, null, false );

				if ( $group->get( 'id' ) ) {
					$row	=	cbgjData::getUsers( null, array( array( 'group', '=', (int) $group->get( 'id' ) ), array( 'user_id', '=', (int) $user->id ) ), null, null, false );

					if ( $row->get( 'id' ) ) {
						$row->deleteAll();
					}
				}
			}
		}
	}

	private function getGroups( $field, $postdata ) {
		$value			=	cbGetParam( $postdata, $field->name, null, _CB_ALLOWRAW );

		if ( is_array( $value ) ) {
			if ( $value ) foreach ( $value as $k => $v ) {
				if ( ( $v === null ) || ( $v === '' ) ) {
					unset( $value[$k] );
				}
			}

			if ( count( $value ) > 0 ) {
				cbArrayToInts( $value );

				$value	=	$this->_implodeCBvalues( $value );
			} else {
				$value	=	'';
			}
		} elseif ( ( $value === null ) || ( $value === '' ) ) {
			$value		=	'';
		} else {
			$value		=	(int) $value;
		}

		return $value;
	}

	public function loadCategoriesList( $name, $value, $controlName ) {
		$categories		=	cbgjData::listArray( cbgjData::getCategories() );

		array_unshift( $categories, moscomprofilerHTML::makeOption( '', CBTxt::T( '- Select Categories -' ) ) );

		if ( isset( $value ) ) {
			$valAsObj	=	array_map( create_function( '$v', '$o=new stdClass(); $o->value=$v; return $o;' ), explode( '|*|', $value ) );
		} else {
			$valAsObj	=	null;
		}

		return moscomprofilerHTML::selectList( $categories, ( $controlName ? $controlName .'['. $name .'][]' : $name ), 'multiple="multiple" size="8"', 'value', 'text', $valAsObj, 0, false, false );
	}

	public function loadGroupsList( $name, $value, $controlName ) {
		$listGroups				=	array();

		$categories					=	cbgjData::getCategories();

		if ( $categories ) foreach ( $categories as $category ) {
			$groups					=	cbgjData::listArray( cbgjData::getGroups( null, array( 'category', '=', $category->id ) ) );

			if ( $groups ) {
				$listGroups[]		=	moscomprofilerHTML::makeOptGroup( $category->name );

				foreach ( $groups as $group ) {
					$listGroups[]	=	moscomprofilerHTML::makeOption( $group->value, $group->text );
				}
			}
		}

		array_unshift( $listGroups, moscomprofilerHTML::makeOption( '', CBTxt::T( '- Select Groups -' ) ) );

		if ( isset( $value ) ) {
			$valAsObj				=	array_map( create_function( '$v', '$o=new stdClass(); $o->value=$v; return $o;' ), explode( '|*|', $value ) );
		} else {
			$valAsObj				=	null;
		}

		return moscomprofilerHTML::selectList( $listGroups, ( $controlName ? $controlName .'['. $name .'][]' : $name ), 'multiple="multiple" size="8"', 'value', 'text', $valAsObj, 0, false, false );
	}
}

class cbgjautoPlugin extends cbPluginHandler {

	public function getTrigger( $trigger_id, $var1, $var2, $var3, $var4, $var5, $var6, $var7, $var8, $var9, $var10 ) {
		global $_CB_framework;

		$trigger					=	cbgjAutoData::getAutos( null, array( 'id', '=', (int) $trigger_id ), null, null, false );

		if ( $trigger->get( 'id' ) ) {
			if ( $trigger->get( 'object' ) == 3 ) {
				$user				=	$this->prepareUser( (int) $trigger->get( 'variable' ) );
			} elseif ( $trigger->get( 'object' ) == 2 ) {
				$user				=	$this->prepareUser( (int) $_CB_framework->myId() );
			} elseif ( $trigger->get( 'object' ) == 1 ) {
				$user				=	$this->prepareUser( ${ 'var' . (int) $trigger->get( 'variable' ) } );
			} else {
				$user				=	$this->getUser( $var1, $var2, $var3, $var4, $var5, $var6, $var7, $var8, $var9, $var10 );
			}

			if ( $user->id ) {
				$rawPassword		=	cbgjClass::getHTMLCleanParam( 'passwd', null );

				if ( ! $rawPassword ) {
					$rawPassword	=	cbgjClass::getHTMLCleanParam( 'password', null );
				}
			} else {
				$rawPassword		=	null;
			}

			$extras					=	$this->getExtras( $var1, $var2, $var3, $var4, $var5, $var6, $var7, $var8, $var9, $var10 );

			$this->getAuto( $user, $rawPassword, $extras, $trigger );
		}
	}

	private function getUser( $var1, $var2, $var3, $var4, $var5, $var6, $var7, $var8, $var9, $var10 ) {
		for ( $i = 1; $i <= 10; $i++ ) {
			$user	=	$this->prepareUser( ${ 'var' . $i } );

			if ( $user instanceof moscomprofilerUser ) {
				return $user;
			}
		}

		return $user;
	}

	private function getExtras( $var1, $var2, $var3, $var4, $var5, $var6, $var7, $var8, $var9, $var10 ) {
		$extras									=	array();

		for ( $i = 1; $i <= 10; $i++ ) {
			$extra								=	${ 'var' . $i };

			if ( is_object( $extra ) || is_array( $extra ) ) {
				$params							=	cbgjClass::parseParams( $extra, true );
				$paramsArray					=	$params->toParamsArray();
				$extra							=	array();

				if ( $paramsArray ) foreach ( $paramsArray as $k => $v ) {
					$k							=	'_' . ltrim( str_replace( ' ', '_', trim( strtolower( $k ) ) ), '_' );
					$extras['var' . $i . $k]	=	$v;
				}

				if ( $extra ) {
					$extras						=	array_merge( $extras, $extra );
				}
			} else {
				$extras['var' . $i]				=	$extra;
			}
		}

		if ( $_POST ) {
			$post								=	cbgjClass::parseParams( $_POST, true );
			$postArray							=	$post->toParamsArray();
			$extra								=	array();

			if ( $postArray ) foreach ( $postArray as $k => $v ) {
				$k								=	'_' . ltrim( str_replace( ' ', '_', trim( strtolower( $k ) ) ), '_' );
				$extras['post' . $k]			=	$v;
			}

			if ( $extra ) {
				$extras							=	array_merge( $extras, $extra );
			}
		}

		if ( $_GET ) {
			$get								=	cbgjClass::parseParams( $_GET, true );
			$getArray							=	$get->toParamsArray();
			$extra								=	array();

			if ( $getArray ) foreach ( $getArray as $k => $v ) {
				$k								=	'_' . ltrim( str_replace( ' ', '_', trim( strtolower( $k ) ) ), '_' );
				$extras['get' . $k]				=	$v;
			}

			if ( $extra ) {
				$extras							=	array_merge( $extras, $extra );
			}
		}

		return $extras;
	}

	private function prepareUser( $user_var ) {
		if ( is_object( $user_var ) ) {
			if ( $user_var instanceof moscomprofilerUser ) {
				$user		=	$user_var;
			} elseif ( isset( $user_var->id ) ) {
				$user_id	=	(int) $user_var->id;
			} elseif ( isset( $user_var->user_id ) ) {
				$user_id	=	(int) $user_var->user_id;
			} elseif ( isset( $user_var->user ) ) {
				$user_id	=	(int) $user_var->user;
			}
		} elseif ( is_integer( $user_var ) ) {
			$user_id		=	$user_var;
		}

		if ( isset( $user_id ) && is_integer( $user_id ) ) {
			$user			=&	CBuser::getUserDataInstance( (int) $user_id );
		}

		if ( ! isset( $user ) ) {
			$user			=&	CBuser::getUserDataInstance( null );
		}

		return $user;
	}

	private function getAuto( $user, $rawPassword, $extras, $trigger ) {
		$cbUser									=	new CBuser();

		$cbUser->load( (int) $user->id );

		if ( ( ! isset( $user->gids ) ) || ( isset( $user->gids ) && ( ! is_array( $user->gids ) ) ) ) {
			$gids								=	array( $user->gid );
		} else {
			$gids								=	$user->gids;
		}

		if ( $user->id ) {
			array_unshift( $gids, -3 );

			if ( isModerator( $user->id ) ) {
				array_unshift( $gids, -5 );
			} else {
				array_unshift( $gids, -4 );
			}
		} else {
			array_unshift( $gids, -2 );
		}

		array_unshift( $gids, -1 );

		$access									=	explode( '|*|', $trigger->get( 'access' ) );

		if ( ! array_intersect( $access, $gids ) ) {
			return;
		}

		$conditionals							=	count( explode( "\n", $trigger->get( 'field' ) ) );

		for ( $i = 0, $n = $conditionals; $i < $n; $i++ ) {
			if ( ! cbgjautoClass::getFieldMatch( $user, $cbUser, $extras, cbgjClass::getHTMLCleanParam( true, "field$i", null, null, $trigger->get( 'field' ) ), cbgjClass::getCleanParam( true, "operator$i", null, null, $trigger->get( 'operator' ) ), cbgjClass::getHTMLCleanParam( true, "value$i", null, null, $trigger->get( 'value' ) ) ) ) {
				return;
			}
		}

		$plugin									=	cbgjClass::getPlugin();
		$exclude								=	$trigger->get( 'exclude' );

		if ( $exclude ) {
			cbArrayToInts( explode( ',', $exclude ) );

			$exclude							=	array_unique( $exclude );

			if ( in_array( $user->id, $exclude ) ) {
				return;
			}
		}

		$params									=	$trigger->getParams();

		if ( ( $params->get( 'auto', null ) == 3 ) && $params->get( 'cat_name', null ) ) {
			$owner								=	(int) $cbUser->replaceUserVars( $params->get( 'cat_owner', $user->id ), true, true, $extras );

			if ( ! $owner ) {
				$owner							=	(int) $user->id;
			}

			$parent								=	(int) $params->get( 'cat_parent', 0 );
			$name								=	trim( strip_tags( $cbUser->replaceUserVars( $params->get( 'cat_name', null ), true, true, $extras ) ) );
			$description						=	$cbUser->replaceUserVars( $params->get( 'cat_description', null ), true, true, $extras );

			if ( $params->get( 'cat_unique', 1 ) ) {
				$where							=	array( array( 'user_id', '=', $owner ), array( 'name', '=', $name ), array( 'parent', '=', (int) $parent ) );
			} else {
				$where							=	array( array( 'name', '=', $name ), array( 'parent', '=', (int) $parent ) );
			}

			$row								=	cbgjData::getCategories( null, $where, null, null, false );

			if ( ! $row->get( 'id' ) ) {
				$row->set( 'published', 1 );
				$row->set( 'parent', (int) $parent );
				$row->set( 'user_id', $owner );
				$row->set( 'name', $name );

				if ( $plugin->params->get( 'category_editor', 1 ) >= 2 ) {
					$row->set( 'description', cbgjClass::getFilteredText( $description ) );
				} else {
					$row->set( 'description', trim( strip_tags( $description ) ) );
				}

				$row->set( 'access', (int) $plugin->params->get( 'category_access_default', -2 ) );
				$row->set( 'types', $params->get( 'types', $plugin->params->get( 'category_types_default', '1|*|2|*|3' ) ) );
				$row->set( 'create', (int) $plugin->params->get( 'category_create_default', 1 ) );
				$row->set( 'create_access', (int) $plugin->params->get( 'category_createaccess_default', -1 ) );
				$row->set( 'nested', (int) $plugin->params->get( 'category_nested_default', 1 ) );
				$row->set( 'nested_access', (int) $plugin->params->get( 'category_nestedaccess_default', -1 ) );
				$row->set( 'date', cbgjClass::getUTCDate() );
				$row->set( 'ordering', 99999 );

				$row->store();
			}
		} elseif ( ( $params->get( 'auto', null ) == 2 ) && $params->get( 'category', null ) && $params->get( 'grp_name', null ) ) {
			if ( ( $params->get( 'category', null ) == -1 ) && $params->get( 'cat_name', null ) ) {
				$owner							=	(int) $cbUser->replaceUserVars( $params->get( 'cat_owner', $user->id ), true, true, $extras );

				if ( ! $owner ) {
					$owner						=	(int) $user->id;
				}

				$parent							=	(int) $params->get( 'cat_parent', 0 );
				$name							=	trim( strip_tags( $cbUser->replaceUserVars( $params->get( 'cat_name', null ), true, true, $extras ) ) );
				$description					=	$cbUser->replaceUserVars( $params->get( 'cat_description', null ), true, true, $extras );

				if ( $params->get( 'cat_unique', 1 ) ) {
					$where						=	array( array( 'user_id', '=', $owner ), array( 'name', '=', $name ), array( 'parent', '=', (int) $parent ) );
				} else {
					$where						=	array( array( 'name', '=', $name ), array( 'parent', '=', (int) $parent ) );
				}

				$category						=	cbgjData::getCategories( null, $where, null, null, false );

				if ( ! $category->get( 'id' ) ) {
					$category->set( 'published', 1 );
					$category->set( 'parent', (int) $parent );
					$category->set( 'user_id', $owner );
					$category->set( 'name', $name );

					if ( $plugin->params->get( 'category_editor', 1 ) >= 2 ) {
						$category->set( 'description', cbgjClass::getFilteredText( $description ) );
					} else {
						$category->set( 'description', trim( strip_tags( $description ) ) );
					}

					$category->set( 'access', (int) $plugin->params->get( 'category_access_default', -2 ) );
					$category->set( 'types', $params->get( 'types', $plugin->params->get( 'category_types_default', '1|*|2|*|3' ) ) );
					$category->set( 'create', (int) $plugin->params->get( 'category_create_default', 1 ) );
					$category->set( 'create_access', (int) $plugin->params->get( 'category_createaccess_default', -1 ) );
					$category->set( 'nested', (int) $plugin->params->get( 'category_nested_default', 1 ) );
					$category->set( 'nested_access', (int) $plugin->params->get( 'category_nestedaccess_default', -1 ) );
					$category->set( 'date', cbgjClass::getUTCDate() );
					$category->set( 'ordering', 99999 );

					$category->store();
				}
			} else {
				$category						=	cbgjData::getCategories( null, array( 'id', '=', (int) $params->get( 'category', null ) ), null, null, false );
			}

			if ( $category->get( 'id' ) ) {
				$owner							=	(int) $cbUser->replaceUserVars( $params->get( 'grp_owner', $user->id ), true, true, $extras );

				if ( ! $owner ) {
					$owner						=	(int) $user->id;
				}

				$parent							=	(int) $params->get( 'grp_parent', 0 );
				$name							=	trim( strip_tags( $cbUser->replaceUserVars( $params->get( 'grp_name', null ), true, true, $extras ) ) );
				$description					=	$cbUser->replaceUserVars( $params->get( 'grp_description', null ), true, true, $extras );
				$join							=	false;

				if ( $params->get( 'grp_unique', 1 ) ) {
					$where						=	array( array( 'category', '=', (int) $category->get( 'id' ) ), array( 'user_id', '=', $owner ), array( 'name', '=', $name ), array( 'parent', '=', (int) $parent ) );
				} else {
					$where						=	array( array( 'category', '=', (int) $category->get( 'id' ) ), array( 'name', '=', $name ), array( 'parent', '=', (int) $parent ) );

					if ( $params->get( 'grp_autojoin', 1 ) ) {
						$join					=	true;
					}
				}

				$row							=	cbgjData::getGroups( null, $where, null, null, false );

				if ( ! $row->get( 'id' ) ) {
					$row->set( 'published', 1 );
					$row->set( 'category', (int) $category->get( 'id' ) );
					$row->set( 'parent', (int) $parent );
					$row->set( 'user_id', $owner );
					$row->set( 'name', $name );

					if ( $plugin->params->get( 'group_editor', 1 ) >= 2 ) {
						$row->set( 'description', cbgjClass::getFilteredText( $description ) );
					} else {
						$row->set( 'description', trim( strip_tags( $description ) ) );
					}

					$row->set( 'access', (int) $plugin->params->get( 'group_access_default', -2 ) );
					$row->set( 'type', (int) $params->get( 'type', $plugin->params->get( 'group_type_default', 1 ) ) );
					$row->set( 'nested', (int) $plugin->params->get( 'group_nested_default', 1 ) );
					$row->set( 'nested_access', (int) $plugin->params->get( 'group_nestedaccess_default', -1 ) );
					$row->set( 'date', cbgjClass::getUTCDate() );
					$row->set( 'ordering', 1 );

					if ( $row->store() ) {
						$row->storeOwner( $row->get( 'user_id' ) );

						if ( $row->get( 'user_id' ) !=  $user->id ) {
							$usr				=	cbgjData::getUsers( null, array( array( 'group', '=', (int) $row->get( 'id' ) ), array( 'user_id', '=', (int) $user->id ) ), null, null, false );

							if ( ! $usr->get( 'id' ) ) {
								$usr->set( 'user_id', (int) $user->id );
								$usr->set( 'group', (int) $row->get( 'id' ) );
								$usr->set( 'date', cbgjClass::getUTCDate() );
								$usr->set( 'status', 1 );
								$usr->store();
							}
						}
					}
				} elseif ( $join ) {
					$usr						=	cbgjData::getUsers( null, array( array( 'group', '=', (int) $row->get( 'id' ) ), array( 'user_id', '=', (int) $user->id ) ), null, null, false );

					if ( ! $usr->get( 'id' ) ) {
						$usr->set( 'user_id', (int) $user->id );
						$usr->set( 'group', (int) $row->get( 'id' ) );
						$usr->set( 'date', cbgjClass::getUTCDate() );
						$usr->set( 'status', (int) $params->get( 'status', 1 ) );

						if ( $usr->store() ) {
							if ( $usr->get( 'status' ) == 4 ) {
								$row->storeOwner( $usr->get( 'user_id' ) );
							}
						}
					}
				}
			}
		} elseif ( ( $params->get( 'auto', null ) == 1 ) && $params->get( 'groups', null ) ) {
			$groups								=	$params->get( 'groups', null );

			if ( $groups ) {
				$groups							=	explode( '|*|', $groups );

				cbArrayToInts( $groups );
			}

			if ( $groups ) foreach ( $groups as $groupId ) {
				$group							=	cbgjData::getGroups( null, array( 'id', '=', (int) $groupId ), null, null, false );

				if ( $group->get( 'id' ) ) {
					$row						=	cbgjData::getUsers( null, array( array( 'group', '=', (int) $group->get( 'id' ) ), array( 'user_id', '=', (int) $user->id ) ), null, null, false );

					if ( ! $row->get( 'id' ) ) {
						$row->set( 'user_id', (int) $user->id );
						$row->set( 'group', (int) $group->get( 'id' ) );
						$row->set( 'date', cbgjClass::getUTCDate() );
						$row->set( 'status', (int) $params->get( 'status', 1 ) );

						if ( $row->store() ) {
							if ( $row->get( 'status' ) == 4 ) {
								$group->storeOwner( $row->get( 'user_id' ) );
							}
						}
					}
				}
			}
		} elseif ( ( $params->get( 'auto', null ) == 4 ) && $params->get( 'groups', null ) ) {
			$groups								=	$params->get( 'groups', null );

			if ( $groups ) {
				$groups							=	explode( '|*|', $groups );

				cbArrayToInts( $groups );
			}

			if ( $groups ) foreach ( $groups as $groupId ) {
				$group							=	cbgjData::getGroups( null, array( 'id', '=', (int) $groupId ), null, null, false );

				if ( $group->get( 'id' ) ) {
					$row						=	cbgjData::getUsers( null, array( array( 'group', '=', (int) $group->get( 'id' ) ), array( 'user_id', '=', (int) $user->id ) ), null, null, false );

					if ( $row->get( 'id' ) && ( $row->get( 'status' ) != 4 ) ) {
						$row->deleteAll();
					}
				}
			}
		}
	}
}

class cbgjautoAdmin extends cbPluginHandler {

	public function getMenuBE( $user, $plugin ) {
		$menu	=	'<a href="' . cbgjClass::getPluginURL( array( 'plugin', 'auto' ) ) . '">'
				.		'<div><img src="' . $plugin->livePath . '/plugins/cbgroupjiveauto/images/icon-128-auto.png" /></div>'
				.		'<div>' . CBTxt::Th( 'Auto' ) . '</div>'
				.	'</a>';

		return $menu;
	}

	public function getToolbarBE( $function, $user, $plugin ) {
		global $_CB_framework;

		if ( ! strstr( $function, 'auto' ) ) {
			$_CB_framework->document->addHeadStyleSheet( $plugin->livePath . '/plugins/cbgroupjiveauto/cbgroupjiveauto.css' );

			CBtoolmenuBar::linkAction( 'gjauto', cbgjClass::getPluginURL( array( 'plugin', 'auto' ) ), CBTxt::T( 'Auto' ) );
		}
	}

	public function getPluginBEToolbar( $function, $user, $plugin ) {
		if ( strstr( $function, 'auto' ) ) {
			switch ( $function ) {
				case 'auto_new':
				case 'auto_edit':
				case 'auto_save':
					CBtoolmenuBar::startTable();
					CBtoolmenuBar::save( 'savePlugin', CBTxt::T( 'Save' ) );
					CBtoolmenuBar::apply( 'applyPlugin', CBTxt::T( 'Apply' ) );
					CBtoolmenuBar::linkAction( 'cancel', cbgjClass::getPluginURL( array( 'plugin', 'auto' ) ), CBTxt::T( 'Cancel' ) );
					CBtoolmenuBar::endTable();
					break;
				case 'auto_show':
				case 'auto':
				default:
					CBtoolmenuBar::startTable();
					cbgjMenu::getDefaults( 'plugin', $function, $user, $plugin );
					CBtoolmenuBar::spacer( '50px' );
					CBtoolmenuBar::linkAction( 'publish', "javascript:cbDoListTask( this, 'editPlugin', 'action', 'plugin.auto_publish', 'id' )", CBTxt::T( 'Publish' ) );
					CBtoolmenuBar::linkAction( 'unpublish', "javascript:cbDoListTask( this, 'editPlugin', 'action', 'plugin.auto_unpublish', 'id' )", CBTxt::T( 'Unpublish' ) );
					CBtoolmenuBar::linkAction( 'copy', "javascript:cbDoListTask( this, 'editPlugin', 'action', 'plugin.auto_copy', 'id' )", CBTxt::T( 'Copy' ) );
					CBtoolmenuBar::linkAction( 'delete', "javascript:cbDoListTask( this, 'editPlugin', 'action', 'plugin.auto_delete', 'id' )", CBTxt::T( 'Delete' ) );
					CBtoolmenuBar::linkAction( 'edit', "javascript:cbDoListTask( this, 'editPlugin', 'action', 'plugin.auto_edit', 'id' )", CBTxt::T( 'Edit' ) );
					CBtoolmenuBar::linkAction( 'new', cbgjClass::getPluginURL( array( 'plugin', 'auto_new' ) ), CBTxt::T( 'New' ) );
					CBtoolmenuBar::back( CBTxt::T( 'Back' ), cbgjClass::getPluginURL() );
					CBtoolmenuBar::endTable();
					break;
			}
		}
	}

	public function getPluginBE( $params, $user, $plugin ) {
		global $_CB_framework;

		if ( strstr( $params[0], 'auto' ) ) {
			$_CB_framework->document->addHeadStyleSheet( $plugin->livePath . '/plugins/cbgroupjiveauto/cbgroupjiveauto.css' );

			switch ( $params[0] ) {
				case 'auto_publish':
					cbSpoofCheck( 'plugin' );
					$this->stateAuto( $params[1], 1, $user, $plugin );
					break;
				case 'auto_unpublish':
					cbSpoofCheck( 'plugin' );
					$this->stateAuto( $params[1], 0, $user, $plugin );
					break;
				case 'auto_order':
					cbSpoofCheck( 'plugin' );
					$this->orderAuto( $params[1], $params[2], $user, $plugin );
					break;
				case 'auto_orderup':
					cbSpoofCheck( 'plugin' );
					$this->orderAuto( $params[1][0], -1, $user, $plugin );
					break;
				case 'auto_orderdown':
					cbSpoofCheck( 'plugin' );
					$this->orderAuto( $params[1][0], 1, $user, $plugin );
					break;
				case 'auto_copy':
					cbSpoofCheck( 'plugin' );
					$this->copyAuto( $params[1], $user, $plugin );
					break;
				case 'auto_delete':
					cbSpoofCheck( 'plugin' );
					$this->deleteAuto( $params[1], $user, $plugin );
					break;
				case 'auto_new':
					$this->getAutoEdit( null, $user, $plugin );
					break;
				case 'auto_edit':
					$this->getAutoEdit( $params[1][0], $user, $plugin );
					break;
				case 'auto_save':
				case 'auto_apply':
					cbSpoofCheck( 'plugin' );
					$this->saveAutoEdit( $params[1][0], $params[3], $user, $plugin );
					break;
				case 'auto_show':
				case 'auto':
				default:
					$this->getAuto( $user, $plugin );
					break;
			}
		}
	}

	public function getAuto( $user, $plugin ) {
		global $_CB_framework;

		cbgjClass::getTemplate( 'cbgroupjiveauto' );

		$paging				=	new cbgjPaging( 'auto' );

		$limit				=	$paging->getlimit( 30 );
		$limitstart			=	$paging->getLimistart();
		$search				=	$paging->getFilter( 'trigger' );
		$access				=	$paging->getFilter( 'access' );
		$state				=	$paging->getFilter( 'state' );
		$id					=	$paging->getFilter( 'id' );
		$where				=	array();

		if ( isset( $search ) && ( $search != '' ) ) {
			$where[]		=	array( 'trigger', 'CONTAINS', $search );
		}

		if ( isset( $access ) && ( $access != '' ) ) {
			$where[]		=	array( 'access', '=', (int) $access );
		}

		if ( isset( $state ) && ( $state != '' ) ) {
			$where[]		=	array( 'published', '=', (int) $state );
		}

		if ( isset( $id ) && ( $id != '' ) ) {
			$where[]		=	array( 'id', '=', (int) $id );
		}

		$searching			=	( count( $where ) ? true : false );

		$total				=	count( cbgjAutoData::getAutos( null, $where ) );

		if ( $total <= $limitstart ) {
			$limitstart		=	0;
		}

		$pageNav			=	$paging->getPageNav( $total, $limitstart, $limit );

		$rows				=	array_values( cbgjAutoData::getAutos( null, $where, null, array( $pageNav->limitstart, $pageNav->limit ) ) );

		$input				=	array();

		$input['trigger']	=	$paging->getInputText( 'adminForm', 'trigger', $search, '30' );

		$listAccess			=	array();
		$listAccess[]		=	moscomprofilerHTML::makeOption( '', CBTxt::T( '- Select Access -' ) );
		$listAccess[]		=	moscomprofilerHTML::makeOptGroup( CBTxt::T( 'Custom ACL' ) );
		$listAccess[]		=	moscomprofilerHTML::makeOption( '-1', CBTxt::T( 'Everybody' ) );
		$listAccess[]		=	moscomprofilerHTML::makeOption( '-2', CBTxt::T( 'All Non-Registered Users' ) );
		$listAccess[]		=	moscomprofilerHTML::makeOption( '-3', CBTxt::T( 'All Registered Users' ) );
		$listAccess[]		=	moscomprofilerHTML::makeOption( '-4', CBTxt::T( 'All Non-Moderators' ) );
		$listAccess[]		=	moscomprofilerHTML::makeOption( '-5', CBTxt::T( 'All Moderators' ) );
		$listAccess[]		=	moscomprofilerHTML::makeOptGroup( CBTxt::T( 'CMS ACL' ) );
		$listAccess			=	array_merge( $listAccess, $_CB_framework->acl->get_group_children_tree( null, 'USERS', false ) );
		$input['access']	=	$paging->getInputSelect( 'adminForm', 'access', $listAccess, $access );

		$listState			=	array();
		$listState[]		=	moscomprofilerHTML::makeOption( '', CBTxt::T( '- Select State -' ) );
		$listState[]		=	moscomprofilerHTML::makeOption( '1', CBTxt::T( 'Published' ) );
		$listState[]		=	moscomprofilerHTML::makeOption( '0', CBTxt::T( 'Unpublished' ) );
		$input['state']		=	$paging->getInputSelect( 'adminForm', 'state', $listState, $state );

		$input['id']		=	$paging->getInputText( 'adminForm', 'id', $id, '6' );

		$pageNav->searching	=	$searching;

		if ( class_exists( 'HTML_cbgroupjiveauto' ) ) {
			HTML_cbgroupjiveauto::showAuto( $rows, $pageNav, $input, $user, $plugin );
		} else {
			$this->showAuto( $rows, $pageNav, $input, $user, $plugin );
		}
	}

	private function showAuto( $rows, $pageNav, $input, $user, $plugin ) {
		global $_CB_framework;

		HTML_cbgjAdmin::setTitle( CBTxt::T( 'Auto' ), 'cbicon-48-gjauto' );

		$imgPath				=	'../components/' . $plugin->option . '/images/';
		$toggleJs				=	"cbToggleAll( this, " . count( $rows ) . ", 'id' );";
		$orderJs				=	"cbsaveorder( this, " . count( $rows ) . ", 'id', 'editPlugin', 'action', 'plugin.auto_order' );";
		$oneOrTwo				=	0;

		$return					=	'<form action="' . $_CB_framework->backendUrl( 'index.php' ) . '" method="post" name="adminForm" enctype="multipart/form-data" id="adminForm" class="adminform">'
								.		'<table class="adminlist" width="100%" cellspacing="0" cellpadding="4" border="0">'
								.			'<thead>'
								.				'<tr>'
								.					'<th colspan="2">&nbsp;</th>'
								.					'<th>&nbsp;</th>'
								.					'<th style="text-align:left;">' . $input['trigger'] . '</th>'
								.					'<th>' . $input['access'] . '</th>'
								.					'<th>' . $input['state'] . '</th>'
								.					'<th colspan="3">&nbsp;</th>'
								.					'<th>' . $input['id'] . '</th>'
								.				'</tr>'
								.				'<tr>'
								.					'<th class="title" width="5%">#</th>'
								.					'<th class="title" width="5%"><input type="checkbox" name="toggle" value="" onclick="' . $toggleJs . '" /></th>'
								.					'<th class="title" width="10%">' . CBTxt::Th( 'Auto' ) . '</th>'
								.					'<th class="title" width="30%">' . CBTxt::Th( 'Triggers' ) . '</th>'
								.					'<th class="title" width="15%">' . CBTxt::Th( 'Access' ) . '</th>'
								.					'<th class="title" width="10%">' . CBTxt::Th( 'State' ) . '</th>'
								.					'<th class="title" colspan="2" width="15%">' . CBTxt::Th( 'Re-Order' ) . '</th>'
								.					'<th class="title" width="5%"><a href="javascript: void(0);" onclick="' . $orderJs . '"><img src="' . $plugin->livePath . '/images/save.png" border="0" width="16" height="16" alt="' . htmlspecialchars( CBTxt::T( 'Save Order' ) ) . '" /></a></th>'
								.					'<th class="title" width="5%">' . CBTxt::Th( 'ID' ) . '</th>'
								.				'</tr>'
								.			'</thead>'
								.			'<tbody>';

		if ( $rows ) for ( $i = 0, $n = count( $rows ); $i < $n; $i++ ) {
			$row				=	$rows[$i];
			$publishImg			=	$imgPath . ( $row->get( 'published' ) ? 'tick.png' : 'publish_x.png' );
			$publishTask		=	( $row->get( 'published' ) ? 'plugin.auto_unpublish' : 'plugin.auto_publish' );
			$publishJs			=	"cbListItemTask( this, 'editPlugin', 'action', '$publishTask', 'id', $i );";
			$orderUpJs			=	"cbListItemTask( this, 'editPlugin', 'action', 'plugin.auto_orderup', 'id', $i );";
			$orderDownJs		=	"cbListItemTask( this, 'editPlugin', 'action', 'plugin.auto_orderdown', 'id', $i );";
			$editJs				=	"cbListItemTask( this, 'editPlugin', 'action', 'plugin.auto_edit', 'id', $i );";

			$return				.=				'<tr class="row' . $oneOrTwo . '">'
								.					'<td style="text-align:center;">' . ( $i + $pageNav->limitstart + 1 ) . '</td>'
								.					'<td style="text-align:center;"><input type="checkbox" id="id' . $i . '" name="id[]" value="' . (int) $row->get( 'id' ) . '" /></td>'
								.					'<td style="text-align:center;">' . $row->getType() . '</td>'
								.					'<td><a href="javascript: void(0);" onclick="' . $editJs . '" title="' . ( $row->get( 'title' ) ? ( $row->get( 'description' ) ? htmlspecialchars( $row->get( 'description' ) ) : htmlspecialchars( $row->get( 'trigger' ) ) ) : ( $row->get( 'description' ) ? htmlspecialchars( $row->get( 'description' ) ) : null ) ) . '">' . ( $row->get( 'title' ) ? htmlspecialchars( $row->get( 'title' ) ) : implode( '<br />', explode( ',', $row->get( 'trigger' ) ) ) ) . '</a></td>'
								.					'<td style="text-align:center;">' . implode( '<br />', $row->getAccess() ) . '</td>'
								.					'<td style="text-align:center;"><a href="javascript: void(0);" onclick="' . $publishJs . '"><img src="' . $publishImg . '" width="16" height="16" border="0" /></a></td>'
								.					'<td style="text-align:center;">' . ( ( $i > 0 ) || ( $i + $pageNav->limitstart > 0 ) ? '<a href="javascript: void(0);" onclick="' . $orderUpJs . '"><img src="' . $plugin->livePath . '/images/moveup.png" width="12" height="12" border="0" alt="' . htmlspecialchars( CBTxt::T( 'Move Up' ) ) . '" /></a>' : null ) . '</td>'
								.					'<td style="text-align:center;">' . ( ( $i < $n - 1 ) || ( $i + $pageNav->limitstart < $pageNav->total - 1 ) ? '<a href="javascript: void(0);" onclick="' . $orderDownJs . '"><img src="' . $plugin->livePath . '/images/movedown.png" width="12" height="12" border="0" alt="' . htmlspecialchars( CBTxt::T( 'Move Down' ) ) . '" /></a>' : null ) . '</td>'
								.					'<td style="text-align:center;"><input type="text" name="order[]" size="5" value="' . (int) $row->get( 'ordering' ) . '" class="text_area" style="text-align: center" /></td>'
								.					'<td style="text-align:center;">' . (int) $row->get( 'id' ) . '</td>'
								.				'</tr>';

			$oneOrTwo			=	( $oneOrTwo == 1 ? 2 : 1 );
		} else {
			$return				.=				'<tr>'
								.					'<td colspan="10">';

			if ( $pageNav->searching ) {
				$return			.=						CBTxt::Th( 'No auto search results found.' );
			} else {
				$return			.=						CBTxt::Th( 'There currently are no autos.' );
			}

			$return				.=					'</td>'
								.				'</tr>';
		}

		$return					.=			'</tbody>'
								.			'<tfoot>'
								.				'<tr>'
								.					'<th colspan="10">' . $pageNav->getListFooter() . '</th>'
								.				'</tr>'
								.			'</tfoot>'
								.		'</table>'
								.		'<input type="hidden" id="option" name="option" value="' . htmlspecialchars( $plugin->option ) . '" />'
								.		'<input type="hidden" id="task" name="task" value="editPlugin" />'
								.		'<input type="hidden" id="cid" name="cid" value="' . (int) $plugin->id . '" />'
								.		'<input type="hidden" id="action" name="action" value="plugin.auto" />'
								.		cbGetSpoofInputTag( 'plugin' )
								.	'</form>';

		echo $return;
	}

	public function getAutoEdit( $id, $user, $plugin, $message = null ) {
		global $_CB_framework;

		cbgjClass::getTemplate( 'cbgroupjiveauto_edit' );

		$row						=	cbgjAutoData::getAutos( null, array( 'id', '=', (int) $id ), null, null, false );
		$params						=	$row->getParams();

		$js							=	"function conditionalCalculate() {"
									.		"$( '#conditional table' ).each( function() {"
									.			"if ( $( this ).find( 'tbody tr' ).length > 1 ) {"
									.				"$( this ).find( '.moveConditional,.removeConditional' ).removeClass( 'gjautoHide' );"
									.				"$( this ).find( 'tbody' ).sortable( 'enable' );"
									.			"} else {"
									.				"$( this ).find( '.moveConditional,.removeConditional' ).addClass( 'gjautoHide' );"
									.				"$( this ).find( 'tbody' ).sortable( 'disable' );"
									.			"}"
									.			"$( this ).find( 'tbody tr' ).each( function( index ) {"
									.				"$( this ).find( '.conditionalField input' ).attr( 'id', 'fields__field' + index ).attr( 'name', 'fields[field' + index + ']' );"
									.				"$( this ).find( '.conditionalOperator select' ).attr( 'id', 'operators__operator' + index ).attr( 'name', 'operators[operator' + index + ']' );"
									.				"$( this ).find( '.conditionalValue input' ).attr( 'id', 'values__value' + index ).attr( 'name', 'values[value' + index + ']' );"
									.			"});"
									.		"});"
									.	"};"
									.	"$( '.addConditional' ).live( 'click', function() {"
									.		"var conditional = $( this ).parents( 'tr' ).first().clone();"
									.		"conditional.find( '.conditionalField input,.conditionalOperator select,.conditionalValue input' ).val( '' ).removeClass( 'gjautoHide' );"
									.		"conditional.appendTo( '#conditional table tbody' );"
									.		"conditionalCalculate();"
									.	"});"
									.	"$( '.removeConditional' ).live( 'click', function() {"
									.		"if ( $( '#conditional table tbody tr' ).length > 1 ) {"
									.			"$( this ).parents( 'tr' ).first().remove();"
									.		"}"
									.		"conditionalCalculate();"
									.	"});"
									.	"$( '.conditionalOperator select' ).live( 'change', function() {"
									.		"if ( ( $( this ).val() == 6 ) || ( $( this ).val() == 7 ) ) {"
									.			"$( this ).closest( 'tr' ).find( '.conditionalValue input' ).addClass( 'gjautoHide' );"
									.		"} else {"
									.			"$( this ).closest( 'tr' ).find( '.conditionalValue input' ).removeClass( 'gjautoHide' );"
									.		"}"
									.	"});"
									.	"$( '#conditional table tbody' ).sortable( { items: 'tr', containment: 'parent', animated: true, stop: conditionalCalculate, tolerance: 'pointer', handle: '.moveConditional', opacity: 0.5 } );"
									.	"conditionalCalculate();"
									.	"function validate() {"
									.		"if ( $( '#trigger' ).val() ) {"
									.			"$( '#object,#access__' ).closest( 'tr' ).removeClass( 'gjautoHide' );"
									.			"if ( $( '#object' ).val() == 1 ) {"
									.				"$( '#variable' ).removeClass( 'gjautoHide' );"
									.				"$( '#variable_user' ).addClass( 'gjautoHide' );"
									.			"} else if ( $( '#object' ).val() == 3 ) {"
									.				"$( '#variable' ).addClass( 'gjautoHide' );"
									.				"$( '#variable_user' ).removeClass( 'gjautoHide' );"
									.			"} else {"
									.				"$( '#variable,#variable_user' ).addClass( 'gjautoHide' );"
									.			"}"
									.			"if ( $( '#access__' ).val() ) {"
									.				"$( '#access__' ).closest( 'tr' ).nextAll( 'tr' ).removeClass( 'gjautoHide' );"
									.				"$( '#params__auto' ).closest( 'tr' ).nextAll( 'tr' ).addClass( 'gjautoHide' );"
									.				"if ( $( '#params__auto' ).val() == 1 ) {"
									.					"$( '#list_groups' ).removeClass( 'gjautoHide' );"
									.					"$( '#params__status' ).closest( 'tr' ).removeClass( 'gjautoHide' );"
									.				"} else if ( $( '#params__auto' ).val() == 2 ) {"
									.					"$( '#list_category' ).removeClass( 'gjautoHide' );"
									.					"if ( $( '#params__category' ).val() ) {"
									.						"$( '#list_category' ).closest( 'tr' ).nextAll( 'tr' ).removeClass( 'gjautoHide' );"
									.						"if ( $( '#params__category' ).val() != -1 ) {"
									.							"$( '#params__types__,#params__cat_parent,#params__cat_name,#params__cat_description,#params__cat_owner,#params__cat_unique' ).closest( 'tr' ).addClass( 'gjautoHide' );"
									.						"}"
									.						"if ( $( '#params__grp_unique' ).val() == 1 ) {"
									.							"$( '#params__grp_autojoin' ).closest( 'tr' ).addClass( 'gjautoHide' );"
									.						"}"
									.					"} else {"
									.						"$( '#list_category' ).closest( 'tr' ).nextAll( 'tr' ).addClass( 'gjautoHide' );"
									.					"}"
									.				"} else if ( $( '#params__auto' ).val() == 3 ) {"
									.					"$( '#list_category' ).closest( 'tr' ).nextAll( 'tr' ).removeClass( 'gjautoHide' );"
									.					"$( '#params__grp_parent,#params__grp_name,#params__grp_description,#params__grp_owner,#params__grp_unique,#params__grp_autojoin,#params__type' ).closest( 'tr' ).addClass( 'gjautoHide' );"
									.				"} else if ( $( '#params__auto' ).val() == 4 ) {"
									.					"$( '#list_groups' ).removeClass( 'gjautoHide' );"
									.				"}"
									.			"} else {"
									.				"$( '#access__' ).closest( 'tr' ).nextAll( 'tr' ).addClass( 'gjautoHide' );"
									.			"}"
									.		"} else {"
									.			"$( '#trigger' ).closest( 'tr' ).nextAll( 'tr' ).addClass( 'gjautoHide' );"
									.		"}"
									.	"};"
									.	"$( '#triggers' ).change( function() {"
									.		"if ( $( '#trigger' ).val() ) {"
									.			"value = $( '#trigger' ).val() + ',' + $( this ).val();"
									.		"} else {"
									.			"value = $( this ).val();"
									.		"}"
									.		"if ( value ) {"
									.			"$( '#trigger' ).attr( 'value', value ).focus();"
									.			"$( this ).attr( 'value', '' );"
									.		"}"
									.	"});"
									.	"$( '#adminForm :input' ).bind( 'change keyup focus', function() {"
									.		"validate();"
									.	"});"
									.	"validate();";

		$_CB_framework->outputCbJQuery( $js, 'ui-all' );

		$input						=	array();

		$input['published']			=	moscomprofilerHTML::yesnoSelectList( 'published', null, (int) cbgjClass::getCleanParam( true, 'published', $row->get( 'published', 0 ) ) );
		$input['title']				=	'<input type="text" id="title" name="title" value="' . htmlspecialchars( cbgjClass::getCleanParam( true, 'title', $row->get( 'title' ) ) ) . '" class="inputbox" size="40" />';
		$input['description']		=	'<textarea id="description" name="description" class="inputbox" cols="40" rows="3">' . htmlspecialchars( cbgjClass::getCleanParam( true, 'description', $row->get( 'description' ) ) ) . '</textarea>';

		$listTriggers				=	array();
		$listTriggers[]				=	moscomprofilerHTML::makeOption( '', CBTxt::T( '- Add Trigger -' ) );
		$listTriggers[]				=	moscomprofilerHTML::makeOptGroup( CBTxt::T( 'Frontend' ) );
		$listTriggers[]				=	moscomprofilerHTML::makeOption( 'onBeforeLoginFormDisplay', CBTxt::T( 'Before Login Form Display' ) );
		$listTriggers[]				=	moscomprofilerHTML::makeOption( 'onBeforeFirstLogin', CBTxt::T( 'Before User First Login' ) );
		$listTriggers[]				=	moscomprofilerHTML::makeOption( 'onBeforeLogin', CBTxt::T( 'Before User Login' ) );
		$listTriggers[]				=	moscomprofilerHTML::makeOption( 'onLoginAuthentication', CBTxt::T( 'Login Authentication' ) );
		$listTriggers[]				=	moscomprofilerHTML::makeOption( 'onDuringLogin', CBTxt::T( 'During Login' ) );
		$listTriggers[]				=	moscomprofilerHTML::makeOption( 'onDoLoginNow', CBTxt::T( 'Do Login Now' ) );
		$listTriggers[]				=	moscomprofilerHTML::makeOption( 'onAfterLogin', CBTxt::T( 'After User Login' ) );
		$listTriggers[]				=	moscomprofilerHTML::makeOption( 'onBeforeLogout', CBTxt::T( 'Before User Logout' ) );
		$listTriggers[]				=	moscomprofilerHTML::makeOption( 'onDoLogoutNow', CBTxt::T( 'Do Logout Now' ) );
		$listTriggers[]				=	moscomprofilerHTML::makeOption( 'onAfterLogout', CBTxt::T( 'After User Logout' ) );
		$listTriggers[]				=	moscomprofilerHTML::makeOption( 'onBeforeUserAvatarUpdate', CBTxt::T( 'Before Avatar Update' ) );
		$listTriggers[]				=	moscomprofilerHTML::makeOption( 'onAfterUserAvatarUpdate', CBTxt::T( 'After Avatar Update' ) );
		$listTriggers[]				=	moscomprofilerHTML::makeOption( 'onAfterUserProfileEditDisplay', CBTxt::T( 'After Profile Edit Display' ) );
		$listTriggers[]				=	moscomprofilerHTML::makeOption( 'onBeforeUserUpdate', CBTxt::T( 'Before Profile Update' ) );
		$listTriggers[]				=	moscomprofilerHTML::makeOption( 'onAfterUserUpdate', CBTxt::T( 'After Profile Update' ) );
		$listTriggers[]				=	moscomprofilerHTML::makeOption( 'onBeforeUserProfileRequest', CBTxt::T( 'Before Profile Request' ) );
		$listTriggers[]				=	moscomprofilerHTML::makeOption( 'onBeforeUserProfileDisplay', CBTxt::T( 'Before Profile Display' ) );
		$listTriggers[]				=	moscomprofilerHTML::makeOption( 'onAfterUserProfileDisplay', CBTxt::T( 'After Profile Display' ) );
		$listTriggers[]				=	moscomprofilerHTML::makeOption( 'onBeforeRegisterFormDisplay', CBTxt::T( 'Before Registration Form Display' ) );
		$listTriggers[]				=	moscomprofilerHTML::makeOption( 'onBeforeRegisterForm', CBTxt::T( 'Before Registration Form' ) );
		$listTriggers[]				=	moscomprofilerHTML::makeOption( 'onStartSaveUserRegistration', CBTxt::T( 'Start Save Registration' ) );
		$listTriggers[]				=	moscomprofilerHTML::makeOption( 'onBeforeUserRegistration', CBTxt::T( 'Before Registration' ) );
		$listTriggers[]				=	moscomprofilerHTML::makeOption( 'onAfterUserRegistration', CBTxt::T( 'After Registration' ) );
		$listTriggers[]				=	moscomprofilerHTML::makeOption( 'onAfterUserRegistrationMailsSent', CBTxt::T( 'After Registration Mail Sent' ) );
		$listTriggers[]				=	moscomprofilerHTML::makeOption( 'onBeforeAddConnection', CBTxt::T( 'Before Add Connection' ) );
		$listTriggers[]				=	moscomprofilerHTML::makeOption( 'onAfterAddConnection', CBTxt::T( 'After Add Connection' ) );
		$listTriggers[]				=	moscomprofilerHTML::makeOption( 'onBeforeRemoveConnection', CBTxt::T( 'Before Remove Connection' ) );
		$listTriggers[]				=	moscomprofilerHTML::makeOption( 'onAfterRemoveConnection', CBTxt::T( 'After Remove Connection' ) );
		$listTriggers[]				=	moscomprofilerHTML::makeOption( 'onBeforeDenyConnection', CBTxt::T( 'Before Deny Connection' ) );
		$listTriggers[]				=	moscomprofilerHTML::makeOption( 'onAfterDenyConnection', CBTxt::T( 'After Deny Connection' ) );
		$listTriggers[]				=	moscomprofilerHTML::makeOption( 'onBeforeAcceptConnection', CBTxt::T( 'Before Accept Connection' ) );
		$listTriggers[]				=	moscomprofilerHTML::makeOption( 'onAfterAcceptConnection', CBTxt::T( 'After Accept Connection' ) );
		$listTriggers[]				=	moscomprofilerHTML::makeOption( 'onLostPassForm', CBTxt::T( 'Lost Password Form' ) );
		$listTriggers[]				=	moscomprofilerHTML::makeOption( 'onStartNewPassword', CBTxt::T( 'Start New Password' ) );
		$listTriggers[]				=	moscomprofilerHTML::makeOption( 'onBeforeNewPassword', CBTxt::T( 'Before New Password' ) );
		$listTriggers[]				=	moscomprofilerHTML::makeOption( 'onNewPassword', CBTxt::T( 'New Password' ) );
		$listTriggers[]				=	moscomprofilerHTML::makeOption( 'onBeforeUsernameReminder', CBTxt::T( 'Before Username Reminder' ) );
		$listTriggers[]				=	moscomprofilerHTML::makeOption( 'onAfterUsernameReminder', CBTxt::T( 'After Username Reminder' ) );
		$listTriggers[]				=	moscomprofilerHTML::makeOption( 'onBeforeEmailUserForm', CBTxt::T( 'Before Email Form' ) );
		$listTriggers[]				=	moscomprofilerHTML::makeOption( 'onAfterEmailUserForm', CBTxt::T( 'After Email Form' ) );
		$listTriggers[]				=	moscomprofilerHTML::makeOption( 'onBeforeEmailUser', CBTxt::T( 'Before Email User' ) );
		$listTriggers[]				=	moscomprofilerHTML::makeOption( 'onBeforeDisplayUsersList', CBTxt::T( 'Before Userlist Display' ) );
		$listTriggers[]				=	moscomprofilerHTML::makeOptGroup( CBTxt::T( 'Backend' ) );
		$listTriggers[]				=	moscomprofilerHTML::makeOption( 'onBeforeUpdateUser', CBTxt::T( 'Before Profile Update' ) );
		$listTriggers[]				=	moscomprofilerHTML::makeOption( 'onAfterUpdateUser', CBTxt::T( 'After Profile Update' ) );
		$listTriggers[]				=	moscomprofilerHTML::makeOption( 'onBeforeNewUser', CBTxt::T( 'Before Registration' ) );
		$listTriggers[]				=	moscomprofilerHTML::makeOption( 'onAfterNewUser', CBTxt::T( 'After Registration' ) );
		$listTriggers[]				=	moscomprofilerHTML::makeOption( 'onBeforeDeleteUser', CBTxt::T( 'Before Delete User' ) );
		$listTriggers[]				=	moscomprofilerHTML::makeOption( 'onAfterDeleteUser', CBTxt::T( 'After Delete User' ) );
		$listTriggers[]				=	moscomprofilerHTML::makeOption( 'onBeforeUserBlocking', CBTxt::T( 'Before Blocking User' ) );
		$listTriggers[]				=	moscomprofilerHTML::makeOption( 'onBeforeSyncUser', CBTxt::T( 'Before Sync User' ) );
		$listTriggers[]				=	moscomprofilerHTML::makeOption( 'onAfterSyncUser', CBTxt::T( 'After Sync User' ) );
		$listTriggers[]				=	moscomprofilerHTML::makeOption( 'onBeforeUserActive', CBTxt::T( 'Before User Active' ) );
		$listTriggers[]				=	moscomprofilerHTML::makeOption( 'onAfterCheckCbDb', CBTxt::T( 'After Check CB Database' ) );
		$listTriggers[]				=	moscomprofilerHTML::makeOption( 'onAfterCheckCbFieldsDb', CBTxt::T( 'After Check Fields Database' ) );
		$listTriggers[]				=	moscomprofilerHTML::makeOption( 'onBeforeFixDb', CBTxt::T( 'Before Fix Database' ) );
		$listTriggers[]				=	moscomprofilerHTML::makeOption( 'onAfterFixDb', CBTxt::T( 'After Fix Database' ) );
		$listTriggers[]				=	moscomprofilerHTML::makeOption( 'onBeforeFixFieldsDb', CBTxt::T( 'Before Fix Fields Database' ) );
		$listTriggers[]				=	moscomprofilerHTML::makeOption( 'onBeforeBackendUsersListBuildQuery', CBTxt::T( 'Before Userlist Build Query' ) );
		$listTriggers[]				=	moscomprofilerHTML::makeOption( 'onAfterBackendUsersList', CBTxt::T( 'Before Userlist' ) );
		$listTriggers[]				=	moscomprofilerHTML::makeOption( 'onBeforeBackendUsersEmailForm', CBTxt::T( 'Before Userlist Email Form' ) );
		$listTriggers[]				=	moscomprofilerHTML::makeOption( 'onBeforeBackendUsersEmailStart', CBTxt::T( 'Before Userlist Email Start' ) );
		$listTriggers[]				=	moscomprofilerHTML::makeOption( 'onBeforeBackendUserEmail', CBTxt::T( 'Before Userlist Email' ) );
		$listTriggers[]				=	moscomprofilerHTML::makeOptGroup( CBTxt::T( 'Frontend & Backend' ) );
		$listTriggers[]				=	moscomprofilerHTML::makeOption( 'onBeforeUserProfileEditDisplay', CBTxt::T( 'Before Profile Edit Display' ) );
		$listTriggers[]				=	moscomprofilerHTML::makeOption( 'onBeforeUserConfirm', CBTxt::T( 'Before User Confirm' ) );
		$listTriggers[]				=	moscomprofilerHTML::makeOption( 'onAfterUserConfirm', CBTxt::T( 'After User Confirm' ) );
		$listTriggers[]				=	moscomprofilerHTML::makeOption( 'onBeforeUserApproval', CBTxt::T( 'Before User Approval' ) );
		$listTriggers[]				=	moscomprofilerHTML::makeOption( 'onAfterUserApproval', CBTxt::T( 'After User Approval' ) );
		$listTriggers[]				=	moscomprofilerHTML::makeOption( 'onPrepareMenus', CBTxt::T( 'Prepare Menu' ) );
		$listTriggers[]				=	moscomprofilerHTML::makeOption( 'onAfterFieldsFetch', CBTxt::T( 'After Fields Fetch' ) );
		$listTriggers[]				=	moscomprofilerHTML::makeOption( 'onAfterTabsFetch', CBTxt::T( 'After Tabs Fetch' ) );
		$listTriggers[]				=	moscomprofilerHTML::makeOption( 'onAfterPrepareViewTabs', CBTxt::T( 'After Prepare Tabs View' ) );
		$listTriggers[]				=	moscomprofilerHTML::makeOption( 'onBeforeEditATab', CBTxt::T( 'Before Tab Edit' ) );
		$listTriggers[]				=	moscomprofilerHTML::makeOption( 'onAfterEditATab', CBTxt::T( 'After Tab Edit' ) );
		$listTriggers[]				=	moscomprofilerHTML::makeOption( 'onStartUsersList', CBTxt::T( 'Start Userlist' ) );
		$listTriggers[]				=	moscomprofilerHTML::makeOption( 'onAfterUsersListFieldsSql', CBTxt::T( 'After Userlist Fields SQL' ) );
		$listTriggers[]				=	moscomprofilerHTML::makeOption( 'onBeforeUsersListBuildQuery', CBTxt::T( 'Before Userlist Build Query' ) );
		$listTriggers[]				=	moscomprofilerHTML::makeOption( 'onBeforeUsersListQuery', CBTxt::T( 'Before Userlist Query' ) );
		$listTriggers[]				=	moscomprofilerHTML::makeOption( 'onInputFieldHtmlRender', CBTxt::T( 'Field HTML Render' ) );
		$listTriggers[]				=	moscomprofilerHTML::makeOption( 'onFieldIcons', CBTxt::T( 'Field Icons' ) );
		$listTriggers[]				=	moscomprofilerHTML::makeOption( 'onLogChange', CBTxt::T( 'Log Change' ) );
		$listTriggers[]				=	moscomprofilerHTML::makeOption( 'onAfterAuthorizeAction', CBTxt::T( 'After Authorize Action' ) );
		$listTriggers[]				=	moscomprofilerHTML::makeOption( 'onAfterAuthorizeView', CBTxt::T( 'After Authorize View' ) );
		$listTriggers[]				=	moscomprofilerHTML::makeOptGroup( CBTxt::T( 'CB GroupJive' ) );
		$listTriggers[]				=	moscomprofilerHTML::makeOption( 'gj_onPluginBE', CBTxt::T( 'Integration Backend' ) );
		$listTriggers[]				=	moscomprofilerHTML::makeOption( 'gj_onBeforeTools', CBTxt::T( 'Before Tools' ) );
		$listTriggers[]				=	moscomprofilerHTML::makeOption( 'gj_onAfterTools', CBTxt::T( 'After Tools' ) );
		$listTriggers[]				=	moscomprofilerHTML::makeOption( 'gj_onBeforeNotification', CBTxt::T( 'Before Notification' ) );
		$listTriggers[]				=	moscomprofilerHTML::makeOption( 'gj_onAfterNotification', CBTxt::T( 'After Notification' ) );
		$listTriggers[]				=	moscomprofilerHTML::makeOption( 'gj_onBeforeTemplate', CBTxt::T( 'Before Template' ) );
		$listTriggers[]				=	moscomprofilerHTML::makeOption( 'gj_onAfterTemplate', CBTxt::T( 'After Template' ) );
		$listTriggers[]				=	moscomprofilerHTML::makeOption( 'gj_onBeforeUpdateCategory', CBTxt::T( 'Before Update Category' ) );
		$listTriggers[]				=	moscomprofilerHTML::makeOption( 'gj_onBeforeCreateCategory', CBTxt::T( 'Before Create Category' ) );
		$listTriggers[]				=	moscomprofilerHTML::makeOption( 'gj_onAfterUpdateCategory', CBTxt::T( 'After Update Category' ) );
		$listTriggers[]				=	moscomprofilerHTML::makeOption( 'gj_onAfterCreateCategory', CBTxt::T( 'After Create Category' ) );
		$listTriggers[]				=	moscomprofilerHTML::makeOption( 'gj_onBeforeDeleteCategory', CBTxt::T( 'Before Delete Category' ) );
		$listTriggers[]				=	moscomprofilerHTML::makeOption( 'gj_onAfterDeleteCategory', CBTxt::T( 'After Delete Category' ) );
		$listTriggers[]				=	moscomprofilerHTML::makeOption( 'gj_onBeforeUpdateCategoryOrder', CBTxt::T( 'Before Update Category Order' ) );
		$listTriggers[]				=	moscomprofilerHTML::makeOption( 'gj_onAfterUpdateCategoryOrder', CBTxt::T( 'After Update Category Order' ) );
		$listTriggers[]				=	moscomprofilerHTML::makeOption( 'gj_onBeforeUpdateCategoryState', CBTxt::T( 'Before Update Category State' ) );
		$listTriggers[]				=	moscomprofilerHTML::makeOption( 'gj_onAfterUpdateCategoryState', CBTxt::T( 'After Update Category State' ) );
		$listTriggers[]				=	moscomprofilerHTML::makeOption( 'gj_onBeforeUpdateCategoryParams', CBTxt::T( 'Before Update Category Params' ) );
		$listTriggers[]				=	moscomprofilerHTML::makeOption( 'gj_onAfterUpdateCategoryParams', CBTxt::T( 'After Update Category Params' ) );
		$listTriggers[]				=	moscomprofilerHTML::makeOption( 'gj_onBeforeSetCategoryParams', CBTxt::T( 'Before Set Category Params' ) );
		$listTriggers[]				=	moscomprofilerHTML::makeOption( 'gj_onAfterSetCategoryParams', CBTxt::T( 'After Set Category Params' ) );
		$listTriggers[]				=	moscomprofilerHTML::makeOption( 'gj_onBeforeUpdateGroup', CBTxt::T( 'Before Update Group' ) );
		$listTriggers[]				=	moscomprofilerHTML::makeOption( 'gj_onBeforeCreateGroup', CBTxt::T( 'Before Create Group' ) );
		$listTriggers[]				=	moscomprofilerHTML::makeOption( 'gj_onAfterUpdateGroup', CBTxt::T( 'After Update Group' ) );
		$listTriggers[]				=	moscomprofilerHTML::makeOption( 'gj_onAfterCreateGroup', CBTxt::T( 'After Create Group' ) );
		$listTriggers[]				=	moscomprofilerHTML::makeOption( 'gj_onBeforeDeleteGroup', CBTxt::T( 'Before Delete Group' ) );
		$listTriggers[]				=	moscomprofilerHTML::makeOption( 'gj_onAfterDeleteGroup', CBTxt::T( 'After Delete Group' ) );
		$listTriggers[]				=	moscomprofilerHTML::makeOption( 'gj_onBeforeUpdateGroupOrder', CBTxt::T( 'Before Update Group Order' ) );
		$listTriggers[]				=	moscomprofilerHTML::makeOption( 'gj_onAfterUpdateGroupOrder', CBTxt::T( 'After Update Group Order' ) );
		$listTriggers[]				=	moscomprofilerHTML::makeOption( 'gj_onBeforeUpdateGroupState', CBTxt::T( 'Before Update Group State' ) );
		$listTriggers[]				=	moscomprofilerHTML::makeOption( 'gj_onAfterUpdateGroupState', CBTxt::T( 'After Update Group State' ) );
		$listTriggers[]				=	moscomprofilerHTML::makeOption( 'gj_onBeforeUpdateGroupParams', CBTxt::T( 'Before Update Group Params' ) );
		$listTriggers[]				=	moscomprofilerHTML::makeOption( 'gj_onAfterUpdateGroupParams', CBTxt::T( 'After Update Group Params' ) );
		$listTriggers[]				=	moscomprofilerHTML::makeOption( 'gj_onBeforeSetGroupParams', CBTxt::T( 'Before Set Group Params' ) );
		$listTriggers[]				=	moscomprofilerHTML::makeOption( 'gj_onAfterSetGroupParams', CBTxt::T( 'After Set Group Params' ) );
		$listTriggers[]				=	moscomprofilerHTML::makeOption( 'gj_onBeforeUpdateUser', CBTxt::T( 'Before Update User' ) );
		$listTriggers[]				=	moscomprofilerHTML::makeOption( 'gj_onBeforeCreateUser', CBTxt::T( 'Before Create User' ) );
		$listTriggers[]				=	moscomprofilerHTML::makeOption( 'gj_onAfterUpdateUser', CBTxt::T( 'After Update User' ) );
		$listTriggers[]				=	moscomprofilerHTML::makeOption( 'gj_onAfterCreateUser', CBTxt::T( 'After Create User' ) );
		$listTriggers[]				=	moscomprofilerHTML::makeOption( 'gj_onBeforeDeleteUser', CBTxt::T( 'Before Delete User' ) );
		$listTriggers[]				=	moscomprofilerHTML::makeOption( 'gj_onAfterDeleteUser', CBTxt::T( 'After Delete User' ) );
		$listTriggers[]				=	moscomprofilerHTML::makeOption( 'gj_onBeforeUpdateUserStatus', CBTxt::T( 'Before Update User Status' ) );
		$listTriggers[]				=	moscomprofilerHTML::makeOption( 'gj_onAfterUpdateUserStatus', CBTxt::T( 'After Update User Status' ) );
		$listTriggers[]				=	moscomprofilerHTML::makeOption( 'gj_onBeforeUpdateUserParams', CBTxt::T( 'Before Update User Params' ) );
		$listTriggers[]				=	moscomprofilerHTML::makeOption( 'gj_onAfterUpdateUserParams', CBTxt::T( 'After Update User Params' ) );
		$listTriggers[]				=	moscomprofilerHTML::makeOption( 'gj_onBeforeSetUserParams', CBTxt::T( 'Before Set User Params' ) );
		$listTriggers[]				=	moscomprofilerHTML::makeOption( 'gj_onAfterSetUserParams', CBTxt::T( 'After Set User Params' ) );
		$listTriggers[]				=	moscomprofilerHTML::makeOption( 'gj_onBeforeUpdateInvite', CBTxt::T( 'Before Update Invite' ) );
		$listTriggers[]				=	moscomprofilerHTML::makeOption( 'gj_onBeforeCreateInvite', CBTxt::T( 'Before Create Invite' ) );
		$listTriggers[]				=	moscomprofilerHTML::makeOption( 'gj_onAfterUpdateInvite', CBTxt::T( 'After Update Invite' ) );
		$listTriggers[]				=	moscomprofilerHTML::makeOption( 'gj_onAfterCreateInvite', CBTxt::T( 'After Create Invite' ) );
		$listTriggers[]				=	moscomprofilerHTML::makeOption( 'gj_onBeforeDeleteInvite', CBTxt::T( 'Before Delete Invite' ) );
		$listTriggers[]				=	moscomprofilerHTML::makeOption( 'gj_onAfterDeleteInvite', CBTxt::T( 'After Delete Invite' ) );
		$listTriggers[]				=	moscomprofilerHTML::makeOption( 'gj_onBeforeUpdateNotification', CBTxt::T( 'Before Update Notification' ) );
		$listTriggers[]				=	moscomprofilerHTML::makeOption( 'gj_onBeforeCreateNotification', CBTxt::T( 'Before Create Notification' ) );
		$listTriggers[]				=	moscomprofilerHTML::makeOption( 'gj_onAfterUpdateNotification', CBTxt::T( 'After Update Notification' ) );
		$listTriggers[]				=	moscomprofilerHTML::makeOption( 'gj_onAfterCreateNotification', CBTxt::T( 'After Create Notification' ) );
		$listTriggers[]				=	moscomprofilerHTML::makeOption( 'gj_onBeforeDeleteNotification', CBTxt::T( 'Before Delete Notification' ) );
		$listTriggers[]				=	moscomprofilerHTML::makeOption( 'gj_onAfterDeleteNotification', CBTxt::T( 'After Delete Notification' ) );
		$listTriggers[]				=	moscomprofilerHTML::makeOption( 'gj_onBeforeUpdateNotificationParams', CBTxt::T( 'Before Update Notification Params' ) );
		$listTriggers[]				=	moscomprofilerHTML::makeOption( 'gj_onAfterUpdateNotificationParams', CBTxt::T( 'After Update Notification Params' ) );
		$listTriggers[]				=	moscomprofilerHTML::makeOption( 'gj_onBeforeSetNotificationParams', CBTxt::T( 'Before Set Notification Params' ) );
		$listTriggers[]				=	moscomprofilerHTML::makeOption( 'gj_onAfterSetNotificationParams', CBTxt::T( 'After Set Notification Params' ) );
		$listTriggers[]				=	moscomprofilerHTML::makeOption( 'gj_onPluginFE', CBTxt::T( 'Integration Frontend' ) );
		$listTriggers[]				=	moscomprofilerHTML::makeOption( 'gj_onBeforeMessageOverview', CBTxt::T( 'Before Message Overview' ) );
		$listTriggers[]				=	moscomprofilerHTML::makeOption( 'gj_onAfterMessageOverview', CBTxt::T( 'After Message Overview' ) );
		$listTriggers[]				=	moscomprofilerHTML::makeOption( 'gj_onBeforeMessageCategory', CBTxt::T( 'Before Message Category' ) );
		$listTriggers[]				=	moscomprofilerHTML::makeOption( 'gj_onAfterMessageCategory', CBTxt::T( 'After Message Category' ) );
		$listTriggers[]				=	moscomprofilerHTML::makeOption( 'gj_onBeforeJoinGroup', CBTxt::T( 'Before Join Group' ) );
		$listTriggers[]				=	moscomprofilerHTML::makeOption( 'gj_onAfterJoinGroup', CBTxt::T( 'After Join Group' ) );
		$listTriggers[]				=	moscomprofilerHTML::makeOption( 'gj_onBeforeLeaveGroup', CBTxt::T( 'Before Leave Group' ) );
		$listTriggers[]				=	moscomprofilerHTML::makeOption( 'gj_onAfterLeaveGroup', CBTxt::T( 'After Leave Group' ) );
		$listTriggers[]				=	moscomprofilerHTML::makeOption( 'gj_onBeforeMessageGroup', CBTxt::T( 'Before Message Group' ) );
		$listTriggers[]				=	moscomprofilerHTML::makeOption( 'gj_onAfterMessageGroup', CBTxt::T( 'After Message Group' ) );
		$listTriggers[]				=	moscomprofilerHTML::makeOption( 'gj_onPluginBEToolbar', CBTxt::T( 'Integration Backend Toolbar' ) );
		$listTriggers[]				=	moscomprofilerHTML::makeOption( 'gjint_onBeforeUpdateAuto', CBTxt::T( 'Before Update Auto' ) );
		$listTriggers[]				=	moscomprofilerHTML::makeOption( 'gjint_onBeforeCreateAuto', CBTxt::T( 'Before Create Auto' ) );
		$listTriggers[]				=	moscomprofilerHTML::makeOption( 'gjint_onAfterUpdateAuto', CBTxt::T( 'After Update Auto' ) );
		$listTriggers[]				=	moscomprofilerHTML::makeOption( 'gjint_onAfterCreateAuto', CBTxt::T( 'After Create Auto' ) );
		$listTriggers[]				=	moscomprofilerHTML::makeOption( 'gjint_onBeforeDeleteAuto', CBTxt::T( 'Before Delete Auto' ) );
		$listTriggers[]				=	moscomprofilerHTML::makeOption( 'gjint_onAfterDeleteAuto', CBTxt::T( 'After Delete Auto' ) );
		$listTriggers[]				=	moscomprofilerHTML::makeOption( 'gjint_onBeforeUpdateAutoOrder', CBTxt::T( 'Before Update Auto Order' ) );
		$listTriggers[]				=	moscomprofilerHTML::makeOption( 'gjint_onAfterUpdateAutoOrder', CBTxt::T( 'After Update Auto Order' ) );
		$listTriggers[]				=	moscomprofilerHTML::makeOption( 'gjint_onBeforeUpdateAutoParams', CBTxt::T( 'Before Update Auto Params' ) );
		$listTriggers[]				=	moscomprofilerHTML::makeOption( 'gjint_onAfterUpdateAutoParams', CBTxt::T( 'After Update Auto Params' ) );
		$listTriggers[]				=	moscomprofilerHTML::makeOption( 'gjint_onBeforeUpdateAutoState', CBTxt::T( 'Before Update Auto State' ) );
		$listTriggers[]				=	moscomprofilerHTML::makeOption( 'gjint_onAfterUpdateAutoState', CBTxt::T( 'After Update Auto State' ) );
		$listTriggers[]				=	moscomprofilerHTML::makeOption( 'gjint_onBeforeSetAutoFields', CBTxt::T( 'Before Set Auto Fields' ) );
		$listTriggers[]				=	moscomprofilerHTML::makeOption( 'gjint_onAfterSetAutoFields', CBTxt::T( 'After Set Auto Fields' ) );
		$listTriggers[]				=	moscomprofilerHTML::makeOption( 'gjint_onBeforeSetAutoOperators', CBTxt::T( 'Before Set Auto Operators' ) );
		$listTriggers[]				=	moscomprofilerHTML::makeOption( 'gjint_onAfterSetAutoOperators', CBTxt::T( 'After Set Auto Operators' ) );
		$listTriggers[]				=	moscomprofilerHTML::makeOption( 'gjint_onBeforeSetAutoValues', CBTxt::T( 'Before Set Auto Values' ) );
		$listTriggers[]				=	moscomprofilerHTML::makeOption( 'gjint_onAfterSetAutoValues', CBTxt::T( 'After Set Auto Values' ) );
		$listTriggers[]				=	moscomprofilerHTML::makeOption( 'gjint_onBeforeSetAutoParams', CBTxt::T( 'Before Set Auto Params' ) );
		$listTriggers[]				=	moscomprofilerHTML::makeOption( 'gjint_onAfterSetAutoParams', CBTxt::T( 'After Set Auto Params' ) );
		$listTriggers[]				=	moscomprofilerHTML::makeOption( 'gjint_onBeforeUpdateEvent', CBTxt::T( 'Before Update Event' ) );
		$listTriggers[]				=	moscomprofilerHTML::makeOption( 'gjint_onBeforeCreateEvent', CBTxt::T( 'Before Create Event' ) );
		$listTriggers[]				=	moscomprofilerHTML::makeOption( 'gjint_onAfterUpdateEvent', CBTxt::T( 'After Update Event' ) );
		$listTriggers[]				=	moscomprofilerHTML::makeOption( 'gjint_onAfterCreateEvent', CBTxt::T( 'After Create Event' ) );
		$listTriggers[]				=	moscomprofilerHTML::makeOption( 'gjint_onBeforeDeleteEvent', CBTxt::T( 'Before Delete Event' ) );
		$listTriggers[]				=	moscomprofilerHTML::makeOption( 'gjint_onAfterDeleteEvent', CBTxt::T( 'After Delete Event' ) );
		$listTriggers[]				=	moscomprofilerHTML::makeOption( 'gjint_onBeforeUpdateEventState', CBTxt::T( 'Before Update Event State' ) );
		$listTriggers[]				=	moscomprofilerHTML::makeOption( 'gjint_onAfterUpdateEventState', CBTxt::T( 'After Update Event State' ) );
		$listTriggers[]				=	moscomprofilerHTML::makeOption( 'gjint_onBeforeUpdateEventAttendance', CBTxt::T( 'Before Update Event Attendance' ) );
		$listTriggers[]				=	moscomprofilerHTML::makeOption( 'gjint_onAfterUpdateEventAttendance', CBTxt::T( 'After Update Event Attendance' ) );
		$listTriggers[]				=	moscomprofilerHTML::makeOption( 'gjint_onBeforeUpdateFile', CBTxt::T( 'Before Update File' ) );
		$listTriggers[]				=	moscomprofilerHTML::makeOption( 'gjint_onBeforeCreateFile', CBTxt::T( 'Before Create File' ) );
		$listTriggers[]				=	moscomprofilerHTML::makeOption( 'gjint_onAfterUpdateFile', CBTxt::T( 'After Update File' ) );
		$listTriggers[]				=	moscomprofilerHTML::makeOption( 'gjint_onAfterCreateFile', CBTxt::T( 'After Create File' ) );
		$listTriggers[]				=	moscomprofilerHTML::makeOption( 'gjint_onBeforeDeleteFile', CBTxt::T( 'Before Delete File' ) );
		$listTriggers[]				=	moscomprofilerHTML::makeOption( 'gjint_onAfterDeleteFile', CBTxt::T( 'After Delete File' ) );
		$listTriggers[]				=	moscomprofilerHTML::makeOption( 'gjint_onBeforeUpdateFileState', CBTxt::T( 'Before Update File State' ) );
		$listTriggers[]				=	moscomprofilerHTML::makeOption( 'gjint_onAfterUpdateFileState', CBTxt::T( 'After Update File State' ) );
		$listTriggers[]				=	moscomprofilerHTML::makeOption( 'gjint_onBeforeUpdatePhoto', CBTxt::T( 'Before Update Photo' ) );
		$listTriggers[]				=	moscomprofilerHTML::makeOption( 'gjint_onBeforeCreatePhoto', CBTxt::T( 'Before Create Photo' ) );
		$listTriggers[]				=	moscomprofilerHTML::makeOption( 'gjint_onAfterUpdatePhoto', CBTxt::T( 'After Update Photo' ) );
		$listTriggers[]				=	moscomprofilerHTML::makeOption( 'gjint_onAfterCreatePhoto', CBTxt::T( 'After Create Photo' ) );
		$listTriggers[]				=	moscomprofilerHTML::makeOption( 'gjint_onBeforeDeletePhoto', CBTxt::T( 'Before Delete Photo' ) );
		$listTriggers[]				=	moscomprofilerHTML::makeOption( 'gjint_onAfterDeletePhoto', CBTxt::T( 'After Delete Photo' ) );
		$listTriggers[]				=	moscomprofilerHTML::makeOption( 'gjint_onBeforeUpdatePhotoState', CBTxt::T( 'Before Update Photo State' ) );
		$listTriggers[]				=	moscomprofilerHTML::makeOption( 'gjint_onAfterUpdatePhotoState', CBTxt::T( 'After Update Photo State' ) );
		$listTriggers[]				=	moscomprofilerHTML::makeOption( 'gjint_onBeforeUpdateVideo', CBTxt::T( 'Before Update Video' ) );
		$listTriggers[]				=	moscomprofilerHTML::makeOption( 'gjint_onBeforeCreateVideo', CBTxt::T( 'Before Create Video' ) );
		$listTriggers[]				=	moscomprofilerHTML::makeOption( 'gjint_onAfterUpdateVideo', CBTxt::T( 'After Update Video' ) );
		$listTriggers[]				=	moscomprofilerHTML::makeOption( 'gjint_onAfterCreateVideo', CBTxt::T( 'After Create Video' ) );
		$listTriggers[]				=	moscomprofilerHTML::makeOption( 'gjint_onBeforeDeleteVideo', CBTxt::T( 'Before Delete Video' ) );
		$listTriggers[]				=	moscomprofilerHTML::makeOption( 'gjint_onAfterDeleteVideo', CBTxt::T( 'After Delete Video' ) );
		$listTriggers[]				=	moscomprofilerHTML::makeOption( 'gjint_onBeforeUpdateVideoState', CBTxt::T( 'Before Update Video State' ) );
		$listTriggers[]				=	moscomprofilerHTML::makeOption( 'gjint_onAfterUpdateVideoState', CBTxt::T( 'After Update Video State' ) );
		$listTriggers[]				=	moscomprofilerHTML::makeOption( 'gjint_onBeforeUpdateWall', CBTxt::T( 'Before Update Wall' ) );
		$listTriggers[]				=	moscomprofilerHTML::makeOption( 'gjint_onBeforeCreateWall', CBTxt::T( 'Before Create Wall' ) );
		$listTriggers[]				=	moscomprofilerHTML::makeOption( 'gjint_onAfterUpdateWall', CBTxt::T( 'After Update Wall' ) );
		$listTriggers[]				=	moscomprofilerHTML::makeOption( 'gjint_onAfterCreateWall', CBTxt::T( 'After Create Wall' ) );
		$listTriggers[]				=	moscomprofilerHTML::makeOption( 'gjint_onBeforeDeleteWall', CBTxt::T( 'Before Delete Wall' ) );
		$listTriggers[]				=	moscomprofilerHTML::makeOption( 'gjint_onAfterDeleteWall', CBTxt::T( 'After Delete Wall' ) );
		$listTriggers[]				=	moscomprofilerHTML::makeOption( 'gjint_onBeforeUpdateWallState', CBTxt::T( 'Before Update Wall State' ) );
		$listTriggers[]				=	moscomprofilerHTML::makeOption( 'gjint_onAfterUpdateWallState', CBTxt::T( 'After Update Wall State' ) );
		$listTriggers[]				=	moscomprofilerHTML::makeOption( 'gj_onMenuBE', CBTxt::T( 'Backend Menu' ) );
		$listTriggers[]				=	moscomprofilerHTML::makeOption( 'gj_onCategoryEdit', CBTxt::T( 'Category Edit' ) );
		$listTriggers[]				=	moscomprofilerHTML::makeOption( 'gj_onGroupEdit', CBTxt::T( 'Group Edit' ) );
		$listTriggers[]				=	moscomprofilerHTML::makeOption( 'gj_onConfigIntegrations', CBTxt::T( 'Config Integrations' ) );
		$listTriggers[]				=	moscomprofilerHTML::makeOption( 'gj_onMenusIntegrationsGeneral', CBTxt::T( 'Menus Integrations General' ) );
		$listTriggers[]				=	moscomprofilerHTML::makeOption( 'gj_onMenusIntegrationsCategories', CBTxt::T( 'Menus Integrations Categories' ) );
		$listTriggers[]				=	moscomprofilerHTML::makeOption( 'gj_onMenusIntegrationsGroups', CBTxt::T( 'Menus Integrations Groups' ) );
		$listTriggers[]				=	moscomprofilerHTML::makeOption( 'gj_onMenusIntegrationsSave', CBTxt::T( 'Menus Integrations Save' ) );
		$listTriggers[]				=	moscomprofilerHTML::makeOption( 'gj_onAuthorization', CBTxt::T( 'Authorization' ) );
		$listTriggers[]				=	moscomprofilerHTML::makeOption( 'gj_onCaptchaRender', CBTxt::T( 'Captcha Render' ) );
		$listTriggers[]				=	moscomprofilerHTML::makeOption( 'gj_onCaptchaValidate', CBTxt::T( 'Captcha Validate' ) );
		$listTriggers[]				=	moscomprofilerHTML::makeOption( 'gj_onToolbarBE', CBTxt::T( 'Backend Toolbar' ) );
		$listTriggers[]				=	moscomprofilerHTML::makeOption( 'gj_onBeforeOverviewCategoryMenu', CBTxt::T( 'Before Overview Category Menu' ) );
		$listTriggers[]				=	moscomprofilerHTML::makeOption( 'gj_onAfterOverviewCategoryMenu', CBTxt::T( 'After Overview Category Menu' ) );
		$listTriggers[]				=	moscomprofilerHTML::makeOption( 'gj_onBeforeOverviewCategoryInfo', CBTxt::T( 'Before Overview Category Info' ) );
		$listTriggers[]				=	moscomprofilerHTML::makeOption( 'gj_onAfterOverviewCategoryInfo', CBTxt::T( 'After Overview Category Info' ) );
		$listTriggers[]				=	moscomprofilerHTML::makeOption( 'gj_onBeforeCategoryGroupMenu', CBTxt::T( 'Before Category Group Menu' ) );
		$listTriggers[]				=	moscomprofilerHTML::makeOption( 'gj_onAfterCategoryGroupMenu', CBTxt::T( 'After Category Group Menu' ) );
		$listTriggers[]				=	moscomprofilerHTML::makeOption( 'gj_onBeforeCategoryGroupInfo', CBTxt::T( 'Before Category Group Info' ) );
		$listTriggers[]				=	moscomprofilerHTML::makeOption( 'gj_onAfterCategoryGroupInfo', CBTxt::T( 'After Category Group Info' ) );
		$listTriggers[]				=	moscomprofilerHTML::makeOption( 'gj_onBeforeCategoryTab', CBTxt::T( 'Before Category Tab' ) );
		$listTriggers[]				=	moscomprofilerHTML::makeOption( 'gj_onAfterCategoryTab', CBTxt::T( 'After Category Tab' ) );
		$listTriggers[]				=	moscomprofilerHTML::makeOption( 'gj_onBeforeCategoryInfo', CBTxt::T( 'Before Category Info' ) );
		$listTriggers[]				=	moscomprofilerHTML::makeOption( 'gj_onAfterCategoryInfo', CBTxt::T( 'After Category Info' ) );
		$listTriggers[]				=	moscomprofilerHTML::makeOption( 'gj_onBeforeCategoryMenu', CBTxt::T( 'Before Category Menu' ) );
		$listTriggers[]				=	moscomprofilerHTML::makeOption( 'gj_onAfterCategoryMenu', CBTxt::T( 'After Category Menu' ) );
		$listTriggers[]				=	moscomprofilerHTML::makeOption( 'gj_onBeforeGroupInviteInfo', CBTxt::T( 'Before Group Invite Info' ) );
		$listTriggers[]				=	moscomprofilerHTML::makeOption( 'gj_onBeforeGroupInviteMenu', CBTxt::T( 'Before Group Invite Menu' ) );
		$listTriggers[]				=	moscomprofilerHTML::makeOption( 'gj_onAfterGroupInviteMenu', CBTxt::T( 'After Group Invite Menu' ) );
		$listTriggers[]				=	moscomprofilerHTML::makeOption( 'gj_onAfterGroupInviteInfo', CBTxt::T( 'After Group Invite Info' ) );
		$listTriggers[]				=	moscomprofilerHTML::makeOption( 'gj_onBeforeGroupTab', CBTxt::T( 'Before Group Tab' ) );
		$listTriggers[]				=	moscomprofilerHTML::makeOption( 'gj_onAfterGroupTab', CBTxt::T( 'After Group Tab' ) );
		$listTriggers[]				=	moscomprofilerHTML::makeOption( 'gj_onBeforeGroupInfo', CBTxt::T( 'Before Group Info' ) );
		$listTriggers[]				=	moscomprofilerHTML::makeOption( 'gj_onAfterGroupInfo', CBTxt::T( 'After Group Info' ) );
		$listTriggers[]				=	moscomprofilerHTML::makeOption( 'gj_onBeforeGroupMenu', CBTxt::T( 'Before Group Menu' ) );
		$listTriggers[]				=	moscomprofilerHTML::makeOption( 'gj_onAfterGroupMenu', CBTxt::T( 'After Group Menu' ) );
		$listTriggers[]				=	moscomprofilerHTML::makeOption( 'gj_onBeforeGroupUserMenu', CBTxt::T( 'Before Group User Menu' ) );
		$listTriggers[]				=	moscomprofilerHTML::makeOption( 'gj_onAfterGroupUserMenu', CBTxt::T( 'After Group User Menu' ) );
		$listTriggers[]				=	moscomprofilerHTML::makeOption( 'gj_onBeforeGroupUserInfo', CBTxt::T( 'Before Group User Info' ) );
		$listTriggers[]				=	moscomprofilerHTML::makeOption( 'gj_onAfterGroupUserInfo', CBTxt::T( 'After Group User Info' ) );
		$listTriggers[]				=	moscomprofilerHTML::makeOption( 'gj_onGeneralNotifications', CBTxt::T( 'General Notifications' ) );
		$listTriggers[]				=	moscomprofilerHTML::makeOption( 'gj_onCategoryNotifications', CBTxt::T( 'Category Notifications' ) );
		$listTriggers[]				=	moscomprofilerHTML::makeOption( 'gj_onGroupNotifications', CBTxt::T( 'Group Notifications' ) );
		$listTriggers[]				=	moscomprofilerHTML::makeOption( 'gj_onBeforeOverviewInfo', CBTxt::T( 'Before Overview Info' ) );
		$listTriggers[]				=	moscomprofilerHTML::makeOption( 'gj_onAfterOverviewInfo', CBTxt::T( 'After Overview Info' ) );
		$listTriggers[]				=	moscomprofilerHTML::makeOption( 'gj_onBeforeOverviewMenu', CBTxt::T( 'Before Overview Menu' ) );
		$listTriggers[]				=	moscomprofilerHTML::makeOption( 'gj_onAfterOverviewMenu', CBTxt::T( 'After Overview Menu' ) );
		$listTriggers[]				=	moscomprofilerHTML::makeOption( 'gj_onBeforePanelInfo', CBTxt::T( 'Before Panel Info' ) );
		$listTriggers[]				=	moscomprofilerHTML::makeOption( 'gj_onAfterPanelInfo', CBTxt::T( 'After Panel Info' ) );
		$listTriggers[]				=	moscomprofilerHTML::makeOption( 'gj_onBeforePanelMenu', CBTxt::T( 'Before Panel Menu' ) );
		$listTriggers[]				=	moscomprofilerHTML::makeOption( 'gj_onAfterPanelMenu', CBTxt::T( 'After Panel Menu' ) );
		$listTriggers[]				=	moscomprofilerHTML::makeOption( 'gj_onBeforeProfileTab', CBTxt::T( 'Before Profile Tab' ) );
		$listTriggers[]				=	moscomprofilerHTML::makeOption( 'gj_onAfterProfileTab', CBTxt::T( 'After Profile Tab' ) );
		$listTriggers[]				=	moscomprofilerHTML::makeOption( 'gj_onBeforeProfileOverviewCategoryMenu', CBTxt::T( 'Before Profile Overview Category Menu' ) );
		$listTriggers[]				=	moscomprofilerHTML::makeOption( 'gj_onAfterProfileOverviewCategoryMenu', CBTxt::T( 'After Profile Overview Category Menu' ) );
		$listTriggers[]				=	moscomprofilerHTML::makeOption( 'gj_onBeforeProfileOverviewCategoryInfo', CBTxt::T( 'Before Profile Overview Category Info' ) );
		$listTriggers[]				=	moscomprofilerHTML::makeOption( 'gj_onAfterProfileOverviewCategoryInfo', CBTxt::T( 'After Profile Overview Category Info' ) );
		$listTriggers[]				=	moscomprofilerHTML::makeOption( 'gj_onBeforeProfileCategoryGroupMenu', CBTxt::T( 'Before Profile Category Group Menu' ) );
		$listTriggers[]				=	moscomprofilerHTML::makeOption( 'gj_onAfterProfileCategoryGroupMenu', CBTxt::T( 'After Profile Category Group Menu' ) );
		$listTriggers[]				=	moscomprofilerHTML::makeOption( 'gj_onBeforeProfileCategoryGroupInfo', CBTxt::T( 'Before Profile Category Group Info' ) );
		$listTriggers[]				=	moscomprofilerHTML::makeOption( 'gj_onAfterProfileCategoryGroupInfo', CBTxt::T( 'After Profile Category Group Info' ) );
		$listTriggers[]				=	moscomprofilerHTML::makeOption( 'gj_onBeforeProfileGroupInvitedMenu', CBTxt::T( 'Before Profile Group Invited Menu' ) );
		$listTriggers[]				=	moscomprofilerHTML::makeOption( 'gj_onAfterProfileGroupInvitedMenu', CBTxt::T( 'After Profile Group Invited Menu' ) );
		$listTriggers[]				=	moscomprofilerHTML::makeOption( 'gj_onBeforeProfileGroupInvitedInfo', CBTxt::T( 'Before Profile Group Invited Info' ) );
		$listTriggers[]				=	moscomprofilerHTML::makeOption( 'gj_onAfterProfileGroupInvitedInfo', CBTxt::T( 'After Profile Group Invited Info' ) );
		$listTriggers[]				=	moscomprofilerHTML::makeOption( 'gj_onBeforeProfileGroupInviteInfo', CBTxt::T( 'Before Profile Group Invite Info' ) );
		$listTriggers[]				=	moscomprofilerHTML::makeOption( 'gj_onBeforeProfileGroupInviteMenu', CBTxt::T( 'Before Profile Group Invite Menu' ) );
		$listTriggers[]				=	moscomprofilerHTML::makeOption( 'gj_onAfterProfileGroupInviteMenu', CBTxt::T( 'After Profile Group Invite Menu' ) );
		$listTriggers[]				=	moscomprofilerHTML::makeOption( 'gj_onAfterProfileGroupInviteInfo', CBTxt::T( 'After Profile Group Invite Info' ) );
		$listTriggers[]				=	moscomprofilerHTML::makeOptGroup( CBTxt::T( 'CB Paid Subscriptions' ) );
		$listTriggers[]				=	moscomprofilerHTML::makeOption( 'onCPayAfterPlanRenewalSelected', CBTxt::T( 'After Plan Renewal Selected' ) );
		$listTriggers[]				=	moscomprofilerHTML::makeOption( 'onCPayEditBasketIntegration', CBTxt::T( 'Edit Basket Integration' ) );
		$listTriggers[]				=	moscomprofilerHTML::makeOption( 'onCbSubsAfterPaymentBasket', CBTxt::T( 'After Payment Basket' ) );
		$listTriggers[]				=	moscomprofilerHTML::makeOption( 'onCPayBeforeDrawSomething', CBTxt::T( 'Before Draw Something' ) );
		$listTriggers[]				=	moscomprofilerHTML::makeOption( 'onCPayAfterDrawSomething', CBTxt::T( 'After Draw Something' ) );
		$listTriggers[]				=	moscomprofilerHTML::makeOption( 'onCPayBeforeDrawPlan', CBTxt::T( 'Before Draw Plan' ) );
		$listTriggers[]				=	moscomprofilerHTML::makeOption( 'onCPayAfterDrawPlan', CBTxt::T( 'After Draw Plan' ) );
		$listTriggers[]				=	moscomprofilerHTML::makeOption( 'onCPayAfterPlanSelected', CBTxt::T( 'After Plan Selected' ) );
		$listTriggers[]				=	moscomprofilerHTML::makeOption( 'onCPayAfterPaymentStatusChange', CBTxt::T( 'After Payment Status Change' ) );
		$listTriggers[]				=	moscomprofilerHTML::makeOption( 'onCPayAfterPaymentStatusUpdateEvent', CBTxt::T( 'After Payment Status Update Event' ) );
		$listTriggers[]				=	moscomprofilerHTML::makeOption( 'onCPayBeforeBackendPlanDisplay', CBTxt::T( 'Before Backend Plan Display' ) );
		$listTriggers[]				=	moscomprofilerHTML::makeOption( 'onCPayBeforeDisplayProductPeriodPrice', CBTxt::T( 'Before Display Product Period Price' ) );
		$listTriggers[]				=	moscomprofilerHTML::makeOption( 'onCPayAfterDisplayProductPeriodPrice', CBTxt::T( 'After Display Product Period Price' ) );
		$listTriggers[]				=	moscomprofilerHTML::makeOption( 'onCPayBeforeComputeTotalizersPaymentBasketUpdated', CBTxt::T( 'Before Compute Totalizers Payment Basket Updated' ) );
		$listTriggers[]				=	moscomprofilerHTML::makeOption( 'onCPayBeforeStorePaymentBasketUpdated', CBTxt::T( 'Before Store Payment Basket Updated' ) );
		$listTriggers[]				=	moscomprofilerHTML::makeOption( 'onCPayAfterPaymentBasketUpdated', CBTxt::T( 'After Payment Basket Updated' ) );
		$listTriggers[]				=	moscomprofilerHTML::makeOption( 'onCPayPaymentItemEvent', CBTxt::T( 'Payment Item Event' ) );
		$listTriggers[]				=	moscomprofilerHTML::makeOption( 'onCPayBeforeGetProductPrice', CBTxt::T( 'Before Get Product Price' ) );
		$listTriggers[]				=	moscomprofilerHTML::makeOption( 'onCPayAfterGetProductPrice', CBTxt::T( 'After Get Product Price' ) );
		$listTriggers[]				=	moscomprofilerHTML::makeOption( 'onCPayBeforeDisplaySubscriptionPeriodPrice', CBTxt::T( 'Before Display Subscription Period Price' ) );
		$listTriggers[]				=	moscomprofilerHTML::makeOption( 'onCPayAfterDisplaySubscriptionPeriodPrice', CBTxt::T( 'After Display Subscription Period Price' ) );
		$listTriggers[]				=	moscomprofilerHTML::makeOption( 'onCPayUserStateChange', CBTxt::T( 'User State Change' ) );
		$listTriggers[]				=	moscomprofilerHTML::makeOption( 'onCPayBeforeMailerEmailMessageSent', CBTxt::T( 'Before Mailer Email Message Sent' ) );
		$listTriggers[]				=	moscomprofilerHTML::makeOption( 'onCPayBeforeMailerPrivateMessageSent', CBTxt::T( 'Before Mailer Private Message Sent' ) );
		$listTriggers[]				=	moscomprofilerHTML::makeOption( 'onCPayAfterMailerMessageSent', CBTxt::T( 'After Mailer Message Sent' ) );
		$listTriggers[]				=	moscomprofilerHTML::makeOption( 'onCPayBeforeDrawSubscription', CBTxt::T( 'Before Draw Subscription' ) );
		$listTriggers[]				=	moscomprofilerHTML::makeOptGroup( CBTxt::T( 'CB Activity' ) );
		$listTriggers[]				=	moscomprofilerHTML::makeOption( 'activity_onBeforeMessageDisplay', CBTxt::T( 'Before Message Display' ) );
		$listTriggers[]				=	moscomprofilerHTML::makeOption( 'activity_onAfterMessageDisplay', CBTxt::T( 'After Message Display' ) );
		$listTriggers[]				=	moscomprofilerHTML::makeOption( 'activity_onBeforeItemDisplay', CBTxt::T( 'Before Item Display' ) );
		$listTriggers[]				=	moscomprofilerHTML::makeOption( 'activity_onAfterItemDisplay', CBTxt::T( 'After Item Display' ) );
		$listTriggers[]				=	moscomprofilerHTML::makeOption( 'activity_onBeforeUpdateActivity', CBTxt::T( 'Before Update Activity' ) );
		$listTriggers[]				=	moscomprofilerHTML::makeOption( 'activity_onBeforeCreateActivity', CBTxt::T( 'Before Create Activity' ) );
		$listTriggers[]				=	moscomprofilerHTML::makeOption( 'activity_onAfterUpdateActivity', CBTxt::T( 'After Update Activity' ) );
		$listTriggers[]				=	moscomprofilerHTML::makeOption( 'activity_onAfterCreateActivity', CBTxt::T( 'After Create Activity' ) );
		$listTriggers[]				=	moscomprofilerHTML::makeOption( 'activity_onBeforeDeleteActivity', CBTxt::T( 'Before Delete Activity' ) );
		$listTriggers[]				=	moscomprofilerHTML::makeOption( 'activity_onAfterDeleteActivity', CBTxt::T( 'After Delete Activity' ) );
		$listTriggers[]				=	moscomprofilerHTML::makeOption( 'activity_onBeforeAccess', CBTxt::T( 'Before Access' ) );
		$listTriggers[]				=	moscomprofilerHTML::makeOption( 'activity_onAfterAccess', CBTxt::T( 'After Access' ) );
		$input['triggers']			=	moscomprofilerHTML::selectList( $listTriggers, 'triggers', null, 'value', 'text', null, 1, false, false );

		$input['trigger']			=	'<input type="text" id="trigger" name="trigger" value="' . htmlspecialchars( str_replace( ' ', '', cbgjClass::getCleanParam( true, 'trigger', $row->get( 'trigger' ) ) ) ) . '" class="inputbox" size="40" />';

		$listObject					=	array();
		$listObject[]				=	moscomprofilerHTML::makeOption( '0', CBTxt::T( 'Automatic' ) );
		$listObject[]				=	moscomprofilerHTML::makeOption( '1', CBTxt::T( 'Manually' ) );
		$listObject[]				=	moscomprofilerHTML::makeOption( '2', CBTxt::T( 'User' ) );
		$listObject[]				=	moscomprofilerHTML::makeOption( '3', CBTxt::T( 'Specific' ) );
		$input['object']			=	moscomprofilerHTML::selectList( $listObject, 'object', null, 'value', 'text', (int) cbgjClass::getCleanParam( true, 'object', $row->get( 'object', 0 ) ), 1, false, false );

		$listVariable				=	array();
		$listVariable[]				=	moscomprofilerHTML::makeOption( '1', CBTxt::T( 'Variable 1' ) );
		$listVariable[]				=	moscomprofilerHTML::makeOption( '2', CBTxt::T( 'Variable 2' ) );
		$listVariable[]				=	moscomprofilerHTML::makeOption( '3', CBTxt::T( 'Variable 3' ) );
		$listVariable[]				=	moscomprofilerHTML::makeOption( '4', CBTxt::T( 'Variable 4' ) );
		$listVariable[]				=	moscomprofilerHTML::makeOption( '5', CBTxt::T( 'Variable 5' ) );
		$listVariable[]				=	moscomprofilerHTML::makeOption( '6', CBTxt::T( 'Variable 6' ) );
		$listVariable[]				=	moscomprofilerHTML::makeOption( '7', CBTxt::T( 'Variable 7' ) );
		$listVariable[]				=	moscomprofilerHTML::makeOption( '8', CBTxt::T( 'Variable 8' ) );
		$listVariable[]				=	moscomprofilerHTML::makeOption( '9', CBTxt::T( 'Variable 9' ) );
		$listVariable[]				=	moscomprofilerHTML::makeOption( '10', CBTxt::T( 'Variable 10' ) );
		$input['variable']			=	moscomprofilerHTML::selectList( $listVariable, 'variable', null, 'value', 'text', (int) cbgjClass::getCleanParam( true, 'variable', $row->get( 'variable', 0 ) ), 1, false, false );

		$input['variable_user']		=	'<input type="text" id="variable_user" name="variable_user" value="' . htmlspecialchars( cbgjClass::getCleanParam( true, 'variable', $row->get( 'variable' ) ) ) . '" class="inputbox" size="5" />';

		$listAccess					=	array();
		$listAccess[]				=	moscomprofilerHTML::makeOptGroup( CBTxt::T( 'Custom ACL' ) );
		$listAccess[]				=	moscomprofilerHTML::makeOption( '-1', CBTxt::T( 'Everybody' ) );
		$listAccess[]				=	moscomprofilerHTML::makeOption( '-2', CBTxt::T( 'All Non-Registered Users' ) );
		$listAccess[]				=	moscomprofilerHTML::makeOption( '-3', CBTxt::T( 'All Registered Users' ) );
		$listAccess[]				=	moscomprofilerHTML::makeOption( '-4', CBTxt::T( 'All Non-Moderators' ) );
		$listAccess[]				=	moscomprofilerHTML::makeOption( '-5', CBTxt::T( 'All Moderators' ) );
		$listAccess[]				=	moscomprofilerHTML::makeOptGroup( CBTxt::T( 'CMS ACL' ) );
		$listAccess					=	array_merge( $listAccess, $_CB_framework->acl->get_group_children_tree( null, 'USERS', false ) );
		$input['access']			=	moscomprofilerHTML::selectList( $listAccess, 'access[]', 'size="6" multiple="multiple"', 'value', 'text', explode( '|*|', cbgjClass::getCleanParam( true, 'access', $row->get( 'access' ) ) ), 1, false, false );

		$listOperator				=	array();
		$listOperator[]				=	moscomprofilerHTML::makeOption( '', CBTxt::T( '- Select Operator -' ) );
		$listOperator[]				=	moscomprofilerHTML::makeOption( '0', CBTxt::T( 'Equal To' ) );
		$listOperator[]				=	moscomprofilerHTML::makeOption( '1', CBTxt::T( 'Not Equal To' ) );
		$listOperator[]				=	moscomprofilerHTML::makeOption( '2', CBTxt::T( 'Greater Than' ) );
		$listOperator[]				=	moscomprofilerHTML::makeOption( '3', CBTxt::T( 'Less Than' ) );
		$listOperator[]				=	moscomprofilerHTML::makeOption( '4', CBTxt::T( 'Greater Than or Equal To' ) );
		$listOperator[]				=	moscomprofilerHTML::makeOption( '5', CBTxt::T( 'Less Than or Equal To' ) );
		$listOperator[]				=	moscomprofilerHTML::makeOption( '6', CBTxt::T( 'Empty' ) );
		$listOperator[]				=	moscomprofilerHTML::makeOption( '7', CBTxt::T( 'Not Empty' ) );
		$listOperator[]				=	moscomprofilerHTML::makeOption( '8', CBTxt::T( 'Does Contain' ) );
		$listOperator[]				=	moscomprofilerHTML::makeOption( '9', CBTxt::T( 'Does Not Contain' ) );
		$listOperator[]				=	moscomprofilerHTML::makeOption( '10', CBTxt::T( 'Is REGEX' ) );
		$listOperator[]				=	moscomprofilerHTML::makeOption( '11', CBTxt::T( 'Is Not REGEX' ) );

		$conditionals				=	count( explode( "\n", $row->get( 'field' ) ) );

		for ( $i = 0, $n = ( $conditionals ? $conditionals : 1 ); $i < $n; $i++ ) {
			$operator				=	cbgjClass::getCleanParam( true, "operator$i", null, null, $row->get( 'operator' ) );

			$input["field$i"]		=	'<input type="text" id="fields__field' . $i . '" name="fields[field' . $i . ']" value="' . htmlspecialchars( cbgjClass::getHTMLCleanParam( true, "field$i", null, null, $row->get( 'field' ) ) ) . '" class="inputbox" size="25" />';
			$input["operator$i"]	=	moscomprofilerHTML::selectList( $listOperator, "operators[operator$i]", null, 'value', 'text', $operator, 1, false, false );
			$input["value$i"]		=	'<input type="text" id="values__' . $i . '" name="values[value' . $i . ']" value="' . htmlspecialchars( cbgjClass::getHTMLCleanParam( true, "value$i", null, null, $row->get( 'value' ) ) ) . '" class="inputbox' . ( in_array( $operator, array( 6, 7 ) ) ? ' gjautoHide' : null ) . '" size="25" />';
		}

		$input['conditionals']		=	( $conditionals ? $conditionals : 1 );

		$listAuto					=	array();
		$listAuto[]					=	moscomprofilerHTML::makeOption( '', CBTxt::T( '- Select Auto -' ) );
		$listAuto[]					=	moscomprofilerHTML::makeOption( '1', CBTxt::T( 'Join Groups' ) );
		$listAuto[]					=	moscomprofilerHTML::makeOption( '4', CBTxt::T( 'Leave Groups' ) );
		$listAuto[]					=	moscomprofilerHTML::makeOption( '2', CBTxt::T( 'New Group' ) );
		$listAuto[]					=	moscomprofilerHTML::makeOption( '3', CBTxt::T( 'New Category' ) );
		$input['auto']				=	moscomprofilerHTML::selectList( $listAuto, 'params[auto]', null, 'value', 'text', $params->get( 'auto' ), 1, false, false );

		$listGroups					=	array();

		$categories					=	cbgjData::getCategories();

		if ( $categories ) foreach ( $categories as $cat ) {
			$groups					=	cbgjData::listArray( cbgjData::getGroups( null, array( 'category', '=', $cat->get( 'id' ) ) ) );

			if ( $groups ) {
				$listGroups[]		=	moscomprofilerHTML::makeOptGroup( $cat->get( 'name' ) );

				foreach ( $groups as $grp ) {
					$listGroups[]	=	moscomprofilerHTML::makeOption( $grp->value, $grp->text );
				}
			}
		}

		if ( $listGroups ) {
			$input['groups']		=	moscomprofilerHTML::selectList( $listGroups, 'params[groups][]', 'size="6" multiple="multiple"', 'value', 'text', explode( '|*|', $params->get( 'groups', '1|*|2|*|3' ) ), 1, false, false );

			array_unshift( $listGroups, moscomprofilerHTML::makeOption( '0', CBTxt::T( 'No Parent' ) ) );

			$input['grp_parent']	=	moscomprofilerHTML::selectList( $listGroups, 'params[grp_parent]', null, 'value', 'text', $params->get( 'grp_parent', 0 ), 1, false, false );
		} else {
			$input['groups']		=	CBTxt::T( 'No groups exist!' );
			$input['grp_parent']	=	CBTxt::T( 'No groups exist!' );
		}

		$listStatus					=	array();
		$listStatus[]				=	moscomprofilerHTML::makeOption( '-1', CBTxt::T( 'Banned' ) );
		$listStatus[]				=	moscomprofilerHTML::makeOption( '0', CBTxt::T( 'Inactive' ) );
		$listStatus[]				=	moscomprofilerHTML::makeOption( '1', CBTxt::T( 'Active' ) );
		$listStatus[]				=	moscomprofilerHTML::makeOption( '2', CBTxt::T( 'Moderator' ) );
		$listStatus[]				=	moscomprofilerHTML::makeOption( '3', CBTxt::T( 'Admin' ) );
		$listStatus[]				=	moscomprofilerHTML::makeOption( '4', CBTxt::T( 'Owner' ) );
		$input['status']			=	moscomprofilerHTML::selectList( $listStatus, 'params[status]', null, 'value', 'text', $params->get( 'status', 1 ), 1, false, false );

		if ( $categories ) {
			$categories				=	cbgjData::listArray( $categories );
			$categoriesParents		=	$categories;

			array_unshift( $categories, moscomprofilerHTML::makeOption( '-1', CBTxt::T( 'New Category' ) ) );
			array_unshift( $categories, moscomprofilerHTML::makeOption( '', CBTxt::T( '- Select Category -' ) ) );

			$input['category']		=	moscomprofilerHTML::selectList( $categories, 'params[category]', null, 'value', 'text', $params->get( 'category' ), 1, false, false );

			array_unshift( $categoriesParents, moscomprofilerHTML::makeOption( '0', CBTxt::T( 'No Parent' ) ) );

			$input['cat_parent']	=	moscomprofilerHTML::selectList( $categoriesParents, 'params[cat_parent]', null, 'value', 'text', $params->get( 'cat_parent', 0 ), 1, false, false );
		} else {
			$input['category']		=	CBTxt::T( 'No categories exist!' );
			$input['cat_parent']	=	CBTxt::T( 'No categories exist!' );
		}

		$input['cat_name']			=	'<input type="text" id="params__cat_name" name="params[cat_name]" value="' . htmlspecialchars( $params->get( 'cat_name' ) ) . '" class="inputbox" size="40" />';
		$input['cat_description']	=	'<textarea id="params__cat_description" name="params[cat_description]" class="inputbox" cols="40" rows="5">' . htmlspecialchars( $params->get( 'cat_description' ) ) . '</textarea>';
		$input['cat_owner']			=	'<input type="text" id="params__cat_owner" name="params[cat_owner]" value="' . htmlspecialchars( $params->get( 'cat_owner' ) ) . '" class="inputbox" size="10" />';
		$input['cat_unique']		=	moscomprofilerHTML::yesnoSelectList( 'params[cat_unique]', null, $params->get( 'cat_unique', 1 ), CBTxt::T( 'Enable' ), CBTxt::T( 'Disable' ) );

		$listTypes					=	array();
		$listTypes[]				=	moscomprofilerHTML::makeOption( '1', CBTxt::T( 'Open' ) );
		$listTypes[]				=	moscomprofilerHTML::makeOption( '2', CBTxt::T( 'Approval' ) );
		$listTypes[]				=	moscomprofilerHTML::makeOption( '3', CBTxt::T( 'Invite' ) );
		$input['types']				=	moscomprofilerHTML::selectList( $listTypes, 'params[types][]', 'size="4" multiple="multiple"', 'value', 'text', explode( '|*|', $params->get( 'types', '1|*|2|*|3' ) ), 1, false, false );

		$input['grp_name']			=	'<input type="text" id="params__grp_name" name="params[grp_name]" value="' . htmlspecialchars( $params->get( 'grp_name' ) ) . '" class="inputbox" size="40" />';
		$input['grp_description']	=	'<textarea id="params__grp_description" name="params[grp_description]" class="inputbox" cols="40" rows="5">' . htmlspecialchars( $params->get( 'grp_description' ) ) . '</textarea>';
		$input['grp_owner']			=	'<input type="text" id="params__grp_owner" name="params[grp_owner]" value="' . htmlspecialchars( $params->get( 'grp_owner' ) ) . '" class="inputbox" size="10" />';
		$input['grp_unique']		=	moscomprofilerHTML::yesnoSelectList( 'params[grp_unique]', null, $params->get( 'grp_unique', 1 ), CBTxt::T( 'Enable' ), CBTxt::T( 'Disable' ) );
		$input['grp_autojoin']		=	moscomprofilerHTML::yesnoSelectList( 'params[grp_autojoin]', null, $params->get( 'grp_autojoin', 1 ), CBTxt::T( 'Enable' ), CBTxt::T( 'Disable' ) );
		$input['type']				=	moscomprofilerHTML::selectList( $listTypes, 'params[type]', null, 'value', 'text', $params->get( 'type', 1 ), 1, false, false );
		$input['exclude']			=	'<input type="text" id="exclude" name="exclude" value="' . htmlspecialchars( $params->get( 'exclude' ) ) . '" class="inputbox" size="20" />';

		cbgjClass::displayMessage( $message );

		if ( class_exists( 'HTML_cbgroupjiveautoEdit' ) ) {
			HTML_cbgroupjiveautoEdit::showAutoEdit( $row, $input, $user, $plugin );
		} else {
			$this->showAutoEdit( $row, $input, $user, $plugin );
		}
	}

	private function showAutoEdit( $row, $input, $user, $plugin ) {
		global $_CB_framework;

		if ( $row->get( 'id' ) ) {
			$title		=	CBTxt::P( 'Auto: <small>Edit [[trigger]] ([id])</small>', array( '[trigger]' => htmlspecialchars( str_replace( ' ', '', $row->get( 'trigger' ) ) ), '[id]' => $row->get( 'id' ) ) );
		} else {
			$title		=	CBTxt::T( 'Auto: <small>New</small>' );
		}

		HTML_cbgjAdmin::setTitle( $title, 'cbicon-48-gjauto' );

		$return			=	'<form action="' . $_CB_framework->backendUrl( 'index.php' ) . '" method="post" name="adminForm" enctype="multipart/form-data" id="adminForm" class="adminform">'
						.		'<table class="adminlist" width="100%" cellspacing="0" cellpadding="4" border="0">'
						.			'<tbody>'
						.				'<tr>'
						.					'<th width="15%">' . CBTxt::Th( 'Published' ) . '</th>'
						.					'<td width="40%">' . $input['published'] . '</td>'
						.					'<td>' . CBTxt::Th( 'Select publish status of auto. Unpublished auto action will not execute.' ) . '</td>'
						.				'</tr>'
						.				'<tr>'
						.					'<th width="15%">' . CBTxt::T( 'Title' ) . '</th>'
						.					'<td width="40%">' . $input['title'] . '</td>'
						.					'<td>' . CBTxt::T( 'Optionally input title to display on auto list in replace of Triggers list.' ) . '</td>'
						.				'</tr>'
						.				'<tr>'
						.					'<th width="15%">' . CBTxt::T( 'Description' ) . '</th>'
						.					'<td width="40%">' . $input['description'] . '</td>'
						.					'<td>' . CBTxt::T( 'Optionally input description to display on auto list on mouseover of Triggers list or Title.' ) . '</td>'
						.				'</tr>'
						.				'<tr>'
						.					'<th width="15%"><div>' . CBTxt::Th( 'Triggers' ) . '</div><div><small>' . CBTxt::Th( '(required)' ) . '</small></div></th>'
						.					'<td width="40%">' . $input['trigger'] . ' ' . $input['triggers'] . '</td>'
						.					'<td>' . CBTxt::Th( 'Input comma separated list of triggers or select trigger to append (e.g. onAfterLogin).' ) . '</td>'
						.				'</tr>'
						.				'<tr>'
						.					'<th width="15%"><div>' . CBTxt::Th( 'User' ) . '</div><div><small>' . CBTxt::Th( '(required)' ) . '</small></div></th>'
						.					'<td width="40%">' . $input['object'] . ' ' . $input['variable'] . ' ' . $input['variable_user'] . '</td>'
						.					'<td>' . CBTxt::Th( 'Select if user object should be automatically obtained from trigger variables, if manually selected variable should be used (e.g. function login( $var1, $var2, $var3 ) {...), if user executing the trigger, or if specified user id should be used. User variable determines substitution target (e.g. [username] will output the User username). Regardless of User selection you can substitute in other variables using [var1], [var2], [var3], etc.. (for arrays/objects use [var1_VARIABLE]; example: [var2_username]). In addition to trigger variables you can access $_GET and $_POST substitutions as post_VARIABLE and get_VARIABLE (e.g. get_task).' ) . '</td>'
						.				'</tr>'
						.				'<tr>'
						.					'<th width="15%"><div>' . CBTxt::Th( 'Access' ) . '</div><div><small>' . CBTxt::Th( '(required)' ) . '</small></div></th>'
						.					'<td width="40%">' . $input['access'] . '</td>'
						.					'<td>' . CBTxt::Th( 'Select access to auto; only groups selected will have access. Parent groups such as Public Front-end will NOT fire for Registered users; exact groups must be selected.' ) . '</td>'
						.				'</tr>'
						.				'<tr id="conditional">'
						.					'<th width="15%">' . CBTxt::Th( 'Conditional' ) . '</th>'
						.					'<td width="40%">'
						.						'<table class="adminlist" width="100%" cellspacing="0" cellpadding="4" border="0">'
						.							'<tbody>';

		for ( $i = 0, $n = $input['conditionals']; $i < $n; $i++ ) {
			$return		.=								'<tr>'
						.									'<td width="5%" style="text-align:center;"><div class="moveConditional ui-icon ui-icon-arrowthick-2-n-s" title="' . htmlspecialchars( CBTxt::T( 'Click and drag to move this conditional.' ) ) . '">#</div></td>'
						.									'<td width="30%" style="text-align:center;" class="conditionalField">' . $input["field$i"] . '</td>'
						.									'<td width="30%" style="text-align:center;" class="conditionalOperator">' . $input["operator$i"] . '</td>'
						.									'<td width="30%" style="text-align:center;" class="conditionalValue">' . $input["value$i"] . '</td>'
						.									'<td width="5%" style="text-align:center;">'
						.										'<div class="addConditional ui-icon ui-icon-plus" title="' . htmlspecialchars( CBTxt::T( 'Click to add new conditional.' ) ) . '">+</div>'
						.										'<div class="removeConditional ui-icon ui-icon-minus" title="' . htmlspecialchars( CBTxt::T( 'Click to remove this conditional.' ) ) . '">-</div>'
						.									'</td>'
						.								'</tr>';
		}

		$return			.=							'</tbody>'
						.						'</table>'
						.					'</td>'
						.					'<td>' . CBTxt::Th( 'Optionally input substitution supported conditional from one value to another. If condition is not met then auto will not be executed.' ) . '</td>'
						.				'</tr>'
						.				'<tr>'
						.					'<th width="15%"><div>' . CBTxt::Th( 'Auto' ) . '</div><div><small>' . CBTxt::Th( '(required)' ) . '</small></div></th>'
						.					'<td width="40%">' . $input['auto'] . '</td>'
						.					'<td>' . CBTxt::Th( 'Select type of auto. Auto determines what type of action is performed automatically.' ) . '</td>'
						.				'</tr>'
						.				'<tr id="list_groups">'
						.					'<th width="15%"><div>' . CBTxt::Th( 'Groups' ) . '</div><div><small>' . CBTxt::Th( '(required)' ) . '</small></div></th>'
						.					'<td width="40%">' . $input['groups'] . '</td>'
						.					'<td>' . CBTxt::Th( 'Select groups to automatically join/leave.' ) . '</td>'
						.				'</tr>'
						.				'<tr>'
						.					'<th width="15%"><div>' . CBTxt::Th( 'Status' ) . '</div><div><small>' . CBTxt::Th( '(required)' ) . '</small></div></th>'
						.					'<td width="40%">' . $input['status'] . '</td>'
						.					'<td>' . CBTxt::Th( 'Select status of the user for the specified groups.' ) . '</td>'
						.				'</tr>'
						.				'<tr id="list_category">'
						.					'<th width="15%"><div>' . CBTxt::Th( 'Category' ) . '</div><div><small>' . CBTxt::Th( '(required)' ) . '</small></div></th>'
						.					'<td width="40%">' . $input['category'] . '</td>'
						.					'<td>' . CBTxt::Th( 'Select category for the new group.' ) . '</td>'
						.				'</tr>'
						.				'<tr>'
						.					'<th width="15%"><div>' . CBTxt::Th( 'Category Parent' ) . '</div><div><small>' . CBTxt::Th( '(required)' ) . '</small></div></th>'
						.					'<td width="40%">' . $input['cat_parent'] . '</td>'
						.					'<td>' . CBTxt::Th( 'Select category parent of new category.' ) . '</td>'
						.				'</tr>'
						.				'<tr>'
						.					'<th width="15%"><div>' . CBTxt::Th( 'Category Name' ) . '</div><div><small>' . CBTxt::Th( '(required)' ) . '</small></div></th>'
						.					'<td width="40%">' . $input['cat_name'] . '</td>'
						.					'<td>' . CBTxt::Th( 'Input substitution supported new category (e.g. [username]).' ) . '</td>'
						.				'</tr>'
						.				'<tr>'
						.					'<th width="15%">' . CBTxt::Th( 'Category Description' ) . '</th>'
						.					'<td width="40%">' . $input['cat_description'] . '</td>'
						.					'<td>' . CBTxt::Th( 'Optionally input substitution supported new category (e.g. [name]).' ) . '</td>'
						.				'</tr>'
						.				'<tr>'
						.					'<th width="15%"><div>' . CBTxt::Th( 'Category Types' ) . '</div><div><small>' . CBTxt::Th( '(required)' ) . '</small></div></th>'
						.					'<td width="40%">' . $input['types'] . '</td>'
						.					'<td>' . CBTxt::Th( 'Select categories available group types. Types determine the way a groups is joined (e.g. Invite requires new users to be invited to join a group).' ) . '</td>'
						.				'</tr>'
						.				'<tr>'
						.					'<th width="15%">' . CBTxt::Th( 'Category Owner' ) . '</th>'
						.					'<td width="40%">' . $input['cat_owner'] . '</td>'
						.					'<td>' . CBTxt::T( 'Optionally input substitution supported category owner override. If left blank the user triggering the auto will be used.' ) . '</td>'
						.				'</tr>'
						.				'<tr>'
						.					'<th width="15%">' . CBTxt::Th( 'Category Unique' ) . '</th>'
						.					'<td width="40%">' . $input['cat_unique'] . '</td>'
						.					'<td>' . CBTxt::Th( 'Select if category should be unique to the user. When checking if a category exists by name it will also check if exists by user id. If disabled only name is checked.' ) . '</td>'
						.				'</tr>'
						.				'<tr>'
						.					'<th width="15%"><div>' . CBTxt::Th( 'Group Parent' ) . '</div><div><small>' . CBTxt::Th( '(required)' ) . '</small></div></th>'
						.					'<td width="40%">' . $input['grp_parent'] . '</td>'
						.					'<td>' . CBTxt::Th( 'Select parent group of new group.' ) . '</td>'
						.				'</tr>'
						.				'<tr>'
						.					'<th width="15%"><div>' . CBTxt::Th( 'Group Name' ) . '</div><div><small>' . CBTxt::Th( '(required)' ) . '</small></div></th>'
						.					'<td width="40%">' . $input['grp_name'] . '</td>'
						.					'<td>' . CBTxt::Th( 'Input substitution supported new group (e.g. [username]).' ) . '</td>'
						.				'</tr>'
						.				'<tr>'
						.					'<th width="15%">' . CBTxt::Th( 'Group Description' ) . '</th>'
						.					'<td width="40%">' . $input['grp_description'] . '</td>'
						.					'<td>' . CBTxt::Th( 'Optionally input substitution supported new group (e.g. [name]).' ) . '</td>'
						.				'</tr>'
						.				'<tr>'
						.					'<th width="15%"><div>' . CBTxt::Th( 'Group Type' ) . '</div><div><small>' . CBTxt::Th( '(required)' ) . '</small></div></th>'
						.					'<td width="40%">' . $input['type'] . '</td>'
						.					'<td>' . CBTxt::Th( 'Select group type. Type determines the way your group is joined (e.g. Invite requires new users to be invited to join your group).' ) . '</td>'
						.				'</tr>'
						.				'<tr>'
						.					'<th width="15%">' . CBTxt::Th( 'Group Owner' ) . '</th>'
						.					'<td width="40%">' . $input['grp_owner'] . '</td>'
						.					'<td>' . CBTxt::T( 'Optionally input substitution supported group owner override. If left blank the user triggering the auto will be used.' ) . '</td>'
						.				'</tr>'
						.				'<tr>'
						.					'<th width="15%">' . CBTxt::Th( 'Group Unique' ) . '</th>'
						.					'<td width="40%">' . $input['grp_unique'] . '</td>'
						.					'<td>' . CBTxt::Th( 'Select if groups should be unique to the user. When checking if a group exists by name it will also check if exists by user id. If disabled only name is checked.' ) . '</td>'
						.				'</tr>'
						.				'<tr>'
						.					'<th width="15%">' . CBTxt::Th( 'Auto Join' ) . '</th>'
						.					'<td width="40%">' . $input['grp_autojoin'] . '</td>'
						.					'<td>' . CBTxt::Th( 'Select if group should be automatically joined if duplicate found.' ) . '</td>'
						.				'</tr>'
						.				'<tr>'
						.					'<th width="15%">' . CBTxt::Th( 'Exclude' ) . '</th>'
						.					'<td width="40%">' . $input['exclude'] . '</td>'
						.					'<td>' . CBTxt::Th( 'Optionally input comma separated list of user ids to be excluded from auto action (e.g. 62,39,21,8).' ) . '</td>'
						.				'</tr>'
						.			'</tbody>'
						.		'</table>'
						.		'<input type="hidden" id="id" name="id" value="' . (int) $row->get( 'id' ) . '" />'
						.		'<input type="hidden" id="ordering" name="order" value="' . (int) $row->get( 'ordering', 99999 ) . '" />'
						.		'<input type="hidden" id="option" name="option" value="' . htmlspecialchars( $plugin->option ) . '" />'
						.		'<input type="hidden" id="task" name="task" value="editPlugin" />'
						.		'<input type="hidden" id="cid" name="cid" value="' . (int) $plugin->id . '" />'
						.		'<input type="hidden" id="action" name="action" value="plugin.auto_save" />'
						.		cbGetSpoofInputTag( 'plugin' )
						.	'</form>';

		echo $return;
	}

	private function saveAutoEdit( $id, $task, $user, $plugin ) {
		$row		=	cbgjAutoData::getAutos( null, array( 'id', '=', (int) $id ), null, null, false );

		$row->set( 'published', (int) cbgjClass::getCleanParam( true, 'published', $row->get( 'published' ) ) );
		$row->set( 'title', cbgjClass::getCleanParam( true, 'title', $row->get( 'title' ) ) );
		$row->set( 'description', cbgjClass::getCleanParam( true, 'description', $row->get( 'description' ) ) );
		$row->set( 'trigger', str_replace( ' ', '', cbgjClass::getCleanParam( true, 'trigger', $row->get( 'trigger' ) ) ) );
		$row->set( 'object', (int) cbgjClass::getCleanParam( true, 'object', $row->get( 'object' ) ) );

		if ( $row->get( 'object' ) == 3 ) {
			$row->set( 'variable', (int) cbgjClass::getCleanParam( true, 'variable_user', $row->get( 'variable' ) ) );
		} elseif ( $row->get( 'object' ) == 2 ) {
			$row->set( 'variable', null );
		} elseif ( $row->get( 'object' ) == 1 ) {
			$row->set( 'variable', (int) cbgjClass::getCleanParam( true, 'variable', $row->get( 'variable' ) ) );
		}

		$row->set( 'access', cbgjClass::getCleanParam( true, 'access', $row->get( 'access' ) ) );
		$row->set( 'exclude', cbgjClass::getCleanParam( true, 'exclude', $row->get( 'exclude' ) ) );
		$row->set( 'ordering', (int) cbgjClass::getCleanParam( true, 'ordering', $row->get( 'ordering' ) ) );

		if ( $row->get( 'exclude' ) ) {
			$exclude	=	explode( ',', $row->get( 'exclude' ) );

			cbArrayToInts( $exclude );

			$row->set( 'exclude', implode( ',', $exclude ) );
		}

		$row->setParams( $_POST['params'] );

		if ( $row->get( 'trigger' ) == '' ) {
			$row->set( '_error', CBTxt::T( 'Trigger not specified!' ) );
		} elseif ( $row->get( 'access' ) == '' ) {
			$row->set( '_error', CBTxt::T( 'Access not specified!' ) );
		} elseif ( $row->get( 'object' ) ) {
			if ( $row->get( 'object' ) == 3 ) {
				if ( ! $row->get( 'variable' ) ) {
					$row->set( '_error', CBTxt::T( 'Specific user not specified!' ) );
				}
			} elseif ( $row->get( 'object' ) == 1 ) {
				if ( ! $row->get( 'variable' ) ) {
					$row->set( '_error', CBTxt::T( 'User variable not specified!' ) );
				}
			}
		} else {
			$params		=	$row->getParams();

			if ( $params->get( 'auto', null ) == '' ) {
				$row->set( '_error', CBTxt::T( 'Auto not specified!' ) );
			} else {
				if ( $params->get( 'auto', null ) == 1 ) {
					if ( $params->get( 'groups', null ) == '' ) {
						$row->set( '_error', CBTxt::T( 'Groups not specified!' ) );
					} elseif ( $params->get( 'status', null ) == '' ) {
						$row->set( '_error', CBTxt::T( 'Status not specified!' ) );
					}
				} elseif ( $params->get( 'auto', null ) == 2 ) {
					if ( $params->get( 'category', null ) == '' ) {
						$row->set( '_error', CBTxt::T( 'Category not specified!' ) );
					} elseif ( $params->get( 'grp_name', null ) == '' ) {
						$row->set( '_error', CBTxt::T( 'Group Name not specified!' ) );
					} elseif ( $params->get( 'type', null ) == '' ) {
						$row->set( '_error', CBTxt::T( 'Type not specified!' ) );
					} elseif ( $params->get( 'category', null ) == -1 ) {
						if ( $params->get( 'cat_name', null ) == '' ) {
							$row->set( '_error', CBTxt::T( 'Category Name not specified!' ) );
						}
					}
				} elseif ( $params->get( 'auto', null ) == 3 ) {
					if ( $params->get( 'cat_name', null ) == '' ) {
						$row->set( '_error', CBTxt::T( 'Category Name not specified!' ) );
					} elseif ( $params->get( 'types', null ) == '' ) {
						$row->set( '_error', CBTxt::T( 'Types not specified!' ) );
					}
				} elseif ( $params->get( 'auto', null ) == 4 ) {
					if ( $params->get( 'groups', null ) == '' ) {
						$row->set( '_error', CBTxt::T( 'Groups not specified!' ) );
					}
				}
			}
		}

		$row->setFields( $_POST['fields'] );
		$row->setOperators( $_POST['operators'] );
		$row->setValues( $_POST['values'] );

		if ( $row->getError() || ( ! $row->store() ) ) {
			return $this->getAutoEdit( $id, $user, $plugin, CBTxt::P( 'Auto failed to save! Error: [error]', array( '[error]' => $row->getError() ) ) );
		}

		if ( in_array( $task, array( 'apply', 'auto_apply' ) ) ) {
			cbgjClass::getPluginURL( array( 'plugin', 'auto_edit', (int) $row->get( 'id' ) ), CBTxt::T( 'Auto saved successfully!' ), false, true );
		} else {
			cbgjClass::getPluginURL( array( 'plugin', 'auto' ), CBTxt::T( 'Auto saved successfully!' ), false, true );
		}
	}

	private function stateAuto( $ids, $state, $user, $plugin ) {
		if ( ! empty( $ids ) ) {
			foreach ( $ids as $id ) {
				$row	=	cbgjAutoData::getAutos( null, array( 'id', '=', (int) $id ), null, null, false );

				if ( ! $row->storeState( $state ) ) {
					cbgjClass::getPluginURL( array( 'plugin', 'auto' ), CBTxt::P( 'Auto state failed to save! Error: [error]', array( '[error]' => $row->getError() ) ), false, true, 'error' );
				}
			}

			cbgjClass::getPluginURL( array( 'plugin', 'auto' ), CBTxt::T( 'Auto state saved successfully!' ), false, true );
		}

		cbgjClass::getPluginURL( array( 'plugin', 'auto' ), CBTxt::T( 'Auto not found.' ), false, true, 'error' );
	}

	private function orderAuto( $ids, $order, $user, $plugin ) {
		if ( is_array( $ids ) ) {
			for ( $i = 0; $i < count( $ids ); $i++ ) {
				$row	=	cbgjAutoData::getAutos( null, array( 'id', '=', (int) $ids[$i] ), null, null, false );

				if ( ! $row->storeOrder( $order[$i] ) ) {
					cbgjClass::getPluginURL( array( 'plugin', 'auto' ), CBTxt::P( 'Auto order failed to save! Error: [error]', array( '[error]' => $row->getError() ) ), false, true, 'error' );
				}
			}
		} else {
			$row		=	cbgjAutoData::getAutos( null, array( 'id', '=', (int) $ids ), null, null, false );

			$row->move( $order );
		}

		cbgjClass::getPluginURL( array( 'plugin', 'auto' ), CBTxt::T( 'Auto order saved successfully!' ), false, true );
	}

	private function deleteAuto( $ids, $user, $plugin ) {
		if ( ! empty( $ids ) ) {
			foreach ( $ids as $id ) {
				$row	=	cbgjAutoData::getAutos( null, array( 'id', '=', (int) $id ), null, null, false );

				if ( ! $row->delete() ) {
					cbgjClass::getPluginURL( array( 'plugin', 'auto' ),CBTxt::P( 'Auto failed to delete! Error: [error]', array( '[error]' => $row->getError() ) ), false, true, 'error' );
				}
			}

			cbgjClass::getPluginURL( array( 'plugin', 'auto' ), CBTxt::T( 'Auto deleted successfully!' ), false, true );
		}

		cbgjClass::getPluginURL( array( 'plugin', 'auto' ), CBTxt::T( 'Auto not found.' ), false, true, 'error' );
	}

	private function copyAuto( $ids, $user, $plugin ) {
		if ( ! empty( $ids ) ) {
			foreach ( $ids as $id ) {
				$row	=	cbgjAutoData::getAutos( null, array( 'id', '=', (int) $id ), null, null, false );

				$row->set( 'id', null );
				$row->set( 'published', 0 );

				if ( ! $row->store() ) {
					cbgjClass::getPluginURL( array( 'plugin', 'auto' ), CBTxt::P( 'Auto failed to copy! Error: [error]', array( '[error]' => $row->getError() ) ), false, true, 'error' );
				}
			}

			cbgjClass::getPluginURL( array( 'plugin', 'auto' ), CBTxt::T( 'Auto copied successfully!' ), false, true );
		}

		cbgjClass::getPluginURL( array( 'plugin', 'auto' ), CBTxt::T( 'Auto not found.' ), false, true, 'error' );
	}
}

class cbgjautoClass extends cbPluginHandler {

	static public function getFieldMatch( $user, $cbUser, $extras, $field, $operator, $value ) {
		if ( ( $field == '' ) && ( $value == '' ) || ( $operator == '' ) ) {
			return true;
		}

		$field			=	trim( $cbUser->replaceUserVars( $field, true, true, $extras ) );
		$value			=	trim( $cbUser->replaceUserVars( $value, true, true, $extras ) );

		switch ( $operator ) {
			case 1:
				$match	=	( $field != $value );
				break;
			case 2:
				$match	=	( $field > $value );
				break;
			case 3:
				$match	=	( $field < $value );
				break;
			case 4:
				$match	=	( $field >= $value );
				break;
			case 5:
				$match	=	( $field <= $value );
				break;
			case 6:
				$match	=	( ! $field );
				break;
			case 7:
				$match	=	( $field );
				break;
			case 8:
				$match	=	( stristr( $field, $value ) );
				break;
			case 9:
				$match	=	( ! stristr( $field, $value ) );
				break;
			case 10:
				$match	=	( preg_match( $value, $field ) );
				break;
			case 11:
				$match	=	( ! preg_match( $value, $field ) );
				break;
			case 0:
			default:
				$match	=	( $field == $value );
				break;
		}

		return $match;
	}

	static public function getTriggers() {
		global $_PLUGINS;

		static $rows		=	null;

		if ( ! isset( $rows ) ) {
			$rows			=	cbgjAutoData::getAutos( null, array( 'published', '=', 1 ) );

			if ( $rows ) foreach ( $rows as $row ) {
				$triggers	=	explode( ',', $row->get( 'trigger' ) );

				if ( $triggers ) foreach ( $triggers as $trigger ) {
					$args	=	'$var1 = null, $var2 = null, $var3 = null, $var4 = null, $var5 = null, $var6 = null, $var7 = null, $var8 = null, $var9 = null, $var10 = null';
					$vars	=	'$var1, $var2, $var3, $var4, $var5, $var6, $var7, $var8, $var9, $var10';

					$code	=	"\n \$plugin = new cbgjautoPlugin();"
							.	"\n \$plugin->getTrigger( '" . (int) $row->get( 'id' ) . "', $vars );";

					$func	=	create_function( $args, $code );

					$_PLUGINS->registerFunction( trim( htmlspecialchars( $trigger ) ), $func );
				}
			}
		}

		return $rows;
	}
}

class cbgjAuto extends comprofilerDBTable {
	var $id				=	null;
	var $title			=	null;
	var $description	=	null;
	var $trigger		=	null;
	var $object			=	null;
	var $variable		=	null;
	var $access			=	null;
	var $field			=	null;
	var $operator		=	null;
	var $value			=	null;
	var $exclude		=	null;
	var $published		=	null;
	var $ordering		=	null;
	var $params			=	null;

	public function __construct( $db ) {
		$this->comprofilerDBTable( '#__groupjive_plugin_auto', 'id', $db );
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

		$plugin	=	cbgjClass::getPlugin();
		$user	=&	CBuser::getUserDataInstance( $_CB_framework->myId() );
		$new	=	( $this->get( 'id' ) ? false : true );

		if ( ! $new ) {
			$_PLUGINS->trigger( 'gjint_onBeforeUpdateAuto', array( &$this, $user, $plugin ) );
		} else {
			$_PLUGINS->trigger( 'gjint_onBeforeCreateAuto', array( &$this, $user, $plugin ) );
		}

		if ( ! parent::store( $updateNulls ) ) {
			return false;
		}

		if ( ! $new ) {
			$_PLUGINS->trigger( 'gjint_onAfterUpdateAuto', array( $this, $user, $plugin ) );
		} else {
			$_PLUGINS->trigger( 'gjint_onAfterCreateAuto', array( $this, $user, $plugin ) );
		}

		$this->updateOrder();

		return true;
	}

	public function delete( $id = null ) {
		global $_CB_framework, $_PLUGINS;

		$plugin	=	cbgjClass::getPlugin();
		$user	=&	CBuser::getUserDataInstance( $_CB_framework->myId() );

		$_PLUGINS->trigger( 'gjint_onBeforeDeleteAuto', array( &$this, $user, $plugin ) );

		if ( ! parent::delete( $id ) ) {
			return false;
		}

		$_PLUGINS->trigger( 'gjint_onAfterDeleteAuto', array( $this, $user, $plugin ) );

		$this->updateOrder();

		return true;
	}

	public function move( $order, $where = null, $ordering = 'ordering' ) {
		global $_CB_framework, $_PLUGINS;

		$plugin	=	cbgjClass::getPlugin();
		$user	=&	CBuser::getUserDataInstance( $_CB_framework->myId() );

		$_PLUGINS->trigger( 'gjint_onBeforeUpdateAutoOrder', array( &$this, $user, $plugin ) );

		parent::move( (int) $order, $where, $ordering );

		$_PLUGINS->trigger( 'gjint_onAfterUpdateAutoOrder', array( $this, $user, $plugin ) );

		$this->updateOrder();

		return true;
	}

	public function storeParams( $params, $html = true ) {
		global $_CB_framework, $_PLUGINS;

		$plugin			=	cbgjClass::getPlugin();
		$user			=&	CBuser::getUserDataInstance( $_CB_framework->myId() );
		$params			=	cbgjClass::parseParams( $params, true, $this->getParams( $html ) );

		$_PLUGINS->trigger( 'gjint_onBeforeUpdateAutoParams', array( &$params, &$this, $user, $plugin ) );

		$this->set( 'params', trim( $params->toIniString() ) );

		if ( ! parent::store() ) {
			return false;
		}

		$_PLUGINS->trigger( 'gjint_onAfterUpdateAutoParams', array( $params, $this, $user, $plugin ) );

		return true;
	}

	public function storeState( $state ) {
		global $_CB_framework, $_PLUGINS;

		$plugin	=	cbgjClass::getPlugin();
		$user	=&	CBuser::getUserDataInstance( $_CB_framework->myId() );

		$_PLUGINS->trigger( 'gjint_onBeforeUpdateAutoState', array( &$state, &$this, $user, $plugin ) );

		$this->set( 'published', (int) $state );

		if ( ! parent::store() ) {
			return false;
		}

		$_PLUGINS->trigger( 'gjint_onAfterUpdateAutoState', array( $this->get( 'published' ), $this, $user, $plugin ) );

		return true;
	}

	public function storeOrder( $order ) {
		global $_CB_framework, $_PLUGINS;

		$plugin	=	cbgjClass::getPlugin();
		$user	=&	CBuser::getUserDataInstance( $_CB_framework->myId() );

		$_PLUGINS->trigger( 'gjint_onBeforeUpdateAutoOrder', array( &$order, &$this, $user, $plugin ) );

		$this->set( 'ordering', (int) $order );

		if ( ! parent::store() ) {
			return false;
		}

		$_PLUGINS->trigger( 'gjint_onAfterUpdateAutoOrder', array( $this->get( 'ordering' ), $this, $user, $plugin ) );

		$this->updateOrder();

		return true;
	}

	public function setFields( $fields, $html = true ) {
		global $_CB_framework, $_PLUGINS;

		$plugin	=	cbgjClass::getPlugin();
		$user	=&	CBuser::getUserDataInstance( $_CB_framework->myId() );
		$fields	=	cbgjClass::parseParams( $fields, $html );

		$_PLUGINS->trigger( 'gjint_onBeforeSetAutoFields', array( &$fields, &$this, $user, $plugin ) );

		$this->set( 'field', trim( $fields->toIniString() ) );

		$_PLUGINS->trigger( 'gjint_onAfterSetAutoFields', array( $fields, $this, $user, $plugin ) );
	}

	public function setOperators( $operators, $html = false ) {
		global $_CB_framework, $_PLUGINS;

		$plugin		=	cbgjClass::getPlugin();
		$user		=&	CBuser::getUserDataInstance( $_CB_framework->myId() );
		$operators	=	cbgjClass::parseParams( $operators, $html );

		$_PLUGINS->trigger( 'gjint_onBeforeSetAutoOperators', array( &$operators, &$this, $user, $plugin ) );

		$this->set( 'operator', trim( $operators->toIniString() ) );

		$_PLUGINS->trigger( 'gjint_onAfterSetAutoOperators', array( $operators, $this, $user, $plugin ) );
	}

	public function setValues( $values, $html = true ) {
		global $_CB_framework, $_PLUGINS;

		$plugin	=	cbgjClass::getPlugin();
		$user	=&	CBuser::getUserDataInstance( $_CB_framework->myId() );
		$values	=	cbgjClass::parseParams( $values, $html );

		$_PLUGINS->trigger( 'gjint_onBeforeSetAutoValues', array( &$values, &$this, $user, $plugin ) );

		$this->set( 'value', trim( $values->toIniString() ) );

		$_PLUGINS->trigger( 'gjint_onAfterSetAutoValues', array( $values, $this, $user, $plugin ) );
	}

	public function setParams( $params, $html = true ) {
		global $_CB_framework, $_PLUGINS;

		$plugin	=	cbgjClass::getPlugin();
		$user	=&	CBuser::getUserDataInstance( $_CB_framework->myId() );
		$params	=	cbgjClass::parseParams( $params, $html, $this->getParams( $html ) );

		$_PLUGINS->trigger( 'gjint_onBeforeSetAutoParams', array( &$params, &$this, $user, $plugin ) );

		$this->set( 'params', trim( $params->toIniString() ) );

		$_PLUGINS->trigger( 'gjint_onAfterSetAutoParams', array( $params, $this, $user, $plugin ) );
	}

	public function getFields( $html = true ) {
		static $cache	=	array();

		$id				=	cbgjClass::getStaticID( array( $this->get( 'id' ), $html ) );

		if ( ! isset( $cache[$id] ) ) {
			$cache[$id]	=	cbgjClass::parseParams( $this->get( 'field' ), $html );
		}

		return $cache[$id];
	}

	public function getOperators( $html = false ) {
		static $cache	=	array();

		$id				=	cbgjClass::getStaticID( array( $this->get( 'id' ), $html ) );

		if ( ! isset( $cache[$id] ) ) {
			$cache[$id]	=	cbgjClass::parseParams( $this->get( 'operator' ), $html );
		}

		return $cache[$id];
	}

	public function getValues( $html = true ) {
		static $cache	=	array();

		$id				=	cbgjClass::getStaticID( array( $this->get( 'id' ), $html ) );

		if ( ! isset( $cache[$id] ) ) {
			$cache[$id]	=	cbgjClass::parseParams( $this->get( 'value' ), $html );
		}

		return $cache[$id];
	}

	public function getParams( $html = true ) {
		static $cache	=	array();

		$id				=	cbgjClass::getStaticID( array( $this->get( 'id' ), $html ) );

		if ( ! isset( $cache[$id] ) ) {
			$cache[$id]	=	cbgjClass::parseParams( $this->get( 'params' ), $html );
		}

		return $cache[$id];
	}

	public function getType() {
		static $cache		=	array();

		$id					=	$this->get( 'id' );

		if ( ! isset( $cache[$id] ) ) {
			$params			=	$this->getParams();

			if ( $params->get( 'auto' ) == 1 ) {
				$cache[$id]	=	CBTxt::T( 'Join Groups' );
			} elseif ( $params->get( 'auto' ) == 2 ) {
				$cache[$id]	=	CBTxt::T( 'New Group' );
			} elseif ( $params->get( 'auto' ) == 3 ) {
				$cache[$id]	=	CBTxt::T( 'New Category' );
			} elseif ( $params->get( 'auto' ) == 4 ) {
				$cache[$id]	=	CBTxt::T( 'Leave Group' );
			} else {
				$cache[$id]	=	CBTxt::T( 'Unknown' );
			}
		}

		return $cache[$id];
	}

	public function getAccess() {
		global $_CB_framework;

		static $cache			=	array();

		$id						=	$this->get( 'access' );

		if ( ! isset( $cache[$id] ) ) {
			$access				=	array();

			if ( $id ) foreach ( explode( '|*|', $id ) as $k => $v ) {
				if ( $v == -1 ) {
					$access[$k]	=	CBTxt::T( 'Everybody' );
				} elseif ( $v == -2 ) {
					$access[$k]	=	CBTxt::T( 'All Non-Registered Users' );
				} elseif ( $v == -3 ) {
					$access[$k]	=	CBTxt::T( 'All Registered Users' );
				} elseif ( $v == -4 ) {
					$access[$k]	=	CBTxt::T( 'All Non-Moderators' );
				} elseif ( $v == -5 ) {
					$access[$k]	=	CBTxt::T( 'All Moderators' );
				} else {
					$access[$k]	=	CBTxt::T( $_CB_framework->acl->get_group_name( $v ) );
				}
			}

			$cache[$id]			=	$access;
		}

		return $cache[$id];
	}
}

class cbgjAutoData {

    static public function getAutos( $access = array(), $filtering = array(), $ordering = array(), $limits = 0, $list = true ) {
		global $_CB_database;

		static $cache	=	array();

		if ( ! $access ) {
			$access		=	array();
		}

		if ( ! $filtering ) {
			$filtering	=	array();
		}

		if ( ! $ordering ) {
			$ordering	=	array();
		}

		$id				=	cbgjClass::getStaticID( array( $filtering, $ordering ) );

		if ( ! isset( $cache[$id] ) ) {
			$where		=	array();
			$join		=	array();

			if ( $filtering ) {
				cbgjData::where( $where, $join, $filtering );
			}

			$orderBy	=	array();

			if ( $ordering ) {
				cbgjData::order( $orderBy, $join, $ordering );
			}

			$query		=	'SELECT *'
						.	"\n FROM " . $_CB_database->NameQuote( '#__groupjive_plugin_auto' )
						.	( count( $where ) ? "\n WHERE " . implode( "\n AND ", $where ) : null )
						.	"\n ORDER BY " . ( count( $orderBy ) ? implode( ', ', $orderBy ) : $_CB_database->NameQuote( 'ordering' ) . " ASC" );
			$_CB_database->setQuery( $query );
			$cache[$id]	=	$_CB_database->loadObjectList( 'id', 'cbgjAuto', array( & $_CB_database ) );
		}

		$rows			=	$cache[$id];

		if ( $rows ) {
			if ( $access ) {
				cbgjData::access( $rows, $access );
			}

			if ( $limits ) {
				cbgjData::limit( $rows, $limits );
			}
		}

		if ( ! $rows ) {
			$rows		=	array();
		}

		if ( $list ) {
			return $rows;
		} else {
			$rows		=	array_shift( $rows );

			if ( ! $rows ) {
				$rows	=	new cbgjAuto( $_CB_database );
			}

			return $rows;
		}
	}
}

cbgjautoClass::getTriggers();
?>