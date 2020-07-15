<?php
declare(strict_types=1);

namespace TestCases\MailChimp;

use App\Database\Entities\MailChimp\MailChimpList;
use App\Database\Entities\MailChimp\MailChimpListMember;
use Tests\App\TestCases\WithDatabaseTestCase;

abstract class ListMemberTestCase extends WithDatabaseTestCase
{
    /**
     * @var array
     */
    protected static $listData = [
        'name' => 'New list',
        'permission_reminder' => 'You signed up for updates on Greeks economy.',
        'email_type_option' => false,
        'contact' => [
            'company' => 'Doe Ltd.',
            'address1' => 'DoeStreet 1',
            'address2' => '',
            'city' => 'Doesy',
            'state' => 'Doedoe',
            'zip' => '1672-12',
            'country' => 'US',
            'phone' => '55533344412'
        ],
        'campaign_defaults' => [
            'from_name' => 'John Doe',
            'from_email' => 'john@doe.com',
            'subject' => 'My new campaign!',
            'language' => 'US'
        ],
        'visibility' => 'prv',
        'use_archive_bar' => false,
        'notify_on_subscribe' => 'notifytest@mytestst.com',
        'notify_on_unsubscribe' => 'notifytest@mytestst.com'
    ];

    /**
     * @return array
     */
    protected static function generateMemberData(): array
    {
        return [
            'email_address' => \rand(100000, 999999) . 'michaeltest@emailtest1.com',
            'email_type' => MailChimpListMember::EMAIL_TYPE_HTML,
            'status' => MailChimpListMember::STATUS_SUBSCRIBED,
            'merge_fields' => [
                'FNAME' =>  'First Name',
                'LNAME' =>  'Last Name',
                'ADDRESS' => [
                    'addr1' =>  'Street name',
                    'addr2' =>  '',
                    'city' =>  'City',
                    'state' =>  'State',
                    'zip' =>  'ZIP',
                    'country' =>  'US'
                ],
                'PHONE' => '88005553535',
                'BIRTHDAY' => '12/12'
            ],
            'interests' => [],
            'language' => 'ru',
            'vip' => false,
            'location' => [
                'latitude' => '-21.8052',
                'longitude' => '-49.0898'
            ],
            'marketing_permissions' => [
                ['marketing_permission_id' => 'id123', 'enabled' => true]
            ],
            'ip_signup' => '49.57.48.99',
            'timestamp_signup' => '2020-07-14T18:08:13+00:00',
            'ip_opt' => '49.114.199.119',
            'timestamp_opt' => '2020-07-14T17:53:54+00:00',
            'tags' => ['test tag 1', 'test tag 2'],
        ];
    }

    /**
     * Create MailChimp list into database.
     *
     * @param array $data
     *
     * @return MailChimpList
     */
    protected function createList(array $data): MailChimpList
    {
        $list = new MailChimpList($data);

        $this->entityManager->persist($list);
        $this->entityManager->flush();

        return $list;
    }

    /**
     * @param MailChimpList $list
     * @param array $data
     * @return MailChimpListMember
     */
    protected function createListMember(MailChimpList $list, array $data): MailChimpListMember
    {
        $listMember = new MailChimpListMember($data);
        $listMember->setList($list);

        $this->entityManager->persist($listMember);
        $this->entityManager->flush();

        $this->entityManager->refresh($list);
        return $listMember;
    }
}
