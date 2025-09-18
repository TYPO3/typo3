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

namespace TYPO3\CMS\Core\SystemResource\Identifier;

use TYPO3\CMS\Core\SystemResource\Exception\InvalidSystemResourceIdentifierException;
use TYPO3\CMS\Core\Utility\MathUtility;

/**
 * This is subject to change during v14 development. Do not use.
 * @internal Only to be used in TYPO3\CMS\Core\SystemResource namespace
 */
final class FalResourceIdentifier extends SystemResourceIdentifier
{
    public const TYPE = 'FAL';

    public function __construct(private readonly string $storageId, private readonly string $falIdentifier, string $givenIdentifier)
    {
        parent::__construct($givenIdentifier);
        if (!MathUtility::canBeInterpretedAsInteger($storageId)) {
            throw new InvalidSystemResourceIdentifierException(sprintf('Given identifier "%s" is invalid. Storage id must be integer.', $givenIdentifier), 1760433315);
        }
    }

    public function getIdentifier(): string
    {
        return sprintf('%d:%s', $this->storageId, $this->falIdentifier);
    }

    public function __toString()
    {
        return sprintf(
            '%s:%d:%s',
            self::TYPE,
            $this->storageId,
            $this->falIdentifier,
        );
    }
}
