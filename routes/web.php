<?php

use EchoLabs\Prism\Enums\Provider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

Route::get('/', function () {
    return view('welcome');
});


Route::get('auth/google', function () {

    $client = new \Google\Client();
    $client->setAuthConfig(Storage::path('google-credentials.json'));
    $client->setScopes([\Google_Service_YouTube::YOUTUBE_FORCE_SSL]);


    // This is crucial - it tells Google we want a refresh token
    $client->setAccessType('offline');

    // This forces Google to always show the consent screen
    // This ensures you get a refresh token every time
    $client->setPrompt('consent');

    // Generate the URL for user consent
    $authUrl = $client->createAuthUrl();
    return redirect($authUrl);
});

Route::get('/', function (Request $request) {
    $client = new \Google\Client();
    $client->setAuthConfig(Storage::path('google-credentials.json'));

    // Exchange authorization code for access token
    $token = $client->fetchAccessTokenWithAuthCode($request->get('code'));

    $refreshToken = $client->getRefreshToken();



    // Store the token for future use
    Storage::put('google-access-token.json', json_encode($token));
    Storage::put('google-refresh-token.json', json_encode($refreshToken));

    return 'Authentication successful!';
});



Route::get('openai',function(){

    $srtContent = Storage::get('ScreenFlow.srt');

   $response =  \EchoLabs\Prism\Prism::text()
       ->using(Provider::Anthropic, 'claude-3-5-sonnet-latest')
        ->withPrompt("From this SRT file, generate a description for the video. The description must be in the same language as the SRT file. Here's the SRT content:\n\n{$srtContent}")
        ->withSystemPrompt("You are an expert at analyzing video transcripts and identifying main arguments. Do not talk in third person, use the I form. Be concise and clear and friendly.
             Always return your response in the same language as the SRT file. The response must be in Markdown compatible with YouTube. Just return the description without anything else. ")
        ->withMaxTokens(300)
       ->generate();

    dd($response);
});
