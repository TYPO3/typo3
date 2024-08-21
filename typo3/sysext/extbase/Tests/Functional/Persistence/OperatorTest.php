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
use TYPO3\CMS\Extbase\Persistence\QueryInterface;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;
use TYPO3Tests\BlogExample\Domain\Repository\PostRepository;

final class OperatorTest extends FunctionalTestCase
{
    protected array $testExtensionsToLoad = [
        'typo3/sysext/extbase/Tests/Functional/Fixtures/Extensions/blog_example',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/Fixtures/OperatorTestImport.csv');
        $request = (new ServerRequest())->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE);
        $this->get(ConfigurationManagerInterface::class)->setRequest($request);
    }

    #[Test]
    public function equalsNullIsResolvedCorrectly(): void
    {
        $query = $this->get(PostRepository::class)->createQuery();
        $query->matching(
            $query->equals('title', null)
        );
        self::assertSame(0, $query->count());
    }

    #[Test]
    public function equalsCorrectlyHandlesCaseSensitivity(): void
    {
        $query = $this->get(PostRepository::class)->createQuery();
        $query->matching(
            $query->equals('title', 'PoSt1', false)
        );
        self::assertSame(2, $query->count());
    }

    #[Test]
    public function betweenSetsBoundariesCorrectly(): void
    {
        $query = $this->get(PostRepository::class)->createQuery();
        $query->setOrderings(['uid' => QueryInterface::ORDER_ASCENDING]);
        $query->matching(
            $query->between('uid', 3, 5)
        );
        $result = array_map(
            static fn(array $row): int => $row['uid'],
            $query->execute(true)
        );
        self::assertEquals([3, 4, 5], $result);
    }
}
