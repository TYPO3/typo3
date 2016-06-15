<?php
namespace TYPO3\CMS\Form\Tests\Unit\Validator;

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

use TYPO3\CMS\Form\Domain\Validator\AbstractValidator;
use TYPO3\CMS\Form\Utility\FormUtility;

/**
 * Test case
 */
abstract class AbstractValidatorTest extends \TYPO3\CMS\Core\Tests\UnitTestCase
{
    /**
     * Must be filled with subject class name
     * in specific test implementation.
     *
     * @var string
     */
    protected $subjectClassName = null;

    /**
     * @param array $options
     * @return AbstractValidator|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function createSubject(array $options)
    {
        /** @var AbstractValidator $subject */
        $subject = $this->getMockBuilder($this->subjectClassName)
            ->setMethods(['getLocalLanguageLabel', 'humanReadableDateFormat'])
            ->setConstructorArgs(['options' => $options])
            ->getMock();

        /** @var FormUtility $formUtilityMock */
        $formUtilityMock = $this->createMock(FormUtility::class);
        $subject->setFormUtility($formUtilityMock);

        return $subject;
    }
}
