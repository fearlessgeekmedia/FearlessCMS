<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>File Upload Test</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
        }
        .test-form {
            background: #f5f5f5;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
        }
        input[type="file"] {
            padding: 10px;
            border: 2px solid #ccc;
            border-radius: 4px;
            width: 100%;
            margin: 10px 0;
            cursor: pointer;
        }
        button {
            background: #007cba;
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
        }
        button:hover {
            background: #005a87;
        }
        .result {
            background: #e7f3ff;
            padding: 15px;
            border-left: 4px solid #007cba;
            margin: 20px 0;
        }
        .error {
            background: #ffe7e7;
            border-left-color: #cc0000;
        }
    </style>
</head>
<body>
    <h1>File Upload Test</h1>

    <?php if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['test_file'])): ?>
        <div class="result <?php echo $_FILES['test_file']['error'] === UPLOAD_ERR_OK ? '' : 'error'; ?>">
            <h3>Upload Result:</h3>
            <p><strong>File Name:</strong> <?php echo htmlspecialchars($_FILES['test_file']['name']); ?></p>
            <p><strong>File Size:</strong> <?php echo number_format($_FILES['test_file']['size']); ?> bytes</p>
            <p><strong>File Type:</strong> <?php echo htmlspecialchars($_FILES['test_file']['type']); ?></p>
            <p><strong>Upload Error:</strong> <?php echo $_FILES['test_file']['error']; ?></p>

            <?php if ($_FILES['test_file']['error'] === UPLOAD_ERR_OK): ?>
                <p style="color: green;"><strong>✓ File upload successful!</strong></p>
            <?php else: ?>
                <p style="color: red;"><strong>✗ File upload failed!</strong></p>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <div class="test-form">
        <h2>Test 1: Standard File Input</h2>
        <form method="POST" enctype="multipart/form-data">
            <label>Select a file:</label>
            <input type="file" name="test_file" accept="image/*">
            <button type="submit">Upload Test File</button>
        </form>
    </div>

    <div class="test-form">
        <h2>Test 2: JavaScript Trigger</h2>
        <input type="file" id="js-file" style="display: none;" accept="image/*">
        <button type="button" onclick="document.getElementById('js-file').click()">Trigger File Input with JS</button>
        <div id="js-result"></div>
    </div>

    <div class="test-form">
        <h2>Test 3: Click Event Test</h2>
        <input type="file" id="click-test" accept="image/*" onclick="console.log('File input clicked')" onchange="console.log('File selected:', this.files[0]?.name)">
        <p><small>Check browser console for click/change events</small></p>
    </div>

    <div class="test-form">
        <h2>Browser Info</h2>
        <p><strong>User Agent:</strong> <span id="user-agent"></span></p>
        <p><strong>JavaScript Enabled:</strong> <span id="js-status">No</span></p>
        <p><strong>File API Support:</strong> <span id="file-api"></span></p>
    </div>

    <script>
        // Test JavaScript functionality
        document.getElementById('js-status').textContent = 'Yes';
        document.getElementById('user-agent').textContent = navigator.userAgent;
        document.getElementById('file-api').textContent = window.File ? 'Yes' : 'No';

        // JS file input test
        document.getElementById('js-file').addEventListener('change', function() {
            const result = document.getElementById('js-result');
            if (this.files[0]) {
                result.innerHTML = '<p style="color: green;">✓ JavaScript file selection works! Selected: ' + this.files[0].name + '</p>';
            } else {
                result.innerHTML = '<p style="color: red;">✗ No file selected</p>';
            }
        });

        // Log all click events for debugging
        document.addEventListener('click', function(e) {
            if (e.target.type === 'file') {
                console.log('File input clicked:', e.target);
            }
        });
    </script>
</body>
</html>
