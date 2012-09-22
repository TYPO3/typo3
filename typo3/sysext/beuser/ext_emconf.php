<?php
/***************************************************************
* Extension Manager/Repository config file for ext "backend_users".
*
* Auto generated 01-04-2012 20:27
*
* Manual updates:
* Only the data in the array - everything else is removed by next
* writing. "version" and "dependencies" must not be touched!
***************************************************************/
$EM_CONF[$_EXTKEY] = array(
	'title' => 'Backend User Administration',
	'description' => 'Backend user administration and overview. Allows you to compare the settings of users and verify their permissions and see who is online.',
	'category' => 'module',
	'author' => 'Felix Kopp',
	'author_email' => 'felix-source@phorax.com',
	'author_company' => 'PHORAX',
	'shy' => '',
	'dependencies' => '',
	'priority' => '',
	'module' => 'mod',
	'state' => 'beta',
	'internal' => '',
	'uploadfolder' => 0,
	'createDirs' => '',
	'modify_tables' => '',
	'clearCacheOnLoad' => 0,
	'lockType' => '',
	'version' => '6.0.0',
	'constraints' => array(
		'depends' => array(
			'typo3' => '6.0.0-0.0.0',
		),
		'conflicts' => array(),
		'suggests' => array()
	),
	'suggests' => array(),
	'conflicts' => '',
	'_md5_values_when_last_written' => 'a:38:{s:12:"ext_icon.gif";s:4:"8f11";s:14:"ext_tables.php";s:4:"ceab";s:28:"ext_typoscript_constants.txt";s:4:"bc87";s:24:"ext_typoscript_setup.txt";s:4:"be94";s:13:"locallang.xlf";s:4:"71f2";s:44:"Classes/Controller/BackendUserController.php";s:4:"23ff";s:49:"Classes/Controller/BackendUserGroupController.php";s:4:"e27c";s:36:"Classes/Domain/Model/BackendUser.php";s:4:"4109";s:41:"Classes/Domain/Model/BackendUserGroup.php";s:4:"8df4";s:31:"Classes/Domain/Model/Demand.php";s:4:"a66a";s:56:"Classes/Domain/Repository/BackendUserGroupRepository.php";s:4:"4375";s:51:"Classes/Domain/Repository/BackendUserRepository.php";s:4:"f9b5";s:58:"Classes/Domain/Repository/BackendUserSessionRepository.php";s:4:"2b01";s:46:"Classes/ViewHelpers/IssueCommandViewHelper.php";s:4:"3f29";s:36:"Classes/ViewHelpers/SUViewHelper.php";s:4:"7912";s:51:"Classes/ViewHelpers/SpriteManagerIconViewHelper.php";s:4:"eccc";s:53:"Classes/ViewHelpers/SpriteManagerRecordViewHelper.php";s:4:"23f4";s:33:"Configuration/TCA/BackendUser.php";s:4:"7174";s:38:"Configuration/TCA/BackendUserGroup.php";s:4:"2d57";s:38:"Configuration/TypoScript/constants.txt";s:4:"9647";s:34:"Configuration/TypoScript/setup.txt";s:4:"f901";s:46:"Resources/Private/Backend/Layouts/Default.html";s:4:"b339";s:58:"Resources/Private/Backend/Templates/BackendUser/Index.html";s:4:"91cc";s:59:"Resources/Private/Backend/Templates/BackendUser/Online.html";s:4:"0d20";s:57:"Resources/Private/Backend/Templates/BackendUser/Show.html";s:4:"f0ef";s:63:"Resources/Private/Backend/Templates/BackendUserGroup/Index.html";s:4:"f2e2";s:40:"Resources/Private/Language/locallang.xlf";s:4:"51cc";s:40:"Resources/Private/Language/locallang.xml";s:4:"8556";s:46:"Resources/Private/Language/locallang_admin.xml";s:4:"76bd";s:85:"Resources/Private/Language/locallang_csh_Tx_Beuser_domain_model_backenduser.xml";s:4:"8333";s:90:"Resources/Private/Language/locallang_csh_Tx_Beuser_domain_model_backendusergroup.xml";s:4:"ed7e";s:43:"Resources/Private/Language/locallang_db.xml";s:4:"ef24";s:38:"Resources/Private/Layouts/Default.html";s:4:"96db";s:35:"Resources/Public/Icons/relation.gif";s:4:"e615";s:67:"Resources/Public/Icons/Tx_Beuser_domain_model_backenduser.gif";s:4:"1103";s:72:"Resources/Public/Icons/Tx_Beuser_domain_model_backendusergroup.gif";s:4:"1103";s:48:"Tests/Unit/Domain/Model/BackendUserGroupTest.php";s:4:"d5a1";s:43:"Tests/Unit/Domain/Model/BackendUserTest.php";s:4:"7c60";}'
);
?>