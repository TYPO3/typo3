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
 */
class FE_loadDBGroup extends \TYPO3\CMS\Core\Database\RelationHandler {

	// Means that everything is returned instead of only uid and label-field
	/**
	 * @todo Define visibility
	 */
	public $fromTC = 0;

}

?>