# Monitoring System Flow

<!-- Root Directory -->

## monitoring_system/

- **Register Your Organization**  
  Button forwards to: [`monitoring_system/register/`]

- **Login**  
  Button forwards to: [`monitoring_system/login/`]

---

<!-- Registration -->

## monitoring_system/register/

- **Fields Required:**

  - University Name
  - Department Name
  - Administrator Name
  - Email
  - Password

- **On Successful Registration:**
  - Forward to: [`monitoring_system/login/`]

---

<!-- Login -->

## monitoring_system/login/

- **Role Selection (Dropdown):**

  - Admin
  - Faculty
  - Student

- **Fields Required:**

  - Email
  - Password

- **Actions:**

  - **Login:**  
    Authenticate and forward to respective dashboard:

    - Admin: [`monitoring_system/admin/`]
    - Faculty: [`monitoring_system/faculty/`]
    - Student: [`monitoring_system/student/`]

  - **Reset Password:**

    - Show dropdown for role selection (Admin/Faculty/Student)
    - Email field

    <!-- Password Reset Verification Alternatives -->

    - **How to verify real user without OTP/email (cost-saving):**
      - Use security questions set during registration.
      - Allow reset only through admin intervention (manual verification).
      - Use existing university authentication (LDAP, SSO, etc.) but don't have any idea.

---

<!-- Admin Dashboard -->

## monitoring_system/admin/

- **Add Faculty:**  
  [`monitoring_system/admin/update_faculty/`]

- **Add Student:**  
  [`monitoring_system/admin/update_student/`]

- **Assign Faculty to Student as Mentor/Guide/Supervisor/VariableTitle:**  
  [`monitoring_system/admin/assign/`]  
  _Note: Store title as a variable string in the assignment table/model to handle variable titles._

- **Approve/Reject Student Submission:**  
  [`monitoring_system/admin/submissions/`]

  - View by semester, all students, or individual (by name/roll).

- **Create DC Member:**  
  [`monitoring_system/admin/dc_member/`]

- **Create DRC Member:**  
  [`monitoring_system/admin/drc_member/`]

- **See Student Progress:**  
  [`monitoring_system/admin/progress/`]

  - Filter by name/roll/all.

- **See Profile of Students:**  
  [`monitoring_system/admin/profile/`]

---

<!-- Faculty Dashboard -->

## monitoring_system/faculty/

- **Approve/Reject Student Submission:**  
  [`monitoring_system/faculty/submissions/`]

  - Even if approved, final approval required.
  - If multiple supervisors/guides, any one approval is sufficient.

- **Request Data from Student:**  
  [`monitoring_system/faculty/request/`]

- **See Assigned Student Progress:**  
  [`monitoring_system/faculty/progress/`]

  - Filter by name/roll/all.

- **See Profile of Students:**  
  [`monitoring_system/faculty/profile/`]

---

<!-- Student Dashboard -->

## monitoring_system/student/

- **Upload Personal Data:**  
  [`monitoring_system/student/personal/`]

- **Upload Academic Data:**  
  [`monitoring_system/student/academic/`]

- **See Progress:**  
  [`monitoring_system/student/progress/`]

- **See Authorities (Assigned Faculty, DC/DRC Members, etc.):**  
  [`monitoring_system/student/authorities/`]

- **See Pending Requests:**  
  [`monitoring_system/student/personal/pending/`]

---

<!-- End of Markdown Structure -->
