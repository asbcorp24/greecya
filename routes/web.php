<?php

use App\Http\Controllers\AccountAuthController;
use App\Http\Controllers\AccountController;
use App\Http\Controllers\Admin\AccessControlController as AdminAccessControlController;
use App\Http\Controllers\Admin\AccessController as AdminAccessController;
use App\Http\Controllers\Admin\AuditController as AdminAuditController;
use App\Http\Controllers\Admin\BookingController as AdminBookingController;
use App\Http\Controllers\Admin\CertificateController as AdminCertificateController;
use App\Http\Controllers\Admin\CrmPlusController as AdminCrmPlusController;
use App\Http\Controllers\Admin\CustomerController as AdminCustomerController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\FamilyController as AdminFamilyController;
use App\Http\Controllers\Admin\FinanceController as AdminFinanceController;
use App\Http\Controllers\Admin\GalleryController as AdminGalleryController;
use App\Http\Controllers\Admin\HeroSlideController as AdminHeroSlideController;
use App\Http\Controllers\Admin\IncidentController as AdminIncidentController;
use App\Http\Controllers\Admin\InventoryController as AdminInventoryController;
use App\Http\Controllers\Admin\LeadController as AdminLeadController;
use App\Http\Controllers\Admin\MedicalController as AdminMedicalController;
use App\Http\Controllers\Admin\MembershipController as AdminMembershipController;
use App\Http\Controllers\Admin\NewsController as AdminNewsController;
use App\Http\Controllers\Admin\OperationsController as AdminOperationsController;
use App\Http\Controllers\Admin\OrderController as AdminOrderController;
use App\Http\Controllers\Admin\PoolController as AdminPoolController;
use App\Http\Controllers\Admin\ProductController as AdminProductController;
use App\Http\Controllers\Admin\ReportsController as AdminReportsController;
use App\Http\Controllers\Admin\ScheduleController as AdminScheduleController;
use App\Http\Controllers\Admin\SeoController as AdminSeoController;
use App\Http\Controllers\Admin\SettingsController as AdminSettingsController;
use App\Http\Controllers\Admin\StaffController as AdminStaffController;
use App\Http\Controllers\Admin\SwimSchoolController as AdminSwimSchoolController;
use App\Http\Controllers\Admin\TrainerController as AdminTrainerController;
use App\Http\Controllers\Admin\TrainingPlanController as AdminTrainingPlanController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\CatalogController;
use App\Http\Controllers\CertificateController;
use App\Http\Controllers\CoachController;
use App\Http\Controllers\GalleryController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\LeadController;
use App\Http\Controllers\NewsController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ReceptionController;
use App\Http\Controllers\SeoController;
use Illuminate\Support\Facades\Route;

Route::get('/',HomeController::class)->name('home');
Route::view('/privacy','legal.privacy')->name('privacy');
Route::view('/offer','legal.offer')->name('offer');
Route::get('/news',[NewsController::class,'index'])->name('news.index');
Route::get('/news/{post}',[NewsController::class,'show'])->name('news.show');
Route::get('/gallery',[GalleryController::class,'index'])->name('gallery.index');
Route::get('/gallery/{album}',[GalleryController::class,'show'])->name('gallery.show');
Route::get('/certificates/{certificate}',[CertificateController::class,'show'])->name('certificate.verify');
Route::get('/certificates/{certificate}/print',[CertificateController::class,'print'])->name('certificate.print');
Route::get('/booking',[BookingController::class,'index'])->name('booking.index');
Route::get('/booking/slots',[BookingController::class,'slots'])->name('booking.slots')->middleware('throttle:60,1');
Route::post('/booking',[BookingController::class,'store'])->name('booking.store')->middleware('throttle:15,1');
Route::get('/booking/success/{booking}',[BookingController::class,'success'])->name('booking.success');
Route::post('/request-call',[LeadController::class,'store'])->name('lead.store')->middleware('throttle:10,1');
Route::get('/tickets',CatalogController::class)->name('catalog.index');
Route::post('/orders',[OrderController::class,'store'])->name('order.store')->middleware('throttle:10,1');
Route::get('/orders/success/{order}',[OrderController::class,'success'])->name('order.success');
Route::get('/sitemap.xml',[SeoController::class,'sitemap'])->name('seo.sitemap');
Route::get('/robots.txt',[SeoController::class,'robots'])->name('seo.robots');

