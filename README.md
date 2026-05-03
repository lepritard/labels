╔══════════════════════════════════════════════════════════════╗
║         LAKE FOREST INDUSTRIES — Label Generator            ║
║                  PHP Local Web Server                        ║
╚══════════════════════════════════════════════════════════════╝

CONTENTS
────────
  lf-labels/
  ├── index.php       ← Data entry form (main page)
  ├── preview.php     ← Label renderer / print page
  ├── assets/
  │   └── logo.svg    ← LF logo (embedded in every label)
  └── README.txt      ← This file


REQUIREMENTS
────────────
You need PHP installed. Check if it's already available:
  Windows:  Open Command Prompt → type:  php -v
  Mac:      Open Terminal        → type:  php -v

If PHP is not installed:
  • Windows: https://windows.php.net/download  (get "VS16 x64 Non Thread Safe")
  • Mac:     It ships with macOS, or install via Homebrew:  brew install php


HOW TO RUN
──────────
1. Place the "lf-labels" folder anywhere on your computer
   (e.g., C:\lf-labels  or  ~/lf-labels)

2. Open a terminal / Command Prompt in that folder:
   Windows: Shift+right-click the folder → "Open in Terminal" or "Open Command Window Here"
   Mac:     Right-click folder → "New Terminal at Folder"

3. Start the PHP built-in server:

   php -S 0.0.0.0:8080

   You'll see: "PHP x.x Development Server started at http://0.0.0.0:8080"

4. Open a browser on any computer on your warehouse network and go to:

   http://[YOUR-COMPUTER-IP]:8080

   To find your IP address:
   Windows:  Run  ipconfig  in Command Prompt — look for "IPv4 Address"
   Mac:      Run  ifconfig | grep inet

   Example:  http://192.168.1.25:8080


USAGE
─────
Box Labels tab:
  • Fill in Customer, NA Number, Part Number, Received Date
  • Enter Total Boxes and Standard Qty Per Box
  • Leave "Label Copies" at 1 for normal individual boxes
  • For a non-standard last box (different quantity), fill in the
    "Non-Standard Box" section at the bottom
  • Click "Generate & Preview Labels" — a new tab opens
  • Press Ctrl+P (or Cmd+P on Mac) to print

Pallet Labels tab:
  • Fill in the pallet details
  • Check "MIXED PALLET" to reveal the second-part section
  • Set "Label Copies" to 5 for the five sides of a pallet
  • Click "Generate & Preview Labels"
  • Set your printer paper size to 4" × 12" before printing

STOPPING THE SERVER
───────────────────
Press Ctrl+C in the terminal window where the server is running.

NOTES
─────
• The server only needs to be running when you want to print labels
• All label data stays on your local network — nothing is sent externally
• The logo (assets/logo.svg) is embedded directly in each label
• Barcodes are generated in the browser using JsBarcode (CODE128 format)
  — requires an internet connection on first load for the CDN library
    (you can switch to a local copy of JsBarcode if needed offline)



VERSION v1.4 NOTES
──────────────────
This version adds local font-file support for Century Gothic.

New files:
  assets/fonts/CenturyGothic.ttf
  assets/fonts/CenturyGothic-Bold.ttf

These are placeholder files right now.
Replace them manually with your real Century Gothic font files.

Important:
• Keep the filenames exactly the same
• After replacing them, refresh the browser (Ctrl+F5)
• If the browser still shows the old font, stop/restart the PHP server and refresh again

