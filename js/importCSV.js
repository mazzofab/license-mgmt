document.getElementById('csvUploadForm').addEventListener('submit', function(e) {
    e.preventDefault();

    const formData = new FormData();
    const fileInput = document.getElementById('csvFile');
    formData.append('csvFile', fileInput.files[0]);

    fetch(OC.generateUrl('/apps/drivinglicensereminder/import-csv'), {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        alert(data.message);
        location.reload();
    })
    .catch(err => alert("Import failed: " + err));
});