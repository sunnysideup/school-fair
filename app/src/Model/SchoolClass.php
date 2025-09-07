<?php


namespace Sunnysideup\Schoolfair\Model;

use SilverStripe\ORM\DataList;
use SilverStripe\ORM\DataObject;
use SilverStripe\Security\Member;
use SilverStripe\Security\Security;
use SilverStripe\Versioned\Versioned;
use Sunnysideup\Schoolfair\Extensions\ViewableMemberObjectExtension;
use Sunnysideup\Schoolfair\Interfaces\SecurityGroupProvider;
use Sunnysideup\Schoolfair\Traits\ViewableMemberObjectTrait;

/**
 * SCHOOL CLASS
 */
class SchoolClass extends DataObject implements SecurityGroupProvider
{
    use ViewableMemberObjectTrait;
    private static $extensions = [
        Versioned::class, // Versioning only, no staging
        ViewableMemberObjectExtension::class,
    ];

    public static function get_security_groups(): array
    {
        $groups = [];
        foreach (SchoolClass::get() as $schoolClass) {
            $groups = array_merge($groups, $schoolClass->provideSecurityGroups());
        }
        return $groups;
    }

    public function provideSecurityGroups(): array
    {
        $title = $this->Title;
        $year = $this->YearLevel ?: 'YEAR_NOT_SET';
        return [
            [
                'code' => 'school-class-' . strtolower(preg_replace('/[^a-zA-Z0-9]+/', '-', $title)) . '-' . $year,
                'permissionCode' => 'SCHOOL_CLASS_' . $year,
                'title' => 'Class: ' . $title . ' (Year ' . $year . ')',
                'sort' => ($year * 100) + ord(substr($title, 0, 1)),
                'description' => 'Class representatives of children in ' . $title . ' (Year ' . $year . ')',
                'Admins' => $this->ClassReps(),
                'Members' => $this->SchoolParents(),
            ],
        ];
    }

    private static $singular_name = 'School Class';
    private static $plural_name = 'School Classes';
    private static array $table_name = 'SchoolClass';

    private static array $db = [
        'Title' => 'Varchar(255)',
        'YearLevel' => 'Int',
    ];

    private static $has_one = [
        'LastUpdatedBy' => Member::class,
    ];

    private static array $has_many = [
        'Children' => Child::class,
    ];

    private static array $many_many = [
        'ClassReps' => SchoolParent::class,
    ];

    private static array $summary_fields = [
        'Title' => 'Class',
        'YearLevel' => 'Year',
        'Children.Count' => 'Children',
        'ClassReps.Count' => 'Reps',
    ];

    private static array $searchable_fields = [
        'Title',
        'YearLevel',
        'Children.Name',
    ];

    private static array $indexes = [
        'TitleIdx' => ['type' => 'index', 'columns' => ['Title']],
        'YearIdx' => ['type' => 'index', 'columns' => ['YearLevel']],
    ];

    private static $default_sort = 'YearLevel, Title';

    private static $field_labels = [
        'Title' => 'Class name',
        'YearLevel' => 'Year level',
        'Children' => 'Children',
        'ClassReps' => 'Class representatives',
    ];

    private static $_cache_parent_ids = [];

    public function SchoolParents(): DataList
    {
        if (!isset(self::$_cache_parent_ids[$this->ID])) {
            $parentIDs = [];
            foreach ($this->Children() as $child) {
                $parentIDs = array_merge($parentIDs, $child->Parents()->column('ID'));
            }
            $parentIDs = array_unique($parentIDs);
            self::$_cache_parent_ids[$this->ID] = SchoolParent::get()->filter('ID', $parentIDs);
        }
        return self::$_cache_parent_ids[$this->ID];
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
        return $this->Children()->exists() || $this->ClassReps()->exists();
    }
}
