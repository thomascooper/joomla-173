<?php
/**
* Joomla Community Builder User Plugin: plug_cbprofilegallery
* @version $Id$
* @package plug_cbprofilegallery
* @subpackage cb.profilegallery.php
* @author Nant, JoomlaJoe and Beat
* @copyright (C) 2004-2012 Nant, JoomlaJoe and Beat, www.joomlapolis.com
* @license Limited http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU/GPL version 2
* @final 1.2.2 (compatible with CB 1.2+)
*/

/** ensure this file is being included by a parent file */
if ( ! ( defined( '_VALID_CB' ) || defined( '_JEXEC' ) || defined( '_VALID_MOS' ) ) ) { die( 'Direct Access to this location is not allowed.' ); }

$_PLUGINS->registerFunction( 'onAfterDeleteUser', 'userDeleted','getProfileGalleryTab' );

/**
 * Management Tab still under construction
 *
 */
class getProfileGalleryManagementTab extends cbTabHandler {
	/**
	 * Construnctor
	 */
	function getProfileGalleryManagementTab() {
		$this->cbTabHandler();
        cbimport( 'language.cbteamplugins' );
    }
		
	/**
	 * Get all plugin, tab, and CB fields related with this application
	 * @access private
	 * @param object mosUser reflecting the user being displayed
	 */
	function _pgGetTabParameters($user){

		$params = $this->params;
		
		// Plugin Parameters
		$TabParams["pgmanagementtabenabled"] = $params->get('pgManagementTabEnabled', 0);
		$TabParams["pgmoderatornotification"] = $params->get('pgModeratorNotification', 1);
		
		return $TabParams;
	}
	
	/**
	* Generates the HTML to display the user profile tab
	* @param object tab reflecting the tab database entry
	* @param object mosUser reflecting the user being displayed
	* @param int 1 for front-end, 2 for back-end
	* @returns mixed : either string HTML for tab content, or false if ErrorMSG generated
	*/
	function getDisplayTab($tab,$user,$ui) {
		global $_CB_framework;
		
		$tabparams=$this->_pgGetTabParameters($user);

		$htmltext1 = "";
		if (!$tabparams["pgmanagementtabenabled"]) return $htmltext1;
		// Is user a moderator?
		$isModerator=isModerator($_CB_framework->myId());
		if (!$isModerator) return $htmltext1;
		if($tab->description != null) {
			$htmltext1 .= "\t\t<div class=\"tab_Description\">";
			$htmltext1 .= cbUnHtmlspecialchars(getLangDefinition($tab->description));
			$htmltext1 .= "</div>\n";
			$htmltext1 .= "<br />\n";
			$htmltext1 .= "Feature not available yet\n";
			$htmltext1 .= "<br />\n";
		}
		return $htmltext1;
	}
}

/**
 * Profile Gallery Tab
 *
 */
class getProfileGalleryTab extends cbTabHandler {
	/**
	* Constructor
	*/
	function getProfileGalleryTab() {
		$this->cbTabHandler();
        cbimport( 'language.cbteamplugins' );
	}
		
	/**
	 * This function returnd an array of parameter and field settings (values)
	 * used though-out the gallery plugin.
	 * @access private
	 * @param object mosUser reflecting the user being displayed
	 */
	function _pgGetTabParameters($user){

		$params = $this->params;
		
		// Plugin Parameters
		$TabParams["pgmanagementtabenabled"] = $params->get('pgManagementTabEnabled', 1);
		$TabParams["pgmoderatornotification"] = $params->get('pgModeratorNotification', 1);
		
		// Tab Parameters
        $TabParams["pgallowuserenable"] = $params->get('pgAllowUserEnable', 1);
		$TabParams["pggalleryautoenabled"] = $params->get('pgGalleryAutoEnabled', 1);
		$TabParams["pgpagingenabled"] = $params->get('pgPagingEnabled', 1);
		$TabParams["pgopmode"] = $params->get('pgOpMode','IMAGEMODE');
		$TabParams["pgimagefiletypelist"] = $params->get('pgImageFileTypeList', 'jpg,gif,png');
		$TabParams["pgotherfiletypelist"] = $params->get('pgOtherFileTypeList', 'zip,doc,pdf,txt');
		$TabParams["pgsortoption"] = $params->get('pgSortOption','DATEDESC');
		$TabParams["pgaccessmode"] = $params->get('pgAccessMode','REG');
		$TabParams["pgallowaccessmodeoverride"] = $params->get('pgAllowAccessModeOverride',1);
		$TabParams["pgallowmoderatorfrontenduploads"] = $params->get('pgAllowModeratorFrontEndUploads',1);
		$TabParams["pgdisplayformat"] = $params->get('pgDisplayFormat','DF1');
		$TabParams["pgdisplayformatparameters"] = $params->get('pgDisplayFormatParameters','10,...,100,0,70,10');
		
		if (substr_count($TabParams["pgdisplayformatparameters"],',')==5) {
			$TabParams["cbpgdisplayformatparameters"] = $TabParams["pgdisplayformatparameters"];
		} else {
			$TabParams["cbpgdisplayformatparameters"] = "10,...,100,0,70,10";
		}
		
		$TabParams["pgallowdisplayformatoverride"] = $params->get('pgAllowDisplayFormatOverride', 1);
		$TabParams["pgautopublish"] = $params->get('pgAutoPublish', 1);
		$TabParams["pgallowautopublishoverride"] = $params->get('pgAllowAutoPublishOverride', 0);
		$TabParams["pgautoapprove"] = $params->get('pgAutoApprove', 1);
		$TabParams["pgallowautoapproveoverride"] = $params->get('pgAllowAutoApproveOverride', 1);
		$TabParams["pgbuttonicons"] = $params->get('pgButtonIcons',0);
		$TabParams["pgiconlist"] = $params->get('pgIconList','delete.gif,unpublish.gif,publish.gif,approve.gif,revoke.gif,edit.gif');
		$TabParams["pgentriesperpage"] = $params->get('pgEntriesPerPage','5');
		$TabParams["pgmaxwidth"] = $params->get('pgMaxWidth', '500');
		$TabParams["pgmaxheight"] = $params->get('pgMaxHeight', '500');
		$TabParams["pgmaxsize"] = $params->get('pgMaxSize', '250');
		$TabParams["pgtnmaxwidth"] = $params->get('pgTNMaxWidth', '150');
		$TabParams["pgtnmaxheight"] = $params->get('pgTNMaxHeight', '150');
		$TabParams["pgnumberofgalleryitems"] = (is_numeric($params->get('pgNumberOfGalleryItems', '10'))) ? $params->get('pgNumberOfGalleryItems', '10') : 10;
		$TabParams["pgdefaultquota"] = $params->get('pgDefaultQuota', '1024');
		
		$TabParams["pgGalleryManagementEnabled"] = $params->get('pgGalleryManagementEnabled', 0);
		
		if ($TabParams["pgbuttonicons"]) {
			if (substr_count($TabParams["pgiconlist"],',')==5) { // There must be 5 items in the input icon list
				$TabParams["cbpgbuttonslist"] = $TabParams["pgiconlist"];
			} else {
				$TabParams["cbpgbuttonslist"] = "delete_f2.png,publish_f2.png,unpublish_f2.png,archive_f2.png,unarchive_f2.png,edit_f2.png";
			}
		} else {
			$TabParams["cbpgbuttonslist"] = "delete_f2.png,publish_f2.png,unpublish_f2.png,archive_f2.png,unarchive_f2.png,edit_f2.png";
		}

		// Get User Level Configuration Options (relevant CB fields)
			
		if (is_numeric($user->cb_pgautopublish)) {
			$TabParams["cbpgautopublish"] = ($user->cb_pgautopublish == '_UE_YES') ? 1 : 0;
		} else {
			$TabParams["cbpgautopublish"] = $TabParams["pgautopublish"];
		}
        
		if (is_numeric($user->cb_pgautoapprove)) {
			$TabParams["cbpgautoapprove"] = ($user->cb_pgautoapprove == '_UE_YES') ? 1 : 0;
		} else {
			$TabParams["cbpgautoapprove"] = $TabParams["pgautoapprove"];
		}
        
		$TabParams["cbpguploadsize"] = (isset($user->cb_pguploadsize) && is_numeric($user->cb_pguploadsize) && ($user->cb_pguploadsize != 0)) ? $user->cb_pguploadsize : $TabParams["pgmaxsize"];

		$TabParams["cbpgtotalquotasize"] = (isset($user->cb_pgtotalquotasize) && is_numeric($user->cb_pgtotalquotasize) && ($user->cb_pgtotalquotasize != 0)) ? $user->cb_pgtotalquotasize : $TabParams["pgdefaultquota"];
		$TabParams["cbpgtotalitems"] = (isset($user->cb_pgtotalitems) && is_numeric($user->cb_pgtotalitems)) ? $user->cb_pgtotalitems : 0;
		$TabParams["cbpgtotalsize"] = (isset($user->cb_pgtotalsize) && is_numeric($user->cb_pgtotalsize)) ? $user->cb_pgtotalsize : 0;
	
		if ( isset($user->cb_pgenable) && (!( is_null($user->cb_pgenable) || $user->cb_pgenable === '' )) ) {
            $TabParams["cbpgenable"] = ($user->cb_pgenable == '_UE_YES') ? 1 : 0;
        } else {
            $TabParams["cbpgenable"] = $TabParams["pggalleryautoenabled"];    
        }
        
		//if ($TabParams["pggalleryautoenabled"]) {
		//	$TabParams["cbpgenable"] = 1;
		//} else {
		//	$TabParams["cbpgenable"] = ($user->cb_pgenable == '_UE_YES') ? 1 : 0;
		//}
	
		$TabParams["cbpglastupdate"] = $user->cb_pglastupdate;
		$TabParams["cbpgtotalquotaitems"] = (isset($user->cb_pgtotalquotaitems) && is_numeric($user->cb_pgtotalquotaitems) && ($user->cb_pgtotalquotaitems != 0) ) ? $user->cb_pgtotalquotaitems : $TabParams["pgnumberofgalleryitems"];
		$TabParams["cbpgshortgreeting"] = $user->cb_pgshortgreeting;
	
		
		if ( isset($user->cb_pgaccessmode) && (!( is_null($user->cb_pgaccessmode) || $user->cb_pgaccessmode === '' )) ) {
			$TabParams["cbpgaccessmode"] = $user->cb_pgaccessmode;
		} else {
			$TabParams["cbpgaccessmode"] = $TabParams["pgaccessmode"];
		}
		
		if (strlen($user->cb_pgdisplayformat)>0) {
			$TabParams["cbpgdisplayformat"] = $user->cb_pgdisplayformat;
		} else {
			$TabParams["cbpgdisplayformat"] = $TabParams["pgdisplayformat"];
		}

        $TabParams['DF1']  =  CBTxt::T("Pictures gallery list format");
        $TabParams['DF2']  =  CBTxt::T("File list format");
        $TabParams['DF3']  =  CBTxt::T("Picture gallery list lightbox format");

        $TabParams['PUB'] = CBTxt::T("Allow Public Access");
        $TabParams['REG'] = CBTxt::T("Allow Registered Access");
        $TabParams['CON'] = CBTxt::T("Allow Connections Access");
        $TabParams['REG-S'] = CBTxt::T("Registered Stealth Access");
        $TabParams['CON-S'] = CBTxt::T("Connections Stealth Access");
        
        
		return $TabParams;
	}

    /**
    * Display Gallery logo in plugin parameters area 
    */
    function _show_pg_logo($name,$value,$control_name) {
        $htmlpglogo = '<img src="../components/com_comprofiler/plugin/user/plug_cbprofilegallery/images/cbprofilegallerylogo.jpg" alt="logo" /><br />';
        return $htmlpglogo;
    }

    /**
    * This function gets the last item id uploaded for a specific userid.
    * This value is passed as a hidden input box in the submission form and compared
    * with a recalculated value to check for form resubmission case via user doing a browser reload.
    * 
    * @param int $userid                                                    
    */
    function _pgLastItemId($userid){
        global $_CB_database;
        
        $query="SELECT id"
            . "\n FROM #__comprofiler_plug_profilegallery"
            . "\n WHERE userid=" . (int) $userid
            . "\n ORDER BY id DESC"
            ;

        $_CB_database->setQuery($query, 0, 1 );
        $result = $_CB_database->loadResult();
        return ($result == NULL) ? 0 : $result;   
    }
	
