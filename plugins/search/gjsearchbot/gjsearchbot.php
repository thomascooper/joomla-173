<?php
if ( ! ( defined( '_VALID_CB' ) || defined( '_JEXEC' ) || defined( '_VALID_MOS' ) ) ) { die( 'Direct Access to this location is not allowed.' ); }

jimport( 'joomla.plugin.plugin' );

class plgSearchgjsearchbot extends JPlugin {

	private function loadCB() {
		global $_CB_framework, $mainframe;

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

			if ( ! file_exists( $_CB_framework->getCfg( 'absolute_path' ) . '/components/com_comprofiler/plugin/user/plug_cbgroupjive/cbgroupjive.class.php' ) ) {
				return;
			}

			cbimport( 'cb.html' );
			cbimport( 'language.front' );

			require_once( $_CB_framework->getCfg( 'absolute_path' ) . '/components/com_comprofiler/plugin/user/plug_cbgroupjive/cbgroupjive.class.php' );
		}
	}

	public function onContentSearchAreas() {
		$this->loadCB();

		static $areas			=	null;

		if ( ! isset( $areas ) ) {
			$areas				=	array();

			if ( $this->getCategorySearching() ) {
				$catArea		=	$this->params->get( 'search_cat_area', 'Categories' );

				if ( ! $catArea ) {
					$catArea	=	'Categories';
				}

				$areas['gjcat']	=	CBTxt::T( $catArea );
			}

			if ( $this->getGroupSearching() ) {
				$grpArea		=	$this->params->get( 'search_grp_area', 'Groups' );

				if ( ! $grpArea ) {
					$grpArea	=	'Groups';
				}

				$areas['gjgrp']	=	CBTxt::T( $grpArea );
			}
		}

		return $areas;
	}

	public function onContentSearch( $text, $phrase = '', $ordering = '', $areas = array() ) {
		global $_CB_framework;

		if ( ( ( ! $this->getCategorySearching() ) && ( ! $this->getGroupSearching() ) ) || ( ! $text ) ) {
			return array();
		}

		$this->loadCB();

		$results						=	array();

		$cbUser							=&	CBuser::getInstance( (int) $_CB_framework->myId() );

		if ( ! $cbUser ) {
			$cbUser						=&	CBuser::getInstance( null );
		}

		$user							=&	$cbUser->getUserData();

		switch( $phrase ) {
			case 'exact':
				$where					=	array( 'name', '=', $text, array( 'description', '=', $text ) );
				break;
			case 'any':
				$where					=	array();
				$words					=	explode( ' ', $text );
				$count					=	0;

				foreach ( $words as $word ) {
					if ( $count >= 1 ) {
						$where[]		=	array( 'name', 'CONTAINS', $word );
						$where[]		=	array( 'description', 'CONTAINS', $word );
					} else {
						$where[]		=	'name';
						$where[]		=	'CONTAINS';
						$where[]		=	$word;
						$where[]		=	array( 'description', 'CONTAINS', $word );
					}

					$count++;
				}
				break;
			case 'all':
			default:
				$where					=	array();
				$desc					=	array();
				$words					=	explode( ' ', $text );

				foreach ( $words as $word ) {
					$where[]			=	'name';
					$where[]			=	'CONTAINS';
					$where[]			=	$word;
					$desc[]				=	'description';
					$desc[]				=	'CONTAINS';
					$desc[]				=	$word;
				}

				$where[]				=	$desc;
				break;
		}

		if ( $this->getCategorySearching() ) {
			$exclude					=	$this->params->get( 'search_cat_exclude', null );
			$resultTitle				=	$this->params->get( 'result_cat_title', '[row_name]' );
			$resultText					=	$this->params->get( 'result_cat_text', '[row_description]' );
			$resultsLimit				=	(int) $this->params->get( 'result_cat_limit', 50 );
			$resultsLinks				=	(int) $this->params->get( 'result_cat_link', 0 );

			if ( $exclude ) {
				if ( ! is_array( $exclude ) ) {
					$exclude			=	explode( '|*|', $exclude );
				}

				cbArrayToInts( $exclude );

				array_unshift( $where, 'id','!IN', $exclude );
			}

			switch( $ordering ) {
				case 'oldest':
					$orderBy			=	array( 'date', 'ASC' );
					break;
				case 'popular':
					$orderBy			=	'group_count_desc';
					break;
				case 'alpha':
					$orderBy			=	array( 'name', 'ASC' );
					break;
				case 'newest':
				case 'category':
				default:
					$orderBy			=	array( 'date', 'DESC' );
					break;
			}

			$rows						=	cbgjData::getCategories( array( array( 'cat_access', 'mod_lvl1' ), $user ), $where, $orderBy, $resultsLimit );

			if ( $rows ) foreach ( $rows as $row ) {
				$cbUserRow				=&	CBuser::getInstance( (int) $row->get( 'user_id' ) );

				if ( ! $cbUserRow ) {
					$cbUserRow			=&	CBuser::getInstance( null );
				}

				$extras					=	array(	'row_id' => (int) $row->get( 'id' ),
													'row_name' => $row->getName(),
													'row_description' => $row->getDescription(),
													'row_logo' => $row->getLogo( true, false ),
													'row_logo_thumb' => $row->getLogo( true, false, true ),
													'row_url' => $row->getUrl(),
													'row_date' => cbFormatDate( $row->get( 'date' ), 1, false ),
													'row_parent_id' => ( $row->get( 'parent' ) ? (int) $row->getParent()->get( 'id' ) : null ),
													'row_parent_name' => ( $row->get( 'parent' ) ? $row->getParent()->getName() : null ),
													'row_parent_description' => ( $row->get( 'parent' ) ? $row->getParent()->getDescription() : null ),
													'row_parent_logo' => ( $row->get( 'parent' ) ? $row->getParent()->getLogo( true, false ) : null ),
													'row_parent_logo_thumb' => ( $row->get( 'parent' ) ? $row->getParent()->getLogo( true, false, true ) : null ),
													'row_parent_url' => ( $row->get( 'parent' ) ? $row->getParent()->getUrl() : null ),
													'row_parent_date' => ( $row->get( 'parent' ) ? cbFormatDate( $row->getParent()->get( 'date' ), 1, false ) : null )
												);

				$result					=	new stdClass();
				$result->href			=	$row->getUrl();
				$result->title			=	$cbUserRow->replaceUserVars( $resultTitle, true, true, $extras );
				$result->text			=	$cbUserRow->replaceUserVars( $resultText, false, true, $extras );
				$result->created		=	$row->get( 'date' );
				$result->browsernav		=	$resultsLinks;
				$result->section		=	( $row->get( 'parent' ) ? $row->getParent()->getName() : null );

				$results[]				=	$result;
			}
		}

		if ( $this->getGroupSearching() ) {
			$exclude					=	$this->params->get( 'search_grp_exclude', null );
			$resultTitle				=	$this->params->get( 'result_grp_title', '[row_name]' );
			$resultText					=	$this->params->get( 'result_grp_text', '[row_description]' );
			$resultsLimit				=	(int) $this->params->get( 'result_grp_limit', 50 );
			$resultsLinks				=	(int) $this->params->get( 'result_grp_link', 0 );

			if ( $exclude ) {
				if ( ! is_array( $exclude ) ) {
					$exclude			=	explode( '|*|', $exclude );
				}

				cbArrayToInts( $exclude );

				array_unshift( $where, 'id','!IN', $exclude );
			}

			switch( $ordering ) {
				case 'oldest':
					$orderBy			=	array( 'date', 'ASC' );
					break;
				case 'popular':
					$orderBy			=	'user_count_desc';
					break;
				case 'alpha':
					$orderBy			=	array( 'name', 'ASC' );
					break;
				case 'category':
					$orderBy			=	array( 'b.name', 'ASC' );
					break;
				case 'newest':
				default:
					$orderBy			=	array( 'date', 'DESC' );
					break;
			}

			$rows						=	cbgjData::getGroups( array( array( 'grp_access', 'mod_lvl2' ), $user ), $where, $orderBy, $resultsLimit );

			if ( $rows ) foreach ( $rows as $row ) {
				$cbUserRow				=&	CBuser::getInstance( (int) $row->get( 'user_id' ) );

				if ( ! $cbUserRow ) {
					$cbUserRow			=&	CBuser::getInstance( null );
				}

				$extras					=	array(	'row_id' => (int) $row->get( 'id' ),
													'row_name' => $row->getName(),
													'row_description' => $row->getDescription(),
													'row_logo' => $row->getLogo( true, false ),
													'row_logo_thumb' => $row->getLogo( true, false, true ),
													'row_url' => $row->getUrl(),
													'row_date' => cbFormatDate( $row->get( 'date' ), 1, false ),
													'row_category_id' => ( $row->get( 'category' ) ? (int) $row->getCategory()->get( 'id' ) : null ),
													'row_category_name' => ( $row->get( 'category' ) ? $row->getCategory()->getName() : null ),
													'row_category_description' => ( $row->get( 'category' ) ? $row->getCategory()->getDescription() : null ),
													'row_category_logo' => ( $row->get( 'category' ) ? $row->getCategory()->getLogo( true, false ) : null ),
													'row_category_logo_thumb' => ( $row->get( 'category' ) ? $row->getCategory()->getLogo( true, false, true ) : null ),
													'row_category_url' => ( $row->get( 'category' ) ? $row->getCategory()->getUrl() : null ),
													'row_category_date' => ( $row->get( 'category' ) ? cbFormatDate( $row->getCategory()->get( 'date' ), 1, false ) : null ),
													'row_parent_id' => ( $row->get( 'parent' ) ? (int) $row->getParent()->get( 'id' ) : null ),
													'row_parent_name' => ( $row->get( 'parent' ) ? $row->getParent()->getName() : null ),
													'row_parent_description' => ( $row->get( 'parent' ) ? $row->getParent()->getDescription() : null ),
													'row_parent_logo' => ( $row->get( 'parent' ) ? $row->getParent()->getLogo( true, false ) : null ),
													'row_parent_logo_thumb' => ( $row->get( 'parent' ) ? $row->getParent()->getLogo( true, false, true ) : null ),
													'row_parent_url' => ( $row->get( 'parent' ) ? $row->getParent()->getUrl() : null ),
													'row_parent_date' => ( $row->get( 'parent' ) ? cbFormatDate( $row->getParent()->get( 'date' ), 1, false ) : null )
												);

				$result					=	new stdClass();
				$result->href			=	$row->getUrl();
				$result->title			=	$cbUserRow->replaceUserVars( $resultTitle, true, true, $extras );
				$result->text			=	$cbUserRow->replaceUserVars( $resultText, false, true, $extras );
				$result->created		=	$row->get( 'date' );
				$result->browsernav		=	$resultsLinks;
				$result->section		=	( $row->get( 'category' ) ? $row->getCategory()->getName() : null );

				$results[]				=	$result;
			}
		}

		return $results;
	}

	private function getCategorySearching( $areas = array() ) {
		if ( $this->params->get( 'search_cat_enable', 0 ) ) {
			if ( $areas && ( ! in_array( 'gjcat', $areas ) ) ) {
				return false;
			}

			return true;
		}

		return false;
	}

	private function getGroupSearching( $areas = array() ) {
		if ( $this->params->get( 'search_grp_enable', 1 ) ) {
			if ( $areas && ( ! in_array( 'gjgrp', $areas ) ) ) {
				return false;
			}

			return true;
		}

		return false;
	}
}
?>