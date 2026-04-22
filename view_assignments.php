<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Instructor') {
    header("Location: login.html");
    exit();
}
$conn = new mysqli("localhost", "root", "", "learning_platform");
$instructor_id = $_SESSION['userID'];
$search = $_GET['course_id'] ?? '';

if ($search) {
    $sql = "SELECT a.*, c.CourseName FROM assignment a
            JOIN course c ON a.Course_ID = c.Course_ID
            WHERE a.instructor_id = ? AND a.Course_ID LIKE ?
            ORDER BY a.Course_ID, a.assignment_number ASC";
    $stmt = $conn->prepare($sql);
    $like = '%' . $search . '%';
    $stmt->bind_param("ss", $instructor_id, $like);
} else {
    $sql = "SELECT a.*, c.CourseName FROM assignment a
            JOIN course c ON a.Course_ID = c.Course_ID
            WHERE a.instructor_id = ?
            ORDER BY a.Course_ID, a.assignment_number ASC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $instructor_id);
}
$stmt->execute();
$assignments = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Assignments - OLP</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        body { background: #f0f2f5; display: block; }
        .wrapper { max-width: 1000px; margin: 40px auto; padding: 0 20px; }
        .page-title { font-size: 1.9rem; font-weight: bold; color: #1a1a2e; margin-bottom: 24px; }
        .back-link { display: inline-block; margin-bottom: 20px; color: #6a11cb; font-weight: bold; text-decoration: none; }
        .back-link:hover { text-decoration: underline; }
        .search-form { display: flex; gap: 12px; margin-bottom: 24px; }
        .search-form input {
            padding: 10px 16px; border: 1.5px solid #ddd;
            border-radius: 8px; font-size: 0.95rem; width: 300px;
        }
        .search-form input:focus { border-color: #6a11cb; outline: none; }
        .search-form button {
            padding: 10px 24px; width: auto;
            background: linear-gradient(135deg, #6a11cb, #2575fc);
            color: white; border: none; border-radius: 8px;
            cursor: pointer; font-size: 0.95rem;
        }
        .search-form button:hover { background: linear-gradient(135deg, #ff5722, #ff5722); }
        .clear-btn {
            padding: 10px 16px; width: auto;
            background: #eee; color: #555; border: none;
            border-radius: 8px; cursor: pointer; font-size: 0.95rem;
            text-decoration: none; display: flex; align-items: center;
        }
        table { width: 100%; border-collapse: collapse; background: #fff; border-radius: 12px; overflow: hidden; box-shadow: 0 2px 12px rgba(0,0,0,0.09); }
        th { background: linear-gradient(to right, #6a11cb, #2575fc); color: #fff; padding: 13px 14px; text-align: left; font-size: 0.95rem; }
        td { padding: 12px 14px; border-bottom: 1px solid #f0f0f0; color: #333; font-size: 0.93rem; }
        tr:hover td { background: #f9f7ff; }
        .no-data { text-align: center; color: #888; padding: 50px; font-size: 1.1rem; }
        .badge {
            display: inline-block; padding: 3px 10px;
            border-radius: 20px; font-size: 0.8rem; font-weight: bold;
            background: #ede7ff; color: #6a11cb;
        }
        .download-btn {
            display: inline-flex; align-items: center; gap: 6px;
            padding: 6px 14px; border-radius: 8px;
            background: linear-gradient(135deg, #6a11cb, #2575fc);
            color: #fff; text-decoration: none; font-size: 0.85rem; font-weight: 600;
            transition: opacity 0.2s;
        }
        .download-btn:hover { opacity: 0.85; }
    </style>
</head>
<body>
<div class="wrapper">
    <a href="instructor_dashboard.php" class="back-link"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
    <div class="page-title"><i class="fas fa-list-alt" style="color:#6a11cb;margin-right:10px;"></i>My Assignments</div>

    <!-- Search -->
    <form method="GET" class="search-form">
        <input type="text" name="course_id" placeholder="🔍 Search by Course Code (e.g. CSE312)" value="<?= htmlspecialchars($search) ?>">
        <button type="submit"><i class="fas fa-search"></i> Search</button>
        <?php if ($search): ?>
            <a href="view_assignments.php" class="clear-btn"><i class="fas fa-times"></i> Clear</a>
        <?php endif; ?>
    </form>

    <?php if ($assignments->num_rows === 0): ?>
        <div class="no-data">
            <i class="fas fa-inbox" style="font-size:3rem;color:#ccc;display:block;margin-bottom:14px;"></i>
            <?= $search ? "No assignments found for '$search'." : "No assignments created yet." ?>
        </div>
    <?php else: ?>
    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Course Code</th>
                <th>Course Name</th>
                <th>Assignment No.</th>
                <th>Description</th>
                <th>Deadline</th>
                <th>Question File</th>
            </tr>
        </thead>
        <tbody>
            <?php $i = 1; while($a = $assignments->fetch_assoc()): ?>
            <tr>
                <td><?= $i++ ?></td>
                <td><span class="badge"><?= htmlspecialchars($a['Course_ID']) ?></span></td>
                <td><?= htmlspecialchars($a['CourseName']) ?></td>
                <td>Assignment <?= htmlspecialchars($a['assignment_number']) ?></td>
                <td><?= $a['description'] ? htmlspecialchars($a['description']) : '<span style="color:#aaa;">—</span>' ?></td>
                <td><?= date('d M Y, h:i A', strtotime($a['deadline'])) ?></td>
                <td>
                    <?php if (!empty($a['question_file']) && file_exists($a['question_file'])): ?>
                        <a href="<?= htmlspecialchars($a['question_file']) ?>" download class="download-btn">
                            <i class="fas fa-download"></i> Download
                        </a>
                    <?php else: ?>
                        <span style="color:#aaa;">—</span>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>
</body>
</html>