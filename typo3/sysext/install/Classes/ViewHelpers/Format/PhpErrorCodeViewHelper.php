<?php
namespace TYPO3\CMS\Install\ViewHelpers\Format;

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

use TYPO3\CMS\Fluid\Core\Rendering\RenderingContextInterface;
use TYPO3\CMS\Fluid\Core\ViewHelper\AbstractViewHelper;
use TYPO3\CMS\Fluid\Core\ViewHelper\Facets\CompilableInterface;

/**
 * Transform PHP error code to readable text
 *
 * @internal
 */
class PhpErrorCodeViewHelper extends AbstractViewHelper implements CompilableInterface
{
    /**
     * @var array
     */
    protected static $levelNames = [
        E_ERROR => 'E_ERROR',
        E_WARNING => 'E_WARNING',
        E_PARSE => 'E_PARSE',
        E_NOTICE => 'E_NOTICE',
        E_CORE_ERROR => 'E_CORE_ERROR',
        E_CORE_WARNING => 'E_CORE_WARNING',
        E_COMPILE_ERROR => 'E_COMPILE_ERROR',
        E_COMPILE_WARNING => 'E_COMPILE_WARNING',
        E_USER_ERROR => 'E_USER_ERROR',
        E_USER_WARNING => 'E_USER_WARNING',
        E_USER_NOTICE => 'E_USER_NOTICE',
        E_STRICT => 'E_STRICT',
        E_RECOVERABLE_ERROR => 'E_RECOVERABLE_ERROR',
        E_DEPRECATED => 'E_DEPRECATED',
        E_USER_DEPRECATED => 'E_USER_DEPRECATED',
    ];

    /**
     * Render a readable string for PHP error code
     *
     * @param int $phpErrorCode
     * @return string
     */
    public function render($phpErrorCode)
    {
        return static::renderStatic(
            [
                'phpErrorCode' => $phpErrorCode,
            ],
            $this->buildRenderChildrenClosure(),
            $this->renderingContext
        );
    }

    /**
     * @param array $arguments
     * @param callable $renderChildrenClosure
     * @param RenderingContextInterface $renderingContext
     *
     * @return string
     */
    public static function renderStatic(array $arguments, \Closure $renderChildrenClosure, RenderingContextInterface $renderingContext)
    {
        $phpErrorCode = $arguments['phpErrorCode'];
        $levels = [];
        if (($phpErrorCode & E_ALL) == E_ALL) {
            $levels[] = 'E_ALL';
            $phpErrorCode &= ~E_ALL;
        }
        foreach (self::$levelNames as $level => $name) {
            if (($phpErrorCode & $level) == $level) {
                $levels[] = $name;
            }
        }

        $output = '';
        if (!empty($levels)) {
            $output = implode(' | ', $levels);
        }

        return $output;
    }
}
