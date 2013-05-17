<?php
/**
* Joomla Community Builder User Plugin: plug_cbprofilegallery
* @version $Id$
* @package plug_cbprofilegallery
* @subpackage install.profilegallery.php
* @author Nant, JoomlaJoe and Beat
* @copyright (C) Nant, JoomlaJoe and Beat, www.joomlapolis.com
* @license Limited http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU/GPL version 2
* @final 1.2.2 (compatible with CB 1.2)
*/
function plug_cb_profilegallery_install(){
	global $_CB_framework, $_CB_database;
	# Show installation result to user
  	?>
	<center>
	<table width="100%" border="0">
	<tr>
	<td>
	</td>
	</tr>
	<tr>
	<td>
	<br />CB Profile Gallery 1.2.1
    <br />Copyright 2006-2012 Nick A., MamboJoe/JoomlaJoe, Beat and CB team on joomlapolis.com.
    <br />This component is released under the GNU/GPL License. 
    <br />All copyright statements must be kept. 
    <br />Derivate work must prominently duly acknowledge original work and include visible online links. 
    <br />Official site: <a href="http://www.joomlapolis.com">www.joomlapolis.com</a>
	<br />
	</td>
	</tr>
	<tr>
	<td background="F0F0F0" colspan="2">
        <code>Installation Process:<br />
        <?php
 
 	cbimport( 'cb.adminfilesystem' );   
    $pgadminFS			=&	cbAdminFileSystem::getInstance();
    $cmsimagesPath		=	$_CB_framework->getCfg( 'absolute_path' ) . "/images";
	$cbImages			=	$cmsimagesPath . '/comprofiler';
	$pgImagesGallery	=	$cbImages . '/plug_profilegallery';

 	if ( $pgadminFS->isUsingStandardPHP() && ( ! $pgadminFS->file_exists( $pgImagesGallery ) ) && ! $pgadminFS->is_writable( $_CB_framework->getCfg( 'absolute_path' ) . "/images/comprofiler/" ) ) {
		print "<font color=red>". $cbImages . "/ is not! writable !</font><br />";
	} else {
		if (! $pgadminFS->file_exists( $pgImagesGallery ) ) {
			if ( $pgadminFS->mkdir( $pgImagesGallery ) ) {
				print "<font color=green>" . $pgImagesGallery . "/ Successfully added.</font><br />";
			} else {
				print "<font color=red>" . $pgImagesGallery . "/ Failed to be to be created, please do so manually !</font><br />";
			}	
		}  else {
			print "<font color=green>" . $pgImagesGallery . "/ already exists.</font><br />";
		}
	}

    $mode     = octdec( $_CB_framework->getCfg( 'dirperms' ) );
    if ( ! $mode ) {
        $mode     = 0755;
    }

	if ( $pgadminFS->file_exists( $pgImagesGallery ) ) {
		if( ! is_writable( $pgImagesGallery ) ) {
			if( ! $pgadminFS->chmod( $pgImagesGallery, $mode ) ) {
				if ( ! @chmod( $pgImagesGallery, $mode ) ) {
					print "<font color=red>" . $pgImagesGallery . "/ Failed to be chmod'd to 775 please do so manually !</font><br />";
				}
			}
		}
		if( ! is_writable( $pgImagesGallery ) ) {
			print "<font color=red>" . $pgImagesGallery . "/ is not writable and failed to be chmod'd to 775 please do so manually !</font><br />";
		}
 		$result	=	@copy( $_CB_framework->getCfg( 'absolute_path' ) . "/components/com_comprofiler/index.html", $_CB_framework->getCfg( 'absolute_path' ) . "/images/comprofiler/plug_profilegallery/index.html" );
		if ( ! $result ) {
			// otherwise try by FTP:
			$result	= $pgadminFS->copy($_CB_framework->getCfg( 'absolute_path' ). "/components/com_comprofiler/index.html" , $_CB_framework->getCfg( 'absolute_path' ) . "/images/comprofiler/plug_profilegallery/index.html");
		}
		if ( $result ) {
			print "<font color=green>index.html file successfully added to the profile gallery repository.</font><br />";
		} else {
			print "<font color=red>index.html failed to be added to the profile gallery repository at " . $_CB_framework->getCfg( 'absolute_path' ) . "/images/comprofiler/plug_profilegallery/" . " please do so manually !</font><br />";
		}
	}
	
    // query to calculate misaligned quotas for existing gallery table items
	$query = "SELECT user_id AS USER, cb_pgtotalitems as ITEMS, cb_pgtotalsize AS SIZE, "
        . "count(userid) AS CITEMS, SUM(pgitemsize) AS CSIZE "
        . "\n FROM #__comprofiler JOIN #__comprofiler_plug_profilegallery"
        . "\n ON user_id = userid"
        . "\n GROUP BY userid"
        . "\n HAVING (ITEMS <> CITEMS) OR (SIZE <> CSIZE) ";

    $_CB_database->setQuery($query);
    $cbusers=$_CB_database->loadObjectList();
    $cbuserscount=count($cbusers);

    //print_r($cbusers);
    //print($cbuserscount);
	$k = 0;
	if ($cbuserscount != 0) {    
        foreach ($cbusers as $cbuser) {                                  
            $cbu_userid = $cbuser->USER;
            $cbu_titems = $cbuser->ITEMS;
            $cbu_tsize = $cbuser->SIZE;
            $cbu_ctitems = $cbuser->CITEMS;
            $cbu_ctsize = $cbuser->CSIZE;

            // print  $cbu_userid . " , " . $cbu_titems . " , " . $cbu_tsize . " , " . $cbu_ctitems . " , " . $cbu_ctsize ; 
        
            if ( ($cbu_titems != $cbu_ctitems) || ($cbu_tsize != $cbu_ctsize) ) {
                $k++;
                cbu_adjust($cbu_userid, $cbu_ctitems, $cbu_ctsize);
                print "<br /><font color=green>Adjusted misaligned quotas for userid " . $cbu_userid 
                    . " ( " . $cbu_titems . "," . $cbu_tsize . " --> " . $cbu_ctitems . "," . $cbu_ctsize . " )</font>";
            }
    	}
    	print "<br /><font color=green>Adjusted total of " . $k . " user quotas for existing gallery items</font><br />";    
	}
 
    // query to calculate misaligned quotas for non existing gallery table items
    $query = "SELECT user_id AS USER, cb_pgtotalitems as ITEMS, cb_pgtotalsize AS SIZE "
        . "\n FROM #__comprofiler LEFT JOIN #__comprofiler_plug_profilegallery"
        . "\n ON user_id = userid"
        . "\n WHERE (cb_pgtotalitems <> 0 OR cb_pgtotalsize <> 0) AND pgitemsize IS NULL "
        . "\n GROUP BY userid";

    $_CB_database->setQuery($query);
    $cbusers=$_CB_database->loadObjectList();
    $cbuserscount=count($cbusers);

    //print_r($cbusers);
    //print($cbuserscount);
    $k = 0;
    if ($cbuserscount != 0) {    
        foreach ($cbusers as $cbuser) {                                  
            $cbu_userid = $cbuser->USER;
            $cbu_titems = $cbuser->ITEMS;
            $cbu_tsize = $cbuser->SIZE;
        
            $k++;
            cbu_adjust($cbu_userid, 0, 0);
            print "<br /><font color=green>Reset misaligned quotas for userid " . $cbu_userid 
                . " ( " . $cbu_titems . "," . $cbu_tsize . " -->  0,0 ) </font>";
        }
    }
    print "<br /><font color=green>Reset total of " . $k . " user quotas for non existing gallery items</font><br />";    
		
	?>
        <font color="green"><b>Installation finished.</b></font></code>
	</td>
	</tr>
	</table>
	</center>
	<?php
	return "";
}

function cbu_adjust($userid, $titems, $tsize) {
global $_CB_database;
    $html2return = "";
        
    $query    = "UPDATE #__comprofiler SET"
              . " cb_pgtotalitems=" . (int) $titems
              . ", cb_pgtotalsize=" . (int) $tsize
              . ", cb_pglastupdate=NOW() WHERE id=" . (int) $userid
              ;
    $_CB_database->setQuery($query);
    $_CB_database->query();
    //print $database->getQuery();
    return $html2return;
        
}

?>