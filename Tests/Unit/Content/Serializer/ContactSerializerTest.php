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

namespace Sulu\Bundle\HeadlessBundle\Tests\Unit\Content\Serializer;

use JMS\Serializer\SerializationContext;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Sulu\Bundle\ContactBundle\Api\Contact;
use Sulu\Bundle\ContactBundle\Contact\ContactManager;
use Sulu\Bundle\ContactBundle\Entity\ContactInterface;
use Sulu\Bundle\ContactBundle\Entity\ContactTitle;
use Sulu\Bundle\ContactBundle\Entity\ContactTitleRepository;
use Sulu\Bundle\ContactBundle\Entity\Position;
use Sulu\Bundle\ContactBundle\Entity\PositionRepository;
use Sulu\Bundle\HeadlessBundle\Content\Serializer\ContactSerializer;
use Sulu\Bundle\HeadlessBundle\Content\Serializer\ContactSerializerInterface;
use Sulu\Bundle\HeadlessBundle\Content\Serializer\MediaSerializerInterface;
use Sulu\Bundle\MediaBundle\Api\Media;
use Sulu\Bundle\MediaBundle\Entity\MediaInterface;
use Sulu\Bundle\MediaBundle\Media\Manager\MediaManagerInterface;
use Sulu\Component\Serializer\ArraySerializerInterface;

class ContactSerializerTest extends TestCase
{
    /**
     * @var ContactManager|ObjectProphecy
     */
    private $contactManager;

    /**
     * @var ArraySerializerInterface|ObjectProphecy
     */
    private $arraySerializer;

    /**
     * @var MediaManagerInterface|ObjectProphecy
     */
    private $mediaManager;

    /**
     * @var MediaSerializerInterface|ObjectProphecy
     */
    private $mediaSerializer;

    /**
     * @var ContactTitleRepository|ObjectProphecy
     */
    private $contactTitleRepository;

    /**
     * @var PositionRepository|ObjectProphecy
     */
    private $positionRepository;

    /**
     * @var ContactSerializerInterface
     */
    private $contactSerializer;

    protected function setUp(): void
    {
        $this->contactManager = $this->prophesize(ContactManager::class);
        $this->arraySerializer = $this->prophesize(ArraySerializerInterface::class);
        $this->mediaManager = $this->prophesize(MediaManagerInterface::class);
        $this->mediaSerializer = $this->prophesize(MediaSerializerInterface::class);
        $this->contactTitleRepository = $this->prophesize(ContactTitleRepository::class);
        $this->positionRepository = $this->prophesize(PositionRepository::class);

        $this->contactSerializer = new ContactSerializer(
            $this->contactManager->reveal(),
            $this->arraySerializer->reveal(),
            $this->mediaManager->reveal(),
            $this->mediaSerializer->reveal(),
            $this->contactTitleRepository->reveal(),
            $this->positionRepository->reveal()
        );
    }

    public function testSerialize(): void
    {
        $locale = 'en';

        $apiContact = $this->prophesize(Contact::class);
        $apiContact->getNote()->willReturn('test-note');
        $apiContact->getAvatar()->willReturn([
            'id' => 2,
            'url' => '/media/2/download/sulu.png?v=1',
        ]);

        $contact = $this->prophesize(ContactInterface::class);
        $this->contactManager->getContact($contact->reveal(), $locale)->willReturn($apiContact->reveal());

        $media = $this->prophesize(MediaInterface::class);
        $apiMedia = $this->prophesize(Media::class);
        $apiMedia->getEntity()->willReturn($media->reveal());

        $this->arraySerializer->serialize($apiContact, null)->willReturn([
            'id' => 1,
            'firstName' => 'John',
            'lastName' => 'Doe',
            'fullName' => 'John Doe',
            'title' => 1,
            'position' => 1,
        ]);

        $this->mediaSerializer->serialize($media->reveal(), $locale)->willReturn([
            'id' => 2,
            'formatUri' => '/media/2/{format}/media-2.jpg?v=1-0',
        ]);

        $this->mediaManager->getById(Argument::any(), $locale)->shouldBeCalled()->willReturn($apiMedia->reveal());

        $contactTitle = $this->prophesize(ContactTitle::class);
        $contactTitle->getTitle()->willReturn('fancyTitle');
        $this->contactTitleRepository->find(Argument::any())->willReturn($contactTitle->reveal());

        $contactPosition = $this->prophesize(Position::class);
        $contactPosition->getPosition()->willReturn('CEO');
        $this->positionRepository->find(Argument::any())->wilLReturn($contactPosition->reveal());

        $result = $this->contactSerializer->serialize($contact->reveal(), $locale, null);

        $this->assertSame([
            'id' => 1,
            'firstName' => 'John',
            'lastName' => 'Doe',
            'fullName' => 'John Doe',
            'title' => 'fancyTitle',
            'position' => 'CEO',
            'note' => 'test-note',
            'avatar' => [
                'id' => 2,
                'formatUri' => '/media/2/{format}/media-2.jpg?v=1-0',
            ],
        ], $result);
    }

