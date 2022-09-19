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

use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\RequestInterface;
use TYPO3\CMS\Extbase\Mvc\Web\Routing\UriBuilder;
use TYPO3\CMS\Extensionmanager\Domain\Model\Extension;
use TYPO3\CMS\Fluid\Core\Rendering\RenderingContext;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractTagBasedViewHelper;

/**
 * ViewHelper for displaying a remove extension link.
 *
 * @internal
 */
final class RemoveExtensionViewHelper extends AbstractTagBasedViewHelper
{
    /**
     * @var string
     */
    protected $tagName = 'a';

    public function initializeArguments(): void
    {
        parent::initializeArguments();
        $this->registerUniversalTagAttributes();
        $this->registerArgument('extension', 'array', '', true);
    }

    public function render(): string
    {
        $extension = $this->arguments['extension'];
        $iconFactory = GeneralUtility::makeInstance(IconFactory::class);
        if (ExtensionManagementUtility::isLoaded($extension['key'])) {
            return '<span class="btn btn-default disabled">' . $iconFactory->getIcon('empty-empty', Icon::SIZE_SMALL)->render() . '</span>';
        }
        if (
            !in_array($extension['type'], Extension::returnAllowedInstallTypes()) ||
            $extension['type'] === 'System'
        ) {
            return '<span class="btn btn-default disabled">' . $iconFactory->getIcon('empty-empty', Icon::SIZE_SMALL)->render() . '</span>';
        }
        $uriBuilder = GeneralUtility::makeInstance(UriBuilder::class);
        /** @var RenderingContext $renderingContext */
        $renderingContext = $this->renderingContext;
        /** @var RequestInterface $request */
        $request = $renderingContext->getRequest();
        $uriBuilder->setRequest($request);
        $uriBuilder->reset();
        $uriBuilder->setFormat('json');
        $uri = $uriBuilder->uriFor(
            'removeExtension',
            ['extension' => $extension['key']],
            'Action'
        );
        $this->tag->addAttribute('href', $uri);
        $this->tag->addAttribute('class', $this->arguments['class']);
        $this->tag->addAttribute('title', htmlspecialchars($this->getLanguageService()->sL(
            'LLL:EXT:extensionmanager/Resources/Private/Language/locallang.xlf:extensionList.remove'
        )));
        $this->tag->setContent($iconFactory->getIcon('actions-edit-delete', Icon::SIZE_SMALL)->render());
        return $this->tag->render();
    }

    protected function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }
}
