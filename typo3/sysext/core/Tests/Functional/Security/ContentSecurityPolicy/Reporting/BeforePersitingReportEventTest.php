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

namespace TYPO3\CMS\Core\Tests\Functional\Security\ContentSecurityPolicy\Reporting;

use PHPUnit\Framework\Attributes\Test;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Symfony\Component\DependencyInjection\Container;
use TYPO3\CMS\Core\Configuration\Features;
use TYPO3\CMS\Core\Crypto\HashService;
use TYPO3\CMS\Core\EventDispatcher\EventDispatcher;
use TYPO3\CMS\Core\EventDispatcher\ListenerProvider;
use TYPO3\CMS\Core\Http\MiddlewareDispatcher;
use TYPO3\CMS\Core\Http\NormalizedParams;
use TYPO3\CMS\Core\Http\Response;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Http\Stream;
use TYPO3\CMS\Core\Middleware\AbstractContentSecurityPolicyReporter;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\Configuration\DispositionMapFactory;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\Event\BeforePersistingReportEvent;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\PolicyProvider;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\Reporting\Report;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\Reporting\ReportDetails;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\Reporting\ReportRepository;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\Reporting\ReportStatus;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\Scope;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class BeforePersitingReportEventTest extends FunctionalTestCase
{
    #[Test]
    public function beforePersistingReportEventCanModifyReport(): void
    {
        $beforePersistingReportEvent = null;

        /** @var Container $container */
        $container = $this->get('service_container');
        $container->set(
            'before-persisting-report-event-listener',
            static function (BeforePersistingReportEvent $event) use (&$beforePersistingReportEvent) {
                $beforePersistingReportEvent = $event;

                $report = new Report(
                    Scope::backend(),
                    ReportStatus::New,
                    1234567899,
                    [],
                    new ReportDetails(['document-uri' => 'https://example.org/modified', 'effective-directive' => 'script-src']),
                );

                $event->report = $report;
            }
        );
        $eventListener = $this->get(ListenerProvider::class);
        $eventListener->addListener(BeforePersistingReportEvent::class, 'before-persisting-report-event-listener');

        $response = $this->executeMiddlewareStack();

        self::assertInstanceOf(BeforePersistingReportEvent::class, $beforePersistingReportEvent);
        self::assertSame(201, $response->getStatusCode());

        // Retrieve the report that was just created
        $repository = $this->get(ReportRepository::class);
        /** @var Report[] $reports */
        $reports = $repository->findAll();
        self::assertCount(1, $reports);
        self::assertSame(1234567899, $reports[0]->requestTime); // Changed timestamp; original report would be 1771490230
        self::assertSame('https://example.org/modified', $reports[0]->details['document-uri']); // Changed URI
    }

    #[Test]
    public function beforePersistingReportEventCanDiscardReport(): void
    {
        $beforePersistingReportEvent = null;

        /** @var Container $container */
        $container = $this->get('service_container');
        $container->set(
            'before-persisting-report-event-listener',
            static function (BeforePersistingReportEvent $event) use (&$beforePersistingReportEvent) {
                $beforePersistingReportEvent = $event;
                $event->report = null;
            }
        );
        $eventListener = $this->get(ListenerProvider::class);
        $eventListener->addListener(BeforePersistingReportEvent::class, 'before-persisting-report-event-listener');

        $response = $this->executeMiddlewareStack();

        self::assertInstanceOf(BeforePersistingReportEvent::class, $beforePersistingReportEvent);
        self::assertSame(201, $response->getStatusCode());

        // Ensure no report was created
        $repository = $this->get(ReportRepository::class);
        /** @var Report[] $reports */
        $reports = $repository->findAll();
        self::assertCount(0, $reports);
    }

    #[Test]
    public function beforePersistingReportEventCanPreserveReport(): void
    {
        $beforePersistingReportEvent = null;

        /** @var Container $container */
        $container = $this->get('service_container');
        $container->set(
            'before-persisting-report-event-listener',
            static function (BeforePersistingReportEvent $event) use (&$beforePersistingReportEvent) {
                $beforePersistingReportEvent = $event;
                // no change to event report
            }
        );
        $eventListener = $this->get(ListenerProvider::class);
        $eventListener->addListener(BeforePersistingReportEvent::class, 'before-persisting-report-event-listener');

        $response = $this->executeMiddlewareStack();

        self::assertInstanceOf(BeforePersistingReportEvent::class, $beforePersistingReportEvent);
        self::assertSame(201, $response->getStatusCode());

        // Retrieve the report that was just created
        $repository = $this->get(ReportRepository::class);
        /** @var Report[] $reports */
        $reports = $repository->findAll();
        self::assertCount(1, $reports);
        self::assertSame(1771490230, $reports[0]->requestTime);
        self::assertSame('https://example.com/endpoint', $reports[0]->details['document-uri']);
    }

    private function executeMiddlewareStack(): ResponseInterface
    {
        $dispositionMapFactory = new DispositionMapFactory($this->createFeaturesMock());
        $reporterMiddleware = new class (
            $this->get(EventDispatcher::class),
            $this->get(PolicyProvider::class),
            $dispositionMapFactory,
            $this->get(ReportRepository::class),
            $this->get(HashService::class),
        ) extends AbstractContentSecurityPolicyReporter {
            public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
            {
                $scope = Scope::backend();
                $this->persistCspReport($scope, $request);
                return (new Response())->withStatus(201);
            }
        };

        $kernel = new class () implements RequestHandlerInterface {
            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                return (new Response())
                    ->withStatus(202);
            }
        };

        $dispatcher = new MiddlewareDispatcher($kernel, [$reporterMiddleware]);

        $uri = 'https://example.com/endpoint';
        $body = new Stream('php://temp', 'rw');
        $body->write(json_encode(['csp-report' => ['document-uri' => $uri, 'effective-directive' => 'foobar', 'blocked-uri' => 'darth://vader']]));
        $body->rewind();
        $requestTime = '1771490230';
        $request = (new ServerRequest($uri, 'POST', $body))
            ->withAttribute('normalizedParams', new NormalizedParams([], [], '', ''))
            ->withQueryParams([
                'requestTime' => $requestTime,
                'requestHash' =>  $this->get(HashService::class)->hmac($requestTime, AbstractContentSecurityPolicyReporter::class),
            ]);

        return $dispatcher->handle($request);
    }

    private function createFeaturesMock(): Features
    {
        $featuresMock = $this->createMock(Features::class);
        $featuresMock->method('isFeatureEnabled')->willReturn(true);
        return $featuresMock;
    }
}
