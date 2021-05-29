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

use ExtbaseTeam\ActionControllerTest\Controller\TestController;
use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Extbase\Mvc\Request;
use TYPO3\CMS\Extbase\Mvc\View\JsonView;
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
     * @var Request
     */
    protected $request;

    /**
     * @var ResponseInterface
     */
    protected $response;

    /**
     * @var TestController
     */
    protected $subject;

    /**
     * @var array
     */
    protected $testExtensionsToLoad = [
        'typo3/sysext/extbase/Tests/Functional/Mvc/Controller/Fixture/Extension/action_controller_test',
    ];

    protected function setUp(): void
    {
        parent::setUp();

        $this->request = new Request();
        $this->request->setPluginName('Pi1');
        $this->request->setControllerExtensionName('ActionControllerTest');
        $this->request->setControllerName('Test');
        $this->request->setFormat('html');

        $this->subject = $this->getContainer()->get(TestController::class);
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
        $this->subject->processRequest($this->request);

        // Assertions
        $arguments = $this->subject->getControllerContext()->getArguments();
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
        $this->subject->processRequest($this->request);

        // Assertions
        $arguments = $this->subject->getControllerContext()->getArguments();
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
        $reflectionClass = new \ReflectionClass($this->subject);
        $reflectionMethod = $reflectionClass->getProperty('defaultViewObjectName');
        $reflectionMethod->setAccessible(true);
        $reflectionMethod->setValue($this->subject, JsonView::class);

        $this->request->setControllerActionName('qux');

        // Test run
        $this->subject->processRequest($this->request);

        // Assertions
        $reflectionMethod = $reflectionClass->getProperty('view');
        $reflectionMethod->setAccessible(true);
        $reflectionMethod->getValue($this->subject);

        $view = $reflectionMethod->getValue($this->subject);
        self::assertInstanceOf(JsonView::class, $view);
    }
}
