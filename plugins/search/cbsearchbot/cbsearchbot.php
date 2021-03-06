<?php
if ( ! ( defined( '_VALID_CB' ) || defined( '_JEXEC' ) || defined( '_VALID_MOS' ) ) ) { die( 'Direct Access to this location is not allowed.' ); }

jimport( 'joomla.plugin.plugin' );

class plgSearchcbsearchbot extends JPlugin {

	private function loadCB() {
		global $mainframe, $_PLUGINS;

		static $CB_loaded	=	0;

		if ( ! $CB_loaded++ ) {
			if ( defined( 'JPATH_ADMINISTRATOR' ) ) {
				if ( ! file_exists( JPATH_ADMINISTRATOR . '/components/com_comprofiler/plugin.foundation.php' ) ) {
					return;
				}

				include_once( JPATH_ADMINISTRATOR . '/components/com_comprofiler/plugin.foundation.php' );
			} else {
				if ( ! file_exists( $mainframe->getCfg( 'absolute_path' ) . '/administrator/components/com_comprofiler/plugin.foundation.php' ) ) {
					return;
				}

				include_once( $mainframe->getCfg( 'absolute_path' ) . '/administrator/components/com_comprofiler/plugin.foundation.php' );
			}

			cbimport( 'cb.html' );
			cbimport( 'language.front' );
			cbimport( 'cb.lists' );

			$_PLUGINS->loadPluginGroup( 'user' );
		}
	}

	public function onContentSearchAreas() {
		$this->loadCB();

		static $areas	=	null;

		if ( ! isset( $areas ) ) {
			$area		=	$this->params->get( 'search_area', 'Users' );

			if ( ! $area ) {
				$area	=	'Users';
			}

			$areas		=	array( 'cb' => CBTxt::T( $area ) );
		}

		return $areas;
	}

