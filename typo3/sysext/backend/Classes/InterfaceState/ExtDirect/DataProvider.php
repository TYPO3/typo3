<?php
namespace TYPO3\CMS\Backend\InterfaceState\ExtDirect;

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
 * ExtDirect DataProvider for State
 */
class DataProvider
{
    /**
     * @var \TYPO3\CMS\Backend\Controller\UserSettingsController
     */
    protected $userSettingsController;

    /**
     * Constructor
     */
    public function __construct()
    {
        // All data is saved in BE_USER->uc
        $this->userSettingsController = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
            \TYPO3\CMS\Backend\Controller\UserSettingsController::class
        );
    }

    /**
     * Gets state for given key
     *
     * @param \stdClass $parameter
     * @return array
     */
    public function getState($parameter)
    {
        $key = $parameter->params->key;
        $data = $this->userSettingsController->process('get', $key);
        return [
            'success' => true,
            'data' => $data
        ];
    }

    /**
     * Save the state for a given key
     *
     * @param \stdClass $parameter
     * @return array
     */
    public function setState($parameter)
    {
        $key = $parameter->params->key;
        $data = json_decode($parameter->params->data);
        foreach ($data as $setting) {
            $this->userSettingsController->process('set', $key . '.' . $setting->name, $setting->value);
        }
        return [
            'success' => true,
            'params' => $parameter
        ];
    }
}
