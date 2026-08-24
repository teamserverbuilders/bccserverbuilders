# TDMS — Tax Declaration Management System
**OCR-Based Digital Archiving with GIS Map Pinning**
*Municipal / City Assessor's Office*

---

## Technology Stack

| Layer | Technology |
|---|---|
| Frontend | Vue.js 3 (SPA) |
| UI Framework | PrimeVue 4 + Tailwind CSS v4 |
| Backend | Laravel 12 |
| Database | MySQL 8 |
| Authentication | Laravel Sanctum |
| Authorization | Spatie Laravel Permission (RBAC) |
| OCR | Google Cloud Vision API |
| Maps | Leaflet.js + OpenStreetMap |
| QR Code | SimpleSoftwareIO QR Code |
| PDF Generation | Laravel DomPDF |
| Charts | Chart.js via vue-chartjs |

---

## Default Login Credentials

| Role | Email | Password |
|---|---|---|
| Super Administrator | admin@tdms.gov.ph | Admin@123 |
| Municipal Assessor | assessor@tdms.gov.ph | Assessor@123 |
| Encoder | encoder@tdms.gov.ph | Encoder@123 |

---

## Quick Start

### 1. Requirements
- PHP 8.2+
- Node.js 18+
- MySQL 8 or MariaDB 10.6+
- Composer 2

### 2. Setup
```bash
# Install PHP dependencies
composer install

# Copy environment file
copy .env.example .env

# Generate app key
php artisan key:generate

# Configure database in .env
# DB_DATABASE=tdms
# DB_USERNAME=root
# DB_PASSWORD=yourpassword

# Run migrations
php artisan migrate

# Seed default data (roles, permissions, users, barangays, classifications)
php artisan db:seed

# Create storage symlink
php artisan storage:link

# Install frontend dependencies
npm install

# Build for production
npm run build
```

### 3. Run Development Server
```bash
# Option A: Run separately
php artisan serve
npm run dev

# Option B: Run together (using composer script)
composer run dev
```

---

## Features

### Modules
1. **Authentication** — Login, 2FA, session management, account lockout
2. **Dashboard** — Live stats, charts, digitization progress, system health
3. **Tax Declarations** — Full CRUD, OCR upload, workflow, QR codes, PDF export
4. **Property Management** — Owner info, location, improvements, images
5. **GIS Mapping** — Interactive Leaflet map, pin properties, barangay layers
6. **OCR Management** — Google Cloud Vision, field extraction, batch processing
7. **Document Management** — Upload, versioning, digital verification
8. **Search Center** — Quick & advanced search across all records
9. **Reports** — Property, assessment, OCR accuracy, audit reports with PDF export
10. **Workflow Management** — Kanban-style status tracking, approval pipeline
11. **User Management** — Departments, positions, roles, permissions
12. **Audit Trail** — Complete activity logs, login history
13. **Analytics** — Charts, growth trends, GIS heatmap
14. **QR Verification** — Public scan-to-verify page
15. **System Settings** — Municipality profile, barangays, classifications

### Workflow Pipeline
```
Draft → OCR Processing → OCR Review → Encoder Review → Assessor Verification → Supervisor Approval → Released → Archived
```

### Database Tables (31 tables)
`users`, `roles`, `permissions`, `departments`, `positions`, `municipalities`, `barangays`,
`classifications`, `assessment_levels`, `tax_types`, `property_owners`, `tax_declarations`,
`property_locations`, `property_improvements`, `property_images`, `property_documents`,
`property_versions`, `ocr_results`, `ocr_logs`, `gis_locations`, `workflow_history`,
`approval_history`, `duplicate_records`, `notifications`, `audit_logs`, `login_logs`,
`backup_logs`, `system_settings`, `activity_logs`

---

## OCR Configuration

Add your Google Cloud Vision API key to `.env`:
```
GOOGLE_VISION_API_KEY=your_api_key_here
```

Also add to `config/services.php`:
```php
'google_vision' => [
    'api_key' => env('GOOGLE_VISION_API_KEY'),
],
```

---

## API Endpoints

All API routes are prefixed with `/api/`. Authentication uses Bearer tokens from Laravel Sanctum.

```
POST   /api/auth/login
GET    /api/dashboard/statistics
GET    /api/tax-declarations
POST   /api/tax-declarations
GET    /api/tax-declarations/{id}
PUT    /api/tax-declarations/{id}
POST   /api/tax-declarations/{id}/status
GET    /api/gis/map-properties
POST   /api/ocr/upload
POST   /api/ocr/{id}/scan
GET    /api/reports/property
GET    /api/users
...and 60+ more endpoints
```

---

## Security
- Role-Based Access Control (5 roles, 30+ permissions)
- Laravel Sanctum token authentication
- Password hashing (bcrypt)
- Account lockout after 5 failed attempts
- Complete audit trail on all operations
- Record locking for approved records
