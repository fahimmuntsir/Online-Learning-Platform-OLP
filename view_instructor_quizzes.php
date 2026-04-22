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

// Add date/time columns if not exist
$conn->query("ALTER TABLE quiz_description ADD COLUMN IF NOT EXISTS Quiz_Date DATE DEFAULT NULL");
$conn->query("ALTER TABLE quiz_description ADD COLUMN IF NOT EXISTS Quiz_Time TIME DEFAULT NULL");

$userID = $_SESSION['userID'];

// Fetch all quizzes for courses taught by this instructor
$query = "
    SELECT 
        qd.Course_ID,
        c.CourseName,
        qd.Quiz_NO,
        qd.Description_Quiz,
        qd.Quiz_Date,
        qd.Quiz_Time,
        COUNT(qq.Que_NO) AS Question_Count
    FROM quiz_description qd
    INNER JOIN Course c ON qd.Course_ID = c.Course_ID
    LEFT JOIN quiz_question qq ON qd.Course_ID = qq.Course_ID AND qd.Quiz_NO = qq.Quiz_NO
    WHERE c.UserID = ?
    GROUP BY qd.Course_ID, qd.Quiz_NO
    ORDER BY qd.Course_ID, qd.Quiz_NO
";
$stmt = $conn->prepare($query);
$stmt->bind_param("s", $userID);
$stmt->execute();
$quizzes = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Fetch questions for a selected quiz (if requested)
$selectedQuiz = null;
$questions = [];
if (isset($_GET['view_course']) && isset($_GET['view_quiz'])) {
    $vc = $_GET['view_course'];
    $vq = (int)$_GET['view_quiz'];

    $qStmt = $conn->prepare("SELECT Que_NO, Question, Choice1, Choice2, Choice3, Choice4, Correct_Ans FROM quiz_question WHERE Course_ID = ? AND Quiz_NO = ? ORDER BY Que_NO");
    $qStmt->bind_param("si", $vc, $vq);
    $qStmt->execute();
    $questions = $qStmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $qStmt->close();

    foreach ($quizzes as $q) {
        if ($q['Course_ID'] === $vc && $q['Quiz_NO'] == $vq) {
            $selectedQuiz = $q;
            break;
        }
    }
}

$conn->close();

