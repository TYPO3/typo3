<?php
if (!defined ('TYPO3_MODE')) die ('Access denied.');

/**
 * Add labels for context sensitive help (CSH)
 */
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addLLrefForTCAdescr('_MOD_web_BlogExampleTxBlogexampleM1', 'EXT:' . $_EXTKEY . '/Resources/Private/Language/locallang_csh.xml');

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addStaticFile($_EXTKEY, 'Configuration/TypoScript', 'BlogExample setup');
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addStaticFile($_EXTKEY, 'Configuration/TypoScript/DefaultStyles', 'BlogExample CSS Styles (optional)');

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::allowTableOnStandardPages('tx_blogexample_domain_model_blog');
$TCA['tx_blogexample_domain_model_blog'] = array (
	'ctrl' => array (
		'title'    => 'LLL:EXT:blog_example/Resources/Private/Language/locallang_db.xml:tx_blogexample_domain_model_blog',
		'label' => 'title',
		'tstamp' => 'tstamp',
		'crdate' => 'crdate',
		'versioningWS' => 2,
		'versioning_followPages' => true,
		'origUid' => 't3_origuid',
		'languageField' => 'sys_language_uid',
		'transOrigPointerField' => 'l18n_parent',
		'transOrigDiffSourceField' => 'l18n_diffsource',
		'delete' => 'deleted',
		'enablecolumns' => array(
			'disabled' => 'hidden'
			),
		'dynamicConfigFile' => \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath($_EXTKEY) . 'Configuration/TCA/Blog.php',
		'iconfile' => \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extRelPath($_EXTKEY) . 'Resources/Public/Icons/icon_tx_blogexample_domain_model_blog.gif'
	)
);

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::allowTableOnStandardPages('tx_blogexample_domain_model_post');
$TCA['tx_blogexample_domain_model_post'] = array (
	'ctrl' => array (
		'title'    => 'LLL:EXT:blog_example/Resources/Private/Language/locallang_db.xml:tx_blogexample_domain_model_post',
		'label' => 'title',
		'label_alt' => 'author',
		'label_alt_force' => TRUE,
		'tstamp'   => 'tstamp',
		'crdate'   => 'crdate',
		'versioningWS' => 2,
		'versioning_followPages' => true,
		'origUid' => 't3_origuid',
		'languageField' => 'sys_language_uid',
		'transOrigPointerField' => 'l18n_parent',
		'transOrigDiffSourceField' => 'l18n_diffsource',
		'delete'   => 'deleted',
		'enablecolumns'  => array(
			'disabled' => 'hidden'
		),
		'dynamicConfigFile' => \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath($_EXTKEY) . 'Configuration/TCA/Post.php',
		'iconfile'   => \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extRelPath($_EXTKEY) . 'Resources/Public/Icons/icon_tx_blogexample_domain_model_post.gif'
	)
);

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::allowTableOnStandardPages('tx_blogexample_domain_model_comment');
$TCA['tx_blogexample_domain_model_comment'] = array (
	'ctrl' => array (
		'title'    => 'LLL:EXT:blog_example/Resources/Private/Language/locallang_db.xml:tx_blogexample_domain_model_comment',
		'label' => 'date',
		'label_alt' => 'author',
		'label_alt_force' => TRUE,
		'tstamp'   => 'tstamp',
		'crdate'   => 'crdate',
		'delete'   => 'deleted',
		'enablecolumns'  => array (
			'disabled' => 'hidden'
		),
		'dynamicConfigFile' => \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath($_EXTKEY) . 'Configuration/TCA/Comment.php',
		'iconfile'   => \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extRelPath($_EXTKEY) . 'Resources/Public/Icons/icon_tx_blogexample_domain_model_comment.gif'
	)
);

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::allowTableOnStandardPages('tx_blogexample_domain_model_person');
$TCA['tx_blogexample_domain_model_person'] = array (
	'ctrl' => array (
		'title'    => 'LLL:EXT:blog_example/Resources/Private/Language/locallang_db.xml:tx_blogexample_domain_model_person',
		'label' => 'lastname',
		'label_alt' => 'firstname',
		'label_alt_force' => TRUE,
		'tstamp' => 'tstamp',
		'crdate' => 'crdate',
		'versioningWS' => 2,
		'versioning_followPages' => true,
		'origUid' => 't3_origuid',
		'prependAtCopy' => 'LLL:EXT:lang/locallang_general.xml:LGL.prependAtCopy',
		'delete' => 'deleted',
		'enablecolumns' => array(
			'disabled' => 'hidden'
			),
		'dynamicConfigFile' => \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath($_EXTKEY) . 'Configuration/TCA/Person.php',
		'iconfile' => \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extRelPath($_EXTKEY) . 'Resources/Public/Icons/icon_tx_blogexample_domain_model_person.gif'
	)
);

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::allowTableOnStandardPages('tx_blogexample_domain_model_tag');
$TCA['tx_blogexample_domain_model_tag'] = array (
	'ctrl' => array (
		'title'    => 'LLL:EXT:blog_example/Resources/Private/Language/locallang_db.xml:tx_blogexample_domain_model_tag',
		'label' => 'name',
		'tstamp'   => 'tstamp',
		'crdate'   => 'crdate',
		'delete'   => 'deleted',
		'enablecolumns'  => array (
			'disabled' => 'hidden'
		),
		'dynamicConfigFile' => \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath($_EXTKEY) . 'Configuration/TCA/Tag.php',
		'iconfile'   => \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extRelPath($_EXTKEY) . 'Resources/Public/Icons/icon_tx_blogexample_domain_model_tag.gif'
	)
);

if (is_array($TCA['fe_users']['columns']['tx_extbase_type'])) {
	$TCA['fe_users']['types']['Tx_BlogExample_Domain_Model_Administrator'] = $TCA['fe_users']['types']['0'];
	array_push($TCA['fe_users']['columns']['tx_extbase_type']['config']['items'], array('LLL:EXT:blog_example/Resources/Private/Language/locallang_db.xml:fe_users.tx_extbase_type.Tx_BlogExample_Domain_Model_Administrator', 'Tx_BlogExample_Domain_Model_Administrator'));
}

// Categorize Post records
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::makeCategorizable($_EXTKEY, 'tx_blogexample_domain_model_post');
?>