-- Dropping the database if it exists to ensure a clean setup
DROP DATABASE IF EXISTS sms;

-- Creating the database
CREATE DATABASE sms;

-- Selecting the database to use
USE sms;

-- =============================================
-- SECTION 1: CORE INFRASTRUCTURE (DEPARTMENTS & USERS)
-- =============================================

CREATE TABLE departments (
    department_id   INT PRIMARY KEY AUTO_INCREMENT,
    department_code VARCHAR(20) UNIQUE NOT NULL,
    department_name VARCHAR(100) NOT NULL
);

CREATE TABLE user_accounts (
    user_id       INT PRIMARY KEY AUTO_INCREMENT,
    user_type     ENUM('ADMIN','HOD','FACULTY', 'STAFF', 'STUDENT') NOT NULL,
    department_id INT NOT NULL,
    full_name     VARCHAR(100) NOT NULL,
    email         VARCHAR(100) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    is_active     BOOLEAN DEFAULT TRUE,
    -- Foreign key to link users to a department. RESTRICT prevents deleting a department if users are still assigned to it.
    FOREIGN KEY (department_id) REFERENCES departments(department_id) ON DELETE RESTRICT
);

-- =============================================
-- SECTION 2: ACADEMIC STRUCTURE (DEGREES, PROGRAMMES, BATCHES)
-- =============================================

CREATE TABLE degree_levels (
    degree_level_id INT AUTO_INCREMENT PRIMARY KEY,
    level_name      VARCHAR(100) UNIQUE NOT NULL
);

CREATE TABLE programmes (
    programme_id            INT AUTO_INCREMENT PRIMARY KEY,
    programme_name          VARCHAR(255) NOT NULL UNIQUE,
    credit_specification_id INT NULL, -- Can be linked to a future table for curriculum details
    minimum_duration_years  INT NOT NULL,
    maximum_duration_years  INT NULL,
    degree_level_id         INT NOT NULL,
    is_active               BOOLEAN DEFAULT TRUE,
    FOREIGN KEY (degree_level_id) REFERENCES degree_levels(degree_level_id) ON DELETE RESTRICT
);

CREATE TABLE batches (
    batch_id       INT AUTO_INCREMENT PRIMARY KEY,
    programme_id   INT NOT NULL,
    batch_name     VARCHAR(50) NOT NULL, -- e.g., "2024 Autumn Batch"
    start_year     INT NOT NULL,
    start_semester ENUM("SPRING", "AUTUMN") NOT NULL,
    is_active      BOOLEAN DEFAULT TRUE,
    FOREIGN KEY (programme_id) REFERENCES programmes(programme_id) ON DELETE RESTRICT,
    UNIQUE (programme_id, batch_name)
);

-- =============================================
-- SECTION 3: USER PROFILES & DETAILS
-- =============================================

CREATE TABLE addresses (
    address_id     INT AUTO_INCREMENT PRIMARY KEY,
    user_id        INT NOT NULL,
    address_line_1 VARCHAR(255) NOT NULL,
    address_line_2 VARCHAR(255) NULL,
    city           VARCHAR(100) NOT NULL,
    state          VARCHAR(100) NOT NULL,
    postal_code    VARCHAR(20) NOT NULL,
    country        VARCHAR(100) NOT NULL,
    -- CASCADE ensures that if a user account is deleted, their associated addresses are also removed.
    FOREIGN KEY (user_id) REFERENCES user_accounts(user_id) ON DELETE CASCADE
);

CREATE TABLE student_profiles (
    user_id                INT PRIMARY KEY, -- 1-to-1 relationship with user_accounts
    roll_number            VARCHAR(20) UNIQUE NOT NULL,
    date_of_birth          DATE NULL,
    current_address_id     INT NULL,
    permanent_address_id   INT NULL,
    self_phone_number      VARCHAR(20) NULL,
    guardian_phone_number  VARCHAR(20) NULL,
    batch_id               INT NOT NULL,
    is_approved_admission  BOOLEAN DEFAULT FALSE,
    FOREIGN KEY (user_id) REFERENCES user_accounts(user_id) ON DELETE CASCADE,
    FOREIGN KEY (batch_id) REFERENCES batches(batch_id) ON DELETE RESTRICT,
    -- ON DELETE SET NULL is used here so that deleting an address doesn't delete the student profile.
    FOREIGN KEY (current_address_id) REFERENCES addresses(address_id) ON DELETE SET NULL,
    FOREIGN KEY (permanent_address_id) REFERENCES addresses(address_id) ON DELETE SET NULL
);

