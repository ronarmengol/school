<?php
require_once '../../includes/auth_functions.php';
require_once '../../includes/load_settings.php';
require_once '../../config/database.php';
check_auth();
check_role(['super_admin', 'admin', 'teacher']);

$page_title = "Library Inventory";
include '../../includes/header.php';

// Handle Save Book
$message = "";
$message_type = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_book'])) {
    $book_id = isset($_POST['book_id']) ? intval($_POST['book_id']) : 0;
    $title = mysqli_real_escape_string($conn, $_POST['title']);
    $author = mysqli_real_escape_string($conn, $_POST['author']);
    $cat_id = intval($_POST['category_id']);
    $isbn = mysqli_real_escape_string($conn, $_POST['isbn']);
    $quantity = intval($_POST['quantity']);
    $location = mysqli_real_escape_string($conn, $_POST['location_shelf']);

    if (empty($title)) {
        $message = "Book title is required.";
        $message_type = "error";
    } else {
        if ($book_id > 0) {
            $sql = "UPDATE library_books SET title = ?, author = ?, category_id = ?, isbn = ?, quantity = ?, location_shelf = ? WHERE book_id = ?";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "ssisisi", $title, $author, $cat_id, $isbn, $quantity, $location, $book_id);
        } else {
            $sql = "INSERT INTO library_books (title, author, category_id, isbn, quantity, available_copies, location_shelf) VALUES (?, ?, ?, ?, ?, ?, ?)";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "ssisiss", $title, $author, $cat_id, $isbn, $quantity, $quantity, $location);
        }

        if (mysqli_stmt_execute($stmt)) {
            $message = $book_id > 0 ? "Book updated successfully!" : "Book added successfully!";
            $message_type = "success";
        } else {
            $message = "Error saving book: " . mysqli_error($conn);
            $message_type = "error";
        }
    }
}

// Fetch Books
$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';
$cat_filter = isset($_GET['category_id']) ? intval($_GET['category_id']) : 0;

$where = "WHERE 1=1";
if (!empty($search))
    $where .= " AND (b.title LIKE '%$search%' OR b.author LIKE '%$search%' OR b.isbn LIKE '%$search%')";
if ($cat_filter > 0)
    $where .= " AND b.category_id = $cat_filter";

$sql = "SELECT b.*, c.category_name FROM library_books b LEFT JOIN library_categories c ON b.category_id = c.category_id $where ORDER BY b.title ASC";
$books = mysqli_query($conn, $sql);
$total_books = mysqli_num_rows($books);

// Fetch Categories for dropdown
$categories = mysqli_query($conn, "SELECT * FROM library_categories WHERE status = 'Active' ORDER BY category_name ASC");

// Stats
$stats_total = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM library_books"))['count'] ?? 0;
$stats_available = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(available_copies) as count FROM library_books"))['count'] ?? 0;
$stats_low = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM library_books WHERE available_copies > 0 AND available_copies <= 3"))['count'] ?? 0;
?>

