<?php

declare(strict_types=1);

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

namespace TYPO3\CMS\Extensionmanager\Controller;

use TYPO3\CMS\Backend\Form\FormResultCompiler;
use TYPO3\CMS\Backend\Form\NodeFactory;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Package\ComposerDeficitDetector;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Core\Utility\PathUtility;
use TYPO3\CMS\Extbase\Mvc\View\ViewInterface;
use TYPO3\CMS\Extensionmanager\Service\ComposerManifestProposalGenerator;
use TYPO3\CMS\Extensionmanager\Utility\ListUtility;

/**
 * Provide information about extensions' composer status
 *
 * @internal This class is a specific controller implementation and is not considered part of the Public TYPO3 API.
 */
class ExtensionComposerStatusController extends AbstractModuleController
{
    /**
     * @var ComposerDeficitDetector
     */
    protected $composerDeficitDetector;

    /**
     * @var ComposerDeficitDetector
     */
    protected $composerManifestProposalGenerator;

    /**
     * @var NodeFactory
     */
    protected $nodeFactory;

    /**
     * @var ListUtility
     */
    protected $listUtility;

    /**
     * @var string
     */
    protected $returnUrl = '';

    public function __construct(
        ComposerDeficitDetector $composerDeficitDetector,
        ComposerManifestProposalGenerator $composerManifestProposalGenerator,
        NodeFactory $nodeFactory,
        ListUtility $listUtility
    ) {
        $this->composerDeficitDetector = $composerDeficitDetector;
        $this->composerManifestProposalGenerator = $composerManifestProposalGenerator;
        $this->nodeFactory = $nodeFactory;
        $this->listUtility = $listUtility;
    }

    protected function initializeAction(): void
    {
        parent::initializeAction();
        if ($this->request->hasArgument('returnUrl')) {
            $this->returnUrl = GeneralUtility::sanitizeLocalUrl(
                (string)$this->request->getArgument('returnUrl')
            );
        }
    }

    protected function initializeView(ViewInterface $view): void
    {
        parent::initializeView($view);
        $this->registerDocHeaderButtons();
    }

    public function listAction(): void
    {
        $extensions = [];
        $basePackagePath = Environment::getExtensionsPath() . '/';
        $detailLinkReturnUrl = $this->uriBuilder->reset()->uriFor('list', array_filter(['returnUrl' => $this->returnUrl]));
        foreach ($this->composerDeficitDetector->getExtensionsWithComposerDeficit() as $extensionKey => $deficit) {
            $extensionPath = $basePackagePath . $extensionKey . '/';
            $extensions[$extensionKey] = [
                'deficit' => $deficit,
                'packagePath' => $extensionPath,
                'icon' => $this->getExtensionIcon($extensionPath),
                'detailLink' => $this->uriBuilder->reset()->uriFor('detail', [
                    'extensionKey' => $extensionKey,
                    'returnUrl' => $detailLinkReturnUrl
                ])
            ];
        }
        ksort($extensions);
        $this->view->assign('extensions', $this->listUtility->enrichExtensionsWithEmConfInformation($extensions));
        $this->generateMenu();
    }

    public function detailAction(string $extensionKey): void
    {
        if ($extensionKey === '') {
            $this->redirect('list');
        }

        $deficit = $this->composerDeficitDetector->checkExtensionComposerDeficit($extensionKey);
        $this->view->assignMultiple([
            'extensionKey' => $extensionKey,
            'deficit' => $deficit
        ]);

        if ($deficit !== ComposerDeficitDetector::EXTENSION_COMPOSER_MANIFEST_VALID) {
            $this->view->assign('composerManifestMarkup', $this->getComposerManifestMarkup($extensionKey));
        }
    }

    protected function getComposerManifestMarkup(string $extensionKey): string
    {
        $formResultCompiler = GeneralUtility::makeInstance(FormResultCompiler::class);
        $composerManifest = $this->composerManifestProposalGenerator->getComposerManifestProposal($extensionKey);
        if ($composerManifest === '') {
            return '';
        }
        $rows = MathUtility::forceIntegerInRange(count(explode(LF, $composerManifest)), 1, PHP_INT_MAX);
        $fakeFieldTca = [
            'renderType' => 't3editor',
            'tableName' => $extensionKey,
            'fieldName' => 'composer.json',
            'effectivePid' => 0,
            'parameterArray' => [
                'itemFormElName' => 'composerManifest-' . $extensionKey,
                'itemFormElValue' => $composerManifest,
                'fieldChangeFunc' => [],
                'fieldConf' => [
                    'config' => [
                        'rows' => ++$rows,
                        'codeMirrorFirstLineNumber' => 1,
                    ]
                ]
            ]
        ];
        $resultArray = $this->nodeFactory->create($fakeFieldTca)->render();
        $formResultCompiler->mergeResult($resultArray);
        $formResultCompiler->addCssFiles();
        $formResultCompiler->printNeededJSFunctions();
        return $resultArray['html'];
    }

    protected function registerDocHeaderButtons(): void
    {
        $buttonBar = $this->view->getModuleTemplate()->getDocHeaderComponent()->getButtonBar();
        if ($this->returnUrl !== '') {
            $buttonBar->addButton(
                $buttonBar
                    ->makeLinkButton()
                    ->setHref($this->returnUrl)
                    ->setClasses('typo3-goBack')
                    ->setTitle($this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.goBack'))
                    ->setIcon($this->view->getModuleTemplate()->getIconFactory()->getIcon('actions-view-go-back', Icon::SIZE_SMALL))
            );
        }
    }

    protected function getExtensionIcon(string $extensionPath): string
    {
        $icon = ExtensionManagementUtility::getExtensionIcon($extensionPath);
        return $icon ? PathUtility::getAbsoluteWebPath($extensionPath . $icon) : '';
    }

    protected function getLanguageService()
    {
        return $GLOBALS['LANG'];
    }
}
