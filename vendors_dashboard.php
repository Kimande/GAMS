<?php
session_start();
ini_set('display_errors', 1);
error_reporting(E_ALL);

include 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: vendor_login.php");
    exit();
} else {
    $user_id = $_SESSION['user_id'];
}
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch vendor_id using user_id
$user_query = $conn->prepare("SELECT vendor_id FROM vendors WHERE user_id = ?");
$user_query->bind_param("i", $user_id);
$user_query->execute();
$user_result = $user_query->get_result();

if ($user_result->num_rows === 0) {
    die("User not associated with any vendor.");
}

$user_data = $user_result->fetch_assoc();
$vendor_id = $user_data['vendor_id'];

// Fetch vendor details using vendor_id
$vendor_query = $conn->prepare("SELECT * FROM vendors WHERE vendor_id = ?");
$vendor_query->bind_param("i", $vendor_id);
$vendor_query->execute();
$vendor_data = $vendor_query->get_result()->fetch_assoc();

if (!$vendor_data) {
    die("Vendor details not found.");
}

// Handle form submission for product upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_product'])) {
    $name = $_POST['name'];
    $description = $_POST['description'];
    $price = $_POST['price'];
    $image = $_FILES['product_image'];

    // Handle file upload
    $target_dir = "uploads/"; // Directory to save the uploaded image
    if (!is_dir($target_dir)) {
        mkdir($target_dir, 0777, true); // Create directory if it doesn't exist
    }

    $target_file = $target_dir . basename($image["name"]); // Full file path including the image name
    if ($image['error'] !== UPLOAD_ERR_OK) {
        die("File upload error: " . $image['error']);
    }

    if (!move_uploaded_file($image["tmp_name"], $target_file)) {
        die("Failed to upload the file.");
    }

    // Insert product details into the database
    $stmt = $conn->prepare("INSERT INTO products (vendor_id, name, description, price, product_image) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("issds", $vendor_id, $name, $description, $price, $target_file);

    if (!$stmt->execute()) {
        die("Error inserting product: " . $stmt->error);
    }

    header("Location: vendors_dashboard.php");
    exit();
}


// Handle product deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_product'])) {
    $product_id = $_POST['product_id']; // Get the product ID to delete
    $sql = "DELETE FROM products WHERE product_id = '$product_id'"; // SQL query to delete the product
    mysqli_query($conn, $sql); // Execute the delete query

    header("Location: vendors_dashboard.php");
    exit();
}

// Fetch vendor products
$product_stmt = $conn->prepare("SELECT * FROM products WHERE vendor_id = ?");
$product_stmt->bind_param("i", $vendor_id);
$product_stmt->execute();
$products = $product_stmt->get_result();

// Fetch customer feedback
$feedback_query = "SELECT feedback, rating, created_at FROM ratings WHERE vendor_id = '$vendor_id' ORDER BY created_at DESC";
$feedbackResult = $conn->query($feedback_query);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vendor Dashboard</title>
    <link rel="stylesheet" href="output.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="javascript/main.js"></script>
