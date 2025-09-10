// Search filter
document.getElementById("searchInput").addEventListener("keyup", function () {
  let filter = this.value.toLowerCase();
  let rows = document.querySelectorAll("#uploadTable tbody tr");

  rows.forEach(row => {
    let text = row.innerText.toLowerCase();
    row.style.display = text.includes(filter) ? "" : "none";
  });
});

// Sorting
document.getElementById("sortSelect").addEventListener("change", function () {
  let rows = Array.from(document.querySelectorAll("#uploadTable tbody tr"));
  let tbody = document.querySelector("#uploadTable tbody");
  let type = this.value;

  let getValue = (row) => {
    switch (type) {
      case "file": return row.cells[0].innerText.toLowerCase();
      case "town": return row.cells[1].innerText.toLowerCase();
      case "status": return row.cells[2].innerText.toLowerCase();
      case "time": return row.cells[3].innerText;
      case "user": return row.cells[4].innerText.toLowerCase();
      default: return "";
    }
  };

  rows.sort((a, b) => getValue(a).localeCompare(getValue(b)));
  rows.forEach(r => tbody.appendChild(r));
});

function deleteFile(id) {
    if (confirm("Are you sure you want to delete this file?")) {
        window.location.href = "delete_file.php?id=" + id;
    }
}


