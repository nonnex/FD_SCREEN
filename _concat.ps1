# --------------------------------------
# Configuration Section
# --------------------------------------

# Output file for concatenation
$outfile = "_concat_out.txt"

# Enable headers for each file ($true = yes, $false = no)
$use_headers = $true

# Header format
$header_start = "/*------------------------------"
$header_file = "File: {0}"
$header_end = "------------------------------*/"

# Use absolute paths in headers ($true = absolute, $false = relative)
$use_absolute_paths = $false

# Add blank lines between files ($true = yes, $false = no)
$add_blank_lines = $true

# Enable minification ($true = yes, $false = no)
$enable_minification = $true

# Path to NUglify.dll (will be set dynamically if installed)
$nuglify_dll_path = ".\lib\netstandard2.0\NUglify.dll"

# Enable dry-run mode ($true = preview only, $false = execute)
$dry_run = $false

# Enable logging ($true = write log file, $false = console only)
$enable_logging = $true
$log_file = "_concat_log.txt"

# Clean stale minified files ($true = remove outdated .min files, $false = keep)
$clean_stale_minified = $true

# Incremental processing ($true = process only modified files, $false = process all)
$incremental = $true
$last_run_file = "_concat_last_run.txt"

# Minification definitions (array of hashtables)
# Format: @{ Path = "path"; Recursive = $true/$false; Patterns = "pattern1 pattern2 ..."; ExcludePatterns = "pattern1 pattern2 ..." }
$minify = @(
    @{ Path = "js";  Recursive = $true; Patterns = "*.js"; ExcludePatterns = "*.test.js *.spec.js" }
    @{ Path = "css"; Recursive = $true; Patterns = "*.css"; ExcludePatterns = "*.min.css" }
    @{ Path = "css/style.css"; Recursive = $false; Patterns = "*.css"; ExcludePatterns = "" }
)

# Source definitions for concatenation (array of hashtables)
# Format: @{ Path = "path"; Recursive = $true/$false; Patterns = "pattern1 pattern2 ..."; UseMinified = $true/$false; ExcludePatterns = "pattern1 pattern2 ..." }
$sources = @(
    @{ Path = ".";          Recursive = $false; Patterns = "*.php"; UseMinified = $false; ExcludePatterns = "" }
    @{ Path = "inc";        Recursive = $false; Patterns = "*.php"; UseMinified = $false; ExcludePatterns = "" }
    @{ Path = "inc/db";     Recursive = $false; Patterns = "*.php"; UseMinified = $false; ExcludePatterns = "" }
    @{ Path = "mock_data";  Recursive = $true;  Patterns = "*.php"; UseMinified = $false; ExcludePatterns = "" }
    @{ Path = "js";         Recursive = $false; Patterns = "*.js";  UseMinified = $true;  ExcludePatterns = "*.test.js *.spec.js" }
    @{ Path = "css";        Recursive = $false; Patterns = "*.css"; UseMinified = $true;  ExcludePatterns = "*.min.css" }
)

# --------------------------------------
# Script Logic
# --------------------------------------

# Initialize logging
function Write-Log {
    param (
        [string]$Message,
        [string]$Level = "INFO"
    )
    $timestamp = Get-Date -Format "yyyy-MM-dd HH:mm:ss"
    $log_message = "[$timestamp] [$Level] $Message"
    if ($enable_logging -and -not $dry_run) {
        $log_message | Out-File -FilePath $log_file -Append -Encoding utf8
    }
    switch ($Level) {
        "ERROR" { Write-Host $log_message -ForegroundColor Red }
        "WARNING" { Write-Host $log_message -ForegroundColor Yellow }
        "INFO" { Write-Host $log_message -ForegroundColor White }
        default { Write-Host $log_message }
    }
}

# Function to install NUglify
function Install-NUglify {
    param (
        [string]$Destination = "."
    )
    Write-Log "Installing NUglify package..."
    try {
        # Download NuGet.exe if not present
        $nuget_path = ".\nuget.exe"
        if (-not (Test-Path $nuget_path)) {
            Write-Log "Downloading NuGet.exe..."
            Invoke-WebRequest -Uri "https://dist.nuget.org/win-x86-commandline/latest/nuget.exe" -OutFile $nuget_path
        }

        # Install NUglify package
        $install_dir = Join-Path $Destination "NUglify"
        & $nuget_path install NUglify -OutputDirectory $Destination -ExcludeVersion
        $dll_path = Join-Path $install_dir "lib\netstandard2.0\NUglify.dll"
        if (Test-Path $dll_path) {
            Write-Log "NUglify installed successfully at $dll_path"
            return $dll_path
        } else {
            Write-Log "Failed to locate NUglify.dll after installation" "ERROR"
            return $null
        }
    } catch {
        Write-Log "Failed to install NUglify: $_" "ERROR"
        return $null
    }
}

