<?php

/**
 * Creates a new user and saves them to the database and returns their user id.
 */
function create_user($email, $username, $password) 
{
    # Prepare server-generated info
    $uuid = uuidv4();
    $hashed_pw = password_hash($password, 0);
    
    // Establish DB connection and prepare a statement
    $sql = get_connection();
    $stmt = $sql->prepare("INSERT INTO users(id, username, email, password) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $uuid, $username, $email, $hashed_pw);

    // Execute
    $stmt->execute();

    // Return user ID
    return $uuid;
}

/**
 * Creates a new session for a user and returns its ID.
 */
function create_session($user_id) 
{
    # Prepare server-generated info
    $token = uuidv4();

    // Establish DB connection and prepare a statement
    $sql = get_connection();
    $stmt = $sql->prepare("INSERT INTO sessions(token, user_id) VALUES (?, ?)");
    $stmt->bind_param("ss", $token, $user_id);

    // Execute
    $stmt->execute();

    // Return Session Token
    return $token;
}

/**
 * Creates a new session for a user and returns its ID.
 */
function create_relationship($from, $to) 
{
    # Prepare server-generated info
    $id = uuidv4();

    // Establish DB connection and prepare a statement
    $sql = get_connection();
    $stmt = $sql->prepare("INSERT INTO relationships(id, sender, recipient, friends, blocked) VALUES (?, ?, ?, 1, 0)");
    $stmt->bind_param("sss", $id, $from, $to);

    // Execute
    $stmt->execute();

    // Return Relationship ID
    return $id;
}

/**
 * Creates a new message between two users and returns its ID.
 */
function create_message($relationship_id, $is_reply, $content)
{
    # Prepare server-generated info
    $id = uuidv4();
    $timestamp = floor(microtime(true) * 1000);

    // Establish DB connection and prepare a statement
    $sql = get_connection();
    $stmt = $sql->prepare("INSERT INTO messages(id, content, relationship, is_reply, timestamp) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("sssii", $id, $content, $relationship_id, $is_reply, $timestamp);

    // Execute
    $stmt->execute();

    // Return Message ID
    return $id;
}

/**
 * Gets a user from the database by their User ID.
 */
function get_user_by_id($user_id) 
{
    // Establish DB connection and prepare a statement
    $sql = get_connection();
    $stmt = $sql->prepare("SELECT * FROM users WHERE id=?");
    $stmt->bind_param("s", $user_id);

    // Execute
    $stmt->execute();
    $res = $stmt->get_result();
    
    // Return Output
    if($res->num_rows > 0) {
        return $res->fetch_assoc();
    }

    // User Not Found
    return false;
}

/**
 * Gets a user from the database by their Username.
 */
function get_user_by_username($username) 
{
    // Establish DB connection and prepare a statement
    $sql = get_connection();
    $stmt = $sql->prepare("SELECT * FROM users WHERE username=?");
    $stmt->bind_param("s", $username);

    // Execute
    $stmt->execute();
    $res = $stmt->get_result();

    // Return Output
    if($res->num_rows > 0) {
        return $res->fetch_assoc();
    }

    // User Not Found
    return false;
}

/**
 * Gets a user from the database by their Email Address.
 */
function get_user_by_email($email) 
{
    // Establish DB connection and prepare a statement
    $sql = get_connection();
    $stmt = $sql->prepare("SELECT * FROM users WHERE email=?");
    $stmt->bind_param("s", $email);

    // Execute
    $stmt->execute();
    $res = $stmt->get_result();

    // Return Output
    if($res->num_rows > 0) {
        return $res->fetch_assoc();
    }

    // User Not Found
    return false;
}

/**
 * Gets a user from the database by a session.
 */
function get_user_by_session($token) 
{
    // Establish DB connection and prepare a statement
    $sql = get_connection();
    $stmt = $sql->prepare("SELECT users.* FROM sessions INNER JOIN users ON sessions.user_id = users.id WHERE sessions.token = ?");
    $stmt->bind_param("s", $token);

    // Execute
    $stmt->execute();
    $res = $stmt->get_result();

    // Return Output
    if($res->num_rows > 0) {
        return $res->fetch_assoc();
    }

    // User Not Found
    return false;
}

/**
 * Gets a relationship from the databae by its ID.
 */
function get_relationship_by_id($id) 
{
    // Establish DB connection and prepare a statement
    $sql = get_connection();
    $stmt = $sql->prepare("SELECT relationships.*, sender.username AS sender_username, recipient.username AS recipient_username FROM relationships 
        INNER JOIN users sender ON relationships.sender = sender.id 
        INNER JOIN users recipient on relationships.recipient = recipient.id 
        WHERE relationships.id = ?");
    $stmt->bind_param("s", $id);

    // Execute
    $stmt->execute();
    $res = $stmt->get_result();

    // Return Output
    if($res->num_rows > 0) {
        return $res->fetch_assoc();
    }

    // Relationship Not Found
    return false;
}

/**
 * Gets a list of relationships involving a certain user.
 */
function get_relationships_by_from($from) 
{
    // Establish DB connection and prepare a statement
    $sql = get_connection();
    $stmt = $sql->prepare("SELECT relationships.*, sender.username AS sender_username, recipient.username AS recipient_username FROM relationships 
        INNER JOIN users sender ON relationships.sender = sender.id 
        INNER JOIN users recipient on relationships.recipient = recipient.id 
        WHERE sender = ? OR recipient = ?");
    $stmt->bind_param("ss", $from, $from);

    // Execute
    $stmt->execute();
    $res = $stmt->get_result();

    // Return Output
    if($res->num_rows > 0) 
    {
        $rows = array();
        while($row = $res->fetch_assoc()) 
        {
            $rows[] = $row;
        }
        return $rows;
    }

    // Relationships Not Found
    return null;
}

/**
 * Gets a list of relationships involving a certain user.
 */
function get_messages_by_relationship($relationship_id) 
{
    // Establish DB connection and prepare a statement
    $sql = get_connection();
    $stmt = $sql->prepare("SELECT messages.*, relationships.friends, relationships.blocked, relationships.sender, relationships.recipient, sender.username AS sender_username, recipient.username AS recipient_username FROM messages
        INNER JOIN relationships ON messages.relationship = relationships.id
        INNER JOIN users sender ON relationships.sender = sender.id
        INNER JOIN users recipient ON relationships.recipient = recipient.id
        WHERE relationships.id = ?
        ORDER BY messages.timestamp;");
    $stmt->bind_param("s", $relationship_id);

    // Execute
    $stmt->execute();
    $res = $stmt->get_result();

    // Return Output
    if($res->num_rows > 0) 
    {
        $rows = array();
        while($row = $res->fetch_assoc()) 
        {
            $rows[] = $row;
        }
        return $rows;
    }

    // Messages Not Found
    return null;
}

/**
 * Creates and returns a connection to the MySQL server.
 */
function get_connection(): mysqli 
{
    $username = "root";
    $password = "password";
    $dbname = "chatcafe";
    $conn = new mysqli("127.0.0.1", $username, $password, $dbname);

    // Ensure connection is healty
    if ($conn->connect_error) {
        die("Failed to connect to the database: " . $conn->connect_error);
    } 

    return $conn;
}

/**
 * Generates a unique identifier.
 * With help from https://stackoverflow.com/questions/2040240/php-function-to-generate-v4-uuid
 */
function uuidv4() 
{
    $data = random_bytes(16);

    $data[6] = chr(ord($data[6]) & 0x0f | 0x40); // set version to 0100
    $data[8] = chr(ord($data[8]) & 0x3f | 0x80); // set bits 6-7 to 10
    
    return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
}

?>