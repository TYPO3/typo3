<?php
namespace TYPO3\CMS\Extensionmanager\Controller;

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

use TYPO3\CMS\Backend\Template\Components\ButtonBar;
use TYPO3\CMS\Backend\View\BackendTemplateView;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Extbase\Mvc\View\ViewInterface;
use TYPO3\CMS\Extensionmanager\Domain\Model\Extension;
use TYPO3\CMS\Extensionmanager\Exception\ExtensionManagerException;

/**
 * Controller for configuration related actions.
 */
class ConfigurationController extends AbstractModuleController
{
    /**
     * @var \TYPO3\CMS\Extensionmanager\Domain\Repository\ConfigurationItemRepository
     */
    protected $configurationItemRepository;

    /**
     * @var \TYPO3\CMS\Extensionmanager\Domain\Repository\ExtensionRepository
     */
    protected $extensionRepository;

    /**
     * @param \TYPO3\CMS\Extensionmanager\Domain\Repository\ConfigurationItemRepository $configurationItemRepository
     */
    public function injectConfigurationItemRepository(\TYPO3\CMS\Extensionmanager\Domain\Repository\ConfigurationItemRepository $configurationItemRepository)
    {
        $this->configurationItemRepository = $configurationItemRepository;
    }

    /**
     * @param \TYPO3\CMS\Extensionmanager\Domain\Repository\ExtensionRepository $extensionRepository
     */
    public function injectExtensionRepository(\TYPO3\CMS\Extensionmanager\Domain\Repository\ExtensionRepository $extensionRepository)
    {
        $this->extensionRepository = $extensionRepository;
    }

    /**
     * Set up the doc header properly here
     *
     * @param ViewInterface $view
     */
    protected function initializeView(ViewInterface $view)
    {
        if ($view instanceof BackendTemplateView) {
            /** @var BackendTemplateView $view */
            parent::initializeView($view);
            $this->generateMenu();
            $this->registerDocheaderButtons();
        }
    }

    /**
     * Show the extension configuration form. The whole form field handling is done
     * in the corresponding view helper
     *
     * @param array $extension Extension information, must contain at least the key
     * @throws \TYPO3\CMS\Extensionmanager\Exception\ExtensionManagerException
     */
    public function showConfigurationFormAction(array $extension)
    {
        if (!isset($extension['key'])) {
            throw new ExtensionManagerException('Extension key not found.', 1359206803);
        }
        $this->handleTriggerArguments();

        $extKey = $extension['key'];
        $configuration = $this->configurationItemRepository->findByExtensionKey($extKey);
        if ($configuration) {
            $this->view
                ->assign('configuration', $configuration)
                ->assign('extension', $extension);
        } else {
            throw new ExtensionManagerException('The extension ' . $extKey . ' has no configuration.', 1476047775);
        }
    }

    /**
     * Save configuration and redirects back to form
     * or to the show page of a distribution
     *
     * @param array $config The new extension configuration
     * @param string $extensionKey The extension key
     */
    public function saveAction(array $config, $extensionKey)
    {
        $this->saveConfiguration($config, $extensionKey);
        /** @var Extension $extension */
        $extension = $this->extensionRepository->findOneByCurrentVersionByExtensionKey($extensionKey);
        // Different handling for distribution installation
        if ($extension instanceof Extension &&
            $extension->getCategory() === Extension::DISTRIBUTION_CATEGORY
        ) {
            $this->redirect('show', 'Distribution', null, ['extension' => $extension->getUid()]);
        } else {
            $this->redirect('showConfigurationForm', null, null, [
                'extension' => [
                    'key' => $extensionKey
                ],
                self::TRIGGER_RefreshTopbar => true
            ]);
        }
    }

    /**
     * Saves new configuration and redirects back to list
     *
     * @param array $config
     * @param string $extensionKey
     */
    public function saveAndCloseAction(array $config, $extensionKey)
    {
        $this->saveConfiguration($config, $extensionKey);
        $this->redirect('index', 'List', null, [
            self::TRIGGER_RefreshTopbar => true
        ]);
    }

