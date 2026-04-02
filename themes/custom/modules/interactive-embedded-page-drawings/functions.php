<?php

use BookStack\Facades\Theme;
use BookStack\Theming\ThemeEvents;
use BookStack\Theming\ThemeViews;

// Update the application configuration to allow diagrams.net
// viewer as an approved iframe source.
Theme::listen(ThemeEvents::APP_BOOT, function () {
    $iframeSources = config()->get('app.iframe_sources');
    $iframeSources .= ' https://viewer.diagrams.net';
    config()->set('app.iframe_sources', $iframeSources);
});

Theme::listen(ThemeEvents::THEME_REGISTER_VIEWS, function (ThemeViews $themeViews) {
    $themeViews->renderBefore('layouts.parts.base-body-start', 'interactive-drawings-pre-body', 50);
});