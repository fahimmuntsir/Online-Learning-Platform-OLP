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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Instructor Dashboard - OLP</title>
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
        .top-bar .user-name {
            font-weight: bold;
            font-size: 1rem;
            color: #1a1a2e;
        }
        .top-bar .user-id {
            font-size: 0.85rem;
            color: #555;
            margin-top: 2px;
        }
        .top-bar .user-email {
            font-size: 0.78rem;
            color: #888;
            margin-top: 2px;
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
                <li><a href="#manage-courses"><i class="fas fa-chalkboard-teacher"></i> Manage Courses</a></li>
                <li><a href="#course-management"><i class="fas fa-tasks"></i> Course Management</a></li>
                <li><a href="#quizzes"><i class="fas fa-pen-alt"></i> Quiz</a></li>
                <li><a href="#assignments"><i class="fas fa-file-upload"></i> Assignments</a></li>
                <li><a href="#announcements"><i class="fas fa-bullhorn"></i> Announcements</a></li>
            </ul>
            <a href="logout.php"><button class="logout-btn">Logout</button></a>
        </nav>

        <main class="content">
            <section id="manage-courses">
                <div class="course-card">
                    <h1>Manage Courses</h1>
                    <a href="add_course.html"><button>Add New Course</button></a>
                </div>
            </section>

            <section id="course-management">
                <div class="course-card">
                    <h1>Course Management</h1>
                    <a href="view_course.php"><button>View Courses</button></a>
                    <h1><br></h1>
                    <a href="edit_course.php?Course_ID"><button>Edit a Course</button></a>
                </div>
            </section>

            <section id="quizzes">
                <div class="course-card">
                    <h1>Quiz</h1>
                    <a href="create_quiz.php"><button>Create New Quiz</button></a>
                    <br><br>
                    <a href="view_instructor_quizzes.php"><button>View Quizzes</button></a>
                    <br><br>
                    <a href="publish_results.html"><button>Publish Quiz Results</button></a>
                </div>
            </section>

            <section id="assignments">
                <div class="course-card">
                    <h1>Assignments</h1>
                    <a href="create_assignment.php"><button>Create Assignment</button></a>
                    <br><br>
                    <a href="view_assignments.php"><button>View Assignments</button></a>
                    <br><br>
                    <a href="view_submissions.php"><button>View Student Submissions</button></a>
                </div>
            </section>

            <section id="announcements">
                <div class="course-card">
                    <h1>Announcements</h1>
                    <a href="create_announcement.php"><button>Post New Announcement</button></a>
                </div>
            </section>
        </main>
    </div>
</body>
</html>