	/**
	 * This function is called when a profile owner sumbits a new gallery item
	 * It should perform all validations and upload the file to the correct storage area and
	 * then call the _pgSave() function
	 * $this->_pgProcessNewItem($user->id,$pgautopublish,$pgtotalitems,$pgtotalsize);
	 * 
	 * @access private
	 * @param int $id gallery item id
	 * @param object mosUser reflecting the user being displayed
	 */
	function _pgProcessNewItem($id,&$user){
		global $ueConfig,$_CB_framework;
		$PGItemAbsolutePath=$_CB_framework->GetCfg( 'absolute_path' ).'/images/comprofiler/plug_profilegallery/';
		$html2return = "";

		$userid = $user->id;
		// Get all relevant tab parameters and user settings
		
		$tabparams = $this->_pgGetTabParameters($user);
				
		$pgitemfilename = $this->_getPagingParamName("pgitemfilename");
		$pgitemtitle = $_POST[$this->_getPagingParamName("pgitemtitle")];
		$pgitemdescription = $_POST[$this->_getPagingParamName("pgitemdescription")];
		$pgitemorder = "999"; // default setting for new feature
		
		$upload_pgitem_name = $_FILES[$pgitemfilename]['name'];
		$upload_pgitem_tmpname = $_FILES[$pgitemfilename]["tmp_name"];
		$upload_pgitem_size = $_FILES[$pgitemfilename]["size"];
		
		$upload_pgitem_nameparts = explode(".",$upload_pgitem_name);
		$upload_pgitem_ext = $upload_pgitem_nameparts[count($upload_pgitem_nameparts)-1];
		$upload_pgitem_baseparts = explode("." . $upload_pgitem_ext,$upload_pgitem_name); 
		$upload_pgitem_base = $upload_pgitem_baseparts[0];
		
		$upload_pgitem_base = str_replace(" ", "_", $upload_pgitem_base);
		
		$pgitemtype = $upload_pgitem_ext = strtolower($upload_pgitem_ext);

        // Get last item id for this user
        $lastitemid = $this->_pgLastItemId($userid);
        if ($lastitemid != $_POST[$this->_getPagingParamName("pglastitemid")]) { // reload catch
            @unlink($upload_pgitem_tmpname);
            $html2return .= '<font color="red">' . CBTxt::Th("No item uploaded!") . "</font><br />";
            return $html2return;       
        }
		// Check for valid upload!
		if (filesize($upload_pgitem_tmpname) == 0){
			$html2return .= '<font color="red">' . CBTxt::Th("No item uploaded!") . "</font><br />";
			return $html2return;
		}
		

		list(/*$in_width*/, /*$in_height*/, $in_type, /*$in_attr*/) = getimagesize($upload_pgitem_tmpname);
		
		// First check size of uploaded item and stop right away if size has exceeded
		// maximum allowable
		if ($upload_pgitem_size > $tabparams["cbpguploadsize"] * 1024) {
			$html2return .= '<font color="red">' . CBTxt::Th("Maximum allowable single upload size exceeded - gallery item rejected") . "</font><br />";
			return $html2return;
		}
		
		// Generate random base name for upload
		$random_upload_pgitem_base = "pg_" . mt_rand();
		
		// Check file extension type
		$inimagelist = in_array($upload_pgitem_ext,explode(",",$tabparams["pgimagefiletypelist"]));
		$infilelist = in_array($upload_pgitem_ext,explode(",",$tabparams["pgotherfiletypelist"]));
		
		
		$consider_imgToolBox = 0;
		
		switch ($tabparams["pgopmode"]) {
			case 'IMAGEMODE': 
				if ( !$inimagelist) {
					$html2return .= '<font color="red">' . CBTxt::Th("File extension not authorized") . "</font><br />";
					return $html2return;
				}
				$consider_imgToolBox = 1;
				break;
			case 'FILEMODE' :
				if ( !$infilelist) {
					$html2return .= '<font color="red">' . CBTxt::Th("File extension not authorized") . "</font><br />";
					return $html2return;
				}
				$consider_imgToolBox = 0;
				break;
			case 'MIXEDMODE' :
				if ( !$infilelist && !$inimagelist) {
					$html2return .= '<font color="red">' . CBTxt::Th("File extension not authorized") . "</font><br />";
					return $html2return;
				} 
				if (!$infilelist) {
					$consider_imgToolBox = 1;
				}
				break;
			default :
				$consider_imgToolBox = 0;
				break;
		}
		
		
		
		// Categorize uploaded item based on type attribute
		$imgToolBox_needed_typecheck = 0;
		if ($consider_imgToolBox) {
			switch ($in_type) {
				case 1: // GIF
				case 2: // JPG
				case 3: // PNG
					$imgToolBox_needed_typecheck = 1;
					break;
				default: // Other TYPES
					$imgToolBox_needed_typecheck = 0;
					if ($pgitemtype == "gif" || $pgitemtype == "jpg" || $pgitemtype == "png") {
						// trying to upload non image as image extension
						$html2return .= '<font color="red">' . CBTxt::Th("Bad File - Item rejected") . "</font><br />";
						return $html2return;
					}
					break;
			}
		}
		
		// determine if user storage repository has been created (from some previous upload)
		// if not create it now and give it proper permissions!
         $mode     = octdec( $_CB_framework->getCfg( 'dirperms' ) );
         if ( ! $mode ) {
            $mode     = 0755;
         }
		$PGItemAbsoluteUserPath = $PGItemAbsolutePath . $userid . "/";
		if(!file_exists($PGItemAbsoluteUserPath)){
			if(mkdir($PGItemAbsolutePath . $userid,$mode)) {
				chmod( $PGItemAbsolutePath . $userid , $mode);
				IF(copy($PGItemAbsolutePath . "index.html", $PGItemAbsoluteUserPath . "index.html")) {
				// Success action or message would go here
				} ELSE {
					//print '<font color="red">' . CBTxt::Th("Failed to be add index.html to the plugin gallery - please contact administrator!") . "</font><br />";
				    $this->_setError(CBTxt::Th("Failed to be add index.html to the plugin gallery - please contact administrator!"));
                }
				$html2return .= "<font color=green>" . CBTxt::Th("Gallery repository successfully created!") . "</font><br />";
			} else {
				$html2return .= '<font color="red">' . CBTxt::Th("Gallery repository could not be created! Please notify system admin!") . "</font><br />";
				return $html2return;
			}
		}

		$final_uploaded_fullfilename = $PGItemAbsoluteUserPath . $random_upload_pgitem_base . "." . $upload_pgitem_ext;
		$final_uploaded_tn_fullfilename = $PGItemAbsoluteUserPath . "tn" . $random_upload_pgitem_base . "." . $upload_pgitem_ext;
		$final_uploaded_filename = $random_upload_pgitem_base . "." . $upload_pgitem_ext;
		
		// Check to see if filename is unique and make it unique if not
		$unique_suffix = 1;
		$new_upload_pgitem_base = $random_upload_pgitem_base;
		
		while (file_exists($final_uploaded_fullfilename)) {
			$new_upload_pgitem_base = $random_upload_pgitem_base . "_" . $unique_suffix++;
			$final_uploaded_fullfilename = $PGItemAbsoluteUserPath . $new_upload_pgitem_base . "." . $upload_pgitem_ext;
			$final_uploaded_tn_fullfilename = $PGItemAbsoluteUserPath . "tn" . $new_upload_pgitem_base . "." . $upload_pgitem_ext;
		}
		$final_uploaded_filename = $new_upload_pgitem_base . "." . $upload_pgitem_ext;
		
		if ($imgToolBox_needed_typecheck) {	
			$imgToolBox = new imgToolBox();
			$imgToolBox->_conversiontype=$ueConfig['conversiontype'];
			$imgToolBox->_IM_path = $ueConfig['im_path'];
			$imgToolBox->_NETPBM_path = $ueConfig['netpbm_path'];
			$imgToolBox->_maxsize = $tabparams["cbpguploadsize"];
			$imgToolBox->_maxwidth = $tabparams["pgmaxwidth"];
			$imgToolBox->_maxheight = $tabparams["pgmaxheight"];
			$imgToolBox->_thumbwidth = $tabparams["pgtnmaxwidth"]; 
			$imgToolBox->_thumbheight = $tabparams["pgtnmaxheight"];
			$imgToolBox->_debug = 0;
			if ( ! ( $imgToolBox->processImage($_FILES[$this->_getPagingParamName("pgitemfilename")],$new_upload_pgitem_base,$PGItemAbsoluteUserPath, 0, 0, 1 ) ) ) {
				$html2return .= '<font color="red">' . CBTxt::Th("Image ToolBox failure! - Please notify system admin - ") . $imgToolBox->_errMSG . "</font><br />";
	    		return $html2return;
	 		} 
		} else {
	 		if (!move_uploaded_file($upload_pgitem_tmpname,$final_uploaded_fullfilename)) {
	 			$html2return .= '<font color="red">' . CBTxt::Th("The file upload has failed! - Please notify your system admin!") . "</font><br />";
				return $html2return;
			}
		}
		chmod($final_uploaded_fullfilename, 0755);
	 	$pgitemsize = filesize($final_uploaded_fullfilename);
	 	if ($tabparams["cbpgtotalsize"] + $pgitemsize > $tabparams["cbpgtotalquotasize"] * 1024) {
	 		$html2return .= '<font color="red">' . CBTxt::Th("This file would cause you to exceed you quota - gallery item rejected") . "</font><br />";
	 		@unlink($final_uploaded_fullfilename);
	 		if (file_exists($final_uploaded_tn_fullfilename)) @unlink($final_uploaded_tn_fullfilename);
			return $html2return;
	 	}
	 	$new_cbpgtotalsize = $tabparams["cbpgtotalsize"] + $pgitemsize;
	 	$new_cbpgtotalitems = $tabparams["cbpgtotalitems"] + 1;
	 	
	 	// if we get here it means that we have validated the new entry
	 	// and should finally save it to the database
        
	 	$this->_pgSave($id,$pgitemorder,$pgitemtype,$final_uploaded_filename,$pgitemsize,$pgitemtitle,$pgitemdescription,$user,$new_cbpgtotalitems,$new_cbpgtotalsize,$tabparams["cbpgautopublish"],$tabparams["cbpgautoapprove"],$tabparams["pgmoderatornotification"]);
	 	$successmessage = "";
	 	if (!$imgToolBox_needed_typecheck) {
	 		$successmessage = "<font color=green>" . sprintf(CBTxt::Th('The file %1$s has been successfully uploaded!'),$final_uploaded_filename) . "</font><br />";
	 	} else {
	 		$successmessage .= "<font color=green>" . sprintf(CBTxt::Th('The file %1$s has been successfully uploaded and tn%1$s thumbnail created!'),$final_uploaded_filename, $successmessage) . "</font><br />";
	 	}
	 	if (!$tabparams["cbpgautoapprove"]) {
	 		$successmessage .= "<br />" . "<font color=green>" . CBTxt::Th("Your Gallery item is pending approval by a site moderator.") . "</font><br />";
	 	}
	 	return $successmessage;
	}
	
	function _pgSave($id,$pgitemorder,$pgitemtype,$pgitemfilename,$pgitemsize,$pgitemtitle,$pgitemdescription,&$user,$cbpgtotalitems,$cbpgtotalsize,$pgitempublished,$pgitemapproved,$moderatornotify) {
		global $_CB_framework, $_CB_database, $Itemid;

		$html2return = "";
		
		$query = "INSERT INTO #__comprofiler_plug_profilegallery SET "
			. "userid="					. (int) $user->id
			. ", pgitemorder="			. (int) $pgitemorder
			. ", pgitemtype="			. $_CB_database->Quote( stripslashes( $pgitemtype ) )
			. ", pgitemfilename="		. $_CB_database->Quote( stripslashes( $pgitemfilename ) )
			. ", pgitemtitle="			. $_CB_database->Quote( stripslashes( $pgitemtitle ) )
			. ", pgitemdescription="	. $_CB_database->Quote( stripslashes( $pgitemdescription ) )
			. ", pgitemdate=now()"
			. ", pgitemsize="			. (int) $pgitemsize
			. ", pgitempublished="		. (int) $pgitempublished
			. ", pgitemapproved="		. (int) $pgitemapproved
			;
		$_CB_database->setQuery( $query );
		$_CB_database->query();
		
		// Notification
		$isModerator=isModerator($_CB_framework->myId());
		if($moderatornotify && !$isModerator){
			if (is_numeric($Itemid)) $andItemid = "&Itemid=".$Itemid;
				else $andItemid = "";
			
			$profile_url = $_CB_framework->getCfg( 'live_site' ) . "/index.php?option=com_comprofiler&task=userProfile&user=".$user->id.$andItemid;
			$notificationmessage =  sprintf(CBTxt::Th("A new Gallery item has just been uploaded and may require approval.\n"
                                    ."This email contains the item details\n\n"
                                    ."Gallery Item Type - %1\$s\n"
                                    ."Gallery Item Title - %2\$s\n"
                                    ."Gallery Item Description - %3\$s\n\n"
                                    ."Username - %4\$s\n"
                                    ."Profile Link - %5\$s \n\n\n"
                                    ."Please do not respond to this message as it is automatically generated and is for information purposes only\n"
                                    ),$pgitemtype,$pgitemtitle,$pgitemdescription,$user->username,$profile_url);
			//eval ("\$notificationmessage = \"$notificationmessage\";");
			
			$cbNotification = new cbNotification();
			if (!$cbNotification->sendToModerators(CBTxt::Th("New Gallery Item just uploaded"),$notificationmessage)) {
                $this->_setErrorMSG("CB Gallery failed to send moderation email");    
            }
		}
		
		$html2return .= $this->pgCBUpdate($user,$cbpgtotalitems,$cbpgtotalsize);
		return $html2return;
		
	}
	
