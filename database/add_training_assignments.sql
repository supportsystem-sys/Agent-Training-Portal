-- Add training_assignments table to existing database
-- Run this script if you already have an existing LMS database

-- Training assignments table
CREATE TABLE IF NOT EXISTS training_assignments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    course_id INT NOT NULL,
    assigned_by INT NOT NULL,
    status ENUM('assigned', 'started', 'completed') DEFAULT 'assigned',
    notes TEXT,
    assigned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
    FOREIGN KEY (assigned_by) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_course (user_id, course_id)
);
