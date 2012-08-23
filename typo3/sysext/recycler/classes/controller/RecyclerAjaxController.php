<?php
namespace TYPO3\CMS\Recycler\Controller;

/**
 * Controller class for the 'recycler' extension. Handles the AJAX Requests
 *
 * @author 		Julian Kleinhans <typo3@kj187.de>
 * @author 	Erik Frister <erik_frister@otq-solutions.com>
 * @package 		TYPO3
 * @subpackage 	tx_recycler
 */
class RecyclerAjaxController {

	/**
	 * Stores the content for the ajax output
	 *
	 * @var 	string
	 */
	protected $content;

	/**
	 * Command to be processed
	 *
	 * @var 	string
	 */
	protected $command;

	/**
	 * Stores relevant data from extJS
	 * Example: Json format
	 * [ ["pages",1],["pages",2],["tt_content",34] ]
	 *
	 * @var 	string
	 */
	protected $data;

	/**
	 * Initialize method
	 *
	 * @return void
	 */
	public function init() {
		$this->mapCommand();
		$this->getContent();
	}

	/**
	 * Maps the command to the correct Model and View
	 *
	 * @return void
	 */
	public function mapCommand() {
		$this->command = \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('cmd');
		$this->data = \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('data');
		// check params
		if (!is_string($this->command)) {
			// @TODO make devlog output
			return FALSE;
		}
		// Create content
		$this->createContent();
	}

	/**
	 * Creates the content
	 *
	 * @return void
	 */
	protected function createContent() {
		$str = '';
		switch ($this->command) {
		case 'getDeletedRecords':
			$table = \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('table') ? \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('table') : \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('tableDefault');
			$limit = \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('limit') ? (int) \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('limit') : (int) \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('pagingSizeDefault');
			$start = \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('start') ? (int) \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('start') : 0;
			$filter = \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('filterTxt') ? \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('filterTxt') : '';
			$startUid = \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('startUid') ? \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('startUid') : '';
			$depth = \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('depth') ? \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('depth') : '';
			$this->setDataInSession('tableSelection', $table);
			$model = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Recycler\\Domain\\Model\\DeletedRecords');
			$model->loadData($startUid, $table, $depth, ($start . ',') . $limit, $filter);
			$deletedRowsArray = $model->getDeletedRows();
			$model = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Recycler\\Domain\\Model\\DeletedRecords');
			$totalDeleted = $model->getTotalCount($startUid, $table, $depth, $filter);
			// load view
			$view = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Recycler\\Controller\\DeletedRecordsController');
			$str = $view->transform($deletedRowsArray, $totalDeleted);
			break;
		case 'doDelete':
			$str = FALSE;
			$model = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Recycler\\Domain\\Model\\DeletedRecords');
			if ($model->deleteData($this->data)) {
				$str = TRUE;
			}
			break;
		case 'doUndelete':
			$str = FALSE;
			$recursive = \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('recursive');
			$model = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Recycler\\Domain\\Model\\DeletedRecords');
			if ($model->undeleteData($this->data, $recursive)) {
				$str = TRUE;
			}
			break;
		case 'getTables':
			$depth = \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('depth') ? \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('depth') : 0;
			$startUid = \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('startUid') ? \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('startUid') : '';
			$this->setDataInSession('depthSelection', $depth);
			$model = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Recycler\\Domain\\Model\\Tables');
			$str = $model->getTables('json', 1, $startUid, $depth);
			break;
		default:
			$str = 'No command was recognized.';
			break;
		}
		$this->content = $str;
	}

	/**
	 * Returns the content that was created in the mapCommand method
	 *
	 * @return string
	 */
	public function getContent() {
		echo $this->content;
	}

	/**
	 * Sets data in the session of the current backend user.
	 *
	 * @param 	string		$identifier: The identifier to be used to set the data
	 * @param 	string		$data: The data to be stored in the session
	 * @return 	void
	 */
	protected function setDataInSession($identifier, $data) {
		$GLOBALS['BE_USER']->uc['tx_recycler'][$identifier] = $data;
		$GLOBALS['BE_USER']->writeUC();
	}

}


?>