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

namespace TYPO3\CMS\Fluid\Tests\Functional\ViewHelpers;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Tests\Functional\SiteHandling\SiteBasedTestTrait;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\InternalRequest;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;
use TYPO3Fluid\Fluid\Core\ViewHelper\Exception;

final class CObjectViewHelperTest extends FunctionalTestCase
{
    use SiteBasedTestTrait;

    protected const LANGUAGE_PRESETS = [
        'EN' => ['id' => 0, 'title' => 'English', 'locale' => 'en_US.UTF8'],
    ];

    /**
     * Add basic FE setup
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/pages.csv');
        $this->writeSiteConfiguration(
            'test',
            $this->buildSiteConfiguration(1, '/'),
        );
    }

    #[Test]
    public function viewHelperAcceptsDataParameter(): void
    {
        (new ConnectionPool())->getConnectionForTable('sys_template')->insert('sys_template', [
            'pid' => 1,
            'root' => 1,
            'clear' => 1,
            'config' => <<<EOT
page = PAGE
page.10 = FLUIDTEMPLATE
page.10 {
    template = TEXT
    template.value = <f:cObject typoscriptObjectPath="lib.test" data="foo" />
}
lib.test = TEXT
lib.test.current = 1
EOT
        ]);
        $response = $this->executeFrontendSubRequest((new InternalRequest())->withPageId(1));
        self::assertStringContainsString('foo', (string)$response->getBody());
    }

    #[Test]
    public function viewHelperAcceptsChildrenClosureAsData(): void
    {
        (new ConnectionPool())->getConnectionForTable('sys_template')->insert('sys_template', [
            'pid' => 1,
            'root' => 1,
            'clear' => 1,
            'config' => <<<EOT
page = PAGE
page.10 = FLUIDTEMPLATE
page.10 {
    template = TEXT
    template.value = <f:cObject typoscriptObjectPath="lib.test">foo</f:cObject>
}
lib.test = TEXT
lib.test.current = 1
EOT
        ]);
        $response = $this->executeFrontendSubRequest((new InternalRequest())->withPageId(1));
        self::assertStringContainsString('foo', (string)$response->getBody());
    }

    #[Test]
    public function viewHelperAcceptsIntegerBasedTagContentAsData(): void
    {
        (new ConnectionPool())->getConnectionForTable('sys_template')->insert('sys_template', [
            'pid' => 1,
            'root' => 1,
            'clear' => 1,
            'config' => <<<EOT
page = PAGE
page.10 = FLUIDTEMPLATE
page.10 {
    template = TEXT
    template.value = <f:for each="{4711:'4712'}" as="i" iteration="iterator" key="k"><f:cObject typoscriptObjectPath="lib.test">{k}</f:cObject></f:for>
}
lib.test = TEXT
lib.test.current = 1
EOT
        ]);
        $response = $this->executeFrontendSubRequest((new InternalRequest())->withPageId(1));
        self::assertStringContainsString('4711', (string)$response->getBody());
    }

    #[Test]
    public function renderThrowsExceptionIfTypoScriptObjectPathDoesNotExist(): void
    {
        (new ConnectionPool())->getConnectionForTable('sys_template')->insert('sys_template', [
            'pid' => 1,
            'root' => 1,
            'clear' => 1,
            'config' => <<<EOT
page = PAGE
page.10 = FLUIDTEMPLATE
page.10 {
    template = TEXT
    template.value = <f:cObject typoscriptObjectPath="doesNotExist" />
}
EOT
        ]);
        $this->expectException(Exception::class);
        $this->expectExceptionCode(1540246570);
        $this->executeFrontendSubRequest((new InternalRequest())->withPageId(1));
    }

    #[Test]
    public function renderThrowsExceptionIfNestedTypoScriptObjectPathDoesNotExist(): void
    {
        (new ConnectionPool())->getConnectionForTable('sys_template')->insert('sys_template', [
            'pid' => 1,
            'root' => 1,
            'clear' => 1,
            'config' => <<<EOT
page = PAGE
page.10 = FLUIDTEMPLATE
page.10 {
    template = TEXT
    template.value = <f:cObject typoscriptObjectPath="does.not.exist" />
}
EOT
        ]);
        $this->expectException(Exception::class);
        $this->expectExceptionCode(1253191023);
        $this->executeFrontendSubRequest((new InternalRequest())->withPageId(1));
    }
}
