<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Quiz - OLP</title>
    <link rel="stylesheet" href="style.css">
    <script>
        let questionCount = 1;

        function addQuestion() {
            questionCount++;
            const questionSection = document.createElement('div');
            questionSection.className = 'question-section';
            questionSection.innerHTML = `
                <h2>Question ${questionCount}</h2>
                <label for="question${questionCount}">Question:</label>
                <input type="text" id="question${questionCount}" name="questions[${questionCount}][text]" placeholder="Enter the question" required>
                
                <label>Answer Choices:</label>
                <input type="text" name="questions[${questionCount}][choice1]" placeholder="Choice 1" required>
                <input type="text" name="questions[${questionCount}][choice2]" placeholder="Choice 2" required>
                <input type="text" name="questions[${questionCount}][choice3]" placeholder="Choice 3" required>
                <input type="text" name="questions[${questionCount}][choice4]" placeholder="Choice 4" required>
                
                <label for="correct-answer${questionCount}">Correct Answer:</label>
                <select id="correct-answer${questionCount}" name="questions[${questionCount}][correct]" required>
                    <option value="">Select the correct answer</option>
                    <option value="choice1">Choice 1</option>
                    <option value="choice2">Choice 2</option>
                    <option value="choice3">Choice 3</option>
                    <option value="choice4">Choice 4</option>
                </select>
            `;
            document.getElementById('questions-container').appendChild(questionSection);
        }
    </script>
</head>
<body>
    <div class="quiz-container">
        <h1>Create a New Quiz</h1>

        <?php if (isset($_GET['error']) && $_GET['error'] === 'duplicate'): ?>
        <div style="background:#fff0f0;border:1.5px solid #e53935;border-radius:8px;padding:14px 18px;margin-bottom:20px;color:#c62828;font-size:0.97rem;">
            ⚠️ <strong>Quiz <?= htmlspecialchars($_GET['quiz_no'] ?? '') ?></strong> for course <strong><?= htmlspecialchars($_GET['course'] ?? '') ?></strong> already exists!
            <br>Please use a different Quiz Number.
        </div>
        <?php endif; ?>
        
        <form class="quiz-form" method="POST" action="submitcreate_quiz.php">
            <label for="course-select">Select Course:</label>
            <select id="course-select" name="Course_ID" required>
                <option value="">Choose a Course</option>
                <?php
                session_start();
                $conn = new mysqli("localhost", "root", "", "learning_platform");
                $user_id = $_SESSION['userID'];
                $sql = "SELECT Course_ID FROM Course WHERE UserID = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("s", $user_id);
                $stmt->execute();
                $result = $stmt->get_result();
                while ($row = $result->fetch_assoc()) {
                    echo "<option value='" . htmlspecialchars($row['Course_ID']) . "'>" . htmlspecialchars($row['Course_ID']) . "</option>";
                }
                $stmt->close();
                $conn->close();
                ?>
            </select>

            <label for="quiz-num">Quiz Number:</label>
            <input type="number" id="quiz-num" name="Quiz_NO" placeholder="Enter the quiz number" required>

            <label for="quiz-date">Quiz Date:</label>
            <input type="date" id="quiz-date" name="Quiz_Date" required>

            <label for="quiz-time">Quiz Time (students can take quiz from this time):</label>
            <input type="time" id="quiz-time" name="Quiz_Time" required>

            <label for="quiz-description">Quiz Description:</label>
            <textarea id="quiz-description" name="Description_Quiz" placeholder="Enter a brief description of the quiz" rows="4" required></textarea>

            <div id="questions-container">
                <div class="question-section">
                    <h2>Question 1</h2>
                    <label for="question1">Question:</label>
                    <input type="text" id="question1" name="questions[1][text]" placeholder="Enter the question" required>

                    <label>Answer Choices:</label>
                    <input type="text" name="questions[1][choice1]" placeholder="Choice 1" required>
                    <input type="text" name="questions[1][choice2]" placeholder="Choice 2" required>
                    <input type="text" name="questions[1][choice3]" placeholder="Choice 3" required>
                    <input type="text" name="questions[1][choice4]" placeholder="Choice 4" required>

                    <label for="correct-answer1">Correct Answer:</label>
                    <select id="correct-answer1" name="questions[1][correct]" required>
                        <option value="">Select the correct answer</option>
                        <option value="choice1">Choice 1</option>
                        <option value="choice2">Choice 2</option>
                        <option value="choice3">Choice 3</option>
                        <option value="choice4">Choice 4</option>
                    </select>
                </div>
            </div>

            <button type="button" onclick="addQuestion()">Add Another Question</button>
            <button type="submit">Create Quiz</button>
        </form>



        <a href="instructor_dashboard.html"><button class="back-btn">Back to Dashboard</button></a>
    </div>
</body>
</html>