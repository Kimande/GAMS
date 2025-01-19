<?php
session_start();
@include_once('config.php');

// Handle Admin Approval Logic
if (isset($_GET['action']) && isset($_GET['id'])) {
    $vendor_id = intval($_GET['id']);
    $action = $_GET['action'];
    
    if ($action == 'approve') {
        $sql = "UPDATE vendors SET approval_status = 'Approved' WHERE vendor_id = ?";
        $notification_message = "Your vendor registration has been approved. You may now log in.";
    } elseif ($action == 'reject') {
        $sql = "UPDATE vendors SET approval_status = 'Rejected' WHERE vendor_id = ?";
        $notification_message = "Your vendor registration has been rejected. Please contact support for more information.";
    } else {
        $_SESSION['flash_message'] = "Invalid action.";
        header('Location: admin_dashboard.php');
        exit();
    }

    // Prepare and execute the update query
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $vendor_id);
    $stmt->execute();
    $stmt->close();

    // Fetch user_id from vendors table based on vendor_id
    $user_id_query = "SELECT user_id FROM vendors WHERE vendor_id = ?";
    $stmt_user_id = $conn->prepare($user_id_query);
    $stmt_user_id->bind_param("i", $vendor_id);
    $stmt_user_id->execute();
    $stmt_user_id->bind_result($user_id);
    $stmt_user_id->fetch();
    $stmt_user_id->close();

    if ($user_id) {
        // Proceed with inserting into notifications
        $notification_sql = "INSERT INTO notifications (vendor_id, message) VALUES (?, ?)";
        $notification_stmt = $conn->prepare($notification_sql);
        $notification_stmt->bind_param("is", $user_id, $notification_message);
        $notification_stmt->execute();
        $notification_stmt->close();
    } else {
        $_SESSION['flash_message'] = "Error: Vendor user ID not found.";
        header('Location: admin_dashboard.php?section=pending');
        exit();
    }

    header('Location: admin_dashboard.php');
    exit();
}

// Fetch vendors and their statuses
$sql = "SELECT v.vendor_id, u.first_name, u.email, v.approval_status, v.license_doc 
        FROM vendors v 
        JOIN users u ON v.user_id = u.user_id";
$result = $conn->query($sql);
$vendors = [];

while ($row = $result->fetch_assoc()) {
    $vendors[] = $row;
}

$pending_vendors = array_filter($vendors, fn($v) => $v['approval_status'] === 'Pending');
$approved_vendors = array_filter($vendors, fn($v) => $v['approval_status'] === 'Approved');
$rejected_vendors = array_filter($vendors, fn($v) => $v['approval_status'] === 'Rejected');

// Fetch all feedback for all vendors
$feedback_query = "SELECT feedback, rating, vendor_id, user_id, created_at FROM ratings ORDER BY created_at DESC";
$feedbackResult = mysqli_query($conn, $feedback_query);

// Determine the active section from the URL
$active_section = isset($_GET['section']) ? $_GET['section'] : 'pending';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <script>
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            sidebar.classList.toggle('hidden');
        }
    </script>
