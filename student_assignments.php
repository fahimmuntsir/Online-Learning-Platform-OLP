<?php
session_start();
$conn = new mysqli("localhost", "root", "", "learning_platform");
$userId = $_SESSION['userID'] ?? '';
$userData = [];
if ($userId) {
    $stmt = $conn->prepare("SELECT * FROM userinfo WHERE UserID = ?");
    $stmt->bind_param("s", $userId);
    $stmt->execute();
    $userData = $stmt->get_result()->fetch_assoc();
}

// ✅ GetStudentProgress Procedure দিয়ে progress data আনছি
$progressRows = [];
if ($userId) {
    $sidStmt = $conn->prepare("SELECT StudentID FROM student WHERE UserID = ?");
    $sidStmt->bind_param("s", $userId);
    $sidStmt->execute();
    $sidData = $sidStmt->get_result()->fetch_assoc();
    if ($sidData) {
        $sid = $sidData['StudentID'];
        $progResult = $conn->query("CALL GetStudentProgress($sid)");
        if ($progResult) {
            while ($row = $progResult->fetch_assoc()) {
                $progressRows[] = $row;
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard - OLP</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        .top-bar {
            position: fixed;
            top: 0;
            right: 0;
            background: white;
            padding: 10px 24px;
            text-align: right;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            z-index: 999;
            min-width: 260px;
        }
        .top-bar .user-name { font-weight: bold; font-size: 1rem; color: #1a1a2e; }
        .top-bar .user-id { font-size: 0.85rem; color: #555; margin-top: 2px; }
        .top-bar .user-email { font-size: 0.78rem; color: #888; margin-top: 2px; }

        /* Progress Table Style */
        .progress-table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0,0,0,0.07);
            margin-top: 16px;
        }
        .progress-table thead tr {
            background: linear-gradient(135deg, #3b3b98, #1a1a2e);
            color: white;
        }
        .progress-table th, .progress-table td {
            padding: 12px 16px;
            text-align: left;
            font-size: 0.9rem;
        }
        .progress-table tbody tr:nth-child(even) {
            background: #f9f9f9;
        }
        .progress-table tbody tr:hover {
            background: #f0f0ff;
        }
        .progress-bar-wrap {
            background: #e0e0e0;
            border-radius: 20px;
            height: 12px;
            width: 120px;
            display: inline-block;
            vertical-align: middle;
            margin-right: 8px;
        }
        .progress-bar-fill {
            background: linear-gradient(135deg, #3b3b98, #6c63ff);
            height: 12px;
            border-radius: 20px;
        }
    </style>
</head>
<body>
    <div class="top-bar">
        <div class="user-name">
            <?php echo htmlspecialchars(($userData['First_Name'] ?? '') . ' ' . ($userData['Last_Name'] ?? '')); ?>
        </div>
        <div class="user-id">
            ID: <?php echo htmlspecialchars($userData['UserID'] ?? ''); ?>
        </div>
        <div class="user-email">
            <?php echo htmlspecialchars($userData['Email'] ?? ''); ?>
        </div>
    </div>

    <div class="dashboard-container">
        <nav class="sidebar">
            <div class="logo">
                <h2>Online Learning Platform</h2>
            </div>
            <ul>
                <li><a href="#enroll-courses"><i class="fas fa-book-open"></i> Enroll in Courses</a></li>
                <li><a href="#your-courses"><i class="fas fa-book-reader"></i> Your Courses</a></li>
                <li><a href="#quizzes"><i class="fas fa-edit"></i> Take Quizzes</a></li>
                <li><a href="#assignments"><i class="fas fa-file-upload"></i> Assignments</a></li>
                <li><a href="#announcements"><i class="fas fa-bullhorn"></i> Announcements</a></li>
                <!-- ✅ নতুন Progress link -->
                <li><a href="#progress"><i class="fas fa-chart-line"></i> My Progress</a></li>
            </ul>
            <a href="logout.php"><button class="logout-btn">Logout</button></a>
        </nav>

        <main class="content">

            <section id="enroll-courses">
                <div class="course-card">
                    <h1>Available Courses to Enroll</h1>
                    <a href="browse_courses.php"><button class="browse-btn">Browse Courses</button></a>
                </div>
            </section>

            <section id="your-courses">
                <div class="course-card">
                    <h1>Your Courses</h1>
                    <a href="show_courses.html"><button>Show Courses</button></a>
                </div>
            </section>

            <section id="quizzes">
                <div class="quiz-card">
                    <h1>Available Quizzes</h1>
                    <a href="available_quizes.php">
                        <button>View Quizzes</button>
                    </a>
                    <a href="view_quiz_results.php">
                        <button style="margin-top:10px;">View My Results</button>
                    </a>
                </div>
            </section>

            <section id="assignments">
                <div class="quiz-card">
                    <h1>Assignments</h1>
                    <a href="student_assignments.php"><button>View & Submit Assignments</button></a>
                </div>
            </section>

            <section id="announcements">
                <h1>Announcements</h1>
                <div id="announcement-container"></div>
            </section>

            <!-- ✅ নতুন Progress Section — GetStudentProgress Procedure ব্যবহার করছে -->
            <section id="progress">
                <h1>My Course Progress</h1>
                <?php if (count($progressRows) > 0): ?>
                    <table class="progress-table">
                        <thead>
                            <tr>
                                <th>Course Name</th>
                                <th>Course ID</th>
                                <th>Quiz No</th>
                                <th>Progress</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($progressRows as $row): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($row['CourseName']); ?></td>
                                    <td><?php echo htmlspecialchars($row['Course_ID']); ?></td>
                                    <td>Quiz <?php echo htmlspecialchars($row['Quiz_NO']); ?></td>
                                    <td>
                                        <div class="progress-bar-wrap">
                                            <div class="progress-bar-fill" style="width: <?php echo $row['Percentage']; ?>%"></div>
                                        </div>
                                        <?php echo $row['Percentage']; ?>%
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p>কোনো progress data নেই। আগে কোনো course এ enroll করো।</p>
                <?php endif; ?>
            </section>

        </main>
    </div>

    <script>
        fetch('show_announcements.php')
            .then(response => response.json())
            .then(data => {
                const announcementContainer = document.getElementById('announcement-container');
                if (data.length === 0) {
                    announcementContainer.innerHTML = '<p>No announcements available for your courses.</p>';
                    return;
                }
                data.forEach(announcement => {
                    const announcementCard = document.createElement('div');
                    announcementCard.classList.add('announcement-card');
                    announcementCard.innerHTML = `
                        <h3>${announcement.Course_ID} - ${announcement.Title}</h3>
                        <p>${announcement.Content}</p>
                        <small>Created At: ${new Date(announcement.Created_At).toLocaleString()}</small>
                    `;
                    announcementContainer.appendChild(announcementCard);
                });
            })
            .catch(error => console.error('Error fetching announcements:', error));
    </script>
</body>
</html>