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

namespace TYPO3\CMS\Form\Tests\Unit\Domain\Finishers;

use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Http\PropagateResponseException;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Extbase\Mvc\ExtbaseRequestParameters;
use TYPO3\CMS\Extbase\Mvc\Request;
use TYPO3\CMS\Form\Domain\Finishers\FinisherContext;
use TYPO3\CMS\Form\Domain\Finishers\RedirectFinisher;
use TYPO3\CMS\Form\Domain\Model\FormDefinition;
use TYPO3\CMS\Form\Domain\Model\FormElements\GenericFormElement;
use TYPO3\CMS\Form\Domain\Model\FormElements\ProcessableValueFormElementInterface;
use TYPO3\CMS\Form\Domain\Runtime\FormRuntime;
use TYPO3\CMS\Form\Service\FormValueResolver;
use TYPO3\CMS\Form\Service\TranslationService;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

#[AllowMockObjectsWithoutExpectations]
final class RedirectFinisherTest extends UnitTestCase
{
    protected bool $resetSingletonInstances = true;

    /**
     * @param string|int|null $pageUid
     */
    #[DataProvider('pageUidOptionForFinisherAcceptsVariousPageRepresentationsDataProvider')]
    #[Test]
    public function pageUidOptionForFinisherAcceptsVariousPageRepresentations($pageUid, int $expectedPage): void
    {
        $uriPrefix = 'https://site.test/?id=';
        $contentObjectRendererMock = self::createStub(ContentObjectRenderer::class);
        $contentObjectRendererMock->method('createUrl')->willReturnCallback(static function (array $conf) use ($uriPrefix): string {
            return $uriPrefix . $conf['parameter'];
        });

        $serverRequest = new ServerRequest()->withAttribute('extbase', new ExtbaseRequestParameters());
        $serverRequest = $serverRequest->withAttribute('currentContentObject', $contentObjectRendererMock);
        $contentObjectRendererMock->setRequest($serverRequest);
        $request = new Request($serverRequest);

        $finisherContextMock = $this->createMock(FinisherContext::class);
        $finisherContextMock->method('getRequest')->willReturn($request);
        $finisherContextMock->expects($this->once())->method('cancel');

        $translationServiceMock = self::createStub(TranslationService::class);
        $translationServiceMock->method('translateFinisherOption')->willReturnArgument(3);

        $redirectFinisherMock = $this->getAccessibleMock(RedirectFinisher::class, null, [], '', false);
        $redirectFinisherMock->_set('options', [
            'pageUid' => $pageUid,
        ]);
        $redirectFinisherMock->injectTranslationService($translationServiceMock);
        try {
            $redirectFinisherMock->execute($finisherContextMock);
            self::fail('RedirectFinisher did not throw expected exception.');
        } /** @noinspection PhpRedundantCatchClauseInspection */ catch (PropagateResponseException $e) {
            $response = $e->getResponse();
            self::assertSame($uriPrefix . $expectedPage, $response->getHeader('Location')[0]);
        }
    }

    public static function pageUidOptionForFinisherAcceptsVariousPageRepresentationsDataProvider(): array
    {
        return [
            'null' => [
                null,
                1,
            ],
            'no page' => [
                '',
                1,
            ],
            'page as integer' => [
                3,
                3,
            ],
            'page as string' => [
                '3',
                3,
            ],
            'page with table prefix' => [
                'pages_3',
                3,
            ],
        ];
    }

    #[Test]
    public function redirectTargetsKeepTheSubmittedValue(): void
    {
        $contentObjectRendererStub = self::createStub(ContentObjectRenderer::class);
        $contentObjectRendererStub->method('createUrl')->willReturnCallback(static function (array $conf): string {
            return 'https://site.test/?id=' . $conf['parameter'] . $conf['additionalParams'] . '#' . $conf['section'];
        });

        $serverRequest = new ServerRequest()
            ->withAttribute('extbase', new ExtbaseRequestParameters())
            ->withAttribute('currentContentObject', $contentObjectRendererStub);
        $contentObjectRendererStub->setRequest($serverRequest);

        $finisherContextMock = $this->createMock(FinisherContext::class);
        $finisherContextMock->method('getRequest')->willReturn(new Request($serverRequest));
        $finisherContextMock->method('getFormRuntime')->willReturn($this->buildFormRuntimeWithOptionableElement());
        $finisherContextMock->expects($this->once())->method('cancel');

        $translationServiceStub = self::createStub(TranslationService::class);
        $translationServiceStub->method('translateFinisherOption')->willReturnArgument(3);

        $redirectFinisherMock = $this->getAccessibleMock(RedirectFinisher::class, null, [], '', false);
        $redirectFinisherMock->_set('options', [
            'pageUid' => '{topic}',
            'additionalParameters' => 'department={department}',
            'fragment' => '{department}',
        ]);
        $redirectFinisherMock->injectTranslationService($translationServiceStub);
        $redirectFinisherMock->injectFormValueResolver(new FormValueResolver($translationServiceStub));

        try {
            $redirectFinisherMock->execute($finisherContextMock);
            self::fail('RedirectFinisher did not throw expected exception.');
        } /** @noinspection PhpRedundantCatchClauseInspection */ catch (PropagateResponseException $e) {
            self::assertSame(
                'https://site.test/?id=3&department=sales#sales',
                $e->getResponse()->getHeader('Location')[0]
            );
        }
    }

    private function buildFormRuntimeWithOptionableElement(): FormRuntime
    {
        // A page uid and a mail alias picked from a select, both of which lose
        // their meaning once the option label is substituted for them
        $submittedValues = ['topic' => '3', 'department' => 'sales'];
        $labels = ['topic' => 'Sales team', 'department' => 'Sales'];

        $formDefinition = new FormDefinition('form');
        foreach ($labels as $identifier => $label) {
            $element = new class ($identifier, '', $label) extends GenericFormElement implements ProcessableValueFormElementInterface {
                public function __construct(string $identifier, string $type, private readonly string $displayValue)
                {
                    parent::__construct($identifier, $type);
                }

                public function processElementValue(mixed $value, FormRuntime $formRuntime): mixed
                {
                    return $this->displayValue;
                }
            };
            $element->setParentRenderable($formDefinition);
        }

        $formRuntimeStub = self::createStub(FormRuntime::class);
        $formRuntimeStub->method('offsetExists')->willReturn(true);
        $formRuntimeStub->method('offsetGet')->willReturnCallback(
            static fn(string $identifier) => $submittedValues[$identifier] ?? null
        );
        $formRuntimeStub->method('getFormDefinition')->willReturn($formDefinition);
        return $formRuntimeStub;
    }
}
