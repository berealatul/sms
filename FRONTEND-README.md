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

### 🔐 Authentication Endpoints

```javascript
// Login (any user type)
POST /auth/login
{
  "email": "admin@gmail.com", // or hod.cse@gmail.com, faculty.cse@gmail.com, etc.
  "password": "adminpass"
}

// Get current user info
GET /auth/me
Headers: { Authorization: "Bearer {token}" }

// Update profile (enhanced with password reset option)
PUT /auth/me
{
  "full_name": "Updated Name",
  "email": "new@email.com",
  "current_password": "oldpass",  // required for password change
  "new_password": "newpass123"
}
```

### 📚 Degree Management (NEW)

```javascript
// Get all degrees (any authenticated user)
GET /degrees

// Get specific degree (any authenticated user)
GET /degrees/{id}

// Create degree (HOD only)
POST /degrees
{
  "level_name": "Professional Diploma"
}

// Update degree (HOD only)
PUT /degrees/{id}
{
  "level_name": "Updated Degree Name"
}

// Delete degree (HOD only)
DELETE /degrees/{id}
```

### 🏢 Department Management (Updated Access)

```javascript
// Get all departments (any authenticated user - UPDATED)
GET / departments;

// Get specific department (any authenticated user - UPDATED)
GET / departments / { id };

// Create/Update/Delete department (Admin only - unchanged)
POST / departments;
PUT / departments / { id };
DELETE / departments / { id };
```

### 👥 User Management (Enhanced Access Controls)

```javascript
// Get all users in department (HOD, Staff, Faculty - EXPANDED ACCESS)
GET /users

// Get users by type (HOD, Staff, Faculty - NEW FILTERING)
GET /users?type=STUDENT
GET /users?type=FACULTY
GET /users?type=STAFF
GET /users?type=HOD

// Get specific user (HOD, Staff, Faculty - EXPANDED ACCESS)
GET /users/{id}

// Find user by credentials (any authenticated user)
POST /users/find
{
  "roll_number": "CSE2020001",  // optional
  "email": "student@gmail.com"  // optional - can use either or both
}

// Update user (ENHANCED PERMISSIONS)
PUT /users/{id}
{
  "full_name": "Updated Name",
  "email": "updated@email.com",
  "user_type": "STAFF",        // HOD only
  "is_active": true,
  "reset_password": true       // NEW: Reset password to email
}
// HOD: Can update anyone in department
// STAFF: Can only update students in department

// Individual user activation/deactivation (HOD only - NEW)
PUT /users/{id}/activate
PUT /users/{id}/deactivate

// Bulk user activation/deactivation (HOD only - ENHANCED)
PUT /users/activate
PUT /users/deactivate
{
  "user_ids": [5, 6, 7]
}

// Create/Delete users (HOD only - unchanged)
POST /users
POST /users/bulk
DELETE /users/{id}
```

### 🎓 Programme Management (Updated Access)

```javascript
// Get all programmes (any department user - EXPANDED ACCESS)
GET / programmes;

// Get specific programme (any department user - NEW)
GET / programmes / { id };

// Create/Update/Delete programme (HOD only - unchanged)
POST / programmes;
PUT / programmes / { id };
DELETE / programmes / { id };
```

### 📅 Batch Management (Enhanced Access)

```javascript
// Get all batches (any department user - EXPANDED ACCESS)
GET / batches;

// Get specific batch (any department user - NEW)
GET / batches / { id };

// Create/Update batch (HOD, Staff - EXPANDED ACCESS)
POST / batches;
PUT / batches / { id };

// Delete batch (HOD only - RESTRICTED)
DELETE / batches / { id };
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

## 🔄 Updated React Integration Examples

### Enhanced API Service with New Endpoints

```javascript
// api/smsApi.js - Updated with all new endpoints
class SMSApiService {
  constructor() {
    this.baseURL = "http://localhost/sms/api";
    this.token = localStorage.getItem("sms_token");
  }

  // Updated headers with CORS support
  getHeaders() {
    return {
      "Content-Type": "application/json",
      ...(this.token && { Authorization: `Bearer ${this.token}` }),
    };
  }

  // Authentication (enhanced)
  async login(email, password) {
    const response = await fetch(`${this.baseURL}/auth/login`, {
      method: "POST",
      headers: this.getHeaders(),
      body: JSON.stringify({ email, password }),
    });
    const data = await response.json();
    if (data.token) {
      this.token = data.token;
      localStorage.setItem("sms_token", data.token);
    }
    return data;
  }

  async updateProfile(profileData) {
    return this.makeRequest("/auth/me", "PUT", profileData);
  }

  // Degrees (NEW)
  async getDegrees() {
    return this.makeRequest("/degrees");
  }

  async createDegree(degreeData) {
    return this.makeRequest("/degrees", "POST", degreeData);
  }

  async updateDegree(id, degreeData) {
    return this.makeRequest(`/degrees/${id}`, "PUT", degreeData);
  }

  async deleteDegree(id) {
    return this.makeRequest(`/degrees/${id}`, "DELETE");
  }