	public function onContentSearch( $text, $phrase = '', $ordering = '', $areas = null ) {
		global $_CB_framework, $_CB_database, $_PLUGINS;

		if ( ( $areas && is_array( $areas ) && ( ! in_array( 'cb', $areas ) ) ) || ( ! $text ) ) {
			return array();
		}

		$this->loadCB();

		$searchFields						=	$this->params->get( 'search_fields', array( '41', '42', '46', '47', '48', '50' ) );

		if ( ! is_array( $searchFields ) ) {
			$searchFields					=	explode( '|*|', $searchFields );
		}

		if ( ! $searchFields ) {
			return array();
		}

		$resultTitle						=	$this->params->get( 'result_title', '[formatname]' );
		$resultText							=	$this->params->get( 'result_text', '[formatname]\'s profile page' );
		$resultsLimit						=	(int) $this->params->get( 'result_limit', 50 );
		$resultsLinks						=	(int) $this->params->get( 'result_link', 0 );
		$results							=	array();

		$cbUser								=&	CBuser::getInstance( (int) $_CB_framework->myId() );

		if ( ! $cbUser ) {
			$cbUser							=&	CBuser::getInstance( null );
		}

		$user								=&	$cbUser->getUserData();
		$tabs								=	$cbUser->_getCbTabs();
		$fields								=	$tabs->_getTabFieldsDb( null, $user, 'list' );

		$queryTables						=	array( 'u'	=> '#__users AS u' );
		$queryJoins							=	array( 'ue'	=> 'LEFT JOIN #__comprofiler AS ue ON u.id = ue.id' );
		$queryWhere							=	array( 'u.block = 0', 'ue.approved = 1', 'ue.confirmed = 1');
		$queryOrdering						=	array();

		switch ( $ordering ) {
			case 'alpha':
				$queryOrdering[]			=	'u.' . $this->params->get( 'ordering_alpha', 'name' ) . ' ASC';
				break;
			case 'popular':
				$queryOrdering[]			=	'ue.hits DESC';
				break;
			case 'oldest':
				$queryOrdering[]			=	'u.registerDate ASC';
				break;
			default:
				$queryOrdering[]			=	'u.registerDate DESC';
				break;
		}

		$searchQuery						=	new cbSqlQueryPart();
		$searchQuery->tag					=	'where';
		$searchQuery->type					=	'sql:operator';
		$searchQuery->operator				=	'OR';

		if ( $phrase == 'all' ) {
			$searchMode						=	'all';
		} elseif ( $phrase == 'exact' ) {
			$searchMode						=	'is';
		} else {
			$searchMode						=	'any';
		}

		if ( $fields ) foreach ( $fields as $k => $field ) {
			$columns						=	$field->getTableColumns();

			if ( ( ! count( $columns ) ) || ( ! in_array( $field->get( 'fieldid' ), $searchFields ) ) ) {
				unset( $fields[$k] );
			} else {
				if ( ! ( $field->params instanceof cbParamsBase ) ) {
					$field->params			=	new cbParamsBase( $field->params );
				}

				$postdata					=	array();
				$searchVals					=	new stdClass();

				foreach ( $columns as $col ) {
					$postdata[$col]			=	$text;
					$searchVals->$col		=	$text;
				}

				$searchSqlQuery				=	new cbSqlQueryPart();
				$searchSqlQuery->tag		=	'where';
				$searchSqlQuery->type		=	'sql:operator';
				$searchSqlQuery->operator	=	'AND';
				$searchSql					=	array();

				$_PLUGINS->trigger( 'onBeforebindSearchCriteria', array( &$field, &$searchVals, &$postdata, 1, 'search' ) );

				foreach ( $columns as $col ) {
					$sql					=	new cbSqlQueryPart();
					$sql->tag				=	'column';
					$sql->name				=	$col;
					$sql->table				=	$field->get( 'table' );
					$sql->type				=	'sql:field';
					$sql->operator			=	'=';
					$sql->value				=	$text;
					$sql->valuetype			=	'const:string';
					$sql->searchmode		=	$searchMode;

					$searchSql[]			=	$sql;
				}

				$_PLUGINS->trigger( 'onAfterbindSearchCriteria', array( &$field, &$searchVals, &$postdata, 1, 'search', &$searchSql ) );

				if ( $searchSql ) {
					$searchSqlQuery->addChildren( $searchSql );

					$searchQuery->addChildren( array( $searchSqlQuery ) );
				}
			}
		}

		if ( ! $fields ) {
			return array();
		}

		$tables								=	array( '#__comprofiler' => 'ue', '#__users' => 'u' );
		$whereFields						=	$searchQuery->reduceSqlFormula( $tables, $queryJoins, true );

		if ( $whereFields ) {
			$queryWhere[]					=	'(' . $whereFields . ')';
		} else {
			return array();
		}

		if ( ! isModerator( $user->get( 'id' ) ) ) {
			$queryWhere[]					=	'ue.banned = 0';
		}

		$query								=	'SELECT u.*'
											.	"\n FROM " . implode( ', ', $queryTables )
											.	( count( $queryJoins ) ? "\n " . implode( "\n ", $queryJoins ) : '' )
											.	( count( $queryWhere ) ? "\n WHERE " . implode( ' AND ', $queryWhere ) : '' )
											.	( count( $queryOrdering ) ? "\n ORDER BY " . implode( ', ', $queryOrdering ) : '' );
		if ( $resultsLimit ) {
			$_CB_database->setQuery( $query, 0, $resultsLimit );
		} else {
			$_CB_database->setQuery( $query );
		}
		$rows								=	$_CB_database->loadObjectList( null, 'moscomprofilerUser', array( &$_CB_database ) );

		if ( $rows ) foreach ( $rows as $row ) {
			$cbUserRow						=&	CBuser::getInstance( (int) $row->get( 'id' ) );

			if ( ! $cbUserRow ) {
				$cbUserRow					=&	CBuser::getInstance( null );
			}

			$result							=	new stdClass();
			$result->href					=	$_CB_framework->userProfileUrl( $row->get( 'id' ), false );
			$result->title					=	$cbUserRow->replaceUserVars( $resultTitle );
			$result->text					=	$cbUserRow->replaceUserVars( $resultText );
			$result->created				=	$row->get( 'registerDate' );
			$result->browsernav				=	$resultsLinks;
			$result->section				=	0;

			$results[]						=	$result;
		}

		return $results;
	}
}
?>