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

namespace TYPO3\CMS\Extensionmanager\Enum;

/**
 * Why a folder in the extensions path is not registered as an extension.
 *
 * @internal
 */
enum UnrecognizedExtensionFolderReason: string
{
    case MissingComposerJson = 'missingComposerJson';
    case InvalidComposerJson = 'invalidComposerJson';
    case NoTypo3PackageType = 'noTypo3PackageType';
    case MissingExtensionKey = 'missingExtensionKey';

    public function getLabelKey(): string
    {
        return 'unrecognizedExtensionFolder.reason.' . $this->value;
    }
}
