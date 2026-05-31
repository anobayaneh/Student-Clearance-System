-- ============================================================
-- Student Clearance Processing System - Database Schema
-- Author: Gideon Agtas
-- ============================================================

CREATE DATABASE IF NOT EXISTS clearance_db;
USE clearance_db;

-- ============================================================
-- TABLE: users
-- ============================================================
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) NOT NULL UNIQUE,
    email VARCHAR(150) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'student', 'officer') NOT NULL DEFAULT 'student',
    full_name VARCHAR(200) NULL,
    phone VARCHAR(20) NULL,
    profile_photo VARCHAR(255) NULL,
    is_active TINYINT(1) DEFAULT 1,
    last_login TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- ============================================================
-- TABLE: departments
-- ============================================================
CREATE TABLE IF NOT EXISTS departments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    dept_name VARCHAR(150) NOT NULL,
    dept_code VARCHAR(20) NOT NULL,
    officer_name VARCHAR(150) NOT NULL,
    dept_email VARCHAR(150) NOT NULL,
    dept_description TEXT NULL,
    location VARCHAR(200) NULL,
    contact_number VARCHAR(20) NULL,
    user_id INT NULL,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

-- ============================================================
-- TABLE: students
-- ============================================================
CREATE TABLE IF NOT EXISTS students (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id VARCHAR(20) NOT NULL UNIQUE,
    full_name VARCHAR(200) NOT NULL,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    middle_name VARCHAR(100) NULL,
    course VARCHAR(100) NOT NULL,
    year_level VARCHAR(20) NOT NULL,
    section VARCHAR(20) NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    phone VARCHAR(20) NULL,
    address TEXT NULL,
    date_of_birth DATE NULL,
    gender ENUM('Male', 'Female', 'Other') NULL,
    guardian_name VARCHAR(200) NULL,
    guardian_contact VARCHAR(20) NULL,
    enrollment_status ENUM('enrolled', 'graduated', 'dropped', 'on_leave') DEFAULT 'enrolled',
    semester VARCHAR(30) DEFAULT '2nd Semester',
    school_year VARCHAR(20) DEFAULT '2024-2025',
    user_id INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

-- ============================================================
-- TABLE: clearances
-- ============================================================
CREATE TABLE IF NOT EXISTS clearances (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    dept_id INT NOT NULL,
    request_date DATE NOT NULL,
    status ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'pending',
    remarks TEXT NULL,
    reviewed_by INT NULL,
    reviewed_at TIMESTAMP NULL,
    semester VARCHAR(30) DEFAULT '2nd Semester',
    school_year VARCHAR(20) DEFAULT '2024-2025',
    priority ENUM('normal', 'urgent') DEFAULT 'normal',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    FOREIGN KEY (dept_id) REFERENCES departments(id) ON DELETE CASCADE,
    FOREIGN KEY (reviewed_by) REFERENCES users(id) ON DELETE SET NULL
);

-- ============================================================
-- TABLE: notifications
-- ============================================================
CREATE TABLE IF NOT EXISTS notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(255) NOT NULL DEFAULT 'Notification',
    message TEXT NOT NULL,
    type ENUM('info', 'success', 'warning', 'danger') DEFAULT 'info',
    link VARCHAR(255) NULL,
    is_read TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- ============================================================
-- TABLE: activity_logs
-- ============================================================
CREATE TABLE IF NOT EXISTS activity_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    action VARCHAR(255) NOT NULL,
    module VARCHAR(100) NULL,
    details TEXT NULL,
    ip_address VARCHAR(45) NULL,
    user_agent VARCHAR(500) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- ============================================================
-- DEMO ACCOUNTS
-- ============================================================
-- STEP 1: Import this SQL file to your database.
-- STEP 2: Run /database/seed_passwords.php in your browser
--         (or via: php database/seed_passwords.php) to set
--         the correct bcrypt passwords.
--
-- Login credentials after running seed_passwords.php:
-- ┌──────────────────────────────────────────────────────┐
-- │  Role     Username              Password              │
-- │  Admin    admin                 Admin@2025            │
-- │  Student  john_dela_cruz        Student@2025          │
-- │  Student  maria_santos          Student@2025          │
-- │  Student  carlos_garcia         Student@2025          │
-- │  Officer  library_officer       Officer@2025          │
-- │  Officer  registrar_officer     Officer@2025          │
-- │  Officer  finance_officer       Officer@2025          │
-- │  Officer  guidance_officer      Officer@2025          │
-- │  Officer  it_officer            Officer@2025          │
-- │  Officer  clinic_officer        Officer@2025          │
-- │  Officer  sao_officer           Officer@2025          │
-- └──────────────────────────────────────────────────────┘
-- The INSERT statements below use a temporary placeholder
-- password. seed_passwords.php will update them properly.
-- ============================================================

-- Admin
-- IMPORTANT: After importing, run /database/seed_passwords.php to set correct passwords
-- Password: Admin@2025
INSERT INTO users (username, email, password, role, full_name, phone, is_active) VALUES
('admin', 'admin@clearancems.edu.ph', '$2y$10$YVoYMz1cFJRWxdWFWkJ5bOgIb.0oZiKG9iHHHmHNKXfDAV6A3GQ0e', 'admin', 'System Administrator', '09171234567', 1);

-- Officers
-- Password: Officer@2025
INSERT INTO users (username, email, password, role, full_name, phone, is_active) VALUES
('library_officer',   'library@clearancems.edu.ph',   '$2y$10$VC3qBxp3gBcJwq3SbkuS2.GXVL.Bqoo1e8YJcWpJjWO1oFXwfhei', 'officer', 'Ms. Ana Reyes Cruz',    '09181000001', 1),
('registrar_officer', 'registrar@clearancems.edu.ph', '$2y$10$VC3qBxp3gBcJwq3SbkuS2.GXVL.Bqoo1e8YJcWpJjWO1oFXwfhei', 'officer', 'Mr. Jose Miguel Reyes',  '09181000002', 1),
('finance_officer',   'finance@clearancems.edu.ph',   '$2y$10$VC3qBxp3gBcJwq3SbkuS2.GXVL.Bqoo1e8YJcWpJjWO1oFXwfhei', 'officer', 'Ms. Linda Go Fernandez', '09181000003', 1),
('guidance_officer',  'guidance@clearancems.edu.ph',  '$2y$10$VC3qBxp3gBcJwq3SbkuS2.GXVL.Bqoo1e8YJcWpJjWO1oFXwfhei', 'officer', 'Dr. Ramon B. Santos',    '09181000004', 1),
('it_officer',        'itdept@clearancems.edu.ph',    '$2y$10$VC3qBxp3gBcJwq3SbkuS2.GXVL.Bqoo1e8YJcWpJjWO1oFXwfhei', 'officer', 'Engr. Mark Anthony Lim', '09181000005', 1),
('clinic_officer',    'clinic@clearancems.edu.ph',    '$2y$10$VC3qBxp3gBcJwq3SbkuS2.GXVL.Bqoo1e8YJcWpJjWO1oFXwfhei', 'officer', 'Dr. Maria Carmen Dizon', '09181000006', 1),
('sao_officer',       'sao@clearancems.edu.ph',       '$2y$10$VC3qBxp3gBcJwq3SbkuS2.GXVL.Bqoo1e8YJcWpJjWO1oFXwfhei', 'officer', 'Mr. Roberto Tan Abad',   '09181000007', 1);

-- Students
-- Password: Student@2025
INSERT INTO users (username, email, password, role, full_name, phone, is_active) VALUES
('john_dela_cruz',    'john.delacruz@student.clearancems.edu.ph',   '$2y$10$VzPo.U3Gy3aBW.gRpD7T9.tJaJNjbFaGq0zRz1xFw0a/bF6hGj4cO', 'student', 'John Miguel Dela Cruz',  '09201000001', 1),
('maria_santos',      'maria.santos@student.clearancems.edu.ph',    '$2y$10$VzPo.U3Gy3aBW.gRpD7T9.tJaJNjbFaGq0zRz1xFw0a/bF6hGj4cO', 'student', 'Maria Isabel Santos',    '09201000002', 1),
('carlos_garcia',     'carlos.garcia@student.clearancems.edu.ph',   '$2y$10$VzPo.U3Gy3aBW.gRpD7T9.tJaJNjbFaGq0zRz1xFw0a/bF6hGj4cO', 'student', 'Carlos Eduardo Garcia',  '09201000003', 1),
('anna_reyes',        'anna.reyes@student.clearancems.edu.ph',      '$2y$10$VzPo.U3Gy3aBW.gRpD7T9.tJaJNjbFaGq0zRz1xFw0a/bF6hGj4cO', 'student', 'Anna Kristine Reyes',    '09201000004', 1),
('pedro_bautista',    'pedro.bautista@student.clearancems.edu.ph',  '$2y$10$VzPo.U3Gy3aBW.gRpD7T9.tJaJNjbFaGq0zRz1xFw0a/bF6hGj4cO', 'student', 'Pedro Jose Bautista',    '09201000005', 1),
('rosa_mendoza',      'rosa.mendoza@student.clearancems.edu.ph',    '$2y$10$VzPo.U3Gy3aBW.gRpD7T9.tJaJNjbFaGq0zRz1xFw0a/bF6hGj4cO', 'student', 'Rosa Angela Mendoza',    '09201000006', 1),
('miguel_torres',     'miguel.torres@student.clearancems.edu.ph',   '$2y$10$VzPo.U3Gy3aBW.gRpD7T9.tJaJNjbFaGq0zRz1xFw0a/bF6hGj4cO', 'student', 'Miguel Antonio Torres',  '09201000007', 1),
('elena_villanueva',  'elena.villanueva@student.clearancems.edu.ph','$2y$10$VzPo.U3Gy3aBW.gRpD7T9.tJaJNjbFaGq0zRz1xFw0a/bF6hGj4cO', 'student', 'Elena Grace Villanueva', '09201000008', 1);

-- ============================================================
-- DEPARTMENTS (user_id references officer user rows: 2-8)
-- ============================================================
INSERT INTO departments (dept_name, dept_code, officer_name, dept_email, dept_description, location, contact_number, user_id) VALUES
('Library',                 'LIB',  'Ms. Ana Reyes Cruz',     'library@clearancems.edu.ph',   'Ensures no overdue books, unpaid fines, or unreturned borrowed materials.',         'Building A, Ground Floor',  '(02) 8800-0001', 2),
('Registrar',               'REG',  'Mr. Jose Miguel Reyes',  'registrar@clearancems.edu.ph', 'Verifies enrollment records, grades, and submission of required academic documents.', 'Building B, 2nd Floor',     '(02) 8800-0002', 3),
('Finance / Accounting',    'FIN',  'Ms. Linda Go Fernandez', 'finance@clearancems.edu.ph',   'Confirms full payment of tuition, miscellaneous fees, and other financial obligations.','Building B, Ground Floor',  '(02) 8800-0003', 4),
('Guidance Office',         'GUO',  'Dr. Ramon B. Santos',    'guidance@clearancems.edu.ph',  'Reviews student conduct records and counseling session requirements.',               'Building C, 1st Floor',     '(02) 8800-0004', 5),
('IT Department',           'ITD',  'Engr. Mark Anthony Lim', 'itdept@clearancems.edu.ph',    'Checks return of borrowed equipment, devices, and lab materials.',                  'Building D, 3rd Floor',     '(02) 8800-0005', 6),
('Medical / Clinic',        'MED',  'Dr. Maria Carmen Dizon', 'clinic@clearancems.edu.ph',    'Confirms up-to-date medical records, health clearance, and vaccination compliance.',  'Building A, 1st Floor',     '(02) 8800-0006', 7),
('Student Affairs Office',  'SAO',  'Mr. Roberto Tan Abad',   'sao@clearancems.edu.ph',       'Verifies participation in required school activities and organization membership.',   'Building C, Ground Floor',  '(02) 8800-0007', 8);

-- ============================================================
-- STUDENTS (user_id references student user rows: 9-16)
-- ============================================================
INSERT INTO students (student_id, full_name, first_name, last_name, middle_name, course, year_level, section, email, phone, address, date_of_birth, gender, guardian_name, guardian_contact, enrollment_status, semester, school_year, user_id) VALUES
('2024-0001', 'John Miguel Dela Cruz',  'John Miguel',  'Dela Cruz',  'Aguilar',  'BSIT',   '4th Year', 'A607', 'john.delacruz@student.clearancems.edu.ph',   '09201000001', '123 Maharlika St., Quezon City',         '2001-03-15', 'Male',   'Elena Dela Cruz',     '09201001001', 'enrolled', '2nd Semester', '2024-2025', 9),
('2024-0002', 'Maria Isabel Santos',    'Maria Isabel', 'Santos',     'Ramos',    'BSCS',   '3rd Year', 'B503', 'maria.santos@student.clearancems.edu.ph',    '09201000002', '456 Rizal Ave., Marikina City',           '2002-07-22', 'Female', 'Ricardo Santos',      '09201001002', 'enrolled', '2nd Semester', '2024-2025', 10),
('2024-0003', 'Carlos Eduardo Garcia',  'Carlos',       'Garcia',     'Eduardo',  'BSIT',   '4th Year', 'A607', 'carlos.garcia@student.clearancems.edu.ph',   '09201000003', '789 Katipunan Rd., Quezon City',          '2001-11-05', 'Male',   'Jose Garcia',         '09201001003', 'enrolled', '2nd Semester', '2024-2025', 11),
('2024-0004', 'Anna Kristine Reyes',    'Anna Kristine','Reyes',      'Buenaventura','BSA', '2nd Year', 'C201', 'anna.reyes@student.clearancems.edu.ph',      '09201000004', '12 Commonwealth Ave., Quezon City',      '2003-05-18', 'Female', 'Gloria Reyes',        '09201001004', 'enrolled', '2nd Semester', '2024-2025', 12),
('2024-0005', 'Pedro Jose Bautista',    'Pedro Jose',   'Bautista',   'Navarro',  'BSECE',  '3rd Year', 'D402', 'pedro.bautista@student.clearancems.edu.ph',  '09201000005', '34 E. Rodriguez Sr. Blvd., Quezon City', '2002-09-30', 'Male',   'Amalia Bautista',     '09201001005', 'enrolled', '2nd Semester', '2024-2025', 13),
('2024-0006', 'Rosa Angela Mendoza',    'Rosa Angela',  'Mendoza',    'Cruz',     'BSED',   '1st Year', 'E101', 'rosa.mendoza@student.clearancems.edu.ph',    '09201000006', '56 Batangas St., Manila',                '2004-01-12', 'Female', 'Andres Mendoza',      '09201001006', 'enrolled', '2nd Semester', '2024-2025', 14),
('2024-0007', 'Miguel Antonio Torres',  'Miguel',       'Torres',     'Antonio',  'BSME',   '4th Year', 'F701', 'miguel.torres@student.clearancems.edu.ph',   '09201000007', '78 Aurora Blvd., Pasig City',            '2001-06-25', 'Male',   'Cynthia Torres',      '09201001007', 'enrolled', '2nd Semester', '2024-2025', 15),
('2024-0008', 'Elena Grace Villanueva', 'Elena Grace',  'Villanueva', 'Lim',      'BSBA',   '2nd Year', 'G202', 'elena.villanueva@student.clearancems.edu.ph','09201000008', '90 Ortigas Ave., Mandaluyong',           '2003-12-08', 'Female', 'Rodrigo Villanueva',  '09201001008', 'enrolled', '2nd Semester', '2024-2025', 16);

-- ============================================================
-- CLEARANCES
-- ============================================================
INSERT INTO clearances (student_id, dept_id, request_date, status, remarks, reviewed_at, semester, school_year) VALUES
-- John Dela Cruz (student 1) - mixed statuses
(1, 1, CURDATE(), 'approved',  'No overdue books or unpaid library fines.',          NOW(), '2nd Semester', '2024-2025'),
(1, 2, CURDATE(), 'approved',  'All enrollment records are complete and verified.',  NOW(), '2nd Semester', '2024-2025'),
(1, 3, CURDATE(), 'pending',   NULL,                                                  NULL, '2nd Semester', '2024-2025'),
(1, 4, CURDATE(), 'pending',   NULL,                                                  NULL, '2nd Semester', '2024-2025'),
(1, 5, CURDATE(), 'approved',  'All laboratory equipment returned in good condition.',NOW(), '2nd Semester', '2024-2025'),
(1, 6, CURDATE(), 'approved',  'Medical records up to date.',                        NOW(), '2nd Semester', '2024-2025'),
(1, 7, CURDATE(), 'pending',   NULL,                                                  NULL, '2nd Semester', '2024-2025'),
-- Maria Santos (student 2) - has rejection
(2, 1, CURDATE(), 'pending',   NULL,                                                  NULL, '2nd Semester', '2024-2025'),
(2, 2, CURDATE(), 'approved',  'Records are complete. Cleared.',                     NOW(), '2nd Semester', '2024-2025'),
(2, 3, CURDATE(), 'rejected',  'Outstanding balance of PHP 3,500 for miscellaneous fees. Please settle at the cashier.',NOW(), '2nd Semester', '2024-2025'),
(2, 4, CURDATE(), 'pending',   NULL,                                                  NULL, '2nd Semester', '2024-2025'),
(2, 5, CURDATE(), 'approved',  'No borrowed items on record.',                       NOW(), '2nd Semester', '2024-2025'),
(2, 6, CURDATE(), 'approved',  'Health clearance issued.',                           NOW(), '2nd Semester', '2024-2025'),
(2, 7, CURDATE(), 'pending',   NULL,                                                  NULL, '2nd Semester', '2024-2025'),
-- Carlos Garcia (student 3) - fully cleared
(3, 1, CURDATE(), 'approved',  'All books returned. No fines.',                      NOW(), '2nd Semester', '2024-2025'),
(3, 2, CURDATE(), 'approved',  'Transcript request noted. Cleared.',                 NOW(), '2nd Semester', '2024-2025'),
(3, 3, CURDATE(), 'approved',  'All fees paid in full.',                             NOW(), '2nd Semester', '2024-2025'),
(3, 4, CURDATE(), 'approved',  'No outstanding conduct issues. Cleared.',            NOW(), '2nd Semester', '2024-2025'),
(3, 5, CURDATE(), 'approved',  'All equipment returned and accounted for.',          NOW(), '2nd Semester', '2024-2025'),
(3, 6, CURDATE(), 'approved',  'Annual physical exam completed.',                   NOW(), '2nd Semester', '2024-2025'),
(3, 7, CURDATE(), 'approved',  'Required activities completed. Cleared.',            NOW(), '2nd Semester', '2024-2025'),
-- Anna Reyes (student 4)
(4, 1, CURDATE(), 'pending',   NULL,                                                  NULL, '2nd Semester', '2024-2025'),
(4, 2, CURDATE(), 'pending',   NULL,                                                  NULL, '2nd Semester', '2024-2025'),
(4, 3, CURDATE(), 'approved',  'No outstanding balance.',                            NOW(), '2nd Semester', '2024-2025'),
(4, 4, CURDATE(), 'approved',  'Cleared. No conduct violations.',                   NOW(), '2nd Semester', '2024-2025'),
(4, 5, CURDATE(), 'approved',  'No borrowed equipment.',                             NOW(), '2nd Semester', '2024-2025'),
(4, 6, CURDATE(), 'pending',   NULL,                                                  NULL, '2nd Semester', '2024-2025'),
(4, 7, CURDATE(), 'approved',  'Participated in required school events.',            NOW(), '2nd Semester', '2024-2025'),
-- Pedro Bautista (student 5)
(5, 1, CURDATE(), 'approved',  'No overdue items.',                                  NOW(), '2nd Semester', '2024-2025'),
(5, 3, CURDATE(), 'pending',   NULL,                                                  NULL, '2nd Semester', '2024-2025'),
(5, 5, CURDATE(), 'rejected',  'Oscilloscope (Unit #3) not yet returned. Please return to IT Lab.',NOW(),'2nd Semester','2024-2025'),
-- Rosa Mendoza (student 6)
(6, 1, CURDATE(), 'pending',   NULL,                                                  NULL, '2nd Semester', '2024-2025'),
(6, 2, CURDATE(), 'pending',   NULL,                                                  NULL, '2nd Semester', '2024-2025'),
(6, 3, CURDATE(), 'pending',   NULL,                                                  NULL, '2nd Semester', '2024-2025'),
-- Miguel Torres (student 7) - nearly cleared
(7, 1, CURDATE(), 'approved',  'Cleared.',                                           NOW(), '2nd Semester', '2024-2025'),
(7, 2, CURDATE(), 'approved',  'Cleared.',                                           NOW(), '2nd Semester', '2024-2025'),
(7, 3, CURDATE(), 'approved',  'All payments settled.',                             NOW(), '2nd Semester', '2024-2025'),
(7, 4, CURDATE(), 'approved',  'Cleared.',                                           NOW(), '2nd Semester', '2024-2025'),
(7, 5, CURDATE(), 'approved',  'Machine shop tools returned.',                      NOW(), '2nd Semester', '2024-2025'),
(7, 6, CURDATE(), 'approved',  'Cleared.',                                           NOW(), '2nd Semester', '2024-2025'),
(7, 7, CURDATE(), 'pending',   NULL,                                                  NULL, '2nd Semester', '2024-2025'),
-- Elena Villanueva (student 8)
(8, 1, CURDATE(), 'approved',  'No library dues.',                                  NOW(), '2nd Semester', '2024-2025'),
(8, 2, CURDATE(), 'pending',   NULL,                                                  NULL, '2nd Semester', '2024-2025'),
(8, 3, CURDATE(), 'approved',  'Cleared.',                                           NOW(), '2nd Semester', '2024-2025'),
(8, 4, CURDATE(), 'pending',   NULL,                                                  NULL, '2nd Semester', '2024-2025');

-- ============================================================
-- NOTIFICATIONS
-- ============================================================
INSERT INTO notifications (user_id, title, message, type, is_read) VALUES
(9,  'Clearance Approved',  'Your clearance request for Library has been APPROVED. No overdue books or unpaid fines.',  'success', 0),
(9,  'Clearance Approved',  'Your clearance request for Registrar has been APPROVED.',                                  'success', 0),
(9,  'Clearance Approved',  'Your clearance request for IT Department has been APPROVED.',                              'success', 1),
(10, 'Clearance Rejected',  'Your clearance for Finance has been REJECTED. Outstanding balance of PHP 3,500.',          'danger',  0),
(10, 'Clearance Approved',  'Your clearance request for Registrar has been APPROVED.',                                  'success', 1),
(11, 'Fully Cleared!',      'Congratulations! All your clearance requests have been APPROVED. You are fully cleared!',  'success', 0),
(13, 'Clearance Rejected',  'Your clearance for IT Department was REJECTED. Please return Oscilloscope Unit #3.',       'danger',  0),
(15, 'Almost Done!',        'Only 1 clearance remaining (Student Affairs Office). Please visit the SAO.',               'info',    0);

-- ============================================================
-- ACTIVITY LOGS (sample)
-- ============================================================
INSERT INTO activity_logs (user_id, action, module, details) VALUES
(1, 'System Initialized',  'System',    'Database seeded with demo data.'),
(2, 'Approved Clearance',  'Clearance', 'Approved clearance for John Dela Cruz (Library).'),
(3, 'Approved Clearance',  'Clearance', 'Approved clearance for Carlos Garcia (Registrar).'),
(4, 'Rejected Clearance',  'Clearance', 'Rejected clearance for Maria Santos (Finance). Reason: Outstanding balance.'),
(1, 'Viewed Reports',      'Reports',   'Admin viewed clearance summary report.');
