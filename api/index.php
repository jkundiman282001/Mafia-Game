<?php require_once "includes/header.php"; ?>

    <section class="hero" id="home">
        <div class="container">
            <div class="hero-content">
                <div class="subtitle">Social Game Platform</div>
                <h1>GAME PORTAL</h1>
                <p>Create rooms, chat with friends, and build your own game experience from scratch.</p>
                <?php if(isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true): ?>
                    <a href="game_room.php" class="cta-button">Enter Lobby</a>
                <?php else: ?>
                    <a href="signup.php" class="cta-button">Get Started</a>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <section class="how-to-play" id="how-to-play">
        <div class="container">
            <h2 class="section-title">Getting Started</h2>
            <div class="steps">
                <div class="step">
                    <div class="step-number">1</div>
                    <h4>Create Account</h4>
                    <p>Sign up to start hosting or joining rooms.</p>
                </div>
                <div class="step">
                    <div class="step-number">2</div>
                    <h4>Join Lobby</h4>
                    <p>Enter the game lobby to see available rooms.</p>
                </div>
                <div class="step">
                    <div class="step-number">3</div>
                    <h4>Create Room</h4>
                    <p>Start your own room and invite others with a code.</p>
                </div>
                <div class="step">
                    <div class="step-number">4</div>
                    <h4>Chat & Build</h4>
                    <p>Communicate in real-time and develop your mechanics.</p>
                </div>
            </div>
        </div>
    </section>

<?php require_once "includes/footer.php"; ?>
