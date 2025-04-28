# --------------------------------------
# Configuration Section
# --------------------------------------

# Output file
$outfile = "_concat_ext.txt"

# Enable headers for each file ($true = yes, $false = no)
$use_headers = $true

# Header format
$header_start = "/*------------------------------"
$header_file  = "File: {0}"
$header_end   = "------------------------------*/"

# Use absolute paths in headers ($true = absolute, $false = relative)
$use_absolute_paths = $false

# Add blank lines between files ($true = yes, $false = no)
$add_blank_lines = $true

# Source definitions (array of hashtables)
# Format: @{ Path = "path"; Recursive = $true/$false; Patterns = "pattern1 pattern2 ..." }
$sources = @(
    @{ Path = ".";          Recursive = $false;     Patterns = "config.php index.php srv_*.php" }
    @{ Path = "inc";        Recursive = $false;     Patterns = "lx_orders.php" }
    @{ Path = "inc/db";     Recursive = $false;     Patterns = "*.php" }
	@{ Path = "mock_data";  Recursive = $false;     Patterns = "*.json" }
    @{ Path = "js";         Recursive = $false;     Patterns = "*.js" }
    #@{ Path = "css";        Recursive = $false;     Patterns = "*.css" }
)

# --------------------------------------
# Script Logic
# --------------------------------------

# Delete output file if it exists
if (Test-Path $outfile) {
    Remove-Item $outfile -ErrorAction Stop
}

# Initialize array to track processed files (to avoid duplicates)
$processed_files = @()

# Initialize total file counter
$total_files = 0

# Process each source
for ($i = 0; $i -lt $sources.Length; $i++) {
    $src = $sources[$i]
    $src_path = $src.Path
    $src_recursive = $src.Recursive
    $src_patterns = $src.Patterns

    # Initialize file counter for this source
    $source_file_count = 0

    # Display source info
    $recursive_text = if ($src_recursive) { "recursive" } else { "non-recursive" }
    Write-Host "Processing source $($i + 1): ""$src_path"" ($recursive_text|$src_patterns)"

    # Handle folder or file
    if (Test-Path $src_path -PathType Container) {
        # Path is a directory
        $files = @()
        foreach ($pattern in $src_patterns.Split()) {
            if ($src_recursive) {
                $files += Get-ChildItem -Path $src_path -Include $pattern -Recurse -File
            } else {
                $files += Get-ChildItem -Path $src_path -Filter $pattern -File
            }
        }

        # Remove duplicates and sort files
        $files = $files | Select-Object -Unique | Sort-Object FullName

        foreach ($file in $files) {
            $filepath = $file.FullName
            if ($processed_files -notcontains $filepath) {
                $processed_files += $filepath
                $console_filepath = if ($use_absolute_paths) { $filepath } else { $file.FullName | Resolve-Path -Relative | ForEach-Object { $_ -replace '^\.\\', '' } }
                Write-Host "  $console_filepath"

                # Add header (if enabled)
                if ($use_headers) {
                    $header_filepath = if ($use_absolute_paths) { $filepath } else { $file.FullName | Resolve-Path -Relative | ForEach-Object { $_ -replace '^\.\\', '' } }
                    "$header_start" | Out-File -FilePath $outfile -Append -Encoding utf8
                    ($header_file -f $header_filepath) | Out-File -FilePath $outfile -Append -Encoding utf8
                    "$header_end" | Out-File -FilePath $outfile -Append -Encoding utf8
                }

                # Append file content
                Get-Content -Path $filepath | Out-File -FilePath $outfile -Append -Encoding utf8

                # Add blank lines (if enabled)
                if ($add_blank_lines) {
                    "" | Out-File -FilePath $outfile -Append -Encoding utf8
                    "" | Out-File -FilePath $outfile -Append -Encoding utf8
                }

                $source_file_count++
                $total_files++
            }
        }
    } elseif (Test-Path $src_path -PathType Leaf) {
        # Path is a file
        if ($processed_files -notcontains $src_path) {
            $processed_files += $src_path
            $console_filepath = if ($use_absolute_paths) { $src_path } else { $src_path | Resolve-Path -Relative | ForEach-Object { $_ -replace '^\.\\', '' } }
            Write-Host "  $console_filepath"

            # Add header (if enabled)
            if ($use_headers) {
                $header_filepath = if ($use_absolute_paths) { $src_path } else { $src_path | Resolve-Path -Relative | ForEach-Object { $_ -replace '^\.\\', '' } }
                "$header_start" | Out-File -FilePath $outfile -Append -Encoding utf8
                ($header_file -f $header_filepath) | Out-File -FilePath $outfile -Append -Encoding utf8
                "$header_end" | Out-File -FilePath $outfile -Append -Encoding utf8
            }

            # Append file content
            Get-Content -Path $src_path | Out-File -FilePath $outfile -Append -Encoding utf8

            # Add blank lines (if enabled)
            if ($add_blank_lines) {
                "" | Out-File -FilePath $outfile -Append -Encoding utf8
                "" | Out-File -FilePath $outfile -Append -Encoding utf8
            }

            $source_file_count++
            $total_files++
        }
    } else {
        Write-Host "Warning: Path ""$src_path"" does not exist."
    }

    # Display file count
    Write-Host "(Files: $source_file_count)"
    Write-Host ""
}

# Final summary
Write-Host ""
if ($total_files -eq 0) {
    Write-Host "No files were processed. Check source paths and patterns."
} elseif (-not (Test-Path $outfile)) {
    Write-Host "Error: $outfile was not created. Check write permissions or script errors."
} else {
    Write-Host "Concatenation complete. $total_files files written to $outfile"
}

Read-Host -Prompt "Press Enter to continue..."