	function pgNotify($id,$user,$notification_action) {
		global $_CB_framework, $res_1, $res_2, $res_3;
        
        $res_1 = $res_2 = $res_3 = true;
		
		switch ($notification_action) {
			case 'MODERATOR-APPROVE':
				// Notify end user about approval
				$tabparams = $this->_pgGetTabParameters($user);
				if($tabparams["pgmoderatornotification"] && ($_CB_framework->myId() != $user->id)){
					$cbNotification = new cbNotification();
					$messagebody =  CBTxt::Th("A Gallery item in your Gallery Tab has just been approved by a moderator.\n\n\n"
                                    ."Please do not respond to this message as it is automatically generated and is for information purposes only\n"
                                    );
					//eval ("\$messagebody = \"$messagebody\";");
					if (!$cbNotification->sendFromSystem($user->id,CBTxt::Th("Your Gallery Item has been approved!"),$messagebody)) {
                        $this->_setErrorMSG("CB Gallery failed to send user email approval notification");
                        $res_1 = false;                                
                    }
				}
				break;
			case 'MODERATOR-DELETE':
				// Notify end-user about deletion
				$tabparams = $this->_pgGetTabParameters($user);
				if($tabparams["pgmoderatornotification"] && ($_CB_framework->myId() != $user->id)){
					$cbNotification = new cbNotification();
					$messagebody =  CBTxt::Th("A Gallery item in your Gallery Tab has just been deleted by a moderator.\n\n\n"
                                    ."If you feel that this action is unjustified please contact one of our moderators.\n"
                                    ."Please do not respond to this message as it is automatically generated and is for information purposes only\n"
                                    );
					//eval ("\$messagebody = \"$messagebody\";");
					if (!$cbNotification->sendFromSystem($user->id,CBTxt::Th("Your Gallery Item has been deleted!"),$messagebody)) {
                        $this->_setErrorMSG("CB Gallery failed to send moderation deletion email");
                        $res_2 = false;    
                    }
				}
				break;
			case 'MODERATOR-REVOKE':
				// Notify end-user about item revoke
				$tabparams = $this->_pgGetTabParameters($user);
				if($tabparams["pgmoderatornotification"] && ($_CB_framework->myId() != $user->id)){
					$cbNotification = new cbNotification();
					$messagebody =  CBTxt::Th("A Gallery item in your Gallery Tab has just been revoked by a moderator.\n\n\n"
                                    ."If you feel that this action is unjustified please contact one of our moderators.\n"
                                    ."Please do not respond to this message as it is automatically generated and is for information purposes only\n"
                                    );
					//eval ("\$messagebody = \"$messagebody\";");
					if (!$cbNotification->sendFromSystem($user->id,CBTxt::Th("Your Gallery Item has been revoked!"),$messagebody)) {
                        $this->_setErrorMSG("CB Gallery failed to send moderation revocation email");
                        $res_3 = false;
                    }
				}
				break;
			default:
				break;
		}
        if (!($res_1 && $res_2 && $res_3)) {
            $this->raiseError(0);
        }
		return $res_1 && $res_2 && $res_3;
	}
	
	
	/**
	 * This function publishes a gallery item that was previously unpublished
	 *
	 * @param unknown_type $id
	 * @return unknown
	 */
	function pgPublish($id) {
		global $_CB_database;
		$html2return = "";
		
		$query = "UPDATE #__comprofiler_plug_profilegallery SET  pgitempublished=1, pgitemdate=NOW() WHERE id=" . (int) $id;
		$_CB_database->setQuery($query);
		$_CB_database->query();
		return $html2return;
		
	}
	
	/**
	 * This function unpublishes a gallery item that was previously published
	 *
	 * @param unknown_type $id
	 * @return unknown
	 */
	function pgUnPublish($id) {
		global $_CB_database;
		$html2return = "";
		
		$query = "UPDATE #__comprofiler_plug_profilegallery SET  pgitempublished=0, pgitemdate=NOW() WHERE id=" . (int) $id;
		$_CB_database->setQuery($query);
		$_CB_database->query();
		//print $_CB_database->getQuery();
		return $html2return;
		
	}
	
	/**
	 * This function approves a gallery item that was previously revoked
	 *
	 * @param unknown_type $id
	 * @param unknown_type $user
	 * @return unknown
	 */
	function pgApprove($id,$user) {
		global $_CB_database;
		$html2return = "";
		
		$query = "UPDATE #__comprofiler_plug_profilegallery SET  pgitemapproved=1, pgitemdate=NOW() WHERE id=" . (int) $id;
		$_CB_database->setQuery($query);
		$_CB_database->query();
		
		// Notificy end user about approval
		$this->pgNotify($id,$user,"MODERATOR-APPROVE");
		return $html2return;
		
	}
	
	/**
	 * This function revokes a gallery item that was previously approved
	 *
	 * @param unknown_type $id
	 * @return unknown
	 */
	function pgRevoke($id,$user) {
		global $_CB_database;
		$html2return = "";
		
		$query = "UPDATE #__comprofiler_plug_profilegallery SET  pgitemapproved=0, pgitemdate=NOW() WHERE id=" . (int) $id;
		$_CB_database->setQuery($query);
		$_CB_database->query();
		//print $_CB_database->getQuery();
		// Add return code message here
		
		$this->pgNotify($id,$user,"MODERATOR-REVOKE");
		
		return $html2return;
		
	}
	
	/**
	 * This function updates the running total fields stored in the users profile
	 *
	 * @param unknown_type $id
	 */
	function pgCBUpdate(&$user,$cbpgtotalitems,$cbpgtotalsize) {
		global $_CB_database;
		$html2return = "";
		
		$query	=	"UPDATE #__comprofiler SET"
				. " cb_pgtotalitems=" . (int) $cbpgtotalitems
				. ", cb_pgtotalsize=" . (int) $cbpgtotalsize
				. ", cb_pglastupdate=NOW() WHERE id=" . (int) $user->id
				;
		$_CB_database->setQuery($query);
		$_CB_database->query();
		//print $database->getQuery();
		$user->cb_pgtotalsize = $cbpgtotalsize;
		$user->cb_pgtotalitems = $cbpgtotalitems;
		return $html2return;
		
	}
	
	/**
	* This function updates the title and description of a gallery item 
	*/
	function pgUpdate($id,$updatetitle,$updatedescription) {
		global $_CB_database;
		$html2return = "";
		
		$query	=	"UPDATE #__comprofiler_plug_profilegallery SET"
				.	" pgitemtitle=" . $_CB_database->Quote( stripslashes( $updatetitle ) )
				.	", pgitemdescription=" . $_CB_database->Quote( stripslashes( $updatedescription ) )
				.	", pgitemdate=NOW()"
				.	" WHERE id=" . (int) $id
				;
		$_CB_database->setQuery($query);
		$_CB_database->query();
		//print $_CB_database->getQuery();
		// Add return code message here
		
		return $html2return;
	}

	/**
	 * This function deletes the profile gallery item entry specified by
	 * the id function parameter. Deletion consists of removing the relative row
	 * in the profilegallery table, deleting the actual gallery item file (and any thumbnail)
	 * and adjusting the gallery owners quota figures
	 *
	 * @param unknown_type $id
	 */
	function pgDelete($id,&$user) {
		global $_CB_database,$_CB_framework;
		$html2return = "";
		
		// Select all entries to be displayed
		$query="SELECT userid, pgitemtype, pgitemfilename, pgitemtitle, pgitemsize"
			. "\n FROM #__comprofiler_plug_profilegallery"
			. "\n WHERE id=". (int) $id;
		$_CB_database->setQuery($query);
		$item2delete=$_CB_database->loadObjectList();
		
        if (count($item2delete)==0) { // check if we are dealing with reload
            return $html2return;
		}
        $PGItemAbsolutePath=$_CB_framework->GetCfg( 'absolute_path' ).'/images/comprofiler/plug_profilegallery/';
		$PGItemAbsoluteUserPath = $PGItemAbsolutePath . $item2delete[0]->userid . "/";
		$pgitemfilenameuserabsolutepath = $PGItemAbsoluteUserPath . $item2delete[0]->pgitemfilename;
		$pgitemthumbuserabsolutepath = $PGItemAbsoluteUserPath . "tn" . $item2delete[0]->pgitemfilename;
		
		@unlink($pgitemfilenameuserabsolutepath);
	 	if (file_exists($pgitemthumbuserabsolutepath)) @unlink($pgitemthumbuserabsolutepath);
		
		$_CB_database->setQuery("SELECT cb_pgenable, cb_pgautopublish, cb_pgaccessmode, cb_pgdisplayformat, cb_pgtotalquotaitems, cb_pgtotalquotasize, cb_pgshortgreeting, cb_pgtotalitems, cb_pgtotalsize, cb_pglastupdate FROM #__comprofiler WHERE id=".(int) $item2delete[0]->userid);
		$user2update=$_CB_database->loadObjectList();
		
		$_CB_database->setQuery("DELETE FROM #__comprofiler_plug_profilegallery WHERE id=".$id);
		$_CB_database->query();
		
		$query = "UPDATE #__comprofiler SET  cb_pgtotalitems=". (int) $user2update[0]->cb_pgtotalitems . "-1, cb_pgtotalsize=" . (int) ( $user2update[0]->cb_pgtotalsize - $item2delete[0]->pgitemsize ) . ", cb_pglastupdate=NOW() WHERE id=" . (int) $item2delete[0]->userid;
		$_CB_database->setQuery($query);
		$_CB_database->query();
		$user->cb_pgtotalitems = (int) $user2update[0]->cb_pgtotalitems -1;
		$user->cb_pgtotalsize =  $user2update[0]->cb_pgtotalsize - $item2delete[0]->pgitemsize;
		
		$this->pgNotify($id,$user,"MODERATOR-DELETE");
		
		return $html2return;
	}
	
