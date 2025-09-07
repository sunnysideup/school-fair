<?php


namespace Sunnysideup\Schoolfair\Model;



use SilverStripe\ORM\DataObject;
use SilverStripe\Security\Member;
use SilverStripe\Security\Security;
use SilverStripe\Versioned\Versioned;
use Sunnysideup\Schoolfair\Extensions\ViewableMemberObjectExtension;
use Sunnysideup\Schoolfair\Traits\ViewableMemberObjectTrait;

/**
 * ROSTER ENTRY
 */
class RosterEntry extends DataObject
{
    use ViewableMemberObjectTrait;
    private static $extensions = [
        Versioned::class, // Versioning only, no staging
        ViewableMemberObjectExtension::class,
    ];

    private static $singular_name = 'Roster Requirement';
    private static $plural_name = 'Roster Requirements';

    private static array $table_name = 'RosterEntry';

    private static array $db = [
        'Title' => 'Varchar(255)',
        'Description' => 'Text',
        'StartTime' => 'Datetime',
        'EndTime' => 'Datetime',
        'RosterPlaces' => 'Int'
    ];

    private static array $has_one = [
        'FairEvent' => FairEvent::class,
        'MainContact' => SchoolParent::class,
        'Location' => FairEventLocation::class,
        'LastUpdatedBy' => Member::class,
    ];

    private static array $belongs_many_many = [
        'Parents' => SchoolParent::class,
    ];

    private static array $summary_fields = [
        'Title' => 'Job',
        'FairEvent.Title' => 'Event',
        'StartTime' => 'Start',
        'EndTime' => 'End',
        'Location.Title' => 'Location',
        'RosterPlaces' => 'Parents Needed',
    ];

    private static array $searchable_fields = [
        'Title',
        'Description',
        'Date',
        'Location',
        'FairEvent.Title',
    ];

    private static array $indexes = [
        'DateTimeIdx' => ['type' => 'index', 'columns' => ['StartTime', 'EndTime']],
        'LocationIdx' => ['type' => 'index', 'columns' => ['Location']],
        'FairEventID' => true,
        'MainContactID' => true,
    ];

    private static array $casting = [
        'NumberOfParentsStillNeeded' => 'Int',
    ];

    private static $default_sort = 'StartTime ASC';

    public function getNumberOfParentsStillNeeded(): int
    {
        return $this->RosterPlaces - $this->Parents->count();
    }

    public function fieldLabels($includerelations = true): array
    {
        $labels = parent::fieldLabels($includerelations);
        $labels['Title'] = 'Roster title';
        $labels['RosterPlaces'] = 'Parents needed';
        $labels['MainContactID'] = 'Main contact';
        return $labels;
    }

    public function populateDefaults()
    {
        $this->StartTime = $this->FairEvent->StartTime ?: null;
        $this->EndTime = $this->FairEvent->EndTime ?: null;
        $this->RosterPlaces = 1;
        return parent::populateDefaults();
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
        return $this->Parents()->exists();
    }
}
