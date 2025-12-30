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
    $room_id = trim($_GET["id"]);
    
    // Prepare a select statement
    $sql = "SELECT * FROM rooms WHERE id = ?";
    
    if($stmt = mysqli_prepare($link, $sql)){
        mysqli_stmt_bind_param($stmt, "i", $param_id);
        $param_id = $room_id;
        
        if(mysqli_stmt_execute($stmt)){
            $result = mysqli_stmt_get_result($stmt);
            
            if(mysqli_num_rows($result) == 1){
                        $row = mysqli_fetch_array($result, MYSQLI_ASSOC);
                        $room_name = $row["room_name"];
                        $room_code = $row["room_code"];
                        $current_players = $row["current_players"];
                        $max_players = $row["max_players"];
                        $status = $row["status"];
                    } else{
                // URL doesn't contain valid id. Redirect to game room page
                echo "<script>window.location.href='game_room.php';</script>";
                exit;
            }
        } else{
            echo "Oops! Something went wrong. Please try again later.";
        }
    }
    mysqli_stmt_close($stmt);
} else{
    // URL doesn't contain id parameter. Redirect to game room page
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
            <span style="color: var(--white-dark);">Status: </span>
            <span style="color: var(--red); font-weight: bold; text-transform: uppercase;"><?php echo $status; ?></span>
            <span style="margin: 0 10px; color: var(--white-dark);">|</span>
            <span style="color: var(--white-dark);">Players: </span>
            <span style="color: var(--white); font-weight: bold;"><?php echo $current_players; ?> / <?php echo $max_players; ?></span>
        </div>
    </div>

    <div class="roles-grid" style="grid-template-columns: 3fr 1fr;">
        <!-- Game Area (Chat/Log) -->
        <div class="role-card" style="text-align: left; height: 500px; display: flex; flex-direction: column;">
            <h3 style="border-bottom: 1px solid var(--red); padding-bottom: 1rem; margin-bottom: 1rem;">Chat Room</h3>
            <div id="chat-box" style="flex-grow: 1; overflow-y: auto; padding: 1rem; background: rgba(0,0,0,0.3); border-radius: 10px; margin-bottom: 1rem;">
                <p style="color: var(--white-dark); font-style: italic;">Welcome to the room! Loading messages...</p>
            </div>
            <form id="chat-form" style="display: flex; gap: 10px;">
                <input type="hidden" id="room_id" value="<?php echo $room_id; ?>">
                <input type="text" id="message-input" class="form-control" placeholder="Type a message..." style="margin-bottom: 0;" autocomplete="off">
                <button type="submit" class="cta-button" style="padding: 0.5rem 1.5rem; font-size: 1rem;">Send</button>
            </form>
        </div>

        <script>
            const chatBox = document.getElementById('chat-box');
            const chatForm = document.getElementById('chat-form');
            const messageInput = document.getElementById('message-input');
            const roomId = document.getElementById('room_id').value;

            function fetchMessages() {
                fetch(`get_messages.php?room_id=${roomId}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.status === 'success') {
                            const isScrolledToBottom = chatBox.scrollHeight - chatBox.clientHeight <= chatBox.scrollTop + 1;
                            
                            chatBox.innerHTML = '';
                            if (data.messages.length === 0) {
                                chatBox.innerHTML = '<p style="color: var(--white-dark); font-style: italic;">No messages yet. Start the conversation!</p>';
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

            chatForm.addEventListener('submit', function(e) {
                e.preventDefault();
                const message = messageInput.value.trim();
                if (!message) return;

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
            });

            // Initial fetch and poll every 2 seconds
            fetchMessages();
            setInterval(fetchMessages, 2000);
        </script>

        <!-- Player List -->
        <div class="role-card" style="text-align: left;">
            <h3 style="border-bottom: 1px solid var(--red); padding-bottom: 1rem; margin-bottom: 1rem;">Players</h3>
            <ul style="list-style: none; padding: 0;">
                <?php
                // Fetch players in the room
                $sql_players = "SELECT u.username, u.id, r.creator_id 
                                FROM room_players rp 
                                JOIN users u ON rp.user_id = u.id 
                                JOIN rooms r ON rp.room_id = r.id 
                                WHERE rp.room_id = ?";
                if($stmt_players = mysqli_prepare($link, $sql_players)){
                    mysqli_stmt_bind_param($stmt_players, "i", $room_id);
                    mysqli_stmt_execute($stmt_players);
                    $result_players = mysqli_stmt_get_result($stmt_players);
                    
                    while($player = mysqli_fetch_assoc($result_players)){
                        $is_creator = ($player['id'] == $player['creator_id']);
                        $is_me = ($player['id'] == $_SESSION['id']);
                        
                        echo '<li style="padding: 0.5rem; border-bottom: 1px solid rgba(255,255,255,0.1); color: var(--white);">';
                        echo htmlspecialchars($player['username']);
                        if($is_me) echo ' (You)';
                        if($is_creator) echo '<span style="float: right; color: gold;">👑</span>';
                        echo '</li>';
                    }
                    mysqli_stmt_close($stmt_players);
                }
                ?>
                <?php if($current_players < $max_players): ?>
                    <li style="padding: 0.5rem; border-bottom: 1px solid rgba(255,255,255,0.1); color: var(--white-dark); font-style: italic;">
                        Waiting for players...
                    </li>
                <?php endif; ?>
            </ul>
            
            <?php if($row['creator_id'] == $_SESSION['id']): ?>
                <div style="margin-top: 2rem; text-align: center;">
                    <button class="cta-button" style="width: 100%; font-size: 1rem;">Start Game</button>
                </div>
            <?php endif; ?>
            
            <div style="margin-top: 1rem; text-align: center;">
                <a href="game_room.php" style="color: var(--white-dark); text-decoration: none; font-size: 0.9rem;">Leave Room</a>
            </div>
        </div>
    </div>
</div>

<?php require_once "includes/footer.php"; ?>
