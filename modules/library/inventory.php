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

// Fetch Categories for dropdown
$categories = mysqli_query($conn, "SELECT * FROM library_categories WHERE status = 'Active' ORDER BY category_name ASC");
?>

<style>
    :root {
        --lib-primary: #2c3e50;
        --lib-secondary: #3498db;
        --lib-border: #e2e8f0;
        --radius-lg: 16px;
        --transition: all 250ms ease;
    }

    .inventory-container {
        padding: 10px 0;
    }

    .inventory-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 32px;
    }

    .inventory-header h1 {
        font-size: 28px;
        font-weight: 800;
        color: var(--lib-primary);
        margin: 0;
    }

    .inventory-header p {
        color: #64748b;
        margin: 4px 0 0 0;
        font-size: 15px;
    }

    .btn-add-book {
        padding: 12px 24px;
        background: var(--lib-primary);
        color: white;
        border: none;
        border-radius: 12px;
        font-weight: 700;
        cursor: pointer;
        display: flex;
        align-items: center;
        gap: 10px;
        transition: var(--transition);
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
    }

    .btn-add-book:hover {
        background: #1e293b;
        transform: translateY(-2px);
    }

    .inventory-panel {
        background: white;
        border-radius: var(--radius-lg);
        border: 1px solid var(--lib-border);
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        overflow: hidden;
    }

    .filter-bar {
        padding: 24px;
        border-bottom: 1px solid var(--lib-border);
        background: #fafafa;
        display: flex;
        gap: 16px;
        flex-wrap: wrap;
    }

    .search-input-wrap {
        position: relative;
        flex: 1;
        min-width: 300px;
    }

    .search-input {
        width: 100%;
        padding: 12px 16px 12px 44px;
        border: 1px solid var(--lib-border);
        border-radius: 12px;
        font-size: 14px;
    }

    .search-icon {
        position: absolute;
        left: 16px;
        top: 50%;
        transform: translateY(-50%);
        color: #94a3b8;
    }

    .filter-select {
        padding: 12px 16px;
        border: 1px solid var(--lib-border);
        border-radius: 12px;
        font-size: 14px;
        min-width: 200px;
    }

    .book-table {
        width: 100%;
        border-collapse: collapse;
    }

    .book-table th {
        text-align: left;
        padding: 16px 24px;
        background: #f8fafc;
        color: #64748b;
        font-size: 12px;
        font-weight: 700;
        text-transform: uppercase;
        border-bottom: 1px solid var(--lib-border);
    }

    .book-table td {
        padding: 20px 24px;
        border-bottom: 1px solid var(--lib-border);
        font-size: 14px;
    }

    .book-table tr:hover {
        background: #fcfcfc;
    }

    .stock-badge {
        padding: 4px 10px;
        border-radius: 6px;
        font-weight: 700;
        font-size: 12px;
    }

    .stock-in {
        background: #dcfce7;
        color: #166534;
    }

    .stock-low {
        background: #fef3c7;
        color: #92400e;
    }

    .stock-out {
        background: #fee2e2;
        color: #991b1b;
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
        padding: 24px;
        border-bottom: 1px solid var(--lib-border);
        background: #fafafa;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .modal-title {
        font-size: 20px;
        font-weight: 800;
        color: var(--lib-primary);
        margin: 0;
    }

    .modal-body {
        padding: 32px;
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 20px;
    }

    .modal-footer {
        padding: 24px 32px;
        border-top: 1px solid var(--lib-border);
        display: flex;
        gap: 12px;
    }

    .form-group {
        margin-bottom: 0;
    }

    .form-group.full {
        grid-column: span 2;
    }

    .form-group label {
        display: block;
        font-size: 13px;
        font-weight: 700;
        color: var(--lib-primary);
        margin-bottom: 6px;
    }

    .form-control {
        width: 100%;
        padding: 12px;
        border: 1px solid var(--lib-border);
        border-radius: 10px;
        font-size: 14px;
    }

    .btn {
        padding: 12px 24px;
        border-radius: 12px;
        font-weight: 700;
        cursor: pointer;
        border: none;
        transition: var(--transition);
    }

    .btn-primary {
        background: var(--lib-primary);
        color: white;
        flex: 1;
    }

    .btn-ghost {
        background: #f1f5f9;
        color: #475569;
    }
</style>

