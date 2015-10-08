<?php
namespace TYPO3\CMS\Backend\Tests\Unit\View\BackendLayout;

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
 * Testing backend layout representation.
 */
class BackendLayoutTest extends \TYPO3\CMS\Core\Tests\UnitTestCase
{
    /**
     * @test
     * @expectedException \UnexpectedValueException
     */
    public function invalidIdentifierIsRecognizedOnCreation()
    {
        $identifier = $this->getUniqueId('identifier__');
        $title = $this->getUniqueId('title');
        $configuration = $this->getUniqueId('configuration');
        new \TYPO3\CMS\Backend\View\BackendLayout\BackendLayout($identifier, $title, $configuration);
    }

    /**
     * @test
     */
    public function objectIsCreated()
    {
        $identifier = $this->getUniqueId('identifier');
        $title = $this->getUniqueId('title');
        $configuration = $this->getUniqueId('configuration');
        $backendLayout = new \TYPO3\CMS\Backend\View\BackendLayout\BackendLayout($identifier, $title, $configuration);

        $this->assertEquals($identifier, $backendLayout->getIdentifier());
        $this->assertEquals($title, $backendLayout->getTitle());
        $this->assertEquals($configuration, $backendLayout->getConfiguration());
    }
}
