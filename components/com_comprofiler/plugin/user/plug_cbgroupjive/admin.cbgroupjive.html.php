<?php
if ( ! ( defined( '_VALID_CB' ) || defined( '_JEXEC' ) || defined( '_VALID_MOS' ) ) ) { die( 'Direct Access to this location is not allowed.' ); }

class HTML_cbgjAdmin {

	/**
	 * render backend plugin
	 *
	 * @param object $menu
	 * @param object $xml
	 * @param moscomprofilerUser  $user
	 * @param object $plugin
	 */
	static public function showPlugin( $menu, $xml, $user, $plugin ) {
		global $_CB_framework;

		HTML_cbgjAdmin::setTitle( $plugin->name, 'cbicon-48-gjplugin' );

		$access_levels		=	$_CB_framework->acl->get_access_children_tree( false );
		$access				=	CBTxt::T( 'Unknown' );

		if ( $access_levels ) foreach( $access_levels as $access_level ) {
			if ( $access_level['value'] == $plugin->access ) {
				$access		=	$access_level['text'];
			}
		}

		$frontend_url		=	'index.php?option=' . $plugin->option . '&task=pluginclass&plugin=' . $plugin->element;
		$frontend_link		=	'<a href="' . cbSef( $frontend_url ) . '" target="_blank">' . $frontend_url . '</a> - <small>(<a href="' . cbgjClass::getPluginURL( array( '', 'menu' ) ) . '">' . CBTxt::Th( 'Create Menu' ) . '</a>)</small>';
		$int_menus			=	cbgjClass::getIntegrations( 'gj_onMenuBE', array( $user, $plugin ), null, 'raw' );
		$rowspan			=	3;

		if ( $int_menus ) {
			$rowspan		+=	max( 0, ( count( array_chunk( $int_menus, 3 ) ) - 1 ) );
		}

		$return				=	'<form action="' . $_CB_framework->backendUrl( 'index.php' ) . '" method="post" name="adminForm" enctype="multipart/form-data" id="adminForm" class="adminForm">'
							.	'<table class="adminlist" width="100%" cellspacing="0" cellpadding="4" border="0">'
							.		'<tbody>'
							.			'<tr class="row">'
							.				'<td colspan="4" style="text-align:center;">' . CBTxt::P( 'Frontend URL: [front_url]', array( '[front_url]' => $frontend_link ) ) . '</td>'
							.			'</tr>'
							.			'<tr class="row">'
							.				'<td width="25%" style="text-align:center;">' . $menu->categories . '</td>'
							.				'<td width="25%" style="text-align:center;">' . $menu->groups . '</td>'
							.				'<td width="25%" style="text-align:center;">' . $menu->users . '</td>'
							.				'<td width="25%" rowspan="' . $rowspan . '" valign="top">'
							.					'<table class="adminlist" width="100%" cellspacing="0" cellpadding="4" border="0">'
							.						'<thead>'
							.							'<tr>'
							.								'<th colspan="2">' . CBTxt::Th( 'Plugin' ) . '</th>'
							.							'</tr>'
							.						'</thead>'
							.						'<tbody>'
							.							'<tr>'
							.								'<th width="90px">' . CBTxt::Th( 'Access' ) . '</td>'
							.								'<td>' . $access . '</td>'
							.							'</tr>'
							.							'<tr>'
							.								'<th width="90px">' . CBTxt::Th( 'Published' ) . '</td>'
							.								'<td>' . ( $plugin->published ? CBTxt::Th( 'Yes' ) : CBTxt::Th( 'No' ) ) . '</td>'
							.							'</tr>'
							.							'<tr>'
							.								'<th width="90px">' . CBTxt::Th( 'Version' ) . '</td>'
							.								'<td>' . htmlspecialchars( $xml->release ) . '</td>'
							.							'</tr>'
							.							'<tr>'
							.								'<th width="90px">' . CBTxt::Th( 'Created' ) . '</td>'
							.								'<td>' . cbFormatDate( $xml->creationDate ) . '</td>'
							.							'</tr>'
							.							'<tr>'
							.								'<th width="90px">' . CBTxt::T( 'Description' ) . '</td>'
							.								'<td>' . htmlspecialchars( $xml->description ) . '</td>'
							.							'</tr>'
							.							'<tr>'
							.								'<th width="90px">' . CBTxt::T( 'Author' ) . '</td>'
							.								'<td><a href="' . htmlspecialchars( $xml->authorUrl ) . '" target="_blank">' . htmlspecialchars( $xml->author ) . '</a></td>'
							.							'</tr>'
							.							'<tr>'
							.								'<th width="90px">' . CBTxt::T( 'Copyright' ) . '</td>'
							.								'<td><a href="' . htmlspecialchars( $xml->copyrightUrl ) . '" target="_blank">' . htmlspecialchars( $xml->copyright ) . '</a></td>'
							.							'</tr>'
							.							'<tr>'
							.								'<th width="90px">' . CBTxt::T( 'License' ) . '</td>'
							.								'<td><a href="' . htmlspecialchars( $xml->licenseUrl ) . '" target="_blank">' . htmlspecialchars( $xml->license ) . '</a></td>'
							.							'</tr>'
							.						'</tbody>'
							.					'</table>'
							.				'</td>'
							.			'</tr>'
							.			'<tr class="row">'
							.				'<td width="25%" style="text-align:center;">' . $menu->invites . '</td>'
							.				'<td width="25%" style="text-align:center;">' . $menu->config . '</td>'
							.				'<td width="25%" style="text-align:center;">' . $menu->tools . '</td>'
							.			'</tr>'
							.			'<tr class="row">'
							.				'<td width="25%" style="text-align:center;">' . $menu->integrations . '</td>'
							.				'<td width="25%" style="text-align:center;">' . $menu->menus . '</td>'
							.				'<td width="25%" style="text-align:center;">' . ( $int_menus ? array_shift( $int_menus ) : null ) . '</td>'
							.			'</tr>';

		if ( $int_menus ) {
			$chunks			=	array_chunk( $int_menus, 3 );

			if ( $chunks ) foreach ( $chunks as $chunk ) {
				$return		.=			'<tr class="row">';

				if ( $chunk ) foreach ( $chunk as $item ) {
					$return	.=				'<td width="25%" style="text-align:center;">' . $item . '</td>';
				}

				$return		.=			'</tr>';
			}
		}

		$return				.=		'</tbody>'
							.	'</table>'
							.	'<input type="hidden" id="option" name="option" value="' . htmlspecialchars( $plugin->option ) . '" />'
							.	'<input type="hidden" id="task" name="task" value="editPlugin" />'
							.	'<input type="hidden" id="cid" name="cid" value="' . (int) $plugin->id . '" />'
							.	cbGetSpoofInputTag( 'plugin' )
							.	'</form>';

		echo $return;
	}

	/**
	 * render backend categories
	 *
	 * @param object $rows
	 * @param object $pageNav
	 * @param array  $input
	 * @param moscomprofilerUser  $user
	 * @param object $plugin
	 */
	static public function showCategories( $rows, $pageNav, $input, $user, $plugin ) {
		global $_CB_framework;

		HTML_cbgjAdmin::setTitle( CBTxt::T( 'Categories' ), 'cbicon-48-gjcategories' );

		$toggleJs			=	"cbToggleAll( this, " . count( $rows ) . ", 'id' );";
		$orderJs			=	"cbsaveorder( this, " . count( $rows ) . ", 'id', 'editPlugin', 'action', 'categories.order' );";
		$oneOrTwo			=	0;

		$frontend_url		=	'index.php?option=' . $plugin->option . '&task=pluginclass&plugin=' . $plugin->element . '&action=categories&func=all';
		$frontend_link		=	'<a href="' . cbSef( $frontend_url ) . '" target="_blank">' . $frontend_url . '</a> - <small>(<a href="' . cbgjClass::getPluginURL( array( 'categories', 'menu', 'all' ) ) . '">' . CBTxt::Th( 'Create Menu' ) . '</a>)</small>';

		$return				=	'<form action="' . $_CB_framework->backendUrl( 'index.php' ) . '" method="post" name="adminForm" enctype="multipart/form-data" id="adminForm" class="adminform">'
							.	'<table class="adminlist" width="100%" cellspacing="0" cellpadding="4" border="0">'
							.		'<thead>'
							.			'<tr>'
							.				'<th colspan="12">' . CBTxt::P( 'Frontend URL: [front_url]', array( '[front_url]' => $frontend_link ) ) . '</th>'
							.			'</tr>'
							.			'<tr>'
							.				'<th colspan="2">&nbsp;</th>'
							.				'<th style="text-align:left;">' . $input['search'] . '</th>'
							.				'<th>' . $input['parent'] . '</th>'
							.				'<th>' . $input['access'] . '</th>'
							.				'<th>' . $input['state'] . '</th>'
							.				'<th>&nbsp;</th>'
							.				'<th>' . $input['creator'] . '</th>'
							.				'<th colspan="3">&nbsp;</th>'
							.				'<th>' . $input['id'] . '</th>'
							.			'</tr>'
							.			'<tr>'
							.				'<th class="title" width="5%">#</th>'
							.				'<th class="title" width="5%"><input type="checkbox" name="toggle" value="" onclick="' . $toggleJs . '" /></th>'
							.				'<th class="title" width="20%">' . CBTxt::Th( 'Category' ) . '</th>'
							.				'<th class="title" width="10%">' . CBTxt::Th( 'Parent' ) . '</th>'
							.				'<th class="title" width="15%">' . CBTxt::Th( 'Access' ) . '</th>'
							.				'<th class="title" width="10%">' . CBTxt::Th( 'State' ) . '</th>'
							.				'<th class="title" width="15%">' . CBTxt::Th( 'Created' ) . '</th>'
							.				'<th class="title" width="5%">' . CBTxt::Th( 'Owner' ) . '</th>'
							.				'<th class="title" colspan="2" width="5%">' . CBTxt::Th( 'Re-Order' ) . '</th>'
							.				'<th class="title" width="5%"><a href="javascript: void(0);" onclick="' . $orderJs . '"><img src="' . $plugin->livePath . '/images/save.png" border="0" width="16" height="16" alt="' . htmlspecialchars( CBTxt::T( 'Save Order' ) ) . '" /></a></th>'
							.				'<th class="title" width="5%">' . CBTxt::Th( 'ID' ) . '</th>'
							.			'</tr>'
							.		'</thead>'
							.		'<tbody>';

		if ( $rows ) for ( $i = 0, $n = count( $rows ); $i < $n; $i++ ) {
			$row			=	$rows[$i];
			$publishImg		=	$plugin->livePath . '/images/' . ( $row->get( 'published' ) ?  'publish.png' : 'unpublish.png' );
			$publishTask	=	( $row->get( 'published' ) ?  'categories.unpublish' : 'categories.publish' );
			$publishJs		=	"cbListItemTask( this, 'editPlugin', 'action', '" . $publishTask . "', 'id', " . $i . " );";
			$orderUpJs		=	"cbListItemTask( this, 'editPlugin', 'action', 'categories.orderup', 'id', " . $i . " );";
			$orderDownJs	=	"cbListItemTask( this, 'editPlugin', 'action', 'categories.orderdown', 'id', " . $i . " );";
			$editJs			=	"cbListItemTask( this, 'editPlugin', 'action', 'categories.edit', 'id', " . $i . " );";

			$return			.=			'<tr class="row' . $oneOrTwo . '">'
							.				'<td style="text-align:center;">' . ( $i + $pageNav->limitstart + 1 ) . '</td>'
							.				'<td style="text-align:center;"><input type="checkbox" id="id' . $i . '" name="id[]" value="' . (int) $row->get( 'id' ) . '" /></td>'
							.				'<td><a href="javascript: void(0);" onclick="' . $editJs . '">' . $row->getName() . '</a></td>'
							.				'<td style="text-align:center;">' . $row->getParent()->getName() . '</td>'
							.				'<td style="text-align:center;">' . $row->getAccess() . '</td>'
							.				'<td style="text-align:center;"><a href="javascript: void(0);" onclick="' . $publishJs . '"><img src="' . $publishImg . '" width="16" height="16" border="0" /></a></td>'
							.				'<td style="text-align:center;">' . cbFormatDate( $row->get( 'date' ) ) . '</td>'
							.				'<td style="text-align:center;">' . $row->getOwnerName() . '</td>'
							.				'<td style="text-align:center;">' . ( ( $i > 0 ) || ( $i + $pageNav->limitstart > 0 ) ? '<a href="javascript: void(0);" onclick="' . $orderUpJs . '"><img src="' . $plugin->livePath . '/images/moveup.png" width="12" height="12" border="0" alt="' . htmlspecialchars( CBTxt::T( 'Move Up' ) ) . '" /></a>' : null ) . '</td>'
							.				'<td style="text-align:center;">' . ( ( $i < $n - 1 ) || ( $i + $pageNav->limitstart < $pageNav->total - 1 ) ? '<a href="javascript: void(0);" onclick="' . $orderDownJs . '"><img src="' . $plugin->livePath . '/images/movedown.png" width="12" height="12" border="0" alt="' . htmlspecialchars( CBTxt::T( 'Move Down' ) ) . '" /></a>' : null ) . '</td>'
							.				'<td style="text-align:center;"><input type="text" name="order[]" size="5" value="' . (int) $row->get( 'ordering' ) . '" class="text_area" style="text-align: center" /></td>'
							.				'<td style="text-align:center;">' . (int) $row->get( 'id' ) . '</td>'
							.			'</tr>';

			$oneOrTwo		=	( $oneOrTwo == 1 ? 2 : 1 );
		} else {
			$return			.=			'<tr>'
							.				'<td colspan="12">';

			if ( $pageNav->searching ) {
				$return		.=					CBTxt::Th( 'No category search results found.' );
			} else {
				$return		.=					CBTxt::Th( 'There currently are no categories.' );
			}

			$return			.=				'</td>'
							.			'</tr>';
		}

		$return				.=		'</tbody>'
							.		'<tfoot>'
							.			'<tr>'
							.				'<th colspan="12">' . $pageNav->getListFooter() . '</th>'
							.			'</tr>'
							.		'</tfoot>'
							.	'</table>'
							.	'<table class="adminlist" width="100%" cellspacing="0" cellpadding="4" border="0" style="margin-top: 10px;">'
							.		'<thead>'
							.			'<tr>'
							.				'<th colspan="2" style="text-align: left;">' . CBTxt::Th( 'Batch Process' ) . '</th>'
							.			'</tr>'
							.		'</thead>'
							.		'<tbody>'
							.			'<tr>'
							.				'<td width="10%">' . CBTxt::Th( 'Parent' ) . '</td>'
							.				'<td>' . $input['batch_parent'] . '</td>'
							.			'</tr>'
							.			'<tr>'
							.				'<td width="10%">' . CBTxt::Th( 'Access' ) . '</td>'
							.				'<td>' . $input['batch_access'] . '</td>'
							.			'</tr>'
							.			'<tr>'
							.				'<td width="10%">' . CBTxt::Th( 'Owner' ) . '</td>'
							.				'<td>' . $input['batch_creator'] . '</td>'
							.			'</tr>'
							.		'</tbody>'
							.		'<tfoot>'
							.			'<tr>'
							.				'<th colspan="2" style="text-align: left;">'
							.					'<input type="button" onclick="cbDoListTask( this, \'editPlugin\', \'action\', \'categories.batch\', \'id\' );return false;" value="' . htmlspecialchars( CBTxt::T( 'Process' ) ) . '">'
							.					' <input type="button" onclick="document.getElementById(\'batch_parent\').value=\'\';document.getElementById(\'batch_access\').value=\'\';document.getElementById(\'batch_creator\').value=\'\';return false;" value="' . htmlspecialchars( CBTxt::T( 'Reset' ) ) . '">'
							.				'</th>'
							.			'</tr>'
							.		'</tfoot>'
							.	'</table>'
							.	'<input type="hidden" id="option" name="option" value="' . htmlspecialchars( $plugin->option ) . '" />'
							.	'<input type="hidden" id="task" name="task" value="editPlugin" />'
							.	'<input type="hidden" id="cid" name="cid" value="' . (int) $plugin->id . '" />'
							.	'<input type="hidden" id="action" name="action" value="categories" />'
							.	cbGetSpoofInputTag( 'plugin' )
							.	'</form>';

		echo $return;
	}

	/**
	 * render backend category edit
	 *
	 * @param object $row
	 * @param array  $input
	 * @param moscomprofilerUser  $user
	 * @param object $plugin
	 */
	static public function showCategoryEdit( $row, $input, $user, $plugin ) {
		global $_CB_framework;

		if ( $row->get( 'id' ) ) {
			$title			=	CBTxt::P( 'Categories: <small>Edit [[category_name]]</small>', array( '[category_name]' => $row->getName() ) );
		} else {
			$title			=	CBTxt::T( 'Categories: <small>New</small>' );
		}

		HTML_cbgjAdmin::setTitle( $title, 'cbicon-48-gjcategories' );

		$tabs				=	new cbTabs( 0, 2 );

		$onEdit				=	cbgjClass::getIntegrations( 'gj_onCategoryEdit', array( $tabs, $row, $user, $plugin ), null, null );

		if ( $row->get( 'id' ) ) {
			$frontend_url	=	'index.php?option=' . $plugin->option . '&task=pluginclass&plugin=' . $plugin->element . '&action=categories&func=show&cat=' . $row->get( 'id' );
			$menu_url		=	cbgjClass::getPluginURL( array( 'categories', 'menu', (int) $row->get( 'id' ) ) );
			$frontend_link	=	'<a href="' . cbSef( $frontend_url ) . '" target="_blank">' . $frontend_url . '</a> - <small>(<a href="' . $menu_url . '">' . CBTxt::Th( 'Create Menu' ) . '</a>)</small>';
		}

		$return				=	'<form action="' . $_CB_framework->backendUrl( 'index.php' ) . '" method="post" name="adminForm" enctype="multipart/form-data" id="adminForm" class="adminform">'
							.	'<table class="adminlist" width="100%" cellspacing="0" cellpadding="4" border="0">';

		if ( $row->get( 'id' ) ) {
			$return			.=		'<thead>'
							.			'<tr>'
							.				'<th colspan="3">' . CBTxt::P( 'Frontend URL: [front_url]', array( '[front_url]' => $frontend_link ) ) . '</th>'
							.			'</tr>'
							.		'</thead>';
		}

		$return				.=		'<tbody>'
							.			'<tr>'
							.				'<th width="15%">' . CBTxt::Th( 'Published' ) . '</th>'
							.				'<td width="40%">' . $input['publish'] . '</td>'
							.				'<td>' . CBTxt::Th( 'Select publish status of category. Unpublished categories will not be visible to the public as well as its groups.' ) . '</td>'
							.			'</tr>'
							.			'<tr>'
							.				'<th width="15%">' . CBTxt::Th( 'Parent' ) . '</th>'
							.				'<td width="40%">' . $input['parent'] . '</td>'
							.				'<td>' . CBTxt::Th( 'Select parent category. Selecting parent category allows for nested category display.' ) . '</td>'
							.			'</tr>'
							.			'<tr>'
							.				'<th width="15%">' . CBTxt::Th( 'Name' ) . '</th>'
							.				'<td width="40%">' . $input['name'] . '</td>'
							.				'<td>' . CBTxt::Th( 'Input category name. This is the name that will distinguish this category from others. Suggested to input something unique and intuitive.' ) . '</td>'
							.			'</tr>'
							.			'<tr>'
							.				'<th width="15%">' . CBTxt::Th( 'Description' ) . '</th>'
							.				'<td width="40%">' . $input['description'] . '</td>'
							.				'<td>' . CBTxt::Th( 'Input category description. Your category description should be short and to the point; describing what your category is all about.' ) . '</td>'
							.			'</tr>'
							.			'<tr>'
							.				'<th width="15%">' . CBTxt::Th( 'Logo' ) . '</th>'
							.				'<td width="40%">'
							.					'<div style="margin-bottom: 10px;">' . $row->getLogo( true ) . '</div>'
							.					( $row->get( 'logo' ) ? '<div style="margin-bottom: 5px;">' . $input['del_logo'] . '</div>' : null )
							.					'<div>' . $input['file'] . '</div>'
							.				'</td>'
							.				'<td>' . CBTxt::Th( 'Select category logo. A logo should represent the focus of your category; please be respectful and tasteful when selecting a logo.' ) . '</td>'
							.			'</tr>'
							.			'<tr>'
							.				'<th width="15%">' . CBTxt::Th( 'Types' ) . '</th>'
							.				'<td width="40%">' . $input['types'] . '</td>'
							.				'<td>' . CBTxt::Th( 'Select categories available group types. Types determine the way a groups is joined (e.g. Invite requires new users to be invited to join a group).' ) . '</td>'
							.			'</tr>'
							.			'<tr>'
							.				'<th width="15%">' . CBTxt::Th( 'Access' ) . '</th>'
							.				'<td width="40%">' . $input['access'] . '</td>'
							.				'<td>' . CBTxt::Th( 'Select category access. Access determines who can effectively see your category. The group selected as well as those above it will have access (e.g. Registered will also be accessible to Author).' ) . '</td>'
							.			'</tr>'
							.			'<tr>'
							.				'<th width="15%">' . CBTxt::Th( 'Create' ) . '</th>'
							.				'<td width="40%">' . $input['create'] . '</td>'
							.				'<td>' . CBTxt::Th( 'Enable or disable the creation of groups in this category. Moderators and owner are exempt from this configuration and can always create groups.' ) . '</td>'
							.			'</tr>'
							.			'<tr>'
							.				'<th width="15%">' . CBTxt::Th( 'Create Access' ) . '</th>'
							.				'<td width="40%">' . $input['create_access'] . '</td>'
							.				'<td>' . CBTxt::Th( 'Select group create access. Create access determines who can create groups in this category. The group selected as well as those above it will have access (e.g. Registered will also be accessible to Author). Moderators and owner are exempt from this configuration and can always create groups.' ) . '</td>'
							.			'</tr>'
							.			'<tr>'
							.				'<th width="15%">' . CBTxt::Th( 'Nested' ) . '</th>'
							.				'<td width="40%">' . $input['nested'] . '</td>'
							.				'<td>' . CBTxt::Th( 'Enable or disable the creation of categories in this category. Moderators and owner are exempt from this configuration and can always create categories.' ) . '</td>'
							.			'</tr>'
							.			'<tr>'
							.				'<th width="15%">' . CBTxt::Th( 'Nested Access' ) . '</th>'
							.				'<td width="40%">' . $input['nested_access'] . '</td>'
							.				'<td>' . CBTxt::Th( 'Select category create access. Create access determines who can create categories in this category. The group selected as well as those above it will have access (e.g. Registered will also be accessible to Author). Moderators and owner are exempt from this configuration and can always create categories.' ) . '</td>'
							.			'</tr>'
							.			'<tr>'
							.				'<th width="15%">' . CBTxt::Th( 'Owner' ) . '</th>'
							.				'<td width="40%">' . $input['owner'] . '</td>'
							.				'<td>' . CBTxt::Th( 'Input category owner. Category owner determines the creator of the category specified as User ID.' ) . '</td>'
							.			'</tr>';

		if ( $onEdit ) {
			$return			.=			'<tr>'
							.				'<th width="15%">' . CBTxt::Th( 'Integrations' ) . '</th>'
							.				'<td colspan="2">'
							.					$tabs->startPane( 'gjIntegrationsTabs' )
							.						$onEdit
							.					$tabs->endPane()
							.				'</td>'
							.			'</tr>';
		}

		$return				.=		'</tbody>'
							.	'</table>'
							.	'<input type="hidden" id="id" name="id" value="' . (int) $row->get( 'id' ) . '" />'
							.	'<input type="hidden" id="date" name="date" value="' . htmlspecialchars( $row->get( 'date', cbgjClass::getUTCDate() ) ) . '" />'
							.	'<input type="hidden" id="ordering" name="order" value="' . (int) $row->get( 'ordering', 99999 ) . '" />'
							.	'<input type="hidden" id="option" name="option" value="' . htmlspecialchars( $plugin->option ) . '" />'
							.	'<input type="hidden" id="task" name="task" value="editPlugin" />'
							.	'<input type="hidden" id="cid" name="cid" value="' . (int) $plugin->id . '" />'
							.	'<input type="hidden" id="action" name="action" value="categories.save" />'
							.	cbGetSpoofInputTag( 'plugin' )
							.	'</form>';

		echo $return;
	}

