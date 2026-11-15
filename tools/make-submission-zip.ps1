# Script tao file ZIP sach de nop bai do an GODMINTON
# Tu dong loai bo .git (giam hon 18MB dung luong rac) de file zip nhe va chuyen nghiep

$projectName = "52400198_52400215_52400323_GODMINTON"
$parentDir = Split-Path -Parent $PSScriptRoot
$destinationZip = Join-Path (Split-Path -Parent $parentDir) "$projectName.zip"

Write-Host "Dang tao file ZIP nop bai tai: $destinationZip" -ForegroundColor Cyan

# Xoa file cu neu co
if (Test-Path $destinationZip) {
    Remove-Item -Path $destinationZip -Force
}

# Cac thu muc va file can loai bo khi dong goi nop bai
$excludeItems = @(
    ".git",
    ".vscode",
    "*.log"
)

# Thuc hien nen
$itemsToZip = Get-ChildItem -Path $parentDir -Force | Where-Object {
    $name = $_.Name
    $exclude = $false
    foreach ($ex in $excludeItems) {
        if ($name -like $ex) { $exclude = $true; break }
    }
    -not $exclude
}

Compress-Archive -Path $itemsToZip.FullName -DestinationPath $destinationZip -CompressionLevel Optimal

$sizeMB = [math]::Round((Get-Item $destinationZip).Length / 1MB, 2)
Write-Host "Da dong goi thanh cong!" -ForegroundColor Green
Write-Host "File nop bai: $destinationZip ($sizeMB MB)" -ForegroundColor Yellow
