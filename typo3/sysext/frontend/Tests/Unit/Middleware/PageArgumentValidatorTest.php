<?php
declare(strict_types = 1);

namespace TYPO3\CMS\Frontend\Tests\Unit\Middleware;

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

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\NullLogger;
use TYPO3\CMS\Core\Http\Response;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Routing\PageArguments;
use TYPO3\CMS\Core\TimeTracker\TimeTracker;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;
use TYPO3\CMS\Frontend\Middleware\PageArgumentValidator;
use TYPO3\CMS\Frontend\Middleware\PageResolver;
use TYPO3\TestingFramework\Core\AccessibleObjectInterface;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class PageArgumentValidatorTest extends UnitTestCase
{
    /**
     * @var bool Reset singletons created by subject
     */
    protected $resetSingletonInstances = true;

    /**
     * @var TypoScriptFrontendController|AccessibleObjectInterface
     */
    protected $controller;

    /**
     * @var RequestHandlerInterface
     */
    protected $responseOutputHandler;

    /**
     * @var PageResolver|AccessibleObjectInterface
     */
    protected $subject;

    protected function setUp(): void
    {
        parent::setUp();
        $timeTrackerStub = new TimeTracker(false);
        GeneralUtility::setSingletonInstance(TimeTracker::class, $timeTrackerStub);
        $this->controller = $this->getAccessibleMock(TypoScriptFrontendController::class, ['dummy'], [], '', false);

        // A request handler which only runs through
        $this->responseOutputHandler = new class implements RequestHandlerInterface {
            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                return new Response();
            }
        };
    }

    /**
     * @test
     */
    public function givenCacheHashWithoutRequiredParametersTriggersRedirect(): void
    {
        $incomingUrl = 'https://example.com/lotus-flower/en/mr-magpie/bloom/?cHash=XYZ';
        $expectedResult = 'https://example.com/lotus-flower/en/mr-magpie/bloom/';
        $this->controller->id = 13;
        $this->controller->cHash = 'XYZ';

        $pageArguments = new PageArguments(13, '1', ['cHash' => 'XYZ'], ['parameter-from' => 'path']);

        $request = new ServerRequest($incomingUrl, 'GET');
        $request = $request->withAttribute('routing', $pageArguments);

        $subject = new PageArgumentValidator($this->controller);
        $subject->setLogger(new NullLogger());

        $response = $subject->process($request, $this->responseOutputHandler);
        static::assertEquals(308, $response->getStatusCode());
        static::assertEquals($expectedResult, $response->getHeader('Location')[0]);
    }

    /**
     * @test
     */
    public function givenCacheHashNotMatchingCalculatedCacheHashTriggers404(): void
    {
        $incomingUrl = 'https://example.com/lotus-flower/en/mr-magpie/bloom/?cHash=YAZ';
        $this->controller->id = 13;
        $this->controller->cHash = 'XYZ';

        $pageArguments = new PageArguments(13, '1', ['cHash' => 'XYZ', 'dynamic' => 'argument'], ['parameter-from' => 'path']);

        $request = new ServerRequest($incomingUrl, 'GET');
        $request = $request->withAttribute('routing', $pageArguments);

        $subject = new PageArgumentValidator($this->controller);
        $response = $subject->process($request, $this->responseOutputHandler);
        static::assertEquals(404, $response->getStatusCode());
    }
}
