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

    <div class="roles-grid room-container" style="display: flex; gap: 2rem; align-items: flex-start;">
        <!-- Left Side: Chat Room -->
        <div class="role-card chat-section" style="flex: 2; text-align: left; height: 500px; display: flex; flex-direction: column;">
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
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok: ' + response.statusText);
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.status === 'success') {
                        messageInput.value = '';
                        fetchMessages();
                    } else {
                        alert('Error: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Fetch error:', error);
                    alert('Failed to send message. See console for details.');
                });
            });

            // Initial fetch and poll every 2 seconds
            fetchMessages();
            setInterval(fetchMessages, 2000);

            // Poll for game status
            function checkGameStatus() {
                fetch(`get_room_status.php?room_id=${roomId}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.status === 'success' && data.room_status === 'in_progress') {
                            window.location.href = `arena.php?id=${roomId}`;
                        }
                    });
            }
            setInterval(checkGameStatus, 3000);
        </script>

        <!-- Player List -->
        <div class="role-card sidebar" style="text-align: left;">
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
            
            <?php 
            // If game is in progress, show your role
            if($status == 'in_progress'):
                $sql_role = "SELECT role FROM room_players WHERE room_id = ? AND user_id = ?";
                if($stmt_r = mysqli_prepare($link, $sql_role)){
                    mysqli_stmt_bind_param($stmt_r, "ii", $room_id, $_SESSION['id']);
                    mysqli_stmt_execute($stmt_r);
                    $res_r = mysqli_stmt_get_result($stmt_r);
                    $role_data = mysqli_fetch_assoc($res_r);
                    $my_role = $role_data ? $role_data['role'] : 'Unknown';
                    ?>
                    <div style="margin-top: 2rem; padding: 1rem; background: rgba(255,0,0,0.1); border: 1px solid var(--red); border-radius: 10px; text-align: center;">
                        <h4 style="color: var(--white-dark); margin-bottom: 0.5rem;">YOUR ROLE</h4>
                        <div style="font-size: 1.5rem; color: var(--red); font-weight: bold; font-family: 'Orbitron', sans-serif;"><?php echo strtoupper($my_role); ?></div>
                    </div>
                    <?php
                }
            endif;
            ?>
            
            <?php if($row['creator_id'] == $_SESSION['id'] && $status == 'waiting'): ?>
                <div style="margin-top: 2rem; text-align: center;">
                    <?php 
                    $min_players = 4; // Set a minimum number of players to start the game
                    $can_start = ($current_players >= $min_players);
                    ?>
                    <form action="start_game_action.php" method="post">
                        <input type="hidden" name="room_id" value="<?php echo $room_id; ?>">
                        <button type="submit" class="cta-button" style="width: 100%; font-size: 1rem; <?php echo !$can_start ? 'opacity: 0.5; cursor: not-allowed;' : ''; ?>" <?php echo !$can_start ? 'disabled' : ''; ?>>
                            <?php echo $can_start ? 'Start Game' : 'Need ' . ($min_players - $current_players) . ' More Players'; ?>
                        </button>
                    </form>
                </div>
            <?php endif; ?>
            
            <div style="margin-top: 1rem; text-align: center;">
                <a href="game_room.php" style="color: var(--white-dark); text-decoration: none; font-size: 0.9rem;">Leave Room</a>
            </div>
        </div>
    </div>
</div>

<?php require_once "includes/footer.php"; ?>
