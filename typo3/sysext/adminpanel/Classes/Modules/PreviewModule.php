<?php
declare(strict_types=1);

namespace TYPO3\CMS\Adminpanel\Modules;

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

use TYPO3\CMS\Adminpanel\Repositories\FrontendGroupsRepository;
use TYPO3\CMS\Core\Type\Bitmask\Permission;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Fluid\View\StandaloneView;
use TYPO3\CMS\Frontend\Authentication\FrontendUserAuthentication;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;

/**
 * Admin Panel Preview Module
 */
class PreviewModule extends AbstractModule
{
    /**
     * Force the preview panel to be opened
     *
     * @var bool
     */
    protected $forceOpen = false;

    /**
     * @inheritdoc
     */
    public function getAdditionalJavaScriptCode(): string
    {
        return 'TSFEtypo3FormFieldSet("TSFE_ADMIN_PANEL[preview_simulateDate]", "datetime", "", 0, 0);';
    }

    /**
     * Creates the content for the "preview" section ("module") of the Admin Panel
     *
     * @return string HTML content for the section. Consists of a string with table-rows with four columns.
     * @see display()
     * @throws \InvalidArgumentException
     */
    public function getContent(): string
    {
        $view = GeneralUtility::makeInstance(StandaloneView::class);
        $templateNameAndPath = $this->extResources . '/Templates/Modules/Preview.html';
        $view->setTemplatePathAndFilename(GeneralUtility::getFileAbsFileName($templateNameAndPath));
        $view->setPartialRootPaths([$this->extResources . '/Partials']);

        $frontendGroupsRepository = GeneralUtility::makeInstance(FrontendGroupsRepository::class);

        $view->assignMultiple(
            [
                'show' => [
                    'hiddenPages' => $this->getConfigurationOption('showHiddenPages'),
                    'hiddenRecords' => $this->getConfigurationOption('showHiddenRecords'),
                    'fluidDebug' => $this->getConfigurationOption('showFluidDebug'),
                ],
                'simulateDate' => $this->getConfigurationOption('simulateDate'),
                'frontendUserGroups' => [
                    'availableGroups' => $frontendGroupsRepository->getAvailableFrontendUserGroups(),
                    'selected' => $this->getConfigurationOption('simulateUserGroup'),
                ],
            ]
        );
        return $view->render();
    }

    /**
     * @inheritdoc
     */
    public function getIdentifier(): string
    {
        return 'preview';
    }

    /**
     * @inheritdoc
     */
    public function getLabel(): string
    {
        $locallangFileAndPath = 'LLL:' . $this->extResources . '/Language/locallang_preview.xlf:module.label';
        return $this->getLanguageService()->sL($locallangFileAndPath);
    }

    public function initializeModule(): void
    {
        $this->initializeFrontendPreview();
        if (GeneralUtility::_GP('ADMCMD_simUser')) {
            $this->getBackendUser()->uc['TSFE_adminConfig']['preview_simulateUserGroup'] = (int)GeneralUtility::_GP(
                'ADMCMD_simUser'
            );
            $this->forceOpen = true;
        }
        if (GeneralUtility::_GP('ADMCMD_simTime')) {
            $this->getBackendUser()->uc['TSFE_adminConfig']['preview_simulateDate'] = (int)GeneralUtility::_GP(
                'ADMCMD_simTime'
            );
            $this->forceOpen = true;
        }
    }

    /**
     * Force module to be shown if either time or users/groups are simulated
     *
     * @return bool
     */
    public function isShown(): bool
    {
        if ($this->forceOpen) {
            return true;
        }
        return parent::isShown();
    }

    /**
     * Clear page cache if fluid debug output is enabled
     *
     * @param array $input
     */
    public function onSubmit(array $input): void
    {
        if ($input['preview_showFluidDebug'] ?? false) {
            $theStartId = (int)$this->getTypoScriptFrontendController()->id;
            $this->getTypoScriptFrontendController()
                ->clearPageCacheContent_pidList(
                    $this->getBackendUser()->extGetTreeList(
                        $theStartId,
                        0,
                        0,
                        $this->getBackendUser()->getPagePermsClause(Permission::PAGE_SHOW)
                    ) . $theStartId
                );
        }
    }

    /**
     * @inheritdoc
     */
    public function showFormSubmitButton(): bool
    {
        return true;
    }

    /**
     * @return TypoScriptFrontendController
     */
    protected function getTypoScriptFrontendController(): TypoScriptFrontendController
    {
        return $GLOBALS['TSFE'];
    }

    /**
     * Initialize frontend preview functionality incl.
     * simulation of users or time
     */
    protected function initializeFrontendPreview()
    {
        $tsfe = $this->getTypoScriptFrontendController();
        $tsfe->clear_preview();
        $tsfe->fePreview = 1;
        $tsfe->showHiddenPage = (bool)$this->getConfigurationOption('showHiddenPages');
        $tsfe->showHiddenRecords = (bool)$this->getConfigurationOption('showHiddenRecords');
        // Simulate date
        $simTime = $this->getConfigurationOption('simulateDate');
        if ($simTime) {
            $GLOBALS['SIM_EXEC_TIME'] = $simTime;
            $GLOBALS['SIM_ACCESS_TIME'] = $simTime - $simTime % 60;
        }
        // simulate user
        $tsfe->simUserGroup = $this->getConfigurationOption('simulateUserGroup');
        if ($tsfe->simUserGroup) {
            if ($tsfe->fe_user->user) {
                $tsfe->fe_user->user[$tsfe->fe_user->usergroup_column] = $tsfe->simUserGroup;
            } else {
                $tsfe->fe_user = GeneralUtility::makeInstance(FrontendUserAuthentication::class);
                $tsfe->fe_user->user = [
                    $tsfe->fe_user->usergroup_column => $tsfe->simUserGroup,
                ];
            }
        }
        if (!$tsfe->simUserGroup && !$simTime && !$tsfe->showHiddenPage && !$tsfe->showHiddenRecords) {
            $tsfe->fePreview = 0;
        }
    }
}
