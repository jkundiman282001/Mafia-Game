<?php
require_once "includes/config.php";
require_once "includes/header.php";

$username = $password = $confirm_password = "";
$username_err = $password_err = $confirm_password_err = "";

if($_SERVER["REQUEST_METHOD"] == "POST"){
 
    // Validate username
    if(empty(trim($_POST["username"]))){
        $username_err = "Please enter a username.";
    } elseif(!preg_match('/^[a-zA-Z0-9_]+$/', trim($_POST["username"]))){
        $username_err = "Username can only contain letters, numbers, and underscores.";
    } else{
        $input_username = trim($_POST["username"]);
        $escaped_username = mysqli_real_escape_string($link, $input_username);
        $sql = "SELECT id FROM users WHERE username = '$escaped_username'";
        
        $result = mysqli_query($link, $sql);
        if($result){
            if(mysqli_num_rows($result) == 1){
                $username_err = "This username is already taken.";
            } else{
                $username = $input_username;
            }
        } else{
            echo "Oops! Something went wrong. Please try again later. " . mysqli_error($link);
        }
    }
    
    // Validate password
    if(empty(trim($_POST["password"]))){
        $password_err = "Please enter a password.";     
    } elseif(strlen(trim($_POST["password"])) < 6){
        $password_err = "Password must have at least 6 characters.";
    } else{
        $password = trim($_POST["password"]);
    }
    
    // Validate confirm password
    if(empty(trim($_POST["confirm_password"]))){
        $confirm_password_err = "Please confirm password.";     
    } else{
        $confirm_password = trim($_POST["confirm_password"]);
        if(empty($password_err) && ($password != $confirm_password)){
            $confirm_password_err = "Password did not match.";
        }
    }
    
    // Check input errors before inserting in database
    if(empty($username_err) && empty($password_err) && empty($confirm_password_err)){
        
        $escaped_username = mysqli_real_escape_string($link, $username);
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $sql = "INSERT INTO users (username, password) VALUES ('$escaped_username', '$hashed_password')";
         
        if(mysqli_query($link, $sql)){
            // Redirect to login page
            echo "<script>window.location.href='login.php';</script>";
            exit;
        } else{
            echo "Something went wrong. Please try again later. " . mysqli_error($link);
        }
    }
}
?>

<div class="auth-wrapper">
    <div class="auth-card">
        <h2>Sign Up</h2>
        <p style="text-align: center; margin-bottom: 2rem; color: var(--white-dark);">Please fill this form to create an account.</p>
        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
            <div class="form-group">
                <label>Username</label>
                <input type="text" name="username" class="form-control" value="<?php echo $username; ?>">
                <span class="help-block"><?php echo $username_err; ?></span>
            </div>    
            <div class="form-group">
                <label>Password</label>
                <div class="password-wrapper">
                    <input type="password" name="password" class="form-control" value="<?php echo $password; ?>" id="password">
                    <button type="button" class="password-toggle" onclick="togglePassword('password', this)">Show</button>
                </div>
                <span class="help-block"><?php echo $password_err; ?></span>
            </div>
            <div class="form-group">
                <label>Confirm Password</label>
                <div class="password-wrapper">
                    <input type="password" name="confirm_password" class="form-control" value="<?php echo $confirm_password; ?>" id="confirm_password">
                    <button type="button" class="password-toggle" onclick="togglePassword('confirm_password', this)">Show</button>
                </div>
                <span class="help-block"><?php echo $confirm_password_err; ?></span>
            </div>
            <div class="form-group">
                <input type="submit" class="cta-button" value="Submit" style="width: 100%; text-align: center;">
            </div>
            <div class="auth-footer">
                <p>Already have an account? <a href="login.php">Login here</a>.</p>
            </div>
        </form>
    </div>
</div>

<script>
    function togglePassword(inputId, btn) {
        const input = document.getElementById(inputId);
        if (input.type === "password") {
            input.type = "text";
            btn.textContent = "Hide";
        } else {
            input.type = "password";
            btn.textContent = "Show";
        }
    }
</script>
<?php require_once "includes/footer.php"; ?>
