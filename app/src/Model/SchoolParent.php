<?php


namespace Sunnysideup\Schoolfair\Model;

use SilverStripe\ORM\DataList;
use SilverStripe\Security\Member;
use SilverStripe\Security\Security;
use SilverStripe\Versioned\Versioned;
use Sunnysideup\Schoolfair\Extensions\ViewableMemberObjectExtension;
use Sunnysideup\Schoolfair\Traits\ViewableMemberObjectTrait;

/**
 * PARENT (extends Member)
 * Note: Member already has FirstName, Surname, Email. We add Phone.
 */
class SchoolParent extends Member
{
    use ViewableMemberObjectTrait;
    private static $extensions = [
        Versioned::class, // Versioning only, no staging
        ViewableMemberObjectExtension::class,
    ];

    private static $singular_name = 'Parent';
    private static $plural_name = 'Parents';
    private static array $table_name = 'SchoolParent';

    private static array $db = [
        'Phone' => 'Varchar(64)',
    ];

    private static array $has_many = [
        'Children' => Child::class,
        // 'ParentFairs' unclear in source; typically many_many to Fair via participation.
    ];

    private static $has_one = [
        'LastUpdatedBy' => Member::class,
    ];
    private static array $many_many = [
        'RosterEntries' => RosterEntry::class,
    ];

    private static array $belongs_many_many = [
        'MemberGroups' => SchoolParentGroup::class . '.Members',
        'AdminGroups' => SchoolParentGroup::class . '.Admins',
        'ClassRepFor' => SchoolClass::class . '.ClassReps',
        'Fairs' => Fair::class . '.FairAdmins',
        'FairEvents' => FairEvent::class . '.EventOrganisers',
    ];

    private static array $summary_fields = [
        'FullName' => 'Parent',
        'Email' => 'Email',
        'Phone' => 'Phone',
        'Children.Count' => 'Children',
    ];

    private static array $searchable_fields = [
        'FirstName',
        'Surname',
        'Email',
        'Phone',
        'Children.Name',
    ];

    private static $default_sort = 'Surname, FirstName';


    private static array $casting = [
        'FullName' => 'Varchar',
    ];

    public function getFullName(): string
    {
        $first = trim((string) $this->FirstName);
        $last = trim((string) $this->Surname);
        return trim($first . ' ' . $last);
    }

    private static $field_labels = [
        'FirstName' => 'First name',
        'Surname' => 'Last name',
        'Email' => 'Email',
        'Phone' => 'Phone',
        'Children' => 'Children',
    ];

    public function IsClassRep(): bool
    {
        return $this->ClassRepFor()->exists();
    }
    public function IsFairAdmin(): bool
    {
        return $this->Fairs()->exists();
    }
    public function IsFairEventOrganiser(): bool
    {
        return $this->FairEvents()->exists();
    }
    public function IsGroupAdmin(): bool
    {
        return $this->AdminGroups()->exists();
    }
    public function IsGroupMember(): bool
    {
        return $this->MemberGroups()->exists();
    }

    public function ClassReps(): ?DataList
    {
        $ids = [];
        if ($this->Children()->exists()) {
            foreach ($this->Children() as $child) {
                foreach ($child->ClassReps()->columnUnique() as $id) {
                    $ids[$id] = $id;
                }
            }
        }
        if (!empty($ids)) {
            return Member::get()->filter('ID', array_values($ids));
        }
        return null;
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
        if ($member->IsClassRep()) {
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
        if ($member->ID === $this->ID) {
            return true;
        }
        if ($this->ClassReps()?->filter('ID', $member->ID)->exists()) {
            return true;
        }
        if ($member->IsFairAdmin()) {
            return true;
        }
        return parent::canEdit($member);
    }

    public function HasDependentRecords(): bool
    {
        return $this->Children()->exists()
            || $this->RosterEntries()->exists()
            || $this->ClassRepFor()->exists()
            || $this->Fairs()->exists()
            || $this->FairEvents()->exists()
            || $this->AdminGroups()->exists();
    }
}
