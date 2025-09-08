# SMS Frontend Integration Guide

## 🚀 Quick Start for Frontend Teams

This guide provides everything frontend developers need to integrate with the SMS (Student Monitoring System) API.

## 📋 Table of Contents

- [API Overview](#api-overview)
- [Authentication Flow](#authentication-flow)
- [User Roles & Permissions](#user-roles--permissions)
- [API Endpoints](#api-endpoints)
- [Frontend Implementation Examples](#frontend-implementation-examples)
- [Error Handling](#error-handling)
- [Testing](#testing)

## 🌐 API Overview

**Base URL:** `http://localhost/sms/api/`

**Content Type:** `application/json`

**Authentication:** JWT Bearer Token

**CORS:** Configured for `http://localhost:3000`

## 🔐 Authentication Flow

### 1. Login Process

```javascript
// Login API call
const login = async (email, password) => {
  const response = await fetch("http://localhost/sms/api/auth/login", {
    method: "POST",
    headers: {
      "Content-Type": "application/json",
    },
    body: JSON.stringify({ email, password }),
  });

  if (response.ok) {
    const data = await response.json();
    // Store token in localStorage or secure storage
    localStorage.setItem("sms_token", data.token);
    return data.token;
  } else {
    throw new Error("Login failed");
  }
};
```

### 2. Using JWT Token

```javascript
// Get stored token
const getToken = () => localStorage.getItem("sms_token");

// API call with authentication
const authenticatedFetch = async (url, options = {}) => {
  const token = getToken();

  return fetch(url, {
    ...options,
    headers: {
      "Content-Type": "application/json",
      Authorization: `Bearer ${token}`,
      ...options.headers,
    },
  });
};
```

### 3. Get Current User Info

```javascript
const getCurrentUser = async () => {
  const response = await authenticatedFetch("http://localhost/sms/api/auth/me");
  if (response.ok) {
    return await response.json();
  }
  throw new Error("Failed to get user info");
};
```

## 👥 User Roles & Permissions

| Role        | Access Level     | Available Features                           |
| ----------- | ---------------- | -------------------------------------------- |
| **ADMIN**   | System-wide      | All department management                    |
| **HOD**     | Department-level | Users, programmes, batches within department |
| **FACULTY** | Limited          | View department info                         |
| **STAFF**   | Limited          | View department info                         |
| **STUDENT** | Limited          | View department info                         |

## 🛠️ API Endpoints

### Authentication Endpoints

#### POST `/auth/login`

```javascript
// Login
fetch("http://localhost/sms/api/auth/login", {
  method: "POST",
  headers: { "Content-Type": "application/json" },
  body: JSON.stringify({
    email: "admin@gmail.com",
    password: "adminpass",
  }),
});
```

#### GET `/auth/me`

```javascript
// Get current user
authenticatedFetch("http://localhost/sms/api/auth/me");
```

#### PUT `/auth/me`

```javascript
// Update profile (name/email only)
authenticatedFetch("http://localhost/sms/api/auth/me", {
  method: "PUT",
  body: JSON.stringify({
    full_name: "Updated Name",
    email: "new@email.com",
  }),
});

// Update password
authenticatedFetch("http://localhost/sms/api/auth/me", {
  method: "PUT",
  body: JSON.stringify({
    current_password: "oldpass",
    new_password: "newpass123",
  }),
});
```

### Department Management (Admin Only)

#### GET `/departments`

```javascript
// Get all departments
const getDepartments = async () => {
  const response = await authenticatedFetch(
    "http://localhost/sms/api/departments"
  );
  return await response.json();
};
```

#### POST `/departments`

```javascript
// Create department
const createDepartment = async (departmentData) => {
  const response = await authenticatedFetch(
    "http://localhost/sms/api/departments",
    {
      method: "POST",
      body: JSON.stringify({
        department_code: "EEE",
        department_name: "Electrical Engineering",
        hod_email: "hod.eee@gmail.com",
      }),
    }
  );
  return await response.json();
};
```

#### GET `/departments/{id}`

```javascript
// Get specific department
const getDepartment = async (id) => {
  const response = await authenticatedFetch(
    `http://localhost/sms/api/departments/${id}`
  );
  return await response.json();
};
```

#### PUT `/departments/{id}`

```javascript
// Update department
const updateDepartment = async (id, updates) => {
  const response = await authenticatedFetch(
    `http://localhost/sms/api/departments/${id}`,
    {
      method: "PUT",
      body: JSON.stringify(updates),
    }
  );
  return await response.json();
};
```

#### DELETE `/departments/{id}`

```javascript
// Delete department
const deleteDepartment = async (id) => {
  const response = await authenticatedFetch(
    `http://localhost/sms/api/departments/${id}`,
    {
      method: "DELETE",
    }
  );
  return await response.json();
};
```

### User Management (HOD Only)

#### GET `/users`

```javascript
// Get users in HOD's department
const getUsers = async () => {
  const response = await authenticatedFetch("http://localhost/sms/api/users");
  return await response.json();
};
```

#### POST `/users`

```javascript
// Create single user
const createUser = async (userData) => {
  const response = await authenticatedFetch("http://localhost/sms/api/users", {
    method: "POST",
    body: JSON.stringify({
      user_type: "FACULTY",
      email: "faculty@gmail.com",
    }),
  });
  return await response.json();
};
```

#### POST `/users/bulk`

```javascript
// Create multiple users
const createBulkUsers = async (users) => {
  const response = await authenticatedFetch(
    "http://localhost/sms/api/users/bulk",
    {
      method: "POST",
      body: JSON.stringify({
        users: [
          { user_type: "STUDENT", email: "student1@gmail.com" },
          { user_type: "FACULTY", email: "faculty1@gmail.com" },
        ],
      }),
    }
  );
  return await response.json();
};
```

#### PUT `/users/{id}`

```javascript
// Update user
const updateUser = async (id, updates) => {
  const response = await authenticatedFetch(
    `http://localhost/sms/api/users/${id}`,
    {
      method: "PUT",
      body: JSON.stringify({
        full_name: "Updated Name",
        user_type: "STAFF",
      }),
    }
  );
  return await response.json();
};
```

#### PUT `/users/activate`

```javascript
// Activate multiple users
const activateUsers = async (userIds) => {
  const response = await authenticatedFetch(
    "http://localhost/sms/api/users/activate",
    {
      method: "PUT",
      body: JSON.stringify({
        user_ids: [5, 6, 7],
      }),
    }
  );
  return await response.json();
};
```

#### DELETE `/users/{id}`

```javascript
// Delete user
const deleteUser = async (id) => {
  const response = await authenticatedFetch(
    `http://localhost/sms/api/users/${id}`,
    {
      method: "DELETE",
    }
  );
  return await response.json();
};
```

### Programme Management (HOD Only)

#### GET `/programmes`

```javascript
// Get programmes
const getProgrammes = async () => {
  const response = await authenticatedFetch(
    "http://localhost/sms/api/programmes"
  );
  return await response.json();
};
```

#### POST `/programmes`

```javascript
// Create programme
const createProgramme = async (programmeData) => {
  const response = await authenticatedFetch(
    "http://localhost/sms/api/programmes",
    {
      method: "POST",
      body: JSON.stringify({
        programme_name: "MSc Computer Science",
        degree_level_id: 2,
        minimum_duration_years: 2,
        maximum_duration_years: 4,
      }),
    }
  );
  return await response.json();
};
```

### Batch Management (HOD Only)

#### GET `/batches`

```javascript
// Get batches
const getBatches = async () => {
  const response = await authenticatedFetch("http://localhost/sms/api/batches");
  return await response.json();
};
```

#### POST `/batches`

```javascript
// Create batch
const createBatch = async (batchData) => {
  const response = await authenticatedFetch(
    "http://localhost/sms/api/batches",
    {
      method: "POST",
      body: JSON.stringify({
        programme_id: 1,
        batch_name: "2024 Spring Batch",
        start_year: 2024,
        start_semester: "SPRING",
      }),
    }
  );
  return await response.json();
};
```

## 🛡️ Error Handling

### Common HTTP Status Codes

| Code | Meaning      | Action                          |
| ---- | ------------ | ------------------------------- |
| 200  | Success      | Process response                |
| 201  | Created      | Resource created successfully   |
| 400  | Bad Request  | Check request format/validation |
| 401  | Unauthorized | Redirect to login               |
| 403  | Forbidden    | Show access denied message      |
| 404  | Not Found    | Show not found message          |
| 409  | Conflict     | Handle duplicate data           |
| 500  | Server Error | Show generic error message      |

### Error Response Format

```javascript
// All error responses follow this format:
{
  "error": "Descriptive error message"
}
```

### Example Error Handling

```javascript
const handleApiCall = async (apiFunction) => {
  try {
    const response = await apiFunction();

    if (!response.ok) {
      const errorData = await response.json();

      switch (response.status) {
        case 401:
          // Redirect to login
          localStorage.removeItem("sms_token");
          window.location.href = "/login";
          break;
        case 403:
          alert("Access denied. You do not have permission for this action.");
          break;
        case 404:
          alert("Resource not found.");
          break;
        case 409:
          alert(`Conflict: ${errorData.error}`);
          break;
        default:
          alert(`Error: ${errorData.error}`);
      }

      throw new Error(errorData.error);
    }

    return await response.json();
  } catch (error) {
    console.error("API call failed:", error);
    throw error;
  }
};
```

## 🧪 Testing

### Test Credentials

```javascript
// Available test users
const testUsers = {
  admin: {
    email: "admin@gmail.com",
    password: "adminpass",
    role: "ADMIN",
  },
  hod: {
    email: "hod.cse@gmail.com",
    password: "adminpass",
    role: "HOD",
  },
  faculty: {
    email: "faculty.cse@gmail.com",
    password: "adminpass",
    role: "FACULTY",
  },
  staff: {
    email: "staff.cse@gmail.com",
    password: "adminpass",
    role: "STAFF",
  },
  student: {
    email: "student.cse@gmail.com",
    password: "adminpass",
    role: "STUDENT",
  },
};
```

### Testing Different Roles

```javascript
// Test role-based access
const testRoleAccess = async () => {
  // Login as admin
  await login('admin@gmail.com', 'adminpass');

  // Should work: Get departments
  const departments = await getDepartments();
  console.log('Admin can access departments:', departments);

  // Login as HOD
  await login('hod.cse@gmail.com', 'adminpass');

  // Should work: Get users
  const users = await getUsers();
  console.log('HOD can access users:', users);

  // Should fail: Create department (HOD cannot do this)
  try {
    await createDepartment({...});
  } catch (error) {
    console.log('Expected error - HOD cannot create departments');
  }
};
```

## 📦 React Integration Example

### Context Provider

```javascript
// AuthContext.js
import React, { createContext, useContext, useState, useEffect } from "react";

const AuthContext = createContext();

export const useAuth = () => useContext(AuthContext);

export const AuthProvider = ({ children }) => {
  const [user, setUser] = useState(null);
  const [token, setToken] = useState(localStorage.getItem("sms_token"));
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    if (token) {
      getCurrentUser()
        .then(setUser)
        .catch(() => {
          localStorage.removeItem("sms_token");
          setToken(null);
        })
        .finally(() => setLoading(false));
    } else {
      setLoading(false);
    }
  }, [token]);

  const login = async (email, password) => {
    const response = await fetch("http://localhost/sms/api/auth/login", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ email, password }),
    });

    if (response.ok) {
      const data = await response.json();
      localStorage.setItem("sms_token", data.token);
      setToken(data.token);

      const userData = await getCurrentUser();
      setUser(userData);

      return userData;
    } else {
      throw new Error("Login failed");
    }
  };

  const logout = () => {
    localStorage.removeItem("sms_token");
    setToken(null);
    setUser(null);
  };

  return (
    <AuthContext.Provider
      value={{
        user,
        token,
        login,
        logout,
        loading,
        isAdmin: user?.user_type === "ADMIN",
        isHOD: user?.user_type === "HOD",
      }}
    >
      {children}
    </AuthContext.Provider>
  );
};
```

### Protected Route Component

```javascript
// ProtectedRoute.js
import React from "react";
import { useAuth } from "./AuthContext";

