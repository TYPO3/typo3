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

namespace TYPO3\CMS\RteCKEditor\Tests\Functional\Form\Element;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Core\ApplicationContext;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Http\NormalizedParams;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\RteCKEditor\Form\Element\RichTextElement;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class RichTextElementTest extends FunctionalTestCase
{
    protected array $coreExtensionsToLoad = ['rte_ckeditor'];

    /**
     * @var array<string, mixed>
     */
    private ?array $backupEnvironment = null;

    protected function setUp(): void
    {
        parent::setUp();
        $this->backupEnvironment = Environment::toArray();
    }

    protected function tearDown(): void
    {
        Environment::initialize(
            new ApplicationContext($this->backupEnvironment['context']),
            $this->backupEnvironment['cli'],
            false,
            $this->backupEnvironment['projectPath'],
            $this->backupEnvironment['publicPath'],
            $this->backupEnvironment['varPath'],
            $this->backupEnvironment['configPath'],
            $this->backupEnvironment['currentScript'],
            $this->backupEnvironment['os'],
        );
        parent::tearDown();
    }

    public static function resolveUrlPathDataProvider(): \Generator
    {
        $mtime = filemtime(__DIR__ . '/../../../../Resources/Public/Css/contents.css');
        yield 'resolve a extension path' => [
            'EXT:rte_ckeditor/Resources/Public/Css/contents.css',
            '/_assets/a1ec5c458af2de6455fd3e0dc29a0e56/Css/contents.css?' . $mtime,
        ];

        yield 'resolve a extension path with a query string at it' => [
            'EXT:rte_ckeditor/Resources/Public/Css/contents.css?4',
            '/_assets/a1ec5c458af2de6455fd3e0dc29a0e56/Css/contents.css?4',
        ];

        yield 'resolve a regular file path' => [
            'fileadmin/templates/rte.css',
            'fileadmin/templates/rte.css',
        ];

        yield 'resolve a regular file path with a query string at it' => [
            'fileadmin/templates/rte.css?v=13',
            'fileadmin/templates/rte.css?v=13',
        ];

        yield 'resolve an external file path' => [
            'https://example.com/typo3styles.css',
            'https://example.com/typo3styles.css',
        ];

        yield 'resolve an external file path with bust is kept' => [
            'https://example.com/typo3styles.css?v=42',
            'https://example.com/typo3styles.css?v=42',
        ];
    }

    #[Test]
    #[DataProvider('resolveUrlPathDataProvider')]
    public function resolveUrlPathCanDealWithVariousInputs(string $input, string $expected): void
    {
        // Simulate backend request in composer mode, using `subdir` as document root.
        $fakePublicDir = Environment::getProjectPath() . '/subdir';
        Environment::initialize(
            Environment::getContext(),
            false,
            true,
            Environment::getProjectPath(),
            $fakePublicDir,
            Environment::getVarPath(),
            Environment::getConfigPath(),
            $fakePublicDir . '/index.php',
            Environment::isWindows() ? 'WINDOWS' : 'UNIX'
        );
        $request = (new ServerRequest('https://example.com/typo3/form-engine', 'GET', null, [], [
            'REQUEST_URI' => '/typo3/form-engine',
            'SCRIPT_NAME' => '/index.php',
            'HTTP_HOST' => 'example.com',
            'DOCUMENT_ROOT' => Environment::getPublicPath(),
            'SCRIPT_FILENAME' => Environment::getPublicPath() . '/index.php',
        ]))
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE);
        $_SERVER = array_replace_recursive($_SERVER, $request->getServerParams());
        $request = $request->withAttribute('normalizedParams', NormalizedParams::createFromRequest($request));
        $GLOBALS['TYPO3_REQUEST'] = $request;
        GeneralUtility::flushInternalRuntimeCaches();

        $subject = $this->getAccessibleMock(RichTextElement::class, ['getExtraPlugins'], [], '', false);
        $result = $subject->_call('replaceAbsolutePathsToRelativeResourcesPath', ['example' => $input]);
        self::assertSame($expected, $result['example']);
    }

}
