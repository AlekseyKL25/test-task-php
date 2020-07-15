<?php
declare(strict_types=1);

namespace App\Http\Controllers\MailChimp;

use App\Database\Entities\MailChimp\MailChimpList;
use App\Database\Entities\MailChimp\MailChimpListMember;
use App\Http\Controllers\Controller;
use Doctrine\ORM\EntityManagerInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Mailchimp\Mailchimp;

final class ListMembersController extends Controller
{
    /** @var Mailchimp  */
    private $mailChimp;

    /**
     * ListMembersController constructor.
     *
     * @param EntityManagerInterface $entityManager
     * @param Mailchimp $mailchimp
     */
    public function __construct(EntityManagerInterface $entityManager, Mailchimp $mailchimp)
    {
        parent::__construct($entityManager);
        $this->mailChimp = $mailchimp;
    }

    /**
     * @param string $listId
     * @return JsonResponse
     */
    public function showAll(string $listId): JsonResponse
    {
        /** @var MailChimpList|null $list */
        $list = $this->entityManager->getRepository(MailChimpList::class)->find($listId);
        if ($list === null) {
            return $this->errorResponse(
                ['message' => \sprintf('MailChimpList[%s] not found', $listId)],
                404
            );
        }

        $returnData = [];
        foreach ($list->getMembers() as $listMember) {
            $returnData[] = $listMember->toArray();
        }
        return $this->successfulResponse($returnData);
    }

    /**
     * @param string $listId
     * @param string $subscriberHash
     * @return JsonResponse
     */
    public function show(string $listId, string $subscriberHash): JsonResponse
    {
        /** @var MailChimpList|null $list */
        $list = $this->entityManager->getRepository(MailChimpList::class)->find($listId);
        if ($list === null) {
            return $this->errorResponse(
                ['message' => \sprintf('MailChimpList[%s] not found', $listId)],
                404
            );
        }

        /** @var MailChimpListMember $listMember */
        $listMember = $list->getMembers()->filter(function (MailChimpListMember $member) use ($subscriberHash) {
            return $member->getId() === $subscriberHash;
        })->first();

        if (empty($listMember)) {
            return $this->errorResponse(
                ['message' => \sprintf('MailChimpListMember[%s] not found', $subscriberHash)],
                404
            );
        }

        return $this->successfulResponse($listMember->toArray());
    }

    /**
     * @param Request $request
     * @param string $listId
     * @return JsonResponse
     */
    public function create(Request $request, string $listId): JsonResponse
    {
        /** @var MailChimpList|null $list */
        $list = $this->entityManager->getRepository(MailChimpList::class)->find($listId);
        if ($list === null) {
            return $this->errorResponse(
                ['message' => \sprintf('MailChimpList[%s] not found', $listId)],
                404
            );
        }

        $listMember = new MailChimpListMember($request->all());

        $validator = $this->getValidationFactory()->make(
            $listMember->toMailChimpArray(),
            $listMember->getValidationRules()
        );
        if ($validator->fails()) {
            return $this->errorResponse([
                'message' => 'Invalid data given',
                'errors' => $validator->errors()->toArray()
            ]);
        }

        try {
            $listMember->setList($list);
            $this->saveEntity($listMember);

            $response = $this->mailChimp->post(
                \sprintf('/lists/%s/members', $list->getMailChimpId()),
                $listMember->toMailChimpArray()
            );

            $this->saveEntity($listMember->setMailChimpId($response->get('id')));
        } catch (\Exception $exception) {
            return $this->errorResponse(['message' => $exception->getMessage()]);
        }

        return $this->successfulResponse($listMember->toArray());
    }

