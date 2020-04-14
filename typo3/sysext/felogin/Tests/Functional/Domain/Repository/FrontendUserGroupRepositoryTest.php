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

namespace TYPO3\CMS\FrontendLogin\Tests\Functional\Domain\Repository;

use TYPO3\CMS\Frontend\Authentication\FrontendUserAuthentication;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;
use TYPO3\CMS\FrontendLogin\Domain\Repository\FrontendUserGroupRepository;
use TYPO3\CMS\FrontendLogin\Service\UserService;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

class FrontendUserGroupRepositoryTest extends FunctionalTestCase
{
    /**
     * @var array
     */
    protected $coreExtensionsToLoad = ['extbase', 'felogin'];

    /**
     * @var FrontendUserGroupRepository
     */
    protected $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $GLOBALS['TSFE'] = static::getMockBuilder(TypoScriptFrontendController::class)
            ->disableOriginalConstructor()
            ->getMock()
        ;

        $GLOBALS['TSFE']->fe_user = new FrontendUserAuthentication();

        $this->repository = new FrontendUserGroupRepository(
            new UserService()
        );

        $this->importDataSet(__DIR__ . '/../../Fixtures/fe_groups.xml');
    }

    /**
     * @test
     */
    public function getTable()
    {
        self::assertSame('fe_groups', $this->repository->getTable());
    }

    /**
     * @test
     */
    public function findRedirectPageIdByGroupId()
    {
        self::assertNull($this->repository->findRedirectPageIdByGroupId(99));
        self::assertSame(10, $this->repository->findRedirectPageIdByGroupId(1));
    }
}
