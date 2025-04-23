document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('csvUploadForm');
    if (!form) return;

    form.addEventListener('submit', function (e) {
        e.preventDefault();

        const fileInput = document.getElementById('csvFile');
        if (!fileInput.files.length) return alert("Please choose a CSV file.");

        const formData = new FormData();
        formData.append('csvFile', fileInput.files[0]);

        fetch(OC.generateUrl('/apps/driverlicensemgmt/import-csv'), {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            alert(data.message || "Import completed.");
            location.reload(); // Refresh to show new data
        })
        .catch(err => {
            console.error(err);
            alert("Upload failed.");
        });
    });
});