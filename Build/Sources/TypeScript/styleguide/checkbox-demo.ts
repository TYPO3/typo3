document.querySelectorAll('.example input[type="checkbox"]').forEach((element: HTMLInputElement) => {
  if (element.id.includes('indeterminate')) {
    element.indeterminate = true;
  }
});
