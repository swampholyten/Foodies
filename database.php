<?php

class Database {
    private $host = "localhost";
    private $username = "root";
    private $password = "";
    private $database = "food";
    private $conn;

    public function __construct() {
        $this->conn = new mysqli($this->host, $this->username, $this->password, $this->database);

        if ($this->conn->connect_error) {
            die("Connection failed: " . $this->conn->connect_error);
        }
    }

    public function closeConnection() {
        $this->conn->close();
    }

    // You can add more CRUD operations as needed

    public function getAllCities() {
        $query = "SELECT * FROM City";
        $result = $this->conn->query($query);
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    public function insertCity($cityName) {
        $query = "INSERT INTO City (cityName) VALUES ('$cityName')";
        return $this->conn->query($query);
    }

    // Add similar functions for other tables

}

// Example usage:
/*
$database = new Database();

// Insert a city
$database->insertCity("City1");

// Get all cities
$cities = $database->getAllCities();
print_r($cities);

$database->closeConnection();
*/

?>
