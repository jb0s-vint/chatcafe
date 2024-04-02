<?php
namespace ChatCafe;

include "database_manager.php";

use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;

class Gateway implements MessageComponentInterface 
{
    private $clients;

    /**
     * Opcodes
     */
    private $OP_S2C_DISCONNECT = 0;
    private $OP_C2S_SENDMESSAGE = 1;
    private $OP_S2C_NEWMESSAGE = 2;
    private $OP_S2C_NEWRELATIONSHIP = 3;
    private $OP_S2C_MODAL = 4;
    private $OP_C2S_ADDFRIEND = 5;

    public function __construct() 
    {
        $this->clients = new \SplObjectStorage;
    }
    
    /**
     * Handle a new incoming connection
     */
    public function onOpen(ConnectionInterface $conn) 
    {
        // Store the new connection to send messages to
        $this->clients->attach($conn);
        $conn->identified = false;
        
        echo "Incoming connection {$conn->resourceId}\n";
    }

    /**
     * Handle messages from existing clients
     * Make sure that they properly authenticate themselves before subscribing to events
     */
    public function onMessage(ConnectionInterface $from, $msg) 
    {
        if(!$from->identified)
        {
            // Try authenticate
            $lookup = get_user_by_session($msg);
            if(!$lookup)
            {
                $this->disconnect_err($from, "Invalid authentication token");
                return;
            }

            // Mark connection as authenticated
            $from->user = $lookup;
            $from->identified = true;

            echo "Connection {$from->resourceId} has identified as {$from->user['username']} ({$from->user['id']})\n";
            return;
        }

        $payload = json_decode($msg);
        if(!$payload || !$payload->op || !$payload->payload)
        {
            $this->disconnect_err($from, "Malformed payload");
            return;
        }

        switch($payload->op)
        {
            case $this->OP_C2S_SENDMESSAGE:
                $this->handle_op_sendmessage($from, $payload->payload);
                break;

            case $this->OP_C2S_ADDFRIEND:
                $this->handle_op_addfriend($from, $payload->payload);
                break;
        }
    }

    /**
     * Handle a closing connection
     */
    public function onClose(ConnectionInterface $conn) 
    {
        // The connection is closed, remove it, as we can no longer send it messages
        $this->clients->detach($conn);
        echo "Connection {$conn->resourceId} has disconnected\n";
    }

    /**
     * Handle a connection error
     */
    public function onError(ConnectionInterface $conn, \Exception $e) 
    {
        echo "An error has occurred: {$e->getMessage()}\n";
        $conn->close();
    }

    /**
     * Handle a SendMessage opcode from a client.
     */
    private function handle_op_sendmessage($from, $payload)
    {
        // Ensure the necessary fields are populated
        $content_is_empty = empty($payload->content);
        $relationship_is_empty = empty($payload->relationship);
        if($content_is_empty || $relationship_is_empty)
        {
            $this->disconnect_err($from, "Malformed payload");
            return;
        }

        // Get user and relationship info
        $relationship = get_relationship_by_id($payload->relationship);
        if(!$relationship)
        {
            $this->disconnect_err($from, "User or relationship not found");
            return;
        }

        // Ensure that the user is not irrelevant to the provided relationship
        if($relationship['recipient'] != $from->user['id'] && $relationship['sender'] != $from->user['id'])
        {
            $this->disconnect_err($from, "This relationship is not yours");
            return;
        }

        // Create Message and Save To Database
        $is_reply = $relationship['recipient'] == $from->user['id'];
        create_message($relationship['id'], $is_reply, $payload->content);

        // Construct event data
        $event_data = (object)[];
        $event_data->relationship = $relationship;
        $event_data->content = htmlspecialchars($payload->content);
        $event_data->is_reply = $is_reply;

        // Dispatch message event to clients
        $this->dispatch_by_user_id($relationship['sender'], $this->OP_S2C_NEWMESSAGE, $event_data);
        $this->dispatch_by_user_id($relationship['recipient'], $this->OP_S2C_NEWMESSAGE, $event_data);
    }

    /**
     * Handle a AddFriend opcode from a client.
     */
    private function handle_op_addfriend($from, $payload)
    {
        $user = get_user_by_username($payload);

        // Did we not find the user?
        if(!$user)
        {
            $this->send_modal($from, "The user you're trying to befriend was not found.");
            return;
        }

        // Create new relationship
        $id = create_relationship($from->user['id'], $user['id']);
        $relationship = get_relationship_by_id($id);

        // Create event data
        $event = (object)[];
        $event->id = $relationship['id'];
        $event->username = $user['username'];

        // Dispatch NewRelationship event to sender
        $this->dispatch_by_user_id($from->user['id'], $this->OP_S2C_NEWRELATIONSHIP, $event);

        // Dispatch NewRelationship event to recipient
        $event->username = $from->user['username'];
        $this->dispatch_by_user_id($user['id'], $this->OP_S2C_NEWRELATIONSHIP, $event);
    }

    /**
     * Dispatch a payload to all connected clients authenticated as a specific user.
     */
    private function dispatch_by_user_id($user_id, $op, $payload)
    {
        $msg = (object)[];
        $msg->op = $op;
        $msg->payload = $payload;
        $enc_msg = json_encode($msg);

        foreach($this->clients as $client)
        {
            // Ensure client is identified and authenticated
            if(!$client->identified) continue;

            // Is this client a user we're looking for?
            if($client->user['id'] == $user_id)
            {
                $client->send($enc_msg);
            }
        }
    }

    /**
     * Send a modal message to a client.
     */
    private function send_modal($client, $text)
    {
        $msg = (object)[];
        $msg->op = $this->OP_S2C_MODAL;
        $msg->payload = $text;
        $enc_msg = json_encode($msg);
        $client->send($enc_msg);
    }

    /**
     * Send an error payload to a client followed by a disconnect.
     */
    private function disconnect_err($client, $err)
    {
        $msg = (object)[];
        $msg->op = $this->OP_S2C_DISCONNECT;
        $msg->payload = $err;
        $enc_msg = json_encode($msg);
        $client->send($enc_msg);
        $client->close();
    }
}

?>