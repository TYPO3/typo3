<?php
namespace TYPO3\CMS\Belog\Tests\Unit\Domain\Model;

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
 * Test case
 *
 */
class ConstraintTest extends \TYPO3\CMS\Core\Tests\UnitTestCase
{
    /**
     * @var \TYPO3\CMS\Belog\Domain\Model\Constraint
     */
    protected $subject = null;

    protected function setUp()
    {
        $this->subject = new \TYPO3\CMS\Belog\Domain\Model\Constraint();
    }

    /**
     * @test
     */
    public function setManualDateStartForDateTimeSetsManualDateStart()
    {
        $date = new \DateTime();
        $this->subject->setManualDateStart($date);

        $this->assertAttributeEquals($date, 'manualDateStart', $this->subject);
    }

    /**
     * @test
     */
    public function setManualDateStartForNoArgumentSetsManualDateStart()
    {
        $this->subject->setManualDateStart();

        $this->assertAttributeEquals(null, 'manualDateStart', $this->subject);
    }

    /**
     * @test
     */
    public function setManualDateStopForDateTimeSetsManualDateStop()
    {
        $date = new \DateTime();
        $this->subject->setManualDateStop($date);

        $this->assertAttributeEquals($date, 'manualDateStop', $this->subject);
    }

    /**
     * @test
     */
    public function setManualDateStopForNoArgumentSetsManualDateStop()
    {
        $this->subject->setManualDateStop();

        $this->assertAttributeEquals(null, 'manualDateStop', $this->subject);
    }
}
