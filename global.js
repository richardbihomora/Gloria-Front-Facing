// Mark that JS is running (gates the scroll-reveal styles in global.css)
document.documentElement.classList.add('js');

// Global Variables
const menuButton = document.getElementById('menu-button');
const menu = document.getElementById('menu');
const questions = document.querySelectorAll(".question");
const menuItems = document.querySelectorAll(".menu-item");
const logo = document.getElementById('logo');
const formControls = document.querySelectorAll(".form-control");
const errorMessages = document.querySelectorAll('.error-message');


// Menu

function setMenu(open) {
  menu.classList.toggle('visible', open);
  logo.classList.toggle('fixed', open);
  if (menuButton) {
    menuButton.setAttribute('aria-expanded', String(open));
    menuButton.setAttribute('aria-label', open ? 'Close menu' : 'Open menu');
  }
}

if (menuButton) {
  menuButton.addEventListener('click', () => {
    setMenu(!menu.classList.contains('visible'));
  });
}

menuItems.forEach((item) => {
  item.addEventListener("click", () => setMenu(false));
});

document.addEventListener('click', (event) => {
  if (menu.classList.contains('visible') && isClickOutsideMenu(event)) {
    setMenu(false);
  }
});

document.addEventListener('keydown', (event) => {
  if (event.key === 'Escape' && menu.classList.contains('visible')) {
    setMenu(false);
  }
});


// FAQ accordion (mouse + keyboard accessible)

function toggleQuestion(question) {
  const faqToggle = question.querySelector(".faq-toggle");
  const answer = question.nextElementSibling;
  const isOpen = answer.style.display === "block";

  answer.style.display = isOpen ? "none" : "block";
  question.setAttribute('aria-expanded', String(!isOpen));
  if (faqToggle) {
    faqToggle.innerHTML = isOpen
      ? '<i class="fa-solid fa-plus" aria-hidden="true"></i>'
      : '<i class="fa-solid fa-minus" aria-hidden="true"></i>';
  }
}

questions.forEach((question) => {
  question.addEventListener("click", () => toggleQuestion(question));
  question.addEventListener("keydown", (event) => {
    if (event.key === "Enter" || event.key === " ") {
      event.preventDefault();
      toggleQuestion(question);
    }
  });
});


// Contact modal — nav "Contact" opens a business-card style dialog with a
// copy-to-clipboard email. The mailto href stays as the no-JS fallback.
const CONTACT_EMAIL = 'dave@gloriatech.co';

function buildContactModal() {
  const overlay = document.createElement('div');
  overlay.className = 'contact-overlay';
  overlay.innerHTML = `
    <div class="contact-card" role="dialog" aria-modal="true" aria-label="Contact Gloria">
      <button class="contact-close" aria-label="Close contact card"><i class="fa-solid fa-xmark" aria-hidden="true"></i></button>
      <div class="card-top">
        <div class="card-brand">
          <img src="/images/logo.png" alt="" aria-hidden="true">
          <span class="card-wordmark">Gloria</span>
        </div>
        <p class="card-tagline">Care that keeps families close</p>
      </div>
      <div class="card-bottom">
        <p class="card-name">Dave Vacchio</p>
        <p class="card-role">Head of Sales</p>
        <div class="card-email-row">
          <i class="fa-regular fa-envelope" aria-hidden="true"></i>
          <span class="card-email">${CONTACT_EMAIL}</span>
          <button class="copy-email" aria-label="Copy email address">
            <i class="fa-regular fa-copy" aria-hidden="true"></i><span>Copy</span>
          </button>
        </div>
      </div>
    </div>`;
  document.body.appendChild(overlay);

  const close = () => {
    overlay.classList.remove('open');
    if (lastFocus) lastFocus.focus();
  };
  let lastFocus = null;

  overlay.addEventListener('click', (e) => {
    if (e.target === overlay) close();
  });
  overlay.querySelector('.contact-close').addEventListener('click', close);
  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape' && overlay.classList.contains('open')) close();
  });

  const copyBtn = overlay.querySelector('.copy-email');
  copyBtn.addEventListener('click', async () => {
    try {
      await navigator.clipboard.writeText(CONTACT_EMAIL);
      copyBtn.classList.add('copied');
      copyBtn.innerHTML = '<i class="fa-solid fa-check" aria-hidden="true"></i><span>Copied!</span>';
      setTimeout(() => {
        copyBtn.classList.remove('copied');
        copyBtn.innerHTML = '<i class="fa-regular fa-copy" aria-hidden="true"></i><span>Copy</span>';
      }, 2000);
    } catch (err) {
      window.location.href = 'mailto:' + CONTACT_EMAIL;
    }
  });

  return {
    open() {
      lastFocus = document.activeElement;
      overlay.classList.add('open');
      overlay.querySelector('.contact-close').focus();
    },
  };
}

