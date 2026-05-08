/**
 * Universal Tab Navigation System
 * Usage: Add class "tab-content" to cards and use toggleTab() function
 */

// Tab Management System
const TabNavigation = {
    init: function(storageKey = 'tabStates', defaultVisible = []) {
        const tabStates = {};
        
        // Load from localStorage
        const savedStates = localStorage.getItem(storageKey);
        if (savedStates) {
            try {
                const parsed = JSON.parse(savedStates);
                Object.assign(tabStates, parsed);
            } catch (e) {
                console.error('Error loading tab states:', e);
            }
        }
        
        // Initialize all tabs with class "tab-content"
        document.querySelectorAll('.tab-content').forEach(tab => {
            const tabId = tab.id.replace('tab-', '');
            if (tabStates[tabId] === undefined) {
                tabStates[tabId] = defaultVisible.includes(tabId);
            }
            
            // Restore state
            const button = document.querySelector(`button[onclick*="toggleTab('${tabId}'"]`);
            if (tabStates[tabId]) {
                tab.style.display = '';
                if (button) button.classList.add('active');
            } else {
                tab.style.display = 'none';
                if (button) button.classList.remove('active');
            }
        });
        
        // Save states to localStorage
        localStorage.setItem(storageKey, JSON.stringify(tabStates));
        
        return tabStates;
    },
    
    toggle: function(tabId, button, storageKey = 'tabStates') {
        const tab = document.getElementById(`tab-${tabId}`);
        if (!tab) return;
        
        // Get current states from localStorage
        const savedStates = localStorage.getItem(storageKey);
        const tabStates = savedStates ? JSON.parse(savedStates) : {};
        
        tabStates[tabId] = !tabStates[tabId];
        
        if (tabStates[tabId]) {
            tab.style.display = '';
            button.classList.add('active');
        } else {
            tab.style.display = 'none';
            button.classList.remove('active');
        }
        
        // Save states to localStorage
        localStorage.setItem(storageKey, JSON.stringify(tabStates));
    },
    
    expandAll: function(storageKey = 'tabStates') {
        const savedStates = localStorage.getItem(storageKey);
        const tabStates = savedStates ? JSON.parse(savedStates) : {};
        
        document.querySelectorAll('.tab-content').forEach(tab => {
            const tabId = tab.id.replace('tab-', '');
            const button = document.querySelector(`button[onclick*="toggleTab('${tabId}'"]`);
            if (tab) {
                tab.style.display = '';
                tabStates[tabId] = true;
            }
            if (button) {
                button.classList.add('active');
            }
        });
        
        localStorage.setItem(storageKey, JSON.stringify(tabStates));
    },
    
    collapseAll: function(storageKey = 'tabStates', defaultVisible = []) {
        const savedStates = localStorage.getItem(storageKey);
        const tabStates = savedStates ? JSON.parse(savedStates) : {};
        
        document.querySelectorAll('.tab-content').forEach(tab => {
            const tabId = tab.id.replace('tab-', '');
            // Keep default visible tabs open
            if (defaultVisible && defaultVisible.includes(tabId)) {
                tabStates[tabId] = true;
                tab.style.display = '';
                const button = document.querySelector(`button[onclick*="toggleTab('${tabId}'"]`);
                if (button) button.classList.add('active');
                return;
            }
            
            const button = document.querySelector(`button[onclick*="toggleTab('${tabId}'"]`);
            if (tab) {
                tab.style.display = 'none';
                tabStates[tabId] = false;
            }
            if (button) {
                button.classList.remove('active');
            }
        });
        
        localStorage.setItem(storageKey, JSON.stringify(tabStates));
    }
};

// Global functions for backward compatibility
function toggleTab(tabId, button, storageKey = 'tabStates') {
    TabNavigation.toggle(tabId, button, storageKey);
}

function expandAllTabs(storageKey = 'tabStates') {
    TabNavigation.expandAll(storageKey);
}

function collapseAllTabs(storageKey = 'tabStates', defaultVisible = []) {
    TabNavigation.collapseAll(storageKey, defaultVisible);
}

// Auto-initialize on DOM ready (only if no custom init is called)
// Pages should call TabNavigation.init() with their own storageKey and defaultVisible