	/**
	 * render backend groups
	 *
	 * @param object $rows
	 * @param object $pageNav
	 * @param array  $input
	 * @param moscomprofilerUser  $user
	 * @param object $plugin
	 */
	static public function showGroups( $rows, $pageNav, $input, $user, $plugin ) {
		global $_CB_framework;

		HTML_cbgjAdmin::setTitle( CBTxt::T( 'Groups' ), 'cbicon-48-gjgroups' );

		$toggleJs			=	"cbToggleAll( this, " . count( $rows ) . ", 'id' );";
		$orderJs			=	"cbsaveorder( this, " . count( $rows ) . ", 'id', 'editPlugin', 'action', 'groups.order' );";
		$oneOrTwo			=	0;

		$frontend_url		=	'index.php?option=' . $plugin->option . '&task=pluginclass&plugin=' . $plugin->element . '&action=groups&func=all';
		$frontend_link		=	'<a href="' . cbSef( $frontend_url ) . '" target="_blank">' . $frontend_url . '</a> - <small>(<a href="' . cbgjClass::getPluginURL( array( 'groups', 'menu', 'all' ) ) . '">' . CBTxt::Th( 'Create Menu' ) . '</a>)</small>';

		$return				=	'<form action="' . $_CB_framework->backendUrl( 'index.php' ) . '" method="post" name="adminForm" enctype="multipart/form-data" id="adminForm" class="adminform">'
							.	'<table class="adminlist" width="100%" cellspacing="0" cellpadding="4" border="0">'
							.		'<thead>'
							.			'<tr>'
							.				'<th colspan="14">' . CBTxt::P( 'Frontend URL: [front_url]', array( '[front_url]' => $frontend_link ) ) . '</th>'
							.			'</tr>'
							.			'<tr>'
							.				'<th colspan="2">&nbsp;</th>'
							.				'<th style="text-align:left;">' . $input['search'] . '</th>'
							.				'<th>' . $input['category'] . '</th>'
							.				'<th>' . $input['parent'] . '</th>'
							.				'<th>' . $input['access'] . '</th>'
							.				'<th>' . $input['type'] . '</th>'
							.				'<th>' . $input['state'] . '</th>'
							.				'<th>&nbsp;</th>'
							.				'<th>' . $input['creator'] . '</th>'
							.				'<th colspan="3">&nbsp;</th>'
							.				'<th>' . $input['id'] . '</th>'
							.			'</tr>'
							.			'<tr>'
							.				'<th class="title" width="5%">#</th>'
							.				'<th class="title" width="5%"><input type="checkbox" name="toggle" value="" onclick="' . $toggleJs . '" /></th>'
							.				'<th class="title" width="15%">' . CBTxt::Th( 'Group' ) . '</th>'
							.				'<th class="title" width="10%">' . CBTxt::Th( 'Category' ) . '</th>'
							.				'<th class="title" width="15%">' . CBTxt::Th( 'Parent' ) . '</th>'
							.				'<th class="title" width="10%">' . CBTxt::Th( 'Access' ) . '</th>'
							.				'<th class="title" width="5%">' . CBTxt::Th( 'Type' ) . '</th>'
							.				'<th class="title" width="5%">' . CBTxt::Th( 'State' ) . '</th>'
							.				'<th class="title" width="10%">' . CBTxt::Th( 'Created' ) . '</th>'
							.				'<th class="title" width="5%">' . CBTxt::Th( 'Owner' ) . '</th>'
							.				'<th class="title" colspan="2" width="5%">' . CBTxt::Th( 'Re-Order' ) . '</th>'
							.				'<th class="title" width="5%"><a href="javascript: void(0);" onclick="' . $orderJs . '"><img src="' . $plugin->livePath . '/images/save.png" border="0" width="16" height="16" alt="' . htmlspecialchars( CBTxt::T( 'Save Order' ) ) . '" /></a></th>'
							.				'<th class="title" width="5%">' . CBTxt::Th( 'ID' ) . '</th>'
							.			'</tr>'
							.		'</thead>'
							.		'<tbody>';

		if ( $rows ) for ( $i = 0, $n = count( $rows ); $i < $n; $i++ ) {
			$row			=	$rows[$i];
			$publishImg		=	$plugin->livePath . '/images/' . ( $row->get( 'published' ) ?  'publish.png' : 'unpublish.png' );
			$publishTask	=	( $row->get( 'published' ) ?  'groups.unpublish' : 'groups.publish' );
			$publishJs		=	"cbListItemTask( this, 'editPlugin', 'action', '" . $publishTask . "', 'id', " . $i . " );";
			$orderUpJs		=	"cbListItemTask( this, 'editPlugin', 'action', 'groups.orderup', 'id', " . $i . " );";
			$orderDownJs	=	"cbListItemTask( this, 'editPlugin', 'action', 'groups.orderdown', 'id', " . $i . " );";
			$editJs			=	"cbListItemTask( this, 'editPlugin', 'action', 'groups.edit', 'id', " . $i . " );";

			$return			.=			'<tr class="row' . $oneOrTwo . '">'
							.				'<td style="text-align:center;">' . ( $i + $pageNav->limitstart + 1 ) . '</td>'
							.				'<td style="text-align:center;"><input type="checkbox" id="id' . $i . '" name="id[]" value="' . (int) $row->get( 'id' ) . '" /></td>'
							.				'<td><a href="javascript: void(0);" onclick="' . $editJs . '">' . $row->getName() . '</a></td>'
							.				'<td style="text-align:center;">' . $row->getCategory()->getName() . '</td>'
							.				'<td style="text-align:center;">' . $row->getParent()->getName() . '</td>'
							.				'<td style="text-align:center;">' . $row->getAccess() . '</td>'
							.				'<td style="text-align:center;">' . $row->getType() . '</td>'
							.				'<td style="text-align:center;"><a href="javascript: void(0);" onclick="' . $publishJs . '"><img src="' . $publishImg . '" width="16" height="16" border="0" /></a></td>'
							.				'<td style="text-align:center;">' . cbFormatDate( $row->get( 'date' ) ) . '</td>'
							.				'<td style="text-align:center;">' . $row->getOwnerName() . '</td>'
							.				'<td style="text-align:center;">' . ( ( $i > 0 ) || ( $i + $pageNav->limitstart > 0 ) ? '<a href="javascript: void(0);" onclick="' . $orderUpJs . '"><img src="' . $plugin->livePath . '/images/moveup.png" width="12" height="12" border="0" alt="' . htmlspecialchars( CBTxt::T( 'Move Up' ) ) . '" /></a>' : null ) . '</td>'
							.				'<td style="text-align:center;">' . ( ( $i < $n - 1 ) || ( $i + $pageNav->limitstart < $pageNav->total - 1 ) ? '<a href="javascript: void(0);" onclick="' . $orderDownJs . '"><img src="' . $plugin->livePath . '/images/movedown.png" width="12" height="12" border="0" alt="' . htmlspecialchars( CBTxt::T( 'Move Down' ) ) . '" /></a>' : null ) . '</td>'
							.				'<td style="text-align:center;"><input type="text" name="order[]" size="5" value="' . (int) $row->get( 'ordering' ) . '" class="text_area" style="text-align: center" /></td>'
							.				'<td style="text-align:center;">' . (int) $row->get( 'id' ) . '</td>'
							.			'</tr>';

			$oneOrTwo		=	( $oneOrTwo == 1 ? 2 : 1 );
		} else {
			$return			.=			'<tr>'
							.				'<td colspan="14">';

			if ( $pageNav->searching ) {
				$return		.=					CBTxt::Th( 'No group search results found.' );
			} else {
				$return		.=					CBTxt::Th( 'There currently are no groups.' );
			}

			$return			.=				'</td>'
							.			'</tr>';
		}

		$return				.=		'</tbody>'
							.		'<tfoot>'
							.			'<tr>'
							.				'<th colspan="14">' . $pageNav->getListFooter() . '</th>'
							.			'</tr>'
							.		'</tfoot>'
							.	'</table>'
							.	'<table class="adminlist" width="100%" cellspacing="0" cellpadding="4" border="0" style="margin-top: 10px;">'
							.		'<thead>'
							.			'<tr>'
							.				'<th colspan="2" style="text-align: left;">' . CBTxt::Th( 'Batch Process' ) . '</th>'
							.			'</tr>'
							.		'</thead>'
							.		'<tbody>'
							.			'<tr>'
							.				'<td width="10%">' . CBTxt::Th( 'Category' ) . '</td>'
							.				'<td>' . $input['batch_category'] . '</td>'
							.			'</tr>'
							.			'<tr>'
							.				'<td width="10%">' . CBTxt::Th( 'Parent' ) . '</td>'
							.				'<td>' . $input['batch_parent'] . '</td>'
							.			'</tr>'
							.			'<tr>'
							.				'<td width="10%">' . CBTxt::Th( 'Access' ) . '</td>'
							.				'<td>' . $input['batch_access'] . '</td>'
							.			'</tr>'
							.			'<tr>'
							.				'<td width="10%">' . CBTxt::Th( 'Type' ) . '</td>'
							.				'<td>' . $input['batch_type'] . '</td>'
							.			'</tr>'
							.			'<tr>'
							.				'<td width="10%">' . CBTxt::Th( 'Owner' ) . '</td>'
							.				'<td>' . $input['batch_creator'] . '</td>'
							.			'</tr>'
							.		'</tbody>'
							.		'<tfoot>'
							.			'<tr>'
							.				'<th colspan="2" style="text-align: left;">'
							.					'<input type="button" onclick="cbDoListTask( this, \'editPlugin\', \'action\', \'groups.batch\', \'id\' );return false;" value="' . htmlspecialchars( CBTxt::T( 'Process' ) ) . '">'
							.					' <input type="button" onclick="document.getElementById(\'batch_category\').value=\'\';document.getElementById(\'batch_parent\').value=\'\';document.getElementById(\'batch_access\').value=\'\';document.getElementById(\'batch_type\').value=\'\';document.getElementById(\'batch_creator\').value=\'\';return false;" value="' . htmlspecialchars( CBTxt::T( 'Reset' ) ) . '">'
							.				'</th>'
							.			'</tr>'
							.		'</tfoot>'
							.	'</table>'
							.	'<input type="hidden" id="option" name="option" value="' . htmlspecialchars( $plugin->option ) . '" />'
							.	'<input type="hidden" id="task" name="task" value="editPlugin" />'
							.	'<input type="hidden" id="cid" name="cid" value="' . (int) $plugin->id . '" />'
							.	'<input type="hidden" id="action" name="action" value="groups" />'
							.	cbGetSpoofInputTag( 'plugin' )
							.	'</form>';

		echo $return;
	}

	/**
	 * render backend group edit
	 *
	 * @param object $row
	 * @param object $category
	 * @param array  $input
	 * @param moscomprofilerUser  $user
	 * @param object $plugin
	 */
	static public function showGroupEdit( $row, $category, $input, $user, $plugin ) {
		global $_CB_framework;

		if ( $row->get( 'id' ) ) {
			$title			=	CBTxt::P( 'Groups: <small>Edit [[goup_name]]</small>', array( '[goup_name]' => $row->getName() ) );
		} else {
			$title			=	CBTxt::T( 'Groups: <small>New</small>' );
		}

		HTML_cbgjAdmin::setTitle( $title, 'cbicon-48-gjgroups' );

		$tabs				=	new cbTabs( 0, 2 );

		$onEdit				=	cbgjClass::getIntegrations( 'gj_onGroupEdit', array( $tabs, $row, $category, $user, $plugin ), null, null );

		if ( $row->get( 'id' ) ) {
			$frontend_url	=	'index.php?option=' . $plugin->option . '&task=pluginclass&plugin=' . $plugin->element . '&action=groups&func=show&cat=' . $row->get( 'category' ) . '&grp=' . $row->get( 'id' );
			$menu_url		=	cbgjClass::getPluginURL( array( 'groups', 'menu', (int) $row->get( 'id' ) ) );
			$frontend_link	=	'<a href="' . cbSef( $frontend_url ) . '" target="_blank">' . $frontend_url . '</a> - <small>(<a href="' . $menu_url . '">' . CBTxt::Th( 'Create Menu' ) . '</a>)</small>';
		}

		$return				=	'<form action="' . $_CB_framework->backendUrl( 'index.php' ) . '" method="post" name="adminForm" enctype="multipart/form-data" id="adminForm" class="adminform">'
							.	'<table class="adminlist" width="100%" cellspacing="0" cellpadding="4" border="0">';

		if ( $row->get( 'id' ) ) {
			$return			.=		'<thead>'
							.			'<tr>'
							.				'<th colspan="3">' . CBTxt::P( 'Frontend URL: [front_url]', array( '[front_url]' => $frontend_link ) ) . '</th>'
							.			'</tr>'
							.		'</thead>';
		}

		$return				.=		'<tbody>'
							.			'<tr>'
							.				'<th width="15%">' . CBTxt::Th( 'Published' ) . '</td>'
							.				'<td width="40%">' . $input['publish'] . '</td>'
							.				'<td>' . CBTxt::Th( 'Select publish status of group. Unpublished groups will not be visible to the public.' ) . '</td>'
							.			'</tr>'
							.			'<tr>'
							.				'<th width="15%">' . CBTxt::Th( 'Category' ) . '</td>'
							.				'<td width="40%">' . $input['category'] . '</td>'
							.				'<td>' . CBTxt::Th( 'Select group category. This is the category a group will belong to and decide its navigation path.' ) . '</td>'
							.			'</tr>'
							.			'<tr>'
							.				'<th width="15%">' . CBTxt::Th( 'Parent' ) . '</th>'
							.				'<td width="40%">' . $input['parent'] . '</td>'
							.				'<td>' . CBTxt::Th( 'Select parent group. Selecting parent group allows for nested group display.' ) . '</td>'
							.			'</tr>'
							.			'<tr>'
							.				'<th width="15%">' . CBTxt::Th( 'Name' ) . '</td>'
							.				'<td width="40%">' . $input['name'] . '</td>'
							.				'<td>' . CBTxt::Th( 'Input group name. This is the name that will distinguish this group from others. Suggested to input something unique and intuitive.' ) . '</td>'
							.			'</tr>'
							.			'<tr>'
							.				'<th width="15%">' . CBTxt::Th( 'Description' ) . '</td>'
							.				'<td width="40%">' . $input['description'] . '</td>'
							.				'<td>' . CBTxt::Th( 'Input group description. Your group description should be short and to the point; describing what your group is all about.' ) . '</td>'
							.			'</tr>'
							.			'<tr>'
							.				'<th width="15%">' . CBTxt::Th( 'Logo' ) . '</td>'
							.				'<td width="40%">'
							.					'<div style="margin-bottom: 10px;">' . $row->getLogo( true ) . '</div>'
							.					( $row->get( 'logo' ) ? '<div style="margin-bottom: 5px;">' . $input['del_logo'] . '</div>' : null )
							.					'<div>' . $input['file'] . '</div>'
							.				'</td>'
							.				'<td>' . CBTxt::Th( 'Select group logo. A logo should represent the topic of your group; please be respectful and tasteful when selecting a logo.' ) . '</td>'
							.			'</tr>'
							.			'<tr>'
							.				'<th width="15%">' . CBTxt::Th( 'Type' ) . '</td>'
							.				'<td width="40%">' . $input['type'] . '</td>'
							.				'<td>' . CBTxt::Th( 'Select group type. Type determines the way your group is joined (e.g. Invite requires new users to be invited to join your group).' ) . '</td>'
							.			'</tr>'
							.			'<tr>'
							.				'<th width="15%">' . CBTxt::Th( 'Group Access' ) . '</td>'
							.				'<td width="40%">' . $input['access'] . '</td>'
							.				'<td>' . CBTxt::Th( 'Select group access. Access determines who can effectively see your group. The group selected as well as those above it will have access (e.g. Registered will also be accessible to Author).' ) . '</td>'
							.			'</tr>'
							.			'<tr>'
							.				'<th width="15%">' . CBTxt::Th( 'Invite Access' ) . '</td>'
							.				'<td width="40%">' . $input['invite'] . '</td>'
							.				'<td>' . CBTxt::Th( 'Select group invite access. Invite access determines what type of users can invite others to join your group (e.g. Users signify only those a member of your group can invite). The users above the selected will also have access.' ) . '</td>'
							.			'</tr>'
							.			'<tr>'
							.				'<th width="15%">' . CBTxt::Th( 'Users Public' ) . '</td>'
							.				'<td width="40%">' . $input['users'] . '</td>'
							.				'<td>' . CBTxt::Th( 'Select if group users tab is publicly visible.' ) . '</td>'
							.			'</tr>'
							.			'<tr>'
							.				'<th width="15%">' . CBTxt::Th( 'Nested' ) . '</th>'
							.				'<td width="40%">' . $input['nested'] . '</td>'
							.				'<td>' . CBTxt::Th( 'Enable or disable the creation of groups in this group. Moderators and owner are exempt from this configuration and can always create groups.' ) . '</td>'
							.			'</tr>'
							.			'<tr>'
							.				'<th width="15%">' . CBTxt::Th( 'Nested Access' ) . '</th>'
							.				'<td width="40%">' . $input['nested_access'] . '</td>'
							.				'<td>' . CBTxt::Th( 'Select group create access. Create access determines who can create groups in this group. The group selected as well as those above it will have access (e.g. Registered will also be accessible to Author). Moderators and owner are exempt from this configuration and can always create group.' ) . '</td>'
							.			'</tr>'
							.			'<tr>'
							.				'<th width="15%">' . CBTxt::Th( 'Owner' ) . '</td>'
							.				'<td width="40%">' . $input['owner'] . '</td>'
							.				'<td>' . CBTxt::Th( 'Input group owner. Group owner determines the creator of the group specified as User ID.' ) . '</td>'
							.			'</tr>';

		if ( $onEdit ) {
			$return			.=			'<tr>'
							.				'<th width="15%">' . CBTxt::Th( 'Integrations' ) . '</th>'
							.				'<td colspan="2">'
							.					$tabs->startPane( 'gjIntegrationsTabs' )
							.						$onEdit
							.					$tabs->endPane()
							.				'</td>'
							.			'</tr>';
		}

		$return				.=		'</tbody>'
							.	'</table>'
							.	'<input type="hidden" id="id" name="id" value="' . (int) $row->get( 'id' ) . '" />'
							.	'<input type="hidden" id="date" name="date" value="' . htmlspecialchars( $row->get( 'date', cbgjClass::getUTCDate() ) ) . '" />'
							.	'<input type="hidden" id="ordering" name="order" value="' . (int) $row->get( 'ordering', 1 ) . '" />'
							.	'<input type="hidden" id="option" name="option" value="' . htmlspecialchars( $plugin->option ) . '" />'
							.	'<input type="hidden" id="task" name="task" value="editPlugin" />'
							.	'<input type="hidden" id="cid" name="cid" value="' . (int) $plugin->id . '" />'
							.	'<input type="hidden" id="action" name="action" value="groups.save" />'
							.	cbGetSpoofInputTag( 'plugin' )
							.	'</form>';

		echo $return;
	}