function formatAnswer($key) {
    $map = ['choice1'=>'Choice 1','choice2'=>'Choice 2','choice3'=>'Choice 3','choice4'=>'Choice 4'];
    return $map[$key] ?? $key;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Quizzes - OLP</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .vq-wrapper {
            max-width: 960px;
            margin: 40px auto;
            padding: 0 20px;
            font-family: 'Segoe UI', sans-serif;
        }
        .vq-wrapper h1 {
            font-size: 1.8rem;
            margin-bottom: 24px;
            color: #2d2d2d;
        }
        .vq-table-wrap {
            overflow-x: auto;
            border-radius: 10px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.08);
        }
        .vq-table {
            width: 100%;
            border-collapse: collapse;
            background: #fff;
        }
        .vq-table thead tr {
            background: linear-gradient(135deg, #4f46e5, #1e1b4b);
            color: #fff;
        }
        .vq-table th {
            padding: 13px 16px;
            text-align: left;
            font-weight: 600;
            font-size: 0.9rem;
            letter-spacing: 0.3px;
        }
        .vq-table td {
            padding: 11px 16px;
            border-bottom: 1px solid #f0f0f0;
            font-size: 0.92rem;
            color: #333;
        }
        .vq-table tbody tr:hover {
            background: #f5f4ff;
        }
        .vq-table tbody tr:last-child td {
            border-bottom: none;
        }
        .btn-view {
            background: linear-gradient(135deg, #4f46e5, #1e1b4b);
            color: #fff;
            border: none;
            padding: 6px 16px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 0.85rem;
            text-decoration: none;
            display: inline-block;
        }
        .btn-view:hover { opacity: 0.88; }

        /* Question detail modal area */
        .quiz-detail-box {
            margin-top: 36px;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 2px 16px rgba(0,0,0,0.10);
            padding: 28px 32px;
        }
        .quiz-detail-box h2 {
            font-size: 1.3rem;
            color: #1e1b4b;
            margin-bottom: 6px;
        }
        .quiz-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 14px;
            margin-bottom: 20px;
        }
        .quiz-meta span {
            background: #ede9fe;
            color: #4f46e5;
            padding: 5px 14px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 500;
        }
        .question-block {
            background: #fafafa;
            border: 1px solid #e8e8e8;
            border-radius: 8px;
            padding: 16px 20px;
            margin-bottom: 14px;
        }
        .question-block h3 {
            font-size: 1rem;
            margin-bottom: 8px;
            color: #222;
        }
        .question-block .choices {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 6px 20px;
        }
        .question-block .choice-item {
            font-size: 0.9rem;
            color: #555;
            padding: 4px 0;
        }
        .choice-item.correct {
            color: #16a34a;
            font-weight: 600;
        }
        .choice-item.correct::after {
            content: ' ✓';
        }
        .btn-back {
            display: inline-block;
            margin-top: 20px;
            background: #e5e7eb;
            color: #333;
            border: none;
            padding: 8px 20px;
            border-radius: 7px;
            cursor: pointer;
            font-size: 0.9rem;
            text-decoration: none;
        }
        .btn-back:hover { background: #d1d5db; }
        .no-quiz { color: #888; margin-top: 20px; font-size: 0.95rem; }
        .badge-date {
            font-size: 0.82rem;
            color: #7c3aed;
        }
        .badge-nodate {
            font-size: 0.82rem;
            color: #aaa;
            font-style: italic;
        }
    </style>
</head>
<body>
<div class="vq-wrapper">
    <h1>📋 All Quizzes</h1>

    <?php if (empty($quizzes)): ?>
        <p class="no-quiz">No quizzes found for your courses.</p>
    <?php else: ?>
    <div class="vq-table-wrap">
        <table class="vq-table">
            <thead>
                <tr>
                    <th>Course ID</th>
                    <th>Course Name</th>
                    <th>Quiz No</th>
                    <th>Questions</th>
                    <th>Date</th>
                    <th>Time</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($quizzes as $q): ?>
                <tr>
                    <td><?php echo htmlspecialchars($q['Course_ID']); ?></td>
                    <td><?php echo htmlspecialchars($q['CourseName']); ?></td>
                    <td>Quiz <?php echo htmlspecialchars($q['Quiz_NO']); ?></td>
                    <td><?php echo (int)$q['Question_Count']; ?> questions</td>
                    <td>
                        <?php if ($q['Quiz_Date']): ?>
                            <span class="badge-date"><?php echo date('d M Y', strtotime($q['Quiz_Date'])); ?></span>
                        <?php else: ?>
                            <span class="badge-nodate">Not set</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($q['Quiz_Time']): ?>
                            <span class="badge-date"><?php echo date('h:i A', strtotime($q['Quiz_Time'])); ?></span>
                        <?php else: ?>
                            <span class="badge-nodate">Not set</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <a class="btn-view" href="view_instructor_quizzes.php?view_course=<?php echo urlencode($q['Course_ID']); ?>&view_quiz=<?php echo $q['Quiz_NO']; ?>">View Questions</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>

    <?php if ($selectedQuiz && !empty($questions)): ?>
    <div class="quiz-detail-box">
        <h2><?php echo htmlspecialchars($selectedQuiz['CourseName']); ?> — Quiz <?php echo htmlspecialchars($selectedQuiz['Quiz_NO']); ?></h2>
        <div class="quiz-meta">
            <span>Course ID: <?php echo htmlspecialchars($selectedQuiz['Course_ID']); ?></span>
            <span>Quiz No: <?php echo htmlspecialchars($selectedQuiz['Quiz_NO']); ?></span>
            <?php if ($selectedQuiz['Quiz_Date']): ?>
            <span>📅 <?php echo date('d M Y', strtotime($selectedQuiz['Quiz_Date'])); ?></span>
            <?php endif; ?>
            <?php if ($selectedQuiz['Quiz_Time']): ?>
            <span>⏰ <?php echo date('h:i A', strtotime($selectedQuiz['Quiz_Time'])); ?></span>
            <?php endif; ?>
            <span><?php echo count($questions); ?> Questions</span>
        </div>

        <?php foreach ($questions as $i => $q): ?>
        <div class="question-block">
            <h3>Q<?php echo $i+1; ?>. <?php echo htmlspecialchars($q['Question']); ?></h3>
            <div class="choices">
                <div class="choice-item <?php echo ($q['Correct_Ans']==='choice1')?'correct':''; ?>">
                    A. <?php echo htmlspecialchars($q['Choice1']); ?>
                </div>
                <div class="choice-item <?php echo ($q['Correct_Ans']==='choice2')?'correct':''; ?>">
                    B. <?php echo htmlspecialchars($q['Choice2']); ?>
                </div>
                <div class="choice-item <?php echo ($q['Correct_Ans']==='choice3')?'correct':''; ?>">
                    C. <?php echo htmlspecialchars($q['Choice3']); ?>
                </div>
                <div class="choice-item <?php echo ($q['Correct_Ans']==='choice4')?'correct':''; ?>">
                    D. <?php echo htmlspecialchars($q['Choice4']); ?>
                </div>
            </div>
        </div>
        <?php endforeach; ?>

        <a class="btn-back" href="view_instructor_quizzes.php">← Back to All Quizzes</a>
    </div>
    <?php elseif ($selectedQuiz && empty($questions)): ?>
    <div class="quiz-detail-box">
        <p>No questions found for this quiz.</p>
        <a class="btn-back" href="view_instructor_quizzes.php">← Back</a>
    </div>
    <?php endif; ?>

    <br>
    <a href="instructor_dashboard.php"><button class="btn-back" style="margin-top:10px;">← Back to Dashboard</button></a>
</div>
</body>
</html>
