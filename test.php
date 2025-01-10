<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your Playlists</title>
    <style>
        :root {
            --primary-color: #2D7FF9;
            --primary-hover: #1E6AD4;
            --primary-light: rgba(45, 127, 249, 0.1);
            --accent-color: #18BFFF;
            --dark-bg: #0A1220;
            --darker-bg: #060912;
            --card-bg: #111827;
            --card-hover: #1F2937;
            --nav-bg: rgba(17, 24, 39, 0.95);
            --light-text: #FFFFFF;
            --gray-text: #94A3B8;
            --border-color: #1F2937;
            --border-radius: 12px;
            --border-radius-lg: 16px;
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            --shadow-sm: 0 2px 8px rgba(0, 0, 0, 0.2);
            --shadow-md: 0 4px 16px rgba(0, 0, 0, 0.3);
            --shadow-lg: 0 8px 24px rgba(0, 0, 0, 0.4);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            background-color: var(--dark-bg);
            font-family: system-ui, -apple-system, sans-serif;
            color: var(--light-text);
        }

        .main-content {
            padding: 0 20px;
        }

        .playlists-title {
            font-size: 15px;
            margin: 15px 0;
        }

        .playlists-grid {
            display: flex;
            gap: 15px;
            margin-bottom: 25px;
        }

        .playlist-box {
            width: 100px;
            height: 100px;
            background-color: var(--card-bg);
            border-radius: var(--border-radius);
            position: relative;
            cursor: pointer;
            transition: var(--transition);
            margin-bottom: 8px;
        }

        .playlist-box:hover {
            background-color: var(--card-hover);
        }

        .play-button {
            position: absolute;
            bottom: 8px;
            right: 8px;
            opacity: 0;
            transition: var(--transition);
            font-size: 14px;
        }

        .playlist-box:hover .play-button {
            opacity: 1;
        }

        .playlist-name {
            color: var(--gray-text);
            font-size: 13px;
        }

        .add-button {
            width: 35px;
            height: 35px;
            border-radius: 50%;
            background-color: var(--primary-color);
            border: none;
            color: var(--light-text);
            font-size: 20px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: var(--transition);
            margin-top: 32px;
        }

        .add-button:hover {
            background-color: var(--primary-hover);
            transform: scale(1.1);
        }

        .divider {
            text-align: center;
            color: var(--gray-text);
            margin: 20px 0;
            font-size: 13px;
        }

        .copy-button {
            background-color: var(--primary-color);
            color: var(--light-text);
            border: none;
            padding: 10px 25px;
            border-radius: var(--border-radius);
            font-size: 14px;
            cursor: pointer;
            transition: var(--transition);
            display: block;
            margin: 0 auto;
        }

        .copy-button:hover {
            background-color: var(--primary-hover);
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="main-content">
        <div class="playlists-title">Your Playlists</div>
        
        <div class="playlists-grid">
            <div class="playlist">
                <div class="playlist-box">
                    <div class="play-button">▶</div>
                </div>
                <div class="playlist-name">Playlist #1</div>
            </div>

            <div class="playlist">
                <div class="playlist-box">
                    <div class="play-button">▶</div>
                </div>
                <div class="playlist-name">Playlist #2</div>
            </div>

            <div class="playlist">
                <div class="playlist-box"></div>
                <div class="playlist-name">Playlist #3</div>
            </div>

            <div class="playlist">
                <div class="playlist-box"></div>
                <div class="playlist-name">Playlist #4</div>
            </div>

            <div class="playlist">
                <button class="add-button">+</button>
            </div>
        </div>

        <div class="divider">or</div>

        <button class="copy-button">Copy Playlist</button>
    </div>
</body>
</html>