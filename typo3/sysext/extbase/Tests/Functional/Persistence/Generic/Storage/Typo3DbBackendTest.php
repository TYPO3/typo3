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

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Context\LanguageAspect;
use TYPO3\CMS\Core\Context\WorkspaceAspect;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\TypoScript\AST\Node\RootNode;
use TYPO3\CMS\Core\TypoScript\FrontendTypoScript;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Extbase\Persistence\Generic\Storage\Typo3DbBackend;
use TYPO3\CMS\Extbase\Persistence\Generic\Typo3QuerySettings;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;
use TYPO3Tests\BlogExample\Domain\Model\Tag;
use TYPO3Tests\BlogExample\Domain\Repository\BlogRepository;

final class Typo3DbBackendTest extends FunctionalTestCase
{
    protected bool $resetSingletonInstances = true;

    protected array $testExtensionsToLoad = [
        'typo3/sysext/extbase/Tests/Functional/Fixtures/Extensions/blog_example',
    ];

    #[Test]
    public function uidOfAlreadyPersistedValueObjectIsDeterminedCorrectly(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/Typo3DbBackendTestImport.csv');
        $domainObject = new Tag('Tag10');
        $typo3DbBackend = $this->get(Typo3DbBackend::class);
        $result = $typo3DbBackend->getUidOfAlreadyPersistedValueObject($domainObject);
        self::assertSame(10, $result);
    }

    #[Test]
    public function getObjectDataByQueryChangesUidIfInPreview(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/Typo3DbBackendTestImport.csv');
        $frontendTypoScript = new FrontendTypoScript(new RootNode(), [], [], []);
        $frontendTypoScript->setSetupArray([]);
        $request = (new ServerRequest())
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_FE)
            ->withAttribute('frontend.typoscript', $frontendTypoScript);
        $blogRepository = $this->get(BlogRepository::class);
        $context = new Context();
        $context->setAspect('workspace', new WorkspaceAspect(1));
        $context->setAspect('language', new LanguageAspect(0, 0));
        GeneralUtility::setSingletonInstance(Context::class, $context);
        $configurationManager = $this->get(ConfigurationManagerInterface::class);
        $configurationManager->setRequest($request);
        $querySettings = new Typo3QuerySettings($context, $configurationManager);
        $querySettings->setRespectStoragePage(false);
        $query = $blogRepository->createQuery();
        $query->setQuerySettings($querySettings);
        $query->matching($query->equals('uid', 1));

        $typo3DbBackend = $this->get(Typo3DbBackend::class);
        $objectData = $typo3DbBackend->getObjectDataByQuery($query);

        self::assertCount(1, $objectData);
        self::assertArrayHasKey('_ORIG_uid', $objectData[0]);
        self::assertSame(101, $objectData[0]['_ORIG_uid']);
        self::assertSame('WorkspaceOverlay Blog1', $objectData[0]['title']);
    }
}
