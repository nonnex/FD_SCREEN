import os
import sys
import logging
import pathlib
import subprocess
import argparse
import json
from datetime import datetime
import shutil
from concurrent.futures import ThreadPoolExecutor

# Check Python version
if sys.version_info < (3, 6):
    print("Error: Python 3.6 or higher is required. Please install Python 3.6 or newer from python.org.")
    input("Press Enter to exit...")
    sys.exit(1)

# Initialize dependencies to None
pkg_resources = None
jsmin = None
cssmin = None
requests = None
tqdm = None
tenacity = None

# Attempt to import dependencies
try:
    from jsmin import jsmin as jsmin_module
    jsmin = jsmin_module
except ImportError:
    jsmin = None

try:
    from cssmin import cssmin as cssmin_module
    cssmin = cssmin_module
except ImportError:
    cssmin = None

try:
    import requests as requests_module
    requests = requests_module
except ImportError:
    requests = None

try:
    from tqdm import tqdm as tqdm_module
    tqdm = tqdm_module
except ImportError:
    tqdm = None
    def tqdm(iterable, *args, **kwargs):
        """Fallback for tqdm if missing."""
        return iterable

try:
    import pkg_resources as pkg_resources_module
    pkg_resources = pkg_resources_module
except ImportError:
    pkg_resources = None

try:
    from tenacity import retry, stop_after_attempt, wait_fixed, retry_if_exception_type
except ImportError:
    tenacity = None
    def retry(*args, **kwargs):
        """Fallback for tenacity retry if missing."""
        def decorator(func):
            def wrapper(*args, **kwargs):
                return func(*args, **kwargs)
            return wrapper
        return decorator
    stop_after_attempt = lambda x: None
    wait_fixed = lambda x: None
    retry_if_exception_type = lambda x: None

# --------------------------------------
# Configuration Section
# --------------------------------------

# Output file for concatenation
OUTFILE = "_concat_out.txt"

# Enable headers for each file
USE_HEADERS = True

# Header format
HEADER_START = "/*------------------------------"
HEADER_FILE = "File: {0}"
HEADER_END = "------------------------------*/"

# Use absolute paths in headers (True = absolute, False = relative)
USE_ABSOLUTE_PATHS = False

# Add blank lines between files
ADD_BLANK_LINES = True

# Enable minification
ENABLE_MINIFICATION = True

# Enable dry-run mode (preview only)
DRY_RUN = False

# Enable logging
ENABLE_LOGGING = True
LOG_FILE = "_concat_log.txt"

# Clean stale minified files
CLEAN_STALE_MINIFIED = True

# Incremental processing (process only modified files)
INCREMENTAL = True
LAST_RUN_FILE = "_concat_last_run.txt"

# Default minification and source definitions (fallback)
DEFAULT_MINIFY = [
    {"path": "js", "recursive": True, "patterns": ["*.js"], "exclude_patterns": ["*.test.js", "*.spec.js"]},
    {"path": "css", "recursive": True, "patterns": ["*.css"], "exclude_patterns": ["*.min.css"]},
    {"path": "css/style.css", "recursive": False, "patterns": ["*.css"], "exclude_patterns": []}
]

DEFAULT_SOURCES = [
    {"path": ".", "recursive": False, "patterns": ["*.php"], "use_minified": False, "exclude_patterns": []},
    {"path": "inc", "recursive": False, "patterns": ["*.php"], "use_minified": False, "exclude_patterns": []},
    {"path": "inc/db", "recursive": False, "patterns": ["*.php"], "use_minified": False, "exclude_patterns": []},
    {"path": "mock_data", "recursive": True, "patterns": ["*.php"], "use_minified": False, "exclude_patterns": []},
    {"path": "js", "recursive": False, "patterns": ["*.js"], "use_minified": True, "exclude_patterns": ["*.test.js", "*.spec.js"]},
    {"path": "css", "recursive": False, "patterns": ["*.css"], "use_minified": True, "exclude_patterns": ["*.min.css"]}
]

# Load configuration from JSON
CONFIG_FILE = "concat_config.json"
MINIFY = DEFAULT_MINIFY
SOURCES = DEFAULT_SOURCES

