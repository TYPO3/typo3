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

namespace ExtbaseTeam\ActionControllerTest\Controller;

use ExtbaseTeam\ActionControllerTest\Domain\Model\Model;
use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Core\Messaging\FlashMessageQueue;
use TYPO3\CMS\Extbase\Annotation as Extbase;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Extbase\Mvc\Controller\Arguments;
use TYPO3\CMS\Extbase\Mvc\RequestInterface;
use TYPO3\CMS\Extbase\Property\TypeConverter\PersistentObjectConverter;
use TYPO3Fluid\Fluid\View\ViewInterface;

/**
 * Fixture controller with some test actions and making some abstract controller things public.
 */
class TestController extends ActionController
{
    public $view;
    public $arguments;
    public $actionMethodName;
    public RequestInterface $request;

    public function getArguments(): Arguments
    {
        return $this->arguments;
    }

    public function renderAssetsForRequest($request): void
    {
        parent::renderAssetsForRequest($request);
    }

    public function initializeActionMethodArguments(): void
    {
        parent::initializeActionMethodArguments();
    }

    public function setViewConfiguration(ViewInterface $view): void
    {
        parent::setViewConfiguration($view);
    }

    public function getFlashMessageQueue(string $identifier = null): FlashMessageQueue
    {
        return parent::getFlashMessageQueue($identifier);
    }

    public function initializeFooAction(): void
    {
        $propertyMappingConfiguration = $this->arguments['fooParam']->getPropertyMappingConfiguration();
        $propertyMappingConfiguration->allowAllProperties();
        $propertyMappingConfiguration->setTypeConverterOption(
            PersistentObjectConverter::class,
            PersistentObjectConverter::CONFIGURATION_CREATION_ALLOWED,
            true
        );
    }

    public function fooAction(Model $fooParam): ResponseInterface
    {
        return $this->htmlResponse('');
    }

    /**
     * @Extbase\Validate("TYPO3.CMS.Extbase.Tests.Functional.Mvc.Controller.Fixture:CustomValidator", param="barParam")
     */
    public function barAction(string $barParam): ResponseInterface
    {
        return $this->htmlResponse('');
    }

    /**
     * @Extbase\Validate("NotEmpty", param="bazParam")
     */
    public function bazAction(array $bazParam): ResponseInterface
    {
        return $this->htmlResponse('');
    }

    public function quxAction(): ResponseInterface
    {
        return $this->htmlResponse('');
    }

    public function initializeActionMethodArgumentsTestActionOne(string $stringArgument, int $integerArgument, \stdClass $objectArgument): ResponseInterface
    {
        return $this->htmlResponse('');
    }

    public function initializeActionMethodArgumentsTestActionTwo(string $arg1, array $arg2 = [21], string $arg3 = 'foo'): ResponseInterface
    {
        return $this->htmlResponse('');
    }

    public function initializeActionMethodArgumentsTestActionThree($arg1): ResponseInterface
    {
        return $this->htmlResponse('');
    }
}
