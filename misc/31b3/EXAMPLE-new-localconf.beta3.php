<?

	// Database-variables are set
	$typo_db = "t31_testsite";		// !!! ENTER DATABASE NAME HERE !!!
	$typo_db_username = "";			// !!! ENTER USERNAME TO DATABASE HERE !!!
	$typo_db_password = "";			// !!! ENTER PASSWORD TO DATABASE HERE !!!
	$typo_db_host = "localhost";
	$typo_db_extTableDef_script = "";	



/*	
******************
 TYPO3_CONF_VARS is a global array with configuration for the Typo3 libraries
******************
	See tslib/index_ts.php 	for frontend specific parameters
	See typo3/init.php  for backend specific parameters
	General options should appear in both scripts with same values

	THESE VARIABLES MAY BE OVERRIDDEN FROM WITHIN localcong.php

- "IM" is short for "ImageMagick", which is an external image manipulation package available from www.imagemagick.org. Version is ABSOLUTELY preferred to be 4.2.9, but may be 5+. See the install notes for Typo3!!
- "GD" is short for "GDLib/FreeType", which are libraries that MUST be compiled into PHP4. GDLib <=1.3 supports GIF, while the latest version 1.8.x supports only PNG. GDLib is available from www.boutell.com/gd/. Freetype has a link from there.


THIS IS SOME OF THE DEFAULT VALUES:

// TYPO3_CONF_VARS["GFX"]:		GENERAL (backend AND frontend), Configuration of the image processing features in Typo3!!
	$TYPO3_CONF_VARS["GFX"]["gif_compress"] = 1;					// boolean. Enables the use of the t3lib_div::gif_compress() workaround function for compressing giffiles made with GD or IM, which probably use only RLE or no compression at all.
	$TYPO3_CONF_VARS["GFX"]["gdlib"] = 1;							// boolean. Enables the use of GD
	$TYPO3_CONF_VARS["GFX"]["gdlib_png"] = 0;						// boolean. Enables the use of GD, featuring ONLY png. (version 1.8+). THIS MEANS that all items normally generated as gif-files will be png-files instead!!!
	$TYPO3_CONF_VARS["GFX"]["im"] = 1;								// boolean. Enables the use of IM
	$TYPO3_CONF_VARS["GFX"]["im_path"] = "/usr/X11R6/bin/";			// Path to the IM tools "convert", "combine", "identify". Version 4.2.9 of ImageMagick is HIGHLY recommended at this point, due to features and speed!
	$TYPO3_CONF_VARS["GFX"]["im_path_lzw"] = "/usr/bin/";			// Path to the IM tool "convert" with LZW enabled! See "gif_compress". If your version 4.2.9 of ImageMagick is compiled with LZW you may leave this field blank AND disable the flag "gif_compress" !!
	$TYPO3_CONF_VARS["GFX"]["im_version_5"] = 0;					// boolean. SET THIS if you're using IM 5+. If this is set, "im_negate_mask", "im_no_effects" and "im_mask_temp_ext_gif" is automatically configured for use with ImageMagick version 5 +

// TYPO3_CONF_VARS["SYS"]:		System related.	
	$TYPO3_CONF_VARS["SYS"]["sitename"] = "Typo3 testsite";			// Name of the base-site. This title shows up in the root of the tree-structure if you're an admin-backend user.
	$TYPO3_CONF_VARS["SYS"]["ddmmyy"] = "d-m-y";					// Format of Date-Month-Year - see php-function date()
	$TYPO3_CONF_VARS["SYS"]["hhmm"] = "H:i";						// Format of Hours-minutes - see php-function date()

// TYPO3_CONF_VARS["BE"]:		BACKEND
	$TYPO3_CONF_VARS["BE"]["unzip_path"] = "";						// Path to unzip.
	$TYPO3_CONF_VARS["BE"]["fileadminDir"] = "fileadmin/";			// Path to the fileadmin dir.
	$TYPO3_CONF_VARS["BE"]["lockRootPath"] = "";					// First part of the userHomePath/groupHomePath. OBSERVE that the first part of "userHomePath" and "groupHomePath" must be the value of "lockRootPath"!. Eg. "/home/typo3/"
	$TYPO3_CONF_VARS["BE"]["userHomePath"] = "";					// Path to the directory where Typo3 backend-users have their home-dirs.  Eg. "/home/typo3/users/". A home for backend user 2 would be: "/home/typo3/users/2/"
	$TYPO3_CONF_VARS["BE"]["groupHomePath"] = "";					// Path to the directory where Typo3 backend-groups have their home-dirs. Remember that the first part of this path must be "lockRootPath". Eg. "/home/typo3/groups/". A home for backend group 1 would be: "/home/typo3/groups/1/"
	$TYPO3_CONF_VARS["BE"]["warning_email_addr"] = "";				// Email-address that will recieve a warning if there has been failed logins 4 times within an hour

// TYPO3_CONF_VARS["FE"]:		FRONTEND, Configuration for the TypoScript frontend (FE). Nothing here relates to the administration backend!
	$TYPO3_CONF_VARS["FE"]["png_to_gif"] = 0;						// boolean. Enables conversion back to gif of all png-files generated in the frontend libraries. Notice that this leaves an increased number of temporarry files in typo3temp/
	$TYPO3_CONF_VARS["FE"]["tidy"] = 0;								// boolean. If set, the output html-code will be passed through "tidy" which is a little program you can get from http://www.w3.org/People/Raggett/tidy/. "Tidy" cleans the HTML-code for nice display! The feature does NOT work with Windows!
	$TYPO3_CONF_VARS["FE"]["logfile_dir"] = ""; 					// Path where Typo3 should write webserver-style logfiles to. This path must be write-enabled for the webserver. Doesn't work for Windows! Remember slash AFTER! Eg: "fileadmin/" or "/var/typo3logs/". Please see the TypoScript reference!
	$TYPO3_CONF_VARS["FE"]["debug"] = 0;							// If set, some HTML-comments are output in the end of the page. Can also be set by TypoScript.
*/


