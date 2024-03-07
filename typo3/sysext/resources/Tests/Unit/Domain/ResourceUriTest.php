<?php declare(strict_types=1);

namespace TYPO3\CMS\Resources\Tests\Unit\Domain;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Resources\Domain\ResourceUri;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class ResourceUriTest extends UnitTestCase
{

    #[Test]
    public function itSupportsTYPO3Scheme(): void
    {
        $resourceUri = new ResourceUri('t3://post.blog-example.typo3.org/15');
        $this->assertSame('t3', $resourceUri->getScheme());
    }


    #[Test]
    public function itDoesNotSupportOtherSchemes(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new ResourceUri('http://post.blog-example.typo3.org/15');
    }



}