<div class="inventory-container">
    <div class="inventory-header">
        <div>
            <a href="index.php"
                style="display: inline-flex; align-items: center; gap: 8px; color: #64748b; text-decoration: none; font-weight: 600; font-size: 13px; margin-bottom: 12px; transition: color 0.2s;"
                onmouseover="this.style.color='var(--lib-secondary)'" onmouseout="this.style.color='#64748b'">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                    <path d="M19 12H5M12 19l-7-7 7-7" />
                </svg>
                Back to Dashboard
            </a>
            <h1>Library Inventory</h1>
            <p>Manage books, textbooks, and academic resources.</p>
        </div>
        <button class="btn-add-book" onclick="openBookModal()">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                <path d="M12 5v14M5 12h14" />
            </svg>
            Add New Book
        </button>
    </div>

    <div class="inventory-panel">
        <div class="filter-bar">
            <div class="search-input-wrap">
                <form id="searchForm">
                    <svg class="search-icon" width="18" height="18" viewBox="0 0 24 24" fill="none"
                        stroke="currentColor" stroke-width="2.5">
                        <circle cx="11" cy="11" r="8" />
                        <path d="M21 21l-4.35-4.35" />
                    </svg>
                    <input type="text" name="search" class="search-input"
                        placeholder="Search by title, author or ISBN..."
                        value="<?php echo htmlspecialchars($search); ?>">
                </form>
            </div>
            <select name="category_id" class="filter-select" onchange="document.getElementById('searchForm').submit()">
                <option value="0">All Categories</option>
                <?php
                mysqli_data_seek($categories, 0);
                while ($c = mysqli_fetch_assoc($categories)):
                    ?>
                    <option value="<?php echo $c['category_id']; ?>" <?php echo $cat_filter == $c['category_id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($c['category_name']); ?>
                    </option>
                <?php endwhile; ?>
            </select>
        </div>

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
                            <div style="font-weight: 700; color: #1e293b;"><?php echo htmlspecialchars($book['title']); ?>
                            </div>
                            <div style="font-size: 12px; color: #64748b; margin-top: 2px;">
                                <?php echo htmlspecialchars($book['author']); ?> • ISBN:
                                <?php echo htmlspecialchars($book['isbn'] ?: 'N/A'); ?></div>
                        </td>
                        <td><span
                                style="font-weight: 600; color: var(--lib-secondary);"><?php echo htmlspecialchars($book['category_name'] ?: 'Uncategorized'); ?></span>
                        </td>
                        <td><span
                                style="font-size: 13px; color: #475569; font-weight: 600;"><?php echo htmlspecialchars($book['location_shelf'] ?: 'Not Set'); ?></span>
                        </td>
                        <td>
                            <?php
                            $stock_lvl = 'stock-out';
                            if ($book['available_copies'] > 5)
                                $stock_lvl = 'stock-in';
                            elseif ($book['available_copies'] > 0)
                                $stock_lvl = 'stock-low';
                            ?>
                            <div class="stock-badge <?php echo $stock_lvl; ?>">
                                <?php echo $book['available_copies']; ?> / <?php echo $book['quantity']; ?>
                            </div>
                        </td>
                        <td style="text-align: right;">
                            <button class="btn-ghost" style="padding: 8px 12px; border-radius: 8px; font-size: 12px;"
                                onclick="openBookModal(<?php echo htmlspecialchars(json_encode($book)); ?>)">Edit</button>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Book Modal -->
<div class="modal-overlay" id="bookModal">
    <div class="modal-content">
        <div class="modal-header">
            <h2 class="modal-title" id="modalTitle">Add New Book</h2>
            <button class="btn-ghost" style="padding: 5px; border-radius: 50%;"
                onclick="closeBookModal()">&times;</button>
        </div>
        <form action="inventory.php" method="POST">
            <div class="modal-body">
                <input type="hidden" name="book_id" id="book_id" value="0">
                <div class="form-group full">
                    <label>Book Title</label>
                    <input type="text" name="title" id="title" class="form-control" placeholder="Enter book title"
                        required>
                </div>
                <div class="form-group">
                    <label>Author</label>
                    <input type="text" name="author" id="author" class="form-control" placeholder="Author name">
                </div>
                <div class="form-group">
                    <label>Category</label>
                    <select name="category_id" id="category_id" class="form-control" required>
                        <option value="">Select Category</option>
                        <?php
                        mysqli_data_seek($categories, 0);
                        while ($c = mysqli_fetch_assoc($categories)):
                            ?>
                            <option value="<?php echo $c['category_id']; ?>">
                                <?php echo htmlspecialchars($c['category_name']); ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>ISBN</label>
                    <input type="text" name="isbn" id="isbn" class="form-control" placeholder="Add ISBN">
                </div>
                <div class="form-group">
                    <label>Total Quantity</label>
                    <input type="number" name="quantity" id="quantity" class="form-control" value="1" min="1">
                </div>
                <div class="form-group">
                    <label>Shelf Location</label>
                    <input type="text" name="location_shelf" id="location_shelf" class="form-control"
                        placeholder="e.g. Shelf A-10">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-ghost" onclick="closeBookModal()">Cancel</button>
                <button type="submit" name="save_book" class="btn btn-primary" id="saveBtn">Save Book</button>
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

    <?php if (!empty($message)): ?>
        showToast("<?php echo $message; ?>", "<?php echo $message_type; ?>");
    <?php endif; ?>
</script>

<?php include '../../includes/footer.php'; ?>