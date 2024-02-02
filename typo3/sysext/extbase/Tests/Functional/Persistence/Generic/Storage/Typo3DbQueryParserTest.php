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

namespace TYPO3\CMS\Extbase\Tests\Functional\Persistence\Generic\Storage;

use ExtbaseTeam\BlogExample\Domain\Repository\RegistryEntryRepository;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Extbase\Persistence\Generic\Storage\Typo3DbQueryParser;
use TYPO3\CMS\Extbase\Persistence\Generic\Typo3QuerySettings;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class Typo3DbQueryParserTest extends FunctionalTestCase
{
    protected $testExtensionsToLoad = [
        'typo3/sysext/extbase/Tests/Functional/Fixtures/Extensions/blog_example',
    ];

    /**
     * @test
     */
    public function tcaWithoutCtrlCreatesAValidSQLStatement(): void
    {
        $GLOBALS['TYPO3_REQUEST'] = (new ServerRequest())
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE);
        $registryEntryRepository = $this->get(RegistryEntryRepository::class);
        $querySettings = new Typo3QuerySettings(new Context(), $this->get(ConfigurationManagerInterface::class));

        $query = $registryEntryRepository->createQuery();
        $query->setQuerySettings($querySettings);

        $typo3DbQueryParser = $this->get(Typo3DbQueryParser::class);
        $queryBuilder = $typo3DbQueryParser->convertQueryToDoctrineQueryBuilder($query);

        $compositeExpression = $queryBuilder->getQueryPart('where');
        self::assertStringNotContainsString('hidden', (string)$compositeExpression);
        self::assertStringNotContainsString('deleted', (string)$compositeExpression);
    }
}