    /**
     * Emits a signal after the configuration file was written
     *
     * @param string $extensionKey
     * @param array $newConfiguration
     */
    protected function emitAfterExtensionConfigurationWriteSignal($extensionKey, array $newConfiguration)
    {
        $this->signalSlotDispatcher->dispatch(__CLASS__, 'afterExtensionConfigurationWrite', [$extensionKey, $newConfiguration, $this]);
    }

    /**
     * Merge and save new configuration
     *
     * @param array $config
     * @param $extensionKey
     */
    protected function saveConfiguration(array $config, $extensionKey)
    {
        /** @var $configurationUtility \TYPO3\CMS\Extensionmanager\Utility\ConfigurationUtility */
        $configurationUtility = $this->objectManager->get(\TYPO3\CMS\Extensionmanager\Utility\ConfigurationUtility::class);
        $newConfiguration = $configurationUtility->getCurrentConfiguration($extensionKey);
        \TYPO3\CMS\Core\Utility\ArrayUtility::mergeRecursiveWithOverrule($newConfiguration, $config);
        $configurationUtility->writeConfiguration(
            $configurationUtility->convertValuedToNestedConfiguration($newConfiguration),
            $extensionKey
        );
        $this->emitAfterExtensionConfigurationWriteSignal($extensionKey, $newConfiguration);
    }

    /**
     * Registers the Icons into the docheader
     *
     * @throws \InvalidArgumentException
     */
    protected function registerDocheaderButtons()
    {
        $moduleTemplate = $this->view->getModuleTemplate();
        $lang = $this->getLanguageService();

        /** @var ButtonBar $buttonBar */
        $buttonBar = $moduleTemplate->getDocHeaderComponent()->getButtonBar();

        $uriBuilder = $this->controllerContext->getUriBuilder();
        $uri = $uriBuilder->reset()->uriFor('index', [], 'List');

        $icon = $this->view->getModuleTemplate()->getIconFactory()->getIcon('actions-view-go-back', Icon::SIZE_SMALL);
        $goBackButton = $buttonBar->makeLinkButton()
            ->setHref($uri)
            ->setTitle($this->translate('extConfTemplate.backToList'))
            ->setIcon($icon);
        $buttonBar->addButton($goBackButton, ButtonBar::BUTTON_POSITION_LEFT);

        $saveSplitButton = $buttonBar->makeSplitButton();
        // SAVE button:
        $saveButton = $buttonBar->makeInputButton()
            ->setName('_savedok')
            ->setValue('1')
            ->setTitle($lang->sL('LLL:EXT:lang/Resources/Private/Language/locallang_core.xlf:rm.saveDoc'))
            ->setForm('configurationform')
            ->setIcon($moduleTemplate->getIconFactory()->getIcon('actions-document-save', Icon::SIZE_SMALL));
        $saveSplitButton->addItem($saveButton, true);

        // SAVE / CLOSE
        $saveAndCloseButton = $buttonBar->makeInputButton()
            ->setName('_saveandclosedok')
            ->setClasses('t3js-save-close')
            ->setValue('1')
            ->setTitle($lang->sL('LLL:EXT:lang/Resources/Private/Language/locallang_core.xlf:rm.saveCloseDoc'))
            ->setForm('configurationform')
            ->setIcon($moduleTemplate->getIconFactory()->getIcon(
                'actions-document-save-close',
                Icon::SIZE_SMALL
            ));
        $saveSplitButton->addItem($saveAndCloseButton);
        $buttonBar->addButton($saveSplitButton, ButtonBar::BUTTON_POSITION_LEFT, 2);
    }

    /**
     * @return \TYPO3\CMS\Lang\LanguageService
     */
    protected function getLanguageService()
    {
        return $GLOBALS['LANG'];
    }
}
