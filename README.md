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
4. Verify test users are created# SMS - Student Monitoring System

### 4. Security Features

- JWT authentication with custom implementation
- bcrypt password hashing
- Rate limiting (60 requests per minute per IP)
- Input validation and sanitization
- CORS configured for React frontend
- Security headers (XSS protection, content sniffing prevention)
- Request logging

### 5. API Endpoints

#### 🌐 API Base URL: `http://localhost/sms/api/`

#### POST /auth/login

**Request:**

```json
{
  "email": "admin@gmail.com",
  "password": "adminpass"
}
```

**Response:**

```json
{
  "token": "jwt_token_here"
}
```

#### GET /auth/me

**Headers:** `Authorization: Bearer {token}`
**Response:**

```json
{
  "user_type": "ADMIN",
  "department_id": null,
  "full_name": "System Admin",
  "email": "admin@gmail.com"
}
```

#### PUT /auth/me

**Headers:** `Authorization: Bearer {token}`
**Request (all fields optional):**

```json
{
  "full_name": "Updated Name",
  "email": "newemail@gmail.com",
  "current_password": "oldpassword",
  "new_password": "newpassword123"
}
```

**Response:**

```json
{
  "message": "Profile updated successfully"
}
```

### Department Management (Admin Only)

#### GET /departments

**Headers:** `Authorization: Bearer {token}` (any authenticated user)
**Response:**

```json
[
  {
    "department_id": 1,
    "department_code": "CSE",
    "department_name": "Computer Science and Engineering",
    "hod_name": "CSE HOD",
    "hod_email": "hod.cse@gmail.com"
  }
]
```

#### GET /departments/{id}

**Headers:** `Authorization: Bearer {token}` (any authenticated user)
**Response:**

```json
{
  "department_id": 1,
  "department_code": "CSE",
  "department_name": "Computer Science and Engineering",
  "hod_name": "CSE HOD",
  "hod_email": "hod.cse@gmail.com"
}
```

#### POST /departments

**Headers:** `Authorization: Bearer {token}` (Admin only)
**Request:**

```json
{
  "department_code": "EEE",
  "department_name": "Electrical and Electronic Engineering",
  "hod_email": "hod.eee@gmail.com"
}
```

**Response:**

```json
{
  "message": "Department created successfully",
  "department_id": 3,
  "department_code": "EEE",
  "department_name": "Electrical and Electronic Engineering",
  "hod_email": "hod.eee@gmail.com"
}
```

#### PUT /departments/{id}

**Headers:** `Authorization: Bearer {token}` (Admin only)
**Request (all fields optional):**

```json
{
  "department_code": "CS",
  "department_name": "Computer Science",
  "hod_email": "newhod.cs@gmail.com"
}
```

**Response:**

```json
{
  "message": "Department updated successfully"
}
```

#### DELETE /departments/{id}

**Headers:** `Authorization: Bearer {token}` (Admin only)
**Response:**

```json
{
  "message": "Department deleted successfully"
}
```

### User Management (HOD, Staff, Faculty Only)

#### GET /users

**Headers:** `Authorization: Bearer {token}` (HOD, Staff, Faculty only)
**Query Parameters:** `?type=STUDENT` (optional - filter by user type)
**Valid types:** `STUDENT`, `FACULTY`, `STAFF`, `HOD`

**Response:**

```json
[
  {
    "user_id": 3,
    "user_type": "FACULTY",
    "full_name": "John Doe",
    "email": "john.doe@gmail.com",
    "is_active": true
  }
]
```

**Examples:**

- `GET /users` - Get all users in department
- `GET /users?type=STUDENT` - Get only students in department
- `GET /users?type=FACULTY` - Get only faculty in department

#### GET /users/{id}

**Headers:** `Authorization: Bearer {token}` (HOD, Staff, Faculty only)
**Response:**

```json
{
  "user_id": 3,
  "user_type": "FACULTY",
  "full_name": "John Doe",
  "email": "john.doe@gmail.com",
  "is_active": true,
  "department_id": 1,
  "department_name": "Computer Science and Engineering",
  "department_code": "CSE"
}
```

#### POST /users/find

**Headers:** `Authorization: Bearer {token}` (any authenticated user)
**Request (provide at least one field):**

```json
{
  "roll_number": "CSE2020001",
  "email": "student@gmail.com"
}
```

**Response:**

```json
{
  "user_id": 5,
  "user_type": "STUDENT",
  "full_name": "John Student",
  "email": "student@gmail.com",
  "roll_number": "CSE2020001",
  "department_code": "CSE",
  "department_name": "Computer Science and Engineering"
}
```

**Search Options:**

- Search by `roll_number` only (for students)
- Search by `email` only (for any user type)
- Search by both `roll_number` and `email` (exact match)

#### POST /users

**Headers:** `Authorization: Bearer {token}` (HOD only)
**Request:**

```json
{
  "user_type": "FACULTY",
  "email": "new.faculty@gmail.com"
}
```

#### POST /users/bulk

**Headers:** `Authorization: Bearer {token}` (HOD only)
**Request:**

```json
{
  "users": [
    { "user_type": "STUDENT", "email": "student1@gmail.com" },
    { "user_type": "FACULTY", "email": "faculty1@gmail.com" }
  ]
}
```

#### PUT /users/{id}

**Headers:** `Authorization: Bearer {token}` (HOD can update anyone, STAFF can update students only)
**Request (all fields optional):**

```json
{
  "full_name": "Updated Name",
  "email": "updated@email.com",
  "user_type": "STAFF",
  "is_active": true,
  "reset_password": true
}
```

**Response:**

```json
{
  "message": "User updated successfully. Password has been reset to user email."
}
```

**Permissions:**

