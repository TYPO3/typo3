<?php
namespace TYPO3\CMS\Form\Domain\Filter;

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
use TYPO3\CMS\Core\Charset\CharsetConverter;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Abstract class for filters.
 */
abstract class AbstractFilter
{
    protected function convertCase($value, $case)
    {
        return $this->getCharsetConverter()->conv_case(
            'utf-8',
            $value,
            $case
        );
    }

    /**
     * @return CharsetConverter
     */
    protected function getCharsetConverter()
    {
        return GeneralUtility::makeInstance(CharsetConverter::class);
    }
}