  // Users (enhanced with new features)
  async getUsers(type = null) {
    const url = type ? `/users?type=${type}` : "/users";
    return this.makeRequest(url);
  }

  async getUser(id) {
    return this.makeRequest(`/users/${id}`);
  }

  async findUser(searchData) {
    return this.makeRequest("/users/find", "POST", searchData);
  }

  async updateUser(id, userData) {
    return this.makeRequest(`/users/${id}`, "PUT", userData);
  }

  async activateUser(id) {
    return this.makeRequest(`/users/${id}/activate`, "PUT");
  }

  async deactivateUser(id) {
    return this.makeRequest(`/users/${id}/deactivate`, "PUT");
  }

  async bulkActivateUsers(userIds) {
    return this.makeRequest("/users/activate", "PUT", { user_ids: userIds });
  }

  async bulkDeactivateUsers(userIds) {
    return this.makeRequest("/users/deactivate", "PUT", { user_ids: userIds });
  }

  // Programmes (enhanced access)
  async getProgrammes() {
    return this.makeRequest("/programmes");
  }

  async getProgramme(id) {
    return this.makeRequest(`/programmes/${id}`);
  }

  // Batches (enhanced access)
  async getBatches() {
    return this.makeRequest("/batches");
  }

  async getBatch(id) {
    return this.makeRequest(`/batches/${id}`);
  }

  // Helper method
  async makeRequest(endpoint, method = "GET", body = null) {
    const config = {
      method,
      headers: this.getHeaders(),
    };

    if (body && method !== "GET") {
      config.body = JSON.stringify(body);
    }

    const response = await fetch(`${this.baseURL}${endpoint}`, config);
    return response.json();
  }
}

export default new SMSApiService();
```

### Enhanced User Management Component

```javascript
// components/UserManagement.jsx - Updated with new features
import React, { useState, useEffect } from "react";
import smsApi from "../api/smsApi";

const UserManagement = () => {
  const [users, setUsers] = useState([]);
  const [userType, setUserType] = useState("");
  const [selectedUsers, setSelectedUsers] = useState([]);
  const [searchCriteria, setSearchCriteria] = useState({
    roll_number: "",
    email: "",
  });

  // Load users with optional filtering
  const loadUsers = async (type = null) => {
    try {
      const data = await smsApi.getUsers(type);
      setUsers(data);
    } catch (error) {
      console.error("Error loading users:", error);
    }
  };

  // Search user by roll number or email
  const searchUser = async () => {
    try {
      const searchData = {};
      if (searchCriteria.roll_number)
        searchData.roll_number = searchCriteria.roll_number;
      if (searchCriteria.email) searchData.email = searchCriteria.email;

      if (Object.keys(searchData).length === 0) {
        alert("Please provide either roll number or email");
        return;
      }

      const user = await smsApi.findUser(searchData);
      setUsers([user]); // Show found user
    } catch (error) {
      console.error("User not found:", error);
      alert("User not found");
    }
  };

  // Update user with password reset option
  const updateUser = async (userId, userData) => {
    try {
      await smsApi.updateUser(userId, userData);
      loadUsers(userType);
      alert("User updated successfully");
    } catch (error) {
      console.error("Error updating user:", error);
    }
  };

  // Individual user activation/deactivation
  const toggleUserStatus = async (userId, activate) => {
    try {
      if (activate) {
        await smsApi.activateUser(userId);
      } else {
        await smsApi.deactivateUser(userId);
      }
      loadUsers(userType);
      alert(`User ${activate ? "activated" : "deactivated"} successfully`);
    } catch (error) {
      console.error("Error updating user status:", error);
    }
  };

  // Bulk operations
  const bulkUpdateStatus = async (activate) => {
    if (selectedUsers.length === 0) {
      alert("Please select users first");
      return;
    }

    try {
      if (activate) {
        await smsApi.bulkActivateUsers(selectedUsers);
      } else {
        await smsApi.bulkDeactivateUsers(selectedUsers);
      }
      setSelectedUsers([]);
      loadUsers(userType);
      alert(`Users ${activate ? "activated" : "deactivated"} successfully`);
    } catch (error) {
      console.error("Error in bulk operation:", error);
    }
  };

  useEffect(() => {
    loadUsers();
  }, []);

  return (
    <div className="user-management">
      <h2>User Management</h2>

      {/* User Type Filter */}
      <div className="filters">
        <select
          value={userType}
          onChange={(e) => {
            setUserType(e.target.value);
            loadUsers(e.target.value || null);
          }}
        >
          <option value="">All Users</option>
          <option value="STUDENT">Students</option>
          <option value="FACULTY">Faculty</option>
          <option value="STAFF">Staff</option>
          <option value="HOD">HOD</option>
        </select>
      </div>

      {/* Search Section */}
      <div className="search-section">
        <h3>Search User</h3>
        <input
          type="text"
          placeholder="Roll Number"
          value={searchCriteria.roll_number}
          onChange={(e) =>
            setSearchCriteria({
              ...searchCriteria,
              roll_number: e.target.value,
            })
          }
        />
        <input
          type="email"
          placeholder="Email"
          value={searchCriteria.email}
          onChange={(e) =>
            setSearchCriteria({ ...searchCriteria, email: e.target.value })
          }
        />
        <button onClick={searchUser}>Search</button>
        <button
          onClick={() => {
            setSearchCriteria({ roll_number: "", email: "" });
            loadUsers(userType);
          }}
        >
          Clear
        </button>
      </div>

      {/* Bulk Actions */}
      <div className="bulk-actions">
        <button
          onClick={() => bulkUpdateStatus(true)}
          disabled={selectedUsers.length === 0}
        >
          Activate Selected
        </button>
        <button
          onClick={() => bulkUpdateStatus(false)}
          disabled={selectedUsers.length === 0}
        >
          Deactivate Selected
        </button>
      </div>

      {/* Users Table */}
      <table>
        <thead>
          <tr>
            <th>
              <input
                type="checkbox"
                onChange={(e) => {
                  if (e.target.checked) {
                    setSelectedUsers(users.map((u) => u.user_id));
                  } else {
                    setSelectedUsers([]);
                  }
                }}
              />
            </th>
            <th>Name</th>
            <th>Email</th>
            <th>Type</th>
            <th>Status</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          {users.map((user) => (
            <tr key={user.user_id}>
              <td>
                <input
                  type="checkbox"
                  checked={selectedUsers.includes(user.user_id)}
                  onChange={(e) => {
                    if (e.target.checked) {
                      setSelectedUsers([...selectedUsers, user.user_id]);
                    } else {
                      setSelectedUsers(
                        selectedUsers.filter((id) => id !== user.user_id)
                      );
                    }
                  }}
                />
              </td>
              <td>{user.full_name}</td>
              <td>{user.email}</td>
              <td>{user.user_type}</td>
              <td>{user.is_active ? "Active" : "Inactive"}</td>
              <td>
                <button
                  onClick={() =>
                    toggleUserStatus(user.user_id, !user.is_active)
                  }
                >
                  {user.is_active ? "Deactivate" : "Activate"}
                </button>
                <button
                  onClick={() =>
                    updateUser(user.user_id, { reset_password: true })
                  }
                >
                  Reset Password
                </button>
              </td>
            </tr>
          ))}
        </tbody>
      </table>
    </div>
  );
};

