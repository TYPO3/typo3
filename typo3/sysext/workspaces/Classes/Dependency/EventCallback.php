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

namespace TYPO3\CMS\Workspaces\Dependency;

/**
 * Object to hold information on a callback to a defined object and method.
 *
 * @internal
 */
class EventCallback
{
    protected object $object;
    protected string $method;
    protected array $targetArguments;

    public function __construct(object $object, string $method, array $targetArguments = [])
    {
        $this->object = $object;
        $this->method = $method;
        $this->targetArguments = $targetArguments;
        $this->targetArguments['target'] = $object;
    }

    /**
     * Executes the callback.
     */
    public function execute(array $callerArguments, object $caller, string $eventName): mixed
    {
        $callable = [$this->object, $this->method];
        if (is_callable($callable)) {
            return $callable($callerArguments, $this->targetArguments, $caller, $eventName);
        }
        return null;
    }
}
