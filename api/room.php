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

<!-- Lobby Container -->
<div id="lobby-container" class="container" style="padding-top: 100px; padding-bottom: 50px;">
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

<!-- Arena Container -->
<div id="arena-container" class="container" style="display: none; padding-top: 80px; max-width: 1400px; padding-bottom: 50px;">
    <div class="arena-layout" style="display: flex; gap: 20px; height: 80vh;">
        <!-- Left Column: Players -->
        <div class="arena-column players-col" style="flex: 1; background: var(--black-light); border: 1px solid var(--red); border-radius: 10px; padding: 20px; display: flex; flex-direction: column; min-width: 250px;">
            <h3 style="color: var(--red); border-bottom: 1px solid rgba(255,255,255,0.1); padding-bottom: 10px; margin-bottom: 15px;">PLAYERS</h3>
            <div id="arena-player-list" style="flex: 1; overflow-y: auto;">
                <!-- Arena players here -->
            </div>
        </div>

        <!-- Middle Column: Game Status/Actions -->
        <div class="arena-column main-col" style="flex: 2; background: var(--black-light); border: 1px solid var(--red); border-radius: 10px; padding: 20px; display: flex; flex-direction: column; text-align: center; min-width: 400px;">
            <div id="phase-banner" style="background: var(--red); padding: 10px; border-radius: 5px; margin-bottom: 20px;">
                <h2 id="phase-name" style="margin: 0; letter-spacing: 2px;">DAY 1</h2>
            </div>
            
            <div id="arena-timer" style="font-size: 3rem; font-weight: bold; margin: 20px 0; font-family: monospace; color: var(--white);">03:00</div>
            
            <div id="arena-role-card" style="background: rgba(255,255,255,0.05); padding: 20px; border-radius: 10px; margin-bottom: 20px;">
                <p style="color: var(--white-dark); margin-bottom: 5px;">YOUR ROLE</p>
                <h3 id="user-role-display" style="color: #ffeb3b; margin: 0; font-size: 1.5rem;">...</h3>
            </div>

            <div id="arena-action-panel" style="display: none; background: rgba(255,0,0,0.1); border: 1px dashed var(--red); padding: 20px; border-radius: 10px;">
                <h3 id="arena-action-title" style="color: var(--red); margin-bottom: 10px;">YOUR TURN</h3>
                <p id="arena-action-desc" style="color: var(--white-dark);">Select a target from the player list.</p>
                <div id="arena-investigation-result" style="display: none; margin-top: 10px; padding: 10px; background: rgba(0,0,0,0.3); color: #ffeb3b; border-radius: 5px;"></div>
            </div>

            <div id="arena-trial-control" style="display: none; margin-top: auto; padding-top: 20px;">
                <button id="arena-trial-btn" class="cta-button" style="width: 100%;">PROCEED TO TRIAL</button>
            </div>
        </div>

        <!-- Right Column: Chat -->
        <div class="arena-column chat-col" style="flex: 1.5; background: var(--black-light); border: 1px solid var(--red); border-radius: 10px; padding: 20px; display: flex; flex-direction: column; min-width: 350px;">
            <h3 style="color: var(--red); border-bottom: 1px solid rgba(255,255,255,0.1); padding-bottom: 10px; margin-bottom: 15px;">CHAT</h3>
            <div id="arena-chat-box" style="flex: 1; overflow-y: auto; background: rgba(0,0,0,0.2); padding: 10px; border-radius: 5px; margin-bottom: 15px;">
                <!-- Arena chat messages here -->
            </div>
            <form id="arena-chat-form" style="display: flex; gap: 5px;">
                <input type="text" id="arena-message-input" class="form-control" placeholder="Talk..." style="margin: 0;" autocomplete="off">
                <button type="submit" class="cta-button" style="padding: 0 15px;">SEND</button>
            </form>
        </div>
    </div>
</div>

<!-- Original elements preserved for compatibility or hidden use -->
<div id="game-status-notification" style="display: none;"></div>
<div id="phase-text" style="display: none;"></div>
<div id="user-role-text" style="display: none;"></div>
<div id="timer-display" style="display: none;"></div>
<div id="time-left" style="display: none;"></div>
<div id="action-panel" style="display: none;"></div>
<div id="action-title" style="display: none;"></div>
<div id="action-desc" style="display: none;"></div>
<div id="investigation-result" style="display: none;"></div>
<div id="trial-control" style="display: none;"></div>
<div id="trial-btn" style="display: none;"></div>

