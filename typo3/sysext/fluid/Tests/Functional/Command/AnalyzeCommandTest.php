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

namespace TYPO3\CMS\Fluid\Tests\Functional\Command;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use TYPO3\CMS\Fluid\Command\AnalyzeCommand;
use TYPO3\CMS\Fluid\Core\Rendering\RenderingContextFactory;
use TYPO3\CMS\Fluid\Service\TemplateFinder;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class AnalyzeCommandTest extends FunctionalTestCase
{
    protected bool $initializeDatabase = false;

    public static function exitCodeDataProvider(): array
    {
        $fixtures = __DIR__ . '/../Fixtures';
        $validTemplate = $fixtures . '/Extensions/fluid_test/Resources/Private/Templates/BaseTemplate.fluid.html';
        $invalidTemplate = $fixtures . '/InvalidFluidTemplates/invalidTemplateDueToSyntaxError.fluid.html';
        return [
            'text output, valid template' => [$validTemplate, [], Command::SUCCESS],
            'text output, invalid template' => [$invalidTemplate, [], Command::FAILURE],
            'json output, valid template' => [$validTemplate, ['--json' => true], Command::SUCCESS],
            'json output, invalid template' => [$invalidTemplate, ['--json' => true], Command::FAILURE],
        ];
    }

    #[DataProvider('exitCodeDataProvider')]
    #[Test]
    public function exitCodeReflectsTemplateErrors(string $template, array $options, int $expectedExitCode): void
    {
        $templateFinder = self::createStub(TemplateFinder::class);
        $templateFinder->method('findTemplatesInAllPackages')->willReturn([$template]);
        $command = new AnalyzeCommand($templateFinder, $this->get(RenderingContextFactory::class));
        $tester = new CommandTester($command);

        $exitCode = $tester->execute($options);

        self::assertSame($expectedExitCode, $exitCode, $tester->getDisplay());
    }
}
