# Software Requirements Specification (SRS) Document: Student Monitoring System

## 1. Introduction

### 1.1 Purpose

This SRS document outlines the functional and non-functional requirements for the **Student Monitoring System**, a centralized web application designed to streamline student data collection, enhance faculty-student interactions, and manage multi-level approval workflows in a university setting. It serves as a comprehensive guide for development, testing, and deployment.

### 1.2 Scope

The system will manage student personal and academic data, facilitate interactions between students and faculty (mentors, supervisors, guides), enable multi-stage data approval workflows, and provide administrative oversight for user management and final data approvals. It also covers notifications and secure data handling.

### 1.3 Intended Audience

This document is primarily for project stakeholders, including administrators, faculty, students, developers, quality assurance engineers, and project managers involved in the Student Monitoring System's lifecycle.

## 2. Overall Description

### 2.1 Product Perspective

The Student Monitoring System is a standalone web application, built using Django and MySQL, integrating Google OAuth for authentication. It provides role-based dashboards and workflows to manage student academic progress and related data.

### 2.2 User Characteristics

- **Students:** Users who will primarily upload and track their academic data, view feedback, and monitor approval statuses. They are expected to have basic web literacy.
- **Faculty:** Users (mentors, supervisors, guides) responsible for reviewing, commenting on, and approving student submissions. They require clear interfaces for efficient data management and feedback.
- **Administrators:** Users with full control over system configuration, user management, role assignments, and final data approvals. They need robust tools for oversight and data modification.

### 2.3 General Constraints

The system relies on Google OAuth for authentication. It must adhere to data privacy standards and provide a responsive web interface. The initial frontend will use Django templates, with potential future migration to React.

## 3. Specific Requirements

### 3.1 Functional Requirements

#### 3.1.1 Authentication

- **FR1.1 - Google OAuth Authentication:** The system must support seamless login for Admin, Faculty, and Students using Google OAuth.

#### 3.1.2 Administrator Module

- **FR2.1 - User Account Management:** Admin must be able to add and manage (activate/deactivate) faculty and student accounts.
- **FR2.2 - Faculty Status Management & Reassignment Alerts:** Admin must be able to mark faculty as **NON_WORKING**. When a faculty is marked as NON_WORKING, the system must automatically trigger an alert for the Admin to reassign all students associated with that faculty to another.
- **FR2.3 - Role Assignment:** Admin must be able to assign mentors, supervisors, and guides to each student. A single faculty member can be associated with multiple students in the same or different roles.
- **FR2.4 - Submission Requests & Tracking:** Admin must be able to request specific submissions or additional requirements from any user (student/faculty) and track the status of these requests.
- **FR2.5 - Final Approval Authority:** Admin must have the authority to provide the final approval for all student data submissions.
- **FR2.6 - Data Modification:** Admin must have comprehensive access to modify any data within the system.

#### 3.1.3 Faculty Module

- **FR3.1 - View Assigned Students:** Faculty must be able to view a list of students assigned to them, categorized by their specific role (mentor, supervisor, guide).
- **FR3.2 - Request Missing Information:** Faculty must be able to request missing documents or clarifications from students regarding their submissions.
- **FR3.3 - Submission Review and Feedback:** Faculty must be able to review student submissions, add comments, and request revisions if necessary.
- **FR3.4 - Data Approval/Rejection:** Faculty must be able to approve or reject student-submitted data. Approved data must be automatically forwarded to the Admin for final approval. Rejected data must be sent back to the student with comments.
- **FR3.5 - Progress Monitoring & Feedback:** Faculty must be able to monitor the progress of their assigned students and provide ongoing feedback and comments.

#### 3.1.4 Student Module

- **FR4.1 - Data Upload and Editing:** Students must be able to upload and edit their personal and academic details within the system.
- **FR4.2 - View Associated Contacts:** Students must be able to view their assigned mentors, supervisors, guides, and Admin contacts.
- **FR4.3 - Submission Status Tracking:** Students must be able to track the real-time status of their submissions (e.g., Pending → Received → Approved/Rejected).
- **FR4.4 - View Feedback and Comments:** Students must be able to read feedback, comments, and revision requests from Faculty and Admin.

#### 3.1.5 Multi-Stage Approval Workflow

- **FR5.1 - Workflow Automation:** The system must implement a defined workflow: Student submits data → Faculty reviews and approves/rejects → (if approved by Faculty) Admin provides final approval.
- **FR5.2 - Audit Trail:** The system must maintain a comprehensive audit trail for all data submissions and approvals, including timestamps and associated comments from each stage.

#### 3.1.6 Notifications & Tracking

- **FR6.1 - Real-time Indicators:** The system must provide real-time dashboard indicators for pending tasks, new submissions, and approval statuses for all user roles.

### 3.2 Non-Functional Requirements

#### 3.2.1 Performance Requirements

- **NFR1.1 - Response Time:** Key user interactions (login, data submission, approval) should complete within 3 seconds under typical load conditions.
- **NFR1.2 - Scalability:** The system architecture must support future scalability to accommodate a growing number of students and faculty without significant performance degradation.

#### 3.2.2 Security Requirements

- **NFR2.1 - Authentication Robustness:** Leverage Google OAuth2 via `django-allauth` for secure and robust user authentication.
- **NFR2.2 - Role-Based Access Control (RBAC):** Implement strict RBAC to ensure users only access functionalities and data commensurate with their assigned roles.
- **NFR2.3 - Data Protection:** Utilize Django's built-in protections against common web vulnerabilities (e.g., CSRF, XSS, SQL Injection). All sensitive data must be handled securely.
- **NFR2.4 - Data Encryption:** Data should be encrypted in transit using HTTPS.

#### 3.2.3 Data Management

- **NFR3.1 - Database System:** Use a MySQL-backed relational database for structured and reliable data storage.
- **NFR3.2 - Data Integrity:** Implement database constraints and application-level validation to maintain data integrity and consistency across all modules.

#### 3.2.4 Usability Requirements

- **NFR4.1 - Intuitive User Interface:** The system must offer an intuitive, clean, and consistent user interface across all dashboards (Admin, Faculty, Student) for ease of use.
- **NFR4.2 - Clear Feedback:** The system should provide clear and concise feedback to users on the status of their actions and any errors encountered.

#### 3.2.5 Reliability Requirements

- **NFR5.1 - System Availability:** The system should aim for high availability, minimizing downtime for scheduled maintenance and unexpected issues.
- **NFR5.2 - Error Handling:** Robust error handling mechanisms should be in place to gracefully manage exceptions and prevent data loss or system crashes.
