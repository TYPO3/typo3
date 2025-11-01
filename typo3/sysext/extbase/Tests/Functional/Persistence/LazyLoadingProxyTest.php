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

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Extbase\Persistence\Generic\LazyLoadingProxy;
use TYPO3\CMS\Extbase\Persistence\Generic\Mapper\DataMapper;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;
use TYPO3Tests\BlogExample\Domain\Model\Administrator;
use TYPO3Tests\BlogExample\Domain\Model\Blog;

final class LazyLoadingProxyTest extends FunctionalTestCase
{
    protected array $coreExtensionsToLoad = ['extbase'];
    protected array $testExtensionsToLoad = ['typo3/sysext/extbase/Tests/Functional/Fixtures/Extensions/blog_example'];

    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/Fixtures/LazyLoadingProxyTestImport.csv');
        $request = (new ServerRequest())->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE);
        $this->get(ConfigurationManagerInterface::class)->setRequest($request);
    }

    #[Test]
    public function serializeAndUnserialize(): void
    {
        $blog = new Blog();
        $blog->_setProperty('administrator', new LazyLoadingProxy($blog, 'administrator', 1));

        $serialized = serialize($blog->getAdministrator());

        self::assertFalse(str_contains($serialized, 'dataMapper'), 'Assert that serialized object string does not contain dataMapper');

        $administratorProxy = unserialize($serialized, ['allowed_classes' => true]);
        self::assertInstanceOf(LazyLoadingProxy::class, $administratorProxy, 'Assert that $administratorProxy is an instance of LazyLoadingProxy');

        $administrator = $administratorProxy->_loadRealInstance();
        self::assertInstanceOf(Administrator::class, $administrator, 'Assert that $administrator is an instance of Administrator');

        self::assertSame('Blog Admin', $administrator->getUsername());
    }

    #[Test]
    public function nonExistingLazyLoadedPropertyReturnsNull(): void
    {
        $lazyLoadingProxy = new LazyLoadingProxy(
            new Blog(),
            'administrator',
            0,
            $this->get(DataMapper::class)
        );
        // Directly using the magic `__get()` method here to avoid PHPStan complaining
        // about the dynamic property issue and spare an ignore pattern or annotation.
        // See: https://phpstan.org/blog/solving-phpstan-access-to-undefined-property
        // This equals to: self::assertNull($lazyLoadingProxy->name);
        self::assertNull($lazyLoadingProxy->__get('name'));
    }
}
