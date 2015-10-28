<?php
namespace FoT3\Jumpurl\Tests\Unit;

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

use TYPO3\CMS\Core\Resource\Exception\FileDoesNotExistException;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Utility\HttpUtility;
use TYPO3\CMS\Frontend\Authentication\FrontendUserAuthentication;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;
use FoT3\Jumpurl\JumpUrlHandler;

/**
 * Testcase for handling jump URLs when given with a test parameter
 */
class JumpUrlHandlerTest extends \TYPO3\CMS\Core\Tests\UnitTestCase
{
    /**
     * The default location data used for JumpUrl secure.
     *
     * @var string
     */
    protected $defaultLocationData = '1234:tt_content:999';

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|JumpUrlHandler
     */
    protected $jumpUrlHandler;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface|\TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController
     */
    protected $tsfe;

    /**
     * Sets environment variables and initializes global mock object.
     */
    protected function setUp()
    {
        parent::setUp();

        $GLOBALS['TYPO3_CONF_VARS']['SYS']['encryptionKey'] = '12345';

        $this->jumpUrlHandler = $this->getMock(
            JumpUrlHandler::class,
            array('isLocationDataValid', 'getResourceFactory', 'getTypoScriptFrontendController', 'readFileAndExit', 'redirect')
        );

        $this->tsfe = $this->getAccessibleMock(
            TypoScriptFrontendController::class,
            array('getPagesTSconfig'),
            array(),
            '',
            false
        );
        $this->jumpUrlHandler->expects($this->any())
            ->method('getTypoScriptFrontendController')
            ->will($this->returnValue($this->tsfe));
    }

    /**
     * Provides a valid jump URL hash and a target URL
     *
     * @return array
     */
    public function jumpUrlDefaultValidParametersDataProvider()
    {
        return array(
            'File with spaces and ampersands' => array(
                '691dbf63a21181e2d69bf78e61f1c9fd023aef2c',
                str_replace('%2F', '/', rawurlencode('typo3temp/phpunitJumpUrlTestFile with spaces & amps.txt')),
            ),
            'External URL' => array(
                '7d2261b12682a4b73402ae67415e09f294b29a55',
                'http://www.mytesturl.tld',
            ),
            'External URL with GET parameters' => array(
                'cfc95f583da7689238e98bbc8930ebd820f0d20f',
                'http://external.domain.tld?parameter1=' . rawurlencode('parameter[data]with&a lot-of-special/chars'),
            ),
            'External URL without www' => array(
                '8591c573601d17f37e06aff4ac14c78f107dd49e',
                'http://external.domain.tld',
            ),
            'Mailto link' => array(
                'bd82328dc40755f5d0411e2e16e7c0cbf33b51b7',
                'mailto:mail@ddress.tld',
            )
        );
    }

    /**
     * @test
     * @dataProvider jumpUrlDefaultValidParametersDataProvider
     * @param string $hash
     * @param string $jumpUrl
     */
    public function jumpUrlDefaultAcceptsValidUrls($hash, $jumpUrl)
    {
        $_GET['juHash'] = $hash;
        $_GET['jumpurl'] = $jumpUrl;

        $this->jumpUrlHandler->expects($this->once())
            ->method('redirect')
            ->with($jumpUrl, HttpUtility::HTTP_STATUS_303);

        $this->jumpUrlHandler->canHandleCurrentUrl();
        $this->jumpUrlHandler->handle();
    }

    /**
     * @test
     * @dataProvider jumpUrlDefaultValidParametersDataProvider
     * @expectedException \Exception
     * @expectedExceptionCode 1359987599
     * @param string $hash
     * @param string $jumpUrl
     */
    public function jumpUrlDefaultFailsOnInvalidHash($hash, $jumpUrl)
    {
        $_GET['jumpurl'] = $jumpUrl;
        $_GET['juHash'] = $hash . '1';

        $this->jumpUrlHandler->canHandleCurrentUrl();
        $this->jumpUrlHandler->handle();
    }

    /**
     * @test
     * @dataProvider jumpUrlDefaultValidParametersDataProvider
     * @param string $hash
     * @param string $jumpUrl
     */
    public function jumpUrlDefaultTransfersSession($hash, $jumpUrl)
    {
        $tsConfig['TSFE.']['jumpUrl_transferSession'] = 1;

        /** @var \PHPUnit_Framework_MockObject_MockObject|FrontendUserAuthentication $frontendUserMock */
        $frontendUserMock = $this->getMock(FrontendUserAuthentication::class);
        $frontendUserMock->id = 123;

        $this->tsfe->_set('fe_user', $frontendUserMock);
        $this->tsfe->expects($this->once())
            ->method('getPagesTSconfig')
            ->will($this->returnValue($tsConfig));

        $sessionGetParameter = (strpos($jumpUrl, '?') === false ? '?' : '') . '&FE_SESSION_KEY=123-fc9f825a9af59169895f3bb28267a42f';
        $expectedJumpUrl = $jumpUrl . $sessionGetParameter;

        $this->jumpUrlHandler->expects($this->once())
            ->method('redirect')
            ->with($expectedJumpUrl, HttpUtility::HTTP_STATUS_303);

        $_GET['jumpurl'] = $jumpUrl;
        $_GET['juHash'] = $hash;

        $this->jumpUrlHandler->canHandleCurrentUrl();
        $this->jumpUrlHandler->handle();
    }

