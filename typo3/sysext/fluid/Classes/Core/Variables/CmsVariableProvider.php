<?php
namespace TYPO3\CMS\Fluid\Core\Variables;

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
 * Class CmsVariableProvider
 */
class CmsVariableProvider extends \TYPO3Fluid\Fluid\Core\Variables\StandardVariableProvider
{
    /**
     * Get a variable by dotted path expression, retrieving the
     * variable from nested arrays/objects one segment at a time.
     * If the second argument is provided, it must be an array of
     * accessor names which can be used to extract each value in
     * the dotted path.
     *
     * @param string $path
     * @param array $accessors
     * @return mixed
     */
    public function getByPath($path, array $accessors = [])
    {
        $path = $this->resolveSubVariableReferences($path);
        return \TYPO3\CMS\Extbase\Reflection\ObjectAccess::getPropertyPath($this->variables, $path);
    }

    /**
     * @param string $propertyPath
     * @return string
     */
    protected function resolveSubVariableReferences($propertyPath)
    {
        if (strpos($propertyPath, '{') !== false) {
            preg_match_all('/(\{.*\})/', $propertyPath, $matches);
            foreach ($matches[1] as $match) {
                $subPropertyPath = substr($match, 1, -1);
                $propertyPath = str_replace($match, $this->getByPath($subPropertyPath), $propertyPath);
            }
        }
        return $propertyPath;
    }
}
