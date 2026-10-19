<?php require __DIR__ . '/app/bootstrap.php'; ?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>InnPulse — Sign In</title>
    <!-- Login is now a modal on the main page. Redirect there. -->
    <script>
        // If someone lands directly on login.php (e.g. old bookmark, back-button),
        // send them to index.php and auto-open the login modal.
        window.location.replace('index.php?login=1');
    </script>
</head>

<body></body>

</html>