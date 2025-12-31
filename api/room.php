<?php
require_once "includes/header.php";
require_once "includes/config.php";

// Check if the user is logged in
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    echo "<script>window.location.href='login.php';</script>";
    exit;
}

// Check if room ID is provided
if(isset($_GET["id"]) && !empty(trim($_GET["id"]))){
    $room_id = (int)trim($_GET["id"]);
    
    // Fetch room details
    $sql = "SELECT * FROM rooms WHERE id = $room_id";
    $result = mysqli_query($link, $sql);
    
    if($result && mysqli_num_rows($result) == 1){
        $row = mysqli_fetch_array($result, MYSQLI_ASSOC);
        $room_name = $row["room_name"];
        $room_code = $row["room_code"];
        $current_players = $row["current_players"];
        $max_players = $row["max_players"];
        $status = $row["status"];
    } else{
        echo "<script>window.location.href='game_room.php';</script>";
        exit;
    }
} else{
    echo "<script>window.location.href='game_room.php';</script>";
    exit;
}
?>

<div class="container" style="padding-top: 100px; padding-bottom: 50px;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
        <div>
            <h2 class="section-title" style="margin-bottom: 0.5rem;"><?php echo htmlspecialchars($room_name); ?></h2>
            <div style="display: flex; align-items: center; gap: 1rem;">
                <span style="color: var(--white-dark);">Room Code:</span>
                <span style="font-size: 1.5rem; color: var(--red); font-weight: bold; letter-spacing: 2px; background: rgba(255, 255, 255, 0.1); padding: 0.2rem 1rem; border-radius: 5px;"><?php echo htmlspecialchars($room_code); ?></span>
            </div>
        </div>
        <div style="display: inline-block; padding: 0.5rem 1.5rem; background: var(--black-light); border: 1px solid var(--red); border-radius: 20px;">
            <span style="color: var(--white-dark);">Players: </span>
            <span id="player-count" style="color: var(--white); font-weight: bold;"><?php echo $current_players; ?> / <?php echo $max_players; ?></span>
        </div>
    </div>

    <!-- Start Game Button for Creator -->
    <div id="creator-controls" style="display: none; margin-bottom: 2rem; text-align: center; padding: 1.5rem; background: rgba(255, 255, 255, 0.05); border-radius: 10px; border: 1px dashed var(--red);">
        <p style="color: var(--white-dark); margin-bottom: 1rem;">You are the room creator. When enough players have joined, you can start the game.</p>
        <button id="start-game-btn" class="cta-button" style="min-width: 200px;">Start Game</button>
        <p id="start-error" style="color: var(--red); margin-top: 10px; font-size: 0.9rem; display: none;"></p>
    </div>

    <!-- Game Started Notification -->
    <div id="game-status-notification" style="display: none; margin-bottom: 2rem; text-align: center; padding: 1rem; background: var(--red); color: white; border-radius: 10px; font-weight: bold; text-transform: uppercase; letter-spacing: 2px;">
        The Game has Started!
    </div>

    <div class="room-container" style="display: flex; gap: 2rem; align-items: flex-start;">
        <!-- Left Side: Chat Room -->
        <div class="role-card chat-section" style="flex: 2; height: 500px; display: flex; flex-direction: column; text-align: left; min-width: 0;">
            <h3 style="border-bottom: 1px solid var(--red); padding-bottom: 1rem; margin-bottom: 1rem;">Room Chat</h3>
            <div id="chat-box" style="flex-grow: 1; overflow-y: auto; padding: 1rem; background: rgba(0,0,0,0.3); border-radius: 10px; margin-bottom: 1rem;">
                <p style="color: var(--white-dark); font-style: italic;">Loading messages...</p>
            </div>
            <form id="chat-form" style="display: flex; gap: 10px;">
                <input type="hidden" id="room_id" value="<?php echo $room_id; ?>">
                <input type="text" id="message-input" class="form-control" placeholder="Type a message..." style="margin-bottom: 0;" autocomplete="off">
                <button type="submit" class="cta-button" style="padding: 0.5rem 1.5rem; font-size: 1rem;">Send</button>
            </form>
        </div>

        <!-- Right Side: Player List -->
        <div class="role-card" style="flex: 1; min-width: 250px; text-align: left;">
            <h3 style="border-bottom: 1px solid var(--red); padding-bottom: 1rem; margin-bottom: 1rem;">Players</h3>
            <div id="player-list" style="display: flex; flex-direction: column; gap: 10px;">
                <!-- Players will be loaded here -->
                <p style="color: var(--white-dark);">Loading players...</p>
            </div>
        </div>
    </div>
