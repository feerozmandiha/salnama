(function() {
  function initBlock(block) {
    const heroSlides   = block.querySelectorAll('.salnama-slider-item');
    const heroContents = block.querySelectorAll('.salnama-hero-content');
    const dots         = block.querySelectorAll('.salnama-slider-dot');

    if (heroSlides.length < 2) return;

    const slideInterval     = parseInt(block.dataset.animationSpeed, 10) || 6000;
    const transitionEffect  = block.dataset.transitionEffect || 'fade';
    const transitionDuration= 1500;
    let currentSlide = 0;
    let timer;

    function showSlide(index) {
      heroSlides.forEach((s,i) => {
        s.classList.remove('active','exiting');
        heroContents[i].classList.remove('active');
        dots[i].classList.remove('active');
      });

      if (transitionEffect === 'slide') {
        heroSlides[currentSlide].classList.add('exiting');
      }

      currentSlide = index;
      heroSlides[currentSlide].classList.add('active');
      heroContents[currentSlide].classList.add('active');
      dots[currentSlide].classList.add('active');

      if (transitionEffect === 'slide') {
        setTimeout(() => {
          heroSlides.forEach(s => s.classList.remove('exiting'));
        }, transitionDuration);
      }
    }

    function nextSlide() {
      let newIndex = (currentSlide + 1) % heroSlides.length;
      showSlide(newIndex);
    }

    function resetTimer() {
      clearInterval(timer);
      timer = setInterval(nextSlide, slideInterval);
    }

    // رویداد دات‌ها
    dots.forEach(dot => {
      dot.addEventListener('click', () => {
        showSlide(parseInt(dot.dataset.slide,10));
        resetTimer();
      });
    });

    // شروع اولیه
    showSlide(0);
    timer = setInterval(nextSlide, slideInterval);
  }

  document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.salnama-hero-block').forEach(initBlock);
  });
})();
