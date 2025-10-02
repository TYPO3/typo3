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

namespace TYPO3\CMS\Core\Tests\Unit\Schema;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Schema\Field\FieldCollection;
use TYPO3\CMS\Core\Schema\SchemaCollection;
use TYPO3\CMS\Core\Schema\TcaSchema;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class SchemaCollectionTest extends UnitTestCase
{
    #[Test]
    public function ensureEmptyCollectionIsEmpty(): void
    {
        $subject = new SchemaCollection([]);
        self::assertSame([], $subject->getNames());
    }

    #[Test]
    public function ensureCollectionIsNotEmpty(): void
    {
        $schema = new TcaSchema('example', new FieldCollection([]), []);
        $subject = new SchemaCollection(['example' => $schema]);
        self::assertSame(['example'], $subject->getNames());
    }

    #[Test]
    public function ensureCollectionIsReadOnly(): void
    {
        $schema = new TcaSchema('example', new FieldCollection([]), []);
        $subject = new SchemaCollection([]);
        $this->expectExceptionCode(1712539286);
        $subject['example'] = $schema;
    }

    #[Test]
    public function ensureCollectionIsReadOnlyByCallingUnset(): void
    {
        $schema = new TcaSchema('example', new FieldCollection([]), []);
        $subject = new SchemaCollection(['example' => $schema]);
        $this->expectExceptionCode(1712539285);
        unset($subject['example']);
    }
}
