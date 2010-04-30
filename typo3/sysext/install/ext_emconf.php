<?php

########################################################################
# Extension Manager/Repository config file for ext "install".
#
# Auto generated 30-11-2009 00:44
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
	'_md5_values_when_last_written' => 'a:69:{s:9:"ChangeLog";s:4:"b83f";s:16:"ext_autoload.php";s:4:"d32b";s:12:"ext_icon.gif";s:4:"fbaa";s:17:"ext_localconf.php";s:4:"18c7";s:14:"ext_tables.php";s:4:"1dfd";s:16:"requirements.php";s:4:"0fd1";s:24:"imgs/blackwhite_mask.gif";s:4:"495e";s:21:"imgs/combine_back.jpg";s:4:"7f33";s:21:"imgs/combine_mask.jpg";s:4:"b4f6";s:19:"imgs/copyrights.txt";s:4:"73db";s:18:"imgs/greenback.gif";s:4:"4bfe";s:14:"imgs/jesus.bmp";s:4:"4b17";s:14:"imgs/jesus.gif";s:4:"bf76";s:14:"imgs/jesus.jpg";s:4:"9778";s:14:"imgs/jesus.pcx";s:4:"02d8";s:14:"imgs/jesus.png";s:4:"6782";s:14:"imgs/jesus.tga";s:4:"320c";s:14:"imgs/jesus.tif";s:4:"c8f8";s:22:"imgs/jesus2_transp.gif";s:4:"5b11";s:22:"imgs/jesus2_transp.png";s:4:"bf18";s:29:"imgs/pdf_from_imagemagick.pdf";s:4:"dfbb";s:21:"imgs/typo3logotype.ai";s:4:"9631";s:24:"mod/class.tx_install.php";s:4:"381a";s:29:"mod/class.tx_install.php.orig";s:4:"2f3b";s:29:"mod/class.tx_install_ajax.php";s:4:"238c";s:32:"mod/class.tx_install_session.php";s:4:"3ea0";s:13:"mod/clear.gif";s:4:"cc11";s:12:"mod/conf.php";s:4:"9b8b";s:15:"mod/install.css";s:4:"bec2";s:15:"mod/install.gif";s:4:"fbaa";s:14:"mod/install.js";s:4:"0370";s:21:"mod/locallang_mod.xml";s:4:"ff83";s:48:"report/class.tx_install_report_installstatus.php";s:4:"8ac9";s:20:"report/locallang.xml";s:4:"fb90";s:46:"updates/class.tx_coreupdates_compatversion.php";s:4:"0d6f";s:41:"updates/class.tx_coreupdates_cscsplit.php";s:4:"66ce";s:43:"updates/class.tx_coreupdates_imagescols.php";s:4:"fbc4";s:50:"updates/class.tx_coreupdates_installnewsysexts.php";s:4:"d8eb";s:47:"updates/class.tx_coreupdates_installsysexts.php";s:4:"a9b8";s:50:"updates/class.tx_coreupdates_installversioning.php";s:4:"e7bb";s:46:"updates/class.tx_coreupdates_mergeadvanced.php";s:4:"1ee9";s:42:"updates/class.tx_coreupdates_notinmenu.php";s:4:"5799";s:34:"verify_imgs/install_44f1273ab1.jpg";s:4:"1bb3";s:34:"verify_imgs/install_48784f637a.gif";s:4:"7a81";s:34:"verify_imgs/install_48784f637a.png";s:4:"0008";s:34:"verify_imgs/install_a8f7a333c8.gif";s:4:"2997";s:34:"verify_imgs/install_a8f7a333c8.png";s:4:"de3c";s:34:"verify_imgs/install_d1fa76faad.gif";s:4:"339f";s:34:"verify_imgs/install_d1fa76faad.png";s:4:"4b7e";s:34:"verify_imgs/install_f6b0cedc4d.gif";s:4:"c091";s:34:"verify_imgs/install_f6b0cedc4d.png";s:4:"f787";s:34:"verify_imgs/install_fcaf26c521.jpg";s:4:"32eb";s:34:"verify_imgs/install_fe1e67e805.gif";s:4:"8ff7";s:34:"verify_imgs/install_fe1e67e805.png";s:4:"2e7c";s:31:"verify_imgs/install_read_ai.jpg";s:4:"9878";s:32:"verify_imgs/install_read_bmp.jpg";s:4:"abc1";s:32:"verify_imgs/install_read_gif.jpg";s:4:"939b";s:32:"verify_imgs/install_read_jpg.jpg";s:4:"b66f";s:32:"verify_imgs/install_read_pcx.jpg";s:4:"1f03";s:32:"verify_imgs/install_read_pdf.jpg";s:4:"9d98";s:32:"verify_imgs/install_read_png.jpg";s:4:"939b";s:32:"verify_imgs/install_read_tga.jpg";s:4:"1f03";s:32:"verify_imgs/install_read_tif.jpg";s:4:"c64c";s:33:"verify_imgs/install_scale_gif.gif";s:4:"4557";s:33:"verify_imgs/install_scale_jpg.jpg";s:4:"3d81";s:33:"verify_imgs/install_scale_png.png";s:4:"aadd";s:33:"verify_imgs/install_write_gif.gif";s:4:"4956";s:33:"verify_imgs/install_write_png.png";s:4:"d644";s:22:"verify_imgs/readme.txt";s:4:"35d9";}',
	'constraints' => array(
		'depends' => array(
			'php' => '5.1.0-0.0.0',
			'typo3' => '4.3.0-0.0.0',
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