Route::middleware('guest')->group(function(){
    Route::get('/login',[AuthController::class,'create'])->name('login');Route::post('/login',[AuthController::class,'store'])->name('login.store');
    Route::get('/account/register',[AccountAuthController::class,'create'])->name('account.register');Route::post('/account/register',[AccountAuthController::class,'store'])->name('account.register.store');
});
Route::post('/logout',[AuthController::class,'destroy'])->middleware('auth')->name('logout');
Route::prefix('account')->name('account.')->middleware(['auth','customer'])->group(function(){
    Route::get('/',[AccountController::class,'dashboard'])->name('dashboard');Route::patch('/profile',[AccountController::class,'updateProfile'])->name('profile.update');Route::post('/progress',[AccountController::class,'storeProgress'])->name('progress.store');
});

Route::middleware(['auth','admin','audit.admin'])->group(function(){
    Route::get('/reception',[ReceptionController::class,'index'])->name('reception.index');
    Route::get('/coach',[CoachController::class,'index'])->name('coach.index');
    Route::post('/coach/notes',[CoachController::class,'note'])->name('coach.notes.store');
    Route::post('/coach/sessions/{session}/attendance',[CoachController::class,'attendance'])->name('coach.attendance.store');
    Route::post('/coach/members/{member}/progress',[CoachController::class,'progress'])->name('coach.progress.store');
});

