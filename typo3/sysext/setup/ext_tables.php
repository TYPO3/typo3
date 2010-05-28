<?php
if (!defined ('TYPO3_MODE')) 	die ('Access denied.');

if (TYPO3_MODE=='BE')	{
	t3lib_extMgm::addModule('user','setup','after:task',t3lib_extMgm::extPath($_EXTKEY).'mod/');
	t3lib_extMgm::addLLrefForTCAdescr('_MOD_user_setup','EXT:setup/locallang_csh_mod.xml');
}

$GLOBALS['TYPO3_USER_SETTINGS'] = array(
	'ctrl' => array (
		'dividers2tabs' => 1
	),
	'columns' => array (
		'realName' => array(
			'type' => 'text',
			'label' => 'LLL:EXT:setup/mod/locallang.xml:beUser_realName',
			'table' => 'be_users',
			'csh' => 'beUser_realName',
		),
		'email' => array(
			'type' => 'text',
			'label' => 'LLL:EXT:setup/mod/locallang.xml:beUser_email',
			'table' => 'be_users',
			'csh' => 'beUser_email',
		),
		'emailMeAtLogin' => array(
			'type' => 'check',
			'label' => 'LLL:EXT:setup/mod/locallang.xml:emailMeAtLogin',
			'csh' => 'emailMeAtLogin',
		),
		'password' => array(
			'type' => 'password',
			'label' => 'LLL:EXT:setup/mod/locallang.xml:newPassword',
			'table' => 'be_users',
			'csh' => 'newPassword',
			'eval' => 'md5',
		),
		'password2' => array(
			'type' => 'password',
			'label' => 'LLL:EXT:setup/mod/locallang.xml:newPasswordAgain',
			'table' => 'be_users',
			'csh' => 'newPasswordAgain',
			'eval' => 'md5',
		),
		'lang' => array(
			'type' => 'select',
			'itemsProcFunc' => 'SC_mod_user_setup_index->renderLanguageSelect',
			'label' => 'LLL:EXT:setup/mod/locallang.xml:language',
			'csh' => 'language',
		),
		'condensedMode' => array(
			'type' => 'check',
			'label' => 'LLL:EXT:setup/mod/locallang.xml:condensedMode',
			'csh' => 'condensedMode',
		),
		'startModule' => array(
			'type' => 'select',
			'itemsProcFunc' => 'SC_mod_user_setup_index->renderStartModuleSelect',
			'label' => 'LLL:EXT:setup/mod/locallang.xml:startModule',
			'csh' => 'startModule',
		),
		'thumbnailsByDefault' => array(
			'type' => 'check',
			'label' => 'LLL:EXT:setup/mod/locallang.xml:showThumbs',
			'csh' => 'showThumbs',
		),
		'helpText' => array(
			'type' => 'check',
			'label' => 'LLL:EXT:setup/mod/locallang.xml:helpText',
			'csh' => 'helpText',
		),
		'edit_wideDocument' => array(
			'type' => 'check',
			'label' => 'LLL:EXT:setup/mod/locallang.xml:edit_wideDocument',
			'csh' => 'edit_wideDocument',
		),
		'edit_showFieldHelp' => array(
			'type' => 'select',
			'items' => array (
				'0' => 'LLL:EXT:setup/mod/locallang.xml:edit_showFieldHelp_none',
				'icon' => 'LLL:EXT:setup/mod/locallang.xml:edit_showFieldHelp_icon',
				'text' => 'LLL:EXT:setup/mod/locallang.xml:edit_showFieldHelp_message',
			),
			'label' => 'LLL:EXT:setup/mod/locallang.xml:edit_showFieldHelp',
			'csh' => 'edit_showFieldHelp',
		),
		'titleLen' => array(
			'type' => 'text',
			'label' => 'LLL:EXT:setup/mod/locallang.xml:maxTitleLen',
			'csh' => 'maxTitleLen',
		),

		'edit_RTE' => array(
			'type' => 'check',
			'label' => 'LLL:EXT:setup/mod/locallang.xml:edit_RTE',
			'csh' => 'edit_RTE',
		),
		'edit_docModuleUpload' => array(
			'type' => 'check',
			'label' => 'LLL:EXT:setup/mod/locallang.xml:edit_docModuleUpload',
			'csh' => 'edit_docModuleUpload',
		),
		'disableCMlayers' => array(
			'type' => 'check',
			'label' => 'LLL:EXT:setup/mod/locallang.xml:disableCMlayers',
			'csh' => 'disableCMlayers',
		),
		'copyLevels' => array(
			'type' => 'text',
			'label' => 'LLL:EXT:setup/mod/locallang.xml:copyLevels',
			'csh' => 'copyLevels',
		),
		'recursiveDelete' => array(
			'type' => 'check',
			'label' => 'LLL:EXT:setup/mod/locallang.xml:recursiveDelete',
			'csh' => 'recursiveDelete',
		),
		'simulate' => array(
			'type' => 'select',
			'itemsProcFunc' => 'SC_mod_user_setup_index->renderSimulateUserSelect',
			'access' => 'admin',
			'label' => 'LLL:EXT:setup/mod/locallang.xml:simulate',
			'csh' => 'simuser'
		),
		'enableFlashUploader' => array(
			'type' => 'check',
			'label' => 'LLL:EXT:setup/mod/locallang.xml:enableFlashUploader',
			'csh' => 'enableFlashUploader',
		),
		'resizeTextareas' => array(
			'type' => 'check',
			'label' => 'LLL:EXT:setup/mod/locallang.xml:resizeTextareas',
			'csh' => 'resizeTextareas',
		),
		'resizeTextareas_MaxHeight' => array(
			'type' => 'text',
			'label' => 'LLL:EXT:setup/mod/locallang.xml:resizeTextareas_MaxHeight',
			'csh' => 'resizeTextareas_MaxHeight',
		),
		'resizeTextareas_Flexible' => array(
			'type' => 'check',
			'label' => 'LLL:EXT:setup/mod/locallang.xml:resizeTextareas_Flexible',
			'csh' => 'resizeTextareas_Flexible',
		),
		'installToolEnableButton' => array(
			'type' => 'user',
			'label' => 'LLL:EXT:setup/mod/locallang.xml:enableInstallTool.label',
			'userFunc' => 'SC_mod_user_setup_index->renderInstallToolEnableFileButton',
			'access' => 'admin',
			'csh' => 'enableInstallTool'
		),
	),
	'showitem' => '--div--;LLL:EXT:setup/mod/locallang.xml:personal_data,realName,email,emailMeAtLogin,password,password2,lang,
			--div--;LLL:EXT:setup/mod/locallang.xml:opening,condensedMode,startModule,thumbnailsByDefault,helpText,edit_showFieldHelp,titleLen,
			--div--;LLL:EXT:setup/mod/locallang.xml:editFunctionsTab,edit_RTE,edit_wideDocument,edit_docModuleUpload,enableFlashUploader,resizeTextareas,resizeTextareas_MaxHeight,resizeTextareas_Flexible,disableCMlayers,copyLevels,recursiveDelete,
			--div--;LLL:EXT:setup/mod/locallang.xml:adminFunctions,simulate,installToolEnableButton'

);
?>