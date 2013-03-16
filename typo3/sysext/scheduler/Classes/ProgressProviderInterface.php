<?php
namespace TYPO3\CMS\Scheduler;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2012-2013 Ingo Renner <ingo@typo3.org>
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
 * Interface for tasks who can provide their progress
 *
 * @author Ingo Renner <ingo@typo3.org>
 */
interface ProgressProviderInterface {
	/**
	 * Gets the progress of a task.
	 *
	 * @return float Progress of the task as a two decimal precision float. f.e. 44.87
	 */
	public function getProgress();

}

?>