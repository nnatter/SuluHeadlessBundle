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

use JMS\Serializer\SerializationContext;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Sulu\Bundle\ContactBundle\Api\Account;
use Sulu\Bundle\ContactBundle\Api\Contact;
use Sulu\Bundle\ContactBundle\Contact\AccountManager;
use Sulu\Bundle\ContactBundle\Contact\ContactManager;
use Sulu\Bundle\ContactBundle\Entity\AccountInterface;
use Sulu\Bundle\ContactBundle\Entity\ContactInterface;
use Sulu\Bundle\HeadlessBundle\Content\ContentTypeResolver\ContactAccountSelectionResolver;
use Sulu\Bundle\HeadlessBundle\Content\ContentView;
use Sulu\Bundle\HeadlessBundle\Content\Serializer\AccountSerializerInterface;
use Sulu\Bundle\HeadlessBundle\Content\Serializer\ContactSerializerInterface;
use Sulu\Component\Content\Compat\PropertyInterface;

class ContactAccountSelectionResolverTest extends TestCase
{
    /**
     * @var ContactManager|ObjectProphecy
     */
    private $contactManager;

    /**
     * @var AccountManager|ObjectProphecy
     */
    private $accountManager;

    /**
     * @var ContactSerializerInterface|ObjectProphecy
     */
    private $contactSerializer;

    /**
     * @var AccountSerializerInterface|ObjectProphecy
     */
    private $accountSerializer;

    /**
     * @var ContactAccountSelectionResolver
     */
    private $contactAccountResolver;

    protected function setUp(): void
    {
        $this->contactManager = $this->prophesize(ContactManager::class);
        $this->accountManager = $this->prophesize(AccountManager::class);
        $this->contactSerializer = $this->prophesize(ContactSerializerInterface::class);
        $this->accountSerializer = $this->prophesize(AccountSerializerInterface::class);

        $this->contactAccountResolver = new ContactAccountSelectionResolver(
            $this->contactManager->reveal(),
            $this->accountManager->reveal(),
            $this->contactSerializer->reveal(),
            $this->accountSerializer->reveal()
        );
    }

    public function testGetContentType(): void
    {
        self::assertSame('contact_account_selection', $this->contactAccountResolver::getContentType());
    }

    public function testResolve(): void
    {
        $locale = 'en';

        $contact = $this->prophesize(ContactInterface::class);
        $apiContact = $this->prophesize(Contact::class);
        $apiContact->getEntity()->willReturn($contact->reveal());

        $account = $this->prophesize(AccountInterface::class);
        $apiAccount = $this->prophesize(Account::class);
        $apiAccount->getEntity()->willReturn($account->reveal());

        $data = ['c2', 'a3'];

        $this->contactManager->getById(2, $locale)->willReturn($apiContact->reveal());
        $this->contactSerializer->serialize($contact, $locale, Argument::type(SerializationContext::class))->willReturn([
            'id' => 2,
            'firstName' => 'John',
            'lastName' => 'Doe',
            'fullName' => 'John Doe',
            'title' => 'fancyTitle',
            'position' => 'CEO',
            'avatar' => [
                'id' => 2,
                'formatUri' => '/media/2/{format}/media-2.jpg?v=1-0',
            ],
        ]);

        $this->accountManager->getById(3, $locale)->willReturn($apiAccount->reveal());
        $this->accountSerializer->serialize($account, $locale, Argument::type(SerializationContext::class))->willReturn([
            'id' => 3,
            'depth' => 1,
            'name' => 'Sulu GmbH',
            'corporation' => 'Digital Agency',
            'logo' => [
                'id' => 2,
                'formatUri' => '/media/2/{format}/media-2.jpg?v=1-0',
            ],
        ]);

        $property = $this->prophesize(PropertyInterface::class);
        $result = $this->contactAccountResolver->resolve($data, $property->reveal(), $locale);

        $this->assertInstanceOf(ContentView::class, $result);
        $this->assertSame(
            [
                [
                    'id' => 2,
                    'firstName' => 'John',
                    'lastName' => 'Doe',
                    'fullName' => 'John Doe',
                    'title' => 'fancyTitle',
                    'position' => 'CEO',
                    'avatar' => [
                        'id' => 2,
                        'formatUri' => '/media/2/{format}/media-2.jpg?v=1-0',
                    ],
                ],
                [
                    'id' => 3,
                    'depth' => 1,
                    'name' => 'Sulu GmbH',
                    'corporation' => 'Digital Agency',
                    'logo' => [
                        'id' => 2,
                        'formatUri' => '/media/2/{format}/media-2.jpg?v=1-0',
                    ],
                ],
            ],
            $result->getContent()
        );

        $this->assertSame(
            ['ids' => ['c2', 'a3']],
            $result->getView()
        );
    }

    public function testResolveDataIsNull(): void
    {
        $locale = 'en';
        $property = $this->prophesize(PropertyInterface::class);

        $result = $this->contactAccountResolver->resolve(null, $property->reveal(), $locale);

        $this->assertSame([], $result->getContent());

        $this->assertSame(['ids' => []], $result->getView());
    }

    public function testResolveDataIsEmptyArray(): void
    {
        $locale = 'en';
        $property = $this->prophesize(PropertyInterface::class);

        $result = $this->contactAccountResolver->resolve([], $property->reveal(), $locale);

        $this->assertSame([], $result->getContent());

        $this->assertSame(['ids' => []], $result->getView());
    }
}
