# Learning Management System (LMS)

A comprehensive Learning Management System built with PHP Core and MySQL, featuring user authentication, Training management, lesson delivery, quiz system, and progress tracking.

## Features

### Admin Features
- **Dashboard**: View system statistics (users, Trainings, lessons, quiz scores)
- **Training Management**: Create, edit, and delete Trainings
- **Lesson Management**: Add lessons with text content and video URLs
- **Quiz Management**: Create quizzes with multiple choice and essay questions
- **Question Management**: Add questions with multiple options
- **User Management**: View and manage learner accounts

### Learner Features
- **Registration & Login**: Secure user authentication
- **Training Browser**: Browse available Trainings
- **Lesson Viewer**: Watch videos and read content
- **Progress Tracking**: Automatic video progress tracking via AJAX
- **Quiz System**: Take auto-graded quizzes with instant results
- **Profile Dashboard**: View progress, quiz scores, and statistics

## Technology Stack

- **Backend**: PHP (Core, no frameworks)
- **Database**: MySQL
- **Frontend**: HTML5, CSS3, JavaScript
- **Authentication**: PHP Sessions with password hashing
- **Progress Tracking**: AJAX for real-time video progress updates

## Database Schema

The system uses the following main tables:
- `users`: User accounts (admin/learner roles)
- `Trainings`: Training information
- `lessons`: Lesson content and videos
- `quizzes`: Quiz definitions
- `questions`: Quiz questions
- `question_options`: Multiple choice options
- `quiz_attempts`: Quiz attempt records
- `answers`: User answers
- `progress`: Lesson progress tracking

## Installation & Setup

### Prerequisites
- XAMPP/WAMP/LAMP server
- PHP 7.4 or higher
- MySQL 5.7 or higher
- Web browser

### Setup Instructions

1. **Clone/Download the project**
   ```bash
   # Place the project files in your web server directory
   # For XAMPP: C:\xampp\htdocs\training
   # For WAMP: C:\wamp64\www\training
   ```

2. **Database Setup**
   - Open phpMyAdmin (http://localhost/phpmyadmin)
   - Import the database schema from `database/schema.sql`
   - This will create the `lms_system` database with sample data

3. **Database Configuration**
   - Edit `config/database.php` if needed:
     ```php
     define('DB_HOST', 'localhost');
     define('DB_NAME', 'lms_system');
     define('DB_USER', 'root');
     define('DB_PASS', ''); // Change if you have a password
     ```

4. **Access the Application**
   - Open your web browser
   - Navigate to: `http://localhost/training`
   - You'll be redirected to the login page

### Default Login Credentials

**Admin Account:**
- Email: admin@lms.com
- Password: admin123

**Test Learner Account:**
- Email: learner@test.com
- Password: admin123

## Usage Guide

### For Administrators

1. **Login** with admin credentials
2. **Create Trainings**: Go to "Trainings" → "Add New Training"
3. **Add Lessons**: Select a Training → "Lessons" → "Add New Lesson"
4. **Create Quizzes**: Select a lesson → "Quizzes" → "Add New Quiz"
5. **Add Questions**: Select a quiz → "Questions" → "Add New Question"
6. **Add Options**: For MCQ questions, add multiple options and mark correct answers

### For Learners

1. **Register** a new account or login with existing credentials
2. **Browse Trainings**: View available Trainings on the dashboard
3. **Start Learning**: Click on a Training to view lessons
4. **Watch Videos**: Video progress is automatically tracked
5. **Take Quizzes**: Complete quizzes to test your knowledge
6. **Track Progress**: View your progress in the profile section

## Key Features Explained

### Progress Tracking
- Videos automatically track watch time using JavaScript
- Progress is saved every 5 seconds via AJAX
- Lessons are marked complete when 90% of video is watched
- Progress is displayed with visual progress bars

### Quiz System
- Supports Multiple Choice Questions (MCQ) and Essay questions
- MCQ questions are auto-graded
- Essay questions are recorded but not auto-graded
- Instant results with detailed feedback
- Pass/fail status based on configurable passing score

### Security Features
- Password hashing using PHP's `password_hash()`
- Session-based authentication
- Input sanitization and validation
- SQL injection prevention using prepared statements

## File Structure

```
training/
├── config/
│   └── database.php          # Database configuration
├── includes/
│   └── functions.php         # Utility functions
├── database/
│   └── schema.sql           # Database schema and sample data
├── assets/
│   └── css/
│       └── style.css        # Main stylesheet
├── auth/
│   ├── login.php           # User login
│   ├── register.php        # User registration
│   └── logout.php          # User logout
├── admin/
│   ├── dashboard.php       # Admin dashboard
│   ├── Trainings.php         # Training management
│   ├── Training_form.php     # Add/edit Trainings
│   ├── lessons.php         # Lesson management
│   ├── lesson_form.php     # Add/edit lessons
│   ├── quizzes.php         # Quiz management
│   ├── quiz_form.php       # Add/edit quizzes
│   ├── questions.php       # Question management
│   ├── question_form.php   # Add/edit questions
│   ├── question_options.php # Option management
│   ├── option_form.php     # Add/edit options
│   └── users.php           # User management
├── learner/
│   ├── dashboard.php       # Learner dashboard
│   ├── Trainings.php         # Browse Trainings
│   ├── Training.php          # Training details
│   ├── lesson.php          # Lesson viewer
│   ├── quiz.php            # Quiz interface
│   ├── quiz_result.php     # Quiz results
│   ├── profile.php         # User profile
│   └── save_progress.php   # AJAX progress tracking
├── index.php               # Main entry point
└── README.md              # This file
```

## Customization

### Adding New Features
- **New User Roles**: Modify the `role` enum in the database and update authentication logic
- **File Uploads**: Add file upload functionality for Training materials
- **Email Notifications**: Integrate email system for notifications
- **Advanced Reporting**: Add more detailed analytics and reporting features

### Styling
- Modify `assets/css/style.css` for custom styling
- The CSS uses a responsive design that works on desktop and mobile devices
- Color scheme and branding can be easily customized

## Troubleshooting

### Common Issues

1. **Database Connection Error**
   - Check database credentials in `config/database.php`
   - Ensure MySQL is running
   - Verify database exists and schema is imported

2. **Session Issues**
   - Check PHP session configuration
   - Ensure session directory is writable

3. **Video Not Playing**
   - Ensure video URLs are accessible
   - Check browser compatibility with video format

4. **Progress Not Saving**
   - Check browser console for JavaScript errors
   - Verify AJAX requests are reaching the server
   - Check PHP error logs

### Debug Mode
To enable debug mode, add this to the top of PHP files:
```php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
```

## Contributing

This is a complete LMS system ready for production use. To contribute:
1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Test thoroughly
5. Submit a pull request

## License

This project is open source and available under the MIT License.

## Support

For support and questions, please refer to the code documentation or create an issue in the project repository.
