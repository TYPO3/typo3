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

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Imaging\IconSize;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\RequestInterface;
use TYPO3\CMS\Extbase\Mvc\Web\Routing\UriBuilder;
use TYPO3\CMS\Extensionmanager\Enum\ExtensionType;
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
        $this->registerArgument('extension', 'array', '', true);
    }

    public function render(): string
    {
        $extension = $this->arguments['extension'];
        $extensionKey = $extension['key'];
        $extensionType = ExtensionType::tryFrom($extension['type']);
        $iconFactory = GeneralUtility::makeInstance(IconFactory::class);
        if (
            ExtensionManagementUtility::isLoaded($extensionKey)
            || $extensionType === null
            || $extensionType === ExtensionType::System
        ) {
            return '<span class="btn btn-default disabled">' . $iconFactory->getIcon('empty-empty', IconSize::SMALL)->render() . '</span>';
        }
        $uriBuilder = GeneralUtility::makeInstance(UriBuilder::class);
        /** @var RequestInterface $request */
        $request = $this->renderingContext->getAttribute(ServerRequestInterface::class);
        $uriBuilder->setRequest($request);
        $uriBuilder->reset();
        $uriBuilder->setFormat('json');
        $uri = $uriBuilder->uriFor(
            'removeExtension',
            ['extension' => $extensionKey],
            'Action'
        );
        $this->tag->addAttribute('href', $uri);
        $this->tag->addAttribute('title', htmlspecialchars($this->getLanguageService()->sL(
            'LLL:EXT:extensionmanager/Resources/Private/Language/locallang.xlf:extensionList.remove'
        )));
        $this->tag->setContent($iconFactory->getIcon('actions-edit-delete', IconSize::SMALL)->render());
        return $this->tag->render();
    }

    protected function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }
}
