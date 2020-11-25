<?php

declare(strict_types=1);

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\HeadlessBundle\Tests\Unit\Content\ContentTypeResolver;

use PHPUnit\Framework\TestCase;
use Prophecy\Prophecy\ObjectProphecy;
use Sulu\Bundle\HeadlessBundle\Content\ContentTypeResolver\MediaSelectionResolver;
use Sulu\Bundle\HeadlessBundle\Content\ContentView;
use Sulu\Bundle\HeadlessBundle\Content\Serializer\MediaSerializerInterface;
use Sulu\Bundle\MediaBundle\Api\Media;
use Sulu\Bundle\MediaBundle\Entity\MediaInterface;
use Sulu\Bundle\MediaBundle\Media\Manager\MediaManagerInterface;
use Sulu\Component\Content\Compat\PropertyInterface;

class MediaSelectionResolverTest extends TestCase
{
    /**
     * @var MediaManagerInterface|ObjectProphecy
     */
    private $mediaManager;

    /**
     * @var MediaSerializerInterface|ObjectProphecy
     */
    private $mediaSerializer;

    /**
     * @var MediaSelectionResolver
     */
    private $mediaResolver;

    protected function setUp(): void
    {
        $this->mediaManager = $this->prophesize(MediaManagerInterface::class);
        $this->mediaSerializer = $this->prophesize(MediaSerializerInterface::class);

        $this->mediaResolver = new MediaSelectionResolver(
            $this->mediaManager->reveal(),
            $this->mediaSerializer->reveal()
        );
    }

    public function testGetContentType(): void
    {
        self::assertSame('media_selection', $this->mediaResolver::getContentType());
    }

    public function testResolve(): void
    {
        $locale = 'en';

        $media1 = $this->prophesize(MediaInterface::class);
        $apiMedia1 = $this->prophesize(Media::class);
        $apiMedia1->getEntity()->willReturn($media1->reveal());

        $media2 = $this->prophesize(MediaInterface::class);
        $apiMedia2 = $this->prophesize(Media::class);
        $apiMedia2->getEntity()->willReturn($media2->reveal());

        $this->mediaManager->getByIds([1, 2], 'en')->willReturn([$apiMedia1->reveal(), $apiMedia2->reveal()]);

        $this->mediaSerializer->serialize($media1->reveal(), $locale)->willReturn([
            'id' => 1,
            'formatUri' => '/media/1/{format}/media-1.jpg?v=1-0',
        ]);
        $this->mediaSerializer->serialize($media2->reveal(), $locale)->willReturn([
            'id' => 2,
            'formatUri' => '/media/2/{format}/media-2.jpg?v=1-0',
        ]);

        $property = $this->prophesize(PropertyInterface::class);

        $result = $this->mediaResolver->resolve(['ids' => [1, 2]], $property->reveal(), $locale);

        $this->assertInstanceOf(ContentView::class, $result);
        $this->assertSame(
            [
                [
                    'id' => 1,
                    'formatUri' => '/media/1/{format}/media-1.jpg?v=1-0',
                ],
                [
                    'id' => 2,
                    'formatUri' => '/media/2/{format}/media-2.jpg?v=1-0',
                ],
            ],
            $result->getContent()
        );
        $this->assertSame(
            [
                'ids' => [1, 2],
            ],
            $result->getView()
        );
    }

    public function testResolveDataIsNull(): void
    {
        $locale = 'en';
        $property = $this->prophesize(PropertyInterface::class);

        $result = $this->mediaResolver->resolve(null, $property->reveal(), $locale);

        $this->assertInstanceOf(ContentView::class, $result);
        $this->assertSame([], $result->getContent());
        $this->assertSame(['ids' => []], $result->getView());
    }

    public function testResolveDataIsEmptyArray(): void
    {
        $locale = 'en';
        $property = $this->prophesize(PropertyInterface::class);

        $result = $this->mediaResolver->resolve([], $property->reveal(), $locale);

        $this->assertInstanceOf(ContentView::class, $result);
        $this->assertSame([], $result->getContent());
        $this->assertSame(['ids' => []], $result->getView());
    }

    public function testResolveDataWithoutIds(): void
    {
        $locale = 'en';
        $property = $this->prophesize(PropertyInterface::class);

        $this->mediaManager->getByIds([], 'en')->willReturn([]);

        $dataWithoutIdsKey = ['unrelatedKey' => 'unrelatedValue'];
        $result = $this->mediaResolver->resolve($dataWithoutIdsKey, $property->reveal(), $locale);

        $this->assertInstanceOf(ContentView::class, $result);
        $this->assertSame([], $result->getContent());
        $this->assertSame(['ids' => [], 'unrelatedKey' => 'unrelatedValue'], $result->getView());
    }
}
