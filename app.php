<?php
include 'api/database_manager.php';

// Get user data from session token in cookie
$user = get_user_by_session($_COOKIE["CHATCAFE_SESSION"]);

// Ensure that the user is authenticated
if($user == null) 
{
    // Redirect to login
    header("Location: login.php");
    exit;
}

// Get user relationships
$relationships = get_relationships_by_from($user['id']);
$current_conversation = null;

// Determine what relationship is currently focused
if(isset($_GET["c"])) 
{
    foreach($relationships as $rel)
    {
        // Is this the relationship we're looking for?
        if($rel['id'] == $_GET['c'])
        {
            $current_conversation = $rel;
        }
    }
}

?>

<!DOCTYPE html>
<html>
    <head>
        <title><?php echo $user["username"]?> - ChatCafe</title>
        <link rel="stylesheet" href="assets/css/reset.css"/>
        <link rel="stylesheet" href="assets/css/core.css"/>
        <link rel="stylesheet" href="assets/css/app.css"/>

        <!-- Client-side Javascript -->
        <script src="assets/js/app.js"></script>
    </head>
    <body>
        <!-- Navbar -->
        <div class="sidebar">
            <div class="logo">
                <img src="assets/img/mug-hot-solid.svg" alt="ChatCafe icon"/>
            </div>
            <div class="nav">
                <button id="chat" class="active" onclick="chatcafe.setTab('chat')">
                    <img src="assets/img/comment-solid.svg" class="invert" alt="Comment icon"/>
                </button>
                <button id="info" onclick="chatcafe.setTab('info')">
                    <img src="assets/img/info-solid.svg" class="invert" alt="Info icon"/>
                </button>
            </div>
            <?php user_avatar(); ?>
        </div>

        <!-- Main page contents -->
        <div class="main">
            <!-- Chat Tab -->
            <div class="tab chat active">
                <?php conversation_list(); ?>
                <?php chat_view(); ?>
            </div>

            <!-- Info Tab -->
            <div class="tab info">
                <div class="section modal">
                    <h1>ChatCafe by Jake v/d Bos</h1>
                    <h3>Dit gedeelte is in het Nederlands geschreven voor makkelijkheid.</h3>
                </div>
                <ul class="section modal">
                    <li>Dit is een hele simpele chat app gemaakt helemaal door Jake in PHP met MySQL voor database.</li>
                    <li>Ik had gehoopt dat ik dit zou kunnen behalen zonder JavaScript, maar helaas moest ik een klein beetje JavaScript gebruiken op de client gesynchroniseerd te houden met andere chatters.</li>
                    <li>De WebSocket die hiervoor wordt gebruikt is uiteraard wel geschreven in PHP.</li>
                </ul>
                <ul class="section modal">
                    <li>De database doet aan de eisen die mij zijn gesteld: een meer-op-meer tabel relatie.</li>
                    <li>Elk bericht dat wordt opgeslagen wijst naar een "relationship", die wijst naar twee "users".</li>
                    <img src="assets/img/database-graph.png" alt="Database graph"/>
                </ul>
                <ul class="section modal">
                    <li>Dit project bleek veel meer werk te zijn dan ik had gedacht, maar het is mij mooi gelukt.</li>
                </ul>
            </div>
        </div>

        <!-- Modal overlay -->
        <div class="modal-container">
            <div class="modal">
                <h2><img src="assets/img/circle-info-solid.svg" class="invert" alt="Info icon"/> Info</h2>
                <p id="msg">[message contents]</p>
                <div class="action-bar">
                    <button onclick="chatcafe.modal.hide()">OK</button>
                </div>
            </div>
        </div>
    </body>
</html>

<?php

function get_other_name_from_relationship($relationship)
{
    global $user;
    $relationship_is_towards_me = $relationship['recipient'] == $user['id'];

    // If the relationship is towards me, then the other person's name is in sender_username,
    // rather than recipient_username. There's probably a neater way to write this but too bad!
    if($relationship_is_towards_me) 
    {
        return $relationship['sender_username'];   
    }

    return $relationship['recipient_username'];
}

function user_avatar()
{
    echo "<div class='avatar'>";
    echo "    <img src='assets/img/user-solid.svg' alt='User icon'/>";
    echo "</div>";
}

function conversation_list() 
{
    global $relationships;
    
    echo "<div class='conversation-list'>";
    echo "    <div class='friend-finder'>";
    echo "        <input type='text' placeholder='Add friends...'>";
    echo "        <button onclick='chatcafe.chat.submitFriend()'><img src='assets/img/user-plus-solid.svg' class='invert' alt='User Plus icon'/></button>";
    echo "    </div>";
    if($relationships)
    {
        foreach($relationships as $rel) 
        {
            conversation_switcher($rel);
        }
    }
    echo "</div>";
}

function conversation_switcher($relationship) 
{
    $id = $relationship['id'];
    $username = get_other_name_from_relationship($relationship);

    echo "<div class='conversation' onclick='window.location = (\"/project/app.php?c=$id\")'>";
    echo      user_avatar();
    echo "    <div class='vertical'>";
    echo "        <b>$username</b>";
    echo "        <p>Click to chat</p>";
    echo "    </div>";
    echo "</div>";
}

function chat_view()
{
    global $user, $current_conversation;

    // If there is no current conversation show an empty view
    if(!$current_conversation)
    {
        echo "<div class='chat-view empty'>";
        echo "    <h1>Nothing here...</h1>";
        echo "    <h3>Select a friend in the sidebar to start chatting.</h3>";
        echo "</div>";
        return;
    }

    // Get Messages
    $recipient_name = get_other_name_from_relationship($current_conversation);
    $messages = get_messages_by_relationship($current_conversation['id']);

    // Render chat view
    echo "<div class='chat-view'>";
    echo "    <div class='header'>";
    echo          user_avatar();
    echo "        <b>$recipient_name</b>";
    echo "    </div>";
    echo "    <div class='body'>";
    echo "        <div class='opening'>";
    echo "            <h1><img src='assets/img/comments-solid.svg' class='invert' alt='Comments icon'/> $recipient_name</h1>";
    echo "            <h3>This is the beginning of your conversation with <b>$recipient_name</b>.</h3>";
    echo "        </div>";
    
    // Insert chat messages
    if($messages)
    {
        foreach($messages as $msg)
        {
            chat_view_message($msg);
        }
    }

    echo "    </div>";
    echo "    <div class='footer'>";
    echo "        <form onsubmit='chatcafe.chat.submitCurrentMessage(); return false;'>";
    echo "            <input id='message-field' type='text' placeholder='Message $recipient_name'/>";
    echo "        </form>";
    echo "        <button onclick='chatcafe.chat.submitCurrentMessage()'><img src='assets/img/paper-plane-solid.svg' class='invert' alt='Paper Plane icon'/></button>";
    echo "    </div>";
    echo "</div>";
}

function chat_view_message($message)
{
    $content = htmlspecialchars($message['content']);

    if($message['is_reply'])
    {
        $author_username = $message['recipient_username'];
    }
    else 
    {
        $author_username = $message['sender_username'];
    }

    echo "<div class='message'>";
    echo      user_avatar();
    echo "    <div class='content'>";
    echo "        <b>$author_username</b>";
    echo "        <p>$content</p>";
    echo "    </div>";
    echo "</div>";
}

?>