export default UserManagement;
```

### New Degree Management Component

```javascript
// components/DegreeManagement.jsx - NEW COMPONENT
import React, { useState, useEffect } from "react";
import smsApi from "../api/smsApi";

const DegreeManagement = () => {
  const [degrees, setDegrees] = useState([]);
  const [newDegree, setNewDegree] = useState({ level_name: "" });
  const [editingDegree, setEditingDegree] = useState(null);

  const loadDegrees = async () => {
    try {
      const data = await smsApi.getDegrees();
      setDegrees(data);
    } catch (error) {
      console.error("Error loading degrees:", error);
    }
  };

  const createDegree = async () => {
    try {
      await smsApi.createDegree(newDegree);
      setNewDegree({ level_name: "" });
      loadDegrees();
      alert("Degree created successfully");
    } catch (error) {
      console.error("Error creating degree:", error);
    }
  };

  const updateDegree = async (id, degreeData) => {
    try {
      await smsApi.updateDegree(id, degreeData);
      setEditingDegree(null);
      loadDegrees();
      alert("Degree updated successfully");
    } catch (error) {
      console.error("Error updating degree:", error);
    }
  };

  const deleteDegree = async (id) => {
    if (confirm("Are you sure you want to delete this degree?")) {
      try {
        await smsApi.deleteDegree(id);
        loadDegrees();
        alert("Degree deleted successfully");
      } catch (error) {
        console.error("Error deleting degree:", error);
      }
    }
  };

  useEffect(() => {
    loadDegrees();
  }, []);

  return (
    <div className="degree-management">
      <h2>Degree Management</h2>

      {/* Create New Degree */}
      <div className="create-section">
        <h3>Create New Degree</h3>
        <input
          type="text"
          placeholder="Degree Level Name"
          value={newDegree.level_name}
          onChange={(e) => setNewDegree({ level_name: e.target.value })}
        />
        <button onClick={createDegree}>Create Degree</button>
      </div>

      {/* Degrees List */}
      <table>
        <thead>
          <tr>
            <th>ID</th>
            <th>Level Name</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          {degrees.map((degree) => (
            <tr key={degree.degree_level_id}>
              <td>{degree.degree_level_id}</td>
              <td>
                {editingDegree === degree.degree_level_id ? (
                  <input
                    type="text"
                    defaultValue={degree.level_name}
                    onBlur={(e) =>
                      updateDegree(degree.degree_level_id, {
                        level_name: e.target.value,
                      })
                    }
                  />
                ) : (
                  degree.level_name
                )}
              </td>
              <td>
                <button
                  onClick={() => setEditingDegree(degree.degree_level_id)}
                >
                  Edit
                </button>
                <button onClick={() => deleteDegree(degree.degree_level_id)}>
                  Delete
                </button>
              </td>
            </tr>
          ))}
        </tbody>
      </table>
    </div>
  );
};

export default DegreeManagement;
```
