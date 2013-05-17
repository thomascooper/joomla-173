<?php
/**
* @copyright ? 2009 joomlapolis.com
* @license http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU/GPL version 2
*/

if ( ! ( defined( '_VALID_CB' ) || defined( '_JEXEC' ) || defined( '_VALID_MOS' ) ) ) { die( 'Direct Access to this location is not allowed.' ); }

if ( ! defined( '_CB_MOD_CB_ADMINNAV_VERSION' ) ) { define( '_CB_MOD_CB_ADMINNAV_VERSION', '1.2.1,1.1' ); }

global $_CB_framework, $mainframe;

if ( defined( 'JPATH_ADMINISTRATOR' ) ) {
	if ( ! file_exists( JPATH_ADMINISTRATOR . '/components/com_comprofiler/plugin.foundation.php' ) ) {
		echo 'CB not installed!';
		return;
	}

	include_once( JPATH_ADMINISTRATOR . '/components/com_comprofiler/plugin.foundation.php' );
} else {
	if ( ! file_exists( $mainframe->getCfg( 'absolute_path' ) . '/administrator/components/com_comprofiler/plugin.foundation.php' ) ) {
		echo 'CB not installed!';
		return;
	}

	include_once( $mainframe->getCfg( 'absolute_path' ) . '/administrator/components/com_comprofiler/plugin.foundation.php' );
}

cbimport( 'cb.html' );
cbimport( 'cb.database' );
cbimport( 'language.cbteamplugins' );

