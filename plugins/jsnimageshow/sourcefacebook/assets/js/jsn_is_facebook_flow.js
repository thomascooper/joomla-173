/**
 * @author JoomlaShine.com Team
 * @copyright JoomlaShine.com
 * @link joomlashine.com
 * @package JSN ImageShow
 * @version $Id$
 * @license GNU/GPL v2 http://www.gnu.org/licenses/gpl-2.0.html
 */
var JSNISFacebookFlow = {
	connectFacebook:function(appId,appSecret,title,imgSize,tmbSize, objISShowlist){
		//window.parent.document.getElementById('btn-save-showlist').disabled = true;
		//JSNISImageShow.toggleLoadingIcon('jsn-create-source', true);
		objISShowlist.toggleLoadingIcon('jsn-create-source', true);
		var iframe = window.parent.document.getElementsByTagName('iframe')[0];
		var current_url=iframe.src+'&f_appid='+appId+'&f_appsecret='+appSecret+'&f_title='+title+'&f_imgsize='+imgSize+'&f_tmbSize='+tmbSize;
		var ajax = new Request({
			url: 'index.php?option=com_imageshow&controller=maintenance&task=validateProfile&source=facebook&facebook_app_id='+appId+'&facebook_app_secret='+appSecret,
			method: 'get',
			onComplete: function(stringJSON)
			{
				//JSNISImageShow.toggleLoadingIcon('jsn-create-source', false);
				objISShowlist.toggleLoadingIcon('jsn-create-source', false);
				var data = JSON.decode(stringJSON);
				if (data.success == false) {
					//window.parent.document.getElementById('btn-save-showlist').disabled = false;
					alert(data.msg);
					return;
				}

				iframe.src = current_url;
			}
		});
		ajax.send();
	}
};