	/**
	 * render backend users
	 *
	 * @param object $rows
	 * @param object $pageNav
	 * @param array  $input
	 * @param moscomprofilerUser  $user
	 * @param object $plugin
	 */
	static public function showUsers( $rows, $pageNav, $input, $user, $plugin ) {
		global $_CB_framework;

		HTML_cbgjAdmin::setTitle( CBTxt::T( 'Users' ), 'cbicon-48-gjusers' );

		$toggleJs			=	"cbToggleAll( this, " . count( $rows ) . ", 'id' );";
		$oneOrTwo			=	0;

		$return				=	'<form action="' . $_CB_framework->backendUrl( 'index.php' ) . '" method="post" name="adminForm" enctype="multipart/form-data" id="adminForm" class="adminform">'
							.	'<table class="adminlist" width="100%" cellspacing="0" cellpadding="4" border="0">'
							.		'<thead>'
							.			'<tr>'
							.				'<th colspan="2">&nbsp;</th>'
							.				'<th style="text-align:left;">' . $input['search'] . '</th>'
							.				'<th>' . $input['category'] . '</th>'
							.				'<th>' . $input['group'] . '</th>'
							.				'<th>' . $input['status'] . '</th>'
							.				'<th>&nbsp;</th>'
							.				'<th>' . $input['id'] . '</th>'
							.			'</tr>'
							.			'<tr>'
							.				'<th class="title" width="5%">#</th>'
							.				'<th class="title" width="5%"><input type="checkbox" name="toggle" value="" onclick="' . $toggleJs . '" /></th>'
							.				'<th class="title" width="15%">' . CBTxt::Th( 'User' ) . '</th>'
							.				'<th class="title" width="20%">' . CBTxt::Th( 'Category' ) . '</th>'
							.				'<th class="title" width="20%">' . CBTxt::Th( 'Group' ) . '</th>'
							.				'<th class="title" width="10%">' . CBTxt::Th( 'Status' ) . '</th>'
							.				'<th class="title" width="15%">' . CBTxt::Th( 'Joined' ) . '</th>'
							.				'<th class="title" width="10%">' . CBTxt::Th( 'ID' ) . '</th>'
							.			'</tr>'
							.		'</thead>'
							.		'<tbody>';

		if ( $rows ) for ( $i = 0, $n = count( $rows ); $i < $n; $i++ ) {
			$row			=	$rows[$i];

			if ( $row->get( 'status' ) == 1 ) {
				$statusJs	=	"cbListItemTask( this, 'editPlugin', 'action', 'users.inactive', 'id', " . $i . " );";
				$status		=	'<a href="javascript: void(0);" onclick="' . $statusJs . '"><img src="' . $plugin->livePath. '/images/publish.png" alt="' . htmlspecialchars( CBTxt::T( 'Inactive' ) ) . '" title="' . htmlspecialchars( CBTxt::T( 'Set as Inactive' ) ) . '" /></a>';
			} elseif ( $row->get( 'status' ) == 0 ) {
				$statusJs	=	"cbListItemTask( this, 'editPlugin', 'action', 'users.active', 'id', " . $i . " );";
				$status		=	'<a href="javascript: void(0);" onclick="' . $statusJs . '"><img src="' . $plugin->livePath. '/images/pending.png" alt="' . htmlspecialchars( CBTxt::T( 'Active' ) ) . '" title="' . htmlspecialchars( CBTxt::T( 'Set as Active' ) ) . '" /></a>';
			} else {
				$status		=	null;
			}

			if ( ( $row->get( 'status' ) != -1 ) && ( $row->get( 'status' ) != 4 ) ) {
				$banJs		=	"cbListItemTask( this, 'editPlugin', 'action', 'users.ban', 'id', " . $i . " );";
				$ban		=	'<a href="javascript: void(0);" onclick="' . $banJs . '"><img src="' . $plugin->livePath. '/images/lock.png" alt="' . htmlspecialchars( CBTxt::T( 'Ban' ) ) . '" title="' . htmlspecialchars( CBTxt::T( 'Set as Banned' ) ) . '" /></a>';
			} elseif ( $row->get( 'status' ) == -1 ) {
				$banJs		=	"cbListItemTask( this, 'editPlugin', 'action', 'users.active', 'id', " . $i . " );";
				$ban		=	'<a href="javascript: void(0);" onclick="' . $banJs . '"><img src="' . $plugin->livePath. '/images/unlock.png" alt="' . htmlspecialchars( CBTxt::T( 'Unban' ) ) . '" title="' . htmlspecialchars( CBTxt::T( 'Set as Unbanned' ) ) . '" /></a>';
			} else {
				$ban		=	null;
			}

			if ( $row->get( 'status' ) == 1 ) {
				$promoteJs	=	"cbListItemTask( this, 'editPlugin', 'action', 'users.mod', 'id', " . $i . " );";
				$promote	=	'<a href="javascript: void(0);" onclick="' . $promoteJs . '"><img src="' . $plugin->livePath . '/images/uparrow.png" alt="' . htmlspecialchars( CBTxt::T( 'Promote' ) ) . '" title="' . htmlspecialchars( CBTxt::T( 'Promote to Moderator' ) ) . '" /></a>';
			} elseif ( $row->get( 'status' ) == 2 ) {
				$promoteJs	=	"cbListItemTask( this, 'editPlugin', 'action', 'users.admin', 'id', " . $i . " );";
				$promote	=	'<a href="javascript: void(0);" onclick="' . $promoteJs . '"><img src="' . $plugin->livePath . '/images/uparrow.png" alt="' . htmlspecialchars( CBTxt::T( 'Promote' ) ) . '" title="' . htmlspecialchars( CBTxt::T( 'Promote to Admin' ) ) . '" /></a>';
			} elseif ( $row->get( 'status' ) == 3 ) {
				$promoteJs	=	"cbListItemTask( this, 'editPlugin', 'action', 'users.owner', 'id', " . $i . " );";
				$promote	=	'<a href="javascript: void(0);" onclick="' . $promoteJs . '"><img src="' . $plugin->livePath . '/images/uparrow.png" alt="' . htmlspecialchars( CBTxt::T( 'Promote' ) ) . '" title="' . htmlspecialchars( CBTxt::T( 'Promote to Owner' ) ) . '" /></a>';
			} else {
				$promote	=	null;
			}

			if ( $row->get( 'status' ) == 2 ) {
				$demoteJs	=	"cbListItemTask( this, 'editPlugin', 'action', 'users.active', 'id', " . $i . " );";
				$demote		=	'<a href="javascript: void(0);" onclick="' . $demoteJs . '"><img src="' . $plugin->livePath . '/images/downarrow.png" alt="' . htmlspecialchars( CBTxt::T( 'Demote' ) ) . '" title="' . htmlspecialchars( CBTxt::T( 'Demote to User' ) ) . '" /></a>';
			} elseif ( $row->get( 'status' ) == 3 ) {
				$demoteJs	=	"cbListItemTask( this, 'editPlugin', 'action', 'users.mod', 'id', " . $i . " );";
				$demote		=	'<a href="javascript: void(0);" onclick="' . $demoteJs . '"><img src="' . $plugin->livePath . '/images/downarrow.png" alt="' . htmlspecialchars( CBTxt::T( 'Demote' ) ) . '" title="' . htmlspecialchars( CBTxt::T( 'Demote to Moderator' ) ) . '" /></a>';
			} elseif ( $row->get( 'status' ) == 4 ) {
				$demoteJs	=	"cbListItemTask( this, 'editPlugin', 'action', 'users.admin', 'id', " . $i . " );";
				$demote		=	'<a href="javascript: void(0);" onclick="' . $demoteJs . '"><img src="' . $plugin->livePath . '/images/downarrow.png" alt="' . htmlspecialchars( CBTxt::T( 'Demote' ) ) . '" title="' . htmlspecialchars( CBTxt::T( 'Demote to Admin' ) ) . '" /></a>';
			} else {
				$demote		=	null;
			}

			$editJs			=	"cbListItemTask( this, 'editPlugin', 'action', 'users.edit', 'id', " . $i . " );";

			$return			.=			'<tr class="row' . $oneOrTwo . '">'
							.				'<td style="text-align:center;">' . ( $i + $pageNav->limitstart + 1 ) . '</td>'
							.				'<td style="text-align:center;"><input type="checkbox" id="id' . $i . '" name="id[]" value="' . (int) $row->get( 'id' ) . '" /></td>'
							.				'<td><a href="javascript: void(0);" onclick="' . $editJs . '">' . $row->getOwnerName() . '</a></td>'
							.				'<td style="text-align:center;">' . $row->getCategory()->getName() . '</td>'
							.				'<td style="text-align:center;">' . $row->getGroup()->getName() . '</td>'
							.				'<td style="text-align:center;">'
							.					'<div>' . $row->getStatus() . '</div>'
							.					'<div>' . $status . $ban . $promote . $demote . '</div>'
							.				'</td>'
							.				'<td style="text-align:center;">' . cbFormatDate( $row->get( 'date' ) ) . '</td>'
							.				'<td style="text-align:center;">' . (int) $row->get( 'id' ) . '</td>'
							.			'</tr>';

			$oneOrTwo		=	( $oneOrTwo == 1 ? 2 : 1 );
		} else {
			$return			.=			'<tr>'
							.				'<td colspan="10">';

			if ( $pageNav->searching ) {
				$return		.=					CBTxt::Th( 'No user search results found.' );
			} else {
				$return		.=					CBTxt::Th( 'There currently are no users.' );
			}

			$return			.=				'</td>'
							.			'</tr>';
		}

		$return				.=		'</tbody>'
							.		'<tfoot>'
							.			'<tr>'
							.				'<th colspan="8">' . $pageNav->getListFooter() . '</th>'
							.			'</tr>'
							.		'</tfoot>'
							.	'</table>'
							.	'<table class="adminlist" width="100%" cellspacing="0" cellpadding="4" border="0" style="margin-top: 10px;">'
							.		'<thead>'
							.			'<tr>'
							.				'<th colspan="2" style="text-align: left;">' . CBTxt::Th( 'Batch Process' ) . '</th>'
							.			'</tr>'
							.		'</thead>'
							.		'<tbody>'
							.			'<tr>'
							.				'<td width="10%">' . CBTxt::Th( 'Group' ) . '</td>'
							.				'<td>' . $input['batch_group'] . '</td>'
							.			'</tr>'
							.			'<tr>'
							.				'<td width="10%">' . CBTxt::Th( 'Status' ) . '</td>'
							.				'<td>' . $input['batch_status'] . '</td>'
							.			'</tr>'
							.		'</tbody>'
							.		'<tfoot>'
							.			'<tr>'
							.				'<th colspan="2" style="text-align: left;">'
							.					'<input type="button" onclick="cbDoListTask( this, \'editPlugin\', \'action\', \'users.batch\', \'id\' );return false;" value="' . htmlspecialchars( CBTxt::T( 'Process' ) ) . '">'
							.					' <input type="button" onclick="document.getElementById(\'batch_group\').value=\'\';document.getElementById(\'batch_status\').value=\'\';return false;" value="' . htmlspecialchars( CBTxt::T( 'Reset' ) ) . '">'
							.				'</th>'
							.			'</tr>'
							.		'</tfoot>'
							.	'</table>'
							.	'<input type="hidden" id="option" name="option" value="' . htmlspecialchars( $plugin->option ) . '" />'
							.	'<input type="hidden" id="task" name="task" value="editPlugin" />'
							.	'<input type="hidden" id="cid" name="cid" value="' . (int) $plugin->id . '" />'
							.	'<input type="hidden" id="action" name="action" value="users" />'
							.	cbGetSpoofInputTag( 'plugin' )
							.	'</form>';

		echo $return;
	}

	/**
	 * render backend user edit
	 *
	 * @param object $row
	 * @param object $group
	 * @param object $category
	 * @param array  $input
	 * @param moscomprofilerUser  $user
	 * @param object $plugin
	 */
	static public function showUserEdit( $row, $group, $category, $input, $user, $plugin ) {
		global $_CB_framework;

		if ( $row->get( 'id' ) ) {
			$title	=	CBTxt::P( 'Users: <small>Edit [[user_id]]</small>', array( '[user_id]' => (int) $row->get( 'user_id' ) ) );
		} else {
			$title	=	CBTxt::T( 'Users: <small>New</small>' );
		}

		HTML_cbgjAdmin::setTitle( $title, 'cbicon-48-gjusers' );

		$return		=	'<form action="' . $_CB_framework->backendUrl( 'index.php' ) . '" method="post" name="adminForm" enctype="multipart/form-data" id="adminForm" class="adminform">'
					.	'<table class="adminlist" width="100%" cellspacing="0" cellpadding="4" border="0">'
					.		'<tbody>'
					.			'<tr>'
					.				'<td width="15%">' . CBTxt::Th( 'User' ) . '</td>'
					.				'<td width="40%">' . $input['user'] . '</td>'
					.				'<td>' . CBTxt::Th( 'Input comma separated list of user ids of the users to join the specified group (e.g. 62,43,29).' ) . '</td>'
					.			'</tr>'
					.			'<tr>'
					.				'<td width="15%">' . CBTxt::Th( 'Group' ) . '</td>'
					.				'<td width="40%">' . $input['group'] . '</td>'
					.				'<td>' . CBTxt::Th( 'Select group the specified user is to join.' ) . '</td>'
					.			'</tr>'
					.			'<tr>'
					.				'<td width="15%">' . CBTxt::Th( 'Status' ) . '</td>'
					.				'<td width="40%">' . $input['status'] . '</td>'
					.				'<td>' . CBTxt::Th( 'Select status of the user for the specified group.' ) . '</td>'
					.			'</tr>'
					.		'</tbody>'
					.	'</table>'
					.	'<input type="hidden" id="id" name="id" value="' . (int) $row->get( 'id' ) . '" />'
					.	'<input type="hidden" id="date" name="date" value="' . htmlspecialchars( $row->get( 'date', cbgjClass::getUTCDate() ) ) . '" />'
					.	'<input type="hidden" id="option" name="option" value="' . htmlspecialchars( $plugin->option ) . '" />'
					.	'<input type="hidden" id="task" name="task" value="editPlugin" />'
					.	'<input type="hidden" id="cid" name="cid" value="' . (int) $plugin->id . '" />'
					.	'<input type="hidden" id="action" name="action" value="users.save" />'
					.	cbGetSpoofInputTag( 'plugin' )
					.	'</form>';

		echo $return;
	}

	/**
	 * render backend invites
	 *
	 * @param object $rows
	 * @param object $pageNav
	 * @param array  $input
	 * @param moscomprofilerUser  $user
	 * @param object $plugin
	 */
	static public function showInvites( $rows, $pageNav, $input, $user, $plugin ) {
		global $_CB_framework;

		HTML_cbgjAdmin::setTitle( CBTxt::T( 'Invites' ), 'cbicon-48-gjinvites' );

		$toggleJs		=	"cbToggleAll( this, " . count( $rows ) . ", 'id' );";
		$oneOrTwo		=	0;

		$return			=	'<form action="' . $_CB_framework->backendUrl( 'index.php' ) . '" method="post" name="adminForm">'
						.	'<table class="adminlist" width="100%" cellspacing="0" cellpadding="4" border="0">'
						.		'<thead>'
						.			'<tr>'
						.				'<th colspan="2">&nbsp;</th>'
						.				'<th style="text-align:left;">' . $input['search'] . '</th>'
						.				'<th>' . $input['category'] . '</th>'
						.				'<th>' . $input['group'] . '</th>'
						.				'<th>' . $input['code'] . '</th>'
						.				'<th colspan="2">&nbsp;</th>'
						.				'<th>' . $input['id'] . '</th>'
						.			'</tr>'
						.			'<tr>'
						.				'<th class="title" width="5%">#</th>'
						.				'<th class="title" width="5%"><input type="checkbox" name="toggle" value="" onclick="' . $toggleJs . '" /></th>'
						.				'<th class="title" width="15%">' . CBTxt::Th( 'User' ) . '</th>'
						.				'<th class="title" width="15%">' . CBTxt::Th( 'Category' ) . '</th>'
						.				'<th class="title" width="15%">' . CBTxt::Th( 'Group' ) . '</th>'
						.				'<th class="title" width="10%">' . CBTxt::Th( 'Code' ) . '</th>'
						.				'<th class="title" width="15%">' . CBTxt::Th( 'Invited' ) . '</th>'
						.				'<th class="title" width="15%">' . CBTxt::Th( 'Accepted' ) . '</th>'
						.				'<th class="title" width="5%">' . CBTxt::Th( 'ID' ) . '</th>'
						.			'</tr>'
						.		'</thead>'
						.		'<tbody>';

		if ( $rows ) for ( $i = 0, $n = count( $rows ); $i < $n; $i++ ) {
			$row		=	$rows[$i];

			$return		.=			'<tr class="row' . $oneOrTwo . '">'
						.				'<td style="text-align:center;">' . ( $i + $pageNav->limitstart + 1 ) . '</td>'
						.				'<td style="text-align:center;"><input type="checkbox" id="id' . $i . '" name="id[]" value="' . (int) $row->get( 'id' ) . '" /></td>'
						.				'<td>' . ( $row->user ? $row->getInvitedName() : htmlspecialchars( $row->get( 'email' ) ) ) . '</td>'
						.				'<td style="text-align:center;">' . $row->getCategory()->getName() . '</td>'
						.				'<td style="text-align:center;">' . $row->getGroup()->getName() . '</td>'
						.				'<td style="text-align:center;">' . htmlspecialchars( $row->get( 'code' ) ) . '</td>'
						.				'<td style="text-align:center;">' . cbFormatDate( $row->get( 'invited' ), false ) . '</td>'
						.				'<td style="text-align:center;">' . cbFormatDate( $row->get( 'accepted' ), false ) . '</td>'
						.				'<td style="text-align:center;">' . (int) $row->get( 'id' ) . '</td>'
						.			'</tr>';

			$oneOrTwo	=	( $oneOrTwo == 1 ? 2 : 1 );
		} else {
			$return		.=			'<tr>'
						.				'<td colspan="10">';

			if ( $pageNav->searching ) {
				$return	.=					CBTxt::Th( 'No invite search results found.' );
			} else {
				$return	.=					CBTxt::Th( 'There currently are no invites.' );
			}

			$return		.=				'</td>'
						.			'</tr>';
		}

		$return			.=		'</tbody>'
						.		'<tfoot>'
						.			'<tr>'
						.				'<th colspan="9">' . $pageNav->getListFooter() . '</th>'
						.			'</tr>'
						.		'</tfoot>'
						.	'</table>'
						.	'<input type="hidden" id="option" name="option" value="' . htmlspecialchars( $plugin->option ) . '" />'
						.	'<input type="hidden" id="task" name="task" value="editPlugin" />'
						.	'<input type="hidden" id="cid" name="cid" value="' . (int) $plugin->id . '" />'
						.	'<input type="hidden" id="action" name="action" value="invites" />'
						.	cbGetSpoofInputTag( 'plugin' )
						.	'</form>';

		echo $return;
	}

