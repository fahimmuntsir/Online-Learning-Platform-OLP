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

$course_id = $_GET['course_id'];
$quiz_no   = $_GET['quiz_no'];
$user_id   = $_SESSION['userID'];

// Check if already taken
$query = "SELECT 1 FROM quiz_result WHERE Course_ID = ? AND Quiz_NO = ? AND Student_ID = ?";
$stmt  = $conn->prepare($query);
$stmt->bind_param("sis", $course_id, $quiz_no, $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $_SESSION['message'] = "You have already taken this quiz. You are not eligible to take it again.";
    header("Location: take_quiz.php?course_id=$course_id&quiz_no=$quiz_no");
    exit();
}
$stmt->close();

// Fetch questions
$query = "SELECT Que_NO, Question, Choice1, Choice2, Choice3, Choice4, Correct_Ans
          FROM quiz_question
          WHERE Course_ID = ? AND Quiz_NO = ?
          ORDER BY Que_NO";
$stmt = $conn->prepare($query);
$stmt->bind_param("si", $course_id, $quiz_no);
$stmt->execute();
$questions = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Take Quiz</title>
    <link rel="stylesheet" href="style.css">
    <style>
        /* ── Quiz wrapper ── */
        #quiz-container {
            max-width: 680px;
            margin: 48px auto;
            padding: 0 20px;
            font-family: 'Segoe UI', sans-serif;
        }

        /* Question heading */
        #quiz-container h2 {
            font-size: 1.45rem;
            color: #1e1b4b;
            margin-bottom: 10px;
        }

        /* Question text */
        #quiz-container .question-text {
            font-size: 1.05rem;
            color: #333;
            margin-bottom: 22px;
            line-height: 1.55;
        }

        /* ── Option list ── */
        .options-list {
            list-style: none;
            padding: 0;
            margin: 0 0 24px;
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .option-item {
            display: flex;
            align-items: center;
            gap: 14px;
            background: #f8f7ff;
            border: 2px solid #e0defe;
            border-radius: 10px;
            padding: 13px 18px;
            cursor: pointer;
            transition: border-color 0.15s, background 0.15s;
        }

        .option-item:hover {
            border-color: #4f46e5;
            background: #ede9fe;
        }

        /* When its radio is checked */
        .option-item:has(input[type="radio"]:checked) {
            border-color: #4f46e5;
            background: #ede9fe;
        }

        /* The radio input itself — keep it in flow, next to label number */
        .option-item input[type="radio"] {
            width: 18px;
            height: 18px;
            accent-color: #4f46e5;
            flex-shrink: 0;
            margin: 0;
            cursor: pointer;
        }

        /* Option number badge */
        .option-num {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 28px;
            height: 28px;
            border-radius: 50%;
            background: #4f46e5;
            color: #fff;
            font-size: 0.82rem;
            font-weight: 700;
            flex-shrink: 0;
        }

        /* Option text */
        .option-text {
            font-size: 0.97rem;
            color: #333;
            line-height: 1.4;
        }

        /* Next button */
        #quiz-container .next-btn {
            background: linear-gradient(135deg, #4f46e5, #1e1b4b);
            color: #fff;
            border: none;
            padding: 12px 36px;
            border-radius: 9px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            letter-spacing: 0.3px;
        }
        #quiz-container .next-btn:hover { opacity: 0.88; }

        /* Progress bar */
        .progress-bar-wrap {
            background: #e5e7eb;
            border-radius: 9999px;
            height: 7px;
            margin-bottom: 28px;
            overflow: hidden;
        }
        .progress-bar-fill {
            height: 100%;
            background: linear-gradient(90deg, #4f46e5, #7c3aed);
            border-radius: 9999px;
            transition: width 0.35s ease;
        }

        /* Counter text */
        .q-counter {
            font-size: 0.85rem;
            color: #888;
            margin-bottom: 8px;
        }

        /* Already-taken message */
        .already-taken {
            max-width: 500px;
            margin: 80px auto;
            text-align: center;
            font-family: 'Segoe UI', sans-serif;
        }
        .already-taken .icon { font-size: 3rem; margin-bottom: 12px; }
        .already-taken h2 { color: #1e1b4b; margin-bottom: 8px; }
        .already-taken p  { color: #666; }
    </style>
</head>
<body>

<?php if (isset($_SESSION['message'])): ?>
<div class="already-taken">
    <div class="icon">&#9888;&#65039;</div>
    <h2>Already Taken</h2>
    <p><?php echo htmlspecialchars($_SESSION['message']); unset($_SESSION['message']); ?></p>
    <br>
    <a href="available_quizes.php"><button>Back to Quizzes</button></a>
</div>
<?php else: ?>

<div id="quiz-container"></div>

<script>
    let score           = 0;
    let currentQuestion = 0;
    const questions     = <?php echo json_encode($questions); ?>;
    const totalQ        = questions.length;

    function disableBackButton() {
        window.history.pushState(null, "", location.href);
        window.onpopstate = function () {
            document.getElementById('quiz-container').innerHTML = buildSubmitForm(0);
            document.getElementById('auto-submit-form').submit();
        };
    }

    function buildSubmitForm(s) {
        return `
            <h2>Submitting Quiz...</h2>
            <form id="auto-submit-form" method="POST" action="submit_quiz.php">
                <input type="hidden" name="score"     value="${s}">
                <input type="hidden" name="course_id" value="<?php echo htmlspecialchars($course_id); ?>">
                <input type="hidden" name="quiz_no"   value="<?php echo htmlspecialchars($quiz_no); ?>">
            </form>`;
    }

    function loadQuestion() {
        if (currentQuestion >= totalQ) {
            document.getElementById('quiz-container').innerHTML = buildSubmitForm(score);
            document.getElementById('auto-submit-form').submit();
            return;
        }

        const q        = questions[currentQuestion];
        const progress = Math.round((currentQuestion / totalQ) * 100);

        const choices = [
            { val: 'choice1', label: '1', text: q.Choice1 },
            { val: 'choice2', label: '2', text: q.Choice2 },
            { val: 'choice3', label: '3', text: q.Choice3 },
            { val: 'choice4', label: '4', text: q.Choice4 },
        ];

        const optionsHTML = choices.map(c => `
            <li class="option-item" onclick="selectOption('${c.val}')">
                <input type="radio" name="answer" value="${c.val}" id="opt_${c.val}">
                <span class="option-num">${c.label}</span>
                <span class="option-text">${c.text}</span>
            </li>
        `).join('');

        document.getElementById('quiz-container').innerHTML = `
            <div class="q-counter">Question ${currentQuestion + 1} of ${totalQ}</div>
            <div class="progress-bar-wrap">
                <div class="progress-bar-fill" style="width:${progress}%"></div>
            </div>
            <h2>Question ${currentQuestion + 1}</h2>
            <p class="question-text">${q.Question}</p>
            <form onsubmit="return checkAnswer(event)">
                <ul class="options-list">${optionsHTML}</ul>
                <button type="submit" class="next-btn">
                    ${currentQuestion + 1 < totalQ ? 'Next &rarr;' : 'Submit Quiz'}
                </button>
            </form>
        `;
    }

    function selectOption(val) {
        // Click the radio when the whole card is clicked
        const radio = document.getElementById('opt_' + val);
        if (radio) radio.checked = true;
    }

    function checkAnswer(event) {
        event.preventDefault();
        const selected = document.querySelector('input[name="answer"]:checked');
        if (!selected) {
            alert('Please select an answer before continuing.');
            return;
        }
        if (selected.value === questions[currentQuestion].Correct_Ans) {
            score++;
        }
        currentQuestion++;
        loadQuestion();
    }

    document.addEventListener('DOMContentLoaded', () => {
        disableBackButton();
        loadQuestion();
    });
</script>

<?php endif; ?>
</body>
</html>
