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

namespace TYPO3\CMS\Core\Tests\Functional\Resource\Index;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Resource\Index\MetaDataRepository;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class MetaDataRepositoryTest extends FunctionalTestCase
{
    protected array $coreExtensionsToLoad = ['filemetadata'];

    #[Test]
    public function canUpdateAnExistingFieldWithEmptyValue(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/FileWithMetaData.csv');
        $subject = $this->get(MetaDataRepository::class);
        $result = $subject->update(1, [
            'copyright' => '',
        ]);
        self::assertSame('', $result['copyright']);
    }

    #[Test]
    public function canUpdateAnExistingFieldWithNewValue(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/FileWithMetaData.csv');
        $subject = $this->get(MetaDataRepository::class);
        $result = $subject->update(1, [
            'copyright' => 'Something New',
        ]);
        self::assertSame('Something New', $result['copyright']);
    }
}
