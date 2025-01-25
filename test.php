<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="monetag" content="5b5da452bb7f578199b5f1d963c7b3bf">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ads Test</title>
    <style>
        body {
            margin: 0;
            padding: 0;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .content {
            flex: 1;
        }

        .ad-container {
            width: 100%;
            text-align: center;
            padding: 10px;
            box-sizing: border-box;
        }
    </style>
</head>
<body>
    <div class="content">
        <h1>Testpage for ads</h1>
        <p>If it works, the ad will be displayed.</p>
    </div>

    <div class="ad-container">
        <script>
            (function(d, z, s) {
                s.src = 'https://' + d + '/400/' + z;
                try {
                    (document.body || document.documentElement).appendChild(s);
                } catch (e) {}
            })('vemtoutcheeg.com', 8833185, document.createElement('script'));
        </script>
    </div>
</body>
</html>