</div>

<script>
    const chatBox = document.getElementById('chat-box');
    const chatForm = document.getElementById('chat-form');
    const messageInput = document.getElementById('message-input');
    const roomId = document.getElementById('room_id').value;
    const playerList = document.getElementById('player-list');
    const playerCount = document.getElementById('player-count');
    const creatorControls = document.getElementById('creator-controls');
    const startGameBtn = document.getElementById('start-game-btn');
    const gameNotification = document.getElementById('game-status-notification');
    const startError = document.getElementById('start-error');

    let lastMessageId = 0;

    function syncRoom() {
        fetch(`sync_room.php?room_id=${roomId}&last_id=${lastMessageId}`)
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    // Update Room Status & Creator Controls
                    if (data.room) {
                        playerCount.innerText = `${data.room.current_players} / ${data.room.max_players}`;
                        
                        if (data.room.status === 'in_progress') {
                            gameNotification.style.display = 'block';
                            creatorControls.style.display = 'none';
                        } else if (data.room.creator_id === data.room.current_user_id) {
                            creatorControls.style.display = 'block';
                            if (data.room.current_players < 2) {
                                startGameBtn.disabled = true;
                                startGameBtn.style.opacity = '0.5';
                            } else {
                                startGameBtn.disabled = false;
                                startGameBtn.style.opacity = '1';
                            }
                        }
                    }

                    // Update Player List
                    if (data.players) {
                        playerList.innerHTML = '';
                        data.players.forEach(username => {
                            const p = document.createElement('div');
                            p.style.padding = '10px';
                            p.style.background = 'rgba(255,255,255,0.05)';
                            p.style.borderRadius = '5px';
                            p.style.color = 'var(--white)';
                            p.innerText = username;
                            playerList.appendChild(p);
                        });
                    }

                    // Append New Messages
                    if (data.messages && data.messages.length > 0) {
                        // Remove "Loading..." or "No messages yet" if they exist
                        if (lastMessageId === 0) chatBox.innerHTML = '';

                        const isScrolledToBottom = chatBox.scrollHeight - chatBox.clientHeight <= chatBox.scrollTop + 1;
                        
                        data.messages.forEach(msg => {
                            const msgDiv = document.createElement('div');
                            msgDiv.style.marginBottom = '0.5rem';
                            msgDiv.innerHTML = `<span style="color: var(--white-dark); font-size: 0.8rem;">[${msg.time}]</span> <strong style="color: var(--red);">${msg.username}:</strong> <span style="color: var(--white);">${msg.message}</span>`;
                            chatBox.appendChild(msgDiv);
                            lastMessageId = msg.id;
                        });

                        if (isScrolledToBottom) {
                            chatBox.scrollTop = chatBox.scrollHeight;
                        }
                    } else if (lastMessageId === 0) {
                        chatBox.innerHTML = '<p style="color: var(--white-dark); font-style: italic;">No messages yet.</p>';
                    }
                }
            })
            .catch(err => console.error('Sync error:', err));
    }

    startGameBtn.addEventListener('click', function() {
        const formData = new FormData();
        formData.append('room_id', roomId);

        fetch('start_game.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                startError.style.display = 'none';
                syncRoom();
            } else {
                startError.innerText = data.message;
                startError.style.display = 'block';
            }
        });
    });

    chatForm.addEventListener('submit', function(e) {
        e.preventDefault();
        const message = messageInput.value.trim();
        if (message) {
            const formData = new FormData();
            formData.append('room_id', roomId);
            formData.append('message', message);

            fetch('send_message.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    messageInput.value = '';
                    syncRoom();
                }
            });
        }
    });

    // Sync every 2 seconds
    setInterval(syncRoom, 2000);
    syncRoom();
</script>

<?php require_once "includes/footer.php"; ?>
