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

namespace TYPO3\CMS\Extbase\Tests\Functional\Persistence;

use ExtbaseTeam\BlogExample\Domain\Model\Administrator;
use ExtbaseTeam\BlogExample\Domain\Model\Blog;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Persistence\Generic\LazyLoadingProxy;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

class LazyLoadingProxyTest extends FunctionalTestCase
{
    protected array $coreExtensionsToLoad = ['extbase'];
    protected array $testExtensionsToLoad = ['typo3/sysext/extbase/Tests/Functional/Fixtures/Extensions/blog_example'];

    /**
     * Sets up this test suite.
     */
    protected function setUp(): void
    {
        parent::setUp();

        GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable('fe_users')
            ->insert('fe_users', [
                'uid' => 1,
                'username' => 'Blog Admin',
                'tx_extbase_type' => Administrator::class,
            ]);

        $request = (new ServerRequest())->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE);
        $GLOBALS['TYPO3_REQUEST'] = $request;
    }

    /**
     * @test
     */
    public function serializeAndUnserialize(): void
    {
        $blog = new Blog();
        $blog->_setProperty('administrator', new LazyLoadingProxy($blog, 'administrator', 1));

        $serialized = serialize($blog->getAdministrator());

        self::assertFalse(str_contains($serialized, 'dataMapper'), 'Assert that serialized object string does not contain dataMapper');

        /** @var LazyLoadingProxy $administratorProxy */
        $administratorProxy = unserialize($serialized, ['allowed_classes' => true]);
        self::assertInstanceOf(LazyLoadingProxy::class, $administratorProxy, 'Assert that $administratorProxy is an instance of LazyLoadingProxy');

        /** @var Administrator $administrator */
        $administrator = $administratorProxy->_loadRealInstance();
        self::assertInstanceOf(Administrator::class, $administrator, 'Assert that $administrator is an instance of Administrator');

        self::assertSame('Blog Admin', $administrator->getUsername());
    }
}
