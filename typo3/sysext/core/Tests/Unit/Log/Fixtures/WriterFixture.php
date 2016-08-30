<?php
namespace TYPO3\CMS\Core\Tests\Unit\Log\Fixtures;

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
 * A writer dedicated for testing
 */
class WriterFixture extends \TYPO3\CMS\Core\Log\Writer\AbstractWriter
{
    /**
     * @var array
     */
    protected $records = [];

    public function writeLog(\TYPO3\CMS\Core\Log\LogRecord $record)
    {
        $this->records[] = $record;
    }
}
