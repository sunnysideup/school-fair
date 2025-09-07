<?php

namespace Sunnysideup\Schoolfair\Traits;

use SilverStripe\Control\Controller;
use SilverStripe\ORM\DataList;
use SilverStripe\ORM\UnsavedRelationList;
use SilverStripe\View\Parsers\URLSegmentFilter;

trait ViewableMemberObjectTrait
{
    public function Link(?string $action = null): string
    {
        if ($this->hasMethod('MyLink')) {
            return $this->MyLink($action);
        }
        if ($this->MyViewController()) {
            $controller = $this->MyViewController();
        }


        if (! $action) {
            $action = 'view' . $this->MyHolderSlug();
        }
        $title = $this->getTitle();
        $urlTitle = $this->generateURLSegment((string) $title);
        $action = $action . '/' . $this->ID . '/' . $urlTitle;
        $link = rtrim($controller->Link($action), '/');
        str_replace('stage=Stage', '', $link);
        return rtrim($controller->Link($action), '?');
    }

    public function DraftLink(?string $action = null): string
    {
        return Controller::join_links(
            $this->Link($action),
            '?' . http_build_query(['stage' => 'Stage'])
        );
    }

    public function MyViewerGroups(): DataList|UnsavedRelationList
    {
        return $this->DefaultFolder()->ViewerGroups();
    }

    public function generateURLSegment(string $title): string
    {
        if (! $title) {
            $title = 'error-no-title';
        }
        $filter = URLSegmentFilter::create();
        return $filter->filter($title);
    }

    public function canDelete($member = null)
    {
        return $this->canArchive($member);
    }

    public function canArchive($member = null)
    {
        if ($this->HasDependentRecords()) {
            return false;
        }
        return $this->canEdit($member);
    }
}
