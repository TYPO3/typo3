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

use TYPO3\CMS\Fluid\Core\Rendering\RenderingContextFactory;
use TYPO3Fluid\Fluid\Validation\TemplateValidator;
use TYPO3Fluid\Fluid\Validation\TemplateValidatorResult;

/**
 * @internal
 */
readonly class CacheWarmupService
{
    public function __construct(
        private TemplateFinder $templateFinder,
        private RenderingContextFactory $renderingContextFactory,
    ) {}

    /**
     * @return TemplateValidatorResult[]
     */
    public function warmupTemplatesInAllPackages(): array
    {
        $templates = $this->templateFinder->findTemplatesInAllPackages();
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
}
