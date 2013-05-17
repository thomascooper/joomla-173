<?php
if ( ! ( defined( '_VALID_CB' ) || defined( '_JEXEC' ) || defined( '_VALID_MOS' ) ) ) { die( 'Direct Access to this location is not allowed.' ); }

class HTML_groupjiveInputLimit {

	/**
	 * output input character limit jquery
	 *
	 * @param string $input selector for the input to check against (will also establish for html editor)
	 * @param string $limitInput selector for the input to update with current limit (optional)
	 * @param int $limit maximum character limit
	 */
	static function loadJquery( $input, $limitInput, $limit ) {
		global $_CB_framework;

		if ( $input && $limit ) {
			$inputJs			=	"$( '" . addslashes( $input ) . "' ).bind( 'keypress keyup focus blur', function() {"
								.		"var length = $( this ).val().length;"
								.		"var count = ( " . (int) $limit . " - length );"
								.		( $limitInput ? "$( '" . addslashes( $limitInput ) . "' ).attr( 'value', ( count > 0 ? count : 0 ) );" : null )
								.		"if ( count <= 0 ) {"
								.			"var value = $( this ).val();"
								.			"var newValue = value.substr( 0, " . (int) $limit . " );"
								.			"$( this ).attr( 'value', newValue );"
								.		"}"
								.	"});"
								.	"$( '" . addslashes( $input ) . "_parent' ).live( 'click mouseenter mouseleave', function() {"
								.		"var editor = $( '" . addslashes( $input ) . "_parent iframe' ).contents().find( 'body' );"
								.		"var length = editor.html().length;"
								.		"var count = ( " . (int) $limit . " - length );"
								.		( $limitInput ? "$( '" . addslashes( $limitInput ) . "' ).attr( 'value', ( count > 0 ? count : 0 ) );" : null )
								.		"if ( count <= 0 ) {"
								.			"var value = editor.html();"
								.			"var newValue = value.substr( 0, " . (int) $limit . " );"
								.			"editor.html( newValue );"
								.		"}"
								.	"});";

			$_CB_framework->outputCbJQuery( $inputJs );
		}
	}
}
?>