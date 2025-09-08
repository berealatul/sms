# SMS - Student Monitoring System - Installation Guide

## Setup Instructions

### 1. Copy Files to XAMPP

Copy the entire `sms` folder to your XAMPP htdocs directory:

```
C:\xampp\htdocs\sms\
```

### 2. Directory Structure in htdocs

After copying, your structure should be:

```
C:\xampp\htdocs\sms\
├── index.html (React frontend will go here)
├── README.md
├── .gitignore
├── api/
│   ├── index.php (Main API entry point)
│   ├── config/
│   │   └── Database.php
│   ├── controllers/
│   │   ├── AuthController.php
│   │   ├── DepartmentController.php
│   │   ├── UserController.php
│   │   ├── ProgrammeController.php
│   │   └── BatchController.php
│   ├── middleware/
│   │   └── AuthMiddleware.php
│   ├── services/
│   │   └── JWTService.php
│   └── utils/
│       ├── Response.php
│       └── Security.php
└── docs/
    ├── schema.sql
    ├── description.txt
    └── postman.json
```

### 3. Database Setup

1. Start XAMPP (Apache + MySQL)
2. Open phpMyAdmin: `http://localhost/phpmyadmin`
3. Run the SQL from `docs/schema.sql` to create database and tables
4. Verify test users are created

### 4. API Access URLs

Once setup is complete, your API will be accessible at:

- **Base URL:** `http://localhost/sms/api/index.php`
- **Login:** `POST http://localhost/sms/api/auth/login`
- **User Info:** `GET http://localhost/sms/api/auth/me`
- **Departments:** `GET http://localhost/sms/api/departments`
- **Users:** `GET http://localhost/sms/api/users`

### 5. Frontend Access

- **Frontend:** `http://localhost/sms/index.html`
- React build files will go in the root `sms` directory
-

### 6. URL Rewriting (Optional)

For cleaner URLs without `index.php`, create `.htaccess` in the `api` folder:

```apache
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*)$ index.php [QSA,L]
```

This allows URLs like: `http://localhost/sms/api/auth/login`

### 7. Troubleshooting

- Ensure XAMPP Apache and MySQL are running
- Check PHP error logs in XAMPP control panel
- Verify database connection in `config/Database.php`
- Test with simple GET request to verify API is accessible
