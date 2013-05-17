<?php
if ( ! ( defined( '_VALID_CB' ) || defined( '_JEXEC' ) || defined( '_VALID_MOS' ) ) ) { die( 'Direct Access to this location is not allowed.' ); }

class HTML_groupjiveFormValidation {

	/**
	 * output form validation jquery
	 *
	 * @param string $selector
	 * @param string $params
	 */
	static function loadJquery( $selector, $params ) {
		global $_CB_framework;

		if ( ! $selector ) {
			$selector	=	'#gjForm';
		}

		$js				=	"$( '" . addslashes( $selector ) . "' ).validate( {"
						.		"submitHandler: function( form ) {"
						.			"$( form ).find( 'input[type=\"submit\"]' ).attr( 'disabled', 'disabled' ).addClass( 'disabled' ).val( '" . addslashes( CBTxt::T( 'Loading...' ) ) . "' );"
						.			"form.submit();"
						.		"},";

		if ( $params ) {
			$js			.=		$params . ( ( cbIsoUtf_substr( trim( $params ), -1 ) != ',' ) ? ',' : null );
		}

		$js				.=		"ignoreTitle: true,"
						.		"errorClass: 'gjValidationError',"
						.		"highlight: function( element, errorClass ) {"
						.			"$( element ).parent().parent().addClass( 'error');"
						.		"},"
						.		"unhighlight: function( element, errorClass ) {"
						.			"$( element ).parent().parent().removeClass( 'error' );"
						.		"},"
						.		"errorElement: 'div',"
						.		"errorPlacement: function( error, element ) {"
						.			"$( element ).parent().children().last().after( error );"
						.		"}"
						.	"});"
						.	"$.extend( jQuery.validator.messages, {"
						.		"required: '" . addslashes( CBTxt::T( 'This input is required.' ) ) . "',"
						.		"remote: '" . addslashes( CBTxt::T( 'Please fix this input.' ) ) . "',"
						.		"email: '" . addslashes( CBTxt::T( 'Please input a valid email address.' ) ) . "',"
						.		"url: '" . addslashes( CBTxt::T( 'Please input a valid URL.' ) ) . "',"
						.		"date: '" . addslashes( CBTxt::T( 'Please input a valid date.' ) ) . "',"
						.		"dateISO: '" . addslashes( CBTxt::T( 'Please input a valid date (ISO).' ) ) . "',"
						.		"number: '" . addslashes( CBTxt::T( 'Please input a valid number.' ) ) . "',"
						.		"digits: '" . addslashes( CBTxt::T( 'Please input only digits.' ) ) . "',"
						.		"creditcard: '" . addslashes( CBTxt::T( 'Please input a valid credit card number.' ) ) . "',"
						.		"equalTo: '" . addslashes( CBTxt::T( 'Please input the same value again.' ) ) . "',"
						.		"accept: '" . addslashes( CBTxt::T( 'Please input a value with a valid extension.' ) ) . "',"
						.		"maxlength: $.validator.format('" . addslashes( CBTxt::T( 'Please input no more than {0} characters.' ) ) . "'),"
						.		"minlength: $.validator.format('" . addslashes( CBTxt::T( 'Please input at least {0} characters.' ) ) . "'),"
						.		"rangelength: $.validator.format('" . addslashes( CBTxt::T( 'Please input a value between {0} and {1} characters long.' ) ) . "'),"
						.		"range: $.validator.format('" . addslashes( CBTxt::T( 'Please input a value between {0} and {1}.' ) ) . "'),"
						.		"max: $.validator.format('" . addslashes( CBTxt::T( 'Please input a value less than or equal to {0}.' ) ) . "'),"
						.		"min: $.validator.format('" . addslashes( CBTxt::T( 'Please input a value greater than or equal to {0}.' ) ) . "')"
						.	"});";

		$_CB_framework->outputCbJQuery( $js, 'validate' );
	}
}
?>