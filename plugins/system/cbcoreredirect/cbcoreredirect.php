<?php
/**
* Joomla Community Builder Plugin: cbcoreredirect
* @version $Id: cbcoreredirect.php 2403 2012-04-02 20:30:14Z kyle $
* @package cbcoreredirect
* @subpackage cbcoreredirect.php
* @author Krileon
* @copyright (C) 2012 www.joomlapolis.com
* @license Limited http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU/GPL version 2
*/

if ( ! defined( '_JEXEC' ) ) { die( 'Direct Access to this location is not allowed.' ); }

class plgSystemCBCoreRedirect extends JPlugin {

	public function onAfterInitialise() {
		$app							=	JFactory::getApplication();

		if ( $app->isSite() ) {
			$uri						=	JURI::getInstance();

			if ( $uri->getVar( 'option' ) == 'com_users' ) {
				switch ( $uri->getVar( 'view' ) ) {
					case 'profile':
						if ( $uri->getVar( 'layout' ) == 'edit' ) {
							$user_id	=	(int) $uri->getVar( 'user_id' );
							$task		=	'userDetails';

							if ( $user_id ) {
								$task	.=	'&user=' . $user_id;
							}
						} else {
							$task		=	null;
						}
						break;
					case 'registration':
						$task			=	'registers';
						break;
					case 'reset':
					case 'remind':
						$task			=	'lostpassword';
						break;
					case 'logout':
						$task			=	'logout';
						break;
					case 'login':
					default:
						$task			=	'login';
						$data			=	$app->getUserState( 'users.login.form.data', array() );
						$return			=	( isset( $data['return'] ) ? $data['return'] : null );

						if ( $return ) {
							$session	=	JFactory::getSession();

							$session->set( 'cbLoginReturn', base64_encode( $return ) );
						}
						break;
				}

				$Itemid					=	( $this->params->get( 'itemids', 1 ) ? $this->getItemid( $task ) : null );
				$url					=	'index.php?option=com_comprofiler' . ( $task ? '&task=' . $task : null ) . ( $Itemid ? '&Itemid=' . $Itemid : null );

				$app->redirect( JRoute::_( $url, false ), null, null, true, true );
			}

			if ( ( $uri->getVar( 'option' ) == 'com_comprofiler' ) && ( $uri->getVar( 'task' ) == 'login' ) ) {
				$session				=	JFactory::getSession();

				if ( $session->has( 'cbLoginReturn' ) ) {
					$redirect			=	$session->get( 'cbLoginReturn' );
					$document			=	JFactory::getDocument();
					$js					=	"function cbLoginReturn() {"
										.		"var pageForms = document.forms;"
										.		"if ( pageForms ) for ( i = 0; i < pageForms.length; i++ ) {"
										.			"if ( pageForms[i].id == 'login-form' ) {"
										.				"pageForms[i].return.value = '" . addslashes( 'B:' . $redirect ) . "';"
										.			"}"
										.		"}"
										.	"}"
										.	"if ( window.addEventListener ) {"
										.		"window.addEventListener( 'load', cbLoginReturn, false );"
										.	"} else if ( window.attachEvent ) {"
										.		"window.attachEvent( 'onload', cbLoginReturn );"
										.	"}";

					$document->addScriptDeclaration( $js );

					$session->clear( 'cbLoginReturn' );
				}
			}

			if ( $this->params->get( 'rewrite_urls', 1 ) ) {
				$router					=	$app->getRouter();

				$router->attachBuildRule( array( $this, 'buildRule' ) );
			}
		}
	}

	public function buildRule( &$router, &$uri ) {
		$app							=	JFactory::getApplication();

		if ( $app->isSite() ) {
			if ( $uri->getVar( 'option' ) == 'com_users' ) {
				$uri->setVar( 'option', 'com_comprofiler' );

				switch ( $uri->getVar( 'view' ) ) {
					case 'profile':
						if ( $uri->getVar( 'layout' ) == 'edit' ) {
							$user_id	=	(int) $uri->getVar( 'user_id' );
							$task		=	'userDetails';

							if ( $user_id ) {
								$task	.=	'&user=' . $user_id;
							}
						} else {
							$task		=	null;
						}
						break;
					case 'registration':
						$task			=	'registers';
						break;
					case 'reset':
					case 'remind':
						$task			=	'lostpassword';
						break;
					case 'logout':
						$task			=	'logout';
						break;
					case 'login':
					default:
						$task			=	'login';
						$data			=	$app->getUserState( 'users.login.form.data', array() );
						$return			=	( isset( $data['return'] ) ? $data['return'] : null );

						if ( $return ) {
							$session	=	JFactory::getSession();

							$session->set( 'cbLoginReturn', base64_encode( $return ) );
						}
						break;
				}

				$uri->delVar( 'task' );
				$uri->delVar( 'view' );
				$uri->delVar( 'layout' );

				if ( $task ) {
					$uri->setVar( 'task', $task );
				}

				$Itemid					=	$uri->getVar( 'Itemid' );

				if ( ! $Itemid ) {
					$Itemid				=	( $this->params->get( 'itemids', 1 ) ? $this->getItemid( $task ) : null );
				}

				$uri->delVar( 'Itemid' );

				if ( $Itemid ) {
					$uri->setVar( 'Itemid', $Itemid );
				}
			}
		}
	}

	public function getItemid( $task ) {
		static $items			=	null;

		if ( ! isset( $items ) ) {
			$app				=	JFactory::getApplication();
			$menu				=	$app->getMenu();
			$items				=	$menu->getItems( 'component', 'com_comprofiler' );
		}

		$Itemid					=	null;

		if ( ( $task !== 'userprofile' ) && is_string( $task ) ) {
			if ( $items ) foreach ( $items as $item ) {
				if ( isset( $item->query['task'] ) && ( $item->query['task'] == $task ) ) {
					$Itemid		=	$item->id;
				}
			}
		}

		if ( ( $task === 'userprofile' ) || ( ( ! $Itemid ) && ( ! in_array( $task, array( 'login', 'logout', 'registers', 'lostpassword' ) ) ) ) ) {
			if ( $items ) foreach ( $items as $item ) {
				if ( ! isset( $item->query['task'] ) ) {
					$Itemid		=	$item->id;
				}
			}

			if ( ! $Itemid ) {
				if ( $items ) foreach ( $items as $item ) {
					if ( isset( $item->query['task'] ) && ( $item->query['task'] == 'usersList' ) ) {
						$Itemid	=	$item->id;
					}
				}
			}
		}

		return $Itemid;
	}
}