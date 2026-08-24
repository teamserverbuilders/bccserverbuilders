<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\TaxDeclarationController;
use App\Http\Controllers\PropertyOwnerController;
use App\Http\Controllers\GisController;
use App\Http\Controllers\OcrController;
use App\Http\Controllers\OcrManagementController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\AuditController;
use App\Http\Controllers\FieldAppraisalController;
use App\Http\Controllers\WorkflowController;
use App\Http\Controllers\ArchiveController;
use App\Http\Controllers\PropertyImprovementController;
use App\Http\Controllers\PropertyLocationController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\OwnershipHistoryController;
use Illuminate\Support\Facades\Route;

// ------------------------------------------------------------------
// API Routes (web middleware group, CSRF excluded via bootstrap/app.php)
// ------------------------------------------------------------------
Route::prefix('api')->group(function () {

    // Public routes
    Route::post('/auth/login', [AuthController::class, 'login']);

    // QR Verification (public)
    Route::get('/verify/{tdNumber}', function (string $tdNumber) {
        $td = \App\Models\TaxDeclaration::where('td_number', $tdNumber)
            ->with(['owner', 'barangay', 'classification', 'gisLocation'])
            ->first();
        if (!$td) return response()->json(['message' => 'Record not found.'], 404);
        return response()->json($td);
    });

    // Authenticated routes
    Route::middleware('auth:sanctum')->group(function () {
        // Auth
        Route::post('/auth/logout', [AuthController::class, 'logout']);
        Route::get('/auth/me', [AuthController::class, 'me']);
        Route::post('/auth/change-password', [AuthController::class, 'changePassword']);
        Route::put('/auth/profile', [AuthController::class, 'updateProfile']);

        // Dashboard stats
        Route::get('/dashboard/statistics', [TaxDeclarationController::class, 'statistics']);

        // Global quick search (navbar)
        Route::get('/search', [SearchController::class, 'global']);

        // Tax Declarations
        Route::get('/tax-declarations/trashed', fn() => response()->json(
            \App\Models\TaxDeclaration::onlyTrashed()->with(['owner', 'classification'])->paginate(15)
        ));
        Route::post('/tax-declarations/{id}/restore', [TaxDeclarationController::class, 'restore']);
        Route::post('/tax-declarations/{taxDeclaration}/status', [TaxDeclarationController::class, 'updateStatus']);
        Route::post('/tax-declarations/{taxDeclaration}/unlock', [TaxDeclarationController::class, 'unlock']);
        Route::post('/tax-declarations/{taxDeclaration}/transfer-ownership', [TaxDeclarationController::class, 'transferOwnership']);
        Route::get('/tax-declarations/{taxDeclaration}/qr', [TaxDeclarationController::class, 'generateQr']);
        Route::get('/tax-declarations/{taxDeclaration}/pdf', [TaxDeclarationController::class, 'generatePdf']);
        Route::apiResource('tax-declarations', TaxDeclarationController::class);

        // Ownership transfer history
        Route::get('/ownership-history', [OwnershipHistoryController::class, 'index']);
        Route::get('/ownership-history/{ownershipHistory}', [OwnershipHistoryController::class, 'show']);

        // Archive (soft-deleted records)
        Route::get('/archive', [ArchiveController::class, 'index']);
        Route::get('/archive/counts', [ArchiveController::class, 'counts']);
        Route::post('/archive/{type}/{id}/restore', [ArchiveController::class, 'restore']);
        Route::delete('/archive/{type}/{id}', [ArchiveController::class, 'forceDestroy']);

        // Workflow
        Route::get('/workflow/board', [WorkflowController::class, 'board']);
        Route::get('/workflow/history', [WorkflowController::class, 'history']);

        // Property Owners
        Route::apiResource('property-owners', PropertyOwnerController::class);

        // Property Improvements
        Route::apiResource('property-improvements', PropertyImprovementController::class);

        // Property Locations
        Route::apiResource('property-locations', PropertyLocationController::class);

        // Field Appraisals
        Route::post('/field-appraisals/{fieldAppraisal}/photos', [FieldAppraisalController::class, 'uploadPhotos']);
        Route::post('/field-appraisals/{fieldAppraisal}/attachments', [FieldAppraisalController::class, 'uploadAttachments']);
        Route::post('/field-appraisals/{fieldAppraisal}/sketch', [FieldAppraisalController::class, 'uploadSketch']);
        Route::delete('/field-appraisals/{fieldAppraisal}/sketch', [FieldAppraisalController::class, 'deleteSketch']);
        Route::post('/field-appraisals/{fieldAppraisal}/approve', [FieldAppraisalController::class, 'approve']);
        Route::get('/field-appraisals/{fieldAppraisal}/pdf', [FieldAppraisalController::class, 'generatePdf']);
        Route::apiResource('field-appraisals', FieldAppraisalController::class);

        // GIS
        Route::get('/gis/map-properties', [GisController::class, 'mapProperties']);
        Route::get('/gis/barangay-layer', [GisController::class, 'barangayLayer']);
        Route::get('/gis/heatmap', [GisController::class, 'heatmap']);
        Route::get('/gis/tax-declarations/{taxDeclaration}', [GisController::class, 'show']);
        Route::get('/gis/field-appraisals/{fieldAppraisal}', [GisController::class, 'showFieldAppraisal']);
        Route::get('/gis/land/{taxDeclaration}', [GisController::class, 'getLand']);
        Route::post('/gis/land', [GisController::class, 'saveLand']);
        Route::delete('/gis/land/{taxDeclaration}', [GisController::class, 'deleteLand']);
        Route::apiResource('gis', GisController::class)->only(['index', 'store']);

        // OCR – shared primitives (used by TD form + Field Appraisal form + OCR page)
        Route::post('/ocr/upload', [OcrController::class, 'upload']);
        Route::post('/ocr/{ocrResult}/scan', [OcrController::class, 'scan']);

        // OCR Management page – listing / review / batch / delete
        // (Note: literal routes MUST come before the {ocrResult} model-binding route.)
        Route::get('/ocr/extracted-td-numbers', [OcrManagementController::class, 'extractedTdNumbers']);
        Route::get('/ocr', [OcrManagementController::class, 'index']);
        Route::post('/ocr/batch-scan', [OcrManagementController::class, 'batchScan']);
        Route::post('/ocr/bulk-delete', [OcrManagementController::class, 'bulkDestroy']);
        Route::get('/ocr/{ocrResult}', [OcrManagementController::class, 'show']);
        Route::put('/ocr/{ocrResult}/correct', [OcrManagementController::class, 'correct']);
        Route::delete('/ocr/{ocrResult}', [OcrManagementController::class, 'destroy']);

        // Documents & Images
        Route::get('/documents', [DocumentController::class, 'listAll']);
        Route::post('/documents', [DocumentController::class, 'store']);
        Route::get('/tax-declarations/{taxDeclaration}/documents', [DocumentController::class, 'index']);
        Route::post('/tax-declarations/{taxDeclaration}/documents', [DocumentController::class, 'upload']);
        Route::get('/documents/{document}/download', [DocumentController::class, 'download']);
        Route::delete('/documents/{document}', [DocumentController::class, 'destroy']);

        Route::get('/tax-declarations/{taxDeclaration}/images', [DocumentController::class, 'getImages']);
        Route::post('/tax-declarations/{taxDeclaration}/images', [DocumentController::class, 'uploadImage']);
        Route::delete('/images/{image}', [DocumentController::class, 'deleteImage']);

        // Reports
        Route::get('/reports/property', [ReportController::class, 'propertyReport']);
        Route::get('/reports/barangay', [ReportController::class, 'barangayReport']);
        Route::get('/reports/assessment', [ReportController::class, 'assessmentReport']);
        Route::get('/reports/ocr-accuracy', [ReportController::class, 'ocrAccuracyReport']);
        Route::get('/reports/audit', [ReportController::class, 'auditReport']);
        Route::get('/reports/user-activity', [ReportController::class, 'userActivityReport']);
        Route::get('/reports/digitization', [ReportController::class, 'digitizationReport']);
        Route::get('/reports/export-pdf', [ReportController::class, 'exportPdf']);

        // Audit Trail
        Route::get('/audit/logs', [AuditController::class, 'auditLogs']);
        Route::get('/audit/login-logs', [AuditController::class, 'loginLogs']);
        Route::get('/audit/activity-logs', [AuditController::class, 'activityLogs']);

        // Users & RBAC
        Route::get('/users/roles', [UserController::class, 'roles']);
        Route::get('/users/permissions', [UserController::class, 'permissions']);
        Route::post('/users/roles', [UserController::class, 'storeRole']);
        Route::put('/users/roles/{role}', [UserController::class, 'updateRole']);
        Route::delete('/users/roles/{role}', [UserController::class, 'destroyRole']);
        Route::get('/users/departments', [UserController::class, 'departments']);
        Route::get('/users/positions', [UserController::class, 'positions']);
        Route::post('/users/{user}/reset-password', [UserController::class, 'resetPassword']);
        Route::post('/users/{user}/toggle-status', [UserController::class, 'toggleStatus']);
        Route::put('/users/{user}/permissions', [UserController::class, 'syncPermissions']);
        Route::apiResource('users', UserController::class);

        // Settings
        Route::get('/settings/municipalities', [SettingsController::class, 'municipalities']);
        Route::post('/settings/municipalities/resolve', [SettingsController::class, 'resolveMunicipality']);
        Route::get('/settings/barangays', [SettingsController::class, 'barangays']);
        Route::get('/settings/psgc/regions', [SettingsController::class, 'psgcRegions']);
        Route::get('/settings/psgc/provinces', [SettingsController::class, 'psgcProvinces']);
        Route::get('/settings/psgc/municipalities', [SettingsController::class, 'psgcMunicipalities']);
        Route::get('/settings/psgc/barangays', [SettingsController::class, 'psgcBarangays']);
        Route::post('/settings/barangays', [SettingsController::class, 'storeBarangay']);
        Route::post('/settings/barangays/bulk', [SettingsController::class, 'bulkStoreBarangays']);
        Route::delete('/settings/barangays/clear', [SettingsController::class, 'clearBarangays']);
        Route::delete('/settings/barangays/{barangay}', [SettingsController::class, 'deleteBarangay']);
        Route::put('/settings/barangays/{barangay}', [SettingsController::class, 'updateBarangay']);
        Route::get('/settings/classifications', [SettingsController::class, 'classifications']);
        Route::post('/settings/classifications', [SettingsController::class, 'storeClassification']);
        Route::get('/settings/assessment-levels', [SettingsController::class, 'assessmentLevels']);
        Route::get('/settings/tax-types', [SettingsController::class, 'taxTypes']);
        Route::post('/settings/departments', [SettingsController::class, 'storeDepartment']);
        Route::post('/settings/positions', [SettingsController::class, 'storePosition']);
    });
});

// ------------------------------------------------------------------
// SPA catch-all — API routes above always win; Vue Router handles the rest
// ------------------------------------------------------------------
Route::get('/{any?}', function () {
    return view('app');
})->where('any', '.*');
