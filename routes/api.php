<?php
use App\Http\Controllers\Api\UserController;
/** No Role auth Controller */
Route::group(['middleware' => ['cors'],'namespace' => 'Api'], function (){
    Route::post('register-social', 'UserController@socialLogin');
    Route::post('/facbook-login', [UserController::class, 'facebookLogin']);//test route

    // Route::post('register-social', 'UserController@socialLogin')->middleware('throttle:10,1');
    Route::post('webhook/{token}', 'WebHookController@webhook');

    Route::post('register', 'Auth\RegisterController@register');
    Route::post('login', 'Auth\LoginController@login');
    Route::post('login/{social}', 'Auth\LoginController@social');
    Route::post('refresh', 'Auth\LoginController@refresh');
    Route::post('send-sms-verify', 'Auth\RegisterController@sendSmsVerify');
    Route::post('check-sms-verify', 'Auth\RegisterController@checkSmsVerify');

    Route::post('send-otp', 'Auth\OtpController@sendOtp');
    Route::post('verify-otp', 'Auth\OtpController@verifyOtp');

    Route::post('reset-my-password', 'UserController@resetMyPassword');
    Route::post('new-password/{token}', 'UserController@newPassword');
    Route::get('/home-special-survey', 'SurveysController@homeSpecialSurvey');
    Route::get('/home-current-special-survey', 'SurveysController@homeCurrentSpecialSurvey');
    Route::get('/home-special-survey/{id}', 'SurveysController@homeSpecialSurveyById');
    Route::post('/survey-image-upload', 'SurveysController@surveyGraphImage');
    Route::get('/home-survey-approval', 'SurveysController@homeSurveyApproval');
    Route::get('/subjects', 'SubjectsController@index');
    Route::get('/languages', 'CountryController@languages');
    Route::get('/categories/tree', 'CategoryController@categoryTree');
    Route::get('init', 'InitController@init');
    Route::get('i18n', 'InitController@i18n');
    Route::get('/locale', 'LocaleController@getLocale');
    Route::get('/hasSurvey/{id}', 'SurveysController@hasSurvey');
    Route::get('/surveys/detail/{slug}', 'SurveysController@getdetails'); //changed function, as panel edit survey & frontend survey details was using same function
    Route::get('/subjectHasSurvey/{id}', 'SubjectsController@subjectHasSurvey');
    Route::get('/citiesOfCountry/{country}', 'CountryController@citiesOfCountry');
    Route::get('/surveys/newest', 'SurveysController@newest');
    Route::get('/pages', 'PagesController@index');
    Route::get('/pages/{slug}', 'PagesController@show');
    Route::get('/surveys/topVoted', 'SurveysController@topVoted');
    Route::get('/user-profile/{username}', 'UserController@userProfile');
    Route::get('/categories/{category}', 'CategoryController@categoryDetail');
    Route::get('/hit-survey/{category}', 'SurveysController@hitSurvey');
    Route::get('/test', 'TestController@test');
});

/** User Role Auth Controller */
Route::group(['middleware' => ['auth:api'],  'namespace' => 'Api'], function (){

    Route::resource('/panel/categories', 'CategoryController');
    Route::get('/optimal-init', 'InitController@init');
    Route::get('/profile', 'UserController@getProfile');
    Route::get('/checkVote/{survey}/{choice}', 'SurveyVotesController@checkVote');
    Route::get('/cancelVote/{survey}/{choice}', 'SurveyVotesController@cancelVote');
    Route::get('/survey/{survey}/comments', 'CommentController@getSurveyComments');
    Route::get('/delete-my-account', 'UserController@deleteMyAccount');
    Route::get('/approve-delete-profile/{token}', 'UserController@ApproveDeleteProfile');
    Route::get('/verify-email-change/{token}', 'UserController@verifyEmailChange');
    Route::put('/profile', 'UserController@updateProfile');
    Route::put('/comments/{comment}', 'CommentController@update');
    Route::post('/password-change', 'UserController@passwordChange');
    Route::post('/email-change', 'UserController@emailChange');
    Route::post('/check-password', 'UserController@checkPassword');
    Route::post('/logout', 'Auth\LoginController@logout');
    Route::post('/savePhoneNumber', 'UserController@savePhoneNumber');
    Route::post('/userCategoryCreate', 'CategoryController@userCategoryCreate');
    Route::post('/pushVote', 'SurveysController@pushVote');
    Route::post('/surveys', 'SurveysController@store');
    Route::post('/survey/{survey}/vote', 'SurveyVotesController@vote')->name('VoteSurvey');
    Route::post('/comments', 'CommentController@store');
    Route::post('/addChoice/{survey}', 'SurveyChoiceController@addChoice');
    Route::post('/submitVote/{survey}', 'SurveyVotesController@submitVote');
    Route::post('/file/upload', 'FileController@upload');
    Route::post('/likes', 'CommentLikeController@store');
    Route::patch('/profileImage', 'UserController@profileImage');
    Route::patch('/nationalImage', 'UserController@nationalImage');
    Route::patch('/portraitImage', 'UserController@portraitImage');
    Route::delete('/comments/{comment}', 'CommentController@destroy');


    /** Friend */
    Route::get('/friend/listMyFriend', 'FriendsController@listMyFriend');
    Route::get('/friend/listRequestFriend', 'FriendsController@listRequestFriend');
    Route::get('/friend/requestFriend/{friend}', 'FriendsController@requestFriend');
    Route::post('/friend/deleteFriend', 'FriendsController@deleteFriend');
    Route::delete('/friend/requestFriend/{friend}', 'FriendsController@cancelRequestFriend');
    Route::patch('/friend/requestAnswer/{friend}', 'FriendsController@requestAnswer');

    /** Privacies */
    Route::get('/userPrivacySettings', 'UserController@userPrivacySettings');
    Route::post('/userPrivacySettings', 'UserController@userPrivacySettingsChange');
    /** Privacies */
});

