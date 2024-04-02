<?php
include 'api/database_manager.php';

$email_err = "";
$password_err = "";
$email = "";

/**
 * Handles login POST requests
 */
if ($_SERVER["REQUEST_METHOD"] == "POST")
{
    // Ensure that user has input valid data into the form
    if(validate_form()) 
    {
        // Get user with specified email from database
        $user = get_user_by_email($_POST["email"]);
        
        // Did we find the user?
        if($user) 
        {
            // Found user, validate credentials
            $pw_hash = $user["password"];
            $pw_posted = $_POST["password"];
            
            // Are the credentials correct?
            if(password_verify($pw_posted, $pw_hash)) 
            {
                // Password is correct! Let user in
                finalize_login($user["id"]);
            }
            else 
            {
                // Password is incorrect
                invalid_creds();
            }
        }
        else 
        {
            // User not found
            invalid_creds();
        }
    }
}

/**
 * Finalizes the login process and advances to the app.
 */
function finalize_login($user_id) 
{
    // Create a session
    $token = create_session($user_id);

    // Add the session token as a cookie
    setcookie("CHATCAFE_SESSION", $token);

    // Redirect user to /app
    header("Location: app.php");
    return;
}

/**
 * Simple utility function that tells the user they input invalid credentials.
 */
function invalid_creds() 
{
    global $email_err, $password_err;
    global $email;

    $email_err = $password_err = "Invalid email or password.";
    $email = $_POST["email"];
}

/**
 * Validates the user input and applies errors where appropriate. 
 * Returns true if input is valid.
 */
function validate_form() 
{
    global $email_err, $password_err;
    global $email;

    // Ensure that email is not empty
    if(empty($_POST["email"])) 
    {
        $email_err = "You must enter an email address.";
        return false;
    }

    // Ensure that password is not empty
    if(empty($_POST["password"])) 
    {
        $password_err = "You must enter a password.";

        // Remember what email the user inputted
        $email = $_POST["email"];

        return false;
    }

    return true;
}

?>

<!DOCTYPE html>
<html>
    <head>
        <title>Login - ChatCafe</title>
        <link rel="stylesheet" href="assets/css/reset.css"/>
        <link rel="stylesheet" href="assets/css/core.css"/>
        <link rel="stylesheet" href="assets/css/login.css"/>
    </head>
    <body>
        <div class="modal login">
            <h1>Welcome to ChatCafe</h1>
            <h3>Please sign in with your account</h3>

            <!-- Login Form -->
            <form action="login.php" method="post">
                <?php input_field_with_value("Email Address", "email", $email, $email_err) ?>
                <?php input_field("Password", "password", $password_err) ?>

                <div class="action-bar">
                    <a href="register.php">Create an account</a>
                    <button type="submit">Log in</button>
                </div>
            </form>
        </div>
    </body>
</html>

<?php
/**
 * Generic input field with no value.
 * This just calls the input_field_with_value function with an empty value.
 */
function input_field($name, $id, $err) 
{
    input_field_with_value($name, $id, "", $err);
}

/**
 * Generic input field with value.
 * Useful if you want the user's value to be retained after a form submission.
 */
function input_field_with_value($name, $id, $default_value, $err) 
{
    if($err != "") 
    {
        // An error occurred - append the error message to the label
        echo "<label for='$id'>$name - <i style='color: red'>$err</i></label>";
    }
    else 
    {
        // No error, just return the label alone
        echo "<label for='$id'>$name</label>";
    }

    // Add input below the label
    echo "<input name='$id' id='$id' type='$id' value='$default_value'>";
}
?>