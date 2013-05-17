<?php
if ( ! ( defined( '_VALID_CB' ) || defined( '_JEXEC' ) || defined( '_VALID_MOS' ) ) ) { die( 'Direct Access to this location is not allowed.' ); }

class HTML_cbactivityAdmin {

	static public function showPlugin( $menu, $xml, $user, $plugin ) {
		global $_CB_framework;

		HTML_cbactivityAdmin::setTitle( $plugin->name, 'cbicon-48-cbactivity-plugin' );

		$access_levels		=	$_CB_framework->acl->get_access_children_tree( false );
		$access				=	CBTxt::T( 'Unknown' );

		if ( $access_levels ) foreach( $access_levels as $access_level ) {
			if ( $access_level['value'] == $plugin->access ) {
				$access		=	CBTxt::T( $access_level['text'] );
			}
		}

		$frontend_url		=	'index.php?option=' . $plugin->option . '&task=pluginclass&plugin=' . $plugin->element;
		$frontend_link		=	'<a href="' . cbSef( $frontend_url ) . '" target="_blank">' . $frontend_url . '</a> - <small>(<a href="' . cbactivityClass::getPluginURL( array( 'menu' ) ) . '">' . CBTxt::Th( 'Create Menu' ) . '</a>)</small>';

		$return				=	'<form action="' . $_CB_framework->backendUrl( 'index.php' ) . '" method="post" name="adminForm" enctype="multipart/form-data" id="adminForm" class="adminform">'
							.		'<table class="adminlist" width="100%" cellspacing="0" cellpadding="4" border="0">'
							.			'<tbody>'
							.				'<tr class="row">'
							.					'<td colspan="3" style="text-align:center;">' . CBTxt::P( 'Frontend URL: [front_url]', array( '[front_url]' => $frontend_link ) ) . '</td>'
							.				'</tr>'
							.				'<tr class="row">'
							.					'<td width="37.5%" style="text-align:center;">' . $menu->activity . '</td>'
							.					'<td width="37.5%" style="text-align:center;">' . $menu->config . '</td>'
							.					'<td width="25%">'
							.						'<table class="adminlist" width="100%" cellspacing="0" cellpadding="4" border="0">'
							.							'<thead>'
							.								'<tr>'
							.									'<th colspan="2">' . CBTxt::T( 'Plugin' ) . '</th>'
							.								'</tr>'
							.							'</thead>'
							.							'<tbody>'
							.								'<tr>'
							.									'<th width="90px">' . CBTxt::T( 'Project' ) . '</td>'
							.									'<td><a href="' . htmlspecialchars( $xml->project ) . '" target="_blank">' . htmlspecialchars( $xml->name ) . '</a></td>'
							.								'</tr>'
							.								'<tr>'
							.									'<th width="90px">' . CBTxt::T( 'Access' ) . '</td>'
							.									'<td>' . $access . '</td>'
							.								'</tr>'
							.								'<tr>'
							.									'<th width="90px">' . CBTxt::T( 'Published' ) . '</td>'
							.									'<td>' . ( $plugin->published ? CBTxt::T( 'Yes' ) : CBTxt::T( 'No' ) ) . '</td>'
							.								'</tr>'
							.								'<tr>'
							.									'<th width="90px">' . CBTxt::T( 'Version' ) . '</td>'
							.									'<td>' . htmlspecialchars( $xml->release ) . '</td>'
							.								'</tr>'
							.								'<tr>'
							.									'<th width="90px">' . CBTxt::T( 'Created' ) . '</td>'
							.									'<td>' . cbFormatDate( $xml->creationDate ) . '</td>'
							.								'</tr>'
							.								'<tr>'
							.									'<th width="90px">' . CBTxt::T( 'Description' ) . '</td>'
							.									'<td>' . htmlspecialchars( $xml->description ) . '</td>'
							.								'</tr>'
							.								'<tr>'
							.									'<th width="90px">' . CBTxt::T( 'Author' ) . '</td>'
							.									'<td><a href="' . htmlspecialchars( $xml->authorUrl ) . '" target="_blank">' . htmlspecialchars( $xml->author ) . '</a></td>'
							.								'</tr>'
							.								'<tr>'
							.									'<th width="90px">' . CBTxt::T( 'Copyright' ) . '</td>'
							.									'<td><a href="' . htmlspecialchars( $xml->copyrightUrl ) . '" target="_blank">' . htmlspecialchars( $xml->copyright ) . '</a></td>'
							.								'</tr>'
							.								'<tr>'
							.									'<th width="90px">' . CBTxt::T( 'License' ) . '</td>'
							.									'<td><a href="' . htmlspecialchars( $xml->licenseUrl ) . '" target="_blank">' . htmlspecialchars( $xml->license ) . '</a></td>'
							.								'</tr>'
							.							'</tbody>'
							.						'</table>'
							.					'</td>'
							.				'</tr>'
							.			'</tbody>'
							.		'</table>'
							.		'<input type="hidden" id="option" name="option" value="' . htmlspecialchars( $plugin->option ) . '" />'
							.		'<input type="hidden" id="task" name="task" value="editPlugin" />'
							.		'<input type="hidden" id="cid" name="cid" value="' . (int) $plugin->id . '" />'
							.		cbGetSpoofInputTag( 'plugin' )
							.	'</form>';

		echo $return;
	}