const ProtectedRoute = ({ children, requiredRole }) => {
  const { user, loading } = useAuth();

  if (loading) {
    return <div>Loading...</div>;
  }

  if (!user) {
    return <div>Please login to access this page.</div>;
  }

  if (requiredRole && user.user_type !== requiredRole) {
    return <div>Access denied. Required role: {requiredRole}</div>;
  }

  return children;
};

export default ProtectedRoute;
```

### API Service

```javascript
// apiService.js
const API_BASE = "http://localhost/sms/api";

const getToken = () => localStorage.getItem("sms_token");

const apiCall = async (endpoint, options = {}) => {
  const token = getToken();

  const response = await fetch(`${API_BASE}${endpoint}`, {
    headers: {
      "Content-Type": "application/json",
      ...(token && { Authorization: `Bearer ${token}` }),
      ...options.headers,
    },
    ...options,
  });

  if (!response.ok) {
    const errorData = await response.json();
    throw new Error(errorData.error || "API call failed");
  }

  return response.json();
};

export const authAPI = {
  login: (email, password) =>
    apiCall("/auth/login", {
      method: "POST",
      body: JSON.stringify({ email, password }),
    }),

  getCurrentUser: () => apiCall("/auth/me"),

  updateProfile: (data) =>
    apiCall("/auth/me", {
      method: "PUT",
      body: JSON.stringify(data),
    }),
};