if os.path.exists(CONFIG_FILE):
    try:
        with open(CONFIG_FILE, "r", encoding="utf-8") as f:
            config = json.load(f)
        MINIFY = config.get("minify", DEFAULT_MINIFY)
        SOURCES = config.get("sources", DEFAULT_SOURCES)
    except Exception as e:
        print(f"Warning: Could not load {CONFIG_FILE}: {e}. Using default settings.")
        input("Press Enter to continue...")

# Dependencies with pinned versions
REQUIREMENTS = [
    "setuptools==75.1.0",
    "jsmin==3.0.0",
    "cssmin==0.2.0",
    "tqdm==4.66.5",
    "tenacity==8.5.0",
    "requests==2.32.3"
]

# --------------------------------------
# Dependency Management
# --------------------------------------

# Set up logging
logging.basicConfig(
    level=logging.INFO,
    format="%(message)s",
    handlers=[
        logging.FileHandler(LOG_FILE, encoding="utf-8") if ENABLE_LOGGING and not DRY_RUN else logging.NullHandler(),
        logging.StreamHandler()
    ]
)
logger = logging.getLogger()

def print_friendly_message(message, is_error=False):
    """Print a user-friendly message with clear formatting."""
    prefix = "===== ERROR =====" if is_error else "===== INFO ====="
    logger.info(f"\n{prefix}\n{message}\n{'=' * len(prefix)}\n")

def check_admin():
    """Check if running with elevated privileges (Windows-specific)."""
    if sys.platform == "win32":
        try:
            import ctypes
            return ctypes.windll.shell32.IsUserAnAdmin()
        except:
            return False
    return False

def check_pip():
    """Check if pip is available; attempt to install if missing."""
    try:
        result = subprocess.run(
            [sys.executable, "-m", "pip", "--version"],
            capture_output=True, text=True, check=True
        )
        logger.info(f"Found pip: {result.stdout.strip()}")
        return True
    except (subprocess.CalledProcessError, FileNotFoundError):
        print_friendly_message(
            "pip is missing. Let’s try to install it.\n"
            "1. Please wait while we set up pip.\n"
            "2. If this fails, download get-pip.py from https://bootstrap.pypa.io/get-pip.py and run: python get-pip.py",
            is_error=True
        )
        try:
            import ensurepip
            ensurepip.bootstrap()
            logger.info("pip installed successfully.")
            return True
        except Exception as e:
            print_friendly_message(
                f"Failed to install pip: {e}\n"
                "Please download get-pip.py from https://bootstrap.pypa.io/get-pip.py and run:\n"
                "python get-pip.py\n"
                "Then rerun this script.",
                is_error=True
            )
            return False

def check_pip_connectivity():
    """Check if pip can connect to PyPI."""
    try:
        result = subprocess.run(
            [sys.executable, "-m", "pip", "search", "pip"],
            capture_output=True, text=True, timeout=10
        )
        if result.returncode == 0:
            return True
        print_friendly_message(
            f"Cannot connect to the package server (PyPI): {result.stderr}\n"
            "1. Check your internet connection.\n"
            "2. If behind a proxy, set it with: pip install --proxy http://proxy:port setuptools\n"
            "3. Or download wheel files from https://pypi.org and install with: pip install <file>.whl",
            is_error=True
        )
        return False
    except (subprocess.CalledProcessError, subprocess.TimeoutExpired) as e:
        print_friendly_message(
            f"Cannot connect to the package server: {e}\n"
            "1. Check your internet connection.\n"
            "2. If behind a proxy, set it with: pip install --proxy http://proxy:port setuptools\n"
            "3. Or download wheel files from https://pypi.org and install with: pip install <file>.whl",
            is_error=True
        )
        return False

def check_internet():
    """Check if internet is available."""
    if requests is None:
        logger.warning("Cannot check internet because requests module is missing.")
        return True  # Assume internet is available to avoid blocking
    try:
        requests.get("https://www.google.com", timeout=5)
        return True
    except requests.RequestException:
        print_friendly_message(
            "No internet connection detected.\n"
            "1. Connect to the internet and try again.\n"
            "2. Or download wheel files for these packages from https://pypi.org:\n"
            "   - " + "\n   - ".join(REQUIREMENTS) + "\n"
            "   Then install with: pip install <file>.whl",
            is_error=True
        )
        return False

