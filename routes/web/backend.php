<?php

//Route Dashboard

use App\Exports\GoodStandingDataExport;
use App\Exports\NocDataExport;
use App\Http\Controllers\Admin\CMS\GoodStandingController as CMSGoodStandingController;

use App\Http\Controllers\Admin\CMS\MPharmacyController;
use App\Http\Controllers\Admin\CMS\NocController as CMSNocController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\Master\MPharmaController;
use App\Http\Controllers\Admin\NOC\NOCController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\GoodStanding\GoodStandingController;
use Maatwebsite\Excel\Facades\Excel;

Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth'])
    ->name('dashboard');
// CMS
Route::middleware(['auth', 'admin:admin,super_admin'])->group(function () {
    Route::resource('cms/noc-main', CMSNocController::class)->only('index', 'show', 'update');

    Route::resource('cms/good-standing-main', CMSGoodStandingController::class)->only('index', 'show', 'update');
    Route::resource('cms/m-phamacy', MPharmacyController::class)->only('index', 'show', 'update');
    Route::put('cms/m-phamacy/approve/{id}', [MPharmacyController::class, 'approve'])->name('phamacy.approve');
    Route::put('cms/m-phamacy/reject/{id}', [MPharmacyController::class, 'reject'])->name('phamacy.reject');

    Route::put('cms/noc-approve/{id}', [CMSNocController::class, 'approve'])->name('noc.approve');
    Route::post('cms/store-data', [CMSNocController::class, 'storeData'])->name('applicant.store');

    Route::get('change-password', [PasswordResetController::class, 'showChangePassword'])->name('change.password');
    Route::post('change-password', [PasswordResetController::class, 'updatePassword'])->name('password.update');
});

Route::post('export-data-noc', function () {
    $status = request('noc_data'); // Retrieve the status from the form
    return Excel::download(new NocDataExport($status), 'noc_data.xlsx');
});

Route::post('export-data-good', function () {
    $status = request('noc_data'); // Retrieve the status from the form

    return Excel::download(new GoodStandingDataExport($status), 'good_standing_data.xlsx');
});

Route::get('/download-images/{userId}', [CMSNocController::class, 'downloadImages']);
Route::get('/download-images-without-pdf/{userId}', [CMSNocController::class, 'downloadImagesWithoutPdf']);
Route::resource('dashboard/user', UserController::class)->middleware(['auth']);

Route::get('/mpharma-download-images/{userId}', [MPharmacyController::class, 'downloadImages']);

//NOC Normal User
Route::resource('backend/noc', NOCController::class)->middleware(['auth']);
Route::resource('backend/good-standing', GoodStandingController::class)->middleware(['auth']);
Route::resource('backend/m-pharma', MPharmaController::class)->middleware(['auth']);
