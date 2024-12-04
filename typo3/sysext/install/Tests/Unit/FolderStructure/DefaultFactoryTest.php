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

namespace TYPO3\CMS\Install\Tests\Unit\FolderStructure;

use PHPUnit\Framework\Attributes\DoesNotPerformAssertions;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Install\FolderStructure\DefaultFactory;
use TYPO3\CMS\Install\WebserverType;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class DefaultFactoryTest extends UnitTestCase
{
    #[Test]
    #[DoesNotPerformAssertions]
    public function getStructureDoesNotFail(): void
    {
        $webserverType = WebserverType::fromType('i-dont-care');
        (new DefaultFactory())->getStructure($webserverType);
    }
}