def in_virtualenv():
    """Check if running in a virtual environment."""
    return sys.prefix != sys.base_prefix

def create_virtualenv():
    """Create a virtual environment and guide user to activate it."""
    venv_dir = ".venv"
    if not os.path.exists(venv_dir):
        print_friendly_message(
            "We need to create a safe space (virtual environment) for the script.\n"
            "1. Please wait while we set it up.\n"
            "2. You’ll need to run one command afterward."
        )
        try:
            subprocess.check_call([sys.executable, "-m", "venv", venv_dir])
            activate_cmd = f".\\{venv_dir}\\Scripts\\activate" if sys.platform == "win32" else f"source {venv_dir}/bin/activate"
            print_friendly_message(
                f"Virtual environment created in {venv_dir}.\n"
                "Please follow these steps:\n"
                f"1. Open a new PowerShell window.\n"
                f"2. Run: cd {os.getcwd()}\n"
                f"3. Run: {activate_cmd}\n"
                f"4. Run: python _concat.py\n"
                "This will ensure everything works smoothly."
            )
            input("Press Enter to exit...")
            return False
        except Exception as e:
            print_friendly_message(
                f"Failed to create virtual environment: {e}\n"
                "Please create it manually:\n"
                f"1. Run: python -m venv {venv_dir}\n"
                f"2. Run: {venv_dir}\\Scripts\\activate (Windows) or source {venv_dir}/bin/activate (Linux/Mac)\n"
                f"3. Run: pip install {' '.join(REQUIREMENTS)}\n"
                f"4. Run: python _concat.py",
                is_error=True
            )
            return False
    return True

def install_package(package):
    """Install a single package using pip."""
    try:
        result = subprocess.run(
            [sys.executable, "-m", "pip", "install", package],
            capture_output=True, text=True, check=True
        )
        logger.debug(f"Installed {package}: {result.stdout}")
        return True
    except subprocess.CalledProcessError as e:
        print_friendly_message(
            f"Failed to install {package}: {e.stderr}\n"
            "Please try installing it manually:\n"
            f"1. Run: pip install {package}\n"
            "2. If it fails, check your internet or download the wheel file from https://pypi.org",
            is_error=True
        )
        return False

