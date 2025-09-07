<?php


namespace Sunnysideup\Schoolfair\Model;


use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\FieldType\DBDatetime;
use SilverStripe\Security\Member;
use SilverStripe\Security\Security;
use SilverStripe\Versioned\Versioned;
use Sunnysideup\Schoolfair\Extensions\ViewableMemberObjectExtension;
use Sunnysideup\Schoolfair\Traits\ViewableMemberObjectTrait;

/**
 * FAIR
 */
class Fair extends DataObject
{
    use ViewableMemberObjectTrait;
    private static $extensions = [
        Versioned::class, // Versioning only, no staging
        ViewableMemberObjectExtension::class,
    ];


    protected static $_cache_current_fair = null;
    public static function get_current_fair(): ?Fair
    {
        if (self::$_cache_current_fair === null) {
            self::$_cache_current_fair = Fair::get()->filter(['IsCurrentEvent' => 1])->first();
            if (!self::$_cache_current_fair) {
                self::$_cache_current_fair = Fair::get()->sort('StartDate DESC')->first();
            }
        }
        return self::$_cache_current_fair;
    }

    private static $singular_name = 'Fair / Event';
    private static $plural_name = 'Fairs / Events';
    private static array $table_name = 'Fair';

    private static array $db = [
        'Title' => 'Varchar(255)',
        'TimeDescription' => 'Text',
        'StartDate' => 'Date',
        'EndDate' => 'Date',
        'IsCurrentEvent' => 'Boolean',
    ];

    private static $has_one = [
        'LastUpdatedBy' => Member::class,
    ];


    private static array $has_many = [
        'FairEvents' => FairEvent::class,
    ];

    private static $many_many = [
        'FairAdmins' => SchoolParent::class
    ];

    private static array $summary_fields = [
        'Title' => 'Title',
        'StartDate' => 'Starts on',
        'EndDate' => 'Ends on',
        'FairEvents.Count' => 'Events',
    ];

    private static array $searchable_fields = [
        'Title',
        'StartDate',
        'EndDate',
    ];

    private static array $indexes = [
        'DateRange' => [
            'type' => 'index',
            'columns' => ['StartDate', 'EndDate'],
        ],
        'TitleIdx' => ['type' => 'index', 'columns' => ['Title']],
    ];

    private static $default_sort = 'StartDate DESC';

    private static array $casting = [
        'DateRangeNice' => 'Varchar',
        'IsPast' => 'Boolean'
    ];


    private static $field_labels = [
        'Title' => 'Fair name',
        'StartDate' => 'Start date',
        'EndDate' => 'End date',
        'TimeDescription' => 'Date/Time Notes',
        'FairEvents' => 'Events',
    ];

    public function getDateRangeNice(): string
    {
        return trim(($this->StartDate ?: '') . ' — ' . ($this->EndDate ?: ''));
    }

    public function getIsPast(): bool
    {
        $now = DBDatetime::now()->format('Y-m-d');
        return strtotime($this->EndDate) < strtotime($now);
    }

    public function onBeforeWrite(): void
    {
        parent::onBeforeWrite();
        if ($this->IsCurrentEvent) {
            // make sure no other event is current
            $others = Fair::get()->filter(['IsCurrentEvent' => 1, 'ID:not' => $this->ID]);
            foreach ($others as $other) {
                $other->IsCurrentEvent = 0;
                $other->write();
            }
        } else {
            // make sure at least one event is current
            $currentExists = Fair::get()->filter(['IsCurrentEvent' => 1])->exists();
            if (!$currentExists) {
                $this->IsCurrentEvent = 1;
            }
        }
    }


    public function canCreate($member = null, $context = []): bool
    {
        $member = $member ?: Security::getCurrentUser();
        if ($member->IsFairAdmin()) {
            return true;
        }
        return parent::canCreate($member, $context);
    }

    public function canView($member = null)
    {
        $member = $member ?: Security::getCurrentUser();
        if (!$member) {
            return false;
        }
        return true;
    }

    public function canEdit($member = null)
    {
        $member = $member ?: Security::getCurrentUser();
        if ($member->IsFairAdmin()) {
            return true;
        }
        return parent::canEdit($member);
    }

    public function HasDependentRecords(): bool
    {
        return $this->FairEvents()->exists();
    }
}
