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

    function fetchMessages() {
        fetch(`get_messages.php?room_id=${roomId}`)
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    const isScrolledToBottom = chatBox.scrollHeight - chatBox.clientHeight <= chatBox.scrollTop + 1;
                    chatBox.innerHTML = '';
                    if (data.messages.length === 0) {
                        chatBox.innerHTML = '<p style="color: var(--white-dark); font-style: italic;">No messages yet.</p>';
                    } else {
                        data.messages.forEach(msg => {
                            const msgDiv = document.createElement('div');
                            msgDiv.style.marginBottom = '0.5rem';
                            msgDiv.innerHTML = `<strong style="color: var(--red);">${msg.username}:</strong> <span style="color: var(--white);">${msg.message}</span>`;
                            chatBox.appendChild(msgDiv);
                        });
                    }
                    if (isScrolledToBottom) {
                        chatBox.scrollTop = chatBox.scrollHeight;
                    }
                }
            });
    }

    function fetchRoomStatus() {
        fetch(`get_room_status.php?room_id=${roomId}`)
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    playerCount.innerText = `${data.current_players} / ${data.max_players}`;
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
            });
    }

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
                    fetchMessages();
                }
            });
        }
    });

    setInterval(fetchMessages, 2000);
    setInterval(fetchRoomStatus, 3000);
    fetchMessages();
    fetchRoomStatus();
</script>

<?php require_once "includes/footer.php"; ?>
