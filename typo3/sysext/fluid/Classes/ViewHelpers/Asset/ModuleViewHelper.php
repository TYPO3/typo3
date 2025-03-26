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

namespace TYPO3\CMS\Fluid\ViewHelpers\Asset;

use TYPO3\CMS\Core\Page\AssetCollector;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

/**
 * ViewHelper to add JavaScript modules to the TYPO3 AssetCollector.
 *
 * Examples
 * ========
 *
 * ::
 *
 *    <f:asset.module identifier="@my/package/filename.js"/>
 *
 * Details
 * =======
 *
 * In the AssetCollector, the "identifier" attribute is used as a unique identifier. Thus, if modules are added multiple
 * times using the same module identifier, the asset will only be served once.
 */
final class ModuleViewHelper extends AbstractViewHelper
{
    protected AssetCollector $assetCollector;

    public function injectAssetCollector(AssetCollector $assetCollector): void
    {
        $this->assetCollector = $assetCollector;
    }

    public function initializeArguments(): void
    {
        parent::initializeArguments();
        $this->registerArgument('identifier', 'string', 'Bare module identifier like "@my/package/filename.js".', true);
    }

    public function render(): string
    {
        $identifier = (string)$this->arguments['identifier'];
        $this->assetCollector->addJavaScriptModule($identifier);
        return '';
    }
}
