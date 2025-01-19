<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gas Agency</title>
    <link rel="stylesheet" href="OUTPUT.CSS">
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class=white md:pt-16 pt-0"> 

<header id="navbar">
    <nav class="bg-green-600 p-4 md:fixed top-0 left-0 w-full z-10">
        <div class="container mx-auto flex justify-between items-center">
            <img src="uploads/GAMSlogo.png" class="h-16 w-auto" alt="logo"> 
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

            <!-- Menu items -->
            <ul id="menu" class="hidden md:flex space-x-4 text-lg">
                <li><a href="index.php" class="hover:underline hover:text-red-500">Home</a></li>
                <li><a href="#how-we-work" class="hover:underline hover:text-red-500">How we work</a></li>
                <li><a href="#services" class="hover:underline hover:text-red-500">Services</a></li>
                <li><a href="#about" class="hover:underline hover:text-red-500">About Us</a></li>
                <li><a href="#contact" class="hover:underline hover:text-red-500">Contact Us</a></li>
            </ul>
        </div>

        <!-- Mobile menu -->
        <div id="mobile-menu" class="hidden md:hidden">
            <ul class="flex flex-col space-y-2 text-lg p-4">
                <li><a href="index.php" class="hover:underline hover:text-red-500">Home</a></li>
                <li><a href="#how-we-work" class="hover:underline hover:text-red-500">How we work</a></li>
                <li><a href="#services" class="hover:underline hover:text-red-500">Services</a></li>
                <li><a href="#about" class="hover:underline hover:text-red-500">About Us</a></li>
                <li><a href="#contact" class="hover:underline hover:text-red-500">Contact Us</a></li>
            </ul>
        </div>
    </nav>
</header>
<main>

    <section class="container mx-auto bg-img min-h-screen flex flex-col md:flex-row justify-center items-center text-center md:text-left">
    
    <div class="md:w-1/2 p-4">
        <h1 id="typing-text" class="text-4xl font-bold text-black bold mb-4">Welcome to Gas Agency <br><h2>find a trusted vendor</h2></h1>
        <p id="typing-subtext" class="text-lg text-green-600 mb-6">Your reliable gas delivery partner.</p>
        <div class="space-x-4">
            <a href="customer_login.php" class="bg-blue-700 text-white px-6 py-2 rounded hover:bg-blue-800">customer Login</a>
            <a href="vendor_login.php" class="bg-green-500 text-white px-6 py-2 rounded hover:bg-green-700">Vendor Login</a>
            <a href="user_signup.php" class="bg-green-500 text-white px-6 py-2 rounded hover:bg-green-700">Sign Up</a>
        </div>
    </div>
    
    <!-- Image Section -->
    <div class="md:w-1/2 p-4 flex justify-center md:justify-end">
        <img src="uploads/gas.jpg" alt="Gas Agency" class="max-w-full h-auto">
    </div>
</section>


    <!-- How We Work Section -->
    <section id="how-we-work" class="mx-auto bg-white min-h-screen flex flex-col justify-center text-center">
        <h1 class="text-3xl font-bold text-gray-700 mb-4">How we work</h1>
        <p class="text-gray-500 ">We register only authorized vendors on the platform, ensuring quality and reliability for all our customers.</p>
    </section>

    <!-- Services Section -->
    <section id="services" class="min-h-screen bg-green-500 flex flex-col justify-center text-center">
        <div class="container mx-auto">
            <h2 class="text-3xl font-bold text-white  mb-3">Our Services</h2>
            <p class="text-lg text-gray-600 mb-6">Explore our services to connect with reliable service providers and vendors.</p>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <div class="bg-gray-100 p-6 rounded-lg shadow-lg">
                    <h3 class="text-xl font-semibold mb-2">Delivery Services</h3>
                    <p>Fast and reliable delivery services to your doorstep.</p>
                </div>
                <div class="bg-gray-100 p-6 rounded-lg shadow-lg">
                    <h3 class="text-xl font-semibold mb-2">Vendor Solutions</h3>
                    <p>Helping vendors reach more customers with a seamless platform for growth.</p>
                </div>
                <div class="bg-gray-100 p-6 rounded-lg shadow-lg">
                    <h3 class="text-xl font-semibold mb-2">Customer Support</h3>
                    <p>24/7 support for all your inquiries and assistance needs.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- About Us Section -->
    <section id="about" class="min-h-screen bg-gray-200 flex flex-col justify-center text-center">
        <div class="container mx-auto">
            <h2 class="text-3xl font-bold text-gray-800 mb-6">About Us</h2>
            <p class="text-lg text-gray-600 mb-6">At Gas Agency, we aim to streamline connections between customers and vendors in the gas and service sectors.</p>
        </div>
    </section>

    <!-- Contact Us Section -->
    <section id="contact" class="min-h-screen bg-white flex flex-col justify-center text-center">
        <div class="container mx-auto">
            <h2 class="text-3xl font-bold text-gray-800 mb-6">Contact Us</h2>
            <p class="text-lg text-gray-600 mb-6">For inquiries or support, reach out to us anytime. We're here to help!</p>
            <a href="mailto:support@gobeba.com" class="text-blue-500 hover:underline">support@gasagency.com</a>
        </div>
    </section>
</main>

<!-- Footer -->
<footer class="bg-blue-700 p-6 text-center text-white">
    <p>&copy; 2024 Gas Agency. All rights reserved.</p>
</footer>

<!-- Scripts -->
<script src="javascript/main.js"></script>
</body>
</html>
