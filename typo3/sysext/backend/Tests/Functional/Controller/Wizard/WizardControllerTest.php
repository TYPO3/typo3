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

namespace TYPO3\CMS\Backend\Tests\Functional\Controller\Wizard;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\DependencyInjection\ServiceLocator;
use TYPO3\CMS\Backend\Controller\Wizard\WizardController;
use TYPO3\CMS\Backend\Wizard\DTO\Configuration;
use TYPO3\CMS\Backend\Wizard\DTO\Finisher;
use TYPO3\CMS\Backend\Wizard\DTO\Step;
use TYPO3\CMS\Backend\Wizard\DTO\SubmissionResult;
use TYPO3\CMS\Backend\Wizard\WizardProviderInterface;
use TYPO3\CMS\Backend\Wizard\WizardProviderRegistry;
use TYPO3\CMS\Core\Http\JsonResponse;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class WizardControllerTest extends FunctionalTestCase
{
    private WizardController $subject;

    private MockObject|WizardProviderInterface $wizardProviderMock;

    protected bool $initializeDatabase = false;

    protected function setUp(): void
    {
        parent::setUp();

        $this->wizardProviderMock = $this->createMock(WizardProviderInterface::class);

        $wizardProviderFactory = new WizardProviderRegistry(
            new ServiceLocator(['foo' => fn() => $this->wizardProviderMock])
        );

        $this->subject = new WizardController($wizardProviderFactory);
    }

    #[Test]
    public function getConfigurationActionReturnsJsonFromProvider(): void
    {
        $request = new ServerRequest('https://example.com/typo3/', 'GET')->withQueryParams([
            'mode' => 'foo',
        ]);

        $this->wizardProviderMock->expects($this->once())
            ->method('getConfiguration')
            ->willReturn(Configuration::create([
                Step::create('foo.js')
                    ->withConfigurationData(['bar' => 'bar']),
            ]));

        $response = $this->subject->getConfigurationAction($request);

        self::assertInstanceOf(JsonResponse::class, $response);
        self::assertSame(
            '{"steps":[{"module":"foo.js","configurationData":{"bar":"bar"}}]}',
            (string)$response->getBody()
        );
    }
    #[Test]
    public function submitDataActionReturnsJsonFromProvider(): void
    {
        $request = new ServerRequest('https://example.com/typo3/', 'GET')->withQueryParams([
            'mode' => 'foo',
        ]);

        $this->wizardProviderMock->expects($this->once())
            ->method('handleSubmit')
            ->with($request)
            ->willReturn(
                SubmissionResult::createSuccessResult(Finisher::createNoopFinisher(
                    'Success',
                    'Operation completed successfully.'
                ))
            );

        $response = $this->subject->submitDataAction($request);

        self::assertInstanceOf(JsonResponse::class, $response);
        self::assertSame(
            '{"success":true,"finisher":{"identifier":"noop","module":"@typo3/backend/wizard/finisher/noop-finisher.js","data":[],"labels":{"successTitle":"Success","successDescription":"Operation completed successfully."}}}',
            (string)$response->getBody()
        );
    }
}