CREATE TABLE faculty_profiles (
    user_id        INT PRIMARY KEY, -- 1-to-1 relationship with user_accounts
    phone_number   VARCHAR(20) UNIQUE NULL,
    specialization VARCHAR(255) NULL,
    FOREIGN KEY (user_id) REFERENCES user_accounts(user_id) ON DELETE CASCADE
);

-- =============================================
-- SECTION 4: COMMITTEES & WORKFLOWS
-- =============================================

CREATE TABLE committees (
    committee_id     INT PRIMARY KEY AUTO_INCREMENT,
    committee_code   VARCHAR(50) NOT NULL UNIQUE, -- e.g., "GUIDE", "HOD", "DRC"
    committee_name   VARCHAR(100) NOT NULL,
    description      TEXT NULL,
    -- Defines the rule for a committee's decision-making process.
    approval_policy  ENUM("ANY_ONE", "MAJORITY", "ALL") DEFAULT "ALL"
);

CREATE TABLE committee_members (
    committee_id INT,
    user_id      INT,
    PRIMARY KEY (committee_id, user_id),
    FOREIGN KEY (committee_id) REFERENCES committees(committee_id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES user_accounts(user_id) ON DELETE CASCADE
);

-- Maps a generic committee role (code) to a specific committee for a student.
-- e.g., For Student X, the "GUIDE" role is fulfilled by Committee Y.
CREATE TABLE student_committee_assignments (
    user_id        INT NOT NULL,          -- The student's ID
    committee_code VARCHAR(50) NOT NULL,  -- The role, e.g., "GUIDE"
    committee_id   INT NOT NULL,          -- The specific committee assigned to that role
    PRIMARY KEY (user_id, committee_code),
    FOREIGN KEY (user_id) REFERENCES student_profiles(user_id) ON DELETE CASCADE,
    FOREIGN KEY (committee_id) REFERENCES committees(committee_id) ON DELETE RESTRICT
);

CREATE TABLE workflows (
    workflow_id   INT PRIMARY KEY AUTO_INCREMENT,
    workflow_name VARCHAR(100) NOT NULL UNIQUE,
    description   TEXT
);

-- Defines the ordered steps of a workflow, referencing the generic committee role.
CREATE TABLE workflow_steps (
    step_id        INT AUTO_INCREMENT PRIMARY KEY,
    workflow_id    INT NOT NULL,
    step_order     INT NOT NULL,          -- The sequence of the step (1, 2, 3...)
    committee_code VARCHAR(50) NOT NULL,  -- The role responsible for this step, e.g., "GUIDE"
    is_final_step  BOOLEAN DEFAULT FALSE, -- Indicates if this is the last step in the workflow
    FOREIGN KEY (workflow_id) REFERENCES workflows(workflow_id) ON DELETE CASCADE,
    UNIQUE (workflow_id, step_order)
);

-- =============================================
-- SECTION 5: REQUIREMENTS, SUBMISSIONS & APPROVALS
-- =============================================

CREATE TABLE requirements (
    requirement_id    INT PRIMARY KEY AUTO_INCREMENT,
    requirement_title VARCHAR(100) NOT NULL,
    fulfillment_mode  ENUM('ONLINE','OFFLINE','ANY') DEFAULT 'ANY',
    assigned_on       TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    due_date          DATE DEFAULT NULL,
    description       TEXT,
    workflow_id       INT NULL, -- If NULL, no approval workflow is needed. If set, it must be followed.
    FOREIGN KEY (workflow_id) REFERENCES workflows(workflow_id) ON DELETE SET NULL
);

CREATE TABLE requirement_assignments (
    requirement_id INT NOT NULL,
    user_id        INT NOT NULL,
    PRIMARY KEY (requirement_id, user_id),
    FOREIGN KEY (requirement_id) REFERENCES requirements(requirement_id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES user_accounts(user_id) ON DELETE CASCADE
);

CREATE TABLE submissions (
    submission_id  INT PRIMARY KEY AUTO_INCREMENT,
    requirement_id INT NOT NULL,
    user_id        INT NOT NULL, -- The user (student) who made the submission
    submitted_on   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    description    TEXT,
    overall_status ENUM('IN_PROGRESS', 'APPROVED', 'REJECTED', 'SUBMITTED') DEFAULT 'SUBMITTED',
    FOREIGN KEY (requirement_id) REFERENCES requirements(requirement_id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES user_accounts(user_id) ON DELETE CASCADE
);

CREATE TABLE submission_documents (
    document_id   INT PRIMARY KEY AUTO_INCREMENT,
    submission_id INT NOT NULL,
    document_type VARCHAR(50),
    file_path     VARCHAR(255) NOT NULL,
    uploaded_on   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (submission_id) REFERENCES submissions(submission_id) ON DELETE CASCADE
);

-- Tracks the detailed history of a submission through its assigned workflow.
CREATE TABLE submission_approval_log (
    log_id           INT AUTO_INCREMENT PRIMARY KEY,
    submission_id    INT NOT NULL,
    workflow_step_id INT NOT NULL,
    approver_user_id INT NOT NULL, -- The user from the committee who took the action
    committee_id     INT NOT NULL, -- The committee responsible for this step
    status           ENUM('PENDING','APPROVED','REJECTED') DEFAULT 'PENDING',
    remarks          TEXT NULL,
    processed_on     TIMESTAMP,
    FOREIGN KEY (submission_id) REFERENCES submissions(submission_id) ON DELETE CASCADE,
    FOREIGN KEY (workflow_step_id) REFERENCES workflow_steps(step_id) ON DELETE CASCADE,
    FOREIGN KEY (approver_user_id) REFERENCES user_accounts(user_id) ON DELETE RESTRICT,
    FOREIGN KEY (committee_id) REFERENCES committees(committee_id) ON DELETE RESTRICT,
    -- An approver can only have one status entry per step for a given submission
    UNIQUE (submission_id, workflow_step_id, approver_user_id)
);

-- =============================================
-- SECTION 6: SEED DATA
-- =============================================

INSERT INTO departments (department_code, department_name) VALUES
('COE', 'Controller Of Examination'),
('CSE', 'Computer Science and Engineering');

INSERT INTO user_accounts (user_type, department_id, full_name, email, password_hash) VALUES
('ADMIN', 1, 'CoE Admin', 'admin@gmail.com', '$2y$10$rkp/h5oT9NiuwhCv0VbRMuAsRJ9erfJh3KtcvuStG9wJukBAd8ACi'),
('HOD', 2, 'CSE HOD', 'hod.cse@gmail.com', '$2y$10$rkp/h5oT9NiuwhCv0VbRMuAsRJ9erfJh3KtcvuStG9wJukBAd8ACi'),
('FACULTY', 2, 'CSE Faculty', 'faculty.cse@gmail.com', '$2y$10$rkp/h5oT9NiuwhCv0VbRMuAsRJ9erfJh3KtcvuStG9wJukBAd8ACi'),
('STAFF', 2, 'CSE Staff', 'staff.cse@gmail.com', '$2y$10$rkp/h5oT9NiuwhCv0VbRMuAsRJ9erfJh3KtcvuStG9wJukBAd8ACi'),
('STUDENT', 2, 'CSE Student', 'student.cse@gmail.com', '$2y$10$rkp/h5oT9NiuwhCv0VbRMuAsRJ9erfJh3KtcvuStG9wJukBAd8ACi');

INSERT INTO degree_levels (level_name) VALUES
('Undergraduate'),
('Postgraduate'),
('Doctorate');

-- SUGGESTED INDEXES FOR PERFORMANCE --
-- Indexing foreign keys and frequently queried columns is crucial for performance.
CREATE INDEX idx_users_email ON user_accounts(email);
CREATE INDEX idx_students_roll_number ON student_profiles(roll_number);
CREATE INDEX idx_submissions_user_req ON submissions(user_id, requirement_id);
CREATE INDEX idx_approval_log_submission_step ON submission_approval_log(submission_id, workflow_step_id);
