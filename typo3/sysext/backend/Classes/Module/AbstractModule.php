<?php
namespace TYPO3\CMS\Backend\Module;

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

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Template\ModuleTemplate;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * A backend module. This class may be used by extension backend modules
 * to implement own actions and controllers. It initializes the module
 * template and comes with a simple dispatcher method.
 *
 * @internal Experimental for now
 */
class AbstractModule
{
    /**
     * ModuleTemplate object
     *
     * @var ModuleTemplate
     */
    protected $moduleTemplate;

    /**
     * Constructor Method
     */
    public function __construct()
    {
        $this->moduleTemplate = GeneralUtility::makeInstance(ModuleTemplate::class);
    }

    /**
     * PSR Request Object
     *
     * @var ServerRequestInterface
     */
    protected $request;

    /**
     * Central Request Dispatcher
     *
     * @param ServerRequestInterface $request PSR7 Request Object
     * @param ResponseInterface $response PSR7 Response Object
     *
     * @return ResponseInterface
     *
     * @throws \InvalidArgumentException In case an action is not callable
     */
    public function processRequest(ServerRequestInterface $request, ResponseInterface $response)
    {
        $methodName = $request->getQueryParams()['action'] ?: 'index';
        if (!is_callable([$this, $methodName])) {
            throw new \InvalidArgumentException(
                'The method "' . $methodName . '" is not callable within "' . get_class($this) . '".',
                1442736343
            );
        }
        return $this->{$methodName}($request, $response);
    }
}
