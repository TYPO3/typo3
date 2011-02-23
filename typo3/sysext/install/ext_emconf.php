<?php

########################################################################
# Extension Manager/Repository config file for ext "install".
#
# Auto generated 23-02-2011 10:59
#
# Manual updates:
# Only the data in the array - everything else is removed by next
# writing. "version" and "dependencies" must not be touched!
########################################################################

$EM_CONF[$_EXTKEY] = array(
	'title' => 'Tools>Install',
	'description' => 'The Install Tool mounted as the module Tools>Install in TYPO3.',
	'category' => 'module',
	'shy' => 1,
	'dependencies' => '',
	'conflicts' => '',
	'priority' => '',
	'loadOrder' => '',
	'module' => 'mod',
	'state' => 'stable',
	'internal' => 0,
	'uploadfolder' => 0,
	'createDirs' => '',
	'modify_tables' => '',
	'clearCacheOnLoad' => 0,
	'lockType' => '',
	'author' => 'Kasper Skaarhoj',
	'author_email' => 'kasperYYYY@typo3.com',
	'author_company' => 'CURBY SOFT Multimedie',
	'CGLcompliance' => '',
	'CGLcompliance_note' => '',
	'version' => '0.1.1',
	'_md5_values_when_last_written' => 'a:136:{s:9:"ChangeLog";s:4:"b83f";s:16:"ext_autoload.php";s:4:"5612";s:12:"ext_icon.gif";s:4:"a403";s:17:"ext_localconf.php";s:4:"5de8";s:14:"ext_tables.php";s:4:"1dfd";s:16:"requirements.php";s:4:"0fd1";s:24:"Classes/Updates/Base.php";s:4:"b540";s:50:"Resources/Private/Templates/AlterPasswordForm.html";s:4:"b034";s:49:"Resources/Private/Templates/CheckImageMagick.html";s:4:"bbdb";s:42:"Resources/Private/Templates/CheckMail.html";s:4:"d8b6";s:58:"Resources/Private/Templates/CheckTheDatabaseAdminUser.html";s:4:"5194";s:54:"Resources/Private/Templates/CheckTheDatabaseCache.html";s:4:"d967";s:55:"Resources/Private/Templates/CheckTheDatabaseImport.html";s:4:"d323";s:53:"Resources/Private/Templates/CheckTheDatabaseMenu.html";s:4:"b6d4";s:51:"Resources/Private/Templates/CheckTheDatabaseUc.html";s:4:"18c3";s:47:"Resources/Private/Templates/CleanUpManager.html";s:4:"ce96";s:49:"Resources/Private/Templates/DisplayFieldComp.html";s:4:"128c";s:46:"Resources/Private/Templates/DisplayFields.html";s:4:"b882";s:51:"Resources/Private/Templates/DisplaySuggestions.html";s:4:"68e3";s:49:"Resources/Private/Templates/DisplayTwinImage.html";s:4:"79b0";s:51:"Resources/Private/Templates/GenerateConfigForm.html";s:4:"8b3c";s:69:"Resources/Private/Templates/GenerateUpdateDatabaseFormCheckboxes.html";s:4:"aee0";s:52:"Resources/Private/Templates/GetUpdateDbFormWrap.html";s:4:"727a";s:42:"Resources/Private/Templates/ImageMenu.html";s:4:"5f91";s:46:"Resources/Private/Templates/InitExtConfig.html";s:4:"a3d6";s:40:"Resources/Private/Templates/Install.html";s:4:"9742";s:44:"Resources/Private/Templates/Install_123.html";s:4:"0ffe";s:46:"Resources/Private/Templates/Install_login.html";s:4:"dc5c";s:42:"Resources/Private/Templates/LoginForm.html";s:4:"983a";s:47:"Resources/Private/Templates/PhpInformation.html";s:4:"6675";s:41:"Resources/Private/Templates/PrintAll.html";s:4:"fc8c";s:45:"Resources/Private/Templates/PrintSection.html";s:4:"ccde";s:45:"Resources/Private/Templates/SetupGeneral.html";s:4:"9362";s:43:"Resources/Private/Templates/StepHeader.html";s:4:"60fb";s:43:"Resources/Private/Templates/StepOutput.html";s:4:"7b62";s:46:"Resources/Private/Templates/Typo3ConfEdit.html";s:4:"d880";s:49:"Resources/Private/Templates/Typo3TempManager.html";s:4:"3cca";s:50:"Resources/Private/Templates/UpdateWizardParts.html";s:4:"bbb2";s:42:"Resources/Private/Templates/ViewArray.html";s:4:"89de";s:56:"Resources/Private/Templates/WriteToLocalConfControl.html";s:4:"bfa0";s:43:"Resources/Public/Images/body-background.jpg";s:4:"1344";s:51:"Resources/Public/Images/button-background-hover.jpg";s:4:"5317";s:45:"Resources/Public/Images/button-background.jpg";s:4:"b224";s:46:"Resources/Public/Images/content-background.jpg";s:4:"e4d2";s:42:"Resources/Public/Images/content-bottom.png";s:4:"9c7e";s:39:"Resources/Public/Images/content-top.png";s:4:"54e4";s:44:"Resources/Public/Images/input-background.gif";s:4:"d6ca";s:42:"Resources/Public/Images/login-icon-key.gif";s:4:"2e16";s:32:"Resources/Public/Images/logo.gif";s:4:"02c8";s:37:"Resources/Public/Images/menuAbout.png";s:4:"05df";s:42:"Resources/Public/Images/menuBackground.gif";s:4:"4e57";s:39:"Resources/Public/Images/menuCleanup.png";s:4:"cd67";s:38:"Resources/Public/Images/menuConfig.png";s:4:"ea25";s:40:"Resources/Public/Images/menuDatabase.png";s:4:"2d40";s:41:"Resources/Public/Images/menuExtConfig.png";s:4:"2561";s:38:"Resources/Public/Images/menuImages.png";s:4:"6479";s:39:"Resources/Public/Images/menuPhpinfo.png";s:4:"e536";s:45:"Resources/Public/Images/menuTypo3confEdit.png";s:4:"c816";s:41:"Resources/Public/Images/menuTypo3temp.png";s:4:"202b";s:38:"Resources/Public/Images/menuUpdate.png";s:4:"d90f";s:35:"Resources/Public/Images/numbers.png";s:4:"9f2c";s:38:"Resources/Public/Javascript/install.js";s:4:"e05f";s:40:"Resources/Public/Stylesheets/general.css";s:4:"e968";s:36:"Resources/Public/Stylesheets/ie6.css";s:4:"54a1";s:36:"Resources/Public/Stylesheets/ie7.css";s:4:"d963";s:40:"Resources/Public/Stylesheets/install.css";s:4:"a471";s:44:"Resources/Public/Stylesheets/install_123.css";s:4:"e74e";s:46:"Resources/Public/Stylesheets/install_login.css";s:4:"25f0";s:38:"Resources/Public/Stylesheets/reset.css";s:4:"38d7";s:24:"imgs/blackwhite_mask.gif";s:4:"010a";s:21:"imgs/combine_back.jpg";s:4:"11a9";s:21:"imgs/combine_mask.jpg";s:4:"717c";s:19:"imgs/copyrights.txt";s:4:"73db";s:18:"imgs/greenback.gif";s:4:"97da";s:14:"imgs/jesus.bmp";s:4:"4b17";s:14:"imgs/jesus.gif";s:4:"bf76";s:14:"imgs/jesus.jpg";s:4:"9e10";s:14:"imgs/jesus.pcx";s:4:"02d8";s:14:"imgs/jesus.png";s:4:"65b6";s:14:"imgs/jesus.tga";s:4:"320c";s:14:"imgs/jesus.tif";s:4:"c8f8";s:22:"imgs/jesus2_transp.gif";s:4:"5b11";s:22:"imgs/jesus2_transp.png";s:4:"0d3a";s:29:"imgs/pdf_from_imagemagick.pdf";s:4:"dfbb";s:21:"imgs/typo3logotype.ai";s:4:"9631";s:24:"mod/class.tx_install.php";s:4:"b438";s:29:"mod/class.tx_install_ajax.php";s:4:"06c9";s:32:"mod/class.tx_install_session.php";s:4:"bb25";s:13:"mod/clear.gif";s:4:"cc11";s:12:"mod/conf.php";s:4:"9b8b";s:15:"mod/install.gif";s:4:"fbaa";s:21:"mod/locallang_mod.xml";s:4:"ff83";s:48:"report/class.tx_install_report_installstatus.php";s:4:"3b04";s:20:"report/locallang.xml";s:4:"fb90";s:50:"updates/class.tx_coreupdates_addflexformstoacl.php";s:4:"530b";s:48:"updates/class.tx_coreupdates_charsetdefaults.php";s:4:"ccc2";s:46:"updates/class.tx_coreupdates_compatversion.php";s:4:"dad1";s:49:"updates/class.tx_coreupdates_compressionlevel.php";s:4:"3a92";s:41:"updates/class.tx_coreupdates_cscsplit.php";s:4:"da9f";s:48:"updates/class.tx_coreupdates_flagsfromsprite.php";s:4:"edb1";s:42:"updates/class.tx_coreupdates_imagelink.php";s:4:"99da";s:43:"updates/class.tx_coreupdates_imagescols.php";s:4:"d978";s:50:"updates/class.tx_coreupdates_installnewsysexts.php";s:4:"955b";s:47:"updates/class.tx_coreupdates_installsysexts.php";s:4:"a269";s:46:"updates/class.tx_coreupdates_mergeadvanced.php";s:4:"f14d";s:50:"updates/class.tx_coreupdates_migrateworkspaces.php";s:4:"4ea0";s:42:"updates/class.tx_coreupdates_notinmenu.php";s:4:"0a5c";s:48:"updates/class.tx_coreupdates_statictemplates.php";s:4:"9861";s:39:"updates/class.tx_coreupdates_t3skin.php";s:4:"479b";s:34:"verify_imgs/install_44f1273ab1.jpg";s:4:"1bb3";s:34:"verify_imgs/install_48784f637a.gif";s:4:"46c3";s:34:"verify_imgs/install_48784f637a.png";s:4:"0fe0";s:34:"verify_imgs/install_a8f7a333c8.gif";s:4:"c780";s:34:"verify_imgs/install_a8f7a333c8.png";s:4:"0016";s:34:"verify_imgs/install_d1fa76faad.gif";s:4:"339f";s:34:"verify_imgs/install_d1fa76faad.png";s:4:"1a13";s:34:"verify_imgs/install_f6b0cedc4d.gif";s:4:"c091";s:34:"verify_imgs/install_f6b0cedc4d.png";s:4:"9e83";s:34:"verify_imgs/install_fcaf26c521.jpg";s:4:"eddb";s:34:"verify_imgs/install_fe1e67e805.gif";s:4:"eb53";s:34:"verify_imgs/install_fe1e67e805.png";s:4:"4f3d";s:31:"verify_imgs/install_read_ai.jpg";s:4:"62db";s:32:"verify_imgs/install_read_bmp.jpg";s:4:"acbd";s:32:"verify_imgs/install_read_gif.jpg";s:4:"d6f9";s:32:"verify_imgs/install_read_jpg.jpg";s:4:"e377";s:32:"verify_imgs/install_read_pcx.jpg";s:4:"6254";s:32:"verify_imgs/install_read_pdf.jpg";s:4:"9eef";s:32:"verify_imgs/install_read_png.jpg";s:4:"d6f9";s:32:"verify_imgs/install_read_tga.jpg";s:4:"6254";s:32:"verify_imgs/install_read_tif.jpg";s:4:"6254";s:33:"verify_imgs/install_scale_gif.gif";s:4:"4557";s:33:"verify_imgs/install_scale_jpg.jpg";s:4:"3d81";s:33:"verify_imgs/install_scale_png.png";s:4:"6414";s:33:"verify_imgs/install_write_gif.gif";s:4:"667b";s:33:"verify_imgs/install_write_png.png";s:4:"785d";s:22:"verify_imgs/readme.txt";s:4:"35d9";}',
	'constraints' => array(
		'depends' => array(
			'php' => '5.1.0-0.0.0',
			'typo3' => '4.4.0-0.0.0',
		),
		'conflicts' => array(
		),
		'suggests' => array(
		),
	),
	'suggests' => array(
	),
);

?>