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

namespace TYPO3\CMS\Core\Schema\Capability;

/**
 * Can be used for any kind of field which does NOT have
 * a definition in the "columns" section of TCA.
 *
 * -> sortBy
 * -> crdate
 * -> tstamp
 * -> delete
 */
final readonly class SystemInternalFieldCapability implements SchemaCapabilityInterface
{
    public function __construct(
        protected string $fieldName
    ) {}

    public function getFieldName(): string
    {
        return $this->fieldName;
    }

    public function __toString(): string
    {
        return $this->getFieldName();
    }
}
