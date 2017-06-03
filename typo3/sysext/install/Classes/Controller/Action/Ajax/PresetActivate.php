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

use TYPO3\CMS\Core\Configuration\ConfigurationManager;
use TYPO3\CMS\Install\Configuration\FeatureManager;
use TYPO3\CMS\Install\Status\InfoStatus;
use TYPO3\CMS\Install\Status\OkStatus;

/**
 * Activate a LocalConfiguration preset
 */
class PresetActivate extends AbstractAjaxAction
{
    /**
     * Executes the action
     *
     * @return array Rendered content
     */
    protected function executeAction(): array
    {
        $messages = [];
        $configurationManager = new ConfigurationManager();
        $featureManager = new FeatureManager();
        $configurationValues = $featureManager->getConfigurationForSelectedFeaturePresets($this->postValues['values']);
        if (!empty($configurationValues)) {
            $configurationManager->setLocalConfigurationValuesByPathValuePairs($configurationValues);
            $message = new OkStatus();
            $message->setTitle('Configuration written');
            $messageBody = [];
            foreach ($configurationValues as $configurationKey => $configurationValue) {
                $messageBody[] = '\'' . $configurationKey . '\' => \'' . $configurationValue . '\'';
            }
            $message->setMessage(implode('<br>', $messageBody));
        } else {
            $message = new InfoStatus();
            $message->setTitle('No configuration change selected');
        }
        $messages[] = $message;

        $this->view->assignMultiple([
            'success' => true,
            'status' => $messages,
        ]);
        return $this->view->render();
    }
}