const contactLinks = document.querySelectorAll('a.nav-item[href^="mailto:"], a.menu-item[href^="mailto:"]');
if (contactLinks.length) {
  const modal = buildContactModal();
  contactLinks.forEach((link) => {
    link.addEventListener('click', (e) => {
      e.preventDefault();
      if (menu.classList.contains('visible')) setMenu(false);
      modal.open();
    });
  });
}


// Hero fold sizing — pin the care-settings banner to the bottom edge of the
// first viewport on desktop, whatever the actual header/banner heights are
const heroSplit = document.querySelector('.hero-split');
const headerEl = document.querySelector('header');
const marqueeEl = document.querySelector('.marquee');

function sizeHeroFold() {
  if (!heroSplit || !headerEl || !marqueeEl) return;
  if (window.innerWidth >= 1024) {
    // Fill the first viewport so the care-settings banner sits at its bottom edge
    heroSplit.style.minHeight = Math.max(480, window.innerHeight - headerEl.offsetHeight - marqueeEl.offsetHeight) + 'px';
  } else {
    heroSplit.style.minHeight = '';
  }
}

if (heroSplit) {
  sizeHeroFold();
  window.addEventListener('resize', sizeHeroFold);
  window.addEventListener('load', sizeHeroFold);
}


// Scroll-reveal

const revealEls = document.querySelectorAll('.reveal');
if (revealEls.length && 'IntersectionObserver' in window) {
  const observer = new IntersectionObserver((entries) => {
    entries.forEach((entry) => {
      if (entry.isIntersecting) {
        entry.target.classList.add('is-visible');
        observer.unobserve(entry.target);
      }
    });
  }, { threshold: 0.12 });
  revealEls.forEach((el) => observer.observe(el));
} else {
  revealEls.forEach((el) => el.classList.add('is-visible'));
}


// Form validation

formControls.forEach((formControl, index) => {
  formControl.addEventListener('invalid', (event) => {
    event.preventDefault();

    const input = event.target;

    input.classList.add('error');
    input.setAttribute('aria-invalid', 'true');

    const errorMessage = errorMessages[index];
    if (errorMessage) {
      errorMessage.textContent = getCustomMessage(input);
      errorMessage.style.display = 'block';
    }

    // Move focus to the first invalid control so keyboard and screen
    // reader users land on the field that needs fixing
    if (input.form && input.form.querySelector(':invalid') === input) {
      input.focus();
    }
  });

  // Listen for changes or input corrections
  formControl.addEventListener('input', (event) => {
    const input = event.target;

    if (input.validity.valid) {
      const errorMessage = errorMessages[index];
      if (errorMessage) {
        errorMessage.style.display = 'none';
      }
      input.classList.remove('error');
      input.removeAttribute('aria-invalid');
    }
  });
});



//Helper Functions

// Function to check if click is outside the menu

function isClickOutsideMenu(event) {
  return !menu.contains(event.target) && !menuButton.contains(event.target);
}

// Get custom error message

function getCustomMessage(input) {
  if (input.id === "name") return "Please enter your name.";
  if (input.id === "phone-number") return "Please enter a valid phone number.";
  if (input.id === "email") return "Please enter a valid business email.";
  if (input.id === "company-type") return "Please select a company type.";
  if (input.id === "hear-about") return "Please let us know how you heard about us.";
  return "This field is required.";
}
