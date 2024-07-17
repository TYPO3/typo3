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

namespace TYPO3\CMS\Extensionmanager\ViewHelpers;

use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Imaging\IconSize;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\RequestInterface;
use TYPO3\CMS\Extbase\Mvc\Web\Routing\UriBuilder;
use TYPO3\CMS\Extbase\Service\ExtensionService;
use TYPO3\CMS\Extensionmanager\Domain\Model\Extension;
use TYPO3\CMS\Fluid\Core\Rendering\RenderingContext;
use TYPO3\CMS\Fluid\ViewHelpers\Form\AbstractFormViewHelper;

/**
 * Render a link to download an extension.
 *
 * @internal
 */
final class DownloadExtensionViewHelper extends AbstractFormViewHelper
{
    /**
     * @var string
     */
    protected $tagName = 'form';

    protected ExtensionService $extensionService;
    protected IconFactory $iconFactory;

    public function injectExtensionService(ExtensionService $extensionService): void
    {
        $this->extensionService = $extensionService;
    }

    public function injectIconFactory(IconFactory $iconFactory): void
    {
        $this->iconFactory = $iconFactory;
    }

    public function initializeArguments(): void
    {
        parent::initializeArguments();
        $this->registerArgument('extension', Extension::class, '', true);
    }

    public function render(): string
    {
        /** @var Extension $extension */
        $extension = $this->arguments['extension'];
        $installPaths = Extension::returnInstallPaths();
        $pathSelector = '<ul class="extensionmanager-is-hidden">';
        foreach ($installPaths as $installPathType => $installPath) {
            /** @var string $installPathType */
            $pathSelector .= '<li>
                <input type="radio" id="' . htmlspecialchars($extension->getExtensionKey()) . '-downloadPath-' . htmlspecialchars($installPathType) . '" name="downloadPath" class="downloadPath" value="' . htmlspecialchars($installPathType) . '" ' . ($installPathType === 'Local' ? 'checked="checked"' : '') . ' />
                <label for="' . htmlspecialchars($extension->getExtensionKey()) . '-downloadPath-' . htmlspecialchars($installPathType) . '">' . htmlspecialchars($installPathType) . '</label>
            </li>';
        }
        $pathSelector .= '</ul>';
        $uriBuilder = GeneralUtility::makeInstance(UriBuilder::class);
        /** @var RenderingContext $renderingContext */
        $renderingContext = $this->renderingContext;
        /** @var RequestInterface $request */
        $request = $renderingContext->getRequest();
        $uriBuilder->setRequest($request);
        $action = 'checkDependencies';
        $uriBuilder->reset();
        $uriBuilder->setFormat('json');
        $uri = $uriBuilder->uriFor($action, [
            'extension' => (int)$extension->getUid(),
        ], 'Download');
        $this->tag->addAttribute('data-href', $uri);

        $automaticInstallation = (bool)GeneralUtility::makeInstance(ExtensionConfiguration::class)->get('extensionmanager', 'automaticInstallation');
        $labelKeySuffix = $automaticInstallation ? '' : '.downloadOnly';
        $titleAndValue = $this->getLanguageService()->sL(
            'LLL:EXT:extensionmanager/Resources/Private/Language/locallang.xlf:extensionList.downloadViewHelper.submit' . $labelKeySuffix
        );
        $label = '
            <div class="btn-group">
                <button
                    title="' . htmlspecialchars($titleAndValue) . '"
                    type="submit"
                    class="btn btn-default"
                    value="' . htmlspecialchars($titleAndValue) . '"
                >
                    ' . $this->iconFactory->getIcon('actions-download', IconSize::SMALL)->render() . '
                </button>
            </div>';

        $this->tag->setContent($label . $pathSelector);
        return '<div id="' . htmlspecialchars($extension->getExtensionKey()) . '-downloadFromTer" class="downloadFromTer">' . $this->tag->render() . '</div>';
    }

    protected function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }
}
