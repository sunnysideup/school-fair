<?php

namespace Sunnysideup\Schoolfair\Api;


use SilverStripe\Core\ClassInfo;
use SilverStripe\ORM\DB;
use SilverStripe\Security\Member;
use Sunnysideup\PermissionProvider\Api\PermissionProviderFactory;
use Sunnysideup\Schoolfair\Interfaces\SecurityGroupProvider;

class CreateGroupsAndMembers
{
    protected static array $cache = [];

    protected bool $verbose = false;

    public function __construct(?bool $verbose = false)
    {
        $this->verbose = $verbose;
    }


    protected $_cached_completed = [];
    public function run()
    {
        foreach (ClassInfo::implementorsOf(SecurityGroupProvider::class) as $class) {
            /** @var SecurityGroupProvider $obj */
            $objects = $class::get();
            foreach ($objects as $obj) {
                $groups = $obj->getSecurityGroups();
                foreach ($groups as $group) {
                    if ($this->groupArrayTester($group)) {
                        if (isset(self::$_cached_completed[$group['code']])) {
                            continue;
                        }
                        if ($this->verbose) {
                            DB::alteration_message('Creating group ' . $group->getTitle(), 'created');


                            $code = $group['code'];
                            $permissionCode = $group['permissionCode'];
                            $permissionArray = [
                                $permissionCode,
                            ];
                            $title = $group['title'];
                            $roleTitle = $this->roleTitleMaker($title);
                            $group = PermissionProviderFactory::inst()
                                ->setGroupName($title)
                                ->setCode($code)
                                ->setPermissionCode($permissionCode)
                                ->setRoleTitle($roleTitle)
                                ->setPermissionArray($permissionArray)
                                ->CreateGroup();
                            // ->AddMemberToGroup($member);
                            $group->Sort = $group['sort'] ?? 0;
                            $group->write();
                            if ($this->verbose) {
                                DB::alteration_message($title . ' group created', 'created');
                            }
                            $group->Members()->removeAll();
                            foreach ($group['members'] as $member) {
                                if ($member instanceof Member) {
                                    $group->Members()->add($member);
                                    if ($this->verbose) {
                                        DB::alteration_message('... adding member ' . $member->getTitle(), 'created');
                                    }
                                }
                            }
                            $group->Admins()->removeAll();
                            foreach ($group['admins'] as $member) {
                                if ($member instanceof Member) {
                                    $group->Admins()->add($member);
                                    if ($this->verbose) {
                                        DB::alteration_message('... adding admin ' . $member->getTitle(), 'created');
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
    }

    protected function roleTitleMaker(string $string): string
    {
        return $string . ' Privileges';
    }

    protected function groupArrayTester(array $group)
    {
        if (!isset($group['code']) || !is_string($group['code']) || trim($group['code']) === '') {
            throw new \InvalidArgumentException('Group array must have a non-empty string "code" key.');
        }
        if (!isset($group['permissionCode']) || !is_string($group['permissionCode']) || trim($group['permissionCode']) === '') {
            throw new \InvalidArgumentException('Group array must have a non-empty string "permissionCode" key.');
        }
        if (!isset($group['title']) || !is_string($group['title']) || trim($group['title']) === '') {
            throw new \InvalidArgumentException('Group array must have a non-empty string "title" key.');
        }
        // Optionally check for 'sort' key
        if (isset($group['sort']) && !is_int($group['sort'])) {
            throw new \InvalidArgumentException('If provided, "sort" key must be an integer.');
        }
        if (!isset($group['admins']) || !is_array($group['admins'])) {
            throw new \InvalidArgumentException('Group array must have an "admins" key with an array of Members.');
        }
        if (!isset($group['members']) || !is_array($group['members'])) {
            throw new \InvalidArgumentException('Group array must have an "members" key with an array of Members.');
        }
    }
}
