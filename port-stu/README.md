# Learning Management System (LMS)

A fully functional, production-style Learning Management System built with PHP and MySQL. The system follows a Browse-First architecture and is designed for academic assessment and real-world use.

## System Purpose
Build a robust platform that meets academic requirements for:
- Full CRUD operations
- Secure authentication and authorization
- AJAX-based interactions
- Session-based access control

## Login Credentials

| Role | Email | Password |
|------|-------|----------|
| **Admin** | `admin@lms.com` | `password` |
| **Instructor** | `ins@email.com` | `password` |
| **Student** | `student@email.com` | `password` |

## Setup Instructions

1.  **Clone/Extract Project**:
    Place the source code in your web server's root directory (e.g., `/var/www/html` or `C:\Wemp\nginx\html`).

2.  **Database Configuration**:
    *   Create a MySQL database named `lms_db`.
    *   Import the schema and sample data from `lms_schema.sql`:
        ```bash
        mysql -u root -p lms_db < lms_schema.sql
        ```
    *   Verify `config/database.php` matches your local environment credentials.

3.  **Run Application**:
    - **Local**: Navigate to your configured local server URL
    - **Server**: Navigate to `https://student.heraldcollege.edu.np/~np03cs4a240050/public/index.php`

## User Roles

### Admin
- Full CRUD access to all system entities (Users, Courses, Lessons, Categories).
- Verifies instructor applications.
- Assigns instructors to courses and manages enrollments.
- Handles support ticket responses.

### Instructor
- Applies through signup; requires admin verification.
- Views assigned courses and browses all courses.
- Manages lessons directly from the course details page (inline CRUD).
- Tracks student progress for their courses.

### Student
- Direct registration and login.
- Enrolls in courses and accesses lesson content.
- Tracks personal progress via dashboard.
- Submits support requests to admin.

## Features

### CRUD Operations
- **User Management**: Administrators can create, verify, and remove users.
- **Content Management**: Full CRUD for Categories, Courses, and Lessons.
- **Enrollment System**: Students can enroll; Admins can manage all enrollments.
- **Support System**: User-initiated tickets with Admin response capabilities.
- **Inline Lesson Management**: Instructors can add, edit, and delete lessons directly from course details.

### Security Measures
- **SQL Injection Prevention**: 100% usage of PDO prepared statements.
- **XSS Prevention**: All output sanitized via `e()` helper function.
- **CSRF Protection**: Session-based anti-CSRF tokens on all POST requests.
- **Password Security**: Hashed with `password_hash()` and `password_verify()`.
- **RBAC**: Strict role-based access control at configuration level.

### AJAX Features
- **Progress Tracking**: Real-time lesson completion without page reloads.
- **Dynamic Content**: AJAX-based enrollment and status updates.
- **Enhanced UI**: Lucide icons dynamically rendered for modal content.

## Technical Stack
- **Backend**: PHP (PDO for database)
- **Database**: MySQL
- **Frontend**: HTML5, Vanilla CSS
- **Theme**: Retro CLI (Black & Orange)
- **Icons**: Lucide Icons
- **Font**: JetBrains Mono

## File Structure
```
/admin       - Administrative dashboard and management
/instructor  - Instructor tools and progress tracking
/student     - Student course access and dashboard
/public      - Guest browsing, login, and signup
/config      - Database and authentication configuration
/includes    - Reusable components (Header, Footer, Navbar)
/assets      - CSS and JS assets
/api         - AJAX request endpoints
```

## CSS Architecture

The stylesheet (`assets/css/style.css`) follows a modular structure:
- **CSS Variables**: Theme colors and fonts in `:root`
- **Base Styles**: Reset, typography, and layout
- **Components**: Buttons, cards, forms, tables, badges, alerts
- **Layout**: Grid system, navigation, sidebar
- **Utilities**: Spacing, text, flex helpers
- **Icon Alignment**: Consistent Lucide icon sizing

### Utility Classes
| Class | Purpose |
|-------|---------|
| `.w-full` | Full width |
| `.flex-1` | Flex grow |
| `.page-title` | Page heading with icon |
| `.course-title` | Course card heading |
| `.meta-item` | Icon + text alignment |
| `.filter-form` | Filter form layout |
| `.price-text` | Price display |