    /**
     * @param Request $request
     * @param string $listId
     * @param string $subscriberHash
     * @return JsonResponse
     */
    public function update(Request $request, string $listId, string $subscriberHash): JsonResponse
    {
        /** @var MailChimpList|null $list */
        $list = $this->entityManager->getRepository(MailChimpList::class)->find($listId);
        if ($list === null) {
            return $this->errorResponse(
                ['message' => \sprintf('MailChimpList[%s] not found', $listId)],
                404
            );
        }

        /** @var MailChimpListMember $listMember */
        $listMember = $list->getMembers()->filter(function (MailChimpListMember $member) use ($subscriberHash) {
            return $member->getId() === $subscriberHash;
        })->first();

        if (empty($listMember)) {
            return $this->errorResponse(
                ['message' => \sprintf('MailChimpListMember[%s] not found', $subscriberHash)],
                404
            );
        }

        $listMember->fill($request->all());

        $validator = $this->getValidationFactory()->make(
            $listMember->toMailChimpArray(),
            $listMember->getValidationRules()
        );
        if ($validator->fails()) {
            return $this->errorResponse([
                'message' => 'Invalid data given',
                'errors' => $validator->errors()->toArray()
            ]);
        }

        try {
            $this->saveEntity($listMember);

            $this->mailChimp->patch(
                \sprintf("/lists/%s/members/%s", $list->getMailChimpId(), $listMember->getMailChimpId()),
                $listMember->toMailChimpArray()
            );
        } catch (\Exception $exception) {
            return $this->errorResponse(['message' => $exception->getMessage()]);
        }

        return $this->successfulResponse($listMember->toArray());
    }

    /**
     * @param string $listId
     * @param string $subscriberHash
     * @return JsonResponse
     */
    public function remove(string $listId, string $subscriberHash): JsonResponse
    {
        /** @var MailChimpList|null $list */
        $list = $this->entityManager->getRepository(MailChimpList::class)->find($listId);
        if ($list === null) {
            return $this->errorResponse(
                ['message' => \sprintf('MailChimpList[%s] not found', $listId)],
                404
            );
        }

        /** @var MailChimpListMember $listMember */
        $listMember = $list->getMembers()->filter(function (MailChimpListMember $member) use ($subscriberHash) {
            return $member->getId() === $subscriberHash;
        })->first();

        if (empty($listMember)) {
            return $this->errorResponse(
                ['message' => \sprintf('MailChimpListMember[%s] not found', $subscriberHash)],
                404
            );
        }

        if ($listMember->getStatus() === MailChimpListMember::STATUS_ARCHIVED) {
            return $this->errorResponse(
                ['message' => 'Method Not Allowed'],
                405
            );
        }

        try {
            $listMember->setStatus(MailChimpListMember::STATUS_ARCHIVED);
            $this->saveEntity($listMember);
            $this->mailChimp->delete(
                \sprintf("/lists/%s/members/%s", $list->getMailChimpId(), $listMember->getMailChimpId())
            );
        } catch (\Exception $exception) {
            return $this->errorResponse(['message' => $exception->getMessage()]);
        }

        return $this->successfulResponse([]);
    }

    /**
     * @param string $listId
     * @param string $subscriberHash
     * @return JsonResponse
     */
    public function actionDeletePermanent(string $listId, string $subscriberHash): JsonResponse
    {
        /** @var MailChimpList|null $list */
        $list = $this->entityManager->getRepository(MailChimpList::class)->find($listId);
        if ($list === null) {
            return $this->errorResponse(
                ['message' => \sprintf('MailChimpList[%s] not found', $listId)],
                404
            );
        }

        /** @var MailChimpListMember $listMember */
        $listMember = $list->getMembers()->filter(function (MailChimpListMember $member) use ($subscriberHash) {
            return $member->getId() === $subscriberHash;
        })->first();

        if (empty($listMember)) {
            return $this->errorResponse(
                ['message' => \sprintf('MailChimpListMember[%s] not found', $subscriberHash)],
                404
            );
        }

        try {
            $this->removeEntity($listMember);
            $this->mailChimp->post(\sprintf(
                "/lists/%s/members/%s/actions/delete-permanent",
                $list->getMailChimpId(),
                $listMember->getMailChimpId()
            ));
        } catch (\Exception $exception) {
            return $this->errorResponse(['message' => $exception->getMessage()]);
        }

        return $this->successfulResponse([]);
    }
}
