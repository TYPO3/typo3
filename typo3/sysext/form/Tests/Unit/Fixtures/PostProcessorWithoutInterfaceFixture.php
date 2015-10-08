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

/**
 * Post processor with form prefix fixture
 */
class PostProcessorWithoutInterfaceFixture
{
    /**
     * @param \TYPO3\CMS\Form\Domain\Model\Element $form
     * @param array $typoScript
     */
    public function __construct(\TYPO3\CMS\Form\Domain\Model\Element $form, array $typoScript)
    {
    }

    /**
     * @return string
     */
    public function process()
    {
        return 'withoutInterface';
    }
}
