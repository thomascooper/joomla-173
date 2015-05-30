<?php
/**
 * @author     mediahof, Kiel-Germany
 * @link       http://www.mediahof.de
 * @copyright  Copyright (C) 2011 - 2014 mediahof. All rights reserved.
 * @license    GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

defined('_JEXEC') or die;
?>
<div id="mhap_<?php echo $module->id; ?>" class="mhap" style="<?php echo $mhap->css->popup; ?>">
    <?php if ($params->get('titleHide') != '1') { ?>
        <div class="popuptitle" style="<?php echo $mhap->css->title; ?>">
            <a id="mhap_<?php echo $module->id; ?>_cb" style="<?php echo $mhap->css->link; ?>"><?php echo $params->get('titleText'); ?></a>
        </div>
    <?php } ?>
    <div style="<?php echo $mhap->css->div; ?>">
        <?php echo $mhap->content; ?>
    </div>
    <script type="text/javascript">
        setTimeout(function () {
            mhap('mhap_<?php echo $module->id; ?>', <?php echo $params->get('fadeSpeed', 100) ?>);
        }, <?php echo $params->get('delay', 0) * 1000; ?>);
    </script>
</div>