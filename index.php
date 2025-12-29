<?php require_once "includes/header.php"; ?>

    <section class="hero" id="home">
        <div class="container">
            <div class="hero-content">
                <div class="subtitle">Social Deduction</div>
                <h1>MAFIA GAME</h1>
                <p>Trust no one. Deceive everyone. Survive the night. Can you identify the killer before it's too late?</p>
                <?php if(isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true): ?>
                    <a href="game_room.php" class="cta-button">Play Now</a>
                <?php else: ?>
                    <a href="signup.php" class="cta-button">Join the Game</a>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <section class="roles-section" id="roles">
        <div class="container">
            <h2 class="section-title">Choose Your Role</h2>
            <div class="roles-grid">
                <div class="role-card">
                    <div class="role-icon">👥</div>
                    <h3>Townsfolk</h3>
                    <p>You are an innocent citizen. Work together with other townspeople to identify and eliminate the killer through discussion and voting.</p>
                    <span class="role-badge">Innocent</span>
                </div>
                <div class="role-card">
                    <div class="role-icon">🔪</div>
                    <h3>Killer</h3>
                    <p>You are the mastermind. Eliminate players each night, blend in during the day, and deceive the town to achieve victory.</p>
                    <span class="role-badge">Evil</span>
                </div>
                <div class="role-card">
                    <div class="role-icon">🔍</div>
                    <h3>Detective</h3>
                    <p>Investigate one player each night to discover their true identity. Use your findings to guide the town to victory.</p>
                    <span class="role-badge">Special</span>
                </div>
                <div class="role-card">
                    <div class="role-icon">💉</div>
                    <h3>Doctor</h3>
                    <p>Protect one player each night from the killer's attack. Your healing powers are crucial for the town's survival.</p>
                    <span class="role-badge">Protector</span>
                </div>
            </div>
        </div>
    </section>

    <section class="how-to-play" id="how-to-play">
        <div class="container">
            <h2 class="section-title">How to Play</h2>
            <div class="steps">
                <div class="step">
                    <div class="step-number">1</div>
                    <h4>Join Game</h4>
                    <p>Create or join a game room with your friends</p>
                </div>
                <div class="step">
                    <div class="step-number">2</div>
                    <h4>Get Role</h4>
                    <p>Receive your secret role assignment privately</p>
                </div>
                <div class="step">
                    <div class="step-number">3</div>
                    <h4>Day Phase</h4>
                    <p>Discuss and vote to eliminate suspicious players</p>
                </div>
                <div class="step">
                    <div class="step-number">4</div>
                    <h4>Night Phase</h4>
                    <p>Special roles perform their actions in secret</p>
                </div>
                <div class="step">
                    <div class="step-number">5</div>
                    <h4>Victory</h4>
                    <p>Eliminate all threats or be eliminated yourself!</p>
                </div>
            </div>
        </div>
    </section>

<?php require_once "includes/footer.php"; ?>
