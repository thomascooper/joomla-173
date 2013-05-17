<?php
if ( ! ( defined( '_VALID_CB' ) || defined( '_JEXEC' ) || defined( '_VALID_MOS' ) ) ) { die( 'Direct Access to this location is not allowed.' ); }

jimport( 'joomla.plugin.plugin' );

class plgContentcbauthorbot extends JPlugin {

	public function onContentBeforeDisplay( $context, &$article ) {
		global $mainframe;

		if ( isset( $article->created_by ) || isset( $article->modified_by ) ) {
			static $CB_loaded				=	0;

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
			}

			if ( ( isset( $article->created_by ) ) && $article->created_by ) {
				$cbUserCreate				=&	CBuser::getInstance( (int) $article->created_by );

				if ( ! $cbUserCreate ) {
					$cbUserCreate			=&	CBuser::getInstance( null );
				}

				if ( isset( $article->author ) ) {
					$article->author		=	$cbUserCreate->getField( 'formatname', null, 'html', 'none', 'list', 0, true );
				}

				$article->created_by_alias	=	$cbUserCreate->getField( 'formatname', null, 'html', 'none', 'list', 0, true );
			}

			if ( ( isset( $article->modified_by ) ) && $article->modified_by ) {
				$cbUserModify				=&	CBuser::getInstance( (int) $article->modified_by );

				if ( ! $cbUserModify ) {
					$cbUserModify			=&	CBuser::getInstance( null );
				}

				$article->modified_by_name	=	$cbUserModify->getField( 'formatname', null, 'html', 'none', 'list', 0, true );
			}

			if ( ( isset( $article->contactid ) ) && $article->contactid ) {
				$article->contactid			=	null;
			}
		}
	}
}
?>