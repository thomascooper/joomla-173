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
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="<?php echo $gantry->language; ?>" lang="<?php echo $gantry->language;?>" >
    <head>
        <?php
            $gantry->displayHead();
            $gantry->addStyles(array('template.css','joomla.css','style.css'));
        ?>
    </head>
    <body <?php echo $gantry->displayBodyTag(); ?>>
        <?php /** Begin Drawer **/ if ($gantry->countModules('drawer')) : ?>
        <div id="rt-drawer">
            <div class="rt-container">
                <?php echo $gantry->displayModules('drawer','standard','standard'); ?>
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
	<script type='text/javascript'>
		var sPage = document.getElementById("k2-pagination");

		var sContent = document.getElementById("rt-content-bottom");

		var parentDiv = sContent.parentNode;

		parentDiv.insertBefore(sPage, sContent.nextSibling);
	</script>
<!-- MANUAL::Start CI slider -->
	<div style="background:url(http://worldwideinterweb.com/templates/rt_gantry/images/body/title_bg.gif);padding-right:0;position:fixed;right:0;bottom:33px;height:125px;width:290px;">
	<div style="color:#E3E1A3;font-family:helvetica,Arial,sans-serif;font-size:17px;font-weight:700;margin:5px;">AROUND THE WEB<a href="#" onclick="jQuery(this).parent().parent().remove();" style="display:inline-block;float:right; font-weight:normal; color:#fff; text-decoration:none;">[x]</a></div><script type='text/javascript'>
	var _CI = _CI || {};
	(function() {
	var script = document.createElement('script');
	ref = document.getElementsByTagName('script')[0];
	_CI.counter = (_CI.counter) ? _CI.counter + 1 : 1;
	document.write('<div id="_CI_widget_');
	document.write(_CI.counter+'"></div>');
	script.type = 'text/javascript';
	script.src = 'http://widget.crowdignite.com/widgets/28257?_ci_wid=_CI_widget_'+_CI.counter;
	script.async = true;
	ref.parentNode.insertBefore(script, ref);
	})(); </script>
	<style>
	#widget_table_28257{border-spacing:0!important;display:block;table-layout:fixed;background:#fff!important;margin:5px!important;}
	#widget_table_28257 tr{height:85px;width:290px;display:inline-block;float:left;margin:0;padding:0;}
	#widget_table_28257 tr td{height:75px;min-width:270px;width:270px;display:block;float:left;margin:0;padding:5px;text-align:left;}
	#widget_table_28257 tr td>br{display:none;}
	#widget_table_28257 tr td>a{float:left;margin-right:5px;}
	#widget_table_28257 tr td>div{margin:3px 0 0 5px!important;width:185px!important;clear:none!important;display:block;float:left;}
	#widget_table_28257 tr td>div a{display:inline;}
	#widget_table_28257 img{border:0 none;}
	#widget_table_28257 a{color:#441F01!important;font-family:tahoma,sans-serif;font-weight:700;text-decoration:none;}
	#widget_table_28257 a:hover{text-decoration:underline;}
	</style>
<!-- MANUAL::End CI slider -->
</div>
	</body>
</html>
<?php
$gantry->finalize();
?>