    /**
     * Provides a valid jump secure URL hash, a file path and related
     * record data
     *
     * @return array
     */
    public function jumpUrlSecureValidParametersDataProvider()
    {
        return array(
            array(
                '1933f3c181db8940acfcd4d16c74643947179948',
                'typo3temp/phpunitJumpUrlTestFile.txt',
            ),
            array(
                '304b8c8e022e92e6f4d34e97395da77705830818',
                str_replace('%2F', '/', rawurlencode('typo3temp/phpunitJumpUrlTestFile with spaces & amps.txt')),
            ),
            array(
                '304b8c8e022e92e6f4d34e97395da77705830818',
                str_replace('%2F', '/', rawurlencode('typo3temp/phpunitJumpUrlTestFile with spaces & amps.txt')),
            )
        );
    }

    /**
     * @test
     * @dataProvider jumpUrlSecureValidParametersDataProvider
     * @param string $hash
     * @param string $jumpUrl
     */
    public function jumpUrlSecureAcceptsValidUrls($hash, $jumpUrl)
    {
        $_GET['jumpurl'] = $jumpUrl;
        $this->prepareJumpUrlSecureTest($hash);

        $fileMock = $this->getMock(File::class, array('dummy'), array(), '', false);
        $resourceFactoryMock = $this->getMock(ResourceFactory::class, array('retrieveFileOrFolderObject'));

        $resourceFactoryMock->expects($this->once())
            ->method('retrieveFileOrFolderObject')
            ->will($this->returnValue($fileMock));

        $this->jumpUrlHandler->expects($this->once())
            ->method('isLocationDataValid')
            ->with($this->defaultLocationData)
            ->will($this->returnValue(true));

        $this->jumpUrlHandler->expects($this->once())
            ->method('getResourceFactory')
            ->will($this->returnValue($resourceFactoryMock));

        $this->jumpUrlHandler->expects($this->once())
            ->method('readFileAndExit')
            ->with($fileMock);

        $this->jumpUrlHandler->canHandleCurrentUrl();
        $this->jumpUrlHandler->handle();
    }

    /**
     * @test
     * @dataProvider jumpUrlSecureValidParametersDataProvider
     * @expectedException \Exception
     * @expectedExceptionCode 1294585193
     * @param string $hash
     * @param string $jumpUrl
     */
    public function jumpUrlSecureFailsIfFileDoesNotExist($hash, $jumpUrl)
    {
        $_GET['jumpurl'] = $jumpUrl;
        $this->prepareJumpUrlSecureTest($hash);

        $resourceFactoryMock = $this->getMock(ResourceFactory::class, array('retrieveFileOrFolderObject'));
        $resourceFactoryMock->expects($this->once())
            ->method('retrieveFileOrFolderObject')
            ->will($this->throwException(new FileDoesNotExistException()));

        $this->jumpUrlHandler->expects($this->once())
            ->method('isLocationDataValid')
            ->with($this->defaultLocationData)
            ->will($this->returnValue(true));

        $this->jumpUrlHandler->expects($this->once())
            ->method('getResourceFactory')
            ->will($this->returnValue($resourceFactoryMock));

        $this->jumpUrlHandler->canHandleCurrentUrl();
        $this->jumpUrlHandler->handle();
    }

    /**
     * @test
     * @dataProvider jumpUrlSecureValidParametersDataProvider
     * @expectedException \Exception
     * @expectedExceptionCode 1294585195
     * @param string $hash
     * @param string $jumpUrl
     */
    public function jumpUrlSecureFailsOnDeniedAccess($hash, $jumpUrl)
    {
        $_GET['jumpurl'] = $jumpUrl;
        $this->prepareJumpUrlSecureTest($hash);

        $this->jumpUrlHandler->expects($this->once())
            ->method('isLocationDataValid')
            ->with($this->defaultLocationData)
            ->will($this->returnValue(false));

        $this->jumpUrlHandler->canHandleCurrentUrl();
        $this->jumpUrlHandler->handle();
    }

    /**
     * @test
     * @dataProvider jumpUrlSecureValidParametersDataProvider
     * @expectedException \Exception
     * @expectedExceptionCode 1294585196
     * @param string $hash
     * @param string $jumpUrl
     */
    public function jumpUrlSecureFailsOnInvalidHash($hash, $jumpUrl
    ) {
        $_GET['juSecure'] = '1';
        $_GET['juHash'] = $hash . '1';
        $_GET['locationData'] = $this->defaultLocationData;

        $this->jumpUrlHandler->canHandleCurrentUrl();
        $this->jumpUrlHandler->handle();
    }

    /**
     * @return array
     */
    public function jumpUrlSecureFailsOnForbiddenFileLocationDataProvider()
    {
        return array(
            'totally forbidden' => array(
                '/a/totally/forbidden/path'
            ),
            'typo3conf file' => array(
                PATH_site . '/typo3conf/path'
            ),
            'file with forbidden character' => array(
                PATH_site . '/mypath/test.php'
            )
        );
    }

    /**
     * @test
     * @dataProvider jumpUrlSecureFailsOnForbiddenFileLocationDataProvider
     * @expectedException \Exception
     * @expectedExceptionCode 1294585194
     * @param string $path
     * @param string $path
     */
    public function jumpUrlSecureFailsOnForbiddenFileLocation($path)
    {
        $this->jumpUrlHandler->expects($this->once())
            ->method('isLocationDataValid')
            ->with('')
            ->will($this->returnValue(true));


        $hash = \FoT3\Jumpurl\JumpUrlUtility::calculateHashSecure($path, '', '');

        $_GET['jumpurl'] = $path;
        $_GET['juSecure'] = '1';
        $_GET['juHash'] = $hash;
        $_GET['locationData'] = '';

        $this->jumpUrlHandler->canHandleCurrentUrl();
        $this->jumpUrlHandler->handle();
    }

    /**
     * @param string $hash
     * @return void
     */
    protected function prepareJumpUrlSecureTest($hash)
    {
        $_GET['juSecure'] = '1';
        $_GET['juHash'] = $hash;
        $_GET['locationData'] = $this->defaultLocationData;
    }
}
