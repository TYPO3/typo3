<?php
namespace TYPO3\CMS\Backend\Form\FormDataGroup;

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

use TYPO3\CMS\Backend\Form\FormDataGroupInterface;
use TYPO3\CMS\Backend\Form\FormDataProviderInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Calls provider given by setter. This group is used to call a hard coded list of providers
 * in some situations where extendability is not wanted. Use with care if at all ...
 */
class OnTheFly implements FormDataGroupInterface
{
    /**
     * @var array<FormDataProviderInterface>
     */
    protected $providerList = [];

    /**
     * Compile form data
     *
     * @param array $result Initialized result array
     * @return array Result filled with data
     * @throws \UnexpectedValueException
     */
    public function compile(array $result)
    {
        if (empty($this->providerList)) {
            throw new \UnexpectedValueException(
                'Data provider list is empty, call setProviderList first',
                1441108674
            );
        }

        foreach ($this->providerList as $providerClassName) {
            /** @var FormDataProviderInterface $provider */
            $provider = GeneralUtility::makeInstance($providerClassName);

            if (!$provider instanceof FormDataProviderInterface) {
                throw new \UnexpectedValueException(
                    'Data provider ' . $providerClassName . ' must implement FormDataProviderInterface',
                    1441108719
                );
            }

            $result = $provider->addData($result);
        }

        return $result;
    }

    /**
     * Set list of providers to be called
     *
     * @param array $list Given list of Provider class names
     */
    public function setProviderList(array $list)
    {
        $this->providerList = $list;
    }
}
