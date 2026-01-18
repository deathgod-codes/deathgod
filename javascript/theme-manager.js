/**
 * Theme Manager
 * Handles dark/light theme switching with persistence
 */

class ThemeManager {
    constructor() {
        this.currentTheme = this.loadTheme();
        this.applyTheme(this.currentTheme);
        this.setupSystemThemeListener();
    }
    
    loadTheme() {
        // Check localStorage first
        const savedTheme = localStorage.getItem('theme_preference');
        if (savedTheme && (savedTheme === 'light' || savedTheme === 'dark')) {
            return savedTheme;
        }
        
        // Check user preference in database (if available)
        // This would require an API call
        
        // Default to system preference
        if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) {
            return 'dark';
        }
        
        return 'light';
    }
    
    applyTheme(theme) {
        document.documentElement.setAttribute('data-theme', theme);
        this.currentTheme = theme;
        this.updateToggleButton();
    }
    
    toggleTheme() {
        const newTheme = this.currentTheme === 'light' ? 'dark' : 'light';
        this.applyTheme(newTheme);
        this.saveTheme(newTheme);
    }
    
    saveTheme(theme) {
        // Save to localStorage
        localStorage.setItem('theme_preference', theme);
        
        // Also save to database if user is logged in
        this.saveThemeToDatabase(theme);
    }
    
    saveThemeToDatabase(theme) {
        // API call to save theme preference
        fetch('php/update-theme.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'theme=' + encodeURIComponent(theme)
        }).catch(error => {
            console.error('Failed to save theme to database:', error);
        });
    }
    
    setupSystemThemeListener() {
        if (window.matchMedia) {
            const darkModeQuery = window.matchMedia('(prefers-color-scheme: dark)');
            
            // Modern browsers
            if (darkModeQuery.addEventListener) {
                darkModeQuery.addEventListener('change', (e) => {
                    // Only auto-switch if user hasn't manually set a preference
                    if (!localStorage.getItem('theme_preference')) {
                        const newTheme = e.matches ? 'dark' : 'light';
                        this.applyTheme(newTheme);
                    }
                });
            }
        }
    }
    
    updateToggleButton() {
        const toggleBtn = document.querySelector('.theme-toggle');
        if (toggleBtn) {
            const icon = toggleBtn.querySelector('i');
            const text = toggleBtn.querySelector('.theme-text');
            
            if (this.currentTheme === 'dark') {
                if (icon) icon.className = 'fas fa-sun';
                if (text) text.textContent = 'Light';
            } else {
                if (icon) icon.className = 'fas fa-moon';
                if (text) text.textContent = 'Dark';
            }
        }
    }
    
    getTheme() {
        return this.currentTheme;
    }
}

// Initialize theme manager
let themeManager;
document.addEventListener('DOMContentLoaded', () => {
    themeManager = new ThemeManager();
});

// Global function to toggle theme
function toggleTheme() {
    if (themeManager) {
        themeManager.toggleTheme();
    }
}

// Export
if (typeof module !== 'undefined' && module.exports) {
    module.exports = ThemeManager;
}
