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

// Handling user-submitted form to choose identity
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $userID = $_POST["userID"];
    $selectedIdentity = $_POST["selectedIdentity"];

    switch ($selectedIdentity) {
        case 'customer':
            header("Location: customer_home.php?userID=$userID");
            exit();
        case 'producer':
            header("Location: producer_home.php?userID=$userID");
            exit();
        case 'rider':
            header("Location: rider_home.php?userID=$userID");
            exit();
        default:
            // Handle other cases or show an error message
            echo "Invalid identity!";
            exit();
    }
}

// Get the list of user identities
$userID = $_GET["userID"];
$userQuery = "SELECT * FROM User WHERE userID = $userID";
$userResult = $conn->query($userQuery);

if ($userResult->num_rows > 0) {
    $row = $userResult->fetch_assoc();
    ?>

    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Choose Identity</title>
    </head>
    <body>
        <h2>Choose Your Identity</h2>
        <p>Welcome, <?php echo $row["firstName"]; ?>!</p>
        <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
            <input type="hidden" name="userID" value="<?php echo $userID; ?>">
            <label>
                <input type="radio" name="selectedIdentity" value="customer" required> Customer
            </label>
            <label>
                <input type="radio" name="selectedIdentity" value="producer" required> Producer
            </label>
            <label>
                <input type="radio" name="selectedIdentity" value="rider" required> Rider
            </label>
            <br>
            <input type="submit" value="Login">
        </form>
    </body>
    </html>

    <?php
} else {
    echo "User not found!";
}

// Close the database connection
$conn->close();
?>
