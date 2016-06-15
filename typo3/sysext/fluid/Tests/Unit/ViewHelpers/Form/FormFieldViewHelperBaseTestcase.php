<?php
namespace TYPO3\CMS\Fluid\Tests\Unit\ViewHelpers\Form;

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
use TYPO3Fluid\Fluid\Core\ViewHelper\ViewHelperInterface;

/**
 * Test for the Abstract Form view helper
 */
abstract class FormFieldViewHelperBaseTestcase extends \TYPO3\CMS\Fluid\Tests\Unit\ViewHelpers\ViewHelperBaseTestcase
{
    /**
     * @var \TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface
     */
    protected $mockConfigurationManager;

    /**
     * @return void
     */
    protected function setUp()
    {
        parent::setUp();
        $this->mockConfigurationManager = $this->createMock(\TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface::class);
    }

    /**
     * @param ViewHelperInterface $viewHelper
     * @return void
     */
    protected function injectDependenciesIntoViewHelper(ViewHelperInterface $viewHelper)
    {
        $viewHelper->_set('configurationManager', $this->mockConfigurationManager);
        parent::injectDependenciesIntoViewHelper($viewHelper);
    }
}