# Validate configuration and environment
function Validate-Environment {
    # Check and install NUglify if missing
    if ($enable_minification -and -not (Test-Path $nuglify_dll_path)) {
        Write-Log "NUglify.dll not found at $nuglify_dll_path. Attempting to install..."
        $script:nuglify_dll_path = Install-NUglify -Destination "."
        if (-not $script:nuglify_dll_path) {
            Write-Log "NUglify installation failed. Minification disabled." "ERROR"
            $script:enable_minification = $false
        }
    }

    # Validate output file
    if (-not $dry_run -and (Test-Path $outfile)) {
        try {
            Remove-Item $outfile -ErrorAction Stop
            Write-Log "Deleted existing output file: $outfile"
        } catch {
            Write-Log "Failed to delete $outfile. Check permissions. ($_)" "ERROR"
            exit 1
        }
    }

    # Initialize log file
    if ($enable_logging -and -not $dry_run) {
        try {
            "" | Out-File -FilePath $log_file -Encoding utf8 -ErrorAction Stop
            Write-Log "Initialized log file: $log_file"
        } catch {
            Write-Log "Failed to initialize log file $log_file. Logging disabled. ($_)" "WARNING"
            $script:enable_logging = $false
        }
    }

    # Validate source and minify paths
    foreach ($src in $sources) {
        if (-not (Test-Path $src.Path)) {
            Write-Log "Source path does not exist: $($src.Path)" "WARNING"
        }
    }
    foreach ($min in $minify) {
        if (-not (Test-Path $min.Path)) {
            Write-Log "Minify path does not exist: $($min.Path)" "WARNING"
        }
    }
}

# Load NUglify for minification
if ($enable_minification) {
    try {
        Add-Type -Path $nuglify_dll_path -ErrorAction Stop
        Write-Log "Loaded NUglify.dll successfully"
    } catch {
        Write-Log "Failed to load NUglify.dll at $nuglify_dll_path. Minification disabled. ($_)" "ERROR"
        $script:enable_minification = $false
    }
}

# Function to minify a file
function Minify-File {
    param (
        [string]$FilePath
    )
    $extension = [System.IO.Path]::GetExtension($FilePath).ToLower()
    $minified_path = [System.IO.Path]::ChangeExtension($FilePath, ".min$extension")
    $original_size = (Get-Item $FilePath).Length / 1KB
    
    # Skip if minified file exists and is up-to-date
    if (Test-Path $minified_path) {
        $original_last_write = (Get-Item $FilePath).LastWriteTime
        $minified_last_write = (Get-Item $minified_path).LastWriteTime
        if ($minified_last_write -ge $original_last_write) {
            Write-Log "Skipped (up-to-date): $minified_path" "INFO"
            return $minified_path
        }
    }

    if ($dry_run) {
        Write-Log "Would minify: $FilePath -> $minified_path" "INFO"
        return $minified_path
    }

    try {
        $content = Get-Content -Path $FilePath -Raw -Encoding utf8
        $result = $null
        if ($extension -eq ".js") {
            $result = [Uglify]::Js($content)
        } elseif ($extension -eq ".css") {
            $result = [Uglify]::Css($content)
        } else {
            Write-Log "Unsupported file type for minification: $FilePath" "WARNING"
            return $null
        }

        if ($result.HasErrors) {
            Write-Log "Minification failed for $FilePath" "ERROR"
            foreach ($error in $result.Errors) {
                Write-Log "  $error" "ERROR"
            }
            return $null
        }

        $result.Code | Out-File -FilePath $minified_path -Encoding utf8
        $minified_size = (Get-Item $minified_path).Length / 1KB
        Write-Log "Minified: $FilePath -> $minified_path (Size: $original_size KB -> $minified_size KB)" "INFO"
        return $minified_path
    } catch {
        Write-Log "Failed to minify $FilePath ($_)" "ERROR"
        return $null
    }
}

