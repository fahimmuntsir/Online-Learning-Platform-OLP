<?php
session_start();

if (!isset($_SESSION['userID'])) {
    header("Location: login.html");
    exit();
}

$userID = $_SESSION['userID'];
$conn = new mysqli("localhost", "root", "", "learning_platform");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch StudentID
$studentQuery = "SELECT StudentID FROM Student WHERE UserID = ?";
$stmt = $conn->prepare($studentQuery);
$stmt->bind_param("s", $userID);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $student = $result->fetch_assoc();
    $studentID = $student['StudentID'];
} else {
    echo "Error: Student not found.";
    exit();
}

// Handle enroll action
$popupType = ""; // "success", "already"
if (isset($_GET['enroll']) && isset($_GET['course_id'])) {
    $courseID = $_GET['course_id'];

    // ✅ আগে enrolled কিনা check করো
    $checkEnrollmentSQL = "SELECT * FROM Enrollment WHERE StudentID = ? AND course_ID = ?";
    $checkStmt = $conn->prepare($checkEnrollmentSQL);
    $checkStmt->bind_param("is", $studentID, $courseID);
    $checkStmt->execute();
    $enrollmentResult = $checkStmt->get_result();

    if ($enrollmentResult->num_rows > 0) {
        // ইতিমধ্যে enrolled
        $popupType = "already";
    } else {
    
        $callStmt = $conn->prepare("CALL EnrollStudent(?, ?)");
        $callStmt->bind_param("is", $studentID, $courseID);
        if ($callStmt->execute()) {
            $popupType = "success";
        }
        $callStmt->close();
    }
    $checkStmt->close();
}

// Fetch all courses — JOIN দিয়ে instructor name আনছি
$sql = "SELECT c.Course_ID, c.CourseName, c.Description, c.Start_Date, c.End_Date,
               u.First_Name, u.Last_Name
        FROM Course c
        LEFT JOIN userinfo u ON c.UserID = u.UserID";
$result = $conn->query($sql);
$courses = $result->num_rows > 0 ? $result->fetch_all(MYSQLI_ASSOC) : [];

