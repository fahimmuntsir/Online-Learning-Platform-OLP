<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Admin') {
    header("Location: login.html");
    exit();
}

$conn = new mysqli("localhost", "root", "", "learning_platform");

$users = $conn->query("SELECT * FROM userinfo");
$courses = $conn->query("SELECT * FROM course");
$enrollments = $conn->query("SELECT * FROM enrollment");
$quizzes = $conn->query("SELECT * FROM quiz_description");
$announcements = $conn->query("SELECT * FROM announcement");

// Count assignments and submissions
$assign_count_res = $conn->query("SELECT COUNT(*) as cnt FROM assignment");
$assign_count = $assign_count_res ? $assign_count_res->fetch_assoc()['cnt'] : 0;
$sub_count_res = $conn->query("SELECT COUNT(*) as cnt FROM assignment_submission");
$sub_count = $sub_count_res ? $sub_count_res->fetch_assoc()['cnt'] : 0;
?>
<!DOCTYPE html>
<html>
<head>
    <title>Admin Dashboard</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; display: flex; background: #f4f4f4; }
        .sidebar {
            width: 220px; min-height: 100vh;
            background: linear-gradient(to bottom, #1a1a2e, #16213e);
            color: white; padding: 20px; position: fixed; top: 0; left: 0;
        }
        .sidebar h2 { color: #e94560; text-align: center; margin-bottom: 30px; }
        .sidebar a {
            display: block; color: white; text-decoration: none;
            padding: 10px 15px; margin: 5px 0; border-radius: 5px;
        }
        .sidebar a:hover { background: #e94560; }
        .logout { background: #e94560; text-align: center; margin-top: 20px; border-radius: 5px; }
        .main { margin-left: 220px; padding: 30px; width: calc(100% - 220px); }
        .main h1 { color: #1a1a2e; margin-bottom: 20px; }
        .cards { display: flex; flex-wrap: wrap; gap: 20px; margin-bottom: 30px; }
        .card {
            background: white; padding: 20px; border-radius: 10px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1); flex: 1; text-align: center; min-width: 130px;
        }
        .card h3 { color: #6a11cb; font-size: 40px; margin-bottom: 5px; }
        .card p { color: #666; }
        h2 { color: #1a1a2e; margin: 20px 0 10px; }
        table {
            width: 100%; border-collapse: collapse; background: white;
            border-radius: 10px; overflow: hidden;
            box-shadow: 0 0 10px rgba(0,0,0,0.1); margin-bottom: 30px;
        }
        th {
            background: linear-gradient(to right, #6a11cb, #2575fc);
            color: white; padding: 12px; text-align: left;
        }
        td { padding: 10px; border-bottom: 1px solid #eee; }
        tr:hover { background: #f9f9f9; }
        .delete-btn {
            background: #e94560; color: white; border: none;
            padding: 5px 10px; border-radius: 5px; cursor: pointer;
        }
        .download-btn {
            display: inline-flex; align-items: center; gap: 5px;
            background: linear-gradient(135deg, #6a11cb, #2575fc);
            color: white; padding: 5px 12px; border-radius: 5px;
            text-decoration: none; font-size: 0.85rem;
        }
        .download-btn:hover { background: linear-gradient(135deg, #ff5722, #ff5722); color: white; text-decoration: none; }
        .search-bar { padding: 8px 14px; border: 1.5px solid #ddd; border-radius: 7px; font-size: 0.93rem; margin-bottom: 12px; width: 320px; }
        .search-bar:focus { border-color: #6a11cb; outline: none; }
    </style>
</head>
<body>

<div class="sidebar">
    <h2>⚙️ Admin</h2>
    <a href="#users">👥 All Users</a>
    <a href="#courses">📚 All Courses</a>
    <a href="#enrollments">📋 Enrollments</a>
    <a href="#quizzes">📝 All Quizzes</a>
    <a href="#assignments">📁 Assignments</a>
    <a href="#announcements">📢 Announcements</a>
    <a href="logout.php" class="logout">🚪 Logout</a>
</div>

<div class="main">
    <h1>Admin Dashboard</h1>

    <!-- Stats Cards -->
    <div class="cards">
        <div class="card">
            <h3><?php echo $users->num_rows; ?></h3>
            <p>Total Users</p>
        </div>
        <div class="card">
            <h3><?php echo $courses->num_rows; ?></h3>
            <p>Total Courses</p>
        </div>
        <div class="card">
            <h3><?php echo $enrollments->num_rows; ?></h3>
            <p>Total Enrollments</p>
        </div>
        <div class="card">
            <h3><?php echo $quizzes->num_rows; ?></h3>
            <p>Total Quizzes</p>
        </div>
        <div class="card">
            <h3><?php echo $assign_count; ?></h3>
            <p>Assignments</p>
        </div>
        <div class="card">
            <h3><?php echo $sub_count; ?></h3>
            <p>Submissions</p>
        </div>
        <div class="card">
            <h3><?php echo $announcements->num_rows; ?></h3>
            <p>Announcements</p>
        </div>
    </div>

    <!-- Users -->
    <h2 id="users">👥 All Users</h2>
    <table>
        <tr>
            <th>User ID</th><th>First Name</th><th>Last Name</th>
            <th>Role</th><th>Email</th><th>Action</th>
        </tr>
        <?php
        $users = $conn->query("SELECT * FROM userinfo");
        while($row = $users->fetch_assoc()) {
            echo "<tr>
                <td>{$row['UserID']}</td>
                <td>{$row['First_Name']}</td>
                <td>{$row['Last_Name']}</td>
                <td>{$row['Role']}</td>
                <td>{$row['Email']}</td>
                <td><button class='delete-btn' onclick=\"location.href='admin_delete_user.php?id={$row['UserID']}'\">Delete</button></td>
            </tr>";
        }
        ?>
    </table>

    <!-- Courses -->
    <h2 id="courses">📚 All Courses</h2>
    <table>
        <tr>
            <th>Course ID</th><th>Course Name</th>
            <th>Instructor</th><th>Start Date</th><th>End Date</th>
        </tr>
        <?php
        $courses = $conn->query("SELECT * FROM course");
        while($row = $courses->fetch_assoc()) {
            echo "<tr>
                <td>{$row['Course_ID']}</td>
                <td>{$row['CourseName']}</td>
                <td>{$row['UserID']}</td>
                <td>{$row['Start_Date']}</td>
                <td>{$row['End_Date']}</td>
            </tr>";
        }
        ?>
    </table>

    <!-- Enrollments -->
    <h2 id="enrollments">📋 Enrollments</h2>
    <table>
        <tr>
            <th>Enrollment ID</th><th>Student ID</th>
            <th>Course ID</th><th>Date</th>
        </tr>
        <?php
        $enrollments = $conn->query("SELECT * FROM enrollment");
        while($row = $enrollments->fetch_assoc()) {
            echo "<tr>
                <td>{$row['EnrollmentID']}</td>
                <td>{$row['StudentID']}</td>
                <td>{$row['course_ID']}</td>
                <td>{$row['enrollmentDate']}</td>
            </tr>";
        }
        ?>
    </table>

    <!-- Quizzes -->
    <h2 id="quizzes">📝 All Quizzes</h2>
    <table>
        <tr>
            <th>Quiz No</th><th>Course ID</th><th>Description</th>
        </tr>
        <?php
        $quizzes = $conn->query("SELECT * FROM quiz_description");
        while($row = $quizzes->fetch_assoc()) {
            echo "<tr>
                <td>{$row['Quiz_NO']}</td>
                <td>{$row['Course_ID']}</td>
                <td>{$row['Description_Quiz']}</td>
            </tr>";
        }
        ?>
    </table>

    <!-- Assignments -->
    <h2 id="assignments">📁 All Assignment Submissions</h2>
    <input type="text" class="search-bar" id="assign-search" placeholder="🔍 Search by student name, ID, or course..." oninput="filterAssignments()">
    <table id="assign-table">
        <tr>
            <th>#</th>
            <th>Student ID</th>
            <th>Student Name</th>
            <th>Course ID</th>
            <th>Course Name</th>
            <th>Assignment No.</th>
            <th>Instructor</th>
            <th>Submitted At</th>
            <th>Download</th>
        </tr>
        <?php
        $assign_sql = "SELECT s.submission_id, s.file_path, s.submitted_at,
                              u.UserID AS student_id, u.First_Name, u.Last_Name,
                              a.assignment_number, a.Course_ID,
                              c.CourseName,
                              ins.First_Name AS inst_first, ins.Last_Name AS inst_last
                       FROM assignment_submission s
                       JOIN assignment a ON s.assignment_id = a.assignment_id
                       JOIN userinfo u ON s.student_id = u.UserID
                       JOIN course c ON a.Course_ID = c.Course_ID
                       JOIN userinfo ins ON a.instructor_id = ins.UserID
                       ORDER BY s.submitted_at DESC";
        $res = $conn->query($assign_sql);
        $i = 1;
        if ($res && $res->num_rows > 0) {
            while($row = $res->fetch_assoc()) {
                $submitted = date('d M Y, h:i A', strtotime($row['submitted_at']));
                echo "<tr>
                    <td>{$i}</td>
                    <td>{$row['student_id']}</td>
                    <td>{$row['First_Name']} {$row['Last_Name']}</td>
                    <td>{$row['Course_ID']}</td>
                    <td>{$row['CourseName']}</td>
                    <td>Assignment {$row['assignment_number']}</td>
                    <td>{$row['inst_first']} {$row['inst_last']}</td>
                    <td>{$submitted}</td>
                    <td><a href='download_submission.php?id={$row['submission_id']}' class='download-btn'>⬇ Download</a></td>
                </tr>";
                $i++;
            }
        } else {
            echo "<tr><td colspan='9' style='text-align:center;color:#888;padding:30px;'>No submissions found.</td></tr>";
        }
        ?>
    </table>

    <!-- Announcements -->
    <h2 id="announcements">📢 All Announcements</h2>
    <table>
        <tr>
            <th>ID</th><th>Instructor</th><th>Course ID</th>
            <th>Title</th><th>Content</th><th>Date</th>
        </tr>
        <?php
        $announcements = $conn->query("SELECT * FROM announcement");
        while($row = $announcements->fetch_assoc()) {
            echo "<tr>
                <td>{$row['Announce_ID']}</td>
                <td>{$row['UserID']}</td>
                <td>{$row['Course_ID']}</td>
                <td>{$row['Title']}</td>
                <td>{$row['Content']}</td>
                <td>{$row['Created_At']}</td>
            </tr>";
        }
        ?>
    </table>

</div>
<script>
function filterAssignments() {
    var q = document.getElementById('assign-search').value.toLowerCase();
    var rows = document.querySelectorAll('#assign-table tr');
    rows.forEach(function(row, idx) {
        if (idx === 0) return; // header
        row.style.display = !q || row.textContent.toLowerCase().includes(q) ? '' : 'none';
    });
}
</script>
</body>
</html>
