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

// Function to generate a random license number for a rider
function generateRandomLicense() {
    $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    $license = '';
    for ($i = 0; $i < 10; $i++) {
        $license .= $characters[rand(0, strlen($characters) - 1)];
    }
    return $license;
}

// Handling user-submitted registration form
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get user input data from the form
    $firstName = $_POST["firstName"];
    $lastName = $_POST["lastName"];
    $email = $_POST["email"];
    $phoneNumber = $_POST["phoneNumber"];
    $street = $_POST["street"];
    $zipCode = $_POST["zipCode"];
    $cityID = $_POST["cityID"];
    $birthday = $_POST["birthday"];
    $gender = $_POST["gender"];
    $password = $_POST["password"];
    $userType = $_POST["userType"];

    // Create a new user without hashing the password
    $sql = "INSERT INTO `User` (firstName, lastName, email, phoneNumber, birthday, gender, password) 
            VALUES ('$firstName', '$lastName', '$email', '$phoneNumber', '$birthday', '$gender', '$password')";

    if ($conn->query($sql) === TRUE) {
        $userID = $conn->insert_id;

        // Create a new address
        $sql = "INSERT INTO Address (street, cityID, zipCode) VALUES ('$street', $cityID, '$zipCode')";
        if ($conn->query($sql) === TRUE) {
            $addressID = $conn->insert_id;

            // Update user's address ID
            $sql = "UPDATE `User` SET addressID = $addressID WHERE userID = $userID";
            $conn->query($sql);

            // Insert into corresponding user type table
            switch ($userType) {
                case 'customer':
                    $sql = "INSERT INTO Customer (userID) VALUES ($userID)";
                    break;
                case 'producer':
                    $sql = "INSERT INTO Producer (userID) VALUES ($userID)";
                    break;
                case 'rider':
                    // Generate a random license number for the rider
                    $licenceNumber = generateRandomLicense();
                    $sql = "INSERT INTO Rider (userID, licenceNumber) VALUES ($userID, '$licenceNumber')";
                    break;
                default:
                    // Handle other cases or show an error message
                    echo "Invalid user type!";
                    exit();
            }

            if ($conn->query($sql) === TRUE) {
                // Output success message or redirect to another page
                echo "Registration successful!";
            } else {
                echo "Error creating user type record: " . $conn->error;
            }
        } else {
            echo "Error creating address: " . $conn->error;
        }
    } else {
        echo "Error creating user: " . $conn->error;
    }
}

// Get the list of cities for the dropdown selection
$cityQuery = "SELECT * FROM City";
$cityResult = $conn->query($cityQuery);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Registration</title>
</head>
<body>
    <h2>User Registration</h2>
    <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
        <label for="firstName">First Name:</label>
        <input type="text" name="firstName" required><br>

        <label for="lastName">Last Name:</label>
        <input type="text" name="lastName" required><br>

        <label for="email">Email:</label>
        <input type="email" name="email" required><br>

        <label for="phoneNumber">Phone Number:</label>
        <input type="text" name="phoneNumber" required><br>

        <label for="street">Street Address:</label>
        <input type="text" name="street" required><br>

        <label for="zipCode">Zip Code:</label>
        <input type="text" name="zipCode" required><br>

        <label for="cityID">City:</label>
        <select name="cityID" required>
            <?php
            while ($row = $cityResult->fetch_assoc()) {
                echo "<option value=" . $row['cityID'] . ">" . $row['cityName'] . "</option>";
            }
            ?>
        </select><br>

        <label for="birthday">Birthday:</label>
        <input type="date" name="birthday"><br>

        <label for="gender">Gender:</label>
        <select name="gender">
            <option value="Male">Male</option>
            <option value="Female">Female</option>
        </select><br>

        <label for="password">Password:</label>
        <input type="password" name="password" required><br>

        <label for="userType">User Type:</label>
        <select name="userType" required>
            <option value="customer">Customer</option>
            <option value="producer">Producer</option>
            <option value="rider">Rider</option>
        </select><br>

        <input type="submit" value="Register">
    </form>
</body>
</html>

<?php
// Close the database connection
$conn->close();
?>
