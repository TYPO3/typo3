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

namespace TYPO3\CMS\Core\Controller;

use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use TYPO3\CMS\Core\Core\RequestId;
use TYPO3\CMS\Core\Information\Typo3Information;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\View\ViewFactoryData;
use TYPO3\CMS\Core\View\ViewFactoryInterface;

/**
 * A class representing error messages shown on a page, rendered via fluid.
 * Classic Example: "No pages are found on rootlevel"
 */
#[Autoconfigure(public: true)]
readonly class ErrorPageController
{
    public function __construct(
        protected ViewFactoryInterface $viewFactory,
        protected RequestId $requestId,
        protected Typo3Information $typo3Information,
        protected ContentSecurityPolicy\PolicyRegistry $policyRegistry,
    ) {}

    /**
     * Renders the view and returns the content.
     */
    public function errorAction(string $title, string $message, int $errorCode = 0, ?int $httpStatusCode = null): string
    {
        $viewFactoryData = new ViewFactoryData(
            templateRootPaths: ['EXT:core/Resources/Private/Templates'],
        );
        $view = $this->viewFactory->create($viewFactoryData);
        $view->assignMultiple([
            'message' => $message,
            'title' => $title,
            'httpStatusCode' => $httpStatusCode,
            'errorCodeUrlPrefix' => Typo3Information::URL_EXCEPTION,
            'donationUrl' => Typo3Information::URL_DONATE,
            'errorCode' => $errorCode,
            'requestId' => GeneralUtility::makeInstance(RequestId::class),
            'copyrightYear' => $this->typo3Information->getCopyrightYear(),
        ]);
        $this->policyRegistry->appendMutationCollection(
            new ContentSecurityPolicy\MutationCollection(
                new ContentSecurityPolicy\Mutation(
                    ContentSecurityPolicy\MutationMode::Extend,
                    ContentSecurityPolicy\Directive::StyleSrcElem,
                    ContentSecurityPolicy\SourceKeyword::nonceProxy
                )
            )
        );
        return $view->render('ErrorPage/Error');
    }
}