	static public function showActivity( $rows, $pageNav, $input, $user, $plugin ) {
		global $_CB_framework;

		HTML_cbactivityAdmin::setTitle( CBTxt::T( 'Activity' ), 'cbicon-48-cbactivity-activity' );

		$toggleJs		=	"cbToggleAll( this, " . count( $rows ) . ", 'id' );";
		$oneOrTwo		=	0;

		$return			=	'<form action="' . $_CB_framework->backendUrl( 'index.php' ) . '" method="post" name="adminForm" enctype="multipart/form-data" id="adminForm" class="adminform">'
						.		'<table class="adminlist" width="100%" cellspacing="0" cellpadding="4" border="0">'
						.			'<thead>'
						.				'<tr>'
						.					'<th colspan="2">&nbsp;</th>'
						.					'<th style="text-align:left;">' . $input['owner'] . '</th>'
						.					'<th style="text-align:left;">' . $input['user'] . '</th>'
						.					'<th style="text-align:center;">' . $input['type'] . '</th>'
						.					'<th style="text-align:center;">' . $input['subtype'] . '</th>'
						.					'<th style="text-align:center;">' . $input['item'] . '</th>'
						.					'<th>&nbsp;</th>'
						.					'<th style="text-align:center;">' . $input['id'] . '</th>'
						.				'</tr>'
						.				'<tr>'
						.					'<th class="title" width="5%" style="text-align:center;">#</th>'
						.					'<th class="title" width="5%" style="text-align:center;"><input type="checkbox" name="toggle" value="" onclick="' . $toggleJs . '" /></th>'
						.					'<th class="title" width="15%">' . CBTxt::T( 'Owner' ) . '</th>'
						.					'<th class="title" width="15%">' . CBTxt::T( 'User' ) . '</th>'
						.					'<th class="title" width="15%" style="text-align:center;">' . CBTxt::T( 'Type' ) . '</th>'
						.					'<th class="title" width="15%" style="text-align:center;">' . CBTxt::T( 'Sub-Type' ) . '</th>'
						.					'<th class="title" width="15%" style="text-align:center;">' . CBTxt::T( 'Item' ) . '</th>'
						.					'<th class="title" width="10%" style="text-align:center;">' . CBTxt::T( 'Date' ) . '</th>'
						.					'<th class="title" width="5%" style="text-align:center;">' . CBTxt::T( 'ID' ) . '</th>'
						.				'</tr>'
						.			'</thead>'
						.			'<tbody>';

		if ( $rows ) for ( $i = 0, $n = count( $rows ); $i < $n; $i++ ) {
			$row		=	$rows[$i];
			$editJs		=	"cbListItemTask( this, 'editPlugin', 'action', 'activity.edit', 'id', $i );";

			$return		.=				'<tr class="row' . $oneOrTwo . '">'
						.					'<td style="text-align:center;">' . ( $i + $pageNav->limitstart + 1 ) . '</td>'
						.					'<td style="text-align:center;"><input type="checkbox" id="id' . $i . '" name="id[]" value="' . (int) $row->get( 'id' ) . '" /></td>'
						.					'<td><a href="javascript: void(0);" onclick="' . $editJs . '">' . $row->getOwnerName() . '</a></td>'
						.					'<td>' . $row->getUserName() . '</td>'
						.					'<td style="text-align:center;">' . $row->get( 'type' ) . '</td>'
						.					'<td style="text-align:center;">' . $row->get( 'subtype' ) . '</td>'
						.					'<td style="text-align:center;">' . $row->get( 'item' ) . '</td>'
						.					'<td style="text-align:center;">' . cbFormatDate( $row->get( 'date' ) ) . '</td>'
						.					'<td style="text-align:center;">' . (int) $row->get( 'id' ). '</td>'
						.				'</tr>';

			$oneOrTwo	=	( $oneOrTwo == 1 ? 2 : 1 );
		} else {
			$return		.=				'<tr>'
						.					'<td colspan="9">';

			if ( $pageNav->searching ) {
				$return	.=						CBTxt::T( 'No activity search results found.' );
			} else {
				$return	.=						CBTxt::T( 'There currently is no activity.' );
			}

			$return		.=					'</td>'
						.				'</tr>';
		}

		$return			.=			'</tbody>'
						.			'<tfoot>'
						.				'<tr>'
						.					'<th colspan="9" style="text-align:center;">' . $pageNav->getListFooter() . '</th>'
						.				'</tr>'
						.			'</tfoot>'
						.		'</table>'
						.		'<table class="adminlist batchForm" width="100%" cellspacing="0" cellpadding="4" border="0" style="margin-top: 10px;">'
						.			'<thead>'
						.				'<tr>'
						.					'<th colspan="2" style="text-align: left;">' . CBTxt::Th( 'Batch Process' ) . '</th>'
						.				'</tr>'
						.			'</thead>'
						.			'<tbody>'
						.				'<tr>'
						.					'<td width="10%">' . CBTxt::Th( 'Owner' ) . '</td>'
						.					'<td>' . $input['batch_owner'] . '</td>'
						.				'</tr>'
						.				'<tr>'
						.					'<td width="10%">' . CBTxt::Th( 'User' ) . '</td>'
						.					'<td>' . $input['batch_user'] . '</td>'
						.				'</tr>'
						.				'<tr>'
						.					'<td width="10%">' . CBTxt::Th( 'Type' ) . '</td>'
						.					'<td>' . $input['batch_type'] . '</td>'
						.				'</tr>'
						.				'<tr>'
						.					'<td width="10%">' . CBTxt::Th( 'Sub-Type' ) . '</td>'
						.					'<td>' . $input['batch_subtype'] . '</td>'
						.				'</tr>'
						.				'<tr>'
						.					'<td width="10%">' . CBTxt::Th( 'Item' ) . '</td>'
						.					'<td>' . $input['batch_item'] . '</td>'
						.				'</tr>'
						.				'<tr>'
						.					'<td width="10%">' . CBTxt::Th( 'Title' ) . '</td>'
						.					'<td>' . $input['batch_title'] . '</td>'
						.				'</tr>'
						.				'<tr>'
						.					'<td width="10%">' . CBTxt::Th( 'Icon' ) . '</td>'
						.					'<td>' . $input['batch_icon'] . '</td>'
						.				'</tr>'
						.				'<tr>'
						.					'<td width="10%">' . CBTxt::Th( 'Class' ) . '</td>'
						.					'<td>' . $input['batch_class'] . '</td>'
						.				'</tr>'
						.			'</tbody>'
						.			'<tfoot>'
						.				'<tr>'
						.					'<th colspan="2" style="text-align: left;">'
						.						'<input type="button" class="batchSubmit" value="' . htmlspecialchars( CBTxt::T( 'Process' ) ) . '">'
						.						' <input type="button" class="batchReset" value="' . htmlspecialchars( CBTxt::T( 'Reset' ) ) . '">'
						.					'</th>'
						.				'</tr>'
						.			'</tfoot>'
						.		'</table>'
						.		'<input type="hidden" id="option" name="option" value="' . htmlspecialchars( $plugin->option ) . '" />'
						.		'<input type="hidden" id="task" name="task" value="editPlugin" />'
						.		'<input type="hidden" id="cid" name="cid" value="' . (int) $plugin->id . '" />'
						.		'<input type="hidden" id="action" name="action" value="activity" />'
						.		cbGetSpoofInputTag( 'plugin' )
						.	'</form>';

		echo $return;
	}

