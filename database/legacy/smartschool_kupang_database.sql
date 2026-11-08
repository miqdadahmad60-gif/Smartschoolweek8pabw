-- ============================================================
-- DATABASE SMART SCHOOL KUPANG
-- Siap di-import melalui phpMyAdmin
-- ============================================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+07:00";
SET NAMES utf8mb4;

DROP DATABASE IF EXISTS smartschool_kupang;
CREATE DATABASE smartschool_kupang
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE smartschool_kupang;

START TRANSACTION;

-- ============================================================
-- 1. AKUN PENGGUNA
-- ============================================================

CREATE TABLE users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(120) NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('siswa', 'guru', 'ortu', 'admin') NOT NULL,
    phone VARCHAR(25) DEFAULT NULL,
    address TEXT DEFAULT NULL,
    profile_photo VARCHAR(255) DEFAULT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uk_users_email (email),
    KEY idx_users_role (role)
) ENGINE=InnoDB;

CREATE TABLE students (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    student_code VARCHAR(20) NOT NULL,
    nisn VARCHAR(20) DEFAULT NULL,
    class_name VARCHAR(50) NOT NULL,
    major VARCHAR(100) NOT NULL DEFAULT 'Teknik Komputer dan Jaringan',
    school_year VARCHAR(20) NOT NULL DEFAULT '2026/2027',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uk_students_user (user_id),
    UNIQUE KEY uk_students_code (student_code),
    UNIQUE KEY uk_students_nisn (nisn),
    CONSTRAINT fk_students_user
        FOREIGN KEY (user_id) REFERENCES users(id)
        ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE teachers (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    teacher_code VARCHAR(20) NOT NULL,
    nip VARCHAR(30) DEFAULT NULL,
    main_subject VARCHAR(100) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uk_teachers_user (user_id),
    UNIQUE KEY uk_teachers_code (teacher_code),
    UNIQUE KEY uk_teachers_nip (nip),
    CONSTRAINT fk_teachers_user
        FOREIGN KEY (user_id) REFERENCES users(id)
        ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE parents (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    parent_code VARCHAR(20) NOT NULL,
    relationship ENUM('ayah', 'ibu', 'wali') NOT NULL DEFAULT 'wali',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uk_parents_user (user_id),
    UNIQUE KEY uk_parents_code (parent_code),
    CONSTRAINT fk_parents_user
        FOREIGN KEY (user_id) REFERENCES users(id)
        ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE parent_students (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    parent_id INT UNSIGNED NOT NULL,
    student_id INT UNSIGNED NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uk_parent_student (parent_id, student_id),
    CONSTRAINT fk_parent_students_parent
        FOREIGN KEY (parent_id) REFERENCES parents(id)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_parent_students_student
        FOREIGN KEY (student_id) REFERENCES students(id)
        ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================================
-- 2. MATA PELAJARAN DAN MATERI
-- ============================================================

CREATE TABLE subjects (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    subject_code VARCHAR(20) NOT NULL,
    name VARCHAR(100) NOT NULL,
    description TEXT DEFAULT NULL,
    teacher_id INT UNSIGNED DEFAULT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uk_subjects_code (subject_code),
    KEY idx_subjects_teacher (teacher_id),
    CONSTRAINT fk_subjects_teacher
        FOREIGN KEY (teacher_id) REFERENCES teachers(id)
        ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE materials (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    subject_id INT UNSIGNED NOT NULL,
    teacher_id INT UNSIGNED NOT NULL,
    title VARCHAR(180) NOT NULL,
    description TEXT DEFAULT NULL,
    file_name VARCHAR(255) DEFAULT NULL,
    original_name VARCHAR(255) DEFAULT NULL,
    file_type VARCHAR(30) DEFAULT NULL,
    file_size INT UNSIGNED DEFAULT NULL,
    is_published TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_materials_subject (subject_id),
    KEY idx_materials_teacher (teacher_id),
    KEY idx_materials_published (is_published),
    CONSTRAINT fk_materials_subject
        FOREIGN KEY (subject_id) REFERENCES subjects(id)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_materials_teacher
        FOREIGN KEY (teacher_id) REFERENCES teachers(id)
        ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================================
-- 3. KUIS, SOAL, DAN NILAI
-- ============================================================

CREATE TABLE quizzes (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    subject_id INT UNSIGNED NOT NULL,
    teacher_id INT UNSIGNED NOT NULL,
    title VARCHAR(180) NOT NULL,
    description TEXT DEFAULT NULL,
    duration_minutes INT UNSIGNED NOT NULL DEFAULT 30,
    passing_score DECIMAL(5,2) NOT NULL DEFAULT 75.00,
    start_at DATETIME DEFAULT NULL,
    end_at DATETIME DEFAULT NULL,
    is_published TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_quizzes_subject (subject_id),
    KEY idx_quizzes_teacher (teacher_id),
    KEY idx_quizzes_published (is_published),
    CONSTRAINT fk_quizzes_subject
        FOREIGN KEY (subject_id) REFERENCES subjects(id)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_quizzes_teacher
        FOREIGN KEY (teacher_id) REFERENCES teachers(id)
        ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE quiz_questions (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    quiz_id INT UNSIGNED NOT NULL,
    question_text TEXT NOT NULL,
    option_a VARCHAR(255) NOT NULL,
    option_b VARCHAR(255) NOT NULL,
    option_c VARCHAR(255) NOT NULL,
    option_d VARCHAR(255) NOT NULL,
    correct_answer ENUM('a', 'b', 'c', 'd') NOT NULL,
    points DECIMAL(5,2) NOT NULL DEFAULT 1.00,
    question_order INT UNSIGNED NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_questions_quiz (quiz_id),
    CONSTRAINT fk_questions_quiz
        FOREIGN KEY (quiz_id) REFERENCES quizzes(id)
        ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE quiz_attempts (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    quiz_id INT UNSIGNED NOT NULL,
    student_id INT UNSIGNED NOT NULL,
    score DECIMAL(5,2) NOT NULL DEFAULT 0.00,
    correct_answers INT UNSIGNED NOT NULL DEFAULT 0,
    total_questions INT UNSIGNED NOT NULL DEFAULT 0,
    started_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    submitted_at DATETIME DEFAULT NULL,
    status ENUM('mengerjakan', 'selesai') NOT NULL DEFAULT 'mengerjakan',
    UNIQUE KEY uk_quiz_student (quiz_id, student_id),
    KEY idx_attempts_student (student_id),
    CONSTRAINT fk_attempts_quiz
        FOREIGN KEY (quiz_id) REFERENCES quizzes(id)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_attempts_student
        FOREIGN KEY (student_id) REFERENCES students(id)
        ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE quiz_answers (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    attempt_id INT UNSIGNED NOT NULL,
    question_id INT UNSIGNED NOT NULL,
    selected_answer ENUM('a', 'b', 'c', 'd') DEFAULT NULL,
    is_correct TINYINT(1) NOT NULL DEFAULT 0,
    points_earned DECIMAL(5,2) NOT NULL DEFAULT 0.00,
    UNIQUE KEY uk_attempt_question (attempt_id, question_id),
    CONSTRAINT fk_answers_attempt
        FOREIGN KEY (attempt_id) REFERENCES quiz_attempts(id)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_answers_question
        FOREIGN KEY (question_id) REFERENCES quiz_questions(id)
        ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================================
-- 4. FASILITAS SEKOLAH
-- ============================================================

CREATE TABLE facilities (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    facility_code VARCHAR(20) NOT NULL,
    name VARCHAR(120) NOT NULL,
    description TEXT DEFAULT NULL,
    total_stock INT UNSIGNED NOT NULL DEFAULT 1,
    available_stock INT UNSIGNED NOT NULL DEFAULT 1,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uk_facilities_code (facility_code)
) ENGINE=InnoDB;

CREATE TABLE facility_requests (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    student_id INT UNSIGNED NOT NULL,
    facility_id INT UNSIGNED NOT NULL,
    purpose TEXT NOT NULL,
    date_needed DATE NOT NULL,
    return_date DATE DEFAULT NULL,
    status ENUM(
        'menunggu',
        'disetujui',
        'ditolak',
        'dipinjam',
        'dikembalikan'
    ) NOT NULL DEFAULT 'menunggu',
    teacher_note TEXT DEFAULT NULL,
    approved_by INT UNSIGNED DEFAULT NULL,
    approved_at DATETIME DEFAULT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_requests_student (student_id),
    KEY idx_requests_facility (facility_id),
    KEY idx_requests_status (status),
    CONSTRAINT fk_requests_student
        FOREIGN KEY (student_id) REFERENCES students(id)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_requests_facility
        FOREIGN KEY (facility_id) REFERENCES facilities(id)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_requests_approver
        FOREIGN KEY (approved_by) REFERENCES teachers(id)
        ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB;

-- ============================================================
-- 5. NOTIFIKASI
-- ============================================================

CREATE TABLE notifications (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    title VARCHAR(160) NOT NULL,
    message TEXT NOT NULL,
    link VARCHAR(255) DEFAULT NULL,
    is_read TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_notifications_user (user_id),
    KEY idx_notifications_read (is_read),
    CONSTRAINT fk_notifications_user
        FOREIGN KEY (user_id) REFERENCES users(id)
        ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================================
-- 6. DATA AWAL / AKUN DEMO
-- ============================================================

INSERT INTO users
    (id, name, email, password, role, phone, address)
VALUES
    (
        1,
        'Budi Santoso',
        'siswa@smartschool.test',
        '$2y$10$gCUX.Qrq6Lz.HMfHgNOFOOjmL5d2meE91ChAJX6zTqGypu04iTFoK',
        'siswa',
        '081234567801',
        'Kota Kupang, Nusa Tenggara Timur'
    ),
    (
        2,
        'Ibu Maria Lestari',
        'guru@smartschool.test',
        '$2y$10$lxlPruyg9t6UbFXLxGV6o.ttSy3X.CPOOIwCXJy0wQVTBxbrbir1u',
        'guru',
        '081234567802',
        'Kota Kupang, Nusa Tenggara Timur'
    ),
    (
        3,
        'Bapak Anton Santoso',
        'ortu@smartschool.test',
        '$2y$10$4eObnaaLPogtS/g8DjBIUOLYuJqq93kY83gf59xjytJYtOr3.SC9.',
        'ortu',
        '081234567803',
        'Kota Kupang, Nusa Tenggara Timur'
    );

INSERT INTO students
    (id, user_id, student_code, nisn, class_name, major, school_year)
VALUES
    (
        1,
        1,
        'SSK001',
        '0098765432',
        'XII TKJ 1',
        'Teknik Komputer dan Jaringan',
        '2026/2027'
    );

INSERT INTO teachers
    (id, user_id, teacher_code, nip, main_subject)
VALUES
    (
        1,
        2,
        'GRU001',
        '198706152012012001',
        'Matematika Dasar'
    );

INSERT INTO parents
    (id, user_id, parent_code, relationship)
VALUES
    (1, 3, 'ORT001', 'ayah');

INSERT INTO parent_students (parent_id, student_id)
VALUES (1, 1);

INSERT INTO subjects
    (id, subject_code, name, description, teacher_id)
VALUES
    (1, 'MTK', 'Matematika Dasar', 'Matematika dasar dan aljabar.', 1),
    (2, 'BIN', 'Bahasa Indonesia', 'Bahasa, sastra, dan komunikasi.', 1),
    (3, 'FIS', 'Fisika Terapan', 'Fisika untuk kebutuhan industri.', 1),
    (4, 'KIM', 'Kimia Industri', 'Kimia dasar dan proses industri.', 1);

INSERT INTO materials
    (
        subject_id,
        teacher_id,
        title,
        description,
        original_name,
        file_type
    )
VALUES
    (
        1,
        1,
        'Modul 01 - Aljabar Dasar',
        'Persamaan, variabel, dan operasi dasar aljabar.',
        'Modul-01-Aljabar.pdf',
        'pdf'
    ),
    (
        3,
        1,
        'Hukum Newton',
        'Ringkasan hukum gerak Newton dan latihan.',
        'Presentasi-Hukum-Newton.pptx',
        'pptx'
    ),
    (
        2,
        1,
        'Analisis Puisi',
        'Panduan mengenali unsur intrinsik puisi.',
        'Tugas-Analisis-Puisi.docx',
        'docx'
    );

INSERT INTO quizzes
    (
        id,
        subject_id,
        teacher_id,
        title,
        description,
        duration_minutes,
        passing_score
    )
VALUES
    (
        1,
        1,
        1,
        'Kuis Matematika Dasar',
        'Evaluasi operasi hitung dan aljabar dasar.',
        30,
        75
    );

INSERT INTO quiz_questions
    (
        quiz_id,
        question_text,
        option_a,
        option_b,
        option_c,
        option_d,
        correct_answer,
        points,
        question_order
    )
VALUES
    (1, 'Hasil dari 8 x 7 adalah...', '48', '56', '64', '72', 'b', 1, 1),
    (1, 'Nilai x dari 2x + 4 = 14 adalah...', '3', '4', '5', '6', 'c', 1, 2),
    (1, 'Hasil 12 kuadrat adalah...', '124', '144', '164', '184', 'b', 1, 3);

INSERT INTO quiz_attempts
    (
        id,
        quiz_id,
        student_id,
        score,
        correct_answers,
        total_questions,
        submitted_at,
        status
    )
VALUES
    (
        1,
        1,
        1,
        66.67,
        2,
        3,
        NOW(),
        'selesai'
    );

INSERT INTO quiz_answers
    (
        attempt_id,
        question_id,
        selected_answer,
        is_correct,
        points_earned
    )
VALUES
    (1, 1, 'b', 1, 1),
    (1, 2, 'c', 1, 1),
    (1, 3, 'a', 0, 0);

INSERT INTO facilities
    (
        id,
        facility_code,
        name,
        description,
        total_stock,
        available_stock
    )
VALUES
    (1, 'FSL001', 'Laptop Praktik', 'Laptop untuk praktikum.', 24, 24),
    (2, 'FSL002', 'Kamera DSLR', 'Kamera dokumentasi dan multimedia.', 8, 7),
    (3, 'FSL003', 'Proyektor Kelas', 'Proyektor untuk presentasi.', 12, 12),
    (4, 'FSL004', 'Ruang Multimedia', 'Studio multimedia sekolah.', 1, 1);

INSERT INTO facility_requests
    (
        student_id,
        facility_id,
        purpose,
        date_needed,
        status
    )
VALUES
    (
        1,
        2,
        'Dokumentasi tugas multimedia',
        DATE_ADD(CURDATE(), INTERVAL 3 DAY),
        'menunggu'
    );

INSERT INTO notifications (user_id, title, message, link)
VALUES
    (
        1,
        'Materi Baru',
        'Modul Aljabar Dasar telah diterbitkan.',
        '/academic'
    ),
    (
        2,
        'Pengajuan Fasilitas',
        'Budi Santoso mengajukan peminjaman Kamera DSLR.',
        '/facilities'
    ),
    (
        3,
        'Nilai Kuis Anak',
        'Nilai Kuis Matematika Dasar Budi telah tersedia.',
        '/grades'
    );

COMMIT;

-- ============================================================
-- AKUN DEMO
-- ============================================================
-- Siswa     : siswa@smartschool.test / siswa123
-- Guru      : guru@smartschool.test  / guru123
-- Orang Tua : ortu@smartschool.test  / ortu123
-- ============================================================