# Function to clean stale minified files
function Clean-StaleMinified {
    param (
        [string]$Path,
        [bool]$Recursive,
        [string[]]$Patterns
    )
    $files = @()
    foreach ($pattern in $Patterns) {
        if ($Recursive) {
            $files += Get-ChildItem -Path $Path -Include $pattern -Recurse -File
        } else {
            $files += Get-ChildItem -Path $Path -Filter $pattern -File
        }
    }
    foreach ($file in $files) {
        $minified_path = [System.IO.Path]::ChangeExtension($file.FullName, ".min" + [System.IO.Path]::GetExtension($file.FullName))
        if (Test-Path $minified_path) {
            $original_exists = Test-Path $file.FullName
            $minified_last_write = (Get-Item $minified_path).LastWriteTime
            $original_last_write = if ($original_exists) { (Get-Item $file.FullName).LastWriteTime } else { $null }
            if (-not $original_exists -or ($minified_last_write -lt $original_last_write)) {
                if ($dry_run) {
                    Write-Log "Would delete stale minified file: $minified_path" "INFO"
                } else {
                    Remove-Item $minified_path -ErrorAction SilentlyContinue
                    Write-Log "Deleted stale minified file: $minified_path" "INFO"
                }
            }
        }
    }
}

# Function to check if file should be processed (incremental mode)
function Should-ProcessFile {
    param (
        [string]$FilePath
    )
    if (-not $incremental -or -not (Test-Path $last_run_file)) {
        return $true
    }
    $last_run = Get-Content $last_run_file -Raw -ErrorAction SilentlyContinue | ForEach-Object { [datetime]::Parse($_) }
    if (-not $last_run) {
        return $true
    }
    $file_last_write = (Get-Item $FilePath).LastWriteTime
    return $file_last_write -gt $last_run
}

# Initialize array to track processed files
$processed_files = @()

# Initialize counters
$total_files = 0
$total_minified = 0

# Validate environment
Validate-Environment

# Process minification
if ($enable_minification) {
    Write-Log "Starting minification process..."
    $minify_files = @()
    for ($i = 0; $i -lt $minify.Length; $i++) {
        $min = $minify[$i]
        $min_path = $min.Path
        $min_recursive = $min.Recursive
        $min_patterns = $min.Patterns
        $min_exclude_patterns = $min.ExcludePatterns

        $recursive_text = if ($min_recursive) { "recursive" } else { "non-recursive" }
        Write-Log "Processing minification source $($i + 1): ""$min_path"" ($recursive_text|$min_patterns|exclude: $min_exclude_patterns)"

        if ($clean_stale_minified -and -not $dry_run) {
            Clean-StaleMinified -Path $min_path -Recursive $min_recursive -Patterns $min_patterns
        }

        if (Test-Path $min_path -PathType Container) {
            foreach ($pattern in $min_patterns.Split()) {
                if ($min_recursive) {
                    $minify_files += Get-ChildItem -Path $min_path -Include $pattern -Recurse -File
                } else {
                    $minify_files += Get-ChildItem -Path $min_path -Filter $pattern -File
                }
            }
        } elseif (Test-Path $min_path -PathType Leaf) {
            $minify_files += Get-Item $min_path
        } else {
            Write-Log "Path does not exist: $min_path" "WARNING"
        }
    }
    # Apply exclusion patterns and deduplicate
    if ($min_exclude_patterns) {
        $exclude_filters = $min_exclude_patterns.Split()
        $minify_files = $minify_files | Where-Object { $file = $_; -not ($exclude_filters | Where-Object { $file.Name -like $_ }) }
    }
    $minify_files = $minify_files | Select-Object -Unique | Sort-Object FullName

    $progress = 0
    $total = $minify_files.Count
    foreach ($file in $minify_files) {
        $filepath = $file.FullName
        $progress++
        Write-Progress -Activity "Minifying files" -Status "Processing $filepath" -PercentComplete ($progress / $total * 100)
        if ($processed_files -notcontains $filepath -and (Should-ProcessFile -FilePath $filepath)) {
            $minified_path = Minify-File -FilePath $filepath
            if ($minified_path) {
                $total_minified++
            }
            $processed_files += $filepath
        }
    }
    Write-Progress -Activity "Minifying files" -Completed
    Write-Log "Minification complete. $total_minified files minified."
    Write-Log ""
    $processed_files = @()
}

