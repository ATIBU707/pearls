# ============================================================
# start-ngrok.ps1  —  Start ngrok with your permanent static domain
# Double-click or: Right-click → "Run with PowerShell"
# ============================================================

$ngrokExe = "C:\Users\wassw\AppData\Local\Microsoft\WinGet\Packages\Ngrok.Ngrok_Microsoft.Winget.Source_8wekyb3d8bbwe\ngrok.exe"
$staticUrl = "https://whisking-stoppable-appendage.ngrok-free.dev"

Write-Host ""
Write-Host "========================================" -ForegroundColor Cyan
Write-Host "  Starting ngrok — Pearls of Wisdom     " -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""
Write-Host "  Public URL (permanent):" -ForegroundColor Yellow
Write-Host "  $staticUrl" -ForegroundColor Green
Write-Host ""
Write-Host "  Dashboard: http://localhost:4040" -ForegroundColor Gray
Write-Host ""
Write-Host "  Keep this window open while testing payments!" -ForegroundColor Yellow
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""

# Start ngrok using the config file (which has the static domain)
& $ngrokExe start --all
