<?php
declare(strict_types=1);
namespace TYPO3\CMS\Install\Controller\Action\Ajax;

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

use TYPO3\CMS\Install\Service\ClearTableService;
use TYPO3\CMS\Install\Status\OkStatus;

/**
 * Truncate a given table via ClearTableService
 */
class ClearTablesClear extends AbstractAjaxAction
{
    /**
     * Executes the action
     *
     * @return array Rendered content
     * @throws \RuntimeException
     */
    protected function executeAction(): array
    {
        if (empty($this->postValues['table'])) {
            throw new \RuntimeException(
                'No table name given',
                1501944076
            );
        }

        (new ClearTableService())->clearSelectedTable($this->postValues['table']);
        $message = new OkStatus();
        $message->setTitle('Cleared table');
        $messages[] = $message;

        $this->view->assignMultiple([
            'success' => true,
            'status' => $messages,
        ]);
        return $this->view->render();
    }
}
