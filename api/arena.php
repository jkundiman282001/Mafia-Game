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
    
    // Fetch room details
    $sql = "SELECT * FROM rooms WHERE id = ?";
    if($stmt = mysqli_prepare($link, $sql)){
        mysqli_stmt_bind_param($stmt, "i", $room_id);
        if(mysqli_stmt_execute($stmt)){
            $result = mysqli_stmt_get_result($stmt);
            if(mysqli_num_rows($result) == 1){
                $room = mysqli_fetch_assoc($result);
                if($room['status'] !== 'in_progress'){
                    echo "<script>window.location.href='room.php?id=$room_id';</script>";
                    exit;
                }
            } else {
                echo "<script>window.location.href='game_room.php';</script>";
                exit;
            }
        }
        mysqli_stmt_close($stmt);
    }

    // Fetch user's role and status
    $sql_player = "SELECT role, is_alive FROM room_players WHERE room_id = ? AND user_id = ?";
    if($stmt_p = mysqli_prepare($link, $sql_player)){
        mysqli_stmt_bind_param($stmt_p, "ii", $room_id, $_SESSION['id']);
        mysqli_stmt_execute($stmt_p);
        $res_p = mysqli_stmt_get_result($stmt_p);
        $player_data = mysqli_fetch_assoc($res_p);
        $my_role = $player_data ? $player_data['role'] : 'Unknown';
        $is_alive = $player_data ? $player_data['is_alive'] : false;
        mysqli_stmt_close($stmt_p);
    }
} else {
    echo "<script>window.location.href='game_room.php';</script>";
    exit;
}
?>

<div class="container" style="padding-top: 100px; padding-bottom: 50px;">
    <div style="text-align: center; margin-bottom: 3rem;">
        <h2 class="section-title" style="margin-bottom: 0.5rem;">GAME ARENA</h2>
        <div id="phase-indicator" style="display: inline-block; padding: 0.5rem 2rem; border-radius: 20px; background: rgba(0,0,0,0.5); border: 1px solid var(--red); margin-bottom: 1rem;">
            <span id="current-phase" style="color: var(--red); font-weight: bold; text-transform: uppercase; letter-spacing: 3px;">NIGHT PHASE</span>
            <span id="current-round" style="color: var(--white-dark); margin-left: 1rem;">ROUND 1</span>
        </div>
        <p style="color: var(--white-dark); text-transform: uppercase; letter-spacing: 2px;">Room: <?php echo htmlspecialchars($room['room_name']); ?></p>
    </div>

    <style>
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.9);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 1000;
            display: none;
        }
        .modal-content {
            background: var(--black-light);
            padding: 3rem;
            border-radius: 20px;
            border: 2px solid var(--red);
            text-align: center;
            max-width: 500px;
            width: 90%;
            box-shadow: 0 0 30px rgba(255, 0, 64, 0.3);
        }
        .modal-title {
            font-size: 2.5rem;
            color: var(--white);
            margin-bottom: 1.5rem;
            text-transform: uppercase;
            letter-spacing: 5px;
        }
        .loading-dots:after {
            content: ' .';
            animation: dots 1.5s steps(5, end) infinite;
        }
        @keyframes dots {
            0%, 20% { content: ' .'; }
            40% { content: ' . .'; }
            60% { content: ' . . .'; }
            80%, 100% { content: ' . . . .'; }
        }
    </style>

    <div id="game-modal" class="modal-overlay">
        <div class="modal-content">
            <h2 id="modal-title" class="modal-title">NIGHT PHASE</h2>
            <p id="modal-message" style="color: var(--white-dark); font-size: 1.2rem;"></p>
        </div>
    </div>

    <div class="arena-container" style="display: grid; grid-template-columns: 1fr 2fr 1fr; gap: 2rem; align-items: flex-start;">
        <!-- Left Column: Player Info -->
        <div class="role-card" style="text-align: left;">
            <h3 style="border-bottom: 1px solid var(--red); padding-bottom: 1rem; margin-bottom: 1rem;">Your Identity</h3>
            <div style="text-align: center; padding: 1.5rem; background: rgba(0,0,0,0.3); border-radius: 10px; border: 1px solid var(--red);">
                <div style="font-size: 0.9rem; color: var(--white-dark); margin-bottom: 0.5rem;">YOU ARE A</div>
                <div style="font-size: 2rem; color: var(--red); font-weight: bold; font-family: 'Orbitron', sans-serif; margin-bottom: 1rem; text-shadow: 0 0 10px rgba(255,0,64,0.5);">
                    <?php echo strtoupper($my_role); ?>
                </div>
                <div style="display: inline-block; padding: 0.3rem 1rem; border-radius: 15px; background: <?php echo $is_alive ? 'rgba(0,255,0,0.1)' : 'rgba(255,0,0,0.1)'; ?>; border: 1px solid <?php echo $is_alive ? '#00ff00' : '#ff0000'; ?>; color: <?php echo $is_alive ? '#00ff00' : '#ff0000'; ?>; font-size: 0.8rem; font-weight: bold;">
                    <?php echo $is_alive ? 'ALIVE' : 'DECEASED'; ?>
                </div>
            </div>
            
            <div id="action-area" style="margin-top: 2rem; display: none;">
                <h4 id="action-title" style="color: var(--white-dark); font-size: 0.9rem; margin-bottom: 1rem; text-transform: uppercase;">Your Action</h4>
                <div id="action-content">
                    <!-- Action buttons will be injected here -->
                </div>
            </div>
            
            <div id="host-controls" style="margin-top: 2rem; display: <?php echo $_SESSION['id'] == $room['creator_id'] ? 'block' : 'none'; ?>;">
                <button id="next-phase-btn" class="cta-button" style="width: 100%; padding: 0.8rem;">Advance to Next Phase</button>
            </div>
        </div>

        <!-- Center Column: Game Logs / Action Area -->
        <div class="role-card" style="text-align: left; display: flex; flex-direction: column; min-height: 500px;">
            <h3 style="border-bottom: 1px solid var(--red); padding-bottom: 1rem; margin-bottom: 1rem;">Arena Chat</h3>
            <div id="chat-box" style="flex-grow: 1; background: rgba(0,0,0,0.4); border-radius: 10px; padding: 1rem; overflow-y: auto; margin-bottom: 1rem;">
                <p style="color: var(--white-dark); font-style: italic;">Connecting to arena chat...</p>
            </div>
            
            <!-- Chat for the arena -->
            <form id="chat-form" style="display: flex; gap: 10px;">
                <input type="hidden" id="room_id" value="<?php echo $room_id; ?>">
                <input type="text" id="message-input" class="form-control" placeholder="Type to chat..." style="margin-bottom: 0;" autocomplete="off">
                <button type="submit" class="cta-button" style="padding: 0.5rem 1.5rem;">Send</button>
            </form>
        </div>

        <!-- Right Column: Alive Players -->
        <div class="role-card" style="text-align: left;">
            <h3 style="border-bottom: 1px solid var(--red); padding-bottom: 1rem; margin-bottom: 1rem;">Players</h3>
            <ul id="players-list" style="list-style: none; padding: 0;">
                <!-- Players will be loaded here via JS -->
            </ul>
        </div>
    </div>
    
    <div style="margin-top: 2rem; text-align: center;">
        <a href="game_room.php" style="color: var(--white-dark); text-decoration: none; font-size: 0.9rem;">Quit Game</a>
    </div>
