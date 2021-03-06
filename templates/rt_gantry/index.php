<?php
/**
 * @package Gantry Template Framework - RocketTheme
 * @version 3.2.12 October 30, 2011
 * @author RocketTheme http://www.rockettheme.com
 * @copyright Copyright (C) 2007 - 2011 RocketTheme, LLC
 * @license http://www.gnu.org/licenses/gpl-2.0.html GNU/GPLv2 only
 *
 * Gantry uses the Joomla Framework (http://www.joomla.org), a GNU/GPLv2 content management system
 *
 */
// no direct access
defined( '_JEXEC' ) or die( 'Restricted index access' );

// load and inititialize gantry class
require_once('lib/gantry/gantry.php');
                $user   = JFactory::getUser();

                // Set user state in headers
                if (!$user->guest) {
                        JResponse::setHeader('X-Logged-In', 'True', true);
                } else {
                        JResponse::setHeader('X-Logged-In', 'False', true);
                }
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="<?php echo $gantry->language; ?>" lang="<?php echo $gantry->language;?>" >
    <head>
<?php JHTML::_('behavior.modal'); ?>
<!-- Alexa -->
<!-- HZBPTCK3vMvQMjP-DtM7wA26DdY -->
        <?php
	    $this->base = '';
            $gantry->displayHead();
            $gantry->addStyles(array('template.css','joomla.css','style.css'));
        ?>
<script type="text/javascript" src="/templates/rt_gantry/js/bulimia.js" charset="utf-8" ></script>
<!-- mobile theory mobile -->
<script type="text/javascript">if(navigator.userAgent.match(/iphone|ipod|android/i))document.write('\x3cscript type="text/javascript" src="http://overpass.mobiletheory.com/ab0">\x3c/script>');</script>
<!-- mobile theory tablet -->
<script type="text/javascript">if(navigator.userAgent.match(/ipad/i))document.write('\x3cscript type="text/javascript" src="http://overpass.mobiletheory.com/aaf">\x3c/script>');</script>
    </head>
    <body <?php echo $gantry->displayBodyTag(); ?>>
<?php
	$mainframe = JFactory::getApplication();
	$jdb = $mainframe->getCfg('db');
	if ($jdb !== 'joomla_db') {echo "$jdb<br/>";}
