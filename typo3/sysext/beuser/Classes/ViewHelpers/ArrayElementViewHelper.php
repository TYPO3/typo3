<?php
namespace TYPO3\CMS\Beuser\ViewHelpers;

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
 * Get a value from an array by given key.
 */
class ArrayElementViewHelper extends AbstractViewHelper implements CompilableInterface
{
    /**
     * Return array element by key. Accessed values must be scalar (string, int, float or double)
     *
     * @param array $array Array to search in
     * @param string $key Key to return its value
     * @param string $subKey If result of key access is an array, subkey can be used to fetch an element from this again
     * @return string
     */
    public function render(array $array, $key, $subKey = '')
    {
        return static::renderStatic(
            [
                'array' => $array,
                'key' => $key,
                'subKey' => $subKey
            ],
            $this->buildRenderChildrenClosure(),
            $this->renderingContext
        );
    }

    /**
     * Return array element by key.
     *
     * @param array $arguments
     * @param \Closure $renderChildrenClosure
     * @param RenderingContextInterface $renderingContext
     * @throws \TYPO3\CMS\Beuser\Exception
     * @return string
     */
    public static function renderStatic(array $arguments, \Closure $renderChildrenClosure, RenderingContextInterface $renderingContext)
    {
        $array = $arguments['array'];
        $key = $arguments['key'];
        $subKey = $arguments['subKey'];
        $result = '';
        if (is_array($array) && isset($array[$key])) {
            $result = $array[$key];
            if (is_array($result) && $subKey && isset($result[$subKey])) {
                $result = $result[$subKey];
            }
        }
        if (!is_scalar($result)) {
            throw new \TYPO3\CMS\Beuser\Exception(
                'Only scalar return values (string, int, float or double) are supported.',
                1382284105
            );
        }
        return $result;
    }
}