// ********************************************
// IMPORTANT CONFIGURATION OF THE IMAGE MANIPULATION:
//  - For description of each, see above
// ********************************************
		
	$TYPO3_CONF_VARS["GFX"]["gdlib"] = 1;					// CLEAR this flag to disable ANY use of GDLib, eg if not installed!
	$TYPO3_CONF_VARS["GFX"]["gdlib_png"] = 0;				// SET this flag to make Typo3 use PNG instead of GIF with GDLIB. ALL GDLib-related GIF-operations will be done with png-images instead!
	$TYPO3_CONF_VARS["GFX"]["im"] = 1;						// CLEAR this flag to disable ANY use of ImageMagick, eg. if not installed! 
	$TYPO3_CONF_VARS["GFX"]["im_version_5"] = 0;			// SET this if you use ImageMagick 5+ with "im_path" below
	$TYPO3_CONF_VARS["GFX"]["im_path"] = "/usr/X11R6/bin/";	// Path to ImageMagick 4.2.9 (for all manipulation)
	$TYPO3_CONF_VARS["GFX"]["im_path_lzw"] = "/usr/bin/";	// Path to ImageMagick 5+ (LZW-enabled for t3lib_div::gif_compress() function)


// **********************
// WINDOWS NT / ISS4
// **********************

	// WINDOWS: This is an example of how to set the path for imageMagick on Windows!
/*
	$TYPO3_CONF_VARS["GFX"]["im_path"]='d:\\www\\typo3\\download\\ImageMagick-nt\\ImageMagick-win2k\\';		
 	$TYPO3_CONF_VARS["GFX"]["im_path_lzw"]=$TYPO3_CONF_VARS["GFX"]["im_path"];
*/

?>