<?php
namespace TYPO3\CMS\Fluid\Tests\Unit\Core\Widget;

/*                                                                        *
 * This script is backported from the FLOW3 package "TYPO3.Fluid".        *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 *  of the License, or (at your option) any later version.                *
 *                                                                        *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Lesser       *
 * General Public License for more details.                               *
 *                                                                        *
 * You should have received a copy of the GNU Lesser General Public       *
 * License along with the script.                                         *
 * If not, see http://www.gnu.org/licenses/lgpl.html                      *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * Test case
 */
class WidgetRequestBuilderTest extends \TYPO3\CMS\Core\Tests\UnitTestCase
{
    /**
     * @var \TYPO3\CMS\Fluid\Core\Widget\WidgetRequestBuilder
     */
    protected $widgetRequestBuilder;

    /**
     * @var \TYPO3\CMS\Extbase\Object\ObjectManagerInterface
     */
    protected $mockObjectManager;

    /**
     * @var \TYPO3\CMS\Fluid\Core\Widget\WidgetRequest
     */
    protected $mockWidgetRequest;

    /**
     * @var \TYPO3\CMS\Fluid\Core\Widget\AjaxWidgetContextHolder
     */
    protected $mockAjaxWidgetContextHolder;

    /**
     * @var \TYPO3\CMS\Fluid\Core\Widget\WidgetContext
     */
    protected $mockWidgetContext;

    protected function setUp()
    {
        $this->widgetRequestBuilder = $this->getAccessibleMock(\TYPO3\CMS\Fluid\Core\Widget\WidgetRequestBuilder::class, ['setArgumentsFromRawRequestData']);
        $this->mockWidgetRequest = $this->getMock(\TYPO3\CMS\Fluid\Core\Widget\WidgetRequest::class);
        $this->mockObjectManager = $this->getMock(\TYPO3\CMS\Extbase\Object\ObjectManagerInterface::class);
        $this->mockObjectManager->expects($this->once())->method('get')->with(\TYPO3\CMS\Fluid\Core\Widget\WidgetRequest::class)->will($this->returnValue($this->mockWidgetRequest));
        $this->widgetRequestBuilder->_set('objectManager', $this->mockObjectManager);
        $this->mockWidgetContext = $this->getMock(\TYPO3\CMS\Fluid\Core\Widget\WidgetContext::class);
        $this->mockAjaxWidgetContextHolder = $this->getMock(\TYPO3\CMS\Fluid\Core\Widget\AjaxWidgetContextHolder::class, [], [], '', false);
        $this->widgetRequestBuilder->injectAjaxWidgetContextHolder($this->mockAjaxWidgetContextHolder);
        $this->mockAjaxWidgetContextHolder->expects($this->once())->method('get')->will($this->returnValue($this->mockWidgetContext));
    }

    /**
     * @test
     */
    public function buildSetsRequestUri()
    {
        $requestUri = \TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('TYPO3_REQUEST_URL');
        $this->mockWidgetRequest->expects($this->once())->method('setRequestURI')->with($requestUri);
        $this->widgetRequestBuilder->build();
    }

    /**
     * @test
     */
    public function buildSetsBaseUri()
    {
        $baseUri = \TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('TYPO3_SITE_URL');
        $this->mockWidgetRequest->expects($this->once())->method('setBaseURI')->with($baseUri);
        $this->widgetRequestBuilder->build();
    }

    /**
     * @test
     */
    public function buildSetsRequestMethod()
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $this->mockWidgetRequest->expects($this->once())->method('setMethod')->with('POST');
        $this->widgetRequestBuilder->build();
    }

    /**
     * @test
     */
    public function buildSetsPostArgumentsFromRequest()
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_GET = ['get' => 'foo'];
        $_POST = ['post' => 'bar'];
        $this->mockWidgetRequest->expects($this->once())->method('setArguments')->with($_POST);
        $this->widgetRequestBuilder->build();
    }

    /**
     * @test
     */
    public function buildSetsGetArgumentsFromRequest()
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_GET = ['get' => 'foo'];
        $_POST = ['post' => 'bar'];
        $this->mockWidgetRequest->expects($this->once())->method('setArguments')->with($_GET);
        $this->widgetRequestBuilder->build();
    }

    /**
     * @test
     */
    public function buildSetsControllerActionNameFromGetArguments()
    {
        $_GET = ['action' => 'myAction'];
        $this->mockWidgetRequest->expects($this->once())->method('setControllerActionName')->with('myAction');
        $this->widgetRequestBuilder->build();
    }

    /**
     * @test
     */
    public function buildSetsWidgetContext()
    {
        $_GET = ['fluid-widget-id' => '123'];
        $this->mockAjaxWidgetContextHolder->expects($this->once())->method('get')->with('123')->will($this->returnValue($this->mockWidgetContext));
        $this->mockWidgetRequest->expects($this->once())->method('setWidgetContext')->with($this->mockWidgetContext);
        $this->widgetRequestBuilder->build();
    }

    /**
     * @test
     */
    public function buildReturnsRequest()
    {
        $expected = $this->mockWidgetRequest;
        $actual = $this->widgetRequestBuilder->build();
        $this->assertSame($expected, $actual);
    }
}
