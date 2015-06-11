<?php
namespace TYPO3\CMS\Fluid\Tests\Unit\Core\Fixtures;

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
 * Class TestViewHelper
 */
class TestViewHelper extends \TYPO3\CMS\Fluid\Core\ViewHelper\AbstractViewHelper
{
    /**
     * My comments. Bla blubb.
     *
     * @param int $param1 P1 Stuff
     * @param array $param2 P2 Stuff
     * @param string $param3 P3 Stuff
     */
    public function render($param1, array $param2, $param3 = 'default')
    {
    }
}
