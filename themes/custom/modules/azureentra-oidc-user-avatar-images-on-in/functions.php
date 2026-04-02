<?php

use BookStack\Facades\Theme;
use BookStack\Http\HttpRequestService;
use BookStack\Theming\ThemeEvents;
use BookStack\Uploads\UserAvatars;
use BookStack\Users\Models\User;
use GuzzleHttp\Psr7\Request;

// Variable to track the access token for later use
$accessToken = '';

// Listen for the OIDC ID token validation events so we can capture the access token
Theme::listen(ThemeEvents::OIDC_ID_TOKEN_PRE_VALIDATE, function (array $idTokenData, array $accessTokenData) use (&$accessToken) {
    $accessToken = $accessTokenData['access_token'] ?? '';
});

// Listen for the auth register event to download and assign the profile image to the user
Theme::listen(ThemeEvents::AUTH_REGISTER, function (string $authSystem, User $user) use (&$accessToken) {
    if ($authSystem === 'oidc' && $accessToken) {
        downloadAndAssignUserAvatar($user, $accessToken);
    }
});

// Function to download and assign the profile image to the user
function downloadAndAssignUserAvatar(User $user, string $accessToken): void
{
    // Create the HTTP client for fetching the profile image
    /** @var HttpRequestService $http */
    $http = app()->make(HttpRequestService::class);
    $client = $http->buildClient(4);

    // Fetch the profile image via an authorized request
    $response = $client->sendRequest(new Request('GET', 'https://graph.microsoft.com/v1.0/me/photo/$value', [
        'Authorization' => 'Bearer ' . $accessToken,
    ]));

    // If the response is successful and the content type is an image, assign the image to the user
    $allowedContentTypes = ['image/jpeg', 'image/png'];
    if ($response->getStatusCode() === 200 && in_array($response->getHeader('Content-Type')[0], $allowedContentTypes)) {
        $avatars = app()->make(UserAvatars::class);
        $extension = explode('/', $response->getHeader('Content-Type')[0])[1];
        $avatars->assignToUserFromExistingData($user, $response->getBody()->getContents(), $extension);
    }
}
