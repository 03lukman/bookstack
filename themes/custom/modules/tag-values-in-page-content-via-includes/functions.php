<?php

use BookStack\Entities\Models\Page;
use BookStack\Facades\Theme;
use BookStack\Theming\ThemeEvents;

// Listen to page include parsing events
Theme::listen(ThemeEvents::PAGE_INCLUDE_PARSE, function (string $tagReference, string $replacementHTML, Page $currentPage, ?Page $referencedPage) {

    // Allow default behaviour for non-tag-based includes
    if (!str_starts_with($tagReference, '0tag:')) {
         return null;
    }

    // Get the target tag name from the include reference
    $tagName = explode(':', $tagReference)[1];

    // Fetch the tag value from the page, parent chapter, or parent book
    $tagValue = $currentPage->tags()->where('name', '=', $tagName)->first()?->value ??
                $currentPage->chapter?->tags()->where('name', '=', $tagName)->first()?->value ??
                $currentPage->book?->tags()->where('name', '=', $tagName)->first()?->value ?? '';

    // Return the tag value to be used for the include
    return $tagValue;
});
