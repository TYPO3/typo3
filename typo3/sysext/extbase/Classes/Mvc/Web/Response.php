<?php

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

namespace TYPO3\CMS\Extbase\Mvc\Web;

use TYPO3\CMS\Core\Compatibility\PublicPropertyDeprecationTrait;
use TYPO3\CMS\Extbase\Service\EnvironmentService;

/**
 * A web specific response implementation
 * @deprecated since TYPO3 10.2, will be removed in version 11.0.
 */
class Response extends \TYPO3\CMS\Extbase\Mvc\Response
{
    use PublicPropertyDeprecationTrait;

    /**
     * @var array
     */
    private $deprecatedPublicProperties = [
        'environmentService' => 'Property \TYPO3\CMS\Extbase\Mvc\Web\Response::$environmentService is deprecated since TYPO3 10.2 and will be removed in TYPO3 11.0'
    ];

    /**
     * @var \TYPO3\CMS\Extbase\Service\EnvironmentService
     * @deprecated
     */
    private $environmentService;

    /**
     * @param \TYPO3\CMS\Extbase\Service\EnvironmentService $environmentService
     * @internal only to be used within Extbase, not part of TYPO3 Core API.
     * @deprecated since TYPO3 10.2, will be removed in 11.0
     */
    public function injectEnvironmentService(EnvironmentService $environmentService)
    {
        $this->environmentService = $environmentService;
    }

    /**
     * Sends additional headers and returns the content
     *
     * @return string|null
     * @internal only to be used within Extbase, not part of TYPO3 Core API.
     */
    public function shutdown()
    {
        if (!empty($this->getAdditionalHeaderData())) {
            $this->getTypoScriptFrontendController()->additionalHeaderData[] = implode(LF, $this->getAdditionalHeaderData());
        }
        $this->sendHeaders();
        return parent::shutdown();
    }
}
