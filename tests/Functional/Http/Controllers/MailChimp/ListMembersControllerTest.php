<?php
declare(strict_types=1);

namespace Functional\Http\Controllers\MailChimp;

use App\Database\Entities\MailChimp\MailChimpList;
use App\Database\Entities\MailChimp\MailChimpListMember;
use Mailchimp\Mailchimp;
use TestCases\MailChimp\ListMemberTestCase;

final class ListMembersControllerTest extends ListMemberTestCase
{
    /**
     * @var array
     */
    protected $createdListIds = [];

    /**
     * Call MailChimp to delete lists created during test.
     *
     * @return void
     */
    public function tearDown(): void
    {
        /** @var Mailchimp $mailChimp */
        $mailChimp = $this->app->make(Mailchimp::class);

        foreach ($this->createdListIds as $listId) {
            // Delete list on MailChimp after test
            $mailChimp->delete(\sprintf('lists/%s', $listId));
        }

        parent::tearDown();
    }

    /**
     * Tests API returns error response when list not found
     */
    public function testCreateMemberListNotFoundResponse(): void
    {
        $this->post('/mailchimp/lists/not_existing_list/members', static::generateMemberData());
        $this->assertListNotFoundResponse('not_existing_list');
    }

    /**
     * Tests API returns successful response with mailchimp ID and created data when creating with valid data
     */
    public function testCreateMemberSuccessfully(): void
    {
        $this->post('/mailchimp/lists', static::$listData);
        $listContent = \json_decode($this->response->content(), true);
        $listId = $listContent['list_id'];

        $memberCreateData = self::generateMemberData();
        $this->post(\sprintf("/mailchimp/lists/%s/members", $listId), $memberCreateData);

        $memberContent = \json_decode($this->response->content(), true);
        $this->assertResponseOk();
        $this->seeJson($memberCreateData);
        self::assertArrayHasKey('mail_chimp_id', $memberContent);
        self::assertNotNull($memberContent['mail_chimp_id']);

        $this->createdListIds[] = $listContent['mail_chimp_id']; // Store MailChimp list id for cleaning purposes
    }

    /**
     * Tests API returns error response with info when creating with invalid data
     */
    public function testCreateMemberValidationFailed(): void
    {
        $list = $this->createList(self::$listData);
        $this->createListMember($list, self::generateMemberData());

        $this->post(\sprintf('/mailchimp/lists/%s/members', $list->getId()));
        $this->assertValidationFailedResponse();

        $content = \json_decode($this->response->getContent(), true);

        $requiredFields = [
            'email_address',
            'status'
        ];
        foreach (\array_keys(static::generateMemberData()) as $key) {
            if (! \in_array($key, $requiredFields, true)) {
                continue;
            }

            self::assertArrayHasKey($key, $content['errors']);
        }
    }

    /**
     * Tests API returns error response when list not found
     */
    public function testUpdateMemberListNotFoundResponse(): void
    {
        $this->put(
            \sprintf('/mailchimp/lists/%s/members/%s', 'not_existing_list', 'not_existing_member'),
            self::generateMemberData()
        );
        $this->assertListNotFoundResponse('not_existing_list');
    }

    /**
     * Tests API returns error response when member not found in list
     */
    public function testUpdateMemberNotFoundResponse(): void
    {
        $list = $this->createList(self::$listData);

        $this->put(
            \sprintf('/mailchimp/lists/%s/members/%s', $list->getId(), 'not_existing_member'),
            self::generateMemberData()
        );
        $this->assertMemberNotFoundResponse('not_existing_member');
    }

    /**
     * Tests API returns error response with info when updating with invalid data
     */
    public function testUpdateMemberValidationFailed(): void
    {
        $list = $this->createList(self::$listData);
        $listMember = $this->createListMember($list, self::generateMemberData());

        $this->put(
            \sprintf('/mailchimp/lists/%s/members/%s', $list->getId(), $listMember->getId()),
            ['email_type' => 'unknown']
        );

        $this->assertValidationFailedResponse();

        $content = \json_decode($this->response->getContent(), true);
        self::assertArrayHasKey('email_type', $content['errors']);

    }

    /**
     * Tests API returns successful response with mailchimp ID and updated data when creating existing member with
     *  valid data with
     */
    public function testUpdateMemberSuccessfully(): void
    {
        $this->post('/mailchimp/lists', static::$listData);
        $listContent = \json_decode($this->response->content(), true);
        $listId = $listContent['list_id'];

        $memberCreateData = self::generateMemberData();
        $this->post(\sprintf("/mailchimp/lists/%s/members", $listId), $memberCreateData);
        $memberContent = \json_decode($this->response->content(), true);

        // Refresh list for refreshing 'members' field after creating member above
        $list = $this->entityManager->getRepository(MailChimpList::class)->find($listId);
        $this->entityManager->refresh($list);

        $this->put(
            \sprintf('/mailchimp/lists/%s/members/%s', $listId, $memberContent['id']),
            ['merge_fields' => ['ADDRESS' => ['addr1' => 'Updated address']]]
        );
        $updatedContent = \json_decode($this->response->content(), true);

        $this->assertResponseOk();

        foreach (\array_keys($memberCreateData) as $key) {
            self::assertArrayHasKey($key, $updatedContent);
            self::assertEquals('Updated address', $updatedContent['merge_fields']['ADDRESS']['addr1']);
        }

        $this->createdListIds[] = $listContent['mail_chimp_id']; // Store MailChimp list id for cleaning purposes
    }

