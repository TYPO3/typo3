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
use TYPO3\CMS\Extbase\Persistence\Generic\Exception as GenericPersistenceException;
use TYPO3\CMS\Extbase\Persistence\Generic\Qom\QueryObjectModelFactory;
use TYPO3\CMS\Extbase\Persistence\Generic\Query;
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
        $request = new ServerRequest()->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE);
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
        /** @var Query $query */
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

    /**
     * `QueryInterface::OPERATOR_IS_NULL` is declared but not implemented by the query parser, so a comparison with it
     * throws when the SQL is built. The operator is only reachable through the QOM factory.
     */
    #[Test]
    public function unsupportedOperatorViaQomFactoryThrowsException(): void
    {
        // Given: a query comparing the title with an operator the query parser does not know
        $qomFactory = $this->get(QueryObjectModelFactory::class);
        $query = $this->get(PostRepository::class)->createQuery();
        $query->matching(
            $qomFactory->comparison(
                $qomFactory->propertyValue('title', 'tx_blogexample_domain_model_post'),
                QueryInterface::OPERATOR_IS_NULL,
                null
            )
        );

        // Then: building the SQL fails
        $this->expectException(GenericPersistenceException::class);
        $this->expectExceptionCode(1242816073);

        // When: the query is executed (`execute(true)` builds the SQL, the lazy result would not)
        $query->execute(true);
    }
}
