import './style.scss';

console.log('Salnama Accordion Frontend Script Loaded'); // برای دیباگ

document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM Ready - Initializing accordions'); // برای دیباگ
    
    const accordions = document.querySelectorAll('.salnama-accordion');
    console.log('Found accordions:', accordions.length); // برای دیباگ
    
    accordions.forEach((accordion, index) => {
        console.log(`Initializing accordion ${index + 1}`); // برای دیباگ
        
        const isMultiple = accordion.dataset.multiple === 'true';
        const items = accordion.querySelectorAll('.salnama-accordion-item');
        
        items.forEach((item, itemIndex) => {
            const header = item.querySelector('.salnama-accordion-header');
            const content = item.querySelector('.salnama-accordion-content');
            
            if (!header || !content) {
                console.log('Header or content not found in item', itemIndex);
                return;
            }

            console.log(`Setting up click event for item ${itemIndex}`); // برای دیباگ
            
            header.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                console.log('Accordion header clicked'); // برای دیباگ
                
                toggleAccordionItem(item, isMultiple, items);
            });
        });
    });

    function toggleAccordionItem(item, isMultiple, allItems) {
        const isOpen = item.classList.contains('is-open');
        console.log('Item is open:', isOpen); // برای دیباگ

        if (!isMultiple && !isOpen) {
            // Close all other items if multiple open is disabled
            allItems.forEach(otherItem => {
                if (otherItem !== item && otherItem.classList.contains('is-open')) {
                    closeAccordionItem(otherItem);
                }
            });
        }

        if (isOpen) {
            closeAccordionItem(item);
        } else {
            openAccordionItem(item);
        }
    }

    function openAccordionItem(item) {
        const header = item.querySelector('.salnama-accordion-header');
        const content = item.querySelector('.salnama-accordion-content');
        
        item.classList.add('is-open');
        if (header) header.setAttribute('aria-expanded', 'true');
        if (content) {
            content.setAttribute('aria-hidden', 'false');
            content.style.maxHeight = content.scrollHeight + 'px';
        }
        console.log('Accordion item opened'); // برای دیباگ
    }

    function closeAccordionItem(item) {
        const header = item.querySelector('.salnama-accordion-header');
        const content = item.querySelector('.salnama-accordion-content');
        
        item.classList.remove('is-open');
        if (header) header.setAttribute('aria-expanded', 'false');
        if (content) {
            content.setAttribute('aria-hidden', 'true');
            content.style.maxHeight = '0';
        }
        console.log('Accordion item closed'); // برای دیباگ
    }
});