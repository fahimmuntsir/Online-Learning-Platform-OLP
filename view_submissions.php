<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Instructor') {
    header("Location: login.html");
    exit();
}
$conn = new mysqli("localhost", "root", "", "learning_platform");
$instructor_id = $_SESSION['userID'];

// Get all submissions for this instructor's assignments
$sql = "SELECT s.submission_id, s.file_path, s.submitted_at,
               u.UserID AS student_id, u.First_Name, u.Last_Name,
               a.assignment_number, a.deadline, a.Course_ID, c.CourseName
        FROM assignment_submission s
        JOIN assignment a ON s.assignment_id = a.assignment_id
        JOIN userinfo u ON s.student_id = u.UserID
        JOIN course c ON a.Course_ID = c.Course_ID
        WHERE a.instructor_id = ?
        ORDER BY s.submitted_at DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $instructor_id);
$stmt->execute();
$submissions = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Submissions - OLP</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        body { background: #f0f2f5; display: block; }
        .wrapper { max-width: 1100px; margin: 40px auto; padding: 0 20px; }
        .page-title { font-size: 1.9rem; font-weight: bold; color: #1a1a2e; margin-bottom: 26px; }
        .back-link { display: inline-block; margin-bottom: 20px; color: #6a11cb; font-weight: bold; text-decoration: none; }
        .back-link:hover { text-decoration: underline; }
        table { width: 100%; border-collapse: collapse; background: #fff; border-radius: 12px; overflow: hidden; box-shadow: 0 2px 12px rgba(0,0,0,0.09); }
        th { background: linear-gradient(to right, #6a11cb, #2575fc); color: #fff; padding: 13px 14px; text-align: left; font-size: 0.95rem; }
        td { padding: 11px 14px; border-bottom: 1px solid #f0f0f0; color: #333; font-size: 0.93rem; }
        tr:hover td { background: #f9f7ff; }
        .download-btn {
            display: inline-flex; align-items: center; gap: 6px;
            background: linear-gradient(135deg, #5c49c6, #2575fc);
            color: #fff; padding: 7px 16px; border-radius: 6px;
            text-decoration: none; font-size: 0.88rem; transition: background 0.2s;
        }
        .download-btn:hover { background: linear-gradient(135deg, #ff5722, #ff5722); text-decoration: none; color: #fff; }
        .no-data { text-align: center; color: #888; padding: 50px; font-size: 1.1rem; }
        .filter-row { display: flex; gap: 14px; margin-bottom: 20px; align-items: center; }
        .filter-row input, .filter-row select {
            padding: 9px 13px; border: 1.5px solid #ddd; border-radius: 7px;
            font-size: 0.93rem; color: #333; background: #fff;
        }
        .filter-row input:focus, .filter-row select:focus { border-color: #6a11cb; outline: none; }
    </style>
</head>
<body>
<div class="wrapper">
    <a href="instructor_dashboard.html" class="back-link"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
    <div class="page-title"><i class="fas fa-inbox" style="color:#6a11cb;margin-right:10px;"></i>Student Submissions</div>

    <div class="filter-row">
        <input type="text" id="search" placeholder="🔍 Search by student name or ID..." oninput="filterTable()">
        <select id="course-filter" onchange="filterTable()">
            <option value="">All Courses</option>
            <?php
            $cs = $conn->prepare("SELECT DISTINCT c.Course_ID, c.CourseName FROM course c JOIN assignment a ON c.Course_ID = a.Course_ID WHERE a.instructor_id = ?");
            $cs->bind_param("s", $instructor_id);
            $cs->execute();
            $cr = $cs->get_result();
            while($c = $cr->fetch_assoc()) {
                echo "<option value='" . htmlspecialchars($c['Course_ID']) . "'>" . htmlspecialchars($c['Course_ID']) . " - " . htmlspecialchars($c['CourseName']) . "</option>";
            }
            ?>
        </select>
    </div>

    <?php if ($submissions->num_rows === 0): ?>
        <div class="no-data"><i class="fas fa-inbox" style="font-size:3rem;color:#ccc;display:block;margin-bottom:14px;"></i>No submissions yet.</div>
    <?php else: ?>
    <table id="submissions-table">
        <thead>
            <tr>
                <th>#</th>
                <th>Student ID</th>
                <th>Student Name</th>
                <th>Course</th>
                <th>Assignment No.</th>
                <th>Submitted At</th>
                <th>Download</th>
            </tr>
        </thead>
        <tbody>
            <?php $i = 1; while($s = $submissions->fetch_assoc()): ?>
            <tr>
                <td><?= $i++ ?></td>
                <td><?= htmlspecialchars($s['student_id']) ?></td>
                <td><?= htmlspecialchars($s['First_Name'] . ' ' . $s['Last_Name']) ?></td>
                <td><?= htmlspecialchars($s['Course_ID']) ?> — <?= htmlspecialchars($s['CourseName']) ?></td>
                <td>Assignment <?= htmlspecialchars($s['assignment_number']) ?></td>
                <td><?= date('d M Y, h:i A', strtotime($s['submitted_at'])) ?></td>
                <td>
                    <a href="download_submission.php?id=<?= $s['submission_id'] ?>" class="download-btn">
                        <i class="fas fa-download"></i> Download
                    </a>
                </td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>
<script>
function filterTable() {
    var search = document.getElementById('search').value.toLowerCase();
    var courseFilter = document.getElementById('course-filter').value.toLowerCase();
    var rows = document.querySelectorAll('#submissions-table tbody tr');
    rows.forEach(function(row) {
        var text = row.textContent.toLowerCase();
        var courseCell = row.cells[3] ? row.cells[3].textContent.toLowerCase() : '';
        var matchSearch = !search || text.includes(search);
        var matchCourse = !courseFilter || courseCell.includes(courseFilter);
        row.style.display = (matchSearch && matchCourse) ? '' : 'none';
    });
}
</script>
</body>
</html>
