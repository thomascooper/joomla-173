(function($) {
	$.fn.oauthpopup = function( options ) {
		var left = ( ( screen.width / 2 ) - ( 800 / 2 ) );
		var top = ( ( screen.height / 2 ) - ( 800 / 2 ) );

		var settings						=	$.extend( {
													url: null,
													name: 'oAuthLogin',
													specs: 'location=0,status=0,width=800,height=600,left=' + left + ',top=' + top,
													init: function(){ return true; },
													changed: function(){},
													callback: function(){ window.location.reload(); }
												}, options );

		$( this ).click( function() {
			if ( settings.init( settings ) ) {
				var oAuthWindow				=	window.open( settings.url, settings.name, settings.specs );

				window.oAuthSuccess			=	false;
				window.oAuthError			=	null;

				var windowClosed			=	window.setInterval( function(){
													if ( oAuthWindow.closed ) {
														window.clearInterval( windowClosed );
														settings.callback( window.oAuthSuccess, window.oAuthError, oAuthWindow );
													}
												}, 1000 );
			}
		});
	};
})(jQuery);