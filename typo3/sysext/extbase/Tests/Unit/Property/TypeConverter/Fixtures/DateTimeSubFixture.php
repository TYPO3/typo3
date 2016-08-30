<?php
namespace TYPO3\CMS\Extbase\Tests\Unit\Property\TypeConverter\Fixtures;

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

/**
 * Fixture date time sub
 */
class DateTimeSubFixture extends \DateTime
{
    /**
     * @param string $format
     * @param string $time
     * @param \DateTimeZone $timezone
     * @return DateTimeSubFixture
     */
    public static function createFromFormat($format, $time, $timezone = null)
    {
        return new self();
    }

    /**
     * @return string
     */
    public function foo()
    {
        return 'Bar';
    }
}
