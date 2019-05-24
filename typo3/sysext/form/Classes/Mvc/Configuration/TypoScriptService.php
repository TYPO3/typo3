<?php
declare(strict_types = 1);
namespace TYPO3\CMS\Form\Mvc\Configuration;

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

use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;

/**
 * Utilities to manage and convert TypoScript
 *
 * Scope: frontend
 */
class TypoScriptService
{

    /**
     * @var \TYPO3\CMS\Core\TypoScript\TypoScriptService
     */
    protected $extbaseTypoScriptService;

    /**
     * @param \TYPO3\CMS\Core\TypoScript\TypoScriptService $typoScriptService
     * @internal
     */
    public function injectTypoScriptService(\TYPO3\CMS\Core\TypoScript\TypoScriptService $typoScriptService)
    {
        $this->extbaseTypoScriptService = $typoScriptService;
    }

    /**
     * Parse an configuration with ContentObjectRenderer::cObjGetSingle()
     * and return the result.
     *
     * @param array $configuration
     * @return array
     * @internal
     */
    public function resolvePossibleTypoScriptConfiguration(array $configuration = []): array
    {
        $configuration = $this->extbaseTypoScriptService->convertPlainArrayToTypoScriptArray($configuration);
        $configuration = $this->resolveTypoScriptConfiguration($configuration);
        $configuration = $this->extbaseTypoScriptService->convertTypoScriptArrayToPlainArray($configuration);
        return $configuration;
    }

    /**
     * Parse an configuration with ContentObjectRenderer::cObjGetSingle()
     * if there is an array key without and with a dot at the end.
     * This sample would be identified as a TypoScript parsable configuration
     * part:
     *
     * [
     *   'example' => 'TEXT'
     *   'example.' => [
     *     'value' => 'some value'
     *   ]
     * ]
     *
     * @param array $configuration
     * @return array
     */
    protected function resolveTypoScriptConfiguration(array $configuration = []): array
    {
        foreach ($configuration as $key => $value) {
            $keyWithoutDot = rtrim((string)$key, '.');
            if (isset($configuration[$keyWithoutDot]) && isset($configuration[$keyWithoutDot . '.'])) {
                $value = $this->getTypoScriptFrontendController()->cObj->cObjGetSingle(
                    $configuration[$keyWithoutDot],
                    $configuration[$keyWithoutDot . '.'],
                    $keyWithoutDot
                );
                $configuration[$keyWithoutDot] = $value;
            } elseif (!isset($configuration[$keyWithoutDot]) && isset($configuration[$keyWithoutDot . '.'])) {
                $configuration[$keyWithoutDot] = $this->resolveTypoScriptConfiguration($value);
            }
            unset($configuration[$keyWithoutDot . '.']);
        }
        return $configuration;
    }

    /**
     * @return TypoScriptFrontendController
     */
    protected function getTypoScriptFrontendController()
    {
        return $GLOBALS['TSFE'];
    }
}
