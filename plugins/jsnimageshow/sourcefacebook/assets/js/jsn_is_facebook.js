/**
 * @author JoomlaShine.com Team
 * @copyright JoomlaShine.com
 * @link joomlashine.com
 * @package JSN ImageShow
 * @version $Id$
 * @license GNU/GPL v2 http://www.gnu.org/licenses/gpl-2.0.html
 */
var JSNISFacebook = {
	openAutoModalWindow:function(external_source_id)
	{
		(function($){
			pathArray	= document.location.href.split("?");
			link		= pathArray[0]+'?option=com_imageshow&controller=maintenance&type=editprofile&source_type=facebook&tmpl=component&caller=showlist&external_source_id='+external_source_id;
			var JSNISShowlistWindow = new $.JSNISUIWindow(link,{
				width: '400',
				height: '500',
				title: JSNISLang.translate('SHOWLIST_LOGIN'),
				scrollContent: true
			});
		})(jQuery);
	},
	loadSDK:function(){
		(function(d){
			var js, id = "facebook-jssdk", ref = d.getElementsByTagName("script")[0];
			if (d.getElementById(id)) {return;}
			js = d.createElement("script"); js.id = id; js.async = true;
			js.src = "//connect.facebook.net/en_US/all.js";
			ref.parentNode.insertBefore(js, ref);
		}(document));
	},
	initialize:function(appID){
		FB.init({
		  appId      : appID, // App ID
		  status     : true, // check login status
		  cookie     : true, // enable cookies to allow the server to access the session
		  xfbml      : true  // parse XFBML
		});
	},
	checkLogin:function(caller){
		FB.getLoginStatus(function(response) {
			document.getElementById('loadingConnect').style.display='none';
			facebookConnected = true;
			if (response.status !== 'connected') {
				if(caller!="newProfile"){
					document.getElementById('facebookWarningText').style.display='block';
				}
				document.getElementById('loginButton').style.display='block';
			}
		});
	},
	synchronize:function(caller, objISShowlist){
		FB.Event.subscribe('auth.statusChange', function(response) {
			if (response.status === 'connected') {
				FB.getLoginStatus(function (resp) {
			        if (resp.authResponse) {
			        	if(caller=="newProfile"){
			        		document.getElementById('loginFacebook').style.display='none';
			        	}
			            document.getElementById('access_token').value=resp.authResponse.accessToken;
			            FB.api('/me', function(resp1) {
			            	document.getElementById('facebook_userid').value=resp1.id;
			            	if(caller!="newProfile")
			            		document.getElementById('facebookWarning').style.display='none';
			            	facebookLogin = true;
			            	if(caller=="newProfile"){
			            		document.getElementById('task').value = 'createprofile';
			            		objISShowlist.submitProfileForm();
			            	}else if(caller=="showlist")
			            	{	
			            		//var form = document.adminForm;
			            		document.getElementById('frm-edit-source-profile').submit();
			            		window.top.setTimeout('window.parent.jQuery.closeAllJSNWindow(); window.top.location.reload(true)', 1000);
			            	}
						});
			        }
			    });
			}
	    });
	},
	notConnected:function(){
		if(!facebookConnected){
			document.getElementById('loadingConnect').style.display='none';
			document.getElementById('facebookNotMatch').style.display='block';
		}
	},
	checkExpiration:function(external_source_id,uid,accessToken){
		FB.getLoginStatus(function(response) {
			if (response.status === "connected") {
				if(uid != response.authResponse.userID && accessToken != response.authResponse.accessToken){
					JSNISFacebook.openAutoModalWindow(external_source_id);
				}
				else
				{
					var url = 'https://graph.facebook.com/debug_token?input_token=' + accessToken +'&access_token=' + accessToken;
					(function($) {
						
						$.ajax({
							type: 'GET',
							url: url,
							complete: function (jqXHR) {
								var result = JSON.parse(jqXHR.responseText);
								if (typeof result.error == 'object')
								{
									JSNISFacebook.openAutoModalWindow(external_source_id);
								}
							}
						});
					})(jQuery);
				}	
			}else {
				JSNISFacebook.openAutoModalWindow(external_source_id);
			}
		});
	}
};