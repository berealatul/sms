# SMS - Student Monitoring System

## Authentication API Endpoints

### Security Features

- JWT authentication with custom implementation
- bcrypt password hashing
- Rate limiting (60 requests per minute per IP)
- Input validation and sanitization
- CORS configured for React frontend
- Security headers (XSS protection, content sniffing prevention)
- Request logging

### API Endpoints

#### POST /api/auth/login

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

#### GET /api/auth/me

**Headers:** `Authorization: Bearer {token}`
**Response:**

```json
{
  "user_type": "ADMIN",
  "department_id": 1,
  "full_name": "CoE Admin",
  "email": "admin@gmail.com"
}
```

### Test Users (from schema.sql)

- **Admin:** admin@gmail.com / password
- **HOD:** hod.cse@gmail.com / password
- **Faculty:** faculty.cse@gmail.com / password
- **Staff:** staff.cse@gmail.com / password
- **Student:** student.cse@gmail.com / password

### Architecture

- **Modular Design:** Separate controllers, middleware, services, and utilities
- **Security First:** Input validation, rate limiting, proper error handling
- **Database:** Singleton pattern with prepared statements
- **JWT:** Custom implementation without external dependencies
- **SOLID Principles:** Single responsibility, dependency injection ready