    /**
     * Tests API returns error response when list not found
     */
    public function testShowAllMemberListNotFoundResponse(): void
    {
        $this->get(\sprintf('/mailchimp/lists/%s/members', 'not_existing_list'));
        $this->assertListNotFoundResponse('not_existing_list');
    }

    /**
     * Tests API returns successful empty response when list has not members
     */
    public function testShowAllMemberSuccessfulEmptyResponseFromEmptyListResponse(): void
    {
        $list = $this->createList(self::$listData);

        $this->get(\sprintf('/mailchimp/lists/%s/members', $list->getId()));

        $this->assertResponseOk();
        self::assertEmpty(\json_decode($this->response->content(), true));
    }

    /**
     * Tests API successfully shows all created member when requesting existing list
     */
    public function testShowAllMemberSuccessfully(): void
    {
        $list = $this->createList(self::$listData);

        $createdMembersData = [];
        $createMembersCount = 2;
        for ($i = 0; $i < $createMembersCount; $i++) {
            $memberData = self::generateMemberData();
            $this->createListMember($list, $memberData);

            $createdMembersData[] = $memberData;
        }

        $this->get(\sprintf('/mailchimp/lists/%s/members', $list->getId()));

        $contentMembers = \json_decode($this->response->content(), true);

        $this->assertResponseOk();

        for ($i = 0; $i < $createMembersCount; $i++) {
            foreach ($createdMembersData[$i] as $key => $value) {
                self::assertArrayHasKey($key, $contentMembers[$i]);
                self::assertEquals($value, $contentMembers[$i][$key]);
            }
        }
    }

    /**
     * Tests API returns error response when list not found
     */
    public function testShowMemberListNotFoundResponse(): void
    {
        $this->get(\sprintf('/mailchimp/lists/%s/members/%s', 'not_existing_list', 'not_existing_member'));
        $this->assertListNotFoundResponse('not_existing_list');
    }

    /**
     * Tests API returns error response when member not found in list
     */
    public function testShowMemberNotFoundResponse(): void
    {
        $list = $this->createList(self::$listData);

        $this->get(\sprintf('/mailchimp/lists/%s/members/%s', $list->getId(), 'not_existing_member'));
        $this->assertMemberNotFoundResponse('not_existing_member');
    }

    /**
     * Tests API successfully shows created member when requesting existing member
     */
    public function testShowMemberSuccessfully(): void
    {
        $list = $this->createList(self::$listData);
        $memberData = self::generateMemberData();
        $listMember = $this->createListMember($list, $memberData);

        $this->get(\sprintf('/mailchimp/lists/%s/members/%s', $list->getId(), $listMember->getId()));

        $contentMember = \json_decode($this->response->content(), true);

        $this->assertResponseOk();

        foreach ($memberData as $key => $value) {
            self::assertArrayHasKey($key, $contentMember);
            self::assertEquals($value, $contentMember[$key]);
        }
    }

    /**
     * Tests API returns error response when list not found
     */
    public function testRemoveMemberListNotFoundResponse(): void
    {
        $this->delete(\sprintf('/mailchimp/lists/%s/members/%s', 'not_existing_list', 'not_existing_member'));
        $this->assertListNotFoundResponse('not_existing_list');
    }

    /**
     * Tests API returns error response when member not found in list
     */
    public function testRemoveMemberNotFoundResponse(): void
    {
        $list = $this->createList(self::$listData);

        $this->delete(\sprintf('/mailchimp/lists/%s/members/%s', $list->getId(), 'not_existing_member'));
        $this->assertMemberNotFoundResponse('not_existing_member');
    }

    /**
     * Tests API returns successful response when removing existing user and member status changed to 'archived'
     */
    public function testRemoveMemberSuccessfully(): void
    {
        $this->post('/mailchimp/lists', static::$listData);
        $listContent = \json_decode($this->response->content(), true);
        $listId = $listContent['list_id'];

        $this->post(\sprintf("/mailchimp/lists/%s/members", $listId), self::generateMemberData());
        $memberContent = \json_decode($this->response->content(), true);

        // Refresh list for refreshing 'members' field after creating member above
        $list = $this->entityManager->getRepository(MailChimpList::class)->find($listId);
        $this->entityManager->refresh($list);

        $this->delete(\sprintf('/mailchimp/lists/%s/members/%s', $listContent['list_id'], $memberContent['id']));

        $this->assertResponseOk();
        self::assertEmpty(\json_decode($this->response->content(), true));

        $this->get(\sprintf('/mailchimp/lists/%s/members/%s', $listContent['list_id'], $memberContent['id']));
        $newMemberContent = \json_decode($this->response->content(), true);
        self::assertEquals(MailChimpListMember::STATUS_ARCHIVED, $newMemberContent['status']);

        $this->createdListIds[] = $listContent['mail_chimp_id']; // Store MailChimp list id for cleaning purposes
    }

