<?php
// Start the session to retrieve producerID
session_start();

// Check if the producer is logged in
if (!isset($_SESSION['producerID'])) {
    // Redirect to the login page or display an error message
    header("Location: login.php");
    exit();
}

$producerID = $_SESSION['producerID'];

// Database connection code - modify according to your database configuration
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "food";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Handling producer's submission to produce a food item
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $foodName = $_POST["foodName"];
    $foodDescription = $_POST["foodDescription"];
    $foodPrice = $_POST["foodPrice"];

    // Check if food type is selected
    if (isset($_POST["foodTypeID"])) {
        $foodTypeID = $_POST["foodTypeID"];

        // Insert the produced food into the database
        $sql = "INSERT INTO Food (foodName, foodDescription, foodPrice, producerID, typeName)
                VALUES ('$foodName', '$foodDescription', $foodPrice, $producerID, '$foodTypeID')";

        if ($conn->query($sql) === TRUE) {
            echo "Food produced successfully!";
        } else {
            echo "Error producing food: " . $conn->error;
        }
    } else {
        echo "Please select a food type.";
    }
}

// Get the list of available food types for the dropdown selection
$foodTypeQuery = "SELECT * FROM FoodType";
$foodTypeResult = $conn->query($foodTypeQuery);

// Close the database connection
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Producer Home</title>
</head>
<body>
    <h2>Welcome, Producer!</h2>
    
    <h3>Produce a Food Item</h3>
    <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
        <label for="foodName">Food Name:</label>
        <input type="text" name="foodName" required><br>

        <label for="foodDescription">Food Description:</label>
        <textarea name="foodDescription" rows="4" required></textarea><br>

        <label for="foodPrice">Food Price:</label>
        <input type="number" name="foodPrice" step="0.01" required><br>

        <!-- Only display the food type selection if there are available food types -->
        <?php if ($foodTypeResult->num_rows > 0): ?>
            <label for="foodTypeID">Food Type:</label>
            <select name="foodTypeID" required>
                <?php
                while ($row = $foodTypeResult->fetch_assoc()) {
                    echo "<option value='" . $row['typeName'] . "'>" . $row['typeName'] . "</option>";
                }
                ?>
            </select><br>
        <?php endif; ?>

        <input type="submit" value="Produce Food">
    </form>
</body>
</html>
