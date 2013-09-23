<?php defined( '_JEXEC' ) or die; 

include_once JPATH_THEMES.'/'.$this->template.'/logic.php'; // load logic.php

?><!doctype html>
<!--[if IEMobile]><html class="iemobile" lang="<?php echo $this->language; ?>"> <![endif]-->
<!--[if IE 8]>    <html class="no-js ie8" lang="<?php echo $this->language; ?>"> <![endif]-->
<!--[if gt IE 8]><!-->  <html class="no-js" lang="<?php echo $this->language; ?>"> <!--<![endif]-->

<head>
<script src="http://code.jquery.com/jquery-1.10.2.min.js"></script>
<?php
	    $this->base = '';
?>
  <jdoc:include type="head" />
  <script src="templates/frontend/js/jquery.superLabels.min.js"></script>
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=0" />
  <link rel="apple-touch-icon-precomposed" href="<?php echo $tpath; ?>/images/apple-touch-icon-57x57-precomposed.png">
  <link rel="apple-touch-icon-precomposed" sizes="72x72" href="<?php echo $tpath; ?>/images/apple-touch-icon-72x72-precomposed.png">
  <link rel="apple-touch-icon-precomposed" sizes="114x114" href="<?php echo $tpath; ?>/images/apple-touch-icon-114x114-precomposed.png">
  <link rel="apple-touch-icon-precomposed" sizes="144x144" href="<?php echo $tpath; ?>/images/apple-touch-icon-144x144-precomposed.png">
  <!--[if lte IE 8]>
    <script src="http://html5shim.googlecode.com/svn/trunk/html5.js"></script>
    <?php if ($pie==1) : ?>
      <style> 
        {behavior:url(<?php echo $tpath; ?>/js/PIE.htc);}
      </style>
    <?php endif; ?>
  <![endif]-->
<script type="text/javascript">

  var _gaq = _gaq || [];
  _gaq.push(['_setAccount', 'UA-27023314-1']);
  _gaq.push(['_trackPageview']);

  (function() {
    var ga = document.createElement('script'); ga.type = 'text/javascript'; ga.async = true;
    ga.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';
    var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(ga, s);
  })();

</script>
</head>
  
<body class="<?php echo (($menu->getActive() == $menu->getDefault()) ? ('front') : ('page')).' '.$active->alias.' '.$pageclass; ?>">
<div id="fb-root"></div>
<script>
  window.fbAsyncInit = function() {
    // init the FB JS SDK
    FB.init({
      appId      : '654546621229488',                        // App ID from the app dashboard
      status     : true,                                 // Check Facebook Login status
      xfbml      : true                                  // Look for social plugins on the page
    });

    // Additional initialization code such as adding Event Listeners goes here
FB.Event.subscribe('edge.create', function(response) {
// do something with response.session
console.log('liked');
$( ".pluginButton" ).css( "border", "10px solid red" );
<?php
if(isset($_SESSION['__default']['cbfacebookconnect_facebook']['access_token'])) {
echo 'var is_logged = true;';
}else{
echo 'var is_logged = false;';
}

?>
if (is_logged) {
$("#contest-submit").removeAttr("disabled");
}
});
FB.Event.subscribe('edge.remove', function(response) {
// do something with response.session
console.log('unliked');
$( ".pluginButton" ).css( "border", "10px solid red" );
$("#contest-submit").attr("disabled", "disabled");
});
  };

  // Load the SDK asynchronously
  (function(d, s, id){
     var js, fjs = d.getElementsByTagName(s)[0];
     if (d.getElementById(id)) {return;}
     js = d.createElement(s); js.id = id;
     js.src = "//connect.facebook.net/en_US/all.js";
     fjs.parentNode.insertBefore(js, fjs);
   }(document, 'script', 'facebook-jssdk'));
</script>
	<div id="content-middle">
<div class="fb-like" data-href="https://www.facebook.com/worldwideinterweb/" data-width="50" data-layout="button_count" data-show-faces="false" data-send="false"></div>
<?php
if(isset($_SESSION['__default']['cbfacebookconnect_facebook']['access_token'])) {
$access_token = $_SESSION['__default']['cbfacebookconnect_facebook']['access_token'];
require_once("facebook-php-sdk/src/facebook.php");

$config = array();
$config['appId'] = '654546621229488';
$config['secret'] = '608f488e9112acfc42f1c45eaddf6298';

$facebook = new Facebook($config);
$facebook->setAccessToken($access_token);
$data = $facebook->api('me/likes/293181650699530');
}
?>
		<jdoc:include type="message" />
		<jdoc:include type="component" />
		<div class="clear"><!-- --></div>
  		<jdoc:include type="modules" name="simple-login" />
</div>
  <jdoc:include type="modules" name="debug" />
<?php if (!$data['data']) { ?>
<script>
$("#contest-submit").attr("disabled", "disabled");
</script>
<?php } ?>
</body>

</html>

