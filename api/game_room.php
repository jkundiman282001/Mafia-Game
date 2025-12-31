<?php
require_once "includes/header.php";

// Check if the user is logged in, if not then redirect him to login page
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    echo "<script>window.location.href='login.php';</script>";
    exit;
}
?>

<div class="container" style="padding-top: 100px; padding-bottom: 50px;">
    <h2 class="section-title">Lobby</h2>
    <p style="text-align: center; color: var(--white-dark); margin-bottom: 3rem;">Join a room or create your own to start chatting.</p>
    
    <div class="roles-grid">
        <!-- Public Room -->
        <div class="role-card">
            <div class="role-icon" style="font-size: 3rem;">💬</div>
            <h3>Public Lobby</h3>
            <p>Join public rooms and meet new people.</p>
            <div style="margin: 1rem 0;">
                <span class="role-badge" style="background: var(--red);">Open</span>
            </div>
            <a href="join_lobby.php" class="cta-button" style="width: 100%; margin-top: 1rem; display: inline-block;">Browse Rooms</a>
        </div>

        <!-- Create Room -->
        <div class="role-card">
            <div class="role-icon" style="font-size: 3rem;">🏠</div>
            <h3>Create Room</h3>
            <p>Host your own room and invite your friends with a code.</p>
            <div style="margin: 1rem 0;">
                <span class="role-badge" style="background: var(--white-dark); color: var(--black);">Private</span>
            </div>
            <a href="create_room.php" class="cta-button" style="width: 100%; margin-top: 1rem; background: transparent; border: 1px solid var(--red); display: inline-block;">Create New</a>
        </div>

        <!-- Ranked Room -->
        <div class="role-card">
            <div class="role-icon" style="font-size: 3rem;">⚡</div>
            <h3>Quick Join</h3>
            <p>Instantly jump into a random active room.</p>
            <div style="margin: 1rem 0;">
                <span class="role-badge" style="background: var(--red); color: white;">Fast</span>
            </div>
            <button class="cta-button" style="width: 100%; margin-top: 1rem; opacity: 0.5; cursor: not-allowed;">Coming Soon</button>
        </div>
    </div>
</div>

<?php require_once "includes/footer.php"; ?>
