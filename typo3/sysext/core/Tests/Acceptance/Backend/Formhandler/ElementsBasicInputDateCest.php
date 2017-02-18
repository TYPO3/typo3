<?php
namespace TYPO3\CMS\Core\Tests\Acceptance\Backend\Formhandler;

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

use TYPO3\TestingFramework\Core\Acceptance\Step\Backend\Admin;

/**
 * Tests for "elements_basic" date and time related input fields of ext:styleguide
 */
class ElementsBasicInputDateCest extends AbstractElementsBasicCest
{
    /**
     * @param Admin $I
     */
    public function checkThatValidationWorks_evalYear(Admin $I)
    {
        $dataSets = [
            'input_24 eval=year' => [
                [
                    'inputValue' => '2016',
                    'expectedValue' => '2016',
                    'expectedInternalValue' => '2016',
                    'expectedValueAfterSave' => '2016',
                    'comment' => '',
                ],
                [
                    'inputValue' => '12',
                    'expectedValue' => '2012',
                    'expectedInternalValue' => '2012',
                    'expectedValueAfterSave' => '2012',
                    'comment' => '',
                ],
                [
                    'inputValue' => 'Kasper',
                    'expectedValue' => date('Y'),
                    'expectedInternalValue' => date('Y'),
                    'expectedValueAfterSave' => date('Y'),
                    'comment' => 'Invalid character is converted to current year',
                ],
            ]
        ];
        $this->runTests($I, $dataSets);
    }

    /**
     * @param Admin $I
     */
    public function checkThatValidationWorks_EvalDate_TypeDate(Admin $I)
    {
        $dataSets = [
            'input_36 dbType=date eval=date' => [
                [
                    'inputValue' => '29-01-2016',
                    'expectedValue' => '29-01-2016',
                    'expectedInternalValue' => '2016-01-29T00:00:00Z',
                    'expectedValueAfterSave' => '2016-01-29T00:00:00+00:00',
                    'comment' => '',
                ],
                [
                    'inputValue' => '13-13-2016',
                    'expectedValue' => '13-01-2017',
                    'expectedInternalValue' => '2017-01-13T00:00:00Z',
                    'expectedValueAfterSave' => '2017-01-13T00:00:00+00:00',
                    'comment' => '',
                ],
                [
                    'inputValue' => '29-02-2016',
                    'expectedValue' => '29-02-2016',
                    'expectedInternalValue' => '2016-02-29T00:00:00Z',
                    'expectedValueAfterSave' => '2016-02-29T00:00:00+00:00',
                    'comment' => 'Check valid leap year input',
                ],
                [
                    'inputValue' => '29-02-2015',
                    'expectedValue' => '01-03-2015',
                    'expectedInternalValue' => '2015-03-01T00:00:00Z',
                    'expectedValueAfterSave' => '2015-03-01T00:00:00+00:00',
                    'comment' => 'Check invalid leap year transformation',
                ],
            ]
        ];
        $this->runTests($I, $dataSets);
    }

    /**
     * @param Admin $I
     */
    public function checkThatValidationWorks_EvalDateTime(Admin $I)
    {
        $dataSets = [
            'input_7 eval=datetime' => [
                [
                    'inputValue' => '05:23 29-01-2016',
                    'expectedValue' => '05:23 29-01-2016',
                    'expectedInternalValue' => '2016-01-29T05:23:00Z',
                    'expectedValueAfterSave' => '2016-01-29T05:23:00+00:00',
                    'comment' => '',
                ],
                [
                    'inputValue' => '05:23 13-13-2016',
                    'expectedValue' => '05:23 13-01-2017',
                    'expectedInternalValue' => '2017-01-13T05:23:00Z',
                    'expectedValueAfterSave' => '2017-01-13T05:23:00+00:00',
                    'comment' => '',
                ],
                [
                    'inputValue' => '05:23 29-02-2016',
                    'expectedValue' => '05:23 29-02-2016',
                    'expectedInternalValue' => '2016-02-29T05:23:00Z',
                    'expectedValueAfterSave' => '2016-02-29T05:23:00+00:00',
                    'comment' => 'Check valid leap year input',
                ],
                [
                    'inputValue' => '05:23 29-02-2015',
                    'expectedValue' => '05:23 01-03-2015',
                    'expectedInternalValue' => '2015-03-01T05:23:00Z',
                    'expectedValueAfterSave' => '2015-03-01T05:23:00+00:00',
                    'comment' => 'Check invalid leap year transformation',
                ],
            ]
        ];
        $this->runTests($I, $dataSets);
    }

