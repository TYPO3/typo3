<?php
namespace TYPO3\CMS\Form\Tests\Unit\Validator;

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
 * Test case
 */
class IpValidatorTest extends AbstractValidatorTest
{
    /**
     * @var string
     */
    protected $subjectClassName = \TYPO3\CMS\Form\Domain\Validator\IpValidator::class;

    /**
     * @return array
     */
    public function validIpv4Provider()
    {
        return [
            '127.0.0.1'   => ['127.0.0.1'],
            '10.0.0.4'    => ['10.0.0.4'],
            '192.168.0.4' => ['192.168.0.4'],
            '0.0.0.0'     => ['0.0.0.0']
        ];
    }

    /**
     * @return array
     */
    public function invalidIpv4Provider()
    {
        return [
            '127.0.0.256' => ['127.0.0.256'],
            '256.0.0.2'   => ['256.0.0.2']
        ];
    }

    /**
     * @test
     * @dataProvider validIpv4Provider
     */
    public function validateForValidInputHasEmptyErrorResult($input)
    {
        $options = ['element' => uniqid('test'), 'errorMessage' => uniqid('error')];
        $subject = $this->createSubject($options);

        $this->assertEmpty(
            $subject->validate($input)->getErrors()
        );
    }

    /**
     * @test
     * @dataProvider invalidIpv4Provider
     */
    public function validateForInvalidInputHasNotEmptyErrorResult($input)
    {
        $options = ['element' => uniqid('test'), 'errorMessage' => uniqid('error')];
        $subject = $this->createSubject($options);

        $this->assertNotEmpty(
            $subject->validate($input)->getErrors()
        );
    }
}
