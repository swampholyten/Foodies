<?php
// Start the session to retrieve riderID
session_start();

// Check if the rider is logged in
if (!isset($_SESSION['riderID'])) {
    // Redirect to the login page or display an error message
    header("Location: login.php");
    exit();
}

$riderID = $_SESSION['riderID'];

// Database connection code - modify according to your database configuration
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "food";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Handling rider's submission to accept, complete, or quit a request
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["requestID"])){
    $requestID = $_POST["requestID"];

    // Check the number of penalty records for the rider
    $penaltyCountQuery = "SELECT COUNT(*) AS penaltyCount FROM RiderPenalty WHERE riderID = $riderID";
    $penaltyCountResult = $conn->query($penaltyCountQuery);

    if ($penaltyCountResult->num_rows > 0) {
        $penaltyCount = $penaltyCountResult->fetch_assoc()['penaltyCount'];

        if ($penaltyCount >= 5) {
            // Remove the rider's existence from everywhere in the database
            $removeRiderQuery = "DELETE FROM Rider WHERE riderID = $riderID";
            $removePenaltiesQuery = "DELETE FROM RiderPenalty WHERE riderID = $riderID";
            // Update Order records to dissociate them from the rider
            $updateOrdersQuery = "UPDATE `Order` SET riderID = -1 WHERE riderID = $riderID";
            // Set acceptedBy to NULL for requests associated with the rider
            $removeRequestsQuery = "UPDATE Request SET acceptedBy = NULL WHERE acceptedBy = $riderID";

            // Execute the queries
            if ($conn->query($updateOrdersQuery) === TRUE && $conn->query($removeRequestsQuery) === TRUE && $conn->query($removePenaltiesQuery) === TRUE && $conn->query($removeRiderQuery)) {
                // Redirect to the login page or display a message
                header("Location: login.php");
                exit();
            } else {
                echo "Error removing rider's existence: " . $conn->error;
            }
        }
    }

    if (isset($_POST["accept_request"])) {
        // Check if the rider has an active penalty
        $checkPenaltyQuery = "SELECT * FROM RiderPenalty 
                              WHERE riderID = $riderID 
                              AND NOW() BETWEEN penaltyTime AND DATE_ADD(penaltyTime, INTERVAL suspensionDuration MINUTE)";

        $penaltyResult = $conn->query($checkPenaltyQuery);

        if ($penaltyResult->num_rows > 0) {
            // Rider has an active penalty, cannot accept the request
            echo "You have an active penalty. You cannot accept new requests at this time.";
        } else {
            // Update the request with the rider who accepted it
            $updateRequestQuery = "UPDATE Request SET acceptedBy = $riderID, acceptedTime = NOW() WHERE requestID = $requestID";
            if ($conn->query($updateRequestQuery) === TRUE) {
                echo "Request accepted successfully!";
            } else {
                echo "Error accepting request: " . $conn->error;
            }
        }
    } elseif (isset($_POST["complete_request"])) {
        // Complete the request - set isCompleted true and update order's deliveredTime
        $completeRequestQuery = "UPDATE Request
                                 SET isCompleted = TRUE
                                 WHERE requestID = $requestID";
        if ($conn->query($completeRequestQuery) === TRUE) {
            // Get the old madeTime value
            $getOldMadeTimeQuery = "SELECT `Order`.madeTime 
                                    FROM `Order`
                                    WHERE orderID = (SELECT orderID FROM Request WHERE requestID = $requestID)";
            $oldMadeTimeResult = $conn->query($getOldMadeTimeQuery);
            $oldMadeTime = $oldMadeTimeResult->fetch_assoc()['madeTime'];

            // Update the order's deliveredTime, madeTime, and riderID
            $updateOrderQuery = "UPDATE `Order` 
                                 SET deliveredTime = NOW(), madeTime = '$oldMadeTime', riderID = $riderID
                                 WHERE orderID = (SELECT orderID FROM Request WHERE requestID = $requestID)";
            if ($conn->query($updateOrderQuery) === TRUE) {
                echo "Request completed successfully!";
            } else {
                echo "Error completing request: " . $conn->error;
            }
        } else {
            echo "Error completing request: " . $conn->error;
        }
    } elseif (isset($_POST["quit_request"])) {
        // Quit the request (remove acceptedBy) and create a new RiderPenalty record
        $quitRequestQuery = "UPDATE Request SET acceptedBy = NULL WHERE requestID = $requestID";
        
        // Create a new RiderPenalty record
        $createPenaltyQuery = "INSERT INTO RiderPenalty (riderID, penaltyTime, suspensionDuration) 
                               SELECT $riderID, NOW(), (120 + IFNULL((SELECT COUNT(*) FROM RiderPenalty WHERE riderID = $riderID), 0) * 10)";

        if ($conn->query($quitRequestQuery) === TRUE && $conn->query($createPenaltyQuery) === TRUE) {
            echo "You have quit the request. A penalty has been applied.";
        } else {
            echo "Error quitting request or creating penalty: " . $conn->error;
        }
    }
}