<!-- Game End Notification -->
<div id="game-end-notification" style="display: none; margin-bottom: 2rem; text-align: center; padding: 2rem; background: gold; color: black; border-radius: 10px; font-weight: bold; position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); z-index: 1000; width: 80%; max-width: 600px; border: 5px solid black;">
    <h2 style="margin-bottom: 1rem; font-size: 2.5rem;">GAME OVER!</h2>
    <h3 id="winner-text" style="font-size: 1.5rem;">The Townspeople Win!</h3>
    <a href="game_room.php" class="cta-button" style="margin-top: 1.5rem; display: inline-block; background: black; color: white;">Return to Lobby</a>
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
    const phaseText = document.getElementById('phase-text');
    const roleText = document.getElementById('user-role-text');
    const startError = document.getElementById('start-error');
    
    const timerDisplay = document.getElementById('timer-display');
    const timeLeft = document.getElementById('time-left');
    const actionPanel = document.getElementById('action-panel');
    const actionTitle = document.getElementById('action-title');
    const actionDesc = document.getElementById('action-desc');
    const investigationResult = document.getElementById('investigation-result');
    const trialControl = document.getElementById('trial-control');
    const trialBtn = document.getElementById('trial-btn');
    const gameEndNotification = document.getElementById('game-end-notification');
    const winnerText = document.getElementById('winner-text');
    
    // Arena Elements
    const lobbyContainer = document.getElementById('lobby-container');
    const arenaContainer = document.getElementById('arena-container');
    const arenaPlayerList = document.getElementById('arena-player-list');
    const phaseName = document.getElementById('phase-name');
    const arenaTimer = document.getElementById('arena-timer');
    const userRoleDisplay = document.getElementById('user-role-display');
    const arenaActionPanel = document.getElementById('arena-action-panel');
    const arenaActionTitle = document.getElementById('arena-action-title');
    const arenaActionDesc = document.getElementById('arena-action-desc');
    const arenaTrialControl = document.getElementById('arena-trial-control');
    const arenaTrialBtn = document.getElementById('arena-trial-btn');
    const arenaChatBox = document.getElementById('arena-chat-box');
    const arenaChatForm = document.getElementById('arena-chat-form');
    const arenaMessageInput = document.getElementById('arena-message-input');
    const arenaInvestigationResult = document.getElementById('arena-investigation-result');

    let lastMessageId = 0;
    let isAlive = true;
    let userRole = '';
    let currentPhase = '';
    let currentTurn = '';

    function syncRoom() {
        fetch(`sync_room.php?room_id=${roomId}&last_id=${lastMessageId}`)
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.text().then(text => {
                    try {
                        return JSON.parse(text);
                    } catch (e) {
                        console.error('Failed to parse JSON:', text);
                        throw new Error('Invalid JSON response');
                    }
                });
            })
            .then(data => {
                if (data.status === 'success') {
                    isAlive = data.is_alive;
                    userRole = data.user_role;
                    
                    if (data.room) {
                        currentPhase = data.room.phase;
                        currentTurn = data.room.current_turn;
                        playerCount.innerText = `${data.room.current_players} / ${data.room.max_players}`;
                        
                        // Handle Game End
                        if (data.room.status === 'finished') {
                            gameEndNotification.style.display = 'block';
                            gameNotification.style.display = 'none';
                            actionPanel.style.display = 'none';
                            trialControl.style.display = 'none';
                            arenaContainer.style.display = 'none';
                            winnerText.innerText = data.room.winner + " Win!";
                            return;
                        }

                        if (data.room.status === 'waiting') {
                            gameNotification.style.display = 'none';
                            if (data.room.creator_id === data.room.current_user_id) {
                                creatorControls.style.display = 'block';
                            } else {
                                creatorControls.style.display = 'none';
                            }
                        }

                        if (data.room.status === 'in_progress') {
                            // Hide lobby elements
                            lobbyContainer.style.display = 'none';
                            gameNotification.style.display = 'none'; 
                            creatorControls.style.display = 'none';
                            actionPanel.style.display = 'none';
                            trialControl.style.display = 'none';
                            
                            // Show Arena
                            arenaContainer.style.display = 'block';
                            if (data.user_role) {
                                userRoleDisplay.innerText = data.user_role + (isAlive ? "" : " (DEAD)");
                                userRoleDisplay.style.color = isAlive ? '#ffeb3b' : '#666';
                            }

                            // Update Phase and Timer in Arena
                            if (data.room.phase === 'day') {
                                phaseName.innerText = "☀️ DAY " + data.room.round;
                                phaseName.parentElement.style.background = '#ef6c00';
                                arenaTimer.style.display = 'block';
                                
                                const minutes = Math.floor(data.room.time_remaining / 60);
                                const seconds = data.room.time_remaining % 60;
                                arenaTimer.innerText = `${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
                                
                                // Show Trial Button for Creator
                                if (data.room.creator_id === data.room.current_user_id) {
                                    arenaTrialControl.style.display = 'block';
                                } else {
                                    arenaTrialControl.style.display = 'none';
                                }
                                arenaActionPanel.style.display = 'none';
                            } else if (data.room.phase === 'night') {
                                phaseName.innerText = "🌙 NIGHT " + data.room.round;
                                phaseName.parentElement.style.background = '#1a237e';
                                arenaTimer.style.display = 'none';
                                arenaTrialControl.style.display = 'none';
                                
                                // Show Action Panel if it's user's turn
                                if (isAlive && userRole === currentTurn) {
                                    arenaActionPanel.style.display = 'block';
                                    arenaActionTitle.innerText = "YOUR TURN";
                                    if (userRole === 'Killer') arenaActionDesc.innerText = "Eliminate someone.";
                                    if (userRole === 'Doctor') arenaActionDesc.innerText = "Protect someone.";
                                    if (userRole === 'Investigator') arenaActionDesc.innerText = "Investigate someone.";
                                } else {
                                    arenaActionPanel.style.display = 'none';
                                }
                            } else if (data.room.phase === 'trial') {
                                phaseName.innerText = "⚖️ TRIAL";
                                phaseName.parentElement.style.background = '#795548';
                                arenaTimer.style.display = 'none';
                                arenaTrialControl.style.display = 'none';
                                
                                if (isAlive) {
                                    arenaActionPanel.style.display = 'block';
                                    arenaActionTitle.innerText = "VOTING TIME";
                                    arenaActionDesc.innerText = "Cast your vote.";
                                } else {
                                    arenaActionPanel.style.display = 'none';
                                }
                            }
                        }
                    }

                    // Update Player List with Action Buttons
                    if (data.players) {
                        playerList.innerHTML = '';
                        arenaPlayerList.innerHTML = '';
                        const currentUserId = data.room ? data.room.current_user_id : null;
                        data.players.forEach(p => {
                            const pDiv = document.createElement('div');
                            pDiv.style.padding = '10px';
                            pDiv.style.marginBottom = '5px';
                            pDiv.style.background = 'rgba(255,255,255,0.05)';
                            pDiv.style.borderRadius = '5px';
                            pDiv.style.display = 'flex';
                            pDiv.style.justifyContent = 'space-between';
                            pDiv.style.alignItems = 'center';
                            
                            let actionBtn = '';
                            if (isAlive && p.is_alive && p.id != currentUserId) {
                                if (currentPhase === 'night' && userRole === currentTurn) {
                                    let actionName = '';
                                    let btnColor = '';
                                    let btnLabel = '';
                                    
                                    if (userRole === 'Killer') {
                                        actionName = 'kill';
                                        btnColor = 'var(--red)';
                                        btnLabel = 'Kill';
                                    } else if (userRole === 'Doctor') {
                                        actionName = 'save';
                                        btnColor = '#4caf50';
                                        btnLabel = 'Save';
                                    } else if (userRole === 'Investigator') {
                                        actionName = 'investigate';
                                        btnColor = '#2196f3';
                                        btnLabel = 'Check';
                                    }
                                    
                                    if (actionName) {
                                        actionBtn = `<button onclick="performAction('${actionName}', ${p.id})" class="cta-button" style="padding: 5px 15px; font-size: 0.8rem; background: ${btnColor}; margin: 0;">${btnLabel}</button>`;
                                    }
                                } else if (currentPhase === 'trial') {
                                    actionBtn = `<button onclick="performAction('vote', ${p.id})" class="cta-button" style="padding: 5px 15px; font-size: 0.8rem; background: #795548; margin: 0;">Vote (${p.vote_count})</button>`;
                                }
                            }

                            const pContent = `
                                <div>
                                    <span style="color: ${p.is_alive ? 'white' : '#666'}">${p.username}</span>
                                    ${!p.is_alive ? '<span style="color: var(--red); font-size: 0.7rem; margin-left: 5px;">DEAD</span>' : ''}
                                </div>
                                ${actionBtn}
                            `;
                            
                            pDiv.innerHTML = pContent;
                            playerList.appendChild(pDiv);
                            
                            // Clone for arena
                            const pDivArena = pDiv.cloneNode(true);
                            arenaPlayerList.appendChild(pDivArena);
                        });
                    }

                    // Append New Messages
                    if (data.messages && data.messages.length > 0) {
                        // Remove "Loading..." or "No messages yet" if they exist
                        if (lastMessageId === 0) {
                            chatBox.innerHTML = '';
                            arenaChatBox.innerHTML = '';
                        }

                        const isScrolledToBottom = chatBox.scrollHeight - chatBox.clientHeight <= chatBox.scrollTop + 1;
                        const isArenaScrolledToBottom = arenaChatBox.scrollHeight - arenaChatBox.clientHeight <= arenaChatBox.scrollTop + 1;
                        
                        data.messages.forEach(msg => {
                            const msgDiv = document.createElement('div');
                            msgDiv.style.marginBottom = '0.5rem';
                            msgDiv.innerHTML = `<span style="color: var(--white-dark); font-size: 0.8rem;">[${msg.time}]</span> <strong style="color: var(--red);">${msg.username}:</strong> <span style="color: var(--white);">${msg.message}</span>`;
                            
                            chatBox.appendChild(msgDiv);
                            
                            // Clone for arena
                            const msgDivArena = msgDiv.cloneNode(true);
                            arenaChatBox.appendChild(msgDivArena);
                            
                            lastMessageId = msg.id;
                        });

                        if (isScrolledToBottom) chatBox.scrollTop = chatBox.scrollHeight;
                        if (isArenaScrolledToBottom) arenaChatBox.scrollTop = arenaChatBox.scrollHeight;
                    } else if (lastMessageId === 0) {
                        chatBox.innerHTML = '<p style="color: var(--white-dark); font-style: italic;">No messages yet.</p>';
                        arenaChatBox.innerHTML = '<p style="color: var(--white-dark); font-style: italic;">No messages yet.</p>';
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
        sendMessage(message);
    });

    arenaChatForm.addEventListener('submit', function(e) {
        e.preventDefault();
        const message = arenaMessageInput.value.trim();
        sendMessage(message);
    });

    function sendMessage(message) {
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
                    arenaMessageInput.value = '';
                    syncRoom();
                }
            });
        }
    }

    function performAction(action, targetId) {
        const formData = new FormData();
        formData.append('room_id', roomId);
        formData.append('action', action);
        formData.append('target_id', targetId);

        fetch('game_action.php', {
            method: 'POST',
            body: formData
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            if (data.status === 'success') {
                if (data.investigation_result) {
                    investigationResult.style.display = 'block';
                    investigationResult.innerText = `Investigation Result: This player is ${data.investigation_result}`;
                    
                    arenaInvestigationResult.style.display = 'block';
                    arenaInvestigationResult.innerText = `Investigation Result: ${data.investigation_result}`;
                    
                    // Also show in arena desc for visibility
                    arenaActionDesc.innerText = `Investigation Result: ${data.investigation_result}`;
                }
                actionPanel.style.display = 'none';
                arenaActionPanel.style.display = 'none';
                syncRoom(); // Refresh UI immediately
            } else {
                alert(data.message);
            }
        })
        .catch(err => {
            console.error('Action error:', err);
            alert('An error occurred while performing the action. Please try again.');
        });
    }

    trialBtn.addEventListener('click', () => {
        proceedToTrial();
    });

    arenaTrialBtn.addEventListener('click', () => {
        proceedToTrial();
    });

    function proceedToTrial() {
        const formData = new FormData();
        formData.append('room_id', roomId);
        formData.append('action', 'trial');

        fetch('game_action.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                trialControl.style.display = 'none';
                arenaTrialControl.style.display = 'none';
                syncRoom();
            }
        });
    }

    // Start syncing every 2 seconds
    setInterval(syncRoom, 2000);
    syncRoom();
</script>

<?php require_once "includes/footer.php"; ?>
