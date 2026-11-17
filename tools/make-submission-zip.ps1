<#
Tạo ZIP mã nguồn, giữ lịch sử .git và tệp ẩn như .htaccess.
Đây là gói source; báo cáo, Rubric.xlsx và video được ghép vào gói nộp riêng.
#>
[CmdletBinding()]
param(
    [string]$ProjectRoot = (Split-Path -Parent $PSScriptRoot),
    [string]$DestinationZip,
    [switch]$DryRun
)

$ErrorActionPreference = 'Stop'
$rootItem = Get-Item -LiteralPath $ProjectRoot
if (-not $rootItem.PSIsContainer) { throw 'ProjectRoot phải là một thư mục.' }
$rootPath = $rootItem.FullName.TrimEnd([char[]]'\/')
$rootPrefix = $rootPath + [IO.Path]::DirectorySeparatorChar
if (-not $DestinationZip) {
    $zipName = '52400198_52400215_52400323_GODMINTON_source_{0}.zip' -f (Get-Date -Format 'yyyyMMdd_HHmmss')
    $DestinationZip = Join-Path (Split-Path -Parent $rootPath) $zipName
}
$zipPath = [IO.Path]::GetFullPath($DestinationZip)
if ([IO.Path]::GetExtension($zipPath) -ne '.zip') { throw 'DestinationZip phải có đuôi .zip.' }
if (Test-Path -LiteralPath $zipPath) { throw "File đã tồn tại, hãy chọn tên ZIP khác: $zipPath" }

$excludedRootDirectories = @('.vscode', '.idea', 'node_modules', 'report', 'scratch')

function Get-SourceFiles([string]$DirectoryPath) {
    foreach ($item in Get-ChildItem -LiteralPath $DirectoryPath -Force) {
        if ($item.Attributes -band [IO.FileAttributes]::ReparsePoint) { continue }
        $relative = $item.FullName.Substring($rootPrefix.Length)
        $topDirectory = $relative.Split([char[]]'\/')[0]
        if ($excludedRootDirectories -contains $topDirectory) { continue }
        if ($item.PSIsContainer) {
            if ($item.Name -like '.env*') { continue }
            Get-SourceFiles $item.FullName
            continue
        }
        if ($item.Name -like '.env*' -and $item.Name -ne '.env.example') { continue }
        if ($item.Extension -in @('.log', '.bak', '.tmp', '.zip')) { continue }
        if ($item.Name -in @('Thumbs.db', '.DS_Store')) { continue }
        if ($item.FullName -eq $zipPath) { continue }
        $item
    }
}

# Liệt kê trước khi mở ZIP để không tự nén file đang được tạo.
$sourceFiles = @(Get-SourceFiles $rootPath)
if ($DryRun) {
    $sourceFiles | ForEach-Object { $_.FullName.Substring($rootPrefix.Length).Replace('\', '/') }
    return
}

Add-Type -AssemblyName System.IO.Compression
$zipStream = [IO.File]::Open($zipPath, [IO.FileMode]::CreateNew, [IO.FileAccess]::Write)
$archive = $null
try {
    $archive = New-Object IO.Compression.ZipArchive($zipStream, [IO.Compression.ZipArchiveMode]::Create, $false)
    foreach ($sourceFile in $sourceFiles) {
        $entryName = $sourceFile.FullName.Substring($rootPrefix.Length).Replace('\', '/')
        $entry = $archive.CreateEntry($entryName, [IO.Compression.CompressionLevel]::Optimal)
        $inputStream = [IO.File]::OpenRead($sourceFile.FullName)
        $entryStream = $null
        try {
            $entryStream = $entry.Open()
            $inputStream.CopyTo($entryStream)
        } finally {
            if ($null -ne $entryStream) { $entryStream.Dispose() }
            $inputStream.Dispose()
        }
    }
} finally {
    if ($null -ne $archive) { $archive.Dispose() }
    $zipStream.Dispose()
}

[pscustomobject]@{
    SourceZip = $zipPath
    Files = $sourceFiles.Count
    SizeMB = [math]::Round((Get-Item -LiteralPath $zipPath).Length / 1MB, 2)
}
