<?php
require_once "includes/config.php";
require_once "includes/header.php";

// Check if the user is already logged in, if yes then redirect him to welcome page
if(isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true){
    echo "<script>window.location.href='index.php';</script>";
    exit;
}
 
$username = $password = "";
$username_err = $password_err = "";
 
if($_SERVER["REQUEST_METHOD"] == "POST"){
 
    if(empty(trim($_POST["username"]))){
        $username_err = "Please enter username.";
    } else{
        $username = trim($_POST["username"]);
    }
    
    if(empty(trim($_POST["password"]))){
        $password_err = "Please enter your password.";
    } else{
        $password = trim($_POST["password"]);
    }
    
    if(empty($username_err) && empty($password_err)){
        $escaped_username = mysqli_real_escape_string($link, $username);
        $sql = "SELECT id, username, password FROM users WHERE username = '$escaped_username'";
        
        $result = mysqli_query($link, $sql);
        if($result){
            if(mysqli_num_rows($result) == 1){
                $row = mysqli_fetch_assoc($result);
                $id = $row['id'];
                $username = $row['username'];
                $hashed_password = $row['password'];
                
                if(password_verify($password, $hashed_password)){
                    // Password is correct, so data is already in session
                    // Store data in session variables
                    $_SESSION["loggedin"] = true;
                    $_SESSION["id"] = $id;
                    $_SESSION["username"] = $username;                            
                    
                    // Save session and redirect user to welcome page
                    session_write_close();
                    echo "<script>window.location.href='index.php';</script>";
                } else{
                    $password_err = "The password you entered was not valid.";
                }
            } else{
                $username_err = "No account found with that username.";
            }
        } else{
            echo "Oops! Something went wrong. Please try again later. " . mysqli_error($link);
        }
    }
}
?>

<div class="auth-wrapper">
    <div class="auth-card">
        <h2>Login</h2>
        <p style="text-align: center; margin-bottom: 2rem; color: var(--white-dark);">Please fill in your credentials to login.</p>
        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
            <div class="form-group">
                <label>Username</label>
                <input type="text" name="username" class="form-control" value="<?php echo $username; ?>">
                <span class="help-block"><?php echo $username_err; ?></span>
            </div>    
            <div class="form-group">
                <label>Password</label>
                <div class="password-wrapper">
                    <input type="password" name="password" class="form-control" id="password">
                    <button type="button" class="password-toggle" onclick="togglePassword('password', this)">Show</button>
                </div>
                <span class="help-block"><?php echo $password_err; ?></span>
            </div>
            <div class="form-group">
                <input type="submit" class="cta-button" value="Login" style="width: 100%; text-align: center;">
            </div>
            <div class="auth-footer">
                <p>Don't have an account? <a href="signup.php">Sign up now</a>.</p>
            </div>
        </form>
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
    </div>
</div>

<?php require_once "includes/footer.php"; ?>
