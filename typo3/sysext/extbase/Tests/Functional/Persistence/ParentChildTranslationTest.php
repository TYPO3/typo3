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
use TYPO3\CMS\Core\Context\LanguageAspect;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;
use TYPO3Tests\ParentChildTranslation\Domain\Repository\MainRepository;

final class ParentChildTranslationTest extends FunctionalTestCase
{
    protected array $testExtensionsToLoad = ['typo3/sysext/extbase/Tests/Functional/Fixtures/Extensions/parent_child_translation'];

    #[Test]
    public function localizeChildrenOfAllLanguageElementToDefaultLanguage(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/parentChildTranslationExampleData.csv');
        // Init ConfigurationManagerInterface stateful singleton, usually done by extbase bootstrap
        $this->get(ConfigurationManagerInterface::class)->setRequest(
            (new ServerRequest())->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE)
        );

        $query = $this->get(MainRepository::class)->createQuery();
        $results = $query->execute();

        self::assertCount(2, $results);

        $children = [];
        foreach ($results as $main) {
            $children[] = $main->getChild()->getTitle();
            $children[] = $main->getSqueeze()->toArray()[0]->getChild()->getTitle();
        }

        self::assertSame(
            [
                'Child 1 EN',
                'Child 1 EN',
                'Child 2 EN',
                'Child 3 EN',
            ],
            $children
        );
    }

    #[Test]
    public function localizesChildrenOfAllLanguageElementToTranslation(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/parentChildTranslationExampleData.csv');
        // Init ConfigurationManagerInterface stateful singleton, usually done by extbase bootstrap
        $this->get(ConfigurationManagerInterface::class)->setRequest(
            (new ServerRequest())->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE)
        );

        $query = $this->get(MainRepository::class)->createQuery();
        $querySettings = $query->getQuerySettings();
        $querySettings->setStoragePageIds([1]);
        $querySettings->setRespectSysLanguage(true);
        $querySettings->setLanguageAspect(new LanguageAspect(1, 1, LanguageAspect::OVERLAYS_OFF));

        $results = $query->execute();

        self::assertCount(2, $results);

        $children = [];
        foreach ($results as $main) {
            $children[] = $main->getChild()->getTitle();
            $children[] = $main->getSqueeze()->toArray()[0]->getChild()->getTitle();
        }

        self::assertSame(
            [
                'Kind 1 DE',
                'Kind 1 DE',
                'Kind 2 DE',
                'Kind 3 DE',
            ],
            $children
        );
    }
}