	/**
	* Generates the HTML to display the user profile tab
	* @param object tab reflecting the tab database entry
	* @param object mosUser reflecting the user being displayed
	* @param int 1 for front-end, 2 for back-end
	* @returns mixed : either string HTML for tab content, or false if Error generated
	*/
	function getDisplayTab($tab,$user,$ui) {
		global $_CB_database,$ueConfig,$_CB_framework;
						
		// Setup image storage paths
		$PGImagesPath			=	'/components/com_comprofiler/plugin/user/plug_cbprofilegallery/images/';
		$PGImagesAbsolutePath	=	$_CB_framework->getCfg( 'absolute_path' ) . $PGImagesPath;
		$PGImagesLivePath		=	$_CB_framework->getCfg( 'absolute_path' ) . $PGImagesPath;
		$PGItemAbsolutePath		=	$_CB_framework->GetCfg( 'absolute_path' ) . '/images/comprofiler/plug_profilegallery/';
		$PGItemPath				=	'images/comprofiler/plug_profilegallery/';
	
		$tabparams = $this->_pgGetTabParameters($user);
		//print_r($tabparams);
		
		// Return if the user doesn't have the ProfileGallery enabled no need to go any further
		// Does not even display the tab
		if(!$tabparams["cbpgenable"]) return "";
		
		$htmltext0 = "";
		$htmltext1 = ""; // first part of html code to display in the tab
		$htmltext2 = ""; // second part of html code to display in the tab
		$pgWHERE = "";
		$pgORDERBY = "";
		
		//Check to see if there are actions that need to be executed
		$action = $this->_getReqParam("PGformaction", null);
		$id = $this->_getReqParam("id",0);
		//$showform = $this->_getReqParam("showform", 0);

		switch ($tabparams["pgsortoption"]) {
			case 'DATEDESC':
				$pgORDERBY = ",pgitemdate desc";
				break;
			case 'DATEASC':
				$pgORDERBY = ",pgitemdate asc";
				break;
			default:
				$pgORDERBY = ",pgitemdate desc";
				break;
		}
		// Is profile owner viewing or not?
		// If not then set additional WHERE clause to only gather published images
		$isModerator=isModerator($_CB_framework->myId());
		
		if(($_CB_framework->myId() != $user->id) && !$isModerator) {
			$isME=false;
			$pgWHERE = "\n AND pgitempublished=1";
			$pgWHERE .= "\n AND pgitemapproved=1";
		} else {
			if ($_CB_framework->myId() == $user->id) {
				$isME=true;
			} else {
				$isME=false;
			}
			//LOGIC FOR ACTIONS HERE
			//Take necessary profile owner action if there is
			//Check to see if there are actions that need to be executed
			$action = $this->_getReqParam("PGformaction", null);
			$id = $this->_getReqParam("id",0);
					
			switch ($action) {
				CASE 'delete':
					$this->pgDelete($id,$user);
					break;
				CASE 'update':
					$updatetitle = $this->_getReqParam("pguitemtitle",null);
					$updatedescription = $this->_getReqParam("pguitemdescription",null);
					$this->pgUpdate($id,$updatetitle,$updatedescription);
					//print "Updating gallery item id:".$id;
					break;
				CASE 'publish':
					$this->pgPublish($id);
					//print "Publishing gallery item id:".$id;
					break;	
				CASE 'unpublish':
					$this->pgUnPublish($id);
					//print "Unpublishing gallery item id:".$id;
					break;	
				CASE 'approve':
                    if ($isModerator) {
					    $this->pgApprove($id,$user);
                    }
					//print "Approving gallery item id:".$id;
					break;
				CASE 'revoke':
                    if ($isModerator) {
					    $this->pgRevoke($id,$user);
                    }
					//print "Revoking gallery item id:".$id;
					break;
				DEFAULT:
					//print "I'm doing nothing:".$id." action:".$action;
					break;
			}
		}
		
		// if moderator viewing display extra info for front-end moderation activities
		if ($isModerator && !$isME) {
			$moderatorviewmessage = sprintf(    CBTxt::Th('<font color="red">Moderator data:<br />'
                                                .'Items - %1$d<br />'
                                                .'Item Quota - %2$d<br />'
                                                .'Storage - %3$d<br />'
                                                .'Storage Quota - %4$d<br />'
                                                .'Access Mode - %5$s<br />'
                                                .'Display Mode - %6$s<br /></font>'
                                                ),(int) $user->cb_pgtotalitems,
				                                $tabparams["cbpgtotalquotaitems"],
				                                $user->cb_pgtotalsize/1024,
				                                $tabparams["cbpgtotalquotasize"],
				                                $tabparams[$tabparams["cbpgaccessmode"]],
				                                $tabparams[$tabparams["cbpgdisplayformat"]],
                                                $tabparams["cbpguploadsize"]
                                                );
			$htmltext1 .= $moderatorviewmessage . "<br />";
		}
		
		// First thing to do is to display the correct tab description if not empty
		if($tab->description != null) {
			$htmltext1 .= "\t\t<div class=\"tab_Description\">";
			$htmltext1 .= cbUnHtmlspecialchars(getLangDefinition($tab->description));
			$htmltext1 .= "</div>\n";
		}
		
		
		// if connections restriction enable
		// and connections enabled on the system
		// and its not me viewing then
		// check if current viewer is connected with
		// profile owner
		
		if($_CB_framework->myId()==0) {
			$isAnonymous = true;
		} else {
			$isAnonymous = false;
		}
		$check4connection = 0;

		switch ($tabparams["cbpgaccessmode"]) {
			case 'PUB':
				break;
			case 'REG':
				if ($isAnonymous) {
					
					$htmltext1 .= "<p>" 
						. sprintf(CBTxt::Th("Only Registered Members Allowed to view the %1\$d items in this Gallery!"), (int) $user->cb_pgtotalitems)
						. "</p>";
					return $htmltext1;
				}
				break;
			case 'REG-S':
				if ($isAnonymous) return "";
				break;
			case 'CON':
				if ($isAnonymous) {
					$htmltext1 .= "<p>" 
						. sprintf(CBTxt::Th("Sorry - connections only viewing enabled for this gallery that currently has %1\$d items in it."), (int) $user->cb_pgtotalitems)
						. "</p>";
					return $htmltext1;
				}
				$check4connection = 1;
				break;
			case 'CON-S':
				if ($isAnonymous) {
					return $htmltext1;
				}
				$check4connection = 1;
				break;
			default:
				break;	
		}
		
		if ($check4connection && !$isAnonymous && !$isModerator && !$isME) {
			if ($ueConfig['allowConnections']) {	
				$query="SELECT COUNT(*)"
					. "\n FROM #__comprofiler_members"
					. "\n WHERE memberid=" . (int) $user->id
					. "\n AND referenceid=" . (int) $_CB_framework->myId()
					. "\n AND accepted=1 AND pending=0";
				$_CB_database->setQuery($query);
				$isconnected = $_CB_database->loadResult();
				if (!$isconnected) {
					if ($tabparams["cbpgaccessmode"] == 'CON-S') return "";
					$htmltext1 .= "<p>" . sprintf(CBTxt::Th("Sorry - connections only viewing enabled for this gallery that currently has %1\$d items in it."), (int) $user->cb_pgtotalitems) . "</p>";
					return $htmltext1;
				}
			} else {
				if ($tabparams["cbpgaccessmode"] == 'CON-S') return "";
				$htmltext1 .= "<p>" . sprintf(CBTxt::Th("Sorry - connections only viewing enabled for this gallery that currently has %1\$d items in it."), (int) $user->cb_pgtotalitems) . "</p>";
				return $htmltext1;
			}
		}
		
		//Check to see if a user has submitted a new gallery item entry to be processed
		if (($isME || ($tabparams["pgallowaccessmodeoverride"] && $isModerator)) AND isset($_POST[$this->_getPagingParamName("pgitemtitle")])) {
			$htmltext0 .= $this->_pgProcessNewItem($id,$user);
		}
		
		// Find and show posts
		$pagingParams = $this->_getPaging(array(),array("pgposts_"));
				
		//check to see if the Admin enabled pagination
		if ($tabparams["pgpagingenabled"]) {
			//select a count of all applicable entries for pagination
			$query="SELECT count(*)"
				. "\n FROM #__comprofiler_plug_profilegallery"
				. "\n WHERE userid=" . (int) $user->id
				. "\n " . $pgWHERE;
			
			$_CB_database->setQuery($query);
			$pgtotal = $_CB_database->loadResult();
			
			if (!is_numeric($pgtotal)) $pgtotal = 0;

			if ($pagingParams["pgposts_limitstart"] === null) $pagingParams["pgposts_limitstart"] = "0";
			if ($tabparams["pgentriesperpage"] > $pgtotal) $pagingParams["pgposts_limitstart"] = "0";
		} else {
			$pagingParams["pgposts_limitstart"] = "0";
		}
		
		$PGItemAbsoluteUserPath = $PGItemAbsolutePath . $user->id . "/";
		$PGItemUserPath = $PGItemPath . $user->id . "/";

        $lastitemid = $this->_pgLastItemId($user->id);
            
		// Select all entries to be displayed
		$query="SELECT *"
			. "\n FROM #__comprofiler_plug_profilegallery"
			. "\n WHERE userid=" . (int) $user->id
			. "\n " . $pgWHERE
			. "\n ORDER BY pgitemorder"
			. "\n " . $pgORDERBY
			;
		$_CB_database->setQuery($query, (int) ( $pagingParams["pgposts_limitstart"] ? $pagingParams["pgposts_limitstart"] : 0 ), (int) $tabparams["pgentriesperpage"] );
		//print $database->getQuery();
		$pgitems=$_CB_database->loadObjectList();
		$pgdisplaycount=count($pgitems);

		// Display welcome message
		$htmltext2 .= '<div class="cbpgGreetings">';
		$htmltext2 .= $tabparams["cbpgshortgreeting"];
		$htmltext2 .= '</div>';
		
		// Display submit new item logic
		$base_url = $this->_getAbsURLwithParam(array());
		if (($isME || ($tabparams["pgallowmoderatorfrontenduploads"] && $isModerator))  && ($user->cb_pgtotalitems < $tabparams["cbpgtotalquotaitems"])) {
			$_CB_framework->addJQueryPlugin( 'cbprofilegallery', '/components/com_comprofiler/plugin/user/plug_cbprofilegallery/js/profilegallery.js' );
			$_CB_framework->outputCbJQuery( '', 'cbprofilegallery' );
			$css		=	'.cbpgToggleEditor { padding-right: 14px; margin-bottom: 10px; }'
						.	"\n"
						.	'.cbpgEditorHidden { background: url(' . $_CB_framework->getCfg( 'live_site' ) . '/components/com_comprofiler/plugin/user/plug_cbprofilegallery/images/none-arrow.gif' . ') no-repeat right; }'
						.	"\n"
						.	'.cbpgEditorVisible { background: url(' . $_CB_framework->getCfg( 'live_site' ) . '/components/com_comprofiler/plugin/user/plug_cbprofilegallery/images/block-arrow.gif' . ') no-repeat right; }'
						.	"\n"
						.	'.cbpgQuotas { padding: 10px 0px; }'
						.	"\n"
						.	'.cbpgAdd { padding: 10px 0px 30px; }'
						.	"\n"
						.	'.cbpgAdd label { }'
						.	"\n"
						.	'label.cbpgInvalid { color: red; font-weight: bold; font-size:110%; margin-left: 8px; }'
						.	"\n"
						.	'input.cbpgInvalid { border-color: red; }'
						;
			$_CB_framework->document->addHeadStyleInline( $css );
				
			$quota_marks = sprintf(CBTxt::T(' [Your current quota marks: %1$d/%2$d items %3$d/%4$d Kbytes (%5$d%% consumed - %6$d%% free)]'),(int) $user->cb_pgtotalitems,
				$tabparams["cbpgtotalquotaitems"],
				$user->cb_pgtotalsize/1024,
				$tabparams["cbpgtotalquotasize"],
				floor($user->cb_pgtotalsize/1024/$tabparams["cbpgtotalquotasize"]*100),
				100-floor($user->cb_pgtotalsize/1024/$tabparams["cbpgtotalquotasize"]*100),
                (int) $tabparams["cbpguploadsize"]);
				
//			$htmltext2 .= '<div class="cbpgQuotas">' . $quota_marks . '</div>';
            
			$showform	=	false;
			$warnText	=	( ( ! $isME ) ? CBTxt::T( "You are about to add an entry to somebody else's gallery as a site Moderator. Proceed ?" ) : '' );

			$htmltext2 .= '<div class="cbpgSubmitForm"><a href="javascript:void(0);"  class="cbpgToggleEditor' . ( $showform ? ' cbpbEditorShow' : '' ) . '" title="' . htmlspecialchars( $warnText ) . '">'  . CBTxt::Th("Submit New Gallery Entry") . '</a>';

			$htmltext2 .= "<div class=\"cbpgAdd\" id=\"pg_divForm\" style=\"display:none;width:100%;\">";
            $htmltext2 .= '<div class="cbpgQuotas">' . $quota_marks . '</div>';
			$htmltext2 .= "<form name=\"pgadminForm\" id=\"pgadminForm\" method=\"post\" action=\"".$base_url."\" enctype=\"multipart/form-data\">\n";				
			
            $htmltext2 .= "<input type=\"hidden\" name=\"".$this->_getPagingParamName("pglastitemid")."\" value=\"".(int)$lastitemid."\" />";
            	
			$htmltext2 .= '<b><label for="cbpg_pgitemtitle" title="' . htmlspecialchars( CBTxt::T("A gallery item title must be entered") ) . '">' . CBTxt::Th("Title:") . "</label></b><br />";
			$htmltext2 .= "<input class=\"inputbox required\" type=\"text\" name=\"".$this->_getPagingParamName("pgitemtitle")."\" id=\"cbpg_pgitemtitle\" size=\"30\" maxlength=\"255\" /><br />";
			$htmltext2 .= '<b><label for="cbpg_pgitemdescription">' . CBTxt::Th("Description:") . "</label></b><br />";
			$htmltext2 .= "<textarea class=\"inputbox\" cols=\"35\" rows=\"4\" name=\"".$this->_getPagingParamName("pgitemdescription")."\" id=\"cbpg_pgitemdescription\"></textarea><br />";
			$htmltext2 .= '<b><label for="cbpg_pgitemfilename" title="' . htmlspecialchars( CBTxt::T("A file must be selected via the Browse button") ) . '">' . CBTxt::Th("Image File:") . "</label></b><br />";
			$htmltext2 .= "<input class=\"inputbox required\" type=\"file\" name=\"".$this->_getPagingParamName("pgitemfilename")."\" id=\"cbpg_pgitemfilename\" size=\"30\" /><br />";
			$htmltext2 .= "<input class=\"button\" name=\"pgsubmitentry\" id=\"pgsubmitentry\" type=\"submit\" value=\"" . htmlspecialchars( CBTxt::T("Submit Gallery Entry") ) ."\" title=\"\" />";
			$htmltext2 .= '<img alt="" src="' . $_CB_framework->getCfg( 'live_site' ) . '/components/com_comprofiler/images/wait.gif' . '" style="display:none; margin:9px;" />'; 
			$htmltext2 .= "</form>"; 
			$htmltext2 .= "</div></div>";
		}
		if (($isME || ($tabparams["pgallowmoderatorfrontenduploads"] && $isModerator)) && ($user->cb_pgtotalitems >= $tabparams["cbpgtotalquotaitems"])) {
			$htmltext2 .= '<font color="red">' . CBTxt::Th("Your Gallery item quota has been reached. You must delete an item in order to upload a new one or you may contact the admin to increase your quota.") . "</font><br />";
		}

		if ($pgdisplaycount > 0){	
			// headings go here if needed
			switch ($tabparams["cbpgdisplayformat"]){
				case 'DF1': // thumbnail layout headings and inits
					$icon = explode(",",$tabparams["cbpgbuttonslist"]);
					$dparm = explode(",",$tabparams["cbpgdisplayformatparameters"]);
					
					// get extra container box size bazed on icon height
					if ($isME || $isModerator) {
						list(, $icon_height, , ) = getimagesize($PGImagesAbsolutePath . $icon[1]);
						$pg_extrasize = $icon_height;
					} else {
						$pg_extrasize = 0;
					}
					
					$_CB_framework->document->addHeadScriptDeclaration(
					  "function pgpopup(pgimagefile,pgimagetitle,pgimagedescription) {\n"
					. "var newWindow = window.open(\"\",\"newWindow\",\"height=" . ($tabparams["pgmaxheight"]+$dparm[2]) . ",width=" . ($tabparams["pgmaxwidth"]+$dparm[3]) . ",resizable=yes, scrollbars=yes, toolbar=no " . "\" );\n"
					. "var imageurl = \"<img sr\" + \"c=\" + pgimagefile + \" />\";\n"
					. "newWindow.document.open();"
                    . "newWindow.document.writeln(\"<html>\");\n"
                    . "newWindow.document.writeln(\"<head>\");\n"
					. "newWindow.document.writeln(\"<title>Profile Gallery Image: \"+ pgimagetitle + \"</title>\");\n"
					. "newWindow.document.writeln(\"<div align='center' >\");\n" 
                    . "newWindow.document.writeln(imageurl);\n"
					. "newWindow.document.writeln(\"<br />\");"
					. "newWindow.document.writeln(pgimagedescription);\n"
					. "newWindow.document.writeln(\"</div>\");\n"
					. "newWindow.document.close();\n"
					. "}\n"
					);                     
 //                   $htmltext2 .= '<div class="cbpgItems">';
					break;
				case 'DF2': // file list layout headings
                    $dparm = explode(",",$tabparams["cbpgdisplayformatparameters"]);
					$htmltext2 .= "<table cellpadding=\"2\" cellspacing=\"4\" border=\"0\" width=\"95%\">";
					$htmltext2 .= "<tr class=\"sectiontableheader\">";
					$htmltext2 .= "<td width=25%>" . CBTxt::Th("Updated") . "</td>";
					$htmltext2 .= "<td>" . CBTxt::Th("Title") . "</td>";
					$htmltext2 .= "<td width=50%>". CBTxt::Th("Description") . "</td>";
					if ($isME || $isModerator) $htmltext2 .= "<td>" . CBTxt::Th("Actions") . "</td>";
					$htmltext2 .= "</tr>";
					break;
				case 'DF3': // thumbnail layout with lightbox headings and inits
					$icon = explode(",",$tabparams["cbpgbuttonslist"]);
					$dparm = explode(",",$tabparams["cbpgdisplayformatparameters"]);
					//print_r($dparm);
					
					// get extra container box size bazed on icon height
					if ($isME || $isModerator) {
						list(, $icon_height, , ) = getimagesize($PGImagesAbsolutePath . $icon[1]);
						$pg_extrasize = $icon_height;
					} else {
						$pg_extrasize = 0;
					}
					global $_CB_framework;
					// a better language string would be: 'Image {x} of {y}' but using existing stuff:
					// $txtImageXofY	=	CBTxt::T("Image ") . '{x}' . CBTxt::T(" of ") . '{y}';
                    $txtImageXofY   =   CBTxt::T('Image {x} of {y}');
					$_CB_framework->outputCbJQuery( "$('.pglightbox').slimbox( { counterText: '" . addslashes( $txtImageXofY ). "' } );", 'slimbox2' );
 //                  $htmltext2 .= '<div class="cbpgItems">';
					break;
				default:
					$htmltext2 .= "<b>column_heading_1 | column_heading2 | column_heading3</b><br />";
			}
			$i=2;
			$k=0;
			foreach ($pgitems as $pgitem) {
				$k++;
				$i= ($i==1) ? 2 : 1;
				$pgitemfilename = $pgitem->pgitemfilename;
				$pgitemtype = $pgitem->pgitemtype;
				$pgitemtitle = $pgitem->pgitemtitle;
				//$js_pgitemtitle = str_replace(array('"','<','>',"\n","\\","'","&#039;"), array("&quot;","&lt;","&gt;","\\n","\\\\","\\'","\\'"), $pgitemtitle);
				$js_pgitemtitle = addslashes(htmlspecialchars($pgitemtitle));
                                
                $html_pgitemtitle = htmlspecialchars($pgitemtitle);
				$html_pgitemtitle_formatted = htmlspecialchars(($dparm[0]&&cbIsoUtf_strlen($pgitemtitle)>$dparm[0]) ? cbIsoUtf_substr($pgitemtitle,0,$dparm[0]) . $dparm[1]:$pgitemtitle);
				$js_pgitemtitle_formatted = addslashes($html_pgitemtitle_formatted);
                
                $pgitemdescription = $pgitem->pgitemdescription;
				//$js_pgitemdescription = str_replace(array('"','<','>',"\n","\\","'","&#039;"), array("&quot;","&lt;","&gt;","<br />","\\\\","\\'","\\'"), $pgitemdescription);
				$js_pgitemdescription =  addslashes(htmlspecialchars($pgitemdescription));
                $html_pgitemdescription = htmlspecialchars($pgitemdescription);
                $lb_html_pgitemdescription = htmlspecialchars( str_replace( "\n", '<br />', $pgitemdescription) );
				$html_pgitemlightbox = CBTxt::Th("Title") . ": " . $html_pgitemtitle . "<br />" . CBTxt::Th("Description") . ": " . $lb_html_pgitemdescription;
				$pgitemdate = cbFormatDate($pgitem->pgitemdate);
				$pgitempublished = $pgitem->pgitempublished;
				$pgitemapproved = $pgitem->pgitemapproved;
	
			
				switch($pgitemtype) {
					case 'jpg':
                    case 'gif':
                    case 'png':
                    case 'bmp':
					    $popupname = "pgpopup";
						break;
					default:
						$popupname = "pgpopup";
				}
				
				$pgitemfilenameuserpath = $PGItemUserPath . $pgitemfilename;
				$pgitemthumbuserpath = $PGItemUserPath . "tn" . $pgitemfilename;
				$pgitemthumbuserabsolutepath = $PGItemAbsoluteUserPath . "tn" . $pgitemfilename;
				if (!file_exists($pgitemthumbuserabsolutepath)) {
					$pgitemthumbuserabsolutepath = $PGImagesAbsolutePath . "pgtn_" . $pgitemtype . "item.gif";
					$pgitemthumbuserpath = $PGImagesPath . "pgtn_" . $pgitemtype . "item.gif";
					if (!file_exists($pgitemthumbuserabsolutepath)) {
						$pgitemthumbuserabsolutepath = $PGImagesAbsolutePath . "pgtn_nonimageitem.gif";
						$pgitemthumbuserpath = $PGImagesPath . "pgtn_nonimageitem.gif";
					}
				}
                $pglivelink = $_CB_framework->getCfg( 'live_site' ) . "/" . $pgitemfilenameuserpath;
				$pgitemurl = "<a href=\"" . $pglivelink . "\">" . $pgitemfilename . "</a>";
				$pgitemtitle_url = "<a href=\"".cbSef($pglivelink)."\" target=\"_blank\"><b>".$html_pgitemtitle_formatted."</b><br />";

				list($pgitemtn_width, $pgitemtn_height, , ) = getimagesize($pgitemthumbuserabsolutepath);
					
				if ($pgitemtn_width <= $tabparams["pgtnmaxwidth"]) {
					$resize_width_factor = 1;
				} else {
					$resize_width_factor = $tabparams["pgtnmaxwidth"] / $pgitemtn_width;
				}
				if ($pgitemtn_height <= $tabparams["pgtnmaxheight"]) {
					$resize_height_factor = 1;
				} else {
					$resize_height_factor = $tabparams["pgtnmaxheight"] / $pgitemtn_height;
				}
	
				$resize_factor = min($resize_width_factor,$resize_height_factor);
				$newtn_height = floor($pgitemtn_height * $resize_factor);
				$newtn_width = floor($pgitemtn_width * $resize_factor);

				
				switch ($tabparams["cbpgdisplayformat"]){
					case 'DF1': // image layout headings (none)
						if (!$pgitemapproved || !$pgitempublished) {
							$htmltext2.= "<div class=\"connectionBox cbpgIbox\" style=\"text-align:center;border:1px dotted;position:relative;height:".($tabparams["pgtnmaxheight"]+$dparm[4]+$pg_extrasize)."px;width:".($tabparams["pgtnmaxwidth"]+$dparm[5])."px;\">";
						} else {
							$htmltext2.= "<div class=\"connectionBox cbpgIbox\" style=\"text-align:center;position:relative;height:".($tabparams["pgtnmaxheight"]+$dparm[4]+$pg_extrasize)."px;width:".($tabparams["pgtnmaxwidth"]+$dparm[5])."px;\">";
						}
						// Check file extension type
						$inimagelist = in_array($pgitemtype,explode(",",$tabparams["pgimagefiletypelist"]));
						if ($tabparams["pgopmode"]!="FILEMODE" && $inimagelist){
							$htmltext2.= "<a href=\"".cbSef($pglivelink) . "\" target=\"_blank\"><b>" . $html_pgitemtitle_formatted . "</b></a><br />"
								. "<div style=\"height:".$tabparams["pgtnmaxheight"]."px;\">"
								. "<a href=\"javascript:$popupname('$pglivelink','$js_pgitemtitle','$js_pgitemdescription')\">"
							//	. $popupcode
                                . "<img src=\"" . $_CB_framework->getCfg( 'live_site' ) . "/" . $pgitemthumbuserpath . "\" border=\"0\" height=\"$newtn_height\" width=\"$newtn_width\" alt=\"\" title=\"" . $html_pgitemdescription . "\" />"
								. "</a>"
								. "</div>"
								. "<br /><br />";
						} else {
							$htmltext2.= "<a href=\"".cbSef($pglivelink)."\"><b>" . $html_pgitemtitle_formatted . "</b></a><br />"
								. "<div style=\"height:".$tabparams["pgtnmaxheight"]."px;\">"
								. "<a href=\"$pgitemfilenameuserpath\" target=\"_blank\">"
								. "<img src=\"" . $_CB_framework->getCfg( 'live_site' ) . "/" . $pgitemthumbuserpath . "\" border=\"0\" height=\"$newtn_height\" width=\"$newtn_width\" alt=\"\" title=\"" . $html_pgitemdescription . "\" />"
								. "</a>"
								. "</div>"
								. "<br /><br />";							
						}
						$htmltext2 .= "<form name=\"PGactionForm".$k."\" id=\"PGactionForm".$k."\" method=\"post\" action=\"".$base_url."\">";
						$htmltext2 .= "<input type=\"hidden\" name=\"".$this->_getPagingParamName("id")."\" value=\"".$pgitem->id."\" />";
						$htmltext2 .= "<input type=\"submit\" name=\"PGsubmitform\" style=\"display:none;\" />";
						$htmltext2 .= "<input type=\"hidden\" name=\"".$this->_getPagingParamName("PGformaction")."\" value=\"default\" /></form>";
						if ($isME || $isModerator) {
							$htmltext2 .= "<a href=\"javascript:if (confirm('" . addslashes(CBTxt::T("Are you sure you want to delete selected item ? The selected item will be deleted and cannot be undone!")) . "')) { document.PGactionForm".$k.".".$this->_getPagingParamName("PGformaction").".value='delete';document.PGactionForm".$k.".submit(); }\"><img style=\"cursor:pointer;border:0px;\" class=\"pg_c1\" src=\"" . $_CB_framework->getCfg( 'live_site' ) . "/components/com_comprofiler/plugin/user/plug_cbprofilegallery/images/" . $icon[0] . "\" alt=\"" . htmlspecialchars(CBTxt::T("Delete")) ."\" title=\"" . htmlspecialchars(CBTxt::T("Delete")) . "\" /></a>";			
							if (!$pgitempublished) {
								$htmltext2 .= "<a href=\"javascript:document.PGactionForm".$k.".".$this->_getPagingParamName("PGformaction").".value='publish';document.PGactionForm".$k.".submit();\"><img style=\"cursor:pointer;border:0px;\" class=\"pg_c2\" src=\"" . $_CB_framework->getCfg( 'live_site' ) . "/components/com_comprofiler/plugin/user/plug_cbprofilegallery/images/" . $icon[2] . "\" alt=\"" . htmlspecialchars(CBTxt::T("Publish")) ."\" title=\"" . htmlspecialchars(CBTxt::T("Publish")) . "\" /></a>";
							} else {
								$htmltext2 .= "<a href=\"javascript:document.PGactionForm".$k.".".$this->_getPagingParamName("PGformaction").".value='unpublish';document.PGactionForm".$k.".submit();\"><img style=\"cursor:pointer;border:0px;\" class=\"pg_c3\" src=\"" . $_CB_framework->getCfg( 'live_site' ) . "/components/com_comprofiler/plugin/user/plug_cbprofilegallery/images/" . $icon[1] . "\" alt=\"" . htmlspecialchars(CBTxt::T("Unpublish")) ."\" title=\"" . htmlspecialchars(CBTxt::T("Unpublish")) . "\" /></a>";
							}
						}
						if ($isModerator) {
							if (!$pgitemapproved) {
								$htmltext2 .= "<a href=\"javascript:document.PGactionForm".$k.".".$this->_getPagingParamName("PGformaction").".value='approve';document.PGactionForm".$k.".submit();\"><img style=\"cursor:pointer;border:0px;\" class=\"pg_c4\" src=\"" . $_CB_framework->getCfg( 'live_site' ) . "/components/com_comprofiler/plugin/user/plug_cbprofilegallery/images/" . $icon[3] . "\" alt=\"" . htmlspecialchars(CBTxt::T("Approve")) ."\" title=\"" . htmlspecialchars(CBTxt::T("Approve")) . "\" /></a>";
							} else {
								$htmltext2 .= "<a href=\"javascript:document.PGactionForm".$k.".".$this->_getPagingParamName("PGformaction").".value='revoke';document.PGactionForm".$k.".submit();\"><img style=\"cursor:pointer;border:0px;\" class=\"pg_c5\" src=\"" . $_CB_framework->getCfg( 'live_site' ) . "/components/com_comprofiler/plugin/user/plug_cbprofilegallery/images/" . $icon[4] . "\" alt=\"" . htmlspecialchars(CBTxt::T("Revoke")) ."\" title=\"" . htmlspecialchars(CBTxt::T("Revoke")) . "\" /></a>";
							}
						}
						if ($isME || $isModerator) {
							$popform=null;
							$popform .= "<form name=\"PGformaction".$k."\" method=\"post\" action=\"".$base_url."\">";
							$popform .= "<input type=\"hidden\" name=\"".$this->_getPagingParamName("id")."\" value=\"".$pgitem->id."\" />";
							$popform .= "<input type=\"hidden\" name=\"".$this->_getPagingParamName("PGformaction")."\" value=\"update\" />";
							$popform .= "<br /><b>" . CBTxt::Th("Title:") . ":</b><br /><input class=\"inputbox\" type=\"text\" name=\"".$this->_getPagingParamName("pguitemtitle")."\" size=\"30\" maxlength=\"255\" value=\"".$html_pgitemtitle."\" />";
							$popform .= "<br /><b>". CBTxt::Th("Description") . ":</b><br /><textarea class=\"inputbox\" cols=\"35\" rows=\"4\" name=\"".$this->_getPagingParamName("pguitemdescription")."\" style=\"height:75px;width:285px;overflow:auto;\" >".$html_pgitemdescription."</textarea>";
							$popform .= "<br /><input type=\"submit\" value=\"" . htmlspecialchars( CBTxt::T("Update") ) . "\" title=\"\" /></form>";					
							
							$htmltext2 .= "<a href=\"javascript:void(0);\" name=\"PGeditForm".$k."\" id=\"PGeditForm".$k."\" onclick=\""
								."return overlib('".addslashes(htmlspecialchars($popform))."', STICKY, CAPTION,'" . CBTxt::T("Edit Gallery Item")."', CENTER,CLOSECLICK,CLOSETEXT,'"._UE_CLOSE_OVERLIB."',WIDTH,300, ANCHOR,'PGeditForm".$k."',ANCHORALIGN,'LR','UR');\">"."<img style=\"cursor:pointer;border:0px;\" class=\"pg_c5\" src=\"" . $_CB_framework->getCfg( 'live_site' ) . "/components/com_comprofiler/plugin/user/plug_cbprofilegallery/images/" . $icon[5] . "\" alt=\"" . htmlspecialchars(CBTxt::T("Edit")) ."\" title=\"" . htmlspecialchars(CBTxt::T("Edit")) . "\""." /></a> ";
                        }

						$htmltext2 .= "<br /></div>";
						break;	
					case 'DF2': // file list layout
						$htmltext2 .= "<form name=\"PGactionForm".$k."\" id=\"PGactionForm".$k."\" method=\"post\" action=\"".$base_url."\">";
						$htmltext2 .= "<input type=\"hidden\" name=\"".$this->_getPagingParamName("id")."\" value=\"".$pgitem->id."\" />";
						$htmltext2 .= "<input type=\"submit\" name=\"PGsubmitform\" style=\"display:none;\" />";
						$htmltext2 .= "<input type=\"hidden\" name=\"".$this->_getPagingParamName("PGformaction")."\" value=\"default\" /></form>";

						$htmltext2 .= "<tr>";
						$htmltext2 .= "<td>".$pgitemdate."</td>";
						$htmltext2 .= "<td>".$pgitemtitle_url."</td>";
						$htmltext2 .= "<td>".cbUnHtmlspecialchars($pgitemdescription)."</td>";
						if ($isME || $isModerator) $htmltext2 .= "<td>";
						if ($isME || $isModerator) {
							$htmltext2 .= "<a href=\"javascript:if (confirm('" . addslashes(CBTxt::T("Are you sure you want to delete selected item ? The selected item will be deleted and cannot be undone!")) . "')) { document.PGactionForm".$k.".".$this->_getPagingParamName("PGformaction").".value='delete';document.PGactionForm".$k.".submit(); }\">" . CBTxt::T("Delete")."<br /></a>";			
							if (!$pgitempublished) {
								$htmltext2 .= "<a href=\"javascript:document.PGactionForm".$k.".".$this->_getPagingParamName("PGformaction").".value='publish';document.PGactionForm".$k.".submit();\">". CBTxt::Th("Publish") . "<br /></a>";
							} else {
								$htmltext2 .= "<a href=\"javascript:document.PGactionForm".$k.".".$this->_getPagingParamName("PGformaction").".value='unpublish';document.PGactionForm".$k.".submit();\">" . CBTxt::Th("Unpublish") . "<br /></a>";
							}
						}
						if ($isModerator) {
							if (!$pgitemapproved) {
								$htmltext2 .= "<a href=\"javascript:document.PGactionForm".$k.".".$this->_getPagingParamName("PGformaction").".value='approve';document.PGactionForm".$k.".submit();\">" . CBTxt::Th("Approve") . "<br /></a>";
							} else {
								$htmltext2 .= "<a href=\"javascript:document.PGactionForm".$k.".".$this->_getPagingParamName("PGformaction").".value='revoke';document.PGactionForm".$k.".submit();\">" . CBTxt::Th("Revoke") . "<br /></a>";
							}
						}
						if ($isME || $isModerator) {
							$popform=null;
							$popform .= "<form name=\"PGformaction".$k."\" method=\"post\" action=\"".$base_url."\">";
							$popform .= "<input type=\"hidden\" name=\"".$this->_getPagingParamName("id")."\" value=\"".$pgitem->id."\" />";
							$popform .= "<input type=\"hidden\" name=\"".$this->_getPagingParamName("PGformaction")."\" value=\"update\" />";
							$popform .= "<br /><b>" . CBTxt::T("Title") . ":</b><br /><input class=\"inputbox\" type=\"text\" name=\"".$this->_getPagingParamName("pguitemtitle")."\" size=\"30\" maxlength=\"255\" value=\"".$html_pgitemtitle."\" />";
							$popform .= "<br /><b>" . CBTxt::T("Description") . ":</b><br /><textarea class=\"inputbox\" cols=\"35\" rows=\"4\" name=\"".$this->_getPagingParamName("pguitemdescription")."\" style=\"height:75px;width:285px;overflow:auto;\" >".$html_pgitemdescription."</textarea>";
							$popform .= "<br /><input type=\"submit\" value=\"" . htmlspecialchars( CBTxt::T("Update") ) . "\" title=\"\" /></form>";					
							
							$htmltext2 .= "<a href=\"javascript:void(0);\" name=\"PGeditForm".$k."\" id=\"PGeditForm".$k."\" onclick=\""
                                ."return overlib('".addslashes(htmlspecialchars($popform))."', STICKY, CAPTION,'" . addslashes(CBTxt::T("Edit Gallery Item")) . "', CENTER,CLOSECLICK,CLOSETEXT,'" . _UE_CLOSE_OVERLIB . "',WIDTH,300, ANCHOR,'PGeditForm".$k."',ANCHORALIGN,'LR','UR');\">". CBTxt::Th("Edit") ."</a> ";
                        }

						if ($isME || $isModerator) $htmltext2 .= "</td>";
						$htmltext2 .= "</tr>";
						break;

					case 'DF3': // image layout headings (none)
						if (!$pgitemapproved || !$pgitempublished) {
							$htmltext2.= "<div class=\"connectionBox cbpgIbox\" style=\"text-align:center;border:1px dotted;position:relative;height:".($tabparams["pgtnmaxheight"]+$dparm[4]+$pg_extrasize)."px;width:".($tabparams["pgtnmaxwidth"]+$dparm[5])."px;\">";
						} else {
							$htmltext2.= "<div class=\"connectionBox cbpgIbox\" style=\"text-align:center;position:relative;height:".($tabparams["pgtnmaxheight"]+$dparm[4]+$pg_extrasize)."px;width:".($tabparams["pgtnmaxwidth"]+$dparm[5])."px;\">";
						}
						// Check file extension type
						$inimagelist = in_array($pgitemtype,explode(",",$tabparams["pgimagefiletypelist"]));
						if ($tabparams["pgopmode"]!="FILEMODE" && $inimagelist){
							$htmltext2.= "<a href=\"".cbSef($pgitemfilenameuserpath) . "\" target=\"_blank\"><b>" . $html_pgitemtitle_formatted . "</b></a><br />"
								. "<div style=\"height:".$tabparams["pgtnmaxheight"]."px;\">"	
								. '<a class="pglightbox" title="' . htmlspecialchars( $html_pgitemlightbox ) . '" rel="lightbox-group" href="' . $pglivelink . '">'
								. "<img src=\"" . $_CB_framework->getCfg( 'live_site' ) . "/" . $pgitemthumbuserpath . "\" border=\"0\"  height=\"$newtn_height\" width=\"$newtn_width\" alt=\"\" title=\"" . $html_pgitemdescription . "\" />"
								. "</a>"
								. "</div>"
								;
						} else {
							$htmltext2.= "<a href=\"".cbSef($pglivelink)."\"><b>" . $html_pgitemtitle_formatted . "</b></a><br />"
								. "<div style=\"height:".$tabparams["pgtnmaxheight"]."px;\">"
								. "<a href=\"$pgitemfilenameuserpath\" target=\"_blank\">"
								. "<img src=\"" . $_CB_framework->getCfg( 'live_site' ) . "/" . $pgitemthumbuserpath . "\" border=\"0\"  height=\"$newtn_height\" width=\"$newtn_width\" alt=\"\" title=\"" . $html_pgitemdescription . "\" />"
								. "</a>"
								. "</div>"
								;							
						}
						if ($isME || $isModerator) {
							$htmltext2	.=	'<div class="cbpgControlArea" style="text-align:center;">';
						}
						$htmltext2 .= "<form name=\"PGactionForm".$k."\" id=\"PGactionForm".$k."\" method=\"post\" action=\"".$base_url."\">";
						$htmltext2 .= "<input type=\"hidden\" name=\"".$this->_getPagingParamName("id")."\" value=\"".$pgitem->id."\" />";                                                                                                                                                                                                                                                                                                                                                                                               
						$htmltext2 .= "<input type=\"submit\" name=\"PGsubmitform\" style=\"display:none;\" title=\"\" />";
						$htmltext2 .= "<input type=\"hidden\" name=\"".$this->_getPagingParamName("PGformaction")."\" value=\"default\" /></form>";
						if ($isME || $isModerator) {
							$htmltext2 .= "<a href=\"javascript:if (confirm('" . addslashes(CBTxt::T("Are you sure you want to delete selected item ? The selected item will be deleted and cannot be undone!")) . "')) { document.PGactionForm".$k.".".$this->_getPagingParamName("PGformaction").".value='delete';document.PGactionForm".$k.".submit(); }\"><img style=\"cursor:pointer;border:0px;\" class=\"pg_c1\" src=\"" . $_CB_framework->getCfg( 'live_site' ) . "/components/com_comprofiler/plugin/user/plug_cbprofilegallery/images/" . $icon[0] . "\" alt=\"" . htmlspecialchars(CBTxt::T("Delete")) . "\" title=\"". htmlspecialchars(CBTxt::T("Delete"))."\" /></a>";			
							if (!$pgitempublished) {
								$htmltext2 .= "<a href=\"javascript:document.PGactionForm".$k.".".$this->_getPagingParamName("PGformaction").".value='publish';document.PGactionForm".$k.".submit();\"><img style=\"cursor:pointer;border:0px;\" class=\"pg_c2\" src=\"" . $_CB_framework->getCfg( 'live_site' ) . "/components/com_comprofiler/plugin/user/plug_cbprofilegallery/images/" . $icon[2] . "\" alt=\"" . htmlspecialchars(CBTxt::T("Publish")) ."\" title=\"" . htmlspecialchars(CBTxt::T("Publish")) . "\" /></a>";
							} else {
								$htmltext2 .= "<a href=\"javascript:document.PGactionForm".$k.".".$this->_getPagingParamName("PGformaction").".value='unpublish';document.PGactionForm".$k.".submit();\"><img style=\"cursor:pointer;border:0px;\" class=\"pg_c3\" src=\"" . $_CB_framework->getCfg( 'live_site' ) . "/components/com_comprofiler/plugin/user/plug_cbprofilegallery/images/" . $icon[1] . "\" alt=\"" . htmlspecialchars(CBTxt::T("Unpublish")) ."\" title=\"" . htmlspecialchars(CBTxt::T("Unpublish")) . "\" /></a>";
							}
						}
						if ($isModerator) {
							if (!$pgitemapproved) {
								$htmltext2 .= "<a href=\"javascript:document.PGactionForm".$k.".".$this->_getPagingParamName("PGformaction").".value='approve';document.PGactionForm".$k.".submit();\"><img style=\"cursor:pointer;border:0px;\" class=\"pg_c4\" src=\"" . $_CB_framework->getCfg( 'live_site' ) . "/components/com_comprofiler/plugin/user/plug_cbprofilegallery/images/" . $icon[3] . "\" alt=\"" . htmlspecialchars(CBTxt::T("Approve")) ."\" title=\"" . htmlspecialchars(CBTxt::T("Approve")) . "\" /></a>";
							} else {
								$htmltext2 .= "<a href=\"javascript:document.PGactionForm".$k.".".$this->_getPagingParamName("PGformaction").".value='revoke';document.PGactionForm".$k.".submit();\"><img style=\"cursor:pointer;border:0px;\" class=\"pg_c5\" src=\"" . $_CB_framework->getCfg( 'live_site' ) . "/components/com_comprofiler/plugin/user/plug_cbprofilegallery/images/" . $icon[4] . "\" alt=\"" . htmlspecialchars(CBTxt::T("Revoke")) . "\" title=\"" . htmlspecialchars(CBTxt::T("Revoke")) . "\" /></a>";
							}
						}
						if ($isME || $isModerator) {
							$popform=null;
							$popform .= "<form name=\"PGformaction".$k."\" method=\"post\" action=\"".$base_url."\">";
							$popform .= "<input type=\"hidden\" name=\"".$this->_getPagingParamName("id")."\" value=\"".$pgitem->id."\" />";
							$popform .= "<input type=\"hidden\" name=\"".$this->_getPagingParamName("PGformaction")."\" value=\"update\" />";
							$popform .= "<br /><b>" . CBTxt::T("Title") . ":</b><br /><input class=\"inputbox\" type=\"text\" name=\"".$this->_getPagingParamName("pguitemtitle")."\" size=\"30\" maxlength=\"255\" value=\"".$html_pgitemtitle."\" />";
							$popform .= "<br /><b>" . CBTxt::T("Description") . ":</b><br /><textarea class=\"inputbox\" cols=\"35\" rows=\"4\" name=\"".$this->_getPagingParamName("pguitemdescription")."\" style=\"height:75px;width:285px;overflow:auto;\" >".$html_pgitemdescription."</textarea>";
							$popform .= "<br /><input type=\"submit\" value=\"" . htmlspecialchars( CBTxt::T("Update") ) . "\" title=\"\" /></form>";					
							
							$htmltext2 .= "<a href=\"javascript:void(0);\" name=\"PGeditForm".$k."\" id=\"PGeditForm".$k."\" onclick=\""
                                ."return overlib('".addslashes(htmlspecialchars($popform))."', STICKY, CAPTION,'" . CBTxt::T("Edit Gallery Item") . "', CENTER,CLOSECLICK,CLOSETEXT,'" . _UE_CLOSE_OVERLIB . "',WIDTH,300, ANCHOR,'PGeditForm".$k."',ANCHORALIGN,'LR','UR');\">"."<img style=\"cursor:pointer;border:0px;\" class=\"pg_c5\" src=\"" . $_CB_framework->getCfg( 'live_site' ) . "/components/com_comprofiler/plugin/user/plug_cbprofilegallery/images/" . $icon[5] . "\" alt=\"" . htmlspecialchars(CBTxt::T("Edit")) ."\" title=\"" . htmlspecialchars(CBTxt::T("Edit")) . "\""." /></a> ";

							$htmltext2	.=	'</div>';
						}
						$htmltext2 .= "</div>";
						break;
					default:
						$htmltext2 .= $pgitemurl . "|" . $pgitemtype . "|" . $pgitemtitle ."|" . $pgitemdescription ."<br />";
						break;
				}
				
			}
			switch ($tabparams["cbpgdisplayformat"]){
					case 'DF1':
//                     $htmltext2 .= '</div>'; // close cbpgItems div
						break;
					case 'DF2':
						$htmltext2 .= "</table>";
						break;
					case 'DF3':
//						$htmltext2 .= '</div>'; // close cbpgItems div 
                        break;
			}
			// Add paging control at end of list if paging enabled
			if ($tabparams["pgpagingenabled"] && ($tabparams["pgentriesperpage"] < $pgtotal)) {
				$htmltext2 .= "<div style=\"clear:both;\">&nbsp;</div>";
				$htmltext2 .= "<div style='width:95%;text-align:center;'>"
				.$this->_writePaging($pagingParams,"pgposts_",$tabparams["pgentriesperpage"],$pgtotal)
				."</div>";
			}	
		} else {
			$htmltext2 .= "<br/>";
			$htmltext2 .= CBTxt::Th("No Items published in this profile gallery");
			$htmltext2 .= "<br/>";
		}
		$htmltext2 .= "<div style=\"clear:both;\">&nbsp;</div>";
 //       $htmltext2 .= "</div>";
		return $htmltext0 . $htmltext1 . $htmltext2;
	}

