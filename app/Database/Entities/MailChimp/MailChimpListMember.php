<?php
declare(strict_types=1);

namespace App\Database\Entities\MailChimp;

use Doctrine\ORM\Mapping as ORM;
use EoneoPay\Utils\Str;
use Illuminate\Validation\Rule;

/**
 * @ORM\Entity()
 * @ORM\Table(name="mail_chimp_list_member")
 */
final class MailChimpListMember extends MailChimpEntity
{
    const STATUS_SUBSCRIBED = 'subscribed';
    const STATUS_UNSUBSCRIBED = 'unsubscribed';
    const STATUS_CLEANED = 'cleaned';
    const STATUS_PENDING = 'pending';
    const STATUS_TRANSACTIONAL = 'transactional';
    const STATUS_ARCHIVED = 'archived';

    const EMAIL_TYPE_HTML = 'html';
    const EMAIL_TYPE_TEXT = 'text';

    /**
     * @ORM\Id
     * @ORM\Column(name="id", type="guid")
     * @ORM\GeneratedValue(strategy="UUID")
     *
     * @var string
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="MailChimpList", inversedBy="members")
     * @ORM\JoinColumn(name="list_id", referencedColumnName="id", nullable=false)
     *
     * @var MailChimpList
     */
    private $list;

    /**
     * @ORM\Column(
     *     name="mail_chimp_id",
     *     type="string",
     *     nullable=true,
     *     options={"comment":"Member's ID from mailchimp"}
     * )
     *
     * @var string|null
     */
    private $mailChimpId;

    /**
     * @ORM\Column(name="email_address", type="string", nullable=false)
     *
     * @var string
     */
    private $emailAddress;

    /**
     * @ORM\Column(name="email_type", type="string", nullable=true)
     *
     * @var string|null
     */
    private $emailType;

    /**
     * @ORM\Column(name="status", type="string", nullable=false)
     *
     * @var string
     */
    private $status;

    /**
     * @ORM\Column(name="merge_fields", type="array", nullable=true)
     *
     * @var array|null
     */
    private $mergeFields;

    /**
     * @ORM\Column(name="interests", type="array", nullable=true)
     *
     * @var array|null
     */
    private $interests;

    /**
     * @ORM\Column(name="language", type="string", nullable=true)
     *
     * @var string|null
     */
    private $language;

    /**
     * @ORM\Column(name="vip", type="boolean", nullable=true)
     *
     * @var bool|null
     */
    private $vip;

    /**
     * @ORM\Column(name="location", type="array", nullable=true)
     *
     * @var array|null
     */
    private $location;

    /**
     * @ORM\Column(name="marketing_permissions", type="array", nullable=true)
     *
     * @var array|null
     */
    private $marketingPermissions;

    /**
     * @ORM\Column(name="ip_signup", type="string", nullable=true)
     *
     * @var string|null
     */
    private $ipSignup;

    /**
     * @ORM\Column(name="timestamp_signup", type="string", nullable=true)
     *
     * @var string|null
     */
    private $timestampSignup;

    /**
     * @ORM\Column(name="ip_opt", type="string", nullable=true)
     *
     * @var string|null
     */
    private $ipOpt;

    /**
     * @ORM\Column(name="timestamp_opt", type="string", nullable=true)
     *
     * @var string|null
     */
    private $timestampOpt;

    /**
     * @ORM\Column(name="tags", type="array", nullable=true)
     *
     * @var array|null
     */
    private $tags;

    /**
     * @inheritDoc
     */
    public function getValidationRules(): array
    {
        return [
            'email_address' => 'required|email',
            'email_type' => [
                'nullable',
                'string',
                Rule::in([
                    self::EMAIL_TYPE_HTML,
                    self::EMAIL_TYPE_TEXT,
                ])
            ],
            'status' => [
                'required',
                'string',
                Rule::in([
                    self::STATUS_SUBSCRIBED,
                    self::STATUS_UNSUBSCRIBED,
                    self::STATUS_CLEANED,
                    self::STATUS_PENDING,
                    self::STATUS_TRANSACTIONAL,
                ])
            ],
            'merge_fields.FNAME' => 'nullable|string',
            'merge_fields.LNAME' => 'nullable|string',
            'merge_fields.ADDRESS.addr1' => 'nullable|string',
            'merge_fields.ADDRESS.addr2' => 'nullable|string',
            'merge_fields.ADDRESS.city' => 'nullable|string',
            'merge_fields.ADDRESS.state' => 'nullable|string',
            'merge_fields.ADDRESS.zip' => 'nullable|string',
            'merge_fields.ADDRESS.country' => 'nullable|string',
            'merge_fields.PHONE' => 'nullable|string',
            'merge_fields.BIRTHDAY' => [
                'nullable',
                'string',
                'regex:#^\d{2}/\d{2}$#'
            ],
            'interests' => 'nullable|array',
            'language' => [
                'nullable',
                'string',
                'regex:/^(\w{2}|\w{2}_\w{2})$/'
            ],
            'vip' => 'nullable|boolean',
            'location.latitude' => 'nullable|numeric',
            'location.longitude' => 'nullable|numeric',
            'marketing_permissions' => 'nullable|array',
            'marketing_permissions.*' => 'array',
            'marketing_permissions.*.marketing_permission_id' => 'required|filled|string',
            'marketing_permissions.*.enabled' => 'nullable|boolean',
            'ip_signup' => 'nullable|ip',
            'timestamp_signup' => 'nullable|date',
            'ip_opt' => 'nullable|ip',
            'timestamp_opt' => 'nullable|date',
            'tags' => 'nullable|array',
            'tags.*' => 'string|filled',
        ];
    }

