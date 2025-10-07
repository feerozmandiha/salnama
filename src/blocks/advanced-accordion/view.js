import './style.scss';

console.log('=== SALNAMA ACCORDION VIEW.JS LOADED ===');

class SalnamaAccordionManager {
    constructor() {
        this.initialized = false;
        this.init();
    }

    init() {
        if (this.initialized) return;
        
        document.addEventListener('DOMContentLoaded', () => {
            this.initializeAllAccordions();
        });

        // برای محتوای dynamic
        const observer = new MutationObserver((mutations) => {
            mutations.forEach((mutation) => {
                mutation.addedNodes.forEach((node) => {
                    if (node.nodeType === 1) { // Element node
                        if (node.classList && node.classList.contains('salnama-accordion')) {
                            this.initializeAccordion(node);
                        } else if (node.querySelector) {
                            const accordions = node.querySelectorAll('.salnama-accordion');
                            accordions.forEach(accordion => {
                                this.initializeAccordion(accordion);
                            });
                        }
                    }
                });
            });
        });

        observer.observe(document.body, {
            childList: true,
            subtree: true
        });

        this.initialized = true;
    }

    initializeAllAccordions() {
        const accordions = document.querySelectorAll('.salnama-accordion');
        console.log('Found accordions:', accordions.length);
        
        accordions.forEach((accordion, index) => {
            this.initializeAccordion(accordion, index);
        });
    }

    initializeAccordion(accordion, index = 0) {
        if (accordion.classList.contains('accordion-initialized')) {
            return; // قبلاً مقداردهی شده
        }

        console.log(`Initializing accordion ${index + 1}`, accordion);
        
        const isMultiple = accordion.dataset.multiple === 'true';
        const isNested = accordion.classList.contains('salnama-nested-accordion');
        const items = accordion.querySelectorAll('.salnama-accordion-item');
        
        console.log(`Found ${items.length} items in accordion ${index + 1}`, { isMultiple, isNested });

        items.forEach((item, itemIndex) => {
            this.initializeAccordionItem(item, itemIndex, isMultiple, items);
        });

        accordion.classList.add('accordion-initialized');
    }

    initializeAccordionItem(item, itemIndex, isMultiple, allItems) {
        const header = item.querySelector('.salnama-accordion-header');
        const content = item.querySelector('.salnama-accordion-content');
        
        if (!header) {
            console.log('No header found in item', itemIndex);
            return;
        }

        // حذف event listenerهای قبلی
        const newHeader = header.cloneNode(true);
        header.parentNode.replaceChild(newHeader, header);

        newHeader.addEventListener('click', (e) => {
            e.preventDefault();
            e.stopPropagation();
            
            this.handleAccordionClick(item, isMultiple, allItems);
        });

        console.log(`Event listener added to item ${itemIndex}`);
    }

    handleAccordionClick(clickedItem, isMultiple, allItems) {
        const isOpen = clickedItem.classList.contains('is-open');
        console.log('Accordion clicked, isOpen:', isOpen);

        if (!isMultiple && !isOpen) {
            // بستن سایر آیتم‌ها
            allItems.forEach(item => {
                if (item !== clickedItem && item.classList.contains('is-open')) {
                    this.closeAccordionItem(item);
                }
            });
        }

        if (isOpen) {
            this.closeAccordionItem(clickedItem);
        } else {
            this.openAccordionItem(clickedItem);
        }
    }

    openAccordionItem(item) {
        const header = item.querySelector('.salnama-accordion-header');
        const content = item.querySelector('.salnama-accordion-content');
        const accordion = item.closest('.salnama-accordion');
        const isHorizontal = accordion.classList.contains('salnama-accordion-horizontal');
        
        item.classList.add('is-open');
        if (header) header.setAttribute('aria-expanded', 'true');
        if (content) {
            content.hidden = false;
            
            if (isHorizontal) {
                content.style.maxHeight = 'none';
                content.style.opacity = '1';
                content.style.visibility = 'visible';
            } else {
                content.style.maxHeight = content.scrollHeight + 'px';
            }
        }
        console.log('✅ Item opened');
    }

    closeAccordionItem(item) {
        const header = item.querySelector('.salnama-accordion-header');
        const content = item.querySelector('.salnama-accordion-content');
        const accordion = item.closest('.salnama-accordion');
        const isHorizontal = accordion.classList.contains('salnama-accordion-horizontal');
        
        item.classList.remove('is-open');
        if (header) header.setAttribute('aria-expanded', 'false');
        if (content) {
            content.hidden = true;
            
            if (isHorizontal) {
                content.style.opacity = '0';
                content.style.visibility = 'hidden';
            } else {
                content.style.maxHeight = '0';
            }
        }
        console.log('✅ Item closed');
    }
}

// راه‌اندازی manager
new SalnamaAccordionManager();