	/**
	 * render backend config
	 *
	 * @param array  $input
	 * @param moscomprofilerUser  $user
	 * @param object $plugin
	 */
	static public function showConfig( $input, $user, $plugin ) {
		global $_CB_framework;

		HTML_cbgjAdmin::setTitle( CBTxt::T( 'Configuration' ), 'cbicon-48-gjconfig' );

		$tabs	=	new cbTabs( 1, 2 );

		$return	=	'<form action="' . $_CB_framework->backendUrl( 'index.php' ) . '" method="post" name="adminForm" enctype="multipart/form-data" id="adminForm" class="adminform">'
				.	$tabs->startPane( 'gjConfig' )
				.		$tabs->startTab( null, htmlspecialchars( CBTxt::T( 'General' ) ), 'gjGeneral' )
				.			$tabs->startPane( 'gjGeneralTabs' )
				.				$tabs->startTab( null, htmlspecialchars( CBTxt::T( 'Global' ) ), 'gjGeneralGlobal' )
				.					'<table class="adminlist" width="100%" cellspacing="0" cellpadding="4" border="0">'
				.						'<tbody>'
				.							'<tr>'
				.								'<th width="15%">' . CBTxt::Th( 'Title' ) . '</th>'
				.								'<td width="40%">' . $input['general_title'] . '</td>'
				.								'<td>' . CBTxt::Th( 'Optionally replace breadcrumb title from plugin name (CB GroupJive).' ) . '</td>'
				.							'</tr>'
				.							'<tr>'
				.								'<th width="15%">' . CBTxt::Th( 'Template' ) . '</th>'
				.								'<td width="40%">' . $input['general_template'] . '</td>'
				.								'<td>' . CBTxt::Ph( 'Select template to be used for all of GroupJive. If template is incomplete then missing files will be used from the default template. Template files can be located at the following location: [rel_path]/templates', array( '[rel_path]' => $plugin->relPath ) ) . '</td>'
				.							'</tr>'
				.							'<tr>'
				.								'<th width="15%">' . CBTxt::Th( 'Class' ) . '</th>'
				.								'<td width="40%">' . $input['general_class'] . '</td>'
				.								'<td>' . CBTxt::Th( 'Optionally add a class suffix to surrounding DIV encasing all of GroupJive.' ) . '</td>'
				.							'</tr>'
				.							'<tr>'
				.								'<th width="15%">' . CBTxt::Th( 'Item ID' ) . '</th>'
				.								'<td width="40%">' . $input['general_itemid'] . '</td>'
				.								'<td>' . CBTxt::Th( 'Optionally add an Item ID override.' ) . '</td>'
				.							'</tr>'
				.							'<tr>'
				.								'<th width="15%">' . CBTxt::Th( 'Dynamic ID' ) . '</th>'
				.								'<td width="40%">' . $input['general_dynamicid'] . '</td>'
				.								'<td>' . CBTxt::Th( 'Optionally obtain Item ID for every URL. Please note this will perform a query for every URL in attempt to find its menu link.' ) . '</td>'
				.							'</tr>'
				.							'<tr>'
				.								'<th width="15%">' . CBTxt::Th( 'Tooltips' ) . '</th>'
				.								'<td width="40%">' . $input['general_tooltips'] . '</td>'
				.								'<td>' . CBTxt::Th( 'Select method for tooltip display. Applies to frontend only.' ) . '</td>'
				.							'</tr>'
				.							'<tr>'
				.								'<th width="15%">' . CBTxt::Th( 'Panes' ) . '</th>'
				.								'<td width="40%">' . $input['general_panes'] . '</td>'
				.								'<td>' . CBTxt::Th( 'Enable or disable usage of Panes. Please note this will remove ALL menu structures. Only use if another means of menu display is available.' ) . '</td>'
				.							'</tr>'
				.							'<tr>'
				.								'<th width="15%">' . CBTxt::Th( 'Validation' ) . '</th>'
				.								'<td width="40%">' . $input['general_validate'] . '</td>'
				.								'<td>' . CBTxt::Th( 'Enable or disable usage of jQuery form validation. This will prevent users from submitting forms without the required inputs complete. Validation is always done after form submit regardless of this parameter.' ) . '</td>'
				.							'</tr>'
				.							'<tr>'
				.								'<th width="15%">' . CBTxt::Th( 'Folder Permission' ) . '</th>'
				.								'<td width="40%">' . $input['general_dirperms'] . '</td>'
				.								'<td>' . CBTxt::Th( 'Optionally input folder permissions used for folder creation. Only configure if you are sure you know what you are doing.' ) . '</td>'
				.							'</tr>'
				.							'<tr>'
				.								'<th width="15%">' . CBTxt::Th( 'File Permission' ) . '</th>'
				.								'<td width="40%">' . $input['general_fileperms'] . '</td>'
				.								'<td>' . CBTxt::Th( 'Optionally input file permissions used for file creation. Only configure if you are sure you know what you are doing.' ) . '</td>'
				.							'</tr>'
				.							'<tr>'
				.								'<th width="15%">' . CBTxt::Th( 'Word Filter' ) . '</th>'
				.								'<td width="40%">' . $input['general_wordfilter'] . '</td>'
				.								'<td>' . CBTxt::Th( 'Optionally input word filtering applied to all locations user text can be supplied. Separate a filter and its replacement with an equal (=) sign (e.g. idiot=genius). Separate multiple filters by a linebreak. Lanugage strings are supported. Leave replacement blank to simply remove the word (e.g. idiot=). Note only whole words will be replaced.' ) . '</td>'
				.							'</tr>'
				.						'</tbody>'
				.					'</table>'
				.				$tabs->endTab()
				.				$tabs->startTab( null, htmlspecialchars( CBTxt::T( 'Logos' ) ), 'gjGeneralLogos' )
				.					'<table class="adminlist" width="100%" cellspacing="0" cellpadding="4" border="0">'
				.						'<tbody>'
				.							'<tr>'
				.								'<th width="15%">' . CBTxt::Th( 'Size' ) . '</th>'
				.								'<td width="40%">' . $input['logo_size'] . '</td>'
				.								'<td>' . CBTxt::Th( 'Input maximum file size for logos. If blank Community Builder avatar size will be used.' ) . '</td>'
				.							'</tr>'
				.							'<tr>'
				.								'<th width="15%">' . CBTxt::Th( 'Width' ) . '</th>'
				.								'<td width="40%">' . $input['logo_width'] . '</td>'
				.								'<td>' . CBTxt::Th( 'Input maximum logo width. If blank Community Builder avatar width will be used.' ) . '</td>'
				.							'</tr>'
				.							'<tr>'
				.								'<th width="15%">' . CBTxt::Th( 'Height' ) . '</th>'
				.								'<td width="40%">' . $input['logo_height'] . '</td>'
				.								'<td>' . CBTxt::Th( 'Input maximum logo height. If blank Community Builder avatar height will be used.' ) . '</td>'
				.							'</tr>'
				.							'<tr>'
				.								'<th width="15%">' . CBTxt::Th( 'Thumbnail Width' ) . '</th>'
				.								'<td width="40%">' . $input['logo_thumbwidth'] . '</td>'
				.								'<td>' . CBTxt::Th( 'Input maximum logo thumbnail width. If blank Community Builder avatar thumbnail width will be used.' ) . '</td>'
				.							'</tr>'
				.							'<tr>'
				.								'<th width="15%">' . CBTxt::Th( 'Thumbnail Height' ) . '</th>'
				.								'<td width="40%">' . $input['logo_thumbheight'] . '</td>'
				.								'<td>' . CBTxt::Th( 'Input maximum logo thumbnail height. If blank Community Builder avatar thumbnail height will be used.' ) . '</td>'
				.							'</tr>'
				.						'</tbody>'
				.					'</table>'
				.				$tabs->endTab()
				.				$tabs->startTab( null, htmlspecialchars( CBTxt::T( 'Overrides' ) ), 'gjGeneralOverrides' )
				.					'<table class="adminlist" width="100%" cellspacing="0" cellpadding="4" border="0">'
				.						'<tbody>'
				.							'<tr>'
				.								'<th colspan="3" style="text-align: center;">' . CBTxt::Th( 'Please note overrides may not function for all languages dependant on if the language uses variable nouns. Simply exclude the substitution (e.g. [category]) from your translation (e.g. "[category] Forums" => "Category Forums"). See documentation for further details on translating without Overrides as are completely optional within the translation.' ) . '</th>'
				.							'</tr>'
				.							'<tr>'
				.								'<th width="15%">' . CBTxt::Th( 'Overview' ) . '</th>'
				.								'<td width="40%">' . $input['override_overview_s'] . '</td>'
				.								'<td>' . CBTxt::Th( 'Singular string override for Overview.' ) . '</td>'
				.							'</tr>'
				.							'<tr>'
				.								'<th width="15%">' . CBTxt::Th( 'Panel' ) . '</th>'
				.								'<td width="40%">' . $input['override_panel_s'] . '</td>'
				.								'<td>' . CBTxt::Th( 'Singular string override for Panel.' ) . '</td>'
				.							'</tr>'
				.							'<tr>'
				.								'<th width="15%">' . CBTxt::Th( 'Category' ) . '</th>'
				.								'<td width="40%">'
				.									'<div style="margin-bottom:5px;">' . CBTxt::P( 'Singular: [category]', array( '[category]' => $input['override_category_s'] ) ) . '</div>'
				.									'<div>' . CBTxt::Ph( 'Plural: [categories]', array( '[categories]' => $input['override_category_p'] ) ) . '</div>'
				.								'</td>'
				.								'<td>' . CBTxt::Th( 'Singular and Plural string override for category.' ) . '</td>'
				.							'</tr>'
				.							'<tr>'
				.								'<th width="15%">' . CBTxt::Th( 'Group' ) . '</th>'
				.								'<td width="40%">'
				.									'<div style="margin-bottom:5px;">' . CBTxt::P( 'Singular: [group]', array( '[group]' => $input['override_group_s'] ) ) . '</div>'
				.									'<div>' . CBTxt::Ph( 'Plural: [groups]', array( '[groups]' => $input['override_group_p'] ) ) . '</div>'
				.								'</td>'
				.								'<td>' . CBTxt::Th( 'Singular and Plural string override for Group.' ) . '</td>'
				.							'</tr>'
				.							'<tr>'
				.								'<th width="15%">' . CBTxt::Th( 'User' ) . '</th>'
				.								'<td width="40%">'
				.									'<div style="margin-bottom:5px;">' . CBTxt::P( 'Singular: [user]', array( '[user]' => $input['override_user_s'] ) ) . '</div>'
				.									'<div>' . CBTxt::Ph( 'Plural: [users]', array( '[users]' => $input['override_user_p'] ) ) . '</div>'
				.								'</td>'
				.								'<td>' . CBTxt::Th( 'Singular and Plural string override for User.' ) . '</td>'
				.							'</tr>'
				.							'<tr>'
				.								'<th width="15%">' . CBTxt::Th( 'Owner' ) . '</th>'
				.								'<td width="40%">' . $input['override_owner_s'] . '</td>'
				.								'<td>' . CBTxt::Th( 'Singular string override for owner.' ) . '</td>'
				.							'</tr>'
				.							'<tr>'
				.								'<th width="15%">' . CBTxt::Th( 'Moderator' ) . '</th>'
				.								'<td width="40%">'
				.									'<div style="margin-bottom:5px;">' . CBTxt::P( 'Singular: [mod]', array( '[mod]' => $input['override_mod_s'] ) ) . '</div>'
				.									'<div>' . CBTxt::Ph( 'Plural: [users]', array( '[users]' => $input['override_mod_p'] ) ) . '</div>'
				.								'</td>'
				.								'<td>' . CBTxt::Th( 'Singular and Plural string override for Moderator.' ) . '</td>'
				.							'</tr>'
				.							'<tr>'
				.								'<th width="15%">' . CBTxt::Th( 'Admin' ) . '</th>'
				.								'<td width="40%">'
				.									'<div style="margin-bottom:5px;">' . CBTxt::P( 'Singular: [admin]', array( '[admin]' => $input['override_admin_s'] ) ) . '</div>'
				.									'<div>' . CBTxt::Ph( 'Plural: [admins]', array( '[admins]' => $input['override_admin_p'] ) ) . '</div>'
				.								'</td>'
				.								'<td>' . CBTxt::Th( 'Singular and Plural string override for Admin.' ) . '</td>'
				.							'</tr>'
				.						'</tbody>'
				.					'</table>'
				.				$tabs->endTab()
				.			$tabs->endPane()
				.		$tabs->endTab()
				.		$tabs->startTab( null, htmlspecialchars( CBTxt::T( 'Notifications' ) ), 'gjNotifications' )
				.			$tabs->startPane( 'gjNotificationsTabs' )
				.				$tabs->startTab( null, htmlspecialchars( CBTxt::T( 'Global' ) ), 'gjNotificationsGlobal' )
				.					'<table class="adminlist" width="100%" cellspacing="0" cellpadding="4" border="0">'
				.						'<tbody>'
				.							'<tr>'
				.								'<th width="15%">' . CBTxt::Th( 'Notifications' ) . '</th>'
				.								'<td width="40%">' . $input['general_notifications'] . '</td>'
				.								'<td>' . CBTxt::Th( 'Enable or disable sending and configuration of notifications. Moderators are exempt from this configuration.' ) . '</td>'
				.							'</tr>'
				.							'<tr>'
				.								'<th width="15%">' . CBTxt::Th( 'Notify By' ) . '</th>'
				.								'<td width="40%">' . $input['general_notifyby'] . '</td>'
				.								'<td>' . CBTxt::Th( 'Select method for notifications. Notify By determines how users are contacted when a notification is sent.' ) . '</td>'
				.							'</tr>'
				.							'<tr>'
				.								'<th width="15%">' . CBTxt::Th( 'From Name' ) . '</th>'
				.								'<td width="40%">' . $input['notifications_from_name'] . '</td>'
				.								'<td>' . CBTxt::Th( 'Optionally input notification email name. This will override the users name sent with email.' ) . '</td>'
				.							'</tr>'
				.							'<tr>'
				.								'<th width="15%">' . CBTxt::Th( 'From Address' ) . '</th>'
				.								'<td width="40%">' . $input['notifications_from_address'] . '</td>'
				.								'<td>' . CBTxt::Th( 'Optionally input notification email address. This will override the users email address sent with email.' ) . '</td>'
				.							'</tr>'
				.							'<tr>'
				.								'<th width="15%">' . CBTxt::Th( 'Description' ) . '</th>'
				.								'<td width="40%">' . $input['notifications_desc'] . '</td>'
				.								'<td>' . CBTxt::Th( 'Optionally add description to be shown above notifications display.' ) . '</td>'
				.							'</tr>'
				.							'<tr>'
				.								'<th width="15%">' . CBTxt::Th( 'Content Plugins' ) . '</th>'
				.								'<td width="40%">' . $input['notifications_desc_content'] . '</td>'
				.								'<td>' . CBTxt::Th( 'Enable or disable processing of content plugins on notifications description.' ) . '</td>'
				.							'</tr>'
				.						'</tbody>'
				.					'</table>'
				.				$tabs->endTab()
				.				$tabs->startTab( null, htmlspecialchars( CBTxt::T( 'General' ) ), 'gjNotificationsGeneral' )
				.					$tabs->startPane( 'gjNotificationsGeneralTabs' )
				.						$tabs->startTab( null, htmlspecialchars( CBTxt::T( 'Display' ) ), 'gjNotificationsGeneralDisplay' )
				.							'<table class="adminlist" width="100%" cellspacing="0" cellpadding="4" border="0">'
				.								'<tbody>'
				.									'<tr>'
				.										'<th width="15%">' . CBTxt::Th( 'Description' ) . '</th>'
				.										'<td width="40%">' . $input['notifications_desc_gen'] . '</td>'
				.										'<td>' . CBTxt::Th( 'Optionally add description to be shown above general notifications display.' ) . '</td>'
				.									'</tr>'
				.									'<tr>'
				.										'<th width="15%">' . CBTxt::Th( 'Content Plugins' ) . '</th>'
				.										'<td width="40%">' . $input['notifications_desc_gen_content'] . '</td>'
				.										'<td>' . CBTxt::Th( 'Enable or disable processing of content plugins on general notifications description.' ) . '</td>'
				.									'</tr>'
				.								'</tbody>'
				.							'</table>'
				.						$tabs->endTab()
				.						$tabs->startTab( null, htmlspecialchars( CBTxt::T( 'Defaults' ) ), 'gjNotificationsGeneralDefaults' )
				.							'<table class="adminlist" width="100%" cellspacing="0" cellpadding="4" border="0">'
				.								'<tbody>'
				.									'<tr>'
				.										'<th width="15%">' . CBTxt::Th( 'Create Category' ) . '</th>'
				.										'<td width="40%">' . $input['notifications_general_categorynew'] . '</td>'
				.										'<td>' . CBTxt::Th( 'Select default value for general notification parameter "Create of new category".' ) . '</td>'
				.									'</tr>'
				.									'<tr>'
				.										'<th width="15%">' . CBTxt::Th( 'Category Approval' ) . '</th>'
				.										'<td width="40%">' . $input['notifications_general_categoryapprove'] . '</td>'
				.										'<td>' . CBTxt::Th( 'Select default value for general notification parameter "New category requires approval".' ) . '</td>'
				.									'</tr>'
				.									'<tr>'
				.										'<th width="15%">' . CBTxt::Th( 'Update Category' ) . '</th>'
				.										'<td width="40%">' . $input['notifications_general_categoryupdate'] . '</td>'
				.										'<td>' . CBTxt::Th( 'Select default value for general notification parameter "Update of existing category".' ) . '</td>'
				.									'</tr>'
				.									'<tr>'
				.										'<th width="15%">' . CBTxt::Th( 'Delete Category' ) . '</th>'
				.										'<td width="40%">' . $input['notifications_general_categorydelete'] . '</td>'
				.										'<td>' . CBTxt::Th( 'Select default value for general notification parameter "Delete of existing category".' ) . '</td>'
				.									'</tr>'
				.								'</tbody>'
				.							'</table>'
				.						$tabs->endTab()
				.					$tabs->endPane()
				.				$tabs->endTab()
				.				$tabs->startTab( null, htmlspecialchars( CBTxt::T( 'Categories' ) ), 'gjNotificationsCategories' )
				.					$tabs->startPane( 'gjNotificationsCategoriesTabs' )
				.						$tabs->startTab( null, htmlspecialchars( CBTxt::T( 'Display' ) ), 'gjNotificationsCategoriesDisplay' )
				.							'<table class="adminlist" width="100%" cellspacing="0" cellpadding="4" border="0">'
				.								'<tbody>'
				.									'<tr>'
				.										'<th width="15%">' . CBTxt::Th( 'Description' ) . '</th>'
				.										'<td width="40%">' . $input['notifications_desc_cat'] . '</td>'
				.										'<td>' . CBTxt::Th( 'Optionally add description to be shown above categories notifications display.' ) . '</td>'
				.									'</tr>'
				.									'<tr>'
				.										'<th width="15%">' . CBTxt::Th( 'Content Plugins' ) . '</th>'
				.										'<td width="40%">' . $input['notifications_desc_cat_content'] . '</td>'
				.										'<td>' . CBTxt::Th( 'Enable or disable processing of content plugins on categories notifications description.' ) . '</td>'
				.									'</tr>'
				.								'</tbody>'
				.							'</table>'
				.						$tabs->endTab()
				.						$tabs->startTab( null, htmlspecialchars( CBTxt::T( 'Defaults' ) ), 'gjNotificationsCategoriesDefaults' )
				.							'<table class="adminlist" width="100%" cellspacing="0" cellpadding="4" border="0">'
				.								'<tbody>'
				.									'<tr>'
				.										'<th width="15%">' . CBTxt::Th( 'Create Nested' ) . '</th>'
				.										'<td width="40%">' . $input['notifications_category_nestednew'] . '</td>'
				.										'<td>' . CBTxt::Th( 'Select default value for category notification parameter "Create of new nested category".' ) . '</td>'
				.									'</tr>'
				.									'<tr>'
				.										'<th width="15%">' . CBTxt::Th( 'Nested Approval' ) . '</th>'
				.										'<td width="40%">' . $input['notifications_category_nestedapprove'] . '</td>'
				.										'<td>' . CBTxt::Th( 'Select default value for category notification parameter "New nested category requires approval".' ) . '</td>'
				.									'</tr>'
				.									'<tr>'
				.										'<th width="15%">' . CBTxt::Th( 'Update Nested' ) . '</th>'
				.										'<td width="40%">' . $input['notifications_category_nestedupdate'] . '</td>'
				.										'<td>' . CBTxt::Th( 'Select default value for category notification parameter "Update of existing nested category".' ) . '</td>'
				.									'</tr>'
				.									'<tr>'
				.										'<th width="15%">' . CBTxt::Th( 'Delete Nested' ) . '</th>'
				.										'<td width="40%">' . $input['notifications_category_nesteddelete'] . '</td>'
				.										'<td>' . CBTxt::Th( 'Select default value for category notification parameter "Delete of existing nested category".' ) . '</td>'
				.									'</tr>'
				.									'<tr>'
				.										'<th width="15%">' . CBTxt::Th( 'Create Group' ) . '</th>'
				.										'<td width="40%">' . $input['notifications_category_groupnew'] . '</td>'
				.										'<td>' . CBTxt::Th( 'Select default value for category notification parameter "Create of new group".' ) . '</td>'
				.									'</tr>'
				.									'<tr>'
				.										'<th width="15%">' . CBTxt::Th( 'Group Approval' ) . '</th>'
				.										'<td width="40%">' . $input['notifications_category_groupapprove'] . '</td>'
				.										'<td>' . CBTxt::Th( 'Select default value for category notification parameter "New group requires approval".' ) . '</td>'
				.									'</tr>'
				.									'<tr>'
				.										'<th width="15%">' . CBTxt::Th( 'Update Group' ) . '</th>'
				.										'<td width="40%">' . $input['notifications_category_groupupdate'] . '</td>'
				.										'<td>' . CBTxt::Th( 'Select default value for category notification parameter "Update of existing group".' ) . '</td>'
				.									'</tr>'
				.									'<tr>'
				.										'<th width="15%">' . CBTxt::Th( 'Delete Group' ) . '</th>'
				.										'<td width="40%">' . $input['notifications_category_groupdelete'] . '</td>'
				.										'<td>' . CBTxt::Th( 'Select default value for category notification parameter "Delete of existing group".' ) . '</td>'
				.									'</tr>'
				.								'</tbody>'
				.							'</table>'
				.						$tabs->endTab()
				.					$tabs->endPane()
				.				$tabs->endTab()
				.				$tabs->startTab( null, htmlspecialchars( CBTxt::T( 'Groups' ) ), 'gjNotificationsGroups' )
				.					$tabs->startPane( 'gjNotificationsGroupsTabs' )
				.						$tabs->startTab( null, htmlspecialchars( CBTxt::T( 'Display' ) ), 'gjNotificationsGroupsDisplay' )
				.							'<table class="adminlist" width="100%" cellspacing="0" cellpadding="4" border="0">'
				.								'<tbody>'
				.									'<tr>'
				.										'<th width="15%">' . CBTxt::Th( 'Description' ) . '</th>'
				.										'<td width="40%">' . $input['notifications_desc_grp'] . '</td>'
				.										'<td>' . CBTxt::Th( 'Optionally add description to be shown above groups notifications display.' ) . '</td>'
				.									'</tr>'
				.									'<tr>'
				.										'<th width="15%">' . CBTxt::Th( 'Content Plugins' ) . '</th>'
				.										'<td width="40%">' . $input['notifications_desc_grp_content'] . '</td>'
				.										'<td>' . CBTxt::Th( 'Enable or disable processing of content plugins on groups notifications description.' ) . '</td>'
				.									'</tr>'
				.								'</tbody>'
				.							'</table>'
				.						$tabs->endTab()
				.						$tabs->startTab( null, htmlspecialchars( CBTxt::T( 'Defaults' ) ), 'gjNotificationsGroupsDefaults' )
				.							'<table class="adminlist" width="100%" cellspacing="0" cellpadding="4" border="0">'
				.								'<tbody>'
				.									'<tr>'
				.										'<th width="15%">' . CBTxt::Th( 'Create Nested' ) . '</th>'
				.										'<td width="40%">' . $input['notifications_group_nestednew'] . '</td>'
				.										'<td>' . CBTxt::Th( 'Select default value for group notification parameter "Create of new nested group".' ) . '</td>'
				.									'</tr>'
				.									'<tr>'
				.										'<th width="15%">' . CBTxt::Th( 'Nested Approval' ) . '</th>'
				.										'<td width="40%">' . $input['notifications_group_nestedapprove'] . '</td>'
				.										'<td>' . CBTxt::Th( 'Select default value for group notification parameter "New nested group requires approval".' ) . '</td>'
				.									'</tr>'
				.									'<tr>'
				.										'<th width="15%">' . CBTxt::Th( 'Update Nested' ) . '</th>'
				.										'<td width="40%">' . $input['notifications_group_nestedupdate'] . '</td>'
				.										'<td>' . CBTxt::Th( 'Select default value for group notification parameter "Update of existing nested group".' ) . '</td>'
				.									'</tr>'
				.									'<tr>'
				.										'<th width="15%">' . CBTxt::Th( 'Delete Nested' ) . '</th>'
				.										'<td width="40%">' . $input['notifications_group_nesteddelete'] . '</td>'
				.										'<td>' . CBTxt::Th( 'Select default value for group notification parameter "Delete of existing nested group".' ) . '</td>'
				.									'</tr>'
				.									'<tr>'
				.										'<th width="15%">' . CBTxt::Th( 'User Join' ) . '</th>'
				.										'<td width="40%">' . $input['notifications_group_userjoin'] . '</td>'
				.										'<td>' . CBTxt::Th( 'Select default value for group notification parameter "Join of new user".' ) . '</td>'
				.									'</tr>'
				.									'<tr>'
				.										'<th width="15%">' . CBTxt::Th( 'User Leave' ) . '</th>'
				.										'<td width="40%">' . $input['notifications_group_userleave'] . '</td>'
				.										'<td>' . CBTxt::Th( 'Select default value for group notification parameter "Leave of existing user".' ) . '</td>'
				.									'</tr>'
				.									'<tr>'
				.										'<th width="15%">' . CBTxt::Th( 'Invite Sent' ) . '</th>'
				.										'<td width="40%">' . $input['notifications_group_userinvite'] . '</td>'
				.										'<td>' . CBTxt::Th( 'Select default value for group notification parameter "Invite of new user".' ) . '</td>'
				.									'</tr>'
				.									'<tr>'
				.										'<th width="15%">' . CBTxt::Th( 'User Approval' ) . '</th>'
				.										'<td width="40%">' . $input['notifications_group_userapprove'] . '</td>'
				.										'<td>' . CBTxt::Th( 'Select default value for group notification parameter "New user requires approval".' ) . '</td>'
				.									'</tr>'
				.									'<tr>'
				.										'<th width="15%">' . CBTxt::Th( 'Invite Accepted' ) . '</th>'
				.										'<td width="40%">' . $input['notifications_group_inviteaccept'] . '</td>'
				.										'<td>' . CBTxt::Th( 'Select default value for group notification parameter "My group invite requests accepted".' ) . '</td>'
				.									'</tr>'
				.								'</tbody>'
				.							'</table>'
				.						$tabs->endTab()
				.					$tabs->endPane()
				.				$tabs->endTab()
				.			$tabs->endPane()
				.		$tabs->endTab()
				.		$tabs->startTab( null, htmlspecialchars( CBTxt::T( 'Overview' ) ), 'gjOverview' )
				.			$tabs->startPane( 'gjOverviewTabs' )
				.				$tabs->startTab( null, htmlspecialchars( CBTxt::T( 'Display' ) ), 'gjOverviewDisplay' )
				.					'<table class="adminlist" width="100%" cellspacing="0" cellpadding="4" border="0">'
				.						'<tbody>'
				.							'<tr>'
				.								'<th width="15%">' . CBTxt::Th( 'Logo' ) . '</th>'
				.								'<td width="40%">' . $input['overview_logo'] . '</td>'
				.								'<td>' . CBTxt::Ph( 'Select logo to be used on overview pane display. Logo images can be located at the following location: [rel_path]/images', array( '[rel_path]' => $plugin->relPath ) ) . '</td>'
				.							'</tr>'
				.							'<tr>'
				.								'<th width="15%">' . CBTxt::Th( 'Description' ) . '</th>'
				.								'<td width="40%">' . $input['overview_desc'] . '</td>'
				.								'<td>' . CBTxt::Th( 'Optionally add description to be shown on overview pane display.' ) . '</td>'
				.							'</tr>'
				.							'<tr>'
				.								'<th width="15%">' . CBTxt::Th( 'Content Plugins' ) . '</th>'
				.								'<td width="40%">' . $input['overview_desc_content'] . '</td>'
				.								'<td>' . CBTxt::Th( 'Enable or disable processing of content plugins on overview description.' ) . '</td>'
				.							'</tr>'
				.							'<tr>'
				.								'<th width="15%">' . CBTxt::Th( 'New Category' ) . '</th>'
				.								'<td width="40%">' . $input['overview_new_category'] . '</td>'
				.								'<td>' . CBTxt::Th( 'Enable or disable display of quick new category link on overview.' ) . '</td>'
				.							'</tr>'
				.							'<tr>'
				.								'<th width="15%">' . CBTxt::Th( 'New Group' ) . '</th>'
				.								'<td width="40%">' . $input['overview_new_group'] . '</td>'
				.								'<td>' . CBTxt::Th( 'Enable or disable display of quick new group link on overview.' ) . '</td>'
				.							'</tr>'
				.						'</tbody>'
				.					'</table>'
				.				$tabs->endTab()
				.				$tabs->startTab( null, htmlspecialchars( CBTxt::T( 'Messages' ) ), 'gjOverviewMessages' )
				.					'<table class="adminlist" width="100%" cellspacing="0" cellpadding="4" border="0">'
				.						'<tbody>'
				.							'<tr>'
				.								'<th width="15%">' . CBTxt::Th( 'Editor' ) . '</th>'
				.								'<td width="40%">' . $input['overview_message_editor'] . '</td>'
				.								'<td>' . CBTxt::Th( 'Select editor for overview messaging.' ) . '</td>'
				.							'</tr>'
				.						'</tbody>'
				.					'</table>'
				.				$tabs->endTab()
				.				$tabs->startTab( null, htmlspecialchars( CBTxt::T( 'Categories' ) ), 'gjOverviewCategories' )
				.					$tabs->startPane( 'gjOverviewCategoriesTabs' )
				.						$tabs->startTab( null, htmlspecialchars( CBTxt::T( 'Display' ) ), 'gjOverviewCategoriesDisplay' )
				.							'<table class="adminlist" width="100%" cellspacing="0" cellpadding="4" border="0">'
				.								'<tbody>'
				.									'<tr>'
				.										'<th width="15%">' . CBTxt::Th( 'Description Length' ) . '</th>'
				.										'<td width="40%">' . $input['overview_cat_desc_limit'] . '</td>'
				.										'<td>' . CBTxt::Th( 'Optionally input maxiumum description length of categories. Leave blank or 0 for no limit.' ) . '</td>'
				.									'</tr>'
				.									'<tr>'
				.										'<th width="15%">' . CBTxt::Th( 'Order By' ) . '</th>'
				.										'<td width="40%">' . $input['overview_orderby'] . '</td>'
				.										'<td>' . CBTxt::Th( 'Select ordering of categories.' ) . '</td>'
				.									'</tr>'
				.								'</tbody>'
				.							'</table>'
				.						$tabs->endTab()
				.						$tabs->startTab( null, htmlspecialchars( CBTxt::T( 'Paging' ) ), 'gjOverviewCategoriesPaging' )
				.							'<table class="adminlist" width="100%" cellspacing="0" cellpadding="4" border="0">'
				.								'<tbody>'
				.									'<tr>'
				.										'<th width="15%">' . CBTxt::Th( 'Paging' ) . '</th>'
				.										'<td width="40%">' . $input['overview_paging'] . '</td>'
				.										'<td>' . CBTxt::Th( 'Enable or disable usage of paging on categories overview.' ) . '</td>'
				.									'</tr>'
				.									'<tr>'
				.										'<th width="15%">' . CBTxt::T( 'Page Limitbox' ) . '</th>'
				.										'<td width="50%">' . $input['overview_limitbox'] . '</td>'
				.										'<td>' . CBTxt::T( 'Enable or disable usage of page limit on categories overview. Requires Paging to be Enabled.' ) . '</td>'
				.									'</tr>'
				.									'<tr>'
				.										'<th width="15%">' . CBTxt::T( 'Page Limit' ) . '</th>'
				.										'<td width="50%">' . $input['overview_limit'] . '</td>'
				.										'<td>' . CBTxt::T( 'Input default page limit on categories overview. Page limit determines how many categories are displayed per page.' ) . '</td>'
				.									'</tr>'
				.									'<tr>'
				.										'<th width="15%">' . CBTxt::Th( 'Search' ) . '</th>'
				.										'<td width="40%">' . $input['overview_search'] . '</td>'
				.										'<td>' . CBTxt::Th( 'Enable or disable usage of searching on categories overview.' ) . '</td>'
				.									'</tr>'
				.								'</tbody>'
				.							'</table>'
				.						$tabs->endTab()
				.					$tabs->endPane()
				.				$tabs->endTab()
				.			$tabs->endPane()
				.		$tabs->endTab()
				.		$tabs->startTab( null, htmlspecialchars( CBTxt::T( 'Panel' ) ), 'gjPanel' )
				.			$tabs->startPane( 'gjPanelTabs' )
				.				$tabs->startTab( null, htmlspecialchars( CBTxt::T( 'General' ) ), 'gjPanelGeneral' )
				.					'<table class="adminlist" width="100%" cellspacing="0" cellpadding="4" border="0">'
				.						'<tbody>'
				.							'<tr>'
				.								'<th width="15%">' . CBTxt::Th( 'Panel' ) . '</th>'
				.								'<td width="40%">' . $input['overview_panel'] . '</td>'
				.								'<td>' . CBTxt::Th( 'Enable or disable display and usage of panel. Moderators are exempt from this configuration.' ) . '</td>'
				.							'</tr>'
				.							'<tr>'
				.								'<th width="15%">' . CBTxt::Th( 'Logo' ) . '</th>'
				.								'<td width="40%">' . $input['panel_logo'] . '</td>'
				.								'<td>' . CBTxt::Ph( 'Select logo to be used on Panel pane display. Logo images can be located at the following location: [rel_path]/images', array( '[rel_path]' => $plugin->relPath ) ) . '</td>'
				.							'</tr>'
				.							'<tr>'
				.								'<th width="15%">' . CBTxt::Th( 'Description' ) . '</th>'
				.								'<td width="40%">' . $input['panel_desc'] . '</td>'
				.								'<td>' . CBTxt::Th( 'Optionally add description to be shown on panel pane display.' ) . '</td>'
				.							'</tr>'
				.							'<tr>'
				.								'<th width="15%">' . CBTxt::Th( 'Content Plugins' ) . '</th>'
				.								'<td width="40%">' . $input['panel_desc_content'] . '</td>'
				.								'<td>' . CBTxt::Th( 'Enable or disable processing of content plugins on panel description.' ) . '</td>'
				.							'</tr>'
				.							'<tr>'
				.								'<th width="15%">' . CBTxt::Th( 'New Category' ) . '</th>'
				.								'<td width="40%">' . $input['panel_new_category'] . '</td>'
				.								'<td>' . CBTxt::Th( 'Enable or disable display of quick new category link on panel.' ) . '</td>'
				.							'</tr>'
				.							'<tr>'
				.								'<th width="15%">' . CBTxt::Th( 'New Group' ) . '</th>'
				.								'<td width="40%">' . $input['panel_new_group'] . '</td>'
				.								'<td>' . CBTxt::Th( 'Enable or disable display of quick new group link on panel.' ) . '</td>'
				.							'</tr>'
				.						'</tbody>'
				.					'</table>'
				.				$tabs->endTab()
				.				$tabs->startTab( null, htmlspecialchars( CBTxt::T( 'Categories' ) ), 'gjPanelCategories' )
				.					'<table class="adminlist" width="100%" cellspacing="0" cellpadding="4" border="0">'
				.						'<tbody>'
				.							'<tr>'
				.								'<th width="15%">' . CBTxt::Th( 'Display' ) . '</th>'
				.								'<td width="40%">' . $input['panel_category_display'] . '</td>'
				.								'<td>' . CBTxt::Th( 'Enable or disable display of categories menu link on Panel.' ) . '</td>'
				.							'</tr>'
				.						'</tbody>'
				.					'</table>'
				.				$tabs->endTab()
				.				$tabs->startTab( null, htmlspecialchars( CBTxt::T( 'Groups' ) ), 'gjPanelGroups' )
				.					'<table class="adminlist" width="100%" cellspacing="0" cellpadding="4" border="0">'
				.						'<tbody>'
				.							'<tr>'
				.								'<th width="15%">' . CBTxt::Th( 'Display' ) . '</th>'
				.								'<td width="40%">' . $input['panel_group_display'] . '</td>'
				.								'<td>' . CBTxt::Th( 'Enable or disable display of groups menu link on Panel.' ) . '</td>'
				.							'</tr>'
				.						'</tbody>'
				.					'</table>'
				.				$tabs->endTab()
				.				$tabs->startTab( null, htmlspecialchars( CBTxt::T( 'Joined' ) ), 'gjPanelJoined' )
				.					'<table class="adminlist" width="100%" cellspacing="0" cellpadding="4" border="0">'
				.						'<tbody>'
				.							'<tr>'
				.								'<th width="15%">' . CBTxt::Th( 'Display' ) . '</th>'
				.								'<td width="40%">' . $input['panel_joined_display'] . '</td>'
				.								'<td>' . CBTxt::Th( 'Enable or disable display of Joined menu link on Panel.' ) . '</td>'
				.							'</tr>'
				.						'</tbody>'
				.					'</table>'
				.				$tabs->endTab()
				.				$tabs->startTab( null, htmlspecialchars( CBTxt::T( 'Invites' ) ), 'gjPanelInvites' )
				.					'<table class="adminlist" width="100%" cellspacing="0" cellpadding="4" border="0">'
				.						'<tbody>'
				.							'<tr>'
				.								'<th width="15%">' . CBTxt::Th( 'Display' ) . '</th>'
				.								'<td width="40%">' . $input['panel_invites_display'] . '</td>'
				.								'<td>' . CBTxt::Th( 'Enable or disable display of Invites menu link on Panel.' ) . '</td>'
				.							'</tr>'
				.						'</tbody>'
				.					'</table>'
				.				$tabs->endTab()
				.				$tabs->startTab( null, htmlspecialchars( CBTxt::T( 'Invited' ) ), 'gjPanelInvited' )
				.					'<table class="adminlist" width="100%" cellspacing="0" cellpadding="4" border="0">'
				.						'<tbody>'
				.							'<tr>'
				.								'<th width="15%">' . CBTxt::Th( 'Display' ) . '</th>'
				.								'<td width="40%">' . $input['panel_invited_display'] . '</td>'
				.								'<td>' . CBTxt::Th( 'Enable or disable display of Invited To menu link on Panel.' ) . '</td>'
				.							'</tr>'
				.						'</tbody>'
				.					'</table>'
				.				$tabs->endTab()
				.			$tabs->endPane()
				.		$tabs->endTab()
				.		$tabs->startTab( null, htmlspecialchars( CBTxt::T( 'Categories' ) ), 'gjCategories' )
				.			$tabs->startPane( 'gjCategoriesTabs' )
				.				$tabs->startTab( null, htmlspecialchars( CBTxt::T( 'General' ) ), 'gjCategoriesGeneral' )
				.					'<table class="adminlist" width="100%" cellspacing="0" cellpadding="4" border="0">'
				.						'<tbody>'
				.							'<tr>'
				.								'<th width="15%">' . CBTxt::Th( 'Logo' ) . '</th>'
				.								'<td width="40%">' . $input['category_logo'] . '</td>'
				.								'<td>' . CBTxt::Ph( 'Select default logo for newly created categories or categories without a logo. Logo images can be located at the following location: [rel_path]/images', array( '[rel_path]' => $plugin->relPath ) ) . '</td>'
				.							'</tr>'
				.							'<tr>'
				.								'<th width="15%">' . CBTxt::Th( 'Create' ) . '</th>'
				.								'<td width="40%">' . $input['category_create'] . '</td>'
				.								'<td>' . CBTxt::Th( 'Enable or disable the creation of categories. Moderators are exempt from this configuration.' ) . '</td>'
				.							'</tr>'
				.							'<tr>'
				.								'<th width="15%">' . CBTxt::Th( 'Create Access' ) . '</th>'
				.								'<td width="40%">' . $input['category_create_access'] . '</td>'
				.								'<td>' . CBTxt::Th( 'Select category create access. Access determines who can create categories. The group selected as well as those above it will have access (e.g. Registered will also be accessible to Author). Moderators are exempt from this configuration.' ) . '</td>'
				.							'</tr>'
				.							'<tr>'
				.								'<th width="15%">' . CBTxt::Th( 'Nested' ) . '</th>'
				.								'<td width="40%">' . $input['category_nested'] . '</td>'
				.								'<td>' . CBTxt::Th( 'Enable or disable nested categories. Nested categories allow categories within categories. Moderatorsa are exempt from this configuration and can always create nested categories.' ) . '</td>'
				.							'</tr>'
				.							'<tr>'
				.								'<th width="15%">' . CBTxt::Th( 'Nested Access' ) . '</th>'
				.								'<td width="40%">' . $input['category_nested_access'] . '</td>'
				.								'<td>' . CBTxt::Th( 'Select category nested access. Access determines who can create nested categories. The group selected as well as those above it will have access (e.g. Registered will also be accessible to Author). Moderators are exempt from this configuration.' ) . '</td>'
				.							'</tr>'
				.							'<tr>'
				.								'<th width="15%">' . CBTxt::Th( 'Approve' ) . '</th>'
				.								'<td width="40%">' . $input['category_approve'] . '</td>'
				.								'<td>' . CBTxt::Th( 'Enable or disable approval of newly created categories. Categories will require approval by a Moderator to be published. Moderators are exempt from this configuration.' ) . '</td>'
				.							'</tr>'
				.							'<tr>'
				.								'<th width="15%">' . CBTxt::Th( 'Limit' ) . '</th>'
				.								'<td width="40%">' . $input['category_limit'] . '</td>'
				.								'<td>' . CBTxt::Th( 'Set the number of categories a user is allowed to create. Total is based off published and unpublished categories. Leave blank or 0 for unlimited. Moderators are exempt from this configuration.' ) . '</td>'
				.							'</tr>'
				.							'<tr>'
				.								'<th width="15%">' . CBTxt::Th( 'Captcha' ) . '</th>'
				.								'<td width="40%">' . $input['category_captcha'] . '</td>'
				.								'<td>' . CBTxt::Th( 'Enable or disable usage of Captcha on new category creation. Requires latest CB Captcha or integrated Captcha to be installed and published. Moderators are exempt from this configuration.' ) . '</td>'
				.							'</tr>'
				.							'<tr>'
				.								'<th width="15%">' . CBTxt::Th( 'New Category' ) . '</th>'
				.								'<td width="40%">' . $input['category_new_category'] . '</td>'
				.								'<td>' . CBTxt::Th( 'Enable or disable display of quick new nested category link on categories.' ) . '</td>'
				.							'</tr>'
				.							'<tr>'
				.								'<th width="15%">' . CBTxt::Th( 'New Group' ) . '</th>'
				.								'<td width="40%">' . $input['category_new_group'] . '</td>'
				.								'<td>' . CBTxt::Th( 'Enable or disable display of quick new group link on categories.' ) . '</td>'
				.							'</tr>'
				.							'<tr>'
				.								'<th width="15%">' . CBTxt::Th( 'Hide Empty' ) . '</th>'
				.								'<td width="40%">' . $input['category_hide_empty'] . '</td>'
				.								'<td>' . CBTxt::Th( 'Enable or disable display of categories with no groups for all displays. Note users with permission to create groups or nested categories will see the category. Group count check only includes a categories direct groups and does not check for nested category groups.' ) . '</td>'
				.							'</tr>'
				.						'</tbody>'
				.					'</table>'
				.				$tabs->endTab()
				.				$tabs->startTab( null, htmlspecialchars( CBTxt::T( 'Description' ) ), 'gjCategoriesDescription' )
				.					'<table class="adminlist" width="100%" cellspacing="0" cellpadding="4" border="0">'
				.						'<tbody>'
				.							'<tr>'
				.								'<th width="15%">' . CBTxt::Th( 'Editor' ) . '</th>'
				.								'<td width="40%">' . $input['category_editor'] . '</td>'
				.								'<td>' . CBTxt::Th( 'Select editor for category description.' ) . '</td>'
				.							'</tr>'
				.							'<tr>'
				.								'<th width="15%">' . CBTxt::Th( 'Input Limit' ) . '</th>'
				.								'<td width="40%">' . $input['category_desc_inputlimit'] . '</td>'
				.								'<td>' . CBTxt::Th( 'Input category description character count limit. If left blank or zero no limit will be applied. Moderators are exempt from this configuration.' ) . '</td>'
				.							'</tr>'
				.							'<tr>'
				.								'<th width="15%">' . CBTxt::Th( 'Content Plugins' ) . '</th>'
				.								'<td width="40%">' . $input['category_desc_content'] . '</td>'
				.								'<td>' . CBTxt::Th( 'Enable or disable processing of content plugins on category description.' ) . '</td>'
				.							'</tr>'
				.						'</tbody>'
				.					'</table>'
				.				$tabs->endTab()
				.				$tabs->startTab( null, htmlspecialchars( CBTxt::T( 'Messages' ) ), 'gjCategoriesMessages' )
				.					'<table class="adminlist" width="100%" cellspacing="0" cellpadding="4" border="0">'
				.						'<tbody>'
				.							'<tr>'
				.								'<th width="15%">' . CBTxt::Th( 'Display' ) . '</th>'
				.								'<td width="40%">' . $input['category_message'] . '</td>'
				.								'<td>' . CBTxt::Th( 'Enable or disable messaging of a categories groups. Moderators are exempt from this configuration.' ) . '</td>'
				.							'</tr>'
				.							'<tr>'
				.								'<th width="15%">' . CBTxt::Th( 'Editor' ) . '</th>'
				.								'<td width="40%">' . $input['category_message_editor'] . '</td>'
				.								'<td>' . CBTxt::Th( 'Select editor for category messaging.' ) . '</td>'
				.							'</tr>'
				.							'<tr>'
				.								'<th width="15%">' . CBTxt::Th( 'Captcha' ) . '</th>'
				.								'<td width="40%">' . $input['category_message_captcha'] . '</td>'
				.								'<td>' . CBTxt::Th( 'Enable or disable usage of Captcha on category messaging. Requires latest CB Captcha or integrated Captcha to be installed and published. Moderators are exempt from this configuration.' ) . '</td>'
				.							'</tr>'
				.						'</tbody>'
				.					'</table>'
				.				$tabs->endTab()
				.				$tabs->startTab( null, htmlspecialchars( CBTxt::T( 'Nested' ) ), 'gjCategoriesNested' )
				.					$tabs->startPane( 'gjCategoriesNestedTabs' )
				.						$tabs->startTab( null, htmlspecialchars( CBTxt::T( 'Display' ) ), 'gjCategoriesNestedDisplay' )
				.							'<table class="adminlist" width="100%" cellspacing="0" cellpadding="4" border="0">'
				.								'<tbody>'
				.									'<tr>'
				.										'<th width="15%">' . CBTxt::Th( 'Description Length' ) . '</th>'
				.										'<td width="40%">' . $input['category_nested_desc_limit'] . '</td>'
				.										'<td>' . CBTxt::Th( 'Optionally input maxiumum description length of categories. Leave blank or 0 for no limit.' ) . '</td>'
				.									'</tr>'
				.									'<tr>'
				.										'<th width="15%">' . CBTxt::Th( 'Order By' ) . '</th>'
				.										'<td width="40%">' . $input['category_nested_orderby'] . '</td>'
				.										'<td>' . CBTxt::Th( 'Select ordering of categories.' ) . '</td>'
				.									'</tr>'
				.								'</tbody>'
				.							'</table>'
				.						$tabs->endTab()
				.						$tabs->startTab( null, htmlspecialchars( CBTxt::T( 'Paging' ) ), 'gjCategoriesNestedPaging' )
				.							'<table class="adminlist" width="100%" cellspacing="0" cellpadding="4" border="0">'
				.								'<tbody>'
				.									'<tr>'
				.										'<th width="15%">' . CBTxt::Th( 'Paging' ) . '</th>'
				.										'<td width="40%">' . $input['category_nested_paging'] . '</td>'
				.										'<td>' . CBTxt::Th( 'Enable or disable usage of paging on categories.' ) . '</td>'
				.									'</tr>'
				.									'<tr>'
				.										'<th width="15%">' . CBTxt::T( 'Page Limitbox' ) . '</th>'
				.										'<td width="50%">' . $input['category_nested_limitbox'] . '</td>'
				.										'<td>' . CBTxt::T( 'Enable or disable usage of page limit on categories. Requires Paging to be Enabled.' ) . '</td>'
				.									'</tr>'
				.									'<tr>'
				.										'<th width="15%">' . CBTxt::T( 'Page Limit' ) . '</th>'
				.										'<td width="50%">' . $input['category_nested_limit'] . '</td>'
				.										'<td>' . CBTxt::T( 'Input default page limit on categories. Page limit determines how many categories are displayed per page.' ) . '</td>'
				.									'</tr>'
				.									'<tr>'
				.										'<th width="15%">' . CBTxt::Th( 'Search' ) . '</th>'
				.										'<td width="40%">' . $input['category_nested_search'] . '</td>'
				.										'<td>' . CBTxt::Th( 'Enable or disable usage of searching on categories.' ) . '</td>'
				.									'</tr>'
				.								'</tbody>'
				.							'</table>'
				.						$tabs->endTab()
				.					$tabs->endPane()
				.				$tabs->endTab()
				.				$tabs->startTab( null, htmlspecialchars( CBTxt::T( 'All' ) ), 'gjCategoriesAll' )
				.					$tabs->startPane( 'gjCategoriesAllTabs' )
				.						$tabs->startTab( null, htmlspecialchars( CBTxt::T( 'Display' ) ), 'gjCategoriesAllDisplay' )
				.							'<table class="adminlist" width="100%" cellspacing="0" cellpadding="4" border="0">'
				.								'<tbody>'
				.									'<tr>'
				.										'<th width="15%">' . CBTxt::Th( 'Description Length' ) . '</th>'
				.										'<td width="40%">' . $input['category_all_desc_limit'] . '</td>'
				.										'<td>' . CBTxt::Th( 'Optionally input maxiumum description length of categories. Leave blank or 0 for no limit.' ) . '</td>'
				.									'</tr>'
				.									'<tr>'
				.										'<th width="15%">' . CBTxt::Th( 'Order By' ) . '</th>'
				.										'<td width="40%">' . $input['category_all_orderby'] . '</td>'
				.										'<td>' . CBTxt::Th( 'Select ordering of categories.' ) . '</td>'
				.									'</tr>'
				.								'</tbody>'
				.							'</table>'
				.						$tabs->endTab()
				.						$tabs->startTab( null, htmlspecialchars( CBTxt::T( 'Paging' ) ), 'gjCategoriesAllPaging' )
				.							'<table class="adminlist" width="100%" cellspacing="0" cellpadding="4" border="0">'
				.								'<tbody>'
				.									'<tr>'
				.										'<th width="15%">' . CBTxt::Th( 'Paging' ) . '</th>'
				.										'<td width="40%">' . $input['category_all_paging'] . '</td>'
				.										'<td>' . CBTxt::Th( 'Enable or disable usage of paging on categories.' ) . '</td>'
				.									'</tr>'
				.									'<tr>'
				.										'<th width="15%">' . CBTxt::T( 'Page Limitbox' ) . '</th>'
				.										'<td width="50%">' . $input['category_all_limitbox'] . '</td>'
				.										'<td>' . CBTxt::T( 'Enable or disable usage of page limit on categories. Requires Paging to be Enabled.' ) . '</td>'
				.									'</tr>'
				.									'<tr>'
				.										'<th width="15%">' . CBTxt::T( 'Page Limit' ) . '</th>'
				.										'<td width="50%">' . $input['category_all_limit'] . '</td>'
				.										'<td>' . CBTxt::T( 'Input default page limit on categories. Page limit determines how many categories are displayed per page.' ) . '</td>'
				.									'</tr>'
				.									'<tr>'
				.										'<th width="15%">' . CBTxt::Th( 'Search' ) . '</th>'
				.										'<td width="40%">' . $input['category_all_search'] . '</td>'
				.										'<td>' . CBTxt::Th( 'Enable or disable usage of searching on categories.' ) . '</td>'
				.									'</tr>'
				.								'</tbody>'
				.							'</table>'
				.						$tabs->endTab()
				.					$tabs->endPane()
				.				$tabs->endTab()
				.				$tabs->startTab( null, htmlspecialchars( CBTxt::T( 'Approval' ) ), 'gjCategoriesApproval' )
				.					$tabs->startPane( 'gjCategoriesApprovalTabs' )
				.						$tabs->startTab( null, htmlspecialchars( CBTxt::T( 'Display' ) ), 'gjCategoriesApprovalDisplay' )
				.							'<table class="adminlist" width="100%" cellspacing="0" cellpadding="4" border="0">'
				.								'<tbody>'
				.									'<tr>'
				.										'<th width="15%">' . CBTxt::Th( 'Description Length' ) . '</th>'
				.										'<td width="40%">' . $input['category_approval_desc_limit'] . '</td>'
				.										'<td>' . CBTxt::Th( 'Optionally input maxiumum description length of categories. Leave blank or 0 for no limit.' ) . '</td>'
				.									'</tr>'
				.									'<tr>'
				.										'<th width="15%">' . CBTxt::Th( 'Order By' ) . '</th>'
				.										'<td width="40%">' . $input['category_approval_orderby'] . '</td>'
				.										'<td>' . CBTxt::Th( 'Select ordering of categories.' ) . '</td>'
				.									'</tr>'
				.								'</tbody>'
				.							'</table>'
				.						$tabs->endTab()
				.						$tabs->startTab( null, htmlspecialchars( CBTxt::T( 'Paging' ) ), 'gjCategoriesApprovalPaging' )
				.							'<table class="adminlist" width="100%" cellspacing="0" cellpadding="4" border="0">'
				.								'<tbody>'
				.									'<tr>'
				.										'<th width="15%">' . CBTxt::Th( 'Paging' ) . '</th>'
				.										'<td width="40%">' . $input['category_approval_paging'] . '</td>'
				.										'<td>' . CBTxt::Th( 'Enable or disable usage of paging on categories.' ) . '</td>'
				.									'</tr>'
				.									'<tr>'
				.										'<th width="15%">' . CBTxt::T( 'Page Limitbox' ) . '</th>'
				.										'<td width="50%">' . $input['category_approval_limitbox'] . '</td>'
				.										'<td>' . CBTxt::T( 'Enable or disable usage of page limit on categories. Requires Paging to be Enabled.' ) . '</td>'
				.									'</tr>'
				.									'<tr>'
				.										'<th width="15%">' . CBTxt::T( 'Page Limit' ) . '</th>'
				.										'<td width="50%">' . $input['category_approval_limit'] . '</td>'
				.										'<td>' . CBTxt::T( 'Input default page limit on categories. Page limit determines how many categories are displayed per page.' ) . '</td>'
				.									'</tr>'
				.									'<tr>'
				.										'<th width="15%">' . CBTxt::Th( 'Search' ) . '</th>'
				.										'<td width="40%">' . $input['category_approval_search'] . '</td>'
				.										'<td>' . CBTxt::Th( 'Enable or disable usage of searching on categories.' ) . '</td>'
				.									'</tr>'
				.								'</tbody>'
				.							'</table>'
				.						$tabs->endTab()
				.					$tabs->endPane()
				.				$tabs->endTab()
				.				$tabs->startTab( null, htmlspecialchars( CBTxt::T( 'Groups' ) ), 'gjCategoriesGroups' )
				.					$tabs->startPane( 'gjCategoriesGroupsTabs' )
				.						$tabs->startTab( null, htmlspecialchars( CBTxt::T( 'Display' ) ), 'gjCategoriesGroupsDisplay' )
				.							'<table class="adminlist" width="100%" cellspacing="0" cellpadding="4" border="0">'
				.								'<tbody>'
				.									'<tr>'
				.										'<th width="15%">' . CBTxt::Th( 'Description Length' ) . '</th>'
				.										'<td width="40%">' . $input['category_groups_desc_limit'] . '</td>'
				.										'<td>' . CBTxt::Th( 'Optionally input maxiumum description length of groups. Leave blank or 0 for no limit.' ) . '</td>'
				.									'</tr>'
				.									'<tr>'
				.										'<th width="15%">' . CBTxt::Th( 'Order By' ) . '</th>'
				.										'<td width="40%">' . $input['category_groups_orderby'] . '</td>'
				.										'<td>' . CBTxt::Th( 'Select ordering of groups.' ) . '</td>'
				.									'</tr>'
				.								'</tbody>'
				.							'</table>'
				.						$tabs->endTab()
				.						$tabs->startTab( null, htmlspecialchars( CBTxt::T( 'Paging' ) ), 'gjCategoriesGroupsPaging' )
				.							'<table class="adminlist" width="100%" cellspacing="0" cellpadding="4" border="0">'
				.								'<tbody>'
				.									'<tr>'
				.										'<th width="15%">' . CBTxt::Th( 'Paging' ) . '</th>'
				.										'<td width="40%">' . $input['category_groups_paging'] . '</td>'
				.										'<td>' . CBTxt::Th( 'Enable or disable usage of paging on groups.' ) . '</td>'
				.									'</tr>'
				.									'<tr>'
				.										'<th width="15%">' . CBTxt::T( 'Page Limitbox' ) . '</th>'
				.										'<td width="50%">' . $input['category_groups_limitbox'] . '</td>'
				.										'<td>' . CBTxt::T( 'Enable or disable usage of page limit on groups. Requires Paging to be Enabled.' ) . '</td>'
				.									'</tr>'
				.									'<tr>'
				.										'<th width="15%">' . CBTxt::T( 'Page Limit' ) . '</th>'
				.										'<td width="50%">' . $input['category_groups_limit'] . '</td>'
				.										'<td>' . CBTxt::T( 'Input default page limit on groups. Page limit determines how many groups are displayed per page.' ) . '</td>'
				.									'</tr>'
				.									'<tr>'
				.										'<th width="15%">' . CBTxt::Th( 'Search' ) . '</th>'
				.										'<td width="40%">' . $input['category_groups_search'] . '</td>'
				.										'<td>' . CBTxt::Th( 'Enable or disable usage of searching on groups.' ) . '</td>'
				.									'</tr>'
				.								'</tbody>'
				.							'</table>'
				.						$tabs->endTab()
				.					$tabs->endPane()
				.				$tabs->endTab()
				.				$tabs->startTab( null, htmlspecialchars( CBTxt::T( 'Defaults' ) ), 'gjCategoriesDefaults' )
				.					'<table class="adminlist" width="100%" cellspacing="0" cellpadding="4" border="0">'
				.						'<tbody>'
				.							'<tr>'
				.								'<th width="15%">' . CBTxt::Th( 'Types' ) . '</th>'
				.								'<td width="40%">' . $input['category_types_default'] . ' ' . $input['category_types_config'] . '</td>'
				.								'<td>' . CBTxt::Th( 'Select default value for category parameter "Types". Additionally select the display of the "Types" category parameter. Moderators are exempt from this configuration.' ) . '</td>'
				.							'</tr>'
				.							'<tr>'
				.								'<th width="15%">' . CBTxt::Th( 'Access' ) . '</th>'
				.								'<td width="40%">' . $input['category_access_default'] . ' ' . $input['category_access_config'] . '</td>'
				.								'<td>' . CBTxt::Th( 'Select default value for category parameter "Access". Additionally select the display of the "Access" category parameter. Moderators are exempt from this configuration.' ) . '</td>'
				.							'</tr>'
				.							'<tr>'
				.								'<th width="15%">' . CBTxt::Th( 'Create' ) . '</th>'
				.								'<td width="40%">' . $input['category_create_default'] . ' ' . $input['category_create_config'] . '</td>'
				.								'<td>' . CBTxt::Th( 'Select default value for category parameter "Create". Additionally select the display of the "Create" category parameter. Moderators are exempt from this configuration.' ) . '</td>'
				.							'</tr>'
				.							'<tr>'
				.								'<th width="15%">' . CBTxt::Th( 'Create Access' ) . '</th>'
				.								'<td width="40%">' . $input['category_createaccess_default'] . ' ' . $input['category_createaccess_config'] . '</td>'
				.								'<td>' . CBTxt::Th( 'Select default value for category parameter "Create Access". Additionally select the display of the "Create Access" category parameter. Moderators are exempt from this configuration.' ) . '</td>'
				.							'</tr>'
				.							'<tr>'
				.								'<th width="15%">' . CBTxt::Th( 'Nested' ) . '</th>'
				.								'<td width="40%">' . $input['category_nested_default'] . ' ' . $input['category_nested_config'] . '</td>'
				.								'<td>' . CBTxt::Th( 'Select default value for category parameter "Nested". Additionally select the display of the "Nested" category parameter. Moderators are exempt from this configuration.' ) . '</td>'
				.							'</tr>'
				.							'<tr>'
				.								'<th width="15%">' . CBTxt::Th( 'Nested Access' ) . '</th>'
				.								'<td width="40%">' . $input['category_nestedaccess_default'] . ' ' . $input['category_nestedaccess_config'] . '</td>'
				.								'<td>' . CBTxt::Th( 'Select default value for category parameter "Nested Access". Additionally select the display of the "Nested Access" category parameter. Moderators are exempt from this configuration.' ) . '</td>'
				.							'</tr>'
				.						'</tbody>'
				.					'</table>'
				.				$tabs->endTab()
				.			$tabs->endPane()
				.		$tabs->endTab()
				.		$tabs->startTab( null, htmlspecialchars( CBTxt::T( 'Groups' ) ), 'gjGroups' )
				.			$tabs->startPane( 'gjGroupsTabs' )
				.				$tabs->startTab( null, htmlspecialchars( CBTxt::T( 'General' ) ), 'gjGroupsGeneral' )
				.					'<table class="adminlist" width="100%" cellspacing="0" cellpadding="4" border="0">'
				.						'<tbody>'
				.							'<tr>'
				.								'<th width="15%">' . CBTxt::Th( 'Logo' ) . '</th>'
				.								'<td width="40%">' . $input['group_logo'] . '</td>'
				.								'<td>' . CBTxt::Ph( 'Select default logo for newly created groups or groups without a logo. Logo images can be located at the following location: [rel_path]/images', array( '[rel_path]' => $plugin->relPath ) ) . '</td>'
				.							'</tr>'
				.							'<tr>'
				.								'<th width="15%">' . CBTxt::Th( 'Create' ) . '</th>'
				.								'<td width="40%">' . $input['group_create'] . '</td>'
				.								'<td>' . CBTxt::Th( 'Enable or disable the creation of groups. Moderators and category owners are exempt from this configuration.' ) . '</td>'
				.							'</tr>'
				.							'<tr>'
				.								'<th width="15%">' . CBTxt::Th( 'Create Access' ) . '</th>'
				.								'<td width="40%">' . $input['group_create_access'] . '</td>'
				.								'<td>' . CBTxt::Th( 'Select group create access. Access determines who can create groups. The group selected as well as those above it will have access (e.g. Registered will also be accessible to Author). Moderators are exempt from this configuration.' ) . '</td>'
				.							'</tr>'
				.							'<tr>'
				.								'<th width="15%">' . CBTxt::Th( 'Nested' ) . '</th>'
				.								'<td width="40%">' . $input['group_nested'] . '</td>'
				.								'<td>' . CBTxt::Th( 'Enable or disable nested groups. Nested groups allow groups within groups. Moderators are exempt from this configuration.' ) . '</td>'
				.							'</tr>'
				.							'<tr>'
				.								'<th width="15%">' . CBTxt::Th( 'Nested Access' ) . '</th>'
				.								'<td width="40%">' . $input['group_nested_access'] . '</td>'
				.								'<td>' . CBTxt::Th( 'Select group nested access. Access determines who can create nested groups. The group selected as well as those above it will have access (e.g. Registered will also be accessible to Author). Moderators are exempt from this configuration.' ) . '</td>'
				.							'</tr>'
				.							'<tr>'
				.								'<th width="15%">' . CBTxt::Th( 'Approve' ) . '</th>'
				.								'<td width="40%">' . $input['group_approve'] . '</td>'
				.								'<td>' . CBTxt::Th( 'Enable or disable approval of newly created groups. Groups will require approval by category owner or a moderator to be published. Moderators and category owners are exempt from this configuration.' ) . '</td>'
				.							'</tr>'
				.							'<tr>'
				.								'<th width="15%">' . CBTxt::Th( 'Leave' ) . '</th>'
				.								'<td width="40%">' . $input['group_leave'] . '</td>'
				.								'<td>' . CBTxt::Th( 'Enable or disable users leaving groups. Moderators and category owners are exempt from this configuration.' ) . '</td>'
				.							'</tr>'
				.							'<tr>'
				.								'<th width="15%">' . CBTxt::Th( 'Limit' ) . '</th>'
				.								'<td width="40%">' . $input['group_limit'] . '</td>'
				.								'<td>' . CBTxt::Th( 'Set the number of groups a user is allowed to create. Total is based off published and unpublished groups. Leave blank or 0 for unlimited. Moderators are exempt from this configuration.' ) . '</td>'
				.							'</tr>'
				.							'<tr>'
				.								'<th width="15%">' . CBTxt::Th( 'Toggle' ) . '</th>'
				.								'<td width="40%">' . $input['group_toggle'] . '</td>'
				.								'<td>' . CBTxt::Th( 'Select if group tabs new content display is toggled or always shown.' ) . '</td>'
				.							'</tr>'
				.							'<tr>'
				.								'<th width="15%">' . CBTxt::Th( 'Captcha' ) . '</th>'
				.								'<td width="40%">' . $input['group_captcha'] . '</td>'
				.								'<td>' . CBTxt::Th( 'Enable or disable usage of captcha on new group creation. Requires latest CB Captcha or integrated captcha to be installed and published. Moderators are exempt from this configuration.' ) . '</td>'
				.							'</tr>'
				.							'<tr>'
				.								'<th width="15%">' . CBTxt::Th( 'New Group' ) . '</th>'
				.								'<td width="40%">' . $input['group_new_group'] . '</td>'
				.								'<td>' . CBTxt::Th( 'Enable or disable display of quick new nested group link on groups.' ) . '</td>'
				.							'</tr>'
				.						'</tbody>'
				.					'</table>'
				.				$tabs->endTab()
				.				$tabs->startTab( null, htmlspecialchars( CBTxt::T( 'Description' ) ), 'gjGroupsDescription' )
				.					'<table class="adminlist" width="100%" cellspacing="0" cellpadding="4" border="0">'
				.						'<tbody>'
				.							'<tr>'
				.								'<th width="15%">' . CBTxt::Th( 'Editor' ) . '</th>'
				.								'<td width="40%">' . $input['group_editor'] . '</td>'
				.								'<td>' . CBTxt::Th( 'Select editor for group description.' ) . '</td>'
				.							'</tr>'
				.							'<tr>'
				.								'<th width="15%">' . CBTxt::Th( 'Input Limit' ) . '</th>'
				.								'<td width="40%">' . $input['group_desc_inputlimit'] . '</td>'
				.								'<td>' . CBTxt::Th( 'Input group description character count limit. If left blank or zero no limit will be applied. Moderators are exempt from this configuration.' ) . '</td>'
				.							'</tr>'
				.							'<tr>'
				.								'<th width="15%">' . CBTxt::Th( 'Content Plugins' ) . '</th>'
				.								'<td width="40%">' . $input['group_desc_content'] . '</td>'
				.								'<td>' . CBTxt::Th( 'Enable or disable processing of content plugins on group description.' ) . '</td>'
				.							'</tr>'
				.						'</tbody>'
				.					'</table>'
				.				$tabs->endTab()
				.				$tabs->startTab( null, htmlspecialchars( CBTxt::T( 'Messages' ) ), 'gjGroupsMessages' )
				.					'<table class="adminlist" width="100%" cellspacing="0" cellpadding="4" border="0">'
				.						'<tbody>'
				.							'<tr>'
				.								'<th width="15%">' . CBTxt::Th( 'Display' ) . '</th>'
				.								'<td width="40%">' . $input['group_message'] . '</td>'
				.								'<td>' . CBTxt::Th( 'Enable or disable messaging of a groups users. Moderators and category owners are exempt from this configuration and can always message.' ) . '</td>'
				.							'</tr>'
				.							'<tr>'
				.								'<th width="15%">' . CBTxt::Th( 'Access' ) . '</th>'
				.								'<td width="40%">' . $input['group_message_perm'] . '</td>'
				.								'<td>' . CBTxt::Th( 'Select group message access. Message access determines what type of users can message group users (e.g. Users signify only those a member of the group can message). The users above the selected will also have permission. Moderators and category owners are exempt from this configuration.' ) . '</td>'
				.							'</tr>'
				.							'<tr>'
				.								'<th width="15%">' . CBTxt::Th( 'Editor' ) . '</th>'
				.								'<td width="40%">' . $input['group_message_editor'] . '</td>'
				.								'<td>' . CBTxt::Th( 'Select editor for Group messaging.' ) . '</td>'
				.							'</tr>'
				.							'<tr>'
				.								'<th width="15%">' . CBTxt::Th( 'Captcha' ) . '</th>'
				.								'<td width="40%">' . $input['group_message_captcha'] . '</td>'
				.								'<td>' . CBTxt::Th( 'Enable or disable usage of Captcha on Group messaging. Requires latest CB Captcha or integrated Captcha to be installed and published. Moderators are exempt from this configuration.' ) . '</td>'
				.							'</tr>'
				.						'</tbody>'
				.					'</table>'
				.				$tabs->endTab()
				.				$tabs->startTab( null, htmlspecialchars( CBTxt::T( 'Nested' ) ), 'gjGroupsNested' )
				.					$tabs->startPane( 'gjGroupsNestedTabs' )
				.						$tabs->startTab( null, htmlspecialchars( CBTxt::T( 'Display' ) ), 'gjGroupsNestedDisplay' )
				.							'<table class="adminlist" width="100%" cellspacing="0" cellpadding="4" border="0">'
				.								'<tbody>'
				.									'<tr>'
				.										'<th width="15%">' . CBTxt::Th( 'Description Length' ) . '</th>'
				.										'<td width="40%">' . $input['group_nested_desc_limit'] . '</td>'
				.										'<td>' . CBTxt::Th( 'Optionally input maxiumum description length of groups. Leave blank or 0 for no limit.' ) . '</td>'
				.									'</tr>'
				.									'<tr>'
				.										'<th width="15%">' . CBTxt::Th( 'Order By' ) . '</th>'
				.										'<td width="40%">' . $input['group_nested_orderby'] . '</td>'
				.										'<td>' . CBTxt::Th( 'Select ordering of groups.' ) . '</td>'
				.									'</tr>'
				.								'</tbody>'
				.							'</table>'
				.						$tabs->endTab()
				.						$tabs->startTab( null, htmlspecialchars( CBTxt::T( 'Paging' ) ), 'gjGroupsNestedPaging' )
				.							'<table class="adminlist" width="100%" cellspacing="0" cellpadding="4" border="0">'
				.								'<tbody>'
				.									'<tr>'
				.										'<th width="15%">' . CBTxt::Th( 'Paging' ) . '</th>'
				.										'<td width="40%">' . $input['group_nested_paging'] . '</td>'
				.										'<td>' . CBTxt::Th( 'Enable or disable usage of paging on groups.' ) . '</td>'
				.									'</tr>'
				.									'<tr>'
				.										'<th width="15%">' . CBTxt::T( 'Page Limitbox' ) . '</th>'
				.										'<td width="50%">' . $input['group_nested_limitbox'] . '</td>'
				.										'<td>' . CBTxt::T( 'Enable or disable usage of page limit on groups. Requires Paging to be Enabled.' ) . '</td>'
				.									'</tr>'
				.									'<tr>'
				.										'<th width="15%">' . CBTxt::T( 'Page Limit' ) . '</th>'
				.										'<td width="50%">' . $input['group_nested_limit'] . '</td>'
				.										'<td>' . CBTxt::T( 'Input default page limit on groups. Page limit determines how many groups are displayed per page.' ) . '</td>'
				.									'</tr>'
				.									'<tr>'
				.										'<th width="15%">' . CBTxt::Th( 'Search' ) . '</th>'
				.										'<td width="40%">' . $input['group_nested_search'] . '</td>'
				.										'<td>' . CBTxt::Th( 'Enable or disable usage of searching on groups.' ) . '</td>'
				.									'</tr>'
				.								'</tbody>'
				.							'</table>'
				.						$tabs->endTab()
				.					$tabs->endPane()
				.				$tabs->endTab()
				.				$tabs->startTab( null, htmlspecialchars( CBTxt::T( 'All' ) ), 'gjGroupsAll' )
				.					$tabs->startPane( 'gjGroupsAllTabs' )
				.						$tabs->startTab( null, htmlspecialchars( CBTxt::T( 'Display' ) ), 'gjGroupsAllDisplay' )
				.							'<table class="adminlist" width="100%" cellspacing="0" cellpadding="4" border="0">'
				.								'<tbody>'
				.									'<tr>'
				.										'<th width="15%">' . CBTxt::Th( 'Description Length' ) . '</th>'
				.										'<td width="40%">' . $input['group_all_desc_limit'] . '</td>'
				.										'<td>' . CBTxt::Th( 'Optionally input maxiumum description length of groups. Leave blank or 0 for no limit.' ) . '</td>'
				.									'</tr>'
				.									'<tr>'
				.										'<th width="15%">' . CBTxt::Th( 'Order By' ) . '</th>'
				.										'<td width="40%">' . $input['group_all_orderby'] . '</td>'
				.										'<td>' . CBTxt::Th( 'Select ordering of groups.' ) . '</td>'
				.									'</tr>'
				.								'</tbody>'
				.							'</table>'
				.						$tabs->endTab()
				.						$tabs->startTab( null, htmlspecialchars( CBTxt::T( 'Paging' ) ), 'gjGroupsAllPaging' )
				.							'<table class="adminlist" width="100%" cellspacing="0" cellpadding="4" border="0">'
				.								'<tbody>'
				.									'<tr>'
				.										'<th width="15%">' . CBTxt::Th( 'Paging' ) . '</th>'
				.										'<td width="40%">' . $input['group_all_paging'] . '</td>'
				.										'<td>' . CBTxt::Th( 'Enable or disable usage of paging on groups.' ) . '</td>'
				.									'</tr>'
				.									'<tr>'
				.										'<th width="15%">' . CBTxt::T( 'Page Limitbox' ) . '</th>'
				.										'<td width="50%">' . $input['group_all_limitbox'] . '</td>'
				.										'<td>' . CBTxt::T( 'Enable or disable usage of page limit on groups. Requires Paging to be Enabled.' ) . '</td>'
				.									'</tr>'
				.									'<tr>'
				.										'<th width="15%">' . CBTxt::T( 'Page Limit' ) . '</th>'
				.										'<td width="50%">' . $input['group_all_limit'] . '</td>'
				.										'<td>' . CBTxt::T( 'Input default page limit on groups. Page limit determines how many groups are displayed per page.' ) . '</td>'
				.									'</tr>'
				.									'<tr>'
				.										'<th width="15%">' . CBTxt::Th( 'Search' ) . '</th>'
				.										'<td width="40%">' . $input['group_all_search'] . '</td>'
				.										'<td>' . CBTxt::Th( 'Enable or disable usage of searching on groups.' ) . '</td>'
				.									'</tr>'
				.								'</tbody>'
				.							'</table>'
				.						$tabs->endTab()
				.					$tabs->endPane()
				.				$tabs->endTab()
				.				$tabs->startTab( null, htmlspecialchars( CBTxt::T( 'Approval' ) ), 'gjGroupsApproval' )
				.					$tabs->startPane( 'gjGroupsApprovalTabs' )
				.						$tabs->startTab( null, htmlspecialchars( CBTxt::T( 'Display' ) ), 'gjGroupsApprovalDisplay' )
				.							'<table class="adminlist" width="100%" cellspacing="0" cellpadding="4" border="0">'
				.								'<tbody>'
				.									'<tr>'
				.										'<th width="15%">' . CBTxt::Th( 'Description Length' ) . '</th>'
				.										'<td width="40%">' . $input['group_approval_desc_limit'] . '</td>'
				.										'<td>' . CBTxt::Th( 'Optionally input maxiumum description length of groups. Leave blank or 0 for no limit.' ) . '</td>'
				.									'</tr>'
				.									'<tr>'
				.										'<th width="15%">' . CBTxt::Th( 'Order By' ) . '</th>'
				.										'<td width="40%">' . $input['group_approval_orderby'] . '</td>'
				.										'<td>' . CBTxt::Th( 'Select ordering of groups.' ) . '</td>'
				.									'</tr>'
				.								'</tbody>'
				.							'</table>'
				.						$tabs->endTab()
				.						$tabs->startTab( null, htmlspecialchars( CBTxt::T( 'Paging' ) ), 'gjGroupsApprovalPaging' )
				.							'<table class="adminlist" width="100%" cellspacing="0" cellpadding="4" border="0">'
				.								'<tbody>'
				.									'<tr>'
				.										'<th width="15%">' . CBTxt::Th( 'Paging' ) . '</th>'
				.										'<td width="40%">' . $input['group_approval_paging'] . '</td>'
				.										'<td>' . CBTxt::Th( 'Enable or disable usage of paging on groups.' ) . '</td>'
				.									'</tr>'
				.									'<tr>'
				.										'<th width="15%">' . CBTxt::T( 'Page Limitbox' ) . '</th>'
				.										'<td width="50%">' . $input['group_approval_limitbox'] . '</td>'
				.										'<td>' . CBTxt::T( 'Enable or disable usage of page limit on groups. Requires Paging to be Enabled.' ) . '</td>'
				.									'</tr>'
				.									'<tr>'
				.										'<th width="15%">' . CBTxt::T( 'Page Limit' ) . '</th>'
				.										'<td width="50%">' . $input['group_approval_limit'] . '</td>'
				.										'<td>' . CBTxt::T( 'Input default page limit on groups. Page limit determines how many groups are displayed per page.' ) . '</td>'
				.									'</tr>'
				.									'<tr>'
				.										'<th width="15%">' . CBTxt::Th( 'Search' ) . '</th>'
				.										'<td width="40%">' . $input['group_approval_search'] . '</td>'
				.										'<td>' . CBTxt::Th( 'Enable or disable usage of searching on groups.' ) . '</td>'
				.									'</tr>'
				.								'</tbody>'
				.							'</table>'
				.						$tabs->endTab()
				.					$tabs->endPane()
				.				$tabs->endTab()
				.				$tabs->startTab( null, htmlspecialchars( CBTxt::T( 'Users' ) ), 'gjGroupsUsers' )
				.					$tabs->startPane( 'gjGroupsUsersTabs' )
				.						$tabs->startTab( null, htmlspecialchars( CBTxt::T( 'Paging' ) ), 'gjGroupsUsersPaging' )
				.							'<table class="adminlist" width="100%" cellspacing="0" cellpadding="4" border="0">'
				.								'<tbody>'
				.									'<tr>'
				.										'<th width="15%">' . CBTxt::Th( 'Paging' ) . '</th>'
				.										'<td width="40%">' . $input['group_users_paging'] . '</td>'
				.										'<td>' . CBTxt::Th( 'Enable or disable usage of paging on users.' ) . '</td>'
				.									'</tr>'
				.									'<tr>'
				.										'<th width="15%">' . CBTxt::T( 'Page Limitbox' ) . '</th>'
				.										'<td width="50%">' . $input['group_users_limitbox'] . '</td>'
				.										'<td>' . CBTxt::T( 'Enable or disable usage of page limit on users. Requires Paging to be Enabled.' ) . '</td>'
				.									'</tr>'
				.									'<tr>'
				.										'<th width="15%">' . CBTxt::T( 'Page Limit' ) . '</th>'
				.										'<td width="50%">' . $input['group_users_limit'] . '</td>'
				.										'<td>' . CBTxt::T( 'Input default page limit on users. Page limit determines how many users are displayed per page.' ) . '</td>'
				.									'</tr>'
				.									'<tr>'
				.										'<th width="15%">' . CBTxt::Th( 'Search' ) . '</th>'
				.										'<td width="40%">' . $input['group_users_search'] . '</td>'
				.										'<td>' . CBTxt::Th( 'Enable or disable usage of searching on users.' ) . '</td>'
				.									'</tr>'
				.								'</tbody>'
				.							'</table>'
				.						$tabs->endTab()
				.						$tabs->startTab( null, htmlspecialchars( CBTxt::T( 'Approval' ) ), 'gjGroupsUsersApproval' )
				.							'<table class="adminlist" width="100%" cellspacing="0" cellpadding="4" border="0">'
				.								'<tbody>'
				.									'<tr>'
				.										'<th width="15%">' . CBTxt::Th( 'Paging' ) . '</th>'
				.										'<td width="40%">' . $input['group_users_approval_paging'] . '</td>'
				.										'<td>' . CBTxt::Th( 'Enable or disable usage of paging on users.' ) . '</td>'
				.									'</tr>'
				.									'<tr>'
				.										'<th width="15%">' . CBTxt::T( 'Page Limitbox' ) . '</th>'
				.										'<td width="50%">' . $input['group_users_approval_limitbox'] . '</td>'
				.										'<td>' . CBTxt::T( 'Enable or disable usage of page limit on users. Requires Paging to be Enabled.' ) . '</td>'
				.									'</tr>'
				.									'<tr>'
				.										'<th width="15%">' . CBTxt::T( 'Page Limit' ) . '</th>'
				.										'<td width="50%">' . $input['group_users_approval_limit'] . '</td>'
				.										'<td>' . CBTxt::T( 'Input default page limit on users. Page limit determines how many users are displayed per page.' ) . '</td>'
				.									'</tr>'
				.									'<tr>'
				.										'<th width="15%">' . CBTxt::Th( 'Search' ) . '</th>'
				.										'<td width="40%">' . $input['group_users_approval_search'] . '</td>'
				.										'<td>' . CBTxt::Th( 'Enable or disable usage of searching on users.' ) . '</td>'
				.									'</tr>'
				.								'</tbody>'
				.							'</table>'
				.						$tabs->endTab()
				.					$tabs->endPane()
				.				$tabs->endTab()
				.				$tabs->startTab( null, htmlspecialchars( CBTxt::T( 'Invites' ) ), 'gjGroupsInvites' )
				.					$tabs->startPane( 'gjGroupsInvitesTabs' )
				.						$tabs->startTab( null, htmlspecialchars( CBTxt::T( 'General' ) ), 'gjGroupsInvitesGeneral' )
				.							'<table class="adminlist" width="100%" cellspacing="0" cellpadding="4" border="0">'
				.								'<tbody>'
				.									'<tr>'
				.										'<th width="15%">' . CBTxt::Th( 'Display' ) . '</th>'
				.										'<td width="40%">' . $input['group_invites_display'] . '</td>'
				.										'<td>' . CBTxt::Th( 'Enable or disable display of invites tab on groups.' ) . '</td>'
				.									'</tr>'
				.									'<tr>'
				.										'<th width="15%">' . CBTxt::Th( 'Invite By' ) . '</th>'
				.										'<td width="40%">' . $input['group_invites_by'] . '</td>'
				.										'<td>' . CBTxt::Th( 'Select how users may invite others to join groups. At least one method is required to be provided.' ) . '</td>'
				.									'</tr>'
				.									'<tr>'
				.										'<th width="15%">' . CBTxt::Th( 'Captcha' ) . '</th>'
				.										'<td width="40%">' . $input['group_invites_captcha'] . '</td>'
				.										'<td>' . CBTxt::Th( 'Enable or disable usage of Captcha on Invites tab. Requires latest CB Captcha or integrated Captcha to be installed and published. Moderators are exempt from this configuration.' ) . '</td>'
				.									'</tr>'
				.									'<tr>'
				.										'<th width="15%">' . CBTxt::Th( 'Connections' ) . '</th>'
				.										'<td width="40%">' . $input['group_invites_list'] . '</td>'
				.										'<td>' . CBTxt::Th( 'Enable or disable usage of connections dropdown list. This will allow quickly selecting a connected user to invite.' ) . '</td>'
				.									'</tr>'
				.									'<tr>'
				.										'<th width="15%">' . CBTxt::Th( 'Auto Accept' ) . '</th>'
				.										'<td width="40%">' . $input['group_invites_accept'] . '</td>'
				.										'<td>' . CBTxt::Th( 'Enabled or disable auto accept of group invites on registration.' ) . '</td>'
				.									'</tr>'
				.								'</tbody>'
				.							'</table>'
				.						$tabs->endTab()
				.						$tabs->startTab( null, htmlspecialchars( CBTxt::T( 'Paging' ) ), 'gjGroupsInvitesPaging' )
				.							'<table class="adminlist" width="100%" cellspacing="0" cellpadding="4" border="0">'
				.								'<tbody>'
				.									'<tr>'
				.										'<th width="15%">' . CBTxt::Th( 'Paging' ) . '</th>'
				.										'<td width="40%">' . $input['group_invites_paging'] . '</td>'
				.										'<td>' . CBTxt::Th( 'Enable or disable usage of paging on invites group tab.' ) . '</td>'
				.									'</tr>'
				.									'<tr>'
				.										'<th width="15%">' . CBTxt::T( 'Page Limitbox' ) . '</th>'
				.										'<td width="50%">' . $input['group_invites_limitbox'] . '</td>'
				.										'<td>' . CBTxt::T( 'Enable or disable usage of page limit on invites group tab. Requires Paging to be Enabled.' ) . '</td>'
				.									'</tr>'
				.									'<tr>'
				.										'<th width="15%">' . CBTxt::T( 'Page Limit' ) . '</th>'
				.										'<td width="50%">' . $input['group_invites_limit'] . '</td>'
				.										'<td>' . CBTxt::T( 'Input default page limit on invites group tab. Page limit determines how many invites are displayed per page.' ) . '</td>'
				.									'</tr>'
				.									'<tr>'
				.										'<th width="15%">' . CBTxt::Th( 'Search' ) . '</th>'
				.										'<td width="40%">' . $input['group_invites_search'] . '</td>'
				.										'<td>' . CBTxt::Th( 'Enable or disable usage of searching on invites group tab.' ) . '</td>'
				.									'</tr>'
				.								'</tbody>'
				.							'</table>'
				.						$tabs->endTab()
				.					$tabs->endPane()
				.				$tabs->endTab()
				.				$tabs->startTab( null, htmlspecialchars( CBTxt::T( 'Defaults' ) ), 'gjGroupsDefaults' )
				.					'<table class="adminlist" width="100%" cellspacing="0" cellpadding="4" border="0">'
				.						'<tbody>'
				.							'<tr>'
				.								'<th width="15%">' . CBTxt::Th( 'Type' ) . '</th>'
				.								'<td width="40%">' . $input['group_type_default'] . ' ' . $input['group_type_config'] . '</td>'
				.								'<td>' . CBTxt::Th( 'Select default value for group parameter "Type". Additionally select the display of the "Type" group parameter. Moderators are exempt from this configuration.' ) . '</td>'
				.							'</tr>'
				.							'<tr>'
				.								'<th width="15%">' . CBTxt::Th( 'Group Access' ) . '</th>'
				.								'<td width="40%">' . $input['group_access_default'] . ' ' . $input['group_access_config'] . '</td>'
				.								'<td>' . CBTxt::Th( 'Select default value for group parameter "Group Access". Additionally select the display of the "Group Access" group parameter. Moderators are exempt from this configuration.' ) . '</td>'
				.							'</tr>'
				.							'<tr>'
				.								'<th width="15%">' . CBTxt::Th( 'Invite Access' ) . '</th>'
				.								'<td width="40%">' . $input['group_invite_default'] . ' ' . $input['group_invite_config'] . '</td>'
				.								'<td>' . CBTxt::Th( 'Select default value for group parameter "Invite Access". Additionally select the display of the "Invite Access" group parameter. Moderators are exempt from this configuration.' ) . '</td>'
				.							'</tr>'
				.							'<tr>'
				.								'<th width="15%">' . CBTxt::Th( 'Users Public' ) . '</th>'
				.								'<td width="40%">' . $input['group_users_default'] . ' ' . $input['group_users_config'] . '</td>'
				.								'<td>' . CBTxt::Th( 'Select default value for group parameter "Users Public". Additionally select the display of the "Users Public" group parameter. Moderators are exempt from this configuration.' ) . '</td>'
				.							'</tr>'
				.							'<tr>'
				.								'<th width="15%">' . CBTxt::Th( 'Nested' ) . '</th>'
				.								'<td width="40%">' . $input['group_nested_default'] . ' ' . $input['group_nested_config'] . '</td>'
				.								'<td>' . CBTxt::Th( 'Select default value for group parameter "Nested". Additionally select the display of the "Nested" group parameter. Moderators are exempt from this configuration.' ) . '</td>'
				.							'</tr>'
				.							'<tr>'
				.								'<th width="15%">' . CBTxt::Th( 'Nested Access' ) . '</th>'
				.								'<td width="40%">' . $input['group_nestedaccess_default'] . ' ' . $input['group_nestedaccess_config'] . '</td>'
				.								'<td>' . CBTxt::Th( 'Select default value for group parameter "Nested Access". Additionally select the display of the "Nested Access" group parameter. Moderators are exempt from this configuration.' ) . '</td>'
				.							'</tr>'
				.						'</tbody>'
				.					'</table>'
				.				$tabs->endTab()
				.			$tabs->endPane()
				.		$tabs->endTab()
				.		$tabs->startTab( null, htmlspecialchars( CBTxt::T( 'Tabs' ) ), 'gjTabs' )
				.			$tabs->startPane( 'gjTabsTabs' )
				.				$tabs->startTab( null, htmlspecialchars( CBTxt::T( 'General' ) ), 'gjTabsGeneral' )
				.					'<table class="adminlist" width="100%" cellspacing="0" cellpadding="4" border="0">'
				.						'<tbody>'
				.							'<tr>'
				.								'<th width="15%">' . CBTxt::Th( 'New Category' ) . '</th>'
				.								'<td width="40%">' . $input['tab_new_category'] . '</td>'
				.								'<td>' . CBTxt::Th( 'Enable or disable display of quick new category link on profile tab.' ) . '</td>'
				.							'</tr>'
				.							'<tr>'
				.								'<th width="15%">' . CBTxt::Th( 'New Group' ) . '</th>'
				.								'<td width="40%">' . $input['tab_new_group'] . '</td>'
				.								'<td>' . CBTxt::Th( 'Enable or disable display of quick new group link on profile tab.' ) . '</td>'
				.							'</tr>'
				.						'</tbody>'
				.					'</table>'
				.				$tabs->endTab()
				.				$tabs->startTab( null, htmlspecialchars( CBTxt::T( 'Categories' ) ), 'gjTabsCategories' )
				.					$tabs->startPane( 'gjTabsCategoriesTabs' )
				.						$tabs->startTab( null, htmlspecialchars( CBTxt::T( 'General' ) ), 'gjTabsCategoriesGeneral' )
				.							'<table class="adminlist" width="100%" cellspacing="0" cellpadding="4" border="0">'
				.								'<tbody>'
				.									'<tr>'
				.										'<th width="15%">' . CBTxt::Th( 'Display' ) . '</th>'
				.										'<td width="40%">' . $input['category_tab_display'] . '</td>'
				.										'<td>' . CBTxt::Th( 'Enable or disable display of categories profile tab.' ) . '</td>'
				.									'</tr>'
				.									'<tr>'
				.										'<th width="15%">' . CBTxt::Th( 'Description Length' ) . '</th>'
				.										'<td width="40%">' . $input['category_tab_desc_limit'] . '</td>'
				.										'<td>' . CBTxt::Th( 'Optionally input maxiumum description length of categories. Leave blank or 0 for no limit.' ) . '</td>'
				.									'</tr>'
				.									'<tr>'
				.										'<th width="15%">' . CBTxt::Th( 'Order By' ) . '</th>'
				.										'<td width="40%">' . $input['category_tab_orderby'] . '</td>'
				.										'<td>' . CBTxt::Th( 'Select ordering of categories.' ) . '</td>'
				.									'</tr>'
				.								'</tbody>'
				.							'</table>'
				.						$tabs->endTab()
				.						$tabs->startTab( null, htmlspecialchars( CBTxt::T( 'Paging' ) ), 'gjTabsCategoriesPaging' )
				.							'<table class="adminlist" width="100%" cellspacing="0" cellpadding="4" border="0">'
				.								'<tbody>'
				.									'<tr>'
				.										'<th width="15%">' . CBTxt::Th( 'Paging' ) . '</th>'
				.										'<td width="40%">' . $input['category_tab_paging'] . '</td>'
				.										'<td>' . CBTxt::Th( 'Enable or disable usage of paging on categories profile tab.' ) . '</td>'
				.									'</tr>'
				.									'<tr>'
				.										'<th width="15%">' . CBTxt::T( 'Page Limitbox' ) . '</th>'
				.										'<td width="50%">' . $input['category_tab_limitbox'] . '</td>'
				.										'<td>' . CBTxt::T( 'Enable or disable usage of page limit on categories profile tab. Requires Paging to be Enabled.' ) . '</td>'
				.									'</tr>'
				.									'<tr>'
				.										'<th width="15%">' . CBTxt::T( 'Page Limit' ) . '</th>'
				.										'<td width="50%">' . $input['category_tab_limit'] . '</td>'
				.										'<td>' . CBTxt::T( 'Input default page limit on categories profile tab. Page limit determines how many categories are displayed per page.' ) . '</td>'
				.									'</tr>'
				.									'<tr>'
				.										'<th width="15%">' . CBTxt::Th( 'Search' ) . '</th>'
				.										'<td width="40%">' . $input['category_tab_search'] . '</td>'
				.										'<td>' . CBTxt::Th( 'Enable or disable usage of searching on categories profile tab.' ) . '</td>'
				.									'</tr>'
				.								'</tbody>'
				.							'</table>'
				.						$tabs->endTab()
				.					$tabs->endPane()
				.				$tabs->endTab()
				.				$tabs->startTab( null, htmlspecialchars( CBTxt::T( 'Groups' ) ), 'gjTabsGroups' )
				.					$tabs->startPane( 'gjTabsGroupsTabs' )
				.						$tabs->startTab( null, htmlspecialchars( CBTxt::T( 'General' ) ), 'gjTabsGroupsGeneral' )
				.							'<table class="adminlist" width="100%" cellspacing="0" cellpadding="4" border="0">'
				.								'<tbody>'
				.									'<tr>'
				.										'<th width="15%">' . CBTxt::Th( 'Display' ) . '</th>'
				.										'<td width="40%">' . $input['group_tab_display'] . '</td>'
				.										'<td>' . CBTxt::Th( 'Enable or disable display of groups profile tab.' ) . '</td>'
				.									'</tr>'
				.									'<tr>'
				.										'<th width="15%">' . CBTxt::Th( 'Joined' ) . '</th>'
				.										'<td width="40%">' . $input['group_tab_joined'] . '</td>'
				.										'<td>' . CBTxt::Th( 'Enable or disable inclusion of Joined groups in addition to Owned groups on groups profile tab.' ) . '</td>'
				.									'</tr>'
				.									'<tr>'
				.										'<th width="15%">' . CBTxt::Th( 'Description Length' ) . '</th>'
				.										'<td width="40%">' . $input['group_tab_desc_limit'] . '</td>'
				.										'<td>' . CBTxt::Th( 'Optionally input maxiumum description length of groups. Leave blank or 0 for no limit.' ) . '</td>'
				.									'</tr>'
				.									'<tr>'
				.										'<th width="15%">' . CBTxt::Th( 'Order By' ) . '</th>'
				.										'<td width="40%">' . $input['group_tab_orderby'] . '</td>'
				.										'<td>' . CBTxt::Th( 'Select ordering of groups.' ) . '</td>'
				.									'</tr>'
				.								'</tbody>'
				.							'</table>'
				.						$tabs->endTab()
				.						$tabs->startTab( null, htmlspecialchars( CBTxt::T( 'Paging' ) ), 'gjTabsGroupsPaging' )
				.							'<table class="adminlist" width="100%" cellspacing="0" cellpadding="4" border="0">'
				.								'<tbody>'
				.									'<tr>'
				.										'<th width="15%">' . CBTxt::Th( 'Paging' ) . '</th>'
				.										'<td width="40%">' . $input['group_tab_paging'] . '</td>'
				.										'<td>' . CBTxt::Th( 'Enable or disable usage of paging on groups profile tab.' ) . '</td>'
				.									'</tr>'
				.									'<tr>'
				.										'<th width="15%">' . CBTxt::T( 'Page Limitbox' ) . '</th>'
				.										'<td width="50%">' . $input['group_tab_limitbox'] . '</td>'
				.										'<td>' . CBTxt::T( 'Enable or disable usage of page limit on groups profile tab. Requires Paging to be Enabled.' ) . '</td>'
				.									'</tr>'
				.									'<tr>'
				.										'<th width="15%">' . CBTxt::T( 'Page Limit' ) . '</th>'
				.										'<td width="50%">' . $input['group_tab_limit'] . '</td>'
				.										'<td>' . CBTxt::T( 'Input default page limit on groups profile tab. Page limit determines how many groups are displayed per page.' ) . '</td>'
				.									'</tr>'
				.									'<tr>'
				.										'<th width="15%">' . CBTxt::Th( 'Search' ) . '</th>'
				.										'<td width="40%">' . $input['group_tab_search'] . '</td>'
				.										'<td>' . CBTxt::Th( 'Enable or disable usage of searching on groups profile tab.' ) . '</td>'
				.									'</tr>'
				.								'</tbody>'
				.							'</table>'
				.						$tabs->endTab()
				.					$tabs->endPane()
				.				$tabs->endTab()
				.				$tabs->startTab( null, htmlspecialchars( CBTxt::T( 'Joined' ) ), 'gjTabsJoined' )
				.					$tabs->startPane( 'gjTabsJoinedTabs' )
				.						$tabs->startTab( null, htmlspecialchars( CBTxt::T( 'General' ) ), 'gjTabsJoinedGeneral' )
				.							'<table class="adminlist" width="100%" cellspacing="0" cellpadding="4" border="0">'
				.								'<tbody>'
				.									'<tr>'
				.										'<th width="15%">' . CBTxt::Th( 'Display' ) . '</th>'
				.										'<td width="40%">' . $input['joined_tab_display'] . '</td>'
				.										'<td>' . CBTxt::Th( 'Enable or disable display of Joined profile tab.' ) . '</td>'
				.									'</tr>'
				.									'<tr>'
				.										'<th width="15%">' . CBTxt::Th( 'Owned' ) . '</th>'
				.										'<td width="40%">' . $input['joined_tab_owned'] . '</td>'
				.										'<td>' . CBTxt::Th( 'Enable or disable inclusion of Owned groups in addition to Joined groups on Joined profile tab.' ) . '</td>'
				.									'</tr>'
				.									'<tr>'
				.										'<th width="15%">' . CBTxt::Th( 'Description Length' ) . '</th>'
				.										'<td width="40%">' . $input['joined_tab_desc_limit'] . '</td>'
				.										'<td>' . CBTxt::Th( 'Optionally input maxiumum description length of groups. Leave blank or 0 for no limit.' ) . '</td>'
				.									'</tr>'
				.									'<tr>'
				.										'<th width="15%">' . CBTxt::Th( 'Order By' ) . '</th>'
				.										'<td width="40%">' . $input['joined_tab_orderby'] . '</td>'
				.										'<td>' . CBTxt::Th( 'Select ordering of groups.' ) . '</td>'
				.									'</tr>'
				.								'</tbody>'
				.							'</table>'
				.						$tabs->endTab()
				.						$tabs->startTab( null, htmlspecialchars( CBTxt::T( 'Paging' ) ), 'gjTabsJoinedPaging' )
				.							'<table class="adminlist" width="100%" cellspacing="0" cellpadding="4" border="0">'
				.								'<tbody>'
				.									'<tr>'
				.										'<th width="15%">' . CBTxt::Th( 'Paging' ) . '</th>'
				.										'<td width="40%">' . $input['joined_tab_paging'] . '</td>'
				.										'<td>' . CBTxt::Th( 'Enable or disable usage of paging on joined profile tab.' ) . '</td>'
				.									'</tr>'
				.									'<tr>'
				.										'<th width="15%">' . CBTxt::T( 'Page Limitbox' ) . '</th>'
				.										'<td width="50%">' . $input['joined_tab_limitbox'] . '</td>'
				.										'<td>' . CBTxt::T( 'Enable or disable usage of page limit on joined profile tab. Requires Paging to be Enabled.' ) . '</td>'
				.									'</tr>'
				.									'<tr>'
				.										'<th width="15%">' . CBTxt::T( 'Page Limit' ) . '</th>'
				.										'<td width="50%">' . $input['joined_tab_limit'] . '</td>'
				.										'<td>' . CBTxt::T( 'Input default page limit on joined profile tab. Page limit determines how many groups are displayed per page.' ) . '</td>'
				.									'</tr>'
				.									'<tr>'
				.										'<th width="15%">' . CBTxt::Th( 'Search' ) . '</th>'
				.										'<td width="40%">' . $input['joined_tab_search'] . '</td>'
				.										'<td>' . CBTxt::Th( 'Enable or disable usage of searching on joined profile tab.' ) . '</td>'
				.									'</tr>'
				.								'</tbody>'
				.							'</table>'
				.						$tabs->endTab()
				.					$tabs->endPane()
				.				$tabs->endTab()
				.				$tabs->startTab( null, htmlspecialchars( CBTxt::T( 'Invites' ) ), 'gjTabsInvites' )
				.					$tabs->startPane( 'gjTabsInvitesTabs' )
				.						$tabs->startTab( null, htmlspecialchars( CBTxt::T( 'General' ) ), 'gjTabsInvitesGeneral' )
				.							'<table class="adminlist" width="100%" cellspacing="0" cellpadding="4" border="0">'
				.								'<tbody>'
				.									'<tr>'
				.										'<th width="15%">' . CBTxt::Th( 'Display' ) . '</th>'
				.										'<td width="40%">' . $input['invites_tab_display'] . '</td>'
				.										'<td>' . CBTxt::Th( 'Enable or disable display of Invites profile tab.' ) . '</td>'
				.									'</tr>'
				.								'</tbody>'
				.							'</table>'
				.						$tabs->endTab()
				.						$tabs->startTab( null, htmlspecialchars( CBTxt::T( 'Paging' ) ), 'gjTabsInvitesPaging' )
				.							'<table class="adminlist" width="100%" cellspacing="0" cellpadding="4" border="0">'
				.								'<tbody>'
				.									'<tr>'
				.										'<th width="15%">' . CBTxt::Th( 'Paging' ) . '</th>'
				.										'<td width="40%">' . $input['invites_tab_paging'] . '</td>'
				.										'<td>' . CBTxt::Th( 'Enable or disable usage of paging on invites profile tab.' ) . '</td>'
				.									'</tr>'
				.									'<tr>'
				.										'<th width="15%">' . CBTxt::T( 'Page Limitbox' ) . '</th>'
				.										'<td width="50%">' . $input['invites_tab_limitbox'] . '</td>'
				.										'<td>' . CBTxt::T( 'Enable or disable usage of page limit on invites profile tab. Requires Paging to be Enabled.' ) . '</td>'
				.									'</tr>'
				.									'<tr>'
				.										'<th width="15%">' . CBTxt::T( 'Page Limit' ) . '</th>'
				.										'<td width="50%">' . $input['invites_tab_limit'] . '</td>'
				.										'<td>' . CBTxt::T( 'Input default page limit on invites profile tab. Page limit determines how many invites are displayed per page.' ) . '</td>'
				.									'</tr>'
				.									'<tr>'
				.										'<th width="15%">' . CBTxt::Th( 'Search' ) . '</th>'
				.										'<td width="40%">' . $input['invites_tab_search'] . '</td>'
				.										'<td>' . CBTxt::Th( 'Enable or disable usage of searching on invites profile tab.' ) . '</td>'
				.									'</tr>'
				.								'</tbody>'
				.							'</table>'
				.						$tabs->endTab()
				.					$tabs->endPane()
				.				$tabs->endTab()
				.				$tabs->startTab( null, htmlspecialchars( CBTxt::T( 'Invited' ) ), 'gjTabsInvited' )
				.					$tabs->startPane( 'gjTabsInvitedTabs' )
				.						$tabs->startTab( null, htmlspecialchars( CBTxt::T( 'General' ) ), 'gjTabsInvitedGeneral' )
				.							'<table class="adminlist" width="100%" cellspacing="0" cellpadding="4" border="0">'
				.								'<tbody>'
				.									'<tr>'
				.										'<th width="15%">' . CBTxt::Th( 'Display' ) . '</th>'
				.										'<td width="40%">' . $input['invited_tab_display'] . '</td>'
				.										'<td>' . CBTxt::Th( 'Enable or disable display of Invited To profile tab.' ) . '</td>'
				.									'</tr>'
				.									'<tr>'
				.										'<th width="15%">' . CBTxt::Th( 'Description Length' ) . '</th>'
				.										'<td width="40%">' . $input['invited_tab_desc_limit'] . '</td>'
				.										'<td>' . CBTxt::Th( 'Optionally input maxiumum description length of groups. Leave blank or 0 for no limit.' ) . '</td>'
				.									'</tr>'
				.									'<tr>'
				.										'<th width="15%">' . CBTxt::Th( 'Order By' ) . '</th>'
				.										'<td width="40%">' . $input['invited_tab_orderby'] . '</td>'
				.										'<td>' . CBTxt::Th( 'Select ordering of groups.' ) . '</td>'
				.									'</tr>'
				.								'</tbody>'
				.							'</table>'
				.						$tabs->endTab()
				.						$tabs->startTab( null, htmlspecialchars( CBTxt::T( 'Paging' ) ), 'gjTabsInvitedPaging' )
				.							'<table class="adminlist" width="100%" cellspacing="0" cellpadding="4" border="0">'
				.								'<tbody>'
				.									'<tr>'
				.										'<th width="15%">' . CBTxt::Th( 'Paging' ) . '</th>'
				.										'<td width="40%">' . $input['invited_tab_paging'] . '</td>'
				.										'<td>' . CBTxt::Th( 'Enable or disable usage of paging on invited to profile tab.' ) . '</td>'
				.									'</tr>'
				.									'<tr>'
				.										'<th width="15%">' . CBTxt::T( 'Page Limitbox' ) . '</th>'
				.										'<td width="50%">' . $input['invited_tab_limitbox'] . '</td>'
				.										'<td>' . CBTxt::T( 'Enable or disable usage of page limit on invited to profile tab. Requires Paging to be Enabled.' ) . '</td>'
				.									'</tr>'
				.									'<tr>'
				.										'<th width="15%">' . CBTxt::T( 'Page Limit' ) . '</th>'
				.										'<td width="50%">' . $input['invited_tab_limit'] . '</td>'
				.										'<td>' . CBTxt::T( 'Input default page limit on invited to profile tab. Page limit determines how many invites are displayed per page.' ) . '</td>'
				.									'</tr>'
				.									'<tr>'
				.										'<th width="15%">' . CBTxt::Th( 'Search' ) . '</th>'
				.										'<td width="40%">' . $input['invited_tab_search'] . '</td>'
				.										'<td>' . CBTxt::Th( 'Enable or disable usage of searching on invited to profile tab.' ) . '</td>'
				.									'</tr>'
				.								'</tbody>'
				.							'</table>'
				.						$tabs->endTab()
				.					$tabs->endPane()
				.				$tabs->endTab()
				.			$tabs->endPane()
				.		$tabs->endTab()
				.		$tabs->startTab( null, htmlspecialchars( CBTxt::T( 'Integrations' ) ), 'gjIntegrations' )
				.			$tabs->startPane( 'gjIntegrationsTabs' )
				.				cbgjClass::getIntegrations( 'gj_onConfigIntegrations', array( $tabs, $user, $plugin ), CBTxt::Th( 'There are no integrations available.' ), null )
				.			$tabs->endPane()
				.		$tabs->endTab()
				.	$tabs->endPane()
				.	'<input type="hidden" id="option" name="option" value="' . htmlspecialchars( $plugin->option ) . '" />'
				.	'<input type="hidden" id="task" name="task" value="editPlugin" />'
				.	'<input type="hidden" id="cid" name="cid" value="' . (int) $plugin->id . '" />'
				.	'<input type="hidden" id="action" name="action" value="config.save" />'
				.	cbGetSpoofInputTag( 'plugin' )
				.	'</form>';

		echo $return;
	}

