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

namespace TYPO3\CMS\Styleguide\Service;

/**
 * Get test strings
 *
 * @internal
 */
final class KauderwelschService
{
    /**
     * Lorem ipsum test with fixed length.
     */
    public function getLoremIpsum(): string
    {
        return 'Bacon ipsum dolor sit strong amet capicola jerky pork chop rump shoulder shank. Shankle strip steak pig salami link.';
    }

    /**
     * Lorem ipsum test with fixed length and HTML in it.
     */
    public function getLoremIpsumHtml(): string
    {
        return 'Bacon ipsum dolor sit <strong>strong amet capicola</strong> jerky pork chop rump shoulder shank. Shankle strip <a href="#">steak pig salami link</a>. Leberkas shoulder ham hock cow salami bacon <em>em pork pork</em> chop, jerky pork belly drumstick ham. Tri-tip strip steak sirloin prosciutto pastrami. Corned beef venison tenderloin, biltong meatball pork tongue short ribs jowl cow hamburger strip steak. Doner turducken jerky short loin chuck filet mignon.';
    }

    /**
     * Get a single word
     */
    public function getWord(): string
    {
        return 'lipsum';
    }

    /**
     * Get a single password
     */
    public function getPassword(): string
    {
        return 'somePassword1!';
    }

    /**
     * Get an integer
     */
    public function getInteger(): int
    {
        return 42;
    }

    /**
     * Timestamp of a day before 1970
     */
    public function getDateTimestamp(): int
    {
        // 1960-1-1 00:00:00 GMT
        return -315619200;
    }

    /**
     * Timestamp of a day before 1970 with seconds
     */
    public function getDatetimeTimestamp(): int
    {
        // 1960-1-1 05:23:42 GMT
        return -315599778;
    }

    /**
     * Date before 1970 as string
     */
    public function getDateString(): string
    {
        // GMT
        return '1960-01-01';
    }

    /**
     * Date before 1970 with seconds as string
     */
    public function getDatetimeString(): string
    {
        // GMT
        return '1960-01-01 05:42:23';
    }

    /**
     * Get a float
     */
    public function getFloat(): float
    {
        return 5.23;
    }

    /**
     * Get a link
     */
    public function getLink(): string
    {
        return 'https://typo3.org';
    }

    /**
     * Get a valid email
     */
    public function getEmail(): string
    {
        return 'foo@example.com';
    }

    /**
     * Get a valid uuid v4 / v7 - both have same format
     */
    public function getUuid(): string
    {
        return '2cc152d0-08b4-438b-92e5-4b6e8d90b465';
    }

    /**
     * Get a color as hex string
     */
    public function getHexColor(): string
    {
        return '#FF8700';
    }

    /**
     * Get a json as array
     */
    public function getJson(): array
    {
        return [
            [
                'name' => 'Alice',
                'age' => 25,
            ],
            [
                'name' => 'Bob',
                'age' => 42,
            ],
        ];
    }
}
