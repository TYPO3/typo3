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

namespace TYPO3\CMS\Frontend\Tests\Functional\DataProcessing;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Domain\Repository\PageRepository;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Frontend\ContentObject\Menu\AbstractMenuContentObject;
use TYPO3\CMS\Frontend\DataProcessing\LanguageMenuProcessor;
use TYPO3\CMS\Frontend\Page\PageInformation;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class LanguageMenuProcessorTest extends FunctionalTestCase
{
    protected bool $initializeDatabase = false;

    public static function menuConfigurationIsNotCarriedOverBetweenCallsDataProvider(): array
    {
        return [
            'same configuration' => [
                'firstConfiguration' => [],
                'secondConfiguration' => [],
            ],
            'addQueryString disabled in first call' => [
                'firstConfiguration' => ['addQueryString' => 0],
                'secondConfiguration' => [],
            ],
            'addQueryString.exclude set in first call' => [
                'firstConfiguration' => ['addQueryString.' => ['exclude' => 'foo']],
                'secondConfiguration' => [],
            ],
            'languages set in first call' => [
                'firstConfiguration' => ['languages' => '0,1'],
                'secondConfiguration' => ['languages' => '2'],
            ],
        ];
    }

    #[Test]
    #[DataProvider('menuConfigurationIsNotCarriedOverBetweenCallsDataProvider')]
    public function menuConfigurationIsNotCarriedOverBetweenCalls(array $firstConfiguration, array $secondConfiguration): void
    {
        $cObj = $this->get(ContentObjectRenderer::class);
        $cObj->setRequest($this->createRequest());

        $subject = $this->createSubject();
        $subject->process($cObj, [], $firstConfiguration, []);
        $subject->process($cObj, [], $secondConfiguration, []);

        $freshSubject = $this->createSubject();
        $freshSubject->process($cObj, [], $secondConfiguration, []);

        self::assertSame($this->getMenuConfig($freshSubject), $this->getMenuConfig($subject));
    }

    #[Test]
    public function canonicalizationParametersAreExcludedOnceOnRepeatedCalls(): void
    {
        $cObj = $this->get(ContentObjectRenderer::class);
        $cObj->setRequest($this->createRequest());

        $subject = $this->createSubject();
        $subject->process($cObj, [], [], []);
        $subject->process($cObj, [], [], []);
        $subject->process($cObj, [], [], []);

        self::assertSame('id', $this->getMenuConfig($subject)['addQueryString.']['exclude']);
    }

    private function createRequest(): ServerRequestInterface
    {
        $pageInformation = new PageInformation();
        $pageInformation->setId(1);
        $pageInformation->setPageRecord(['uid' => 1]);
        return new ServerRequest('https://example.com/')
            ->withAttribute('site', new Site('main', 1, []))
            ->withAttribute('frontend.page.information', $pageInformation);
    }

    private function createSubject(): LanguageMenuProcessor
    {
        $menuContentObjectLocator = self::createStub(ContainerInterface::class);
        $menuContentObjectLocator->method('get')->willReturn(self::createStub(AbstractMenuContentObject::class));
        return new LanguageMenuProcessor($menuContentObjectLocator, $this->get(PageRepository::class));
    }

    private function getMenuConfig(LanguageMenuProcessor $subject): array
    {
        return new \ReflectionProperty($subject, 'menuConfig')->getValue($subject);
    }
}
