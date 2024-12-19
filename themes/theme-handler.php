<?php
function isChristmasThemeEnabled() {
    return isset($_COOKIE['christmas_theme']) && $_COOKIE['christmas_theme'] === 'enabled';
}

if (basename($_SERVER['PHP_SELF']) === 'settings.php' && isset($_POST['toggle_christmas_theme'])) {
    if (isChristmasThemeEnabled()) {
        setcookie('christmas_theme', '', time() - 3600, '/');
    } else {
        setcookie('christmas_theme', 'enabled', time() + (86400 * 30), '/');
    }
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

function outputChristmasThemeCSS() {
    if (isChristmasThemeEnabled()) {
        echo '<style>
            :root {
                --primary-color: #D42426;
                --primary-hover: #B71C1C;
                --primary-light: rgba(212, 36, 38, 0.1);
                --accent-color: #2E7D32;
                --dark-bg: #1C2834;
                --darker-bg: #131E28;
                --card-bg: #243447;
                --card-hover: #2C3E50;
                --nav-bg: rgba(36, 52, 71, 0.95);
                --light-text: #F8F9FA;
                --gray-text: #B8C5C5;
                --border-color: #2C3E50;
                
                /* Christmas-specific colors */
                --christmas-red: #D42426;
                --christmas-green: #2E7D32;
                --christmas-gold: #FFD700;
                --snow-white: #FFFFFF;
            }

            .snowflake {
                position: fixed;
                top: -10px;
                animation: snowfall linear infinite;
            }

            @keyframes snowfall {
                0% {
                    transform: translateY(-10px) rotate(0deg);
                }
                100% {
                    transform: translateY(100vh) rotate(360deg);
                }
            }

            .card {
                border: 1px solid var(--christmas-gold) !important;
                box-shadow: 0 0 15px rgba(255, 215, 0, 0.1) !important;
            }

            a:not(.btn) {
                color: var(--christmas-red);
                transition: color 0.3s ease;
            }

            a:not(.btn):hover {
                color: var(--christmas-green);
            }

            h1, h2, h3, h4, h5, h6 {
                color: var(--christmas-red);
                text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.1);
            }

            .btn-primary {
                background: linear-gradient(45deg, var(--christmas-red), var(--christmas-green));
                border: none;
                box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
            }

            .btn-primary:hover {
                transform: translateY(-2px);
                box-shadow: 0 6px 20px rgba(0, 0, 0, 0.3);
            }
        </style>';

        echo '<script>
            function createSnowflakes() {
                const snowflakesCount = 50;
                const container = document.body;
                
                for (let i = 0; i < snowflakesCount; i++) {
                    const snowflake = document.createElement("div");
                    snowflake.className = "snowflake";
                    snowflake.style.left = `${Math.random() * 100}vw`;
                    snowflake.style.opacity = Math.random();
                    snowflake.style.animation = `snowfall ${Math.random() * 3 + 2}s linear infinite`;
                    snowflake.innerHTML = "❄";
                    snowflake.style.color = "rgba(255, 255, 255, 0.5)";
                    snowflake.style.fontSize = `${Math.random() * 10 + 10}px`;
                    container.appendChild(snowflake);
                }
            }
            
            if (!document.getElementById("snowflakes-loaded")) {
                createSnowflakes();
                const marker = document.createElement("div");
                marker.id = "snowflakes-loaded";
                marker.style.display = "none";
                document.body.appendChild(marker);
            }
        </script>';
    }
}
?>