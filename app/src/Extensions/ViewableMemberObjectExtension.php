<?php


namespace Sunnysideup\Schoolfair\Extensions;

use SilverStripe\Core\Extension;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\LiteralField;
use SilverStripe\Forms\ReadonlyField;


class ViewableMemberObjectExtension extends Extension
{
    public function updateCMSFields(FieldList $fields)
    {
        $owner = $this->getOwner();
        if ($owner->isPublished()) {
            $liveVersion = '<a href="' . $owner->Link() . '">View Live Version</a> ';
        } else {
            $liveVersion = '[not published] ';
        }
        if ($owner->exists()) {
            $fields->addFieldsToTab(
                'Root.ViewAndEdit',
                [
                    LiteralField::create(
                        'LinkNice',
                        '<p class="message good"> ' . $liveVersion . ' | <a href="' . $owner->DraftLink() . '">View Draft Version</a></p>'
                    ),
                    ReadonlyField::create(
                        'ViewerGroups',
                        'Viewer Groups',
                        (
                            $owner->MyViewerGroups()->exists() ?
                            implode(', ', $owner->MyViewerGroups()->column('Title'))
                            : 'None'
                        )
                    ),
                ]
            );
        }
        $fields->addFieldsToTab(
            'Root.History',
            [
                ReadonlyField::create(
                    'Created',
                    'Created',
                    $owner->dbObject('Created')->Nice()
                ),
                ReadonlyField::create(
                    'LastEdited',
                    'Last Edited',
                    $owner->dbObject('LastEdited')->Nice()
                ),
                ReadonlyField::create(
                    'LastUpdatedByID',
                    'Last Updated By',
                    $owner->LastUpdatedBy()->getTitle()
                ),
            ]
        );
    }
}