// Handling rider's submission to switch company
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["switch_company"])) {
    // Assuming you have already fetched the selected company ID from the form
    $selectedCompanyID = $_POST["company"];

    // Update the rider's selected company
    $updateRiderCompanyQuery = "UPDATE Rider SET companyID = $selectedCompanyID WHERE riderID = $riderID";

    if ($conn->query($updateRiderCompanyQuery) === TRUE) {
        echo "Company switched successfully!";
        // You may want to redirect or reload the page after updating the company
    } else {
        echo "Error switching company: " . $conn->error;
    }
}

// Get the list of requests including those accepted by the current rider, excluding completed requests
$requestsQuery = "SELECT Request.*, `Order`.madeTime, Company.companyName, `User`.firstName, `User`.lastName
                 FROM Request
                 INNER JOIN `Order` ON Request.orderID = `Order`.orderID
                 INNER JOIN Company ON Request.publishedBy = Company.companyID
                 INNER JOIN `User` ON `Order`.customerID = `User`.userID
                 WHERE ((Request.isCompleted = FALSE AND Request.acceptedBy IS NULL) OR Request.acceptedBy = $riderID)
                 AND Request.isCompleted = FALSE
                 AND Request.publishedBy = (SELECT companyID FROM Rider WHERE riderID = $riderID)";




$requestsResult = $conn->query($requestsQuery);

// Get the list of available companies
$companyQuery = "SELECT * FROM Company";
$companyResult = $conn->query($companyQuery);

// Close the database connection
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rider Home</title>
</head>
<body>
    <h2>Welcome, Rider!</h2>

    <h3>Switch Company</h3>
    <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
        <label for="company">Select Company:</label>
        <select name="company" id="company">
            <?php
            // Fetch and display the company data for the dropdown
            while ($companyRow = $companyResult->fetch_assoc()) {
                echo "<option value='{$companyRow['companyID']}'>{$companyRow['companyName']}</option>";
            }
            ?>
        </select>
        <input type="submit" name="switch_company" value="Switch Company">
    </form>

    <h3>Requests</h3>
    <table border="1">
        <tr>
            <th>Request ID</th>
            <th>Company</th>
            <th>Customer</th>
            <th>Order Made Time</th>
            <th>Status</th>
            <th>Action</th>
        </tr>
        <?php
        while ($row = $requestsResult->fetch_assoc()) {
            echo "<tr>
                    <td>{$row['requestID']}</td>
                    <td>{$row['companyName']}</td>
                    <td>{$row['firstName']} {$row['lastName']}</td>
                    <td>{$row['madeTime']}</td>
                    <td>";
            
            if ($row['acceptedBy'] == $riderID) {
                echo "Accepted by You";
            } elseif ($row['acceptedBy'] === NULL) {
                echo "Not Accepted";
            } else {
                echo "Accepted by Another Rider";
            }

            echo "</td>
                    <td>";
            
            if ($row['acceptedBy'] === NULL) {
                echo "<form method='post' action='" . htmlspecialchars($_SERVER["PHP_SELF"]) . "'>
                        <input type='hidden' name='requestID' value='{$row['requestID']}'>
                        <input type='submit' name='accept_request' value='Accept Request'>
                      </form>";
            } elseif ($row['acceptedBy'] == $riderID) {
                echo "<form method='post' action='" . htmlspecialchars($_SERVER["PHP_SELF"]) . "'>
                        <input type='hidden' name='requestID' value='{$row['requestID']}'>
                        <input type='submit' name='complete_request' value='Complete Request'>
                        <input type='submit' name='quit_request' value='Quit Request'>
                      </form>";
            }

            echo "</td>
                  </tr>";
        }
        ?>
    </table>
</body>
</html>
