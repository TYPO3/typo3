<?php
namespace TYPO3\CMS\SysNote\Controller;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2013 Kai Vogel <kai.vogel@speedprogs.de>, Speedprogs.de
 *
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 3 of the License, or
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
 * Note controller
 *
 * @author Kai Vogel <kai.vogel@speedprogs.de>
 */
class NoteController extends \TYPO3\CMS\Extbase\Mvc\Controller\ActionController {

	/**
	 * @var \TYPO3\CMS\SysNote\Domain\Repository\SysNoteRepository
	 * @inject
	 */
	protected $sysNoteRepository;

	/**
	 * @var \TYPO3\CMS\Extbase\Domain\Repository\BackendUserRepository
	 * @inject
	 */
	protected $backendUserRepository;

	/**
	 * Render notes by single PID or PID list
	 *
	 * @param string $pids Single PID or comma separated list of PIDs
	 * @return string
	 * @dontvalidate $pids
	 */
	public function listAction($pids) {
		if (empty($pids) || empty($GLOBALS['BE_USER']->user['uid'])) {
			return '';
		}
		$author = $this->backendUserRepository->findByUid($GLOBALS['BE_USER']->user['uid']);
		$notes = $this->sysNoteRepository->findByPidsAndAuthor($pids, $author);
		$this->view->assign('notes', $notes);
	}

}
?>