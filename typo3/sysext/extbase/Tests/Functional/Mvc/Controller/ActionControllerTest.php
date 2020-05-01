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

namespace TYPO3\CMS\Extbase\Tests\Functional\Mvc\Controller;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Request;
use TYPO3\CMS\Extbase\Mvc\Response;
use TYPO3\CMS\Extbase\Mvc\View\JsonView;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Extbase\Tests\Functional\Mvc\Controller\Fixture\Controller\TestController;
use TYPO3\CMS\Extbase\Tests\Functional\Mvc\Controller\Fixture\Validation\Validator\CustomValidator;
use TYPO3\CMS\Extbase\Validation\Validator\ConjunctionValidator;
use TYPO3\CMS\Extbase\Validation\Validator\NotEmptyValidator;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * Test case
 */
class ActionControllerTest extends FunctionalTestCase
{
    /**
     * @var \TYPO3\CMS\Extbase\Mvc\Request
     */
    protected $request;

    /**
     * @var \TYPO3\CMS\Extbase\Mvc\Response
     */
    protected $response;

    /**
     * @var \TYPO3\CMS\Extbase\Tests\Functional\Mvc\Controller\Fixture\Controller\TestController
     */
    protected $controller;

    protected function setUp(): void
    {
        parent::setUp();

        $objectManager = GeneralUtility::makeInstance(ObjectManager::class);

        $this->request = $objectManager->get(Request::class);
        $this->request->setPluginName('Pi1');
        $this->request->setControllerExtensionName('Extbase\\Tests\\Functional\\Mvc\\Controller\\Fixture');
        $this->request->setControllerName('Test');
        $this->request->setMethod('GET');
        $this->request->setFormat('html');

        $this->response = $objectManager->get(Response::class);

        $this->controller = $objectManager->get(TestController::class);
    }

    /**
     * @test
     */
    public function customValidatorsAreProperlyResolved()
    {
        // Setup
        $this->request->setControllerActionName('bar');
        $this->request->setArgument('barParam', '');

        // Test run
        $this->controller->processRequest($this->request, $this->response);

        // Assertions
        $arguments = $this->controller->getControllerContext()->getArguments();
        $argument = $arguments->getArgument('barParam');

        /** @var ConjunctionValidator $validator */
        $conjunctionValidator = $argument->getValidator();
        self::assertInstanceOf(ConjunctionValidator::class, $conjunctionValidator);

        /** @var \SplObjectStorage $validators */
        $validators = $conjunctionValidator->getValidators();
        self::assertInstanceOf(\SplObjectStorage::class, $validators);

        $validators->rewind();
        self::assertInstanceOf(CustomValidator::class, $validators->current());
    }

    /**
     * @test
     */
    public function extbaseValidatorsAreProperlyResolved()
    {
        // Setup
        $this->request->setControllerActionName('baz');
        $this->request->setArgument('bazParam', [ 'notEmpty' ]);

        // Test run
        $this->controller->processRequest($this->request, $this->response);

        // Assertions
        $arguments = $this->controller->getControllerContext()->getArguments();
        $argument = $arguments->getArgument('bazParam');

        /** @var ConjunctionValidator $validator */
        $conjunctionValidator = $argument->getValidator();
        self::assertInstanceOf(ConjunctionValidator::class, $conjunctionValidator);

        /** @var \SplObjectStorage $validators */
        $validators = $conjunctionValidator->getValidators();
        self::assertInstanceOf(\SplObjectStorage::class, $validators);
        self::assertCount(1, $validators);

        $validators->rewind();
        self::assertInstanceOf(NotEmptyValidator::class, $validators->current());
    }

    /**
     * @test
     */
    public function resolveViewRespectsDefaultViewObjectName()
    {
        // Test setup
        $reflectionClass = new \ReflectionClass($this->controller);
        $reflectionMethod = $reflectionClass->getProperty('defaultViewObjectName');
        $reflectionMethod->setAccessible(true);
        $reflectionMethod->setValue($this->controller, JsonView::class);

        $this->request->setControllerActionName('qux');

        // Test run
        $this->controller->processRequest($this->request, $this->response);

        // Assertions
        $reflectionMethod = $reflectionClass->getProperty('view');
        $reflectionMethod->setAccessible(true);
        $reflectionMethod->getValue($this->controller);

        $view = $reflectionMethod->getValue($this->controller);
        self::assertInstanceOf(JsonView::class, $view);
    }
}
