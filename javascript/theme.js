/**
 * Theme Manager
 * Handles dark mode toggle and theme persistence
 */

// Check for saved theme preference or default to light mode
const currentTheme = localStorage.getItem('theme') || 'light';

// Apply theme on page load
document.addEventListener('DOMContentLoaded', function() {
    document.documentElement.setAttribute('data-theme', currentTheme);
    
    // Update toggle button if it exists
    const themeToggle = document.getElementById('theme-toggle');
    if(themeToggle) {
        themeToggle.checked = currentTheme === 'dark';
        
        // Add event listener for theme toggle
        themeToggle.addEventListener('change', function() {
            const theme = this.checked ? 'dark' : 'light';
            document.documentElement.setAttribute('data-theme', theme);
            localStorage.setItem('theme', theme);
            
            // Update server-side preference if user is logged in
            updateThemePreference(theme);
        });
    }
});

// Function to update theme preference on server
function updateThemePreference(theme) {
    const xhr = new XMLHttpRequest();
    xhr.open("POST", "api/v1/user/update-theme.php", true);
    xhr.setRequestHeader("Content-Type", "application/json");
    xhr.send(JSON.stringify({ theme: theme }));
}

// Function to toggle theme programmatically
function toggleTheme() {
    const currentTheme = document.documentElement.getAttribute('data-theme');
    const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
    document.documentElement.setAttribute('data-theme', newTheme);
    localStorage.setItem('theme', newTheme);
    updateThemePreference(newTheme);
    
    const themeToggle = document.getElementById('theme-toggle');
    if(themeToggle) {
        themeToggle.checked = newTheme === 'dark';
    }
}
