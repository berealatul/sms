# 📁 SMS Frontend Documentation Overview

## 🎯 Files for Frontend Teams

| File                                      | Purpose                    | Target Audience     |
| ----------------------------------------- | -------------------------- | ------------------- |
| **`FRONTEND-README.md`**                  | Complete integration guide | Frontend developers |
| **`docs/frontend-quick-reference.md`**    | Quick API reference        | All developers      |
| **`docs/frontend-example.html`**          | Working HTML example       | Beginners           |
| **`docs/frontend-package-template.json`** | React project template     | React developers    |
| **`docs/postman-collection.json`**        | API testing collection     | QA/Testing teams    |

## 🚀 Getting Started Steps

### 1. **Read the Documentation**

Start with `FRONTEND-README.md` for complete integration guide.

### 2. **Test the API**

- Use `docs/frontend-example.html` - Open in browser and test
- Import `docs/postman-collection.json` into Postman for API testing

### 3. **Quick Reference**

Keep `docs/frontend-quick-reference.md` handy for common endpoints and patterns.

### 4. **React Setup** (Optional)

Use `docs/frontend-package-template.json` as starting point for React projects.

## 🔑 Key Information

### API Base URL

```
http://localhost/sms/api/
```

### Test Credentials

```javascript
// Admin (Full access)
{ email: 'admin@gmail.com', password: 'adminpass' }

// HOD (Department management)
{ email: 'hod.cse@gmail.com', password: 'adminpass' }
```

### Authentication Flow

1. Login with POST `/auth/login`
2. Store JWT token from response
3. Include token in all requests: `Authorization: Bearer {token}`

### User Roles & Access

- **ADMIN**: Can manage all departments
- **HOD**: Can manage users/programmes/batches in their department
- **FACULTY/STAFF/STUDENT**: Read-only access

## 🛠️ Essential Endpoints

| Endpoint       | Method | Access | Purpose                 |
| -------------- | ------ | ------ | ----------------------- |
| `/auth/login`  | POST   | Public | Get JWT token           |
| `/auth/me`     | GET    | All    | Get current user info   |
| `/auth/me`     | PUT    | All    | Update profile/password |
| `/departments` | GET    | All    | List departments        |
| `/departments` | POST   | Admin  | Create department       |
| `/users`       | GET    | HOD    | List department users   |
| `/users`       | POST   | HOD    | Create user             |

## ⚠️ Important Notes

### Password Updates

❌ **Wrong:** `{ "password": "newpass" }`
✅ **Correct:** `{ "current_password": "old", "new_password": "new" }`

### Error Handling

- `401`: Token invalid → Redirect to login
- `403`: No permission → Show access denied
- `400`: Bad request → Check input validation

### CORS

API is configured for `http://localhost:3000` (React default port)

## 🧪 Testing Strategy

1. **Manual Testing**: Use `frontend-example.html`
2. **API Testing**: Use Postman collection
3. **Role Testing**: Test with different user types
4. **Error Testing**: Test invalid tokens, wrong permissions

## 📞 Support

If you encounter issues:

1. Check main `README.md` for detailed API documentation
2. Verify XAMPP is running with Apache + MySQL
3. Test with Postman collection first
4. Check browser console for CORS/network errors

Happy coding! 🎉
