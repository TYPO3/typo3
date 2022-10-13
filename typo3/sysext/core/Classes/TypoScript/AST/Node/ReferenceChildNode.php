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

namespace TYPO3\CMS\Core\TypoScript\AST\Node;

use TYPO3\CMS\Core\TypoScript\Tokenizer\Token\IdentifierTokenStream;

/**
 * A node object created for LineIdentifierReference lines which use the T_OPERATOR_REFERENCE
 * operator and have a TokenStreamIdentifier stream for "the right side" of the expression.
 *
 * The reference operator is nasty, since it's no "true" reference / pointer:
 * foo.bar = barValue1
 * baz =< foo
 * baz.bar = barValue2
 * This ends up with "barValue1" for "foo.bar", and "barValue2" for "baz.bar". "barValue1"
 * for "foo.bar" is kept!
 *
 * Note the reference operator *only* works for TS "setup" code, not for "constants", and it
 * is only resolved in these cases. See ContentObjectRenderer->cObjGetSingle() for details.
 *
 * @internal: Internal AST structure.
 */
final class ReferenceChildNode extends AbstractChildNode
{
    private ?IdentifierTokenStream $referenceSourceStream;

    protected function serialize(): array
    {
        $result = parent::serialize();
        if ($this->referenceSourceStream !== null) {
            $result['referenceSourceStream'] = $this->referenceSourceStream;
        }
        return $result;
    }

    public function setReferenceSourceStream(?IdentifierTokenStream $referenceSourceStream): void
    {
        $this->referenceSourceStream = $referenceSourceStream;
    }

    public function getReferenceSourceStream(): IdentifierTokenStream
    {
        return $this->referenceSourceStream;
    }
}
