<?php
namespace TYPO3\CMS\Version\Dependency;

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

/**
 * Object to hold information on a callback to a defined object and method.
 */
class EventCallback
{
    /**
     * @var object
     */
    protected $object;

    /**
     * @var string
     */
    protected $method;

    /**
     * @var array
     */
    protected $targetArguments;

    /**
     * Creates the objects.
     *
     * @param object $object
     * @param string $method
     * @param array $targetArguments (optional)
     */
    public function __construct($object, $method, array $targetArguments = [])
    {
        $this->object = $object;
        $this->method = $method;
        $this->targetArguments = $targetArguments;
        $this->targetArguments['target'] = $object;
    }

    /**
     * Executes the callback.
     *
     * @param array $callerArguments
     * @param object $caller
     * @param string $eventName
     * @return mixed
     */
    public function execute(array $callerArguments = [], $caller, $eventName)
    {
        return call_user_func_array([$this->object, $this->method], [$callerArguments, $this->targetArguments, $caller, $eventName]);
    }
}
