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

namespace TYPO3\CMS\Core\Tests\Functional\DataHandling\DataHandler;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Context\WorkspaceAspect;
use TYPO3\CMS\Core\Core\Bootstrap;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Functional\Framework\DataHandling\ActionService;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * Tests related to security aspects in DataHandler
 */
final class SecurityTest extends FunctionalTestCase
{
    /**
     * @var BackendUserAuthentication
     */
    private $backendUser;

    /**
     * @var ActionService
     */
    private $actionService;

    protected array $coreExtensionsToLoad = ['rte_ckeditor'];

    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/DataSet/pages.csv');
        $this->importCSVDataSet(__DIR__ . '/../../Fixtures/be_users_admin.csv');
        $this->backendUser = $this->setUpBackendUser(1);
        $this->backendUser->workspace = 0;
        GeneralUtility::makeInstance(Context::class)
            ->setAspect('workspace', new WorkspaceAspect(0));
        Bootstrap::initializeLanguageObject();

        $this->actionService = GeneralUtility::makeInstance(ActionService::class);
    }

    public static function crossSiteScriptingDataProvider(): array
    {
        return [
            [
                'input' => 'The "test" value might be =< x or > y...', // submitted payload
                'expectations' => [
                    // @todo issue in `masterminds/html5`, first `<` should be parsed and encoded to `&lt;`
                    'The "test" value might be = x or &gt; y...', // default processing, HTML Sanitizer enabled
                    'The "test" value might be =< x or > y...', // default processing, HTML Sanitizer disabled
                ],
            ],
            [
                'input' => '<p undefined="<not-allowed>"></p>',
                'expectations' => [
                    '<p></p>',
                    '<p></p>',
                ],
            ],
            [
                'input' => '<p undefined=<not-allowed>></p>',
                'expectations' => [
                    '<p></p>',
                    '<p></p>',
                ],
            ],
            [
                'input' => '<p title="<encode-me>"></p>',
                'expectations' => [
                    '<p title="&lt;encode-me&gt;"></p>',
                    '<p title="&lt;encode-me&gt;"></p>',
                ],
            ],
            [
                'input' => '<p title=<encode-me>></p>',
                'expectations' => [
                    '<p title="&lt;encode-me&gt;"></p>',
                    '<p title="&lt;encode-me&gt;"></p>',
                ],
            ],
            [
                'input' => '<p title="""></p>',
                'expectations' => [
                    '<p></p>',
                    '<p></p>',
                ],
            ],
            [
                'input' => '<p title="title"></p>',
                'expectations' => [
                    '<p title="title"></p>',
                    '<p title="title"></p>',
                ],
            ],
            [
                'input' => '<p title="escape"<img src=src>"></p>',
                'expectations' => [
                    '<p title="escape">"&gt;</p>',
                    '<p title="escape">"></p>',
                ],
            ],
            [
                'input' => '<p title=""""></p>',
                'expectations' => [
                    '<p title></p>',
                    '<p title></p>',
                ],
            ],
            [
                'input' => '<p title=""anything"></p>',
                'expectations' => [
                    '<p></p>',
                    '<p></p>',
                ],
            ],
            [
                'input' => '<p title=""anything""></p>',
                'expectations' => [
                    '<p title></p>',
                    '<p title></p>',
                ],
            ],
            [
                'input' => '<p title="anything""></p>',
                'expectations' => [
                    '<p></p>',
                    '<p></p>',
                ],
            ],
            [
                // 'removeTags' scrubs this
                'input' => '<link rel="nothing" /><meta name="something" value="anything">meta</meta><o:p>Word!</o:p>, <sdfield>sdfield</sdfield>, <style type="text/css">html { background-color: red }</style>',
                'expectations' => [
                    'metaWord!, sdfield, html { background-color: red }',
                    'metaWord!, sdfield, html { background-color: red }',
                ],
            ],
            [
                // 'removeTags' no longer scrubs this
                'input' => '<strike>strike</strike> <u>underline</u> <center>center</center>',
                'expectations' => [
                    '<strike>strike</strike> <u>underline</u> <center>center</center>',
                    '<strike>strike</strike> <u>underline</u> <center>center</center>',
                ],
            ],
            [
                'input' => '<not-allowed><p title="</not-allowed><img src=x onerror=alert(1)><img src=x onerror=alert(2)>',
                'expectations' => [
                    '<p>&lt;not-allowed&gt;</p>' . "\r\n" . '<p></p>',
                    '<p>&lt;not-allowed&gt;</p>' . "\r\n" . '<p></p>',
                ],
            ],
            [
                'input' => '<not-allowed><p title="</not-allowed><img src="x" onerror="alert(1)"><img src="x" onerror="alert(2)">',
                'expectations' => [
                    '<p>&lt;not-allowed&gt;</p>' . "\r\n" . '<p></p>',
                    '<p>&lt;not-allowed&gt;</p>' . "\r\n" . '<p></p>',
                ],
            ],
            [
                'input' => '<script>alert(3)</script>',
                'expectations' => [
                    '&lt;script&gt;alert(3)&lt;/script&gt;',
                    '&lt;script&gt;alert(3)&lt;/script&gt;',
                ],
            ],
            [
                'input' => '<p><script>alert(3)</script></p>',
                'expectations' => [
                    '<p>&lt;script&gt;alert(3)&lt;/script&gt;</p>',
                    '<p>&lt;script&gt;alert(3)&lt;/script&gt;</p>',
                ],
            ],
            [
                'input' => '<title>title</title>',
                'expectations' => [
                    'title',
                    'title',
                ],
            ],
            [
                'input' => '<p><title>title</title></p>',
                'expectations' => [
                    '<p>title</p>',
                    '<p>title</p>',
                ],
            ],
            [
                'input' => '<font face="a" color="b" onmouseover="alert(1);">text</font>'
                    . '<img src="x" alt="test" onerror="alert(2)">',
                'expectations' => [
                    '<font face="a" color="b">text</font>'
                        . '<img src="x" alt="test">',
                    // @todo "expected" for the time being without using HTML Sanitizer
                    '<font face="a" color="b" onmouseover="alert(1);">text</font>'
                        . '<img src="x" alt="test" onerror="alert(2)">',
                ],
            ],
            [
                'input' => '<p>'
                    . '<font face="a" color="b" onmouseover="alert(1);">text</font>'
                    . '<img src="x" alt="test" onerror="alert(2)">'
                    . '</p>',
                'expectations' => [
                    '<p><font face="a" color="b">text</font>'
                        . '<img src="x" alt="test"></p>',
                    // @todo "expected" for the time being without using HTML Sanitizer
                    '<p><font face="a" color="b" onmouseover="alert(1);">text</font>'
                        . '<img src="x" alt="test" onerror="alert(2)"></p>',
                ],
            ],
            [
                'input' => '<p><a href="https://typo3.org" target="_blank" rel="noreferrer" role="button" onmouseover="alert(1)">text</a></p>',
                'expectations' => [
                    '<p><a href="https://typo3.org" target="_blank" rel="noreferrer" role="button">text</a></p>',
                    // @todo "expected" for the time being without using HTML Sanitizer
                    '<p><a href="https://typo3.org" target="_blank" rel="noreferrer" role="button" onmouseover="alert(1)">text</a></p>',
                ],
            ],
            [
                'input' => '<p><a href="t3://page?uid=1" target="_blank" rel="noreferrer" role="button" onmouseover="alert(1)">text</a></p>',
                'expectations' => [
                    '<p><a href="t3://page?uid=1" target="_blank" rel="noreferrer" role="button">text</a></p>',
                    // @todo "expected" for the time being without using HTML Sanitizer
                    '<p><a href="t3://page?uid=1" target="_blank" rel="noreferrer" role="button" onmouseover="alert(1)">text</a></p>',
                ],
            ],
            [
                'input' => '<?xml >s<img src=x onerror=alert(1)> ?>',
                'expectations' => [
                    '&lt;?xml &gt;s&lt;img src=x onerror=alert(1)&gt; ?&gt;',
                    '<?xml >s<img src=x onerror=alert(1)> ?>',
                ],
            ],
        ];
    }

    /**
     * This test does not define any additional configuration, scope is to test
     * the factory-default configuration of TYPO3 when editing content via backend
     * user interface.
     */
    #[DataProvider('crossSiteScriptingDataProvider')]
    #[Test]
    public function markupIsSanitizedForContentBodytextWithHtmlSanitizerEnabled(string $input, array $expectations): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['features']['security.backend.htmlSanitizeRte'] = true;
        $newIds = $this->actionService->createNewRecord('tt_content', 1, [
            'CType' => 'text',
            'bodytext' => $input,
        ]);
        $contentId = current($newIds['tt_content'] ?? 0);
        self::assertGreaterThan(0, $contentId, 'Could not resolve content id');

        $connection = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable('tt_content');
        $record = $connection->select(['bodytext'], 'tt_content', ['uid' => (int)$contentId])->fetchAssociative();
        $bodytext = $record['bodytext'] ?? null;

        $expectation = $expectations[0];
        self::assertSame($expectation, $bodytext, sprintf('Given markup: `%s`', $input));
    }

    /**
     * This test does not define any additional configuration, scope is to test
     * the factory-default configuration of TYPO3 when editing content via backend
     * user interface.
     */
    #[DataProvider('crossSiteScriptingDataProvider')]
    #[Test]
    public function markupIsSanitizedForContentBodytextWithHtmlSanitizerDisabled(string $input, array $expectations): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['features']['security.backend.htmlSanitizeRte'] = false;
        $newIds = $this->actionService->createNewRecord('tt_content', 1, [
            'CType' => 'text',
            'bodytext' => $input,
        ]);
        $contentId = current($newIds['tt_content'] ?? 0);
        self::assertGreaterThan(0, $contentId, 'Could not resolve content id');

        $connection = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable('tt_content');
        $record = $connection->select(['bodytext'], 'tt_content', ['uid' => (int)$contentId])->fetchAssociative();
        $bodytext = $record['bodytext'] ?? null;

        $expectation = $expectations[1];
        self::assertSame($expectation, $bodytext, sprintf('Given markup: `%s`', $input));
    }
}
