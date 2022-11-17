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

namespace TYPO3\CMS\Adminpanel\ViewHelpers;

use TYPO3\CMS\Adminpanel\ModuleApi\ContentProviderInterface;
use TYPO3\CMS\Adminpanel\ModuleApi\ModuleData;
use TYPO3\CMS\Adminpanel\ModuleApi\ModuleDataStorageCollection;
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;
use TYPO3Fluid\Fluid\Core\ViewHelper\Traits\CompileWithRenderStatic;

/**
 * Render submodule content
 *
 * @internal
 */
final class SubModuleRenderViewHelper extends AbstractViewHelper
{
    use CompileWithRenderStatic;

    public function initializeArguments(): void
    {
        $this->registerArgument('module', ContentProviderInterface::class, 'SubModule instance to be rendered', true);
        $this->registerArgument('data', ModuleDataStorageCollection::class, 'Data to be used for rendering', true);
    }

    /**
     * Resolve user name from backend user id.
     *
     * @param array{module: ContentProviderInterface, data: ModuleDataStorageCollection} $arguments
     */
    public static function renderStatic(array $arguments, \Closure $renderChildrenClosure, RenderingContextInterface $renderingContext): string
    {
        $module = $arguments['module'];
        $data = $arguments['data'];
        $moduleData = $data->contains($module) ? $data->offsetGet($module) : new ModuleData();
        return $module->getContent($moduleData);
    }
}
