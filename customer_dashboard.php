<?php
session_start();
include 'config.php';
$vendors = [];
$message = "";
$location = "";
$feedbacks = [];

if (!isset($_SESSION['user_id'])) {
    header("Location: customer_login.php");
    exit;
} 
$user_id = $_SESSION['user_id'];

$user_query = $conn->prepare("SELECT user_id FROM users WHERE user_id = ?");
$user_query->bind_param("i", $user_id);
$user_query->execute();
$user_result = $user_query->get_result();

if ($user_result->num_rows === 0) {
    die("User not associated with any user.");
}

$users_data = $user_result->fetch_assoc();
$user_id = $users_data['user_id'];
// Fetch user details from the database
$userQuery = "SELECT first_name, last_name, email, password FROM users WHERE user_id = '$user_id'";
$userResult = mysqli_query($conn, $userQuery);

if ($userResult && mysqli_num_rows($userResult) > 0) {
    $users_data = mysqli_fetch_assoc($userResult);
} else {
    $users_data = [];
    echo "User details not found.";
    exit;
}




// Handle vendor search by location for each customer
if (isset($_GET['location'])) {
    $location = mysqli_real_escape_string($conn, $_GET['location']);

    // Query to fetch vendors specific to the customer's location
    $vendorQuery = "SELECT business_name, county, location, address, phone_number, services 
                    FROM vendors 
                    WHERE location LIKE '%$location%' AND approval_status = 'Approved'";
    $vendorResult = mysqli_query($conn, $vendorQuery);

    if (mysqli_num_rows($vendorResult) > 0) {
        $vendors = mysqli_fetch_all($vendorResult, MYSQLI_ASSOC);
    } else {
        $message = "No vendors found for the location: $location";
    }
}

// Fetch products from the database
$productQuery = "SELECT product_id, name, description, price, product_image FROM products";
$productResult = mysqli_query($conn, $productQuery);

// Fetch feedbacks with business name
$feedbackQuery = "SELECT f.rating, f.feedback, v.business_name, CONCAT(u.first_name, ' ', u.last_name) AS full_name 
                  FROM ratings f 
                  JOIN users u ON f.user_id = u.user_id
                  JOIN vendors v ON f.vendor_id = v.vendor_id"; // Join with vendors to get business name
$feedbackResult = mysqli_query($conn, $feedbackQuery);

if ($feedbackResult && mysqli_num_rows($feedbackResult) > 0) {
    $feedbacks = mysqli_fetch_all($feedbackResult, MYSQLI_ASSOC);
} else {
    $feedbacks = [];
}