    /**
     * @param Admin $I
     */
    public function checkThatValidationWorks_evalTime(Admin $I)
    {
        $dataSets = [
            'input_17 eval=time' => [
                [
                    'inputValue' => '13:30',
                    'expectedValue' => '13:30',
                    'expectedInternalValue' => '13:30',
                    'expectedValueAfterSave' => (new \DateTime('13:30'))->getTimestamp(),
                    'comment' => '',
                ],
                [
                    'inputValue' => '123',
                    'expectedValue' => '12:03',
                    'expectedInternalValue' => '12:03',
                    'expectedValueAfterSave' => (new \DateTime('12:03'))->getTimestamp(),
                    'comment' => '',
                ],
                [
                    'inputValue' => '12345',
                    'expectedValue' => '12:34',
                    'expectedInternalValue' => '12:34',
                    'expectedValueAfterSave' => (new \DateTime('12:34'))->getTimestamp(),
                    'comment' => '',
                ],
                [
                    'inputValue' => '12:04+5',
                    'expectedValue' => '12:09',
                    'expectedInternalValue' => '12:09',
                    'expectedValueAfterSave' => (new \DateTime('12:09'))->getTimestamp(),
                    'comment' => '',
                ],
                [
                    'inputValue' => '12:09-3',
                    'expectedValue' => '12:06',
                    'expectedInternalValue' => '12:06',
                    'expectedValueAfterSave' => (new \DateTime('12:06'))->getTimestamp(),
                    'comment' => '',
                ],
            ],
        ];
        $this->runTests($I, $dataSets);
    }

    /**
     * @param Admin $I
     */
    /**
    public function checkThatValidationWorks_EvalDateTime_DbTypeDateTime(Admin $I)
    {
        // @todo fix these unstable test
        $dataSets = [
            'input_37 dbType=datetime eval=datetime' => [
                [
                    'inputValue' => '05:23 29-01-2016',
                    'expectedValue' => '05:23 29-01-2016',
                    'expectedInternalValue' => '2016-01-29T05:23:00Z',
                    'expectedValueAfterSave' => '2016-01-29T05:23:00+00:00',
                    'comment' => '',
                ],
                [
                    'inputValue' => '05:23 13-13-2016',
                    'expectedValue' => '05:23 13-01-2017',
                    'expectedInternalValue' => '2017-01-13T05:23:00Z',
                    'expectedValueAfterSave' => '2017-01-13T05:23:00+00:00',
                    'comment' => '',
                ],
                [
                    'inputValue' => '05:23 29-02-2016',
                    'expectedValue' => '05:23 29-02-2016',
                    'expectedInternalValue' => '2016-02-29T05:23:00Z',
                    'expectedValueAfterSave' => '2016-02-29T05:23:00+00:00',
                    'comment' => 'Check valid leap year input',
                ],
                [
                    'inputValue' => '05:23 29-02-2015',
                    'expectedValue' => '05:23 01-03-2015',
                    'expectedInternalValue' => '2015-03-01T05:23:00Z',
                    'expectedValueAfterSave' => '2015-03-01T05:23:00+00:00',
                    'comment' => 'Check invalid leap year transformation',
                ],
            ],
            'input_18 eval=timesec' => [
                [
                    'inputValue' => '13:30:00',
                    'expectedValue' => '13:30:00',
                    'expectedInternalValue' => '13:30:00',
                    'expectedValueAfterSave' => (new \DateTime('13:30:00'))->getTimestamp(),
                    'comment' => '',
                ],
                [
                    'inputValue' => '12345',
                    'expectedValue' => '12:34:05',
                    'expectedInternalValue' => '12:34:05',
                    'expectedValueAfterSave' => (new \DateTime('12:34:05'))->getTimestamp(),
                    'comment' => '',
                ],
                [
                    'inputValue' => '12:04:04+5',
                    'expectedValue' => '12:09:04',
                    'expectedInternalValue' => '12:09:04',
                    'expectedValueAfterSave' => (new \DateTime('12:09:04'))->getTimestamp(),
                    'comment' => '',
                ],
            ],
            'input_6 eval=date' => [
                [
                    'inputValue' => '29-01-2016',
                    'expectedValue' => '29-01-2016',
                    'expectedInternalValue' => '2016-01-29T00:00:00Z',
                    'expectedValueAfterSave' => '2016-01-29T00:00:00+00:00',
                    'comment' => '',
                ],
                [
                    'inputValue' => '13-13-2016',
                    'expectedValue' => '13-01-2017',
                    'expectedInternalValue' => '2017-01-13T00:00:00Z',
                    'expectedValueAfterSave' => '2017-01-13T00:00:00+00:00',
                    'comment' => '',
                ],
                [
                    'inputValue' => '29-02-2016',
                    'expectedValue' => '29-02-2016',
                    'expectedInternalValue' => '2016-02-29T00:00:00Z',
                    'expectedValueAfterSave' => '2016-02-29T00:00:00+00:00',
                    'comment' => 'Check valid leap year input',
                ],
                [
                    'inputValue' => '29-02-2015',
                    'expectedValue' => '01-03-2015',
                    'expectedInternalValue' => '2015-03-01T00:00:00Z',
                    'expectedValueAfterSave' => '2015-03-01T00:00:00+00:00',
                    'comment' => 'Check invalid leap year transformation',
                ],
            ],
        ];
        $this->runTests($I, $dataSets);
    }
     */
}