	function _getPGparamName( $name ) {
		return $name;		// return same name as field for now.
	}
	/**
	* Generates the HTML to display the user edit tab
	* @param object tab reflecting the tab database entry
	* @param object mosUser reflecting the user being displayed
	* @param int 1 for front-end, 2 for back-end
	* @returns mixed : either string HTML for tab content, or false if ErrorMSG generated
	*/
	function getEditTab($tab,$user,$ui) {
        global $_CB_framework;
        
		//fix to hide edit tab from front-end!
		//if ($ui==1) return;
		
		// Get all relevant tab parameters - these settings are global and set by the admin
		$tabparams = $this->_pgGetTabParameters($user);
		//print_r($tabparams);
        $isModerator=isModerator($_CB_framework->myId());


		if ($ui==1 && $tabparams["pgallowuserenable"]!=1 && $tabparams["cbpgenable"]!=1 && !$isModerator) {
			return null;
		}
		
        $testfeature = 0; // test feature setting to allow backend running total edits set this to 1;
        
		$return		=	$this->_writeTabDescription( $tab, $user );

		$return .= "<table cellpadding=\"5\" cellspacing=\"0\" border=\"0\" width=\"100%\">\n";
		
		// Short Greeting:
		$return .= "<tr>\n";
		$return .= "	<td class=\"titleCell\">" . CBTxt::Th("Short Greeting") . ":</td>\n";
		$return .= "	<td class=\"fieldCell\"><input class=\"inputbox\" type=\"text\" name=\"".$this->_getPGparamName("cb_pgshortgreeting")."\" mosReq=\"0\" mosLabel=\"" . htmlspecialchars(CBTxt::T("Short Greeting")) . "\" value=\"".cbUnHtmlspecialchars($user->cb_pgshortgreeting)."\" size=\"40\" />";
		$return .= getFieldIcons($ui, false, true, CBTxt::Th("Enter a short greeting for your gallery viewers"), CBTxt::Th("Short Greeting").":");
		$return .= "</td></tr>\n";

		$lists = array();

		// make the select list for the special gallery yes/no fields (normally 1=yes, 0=no)
		$yesno = array();
		$yesno[] = moscomprofilerHTML::makeOption( '_UE_YES', _CMN_YES );	// 1
		$yesno[] = moscomprofilerHTML::makeOption( '_UE_NO', _CMN_NO );	// 0

		// Enable Gallery:
        // logic is:    that backend can edit cb field but frontend can only edit if parameter is set to allow or if moderator is viewing
        //              
		if ($tabparams["pgallowuserenable"] || $ui==2 || $isModerator) { 
			$lists['_pg_EnableProfileGallery'] = moscomprofilerHTML::radioList( $yesno, $this->_getPGparamName("cb_pgenable"), 'class="inputbox" size="1" mosReq="0" mosLabel="' . htmlspecialchars(CBTxt::T("Enable Gallery")).'"', 'value', 'text', $user->cb_pgenable);
			$return .= "<tr>\n";
			$return .= "  <td class=\"titleCell\">" . CBTxt::Th("Enable Gallery") . ":</td>\n";
			$return .= "  <td class=\"fieldCell\">".$lists['_pg_EnableProfileGallery'];
			$return .= getFieldIcons($ui, false, false, CBTxt::Th("Select Yes or No to turn-on or off the Gallery Tab"),CBTxt::Th("Enable Gallery") . ":");
			$return .= "</td>\n</tr>\n";
		}

		// Enable Autopublish Items:
		if ($tabparams["pgallowautopublishoverride"] || $ui==2 || $isModerator) {
			$lists['_pg_AutoPublish'] = moscomprofilerHTML::radioList( $yesno, $this->_getPGparamName("cb_pgautopublish"), 'class="inputbox" size="1" mosReq="0" mosLabel="' . htmlspecialchars(CBTxt::T("Autopublish items")).'"', 'value', 'text', $user->cb_pgautopublish);
			$return .= "<tr>\n";
			$return .= "  <td class=\"titleCell\">" . CBTxt::Th("Autopublish items") . ":</td>\n";
			$return .= "  <td class=\"fieldCell\">";
			if ($tabparams["pgautopublish"]) {
				$return .= $lists['_pg_AutoPublish'];
			} else {
				$return .= ($tabparams["cbpgautopublish"] ? _CMN_YES : _CMN_NO);
			}
			$return .= getFieldIcons($ui, false, false, CBTxt::Th("Select Yes or No to autopublish or not newly uploaded gallery items"), CBTxt::Th("Autopublish items").":");
			$return .= "</td>\n</tr>\n";
		}

		// Auto-approve Items setting in backend or frontend for moderators:
		if ( ($ui == 2 && $tabparams["pgallowautoapproveoverride"]) || ($isModerator && $tabparams["pgallowautoapproveoverride"]) ) {
			$lists['_pg_AutoApprove'] = moscomprofilerHTML::radioList( $yesno, $this->_getPGparamName("cb_pgautoapprove"), 'class="inputbox" size="1" mosReq="0" mosLabel="' . htmlspecialchars(CBTxt::T("Automatically approve")) . '"', 'value', 'text', $user->cb_pgautoapprove);
			$return .= "<tr>\n";
			$return .= "  <td class=\"titleCell\">" . CBTxt::Th("Automatically approve") . ":</td>\n";
			$return .= "  <td class=\"fieldCell\">". $lists['_pg_AutoApprove'];
			$return .= getFieldIcons($ui, false, false,CBTxt::Th("This value can be set by the admin to over-ride the gallery plugin backend default approval parameter"), CBTxt::Th("Automatically approve") . ":");
			$return .= "</td>\n</tr>\n";
		}
		// Storage Quota setting only in backend or frontend for moderators:
		if (isset($user->cb_pgtotalquotasize) && $user->cb_pgtotalquotasize) {
			$quota = $user->cb_pgtotalquotasize;
		} else {
			$quota = $tabparams["pgdefaultquota"];
		}
		$return .= "<tr>\n";
		$return .= "  <td class=\"titleCell\">" . CBTxt::Th("Storage Quota (KB)") . ":</td>\n";
		$return .= "  <td class=\"fieldCell\">";
		if ($ui == 2 || $isModerator) {
			$return .= "<input class=\"inputbox\" type=\"text\" name=\"".$this->_getPGparamName("cb_pgtotalquotasize")."\" mosReq=\"0\" mosLabel=\"" . htmlspecialchars(CBTxt::T("Storage Quota (KB)")) . "\" value=\"".(isset($user->cb_pgtotalquotasize) ? $user->cb_pgtotalquotasize : null)."\" size=\"10\" />";
			$return .= " (" . CBTxt::Th("Default setting") . ": ".$tabparams["pgdefaultquota"].")";

		} else {
			$return .= $quota;
		}
		$return .= getFieldIcons($ui, false, false, CBTxt::Th("This value can be set by the admin to over-ride the gallery plugin backend default user quota"), CBTxt::Th("Storage Quota (KB)") . ":");
		$return .= "</td>\n</tr>\n";
		
		// Uploaded Items Quota setting only in backend or frontend for moderators:
		if (isset($user->cb_pgtotalquotaitems) && $user->cb_pgtotalquotaitems) {
			$quota = $user->cb_pgtotalquotaitems;
		} else {
			$quota = $tabparams["pgnumberofgalleryitems"];
		}
		$return .= "<tr>\n";
		$return .= "  <td class=\"titleCell\">" . CBTxt::Th("Item Quota") . ":</td>\n";
		$return .= "  <td class=\"fieldCell\">";
		if ($ui == 2 || $isModerator) {
			$return .= "<input class=\"inputbox\" type=\"text\" name=\"".$this->_getPGparamName("cb_pgtotalquotaitems")."\" mosReq=\"0\" mosLabel=\"" . htmlspecialchars(CBTxt::T("Item Quota")) . "\" value=\"".(isset($user->cb_pgtotalquotaitems) ? $user->cb_pgtotalquotaitems : null)."\" size=\"10\" />";
			$return .= " (" . CBTxt::Th("Default setting").": ".$tabparams["pgnumberofgalleryitems"].")";

		} else {
			$return .= $quota;
		}
		$return .= getFieldIcons($ui, false, false, CBTxt::Th("The admin may use this to over-ride the default value of allowable items for each profile owner"), CBTxt::Th("Item Quota") . ":");
		$return .= "</td>\n</tr>\n";
		
		// Single Uploaded Item Maximum Size setting only in backend or frontend for moderators:
		if (isset($user->cb_pguploadsize) && $user->cb_pguploadsize) {
			$quota = $user->cb_pguploadsize;
		} else {
			$quota = $tabparams["pgmaxsize"];
		}
		$return .= "<tr>\n";
		$return .= "  <td class=\"titleCell\">" . CBTxt::Th("Max single upload (KB)") . ":</td>\n";
		$return .= "  <td class=\"fieldCell\">";
		if ($ui == 2 || $isModerator) {
			$return .= "<input class=\"inputbox\" type=\"text\" name=\"".$this->_getPGparamName("cb_pguploadsize")."\" mosReq=\"0\" mosLabel=\"" . htmlspecialchars(CBTxt::T("Max single upload (KB)")) . "\" value=\"".(isset($user->cb_pguploadsize) ? $user->cb_pguploadsize : null)."\" size=\"10\" />";
			$return .= " (" . CBTxt::Th("Default setting") . ": ".$tabparams["pgmaxsize"].")";

		} else {
			$return .= $quota;
		}
		$return .= getFieldIcons($ui, false, false, CBTxt::Th("This value can be set by the admin to over-ride the gallery plugin backend default maximum single upload size"), CBTxt::Th("Max single upload (KB)") . ":");
		$return .= "</td>\n</tr>\n";
		
		// Current Item Count Usage (information field only):
		if (isset($user->cb_pgtotalitems) && $user->cb_pgtotalitems) {
			$quota = $user->cb_pgtotalitems;
		} else {
			$quota = "0";
		}
		$return .= "<tr>\n";
		$return .= "  <td class=\"titleCell\">" . CBTxt::Th("Current Items") . ":</td>\n";
		$return .= "  <td class=\"fieldCell\">";
		
		if ($ui == 2 && $testfeature) {
			$return .= "<input class=\"inputbox\" type=\"text\" name=\"".$this->_getPGparamName("cb_pgtotalitems")."\" mosReq=\"0\" mosLabel=\"" . htmlspecialchars(CBTxt::T("Current Items")) . "\" value=\"".(isset($user->cb_pgtotalitems) ? $user->cb_pgtotalitems : 0)."\" size=\"10\" />";
		} else {
			$return .= $quota;
		}
		$return .= getFieldIcons($ui, false, false, CBTxt::Th("Keeps track of number of stored items"), CBTxt::Th("Current Items").":");
		$return .= "</td>\n</tr>\n";
		
		// Current Item Size Usage (information field only):
		if (isset($user->cb_pgtotalsize) && $user->cb_pgtotalsize) {
			$quota = $user->cb_pgtotalsize;
		} else {
			$quota = "0";
		}
		$return .= "<tr>\n";
		$return .= "  <td class=\"titleCell\">" . CBTxt::Th("Current Storage") . ":</td>\n";
		$return .= "  <td class=\"fieldCell\">";
		if ($ui == 2 && $testfeature) {
			$return .= "<input class=\"inputbox\" type=\"text\" name=\"".$this->_getPGparamName("cb_pgtotalsize")."\" mosReq=\"0\" mosLabel=\"" . htmlspecialchars(CBTxt::T("Current Storage"))."\" value=\"".(isset($user->cb_pgtotalsize) ? $user->cb_pgtotalsize : 0)."\" size=\"10\" />";
		} else {
			$return .= $quota;
		}
		$return .= getFieldIcons($ui, false, false, CBTxt::Th("This field keeps track of the total size of all uploaded gallery items - like a quota usage field. Value is in bytes"), CBTxt::Th("Current Storage").":");
		$return .= "</td>\n</tr>\n";

		// Last Update (information field only):
		if (isset($user->cb_pglastupdate) && $user->cb_pglastupdate) {
			$quota = cbFormatDate($user->cb_pglastupdate);
		} else {
			$quota = _UE_NEVER;
		}
		$return .= "<tr>\n";
		$return .= "  <td class=\"titleCell\">" . CBTxt::Th("Last Update") . ":</td>\n";
		$return .= "  <td class=\"fieldCell\">";
		if ($ui == 2 && $testfeature) {
			$return .= "<input class=\"inputbox\" type=\"text\" name=\"".$this->_getPGparamName("cb_pglastupdate")."\" mosReq=\"0\" mosLabel=\"" . htmlspecialchars(CBTxt::T("Last Update")) . "\" value=\"".(isset($user->cb_pglastupdate) ? $user->cb_pglastupdate : null)."\" size=\"10\" />";

		} else {
			$return .= $quota;
		}
		$return .= getFieldIcons($ui, false, false, CBTxt::Th("Date of last update to Gallery items in this profile"), CBTxt::Th("Last Update") . ":");
		$return .= "</td>\n</tr>\n";
		
		// Display Format:
		if ($tabparams["pgallowdisplayformatoverride"] || $ui ==2 || $isModerator) {
			$displayFormat = array();
			$displayFormat[] = moscomprofilerHTML::makeOption( '', ' ' );
			$displayFormat[] = moscomprofilerHTML::makeOption( 'DF1', CBTxt::T("Pictures gallery list format")  );
			$displayFormat[] = moscomprofilerHTML::makeOption( 'DF2', CBTxt::T("File list format") );
			$displayFormat[] = moscomprofilerHTML::makeOption( 'DF3', CBTxt::T("Picture gallery list lightbox format") );
			$lists['_pg_DisplayFormat'] = moscomprofilerHTML::selectList( $displayFormat, $this->_getPGparamName("cb_pgdisplayformat"), 'class="inputbox" size="1" mosReq="0" mosLabel="' . htmlspecialchars(CBTxt::T("Display Format")) . '"', 'value', 'text', $user->cb_pgdisplayformat );
			$return .= "<tr>\n";
			$return .= "  <td class=\"titleCell\">" . CBTxt::Th("Display Format") . ":</td>\n";
			$return .= "  <td class=\"fieldCell\">". $lists['_pg_DisplayFormat'];
			$return .= getFieldIcons($ui, false, false, CBTxt::Th("Select Display Format to apply for gallery viewing."), CBTxt::Th("Display Format") . ":");
			$return .= "</td>\n</tr>\n";
		}
		
		// Access Mode:
		if ($tabparams["pgallowaccessmodeoverride"] || $ui ==2 || $isModerator) {
			$accessMode = array();
			$accessMode[] = moscomprofilerHTML::makeOption( '', ' ' );
			$accessMode[] = moscomprofilerHTML::makeOption( 'PUB', CBTxt::T("Allow Public Access")  );
			$accessMode[] = moscomprofilerHTML::makeOption( 'REG', CBTxt::T("Allow Registered Access") );
			$accessMode[] = moscomprofilerHTML::makeOption( 'CON', CBTxt::T("Allow Connections Access") );
			$accessMode[] = moscomprofilerHTML::makeOption( 'REG-S', CBTxt::T("Registered Stealth Access") );
			$accessMode[] = moscomprofilerHTML::makeOption( 'CON-S', CBTxt::T("Connections Stealth Access") );
			
			$lists['_pg_AccessMode'] = moscomprofilerHTML::selectList( $accessMode, $this->_getPGparamName("cb_pgaccessmode"), 'class="inputbox" size="1" mosReq="0" mosLabel="' . htmlspecialchars(CBTxt::T("Access Mode")) . '"', 'value', 'text', $user->cb_pgaccessmode );
			$return .= "<tr>\n";
			$return .= "  <td class=\"titleCell\">" . CBTxt::Th("Access Mode") . ":</td>\n";
			$return .= "  <td class=\"fieldCell\">". $lists['_pg_AccessMode'];
			$return .= getFieldIcons($ui, false, false, CBTxt::Th("Select desirable access mode: Public access, Registered users only, Connected users only, REG-S for Registered-stealth, CON-S for Connections-stealth"), CBTxt::Th("Access Mode") . ":");
			$return .= "</td>\n</tr>\n";
		}

		//$return .= "<tr>\n";
		//$return .= "  <td class=\"titleCell\">--- end ---</td>\n";
		//$return .= "  <td class=\"fieldCell\">--- end of programmed fields ! below are the standard cb fields: change fields to 0</td>\n";
		//$return .= "</tr>\n";
		$return .= "</table>\n";

		return $return;
	}

