<?php

namespace Sunnysideup\Schoolfair\Model;

use SilverStripe\ORM\DataList;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\FieldType\DBDatetime;
use SilverStripe\Security\Member;
use SilverStripe\Security\Group;
use SilverStripe\Security\Security;
use SilverStripe\Versioned\Versioned;
use Sunnysideup\Schoolfair\Extensions\ViewableMemberObjectExtension;
use Sunnysideup\Schoolfair\Traits\ViewableMemberObjectTrait;

/**
 * CHILD
 */
class Child extends DataObject
{
    use ViewableMemberObjectTrait;
    private static $extensions = [
        Versioned::class, // Versioning only, no staging
        ViewableMemberObjectExtension::class,
    ];

    private static $singular_name = 'Child';
    private static $plural_name = 'Children';
    private static array $table_name = 'Child';

    private static array $db = [
        'Title' => 'Varchar(255)',
    ];

    private static array $has_one = [
        'SchoolClass' => SchoolClass::class,
        'LastUpdatedBy' => Member::class,
    ];

    private static array $has_many = [
        // Linked via Parent->Children()
    ];

    private static array $belongs_many_many = [
        'Parents' => SchoolParent::class,
    ];

    private static array $summary_fields = [
        'Title' => 'Name',
        'SchoolClass.Title' => 'Class',
    ];

    private static array $searchable_fields = [
        'Name',
        'SchoolClass.Title',
    ];

    private static array $indexes = [
        'Title' => true,
    ];

    private static $default_sort = 'Title';

    private static $field_labels = [
        'Title' => 'Name',
        'SchoolClassID' => 'Class',
        'Parents' => 'Parents / Caregivers'
    ];

    public function ClassReps(): ?DataList
    {
        if ($this->SchoolClassID) {
            return $this->SchoolClass()?->ClassReps();
        }
        return null;
    }

    public function canCreate($member = null, $context = []): bool
    {
        $member = $member ?: Security::getCurrentUser();
        if (!$member) {
            return false;
        }
        if ($member->IsClassRep()) {
            return true;
        }
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
        if (!$member) {
            return false;
        }
        if ($this->Parents()?->filter('ID', $member->ID)->exists()) {
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
        return false;
    }

    public function RestrictedLookups(Member $member)
    {
        $list = [
            'SchoolClassID' => SchoolClass::class,
        ];
        if ($member->IsParent()) {
            $list['SchoolClassID'] = SchoolParent::class;
        }
        if ($member->IsClassRep()) {
            $list['SchoolClassID'] = $member->SchoolClasses();
        }
        return $list;
    }
}
