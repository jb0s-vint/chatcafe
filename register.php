<?php
include 'api/database_manager.php';

$username_err = "";
$email_err = "";
$password_err = "";
$username = "";
$email = "";

/**
 * Handles register POST requests
 */
if ($_SERVER["REQUEST_METHOD"] == "POST") 
{    
    $errored = false;

    // Ensure that email is not empty
    if(empty($_POST["text"])) 
    {
        $username_err = "You must enter a username.";
        $errored = true;
    }

    // Ensure that username doesn't exceed length limit
    if(strlen($_POST["text"]) > 25)
    {
        $username_err = "Username cannot exceed 25 characters";
        $errored = true;
    }

    // Ensure that email is not empty
    if(empty($_POST["email"])) 
    {
        $email_err = "You must enter an email address.";
        $errored = true;
    }

    // Ensure that email doesn't exceed length limit
    if(strlen($_POST["email"]) > 50)
    {
        $email_err = "Email cannot exceed 50 characters";
        $errored = true;
    }

    // Ensure that password is not empty
    if(empty($_POST["password"])) 
    {
        $password_err = "You must enter a password.";
        $errored = true;
    }

    // Ensure that no user with this email address already exists
    if(get_user_by_email($_POST["email"]))
    {
        $email_err = "An account with this email already exists";
        $errored = true;
    }

    // Ensure that no user with this username already exists
    if(get_user_by_username($_POST["text"]))
    {
        $username_err = "An account with this username already exists";
        $errored = true;
    }

    // Did an error occur?
    if($errored) 
    {
        // Remember what email and username the user inputted
        $username = $_POST["text"];
        $email = $_POST["email"];
    }
    else 
    {
        // All clear, create the user
        create_user($_POST["email"], $_POST["text"], $_POST["password"]);

        // Redirect user to /login
        header("Location: login.php");
    }
}
?>

<!DOCTYPE html>
<html>
    <head>
        <title>Register - ChatCafe</title>
        <link rel="stylesheet" href="assets/css/reset.css"/>
        <link rel="stylesheet" href="assets/css/core.css"/>
        <link rel="stylesheet" href="assets/css/login.css"/>
    </head>
    <body>
        <div class="modal login">
            <h1>Create a ChatCafe account</h1>
            <h3>Don't share your account info!</h3>

            <!-- Register Form -->
            <form action="register.php" method="post">
                <?php input_field_with_value("Username", "text", $username, $username_err) ?>
                <?php input_field_with_value("Email Address", "email", $email, $email_err) ?>
                <?php input_field("Password", "password", $password_err) ?>

                <div class="action-bar">
                    <a href="login.php">Use existing account</a>
                    <button type="submit">Create account</button>
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
function input_field($name, $id, $err) {
    input_field_with_value($name, $id, "", $err);
}

/**
 * Generic input field with value.
 * Useful if you want the user's value to be retained after a form submission.
 */
function input_field_with_value($name, $id, $default_value, $err) {
    if($err != "") {
        // An error occurred - append the error message to the label
        echo "<label for='$id'>$name - <i style='color: red'>$err</i></label>";
    }
    else {
        // No error, just return the label alone
        echo "<label for='$id'>$name</label>";
    }

    // Add input below the label
    echo "<input name='$id' id='$id' type='$id' value='$default_value'>";
}
?>