	/**
	* Saves the user edit tab postdata into the tab's permanent storage
	* @param object tab reflecting the tab database entry
	* @param object mosUser reflecting the user being displayed
	* @param int 1 for front-end, 2 for back-end
	* @param array _POST data for saving edited tab content as generated with getEditTab
	* @returns mixed : either string HTML for tab content, or false if ErrorMSG generated
	*/
	function saveEditTab($tab, &$user, $ui, $postdata) {
        global $_CB_framework;
        
		// Get all relevant tab parameters - these settings are global and set by the admin
		$tabparams = $this->_pgGetTabParameters($user);

        $isModerator=isModerator($_CB_framework->myId());

		// Short Greeting:
		$cb_pgshortgreeting = cbGetParam($_POST, "cb_pgshortgreeting", "");
		$user->cb_pgshortgreeting = cbUnEscapeSQL($cb_pgshortgreeting);
        				
		// Enable Gallery:
		if ($tabparams["pgallowuserenable"]=="1" || $ui==2 || $isModerator) {
			$cb_pgenable = cbGetParam($_POST, "cb_pgenable", null);
			if ($cb_pgenable) {
				$user->cb_pgenable = $cb_pgenable;
			}
		}
		
		// Enable Autopublish Items:
		if ($tabparams["pgallowautopublishoverride"] || $ui==2 || $isModerator) {
			if ($tabparams["pgautopublish"]) {
				$cb_pgautopublish = cbGetParam($_POST, "cb_pgautopublish", null);
				if ($cb_pgautopublish) {
					$user->cb_pgautopublish = $cb_pgautopublish;
				}
			}
		}

        // Auto-approve Items setting in backend or frontend for moderators:            
		if ( ($ui == 2 && $tabparams["pgallowautoapproveoverride"]) || ($isModerator && $tabparams["pgallowautoapproveoverride"]) ) {			
			$cb_pgautoapprove = cbGetParam($_POST, "cb_pgautoapprove", null);
			if ($cb_pgautoapprove) {
				$user->cb_pgautoapprove = $cb_pgautoapprove;
			}
		}

        /* Not needed, done by CB fields: */

		if ($ui == 2 || $isModerator) {	
			// Item Quota (setting only in backend and frontend for moderators):
			$cb_pgtotalquotaitems = cbGetParam($_POST, "cb_pgtotalquotaitems", null);
			if (is_numeric($cb_pgtotalquotaitems)) {
				$user->cb_pgtotalquotaitems = $cb_pgtotalquotaitems;
			}
		}
		
		if ($ui == 2 || $isModerator) {	
			// Storage Quota (setting only in backend and frontend for moderators):
			$cb_pgtotalquotasize = cbGetParam($_POST, "cb_pgtotalquotasize", null);
			if (is_numeric($cb_pgtotalquotasize)) {
				$user->cb_pgtotalquotasize = $cb_pgtotalquotasize;
			}
		}
		
		if ($ui == 2 || $isModerator) {	
			// Maximum Single Upload Size (setting only in backend and frontend for moderators):
			$cb_pguploadsize = cbGetParam($_POST, "cb_pguploadsize", null);
			if (is_numeric($cb_pguploadsize)) {
				$user->cb_pguploadsize = $cb_pguploadsize;
			}
		}
		
		if ($ui == 2 || $isModerator) {	
			// Current Item Count Usage (information field only):
			$cb_pgtotalitems = cbGetParam($_POST, "cb_pgtotalitems", null);
			if (is_numeric($cb_pgtotalitems)) {
				$user->cb_pgtotalitems = $cb_pgtotalitems;
			}
		}
		
		if ($ui == 2 || $isModerator) {	
			// Current Item Size Usage (information field only):
			$cb_pgtotalsize = cbGetParam($_POST, "cb_pgtotalsize", null);
			if (is_numeric($cb_pgtotalsize)) {
				$user->cb_pgtotalsize = $cb_pgtotalsize;
			}
		}

		if ($ui == 2 || $isModerator) {	
			// Last Update Date (information field only):
			$cb_pglastupdate = cbGetParam($_POST, "cb_pglastupdate", null);
			if ($cb_pglastupdate !== null) {
				$user->cb_pglastupdate = $cb_pglastupdate;
			}
		}

		// Access Mode:
		if ($tabparams["pgallowaccessmodeoverride"] || $ui ==2 || $isModerator) {
			$cb_pgaccessmode = cbGetParam($_POST, "cb_pgaccessmode", null);
			if ($cb_pgaccessmode) {
				$user->cb_pgaccessmode = $cb_pgaccessmode;
			}
		}
		// Display Format:
		if ($tabparams["pgallowdisplayformatoverride"] || $ui ==2 || $isModerator) {
			$cb_pgdisplayformat = cbGetParam($_POST, "cb_pgdisplayformat", null);
			if ($cb_pgdisplayformat) {
				$user->cb_pgdisplayformat = $cb_pgdisplayformat;
			}
		}
	}
	
