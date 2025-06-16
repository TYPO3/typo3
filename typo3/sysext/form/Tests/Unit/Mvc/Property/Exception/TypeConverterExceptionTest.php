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

namespace TYPO3\CMS\Form\Tests\Unit\Mvc\Property\Exception;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Extbase\Error\Error;
use TYPO3\CMS\Form\Mvc\Property\Exception\TypeConverterException;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class TypeConverterExceptionTest extends UnitTestCase
{
    #[Test]
    public function errorMessageWithPlaceholdersAreReplaced(): void
    {
        $error = new Error('"%s" is no integer.', 123, ['foo']);

        $result = TypeConverterException::fromError($error);

        self::assertSame('"foo" is no integer.', $result->getMessage());
    }
}