    /**
     * Tests API returns error response when repeating removal
     */
    public function testRemoveMemberRepeatingFails(): void
    {
        $this->post('/mailchimp/lists', static::$listData);
        $listContent = \json_decode($this->response->content(), true);
        $listId = $listContent['list_id'];

        $this->post(\sprintf("/mailchimp/lists/%s/members", $listId), self::generateMemberData());
        $memberContent = \json_decode($this->response->content(), true);

        // Refresh list for refreshing 'members' field after creating member above
        $list = $this->entityManager->getRepository(MailChimpList::class)->find($listId);
        $this->entityManager->refresh($list);

        $this->delete(\sprintf('/mailchimp/lists/%s/members/%s', $listContent['list_id'], $memberContent['id']));

        $this->assertResponseOk();
        self::assertEmpty(\json_decode($this->response->content(), true));

        $this->delete(\sprintf('/mailchimp/lists/%s/members/%s', $listContent['list_id'], $memberContent['id']));
        $content = \json_decode($this->response->content(), true);

        $this->assertResponseStatus(405);
        self::assertArrayHasKey('message', $content);
        self::assertEquals('Method Not Allowed', $content['message']);

        $this->createdListIds[] = $listContent['mail_chimp_id']; // Store MailChimp list id for cleaning purposes
    }

    /**
     * Tests API returns error response when list not found
     */
    public function testDeletePermanentMemberListNotFoundResponse(): void
    {
        $this->post(\sprintf(
            '/mailchimp/lists/%s/members/%s/actions/delete-permanent',
            'not_existing_list',
            'not_existing_member')
        );
        $this->assertListNotFoundResponse('not_existing_list');
    }

    /**
     * Tests API returns error response when member not found in list
     */
    public function testDeletePermanentMemberNotFoundResponse(): void
    {
        $list = $this->createList(self::$listData);

        $this->post(\sprintf(
            '/mailchimp/lists/%s/members/%s/actions/delete-permanent',
            $list->getId(),
            'not_existing_member'
        ));
        $this->assertMemberNotFoundResponse('not_existing_member');
    }

    /**
     * Tests API returns successful response when permanently deleting existing user and 'show' returns 404
     */
    public function testDeletePermanentSuccessfully(): void
    {
        $this->post('/mailchimp/lists', static::$listData);
        $listContent = \json_decode($this->response->content(), true);
        $listId = $listContent['list_id'];

        $this->post(\sprintf("/mailchimp/lists/%s/members", $listId), self::generateMemberData());
        $memberContent = \json_decode($this->response->content(), true);
        $memberId = $memberContent['id'];

        // Refresh list for refreshing 'members' field after creating member above
        $list = $this->entityManager->getRepository(MailChimpList::class)->find($listId);
        $this->entityManager->refresh($list);

        $this->post(\sprintf(
            '/mailchimp/lists/%s/members/%s/actions/delete-permanent',
            $listContent['list_id'],
            $memberId
        ));

        $this->assertResponseOk();
        self::assertEmpty(\json_decode($this->response->content(), true));

        $this->get(\sprintf('/mailchimp/lists/%s/members/%s', $listId, $memberId));
        $this->assertMemberNotFoundResponse($memberId);

        $this->createdListIds[] = $listContent['mail_chimp_id']; // Store MailChimp list id for cleaning purposes
    }

    /**
     * Asserts error response when list not found.
     *
     * @param string $listId
     *
     * @return void
     */
    private function assertListNotFoundResponse(string $listId): void
    {
        $content = \json_decode($this->response->content(), true);

        $this->assertResponseStatus(404);
        self::assertArrayHasKey('message', $content);
        self::assertEquals(\sprintf('MailChimpList[%s] not found', $listId), $content['message']);
    }

    /**
     * Asserts error response when member not found.
     *
     * @param string $listMemberId
     *
     * @return void
     */
    private function assertMemberNotFoundResponse(string $listMemberId): void
    {
        $content = \json_decode($this->response->content(), true);

        $this->assertResponseStatus(404);
        self::assertArrayHasKey('message', $content);
        self::assertEquals(\sprintf('MailChimpListMember[%s] not found', $listMemberId), $content['message']);
    }

    /**
     * Asserts error response when validation failed
     */
    private function assertValidationFailedResponse(): void
    {
        $content = \json_decode($this->response->getContent(), true);

        $this->assertResponseStatus(400);
        self::assertArrayHasKey('message', $content);
        self::assertArrayHasKey('errors', $content);
        self::assertEquals('Invalid data given', $content['message']);
    }
}
