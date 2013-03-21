<?php
if (!defined('TYPO3_MODE')) {
	die('Access denied.');
}
if (TYPO3_MODE == 'BE') {
	\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addModule('user', 'setup', 'after:task', \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath($_EXTKEY) . 'mod/');
	\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addLLrefForTCAdescr('_MOD_user_setup', 'EXT:setup/locallang_csh_mod.xml');
}
$GLOBALS['TYPO3_USER_SETTINGS'] = array(
	'ctrl' => array(
		'dividers2tabs' => 1
	),
	'columns' => array(
		'realName' => array(
			'type' => 'text',
			'label' => 'LLL:EXT:setup/mod/locallang.xml:beUser_realName',
			'table' => 'be_users',
			'csh' => 'beUser_realName'
		),
		'email' => array(
			'type' => 'text',
			'label' => 'LLL:EXT:setup/mod/locallang.xml:beUser_email',
			'table' => 'be_users',
			'csh' => 'beUser_email'
		),
		'emailMeAtLogin' => array(
			'type' => 'check',
			'label' => 'LLL:EXT:setup/mod/locallang.xml:emailMeAtLogin',
			'csh' => 'emailMeAtLogin'
		),
		'password' => array(
			'type' => 'password',
			'label' => 'LLL:EXT:setup/mod/locallang.xml:newPassword',
			'table' => 'be_users',
			'csh' => 'newPassword',
			'eval' => 'md5'
		),
		'password2' => array(
			'type' => 'password',
			'label' => 'LLL:EXT:setup/mod/locallang.xml:newPasswordAgain',
			'table' => 'be_users',
			'csh' => 'newPasswordAgain',
			'eval' => 'md5'
		),
		'lang' => array(
			'type' => 'select',
			'itemsProcFunc' => 'TYPO3\\CMS\\Setup\\Controller\\SetupModuleController->renderLanguageSelect',
			'label' => 'LLL:EXT:setup/mod/locallang.xml:language',
			'csh' => 'language'
		),
		'startModule' => array(
			'type' => 'select',
			'itemsProcFunc' => 'TYPO3\\CMS\\Setup\\Controller\\SetupModuleController->renderStartModuleSelect',
			'label' => 'LLL:EXT:setup/mod/locallang.xml:startModule',
			'csh' => 'startModule'
		),
		'thumbnailsByDefault' => array(
			'type' => 'check',
			'label' => 'LLL:EXT:setup/mod/locallang.xml:showThumbs',
			'csh' => 'showThumbs'
		),
		'edit_wideDocument' => array(
			'type' => 'check',
			'label' => 'LLL:EXT:setup/mod/locallang.xml:edit_wideDocument',
			'csh' => 'edit_wideDocument'
		),
		'titleLen' => array(
			'type' => 'text',
			'label' => 'LLL:EXT:setup/mod/locallang.xml:maxTitleLen',
			'csh' => 'maxTitleLen'
		),
		'edit_RTE' => array(
			'type' => 'check',
			'label' => 'LLL:EXT:setup/mod/locallang.xml:edit_RTE',
			'csh' => 'edit_RTE'
		),
		'edit_docModuleUpload' => array(
			'type' => 'check',
			'label' => 'LLL:EXT:setup/mod/locallang.xml:edit_docModuleUpload',
			'csh' => 'edit_docModuleUpload'
		),
		'showHiddenFilesAndFolders' => array(
			'type' => 'check',
			'label' => 'LLL:EXT:setup/mod/locallang.xml:showHiddenFilesAndFolders',
			'csh' => 'showHiddenFilesAndFolders'
		),
		'copyLevels' => array(
			'type' => 'text',
			'label' => 'LLL:EXT:setup/mod/locallang.xml:copyLevels',
			'csh' => 'copyLevels'
		),
		'recursiveDelete' => array(
			'type' => 'check',
			'label' => 'LLL:EXT:setup/mod/locallang.xml:recursiveDelete',
			'csh' => 'recursiveDelete'
		),
		'simulate' => array(
			'type' => 'select',
			'itemsProcFunc' => 'TYPO3\\CMS\\Setup\\Controller\\SetupModuleController->renderSimulateUserSelect',
			'label' => 'LLL:EXT:setup/mod/locallang.xml:simulate',
			'csh' => 'simuser'
		),
		'resetConfiguration' => array(
			'type' => 'button',
			'label' => 'LLL:EXT:setup/mod/locallang.xml:resetConfiguration',
			'buttonlabel' => 'LLL:EXT:setup/mod/locallang.xml:resetConfigurationShort',
			'csh' => 'reset',
			'onClick' => 'if (confirm(\'%s\')) { document.getElementById(\'setValuesToDefault\').value = 1; this.form.submit(); }',
			'onClickLabels' => array(
				'LLL:EXT:setup/mod/locallang.xml:setToStandardQuestion'
			)
		),
		'clearSessionVars' => array(
			'type' => 'button',
			'access' => 'admin',
			'label' => 'LLL:EXT:setup/mod/locallang.xml:clearSessionVars',
			'buttonlabel' => 'LLL:EXT:setup/mod/locallang.xml:clearSessionVarsShort',
			'csh' => 'reset',
			'onClick' => 'if (confirm(\'%s\')) { document.getElementById(\'clearSessionVars\').value = 1; this.form.submit(); }',
			'onClickLabels' => array(
				'LLL:EXT:setup/mod/locallang.xml:clearSessionVarsQuestion'
			)
		),
		'resizeTextareas' => array(
			'type' => 'check',
			'label' => 'LLL:EXT:setup/mod/locallang.xml:resizeTextareas',
			'csh' => 'resizeTextareas'
		),
		'resizeTextareas_Flexible' => array(
			'type' => 'check',
			'label' => 'LLL:EXT:setup/mod/locallang.xml:resizeTextareas_Flexible',
			'csh' => 'resizeTextareas_Flexible'
		),
		'resizeTextareas_MaxHeight' => array(
			'type' => 'text',
			'label' => 'LLL:EXT:setup/mod/locallang.xml:flexibleTextareas_MaxHeight',
			'csh' => 'flexibleTextareas_MaxHeight'
		),
		'debugInWindow' => array(
			'type' => 'check',
			'label' => 'LLL:EXT:setup/mod/locallang.xml:debugInWindow',
			'access' => 'admin'
		)
	),
	'showitem' => '--div--;LLL:EXT:setup/mod/locallang.xml:personal_data,realName,email,emailMeAtLogin,password,password2,lang,
			--div--;LLL:EXT:setup/mod/locallang.xml:opening,startModule,thumbnailsByDefault,titleLen,
			--div--;LLL:EXT:setup/mod/locallang.xml:editFunctionsTab,edit_RTE,edit_wideDocument,edit_docModuleUpload,showHiddenFilesAndFolders,resizeTextareas,resizeTextareas_Flexible,resizeTextareas_MaxHeight,copyLevels,recursiveDelete,resetConfiguration,clearSessionVars,
			--div--;LLL:EXT:setup/mod/locallang.xml:adminFunctions,simulate,debugInWindow'
);
?>