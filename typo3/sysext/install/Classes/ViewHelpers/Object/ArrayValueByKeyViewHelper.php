<?php
namespace TYPO3\CMS\Install\ViewHelpers\Object;

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
use TYPO3\CMS\Install\ViewHelpers\Exception;

/**
 * View helper which allows you to access a key in an array.
 *
 * = Examples =
 *
 * <code title="Defaults">
 * <i:object.key array="{array}" key="{key}" />
 * </code>
 * <output>
 * The key in the array, if it exists, otherwise an empty string.
 * </output>
 *
 * @internal
 */
class ArrayValueByKeyViewHelper extends AbstractViewHelper implements CompilableInterface
{
    /**
     * Get the value of an key in an array.
     *
     * @param array $array The array being processed
     * @param mixed $key The key being accessed
     * @return string
     */
    public function render(array $array, $key)
    {
        return static::renderStatic(
            [
                'array' => $array,
                'key' => $key,
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
     * @throws Exception
     */
    public static function renderStatic(array $arguments, \Closure $renderChildrenClosure, RenderingContextInterface $renderingContext)
    {
        $array = $arguments['array'];
        $key = $arguments['key'];
        $result = '';
        if (isset($array[$key])) {
            $result = $array[$key];
        }
        if (!is_scalar($result)) {
            throw new Exception(
                'Only scalar return values (string, int, float or double) are supported.',
                1430852128
            );
        }
        return (string)$result;
    }
}
