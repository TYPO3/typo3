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

namespace {
    die('Access denied');
}

namespace TYPO3\CMS\Extbase\Annotation {
    /**
     * @deprecated since TYPO3 v14, will be removed in TYPO3 v15
     */
    class FileUpload extends \TYPO3\CMS\Extbase\Attribute\FileUpload {}
    /**
     * @deprecated since TYPO3 v14, will be removed in TYPO3 v15
     */
    class IgnoreValidation extends \TYPO3\CMS\Extbase\Attribute\IgnoreValidation {}
    /**
     * @deprecated since TYPO3 v14, will be removed in TYPO3 v15
     */
    class Validate extends \TYPO3\CMS\Extbase\Attribute\Validate {}
}

namespace TYPO3\CMS\Extbase\Annotation\ORM {
    /**
     * @deprecated since TYPO3 v14, will be removed in TYPO3 v15
     */
    class Cascade extends \TYPO3\CMS\Extbase\Attribute\ORM\Cascade {}
    /**
     * @deprecated since TYPO3 v14, will be removed in TYPO3 v15
     */
    class Lazy extends \TYPO3\CMS\Extbase\Attribute\ORM\Lazy {}
    /**
     * @deprecated since TYPO3 v14, will be removed in TYPO3 v15
     */
    class Transient extends \TYPO3\CMS\Extbase\Attribute\ORM\Transient {}
}