// Fetch enrolled course IDs for this student
$enrolledCourses = [];
$enrolledSQL = "SELECT course_ID FROM Enrollment WHERE StudentID = ?";
$enrolledStmt = $conn->prepare($enrolledSQL);
$enrolledStmt->bind_param("i", $studentID);
$enrolledStmt->execute();
$enrolledResult = $enrolledStmt->get_result();
while ($row = $enrolledResult->fetch_assoc()) {
    $enrolledCourses[] = $row['course_ID'];
}
$enrolledStmt->close();

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Browse Courses - OLP</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        body {
            background: #f0f2f5;
            font-family: 'Segoe UI', sans-serif;
        }

        .browse-courses-container {
            max-width: 960px;
            margin: 40px auto;
            padding: 0 20px;
            text-align: center;
        }

        h1 {
            font-size: 2rem;
            color: #1a1a2e;
            margin-bottom: 30px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
        }

        thead tr {
            background: linear-gradient(135deg, #3b3b98, #1a1a2e);
            color: white;
        }

        th {
            padding: 14px 12px;
            font-size: 0.9rem;
            font-weight: 600;
            letter-spacing: 0.3px;
        }

        td {
            padding: 14px 12px;
            border-bottom: 1px solid #f0f0f0;
            font-size: 0.88rem;
            color: #333;
            vertical-align: middle;
        }

        tbody tr:last-child td {
            border-bottom: none;
        }

        tbody tr:hover {
            background: #fafafa;
        }

        .status-badge {
            display: inline-block;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }

        .status-enrolled {
            background: #e8f5e9;
            color: #2e7d32;
            border: 1px solid #a5d6a7;
        }

        .status-not-enrolled {
            background: #fff3e0;
            color: #e65100;
            border: 1px solid #ffcc80;
        }

        .enroll-btn {
            background: linear-gradient(135deg, #3b3b98, #1a1a2e);
            color: white;
            border: none;
            padding: 8px 18px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 0.85rem;
            font-weight: 600;
            transition: opacity 0.2s;
        }

        .enroll-btn:hover {
            opacity: 0.85;
        }

        .enrolled-btn {
            background: #e0e0e0;
            color: #888;
            border: none;
            padding: 8px 18px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 0.85rem;
            font-weight: 600;
        }

        .back-btn {
            display: inline-block;
            margin-top: 28px;
            padding: 13px 0;
            width: 100%;
            background: linear-gradient(135deg, #3b3b98, #1a1a2e);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            transition: opacity 0.2s;
        }

        .back-btn:hover {
            opacity: 0.85;
        }

        .popup-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.45);
            z-index: 9999;
            justify-content: center;
            align-items: center;
        }

        .popup-overlay.show {
            display: flex;
        }

        .popup-box {
            background: white;
            border-radius: 18px;
            padding: 40px 36px;
            text-align: center;
            max-width: 380px;
            width: 90%;
            box-shadow: 0 20px 60px rgba(0,0,0,0.2);
            animation: popIn 0.35s cubic-bezier(0.34, 1.56, 0.64, 1);
        }

        @keyframes popIn {
            from { transform: scale(0.7); opacity: 0; }
            to   { transform: scale(1);   opacity: 1; }
        }

        .popup-icon {
            width: 70px;
            height: 70px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 18px;
            font-size: 2rem;
        }

        .popup-icon.success {
            background: #e8f5e9;
            color: #2e7d32;
        }

        .popup-icon.already {
            background: #fff3e0;
            color: #e65100;
        }

        .popup-box h2 {
            font-size: 1.3rem;
            color: #1a1a2e;
            margin-bottom: 8px;
        }

        .popup-box p {
            font-size: 0.92rem;
            color: #666;
            margin-bottom: 24px;
        }

        .popup-close-btn {
            background: linear-gradient(135deg, #3b3b98, #1a1a2e);
            color: white;
            border: none;
            padding: 11px 32px;
            border-radius: 8px;
            font-size: 0.95rem;
            font-weight: 600;
            cursor: pointer;
            transition: opacity 0.2s;
        }

        .popup-close-btn:hover {
            opacity: 0.85;
        }
    </style>
</head>
<body>

<!-- Success Popup -->
<div class="popup-overlay <?php echo $popupType === 'success' ? 'show' : ''; ?>" id="successPopup">
    <div class="popup-box">
        <div class="popup-icon success">
            <i class="fas fa-check-circle"></i>
        </div>
        <h2>Enrollment Successful!</h2>
        <p>You have successfully enrolled in this course. Good luck with your learning journey!</p>
        <button class="popup-close-btn" onclick="closePopup('successPopup')">Continue</button>
    </div>
</div>

<!-- Already Enrolled Popup -->
<div class="popup-overlay <?php echo $popupType === 'already' ? 'show' : ''; ?>" id="alreadyPopup">
    <div class="popup-box">
        <div class="popup-icon already">
            <i class="fas fa-info-circle"></i>
        </div>
        <h2>Already Enrolled</h2>
        <p>You are already enrolled in this course. Check your courses from the dashboard.</p>
        <button class="popup-close-btn" onclick="closePopup('alreadyPopup')">OK</button>
    </div>
</div>

<div class="browse-courses-container">
    <h1>Available Courses</h1>

    <?php if (count($courses) > 0): ?>
        <table>
            <thead>
                <tr>
                    <th>Course ID</th>
                    <th>Course Name</th>
                    <th>Instructor</th>
                    <th>Description</th>
                    <th>Start Date</th>
                    <th>End Date</th>
                    <th>Status</th>
                    <th>Enroll</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($courses as $course): ?>
                    <?php $isEnrolled = in_array($course['Course_ID'], $enrolledCourses); ?>
                    <tr>
                        <td><?php echo htmlspecialchars($course['Course_ID']); ?></td>
                        <td><?php echo htmlspecialchars($course['CourseName']); ?></td>
                        <td><?php echo htmlspecialchars($course['First_Name'] . ' ' . $course['Last_Name']); ?></td>
                        <td><?php echo htmlspecialchars($course['Description']); ?></td>
                        <td><?php echo htmlspecialchars($course['Start_Date']); ?></td>
                        <td><?php echo htmlspecialchars($course['End_Date']); ?></td>
                        <td>
                            <?php if ($isEnrolled): ?>
                                <span class="status-badge status-enrolled">
                                    <i class="fas fa-check"></i> Already Enrolled
                                </span>
                            <?php else: ?>
                                <span class="status-badge status-not-enrolled">
                                    Not Enrolled
                                </span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <a href="browse_courses.php?enroll=true&course_id=<?php echo $course['Course_ID']; ?>">
                                <?php if ($isEnrolled): ?>
                                    <button class="enrolled-btn">Enrolled</button>
                                <?php else: ?>
                                    <button class="enroll-btn">Enroll</button>
                                <?php endif; ?>
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p>No courses available at the moment.</p>
    <?php endif; ?>

    <a href="student_dashboard.php" class="back-btn">Back to Dashboard</a>
</div>

<script>
    function closePopup(id) {
        document.getElementById(id).classList.remove('show');
    }

    document.querySelectorAll('.popup-overlay').forEach(overlay => {
        overlay.addEventListener('click', function(e) {
            if (e.target === this) {
                this.classList.remove('show');
            }
        });
    });
</script>

</body>
</html>