    /**
     * Get array representation of entity.
     *
     * @return array
     */
    public function toArray(): array
    {
        $array = [];
        $str = new Str();

        foreach (\get_object_vars($this) as $property => $value) {
            $array[$str->snake($property)] = $value;
        }

        return $array;
    }

    public function toMailChimpArray(): array
    {
        $jsonTypeObjectFields = [ // these fields encodes as 'array' when empty.
            'merge_fields',
            'interests',
            'location'
        ];

        $array = [];

        foreach ($this->toArray() as $property => $value) {
            if ($value === null) {
                continue;
            }
            if (in_array($property, $jsonTypeObjectFields)) {
                continue;
            }

            $array[$property] = $value;
        }

        return $array;
    }

    /**
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * @return string|null
     */
    public function getMailChimpId(): ?string
    {
        return $this->mailChimpId;
    }

    /**
     * @return string
     */
    public function getStatus(): string
    {
        return $this->status;
    }

    /**
     * @param MailChimpList $list
     * @return MailChimpListMember
     */
    public function setList(MailChimpList $list): MailChimpListMember
    {
        $this->list = $list;
        return $this;
    }

    /**
     * @param string|null $mailChimpId
     * @return MailChimpListMember
     */
    public function setMailChimpId(?string $mailChimpId): MailChimpListMember
    {
        $this->mailChimpId = $mailChimpId;
        return $this;
    }

    /**
     * @param string $emailAddress
     * @return MailChimpListMember
     */
    public function setEmailAddress(string $emailAddress): MailChimpListMember
    {
        $this->emailAddress = $emailAddress;
        return $this;
    }

    /**
     * @param string|null $emailType
     * @return MailChimpListMember
     */
    public function setEmailType(?string $emailType): MailChimpListMember
    {
        $this->emailType = $emailType;
        return $this;
    }

    /**
     * @param string $status
     * @return MailChimpListMember
     */
    public function setStatus(string $status): MailChimpListMember
    {
        $this->status = $status;
        return $this;
    }

    /**
     * @param array|null $mergeFields
     * @return MailChimpListMember
     */
    public function setMergeFields(?array $mergeFields): MailChimpListMember
    {
        $this->mergeFields = $mergeFields;
        return $this;
    }

    /**
     * @param array|null $interests
     * @return MailChimpListMember
     */
    public function setInterests(?array $interests): MailChimpListMember
    {
        $this->interests = $interests;
        return $this;
    }

    /**
     * @param string|null $language
     * @return MailChimpListMember
     */
    public function setLanguage(?string $language): MailChimpListMember
    {
        $this->language = $language;
        return $this;
    }

    /**
     * @param bool|null $vip
     * @return MailChimpListMember
     */
    public function setVip(?bool $vip): MailChimpListMember
    {
        $this->vip = $vip;
        return $this;
    }

    /**
     * @param array|null $location
     * @return MailChimpListMember
     */
    public function setLocation(?array $location): MailChimpListMember
    {
        $this->location = $location;
        return $this;
    }

    /**
     * @param array|null $marketingPermissions
     * @return MailChimpListMember
     */
    public function setMarketingPermissions(?array $marketingPermissions): MailChimpListMember
    {
        $this->marketingPermissions = $marketingPermissions;
        return $this;
    }

    /**
     * @param string|null $ipSignup
     * @return MailChimpListMember
     */
    public function setIpSignup(?string $ipSignup): MailChimpListMember
    {
        $this->ipSignup = $ipSignup;
        return $this;
    }

    /**
     * @param string|null $timestampSignup
     * @return MailChimpListMember
     */
    public function setTimestampSignup(?string $timestampSignup): MailChimpListMember
    {
        $this->timestampSignup = $timestampSignup;
        return $this;
    }

    /**
     * @param string|null $ipOpt
     * @return MailChimpListMember
     */
    public function setIpOpt(?string $ipOpt): MailChimpListMember
    {
        $this->ipOpt = $ipOpt;
        return $this;
    }

    /**
     * @param string|null $timestampOpt
     * @return MailChimpListMember
     */
    public function setTimestampOpt(?string $timestampOpt): MailChimpListMember
    {
        $this->timestampOpt = $timestampOpt;
        return $this;
    }

    /**
     * @param array|null $tags
     * @return MailChimpListMember
     */
    public function setTags(?array $tags): MailChimpListMember
    {
        $this->tags = $tags;
        return $this;
    }
}
