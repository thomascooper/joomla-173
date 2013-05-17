<?php
if ( ! ( defined( '_VALID_CB' ) || defined( '_JEXEC' ) || defined( '_VALID_MOS' ) ) ) { die( 'Direct Access to this location is not allowed.' ); }

class HTML_groupjiveOverviewMessage {

	/**
	 * render frontend overview message
	 *
	 * @param array $input
	 * @param moscomprofilerUser $user
	 * @param object $plugin
	 */
	static function showOverviewMessage( $input, $user, $plugin ) {
		global $_CB_framework;

		$generalTitle	=	$plugin->params->get( 'general_title', $plugin->name );
		$pageTitle		=	CBTxt::P( 'Message [category]', array( '[category]' => cbgjClass::getOverride( 'category', true ) ) );

		$_CB_framework->setPageTitle( htmlspecialchars( $pageTitle ) );

		if ( $generalTitle != '' ) {
			$_CB_framework->appendPathWay( htmlspecialchars( CBTxt::T( $generalTitle ) ), cbgjClass::getPluginURL() );
		}

		$_CB_framework->appendPathWay( htmlspecialchars( cbgjClass::getOverride( 'category', true ) . ' ' . cbgjClass::getOverride( 'overview' ) ), cbgjClass::getPluginURL( array( 'overview' ) ) );
		$_CB_framework->appendPathWay( htmlspecialchars( $pageTitle ), cbgjClass::getPluginURL( array( 'overview', 'message' ) ) );

		$return			=	'<div class="gjOverviewMessage">'
						.		'<form action="' . cbgjClass::getPluginURL( array( 'overview', 'send' ) ) . '" method="post" enctype="multipart/form-data" name="gjForm" id="gjForm" class="gjForm form-horizontal">'
						.			'<legend class="gjEditTitle">' . $pageTitle . '</legend>'
						.			'<div class="gjEditContentInput control-group">'
						.				'<label class="gjEditContentInputTitle control-label">' . CBTxt::Th( 'Subject' ) . '</label>'
						.				'<div class="gjEditContentInputField controls">'
						.					$input['subject']
						.					'<span class="gjEditContentInputIcon help-inline">'
						.						cbgjClass::getIcon( null, CBTxt::T( 'Required' ), 'icon-star' )
						.						cbgjClass::getIcon( CBTxt::P( 'Input [categories] message subject.', array( '[categories]' => cbgjClass::getOverride( 'category', true ) ) ) )
						.					'</span>'
						.				'</div>'
						.			'</div>'
						.			'<div class="gjEditContentInput control-group">'
						.				'<label class="gjEditContentInputTitle control-label">' . CBTxt::Th( 'Body' ) . '</label>'
						.				'<div class="gjEditContentInputField controls">'
						.					$input['body']
						.					'<span class="gjEditContentInputIcon help-inline">'
						.						cbgjClass::getIcon( null, CBTxt::T( 'Required' ), 'icon-star' )
						.						cbgjClass::getIcon( CBTxt::P( 'Input [categories] message body.', array( '[categories]' => cbgjClass::getOverride( 'category', true ) ) ) )
						.					'</span>'
						.				'</div>'
						.			'</div>'
						.			'<div class="gjButtonWrapper form-actions">'
						.				'<input type="submit" value="' . htmlspecialchars( CBTxt::T( 'Send Message' ) ) . '" class="gjButton gjButtonSubmit btn btn-primary" />&nbsp;'
						.				'<input type="button" value="' . htmlspecialchars( CBTxt::T( 'Cancel' ) ) . '" class="gjButton gjButtonCancel btn btn-mini" onclick="' . cbgjClass::getPluginURL( array( 'overview' ), CBTxt::T( 'Are you sure you want to cancel? All unsaved data will be lost!' ) ) . '" />'
						.			'</div>'
						.			cbGetSpoofInputTag( 'plugin' )
						.		'</form>'
						.	'</div>';

		echo $return;
	}
}
?>