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

namespace TYPO3\CMS\Core\Schema;

/**
 * @internal This is an experimental implementation and might change until TYPO3 v13 LTS
 */
enum FieldFormat: string
{
    case Date = 'date';

    case Datetime = 'datetime';

    case Time = 'time';

    case Timesec = 'timesec';

    case Year = 'year';

    case Int = 'int';

    case Float = 'float';

    case Number = 'number';

    case Md5 = 'md5';

    case Filesize = 'filesize';

    case User = 'user';

    case Undefined = '';

    private const FORMAT_CONFIGURATION = [
        self::Date->value => [
            'strftime',
            'option',
            'appendAge',
        ],
        self::Int->value => [
            'base',
        ],
        self::Float->value => [
            'precision',
        ],
        self::Number->value => [
            'option',
        ],
        self::Filesize->value => [
            'appendByteSize',
        ],
        self::User->value => [
            'userFunc',
        ],
    ];

    public static function fromTcaConfiguration(array $configuration): self
    {
        if (isset($configuration['config'])) {
            $configuration = $configuration['config'];
        }
        if (isset($configuration['format'])) {
            return match ($configuration['format']) {
                'date' => self::Date,
                'datetime' => self::Datetime,
                'time' => self::Time,
                'timesec' => self::Timesec,
                'year' => self::Year,
                'int' => self::Int,
                'float' => self::Float,
                'number' => self::Number,
                'md5' => self::Md5,
                'filesize' => self::Filesize,
                'user' => self::User,
                default => throw new \UnexpectedValueException('Invalid format: ' . $configuration['format'], 1724744407),
            };
        }

        return self::Undefined;
    }

    public function getFormatConfiguration(array $configuration): array
    {
        if (isset($configuration['config'])) {
            $configuration = $configuration['config'];
        }

        if (!isset(self::FORMAT_CONFIGURATION[$this->value])
            || !is_array($configuration['format.'] ?? false)
            || $configuration['format.'] === []
        ) {
            return [];
        }

        return array_filter($configuration['format.'], fn(string $option): bool => in_array($option, self::FORMAT_CONFIGURATION[$this->value], true), ARRAY_FILTER_USE_KEY);
    }
}
