<?php
namespace TYPO3\CMS\Core\Tests\Unit\Imaging;

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

use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Type\Icon\IconState;

/**
 * Testcase for \TYPO3\CMS\Core\Imaging\Icon
 */
class IconTest extends \TYPO3\CMS\Core\Tests\UnitTestCase
{
    /**
     * @var \TYPO3\CMS\Core\Imaging\Icon
     */
    protected $subject = null;

    /**
     * @var string
     */
    protected $iconIdentifier = 'actions-document-close';

    /**
     * @var string
     */
    protected $overlayIdentifier = 'overlay-readonly';

    /**
     * Set up
     *
     * @return void
     */
    protected function setUp()
    {
        $iconFactory = new IconFactory();
        $this->subject = $iconFactory->getIcon($this->iconIdentifier, Icon::SIZE_SMALL, $this->overlayIdentifier, IconState::cast(IconState::STATE_DISABLED));
    }

    /**
     * @test
     */
    public function renderAndCastToStringReturnsTheSameCode()
    {
        $this->assertEquals($this->subject->render(), (string)$this->subject);
    }

    /**
     * @test
     */
    public function getIdentifierReturnsCorrectIdentifier()
    {
        $this->assertEquals($this->iconIdentifier, $this->subject->getIdentifier());
    }

    /**
     * @test
     */
    public function getOverlayIdentifierReturnsCorrectIdentifier()
    {
        $this->assertEquals($this->overlayIdentifier, $this->subject->getOverlayIcon()->getIdentifier());
    }

    /**
     * @test
     */
    public function getSizedentifierReturnsCorrectIdentifier()
    {
        $this->assertEquals(Icon::SIZE_SMALL, $this->subject->getSize());
    }

    /**
     * @test
     */
    public function getStateReturnsCorrectIdentifier()
    {
        $this->assertTrue($this->subject->getState()->equals(IconState::STATE_DISABLED));
    }
}
