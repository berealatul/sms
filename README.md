# 🎓 Student Monitoring System

A centralized, multi-tenant web application to streamline student data management, faculty-student workflows, and multi-stage approval systems across universities and departments.

---

## 🚀 Features

### 👤 Role-Based Dashboards
- **Admin**
  - Register faculty/students individually or via CSV
  - Assign faculty as Mentors/Guides/Supervisors (custom titles)
  - Track and approve student submissions by semester or student
  - Create and manage DC/DRC members
  - Monitor student progress and profile data
- **Faculty**
  - View assigned students
  - Approve/Reject student submissions
  - Request additional documents or revisions
  - Provide feedback, monitor progress
- **Student**
  - Upload personal and academic data
  - Track submission status and feedback
  - View assigned mentors/supervisors and authority hierarchy

### 🛠 Approval Workflow
- Multi-stage:
  - Student → Faculty → Admin
- Approval trail with comments, timestamps, and roles

### 🧑‍💼 Dynamic Profile Fields
- Admin can add new fields (e.g., "Profile Picture", "Research Area")
- Supports custom data types: text, image, video, audio

### 📂 Bulk Upload Support
- Register faculty and students via CSV upload
- Pre-assigned emails and initial passwords for first login

### 🌐 Multi-Tenant Architecture
- Each organization (university) has its own database
- Isolation of users, roles, and submissions per tenant

### 🔐 Secure Authentication & Password Reset
- Role-based login
- Reset system with security question

---

## 🏗️ Tech Stack

| Layer | Tech |
|-------|------|
| Backend | Django, Django REST Framework |
| Database | PostgreSQL (per-tenant) |
| Async Tasks | Celery + Redis |
| Frontend | Django Templates (optionally React/HTMX) |
| File Storage | Local / S3 (for uploads) |
| Deployment | Docker, Gunicorn, Nginx |

---

## ⚙️ Setup Instructions

### 1️⃣ Clone the Repo
```bash
git clone https://github.com/yourusername/student-monitoring-system.git
cd student-monitoring-system
```
### 2️⃣ Create Virtual Environment
```bash
python -m venv venv
source venv/bin/activate
pip install -r requirements.txt
```
### 3️⃣ Configure .env
- Create a .env file and configure:
```
DEBUG=True
SECRET_KEY=your_secret_key
DATABASE_URL=postgres://user:pass@localhost/db
REDIS_URL=redis://localhost:6379
```
### 4️⃣ Apply Migrations
```bash
python manage.py migrate
```
### 5️⃣ Run Server
```bash
python manage.py runserver
```

## 📁 Project Structure
```bash
monitoring_system/
├── accounts/          # Registration, login, dynamic profiles
├── admin_panel/       # Admin-level dashboards & tools
├── faculty_portal/    # Faculty dashboards & actions
├── student_portal/    # Student data entry & tracking
├── submissions/       # Approval flow and audit trail
├── assignments/       # Mentor/Guide/Supervisor logic
├── tenant/            # Multi-tenant logic (using django-tenants)
```

## 📊 Example Use Case
- University registers via /register/ → new tenant created

- Admin logs in and adds faculty/students (single or CSV)

- Students log in and fill personal/academic info

- Faculty review, approve, or request changes

- Admin gives final approval

- All actions are tracked via audit trail

## ✅ Future Roadmap
- 📬 Notification system for pending actions

- 📊 Analytics dashboard for admins

- 📎 Chat or comment threads for review cycle

- 📱 Mobile-friendly version / mobile app

📃 License
This project is licensed under the MIT License.

🧑‍💻 Maintainers
ATUL PRAKASH
