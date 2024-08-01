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

namespace TYPO3\CMS\Styleguide\TcaDataGenerator;

/**
 * Interface for single FieldGeneratorInterface implementations
 * if they need the RecordData instance again.
 *
 * This prevents a dependency loop between RecordData which gets
 * FieldGeneratorResolver injected, which gets a list of all
 * field generators injected, and single field generators that
 * need RecordData again.
 *
 * There are two potential solutions in symfony DI to resolve such
 * a situation: First, RecordData could be injected lazily into
 * single field generators. This would be a misuse of lazy, though:
 * The idea of lazy is to have dependencies injected only if the
 * injected object is used only seldom. This is not the case here.
 * The second solution is to have an interface like this one, and
 * make RecordData setRecordData($this) to single generators if
 * they implement the interface. This is what we are going with.
 */
interface RecordDataAwareInterface
{
    public function setRecordData(RecordData $recordData): void;
}
