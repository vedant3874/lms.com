<?php
// File: config.php

// Start session only if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$host = 'localhost';
$username = 'root';
$password = '';
$database = 'lms_realistic'; // Change this to your actual database name

$conn = new mysqli($host, $username, $password, $database);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Function to get user info
function getUserInfo($user_id, $conn) {
    $stmt = $conn->prepare("SELECT id, name, email, role FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}

// Function to get unread message count
function getUnreadMessageCount($user_id, $conn) {
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM messages WHERE receiver_id = ? AND is_read = 0");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    return $result['count'];
}

// Function to check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user']);
}

// Function to redirect if not logged in
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit;
    }
}

// Function to redirect if not specific role
function requireRole($role) {
    if (!isset($_SESSION['user']) || $_SESSION['user']['role'] != $role) {
        header('Location: login.php');
        exit;
    }
}
?>