<style>
    :root {
        --lib-primary: #8b5cf6;
        --lib-primary-dark: #7c3aed;
        --lib-primary-light: #c4b5fd;
        --lib-secondary: #0ea5e9;
        --lib-success: #10b981;
        --lib-warning: #f59e0b;
        --lib-danger: #ef4444;
        --lib-bg: #f8fafc;
        --lib-card: #ffffff;
        --lib-border: #e2e8f0;
        --lib-text: #1e293b;
        --lib-muted: #64748b;
        --lib-light: #94a3b8;
    }

    .inventory-page {
        min-height: 100vh;
        padding-bottom: 60px;
    }

    .inventory-container {
        max-width: 1200px;
        margin: 0 auto;
    }

    /* Header Section */
    .page-header {
        background: linear-gradient(135deg, var(--lib-primary) 0%, var(--lib-primary-dark) 100%);
        color: white;
        padding: 32px;
        border-radius: 20px;
        margin-bottom: 32px;
        position: relative;
        overflow: hidden;
    }

    .page-header::before {
        content: '';
        position: absolute;
        top: -50%;
        right: -10%;
        width: 300px;
        height: 300px;
        background: rgba(255, 255, 255, 0.1);
        border-radius: 50%;
    }

    .header-content {
        position: relative;
        z-index: 1;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .header-text h1 {
        font-size: 28px;
        font-weight: 800;
        margin: 0 0 8px 0;
    }

    .header-text p {
        font-size: 15px;
        opacity: 0.9;
        margin: 0;
    }

    .btn-back {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 12px 20px;
        background: rgba(255, 255, 255, 0.2);
        color: white;
        border: 2px solid rgba(255, 255, 255, 0.3);
        border-radius: 12px;
        font-weight: 600;
        font-size: 14px;
        text-decoration: none;
        transition: all 0.2s ease;
    }

    .btn-back:hover {
        background: rgba(255, 255, 255, 0.3);
        transform: translateY(-2px);
    }

    /* Stats Cards */
    .stats-row {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 20px;
        margin-bottom: 32px;
    }

    .stat-card {
        background: var(--lib-card);
        padding: 24px;
        border-radius: 16px;
        border: 1px solid var(--lib-border);
        display: flex;
        align-items: center;
        gap: 16px;
        transition: all 0.2s ease;
    }

    .stat-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 10px 25px -10px rgba(139, 92, 246, 0.2);
    }

    .stat-icon {
        width: 56px;
        height: 56px;
        border-radius: 14px;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }

    .stat-icon.total {
        background: linear-gradient(135deg, #ede9fe 0%, #ddd6fe 100%);
        color: #7c3aed;
    }

    .stat-icon.available {
        background: linear-gradient(135deg, #dcfce7 0%, #bbf7d0 100%);
        color: #16a34a;
    }

    .stat-icon.low {
        background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
        color: #d97706;
    }

    .stat-value {
        font-size: 28px;
        font-weight: 800;
        color: var(--lib-text);
    }

    .stat-label {
        font-size: 13px;
        color: var(--lib-muted);
        margin-top: 4px;
        font-weight: 500;
    }

    /* Table Card */
    .table-card {
        background: var(--lib-card);
        border-radius: 20px;
        border: 1px solid var(--lib-border);
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
        overflow: hidden;
    }

    .card-header {
        padding: 24px 32px;
        border-bottom: 1px solid var(--lib-border);
        display: flex;
        align-items: center;
        justify-content: space-between;
        background: linear-gradient(to right, #fafafb, #f8fafc);
        flex-wrap: wrap;
        gap: 16px;
    }

    .card-header-left {
        display: flex;
        align-items: center;
        gap: 16px;
    }

    .card-header-icon {
        width: 48px;
        height: 48px;
        background: linear-gradient(135deg, var(--lib-primary) 0%, var(--lib-primary-dark) 100%);
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        box-shadow: 0 4px 12px rgba(139, 92, 246, 0.3);
    }

    .card-header-text h2 {
        margin: 0;
        font-size: 20px;
        font-weight: 800;
        color: var(--lib-text);
    }

    .card-header-text p {
        margin: 4px 0 0 0;
        font-size: 14px;
        color: var(--lib-muted);
    }

    .btn-add {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 12px 20px;
        background: linear-gradient(135deg, var(--lib-primary) 0%, var(--lib-primary-dark) 100%);
        color: white;
        border: none;
        border-radius: 12px;
        font-weight: 700;
        font-size: 14px;
        cursor: pointer;
        transition: all 0.2s ease;
        box-shadow: 0 4px 12px rgba(139, 92, 246, 0.25);
    }

    .btn-add:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 16px rgba(139, 92, 246, 0.35);
    }

    /* Filter Bar */
    .filter-bar {
        padding: 20px 32px;
        border-bottom: 1px solid var(--lib-border);
        display: flex;
        gap: 16px;
        flex-wrap: wrap;
        background: #fafafa;
    }

    .search-wrap {
        position: relative;
        flex: 1;
        min-width: 280px;
    }

    .search-wrap .search-icon {
        position: absolute;
        left: 16px;
        top: 50%;
        transform: translateY(-50%);
        color: var(--lib-muted);
        pointer-events: none;
    }

    .search-input {
        width: 100%;
        padding: 14px 16px 14px 48px;
        border: 2px solid var(--lib-border);
        border-radius: 12px;
        font-size: 14px;
        transition: all 0.2s ease;
        background: white;
    }

    .search-input:focus {
        outline: none;
        border-color: var(--lib-primary);
        box-shadow: 0 0 0 4px rgba(139, 92, 246, 0.1);
    }

    .filter-select {
        padding: 14px 40px 14px 16px;
        border: 2px solid var(--lib-border);
        border-radius: 12px;
        font-size: 14px;
        min-width: 180px;
        background: white;
        cursor: pointer;
        appearance: none;
        background-image: url("data:image/svg+xml,%3Csvg width='12' height='8' viewBox='0 0 12 8' fill='none' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath d='M1 1.5L6 6.5L11 1.5' stroke='%2364748b' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'/%3E%3C/svg%3E");
        background-repeat: no-repeat;
        background-position: right 14px center;
    }

    /* Table */
    .book-table {
        width: 100%;
        border-collapse: collapse;
    }

    .book-table th {
        text-align: left;
        padding: 14px 24px;
        background: #f8fafc;
        color: var(--lib-muted);
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        border-bottom: 1px solid var(--lib-border);
    }

    .book-table td {
        padding: 20px 24px;
        border-bottom: 1px solid var(--lib-border);
        font-size: 14px;
        color: var(--lib-text);
        vertical-align: middle;
    }

    .book-table tr:hover {
        background: linear-gradient(90deg, #faf5ff 0%, transparent 50%);
    }

    .book-table tr:last-child td {
        border-bottom: none;
    }

    .book-info .title {
        font-weight: 700;
        color: var(--lib-text);
        margin-bottom: 4px;
    }

    .book-info .meta {
        font-size: 12px;
        color: var(--lib-muted);
    }

    .category-badge {
        display: inline-flex;
        padding: 4px 12px;
        background: #ede9fe;
        color: #7c3aed;
        border-radius: 6px;
        font-size: 12px;
        font-weight: 600;
    }

    .location-text {
        font-size: 13px;
        color: var(--lib-muted);
        font-weight: 500;
    }

    .stock-badge {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 6px 12px;
        border-radius: 8px;
        font-weight: 700;
        font-size: 12px;
    }

    .stock-in {
        background: #dcfce7;
        color: #16a34a;
    }

    .stock-low {
        background: #fef3c7;
        color: #d97706;
    }

    .stock-out {
        background: #fee2e2;
        color: #dc2626;
    }

    .btn-edit {
        padding: 8px 14px;
        background: #f1f5f9;
        color: var(--lib-muted);
        border: none;
        border-radius: 8px;
        font-weight: 600;
        font-size: 12px;
        cursor: pointer;
        transition: all 0.2s ease;
    }

    .btn-edit:hover {
        background: #e2e8f0;
        color: var(--lib-text);
    }

    /* Modal */
    .modal-overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(15, 23, 42, 0.6);
        backdrop-filter: blur(4px);
        display: none;
        justify-content: center;
        align-items: center;
        z-index: 10000;
    }

    .modal-overlay.active {
        display: flex;
    }

    .modal-content {
        background: white;
        width: 100%;
        max-width: 600px;
        border-radius: 20px;
        box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
        overflow: hidden;
    }

    .modal-header {
        padding: 24px 32px;
        border-bottom: 1px solid var(--lib-border);
        background: linear-gradient(to right, #fafafb, #f8fafc);
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .modal-title {
        font-size: 20px;
        font-weight: 800;
        color: var(--lib-text);
        margin: 0;
    }

    .modal-close {
        width: 36px;
        height: 36px;
        background: #f1f5f9;
        border: none;
        border-radius: 10px;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        color: var(--lib-muted);
        transition: all 0.2s ease;
    }

    .modal-close:hover {
        background: #e2e8f0;
        color: var(--lib-text);
    }

    .modal-body {
        padding: 32px;
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 20px;
    }

    .form-group {
        margin-bottom: 0;
    }

    .form-group.full {
        grid-column: span 2;
    }

    .form-label {
        display: block;
        font-size: 13px;
        font-weight: 600;
        color: var(--lib-text);
        margin-bottom: 8px;
    }

    .form-input,
    .form-select-modal {
        width: 100%;
        padding: 14px 16px;
        border: 2px solid var(--lib-border);
        border-radius: 12px;
        font-size: 14px;
        transition: all 0.2s ease;
        background: var(--lib-bg);
    }

    .form-input:focus,
    .form-select-modal:focus {
        outline: none;
        border-color: var(--lib-primary);
        background: white;
        box-shadow: 0 0 0 4px rgba(139, 92, 246, 0.1);
    }

    .modal-footer {
        padding: 24px 32px;
        border-top: 1px solid var(--lib-border);
        display: flex;
        gap: 12px;
        background: #fafafa;
    }

    .btn-cancel {
        padding: 14px 24px;
        background: white;
        color: var(--lib-muted);
        border: 2px solid var(--lib-border);
        border-radius: 12px;
        font-weight: 700;
        cursor: pointer;
        transition: all 0.2s ease;
    }

    .btn-cancel:hover {
        background: #f8fafc;
        color: var(--lib-text);
    }

    .btn-save {
        flex: 1;
        padding: 14px 24px;
        background: linear-gradient(135deg, var(--lib-primary) 0%, var(--lib-primary-dark) 100%);
        color: white;
        border: none;
        border-radius: 12px;
        font-weight: 700;
        cursor: pointer;
        transition: all 0.2s ease;
        box-shadow: 0 4px 12px rgba(139, 92, 246, 0.25);
    }

    .btn-save:hover {
        transform: translateY(-1px);
        box-shadow: 0 6px 16px rgba(139, 92, 246, 0.35);
    }

    /* Empty State */
    .empty-state {
        text-align: center;
        padding: 80px 40px;
    }

    .empty-icon {
        width: 80px;
        height: 80px;
        margin: 0 auto 20px;
        background: #ede9fe;
        border-radius: 20px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: var(--lib-primary);
    }

    .empty-title {
        font-size: 20px;
        font-weight: 700;
        color: var(--lib-text);
        margin-bottom: 8px;
    }

    .empty-text {
        font-size: 14px;
        color: var(--lib-muted);
    }

    /* Responsive */
    @media (max-width: 768px) {
        .inventory-container {
            padding: 0 16px;
        }

        .page-header {
            padding: 24px;
        }

        .header-content {
            flex-direction: column;
            align-items: flex-start;
            gap: 16px;
        }

        .stats-row {
            grid-template-columns: 1fr;
        }

        .card-header {
            flex-direction: column;
            align-items: flex-start;
        }

        .filter-bar {
            flex-direction: column;
        }

        .search-wrap {
            min-width: 100%;
        }

        .modal-body {
            grid-template-columns: 1fr;
        }

        .form-group.full {
            grid-column: span 1;
        }
    }
</style>

<div class="inventory-page">
    <div class="inventory-container">
        <!-- Page Header -->
        <div class="page-header">
            <div class="header-content">
                <div class="header-text">
                    <h1>Library Inventory</h1>
                    <p>Manage books, textbooks, and academic resources in your library collection.</p>
                </div>
                <a href="index.php" class="btn-back">
                    <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                    Back to Library
                </a>
            </div>
        </div>

        <!-- Stats Row -->
        <div class="stats-row">
            <div class="stat-card">
                <div class="stat-icon total">
                    <svg width="28" height="28" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                    </svg>
                </div>
                <div>
                    <div class="stat-value"><?php echo $stats_total; ?></div>
                    <div class="stat-label">Total Book Titles</div>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon available">
                    <svg width="28" height="28" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <div>
                    <div class="stat-value"><?php echo $stats_available; ?></div>
                    <div class="stat-label">Available Copies</div>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon low">
                    <svg width="28" height="28" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                </div>
                <div>
                    <div class="stat-value"><?php echo $stats_low; ?></div>
                    <div class="stat-label">Low Stock Titles</div>
                </div>
            </div>
        </div>

        <!-- Table Card -->
        <div class="table-card">
            <div class="card-header">
                <div class="card-header-left">
                    <div class="card-header-icon">
                        <svg width="24" height="24" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                        </svg>
                    </div>
                    <div class="card-header-text">
                        <h2>Book Inventory</h2>
                        <p><?php echo $total_books; ?> books found</p>
                    </div>
                </div>
                <button class="btn-add" onclick="openBookModal()">
                    <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4" />
                    </svg>
                    Add New Book
                </button>
            </div>

            <div class="filter-bar">
                <div class="search-wrap">
                    <form id="searchForm">
                        <svg class="search-icon" width="18" height="18" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                        <input type="text" name="search" class="search-input"
                            placeholder="Search by title, author or ISBN..."
                            value="<?php echo htmlspecialchars($search); ?>">
                    </form>
                </div>
                <select name="category_id" class="filter-select"
                    onchange="document.getElementById('searchForm').submit()">
                    <option value="0">All Categories</option>
                    <?php
                    mysqli_data_seek($categories, 0);
                    while ($c = mysqli_fetch_assoc($categories)): ?>
                        <option value="<?php echo $c['category_id']; ?>" <?php echo $cat_filter == $c['category_id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($c['category_name']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>

            <?php if ($total_books > 0): ?>
                <table class="book-table">
                    <thead>
                        <tr>
                            <th>Book Details</th>
                            <th>Category</th>
                            <th>Location</th>
                            <th>Stock</th>
                            <th style="text-align: right;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($book = mysqli_fetch_assoc($books)): ?>
                            <tr>
                                <td>
                                    <div class="book-info">
                                        <div class="title"><?php echo htmlspecialchars($book['title']); ?></div>
                                        <div class="meta"><?php echo htmlspecialchars($book['author']); ?> • ISBN:
                                            <?php echo htmlspecialchars($book['isbn'] ?: 'N/A'); ?></div>
                                    </div>
                                </td>
                                <td>
                                    <span
                                        class="category-badge"><?php echo htmlspecialchars($book['category_name'] ?: 'Uncategorized'); ?></span>
                                </td>
                                <td>
                                    <span
                                        class="location-text"><?php echo htmlspecialchars($book['location_shelf'] ?: 'Not Set'); ?></span>
                                </td>
                                <td>
                                    <?php
                                    $stock_lvl = 'stock-out';
                                    if ($book['available_copies'] > 5)
                                        $stock_lvl = 'stock-in';
                                    elseif ($book['available_copies'] > 0)
                                        $stock_lvl = 'stock-low';
                                    ?>
                                    <span class="stock-badge <?php echo $stock_lvl; ?>"><?php echo $book['available_copies']; ?>
                                        / <?php echo $book['quantity']; ?></span>
                                </td>
                                <td style="text-align: right;">
                                    <button class="btn-edit"
                                        onclick="openBookModal(<?php echo htmlspecialchars(json_encode($book)); ?>)">Edit</button>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="empty-state">
                    <div class="empty-icon">
                        <svg width="40" height="40" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                        </svg>
                    </div>
                    <div class="empty-title">No Books Found</div>
                    <div class="empty-text">Start by adding your first book to the library inventory.</div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Book Modal -->
<div class="modal-overlay" id="bookModal">
    <div class="modal-content">
        <div class="modal-header">
            <h2 class="modal-title" id="modalTitle">Add New Book</h2>
            <button class="modal-close" onclick="closeBookModal()">
                <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
        <form action="inventory.php" method="POST">
            <div class="modal-body">
                <input type="hidden" name="book_id" id="book_id" value="0">
                <div class="form-group full">
                    <label class="form-label">Book Title *</label>
                    <input type="text" name="title" id="title" class="form-input" placeholder="Enter book title"
                        required>
                </div>
                <div class="form-group">
                    <label class="form-label">Author</label>
                    <input type="text" name="author" id="author" class="form-input" placeholder="Author name">
                </div>
                <div class="form-group">
                    <label class="form-label">Category *</label>
                    <select name="category_id" id="category_id" class="form-select-modal" required>
                        <option value="">Select Category</option>
                        <?php
                        mysqli_data_seek($categories, 0);
                        while ($c = mysqli_fetch_assoc($categories)): ?>
                            <option value="<?php echo $c['category_id']; ?>">
                                <?php echo htmlspecialchars($c['category_name']); ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">ISBN</label>
                    <input type="text" name="isbn" id="isbn" class="form-input" placeholder="ISBN number">
                </div>
                <div class="form-group">
                    <label class="form-label">Total Quantity</label>
                    <input type="number" name="quantity" id="quantity" class="form-input" value="1" min="1">
                </div>
                <div class="form-group">
                    <label class="form-label">Shelf Location</label>
                    <input type="text" name="location_shelf" id="location_shelf" class="form-input"
                        placeholder="e.g. Shelf A-10">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-cancel" onclick="closeBookModal()">Cancel</button>
                <button type="submit" name="save_book" class="btn-save" id="saveBtn">Save Book</button>
            </div>
        </form>
    </div>
</div>

<script>
    function openBookModal(data = null) {
        if (data) {
            document.getElementById('modalTitle').innerText = 'Edit Book';
            document.getElementById('saveBtn').innerText = 'Update Book';
            document.getElementById('book_id').value = data.book_id;
            document.getElementById('title').value = data.title;
            document.getElementById('author').value = data.author;
            document.getElementById('category_id').value = data.category_id;
            document.getElementById('isbn').value = data.isbn;
            document.getElementById('quantity').value = data.quantity;
            document.getElementById('location_shelf').value = data.location_shelf;
        } else {
            document.getElementById('modalTitle').innerText = 'Add New Book';
            document.getElementById('saveBtn').innerText = 'Save Book';
            document.getElementById('book_id').value = '0';
            document.getElementById('title').value = '';
            document.getElementById('author').value = '';
            document.getElementById('isbn').value = '';
            document.getElementById('quantity').value = '1';
            document.getElementById('location_shelf').value = '';
        }
        document.getElementById('bookModal').classList.add('active');
    }

    function closeBookModal() {
        document.getElementById('bookModal').classList.remove('active');
    }

    document.getElementById('bookModal').addEventListener('click', function (e) {
        if (e.target === this) closeBookModal();
    });

    <?php if (!empty($message)): ?>
        document.addEventListener('DOMContentLoaded', function () {
            <?php if ($message_type === 'success'): ?>
                showToastSuccess("<?php echo addslashes($message); ?>");
            <?php else: ?>
                showToastError("<?php echo addslashes($message); ?>");
            <?php endif; ?>
        });
    <?php endif; ?>
</script>

<?php include '../../includes/footer.php'; ?>