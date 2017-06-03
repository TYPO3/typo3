<?php
declare(strict_types=1);
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

use TYPO3\CMS\Core\FormProtection\FormProtectionFactory;
use TYPO3\CMS\Core\FormProtection\InstallToolFormProtection;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Install\Configuration\FeatureManager;
use TYPO3\CMS\Install\Controller\Action;
use TYPO3\CMS\Install\Service\LocalConfigurationValueService;

/**
 * About page
 */
class Settings extends Action\AbstractAction
{
    /**
     * @var FeatureManager
     */
    protected $featureManager;

    /**
     * @param FeatureManager $featureManager
     */
    public function __construct(FeatureManager $featureManager = null)
    {
        $this->featureManager = $featureManager ?: GeneralUtility::makeInstance(FeatureManager::class);
    }

    /**
     * Executes the tool
     *
     * @return string Rendered content
     */
    protected function executeAction(): string
    {
        $presetFeatures = $this->featureManager->getInitializedFeatures();
        $localConfigurationValueService = new LocalConfigurationValueService();
        $formProtection = FormProtectionFactory::get(InstallToolFormProtection::class);
        $this->view->assignMultiple([
            'changeInstallToolPasswordToken' => $formProtection->generateToken('installTool', 'changeInstallToolPassword'),

            'localConfigurationWriteToken' => $formProtection->generateToken('installTool', 'localConfigurationWrite'),
            'localConfigurationSectionNames' => $localConfigurationValueService->getSpeakingSectionNames(),
            'localConfigurationData' => $localConfigurationValueService->getCurrentConfigurationData(),

            'presetActivateToken' => $formProtection->generateToken('installTool', 'presetActivate'),
            'presetFeatures' => $presetFeatures,
        ]);
        return $this->view->render();
    }
}