	static public function showActivityEdit( $row, $input, $user, $plugin ) {
		global $_CB_framework;

		if ( $row->id ) {
			$title	=	CBTxt::P( 'Activity: <small>Edit [[id]]</small>', array( '[id]' => $row->get( 'id' ) ) );
		} else {
			$title	=	CBTxt::T( 'Activity: <small>New</small>' );
		}

		HTML_cbactivityAdmin::setTitle( $title, 'cbicon-48-cbactivity-activity' );

		$return		=	'<form action="' . $_CB_framework->backendUrl( 'index.php' ) . '" method="post" name="adminForm" enctype="multipart/form-data" id="adminForm" class="adminform">'
					.		'<table class="adminlist" width="100%" cellspacing="0" cellpadding="4" border="0">'
					.			'<tbody>'
					.				'<tr>'
					.					'<th width="15%"><div>' . CBTxt::T( 'Owner' ) . '</div><div><small>' . CBTxt::T( '(required)' ) . '</small></div></th>'
					.					'<td width="50%">' . $input['owner'] . '</td>'
					.					'<td>' . CBTxt::T( 'Input owner of activity as single integer user_id.' ) . '</td>'
					.				'</tr>'
					.				'<tr>'
					.					'<th width="15%">' . CBTxt::T( 'User' ) . '</th>'
					.					'<td width="50%">' . $input['user'] . '</td>'
					.					'<td>' . CBTxt::T( 'Input target user of activity as single integer user_id.' ) . '</td>'
					.				'</tr>'
					.				'<tr>'
					.					'<th width="15%"><div>' . CBTxt::T( 'Type' ) . '</div><div><small>' . CBTxt::T( '(required)' ) . '</small></div></th>'
					.					'<td width="50%">' . $input['type'] . '</td>'
					.					'<td>' . CBTxt::T( 'Input activity entry type.' ) . '</td>'
					.				'</tr>'
					.				'<tr>'
					.					'<th width="15%">' . CBTxt::T( 'Sub-Type' ) . '</th>'
					.					'<td width="50%">' . $input['subtype'] . '</td>'
					.					'<td>' . CBTxt::T( 'Optionally input activity entry sub-type.' ) . '</td>'
					.				'</tr>'
					.				'<tr>'
					.					'<th width="15%">' . CBTxt::T( 'Item' ) . '</th>'
					.					'<td width="50%">' . $input['item'] . '</td>'
					.					'<td>' . CBTxt::T( 'Optionally input activity entry item id.' ) . '</td>'
					.				'</tr>'
					.				'<tr>'
					.					'<th width="15%">' . CBTxt::T( 'From' ) . '</th>'
					.					'<td width="50%">' . $input['from'] . '</td>'
					.					'<td>' . CBTxt::T( 'Optionally input activity entry old value.' ) . '</td>'
					.				'</tr>'
					.				'<tr>'
					.					'<th width="15%">' . CBTxt::T( 'To' ) . '</th>'
					.					'<td width="50%">' . $input['to'] . '</td>'
					.					'<td>' . CBTxt::T( 'Optionally input activity entry new value.' ) . '</td>'
					.				'</tr>'
					.				'<tr>'
					.					'<th width="15%">' . CBTxt::T( 'Title' ) . '</th>'
					.					'<td width="50%">' . $input['title'] . '</td>'
					.					'<td>' . CBTxt::T( 'Optionally input substitution supported activity entry title.' ) . '</td>'
					.				'</tr>'
					.				'<tr>'
					.					'<th width="15%">' . CBTxt::T( 'Message' ) . '</th>'
					.					'<td width="50%">' . $input['message'] . '</td>'
					.					'<td>' . CBTxt::T( 'Optionally input substitution supported activity entry message.' ) . '</td>'
					.				'</tr>'
					.				'<tr>'
					.					'<th width="15%">' . CBTxt::T( 'Icon' ) . '</th>'
					.					'<td width="50%">' . $input['icon'] . '</td>'
					.					'<td>' . CBTxt::T( 'Optionally input template supported icon name to be displayed next to activity time.' ) . '</td>'
					.				'</tr>'
					.				'<tr>'
					.					'<th width="15%">' . CBTxt::T( 'Class' ) . '</th>'
					.					'<td width="50%">' . $input['class'] . '</td>'
					.					'<td>' . CBTxt::T( 'Optionally input activity class surrounding entire activity entry.' ) . '</td>'
					.				'</tr>'
					.			'</tbody>'
					.		'</table>'
					.		'<input type="hidden" id="id" name="id" value="' . (int) $row->get( 'id' ) . '" />'
					.		'<input type="hidden" id="option" name="option" value="' . htmlspecialchars( $plugin->option ) . '" />'
					.		'<input type="hidden" id="task" name="task" value="editPlugin" />'
					.		'<input type="hidden" id="cid" name="cid" value="' . (int) $plugin->id . '" />'
					.		'<input type="hidden" id="action" name="action" value="activity.save" />'
					.		cbGetSpoofInputTag( 'plugin' )
					.	'</form>';

		echo $return;
	}

