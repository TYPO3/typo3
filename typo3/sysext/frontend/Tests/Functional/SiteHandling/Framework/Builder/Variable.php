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

namespace TYPO3\CMS\Frontend\Tests\Functional\SiteHandling\Framework\Builder;

class Variable
{
    public const CAST_NONE = 0;
    public const CAST_STRING = 1;
    public const CAST_INT = 2;
    public const CAST_FLOAT = 3;

    /**
     * @var string
     */
    private $variableName;

    /**
     * @var int
     */
    private $cast;

    public static function create(string $variableName, int $cast = self::CAST_NONE): self
    {
        return new static($variableName, $cast);
    }

    private function __construct(string $variableName, int $cast = self::CAST_NONE)
    {
        $this->variableName = $variableName;
        $this->cast = $cast;
    }

    public function apply(Variables $variables)
    {
        if (!isset($variables[$this->variableName])) {
            throw new \LogicException(
                sprintf(
                    'Missing variable name "%s"',
                    $this->variableName
                ),
                1577789317
            );
        }
        return $this->cast($variables[$this->variableName]);
    }

    private function cast($value)
    {
        switch ($this->cast) {
            case self::CAST_NONE:
                return $value;
            case self::CAST_STRING:
                return (string)$value;
            case self::CAST_INT:
                return (int)$value;
            case self::CAST_FLOAT:
                return (float)$value;
        }
        return $value;
    }
}