	/**
	 * render backend tools
	 *
	 * @param object $msgs
	 * @param moscomprofilerUser  $user
	 * @param object $plugin
	 */
	static public function showTools( $msgs, $user, $plugin ) {
		global $_CB_framework;

		HTML_cbgjAdmin::setTitle( CBTxt::T( 'Tools' ), 'cbicon-48-gjtools' );

		$return	=	'<form action="' . $_CB_framework->backendUrl( 'index.php' ) . '" method="post" name="adminForm" enctype="multipart/form-data" id="adminForm" class="adminform">'
				.	'<table class="adminlist" width="100%" cellspacing="0" cellpadding="4" border="0">'
				.		'<thead>'
				.			'<tr>'
				.				'<th colspan="3">' . CBTxt::Th( 'Errors' ) . '</th>'
				.			'</tr>'
				.		'</thead>'
				.		'<tbody>'
				.			'<tr>'
				.				'<td style="color:red;"><div>' . implode( '<div></div>', $msgs->errors ) . '</div></td>'
				.			'</tr>'
				.		'</tbody>'
				.		'<thead>'
				.			'<tr>'
				.				'<th colspan="3">' . CBTxt::Th( 'Warnings' ) . '</th>'
				.			'</tr>'
				.		'</thead>'
				.		'<tbody>'
				.			'<tr>'
				.				'<td style="color:orange;"><div>' . implode( '<div></div>', $msgs->warnings ) . '</div></td>'
				.			'</tr>'
				.		'</tbody>'
				.		'<thead>'
				.			'<tr>'
				.				'<th colspan="3">' . CBTxt::Th( 'Info' ) . '</th>'
				.			'</tr>'
				.		'</thead>'
				.		'<tbody>'
				.			'<tr>'
				.				'<td style="color:green;"><div>' . implode( '<div></div>', $msgs->info ) . '</div></td>'
				.			'</tr>'
				.		'</tbody>'
				.	'</table>'
				.	'<input type="hidden" id="option" name="option" value="' . htmlspecialchars( $plugin->option ) . '" />'
				.	'<input type="hidden" id="task" name="task" value="editPlugin" />'
				.	'<input type="hidden" id="cid" name="cid" value="' . (int) $plugin->id . '" />'
				.	'<input type="hidden" id="action" name="action" value="tools" />'
				.	cbGetSpoofInputTag( 'plugin' )
				.	'</form>';

		echo $return;
	}