	function getTabComponent($tab, $user, $ui, $postdata) {
		return "Hello World of Components!";
	}


	/**
	* UserBot Called when a user is deleted from backend (prepare future unregistration)
	* @param object mosUser reflecting the user being deleted
	* @param int 1 for successful deleting
	* @returns true if all is ok, or false if ErrorMSG generated
	* 
	*/
	function userDeleted($user, $success) {
		global $_CB_database,$_CB_framework;
		
		$PGItemAbsolutePath=$_CB_framework->GetCfg( 'live_site' ).'/images/comprofiler/plug_profilegallery/';
		$PGItemAbsoluteUserPath = $PGItemAbsolutePath . $user->id;
		
		$this->RemoveDirectory($PGItemAbsoluteUserPath);
		// silent please : print "Deleting user gallery folder ".$user->id;
		$sql="DELETE FROM #__comprofiler_plug_profilegallery WHERE userid=" . (int) $user->id;
		$_CB_database->SetQuery($sql);
		if (!$_CB_database->query()) {
			$this->_setErrorMSG("SQL error cb.profilegallery:userDeleted-1" . $_CB_database->stderr(true));
			return false;
		}
				
		return true;
	}
	
	/***********************************
		Author : M. Niyazi Yarar
		Created : February, 2006
		Description : Simply clean files 
		and removes the directory

		If any error occurs or for your suggestions,
		please send me e-mail
	***********************************/
	function ClearDirectory($path){
   		if ( false != ( $dir_handle = @opendir( $path) ) ) {    
       		while ( false != ( $file = readdir( $dir_handle) ) ) {    
          		if($file == "." || $file == ".."){
              		if(!@unlink($path."/".$file)){
                  		continue;
              		}                
          		}else{
              		@unlink($path."/".$file);
           		}
			}
			closedir($dir_handle);
			return true;
			// all files deleted
   		}else{
       		return false;
			// directory doesn?t exist
		}		    
	}
	
	function RemoveDirectory($path){
		if($this->ClearDirectory($path)){
			if(rmdir($path)){
				return true;
				// directory removed
			}else{
				return false;
				// directory couldn?t removed
			}
		}else{
			return false;
			// no empty directory
		}
	}

	
}	// end class getForumTab.
?>