# Process concatenation
Write-Log "Starting concatenation process..."
for ($i = 0; $i -lt $sources.Length; $i++) {
    $src = $sources[$i]
    $src_path = $src.Path
    $src_recursive = $src.Recursive
    $src_patterns = $src.Patterns
    $use_minified = $src.UseMinified
    $src_exclude_patterns = $src.ExcludePatterns

    $source_file_count = 0
    $recursive_text = if ($src_recursive) { "recursive" } else { "non-recursive" }
    $minified_text = if ($use_minified) { "using minified" } else { "using original" }
    Write-Log "Processing source $($i + 1): ""$src_path"" ($recursive_text|$src_patterns|$minified_text|exclude: $src_exclude_patterns)"

    $files = @()
    if (Test-Path $src_path -PathType Container) {
        foreach ($pattern in $src_patterns.Split()) {
            if ($src_recursive) {
                $files += Get-ChildItem -Path $src_path -Include $pattern -Recurse -File
            } else {
                $files += Get-ChildItem -Path $src_path -Filter $pattern -File
            }
        }
        # Apply exclusion patterns
        if ($src_exclude_patterns) {
            $exclude_filters = $src_exclude_patterns.Split()
            $files = $files | Where-Object { $file = $_; -not ($exclude_filters | Where-Object { $file.Name -like $_ }) }
        }
        $files = $files | Select-Object -Unique | Sort-Object FullName
    } elseif (Test-Path $src_path -PathType Leaf) {
        $files += Get-Item $src_path
    } else {
        Write-Log "Path does not exist: $src_path" "WARNING"
        continue
    }

    $progress = 0
    $total = $files.Count
    foreach ($file in $files) {
        $filepath = $file.FullName
        $use_filepath = $filepath
        $progress++
        Write-Progress -Activity "Concatenating files" -Status "Processing $filepath" -PercentComplete ($progress / $total * 100)

        if ($use_minified -and $enable_minification) {
            $minified_path = [System.IO.Path]::ChangeExtension($filepath, ".min" + [System.IO.Path]::GetExtension($filepath))
            if (Test-Path $minified_path) {
                $use_filepath = $minified_path
            } else {
                Write-Log "Minified file not found, using original: $filepath" "WARNING"
            }
        }

        if ($processed_files -notcontains $use_filepath -and (Should-ProcessFile -FilePath $use_filepath)) {
            $processed_files += $use_filepath
            $console_filepath = if ($use_absolute_paths) { $use_filepath } else { $use_filepath | Resolve-Path -Relative | ForEach-Object { $_ -replace '^\.\\', '' } }
            Write-Log "Processing: $console_filepath"

            if ($dry_run) {
                Write-Log "Would concatenate: $console_filepath" "INFO"
                $source_file_count++
                $total_files++
                continue
            }

            if ($use_headers) {
                $header_filepath = if ($use_absolute_paths) { $use_filepath } else { $use_filepath | Resolve-Path -Relative | ForEach-Object { $_ -replace '^\.\\', '' } }
                "$header_start" | Out-File -FilePath $outfile -Append -Encoding utf8
                ($header_file -f $header_filepath) | Out-File -FilePath $outfile -Append -Encoding utf8
                "$header_end" | Out-File -FilePath $outfile -Append -Encoding utf8
            }

            Get-Content -Path $use_filepath | Out-File -FilePath $outfile -Append -Encoding utf8

            if ($add_blank_lines) {
                "" | Out-File -FilePath $outfile -Append -Encoding utf8
                "" | Out-File -FilePath $outfile -Append -Encoding utf8
            }

            $source_file_count++
            $total_files++
        }
    }
    Write-Progress -Activity "Concatenating files" -Completed
    Write-Log "Files processed: $source_file_count"
    Write-Log ""
}

# Update last run timestamp
if (-not $dry_run -and $incremental) {
    (Get-Date).ToString("o") | Out-File -FilePath $last_run_file -Encoding utf8
}

# Final summary
Write-Log ""
if ($total_files -eq 0) {
    Write-Log "No files were processed. Check source paths and patterns." "WARNING"
} elseif (-not (Test-Path $outfile) -and -not $dry_run) {
    Write-Log "Error: $outfile was not created. Check write permissions or script errors." "ERROR"
} else {
    Write-Log "Concatenation complete. $total_files files written to $outfile"
}

if ($dry_run) {
    Write-Log "Dry run completed. No files were modified."
}

Read-Host -Prompt "Press Enter to continue..."