<?php
require_once '../../includes/auth_functions.php';
check_auth();
check_role(['super_admin', 'admin']);

$error = "";
$success = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $class_name = trim($_POST['class_name']);
    $section_name = trim($_POST['section_name']);

    if (empty($class_name)) {
        $error = "Class Name is required.";
    } else {
        $sql = "INSERT INTO classes (class_name, section_name) VALUES (?, ?)";
        if ($stmt = mysqli_prepare($conn, $sql)) {
            mysqli_stmt_bind_param($stmt, "ss", $class_name, $section_name);
            if (mysqli_stmt_execute($stmt)) {
                $success = "Class added successfully.";
                // box_msg? redirect?
                header("Location: index.php");
                exit();
            } else {
                $error = "Something went wrong. Please try again.";
            }
            mysqli_stmt_close($stmt);
        }
    }
}

$page_title = "Add Class";
include '../../includes/header.php';
?>

<div style="max-width: 600px; margin: 0 auto;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <h3>Add New Class</h3>
        <a href="index.php" class="btn btn-danger">Back</a>
    </div>

    <?php if($error): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>

    <div class="card" style="background: white; padding: 20px; border-radius: 8px;">
        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="POST">
            <div class="form-group">
                <label for="class_name" class="form-label">Class Name (e.g. Grade 1)</label>
                <input type="text" name="class_name" class="form-control" required>
            </div>
            <div class="form-group">
                <label for="section_name" class="form-label">Section (e.g. A, North)</label>
                <input type="text" name="section_name" class="form-control">
            </div>
            <div class="form-group">
                <button type="submit" class="btn btn-primary">Save Class</button>
            </div>
        </form>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>
