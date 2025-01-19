// Toggle mobile menu and icons
document.getElementById('menu-btn').addEventListener('click', function () {
    const mobileMenu = document.getElementById('mobile-menu');
    const hamburgerIcon = document.getElementById('hamburger-icon');
    const closeIcon = document.getElementById('close-icon')
    mobileMenu.classList.toggle('hidden');
    hamburgerIcon.classList.toggle('hidden');
    closeIcon.classList.toggle('hidden');
});

    // Text for the typing effect for home page
    const titleText = "Welcome to Gas Agency services";
    const subText = "Your go-to platform for safe, reliable, and convenient LPG delivery. Find trusted vendors near you.";

    const typingTitleElement = document.getElementById("typing-text");
    const typingSubTextElement = document.getElementById("typing-subtext");

    let titleIndex = 0;
    let subTextIndex = 0;


    function typeTitle() {
        if (titleIndex < titleText.length) {
            typingTitleElement.textContent += titleText.charAt(titleIndex);
            titleIndex++;
            setTimeout(typeTitle, 50); 
        } else {
              }
    }

    function typeSubText() {
        if (subTextIndex < subText.length) {
            typingSubTextElement.textContent += subText.charAt(subTextIndex);
            subTextIndex++;
            setTimeout(typeSubText, 50);
        }
    }

    document.addEventListener("DOMContentLoaded", typeTitle);
    
    //role based redirection during user signup

    function checkRole() {
        const role = document.querySelector('input[name="role"]:checked').value;
        console.log(role); // Check if the correct role is selected
        
        const vendorDetails = document.getElementById('vendorDetails');
        if (role === 'vendor') {
            vendorDetails.style.display = 'block';
        } else {
            vendorDetails.style.display = 'none';
        }
    }
    
    
   // Function to toggle visibility of sections for vendors
function toggleSection(sectionId) {
    const sections = ['viewDetails', 'profileMenu','vendors', 'feedback']; // Ensure these match your section IDs
    sections.forEach(id => {
        const element = document.getElementById(id);
        if (element) {
            element.classList.add('hidden'); // Add the 'hidden' class to hide all sections
        }
    });
    const section = document.getElementById(sectionId);
    if (section) {
        section.classList.remove('hidden'); // Remove the 'hidden' class to show the desired section
    }
}

// Toggle the dropdown visibility
function toggleDropdown() {
    const profileMenu = document.getElementById('profileMenu');
    if (profileMenu) {
        profileMenu.classList.toggle('hidden');
    }
}


// Toggle mobile menu for responsive design
function toggleMobileMenu() {
    const mobileMenu = document.getElementById('mobileMenu');
    if (mobileMenu) {
        mobileMenu.classList.toggle('hidden');
    }
}

// Function to handle role-specific display logic
function checkRole() {
    const role = document.querySelector('input[name="role"]:checked')?.value; // Ensure the role is selected
    const viewDetails = document.getElementById('viewDetails');

    if (role === 'user') {
        if (viewDetails) {
            viewDetails.style.display = 'block';
            // Make vendor-specific fields required
            document.querySelectorAll('#viewDetails input, #viewDetails select').forEach(input => {
                input.required = true;
            });
        }
    } else {
        if (viewDetails) {
            viewDetails.style.display = 'none';
            // Remove required attribute for vendor fields
            document.querySelectorAll('#viewDetails input, #viewDetails select').forEach(input => {
                input.required = false;
            });
        }
    }
}
// password recovery
function toggleRecoveryForm() {
    const recoveryForm = document.getElementById('recoveryForm');
    recoveryForm.classList.toggle('hidden');
}


