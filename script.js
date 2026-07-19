document.addEventListener('DOMContentLoaded', () => {
    // Menu Tabs Interactivity
    const tabBtns = document.querySelectorAll('.tab-btn');
    
    tabBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            // Remove active class from all
            tabBtns.forEach(t => {
                t.classList.remove('active', 'text-[#af0202]', 'text-2xl', 'px-8', 'py-4');
                t.classList.add('text-lg', 'px-6', 'py-3');
            });
            
            // Add active class to clicked button
            btn.classList.remove('text-lg', 'px-6', 'py-3');
            btn.classList.add('active', 'text-[#af0202]', 'text-2xl', 'px-8', 'py-4');
            
            // Here you could add logic to change the menu cards based on the selected day
            const selectedDay = btn.getAttribute('data-day');
            console.log(`Selected Day: ${selectedDay}`);
        });
    });
});
