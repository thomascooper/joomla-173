/**
* Joomla Community Builder User Plugin: plug_cbprofilegallery
* @version $Id$
* @package plug_cbprofilegallery
* @subpackage profilegallery.js
* @author Nant, JoomlaJoe and Beat
* @copyright (C) Nant, JoomlaJoe and Beat, www.joomlapolis.com
* @license Limited http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU/GPL version 2
*/

(function($) {
	$.fn.cbpgtoggleEditor = function(options) {
		this.each(function() {
			if ( $(this).hasClass('cbpgEditorShow') ) {
				$(this).addClass('cbpgEditorVisible').next('div').show();
			} else {
				$(this).addClass('cbpgEditorHidden').next('div').hide();
			}
			$(this).click( function() {
				if ( $(this).hasClass('cbpgEditorHidden') ) {
					if ( $(this).attr('title') && ! confirm( $(this).attr('title') ) ) {
						return;
					}
					$(this).removeClass('cbpgEditorHidden').addClass('cbpgEditorVisible');
				} else {
					$(this).removeClass('cbpgEditorVisible').addClass('cbpgEditorHidden');
				}
				$(this).next('div').slideToggle('slow');
			});
		});
		return this;
	};
	// Doing our simplified function as unfortunately validate has an @ not-compatible with jQuery 1.3.1 in CB 1.2:
	$.fn.cbpgvalidate = function(options) {
		this.each(function() {
			$(this).submit( function() {
				var valid = true;
				$(this).children("input[class*='required']").each(function() {
					if ( $(this).val().length < 1 ) {
						valid = false;
						$(this).addClass('cbpgInvalid');
						$(this).next('label').remove();
						$(this).after( '<label class="cbpgInvalid" for="' + $(this).attr('id') + '">' + $("label[for='" + $(this).attr('id') + "']").attr('title') + '</label>' );
					} else {
						$(this).removeClass('cbpgInvalid');
						$(this).next('label').remove();
					}
				});
				if ( valid ) {
					$(this).children(':submit').attr('disabled', true).hide('normal', function() { $(this).next('img').show('normal'); } );
					$(this).children(':text,textarea,:file').attr('readonly',true);
				}
				return valid;
			});
		});
		return this;
	};
	$.fn.cbpgControlsHover = function(options) {
		this.each(function() {
			$(this).children(options).fadeTo(0, 0.01 );
			$(this).hover(
				function() {
					$(this).children(options).fadeTo(0, 1 );
				},
				function() {
					$(this).children(options).fadeTo('slow', 0.01 );
				}
			);
		});
		return this;
	};
})(jQuery);

jQuery(document).ready(function($){
	$('#pgadminForm').cbpgvalidate({
		errorClass: 'cbpgInvalid'
	});
	$('a.cbpgToggleEditor').cbpgtoggleEditor();
	$('.cbpgIbox').cbpgControlsHover('.cbpgControlArea');
});