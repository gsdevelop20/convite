<?php

use App\Http\Controllers\Admin\EventController;
use App\Http\Controllers\Admin\EventInvitationController;
use App\Http\Controllers\Admin\GuestController;
use App\Http\Controllers\Admin\ZApiSettingsController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

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

Route::redirect('/', '/dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');
    Route::get('/events', [EventController::class, 'index'])->name('events.index');
    Route::post('/events', [EventController::class, 'store'])->name('events.store');
    Route::match(['patch', 'post'], '/events/{event}', [EventController::class, 'update'])->name('events.update');
    Route::get('/events/{event}/queue', [EventController::class, 'queue'])->name('events.queue');
    Route::get('/events/{event}', [EventController::class, 'show'])->name('events.show');
    Route::post('/events/{event}/guests', [GuestController::class, 'store'])->name('events.guests.store');
    Route::patch('/events/{event}/guests/{guest}', [GuestController::class, 'update'])->name('events.guests.update');
    Route::delete('/events/{event}/guests/{guest}', [GuestController::class, 'destroy'])->name('events.guests.destroy');
    Route::post('/events/{event}/guests/import', [GuestController::class, 'import'])->name('events.guests.import');
    Route::post('/events/{event}/invitations/send', [EventInvitationController::class, 'store'])->name('events.invitations.send');
    Route::post('/events/{event}/guests/{guest}/send-now', [EventInvitationController::class, 'sendNow'])->name('events.guests.send-now');
    Route::get('/settings/zapi', [ZApiSettingsController::class, 'edit'])->name('settings.zapi.edit');
    Route::put('/settings/zapi', [ZApiSettingsController::class, 'update'])->name('settings.zapi.update');
    Route::post('/settings/zapi/test', [ZApiSettingsController::class, 'test'])->name('settings.zapi.test');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});


require __DIR__.'/auth.php';
