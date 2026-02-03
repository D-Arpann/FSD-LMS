SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS support_tickets;
DROP TABLE IF EXISTS lesson_progress;
DROP TABLE IF EXISTS enrollments;
DROP TABLE IF EXISTS lessons;
DROP TABLE IF EXISTS courses;
DROP TABLE IF EXISTS categories;
DROP TABLE IF EXISTS users;
SET FOREIGN_KEY_CHECKS = 1;

CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'instructor', 'student') NOT NULL DEFAULT 'student',
    bio TEXT,
    experience TEXT,
    verified TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT
);

CREATE TABLE courses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(200) NOT NULL,
    description TEXT,
    category_id INT,
    instructor_id INT,
    price DECIMAL(10,2) DEFAULT 0.00,
    duration VARCHAR(50),
    level ENUM('beginner', 'intermediate', 'advanced') DEFAULT 'beginner',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL,
    FOREIGN KEY (instructor_id) REFERENCES users(id) ON DELETE SET NULL
);

CREATE TABLE lessons (
    id INT AUTO_INCREMENT PRIMARY KEY,
    course_id INT NOT NULL,
    title VARCHAR(200) NOT NULL,
    video_url VARCHAR(500),
    learning_outcome TEXT,
    order_num INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE
);

CREATE TABLE enrollments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    course_id INT NOT NULL,
    enrolled_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_enrollment (user_id, course_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE
);

CREATE TABLE lesson_progress (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    lesson_id INT NOT NULL,
    completed TINYINT(1) DEFAULT 0,
    completed_at TIMESTAMP NULL,
    UNIQUE KEY unique_progress (user_id, lesson_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (lesson_id) REFERENCES lessons(id) ON DELETE CASCADE
);

CREATE TABLE support_tickets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    subject VARCHAR(200) NOT NULL,
    message TEXT NOT NULL,
    status ENUM('open', 'in_progress', 'closed') DEFAULT 'open',
    admin_reply TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

INSERT INTO users (id, name, email, password, role, verified, bio, experience) VALUES 
(1, 'Admin', 'admin@lms.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 1, NULL, NULL),
(2, 'Instructor User', 'ins@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'instructor', 1, 'Senior IT Instructor with 10+ years of experience.', 'Worked at Tech Corp and diverse startups.'),
(3, 'Student User', 'student@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student', 1, NULL, NULL);

INSERT INTO categories (id, name, description) VALUES 
(1, 'Programming', 'Learn various programming languages and software engineering concepts'),
(2, 'Web Development', 'Frontend, Backend, and Full Stack Web Technologies'),
(3, 'Data Science', 'Big Data, Machine Learning, and Artificial Intelligence');

INSERT INTO courses (id, title, description, category_id, instructor_id, price, duration, level) VALUES
(1, 'Python Masterclass', 'Comprehensive guide to Python programming from scratch to advanced concepts.', 1, 2, 49.99, '15 hours', 'beginner'),
(2, 'Full Stack Web Bootcamp', 'Become a full stack developer with HTML, CSS, JS, PHP, and MySQL.', 2, 2, 89.99, '45 hours', 'intermediate'),
(3, 'Machine Learning A-Z', 'Hands-on Machine Learning with Python and Scikit-Learn.', 3, 2, 69.99, '25 hours', 'advanced');

INSERT INTO lessons (course_id, title, video_url, learning_outcome, order_num) VALUES
(1, 'Introduction to Python', 'https://www.youtube.com/embed/_uQrJ0TkZlc', 'Setup Python environment', 1),
(1, 'Variables and Data Types', 'https://www.youtube.com/embed/vKqVnr0BEJO', 'Understand basic data types', 2),
(1, 'Control Flow', 'https://www.youtube.com/embed/PqFKRqpHrjw', 'Master if statements and loops', 3);

INSERT INTO lessons (course_id, title, video_url, learning_outcome, order_num) VALUES
(2, 'HTML5 Foundations', 'https://www.youtube.com/embed/mU6anWqZJcc', 'Build semantic HTML pages', 1),
(2, 'CSS3 Styling', 'https://www.youtube.com/embed/yfoY53QXEnI', 'Style layouts with Flexbox and Grid', 2),
(2, 'PHP Basics', 'https://www.youtube.com/embed/2eebptXfHvW', 'Server-side scripting fundamentals', 3);

INSERT INTO lessons (course_id, title, video_url, learning_outcome, order_num) VALUES
(3, 'What is Machine Learning?', 'https://www.youtube.com/embed/KNAWp2S3w94', 'Overview of ML types', 1),
(3, 'Linear Regression', 'https://www.youtube.com/embed/nk2CQBPmQS4', 'Predict continuous values', 2);

INSERT INTO enrollments (user_id, course_id) VALUES
(3, 1),
(3, 2);

ALTER TABLE users AUTO_INCREMENT = 4;
ALTER TABLE categories AUTO_INCREMENT = 4;
ALTER TABLE courses AUTO_INCREMENT = 4;
