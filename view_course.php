<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Courses - OLP</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div style="max-width: 800px; margin: 0 auto; text-align: center;">
        <h1>Your Courses</h1>

        <?php
        session_start(); // Start the session

        // Check if the user is logged in
        if (!isset($_SESSION['userID'])) {
            header("Location: login.html");
            exit();
        }

        $userID = $_SESSION['userID']; // Get the logged-in user's ID
        $conn = new mysqli("localhost", "root", "", "learning_platform");

        // Check database connection
        if ($conn->connect_error) {
            die("Connection failed: " . $conn->connect_error);
        }

        $sql = "SELECT * FROM Course WHERE UserID = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $userID);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            echo "<table style='width: 100%; border-collapse: collapse; margin-top: 20px;'>
                    <thead>
                        <tr>
                            <th style='border: 1px solid #ccc; padding: 10px;'>Course ID</th>
                            <th style='border: 1px solid #ccc; padding: 10px;'>Course Name</th>
                            <th style='border: 1px solid #ccc; padding: 10px;'>Start Date</th>
                            <th style='border: 1px solid #ccc; padding: 10px;'>End Date</th>
                            <th style='border: 1px solid #ccc; padding: 10px;'>Action</th>
                        </tr>
                    </thead>
                    <tbody>";

            while ($row = $result->fetch_assoc()) {
                echo "<tr>
                        <td style='border: 1px solid #ccc; padding: 10px;'>" . htmlspecialchars($row['Course_ID']) . "</td>
                        <td style='border: 1px solid #ccc; padding: 10px;'>" . htmlspecialchars($row['CourseName']) . "</td>
                        <td style='border: 1px solid #ccc; padding: 10px;'>" . htmlspecialchars($row['Start_Date']) . "</td>
                        <td style='border: 1px solid #ccc; padding: 10px;'>" . htmlspecialchars($row['End_Date']) . "</td>
                        <td style='border: 1px solid #ccc; padding: 10px;'>
                            <a href='course_description.php?course_id=" . $row['Course_ID'] . "'>
                                <button>View Description</button>
                            </a>
                        </td>
                      </tr>";
            }

            echo "</tbody></table>";
        } else {
            echo "<p style='margin-top: 20px; color: #555;'>No courses found.</p>";
        }

        $stmt->close();
        $conn->close();
        ?>

        <a href="instructor_dashboard.php"><button style="margin-top: 20px; padding: 10px 20px;">Back to Dashboard</button></a>
    </div>
</body>
</html>