<?php
// Database configuration
$host = 'localhost';
$dbname = 'stockms3';
$username = 'root';
$password = 'khaled';

try {
    // Connect to MySQL
    $pdo = new PDO("mysql:host=$host", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Create database if not exists
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbname`");
    echo "Database created or already exists.\n";
    
    // Connect to the specific database
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Create tables
    // You can add your table creation SQL statements here
    // For example:
    $sql = "
    CREATE TABLE IF NOT EXISTS `users` (
        `id` INT NOT NULL AUTO_INCREMENT,
        `username` VARCHAR(255) NOT NULL,
        `auth_key` VARCHAR(32) NOT NULL,
        `password_hash` VARCHAR(255) NOT NULL,
        `password_reset_token` VARCHAR(255) NULL,
        `email` VARCHAR(255) NOT NULL,
        `status` SMALLINT NOT NULL DEFAULT 10,
        `created_at` INT NOT NULL,
        `updated_at` INT NOT NULL,
        PRIMARY KEY (`id`),
        UNIQUE INDEX `username_UNIQUE` (`username` ASC),
        UNIQUE INDEX `email_UNIQUE` (`email` ASC)
    );
    ";
    
    $pdo->exec($sql);
    echo "Tables created successfully.\n";
    
} catch(PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
