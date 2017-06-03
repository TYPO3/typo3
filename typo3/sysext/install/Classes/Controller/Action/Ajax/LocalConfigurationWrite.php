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

use TYPO3\CMS\Install\Service\LocalConfigurationValueService;
use TYPO3\CMS\Install\Status\WarningStatus;

/**
 * Write values to LocalConfiguration
 */
class LocalConfigurationWrite extends AbstractAjaxAction
{
    /**
     * Executes the action
     *
     * @return array Rendered content
     */
    protected function executeAction(): array
    {
        if (!is_array($this->postValues['configurationValues']) || empty($this->postValues['configurationValues'])) {
            throw new \RuntimeException(
                'Expected value array not found',
                1502282283
            );
        }

        $localConfigurationValueService = new LocalConfigurationValueService();
        $messages = $localConfigurationValueService->updateLocalConfigurationValues($this->postValues['configurationValues']);

        if (empty($messages)) {
            $message = new WarningStatus();
            $message->setTitle('No values changed');
            $messages[] = $message;
        }

        $this->view->assignMultiple([
            'success' => true,
            'status' => $messages,
        ]);
        return $this->view->render();
    }
}
