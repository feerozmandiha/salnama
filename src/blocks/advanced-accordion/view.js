import './style.scss';

console.log('=== SALNAMA ACCORDION VIEW.JS LOADED ===');

// جلوگیری از اجرای تکراری
let accordionsInitialized = false;

function initSalnamaAccordions() {
    if (accordionsInitialized) {
        console.log('Accordions already initialized, skipping...');
        return;
    }
    
    console.log('DOM Content Loaded - Initializing accordions');
    accordionsInitialized = true;
    
    const accordions = document.querySelectorAll('.salnama-accordion');
    console.log('Found accordions:', accordions.length);
    
    accordions.forEach((accordion, accordionIndex) => {
        console.log(`Setting up accordion ${accordionIndex + 1}`);
        
        const isMultiple = accordion.dataset.multiple === 'true';
        const items = accordion.querySelectorAll('.salnama-accordion-item');
        console.log(`Found ${items.length} items in accordion ${accordionIndex + 1}`);
        
        items.forEach((item, itemIndex) => {
            const header = item.querySelector('.salnama-accordion-header');
            const content = item.querySelector('.salnama-accordion-content');
            
            if (!header) {
                console.log('No header found in item', itemIndex);
                return;
            }
            
            // حذف تمام event listenerهای قبلی با clone کردن المنت
            const newHeader = header.cloneNode(true);
            header.parentNode.replaceChild(newHeader, header);
            
            newHeader.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                console.log('🎯 ACCORDION CLICKED!');
                
                const currentItem = this.closest('.salnama-accordion-item');
                if (!currentItem) return;
                
                const isOpen = currentItem.classList.contains('is-open');
                console.log('Current state - isOpen:', isOpen);
                
                // اگر multipleOpen فعال نیست، سایر آیتم‌ها را ببند
                if (!isMultiple && !isOpen) {
                    items.forEach(otherItem => {
                        if (otherItem !== currentItem && otherItem.classList.contains('is-open')) {
                            closeAccordionItem(otherItem);
                        }
                    });
                }
                
                if (isOpen) {
                    closeAccordionItem(currentItem);
                } else {
                    openAccordionItem(currentItem);
                }
            });
            
            console.log(`Event listener added to item ${itemIndex}`);
        });
        
        function openAccordionItem(item) {
            const header = item.querySelector('.salnama-accordion-header');
            const content = item.querySelector('.salnama-accordion-content');
            
            item.classList.add('is-open');
            if (header) header.setAttribute('aria-expanded', 'true');
            if (content) {
                content.hidden = false;
                content.style.maxHeight = content.scrollHeight + 'px';
            }
            console.log('✅ Item opened');
        }
        
        function closeAccordionItem(item) {
            const header = item.querySelector('.salnama-accordion-header');
            const content = item.querySelector('.salnama-accordion-content');
            
            item.classList.remove('is-open');
            if (header) header.setAttribute('aria-expanded', 'false');
            if (content) {
                content.hidden = true;
                content.style.maxHeight = '0';
            }
            console.log('✅ Item closed');
        }
    });
}

// فقط یک بار اجرا شود
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initSalnamaAccordions);
} else {
    // اگر DOM از قبل بارگذاری شده، مستقیماً اجرا شود
    setTimeout(initSalnamaAccordions, 100);
}