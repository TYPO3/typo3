<?php
declare(strict_types = 1);
namespace TYPO3\CMS\Form\Domain\Configuration\FrameworkConfiguration\Extractors\PropertyCollectionElement;

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

use TYPO3\CMS\Form\Domain\Configuration\FrameworkConfiguration\Extractors\AbstractExtractor;

/**
 * @internal
 */
class PredefinedDefaultsExtractor extends AbstractExtractor
{

    /**
     * @param string $_
     * @param mixed $value
     * @param array $matches
     */
    public function __invoke(string $_, $value, array $matches)
    {
        [, $propertyCollectionName, $propertyCollectionElementIdentifier, $propertyPath] = $matches;
        $propertyCollectionName = str_replace('Definition', '', $propertyCollectionName);

        $result = $this->extractorDto->getResult();
        $result['collections'][$propertyCollectionName][$propertyCollectionElementIdentifier]['predefinedDefaults'][$propertyPath] = $value;
        $this->extractorDto->setResult($result);
    }
}
