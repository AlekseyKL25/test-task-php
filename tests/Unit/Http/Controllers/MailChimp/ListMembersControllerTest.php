<?php
declare(strict_types=1);

namespace Unit\Http\Controllers\MailChimp;

use App\Http\Controllers\MailChimp\ListMembersController;
use Illuminate\Http\JsonResponse;
use Mailchimp\Mailchimp;
use Mockery\MockInterface;
use TestCases\MailChimp\ListMemberTestCase;

final class ListMembersControllerTest extends ListMemberTestCase
{
    protected const MAILCHIMP_EXCEPTION_MESSAGE = 'MailChimp exception';

    /**
     * Test controller returns error response when exception is thrown during create MailChimp request.
     *
     * @return void
     */
    public function testCreateListMailChimpException(): void
    {
        $list = $this->createList(self::$listData);

        /** @noinspection PhpParamsInspection Mock given on purpose */
        $controller = new ListMembersController($this->entityManager, $this->mockMailChimpForException('post'));

        $this->assertMailChimpExceptionResponse(
            $controller->create($this->getRequest(static::generateMemberData()), $list->getId())
        );
    }

    /**
     * Test controller returns error response when exception is thrown during update MailChimp request.
     *
     * @return void
     */
    public function testUpdateListMailChimpException(): void
    {
        $list = $this->createList(self::$listData);
        $member = $this->createListMember($list, self::generateMemberData());

        // If there is no list id, skip
        if ($member->getId() === null) {
            self::markTestSkipped('Unable to update, no id provided for list');

            return;
        }

        /** @noinspection PhpParamsInspection Mock given on purpose */
        $controller = new ListMembersController($this->entityManager, $this->mockMailChimpForException('patch'));

        $this->assertMailChimpExceptionResponse(
            $controller->update($this->getRequest(), $list->getId(), $member->getId())
        );
    }

    /**
     * Test controller returns error response when exception is thrown during remove MailChimp request.
     *
     * @return void
     */
    public function testRemoveListMailChimpException(): void
    {
        $list = $this->createList(self::$listData);
        $member = $this->createListMember($list, self::generateMemberData());

        // If there is no list id, skip
        if ($member->getId() === null) {
            self::markTestSkipped('Unable to update, no id provided for list');

            return;
        }

        /** @noinspection PhpParamsInspection Mock given on purpose */
        $controller = new ListMembersController($this->entityManager, $this->mockMailChimpForException('delete'));

        $this->assertMailChimpExceptionResponse($controller->remove($list->getId(), $member->getId()));
    }

    /**
     * Test controller returns error response when exception is thrown during delete permanent MailChimp request.
     *
     * @return void
     */
    public function testDeletePermanentListMailChimpException(): void
    {
        $list = $this->createList(self::$listData);
        $member = $this->createListMember($list, self::generateMemberData());

        // If there is no list id, skip
        if ($member->getId() === null) {
            self::markTestSkipped('Unable to update, no id provided for list');

            return;
        }

        /** @noinspection PhpParamsInspection Mock given on purpose */
        $controller = new ListMembersController($this->entityManager, $this->mockMailChimpForException('post'));

        $this->assertMailChimpExceptionResponse($controller->actionDeletePermanent($list->getId(), $member->getId()));
    }

    /**
     * Asserts error response when MailChimp exception is thrown.
     *
     * @param \Illuminate\Http\JsonResponse $response
     *
     * @return void
     */
    protected function assertMailChimpExceptionResponse(JsonResponse $response): void
    {
        $content = \json_decode($response->content(), true);

        self::assertEquals(400, $response->getStatusCode());
        self::assertArrayHasKey('message', $content);
        self::assertEquals(self::MAILCHIMP_EXCEPTION_MESSAGE, $content['message']);
    }

    /**
     * Returns mock of MailChimp to trow exception when requesting their API.
     *
     * @param string $method
     *
     * @return \Mockery\MockInterface
     *
     * @SuppressWarnings(PHPMD.StaticAccess) Mockery requires static access to mock()
     */
    private function mockMailChimpForException(string $method): MockInterface
    {
        $mailChimp = \Mockery::mock(Mailchimp::class);

        $mailChimp
            ->shouldReceive($method)
            ->once()
            ->withArgs(function (string $method, ?array $options = null) {
                return !empty($method) && (null === $options || \is_array($options));
            })
            ->andThrow(new \Exception(self::MAILCHIMP_EXCEPTION_MESSAGE));

        return $mailChimp;
    }
}
