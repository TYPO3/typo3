<?php
declare(strict_types = 1);
namespace TYPO3\CMS\Core\Database\Query\Expression;

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
 * Facade of the Doctrine DBAL CompositeExpression to have
 * all Query related classes with in TYPO3\CMS namespace.
 */
class CompositeExpression extends \Doctrine\DBAL\Query\Expression\CompositeExpression
{
    /**
     * Retrieves the string representation of this composite expression.
     * If expression is empty, just return an empty string.
     * Native Doctrine expression would return () instead.
     *
     * @return string
     */
    public function __toString()
    {
        if ($this->count() === 0) {
            return '';
        }
        return parent::__toString();
    }

    /**
     * Adds an expression to composite expression.
     *
     * @param mixed $part
     *
     * @return \Doctrine\DBAL\Query\Expression\CompositeExpression
     */
    public function add($part)
    {
        // Due to a bug in Doctrine DBAL, we must add our own check here,
        // which we luckily can, as we use a subclass anyway.
        // @see https://github.com/doctrine/dbal/issues/2388
        $isEmpty = $part instanceof self ? $part->count() === 0 : empty($part);
        if (!$isEmpty) {
            parent::add($part);
        }

        return $this;
    }
}
