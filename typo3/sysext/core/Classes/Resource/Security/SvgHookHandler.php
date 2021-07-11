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

namespace TYPO3\CMS\Core\Resource\Security;

class SvgHookHandler
{
    /**
     * @var SvgSanitizer
     */
    protected $sanitizer;

    /**
     * @var SvgTypeCheck
     */
    protected $typeCheck;

    public function __construct(SvgSanitizer $sanitizer, SvgTypeCheck $typeCheck)
    {
        $this->sanitizer = $sanitizer;
        $this->typeCheck = $typeCheck;
    }

    /**
     * @param array $parameters
     */
    public function processMoveUploadedFile(array $parameters)
    {
        $filePath = $parameters['source'] ?? null;
        if ($filePath !== null && $this->typeCheck->forFilePath($filePath)) {
            $this->sanitizer->sanitizeFile($filePath);
        }
    }
}
