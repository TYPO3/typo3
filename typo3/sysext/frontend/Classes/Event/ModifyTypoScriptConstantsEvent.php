<?php

declare(strict_types=1);

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

namespace TYPO3\CMS\Frontend\Event;

use TYPO3\CMS\Core\TypoScript\AST\Node\RootNode;

/**
 * This event will provide listeners with the opportunity to adjust constants according to the users' requirements
 *
 * @internal This event did not stabilize yet and may change. Use at your own risk.
 *           Note the TypoScript AST is also marked @internal at the moment and may change as well.
 */
final class ModifyTypoScriptConstantsEvent
{
    public function __construct(
        private RootNode $constantsAst,
    ) {}

    public function getConstantsAst(): RootNode
    {
        return $this->constantsAst;
    }

    public function setConstantsAst(RootNode $constantsAst): void
    {
        $this->constantsAst = $constantsAst;
    }
}
