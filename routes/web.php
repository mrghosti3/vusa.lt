<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Route;
use Laravel\Socialite\Facades\Socialite;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

$this->host = config('app.url');
$this->host = explode('://', $this->host)[1];

Route::feeds();

Route::prefix('admin')->group(function () {
    Route::middleware(['auth', 'verified'])->group(function () {

        // Main
        Route::get('/', [Admin\DashboardController::class, 'dashboard'])->name('dashboard');

        // Resources
        Route::resource('pages', Admin\PagesController::class);
        Route::post('pages/search', [Admin\PagesController::class, 'searchForPage'])->name('pages.search');

        Route::resource('news', Admin\NewsController::class);
        Route::post('news/search', [Admin\NewsController::class, 'searchForNews'])->name('news.search');

        Route::resource('mainPage', Admin\MainPageController::class);
        Route::resource('banners', Admin\BannerController::class);
        Route::resource('navigation', Admin\NavigationController::class);
        Route::resource('users', Admin\UserController::class);
        Route::post('users/{user}/detach/{duty}', [Admin\UserController::class, 'detachFromDuty'])->name('users.detach');

        Route::resource('calendar', Admin\CalendarController::class);
        Route::post('calendar/{calendar}/media/{media}', [Admin\CalendarController::class, 'destroyMedia'])->name('calendar.destroyMedia');
        Route::resource('registrationForms', Admin\RegistrationFormController::class);
        Route::resource('registrations', Admin\RegistrationController::class);

        Route::resource('saziningaiExams', Admin\SaziningaiExamsController::class);
        Route::resource('saziningaiExamFlows', Admin\SaziningaiExamFlowsController::class);
        Route::resource('saziningaiExamObservers', Admin\SaziningaiExamObserversController::class);
        Route::resource('files', Admin\FilesController::class);
        Route::resource('duties', Admin\DutyController::class);
        Route::resource('dutyUsers', Admin\DutyUserController::class);
        Route::resource('dutyInstitutions', Admin\DutyInstitutionController::class);
        Route::post('dutyInstitutions/search', [Admin\DutyInstitutionController::class, 'searchForInstitutions'])->name('dutyInstitutions.search');
        Route::post('dutyInstitutions/reorderDuties', [Admin\DutyInstitutionController::class, 'reorderDuties'])->name('dutyInstitutions.reorderDuties');

        Route::resource('roles', Admin\RolesController::class);
        Route::post('files/search', [Admin\FilesController::class, 'searchForFiles'])->name('files.search');
        Route::post('images/search', [Admin\FilesController::class, 'searchForImages'])->name('images.search');
        Route::post('duties/search', [Admin\DutyController::class, 'searchForDuties'])->name('duties.search');

        Route::post('files/uploadImage', [Admin\FilesController::class, 'uploadImage'])->name('files.uploadImage');
    });
});

Route::get('/auth/redirect', function () {
    return Socialite::driver('microsoft')->redirect();
})->name('microsoft.redirect');

Route::get('/auth/microsoft/callback', [Admin\UserController::class, 'storeFromMicrosoft'])->name('microsoft.callback');

