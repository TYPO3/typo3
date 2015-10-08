<?php
namespace TYPO3\CMS\Documentation\Tests\Unit\Domain\Model;

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
 */
class DocumentTranslationTest extends \TYPO3\CMS\Core\Tests\UnitTestCase
{
    /**
     * @var \TYPO3\CMS\Documentation\Domain\Model\DocumentTranslation
     */
    protected $subject;

    protected function setUp()
    {
        $this->subject = new \TYPO3\CMS\Documentation\Domain\Model\DocumentTranslation();
    }

    /**
     * @test
     */
    public function setLanguageForStringSetsLocale()
    {
        $this->subject->setLanguage('Conceived at T3DD13');

        $this->assertSame(
            'Conceived at T3DD13',
            $this->subject->getLanguage()
        );
    }

    /**
     * @test
     */
    public function setTitleForStringSetsTitle()
    {
        $this->subject->setTitle('Conceived at T3DD13');

        $this->assertSame(
            'Conceived at T3DD13',
            $this->subject->getTitle()
        );
    }

    /**
     * @test
     */
    public function getFormatsReturnsInitialValueForDocumentFormat()
    {
        $newObjectStorage = new \TYPO3\CMS\Extbase\Persistence\ObjectStorage();
        $this->assertEquals(
            $newObjectStorage,
            $this->subject->getFormats()
        );
    }

    /**
     * @test
     */
    public function setFormatsForObjectStorageContainingDocumentFormatSetsFormats()
    {
        $format = new \TYPO3\CMS\Documentation\Domain\Model\DocumentFormat();
        $objectStorageHoldingExactlyOneFormats = new \TYPO3\CMS\Extbase\Persistence\ObjectStorage();
        $objectStorageHoldingExactlyOneFormats->attach($format);
        $this->subject->setFormats($objectStorageHoldingExactlyOneFormats);

        $this->assertSame(
            $objectStorageHoldingExactlyOneFormats,
            $this->subject->getFormats()
        );
    }

    /**
     * @test
     */
    public function addFormatToObjectStorageHoldingFormats()
    {
        $format = new \TYPO3\CMS\Documentation\Domain\Model\DocumentFormat();
        $objectStorageHoldingExactlyOneFormat = new \TYPO3\CMS\Extbase\Persistence\ObjectStorage();
        $objectStorageHoldingExactlyOneFormat->attach($format);
        $this->subject->addFormat($format);

        $this->assertEquals(
            $objectStorageHoldingExactlyOneFormat,
            $this->subject->getFormats()
        );
    }

    /**
     * @test
     */
    public function removeFormatFromObjectStorageHoldingFormats()
    {
        $format = new \TYPO3\CMS\Documentation\Domain\Model\DocumentFormat();
        $localObjectStorage = new \TYPO3\CMS\Extbase\Persistence\ObjectStorage();
        $localObjectStorage->attach($format);
        $localObjectStorage->detach($format);
        $this->subject->addFormat($format);
        $this->subject->removeFormat($format);

        $this->assertEquals(
            $localObjectStorage,
            $this->subject->getFormats()
        );
    }
}
