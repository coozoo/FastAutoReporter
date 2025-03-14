<?php

$myreporter = basename(dirname(__FILE__));
if (basename($_SERVER['DOCUMENT_ROOT']) == $myreporter) {
    $myreporter = "";
}
include($_SERVER['DOCUMENT_ROOT'] . "/$myreporter/initvar.php");


echo("
<!DOCTYPE html>
<html lang=\"en\">
<head>
    <meta charset=\"UTF-8\">
    <meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0\">
    <title>Loading...</title>
    <link rel='shortcut icon' type='image/png' href=\"$iconfile\" />");
?>
    <style>
        /* Fullscreen overlay */
        #overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(255, 255, 255, 0.9);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 9999;
        }

        /* Simple spinner */
        .spinner {
            width: 50px;
            height: 50px;
            border: 5px solid #ccc;
            border-top: 5px solid #000;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>

    <script>
        function loadContent() {
            // Fetch getxraycase.php with the query parameters from the loader
            fetch('getxraycasepage.php' + window.location.search)
                .then(response => response.text())
                .then(html => {
                    document.open();
                    document.write(html);
                    document.close();
                })
                .catch(error => console.error("Error loading content:", error));
        }

        setTimeout(loadContent, 300); // Start loading content after 300ms
    </script>
</head>
<body>
    <div id="overlay">
        <div class="spinner"></div>
    </div>
</body>
</html>

