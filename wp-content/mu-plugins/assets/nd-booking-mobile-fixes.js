/**
 * ND Booking Mobile Fixes - JavaScript for text modification and reordering
 */

document.addEventListener('DOMContentLoaded', function() {
  if (!document.body.classList.contains('loft1325-mobile-unified-active')) {
    return;
  }

  function applyFixes() {
    const bookingAction = getBookingAction();

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

    // 3. Keep the bottom "FINALISER" button as the only primary CTA and wire it to booking submit
    const finaliseBtn = document.querySelector('.sticky-bar .primary-button') || document.querySelector('button.primary-button');
    if (finaliseBtn) {
      if (bookingAction && bookingAction.href && finaliseBtn.tagName.toLowerCase() === 'a') {
        finaliseBtn.setAttribute('href', bookingAction.href);
      }

      if (!finaliseBtn.dataset.loft1325BookingBound) {
        finaliseBtn.addEventListener('click', function(e) {
          if (bookingAction && bookingAction.submit) {
            e.preventDefault();
            bookingAction.submit();
            return;
          }

          const form = document.querySelector('form.nd_booking_form') || document.querySelector('form');
          if (form) {
            e.preventDefault();
            form.submit();
          }
        });
        finaliseBtn.dataset.loft1325BookingBound = '1';
      }
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

  function getBookingAction() {
    const firstVisibleBookingButton = Array.from(document.querySelectorAll('.nd_booking_button, input[type="submit"].nd_booking_button, a.nd_booking_button'))
      .find(btn => window.getComputedStyle(btn).display !== 'none');

    if (!firstVisibleBookingButton) {
      return null;
    }

    const tagName = firstVisibleBookingButton.tagName.toLowerCase();
    if (tagName === 'a') {
      return {
        href: firstVisibleBookingButton.getAttribute('href') || '',
        submit: function() {
          firstVisibleBookingButton.click();
        }
      };
    }

    return {
      submit: function() {
        firstVisibleBookingButton.click();
      }
    };
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