export const departmentAPI = {
  getAll: () => apiCall("/departments"),
  getById: (id) => apiCall(`/departments/${id}`),
  create: (data) =>
    apiCall("/departments", {
      method: "POST",
      body: JSON.stringify(data),
    }),
  update: (id, data) =>
    apiCall(`/departments/${id}`, {
      method: "PUT",
      body: JSON.stringify(data),
    }),
  delete: (id) => apiCall(`/departments/${id}`, { method: "DELETE" }),
};

export const userAPI = {
  getAll: () => apiCall("/users"),
  create: (data) =>
    apiCall("/users", {
      method: "POST",
      body: JSON.stringify(data),
    }),
  createBulk: (users) =>
    apiCall("/users/bulk", {
      method: "POST",
      body: JSON.stringify({ users }),
    }),
  update: (id, data) =>
    apiCall(`/users/${id}`, {
      method: "PUT",
      body: JSON.stringify(data),
    }),
  delete: (id) => apiCall(`/users/${id}`, { method: "DELETE" }),
  activate: (userIds) =>
    apiCall("/users/activate", {
      method: "PUT",
      body: JSON.stringify({ user_ids: userIds }),
    }),
};
```

## 🚀 Getting Started

1. **Clone/Setup**: Ensure the SMS API is running on `http://localhost/sms/api/`

2. **Test Connection**:

   ```javascript
   fetch("http://localhost/sms/api/auth/login", {
     method: "POST",
     headers: { "Content-Type": "application/json" },
     body: JSON.stringify({
       email: "admin@gmail.com",
       password: "adminpass",
     }),
   })
     .then((response) => response.json())
     .then(console.log);
   ```

3. **Implement Authentication**: Use the provided context and service examples

4. **Add Role-Based Access**: Use the user role information to show/hide features

5. **Handle Errors**: Implement proper error handling for better UX

## 📞 Support

- **API Documentation**: See main `README.md` for detailed API specs
- **Postman Collection**: Import `docs/postman-collection.json` for testing
- **Test Script**: Run `docs/test-api-script.ps1` to verify API functionality

Happy coding! 🎉
