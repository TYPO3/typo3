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

namespace TYPO3\CMS\Fluid\Service;

use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;
use TYPO3\CMS\Core\Package\PackageInterface;
use TYPO3\CMS\Core\Package\PackageManager;
use TYPO3\CMS\Fluid\Core\Rendering\RenderingContextFactory;
use TYPO3Fluid\Fluid\Validation\TemplateValidator;
use TYPO3Fluid\Fluid\Validation\TemplateValidatorResult;

/**
 * @internal
 */
readonly class CacheWarmupService
{
    public function __construct(
        private PackageManager $packageManager,
        private RenderingContextFactory $renderingContextFactory,
    ) {}

    /**
     * @return TemplateValidatorResult[]
     */
    public function warmupTemplatesInAllPackages(): array
    {
        $templates = $this->findTemplatesInAllPackages();
        $validationResults = (new TemplateValidator())->validateTemplateFiles(
            $templates,
            $this->renderingContextFactory->create()
        );
        foreach ($validationResults as &$result) {
            if ($result->canBeCompiled()) {
                try {
                    $this->renderingContextFactory->create()->getTemplateCompiler()->store(
                        $result->identifier,
                        $result->parsedTemplate,
                    );
                } catch (\Exception $e) {
                    $result = $result->withErrors([...$result->errors, $e]);
                }
            }
        }
        return $validationResults;
    }

    private function findTemplatesInAllPackages(): array
    {
        $packagePaths = array_map(
            fn(PackageInterface $package): string => $package->getPackagePath(),
            $this->packageManager->getActivePackages(),
        );
        $finder = new Finder();
        $templates = $finder
            ->files()
            ->in($packagePaths)
            ->exclude([
                'Classes',
                'Tests',
                'node_modules',
                'vendor',
            ])
            ->name('*.fluid.*');
        return array_map(
            fn(SplFileInfo $file): string => $file->getPathname(),
            iterator_to_array($templates),
        );
    }
}