</head>
<body class="bg-gray-100 md:pt-16 font-sans leading-normal tracking-normal">
    <!-- Navbar -->
    <nav class="bg-green-600 p-4 fixed w-full top-0 z-10 shadow-md">
        <div class="container mx-auto flex justify-between items-center">
            <a href="#" class="text-2xl font-bold text-white">Vendor Dashboard</a>
             <!-- Hamburger icon for mobile -->
             <button id="hamburgerIcon" class="text-white md:hidden" onclick="toggleMobileMenu()">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                </svg>
            </button>
            <ul class=" hidden md:flex space-x-6 text-white font-semibold">
                <li><a href="index.php" class="hover:text-green-200 transition">Home</a></li>
                <li><a href="javascript:void(0);" onclick="toggleSection('feedbackSection')" class="hover:text-green-200 transition">Check Feedback</a></li>
                <li><a href="javascript:void(0);" onclick="toggleSection('uploadProduct')" class="hover:text-green-200 transition">Upload Product</a></li>
            </ul>
            <div class="relative">
            <button onclick="toggleDropdown()" class="flex items-center space-x-2 text-white hover:text-green-200 focus:outline-none">
            <img src="uploads/profile-user.png" alt="Profile Icon" class="w-auto h-16 rounded-full">
            <span>your profile</span>
        </button>
                <div id="profileMenu" class="hidden absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-lg">
                    <a href="javascript:void(0);" onclick="toggleSection('viewDetails')" class="block px-4 py-2 text-gray-700 hover:bg-gray-100">View Details</a>
                    <form action="vendor_login.php" method="POST" id="logout" class="block w-full">
                        <button type="submit" class="w-full text-left px-4 py-2 text-gray-700 hover:bg-gray-100">Logout</button>
                    </form>
                </div>
            </div>
        </div>
        <!-- Mobile menu (hidden by default) -->
    <div id="mobileMenu" class="hidden md:hidden bg-green-600 p-4 mt-4">
        <ul class="text-white font-semibold">
            <li><a href="index.php" class="block py-2">Home</a></li>
            <li><a href="javascript:void(0);" onclick="toggleSection('feedbackSection')" class="block py-2">Check Feedback</a></li>
            <li><a href="javascript:void(0);" onclick="toggleSection('uploadProduct')" class="block py-2">Upload Product</a></li>
        </ul>
    </div>

    </nav>

    <div class="container mx-auto px-2 py-4 mt-12">
        <!-- Vendor Profile Section -->
        <section id="viewDetails" class="bg-white p-6 w-1/2 rounded-lg shadow-lg hidden">
            <h2 class="text-2xl font-semibold mb-4">Vendor Profile</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                <div class="space-y-4">
                    <p><strong>Business Name:</strong> <?php echo htmlspecialchars($vendor_data['business_name']); ?></p>
                    <p><strong>County:</strong> <?php echo htmlspecialchars($vendor_data['county']); ?></p>
                    <p><strong>Location:</strong> <?php echo htmlspecialchars($vendor_data['location']); ?></p>
                    <p><strong>Address:</strong> <?php echo htmlspecialchars($vendor_data['address']); ?></p>
                    <p><strong>Phone Number:</strong> <?php echo htmlspecialchars($vendor_data['phone_number']); ?></p>
                    <p><strong>Services:</strong> <?php echo htmlspecialchars($vendor_data['services']); ?></p>
                    <p><strong>Approval Status:</strong> <?php echo htmlspecialchars($vendor_data['approval_status']); ?></p>
                </div>
            </div>
        </section>
        <!--logout-->
        

        <!-- Product Upload Section -->
        <section id="uploadProduct" class="bg-white p-6 mt-8 w-1/2 rounded-lg shadow-lg hidden">
            <h2 class="text-2xl font-semibold mb-4">Upload Product</h2>
            <form action="vendors_dashboard.php" method="post" enctype="multipart/form-data">
                <div class="mb-4">
                    <label for="name" class="block text-sm font-medium text-gray-700">Product Name</label>
                    <input type="text" name="name" class="mt-1 block w-1/2 border-gray-300 rounded-md shadow-sm" required>
                </div>
                <div class="mb-4">
                    <label for="description" class="block text-sm font-medium text-gray-700">Description</label>
                    <textarea name="description" class="mt-1 block w-1/2 border-gray-300 rounded-md shadow-sm" required></textarea>
                </div>
                <div class="mb-4">
                    <label for="price" class="block text-sm font-medium text-gray-700">Price</label>
                    <input type="number" name="price" class="mt-1 block w-1/2 border-gray-300 rounded-md shadow-sm" required>
                </div>
                <div class="mb-4">
                    <label for="product_image" class="block text-sm font-medium text-gray-700">Product Image</label>
                    <input type="file" name="product_image" accept="image/*" class="mt-1 block w-1/2" required>
                </div>
                <button type="submit" name="upload_product" class="bg-green-500 text-white py-2 px-4 rounded-md hover:bg-green-600 transition duration-300">Upload Product</button>
            </form>
        </section>

        <!-- Products Display Section -->
        <section id="productsSection" class="bg-white p-6 mt-8 w-1/2 rounded-lg shadow-lg">
            <h2 class="text-2xl font-semibold mb-4">Your Products</h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php while ($product = $products->fetch_assoc()) { ?>
                <div class="bg-gray-100 p-2 rounded-lg shadow-md">
                    <img src="<?php echo htmlspecialchars($product['product_image']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" class=" h-auto w-auto max-w-full max-h-32 object-cover rounded-t-lg">
                    <div class="p-4">
                        <h3 class="font-bold text-lg"><?php echo htmlspecialchars($product['name']); ?></h3>
                        <p class="text-sm text-gray-700 mt-1"><?php echo htmlspecialchars($product['description']); ?></p>
                        <p class="font-semibold text-green-500 mt-2">$<?php echo htmlspecialchars($product['price']); ?></p>
                        <div class="mt-4 flex space-x-2">
                            <form action="vendors_dashboard.php" method="POST">
                                <input type="hidden" name="product_id" value="<?php echo $product['product_id']; ?>">
                                <button type="submit" name="delete_product" class="bg-red-500 text-white py-1 px-3 rounded-md hover:bg-red-600 transition">Delete</button>
                            </form>
                        </div>
                    </div>
                </div>
                <?php } ?>
            </div>
        </section>

        <!-- Customer Feedback Section -->
        <section id="feedbackSection" class="bg-white p-4 mt-6  w-1/2 rounded-lg shadow-lg hidden">
            <h2 class="text-2xl font-semibold mb-4">Customer Feedback</h2>
            <?php if ($feedbackResult->num_rows > 0) { ?>
            <ul>
                <?php while ($feedback = $feedbackResult->fetch_assoc()) { ?>
                <li class="border-b border-gray-300 py-4">
                    <p><strong>Rating:</strong> <?php echo htmlspecialchars($feedback['rating']); ?>/5</p>
                    <p><strong>Feedback:</strong> <?php echo htmlspecialchars($feedback['feedback']); ?></p>
                    <p class="text-xs text-gray-500"><?php echo htmlspecialchars($feedback['created_at']); ?></p>
                </li>
                <?php } ?>
            </ul>
            <?php } else { ?>
            <p>No feedback available at this time.</p>
            <?php } ?>
        </section>
    </div>
</body>
</html>