/** Super Admin Role Auth Controller */
Route::group(['middleware' => 'super_admin', 'prefix' => 'panel', 'namespace' => 'Api'], function (){
    Route::resource('/countries', 'CountryController');
    Route::resource('/pages', 'PagesController');
    Route::put('/pages/{page}/publish', 'PagesController@publish');
    Route::get('/yaml-parse', 'TranslationsController@yamlParseKeyword');
    Route::post('/privacies', 'PrivacyController@create');
    Route::post('/password-change', 'UserController@passwordChange');
});

/** Country Admin Role Controller */
Route::group(['middleware' => 'country_admin', 'prefix' => 'panel', 'namespace' => 'Api'], function (){
    Route::get('/pages', 'PagesController@index');
    Route::post('/pages/translation/{id}', 'PagesController@translation');
    Route::patch('/users/upgrade/{user}', 'MembersController@upgrade');
});

/** Super Admin and Country Admin Role Controller */
Route::group(['middleware' => ['has_any_role:country_admin.super_admin'], 'prefix' => 'panel', 'namespace'=> 'Api'], function (){
    Route::resource('/subjects', 'SubjectsController');
    Route::resource('/categories', 'CategoryController');
    Route::get('/category/{category}', 'CategoryController@getChildren');
    Route::put('/category/{category}', 'CategoryController@getChildren');
    //for api settings
    Route::get('/user-profile/{user}', 'UserController@userProfile');
    Route::get('panel-init', 'InitController@panelInit');
    Route::put('/comments/{comment}', 'CommentController@changeStatus');
    Route::get('/comments', 'CommentController@comments');
    Route::delete('/comments/{comment}', 'CommentController@destroy');
    Route::get('/menu', 'MenuController@index');
    Route::get('/dashboard', 'DashboardController@index');
    Route::get('/cities/country/{id}', 'CityCrudController@getCitiesWithCountryId');
    Route::get('/categories/list/pending', 'CategoryController@pending');
    Route::get('/surveys/{type}', 'SurveysController@index');
    Route::get('/pendingChoices', 'SurveyChoiceController@pendingChoices');
    Route::get('/surveys/detail/{id}', 'SurveysController@detail');
    Route::put('/surveys/{survey}', 'SurveysController@update');
    Route::put('/surveys/normal', 'SurveysController@index');
    Route::put('/choice/statusUpdate/{id}', 'SurveyChoiceController@statusUpdate');
    Route::post('/surveys/fake/{id}', 'SurveysController@fake');
    Route::post('/surveys', 'SurveysController@store');
    Route::get('/specialDateRange', 'SurveysController@specialDateRange');
    Route::patch('surveys/status/{survey}', 'SurveysController@statusUpdate');
    Route::patch('surveys/show-on-home/{survey}', 'SurveysController@showOnHomeUpdate');
    Route::delete('/surveys/{survey}', 'SurveysController@destroy');
    Route::delete('/ca/{survey}', 'SurveysController@destroy');
    Route::delete('/surveys/choices/{choice}', 'SurveyChoiceController@destroy');
    Route::post('/choice_image/upload', 'SurveyChoiceController@uploadImage');
    Route::patch('categories/status/{category}', 'CategoryController@statusUpdate');
    Route::resource('keywords', 'KeywordsController');
    Route::resource('translations', 'TranslationsController');
    Route::resource('/users', 'MembersController');
    Route::resource('/cities', 'CityCrudController');
    Route::post('file/upload', 'FileController@upload');
    Route::resource('/pages', 'PagesController');
    //city routes
    //keyword routes 0001.17109.tr
});