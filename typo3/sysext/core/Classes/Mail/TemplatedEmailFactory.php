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

namespace TYPO3\CMS\Core\Mail;

use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Fluid\View\TemplatePaths;

/**
 * Factory for creating FluidEmail instances.
 *
 * Provides three creation methods:
 * - create(): For backend/CLI usage with global configuration only
 * - createFromRequest(): For frontend usage with site-aware template paths
 * - createWithOverrides(): For extensions needing custom template path overrides
 */
#[Autoconfigure(public: true)]
readonly class TemplatedEmailFactory
{
    /**
     * Create a FluidEmail instance with template paths resolved from site settings.
     *
     * Use this method for frontend contexts (e.g., form submissions, felogin)
     * where site-specific email templates should be applied.
     *
     * The factory extracts the site from the request attribute and merges
     * site-specific email settings with global mail configuration.
     *
     * Site settings used:
     * - email.templateRootPaths: array of template root paths
     * - email.layoutRootPaths: array of layout root paths
     * - email.partialRootPaths: array of partial root paths
     * - email.format: email format (html, plain, both)
     */
    public function createFromRequest(ServerRequestInterface $request): FluidEmail
    {
        return $this->createWithOverrides(request: $request);
    }

    /**
     * Create a FluidEmail instance using global configuration only.
     *
     * Use this method for backend/CLI contexts (e.g., login notifications,
     * scheduler tasks, install tool) where no site context is available
     * or site-specific templates are not desired.
     *
     * Template paths are read from $GLOBALS['TYPO3_CONF_VARS']['MAIL'].
     */
    public function create(?ServerRequestInterface $request = null): FluidEmail
    {
        $templatePaths = $this->buildTemplatePathsFromGlobals();

        $fluidEmail = new FluidEmail($templatePaths);

        if ($request !== null) {
            $fluidEmail->setRequest($request);
        }

        return $fluidEmail;
    }

    /**
     * Create a FluidEmail instance with custom template path overrides.
     *
     * Use this method when extensions need to provide their own template paths
     * that are merged on top of the base configuration. The base configuration
     * is built from global $GLOBALS['TYPO3_CONF_VARS']['MAIL'] with site settings
     * merged on top when a request with a site attribute is provided.
     *
     * The override paths are then merged using array_replace(), so higher numeric
     * keys in overrides will take precedence, while existing numeric keys will
     * be overwritten.
     *
     * @param string[] $templateRootPaths Additional template root paths to merge
     * @param string[] $layoutRootPaths Additional layout root paths to merge
     * @param string[] $partialRootPaths Additional partial root paths to merge
     * @param ServerRequestInterface|null $request Optional request for site resolution and ViewHelper context
     */
    public function createWithOverrides(
        array $templateRootPaths = [],
        array $layoutRootPaths = [],
        array $partialRootPaths = [],
        ?ServerRequestInterface $request = null,
    ): FluidEmail {
        $site = $request?->getAttribute('site');
        $templatePaths = $this->buildTemplatePathsWithSiteOverrides($site);

        if ($templateRootPaths !== []) {
            $templatePaths->setTemplateRootPaths(
                array_replace($templatePaths->getTemplateRootPaths(), $templateRootPaths)
            );
        }
        if ($layoutRootPaths !== []) {
            $templatePaths->setLayoutRootPaths(
                array_replace($templatePaths->getLayoutRootPaths(), $layoutRootPaths)
            );
        }
        if ($partialRootPaths !== []) {
            $templatePaths->setPartialRootPaths(
                array_replace($templatePaths->getPartialRootPaths(), $partialRootPaths)
            );
        }

        $fluidEmail = new FluidEmail($templatePaths);

        if ($request !== null) {
            $fluidEmail->setRequest($request);
        }

        if ($site instanceof Site) {
            $format = $site->getSettings()->get('email.format', '');
            if ($format !== '' && is_string($format)) {
                $fluidEmail->format($format);
            }
        }

        return $fluidEmail;
    }

    /**
     * Build template paths from global config with site settings merged on top.
     *
     * Site settings take precedence and are merged using array_replace()
     * to allow overriding specific numeric keys.
     */
    private function buildTemplatePathsWithSiteOverrides(?object $site): TemplatePaths
    {
        $templatePaths = $this->buildTemplatePathsFromGlobals();

        if ($site instanceof Site && !$site->getSettings()->isEmpty()) {
            $settings = $site->getSettings();

            $siteTemplateRootPaths = $settings->get('email.templateRootPaths', []);
            if (is_array($siteTemplateRootPaths) && $siteTemplateRootPaths !== []) {
                $templatePaths->setTemplateRootPaths(
                    $this->mergeYamlSiteSettingsArrayWithCurrent($templatePaths->getTemplateRootPaths(), $siteTemplateRootPaths)
                );
            }

            $siteLayoutRootPaths = $settings->get('email.layoutRootPaths', []);
            if (is_array($siteLayoutRootPaths) && $siteLayoutRootPaths !== []) {
                $templatePaths->setLayoutRootPaths(
                    $this->mergeYamlSiteSettingsArrayWithCurrent($templatePaths->getLayoutRootPaths(), $siteLayoutRootPaths)
                );
            }

            $sitePartialRootPaths = $settings->get('email.partialRootPaths', []);
            if (is_array($sitePartialRootPaths) && $sitePartialRootPaths !== []) {
                $templatePaths->setPartialRootPaths(
                    $this->mergeYamlSiteSettingsArrayWithCurrent($templatePaths->getPartialRootPaths(), $sitePartialRootPaths)
                );
            }
        }

        return $templatePaths;
    }

    /**
     * When using the Site Settings GUI, the entered "stringlist" arrays have running numerical
     * indexes:
     *
     * email.partialRootPaths:
     * - 'EXT:my_extension/Resources/Private/Partials/Email'
     * - 'EXT:my_extension/Resources/Private/Partials/Email2'
     *
     * This would resolve to an array with the keys "0" and "1". This would override the
     * global template paths that already use "0" as the EXT:core base template paths.
     *
     * For a manually maintained settings.yaml, integrators however might use named indexes.
     * This method here allows to deal with both:
     *
     * - if the input is a sequential list (PHP `is_array_list`), all array keys are APPENDED to the array
     * - if the input has specific array keys (100, 200, ...) the array keys are REPLACED
     */
    private function mergeYamlSiteSettingsArrayWithCurrent(array $currentArray, array $yamlArray): array
    {
        if (array_is_list($yamlArray)) {
            // array_merge() would replace numerical array keys, which we do not want.
            // Data must be stacked on top of the existing structure, with higher priority than globals.
            $nextKey = max(array_keys($currentArray)) + 1;
            $outputArray = $currentArray;
            foreach ($yamlArray as $value) {
                $outputArray[$nextKey++] = $value;
            }
            return $outputArray;
        }
        return array_replace($currentArray, $yamlArray);
    }

    /**
     * Build template paths from global mail configuration.
     */
    private function buildTemplatePathsFromGlobals(): TemplatePaths
    {
        $globalConfig = $GLOBALS['TYPO3_CONF_VARS']['MAIL'] ?? [];

        $templatePaths = new TemplatePaths();
        $templatePaths->setTemplateRootPaths($globalConfig['templateRootPaths'] ?? []);
        $templatePaths->setLayoutRootPaths($globalConfig['layoutRootPaths'] ?? []);
        $templatePaths->setPartialRootPaths($globalConfig['partialRootPaths'] ?? []);

        return $templatePaths;
    }
}
