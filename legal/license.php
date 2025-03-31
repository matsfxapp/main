<?php
require_once '../config/config.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="matSFX - The new way to listen with Joy! Ad-free and Open-Source, can it be even better?" />
    <meta property="og:title" content="matSFX - Listen with Joy!" />
    <meta property="og:description" content="Experience ad-free music, unique Songs and Artists, a new and modern look!" />
    <meta property="og:image" content="https://alpha.matsfx.com/app_logos/matsfx_logo.png" />
    <meta property="og:type" content="website" />
    <meta property="og:url" content="https://matsfx.com/" />
    <title>matSFX License</title>
    <link rel="icon" type="image/png" href="/app_logos/matsfx_logo.png">
    <link rel="shortcut icon" type="image/png" href="/app_logos/matsfx_logo.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
    <?php if (function_exists('outputChristmasThemeCSS')) outputChristmasThemeCSS(); ?>
    <style>
        :root {
            --primary-color: #2D7FF9;
            --primary-hover: #1E6AD4;
            --primary-light: rgba(45, 127, 249, 0.1);
            --accent-color: #18BFFF;
            --dark-bg: #0A1220;
            --darker-bg: #060912;
            --light-text: #FFFFFF;
            --gray-text: #94A3B8;
            --border-color: #1F2937;
        }

        .license-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 2rem 1.25rem 6rem;
        }

        @media (min-width: 768px) {
            .license-container {
                padding: 2rem 2rem 6rem;
            }
        }

        h1 {
            color: var(--primary-color);
            font-size: 2rem;
            margin-bottom: 2rem;
            line-height: 1.2;
        }

        @media (min-width: 768px) {
            h1 {
                font-size: 2.5rem;
            }
        }

        .copyright-notice {
            color: var(--gray-text);
            margin-bottom: 2rem;
            font-size: 0.9375rem;
        }

        strong {
            color: var(--accent-color);
            display: block;
            margin-top: 1.5rem;
            margin-bottom: 0.75rem;
            font-size: 1.125rem;
        }

        ol {
            list-style: none;
            counter-reset: license-counter;
            padding: 0;
            margin: 2rem 0;
        }

        ol > li {
            counter-increment: license-counter;
            margin-bottom: 1.5rem;
        }

        ol > li::before {
            content: counter(license-counter) ". ";
            color: var(--primary-color);
            font-weight: bold;
        }

        ul {
            padding-left: 1.25rem;
            margin: 0.75rem 0;
        }

        ul li {
            margin: 0.5rem 0;
        }

        ul ul {
            margin: 0.5rem 0;
        }

        .warranty {
            background-color: var(--darker-bg);
            padding: 1.25rem;
            border-radius: 4px;
            margin: 1.5rem 0;
            font-size: 0.9375rem;
        }

        .footer {
            color: var(--gray-text);
            text-align: center;
            margin-top: 3rem;
            padding-top: 1.5rem;
            border-top: 1px solid var(--border-color);
            font-size: 0.9375rem;
        }

        p {
            margin: 1rem 0;
        }
        
        .section-divider {
            border-top: 1px solid var(--border-color);
            margin: 2rem 0;
        }
        
        .summary-section {
            background-color: rgba(45, 127, 249, 0.1);
            border-radius: 8px;
            padding: 1.5rem;
            margin: 2rem 0;
        }
        
        .summary-title {
            color: var(--accent-color);
            font-weight: bold;
            margin-bottom: 1rem;
        }
        
        .allowed-list, .not-allowed-list {
            list-style-type: none;
            padding-left: 0.5rem;
        }
        
        .allowed-list li:before {
            content: "✓ ";
            color: #4ADE80;
            font-weight: bold;
            margin-right: 0.5rem;
        }
        
        .not-allowed-list li:before {
            content: "✗ ";
            color: #F87171;
            font-weight: bold;
            margin-right: 0.5rem;
        }
        
        .emphasis {
            font-weight: bold;
            color: var(--accent-color);
            font-style: italic;
            display: block;
            margin: 1rem 0;
            text-align: center;
        }
        
        .consequences-section {
            background-color: rgba(248, 113, 113, 0.1);
            border-radius: 8px;
            padding: 1.5rem;
            margin: 2rem 0;
        }
        
        .header-spacer {
            height: 65px;
            width: 100%;
            display: block;
        }
    </style>
