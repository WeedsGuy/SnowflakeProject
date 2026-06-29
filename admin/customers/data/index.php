<?php
require __DIR__ . '/../../../vendor/autoload.php';

set_time_limit(0);
ini_set('memory_limit', '512M');

$successMessage = '';
$errorMessage = '';
$csvFilePath = '';

if (isset($_POST['upload'])) {
    $uploadDir = __DIR__ . '/uploads/';
    if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

    $csvFilePath = $uploadDir . time() . "_" . $_FILES['csv_file']['name'];
    if (move_uploaded_file($_FILES['csv_file']['tmp_name'], $csvFilePath)) {
        $totalRows = count(file($csvFilePath)) - 1; 
    } else {
        $errorMessage = "Failed to upload CSV file!";
    }
}
?>

<?php include __DIR__.'/../../layout/header.php'; ?>
<?php include __DIR__.'/../../layout/sidebar.php'; ?>

<style>
.import-container { padding: 30px; }
.import-card {
    background: #fff;
    border-radius: 10px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.08);
    padding: 16px 20px;
    display: flex;
    flex-direction: column;
    gap: 20px;
}
.import-title { font-size: 18px; font-weight: 600; margin: 0; }
.file-input {
    border: 2px dashed #d1d5db;
    padding: 8px 16px;
    border-radius: 6px;
    background: #f9fafb;
    cursor: pointer;
    transition: 0.3s ease;
    font-size: 14px;
    white-space: nowrap;
}
.file-input:hover { border-color: #3b82f6; background: #f0f7ff; }
.file-input input { display: none; }
.btn-import {
    background: #3b82f6; color: #fff; border: none; padding: 8px 18px;
    border-radius: 6px; font-weight: 600; cursor: pointer; transition: 0.3s ease;
}
.btn-import:hover { background: #2563eb; }
.alert-success { background: #e6fffa; color: #065f46; padding: 10px 14px; border-radius:6px; font-size:14px; }
.alert-error { background: #fee2e2; color: #991b1b; padding: 10px 14px; border-radius:6px; font-size:14px; }
#progressBarContainer { background:#eee; height:20px; border-radius:10px; margin-top:10px; }
#progressBar { height:20px; width:0%; background:#3b82f6; border-radius:10px; }

/* ===== RANGE PROGRESS FINAL STYLE ===== */

#progressSlider {
    -webkit-appearance: none;
    appearance: none;
    width: 100%;
    height: 12px;
    border-radius: 10px;
    outline: none;
    background: #e5e7eb; /* light gray remaining */
    transition: background 0.4s ease-in-out;
}

/* Chrome / Safari track fix */
#progressSlider::-webkit-slider-runnable-track {
    height: 12px;
    border-radius: 10px;
}

/* Firefox track fix */
#progressSlider::-moz-range-track {
    height: 12px;
    border-radius: 10px;
    background: #e5e7eb;
}

/* Slider Thumb (circle) */
#progressSlider::-webkit-slider-thumb {
    -webkit-appearance: none;
    appearance: none;
    width: 16px;
    height: 16px;
    border-radius: 50%;
    background: #2563eb;
    border: 2px solid #ffffff;
    box-shadow: 0 0 4px rgba(0,0,0,0.2);
    cursor: pointer;
    margin-top: -2px; /* align center */
}

#progressSlider::-moz-range-thumb {
    width: 16px;
    height: 16px;
    border-radius: 50%;
    background: #2563eb;
    border: none;
    cursor: pointer;
}


</style>

<div class="main">
    <div class="topbar">
        <h2>Customer - Import CSV</h2>
        <div class="admin-info">Admin User</div>
    </div>

    <div class="import-container">
        <div class="import-card">
        <div class="upload-block">    
        <div class="import-title">Upload CSV File</div>

            <?php if (!empty($successMessage)) : ?>
                <div class="alert-success"><?= $successMessage ?></div>
            <?php endif; ?>
            <?php if (!empty($errorMessage)) : ?>
                <div class="alert-error"><?= $errorMessage ?></div>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data" id="uploadForm">
                <label class="file-input">
                    Click to select CSV file
                    <input type="file" name="csv_file" id="csv_file" accept=".csv" required>
                </label>
                <button type="submit" name="upload" class="btn-import">Upload & Start Import</button>
            </form>
            </div>
            <?php if (!empty($csvFilePath)) : ?>
                <input type="range" id="progressSlider" min="0" max="<?= $totalRows ?>" value="0" style="width:100%;" disabled>
                <div id="progressText">0 / <?= $totalRows ?> rows completed</div>
                <script>
                     let filePath = "<?= str_replace('\\','/',$csvFilePath) ?>";
                    let offset = 0;
                    const totalRows = <?= $totalRows ?>;
                    const chunkSize = 500;

                    function updateProgress(rowsProcessed){
                        document.getElementById('progressSlider').value = rowsProcessed;
                        document.getElementById('progressText').innerText = `${rowsProcessed} / ${totalRows} rows completed`;
                    }

                    function processChunk(){
                        fetch('properties.php', {
                            method:'POST',
                            headers:{'Content-Type':'application/x-www-form-urlencoded'},
                            body: `file=${filePath}&offset=${offset}&limit=${chunkSize}`
                        })
                        .then(res=>res.json())
                        .then(data=>{
                            offset += data.processed;
                            updateProgress(offset);
                            if(data.processed==0){
                               alert("Import Completed!");
                                //window.location.href = window.location.href;
                            }
                            if(offset < totalRows){
                                processChunk();
                            } else {
                                document.getElementById("csv_file").value="";
                                alert("Import Completed!");
                                //window.location.href = window.location.href;
                            }
                        });
                    }
                    processChunk();
                </script>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include __DIR__.'/../../layout/footer.php'; ?>
