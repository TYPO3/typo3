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

use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Backend\Template\ModuleTemplate;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Imaging\IconSize;
use TYPO3\CMS\Core\Page\JavaScriptModuleInstruction;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extensionmanager\Package\ComposerDeficitDetector;
use TYPO3\CMS\Extensionmanager\Service\ComposerManifestProposalGenerator;

/**
 * Provide information about extension's composer status.
 *
 * @internal This class is a specific controller implementation and is not considered part of the Public TYPO3 API.
 */
class ExtensionComposerStatusController extends AbstractController
{
    protected string $returnUrl = '';

    public function __construct(
        protected readonly ComposerDeficitDetector $composerDeficitDetector,
        protected readonly ComposerManifestProposalGenerator $composerManifestProposalGenerator,
        protected readonly PageRenderer $pageRenderer,
        protected readonly IconFactory $iconFactory,
        protected readonly ExtensionConfiguration $extensionConfiguration
    ) {}

    protected function initializeAction(): void
    {
        parent::initializeAction();
        $this->settings['offlineMode'] = (bool)$this->extensionConfiguration->get('extensionmanager', 'offlineMode');
        // The returnUrl, given in the request contains the actual destination, e.g. reports module.
        // Since we need to forward it in all actions, we define it as class variable here.
        if ($this->request->hasArgument('returnUrl')) {
            $this->returnUrl = GeneralUtility::sanitizeLocalUrl(
                (string)$this->request->getArgument('returnUrl')
            );
        }
    }

    public function listAction(): ResponseInterface
    {
        $extensions = [];
        // Contains the return link to this action + the initial returnUrl, e.g. reports module
        $detailLinkReturnUrl = $this->uriBuilder->reset()->uriFor('list', array_filter(['returnUrl' => $this->returnUrl]));
        foreach ($this->composerDeficitDetector->getExtensionsWithComposerDeficit() as $extensionKey => $extensionInformation) {
            $extensionInformation['detailLink'] = $this->uriBuilder->reset()->uriFor('detail', [
                'extensionKey' => $extensionKey,
                'returnUrl' => $detailLinkReturnUrl,
            ]);
            $extensions[$extensionKey] = $extensionInformation;
        }
        ksort($extensions);
        $view = $this->initializeModuleTemplate($this->request);
        $this->registerDocHeaderButtons($view);
        $view->assign('extensions', $extensions);
        return $view->renderResponse('ExtensionComposerStatus/List');
    }

    public function detailAction(string $extensionKey): ResponseInterface
    {
        if ($extensionKey === '') {
            return $this->redirect('list');
        }
        $view = $this->initializeModuleTemplate($this->request);
        $this->registerDocHeaderButtons($view);
        $deficit = $this->composerDeficitDetector->checkExtensionComposerDeficit($extensionKey);
        $view->assignMultiple([
            'extensionKey' => $extensionKey,
            'deficit' => $deficit,
        ]);
        if ($deficit !== ComposerDeficitDetector::EXTENSION_COMPOSER_MANIFEST_VALID) {
            $view->assign('composerManifestMarkup', $this->getComposerManifestMarkup($extensionKey));
        }
        return $view->renderResponse('ExtensionComposerStatus/Detail');
    }

    protected function getComposerManifestMarkup(string $extensionKey): string
    {
        $composerManifest = $this->composerManifestProposalGenerator->getComposerManifestProposal($extensionKey);
        if ($composerManifest === '') {
            return '';
        }
        $this->pageRenderer->loadJavaScriptModule('@typo3/backend/code-editor/element/code-mirror-element.js');
        $codeMirrorConfig = [
            'label' => $extensionKey . ' > composer.json',
            'panel' => 'bottom',
            'mode' => GeneralUtility::jsonEncodeForHtmlAttribute(JavaScriptModuleInstruction::create('@codemirror/lang-json', 'json')->invoke(), false),
            'nolazyload' => 'true',
            'autoheight' => 'true',
        ];
        $textareaAttributes = [
            'rows' => (string)count(explode(LF, $composerManifest)),
            'class' => 'form-control',
        ];
        return '<typo3-t3editor-codemirror ' . GeneralUtility::implodeAttributes($codeMirrorConfig, true) . '>'
            . '<textarea ' . GeneralUtility::implodeAttributes($textareaAttributes, true) . '>' . htmlspecialchars($composerManifest) . '</textarea>'
            . '</typo3-t3editor-codemirror>';
    }

    protected function registerDocHeaderButtons(ModuleTemplate $view): void
    {
        $buttonBar = $view->getDocHeaderComponent()->getButtonBar();
        if ($this->returnUrl !== '') {
            // Add "Go back" in case a return url is defined
            $buttonBar->addButton(
                $buttonBar
                    ->makeLinkButton()
                    ->setHref($this->returnUrl)
                    ->setClasses('typo3-goBack')
                    ->setTitle($this->translate('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.goBack'))
                    ->setShowLabelText(true)
                    ->setIcon($this->iconFactory->getIcon('actions-view-go-back', IconSize::SMALL))
            );
        }
    }
}
