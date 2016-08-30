<?php
namespace TYPO3\CMS\Install\Controller\Action\Tool;

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

use TYPO3\CMS\Install\Controller\Action;

/**
 * Show configuration features and handle presets
 */
class Configuration extends Action\AbstractAction
{
    /**
     * @var \TYPO3\CMS\Install\Configuration\FeatureManager
     */
    protected $featureManager;

    /**
     * @var \TYPO3\CMS\Core\Configuration\ConfigurationManager
     */
    protected $configurationManager = null;

    /**
     * @param \TYPO3\CMS\Install\Configuration\FeatureManager $featureManager
     */
    public function injectFeatureManager(\TYPO3\CMS\Install\Configuration\FeatureManager $featureManager)
    {
        $this->featureManager = $featureManager;
    }

    /**
     * @param \TYPO3\CMS\Core\Configuration\ConfigurationManager $configurationManager
     */
    public function injectConfigurationManager(\TYPO3\CMS\Core\Configuration\ConfigurationManager $configurationManager)
    {
        $this->configurationManager = $configurationManager;
    }

    /**
     * Executes the tool
     *
     * @return string Rendered content
     */
    protected function executeAction()
    {
        $actionMessages = [];
        if (isset($this->postValues['set']['activate'])) {
            $actionMessages[] = $this->activate();
            $this->activate();
        }
        $this->view->assign('actionMessages', $actionMessages);

        $postValues = is_array($this->postValues['values']) ? $this->postValues['values'] : [];
        $this->view->assign('features', $this->featureManager->getInitializedFeatures($postValues));

        return $this->view->render();
    }

    /**
     * Configure selected feature presets to be active
     *
     * @return \TYPO3\CMS\Install\Status\StatusInterface
     */
    protected function activate()
    {
        $configurationValues = $this->featureManager->getConfigurationForSelectedFeaturePresets($this->postValues['values']);

        if (!empty($configurationValues)) {
            $this->configurationManager->setLocalConfigurationValuesByPathValuePairs($configurationValues);
            /** @var $message \TYPO3\CMS\Install\Status\StatusInterface */
            $message = $this->objectManager->get(\TYPO3\CMS\Install\Status\OkStatus::class);
            $message->setTitle('Configuration written');
            $messageBody = [];
            foreach ($configurationValues as $configurationKey => $configurationValue) {
                $messageBody[] = '\'' . $configurationKey . '\' => \'' . $configurationValue . '\'';
            }
            $message->setMessage(implode(LF, $messageBody));
        } else {
            /** @var $message \TYPO3\CMS\Install\Status\StatusInterface */
            $message = $this->objectManager->get(\TYPO3\CMS\Install\Status\InfoStatus::class);
            $message->setTitle('No configuration change selected');
        }
        return $message;
    }
}
