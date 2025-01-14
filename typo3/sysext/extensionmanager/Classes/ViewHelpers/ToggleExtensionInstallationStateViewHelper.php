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
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Imaging\IconSize;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Package\PackageManager;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\RequestInterface;
use TYPO3\CMS\Extbase\Mvc\Web\Routing\UriBuilder;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractTagBasedViewHelper;
use TYPO3Fluid\Fluid\Core\ViewHelper\TagBuilder;

/**
 * Render deactivate / activate extension link.
 *
 * @internal
 */
final class ToggleExtensionInstallationStateViewHelper extends AbstractTagBasedViewHelper
{
    /**
     * @var string
     */
    protected $tagName = 'form';

    public function initializeArguments(): void
    {
        parent::initializeArguments();
        $this->registerArgument('extension', 'array', '', true);
    }

    public function render(): string
    {
        if (Environment::isComposerMode()) {
            return '';
        }

        $extension = $this->arguments['extension'];
        $extension += [
            'installed' => false,
        ];
        // Early return if package is protected and can not be unloaded
        $packageManager = GeneralUtility::makeInstance(PackageManager::class);
        $package = $packageManager->getPackage($extension['key']);
        if ($package->isProtected()) {
            return '';
        }

        $uriBuilder = GeneralUtility::makeInstance(UriBuilder::class);
        /** @var RequestInterface $request */
        $request = $this->renderingContext->getAttribute(ServerRequestInterface::class);
        $uriBuilder->setRequest($request);
        $uri = $uriBuilder->reset()->uriFor(
            'toggleExtensionInstallationState',
            ['extensionKey' => $extension['key']],
            'Action'
        );
        $this->tag->addAttribute('action', $uri);
        $this->tag->addAttribute('method', 'post');

        $iconFactory = GeneralUtility::makeInstance(IconFactory::class);
        $buttonTagBuilder = new TagBuilder('button');
        $buttonTagBuilder->addAttribute('type', 'submit');
        $buttonTagBuilder->addAttribute('class', 'onClickMaskExtensionManager btn btn-default');
        $buttonTagBuilder->setContent($iconFactory->getIcon('actions-system-extension-' . ($extension['installed'] ? 'uninstall' : 'install'), IconSize::SMALL)->render());
        $buttonTagBuilder->addAttribute('title', htmlspecialchars($this->getLanguageService()->sL(
            'LLL:EXT:extensionmanager/Resources/Private/Language/locallang.xlf:extensionList.' . ($extension['installed'] ? 'deactivate' : 'activate')
        )));
        $this->tag->setContent($buttonTagBuilder->render());
        return $this->tag->render();
    }

    protected function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }
}
