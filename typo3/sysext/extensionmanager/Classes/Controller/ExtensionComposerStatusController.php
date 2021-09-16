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
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Package\ComposerDeficitDetector;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Core\Utility\PathUtility;
use TYPO3\CMS\Extensionmanager\Service\ComposerManifestProposalGenerator;
use TYPO3\CMS\Extensionmanager\Utility\ListUtility;

/**
 * Provide information about extensions' composer status
 *
 * @internal This class is a specific controller implementation and is not considered part of the Public TYPO3 API.
 */
class ExtensionComposerStatusController extends AbstractModuleController
{
    protected ComposerDeficitDetector $composerDeficitDetector;
    protected ComposerManifestProposalGenerator $composerManifestProposalGenerator;
    protected PageRenderer $pageRenderer;
    protected IconFactory $iconFactory;
    protected ListUtility $listUtility;

    public function __construct(
        ComposerDeficitDetector $composerDeficitDetector,
        ComposerManifestProposalGenerator $composerManifestProposalGenerator,
        PageRenderer $pageRenderer,
        IconFactory $iconFactory,
        ListUtility $listUtility
    ) {
        $this->composerDeficitDetector = $composerDeficitDetector;
        $this->composerManifestProposalGenerator = $composerManifestProposalGenerator;
        $this->pageRenderer = $pageRenderer;
        $this->iconFactory = $iconFactory;
        $this->listUtility = $listUtility;
    }

    public function listAction(): ResponseInterface
    {
        $extensions = [];
        $basePackagePath = Environment::getExtensionsPath() . '/';
        foreach ($this->composerDeficitDetector->getExtensionsWithComposerDeficit() as $extensionKey => $deficit) {
            $extensionPath = $basePackagePath . $extensionKey . '/';
            $extensions[$extensionKey] = [
                'deficit' => $deficit,
                'packagePath' => $extensionPath,
                'icon' => $this->getExtensionIcon($extensionPath),
                'detailLink' => $this->uriBuilder->reset()->uriFor('detail', [
                    'extensionKey' => $extensionKey,
                    'returnUrl' => $this->uriBuilder->reset()->uriFor('list'),
                ])
            ];
        }
        ksort($extensions);
        $this->view->assign('extensions', $this->listUtility->enrichExtensionsWithEmConfInformation($extensions));

        $moduleTemplate = $this->initializeModuleTemplate($this->request);
        $moduleTemplate->setContent($this->view->render());
        return $this->htmlResponse($moduleTemplate->renderContent());
    }

    public function detailAction(string $extensionKey): ResponseInterface
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

        $moduleTemplate = $this->initializeModuleTemplate($this->request);
        $buttonBar = $moduleTemplate->getDocHeaderComponent()->getButtonBar();
        $returnUrl = GeneralUtility::sanitizeLocalUrl((string)$this->request->getArgument('returnUrl'));
        $buttonBar->addButton(
            $buttonBar
                ->makeLinkButton()
                ->setHref($returnUrl)
                ->setClasses('typo3-goBack')
                ->setTitle($this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.goBack'))
                ->setIcon($this->iconFactory->getIcon('actions-view-go-back', Icon::SIZE_SMALL))
        );
        $moduleTemplate->setContent($this->view->render());
        return $this->htmlResponse($moduleTemplate->renderContent());
    }

    protected function getComposerManifestMarkup(string $extensionKey): string
    {
        $composerManifest = $this->composerManifestProposalGenerator->getComposerManifestProposal($extensionKey);
        if ($composerManifest === '') {
            return '';
        }
        $rows = MathUtility::forceIntegerInRange(count(explode(LF, $composerManifest)), 1, PHP_INT_MAX);

        if (ExtensionManagementUtility::isLoaded('t3editor')) {
            $this->pageRenderer->addCssFile('EXT:t3editor/Resources/Public/JavaScript/Contrib/codemirror/lib/codemirror.css');
            $this->pageRenderer->addCssFile('EXT:t3editor/Resources/Public/Css/t3editor.css');
            $this->pageRenderer->loadRequireJsModule('TYPO3/CMS/T3editor/Element/CodeMirrorElement');

            $codeMirrorConfig = [
                'label' => $extensionKey . ' > composer.json',
                'panel' => 'bottom',
                'mode' => 'codemirror/mode/javascript/javascript',
                'nolazyload' => 'true',
                'options' => GeneralUtility::jsonEncodeForHtmlAttribute([
                    'rows' => 'auto',
                    'format' => 'json'
                ], false),
            ];

            return '<typo3-t3editor-codemirror ' . GeneralUtility::implodeAttributes($codeMirrorConfig, true) . '>'
                . '<textarea ' . GeneralUtility::implodeAttributes(['rows' => (string)++$rows], true) . '>' . htmlspecialchars($composerManifest) . '</textarea>'
                . '</typo3-t3editor-codemirror>';
        }

        return '<textarea ' . GeneralUtility::implodeAttributes(['class' => 'form-control', 'rows' => (string)++$rows], true) . '>'
            . htmlspecialchars($composerManifest)
            . '</textarea>';
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
