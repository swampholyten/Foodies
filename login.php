<?php
// Database connection code - modify according to your database configuration
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "food";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Handling user-submitted login form
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST["email"];
    $password = $_POST["password"];

    // Check user credentials
    $sql = "SELECT * FROM `User` WHERE email = '$email' AND password = '$password'";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $userID = $row["userID"];

        // Check how many user types the user has
        $userTypes = array();

        $customerQuery = "SELECT * FROM Customer WHERE userID = $userID";
        $customerResult = $conn->query($customerQuery);
        if ($customerResult->num_rows > 0) {
            $userTypes[] = 'customer';
        }

        $producerQuery = "SELECT * FROM Producer WHERE userID = $userID";
        $producerResult = $conn->query($producerQuery);
        if ($producerResult->num_rows > 0) {
            $userTypes[] = 'producer';
            // Start a session and store producerID
            session_start();
            $_SESSION['producerID'] = $userID;
        }

        $riderQuery = "SELECT * FROM Rider WHERE userID = $userID";
        $riderResult = $conn->query($riderQuery);
        if ($riderResult->num_rows > 0) {
            $userTypes[] = 'rider';
        }

        // Start a session for other user types
        foreach ($userTypes as $type) {
            session_start();
            $_SESSION[$type . 'ID'] = $userID;
        }

        // Redirect to identity selection page if the user has multiple types
        if (count($userTypes) > 1) {
            header("Location: choose_identity.php?userID=$userID");
            exit();
        }

        // Redirect to the corresponding home page if the user has only one type
        if (count($userTypes) == 1) {
            $selectedIdentity = $userTypes[0];
            header("Location: " . $selectedIdentity . "_home.php?userID=$userID");
            exit();
        }

        // Redirect to a default page or show an error message
        header("Location: default_home.php");
        exit();
    } else {
        echo "Invalid email or password!";
    }
}

// Close the database connection
$conn->close();
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Login</title>
</head>
<body>
    <h2>User Login</h2>
    <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
        <label for="email">Email:</label>
        <input type="email" name="email" required><br>

        <label for="password">Password:</label>
        <input type="password" name="password" required><br>

        <input type="submit" value="Login">
    </form>
</body>
</html>
