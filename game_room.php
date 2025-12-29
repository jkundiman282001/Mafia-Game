<?php
require_once "includes/header.php";

// Check if the user is logged in, if not then redirect him to login page
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    echo "<script>window.location.href='login.php';</script>";
    exit;
}
?>

<div class="container" style="padding-top: 100px; padding-bottom: 50px;">
    <h2 class="section-title">Game Room</h2>
    <p style="text-align: center; color: var(--white-dark); margin-bottom: 3rem;">Join a game or create your own room.</p>
    
    <div class="roles-grid">
        <!-- Public Room -->
        <div class="role-card">
            <div class="role-icon" style="font-size: 3rem;">🎮</div>
            <h3>Public Lobby</h3>
            <p>Join players from around the world in a classic Mafia game.</p>
            <div style="margin: 1rem 0;">
                <span class="role-badge" style="background: var(--red);">Live</span>
                <span style="color: var(--white-dark); font-size: 0.9rem; margin-left: 10px;">Waiting for players...</span>
            </div>
            <a href="join_lobby.php" class="cta-button" style="width: 100%; margin-top: 1rem; display: inline-block;">Join Lobby</a>
        </div>

        <!-- Create Room -->
        <div class="role-card">
            <div class="role-icon" style="font-size: 3rem;">➕</div>
            <h3>Create Room</h3>
            <p>Host a private game for you and your friends with custom rules.</p>
            <div style="margin: 1rem 0;">
                <span class="role-badge" style="background: var(--white-dark); color: var(--black);">Custom</span>
            </div>
            <a href="create_room.php" class="cta-button" style="width: 100%; margin-top: 1rem; background: transparent; border: 1px solid var(--red); display: inline-block;">Create New</a>
        </div>

        <!-- Ranked Room -->
        <div class="role-card">
            <div class="role-icon" style="font-size: 3rem;">🏆</div>
            <h3>Ranked Match</h3>
            <p>Compete against skilled players and climb the leaderboard.</p>
            <div style="margin: 1rem 0;">
                <span class="role-badge" style="background: gold; color: black;">Competitive</span>
            </div>
            <button class="cta-button" style="width: 100%; margin-top: 1rem; opacity: 0.5; cursor: not-allowed;">Coming Soon</button>
        </div>
    </div>
</div>

<?php require_once "includes/footer.php"; ?>
