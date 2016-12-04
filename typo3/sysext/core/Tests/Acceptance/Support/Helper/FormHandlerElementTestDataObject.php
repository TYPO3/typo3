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

namespace TYPO3\CMS\Core\Tests\Acceptance\Support\Helper;

/**
 * Class FormHandlerElementTestDataObject.
 */
class FormHandlerElementTestDataObject
{
    /**
     * The test value.
     *
     * @var string
     */
    public $inputValue;

    /**
     * The expected value as seen by the user.
     *
     * @var string
     */
    public $expectedValue;

    /**
     * The expected value in the value attribute of the hidden field
     * (full ISO date for example).
     *
     * @var string
     */
    public $expectedInternalValue;

    /**
     * Expected value in hidden field after saving the data.
     *
     * @var string
     */
    public $expectedValueAfterSave;

    /**
     * Does this test data set result in a modal window (e.g. for errors)?
     *
     * @var bool
     */
    public $notificationExpected;

    /**
     * Comment echoed in test log.
     *
     * @var string
     */
    public $comment;

    /**
     * FormHandlerElementTestDataObject constructor.
     *
     * @param string $inputValue
     * @param string $expectedValue
     * @param string $expectedInternalValue
     * @param string $expectedValueAfterSave
     * @param bool   $notificationExpected
     * @param string $comment
     */
    public function __construct(
        string $inputValue,
        string $expectedValue,
        string $expectedInternalValue = '',
        string $expectedValueAfterSave = '',
        bool $notificationExpected = false,
        string $comment = ''
    ) {
        $this->inputValue = $inputValue;
        $this->expectedValue = $expectedValue;
        $this->expectedInternalValue = $expectedInternalValue;
        $this->expectedValueAfterSave = $expectedValueAfterSave;
        $this->notificationExpected = $notificationExpected;
        $this->comment = $comment;
    }
}
