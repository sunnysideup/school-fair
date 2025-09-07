<?php


namespace Sunnysideup\Schoolfair\Model;


use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\FieldType\DBDatetime;
use SilverStripe\Security\Member;
use SilverStripe\Security\Group;
use SilverStripe\Security\Security;
use SilverStripe\Versioned\Versioned;
use Sunnysideup\Schoolfair\Extensions\ViewableMemberObjectExtension;
use Sunnysideup\Schoolfair\Traits\ViewableMemberObjectTrait;

/**
 * PARENT GROUP (extends Group)
 */
class SchoolParentGroup extends Group
{
    use ViewableMemberObjectTrait;
    private static $extensions = [
        Versioned::class, // Versioning only, no staging
        ViewableMemberObjectExtension::class,
    ];

    private static array $table_name = 'SchoolParentGroup';

    private static array $db = [
        'Description' => 'Text',
        // Group already has Title
    ];

    private static $has_one = [
        'LastUpdatedBy' => Member::class,
    ];

    private static $has_many = [
        'FairEvents' => FairEvent::class,
    ];

    private static array $many_many = [
        'Admins' => SchoolParent::class,
    ];



    private static array $summary_fields = [
        'Title' => 'Group',
        'Members.Count' => 'Members',
        'Admins.Count' => 'Admins',
    ];

    private static array $searchable_fields = [
        'Title',
        'Description',
        'Members.FirstName',
        'Members.Surname',
    ];

    private static array $indexes = [
        'TitleIdx' => ['type' => 'index', 'columns' => ['Title']],
    ];

    private static array $casting = [
        'Title' => 'Varchar',
    ];

    private static $field_labels = [
        'Title' => 'Group name',
        'Description' => 'Description',
        'Members' => 'Parent members',
        'Admins' => 'Group admins',
    ];



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
        return $this->Members()->exists()
            || $this->FairEvents()->exists()
            || $this->Admins()->exists();
    }
}