Route::prefix('admin')->name('admin.')->middleware(['auth','admin','audit.admin'])->group(function(){
    Route::get('/',DashboardController::class)->name('dashboard');

    Route::get('/permissions',[AdminAccessControlController::class,'index'])->name('permissions.index');
    Route::post('/permissions/users',[AdminAccessControlController::class,'storeUser'])->name('permissions.users.store');
    Route::patch('/permissions/users/{user}/role',[AdminAccessControlController::class,'updateUserRole'])->name('permissions.users.role');
    Route::put('/permissions/roles',[AdminAccessControlController::class,'updateRolePermissions'])->name('permissions.roles.update');
    Route::put('/permissions/users/{user}',[AdminAccessControlController::class,'updateUserPermissions'])->name('permissions.users.update');
    Route::get('/audit',[AdminAuditController::class,'index'])->name('audit.index');

    Route::get('/bookings',[AdminBookingController::class,'index'])->name('bookings.index');Route::patch('/bookings/{booking}',[AdminBookingController::class,'update'])->name('bookings.update');
    Route::get('/customers',[AdminCustomerController::class,'index'])->name('customers.index');
    Route::get('/customers/{customer}',[AdminCustomerController::class,'show'])->name('customers.show');
    Route::patch('/customers/{customer}',[AdminCustomerController::class,'update'])->name('customers.update');
    Route::post('/customers/{customer}/notes',[AdminCustomerController::class,'storeNote'])->name('customers.notes.store');
    Route::post('/customers/{customer}/goals',[AdminCustomerController::class,'storeGoal'])->name('customers.goals.store');
    Route::patch('/customers/{customer}/goals/{goal}',[AdminCustomerController::class,'updateGoal'])->name('customers.goals.update');

    Route::get('/families',[AdminFamilyController::class,'index'])->name('families.index');Route::post('/families',[AdminFamilyController::class,'store'])->name('families.store');
    Route::get('/families/{family}',[AdminFamilyController::class,'show'])->name('families.show');Route::post('/families/{family}/members',[AdminFamilyController::class,'addMember'])->name('families.members.store');
    Route::post('/families/{family}/children',[AdminFamilyController::class,'storeChild'])->name('families.children.store');Route::post('/families/{family}/consents',[AdminFamilyController::class,'consent'])->name('families.consents.store');
    Route::post('/families/{family}/wallet',[AdminFamilyController::class,'wallet'])->name('families.wallet');

    Route::get('/swim-school',[AdminSwimSchoolController::class,'index'])->name('swim-school.index');Route::post('/swim-school',[AdminSwimSchoolController::class,'store'])->name('swim-school.store');
    Route::get('/swim-school/{group}',[AdminSwimSchoolController::class,'show'])->name('swim-school.show');Route::post('/swim-school/{group}/members',[AdminSwimSchoolController::class,'addMember'])->name('swim-school.members.store');
    Route::patch('/swim-school/{group}/members/{member}',[AdminSwimSchoolController::class,'updateMember'])->name('swim-school.members.update');Route::post('/swim-school/{group}/sessions',[AdminSwimSchoolController::class,'storeSession'])->name('swim-school.sessions.store');
    Route::post('/swim-school/{group}/sessions/{session}/attendance',[AdminSwimSchoolController::class,'attendance'])->name('swim-school.attendance.store');Route::post('/swim-school/{group}/makeups/{makeup}',[AdminSwimSchoolController::class,'assignMakeup'])->name('swim-school.makeups.update');
    Route::post('/swim-school/{group}/members/{member}/progress',[AdminSwimSchoolController::class,'progress'])->name('swim-school.progress.store');

    Route::get('/medical',[AdminMedicalController::class,'index'])->name('medical.index');Route::post('/medical',[AdminMedicalController::class,'store'])->name('medical.store');
    Route::patch('/medical/{clearance}',[AdminMedicalController::class,'update'])->name('medical.update');Route::get('/medical/{clearance}/history',[AdminMedicalController::class,'history'])->name('medical.history');

    Route::get('/operations',[AdminOperationsController::class,'index'])->name('operations.index');Route::put('/operations/zones/{zone}/norm',[AdminOperationsController::class,'norm'])->name('operations.norm.update');
    Route::post('/operations/water',[AdminOperationsController::class,'water'])->name('operations.water.store');Route::patch('/operations/alerts/{alert}',[AdminOperationsController::class,'acknowledge'])->name('operations.alerts.update');
    Route::post('/operations/log',[AdminOperationsController::class,'operation'])->name('operations.log.store');Route::post('/operations/checklists',[AdminOperationsController::class,'checklist'])->name('operations.checklists.store');
    Route::post('/operations/checklists/{checklist}/run',[AdminOperationsController::class,'runChecklist'])->name('operations.checklists.run');Route::post('/operations/chemicals',[AdminOperationsController::class,'chemicalUsage'])->name('operations.chemicals.store');

    Route::get('/incidents',[AdminIncidentController::class,'index'])->name('incidents.index');Route::post('/incidents',[AdminIncidentController::class,'store'])->name('incidents.store');Route::patch('/incidents/{incident}',[AdminIncidentController::class,'update'])->name('incidents.update');

    Route::get('/orders',[AdminOrderController::class,'index'])->name('orders.index');Route::patch('/orders/{order}',[AdminOrderController::class,'update'])->name('orders.update');
    Route::get('/schedule',[AdminScheduleController::class,'index'])->name('schedule.index');Route::post('/schedule',[AdminScheduleController::class,'store'])->name('schedule.store');Route::delete('/schedule/{slot}',[AdminScheduleController::class,'destroy'])->name('schedule.destroy');
    Route::get('/trainers',[AdminTrainerController::class,'index'])->name('trainers.index');Route::post('/trainers',[AdminTrainerController::class,'store'])->name('trainers.store');Route::patch('/trainers/{trainer}',[AdminTrainerController::class,'update'])->name('trainers.update');Route::delete('/trainers/{trainer}',[AdminTrainerController::class,'destroy'])->name('trainers.destroy');
    Route::get('/products',[AdminProductController::class,'index'])->name('products.index');Route::post('/products',[AdminProductController::class,'store'])->name('products.store');Route::patch('/products/{product}',[AdminProductController::class,'update'])->name('products.update');
    Route::get('/leads',[AdminLeadController::class,'index'])->name('leads.index');Route::patch('/leads/{lead}',[AdminLeadController::class,'update'])->name('leads.update');
    Route::get('/news',[AdminNewsController::class,'index'])->name('news.index');Route::post('/news',[AdminNewsController::class,'store'])->name('news.store');Route::patch('/news/{post}',[AdminNewsController::class,'update'])->name('news.update');Route::delete('/news/{post}',[AdminNewsController::class,'destroy'])->name('news.destroy');
    Route::get('/gallery',[AdminGalleryController::class,'index'])->name('gallery.index');Route::post('/gallery/albums',[AdminGalleryController::class,'storeAlbum'])->name('gallery.albums.store');Route::patch('/gallery/albums/{album}',[AdminGalleryController::class,'updateAlbum'])->name('gallery.albums.update');Route::delete('/gallery/albums/{album}',[AdminGalleryController::class,'destroyAlbum'])->name('gallery.albums.destroy');Route::post('/gallery/albums/{album}/photos',[AdminGalleryController::class,'storePhoto'])->name('gallery.photos.store');Route::patch('/gallery/photos/{photo}',[AdminGalleryController::class,'updatePhoto'])->name('gallery.photos.update');Route::delete('/gallery/photos/{photo}',[AdminGalleryController::class,'destroyPhoto'])->name('gallery.photos.destroy');
    Route::get('/slides',[AdminHeroSlideController::class,'index'])->name('slides.index');Route::post('/slides',[AdminHeroSlideController::class,'store'])->name('slides.store');Route::patch('/slides/{slide}',[AdminHeroSlideController::class,'update'])->name('slides.update');Route::delete('/slides/{slide}',[AdminHeroSlideController::class,'destroy'])->name('slides.destroy');
    Route::get('/certificates/scan',[AdminCertificateController::class,'scan'])->name('certificates.scan');Route::get('/certificates',[AdminCertificateController::class,'index'])->name('certificates.index');Route::post('/certificates',[AdminCertificateController::class,'store'])->name('certificates.store');Route::patch('/certificates/{certificate}',[AdminCertificateController::class,'update'])->name('certificates.update');Route::post('/certificates/{certificate}/redeem',[AdminCertificateController::class,'redeem'])->name('certificates.redeem');
    Route::get('/training-plans',[AdminTrainingPlanController::class,'index'])->name('training-plans.index');Route::post('/training-plans',[AdminTrainingPlanController::class,'store'])->name('training-plans.store');Route::patch('/training-plans/{plan}',[AdminTrainingPlanController::class,'update'])->name('training-plans.update');Route::delete('/training-plans/{plan}',[AdminTrainingPlanController::class,'destroy'])->name('training-plans.destroy');Route::post('/training-plans/{plan}/items',[AdminTrainingPlanController::class,'storeItem'])->name('training-plans.items.store');Route::delete('/training-plan-items/{item}',[AdminTrainingPlanController::class,'destroyItem'])->name('training-plans.items.destroy');Route::post('/training-plans/{plan}/progress',[AdminTrainingPlanController::class,'storeProgress'])->name('training-plans.progress.store');

    Route::get('/pool',[AdminPoolController::class,'index'])->name('pool.index');Route::post('/pool/zones',[AdminPoolController::class,'storeZone'])->name('pool.zones.store');Route::post('/pool/lanes',[AdminPoolController::class,'storeLane'])->name('pool.lanes.store');Route::patch('/pool/lanes/{lane}',[AdminPoolController::class,'updateLane'])->name('pool.lanes.update');Route::post('/pool/slots/{slot}/lanes',[AdminPoolController::class,'assignLane'])->name('pool.slots.lanes.store');Route::delete('/pool/slots/{slot}/lanes/{lane}',[AdminPoolController::class,'detachLane'])->name('pool.slots.lanes.destroy');Route::post('/pool/slots/{slot}/waitlist',[AdminPoolController::class,'waitlist'])->name('pool.waitlist.store');Route::post('/pool/slots/{slot}/waitlist/{entry}/promote',[AdminPoolController::class,'promoteWaitlist'])->name('pool.waitlist.promote');Route::post('/pool/water',[AdminPoolController::class,'storeWater'])->name('pool.water.store');Route::post('/pool/maintenance',[AdminPoolController::class,'storeMaintenance'])->name('pool.maintenance.store');Route::patch('/pool/maintenance/{task}',[AdminPoolController::class,'updateMaintenance'])->name('pool.maintenance.update');
    Route::get('/memberships',[AdminMembershipController::class,'index'])->name('memberships.index');Route::post('/membership-plans',[AdminMembershipController::class,'storePlan'])->name('memberships.plans.store');Route::post('/memberships',[AdminMembershipController::class,'store'])->name('memberships.store');Route::patch('/memberships/{membership}',[AdminMembershipController::class,'update'])->name('memberships.update');Route::post('/memberships/{membership}/freeze',[AdminMembershipController::class,'freeze'])->name('memberships.freeze');Route::post('/customers/{customer}/wallet',[AdminMembershipController::class,'wallet'])->name('memberships.wallet');
    Route::get('/access',[AdminAccessController::class,'index'])->name('access.index');Route::post('/access/checkin',[AdminAccessController::class,'checkin'])->name('access.checkin');Route::post('/access/lockers',[AdminAccessController::class,'storeLocker'])->name('access.lockers.store');Route::post('/customers/{customer}/access-cards',[AdminAccessController::class,'issueCard'])->name('access.cards.store');Route::post('/customers/{customer}/medical-clearances',[AdminAccessController::class,'medical'])->name('access.medical.store');Route::post('/access/locker-rentals',[AdminAccessController::class,'assignLocker'])->name('access.lockers.assign');Route::post('/access/locker-rentals/{rental}/return',[AdminAccessController::class,'returnLocker'])->name('access.lockers.return');
    Route::get('/finance',[AdminFinanceController::class,'index'])->name('finance.index');Route::post('/finance/registers',[AdminFinanceController::class,'storeRegister'])->name('finance.registers.store');Route::post('/finance/registers/{register}/open',[AdminFinanceController::class,'openShift'])->name('finance.shifts.open');Route::post('/finance/shifts/{shift}/close',[AdminFinanceController::class,'closeShift'])->name('finance.shifts.close');Route::post('/finance/transactions',[AdminFinanceController::class,'transaction'])->name('finance.transactions.store');
    Route::get('/staff',[AdminStaffController::class,'index'])->name('staff.index');Route::post('/staff/shifts',[AdminStaffController::class,'storeShift'])->name('staff.shifts.store');Route::patch('/staff/shifts/{shift}',[AdminStaffController::class,'updateShift'])->name('staff.shifts.update');Route::post('/staff/payroll-rules',[AdminStaffController::class,'storeRule'])->name('staff.rules.store');Route::post('/staff/payroll/calculate',[AdminStaffController::class,'calculate'])->name('staff.payroll.calculate');Route::post('/staff/payroll/{accrual}/pay',[AdminStaffController::class,'pay'])->name('staff.payroll.pay');
    Route::get('/inventory',[AdminInventoryController::class,'index'])->name('inventory.index');Route::post('/inventory',[AdminInventoryController::class,'store'])->name('inventory.store');Route::post('/inventory/{item}/batches',[AdminInventoryController::class,'storeBatch'])->name('inventory.batches.store');Route::post('/inventory/{item}/movements',[AdminInventoryController::class,'movement'])->name('inventory.movements.store');
    Route::get('/crm-plus',[AdminCrmPlusController::class,'index'])->name('crm-plus.index');Route::post('/crm-plus/tasks',[AdminCrmPlusController::class,'task'])->name('crm-plus.tasks.store');Route::post('/crm-plus/tasks/{task}/complete',[AdminCrmPlusController::class,'complete'])->name('crm-plus.tasks.complete');Route::post('/crm-plus/interactions',[AdminCrmPlusController::class,'interaction'])->name('crm-plus.interactions.store');Route::post('/crm-plus/campaigns',[AdminCrmPlusController::class,'campaign'])->name('crm-plus.campaigns.store');Route::post('/crm-plus/campaigns/{campaign}/launch',[AdminCrmPlusController::class,'launch'])->name('crm-plus.campaigns.launch');Route::post('/crm-plus/corporates',[AdminCrmPlusController::class,'corporate'])->name('crm-plus.corporates.store');Route::post('/crm-plus/corporates/{account}/members',[AdminCrmPlusController::class,'corporateMember'])->name('crm-plus.corporates.members.store');Route::post('/crm-plus/templates',[AdminCrmPlusController::class,'template'])->name('crm-plus.templates.store');Route::post('/crm-plus/documents',[AdminCrmPlusController::class,'document'])->name('crm-plus.documents.store');Route::post('/crm-plus/documents/{document}/sign',[AdminCrmPlusController::class,'sign'])->name('crm-plus.documents.sign');
    Route::get('/reports',[AdminReportsController::class,'index'])->name('reports.index');

    Route::get('/settings',[AdminSettingsController::class,'general'])->name('settings.general');Route::patch('/settings',[AdminSettingsController::class,'updateGeneral'])->name('settings.general.update');Route::get('/settings/contacts',[AdminSettingsController::class,'contacts'])->name('settings.contacts');Route::patch('/settings/contacts',[AdminSettingsController::class,'updateContacts'])->name('settings.contacts.update');Route::get('/seo',[AdminSeoController::class,'index'])->name('seo.index');Route::post('/seo',[AdminSeoController::class,'store'])->name('seo.store');Route::patch('/seo/{seoPage}',[AdminSeoController::class,'update'])->name('seo.update');Route::delete('/seo/{seoPage}',[AdminSeoController::class,'destroy'])->name('seo.destroy');
});
