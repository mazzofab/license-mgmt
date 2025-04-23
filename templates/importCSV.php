<form id="csvUploadForm">
    <label for="csvFile">Import CSV:</label>
    <input type="file" id="csvFile" name="csvFile" accept=".csv" required />
    <button type="submit">Upload</button>
</form>
<script src="<?php print_unescaped(OC_Helper::linkTo('drivinglicensereminder', 'js/importCSV.js')); ?>"></script>