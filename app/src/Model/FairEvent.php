<?php


namespace Sunnysideup\Schoolfair\Model;

use SilverStripe\ORM\DataList;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\FieldType\DBDatetime;
use SilverStripe\Security\Member;
use SilverStripe\Security\Security;
use SilverStripe\Versioned\Versioned;
use Sunnysideup\Schoolfair\Extensions\ViewableMemberObjectExtension;
use Sunnysideup\Schoolfair\Traits\ViewableMemberObjectTrait;

/**
 * FAIR EVENT
 */
class FairEvent extends DataObject
{
    use ViewableMemberObjectTrait;
    private static $extensions = [
        Versioned::class, // Versioning only, no staging
        ViewableMemberObjectExtension::class,
    ];

    private static $singular_name = 'Single Event';
    private static $plural_name = 'Single Events';
    private static array $table_name = 'FairEvent';

    private static array $db = [
        'Title' => 'Varchar(255)',
        'Description' => 'Text',
        'StartTime' => 'Datetime',
        'EndTime' => 'Datetime',
        'TimeDescription' => 'Text',
    ];

    private static array $has_one = [
        'ParentGroup' => SchoolParentGroup::class,
        'Fair' => Fair::class,
        'LastUpdatedBy' => Member::class,
    ];

    private static array $has_many = [
        'RosterEntries' => RosterEntry::class,
    ];

    private static $many_many = [
        'Locations' => FairEventLocation::class,
        'EventOrganisers' => SchoolParent::class,
    ];

    private static array $summary_fields = [
        'Title' => 'Title',
        'Fair.Title' => 'Fair',
        'ParentGroup.Title' => 'Group',
        'StartTime' => 'Starts',
        'EndTime' => 'Ends',
        'Location' => 'Location',
        'RosterEntries.Count' => 'Roster entries',
    ];

    private static array $searchable_fields = [
        'Title',
        'Description',
        'Location.Title',
        'Fair.Title',
        'ParentGroup.Title',
    ];

    private static array $indexes = [
        'TitleIdx' => ['type' => 'index', 'columns' => ['Title']],

    ];

    private static array $casting = [
        'WhenNice' => 'Varchar',
    ];

    private static $default_sort = 'StartTime ASC';

    private static array $field_labels = [
        'Title' => 'Event title',
        'Description' => 'Description',
        'DateTime' => 'When',
        'Location' => 'Location',
        'ParentGroupID' => 'Parent group',
        'FairID' => 'Fair',
        'TimeDescription' => 'Date/Time Notes',
        'RosterEntries' => 'People needed',
    ];

    public function getWhenNice(): string
    {
        /** @var DBDatetime $dt */
        $startTime = $this->obj('StartTime');
        $endTime = $this->obj('EndTime');
        return trim($startTime?->Nice() ?? '' . ' — ' . $endTime?->Nice() ?? '');
    }

    public function populateDefaults()
    {
        $this->FairID = Fair::get_current_fair()?->ID ?? 0;
        if (!$this->StartTime) {
            $this->StartTime = Fair::get_current_fair()?->StartDate . ' 09:00:00' ?? null;
        }
        if (!$this->EndTime) {
            $this->EndTime = Fair::get_current_fair()?->EndDate . ' 17:00:00' ?? null;
        }
        return parent::populateDefaults();
    }

    public function FairAdmins(): ?DataList
    {
        return $this->Fair()?->FairAdmins() ?: null;
    }

    public function GroupAdmins(): ?DataList
    {
        return $this->ParentGroup()?->Admins() ?: null;
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
        if (!$member) {
            return false;
        }
        if ($this->FairAdmins()?->filter('ID', $member->ID)->exists()) {
            return true;
        }
        if ($this->EventOrganisers()?->filter('ID', $member->ID)->exists()) {
            return true;
        }
        if ($this->GroupAdmins()?->filter('ID', $member->ID)->exists()) {
            return true;
        }

        return parent::canEdit($member);
    }

    public function HasDependentRecords(): bool
    {
        return $this->RosterEntries()->exists() || $this->EventOrganisers()->exists() || $this->ParentGroup()->exists();
    }
}
