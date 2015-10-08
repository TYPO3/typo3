<?php
namespace TYPO3\CMS\Form\Tests\Unit\Fixtures;

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

use TYPO3\CMS\Form\PostProcess\AbstractPostProcessor;
use TYPO3\CMS\Form\PostProcess\PostProcessorInterface;

/**
 * Post processor with form prefix fixture
 */
class PostProcessorWithFormPrefixFixture extends AbstractPostProcessor implements PostProcessorInterface
{
    /**
     * @param \TYPO3\CMS\Form\Domain\Model\Element $form
     * @param array $typoScript
     */
    public function __construct(\TYPO3\CMS\Form\Domain\Model\Element $form, array $typoScript)
    {
    }

    /**
     * @param \TYPO3\CMS\Form\Mvc\Controller\ControllerContext $controllerContext
     */
    public function setControllerContext(\TYPO3\CMS\Form\Mvc\Controller\ControllerContext $controllerContext)
    {
    }

    /**
     * @return string
     */
    public function process()
    {
        return 'processedWithPrefix';
    }
}
