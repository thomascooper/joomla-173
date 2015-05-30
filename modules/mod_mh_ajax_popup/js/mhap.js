/**
 * @author     mediahof, Kiel-Germany
 * @link       http://www.mediahof.de
 * @copyright  Copyright (C) 2011 - 2014 mediahof. All rights reserved.
 * @license    GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
function mhap(idStr, fadeSpeed) {
    var target = document.getElementById(idStr);
    if (document.getElementById(idStr + '_cb')) {
        document.getElementById(idStr + '_cb').onclick = function () {
            target.style.display = 'none';
        }
    }

    target.style.display = 'block';

    for (var count = 1; count <= 20; count++) {
        (function (trans) {
            setTimeout(function () {
                if (window.navigator.appName == 'Microsoft Internet Explorer') {
                    target.style.filter = 'alpha(opacity=' + trans + ')';
                } else {
                    target.style.opacity = (trans / 100);
                }
            }, (fadeSpeed * count));
        })(count * 5);
    }
}