def manage_dependencies(no_install=False):
    """Check and install missing dependencies."""
    global pkg_resources

    logger.info(f"Checking setup in: {sys.prefix}")
    if check_admin():
        print_friendly_message(
            "You’re running PowerShell as an administrator, which can cause issues.\n"
            "Please follow these steps:\n"
            "1. Close this PowerShell window.\n"
            "2. Open a new PowerShell by pressing Win + X and selecting 'Windows PowerShell' (NOT 'Admin').\n"
            f"3. Run: cd {os.getcwd()}\n"
            f"4. Run: python _concat.py\n"
            "This will fix the issue."
        )
        input("Press Enter to exit...")
        return False

    if not check_pip():
        return False

    if not in_virtualenv():
        print_friendly_message(
            "This script needs to run in a virtual environment to avoid issues.\n"
            "We’ll create one for you now."
        )
        if not create_virtualenv():
            return False
        return False  # Exit to let user activate virtualenv

    if not check_internet() or not check_pip_connectivity():
        return False

    if pkg_resources is None:
        print_friendly_message(
            "The 'setuptools' package is missing. Let’s install it."
        )
        if no_install:
            print_friendly_message(
                "Cannot install setuptools because --no-install was used.\n"
                f"Please run: pip install {' '.join(REQUIREMENTS)}\n"
                "Then rerun: python _concat.py",
                is_error=True
            )
            return False
        if not install_package("setuptools==75.1.0"):
            return False
        try:
            import pkg_resources as pkg_resources_module
            pkg_resources = pkg_resources_module
            logger.info("setuptools installed successfully.")
        except ImportError:
            print_friendly_message(
                "Failed to load setuptools after installation.\n"
                f"Please run: pip install {' '.join(REQUIREMENTS)}\n"
                "Then rerun: python _concat.py",
                is_error=True
            )
            return False

    missing = []
    installed = []
    for pkg in REQUIREMENTS:
        pkg_name = pkg.split("==")[0]
        try:
            dist = pkg_resources.get_distribution(pkg_name)
            installed.append((pkg_name, dist.version))
            if dist.version != pkg.split("==")[1]:
                missing.append(pkg)
        except pkg_resources.DistributionNotFound:
            missing.append(pkg)

    print_friendly_message(
        "Checking required packages:\n" +
        "\n".join(f"- {name}: {ver}" for name, ver in installed) +
        (f"\nMissing or outdated:\n- {', '.join(missing)}" if missing else "\nAll packages are ready!")
    )

    if not missing:
        return True

    if no_install:
        print_friendly_message(
            f"Missing packages: {', '.join(missing)}\n"
            "Please install them manually:\n"
            f"1. Run: pip install {' '.join(missing)}\n"
            "2. Rerun: python _concat.py",
            is_error=True
        )
        return False

    print_friendly_message(
        f"Installing missing packages: {', '.join(missing)}"
    )
    for pkg in missing:
        if not install_package(pkg):
            return False

    # Verify installations
    missing_after = []
    for pkg in REQUIREMENTS:
        pkg_name = pkg.split("==")[0]
        try:
            dist = pkg_resources.get_distribution(pkg_name)
            if dist.version != pkg.split("==")[1]:
                missing_after.append(pkg)
        except pkg_resources.DistributionNotFound:
            missing_after.append(pkg)

    if missing_after:
        print_friendly_message(
            f"Some packages still missing: {', '.join(missing_after)}\n"
            "Please install them manually:\n"
            f"1. Run: pip install {' '.join(missing_after)}\n"
            "2. Rerun: python _concat.py",
            is_error=True
        )
        return False

    with open("requirements.txt", "w", encoding="utf-8") as f:
        f.write("\n".join(REQUIREMENTS))
    print_friendly_message("All packages installed and requirements.txt created.")
    return True

def cleanup_temp_files():
    """Clean up temporary pip cache files."""
    pip_cache = os.path.expanduser("~/.cache/pip")
    if os.path.exists(pip_cache):
        try:
            shutil.rmtree(pip_cache, ignore_errors=True)
            logger.info("Cleaned up pip cache.")
        except Exception as e:
            logger.warning(f"Failed to clean pip cache: {e}")

# --------------------------------------
# Script Logic
# --------------------------------------

def validate_environment():
    """Validate configuration and environment."""
    global ENABLE_MINIFICATION, ENABLE_LOGGING
    if ENABLE_MINIFICATION and (jsmin is None or cssmin is None):
        print_friendly_message(
            "Minification tools are missing. We’ll skip minification but still concatenate files.",
            is_error=True
        )
        ENABLE_MINIFICATION = False

    if not DRY_RUN and os.path.exists(OUTFILE):
        try:
            os.remove(OUTFILE)
            logger.info(f"Deleted existing output file: {OUTFILE}")
        except OSError as e:
            print_friendly_message(
                f"Cannot delete {OUTFILE}: {e}\n"
                "Please check if the file is open or you have permission to delete it.",
                is_error=True
            )
            sys.exit(1)

    if ENABLE_LOGGING and not DRY_RUN:
        try:
            with open(LOG_FILE, "w", encoding="utf-8") as f:
                f.write("")
            logger.info(f"Initialized log file: {LOG_FILE}")
        except OSError as e:
            logger.warning(f"Failed to initialize log file {LOG_FILE}: {e}")
            ENABLE_LOGGING = False

    for src in SOURCES:
        if not os.path.exists(src["path"]):
            logger.warning(f"Source path does not exist: {src['path']}")
    for min_item in MINIFY:
        if not os.path.exists(min_item["path"]):
            logger.warning(f"Minify path does not exist: {min_item['path']}")