	/**
	 * render backend integrations
	 *
	 * @param object $rows
	 * @param object $pageNav
	 * @param array  $input
	 * @param array $access_levels
	 * @param moscomprofilerUser  $user
	 * @param object $plugin
	 */
	static public function showIntegrations( $rows, $pageNav, $input, $access_levels, $user, $plugin ) {
		global $_CB_framework;

		HTML_cbgjAdmin::setTitle( CBTxt::T( 'Integrations' ), 'cbicon-48-gjintegrations' );

		$oneOrTwo			=	0;

		$return				=	'<form action="' . $_CB_framework->backendUrl( 'index.php' ) . '" method="post" name="adminForm" enctype="multipart/form-data" id="adminForm" class="adminform">'
							.		'<table class="adminlist" width="100%" cellspacing="0" cellpadding="4" border="0">'
							.			'<thead>'
							.				'<tr>'
							.					'<th>&nbsp;</th>'
							.					'<th style="text-align:left;">' . $input['search'] . '</th>'
							.					'<th>' . $input['access'] . '</th>'
							.					'<th>' . $input['state'] . '</th>'
							.					'<th colspan="2">&nbsp;</th>'
							.					'<th>' . $input['id'] . '</th>'
							.				'</tr>'
							.				'<tr>'
							.					'<th class="title" width="5%">#</th>'
							.					'<th class="title" width="55%">' . CBTxt::Th( 'Integration' ) . '</th>'
							.					'<th class="title" width="10%">' . CBTxt::Th( 'Access' ) . '</th>'
							.					'<th class="title" width="10%">' . CBTxt::Th( 'State' ) . '</th>'
							.					'<th class="title" width="10%">' . CBTxt::Th( 'Created' ) . '</th>'
							.					'<th class="title" width="5%">' . CBTxt::Th( 'Version' ) . '</th>'
							.					'<th class="title" width="5%">' . CBTxt::Th( 'ID' ) . '</th>'
							.				'</tr>'
							.			'</thead>'
							.			'<tbody>';

		if ( $rows ) for ( $i = 0, $n = count( $rows ); $i < $n; $i++ ) {
			$row			=	$rows[$i];
			$publishImg		=	$plugin->livePath . '/images/' . ( $row->published ?  'publish.png' : 'unpublish.png' );

			$xml			=	new CBSimpleXMLElement( trim( file_get_contents( $plugin->absPath . '/plugins/' . $row->folder . '/' . $row->element . '.xml' ) ) );

			$access			=	CBTxt::T( 'Unknown' );

			if ( $access_levels ) foreach( $access_levels as $access_level ) {
				if ( $access_level->value == $row->access ) {
					$access	=	$access_level->text;
				}
			}

			$return			.=				'<tr class="row' . $oneOrTwo . '">'
							.					'<td style="text-align:center;">' . ( $i + $pageNav->limitstart + 1 ) . '</td>'
							.					'<td>'
							.						'<div><strong>' . CBTxt::Th( 'Name' ) . '</strong>: ' . htmlspecialchars( $row->name ) . '</div>'
							.						'<div><strong>' . CBTxt::Th( 'Description' ) . '</strong>: ' . htmlspecialchars( $xml->description ) . '</div>'
							.					 '</td>'
							.					'<td style="text-align:center;">' . $access . '</td>'
							.					'<td style="text-align:center;"><img src="' . $publishImg . '" width="16" height="16" border="0" /></td>'
							.					'<td style="text-align:center;">' . cbFormatDate( $xml->creationDate ) . '</td>'
							.					'<td style="text-align:center;">' . htmlspecialchars( $xml->release ) . '</td>'
							.					'<td style="text-align:center;">' . (int) $row->id . '</td>'
							.				'</tr>';

			$oneOrTwo		=	( $oneOrTwo == 1 ? 2 : 1 );
		} else {
			$return			.=				'<tr>'
							.					'<td colspan="10">';

			if ( $pageNav->searching ) {
				$return		.=						CBTxt::Th( 'No integration search results found.' );
			} else {
				$return		.=						CBTxt::Th( 'There currently are no integrations installed.' );
			}

			$return			.=					'</td>'
							.				'</tr>';
		}

		$return				.=			'</tbody>'
							.			'<tfoot>'
							.				'<tr>'
							.					'<th colspan="10">' . $pageNav->getListFooter() . '</th>'
							.				'</tr>'
							.			'</tfoot>'
							.		'</table>'
							.		'<input type="hidden" id="option" name="option" value="' . htmlspecialchars( $plugin->option ) . '" />'
							.		'<input type="hidden" id="task" name="task" value="editPlugin" />'
							.		'<input type="hidden" id="cid" name="cid" value="' . (int) $plugin->id . '" />'
							.		'<input type="hidden" id="action" name="action" value="integrations" />'
							.		cbGetSpoofInputTag( 'plugin' )
							.	'</form>';

		echo $return;
	}

