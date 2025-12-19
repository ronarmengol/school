<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth_functions.php';

// Bypass auth for CLI
if (php_sapi_name() !== 'cli') {
  check_auth();
  check_role(['super_admin']);
}

$sql = "
-- Library Categories
CREATE TABLE IF NOT EXISTS `library_categories` (
  `category_id` int(11) NOT NULL AUTO_INCREMENT,
  `category_name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  PRIMARY KEY (`category_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Library Books
CREATE TABLE IF NOT EXISTS `library_books` (
  `book_id` int(11) NOT NULL AUTO_INCREMENT,
  `category_id` int(11) DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `author` varchar(255) DEFAULT NULL,
  `isbn` varchar(20) DEFAULT NULL,
  `publisher` varchar(100) DEFAULT NULL,
  `year_published` int(4) DEFAULT NULL,
  `quantity` int(11) DEFAULT 0,
  `available_copies` int(11) DEFAULT 0,
  `location_shelf` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`book_id`),
  KEY `category_id` (`category_id`),
  CONSTRAINT `library_books_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `library_categories` (`category_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Library Issuances
CREATE TABLE IF NOT EXISTS `library_issuances` (
  `issuance_id` int(11) NOT NULL AUTO_INCREMENT,
  `book_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `issue_date` date NOT NULL,
  `due_date` date NOT NULL,
  `return_date` date DEFAULT NULL,
  `status` enum('Issued','Returned','Overdue','Lost') DEFAULT 'Issued',
  `fine_amount` decimal(10,2) DEFAULT 0.00,
  `fine_status` enum('N/A','Unpaid','Paid') DEFAULT 'N/A',
  `recorded_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`issuance_id`),
  KEY `book_id` (`book_id`),
  KEY `student_id` (`student_id`),
  KEY `recorded_by` (`recorded_by`),
  CONSTRAINT `library_issuances_ibfk_1` FOREIGN KEY (`book_id`) REFERENCES `library_books` (`book_id`) ON DELETE CASCADE,
  CONSTRAINT `library_issuances_ibfk_2` FOREIGN KEY (`student_id`) REFERENCES `students` (`student_id`) ON DELETE CASCADE,
  CONSTRAINT `library_issuances_ibfk_3` FOREIGN KEY (`recorded_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
";

// Execute the multi-query
if (mysqli_multi_query($conn, $sql)) {
  do {
    // Prepare next result set if any
    if ($result = mysqli_store_result($conn)) {
      mysqli_free_result($result);
    }
  } while (mysqli_more_results($conn) && mysqli_next_result($conn));

  // Check if categories already exist before inserting
  $check = mysqli_query($conn, "SELECT COUNT(*) as count FROM library_categories");
  $row = mysqli_fetch_assoc($check);
  if ($row['count'] == 0) {
    $insert_cats = "INSERT INTO `library_categories` (`category_name`, `description`) VALUES 
            ('Science', 'Books related to physical and natural sciences'),
            ('Literature', 'Classical and modern literary works'),
            ('History', 'Historical accounts and biographies'),
            ('Technology', 'Computer science, engineering and modern tech'),
            ('Mathematics', 'Arithmetic, Algebra, Calculus and related topics'),
            ('Arts', 'Fine arts, music, and design')";
    mysqli_query($conn, $insert_cats);
  }

  echo "Library tables created successfully!";
} else {
  echo "Error creating tables: " . mysqli_error($conn);
}
?>