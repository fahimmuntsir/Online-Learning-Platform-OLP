<?php
// Database connection parameters
$servername = "localhost";
$username = "root"; // Adjust if you have a different username
$password = ""; // Adjust if you have a password
$dbname = "learning_platform";

// Create a connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Check if form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $userId = $_POST['UserID'];
    $firstName = $_POST['First_Name'];
    $lastName = $_POST['Last_Name'];
    $role = $_POST['Role']; // Either 'Student' or 'Instructor'
    $email = $_POST['Email'];
    $password = password_hash($_POST['Password'], PASSWORD_DEFAULT); // Hash the password for security

    // Check if the user ID or email already exists
    $checkQuery = "SELECT * FROM Userinfo WHERE UserID = ? OR Email = ?";
    $stmt = $conn->prepare($checkQuery);
    $stmt->bind_param("ss", $userId, $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        echo "Error: User ID or Email already exists. Please try again with different credentials.";
    } else {
        // Insert the new user into the Userinfo table
        $insertQuery = "INSERT INTO Userinfo (UserID, First_Name, Last_Name, Role, Email, Password) VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($insertQuery);
        $stmt->bind_param("ssssss", $userId, $firstName, $lastName, $role, $email, $password);

        if ($stmt->execute()) {
            // Get the auto-generated ID for the new user
            $lastInsertedId = $stmt->insert_id;

            // Insert into the corresponding table based on the role
            if ($role === "student") {
                $studentInsertQuery = "INSERT INTO Student (UserID) VALUES (?)";
                $stmt = $conn->prepare($studentInsertQuery);
                $stmt->bind_param("s", $userId);

                if ($stmt->execute()) {
                    echo "Student ID successfully created! Redirecting to the login page.";
                } else {
                    echo "Error creating Student ID: " . $stmt->error;
                }
            } elseif ($role === "instructor") {
                $instructorInsertQuery = "INSERT INTO Instructor (UserID) VALUES (?)";
                $stmt = $conn->prepare($instructorInsertQuery);
                $stmt->bind_param("s", $userId);

                if ($stmt->execute()) {
                    echo "Instructor ID successfully created! Redirecting to the login page.";
                } else {
                    echo "Error creating Instructor ID: " . $stmt->error;
                }
            }

            // Redirect to login
            header("Location: login.html");
            exit();
        } else {
            echo "Error: " . $stmt->error;
        }
    }

    // Close the statement
    $stmt->close();
}

// Close the connection
$conn->close();
?>
