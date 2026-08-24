<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Department;
use App\Models\Position;
use App\Models\Municipality;
use App\Models\Barangay;
use App\Models\Classification;
use App\Models\AssessmentLevel;
use App\Models\TaxType;
use App\Models\SystemSetting;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Permissions
        $permissions = \App\Http\Controllers\UserController::permissionCatalog();

        foreach ($permissions as $perm) {
            Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'web']);
        }

        // Roles
        $superAdmin = Role::firstOrCreate(['name' => 'Super Administrator', 'guard_name' => 'web']);
        $superAdmin->syncPermissions($permissions);

        $assessor = Role::firstOrCreate(['name' => 'Municipal Assessor', 'guard_name' => 'web']);
        $assessor->syncPermissions([
            'tax-declarations.view', 'tax-declarations.approve', 'tax-declarations.archive',
            'field-appraisals.view', 'properties.view', 'ocr.view', 'gis.view',
            'workflow.view', 'archive.view', 'reports.view', 'reports.export',
            'documents.view', 'audit.view',
        ]);

        $supervisor = Role::firstOrCreate(['name' => 'Supervisor', 'guard_name' => 'web']);
        $supervisor->syncPermissions([
            'tax-declarations.view', 'tax-declarations.approve',
            'field-appraisals.view', 'properties.view', 'ocr.view', 'gis.view',
            'workflow.view', 'reports.view', 'documents.view',
        ]);

        $encoder = Role::firstOrCreate(['name' => 'Encoder', 'guard_name' => 'web']);
        $encoder->syncPermissions([
            'tax-declarations.view', 'tax-declarations.create', 'tax-declarations.edit',
            'field-appraisals.view', 'field-appraisals.create', 'field-appraisals.edit',
            'properties.view', 'properties.create', 'properties.edit',
            'ocr.view', 'ocr.upload', 'ocr.scan', 'ocr.correct',
            'gis.view', 'gis.edit', 'documents.view', 'documents.upload',
            'workflow.view',
        ]);

        $viewer = Role::firstOrCreate(['name' => 'Viewer', 'guard_name' => 'web']);
        $viewer->syncPermissions([
            'tax-declarations.view', 'field-appraisals.view', 'properties.view',
            'reports.view',
        ]);

        // Departments
        $assessorDept = Department::firstOrCreate(['name' => "Assessor's Office"], ['code' => 'AO', 'is_active' => true]);
        $itDept = Department::firstOrCreate(['name' => 'Information Technology'], ['code' => 'IT', 'is_active' => true]);

        // Positions
        $assessorPos = Position::firstOrCreate(['name' => 'Municipal Assessor', 'department_id' => $assessorDept->id], ['code' => 'MA']);
        $encoderPos = Position::firstOrCreate(['name' => 'Records Officer', 'department_id' => $assessorDept->id], ['code' => 'RO']);
        $adminPos = Position::firstOrCreate(['name' => 'System Administrator', 'department_id' => $itDept->id], ['code' => 'SA']);

        // Default municipality
        $municipality = Municipality::firstOrCreate(
            ['name' => 'Municipality of Sample'],
            ['province' => 'Sample Province', 'region' => 'Region X', 'zip_code' => '9000', 'is_active' => true]
        );

        // Barangays
        $barangayNames = ['Barangay 1', 'Barangay 2', 'Barangay 3', 'Barangay 4', 'Barangay 5',
            'Barangay 6', 'Barangay 7', 'Barangay 8', 'Barangay 9', 'Barangay 10'];
        foreach ($barangayNames as $i => $name) {
            Barangay::firstOrCreate(['name' => $name, 'municipality_id' => $municipality->id], [
                'code' => str_pad($i + 1, 3, '0', STR_PAD_LEFT),
                'latitude' => 8.0 + ($i * 0.01),
                'longitude' => 125.0 + ($i * 0.01),
            ]);
        }

        // Classifications
        $classifications = [
            ['name' => 'Residential', 'code' => 'RES', 'assessment_rate' => 20, 'color' => '#10B981'],
            ['name' => 'Commercial', 'code' => 'COM', 'assessment_rate' => 50, 'color' => '#3B82F6'],
            ['name' => 'Agricultural', 'code' => 'AGR', 'assessment_rate' => 40, 'color' => '#F59E0B'],
            ['name' => 'Industrial', 'code' => 'IND', 'assessment_rate' => 50, 'color' => '#EF4444'],
            ['name' => 'Special', 'code' => 'SPE', 'assessment_rate' => 15, 'color' => '#8B5CF6'],
            ['name' => 'Mineral', 'code' => 'MIN', 'assessment_rate' => 50, 'color' => '#6B7280'],
            ['name' => 'Timberland', 'code' => 'TIM', 'assessment_rate' => 20, 'color' => '#065F46'],
        ];

        foreach ($classifications as $class) {
            $c = Classification::firstOrCreate(['name' => $class['name']], array_merge($class, ['is_active' => true]));
            AssessmentLevel::firstOrCreate(
                ['classification_id' => $c->id, 'name' => 'Standard'],
                ['assessment_rate' => $class['assessment_rate'], 'is_active' => true]
            );
        }

        // Tax Types
        TaxType::firstOrCreate(['name' => 'Basic Real Property Tax'], ['code' => 'BRPT', 'rate' => 0.01, 'is_active' => true]);
        TaxType::firstOrCreate(['name' => 'Special Education Fund'], ['code' => 'SEF', 'rate' => 0.01, 'is_active' => true]);

        // System Settings
        $settings = [
            ['key' => 'app_name', 'value' => 'TDMS', 'group' => 'general'],
            ['key' => 'municipality_name', 'value' => 'Municipality of Sample', 'group' => 'general'],
            ['key' => 'ocr_enabled', 'value' => 'true', 'group' => 'ocr'],
            ['key' => 'max_upload_size', 'value' => '20', 'group' => 'general'],
            ['key' => 'session_timeout', 'value' => '60', 'group' => 'security'],
            ['key' => 'backup_schedule', 'value' => 'daily', 'group' => 'backup'],
        ];
        foreach ($settings as $setting) {
            SystemSetting::firstOrCreate(['key' => $setting['key']], $setting);
        }

        // Admin User
        $admin = User::firstOrCreate(
            ['email' => 'admin@tdms.gov.ph'],
            [
                'name' => 'System Administrator',
                'password' => Hash::make('Admin@123'),
                'department_id' => $itDept->id,
                'position_id' => $adminPos->id,
                'employee_id' => 'EMP-001',
                'status' => 'active',
            ]
        );
        $admin->assignRole('Super Administrator');
        $admin->syncPermissions($permissions);

        // Assessor User
        $assessorUser = User::firstOrCreate(
            ['email' => 'assessor@tdms.gov.ph'],
            [
                'name' => 'Municipal Assessor',
                'password' => Hash::make('Assessor@123'),
                'department_id' => $assessorDept->id,
                'position_id' => $assessorPos->id,
                'employee_id' => 'EMP-002',
                'status' => 'active',
            ]
        );
        $assessorUser->assignRole('Municipal Assessor');
        $assessorUser->syncPermissions($assessor->permissions);

        // Encoder User
        $encoderUser = User::firstOrCreate(
            ['email' => 'encoder@tdms.gov.ph'],
            [
                'name' => 'Records Encoder',
                'password' => Hash::make('Encoder@123'),
                'department_id' => $assessorDept->id,
                'position_id' => $encoderPos->id,
                'employee_id' => 'EMP-003',
                'status' => 'active',
            ]
        );
        $encoderUser->assignRole('Encoder');
        $encoderUser->syncPermissions($encoder->permissions);
    }
}
