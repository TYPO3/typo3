<?php
namespace TYPO3\CMS\Lang\Tests\Unit\Domain\Model;

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
 * Testcase for Language
 */
class LanguageTest extends \TYPO3\CMS\Core\Tests\UnitTestCase
{
    /**
     * @var \TYPO3\CMS\Lang\Domain\Model\Language
     */
    protected $subject = null;

    /**
     * Set up
     */
    protected function setUp()
    {
        $this->subject = new \TYPO3\CMS\Lang\Domain\Model\Language();
    }

    /**
     * @test
     */
    public function getLocaleInitiallyReturnsEmptyString()
    {
        $this->assertSame(
            '',
            $this->subject->getLocale()
        );
    }

    /**
     * @test
     */
    public function getLocaleInitiallyReturnsGivenLocaleFromConstruct()
    {
        $locale = 'nl';
        $this->subject = new \TYPO3\CMS\Lang\Domain\Model\Language($locale);

        $this->assertSame(
            $locale,
            $this->subject->getLocale()
        );
    }

    /**
     * @test
     */
    public function setLocaleSetsLocale()
    {
        $locale = 'nl';
        $this->subject->setLocale($locale);

        $this->assertSame(
            $locale,
            $this->subject->getLocale()
        );
    }

    /**
     * @test
     */
    public function getLanguageInitiallyReturnsEmptyString()
    {
        $this->assertSame(
            '',
            $this->subject->getLabel()
        );
    }

    /**
     * @test
     */
    public function getLanguageInitiallyReturnsGivenLanguageFromConstruct()
    {
        $language = 'nl';
        $this->subject = new \TYPO3\CMS\Lang\Domain\Model\Language('', $language);

        $this->assertSame(
            $language,
            $this->subject->getLabel()
        );
    }

    /**
     * @test
     */
    public function setLanguageSetsLanguage()
    {
        $language = 'nl';
        $this->subject->setLabel($language);

        $this->assertSame(
            $language,
            $this->subject->getLabel()
        );
    }

    /**
     * @test
     */
    public function getSelectedInitiallyReturnsFalse()
    {
        $this->assertSame(
            false,
            $this->subject->getSelected()
        );
    }

    /**
     * @test
     */
    public function getSelectedInitiallyReturnsGivenSelectedFromConstruct()
    {
        $selected = false;
        $this->subject = new \TYPO3\CMS\Lang\Domain\Model\Language('', '', false);

        $this->assertSame(
            $selected,
            $this->subject->getSelected()
        );
    }

    /**
     * @test
     */
    public function setSelectedSetsSelected()
    {
        $selected = true;
        $this->subject->setSelected($selected);

        $this->assertSame(
            $selected,
            $this->subject->getSelected()
        );
    }
}