Route::group(['prefix' => '{lang?}', 'where' => ['lang' => '(lt|en)']], function () {

    Route::name('padalinys.')->group(function () {
        Route::domain('{padalinys}.' . $this->host)->group(function () {
            Route::get('/', [Public\MainController::class, 'home'])->name('home');
            Route::get('naujienos', [Public\MainController::class, 'newsArchive'])->name('newsArchive');
            Route::get('naujiena/archyvas', [Public\MainController::class, 'newsArchive']);
            Route::get('saziningai-registracija', [Public\MainController::class, 'saziningaiExamRegistration'])->name('saziningaiExamRegistration');
            Route::post('saziningai-registracija', [Public\MainController::class, 'storeSaziningaiExamRegistration'])->name('saziningaiExamRegistration.store');
            Route::get('saziningai-uzregistruoti-egzaminai', [Public\MainController::class, 'saziningaiExams'])->name('saziningaiExams.registered');
            Route::post('saziningai-uzregistruoti-egzaminai', [Public\MainController::class, 'storeSaziningaiExamObserver'])->name('saziningaiExamObserver.store');

            Route::get('ataskaita-2022', [Public\MainController::class, 'ataskaita2022']);
            Route::get('ataskaita-2022/{permalink}', [Public\MainController::class, 'ataskaita2022'])->where('permalink', '.*')->name('ataskaita2022');

            Route::get('kontaktai', [Public\MainController::class, 'contactsCategory'])->name('contacts');
            Route::get('kontaktai/paieska', [Public\MainController::class, 'searchContacts'])->name('contacts.search');
            Route::get('kontaktai/kategorija/{alias}', [Public\MainController::class, 'contactsCategory'])->name('contacts.category');
            Route::get('kontaktai/{alias}', [Public\MainController::class, 'contacts'])->name('contacts.alias');

            Route::get('{newsString}/{permalink}', [Public\MainController::class, 'news'])->where('news_string', '(naujiena|news)')->name('news');

            Route::post('search', [Public\MainController::class, 'search'])->name('search');

            Route::get('mainNews', [Public\MainController::class, 'getMainNews']);
            Route::get('{permalink}', [Public\MainController::class, 'page'])->where('permalink', '.*')->name('page');
        });
    });

    Route::name('main.')->group(function () {
        Route::domain($this->host)->group(function () {
            Route::get('/', [Public\MainController::class, 'home'])->name('home');
            Route::get('naujienos', [Public\MainController::class, 'newsArchive'])->name('newsArchive');
            Route::get('naujiena/archyvas', [Public\MainController::class, 'newsArchive']);
            Route::get('saziningai-registracija', [Public\MainController::class, 'saziningaiExamRegistration'])->name('saziningaiExamRegistration');
            Route::post('saziningai-registracija', [Public\MainController::class, 'storeSaziningaiExamRegistration'])->name('saziningaiExamRegistration.store');
            Route::get('saziningai-uzregistruoti-egzaminai', [Public\MainController::class, 'saziningaiExams'])->name('saziningaiExams.registered');
            Route::post('saziningai-uzregistruoti-egzaminai', [Public\MainController::class, 'storeSaziningaiExamObserver'])->name('saziningaiExamObserver.store');
            
            Route::get('nariu-registracija', [Public\MainController::class, 'memberRegistration'])->name('memberRegistration');
            Route::post('nariu-registracija', [Public\MainController::class, 'storeMemberRegistration'])->name('memberRegistration.store');

            Route::get('ataskaita-2022', [Public\MainController::class, 'ataskaita2022']);
            Route::get('ataskaita-2022/{permalink}', [Public\MainController::class, 'ataskaita2022'])->where('permalink', '.*')->name('ataskaita2022');

            Route::get('kontaktai', [Public\MainController::class, 'contactsCategory'])->name('contacts');
            Route::get('kontaktai/paieska', [Public\MainController::class, 'searchContacts'])->name('contacts.search');
            Route::get('kontaktai/kategorija/{alias}', [Public\MainController::class, 'contactsCategory'])->name('contacts.category');
            Route::get('kontaktai/{alias}', [Public\MainController::class, 'contacts'])->name('contacts.alias');

            Route::get('kalendorius/ics', [Public\MainController::class, 'publicAllEventCalendar'])->name('calendar.ics');

            // because of this, can't get current route for main.page, it shows main.news
            Route::get('{newsString}/{permalink}', [Public\MainController::class, 'news'])->where('news_string', '(naujiena|news)')->name('news');

            // Route::middleware(['throttle:summerCamps'])->group(function () {
                Route::get('pirmakursiu-stovyklos', [Public\MainController::class, 'summerCamps'])->name('pirmakursiuStovyklos');
                Route::get('kalendorius/renginys/{calendar}', [Public\MainController::class, 'calendarEventMain'])->name('calendar.event');
            // });

            Route::post('search', [Public\MainController::class, 'search'])->name('search');

            Route::get('mainNews', [Public\MainController::class, 'getMainNews']);

            Route::get('{permalink}', [Public\MainController::class, 'page'])->where('permalink', '.*')->name('page');
        });
    });
});

Route::get('/', [Public\MainController::class, 'home'])->name('home');
Route::get('naujienos', [Public\MainController::class, 'newsArchive'])->name('newsArchive');

Route::get('naujiena/archyvas', [Public\MainController::class, 'newsArchive']);

// render login form
Route::inertia('login', 'LoginForm')->middleware('guest')->name('login');

Route::get('saziningai-registracija', [Public\MainController::class, 'saziningaiExamRegistration'])->name('saziningaiExamRegistration');
Route::post('saziningai-registracija', [Public\MainController::class, 'storeSaziningaiExamRegistration'])->name('saziningaiExamRegistration.store');
Route::get('saziningai-uzregistruoti-egzaminai', [Public\MainController::class, 'saziningaiExams'])->name('saziningaiExams.registered');
Route::post('saziningai-uzregistruoti-egzaminai', [Public\MainController::class, 'storeSaziningaiExamObserver'])->name('saziningaiExamObserver.store');

Route::get('nariu-registracija', [Public\MainController::class, 'memberRegistration'])->name('memberRegistration');

Route::get('ataskaita-2022', [Public\MainController::class, 'ataskaita2022']);
Route::get('ataskaita-2022/{permalink}', [Public\MainController::class, 'ataskaita2022'])->where('permalink', '.*')->name('ataskaita2022');
Route::get('{newsString}/{permalink}', [Public\MainController::class, 'news'])->where('newsString', '(naujiena|news)')->name('news');

Route::get('kontaktai', [Public\MainController::class, 'contactsCategory'])->name('contacts');
Route::get('kontaktai/paieska', [Public\MainController::class, 'searchContacts'])->name('contacts.search');
Route::get('kontaktai/kategorija/{alias}', [Public\MainController::class, 'contactsCategory'])->name('contacts.category');
Route::get('kontaktai/{alias}', [Public\MainController::class, 'contacts'])->name('contacts.alias');

Route::get('pirmakursiu-stovyklos', [Public\MainController::class, 'summerCamps'])->name('pirmakursiuStovyklos');
Route::get('kalendorius/renginys/{calendar}', [Public\MainController::class, 'calendarEvent'])->name('calendar.event');

Route::middleware(['throttle:formRegistrations'])->group(function () {
    Route::post('registration/{registrationForm}', [Public\MainController::class, 'storeRegistration'])->name('registration.store');
});

Route::post('search', [Public\MainController::class, 'search'])->name('search');

Route::get('mainNews', [Public\MainController::class, 'getMainNews']);

Route::get('kalendorius/ics', [Public\MainController::class, 'publicAllEventCalendar'])->name('calendar.ics');

Route::get('{permalink}', [Public\MainController::class, 'page'])->where('permalink', '.*')->name('page');