</head>
<body>
    <?php require_once '../includes/header.php'; ?>
    
    <div class="header-spacer"></div>

    <div class="license-container">
        <h1>matSFX License – as of 30th March 2025</h1>
        
        <div class="copyright-notice">Copyright (c) 2024-2025 matSFX</div>
        
        <div class="section-divider"></div>
        
        <div class="summary-section">
            <div class="summary-title">Summary</div>
            <p>This software is open source, but <strong style="display: inline; color: var(--light-text);">not public domain</strong>.</p>
            
            <p>You are allowed to:</p>
            <ul class="allowed-list">
                <li>Use, modify, and share the code</li>
                <li>Build your own projects based on it</li>
            </ul>
            
            <p>You are <strong style="display: inline; color: var(--light-text);">not allowed</strong> to:</p>
            <ul class="not-allowed-list">
                <li>Use the name "matSFX" or its logos</li>
                <li>Copy the exact design or branding</li>
                <li>Keep the original contact info</li>
                <li>Distribute without proper attribution</li>
            </ul>
            
            <p class="emphasis">Make it your own if you fork it—respect the work behind this project.</p>
        </div>
        
        <div class="section-divider"></div>

        <h2>License Terms</h2>
        <ol>
            <li>
                <strong>Name and Branding Requirements</strong>
                <ul>
                    <li>The name "matSFX" is copyrighted and must not be used in any derivative work or redistribution of this Software. The user is required to rename the project to a unique and distinct name.</li>
                    <li>The logo, app icon, and any branding (including but not limited to default icons and badges) used in the original project are also copyrighted and must be replaced in any derivative work.</li>
                </ul>
            </li>

            <li>
                <strong>Design and Styling Modifications</strong>
                <ul>
                    <li>The user is required to change at least one visual aspect of the project to ensure the derivative work is distinguishable from the original. For example:
                        <ul>
                            <li>Changing the background color.</li>
                            <li>Customizing the styling, layout, or theme.</li>
                        </ul>
                    </li>
                    <li>All default icons and badges provided in the original Software must be replaced with user-created designs or legally obtained alternatives.</li>
                </ul>
            </li>

            <li>
                <strong>Terms and Contact Information</strong>
                <ul>
                    <li>Any derivative work must replace the terms of service, privacy policy, and contact information provided in the original project with those of the user's own creation.</li>
                </ul>
            </li>

            <li>
                <strong>Attribution</strong>
                <ul>
                    <li>While the name and branding must be replaced, appropriate attribution to the original matSFX project must be provided in the documentation or credits of the derivative work. This attribution should include:
                        <ul>
                            <li>A reference to the original repository or source.</li>
                            <li>A note that the derivative work was based on matSFX, without implying endorsement by the original creators.</li>
                        </ul>
                    </li>
                </ul>
            </li>

            <li>
                <strong>Prohibited Uses</strong>
                <ul>
                    <li>The Software and its derivatives must not be used for any unlawful or unethical purposes.</li>
                </ul>
            </li>

            <li>
                <strong>Warranty Disclaimer</strong>
                <div class="warranty">
                    THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE, AND NON-INFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES, OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT, OR OTHERWISE, ARISING FROM, OUT OF, OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
                </div>
            </li>
        </ol>
        
        <div class="section-divider"></div>
        
        <div class="consequences-section">
            <strong style="color: #F87171;">Violation Consequences</strong>
            <p>Failure to follow this license may result in:</p>
            <ul>
                <li>DMCA takedown requests or platform-level removals</li>
                <li>Public notice of license violations</li>
                <li>Legal action depending on the severity</li>
            </ul>
            <p>Respect the license. Keep open source fair and ethical.</p>
        </div>
        
        <div class="section-divider"></div>

        <div class="footer">
            By using, modifying, or redistributing this Software, you agree to the terms of this license.
        </div>
    </div>

    <div class="player-spacer"></div>
    <?php require_once '../includes/player.php'; ?>
    
    <script src="js/search.js"></script>
</body>
</html>