    public function testSerializeWithContext(): void
    {
        $locale = 'en';

        $apiContact = $this->prophesize(Contact::class);
        $apiContact->getNote()->willReturn(null);
        $apiContact->getAvatar()->willReturn([
            'id' => 2,
            'url' => '/media/2/download/sulu.png?v=1',
        ]);

        $contact = $this->prophesize(ContactInterface::class);
        $this->contactManager->getContact($contact->reveal(), $locale)->willReturn($apiContact->reveal());

        $media = $this->prophesize(MediaInterface::class);
        $apiMedia = $this->prophesize(Media::class);
        $apiMedia->getEntity()->willReturn($media->reveal());

        $context = $this->prophesize(SerializationContext::class);

        $this->arraySerializer->serialize($apiContact, $context)->willReturn([
            'id' => 1,
            'firstName' => 'John',
            'lastName' => 'Doe',
            'fullName' => 'John Doe',
            'title' => 1,
            'position' => 1,
        ]);

        $this->mediaSerializer->serialize($media->reveal(), $locale)->willReturn([
            'id' => 2,
            'formatUri' => '/media/2/{format}/media-2.jpg?v=1-0',
        ]);

        $this->mediaManager->getById(Argument::any(), $locale)->shouldBeCalled()->willReturn($apiMedia->reveal());

        $contactTitle = $this->prophesize(ContactTitle::class);
        $contactTitle->getTitle()->willReturn('fancyTitle');
        $this->contactTitleRepository->find(Argument::any())->willReturn($contactTitle->reveal());

        $contactPosition = $this->prophesize(Position::class);
        $contactPosition->getPosition()->willReturn('CEO');
        $this->positionRepository->find(Argument::any())->wilLReturn($contactPosition->reveal());

        $result = $this->contactSerializer->serialize($contact->reveal(), $locale, $context->reveal());

        $this->assertSame([
            'id' => 1,
            'firstName' => 'John',
            'lastName' => 'Doe',
            'fullName' => 'John Doe',
            'title' => 'fancyTitle',
            'position' => 'CEO',
            'avatar' => [
                'id' => 2,
                'formatUri' => '/media/2/{format}/media-2.jpg?v=1-0',
            ],
        ], $result);
    }

    public function testSerializeWithoutAvatarAndTitleAndPosition(): void
    {
        $locale = 'en';

        $apiContact = $this->prophesize(Contact::class);
        $apiContact->getNote()->willReturn(null);
        $apiContact->getAvatar()->willReturn(null);

        $contact = $this->prophesize(ContactInterface::class);
        $this->contactManager->getContact($contact->reveal(), $locale)->willReturn($apiContact->reveal());

        $context = $this->prophesize(SerializationContext::class);

        $this->arraySerializer->serialize($apiContact, $context)->willReturn([
            'id' => 1,
            'firstName' => 'John',
            'lastName' => 'Doe',
            'fullName' => 'John Doe',
        ]);

        $result = $this->contactSerializer->serialize($contact->reveal(), $locale, $context->reveal());

        $this->assertSame([
            'id' => 1,
            'firstName' => 'John',
            'lastName' => 'Doe',
            'fullName' => 'John Doe',
        ], $result);
    }
}
