/**
 * ND Booking Mobile Fixes - JavaScript for text modification and reordering
 */

document.addEventListener('DOMContentLoaded', function() {
  if (!document.body.classList.contains('loft1325-mobile-unified-active')) {
    return;
  }

  function applyFixes() {
    // 1. Rename "Grand Total" to "Total"
    const grandTotalLabels = document.querySelectorAll('th, td, span, strong, p');
    grandTotalLabels.forEach(label => {
      if (label.textContent.trim().toLowerCase() === 'grand total') {
        label.textContent = 'Total';
      }
      if (label.textContent.trim().toLowerCase() === 'lodging tax') {
        label.textContent = 'Taxe d\'hébergement';
      }
    });

    // 2. Hide "Total Tax" rows
    const rows = document.querySelectorAll('tr');
    rows.forEach(row => {
      if (row.textContent.toLowerCase().includes('total tax')) {
        row.style.display = 'none';
      }
    });

    // 3. Rename "FINALISER" button to "RÉSERVER" and make it the primary button
    const finaliseBtn = document.querySelector('.sticky-bar .primary-button') || document.querySelector('button.primary-button');
    if (finaliseBtn) {
      finaliseBtn.textContent = 'RÉSERVER';
      
      // If it doesn't have a click handler to submit the form, add one
      finaliseBtn.addEventListener('click', function(e) {
        const form = document.querySelector('form.nd_booking_form') || document.querySelector('form');
        if (form) {
          form.submit();
        }
      });
    }

    // 4. Move reviews to the end of the content
    const reviewsSection = document.querySelector('.nd_booking_single_cpt_1_reviews') || document.querySelector('[class*="review"]');
    const mainContent = document.querySelector('main') || document.querySelector('.mobile-shell') || document.body;
    if (reviewsSection && mainContent) {
      mainContent.appendChild(reviewsSection);
    }

    // 5. Hide the duplicate "RÉSERVER" buttons from the form
    const bookingButtons = document.querySelectorAll('.nd_booking_button');
    bookingButtons.forEach(btn => {
      btn.style.display = 'none';
    });
  }

  // Run fixes immediately and then every second for 5 seconds to catch dynamic content
  applyFixes();
  let count = 0;
  const interval = setInterval(() => {
    applyFixes();
    count++;
    if (count > 5) clearInterval(interval);
  }, 1000);
});
