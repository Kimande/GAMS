<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Process the form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    include('config.php'); // Database connection

    // Sanitize and validate inputs
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $email = filter_var(trim($_POST['email']), FILTER_VALIDATE_EMAIL);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $role = $_POST['role']; // 'user' or 'vendor'

    // Check required fields
    if (!$first_name || !$last_name || !$email || !$password || !$confirm_password || !$role) {
        $error = "All fields are required.";
    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match.";
    } else {
        // Hash the password for security
        $hashed_password = password_hash($password, PASSWORD_BCRYPT);

        // Insert user data securely
        $stmt_user = $conn->prepare("INSERT INTO users (first_name, last_name, email, password, role) VALUES (?, ?, ?, ?, ?)");
        $stmt_user->bind_param("sssss", $first_name, $last_name, $email, $hashed_password, $role);

        if ($stmt_user->execute()) {
            $user_id = $stmt_user->insert_id;

            if ($role === 'vendor') {
                // Handle vendor-specific data
                $business_name = trim($_POST['business_name']);
                $county = trim($_POST['county']);
                $location = trim($_POST['location']);
                $address = trim($_POST['address']);
                $phone_number = trim($_POST['phone_number']);
                $services = trim($_POST['services']);

                // Validate file upload
                if (isset($_FILES['license_doc']) && $_FILES['license_doc']['error'] === UPLOAD_ERR_OK) {
                    $allowed_types = ['pdf', 'jpg', 'jpeg', 'png'];
                    $file_info = pathinfo($_FILES['license_doc']['name']);
                    $file_extension = strtolower($file_info['extension']);
                    $unique_file_name = uniqid() . "." . $file_extension;
                    $upload_dir = "uploads/";
                    $target_file = $upload_dir . $unique_file_name;

                    if (in_array($file_extension, $allowed_types) && move_uploaded_file($_FILES['license_doc']['tmp_name'], $target_file)) {
                        $approval_status = "pending";

                        // Insert vendor details
                        $stmt_vendor = $conn->prepare(
                            "INSERT INTO vendors (user_id, business_name, county, location, address, phone_number, services, license_doc, approval_status) 
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)"
                        );
                        $stmt_vendor->bind_param(
                            "issssssss",
                            $user_id,
                            $business_name,
                            $county,
                            $location,
                            $address,
                            $phone_number,
                            $services,
                            $unique_file_name,
                            $approval_status
                        );

                        if ($stmt_vendor->execute()) {
                            header("Location: vendor_login.php");
                            exit();
                        } else {
                            $error = "Error adding vendor details: " . $stmt_vendor->error;
                        }
                    } else {
                        $error = "Invalid file type or upload failed.";
                    }
                } else {
                    $error = "No license document uploaded or an error occurred.";
                }
            } else {
                // Redirect for user registration
                header("Location: customer_login.php");
                exit();
            }
        } else {
            $error = "Error: " . $stmt_user->error;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Signup</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100 flex items-center justify-center min-h-screen">

    <div class="w-full max-w-lg p-6 bg-white shadow-lg rounded-lg space-y-6">
        <h2 class="text-3xl font-semibold text-gray-800 text-center">Sign Up</h2>

        <?php if (isset($error)): ?>
            <div class="bg-red-200 text-red-800 p-4 rounded">
                <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <form id="userForm" method="post" action="" enctype="multipart/form-data" class="space-y-6">
            <!-- Role Selection -->
            <div class="text-center">
                <span class="block text-gray-700 font-medium mb-2">Register as:</span>
                <div class="flex justify-center space-x-8">
                    <label class="flex items-center space-x-2">
                        <input type="radio" name="role" value="user" class="form-radio h-5 w-5 text-green-500" onchange="checkRole()" required>
                        <span class="text-gray-700">User</span>
                    </label>
                    <label class="flex items-center space-x-2">
                        <input type="radio" name="role" value="vendor" class="form-radio h-5 w-5 text-green-500" onchange="checkRole()" required>
                        <span class="text-gray-700">Vendor</span>
                    </label>
                </div>
            </div>

            <!-- Name, Email, Password Fields -->
            <div class="flex flex-col md:flex-row md:space-x-4">
                <input type="text" name="first_name" placeholder="First Name" class="w-full p-3 border border-gray-300 rounded-md" required>
                <input type="text" name="last_name" placeholder="Last Name" class="w-full p-3 border border-gray-300 rounded-md" required>
            </div>
            <input type="email" name="email" placeholder="Email" class="w-full p-3 border border-gray-300 rounded-md" required>
            <div class="flex flex-col md:flex-row md:space-x-4">
                <input type="password" name="password" placeholder="Password" class="w-full p-3 border border-gray-300 rounded-md" required>
                <input type="password" name="confirm_password" placeholder="Confirm Password" class="w-full p-3 border border-gray-300 rounded-md" required>
            </div>

            <!-- Vendor-Specific Details -->
            <div id="vendorDetails" style="display: none;" class="space-y-4">
                <input type="text" name="business_name" placeholder="Business Name" class="w-full p-3 border border-gray-300 rounded-md">
                <select name="county" class="w-full p-3 border border-gray-300 rounded-md">
                    <option value="">Select County</option>
                    <option value="Nairobi">Nairobi</option>
                    <option value="Kiambu">Kiambu</option>
                    <option value="Machakos">Machakos</option>
                    <option value="Kajiado">Kajiado</option>
                </select>
                <select name="location" class="w-full p-3 border border-gray-300 rounded-md">
                    <option value="">Select Location</option>
                    <optgroup label="Nairobi">
                        <option value="Westlands">Westlands</option>
                        <option value="Kilimani">Kilimani</option>
                        <option value="Kasarani">Kasarani</option>
                        <option value="Ruaraka">Ruaraka</option>
                    </optgroup>
                </select>
                <input type="text" name="address" placeholder="Address" class="w-full p-3 border border-gray-300 rounded-md">
                <input type="text" name="phone_number" placeholder="Phone Number" class="w-full p-3 border border-gray-300 rounded-md">
                <input type="text" name="services" placeholder="Services" class="w-full p-3 border border-gray-300 rounded-md">
                <input type="file" name="license_doc" class="w-full text-sm text-gray-500" accept=".pdf,.jpg,.jpeg,.png">
            </div>

            <!-- Submit Button -->
            <button type="submit" id="submitButton" class="w-full bg-green-500 text-white font-semibold py-3 rounded-md" disabled>Sign Up</button>
        </form>
    </div>

    <script>
        function checkRole() {
            const role = document.querySelector('input[name="role"]:checked')?.value;
            const vendorDetails = document.getElementById("vendorDetails");
            vendorDetails.style.display = role === "vendor" ? "block" : "none";
            checkFormValidity();
        }

        function checkFormValidity() {
            const role = document.querySelector('input[name="role"]:checked')?.value;
            const requiredFields = Array.from(document.querySelectorAll("input[required], select[required]"));
            let isValid = requiredFields.every(field => field.value.trim() !== "");

            if (role === "vendor") {
                const licenseDoc = document.querySelector('input[name="license_doc"]');
                isValid = isValid && licenseDoc.files.length > 0;
            }

            document.getElementById("submitButton").disabled = !isValid;
        }

        document.getElementById("userForm").addEventListener("input", checkFormValidity);
        document.querySelectorAll('input[name="role"]').forEach(input => input.addEventListener("change", checkRole));
    </script>
</body>
</html>


