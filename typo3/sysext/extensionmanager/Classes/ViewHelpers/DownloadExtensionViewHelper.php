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
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Imaging\IconSize;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Extbase\Mvc\RequestInterface;
use TYPO3\CMS\Extbase\Mvc\Web\Routing\UriBuilder;
use TYPO3\CMS\Extensionmanager\Domain\Model\Extension;
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

    public function __construct(
        #[Autowire(expression: 'service("extension-configuration").get("extensionmanager", "automaticInstallation")')]
        private readonly string $automaticInstallation,
        private readonly IconFactory $iconFactory,
        private readonly UriBuilder $uriBuilder,
    ) {
        parent::__construct();
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
        /** @var RequestInterface $request */
        $request = $this->renderingContext->getAttribute(ServerRequestInterface::class);
        $this->uriBuilder->setRequest($request);
        $action = 'checkDependencies';
        $this->uriBuilder->reset();
        $this->uriBuilder->setFormat('json');
        $uri = $this->uriBuilder->uriFor($action, [
            'extension' => (int)$extension->getUid(),
        ], 'Download');
        $this->tag->addAttribute('data-href', $uri);

        $labelKeySuffix = $this->automaticInstallation ? '' : '.downloadOnly';
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

        $this->tag->setContent($label);
        return '<div id="' . htmlspecialchars($extension->getExtensionKey()) . '-downloadFromTer" class="downloadFromTer">' . $this->tag->render() . '</div>';
    }

    protected function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }
}
