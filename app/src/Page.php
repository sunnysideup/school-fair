<?php

namespace {

    use SilverStripe\CMS\Model\SiteTree;

    /**
 * Class \Page
 *
 * @property bool $NeverCachePublicly
 * @property int $PublicCacheDurationInSeconds
 * @mixin \Sunnysideup\SimpleTemplateCaching\Extensions\PageExtension
 * @mixin \SilverStripe\Assets\Shortcodes\FileLinkTracking
 * @mixin \SilverStripe\Assets\AssetControlExtension
 * @mixin \SilverStripe\CMS\Model\SiteTreeLinkTracking
 * @mixin \SilverStripe\Versioned\VersionedStateExtension
 * @mixin \SilverStripe\Versioned\RecursivePublishable
 * @mixin \Sunnysideup\AutomatedContentManagement\Extensions\DataObjectExtensionForLLM
 * @mixin \Sunnysideup\HasOneEdit\DataObjectExtension
 * @mixin \Sunnysideup\SimpleTemplateCaching\Extensions\DataObjectExtension
 * @mixin \Sunnysideup\YesNoAnyFilter\FixBooleanSearchAsExtension
 */
class Page extends SiteTree
    {
        private static $db = [];

        private static $has_one = [];
    }
}