	/**
	 * render backend menus
	 *
	 * @param array $input
	 * @param moscomprofilerUser  $user
	 * @param object $plugin
	 */
	static public function showMenus( $input, $user, $plugin ) {
		global $_CB_framework;

		HTML_cbgjAdmin::setTitle( CBTxt::T( 'Menus' ), 'cbicon-48-gjmenus' );

		$return	=	'<form action="' . $_CB_framework->backendUrl( 'index.php' ) . '" method="post" name="adminForm" enctype="multipart/form-data" id="adminForm" class="adminform">'
				.		'<table class="adminlist" width="100%" cellspacing="0" cellpadding="4" border="0">'
				.			'<thead>'
				.				'<tr>'
				.					'<th colspan="3">' . CBTxt::Th( 'General' ) . '</th>'
				.				'</tr>'
				.			'</thead>'
				.			'<tbody>'
				.				'<tr>'
				.					'<td width="10%" style="text-align:center;">' . $input['plugin'] . '</td>'
				.					'<th width="20%">' . CBTxt::Th( 'Plugin' ) . '</td>'
				.					'<td>' . CBTxt::Th( 'Create menu link to CB GroupJive component plugin page.' ) . '</td>'
				.				'</tr>'
				.				'<tr>'
				.					'<td width="10%" style="text-align:center;">' . $input['overview'] . '</td>'
				.					'<th width="20%">' . CBTxt::Th( 'Category Overview' ) . '</td>'
				.					'<td>' . CBTxt::Th( 'Create menu link to category overview page.' ) . '</td>'
				.				'</tr>'
				.				'<tr>'
				.					'<td width="10%" style="text-align:center;">' . $input['panel'] . '</td>'
				.					'<th width="20%">' . CBTxt::Th( 'My Panel' ) . '</td>'
				.					'<td>' . CBTxt::Th( 'Create menu link to user panel page.' ) . '</td>'
				.				'</tr>'
				.				'<tr>'
				.					'<td width="10%" style="text-align:center;">' . $input['notifications'] . '</td>'
				.					'<th width="20%">' . CBTxt::Th( 'General Notifications' ) . '</td>'
				.					'<td>' . CBTxt::Th( 'Create menu link to general notifications page.' ) . '</td>'
				.				'</tr>'
				.				'<tr>'
				.					'<td width="10%" style="text-align:center;">' . $input['message'] . '</td>'
				.					'<th width="20%">' . CBTxt::Th( 'Message Categories' ) . '</td>'
				.					'<td>' . CBTxt::Th( 'Create menu link to general message page.' ) . '</td>'
				.				'</tr>'
				.				'<tr>'
				.					'<td width="10%" style="text-align:center;">' . $input['all_categories'] . '</td>'
				.					'<th width="20%">' . CBTxt::Th( 'All Categories' ) . '</td>'
				.					'<td>' . CBTxt::Th( 'Create menu link to all categories page.' ) . '</td>'
				.				'</tr>'
				.				'<tr>'
				.					'<td width="10%" style="text-align:center;">' . $input['all_groups'] . '</td>'
				.					'<th width="20%">' . CBTxt::Th( 'All Groups' ) . '</td>'
				.					'<td>' . CBTxt::Th( 'Create menu link to all groups page.' ) . '</td>'
				.				'</tr>'
				.				'<tr>'
				.					'<td width="10%" style="text-align:center;">' . $input['all_my_categories'] . '</td>'
				.					'<th width="20%">' . CBTxt::Th( 'All My Categories' ) . '</td>'
				.					'<td>' . CBTxt::Th( 'Create menu link to users all categories page.' ) . '</td>'
				.				'</tr>'
				.				'<tr>'
				.					'<td width="10%" style="text-align:center;">' . $input['all_my_groups'] . '</td>'
				.					'<th width="20%">' . CBTxt::Th( 'All My Groups' ) . '</td>'
				.					'<td>' . CBTxt::Th( 'Create menu link to users all groups page.' ) . '</td>'
				.				'</tr>'
				.				'<tr>'
				.					'<td width="10%" style="text-align:center;">' . $input['new_category'] . '</td>'
				.					'<th width="20%">' . CBTxt::Th( 'New Category' ) . '</td>'
				.					'<td>' . CBTxt::Th( 'Create menu link to generic new category page.' ) . '</td>'
				.				'</tr>'
				.				'<tr>'
				.					'<td width="10%" style="text-align:center;">' . $input['new_group'] . '</td>'
				.					'<th width="20%">' . CBTxt::Th( 'New Group' ) . '</td>'
				.					'<td>' . CBTxt::Th( 'Create menu link to genric new group page.' ) . '</td>'
				.				'</tr>'
				.				'<tr>'
				.					'<td width="10%" style="text-align:center;">' . $input['approve_category'] . '</td>'
				.					'<th width="20%">' . CBTxt::Th( 'Category Approval' ) . '</td>'
				.					'<td>' . CBTxt::Th( 'Create menu link to category approval page.' ) . '</td>'
				.				'</tr>'
				.				'<tr>'
				.					'<td width="10%" style="text-align:center;">' . $input['approve_group'] . '</td>'
				.					'<th width="20%">' . CBTxt::Th( 'Group Approval' ) . '</td>'
				.					'<td>' . CBTxt::Th( 'Create menu link to group approval page.' ) . '</td>'
				.				'</tr>'
				.				cbgjClass::getIntegrations( 'gj_onMenusIntegrationsGeneral', array( $user, $plugin ), null, null )
				.			'</tbody>'
				.			'<thead>'
				.				'<tr>'
				.					'<th colspan="3">' . CBTxt::Th( 'Categories' ) . '</th>'
				.				'</tr>'
				.			'</thead>'
				.			'<tbody>'
				.				'<tr>'
				.					'<th width="10%" style="text-align:center;">' . CBTxt::Th( 'Categories' ) . '</td>'
				.					'<td width="20%">' . $input['cats'] . '</td>'
				.					'<td>' . CBTxt::Th( 'Select categories to create the following menu links for.' ) . '</td>'
				.				'</tr>'
				.				'<tr>'
				.					'<td width="10%" style="text-align:center;">' . $input['categories'] . '</td>'
				.					'<th width="20%">' . CBTxt::Th( 'Category' ) . '</td>'
				.					'<td>' . CBTxt::Th( 'Create menu link to a categories page.' ) . '</td>'
				.				'</tr>'
				.				'<tr>'
				.					'<td width="10%" style="text-align:center;">' . $input['new_category_nested'] . '</td>'
				.					'<th width="20%">' . CBTxt::Th( 'New Nested Category' ) . '</td>'
				.					'<td>' . CBTxt::Th( 'Create menu link to a categories new nested category page.' ) . '</td>'
				.				'</tr>'
				.				'<tr>'
				.					'<td width="10%" style="text-align:center;">' . $input['new_category_group'] . '</td>'
				.					'<th width="20%">' . CBTxt::Th( 'New Category Group' ) . '</td>'
				.					'<td>' . CBTxt::Th( 'Create menu link to a categories new group page.' ) . '</td>'
				.				'</tr>'
				.				'<tr>'
				.					'<td width="10%" style="text-align:center;">' . $input['notifications_category'] . '</td>'
				.					'<th width="20%">' . CBTxt::Th( 'Category Notifications' ) . '</td>'
				.					'<td>' . CBTxt::Th( 'Create menu link to a categories notifications page.' ) . '</td>'
				.				'</tr>'
				.				'<tr>'
				.					'<td width="10%" style="text-align:center;">' . $input['message_groups'] . '</td>'
				.					'<th width="20%">' . CBTxt::Th( 'Message Groups' ) . '</td>'
				.					'<td>' . CBTxt::Th( 'Create menu link to a categories message page.' ) . '</td>'
				.				'</tr>'
				.				'<tr>'
				.					'<td width="10%" style="text-align:center;">' . $input['edit_category'] . '</td>'
				.					'<th width="20%">' . CBTxt::Th( 'Edit Category' ) . '</td>'
				.					'<td>' . CBTxt::Th( 'Create menu link to edit a category.' ) . '</td>'
				.				'</tr>'
				.				cbgjClass::getIntegrations( 'gj_onMenusIntegrationsCategories', array( $user, $plugin ), null, null )
				.			'</tbody>'
				.			'<thead>'
				.				'<tr>'
				.					'<th colspan="3">' . CBTxt::Th( 'Groups' ) . '</th>'
				.				'</tr>'
				.			'</thead>'
				.			'<tbody>'
				.				'<tr>'
				.					'<th width="10%" style="text-align:center;">' . CBTxt::Th( 'Groups' ) . '</td>'
				.					'<td width="20%">' . $input['grps'] . '</td>'
				.					'<td>' . CBTxt::Th( 'Select groups to create the following menu links for.' ) . '</td>'
				.				'</tr>'
				.				'<tr>'
				.					'<td width="10%" style="text-align:center;">' . $input['groups'] . '</td>'
				.					'<th width="20%">' . CBTxt::Th( 'Group' ) . '</td>'
				.					'<td>' . CBTxt::Th( 'Create menu link to a groups page.' ) . '</td>'
				.				'</tr>'
				.				'<tr>'
				.					'<td width="10%" style="text-align:center;">' . $input['new_group_nested'] . '</td>'
				.					'<th width="20%">' . CBTxt::Th( 'New Nested Group' ) . '</td>'
				.					'<td>' . CBTxt::Th( 'Create menu link to a groups new nested group page.' ) . '</td>'
				.				'</tr>'
				.				'<tr>'
				.					'<td width="10%" style="text-align:center;">' . $input['notifications_group'] . '</td>'
				.					'<th width="20%">' . CBTxt::Th( 'Group Notifications' ) . '</td>'
				.					'<td>' . CBTxt::Th( 'Create menu link to a groups notifications page.' ) . '</td>'
				.				'</tr>'
				.				'<tr>'
				.					'<td width="10%" style="text-align:center;">' . $input['message_users'] . '</td>'
				.					'<th width="20%">' . CBTxt::Th( 'Message Users' ) . '</td>'
				.					'<td>' . CBTxt::Th( 'Create menu link to a groups message page.' ) . '</td>'
				.				'</tr>'
				.				'<tr>'
				.					'<td width="10%" style="text-align:center;">' . $input['edit_group'] . '</td>'
				.					'<th width="20%">' . CBTxt::Th( 'Edit Group' ) . '</td>'
				.					'<td>' . CBTxt::Th( 'Create menu link to edit a group.' ) . '</td>'
				.				'</tr>'
				.				'<tr>'
				.					'<td width="10%" style="text-align:center;">' . $input['join'] . '</td>'
				.					'<th width="20%">' . CBTxt::Th( 'Join Group' ) . '</td>'
				.					'<td>' . CBTxt::Th( 'Create menu link to join a group.' ) . '</td>'
				.				'</tr>'
				.				'<tr>'
				.					'<td width="10%" style="text-align:center;">' . $input['leave'] . '</td>'
				.					'<th width="20%">' . CBTxt::Th( 'Leave Group' ) . '</td>'
				.					'<td>' . CBTxt::Th( 'Create menu link to leave a group.' ) . '</td>'
				.				'</tr>'
				.				cbgjClass::getIntegrations( 'gj_onMenusIntegrationsGroups', array( $user, $plugin ), null, null )
				.			'</tbody>'
				.		'</table>'
				.		'<input type="hidden" id="option" name="option" value="' . htmlspecialchars( $plugin->option ) . '" />'
				.		'<input type="hidden" id="task" name="task" value="editPlugin" />'
				.		'<input type="hidden" id="cid" name="cid" value="' . (int) $plugin->id . '" />'
				.		'<input type="hidden" id="action" name="action" value="menus.save" />'
				.		cbGetSpoofInputTag( 'plugin' )
				.	'</form>';

		echo $return;
	}

	/**
	 * render backend titles
	 *
	 * @param mixed $title
	 * @param string $class
	 */
	static public function setTitle( $title, $class ) {
		global $_GJ_Backend_Title;

		$_GJ_Backend_Title	=	array( $title, $class );
	}
}
?>