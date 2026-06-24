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

namespace TYPO3\CMS\Workspaces\Tests\Functional\Dependency;

use PHPUnit\Framework\Attributes\Test;
use Psr\EventDispatcher\EventDispatcherInterface;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Workspaces\Dependency\DependencyCollectionAction;
use TYPO3\CMS\Workspaces\Dependency\DependencyResolver;
use TYPO3\CMS\Workspaces\Dependency\ElementEntity;
use TYPO3\CMS\Workspaces\Domain\Repository\WorkspaceRepository;
use TYPO3\CMS\Workspaces\Domain\Repository\WorkspaceStageRepository;
use TYPO3\CMS\Workspaces\Service\Dependency\CollectionService;
use TYPO3\CMS\Workspaces\Service\GridDataService;
use TYPO3\CMS\Workspaces\Service\WorkspaceService;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * Records may reference each other in a circle, for instance two plugins whose
 * FlexForm points at the respective other content element. Resolving workspace
 * dependencies must cope with that instead of traversing the cycle endlessly.
 */
final class CircularDependencyTest extends FunctionalTestCase
{
    protected array $coreExtensionsToLoad = ['workspaces'];

    private BackendUserAuthentication $backendUser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/be_users.csv');
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/sys_workspace.csv');
        // tt_content:2003 -> tt_content:2001 -> tt_content:2002 -> tt_content:2001 -> ...
        $this->importCSVDataSet(__DIR__ . '/Fixtures/CircularDependencies.csv');

        $this->backendUser = $this->setUpBackendUser(1);
        $this->backendUser->workspace = 91;
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->createFromUserPreferences($this->backendUser);
    }

    #[Test]
    public function gridListNestsCircularlyRelatedElementsOnlyOnce(): void
    {
        $workspace = $this->get(WorkspaceRepository::class)->findByUid(91);
        $stages = $this->get(WorkspaceStageRepository::class)->findAllStagesByWorkspace($this->backendUser, $workspace);
        $versions = $this->get(WorkspaceService::class)->selectVersionsInWorkspace(91, -99, 1);

        $result = $this->get(GridDataService::class)->generateGridListFromVersions($stages, $versions, new \stdClass());

        self::assertSame(1, $result['total']);
        self::assertSame([2003, 2001, 2002], array_column($result['data'], 'uid'));
        self::assertSame([0, 1, 2], array_column($result['data'], 'Workspaces_CollectionLevel'));
        self::assertSame([1, 1, 0], array_column($result['data'], 'Workspaces_CollectionChildren'));
    }

    #[Test]
    public function collectionServiceKeepsCircularlyRelatedElementsInTheDataArray(): void
    {
        $subject = $this->get(CollectionService::class);
        $dependencyResolver = $subject->getDependencyResolver();
        $dataArray = [];
        foreach ([2003, 2001, 2002] as $uid) {
            $dependencyResolver->addElement('tt_content', $uid);
            $dataArray['tt_content:' . $uid] = [
                'id' => 'tt_content:' . $uid,
                'table' => 'tt_content',
                'uid' => $uid,
                'Workspaces_Collection' => 0,
                'Workspaces_CollectionLevel' => 0,
                'Workspaces_CollectionParent' => '',
                'Workspaces_CollectionCurrent' => '',
                'Workspaces_CollectionChildren' => 0,
            ];
        }

        $result = $subject->process($dataArray);

        self::assertSame(['tt_content:2003', 'tt_content:2001', 'tt_content:2002'], array_column($result, 'id'));
        self::assertSame([0, 1, 2], array_column($result, 'Workspaces_CollectionLevel'));
        self::assertSame([1, 1, 0], array_column($result, 'Workspaces_CollectionChildren'));
        self::assertSame(md5('tt_content:2003'), $result[1]['Workspaces_CollectionParent']);
        self::assertSame(md5('tt_content:2001'), $result[2]['Workspaces_CollectionParent']);
    }

    #[Test]
    public function nestedElementsContainAllElementsOfACircularStructure(): void
    {
        $dependencyResolver = $this->createDependencyResolver([2003, 2001, 2002]);
        $outerMostParents = $dependencyResolver->getOuterMostParents();

        self::assertSame(['tt_content:2003'], array_keys($outerMostParents));
        self::assertSame(
            ['tt_content:2003', 'tt_content:2001', 'tt_content:2002'],
            $this->getNestedElementIdentifiers($dependencyResolver, $outerMostParents['tt_content:2003'])
        );
    }

    /**
     * Two outermost parents pointing into the same cyclic structure:
     *
     * tt_content:2011 -> tt_content:2013
     * tt_content:2012 -> tt_content:2014
     * tt_content:2013 -> tt_content:2014, tt_content:2015
     * tt_content:2014 -> tt_content:2013
     *
     * Both of them depend on the complete structure, no matter which one is resolved first.
     */
    #[Test]
    public function nestedElementsOfSubsequentOuterMostParentsAreComplete(): void
    {
        $dependencyResolver = $this->createDependencyResolver([2011, 2012, 2013, 2014, 2015]);
        $outerMostParents = $dependencyResolver->getOuterMostParents();

        self::assertSame(['tt_content:2011', 'tt_content:2012'], array_keys($outerMostParents));
        $nestedElements = [];
        foreach ($outerMostParents as $identifier => $outerMostParent) {
            $nestedElements[$identifier] = $this->getNestedElementIdentifiers($dependencyResolver, $outerMostParent);
            sort($nestedElements[$identifier]);
        }
        self::assertSame(
            [
                'tt_content:2011' => ['tt_content:2011', 'tt_content:2013', 'tt_content:2014', 'tt_content:2015'],
                'tt_content:2012' => ['tt_content:2012', 'tt_content:2013', 'tt_content:2014', 'tt_content:2015'],
            ],
            $nestedElements
        );
    }

    /**
     * @param int[] $uids
     */
    private function createDependencyResolver(array $uids): DependencyResolver
    {
        $dependencyResolver = GeneralUtility::makeInstance(DependencyResolver::class);
        $dependencyResolver->setWorkspace(91);
        $dependencyResolver->setEventDispatcher($this->get(EventDispatcherInterface::class));
        $dependencyResolver->setAction(DependencyCollectionAction::Publish);
        foreach ($uids as $uid) {
            $dependencyResolver->addElement('tt_content', $uid);
        }
        return $dependencyResolver;
    }

    /**
     * @return string[]
     */
    private function getNestedElementIdentifiers(DependencyResolver $dependencyResolver, ElementEntity $outerMostParent): array
    {
        return array_map(
            static fn(ElementEntity $element): string => (string)$element,
            array_values($dependencyResolver->getNestedElements($outerMostParent))
        );
    }
}
