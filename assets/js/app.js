const DEFAULT_TAB = "chat";

window.chatcafe = {
    currentTab: DEFAULT_TAB,

    // Functions
    setTab: function(tab) {
        this.setTabVisible(this.currentTab, false);
        this.setTabVisible(tab, true);
        this.currentTab = tab;
    },
    setTabVisible: function(tab, visible) {
        const screenElement = document.querySelector(`.tab.${tab}`);
        const buttonElement = document.querySelector(`.nav #${tab}`);

        if(visible === true) {
            screenElement.classList.add("active");
            buttonElement.classList.add("active");
        }
        else {
            screenElement.classList.remove("active");
            buttonElement.classList.remove("active");
        }
    },

    // Chat
    chat: {

        // Gets the currently active conversation.
        getCurrentConversation() {
            const urlParams = new URLSearchParams(window.location.search);
            const convId = urlParams.get('c');
            return convId;
        },

        // Adds a new message to the active conversation.
        addMessage: function(author, content) {
            const body = document.querySelector('.chat .body');
            const isScrolledDown = Math.abs(body.scrollHeight - body.scrollTop - body.clientHeight) < 1;

            // Container
            const div = document.createElement("div");
            div.classList.add("message");

            // Avatar
            const avatar = document.createElement("div");
            const icon = document.createElement("img");
            icon.src = "assets/img/user-solid.svg";
            avatar.classList.add("avatar");
            avatar.appendChild(icon);
            div.appendChild(avatar);

            // Message author and content
            const usernameAndMessage = document.createElement("div");
            const username = document.createElement("b");
            const message = document.createElement("p");
            username.innerHTML = author; // Susceptible to XSS but only on the client side so not an issue
            message.innerHTML = content; // Susceptible to XSS but only on the client side so not an issue
            usernameAndMessage.appendChild(username);
            usernameAndMessage.appendChild(message);
            div.appendChild(usernameAndMessage);

            // Add fully constructed message to view
            body.appendChild(div);

            // If we were scrolled all the way down, then force scroll down again to see new message
            if(isScrolledDown) {
                div.scrollIntoView();
            }
        },

        // Adds a new conversation to the conversation list.
        addConversation: function(relationship) {
            const body = document.querySelector('.chat .conversation-list');

            // Container
            const div = document.createElement("div");
            div.classList.add("conversation");
            div.addEventListener('click', () => {
                window.location = `?c=${relationship.id}`;
            });

            // Avatar
            const avatar = document.createElement("div");
            const icon = document.createElement("img");
            icon.src = "assets/img/user-solid.svg";
            avatar.classList.add("avatar");
            avatar.appendChild(icon);
            div.appendChild(avatar);

            // Username and subtitle
            const usernameAndMessage = document.createElement("div");
            const username = document.createElement("b");
            const message = document.createElement("p");
            username.innerHTML = relationship.username;
            message.innerHTML = "Click to chat";
            usernameAndMessage.appendChild(username);
            usernameAndMessage.appendChild(message);
            usernameAndMessage.classList.add("vertical");
            div.appendChild(usernameAndMessage);

            // Add fully constructed conversation switcher to view
            body.appendChild(div);
        },
        
        // Sends a message to the currently active conversation.
        sendMessage: function(content) {
            const relationship = this.getCurrentConversation();
            const socket = chatcafe.gateway.socket;

            // Send event to gateway. The gateway will echo this back to us, at which point 
            // the message will be added to the chat view for us
            socket.send(JSON.stringify({
                op: 1,
                payload: {
                    relationship,
                    content
                }
            }));
        },

        // Adds a new relationship.
        addFriend: function(username) {
            const socket = chatcafe.gateway.socket;

            // Send event to gateway. The gateway will send us the result of this action,
            // so there's no need to do anything from here on out.
            socket.send(JSON.stringify({
                op: 5,
                payload: username
            }));
        },

        // Reads what the user currently has written in the friend finder and sends a friend request.
        submitFriend: function() {
            const input = document.querySelector(".friend-finder input");
            const value = input.value;

            // Is the input valid?
            if(value.length === 0) {
                chatcafe.modal.show("Username must not be empty!");
                return;
            }

            input.value = "";
            this.addFriend(value);
        },

        // Reads what the user currently has written in the chatbox and sends it.
        submitCurrentMessage: function() {
            const input = document.querySelector("#message-field");
            const msg = input.value;

            // Is the input valid?
            if(msg.length === 0) {
                chatcafe.modal.show("You must write a message to send!");
                return;
            }

            input.value = "";
            this.sendMessage(msg);
        }
    },

    // Gateway
    gateway: {
        socket: null,

        // Connects to the gateway.
        connect: function() {
            this.socket = new WebSocket("ws://localhost:8080");

            // Authenticate when connection is opened
            const token = getCookie("CHATCAFE_SESSION");
            this.socket.addEventListener("open", (event) => {
                console.log("[Gateway] authenticating");
                this.socket.send(token);
            });

            // Listen for events
            this.socket.addEventListener("message", (event) => {
                const json = JSON.parse(event.data);
                const op = json.op;
                const payload = json.payload;

                switch(op) {
                    // OP_DISCONNECT
                    case 0:
                        const msg = `Connection to the Gateway was lost: ${payload}`;
                        chatcafe.modal.show(msg);
                        break;
                    
                    // OP_NEWMESSAGE
                    case 2:
                        const relationship = payload.relationship;
                        const author_name = payload.is_reply ? relationship.recipient_username : relationship.sender_username;

                        // If the relationship for this message is not currently in focus then discard the event
                        if(relationship.id != chatcafe.chat.getCurrentConversation()) {
                            return;
                        }

                        chatcafe.chat.addMessage(author_name, payload.content);
                        break;
                    
                    // OP_NEWRELATIONSHIP
                    case 3:
                        chatcafe.chat.addConversation(payload);
                        break;

                    // OP_MODAL
                    case 4:
                        chatcafe.modal.show(payload);
                        break;
                }
            });
        }
    },

    // Modal
    modal: {

        // Show a modal with a message.
        show: function(message) {
            const modal = document.querySelector(".modal-container");
            const content = document.querySelector(".modal-container #msg");
            content.innerHTML = message;
            modal.classList.add("visible");
        },

        // Hide the modal.
        hide: function() {
            const modal = document.querySelector(".modal-container");
            modal.classList.remove("visible");
        }
    }
};

// Utility function to get the value of a cookie.
function getCookie(name) {
    var match = document.cookie.match(new RegExp('(^| )' + name + '=([^;]+)'));
    if (match) {
        return match[2];
    }
}

// Connect to Gateway on page load
chatcafe.gateway.connect();