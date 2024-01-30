<?php

// Start the session to retrieve customerID
session_start();

// Database connection code - modify according to your database configuration
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "food";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$cond = 1;

// 处理删除操作
if(isset($_POST['action']) && isset($_POST['id'])) {
    $action = $_POST['action'];
    $id = $_POST['id'];

    switch($action) {

        case 'remove_request':
            // 删除请求（requests）
            $query = "DELETE FROM Request WHERE requestID = $id";
            break;

        case 'remove_order':
            // 删除订单（orders）及相关订单项（orderItems）和请求（requests）
            $queryDeleteOrderItems = "DELETE FROM OrderItem WHERE orderID = $id";
            $queryDeleteRequests = "DELETE FROM Request WHERE orderID = $id";
            $queryDeleteOrder = "DELETE FROM `Order` WHERE orderID = $id";

            // 开始事务，确保删除操作的原子性
            $conn->begin_transaction();

            // 执行删除订单项操作
            $resultDeleteOrderItems = $conn->query($queryDeleteOrderItems);

            // 执行删除请求操作
            $resultDeleteRequests = $conn->query($queryDeleteRequests);

            // 执行删除订单操作
            $resultDeleteOrder = $conn->query($queryDeleteOrder);

            // 提交或回滚事务
            if ($resultDeleteOrderItems && $resultDeleteRequests && $resultDeleteOrder) {
                $conn->commit();
                echo "Order, associated items, and related requests successfully removed";
            } else {
                $conn->rollback();
                echo "Error removing order, associated items, and related requests: " . $conn->error;
            }

            $cond = 0;

            break;

        case 'remove_order_item':
            // 删除订单项（orderItems）
            $query = "DELETE FROM OrderItem WHERE orderItemID = $id";
            break;

        case 'remove_rider_penalty':
            // 删除骑手处罚（riderPenalty）
            $query = "DELETE FROM RiderPenalty WHERE penaltyID = $id";
            break;

        default:
            // 如果没有匹配的操作，输出错误信息
            echo "Invalid action";
            exit();
    }

    if($cond) {
        // 执行删除操作
        $result = $conn->query($query);

        if($result) {
            echo "Record successfully removed";
        } else {
            echo "Error removing record: " . $conn->error;
        }
    } else {
        $cond = 1;
    }
}

// 处理创建操作
if(isset($_POST['create']) && isset($_POST['type'])) {
    $type = $_POST['type'];

    switch($type) {
        case 'city':
            // 创建城市
            $cityName = $_POST['cityName'];
            $query = "INSERT INTO City (cityName) VALUES ('$cityName')";
            break;

        case 'foodType':
            // 创建食品类型
            $typeName = $_POST['typeName'];
            $foodTypeDescription = $_POST['foodTypeDescription'];
            $expirationTime = $_POST['expirationTime'];
            $query = "INSERT INTO FoodType (typeName, foodTypeDescription, expirationTime) VALUES ('$typeName', '$foodTypeDescription', $expirationTime)";
            break;

        case 'company':
            // 创建公司
            $companyName = $_POST['companyName'];
            $addressID = $_POST['addressID'];
            $query = "INSERT INTO Company (companyName, addressID) VALUES ('$companyName', $addressID)";
            break;

        case 'address':
            // 创建地址
            $street = $_POST['street'];
            $cityID = $_POST['cityID'];
            $zipCode = $_POST['zipCode'];
            $query = "INSERT INTO Address (street, cityID, zipCode) VALUES ('$street', $cityID, '$zipCode')";
            break;

        default:
            // 如果没有匹配的操作，输出错误信息
            echo "Invalid type";
            exit();
    }

    // 执行创建操作
    $result = $conn->query($query);

    if($result) {
        echo "Record successfully created";
    } else {
        echo "Error creating record: " . $conn->error;
    }
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>Admin Page</title>
</head>
<body>
    <h1>Admin Page</h1>


    <!-- 删除请求（requests） -->
    <h2>Remove Request</h2>
    <form action="admin.php" method="post">
        <input type="hidden" name="action" value="remove_request">
        Request ID: <input type="text" name="id" required>
        <input type="submit" value="Remove Request">
    </form>

    <!-- 删除订单（orders） -->
    <h2>Remove Order</h2>
    <form action="admin.php" method="post">
        <input type="hidden" name="action" value="remove_order">
        Order ID: <input type="text" name="id" required>
        <input type="submit" value="Remove Order">
    </form>

    <!-- 删除订单项（orderItems） -->
    <h2>Remove Order Item</h2>
    <form action="admin.php" method="post">
        <input type="hidden" name="action" value="remove_order_item">
        Order Item ID: <input type="text" name="id" required>
        <input type="submit" value="Remove Order Item">
    </form>

    <!-- 删除骑手处罚（riderPenalty） -->
    <h2>Remove Rider Penalty</h2>
    <form action="admin.php" method="post">
        <input type="hidden" name="action" value="remove_rider_penalty">
        Penalty ID: <input type="text" name="id" required>
        <input type="submit" value="Remove Rider Penalty">
    </form>

    <!-- 创建城市 -->
    <h2>Create City</h2>
    <form action="admin.php" method="post">
        <input type="hidden" name="create" value="1">
        <input type="hidden" name="type" value="city">
        City Name: <input type="text" name="cityName" required>
        <input type="submit" value="Create City">
    </form>

    <!-- 创建食品类型 -->
    <h2>Create Food Type</h2>
    <form action="admin.php" method="post">
        <input type="hidden" name="create" value="1">
        <input type="hidden" name="type" value="foodType">
        Type Name: <input type="text" name="typeName" required><br>
        Description: <input type="text" name="foodTypeDescription" required><br>
        Expiration Time: <input type="text" name="expirationTime" required><br>
        <input type="submit" value="Create Food Type">
    </form>

    <!-- 创建公司 -->
    <h2>Create Company</h2>
    <form action="admin.php" method="post">
        <input type="hidden" name="create" value="1">
        <input type="hidden" name="type" value="company">
        Company Name: <input type="text" name="companyName" required><br>
        Address ID: <input type="text" name="addressID" required><br>
        <input type="submit" value="Create Company">
    </form>

    <!-- 创建地址 -->
    <h2>Create Address</h2>
    <form action="admin.php" method="post">
        <input type="hidden" name="create" value="1">
        <input type="hidden" name="type" value="address">
        Street: <input type="text" name="street" required><br>
        City ID: <input type="text" name="cityID" required><br>
        Zip Code: <input type="text" name="zipCode" required><br>
        <input type="submit" value="Create Address">
    </form>
</body>
</html>