if ( ! class_exists( 'cbAdminMenu' ) ) {
	class cbAdminMenu {
		var	$params		=	null;
		var	$display	=	null;
		var	$disabled	=	null;
		var	$bootstrap	=	null;

		function cbAdminMenu( $params ) {
			$this->params			=	$params;
			$this->display			=	$params->get( 'cb_adminnav_display', 1 );

			if ( checkJversion() >= 1 ) {
				$this->disabled		=	JRequest::getInt( 'hidemainmenu' );
			} else {
				$this->disabled		=	cbGetParam( $_REQUEST, 'hidemainmenu', 0 );
			}

			if ( checkJversion() >= 2 ) {
				$this->bootstrap	=	JHtml::isRegistered( 'bootstrap.framework' );
			} else {
				$this->bootstrap	=	false;
			}
		}

		function getDisplay() {
			$return		=	null;

			if ( $this->display == 1 ) {
				$return	.=	$this->getTab();
			} elseif ( $this->display == 2 ) {
				$return	.=	$this->getMenu();
			}

			return $return;
		}

		function getTab() {
			$menus				=	$this->getMenus();
			$return				=	null;

			if ( $menus ) {
				$return			=	'<table class="adminlist">';

				foreach ( $menus as $menu ) {
					if ( isset( $menu['menu'] ) ) {
						$items	=	$menu['menu'];
					} else {
						$items	=	array();
					}

					if ( isset( $menu['component'] ) ) {
						$return	.=	$this->getTabItems( $menu['component'], $items );
					}
				}

				$return			.=		'<thead>'
								.			'<tr>'
								.				'<th class="title">&nbsp;</th>'
								.			'</tr>'
								.		'</thead>'
								.	'</table>';
			}

			return $return;
		}

		function getTabItems( $component, $items ) {
			$com_title				=	( isset( $component['title'] ) ? $component['title'] : null );
			$com_link				=	( isset( $component['link'] ) ? $component['link'] : null );
			$com_access				=	( isset( $component['access'] ) ? $component['access'] : null );

			if ( $com_title && $com_link && $this->checkAccess( $com_access ) ) {
				$return				=	'<thead>'
									.		'<tr>'
									.			'<th class="title"><a href="' . trim( $com_link ) . '">' . CBTxt::T( trim( $com_title ) ) . '</a></th>'
									.		'</tr>'
									.	'</thead>';

				if ( $items ) {
					$return			.=	'<tbody>'
									.		'<tr>'
									.			'<td>'
									.				'<table class="adminlist">';

					foreach ( $items as $item ) {
						$title		=	( isset( $item['title'] ) ? $item['title'] : null );
						$link		=	( isset( $item['link'] ) ? $item['link'] : null );
						$access		=	( isset( $item['access'] ) ? $item['access'] : null );

						if ( $title && $link && $this->checkAccess( $access ) ) {
							$return	.=					'<tr>'
									.						'<td>'
									.							'<ul style="margin: 0px; padding: 0px 0px 0px 20px;">'
									.								'<li><a href="' . trim( $link ) . '">' . CBTxt::T( trim( $title ) ) . '</a></li>'
									.							'</ul>'
									.						'</td>'
									.					'</tr>';
						}
					}

					$return			.=				'</table>'
									.			'</td>'
									.		'</tr>'
									.	'</tbody>';
				}
			}

			return $return;
		}

		function getMenu() {
			global $_CB_framework;

			$live_site				=	str_replace( '/administrator', '', $_CB_framework->getCfg( 'live_site' ) );
			$return					=	null;

			if ( checkJversion() == 2 ) {
				$css				=	'/administrator/modules/mod_cb_adminnav/mod_cb_adminnavj16.css';
			} elseif ( checkJversion() == 1 ) {
				$css				=	'/administrator/modules/mod_cb_adminnav/mod_cb_adminnavj15.css';
			} else {
				$css				=	'/administrator/modules/mod_cb_adminnavj10.css';
			}

			if ( file_exists( $_CB_framework->getCfg( 'absolute_path' ) . $css ) ) {
				$_CB_framework->document->addHeadStyleSheet( $live_site . $css );

				$menus				=	$this->getMenus();

				if ( $menus ) {
					$class			=	array();

					if ( $this->bootstrap ) {
						$class[]	=	'cb_menu';
						$class[]	=	'nav';
					}

					if ( $this->disabled ) {
						$class[]	=	'disabled';
					}

					$return			=	'<div>'
									.		'<ul' . ( ! $this->bootstrap ? ' id="cb_menu"' : null ) . ( $class ? ' class="' . implode( ' ', $class ) . '"' : null ) . '>';

					foreach ( $menus as $menu ) {
						if ( isset( $menu['menu'] ) ) {
							$items	=	$menu['menu'];
						} else {
							$items	=	array();
						}

						if ( isset( $menu['menu_width'] ) ) {
							$width	=	$menu['menu_width'];
						} else {
							$width	=	140;
						}

						if ( isset( $menu['menu_icons'] ) ) {
							$icons	=	$menu['menu_icons'];
						} else {
							$icons	=	null;
						}

						if ( isset( $menu['menu_css'] ) ) {
							$css	=	$menu['menu_css'];
						} else {
							$css	=	null;
						}

						if ( isset( $menu['component'] ) ) {
							$return	.=	$this->getMenuItems( $menu['component'], $items, $width, $icons, $css );
						}
					}

					$return			.=		'</ul>'
									.	'</div>';
				}
			}

			return $return;
		}

		function getMenuItems( $component, $items = array(), $width = 140, $icons = null, $css = null ) {
			global $_CB_framework;

			$com_title					=	( isset( $component['title'] ) ? $component['title'] : null );
			$return						=	null;

			if ( ! $this->disabled ) {
				$com_link				=	( isset( $component['link'] ) ? $component['link'] : null );
				$com_access				=	( isset( $component['access'] ) ? $component['access'] : null );

				if ( $com_title && $com_link && $this->checkAccess( $com_access ) ) {
					if ( $css && file_exists( $css ) ) {
						$_CB_framework->document->addHeadStyleSheet( $css );
					}

					$style				=	( $width && ( ! $this->bootstrap ) ? ' style="width:' . (int) $width . 'px;"' : null );
					$return				=	'<li class="cb_node' . ( $this->bootstrap ? ' dropdown' : null ) . '">'
										.		'<a ' . ( $this->bootstrap ? 'class="dropdown-toggle" data-toggle="dropdown" href="#"' : 'href="' . trim( $com_link ) . '"' ) . '>'
										.			CBTxt::T( trim( $com_title ) )
										.			( $this->bootstrap ? ' <span class="caret"></span>' : null )
										.		'</a>';

					if ( $items ) {
						$return			.=		'<ul' . $style . ( $this->bootstrap ? ' class="dropdown-menu"' : null ) . '>';

						foreach ( $items as $item ) {
							$title		=	( isset( $item['title'] ) ? $item['title'] : null );
							$link		=	( isset( $item['link'] ) ? $item['link'] : null );
							$access		=	( isset( $item['access'] ) ? $item['access'] : null );

							if ( $title && $link && $this->checkAccess( $access ) ) {
								if ( $icons ) {
									$icon	=	'icon-' . trim( $icons ) . '-' . preg_replace( '/[^-a-zA-Z0-9_]/', '', str_replace( ' ', '_', strtolower( trim( $title ) ) ) );
								} else {
									$icon	=	null;
								}

								$return	.=			'<li' . $style . '>'
										.				'<a href="' . trim( $link ) . '"' . ( $icon && ( ! $this->bootstrap ) ? ' class="' . $icon . '"' : null ) . '>'
										.					( $icon && $this->bootstrap ? '<i class="' . $icon . '"></i> ' : null )
										.					'<span>' . CBTxt::T( trim( $title ) ) . '</span>'
										.				'</a>'
										.			'</li>';
							}
						}

						$return			.=		'</ul>';
					}

					$return				.=	'</li>';
				}
			} elseif ( $com_title ) {
				$return					=	'<li class="disabled">'
										.		'<a>' . CBTxt::T( trim( $component['title'] ) ) . '</a>'
										.	'</li>';
			}

			return $return;
		}

		function getCBGJ() {
			global $_CB_framework, $_CB_database;

			$return							=	array();

			if ( file_exists( $_CB_framework->getCfg( 'absolute_path' ) . '/components/com_comprofiler/plugin/user/plug_cbgroupjive' ) ) {
				$query						=	'SELECT ' . $_CB_database->NameQuote( 'id' )
											.	"\n FROM " . $_CB_database->NameQuote( '#__comprofiler_plugin' )
											.	"\n WHERE " . $_CB_database->NameQuote( 'element' )	. ' = ' . $_CB_database->Quote( 'cbgroupjive' );
				$_CB_database->setQuery( $query, 0, 1 );
				$plugin_id					=	$_CB_database->loadResult();

				if ( $plugin_id ) {
					$return['component']	=	array(	'title' => 'GroupJive', 'link' => $_CB_framework->backendUrl( 'index.php?option=com_comprofiler&task=editPlugin&cid=' . $plugin_id ), 'access' => array( 'core.manage' ) );
					$return['menu']			=	array(	array( 'title' => 'Categories', 'link' => $_CB_framework->backendUrl( 'index.php?option=com_comprofiler&task=editPlugin&cid=' . $plugin_id . '&action=categories' ) ),
														array( 'title' => 'Groups', 'link' => $_CB_framework->backendUrl( 'index.php?option=com_comprofiler&task=editPlugin&cid=' . $plugin_id . '&action=groups' ) ),
														array( 'title' => 'Users', 'link' => $_CB_framework->backendUrl( 'index.php?option=com_comprofiler&task=editPlugin&cid=' . $plugin_id . '&action=users' ) ),
														array( 'title' => 'Invites', 'link' => $_CB_framework->backendUrl( 'index.php?option=com_comprofiler&task=editPlugin&cid=' . $plugin_id . '&action=invites' ) ),
														array( 'title' => 'Configuration', 'link' => $_CB_framework->backendUrl( 'index.php?option=com_comprofiler&task=editPlugin&cid=' . $plugin_id . '&action=config' ) ),
														array( 'title' => 'Tools', 'link' => $_CB_framework->backendUrl( 'index.php?option=com_comprofiler&task=editPlugin&cid=' . $plugin_id . '&action=tools' ) ),
														array( 'title' => 'Integrations', 'link' => $_CB_framework->backendUrl( 'index.php?option=com_comprofiler&task=editPlugin&cid=' . $plugin_id . '&action=integrations' ) ),
														array( 'title' => 'Menus', 'link' => $_CB_framework->backendUrl( 'index.php?option=com_comprofiler&task=editPlugin&cid=' . $plugin_id . '&action=menus' ) )
													);

					if ( $this->bootstrap ) {
						array_unshift( $return['menu'], array( 'title' => 'Plugin', 'link' => $_CB_framework->backendUrl( 'index.php?option=com_comprofiler&task=editPlugin&cid=' . $plugin_id ), 'access' => array( 'core.manage' ) ) );
					}

					$return['menu_icons']	=	'cbgj';
					$return['menu_width']	=	100;

					if ( file_exists( $_CB_framework->getCfg( 'absolute_path' ) . '/components/com_comprofiler/plugin/user/plug_cbgroupjive/plugins/cbgroupjiveauto' ) ) {
						$return['menu'][]	=	array( 'title' => 'Auto', 'link' => $_CB_framework->backendUrl( 'index.php?option=com_comprofiler&task=editPlugin&cid=' . $plugin_id . '&action=plugin.auto' ) );
					}
				}
			}

			return $return;
		}

		function getCBSubs() {
			global $_CB_framework, $_CB_database;

			$return							=	array();

			if ( file_exists( $_CB_framework->getCfg( 'absolute_path' ) . '/components/com_comprofiler/plugin/user/plug_cbpaidsubscriptions' ) ) {
				$query						=	'SELECT ' . $_CB_database->NameQuote( 'id' )
											.	"\n FROM " . $_CB_database->NameQuote( '#__comprofiler_plugin' )
											.	"\n WHERE " . $_CB_database->NameQuote( 'element' )	. ' = ' . $_CB_database->Quote( 'cbpaidsubscriptions' );
				$_CB_database->setQuery( $query, 0, 1 );
				$plugin_id					=	$_CB_database->loadResult();

				if ( $plugin_id ) {
					$return['component']	=	array(	'title' => 'Paid Subscriptions', 'link' => $_CB_framework->backendUrl( 'index.php?option=com_comprofiler&task=editPlugin&cid=' . $plugin_id ), 'access' => array( 'core.manage' ) );
					$return['menu']			=	array(	array( 'title' => 'Settings', 'link' => $_CB_framework->backendUrl( 'index.php?option=com_comprofiler&task=editPlugin&cid=' . $plugin_id . '&action=showparams' ), 'access' => array( 'cbsubs.settings', 'com_cbsubs' ) ),
														array( 'title' => 'Gateways', 'link' => $_CB_framework->backendUrl( 'index.php?option=com_comprofiler&task=editPlugin&cid=' . $plugin_id . '&action=showtable&table=gateways' ), 'access' => array( 'cbsubs.gateways', 'com_cbsubs' ) ),
														array( 'title' => 'Plans', 'link' => $_CB_framework->backendUrl( 'index.php?option=com_comprofiler&task=editPlugin&cid=' . $plugin_id . '&action=showtable&table=plans' ), 'access' => array( 'cbsubs.marketing', 'com_cbsubs' ) ),
														array( 'title' => 'Subscriptions', 'link' => $_CB_framework->backendUrl( 'index.php?option=com_comprofiler&task=editPlugin&cid=' . $plugin_id . '&action=showtable&table=subscriptions' ), 'access' => array( 'cbsubs.usersubscriptionview', 'com_cbsubs' ) ),
														array( 'title' => 'Baskets', 'link' => $_CB_framework->backendUrl( 'index.php?option=com_comprofiler&task=editPlugin&cid=' . $plugin_id . '&action=showtable&table=paymentbaskets' ), 'access' => array( array( 'cbsubs.sales', 'cbsubs.financial' ), 'com_cbsubs' ) ),
														array( 'title' => 'Payments', 'link' => $_CB_framework->backendUrl( 'index.php?option=com_comprofiler&task=editPlugin&cid=' . $plugin_id . '&action=showtable&table=payments' ), 'access' => array( array( 'cbsubs.sales', 'cbsubs.financial' ), 'com_cbsubs' ) ),
														array( 'title' => 'Notifications', 'link' => $_CB_framework->backendUrl( 'index.php?option=com_comprofiler&task=editPlugin&cid=' . $plugin_id . '&action=showtable&table=notifications' ), 'access' => array( array( 'cbsubs.settings', 'cbsubs.gateways', 'cbsubs.sales' ), 'com_cbsubs' ) ),
														array( 'title' => 'Currencies', 'link' => $_CB_framework->backendUrl( 'index.php?option=com_comprofiler&task=editPlugin&cid=' . $plugin_id . '&action=showtable&table=currencies' ), 'access' => array( array( 'cbsubs.marketing', 'cbsubs.financial' ), 'com_cbsubs' ) ),
														array( 'title' => 'Statistics', 'link' => $_CB_framework->backendUrl( 'index.php?option=com_comprofiler&task=editPlugin&cid=' . $plugin_id . '&action=showstats' ), 'access' => array( 'cbsubs.financial', 'com_cbsubs' ) ),
														array( 'title' => 'Merchandise', 'link' => $_CB_framework->backendUrl( 'index.php?option=com_comprofiler&task=editPlugin&cid=' . $plugin_id . '&action=showtable&table=merchandises' ), 'access' => array( 'cbsubs.merchandisemanage', 'com_cbsubs' ) ),
														array( 'title' => 'Donations', 'link' => $_CB_framework->backendUrl( 'index.php?option=com_comprofiler&task=editPlugin&cid=' . $plugin_id . '&action=showtable&table=donations' ), 'access' => array( 'cbsubs.donationview', 'com_cbsubs' ) ),
														array( 'title' => 'Import', 'link' => $_CB_framework->backendUrl( 'index.php?option=com_comprofiler&task=editPlugin&cid=' . $plugin_id . '&action=import' ), 'access' => array( array( 'cbsubs.settings', 'cbsubs.recordpayments' ), 'com_cbsubs' ) ),
														array( 'title' => 'History Logs', 'link' => $_CB_framework->backendUrl( 'index.php?option=com_comprofiler&task=editPlugin&cid=' . $plugin_id . '&action=showtable&table=history' ), 'access' => array( array( 'cbsubs.settings', 'cbsubs.gateways' ), 'com_cbsubs' ) )
													);

					if ( $this->bootstrap ) {
						array_unshift( $return['menu'], array( 'title' => 'Payments Center', 'link' => $_CB_framework->backendUrl( 'index.php?option=com_comprofiler&task=editPlugin&cid=' . $plugin_id ), 'access' => array( 'core.manage' ) ) );
					}

					$return['menu_icons']	=	'cbsubs';

					if ( file_exists( $_CB_framework->getCfg( 'absolute_path' ) . '/components/com_comprofiler/plugin/user/plug_cbpaidsubscriptions/plugin/cbsubstax' ) ) {
						$return['menu'][]	=	array( 'title' => 'Taxes', 'link' => $_CB_framework->backendUrl( 'index.php?option=com_comprofiler&task=editPlugin&cid=' . $plugin_id . '&action=showtaxsettings' ), 'access' => array( 'cbsubs.financial', 'com_cbsubs' ) );
					}

					if ( file_exists( $_CB_framework->getCfg( 'absolute_path' ) . '/components/com_comprofiler/plugin/user/plug_cbpaidsubscriptions/plugin/cbsubspromotion' ) ) {
						$return['menu'][]	=	array( 'title' => 'Promotions', 'link' => $_CB_framework->backendUrl( 'index.php?option=com_comprofiler&task=editPlugin&cid=' . $plugin_id . '&action=showpromotionssettings' ), 'access' => array( 'cbsubs.marketing', 'com_cbsubs' ) );
					}

					if ( file_exists( $_CB_framework->getCfg( 'absolute_path' ) . '/components/com_comprofiler/plugin/user/plug_cbpaidsubscriptions/plugin/cbsubsmailer' ) ) {
						$return['menu'][]	=	array( 'title' => 'Mailer', 'link' => $_CB_framework->backendUrl( 'index.php?option=com_comprofiler&task=editPlugin&cid=' . $plugin_id . '&action=showmailersettings' ), 'access' => array( array( 'core.admin', 'cbsubs.marketing' ), 'com_cbsubs' ) );
					}
				}
			}

			return $return;
		}

		function getCB() {
			global $_CB_framework;

			$return						=	array();

			if ( file_exists( $_CB_framework->getCfg( 'absolute_path' ) . '/components/com_comprofiler' ) ) {
				$return['component']	=	array(	'title' => 'Community Builder', 'link' => $_CB_framework->backendUrl( 'index.php?option=com_comprofiler' ), 'access' => array( 'core.manage' ) );
				$return['menu']			=	array(	array( 'title' => 'User Management', 'link' => $_CB_framework->backendUrl( 'index.php?option=com_comprofiler&task=showusers' ), 'access' => array( 'core.manage', 'com_users' ) ),
													array( 'title' => 'Tab Management', 'link' => $_CB_framework->backendUrl( 'index.php?option=com_comprofiler&task=showTab' ), 'access' => array( 'core.edit' ) ),
													array( 'title' => 'Field Management', 'link' => $_CB_framework->backendUrl( 'index.php?option=com_comprofiler&task=showField' ), 'access' => array( 'core.edit' ) ),
													array( 'title' => 'List Management', 'link' => $_CB_framework->backendUrl( 'index.php?option=com_comprofiler&task=showLists' ), 'access' => array( 'core.edit' ) ),
													array( 'title' => 'Plugin Management', 'link' => $_CB_framework->backendUrl( 'index.php?option=com_comprofiler&task=showPlugins' ), 'access' => array( array( 'core.admin', 'core.edit', 'core.edit.state' ) ) ),
													array( 'title' => 'Tools', 'link' => $_CB_framework->backendUrl( 'index.php?option=com_comprofiler&task=tools' ), 'access' => array( array( 'core.admin', 'core.edit' ) ) ),
													array( 'title' => 'Configuration', 'link' => $_CB_framework->backendUrl( 'index.php?option=com_comprofiler&task=showconfig' ), 'access' => array( 'core.admin' ) )
												);

					if ( $this->bootstrap ) {
						array_unshift( $return['menu'], array( 'title' => 'Credits', 'link' => $_CB_framework->backendUrl( 'index.php?option=com_comprofiler' ), 'access' => array( 'core.manage' ) ) );
					}

				$return['menu_icons']	=	'cb';
			}

			return $return;
		}

		function checkAccess( $access ) {
			global $_CB_framework;

			if ( $access ) {
				if ( checkJversion() >= 2 ) {
					$actions			=	( isset( $access[0] ) ? $access[0] : null );

					if ( $actions ) {
						$assetname		=	( isset( $access[1] ) ? $access[1] : 'com_comprofiler' );

						if ( ! is_array( $actions ) ) {
							$actions	=	array( $actions );
						}

						foreach( $actions as $action ) {
							if ( CBuser::getMyInstance()->authoriseAction( $action, $assetname ) ) {
								return true;
							}
						}
					}
				} else {
					if ( in_array( CBuser::getMyUserDataInstance()->gid, $_CB_framework->acl->mapGroupNamesToValues( array( 'Administrator', 'Superadministrator' ) ) ) ) {
						return true;
					}
				}
			} else {
				return true;
			}

			return false;
		}

		function getMenus() {
			$show_cb		=	$this->params->get( 'cb_adminnav_cb', 1 );
			$show_cbsubs	=	$this->params->get( 'cb_adminnav_cbsubs', 1 );
			$show_cbgj		=	$this->params->get( 'cb_adminnav_cbgj', 1 );
			$show_plugins	=	$this->params->get( 'cb_adminnav_plugins', 0 );
			$menus			=	array();

			if ( $show_cb ) {
				$menus[]	=	$this->getCB();
			}

			if ( $show_cbsubs ) {
				$menus[]	=	$this->getCBSubs();
			}

			if ( $show_cbgj ) {
				$menus[]	=	$this->getCBGJ();
			}

			if ( $show_plugins ) {
				global $_PLUGINS;

				$_PLUGINS->loadPluginGroup( 'user' );

				$variables	=	array( $this->params, $this->display, $this->disabled );
				$plugins	=	array_filter( $_PLUGINS->trigger( 'onCBAdminNav', $variables ) );

				$menus		=	array_merge( $menus, $plugins );
			}

			return $menus;
		}
	}
}

$cbAdminMenu	=	new cbAdminMenu( $params );

echo $cbAdminMenu->getDisplay();
?>