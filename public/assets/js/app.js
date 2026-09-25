/**
 * Comportements JavaScript de l'application.
 *
 * Confirmation des actions destructrices : tout formulaire portant l'attribut
 * data-confirm demande une confirmation avant envoi.
 */
document.addEventListener('submit', (event) => {
  const form = event.target;

  if (form instanceof HTMLFormElement && form.dataset.confirm && !window.confirm(form.dataset.confirm)) {
    event.preventDefault();
  }
});