</head>
<body class="bg-gray-100 flex flex-col md:flex-row">
    <button 
            class="md:hidden p-4 bg-gray-800 text-white focus:outline-none"
            onclick="toggleSidebar()">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16m-7 6h7" />
            </svg>
        </button>

    <!-- Sidebar -->
    <div id="sidebar" class="w-full md:w-64 h-screen bg-gray-800 text-white shadow-lg md:block hidden">
        <div class="p-5">
            <h1 class="text-2xl font-bold">Admin Panel</h1>
        </div>
        <nav class="mt-10">
            <a href="?section=pending" class="block py-2.5 px-4 rounded transition duration-200 hover:bg-gray-700 <?php echo $active_section == 'pending' ? 'bg-gray-700' : ''; ?>">Pending Vendors</a>
            <a href="?section=approved" class="block py-2.5 px-4 rounded transition duration-200 hover:bg-gray-700 <?php echo $active_section == 'approved' ? 'bg-gray-700' : ''; ?>">Approved Vendors</a>
            <a href="?section=rejected" class="block py-2.5 px-4 rounded transition duration-200 hover:bg-gray-700 <?php echo $active_section == 'rejected' ? 'bg-gray-700' : ''; ?>">Rejected Vendors</a>
            <a href="?section=feedback" class="block py-2.5 px-4 rounded transition duration-200 hover:bg-gray-700 <?php echo $active_section == 'feedback' ? 'bg-gray-700' : ''; ?>">Customer Feedback</a>
        </nav>
    </div>

    <!-- Main Content -->
    <div class="flex-1 p-6">
        <h1 class="text-4xl font-bold text-center text-gray-800 mb-6">Admin Dashboard</h1>
        
        <?php if (isset($_SESSION['flash_message'])): ?>
            <div class="bg-green-500 text-white p-3 rounded mb-5 text-center">
                <?php echo $_SESSION['flash_message']; unset($_SESSION['flash_message']); ?>
            </div>
        <?php endif; ?>

        <!-- Dynamic Vendor Section -->
        <?php if (in_array($active_section, ['pending', 'approved', 'rejected'])): ?>
            <h2 class="text-3xl font-semibold text-gray-700 mt-8"><?php echo ucfirst($active_section) . ' Vendors'; ?></h2>
            <div class="overflow-x-auto mt-4">
                <table class="min-w-full bg-white rounded-lg shadow-md border border-gray-300">
                    <thead class="bg-gray-200">
                        <tr>
                            <th class="py-3 px-4 border-b">Business Name</th>
                            <th class="py-3 px-4 border-b">Email</th>
                            <th class="py-3 px-4 border-b">Approval Status</th>
                            <th class="py-3 px-4 border-b">License Document</th>
                            <?php if ($active_section === 'pending'): ?>
                                <th class="py-3 px-4 border-b">Action</th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $current_vendors = ${$active_section . '_vendors'};
                        foreach ($current_vendors as $row): ?>
                            <tr class="hover:bg-gray-100">
                                <td class="py-2 px-4 border-b"><?php echo htmlspecialchars($row['first_name']); ?></td>
                                <td class="py-2 px-4 border-b"><?php echo htmlspecialchars($row['email']); ?></td>
                                <td class="py-2 px-4 border-b"><?php echo htmlspecialchars($row['approval_status']); ?></td>
                                <td class="py-2 px-4 border-b">
                                    <?php if (!empty($row['license_doc'])): ?>
                                        <a href="<?php echo htmlspecialchars($row['license_doc']); ?>" target="_blank" class="text-blue-500 hover:underline">View License</a>
                                    <?php else: ?>
                                        No License Uploaded
                                    <?php endif; ?>
                                </td>
                                <?php if ($active_section === 'pending'): ?>
                                <td class="py-2 px-4 border-b">
                                    <a href="?action=approve&id=<?php echo $row['vendor_id']; ?>&section=pending" class="text-green-500 hover:underline">Approve</a>
                                    <a href="?action=reject&id=<?php echo $row['vendor_id']; ?>&section=pending" class="text-red-500 hover:underline">Reject</a>
                                </td>
                                <?php endif; ?>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>

        <!-- Customer Feedback Section -->
        <?php if ($active_section === 'feedback'): ?>
            <h2 class="text-3xl font-semibold text-gray-700 mt-6">Customer Feedback</h2>
            <div class="feedback-section mt-4">
                <?php if (mysqli_num_rows($feedbackResult) > 0): ?>
                    <?php while ($row = mysqli_fetch_assoc($feedbackResult)): ?>
                        <div class="bg-white p-4 rounded-md shadow-lg mb-4">
                            <p class="text-gray-700 mb-2"><?php echo htmlspecialchars($row['feedback']); ?></p>
                            <p class="text-green-500 font-bold">Rating: <?php echo htmlspecialchars($row['rating']); ?> / 5</p>
                            <p class="text-gray-500 text-sm mt-2">
                                For Vendor ID: <?php echo htmlspecialchars($row['vendor_id']); ?> | Submitted by User <?php echo htmlspecialchars($row['user_id']); ?> on <?php echo htmlspecialchars($row['created_at']); ?>
                            </p>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <p class="text-gray-500">No feedback available.</p>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