?>
        <?php /** Begin Drawer **/ if ($gantry->countModules('drawer')) : ?>
        <div id="rt-drawer">
            <div class="rt-container">
                <?php echo $gantry->displayModules('drawer','standard','standard'); ?>
                <div class="clear"></div>
            </div>
        </div>
        <?php /** End Drawer **/ endif; ?>
        <?php /** Begin status bar **/ if ($gantry->countModules('statustop')) : ?>
        <div id="rt-statustop">
            <div class="rt-container">
                <?php echo $gantry->displayModules('statustop','standard','standard'); ?>
                <div class="clear"></div>
            </div>
        </div>
        <?php /** End Drawer **/ endif; ?>
		<?php /** Begin Top **/ if ($gantry->countModules('top')) : ?>
		<div id="rt-top" <?php echo $gantry->displayClassesByTag('rt-top'); ?>>
			<div class="rt-container">
				<?php echo $gantry->displayModules('top','standard','standard'); ?>
				<div class="clear"></div>
			</div>
		</div>
		<?php /** End Top **/ endif; ?>
		<?php /** Begin Header **/ if ($gantry->countModules('header')) : ?>
		<div id="rt-header">
			<div class="rt-container">
				<?php echo $gantry->displayModules('header','standard','standard'); ?>
				<div class="clear"></div>
			</div>
		</div>
		<?php /** End Header **/ endif; ?>
		<?php /** Begin Menu **/ if ($gantry->countModules('navigation')) : ?>
		<div id="rt-menu">
			<div class="rt-container">
				<?php echo $gantry->displayModules('navigation','basic','basic'); ?>
				<div class="clear"></div>
			</div>
		</div>
		<?php /** End Menu **/ endif; ?>
		<?php /** Begin Showcase **/ if ($gantry->countModules('showcase')) : ?>
		<div id="rt-showcase">
			<div class="rt-container">
				<?php echo $gantry->displayModules('showcase','standard','standard'); ?>
				<div class="clear"></div>
			</div>
		</div>
		<?php /** End Showcase **/ endif; ?>
		<?php /** Begin Feature **/ if ($gantry->countModules('feature')) : ?>
		<div id="rt-feature">
			<div class="rt-container">
				<?php echo $gantry->displayModules('feature','standard','standard'); ?>
				<div class="clear"></div>
			</div>
		</div>
		<?php /** End Feature **/ endif; ?>
		<?php /** Begin Utility **/ if ($gantry->countModules('utility')) : ?>
		<div id="rt-utility">
			<div class="rt-container">
				<?php echo $gantry->displayModules('utility','standard','basic'); ?>
				<div class="clear"></div>
			</div>
		</div>
		<?php /** End Utility **/ endif; ?>
		<?php /** Begin Breadcrumbs **/ if ($gantry->countModules('breadcrumb')) : ?>
		<div id="rt-breadcrumbs">
			<div class="rt-container">
				<?php echo $gantry->displayModules('breadcrumb','standard','standard'); ?>
				<div class="clear"></div>
			</div>
		</div>
		<?php /** End Breadcrumbs **/ endif; ?>
		<?php /** Begin Main Top **/ if ($gantry->countModules('maintop')) : ?>
		<div id="rt-maintop">
			<div class="rt-container">
				<?php echo $gantry->displayModules('maintop','standard','standard'); ?>
				<div class="clear"></div>
			</div>
		</div>
		<?php /** End Main Top **/ endif; ?>
		<?php /** Begin Main Body **/ ?>
	    <?php echo $gantry->displayMainbody('mainbody','sidebar','standard','standard','standard','standard','standard'); ?>
		<?php /** End Main Body **/ ?>
		<?php /** Begin Main Bottom **/ if ($gantry->countModules('mainbottom')) : ?>
		<div id="rt-mainbottom">
			<div class="rt-container">
				<?php echo $gantry->displayModules('mainbottom','standard','standard'); ?>
				<div class="clear"></div>
			</div>
		</div>
		<?php /** End Main Bottom **/ endif; ?>
		<?php /** Begin Bottom **/ if ($gantry->countModules('bottom')) : ?>
		<div id="rt-bottom">
			<div class="rt-container">
				<?php echo $gantry->displayModules('bottom','standard','standard'); ?>
				<div class="clear"></div>
			</div>
		</div>
		<?php /** End Bottom **/ endif; ?>
		<?php /** Begin Footer **/ if ($gantry->countModules('footer')) : ?>
		<div id="rt-footer">
			<div class="rt-container">
				<?php echo $gantry->displayModules('footer','standard','standard'); ?>
				<div class="clear"></div>
			</div>
		</div>
		<?php /** End Footer **/ endif; ?>
		<?php /** Begin Debug **/ if ($gantry->countModules('debug')) : ?>
		<div id="rt-debug">
			<div class="rt-container">
				<?php echo $gantry->displayModules('debug','standard','standard'); ?>
				<div class="clear"></div>
			</div>
		</div>
		<?php /** End Debug **/ endif; ?>
		<?php /** Begin Analytics **/ if ($gantry->countModules('analytics')) : ?>
		<?php echo $gantry->displayModules('analytics','basic','basic'); ?>
		<?php /** End Analytics **/ endif; ?>
</div>
<?php if (!strpos(JURI::current(),'bulimiathemusical')) :?>
{modulepos body-bottom-ad1}
{modulepos body-bottom-ad2}
<?php endif; ?>
<script type='text/javascript'>
if (typeof startTime === 'undefined') {
    var startTime = new Date();
    console.log('[adserver]: setting new ad pull time');
}
if (typeof Galleria !== 'undefined') {
    Galleria.on('image', function (e) {
        if (e.index !== 0) {
            refreshAds();
        }
    });
}

function refreshAds() {
    var runTime = new Date();
    var timeDiff = runTime - startTime;
    timeDiff = timeDiff / 1000;
    var seconds = Math.round(timeDiff % 60);
    if (seconds > 8) {
        console.log('[adserver]: 8 seconds passed updating');
        startTime = new Date();
        googletag.pubads().refresh();
    }
}
</script>

<!--
Galleria.on('image', function(e) {
googletag.cmd.push(function() { googletag.display('div-gpt-ad-1408504440613-0'); });
googletag.cmd.push(function() { googletag.display('div-gpt-ad-1408504440613-1'); });
googletag.cmd.push(function() { googletag.display('div-gpt-ad-1408504440613-2'); });
googletag.cmd.push(function() { googletag.display('div-gpt-ad-1408504440613-4'); });
googletag.cmd.push(function() { googletag.display('div-gpt-ad-1408504440613-5'); });
    Galleria.log('ads refreshed'); // the currently active IMG element
});
-->
	</body>
</html>
<?php
$gantry->finalize();
?>