- **HOD**: Can update any user in their department (including faculty details and user types)
- **STAFF**: Can only update student details in their department
- **Password Reset**: Set `"reset_password": true` to reset password to user's email address

#### DELETE /users/{id}

**Headers:** `Authorization: Bearer {token}` (HOD only)

#### PUT /users/activate

**Headers:** `Authorization: Bearer {token}` (HOD only)
**Description:** Bulk activate multiple users
**Request:**

```json
{
  "user_ids": [5, 6, 7]
}
```

**Response:**

```json
{
  "message": "Successfully activated 2 users",
  "activated_count": 2,
  "total_requested": 3,
  "errors": ["User ID 7 is already active"]
}
```

#### PUT /users/deactivate

**Headers:** `Authorization: Bearer {token}` (HOD only)
**Description:** Bulk deactivate multiple users
**Request:**

```json
{
  "user_ids": [5, 6, 7]
}
```

**Response:**

```json
{
  "message": "Successfully deactivated 3 users",
  "deactivated_count": 3,
  "total_requested": 3
}
```

#### PUT /users/{id}/activate

**Headers:** `Authorization: Bearer {token}` (HOD only)
**Description:** Activate a single user
**Response:**

```json
{
  "message": "User activated successfully",
  "user_id": 5,
  "email": "user@gmail.com"
}
```

#### PUT /users/{id}/deactivate

**Headers:** `Authorization: Bearer {token}` (HOD only)
**Description:** Deactivate a single user
**Response:**

```json
{
  "message": "User deactivated successfully",
  "user_id": 5,
  "email": "user@gmail.com"
}
```

### Degree Management

#### GET /degrees

**Headers:** `Authorization: Bearer {token}` (any authenticated user)
**Response:**

```json
[
  {
    "degree_level_id": 1,
    "level_name": "Undergraduate"
  },
  {
    "degree_level_id": 2,
    "level_name": "Postgraduate"
  }
]
```

#### GET /degrees/{id}

**Headers:** `Authorization: Bearer {token}` (any authenticated user)
**Response:**

```json
{
  "degree_level_id": 1,
  "level_name": "Undergraduate"
}
```

#### POST /degrees

**Headers:** `Authorization: Bearer {token}` (HOD only)
**Request:**

```json
{
  "level_name": "Doctorate"
}
```

**Response:**

```json
{
  "message": "Degree level created successfully",
  "degree_level_id": 4,
  "level_name": "Doctorate"
}
```

#### PUT /degrees/{id}

**Headers:** `Authorization: Bearer {token}` (HOD only)
**Request:**

```json
{
  "level_name": "Updated Degree Name"
}
```

#### DELETE /degrees/{id}

**Headers:** `Authorization: Bearer {token}` (HOD only)

### Programme Management (Department-Specific)

#### GET /programmes

**Headers:** `Authorization: Bearer {token}` (any authenticated user from department)
**Response:**

```json
[
  {
    "programme_id": 1,
    "programme_name": "BSc Computer Science",
    "minimum_duration_years": 4,
    "maximum_duration_years": 6,
    "is_active": true,
    "degree_level": "Undergraduate",
    "department_code": "CSE",
    "department_name": "Computer Science and Engineering"
  }
]
```

#### GET /programmes/{id}

**Headers:** `Authorization: Bearer {token}` (any authenticated user from department)

#### POST /programmes

**Headers:** `Authorization: Bearer {token}` (HOD only)
**Request:**

```json
{
  "programme_name": "PhD Computer Science",
  "degree_level_id": 3,
  "minimum_duration_years": 3,
  "maximum_duration_years": 6
}
```

#### PUT /programmes/{id}

**Headers:** `Authorization: Bearer {token}` (HOD only)
**Request:**

```json
{
  "is_active": false
}
```

#### DELETE /programmes/{id}

**Headers:** `Authorization: Bearer {token}` (HOD only)

### Batch Management (Department-Specific)

#### GET /batches

**Headers:** `Authorization: Bearer {token}` (any authenticated user from department)
**Response:**

```json
[
  {
    "batch_id": 1,
    "batch_name": "2024 Autumn Batch",
    "start_year": 2024,
    "start_semester": "AUTUMN",
    "is_active": true,
    "programme_name": "BSc Computer Science",
    "programme_id": 1,
    "department_code": "CSE",
    "department_name": "Computer Science and Engineering"
  }
]
```

#### GET /batches/{id}

**Headers:** `Authorization: Bearer {token}` (any authenticated user from department)

#### POST /batches

**Headers:** `Authorization: Bearer {token}` (HOD, Staff only)
**Request:**

```json
{
  "programme_id": 1,
  "batch_name": "2024 Autumn Batch",
  "start_year": 2024,
  "start_semester": "AUTUMN"
}
```

#### PUT /batches/{id}

**Headers:** `Authorization: Bearer {token}` (HOD, Staff only)
**Request:**

```json
{
  "is_active": false
}
```

#### DELETE /batches/{id}

**Headers:** `Authorization: Bearer {token}` (HOD only)

### Test Users (from schema.sql)

- **Admin:** admin@gmail.com / adminpass (No department)
- **HOD:** hod.cse@gmail.com / adminpass (CSE Department)
- **Faculty:** faculty.cse@gmail.com / adminpass (CSE Department)
- **Staff:** staff.cse@gmail.com / adminpass (CSE Department)
- **Student:** student.cse@gmail.com / adminpass (CSE Department)

### Architecture

- **Modular Design:** Separate controllers, middleware, services, and utilities
- **Security First:** Input validation, rate limiting, proper error handling
- **Database:** Singleton pattern with prepared statements
- **JWT:** Custom implementation without external dependencies
- **SOLID Principles:** Single responsibility, dependency injection ready
