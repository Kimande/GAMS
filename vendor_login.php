<?php
session_start();

// Enable error reporting for troubleshooting
ini_set('display_errors', 1);
error_reporting(E_ALL);

@include('config.php');

// Check for form submission (Login)
if (isset($_POST['login'])) {
    // Retrieve posted data for login
    $email = $_POST['email'];
    $password = $_POST['password'];

    // Prepare and execute query to check user credentials
    $sql = "SELECT u.user_id, v.approval_status, u.password FROM users u 
            JOIN vendors v ON u.user_id = v.user_id WHERE u.email = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    // Check query results
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $user_id = $row['user_id']; // Ensure using correct field name 'user_id'
        $approval_status = $row['approval_status'];

        // Verify password
        if (password_verify($password, $row['password'])) {
            if ($approval_status === 'Approved') {
                // Allow login
                $_SESSION['user_id'] = $user_id;
                
                header("Location: vendors_dashboard.php");
                exit(); // Ensure script stops after redirection
            } elseif ($approval_status === 'Pending') {
                // Vendor not yet approved
                $_SESSION['error_message'] = "Your vendor registration status is: <strong>Pending</strong>. Please wait for admin approval.";
            } elseif ($approval_status === 'Rejected') {
                // Vendor rejected
                $_SESSION['error_message'] = "Your vendor registration status is: <strong>Rejected</strong>. Contact support for assistance.";
            }
        } else {
            // Invalid password
            $_SESSION['error_message'] = "Invalid email or password.";
        }
    } else {
        // User not found
        $_SESSION['error_message'] = "Invalid email or password.";
    }
}

// Handle password recovery
if (isset($_POST['recover'])) {
    $email = $_POST['email'];

    // Check if the email exists
    $sql = "SELECT u.id FROM users u JOIN vendors v ON u.id = v.user_id WHERE u.email = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        $user_id = $user['id'];

        // Generate a password reset token
        $token = bin2hex(random_bytes(50)); // Generate a secure token
        $expires = date("U") + 1800; // Token expires in 30 minutes

        // Insert the token into the database
        $sql = "INSERT INTO password_resets (user_id, token, expires) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("isi", $user_id, $token, $expires);
        if ($stmt->execute()) {
            // Send email to user with the reset link
            $reset_link = "http://yourwebsite.com/reset_password.php?token=" . $token; // Adjust to your actual domain
            // Uncomment the following line to send an email (use PHPMailer for better results)
            // mail($email, "Password Reset", "Click this link to reset your password: " . $reset_link);

            $_SESSION['flash_message'] = "A password reset link has been sent to your email.";
            header("Location: vendors_dashboard.php");
            exit(); // Ensure redirection occurs after password reset
        } else {
            $_SESSION['error_message'] = "There was an error processing your request.";
        }
    } else {
        $_SESSION['error_message'] = "No account found with that email address.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Vendor Login</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <script>
        function validateForm() {
            let email = document.forms["loginForm"]["email"].value;
            let password = document.forms["loginForm"]["password"].value;
            if (email === "" || password === "") {
                alert("Email and password must be filled out");
                return false;
            }
            return true;
        }
    </script>
</head>
<body class="bg-gray-50 min-h-screen flex items-center justify-center">

    <div class="bg-white shadow-lg rounded-lg w-full max-w-sm p-8 mx-4">
        <h2 class="text-2xl font-bold text-center mb-6 text-gray-700">Vendor Login</h2>

        <?php if (isset($_SESSION['error_message'])): ?>
            <div class="bg-red-200 text-red-700 p-3 rounded mb-4">
                <?php echo $_SESSION['error_message']; ?>
                <?php unset($_SESSION['error_message']); ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['flash_message'])): ?>
            <div class="bg-green-200 text-green-700 p-3 rounded mb-4">
                <?php echo $_SESSION['flash_message']; ?>
                <?php unset($_SESSION['flash_message']); ?>
            </div>
        <?php endif; ?>

        <form name="loginForm" action="vendor_login.php" method="post" onsubmit="return validateForm()">
            <div class="mb-4">
                <label for="email" class="block text-gray-700">Email:</label>
                <input type="email" id="email" name="email" required class="border rounded w-full py-2 px-3" />
            </div>
            <div class="mb-4">
                <label for="password" class="block text-gray-700">Password:</label>
                <input type="password" id="password" name="password" required class="border rounded w-full py-2 px-3" />
            </div>
            <button type="submit" name="login" class="bg-blue-500 text-white py-2 px-4 rounded">Login</button>
        </form>

        <div class="mt-4">
            <a href="#" class="text-blue-500 hover:underline" onclick="toggleRecoveryForm()">Forgot your password?</a>
        </div>

        <div id="recoveryForm" class="hidden mt-4">
            <h3 class="text-lg font-semibold mb-2">Password Recovery</h3>
            <form action="vendor_login.php" method="post">
                <div class="mb-4">
                    <label for="recovery_email" class="block text-gray-700">Enter your email:</label>
                    <input type="email" id="recovery_email" name="email" required class="border rounded w-full py-2 px-3" />
                </div>
                <button type="submit" name="recover" class="bg-blue-500 text-white py-2 px-4 rounded">Send Recovery Link</button>
            </form>
        </div>
    </div>

    <script>
        function toggleRecoveryForm() {
            const recoveryForm = document.getElementById('recoveryForm');
            recoveryForm.classList.toggle('hidden');
        }
    </script>
</body>
</html>
