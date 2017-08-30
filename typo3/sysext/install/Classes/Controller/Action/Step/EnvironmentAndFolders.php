<?php
namespace TYPO3\CMS\Install\Controller\Action\Step;

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
use TYPO3\CMS\Core\Core\Bootstrap;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageQueue;
use TYPO3\CMS\Core\Package\PackageInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Install\FolderStructure\DefaultFactory;
use TYPO3\CMS\Install\Service\EnableFileService;
use TYPO3\CMS\Install\SystemEnvironment\Check;
use TYPO3\CMS\Install\SystemEnvironment\SetupCheck;

/**
 * Very first install step:
 * - Needs execution if typo3conf/LocalConfiguration.php does not exist
 * - Renders system environment output
 * - Creates folders like typo3temp, see FolderStructure/DefaultFactory for details
 * - Creates typo3conf/LocalConfiguration.php from factory
 */
class EnvironmentAndFolders extends AbstractStepAction
{
    /**
     * Execute environment and folder step:
     * - Create main folder structure
     * - Create typo3conf/LocalConfiguration.php
     *
     * @return FlashMessage[]
     */
    public function execute()
    {
        $folderStructureFactory = GeneralUtility::makeInstance(DefaultFactory::class);
        $structureFacade = $folderStructureFactory->getStructure();
        $structureFixMessageQueue = $structureFacade->fix();
        $errorsFromStructure = $structureFixMessageQueue->getAllMessages(FlashMessage::ERROR);

        if (@is_dir(PATH_typo3conf)) {
            $configurationManager = GeneralUtility::makeInstance(ConfigurationManager::class);
            $configurationManager->createLocalConfigurationFromFactoryConfiguration();

            // Create a PackageStates.php with all packages activated marked as "part of factory default"
            if (!file_exists(PATH_typo3conf . 'PackageStates.php')) {
                /** @var \TYPO3\CMS\Core\Package\FailsafePackageManager $packageManager */
                $packageManager = Bootstrap::getInstance()->getEarlyInstance(\TYPO3\CMS\Core\Package\PackageManager::class);
                $packages = $packageManager->getAvailablePackages();
                foreach ($packages as $package) {
                    if ($package instanceof PackageInterface
                        && $package->isPartOfFactoryDefault()
                    ) {
                        $packageManager->activatePackage($package->getPackageKey());
                    }
                }
                $packageManager->forceSortAndSavePackageStates();
            }

            // Create enable install tool file after typo3conf & LocalConfiguration were created
            $installToolService = GeneralUtility::makeInstance(EnableFileService::class);
            $installToolService->removeFirstInstallFile();
            $installToolService->createInstallToolEnableFile();
        }

        return $errorsFromStructure;
    }

    /**
     * Step needs to be executed if LocalConfiguration file does not exist.
     *
     * @return bool
     */
    public function needsExecution()
    {
        if (@is_file(PATH_typo3conf . 'LocalConfiguration.php')) {
            return false;
        }
        return true;
    }

    /**
     * Executes the step
     *
     * @return string Rendered content
     */
    protected function executeAction()
    {
        $systemCheckMessageQueue = new FlashMessageQueue('install');
        $checkMessages = (new Check())->getStatus();
        foreach ($checkMessages as $message) {
            $systemCheckMessageQueue->enqueue($message);
        }
        $setupCheckMessages = (new SetupCheck())->getStatus();
        foreach ($setupCheckMessages as $message) {
            $systemCheckMessageQueue->enqueue($message);
        }
        $environmentStatus = [
            'error' => $systemCheckMessageQueue->getAllMessages(FlashMessage::ERROR),
            'warning' => $systemCheckMessageQueue->getAllMessages(FlashMessage::WARNING),
        ];

        $folderStructureFactory = GeneralUtility::makeInstance(DefaultFactory::class);
        $structureFacade = $folderStructureFactory->getStructure();
        $structureMessageQueue = $structureFacade->getStatus();
        $structureErrors = $structureMessageQueue->getAllMessages(FlashMessage::ERROR);

        if (!empty($environmentStatus['error']) || !empty($environmentStatus['warning']) || !empty($structureErrors)) {
            $this->view->assign('errorsOrWarningsFromStatus', true);
        }

        $this->view->assignMultiple([
            'environmentStatus' => $environmentStatus,
            'structureErrors' => $structureErrors,
        ]);

        $this->assignSteps();

        return $this->view->render();
    }
}
