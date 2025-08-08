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

namespace TYPO3Tests\TestViewfactoryTarget\Service;

use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use TYPO3\CMS\Core\View\ViewFactoryData;
use TYPO3\CMS\Core\View\ViewFactoryInterface;

/**
 * Example service class that uses the ViewFactoryInterface to create
 * a new view instance. DI can then be used to inject alternative
 * views into that service, which is demonstrated by
 * EXT:test_viewfactory_customview and the related test case.
 *
 * Only needs to be public because we fetch the class directly in our
 * functional test
 */
#[Autoconfigure(public: true)]
final class TestService
{
    public function __construct(private readonly ViewFactoryInterface $viewFactory) {}

    public function renderSomething(string $template, array $variables): string
    {
        $view = $this->viewFactory->create(new ViewFactoryData());
        return $view->assignMultiple($variables)->render($template);
    }
}
