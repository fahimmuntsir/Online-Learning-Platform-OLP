<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Instructor') {
    header("Location: login.html");
    exit();
}
$conn = new mysqli("localhost", "root", "", "learning_platform");
$user_id = $_SESSION['userID'];
$sql = "SELECT Course_ID, CourseName FROM course WHERE UserID = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $user_id);
$stmt->execute();
$courses = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Assignment - OLP</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        body { background: #f0f2f5; display: block; }
        .assignment-create-wrapper { max-width: 680px; margin: 48px auto; padding: 0 20px; }
        .card {
            background: #fff; border-radius: 14px;
            box-shadow: 0 2px 16px rgba(0,0,0,0.10); padding: 38px 40px;
        }
        .card h1 { font-size: 1.8rem; font-weight: bold; color: #1a1a2e; margin-bottom: 28px; text-align: center; }
        .form-group { margin-bottom: 22px; }
        .form-group label { display: block; font-weight: bold; color: #333; margin-bottom: 8px; }
        .form-group select, .form-group input, .form-group textarea {
            width: 100%; padding: 11px 14px; border: 1.5px solid #ddd;
            border-radius: 7px; font-size: 1rem; color: #333; background: #fafafa;
            transition: border-color 0.2s;
        }
        .form-group select:focus, .form-group input:focus, .form-group textarea:focus {
            border-color: #6a11cb; outline: none; background: #fff;
        }
        .form-group textarea { resize: vertical; min-height: 90px; }
        .datetime-row { display: flex; gap: 16px; }
        .datetime-row .form-group { flex: 1; }
        .submit-btn {
            width: 100%; padding: 12px; margin-top: 6px;
            background: linear-gradient(135deg, #5c49c6, #000000);
            color: #fff; border: none; border-radius: 7px;
            font-size: 1.05rem; cursor: pointer; transition: background 0.3s;
        }
        .submit-btn:hover { background: linear-gradient(135deg, #ff5722, #ff5722); }
        .back-link { display: inline-block; margin-bottom: 22px; color: #6a11cb; font-weight: bold; text-decoration: none; }
        .back-link:hover { text-decoration: underline; }
        .success-msg { background: #e8f5e9; color: #2e7d32; border-radius: 8px; padding: 12px 18px; margin-bottom: 18px; font-weight: bold; }
        .error-msg { background: #ffebee; color: #c62828; border-radius: 8px; padding: 12px 18px; margin-bottom: 18px; font-weight: bold; }
        .upload-box {
            border: 2px dashed #b0a0e0; border-radius: 10px;
            padding: 18px 16px; background: #faf8ff; margin-top: 6px;
        }
        .upload-box input[type="file"] { width: 100%; }
        .file-note { font-size: 0.82rem; color: #888; margin-top: 6px; }
    </style>
</head>
<body>
<div class="assignment-create-wrapper">
    <a href="instructor_dashboard.php" class="back-link"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
    <div class="card">
        <h1>Create Assignment</h1>

        <?php if (isset($_GET['success'])): ?>
            <div class="success-msg">✅ Assignment created successfully!</div>
        <?php elseif (isset($_GET['error'])): ?>
            <div class="error-msg">❌ Failed to create assignment. Please try again.</div>
        <?php endif; ?>

        <form method="POST" action="save_assignment.php" enctype="multipart/form-data" id="create-form">
            <div class="form-group">
                <label for="course-select"><i class="fas fa-book" style="color:#6a11cb;margin-right:6px;"></i>Select Course:</label>
                <select id="course-select" name="Course_ID" required>
                    <option value="">Choose a Course</option>
                    <?php while($c = $courses->fetch_assoc()): ?>
                        <option value="<?= htmlspecialchars($c['Course_ID']) ?>">
                            <?= htmlspecialchars($c['Course_ID']) ?> — <?= htmlspecialchars($c['CourseName']) ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="assign-num"><i class="fas fa-hashtag" style="color:#6a11cb;margin-right:6px;"></i>Assignment Number:</label>
                <input type="number" id="assign-num" name="assignment_number" placeholder="Enter the assignment number" min="1" required>
            </div>
            <div class="form-group">
                <label for="assign-desc"><i class="fas fa-align-left" style="color:#6a11cb;margin-right:6px;"></i>Assignment Description:</label>
                <textarea id="assign-desc" name="description" placeholder="Enter a brief description of the assignment (optional)"></textarea>
            </div>

            <!-- NEW: Question File Upload -->
            <div class="form-group">
                <label><i class="fas fa-file-upload" style="color:#6a11cb;margin-right:6px;"></i>Upload Assignment Question / Instructions:</label>
                <div class="upload-box">
                    <input type="file" name="question_file" id="question-file" accept=".pdf,.doc,.docx">
                </div>
                <div class="file-note">
                    📎 Accepted formats: PDF, DOC, DOCX &nbsp;|&nbsp; Max size: 20 MB<br>
                    Students will be able to download this file from their assignments page.
                </div>
            </div>

            <div class="form-group">
                <label><i class="fas fa-file" style="color:#6a11cb;margin-right:6px;"></i>Accepted Submission File Types:</label>
                <div style="display:flex;gap:20px;margin-top:4px;">
                    <label style="font-weight:normal;"><input type="checkbox" name="allow_pdf" value="1" checked> PDF</label>
                    <label style="font-weight:normal;"><input type="checkbox" name="allow_word" value="1" checked> Word (DOC/DOCX)</label>
                </div>
            </div>
            <div class="datetime-row">
                <div class="form-group">
                    <label for="deadline-date"><i class="fas fa-calendar" style="color:#6a11cb;margin-right:6px;"></i>Deadline Date:</label>
                    <input type="date" id="deadline-date" name="deadline_date" required>
                </div>
                <div class="form-group">
                    <label for="deadline-time"><i class="fas fa-clock" style="color:#6a11cb;margin-right:6px;"></i>Deadline Time:</label>
                    <input type="time" id="deadline-time" name="deadline_time" required>
                </div>
            </div>
            <div id="file-size-err" style="color:#e53935;margin-bottom:10px;display:none;">
                ⚠️ File size exceeds 20 MB. Please choose a smaller file.
            </div>
            <button type="submit" class="submit-btn"><i class="fas fa-plus-circle"></i> Create Assignment</button>
        </form>
    </div>
</div>
<script>
document.getElementById('question-file').addEventListener('change', function() {
    var maxSize = 20 * 1024 * 1024;
    var errEl = document.getElementById('file-size-err');
    var btn = document.querySelector('.submit-btn');
    if (this.files[0] && this.files[0].size > maxSize) {
        errEl.style.display = 'block';
        btn.disabled = true;
    } else {
        errEl.style.display = 'none';
        btn.disabled = false;
    }
});
</script>
</body>
</html>