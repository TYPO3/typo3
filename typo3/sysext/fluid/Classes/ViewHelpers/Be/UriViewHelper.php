<?php
namespace TYPO3\CMS\Fluid\ViewHelpers\Be;

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

use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * A view helper for creating URIs to modules.
 * = Examples =
 * <code title="URI to the web_ts module on page 92">
 * <f:be.uri route="web_ts" parameters="{id: 92}"/>
 * </code>
 * <output>
 * /typo3/index.php?M=web_ts&moduleToken=b6e9c9f?id=92
 * </output>
 *
 *  <code title="Inline notation">
 * {f:be.uri(route: 'web_ts', parameters: '{id: 92}')}
 * </code>
 * <output>
 * /typo3/index.php?route=%2module%2web_ts%2&moduleToken=b6e9c9f?id=92
 * </output>
 */
class UriViewHelper extends AbstractBackendViewHelper
{

    /**
     * Arguments initialization
     *
     * @throws \TYPO3Fluid\Fluid\Core\ViewHelper\Exception
     */
    public function initializeArguments()
    {
        parent::initializeArguments();
        $this->registerArgument('route', 'string', 'The name of the route', true);
        $this->registerArgument('parameters', 'array', 'An array of parameters', false, []);
        $this->registerArgument(
            'referenceType',
            'string',
            'The type of reference to be generated (one of the constants)',
            false,
            UriBuilder::ABSOLUTE_PATH
        );
    }

    /**
     * @return string Rendered link
     */
    public function render()
    {
        /** @var UriBuilder $uriBuilder */
        $uriBuilder = GeneralUtility::makeInstance(UriBuilder::class);
        $route = $this->arguments['route'];
        $parameters = $this->arguments['parameters'];
        $referenceType = $this->arguments['referenceType'];
        $uri = $uriBuilder->buildUriFromRoute($route, $parameters, $referenceType);

        return (string)$uri;
    }
}
