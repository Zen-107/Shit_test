<?php
require_once "config.php";

header("Content-Type: application/json");

if (!empty($_SESSION["user_email"])) {
    echo json_encode([
        "loggedIn" => true,
        "email" => $_SESSION["user_email"]
    ]);
} else {
    echo json_encode([
        "loggedIn" => false
    ]);
}
