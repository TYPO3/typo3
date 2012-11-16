<?php
namespace TYPO3\CMS\SysNote;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2012 Georg Ringer <typo3@ringerge.org>
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/
/**
 * Render sys_notes
 *
 * @author Georg Ringer <typo3@ringerge.org>
 */
class SysNoteRenderer {

	/**
	 * Render sys_notes by pid
	 *
	 * @param string $pidList comma separated list of page ids
	 * @return string
	 */
	public function renderByPid($pidList) {
		/** @var $repository \TYPO3\CMS\SysNote\Domain\Repository\SysNoteRepository */
		$repository = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\SysNote\\Domain\\Repository\\SysNoteRepository');
		$notes = $repository->findAllByPidList($pidList);
		$out = '';
		if ($this->notesAvailable($notes)) {
			/** @var $fluidView \TYPO3\CMS\Fluid\View\StandaloneView */
			$fluidView = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Fluid\\View\\StandaloneView');
			$templatePathAndFilename = \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('sys_note', 'Resources/Private/Template/List.html');
			$fluidView->setTemplatePathAndFilename($templatePathAndFilename);
			$fluidView->assign('notes', $notes);
			$out = $fluidView->render();
		}
		return $out;
	}

	/**
	 * Check if notes are available
	 *
	 * @param array $notes All notes returned from repository
	 * @return boolean TRUE if there are one or more notes
	 */
	protected function notesAvailable(array $notes) {
		$notesAvailable = FALSE;
		if (count($notes) > 0) {
			$notesAvailable = TRUE;
		}
		return $notesAvailable;
	}

}


?>