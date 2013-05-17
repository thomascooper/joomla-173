<?php
if ( ! ( defined( '_VALID_CB' ) || defined( '_JEXEC' ) || defined( '_VALID_MOS' ) ) ) { die( 'Direct Access to this location is not allowed.' ); }

class HTML_cbactivityJquery {

	static public function loadJquery( $location, $user, $plugin ) {
		global $_CB_framework;

		$timeago					=	( $plugin->params->get( 'date_ago', 1 ) && $plugin->params->get( 'date_jquery', 1 ) );
		$js							=	null;
		$plugins					=	array();

		if ( $timeago ) {
			static $TIMEAGO_loaded	=	0;

			if ( ! $TIMEAGO_loaded++ ) {
				$_CB_framework->addJQueryPlugin( 'timeago', $plugin->livePath . '/js/jquery.timeago.js' );

				$prefixAgo			=	CBTxt::T( 'TIMEAGOPREFIX' );

				if ( $prefixAgo == 'TIMEAGOPREFIX' ) {
					$prefixAgo		=	null;
				}

				$prefixFromNow		=	CBTxt::T( 'TIMEAGOFROMNOWPREFIX' );

				if ( $prefixFromNow == 'TIMEAGOPREFIX' ) {
					$prefixFromNow	=	null;
				}

				$js					.=	"$.extend( jQuery.timeago.settings.strings, {"
									.		( $prefixAgo ? "prefixAgo: '" . addslashes( $prefixAgo ) . "'," : null )
									.		( $prefixFromNow ? "prefixFromNow: '" . addslashes( $prefixFromNow ) . "'," : null )
									.		"suffixAgo: '" . addslashes( CBTxt::T( 'ago' ) ) . "',"
									.		"suffixFromNow: '" . addslashes( CBTxt::T( 'from now' ) ) . "',"
									.		"seconds: '" . addslashes( CBTxt::T( 'less than a minute' ) ) . "',"
									.		"minute: '" . addslashes( CBTxt::T( 'about a minute' ) ) . "',"
									.		"minutes: '" . addslashes( CBTxt::T( '%d minutes' ) ) . "',"
									.		"hour: '" . addslashes( CBTxt::T( 'about an hour' ) ) . "',"
									.		"hours: '" . addslashes( CBTxt::T( 'about %d hours' ) ) . "',"
									.		"day: '" . addslashes( CBTxt::T( 'a day' ) ) . "',"
									.		"days: '" . addslashes( CBTxt::T( '%d days' ) ) . "',"
									.		"month: '" . addslashes( CBTxt::T( 'about a month' ) ) . "',"
									.		"months: '" . addslashes( CBTxt::T( '%d months' ) ) . "',"
									.		"year: '" . addslashes( CBTxt::T( 'about a year' ) ) . "',"
									.		"years: '" . addslashes( CBTxt::T( '%d years' ) ) . "'"
									.	"});"
									.	"$( '.activityTimeago' ).timeago({ allowFuture: true });";
			}

			$plugins[]				=	'timeago';
		}

		if ( ( ( $location == 'recent' ) && $plugin->params->get( 'recent_paging', 1 ) && $plugin->params->get( 'recent_paging_jquery', 1 ) ) || ( $location == 'tab' ) && $plugin->params->get( 'tab_paging', 1 ) && $plugin->params->get( 'tab_paging_jquery', 1 ) ) {
			$js						.=	"$( '#activityForm' ).ajaxForm({"
									.		"beforeSend: function( jqXHR, settings ) {"
									.			"$( '.activityButtonMore' ).addClass( 'disabled' ).html( '" . addslashes( CBTxt::T( 'Loading...' ) ) . "' );"
									.		"},"
									.		"success: function( data, textStatus, jqXHR ) {"
									.			"if ( data.replace( /^\s+|\s+$/, '' ) ) {"
									.				"var newData = $( data );"
									.				"if ( newData.length ) {"
									.					"$( '.activityPaging' ).fadeOut( 'fast', function() {"
									.						"$( this ).replaceWith( newData.hide() );"
									.						( $timeago ? "$( '.activityTimeago' ).timeago({ allowFuture: true });" : null )
									.						"newData.fadeIn( 'slow' );"
									.					"});"
									.				"}"
									.			"}"
									.		"}"
									.	"});"
									.	"$( '#activityForm' ).delegate( '.activityButtonMore', 'click', function() {"
									.		"if ( ! $( this ).hasClass( 'disabled' ) ) {"
									.			"$( '#activityForm' ).submit();"
									.		"}"
									.	"});";

			if ( ( ( $location == 'recent' ) && $plugin->params->get( 'recent_paging_auto', 1 ) ) || ( $location == 'tab' ) && $plugin->params->get( 'tab_paging_auto', 1 ) ) {
				$js					.=	"$( window ).scroll( function() {"
									.		"if ( ( $( window ).scrollTop() + $( window ).height() ) > ( $( document ).height() - $( '#activityForm' ).offset().top ) ) {"
									.			"var isVisible = false;";

				if ( $location == 'recent' ) {
					$js				.=			"isVisible = true;";
				} elseif ( $location == 'tab' ) {
					$js				.=			"var tab = $( '#cbtab" . (int) $plugin->tab->tabid . "' );"
									.			"if ( tab.length ) {"
									.				"if ( tab.is( ':visible' ) ) {"
									.					"isVisible = true;"
									.				"}"
									.			"} else {"
									.				"isVisible = true;"
									.			"}";
				}

				$js					.=			"if ( isVisible ) {"
									.				"var more = $( '.activityButtonMore' );"
									.				"if ( more.length ) {"
									.					"more.trigger( 'click' );"
									.				"}"
									.			"}"
									.		"}"
									.	"});";
			}

			$plugins[]				=	'form';
		}

		if ( ( ( $location == 'recent' ) && $plugin->params->get( 'recent_update', 1 ) ) || ( $location == 'tab' ) && $plugin->params->get( 'tab_update', 1 ) ) {
			if ( $location == 'recent' ) {
				$interval			=	( (int) $plugin->params->get( 'recent_update_interval', 1 ) * 60000 );
				$limit				=	(int) $plugin->params->get( 'recent_update_interval_limit', 10 );
				$url				=	cbactivityClass::getPluginURL( array(), null, false, false, null, false, 'raw' );
				$data				=	"data: { 'recent_activity_last': id, 'recent_activity_ajax': 1 },";
			} elseif ( $location == 'tab' ) {
				$interval			=	( (int) $plugin->params->get( 'tab_interval', 1 ) * 60000 );
				$limit				=	(int) $plugin->params->get( 'tab_interval_limit', 10 );
				$url				=	cbactivityClass::getTabClassURL( $user->get( 'id' ), false, 'raw' );
				$data				=	"data: { 'tab_activity_last': id, 'tab_activity_ajax': 1 },";
			}

			if ( ! $interval ) {
				$interval			=	60000;
			}

			if ( ! $limit ) {
				$limit				=	10;
			}

			$js						.=	"var activityUpdateCount = 0;"
									.	"var activityAutoUpdate = setInterval( function() {"
									.		"var first = $( '#activityForm' ).children().first();"
									.		"if ( first.length ) {"
									.			"var id = first.attr( 'id' );"
									.			"if ( id ) {"
									.				"id = id.replace( /activity/i, '' );"
									.				"$.ajax({"
									.					"url: '" . addslashes( $url ) . "',"
									.					"type: 'POST',"
									.					$data
									.					"success: function( data, textStatus, jqXHR ) {"
									.						"if ( data.replace( /^\s+|\s+$/, '' ) ) {"
									.							"var newData = $( data );"
									.							"if ( newData.length ) {"
									.								"$( '#activityForm' ).prepend( newData.hide() );"
									.								( $timeago ? "$( '.activityTimeago' ).timeago({ allowFuture: true });" : null )
									.								"newData.fadeIn( 'slow' );"
									.							"}"
									.						"}"
									.					"}"
									.				"});"
									.			"}"
									.		"}"
									.		"activityUpdateCount++;"
									.		"if ( activityUpdateCount >= $limit ) {"
									.			"clearInterval( activityAutoUpdate );"
									.		"}"
									.	"}, $interval );";
		}

		if ( $js ) {
			$_CB_framework->outputCbJQuery( $js, $plugins );
		}
	}
}
?>