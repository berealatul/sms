# Student Monitoring System

A centralized web application to streamline student data collection, faculty-student interactions, and multi-level approval workflows in a university setting.

---

## Core Functionalities

1. **Role-Based Dashboards**

   - **Admin**:
     - Add/manage faculty and student accounts.
     - Mark faculty as **NON\_WORKING** and trigger automatic reassignment alerts.
     - Assign mentors, supervisors, and guides to each student.
     - Request submissions or additional requirements from users and track their status.
     - Final approval authority for all student data submissions.
   - **Faculty**:
     - View assigned students by role.
     - Request missing documents or clarifications from students.
     - Review submissions, comment, and request revisions.
     - Approve or reject data, forwarding approved entries to Admin.
     - Monitor student progress and provide ongoing feedback.
   - **Student**:
     - Upload and edit personal and academic details.
     - View assigned mentors, supervisors, guides, and Admin contacts.
     - Track submission statuses (Pending → Received → Approved/Rejected).
     - Read feedback and comments from Faculty and Admin.

2. **Multi-Stage Approval Workflow**

   - Student submits data → Faculty review → Admin final approval.
   - Comprehensive audit trail with timestamps and comments.

3. **Notifications & Tracking**

   - Real-time dashboard indicators for pending tasks and approvals.

4. **Data Management & Security**

   - MySQL-backed relational database for structured storage.
   - Django’s built-in protections against common web vulnerabilities.

---

## Tech Stack

- **Backend**: Python, Django
- **Database**: MySQL
- **Authentication**: JSON Web Tokens -> Google OAuth2 [Rree tier is limited to 100 user only by Google]
- **Frontend**: Django templates -> React

---

## Setup & Installation

1. Clone the repo:
   ```bash
   git clone https://github.com/berealatul/studentMonitoringSystem.git
   cd studentMonitoringSystem
   ```
2. Create and activate a virtual environment:
   ```bash
   python3 -m venv venv && source venv/bin/activate
   ```
3. Install dependencies:
   ```bash
   pip install -r requirements.txt
   ```
4. Configure environment variables in a `.env` file (see sample `.env.example`).
5. Create MySQL database and run migrations:
   ```bash
   mysql -u root -p -e "CREATE DATABASE student_monitoring_db;"
   python manage.py migrate
   ```
6. Create a superuser:
   ```bash
   python manage.py createsuperuser
   ```
7. Start the development server:
   ```bash
   python manage.py runserver
   ```
8. Visit `http://localhost:8000`.

---

## Repository Structure (To be restructured later on)

```
├── manage.py                      # Django management script
├── requirements.txt               # Python dependencies
├── .env.example                   # Sample environment variables file
├── README.md                      # Project documentation
├── university/                    # Root Django project folder
│   ├── settings.py                # Project settings and configurations
│   ├── urls.py                    # Root URL configuration (includes core_app.urls)
│   ├── asgi.py                    # ASGI entry point for async deployment
│   └── __init__.py
└── monitoring_system/             # Main application logic
    ├── models.py                  # Database models for users, submissions, roles, etc.
    ├── views.py                   # Views for handling web requests
    ├── urls.py                    # URL routing specific to this app (included in project URLs)
    ├── templates/                 # HTML templates for the web UI
    └── static/                    # Static files (CSS, JS, images)
```

**Assumptions Explained:**
- `university/` is the root configuration directory created when starting a Django project. It handles global settings and routing.
- `monitoring_system/` is the main Django app containing the business logic, views, models, and templates specific to the student monitoring system.
- `university/urls.py` includes a route to `monitoring_system/urls.py` so that URLs from the app are accessible via the main project.
- `asgi.py` is used instead of `wsgi.py` for compatibility with asynchronous features and modern deployment stacks (e.g., Daphne, Uvicorn).

---

## Contributing

1. Fork the repository.
2. Create a feature branch: `git checkout -b feature/YourFeature`
3. Commit your changes: `git commit -m "Add YourFeature"`
4. Push to origin: `git push origin feature/YourFeature`
5. Open a Pull Request.

---

## 📄 License

MIT License © Atul Prakash
