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

namespace TYPO3\CMS\Core\Security\ContentSecurityPolicy\Reporting;

/**
 * @internal
 */
class ReportDetails extends \ArrayObject implements \JsonSerializable
{
    public function __construct(array $array)
    {
        if (!empty($array['violated-directive']) && !isset($array['effective-directive'])) {
            $array['effective-directive'] = $array['violated-directive'];
        }
        parent::__construct($array);
    }

    public function jsonSerialize(): array
    {
        $details = $this->getArrayCopy();
        return array_combine(
            array_map(self::toCamelCase(...), array_keys($details)),
            array_values($details)
        );
    }

    protected static function toCamelCase(string $value): string
    {
        return lcfirst(str_replace('-', '', ucwords($value, '-')));
    }
}
