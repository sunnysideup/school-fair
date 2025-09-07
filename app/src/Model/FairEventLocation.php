<?php


namespace Sunnysideup\Schoolfair\Model;


use SilverStripe\ORM\DataObject;
use SilverStripe\Security\Member;
use SilverStripe\Security\Security;
use SilverStripe\Versioned\Versioned;
use Sunnysideup\Schoolfair\Extensions\ViewableMemberObjectExtension;
use Sunnysideup\Schoolfair\Traits\ViewableMemberObjectTrait;

/**
 * CHILD
 */
class FairEventLocation extends DataObject
{
    use ViewableMemberObjectTrait;
    private static $extensions = [
        Versioned::class, // Versioning only, no staging
        ViewableMemberObjectExtension::class,
    ];

    protected static $_cache_current_location = null;
    public static function get_current_location(): ?FairEventLocation
    {
        if (self::$_cache_current_location === null) {
            self::$_cache_current_location = FairEventLocation::get()->filter(['DefaultLocation' => 1])->first();
            if (!self::$_cache_current_location) {
                self::$_cache_current_location = FairEventLocation::get()->first();
            }
        }
        return self::$_cache_current_location;
    }

    private static $singular_name = 'Location';
    private static $plural_name = 'Locations';
    private static array $table_name = 'FairEventLocation';

    private static array $db = [
        'Title' => 'Varchar(255)',
        'DefaultLocation' => 'Boolean',
    ];

    private static $has_one = [
        'LastUpdatedBy' => Member::class,
    ];

    private static array $belongs_many_many = [
        'FairEvents' => FairEvent::class,
    ];

    private static array $summary_fields = [
        'Title' => 'Location',
    ];

    private static array $searchable_fields = [
        'Title',
        'FairEvents.Title',
    ];

    private static array $indexes = [
        'Title' => true,
    ];

    private static $field_labels = [
        'Title' => 'Location',
    ];

    private static $default_sort = 'Title';

    public function onBeforeWrite()
    {
        parent::onBeforeWrite();
        if ($this->DefaultLocation) {
            // make sure no other event is current
            $others = FairEventLocation::get()->filter(['DefaultLocation' => 1, 'ID:not' => $this->ID]);
            foreach ($others as $other) {
                $other->DefaultLocation = 0;
                $other->write();
            }
        } else {
            // make sure at least one event is current
            $currentExists = FairEventLocation::get()->filter(['DefaultLocation' => 1])->exists();
            if (!$currentExists) {
                $this->DefaultLocation = 1;
            }
        }
    }


    public function canCreate($member = null, $context = []): bool
    {
        $member = $member ?: Security::getCurrentUser();
        if (!$member) {
            return false;
        }
        if ($member->IsFairAdmin()) {
            return true;
        }
        if ($member->IsGroupAdmin()) {
            return true;
        }
        if ($member->IsFairEventOrganiser()) {
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
        return $this->canCreate($member);
    }


    public function HasDependentRecords(): bool
    {
        return $this->FairEvents()->exists();
    }
}
