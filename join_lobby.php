<?php
require_once "includes/header.php";
require_once "includes/config.php";

// Check if the user is logged in
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    echo "<script>window.location.href='login.php';</script>";
    exit;
}

// Fetch available rooms
$sql = "SELECT r.*, u.username as creator_name 
        FROM rooms r 
        JOIN users u ON r.creator_id = u.id 
        WHERE r.status = 'waiting' 
        ORDER BY r.created_at DESC";
$result = mysqli_query($link, $sql);
?>

<div class="container" style="padding-top: 100px; padding-bottom: 50px;">
    <h2 class="section-title">Join Lobby</h2>
    <p style="text-align: center; color: var(--white-dark); margin-bottom: 3rem;">Select a room to join or enter a Room ID.</p>
    
    <!-- Join by ID Form -->
    <div style="max-width: 500px; margin: 0 auto 3rem; background: var(--black-light); padding: 2rem; border-radius: 20px; border: 1px solid var(--red);">
        <h3 style="text-align: center; margin-bottom: 1.5rem; color: var(--white);">Join by Room ID</h3>
        <form action="join_room_action.php" method="post" style="display: flex; gap: 10px;">
            <input type="text" name="room_code" class="form-control" placeholder="Enter Room Code (e.g. A1B2C3)" style="margin-bottom: 0;" required>
            <button type="submit" class="cta-button" style="padding: 0.5rem 1.5rem; white-space: nowrap;">Join Room</button>
        </form>
    </div>

    <h3 style="margin-bottom: 1.5rem; color: var(--white); border-left: 4px solid var(--red); padding-left: 1rem;">Available Rooms</h3>
    <div style="background: var(--black-light); padding: 2rem; border-radius: 20px; border: 1px solid var(--red);">
        <?php if(mysqli_num_rows($result) > 0): ?>
            <div class="rooms-list" style="display: grid; gap: 1rem;">
                <?php while($row = mysqli_fetch_assoc($result)): ?>
                    <div class="room-item" style="display: flex; justify-content: space-between; align-items: center; padding: 1.5rem; background: rgba(0,0,0,0.3); border-radius: 10px; border: 1px solid rgba(255, 0, 64, 0.2);">
                        <div>
                            <h3 style="color: var(--white); margin-bottom: 0.5rem;"><?php echo htmlspecialchars($row['room_name']); ?></h3>
                            <p style="color: var(--white-dark); font-size: 0.9rem;">
                                Host: <span style="color: var(--red);"><?php echo htmlspecialchars($row['creator_name']); ?></span>
                                <span style="margin: 0 10px;">|</span>
                                Players: <?php echo $row['current_players']; ?>/<?php echo $row['max_players']; ?>
                            </p>
                        </div>
                        <div>
                            <?php if($row['current_players'] < $row['max_players']): ?>
                                <form action="join_room_action.php" method="post">
                                    <input type="hidden" name="room_id" value="<?php echo $row['id']; ?>">
                                    <button type="submit" class="cta-button" style="padding: 0.5rem 2rem; font-size: 1rem;">Join</button>
                                </form>
                            <?php else: ?>
                                <button class="cta-button" style="padding: 0.5rem 2rem; font-size: 1rem; opacity: 0.5; cursor: not-allowed; background: transparent;">Full</button>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <p style="text-align: center; color: var(--white-dark); padding: 2rem;">No rooms available currently. Why not create one?</p>
            <div style="text-align: center; margin-top: 1rem;">
                <a href="create_room.php" class="cta-button" style="display: inline-block;">Create Room</a>
            </div>
        <?php endif; ?>
    </div>
    
    <div style="text-align: center; margin-top: 2rem;">
        <a href="game_room.php" style="color: var(--white-dark); text-decoration: none;">Back to Dashboard</a>
    </div>
</div>

<?php require_once "includes/footer.php"; ?>
