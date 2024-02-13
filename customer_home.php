<?php
// Start the session to retrieve customerID
session_start();

// Check if the customer is logged in
if (!isset($_SESSION['customerID'])) {
    // Redirect to the login page or display an error message
    header("Location: login.php");
    exit();
}

$customerID = $_SESSION['customerID'];

// Database connection code - modify according to your database configuration
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "food";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Handling customer's submission to add items to the shopping cart
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["add_to_cart"])) {
    $foodID = $_POST["foodID"];
    $quantity = $_POST["quantity"];

    // Check if the selected food is still available
    $foodAvailabilityQuery = "SELECT Food.*, FoodType.expirationTime,
    TIMESTAMPDIFF(MINUTE, Food.createdTime, NOW()) AS elapsedMinutes
    FROM Food
    INNER JOIN FoodType ON Food.typeName = FoodType.typeName
    WHERE NOW() <= DATE_ADD(Food.createdTime, INTERVAL FoodType.expirationTime MINUTE)
    AND Food.foodID = $foodID";

    $foodAvailabilityResult = $conn->query($foodAvailabilityQuery);

    if ($foodAvailabilityResult->num_rows > 0) {
        $row = $foodAvailabilityResult->fetch_assoc();
        // The food is available, add it to the shopping cart
        $_SESSION['shopping_cart'][] = array(
            'foodID' => $row['foodID'],
            'foodName' => $row['foodName'],
            'quantity' => $quantity,
            'subtotal' => $row['foodPrice'] * $quantity,
            'expirationTime' => $row['expirationTime'] - $row['elapsedMinutes']
        );
        echo "Food added to the shopping cart!";
    } else {
        echo "Selected food is not available or has expired.";
    }
}

// Handling customer's submission to place an order
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["place_order"])) {
    if (!empty($_SESSION['shopping_cart'])) {
        // Place the order
        $orderInsertQuery = "INSERT INTO `Order` (customerID, madeTime) VALUES ($customerID, NOW())";
        if ($conn->query($orderInsertQuery) === TRUE) {
            $orderID = $conn->insert_id;

            // Insert the order items
            foreach ($_SESSION['shopping_cart'] as $cartItem) {
                $foodID = $cartItem['foodID'];
                $quantity = $cartItem['quantity'];
                $subtotal = $cartItem['subtotal'];

                $orderItemInsertQuery = "INSERT INTO OrderItem (orderID, foodID, quantity, subtotal)
                                         VALUES ($orderID, $foodID, $quantity, $subtotal)";
                
                $conn->query($orderItemInsertQuery);
            }

            // Clear the shopping cart after placing the order
            $_SESSION['shopping_cart'] = array();
            echo "Order placed successfully!";

            // Create a Request record
            $cityQuery = "SELECT City.cityID, Company.companyID, Company.addressID
                          FROM `User`
                          INNER JOIN Address AS UserAddress ON `User`.addressID = UserAddress.addressID
                          INNER JOIN City ON UserAddress.cityID = City.cityID
                          INNER JOIN Company ON Company.addressID = UserAddress.addressID
                          WHERE `User`.userID = $customerID";

            $cityResult = $conn->query($cityQuery);

            if ($cityResult->num_rows > 0) {
                $row = $cityResult->fetch_assoc();
                $companyID = $row['companyID'];
                $fromAddressID = $row['addressID'];

                // Insert the Request record
                $requestInsertQuery = "INSERT INTO Request (orderID, fromAddressID, toAddressID, publishedTime, publishedBy)
                                       VALUES ($orderID, $fromAddressID, 
                                               (SELECT addressID FROM Company WHERE companyID = $companyID),
                                               NOW(), $companyID)";

                $conn->query($requestInsertQuery);
            } else {
                echo "Error creating Request: Could not determine the associated city and company.";
            }
        } else {
            echo "Error placing order: " . $conn->error;
        }
    } else {
        echo "Shopping cart is empty. Add items before placing an order.";
    }
}

// Get the list of available foods with remaining expiration time
$availableFoodsQuery = "SELECT Food.*, FoodType.expirationTime,
                        TIMESTAMPDIFF(MINUTE, Food.createdTime, NOW()) AS elapsedMinutes
                        FROM Food
                        INNER JOIN FoodType ON Food.typeName = FoodType.typeName
                        WHERE NOW() <= DATE_ADD(Food.createdTime, INTERVAL FoodType.expirationTime MINUTE)";

$availableFoodsResult = $conn->query($availableFoodsQuery);

// Close the database connection
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Home</title>
</head>
<body>
    <h2>Welcome, Customer!</h2>
    
    <h3>Available Foods</h3>
    <table border="1">
        <tr>
            <th>Food ID</th>
            <th>Food Name</th>
            <th>Description</th>
            <th>Price</th>
            <th>Remaining Expiration Time (Minutes)</th>
            <th>Action</th>
        </tr>
        <?php
        while ($row = $availableFoodsResult->fetch_assoc()) {
            $remainingTime = $row['expirationTime'] - $row['elapsedMinutes'];
            echo "<tr>
                    <td>{$row['foodID']}</td>
                    <td>{$row['foodName']}</td>
                    <td>{$row['foodDescription']}</td>
                    <td>{$row['foodPrice']}</td>
                    <td>{$remainingTime}</td>
                    <td>
                        <form method='post' action='" . htmlspecialchars($_SERVER["PHP_SELF"]) . "'>
                            <input type='hidden' name='foodID' value='{$row['foodID']}'>
                            <label for='quantity'>Quantity:</label>
                            <input type='number' name='quantity' value='1' min='1' max='10' required>
                            <input type='submit' name='add_to_cart' value='Add to Cart'>
                        </form>
                    </td>
                  </tr>";
        }
        ?>
    </table>

    <h3>Shopping Cart</h3>
    <?php
    if (!empty($_SESSION['shopping_cart'])) {
        echo "<table border='1'>
                <tr>
                    <th>Food ID</th>
                    <th>Food Name</th>
                    <th>Quantity</th>
                    <th>Subtotal</th>
                    <th>Expiration Time (Minutes)</th>
                </tr>";
        
        foreach ($_SESSION['shopping_cart'] as $cartItem) {
            echo "<tr>
                    <td>{$cartItem['foodID']}</td>
                    <td>{$cartItem['foodName']}</td>
                    <td>{$cartItem['quantity']}</td>
                    <td>{$cartItem['subtotal']}</td>
                    <td>{$cartItem['expirationTime']}</td>
                  </tr>";
        }

        echo "</table>";

        echo "<form method='post' action='" . htmlspecialchars($_SERVER["PHP_SELF"]) . "'>
                <input type='submit' name='place_order' value='Place Order'>
              </form>";
    } else {
        echo "Shopping cart is empty.";
    }
    ?>
</body>
</html>