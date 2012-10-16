<?php

########################################################################
# Extension Manager/Repository config file for ext "about".
#
# Auto generated 16-10-2012 14:04
#
# Manual updates:
# Only the data in the array - everything else is removed by next
# writing. "version" and "dependencies" must not be touched!
########################################################################

$EM_CONF[$_EXTKEY] = array(
	'title' => 'Help>About',
	'description' => 'Shows info about TYPO3 and installed extensions.',
	'category' => 'module',
	'shy' => 1,
	'dependencies' => 'extbase,fluid',
	'conflicts' => '',
	'priority' => '',
	'loadOrder' => '',
	'module' => 'mod',
	'doNotLoadInFE' => 1,
	'state' => 'stable',
	'internal' => 0,
	'uploadfolder' => 0,
	'createDirs' => '',
	'modify_tables' => '',
	'clearCacheOnLoad' => 0,
	'lockType' => '',
	'author' => 'Kasper Skaarhoj',
	'author_email' => 'kasperYYYY@typo3.com',
	'author_company' => 'Curby Soft Multimedia',
	'CGLcompliance' => '',
	'CGLcompliance_note' => '',
	'version' => '4.7.5',
	'_md5_values_when_last_written' => 'a:18:{s:16:"ext_autoload.php";s:4:"f7a4";s:12:"ext_icon.gif";s:4:"f3ab";s:14:"ext_tables.php";s:4:"002c";s:38:"Classes/Controller/AboutController.php";s:4:"4ade";s:34:"Classes/Domain/Model/Extension.php";s:4:"aade";s:49:"Classes/Domain/Repository/ExtensionRepository.php";s:4:"2afe";s:43:"Classes/ViewHelpers/SkinImageViewHelper.php";s:4:"893f";s:38:"Resources/Private/Layouts/Default.html";s:4:"440b";s:37:"Resources/Private/Partials/About.html";s:4:"da2f";s:40:"Resources/Private/Partials/CoreTeam.html";s:4:"f320";s:39:"Resources/Private/Partials/Credits.html";s:4:"14f9";s:45:"Resources/Private/Partials/CustomContent.html";s:4:"64c0";s:40:"Resources/Private/Partials/Donation.html";s:4:"2702";s:42:"Resources/Private/Partials/Extensions.html";s:4:"2d49";s:49:"Resources/Private/Partials/ExternalLibraries.html";s:4:"0799";s:36:"Resources/Private/Partials/Logo.html";s:4:"0666";s:44:"Resources/Private/Templates/About/Index.html";s:4:"b9c8";s:48:"interfaces/interface.tx_about_customsections.php";s:4:"a1e1";}',
	'constraints' => array(
		'depends' => array(
			'php' => '5.3.0-0.0.0',
			'typo3' => '4.7.0-0.0.0',
			'extbase' => '1.4.0-0.0.0',
			'fluid' => '1.4.0-0.0.0',
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