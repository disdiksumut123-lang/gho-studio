<?php

use App\Http\Controllers\Admin\ProviderController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\Cms\CmsController;
use App\Http\Controllers\Coach\CoachController;
use App\Http\Controllers\Fas\ActivityController;
use App\Http\Controllers\Fas\InviteController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\Fas\TrainingController;
use App\Http\Controllers\Pes\CaseController;
use App\Http\Controllers\Pes\LearnController;
use App\Http\Controllers\ShareController;
use App\Http\Controllers\ReportController;
use Illuminate\Support\Facades\Route;

Route::get('/bahasa/{loc}', [AuthController::class, 'locale'])->name('locale');

Route::get('/l/{token}', [ShareController::class, 'open'])->name('share.open');
Route::get('/undangan/{token}', [AuthController::class, 'invite'])->name('invite.open');

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:10,1');
    Route::get('/daftar', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/daftar', [AuthController::class, 'register'])->middleware('throttle:10,1');
});

Route::middleware('auth')->group(function () {
    Route::post('/keluar', [AuthController::class, 'logout'])->name('logout');
    Route::get('/', [AuthController::class, 'home'])->name('home');
    Route::get('/peran/{role}', [AuthController::class, 'switchRole'])->name('role.switch');

    // Fasilitator
    Route::prefix('fasilitator')->name('fas.')->middleware('role:fasilitator')->group(function () {
        Route::get('/', [TrainingController::class, 'index'])->name('home');
        Route::post('/pelatihan', [TrainingController::class, 'store'])->name('training.store');
        Route::get('/pelatihan/{training}', [TrainingController::class, 'show'])->name('training.show');
        Route::put('/pelatihan/{training}', [TrainingController::class, 'update'])->name('training.update');
        Route::delete('/pelatihan/{training}', [TrainingController::class, 'destroy'])->name('training.destroy');
        Route::get('/pelatihan/{training}/peserta', [TrainingController::class, 'participants'])->name('training.participants');
        Route::get('/pelatihan/{training}/laporan', [ReportController::class, 'training'])->name('report');
        Route::get('/pelatihan/{training}/laporan/{user}', [ReportController::class, 'person'])->name('report.user');
        Route::post('/pelatihan/{training}/angkatan', [TrainingController::class, 'addCohort'])->name('cohort.store');
        Route::post('/pelatihan/{training}/staf', [TrainingController::class, 'addStaff'])->name('staff.store');
        Route::post('/pelatihan/{training}/undang', [InviteController::class, 'store'])->name('invite.store');
        Route::post('/pelatihan/{training}/impor', [InviteController::class, 'import'])->name('invite.import');
        Route::get('/pelatihan/{training}/templat', [InviteController::class, 'template'])->name('invite.template');
        Route::post('/pelatihan/{training}/kirim-wa', [InviteController::class, 'sendAll'])->name('invite.sendall');
        Route::post('/undangan/{invite}/kirim', [InviteController::class, 'send'])->name('invite.send');
        Route::delete('/undangan/{invite}', [InviteController::class, 'destroy'])->name('invite.delete');
        Route::post('/pendaftaran/{enrollment}/{action}', [InviteController::class, 'decide'])->name('enroll.decide');
        Route::post('/pelatihan/{training}/agenda', [TrainingController::class, 'addAgenda'])->name('agenda.store');
        Route::post('/agenda/{agenda}/mapel', [TrainingController::class, 'addSubject'])->name('subject.store');
        Route::put('/item/{level}/{id}', [TrainingController::class, 'updateItem'])->name('item.update');
        Route::post('/item/{level}/{id}/urutan', [TrainingController::class, 'toggleSequential'])->name('item.seq');
        Route::post('/item/{level}/{id}/pindah/{dir}', [TrainingController::class, 'move'])->name('item.move');
        Route::delete('/item/{level}/{id}', [TrainingController::class, 'deleteItem'])->name('item.delete');
        Route::post('/item/{level}/{id}/bagikan', [ShareController::class, 'store'])->name('share.store');
        Route::delete('/tautan/{link}', [ShareController::class, 'destroy'])->name('share.delete');

        Route::get('/mapel/{subject}/aktivitas/baru', [ActivityController::class, 'create'])->name('activity.create');
        Route::post('/mapel/{subject}/aktivitas', [ActivityController::class, 'store'])->name('activity.store');
        Route::get('/aktivitas/{activity}', [ActivityController::class, 'edit'])->name('activity.edit');
        Route::put('/aktivitas/{activity}', [ActivityController::class, 'update'])->name('activity.update');
        Route::post('/aktivitas/{activity}/soal', [ActivityController::class, 'saveQuestions'])->name('activity.questions');
        Route::post('/aktivitas/{activity}/buat-soal', [ActivityController::class, 'generateQuiz'])->name('activity.genquiz');
        Route::post('/perancang', [ActivityController::class, 'design'])->name('design');
        Route::get('/jawaban', [CoachController::class, 'review'])->name('review');
    });

    // Coach
    Route::prefix('coach')->name('coach.')->middleware('role:coach')->group(function () {
        Route::get('/', [CoachController::class, 'index'])->name('home');
        Route::get('/review', [CoachController::class, 'review'])->name('review');
    });
    Route::middleware('auth')->group(function () {
        Route::post('/umpan-balik/{feedback}/setujui', [CoachController::class, 'approve'])->name('feedback.approve');
        Route::post('/umpan-balik/{feedback}/kembalikan', [CoachController::class, 'returnBack'])->name('feedback.return');
    });

    // CMS
    Route::prefix('cms')->name('cms.')->middleware('role:cms')->group(function () {
        Route::get('/', [CmsController::class, 'index'])->name('home');
        Route::get('/konten/baru', [CmsController::class, 'create'])->name('create');
        Route::post('/konten', [CmsController::class, 'save'])->name('store');
        Route::get('/konten/{post}', [CmsController::class, 'edit'])->name('edit');
        Route::put('/konten/{post}', [CmsController::class, 'update'])->name('update');
        Route::delete('/konten/{post}', [CmsController::class, 'destroy'])->name('destroy');
        Route::post('/caption', [CmsController::class, 'adapt'])->name('adapt');
        Route::get('/akun', [CmsController::class, 'accounts'])->name('accounts');
        Route::post('/akun', [CmsController::class, 'saveAccount'])->name('accounts.save');
        Route::delete('/akun/{account}', [CmsController::class, 'removeAccount'])->name('accounts.delete');
    });

    // Peserta
    Route::prefix('belajar')->name('pes.')->group(function () {
        Route::get('/', [LearnController::class, 'index'])->name('home');
        Route::post('/gabung', [LearnController::class, 'join'])->name('join');
        Route::get('/pelatihan/{training}', [LearnController::class, 'training'])->name('training');
        Route::get('/pelatihan/{training}/laporan', [ReportController::class, 'mine'])->name('report');
        Route::get('/aktivitas/{activity}', [LearnController::class, 'activity'])->name('activity');
        Route::post('/aktivitas/{activity}/simpan', [LearnController::class, 'saveAnswer'])->name('answer.save');
        Route::post('/aktivitas/{activity}/kirim', [LearnController::class, 'submitAnswer'])->name('answer.submit');
        Route::post('/jawaban/{answer}/tanggapan', [LearnController::class, 'feedback'])->name('answer.feedback');
        Route::get('/jawaban/{answer}/berkas', [LearnController::class, 'download'])->name('answer.file');
        Route::post('/aktivitas/{activity}/selesai', [LearnController::class, 'complete'])->name('complete');
        Route::post('/aktivitas/{activity}/kemajuan', [LearnController::class, 'mediaProgress'])->name('media');
        Route::post('/aktivitas/{activity}/kuis', [LearnController::class, 'submitQuiz'])->name('quiz');
        Route::post('/aktivitas/{activity}/kasus/mulai', [CaseController::class, 'start'])->name('case.start');
        Route::post('/kasus/{session}/jawab', [CaseController::class, 'answer'])->name('case.answer');
        Route::post('/kasus/{session}/tanggapan', [CaseController::class, 'respond'])->name('case.respond');
    });

    // Pengaturan (admin)
    Route::prefix('pengaturan')->name('admin.')->middleware('role:admin')->group(function () {
        Route::get('/penyedia', [ProviderController::class, 'index'])->name('providers');
        Route::post('/penyedia', [ProviderController::class, 'store'])->name('providers.store');
        Route::put('/penyedia/{provider}', [ProviderController::class, 'update'])->name('providers.update');
        Route::delete('/penyedia/{provider}', [ProviderController::class, 'destroy'])->name('providers.destroy');
        Route::post('/penyedia/{provider}/uji', [ProviderController::class, 'test'])->name('providers.test');
        Route::post('/penyedia/{provider}/utama', [ProviderController::class, 'makeDefault'])->name('providers.default');
        Route::get('/pengguna', [UserController::class, 'index'])->name('users');
        Route::get('/whatsapp', [SettingController::class, 'index'])->name('settings');
        Route::post('/whatsapp', [SettingController::class, 'save'])->name('settings.save');
        Route::post('/pengguna', [UserController::class, 'store'])->name('users.store');
        Route::put('/pengguna/{user}', [UserController::class, 'update'])->name('users.update');
    });
});
