    <?php
    @include('config.php');
    ob_start();
    session_start();
    
    error_reporting(E_ALL);

    if (isset($_POST['login'])) {
        $email = $_POST['email'];
        $password = $_POST['password'];

        $sql = "SELECT * FROM users WHERE email=?";
        $stmt = $conn->prepare($sql);
        if ($stmt === false) {
            die('Prepare failed: ' . htmlspecialchars($conn->error));
        }
        
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();

            // Check if password matches
            if (password_verify($password, $row['password'])) {
                // Set session variables
                $_SESSION['user_id'] = $row['user_id'];
                $_SESSION['email'] = $row['email'];
                $_SESSION['role'] = $row['role'];

                // Set session variables
                $_SESSION['user_id'] = $row['user_id'];
                $_SESSION['role'] = $row['role'];

                // Debugging the role
                var_dump($row['role']); // Remove this after confirming the role is correct

                // Redirect based on role
                if ($row['role'] == 'vendor') {
                    header('Location: vendors_dashboard.php');
                } else {
                    header('Location: customer_dashboard.php');
                }
                exit;
            } else {
                $error_message = "Invalid password.";
            }
        } else {
            $error_message = "No user found.";
        }

        $stmt->close();
    }

    $conn->close();
    ?>

    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Login</title>
        <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    </head>
    <body class="bg-gray-50 min-h-screen flex items-center justify-center">
    <div class="bg-white shadow-lg rounded-lg w-full max-w-sm p-8 mx-4">
        <h2 class="text-2xl font-bold text-center mb-6 text-gray-700">Welcome Back!</h2>
        
        <?php if (!empty($error_message)): ?>
            <p class="text-red-500 text-center mb-4"><?php echo $error_message; ?></p>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="mb-4">
                <label for="email" class="block text-gray-600 text-sm font-medium mb-1">Email</label>
                <input type="email" name="email" id="email" class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:border-blue-500" required>
            </div>
            <div class="mb-6">
                <label for="password" class="block text-gray-600 text-sm font-medium mb-1">Password</label>
                <input type="password" name="password" id="password" class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:border-blue-500" required>
            </div>
            <button type="submit" name="login" class="w-full bg-blue-600 hover:bg-blue-700 text-white py-2 rounded-md font-semibold focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-opacity-50 transition duration-150 ease-in-out">Login</button>
        </form>

        <div class="text-center mt-6">
            <p class="text-gray-500 text-sm">Don't have an account? <a href="user_signup.php" class="text-blue-500 hover:text-blue-600 font-medium">Sign up</a></p>
        </div>
    </div>
</body>
    </html>
