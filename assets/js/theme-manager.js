// Theme Management System for Note App
// Handles dark/light mode switching with localStorage persistence

class ThemeManager {
    constructor() {
        this.init();
    }

    init() {
        // Set initial theme based on user preference or system preference
        this.setInitialTheme();
        
        // Create and add theme toggle button
        this.createThemeToggle();
        
        // Listen for system theme changes
        this.listenForSystemThemeChanges();
    }

    setInitialTheme() {
        // Check for saved theme preference
        const savedTheme = localStorage.getItem('noteapp-theme');
        
        if (savedTheme) {
            this.setTheme(savedTheme);
        } else {
            // Check system preference
            const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
            this.setTheme(prefersDark ? 'dark' : 'light');
        }
    }

    getCurrentTheme() {
        return document.documentElement.getAttribute('data-theme') || 'light';
    }

    setTheme(theme) {
        // Validate theme
        if (theme !== 'light' && theme !== 'dark') {
            theme = 'light';
        }

        // Apply theme to document
        document.documentElement.setAttribute('data-theme', theme);
        
        // Save preference
        localStorage.setItem('noteapp-theme', theme);
        
        // Update toggle button if it exists
        this.updateToggleButton(theme);
        
        // Dispatch custom event for other components
        window.dispatchEvent(new CustomEvent('themeChanged', { 
            detail: { theme } 
        }));
    }

    toggleTheme() {
        const currentTheme = this.getCurrentTheme();
        const newTheme = currentTheme === 'light' ? 'dark' : 'light';
        
        // Add switching animation to toggle button
        const toggleBtn = document.querySelector('.theme-toggle');
        if (toggleBtn) {
            toggleBtn.classList.add('switching');
            setTimeout(() => {
                toggleBtn.classList.remove('switching');
            }, 600);
        }
        
        this.setTheme(newTheme);
        
        // Show brief notification
        this.showThemeNotification(newTheme);
    }

    createThemeToggle() {
        // Don't create if it already exists
        if (document.querySelector('.theme-toggle')) {
            return;
        }

        const toggleButton = document.createElement('button');
        toggleButton.className = 'theme-toggle';
        toggleButton.setAttribute('aria-label', 'Toggle dark/light mode');
        toggleButton.setAttribute('title', 'Switch theme');
        
        // Set initial icon
        this.updateToggleButton(this.getCurrentTheme(), toggleButton);
        
        // Add click handler
        toggleButton.addEventListener('click', () => {
            this.toggleTheme();
        });

        // Add keyboard support
        toggleButton.addEventListener('keydown', (e) => {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                this.toggleTheme();
            }
        });

        // Add to document
        document.body.appendChild(toggleButton);
    }

    updateToggleButton(theme, button = null) {
        const toggleBtn = button || document.querySelector('.theme-toggle');
        if (!toggleBtn) return;

        if (theme === 'dark') {
            toggleBtn.innerHTML = '☀️';
            toggleBtn.setAttribute('aria-label', 'Switch to light mode');
            toggleBtn.setAttribute('title', 'Switch to light mode');
        } else {
            toggleBtn.innerHTML = '🌙';
            toggleBtn.setAttribute('aria-label', 'Switch to dark mode');
            toggleBtn.setAttribute('title', 'Switch to dark mode');
        }
    }

    showThemeNotification(theme) {
        // Remove existing notification
        const existing = document.querySelector('.theme-notification');
        if (existing) {
            existing.remove();
        }

        // Create notification
        const notification = document.createElement('div');
        notification.className = 'theme-notification';
        notification.style.cssText = `
            position: fixed;
            top: 90px;
            right: 20px;
            z-index: 1001;
            padding: 0.75rem 1.5rem;
            background: var(--gradient-primary);
            color: var(--text-inverse);
            border-radius: 25px;
            font-weight: 600;
            font-size: 0.9rem;
            box-shadow: var(--shadow-light);
            transform: translateX(100%);
            transition: transform 0.3s ease;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        `;

        const icon = theme === 'dark' ? '🌙' : '☀️';
        const text = theme === 'dark' ? 'Dark mode enabled' : 'Light mode enabled';
        notification.innerHTML = `${icon} ${text}`;

        document.body.appendChild(notification);

        // Animate in
        setTimeout(() => {
            notification.style.transform = 'translateX(0)';
        }, 10);

        // Animate out and remove
        setTimeout(() => {
            notification.style.transform = 'translateX(100%)';
            setTimeout(() => {
                if (notification.parentNode) {
                    notification.remove();
                }
            }, 300);
        }, 2000);
    }

    listenForSystemThemeChanges() {
        // Listen for system theme changes
        const mediaQuery = window.matchMedia('(prefers-color-scheme: dark)');
        
        mediaQuery.addEventListener('change', (e) => {
            // Only auto-change if user hasn't set a preference
            const savedTheme = localStorage.getItem('noteapp-theme');
            if (!savedTheme) {
                this.setTheme(e.matches ? 'dark' : 'light');
            }
        });
    }

    // Utility method to get theme-appropriate colors
    getThemeColors() {
        const theme = this.getCurrentTheme();
        
        if (theme === 'dark') {
            return {
                primary: '#e8e8e8',
                secondary: '#b0b0b0',
                background: 'rgba(30, 30, 30, 0.95)',
                border: '#404040'
            };
        } else {
            return {
                primary: '#2c3e50',
                secondary: '#7f8c8d',
                background: 'rgba(255, 255, 255, 0.95)',
                border: '#e0e6ed'
            };
        }
    }

    // Method to apply theme to dynamically created elements
    applyThemeToElement(element) {
        const theme = this.getCurrentTheme();
        
        if (theme === 'dark') {
            element.classList.add('dark-theme');
        } else {
            element.classList.remove('dark-theme');
        }
    }
}

// Auto-initialize when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    // Initialize theme manager
    window.themeManager = new ThemeManager();
    
    // Add theme support to any forms that might be submitted
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        form.addEventListener('submit', function() {
            // Preserve theme during page transitions
            const currentTheme = window.themeManager.getCurrentTheme();
            localStorage.setItem('noteapp-theme', currentTheme);
        });
    });
});

// Keyboard shortcut for theme toggle (Ctrl/Cmd + Shift + T)
document.addEventListener('keydown', function(e) {
    if ((e.ctrlKey || e.metaKey) && e.shiftKey && e.key === 'T') {
        e.preventDefault();
        if (window.themeManager) {
            window.themeManager.toggleTheme();
        }
    }
});

// Export for use in other scripts
window.ThemeManager = ThemeManager;