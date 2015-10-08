<?php
namespace TYPO3\CMS\Dbal\ExtensionManager;

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

/**
 * Class that renders fields for the Extension Manager configuration.
 */
class MessageDisplay
{
    /**
     * Renders a message for EM.
     *
     * @return string
     */
    public function displayMessage()
    {
        $out = '
			<div>
				<div class="alert alert-info">
					<h4>PostgreSQL</h4>
					<div class="alert-body">
						If you use a PostgreSQL database, make sure to run SQL scripts located in<br />
						<tt>' . \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('dbal') . 'res/postgresql/</tt><br />
						to ensure best compatibility with TYPO3.
					</div>
				</div>
			</div>
		';
        return $out;
    }
}