def minify_file(filepath):
    """Minify a file and save as <filename>.min.<extension>."""
    path = pathlib.Path(filepath)
    ext = path.suffix.lower()
    minified_path = path.with_suffix(".min" + ext)
    original_size = path.stat().st_size / 1024

    # Skip if minified file is up-to-date
    if minified_path.exists():
        if minified_path.stat().st_mtime >= path.stat().st_mtime:
            logger.info(f"Skipped (up-to-date): {minified_path}")
            return str(minified_path)

    if DRY_RUN:
        logger.info(f"Would minify: {filepath} -> {minified_path}")
        return str(minified_path)

    try:
        with open(filepath, "r", encoding="utf-8") as f:
            content = f.read()
        
        if ext == ".js":
            result = jsmin(content)
        elif ext == ".css":
            result = cssmin(content)
        else:
            logger.warning(f"Unsupported file type for minification: {filepath}")
            return None

        with open(minified_path, "w", encoding="utf-8") as f:
            f.write(result)
        
        minified_size = minified_path.stat().st_size / 1024
        logger.info(f"Minified: {filepath} -> {minified_path} (Size: {original_size:.2f} KB -> {minified_size:.2f} KB)")
        return str(minified_path)
    except Exception as e:
        logger.error(f"Failed to minify {filepath}: {e}")
        return None

def clean_stale_minified(path, recursive, patterns):
    """Remove stale minified files."""
    files = get_files(path, recursive, patterns)
    for file in files:
        minified_path = pathlib.Path(file).with_suffix(".min" + pathlib.Path(file).suffix)
        if minified_path.exists():
            original_exists = os.path.exists(file)
            minified_mtime = minified_path.stat().st_mtime
            original_mtime = pathlib.Path(file).stat().st_mtime if original_exists else 0
            if not original_exists or minified_mtime < original_mtime:
                if DRY_RUN:
                    logger.info(f"Would delete stale minified file: {minified_path}")
                else:
                    minified_path.unlink(missing_ok=True)
                    logger.info(f"Deleted stale minified file: {minified_path}")

def should_process_file(filepath):
    """Check if file should be processed in incremental mode."""
    if not INCREMENTAL or not os.path.exists(LAST_RUN_FILE):
        return True
    try:
        with open(LAST_RUN_FILE, "r", encoding="utf-8") as f:
            last_run = datetime.fromisoformat(f.read().strip())
        file_mtime = datetime.fromtimestamp(os.path.getmtime(filepath))
        return file_mtime > last_run
    except Exception:
        return True

def get_files(path, recursive, patterns, exclude_patterns=None):
    """Get files matching patterns, respecting exclude patterns."""
    files = []
    path = pathlib.Path(path)
    exclude_patterns = exclude_patterns or []

    for pattern in patterns:
        if recursive:
            files.extend(path.rglob(pattern))
        else:
            files.extend(path.glob(pattern))

    # Apply exclude patterns
    filtered_files = []
    for file in files:
        if not any(file.match(excl) for excl in exclude_patterns):
            filtered_files.append(file)

    return sorted(set(str(f) for f in filtered_files))

