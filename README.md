# Eventra - School Event Management System

A comprehensive web-based event management platform designed for schools and educational institutions to organize, manage, and register for events efficiently.

## 📋 Overview

Eventra is a PHP-based event management system that streamlines the process of creating, approving, and registering for school events. It provides separate interfaces for regular users and administrators, enabling effective event coordination and participant management.

## ✨ Features

### For Regular Users
- **User Registration & Authentication** - Secure signup and login with unique User IDs
- **Event Discovery** - Browse and view all approved events in one place
- **Easy Registration** - Register for events with a single click (subject to capacity)
- **Event Creation** - Create events that require admin approval before going live
- **Registration Management** - Track your event registrations and statuses
- **Participant List** - Event creators can view approved participants and manage registrations

### For Administrators
- **Event Approval System** - Review and approve/reject user-created events
- **Dashboard Statistics** - View key metrics including total events, users, registrations
- **Event Management** - Comprehensive view of all system events
- **User Management** - Monitor all registered users and their departments
- **Registration Monitoring** - Track all event registrations across the platform

## 🛠 Technology Stack

- **Backend**: PHP 7.0+
- **Database**: MySQL
- **Frontend**: HTML5, CSS3, JavaScript (Vanilla)
- **Server**: Apache/any PHP-compatible server
- **Session Management**: PHP Sessions

## 📁 File Structure

```
eventra/
├── index.php              # Landing page with login/register modals
├── auth.php              # Authentication handler (login, register, logout)
├── config.php            # Database configuration & helper functions
├── dashboard.php         # User dashboard (view/create/register events)
├── admin.php             # Admin dashboard (event approval, statistics)
├── get_participants.php  # JSON API endpoint for participant data
├── style.css             # Styling for all pages
└── README.md             # Project documentation
```

## 🚀 Getting Started

### Prerequisites
- PHP 7.0 or higher
- MySQL Server
- Web server (Apache, Nginx, etc.)
- Modern web browser

### Installation

1. **Clone or download the project**
   ```bash
   git clone <repository-url>
   cd eventra
   ```

2. **Create MySQL Database**
   ```sql
   CREATE DATABASE school_events;
   ```

3. **Create Required Tables**
   ```sql
   -- Users table
   CREATE TABLE users (
     user_id VARCHAR(20) PRIMARY KEY,
     name VARCHAR(100) NOT NULL,
     email VARCHAR(100) UNIQUE NOT NULL,
     password VARCHAR(255) NOT NULL,
     phone VARCHAR(10) NOT NULL,
     class VARCHAR(50) NOT NULL,
     role ENUM('user', 'admin') DEFAULT 'user',
     created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
   );

   -- Events table
   CREATE TABLE events (
     event_id INT PRIMARY KEY AUTO_INCREMENT,
     title VARCHAR(150) NOT NULL,
     description TEXT NOT NULL,
     event_date DATE NOT NULL,
     event_time TIME NOT NULL,
     venue VARCHAR(100) NOT NULL,
     category VARCHAR(50) NOT NULL,
     max_participants INT NOT NULL,
     created_by VARCHAR(20) NOT NULL,
     status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
     created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
     FOREIGN KEY (created_by) REFERENCES users(user_id)
   );

   -- Registrations table
   CREATE TABLE registrations (
     reg_id INT PRIMARY KEY AUTO_INCREMENT,
     event_id INT NOT NULL,
     user_id VARCHAR(20) NOT NULL,
     status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
     registered_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
     FOREIGN KEY (event_id) REFERENCES events(event_id),
     FOREIGN KEY (user_id) REFERENCES users(user_id)
   );
   ```

4. **Configure Database Connection**
   - Edit `config.php` and update the database credentials:
   ```php
   define('DB_HOST', 'localhost');
   define('DB_USER', 'root');
   define('DB_PASS', '');
   define('DB_NAME', 'school_events');
   ```

5. **Place files on your web server**
   - Copy all files to your web server's document root (e.g., `htdocs/` for XAMPP)

6. **Access the application**
   - Open your browser and navigate to `http://localhost/eventra/`

## 👥 User Roles

### Regular User
- Register for an account with name, email, phone, and department
- View all approved events
- Create new events (requires admin approval)
- Register for available events
- View their registration status
- See approved participants in their created events

### Administrator
- Access dedicated admin dashboard
- Approve or reject pending events
- View system statistics (total events, users, registrations)
- Monitor all user registrations
- Switch to user view when needed

## 📊 Core Functionality

### User Registration Flow
1. Click "Register" on landing page
2. Enter full name, email, phone, and department
3. Set a password
4. System generates unique User ID (format: USR[YY][4-digit-random])
5. Automatic login after successful registration

### Login Process
1. Click "Login" on landing page
2. Enter User ID or Email
3. Enter password
4. Redirected to user dashboard or admin dashboard based on role

### Event Creation Workflow
1. Navigate to "Create Event" section in dashboard
2. Fill event details (title, description, date, time, venue, category, capacity)
3. Submit event for approval
4. Event appears as "pending" until admin approval
5. Approved events become visible to all users

### Event Registration
1. Browse available approved events
2. Click "Register" button
3. System checks:
   - Event approval status
   - Current registration status (if already registered)
   - Event capacity
4. Pending registrations require admin approval
5. Approved registrations are confirmed

## 🔒 Security Features

- **Password Hashing**: Uses PHP's `password_hash()` with PASSWORD_DEFAULT algorithm
- **SQL Injection Prevention**: Prepared statements with parameterized queries
- **Session Management**: Secure PHP session handling
- **Role-Based Access Control**: Different features for users vs. admins
- **Input Validation**: Trimming and validation of user inputs
- **XSS Protection**: HTML special characters escaped with `htmlspecialchars()`

## 📝 API Endpoints

### `get_participants.php`
Returns JSON data of event participants.

**Parameters:**
- `event_id` (int, required) - The event ID

**Response:**
```json
{
  "total": 10,
  "approved": 8,
  "pending": 2,
  "rejected": 0,
  "participants": [...]
}
```

## 🎨 Color Scheme

- **Primary**: Purple (#9500ff)
- **Success**: Green (#10b981)
- **Danger**: Red (#ef4444)
- **Warning**: Amber (#f59e0b)
- **Dark**: Gray (#1f2937)
- **Light**: Off-white (#f9fafb)

## 🐛 Error Handling

- Database connection errors are caught and reported
- Form validation prevents empty submissions
- Duplicate email registration is prevented
- Event capacity is enforced
- Unauthorized access is restricted with redirects

## 📱 Features by Page

| Feature | Page | Role |
|---------|------|------|
| Login/Register | index.php | All |
| Create Event | dashboard.php | User |
| Browse Events | dashboard.php | User |
| Register for Event | dashboard.php | User |
| Manage Participants | dashboard.php | Event Creator |
| View Admin Stats | admin.php | Admin |
| Approve Events | admin.php | Admin |
| View All Events | admin.php | Admin |

## 🔄 Database Relationships

- **Users** → **Events** (one-to-many via `created_by`)
- **Users** → **Registrations** (one-to-many)
- **Events** → **Registrations** (one-to-many)

## 💡 Future Enhancements

- Email notifications for registration status
- Event search and filtering
- Calendar view for events
- Bulk registration import
- CSV export of participants
- Event attendance tracking
- Reviews and ratings system
- Email verification
- Forgot password functionality

## 📄 License

This project is provided as-is for educational purposes.

## 👨‍💻 Support

For issues or questions regarding the system, please contact the administrator or refer to the dashboard help section.