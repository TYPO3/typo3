<?php
declare(strict_types = 1);
namespace TYPO3\CMS\Frontend\Tests\Functional\SiteHandling\Fixtures;

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

use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\Internal\ArrayValueInstruction;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\RequestBootstrap;

/**
 * Test case for frontend requests having site handling configured
 */
class LinkGeneratorController
{
    /**
     * @var ContentObjectRenderer
     */
    public $cObj;

    public function mainAction(): string
    {
        $instruction = RequestBootstrap::getInternalRequest()
            ->getInstruction(LinkGeneratorController::class);
        if (!$instruction instanceof ArrayValueInstruction) {
            return '';
        }
        return $this->cObj->cObjGet($instruction->getArray());
    }
}
