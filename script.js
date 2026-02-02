// Confirm before deleting
function confirmDelete() {
  return confirm("Are you sure you want to delete this record?");
}

// Highlight row on hover
document.addEventListener("DOMContentLoaded", () => {
  const rows = document.querySelectorAll("table tr");
  rows.forEach(row => {
    row.addEventListener("mouseover", () => {
      row.style.backgroundColor = "#f0f8ff";
    });
    row.addEventListener("mouseout", () => {
      row.style.backgroundColor = "";
    });
  });
});
