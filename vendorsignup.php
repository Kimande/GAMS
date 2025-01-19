<?php
session_start();
@include('config.php'); // Update with your database credentials

if (isset($_POST['sign_up'])) {
    //input validation
    // Validate mandatory fields
$required_fields = ['first_name', 'last_name', 'email', 'password', 'confirm_password'];
foreach ($required_fields as $field) {
    if (empty($_POST[$field])) {
        $errors[] = ucfirst($field) . " is required.";
    }
}

// Email validation
if (!filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
    $errors[] = "Invalid email format.";
}

// Phone number validation (if present)
if (!empty($_POST['phone_number']) && (!is_numeric($_POST['phone_number']) || strlen($_POST['phone_number']) !== 10)) {
    $errors[] = "Phone number must be 10 digits.";
}

// Password validation
if (strlen($_POST['password']) < 6) {
    $errors[] = "Password must be at least 6 characters.";
}

if ($_POST['password'] !== $_POST['confirm_password']) {
    $errors[] = "Passwords do not match.";
}

// Stop execution if there are errors
if (!empty($errors)) {
    foreach ($errors as $error) {
        echo "<div class='error'>$error</div>";
    }
    return;
}

    // Retrieve posted data
    $business_name = $_POST['business_name'];
    $county = $_POST['county'];
    $location = $_POST['location'];
    $address = $_POST['address'];
    $phone_number = $_POST['phone_number'];
    $services = $_POST['services'];
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT); // Secure password
    $license_doc = $_FILES['license_doc'];

    // Validate the uploaded file
    $target_dir = "uploads/licenses";
    $target_file = $target_dir . basename($license_doc['name']);
    $uploadOk = 1;
    $fileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

    // Check if file is a valid document (you can add more extensions if needed)
    $allowedTypes = ['pdf', 'jpg', 'jpeg', 'png',]; // Add your allowed file types here
    if (!in_array($fileType, $allowedTypes)) {
        echo "Sorry, only PDF, JPG, JPEG, and PNG files are allowed.";
        $uploadOk = 0;
    }

    // Check file size (limit to 2MB for example)
    if ($license_doc['size'] > 2 * 1024 * 1024) {
        echo "Sorry, your file is too large. Maximum file size is 2MB.";
        $uploadOk = 0;
    }

    // Proceed with uploading if there are no issues
    if ($uploadOk === 1) {
        // Prepare the user insertion SQL statement
        $sql_user = "INSERT INTO users (email, password) VALUES (?, ?)";
        
        if ($stmt_user = $conn->prepare($sql_user)) {
            // Bind the parameters for user insertion
            $stmt_user->bind_param("ss", $email, $password);

            if ($stmt_user->execute()) {
                $user_id = $stmt_user->insert_id; // Get the last inserted user_id

                // Prepare the vendor insertion SQL statement with approval status
                $sql_vendor = "INSERT INTO vendors (user_id, business_name, county, location, address, phone_number, services, license_doc, approval_status)
                               VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'Pending')";
                if ($stmt_vendor = $conn->prepare($sql_vendor)) {
                    // Move uploaded file to the target directory
                    if (move_uploaded_file($license_doc['tmp_name'], $target_file)) {
                        // Bind the parameters for vendor insertion
                        $stmt_vendor->bind_param("issssssss", $user_id, $business_name, $county, $location, $address, $phone_number, $services, $license_doc['name']);

                        if ($stmt_vendor->execute()) {
                            $_SESSION['flash_message'] = "Registration successful! Await admin approval.";
                            header("Location: vendor_login.php"); // Redirect to vendor login page
                            exit();
                        } else {
                            echo "Vendor insertion failed: " . $stmt_vendor->error;
                        }
                    } else {
                        echo "Sorry, there was an error uploading your file.";
                    }
                    $stmt_vendor->close(); // Close the vendor statement
                } else {
                    echo "Vendor statement preparation failed: " . $conn->error;
                }
            } else {
                echo "User insertion failed: " . $stmt_user->error;
            }
            $stmt_user->close(); // Close the user statement
        } else {
            echo "User statement preparation failed: " . $conn->error;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vendor Signup</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100 flex items-center justify-center min-h-screen">

    <div class="w-full max-w-lg p-6 md:p-8 bg-white shadow-lg rounded-lg mx-4 md:mx-0 space-y-6">
        <h2 class="text-3xl font-semibold text-gray-800 text-center mb-4">Vendor Signup</h2>
        
        <form name="signupForm" action="vendor_signup.php" method="post" enctype="multipart/form-data" class="space-y-6">
            <label for="business_name" class="block text-gray-700">Business Name:</label>
            <input type="text" id="business_name" name="business_name" class="w-full p-3 border border-gray-300 rounded-md" required>

            <label for="county" class="block text-gray-700">County:</label>
            <select id="county" name="county" class="w-full p-3 border border-gray-300 rounded-md" required>
                <option value="">Select a County</option>
                <option value="Nairobi">Nairobi</option>
                <option value="Kiambu">Kiambu</option>
                <option value="Machakos">Machakos</option>
                <option value="Kajiado">Kajiado</option>
            </select>

            <label for="location" class="block text-gray-700">Location:</label>
            <select id="location" name="location" class="w-full p-3 border border-gray-300 rounded-md" required>
                <option value="">Select a Location</option>
                <!-- Nairobi Locations -->
                <optgroup label="Nairobi">
                    <option value="Westlands">Westlands</option>
                    <option value="Kilimani">Kilimani</option>
                    <option value="Kasarani">Kasarani</option>
                    <option value="Langata">Langata</option>
                    <option value="Ruiru">Ruiru</option>
                    <option value="Ruaraka">Ruaraka</option>
                </optgroup>
                <!-- Kiambu Locations -->
                <optgroup label="Kiambu">
                    <option value="Thika">Thika</option>
                    <option value="Ruiru">Ruiru</option>
                    <option value="Limuru">Limuru</option>
                    <option value="Githunguri">Githunguri</option>
                </optgroup>
                <!-- Machakos Locations -->
                <optgroup label="Machakos">
                    <option value="Athi River">Athi River</option>
                    <option value="Machakos Town">Machakos Town</option>
                </optgroup>
                <!-- Kajiado Locations -->
                <optgroup label="Kajiado">
                    <option value="Kitengela">Kitengela</option>
                    <option value="Ongata Rongai">Ongata Rongai</option>
                </optgroup>
            </select>

            <label for="address" class="block text-gray-700">Address:</label>
            <input type="text" id="address" name="address" class="w-full p-3 border border-gray-300 rounded-md" required>

            <label for="phone_number" class="block text-gray-700">Phone Number:</label>
            <input type="text" id="phone_number" name="phone_number" class="w-full p-3 border border-gray-300 rounded-md" required>

            <label for="services" class="block text-gray-700">Services:</label>
            <textarea id="services" name="services" class="w-full p-3 border border-gray-300 rounded-md" required></textarea>

            <label for="email" class="block text-gray-700">Email:</label>
            <input type="email" id="email" name="email" class="w-full p-3 border border-gray-300 rounded-md" required>

            <label for="password" class="block text-gray-700">Password:</label>
            <input type="password" id="password" name="password" class="w-full p-3 border border-gray-300 rounded-md" required>

            <label for="license_doc" class="block text-gray-700">License Document:</label>
            <input type="file" id="license_doc" name="license_doc" class="w-full text-sm text-gray-500 rounded-md border" required>

            <button type="submit" name="sign_up" class="w-full bg-green-500 hover:bg-green-600 text-white font-semibold py-3 rounded-md transition duration-200">
                Sign Up
            </button>
        </form>

        <div class="text-center">
            <p class="text-gray-600">Already have an account? 
                <a href="vendor_login.php" class="text-green-500 hover:underline">Login Here</a>
            </p>
        </div>


    </div>

</body>
</html>
