<?php
session_start();
if (!isset($_SESSION['userID'])) {
    header("Location: login.html");
    exit();
}

$conn = new mysqli("localhost", "root", "", "learning_platform");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Ensure columns exist
$conn->query("ALTER TABLE quiz_description ADD COLUMN IF NOT EXISTS Quiz_Date DATE DEFAULT NULL");
$conn->query("ALTER TABLE quiz_description ADD COLUMN IF NOT EXISTS Quiz_Time TIME DEFAULT NULL");

$userID = $_SESSION['userID'];
$studentQuery = "SELECT StudentID FROM Student WHERE UserID = ?";
$stmt = $conn->prepare($studentQuery);
$stmt->bind_param("s", $userID);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $student = $result->fetch_assoc();
    $studentID = $student['StudentID'];

    $query = "
        SELECT qd.Course_ID, qd.Quiz_NO, qd.Description_Quiz,
               qd.Quiz_Date, qd.Quiz_Time,
               c.CourseName
        FROM quiz_description qd
        INNER JOIN Enrollment e ON qd.Course_ID = e.course_ID
        INNER JOIN Course c ON qd.Course_ID = c.Course_ID
        WHERE e.StudentID = ?
        ORDER BY qd.Course_ID, qd.Quiz_NO";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $studentID);
    $stmt->execute();
    $quizzes = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
} else {
    $quizzes = [];
}

$stmt->close();
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Available Quizzes</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .popup-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.45);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }
        .popup-overlay.active { display: flex; }
        .popup-box {
            background: #fff;
            border-radius: 14px;
            padding: 36px 32px 28px;
            max-width: 420px;
            width: 90%;
            text-align: center;
            box-shadow: 0 8px 40px rgba(0,0,0,0.18);
            animation: popIn 0.22s ease;
        }
        @keyframes popIn {
            from { transform: scale(0.85); opacity: 0; }
            to   { transform: scale(1);    opacity: 1; }
        }
        .popup-icon { font-size: 2.8rem; margin-bottom: 10px; }
        .popup-box h3 { font-size: 1.2rem; color: #1e1b4b; margin-bottom: 8px; }
        .popup-box p { font-size: 0.95rem; color: #555; line-height: 1.55; margin-bottom: 22px; }
        .popup-box p strong { color: #4f46e5; }
        .popup-close-btn {
            background: linear-gradient(135deg, #4f46e5, #1e1b4b);
            color: #fff;
            border: none;
            padding: 10px 28px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 0.95rem;
            font-weight: 500;
        }
        .popup-close-btn:hover { opacity: 0.88; }
        .quiz-schedule { display: inline-flex; align-items: center; gap: 8px; margin: 6px 0 10px; flex-wrap: wrap; }
        .badge { font-size: 0.82rem; padding: 4px 12px; border-radius: 20px; font-weight: 500; }
        .badge-purple { background: #ede9fe; color: #4f46e5; }
        .badge-gray   { background: #f3f4f6; color: #888; font-style: italic; }
        .badge-locked { background: #fef2f2; color: #dc2626; }
        .badge-open   { background: #f0fdf4; color: #16a34a; }
    </style>
</head>
<body>
<div class="dashboard-container">
    <main class="content">
        <section id="quiz-list">
            <h1>Available Quizzes</h1>
            <?php if (empty($quizzes)): ?>
                <p>No quizzes available.</p>
            <?php else: ?>
                <?php
                $now     = new DateTime();
                $today   = $now->format('Y-m-d');
                $nowTime = $now->format('H:i:s');

                foreach ($quizzes as $quiz):
                    $hasDate = !empty($quiz['Quiz_Date']);
                    $hasTime = !empty($quiz['Quiz_Time']);
                    $isLocked = false;
                    $lockReason = '';

                    if ($hasDate && $hasTime) {
                        if ($quiz['Quiz_Date'] > $today) {
                            $isLocked = true;
                            $lockReason = date('d M Y', strtotime($quiz['Quiz_Date'])) . ' at ' . date('h:i A', strtotime($quiz['Quiz_Time']));
                        } elseif ($quiz['Quiz_Date'] === $today && $quiz['Quiz_Time'] > $nowTime) {
                            $isLocked = true;
                            $lockReason = 'today at ' . date('h:i A', strtotime($quiz['Quiz_Time']));
                        }
                    } elseif ($hasDate) {
                        if ($quiz['Quiz_Date'] > $today) {
                            $isLocked = true;
                            $lockReason = date('d M Y', strtotime($quiz['Quiz_Date']));
                        }
                    }

                    $scheduleLabel = '';
                    if ($hasDate && $hasTime) {
                        $scheduleLabel = date('d M Y', strtotime($quiz['Quiz_Date'])) . ' · ' . date('h:i A', strtotime($quiz['Quiz_Time']));
                    } elseif ($hasDate) {
                        $scheduleLabel = date('d M Y', strtotime($quiz['Quiz_Date']));
                    }
                ?>
                <div class="quiz-card">
                    <h2><?php echo htmlspecialchars($quiz['CourseName']); ?> — Quiz <?php echo htmlspecialchars($quiz['Quiz_NO']); ?></h2>
                    <p><?php echo htmlspecialchars($quiz['Description_Quiz']); ?></p>

                    <div class="quiz-schedule">
                        <?php if ($scheduleLabel): ?>
                            <span class="badge badge-purple">&#128197; <?php echo htmlspecialchars($scheduleLabel); ?></span>
                        <?php else: ?>
                            <span class="badge badge-gray">No schedule set</span>
                        <?php endif; ?>
                        <?php if ($isLocked): ?>
                            <span class="badge badge-locked">&#128274; Locked</span>
                        <?php else: ?>
                            <span class="badge badge-open">&#9989; Open</span>
                        <?php endif; ?>
                    </div>

                    <?php if ($isLocked): ?>
                        <button onclick="showLockedPopup('<?php echo htmlspecialchars(addslashes($quiz['CourseName'])); ?>', 'Quiz <?php echo $quiz['Quiz_NO']; ?>', '<?php echo htmlspecialchars(addslashes($lockReason)); ?>')">Take Quiz</button>
                    <?php else: ?>
                        <a href="take_quiz.php?course_id=<?php echo urlencode($quiz['Course_ID']); ?>&quiz_no=<?php echo $quiz['Quiz_NO']; ?>">
                            <button>Take Quiz</button>
                        </a>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </section>
        <a href="student_dashboard.php"><button class="back-btn">Back to Dashboard</button></a>
    </main>
</div>

<!-- Locked Popup -->
<div class="popup-overlay" id="lockedPopup">
    <div class="popup-box">
        <div class="popup-icon">&#128274;</div>
        <h3 id="popupTitle">Quiz Not Available Yet</h3>
        <p id="popupMsg">This quiz will be available soon.</p>
        <button class="popup-close-btn" onclick="closePopup()">Got it</button>
    </div>
</div>

<script>
function showLockedPopup(courseName, quizLabel, lockReason) {
    document.getElementById('popupTitle').textContent = courseName + ' \u2014 ' + quizLabel;
    document.getElementById('popupMsg').innerHTML =
        'This quiz will be available on <strong>' + lockReason + '</strong>.<br>'
        + 'You can take the quiz from that date and time onwards.';
    document.getElementById('lockedPopup').classList.add('active');
}
function closePopup() {
    document.getElementById('lockedPopup').classList.remove('active');
}
document.getElementById('lockedPopup').addEventListener('click', function(e) {
    if (e.target === this) closePopup();
});
</script>
</body>
</html>
