DROP DATABASE IF EXISTS sms;
CREATE DATABASE sms;
USE sms;

-- used by users table
CREATE TABLE departments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    code VARCHAR(20) UNIQUE NOT NULL,
    name VARCHAR(100) NOT NULL
);

CREATE TABLE users (
    user_id INT PRIMARY KEY AUTO_INCREMENT,
    user_type ENUM('ADMIN','HOD','FACULTY', 'STAFF', 'STUDENT'),
    department_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE RESTRICT
);

-- used by programmes table
CREATE TABLE degree_levels (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) UNIQUE NOT NULL
);

-- used by batches table
CREATE TABLE programmes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL UNIQUE,
    credit_specification_id INT NULL,
    minimum_year INT NOT NULL,
    maximum_year INT NULL,
    degree_level_id INT NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    FOREIGN KEY (degree_level_id) REFERENCES degree_levels(id)
);

CREATE TABLE batches (
    id INT AUTO_INCREMENT PRIMARY KEY,
    programme_id INT NOT NULL,
    batch_name VARCHAR(50) NOT NULL, -- e.g., "2023 Batch", "2024 Batch"
    start_year INT NOT NULL, -- e.g., 2023
    start_semester ENUM("SPRING", "AUTUMN"),
    is_active BOOLEAN DEFAULT TRUE,
    FOREIGN KEY (programme_id) REFERENCES programmes(id) ON DELETE RESTRICT,
    UNIQUE (programme_id, batch_name)
);

-- used and updated by students only
CREATE TABLE addresses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    address_line_1 VARCHAR(255) NOT NULL,
    address_line_2 VARCHAR(255) NULL,
    city VARCHAR(100) NOT NULL,
    state VARCHAR(100) NOT NULL,
    postal_code VARCHAR(20) NOT NULL,
    country VARCHAR(100) NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);

CREATE TABLE students (
    user_id INT PRIMARY KEY,
    roll_number VARCHAR(10) UNIQUE NOT NULL,
    date_of_birth DATE NULL,
    current_address_id INT NOT NULL,
    permanent_address_id INT NOT NULL,
    self_phone_number VARCHAR(20) NULL,
    guardian_phone_number VARCHAR(20) NULL,
    batch_id INT NOT NULL,
    is_approved BOOLEAN DEFAULT FALSE,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (batch_id) REFERENCES batches(id),
    FOREIGN KEY (current_address_id) REFERENCES addresses(id),
    FOREIGN KEY (permanent_address_id) REFERENCES addresses(id)
);

CREATE TABLE faculties (
    user_id INT PRIMARY KEY,
    phone_number VARCHAR(20) UNIQUE NULL,
    specialization VARCHAR(255) NULL,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);


INSERT INTO departments (department_code, department_name) VALUES
('COE', 'Controller Of Examination'),
('CSE', 'Computer Science and Engineering');

-- default users (bcrypt hash: "adminpass")
INSERT INTO users (user_type, department_id, name, email, password) VALUES
('ADMIN', 1, 'CoE Admin', 'admin@gmail.com', '$2y$10$rkp/h5oT9NiuwhCv0VbRMuAsRJ9erfJh3KtcvuStG9wJukBAd8ACi'),
('HOD', 2, 'CSE HOD', 'hod.cse@gmail.com', '$2y$10$rkp/h5oT9NiuwhCv0VbRMuAsRJ9erfJh3KtcvuStG9wJukBAd8ACi'),
('FACULTY', 2, 'CSE Faculty', 'faculty.cse@gmail.com', '$2y$10$rkp/h5oT9NiuwhCv0VbRMuAsRJ9erfJh3KtcvuStG9wJukBAd8ACi'),
('STAFF', 2, 'CSE Staff', 'staff.cse@gmail.com', '$2y$10$rkp/h5oT9NiuwhCv0VbRMuAsRJ9erfJh3KtcvuStG9wJukBAd8ACi'),
('STUDENT', 2, 'CSE Student', 'student.cse@gmail.com', '$2y$10$rkp/h5oT9NiuwhCv0VbRMuAsRJ9erfJh3KtcvuStG9wJukBAd8ACi');

INSERT INTO degree_levels (degree_level_name) VALUES
('UG'),
('PG'),
('PhD');


-- assuming that every requirement will be submitted in the form of document
-- requirements
CREATE TABLE requirements(
    id              INT PRIMARY KEY AUTO_INCREMENT,
    title           VARCHAR(100) NOT NULL,
    fullfilment_mode ENUM('ONLINE','OFFLINE','ANY') DEFAULT 'ANY',
    assigned_on     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    due_date        DATE DEFAULT NULL,
    description     TEXT
);

CREATE TABLE requirement_assignments (
    requirement_id INT NOT NULL,
    user_id INT NOT NULL,
    FOREIGN KEY (requirement_id) REFERENCES requirements(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    PRIMARY KEY (requirement_id, user_id)
);

-- submissions
CREATE TABLE submissions (
    id              INT PRIMARY KEY AUTO_INCREMENT,
    requirement_id  INT NOT NULL,
    user_id         INT NOT NULL,
    submitted_on    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    description     TEXT,
    FOREIGN KEY (requirement_id) REFERENCES requirements(id),
    FOREIGN KEY (user_id) REFERENCES users(user_id)
);

-- documents
CREATE TABLE documents (
    id              INT PRIMARY KEY AUTO_INCREMENT,
    submission_id   INT NOT NULL,
    doc_type        VARCHAR(50),
    file_path       VARCHAR(255) NOT NULL,
    uploaded_on     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (submission_id) REFERENCES submissions(id)
);

now how to assign any requirements to a group of students of some students
so that they can get the notifications and submit the requirements