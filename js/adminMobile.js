document.addEventListener('DOMContentLoaded', function() {
    // Initialize view
    initMobileView();
    
    // Listen for window resize
    window.addEventListener('resize', function() {
        initMobileView();
    });
    
    // Handle modals better on mobile
    initMobileModals();
    
    // Function to initialize view with bottom tabs
    function initMobileView() {
        // Only apply for screens
        if (window.innerWidth <= 768) {
            // Add bottom tabs if they dont exist
            if (!document.querySelector('.mobile-tabs')) {
                createMobileTabs();
            }
        } else {
            // Remove specific elements when on desktop
            const mobileElements = document.querySelectorAll('.mobile-tabs, .more-menu');
            mobileElements.forEach(el => el.remove());
        }
    }
    
    // Create tabs navigation
    function createMobileTabs() {
        // Create tabs container
        const tabs = document.createElement('div');
        tabs.className = 'mobile-tabs';
        
        // Get current view for setting active state
        const urlParams = new URLSearchParams(window.location.search);
        const currentView = urlParams.get('view') || 'dashboard';
        
        // Define main navigation tabs
        const tabItems = [
            { icon: 'fa-tachometer-alt', text: 'Dashboard', link: '?view=dashboard' },
            { icon: 'fa-users', text: 'Users', link: '?view=users' },
            { icon: 'fa-gavel', text: 'Appeals', link: '?view=appeals' },
            { icon: 'fa-ellipsis-h', text: 'More', link: '#more' }
        ];
        
        // Create each tab
        tabItems.forEach(item => {
            const tab = document.createElement('a');
            
            // Check if this tab should be active
            const isActive = 
                (item.text === 'Dashboard' && (currentView === 'dashboard' || !currentView)) ||
                (item.text === 'Users' && (currentView === 'users' || currentView === 'user-detail')) ||
                (item.text === 'Appeals' && currentView === 'appeals') ||
                (item.text === 'More' && ['badges', 'marked-for-deletion'].includes(currentView));
            
            tab.className = `mobile-tab ${isActive ? 'active' : ''}`;
            tab.href = item.link;
            
            // Special handling for More tab
            if (item.text === 'More') {
                tab.addEventListener('click', function(e) {
                    e.preventDefault();
                    toggleMoreMenu();
                });
            }
            
            tab.innerHTML = `
                <i class="fas ${item.icon}"></i>
                <span>${item.text}</span>
            `;
            
            // Add notification badge for Appeals tab if needed
            if (item.text === 'Appeals') {
                // Check if theres a badge in the sidebar
                const appealLink = document.querySelector('.sidebar .nav-link[href*="appeals"]');
                const badge = appealLink?.querySelector('.badge');
                
                if (badge) {
                    const notificationBadge = document.createElement('span');
                    notificationBadge.className = 'badge badge-warning';
                    notificationBadge.innerHTML = badge.innerHTML;
                    tab.style.position = 'relative';
                    tab.appendChild(notificationBadge);
                }
            }
            
            tabs.appendChild(tab);
        });
        
        document.body.appendChild(tabs);
    }
    
    // Toggle more menu for additional options
    function toggleMoreMenu() {
        let moreMenu = document.querySelector('.more-menu');
        
        // Create menu if it doesn't exist
        if (!moreMenu) {
            moreMenu = document.createElement('div');
            moreMenu.className = 'more-menu';
            
            // Get all navigation items
            const navItems = document.querySelectorAll('.sidebar .nav-item');
            const mainTabItems = ['Dashboard', 'User Management', 'Appeals'];
            
            // Add each relevant item to the more menu
            navItems.forEach(item => {
                const link = item.querySelector('.nav-link');
                const text = link.querySelector('span').textContent.trim();
                
                // Skip items already in main tabs
                if (mainTabItems.some(tabText => text.includes(tabText))) {
                    return;
                }
                
                // Create menu item
                const menuItem = document.createElement('a');
                menuItem.href = link.getAttribute('href');
                menuItem.className = 'more-menu-item';
                if (link.classList.contains('active')) {
                    menuItem.classList.add('active');
                }
                
                // Use same icon and text as sidebar
                menuItem.innerHTML = link.innerHTML;
                
                moreMenu.appendChild(menuItem);
            });
            
            document.body.appendChild(moreMenu);
            
            // Close when clicking outside
            document.addEventListener('click', function(e) {
                if (moreMenu && 
                    !moreMenu.contains(e.target) && 
                    !e.target.closest('.mobile-tab[href="#more"]')) {
                    moreMenu.style.display = 'none';
                }
            });
        } else {
            // Toggle visibility of existing menu
            moreMenu.style.display = moreMenu.style.display === 'none' ? 'flex' : 'none';
        }
    }
    
    // modal handling on mobile
    function initMobileModals() {
        const modals = document.querySelectorAll('.modal, .modal-appeal');
        
        modals.forEach(modal => {
            // Set max height dynamically
            function setModalMaxHeight() {
                if (window.innerWidth <= 768) {
                    const windowHeight = window.innerHeight;
                    modal.style.maxHeight = (windowHeight * 0.9) + 'px';
                    
                    // Also limit body height for scrolling
                    const modalBody = modal.querySelector('.modal-body');
                    if (modalBody) {
                        const headerHeight = modal.querySelector('.modal-header')?.offsetHeight || 0;
                        const footerHeight = modal.querySelector('.modal-footer')?.offsetHeight || 0;
                        const maxBodyHeight = windowHeight * 0.9 - headerHeight - footerHeight - 40;
                        modalBody.style.maxHeight = maxBodyHeight + 'px';
                    }
                }
            }
            
            // Set initial heights
            setModalMaxHeight();
            
            // Update on resize
            window.addEventListener('resize', setModalMaxHeight);
            
            // Fix modal close buttons
            const closeButtons = modal.querySelectorAll('.modal-close, .cancel-btn');
            closeButtons.forEach(button => {
                button.addEventListener('click', function() {
                    modal.closest('.modal-backdrop').style.display = 'none';
                });
            });
        });
        
        // Backdrop click to close
        const backdrops = document.querySelectorAll('.modal-backdrop');
        backdrops.forEach(backdrop => {
            backdrop.addEventListener('click', function(e) {
                if (e.target === backdrop) {
                    backdrop.style.display = 'none';
                }
            });
        });
    }
});