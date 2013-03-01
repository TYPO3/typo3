<?php
/*
 * @deprecated since 6.0, the classname TSpagegen and this file is obsolete
 * and will be removed with 6.2. The class was renamed and is now located at:
 * typo3/sysext/frontend/Classes/Page/PageGenerator.php
 */
require_once \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('frontend') . 'Classes/Page/PageGenerator.php';
/**
 * Class for fetching record relations for the frontend.
 *
 * @author Kasper Skårhøj <kasperYYYY@typo3.com>
 * @see tslib_cObj::RECORDS()
 * @deprecated since 6.1 will be removed in 6.3
 */
class FE_loadDBGroup extends \TYPO3\CMS\Core\Database\RelationHandler {

	/**
	 * @var boolean $fetchAllFields if false getFromDB() fetches only uid, pid, thumbnail and label fields (as defined in TCA)
	 */
	public $fetchAllFields = TRUE;

	/**
	 * Default constructor writes deprecation log.
	 */
	public function __construct() {
		\TYPO3\CMS\Core\Utility\GeneralUtility::deprecationLog(
			'Class FE_loadDBGroup is deprecated and unused since TYPO3 6.1. It will be removed with version 6.3.'
		);
	}

}

?>