// Handle feedback submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_SESSION['user_id'])) {
        echo "You must be logged in to submit feedback.";
        exit;
    }
    $user_id = $_SESSION['user_id'];
    $business_name = mysqli_real_escape_string($conn, $_POST['business_name']);  // Get the business name input
    $rating = $_POST['rating'];
    $feedback = mysqli_real_escape_string($conn, $_POST['feedback']);
    $created_at = date('Y-m-d H:i:s');

    // validation if input is skipped
    if (empty($business_name) || empty($rating) || empty($feedback)) {
        echo "All fields are required.";
        exit;
    }

    // Fetch vendor_id based on business name
    $vendorQuery = "SELECT vendor_id FROM vendors WHERE business_name = '$business_name' AND approval_status = 'Approved'";
    $vendorResult = mysqli_query($conn, $vendorQuery);

    if (mysqli_num_rows($vendorResult) > 0) {
        $vendor = mysqli_fetch_assoc($vendorResult);
        $vendor_id = $vendor['vendor_id'];

        // Insert feedback into the database
        $query = "INSERT INTO ratings (user_id, vendor_id, rating, feedback, created_at) 
                  VALUES ('$user_id', '$vendor_id', '$rating', '$feedback', '$created_at')";

        if (mysqli_query($conn, $query)) {
            echo "Feedback submitted successfully!";
            header("Location: user_dashboard.php");
            exit;
        } else {
            echo "Error: " . mysqli_error($conn);
        }
    } else {
        echo "Vendor not found.";
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100">

    <!-- Navbar -->
    <nav class="bg-green-500 p-4 flex justify-between items-center">
        <h2 class="text-white text-lg font-semibold">Customer Dashboard</h2>
        <div class="md:hidden">
                <button id="menu-btn" class="text-white focus:outline-none">
                    <svg id="hamburger-icon" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16m-7 6h7"></path>
                    </svg>
                    <svg id="close-icon" class="w-6 h-6 hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
        <ul id="menu" class="hidden md:flex space-x-4 text-lg">
                <li><a href="index.php" class="hover:underline hover:text-red-500">Home</a></li>
                <li><a href="#feedback" class="hover:underline hover:text-red-500">check feedback</a></li>
            </ul>
        <div class="flex items-center space-x-4">
    
            <!-- Search Bar -->
            <form action="" method="GET" class="flex">
                
                <input type="text" name="location" placeholder="Enter your location" required class="p-2 rounded-l-md">
                <button type="submit" class="bg-white text-green-500 p-3 rounded-r-md">Search</button>
            </form>
        </div>
        <div class="relative">
        <button onclick="toggleDropdown()" class="flex items-center space-x-2 text-white hover:text-green-200 focus:outline-none">
            <img src="uploads/profile-user.png" alt="Profile Icon" class="w-auto h-16 rounded-full">
            <span>your profile</span>
        </button>
        <div id="profileMenu" class="hidden absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-lg">
            <a href="javascript:void(0);" onclick="toggleSection('viewDetails')" class="block px-4 py-2 text-gray-700 hover:bg-gray-100">View Details</a>
            <form action="customer_login.php" method="POST" class="block w-full">
                <button type="submit" class="w-full text-left px-4 py-2 text-gray-700 hover:bg-gray-100">Logout</button>
            </form>
        </div>
        <!-- Mobile menu -->
        <div id="mobile-menu" class="hidden md:hidden">
            <ul class="flex flex-col space-y-2 text-lg p-4">
            <li><a href="index.php" class="hover:underline hover:text-red-500">Home</a></li>
            <li><a href="#feedback" class="hover:underline hover:text-red-500">check feedback</a></li>
            </ul>
            <button onclick="toggleDropdown()" class="flex items-center space-x-2 text-white hover:text-green-200 focus:outline-none">
            <img src="uploads/profile-user.png" alt="Profile Icon" class="w-auto h-16 rounded-full">
            <span>your profile</span>
        </button>
        <div id="profileMenu" class="hidden absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-lg">
            <a href="javascript:void(0);" onclick="toggleSection('viewDetails')" class="block px-4 py-2 text-gray-700 hover:bg-gray-100">View Details</a>
            <form action="logout.php" method="POST" class="block w-full">
                <button type="submit" class="w-full text-left px-4 py-2 text-gray-700 hover:bg-gray-100">Logout</button>
            </form>
        </div>
        </div>

    </nav>

    <div class="container mx-auto p-4">
        <!-- customer Profile Section -->
        <section id="viewDetails" class="bg-white p-6 rounded-lg shadow-lg hidden">
            <h2 class="text-2xl font-semibold mb-4">user Profile</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="space-y-4">
                    <p><strong>first Name:</strong> <?php echo htmlspecialchars($users_data['first_name']); ?></p>
                    <p><strong>Last Name:</strong> <?php echo htmlspecialchars($users_data['last_name']); ?></p>
                    <p><strong>email:</strong> <?php echo htmlspecialchars($users_data['email']); ?></p>
                    <p><strong>password:</strong> <?php echo htmlspecialchars($users_data['password']); ?></p>
                    
                </div>
            </div>
        </section>
        <!-- Product Listings -->
        <section class="mt-12">
            <h3 class="text-2xl font-bold mb-6">Available Products</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php while ($product = mysqli_fetch_assoc($productResult)) { ?>
                    <div class="bg-white p-6 rounded-lg shadow-lg flex flex-col items-center text-center">
                        <img src="<?php echo htmlspecialchars($product['product_image']); ?>" alt="Product Image" class="w-auto h-auto max-w-32 max-h-32 object-cover rounded-full mb-4">
                        <h4 class="text-lg font-semibold mb-2"><?php echo htmlspecialchars($product['name']); ?></h4>
                        <p class="text-gray-700 mb-4"><?php echo htmlspecialchars($product['description']); ?></p>
                        <p class="text-green-500 font-bold mb-4">Price: Ksh. <?php echo htmlspecialchars($product['price']); ?></p>
                        <form action="order.php" method="POST" class="w-full">
                            <input type="hidden" name="product_id" value="<?php echo htmlspecialchars($product['product_id']); ?>">
                            <button type="submit" class="bg-green-500 text-white w-full py-2 rounded-lg hover:bg-green-600">Order</button>
                        </form>
                    </div>
                <?php } ?>
            </div>
        </section>

        <!-- Feedback Form -->
        <!-- Feedback Form -->
<section class="mt-12 flex justify-center">
    <div class="max-w-md w-full">
        <h3 class="text-2xl font-bold mb-6 text-center">Leave Feedback</h3>
        <!-- Customer Feedback Form -->
        <form action="" method="POST" class="bg-white p-6 rounded-lg shadow-lg">
            <!-- Vendor Business Name Input -->
            <label for="business_name" class="block font-semibold mb-2">Business Name:</label>
            <input type="text" name="business_name" placeholder="Enter the vendor's business name" required class="w-full p-2 mb-4 border border-gray-300 rounded-md focus:outline-none focus:border-green-500">

            <textarea name="feedback" placeholder="Enter your feedback" required class="w-full h-32 p-4 mb-4 border border-gray-300 rounded-md focus:outline-none focus:border-green-500"></textarea>

            <label for="rating" class="block font-semibold mb-2">Rating:</label>
            <select name="rating" required class="w-full p-2 mb-6 border border-gray-300 rounded-md focus:outline-none focus:border-green-500">
                <option value="5">5 - Excellent</option>
                <option value="4">4 - Good</option>
                <option value="3">3 - Average</option>
                <option value="2">2 - Poor</option>
                <option value="1">1 - Very Poor</option>
            </select>

            <button type="submit" class="w-full bg-green-500 text-white py-2 rounded-lg hover:bg-green-600">Submit Feedback</button>
        </form>
    </div>
</section>
<!-- Customer Feedback Display Section -->
<section id="feedback" class="mt-12 flex justify-start">
    <div class="max-w-3xl w-full">
        <h3 class="text-2xl font-bold mb-6">Customer Feedback</h3>
        <div class="space-y-4">
            <?php foreach ($feedbacks as $feedback) { ?>
                <div class="bg-gray-50 p-4 rounded-lg shadow-lg">
                    <h4 class="font-semibold"><?php echo htmlspecialchars($feedback['full_name']); ?> - Rating: <?php echo htmlspecialchars($feedback['rating']); ?>/5</h4>
                    <p class="text-green-600 font-semibold"><?php echo htmlspecialchars($feedback['business_name']); ?></p>
                    <p class="text-gray-700"><?php echo htmlspecialchars($feedback['feedback']); ?></p>
                </div>
            <?php } ?>
        </div>
    </div>
</section>


        

        <!-- display vendor details for search functionality -->
        <section id="vendors" class="mt-12 ">
            <h3 class="text-2xl font-bold mb-6">Vendors in your Location</h3>
            <?php if (count($vendors) > 0) { ?>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <?php foreach ($vendors as $vendor) { ?>
                        <div class="bg-white p-6 rounded-lg shadow-lg">
                            <h4 class="font-semibold"><?php echo htmlspecialchars($vendor['business_name']); ?></h4>
                            <p class="text-gray-700"><?php echo htmlspecialchars($vendor['location']); ?></p>
                            <p class="text-gray-600"><?php echo htmlspecialchars($vendor['address']); ?></p>
                            <p class="text-gray-600"><?php echo htmlspecialchars($vendor['phone_number']); ?></p>
                            <p class="text-gray-600"><?php echo htmlspecialchars($vendor['services']); ?></p>
                        </div>
                    <?php } ?>
                </div>
            <?php } else { ?>
                <p class="text-gray-700">No vendors found for the location: <?php echo htmlspecialchars($location); ?>.</p>
            <?php } ?>
        </section>
        <Script src="javascript/main.js"></Script>

    </div>
</body>
</html>