	static public function showConfig( $input, $user, $plugin ) {
		global $_CB_framework;

		HTML_cbactivityAdmin::setTitle( CBTxt::T( 'Configuration' ), 'cbicon-48-cbactivity-config' );

		$tabs		=	new cbTabs( 1, 2 );

		$return		=	'<form action="' . $_CB_framework->backendUrl( 'index.php' ) . '" method="post" name="adminForm" enctype="multipart/form-data" id="adminForm" class="adminform">'
					.		$tabs->startPane( 'activityConfig' )
					.			$tabs->startTab( null, htmlspecialchars( CBTxt::T( 'General' ) ), 'activityGeneral' )
					.				'<table class="adminlist" width="100%" cellspacing="0" cellpadding="4" border="0">'
					.					'<tbody>'
					.						'<tr>'
					.							'<th width="15%">' . CBTxt::T( 'Template' ) . '</th>'
					.							'<td width="50%">' . $input['general_template'] . '</td>'
					.							'<td>' . CBTxt::P( 'Select template to be used for all of CB Activity. If template is incomplete then missing files will be used from the default template. Template files can be located at the following location: [rel_path]/templates/', array( '[rel_path]' => $plugin->relPath ) ) . '</td>'
					.						'</tr>'
					.						'<tr>'
					.							'<th width="15%">' . CBTxt::T( 'Class' ) . '</th>'
					.							'<td width="50%">' . $input['general_class'] . '</td>'
					.							'<td>' . CBTxt::T( 'Optionally add a class suffix to surrounding DIV encasing all of CB Activity.' ) . '</td>'
					.						'</tr>'
					.						'<tr>'
					.							'<th width="15%">' . CBTxt::T( 'Exclude' ) . '</th>'
					.							'<td width="50%">' . $input['general_exclude'] . '</td>'
					.							'<td>' . CBTxt::T( 'Optionally input comma separated list of user ids to exclude from activity display (e.g. 62,89,43). Note activity will still log.' ) . '</td>'
					.						'</tr>'
					.						'<tr>'
					.							'<th width="15%">' . CBTxt::T( 'Delete' ) . '</th>'
					.							'<td width="50%">' . $input['general_delete'] . '</td>'
					.							'<td>' . CBTxt::T( 'Enable or disable deletetion of user activity when user is deleted. Note this only functions when user is deleted through Community Builder.' ) . '</td>'
					.						'</tr>'
					.					'</tbody>'
					.				'</table>'
					.			$tabs->endTab()
					.			$tabs->startTab( null, htmlspecialchars( CBTxt::T( 'Tab' ) ), 'activityTab' )
					.				$tabs->startPane( 'activityTabTabs' )
					.					$tabs->startTab( null, htmlspecialchars( CBTxt::T( 'General' ) ), 'activityTabGeneral' )
					.						'<table class="adminlist" width="100%" cellspacing="0" cellpadding="4" border="0">'
					.							'<tbody>'
					.								'<tr>'
					.									'<th width="15%">' . CBTxt::T( 'Display' ) . '</th>'
					.									'<td width="50%">' . $input['tab_display'] . '</td>'
					.									'<td>' . CBTxt::T( 'Select whos activity is displayed on profile tab.' ) . '</td>'
					.								'</tr>'
					.								'<tr>'
					.									'<th width="15%">' . CBTxt::T( 'Access' ) . '</th>'
					.									'<td width="50%">' . $input['tab_access'] . '</td>'
					.									'<td>' . CBTxt::T( 'Select who has access to activity on profile tab.' ) . '</td>'
					.								'</tr>'
					.								'<tr>'
					.									'<th width="15%">' . CBTxt::T( 'Dynamic Updating' ) . '</th>'
					.									'<td width="50%">' . $input['tab_update'] . '</td>'
					.									'<td>' . CBTxt::T( 'Enable or disable usage of automatic updating of activity as new activity occurs on profile tab.' ) . '</td>'
					.								'</tr>'
					.								'<tr>'
					.									'<th width="15%">' . CBTxt::T( 'Dynamic Updating Interval' ) . '</th>'
					.									'<td width="50%">' . $input['tab_interval'] . '</td>'
					.									'<td>' . CBTxt::T( 'Input time in minutes between automatic updating of activity. Note this has no affect if dynamic updating is disabled.' ) . '</td>'
					.								'</tr>'
					.								'<tr>'
					.									'<th width="15%">' . CBTxt::T( 'Dynamic Updating Limit' ) . '</th>'
					.									'<td width="50%">' . $input['tab_interval_limit'] . '</td>'
					.									'<td>' . CBTxt::T( 'Input number of times dynamic updating can occur per page load for a user. Note this has no affect if dynamic updating is disabled.' ) . '</td>'
					.								'</tr>'
					.								'<tr>'
					.									'<th width="15%">' . CBTxt::T( 'Date Cut Off' ) . '</th>'
					.									'<td width="50%">' . $input['tab_cut_off'] . '</td>'
					.									'<td>' . CBTxt::T( 'Select how far out activity is displayed. E.g. selecting 1 week will only show activity that has occurred within the last week.' ) . '</td>'
					.								'</tr>'
					.								'<tr>'
					.									'<th width="15%">' . CBTxt::T( 'Hide Empty' ) . '</th>'
					.									'<td width="50%">' . $input['tab_hide_empty'] . '</td>'
					.									'<td>' . CBTxt::T( 'Enable or disable hiding of empty activity tab.' ) . '</td>'
					.								'</tr>'
					.							'</tbody>'
					.						'</table>'
					.					$tabs->endTab()
					.					$tabs->startTab( null, htmlspecialchars( CBTxt::T( 'Paging' ) ), 'activityTabPaging' )
					.						'<table class="adminlist" width="100%" cellspacing="0" cellpadding="4" border="0">'
					.							'<tbody>'
					.								'<tr>'
					.									'<th width="15%">' . CBTxt::T( 'Paging' ) . '</th>'
					.									'<td width="50%">' . $input['tab_paging'] . '</td>'
					.									'<td>' . CBTxt::T( 'Enable or disable usage of paging on profile tab.' ) . '</td>'
					.								'</tr>'
					.								'<tr>'
					.									'<th width="15%">' . CBTxt::T( 'Dynamic Paging' ) . '</th>'
					.									'<td width="50%">' . $input['tab_paging_jquery'] . '</td>'
					.									'<td>' . CBTxt::T( 'Enable or disable usage of instant paging without page reloads through "More" link.' ) . '</td>'
					.								'</tr>'
					.								'<tr>'
					.									'<th width="15%">' . CBTxt::T( 'Auto Dynamic Paging' ) . '</th>'
					.									'<td width="50%">' . $input['tab_paging_auto'] . '</td>'
					.									'<td>' . CBTxt::T( 'Enable or disable automatic paging of activity when scrolling to bottom of displayed activity. Note this has no affect if dynamic paging is disabled.' ) . '</td>'
					.								'</tr>'
					.								'<tr>'
					.									'<th width="15%">' . CBTxt::T( 'Page Limit' ) . '</th>'
					.									'<td width="50%">' . $input['tab_limit'] . '</td>'
					.									'<td>' . CBTxt::T( 'Input page limit on profile tab. Page limit determines how many entries are displayed per page.' ) . '</td>'
					.								'</tr>'
					.							'</tbody>'
					.						'</table>'
					.					$tabs->endTab()
					.				$tabs->endPane()
					.			$tabs->endTab()
					.			$tabs->startTab( null, htmlspecialchars( CBTxt::T( 'Recent' ) ), 'activityRecent' )
					.				$tabs->startPane( 'activityRecentTabs' )
					.					$tabs->startTab( null, htmlspecialchars( CBTxt::T( 'General' ) ), 'activityRecentGeneral' )
					.						'<table class="adminlist" width="100%" cellspacing="0" cellpadding="4" border="0">'
					.							'<tbody>'
					.								'<tr>'
					.									'<th width="15%">' . CBTxt::T( 'Display' ) . '</th>'
					.									'<td width="50%">' . $input['recent_display'] . '</td>'
					.									'<td>' . CBTxt::T( 'Select whos activity is displayed on recent page.' ) . '</td>'
					.								'</tr>'
					.								'<tr>'
					.									'<th width="15%">' . CBTxt::T( 'Access' ) . '</th>'
					.									'<td width="50%">' . $input['recent_access'] . '</td>'
					.									'<td>' . CBTxt::T( 'Select who has access to activity on recent page.' ) . '</td>'
					.								'</tr>'
					.								'<tr>'
					.									'<th width="15%">' . CBTxt::T( 'Dynamic Updating' ) . '</th>'
					.									'<td width="50%">' . $input['recent_update'] . '</td>'
					.									'<td>' . CBTxt::T( 'Enable or disable usage of automatic updating of activity as new activity occurs on recent page.' ) . '</td>'
					.								'</tr>'
					.								'<tr>'
					.									'<th width="15%">' . CBTxt::T( 'Dynamic Updating Interval' ) . '</th>'
					.									'<td width="50%">' . $input['recent_interval'] . '</td>'
					.									'<td>' . CBTxt::T( 'Input time in minutes between automatic updating of activity. Note this has no affect if dynamic updating is disabled.' ) . '</td>'
					.								'</tr>'
					.								'<tr>'
					.									'<th width="15%">' . CBTxt::T( 'Dynamic Updating Limit' ) . '</th>'
					.									'<td width="50%">' . $input['recent_interval_limit'] . '</td>'
					.									'<td>' . CBTxt::T( 'Input number of times dynamic updating can occur per page load for a user. Note this has no affect if dynamic updating is disabled.' ) . '</td>'
					.								'</tr>'
					.								'<tr>'
					.									'<th width="15%">' . CBTxt::T( 'Date Cut Off' ) . '</th>'
					.									'<td width="50%">' . $input['recent_cut_off'] . '</td>'
					.									'<td>' . CBTxt::T( 'Select how far out activity is displayed. E.g. selecting 1 week will only show activity that has occurred within the last week.' ) . '</td>'
					.								'</tr>'
					.							'</tbody>'
					.						'</table>'
					.					$tabs->endTab()
					.					$tabs->startTab( null, htmlspecialchars( CBTxt::T( 'Paging' ) ), 'activityRecentPaging' )
					.						'<table class="adminlist" width="100%" cellspacing="0" cellpadding="4" border="0">'
					.							'<tbody>'
					.								'<tr>'
					.									'<th width="15%">' . CBTxt::T( 'Paging' ) . '</th>'
					.									'<td width="50%">' . $input['recent_paging'] . '</td>'
					.									'<td>' . CBTxt::T( 'Enable or disable usage of paging on recent page.' ) . '</td>'
					.								'</tr>'
					.								'<tr>'
					.									'<th width="15%">' . CBTxt::T( 'Dynamic Paging' ) . '</th>'
					.									'<td width="50%">' . $input['recent_paging_jquery'] . '</td>'
					.									'<td>' . CBTxt::T( 'Enable or disable usage of instant paging without page reloads through "More" link.' ) . '</td>'
					.								'</tr>'
					.								'<tr>'
					.									'<th width="15%">' . CBTxt::T( 'Auto Dynamic Paging' ) . '</th>'
					.									'<td width="50%">' . $input['recent_paging_auto'] . '</td>'
					.									'<td>' . CBTxt::T( 'Enable or disable automatic paging of activity when scrolling to bottom of displayed activity. Note this has no affect if dynamic paging is disabled.' ) . '</td>'
					.								'</tr>'
					.								'<tr>'
					.									'<th width="15%">' . CBTxt::T( 'Page Limit' ) . '</th>'
					.									'<td width="50%">' . $input['recent_limit'] . '</td>'
					.									'<td>' . CBTxt::T( 'Input page limit on recent page. Page limit determines how many entries are displayed per page.' ) . '</td>'
					.								'</tr>'
					.							'</tbody>'
					.						'</table>'
					.					$tabs->endTab()
					.				$tabs->endPane()
					.			$tabs->endTab()
					.			$tabs->startTab( null, htmlspecialchars( CBTxt::T( 'Datetimes' ) ), 'activityDatetimes' )
					.				'<table class="adminlist" width="100%" cellspacing="0" cellpadding="4" border="0">'
					.					'<tbody>'
					.						'<tr>'
					.							'<th width="15%">' . CBTxt::T( 'Format' ) . '</th>'
					.							'<td width="50%">' . $input['date_format'] . '</td>'
					.							'<td>' . CBTxt::T( 'Optionally input custom PHP date formatting for date and time display. Note this has no affect if dynamic timeago display is used.' ) . '</td>'
					.						'</tr>'
					.						'<tr>'
					.							'<th width="15%">' . CBTxt::T( 'Timeago' ) . '</th>'
					.							'<td width="50%">' . $input['date_ago'] . '</td>'
					.							'<td>' . CBTxt::T( 'Optionally enable or disable display of how long ago an activity entry was made instead of raw date.' ) . '</td>'
					.						'</tr>'
					.						'<tr>'
					.							'<th width="15%">' . CBTxt::T( 'Dynamic Timeago' ) . '</th>'
					.							'<td width="50%">' . $input['date_jquery'] . '</td>'
					.							'<td>' . CBTxt::T( 'Optionally enable or disable automatic updating of timeago. Note this has no affect if timeago is disabled.' ) . '</td>'
					.						'</tr>'
					.					'</tbody>'
					.				'</table>'
					.			$tabs->endTab()
					.			$tabs->startTab( null, htmlspecialchars( CBTxt::T( 'Activity' ) ), 'activityActivity' )
					.				$tabs->startPane( 'activityActivityTabs' )
					.					$tabs->startTab( null, htmlspecialchars( CBTxt::T( 'Display' ) ), 'activityActivityDisplay' )
					.						'<table class="adminlist" width="100%" cellspacing="0" cellpadding="4" border="0">'
					.							'<tbody>'
					.								'<tr>'
					.									'<th width="15%">' . CBTxt::T( 'Title Length' ) . '</th>'
					.									'<td width="50%">' . $input['activity_title_length'] . '</td>'
					.									'<td>' . CBTxt::T( 'Optionally input maximum activity title length. Leave blank or 0 for no limit.' ) . '</td>'
					.								'</tr>'
					.								'<tr>'
					.									'<th width="15%">' . CBTxt::T( 'Description Length' ) . '</th>'
					.									'<td width="50%">' . $input['activity_desc_length'] . '</td>'
					.									'<td>' . CBTxt::T( 'Optionally input maximum activity description length. Leave blank or 0 for no limit.' ) . '</td>'
					.								'</tr>'
					.								'<tr>'
					.									'<th width="15%">' . CBTxt::T( 'Image Thumbnails' ) . '</th>'
					.									'<td width="50%">' . $input['activity_img_thumbnails'] . '</td>'
					.									'<td>' . CBTxt::T( 'Enable or disable usage of thumbnails when an image, video, or any sort of logo is displayed.' ) . '</td>'
					.								'</tr>'
					.							'</tbody>'
					.						'</table>'
					.					$tabs->endTab()
					.					$tabs->startTab( null, htmlspecialchars( CBTxt::T( 'General' ) ), 'activityActivityGeneral' )
					.						'<table class="adminlist" width="100%" cellspacing="0" cellpadding="4" border="0">'
					.							'<tbody>'
					.								'<tr>'
					.									'<th width="15%">' . CBTxt::T( 'Registration' ) . '</th>'
					.									'<td width="50%">' . $input['activity_registration'] . '</td>'
					.									'<td>' . CBTxt::T( 'Enable or disable logging of new registration activity.' ) . '</td>'
					.								'</tr>'
					.								'<tr>'
					.									'<th width="15%">' . CBTxt::T( 'Login' ) . '</th>'
					.									'<td width="50%">' . $input['activity_login'] . '</td>'
					.									'<td>' . CBTxt::T( 'Enable or disable logging of user login activity.' ) . '</td>'
					.								'</tr>'
					.								'<tr>'
					.									'<th width="15%">' . CBTxt::T( 'Logout' ) . '</th>'
					.									'<td width="50%">' . $input['activity_logout'] . '</td>'
					.									'<td>' . CBTxt::T( 'Enable or disable logging of user logout activity.' ) . '</td>'
					.								'</tr>'
					.								'<tr>'
					.									'<th width="15%">' . CBTxt::T( 'Profile Update' ) . '</th>'
					.									'<td width="50%">' . $input['activity_profile'] . '</td>'
					.									'<td>' . CBTxt::T( 'Enable or disable logging of profile update activity. This only occurs if a field is actually changed when updating profile. Profile Picture and Password fields are ignored.' ) . '</td>'
					.								'</tr>'
					.								'<tr>'
					.									'<th width="15%">' . CBTxt::T( 'Profile Picture Update' ) . '</th>'
					.									'<td width="50%">' . $input['activity_avatar'] . '</td>'
					.									'<td>' . CBTxt::T( 'Enable or disable logging of profile picture update activity. This is not included with Profile Update.' ) . '</td>'
					.								'</tr>'
					.								'<tr>'
					.									'<th width="15%">' . CBTxt::T( 'Accept Connection Request' ) . '</th>'
					.									'<td width="50%">' . $input['activity_connections'] . '</td>'
					.									'<td>' . CBTxt::T( 'Enable or disable logging of accepted connection requests. It does not log until the connection is completed. If mutuel connections is enabled both users will log the activity.' ) . '</td>'
					.								'</tr>'
					.							'</tbody>'
					.						'</table>'
					.					$tabs->endTab()
					.					$tabs->startTab( null, htmlspecialchars( CBTxt::T( 'CB GroupJive' ) ), 'activityActivityCBGroupJive' )
					.						'<table class="adminlist" width="100%" cellspacing="0" cellpadding="4" border="0">'
					.							'<tbody>'
					.								'<tr>'
					.									'<th width="15%">' . CBTxt::T( 'Category Create' ) . '</th>'
					.									'<td width="50%">' . $input['activity_gj_cat_create'] . '</td>'
					.									'<td>' . CBTxt::T( 'Enable or disable logging of newly created categories.' ) . '</td>'
					.								'</tr>'
					.								'<tr>'
					.									'<th width="15%">' . CBTxt::T( 'Group Create' ) . '</th>'
					.									'<td width="50%">' . $input['activity_gj_grp_create'] . '</td>'
					.									'<td>' . CBTxt::T( 'Enable or disable logging of newly created groups.' ) . '</td>'
					.								'</tr>'
					.								'<tr>'
					.									'<th width="15%">' . CBTxt::T( 'Group Join' ) . '</th>'
					.									'<td width="50%">' . $input['activity_gj_grp_join'] . '</td>'
					.									'<td>' . CBTxt::T( 'Enable or disable logging of joined groups. This does not include owner of a newly created group.' ) . '</td>'
					.								'</tr>'
					.								'<tr>'
					.									'<th width="15%">' . CBTxt::T( 'Events Create' ) . '</th>'
					.									'<td width="50%">' . $input['activity_gj_events_create'] . '</td>'
					.									'<td>' . CBTxt::T( 'Enable or disable logging of newly scheduled events.' ) . '</td>'
					.								'</tr>'
					.								'<tr>'
					.									'<th width="15%">' . CBTxt::T( 'Events Attend' ) . '</th>'
					.									'<td width="50%">' . $input['activity_gj_events_attend'] . '</td>'
					.									'<td>' . CBTxt::T( 'Enable or disable logging of attending an event. This only logs if attendance is set to Yes.' ) . '</td>'
					.								'</tr>'
					.								'<tr>'
					.									'<th width="15%">' . CBTxt::T( 'Files Create' ) . '</th>'
					.									'<td width="50%">' . $input['activity_gj_files_create'] . '</td>'
					.									'<td>' . CBTxt::T( 'Enable or disable logging of newly uploaded files.' ) . '</td>'
					.								'</tr>'
					.								'<tr>'
					.									'<th width="15%">' . CBTxt::T( 'Photos Create' ) . '</th>'
					.									'<td width="50%">' . $input['activity_gj_photos_create'] . '</td>'
					.									'<td>' . CBTxt::T( 'Enable or disable logging of newly uploaded photos.' ) . '</td>'
					.								'</tr>'
					.								'<tr>'
					.									'<th width="15%">' . CBTxt::T( 'Videos Create' ) . '</th>'
					.									'<td width="50%">' . $input['activity_gj_videos_create'] . '</td>'
					.									'<td>' . CBTxt::T( 'Enable or disable logging of newly published videos.' ) . '</td>'
					.								'</tr>'
					.									'<th width="15%">' . CBTxt::T( 'Wall Create' ) . '</th>'
					.									'<td width="50%">' . $input['activity_gj_wall_create'] . '</td>'
					.									'<td>' . CBTxt::T( 'Enable or disable logging of newly created wall posts.' ) . '</td>'
					.								'</tr>'
					.								'</tr>'
					.									'<th width="15%">' . CBTxt::T( 'Wall Reply' ) . '</th>'
					.									'<td width="50%">' . $input['activity_gj_wall_reply'] . '</td>'
					.									'<td>' . CBTxt::T( 'Enable or disable logging of wall post replies.' ) . '</td>'
					.								'</tr>'
					.							'</tbody>'
					.						'</table>'
					.					$tabs->endTab()
					.					$tabs->startTab( null, htmlspecialchars( CBTxt::T( 'Kunena' ) ), 'activityActivityKunena' )
					.						'<table class="adminlist" width="100%" cellspacing="0" cellpadding="4" border="0">'
					.							'<tbody>'
					.								'<tr>'
					.									'<th width="15%">' . CBTxt::T( 'Topic Create' ) . '</th>'
					.									'<td width="50%">' . $input['activity_kunena_create'] . '</td>'
					.									'<td>' . CBTxt::T( 'Enable or disable logging of newly created topics.' ) . '</td>'
					.								'</tr>'
					.								'<tr>'
					.									'<th width="15%">' . CBTxt::T( 'Topic Reply' ) . '</th>'
					.									'<td width="50%">' . $input['activity_kunena_reply'] . '</td>'
					.									'<td>' . CBTxt::T( 'Enable or disable logging of topic replies.' ) . '</td>'
					.								'</tr>'
					.							'</tbody>'
					.						'</table>'
					.					$tabs->endTab()
					.					$tabs->startTab( null, htmlspecialchars( CBTxt::T( 'CB ProfileBook' ) ), 'activityActivityCBProfileBook' )
					.						'<table class="adminlist" width="100%" cellspacing="0" cellpadding="4" border="0">'
					.							'<tbody>'
					.								'<tr>'
					.									'<th width="15%">' . CBTxt::T( 'Guestbook Create' ) . '</th>'
					.									'<td width="50%">' . $input['activity_pb_guest_create'] . '</td>'
					.									'<td>' . CBTxt::T( 'Enable or disable logging of newly created guestbook entries.' ) . '</td>'
					.								'</tr>'
					.								'<tr>'
					.									'<th width="15%">' . CBTxt::T( 'Wall Create' ) . '</th>'
					.									'<td width="50%">' . $input['activity_pb_wall_create'] . '</td>'
					.									'<td>' . CBTxt::T( 'Enable or disable logging of newly created wall entries.' ) . '</td>'
					.								'</tr>'
					.								'<tr>'
					.									'<th width="15%">' . CBTxt::T( 'Blog Create' ) . '</th>'
					.									'<td width="50%">' . $input['activity_pb_blog_create'] . '</td>'
					.									'<td>' . CBTxt::T( 'Enable or disable logging of newly created blog entries.' ) . '</td>'
					.								'</tr>'
					.							'</tbody>'
					.						'</table>'
					.					$tabs->endTab()
					.					$tabs->startTab( null, htmlspecialchars( CBTxt::T( 'CB Profile Gallery' ) ), 'activityActivityCBProfileGallery' )
					.						'<table class="adminlist" width="100%" cellspacing="0" cellpadding="4" border="0">'
					.							'<tbody>'
					.								'<tr>'
					.									'<th width="15%">' . CBTxt::T( 'Image/File Create' ) . '</th>'
					.									'<td width="50%">' . $input['activity_pg_create'] . '</td>'
					.									'<td>' . CBTxt::T( 'Enable or disable logging of newly uploaded images or files.' ) . '</td>'
					.								'</tr>'
					.							'</tbody>'
					.						'</table>'
					.					$tabs->endTab()
					.					$tabs->startTab( null, htmlspecialchars( CBTxt::T( 'CB Blogs' ) ), 'activityActivityCBBlogs' )
					.						'<table class="adminlist" width="100%" cellspacing="0" cellpadding="4" border="0">'
					.							'<tbody>'
					.								'<tr>'
					.									'<th width="15%">' . CBTxt::T( 'Blog Create' ) . '</th>'
					.									'<td width="50%">' . $input['activity_cbblogs_create'] . '</td>'
					.									'<td>' . CBTxt::T( 'Enable or disable logging of newly created blog entries.' ) . '</td>'
					.								'</tr>'
					.							'</tbody>'
					.						'</table>'
					.					$tabs->endTab()
					.				$tabs->endPane()
					.			$tabs->endTab()
					.		$tabs->endPane()
					.		'<input type="hidden" id="option" name="option" value="' . htmlspecialchars( $plugin->option ) . '" />'
					.		'<input type="hidden" id="task" name="task" value="editPlugin" />'
					.		'<input type="hidden" id="cid" name="cid" value="' . (int) $plugin->id . '" />'
					.		'<input type="hidden" id="action" name="action" value="config.save" />'
					.		cbGetSpoofInputTag( 'plugin' )
					.	'</form>';

		echo $return;
	}

	static private function setTitle( $title, $class ) {
		global $_PLUGIN_Backend_Title;

		$_PLUGIN_Backend_Title	=	array( $title, $class );
	}
}
?>