</div>

<script>
    const roomId = <?php echo $room_id; ?>;
    const myRole = "<?php echo $my_role; ?>";
    const isAlive = <?php echo $is_alive ? 'true' : 'false'; ?>;
    const chatBox = document.getElementById('chat-box');
    const chatForm = document.getElementById('chat-form');
    const messageInput = document.getElementById('message-input');
    const playersList = document.getElementById('players-list');
    const currentPhase = document.getElementById('current-phase');
    const currentRound = document.getElementById('current-round');
    const actionArea = document.getElementById('action-area');
    const actionTitle = document.getElementById('action-title');
    const actionContent = document.getElementById('action-content');
    const nextPhaseBtn = document.getElementById('next-phase-btn');
    const gameModal = document.getElementById('game-modal');
    const modalTitle = document.getElementById('modal-title');
    const modalMessage = document.getElementById('modal-message');

    let gameState = {
        phase: 'night',
        round: 1,
        action_count: 0,
        current_turn: 'Killer'
    };

    let lastPhase = null;

    function showModal(title, message, duration = null) {
        modalTitle.textContent = title;
        modalMessage.innerHTML = message;
        gameModal.style.display = 'flex';
        if (duration) {
            setTimeout(() => {
                gameModal.style.display = 'none';
            }, duration);
        }
    }

    function hideModal() {
        gameModal.style.display = 'none';
    }

    function updateGameUI() {
        fetch(`get_room_status.php?room_id=${roomId}`)
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    const phaseChanged = lastPhase !== data.phase;
                    gameState = data;
                    lastPhase = data.phase;

                    currentPhase.textContent = `${data.phase} PHASE`;
                    currentRound.textContent = `ROUND ${data.round}`;
                    
                    if (phaseChanged) {
                        showModal(`${data.phase} PHASE`, `Round ${data.round} has begun.`, 3000);
                        return; // Don't process turns while phase transition modal is showing
                    }

                    if (data.phase === 'night') {
                        document.getElementById('phase-indicator').style.borderColor = 'var(--red)';
                        currentPhase.style.color = 'var(--red)';
                        
                        if (data.current_turn === 'None') {
                            // If all turns are done, auto transition to day (host only)
                            <?php if ($_SESSION['id'] == $room['creator_id']): ?>
                            autoTransitionToDay();
                            <?php endif; ?>
                            showModal("NIGHT ENDING", "Processing night results...", null);
                        } else if (isAlive && data.current_turn === myRole) {
                            hideModal();
                            actionArea.style.display = 'block';
                            renderActions();
                        } else {
                            actionArea.style.display = 'none';
                            showModal("NIGHT PHASE", `<span class="loading-dots">${data.current_turn}'s Turn</span>`, null);
                        }
                    } else {
                        document.getElementById('phase-indicator').style.borderColor = '#ffaa00';
                        currentPhase.style.color = '#ffaa00';
                        hideModal();
                        actionArea.style.display = 'none';
                    }
                }
            });
    }

    function autoTransitionToDay() {
        const formData = new FormData();
        formData.append('room_id', roomId);
        fetch('transition_phase.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                updateGameUI();
                fetchMessages();
            }
        });
    }

    function renderActions() {
        if (myRole === 'Killer') {
            actionTitle.textContent = "CHOOSE A TARGET TO ELIMINATE";
        } else if (myRole === 'Doctor') {
            actionTitle.textContent = "CHOOSE A PLAYER TO PROTECT";
        } else if (myRole === 'Detective') {
            actionTitle.textContent = "CHOOSE A PLAYER TO INVESTIGATE";
        } else {
            actionArea.style.display = 'none';
        }
    }

    function performAction(targetId, actionType) {
        const formData = new FormData();
        formData.append('room_id', roomId);
        formData.append('target_id', targetId);
        formData.append('action_type', actionType);

        fetch('game_action.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                if (actionType === 'investigate') {
                    const resultColor = data.result === 'Bad' ? 'var(--red)' : '#00ff00';
                    showModal("INVESTIGATION RESULT", `The player is <span style="color: ${resultColor}; font-weight: bold;">${data.result}</span>`, 4000);
                } else {
                    alert('Action recorded for tonight.');
                }
                actionArea.style.display = 'none';
                updateGameUI(); // Trigger UI update to show next turn loading screen
            } else {
                alert(data.message);
            }
        });
    }

    if (nextPhaseBtn) {
        nextPhaseBtn.addEventListener('click', function() {
            const formData = new FormData();
            formData.append('room_id', roomId);

            fetch('transition_phase.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    updateGameUI();
                    fetchMessages();
                }
            });
        });
    }

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

    function fetchPlayers() {
        // We could create a get_players.php, but for now let's just use a simple fetch within arena.php
        // Or better, let's just refresh this part every 10 seconds since it doesn't change much
        fetch(`get_arena_players.php?room_id=${roomId}`)
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    playersList.innerHTML = '';
                    data.players.forEach(p => {
                        const li = document.createElement('li');
                        li.style.cssText = 'padding: 0.7rem; border-bottom: 1px solid rgba(255,255,255,0.05); display: flex; justify-content: space-between; align-items: center;';
                        
                        const nameSpan = document.createElement('span');
                        nameSpan.textContent = p.username;
                        if (!p.is_alive) {
                            nameSpan.style.cssText = 'color: var(--white-dark); text-decoration: line-through;';
                        } else {
                            nameSpan.style.color = 'var(--white)';
                            
                            // Add action button if it's night and player is alive and it's not the current user
                            if (isAlive && gameState.phase === 'night' && gameState.current_turn === myRole && p.user_id != <?php echo $_SESSION['id']; ?>) {
                                const actionBtn = document.createElement('button');
                                actionBtn.style.cssText = 'margin-left: 10px; padding: 2px 8px; font-size: 0.7rem; border-radius: 4px; cursor: pointer; border: 1px solid var(--red); background: transparent; color: var(--red);';
                                
                                if (myRole === 'Killer') {
                                    actionBtn.textContent = 'KILL';
                                    actionBtn.onclick = () => performAction(p.user_id, 'kill');
                                } else if (myRole === 'Doctor') {
                                    actionBtn.textContent = 'SAVE';
                                    actionBtn.onclick = () => performAction(p.user_id, 'save');
                                } else if (myRole === 'Detective') {
                                    actionBtn.textContent = 'CHECK';
                                    actionBtn.onclick = () => performAction(p.user_id, 'investigate');
                                }
                                
                                if (actionBtn.textContent) {
                                    nameSpan.appendChild(actionBtn);
                                }
                            }
                        }
                        
                        const statusSpan = document.createElement('span');
                        if (p.is_alive) {
                            statusSpan.style.cssText = 'width: 8px; height: 8px; background: #00ff00; border-radius: 50%; box-shadow: 0 0 5px #00ff00;';
                        } else {
                            statusSpan.textContent = 'RIP';
                            statusSpan.style.cssText = 'font-size: 0.8rem; color: var(--red);';
                        }
                        
                        li.appendChild(nameSpan);
                        li.appendChild(statusSpan);
                        playersList.appendChild(li);
                    });
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

    // Initial fetch and polling
    updateGameUI();
    fetchMessages();
    fetchPlayers();
    setInterval(updateGameUI, 3000);
    setInterval(fetchMessages, 2000);
    setInterval(fetchPlayers, 5000);
</script>

<?php require_once "includes/footer.php"; ?>