<?php
declare(strict_types = 1);
namespace TYPO3\CMS\Seo\Tests\Unit\XmlSitemap;

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Seo\XmlSitemap\PagesXmlSitemapDataProvider;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class PagesXmlSitemapDataProviderTest extends UnitTestCase
{
    /**
     * @test
     */
    public function checkIfCorrectKeyIsGivenAfterConstruct(): void
    {
        $key = 'dummyKey';
        $cObj = $this->prophesize(ContentObjectRenderer::class);

        $subject = $this->getAccessibleMock(
            PagesXmlSitemapDataProvider::class,
            ['generateItems'],
            [$this->prophesize(ServerRequestInterface::class)->reveal(), $key, [], $cObj->reveal()],
            '',
            true
        );
        $subject->expects($this->any())->method('generateItems')->willReturn(null);
        $this->assertEquals($key, $subject->getKey());
    }

    /**
     * @test
     */
    public function checkGetItemsReturnsDefinedItems(): void
    {
        $key = 'dummyKey';
        $cObj = $this->prophesize(ContentObjectRenderer::class);

        $subject = $this->getAccessibleMock(
            PagesXmlSitemapDataProvider::class,
            ['generateItems'],
            [$key, [], $cObj->reveal()],
            '',
            false
        );
        $items = ['foo' => 'bar'];
        $subject->_set('items', $items);

        $this->assertEquals($items, $subject->getItems());
    }

    /**
     * @test
     */
    public function checkGetLastModReturnsRightDate(): void
    {
        $key = 'dummyKey';
        $cObj = $this->prophesize(ContentObjectRenderer::class);

        $subject = $this->getAccessibleMock(
            PagesXmlSitemapDataProvider::class,
            ['generateItems'],
            [$key, [], $cObj->reveal()],
            '',
            false
        );
        $items = [
            [
                'loc' => 'https://yourdomain.com/page-1',
                'lastMod' => 1535655601
            ],
            [
                'loc' => 'https://yourdomain.com/page-2',
                'lastMod' => 1530432000
            ],
            [
                'loc' => 'https://yourdomain.com/page-3',
                'lastMod' => 1535655756
            ],
        ];
        $subject->_set('items', $items);

        $this->assertEquals(1535655756, $subject->getLastModified());
    }
}
