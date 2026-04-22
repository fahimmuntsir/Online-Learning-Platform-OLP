<?php
session_start();
$conn = new mysqli("localhost", "root", "", "learning_platform");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$userID = $_SESSION['userID'] ?? '';

if (empty($userID)) {
    header("Location: login.html");
    exit;
}

// Get StudentID from userID
$stmt = $conn->prepare("SELECT StudentID FROM student WHERE UserID = ?");
$stmt->bind_param("s", $userID);
$stmt->execute();
$studentRow = $stmt->get_result()->fetch_assoc();

if (!$studentRow) {
    die("Student record not found. Please login as a student.");
}

$studentID = $studentRow['StudentID'];

// Add is_published column if it doesn't exist yet (safe to run)
$conn->query("ALTER TABLE quiz_result ADD COLUMN IF NOT EXISTS is_published TINYINT(1) NOT NULL DEFAULT 0");

// Fetch only PUBLISHED results for this student
$stmt2 = $conn->prepare("
    SELECT Course_ID, CourseName, Quiz_NO, Score, is_published
    FROM quiz_result
    WHERE Student_ID = ? AND is_published = 1
    ORDER BY Course_ID, Quiz_NO
");
$stmt2->bind_param("i", $studentID);
$stmt2->execute();
$results = $stmt2->get_result();

// Get user info for top bar
$stmt3 = $conn->prepare("SELECT * FROM userinfo WHERE UserID = ?");
$stmt3->bind_param("s", $userID);
$stmt3->execute();
$userData = $stmt3->get_result()->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Quiz Results - OLP</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        .top-bar {
            position: fixed; top: 0; right: 0;
            background: white; padding: 10px 24px;
            text-align: right;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            z-index: 999; min-width: 260px;
        }
        .top-bar .user-name { font-weight: bold; font-size: 1rem; color: #1a1a2e; }
        .top-bar .user-id   { font-size: 0.85rem; color: #555; margin-top: 2px; }
        .top-bar .user-email{ font-size: 0.78rem; color: #888; margin-top: 2px; }

        .results-wrapper {
            max-width: 800px;
            margin: 40px auto;
            padding: 30px 20px;
        }
        .results-wrapper h1 {
            font-size: 2rem;
            margin-bottom: 24px;
            color: #1a1a2e;
        }
        .result-table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 16px rgba(0,0,0,0.08);
        }
        .result-table thead {
            background: linear-gradient(135deg, #667eea, #000);
            color: white;
        }
        .result-table th, .result-table td {
            padding: 14px 18px;
            text-align: left;
            border-bottom: 1px solid #f0f0f0;
        }
        .result-table tbody tr:last-child td { border-bottom: none; }
        .result-table tbody tr:hover { background: #f8f9ff; }

        .score-badge {
            display: inline-block;
            padding: 4px 14px;
            border-radius: 20px;
            font-weight: bold;
            font-size: 0.95rem;
        }
        .score-high  { background: #d4edda; color: #155724; }
        .score-mid   { background: #fff3cd; color: #856404; }
        .score-low   { background: #f8d7da; color: #721c24; }

        .no-results {
            background: white;
            border-radius: 12px;
            padding: 40px;
            text-align: center;
            color: #888;
            box-shadow: 0 4px 16px rgba(0,0,0,0.08);
        }
        .no-results i { font-size: 3rem; color: #ccc; margin-bottom: 12px; display: block; }

        .back-link {
            display: inline-block;
            margin-top: 24px;
            padding: 12px 28px;
            background: linear-gradient(135deg, #667eea, #000);
            color: white;
            border-radius: 8px;
            text-decoration: none;
            font-weight: bold;
        }
        .back-link:hover { opacity: 0.9; }
    </style>
</head>
<body>
    <div class="top-bar">
        <div class="user-name"><?php echo htmlspecialchars(($userData['First_Name'] ?? '') . ' ' . ($userData['Last_Name'] ?? '')); ?></div>
        <div class="user-id">ID: <?php echo htmlspecialchars($userData['UserID'] ?? ''); ?></div>
        <div class="user-email"><?php echo htmlspecialchars($userData['Email'] ?? ''); ?></div>
    </div>

    <div class="results-wrapper">
        <h1><i class="fas fa-poll"></i> My Quiz Results</h1>

        <?php if ($results->num_rows > 0): ?>
        <table class="result-table">
            <thead>
                <tr>
                    <th>Course ID</th>
                    <th>Course Name</th>
                    <th>Quiz No</th>
                    <th>Score</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $results->fetch_assoc()): 
                    $score = $row['Score'];
                    $badgeClass = $score >= 7 ? 'score-high' : ($score >= 4 ? 'score-mid' : 'score-low');
                ?>
                <tr>
                    <td><?php echo htmlspecialchars($row['Course_ID']); ?></td>
                    <td><?php echo htmlspecialchars($row['CourseName']); ?></td>
                    <td>Quiz <?php echo htmlspecialchars($row['Quiz_NO']); ?></td>
                    <td><span class="score-badge <?php echo $badgeClass; ?>"><?php echo $score; ?></span></td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
        <?php else: ?>
        <div class="no-results">
            <i class="fas fa-inbox"></i>
            <h3>No Results Published Yet</h3>
            <p>Your instructor hasn't published any quiz results yet. Please check back later.</p>
        </div>
        <?php endif; ?>

        <a class="back-link" href="student_dashboard.php"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
    </div>
</body>
</html>
<?php $conn->close(); ?>
