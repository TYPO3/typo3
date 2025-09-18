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
use Psr\EventDispatcher\EventDispatcherInterface;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Http\NormalizedParams;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Localization\Locales;
use TYPO3\CMS\Core\Resource\StorageRepository;
use TYPO3\CMS\Core\SystemResource\Publishing\SystemResourcePublisherInterface;
use TYPO3\CMS\Core\SystemResource\SystemResourceFactory;
use TYPO3\CMS\Core\Tests\Functional\Fixtures\DummyFileCreationService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\RteCKEditor\Form\Element\RichTextElement;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class RichTextElementTest extends FunctionalTestCase
{
    protected array $coreExtensionsToLoad = ['rte_ckeditor'];
    private DummyFileCreationService $file;

    protected function setUp(): void
    {
        parent::setUp();
        $this->file = new DummyFileCreationService($this->get(StorageRepository::class));
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->file->cleanupCreatedFiles();
    }

    public static function resolveUrlPathDataProvider(): \Generator
    {
        $mtime = filemtime(__DIR__ . '/../../../../Resources/Public/Css/contents.css');
        yield 'resolve a extension path' => [
            'EXT:rte_ckeditor/Resources/Public/Css/contents.css',
            '/_assets/d06330b2f94417f8e5e5f457c6608eca/Css/contents.css?' . $mtime,
        ];

        yield 'resolve a package path' => [
            'PKG:rte_ckeditor:Resources/Public/Css/contents.css',
            '/_assets/d06330b2f94417f8e5e5f457c6608eca/Css/contents.css?' . $mtime,
        ];

        yield 'resolve a extension path with a query string' => [
            'EXT:rte_ckeditor/Resources/Public/Css/contents.css?4',
            '/_assets/d06330b2f94417f8e5e5f457c6608eca/Css/contents.css?4&' . $mtime,
        ];

        yield 'resolve a package path with a query string' => [
            'PKG:rte_ckeditor:Resources/Public/Css/contents.css?4',
            '/_assets/d06330b2f94417f8e5e5f457c6608eca/Css/contents.css?4&' . $mtime,
        ];

        // @todo: this should be revised by refactoring the RichTextElement
        //        to not try to resolve each and every config key to an URL
        //        and then removing the check for EXT and PKG
        yield 'resolve a regular file path (non EXT or PKG syntax is simply ignored)' => [
            '/fileadmin/templates/rte.css',
            '/fileadmin/templates/rte.css',
        ];

        yield 'resolve an external file path (non EXT or PKG syntax is simply ignored)' => [
            'https://example.com/typo3styles.css',
            'https://example.com/typo3styles.css',
        ];

        yield 'resolve an external file path with bust is kept (non EXT or PKG syntax is simply ignored)' => [
            'https://example.com/typo3styles.css?v=42',
            'https://example.com/typo3styles.css?v=42',
        ];

        yield 'non EXT or PKG syntax is simply ignored' => [
            'this works as well and we don not know why exactly',
            'this works as well and we don not know why exactly',
        ];
    }

    #[Test]
    #[DataProvider('resolveUrlPathDataProvider')]
    public function resolveUrlPathCanDealWithVariousInputs(string $input, string $expected): void
    {
        $this->file->ensureFilesExistInStorage('/templates/rte.css');
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

        $subject = $this->getAccessibleMock(
            RichTextElement::class,
            ['getExtraPlugins'],
            [
                $this->createMock(EventDispatcherInterface::class),
                $this->createMock(UriBuilder::class),
                $this->createMock(Locales::class),
                $this->get(SystemResourcePublisherInterface::class),
                $this->get(SystemResourceFactory::class),
            ],
        );
        $result = $subject->_call('replaceAbsolutePathsToRelativeResourcesPath', ['example' => $input]);
        self::assertSame($expected, $result['example']);
    }
}