def main(args):
    print_friendly_message("Starting the script to combine and optimize your files...")
    if not manage_dependencies(no_install=args.no_install):
        if ENABLE_MINIFICATION:
            print_friendly_message(
                "We couldn’t set up all required tools.\n"
                "Please follow the instructions above to install missing packages.\n"
                "If you need help, contact support or try again.",
                is_error=True
            )
            input("Press Enter to exit...")
            sys.exit(1)
        print_friendly_message(
            "Some tools are missing, so we’ll skip minification but still combine files."
        )

    if tqdm is None:
        logger.info("Progress bars disabled (tqdm missing).")
    if tenacity is None:
        logger.info("Dependency retries disabled (tenacity missing).")

    validate_environment()
    processed_files = set()
    total_files = 0
    total_minified = 0

    # Process minification
    if ENABLE_MINIFICATION:
        print_friendly_message("Minifying JavaScript and CSS files...")
        minify_files = []
        for i, min_item in enumerate(MINIFY, 1):
            path = min_item["path"]
            recursive = min_item["recursive"]
            patterns = min_item["patterns"]
            exclude_patterns = min_item["exclude_patterns"]
            recursive_text = "recursive" if recursive else "non-recursive"
            logger.info(f"Processing minification source {i}: \"{path}\" ({recursive_text}|{', '.join(patterns)}|exclude: {', '.join(exclude_patterns)})")
            
            if CLEAN_STALE_MINIFIED and not DRY_RUN:
                clean_stale_minified(path, recursive, patterns)
            
            minify_files.extend(get_files(path, recursive, patterns, exclude_patterns))
        
        minify_files = sorted(set(minify_files))
        with ThreadPoolExecutor() as executor:
            results = list(tqdm(
                executor.map(minify_file, minify_files),
                total=len(minify_files),
                desc="Minifying files",
                unit="file"
            ))
        total_minified = sum(1 for r in results if r is not None)
        processed_files.update(f for f in minify_files if f)
        
        print_friendly_message(f"Minified {total_minified} files.")
        processed_files.clear()

    # Process concatenation
    print_friendly_message("Combining files into _concat_out.txt...")
    with open(OUTFILE, "w", encoding="utf-8") if not DRY_RUN else open(os.devnull, "w") as out:
        for i, src in enumerate(SOURCES, 1):
            path = src["path"]
            recursive = src["recursive"]
            patterns = src["patterns"]
            use_minified = src["use_minified"]
            exclude_patterns = src["exclude_patterns"]
            recursive_text = "recursive" if recursive else "non-recursive"
            minified_text = "using minified" if use_minified else "using original"
            logger.info(f"Processing source {i}: \"{path}\" ({recursive_text}|{', '.join(patterns)}|{minified_text}|exclude: {', '.join(exclude_patterns)})")

            files = get_files(path, recursive, patterns, exclude_patterns)
            source_file_count = 0

            for filepath in tqdm(files, desc=f"Combining source {i}", unit="file"):
                use_filepath = filepath
                if use_minified and ENABLE_MINIFICATION:
                    minified_path = pathlib.Path(filepath).with_suffix(".min" + pathlib.Path(filepath).suffix)
                    if minified_path.exists():
                        use_filepath = str(minified_path)
                    else:
                        logger.warning(f"Minified file not found, using original: {filepath}")

                if use_filepath not in processed_files and should_process_file(use_filepath):
                    console_filepath = use_filepath if USE_ABSOLUTE_PATHS else os.path.relpath(use_filepath).replace("\\", "/")
                    logger.info(f"Processing: {console_filepath}")

                    if DRY_RUN:
                        logger.info(f"Would concatenate: {console_filepath}")
                        source_file_count += 1
                        total_files += 1
                        continue

                    if USE_HEADERS:
                        header_filepath = console_filepath
                        out.write(f"{HEADER_START}\n")
                        out.write(f"{HEADER_FILE.format(header_filepath)}\n")
                        out.write(f"{HEADER_END}\n")

                    with open(use_filepath, "r", encoding="utf-8") as f:
                        out.write(f.read() + "\n")

                    if ADD_BLANK_LINES:
                        out.write("\n\n")

                    source_file_count += 1
                    total_files += 1
                    processed_files.add(use_filepath)

            logger.info(f"Files processed: {source_file_count}")

    # Update last run timestamp
    if not DRY_RUN and INCREMENTAL:
        with open(LAST_RUN_FILE, "w", encoding="utf-8") as f:
            f.write(datetime.now().isoformat())

    # Clean up temporary files
    cleanup_temp_files()

    # Final summary
    if total_files == 0:
        print_friendly_message(
            "No files were processed. Please check:\n"
            "1. Your files are in the correct folders (js, css, inc, etc.).\n"
            "2. The concat_config.json file is correct (if you’re using one).\n"
            "3. Contact support if you need help.",
            is_error=True
        )
    elif not os.path.exists(OUTFILE) and not DRY_RUN:
        print_friendly_message(
            f"Could not create {OUTFILE}. Please check:\n"
            "1. You have permission to write files in this folder.\n"
            "2. The folder is not locked by another program.",
            is_error=True
        )
    else:
        print_friendly_message(
            f"Success! Combined {total_files} files into {OUTFILE}.\n"
            "Check the output file and let us know if you need help!"
        )

    if DRY_RUN:
        print_friendly_message("This was a test run. No files were changed.")

if __name__ == "__main__":
    parser = argparse.ArgumentParser(description="Concatenate and minify files.")
    parser.add_argument("--no-install", action="store_true", help="Skip automatic dependency installation (advanced users only)")
    args = parser.parse_args()
    main(args)
    input("Press Enter to exit...")