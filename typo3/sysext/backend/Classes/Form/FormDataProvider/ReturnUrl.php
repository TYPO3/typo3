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

namespace TYPO3\CMS\Backend\Form\FormDataProvider;

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Form\FormDataProviderInterface;
use TYPO3\CMS\Backend\Routing\UriBuilder;

/**
 * Resolve return Url if not set from outside. This is used
 * by various field elements when a "sub" FormEngine view
 * is triggered. An example is the "Add" button on type="group"
 * elements.
 *
 * @todo: We may want to get rid of this eventually: The returnUrl
 *        should typically be set by calling controllers as initial
 *        data, since only controllers know details about current
 *        context. The fallback below is a bit of guesswork.
 */
class ReturnUrl implements FormDataProviderInterface
{
    public function __construct(private readonly UriBuilder $uriBuilder)
    {
    }

    public function addData(array $result): array
    {
        if ($result['returnUrl'] !== null) {
            return $result;
        }
        /** @var ServerRequestInterface $request */
        $request = $result['request'];
        $routeIdentifier = $request->getAttribute('route')?->getOption('_identifier');
        if ($routeIdentifier === null) {
            // Route could not be found. This usually should not happen in any
            // backend context, but is sanitized here nevertheless. returnUrl
            // will be kept as null in this case, which may or may not trigger
            // subsequent issues.
            return $result;
        }
        $queryParams = $request->getQueryParams();
        $relevantQueryParams = [];
        foreach ($queryParams as $queryKey => $queryValue) {
            if (in_array($queryKey, ['token', 'returnUrl'], true)) {
                continue;
            }
            $relevantQueryParams[$queryKey] = $queryValue;
        }
        $result['returnUrl'] = (string)$this->uriBuilder->buildUriFromRoute($routeIdentifier, $relevantQueryParams);
        return $result;
    }
}
