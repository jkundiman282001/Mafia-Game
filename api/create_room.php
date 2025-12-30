<?php
require_once "includes/header.php";

// Check if the user is logged in
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    echo "<script>window.location.href='login.php';</script>";
    exit;
}

require_once "includes/config.php";

$room_name = "";
$room_name_err = "";

if($_SERVER["REQUEST_METHOD"] == "POST"){
    // Validate room name
    if(empty(trim($_POST["room_name"]))){
        $room_name_err = "Please enter a room name.";
    } else{
        $room_name = trim($_POST["room_name"]);
    }
    
    // Check input errors before inserting in database
    if(empty($room_name_err)){
        // Generate a unique room code
        $room_code = strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 6));
        $max_players = isset($_POST["max_players"]) ? (int)$_POST["max_players"] : 10;
        $user_id = (int)$_SESSION["id"];
        
        $escaped_room_name = mysqli_real_escape_string($link, $room_name);
        $sql = "INSERT INTO rooms (room_name, room_code, creator_id, max_players) VALUES ('$escaped_room_name', '$room_code', $user_id, $max_players)";
        
        if(mysqli_query($link, $sql)){
            $new_room_id = mysqli_insert_id($link);
            
            // Add creator to room_players and update current_players count
            $sql_player = "INSERT INTO room_players (room_id, user_id) VALUES ($new_room_id, $user_id)";
            mysqli_query($link, $sql_player);
            
            // Update room current players count
            $sql_update = "UPDATE rooms SET current_players = 1 WHERE id = $new_room_id";
            mysqli_query($link, $sql_update);
            
            // Redirect to room page
            session_write_close();
            echo "<script>window.location.href='room.php?id=" . $new_room_id . "';</script>";
            exit;
        } else{
            echo "Something went wrong. Please try again later. " . mysqli_error($link);
        }
    }
}
?>

<div class="auth-wrapper">
    <div class="auth-card">
        <h2>Create Room</h2>
        <p style="text-align: center; margin-bottom: 2rem; color: var(--white-dark);">Set up your game room.</p>
        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
            <div class="form-group">
                <label>Room Name</label>
                <input type="text" name="room_name" class="form-control" value="<?php echo $room_name; ?>">
                <span class="help-block"><?php echo $room_name_err; ?></span>
            </div>
            <div class="form-group">
                <label>Max Players</label>
                <select name="max_players" class="form-control">
                    <option value="5">5 Players</option>
                    <option value="10" selected>10 Players</option>
                    <option value="15">15 Players</option>
                </select>
            </div>
            <div class="form-group">
                <input type="submit" class="cta-button" value="Create Room" style="width: 100%; text-align: center;">
            </div>
            <div class="auth-footer">
                <a href="game_room.php">Back to Lobby</a>
            </div>
        </form>
    </div>
</div>

<?php require_once "includes/footer.php"; ?>
