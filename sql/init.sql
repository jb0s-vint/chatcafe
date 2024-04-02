# ChatCafe Database Setup script
# You can run this only once.

# Create Database
CREATE DATABASE IF NOT EXISTS chatcafe;
USE chatcafe;

# Create users table
CREATE TABLE IF NOT EXISTS users (
    id VARCHAR(36) PRIMARY KEY,
    username VARCHAR(25) NOT NULL,
    email VARCHAR(50) NOT NULL,
    password VARCHAR(60) NOT NULL
);

# Create sessions table
CREATE TABLE IF NOT EXISTS sessions (
    token VARCHAR(36) PRIMARY KEY,
    user_id VARCHAR(36) NOT NULL,

    # Define foreign keys
    FOREIGN KEY (user_id) REFERENCES users(id)
);

# Create relationships table
CREATE TABLE IF NOT EXISTS relationships (
    id VARCHAR(36) PRIMARY KEY,
    sender VARCHAR(36) NOT NULL,
    recipient VARCHAR(36) NOT NULL,
    friends BOOLEAN NOT NULL,
    blocked BOOLEAN NOT NULL,

    # Define foreign keys
    FOREIGN KEY (sender) REFERENCES users(id),
    FOREIGN KEY (recipient) REFERENCES users(id)
);

# Create messages table
CREATE TABLE IF NOT EXISTS messages (
    id VARCHAR(36) PRIMARY KEY,
    content VARCHAR(255) NOT NULL,
    relationship VARCHAR(36) NOT NULL,
    is_reply BOOLEAN NOT NULL,
    timestamp MEDIUMTEXT NOT NULL,

    # Define foreign keys
    FOREIGN KEY (relationship) REFERENCES relationships(id)
);