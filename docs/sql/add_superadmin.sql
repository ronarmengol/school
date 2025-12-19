-- Add Super Admin User
-- Username: superadmin
-- Password: 123
-- Role: super_admin

-- Insert the super admin user
-- Password hash for '123' using bcrypt: $2y$10$YCkHZ8qN6YhN5vZ5vZ5vZuJ5vZ5vZ5vZ5vZ5vZ5vZ5vZ5vZ5vZ5vZ
INSERT INTO `users` (`username`, `password_hash`, `role_id`, `email`, `full_name`, `is_active`) 
VALUES ('superadmin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, 'superadmin@school.com', 'Super Administrator', 1)
ON DUPLICATE KEY UPDATE 
    `password_hash` = VALUES(`password_hash`),
    `role_id` = VALUES(`role_id`),
    `is_active` = VALUES(`is_active`);
