<?php
declare(strict_types = 1);
namespace TYPO3\CMS\Backend\Notification;

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

use TYPO3\CMS\Backend\Exception\UnknownTypeException;

final class Action
{
    public const TYPE_IMMEDIATE = 'immediate';
    public const TYPE_DEFERRED = 'deferred';

    /**
     * @var string
     */
    private $type;

    /**
     * @var string
     */
    private $label;

    /**
     * @var string
     */
    private $callbackCode;

    /**
     * @var array
     */
    private $allowedTypes = [self::TYPE_IMMEDIATE, self::TYPE_DEFERRED];

    /**
     * Represent one action item for a notification.
     *
     * @param string $label The label (button text) of this action
     * @param string $callbackCode The JavaScript callback of this action
     * @param string $actionType The kind of action, possible values: TYPE_IMMEDIATE and TYPE_DEFERRED
     */
    public function __construct(string $label, string $callbackCode, string $actionType = self::TYPE_IMMEDIATE)
    {
        if (!in_array($actionType, $this->allowedTypes, true)) {
            throw new UnknownTypeException(sprintf('"%s" is not valid action type, use one of %s', $actionType, implode(', ', $this->allowedTypes)), 1567493886);
        }
        $this->label = $label;
        $this->callbackCode = $callbackCode;
        $this->type = $actionType;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function getCallbackCode(): string
    {
        return $this->callbackCode;
    }
}
