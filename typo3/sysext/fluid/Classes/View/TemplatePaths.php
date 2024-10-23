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

namespace TYPO3\CMS\Fluid\View;

use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\PathUtility;

/**
 * Custom implementation for template paths resolving, one which differs from the base
 * implementation in that it is capable of resolving template paths based on TypoScript
 * configuration when given a package name, and is aware of the Frontend/Backend contexts of TYPO3.
 *
 * @internal This is for internal Fluid use only.
 */
class TemplatePaths extends \TYPO3Fluid\Fluid\View\TemplatePaths
{
    /**
     * Overridden setter with enforced sorting behavior
     */
    public function setTemplateRootPaths(array $templateRootPaths): void
    {
        parent::setTemplateRootPaths(ArrayUtility::sortArrayWithIntegerKeys($templateRootPaths));
    }

    /**
     * Overridden setter with enforced sorting behavior
     */
    public function setLayoutRootPaths(array $layoutRootPaths): void
    {
        parent::setLayoutRootPaths(ArrayUtility::sortArrayWithIntegerKeys($layoutRootPaths));
    }

    /**
     * Overridden setter with enforced sorting behavior
     */
    public function setPartialRootPaths(array $partialRootPaths): void
    {
        parent::setPartialRootPaths(ArrayUtility::sortArrayWithIntegerKeys($partialRootPaths));
    }

    /**
     * Get absolute path to template file
     *
     * @return string|null Returns the absolute path to a Fluid template file
     */
    public function getTemplatePathAndFilename(): ?string
    {
        return $this->templatePathAndFilename;
    }

    /**
     * Guarantees that $reference is turned into a
     * correct, absolute path. The input can be a
     * relative path or a FILE: or EXT: reference
     * but cannot be a FAL resource identifier.
     *
     * @param string $reference
     */
    protected function ensureAbsolutePath($reference): string
    {
        return PathUtility::isAbsolutePath($reference) ? $reference : GeneralUtility::getFileAbsFileName($reference);
    }
}
